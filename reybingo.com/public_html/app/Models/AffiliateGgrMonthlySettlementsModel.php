<?php

namespace App\Models;

use CodeIgniter\Model;

class AffiliateGgrMonthlySettlementsModel extends Model
{
    protected $table = 'affiliate_ggr_monthly_settlements';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'affiliate_id',
        'affiliate_type',
        'period_month',
        'total_stake',
        'total_payout',
        'total_ggr',
        'commission_amount',
        'commission_count',
        'payment_id',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
}
