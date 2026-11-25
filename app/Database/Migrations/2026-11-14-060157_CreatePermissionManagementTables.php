<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePermissionManagementTables extends Migration
{
    public function up()
    {
        // 1. ROLES TABLE
        $this->forge->addField([
            'role_id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'role_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'role_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'is_system' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['1', '0'],
                'default'    => '1',
                'null'       => false,
            ],
            'version' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
                'null'       => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addPrimaryKey('role_id');
        $this->forge->addUniqueKey('role_key', 'uq_role_key');
        $this->forge->addKey('status', false, false, 'idx_role_status');
        $this->forge->createTable('roles', true);

        // 2. MENUS TABLE
        $this->forge->addField([
            'menu_id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'parent_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'menu_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'url' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'icon' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'menu_type' => [
                'type'       => 'ENUM',
                'constraint' => ['sidebar', 'navbar', 'both'],
                'default'    => 'sidebar',
                'null'       => false,
            ],
            'section_header' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'badge_callback' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'order_no' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'null'       => false,
            ],
            'module_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['1', '0'],
                'default'    => '1',
                'null'       => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addPrimaryKey('menu_id');
        $this->forge->addUniqueKey('menu_code', 'uq_menu_code');
        $this->forge->addKey('parent_id', false, false, 'idx_menu_parent');
        $this->forge->addKey(['menu_type', 'status', 'order_no'], false, false, 'idx_menu_lookup');
        $this->forge->addKey('module_key', false, false, 'idx_menu_module');
        $this->forge->addForeignKey('parent_id', 'menus', 'menu_id', 'CASCADE', 'CASCADE', 'fk_menu_parent');
        $this->forge->createTable('menus', true);

        // 3. PERMISSIONS TABLE
        $this->forge->addField([
            'permission_id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'module_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'permission_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'permission_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'permission_type' => [
                'type'       => 'ENUM',
                'constraint' => ['page', 'button', 'api', 'special'],
                'default'    => 'page',
                'null'       => false,
            ],
            'action_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'menu_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addPrimaryKey('permission_id');
        $this->forge->addUniqueKey('permission_code', 'uq_permission_code');
        $this->forge->addKey(['module_key', 'permission_type', 'action_key'], false, false, 'idx_perm_lookup');
        $this->forge->addForeignKey('menu_id', 'menus', 'menu_id', 'CASCADE', 'SET NULL', 'fk_perm_menu');
        $this->forge->createTable('permissions', true);

        // 4. ROLE_MENU_PERMISSIONS TABLE
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'role_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'menu_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'is_visible' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
            ],
            'order_no' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'null'       => false,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'updated_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['role_id', 'menu_id'], 'uq_role_menu');
        $this->forge->addKey(['role_id', 'is_visible', 'order_no'], false, false, 'idx_rmp_lookup');
        $this->forge->addForeignKey('role_id', 'roles', 'role_id', 'CASCADE', 'CASCADE', 'fk_rmp_role');
        $this->forge->addForeignKey('menu_id', 'menus', 'menu_id', 'CASCADE', 'CASCADE', 'fk_rmp_menu');
        $this->forge->createTable('role_menu_permissions', true);

        // 5. ROLE_PERMISSIONS TABLE
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'role_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'permission_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'is_granted' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'updated_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['role_id', 'permission_id'], 'uq_role_permission');
        $this->forge->addKey(['role_id', 'is_granted'], false, false, 'idx_rp_lookup');
        $this->forge->addForeignKey('role_id', 'roles', 'role_id', 'CASCADE', 'CASCADE', 'fk_rp_role');
        $this->forge->addForeignKey('permission_id', 'permissions', 'permission_id', 'CASCADE', 'CASCADE', 'fk_rp_perm');
        $this->forge->createTable('role_permissions', true);

        // 6. USER_PERMISSION_OVERRIDES TABLE
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'permission_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'is_granted' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
            ],
            'reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'updated_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'permission_id'], 'uq_user_permission');
        $this->forge->addKey('user_id', false, false, 'idx_upo_user');
        $this->forge->addForeignKey('permission_id', 'permissions', 'permission_id', 'CASCADE', 'CASCADE', 'fk_upo_perm');
        $this->forge->createTable('user_permission_overrides', true);

        // 7. PERMISSION_AUDIT_LOGS TABLE
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'actor_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
            ],
            'target_type' => [
                'type'       => 'ENUM',
                'constraint' => ['role', 'user'],
                'null'       => false,
            ],
            'target_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'details' => [
                'type' => 'MEDIUMTEXT',
                'null' => false,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['target_type', 'target_id'], false, false, 'idx_audit_target');
        $this->forge->addKey('actor_user_id', false, false, 'idx_audit_actor');
        $this->forge->addKey('created_at', false, false, 'idx_audit_created');
        $this->forge->createTable('permission_audit_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('permission_audit_logs', true);
        $this->forge->dropTable('user_permission_overrides', true);
        $this->forge->dropTable('role_permissions', true);
        $this->forge->dropTable('role_menu_permissions', true);
        $this->forge->dropTable('permissions', true);
        $this->forge->dropTable('menus', true);
        $this->forge->dropTable('roles', true);
    }
}
