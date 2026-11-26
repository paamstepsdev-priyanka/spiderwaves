<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use App\Services\UccRevalidateService;
use App\Services\UccSyncService;
use App\Services\UccClient;
use App\Models\UccSyncStateModel;
use App\Models\UccSyncConflictModel;
use App\Models\UccSyncCheckLogModel;
use App\Models\UccMappedCustomerModel;
use App\Models\UccWebhookLogModel;

class UccRevalidateController extends BaseController
{
    protected UccRevalidateService $revalidateService;
    protected UccSyncService $syncService;
    protected UccSyncStateModel $stateModel;
    protected UccSyncConflictModel $conflictModel;
    protected UccSyncCheckLogModel $checkLogModel;
    protected UccMappedCustomerModel $mappedCustomerModel;
    protected UccWebhookLogModel $webhookLogModel;

    public function __construct()
    {
        $this->revalidateService   = new UccRevalidateService();
        $this->syncService         = new UccSyncService();
        $this->stateModel          = new UccSyncStateModel();
        $this->conflictModel       = new UccSyncConflictModel();
        $this->checkLogModel       = new UccSyncCheckLogModel();
        $this->mappedCustomerModel = new UccMappedCustomerModel();
        $this->webhookLogModel     = new UccWebhookLogModel();
    }

