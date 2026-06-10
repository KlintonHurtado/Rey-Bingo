<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddKycSelfie extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('kyc_back', 'users') && ! $this->db->fieldExists('kyc_selfie', 'users')) {
            $this->forge->addColumn('users', [
                'kyc_selfie' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'kyc_back',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('kyc_selfie', 'users')) {
            $this->forge->dropColumn('users', 'kyc_selfie');
        }
    }
}
