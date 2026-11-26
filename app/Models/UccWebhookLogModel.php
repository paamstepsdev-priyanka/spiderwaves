<?php

namespace App\Models;

use CodeIgniter\Model;

class UccWebhookLogModel extends Model
{
    protected $table            = 'ucc_webhook_log';
    protected $primaryKey       = 'log_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'source_ip',
        'master_version',
        'is_test',
        'request_payload',
        'http_status',
        'processing_status',
        'error_details',
        'received_at',
        'processed_at',
    ];

    protected $useTimestamps = false;

    /**
     * Check if a non-test webhook for the same master_version is already pending manual sync or successfully synced.
     * Failed rows do NOT block retries.
     */
    public function isDuplicateVersion(string $masterVersion): bool
    {
        if ($masterVersion === '') {
            return false;
        }

        return $this->where('master_version', $masterVersion)
            ->where('is_test', 0)
            ->whereIn('processing_status', ['pending', 'success'])
            ->countAllResults() > 0;
    }

    /**
     * Count all non-test webhook notifications currently pending manual sync.
     */
    public function countPending(): int
    {
        return (int)$this->where('is_test', 0)
            ->where('processing_status', 'pending')
            ->countAllResults();
    }

    /**
     * Update all pending (and on success, previously failed) non-test webhook notifications
     * after a manual Sync Now run completes or fails.
     */
    public function markAllPendingResult(string $status, ?string $errorDetails = null): int
    {
        $now = date('Y-m-d H:i:s');
        $affected = 0;

        $this->where('is_test', 0)
            ->where('processing_status', 'pending')
            ->set([
                'processing_status' => $status,
                'error_details'     => $errorDetails,
                'processed_at'      => $now,
            ])
            ->update();
        $affected += (int)$this->db->affectedRows();

        if ($status === 'success') {
            $this->where('is_test', 0)
                ->where('processing_status', 'failed')
                ->where('http_status', 200)
                ->set([
                    'processing_status' => 'success',
                    'error_details'     => 'Resolved by manual sync.',
                    'processed_at'      => $now,
                ])
                ->update();
            $affected += (int)$this->db->affectedRows();
        }

        return $affected;
    }

    /**
     * Update a webhook log entry after sync completes or fails.
     */
    public function markResult(int $logId, string $status, ?string $errorDetails = null, ?int $httpStatus = null): bool
    {
        $data = [
            'processing_status' => $status,
            'error_details'     => $errorDetails,
            'processed_at'      => date('Y-m-d H:i:s'),
        ];

        if ($httpStatus !== null) {
            $data['http_status'] = $httpStatus;
        }

        return $this->update($logId, $data);
    }

    /**
     * Fetch recent webhook deliveries for local status monitoring.
     */
    public function getRecentLogs(int $limit = 15): array
    {
        return $this->orderBy('log_id', 'DESC')->findAll(max(1, $limit));
    }
}

