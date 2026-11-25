<?php

namespace App\Models;

use CodeIgniter\Model;

class RolePermissionModel extends Model
{
    protected $table            = 'role_permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'role_id',
        'permission_key',
        'permission_id',
        'is_granted',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get granted permission keys for role as an array of strings.
     */
    public function getGrantedPermissionKeys(int $roleId): array
    {
        $rows = $this->where('role_id', $roleId)
                     ->where('is_granted', 1)
                     ->findAll();
        return array_column($rows, 'permission_key');
    }

    /**
     * Check if a role possesses a specific permission key.
     */
    public function hasPermissionKey(int $roleId, string $permissionKey): bool
    {
        $row = $this->where('role_id', $roleId)
                    ->where('permission_key', $permissionKey)
                    ->first();
        return ($row !== null && (int)$row['is_granted'] === 1);
    }

    /**
     * Get granted permission IDs for role (backward compatibility).
     */
    public function getGrantedPermissionIds(int $roleId): array
    {
        $rows = $this->where('role_id', $roleId)
                     ->where('is_granted', 1)
                     ->findAll();
        return array_map('intval', array_column($rows, 'permission_id'));
    }
}

