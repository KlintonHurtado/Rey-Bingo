<?php

namespace App\Models;

use CodeIgniter\Model;

class CartonPurchaseLogsModel extends Model
{
    protected $table = 'carton_purchase_logs';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'game_id',
        'cartons_count',
        'amount',
        'from_bonus',
        'from_recharge',
        'from_withdraw',
        'source',
        'roulette_id',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
}
