<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProcessWavesModel;
use App\Models\AccountModel;
use App\Models\StockPointModel;
use App\Models\PurityModel;
use App\Services\LedgerService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * RefiningController
 * 
 * MODULE: REFINING DEPARTMENT
 * Handles chemical refining of accumulated workshop loss metal into pure metal.
 * 
 * Functional Flow:
 * Workshop Loss Departments -> Multi-Batch Consolidation -> Loss Department (12)
 * -> Refining Department (9) [Receipt: inner_transfer = '0']
 * -> Refining Processing & Assay
 * -> Issue to Locker (1) [Outward: gross_weight, purity, net_loss calculated]
 * -> Source batch marked inner_transfer = '1' (prevents duplicate transfer)
 */
class RefiningController extends BaseController
{
    protected ProcessWavesModel $processWavesModel;
    protected AccountModel $accountModel;
    protected StockPointModel $stockPointModel;
    protected PurityModel $purityModel;
    protected LedgerService $ledgerService;

    public function __construct()
    {
        $this->processWavesModel = model(ProcessWavesModel::class);
        $this->accountModel      = model(AccountModel::class);
        $this->stockPointModel   = model(StockPointModel::class);
        $this->purityModel       = model(PurityModel::class);
        $this->ledgerService     = new LedgerService();
    }

    /**
     * Main Refining Ledger Dashboard.
     * Displays all incoming batches from Loss consolidation and refined issues to Locker,
     * along with full SQL aggregated balances.
     */
    public function index()
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';

        $allowedRoles = ['admin', 'superadmin', 'process_manager'];
        if (!in_array($accessLevel, $allowedRoles, true)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access to Refining module.');
        }

