<?php

use App\Libraries\WalletService;

if (! function_exists('wallet_service')) {
    function wallet_service(): WalletService
    {
        return new WalletService();
    }
}

if (! function_exists('wallet_total')) {
    function wallet_total(array $user): float
    {
        return wallet_service()->getTotalBalance($user);
    }
}

if (! function_exists('wallet_withdrawable')) {
    function wallet_withdrawable(array $user): float
    {
        return wallet_service()->getWithdrawableBalance($user);
    }
}

if (! function_exists('wallet_deduct_purchase')) {
    function wallet_deduct_purchase(int $userId, float $amount): bool
    {
        return wallet_service()->deductForPurchase($userId, $amount);
    }
}

if (! function_exists('wallet_deduct_purchase_detailed')) {
    /**
     * @return array{from_bonus:float,from_recharge:float,from_withdraw:float}|null
     */
    function wallet_deduct_purchase_detailed(int $userId, float $amount): ?array
    {
        return wallet_service()->deductForPurchaseDetailed($userId, $amount);
    }
}

if (! function_exists('wallet_credit_recharge')) {
    function wallet_credit_recharge(int $userId, float $amount): void
    {
        wallet_service()->creditRecharge($userId, $amount);
    }
}

if (! function_exists('wallet_credit_withdrawable')) {
    function wallet_credit_withdrawable(int $userId, float $amount): void
    {
        wallet_service()->creditWithdrawable($userId, $amount);
    }
}

if (! function_exists('wallet_credit_commission_earnings')) {
    /** Comisiones y GGR acreditan saldo retirable (no saldo operativo del PV). */
    function wallet_credit_commission_earnings(int $userId, float $amount): void
    {
        wallet_credit_withdrawable($userId, $amount);
    }
}

if (! function_exists('wallet_kyc_allows_withdraw')) {
    /**
     * KYC obligatorio solo para retiros. Depósitos y compras de cartones no lo exigen.
     */
    function wallet_kyc_allows_withdraw(array $user): bool
    {
        if (function_exists('bingo_user_requires_kyc') && ! bingo_user_requires_kyc($user)) {
            return true;
        }

        return ($user['kyc_status'] ?? 'pending') === 'verified';
    }
}

if (! function_exists('wallet_kyc_withdraw_message')) {
    function wallet_kyc_withdraw_message(array $user): string
    {
        $status = (string) ($user['kyc_status'] ?? 'pending');
        $hasDocs = ! empty($user['kyc_front']) && ! empty($user['kyc_back']) && ! empty($user['kyc_selfie']);

        if ($status === 'rejected') {
            return 'Tu verificación fue rechazada. Sube de nuevo las fotos de tu documento (frente y reverso) para poder retirar.';
        }

        if ($status === 'pending' && $hasDocs) {
            return 'Ya enviaste tus documentos. Estamos revisando tu identidad; podrás retirar cuando sea aprobada.';
        }

        return 'Antes de retirar debes verificar tu identidad subiendo una foto de tu documento por ambos lados.';
    }
}

if (! function_exists('wallet_kyc_action_label')) {
    function wallet_kyc_action_label(array $user): string
    {
        $status = (string) ($user['kyc_status'] ?? 'pending');
        $hasDocs = ! empty($user['kyc_front']) && ! empty($user['kyc_back']) && ! empty($user['kyc_selfie']);

        if ($status === 'rejected') {
            return 'Corregir verificación';
        }

        if ($status === 'pending' && $hasDocs) {
            return 'Ver estado de verificación';
        }

        return 'Verificar mi identidad';
    }
}

if (! function_exists('wallet_deduct_withdrawable')) {
    function wallet_deduct_withdrawable(int $userId, float $amount): bool
    {
        return wallet_service()->deductWithdrawable($userId, $amount);
    }
}

if (! function_exists('wallet_deduct_recharge')) {
    function wallet_deduct_recharge(int $userId, float $amount): bool
    {
        return wallet_service()->deductRecharge($userId, $amount);
    }
}

if (! function_exists('wallet_recharge_balance')) {
    function wallet_recharge_balance(array $user): float
    {
        return wallet_service()->getRechargeBalance($user);
    }
}

if (! function_exists('wallet_registration_bonus_amount')) {
    function wallet_registration_bonus_amount(): float
    {
        return max(0, (float) (systemGet('registrationBonus') ?? 0));
    }
}

