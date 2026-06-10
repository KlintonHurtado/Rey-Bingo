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

if (! function_exists('wallet_kyc_allows_withdraw')) {
    /**
     * KYC obligatorio solo para retiros. Depósitos y compras de cartones no lo exigen.
     */
    function wallet_kyc_allows_withdraw(array $user): bool
    {
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
