<?php

namespace App\Models;

use CodeIgniter\Model;

class UccSyncConflictModel extends Model
{
    protected $table            = 'ucc_sync_conflict';
    protected $primaryKey       = 'conflict_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'conflict_id',
        'entity',
        'ucc_id',
        'ucc_private_key',
        'local_id',
        'local_private_key',
        'reason',
        'detected_at',
        'resolved_at',
    ];

    protected $useTimestamps = false;

    /**
     * Get all currently unresolved conflicts.
     */
    public function getOpenConflicts(): array
    {
        return $this->where('resolved_at IS NULL')
            ->orderBy('detected_at', 'DESC')
            ->findAll();
    }

    /**
     * Record a new sync conflict.
     */
    public function recordConflict(string $entity, int $uccId, string $uccPrivateKey, ?int $localId, ?string $localPrivateKey, string $reason): int
    {
        return $this->insert([
            'entity'            => $entity,
            'ucc_id'            => $uccId,
            'ucc_private_key'   => $uccPrivateKey,
            'local_id'          => $localId,
            'local_private_key' => $localPrivateKey,
            'reason'            => $reason,
            'detected_at'       => date('Y-m-d H:i:s'),
            'resolved_at'       => null,
        ]);
    }
}

