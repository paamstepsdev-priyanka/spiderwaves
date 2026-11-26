<?php

namespace App\Controllers;

use App\Models\WavesModel;
use App\Models\CustomerModel;
use App\Models\RemarkModel;
use App\Models\PurityModel;
use App\Services\LedgerService;
use App\Services\UnifiedMasterService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * WavesController
 * 
 * Manages the permanent customer financial and gold ledger (table: waves).
 * Tracks MT, OR, and RK customer metal and rupee balances.
 */
class WavesController extends BaseController
{
    protected WavesModel $wavesModel;
    protected CustomerModel $customerModel;
    protected RemarkModel $remarkModel;
    protected PurityModel $purityModel;
    protected LedgerService $ledgerService;
    protected UnifiedMasterService $unifiedMasterService;

    public function __construct()
    {
        $this->wavesModel           = new WavesModel();
        $this->customerModel        = new CustomerModel();
        $this->remarkModel          = new RemarkModel();
        $this->purityModel          = new PurityModel();
        $this->ledgerService        = new LedgerService();
        $this->unifiedMasterService = new UnifiedMasterService();
    }

    /**
     * Check if user session has customer role, and redirect to customer ledger.
     * 
     * @return ResponseInterface|null
     */
    protected function checkCustomerRedirect(): ?ResponseInterface
    {
        $session = session()->get('spider');
        if (!empty($session['access_level']) && $session['access_level'] === 'cust') {
            $privateKey = $session['private_key'] ?? '';
            return redirect()->to(base_url('report/customer_ledger_for_customer/' . $privateKey));
        }
        return null;
    }

    /**
     * Permanent Financial Gold Ledger Dashboard & Transactions List.
     * Replaces legacy CI3 waves/index with modern 2026 enterprise design and full ledger listing.
     * 
     * @return string|ResponseInterface
     */
    public function index()
    {
        if ($redirect = $this->checkCustomerRedirect()) {
            return $redirect;
        }

        // 1. Calculate overall company balances
        $balances = $this->ledgerService->getTotalBalances();
        $rkTotalBalance = $balances['rk_total_balance'];
        $mtTotalBalance = $balances['mt_total_balance'];
        $orTotalBalance = $balances['or_total_balance'];

        // 2. Read filter inputs
        $search   = trim((string)($this->request->getGet('search') ?? ''));
        $custId   = (int)($this->request->getGet('cust_id') ?? 0);
        $mode     = strtoupper(trim((string)($this->request->getGet('mode') ?? '')));
        $type     = strtolower(trim((string)($this->request->getGet('type') ?? '')));
        $fromDate = trim((string)($this->request->getGet('from_date') ?? ''));
        $toDate   = trim((string)($this->request->getGet('to_date') ?? ''));
        $page     = max(1, (int)($this->request->getGet('page') ?? 1));

        $filters = [
            'search'    => $search,
            'cust_id'   => $custId,
            'mode'      => $mode,
            'type'      => $type,
            'from_date' => $fromDate,
            'to_date'   => $toDate,
            'page'      => $page,
        ];

        // 3. Fetch paginated ledger records (25 per page)
        $perPage = 25;
        $paginated = $this->wavesModel->getPaginatedLedger($perPage, $filters);

        // 4. Calculate stats for the current filter
        $stats = $this->wavesModel->getLedgerStats($filters);

        // 5. Active customers and remarks for dropdowns
        $custData = $this->unifiedMasterService->getActiveCustomers();

        $remarkData = $this->remarkModel->where('status', '1')
            ->orderBy('remark', 'ASC')
            ->findAll();

        $pendingCount = function_exists('get_number_of_pending_raw_waves') 
            ? get_number_of_pending_raw_waves() 
            : 0;

        $data = [
            'records'          => $paginated['records'],
            'total_records'    => $paginated['total'],
            'per_page'         => $paginated['per_page'],
            'current_page'     => $paginated['current_page'],
            'total_pages'      => $paginated['total_pages'],
            'search'           => $search,
            'cust_id'          => $custId,
            'mode'             => $mode,
            'type'             => $type,
            'from_date'        => $fromDate,
            'to_date'          => $toDate,
            'rk_total_balance' => $rkTotalBalance,
            'mt_total_balance' => $mtTotalBalance,
            'or_total_balance' => $orTotalBalance,
            'cust_data'        => $custData,
            'remark_data'      => $remarkData,
            'active_purities'  => $this->purityModel->getActivePurities(),
            'stats'            => $stats,
            'pending_count'    => $pendingCount,
        ];

        return view('waves/index', $data);
    }

