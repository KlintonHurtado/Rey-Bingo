<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRegistrationBonusSetting extends Migration
{
    public function up()
    {
        $exists = $this->db->table('system')->where('key', 'registrationBonus')->countAllResults();

        if ($exists === 0) {
            $this->db->table('system')->insert([
                'key'   => 'registrationBonus',
                'value' => '0',
            ]);
        }
    }

    public function down()
    {
        $this->db->table('system')->where('key', 'registrationBonus')->delete();
    }
}
