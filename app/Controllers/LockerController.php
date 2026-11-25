<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProcessWavesModel;
use App\Models\AccountModel;
use App\Models\StockPointModel;
use App\Services\LedgerService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * LockerController
 * 
 * MODULE 2: LOCKER
 * Dedicated physical stock vault module.
 * 
 * Functional Responsibilities:
 * - Locker Receipt: Inward metal awaiting physical workshop transfer
 * - Locker Issue: Outward metal dispatched to workshop departments
 * - Fine MT and Fine MT Balance calculation
 * - Check Stock workflow integration
 * - Transfer trigger into workshop pipeline
 */
class LockerController extends BaseController
{
    protected ProcessWavesModel $processWavesModel;
    protected AccountModel $accountModel;
    protected StockPointModel $stockPointModel;
    protected LedgerService $ledgerService;

    public function __construct()
    {
        $this->processWavesModel = model(ProcessWavesModel::class);
        $this->accountModel      = model(AccountModel::class);
        $this->stockPointModel   = model(StockPointModel::class);
        $this->ledgerService     = new LedgerService();
    }

    /**
     * Main Locker Stock Dashboard.
     * Reproduces exact legacy Locker screen (Receipt and Issue cards).
     */
    public function index()
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';
        $allowedRoles = ['admin', 'superadmin', 'process_manager', 'locker'];
        if (!in_array($accessLevel, $allowedRoles, true)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access to Locker module.');
        }

        $startDate = $this->ledgerService->getActiveStockPointDate();

        // Account 1 is Locker
        $lockerAccount = $this->accountModel->getAccountById(AccountModel::STOCK_ID);
        if (!$lockerAccount) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Locker account not found.');
        }

        // Fetch Locker Receipt & Issue datasets
        $receiptData = $this->processWavesModel->getLockerReceiptData($startDate);
        $issueData   = $this->processWavesModel->getLockerIssueData($startDate);

        // Balances
        $openingBalance = $this->ledgerService->getOpeningBalance(AccountModel::STOCK_ID);
        $receiveTotal   = $this->ledgerService->getAccountTotalReceiveBalance(AccountModel::STOCK_ID, null, 'fine', $startDate);
        $issueTotal     = $this->ledgerService->getAccountTotalIssueBalance(AccountModel::STOCK_ID, null, 'fine', $startDate);
        $fineMtBalance  = round(($receiveTotal + $openingBalance) - $issueTotal, 3);

        $data = [
            'title'               => 'Locker',
            'accountName'         => $lockerAccount['account_name'] ?? 'Locker',
            'accountId'           => AccountModel::STOCK_ID,
            'accountPrivateKey'   => $lockerAccount['private_key'] ?? AccountModel::LOCKER_PRIVATE_KEY,
            'startDate'           => $startDate,
            'receiptData'         => $receiptData,
            'issueData'           => $issueData,
            'openingBalance'      => $openingBalance,
            'receiveTotalBalance' => $receiveTotal + $openingBalance,
            'issueTotalBalance'   => $issueTotal,
            'fineMtBalance'       => $fineMtBalance,
            'pendingCount'        => $this->getPendingRawWavesCount(),
        ];

        return view('locker/index', $data);
    }

    /**
     * Check Stock Validation Endpoint (AJAX POST).
     * Validates all workshop departments prior to creating a reconciliation milestone.
     */
    public function checkStock(): ResponseInterface
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';
        $allowedRoles = ['admin', 'superadmin', 'process_manager', 'locker'];
        if (!in_array($accessLevel, $allowedRoles, true)) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'error' => 'Unauthorized access to Locker check stock.']);
        }

        $startDate = $this->ledgerService->getActiveStockPointDate();
        $result = $this->ledgerService->checkStockValidation($startDate);

        return $this->response->setJSON($result);
    }

    /**
     * Helper to retrieve pending raw waves count for breadcrumb badge.
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
