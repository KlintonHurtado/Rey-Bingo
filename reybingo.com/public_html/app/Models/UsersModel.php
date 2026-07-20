<?php

namespace App\Models;

use CodeIgniter\Model;

class UsersModel extends Model {
    protected $table = 'users'; // Nombre de la tabla
    protected $primaryKey = 'id'; // Llave primaria

    // Campos permitidos para insert/update
    protected $allowedFields = ['code', 'group', 'wallet', 'wallet_recharge', 'wallet_withdraw', 'wallet_bonus', 'kyc_status', 'kyc_front', 'kyc_back', 'kyc_selfie', 'kyc_observations', 'document', 'firstname', 'lastname', 'business_name', 'store_commission_rate', 'store_prize_commission_rate', 'operator_commission_rate', 'ggr_commission_rate', 'affiliate_cpa_amount', 'username', 'password', 'email', 'phone', 'address_line', 'city', 'state', 'is_reseller', 'bank', 'account', 'remember_token', 'created_at', 'updated_at', 'sounds', 'narration', 'autodial', 'image', 'verification_token', 'verified_email', 'restore_code', 'restore_token', 'referred_code', 'referred_store_id', 'affiliate_signup_store_id', 'operator_id', 'referred_operator_id', 'status', 'deleted', 'roulette', 'low_balance_alert'];

    // Desactivar timestamps automáticos
    protected $useTimestamps = true;

    // Función para obtener el usuario por email o username (incluyendo usuarios inactivos o eliminados)
    public function getUserByUsername($input) {
        return $this->where('username', $input)
                    ->orWhere('email', $input)
                    ->orWhere('phone', $input)
                    ->first();
    }

    public function findPlayerForStoreRecharge(string $query) {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $query);
        if ($normalized === '') {
            return null;
        }

        $player = $this->where('group', 0)
            ->where('deleted', 0)
            ->where('status', 1)
            ->groupStart()
                ->where('document', $query)
                ->orWhere('document', $normalized)
            ->groupEnd()
            ->first();

        if ($player) {
            return $player;
        }

        return $this->where('group', 0)
            ->where('deleted', 0)
            ->where('status', 1)
            ->where(
                'REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(document, ""), ".", ""), "-", ""), " ", ""), "V", "") =',
                $normalized
            )
            ->first();
    }
    
    // Función para obtener el usuario por id (incluyendo usuarios inactivos o eliminados)
    public function getUserById($input) {
        return $this->where('id', $input)
                    ->first();
    }

    // Función para obtener un usuario activo (no eliminado)
    public function getActiveUserByUsername($username) {
        return $this->where('username', $username)
                    ->orWhere('email', $username)
                    ->where('status', 1) // Solo usuarios activos
                    ->where('deleted', 0) // Que no estén eliminados
                    ->first();
    }

    // Función para verificar si el usuario está eliminado
    public function isUserDeleted($userId) {
        return $this->where('id', $userId)
                    ->where('deleted', 1)
                    ->first();
    }

    public function usernameExists(string $username, ?int $excludeUserId = null): bool
    {
        $username = trim($username);
        if ($username === '') {
            return false;
        }

        $builder = $this->where('LOWER(username)', strtolower($username));
        if ($excludeUserId !== null && $excludeUserId > 0) {
            $builder->where('id !=', $excludeUserId);
        }

        return $builder->countAllResults() > 0;
    }

    // Función para verificar si el usuario está inactivo
    public function isUserInactive($userId) {
        return $this->where('id', $userId)
                    ->where('status', 0)
                    ->first();
    }
}