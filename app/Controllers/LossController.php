<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ProcessWavesModel;
use App\Models\AccountModel;
use App\Models\PurityModel;
use App\Services\LedgerService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * LossController
 * 
 * MODULE 4: LOSS RECOVERY & CONSOLIDATION
 * 
 * Functional Responsibilities:
 * - Department Loss Hub: Visual dashboard overview of all workshop loss accounts
 * - Department Loss Ledger: Pending loss receipt batches and historical issue transfers
 * - Multi-Batch Selection: Checkbox selection of cumulative pending loss batches
 * - Consolidation Transfer: Consolidating selected loss batches and transferring to Refining
 * - Audit Trail: Preserves source batch lineage via further_to_process_waves_id
 */
class LossController extends BaseController
{
    protected ProcessWavesModel $processWavesModel;
    protected AccountModel $accountModel;
    protected PurityModel $purityModel;
    protected LedgerService $ledgerService;

    public function __construct()
    {
        $this->processWavesModel = model(ProcessWavesModel::class);
        $this->accountModel      = model(AccountModel::class);
        $this->purityModel       = model(PurityModel::class);
        $this->ledgerService     = new LedgerService();
    }

    /**
     * Loss Module Overview Hub.
     * Displays all workshop departments with pending loss counts, gross weights, and fine MT totals.
     */
    public function index()
    {
        $session     = session();
        $spider      = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';

        if (!in_array($accessLevel, ['admin', 'superadmin', 'process_manager', 'g58'], true)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access to Loss module.');
        }

        $lossAccounts = $this->accountModel->getLossNavigationAccounts($accessLevel);
        $deptIds      = array_column($lossAccounts, 'account_id');
        $summaryMap   = $this->processWavesModel->getLossDepartmentSummary($deptIds);

        $totalPendingCount     = 0;
        $totalPendingGross     = 0.0;
        $totalPendingFine      = 0.0;
        $totalTransferredGross = 0.0;

        $cards = [];
        foreach ($lossAccounts as $acc) {
            $id = (int)$acc['account_id'];
            $stat = $summaryMap[$id] ?? [
                'pending_count'     => 0,
                'pending_gross'     => 0.0,
                'pending_fine'      => 0.0,
                'transferred_count' => 0,
                'transferred_gross' => 0.0,
                'transferred_fine'  => 0.0,
            ];

            $pCount = (int)$stat['pending_count'];
            $pGross = abs((float)$stat['pending_gross']);
            $pFine  = abs((float)$stat['pending_fine']);
            $tGross = abs((float)$stat['transferred_gross']);

            $totalPendingCount     += $pCount;
            $totalPendingGross     += $pGross;
            $totalPendingFine      += $pFine;
            $totalTransferredGross += $tGross;

            $cards[] = [
                'account_id'        => $id,
                'account_name'      => $acc['account_name'],
                'loss_title'        => $acc['loss_title'] ?? ($acc['account_name'] . ' Loss'),
                'private_key'       => $acc['private_key'],
                'loss_percentage'   => (float)$acc['loss_percentage'],
                'pending_count'     => $pCount,
                'pending_gross'     => $pGross,
                'pending_fine'      => $pFine,
                'transferred_count' => (int)$stat['transferred_count'],
                'transferred_gross' => $tGross,
            ];
        }

        $data = [
            'title'                 => 'Loss Management Hub',
            'cards'                 => $cards,
            'totalPendingCount'     => $totalPendingCount,
            'totalPendingGross'     => $totalPendingGross,
            'totalPendingFine'      => $totalPendingFine,
            'totalTransferredGross' => $totalTransferredGross,
            'pendingCount'          => $this->getPendingRawWavesCount(),
        ];

        return view('loss/index', $data);
    }