    /**
     * Standalone Manual Wave Entry screen (Add mode).
     * Exact 1-to-1 parity with legacy CI3 Waves::index() / waves/in_out view.
     * 
     * @return string|ResponseInterface
     */
    public function in_out()
    {
        if ($redirect = $this->checkCustomerRedirect()) {
            return $redirect;
        }

        $balances = $this->ledgerService->getTotalBalances();

        $custData = $this->unifiedMasterService->getActiveCustomers();

        $remarkData = $this->remarkModel->where('status', '1')
            ->orderBy('remark', 'ASC')
            ->findAll();

        $data = [
            'form_type'        => 'Add',
            'waves_data'       => null,
            'rk_total_balance' => $balances['rk_total_balance'],
            'mt_total_balance' => $balances['mt_total_balance'],
            'or_total_balance' => $balances['or_total_balance'],
            'cust_data'        => $custData,
            'remark_data'      => $remarkData,
            'active_purities'  => $this->purityModel->getActivePurities(),
            'pending_count'    => function_exists('get_number_of_pending_raw_waves') ? get_number_of_pending_raw_waves() : 0,
        ];

        return view('waves/in_out', $data);
    }

    /**
     * Standalone Manual Wave Edit screen (Edit mode).
     * Exact 1-to-1 parity with legacy CI3 Waves::edit_in_out($private_key).
     * 
     * @param string $privateKey
     * @return string|ResponseInterface
     */
    public function edit_in_out(string $privateKey)
    {
        if ($redirect = $this->checkCustomerRedirect()) {
            return $redirect;
        }

        $session = session()->get('spider') ?? [];
        $accessLevel = $session['access_level'] ?? '';
        if (!in_array($accessLevel, ['admin', 'superadmin'], true)) {
            session()->setFlashdata('error', 'Unauthorized access to Edit Wave.');
            return redirect()->to(base_url('waves'));
        }

        $wave = $this->wavesModel->getByPrivateKey($privateKey);
        if (!$wave) {
            session()->setFlashdata('error', 'Wave transaction record not found.');
            return redirect()->to(base_url('waves'));
        }

        $balances = $this->ledgerService->getTotalBalances();

        $custData = $this->unifiedMasterService->getActiveCustomers();

        $remarkData = $this->remarkModel->where('status', '1')
            ->orderBy('remark', 'ASC')
            ->findAll();

        $data = [
            'form_type'        => 'Edit',
            'waves_data'       => [(object)$wave],
            'rk_total_balance' => $balances['rk_total_balance'],
            'mt_total_balance' => $balances['mt_total_balance'],
            'or_total_balance' => $balances['or_total_balance'],
            'cust_data'        => $custData,
            'remark_data'      => $remarkData,
            'active_purities'  => $this->purityModel->getActivePurities(),
            'pending_count'    => function_exists('get_number_of_pending_raw_waves') ? get_number_of_pending_raw_waves() : 0,
        ];

        return view('waves/in_out', $data);
    }

