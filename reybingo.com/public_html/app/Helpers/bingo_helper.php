<?php

use App\Models\AwardsModel;
use App\Models\BoardsModel;
use App\Models\CartonsModel;
use App\Models\GamesModel;
use App\Models\ModalitiesModel;
use App\Models\NotificationsModel;
use App\Models\NumbersCartonsModel;
use App\Models\PaymentsModel;
use App\Models\SingsModel;
use App\Models\UsersModel;

if (! function_exists('bingo_get_ordered_drawn_numbers')) {
    function bingo_get_ordered_drawn_numbers(int $gameId): array
    {
        $model = new BoardsModel();
        $rows = $model
            ->where('game', $gameId)
            ->where('status', 1)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        return array_values(array_map('intval', array_column($rows, 'number')));
    }
}

if (! function_exists('bingo_count_drawn_numbers')) {
    function bingo_count_drawn_numbers(int $gameId): int
    {
        return count(bingo_get_ordered_drawn_numbers($gameId));
    }
}

if (! function_exists('bingo_sync_drawn_marks_for_user')) {
    function bingo_sync_drawn_marks_for_user(int $userId, int $gameId, array $drawnNumbers): void
    {
        if (empty($drawnNumbers)) {
            return;
        }

        $modelNumbersCartons = new NumbersCartonsModel();

        foreach ($drawnNumbers as $drawnNumber) {
            $existingNumbers = $modelNumbersCartons->getNumbersByUserAndGame($userId, $gameId, (int) $drawnNumber);

            if (! empty($existingNumbers)) {
                $ids = array_column($existingNumbers, 'id');
                $modelNumbersCartons->whereIn('id', $ids)->set(['status' => 1])->update();
            }
        }
    }
}

if (! function_exists('bingo_get_modality_match_result')) {
    function bingo_get_modality_match_result(array $requiredPositions, array $cartonNumbers, array $drawnNumbersArray): array
    {
        $required = array_values(array_unique(array_map('intval', array_filter($requiredPositions, static function ($position) {
            return $position !== '' && $position !== null;
        }))));

        if (empty($required)) {
            return ['complete' => false, 'winningNumbers' => []];
        }

        $drawnNumbersArray = array_map('intval', $drawnNumbersArray);
        $matchedPositions = [];

        foreach ($cartonNumbers as $cartonNumber) {
            $position = (int) ($cartonNumber['position'] ?? 0);
            $number = (int) ($cartonNumber['number'] ?? 0);

            if ($number < 1) {
                continue;
            }

            if (in_array($position, $required, true) && in_array($number, $drawnNumbersArray, true)) {
                $matchedPositions[$position] = $number;
            }
        }

        if (in_array(13, $required, true)) {
            $matchedPositions[13] = 0;
        }

        $winningNumbers = array_values(array_unique(array_filter($matchedPositions, static function ($number) {
            return (int) $number > 0;
        })));

        return [
            'complete' => count($matchedPositions) === count($required),
            'winningNumbers' => $winningNumbers,
        ];
    }
}

if (! function_exists('bingo_get_game_modalities')) {
    function bingo_get_game_modalities(array $game): array
    {
        $modelModalities = new ModalitiesModel();
        $modelAwards = new AwardsModel();
        $gameId = (int) ($game['id'] ?? 0);

        if ($gameId < 1) {
            return [];
        }

        $awardRows = $modelAwards->where('game', $gameId)->where('status', 1)->findAll();
        $idsFromAwards = array_values(array_unique(array_filter(array_map('intval', array_column($awardRows, 'modality')))));
        $idsFromGame = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) ($game['modalities'] ?? ''))))));
        $ids = array_values(array_unique(array_merge($idsFromAwards, $idsFromGame)));

        if (empty($ids)) {
            return [];
        }

        return $modelModalities->getModalitiesByIds($ids);
    }
}

if (! function_exists('bingo_get_number_sings_limit')) {
    function bingo_get_number_sings_limit(): int
    {
        $limit = (int) systemGet('numberSings');

        return $limit > 0 ? $limit : 1;
    }
}

