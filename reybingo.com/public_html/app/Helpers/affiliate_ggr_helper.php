<?php

/**
 * Sistema de afiliados basado en GGR (Gross Gaming Revenue).
 * GGR = total apostado - total ganado por el jugador referido.
 * Puede ser negativo (ej. apostó 6 y ganó 6.72 → GGR = -0.72).
 */

if (! function_exists('bingo_ensure_affiliate_ggr_schema')) {
    function bingo_ensure_affiliate_ggr_schema(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            $db = \Config\Database::connect();
            $forge = \Config\Database::forge();

            if (! $db->tableExists('affiliate_ggr_events')) {
                $forge->addField([
                    'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'player_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                    'game_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                    'event_type'     => ['type' => 'VARCHAR', 'constraint' => 20],
                    'amount'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
                    'reference_type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                    'reference_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                    'created_at'     => ['type' => 'DATETIME', 'null' => true],
                    'updated_at'     => ['type' => 'DATETIME', 'null' => true],
                ]);
                $forge->addKey('id', true);
                $forge->addKey(['game_id', 'player_id']);
                $forge->createTable('affiliate_ggr_events', true);
            }

            if (! $db->tableExists('affiliate_ggr_commissions')) {
                $forge->addField([
                    'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'player_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                    'affiliate_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                    'affiliate_type'      => ['type' => 'VARCHAR', 'constraint' => 20],
                    'game_id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                    'total_stake'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
                    'total_payout'        => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
                    'ggr_amount'          => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
                    'commission_rate'     => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => '0.0000'],
                    'commission_amount'   => ['type' => 'DECIMAL', 'constraint' => '18,8', 'default' => '0.00000000'],
                    'status'              => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0],
                    'payment_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                    'period_date'         => ['type' => 'DATE', 'null' => true],
                    'created_at'          => ['type' => 'DATETIME', 'null' => true],
                    'updated_at'          => ['type' => 'DATETIME', 'null' => true],
                ]);
                $forge->addKey('id', true);
                $forge->addUniqueKey(['player_id', 'affiliate_id', 'affiliate_type', 'game_id']);
                $forge->createTable('affiliate_ggr_commissions', true);
            }

            if (! $db->tableExists('affiliate_ggr_monthly_settlements')) {
                $forge->addField([
                    'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'affiliate_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                    'affiliate_type'    => ['type' => 'VARCHAR', 'constraint' => 20],
                    'period_month'      => ['type' => 'DATE'],
                    'total_stake'       => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
                    'total_payout'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
                    'total_ggr'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => '0.00'],
                    'commission_amount' => ['type' => 'DECIMAL', 'constraint' => '18,8', 'default' => '0.00000000'],
                    'commission_count'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
                    'payment_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                    'status'            => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 2],
                    'created_at'        => ['type' => 'DATETIME', 'null' => true],
                    'updated_at'        => ['type' => 'DATETIME', 'null' => true],
                ]);
                $forge->addKey('id', true);
                $forge->addUniqueKey(['affiliate_id', 'affiliate_type', 'period_month']);
                $forge->createTable('affiliate_ggr_monthly_settlements', true);
            }

            if ($db->tableExists('users')) {
                if (! $db->fieldExists('operator_commission_rate', 'users')) {
                    $forge->addColumn('users', [
                        'operator_commission_rate' => [
                            'type'       => 'DECIMAL',
                            'constraint' => '8,4',
                            'null'       => true,
                            'default'    => null,
                            'after'      => 'store_commission_rate',
                        ],
                    ]);
                }
                if (! $db->fieldExists('ggr_commission_rate', 'users')) {
                    $forge->addColumn('users', [
                        'ggr_commission_rate' => [
                            'type'       => 'DECIMAL',
                            'constraint' => '8,4',
                            'null'       => true,
                            'default'    => null,
                            'after'      => 'operator_commission_rate',
                        ],
                    ]);
                }
                if (! $db->fieldExists('affiliate_cpa_amount', 'users')) {
                    $forge->addColumn('users', [
                        'affiliate_cpa_amount' => [
                            'type'       => 'DECIMAL',
                            'constraint' => '10,2',
                            'null'       => true,
                            'default'    => null,
                            'after'      => 'ggr_commission_rate',
                        ],
                    ]);
                }
            }

            if ($db->tableExists('affiliate_ggr_commissions') && $db->fieldExists('commission_amount', 'affiliate_ggr_commissions')) {
                $db->query('ALTER TABLE `affiliate_ggr_commissions` MODIFY `commission_amount` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000');
            }
            if ($db->tableExists('affiliate_ggr_monthly_settlements') && $db->fieldExists('commission_amount', 'affiliate_ggr_monthly_settlements')) {
                $db->query('ALTER TABLE `affiliate_ggr_monthly_settlements` MODIFY `commission_amount` DECIMAL(18,8) NOT NULL DEFAULT 0.00000000');
            }

            if ($db->tableExists('system')) {
                $defaults = [
                    'rateAffiliateCpa'          => '0',
                    'rateStoreGgrCommission'    => '0',
                    'ggrSettlementMode'         => 'monthly',
                    'autoApproveGgrCommissions' => '0',
                ];
                foreach ($defaults as $key => $value) {
                    if ($db->table('system')->where('key', $key)->countAllResults() === 0) {
                        $db->table('system')->insert(['key' => $key, 'value' => $value]);
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'No se pudo actualizar el esquema GGR de afiliados: ' . $e->getMessage());
        }
    }
}

if (! function_exists('bingo_ggr_affiliate_active')) {
    /** El GGR se calcula siempre: total apostado − total ganado. */
    function bingo_ggr_affiliate_active(): bool
    {
        bingo_ensure_affiliate_ggr_schema();

        return true;
    }
}

if (! function_exists('bingo_affiliate_commission_mode')) {
    function bingo_affiliate_commission_mode(): string
    {
        return 'hybrid';
    }
}

if (! function_exists('bingo_ggr_settlement_mode')) {
    function bingo_ggr_settlement_mode(): string
    {
        $mode = strtolower(trim((string) (systemGet('ggrSettlementMode') ?? 'monthly')));
        if ($mode === 'immediate') {
            return 'daily';
        }

        return in_array($mode, ['daily', 'weekly', 'monthly'], true) ? $mode : 'monthly';
    }
}

if (! function_exists('bingo_ggr_pays_monthly')) {
    function bingo_ggr_pays_monthly(): bool
    {
        // weekly acumula como mensual hasta tener cierre semanal dedicado
        return in_array(bingo_ggr_settlement_mode(), ['monthly', 'weekly'], true);
    }
}

if (! function_exists('bingo_ggr_should_auto_approve')) {
    function bingo_ggr_should_auto_approve(): bool
    {
        if (bingo_ggr_pays_monthly()) {
            return false;
        }

        return (string) (systemGet('autoApproveGgrCommissions') ?? '1') === '1';
    }
}

if (! function_exists('bingo_ggr_period_bounds')) {
    /**
     * @return array{start:string,end:string,month_date:string,label:string}
     */
    function bingo_ggr_period_bounds(string $yearMonth): array
    {
        $periodStart = $yearMonth . '-01';
        $periodEnd = date('Y-m-t', strtotime($periodStart));

        return [
            'start'       => $periodStart,
            'end'         => $periodEnd,
            'month_date'  => $periodStart,
            'label'       => date('m/Y', strtotime($periodStart)),
        ];
    }
}

if (! function_exists('bingo_resolve_store_operator')) {
    function bingo_resolve_store_operator(array $store): ?array
    {
        $operatorId = (int) ($store['operator_id'] ?? 0);
        if ($operatorId <= 0) {
            return null;
        }

        $modelUsers = new \App\Models\UsersModel();
        $operator = $modelUsers->find($operatorId);
        if (! $operator
            || (int) ($operator['group'] ?? -1) !== bingo_group_operator()
            || (int) ($operator['deleted'] ?? 0) !== 0) {
            return null;
        }

        return $operator;
    }
}

if (! function_exists('bingo_operator_ggr_margin_for_store')) {
    function bingo_operator_ggr_margin_for_store(array $store, ?array $operator = null): float
    {
        $operator = $operator ?? bingo_resolve_store_operator($store);
        if (! $operator) {
            return 0.0;
        }

        $operatorTotal = bingo_operator_commission_rate($operator);
        $storeRate = bingo_store_ggr_commission_rate($store);

        return max(0.0, round($operatorTotal - $storeRate, 4));
    }
}

