<?php

namespace App\Models;

use CodeIgniter\Model;

class CartonsModel extends Model {
    protected $table = 'cartons'; // Nombre de la tabla
    protected $primaryKey = 'id'; // Llave primaria

    // Campos permitidos para insert/update
    protected $allowedFields = ['serial', 'user', 'game', 'created_at', 'updated_at', 'status', 'pay_source'];

    // Desactivar timestamps automáticos
    protected $useTimestamps = true;
    
    // Función para obtener los cartones por jugador y juego
    public function getCartonsByUser($userId, $gameId) {
        return $this->where('user', $userId)
                    ->where('game', $gameId)
                    ->where('status', 1)  // Solo cartones activos
                    ->findAll();  // Obtener todos los cartones en lugar de solo el primero
    }
}