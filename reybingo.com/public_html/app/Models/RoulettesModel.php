<?php

namespace App\Models;

use CodeIgniter\Model;

class RoulettesModel extends Model {
    protected $table = 'roulettes'; // Nombre de la tabla
    protected $primaryKey = 'id'; // Llave primaria

    // Campos permitidos para insert/update
    protected $allowedFields = ['user', 'game', 'cartons', 'price', 'amount', 'status', 'created_at', 'updated_at'];

    // Desactivar timestamps automáticos
    protected $useTimestamps = true;
}