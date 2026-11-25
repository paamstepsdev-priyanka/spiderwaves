<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table            = 'permissions';
    protected $primaryKey       = 'permission_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'module_key',
        'permission_code',
        'permission_name',
        'permission_type',
        'action_key',
        'menu_id',
        'description',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get permission by unique code.
     */
    public function getByCode(string $code): ?array
    {
        return $this->where('permission_code', $code)->first();
    }

    /**
     * Get permissions grouped by module.
     */
    public function getPermissionsByModule(): array
    {
        $perms = $this->orderBy('module_key', 'ASC')->orderBy('permission_id', 'ASC')->findAll();
        $grouped = [];
        foreach ($perms as $p) {
            $grouped[$p['module_key']][] = $p;
        }
        return $grouped;
    }
}

