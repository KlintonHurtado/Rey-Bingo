<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentsModel extends Model {
    protected $table = 'payments';
    protected $primaryKey = 'id'; 

    protected $allowedFields = ['user', 'from', 'type', 'type_id', 'amount', 'description', 'created_at', 'updated_at', 'status'];

    protected $useTimestamps = true;
}