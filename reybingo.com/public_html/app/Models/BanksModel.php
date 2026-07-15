<?php

namespace App\Models;

use CodeIgniter\Model;

class BanksModel extends Model {
    protected $table = 'banks'; // Nombre de la tabla
    protected $primaryKey = 'id'; // Llave primaria

    // Campos permitidos para insert/update
    protected $allowedFields = ['code', 'name', 'account', 'holder', 'document', 'type', 'logo', 'created_at', 'updated_at', 'status'];

    // Desactivar timestamps automáticos
    protected $useTimestamps = true;
}