<?php

namespace App\Libraries;

use App\Models\UsersModel;

class WalletService
{
    protected UsersModel $users;

    public function __construct()
    {
        $this->users = new UsersModel();
    }

    public function normalizeUser(array $user): array
    {
        $user['wallet_recharge'] = (float) ($user['wallet_recharge'] ?? 0);
        $user['wallet_withdraw'] = (float) ($user['wallet_withdraw'] ?? 0);
        $user['wallet_bonus']    = (float) ($user['wallet_bonus'] ?? 0);

        if (($user['wallet_recharge'] + $user['wallet_withdraw'] + $user['wallet_bonus']) <= 0 && (float) ($user['wallet'] ?? 0) > 0) {
            $user['wallet_recharge'] = (float) $user['wallet'];
        }

        return $user;
    }

    public function getTotalBalance(array $user): float
    {
        $user = $this->normalizeUser($user);
        return round($user['wallet_bonus'] + $user['wallet_recharge'] + $user['wallet_withdraw'], 2);
    }

    public function getWithdrawableBalance(array $user): float
    {
        $user = $this->normalizeUser($user);
        return round($user['wallet_withdraw'], 2);
    }

    public function syncLegacyWallet(int $userId, array $balances): void
    {
        $total = round(
            (float) $balances['wallet_bonus']
            + (float) $balances['wallet_recharge']
            + (float) $balances['wallet_withdraw'],
            2
        );

        $this->users->update($userId, array_merge($balances, ['wallet' => $total]));
    }

    public function canAfford(array $user, float $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }

        return $this->getTotalBalance($user) >= $amount;
    }

    /**
     * Descuenta: bono → recarga → retiro
     */
    public function deductForPurchase(int $userId, float $amount): bool
    {
        $user = $this->normalizeUser($this->users->find($userId));
        if (! $user) {
            return false;
        }

        if ($amount <= 0) {
            return true;
        }

        $remaining = $amount;
        $bonus    = $user['wallet_bonus'];
        $recharge = $user['wallet_recharge'];
        $withdraw = $user['wallet_withdraw'];

        $fromBonus = min($bonus, $remaining);
        $remaining -= $fromBonus;
        $bonus -= $fromBonus;

        $fromRecharge = min($recharge, $remaining);
        $remaining -= $fromRecharge;
        $recharge -= $fromRecharge;

        $fromWithdraw = min($withdraw, $remaining);
        $remaining -= $fromWithdraw;
        $withdraw -= $fromWithdraw;

        if ($remaining > 0.0001) {
            return false;
        }

        $this->syncLegacyWallet($userId, [
            'wallet_bonus'    => round($bonus, 2),
            'wallet_recharge' => round($recharge, 2),
            'wallet_withdraw' => round($withdraw, 2),
        ]);

        return true;
    }

    public function creditRecharge(int $userId, float $amount): void
    {
        $user = $this->normalizeUser($this->users->find($userId));
        if (! $user || $amount <= 0) {
            return;
        }

        $this->syncLegacyWallet($userId, [
            'wallet_bonus'    => $user['wallet_bonus'],
            'wallet_recharge' => round($user['wallet_recharge'] + $amount, 2),
            'wallet_withdraw' => $user['wallet_withdraw'],
        ]);
    }

    public function creditWithdrawable(int $userId, float $amount): void
    {
        $user = $this->normalizeUser($this->users->find($userId));
        if (! $user || $amount <= 0) {
            return;
        }

        $this->syncLegacyWallet($userId, [
            'wallet_bonus'    => $user['wallet_bonus'],
            'wallet_recharge' => $user['wallet_recharge'],
            'wallet_withdraw' => round($user['wallet_withdraw'] + $amount, 2),
        ]);
    }

    public function creditBonus(int $userId, float $amount): void
    {
        $user = $this->normalizeUser($this->users->find($userId));
        if (! $user || $amount <= 0) {
            return;
        }

        $this->syncLegacyWallet($userId, [
            'wallet_bonus'    => round($user['wallet_bonus'] + $amount, 2),
            'wallet_recharge' => $user['wallet_recharge'],
            'wallet_withdraw' => $user['wallet_withdraw'],
        ]);
    }

    public function deductWithdrawable(int $userId, float $amount): bool
    {
        $user = $this->normalizeUser($this->users->find($userId));
        if (! $user || $amount <= 0 || $user['wallet_withdraw'] < $amount) {
            return false;
        }

        $this->syncLegacyWallet($userId, [
            'wallet_bonus'    => $user['wallet_bonus'],
            'wallet_recharge' => $user['wallet_recharge'],
            'wallet_withdraw' => round($user['wallet_withdraw'] - $amount, 2),
        ]);

        return true;
    }
}
