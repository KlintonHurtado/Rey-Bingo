<?php

namespace App\Models;

use CodeIgniter\Model;

class AffiliateGgrCommissionsModel extends Model
{
    protected $table = 'affiliate_ggr_commissions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'player_id',
        'affiliate_id',
        'affiliate_type',
        'game_id',
        'total_stake',
        'total_payout',
        'ggr_amount',
        'commission_rate',
        'commission_amount',
        'status',
        'payment_id',
        'period_date',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
}
