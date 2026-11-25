<?php

namespace App\Models;

use CodeIgniter\Model;

class UccSyncCheckLogModel extends Model
{
    protected $table            = 'ucc_sync_check_log';
    protected $primaryKey       = 'check_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'check_id',
        'run_by',
        'run_at',
        'summary_json',
    ];

    protected $useTimestamps = false;

    /**
     * Log a manual or scheduled revalidation check.
     */
    public function logCheck(string $runBy, array $summary): int
    {
        return $this->insert([
            'run_by'       => $runBy,
            'run_at'       => date('Y-m-d H:i:s'),
            'summary_json' => json_encode($summary, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Get the latest check log.
     */
    public function getLatestLog(): ?array
    {
        return $this->orderBy('check_id', 'DESC')->first();
    }
}

