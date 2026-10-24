<?php

namespace App\Models;

use CodeIgniter\Model;

class GamesModel extends Model {
    protected $table = 'games'; 
    protected $primaryKey = 'id'; 

    protected $allowedFields = ['user', 'room', 'description', 'modalities', 'price', 'access_code', 'min_players', 'min_cartons', 'allow_roulette_cartons', 'date', 'time', 'award', 'type', 'url', 'video', 'reset', 'cover', 'created_at', 'updated_at', 'status'];

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
            if (!$db->tableExists('games')) {
                return;
            }
            $fields = $db->getFieldNames('games');
            if (!in_array('access_code', $fields, true)) {
                $db->query("ALTER TABLE `games` ADD COLUMN `access_code` VARCHAR(100) NULL DEFAULT NULL AFTER `price`");
            }
            if (!in_array('cover', $fields, true)) {
                $db->query("ALTER TABLE `games` ADD COLUMN `cover` VARCHAR(255) NULL DEFAULT NULL AFTER `reset`");
            }
            if (!in_array('min_players', $fields, true)) {
                $db->query("ALTER TABLE `games` ADD COLUMN `min_players` INT(11) UNSIGNED NOT NULL DEFAULT 10 AFTER `price`");
            }
            if (!in_array('min_cartons', $fields, true)) {
                $db->query("ALTER TABLE `games` ADD COLUMN `min_cartons` INT(11) UNSIGNED NOT NULL DEFAULT 10 AFTER `price`");
            }
            if (!in_array('allow_roulette_cartons', $fields, true)) {
                $db->query("ALTER TABLE `games` ADD COLUMN `allow_roulette_cartons` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 AFTER `price`");
            }
            if (!in_array('created_at', $fields, true)) {
                $db->query("ALTER TABLE `games` ADD COLUMN `created_at` DATETIME NULL DEFAULT NULL");
            }
            if (!in_array('updated_at', $fields, true)) {
                $db->query("ALTER TABLE `games` ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL");
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error in GamesModel::ensureSchema: ' . $e->getMessage());
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
            log_message('error', 'GamesModel pre-insert filter: ' . $e->getMessage());
        }

        try {
            $res = parent::insert($row, $returnID);
            if ($res) {
                return $res;
            }
        } catch (\Throwable $e) {
            log_message('error', 'GamesModel parent::insert exception: ' . $e->getMessage());
        }

        // Direct builder fallback
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
                    $newId = $this->db->insertID();
                    return $returnID ? $newId : true;
                }
            }
        } catch (\Throwable $e2) {
            log_message('error', 'GamesModel fallback insert error: ' . $e2->getMessage());
        }

        return false;
    }

    public function update($id = null, $row = null): bool
    {
        $this->ensureSchema();

        try {
            $existingFields = $this->db->getFieldNames($this->table);
            if (!empty($existingFields) && is_array($row)) {
                if (!in_array('updated_at', $existingFields, true)) {
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
            $res = parent::update($id, $row);
            if ($res) {
                return true;
            }
        } catch (\Throwable $e) {
            log_message('error', 'GamesModel parent::update exception: ' . $e->getMessage());
        }

        try {
            if ($id && is_array($row) && !empty($row)) {
                $existingFields = $this->db->getFieldNames($this->table);
                $clean = array_filter(
                    $row,
                    fn($k) => in_array($k, $existingFields, true),
                    ARRAY_FILTER_USE_KEY
                );
                if (in_array('updated_at', $existingFields, true) && !isset($clean['updated_at'])) {
                    $clean['updated_at'] = date('Y-m-d H:i:s');
                }
                return (bool) $this->builder()->where('id', $id)->update($clean);
            }
        } catch (\Throwable $e2) {
            log_message('error', 'GamesModel fallback update error: ' . $e2->getMessage());
        }

        return false;
    }

    public function getGameByDate($fecha) {
        return $this->where('DATE(date)', $fecha)->where('status', 1)->first();
    }

    public function getGamesByDate($fecha) {
        return $this->where('DATE(date) >=', $fecha)->where('status', 1)->orderBy('date', 'ASC')->findAll();
    }

    public function getGameByDateComplete($fecha) {
        return $this->where('DATE(date)', $fecha)->first();
    }
}