<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProcessWavesModel;
use App\Models\AccountModel;
use App\Services\LedgerService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * LockerStockVerificationController
 * 
 * MODULE 3: LOCKER STOCK VERIFICATION
 * Dedicated physical stock verification module.
 * 
 * Functional Responsibilities:
 * - Current Stock: Lists unverified metal batches residing in Locker vault
 * - Barcode Scan & Lookup: Validates physical voucher barcodes
 * - Stock Confirmation: Atomic verification state update (locker_stock_check_done = 1)
 * - Restart Verification Process: Resets reconciliation cycle safely
 */
class LockerStockVerificationController extends BaseController
{
    protected ProcessWavesModel $processWavesModel;
    protected AccountModel $accountModel;
    protected LedgerService $ledgerService;

    public function __construct()
    {
        $this->processWavesModel = model(ProcessWavesModel::class);
        $this->accountModel      = model(AccountModel::class);
        $this->ledgerService     = new LedgerService();
    }

    /**
     * Display the Locker Stock Verification workspace.
     * Matches exact legacy view: process_waves/locker_stock_verification.
     */
    public function index()
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';
        $allowedRoles = ['admin', 'superadmin', 'process_manager', 'locker'];
        if (!in_array($accessLevel, $allowedRoles, true)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access to Locker Stock Verification.');
        }

        $startDate = $this->ledgerService->getActiveStockPointDate();
        $lockerAccount = $this->accountModel->getAccountById(AccountModel::STOCK_ID);

        // Fetch current unverified stock batches in Locker
        $unverifiedStock = $this->processWavesModel->getLockerUnverifiedStock($startDate);

        // Calculate opening and receive balances
        $openingBalance = $this->ledgerService->getOpeningBalance(AccountModel::STOCK_ID);
        $receiveTotal   = $this->ledgerService->getAccountTotalReceiveBalance(AccountModel::STOCK_ID, null, 'fine', $startDate);

        $data = [
            'title'               => 'Locker Stock Verification',
            'accountName'         => $lockerAccount['account_name'] ?? 'Locker',
            'accountId'           => AccountModel::STOCK_ID,
            'accountPrivateKey'   => $lockerAccount['private_key'] ?? AccountModel::LOCKER_PRIVATE_KEY,
            'unverifiedStock'     => $unverifiedStock,
            'openingBalance'      => $openingBalance,
            'receiveTotalBalance' => round($receiveTotal + $openingBalance, 3),
            'pendingCount'        => $this->getPendingRawWavesCount(),
        ];

        return view('locker_stock_verification/index', $data);
    }

    /**
     * Mark a scanned stock batch as verified in Locker.
     * Compatible with legacy AJAX: process_waves/mark_locker_stock_verification_done.
     */
    public function markDone(): ResponseInterface
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';
        $allowedRoles = ['admin', 'superadmin', 'process_manager', 'locker'];
        if (!in_array($accessLevel, $allowedRoles, true)) {
            return $this->response->setStatusCode(403)->setBody('false');
        }

        $rawInput = $this->request->getPost('process_waves_id');
        $processWavesId = (int) trim((string) $rawInput);

        if ($processWavesId <= 0) {
            return $this->response->setBody('false');
        }

        // Verify the record exists and belongs to Locker (to_id = 1)
        $batch = $this->processWavesModel->getById($processWavesId);
        if (!$batch || (int)$batch['to_id'] !== AccountModel::STOCK_ID) {
            // Also try looking up by verification code if barcode scanner scanned voucher code
            $db = \Config\Database::connect();
            $alt = $db->table('raw_waves rw')
                ->join('process_waves pw', 'pw.gross_weight = rw.net_weight AND pw.to_id = 1', 'inner')
                ->where('rw.verification_code', (string)$rawInput)
                ->select('pw.process_waves_id')
                ->get()
                ->getRowArray();

            if ($alt && !empty($alt['process_waves_id'])) {
                $processWavesId = (int) $alt['process_waves_id'];
            } else {
                return $this->response->setBody('false');
            }
        }

        $success = $this->processWavesModel->markLockerVerificationDone($processWavesId);
        return $this->response->setContentType('text/plain')->setBody($success ? 'true' : 'false');
    }

    /**
     * Restart Locker Physical Stock Verification Cycle.
     * Resets locker_stock_check_done = '0' for active stock batches.
     * Compatible with legacy AJAX: process_waves/restart_locker_verification_process.
     */
    public function restart(): ResponseInterface
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';
        $allowedRoles = ['admin', 'superadmin', 'process_manager', 'locker'];
        if (!in_array($accessLevel, $allowedRoles, true)) {
            return $this->response->setStatusCode(403)->setContentType('text/plain')->setBody('false');
        }

        $success = $this->processWavesModel->restartLockerVerification();
        return $this->response->setContentType('text/plain')->setBody($success ? 'true' : 'false');
    }

    /**
     * Pending raw waves count for breadcrumb header badge.
     */
    protected function getPendingRawWavesCount(): int
    {
        try {
            $rawWavesModel = model(\App\Models\RawWavesModel::class);
            return (int) $rawWavesModel->where('approved_data', '0')->where('is_deleted', '0')->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
