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
            $fields = $db->getFieldNames('games');
            if (!in_array('access_code', $fields, true)) {
                $db->query("ALTER TABLE `games` ADD COLUMN `access_code` VARCHAR(100) NULL DEFAULT NULL AFTER `price`");
            }
            if (!in_array('cover', $fields, true)) {
                $db->query("ALTER TABLE `games` ADD COLUMN `cover` VARCHAR(255) NULL DEFAULT NULL AFTER `reset`");
            }
        } catch (\Throwable $e) {
            log_message('error', 'Error in GamesModel::ensureSchema: ' . $e->getMessage());
        }
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