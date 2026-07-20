<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class TestDeduct extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:deduct';
    protected $description = 'Test wallet deduction';

    public function run(array $params)
    {
        $userId = $params[0] ?? 1;
        $amount = (float)($params[1] ?? 1);

        helper('wallet');
        
        $model = new \App\Models\UsersModel();
        $user = wallet_service()->normalizeUser($model->find($userId));
        CLI::write("Before: " . wallet_total($user));

        $res = wallet_deduct_purchase($userId, $amount);
        CLI::write("Result: " . ($res ? 'Success' : 'Failed'));

        $user2 = wallet_service()->normalizeUser($model->find($userId));
        CLI::write("After: " . wallet_total($user2));
    }
}