if (! function_exists('bingo_filter_first_sing_per_modality')) {
    function bingo_filter_first_sing_per_modality(array $sings): array
    {
        $seen = [];
        $official = [];

        foreach ($sings as $sing) {
            $modalityId = (int) ($sing['modality'] ?? 0);
            if ($modalityId < 1 || isset($seen[$modalityId])) {
                continue;
            }

            $seen[$modalityId] = true;
            $official[] = $sing;
        }

        return $official;
    }
}

if (! function_exists('bingo_get_official_sings_for_game')) {
    function bingo_get_official_sings_for_game(int $gameId, bool $includePending = false): array
    {
        $modelSings = new SingsModel();
        $builder = $modelSings
            ->where('game', $gameId)
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC');

        if ($includePending) {
            $builder->whereIn('status', [0, 1, 2]);
        } else {
            $builder->whereIn('status', [1, 2]);
        }

        return bingo_filter_first_sing_per_modality($builder->findAll());
    }
}

if (! function_exists('bingo_finalize_game_when_complete')) {
    function bingo_finalize_game_when_complete(int $gameId): bool
    {
        if ($gameId < 1) {
            return false;
        }

        $modelSings = new SingsModel();
        $modelAwards = new AwardsModel();
        $modelGames = new GamesModel();

        $awardsCount = $modelAwards->where('game', $gameId)->where('status', 1)->countAllResults();
        if ($awardsCount < 1) {
            return false;
        }

        $singsCount = $modelSings
            ->select('modality')
            ->where('game', $gameId)
            ->groupBy('modality')
            ->countAllResults();

        if ($singsCount < $awardsCount) {
            return false;
        }

        bingo_ensure_winners_registered($gameId);
        $modelGames->where('id', $gameId)->where('status', 1)->set(['status' => 0])->update();

        return true;
    }
}

if (! function_exists('bingo_register_sing_if_missing')) {
    function bingo_register_sing_if_missing(
        int $gameId,
        int $userId,
        int $cartonId,
        array $modality,
        array $winningNumbers,
        int $lastBallNumber,
        bool $finalize
    ): bool {
        $numberSingsLimit = bingo_get_number_sings_limit();
        $modelSings = new SingsModel();
        $db = \Config\Database::connect();

        $db->transStart();

        $userAlreadySang = $modelSings
            ->where('game', $gameId)
            ->where('modality', $modality['id'])
            ->where('user', $userId)
            ->countAllResults(false);

        if ($userAlreadySang > 0) {
            $db->transRollback();

            return false;
        }

        $existingSings = $modelSings
            ->where('game', $gameId)
            ->where('modality', $modality['id'])
            ->countAllResults(false);

        if ($existingSings >= $numberSingsLimit) {
            $db->transRollback();

            return false;
        }

        $inserted = $modelSings->insert([
            'user' => $userId,
            'game' => $gameId,
            'carton' => $cartonId,
            'modality' => $modality['id'],
            'numbers' => implode(',', $winningNumbers),
            'lastnumber' => $lastBallNumber,
            'notified' => json_encode([]),
            'status' => $finalize ? 1 : 0,
        ]);

        $db->transComplete();

        return $inserted !== false && $db->transStatus();
    }
}

