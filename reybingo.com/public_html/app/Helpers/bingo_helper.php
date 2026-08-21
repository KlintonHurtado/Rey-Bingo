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
            ->orderBy('id', 'ASC')
            ->findAll();

        $unique = [];
        $seen = [];
        foreach ($rows as $row) {
            $number = (int) ($row['number'] ?? 0);
            if ($number < 1 || isset($seen[$number])) {
                continue;
            }
            $seen[$number] = true;
            $unique[] = $number;
        }

        return $unique;
    }
}

if (!function_exists('bingo_number_already_drawn')) {
    function bingo_number_already_drawn(int $gameId, int $number): bool
    {
        if ($gameId < 1 || $number < 1) {
            return true;
        }

        $model = new BoardsModel();

        return $model->where('game', $gameId)->where('number', $number)->countAllResults() > 0;
    }
}

if (!function_exists('bingo_dedupe_drawn_number')) {
    /** Deja solo la primera fila de un número en boards. */
    function bingo_dedupe_drawn_number(int $gameId, int $number): void
    {
        $db = \Config\Database::connect();
        $rows = $db->table('boards')
            ->where('game', $gameId)
            ->where('number', $number)
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        if (count($rows) <= 1) {
            return;
        }

        $deleteIds = [];
        foreach ($rows as $i => $row) {
            if ($i === 0) {
                continue;
            }
            $deleteIds[] = (int) $row['id'];
        }

        if ($deleteIds !== []) {
            $db->table('boards')->whereIn('id', $deleteIds)->delete();
            log_message('warning', "bingo_dedupe_drawn_number: eliminados duplicados del número {$number} en juego {$gameId}");
        }
    }
}

if (!function_exists('bingo_insert_drawn_number')) {
    /**
     * Inserta una bola solo si ese número aún no existe en la partida.
     * @return bool true si se insertó
     */
    function bingo_insert_drawn_number(int $gameId, int $number, array $extra = []): bool
    {
        if ($gameId < 1 || $number < 1 || $number > 75) {
            return false;
        }

        if (bingo_number_already_drawn($gameId, $number)) {
            return false;
        }

        $model = new BoardsModel();
        $payload = array_merge([
            'game'   => $gameId,
            'number' => $number,
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ], $extra);

        try {
            $model->insert($payload);
        } catch (\Throwable $e) {
            log_message('error', 'bingo_insert_drawn_number: ' . $e->getMessage());

            return false;
        }

        bingo_dedupe_drawn_number($gameId, $number);

        return true;
    }
}

if (!function_exists('bingo_count_drawn_numbers')) {
    function bingo_count_drawn_numbers(int $gameId): int
    {
        return count(bingo_get_ordered_drawn_numbers($gameId));
    }
}