    /**
     * Insert or update permanent customer ledger transaction.
     * Compatible with legacy CI3 Waves::insert_in_out().
     * Handles both AJAX calls and standard form posts.
     * 
     * @return ResponseInterface
     */
    public function insert_in_out(): ResponseInterface
    {
        $session = session()->get('spider');
        if (!$session) {
            return $this->isAjax()
                ? $this->response->setBody('false')
                : redirect()->to(base_url('login'))->with('error', 'Session expired.');
        }

        $userId = (int)($session['user_id'] ?? 0);
        $input = $this->request->getPost();

        if (empty($input)) {
            return $this->isAjax()
                ? $this->response->setBody('false')
                : redirect()->to(base_url('waves'))->with('error', 'No input data received.');
        }

        $formType = trim($input['form_type'] ?? 'Add');
        $custId   = (int)($input['cust_id'] ?? 0);
        $mode     = strtoupper(trim($input['transactional_mode'] ?? 'RK'));
        $type     = strtolower(trim($input['transactional_type'] ?? 'receipt'));

        if (!$custId || !in_array($mode, ['RK', 'MT', 'OR'], true) || !in_array($type, ['receipt', 'issue'], true)) {
            return $this->isAjax()
                ? $this->response->setBody('false')
                : redirect()->back()->with('error', 'Missing or invalid required fields.');
        }

        // Determine Wastage for OR mode from Customer profile if not provided
        $wastage = 0.000;
        if ($mode === 'OR') {
            if (isset($input['wastage']) && (float)$input['wastage'] > 0) {
                $wastage = (float)$input['wastage'];
            } else {
                $cust = $this->unifiedMasterService->findCustomerById((int)$custId);
                $wastage = (float)($cust['wastage'] ?? 0);
            }
        }

        // Remark handling
        $remark = trim($input['remark'] ?? '');
        if ($remark === 'other') {
            $remark = trim($input['other_remark'] ?? 'Other');
        }

        // Weights / Amounts based on mode
        if ($mode === 'RK') {
            $grossWeight = 0.000;
            $netWeight   = 0.000;
            $purity      = 0.000;
            $wastage     = 0.000;
            $amount      = (float)($input['amount'] ?? 0);
        } else {
            $grossWeight = (float)($input['gross_weight'] ?? 0);
            $netWeight   = isset($input['net_weight']) && (float)$input['net_weight'] > 0 
                ? (float)$input['net_weight'] 
                : $grossWeight;
            $purity      = (float)($input['purity'] ?? 0);
            $amount      = 0.00;
        }

        $data = [
            'cust_id'            => $custId,
            'transactional_mode' => $mode,
            'transactional_type' => $type,
            'gross_weight'       => $grossWeight,
            'net_weight'         => $netWeight,
            'less_weight'        => (float)($input['less_weight'] ?? 0),
            'purity'             => $purity,
            'wastage'            => $wastage,
            'amount'             => $amount,
            'remark'             => $remark,
            'taken_by'           => $userId,
        ];

        $success = false;

        if ($formType === 'Add') {
            $data['private_key']      = $this->ledgerService->generateGuid();
            $data['entry_date']       = date('Y-m-d');
            $data['approved_data']    = '1';
            $data['approved_by_cust'] = '0';
            $data['is_deleted']       = '0';

            $insertId = $this->wavesModel->insertInOutData($data);
            $success = ($insertId > 0);
        } elseif ($formType === 'Edit') {
            $privateKey = trim($input['private_key'] ?? '');
            if (!empty($privateKey)) {
                $success = $this->wavesModel->updateInOutData($data, ['private_key' => $privateKey]);
            }
        }

        if ($this->isAjax()) {
            // For legacy AJAX compatibility, returning text 'true' or 'false'
            return $this->response->setBody($success ? 'true' : 'false');
        }

        if ($success) {
            $msg = ($formType === 'Edit') ? 'Wave record updated successfully.' : 'Wave record inserted successfully.';
            return redirect()->to(base_url('waves'))->with('success', $msg);
        }

        return redirect()->back()->with('error', 'Failed to save Wave transaction.');
    }

    /**
     * Autocomplete suggestions for customer name search.
     * Compatible with legacy Waves::get_suggestion_cust_name().
     * 
     * @return ResponseInterface
     */
    public function get_suggestion_cust_name(): ResponseInterface
    {
        $searchKey = trim((string)($this->request->getGet('search_key') ?? ''));

        $customers = $this->unifiedMasterService->searchActiveCustomers($searchKey, 30);

        $data = [];
        foreach ($customers as $c) {
            $data[] = [
                'id'   => $c['cust_id'],
                'text' => $c['cust_name'],
            ];
        }

        return $this->response->setJSON($data);
    }

    /**
     * Details view modal for a single ledger record.
     * 
     * @param string $privateKey
     * @return string|ResponseInterface
     */
    public function view_details(string $privateKey)
    {
        $record = $this->wavesModel->getByPrivateKey($privateKey);
        if (!$record) {
            return $this->response->setStatusCode(404)->setBody('<div class="alert alert-danger">Record not found.</div>');
        }

        // Calculate fine weight
        $mode = $record['transactional_mode'];
        $nw   = (float)$record['net_weight'];
        $gw   = (float)$record['gross_weight'];
        $p    = (float)$record['purity'];
        $wst  = (float)$record['wastage'];
        $weight = ($nw > 0) ? $nw : $gw;

        if ($mode === 'MT') {
            $record['fine_weight'] = round(($weight * $p) / 100, 3);
        } elseif ($mode === 'OR') {
            $record['fine_weight'] = round(($weight * ($p + $wst)) / 100, 3);
        } else {
            $record['fine_weight'] = 0.000;
        }

        return view('waves/view_details', ['record' => $record]);
    }

    /**
     * Check if request is an AJAX or XMLHttpRequest.
     * 
     * @return bool
     */
    private function isAjax(): bool
    {
        return $this->request->isAJAX() || 
               strtolower($this->request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest' ||
               str_contains($this->request->getHeaderLine('Accept'), 'application/json');
    }
}
