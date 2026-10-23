<?php

namespace App\Models;

use CodeIgniter\Model;

class AwardsModel extends Model {
    protected $table = 'awards';
    protected $primaryKey = 'id';
    
    protected $allowedFields = [
        'game', 'modality', 'observation', 'amount', 'image', 'status'
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
            $fields = $db->getFieldNames('awards');
            if (!in_array('image', $fields, true)) {
                $db->query("ALTER TABLE `awards` ADD COLUMN `image` VARCHAR(255) NULL DEFAULT NULL AFTER `observation`");
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error in AwardsModel::ensureSchema: ' . $e->getMessage());
        }
    }
}