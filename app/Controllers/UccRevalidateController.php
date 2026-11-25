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

class UccRevalidateController extends BaseController
{
    protected UccRevalidateService $revalidateService;
    protected UccSyncService $syncService;
    protected UccSyncStateModel $stateModel;
    protected UccSyncConflictModel $conflictModel;
    protected UccSyncCheckLogModel $checkLogModel;
    protected UccMappedCustomerModel $mappedCustomerModel;

    public function __construct()
    {
        $this->revalidateService   = new UccRevalidateService();
        $this->syncService         = new UccSyncService();
        $this->stateModel          = new UccSyncStateModel();
        $this->conflictModel       = new UccSyncConflictModel();
        $this->checkLogModel       = new UccSyncCheckLogModel();
        $this->mappedCustomerModel = new UccMappedCustomerModel();
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
     * Revalidate Masters Dashboard view.
     */
    public function index()
    {
        if ($redirect = $this->checkAuthorized()) {
            return $redirect;
        }

        $client = new UccClient();
        $isConfigured = $client->isConfigured();

        $state = $this->stateModel->getState();
        $latestLog = $this->checkLogModel->getLatestLog();
        $openConflicts = $this->conflictModel->getOpenConflicts();
        $orphanedReferences = $this->mappedCustomerModel->getOrphanedReferences();

        $latestSummary = null;
        if (!empty($latestLog['summary_json'])) {
            $latestSummary = json_decode($latestLog['summary_json'], true);
        }

        $liveStatus = $this->revalidateService->getLiveStatus(true);

        $data = [
            'title'               => 'Revalidate Masters (UCC)',
            'isConfigured'        => $isConfigured,
            'state'               => $state,
            'latestLog'           => $latestLog,
            'latestSummary'       => $latestSummary,
            'liveStatus'          => $liveStatus,
            'openConflicts'       => $openConflicts,
            'orphanedReferences'  => $orphanedReferences,
        ];

        return view('master/ucc_revalidate/index', $data);
    }

    /**
     * AJAX endpoint: Lightweight live sync status & pending changes check.
     */
    public function liveStatus(): ResponseInterface
    {
        if ($redirect = $this->checkAuthorized()) {
            return $redirect;
        }

        $force = (bool)$this->request->getGet('force');
        $status = $this->revalidateService->getLiveStatus($force);

        return $this->response->setJSON([
            'status'      => 'success',
            'live_status' => $status,
        ]);
    }

    /**
     * AJAX endpoint: Run incremental sync now and refresh revalidation log.
     */
    public function syncNow(): ResponseInterface
    {
        if ($redirect = $this->checkAuthorized()) {
            return $redirect;
        }

        $session = session()->get('spider') ?? [];
        $username = $session['username'] ?? $session['user'] ?? 'Admin';

        try {
            $syncResult = $this->syncService->sync(false);
            $this->revalidateService->clearStatusCache();
            $summary = $this->revalidateService->runCheck($username);

            return $this->response->setJSON([
                'status'      => 'success',
                'message'     => 'UCC master data synchronized and verified.',
                'sync_result' => $syncResult,
                'summary'     => $summary,
            ]);
        } catch (\Throwable $e) {
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

