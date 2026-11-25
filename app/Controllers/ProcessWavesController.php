<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProcessWavesModel;
use App\Models\AccountModel;
use App\Models\StockPointModel;
use App\Models\CustomerMappingModel;
use App\Models\CustomerModel;
use App\Models\PurityModel;
use App\Services\LedgerService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ProcessWavesController
 * 
 * MODULE 1: PROCESS WAVES
 * Core manufacturing workshop pipeline controller.
 * 
 * Functional Responsibilities:
 * - Departmental process account ledgers (Casting, Filing, Setting, Polish, Packing, etc.)
 * - Inter-departmental batch transfers with dynamic routing
 * - Strict source stock sufficiency validation (preventing negative/insufficient transfers)
 * - Loss validation against configured stage thresholds
 * - Piece count tracking for manufacturing checkpoints
 * - Stock checkpoint creation & historical closing balance snapshots
 * - Printable vouchers with barcode and QR codes
 */
class ProcessWavesController extends BaseController
{
    protected ProcessWavesModel $processWavesModel;
    protected AccountModel $accountModel;
    protected StockPointModel $stockPointModel;
    protected CustomerMappingModel $customerMappingModel;
    protected CustomerModel $customerModel;
    protected PurityModel $purityModel;
    protected LedgerService $ledgerService;

    public function __construct()
    {
        $this->processWavesModel    = model(ProcessWavesModel::class);
        $this->accountModel         = model(AccountModel::class);
        $this->stockPointModel      = model(StockPointModel::class);
        $this->customerMappingModel = model(CustomerMappingModel::class);
        $this->customerModel        = model(CustomerModel::class);
        $this->purityModel          = model(PurityModel::class);
        $this->ledgerService        = new LedgerService();
    }

    /**
     * Process Waves Dashboard / Account Switcher.
     * Redirects to the first accessible process account based on user access role.
     */
    public function index()
    {
        $session = session()->get('spider') ?? [];
        $accessLevel = $session['access_level'] ?? '';
        $navAccounts = $this->accountModel->getProcessNavigationAccounts($accessLevel);
        $firstKey = !empty($navAccounts) ? $navAccounts[0]['private_key'] : '80f372f8dffb5126736fa8c02a24c9fa';
        return redirect()->to(base_url('process-waves/manage/' . $firstKey));
    }