    /**
     * Department Loss Ledger Screen.
     * Dual-panel table showing pending batches for receipt (with multi-select) and historical issues to Refining.
     *
     * @param string $departmentPrivateKey Department private key or empty for General Loss
     * @param string $secondaryKey Optional second segment for legacy route compatibility (e.g. loss/manage/{loss_key}/{dept_key})
     */
    public function manage(string $departmentPrivateKey = '', string $secondaryKey = '')
    {
        if (!empty($secondaryKey)) {
            $departmentPrivateKey = $secondaryKey;
        }

        $session     = session();
        $spider      = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';

        if (!in_array($accessLevel, ['admin', 'superadmin', 'process_manager', 'g58'], true)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access to Loss module.');
        }

        $isGeneral = false;
        $deptAccount = null;
        $sourceDeptId = null;

        if ($departmentPrivateKey === '' || $departmentPrivateKey === AccountModel::LOSS_PRIVATE_KEY) {
            $isGeneral     = true;
            $accountName   = 'General Loss';
            $lossTitle     = 'General Loss';
            $departmentKey = AccountModel::LOSS_PRIVATE_KEY;
        } else {
            $deptAccount = $this->accountModel->getAccountByPrivateKey($departmentPrivateKey);
            if (!$deptAccount) {
                return redirect()->to(base_url('dashboard'))->with('error', 'Department account not found.');
            }
            $sourceDeptId  = (int)$deptAccount['account_id'];
            $accountName   = $deptAccount['account_name'];
            $departmentKey = $deptAccount['private_key'];

            $sequence = [
                AccountModel::REFINING_ID         => 'Refining Loss',
                AccountModel::CASTING_ID          => 'Casting Loss',
                AccountModel::FILING_ID           => 'Filing Loss',
                AccountModel::HEERA_LIGHT_ID      => 'Heera Light Loss',
                AccountModel::PACKING_ID          => 'Packing Loss',
                AccountModel::POLISH_ID           => 'Polish Loss',
                AccountModel::RODIUM_ID           => 'Rodium Loss',
                AccountModel::SETTING_ID          => 'Setting Loss',
                AccountModel::G58_POLISH_ID       => 'G58-Polish',
                AccountModel::G58_FILING_ID       => 'G58-Filing',
                AccountModel::G58_SETTING_ID      => 'G58-Setting',
                AccountModel::GALAI_ID            => 'Galai',
                AccountModel::TASH_ID             => 'Tash',
                AccountModel::SAPPHIRE_ID         => 'Sapphire',
                AccountModel::CNC_ID              => 'Megnet',
            ];

            if (isset($sequence[$sourceDeptId])) {
                $lossTitle = $sequence[$sourceDeptId];
            } else {
                $formattedName = str_replace('_', ' ', $accountName);
                $lossTitle = !str_ends_with(strtolower($formattedName), 'loss')
                    ? $formattedName . ' Loss'
                    : $formattedName;
            }
        }

        if ($accessLevel === 'g58') {
            $allowedG58Loss = [AccountModel::G58_POLISH_ID, AccountModel::G58_FILING_ID, AccountModel::G58_SETTING_ID];
            if ($isGeneral || !in_array($sourceDeptId, $allowedG58Loss, true)) {
                return redirect()->to(base_url('loss'))->with('error', 'Unauthorized loss department for G58 user.');
            }
        }

        $filter       = $this->request->getGet('filter') ?? 'pending';
        $onlyPending  = ($filter !== 'all');
        $isShowingAll = !$onlyPending;

        // Fetch Receipt dataset (batches into Loss)
        $receiptData = $this->processWavesModel->getDepartmentReceiptData(
            AccountModel::LOSS_ID, 
            '', 
            $sourceDeptId, 
            null, 
            0, 
            $onlyPending
        );

        // Fetch Issue dataset (transfers from Loss to Refining)
        $issueData = $this->processWavesModel->getDepartmentIssueData(
            AccountModel::LOSS_ID, 
            '', 
            $sourceDeptId
        );

        // Calculate balances
        $totalReceiveFine = (float)$this->ledgerService->getAccountTotalReceiveBalance(
            AccountModel::LOSS_ID, 
            $sourceDeptId, 
            'fine'
        );
        $totalIssueFine = (float)$this->ledgerService->getAccountTotalIssueBalance(
            AccountModel::LOSS_ID, 
            $sourceDeptId, 
            'fine'
        );
        $fineMtBalance = round($totalReceiveFine - $totalIssueFine, 3);

        // Aggregate current view weights
        $currentPendingGross = 0.0;
        $currentPendingFine  = 0.0;
        foreach ($receiptData as $r) {
            $currentPendingGross += abs((float)$r['gross_weight']);
            $currentPendingFine  += abs((float)($r['gross_weight'] * $r['purity'] / 100));
        }

        $currentIssueGross = 0.0;
        $currentIssueFine  = 0.0;
        foreach ($issueData as $i) {
            $currentIssueGross += abs((float)$i['gross_weight']);
            $currentIssueFine  += abs((float)($i['gross_weight'] * $i['purity'] / 100));
        }

        // Refining account for transfer target
        $refiningAccount = $this->accountModel->getAccountById(AccountModel::REFINING_ID);

        $data = [
            'title'               => $lossTitle . ' - SpiderWaves',
            'accountName'         => $accountName,
            'lossTitle'           => $lossTitle,
            'deptAccount'         => $deptAccount,
            'sourceDeptId'        => $sourceDeptId,
            'departmentKey'       => $departmentKey,
            'isGeneral'           => $isGeneral,
            'filter'              => $filter,
            'onlyPending'         => $onlyPending,
            'isShowingAll'        => $isShowingAll,
            'receiptData'         => $receiptData,
            'issueData'           => $issueData,
            'totalReceiveFine'    => $totalReceiveFine,
            'totalIssueFine'      => $totalIssueFine,
            'fineMtBalance'       => $fineMtBalance,
            'currentPendingGross' => $currentPendingGross,
            'currentPendingFine'  => $currentPendingFine,
            'currentIssueGross'   => $currentIssueGross,
            'currentIssueFine'    => $currentIssueFine,
            'refiningAccount'     => $refiningAccount,
            'pendingCount'        => $this->getPendingRawWavesCount(),
        ];

        return view('loss/manage', $data);
    }

