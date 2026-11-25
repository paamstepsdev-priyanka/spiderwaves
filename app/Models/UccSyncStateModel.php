<?php

namespace App\Models;

use CodeIgniter\Model;

class UccSyncStateModel extends Model
{
    protected $table            = 'ucc_sync_state';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'id',
        'last_version',
        'last_synced_at',
        'last_error',
    ];

    protected $useTimestamps = false;

    /**
     * Get the single state record (id = 1).
     */
    public function getState(): array
    {
        $state = $this->find(1);
        if (!$state) {
            $this->insert(['id' => 1, 'last_version' => null]);
            return ['id' => 1, 'last_version' => null, 'last_synced_at' => null, 'last_error' => null];
        }
        return $state;
    }

    /**
     * Update the last master version token and sync timestamp.
     */
    public function updateVersion(string $version, ?string $error = null): bool
    {
        return $this->update(1, [
            'last_version'   => $version,
            'last_synced_at' => date('Y-m-d H:i:s'),
            'last_error'     => $error,
        ]);
    }

    /**
     * Compatibility alias for older code if called.
     */
    public function updateSeq($seq, ?string $error = null): bool
    {
        return $this->updateVersion((string)$seq, $error);
    }

    /**
     * Record last sync failure error.
     */
    public function recordError(string $error): bool
    {
        return $this->update(1, [
            'last_error' => $error,
        ]);
    }
}