    /**
     * Process Account Ledger Screen.
     * Reproduces exact legacy process_waves/manage.php.
     * 
     * @param string $processAccountPrivateKey Private key of department account
     * @param string $fromAccountPrivateKey    Optional private key for loss/refining drill-down
     */
    public function manage(string $processAccountPrivateKey = '', string $fromAccountPrivateKey = '')
    {
        $session = session()->get('spider') ?? [];
        $accessLevel = $session['access_level'] ?? '';

        // Role authorization check
        if (!in_array($accessLevel, ['admin', 'superadmin', 'process_manager', 'jr_process_manager', 'g58'], true)) {
            if ($accessLevel === 'user') {
                return redirect()->to(base_url('raw-waves'))->with('error', 'Unauthorized access.');
            }
            if ($accessLevel === 'cust') {
                $pKey = $session['private_key'] ?? '';
                return redirect()->to(base_url('report/customer_ledger_for_customer/' . $pKey))->with('error', 'Unauthorized access.');
            }
            if ($accessLevel === 'locker') {
                return redirect()->to(base_url('locker'))->with('error', 'Locker custodian cannot access general workshop stages.');
            }
            return redirect()->to(base_url('login'));
        }

        $startDate = $this->ledgerService->getActiveStockPointDate();

        // If Locker key is passed, redirect to dedicated Locker module!
        if ($processAccountPrivateKey === AccountModel::LOCKER_PRIVATE_KEY) {
            return redirect()->to(base_url('locker'));
        }

        // If Loss key is passed, redirect to dedicated Loss module!
        if ($processAccountPrivateKey === AccountModel::LOSS_PRIVATE_KEY) {
            if (!empty($fromAccountPrivateKey)) {
                return redirect()->to(base_url('loss/manage/' . $fromAccountPrivateKey));
            }
            return redirect()->to(base_url('loss'));
        }

        // If Refining key is passed, redirect to dedicated Refining module!
        if ($processAccountPrivateKey === AccountModel::REFINING_PRIVATE_KEY) {
            return redirect()->to(base_url('refining'));
        }

        $account = $this->accountModel->getAccountByPrivateKey($processAccountPrivateKey);
        if (!$account) {
            // Default to first permitted stage based on role
            if ($accessLevel === 'jr_process_manager') {
                $account = $this->accountModel->getAccountById(AccountModel::PACKING_ID);
            } elseif ($accessLevel === 'g58') {
                $account = $this->accountModel->getAccountById(AccountModel::G58_POLISH_ID);
            } else {
                $account = $this->accountModel->getAccountById(AccountModel::CASTING_ID);
            }
        }

        $accountId = (int) $account['account_id'];

        // Stage-level authorization enforcement
        if ($accessLevel === 'jr_process_manager') {
            $allowedJrIds = [AccountModel::PACKING_ID, AccountModel::TRANSFER_PACKING_ID, AccountModel::RODIUM_ID];
            if (!in_array($accountId, $allowedJrIds, true)) {
                return redirect()->to(base_url('process-waves/manage/' . AccountModel::PACKING_PRIVATE_KEY))
                    ->with('error', 'Unauthorized process stage for Jr. Process Manager.');
            }
        } elseif ($accessLevel === 'g58') {
            $allowedG58Ids = [AccountModel::G58_POLISH_ID, AccountModel::G58_FILING_ID, AccountModel::G58_SETTING_ID, AccountModel::G58_TRANSFER_ID];
            if (!in_array($accountId, $allowedG58Ids, true)) {
                return redirect()->to(base_url('process-waves/manage/' . AccountModel::G58_POLISH_PRIVATE_KEY))
                    ->with('error', 'Unauthorized process stage for G58.');
            }
        }

        $accountName = $account['account_name'];

        $fromAccountId = null;
        if (!empty($fromAccountPrivateKey)) {
            $fromAccount = $this->accountModel->getAccountByPrivateKey($fromAccountPrivateKey);
            if ($fromAccount) {
                $fromAccountId = (int) $fromAccount['account_id'];
                $accountName   = $fromAccount['account_name'] . ' ' . $accountName;
            }
        }

        $filter = $this->request->getGet('filter') ?: 'shift'; // Default is 'shift' (Current Shift / Stock Period) matching CI2
        $page   = max(1, (int) ($this->request->getGet('page') ?: 1));
        $limit  = (int) ($this->request->getGet('limit') ?: 50);
        $offset = ($page - 1) * $limit;

        if ($filter === 'all') {
            // All historical records: ordered newest first (DESC) with pagination
            $receiptData = $this->processWavesModel->getDepartmentReceiptData($accountId, '', $fromAccountId, $limit, $offset);
            $issueData   = $this->processWavesModel->getDepartmentIssueData($accountId, '', $fromAccountId, $limit, $offset);
            $totalReceiptRecords = $this->processWavesModel->getDepartmentReceiptCount($accountId, '', $fromAccountId);
            $totalIssueRecords   = $this->processWavesModel->getDepartmentIssueCount($accountId, '', $fromAccountId);
            $isShowingAll = true;
        } else {
            // Default: Active Shift (date >= stock_point_start_date_time) matching legacy CI2 exactly
            $receiptData = $this->processWavesModel->getDepartmentReceiptData($accountId, $startDate, $fromAccountId);
            $issueData   = $this->processWavesModel->getDepartmentIssueData($accountId, $startDate, $fromAccountId);
            $totalReceiptRecords = count($receiptData);
            $totalIssueRecords   = count($issueData);
            $isShowingAll = false;
        }

        // Calculate balances: ALWAYS computed via SQL SUM over the active stock period + opening balance
        // Preserves exact financial calculations from legacy CI2 common_func_helper::get_account_total_receive_balance()
        $openingBalance    = $this->ledgerService->getOpeningBalance($accountId);
        $receiveTotal      = $this->ledgerService->getAccountTotalReceiveBalance($accountId, $fromAccountId, 'fine', $startDate);
        $issueTotal        = $this->ledgerService->getAccountTotalIssueBalance($accountId, $fromAccountId, 'fine', $startDate);
        $fineBalance       = round(($receiveTotal + $openingBalance) - $issueTotal, 3);

        $receiveGrossTotal = 0.0;
        $issueGrossTotal   = 0.0;
        $grossBalance      = 0.0;
        if ($accountId === AccountModel::FILING_ID) {
            $receiveGrossTotal = $this->ledgerService->getAccountTotalReceiveBalance($accountId, $fromAccountId, 'gross', $startDate);
            $issueGrossTotal   = $this->ledgerService->getAccountTotalIssueBalance($accountId, $fromAccountId, 'gross', $startDate);
            $grossBalance      = round(($receiveGrossTotal + $openingBalance) - $issueGrossTotal, 3);
        }

        // List of all active workshop accounts for sidebar/tabs
        $allAccounts = $this->accountModel->getActiveProcessAccounts();

        $data = [
            'title'                     => $accountName,
            'account'                   => $account,
            'accountId'                 => $accountId,
            'accountName'               => $accountName,
            'accountPrivateKey'         => $account['private_key'],
            'fromAccountId'             => $fromAccountId,
            'fromAccountPrivateKey'     => $fromAccountPrivateKey,
            'allAccounts'               => $allAccounts,
            'receiptData'               => $receiptData,
            'issueData'                 => $issueData,
            'totalReceiptRecords'       => $totalReceiptRecords,
            'totalIssueRecords'         => $totalIssueRecords,
            'openingBalance'            => $openingBalance,
            'receiveTotalBalance'       => $receiveTotal + $openingBalance,
            'issueTotalBalance'         => $issueTotal,
            'balance'                   => $fineBalance,
            'receiveTotalGrossBalance'  => $receiveGrossTotal + $openingBalance,
            'issueTotalGrossBalance'    => $issueGrossTotal,
            'grossBalance'              => $grossBalance,
            'startDate'                 => $startDate,
            'isShowingAll'              => $isShowingAll,
            'filter'                    => $filter,
            'page'                      => $page,
            'limit'                     => $limit,
            'pendingCount'              => $this->getPendingRawWavesCount(),
        ];

        return view('process_waves/manage', $data);
    }

