<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateNotificationsTypeColumn extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE notifications MODIFY COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'system'");
        $this->db->query("UPDATE notifications SET `type` = 'game' WHERE `game` > 0 AND (`type` = '' OR `type` IS NULL OR `type` = 'system')");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE notifications MODIFY COLUMN `type` ENUM('message','sing','system','low_balance','deposit','withdraw','purchase') NOT NULL DEFAULT 'system'");
    }
}
