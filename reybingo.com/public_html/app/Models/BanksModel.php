<?php

namespace App\Models;

use CodeIgniter\Model;

class BanksModel extends Model {
    protected $table = 'banks'; // Nombre de la tabla
    protected $primaryKey = 'id'; // Llave primaria

    // Campos permitidos para insert/update (soporta phone y type)
    protected $allowedFields = ['code', 'name', 'account', 'holder', 'document', 'phone', 'type', 'logo', 'created_at', 'updated_at', 'status'];

    // Desactivar timestamps automáticos
    protected $useTimestamps = true;

    public function __construct()
    {
        parent::__construct();
        if (function_exists('bingo_ensure_banks_schema')) {
            bingo_ensure_banks_schema();
        }
    }
}