    /**
     * Inter-Department Process Transfer Form / Modal.
     * Reproduces legacy process_waves/add_edit_process_waves.php.
     * 
     * @param int|null    $processAccountId Source account ID
     * @param int|null    $processWavesId   Batch process_waves_id (if transferring specific batch)
     * @param string      $isStockCheck     'true' | 'false'
     * @param string|null $rowPrivateKey    Optional row identifier
     */
    public function transfer(?int $processAccountId = null, ?int $processWavesId = null, string $isStockCheck = 'false', ?string $rowPrivateKey = null)
    {
        $session = session()->get('spider') ?? [];
        $accessLevel = $session['access_level'] ?? '';

        if (!in_array($accessLevel, ['admin', 'superadmin', 'process_manager', 'jr_process_manager', 'g58', 'locker'], true)) {
            return '<div class="alert alert-danger p-20">Unauthorized access to process transfer.</div>';
        }

        $batch = null;
        $custName = '';
        $mappedCustData = [];

        if ($processWavesId) {
            $batch = $this->processWavesModel->getById($processWavesId);
            if ($batch) {
                $processAccountId = (int) $batch['to_id'];
                if (!empty($batch['cust_id'])) {
                    $c = $this->customerModel->find($batch['cust_id']);
                    $custName = $c['cust_name'] ?? '';
                }
                if (!empty($batch['mapped_cust_id'])) {
                    $m = $this->customerMappingModel->find($batch['mapped_cust_id']);
                    if ($m) {
                        $mappedCustData = [$m];
                        if (empty($custName)) {
                            $c = $this->customerModel->find($m['cust_id']);
                            $custName = $c['cust_name'] ?? '';
                        }
                    }
                }
            }
        }

        // Stage-level authorization enforcement
        if ($accessLevel === 'jr_process_manager') {
            $allowedJrIds = [AccountModel::PACKING_ID, AccountModel::TRANSFER_PACKING_ID, AccountModel::RODIUM_ID];
            if ($processAccountId && !in_array((int)$processAccountId, $allowedJrIds, true)) {
                return '<div class="alert alert-danger p-20">Unauthorized process stage for Jr. Process Manager.</div>';
            }
        } elseif ($accessLevel === 'g58') {
            $allowedG58Ids = [AccountModel::G58_POLISH_ID, AccountModel::G58_FILING_ID, AccountModel::G58_SETTING_ID, AccountModel::G58_TRANSFER_ID];
            if ($processAccountId && !in_array((int)$processAccountId, $allowedG58Ids, true)) {
                return '<div class="alert alert-danger p-20">Unauthorized process stage for G58.</div>';
            }
        }

        $fromAccount = $this->accountModel->getAccountById((int)$processAccountId);
        if (!$fromAccount) {
            return '<div class="alert alert-danger p-20">Source account not found.</div>';
        }

        // Destination accounts based on map_with_accounts_id
        $destinationAccounts = $this->accountModel->getMappedDestinationAccounts($fromAccount['map_with_accounts_id'] ?? '');

        // Check available fine/gross balance for this source account
        $startDate = $this->ledgerService->getActiveStockPointDate();
        $availableBalance = $this->ledgerService->getAccountNetBalance((int)$processAccountId, null, 'fine', $startDate);

        // Fetch all active mapped customers for dropdown
        $mappedCustList = $this->customerMappingModel
            ->where('mapped_cust_status', '1')
            ->orderBy('mapped_cust_name', 'ASC')
            ->findAll();
        $selectedMappedCustId = !empty($batch['mapped_cust_id']) ? (int) $batch['mapped_cust_id'] : null;

        $isLocker = ((int)$processAccountId === AccountModel::STOCK_ID || (int)($fromAccount['account_id'] ?? 0) === AccountModel::STOCK_ID);

        $idempotencyToken = bin2hex(random_bytes(16));

        $data = [
            'idempotencyToken'     => $idempotencyToken,
            'isLocker'             => $isLocker,
            'accessLevel'          => $accessLevel,
            'fromAccount'          => $fromAccount,
            'processAccountId'     => (int) $processAccountId,
            'processWavesId'       => $processWavesId,
            'batch'                => $batch,
            'custName'             => $custName,
            'mappedCustData'       => $mappedCustData,
            'mappedCustList'       => $mappedCustList,
            'selectedMappedCustId' => $selectedMappedCustId,
            'destinationAccounts'  => $destinationAccounts,
            'availableBalance'     => $availableBalance,
            'isStockCheck'         => $isStockCheck,
            'rowPrivateKey'        => $rowPrivateKey,
            'products'             => ['MT', 'OR', 'RK'],
            'activePurities'       => $this->purityModel->getActivePurities(),
        ];

        return view('process_waves/transfer_modal', $data);
    }

