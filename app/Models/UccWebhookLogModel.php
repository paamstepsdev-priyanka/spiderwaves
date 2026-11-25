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
     * Check if a non-test webhook for the same master_version is already pending or successfully processed.
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
     * Update a webhook log entry after background sync completes or fails.
     */
    public function markResult(int $logId, string $status, ?string $errorDetails = null): bool
    {
        return $this->update($logId, [
            'processing_status' => $status,
            'error_details'     => $errorDetails,
            'processed_at'      => date('Y-m-d H:i:s'),
        ]);
    }
}

