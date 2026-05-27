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
    function wallet_kyc_allows_withdraw(array $user): bool
    {
        return ($user['kyc_status'] ?? 'pending') === 'verified';
    }
}

if (! function_exists('wallet_deduct_withdrawable')) {
    function wallet_deduct_withdrawable(int $userId, float $amount): bool
    {
        return wallet_service()->deductWithdrawable($userId, $amount);
    }
}
