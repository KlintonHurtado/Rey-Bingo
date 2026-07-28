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

        $db = \Config\Database::connect();
        $fields = $db->getFieldNames('users');
        
        $data = ['wallet' => $total];
        
        foreach (['wallet_bonus', 'wallet_recharge', 'wallet_withdraw'] as $col) {
            if (in_array($col, $fields)) {
                $data[$col] = $balances[$col];
            }
        }

        $this->users->update($userId, $data);

        helper('bingo');
        if (function_exists('bingo_check_low_balance_auto_roulette')) {
            bingo_check_low_balance_auto_roulette($userId);
        }
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
        return $this->deductForPurchaseDetailed($userId, $amount) !== null;
    }

    /**
     * @return array{from_bonus:float,from_recharge:float,from_withdraw:float}|null
     */
    public function deductForPurchaseDetailed(int $userId, float $amount): ?array
    {
        $user = $this->normalizeUser($this->users->find($userId));
        if (! $user) {
            return null;
        }

        if ($amount <= 0) {
            return [
                'from_bonus' => 0.0,
                'from_recharge' => 0.0,
                'from_withdraw' => 0.0,
            ];
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
            return null;
        }

        $this->syncLegacyWallet($userId, [
            'wallet_bonus'    => round($bonus, 2),
            'wallet_recharge' => round($recharge, 2),
            'wallet_withdraw' => round($withdraw, 2),
        ]);

        return [
            'from_bonus' => round($fromBonus, 2),
            'from_recharge' => round($fromRecharge, 2),
            'from_withdraw' => round($fromWithdraw, 2),
        ];
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

    public function setBalances(int $userId, float $bonus, float $recharge, float $withdraw): void
    {
        $this->syncLegacyWallet($userId, [
            'wallet_bonus'    => round(max(0, $bonus), 2),
            'wallet_recharge' => round(max(0, $recharge), 2),
            'wallet_withdraw' => round(max(0, $withdraw), 2),
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

    public function deductRecharge(int $userId, float $amount): bool
    {
        $user = $this->normalizeUser($this->users->find($userId));
        if (! $user || $amount <= 0 || $user['wallet_recharge'] < $amount) {
            return false;
        }

        $this->syncLegacyWallet($userId, [
            'wallet_bonus'    => $user['wallet_bonus'],
            'wallet_recharge' => round($user['wallet_recharge'] - $amount, 2),
            'wallet_withdraw' => $user['wallet_withdraw'],
        ]);

        return true;
    }

    public function getRechargeBalance(array $user): float
    {
        $user = $this->normalizeUser($user);

        return round($user['wallet_recharge'], 2);
    }
}