if (! function_exists('bingo_validate_store_ggr_rate')) {
    /**
     * @return array{valid:bool,rate:?float,message?:string}
     */
    function bingo_validate_store_ggr_rate($rateInput, array $store, ?array $operator = null): array
    {
        $rate = ($rateInput === null || $rateInput === '')
            ? null
            : bingo_parse_store_commission_rate_post($rateInput);

        if ($rate === null) {
            return ['valid' => true, 'rate' => null];
        }

        if ($rate < 0 || $rate > 1) {
            return ['valid' => false, 'rate' => null, 'message' => translate('invalid request')];
        }

        $operator = $operator ?? bingo_resolve_store_operator($store);
        if ($operator) {
            $operatorTotal = bingo_operator_commission_rate($operator);
            if ($rate > $operatorTotal + 0.00001) {
                return [
                    'valid'   => false,
                    'rate'    => null,
                    'message' => translate('store ggr rate exceeds operator total'),
                ];
            }
        }

        return ['valid' => true, 'rate' => round($rate, 4)];
    }
}

if (! function_exists('bingo_ggr_effective_commission_rate')) {
    /**
     * Tasa efectiva de comisión GGR.
     * El operador recibe el margen: tasa total del operador − tasa del PV afiliador del jugador.
     */
    function bingo_ggr_effective_commission_rate(int $playerId, array $affiliateUser, string $affiliateType): float
    {
        if ($affiliateType === 'operator') {
            $modelUsers = new \App\Models\UsersModel();
            $player = $modelUsers->find($playerId);
            $storeId = (int) ($player['referred_store_id'] ?? 0);
            $store = $storeId > 0 ? $modelUsers->find($storeId) : null;
            $storeRate = $store ? bingo_ggr_commission_rate_for($store, 'store') : 0.0;
            $operatorTotal = bingo_ggr_commission_rate_for($affiliateUser, 'operator');

            return max(0.0, round($operatorTotal - $storeRate, 4));
        }

        return bingo_ggr_commission_rate_for($affiliateUser, $affiliateType);
    }
}

if (! function_exists('bingo_store_ggr_commission_rate')) {
    function bingo_store_ggr_commission_rate(array $store): float
    {
        $custom = $store['ggr_commission_rate'] ?? null;
        if ($custom !== null && $custom !== '') {
            $rate = max(0, (float) $custom);
        } else {
            $affiliate = systemGet('rateStoreGgrAffiliate');
            if ($affiliate !== null && $affiliate !== '') {
                $rate = max(0, (float) $affiliate);
            } else {
                $rate = max(0, (float) (systemGet('rateStoreGgrCommission') ?? 0));
            }
        }

        $operator = bingo_resolve_store_operator($store);
        if ($operator) {
            $operatorTotal = bingo_operator_commission_rate($operator);
            $rate = min($rate, $operatorTotal);
        }

        return max(0, $rate);
    }
}

if (! function_exists('bingo_ggr_commission_rate_for')) {
    /**
     * @param array|null $affiliate Usuario afiliado (tienda, operador o jugador)
     */
    function bingo_ggr_commission_rate_for(?array $affiliate, string $affiliateType): float
    {
        if (! $affiliate) {
            return 0.0;
        }

        if ($affiliateType === 'store') {
            return bingo_store_ggr_commission_rate($affiliate);
        }

        if ($affiliateType === 'operator') {
            return bingo_operator_commission_rate($affiliate);
        }

        $custom = $affiliate['ggr_commission_rate'] ?? null;
        if ($custom !== null && $custom !== '') {
            return max(0, (float) $custom);
        }

        return match ($affiliateType) {
            'player' => max(0, (float) (systemGet('rateReferrals') ?? 0)),
            default  => 0.0,
        };
    }
}

if (! function_exists('bingo_resolve_player_affiliate_chain')) {
    /**
     * @return list<array{id:int,type:string,user:array}>
     */
    function bingo_resolve_player_affiliate_chain(int $playerId): array
    {
        bingo_ensure_users_schema();

        $modelUsers = new \App\Models\UsersModel();
        $modelReferrals = new \App\Models\ReferralsModel();

        $player = $modelUsers->find($playerId);
        if (! $player || (int) ($player['group'] ?? -1) !== bingo_group_player()) {
            return [];
        }

        $chain = [];
        $seen = [];

        $storeId = (int) ($player['referred_store_id'] ?? 0);
        if ($storeId > 0) {
            $store = $modelUsers->find($storeId);
            if ($store
                && (int) ($store['group'] ?? -1) === bingo_group_store()
                && (int) ($store['deleted'] ?? 0) === 0
                && (int) ($store['status'] ?? 0) === 1) {
                $chain[] = ['id' => $storeId, 'type' => 'store', 'user' => $store];
                $seen['store:' . $storeId] = true;

                $operatorId = (int) ($store['operator_id'] ?? 0);
                if ($operatorId > 0 && ! isset($seen['operator:' . $operatorId])) {
                    $operator = $modelUsers->find($operatorId);
                    if ($operator
                        && (int) ($operator['group'] ?? -1) === bingo_group_operator()
                        && (int) ($operator['deleted'] ?? 0) === 0
                        && (int) ($operator['status'] ?? 0) === 1) {
                        $chain[] = ['id' => $operatorId, 'type' => 'operator', 'user' => $operator];
                        $seen['operator:' . $operatorId] = true;
                    }
                }
            }
        }

        $referral = $modelReferrals->where('id_referrer', $playerId)->first();
        if ($referral) {
            $referrerId = (int) ($referral['id_referred'] ?? 0);
            if ($referrerId > 0 && ! isset($seen['player:' . $referrerId])) {
                $referrer = $modelUsers->find($referrerId);
                if ($referrer
                    && (int) ($referrer['group'] ?? -1) === bingo_group_player()
                    && (int) ($referrer['deleted'] ?? 0) === 0
                    && (int) ($referrer['status'] ?? 0) === 1) {
                    $chain[] = ['id' => $referrerId, 'type' => 'player', 'user' => $referrer];
                }
            }
        }

        return $chain;
    }
}

if (! function_exists('bingo_record_ggr_stake')) {
    function bingo_record_ggr_stake(int $playerId, int $gameId, float $amount, string $referenceType = 'carton_purchase', ?int $referenceId = null): void
    {
        if (! bingo_ggr_affiliate_active() || $playerId <= 0 || $gameId <= 0 || $amount <= 0) {
            return;
        }

        if (bingo_resolve_player_affiliate_chain($playerId) === []) {
            return;
        }

        bingo_ensure_affiliate_ggr_schema();

        $model = new \App\Models\AffiliateGgrEventsModel();
        if ($referenceId !== null && $referenceId > 0) {
            $exists = $model
                ->where('player_id', $playerId)
                ->where('game_id', $gameId)
                ->where('event_type', 'stake')
                ->where('reference_type', $referenceType)
                ->where('reference_id', $referenceId)
                ->countAllResults();
            if ($exists > 0) {
                return;
            }
        }

        $model->insert([
            'player_id'      => $playerId,
            'game_id'        => $gameId,
            'event_type'     => 'stake',
            'amount'         => round($amount, 2),
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
        ]);
    }
}

if (! function_exists('bingo_record_ggr_payout')) {
    function bingo_record_ggr_payout(int $playerId, int $gameId, float $amount, string $referenceType = 'award', ?int $referenceId = null): void
    {
        if (! bingo_ggr_affiliate_active() || $playerId <= 0 || $gameId <= 0 || $amount <= 0) {
            return;
        }

        if (bingo_resolve_player_affiliate_chain($playerId) === []) {
            return;
        }

        bingo_ensure_affiliate_ggr_schema();

        $model = new \App\Models\AffiliateGgrEventsModel();
        if ($referenceId !== null && $referenceId > 0) {
            $exists = $model
                ->where('player_id', $playerId)
                ->where('game_id', $gameId)
                ->where('event_type', 'payout')
                ->where('reference_type', $referenceType)
                ->where('reference_id', $referenceId)
                ->countAllResults();
            if ($exists > 0) {
                return;
            }
        }

        $model->insert([
            'player_id'      => $playerId,
            'game_id'        => $gameId,
            'event_type'     => 'payout',
            'amount'         => round($amount, 2),
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
        ]);
    }
}

