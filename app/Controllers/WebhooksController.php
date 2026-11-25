<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\UccHasher;
use App\Models\UccWebhookLogModel;
use App\Models\UccSyncStateModel;

class WebhooksController extends ResourceController
{
    /**
     * Inbound webhook receiver for UCC master change events.
     * POST /webhooks/ucc/master_changed
     *
     * Verifies Bearer + HMAC-SHA256 + timestamp + nonce, records every call
     * in ucc_webhook_log, deduplicates repeated events, and triggers background sync.
     */
    public function masterChanged(): ResponseInterface
    {
        $expectedBearer = (string)(env('UCC_WEBHOOK_BEARER') ?: (getenv('UCC_WEBHOOK_BEARER') ?: (env('WEBHOOK_BEARER') ?: (getenv('WEBHOOK_BEARER') ?: ''))));
        $webhookSecret  = (string)(env('UCC_WEBHOOK_SECRET') ?: (getenv('UCC_WEBHOOK_SECRET') ?: (env('WEBHOOK_SECRET') ?: (getenv('WEBHOOK_SECRET') ?: ''))));

        $rawBody       = (string)$this->request->getBody();
        $sourceIp      = (string)$this->request->getIPAddress();
        $receivedAt    = date('Y-m-d H:i:s');
        $decodedBody   = json_decode($rawBody, true);
        $masterVersion = (is_array($decodedBody) && is_string($decodedBody['master_version'] ?? null))
            ? $decodedBody['master_version']
            : null;
        $isTestPayload = !empty($decodedBody['test']) ? 1 : 0;

        $logModel = new UccWebhookLogModel();

        if (empty($expectedBearer) || empty($webhookSecret)) {
            $errMsg = 'Spider UCC webhook credentials (UCC_WEBHOOK_BEARER, UCC_WEBHOOK_SECRET) are not configured in .env.';
            $this->safeInsertLog($logModel, [
                'source_ip'         => $sourceIp,
                'master_version'    => $masterVersion,
                'is_test'           => $isTestPayload,
                'request_payload'   => $rawBody,
                'http_status'       => 503,
                'processing_status' => 'failed',
                'error_details'     => $errMsg,
                'received_at'       => $receivedAt,
                'processed_at'      => $receivedAt,
            ]);

            return $this->response->setStatusCode(503)->setJSON([
                'status'  => 'error',
                'message' => $errMsg,
            ]);
        }

        // Exact request path with query as received
        $pathWithQuery = $_SERVER['REQUEST_URI'] ?? '/webhooks/ucc/master_changed';

        // Nonce store with 15-minute TTL (written only after signature verification passes)
        $rememberNonce = function (string $nonce): bool {
            $cache = \Config\Services::cache();
            $cacheKey = 'ucc_wh_nonce_' . $nonce;
            if ($cache->get($cacheKey)) {
                return false;
            }
            $cache->save($cacheKey, 1, 900); // 15 minutes (900 seconds)
            return true;
        };

        // Collect all headers
        $headers = [];
        foreach ($this->request->headers() as $name => $header) {
            $headers[$name] = $header->getValueLine();
        }

        [$statusCode, $responseBody] = UccHasher::verifyWebhook(
            $headers,
            $this->request->getMethod(),
            $pathWithQuery,
            $rawBody,
            $expectedBearer,
            $webhookSecret,
            $rememberNonce
        );

        if ($statusCode !== 200) {
            $this->safeInsertLog($logModel, [
                'source_ip'         => $sourceIp,
                'master_version'    => $masterVersion,
                'is_test'           => $isTestPayload,
                'request_payload'   => $rawBody,
                'http_status'       => $statusCode,
                'processing_status' => 'failed',
                'error_details'     => (string)($responseBody['message'] ?? 'Verification failed'),
                'received_at'       => $receivedAt,
                'processed_at'      => date('Y-m-d H:i:s'),
            ]);

            return $this->response->setStatusCode($statusCode)->setJSON($responseBody);
        }

        // Test ping: verified 200, log as success, do not trigger sync
        if (!empty($responseBody['is_test'])) {
            $this->safeInsertLog($logModel, [
                'source_ip'         => $sourceIp,
                'master_version'    => $responseBody['master_version'] ?? $masterVersion,
                'is_test'           => 1,
                'request_payload'   => $rawBody,
                'http_status'       => 200,
                'processing_status' => 'success',
                'error_details'     => null,
                'received_at'       => $receivedAt,
                'processed_at'      => date('Y-m-d H:i:s'),
            ]);

            return $this->response->setStatusCode(200)->setJSON($responseBody);
        }

        // Prevent duplicate synchronization if the same master_version is already applied or pending
        $verifiedVersion = (string)($responseBody['master_version'] ?? '');
        $stateModel = new UccSyncStateModel();
        $state = $stateModel->getState();
        $lastAppliedVersion = !empty($state['last_version']) ? (string)$state['last_version'] : '';

        $isAlreadyApplied = ($lastAppliedVersion !== '' && $verifiedVersion !== '' && hash_equals($lastAppliedVersion, $verifiedVersion));
        $isDuplicateInLog = $logModel->isDuplicateVersion($verifiedVersion);

        if ($isAlreadyApplied || $isDuplicateInLog) {
            $this->safeInsertLog($logModel, [
                'source_ip'         => $sourceIp,
                'master_version'    => $verifiedVersion,
                'is_test'           => 0,
                'request_payload'   => $rawBody,
                'http_status'       => 200,
                'processing_status' => 'success',
                'error_details'     => 'Duplicate/already-synced master_version; sync skipped.',
                'received_at'       => $receivedAt,
                'processed_at'      => date('Y-m-d H:i:s'),
            ]);

            return $this->response->setStatusCode(200)->setJSON($responseBody);
        }

        // Record valid notification as pending and queue background sync
        $logId = $this->safeInsertLog($logModel, [
            'source_ip'         => $sourceIp,
            'master_version'    => $verifiedVersion,
            'is_test'           => 0,
            'request_payload'   => $rawBody,
            'http_status'       => 200,
            'processing_status' => 'pending',
            'error_details'     => null,
            'received_at'       => $receivedAt,
            'processed_at'      => null,
        ]);

        $lockDir = WRITEPATH . 'locks';
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0755, true);
        }

        $flagData = [
            'triggered_at'   => $receivedAt,
            'master_version' => $verifiedVersion,
            'webhook_log_id' => $logId,
        ];
        file_put_contents($lockDir . '/ucc_sync_pending.flag', json_encode($flagData));

        // Non-blocking background execution of spark ucc:sync
        $sparkArg = escapeshellarg(ROOTPATH . 'spark');
        $logOption = $logId > 0 ? (' --webhook-log=' . $logId) : '';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $sparkCmd = 'start /B php ' . $sparkArg . ' ucc:sync' . $logOption . ' > NUL 2>&1';
            @pclose(@popen($sparkCmd, 'r'));
        } else {
            @exec('php ' . $sparkArg . ' ucc:sync' . $logOption . ' > /dev/null 2>&1 &');
        }

        return $this->response->setStatusCode(200)->setJSON($responseBody);
    }

    /**
     * Safely insert a webhook audit log row without breaking the HTTP response.
     */
    protected function safeInsertLog(UccWebhookLogModel $logModel, array $data): int
    {
        try {
            return (int)$logModel->insert($data, true);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to write ucc_webhook_log: ' . $e->getMessage());
            return 0;
        }
    }
}
