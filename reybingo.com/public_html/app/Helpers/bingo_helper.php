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

if (!function_exists('bingo_get_ordered_drawn_numbers')) {
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

if (!function_exists('bingo_count_drawn_numbers')) {
    function bingo_count_drawn_numbers(int $gameId): int
    {
        return count(bingo_get_ordered_drawn_numbers($gameId));
    }
}

if (!function_exists('bingo_sync_drawn_marks_for_user')) {
    function bingo_sync_drawn_marks_for_user(int $userId, int $gameId, array $drawnNumbers): void
    {
        if (empty($drawnNumbers)) {
            return;
        }

        $modelNumbersCartons = new NumbersCartonsModel();

        foreach ($drawnNumbers as $drawnNumber) {
            $existingNumbers = $modelNumbersCartons->getNumbersByUserAndGame($userId, $gameId, (int) $drawnNumber);

            if (!empty($existingNumbers)) {
                $ids = array_column($existingNumbers, 'id');
                $modelNumbersCartons->whereIn('id', $ids)->set(['status' => 1])->update();
            }
        }
    }
}

if (!function_exists('bingo_get_modality_match_result')) {
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

if (!function_exists('bingo_get_game_modalities')) {
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

if (!function_exists('bingo_get_number_sings_limit')) {
    function bingo_get_number_sings_limit(): int
    {
        $limit = (int) systemGet('numberSings');

        return $limit > 0 ? $limit : 1;
    }
}

if (!function_exists('bingo_filter_first_sing_per_modality')) {
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

if (!function_exists('bingo_get_official_sings_for_game')) {
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

if (!function_exists('bingo_finalize_game_when_complete')) {
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

if (!function_exists('bingo_register_sing_if_missing')) {
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

if (!function_exists('bingo_resolve_missed_bingos_for_game')) {
    function bingo_resolve_missed_bingos_for_game(int $gameId, bool $finalize = false): int
    {
        $modelBoards = new BoardsModel();
        $modelGames = new GamesModel();
        $modelCartons = new CartonsModel();
        $modelNumbersCartons = new NumbersCartonsModel();
        $game = $modelGames->find($gameId);
        if (!$game) {
            return 0;
        }

        $lastBall = $modelBoards->where('game', $gameId)->orderBy('created_at', 'DESC')->first();
        if (!$lastBall) {
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

            if (!isset($syncedUsers[$userId])) {
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

                if (!$finalize && $singBingoOnlyLastBall) {
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
                if (!$matchResult['complete']) {
                    continue;
                }

                $winningNumbers = $matchResult['winningNumbers'];

                if (!$finalize && $singBingoOnlyLastBall && !in_array($lastValidNumber, $winningNumbers, true)) {
                    continue;
                }

                if (
                    bingo_register_sing_if_missing(
                        $gameId,
                        $userId,
                        $cartonId,
                        $modality,
                        $winningNumbers,
                        $lastBallNumber,
                        $finalize
                    )
                ) {
                    $registered++;
                    break;
                }
            }
        }

        return $registered;
    }
}

if (!function_exists('bingo_calculate_award_per_sing')) {
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

if (!function_exists('bingo_enrich_winner_sings')) {
    /**
     * @param list<array> $sings
     * @return list<array>
     */
    function bingo_enrich_winner_sings(array $sings): array
    {
        if ($sings === []) {
            return [];
        }

        $modelUsers = new \App\Models\UsersModel();
        $modelModalities = new \App\Models\ModalitiesModel();
        $modelGames = new \App\Models\GamesModel();
        $modelAwards = new \App\Models\AwardsModel();
        $modelCartons = new \App\Models\CartonsModel();
        $modelGameRooms = new \App\Models\GameRoomsModel();

        foreach ($sings as &$sing) {
            try {
                $user = $modelUsers->find($sing['user']);
                $modality = $modelModalities->find($sing['modality']);
                $game = $modelGames->where('id', $sing['game'])->first();
                $award = $modelAwards->where('game', $sing['game'])->where('modality', $sing['modality'])->first();
                $roomData = $game ? $modelGameRooms->where('id', $game['room'])->first() : null;
                $carton = $modelCartons->where('id', $sing['carton'])->first();

                $sing['serial'] = $carton ? $carton['serial'] : translate('serial not found');
                $sing['room_name'] = $roomData ? $roomData['name'] : translate('room not found');
                $sing['game_description'] = $game ? $game['description'] : translate('game not found');
                $sing['modality_name'] = $modality ? translate($modality['name']) : translate('modality not found');
                $sing['user_code'] = $user ? $user['code'] : translate('code not found');
                $sing['user_name'] = $user ? trim($user['firstname'] . ' ' . $user['lastname']) : translate('user not found');

                if ($game && $award) {
                    $awardPerSing = bingo_calculate_award_per_sing($game, $award, (int) $sing['game'], (int) $sing['modality']);
                    $sing['award_amount'] = number_format($awardPerSing, 2);
                    $sing['award_amount_raw'] = $awardPerSing;
                } else {
                    $sing['award_amount'] = translate('amount not available');
                    $sing['award_amount_raw'] = 0;
                }

                $sing['status_raw'] = (int) ($sing['status'] ?? 0);

                if ($sing['status_raw'] === 1) {
                    $sing['status'] = '<span class="status-badge"><span class="badge bg-warning"><i class="fa-duotone fa-solid fa-clock"></i> ' . translate('pending') . '</span></span>';
                } elseif ($sing['status_raw'] === 2) {
                    $sing['status'] = '<span class="status-badge"><span class="badge bg-success"><i class="fa-duotone fa-solid fa-check-double"></i> ' . translate('paid') . '</span></span>';
                } else {
                    $sing['status'] = '<span class="status-badge"><span class="badge bg-secondary"><i class="fa-duotone fa-solid fa-question"></i> ' . translate('unknown') . '</span></span>';
                }
            } catch (\Throwable $e) {
                log_message('error', 'Error processing sing ID ' . ($sing['id'] ?? '?') . ': ' . $e->getMessage());
                $sing['serial'] = translate('error');
                $sing['room_name'] = translate('error');
                $sing['game_description'] = translate('error');
                $sing['modality_name'] = translate('error');
                $sing['user_code'] = translate('error');
                $sing['user_name'] = translate('error');
                $sing['award_amount'] = translate('error');
                $sing['award_amount_raw'] = 0;
                $sing['status_raw'] = (int) ($sing['status'] ?? 0);
                $sing['status'] = '<span class="status-badge"><span class="badge bg-danger"><i class="fa-duotone fa-solid fa-exclamation-triangle"></i> ' . translate('error') . '</span></span>';
            }
        }
        unset($sing);

        return $sings;
    }
}

if (!function_exists('bingo_summarize_player_prizes')) {
    /**
     * @return array{count:int,total:float,total_formatted:string,items:list<array>}
     */
    function bingo_summarize_player_prizes(int $userId, string $status = '1'): array
    {
        if ($userId <= 0) {
            return [
                'count' => 0,
                'total' => 0.0,
                'total_formatted' => number_format(0, 2),
                'items' => [],
            ];
        }

        $modelSings = new \App\Models\SingsModel();
        $builder = $modelSings->where('user', $userId);

        if ($status !== 'all' && $status !== '') {
            $builder->where('status', (int) $status);
        }

        $sings = $builder->orderBy('id', 'DESC')->findAll();
        $enriched = bingo_enrich_winner_sings($sings);

        $total = 0.0;
        $items = [];

        foreach ($enriched as $sing) {
            $amount = (float) ($sing['award_amount_raw'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $total += $amount;
            $items[] = [
                'id' => (int) ($sing['id'] ?? 0),
                'game' => (string) ($sing['game_description'] ?? ''),
                'modality' => (string) ($sing['modality_name'] ?? ''),
                'amount' => $amount,
                'amount_formatted' => number_format($amount, 2),
                'status_raw' => (int) ($sing['status_raw'] ?? 0),
            ];
        }

        return [
            'count' => count($items),
            'total' => round($total, 2),
            'total_formatted' => number_format($total, 2),
            'items' => $items,
        ];
    }
}

if (!function_exists('bingo_pay_sing_award')) {
    /**
     * @return array{success:bool,error?:string,amount?:string,store_balance?:float}
     */
    function bingo_pay_sing_award(int $singId, int $paidByUserId, array $options = []): array
    {
        $debitStore = (bool) ($options['debit_store'] ?? false);
        $storeId = (int) ($options['store_id'] ?? 0);

        $modelSings = new \App\Models\SingsModel();
        $modelAwards = new \App\Models\AwardsModel();
        $modelUsers = new \App\Models\UsersModel();
        $modelGames = new \App\Models\GamesModel();
        $modelPayments = new \App\Models\PaymentsModel();
        $modelModalities = new \App\Models\ModalitiesModel();

        $sing = $modelSings->find($singId);
        if (!$sing) {
            return ['success' => false, 'error' => translate('sing not found')];
        }

        $game = $modelGames->find($sing['game']);
        if (!$game) {
            return ['success' => false, 'error' => translate('game not found')];
        }

        $award = $modelAwards->where('game', $sing['game'])
            ->where('modality', $sing['modality'])
            ->where('status', 1)
            ->first();

        if (!$award) {
            return ['success' => false, 'error' => translate('award not found')];
        }

        $awardPerSing = bingo_calculate_award_per_sing($game, $award, (int) $sing['game'], (int) $sing['modality']);
        $user = $modelUsers->find($sing['user']);
        if (!$user) {
            return ['success' => false, 'error' => translate('user not found')];
        }

        $singStatus = (int) ($sing['status'] ?? 0);
        if ($singStatus === 2) {
            return ['success' => false, 'error' => translate('this award has already been paid')];
        }

        if ($singStatus !== 1) {
            return ['success' => false, 'error' => translate('the winner is not pending payment')];
        }

        if ($awardPerSing <= 0) {
            $modelSings->update($singId, ['status' => 2]);
            return ['success' => true, 'amount' => '0.00'];
        }

        $storeRow = null;

        if ($debitStore) {
            if ($storeId <= 0) {
                return ['success' => false, 'error' => translate('unauthorized')];
            }

            $storeRow = $modelUsers->find($storeId);
            if (!$storeRow || (int) ($storeRow['group'] ?? -1) !== bingo_group_store()) {
                return ['success' => false, 'error' => translate('unauthorized')];
            }

            $store = wallet_service()->normalizeUser($storeRow);
            if (!$store) {
                return ['success' => false, 'error' => translate('store not found')];
            }

            if (wallet_recharge_balance($store) < $awardPerSing) {
                return ['success' => false, 'error' => translate('insufficient store balance request admin first')];
            }

            if (!wallet_deduct_recharge($storeId, $awardPerSing)) {
                return ['success' => false, 'error' => translate('insufficient store balance request admin first')];
            }
        }

        wallet_credit_withdrawable((int) $sing['user'], $awardPerSing);
        $modelSings->update($singId, ['status' => 2]);

        $modelPayments->insert([
            'user' => (int) $sing['user'],
            'type' => 'award',
            'type_id' => $singId,
            'amount' => $awardPerSing,
            'status' => 2,
        ]);
        $paymentId = (int) $modelPayments->insertID();

        if ($debitStore) {
            $modelPayments->insert([
                'user' => $storeId,
                'type' => 'store_award',
                'type_id' => $singId,
                'amount' => $awardPerSing,
                'status' => 2,
            ]);
        }

        $prizeCommission = 0.0;
        if ($debitStore && $storeRow) {
            $prizeCommission = bingo_credit_store_operation_commission(
                $storeId,
                $awardPerSing,
                'store_prize_commission',
                $singId,
                $storeRow
            );
        }

        $modalitySing = $modelModalities->find($sing['modality']) ?? ['name' => ''];
        bingo_notify_award_payment($user, $game, $sing, $modalitySing, $awardPerSing, $paymentId, $paidByUserId);

        helper('affiliate_ggr');
        bingo_record_ggr_payout((int) $sing['user'], (int) $game['id'], $awardPerSing, 'award', $singId);
        bingo_settle_player_game_ggr_commissions((int) $sing['user'], (int) $game['id'], $paidByUserId);

        $result = [
            'success' => true,
            'amount' => number_format($awardPerSing, 2, '.', ''),
        ];

        if ($debitStore) {
            $updatedStore = wallet_service()->normalizeUser($modelUsers->find($storeId));
            $result['store_balance'] = wallet_recharge_balance($updatedStore ?: []);
            if ($prizeCommission > 0) {
                $result['commission'] = number_format($prizeCommission, 2, '.', '');
            }
        }

        return $result;
    }
}

if (!function_exists('bingo_notify_award_payment')) {
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

if (!function_exists('bingo_pay_pending_awards_for_game')) {
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
        if (!$game) {
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

            if (!$award) {
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
            if (!$user) {
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
            bingo_record_ggr_payout((int) $sing['user'], $gameId, $awardPerSing, 'award', (int) $sing['id']);

            $modality = $modelModalities->find($sing['modality']) ?? ['name' => ''];

            bingo_notify_award_payment($user, $game, $sing, $modality, $awardPerSing, $paymentId, $fromUserId);
            bingo_settle_player_game_ggr_commissions((int) $sing['user'], $gameId, $fromUserId);
            $paid++;
        }

        return $paid;
    }
}

if (!function_exists('bingo_ensure_winners_registered')) {
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

if (!function_exists('bingo_count_game_players')) {
    function bingo_count_game_players(int $gameId): int
    {
        $db = \Config\Database::connect();

        $c = $db->table('cartons')->where('game', $gameId)->select('user')->distinct()->get()->getResultArray();
        $t = $db->table('temp_cartons')->where('game', $gameId)->select('user')->distinct()->get()->getResultArray();
        
        $users = [];
        foreach ($c as $row) {
            if (!empty($row['user'])) {
                $users[] = $row['user'];
            }
        }
        foreach ($t as $row) {
            if (!empty($row['user'])) {
                $users[] = $row['user'];
            }
        }
        
        return count(array_unique($users));
    }
}

if (!function_exists('bingo_count_game_cartons')) {
    /**
     * Cuenta cartones activos (tabla cartons).
     * Para modo LIVE también incluye cartones en selección temporal (temp_cartons).
     */
    function bingo_count_game_cartons(int $gameId): int
    {
        $db = \Config\Database::connect();

        $c = $db->table('cartons')->where('game', $gameId)->countAllResults();
        $t = $db->table('temp_cartons')->where('game', $gameId)->countAllResults();

        return $c + $t;
    }
}

if (!function_exists('bingo_get_min_players')) {
    function bingo_get_min_players(array $game): int
    {
        $min = (int) ($game['min_players'] ?? 10);

        return max(1, $min);
    }
}

if (!function_exists('bingo_get_min_cartons')) {
    function bingo_get_min_cartons(array $game): int
    {
        $min = (int) ($game['min_cartons'] ?? 10);

        return max(1, $min);
    }
}

if (!function_exists('bingo_can_start_game')) {
    function bingo_can_start_game(array $game, ?int $playerCount = null, ?int $cartonCount = null): bool
    {
        // Para juegos tipo LIVE (type=3 o type=4), el admin controla todo manualmente
        $isLiveGame = ((int) ($game['type'] ?? 0)) === 3 || ((int) ($game['type'] ?? 0)) === 4;
        if ($isLiveGame) {
            return true;
        }

        // Permitir que el admin inicie cualquier partida manualmente, saltando el bloqueo
        if (session()->get('logged_in') && session()->get('group') == 1) {
            return true;
        }

        if ($playerCount === null) {
            $playerCount = bingo_count_game_players((int) $game['id']);
        }

        if ($cartonCount === null) {
            $cartonCount = bingo_count_game_cartons((int) $game['id']);
        }

        // Se permite iniciar si cumple el mínimo de jugadores O el mínimo de cartones,
        // para soportar casos donde un Punto de Venta compra múltiples cartones para varios jugadores físicos.
        return $playerCount >= bingo_get_min_players($game)
            || $cartonCount >= bingo_get_min_cartons($game);
    }
}

if (!function_exists('bingo_min_players_start_message')) {
    function bingo_min_players_start_message(array $game, ?int $playerCount = null): string
    {
        if ($playerCount === null) {
            $playerCount = bingo_count_game_players((int) $game['id']);
        }

        $required = bingo_get_min_players($game);

        return str_replace(
            ['{min}', '{current}'],
            [(string) $required, (string) $playerCount],
            translate('the game needs at least {min} players to start. current players: {current}')
        );
    }
}

if (!function_exists('bingo_min_cartons_start_message')) {
    function bingo_min_cartons_start_message(array $game, ?int $cartonCount = null): string
    {
        if ($cartonCount === null) {
            $cartonCount = bingo_count_game_cartons((int) $game['id']);
        }

        $required = bingo_get_min_cartons($game);

        return str_replace(
            ['{min}', '{current}'],
            [(string) $required, (string) $cartonCount],
            translate('the game needs at least {min} cartons to start. current cartons: {current}')
        );
    }
}

if (!function_exists('bingo_game_start_block_message')) {
    function bingo_game_start_block_message(array $game, ?int $playerCount = null, ?int $cartonCount = null): string
    {
        if ($playerCount === null) {
            $playerCount = bingo_count_game_players((int) $game['id']);
        }

        if ($cartonCount === null) {
            $cartonCount = bingo_count_game_cartons((int) $game['id']);
        }

        $messages = [];

        // Si la partida NO puede iniciar (es decir, falló en AMBOS requisitos), mostramos los mensajes
        if ($playerCount < bingo_get_min_players($game) && $cartonCount < bingo_get_min_cartons($game)) {
            $messages[] = bingo_min_players_start_message($game, $playerCount);
            $messages[] = bingo_min_cartons_start_message($game, $cartonCount);
        }

        return implode(' ', $messages);
    }
}

if (!function_exists('bingo_calculate_game_prize_pool')) {
    function bingo_calculate_game_prize_pool(array $game, int $cartonCount): float
    {
        $accumulated = $cartonCount * (float) ($game['price'] ?? 0);

        return $accumulated - ($accumulated * (float) systemGet('rateEarnings'));
    }
}

if (!function_exists('bingo_calculate_game_award_total')) {
    function bingo_calculate_game_award_total(array $game, int $cartonCount, array $awards, bool $forDisplay = false): float
    {
        if (empty($awards)) {
            return 0.0;
        }

        $awardType = (int) ($game['award'] ?? 1);

        if ($awardType === 2) {
            $total = 0.0;
            foreach ($awards as $award) {
                $total += (float) ($award['amount'] ?? 0);
            }

            return $total;
        }

        $poolCartons = $cartonCount;
        if ($forDisplay && $poolCartons === 0) {
            $poolCartons = bingo_get_min_cartons($game);
        }

        $prizePool = bingo_calculate_game_prize_pool($game, $poolCartons);
        $total = 0.0;

        foreach ($awards as $award) {
            $total += $prizePool * (float) ($award['amount'] ?? 0) / 100;
        }

        return $total;
    }
}

if (!function_exists('bingo_get_game_award_total_for_display')) {
    function bingo_get_game_award_total_for_display(array $game, int $cartonCount): float
    {
        $awardModel = new AwardsModel();
        $awards = $awardModel->where('game', (int) $game['id'])->where('status', 1)->findAll();

        return bingo_calculate_game_award_total($game, $cartonCount, $awards, true);
    }
}

if (!function_exists('bingo_calculate_single_award_amount')) {
    function bingo_calculate_single_award_amount(array $game, array $award, int $cartonCount): float
    {
        if (empty($award)) {
            return 0.0;
        }

        $awardType = (int) ($game['award'] ?? 1);

        if ($awardType === 2) { // Fijo
            return (float) ($award['amount'] ?? 0);
        }

        // Acumulado
        $prizePool = bingo_calculate_game_prize_pool($game, $cartonCount);
        return $prizePool * ((float) ($award['amount'] ?? 0) / 100);
    }
}

if (!function_exists('bingo_group_player')) {
    function bingo_group_player(): int
    {
        return 0;
    }
}

if (!function_exists('bingo_group_admin')) {
    function bingo_group_admin(): int
    {
        return 1;
    }
}

if (!function_exists('bingo_group_store')) {
    function bingo_group_store(): int
    {
        return 2;
    }
}

if (!function_exists('bingo_group_operator')) {
    function bingo_group_operator(): int
    {
        return 3;
    }
}

if (!function_exists('bingo_is_store')) {
    function bingo_is_store(?int $group = null): bool
    {
        if ($group === null) {
            $group = (int) session()->get('group');
        }

        return $group === bingo_group_store();
    }
}

if (!function_exists('bingo_is_operator')) {
    function bingo_is_operator(?int $group = null): bool
    {
        if ($group === null) {
            $group = (int) session()->get('group');
        }

        return $group === bingo_group_operator();
    }
}

if (!function_exists('bingo_get_acting_store_id')) {
    function bingo_get_acting_store_id(): int
    {
        return (int) (session()->get('acting_store_id') ?? 0);
    }
}

if (!function_exists('bingo_set_acting_store_id')) {
    function bingo_set_acting_store_id(int $storeId): void
    {
        if ($storeId > 0) {
            session()->set('acting_store_id', $storeId);
        } else {
            session()->remove('acting_store_id');
        }
    }
}

if (!function_exists('bingo_operator_can_access_store')) {
    function bingo_operator_can_access_store(int $operatorId, int $storeId): bool
    {
        if ($operatorId <= 0 || $storeId <= 0) {
            return false;
        }

        bingo_ensure_users_schema();

        $modelUsers = new \App\Models\UsersModel();
        $store = $modelUsers
            ->where('id', $storeId)
            ->where('group', bingo_group_store())
            ->where('operator_id', $operatorId)
            ->where('deleted', 0)
            ->where('status', 1)
            ->first();

        return (bool) $store;
    }
}

if (!function_exists('bingo_list_operators')) {
    function bingo_list_operators(bool $activeOnly = true): array
    {
        $modelUsers = new \App\Models\UsersModel();
        $builder = $modelUsers
            ->where('group', bingo_group_operator())
            ->where('deleted', 0)
            ->orderBy('firstname', 'ASC');

        if ($activeOnly) {
            $builder->where('status', 1);
        }

        return $builder->findAll();
    }
}

if (!function_exists('bingo_assign_store_operator')) {
    function bingo_assign_store_operator(int $storeId, ?int $operatorId): void
    {
        bingo_ensure_users_schema();

        if ($storeId <= 0) {
            return;
        }

        $modelUsers = new \App\Models\UsersModel();
        $modelUsers->update($storeId, [
            'operator_id' => ($operatorId !== null && $operatorId > 0) ? $operatorId : null,
        ]);
    }
}

if (!function_exists('bingo_sync_operator_stores')) {
    function bingo_sync_operator_stores(int $operatorId, array $storeIds): void
    {
        bingo_ensure_users_schema();

        if ($operatorId <= 0) {
            return;
        }

        $modelUsers = new \App\Models\UsersModel();
        $currentStores = $modelUsers
            ->where('group', bingo_group_store())
            ->where('operator_id', $operatorId)
            ->where('deleted', 0)
            ->findAll();

        foreach ($currentStores as $store) {
            $modelUsers->update((int) $store['id'], ['operator_id' => null]);
        }

        foreach ($storeIds as $storeId) {
            $storeId = (int) $storeId;
            if ($storeId <= 0) {
                continue;
            }

            $store = $modelUsers
                ->where('id', $storeId)
                ->where('group', bingo_group_store())
                ->where('deleted', 0)
                ->first();

            if ($store) {
                $modelUsers->update($storeId, ['operator_id' => $operatorId]);
            }
        }
    }
}

if (!function_exists('bingo_operator_store_count')) {
    function bingo_operator_store_count(int $operatorId): int
    {
        bingo_ensure_users_schema();

        if ($operatorId <= 0) {
            return 0;
        }

        $modelUsers = new \App\Models\UsersModel();

        return (int) $modelUsers
            ->where('group', bingo_group_store())
            ->where('operator_id', $operatorId)
            ->where('deleted', 0)
            ->countAllResults();
    }
}

if (!function_exists('bingo_generate_operator_username')) {
    function bingo_generate_operator_username(string $email, \App\Models\UsersModel $model, ?int $excludeId = null): string
    {
        $local = strstr($email, '@', true) ?: 'operador';
        $base = strtolower(preg_replace('/[^a-z0-9]/', '', $local));
        $base = substr($base, 0, 20) ?: 'operador';
        $candidate = $base;
        $suffix = 1;

        while (true) {
            $builder = $model->where('username', $candidate);
            if ($excludeId) {
                $builder = $builder->where('id !=', $excludeId);
            }
            if (!$builder->first()) {
                return $candidate;
            }
            $candidate = $base . $suffix;
            $suffix++;
        }
    }
}

if (!function_exists('bingo_enrich_stores_with_operator')) {
    function bingo_enrich_stores_with_operator(array $stores): array
    {
        bingo_ensure_users_schema();
        $modelUsers = new \App\Models\UsersModel();
        $operatorCache = [];

        foreach ($stores as &$store) {
            $operatorId = (int) ($store['operator_id'] ?? 0);
            if ($operatorId <= 0) {
                $store['operator_name'] = '';
                continue;
            }

            if (!isset($operatorCache[$operatorId])) {
                $operator = $modelUsers->find($operatorId);
                $operatorCache[$operatorId] = $operator
                    ? trim(($operator['firstname'] ?? '') . ' ' . ($operator['lastname'] ?? ''))
                    : '';
            }

            $store['operator_name'] = $operatorCache[$operatorId];
        }

        return $stores;
    }
}

if (!function_exists('bingo_user_requires_kyc')) {
    function bingo_user_requires_kyc(?array $user = null): bool
    {
        if ($user !== null) {
            $group = (int) ($user['group'] ?? -1);

            return !bingo_is_store($group) && !bingo_is_operator($group);
        }

        return !bingo_is_store() && !bingo_is_operator();
    }
}

if (!function_exists('bingo_is_admin')) {
    function bingo_is_admin(?int $group = null): bool
    {
        if ($group === null) {
            $group = (int) session()->get('group');
        }

        return $group === bingo_group_admin();
    }
}

if (!function_exists('bingo_is_player')) {
    function bingo_is_player(?int $group = null): bool
    {
        if ($group === null) {
            $group = (int) session()->get('group');
        }

        return $group === bingo_group_player();
    }
}

if (!function_exists('bingo_ensure_deposits_schema')) {
    function bingo_ensure_deposits_schema(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('deposits')) {
                return;
            }

            $forge = \Config\Database::forge();

            if (!$db->fieldExists('store', 'deposits')) {
                $forge->addColumn('deposits', [
                    'store' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                        'default' => null,
                        'after' => 'user',
                    ],
                ]);
            }

            if (!$db->fieldExists('commission_amount', 'deposits')) {
                $forge->addColumn('deposits', [
                    'commission_amount' => [
                        'type' => 'DECIMAL',
                        'constraint' => '12,2',
                        'null' => true,
                        'default' => null,
                        'after' => 'amount',
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo actualizar el esquema de deposits: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_ensure_games_schema')) {
    function bingo_ensure_games_schema(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('games')) {
                return;
            }

            $forge = \Config\Database::forge();

            if (!$db->fieldExists('min_players', 'games')) {
                $forge->addColumn('games', [
                    'min_players' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'default' => 10,
                        'null' => false,
                        'after' => 'price',
                    ],
                ]);
            }

            if (!$db->fieldExists('min_cartons', 'games')) {
                $after = $db->fieldExists('min_players', 'games') ? 'min_players' : 'price';
                $forge->addColumn('games', [
                    'min_cartons' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'default' => 10,
                        'null' => false,
                        'after' => $after,
                    ],
                ]);
            }

            if (!$db->fieldExists('allow_roulette_cartons', 'games')) {
                $after = $db->fieldExists('min_cartons', 'games') ? 'min_cartons' : 'price';
                $forge->addColumn('games', [
                    'allow_roulette_cartons' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'unsigned' => true,
                        'default' => 1,
                        'null' => false,
                        'after' => $after,
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo actualizar el esquema de games: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_roulette_carton_price')) {
    /** Precio de cartón requerido para usar premios de ruleta. */
    function bingo_roulette_carton_price(): float
    {
        return 0.25;
    }
}

if (!function_exists('bingo_game_allows_roulette_cartons')) {
    function bingo_game_allows_roulette_cartons(?array $game): bool
    {
        if (!$game) {
            return false;
        }

        if ((int) ($game['allow_roulette_cartons'] ?? 1) !== 1) {
            return false;
        }

        // Solo salas con cartones a 0.25
        return abs((float) ($game['price'] ?? 0) - bingo_roulette_carton_price()) < 0.001;
    }
}

if (!function_exists('bingo_ensure_users_schema')) {
    function bingo_ensure_users_schema(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('users')) {
                return;
            }

            $forge = \Config\Database::forge();

            if (!$db->fieldExists('business_name', 'users')) {
                $forge->addColumn('users', [
                    'business_name' => [
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                        'null' => true,
                        'default' => null,
                        'after' => 'lastname',
                    ],
                ]);
            }

            if (!$db->fieldExists('store_commission_rate', 'users')) {
                $forge->addColumn('users', [
                    'store_commission_rate' => [
                        'type' => 'DECIMAL',
                        'constraint' => '8,4',
                        'null' => true,
                        'default' => null,
                        'after' => 'business_name',
                    ],
                ]);
            }

            if (!$db->fieldExists('store_prize_commission_rate', 'users')) {
                $forge->addColumn('users', [
                    'store_prize_commission_rate' => [
                        'type' => 'DECIMAL',
                        'constraint' => '8,4',
                        'null' => true,
                        'default' => null,
                        'after' => 'store_commission_rate',
                    ],
                ]);
            }

            if (!$db->fieldExists('low_balance_alert', 'users')) {
                $forge->addColumn('users', [
                    'low_balance_alert' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'unsigned' => true,
                        'null' => false,
                        'default' => 0,
                        'after' => 'roulette',
                    ],
                ]);
            }

            if (!$db->fieldExists('referred_store_id', 'users')) {
                $forge->addColumn('users', [
                    'referred_store_id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                        'default' => null,
                        'after' => 'referred_code',
                    ],
                ]);
            }

            if (!$db->fieldExists('operator_id', 'users')) {
                $forge->addColumn('users', [
                    'operator_id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                        'default' => null,
                        'after' => 'referred_store_id',
                    ],
                ]);
            }

            if (!$db->fieldExists('referred_operator_id', 'users')) {
                $forge->addColumn('users', [
                    'referred_operator_id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                        'default' => null,
                        'after' => 'operator_id',
                    ],
                ]);
            }

            if (!$db->fieldExists('affiliate_signup_store_id', 'users')) {
                $forge->addColumn('users', [
                    'affiliate_signup_store_id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                        'default' => null,
                        'after' => 'referred_store_id',
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo actualizar el esquema de users: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_ensure_system_settings_schema')) {
    function bingo_ensure_system_settings_schema(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            $db = \Config\Database::connect();
            
            try {
                $db->query("ALTER TABLE `notifications` MODIFY COLUMN `type` ENUM('message','sing','system','low_balance','deposit','withdraw','purchase') NOT NULL DEFAULT 'system'");
            } catch (\Exception $e) {}

            if (!$db->tableExists('system')) {
                return;
            }

            if ($db->table('system')->where('key', 'rateStoreCommission')->countAllResults() === 0) {
                $db->table('system')->insert([
                    'key' => 'rateStoreCommission',
                    'value' => '0',
                ]);
            }

            if ($db->table('system')->where('key', 'rateStoreGgrCommission')->countAllResults() === 0) {
                $existingStoreRate = $db->table('system')->where('key', 'rateStoreCommission')->get()->getRowArray();
                $db->table('system')->insert([
                    'key' => 'rateStoreGgrCommission',
                    'value' => (string) ($existingStoreRate['value'] ?? '0'),
                ]);
            }

            if ($db->table('system')->where('key', 'rateStorePrizeCommission')->countAllResults() === 0) {
                $db->table('system')->insert([
                    'key' => 'rateStorePrizeCommission',
                    'value' => '0',
                ]);
            }

            if ($db->table('system')->where('key', 'rateOperatorCommission')->countAllResults() === 0) {
                $db->table('system')->insert([
                    'key' => 'rateOperatorCommission',
                    'value' => '0',
                ]);
            }

            if ($db->table('system')->where('key', 'lowBalanceThreshold')->countAllResults() === 0) {
                $db->table('system')->insert([
                    'key' => 'lowBalanceThreshold',
                    'value' => '',
                ]);
            }

            if ($db->table('system')->where('key', 'lowBalanceAutoRoulette')->countAllResults() === 0) {
                $db->table('system')->insert([
                    'key' => 'lowBalanceAutoRoulette',
                    'value' => '0',
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo actualizar el esquema de system: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_store_display_name')) {
    function bingo_store_display_name(array $user): string
    {
        $business = trim((string) ($user['business_name'] ?? ''));
        if ($business !== '') {
            return $business;
        }

        return trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
    }
}

if (!function_exists('bingo_generate_store_username')) {
    function bingo_generate_store_username(string $email, UsersModel $model, ?int $excludeId = null): string
    {
        $local = strstr($email, '@', true) ?: 'tienda';
        $base = strtolower(preg_replace('/[^a-z0-9]/', '', $local));
        $base = substr($base, 0, 20) ?: 'tienda';
        $candidate = $base;
        $suffix = 1;

        while (true) {
            $builder = $model->where('username', $candidate);
            if ($excludeId) {
                $builder = $builder->where('id !=', $excludeId);
            }
            if (!$builder->first()) {
                return $candidate;
            }
            $candidate = $base . $suffix;
            $suffix++;
        }
    }
}

if (!function_exists('bingo_deposit_is_store_funding')) {
    function bingo_deposit_is_store_funding(array $deposit): bool
    {
        return ($deposit['method'] ?? '') === 'store funding request'
            || ($deposit['account'] ?? '') === 'store_funding';
    }
}

if (!function_exists('bingo_deposit_is_store_player_recharge')) {
    function bingo_deposit_is_store_player_recharge(array $deposit): bool
    {
        return !empty($deposit['store']) && !bingo_deposit_is_store_funding($deposit);
    }
}

if (!function_exists('bingo_normalize_commission_rate')) {
    /**
     * Acepta tasas en decimal (0.05) o porcentaje entero mal guardado (5).
     */
    function bingo_normalize_commission_rate(float $rate): float
    {
        if ($rate <= 0) {
            return 0.0;
        }

        if ($rate > 1) {
            return round($rate / 100, 4);
        }

        return round($rate, 4);
    }
}

if (!function_exists('bingo_store_commission_rate')) {
    function bingo_store_commission_rate(array $store): float
    {
        bingo_ensure_users_schema();

        $custom = $store['store_commission_rate'] ?? null;
        if ($custom !== null && $custom !== '') {
            return bingo_normalize_commission_rate((float) $custom);
        }

        return bingo_normalize_commission_rate((float) (systemGet('rateStoreCommission') ?? 0));
    }
}

if (!function_exists('bingo_operator_commission_rate')) {
    function bingo_operator_commission_rate(?array $operator = null): float
    {
        if ($operator !== null) {
            $custom = $operator['operator_commission_rate'] ?? null;
            if ($custom !== null && $custom !== '') {
                return max(0, (float) $custom);
            }
        }

        return max(0, (float) (systemGet('rateOperatorCommission') ?? 0));
    }
}

if (!function_exists('bingo_store_prize_commission_rate')) {
    function bingo_store_prize_commission_rate(array $store): float
    {
        bingo_ensure_users_schema();

        $custom = $store['store_prize_commission_rate'] ?? null;
        if ($custom !== null && $custom !== '') {
            return bingo_normalize_commission_rate((float) $custom);
        }

        return bingo_normalize_commission_rate((float) (systemGet('rateStorePrizeCommission') ?? 0));
    }
}

if (!function_exists('bingo_calculate_store_prize_commission')) {
    function bingo_calculate_store_prize_commission(float $amount, array $store): float
    {
        if ($amount <= 0) {
            return 0.0;
        }

        $rate = bingo_store_prize_commission_rate($store);
        if ($rate <= 0) {
            return 0.0;
        }

        return round($amount * $rate, 2);
    }
}

if (!function_exists('bingo_calculate_store_commission')) {
    function bingo_calculate_store_commission(float $amount, array $store): float
    {
        if ($amount <= 0) {
            return 0.0;
        }

        $rate = bingo_store_commission_rate($store);
        if ($rate <= 0) {
            return 0.0;
        }

        return round($amount * $rate, 2);
    }
}

if (!function_exists('bingo_credit_store_operation_commission')) {
    /**
     * Acredita comisión del PV por recargas o pagos de premios a jugadores.
     */
    function bingo_credit_store_operation_commission(int $storeId, float $amount, string $paymentType, int $typeId, ?array $store = null, ?int $fromUserId = null): float
    {
        if ($storeId <= 0 || $amount <= 0 || $typeId <= 0) {
            return 0.0;
        }

        $modelPayments = new \App\Models\PaymentsModel();
        $existing = $modelPayments
            ->where('user', $storeId)
            ->where('type', $paymentType)
            ->where('type_id', $typeId)
            ->first();

        if ($existing) {
            return (float) ($existing['amount'] ?? 0);
        }

        if ($store === null) {
            $modelUsers = new \App\Models\UsersModel();
            $store = $modelUsers->find($storeId);
        }

        if (!$store || (int) ($store['group'] ?? -1) !== bingo_group_store()) {
            return 0.0;
        }

        $commission = $paymentType === 'store_prize_commission'
            ? bingo_calculate_store_prize_commission($amount, $store)
            : bingo_calculate_store_commission($amount, $store);
        if ($commission <= 0) {
            return 0.0;
        }

        wallet_credit_commission_earnings($storeId, $commission);

        $modelPayments->insert([
            'user' => $storeId,
            'type' => $paymentType,
            'type_id' => $typeId,
            'amount' => $commission,
            'status' => 2,
        ]);
        $paymentId = (int) $modelPayments->getInsertID();

        $fromUserId = $fromUserId ?? (int) (session()->get('id') ?? 0);
        if ($paymentId > 0) {
            $modelNotifications = new \App\Models\NotificationsModel();
            $modelNotifications->insert([
                'user' => $storeId,
                'from' => $fromUserId > 0 ? $fromUserId : $storeId,
                'type' => 'payment',
                'type_id' => $paymentId,
                'title' => '💰 ' . translate('store commission credited'),
                'message' => translate('store commission credited') . ': '
                    . systemGet('currency') . ' ' . number_format($commission, 2),
            ]);
        }

        return $commission;
    }
}

if (!function_exists('bingo_parse_store_commission_rate_post')) {
    function bingo_parse_store_commission_rate_post($input): ?float
    {
        if ($input === null || $input === '') {
            return null;
        }

        return round(max(0, (float) $input) / 100, 4);
    }
}

if (!function_exists('bingo_ensure_store_affiliate_code')) {
    function bingo_ensure_store_affiliate_code(array $store): string
    {
        bingo_ensure_users_schema();

        $storeId = (int) ($store['id'] ?? 0);
        if ($storeId <= 0 || (int) ($store['group'] ?? -1) !== bingo_group_store()) {
            return '';
        }

        $code = trim((string) ($store['referred_code'] ?? ''));
        if ($code !== '') {
            return $code;
        }

        $modelUsers = new \App\Models\UsersModel();

        do {
            $code = 'T' . strtoupper(substr(md5(uniqid('store_' . $storeId, true)), 0, 7));
            $exists = $modelUsers->where('referred_code', $code)->first();
        } while ($exists);

        $modelUsers->update($storeId, ['referred_code' => $code]);

        return $code;
    }
}

if (!function_exists('bingo_find_store_by_affiliate_code')) {
    function bingo_find_store_by_affiliate_code(string $code): ?array
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $modelUsers = new \App\Models\UsersModel();
        $store = $modelUsers
            ->where('group', bingo_group_store())
            ->where('deleted', 0)
            ->where('status', 1)
            ->groupStart()
            ->where('referred_code', $code)
            ->orWhere('code', $code)
            ->groupEnd()
            ->first();

        return $store ?: null;
    }
}

if (!function_exists('bingo_store_affiliate_link')) {
    function bingo_store_affiliate_link(array $store): string
    {
        $code = bingo_ensure_store_affiliate_code($store);

        if ($code === '') {
            return site_url('signup');
        }

        return site_url('signup/tienda/' . $code);
    }
}

if (!function_exists('bingo_ensure_operator_affiliate_code')) {
    function bingo_ensure_operator_affiliate_code(array $operator): string
    {
        bingo_ensure_users_schema();

        $operatorId = (int) ($operator['id'] ?? 0);
        if ($operatorId <= 0 || (int) ($operator['group'] ?? -1) !== bingo_group_operator()) {
            return '';
        }

        $code = trim((string) ($operator['referred_code'] ?? ''));
        if ($code !== '' && str_starts_with(strtoupper($code), 'O')) {
            return strtoupper($code);
        }

        $modelUsers = new \App\Models\UsersModel();

        do {
            $code = 'O' . strtoupper(substr(md5(uniqid('operator_' . $operatorId, true)), 0, 7));
            $exists = $modelUsers->where('referred_code', $code)->first();
        } while ($exists);

        $modelUsers->update($operatorId, ['referred_code' => $code]);

        return $code;
    }
}

if (!function_exists('bingo_find_operator_by_affiliate_code')) {
    function bingo_find_operator_by_affiliate_code(string $code): ?array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }

        $modelUsers = new \App\Models\UsersModel();
        $operator = $modelUsers
            ->where('group', bingo_group_operator())
            ->where('deleted', 0)
            ->where('status', 1)
            ->groupStart()
            ->where('referred_code', $code)
            ->orWhere('code', $code)
            ->groupEnd()
            ->first();

        return $operator ?: null;
    }
}

if (!function_exists('bingo_operator_affiliate_link')) {
    /** Enlace para registrar un Punto de venta bajo este OPERADOR. */
    function bingo_operator_affiliate_link(array $operator): string
    {
        return bingo_operator_store_signup_link($operator);
    }
}

if (!function_exists('bingo_operator_store_signup_link')) {
    /** Enlace para registrar un nuevo Punto de venta bajo este OPERADOR. */
    function bingo_operator_store_signup_link(array $operator): string
    {
        $code = bingo_ensure_operator_affiliate_code($operator);

        if ($code === '') {
            return site_url('signup');
        }

        return site_url('signup/punto-venta/' . $code);
    }
}

if (!function_exists('bingo_set_store_signup_session')) {
    function bingo_set_store_signup_session(?array $operator, ?array $referrerStore = null): void
    {
        session()->remove('referred_code');
        session()->remove('referred_store_id');
        bingo_clear_operator_signup_session();

        $validOperator = $operator
            && (int) ($operator['group'] ?? -1) === bingo_group_operator();
        $validReferrerStore = $referrerStore
            && (int) ($referrerStore['group'] ?? -1) === bingo_group_store();

        if (!$validOperator && !$validReferrerStore) {
            session()->remove('signup_as_store');
            session()->remove('store_signup_operator_id');
            session()->remove('store_signup_referrer_id');

            return;
        }

        session()->set('signup_as_store', 1);

        if ($validOperator) {
            session()->set('store_signup_operator_id', (int) $operator['id']);
        } else {
            session()->remove('store_signup_operator_id');
        }

        if ($validReferrerStore) {
            session()->set('store_signup_referrer_id', (int) $referrerStore['id']);
        } else {
            session()->remove('store_signup_referrer_id');
        }
    }
}

if (!function_exists('bingo_clear_store_signup_session')) {
    function bingo_clear_store_signup_session(): void
    {
        session()->remove('signup_as_store');
        session()->remove('store_signup_operator_id');
        session()->remove('store_signup_referrer_id');
    }
}

if (!function_exists('bingo_bootstrap_store_player_affiliate_signup')) {
    /** Prepara sesión para registrar un jugador afiliado a un Punto de venta. */
    function bingo_bootstrap_store_player_affiliate_signup(array $store): bool
    {
        if ((int) ($store['group'] ?? -1) !== bingo_group_store()) {
            return false;
        }

        bingo_clear_store_signup_session();
        bingo_clear_operator_signup_session();

        $code = bingo_ensure_store_affiliate_code($store);
        bingo_set_signup_referrer_session($store, $code);

        return (int) (session()->get('referred_store_id') ?? 0) > 0;
    }
}

if (!function_exists('bingo_apply_store_signup_operator')) {
    function bingo_apply_store_signup_operator(int $newStoreId): void
    {
        bingo_apply_store_signup_affiliation($newStoreId);
    }
}

if (!function_exists('bingo_resolve_player_affiliate_signup_commission')) {
    function bingo_resolve_player_affiliate_signup_commission(array $store): float
    {
        return 0.0;
    }
}

if (!function_exists('bingo_pay_store_player_affiliate_commission_on_signup')) {
    /**
     * Comisión por afiliar jugador deshabilitada.
     *
     * @return array{paid:bool,amount:float,store_id?:int}
     */
    function bingo_pay_store_player_affiliate_commission_on_signup(int $playerId, int $storeId, ?int $fromUserId = null): array
    {
        return ['paid' => false, 'amount' => 0.0];
    }
}

if (!function_exists('bingo_player_eligible_for_signup_affiliate_commission')) {
    function bingo_player_eligible_for_signup_affiliate_commission(int $playerId, int $storeId): bool
    {
        if ($playerId <= 0 || $storeId <= 0) {
            return false;
        }

        bingo_ensure_users_schema();

        $modelUsers = new \App\Models\UsersModel();
        $player = $modelUsers->find($playerId);

        if (!$player) {
            return false;
        }

        if ((int) ($player['affiliate_signup_store_id'] ?? 0) === $storeId) {
            return true;
        }

        if ((int) ($player['affiliate_signup_store_id'] ?? 0) > 0) {
            return false;
        }

        return (int) ($player['referred_store_id'] ?? 0) === $storeId;
    }
}

if (!function_exists('bingo_sync_store_player_affiliate_commissions')) {
    /** Comisiones por afiliación de jugadores deshabilitadas. */
    function bingo_sync_store_player_affiliate_commissions(int $storeId): int
    {
        return 0;
    }
}

if (!function_exists('bingo_resolve_store_referral_signup_commission')) {
    function bingo_resolve_store_referral_signup_commission(array $referrerStore): float
    {
        return 0.0;
    }
}

if (!function_exists('bingo_pay_store_referred_store_commission')) {
    /**
     * Comisión por afiliar PV deshabilitada.
     *
     * @return array{paid:bool,amount:float}
     */
    function bingo_pay_store_referred_store_commission(int $newStoreId, int $referrerStoreId): array
    {
        return ['paid' => false, 'amount' => 0.0];
    }
}

if (!function_exists('bingo_apply_store_signup_affiliation')) {
    function bingo_apply_store_signup_affiliation(int $newStoreId): void
    {
        bingo_ensure_users_schema();

        $operatorId = (int) (session()->get('store_signup_operator_id') ?? 0);
        bingo_clear_store_signup_session();

        if ($newStoreId <= 0) {
            return;
        }

        $modelUsers = new \App\Models\UsersModel();

        if ($operatorId > 0) {
            $operator = $modelUsers
                ->where('id', $operatorId)
                ->where('group', bingo_group_operator())
                ->where('deleted', 0)
                ->where('status', 1)
                ->first();

            if ($operator) {
                $modelUsers->update($newStoreId, ['operator_id' => $operatorId]);
                bingo_sync_operator_stores($operatorId, array_merge(
                    bingo_fetch_operator_store_ids($operatorId),
                    [$newStoreId]
                ));
            }
        }

    }
}

if (!function_exists('bingo_resolve_operator_store_signup_commission')) {
    function bingo_resolve_operator_store_signup_commission(array $operator): float
    {
        return 0.0;
    }
}

if (!function_exists('bingo_pay_operator_referred_store_commission')) {
    /**
     * Comisión al operador por afiliar PV deshabilitada.
     *
     * @return array{paid:bool,amount:float}
     */
    function bingo_pay_operator_referred_store_commission(int $newStoreId, int $operatorId, ?int $fromUserId = null): array
    {
        return ['paid' => false, 'amount' => 0.0];
    }
}

if (!function_exists('bingo_sync_operator_store_affiliate_commissions')) {
    /** Comisiones por afiliación de PV al operador deshabilitadas. */
    function bingo_sync_operator_store_affiliate_commissions(int $operatorId): int
    {
        return 0;
    }
}

if (!function_exists('bingo_sum_operator_store_affiliate_commissions')) {
    function bingo_sum_operator_store_affiliate_commissions(int $operatorId, ?int $storeId = null): float
    {
        if ($operatorId <= 0) {
            return 0.0;
        }

        $modelPayments = new \App\Models\PaymentsModel();
        $builder = $modelPayments
            ->select('amount')
            ->where('user', $operatorId)
            ->where('type', 'operator_store_affiliate_commission')
            ->where('status', 2);

        if ($storeId !== null && $storeId > 0) {
            $builder->where('type_id', $storeId);
        }

        $total = 0.0;
        foreach ($builder->findAll() as $row) {
            $total += (float) ($row['amount'] ?? 0);
        }

        return round($total, 2);
    }
}

if (!function_exists('bingo_fetch_store_referred_players')) {
    function bingo_fetch_store_referred_players(int $storeId, int $limit = 50): array
    {
        if ($storeId <= 0) {
            return [];
        }

        $modelUsers = new \App\Models\UsersModel();
        $modelPayments = new \App\Models\PaymentsModel();

        $players = $modelUsers
            ->where('referred_store_id', $storeId)
            ->where('group', bingo_group_player())
            ->where('deleted', 0)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);

        $cpaRows = $modelPayments
            ->select('type, type_id, amount, status, created_at')
            ->where('user', $storeId)
            ->whereIn('type', ['store_player_affiliate_commission', 'affiliate_cpa', 'store_affiliate_commission'])
            ->findAll();

        $commissionByPlayer = [];
        foreach ($cpaRows as $row) {
            $playerKey = (int) $row['type_id'];
            if (!isset($commissionByPlayer[$playerKey])) {
                $commissionByPlayer[$playerKey] = $row;
            }
        }

        foreach ($players as &$player) {
            $playerId = (int) ($player['id'] ?? 0);
            $commissionRow = $commissionByPlayer[$playerId] ?? null;
            $player['affiliate_commission'] = $commissionRow
                ? number_format((float) $commissionRow['amount'], 2)
                : null;
            $player['affiliate_commission_paid'] = $commissionRow !== null;
        }

        return $players;
    }
}

if (!function_exists('bingo_fetch_store_referred_stores')) {
    function bingo_fetch_store_referred_stores(int $storeId, int $limit = 50): array
    {
        if ($storeId <= 0) {
            return [];
        }

        $modelUsers = new \App\Models\UsersModel();
        $modelPayments = new \App\Models\PaymentsModel();

        $stores = $modelUsers
            ->where('referred_store_id', $storeId)
            ->where('group', bingo_group_store())
            ->where('deleted', 0)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);

        $commissionRows = $modelPayments
            ->select('type_id, amount, status, created_at')
            ->where('user', $storeId)
            ->where('type', 'store_referral_commission')
            ->findAll();

        $commissionByStore = [];
        foreach ($commissionRows as $row) {
            $commissionByStore[(int) $row['type_id']] = $row;
        }

        foreach ($stores as &$store) {
            $storeIdRef = (int) $store['id'];
            $commissionRow = $commissionByStore[$storeIdRef] ?? null;
            $store['affiliate_commission'] = $commissionRow
                ? number_format((float) $commissionRow['amount'], 2)
                : null;
            $store['affiliate_commission_status'] = $commissionRow
                ? translate('paid')
                : translate('pending');
        }

        return $stores;
    }
}

if (!function_exists('bingo_sum_store_affiliate_commissions')) {
    function bingo_sum_store_affiliate_commissions(int $storeId): float
    {
        if ($storeId <= 0) {
            return 0.0;
        }

        $modelPayments = new \App\Models\PaymentsModel();
        $rows = $modelPayments
            ->select('amount')
            ->where('user', $storeId)
            ->whereIn('type', ['store_referral_commission', 'store_affiliate_commission', 'store_player_affiliate_commission', 'affiliate_cpa'])
            ->where('status', 2)
            ->findAll();

        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) ($row['amount'] ?? 0);
        }

        return round($total, 2);
    }
}

if (!function_exists('bingo_sum_store_recharge_commissions')) {
    function bingo_sum_store_recharge_commissions(int $storeId): float
    {
        if ($storeId <= 0) {
            return 0.0;
        }

        $modelPayments = new \App\Models\PaymentsModel();
        $rows = $modelPayments
            ->select('amount')
            ->where('user', $storeId)
            ->where('type', 'store_commission')
            ->where('status', 2)
            ->findAll();

        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) ($row['amount'] ?? 0);
        }

        return round($total, 2);
    }
}

if (!function_exists('bingo_sum_store_prize_commissions')) {
    function bingo_sum_store_prize_commissions(int $storeId): float
    {
        if ($storeId <= 0) {
            return 0.0;
        }

        $modelPayments = new \App\Models\PaymentsModel();
        $rows = $modelPayments
            ->select('amount')
            ->where('user', $storeId)
            ->where('type', 'store_prize_commission')
            ->where('status', 2)
            ->findAll();

        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) ($row['amount'] ?? 0);
        }

        return round($total, 2);
    }
}

if (!function_exists('bingo_sum_store_payment_commissions')) {
    function bingo_sum_store_payment_commissions(int $storeId): float
    {
        return round(
            bingo_sum_store_recharge_commissions($storeId) + bingo_sum_store_prize_commissions($storeId),
            2
        );
    }
}

if (!function_exists('bingo_fetch_operator_store_ids')) {
    function bingo_fetch_operator_store_ids(int $operatorId): array
    {
        if ($operatorId <= 0) {
            return [];
        }

        $modelUsers = new \App\Models\UsersModel();

        return array_map('intval', $modelUsers
            ->select('id')
            ->where('group', bingo_group_store())
            ->where('operator_id', $operatorId)
            ->where('deleted', 0)
            ->findColumn('id') ?: []);
    }
}

if (!function_exists('bingo_set_operator_signup_session')) {
    function bingo_set_operator_signup_session(?array $operator): void
    {
        session()->remove('referred_code');
        session()->remove('referred_store_id');
        bingo_clear_store_signup_session();

        if (!$operator || (int) ($operator['group'] ?? -1) !== bingo_group_operator()) {
            session()->remove('signup_as_operator');
            session()->remove('referred_operator_id');

            return;
        }

        session()->set('signup_as_operator', 1);
        session()->set('referred_operator_id', (int) $operator['id']);
    }
}

if (!function_exists('bingo_clear_operator_signup_session')) {
    function bingo_clear_operator_signup_session(): void
    {
        session()->remove('signup_as_operator');
        session()->remove('referred_operator_id');
    }
}

if (!function_exists('bingo_apply_operator_signup_referral')) {
    function bingo_apply_operator_signup_referral(int $newOperatorId): void
    {
        bingo_ensure_users_schema();

        $referrerId = (int) (session()->get('referred_operator_id') ?? 0);
        bingo_clear_operator_signup_session();

        if ($referrerId <= 0 || $newOperatorId <= 0) {
            return;
        }

        $modelUsers = new \App\Models\UsersModel();
        $referrer = $modelUsers
            ->where('id', $referrerId)
            ->where('group', bingo_group_operator())
            ->where('deleted', 0)
            ->where('status', 1)
            ->first();

        if (!$referrer) {
            return;
        }

        $modelUsers->update($newOperatorId, ['referred_operator_id' => $referrerId]);
    }
}

if (!function_exists('bingo_set_signup_referrer_session')) {
    function bingo_set_signup_referrer_session(?array $referrer, string $referredCode): void
    {
        if (!$referrer) {
            session()->remove('referred_code');
            session()->remove('referred_store_id');
            bingo_clear_operator_signup_session();

            return;
        }

        bingo_clear_operator_signup_session();

        if ((int) ($referrer['group'] ?? -1) === bingo_group_store()) {
            session()->set('referred_store_id', (int) $referrer['id']);
            session()->remove('referred_code');

            return;
        }

        session()->set('referred_code', $referredCode);
        session()->remove('referred_store_id');
    }
}

if (!function_exists('bingo_apply_signup_referral')) {
    function bingo_apply_signup_referral(int $newUserId): void
    {
        bingo_ensure_users_schema();

        $modelUsers = new \App\Models\UsersModel();
        $modelReferrals = new \App\Models\ReferralsModel();

        $referredStoreId = (int) (session()->get('referred_store_id') ?? 0);
        if ($referredStoreId > 0) {
            $store = $modelUsers->find($referredStoreId);
            if (
                $store
                && (int) ($store['group'] ?? -1) === bingo_group_store()
                && (int) ($store['deleted'] ?? 0) === 0
                && (int) ($store['status'] ?? 0) === 1
            ) {
                $modelUsers->update($newUserId, [
                    'referred_store_id' => $referredStoreId,
                    'affiliate_signup_store_id' => $referredStoreId,
                ]);
            }

            session()->remove('referred_store_id');

            return;
        }

        $referredCode = session()->get('referred_code');
        if ($referredCode === null) {
            return;
        }

        $referrer = $modelUsers->where('referred_code', $referredCode)->first();
        if ($referrer && (int) ($referrer['group'] ?? -1) === bingo_group_player()) {
            $modelReferrals->insert([
                'id_referred' => $referrer['id'],
                'id_referrer' => $newUserId,
                'status' => 1,
            ]);
        }

        session()->remove('referred_code');
    }
}

if (!function_exists('bingo_link_player_to_store_for_affiliation')) {
    /**
     * Vincula un jugador al PV cuando es recargado por primera vez sin referente.
     *
     * @return array{linked:bool,store_id:int,newly_linked:bool}
     */
    function bingo_link_player_to_store_for_affiliation(int $playerId, int $storeId): array
    {
        bingo_ensure_users_schema();

        if ($playerId <= 0 || $storeId <= 0) {
            return ['linked' => false, 'store_id' => 0, 'newly_linked' => false];
        }

        $modelUsers = new \App\Models\UsersModel();
        $player = $modelUsers->find($playerId);
        if (!$player || (int) ($player['group'] ?? -1) !== bingo_group_player()) {
            return ['linked' => false, 'store_id' => 0, 'newly_linked' => false];
        }

        $store = $modelUsers->find($storeId);
        if (
            !$store
            || (int) ($store['group'] ?? -1) !== bingo_group_store()
            || (int) ($store['deleted'] ?? 0) !== 0
            || (int) ($store['status'] ?? 0) !== 1
        ) {
            return ['linked' => false, 'store_id' => 0, 'newly_linked' => false];
        }

        $currentStoreId = (int) ($player['referred_store_id'] ?? 0);
        if ($currentStoreId === $storeId) {
            return ['linked' => true, 'store_id' => $storeId, 'newly_linked' => false];
        }

        if ($currentStoreId > 0) {
            return ['linked' => true, 'store_id' => $currentStoreId, 'newly_linked' => false];
        }

        $modelUsers->update($playerId, [
            'referred_store_id' => $storeId,
            'affiliate_signup_store_id' => $storeId,
        ]);

        return ['linked' => true, 'store_id' => $storeId, 'newly_linked' => true];
    }
}

if (!function_exists('bingo_pay_store_affiliate_commission')) {
    /**
     * @deprecated La comisión % del PV solo aplica en recargas y pagos de premios desde el panel del PV.
     * @return array{paid:bool,amount:float,store_id?:int}
     */
    function bingo_pay_store_affiliate_commission(int $playerId, float $depositAmount, int $depositId, ?int $fromUserId = null): array
    {
        return ['paid' => false, 'amount' => 0.0];
    }
}

if (!function_exists('bingo_build_carton_numbers_data')) {
    function bingo_build_carton_numbers_data(int $cartonId): array
    {
        $numbersData = [];
        $bColumn = range(1, 15);
        $iColumn = range(16, 30);
        $nColumn = range(31, 45);
        $gColumn = range(46, 60);
        $oColumn = range(61, 75);

        shuffle($bColumn);
        shuffle($iColumn);
        shuffle($nColumn);
        shuffle($gColumn);
        shuffle($oColumn);

        for ($pos = 0; $pos < 5; $pos++) {
            $numbersData[] = [
                'carton' => $cartonId,
                'number' => $bColumn[$pos],
                'position' => 1 + ($pos * 5),
                'status' => 0,
            ];
        }

        for ($pos = 0; $pos < 5; $pos++) {
            $numbersData[] = [
                'carton' => $cartonId,
                'number' => $iColumn[$pos],
                'position' => 2 + ($pos * 5),
                'status' => 0,
            ];
        }

        for ($pos = 0; $pos < 5; $pos++) {
            $numbersData[] = [
                'carton' => $cartonId,
                'number' => $nColumn[$pos],
                'position' => 3 + ($pos * 5),
                'status' => 0,
            ];
        }

        for ($pos = 0; $pos < 5; $pos++) {
            $numbersData[] = [
                'carton' => $cartonId,
                'number' => $gColumn[$pos],
                'position' => 4 + ($pos * 5),
                'status' => 0,
            ];
        }

        for ($pos = 0; $pos < 5; $pos++) {
            $numbersData[] = [
                'carton' => $cartonId,
                'number' => $oColumn[$pos],
                'position' => 5 + ($pos * 5),
                'status' => 0,
            ];
        }

        return $numbersData;
    }
}

if (!function_exists('bingo_generate_cartons_for_user')) {
    function bingo_generate_cartons_for_user(int $userId, int $gameId, int $count): array
    {
        if ($count < 1) {
            return ['success' => false, 'message' => 'Cantidad inválida', 'carton_ids' => []];
        }

        $modelCartons = new \App\Models\CartonsModel();
        $modelNumbersCartons = new \App\Models\NumbersCartonsModel();
        $modelGames = new \App\Models\GamesModel();

        $game = $modelGames->find($gameId);
        if (!$game || (int) $game['status'] !== 1) {
            return ['success' => false, 'message' => translate('game not found'), 'carton_ids' => []];
        }

        $maxCartons = (int) systemGet('maxCartons');
        $existing = $modelCartons->where('user', $userId)->where('game', $gameId)->countAllResults();
        if ($existing + $count > $maxCartons) {
            return [
                'success' => false,
                'message' => str_replace('{cartons}', (string) $maxCartons, translate('only {cartons} cards can be played per game.')),
                'carton_ids' => [],
            ];
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $cartonIds = [];
            $numbersData = [];

            for ($i = 0; $i < $count; $i++) {
                $modelCartons->insert([
                    'user' => $userId,
                    'game' => $gameId,
                    'status' => 1,
                ]);

                $cartonId = (int) $modelCartons->getInsertID();
                $cartonIds[] = $cartonId;

                $prefix = str_pad((string) rand(0, 999), 3, '0', STR_PAD_LEFT);
                $serial = str_pad((string) $cartonId, 6, '0', STR_PAD_LEFT) . $prefix;
                $modelCartons->update($cartonId, ['serial' => $serial]);

                $numbersData = array_merge($numbersData, bingo_build_carton_numbers_data($cartonId));
            }

            if ($numbersData !== []) {
                $modelNumbersCartons->insertBatch($numbersData);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \RuntimeException('No se pudieron generar los cartones');
            }

            return [
                'success' => true,
                'message' => translate('cartons assigned successfully'),
                'carton_ids' => $cartonIds,
                'game' => $game,
            ];
        } catch (\Throwable $e) {
            $db->transRollback();

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'carton_ids' => [],
            ];
        }
    }
}

if (!function_exists('bingo_game_roulette_payload')) {
    function bingo_game_roulette_payload(array $game, ?array $room = null): array
    {
        $modelModalities = new \App\Models\ModalitiesModel();
        $modelAwards = new \App\Models\AwardsModel();

        $modalityIds = array_values(array_filter(array_map('intval', explode(',', (string) ($game['modalities'] ?? '')))));
        $modalityNames = [];

        if ($modalityIds !== []) {
            foreach ($modelModalities->whereIn('id', $modalityIds)->findAll() as $modality) {
                $modalityNames[] = translate($modality['name']);
            }
        }

        $awards = $modelAwards->where('game', $game['id'])->where('status', 1)->findAll();
        $awardTotal = array_sum(array_map(static fn($row) => (float) ($row['amount'] ?? 0), $awards));
        $currency = systemGet('currency');
        $price = (float) ($game['price'] ?? 0);
        $roomName = trim((string) ($room['name'] ?? ''));
        $when = trim(date('d/m/Y H:i', strtotime(($game['date'] ?? '') . ' ' . ($game['time'] ?? '00:00:00'))));

        return [
            'id' => (int) $game['id'],
            'description' => (string) ($game['description'] ?? ''),
            'date' => (string) ($game['date'] ?? ''),
            'time' => (string) ($game['time'] ?? ''),
            'price' => $price,
            'room' => $roomName,
            'modalities' => $modalityNames,
            'modalities_text' => implode(', ', $modalityNames),
            'award_total' => $awardTotal,
            'allows_roulette' => bingo_game_allows_roulette_cartons($game),
            'label' => trim($roomName . ' - ' . ($game['description'] ?? '') . ' (' . $when . ') - ' . $currency . ' ' . number_format($price, 2)),
            'detail' => ($modalityNames !== [] ? 'Modalidades: ' . implode(', ', $modalityNames) . '. ' : '')
                . 'Precio por cartón: ' . $currency . ' ' . number_format($price, 2)
                . ($awardTotal > 0 ? '. Premios configurados: ' . $currency . ' ' . number_format($awardTotal, 2) : ''),
        ];
    }
}

if (!function_exists('bingo_low_balance_threshold')) {
    /**
     * Saldo máximo (inclusive) para considerar a un jugador con poco saldo.
     * Usa lowBalanceThreshold del sistema o el precio de la partida activa más barata.
     */
    function bingo_low_balance_threshold(): float
    {
        $custom = systemGet('lowBalanceThreshold');
        if ($custom !== null && $custom !== '') {
            return max(0, (float) $custom);
        }

        $modelGames = new \App\Models\GamesModel();
        $game = $modelGames->where('status', 1)->orderBy('price', 'ASC')->first();

        if ($game && isset($game['price'])) {
            return (float) $game['price'];
        }

        return 1.0;
    }
}

if (!function_exists('bingo_fetch_low_balance_players')) {
    /**
     * @return array{players: list<array>, threshold: float}
     */
    function bingo_fetch_low_balance_players(): array
    {
        helper('wallet');

        $modelUsers = new \App\Models\UsersModel();
        $players = $modelUsers
            ->where('group', bingo_group_player())
            ->where('deleted', 0)
            ->where('status', 1)
            ->findAll();

        $threshold = bingo_low_balance_threshold();
        $list = [];

        foreach ($players as $player) {
            $player = wallet_service()->normalizeUser($player);
            $total = wallet_total($player);

            if ($total > $threshold) {
                continue;
            }

            $player['wallet_total'] = $total;
            $player['wallet_recharge_display'] = wallet_recharge_balance($player);
            $player['wallet_withdraw_display'] = wallet_withdrawable($player);
            $list[] = $player;
        }

        usort($list, static function (array $a, array $b): int {
            return $a['wallet_total'] <=> $b['wallet_total'];
        });

        $list = bingo_attach_latest_low_balance_grants($list);

        return [
            'players' => $list,
            'threshold' => $threshold,
        ];
    }
}

if (!function_exists('bingo_low_balance_roulette_pending_count')) {
    /** Jugadores con poco saldo que aún no tienen ruleta disponible (roulette = 1). */
    function bingo_low_balance_roulette_pending_count(): int
    {
        $payload = bingo_fetch_low_balance_players();
        $count = 0;

        foreach ($payload['players'] as $player) {
            if ((int) ($player['roulette'] ?? 1) === 1) {
                $count++;
            }
        }

        return $count;
    }
}

if (!function_exists('bingo_low_balance_auto_enabled')) {
    function bingo_low_balance_auto_enabled(): bool
    {
        bingo_ensure_system_settings_schema();

        return (int) systemGet('lowBalanceAutoRoulette') === 1
            && (int) systemGet('activateRoulette') === 1;
    }
}

if (!function_exists('bingo_grant_player_roulette')) {
    function bingo_grant_player_roulette(int $userId, string $notificationMessage, bool $force = false): bool
    {
        $modelUsers = new \App\Models\UsersModel();
        $player = $modelUsers
            ->where('id', $userId)
            ->where('group', bingo_group_player())
            ->where('deleted', 0)
            ->first();

        if (!$player) {
            return false;
        }

        if (!$force && (int) ($player['roulette'] ?? 1) === 0) {
            return false;
        }

        $modelUsers->update($userId, ['roulette' => 0]);

        $modelNotifications = new \App\Models\NotificationsModel();
        $modelNotifications->insert([
            'user' => $userId,
            'type' => 'roulette',
            'title' => '🎁 ' . translate('roulette'),
            'message' => $notificationMessage,
            'status' => 0,
        ]);

        return true;
    }
}

if (!function_exists('bingo_check_low_balance_auto_roulette')) {
    /**
     * Si el saldo cae al umbral configurado, notifica al jugador y otorga la ruleta.
     * Vuelve a otorgar cuando ya usó la ruleta y sigue con poco saldo (hasta que recargue).
     */
    function bingo_check_low_balance_auto_roulette(int $userId): void
    {
        if ($userId <= 0 || !bingo_low_balance_auto_enabled()) {
            return;
        }

        bingo_ensure_users_schema();

        $modelUsers = new \App\Models\UsersModel();
        $user = $modelUsers->find($userId);

        if (
            !$user
            || (int) ($user['group'] ?? -1) !== bingo_group_player()
            || (int) ($user['deleted'] ?? 0) !== 0
            || (int) ($user['status'] ?? 0) !== 1
        ) {
            return;
        }

        helper('wallet');

        $user = wallet_service()->normalizeUser($user);
        $total = wallet_total($user);
        $threshold = bingo_low_balance_threshold();
        $currency = (string) (systemGet('currency') ?: '');

        if ($total > $threshold) {
            if ((int) ($user['low_balance_alert'] ?? 0) === 1) {
                $modelUsers->update($userId, ['low_balance_alert' => 0]);
            }

            return;
        }

        $rouletteUsed = (int) ($user['roulette'] ?? 1) === 1;
        $alreadyAlerted = (int) ($user['low_balance_alert'] ?? 0) === 1;

        // Ya alertado y aún tiene ruleta pendiente de girar.
        if ($alreadyAlerted && !$rouletteUsed) {
            return;
        }

        $modelNotifications = new \App\Models\NotificationsModel();
        $grantedRoulette = false;

        if ($rouletteUsed) {
            $modelUsers->update($userId, ['roulette' => 0]);
            $grantedRoulette = true;
        }

        if (!$alreadyAlerted || $grantedRoulette) {
            $message = translate('low balance limit reached notification');
            $message = str_replace(
                [':currency', ':amount', ':balance'],
                [$currency, number_format($threshold, 2), number_format($total, 2)],
                $message
            );

            if ($grantedRoulette) {
                $message .= ' ' . translate('low balance roulette auto granted');
            }

            $modelNotifications->insert([
                'user' => $userId,
                'type' => 'low_balance',
                'title' => '⚠️ ' . translate('low balance alert'),
                'message' => $message,
                'status' => 0,
            ]);
        }

        $modelUsers->update($userId, ['low_balance_alert' => 1]);

        if ($grantedRoulette) {
            bingo_log_low_balance_roulette_grant($userId, 'auto', null, $total, $threshold);
        }
    }
}

if (!function_exists('bingo_process_low_balance_auto_roulette_batch')) {
    /** Revisa todos los jugadores con poco saldo y aplica la ruleta automática. */
    function bingo_process_low_balance_auto_roulette_batch(): int
    {
        if (!bingo_low_balance_auto_enabled()) {
            return 0;
        }

        $payload = bingo_fetch_low_balance_players();
        $processed = 0;

        foreach ($payload['players'] as $player) {
            $playerId = (int) ($player['id'] ?? 0);
            if ($playerId <= 0) {
                continue;
            }

            bingo_check_low_balance_auto_roulette($playerId);
            $processed++;
        }

        return $processed;
    }
}

if (!function_exists('bingo_ensure_low_balance_grants_schema')) {
    function bingo_ensure_low_balance_grants_schema(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            $db = \Config\Database::connect();

            try {
                $db->query("ALTER TABLE `notifications` MODIFY COLUMN `type` ENUM('message','sing','system','low_balance','deposit','withdraw','purchase') NOT NULL DEFAULT 'system'");
            } catch (\Exception $e) {}

            if ($db->tableExists('low_balance_roulette_grants')) {
                return;
            }

            $forge = \Config\Database::forge();
            $forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'user' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'granted_by' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'source' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'manual',
                ],
                'balance' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                ],
                'threshold' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,2',
                    'default' => 0,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $forge->addKey('id', true);
            $forge->addKey('user');
            $forge->addKey('created_at');
            $forge->createTable('low_balance_roulette_grants', true);
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo crear low_balance_roulette_grants: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_log_low_balance_roulette_grant')) {
    function bingo_log_low_balance_roulette_grant(
        int $userId,
        string $source,
        ?int $grantedBy = null,
        ?float $balance = null,
        ?float $threshold = null
    ): void {
        bingo_ensure_low_balance_grants_schema();

        $db = \Config\Database::connect();
        if (!$db->tableExists('low_balance_roulette_grants')) {
            return;
        }

        if ($balance === null || $threshold === null) {
            helper('wallet');
            $modelUsers = new \App\Models\UsersModel();
            $user = $modelUsers->find($userId);
            if ($user) {
                $user = wallet_service()->normalizeUser($user);
                $balance = wallet_total($user);
            }
            $threshold = bingo_low_balance_threshold();
        }

        $db->table('low_balance_roulette_grants')->insert([
            'user' => $userId,
            'granted_by' => $grantedBy,
            'source' => in_array($source, ['manual', 'auto'], true) ? $source : 'manual',
            'balance' => round((float) $balance, 2),
            'threshold' => round((float) $threshold, 2),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

if (!function_exists('bingo_fetch_low_balance_roulette_history')) {
    /**
     * @return list<array>
     */
    function bingo_fetch_low_balance_roulette_history(int $limit = 30): array
    {
        bingo_ensure_low_balance_grants_schema();

        $db = \Config\Database::connect();
        if (!$db->tableExists('low_balance_roulette_grants')) {
            return [];
        }

        $modelUsers = new \App\Models\UsersModel();
        $rows = $db->table('low_balance_roulette_grants')
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        foreach ($rows as &$row) {
            $player = $modelUsers->find((int) $row['user']);
            $row['player_name'] = $player
                ? trim(($player['firstname'] ?? '') . ' ' . ($player['lastname'] ?? ''))
                : translate('unknown');
            if ($row['player_name'] === '' && $player) {
                $row['player_name'] = (string) ($player['username'] ?? translate('unknown'));
            }
            $row['player_code'] = $player['code'] ?? '';
            $row['player_email'] = $player['email'] ?? '';
            $row['roulette_status'] = (int) ($player['roulette'] ?? 1);
        }

        return $rows;
    }
}

if (!function_exists('bingo_attach_latest_low_balance_grants')) {
    function bingo_attach_latest_low_balance_grants(array $players): array
    {
        if ($players === []) {
            return $players;
        }

        bingo_ensure_low_balance_grants_schema();

        $db = \Config\Database::connect();
        if (!$db->tableExists('low_balance_roulette_grants')) {
            return $players;
        }

        $userIds = array_map(static fn(array $p): int => (int) $p['id'], $players);

        $grants = $db->table('low_balance_roulette_grants')
            ->whereIn('user', $userIds)
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $latestByUser = [];
        foreach ($grants as $grant) {
            $uid = (int) $grant['user'];
            if (!isset($latestByUser[$uid])) {
                $latestByUser[$uid] = $grant;
            }
        }

        foreach ($players as &$player) {
            $player['latest_grant'] = $latestByUser[(int) $player['id']] ?? null;
        }

        return $players;
    }
}

if (!function_exists('bingo_fetch_pending_roulette_prizes')) {
    /**
     * Premios de ruleta reclamados pero aún sin partida asignada.
     *
     * @return list<array>
     */
    function bingo_fetch_pending_roulette_prizes(?int $userId = null): array
    {
        bingo_ensure_roulettes_schema();

        $userId = $userId ?? (int) session()->get('id');
        if ($userId <= 0) {
            return [];
        }

        $model = new \App\Models\RoulettesModel();

        return $model
            ->where('user', $userId)
            ->where('status', 0)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}

if (!function_exists('bingo_count_pending_roulette_cartons')) {
    function bingo_count_pending_roulette_cartons(?int $userId = null): int
    {
        $total = 0;

        foreach (bingo_fetch_pending_roulette_prizes($userId) as $prize) {
            $total += (int) ($prize['cartons'] ?? 0);
        }

        return $total;
    }
}

if (!function_exists('bingo_ensure_roulettes_schema')) {
    function bingo_ensure_roulettes_schema(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('roulettes')) {
                return;
            }

            if (!$db->fieldExists('game', 'roulettes')) {
                $forge = \Config\Database::forge();
                $forge->addColumn('roulettes', [
                    'game' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                        'default' => null,
                        'after' => 'user',
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo actualizar el esquema de roulettes: ' . $e->getMessage());
        }
    }
}
