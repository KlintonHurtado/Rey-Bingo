<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tablas RBAC staff Admin. El seed también corre vía bingo_ensure_permissions_schema().
 */
class CreateAdminPermissionsSystem extends Migration
{
    public function up()
    {
        helper('permissions');
        if (function_exists('bingo_ensure_permissions_schema')) {
            bingo_ensure_permissions_schema();
        }
    }

    public function down()
    {
        if ($this->db->tableExists('users') && $this->db->fieldExists('admin_role_id', 'users')) {
            $this->forge->dropColumn('users', 'admin_role_id');
        }
        if ($this->db->tableExists('admin_role_permissions')) {
            $this->forge->dropTable('admin_role_permissions', true);
        }
        if ($this->db->tableExists('admin_permissions')) {
            $this->forge->dropTable('admin_permissions', true);
        }
        if ($this->db->tableExists('admin_roles')) {
            $this->forge->dropTable('admin_roles', true);
        }
    }
}