if (! function_exists('bingo_resolve_missed_bingos_for_game')) {
    function bingo_resolve_missed_bingos_for_game(int $gameId, bool $finalize = false): int
    {
        $modelBoards = new BoardsModel();
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $game = $modelGames->find($gameId);
        if (! $game) {
            return 0;
        }

        $lastBall = $modelBoards->where('game', $gameId)->orderBy('created_at', 'DESC')->first();
        if (! $lastBall) {
            return 0;
        }

        $drawnNumbersArray = bingo_get_ordered_drawn_numbers($gameId);
        if (empty($drawnNumbersArray)) {
            return 0;
        }

        $lastValidNumber = (int) end($drawnNumbersArray);
        $lastBallNumber = (int) $lastBall['number'];
        $modalities = bingo_get_game_modalities($game);
        $singBingoOnlyLastBall = (int) systemGet('singBingoOnlyLastBall') === 1;
        $cartons = $modelCartons->where('game', $gameId)->where('user !=', 0)->findAll();
        $syncedUsers = [];
        $registered = 0;

        foreach ($cartons as $carton) {
            $userId = (int) $carton['user'];

            if (! isset($syncedUsers[$userId])) {
                bingo_sync_drawn_marks_for_user($userId, $gameId, $drawnNumbersArray);
                $syncedUsers[$userId] = true;
            }
        }

        foreach ($modalities as $modality) {
            $numberSingsLimit = bingo_get_number_sings_limit();
            $existingForModality = (new SingsModel())
                ->where('game', $gameId)
                ->where('modality', $modality['id'])
                ->countAllResults();

            if ($existingForModality >= $numberSingsLimit) {
                continue;
            }

            $requiredPositions = explode(',', (string) $modality['positions']);

            foreach ($cartons as $carton) {
                $userId = (int) $carton['user'];
                $cartonId = (int) $carton['id'];

                if (! $finalize && $singBingoOnlyLastBall) {
                    $singLastNumber = (new SingsModel())
                        ->where('game', $gameId)
                        ->where('modality', $modality['id'])
                        ->first();

                    if ($singLastNumber && (int) $singLastNumber['lastnumber'] !== $lastBallNumber) {
                        continue;
                    }
                }

                $cartonNumbers = $modelNumbersCartons
                    ->where('carton', $cartonId)
                    ->orderBy('position', 'ASC')
                    ->findAll();

                if (empty($cartonNumbers)) {
                    continue;
                }

                $matchResult = bingo_get_modality_match_result($requiredPositions, $cartonNumbers, $drawnNumbersArray);
                if (! $matchResult['complete']) {
                    continue;
                }

                $winningNumbers = $matchResult['winningNumbers'];

                if (! $finalize && $singBingoOnlyLastBall && ! in_array($lastValidNumber, $winningNumbers, true)) {
                    continue;
                }

                if (bingo_register_sing_if_missing(
                    $gameId,
                    $userId,
                    $cartonId,
                    $modality,
                    $winningNumbers,
                    $lastBallNumber,
                    $finalize
                )) {
                    $registered++;
                    break;
                }
            }
        }

        return $registered;
    }
}

if (! function_exists('bingo_calculate_award_per_sing')) {
    function bingo_calculate_award_per_sing(array $game, array $award, int $gameId, int $modalityId): float
    {
        $modelSings = new SingsModel();
        $modelCartons = new CartonsModel();

        $singsCount = max(1, $modelSings->where('game', $gameId)->where('modality', $modalityId)->countAllResults());
        $cartons = $modelCartons->where('game', $gameId)->where('user !=', 0)->countAllResults();
        $accumulated = $cartons * (float) ($game['price'] ?? 0);
        $totalAward = $accumulated - ($accumulated * (float) systemGet('rateEarnings'));

        if ((int) ($game['award'] ?? 0) === 2) {
            return round((float) ($award['amount'] ?? 0) / $singsCount, 2);
        }

        return round(($totalAward * (float) ($award['amount'] ?? 0) / 100) / $singsCount, 2);
    }
}

if (! function_exists('bingo_notify_award_payment')) {
    function bingo_notify_award_payment(
        array $user,
        array $game,
        array $sing,
        array $modality,
        float $awardPerSing,
        int $paymentId,
        int $fromUserId
    ): void {
        $modelNotifications = new NotificationsModel();
        $currency = systemGet('currency');
        $modalityName = translate($modality['name'] ?? '');
        $gameName = $game['description'] ?? translate('game');

        $modelNotifications->insert([
            'user' => (int) $user['id'],
            'from' => $fromUserId,
            'game' => (int) $sing['game'],
            'modality' => (int) $sing['modality'],
            'type' => 'payment',
            'type_id' => $paymentId,
            'title' => '🎉 ¡GANASTE! Premio acreditado',
            'message' => 'Felicitaciones, ganaste la partida "' . $gameName . '" en la modalidad ' . $modalityName . '. Se acreditó ' . $currency . ' ' . number_format($awardPerSing, 2) . ' en tu billetera (saldo retirable).',
        ]);
    }
}