        // Account 9 is Refining
        $refiningAccount = $this->accountModel->getAccountById(AccountModel::REFINING_ID);
        if (!$refiningAccount) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Refining department account not found.');
        }

        $startDate = $this->ledgerService->getActiveStockPointDate();

        // 1. Full SQL aggregation for balances (never paginated)
        $openingFine  = $this->ledgerService->getOpeningBalance(AccountModel::REFINING_ID);
        $openingGross = 0.000;

        $receiveTotalFine = $this->ledgerService->getAccountTotalReceiveBalance(AccountModel::REFINING_ID, null, 'fine') + $openingFine;
        $issueTotalFine   = $this->ledgerService->getAccountTotalIssueBalance(AccountModel::REFINING_ID, null, 'fine');
        $fineBalance      = round($receiveTotalFine - $issueTotalFine, 3);

        $receiveTotalGross = $this->ledgerService->getAccountTotalReceiveBalance(AccountModel::REFINING_ID, null, 'gross') + $openingGross;
        $issueTotalGross   = $this->ledgerService->getAccountTotalIssueBalance(AccountModel::REFINING_ID, null, 'gross');
        $grossBalance      = round($receiveTotalGross - $issueTotalGross, 3);

        // 2. Fetch Receipt & Issue datasets for Refining
        $receiptData = $this->processWavesModel->getDepartmentReceiptData(AccountModel::REFINING_ID, '', null, null, 0, false);
        $issueData   = $this->processWavesModel->getDepartmentIssueData(AccountModel::REFINING_ID, '', null, null, 0);

        // Calculate total loss on issues
        $totalNetLoss = 0.0;
        foreach ($issueData as $iss) {
            if (!empty($iss['net_loss'])) {
                $totalNetLoss += (float)$iss['net_loss'];
            }
        }

        $data = [
            'title'               => 'Refining',
            'account'             => $refiningAccount,
            'accountId'           => AccountModel::REFINING_ID,
            'accountName'         => $refiningAccount['account_name'] ?? 'Refining',
            'accountPrivateKey'   => $refiningAccount['private_key'] ?? AccountModel::REFINING_PRIVATE_KEY,
            'startDate'           => $startDate,
            'receiptData'         => $receiptData,
            'issueData'           => $issueData,
            'openingFine'         => $openingFine,
            'openingGross'        => $openingGross,
            'receiveTotalFine'    => $receiveTotalFine,
            'issueTotalFine'      => $issueTotalFine,
            'fineBalance'         => $fineBalance,
            'receiveTotalGross'   => $receiveTotalGross,
            'issueTotalGross'     => $issueTotalGross,
            'grossBalance'        => $grossBalance,
            'totalNetLoss'        => round($totalNetLoss, 3),
        ];

        return view('refining/index', $data);
    }

    /**
     * Load Transfer Modal for Refining -> Locker transfer (AJAX GET).
     * 
     * @param int $processWavesId ID of incoming batch in Refining (to_id = 9)
     */
    public function transferModal(int $processWavesId)
    {
        $spider = session()->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';
        if (!in_array($accessLevel, ['admin', 'superadmin', 'process_manager'], true)) {
            return '<div class="alert alert-danger" style="margin: 20px;">Unauthorized access to Refining module.</div>';
        }

        $batch = $this->processWavesModel->find($processWavesId);
        if (!$batch) {
            return '<div class="alert alert-danger" style="margin: 20px;">Refining batch record not found.</div>';
        }

        if ((int)$batch['to_id'] !== AccountModel::REFINING_ID) {
            return '<div class="alert alert-danger" style="margin: 20px;">Invalid batch. Selected record does not belong to Refining.</div>';
        }

        if ((string)$batch['inner_transfer'] === '1') {
            return '<div class="alert alert-warning" style="margin: 20px;">
                <i class="icon-info22"></i> <strong>Already Transferred:</strong> This refining batch has already been transferred to Locker.
            </div>';
        }

        // Get Source Department Name (from loss_account_id or from_id)
        $sourceDeptName = 'Loss Consolidation';
        if (!empty($batch['loss_account_id'])) {
            $deptAcc = $this->accountModel->getAccountById((int)$batch['loss_account_id']);
            if ($deptAcc) {
                $sourceDeptName = $deptAcc['account_name'];
            }
        } elseif (!empty($batch['from_id'])) {
            $deptAcc = $this->accountModel->getAccountById((int)$batch['from_id']);
            if ($deptAcc) {
                $sourceDeptName = $deptAcc['account_name'];
            }
        }

        // Destination is always Locker (Account 1)
        $lockerAccount = $this->accountModel->getAccountById(AccountModel::STOCK_ID);

        $data = [
            'batch'          => $batch,
            'processWavesId' => $processWavesId,
            'sourceDeptName' => $sourceDeptName,
            'incomingGross'  => number_format((float)$batch['gross_weight'], 3, '.', ''),
            'incomingPurity' => number_format((float)$batch['purity'], 2, '.', ''),
            'lockerAccount'  => $lockerAccount,
            'lockerId'       => AccountModel::STOCK_ID,
            'refiningId'     => AccountModel::REFINING_ID,
            'activePurities' => $this->purityModel->getActivePurities(),
        ];

        return view('refining/transfer_modal', $data);
    }

    /**
     * Insert Transfer from Refining to Locker (AJAX POST).
     * Calculates net_loss, creates Locker receipt row, and updates source batch inner_transfer = '1'.
     */
    public function insertTransfer(): ResponseInterface
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';
        if (!in_array($accessLevel, ['admin', 'superadmin', 'process_manager'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'false',
                'message' => 'Unauthorized access to Refining transfer.'
            ]);
        }

        $userId  = (int)($spider['user_id'] ?? 1);

        $processWavesId = (int)$this->request->getPost('process_waves_id');
        $grossWeight    = (float)$this->request->getPost('gross_weight');
        $purity         = (float)$this->request->getPost('purity');
        $remark         = trim((string)$this->request->getPost('remark'));

        // 1. Validate batch existence
        $sourceBatch = $this->processWavesModel->find($processWavesId);
        if (!$sourceBatch) {
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'Source refining batch record not found.'
            ]);
        }

        // 2. Validate department ownership
        if ((int)$sourceBatch['to_id'] !== AccountModel::REFINING_ID) {
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'Batch does not belong to Refining department.'
            ]);
        }

        // 3. Prevent duplicate transfer
        if ((string)$sourceBatch['inner_transfer'] === '1') {
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'This refining batch has already been transferred to Locker.'
            ]);
        }

        // 4. Validate Gross Weight
        $incomingGross = (float)$sourceBatch['gross_weight'];
        if ($grossWeight <= 0) {
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'Gross weight must be greater than 0.'
            ]);
        }

        if ($grossWeight > $incomingGross) {
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'Gross Weight Inventory insufficient. Output weight cannot exceed incoming batch weight (' . number_format($incomingGross, 3, '.', '') . ' g).'
            ]);
        }

        // 5. Validate Purity
        if ($purity <= 0 || $purity > 100) {
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'Purity must be between 0.01% and 100.00%.'
            ]);
        }

        // 6. Calculate Net Loss
        // Legacy CI2 formula (process_waves.php line 150-152):
        // loss_weight = recent_gross_weight - gross_weight
        // net_loss_weight = ((loss_weight * purity) / 100)
        $lossWeight = round($incomingGross - $grossWeight, 3);
        $netLoss    = round(($lossWeight * $purity) / 100, 3);

        // 7. Atomic Database Transaction
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $guid = bin2hex(random_bytes(16));

            $insertData = [
                'private_key'                 => $guid,
                'from_id'                     => AccountModel::REFINING_ID,
                'to_id'                       => AccountModel::STOCK_ID, // Locker
                'mapped_cust_id'              => null,
                'cust_id'                     => null,
                'product'                     => 'MT',
                'gross_weight'                => $grossWeight,
                'purity'                      => $purity,
                'net_loss'                    => $netLoss,
                'loss_account_id'             => $sourceBatch['loss_account_id'] ?? null,
                'user_by'                     => $userId,
                'inner_transfer'              => '0',
                'stock_inner_transfer'        => '0',
                'further_to_process_waves_id' => null,
                'remark'                      => !empty($remark) ? $remark : null,
                'date'                        => date('Y-m-d H:i:s'),
            ];

            $this->processWavesModel->insert($insertData);
            $newId = $this->processWavesModel->getInsertID();

            // Lock source batch from being re-transferred
            $this->processWavesModel->update($processWavesId, [
                'inner_transfer' => '1',
            ]);

            $db->transCommit();

            return $this->response->setJSON([
                'status'           => 'true',
                'message'          => 'Refined gold successfully transferred to Locker.',
                'process_waves_id' => $newId,
                'loss_weight'      => number_format($lossWeight, 3, '.', ''),
                'net_loss'         => number_format($netLoss, 3, '.', ''),
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'Database error during refining transfer: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Check Weight on-the-fly validation (AJAX POST).
     * Validates available batch inventory before submitting transfer.
     */
    public function checkWeight(): ResponseInterface
    {
        $spider = session()->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';
        if (!in_array($accessLevel, ['admin', 'superadmin', 'process_manager'], true)) {
            return $this->response->setBody('false');
        }

        $processWavesId = (int)$this->request->getPost('process_waves_id');
        $grossWeight    = (float)$this->request->getPost('gross_weight');

        if ($processWavesId > 0) {
            $batch = $this->processWavesModel->find($processWavesId);
            if ($batch && $grossWeight > 0 && $grossWeight <= (float)$batch['gross_weight']) {
                return $this->response->setBody('true');
            }
            return $this->response->setBody('false');
        }

        // Fallback to overall balance check
        $recBal = $this->ledgerService->getAccountTotalReceiveBalance(AccountModel::REFINING_ID, null, 'fine');
        $issBal = $this->ledgerService->getAccountTotalIssueBalance(AccountModel::REFINING_ID, null, 'fine');
        $balance = $recBal - $issBal;

        $purity = (float)($this->request->getPost('purity') ?: 100);
        $reqFine = ($grossWeight * $purity) / 100;

        return $this->response->setBody(($balance >= $reqFine && $grossWeight > 0) ? 'true' : 'false');
    }

    /**
     * Printable Refining Transaction Slip.
     * Routes directly to ProcessWaves printable transaction format.
     */
    public function printTransaction(int $processWavesId)
    {
        $spider = session()->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';
        if (!in_array($accessLevel, ['admin', 'superadmin', 'process_manager'], true)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access to print transaction.');
        }

        $processWavesController = new ProcessWavesController();
        return $processWavesController->printTransaction($processWavesId);
    }
}