    /**
     * Insert Process Transfer (AJAX POST).
     * Atomic transaction updating source batch and inserting destination batch.
     * Reproduces exact legacy Process_waves::insert_process_waves().
     */
    public function insertTransfer(): ResponseInterface
    {
        $session = session();
        $spider  = $session->get('spider');
        $accessLevel = $spider['access_level'] ?? '';
        $userId  = (int) ($spider['user_id'] ?? 1);

        if (!in_array($accessLevel, ['admin', 'superadmin', 'process_manager', 'jr_process_manager', 'g58', 'locker'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Unauthorized access.']);
        }

        $fromId            = (int) $this->request->getPost('from_id');

        // Stage-level authorization enforcement
        if ($accessLevel === 'jr_process_manager') {
            $allowedJrIds = [AccountModel::PACKING_ID, AccountModel::TRANSFER_PACKING_ID, AccountModel::RODIUM_ID];
            if (!in_array($fromId, $allowedJrIds, true)) {
                return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Unauthorized process stage for Jr. Process Manager.']);
            }
        } elseif ($accessLevel === 'g58') {
            $allowedG58Ids = [AccountModel::G58_POLISH_ID, AccountModel::G58_FILING_ID, AccountModel::G58_SETTING_ID, AccountModel::G58_TRANSFER_ID];
            if (!in_array($fromId, $allowedG58Ids, true)) {
                return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Unauthorized process stage for G58.']);
            }
        }

        $toIdRaw           = $this->request->getPost('to_id');
        $grossWeight       = (float) $this->request->getPost('gross_weight');
        $purity            = (float) $this->request->getPost('purity');
        $mappedCustId      = $this->request->getPost('mapped_cust_id') ? (int) $this->request->getPost('mapped_cust_id') : null;
        $pcs               = $this->request->getPost('pcs') ? (int) $this->request->getPost('pcs') : null;
        $remark            = trim((string) $this->request->getPost('remark'));
        $processWavesId    = $this->request->getPost('process_waves_id') ? (int) $this->request->getPost('process_waves_id') : null;
        $processWavesIds   = trim((string) $this->request->getPost('process_waves_ids'));
        $lossFromAccountId = $this->request->getPost('loss_from_account_id');
        $recentGrossWeight = (float) $this->request->getPost('recent_gross_weight');
        $idempotencyToken  = trim((string) ($this->request->getPost('idempotency_token') ?? ''));

        // Customer dispatch check
        if ($toIdRaw === 'cust') {
            return $this->response->setJSON([
                'status'   => 'redirect',
                'redirect' => base_url('raw-waves?from_process=1')
            ]);
        }

        $toId = (int) $toIdRaw;
        if ($fromId <= 0 || $toId <= 0) {
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'Invalid source or destination department.'
            ]);
        }

        // Idempotency Layer: Check if this specific token was already processed successfully
        if (!empty($idempotencyToken)) {
            $cache = \Config\Services::cache();
            $cachedDoneKey = 'pw_tx_done_' . $idempotencyToken;
            if ($cachedId = $cache->get($cachedDoneKey)) {
                return $this->response->setJSON([
                    'status'           => 'true',
                    'process_waves_id' => (int) $cachedId,
                    'message'          => 'Transfer already processed successfully.'
                ]);
            }

            // Check if record already exists in database with this idempotency private_key
            $existingRow = $this->processWavesModel->getByPrivateKey($idempotencyToken);
            if ($existingRow) {
                return $this->response->setJSON([
                    'status'           => 'true',
                    'process_waves_id' => (int) $existingRow['process_waves_id'],
                    'message'          => 'Transfer already processed successfully.'
                ]);
            }
        }

        $db = \Config\Database::connect();
        $isMySQL = ($db->DBDriver === 'MySQLi');

        // Named concurrency mutex lock per source department to prevent concurrent overdrafts (MySQL only)
        $lockKey = "pw_tx_dept_{$fromId}";

        if ($isMySQL) {
            $db->query("SELECT GET_LOCK(?, 10)", [$lockKey]);
        }

        // Database transaction begins
        $db->transBegin();

        try {
            $sourceBatch = null;

            // 1. Single Batch Transfer Validation with Row-Level Locking
            if ($processWavesId) {
                $sql = "SELECT * FROM process_waves WHERE process_waves_id = ?";
                if ($isMySQL) {
                    $sql .= " FOR UPDATE";
                }
                $sourceBatch = $db->query($sql, [$processWavesId])->getRowArray();

                if (!$sourceBatch) {
                    $db->transRollback();
                    if ($isMySQL) { $db->query("SELECT RELEASE_LOCK(?)", [$lockKey]); }
                    return $this->response->setJSON([
                        'status'  => 'false',
                        'message' => 'Source batch record not found.'
                    ]);
                }

                // Strict atomic check: has this batch already been transferred?
                if (!empty($sourceBatch['further_to_process_waves_id']) || (string) $sourceBatch['inner_transfer'] === '1') {
                    $db->transRollback();
                    if ($isMySQL) { $db->query("SELECT RELEASE_LOCK(?)", [$lockKey]); }
                    return $this->response->setJSON([
                        'status'  => 'false',
                        'message' => 'This batch has already been transferred to another department.'
                    ]);
                }

                // Populate missing batch fields from source batch if transferring existing batch (e.g., from Locker)
                if ($grossWeight <= 0) {
                    $grossWeight = (float) ($sourceBatch['gross_weight'] ?? 0);
                }
                if ($purity <= 0) {
                    $purity = (float) ($sourceBatch['purity'] ?? 0);
                }
                if (empty($mappedCustId) && !empty($sourceBatch['mapped_cust_id'])) {
                    $mappedCustId = (int) $sourceBatch['mapped_cust_id'];
                }
                if ($recentGrossWeight <= 0) {
                    $recentGrossWeight = (float) ($sourceBatch['gross_weight'] ?? 0);
                }

                // Validate that transfer gross weight does not exceed source batch gross weight (only for single-batch transfers)
                if (empty($processWavesIds)) {
                    $sourceGrossWeight = (float) ($sourceBatch['gross_weight'] ?? 0);
                    if ($grossWeight > ($sourceGrossWeight + \App\Services\LedgerService::SCALE_TOLERANCE_GRAMS)) {
                        $db->transRollback();
                        if ($isMySQL) { $db->query("SELECT RELEASE_LOCK(?)", [$lockKey]); }
                        return $this->response->setJSON([
                            'status'  => 'false',
                            'message' => 'Transfer gross weight (' . number_format($grossWeight, 3) . 'g) exceeds source batch gross weight (' . number_format($sourceGrossWeight, 3) . 'g).'
                        ]);
                    }
                }
            }

            // Gross weight must be positive
            if ($grossWeight <= 0) {
                $db->transRollback();
                if ($isMySQL) { $db->query("SELECT RELEASE_LOCK(?)", [$lockKey]); }
                return $this->response->setJSON([
                    'status'  => 'false',
                    'message' => 'Gross weight must be greater than 0.'
                ]);
            }

            // 2. Multi-Batch Consolidation Validation with Row-Level Locking
            $idArr = [];
            if (!empty($processWavesIds)) {
                $idArr = array_filter(array_map('intval', explode(',', $processWavesIds)));
                if (!empty($idArr)) {
                    $placeholders = implode(',', array_fill(0, count($idArr), '?'));
                    $multiSql = "SELECT process_waves_id, further_to_process_waves_id, inner_transfer, gross_weight FROM process_waves WHERE process_waves_id IN ($placeholders)";
                    if ($isMySQL) {
                        $multiSql .= " FOR UPDATE";
                    }
                    $multiBatches = $db->query($multiSql, $idArr)->getResultArray();

                    if (count($multiBatches) !== count($idArr)) {
                        $db->transRollback();
                        if ($isMySQL) { $db->query("SELECT RELEASE_LOCK(?)", [$lockKey]); }
                        return $this->response->setJSON([
                            'status'  => 'false',
                            'message' => 'One or more selected batches were not found.'
                        ]);
                    }

                    $totalSourceGw = 0.0;
                    foreach ($multiBatches as $mb) {
                        if (!empty($mb['further_to_process_waves_id']) || (string) $mb['inner_transfer'] === '1') {
                            $db->transRollback();
                            if ($isMySQL) { $db->query("SELECT RELEASE_LOCK(?)", [$lockKey]); }
                            return $this->response->setJSON([
                                'status'  => 'false',
                                'message' => 'One or more selected batches have already been transferred.'
                            ]);
                        }
                        $totalSourceGw += (float) ($mb['gross_weight'] ?? 0);
                    }

                    if ($grossWeight > ($totalSourceGw + \App\Services\LedgerService::SCALE_TOLERANCE_GRAMS)) {
                        $db->transRollback();
                        if ($isMySQL) { $db->query("SELECT RELEASE_LOCK(?)", [$lockKey]); }
                        return $this->response->setJSON([
                            'status'  => 'false',
                            'message' => 'Consolidated gross weight (' . number_format($grossWeight, 3) . 'g) exceeds total source batches gross weight (' . number_format($totalSourceGw, 3) . 'g).'
                        ]);
                    }
                }
            }

            // 3. Universal Department Stock Sufficiency Check for ALL Transfers
            $startDate = $this->ledgerService->getActiveStockPointDate();
            if (!$this->ledgerService->checkWeight($fromId, $toId, $grossWeight, $purity, $startDate)) {
                $db->transRollback();
                if ($isMySQL) { $db->query("SELECT RELEASE_LOCK(?)", [$lockKey]); }
                $currentBal = $this->ledgerService->getAccountNetBalance($fromId, null, 'fine', $startDate);
                $reqFine = round(($grossWeight * $purity) / 100, 3);
                return $this->response->setJSON([
                    'status'  => 'false',
                    'message' => 'Source account gold inventory is insufficient. Available: ' . number_format($currentBal, 3) . 'g, Required: ' . number_format($reqFine, 3) . 'g.'
                ]);
            }

            // 4. Server-Side Piece Count (pcs) Validation
            if ($pcs !== null && $pcs > 0) {
                $pieceTrackingAccounts = [
                    AccountModel::HEERA_LIGHT_ID,
                    AccountModel::SETTING_ID,
                    AccountModel::POLISH_ID,
                    AccountModel::RODIUM_ID,
                    AccountModel::PACKING_ID,
                    AccountModel::TRANSFER_PACKING_ID,
                    AccountModel::G58_SETTING_ID,
                    AccountModel::G58_POLISH_ID,
                ];
                if (in_array($fromId, $pieceTrackingAccounts, true)) {
                    $recvPcs = $this->ledgerService->getAccountTotalPcsReceiveBalance($fromId, $mappedCustId, $startDate);
                    $issPcs  = $this->ledgerService->getAccountTotalPcsIssueBalance($fromId, $mappedCustId, $startDate);
                    $availPcs = $recvPcs - $issPcs;

                    if ($availPcs < $pcs) {
                        $db->transRollback();
                        if ($isMySQL) { $db->query("SELECT RELEASE_LOCK(?)", [$lockKey]); }
                        return $this->response->setJSON([
                            'status'  => 'false',
                            'message' => "Insufficient piece count in source stage. Available: {$availPcs} pcs, Tried: {$pcs} pcs."
                        ]);
                    }
                }
            }

            // 5. Short-window deduplication guard against rapid identical balance transfers (within 5 seconds)
            if (!$processWavesId) {
                $recentDup = $db->query("
                    SELECT process_waves_id 
                    FROM process_waves 
                    WHERE from_id = ? AND to_id = ? AND gross_weight = ? AND purity = ? AND user_by = ? 
                      AND date >= ? 
                    ORDER BY process_waves_id DESC LIMIT 1
                ", [
                    $fromId, 
                    $toId, 
                    $grossWeight, 
                    $purity, 
                    $userId, 
                    date('Y-m-d H:i:s', time() - 5)
                ])->getRowArray();

                if ($recentDup) {
                    $db->transRollback();
                    if ($isMySQL) { $db->query("SELECT RELEASE_LOCK(?)", [$lockKey]); }
                    return $this->response->setJSON([
                        'status'           => 'true',
                        'process_waves_id' => (int) $recentDup['process_waves_id'],
                        'message'          => 'Transfer already processed successfully.'
                    ]);
                }
            }

            // Loss calculation
            $lossWeight = ($recentGrossWeight > 0) ? ($recentGrossWeight - $grossWeight) : 0.0;
            $netLossWeight = null;
            if ($fromId === AccountModel::REFINING_ID) {
                $netLossWeight = round(($lossWeight * $purity) / 100, 3);
            }

            // Inner transfer flags
            $innerTransfer = ($toId === AccountModel::STOCK_ID || $toId === AccountModel::REFINING_ID) ? '0' : '1';

            // Resolve customer ID from mapping
            $custId = null;
            if ($mappedCustId) {
                $mapping = $this->customerMappingModel->find($mappedCustId);
                if ($mapping && !empty($mapping['cust_id'])) {
                    $custId = (int) $mapping['cust_id'];
                }
            }

            // Use the client's idempotency token as private_key, or generate GUID if not provided
            $newPrivateKey = (!empty($idempotencyToken) && strlen($idempotencyToken) <= 50)
                ? $idempotencyToken 
                : $this->ledgerService->generateGuid();

            // Destination record payload
            $insertData = [
                'private_key'                 => $newPrivateKey,
                'from_id'                     => $fromId,
                'to_id'                       => $toId,
                'mapped_cust_id'              => $mappedCustId,
                'cust_id'                     => $custId,
                'product'                     => 'MT',
                'gross_weight'                => $grossWeight,
                'purity'                      => $purity,
                'net_loss'                    => $netLossWeight,
                'loss_account_id'             => $lossFromAccountId ?: null,
                'pcs'                         => $pcs,
                'inner_transfer'              => $innerTransfer,
                'stock_inner_transfer'        => $innerTransfer,
                'further_to_process_waves_id' => null,
                'locker_stock_check_done'     => '0',
                'user_by'                     => $userId,
                'date'                        => date('Y-m-d H:i:s'),
                'remark'                      => $remark ?: null,
            ];

            $newProcessWavesId = $this->processWavesModel->insertProcessWavesData($insertData);

            // Update source batch so it cannot be double-transferred
            if ($processWavesId) {
                $this->processWavesModel->updateProcessWavesData([
                    'inner_transfer'              => '1',
                    'further_to_process_waves_id' => $newProcessWavesId
                ], ['process_waves_id' => $processWavesId]);
            }

            // Handle multi-batch consolidation if multiple IDs passed
            if (!empty($idArr)) {
                $this->processWavesModel->updateProcessWavesData([
                    'further_to_process_waves_id' => $newProcessWavesId
                ], ['process_waves_id IN' => $idArr]);
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                if ($isMySQL) { $db->query("SELECT RELEASE_LOCK(?)", [$lockKey]); }
                return $this->response->setJSON(['status' => 'false', 'message' => 'Transaction failed. Rolled back.']);
            }

            $db->transCommit();
            if ($isMySQL) { $db->query("SELECT RELEASE_LOCK(?)", [$lockKey]); }

            // Store in cache for idempotency
            if (!empty($idempotencyToken)) {
                $cache = \Config\Services::cache();
                $cache->save('pw_tx_done_' . $idempotencyToken, $newProcessWavesId, 300);
            }

            return $this->response->setJSON([
                'status'           => 'true',
                'process_waves_id' => $newProcessWavesId
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            if ($isMySQL) { $db->query("SELECT RELEASE_LOCK(?)", [$lockKey]); }
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX Endpoint: Check Weight inventory sufficiency.
     * Compatible with legacy Process_waves::check_weight().
     */
    public function checkWeight(): ResponseInterface
    {
        $fromId      = (int) $this->request->getPost('from_id');
        $toId        = (int) $this->request->getPost('to_id');
        $grossWeight = (float) $this->request->getPost('gross_weight');
        $purity      = (float) ($this->request->getPost('purity') ?: 100.0);

        $startDate = $this->ledgerService->getActiveStockPointDate();
        $valid = $this->ledgerService->checkWeight($fromId, $toId, $grossWeight, $purity, $startDate);

        return $this->response->setBody($valid ? 'true' : 'false');
    }

    /**
     * AJAX Endpoint: Check stage loss percentage.
     * Compatible with legacy Process_waves::check_loss_validation().
     */
    public function checkLossValidation(): ResponseInterface
    {
        $fromId   = (int) $this->request->getPost('from_id');
        $fromName = (string) $this->request->getPost('from_name');

        $startDate = $this->ledgerService->getActiveStockPointDate();
        $result = $this->ledgerService->checkLossValidation($fromId, $fromName, $startDate);

        return $this->response->setJSON($result);
    }

    /**
     * AJAX Endpoint: Check piece count balance.
     * Compatible with legacy Process_waves::check_number_of_pcs().
     */
    public function checkNumberOfPcs(): ResponseInterface
    {
        $fromId       = (int) $this->request->getPost('from_id');
        $mappedCustId = (int) $this->request->getPost('mapped_cust_id');
        $pcs          = (int) $this->request->getPost('pcs');

        if (!$mappedCustId) {
            return $this->response->setBody('mapped_cust_error');
        }

        if ($pcs <= 0 || !$fromId) {
            return $this->response->setBody('input_error');
        }

        $pieceTrackingAccounts = [
            AccountModel::HEERA_LIGHT_ID,
            AccountModel::POLISH_ID,
            AccountModel::RODIUM_ID,
            AccountModel::SETTING_ID,
            AccountModel::PACKING_ID,
            AccountModel::STOCK_ID,
        ];

        if (in_array($fromId, $pieceTrackingAccounts, true)) {
            $startDate = $this->ledgerService->getActiveStockPointDate();
            $recvPcs = $this->ledgerService->getAccountTotalPcsReceiveBalance($fromId, $mappedCustId, $startDate);
            $issPcs  = $this->ledgerService->getAccountTotalPcsIssueBalance($fromId, $mappedCustId, $startDate);
            $bal = $recvPcs - ($issPcs + $pcs);

            return $this->response->setBody($bal < 0 ? 'false' : 'true');
        }

        return $this->response->setBody('true');
    }

    /**
     * Insert Stock Checkpoint (AJAX POST).
     * Compatible with legacy Process_waves::insert_stock_check_point().
     */
    public function insertStockCheckPoint(): ResponseInterface
    {
        $session = session()->get('spider') ?? [];
        $accessLevel = $session['access_level'] ?? '';
        if (!in_array($accessLevel, ['admin', 'superadmin', 'process_manager', 'locker'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'false', 'message' => 'Unauthorized access.']);
        }

        $dateTime = $this->request->getPost('end_date_time') ?: date('Y-m-d H:i:s');

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // 1. Set stock_inner_transfer = '1' for all pending items
            $this->processWavesModel->updateProcessWavesData(
                ['stock_inner_transfer' => '1'],
                ['stock_inner_transfer' => '0']
            );

            // 2. Exact CI2 parity: preserve pending items in Locker (to_id = 1 and further_to_process_waves_id IS NULL)
            $pendingLockerWaves = $this->processWavesModel
                ->where('to_id', AccountModel::STOCK_ID)
                ->where('further_to_process_waves_id IS NULL')
                ->findAll();

            if (!empty($pendingLockerWaves)) {
                foreach ($pendingLockerWaves as $obj) {
                    $this->processWavesModel->updateProcessWavesData(
                        ['stock_inner_transfer' => (string) $obj['inner_transfer']],
                        ['process_waves_id' => $obj['process_waves_id']]
                    );
                }
            }

            // 3. Insert into stock_point
            $stockPointId = $this->stockPointModel->insertStockPoint($dateTime);

            // 4. Insert closing balance snapshot for Locker
            $this->stockPointModel->insertClosingBalance($stockPointId, AccountModel::STOCK_ID, 0.0);

            // 4. Update session start date time
            $session = session();
            $spider = $session->get('spider');
            $spider['stock_point_start_date_time'] = $dateTime;
            $session->set('spider', $spider);

            $db->transCommit();
            return $this->response->setJSON(['status' => 'true']);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->response->setJSON(['status' => 'false', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Printable Process Waves Transaction Slip.
     * Reproduces exact legacy Process_waves::print_process_waves_transaction().
     */
    public function printTransaction(int $processWavesId)
    {
        $session = session()->get('spider') ?? [];
        $accessLevel = $session['access_level'] ?? '';
        if (!in_array($accessLevel, ['admin', 'superadmin', 'process_manager', 'jr_process_manager', 'g58', 'locker'], true)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access.');
        }

        $tx = $this->processWavesModel->getTransactionPrintData($processWavesId);
        if (!$tx) {
            return 'Transaction not found.';
        }

        $fromId = (int) ($tx['from_id'] ?? 0);
        $toId   = (int) ($tx['to_id'] ?? 0);

        if ($accessLevel === 'jr_process_manager') {
            $allowedJrIds = [AccountModel::PACKING_ID, AccountModel::TRANSFER_PACKING_ID, AccountModel::RODIUM_ID];
            if (!in_array($fromId, $allowedJrIds, true) && !in_array($toId, $allowedJrIds, true)) {
                return 'Unauthorized transaction slip for Jr. Process Manager.';
            }
        } elseif ($accessLevel === 'g58') {
            $allowedG58Ids = [AccountModel::G58_POLISH_ID, AccountModel::G58_FILING_ID, AccountModel::G58_SETTING_ID, AccountModel::G58_TRANSFER_ID];
            if (!in_array($fromId, $allowedG58Ids, true) && !in_array($toId, $allowedG58Ids, true)) {
                return 'Unauthorized transaction slip for G58.';
            }
        } elseif ($accessLevel === 'locker') {
            if ($fromId !== AccountModel::STOCK_ID && $toId !== AccountModel::STOCK_ID) {
                return 'Unauthorized transaction slip for Locker.';
            }
        }

        $customerName = $tx['mapped_cust_name'] ?: ($tx['cust_name'] ?: 'N/A');
        $fromAccount  = $tx['from_name'] ?: 'Locker';
        $toAccount    = $tx['to_name'] ?: 'Customer';

        $data = [
            'tx'           => $tx,
            'customerName' => $customerName,
            'fromAccount'  => $fromAccount,
            'toAccount'    => $toAccount,
            'detail'       => $fromAccount . ' to ' . $toAccount,
            'grossWeight'  => number_format((float)$tx['gross_weight'], 3, '.', ''),
            'purity'       => number_format((float)$tx['purity'], 2, '.', ''),
            'dateTime'     => $tx['date'],
            'isLocker'     => ($toAccount === 'Locker'),
        ];

        return view('process_waves/print_transaction', $data);
    }

    /**
     * Printable Full Department Ledger Sheet.
     * Reproduces legacy Process_waves::print_process_waves().
     */
    public function printSheet(string $processAccountPrivateKey = '', string $fromAccountPrivateKey = '')
    {
        $session = session()->get('spider') ?? [];
        $accessLevel = $session['access_level'] ?? '';
        if (!in_array($accessLevel, ['admin', 'superadmin', 'process_manager', 'jr_process_manager', 'g58'], true)) {
            if ($accessLevel === 'locker') {
                return redirect()->to(base_url('locker'))->with('error', 'Locker custodian cannot access general workshop stage prints.');
            }
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access.');
        }

        $startDate = $this->ledgerService->getActiveStockPointDate();
        $account = $this->accountModel->getAccountByPrivateKey($processAccountPrivateKey);
        if (!$account) {
            if ($accessLevel === 'jr_process_manager') {
                $account = $this->accountModel->getAccountById(AccountModel::PACKING_ID);
            } elseif ($accessLevel === 'g58') {
                $account = $this->accountModel->getAccountById(AccountModel::G58_POLISH_ID);
            } else {
                $account = $this->accountModel->getAccountById(AccountModel::CASTING_ID);
            }
        }

        if ($account && (int)$account['account_id'] === AccountModel::LOSS_ID) {
            $lossController = new \App\Controllers\LossController();
            return $lossController->print($fromAccountPrivateKey ?: $processAccountPrivateKey);
        }

        $accountId = (int) $account['account_id'];

        if ($accessLevel === 'jr_process_manager') {
            $allowedJrIds = [AccountModel::PACKING_ID, AccountModel::TRANSFER_PACKING_ID, AccountModel::RODIUM_ID];
            if (!in_array($accountId, $allowedJrIds, true)) {
                return redirect()->to(base_url('process-waves/manage/' . AccountModel::PACKING_PRIVATE_KEY))
                    ->with('error', 'Unauthorized process stage for Jr. Process Manager.');
            }
        } elseif ($accessLevel === 'g58') {
            $allowedG58Ids = [AccountModel::G58_POLISH_ID, AccountModel::G58_FILING_ID, AccountModel::G58_SETTING_ID, AccountModel::G58_TRANSFER_ID];
            if (!in_array($accountId, $allowedG58Ids, true)) {
                return redirect()->to(base_url('process-waves/manage/' . AccountModel::G58_POLISH_PRIVATE_KEY))
                    ->with('error', 'Unauthorized process stage for G58.');
            }
        }
        $receiptData = $this->processWavesModel->getDepartmentReceiptData($accountId, $startDate);
        $issueData   = $this->processWavesModel->getDepartmentIssueData($accountId, $startDate);

        $openingBalance = $this->ledgerService->getOpeningBalance($accountId);
        $receiveTotal   = $this->ledgerService->getAccountTotalReceiveBalance($accountId, null, 'fine', $startDate);
        $issueTotal     = $this->ledgerService->getAccountTotalIssueBalance($accountId, null, 'fine', $startDate);
        $fineBalance    = round(($receiveTotal + $openingBalance) - $issueTotal, 3);

        $data = [
            'account'             => $account,
            'receiptData'         => $receiptData,
            'issueData'           => $issueData,
            'openingBalance'      => $openingBalance,
            'receiveTotalBalance' => $receiveTotal + $openingBalance,
            'issueTotalBalance'   => $issueTotal,
            'fineBalance'         => $fineBalance,
            'startDate'           => $startDate,
        ];

        return view('process_waves/print_sheet', $data);
    }

    /**
     * Pending raw waves count for breadcrumb badge.
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
