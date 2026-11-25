<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionAuditLogModel extends Model
{
    protected $table            = 'permission_audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'actor_user_id',
        'target_type',
        'target_id',
        'action',
        'details',
        'ip_address',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * Log an audit event.
     */
    public function logEvent(int $actorUserId, string $targetType, int $targetId, string $action, array $details, ?string $ip = null): bool
    {
        return (bool)$this->insert([
            'actor_user_id' => $actorUserId,
            'target_type'   => $targetType,
            'target_id'     => $targetId,
            'action'        => $action,
            'details'       => json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'ip_address'    => $ip ?? service('request')->getIPAddress(),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}