if (!function_exists('bingo_broadcast_number_drawn')) {
    /**
     * Notifica en tiempo real a jugadores/admin la bola cantada.
     */
    function bingo_broadcast_number_drawn(int $gameId, int $number, ?array $drawnNumbers = null, ?int $totalNumbersGenerated = null): void
    {
        if ($gameId < 1 || $number < 1) {
            return;
        }

        try {
            if (!class_exists(\App\Libraries\PusherFactory::class) || !class_exists(\Pusher\Pusher::class)) {
                return;
            }

            $drawn = $drawnNumbers ?? bingo_get_ordered_drawn_numbers($gameId);
            $total = $totalNumbersGenerated ?? count($drawn);

            \App\Libraries\PusherFactory::make()->trigger('private-game-' . $gameId, 'game:number_drawn', [
                'n' => $number,
                'number' => $number,
                'drawn' => $drawn,
                'drawnNumbers' => $drawn,
                'totalNumbersGenerated' => $total,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error al notificar bola por Pusher: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_broadcast_sing_accepted')) {
    /**
     * Notifica en tiempo real a los jugadores por Pusher cuando se canta/acepta un Bingo.
     */
    function bingo_broadcast_sing_accepted(int $gameId, array $payload): void
    {
        if ($gameId < 1) {
            return;
        }

        try {
            if (!class_exists(\App\Libraries\PusherFactory::class) || !class_exists(\Pusher\Pusher::class)) {
                return;
            }

            $pusher = \App\Libraries\PusherFactory::make();
            $channel = 'private-game-' . $gameId;

            // Emitir evento bingo_claimed
            $pusher->trigger($channel, 'game:bingo_claimed', array_merge([
                'gameId' => $gameId,
                'at' => date('c'),
            ], $payload));

            // Emitir evento bingo_accepted
            $pusher->trigger($channel, 'game:bingo_accepted', array_merge([
                'gameId' => $gameId,
                'stopped' => true,
                'at' => date('c'),
            ], $payload));
        } catch (\Throwable $e) {
            log_message('error', 'Error al notificar Bingo por Pusher: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_broadcast_game_status')) {
    /**
     * Notifica en tiempo real a los jugadores por Pusher cambios de estado del juego (inicio, fin, posposición).
     */
    function bingo_broadcast_game_status(int $gameId, string $statusEvent, array $extraData = []): void
    {
        if ($gameId < 1) {
            return;
        }

        try {
            if (!class_exists(\App\Libraries\PusherFactory::class) || !class_exists(\Pusher\Pusher::class)) {
                return;
            }

            $pusher = \App\Libraries\PusherFactory::make();
            $channel = 'private-game-' . $gameId;

            $pusher->trigger($channel, $statusEvent, array_merge([
                'gameId' => $gameId,
                'timestamp' => date('c'),
            ], $extraData));
        } catch (\Throwable $e) {
            log_message('error', 'Error al notificar cambio de estado por Pusher: ' . $e->getMessage());
        }
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
    /**
     * Conserva hasta numberSings cantes oficiales por modalidad (orden de llegada).
     * Antes solo dejaba el primero y el resto no se pagaba ni se anunciaba.
     */
    function bingo_filter_first_sing_per_modality(array $sings): array
    {
        $limit = bingo_get_number_sings_limit();
        $counts = [];
        $official = [];

        foreach ($sings as $sing) {
            $modalityId = (int) ($sing['modality'] ?? 0);
            if ($modalityId < 1) {
                continue;
            }

            $used = $counts[$modalityId] ?? 0;
            if ($used >= $limit) {
                continue;
            }

            $counts[$modalityId] = $used + 1;
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
            ->where('carton', $cartonId)
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

        $success = $inserted !== false && $db->transStatus();
        if ($success) {
            try {
                $singId = $modelSings->insertID();

                // Pagar los premios AUTOMÁTICAMENTE para todos los ganadores pendientes del juego
                if ($finalize) {
                    bingo_pay_pending_awards_for_game($gameId);
                }

                $modelUsers = new \App\Models\UsersModel();
                $userSing = $modelUsers->find($userId);
                $userName = $userSing ? trim(($userSing['firstname'] ?? '') . ' ' . ($userSing['lastname'] ?? '')) : ('Jugador #' . $userId);

                bingo_broadcast_sing_accepted($gameId, [
                    'singId'       => $singId,
                    'userId'       => $userId,
                    'playerId'     => (string) $userId,
                    'player'       => $userName,
                    'playerName'   => $userName,
                    'modality'     => translate($modality['name'] ?? ''),
                    'modalityId'   => (int) $modality['id'],
                    'modalityName' => translate($modality['name'] ?? ''),
                    'cartonId'     => $cartonId,
                    'lastNumber'   => $lastBallNumber,
                ]);
            } catch (\Throwable $pe) {
                log_message('error', 'Error broadcasting/paying sing in bingo_register_sing_if_missing: ' . $pe->getMessage());
            }
        }

        return $success;
    }
}

if (!function_exists('bingo_claim_pending_board_sing')) {
    /**
     * Devuelve el siguiente bingo que el tablero (admin LIVE/board) aún no ha anunciado.
     * Usa el marcador "board" en notified porque el jugador ya confirma el sing (status >= 1).
     */
    function bingo_claim_pending_board_sing(int $gameId): ?array
    {
        $modelSings = new SingsModel();
        $modelUsers = new UsersModel();
        $modelModalities = new ModalitiesModel();

        $sings = $modelSings
            ->where('game', $gameId)
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($sings as $sing) {
            $status = (int) ($sing['status'] ?? 0);
            // 0 = recién registrado / pendiente; 1 = confirmado; 2 = pagado
            if ($status < 0) {
                continue;
            }

            $notified = json_decode($sing['notified'] ?? '[]', true);
            if (!is_array($notified)) {
                $notified = [];
            }

            $alreadyBoard = false;
            foreach ($notified as $entry) {
                if ((string) $entry === 'board') {
                    $alreadyBoard = true;
                    break;
                }
            }

            if ($alreadyBoard) {
                continue;
            }

            // Sings antiguos ya confirmados: marcar en silencio para no re-anunciar tras el deploy
            if ($status >= 1) {
                $createdAt = strtotime($sing['created_at'] ?? '');
                if ($createdAt && (time() - $createdAt) > 120) {
                    $notified[] = 'board';
                    $modelSings->update($sing['id'], [
                        'notified' => json_encode(array_values($notified)),
                    ]);
                    continue;
                }
            }

            $user = $modelUsers->find($sing['user']);
            $modality = $modelModalities->find($sing['modality']);
            if (!$user || !$modality) {
                continue;
            }

            $notified[] = 'board';
            $update = [
                'notified' => json_encode(array_values($notified)),
            ];
            if ($status === 0) {
                $update['status'] = 1;
            }
            $modelSings->update($sing['id'], $update);

            $imagePath = bingo_user_image_url($user);

            return [
                'sing' => $sing,
                'player' => $user['firstname'] . ' ' . $user['lastname'],
                'modality' => translate($modality['name']),
                'modalityId' => $modality['id'],
                'image' => $imagePath,
            ];
        }

        return null;
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
            $requiredPositions = explode(',', (string) $modality['positions']);

            foreach ($cartons as $carton) {
                $existingForModality = (new SingsModel())
                    ->where('game', $gameId)
                    ->where('modality', $modality['id'])
                    ->countAllResults();

                if ($existingForModality >= $numberSingsLimit) {
                    break;
                }

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
                }
            }
        }

        return $registered;
    }
}

if (!function_exists('bingo_money_to_cents')) {
    /** Convierte dinero a centavos enteros (evita errores de float tipo +0.01). */
    function bingo_money_to_cents(float $amount): int
    {
        return (int) round(((float) $amount) * 100 + 1e-6);
    }
}

if (!function_exists('bingo_cents_to_money')) {
    function bingo_cents_to_money(int $cents): float
    {
        return round(max(0, $cents) / 100, 2);
    }
}

if (!function_exists('bingo_normalize_earnings_rate')) {
    /** rateEarnings en BD como fracción (0.20). Si viniera como 20, normaliza. */
    function bingo_normalize_earnings_rate(): float
    {
        $rate = (float) (systemGet('rateEarnings') ?? 0);
        if ($rate > 1) {
            $rate = $rate / 100;
        }

        return max(0.0, min(1.0, $rate));
    }
}

if (!function_exists('bingo_calculate_award_per_sing')) {
    function bingo_calculate_award_per_sing(array $game, array $award, int $gameId, int $modalityId): float
    {
        $db = \Config\Database::connect();

        // Contar únicamente cantes oficiales aceptados o pagados (status 1 o 2)
        $singsCount = (int) $db->table('sings')
            ->where('game', $gameId)
            ->where('modality', $modalityId)
            ->whereIn('status', [1, 2])
            ->countAllResults();
        $singsCount = max(1, $singsCount);

        // Contar únicamente cartones comprados/asignados a jugadores (user != 0), excluyendo temp_cartons
        $cartons = (int) $db->table('cartons')
            ->where('game', $gameId)
            ->where('user !=', 0)
            ->countAllResults();

        if ((int) ($game['award'] ?? 0) === 2) {
            $fixedCents = bingo_money_to_cents((float) ($award['amount'] ?? 0));

            // Nunca repartir de más: centavos enteros hacia abajo
            return bingo_cents_to_money(intdiv($fixedCents, $singsCount));
        }

        $poolCents = bingo_money_to_cents(bingo_calculate_game_prize_pool($game, $cartons));
        $pct = (float) ($award['amount'] ?? 0);
        $modalityCents = (int) round($poolCents * $pct / 100);

        if ($modalityCents <= 0 && $pct > 0) {
            $fallbackCents = bingo_money_to_cents($pct);
            return bingo_cents_to_money(intdiv($fallbackCents, $singsCount));
        }

        return bingo_cents_to_money(intdiv($modalityCents, $singsCount));
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

if (!function_exists('bingo_pay_source_from_split')) {
    /**
     * Origen de pago del cartón.
     * Mixed SOLO = Recarga + Retiro. El bono nunca se combina con otros saldos.
     *
     * @param array{from_bonus?:float,from_recharge?:float,from_withdraw?:float} $split
     */
    function bingo_pay_source_from_split(array $split, string $source = 'wallet'): string
    {
        $source = strtolower(trim($source));
        if ($source === 'roulette') {
            return 'roulette';
        }
        if ($source === 'bonus') {
            return 'bonus';
        }

        $fromBonus = round((float) ($split['from_bonus'] ?? 0), 2);
        $fromRecharge = round((float) ($split['from_recharge'] ?? 0), 2);
        $fromWithdraw = round((float) ($split['from_withdraw'] ?? 0), 2);

        // Cualquier uso de bono = origen bono (no existe mixed con bono)
        if ($fromBonus > 0) {
            return 'bonus';
        }

        if ($fromRecharge > 0 && $fromWithdraw > 0) {
            return 'mixed';
        }
        if ($fromRecharge > 0) {
            return 'recharge';
        }
        if ($fromWithdraw > 0) {
            return 'withdraw';
        }

        return 'real';
    }
}

if (!function_exists('bingo_pay_source_from_log')) {
    function bingo_pay_source_from_log(array $log): string
    {
        return bingo_pay_source_from_split([
            'from_bonus' => $log['from_bonus'] ?? 0,
            'from_recharge' => $log['from_recharge'] ?? 0,
            'from_withdraw' => $log['from_withdraw'] ?? 0,
        ], (string) ($log['source'] ?? 'wallet'));
    }
}

if (!function_exists('bingo_award_goes_to_withdraw')) {
    /** Premio a saldo retiro solo si el cartón se pagó con dinero real (recarga y/o retiro). */
    function bingo_award_goes_to_withdraw(string $paySource): bool
    {
        return in_array($paySource, ['real', 'recharge', 'withdraw', 'mixed'], true);
    }
}

if (!function_exists('bingo_ensure_cartons_pay_source_column')) {
    function bingo_ensure_cartons_pay_source_column(): void
    {
        try {
            $db = \Config\Database::connect();
            if (! $db->tableExists('cartons') || $db->fieldExists('pay_source', 'cartons')) {
                return;
            }
            $forge = \Config\Database::forge();
            $forge->addColumn('cartons', [
                'pay_source' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                    'default' => null,
                    'after' => 'status',
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'bingo_ensure_cartons_pay_source_column: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_tag_cartons_pay_source')) {
    /**
     * @param list<int|string> $cartonIds
     */
    function bingo_tag_cartons_pay_source(array $cartonIds, string $paySource): void
    {
        $ids = array_values(array_filter(array_map('intval', $cartonIds), static fn ($id) => $id > 0));
        if ($ids === []) {
            return;
        }

        $paySource = strtolower(trim($paySource));
        if (! in_array($paySource, ['bonus', 'real', 'recharge', 'withdraw', 'roulette', 'mixed'], true)) {
            $paySource = 'real';
        }

        bingo_ensure_cartons_pay_source_column();

        try {
            $db = \Config\Database::connect();
            if (! $db->fieldExists('pay_source', 'cartons')) {
                return;
            }
            $db->table('cartons')
                ->whereIn('id', $ids)
                ->set(['pay_source' => $paySource, 'updated_at' => date('Y-m-d H:i:s')])
                ->update();
        } catch (\Throwable $e) {
            log_message('error', 'bingo_tag_cartons_pay_source: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_infer_carton_pay_source')) {
    /**
     * Infiere origen del cartón por el log de compra más cercano (misma partida).
     * El log manda sobre pay_source guardado: "mixed" antiguo podía ser bono+recarga.
     */
    function bingo_infer_carton_pay_source(int $cartonId, int $userId, int $gameId): ?string
    {
        if ($cartonId <= 0 || $userId <= 0 || $gameId <= 0) {
            return null;
        }

        try {
            bingo_ensure_users_schema();
            bingo_ensure_cartons_pay_source_column();

            $modelCartons = new \App\Models\CartonsModel();
            $carton = $modelCartons->find($cartonId);
            if (! $carton || (int) ($carton['user'] ?? 0) !== $userId) {
                return null;
            }

            $db = \Config\Database::connect();
            if ($db->tableExists('carton_purchase_logs')) {
                $logs = (new \App\Models\CartonPurchaseLogsModel())
                    ->where('user_id', $userId)
                    ->where('game_id', $gameId)
                    ->orderBy('created_at', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->findAll();

                if ($logs !== []) {
                    $cartonTs = strtotime((string) ($carton['created_at'] ?? '')) ?: 0;
                    $best = null;
                    $bestDiff = PHP_INT_MAX;

                    foreach ($logs as $log) {
                        $logTs = strtotime((string) ($log['created_at'] ?? '')) ?: 0;
                        $diff = abs($cartonTs - $logTs);
                        if ($diff < $bestDiff) {
                            $bestDiff = $diff;
                            $best = $log;
                        }
                    }

                    if (count($logs) === 1) {
                        return bingo_pay_source_from_log($logs[0]);
                    }
                    if ($best !== null && $bestDiff <= 180) {
                        return bingo_pay_source_from_log($best);
                    }
                    if ($best !== null) {
                        return bingo_pay_source_from_log($best);
                    }
                }
            }

            $stored = strtolower(trim((string) ($carton['pay_source'] ?? '')));
            // "mixed" sin log es ambiguo (legacy bono+recarga): no confiar → null
            if (in_array($stored, ['bonus', 'real', 'recharge', 'withdraw', 'roulette'], true)) {
                return $stored;
            }

            return null;
        } catch (\Throwable $e) {
            log_message('error', 'bingo_infer_carton_pay_source: ' . $e->getMessage());

            return null;
        }
    }
}

if (!function_exists('bingo_resolve_award_credit_split')) {
    /**
     * Destino del premio según la compra del cartón ganador (no de toda la partida).
     * Bono / ruleta → saldo recarga.
     * Dinero real (recarga, retiro o Mixed Recarga+Retiro) → saldo retiro.
     *
     * @return array{to_recharge:float,to_withdraw:float,has_logs:bool,pay_source?:string}
     */
    function bingo_resolve_award_credit_split(int $userId, int $gameId, float $prizeAmount, ?int $cartonId = null): array
    {
        $prizeAmount = round(max(0, $prizeAmount), 2);
        $empty = [
            'to_recharge' => $prizeAmount,
            'to_withdraw' => 0.0,
            'has_logs' => false,
            'pay_source' => 'unknown',
        ];

        if ($userId <= 0 || $gameId <= 0 || $prizeAmount <= 0) {
            return $empty;
        }

        helper(['wallet', 'bingo']);
        if (function_exists('bingo_ensure_users_schema')) {
            bingo_ensure_users_schema();
        }
        bingo_ensure_cartons_pay_source_column();

        try {
            // 1) Prioridad: origen del cartón que cantó
            if ($cartonId !== null && $cartonId > 0) {
                $paySource = bingo_infer_carton_pay_source($cartonId, $userId, $gameId);
                if ($paySource !== null) {
                    $toWithdraw = bingo_award_goes_to_withdraw($paySource);

                    return [
                        'to_recharge' => $toWithdraw ? 0.0 : $prizeAmount,
                        'to_withdraw' => $toWithdraw ? $prizeAmount : 0.0,
                        'has_logs' => true,
                        'pay_source' => $paySource,
                    ];
                }
            }

            $db = \Config\Database::connect();
            if (! $db->tableExists('carton_purchase_logs')) {
                return $empty;
            }

            $logs = (new \App\Models\CartonPurchaseLogsModel())
                ->where('user_id', $userId)
                ->where('game_id', $gameId)
                ->findAll();

            if ($logs === []) {
                return $empty;
            }

            // 2) Fallback sin cartón: solo retiro si TODAS las compras son dinero real (sin bono/ruleta)
            $hasBonusOrRoulette = false;
            $hasReal = false;
            foreach ($logs as $log) {
                $src = bingo_pay_source_from_log($log);
                if (in_array($src, ['bonus', 'roulette'], true)) {
                    $hasBonusOrRoulette = true;
                }
                if (in_array($src, ['real', 'recharge', 'withdraw', 'mixed'], true)) {
                    $hasReal = true;
                }
            }

            $toWithdraw = $hasReal && ! $hasBonusOrRoulette;

            return [
                'to_recharge' => $toWithdraw ? 0.0 : $prizeAmount,
                'to_withdraw' => $toWithdraw ? $prizeAmount : 0.0,
                'has_logs' => true,
                'pay_source' => $toWithdraw ? 'real' : ($hasBonusOrRoulette ? 'bonus' : 'unknown'),
            ];
        } catch (\Throwable $e) {
            log_message('error', 'bingo_resolve_award_credit_split: ' . $e->getMessage());
            return $empty;
        }
    }
}

if (!function_exists('bingo_credit_award_by_purchase_source')) {
    /**
     * @return array{to_recharge:float,to_withdraw:float,has_logs:bool,pay_source?:string}
     */
    function bingo_credit_award_by_purchase_source(int $userId, int $gameId, float $prizeAmount, ?int $cartonId = null): array
    {
        helper('wallet');
        $split = bingo_resolve_award_credit_split($userId, $gameId, $prizeAmount, $cartonId);

        if ($split['to_recharge'] > 0) {
            wallet_credit_recharge($userId, $split['to_recharge']);
        }
        if ($split['to_withdraw'] > 0) {
            wallet_credit_withdrawable($userId, $split['to_withdraw']);
        }

        return $split;
    }
}

if (!function_exists('bingo_deduct_award_by_purchase_source')) {
    /**
     * Revierte un premio con el mismo criterio de destino (earring).
     *
     * @return array{to_recharge:float,to_withdraw:float,has_logs:bool,pay_source?:string}
     */
    function bingo_deduct_award_by_purchase_source(int $userId, int $gameId, float $prizeAmount, ?int $cartonId = null): array
    {
        helper('wallet');
        $split = bingo_resolve_award_credit_split($userId, $gameId, $prizeAmount, $cartonId);

        if ($split['to_recharge'] > 0) {
            wallet_deduct_recharge($userId, $split['to_recharge']);
        }
        if ($split['to_withdraw'] > 0) {
            wallet_deduct_withdrawable($userId, $split['to_withdraw']);
        }

        return $split;
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

        $existingPayment = $modelPayments
            ->where('type', 'award')
            ->where('type_id', $singId)
            ->first();
        if ($existingPayment) {
            if ($singStatus === 1) {
                $modelSings->update($singId, ['status' => 2]);
            }

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

        $awardCreditSplit = bingo_credit_award_by_purchase_source(
            (int) $sing['user'],
            (int) $sing['game'],
            $awardPerSing,
            (int) ($sing['carton'] ?? 0)
        );
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
        bingo_notify_award_payment($user, $game, $sing, $modalitySing, $awardPerSing, $paymentId, $paidByUserId, $awardCreditSplit);

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
        int $fromUserId,
        array $creditSplit = []
    ): void {
        $modelNotifications = new NotificationsModel();
        $currency = systemGet('currency');
        $modalityName = translate($modality['name'] ?? '');
        $gameName = $game['description'] ?? translate('game');

        $toRecharge = round((float) ($creditSplit['to_recharge'] ?? $awardPerSing), 2);
        $toWithdraw = round((float) ($creditSplit['to_withdraw'] ?? 0), 2);

        if ($toRecharge > 0 && $toWithdraw > 0) {
            $destMsg = translate('award split credit message');
            $destMsg = str_replace(
                [':currency', ':recharge', ':withdraw'],
                [$currency, number_format($toRecharge, 2), number_format($toWithdraw, 2)],
                $destMsg
            );
        } elseif ($toWithdraw > 0) {
            $destMsg = translate('award to withdraw balance message');
            $destMsg = str_replace(
                [':currency', ':amount'],
                [$currency, number_format($toWithdraw, 2)],
                $destMsg
            );
        } else {
            $destMsg = translate('award to recharge balance message');
            $destMsg = str_replace(
                [':currency', ':amount'],
                [$currency, number_format($toRecharge > 0 ? $toRecharge : $awardPerSing, 2)],
                $destMsg
            );
        }

        $modelNotifications->insert([
            'user' => (int) $user['id'],
            'from' => $fromUserId,
            'game' => (int) $sing['game'],
            'modality' => (int) $sing['modality'],
            'type' => 'payment',
            'type_id' => $paymentId,
            'title' => '🎉 ¡GANASTE! Premio acreditado',
            'message' => 'Felicitaciones, ganaste la partida "' . $gameName . '" en la modalidad ' . $modalityName . '. ' . $destMsg,
        ]);
    }
}

if (!function_exists('bingo_pay_pending_awards_for_game')) {
    function bingo_pay_pending_awards_for_game(int $gameId, ?int $fromUserId = null): int
    {
        if ($gameId < 1) {
            return 0;
        }

        $modelGames = new \App\Models\GamesModel();
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
            (new \App\Models\SingsModel())
                ->where('game', $gameId)
                ->where('status', 1)
                ->orderBy('created_at', 'ASC')
                ->orderBy('id', 'ASC')
                ->findAll()
        );

        $paid = 0;

        // Pagar todos los cantes oficiales (hasta numberSings por modalidad), no solo el primero.
        foreach ($pendingSings as $sing) {
            $result = bingo_pay_sing_award((int) $sing['id'], max(1, $fromUserId));
            if ($result['success'] ?? false) {
                $paid++;
            }
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

        // Completar ganadores faltantes hasta numberSings (empates en la misma bola)
        bingo_resolve_missed_bingos_for_game($gameId, false);

        $modelSings = new SingsModel();
        $existingSings = $modelSings->where('game', $gameId)->countAllResults();

        // Si no hubo ningún canto, recuperación a fin de partida (sin exigir última bola)
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
     * Cuenta cartones comprados/asignados (user != 0).
     * No cuenta cartones disponibles sin vender (user = 0), porque eso
     * hacía pasar el mínimo de cartones y saltarse la posposición.
     * En modo LIVE también incluye selección temporal (temp_cartons).
     */
    function bingo_count_game_cartons(int $gameId): int
    {
        try {
            $db = \Config\Database::connect();

            $sold = (int) $db->table('cartons')
                ->where('game', $gameId)
                ->where('user !=', 0)
                ->countAllResults();

            $temp = 0;
            if ($db->tableExists('temp_cartons')) {
                $temp = (int) $db->table('temp_cartons')
                    ->where('game', $gameId)
                    ->countAllResults();
            }

            return $sold + $temp;
        } catch (\Throwable $e) {
            log_message('error', 'bingo_count_game_cartons: ' . $e->getMessage());

            return 0;
        }
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
    /**
     * Debe cumplir mínimo de jugadores Y mínimo de cartones.
     * @param bool $allowAdminBypass Si es true, un admin logueado puede iniciar sin mínimos.
     */
    function bingo_can_start_game(array $game, ?int $playerCount = null, ?int $cartonCount = null, bool $allowAdminBypass = false): bool
    {
        if ($allowAdminBypass && session()->get('logged_in') && (int) session()->get('group') === 1) {
            return true;
        }

        if ($playerCount === null) {
            $playerCount = bingo_count_game_players((int) $game['id']);
        }

        if ($cartonCount === null) {
            $cartonCount = bingo_count_game_cartons((int) $game['id']);
        }

        return $playerCount >= bingo_get_min_players($game)
            && $cartonCount >= bingo_get_min_cartons($game);
    }
}

if (!function_exists('bingo_game_is_due')) {
    /** True si la fecha/hora programada ya llegó (zona app). */
    function bingo_game_is_due(array $game): bool
    {
        $tzName = function_exists('app_timezone') ? app_timezone() : (config('App')->appTimezone ?? 'America/Guayaquil');
        $tz = new \DateTimeZone($tzName);
        $nowObj = new \DateTime('now', $tz);

        $date = trim((string) ($game['date'] ?? ''));
        $time = trim((string) ($game['time'] ?? ''));
        if ($date === '') {
            $date = $nowObj->format('Y-m-d');
        }
        if ($time === '') {
            $time = $nowObj->format('H:i:s');
        }
        // Normalizar H:i → H:i:s
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $time .= ':00';
        }

        try {
            $gameDateTime = new \DateTime($date . ' ' . $time, $tz);
        } catch (\Exception $e) {
            return false;
        }

        return $gameDateTime <= $nowObj;
    }
}

if (!function_exists('bingo_game_start_iso')) {
    /** ISO 8601 con offset de la zona app (para countdown JS fiable). */
    function bingo_game_start_iso(array $game): string
    {
        $tzName = function_exists('app_timezone') ? app_timezone() : (config('App')->appTimezone ?? 'America/Guayaquil');
        $tz = new \DateTimeZone($tzName);
        $date = trim((string) ($game['date'] ?? ''));
        $time = trim((string) ($game['time'] ?? '00:00:00'));
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $time .= ':00';
        }
        try {
            $dt = new \DateTime(($date !== '' ? $date : 'now') . ($date !== '' ? ' ' . $time : ''), $tz);
            return $dt->format('c');
        } catch (\Exception $e) {
            return (new \DateTime('now', $tz))->format('c');
        }
    }
}

if (!function_exists('bingo_postpone_game')) {
    /**
     * Pospone una partida 5 minutos si no cumple mínimos.
     * @return array{postponed:bool,new_time:?string,message:string,player_count:int,carton_count:int}
     */
    function bingo_postpone_game(array $game, ?int $playerCount = null, ?int $cartonCount = null): array
    {
        if ($playerCount === null) {
            $playerCount = bingo_count_game_players((int) $game['id']);
        }
        if ($cartonCount === null) {
            $cartonCount = bingo_count_game_cartons((int) $game['id']);
        }

        if (bingo_can_start_game($game, $playerCount, $cartonCount, false)) {
            return [
                'postponed' => false,
                'new_time' => null,
                'message' => '',
                'player_count' => $playerCount,
                'carton_count' => $cartonCount,
            ];
        }

        $tzName = function_exists('app_timezone') ? app_timezone() : (config('App')->appTimezone ?? 'America/Guayaquil');
        $tz = new \DateTimeZone($tzName);
        $nowObj = new \DateTime('now', $tz);
        $now = $nowObj->format('Y-m-d H:i:s');

        try {
            $gameDateTime = new \DateTime(($game['date'] ?? $nowObj->format('Y-m-d')) . ' ' . ($game['time'] ?? $nowObj->format('H:i:s')), $tz);
        } catch (\Exception $e) {
            $gameDateTime = clone $nowObj;
        }

        $baseTime = $gameDateTime > $nowObj ? clone $gameDateTime : clone $nowObj;
        $baseTime->modify('+5 minutes');

        $gameId = (int) ($game['id'] ?? 0);
        $updateData = [
            'date' => $baseTime->format('Y-m-d'),
            'time' => $baseTime->format('H:i:s'),
            'updated_at' => $now,
            // Queda programada hasta que cumpla mínimos e inicie de nuevo
            'status' => 2,
        ];

        $modelGames = new \App\Models\GamesModel();
        $modelGames->update($gameId, $updateData);

        $minPlayers = bingo_get_min_players($game);
        $minCartons = bingo_get_min_cartons($game);
        $postponeMsg = str_replace(
            [':time', ':players', ':cartons'],
            [$baseTime->format('H:i'), $minPlayers, $minCartons],
            translate('game postponed notification')
        );

        try {
            $modelNotifications = new \App\Models\NotificationsModel();
            $modelNotifications->insert([
                'user' => 0,
                'type' => 'system',
                'game' => $gameId,
                'title' => '⏳ ' . translate('game postponed'),
                'message' => $postponeMsg,
                'status' => 0,
                'created_at' => $now,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Error al guardar notificación de posposición: ' . $e->getMessage());
        }

        try {
            if (class_exists(\App\Libraries\PusherFactory::class) && class_exists(\Pusher\Pusher::class)) {
                \App\Libraries\PusherFactory::make()->trigger('private-game-' . $gameId, 'game:postponed', [
                    'new_time' => $baseTime->format('c'),
                    'message' => $postponeMsg,
                ]);
            }
        } catch (\Throwable $pe) {
            log_message('error', 'Error al notificar posposición por Pusher: ' . $pe->getMessage());
        }

        log_message('info', "Juego {$gameId} pospuesto 5 minutos hasta " . $baseTime->format('Y-m-d H:i:s') . " (jugadores={$playerCount}/{$minPlayers}, cartones={$cartonCount}/{$minCartons})");

        return [
            'postponed' => true,
            'new_time' => $baseTime->format('H:i'),
            'new_datetime' => $baseTime->format('Y-m-d H:i:s'),
            'message' => trim(bingo_game_start_block_message($game, $playerCount, $cartonCount) . ' ' . translate('game postponed to') . ' ' . $baseTime->format('H:i') . '.'),
            'player_count' => $playerCount,
            'carton_count' => $cartonCount,
        ];
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

        // Si la partida NO puede iniciar, mostrar los requisitos que faltan
        if ($playerCount < bingo_get_min_players($game)) {
            $messages[] = bingo_min_players_start_message($game, $playerCount);
        }
        if ($cartonCount < bingo_get_min_cartons($game)) {
            $messages[] = bingo_min_cartons_start_message($game, $cartonCount);
        }

        return implode(' ', $messages);
    }
}

if (!function_exists('bingo_calculate_game_prize_pool')) {
    function bingo_calculate_game_prize_pool(array $game, int $cartonCount): float
    {
        $priceCents = bingo_money_to_cents((float) ($game['price'] ?? 0));
        $accumulatedCents = $priceCents * max(0, (int) $cartonCount);
        $rate = bingo_normalize_earnings_rate();
        $poolCents = (int) round($accumulatedCents * (1 - $rate));

        return bingo_cents_to_money($poolCents);
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
            return bingo_cents_to_money(bingo_money_to_cents((float) ($award['amount'] ?? 0)));
        }

        // Acumulado (en centavos para no regalar centavos por float)
        $poolCents = bingo_money_to_cents(bingo_calculate_game_prize_pool($game, $cartonCount));
        $pct = (float) ($award['amount'] ?? 0);
        $modalityCents = (int) round($poolCents * $pct / 100);

        if ($modalityCents <= 0 && $pct > 0) {
            return bingo_cents_to_money(bingo_money_to_cents($pct));
        }

        return bingo_cents_to_money($modalityCents);
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

if (!function_exists('bingo_generate_player_username')) {
    /**
     * Alias aleatorio basado en nombre + apellido (único).
     */
    function bingo_generate_player_username(string $firstname, string $lastname, \App\Models\UsersModel $model, ?int $excludeId = null): string
    {
        $normalize = static function (string $value): string {
            $ascii = $value;
            if (function_exists('iconv')) {
                $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
                if (is_string($converted) && $converted !== '') {
                    $ascii = $converted;
                }
            }
            $ascii = strtolower(preg_replace('/[^a-z0-9]/i', '', $ascii) ?? '');

            return $ascii;
        };

        $first = $normalize($firstname);
        $last = $normalize($lastname);
        $base = substr($first . $last, 0, 12);
        if ($base === '') {
            $base = 'jugador';
        }

        for ($i = 0; $i < 40; $i++) {
            $suffix = (string) random_int(100, 9999);
            $candidate = substr($base, 0, 16) . $suffix;
            $builder = $model->where('username', $candidate);
            if ($excludeId) {
                $builder = $builder->where('id !=', $excludeId);
            }
            if (! $builder->first()) {
                return $candidate;
            }
        }

        return $base . substr(bin2hex(random_bytes(3)), 0, 6);
    }
}

if (!function_exists('bingo_player_email_is_verified')) {
    function bingo_player_email_is_verified(?array $user): bool
    {
        if (! is_array($user) || $user === []) {
            return false;
        }

        // Admin / PV / operador no requieren verificación de correo de jugador
        if ((int) ($user['group'] ?? 0) !== 0) {
            return true;
        }

        return (int) ($user['verified_email'] ?? 0) === 1;
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
        return true;
    }
}

if (!function_exists('bingo_user_kyc_verified')) {
    function bingo_user_kyc_verified(?array $user): bool
    {
        if (!$user) {
            return false;
        }

        // Tiendas/operadores no pasan por KYC de jugador.
        if (!bingo_user_requires_kyc($user)) {
            return true;
        }

        return (string) ($user['kyc_status'] ?? 'pending') === 'verified';
    }
}

if (!function_exists('bingo_user_roulette_available')) {
    /**
     * Ruletazo visible/usable solo si el sistema lo tiene activo,
     * el jugador tiene giro pendiente (roulette=0) y su KYC está verificado.
     */
    function bingo_user_roulette_available(?array $user): bool
    {
        if (!$user || (int) systemGet('activateRoulette') !== 1) {
            return false;
        }

        if ((int) ($user['roulette'] ?? 1) !== 0) {
            return false;
        }

        return bingo_user_kyc_verified($user);
    }
}

if (!function_exists('bingo_activate_roulette_on_kyc_verified')) {
    /**
     * Activa el Ruletazo al aprobar KYC (una sola vez si aún no reclamó premio).
     */
    function bingo_activate_roulette_on_kyc_verified(int $userId): bool
    {
        if ($userId < 1 || (int) systemGet('activateRoulette') !== 1) {
            return false;
        }

        $modelUsers = new \App\Models\UsersModel();
        $user = $modelUsers->find($userId);
        if (!$user || (int) ($user['group'] ?? -1) !== bingo_group_player()) {
            return false;
        }

        if (!bingo_user_kyc_verified($user)) {
            return false;
        }

        $alreadyClaimed = (new \App\Models\RoulettesModel())
            ->where('user', $userId)
            ->countAllResults() > 0;

        if ($alreadyClaimed) {
            return false;
        }

        $message = 'Tu cuenta fue verificada. ¡Ya puedes girar el Ruletazo y ganar cartones!';

        return bingo_grant_player_roulette($userId, $message, true);
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

if (!function_exists('bingo_user_is_active')) {
    /** Cuenta usable: no eliminada y status = 1 (activo). */
    function bingo_user_is_active(?array $user): bool
    {
        if (! $user) {
            return false;
        }

        return (int) ($user['deleted'] ?? 0) === 0
            && (int) ($user['status'] ?? 0) === 1;
    }
}

if (!function_exists('bingo_destroy_user_sessions')) {
    /**
     * Elimina sesiones en BD del usuario (ci_sessions) para forzar cierre.
     */
    function bingo_destroy_user_sessions(int $userId): void
    {
        if ($userId < 1) {
            return;
        }

        try {
            $sessionConfig = config('Session');
            $driver = (string) ($sessionConfig->driver ?? '');
            $table = (string) ($sessionConfig->savePath ?? 'ci_sessions');

            if (strpos($driver, 'DatabaseHandler') === false) {
                return;
            }

            $db = \Config\Database::connect();
            if (! $db->tableExists($table)) {
                return;
            }

            // Payload serializado PHP típico de CI: "id";i:123;
            $needle = '"id";i:' . $userId . ';';
            $db->table($table)->like('data', $needle, 'both', null, false)->delete();
        } catch (\Throwable $e) {
            log_message('error', 'bingo_destroy_user_sessions: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_enforce_active_session')) {
    /**
     * Si la sesión actual pertenece a un usuario baneado/inactivo, la cierra.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|null
     */
    function bingo_enforce_active_session()
    {
        if (! session()->get('logged_in')) {
            return null;
        }

        $userId = (int) session()->get('id');
        if ($userId < 1) {
            return null;
        }

        static $checked = [];
        if (isset($checked[$userId])) {
            return $checked[$userId] ? null : redirect()->to('/signin');
        }

        try {
            $user = (new UsersModel())->find($userId);
        } catch (\Throwable $e) {
            return null;
        }

        if (bingo_user_is_active($user)) {
            // Jugadores sin correo confirmado no pueden mantener sesión
            if (! bingo_player_email_is_verified($user)) {
                $checked[$userId] = false;
                session()->destroy();
                helper('cookie');
                delete_cookie('_signin');

                return redirect()->to('/signup/verifyPending?email=' . rawurlencode((string) ($user['email'] ?? '')));
            }

            $checked[$userId] = true;

            return null;
        }

        $checked[$userId] = false;
        session()->destroy();
        helper('cookie');
        delete_cookie('_signin');

        return redirect()->to('/signin');
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

            // Evitar truncar nombres de archivo del comprobante
            if ($db->fieldExists('voucher', 'deposits')) {
                try {
                    $forge->modifyColumn('deposits', [
                        'voucher' => [
                            'type' => 'VARCHAR',
                            'constraint' => 191,
                            'null' => true,
                            'default' => null,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    // Algunos hosts no permiten MODIFY; el guardado usa nombres cortos de todos modos.
                    log_message('debug', 'No se pudo ampliar deposits.voucher: ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo actualizar el esquema de deposits: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_upload_candidate_dirs')) {
    /**
     * Carpetas posibles de uploads/{folder}/ (Hostinger: public, raíz, writable).
     *
     * @return list<string>
     */
    function bingo_upload_candidate_dirs(string $folder, bool $onlyExisting = true): array
    {
        $folder = trim(str_replace(['..', '\\'], '', $folder), '/');
        if ($folder === '') {
            return [];
        }

        $dirs = [];
        $candidates = [
            rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR,
            rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR,
        ];

        if (defined('ROOTPATH')) {
            $candidates[] = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
            $candidates[] = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
            // A veces el docroot es public_html y dejan uploads al lado de public/
            $parent = dirname(rtrim(ROOTPATH, '/\\'));
            if ($parent && $parent !== '.' && $parent !== DIRECTORY_SEPARATOR) {
                $candidates[] = $parent . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
                $candidates[] = $parent . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
                $candidates[] = $parent . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
            }
        }

        foreach ($candidates as $dir) {
            $normalized = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
            if (! $onlyExisting || is_dir($normalized)) {
                $dirs[] = $normalized;
            }
        }

        return array_values(array_unique($dirs));
    }
}

if (!function_exists('bingo_upload_sanitize_name')) {
    function bingo_upload_sanitize_name(?string $filename): string
    {
        $filename = basename(str_replace(["\0", '\\', '/'], '', trim((string) $filename)));
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return '';
        }

        $filename = preg_replace('/[^A-Za-z0-9._-]/', '', $filename) ?? '';
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return '';
        }

        return $filename;
    }
}

if (!function_exists('bingo_upload_resolve')) {
    /**
     * Resuelve ruta real de uploads/{folder}/{file} en varias ubicaciones.
     */
    function bingo_upload_resolve(string $folder, ?string $filename): string
    {
        $filename = bingo_upload_sanitize_name($filename);
        if ($filename === '') {
            return '';
        }

        // Probar todas las rutas candidatas (aunque el dir no se haya listado antes)
        foreach (bingo_upload_candidate_dirs($folder, false) as $dir) {
            $path = $dir . $filename;
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return '';
    }
}

if (!function_exists('bingo_upload_store_file')) {
    /**
     * Guarda un UploadedFile en la primera carpeta escribible y copia a las demás.
     * Evita 404 en Hostinger cuando FCPATH/public y writable no coinciden.
     *
     * @return string nombre final del archivo o '' si falla
     */
    function bingo_upload_store_file($file, string $folder, string $preferredName): string
    {
        if (! is_object($file) || ! method_exists($file, 'isValid') || ! $file->isValid()) {
            return '';
        }

        $preferredName = bingo_upload_sanitize_name($preferredName);
        if ($preferredName === '') {
            return '';
        }

        $dirs = bingo_upload_candidate_dirs($folder, false);
        if ($dirs === []) {
            return '';
        }

        $primary = null;
        $tmpPath = method_exists($file, 'getTempName') ? (string) $file->getTempName() : '';

        foreach ($dirs as $dir) {
            if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
                continue;
            }
            if (! is_writable($dir)) {
                continue;
            }

            $target = $dir . $preferredName;
            try {
                // move() solo una vez; luego copiamos
                if ($primary === null) {
                    if (is_file($tmpPath)) {
                        if (! @copy($tmpPath, $target)) {
                            continue;
                        }
                    } elseif (method_exists($file, 'move')) {
                        $file->move($dir, $preferredName, true);
                    } else {
                        continue;
                    }
                    if (! is_file($target)) {
                        continue;
                    }
                    $primary = $target;
                } elseif (is_file($primary) && ! is_file($target)) {
                    @copy($primary, $target);
                }
            } catch (\Throwable $e) {
                log_message('error', 'bingo_upload_store_file: ' . $e->getMessage());
            }
        }

        if ($primary === null || ! is_file($primary)) {
            return '';
        }

        // Asegurar copia en FCPATH (URL estática / rewrite)
        $publicDir = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
        if (! is_dir($publicDir)) {
            @mkdir($publicDir, 0755, true);
        }
        $publicPath = $publicDir . $preferredName;
        if (is_dir($publicDir) && is_writable($publicDir) && ! is_file($publicPath) && is_file($primary)) {
            @copy($primary, $publicPath);
        }

        return $preferredName;
    }
}

if (!function_exists('bingo_kyc_image_url')) {
    /**
     * URL de imagen KYC; si el archivo no existe, placeholder (evita img rota).
     */
    function bingo_kyc_image_url(?string $filename): string
    {
        $name = bingo_upload_sanitize_name($filename);
        if ($name !== '' && bingo_upload_resolve('kyc', $name) !== '') {
            return site_url('uploads/kyc/' . $name);
        }

        // Placeholder genérico (mismo que avatar si no hay asset dedicado)
        $placeholder = FCPATH . 'assets/img/avatar.jpg';
        if (is_file($placeholder)) {
            return site_url('assets/img/avatar.jpg');
        }

        return site_url('uploads/kyc/' . ($name !== '' ? $name : 'missing.jpg'));
    }
}

if (!function_exists('bingo_user_image_url')) {
    /**
     * URL de avatar: si el archivo existe (en cualquier ruta candidata) usa uploads/;
     * si no, avatar por defecto (evita img rota aunque la BD tenga nombre).
     */
    function bingo_user_image_url(?array $user = null, ?string $image = null): string
    {
        $name = $image;
        if ($name === null && is_array($user)) {
            $name = (string) ($user['image'] ?? '');
        }
        $name = bingo_upload_sanitize_name($name);

        if ($name !== '' && bingo_upload_resolve('users', $name) !== '') {
            return site_url('uploads/users/' . $name);
        }

        return site_url('assets/img/avatar.jpg');
    }
}

if (!function_exists('bingo_voucher_public_dir')) {
    /**
     * Única ruta canónica de comprobantes: public_html/public/uploads/vouchers/
     */
    function bingo_voucher_public_dir(): string
    {
        $candidates = [];

        if (defined('FCPATH')) {
            $fc = rtrim(FCPATH, '/\\');
            // Entrada normal: .../public_html/public/
            if (strtolower(basename($fc)) === 'public') {
                $candidates[] = $fc . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'vouchers' . DIRECTORY_SEPARATOR;
            } else {
                // Si el docroot apunta a public_html, forzar /public/uploads/vouchers/
                $candidates[] = $fc . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'vouchers' . DIRECTORY_SEPARATOR;
            }
        }

        if (defined('ROOTPATH')) {
            $candidates[] = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'vouchers' . DIRECTORY_SEPARATOR;
        }

        foreach ($candidates as $dir) {
            $dir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            if (is_dir($dir)) {
                return $dir;
            }
        }

        // Último recurso (misma convención)
        $fallback = (defined('FCPATH') ? rtrim(FCPATH, '/\\') : '.') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'vouchers' . DIRECTORY_SEPARATOR;
        if (! is_dir($fallback)) {
            @mkdir($fallback, 0755, true);
        }

        return $fallback;
    }
}

if (!function_exists('bingo_voucher_candidate_dirs')) {
    /**
     * Rutas donde BUSCAR comprobantes (legacy + canónica).
     * El guardado nuevo solo usa bingo_voucher_public_dir().
     *
     * @return list<string>
     */
    function bingo_voucher_candidate_dirs(): array
    {
        $candidates = [
            bingo_voucher_public_dir(),
        ];

        if (defined('FCPATH')) {
            $candidates[] = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'vouchers' . DIRECTORY_SEPARATOR;
        }

        if (defined('WRITEPATH')) {
            $candidates[] = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'vouchers' . DIRECTORY_SEPARATOR;
        }

        if (defined('ROOTPATH')) {
            $root = rtrim(ROOTPATH, '/\\');
            $candidates[] = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'vouchers' . DIRECTORY_SEPARATOR;
            $candidates[] = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'vouchers' . DIRECTORY_SEPARATOR;
            $parent = dirname($root);
            if ($parent && $parent !== '.' && $parent !== DIRECTORY_SEPARATOR) {
                $candidates[] = $parent . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'vouchers' . DIRECTORY_SEPARATOR;
                $candidates[] = $parent . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'vouchers' . DIRECTORY_SEPARATOR;
                $candidates[] = $parent . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'vouchers' . DIRECTORY_SEPARATOR;
            }
        }

        $dirs = [];
        foreach ($candidates as $dir) {
            $dirs[] = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
        }

        return array_values(array_unique($dirs));
    }
}

if (!function_exists('bingo_voucher_dir')) {
    function bingo_voucher_dir(): string
    {
        return bingo_voucher_public_dir();
    }
}

if (!function_exists('bingo_voucher_mirror_to_public')) {
    /**
     * Si el archivo está en una ruta vieja, lo copia a public/uploads/vouchers/.
     */
    function bingo_voucher_mirror_to_public(string $resolvedPath): string
    {
        if ($resolvedPath === '' || ! is_file($resolvedPath) || @filesize($resolvedPath) <= 0) {
            return $resolvedPath;
        }

        $publicDir = bingo_voucher_public_dir();
        $normalizedPublic = rtrim(str_replace('\\', '/', $publicDir), '/') . '/';
        $normalizedResolved = str_replace('\\', '/', $resolvedPath);

        if (str_starts_with($normalizedResolved, $normalizedPublic)) {
            return $resolvedPath;
        }

        $dest = $publicDir . basename($resolvedPath);
        if (! is_file($dest) || @filesize($dest) <= 0) {
            if (! is_dir($publicDir)) {
                @mkdir($publicDir, 0755, true);
            }
            @copy($resolvedPath, $dest);
            @chmod($dest, 0644);
        }

        if (is_file($dest) && @filesize($dest) > 0) {
            return $dest;
        }

        return $resolvedPath;
    }
}

if (!function_exists('bingo_voucher_sanitize_name')) {
    function bingo_voucher_sanitize_name(?string $filename): string
    {
        $filename = basename(str_replace(["\0", '\\', '/'], '', trim((string) $filename)));
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return '';
        }

        // Normalizar: quitar caracteres inseguros en vez de rechazar todo el nombre
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '', $filename) ?? '';
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return '';
        }

        return $filename;
    }
}

if (!function_exists('bingo_voucher_resolve')) {
    /**
     * Resuelve la ruta real del archivo (busca en rutas alternativas + nombres truncados).
     */
    function bingo_voucher_resolve(?string $filename): string
    {
        $raw = basename(str_replace(["\0", '\\'], '/', trim((string) $filename)));
        $filename = bingo_voucher_sanitize_name($raw);
        if ($filename === '' && $raw === '') {
            return '';
        }

        $candidates = array_values(array_unique(array_filter([$filename, $raw])));

        foreach (bingo_voucher_candidate_dirs() as $dir) {
            foreach ($candidates as $name) {
                if ($name === '' || $name === '.' || $name === '..') {
                    continue;
                }
                $path = $dir . $name;
                if (is_file($path) && @filesize($path) > 0) {
                    return bingo_voucher_mirror_to_public($path);
                }
            }
        }

        // Recuperar si la columna truncó el nombre (p. ej. uniqid con more_entropy)
        foreach ($candidates as $prefix) {
            $prefix = bingo_voucher_sanitize_name($prefix);
            if ($prefix === '' || strlen($prefix) < 6) {
                continue;
            }
            foreach (bingo_voucher_candidate_dirs() as $dir) {
                $matches = glob($dir . $prefix . '*') ?: [];
                // También buscar sin extensión truncada: "abc.pn" -> "abc.*"
                $dot = strrpos($prefix, '.');
                if ($dot !== false && $dot > 0) {
                    $base = substr($prefix, 0, $dot);
                    if (strlen($base) >= 6) {
                        $matches = array_merge($matches, glob($dir . $base . '.*') ?: []);
                    }
                }
                foreach ($matches as $match) {
                    if (is_file($match) && @filesize($match) > 0) {
                        return bingo_voucher_mirror_to_public($match);
                    }
                }
            }
        }

        return '';
    }
}

if (!function_exists('bingo_voucher_path')) {
    function bingo_voucher_path(?string $filename): string
    {
        return bingo_voucher_resolve($filename);
    }
}

if (!function_exists('bingo_voucher_url')) {
    function bingo_voucher_url(?string $filename): string
    {
        $resolved = bingo_voucher_resolve($filename);
        if ($resolved === '') {
            $filename = bingo_voucher_sanitize_name($filename);
            if ($filename === '') {
                return '';
            }

            return site_url('payments/voucher/' . rawurlencode($filename));
        }

        // Servir el nombre real del archivo en disco
        return site_url('payments/voucher/' . rawurlencode(basename($resolved)));
    }
}

if (!function_exists('bingo_voucher_exists')) {
    function bingo_voucher_exists(?string $filename): bool
    {
        return bingo_voucher_resolve($filename) !== '';
    }
}

if (!function_exists('bingo_voucher_new_filename')) {
    function bingo_voucher_new_filename(string $ext): string
    {
        $ext = strtolower($ext);
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        if (! in_array($ext, ['jpg', 'png', 'gif', 'webp'], true)) {
            $ext = 'png';
        }

        // Nombre corto (compatible con VARCHAR(20) antiguos): 13 + 1 + ext
        return uniqid() . '.' . $ext;
    }
}

if (!function_exists('bingo_save_voucher_bytes')) {
    /**
     * Guarda el comprobante SOLO en public/uploads/vouchers/ (nunca en writable u otras).
     *
     * @return array{success:bool,filename:string,error:string}
     */
    function bingo_save_voucher_bytes(string $imageData, string $ext = 'png'): array
    {
        if ($imageData === '') {
            return ['success' => false, 'filename' => '', 'error' => 'empty'];
        }

        $fileName = bingo_voucher_new_filename($ext);
        // Única carpeta de escritura
        $dir = bingo_voucher_public_dir();
        $path = $dir . $fileName;

        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $written = @file_put_contents($path, $imageData);
        if ($written !== false && is_file($path) && @filesize($path) > 0) {
            @chmod($path, 0644);

            return ['success' => true, 'filename' => $fileName, 'error' => ''];
        }

        log_message('error', 'No se pudo guardar voucher en ' . $path . ' (writable=' . (is_writable($dir) ? '1' : '0') . ')');

        return ['success' => false, 'filename' => '', 'error' => 'write'];
    }
}

if (!function_exists('bingo_save_voucher_base64')) {
    /**
     * @return array{success:bool,filename:string,error:string}
     */
    function bingo_save_voucher_base64(?string $dataUrl): array
    {
        $dataUrl = trim((string) $dataUrl);
        if ($dataUrl === '' || stripos($dataUrl, 'data:image') !== 0) {
            return ['success' => false, 'filename' => '', 'error' => 'invalid'];
        }

        if (! preg_match('#^data:image/([a-z0-9.+-]+);base64,#i', $dataUrl, $matches)) {
            return ['success' => false, 'filename' => '', 'error' => 'invalid'];
        }

        $ext = strtolower($matches[1]);
        $raw = (string) preg_replace('#^data:image/[a-z0-9.+-]+;base64,#i', '', $dataUrl);
        $imageData = base64_decode($raw, true);
        if ($imageData === false || $imageData === '') {
            return ['success' => false, 'filename' => '', 'error' => 'decode'];
        }

        return bingo_save_voucher_bytes($imageData, $ext);
    }
}

if (!function_exists('bingo_save_voucher_upload')) {
    /**
     * Guarda un archivo subido (multipart) como comprobante.
     *
     * @return array{success:bool,filename:string,error:string}
     */
    function bingo_save_voucher_upload($file): array
    {
        if (! $file || ! is_object($file) || ! method_exists($file, 'isValid') || ! $file->isValid() || $file->hasMoved()) {
            return ['success' => false, 'filename' => '', 'error' => 'invalid'];
        }

        $mime = (string) ($file->getMimeType() ?: '');
        $ext = strtolower((string) $file->getExtension());
        if ($ext === '' && str_starts_with($mime, 'image/')) {
            $ext = substr($mime, 6);
        }
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        // Algunos móviles mandan image/jpg o vacían la extensión
        if ($ext === '' || $ext === 'jpe') {
            $ext = 'jpg';
        }
        if (! in_array($ext, ['jpg', 'png', 'gif', 'webp'], true)) {
            // Intentar detectar por mime
            if (str_contains($mime, 'png')) {
                $ext = 'png';
            } elseif (str_contains($mime, 'webp')) {
                $ext = 'webp';
            } elseif (str_contains($mime, 'gif')) {
                $ext = 'gif';
            } elseif (str_contains($mime, 'jpeg') || str_contains($mime, 'jpg')) {
                $ext = 'jpg';
            } else {
                return ['success' => false, 'filename' => '', 'error' => 'type'];
            }
        }

        $tmp = method_exists($file, 'getTempName') ? (string) $file->getTempName() : '';
        $bytes = ($tmp !== '' && is_file($tmp)) ? @file_get_contents($tmp) : false;
        if ($bytes === false || $bytes === '') {
            return ['success' => false, 'filename' => '', 'error' => 'empty'];
        }

        return bingo_save_voucher_bytes($bytes, $ext);
    }
}

if (!function_exists('bingo_voucher_sync_after_insert')) {
    /**
     * Si la BD truncó el nombre, renombra el archivo al valor guardado o corrige la columna.
     */
    function bingo_voucher_sync_after_insert(int $depositId, string $savedFilename): bool
    {
        if ($depositId < 1 || $savedFilename === '') {
            return false;
        }

        try {
            $model = new \App\Models\DepositsModel();
            $row = $model->find($depositId);
            if (! $row) {
                return false;
            }

            $stored = trim((string) ($row['voucher'] ?? ''));
            if ($stored !== '' && bingo_voucher_exists($stored)) {
                return true;
            }

            $resolved = bingo_voucher_resolve($savedFilename);
            if ($resolved === '') {
                return false;
            }

            // Si BD truncó el nombre, copiar/renombrar al valor truncado en public/uploads/vouchers
            if ($stored !== '' && $stored !== $savedFilename) {
                $safeStored = bingo_voucher_sanitize_name($stored);
                if ($safeStored !== '') {
                    $dir = bingo_voucher_public_dir();
                    $target = $dir . $safeStored;
                    if (! is_file($target) || @filesize($target) <= 0) {
                        @copy($resolved, $target);
                        @chmod($target, 0644);
                    }
                    if (bingo_voucher_exists($safeStored)) {
                        return true;
                    }
                }
            }

            // Corregir columna al nombre real del archivo
            $realName = basename($resolved);
            $model->update($depositId, ['voucher' => $realName]);

            return bingo_voucher_exists($realName);
        } catch (\Throwable $e) {
            log_message('error', 'bingo_voucher_sync_after_insert: ' . $e->getMessage());

            return false;
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

            // Asegurar que price acepte centavos (0.25, 0.50, etc.)
            if ($db->fieldExists('price', 'games')) {
                $priceField = null;
                foreach ($db->getFieldData('games') as $field) {
                    if (($field->name ?? '') === 'price') {
                        $priceField = $field;
                        break;
                    }
                }
                $priceType = strtolower((string) ($priceField->type ?? ''));
                if ($priceType !== '' && !preg_match('/decimal|numeric|float|double/', $priceType)) {
                    $db->query('ALTER TABLE `games` MODIFY `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00');
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo actualizar el esquema de games: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_roulette_carton_price')) {
    /** Precio de referencia histórico (salas clásicas de ruleta). */
    function bingo_roulette_carton_price(): float
    {
        return 0.25;
    }
}

if (!function_exists('bingo_roulette_max_carton_price')) {
    /** Precio de cartón permitido para cartones de ruleta (solo 0.25). */
    function bingo_roulette_max_carton_price(): float
    {
        return bingo_roulette_carton_price();
    }
}

if (!function_exists('bingo_price_allows_roulette_cartons')) {
    function bingo_price_allows_roulette_cartons(float $price): bool
    {
        return abs($price - bingo_roulette_carton_price()) < 0.001;
    }
}

if (!function_exists('bingo_roulette_default_prizes')) {
    /**
     * Premios por defecto (8 segmentos: 6 con cartones + 2 sin premio).
     *
     * @return list<array{label:string,cartons:int}>
     */
    function bingo_roulette_default_prizes(): array
    {
        return [
            ['label' => '1 CARTÓN', 'cartons' => 1],
            ['label' => '2 CARTONES', 'cartons' => 2],
            ['label' => '3 CARTONES', 'cartons' => 3],
            ['label' => '4 CARTONES', 'cartons' => 4],
            ['label' => '5 CARTONES', 'cartons' => 5],
            ['label' => '10 CARTONES', 'cartons' => 10],
            ['label' => 'INTENTA DE NUEVO', 'cartons' => 0],
            ['label' => 'SUERTE LA PRÓXIMA VEZ', 'cartons' => 0],
        ];
    }
}

if (!function_exists('bingo_normalize_roulette_prizes')) {
    /**
     * Normaliza y valida premios de ruleta (mín. 4, máx. 8).
     *
     * @param mixed $input
     * @return array{ok:bool,prizes:list<array{label:string,cartons:int}>,error:string}
     */
    function bingo_normalize_roulette_prizes($input): array
    {
        $rows = [];

        if (is_string($input)) {
            $decoded = json_decode($input, true);
            $input = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($input)) {
            return ['ok' => false, 'prizes' => [], 'error' => 'invalid'];
        }

        foreach ($input as $item) {
            if (! is_array($item)) {
                continue;
            }
            $label = trim((string) ($item['label'] ?? ''));
            $cartons = (int) ($item['cartons'] ?? 0);
            if ($label === '') {
                continue;
            }
            if ($cartons < 0) {
                $cartons = 0;
            }
            if ($cartons > 100) {
                $cartons = 100;
            }
            $rows[] = [
                'label' => mb_substr($label, 0, 40),
                'cartons' => $cartons,
            ];
        }

        $count = count($rows);
        if ($count < 4) {
            return ['ok' => false, 'prizes' => [], 'error' => 'min'];
        }
        if ($count > 8) {
            $rows = array_slice($rows, 0, 8);
        }

        // Debe haber al menos un premio con cartones > 0
        $hasWin = false;
        foreach ($rows as $row) {
            if ($row['cartons'] > 0) {
                $hasWin = true;
                break;
            }
        }
        if (! $hasWin) {
            return ['ok' => false, 'prizes' => [], 'error' => 'nowin'];
        }

        return ['ok' => true, 'prizes' => array_values($rows), 'error' => ''];
    }
}

if (!function_exists('bingo_roulette_prizes')) {
    /**
     * Premios activos de la ruleta (4–8).
     *
     * @return list<array{label:string,cartons:int}>
     */
    function bingo_roulette_prizes(): array
    {
        $raw = systemGet('roulettePrizes');
        if ($raw) {
            $normalized = bingo_normalize_roulette_prizes($raw);
            if ($normalized['ok']) {
                return $normalized['prizes'];
            }
        }

        return bingo_roulette_default_prizes();
    }
}

if (!function_exists('bingo_roulette_allowed_carton_amounts')) {
    /**
     * Cantidades de cartones que se pueden reclamar (sin ceros).
     *
     * @return list<int>
     */
    function bingo_roulette_allowed_carton_amounts(): array
    {
        $amounts = [];
        foreach (bingo_roulette_prizes() as $prize) {
            $n = (int) ($prize['cartons'] ?? 0);
            if ($n > 0) {
                $amounts[$n] = $n;
            }
        }

        return array_values($amounts);
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

        // Solo salas con cartón a 0.25
        return bingo_price_allows_roulette_cartons((float) ($game['price'] ?? 0));
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

            if (!$db->fieldExists('account_type', 'users')) {
                $forge->addColumn('users', [
                    'account_type' => [
                        'type' => 'VARCHAR',
                        'constraint' => 20,
                        'null' => true,
                        'default' => null,
                        'after' => 'account',
                    ],
                ]);
            }

            if (!$db->fieldExists('terms_accepted_at', 'users')) {
                $forge->addColumn('users', [
                    'terms_accepted_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'default' => null,
                        'after' => 'verified_email',
                    ],
                ]);
            }

            if (!$db->fieldExists('document_expires_at', 'users')) {
                $forge->addColumn('users', [
                    'document_expires_at' => [
                        'type' => 'DATE',
                        'null' => true,
                        'default' => null,
                        'after' => 'document',
                    ],
                ]);
            }

            if (!$db->fieldExists('last_ip', 'users')) {
                $forge->addColumn('users', [
                    'last_ip' => [
                        'type' => 'VARCHAR',
                        'constraint' => 64,
                        'null' => true,
                        'default' => null,
                        'after' => 'status',
                    ],
                ]);
            }

            if (!$db->fieldExists('last_mac', 'users')) {
                $forge->addColumn('users', [
                    'last_mac' => [
                        'type' => 'VARCHAR',
                        'constraint' => 64,
                        'null' => true,
                        'default' => null,
                        'after' => 'last_ip',
                    ],
                ]);
            }

            if (!$db->fieldExists('last_seen_at', 'users')) {
                $forge->addColumn('users', [
                    'last_seen_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                        'default' => null,
                        'after' => 'last_mac',
                    ],
                ]);
            }

            // Jugadores legacy sin flag: marcar verificados para no bloquear cuentas antiguas
            try {
                $db->query("UPDATE `users` SET `verified_email` = 1 WHERE `group` = 0 AND (`verified_email` IS NULL)");
            } catch (\Throwable $e) {
            }

            if (!$db->tableExists('carton_purchase_logs')) {
                $forge->addField([
                    'id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'auto_increment' => true,
                    ],
                    'user_id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                    ],
                    'game_id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                        'default' => null,
                    ],
                    'cartons_count' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'default' => 0,
                    ],
                    'amount' => [
                        'type' => 'DECIMAL',
                        'constraint' => '12,2',
                        'default' => 0,
                    ],
                    'from_bonus' => [
                        'type' => 'DECIMAL',
                        'constraint' => '12,2',
                        'default' => 0,
                    ],
                    'from_recharge' => [
                        'type' => 'DECIMAL',
                        'constraint' => '12,2',
                        'default' => 0,
                    ],
                    'from_withdraw' => [
                        'type' => 'DECIMAL',
                        'constraint' => '12,2',
                        'default' => 0,
                    ],
                    'source' => [
                        'type' => 'VARCHAR',
                        'constraint' => 20,
                        'default' => 'wallet',
                    ],
                    'roulette_id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                        'default' => null,
                    ],
                    'created_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                    'updated_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                ]);
                $forge->addKey('id', true);
                $forge->addKey('user_id');
                $forge->addKey('game_id');
                $forge->createTable('carton_purchase_logs', true);
            }

            if (function_exists('bingo_ensure_cartons_pay_source_column')) {
                bingo_ensure_cartons_pay_source_column();
            }
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo actualizar el esquema de users: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_ensure_retires_schema')) {
    function bingo_ensure_retires_schema(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            $db = \Config\Database::connect();
            if (!$db->tableExists('retires')) {
                return;
            }

            $forge = \Config\Database::forge();

            if (!$db->fieldExists('account_type', 'retires')) {
                $forge->addColumn('retires', [
                    'account_type' => [
                        'type' => 'VARCHAR',
                        'constraint' => 20,
                        'null' => true,
                        'default' => null,
                        'after' => 'account',
                    ],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo actualizar el esquema de retires: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_normalize_account_type')) {
    function bingo_normalize_account_type(?string $type): string
    {
        $type = strtolower(trim((string) $type));
        if (in_array($type, ['savings', 'ahorros', 'ahorro'], true)) {
            return 'savings';
        }
        if (in_array($type, ['checking', 'corriente', 'current'], true)) {
            return 'checking';
        }

        return '';
    }
}

if (!function_exists('bingo_account_type_label')) {
    function bingo_account_type_label(?string $type): string
    {
        $normalized = bingo_normalize_account_type($type);
        if ($normalized === 'savings') {
            return translate('savings account');
        }
        if ($normalized === 'checking') {
            return translate('checking account');
        }

        return '';
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

            $operatorCommissionRow = $db->table('system')->where('key', 'rateOperatorCommission')->get()->getRowArray();
            $storeGgrRow = $db->table('system')->where('key', 'rateStoreGgrCommission')->get()->getRowArray();
            $legacyOperatorGgr = (string) ($operatorCommissionRow['value'] ?? '0');
            $legacyStoreGgr = (string) ($storeGgrRow['value'] ?? '0');
            $commissionDefaults = [
                // Ticket Retail ya no se configura: GGR usa tasas de afiliados
                'rateOperatorGgrAffiliate' => $legacyOperatorGgr,
                'rateOperatorGgrRetail' => $legacyOperatorGgr,
                'rateOperatorRecharge' => '0',
                'rateOperatorWithdraw' => '0',
                'rateStoreGgrAffiliate' => $legacyStoreGgr,
            ];
            foreach ($commissionDefaults as $commissionKey => $commissionDefault) {
                if ($db->table('system')->where('key', $commissionKey)->countAllResults() === 0) {
                    $db->table('system')->insert([
                        'key' => $commissionKey,
                        'value' => $commissionDefault,
                    ]);
                }
            }

            // Migrar valores legacy Ticket Retail → Afiliados si afiliados quedó en 0
            $affiliateOpRow = $db->table('system')->where('key', 'rateOperatorGgrAffiliate')->get()->getRowArray();
            if ($affiliateOpRow && (float) ($affiliateOpRow['value'] ?? 0) <= 0 && (float) $legacyOperatorGgr > 0) {
                $db->table('system')->where('key', 'rateOperatorGgrAffiliate')->update(['value' => $legacyOperatorGgr]);
            }
            $affiliateStoreRow = $db->table('system')->where('key', 'rateStoreGgrAffiliate')->get()->getRowArray();
            if ($affiliateStoreRow && (float) ($affiliateStoreRow['value'] ?? 0) <= 0 && (float) $legacyStoreGgr > 0) {
                $db->table('system')->where('key', 'rateStoreGgrAffiliate')->update(['value' => $legacyStoreGgr]);
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

            // TyC / promociones: value puede ser HTML largo
            try {
                $db->query('ALTER TABLE `system` MODIFY COLUMN `value` MEDIUMTEXT NULL');
            } catch (\Throwable $e) {
                // Algunos hosts no permiten MODIFY
            }

            $legalDefaults = [
                'termsHtml' => bingo_default_terms_html(),
                'promotionsHtml' => bingo_default_promotions_html(),
                'termsRequireAccept' => '1',
                'termsUpdatedAt' => date('Y-m-d H:i:s'),
            ];
            foreach ($legalDefaults as $key => $value) {
                if ($db->table('system')->where('key', $key)->countAllResults() === 0) {
                    $db->table('system')->insert([
                        'key' => $key,
                        'value' => $value,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo actualizar el esquema de system: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_default_terms_html')) {
    function bingo_default_terms_html(): string
    {
        return '<h2>Términos y Condiciones</h2>'
            . '<p>Bienvenido a ' . esc(systemGet('name') ?: 'Rey Bingo') . '. Al crear una cuenta y utilizar la plataforma, usted acepta estos términos.</p>'
            . '<h3>1. Cuenta de usuario</h3>'
            . '<p>Usted es responsable de la confidencialidad de sus credenciales y de toda actividad realizada desde su cuenta.</p>'
            . '<h3>2. Depósitos y retiros</h3>'
            . '<p>Los depósitos y retiros están sujetos a verificación. La plataforma puede solicitar comprobantes o documentación adicional.</p>'
            . '<h3>3. Juego responsable</h3>'
            . '<p>El servicio está dirigido a mayores de edad. Juegue de forma responsable y dentro de sus posibilidades.</p>'
            . '<h3>4. Cartones y partidas</h3>'
            . '<p>La compra de cartones, el inicio de partidas y la asignación de premios se rigen por las reglas publicadas en cada juego.</p>'
            . '<h3>5. Modificaciones</h3>'
            . '<p>Nos reservamos el derecho de actualizar estos términos. El uso continuado de la plataforma implica la aceptación de los cambios.</p>';
    }
}

if (!function_exists('bingo_default_promotions_html')) {
    function bingo_default_promotions_html(): string
    {
        return '<h2>Promociones</h2>'
            . '<p>Conozca las promociones vigentes de ' . esc(systemGet('name') ?: 'Rey Bingo') . '.</p>'
            . '<h3>Bono de registro</h3>'
            . '<p>Los nuevos jugadores pueden recibir un bono de bienvenida según la configuración activa del sistema.</p>'
            . '<h3>Ruleta y premios especiales</h3>'
            . '<p>Algunas promociones otorgan cartones o premios adicionales. Las condiciones específicas se indicarán en cada campaña.</p>'
            . '<h3>Condiciones generales</h3>'
            . '<ul>'
            . '<li>Las promociones no son acumulables salvo indicación expresa.</li>'
            . '<li>La plataforma puede modificar o finalizar una promoción en cualquier momento.</li>'
            . '<li>Uso indebido o fraude puede anular el beneficio.</li>'
            . '</ul>';
    }
}

if (!function_exists('bingo_sanitize_legal_html')) {
    function bingo_sanitize_legal_html(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        // Quitar scripts/embeds peligrosos; el admin edita el resto del HTML
        $html = preg_replace('#<\s*(script|iframe|object|embed|link|meta)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html) ?? $html;
        $html = preg_replace('#<\s*(script|iframe|object|embed|link|meta)[^>]*/?>#is', '', $html) ?? $html;
        $html = preg_replace('/\son\w+\s*=\s*("|\').*?\1/iu', '', $html) ?? $html;
        $html = preg_replace('/\s(href|src)\s*=\s*("|\')\s*javascript:[^"\']*\2/iu', '', $html) ?? $html;

        return $html;
    }
}

if (!function_exists('bingo_legal_html')) {
    function bingo_legal_html(string $key): string
    {
        $allowed = ['termsHtml', 'promotionsHtml'];
        if (! in_array($key, $allowed, true)) {
            return '';
        }

        if (function_exists('bingo_ensure_system_settings_schema')) {
            bingo_ensure_system_settings_schema();
        }

        $html = trim((string) (systemGet($key) ?? ''));
        if ($html === '') {
            $html = $key === 'promotionsHtml' ? bingo_default_promotions_html() : bingo_default_terms_html();
        }

        return bingo_sanitize_legal_html($html);
    }
}

if (!function_exists('bingo_terms_require_accept')) {
    function bingo_terms_require_accept(): bool
    {
        if (function_exists('bingo_ensure_system_settings_schema')) {
            bingo_ensure_system_settings_schema();
        }

        return (string) (systemGet('termsRequireAccept') ?? '1') === '1';
    }
}

if (!function_exists('bingo_user_needs_terms_accept')) {
    /**
     * Jugadores (group 0) que aún no aceptaron TyC, o cuya aceptación
     * es anterior a la última actualización del texto legal.
     */
    function bingo_user_needs_terms_accept(?array $user = null): bool
    {
        if (! bingo_terms_require_accept()) {
            return false;
        }

        if ($user === null && session()->get('logged_in')) {
            $model = new \App\Models\UsersModel();
            $user = $model->find(session()->get('id'));
        }

        if (! is_array($user) || $user === []) {
            return false;
        }

        if ((int) ($user['group'] ?? -1) !== 0) {
            return false;
        }

        if (function_exists('bingo_ensure_users_schema')) {
            bingo_ensure_users_schema();
        }

        $acceptedAt = trim((string) ($user['terms_accepted_at'] ?? ''));
        if ($acceptedAt === '' || $acceptedAt === '0000-00-00 00:00:00') {
            return true;
        }

        $updatedAt = trim((string) (systemGet('termsUpdatedAt') ?: ''));
        if ($updatedAt === '') {
            return false;
        }

        $acceptedTs = strtotime($acceptedAt);
        $updatedTs = strtotime($updatedAt);
        if ($acceptedTs === false || $updatedTs === false) {
            return false;
        }

        return $acceptedTs < $updatedTs;
    }
}

if (!function_exists('bingo_mark_terms_accepted')) {
    function bingo_mark_terms_accepted(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        if (function_exists('bingo_ensure_users_schema')) {
            bingo_ensure_users_schema();
        }

        $model = new \App\Models\UsersModel();

        return (bool) $model->update($userId, [
            'terms_accepted_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

if (!function_exists('bingo_log_carton_purchase')) {
    /**
     * @param array{from_bonus?:float,from_recharge?:float,from_withdraw?:float} $split
     */
    function bingo_log_carton_purchase(
        int $userId,
        int $gameId,
        int $cartonsCount,
        float $amount,
        array $split = [],
        string $source = 'wallet',
        ?int $rouletteId = null
    ): void {
        if ($userId <= 0 || $cartonsCount <= 0) {
            return;
        }

        if (function_exists('bingo_ensure_users_schema')) {
            bingo_ensure_users_schema();
        }

        $fromBonus = round((float) ($split['from_bonus'] ?? 0), 2);
        $fromRecharge = round((float) ($split['from_recharge'] ?? 0), 2);
        $fromWithdraw = round((float) ($split['from_withdraw'] ?? 0), 2);
        $amount = round($amount, 2);

        // Compra 100% con bono → source explícito "bonus" (antes quedaba como wallet/dinero real)
        if ($source === 'wallet' && $fromBonus > 0 && $fromRecharge <= 0 && $fromWithdraw <= 0) {
            $source = 'bonus';
        }

        try {
            $model = new \App\Models\CartonPurchaseLogsModel();
            $model->insert([
                'user_id' => $userId,
                'game_id' => $gameId > 0 ? $gameId : null,
                'cartons_count' => $cartonsCount,
                'amount' => $amount,
                'from_bonus' => $fromBonus,
                'from_recharge' => $fromRecharge,
                'from_withdraw' => $fromWithdraw,
                'source' => in_array($source, ['wallet', 'roulette', 'bonus'], true) ? $source : 'wallet',
                'roulette_id' => $rouletteId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'bingo_log_carton_purchase: ' . $e->getMessage());
        }
    }
}

if (!function_exists('bingo_classify_purchase_source')) {
    /**
     * Clasifica el origen de pago de un log o split.
     * Mixed SOLO = Recarga + Retiro. Bono nunca combina con otros saldos.
     *
     * @param array{source?:string,from_bonus?:float,from_recharge?:float,from_withdraw?:float,amount?:float} $row
     */
    function bingo_classify_purchase_source(array $row): string
    {
        $source = strtolower(trim((string) ($row['source'] ?? 'wallet')));
        $fromBonus = round((float) ($row['from_bonus'] ?? 0), 2);
        $fromRecharge = round((float) ($row['from_recharge'] ?? 0), 2);
        $fromWithdraw = round((float) ($row['from_withdraw'] ?? 0), 2);

        if ($source === 'roulette') {
            return 'roulette';
        }
        if ($source === 'wallet_legacy') {
            return 'wallet_legacy';
        }

        // Preferir montos reales del split sobre el string source
        if ($fromBonus > 0) {
            return 'bonus';
        }
        if ($fromRecharge > 0 && $fromWithdraw > 0) {
            return 'mixed';
        }
        if ($fromRecharge > 0) {
            return 'recharge';
        }
        if ($fromWithdraw > 0) {
            return 'withdraw';
        }

        if (in_array($source, ['bonus', 'recharge', 'withdraw', 'mixed', 'real'], true)) {
            return $source === 'real' ? 'recharge' : $source;
        }

        return 'recharge';
    }
}

if (!function_exists('bingo_purchase_source_label')) {
    function bingo_purchase_source_label(string $sourceKey): string
    {
        return match ($sourceKey) {
            'roulette' => translate('roulette cartons'),
            'bonus' => 'Saldo Bono',
            'recharge' => 'Saldo Recarga',
            'withdraw' => 'Saldo Retiro',
            'mixed' => 'Mixed (Recarga + Retiro)',
            'wallet_legacy' => translate('wallet historical'),
            'real' => 'Saldo Recarga',
            default => 'Saldo Recarga',
        };
    }
}

if (!function_exists('bingo_build_user_carton_purchase_report')) {
    /**
     * Historial admin: un renglón por cartón del usuario con origen de pago, resultado y saldo acreditado.
     *
     * @return list<array<string,mixed>>
     */
    function bingo_build_user_carton_purchase_report(int $userId, int $limit = 500): array
    {
        if ($userId <= 0) {
            return [];
        }

        helper(['bingo', 'wallet']);
        if (function_exists('bingo_ensure_users_schema')) {
            bingo_ensure_users_schema();
        }

        $modelCartons = new \App\Models\CartonsModel();
        $modelGames = new \App\Models\GamesModel();
        $modelSings = new \App\Models\SingsModel();
        $modelPayments = new \App\Models\PaymentsModel();
        $modelPurchaseLogs = new \App\Models\CartonPurchaseLogsModel();
        $modelModalities = new \App\Models\ModalitiesModel();

        $cartons = $modelCartons
            ->where('user', $userId)
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit);

        $purchaseLogs = $modelPurchaseLogs
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->findAll(1000);

        $logsByGame = [];
        foreach ($purchaseLogs as $log) {
            $gid = (int) ($log['game_id'] ?? 0);
            if ($gid <= 0) {
                continue;
            }
            $logsByGame[$gid][] = $log;
        }

        $sings = $modelSings
            ->where('user', $userId)
            ->whereIn('status', [1, 2])
            ->findAll();

        $singsByCarton = [];
        $singIds = [];
        foreach ($sings as $sing) {
            $cid = (int) ($sing['carton'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            $singsByCarton[$cid][] = $sing;
            $singIds[] = (int) $sing['id'];
        }

        $paymentsBySing = [];
        if ($singIds !== []) {
            $awardPayments = $modelPayments
                ->where('user', $userId)
                ->where('type', 'award')
                ->whereIn('type_id', $singIds)
                ->findAll();
            foreach ($awardPayments as $pay) {
                $sid = (int) ($pay['type_id'] ?? 0);
                if ($sid <= 0) {
                    continue;
                }
                if (! isset($paymentsBySing[$sid])) {
                    $paymentsBySing[$sid] = 0.0;
                }
                $paymentsBySing[$sid] += (float) ($pay['amount'] ?? 0);
            }
        }

        $gameCache = [];
        $modalityCache = [];
        $rows = [];
        $coveredGameIds = [];

        $pickNearestPurchaseLog = static function (array $gameLogs, string $cartonCreatedAt): ?array {
            if ($gameLogs === []) {
                return null;
            }
            $cartonTs = strtotime($cartonCreatedAt) ?: 0;
            $best = null;
            $bestDiff = PHP_INT_MAX;
            foreach ($gameLogs as $log) {
                $logTs = strtotime((string) ($log['created_at'] ?? '')) ?: 0;
                $diff = abs($cartonTs - $logTs);
                if ($diff < $bestDiff) {
                    $bestDiff = $diff;
                    $best = $log;
                }
            }
            // Preferir log cercano; si hay uno solo, usarlo siempre
            if (count($gameLogs) === 1) {
                return $gameLogs[0];
            }
            if ($best !== null && $bestDiff <= 300) {
                return $best;
            }

            return $best;
        };

        $splitFromSource = static function (string $sourceKey, float $unitCost, ?array $log): array {
            if ($sourceKey === 'roulette') {
                return ['bonus' => 0.0, 'recharge' => 0.0, 'withdraw' => 0.0, 'amount' => 0.0];
            }

            $count = max(1, (int) ($log['cartons_count'] ?? 1));
            if ($log !== null) {
                return [
                    'bonus' => round(((float) ($log['from_bonus'] ?? 0)) / $count, 2),
                    'recharge' => round(((float) ($log['from_recharge'] ?? 0)) / $count, 2),
                    'withdraw' => round(((float) ($log['from_withdraw'] ?? 0)) / $count, 2),
                    'amount' => round(((float) ($log['amount'] ?? 0)) / $count, 2),
                ];
            }

            return match ($sourceKey) {
                'bonus' => ['bonus' => $unitCost, 'recharge' => 0.0, 'withdraw' => 0.0, 'amount' => $unitCost],
                'withdraw' => ['bonus' => 0.0, 'recharge' => 0.0, 'withdraw' => $unitCost, 'amount' => $unitCost],
                'mixed' => ['bonus' => 0.0, 'recharge' => round($unitCost / 2, 2), 'withdraw' => round($unitCost / 2, 2), 'amount' => $unitCost],
                default => ['bonus' => 0.0, 'recharge' => $unitCost, 'withdraw' => 0.0, 'amount' => $unitCost],
            };
        };

        foreach ($cartons as $carton) {
            $cartonId = (int) ($carton['id'] ?? 0);
            $gameId = (int) ($carton['game'] ?? 0);
            $coveredGameIds[$gameId] = true;

            if ($gameId > 0 && ! isset($gameCache[$gameId])) {
                $gameCache[$gameId] = $modelGames->find($gameId);
            }
            $game = $gameId > 0 ? ($gameCache[$gameId] ?? null) : null;
            $gamePrice = (float) ($game['price'] ?? 0);
            $gameStatus = (int) ($game['status'] ?? 0);

            $gameLogs = $logsByGame[$gameId] ?? [];
            $nearestLog = $pickNearestPurchaseLog($gameLogs, (string) ($carton['created_at'] ?? ''));

            $storedSource = strtolower(trim((string) ($carton['pay_source'] ?? '')));
            if ($nearestLog !== null) {
                // El log manda (corrige "mixed" antiguo bono+recarga)
                $sourceKey = bingo_classify_purchase_source($nearestLog);
                $split = $splitFromSource($sourceKey, $gamePrice, $nearestLog);
                // Si el split tenía bono+otro (legacy), normalizar a solo bono en columnas
                if ($sourceKey === 'bonus' && ((float) $split['recharge'] > 0 || (float) $split['withdraw'] > 0)) {
                    $split['bonus'] = round((float) $split['bonus'] + (float) $split['recharge'] + (float) $split['withdraw'], 2);
                    $split['recharge'] = 0.0;
                    $split['withdraw'] = 0.0;
                }
                if ($sourceKey === 'roulette') {
                    $unitCost = 0.0;
                } else {
                    $unitCost = (float) $split['amount'] > 0 ? (float) $split['amount'] : $gamePrice;
                }
                $unitBonus = (float) $split['bonus'];
                $unitRecharge = (float) $split['recharge'];
                $unitWithdraw = (float) $split['withdraw'];
            } elseif (in_array($storedSource, ['bonus', 'real', 'recharge', 'withdraw', 'roulette', 'mixed'], true)) {
                $sourceKey = $storedSource === 'real' ? 'recharge' : $storedSource;
                // Tag "mixed" antiguo ambiguo → no asumir Recarga+Retiro sin log
                if ($sourceKey === 'mixed') {
                    $sourceKey = 'recharge';
                }
                $split = $splitFromSource($sourceKey, $gamePrice, null);
                $unitCost = $sourceKey === 'roulette' ? 0.0 : $gamePrice;
                $unitBonus = (float) $split['bonus'];
                $unitRecharge = (float) $split['recharge'];
                $unitWithdraw = (float) $split['withdraw'];
            } else {
                $sourceKey = 'wallet_legacy';
                $unitCost = $gamePrice;
                $unitBonus = 0.0;
                $unitRecharge = $gamePrice;
                $unitWithdraw = 0.0;
            }

            $cartonSings = $singsByCarton[$cartonId] ?? [];
            $prizeAmount = 0.0;
            $modalityNames = [];
            foreach ($cartonSings as $sing) {
                $sid = (int) ($sing['id'] ?? 0);
                $prizeAmount += (float) ($paymentsBySing[$sid] ?? 0);
                $mid = (int) ($sing['modality'] ?? 0);
                if ($mid > 0) {
                    if (! isset($modalityCache[$mid])) {
                        $modalityCache[$mid] = $modelModalities->find($mid);
                    }
                    $mod = $modalityCache[$mid] ?? null;
                    if ($mod) {
                        $modalityNames[] = translate($mod['name'] ?? '');
                    }
                }
            }
            $prizeAmount = round($prizeAmount, 2);
            $won = $cartonSings !== [];

            if ($won) {
                $resultKey = 'won';
                $resultLabel = 'Ganó';
            } elseif ($gameStatus === 0) {
                $resultKey = 'lost';
                $resultLabel = 'Perdió';
            } else {
                $resultKey = 'pending';
                $resultLabel = 'En juego';
            }

            $creditKey = '';
            $creditLabel = '—';
            if ($won && $prizeAmount > 0 && $gameId > 0) {
                $credit = bingo_resolve_award_credit_split(
                    $userId,
                    $gameId,
                    max($prizeAmount, 0.01),
                    $cartonId
                );
                if ((float) ($credit['to_withdraw'] ?? 0) > 0 && (float) ($credit['to_recharge'] ?? 0) <= 0) {
                    $creditKey = 'withdraw';
                    $creditLabel = translate('withdraw balance');
                } else {
                    $creditKey = 'recharge';
                    $creditLabel = translate('recharge balance');
                }
            } elseif ($won && $prizeAmount <= 0) {
                $creditLabel = translate('pending');
            }

            $rows[] = [
                'id' => $cartonId,
                'serial' => $carton['serial'] ?? ('#' . $cartonId),
                'created_at' => $carton['created_at'] ?? '',
                'game_id' => $gameId,
                'game' => $game['description'] ?? ('#' . ($gameId ?: '-')),
                'cartons_count' => 1,
                'amount' => $unitCost,
                'from_bonus' => $unitBonus ?? 0.0,
                'from_recharge' => $unitRecharge ?? 0.0,
                'from_withdraw' => $unitWithdraw ?? 0.0,
                'source' => $sourceKey,
                'source_label' => bingo_purchase_source_label($sourceKey),
                'result' => $resultKey,
                'result_label' => $resultLabel,
                'prize_amount' => $prizeAmount,
                'modality' => implode(', ', array_unique(array_filter($modalityNames))),
                'credit_wallet' => $creditKey,
                'credit_label' => $creditLabel,
            ];
        }

        // Compras registradas sin cartón visible (p. ej. borrados): conservar el log agregado
        foreach ($purchaseLogs as $log) {
            $gid = (int) ($log['game_id'] ?? 0);
            if ($gid > 0 && isset($coveredGameIds[$gid])) {
                continue;
            }
            // Solo agregar logs de juegos sin ningún cartón del usuario
            if ($gid > 0) {
                $alreadyListed = false;
                foreach ($rows as $existing) {
                    if ((int) ($existing['game_id'] ?? 0) === $gid) {
                        $alreadyListed = true;
                        break;
                    }
                }
                if ($alreadyListed) {
                    continue;
                }
            }

            if ($gid > 0 && ! isset($gameCache[$gid])) {
                $gameCache[$gid] = $modelGames->find($gid);
            }
            $game = $gid > 0 ? ($gameCache[$gid] ?? null) : null;
            $sourceKey = bingo_classify_purchase_source($log);
            $gameStatus = (int) ($game['status'] ?? 0);

            $gameSings = [];
            $prizeAmount = 0.0;
            $modalityNames = [];
            if ($gid > 0) {
                foreach ($sings as $sing) {
                    if ((int) ($sing['game'] ?? 0) !== $gid) {
                        continue;
                    }
                    $gameSings[] = $sing;
                    $sid = (int) ($sing['id'] ?? 0);
                    $prizeAmount += (float) ($paymentsBySing[$sid] ?? 0);
                    $mid = (int) ($sing['modality'] ?? 0);
                    if ($mid > 0) {
                        if (! isset($modalityCache[$mid])) {
                            $modalityCache[$mid] = $modelModalities->find($mid);
                        }
                        $mod = $modalityCache[$mid] ?? null;
                        if ($mod) {
                            $modalityNames[] = translate($mod['name'] ?? '');
                        }
                    }
                }
            }
            $prizeAmount = round($prizeAmount, 2);
            $won = $gameSings !== [];
            if ($won) {
                $resultKey = 'won';
                $resultLabel = 'Ganó';
            } elseif ($gameStatus === 0) {
                $resultKey = 'lost';
                $resultLabel = 'Perdió';
            } elseif ($gid > 0) {
                $resultKey = 'pending';
                $resultLabel = 'En juego';
            } else {
                $resultKey = 'unknown';
                $resultLabel = '—';
            }

            $creditKey = '';
            $creditLabel = '—';
            if ($won && $prizeAmount > 0 && $gid > 0) {
                $credit = bingo_resolve_award_credit_split($userId, $gid, max($prizeAmount, 0.01));
                if ((float) ($credit['to_withdraw'] ?? 0) > 0 && (float) ($credit['to_recharge'] ?? 0) <= 0) {
                    $creditKey = 'withdraw';
                    $creditLabel = translate('withdraw balance');
                } else {
                    $creditKey = 'recharge';
                    $creditLabel = translate('recharge balance');
                }
            }

            $rows[] = [
                'id' => 'L' . ($log['id'] ?? ''),
                'serial' => '—',
                'created_at' => $log['created_at'] ?? '',
                'game_id' => $gid,
                'game' => $game['description'] ?? ('#' . ($gid ?: '-')),
                'cartons_count' => (int) ($log['cartons_count'] ?? 0),
                'amount' => (float) ($log['amount'] ?? 0),
                'from_bonus' => (float) ($log['from_bonus'] ?? 0),
                'from_recharge' => (float) ($log['from_recharge'] ?? 0),
                'from_withdraw' => (float) ($log['from_withdraw'] ?? 0),
                'source' => $sourceKey,
                'source_label' => bingo_purchase_source_label($sourceKey),
                'result' => $resultKey,
                'result_label' => $resultLabel,
                'prize_amount' => $prizeAmount,
                'modality' => implode(', ', array_unique(array_filter($modalityNames))),
                'credit_wallet' => $creditKey,
                'credit_label' => $creditLabel,
            ];
            if ($gid > 0) {
                $coveredGameIds[$gid] = true;
            }
        }

        usort($rows, static function ($a, $b) {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        return $rows;
    }
}

if (!function_exists('bingo_status_label_short')) {
    function bingo_status_label_short($status): string
    {
        return match ((int) $status) {
            2 => 'Aprobado',
            1 => 'Pendiente',
            0 => 'Rechazado',
            default => (string) $status,
        };
    }
}

if (!function_exists('bingo_build_user_movements_ledger')) {
    /**
     * Historial cronológico unificado de movimientos del jugador (admin).
     * Incluye: recargas, retiros, compras de cartón, resultados, premios, bonos, ruleta, transferencias.
     *
     * @return list<array<string,mixed>>
     */
    function bingo_build_user_movements_ledger(int $userId, int $limit = 2000): array
    {
        if ($userId <= 0) {
            return [];
        }

        helper(['bingo', 'wallet']);
        if (function_exists('bingo_ensure_users_schema')) {
            bingo_ensure_users_schema();
        }

        $modelUsers = new \App\Models\UsersModel();
        $targetUser = $modelUsers->find($userId);
        if ($targetUser) {
            $userGroup = (int) ($targetUser['group'] ?? -1);
            if ($userGroup === bingo_group_operator()) {
                $opLedger = bingo_build_operator_movements_ledger($userId);
                return $opLedger['movements'] ?? [];
            }
            if ($userGroup === bingo_group_store()) {
                $stLedger = bingo_build_store_movements_ledger($userId);
                return $stLedger['movements'] ?? [];
            }
        }

        $rows = [];
        $push = static function (array $row) use (&$rows): void {
            $rows[] = array_merge([
                'datetime' => '',
                'type' => '',
                'type_label' => '',
                'direction' => '',
                'amount' => 0.0,
                'status' => '',
                'status_label' => '',
                'game' => '',
                'game_id' => 0,
                'carton_serial' => '',
                'carton_id' => 0,
                'modality' => '',
                'result' => '',
                'result_label' => '',
                'prize_amount' => 0.0,
                'source' => '',
                'source_label' => '',
                'from_bonus' => 0.0,
                'from_recharge' => 0.0,
                'from_withdraw' => 0.0,
                'credit_wallet' => '',
                'credit_label' => '',
                'detail' => '',
                'ref_table' => '',
                'ref_id' => 0,
                'balance_after' => null,
            ], $row);
        };

        $modelDeposits = new \App\Models\DepositsModel();
        $modelRetires = new \App\Models\RetiresModel();
        $modelPayments = new \App\Models\PaymentsModel();
        $modelGames = new \App\Models\GamesModel();
        $modelSings = new \App\Models\SingsModel();
        $modelModalities = new \App\Models\ModalitiesModel();
        $modelRoulettes = new \App\Models\RoulettesModel();
        $modelTransfers = new \App\Models\TransfersModel();

        $gameCache = [];
        $resolveGame = static function (int $gameId) use (&$gameCache, $modelGames): string {
            if ($gameId <= 0) {
                return '';
            }
            if (! isset($gameCache[$gameId])) {
                $gameCache[$gameId] = $modelGames->find($gameId);
            }
            $g = $gameCache[$gameId] ?? null;

            return (string) ($g['description'] ?? ('#' . $gameId));
        };

        // Recargas / depósitos
        foreach ($modelDeposits->where('user', $userId)->orderBy('date', 'DESC')->findAll(800) as $d) {
            $st = (int) ($d['status'] ?? 0);
            $push([
                'datetime' => (string) ($d['date'] ?? $d['created_at'] ?? ''),
                'type' => 'deposit',
                'type_label' => 'Recarga',
                'direction' => '+',
                'amount' => round((float) ($d['amount'] ?? 0), 2),
                'status' => $st,
                'status_label' => bingo_status_label_short($st),
                'source' => 'recharge',
                'source_label' => 'Saldo Recarga',
                'detail' => trim(
                    'Método: ' . (string) ($d['method'] ?? $d['bank'] ?? '-')
                    . ' | Ref: ' . (string) ($d['reference'] ?? '-')
                ),
                'ref_table' => 'deposits',
                'ref_id' => (int) ($d['id'] ?? 0),
            ]);
        }

        // Retiros
        foreach ($modelRetires->where('user', $userId)->orderBy('created_at', 'DESC')->findAll(800) as $r) {
            $st = (int) ($r['status'] ?? 0);
            $push([
                'datetime' => (string) ($r['created_at'] ?? ''),
                'type' => 'retire',
                'type_label' => 'Retiro',
                'direction' => '-',
                'amount' => round((float) ($r['amount'] ?? 0), 2),
                'status' => $st,
                'status_label' => bingo_status_label_short($st),
                'source' => 'withdraw',
                'source_label' => 'Saldo Retiro',
                'detail' => trim(
                    'Banco: ' . (string) ($r['bank'] ?? '-')
                    . ' | Cuenta: ' . (string) ($r['account'] ?? '-')
                    . (! empty($r['observation']) ? ' | ' . $r['observation'] : '')
                ),
                'ref_table' => 'retires',
                'ref_id' => (int) ($r['id'] ?? 0),
            ]);
        }

        // Compras de cartones + resultado (ganó / perdió / en juego)
        $purchases = bingo_build_user_carton_purchase_report($userId, 1500);
        foreach ($purchases as $p) {
            $amount = round((float) ($p['amount'] ?? 0), 2);
            $isRoulette = ((string) ($p['source'] ?? '')) === 'roulette' || $amount <= 0;
            $push([
                'datetime' => (string) ($p['created_at'] ?? ''),
                'type' => 'purchase',
                'type_label' => $isRoulette ? 'Compra cartón (ruleta)' : 'Compra cartón',
                'direction' => $isRoulette ? '=' : '-',
                'amount' => $amount,
                'status' => 2,
                'status_label' => 'Registrado',
                'game' => (string) ($p['game'] ?? ''),
                'game_id' => (int) ($p['game_id'] ?? 0),
                'carton_serial' => (string) ($p['serial'] ?? ''),
                'carton_id' => (int) ($p['id'] ?? 0),
                'modality' => (string) ($p['modality'] ?? ''),
                'result' => (string) ($p['result'] ?? ''),
                'result_label' => (string) ($p['result_label'] ?? ''),
                'prize_amount' => round((float) ($p['prize_amount'] ?? 0), 2),
                'source' => (string) ($p['source'] ?? ''),
                'source_label' => (string) ($p['source_label'] ?? ''),
                'from_bonus' => round((float) ($p['from_bonus'] ?? 0), 2),
                'from_recharge' => round((float) ($p['from_recharge'] ?? 0), 2),
                'from_withdraw' => round((float) ($p['from_withdraw'] ?? 0), 2),
                'credit_wallet' => (string) ($p['credit_wallet'] ?? ''),
                'credit_label' => (string) ($p['credit_label'] ?? ''),
                'detail' => trim(
                    'Serie: ' . (string) ($p['serial'] ?? '-')
                    . ' | Origen: ' . (string) ($p['source_label'] ?? '-')
                    . ' | Resultado: ' . (string) ($p['result_label'] ?? '-')
                    . (((float) ($p['prize_amount'] ?? 0) > 0)
                        ? ' | Premio: ' . number_format((float) $p['prize_amount'], 2)
                          . ' → ' . (string) ($p['credit_label'] ?? '-')
                        : '')
                ),
                'ref_table' => 'cartons',
                'ref_id' => (int) ($p['id'] ?? 0),
            ]);
        }

        // Premios / bonos / referidos (payments)
        $modalityCache = [];
        $payments = $modelPayments
            ->where('user', $userId)
            ->orderBy('created_at', 'DESC')
            ->findAll(1000);

        foreach ($payments as $pay) {
            $ptype = strtolower(trim((string) ($pay['type'] ?? '')));
            $st = (int) ($pay['status'] ?? 0);
            $amount = round((float) ($pay['amount'] ?? 0), 2);
            $typeId = (int) ($pay['type_id'] ?? 0);

            if ($ptype === 'award') {
                $sing = $typeId > 0 ? $modelSings->find($typeId) : null;
                $gameId = (int) ($sing['game'] ?? 0);
                $cartonId = (int) ($sing['carton'] ?? 0);
                $mid = (int) ($sing['modality'] ?? 0);
                $modName = '';
                if ($mid > 0) {
                    if (! isset($modalityCache[$mid])) {
                        $modalityCache[$mid] = $modelModalities->find($mid);
                    }
                    $modName = (string) (($modalityCache[$mid]['name'] ?? '') ?: $mid);
                    if (function_exists('translate')) {
                        $modName = translate($modName);
                    }
                }
                $serial = '';
                if ($cartonId > 0) {
                    $carton = (new \App\Models\CartonsModel())->find($cartonId);
                    $serial = (string) ($carton['serial'] ?? ('#' . $cartonId));
                }
                $credit = '';
                $creditLabel = '';
                if ($gameId > 0 && $amount > 0) {
                    $split = bingo_resolve_award_credit_split($userId, $gameId, $amount, $cartonId > 0 ? $cartonId : null);
                    if ((float) ($split['to_withdraw'] ?? 0) > 0 && (float) ($split['to_recharge'] ?? 0) <= 0) {
                        $credit = 'withdraw';
                        $creditLabel = 'Saldo Retiro';
                    } else {
                        $credit = 'recharge';
                        $creditLabel = 'Saldo Recarga';
                    }
                }
                $push([
                    'datetime' => (string) ($pay['created_at'] ?? ''),
                    'type' => 'prize',
                    'type_label' => 'Premio ganado',
                    'direction' => '+',
                    'amount' => $amount,
                    'status' => $st,
                    'status_label' => bingo_status_label_short($st),
                    'game' => $resolveGame($gameId),
                    'game_id' => $gameId,
                    'carton_serial' => $serial,
                    'carton_id' => $cartonId,
                    'modality' => $modName,
                    'result' => 'won',
                    'result_label' => 'Ganó',
                    'prize_amount' => $amount,
                    'credit_wallet' => $credit,
                    'credit_label' => $creditLabel,
                    'detail' => trim(
                        'Modalidad: ' . ($modName ?: '-')
                        . ' | Cartón: ' . ($serial ?: '-')
                        . ($creditLabel !== '' ? ' | Acreditado a: ' . $creditLabel : '')
                    ),
                    'ref_table' => 'payments',
                    'ref_id' => (int) ($pay['id'] ?? 0),
                ]);
                continue;
            }

            if ($ptype === 'admin_bonus_debit') {
                $push([
                    'datetime' => (string) ($pay['created_at'] ?? ''),
                    'type' => 'bonus',
                    'type_label' => 'Ajuste Bono (Admin)',
                    'direction' => '-',
                    'amount' => $amount,
                    'status' => $st,
                    'status_label' => bingo_status_label_short($st),
                    'source' => 'bonus',
                    'source_label' => 'Saldo Bono',
                    'detail' => 'Ajuste de saldo de bono por administración',
                    'ref_table' => 'payments',
                    'ref_id' => (int) ($pay['id'] ?? 0),
                ]);
                continue;
            }

            $bonusLabels = [
                'registration_bonus' => 'Bono de registro',
                'admin_bonus' => 'Acreditación Bono (Admin)',
                'bonus' => 'Bono',
                'referred' => 'Comisión referido',
                'referral' => 'Comisión referido',
            ];
            if (isset($bonusLabels[$ptype])) {
                $push([
                    'datetime' => (string) ($pay['created_at'] ?? ''),
                    'type' => 'bonus',
                    'type_label' => $bonusLabels[$ptype],
                    'direction' => '+',
                    'amount' => $amount,
                    'status' => $st,
                    'status_label' => bingo_status_label_short($st),
                    'source' => 'bonus',
                    'source_label' => 'Saldo Bono',
                    'detail' => 'Tipo: ' . $ptype,
                    'ref_table' => 'payments',
                    'ref_id' => (int) ($pay['id'] ?? 0),
                ]);
                continue;
            }

            // Las comisiones se gestionan exclusivamente en el módulo de comisiones / liquidaciones
            if (in_array($ptype, [
                'operator_ggr_commission', 'store_ggr_commission',
                'operator_recharge_commission', 'store_recharge_commission',
                'operator_prize_commission', 'store_prize_commission', 'store_retire_commission',
                'operator_commission', 'store_commission', 'referred', 'referral'
            ], true)) {
                continue;
            }

            if (in_array($ptype, ['operator_store_debit', 'admin_store_debit', 'admin_operator_debit', 'store_debit', 'store_balance_remove', 'admin_recharge_debit'], true)) {
                $push([
                    'datetime' => (string) ($pay['created_at'] ?? ''),
                    'type' => 'retire',
                    'type_label' => $ptype === 'admin_recharge_debit' ? 'Ajuste Recarga (Admin)' : 'Retiro de saldo',
                    'direction' => '-',
                    'amount' => $amount,
                    'status' => $st,
                    'status_label' => bingo_status_label_short($st),
                    'source' => 'recharge',
                    'source_label' => 'Saldo Recarga',
                    'detail' => 'Débito de saldo' . ($typeId > 0 ? (' (Ref #' . $typeId . ')') : ''),
                    'ref_table' => 'payments',
                    'ref_id' => (int) ($pay['id'] ?? 0),
                ]);
                continue;
            }

            if ($ptype === 'admin_withdraw_debit') {
                $push([
                    'datetime' => (string) ($pay['created_at'] ?? ''),
                    'type' => 'retire',
                    'type_label' => 'Ajuste Retiro (Admin)',
                    'direction' => '-',
                    'amount' => $amount,
                    'status' => $st,
                    'status_label' => bingo_status_label_short($st),
                    'source' => 'withdraw',
                    'source_label' => 'Saldo Retiro',
                    'detail' => 'Débito de saldo retirable por administración',
                    'ref_table' => 'payments',
                    'ref_id' => (int) ($pay['id'] ?? 0),
                ]);
                continue;
            }

            if (in_array($ptype, ['operator_store_credit', 'admin_operator_pay', 'admin_operator_credit', 'store_credit', 'store_balance_add', 'admin_recharge_credit', 'operator_prize_credit', 'operator_prize_payout_credit'], true)) {
                $creditLabel = match($ptype) {
                    'admin_recharge_credit' => 'Ajuste Recarga (Admin)',
                    'operator_prize_credit', 'operator_prize_payout_credit' => 'Acreditación por Pago de Premio',
                    default => 'Acreditación de saldo'
                };
                $push([
                    'datetime' => (string) ($pay['created_at'] ?? ''),
                    'type' => 'deposit',
                    'type_label' => $creditLabel,
                    'direction' => '+',
                    'amount' => $amount,
                    'status' => $st,
                    'status_label' => bingo_status_label_short($st),
                    'source' => 'recharge',
                    'source_label' => 'Saldo Recarga',
                    'detail' => (! empty($pay['description']) ? $pay['description'] : ('Acreditación de saldo' . ($typeId > 0 ? (' (Ref #' . $typeId . ')') : ''))),
                    'ref_table' => 'payments',
                    'ref_id' => (int) ($pay['id'] ?? 0),
                ]);
                continue;
            }

            if ($ptype === 'admin_withdraw_credit') {
                $push([
                    'datetime' => (string) ($pay['created_at'] ?? ''),
                    'type' => 'deposit',
                    'type_label' => 'Acreditación Retiro (Admin)',
                    'direction' => '+',
                    'amount' => $amount,
                    'status' => $st,
                    'status_label' => bingo_status_label_short($st),
                    'source' => 'withdraw',
                    'source_label' => 'Saldo Retiro',
                    'detail' => 'Acreditación de saldo retirable por administración',
                    'ref_table' => 'payments',
                    'ref_id' => (int) ($pay['id'] ?? 0),
                ]);
                continue;
            }

            // Excluir tipos de pagos ya manejados específicamente en sus tablas correspondientes
            if (in_array($ptype, ['award', 'retire', 'withdraw', 'payout', 'carton_purchase'], true)) {
                continue;
            }

            $push([
                'datetime' => (string) ($pay['created_at'] ?? ''),
                'type' => 'payment',
                'type_label' => 'Movimiento: ' . ($ptype !== '' ? $ptype : 'pago'),
                'direction' => '+',
                'amount' => $amount,
                'status' => $st,
                'status_label' => bingo_status_label_short($st),
                'detail' => 'type_id=' . $typeId,
                'ref_table' => 'payments',
                'ref_id' => (int) ($pay['id'] ?? 0),
            ]);
        }

        // Ruleta (premios de cartones)
        foreach ($modelRoulettes->where('user', $userId)->orderBy('created_at', 'DESC')->findAll(300) as $roulette) {
            $st = (int) ($roulette['status'] ?? 0);
            $push([
                'datetime' => (string) ($roulette['created_at'] ?? ''),
                'type' => 'roulette',
                'type_label' => 'Ruleta',
                'direction' => '=',
                'amount' => round((float) ($roulette['amount'] ?? 0), 2),
                'status' => $st,
                'status_label' => $st === 1 ? 'Usado' : 'Pendiente',
                'game' => $resolveGame((int) ($roulette['game'] ?? 0)),
                'game_id' => (int) ($roulette['game'] ?? 0),
                'source' => 'roulette',
                'source_label' => 'Cartones ruleta',
                'detail' => 'Cartones otorgados: ' . (int) ($roulette['cartons'] ?? 0),
                'ref_table' => 'roulettes',
                'ref_id' => (int) ($roulette['id'] ?? 0),
            ]);
        }

        // Transferencias (si existen)
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('transfers')) {
                foreach ($modelTransfers->where('user', $userId)->orderBy('created_at', 'DESC')->findAll(300) as $t) {
                    $st = (int) ($t['status'] ?? 0);
                    $push([
                        'datetime' => (string) ($t['created_at'] ?? ''),
                        'type' => 'transfer',
                        'type_label' => 'Transferencia',
                        'direction' => '-',
                        'amount' => round((float) ($t['amount'] ?? 0), 2),
                        'status' => $st,
                        'status_label' => bingo_status_label_short($st),
                        'detail' => trim(
                            'Destino: ' . (string) ($t['from'] ?? '-')
                            . (! empty($t['note']) ? ' | ' . $t['note'] : '')
                        ),
                        'ref_table' => 'transfers',
                        'ref_id' => (int) ($t['id'] ?? 0),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // tabla opcional
        }

        // Ordenar en orden cronológico inverso (DESC: el movimiento más reciente primero)
        usort($rows, static function ($a, $b) {
            $cmp = strcmp((string) ($b['datetime'] ?? ''), (string) ($a['datetime'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }

            return ((int) ($b['ref_id'] ?? 0)) <=> ((int) ($a['ref_id'] ?? 0));
        });

        if ($limit > 0 && count($rows) > $limit) {
            $rows = array_slice($rows, 0, $limit);
        }

        // Obtener el saldo total REAL actual de la billetera del usuario
        $currentWalletBalance = round((float) (wallet_total($targetUser)), 2);

        // Calcular el saldo que quedó después de cada movimiento hacia atrás desde el saldo actual de la billetera
        $cursorBalance = $currentWalletBalance;
        foreach ($rows as &$row) {
            $st = (int) ($row['status'] ?? 0);
            $dir = (string) ($row['direction'] ?? '');
            $amt = round((float) ($row['amount'] ?? 0), 2);

            // El saldo después de este movimiento fue $cursorBalance
            $row['balance_after'] = max(0.0, round($cursorBalance, 2));

            // Solo movimientos aprobados (o retiros pendientes que ya descuentan saldo) alteran el saldo hacia atrás
            $affectsBalance = ($st === 2 || ($row['type'] === 'retire' && $st === 1));
            if ($affectsBalance && $dir !== '=') {
                if ($dir === '+') {
                    // Si este movimiento sumó dinero, antes de este movimiento había MENOS dinero
                    $cursorBalance = round($cursorBalance - $amt, 2);
                } elseif ($dir === '-') {
                    // Si este movimiento restó dinero, antes de este movimiento había MÁS dinero
                    $cursorBalance = round($cursorBalance + $amt, 2);
                }
            }
        }
        unset($row);

        return $rows;
    }
}

if (!function_exists('bingo_user_movements_export_rows')) {
    /**
     * Filas planas para Excel/CSV del ledger de movimientos.
     *
     * @param list<array<string,mixed>> $movements
     * @return array{headers:list<string>,rows:list<list<mixed>>}
     */
    function bingo_user_movements_export_rows(array $movements): array
    {
        $headers = [
            'Fecha',
            'Tipo',
            'Direccion',
            'Monto',
            'Saldo Total',
            'Estado',
            'Juego',
            'Carton serie',
            'Modalidad',
            'Resultado',
            'Premio',
            'Origen pago',
            'Abono',
            'Recarga',
            'Retiro',
            'Acreditado a',
            'Detalle',
            'Ref',
        ];

        $rows = [];
        foreach ($movements as $m) {
            $rows[] = [
                (string) ($m['datetime'] ?? ''),
                (string) ($m['type_label'] ?? $m['type'] ?? ''),
                (string) ($m['direction'] ?? ''),
                (float) ($m['amount'] ?? 0),
                isset($m['balance_after']) ? (float) $m['balance_after'] : '',
                (string) ($m['status_label'] ?? ''),
                (string) ($m['game'] ?? ''),
                (string) ($m['carton_serial'] ?? ''),
                (string) ($m['modality'] ?? ''),
                (string) ($m['result_label'] ?? ''),
                (float) ($m['prize_amount'] ?? 0),
                (string) ($m['source_label'] ?? ''),
                (float) ($m['from_bonus'] ?? 0),
                (float) ($m['from_recharge'] ?? 0),
                (float) ($m['from_withdraw'] ?? 0),
                (string) ($m['credit_label'] ?? ''),
                (string) ($m['detail'] ?? ''),
                (string) (($m['ref_table'] ?? '') . '#' . ($m['ref_id'] ?? '')),
            ];
        }

        return ['headers' => $headers, 'rows' => $rows];
    }
}

if (! function_exists('bingo_build_store_movements_ledger')) {
    /**
     * Construye el historial y ledger completo de movimientos de un Punto de Venta (Store).
     *
     * @param int $storeId
     * @param array<string,mixed> $filters
     * @return array{movements:list<array<string,mixed>>,stats:array<string,mixed>}
     */
    function bingo_build_store_movements_ledger(int $storeId, array $filters = []): array
    {
        if ($storeId <= 0) {
            return ['movements' => [], 'stats' => []];
        }

        $modelDeposits = new \App\Models\DepositsModel();
        $modelPayments = new \App\Models\PaymentsModel();
        $modelRetires = new \App\Models\RetiresModel();
        $modelUsers = new \App\Models\UsersModel();

        $rows = [];
        $push = static function (array $row) use (&$rows) {
            $rows[] = $row;
        };

        // Cache simple de usuarios
        $userCache = [];
        $getUser = static function (int $uid) use (&$userCache, $modelUsers): ?array {
            if ($uid <= 0) {
                return null;
            }
            if (! isset($userCache[$uid])) {
                $userCache[$uid] = $modelUsers->find($uid);
            }
            return $userCache[$uid];
        };

        // 1. Recargas a jugadores realizadas por la tienda
        $storeRecharges = $modelDeposits
            ->where('store', $storeId)
            ->where('method', 'store player recharge')
            ->orderBy('created_at', 'DESC')
            ->findAll(1000);

        foreach ($storeRecharges as $dep) {
            $pId = (int) ($dep['user'] ?? 0);
            $pUser = $getUser($pId);
            $pName = $pUser ? trim(($pUser['firstname'] ?? '') . ' ' . ($pUser['lastname'] ?? '')) : 'Jugador';
            $pDoc = (string) ($pUser['document'] ?? $dep['document'] ?? '');
            $pUsername = (string) ($pUser['username'] ?? '');
            $pCode = (string) ($pUser['code'] ?? '');
            $st = (int) ($dep['status'] ?? 0);
            $amt = round((float) ($dep['amount'] ?? 0), 2);

            $push([
                'id' => 'DEP_' . $dep['id'],
                'datetime' => (string) ($dep['created_at'] ?? ''),
                'type' => 'recharge_player',
                'type_category' => 'recharge',
                'type_label' => 'Recarga a Jugador',
                'badge_class' => 'bg-info text-white',
                'icon' => 'fa-duotone fa-solid fa-mobile-screen',
                'direction' => '-',
                'amount' => $amt,
                'status' => $st,
                'status_label' => bingo_status_label_short($st),
                'beneficiary_name' => $pName,
                'beneficiary_document' => $pDoc,
                'beneficiary_username' => $pUsername,
                'beneficiary_code' => $pCode,
                'ref_code' => 'DEP #' . $dep['id'],
                'detail' => 'Recarga a ' . $pName . ($pDoc !== '' ? ' (Cédula: ' . $pDoc . ')' : '') . ($pUsername !== '' ? ' @' . $pUsername : ''),
                'ref_table' => 'deposits',
                'ref_id' => (int) ($dep['id'] ?? 0),
            ]);
        }

        // 2. Pagos de notas de retiro en efectivo
        $retirePayments = $modelPayments
            ->where('user', $storeId)
            ->whereIn('type', ['store_retire_pay', 'store_prize_pay'])
            ->orderBy('created_at', 'DESC')
            ->findAll(1000);

        foreach ($retirePayments as $pay) {
            $retireId = (int) ($pay['type_id'] ?? 0);
            $retireRecord = $retireId > 0 ? $modelRetires->find($retireId) : null;
            $pId = $retireRecord ? (int) ($retireRecord['user'] ?? 0) : 0;
            $pUser = $getUser($pId);
            $pName = $pUser ? trim(($pUser['firstname'] ?? '') . ' ' . ($pUser['lastname'] ?? '')) : 'Jugador';
            $pDoc = (string) ($retireRecord['document'] ?? $pUser['document'] ?? '');
            $retCode = (string) ($retireRecord['account'] ?? '');
            $amt = round((float) ($pay['amount'] ?? 0), 2);
            $st = (int) ($pay['status'] ?? 2);

            $push([
                'id' => 'PAY_RET_' . $pay['id'],
                'datetime' => (string) ($pay['created_at'] ?? ''),
                'type' => 'pay_retire',
                'type_category' => 'retire',
                'type_label' => 'Pago de Retiro (Efectivo)',
                'badge_class' => 'bg-success text-white',
                'icon' => 'fa-duotone fa-solid fa-money-bill-transfer',
                'direction' => '+',
                'amount' => $amt,
                'status' => $st,
                'status_label' => 'Pagado',
                'beneficiary_name' => $pName,
                'beneficiary_document' => $pDoc,
                'beneficiary_username' => (string) ($pUser['username'] ?? ''),
                'beneficiary_code' => $retCode,
                'ref_code' => $retCode !== '' ? $retCode : ('RET #' . $retireId),
                'detail' => 'Pago en efectivo' . ($retCode !== '' ? ' (Código: ' . $retCode . ')' : '') . ' a ' . $pName . ($pDoc !== '' ? ' | Cédula: ' . $pDoc : ''),
                'ref_table' => 'payments',
                'ref_id' => (int) ($pay['id'] ?? 0),
            ]);
        }

        // 3. Acreditaciones de saldo (Funding / Operador / Admin)
        $storeFunding = $modelDeposits
            ->where('user', $storeId)
            ->groupStart()
                ->where('method', 'store funding request')
                ->orWhere('account', 'store_funding')
            ->groupEnd()
            ->orderBy('created_at', 'DESC')
            ->findAll(500);

        foreach ($storeFunding as $dep) {
            $st = (int) ($dep['status'] ?? 0);
            $amt = round((float) ($dep['amount'] ?? 0), 2);
            $push([
                'id' => 'FUND_' . $dep['id'],
                'datetime' => (string) ($dep['created_at'] ?? ''),
                'type' => 'funding_credit',
                'type_category' => 'credit',
                'type_label' => 'Solicitud de Saldo',
                'badge_class' => 'bg-success text-white',
                'icon' => 'fa-duotone fa-solid fa-hand-holding-dollar',
                'direction' => '+',
                'amount' => $amt,
                'status' => $st,
                'status_label' => bingo_status_label_short($st),
                'beneficiary_name' => 'Mi Punto de Venta',
                'beneficiary_document' => '',
                'beneficiary_username' => '',
                'beneficiary_code' => '',
                'ref_code' => 'SOL #' . $dep['id'],
                'detail' => 'Carga de saldo solicitada por transferencia' . (! empty($dep['bank']) ? ' (' . $dep['bank'] . ')' : ''),
                'ref_table' => 'deposits',
                'ref_id' => (int) ($dep['id'] ?? 0),
            ]);
        }

        // 4. Pagos directos / Acreditaciones / Débitos / Comisiones en tabla `payments`
        $storePayments = $modelPayments
            ->where('user', $storeId)
            ->whereNotIn('type', ['store_retire_pay', 'store_prize_pay'])
            ->orderBy('created_at', 'DESC')
            ->findAll(1000);

        foreach ($storePayments as $pay) {
            $ptype = (string) ($pay['type'] ?? '');
            $amt = round((float) ($pay['amount'] ?? 0), 2);
            $st = (int) ($pay['status'] ?? 2);

            // Acreditaciones de saldo y ajustes manuales (Admin / Operador / Bonos)
            if (in_array($ptype, [
                'admin_store_credit', 'operator_store_credit', 'store_credit', 'store_balance_add',
                'admin_recharge_credit', 'admin_withdraw_credit', 'admin_bonus', 'manual_credit',
                'balance_add', 'adjustment_credit'
            ], true)) {
                $creditLabel = match($ptype) {
                    'admin_store_credit'    => 'Acreditación (Admin)',
                    'operator_store_credit' => 'Acreditación (Operador)',
                    'admin_recharge_credit' => 'Ajuste Recarga (Admin)',
                    'admin_withdraw_credit' => 'Ajuste Retiro (Admin)',
                    'admin_bonus'           => 'Bono (Admin)',
                    default                 => 'Acreditación de Saldo'
                };
                $push([
                    'id' => 'PAY_CRED_' . $pay['id'],
                    'datetime' => (string) ($pay['created_at'] ?? ''),
                    'type' => 'credit',
                    'type_category' => 'credit',
                    'type_label' => $creditLabel,
                    'badge_class' => 'bg-success text-white',
                    'icon' => 'fa-duotone fa-solid fa-circle-plus',
                    'direction' => '+',
                    'amount' => $amt,
                    'status' => $st,
                    'status_label' => bingo_status_label_short($st),
                    'beneficiary_name' => 'Mi Punto de Venta',
                    'beneficiary_document' => '',
                    'beneficiary_username' => '',
                    'beneficiary_code' => '',
                    'ref_code' => 'PAY #' . $pay['id'],
                    'detail' => (! empty($pay['description']) ? $pay['description'] : ($ptype === 'admin_store_credit' ? 'Acreditación manual realizada por Administración' : ($ptype === 'operator_store_credit' ? 'Acreditación de saldo por Operador' : 'Acreditación / Ajuste manual de saldo'))),
                    'ref_table' => 'payments',
                    'ref_id' => (int) ($pay['id'] ?? 0),
                ]);
                continue;
            }

            // Débitos de saldo y ajustes manuales (Admin / Operador)
            if (in_array($ptype, [
                'admin_store_debit', 'operator_store_debit', 'store_debit', 'store_balance_remove',
                'admin_recharge_debit', 'admin_withdraw_debit', 'admin_bonus_debit', 'manual_debit',
                'balance_remove', 'adjustment_debit'
            ], true)) {
                $debitLabel = match($ptype) {
                    'admin_store_debit'    => 'Débito / Ajuste (Admin)',
                    'operator_store_debit' => 'Débito / Ajuste (Operador)',
                    'admin_recharge_debit' => 'Ajuste Recarga (Admin)',
                    'admin_withdraw_debit' => 'Ajuste Retiro (Admin)',
                    'admin_bonus_debit'    => 'Ajuste Bono (Admin)',
                    default                => 'Débito / Retiro de Saldo'
                };
                $push([
                    'id' => 'PAY_DEB_' . $pay['id'],
                    'datetime' => (string) ($pay['created_at'] ?? ''),
                    'type' => 'debit',
                    'type_category' => 'debit',
                    'type_label' => $debitLabel,
                    'badge_class' => 'bg-danger text-white',
                    'icon' => 'fa-duotone fa-solid fa-circle-minus',
                    'direction' => '-',
                    'amount' => $amt,
                    'status' => $st,
                    'status_label' => bingo_status_label_short($st),
                    'beneficiary_name' => 'Mi Punto de Venta',
                    'beneficiary_document' => '',
                    'beneficiary_username' => '',
                    'beneficiary_code' => '',
                    'ref_code' => 'PAY #' . $pay['id'],
                    'detail' => (! empty($pay['description']) ? $pay['description'] : ($ptype === 'admin_store_debit' ? 'Débito manual realizado por Administración' : ($ptype === 'operator_store_debit' ? 'Débito / Ajuste por Operador' : 'Débito / Ajuste de saldo'))),
                    'ref_table' => 'payments',
                    'ref_id' => (int) ($pay['id'] ?? 0),
                ]);
                continue;
            }

            // Omitir comisiones ganadas del libro de movimientos operativos del Punto de Venta
            if (in_array($ptype, ['store_recharge_commission', 'store_ggr_commission', 'store_prize_commission', 'store_retire_commission', 'store_commission', 'referred', 'referral'], true)) {
                continue;
            }
        }

        // Orden cronológico ASC para calcular saldo acumulado y métricas
        usort($rows, static function ($a, $b) {
            $cmp = strcmp((string) ($a['datetime'] ?? ''), (string) ($b['datetime'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp((string) ($a['id'] ?? ''), (string) ($b['id'] ?? ''));
        });

        // Totales estadísticos exclusivamente operativos para el Punto de Venta
        $stats = [
            'total_recharges_amount'      => 0.0,
            'total_recharges_count'       => 0,
            'total_retires_amount'        => 0.0,
            'total_retires_count'         => 0,
            'total_credits_amount'        => 0.0,
            'total_credits_count'         => 0,
            'total_debits_amount'         => 0.0,
            'total_debits_count'          => 0,
        ];

        foreach ($rows as $row) {
            $amt = round((float) ($row['amount'] ?? 0), 2);
            $cat = (string) ($row['type_category'] ?? '');
            $st = (int) ($row['status'] ?? 0);

            if ($st === 2 || $st === 1) {
                if ($cat === 'recharge' && $st === 2) {
                    $stats['total_recharges_amount'] += $amt;
                    $stats['total_recharges_count']++;
                } elseif ($cat === 'retire' && $st === 2) {
                    $stats['total_retires_amount'] += $amt;
                    $stats['total_retires_count']++;
                } elseif ($cat === 'credit' && $st === 2) {
                    $stats['total_credits_amount'] += $amt;
                    $stats['total_credits_count']++;
                } elseif ($cat === 'debit' && $st === 2) {
                    $stats['total_debits_amount'] += $amt;
                    $stats['total_debits_count']++;
                }
            }
        }

        // Filtrado por fecha, tipo y búsqueda
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $typeFilter = trim((string) ($filters['type'] ?? 'all'));
        $search = strtolower(trim((string) ($filters['search'] ?? '')));

        $filteredRows = [];
        foreach ($rows as $row) {
            $rowDate = substr((string) ($row['datetime'] ?? ''), 0, 10);

            if ($dateFrom !== '' && $rowDate < $dateFrom) {
                continue;
            }
            if ($dateTo !== '' && $rowDate > $dateTo) {
                continue;
            }
            if ($typeFilter !== '' && $typeFilter !== 'all') {
                if (($row['type_category'] ?? '') !== $typeFilter && ($row['type'] ?? '') !== $typeFilter) {
                    continue;
                }
            }
            if ($search !== '') {
                $searchContent = strtolower(
                    ($row['beneficiary_name'] ?? '') . ' ' .
                    ($row['beneficiary_document'] ?? '') . ' ' .
                    ($row['beneficiary_username'] ?? '') . ' ' .
                    ($row['ref_code'] ?? '') . ' ' .
                    ($row['detail'] ?? '') . ' ' .
                    ($row['type_label'] ?? '')
                );
                if (strpos($searchContent, $search) === false) {
                    continue;
                }
            }
            $filteredRows[] = $row;
        }

        // Orden DESC (más reciente primero)
        usort($filteredRows, static function ($a, $b) {
            $cmp = strcmp((string) ($b['datetime'] ?? ''), (string) ($a['datetime'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp((string) ($b['id'] ?? ''), (string) ($a['id'] ?? ''));
        });

        // Obtener saldo total disponible real actual de la tienda
        $storeUser = $modelUsers->find($storeId);
        $currentStoreBalance = round((float) ($storeUser['wallet_recharge'] ?? 0), 2);

        $cursorBalance = $currentStoreBalance;
        foreach ($filteredRows as &$row) {
            $st = (int) ($row['status'] ?? 0);
            $dir = (string) ($row['direction'] ?? '');
            $amt = round((float) ($row['amount'] ?? 0), 2);

            $row['balance_after'] = max(0.0, round($cursorBalance, 2));

            if ($st === 2 && $dir !== '=') {
                if ($dir === '+') {
                    $cursorBalance = round($cursorBalance - $amt, 2);
                } elseif ($dir === '-') {
                    $cursorBalance = round($cursorBalance + $amt, 2);
                }
            }
        }
        unset($row);

        return [
            'movements' => $filteredRows,
            'stats'     => $stats,
        ];
    }
}

if (! function_exists('bingo_store_movements_export_rows')) {
    /**
     * Filas planas para Excel/CSV de los movimientos del Punto de Venta.
     *
     * @param list<array<string,mixed>> $movements
     * @return array{headers:list<string>,rows:list<list<mixed>>}
     */
    function bingo_store_movements_export_rows(array $movements): array
    {
        $headers = [
            'Fecha y Hora',
            'Tipo de Movimiento',
            'Direccion (+/-)',
            'Monto',
            'Saldo Resultante',
            'Estado',
            'Beneficiario / Jugador',
            'Documento / Cedula',
            'Referencia / Codigo',
            'Detalle',
        ];

        $rows = [];
        foreach ($movements as $m) {
            $datetime = (string) ($m['datetime'] ?? '');
            if ($datetime !== '' && strtotime($datetime) !== false) {
                $datetime = date('d/m/Y H:i:s', strtotime($datetime));
            }

            $rows[] = [
                $datetime,
                (string) ($m['type_label'] ?? $m['type'] ?? ''),
                (string) ($m['direction'] ?? ''),
                (float) ($m['amount'] ?? 0),
                isset($m['balance_after']) && $m['balance_after'] !== '' && $m['balance_after'] !== null
                    ? (float) $m['balance_after']
                    : '',
                (string) ($m['status_label'] ?? ''),
                (string) ($m['beneficiary_name'] ?? ''),
                (string) ($m['beneficiary_document'] ?? ''),
                (string) ($m['ref_code'] ?? ''),
                (string) ($m['detail'] ?? ''),
            ];
        }

        return ['headers' => $headers, 'rows' => $rows];
    }
}

if (! function_exists('bingo_build_operator_movements_ledger')) {
    /**
     * Construye el libro de movimientos completo del Operador y sus Puntos de Venta.
     *
     * @param int $operatorId
     * @param array<string,mixed> $filters
     * @return array{movements:list<array<string,mixed>>,stats:array<string,mixed>}
     */
    function bingo_build_operator_movements_ledger(int $operatorId, array $filters = []): array
    {
        if ($operatorId <= 0) {
            return ['movements' => [], 'stats' => []];
        }

        $modelDeposits = new \App\Models\DepositsModel();
        $modelPayments = new \App\Models\PaymentsModel();
        $modelRetires = new \App\Models\RetiresModel();
        $modelUsers = new \App\Models\UsersModel();

        $operatorStores = $modelUsers
            ->where('group', bingo_group_store())
            ->where('operator_id', $operatorId)
            ->where('deleted', 0)
            ->findAll();

        $storeMap = [];
        $allStoreIds = [];
        foreach ($operatorStores as $st) {
            $sId = (int) $st['id'];
            $allStoreIds[] = $sId;
            $storeMap[$sId] = trim($st['business_name'] ?: ($st['firstname'] . ' ' . $st['lastname'])) . ' (' . ($st['code'] ?: $st['username']) . ')';
        }

        $selectedStoreId = trim((string) ($filters['store_id'] ?? 'all'));
        $targetStoreIds = $allStoreIds;
        $includeDirectOperator = true;

        if ($selectedStoreId !== 'all' && $selectedStoreId !== '') {
            if ($selectedStoreId === 'operator' || (int) $selectedStoreId === $operatorId) {
                $targetStoreIds = [];
                $includeDirectOperator = true;
            } elseif (in_array((int) $selectedStoreId, $allStoreIds, true)) {
                $targetStoreIds = [(int) $selectedStoreId];
                $includeDirectOperator = false;
            }
        }

        $rows = [];
        $push = static function (array $row) use (&$rows) {
            $rows[] = $row;
        };

        $userCache = [];
        $getUser = static function (int $uid) use (&$userCache, $modelUsers): ?array {
            if ($uid <= 0) {
                return null;
            }
            if (! isset($userCache[$uid])) {
                $userCache[$uid] = $modelUsers->find($uid);
            }
            return $userCache[$uid];
        };

        // 1. Recargas a jugadores realizadas por las tiendas o el operador
        $allRechargeStoreIds = array_merge($targetStoreIds, $includeDirectOperator ? [$operatorId] : []);
        if (! empty($allRechargeStoreIds)) {
            $storeRecharges = $modelDeposits
                ->whereIn('store', $allRechargeStoreIds)
                ->where('method', 'store player recharge')
                ->orderBy('created_at', 'DESC')
                ->findAll(1000);

            foreach ($storeRecharges as $dep) {
                $pId = (int) ($dep['user'] ?? 0);
                $stId = (int) ($dep['store'] ?? 0);
                $pUser = $getUser($pId);
                $pName = $pUser ? trim(($pUser['firstname'] ?? '') . ' ' . ($pUser['lastname'] ?? '')) : 'Jugador';
                $pDoc = (string) ($pUser['document'] ?? $dep['document'] ?? '');
                $pUsername = (string) ($pUser['username'] ?? '');
                $pCode = (string) ($pUser['code'] ?? '');
                $st = (int) ($dep['status'] ?? 0);
                $amt = round((float) ($dep['amount'] ?? 0), 2);
                $stName = $storeMap[$stId] ?? ($stId === $operatorId ? 'Operador Directo' : 'Punto de Venta');

                $push([
                    'id' => 'DEP_' . $dep['id'],
                    'datetime' => (string) ($dep['created_at'] ?? ''),
                    'type' => 'recharge_player',
                    'type_category' => 'recharge',
                    'type_label' => 'Recarga a Jugador',
                    'badge_class' => 'bg-info text-white',
                    'icon' => 'fa-duotone fa-solid fa-mobile-screen',
                    'direction' => '-',
                    'amount' => $amt,
                    'status' => $st,
                    'status_label' => bingo_status_label_short($st),
                    'store_id' => $stId,
                    'store_name' => $stName,
                    'beneficiary_name' => $pName,
                    'beneficiary_document' => $pDoc,
                    'beneficiary_username' => $pUsername,
                    'beneficiary_code' => $pCode,
                    'ref_code' => 'DEP #' . $dep['id'],
                    'detail' => 'Recarga a ' . $pName . ' por ' . $stName,
                    'ref_table' => 'deposits',
                    'ref_id' => (int) ($dep['id'] ?? 0),
                ]);
            }
        }

        // 2. Pagos de notas de retiro en efectivo (por tiendas o el operador)
        if (! empty($allRechargeStoreIds)) {
            $retirePayments = $modelPayments
                ->whereIn('user', $allRechargeStoreIds)
                ->whereIn('type', ['store_retire_pay', 'store_prize_pay'])
                ->orderBy('created_at', 'DESC')
                ->findAll(1000);

            foreach ($retirePayments as $pay) {
                $retireId = (int) ($pay['type_id'] ?? 0);
                $stId = (int) ($pay['user'] ?? 0);
                $retireRecord = $retireId > 0 ? $modelRetires->find($retireId) : null;
                $pId = $retireRecord ? (int) ($retireRecord['user'] ?? 0) : 0;
                $pUser = $getUser($pId);
                $pName = $pUser ? trim(($pUser['firstname'] ?? '') . ' ' . ($pUser['lastname'] ?? '')) : 'Jugador';
                $pDoc = (string) ($retireRecord['document'] ?? $pUser['document'] ?? '');
                $retCode = (string) ($retireRecord['account'] ?? '');
                $amt = round((float) ($pay['amount'] ?? 0), 2);
                $st = (int) ($pay['status'] ?? 2);
                $stName = $storeMap[$stId] ?? ($stId === $operatorId ? 'Operador Directo' : 'Punto de Venta');

                $push([
                    'id' => 'PAY_RET_' . $pay['id'],
                    'datetime' => (string) ($pay['created_at'] ?? ''),
                    'type' => 'pay_retire',
                    'type_category' => 'retire',
                    'type_label' => 'Pago de Retiro',
                    'badge_class' => 'bg-success text-white',
                    'icon' => 'fa-duotone fa-solid fa-money-bill-transfer',
                    'direction' => '+',
                    'amount' => $amt,
                    'status' => $st,
                    'status_label' => 'Pagado',
                    'store_id' => $stId,
                    'store_name' => $stName,
                    'beneficiary_name' => $pName,
                    'beneficiary_document' => $pDoc,
                    'beneficiary_username' => (string) ($pUser['username'] ?? ''),
                    'beneficiary_code' => $retCode,
                    'ref_code' => $retCode !== '' ? $retCode : ('RET #' . $retireId),
                    'detail' => 'Pago de retiro a ' . $pName . ' por ' . $stName,
                    'ref_table' => 'payments',
                    'ref_id' => (int) ($pay['id'] ?? 0),
                ]);
            }
        }

        // 3. Acreditaciones de saldo del Operador (Funding del admin al operador)
        if ($includeDirectOperator) {
            $operatorFunding = $modelDeposits
                ->where('user', $operatorId)
                ->groupStart()
                    ->where('method', 'operator funding request')
                    ->orWhere('account', 'operator_funding')
                ->groupEnd()
                ->orderBy('created_at', 'DESC')
                ->findAll(300);

            foreach ($operatorFunding as $dep) {
                $st = (int) ($dep['status'] ?? 0);
                $amt = round((float) ($dep['amount'] ?? 0), 2);
                $push([
                    'id' => 'FUND_OP_' . $dep['id'],
                    'datetime' => (string) ($dep['created_at'] ?? ''),
                    'type' => 'credit',
                    'type_category' => 'credit',
                    'type_label' => 'Carga de Saldo',
                    'badge_class' => 'bg-success text-white',
                    'icon' => 'fa-duotone fa-solid fa-hand-holding-dollar',
                    'direction' => '+',
                    'amount' => $amt,
                    'status' => $st,
                    'status_label' => bingo_status_label_short($st),
                    'store_id' => $operatorId,
                    'store_name' => 'Operador',
                    'beneficiary_name' => 'Mi Cuenta de Operador',
                    'beneficiary_document' => '',
                    'beneficiary_username' => '',
                    'beneficiary_code' => '',
                    'ref_code' => 'SOL #' . $dep['id'],
                    'detail' => 'Solicitud de saldo al Administrador' . (! empty($dep['bank']) ? ' (' . $dep['bank'] . ')' : ''),
                    'ref_table' => 'deposits',
                    'ref_id' => (int) ($dep['id'] ?? 0),
                ]);
            }
        }

        // 4. Pagos, transferencias a Puntos de Venta y Comisiones
        $relevantUserIds = array_merge([$operatorId], $allStoreIds);
        if (! empty($relevantUserIds)) {
            $allPayments = $modelPayments
                ->whereIn('user', $relevantUserIds)
                ->whereNotIn('type', ['store_retire_pay', 'store_prize_pay'])
                ->orderBy('created_at', 'DESC')
                ->findAll(1500);

            foreach ($allPayments as $pay) {
                $ptype = (string) ($pay['type'] ?? '');
                $uId = (int) ($pay['user'] ?? 0);
                $amt = round((float) ($pay['amount'] ?? 0), 2);
                $st = (int) ($pay['status'] ?? 2);
                $stName = $storeMap[$uId] ?? ($uId === $operatorId ? 'Operador' : 'Punto de Venta');

                if (! empty($targetStoreIds) && count($targetStoreIds) === 1 && $uId !== $targetStoreIds[0] && (int) ($pay['from'] ?? 0) !== $targetStoreIds[0]) {
                    continue;
                }

                // Recargas del Operador a Puntos de Venta (Salida de saldo del operador: Negativo -)
                if ($ptype === 'operator_store_credit') {
                    $push([
                        'id' => 'PAY_OP_CRED_' . $pay['id'],
                        'datetime' => (string) ($pay['created_at'] ?? ''),
                        'type' => 'recharge_store',
                        'type_category' => 'recharge',
                        'type_label' => 'Recarga a PV',
                        'badge_class' => 'bg-danger text-white',
                        'icon' => 'fa-duotone fa-solid fa-arrow-up-right-from-square',
                        'direction' => '-',
                        'amount' => $amt,
                        'status' => $st,
                        'status_label' => bingo_status_label_short($st),
                        'store_id' => $uId,
                        'store_name' => $stName,
                        'beneficiary_name' => $stName,
                        'beneficiary_document' => '',
                        'beneficiary_username' => '',
                        'beneficiary_code' => '',
                        'ref_code' => 'PAY #' . $pay['id'],
                        'detail' => (! empty($pay['description']) ? $pay['description'] : ('Recarga / Transferencia de saldo a ' . $stName)),
                        'ref_table' => 'payments',
                        'ref_id' => (int) ($pay['id'] ?? 0),
                    ]);
                    continue;
                }

                // Retiro de saldo del Punto de Venta por el Operador (Entrada de saldo al operador: Positivo +)
                if ($ptype === 'operator_store_debit') {
                    $push([
                        'id' => 'PAY_OP_DEB_' . $pay['id'],
                        'datetime' => (string) ($pay['created_at'] ?? ''),
                        'type' => 'credit',
                        'type_category' => 'credit',
                        'type_label' => 'Retiro de Saldo de PV',
                        'badge_class' => 'bg-success text-white',
                        'icon' => 'fa-duotone fa-solid fa-hand-holding-dollar',
                        'direction' => '+',
                        'amount' => $amt,
                        'status' => $st,
                        'status_label' => bingo_status_label_short($st),
                        'store_id' => $uId,
                        'store_name' => $stName,
                        'beneficiary_name' => $stName,
                        'beneficiary_document' => '',
                        'beneficiary_username' => '',
                        'beneficiary_code' => '',
                        'ref_code' => 'PAY #' . $pay['id'],
                        'detail' => (! empty($pay['description']) ? $pay['description'] : ('Retiro de saldo recuperado desde ' . $stName . ' a cuenta de Operador')),
                        'ref_table' => 'payments',
                        'ref_id' => (int) ($pay['id'] ?? 0),
                    ]);
                    continue;
                }

                // Transferencias de saldo y Acreditaciones manuales (Admin / Operador / Pago Premio)
                if (in_array($ptype, [
                    'admin_operator_pay', 'admin_operator_credit',
                    'store_credit', 'admin_store_credit', 'store_balance_add',
                    'admin_recharge_credit', 'admin_withdraw_credit', 'admin_bonus', 'manual_credit',
                    'balance_add', 'adjustment_credit', 'operator_prize_credit', 'operator_prize_payout_credit'
                ], true)) {
                    $creditLabel = match($ptype) {
                        'admin_operator_pay', 'admin_operator_credit' => 'Acreditación (Admin)',
                        'admin_store_credit'                          => 'Acreditación PV (Admin)',
                        'admin_recharge_credit'                       => 'Ajuste Recarga (Admin)',
                        'admin_withdraw_credit'                       => 'Ajuste Retiro (Admin)',
                        'admin_bonus'                                 => 'Bono (Admin)',
                        'operator_prize_credit', 'operator_prize_payout_credit' => 'Acreditación por Pago de Premio',
                        default                                       => 'Acreditación de Saldo'
                    };
                    $fromStoreId = (int) ($pay['from'] ?? 0);
                    $fromStoreName = ($fromStoreId > 0 && isset($storeMap[$fromStoreId])) ? $storeMap[$fromStoreId] : $stName;
                    $push([
                        'id' => 'PAY_CRED_' . $pay['id'],
                        'datetime' => (string) ($pay['created_at'] ?? ''),
                        'type' => 'credit',
                        'type_category' => 'credit',
                        'type_label' => $creditLabel,
                        'badge_class' => 'bg-success text-white',
                        'icon' => 'fa-duotone fa-solid fa-circle-plus',
                        'direction' => ($uId === $operatorId ? '+' : '-'),
                        'amount' => $amt,
                        'status' => $st,
                        'status_label' => bingo_status_label_short($st),
                        'store_id' => $uId === $operatorId && $fromStoreId > 0 ? $fromStoreId : $uId,
                        'store_name' => $uId === $operatorId && $fromStoreId > 0 ? $fromStoreName : $stName,
                        'beneficiary_name' => $stName,
                        'beneficiary_document' => '',
                        'beneficiary_username' => '',
                        'beneficiary_code' => '',
                        'ref_code' => 'PAY #' . $pay['id'],
                        'detail' => (! empty($pay['description']) ? $pay['description'] : ($ptype === 'admin_operator_pay' || $ptype === 'admin_operator_credit' ? 'Acreditación manual realizada por Administración al Operador' : ($ptype === 'admin_store_credit' ? 'Acreditación manual por Administración a ' . $stName : 'Transferencia / Acreditación de saldo a ' . $stName))),
                        'ref_table' => 'payments',
                        'ref_id' => (int) ($pay['id'] ?? 0),
                    ]);
                    continue;
                }

                // Comisiones se gestionan exclusivamente en el módulo de comisiones del operador / liquidaciones
                if (in_array($ptype, [
                    'operator_ggr_commission', 'store_ggr_commission',
                    'operator_recharge_commission', 'store_recharge_commission',
                    'operator_prize_commission', 'store_prize_commission', 'store_retire_commission',
                    'operator_commission', 'store_commission', 'referred', 'referral'
                ], true)) {
                    continue;
                }

                // Débitos / Ajustes manuales (Admin / Operador)
                if (in_array($ptype, [
                    'admin_operator_debit', 'operator_debit', 'admin_store_debit',
                    'store_debit', 'store_balance_remove',
                    'admin_recharge_debit', 'admin_withdraw_debit', 'admin_bonus_debit', 'manual_debit',
                    'balance_remove', 'adjustment_debit'
                ], true)) {
                    $debitLabel = match($ptype) {
                        'admin_operator_debit' => 'Débito Operador (Admin)',
                        'admin_store_debit'    => 'Débito PV (Admin)',
                        'admin_recharge_debit' => 'Ajuste Recarga (Admin)',
                        'admin_withdraw_debit' => 'Ajuste Retiro (Admin)',
                        'admin_bonus_debit'    => 'Ajuste Bono (Admin)',
                        default                => 'Débito / Ajuste'
                    };
                    $push([
                        'id' => 'PAY_DEB_' . $pay['id'],
                        'datetime' => (string) ($pay['created_at'] ?? ''),
                        'type' => 'debit',
                        'type_category' => 'debit',
                        'type_label' => $debitLabel,
                        'badge_class' => 'bg-danger text-white',
                        'icon' => 'fa-duotone fa-solid fa-circle-minus',
                        'direction' => '-',
                        'amount' => $amt,
                        'status' => $st,
                        'status_label' => bingo_status_label_short($st),
                        'store_id' => $uId,
                        'store_name' => $stName,
                        'beneficiary_name' => $stName,
                        'beneficiary_document' => '',
                        'beneficiary_username' => '',
                        'beneficiary_code' => '',
                        'ref_code' => 'PAY #' . $pay['id'],
                        'detail' => (! empty($pay['description']) ? $pay['description'] : ($ptype === 'admin_operator_debit' ? 'Débito / Ajuste manual por Administración al Operador' : ($ptype === 'admin_store_debit' ? 'Débito / Ajuste manual por Administración a ' . $stName : 'Débito / Ajuste de saldo'))),
                        'ref_table' => 'payments',
                        'ref_id' => (int) ($pay['id'] ?? 0),
                    ]);
                    continue;
                }
            }
        }

        // Totales estadísticos exclusivamente operativos para el Operador (recargas, retiros, créditos y débitos)
        $stats = [
            'total_recharges_amount'      => 0.0,
            'total_recharges_count'       => 0,
            'total_retires_amount'        => 0.0,
            'total_retires_count'         => 0,
            'total_credits_amount'        => 0.0,
            'total_credits_count'         => 0,
            'total_debits_amount'         => 0.0,
            'total_debits_count'          => 0,
        ];

        foreach ($rows as $row) {
            $amt = round((float) ($row['amount'] ?? 0), 2);
            $cat = (string) ($row['type_category'] ?? '');
            $st = (int) ($row['status'] ?? 0);

            if ($st === 2 || $st === 1) {
                if ($cat === 'recharge' && $st === 2) {
                    $stats['total_recharges_amount'] += $amt;
                    $stats['total_recharges_count']++;
                } elseif ($cat === 'retire' && $st === 2) {
                    $stats['total_retires_amount'] += $amt;
                    $stats['total_retires_count']++;
                } elseif ($cat === 'credit' && $st === 2) {
                    $stats['total_credits_amount'] += $amt;
                    $stats['total_credits_count']++;
                } elseif ($cat === 'debit' && $st === 2) {
                    $stats['total_debits_amount'] += $amt;
                    $stats['total_debits_count']++;
                }
            }
        }

        // Filtrado por fecha, tienda, tipo y búsqueda
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $typeFilter = trim((string) ($filters['type'] ?? 'all'));
        $search = strtolower(trim((string) ($filters['search'] ?? '')));

        $filteredRows = [];
        foreach ($rows as $row) {
            $rowDate = substr((string) ($row['datetime'] ?? ''), 0, 10);

            if ($dateFrom !== '' && $rowDate < $dateFrom) {
                continue;
            }
            if ($dateTo !== '' && $rowDate > $dateTo) {
                continue;
            }
            if ($typeFilter !== '' && $typeFilter !== 'all') {
                if (($row['type_category'] ?? '') !== $typeFilter && ($row['type'] ?? '') !== $typeFilter) {
                    continue;
                }
            }
            if ($search !== '') {
                $searchContent = strtolower(
                    ($row['beneficiary_name'] ?? '') . ' ' .
                    ($row['beneficiary_document'] ?? '') . ' ' .
                    ($row['beneficiary_username'] ?? '') . ' ' .
                    ($row['store_name'] ?? '') . ' ' .
                    ($row['ref_code'] ?? '') . ' ' .
                    ($row['detail'] ?? '') . ' ' .
                    ($row['type_label'] ?? '')
                );
                if (strpos($searchContent, $search) === false) {
                    continue;
                }
            }
            $filteredRows[] = $row;
        }

        // Orden DESC (más reciente primero)
        usort($filteredRows, static function ($a, $b) {
            $cmp = strcmp((string) ($b['datetime'] ?? ''), (string) ($a['datetime'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp((string) ($b['id'] ?? ''), (string) ($a['id'] ?? ''));
        });

        // Obtener saldo total disponible real actual del operador
        $operatorUser = $modelUsers->find($operatorId);
        $currentOpBalance = round((float) ($operatorUser['wallet_recharge'] ?? 0), 2);

        $cursorBalance = $currentOpBalance;
        foreach ($filteredRows as &$row) {
            $st = (int) ($row['status'] ?? 0);
            $dir = (string) ($row['direction'] ?? '');
            $amt = round((float) ($row['amount'] ?? 0), 2);

            $row['balance_after'] = max(0.0, round($cursorBalance, 2));

            if ($st === 2 && $dir !== '=') {
                if ($dir === '+') {
                    $cursorBalance = round($cursorBalance - $amt, 2);
                } elseif ($dir === '-') {
                    $cursorBalance = round($cursorBalance + $amt, 2);
                }
            }
        }
        unset($row);

        return [
            'movements' => $filteredRows,
            'stats'     => $stats,
        ];
    }
}

if (! function_exists('bingo_export_csv_payload')) {
    /**
     * Genera el cuerpo CSV (UTF-8 con BOM) para descargas.
     *
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     */
    function bingo_export_csv_payload(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return "\xEF\xBB\xBF";
        }

        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            $normalized = [];
            foreach ((array) $row as $cell) {
                if (is_bool($cell)) {
                    $normalized[] = $cell ? '1' : '0';
                } elseif (is_scalar($cell) || $cell === null) {
                    $normalized[] = (string) ($cell ?? '');
                } else {
                    $normalized[] = json_encode($cell, JSON_UNESCAPED_UNICODE) ?: '';
                }
            }
            fputcsv($handle, $normalized);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return "\xEF\xBB\xBF" . $csv;
    }
}

if (! function_exists('bingo_operator_movements_export_rows')) {
    /**
     * Filas planas para Excel/CSV de los movimientos del Operador.
     *
     * @param list<array<string,mixed>> $movements
     * @return array{headers:list<string>,rows:list<list<mixed>>}
     */
    function bingo_operator_movements_export_rows(array $movements): array
    {
        $headers = [
            'Fecha y Hora',
            'Tipo de Movimiento',
            'Direccion (+/-)',
            'Monto',
            'Punto de Venta / Origen',
            'Estado',
            'Beneficiario / Jugador',
            'Documento / Cedula',
            'Referencia / Codigo',
            'Detalle',
        ];

        $rows = [];
        foreach ($movements as $m) {
            $datetime = (string) ($m['datetime'] ?? '');
            if ($datetime !== '' && strtotime($datetime) !== false) {
                $datetime = date('d/m/Y H:i:s', strtotime($datetime));
            }

            $rows[] = [
                $datetime,
                (string) ($m['type_label'] ?? $m['type'] ?? ''),
                (string) ($m['direction'] ?? ''),
                (float) ($m['amount'] ?? 0),
                (string) ($m['store_name'] ?? ''),
                (string) ($m['status_label'] ?? ''),
                (string) ($m['beneficiary_name'] ?? ''),
                (string) ($m['beneficiary_document'] ?? ''),
                (string) ($m['ref_code'] ?? ''),
                (string) ($m['detail'] ?? ''),
            ];
        }

        return ['headers' => $headers, 'rows' => $rows];
    }
}

if (! function_exists('bingo_fetch_user_commissions_breakdown')) {
    /**
     * Obtiene el desglose completo de comisiones acumuladas (GGR, Recargas, Retiros) y datos bancarios para liquidación.
     *
     * @param int $userId
     * @return array<string,mixed>
     */
    function bingo_fetch_user_commissions_breakdown(int $userId): array
    {
        $empty = [
            'success'                   => false,
            'user_id'                   => $userId,
            'name'                      => '',
            'role_label'                => '',
            'is_operator'               => false,
            'is_store'                  => false,
            'document'                  => '',
            'phone'                     => '',
            'email'                     => '',
            'code'                      => '',
            'username'                  => '',
            'bank_name'                 => '',
            'account_number'            => '',
            'account_type'              => '',
            'recharge_balance'          => 0.0,
            'ggr_commission'            => 0.0,
            'recharge_commission'       => 0.0,
            'withdraw_commission'       => 0.0,
            'total_pending_commissions' => 0.0,
            'period_label'              => 'Período acumulado',
        ];

        if ($userId <= 0) {
            return $empty;
        }

        $modelUsers = new \App\Models\UsersModel();
        $user = $modelUsers->find($userId);
        if (! $user) {
            return $empty;
        }

        $group = (int) ($user['group'] ?? 0);
        $isOperator = $group === bingo_group_operator();
        $isStore = $group === bingo_group_store();

        if (! $isOperator && ! $isStore) {
            return $empty;
        }

        $name = trim((string) ($user['business_name'] ?: ($user['firstname'] . ' ' . $user['lastname'])));
        $roleLabel = $isOperator ? 'Operador' : 'Punto de Venta';
        $accountTypeFormatted = match((string) ($user['account_type'] ?? '')) {
            'savings'  => 'Cuenta de Ahorros',
            'checking' => 'Cuenta Corriente',
            default    => ($user['account_type'] ?: 'No especificado')
        };

        // Saldo recargable actual
        $rechargeBalance = function_exists('wallet_recharge_balance') ? wallet_recharge_balance($user) : (float) ($user['wallet'] ?? 0);

        // 1. Comisión GGR
        $ggrCommission = 0.0;
        if (function_exists('bingo_ggr_affiliate_active') && bingo_ggr_affiliate_active()) {
            $modelGgr = new \App\Models\AffiliateGgrCommissionsModel();
            $affiliateType = $isOperator ? 'operator' : 'store';
            $pendingGgrRows = $modelGgr
                ->where('affiliate_id', $userId)
                ->where('affiliate_type', $affiliateType)
                ->whereIn('status', [0, 1])
                ->findAll();
            foreach ($pendingGgrRows as $grow) {
                $ggrCommission += (float) ($grow['commission_amount'] ?? 0);
            }
        }

        // 2. Comisión por Recargas y Retiros
        $rechargeCommission = 0.0;
        $withdrawCommission = 0.0;

        if ($isStore) {
            $ledger = bingo_build_store_movements_ledger($userId);
            $rechargeCommission = (float) ($ledger['stats']['recharge_commissions_amount'] ?? 0);
            $withdrawCommission = (float) ($ledger['stats']['prize_commissions_amount'] ?? 0);
        } elseif ($isOperator) {
            $opComms = function_exists('bingo_fetch_operator_commissions_summary') ? bingo_fetch_operator_commissions_summary($userId, $user) : [];
            $rechargeCommission = (float) ($opComms['recharge_commissions'] ?? 0);
            $withdrawCommission = (float) ($opComms['prize_commissions'] ?? 0);
        }

        $totalPending = round($ggrCommission + $rechargeCommission + $withdrawCommission, 2);
        $months = [1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio', 7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'];
        $currentMonthName = $months[(int) date('n')] ?? date('F');
        $periodLabel = 'Mes actual: ' . $currentMonthName . ' ' . date('Y');

        return [
            'success'                   => true,
            'user_id'                   => $userId,
            'name'                      => $name,
            'role_label'                => $roleLabel,
            'is_operator'               => $isOperator,
            'is_store'                  => $isStore,
            'document'                  => (string) ($user['document'] ?? ''),
            'phone'                     => (string) ($user['phone'] ?? ''),
            'email'                     => (string) ($user['email'] ?? ''),
            'code'                      => (string) ($user['code'] ?? ''),
            'username'                  => (string) ($user['username'] ?? ''),
            'bank_name'                 => (string) ($user['bank'] ?: 'No registrado'),
            'account_number'            => (string) ($user['account'] ?: 'No registrado'),
            'account_type'              => $accountTypeFormatted,
            'recharge_balance'          => round($rechargeBalance, 2),
            'ggr_commission'            => round($ggrCommission, 2),
            'recharge_commission'       => round($rechargeCommission, 2),
            'withdraw_commission'       => round($withdrawCommission, 2),
            'total_pending_commissions' => $totalPending,
            'period_label'              => $periodLabel,
        ];
    }
}

if (!function_exists('bingo_document_expiry_status')) {
    /**
     * @return array{status:string,label:string,days:?int,expires_at:?string}
     */
    function bingo_document_expiry_status(?array $user): array
    {
        $expires = trim((string) ($user['document_expires_at'] ?? ''));
        if ($expires === '' || $expires === '0000-00-00') {
            return [
                'status' => 'unknown',
                'label' => translate('document expiry not set'),
                'days' => null,
                'expires_at' => null,
            ];
        }

        $expiresTs = strtotime($expires . ' 23:59:59');
        if ($expiresTs === false) {
            return [
                'status' => 'unknown',
                'label' => translate('document expiry not set'),
                'days' => null,
                'expires_at' => null,
            ];
        }

        $days = (int) floor(($expiresTs - time()) / 86400);
        if ($days < 0) {
            return [
                'status' => 'expired',
                'label' => translate('document expired'),
                'days' => $days,
                'expires_at' => $expires,
            ];
        }

        if ($days <= 30) {
            return [
                'status' => 'expiring',
                'label' => translate('document expiring soon'),
                'days' => $days,
                'expires_at' => $expires,
            ];
        }

        return [
            'status' => 'ok',
            'label' => translate('document valid'),
            'days' => $days,
            'expires_at' => $expires,
        ];
    }
}

if (!function_exists('bingo_capture_client_mac')) {
    function bingo_capture_client_mac(?\CodeIgniter\HTTP\RequestInterface $request = null): string
    {
        $request = $request ?? service('request');
        $candidates = [
            (string) $request->getHeaderLine('X-Client-MAC'),
            (string) $request->getHeaderLine('X-Device-MAC'),
            (string) $request->getHeaderLine('Client-MAC'),
            (string) $request->getHeaderLine('Device-MAC'),
            (string) $request->getPost('mac_address'),
            (string) $request->getGet('mac_address'),
            (string) $request->getPost('last_mac'),
            (string) $request->getGet('last_mac'),
            (string) $request->getPost('device_mac'),
            (string) $request->getGet('device_mac'),
            (string) ($request->getCookie('rey_device_mac') ?? ($_COOKIE['rey_device_mac'] ?? '')),
        ];

        foreach ($candidates as $mac) {
            $mac = strtoupper(trim((string) $mac));
            if ($mac !== '' && preg_match('/^([0-9A-F]{2}[:-]){5}([0-9A-F]{2})$/', $mac)) {
                return str_replace('-', ':', $mac);
            }
        }

        // Intento de resolución vía tabla ARP del servidor / red local
        $ip = (string) ($request->getIPAddress() ?: ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($ip !== '' && $ip !== '127.0.0.1' && $ip !== '::1') {
            try {
                $arpOutput = @shell_exec('arp -a ' . escapeshellarg($ip));
                if ($arpOutput && preg_match('/([0-9A-Fa-f]{2}[:-][0-9A-Fa-f]{2}[:-][0-9A-Fa-f]{2}[:-][0-9A-Fa-f]{2}[:-][0-9A-Fa-f]{2}[:-][0-9A-Fa-f]{2})/', $arpOutput, $matches)) {
                    $foundMac = strtoupper(str_replace('-', ':', $matches[1]));
                    if ($foundMac !== 'FF:FF:FF:FF:FF:FF' && $foundMac !== '00:00:00:00:00:00') {
                        return $foundMac;
                    }
                }
            } catch (\Throwable $e) {
                // Ignore shell_exec restriction
            }
        }

        // Fallback automático determinístico por dispositivo (IP + User-Agent) para garantizar que nunca quede vacío
        if ($ip !== '') {
            $ua = (string) ($request->getUserAgent() ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'REYBINGO_DEVICE'));
            $hash = md5($ip . '|' . $ua . '|reybingo_device');
            $hexParts = str_split(strtoupper(substr($hash, 0, 12)), 2);
            if (count($hexParts) === 6) {
                return implode(':', $hexParts);
            }
        }

        return '02:B1:C0:7E:9A:3D';
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

        $affiliate = systemGet('rateOperatorGgrAffiliate');
        if ($affiliate !== null && $affiliate !== '') {
            return max(0, (float) $affiliate);
        }

        return max(0, (float) (systemGet('rateOperatorCommission') ?? 0));
    }
}

if (! function_exists('bingo_operator_recharge_rate')) {
    function bingo_operator_recharge_rate(?array $operator = null): float
    {
        if ($operator !== null) {
            $custom = $operator['store_commission_rate'] ?? null;
            if ($custom !== null && $custom !== '') {
                return max(0, (float) $custom);
            }
        }

        return max(0, (float) (systemGet('rateOperatorRecharge') ?? 0));
    }
}

if (! function_exists('bingo_operator_withdraw_rate')) {
    function bingo_operator_withdraw_rate(?array $operator = null): float
    {
        if ($operator !== null) {
            $custom = $operator['store_prize_commission_rate'] ?? null;
            if ($custom !== null && $custom !== '') {
                return max(0, (float) $custom);
            }
        }

        return max(0, (float) (systemGet('rateOperatorWithdraw') ?? 0));
    }
}

if (! function_exists('bingo_fetch_operator_detailed_commissions_breakdown')) {
    /**
     * Obtiene el desglose completo de comisiones con cálculo de diferencial (spread) para el Operador
     * en sus 3 tasas: GGR, Recargas y Retiros / Pago de Premios.
     *
     * @param int $operatorId
     * @param array<string,mixed> $filters
     * @return array{stats:array<string,mixed>,items:list<array<string,mixed>>,stores:list<array<string,mixed>>}
     */
    function bingo_fetch_operator_detailed_commissions_breakdown(int $operatorId, array $filters = []): array
    {
        if ($operatorId <= 0) {
            return [
                'stats' => [],
                'items' => [],
                'stores' => [],
            ];
        }

        $modelUsers = new \App\Models\UsersModel();
        $modelDeposits = new \App\Models\DepositsModel();
        $modelPayments = new \App\Models\PaymentsModel();
        $modelRetires = new \App\Models\RetiresModel();
        $modelGgr = new \App\Models\AffiliateGgrCommissionsModel();

        $operator = $modelUsers->find($operatorId);
        if (! $operator) {
            return ['stats' => [], 'items' => [], 'stores' => []];
        }

        $stores = $modelUsers
            ->where('group', bingo_group_store())
            ->where('operator_id', $operatorId)
            ->where('deleted', 0)
            ->findAll();

        $storeMap = [];
        $allStoreIds = [];
        foreach ($stores as $st) {
            $sid = (int) $st['id'];
            $allStoreIds[] = $sid;
            $storeMap[$sid] = [
                'user' => $st,
                'name' => trim($st['business_name'] ?: ($st['firstname'] . ' ' . $st['lastname'])) . ' (' . ($st['code'] ?: $st['username']) . ')',
                'recharge_rate' => bingo_store_commission_rate($st),
                'prize_rate' => bingo_store_prize_commission_rate($st),
                'ggr_rate' => function_exists('bingo_store_ggr_commission_rate') ? bingo_store_ggr_commission_rate($st) : 0.0,
            ];
        }

        $operatorGgrRate = function_exists('bingo_ggr_commission_rate_for') ? bingo_ggr_commission_rate_for($operator, 'operator') : bingo_operator_commission_rate($operator);
        $operatorRechargeRate = bingo_operator_recharge_rate($operator);
        $operatorWithdrawRate = bingo_operator_withdraw_rate($operator);

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $storeFilter = trim((string) ($filters['store_id'] ?? 'all'));
        $rateFilter = trim((string) ($filters['rate_type'] ?? 'all'));
        $search = strtolower(trim((string) ($filters['search'] ?? '')));

        $items = [];

        // 1. Recargas a jugadores
        $rechargeStoreIds = array_merge($allStoreIds, [$operatorId]);
        if (! empty($rechargeStoreIds)) {
            $recharges = $modelDeposits
                ->whereIn('store', $rechargeStoreIds)
                ->where('method', 'store player recharge')
                ->orderBy('created_at', 'DESC')
                ->findAll(2500);

            foreach ($recharges as $dep) {
                $stId = (int) ($dep['store'] ?? 0);
                $amt = round((float) ($dep['amount'] ?? 0), 2);
                $stInfo = $storeMap[$stId] ?? null;
                $isDirect = ($stId === $operatorId || ! $stInfo);

                $stRate = $isDirect ? 0.0 : (float) $stInfo['recharge_rate'];
                $opSpread = $isDirect ? $operatorRechargeRate : max(0.0, $operatorRechargeRate - $stRate);

                $stCommission = round($amt * $stRate, 2);
                $opProfit = round($amt * $opSpread, 2);
                $stName = $isDirect ? 'Directo Operador' : ($stInfo['name'] ?? 'Punto de Venta');

                $status = (int) ($dep['status'] ?? 0);

                $items[] = [
                    'id' => 'REC_' . $dep['id'],
                    'datetime' => (string) ($dep['created_at'] ?? $dep['date'] ?? ''),
                    'rate_type' => 'recharge',
                    'rate_type_label' => 'Recarga',
                    'badge_class' => 'bg-info text-white',
                    'icon' => 'fa-duotone fa-solid fa-mobile-screen',
                    'store_id' => $stId,
                    'store_name' => $stName,
                    'base_amount' => $amt,
                    'store_rate' => $stRate,
                    'store_commission' => $stCommission,
                    'operator_rate' => $operatorRechargeRate,
                    'operator_spread' => $opSpread,
                    'operator_profit' => $opProfit,
                    'status' => $status,
                    'status_label' => bingo_status_label_short($status),
                    'ref_code' => 'DEP #' . $dep['id'],
                    'detail' => 'Recarga de saldo por ' . $stName,
                ];
            }
        }

        // 2. Retiros / Pago de premios en efectivo
        if (! empty($rechargeStoreIds)) {
            $retires = $modelPayments
                ->whereIn('user', $rechargeStoreIds)
                ->whereIn('type', ['store_retire_pay', 'store_prize_pay'])
                ->orderBy('created_at', 'DESC')
                ->findAll(2500);

            foreach ($retires as $pay) {
                $stId = (int) ($pay['user'] ?? 0);
                $amt = round((float) ($pay['amount'] ?? 0), 2);
                $stInfo = $storeMap[$stId] ?? null;
                $isDirect = ($stId === $operatorId || ! $stInfo);

                $stRate = $isDirect ? 0.0 : (float) $stInfo['prize_rate'];
                $opSpread = $isDirect ? $operatorWithdrawRate : max(0.0, $operatorWithdrawRate - $stRate);

                $stCommission = round($amt * $stRate, 2);
                $opProfit = round($amt * $opSpread, 2);
                $stName = $isDirect ? 'Directo Operador' : ($stInfo['name'] ?? 'Punto de Venta');

                $status = (int) ($pay['status'] ?? 2);

                $items[] = [
                    'id' => 'RET_' . $pay['id'],
                    'datetime' => (string) ($pay['created_at'] ?? ''),
                    'rate_type' => 'withdraw',
                    'rate_type_label' => 'Pago Retiro',
                    'badge_class' => 'bg-danger text-white',
                    'icon' => 'fa-duotone fa-solid fa-money-bill-transfer',
                    'store_id' => $stId,
                    'store_name' => $stName,
                    'base_amount' => $amt,
                    'store_rate' => $stRate,
                    'store_commission' => $stCommission,
                    'operator_rate' => $operatorWithdrawRate,
                    'operator_spread' => $opSpread,
                    'operator_profit' => $opProfit,
                    'status' => $status,
                    'status_label' => 'Pagado',
                    'ref_code' => 'PAY #' . $pay['id'],
                    'detail' => 'Pago de retiro en efectivo por ' . $stName,
                ];
            }
        }

        // 3. GGR Afiliados / Red de Puntos de Venta
        if (function_exists('bingo_ggr_affiliate_active') && bingo_ggr_affiliate_active()) {
            $ggrRows = $modelGgr
                ->groupStart()
                    ->where('affiliate_id', $operatorId)
                    ->where('affiliate_type', 'operator')
                ->groupEnd()
                ->orGroupStart()
                    ->whereIn('affiliate_id', !empty($allStoreIds) ? $allStoreIds : [0])
                    ->where('affiliate_type', 'store')
                ->groupEnd()
                ->orderBy('created_at', 'DESC')
                ->findAll(2000);

            foreach ($ggrRows as $grow) {
                $stId = (int) ($grow['affiliate_id'] ?? 0);
                $affType = (string) ($grow['affiliate_type'] ?? '');
                $ggrAmt = round((float) ($grow['ggr_amount'] ?? 0), 2);
                $stInfo = $storeMap[$stId] ?? null;
                $isDirect = ($affType === 'operator' || $stId === $operatorId || ! $stInfo);

                $stRate = $isDirect ? 0.0 : (float) ($stInfo['ggr_rate'] ?? 0);
                $opSpread = $isDirect ? $operatorGgrRate : max(0.0, $operatorGgrRate - $stRate);

                $stCommission = $isDirect ? 0.0 : round((float) ($grow['commission_amount'] ?? ($ggrAmt * $stRate)), 2);
                $opProfit = round($ggrAmt * $opSpread, 2);
                $stName = $isDirect ? 'Directo Operador' : ($stInfo['name'] ?? 'Punto de Venta');

                $status = (int) ($grow['status'] ?? 0);

                $items[] = [
                    'id' => 'GGR_' . $grow['id'],
                    'datetime' => (string) ($grow['created_at'] ?? $grow['period_date'] ?? ''),
                    'rate_type' => 'ggr',
                    'rate_type_label' => 'GGR Afiliados',
                    'badge_class' => 'bg-warning text-dark',
                    'icon' => 'fa-duotone fa-solid fa-chart-pie',
                    'store_id' => $stId,
                    'store_name' => $stName,
                    'base_amount' => $ggrAmt,
                    'store_rate' => $stRate,
                    'store_commission' => $stCommission,
                    'operator_rate' => $operatorGgrRate,
                    'operator_spread' => $opSpread,
                    'operator_profit' => $opProfit,
                    'status' => $status,
                    'status_label' => $status === 1 ? 'Liquidada' : 'Pendiente',
                    'ref_code' => 'GGR #' . $grow['id'],
                    'detail' => 'Comisión GGR por actividad en ' . $stName,
                ];
            }
        }

        // Totales globales para las 3 Tarjetas
        $stats = [
            'ggr' => [
                'rate' => $operatorGgrRate,
                'total_base' => 0.0,
                'stores_earned' => 0.0,
                'operator_earned' => 0.0,
                'count' => 0,
            ],
            'recharge' => [
                'rate' => $operatorRechargeRate,
                'total_base' => 0.0,
                'stores_earned' => 0.0,
                'operator_earned' => 0.0,
                'count' => 0,
            ],
            'withdraw' => [
                'rate' => $operatorWithdrawRate,
                'total_base' => 0.0,
                'stores_earned' => 0.0,
                'operator_earned' => 0.0,
                'count' => 0,
            ],
            'total_operator_profit' => 0.0,
            'total_stores_earned' => 0.0,
        ];

        foreach ($items as $item) {
            $t = (string) ($item['rate_type'] ?? '');
            $base = (float) ($item['base_amount'] ?? 0);
            $stEarn = (float) ($item['store_commission'] ?? 0);
            $opEarn = (float) ($item['operator_profit'] ?? 0);

            if (isset($stats[$t])) {
                $stats[$t]['total_base'] += $base;
                $stats[$t]['stores_earned'] += $stEarn;
                $stats[$t]['operator_earned'] += $opEarn;
                $stats[$t]['count']++;
            }
            $stats['total_operator_profit'] += $opEarn;
            $stats['total_stores_earned'] += $stEarn;
        }

        // Filtrado de items para la tabla
        $filteredItems = [];
        foreach ($items as $item) {
            $itemDate = substr((string) ($item['datetime'] ?? ''), 0, 10);
            if ($dateFrom !== '' && $itemDate < $dateFrom) {
                continue;
            }
            if ($dateTo !== '' && $itemDate > $dateTo) {
                continue;
            }
            if ($storeFilter !== '' && $storeFilter !== 'all') {
                if ($storeFilter === 'operator' && (int) $item['store_id'] !== $operatorId) {
                    continue;
                } elseif ($storeFilter !== 'operator' && (int) $item['store_id'] !== (int) $storeFilter) {
                    continue;
                }
            }
            if ($rateFilter !== '' && $rateFilter !== 'all') {
                if (($item['rate_type'] ?? '') !== $rateFilter) {
                    continue;
                }
            }
            if ($search !== '') {
                $str = strtolower(
                    ($item['store_name'] ?? '') . ' ' .
                    ($item['ref_code'] ?? '') . ' ' .
                    ($item['detail'] ?? '') . ' ' .
                    ($item['rate_type_label'] ?? '')
                );
                if (strpos($str, $search) === false) {
                    continue;
                }
            }
            $filteredItems[] = $item;
        }

        // Ordenar DESC
        usort($filteredItems, static function ($a, $b) {
            return strcmp((string) ($b['datetime'] ?? ''), (string) ($a['datetime'] ?? ''));
        });

        return [
            'stats' => $stats,
            'items' => $filteredItems,
            'stores' => $stores,
        ];
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

if (! function_exists('bingo_fetch_store_detailed_commissions_breakdown')) {
    /**
     * Obtiene el desglose completo de comisiones para el Punto de Venta (Store)
     * en sus 3 tasas: GGR Afiliados, Recargas a Jugadores y Retiros / Pago de Premios.
     *
     * @param int $storeId
     * @param array<string,mixed> $filters
     * @return array{stats:array<string,mixed>,items:list<array<string,mixed>>}
     */
    function bingo_fetch_store_detailed_commissions_breakdown(int $storeId, array $filters = []): array
    {
        if ($storeId <= 0) {
            return ['stats' => [], 'items' => []];
        }

        $modelUsers = new \App\Models\UsersModel();
        $modelDeposits = new \App\Models\DepositsModel();
        $modelPayments = new \App\Models\PaymentsModel();
        $modelRetires = new \App\Models\RetiresModel();
        $modelGgr = new \App\Models\AffiliateGgrCommissionsModel();

        $store = $modelUsers->find($storeId);
        if (! $store) {
            return ['stats' => [], 'items' => []];
        }

        $rechargeRate = bingo_store_commission_rate($store);
        $prizeRate = bingo_store_prize_commission_rate($store);
        $ggrRate = function_exists('bingo_store_ggr_commission_rate') ? bingo_store_ggr_commission_rate($store) : 0.0;

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $rateFilter = trim((string) ($filters['rate_type'] ?? 'all'));
        $search = strtolower(trim((string) ($filters['search'] ?? '')));

        $items = [];

        // 1. Recargas a jugadores realizadas por este Punto de Venta
        $recharges = $modelDeposits
            ->where('store', $storeId)
            ->where('method', 'store player recharge')
            ->orderBy('created_at', 'DESC')
            ->findAll(2500);

        foreach ($recharges as $dep) {
            $amt = round((float) ($dep['amount'] ?? 0), 2);
            $commission = round($amt * $rechargeRate, 2);
            $status = (int) ($dep['status'] ?? 0);
            $pId = (int) ($dep['user'] ?? 0);
            $pUser = $pId > 0 ? $modelUsers->find($pId) : null;
            $pName = $pUser ? trim(($pUser['firstname'] ?? '') . ' ' . ($pUser['lastname'] ?? '')) : 'Jugador';
            $pDoc = (string) ($dep['document'] ?? $pUser['document'] ?? '');
            $pUsername = (string) ($pUser['username'] ?? '');

            $items[] = [
                'id' => 'REC_' . $dep['id'],
                'datetime' => (string) ($dep['created_at'] ?? $dep['date'] ?? ''),
                'rate_type' => 'recharge',
                'rate_type_label' => 'Recarga',
                'badge_class' => 'bg-info text-white',
                'icon' => 'fa-duotone fa-solid fa-mobile-screen',
                'base_amount' => $amt,
                'store_rate' => $rechargeRate,
                'commission_amount' => $commission,
                'status' => $status,
                'status_label' => bingo_status_label_short($status),
                'player_name' => $pName,
                'player_doc' => $pDoc,
                'player_username' => $pUsername,
                'ref_code' => 'DEP #' . $dep['id'],
                'detail' => 'Comisión por recarga a ' . $pName . ($pDoc !== '' ? ' (Cédula: ' . $pDoc . ')' : ''),
            ];
        }

        // 2. Retiros / Pago de premios en efectivo pagados por este Punto de Venta
        $retires = $modelPayments
            ->where('user', $storeId)
            ->whereIn('type', ['store_retire_pay', 'store_prize_pay'])
            ->orderBy('created_at', 'DESC')
            ->findAll(2500);

        foreach ($retires as $pay) {
            $retireId = (int) ($pay['type_id'] ?? 0);
            $retireRecord = $retireId > 0 ? $modelRetires->find($retireId) : null;
            $pId = $retireRecord ? (int) ($retireRecord['user'] ?? 0) : 0;
            $pUser = $pId > 0 ? $modelUsers->find($pId) : null;
            $pName = $pUser ? trim(($pUser['firstname'] ?? '') . ' ' . ($pUser['lastname'] ?? '')) : 'Jugador';
            $pDoc = (string) ($retireRecord['document'] ?? $pUser['document'] ?? '');
            $retCode = (string) ($retireRecord['account'] ?? '');
            $amt = round((float) ($pay['amount'] ?? 0), 2);
            $commission = round($amt * $prizeRate, 2);
            $status = (int) ($pay['status'] ?? 2);

            $items[] = [
                'id' => 'RET_' . $pay['id'],
                'datetime' => (string) ($pay['created_at'] ?? ''),
                'rate_type' => 'withdraw',
                'rate_type_label' => 'Pago Retiro',
                'badge_class' => 'bg-danger text-white',
                'icon' => 'fa-duotone fa-solid fa-money-bill-transfer',
                'base_amount' => $amt,
                'store_rate' => $prizeRate,
                'commission_amount' => $commission,
                'status' => $status,
                'status_label' => 'Pagado',
                'player_name' => $pName,
                'player_doc' => $pDoc,
                'player_username' => (string) ($pUser['username'] ?? ''),
                'ref_code' => $retCode !== '' ? $retCode : ('RET #' . $retireId),
                'detail' => 'Comisión por pago de retiro a ' . $pName . ($retCode !== '' ? ' (Código: ' . $retCode . ')' : ''),
            ];
        }

        // 3. GGR Afiliados / Jugadores vinculados
        if (function_exists('bingo_ggr_affiliate_active') && bingo_ggr_affiliate_active()) {
            $ggrRows = $modelGgr
                ->where('affiliate_id', $storeId)
                ->where('affiliate_type', 'store')
                ->orderBy('created_at', 'DESC')
                ->findAll(2000);

            foreach ($ggrRows as $grow) {
                $ggrAmt = round((float) ($grow['ggr_amount'] ?? 0), 2);
                $commission = round((float) ($grow['commission_amount'] ?? ($ggrAmt * $ggrRate)), 2);
                $status = (int) ($grow['status'] ?? 0);
                $pId = (int) ($grow['player_id'] ?? 0);
                $pUser = $pId > 0 ? $modelUsers->find($pId) : null;
                $pName = $pUser ? trim(($pUser['firstname'] ?? '') . ' ' . ($pUser['lastname'] ?? '')) : 'Jugadores vinculados';

                $items[] = [
                    'id' => 'GGR_' . $grow['id'],
                    'datetime' => (string) ($grow['created_at'] ?? $grow['period_date'] ?? ''),
                    'rate_type' => 'ggr',
                    'rate_type_label' => 'GGR Afiliados',
                    'badge_class' => 'bg-warning text-dark',
                    'icon' => 'fa-duotone fa-solid fa-chart-pie',
                    'base_amount' => $ggrAmt,
                    'store_rate' => $ggrRate,
                    'commission_amount' => $commission,
                    'status' => $status,
                    'status_label' => $status === 1 ? 'Liquidada' : 'Pendiente',
                    'player_name' => $pName,
                    'player_doc' => (string) ($pUser['document'] ?? ''),
                    'player_username' => (string) ($pUser['username'] ?? ''),
                    'ref_code' => 'GGR #' . $grow['id'],
                    'detail' => 'Comisión GGR por actividad de juego en el período',
                ];
            }
        }

        // Totales globales para las 3 Tarjetas del Punto de Venta
        $stats = [
            'ggr' => [
                'rate' => $ggrRate,
                'total_base' => 0.0,
                'total_earned' => 0.0,
                'count' => 0,
            ],
            'recharge' => [
                'rate' => $rechargeRate,
                'total_base' => 0.0,
                'total_earned' => 0.0,
                'count' => 0,
            ],
            'withdraw' => [
                'rate' => $prizeRate,
                'total_base' => 0.0,
                'total_earned' => 0.0,
                'count' => 0,
            ],
            'total_commissions_earned' => 0.0,
        ];

        foreach ($items as $item) {
            $t = (string) ($item['rate_type'] ?? '');
            $base = (float) ($item['base_amount'] ?? 0);
            $earn = (float) ($item['commission_amount'] ?? 0);

            if (isset($stats[$t])) {
                $stats[$t]['total_base'] += $base;
                $stats[$t]['total_earned'] += $earn;
                $stats[$t]['count']++;
            }
            $stats['total_commissions_earned'] += $earn;
        }

        // Filtrado de items
        $filteredItems = [];
        foreach ($items as $item) {
            $itemDate = substr((string) ($item['datetime'] ?? ''), 0, 10);
            if ($dateFrom !== '' && $itemDate < $dateFrom) {
                continue;
            }
            if ($dateTo !== '' && $itemDate > $dateTo) {
                continue;
            }
            if ($rateFilter !== '' && $rateFilter !== 'all') {
                if (($item['rate_type'] ?? '') !== $rateFilter) {
                    continue;
                }
            }
            if ($search !== '') {
                $str = strtolower(
                    ($item['player_name'] ?? '') . ' ' .
                    ($item['player_doc'] ?? '') . ' ' .
                    ($item['ref_code'] ?? '') . ' ' .
                    ($item['detail'] ?? '') . ' ' .
                    ($item['rate_type_label'] ?? '')
                );
                if (strpos($str, $search) === false) {
                    continue;
                }
            }
            $filteredItems[] = $item;
        }

        usort($filteredItems, static function ($a, $b) {
            return strcmp((string) ($b['datetime'] ?? ''), (string) ($a['datetime'] ?? ''));
        });

        return [
            'stats' => $stats,
            'items' => $filteredItems,
        ];
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
    function bingo_sum_operator_store_affiliate_commissions(int $operatorId, ?int $storeId = null, ?string $dateFrom = null, ?string $dateTo = null): float
    {
        if ($operatorId <= 0) {
            return 0.0;
        }

        $dateFrom = $dateFrom !== null ? trim($dateFrom) : '';
        $dateTo = $dateTo !== null ? trim($dateTo) : '';
        if ($dateFrom !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = '';
        }
        if ($dateTo !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = '';
        }
        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
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
        if ($dateFrom !== '') {
            $builder->where('created_at >=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo !== '') {
            $builder->where('created_at <=', $dateTo . ' 23:59:59');
        }

        $total = 0.0;
        foreach ($builder->findAll() as $row) {
            $total += (float) ($row['amount'] ?? 0);
        }

        return round($total, 2);
    }
}

if (!function_exists('bingo_fetch_player_affiliated_store')) {
    /**
     * Obtiene el Punto de Venta al que está afiliado un jugador.
     */
    function bingo_fetch_player_affiliated_store(array $player): ?array
    {
        if ((int) ($player['group'] ?? -1) !== bingo_group_player()) {
            return null;
        }

        $storeId = (int) ($player['referred_store_id'] ?? 0);
        if ($storeId <= 0) {
            $storeId = (int) ($player['affiliate_signup_store_id'] ?? 0);
        }

        if ($storeId <= 0) {
            return null;
        }

        $modelUsers = new \App\Models\UsersModel();
        $store = $modelUsers
            ->select('id, business_name, code, username, firstname, lastname, status, deleted')
            ->where('id', $storeId)
            ->where('group', bingo_group_store())
            ->first();

        return $store ?: null;
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
        // Permitir partidas activas (1) y programadas/pospuestas (2)
        if (!$game || ! in_array((int) ($game['status'] ?? -1), [1, 2], true)) {
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

        // El Ruletazo de bienvenida solo aplica con KYC verificado.
        if (!bingo_user_kyc_verified($player)) {
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
