<?php

namespace App\Models;

use CodeIgniter\Model;

class GamesModel extends Model {
    protected $table = 'games'; 
    protected $primaryKey = 'id'; 

    protected $allowedFields = ['user', 'room', 'description', 'modalities', 'price', 'min_players', 'date', 'time', 'award', 'type', 'url', 'video', 'reset', 'cover', 'created_at', 'updated_at', 'status'];

    protected $useTimestamps = true;

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