    /**
     * Check authorization: Super Admin & Admin only.
     */
    protected function checkAuthorized(): ?ResponseInterface
    {
        $session = session()->get('spider') ?? [];
        $accessLevel = $session['access_level'] ?? '';

        if (!in_array($accessLevel, ['admin', 'superadmin'], true)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => 'error',
                    'message' => 'Unauthorized access. Only Admin and Super Admin can access Revalidate Masters.',
                ]);
            }
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access to Revalidate Masters.');
        }

        return null;
    }

    /**
     * Build local webhook + sync status without calling the remote UCC API.
     */
    protected function buildLocalStatus(bool $isConfigured): array
    {
        $state = $this->stateModel->getState();
        $recentWebhooks = $this->webhookLogModel->getRecentLogs(20);
        $latestWebhook = $recentWebhooks[0] ?? null;
        $pendingCount = $this->webhookLogModel->countPending();

        $entityCounts = [];
        foreach (UccSyncService::ENTITY_ORDER as $entity) {
            $model = $this->syncService->getModelForEntity($entity);
            $entityCounts[$entity] = (int)$model->where('deleted_at IS NULL')->countAllResults();
        }

        return [
            'is_configured'   => $isConfigured,
            'state'           => $state,
            'entity_counts'   => $entityCounts,
            'recent_webhooks' => $recentWebhooks,
            'latest_webhook'  => $latestWebhook,
            'pending_count'   => $pendingCount,
        ];
    }

    /**
     * Revalidate Masters Dashboard view (local webhook & sync monitor, zero UCC API polling).
     */
    public function index()
    {
        if ($redirect = $this->checkAuthorized()) {
            return $redirect;
        }

        $client = new UccClient();
        $isConfigured = $client->isConfigured();
        $local = $this->buildLocalStatus($isConfigured);

        $openConflicts = $this->conflictModel->getOpenConflicts();
        $orphanedReferences = $this->mappedCustomerModel->getOrphanedReferences();

        $data = [
            'title'               => 'UCC Webhook Sync Status',
            'isConfigured'        => $isConfigured,
            'state'               => $local['state'],
            'entityCounts'        => $local['entity_counts'],
            'recentWebhooks'      => $local['recent_webhooks'],
            'latestWebhook'       => $local['latest_webhook'],
            'pendingCount'        => $local['pending_count'],
            'openConflicts'       => $openConflicts,
            'orphanedReferences'  => $orphanedReferences,
        ];

        return view('master/ucc_revalidate/index', $data);
    }

    /**
     * AJAX endpoint: Local webhook & sync status (no remote UCC API calls).
     */
    public function liveStatus(): ResponseInterface
    {
        if ($redirect = $this->checkAuthorized()) {
            return $redirect;
        }

        $client = new UccClient();
        $local = $this->buildLocalStatus($client->isConfigured());

        return $this->response->setJSON([
            'status'      => 'success',
            'live_status' => [
                'is_configured'  => $local['is_configured'],
                'local_version'  => $local['state']['last_version'] ?? null,
                'last_synced_at' => $local['state']['last_synced_at'] ?? null,
                'last_error'     => $local['state']['last_error'] ?? null,
                'entity_counts'  => $local['entity_counts'],
                'latest_webhook' => $local['latest_webhook'],
            ],
        ]);
    }

    /**
     * AJAX endpoint: Run manual UCC master sync now and update pending webhook logs.
     */
    public function syncNow(): ResponseInterface
    {
        if ($redirect = $this->checkAuthorized()) {
            return $redirect;
        }

        $client = new UccClient();
        if (!$client->isConfigured()) {
            return $this->response->setStatusCode(503)->setJSON([
                'status'  => 'error',
                'message' => 'UCC API credentials are not configured in .env.',
            ]);
        }

        @set_time_limit(120);

        try {
            $syncResult = $this->syncService->sync(false);
            if (($syncResult['status'] ?? '') === 'locked') {
                return $this->response->setStatusCode(409)->setJSON([
                    'status'  => 'locked',
                    'message' => 'Another UCC synchronization is already in progress. Please wait and try again.',
                ]);
            }

            $this->webhookLogModel->markAllPendingResult('success', null);
            $this->revalidateService->clearStatusCache();

            $msg = (($syncResult['status'] ?? '') === 'noop')
                ? 'UCC master data is already up to date.'
                : 'UCC master data synchronized successfully.';

            return $this->response->setJSON([
                'status'      => 'success',
                'message'     => $msg,
                'sync_result' => $syncResult,
            ]);
        } catch (\Throwable $e) {
            $this->webhookLogModel->markAllPendingResult('failed', $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Sync failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * AJAX endpoint: Trigger fresh Revalidation check.
     */
    public function runCheck(): ResponseInterface
    {
        if ($redirect = $this->checkAuthorized()) {
            return $redirect;
        }

        $session = session()->get('spider') ?? [];
        $username = $session['username'] ?? 'Admin';

        // Check rate limiting
        if ($this->revalidateService->isRateLimited($username)) {
            return $this->response->setStatusCode(429)->setJSON([
                'status'  => 'rate_limited',
                'message' => 'Rate limit: Revalidation can only be executed once every 60 seconds.',
            ]);
        }

        $summary = $this->revalidateService->runCheck($username);

        return $this->response->setJSON([
            'status'  => 'success',
            'summary' => $summary,
        ]);
    }

    /**
     * AJAX endpoint: Sync missing and changed rows for a specific entity.
     */
    public function syncDifferences(): ResponseInterface
    {
        if ($redirect = $this->checkAuthorized()) {
            return $redirect;
        }

        $entity = (string)$this->request->getPost('entity');
        $keys   = (array)$this->request->getPost('keys');

        if (empty($entity) || empty($keys)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Entity and list of private_keys are required.',
            ]);
        }

        try {
            $result = $this->syncService->syncSpecificKeys($entity, $keys);
            $this->revalidateService->clearStatusCache();
            $session = session()->get('spider') ?? [];
            $username = $session['username'] ?? $session['user'] ?? 'Admin';
            $this->revalidateService->runCheck($username);
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => "Synchronized {$result['applied']} rows for {$entity} ({$result['conflicts']} conflicts logged).",
                'result'  => $result,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Sync failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * AJAX endpoint: Soft-delete extra rows that UCC no longer lists.
     */
    public function softDeleteExtra(): ResponseInterface
    {
        if ($redirect = $this->checkAuthorized()) {
            return $redirect;
        }

        $entity = (string)$this->request->getPost('entity');
        $keys   = (array)$this->request->getPost('keys');

        if (empty($entity) || empty($keys)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Entity and list of private_keys are required.',
            ]);
        }

        try {
            $count = $this->syncService->softDeleteExtraRows($entity, $keys);
            $this->revalidateService->clearStatusCache();
            $session = session()->get('spider') ?? [];
            $username = $session['username'] ?? $session['user'] ?? 'Admin';
            $this->revalidateService->runCheck($username);
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => "Soft-deleted {$count} extra rows in {$entity}.",
                'count'   => $count,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Soft-delete failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * AJAX endpoint: Trigger full re-snapshot.
     */
    public function fullSnapshot(): ResponseInterface
    {
        if ($redirect = $this->checkAuthorized()) {
            return $redirect;
        }

        $confirm = $this->request->getPost('confirm');
        if ($confirm !== 'CONFIRM_FULL_SNAPSHOT') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Explicit confirmation token required for full re-snapshot.',
            ]);
        }

        try {
            $result = $this->syncService->runFullSnapshot();
            $this->revalidateService->clearStatusCache();
            $session = session()->get('spider') ?? [];
            $username = $session['username'] ?? $session['user'] ?? 'Admin';
            $this->revalidateService->runCheck($username);
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Full snapshot successfully completed.',
                'result'  => $result,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Full snapshot failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Download detected differences as CSV.
     */
    public function downloadCsv(): ResponseInterface
    {
        if ($redirect = $this->checkAuthorized()) {
            return $redirect;
        }

        $latestLog = $this->checkLogModel->getLatestLog();
        $summary = !empty($latestLog['summary_json']) ? json_decode($latestLog['summary_json'], true) : [];

        $csv = $this->revalidateService->generateDifferencesCsv($summary);

        $filename = 'ucc_master_differences_' . date('Ymd_His') . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }
}