    /**
     * Render the Multi-Batch Transfer Modal (AJAX GET/POST).
     * Calculates combined weight and prepares transfer into Refining.
     */
    public function transferModal(): string
    {
        $session     = session();
        $spider      = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';

        if (!in_array($accessLevel, ['admin', 'superadmin', 'process_manager', 'g58'], true)) {
            return '<div class="alert alert-danger" style="margin: 15px;">Unauthorized access to loss transfer.</div>';
        }

        $rawBatchIds = $this->request->getVar('batch_ids');
        $deptId      = (int) $this->request->getVar('dept_id');

        if ($accessLevel === 'g58') {
            $allowedG58Loss = [AccountModel::G58_POLISH_ID, AccountModel::G58_FILING_ID, AccountModel::G58_SETTING_ID];
            if (!in_array($deptId, $allowedG58Loss, true)) {
                return '<div class="alert alert-danger" style="margin: 15px;">Unauthorized loss department for G58.</div>';
            }
        }

        if (is_array($rawBatchIds)) {
            $batchIds = array_filter(array_map('intval', $rawBatchIds));
        } else {
            $batchIds = array_filter(array_map('intval', explode(',', (string)$rawBatchIds)));
        }

        if (empty($batchIds)) {
            return '<div class="alert alert-danger" style="margin: 15px;">Please select at least one pending loss batch to transfer.</div>';
        }

        $db = \Config\Database::connect();
        $batches = $db->table('process_waves')
            ->whereIn('process_waves_id', $batchIds)
            ->where('to_id', AccountModel::LOSS_ID)
            ->where('further_to_process_waves_id IS NULL', null, false)
            ->get()
            ->getResultArray();

        if (empty($batches)) {
            return '<div class="alert alert-danger" style="margin: 15px;">The selected batches are no longer pending or could not be found.</div>';
        }

        $totalGross = 0.0;
        foreach ($batches as $b) {
            $totalGross += abs((float)$b['gross_weight']);
        }

        $deptAccount     = $this->accountModel->getAccountById($deptId);
        $refiningAccount = $this->accountModel->getAccountById(AccountModel::REFINING_ID);

        $data = [
            'batchIds'        => array_column($batches, 'process_waves_id'),
            'batches'         => $batches,
            'totalGross'      => round($totalGross, 3),
            'defaultPurity'   => 100.00,
            'activePurities'  => $this->purityModel->getActivePurities(),
            'deptAccount'     => $deptAccount,
            'refiningAccount' => $refiningAccount,
            'deptId'          => $deptId,
        ];

        return view('loss/transfer_modal', $data);
    }

    /**
     * Consolidate and execute transfer to Refining (AJAX POST).
     */
    public function consolidateTransfer(): ResponseInterface
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';
        $userId  = (int) ($spider['user_id'] ?? 1);

        if (!in_array($accessLevel, ['admin', 'superadmin', 'process_manager', 'g58'], true)) {
            return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Unauthorized access.']);
        }

        $rawBatchIds = $this->request->getPost('batch_ids');
        $deptId      = (int) ($this->request->getPost('dept_id') ?: $this->request->getPost('source_dept_id'));

        if ($accessLevel === 'g58') {
            $allowedG58Loss = [AccountModel::G58_POLISH_ID, AccountModel::G58_FILING_ID, AccountModel::G58_SETTING_ID];
            if (!in_array($deptId, $allowedG58Loss, true)) {
                return $this->response->setStatusCode(403)->setJSON(['status' => false, 'message' => 'Unauthorized loss department for G58.']);
            }
        }
        $grossWeight = abs((float) $this->request->getPost('gross_weight'));
        $purity      = (float) $this->request->getPost('purity');
        $remark      = trim((string) $this->request->getPost('remark'));

