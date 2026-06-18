<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddClientDomainSettings extends Migration
{
    public function up()
    {
        $defaults = [
            'activateClientDomain' => '0',
            'clientDomain'         => '',
        ];

        foreach ($defaults as $key => $value) {
            $exists = $this->db->table('system')->where('key', $key)->countAllResults();
            if ($exists === 0) {
                $this->db->table('system')->insert([
                    'key'   => $key,
                    'value' => $value,
                ]);
            }
        }
    }

    public function down()
    {
        $this->db->table('system')->whereIn('key', ['activateClientDomain', 'clientDomain'])->delete();
    }
}
