<?php

namespace App\Models;

use CodeIgniter\Model;

class RetiresModel extends Model {
    protected $table = 'retires'; 
    protected $primaryKey = 'id';

    protected $allowedFields = ['user', 'account', 'account_type', 'bank', 'document', 'phone', 'amount', 'observation', 'created_at', 'updated_at', 'status'];

    protected $useTimestamps = true;
}