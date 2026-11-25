<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MigrateToTwoTableRolePermissions extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // 1. Add permission_key column to role_permissions if not already present
        $fields = $db->getFieldNames('role_permissions');
        if (!in_array('permission_key', $fields, true)) {
            $this->forge->addColumn('role_permissions', [
                'permission_key' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'role_id',
                ],
            ]);
        }

        // 2. Populate permission_key from permissions.permission_code
        if ($db->tableExists('permissions')) {
            $db->query("
                UPDATE role_permissions rp
                JOIN permissions p ON p.permission_id = rp.permission_id
                SET rp.permission_key = p.permission_code
                WHERE rp.permission_key IS NULL OR rp.permission_key = ''
            ");
        }

        // 3. Make permission_key NOT NULL and permission_id NULLABLE
        $this->forge->modifyColumn('role_permissions', [
            'permission_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'permission_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
            ],
        ]);

        // 4. Add unique index on (role_id, permission_key)
        $indexes = $db->getIndexData('role_permissions');
        $indexNames = array_map(fn($idx) => $idx->name, $indexes);

        if (!in_array('uq_role_perm_key', $indexNames, true)) {
            $db->query("ALTER TABLE role_permissions ADD UNIQUE KEY `uq_role_perm_key` (`role_id`, `permission_key`)");
        }

        if (!in_array('idx_perm_key', $indexNames, true)) {
            $db->query("ALTER TABLE role_permissions ADD KEY `idx_perm_key` (`permission_key`)");
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $indexes = $db->getIndexData('role_permissions');
        $indexNames = array_map(fn($idx) => $idx->name, $indexes);

        if (in_array('uq_role_perm_key', $indexNames, true)) {
            $db->query("ALTER TABLE role_permissions DROP INDEX `uq_role_perm_key`");
        }
        if (in_array('idx_perm_key', $indexNames, true)) {
            $db->query("ALTER TABLE role_permissions DROP INDEX `idx_perm_key`");
        }
        $fields = $db->getFieldNames('role_permissions');
        if (in_array('permission_key', $fields, true)) {
            $this->forge->dropColumn('role_permissions', 'permission_key');
        }
    }
}

