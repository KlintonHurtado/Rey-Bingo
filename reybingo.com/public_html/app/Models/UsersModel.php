<?php

namespace App\Models;

use CodeIgniter\Model;

class UsersModel extends Model {
    protected $table = 'users'; // Nombre de la tabla
    protected $primaryKey = 'id'; // Llave primaria

    // Campos permitidos para insert/update
    protected $allowedFields = ['code', 'group', 'wallet', 'wallet_recharge', 'wallet_withdraw', 'wallet_bonus', 'kyc_status', 'kyc_front', 'kyc_back', 'kyc_observations', 'document', 'firstname', 'lastname', 'username', 'password', 'email', 'phone', 'bank', 'account', 'remember_token', 'created_at', 'updated_at', 'sounds', 'narration', 'autodial', 'image', 'verification_token', 'verified_email', 'restore_code', 'restore_token', 'referred_code', 'status', 'deleted', 'roulette'];

    // Desactivar timestamps automáticos
    protected $useTimestamps = true;

    // Función para obtener el usuario por email o username (incluyendo usuarios inactivos o eliminados)
    public function getUserByUsername($input) {
        return $this->where('username', $input)
                    ->orWhere('email', $input)
                    ->orWhere('phone', $input)
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

    // Función para verificar si el usuario está inactivo
    public function isUserInactive($userId) {
        return $this->where('id', $userId)
                    ->where('status', 0)
                    ->first();
    }
}