if (! function_exists('wallet_record_registration_bonus')) {
    function wallet_record_registration_bonus(int $userId, float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $modelPayments = new \App\Models\PaymentsModel();

        if ($modelPayments->where('user', $userId)->where('type', 'registration_bonus')->first()) {
            return false;
        }

        return (bool) $modelPayments->insert([
            'user'    => $userId,
            'type'    => 'registration_bonus',
            'type_id' => $userId,
            'amount'  => round($amount, 2),
            'status'  => 2,
        ]);
    }
}

if (! function_exists('wallet_backfill_registration_bonus_transaction')) {
    function wallet_backfill_registration_bonus_transaction(int $userId): void
    {
        $amount = wallet_registration_bonus_amount();
        if ($amount <= 0) {
            return;
        }

        $modelUsers = new \App\Models\UsersModel();
        $user = $modelUsers->find($userId);
        if (! $user) {
            return;
        }

        $user = wallet_service()->normalizeUser($user);
        if ($user['wallet_bonus'] <= 0) {
            return;
        }

        wallet_record_registration_bonus($userId, $amount);
    }
}

if (! function_exists('wallet_apply_registration_bonus')) {
    function wallet_apply_registration_bonus(int $userId): void
    {
        $amount = wallet_registration_bonus_amount();

        if ($amount > 0) {
            wallet_service()->creditBonus($userId, $amount);
            wallet_record_registration_bonus($userId, $amount);
        }
    }
}

if (! function_exists('wallet_grant_admin_bonus')) {
    /**
     * Otorga saldo bono a un jugador específico (admin).
     *
     * @return array{success:bool,message:string,amount?:float,wallet_bonus?:float}
     */
    function wallet_grant_admin_bonus(int $userId, float $amount, ?int $adminId = null, string $note = ''): array
    {
        helper('bingo');

        $amount = round($amount, 2);
        if ($userId <= 0 || $amount <= 0) {
            return [
                'success' => false,
                'message' => translate('invalid bonus amount'),
            ];
        }

        $modelUsers = new \App\Models\UsersModel();
        $player = $modelUsers
            ->where('id', $userId)
            ->where('group', bingo_group_player())
            ->where('deleted', 0)
            ->first();

        if (! $player) {
            return [
                'success' => false,
                'message' => translate('user not found'),
            ];
        }

        wallet_service()->creditBonus($userId, $amount);

        $modelPayments = new \App\Models\PaymentsModel();
        $paymentId = $modelPayments->insert([
            'user'    => $userId,
            'type'    => 'admin_bonus',
            'type_id' => $adminId ?: 0,
            'amount'  => $amount,
            'status'  => 2,
        ]);

        $player = wallet_service()->normalizeUser($modelUsers->find($userId) ?: $player);

        $currency = (string) systemGet('currency');
        $message = translate('bonus granted notification');
        $message = str_replace(
            [':currency', ':amount'],
            [$currency, number_format($amount, 2)],
            $message
        );
        if ($note !== '') {
            $message .= ' ' . $note;
        }

        try {
            $modelNotifications = new \App\Models\NotificationsModel();
            $modelNotifications->insert([
                'user'    => $userId,
                'from'    => $adminId ?: 0,
                'type'    => 'bonus',
                'type_id' => $paymentId ?: $userId,
                'title'   => translate('bonus granted'),
                'message' => $message,
                'status'  => 0,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'wallet_grant_admin_bonus notification: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => translate('bonus granted successfully'),
            'amount' => $amount,
            'wallet_bonus' => round((float) ($player['wallet_bonus'] ?? 0), 2),
            'payment_id' => $paymentId,
        ];
    }
}

if (! function_exists('wallet_summary_payload')) {
    function wallet_summary_payload(?array $user): array
    {
        if (empty($user)) {
            return [
                'total' => 0,
                'recharge' => 0,
                'withdraw' => 0,
                'bonus' => 0,
            ];
        }

        $user = wallet_service()->normalizeUser($user);

        return [
            'total' => round(wallet_total($user), 2),
            'recharge' => round((float) ($user['wallet_recharge'] ?? 0), 2),
            'withdraw' => round((float) ($user['wallet_withdraw'] ?? 0), 2),
            'bonus' => round((float) ($user['wallet_bonus'] ?? 0), 2),
        ];
    }
}