if (! function_exists('bingo_after_carton_purchase')) {
    function bingo_after_carton_purchase(int $playerId, int $gameId, float $totalCost, array $cartonIds = []): void
    {
        if ($totalCost <= 0) {
            return;
        }

        if ($cartonIds !== []) {
            $price = round($totalCost / count($cartonIds), 2);
            $allocated = 0.0;
            $lastIndex = count($cartonIds) - 1;
            foreach ($cartonIds as $index => $cartonId) {
                $amount = ($index === $lastIndex)
                    ? round($totalCost - $allocated, 2)
                    : $price;
                $allocated += $amount;
                bingo_record_ggr_stake($playerId, $gameId, $amount, 'carton', (int) $cartonId);
            }

            return;
        }

        bingo_record_ggr_stake($playerId, $gameId, $totalCost, 'carton_purchase', null);
    }
}

if (! function_exists('bingo_ggr_payment_type')) {
    function bingo_ggr_payment_type(string $affiliateType): string
    {
        return match ($affiliateType) {
            'store'    => 'ggr_store_commission',
            'operator' => 'ggr_operator_commission',
            'player'   => 'ggr_player_commission',
            default    => 'ggr_commission',
        };
    }
}

if (! function_exists('bingo_credit_ggr_commission')) {
    /**
     * @return array{success:bool,message?:string,payment_id?:int}
     */
    function bingo_credit_ggr_commission(array $commission, ?int $fromUserId = null): array
    {
        bingo_ensure_affiliate_ggr_schema();

        $commissionId = (int) ($commission['id'] ?? 0);
        $affiliateId = (int) ($commission['affiliate_id'] ?? 0);
        $affiliateType = (string) ($commission['affiliate_type'] ?? '');
        $amount = (float) ($commission['commission_amount'] ?? 0);

        if ($commissionId <= 0 || $affiliateId <= 0 || $amount <= 0) {
            return ['success' => false, 'message' => 'Comisión inválida'];
        }

        if ((int) ($commission['status'] ?? 0) === 2) {
            return ['success' => false, 'message' => 'Comisión ya pagada'];
        }

        $modelCommissions = new \App\Models\AffiliateGgrCommissionsModel();
        $modelPayments = new \App\Models\PaymentsModel();
        $modelNotifications = new \App\Models\NotificationsModel();
        $modelUsers = new \App\Models\UsersModel();

        $paymentType = bingo_ggr_payment_type($affiliateType);
        $existing = $modelPayments
            ->where('user', $affiliateId)
            ->where('type', $paymentType)
            ->where('type_id', $commissionId)
            ->countAllResults();

        if ($existing > 0) {
            $modelCommissions->update($commissionId, ['status' => 2]);

            return ['success' => false, 'message' => 'Comisión ya registrada en pagos'];
        }

        wallet_credit_commission_earnings($affiliateId, $amount);

        $modelPayments->insert([
            'user'    => $affiliateId,
            'type'    => $paymentType,
            'type_id' => $commissionId,
            'amount'  => $amount,
            'status'  => 2,
        ]);
        $paymentId = (int) $modelPayments->getInsertID();

        $modelCommissions->update($commissionId, [
            'status'     => 2,
            'payment_id' => $paymentId,
        ]);

        $player = $modelUsers->find((int) $commission['player_id']);
        $playerName = trim(($player['firstname'] ?? '') . ' ' . ($player['lastname'] ?? ''));
        $fromUserId = $fromUserId ?? (int) (session()->get('id') ?? 0);

        $modelNotifications->insert([
            'user'    => $affiliateId,
            'from'    => $fromUserId > 0 ? $fromUserId : (int) ($commission['player_id'] ?? 0),
            'type'    => 'payment',
            'type_id' => $paymentId,
            'title'   => '💰 ' . translate('ggr commission credited'),
            'message' => translate('ggr commission for player') . ' ' . $playerName
                . ' — GGR: ' . systemGet('currency') . ' ' . number_format((float) ($commission['ggr_amount'] ?? 0), 2)
                . ' | ' . translate('commission') . ': ' . systemGet('currency') . ' ' . bingo_format_exact_amount($amount),
        ]);

        return ['success' => true, 'payment_id' => $paymentId];
    }
}

