<?php

namespace App\Models;

use CodeIgniter\Model;

class ModalitiesModel extends Model {
    protected $table = 'modalities'; // Nombre de la tabla
    protected $primaryKey = 'id'; // Llave primaria

    // Campos permitidos para insert/update
    protected $allowedFields = ['name', 'positions', 'observations', 'image', 'created_at', 'updated_at', 'status'];

    // Desactivar timestamps automáticos
    protected $useTimestamps = true;

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        try {
            $db = \Config\Database::connect();
            $fields = $db->getFieldNames('modalities');
            if (!in_array('image', $fields, true)) {
                $db->query("ALTER TABLE `modalities` ADD COLUMN `image` VARCHAR(255) NULL DEFAULT NULL AFTER `observations`");
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error in ModalitiesModel::ensureSchema: ' . $e->getMessage());
        }
    }

    public function getModalitiesByIds($modalityIds) {
        return $this->whereIn('id', $modalityIds)
                    ->where('status', 1)  // Solo modalidades activas
                    ->findAll();
    }
}