if (! function_exists('bingo_pay_pending_awards_for_game')) {
    function bingo_pay_pending_awards_for_game(int $gameId, ?int $fromUserId = null): int
    {
        if ($gameId < 1) {
            return 0;
        }

        $modelSings = new SingsModel();
        $modelAwards = new AwardsModel();
        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();
        $modelPayments = new PaymentsModel();
        $modelModalities = new ModalitiesModel();

        $game = $modelGames->find($gameId);
        if (! $game) {
            return 0;
        }

        if ($fromUserId === null || $fromUserId < 1) {
            $fromUserId = (int) ($game['user'] ?? 0);
        }

        if ($fromUserId < 1 && function_exists('session')) {
            $fromUserId = (int) (session()->get('id') ?? 0);
        }

        $pendingSings = bingo_filter_first_sing_per_modality(
            (new SingsModel())
                ->where('game', $gameId)
                ->where('status', 1)
                ->orderBy('created_at', 'ASC')
                ->orderBy('id', 'ASC')
                ->findAll()
        );

        $paid = 0;

        foreach ($pendingSings as $sing) {
            $existingPayment = (new PaymentsModel())
                ->where('type', 'award')
                ->where('type_id', (int) $sing['id'])
                ->first();

            if ($existingPayment) {
                if ((int) ($sing['status'] ?? 0) === 1) {
                    $modelSings->update($sing['id'], ['status' => 2]);
                }
                continue;
            }

            $award = $modelAwards
                ->where('game', $gameId)
                ->where('modality', $sing['modality'])
                ->where('status', 1)
                ->first();

            if (! $award) {
                continue;
            }

            $awardPerSing = bingo_calculate_award_per_sing(
                $game,
                $award,
                $gameId,
                (int) $sing['modality']
            );

            if ($awardPerSing <= 0) {
                continue;
            }

            $user = $modelUsers->find($sing['user']);
            if (! $user) {
                continue;
            }

            wallet_credit_withdrawable((int) $sing['user'], $awardPerSing);
            $modelSings->update($sing['id'], ['status' => 2]);

            $modelPayments->insert([
                'user' => (int) $sing['user'],
                'type' => 'award',
                'type_id' => (int) $sing['id'],
                'amount' => $awardPerSing,
                'status' => 2,
            ]);

            $paymentId = (int) $modelPayments->insertID();
            $modality = $modelModalities->find($sing['modality']) ?? ['name' => ''];

            bingo_notify_award_payment($user, $game, $sing, $modality, $awardPerSing, $paymentId, $fromUserId);
            $paid++;
        }

        return $paid;
    }
}

if (! function_exists('bingo_ensure_winners_registered')) {
    function bingo_ensure_winners_registered(int $gameId): void
    {
        if ($gameId < 1) {
            return;
        }

        $drawnNumbers = bingo_get_ordered_drawn_numbers($gameId);
        if (empty($drawnNumbers)) {
            return;
        }

        $modelSings = new SingsModel();
        $existingSings = $modelSings->where('game', $gameId)->countAllResults();

        if ($existingSings === 0) {
            bingo_resolve_missed_bingos_for_game($gameId, true);
        }

        $modelSings->where('game', $gameId)->where('status', 0)->set(['status' => 1])->update();
    }
}

if (! function_exists('bingo_count_game_players')) {
    function bingo_count_game_players(int $gameId): int
    {
        $modelCartons = new CartonsModel();

        return (int) $modelCartons
            ->where('game', $gameId)
            ->where('user !=', 0)
            ->select('user')
            ->distinct()
            ->countAllResults();
    }
}

if (! function_exists('bingo_get_min_players')) {
    function bingo_get_min_players(array $game): int
    {
        $min = (int) ($game['min_players'] ?? 10);

        return max(1, $min);
    }
}

if (! function_exists('bingo_can_start_game')) {
    function bingo_can_start_game(array $game, ?int $playerCount = null): bool
    {
        if ($playerCount === null) {
            $playerCount = bingo_count_game_players((int) $game['id']);
        }

        return $playerCount > bingo_get_min_players($game);
    }
}

if (! function_exists('bingo_min_players_start_message')) {
    function bingo_min_players_start_message(array $game, ?int $playerCount = null): string
    {
        if ($playerCount === null) {
            $playerCount = bingo_count_game_players((int) $game['id']);
        }

        $required = bingo_get_min_players($game);

        return str_replace(
            ['{min}', '{current}'],
            [(string) ($required + 1), (string) $playerCount],
            translate('the game needs more than {min} players to start. current players: {current}')
        );
    }
}
