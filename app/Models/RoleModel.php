<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'role_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'role_key',
        'role_name',
        'description',
        'is_system',
        'status',
        'version',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Find role by unique key.
     */
    public function getByRoleKey(string $roleKey): ?array
    {
        return $this->where('role_key', $roleKey)->first();
    }

    /**
     * Get all active roles ordered by role_id.
     */
    public function getActiveRoles(): array
    {
        return $this->where('status', '1')->orderBy('role_id', 'ASC')->findAll();
    }

    /**
     * Bump role version atomically in MySQL.
     */
    public function bumpVersion(int $roleId): bool
    {
        return $this->db->table($this->table)
            ->where('role_id', $roleId)
            ->set('version', 'version + 1', false)
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->update();
    }
}