        if (is_array($rawBatchIds)) {
            $batchIds = array_filter(array_map('intval', $rawBatchIds));
        } else {
            $batchIds = array_filter(array_map('intval', explode(',', (string)$rawBatchIds)));
        }

        if (empty($batchIds)) {
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'No batches selected for transfer.'
            ]);
        }

        if ($grossWeight <= 0.0001) {
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'Total gross weight must be greater than 0.'
            ]);
        }

        if ($purity <= 0 || $purity > 100) {
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'Purity must be between 0.01 and 100.00.'
            ]);
        }

        // Execute atomic multi-batch consolidation
        $result = $this->processWavesModel->consolidateLossBatches(
            AccountModel::LOSS_ID,
            AccountModel::REFINING_ID,
            $deptId,
            $batchIds,
            $grossWeight,
            $purity,
            $userId,
            $remark ?: 'Consolidated loss transfer to Refining'
        );

        return $this->response->setJSON($result);
    }

    /**
     * Printable Loss Ledger Sheet.
     * Reproduces exact legacy Process_waves::print_process_waves() for Loss accounts.
     *
     * @param string $departmentPrivateKey Department private key or empty for General Loss
     */
    public function print(string $departmentPrivateKey = '')
    {
        $session     = session();
        $spider      = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';

        if (!in_array($accessLevel, ['admin', 'superadmin', 'process_manager', 'g58'], true)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access to Loss print sheet.');
        }

        $isGeneral     = false;
        $deptAccount   = null;
        $sourceDeptId  = null;

        if ($departmentPrivateKey === '' || $departmentPrivateKey === AccountModel::LOSS_PRIVATE_KEY) {
            $isGeneral     = true;
            $accountName   = 'General Loss';
            $lossTitle     = 'General Loss';
            $departmentKey = AccountModel::LOSS_PRIVATE_KEY;
        } else {
            $deptAccount = $this->accountModel->getAccountByPrivateKey($departmentPrivateKey);
            if ($deptAccount) {
                $sourceDeptId  = (int)$deptAccount['account_id'];
                $accountName   = $deptAccount['account_name'];
                $lossTitle     = $deptAccount['account_name'] . ' Loss';
                $departmentKey = $deptAccount['private_key'];
            } else {
                $accountName   = 'General Loss';
                $lossTitle     = 'General Loss';
                $departmentKey = AccountModel::LOSS_PRIVATE_KEY;
            }
        }

        if ($accessLevel === 'g58') {
            $allowedG58Ids = [AccountModel::G58_POLISH_ID, AccountModel::G58_FILING_ID, AccountModel::G58_SETTING_ID, AccountModel::G58_TRANSFER_ID];
            if ($isGeneral || ($sourceDeptId && !in_array($sourceDeptId, $allowedG58Ids, true))) {
                return redirect()->to(base_url('loss/manage/' . AccountModel::G58_POLISH_PRIVATE_KEY))
                    ->with('error', 'Unauthorized loss department print sheet for G58.');
            }
        }

        // Fetch Receipt dataset (batches into Loss)
        $receiptData = $this->processWavesModel->getDepartmentReceiptData(
            AccountModel::LOSS_ID, 
            '', 
            $sourceDeptId, 
            null, 
            0, 
            false
        );

        // Fetch Issue dataset (transfers from Loss to Refining)
        $issueData = $this->processWavesModel->getDepartmentIssueData(
            AccountModel::LOSS_ID, 
            '', 
            $sourceDeptId
        );

        // Calculate totals
        $currentPendingGross = 0.0;
        foreach ($receiptData as $r) {
            $currentPendingGross += abs((float)$r['gross_weight']);
        }

        $currentIssueGross = 0.0;
        foreach ($issueData as $i) {
            $currentIssueGross += abs((float)$i['gross_weight']);
        }

        $data = [
            'lossTitle'           => $lossTitle,
            'accountName'         => $accountName,
            'receiptData'         => $receiptData,
            'issueData'           => $issueData,
            'currentPendingGross' => $currentPendingGross,
            'currentIssueGross'   => $currentIssueGross,
            'departmentKey'       => $departmentKey,
        ];

        return view('loss/print_sheet', $data);
    }

    /**
     * Helper to retrieve pending raw waves count for breadcrumb badge.
     */
    protected function getPendingRawWavesCount(): int
    {
        if (function_exists('get_number_of_pending_raw_waves')) {
            return (int) get_number_of_pending_raw_waves();
        }
        return 0;
    }
}

