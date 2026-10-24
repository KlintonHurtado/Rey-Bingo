<?php

namespace App\Models;

use CodeIgniter\Model;

class AwardsModel extends Model {
    protected $table = 'awards';
    protected $primaryKey = 'id';
    
    protected $allowedFields = [
        'game', 'modality', 'observation', 'amount', 'image', 'status', 'created_at', 'updated_at'
    ];
    
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
            if (!$db->tableExists('awards')) {
                return;
            }
            $fields = $db->getFieldNames('awards');
            if (!in_array('image', $fields, true)) {
                $db->query("ALTER TABLE `awards` ADD COLUMN `image` VARCHAR(255) NULL DEFAULT NULL AFTER `observation`");
            }
            if (!in_array('created_at', $fields, true)) {
                $db->query("ALTER TABLE `awards` ADD COLUMN `created_at` DATETIME NULL DEFAULT NULL");
            }
            if (!in_array('updated_at', $fields, true)) {
                $db->query("ALTER TABLE `awards` ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL");
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error in AwardsModel::ensureSchema: ' . $e->getMessage());
        }
    }

    public function insert($row = null, bool $returnID = true)
    {
        $this->ensureSchema();

        try {
            $existingFields = $this->db->getFieldNames($this->table);
            if (!empty($existingFields) && is_array($row)) {
                if (!in_array('created_at', $existingFields, true) || !in_array('updated_at', $existingFields, true)) {
                    $this->useTimestamps = false;
                }
                foreach (array_keys($row) as $key) {
                    if (!in_array($key, $existingFields, true)) {
                        unset($row[$key]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            $res = parent::insert($row, $returnID);
            if ($res) {
                return $res;
            }
        } catch (\Throwable $e) {
            log_message('error', 'AwardsModel::insert exception: ' . $e->getMessage());
        }

        try {
            if (is_array($row) && !empty($row)) {
                $existingFields = $this->db->getFieldNames($this->table);
                $clean = array_filter(
                    $row,
                    fn($k) => in_array($k, $existingFields, true),
                    ARRAY_FILTER_USE_KEY
                );
                if (in_array('created_at', $existingFields, true) && !isset($clean['created_at'])) {
                    $clean['created_at'] = date('Y-m-d H:i:s');
                }
                if (in_array('updated_at', $existingFields, true) && !isset($clean['updated_at'])) {
                    $clean['updated_at'] = date('Y-m-d H:i:s');
                }
                if ($this->builder()->insert($clean)) {
                    return $returnID ? $this->db->insertID() : true;
                }
            }
        } catch (\Throwable $e2) {
            log_message('error', 'AwardsModel fallback insert error: ' . $e2->getMessage());
        }

        return false;
    }
}