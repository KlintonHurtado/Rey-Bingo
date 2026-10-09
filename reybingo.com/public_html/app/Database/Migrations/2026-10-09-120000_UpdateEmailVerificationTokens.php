<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateEmailVerificationTokens extends Migration
{
    public function up()
    {
        $db = $this->db;
        $forge = $this->forge;

        if ($db->tableExists('users')) {
            $fields = [
                'verification_token_expires_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'verification_token',
                ],
                'verification_token_prev' => [
                    'type' => 'VARCHAR',
                    'constraint' => 64,
                    'null' => true,
                    'after' => 'verification_token_expires_at',
                ],
                'verification_token_prev_expires_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'verification_token_prev',
                ],
                'email_verification_sent_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'verification_token_prev_expires_at',
                ],
            ];

            foreach ($fields as $name => $def) {
                if (!$db->fieldExists($name, 'users')) {
                    $forge->addColumn('users', [$name => $def]);
                }
            }

            try {
                $indexes = [];
                $rows = $db->query("SHOW INDEX FROM `users`")->getResultArray();
                foreach ($rows as $r) {
                    $indexes[$r['Key_name']] = true;
                }
                if (!isset($indexes['idx_users_verification_token'])) {
                    $db->query("ALTER TABLE `users` ADD INDEX `idx_users_verification_token` (`verification_token`(64))");
                }
                if (!isset($indexes['idx_users_verification_token_prev'])) {
                    $db->query("ALTER TABLE `users` ADD INDEX `idx_users_verification_token_prev` (`verification_token_prev`)");
                }
            } catch (\Throwable $e) {
                log_message('error', 'UpdateEmailVerificationTokens index creation notice: ' . $e->getMessage());
            }
        }
    }

    public function down()
    {
        $cols = [
            'email_verification_sent_at',
            'verification_token_prev_expires_at',
            'verification_token_prev',
            'verification_token_expires_at'
        ];
        foreach ($cols as $col) {
            if ($this->db->fieldExists($col, 'users')) {
                $this->forge->dropColumn('users', $col);
            }
        }
    }
}
