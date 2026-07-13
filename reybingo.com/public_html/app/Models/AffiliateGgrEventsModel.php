<?php

namespace App\Models;

use CodeIgniter\Model;

class AffiliateGgrEventsModel extends Model
{
    protected $table = 'affiliate_ggr_events';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'player_id',
        'game_id',
        'event_type',
        'amount',
        'reference_type',
        'reference_id',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
}