if (! function_exists('bingo_player_game_actual_prize_payout')) {
    /**
     * Premio realmente pagado (o cantado) a un jugador en una partida.
     * Incluye monto fijo y acumulado, para que el PV afiliado vea Total premios.
     */
    function bingo_player_game_actual_prize_payout(int $playerId, int $gameId): float
    {
        if ($playerId <= 0 || $gameId <= 0) {
            return 0.0;
        }

        $db = \Config\Database::connect();
        $fromPayments = 0.0;
        $fromSings = 0.0;

        try {
            $row = $db->table('payments p')
                ->selectSum('p.amount', 'total')
                ->join('sings s', 's.id = p.type_id', 'inner')
                ->where('p.type', 'award')
                ->where('s.game', $gameId)
                ->where('s.user', $playerId)
                ->get()
                ->getRow();
            $fromPayments = (float) ($row->total ?? 0);
        } catch (\Throwable $e) {
            log_message('error', 'bingo_player_game_actual_prize_payout payments: ' . $e->getMessage());
        }

        try {
            $modelSings = new \App\Models\SingsModel();
            $modelGames = new \App\Models\GamesModel();
            $modelAwards = new \App\Models\AwardsModel();
            $game = $modelGames->find($gameId);
            if ($game) {
                $sings = $modelSings
                    ->where('game', $gameId)
                    ->where('user', $playerId)
                    ->whereIn('status', [1, 2])
                    ->findAll();
                foreach ($sings as $sing) {
                    $award = $modelAwards
                        ->where('game', $gameId)
                        ->where('modality', $sing['modality'])
                        ->where('status', 1)
                        ->first();
                    if (! $award) {
                        continue;
                    }
                    $fromSings += bingo_calculate_award_per_sing(
                        $game,
                        $award,
                        $gameId,
                        (int) $sing['modality']
                    );
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'bingo_player_game_actual_prize_payout sings: ' . $e->getMessage());
        }

        return round(max($fromPayments, $fromSings), 2);
    }
}

if (! function_exists('bingo_settle_player_game_ggr_commissions')) {
    function bingo_settle_player_game_ggr_commissions(int $playerId, int $gameId, ?int $fromUserId = null): int
    {
        if (! bingo_ggr_affiliate_active() || $playerId <= 0 || $gameId <= 0) {
            return 0;
        }

        if (bingo_resolve_player_affiliate_chain($playerId) === []) {
            return 0;
        }

        bingo_ensure_affiliate_ggr_schema();

        $modelEvents = new \App\Models\AffiliateGgrEventsModel();
        $modelCommissions = new \App\Models\AffiliateGgrCommissionsModel();
        $modelGames = new \App\Models\GamesModel();

        $game = $modelGames->find($gameId);
        if (! $game) {
            return 0;
        }

        $stakes = (float) ($modelEvents
            ->selectSum('amount')
            ->where('game_id', $gameId)
            ->where('player_id', $playerId)
            ->where('event_type', 'stake')
            ->get()
            ->getRow()
            ->amount ?? 0);

        if ($stakes <= 0) {
            return 0;
        }

        $payouts = (float) ($modelEvents
            ->selectSum('amount')
            ->where('game_id', $gameId)
            ->where('player_id', $playerId)
            ->where('event_type', 'payout')
            ->get()
            ->getRow()
            ->amount ?? 0);

        // Monto fijo (y pagos ya acreditados) deben restar del GGR igual que el acumulado.
        $actualPrizePayout = bingo_player_game_actual_prize_payout($playerId, $gameId);
        if ($actualPrizePayout > $payouts) {
            $payouts = $actualPrizePayout;
        }

        $ggr = round($stakes - $payouts, 2);
        $autoApprove = bingo_ggr_should_auto_approve();
        $periodDate = ! empty($game['date']) ? $game['date'] : date('Y-m-d');
        $settled = 0;

        foreach (bingo_resolve_player_affiliate_chain($playerId) as $affiliate) {
            $affiliateId = (int) $affiliate['id'];
            $affiliateType = (string) $affiliate['type'];
            $rate = bingo_ggr_effective_commission_rate($playerId, $affiliate['user'], $affiliateType);

            $existing = $modelCommissions
                ->where('player_id', $playerId)
                ->where('affiliate_id', $affiliateId)
                ->where('affiliate_type', $affiliateType)
                ->where('game_id', $gameId)
                ->first();

            $existingStatus = (int) ($existing['status'] ?? 0);
            $alreadyPaid = $existingStatus === 2;

            // Sin tasa: no hay comisión que registrar
            if ($rate <= 0) {
                if ($existing && in_array($existingStatus, [0, 1], true)) {
                    $modelCommissions->update((int) $existing['id'], ['status' => 3]);
                }
                continue;
            }

            // GGR puede ser negativo (premio > apuesta). La comisión hereda el signo, sin redondear a 2.
            $commissionAmount = bingo_commission_multiply($ggr, $rate);
            if (bingo_commission_is_zero($commissionAmount) && bingo_commission_is_zero($ggr)) {
                if ($existing && in_array($existingStatus, [0, 1], true)) {
                    $modelCommissions->update((int) $existing['id'], [
                        'total_stake'       => $stakes,
                        'total_payout'      => $payouts,
                        'ggr_amount'        => $ggr,
                        'commission_rate'   => $rate,
                        'commission_amount' => 0,
                        'status'            => 3,
                        'period_date'       => $periodDate,
                    ]);
                }
                continue;
            }

            if ($existing) {
                $update = [
                    'total_stake'  => $stakes,
                    'total_payout' => $payouts,
                    'ggr_amount'   => $ggr,
                    'commission_rate' => $rate,
                    'period_date'  => $periodDate,
                ];
                if (! $alreadyPaid) {
                    $update['commission_amount'] = $commissionAmount;
                    $update['status'] = 0;
                }
                $modelCommissions->update((int) $existing['id'], $update);
                $row = $modelCommissions->find((int) $existing['id']);
            } else {
                $modelCommissions->insert([
                    'player_id'         => $playerId,
                    'affiliate_id'      => $affiliateId,
                    'affiliate_type'    => $affiliateType,
                    'game_id'           => $gameId,
                    'total_stake'       => $stakes,
                    'total_payout'      => $payouts,
                    'ggr_amount'        => $ggr,
                    'commission_rate'   => $rate,
                    'commission_amount' => $commissionAmount,
                    'status'            => 0,
                    'period_date'       => $periodDate,
                ]);
                $row = $modelCommissions->find((int) $modelCommissions->getInsertID());
            }

            // Pago inmediato solo si la comisión es positiva (negativas reducen el neto mensual)
            if ($autoApprove && $row && ! $alreadyPaid && $commissionAmount > 0) {
                bingo_credit_ggr_commission($row, $fromUserId);
            }

            $settled++;
        }

        return $settled;
    }
}

if (! function_exists('bingo_player_has_unpaid_game_awards')) {
    function bingo_player_has_unpaid_game_awards(int $playerId, int $gameId): bool
    {
        if ($playerId <= 0 || $gameId <= 0) {
            return false;
        }

        $modelSings = new \App\Models\SingsModel();

        return $modelSings
            ->where('game', $gameId)
            ->where('user', $playerId)
            ->where('status', 1)
            ->countAllResults() > 0;
    }
}

if (! function_exists('bingo_settle_game_ggr_commissions')) {
    function bingo_settle_game_ggr_commissions(int $gameId, ?int $fromUserId = null, bool $deferWinnersWithPendingAwards = false): int
    {
        if (! bingo_ggr_affiliate_active() || $gameId <= 0) {
            return 0;
        }

        bingo_ensure_affiliate_ggr_schema();

        $modelEvents = new \App\Models\AffiliateGgrEventsModel();

        $playerIds = $modelEvents
            ->select('player_id')
            ->where('game_id', $gameId)
            ->groupBy('player_id')
            ->findColumn('player_id') ?: [];

        $settled = 0;

        foreach ($playerIds as $playerId) {
            $playerId = (int) $playerId;
            if ($playerId <= 0) {
                continue;
            }

            if ($deferWinnersWithPendingAwards && bingo_player_has_unpaid_game_awards($playerId, $gameId)) {
                continue;
            }

            $settled += bingo_settle_player_game_ggr_commissions($playerId, $gameId, $fromUserId);
        }

        return $settled;
    }
}

if (! function_exists('bingo_approve_ggr_commission')) {
    function bingo_approve_ggr_commission(int $commissionId, ?int $fromUserId = null): array
    {
        bingo_ensure_affiliate_ggr_schema();

        $model = new \App\Models\AffiliateGgrCommissionsModel();
        $row = $model->find($commissionId);

        if (! $row) {
            return ['success' => false, 'message' => translate('record not found')];
        }

        if ((int) ($row['status'] ?? 0) === 2) {
            return ['success' => false, 'message' => translate('commission already paid')];
        }

        return bingo_credit_ggr_commission($row, $fromUserId);
    }
}

if (! function_exists('bingo_reject_ggr_commission')) {
    function bingo_reject_ggr_commission(int $commissionId): array
    {
        bingo_ensure_affiliate_ggr_schema();

        $model = new \App\Models\AffiliateGgrCommissionsModel();
        $row = $model->find($commissionId);

        if (! $row) {
            return ['success' => false, 'message' => translate('record not found')];
        }

        if ((int) ($row['status'] ?? 0) === 2) {
            return ['success' => false, 'message' => translate('commission already paid')];
        }

        $model->update($commissionId, ['status' => 3]);

        return ['success' => true, 'message' => translate('commission rejected')];
    }
}

if (! function_exists('bingo_pay_affiliate_cpa_on_deposit')) {
    /**
     * CPA por afiliación deshabilitado.
     *
     * @return array{paid:bool,amount:float}
     */
    function bingo_pay_affiliate_cpa_on_deposit(int $playerId, int $depositId, ?int $fromUserId = null): array
    {
        return ['paid' => false, 'amount' => 0.0];
    }
}

if (! function_exists('bingo_fetch_affiliate_ggr_dashboard')) {
    /**
     * @return array{
     *   total_commission:float,
     *   total_ggr:float,
     *   total_stake:float,
     *   total_payout:float,
     *   referred_count:int,
     *   pending_commission:float,
     *   chart: list<array{label:string,ggr:float,commission:float}>,
     *   history: list<array>
     * }
     */
    function bingo_fetch_affiliate_ggr_dashboard(int $affiliateId, string $affiliateType, int $days = 30, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        bingo_ensure_affiliate_ggr_schema();

        $modelCommissions = new \App\Models\AffiliateGgrCommissionsModel();
        $modelUsers = new \App\Models\UsersModel();

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

        $builder = $modelCommissions
            ->where('affiliate_id', $affiliateId)
            ->where('affiliate_type', $affiliateType);

        if ($dateFrom !== '' || $dateTo !== '') {
            if ($dateFrom !== '') {
                $builder->groupStart()
                    ->where('period_date >=', $dateFrom)
                    ->orGroupStart()
                        ->where('period_date', null)
                        ->where('created_at >=', $dateFrom . ' 00:00:00')
                    ->groupEnd()
                ->groupEnd();
            }
            if ($dateTo !== '') {
                $builder->groupStart()
                    ->where('period_date <=', $dateTo)
                    ->orGroupStart()
                        ->where('period_date', null)
                        ->where('created_at <=', $dateTo . ' 23:59:59')
                    ->groupEnd()
                ->groupEnd();
            }
        } else {
            $since = date('Y-m-d', strtotime('-' . max(1, $days) . ' days'));
            $builder->groupStart()
                ->where('period_date >=', $since)
                ->orGroupStart()
                    ->where('period_date', null)
                    ->where('DATE(created_at) >=', $since)
                ->groupEnd()
            ->groupEnd();
        }

        $rows = $builder->orderBy('created_at', 'DESC')->findAll(500);

        $totalCommission = 0.0;
        $pendingCommission = 0.0;
        $totalGgr = 0.0;
        $totalStake = 0.0;
        $totalPayout = 0.0;
        $chartMap = [];
        $history = [];

        foreach ($rows as $row) {
            $ggr = (float) ($row['ggr_amount'] ?? 0);
            $commission = (float) ($row['commission_amount'] ?? 0);
            $status = (int) ($row['status'] ?? 0);

            $totalGgr += $ggr;
            $totalStake += (float) ($row['total_stake'] ?? 0);
            $totalPayout += (float) ($row['total_payout'] ?? 0);

            if ($status === 2) {
                $totalCommission += $commission;
            } elseif ($status === 0 || $status === 1) {
                $pendingCommission += $commission;
            }

            if ($status === 3) {
                $totalGgr -= $ggr;
                $totalStake -= (float) ($row['total_stake'] ?? 0);
                $totalPayout -= (float) ($row['total_payout'] ?? 0);
                continue;
            }

            $label = ! empty($row['period_date']) ? $row['period_date'] : date('Y-m-d', strtotime($row['created_at'] ?? 'now'));
            if (! isset($chartMap[$label])) {
                $chartMap[$label] = ['label' => $label, 'ggr' => 0.0, 'commission' => 0.0];
            }
            $chartMap[$label]['ggr'] += $ggr;

            $chartCommission = $commission;
            if (bingo_commission_is_zero($chartCommission) && ! bingo_commission_is_zero($ggr)) {
                $chartCommission = bingo_commission_multiply($ggr, (float) ($row['commission_rate'] ?? 0));
            }
            if (! bingo_commission_is_zero($chartCommission)) {
                $chartMap[$label]['commission'] += $chartCommission;
            }

            $player = $modelUsers->find((int) $row['player_id']);
            $history[] = [
                'id'         => (int) $row['id'],
                'player'     => trim(($player['firstname'] ?? '') . ' ' . ($player['lastname'] ?? '')),
                'game_id'    => (int) ($row['game_id'] ?? 0),
                'stake'      => (float) ($row['total_stake'] ?? 0),
                'payout'     => (float) ($row['total_payout'] ?? 0),
                'ggr'        => $ggr,
                'commission' => $commission,
                'rate'       => (float) ($row['commission_rate'] ?? 0),
                'status'     => $status,
                'date'       => $row['created_at'] ?? '',
            ];
        }

        ksort($chartMap);

        $referredCount = 0;
        if ($affiliateType === 'store') {
            $referredCount = $modelUsers
                ->where('referred_store_id', $affiliateId)
                ->where('group', bingo_group_player())
                ->where('deleted', 0)
                ->countAllResults();
        } elseif ($affiliateType === 'operator') {
            $storeIds = $modelUsers
                ->select('id')
                ->where('operator_id', $affiliateId)
                ->where('group', bingo_group_store())
                ->where('deleted', 0)
                ->findColumn('id') ?: [];

            if ($storeIds !== []) {
                $referredCount = $modelUsers
                    ->whereIn('referred_store_id', $storeIds)
                    ->where('group', bingo_group_player())
                    ->where('deleted', 0)
                    ->countAllResults();
            }
        } elseif ($affiliateType === 'player') {
            $referredCount = (new \App\Models\ReferralsModel())
                ->where('id_referred', $affiliateId)
                ->countAllResults();
        }

        return [
            'total_commission'    => round($totalCommission, 2),
            'pending_commission'  => round($pendingCommission, 2),
            'total_ggr'           => round($totalGgr, 2),
            'total_stake'         => round($totalStake, 2),
            'total_payout'        => round($totalPayout, 2),
            'referred_count'      => $referredCount,
            'chart'               => array_values($chartMap),
            'history'             => $history,
        ];
    }
}

if (! function_exists('bingo_fetch_operator_network_ggr_dashboard')) {
    /**
     * Panel operador: GGR de la red de PV + comisión de margen del operador.
     */
    function bingo_fetch_operator_network_ggr_dashboard(int $operatorId, ?array $operator = null, int $days = 30): array
    {
        bingo_ensure_affiliate_ggr_schema();

        $modelUsers = new \App\Models\UsersModel();
        $stores = $modelUsers
            ->where('group', bingo_group_store())
            ->where('operator_id', $operatorId)
            ->where('deleted', 0)
            ->findAll();

        $operatorDash = bingo_fetch_affiliate_ggr_dashboard($operatorId, 'operator', $days);
        $chartMap = [];
        $totalGgr = 0.0;

        foreach ($stores as $store) {
            $storeDash = bingo_fetch_affiliate_ggr_dashboard((int) $store['id'], 'store', $days);
            $totalGgr += (float) ($storeDash['total_ggr'] ?? 0);

            foreach ($storeDash['chart'] ?? [] as $point) {
                $label = (string) ($point['label'] ?? '');
                if ($label === '') {
                    continue;
                }
                if (! isset($chartMap[$label])) {
                    $chartMap[$label] = ['label' => $label, 'ggr' => 0.0, 'commission' => 0.0];
                }
                $chartMap[$label]['ggr'] += (float) ($point['ggr'] ?? 0);
            }
        }

        foreach ($operatorDash['chart'] ?? [] as $point) {
            $label = (string) ($point['label'] ?? '');
            if ($label === '') {
                continue;
            }
            if (! isset($chartMap[$label])) {
                $chartMap[$label] = ['label' => $label, 'ggr' => 0.0, 'commission' => 0.0];
            }
            $chartMap[$label]['commission'] += (float) ($point['commission'] ?? 0);
        }

        ksort($chartMap);

        return [
            'total_commission'   => round((float) ($operatorDash['total_commission'] ?? 0), 2),
            'pending_commission' => round((float) ($operatorDash['pending_commission'] ?? 0), 2),
            'total_ggr'          => round($totalGgr, 2),
            'total_stake'        => (float) ($operatorDash['total_stake'] ?? 0),
            'total_payout'       => (float) ($operatorDash['total_payout'] ?? 0),
            'referred_count'     => count($stores),
            'chart'              => array_values($chartMap),
            'history'            => $operatorDash['history'] ?? [],
        ];
    }
}

if (! function_exists('bingo_sum_affiliate_ggr_commissions')) {
    /**
     * @return array{total_commission:float,pending_commission:float,total_ggr:float,count:int}
     */
    function bingo_sum_affiliate_ggr_commissions(int $affiliateId, string $affiliateType, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        if ($affiliateId <= 0 || $affiliateType === '') {
            return [
                'total_commission'   => 0.0,
                'pending_commission' => 0.0,
                'total_ggr'          => 0.0,
                'count'              => 0,
            ];
        }

        bingo_ensure_affiliate_ggr_schema();

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

        $modelCommissions = new \App\Models\AffiliateGgrCommissionsModel();
        $rows = $modelCommissions
            ->where('affiliate_id', $affiliateId)
            ->where('affiliate_type', $affiliateType)
            ->findAll();

        $totalCommission = 0.0;
        $pendingCommission = 0.0;
        $totalGgr = 0.0;
        $matched = 0;

        foreach ($rows as $row) {
            $rowDate = ! empty($row['period_date'])
                ? substr((string) $row['period_date'], 0, 10)
                : date('Y-m-d', strtotime((string) ($row['created_at'] ?? 'now')));

            if ($dateFrom !== '' && $rowDate < $dateFrom) {
                continue;
            }
            if ($dateTo !== '' && $rowDate > $dateTo) {
                continue;
            }

            $matched++;
            $ggr = (float) ($row['ggr_amount'] ?? 0);
            $commission = (float) ($row['commission_amount'] ?? 0);
            $status = (int) ($row['status'] ?? 0);

            if ($status === 3) {
                continue;
            }

            $totalGgr += $ggr;

            if ($status === 2) {
                $totalCommission += $commission;
            } elseif ($status === 0 || $status === 1) {
                $pendingCommission += $commission;
            }
        }

        return [
            'total_commission'   => round($totalCommission, 2),
            'pending_commission' => round($pendingCommission, 2),
            'total_ggr'          => round($totalGgr, 2),
            'count'              => $matched,
        ];
    }
}

if (! function_exists('bingo_fetch_store_withdraw_summary')) {
    /**
     * Resumen de ganancias del PV: monto visible vs. saldo realmente retirable.
     *
     * @return array{
     *   display_total:float,
     *   wallet_withdraw:float,
     *   pending_ggr:float,
     *   paid_ggr:float,
     *   payment_commissions:float,
     *   can_withdraw:bool,
     *   monthly_mode:bool,
     *   withdraw_blocked_reason:string
     * }
     */
    function bingo_fetch_store_withdraw_summary(int $storeId, ?array $storeUser = null): array
    {
        $empty = [
            'display_total'           => 0.0,
            'wallet_withdraw'         => 0.0,
            'pending_ggr'             => 0.0,
            'paid_ggr'                => 0.0,
            'payment_commissions'     => 0.0,
            'can_withdraw'            => false,
            'monthly_mode'            => bingo_ggr_pays_monthly(),
            'withdraw_blocked_reason' => 'no withdrawable earnings yet',
        ];

        if ($storeId <= 0) {
            return $empty;
        }

        helper('bingo');

        $ggr = bingo_ggr_affiliate_active()
            ? bingo_sum_affiliate_ggr_commissions($storeId, 'store')
            : [
                'total_commission'   => 0.0,
                'pending_commission' => 0.0,
            ];

        $paidGgr = (float) ($ggr['total_commission'] ?? 0);
        $pendingGgr = (float) ($ggr['pending_commission'] ?? 0);
        $paymentCommissions = bingo_sum_store_payment_commissions($storeId);

        if ($storeUser === null) {
            $modelUsers = new \App\Models\UsersModel();
            $storeUser = $modelUsers->find($storeId) ?: [];
        }

        $walletWithdraw = wallet_withdrawable($storeUser);
        $displayTotal = round($paidGgr + $pendingGgr + $paymentCommissions, 2);
        $monthlyMode = true;
        $canWithdraw = false; // Comisiones quedan acumuladas como deuda del mes y son liquidadas por la administración al fin de mes.

        $withdrawBlockedReason = 'Las comisiones se acumulan durante el mes y son liquidadas por administración al cierre de cada mes.';

        return [
            'display_total'           => $displayTotal,
            'wallet_withdraw'         => round($walletWithdraw, 2),
            'pending_ggr'             => $pendingGgr,
            'paid_ggr'                => $paidGgr,
            'payment_commissions'     => $paymentCommissions,
            'can_withdraw'            => $canWithdraw,
            'monthly_mode'            => $monthlyMode,
            'withdraw_blocked_reason' => $withdrawBlockedReason,
        ];
    }
}

if (! function_exists('bingo_fetch_operator_withdraw_summary')) {
    function bingo_fetch_operator_withdraw_summary(int $operatorId, ?array $operatorUser = null): array
    {
        $empty = [
            'display_total'           => 0.0,
            'wallet_withdraw'         => 0.0,
            'pending_ggr'             => 0.0,
            'paid_ggr'                => 0.0,
            'can_withdraw'            => false,
            'monthly_mode'            => true,
            'withdraw_blocked_reason' => 'Las comisiones se acumulan durante el mes y son liquidadas por administración al cierre de cada mes.',
        ];

        if ($operatorId <= 0) {
            return $empty;
        }

        helper('bingo');

        $ggr = bingo_ggr_affiliate_active()
            ? bingo_sum_affiliate_ggr_commissions($operatorId, 'operator')
            : [
                'total_commission'   => 0.0,
                'pending_commission' => 0.0,
            ];

        $paidGgr = (float) ($ggr['total_commission'] ?? 0);
        $pendingGgr = (float) ($ggr['pending_commission'] ?? 0);

        if ($operatorUser === null) {
            $modelUsers = new \App\Models\UsersModel();
            $operatorUser = $modelUsers->find($operatorId) ?: [];
        }

        $walletWithdraw = wallet_withdrawable($operatorUser);
        $displayTotal = round($paidGgr + $pendingGgr, 2);
        $monthlyMode = true;
        $canWithdraw = false; // Comisiones quedan acumuladas como deuda del mes y son liquidadas por la administración al fin de mes.

        $withdrawBlockedReason = 'Las comisiones se acumulan durante el mes y son liquidadas por administración al cierre de cada mes.';

        return [
            'display_total'           => $displayTotal,
            'wallet_withdraw'         => round($walletWithdraw, 2),
            'pending_ggr'             => $pendingGgr,
            'paid_ggr'                => $paidGgr,
            'can_withdraw'            => $canWithdraw,
            'monthly_mode'            => $monthlyMode,
            'withdraw_blocked_reason' => $withdrawBlockedReason,
        ];
    }
}

if (! function_exists('bingo_fetch_operator_commissions_summary')) {
    /**
     * @return array{
     *   total_commission:float,
     *   ggr_commissions:float,
     *   pending_commission:float,
     *   total_ggr:float,
     *   ggr_rate:float,
     *   operator_rate:float,
     *   referred_operators:int,
     *   ggr_dashboard:array
     * }
     */
    function bingo_fetch_operator_commissions_summary(int $operatorId, ?array $operator = null, int $chartDays = 30): array
    {
        $ggrTotals = bingo_ggr_affiliate_active()
            ? bingo_sum_affiliate_ggr_commissions($operatorId, 'operator')
            : [
                'total_commission'   => 0.0,
                'pending_commission' => 0.0,
                'total_ggr'          => 0.0,
                'count'              => 0,
            ];

        $ggrDashboard = bingo_ggr_affiliate_active()
            ? (function () use ($operatorId, $operator, $chartDays) {
                $modelUsers = new \App\Models\UsersModel();
                $hasStores = $modelUsers
                    ->where('group', bingo_group_store())
                    ->where('operator_id', $operatorId)
                    ->where('deleted', 0)
                    ->countAllResults() > 0;

                return $hasStores
                    ? bingo_fetch_operator_network_ggr_dashboard($operatorId, $operator, $chartDays)
                    : bingo_fetch_affiliate_ggr_dashboard($operatorId, 'operator', $chartDays);
            })()
            : [
                'total_commission'   => 0.0,
                'pending_commission' => 0.0,
                'total_ggr'          => 0.0,
                'chart'              => [],
                'history'            => [],
            ];

        $modelUsers = new \App\Models\UsersModel();
        $affiliatedStores = $modelUsers
            ->where('group', bingo_group_store())
            ->where('operator_id', $operatorId)
            ->where('deleted', 0)
            ->countAllResults();

        return [
            'total_commission'   => $ggrTotals['total_commission'],
            'ggr_commissions'    => $ggrTotals['total_commission'],
            'pending_commission' => $ggrTotals['pending_commission'],
            'total_ggr'          => $ggrTotals['total_ggr'],
            'ggr_rate'           => bingo_ggr_commission_rate_for($operator ?? [], 'operator'),
            'operator_rate'      => bingo_operator_commission_rate($operator),
            'affiliate_rate'     => bingo_operator_commission_rate($operator),
            'recharge_rate'      => bingo_operator_recharge_rate($operator),
            'withdraw_rate'      => bingo_operator_withdraw_rate($operator),
            'referred_operators' => $affiliatedStores,
            'affiliated_stores'  => $affiliatedStores,
            'ggr_dashboard'      => $ggrDashboard,
        ];
    }
}

if (! function_exists('bingo_fetch_operator_stores_commissions_summary')) {
    /**
     * @param list<array> $stores
     * @return array{
     *   store_count:int,
     *   total_commission:float,
     *   affiliate_commissions:float,
     *   ggr_commissions:float,
     *   pending_commission:float,
     *   total_ggr:float,
     *   stores:list<array>,
     *   chart:list<array{label:string,ggr:float,commission:float}>
     * }
     */
    function bingo_fetch_operator_stores_commissions_summary(array $stores, int $chartDays = 30, ?array $operator = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $totalAffiliate = 0.0;
        $totalGgrCommission = 0.0;
        $totalPending = 0.0;
        $totalGgr = 0.0;
        $breakdown = [];
        $chartMap = [];
        $operatorTotalGgrRate = bingo_ggr_commission_rate_for($operator, 'operator');

        $operatorId = (int) ($operator['id'] ?? 0);
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

        $hasDateFilter = $dateFrom !== '' || $dateTo !== '';
        if ($hasDateFilter && $dateFrom !== '' && $dateTo !== '') {
            $chartDays = max(1, (int) ((strtotime($dateTo) - strtotime($dateFrom)) / 86400) + 1);
        }

        foreach ($stores as $store) {
            $storeId = (int) ($store['id'] ?? 0);
            if ($storeId <= 0) {
                continue;
            }

            $affiliateCommission = $operatorId > 0
                ? bingo_sum_operator_store_affiliate_commissions(
                    $operatorId,
                    $storeId,
                    $hasDateFilter ? ($dateFrom !== '' ? $dateFrom : null) : null,
                    $hasDateFilter ? ($dateTo !== '' ? $dateTo : null) : null
                )
                : 0.0;
            $ggrTotals = bingo_ggr_affiliate_active()
                ? bingo_sum_affiliate_ggr_commissions(
                    $storeId,
                    'store',
                    $hasDateFilter ? ($dateFrom !== '' ? $dateFrom : null) : null,
                    $hasDateFilter ? ($dateTo !== '' ? $dateTo : null) : null
                )
                : [
                    'total_commission'   => 0.0,
                    'pending_commission' => 0.0,
                    'total_ggr'          => 0.0,
                    'count'              => 0,
                ];

            $storeTotal = round($affiliateCommission + $ggrTotals['total_commission'], 2);
            $storeGgrRate = bingo_ggr_commission_rate_for($store, 'store');
            $operatorMarginRate = max(0.0, round($operatorTotalGgrRate - $storeGgrRate, 4));

            $breakdown[] = [
                'id'                   => $storeId,
                'name'                 => bingo_store_display_name($store),
                'code'                 => (string) ($store['code'] ?? ''),
                'affiliate_commissions' => $affiliateCommission,
                'ggr_commissions'      => $ggrTotals['total_commission'],
                'pending_commission'   => $ggrTotals['pending_commission'],
                'total_ggr'            => $ggrTotals['total_ggr'],
                'total_commission'     => $storeTotal,
                'commission_rate'      => bingo_store_commission_rate($store),
                'ggr_rate'             => bingo_store_ggr_commission_rate($store),
                'operator_margin_rate' => $operatorMarginRate,
            ];

            $totalAffiliate += $affiliateCommission;
            $totalGgrCommission += $ggrTotals['total_commission'];
            $totalPending += $ggrTotals['pending_commission'];
            $totalGgr += $ggrTotals['total_ggr'];

            if (bingo_ggr_affiliate_active()) {
                $ggrDashboard = bingo_fetch_affiliate_ggr_dashboard(
                    $storeId,
                    'store',
                    $chartDays,
                    $hasDateFilter ? ($dateFrom !== '' ? $dateFrom : null) : null,
                    $hasDateFilter ? ($dateTo !== '' ? $dateTo : null) : null
                );
                foreach ($ggrDashboard['chart'] ?? [] as $point) {
                    $label = (string) ($point['label'] ?? '');
                    if ($label === '') {
                        continue;
                    }
                    if (! isset($chartMap[$label])) {
                        $chartMap[$label] = ['label' => $label, 'ggr' => 0.0, 'commission' => 0.0];
                    }
                    $chartMap[$label]['ggr'] += (float) ($point['ggr'] ?? 0);
                    $chartMap[$label]['commission'] += round(
                        (float) ($point['ggr'] ?? 0) * $operatorMarginRate,
                        2
                    );
                }
            }
        }

        usort($breakdown, static fn(array $a, array $b): int => strcmp($a['name'], $b['name']));
        ksort($chartMap);

        $commissionStats = [
            'ggr' => [
                'rate' => $operatorTotalGgrRate,
                'stores_earned' => 0.0,
                'operator_earned' => 0.0,
            ],
            'recharge' => [
                'rate' => bingo_operator_recharge_rate($operator),
                'stores_earned' => 0.0,
                'operator_earned' => 0.0,
            ],
            'withdraw' => [
                'rate' => bingo_operator_withdraw_rate($operator),
                'stores_earned' => 0.0,
                'operator_earned' => 0.0,
            ],
            'total_stores_earned' => 0.0,
            'total_operator_profit' => 0.0,
        ];

        $perStoreThree = [];
        if ($operatorId > 0 && function_exists('bingo_fetch_operator_detailed_commissions_breakdown')) {
            $detailed = bingo_fetch_operator_detailed_commissions_breakdown($operatorId, [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
                'store_id'  => 'all',
                'rate_type' => 'all',
            ]);
            $commissionStats = array_merge($commissionStats, $detailed['stats'] ?? []);

            foreach ($detailed['items'] ?? [] as $item) {
                $sid = (int) ($item['store_id'] ?? 0);
                if ($sid <= 0 || $sid === $operatorId) {
                    continue;
                }
                $type = (string) ($item['rate_type'] ?? '');
                if (! in_array($type, ['ggr', 'recharge', 'withdraw'], true)) {
                    continue;
                }
                if (! isset($perStoreThree[$sid])) {
                    $perStoreThree[$sid] = [
                        'recharge_store' => 0.0,
                        'recharge_operator' => 0.0,
                        'withdraw_store' => 0.0,
                        'withdraw_operator' => 0.0,
                        'ggr_store' => 0.0,
                        'ggr_operator' => 0.0,
                    ];
                }
                $perStoreThree[$sid][$type . '_store'] += (float) ($item['store_commission'] ?? 0);
                $perStoreThree[$sid][$type . '_operator'] += (float) ($item['operator_profit'] ?? 0);
            }
        }

        foreach ($breakdown as &$row) {
            $sid = (int) ($row['id'] ?? 0);
            $extra = $perStoreThree[$sid] ?? [
                'recharge_store' => 0.0,
                'recharge_operator' => 0.0,
                'withdraw_store' => 0.0,
                'withdraw_operator' => 0.0,
                'ggr_store' => (float) ($row['ggr_commissions'] ?? 0),
                'ggr_operator' => 0.0,
            ];
            foreach ($extra as $ek => $ev) {
                $extra[$ek] = round((float) $ev, 2);
            }
            $row = array_merge($row, $extra);
            $row['three_total_store'] = round(
                $extra['recharge_store'] + $extra['withdraw_store'] + $extra['ggr_store'],
                2
            );
            $row['three_total_operator'] = round(
                $extra['recharge_operator'] + $extra['withdraw_operator'] + $extra['ggr_operator'],
                2
            );
        }
        unset($row);

        return [
            'store_count'           => count($breakdown),
            'total_commission'      => round($totalAffiliate + $totalGgrCommission, 2),
            'affiliate_commissions' => round($totalAffiliate, 2),
            'ggr_commissions'       => round($totalGgrCommission, 2),
            'pending_commission'    => round($totalPending, 2),
            'total_ggr'             => round($totalGgr, 2),
            'commission_stats'      => $commissionStats,
            'stores'                => $breakdown,
            'chart'                 => array_values($chartMap),
            'date_from'             => $dateFrom,
            'date_to'               => $dateTo,
        ];
    }
}

if (! function_exists('bingo_on_game_finished')) {
    function bingo_on_game_finished(int $gameId, ?int $fromUserId = null): void
    {
        // 1. Pagar premios pendientes automáticamente a los ganadores (Restaurado)
        bingo_pay_pending_awards_for_game($gameId, $fromUserId);

        // 2. Liquida perdedores al cierre; los ganadores se liquidan al pagar el premio.
        bingo_settle_game_ggr_commissions($gameId, $fromUserId, true);
    }
}

if (! function_exists('bingo_fetch_global_ggr_stats')) {
    function bingo_fetch_global_ggr_stats(int $days = 30): array
    {
        bingo_ensure_affiliate_ggr_schema();

        $since = date('Y-m-d', strtotime('-' . max(1, $days) . ' days'));
        $db = \Config\Database::connect();

        $events = $db->table('affiliate_ggr_events')
            ->select('event_type, SUM(amount) as total')
            ->where('created_at >=', $since . ' 00:00:00')
            ->groupBy('event_type')
            ->get()
            ->getResultArray();

        $stakes = 0.0;
        $payouts = 0.0;
        foreach ($events as $event) {
            if (($event['event_type'] ?? '') === 'stake') {
                $stakes = (float) ($event['total'] ?? 0);
            } elseif (($event['event_type'] ?? '') === 'payout') {
                $payouts = (float) ($event['total'] ?? 0);
            }
        }

        $commissions = $db->table('affiliate_ggr_commissions')
            ->select('affiliate_type, status, SUM(commission_amount) as total, SUM(ggr_amount) as ggr_total, COUNT(*) as cnt')
            ->where('period_date >=', $since)
            ->groupBy('affiliate_type, status')
            ->get()
            ->getResultArray();

        $paidByType = [];
        $pendingTotal = 0.0;
        $ggrByType = [];

        foreach ($commissions as $row) {
            $type = (string) ($row['affiliate_type'] ?? '');
            $status = (int) ($row['status'] ?? 0);
            $amount = (float) ($row['total'] ?? 0);

            if (! isset($ggrByType[$type])) {
                $ggrByType[$type] = 0.0;
            }
            $ggrByType[$type] += (float) ($row['ggr_total'] ?? 0);

            if ($status === 2) {
                $paidByType[$type] = ($paidByType[$type] ?? 0) + $amount;
            } elseif ($status === 0 || $status === 1) {
                $pendingTotal += $amount;
            }
        }

        return [
            'total_stake'       => round($stakes, 2),
            'total_payout'      => round($payouts, 2),
            'global_ggr'        => round($stakes - $payouts, 2),
            'commissions_paid'  => $paidByType,
            'commissions_pending' => round($pendingTotal, 2),
            'ggr_by_type'       => $ggrByType,
        ];
    }
}

if (! function_exists('bingo_settle_monthly_ggr_commissions')) {
    /**
     * Cierra y paga el GGR mensual acumulado para PV y Operadores.
     *
     * @return array{
     *   success:bool,
     *   period:string,
     *   settled:int,
     *   affiliates:int,
     *   total_commission:float,
     *   message:string
     * }
     */
    function bingo_settle_monthly_ggr_commissions(?string $yearMonth = null, ?int $fromUserId = null): array
    {
        bingo_ensure_affiliate_ggr_schema();

        if (! bingo_ggr_pays_monthly()) {
            return [
                'success'          => false,
                'period'           => '',
                'settled'          => 0,
                'affiliates'       => 0,
                'total_commission' => 0.0,
                'message'          => translate('ggr immediate settlement active'),
            ];
        }

        $yearMonth = $yearMonth ?: date('Y-m', strtotime('first day of last month'));
        if (! preg_match('/^\d{4}-\d{2}$/', $yearMonth)) {
            return [
                'success'          => false,
                'period'           => $yearMonth,
                'settled'          => 0,
                'affiliates'       => 0,
                'total_commission' => 0.0,
                'message'          => translate('invalid period'),
            ];
        }

        $period = bingo_ggr_period_bounds($yearMonth);
        $modelCommissions = new \App\Models\AffiliateGgrCommissionsModel();
        $modelSettlements = new \App\Models\AffiliateGgrMonthlySettlementsModel();
        $modelPayments = new \App\Models\PaymentsModel();
        $modelNotifications = new \App\Models\NotificationsModel();
        $modelUsers = new \App\Models\UsersModel();

        $rows = $modelCommissions
            ->whereIn('status', [0, 1])
            ->whereIn('affiliate_type', ['store', 'operator'])
            ->where('period_date >=', $period['start'])
            ->where('period_date <=', $period['end'])
            ->findAll();

        if ($rows === []) {
            return [
                'success'          => true,
                'period'           => $period['label'],
                'settled'          => 0,
                'affiliates'       => 0,
                'total_commission' => 0.0,
                'message'          => translate('no pending ggr commissions for period'),
            ];
        }

        $groups = [];
        foreach ($rows as $row) {
            $affiliateId = (int) ($row['affiliate_id'] ?? 0);
            $affiliateType = (string) ($row['affiliate_type'] ?? '');
            if ($affiliateId <= 0 || $affiliateType === '') {
                continue;
            }

            $key = $affiliateType . ':' . $affiliateId;
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'affiliate_id'   => $affiliateId,
                    'affiliate_type' => $affiliateType,
                    'rows'           => [],
                    'total_stake'    => 0.0,
                    'total_payout'   => 0.0,
                    'total_ggr'      => 0.0,
                    'commission'     => 0.0,
                ];
            }

            $groups[$key]['rows'][] = $row;
            $groups[$key]['total_stake'] += (float) ($row['total_stake'] ?? 0);
            $groups[$key]['total_payout'] += (float) ($row['total_payout'] ?? 0);
            $groups[$key]['total_ggr'] += (float) ($row['ggr_amount'] ?? 0);
            $groups[$key]['commission'] += (float) ($row['commission_amount'] ?? 0);
        }

        $settledCount = 0;
        $affiliateCount = 0;
        $totalCommission = 0.0;

        foreach ($groups as $group) {
            $affiliateId = (int) $group['affiliate_id'];
            $affiliateType = (string) $group['affiliate_type'];
            // Neto del período: positivos + negativos (GGR en rojo reduce la comisión)
            $commissionAmount = (float) $group['commission'];

            $alreadySettled = $modelSettlements
                ->where('affiliate_id', $affiliateId)
                ->where('affiliate_type', $affiliateType)
                ->where('period_month', $period['month_date'])
                ->countAllResults();

            if ($alreadySettled > 0) {
                continue;
            }

            $affiliate = $modelUsers->find($affiliateId);
            if (! $affiliate) {
                continue;
            }

            $payAmount = $commissionAmount > 0 ? $commissionAmount : 0.0;

            $modelSettlements->insert([
                'affiliate_id'      => $affiliateId,
                'affiliate_type'    => $affiliateType,
                'period_month'      => $period['month_date'],
                'total_stake'       => round((float) $group['total_stake'], 2),
                'total_payout'      => round((float) $group['total_payout'], 2),
                'total_ggr'         => round((float) $group['total_ggr'], 2),
                'commission_amount' => $commissionAmount,
                'commission_count'  => count($group['rows']),
                'status'            => 0,
            ]);
            $settlementId = (int) $modelSettlements->getInsertID();

            $paymentId = 0;
            if ($payAmount > 0) {
                wallet_credit_recharge($affiliateId, $payAmount);

                $paymentType = bingo_ggr_payment_type($affiliateType);
                $modelPayments->insert([
                    'user'    => $affiliateId,
                    'type'    => $paymentType,
                    'type_id' => $settlementId,
                    'amount'  => $payAmount,
                    'status'  => 2,
                ]);
                $paymentId = (int) $modelPayments->getInsertID();
            }

            $modelSettlements->update($settlementId, [
                'payment_id' => $paymentId > 0 ? $paymentId : null,
                'status'     => 2,
            ]);

            foreach ($group['rows'] as $commissionRow) {
                $commissionId = (int) ($commissionRow['id'] ?? 0);
                if ($commissionId <= 0) {
                    continue;
                }

                $modelCommissions->update($commissionId, [
                    'status'     => 2,
                    'payment_id' => $paymentId > 0 ? $paymentId : null,
                ]);
            }

            $affiliateName = $affiliateType === 'store'
                ? bingo_store_display_name($affiliate)
                : trim(($affiliate['firstname'] ?? '') . ' ' . ($affiliate['lastname'] ?? ''));

            $modelNotifications->insert([
                'user'    => $affiliateId,
                'from'    => $fromUserId ?? 0,
                'type'    => 'payment',
                'type_id' => $paymentId > 0 ? $paymentId : $settlementId,
                'title'   => '💰 ' . strtoupper(translate('ggr monthly commission credited')),
                'message' => translate('ggr monthly settlement for period') . ' ' . $period['label']
                    . ' — ' . translate('ggr generated') . ': ' . systemGet('currency') . ' '
                    . number_format(round((float) $group['total_ggr'], 2), 2)
                    . ' | ' . translate('commission') . ': ' . systemGet('currency') . ' '
                    . bingo_format_exact_amount($commissionAmount)
                    . ($affiliateName !== '' ? ' (' . $affiliateName . ')' : ''),
            ]);

            $settledCount += count($group['rows']);
            $affiliateCount++;
            $totalCommission += $payAmount;
        }

        return [
            'success'          => true,
            'period'           => $period['label'],
            'settled'          => $settledCount,
            'affiliates'       => $affiliateCount,
            'total_commission' => round($totalCommission, 2),
            'message'          => $affiliateCount > 0
                ? translate('ggr monthly settlement completed')
                : translate('no pending ggr commissions for period'),
        ];
    }
}
