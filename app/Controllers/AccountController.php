<?php

namespace App\Controllers;

use App\Models\AccountModel;
use App\Models\RawWavesModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AccountController
 *
 * Provides complete frontend-based Account and Account-to-Department Mapping Management.
 * Strictly restricted to Superadmin users only.
 */
class AccountController extends BaseController
{
    protected AccountModel $accountModel;
    protected RawWavesModel $rawWavesModel;

    public function __construct()
    {
        $this->accountModel  = model(AccountModel::class);
        $this->rawWavesModel = model(RawWavesModel::class);
    }

    /**
     * Enforce strict Superadmin-only access for Accounts & Department Mapping management.
     * Admin, Process Manager, Junior Process Manager, G58, and unauthorized users are blocked.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|\CodeIgniter\HTTP\ResponseInterface|null
     */
    protected function checkSuperadminAccess()
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';

        if ($accessLevel !== 'superadmin') {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return $this->response->setStatusCode(403)->setJSON([
                    'status' => false,
                    'error'  => 'Unauthorized. Only Superadmin can access Account Department Mapping.',
                ]);
            }
            session()->setFlashdata('error', 'Unauthorized access. Only Superadmin can manage Accounts and Department Mappings.');
            return redirect()->to(base_url('dashboard'));
        }

        return null;
    }

    /**
     * Retrieve pending raw waves count for breadcrumb badge.
     */
    protected function getPendingRawWavesCount(): int
    {
        try {
            return (int) $this->rawWavesModel->where('approved_data', '0')->where('is_deleted', '0')->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Accounts Management List.
     * Displays all accounts, their types, statuses, and currently mapped departments.
     */
    public function index()
    {
        if ($authRedirect = $this->checkSuperadminAccess()) {
            return $authRedirect;
        }

        $search = $this->request->getGet('search');
        $perPage = 25;

        $paginated = $this->accountModel->getPaginatedAccounts($search, $perPage);
        $accounts  = $paginated['accounts'];
        $pager     = $paginated['pager'];
        $total     = $paginated['total'];

        // Build account map for quick lookup of mapped department names
        $allActive = $this->accountModel->getActiveProcessAccounts();
        $accountNameMap = [];
        foreach ($allActive as $acc) {
            $accountNameMap[(int)$acc['account_id']] = $acc['account_name'];
        }

        // Decorate accounts with parsed mapped departments info
        foreach ($accounts as &$account) {
            $mappedIds = $this->accountModel->parseMappedAccountIds($account['map_with_accounts_id'] ?? '');
            $mappedNames = [];
            foreach ($mappedIds as $mId) {
                if (isset($accountNameMap[$mId])) {
                    $mappedNames[] = $accountNameMap[$mId];
                }
            }
            $account['mapped_ids']   = $mappedIds;
            $account['mapped_names'] = $mappedNames;
            $account['mapped_count'] = count($mappedNames);
        }
        unset($account);

        $data = [
            'title'                => 'Account Management',
            'accounts'             => $accounts,
            'pager'                => $pager,
            'total'                => $total,
            'search'               => $search,
            'pendingCount'         => $this->getPendingRawWavesCount(),
            'availableDepartments' => $this->accountModel->getAvailableMappingAccounts(),
        ];

        return view('master/account/index', $data);
    }

    /**
     * Display Create Account Form with Department Mapping multi-select.
     */
    public function create()
    {
        if ($authRedirect = $this->checkSuperadminAccess()) {
            return $authRedirect;
        }

        $availableDepartments = $this->accountModel->getAvailableMappingAccounts();

        $data = [
            'title'                => 'Create New Process',
            'formType'             => 'Add',
            'account'              => null,
            'mappedAccountIds'     => [],
            'availableDepartments' => $availableDepartments,
            'pendingCount'         => $this->getPendingRawWavesCount(),
        ];

        return view('master/account/form', $data);
    }

    /**
     * Store new account record with selected Department Mappings.
     */
    public function store()
    {
        if ($authRedirect = $this->checkSuperadminAccess()) {
            return $authRedirect;
        }

        $post = $this->request->getPost();
        if (empty($post)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON(['status' => false, 'error' => 'No data submitted.']);
            }
            return redirect()->to(base_url('master/account/create'))->with('error', 'No data submitted.');
        }

        $accountName = trim((string)($post['account_name'] ?? ''));
        $accountType = trim((string)($post['account_type'] ?? ''));
        $lossType    = !empty($post['loss_type']) ? trim((string)$post['loss_type']) : null;
        $lossPercent = isset($post['loss_percentage']) ? (float)$post['loss_percentage'] : 0.0;
        $status      = isset($post['account_status']) && (string)$post['account_status'] === '0' ? '0' : '1';

        // Validation
        if ($accountName === '') {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['status' => false, 'error' => 'Account Name is required.']);
            }
            return redirect()->back()->withInput()->with('error', 'Account Name is required.');
        }

        if (!in_array($accountType, ['process', 'stock', 'loss'], true)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['status' => false, 'error' => 'Valid Account Type is required.']);
            }
            return redirect()->back()->withInput()->with('error', 'Valid Account Type is required.');
        }

        // Check duplicate name
        $existing = $this->accountModel->where('account_name', $accountName)->first();
        if ($existing) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['status' => false, 'error' => 'Account Name already exists!']);
            }
            return redirect()->back()->withInput()->with('error', 'Account Name already exists!');
        }

        // Parse and format selected department mappings into map_with_accounts_id (# delimited)
        $selectedDepts = $post['map_with_accounts'] ?? [];
        if (!is_array($selectedDepts)) {
            $selectedDepts = [];
        }
        $mapWithAccountsId = $this->accountModel->formatMappedAccountIds($selectedDepts);

        // Generate private key (32-char hex)
        $privateKey = bin2hex(random_bytes(16));

        $record = [
            'private_key'          => $privateKey,
            'account_name'         => $accountName,
            'account_type'         => $accountType,
            'loss_type'            => ($accountType === 'loss') ? $lossType : null,
            'loss_percentage'      => $lossPercent,
            'map_with_accounts_id' => $mapWithAccountsId,
            'account_status'       => $status,
        ];

        $db = \Config\Database::connect();
        $db->transStart();

        $insertId = $this->accountModel->insert($record);

        $db->transComplete();

        if ($db->transStatus() === false || !$insertId) {
            $dbError = $db->error();
            $modelErrors = $this->accountModel->errors();
            $errMsg = !empty($modelErrors) ? implode(', ', $modelErrors) : (!empty($dbError['message']) ? $dbError['message'] : 'Failed to save process in database.');
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(500)->setJSON(['status' => false, 'error' => $errMsg]);
            }
            return redirect()->back()->withInput()->with('error', $errMsg);
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'      => true,
                'message'     => "Process '{$accountName}' created and department mappings saved successfully.",
                'account_id'  => $insertId,
                'private_key' => $privateKey,
            ]);
        }

        return redirect()->to(base_url('master/account'))->with('success', "Process '{$accountName}' created and department mappings saved successfully.");
    }

    /**
     * AJAX endpoint to fetch process and mapped department data for modal editing.
     */
    public function getProcessData(string $privateKey)
    {
        if ($authRedirect = $this->checkSuperadminAccess()) {
            return $authRedirect;
        }

        $account = $this->accountModel->getAccountByPrivateKey($privateKey);
        if (!$account) {
            return $this->response->setStatusCode(404)->setJSON(['status' => false, 'error' => 'Account / Process not found.']);
        }

        $mappedAccountIds = $this->accountModel->parseMappedAccountIds($account['map_with_accounts_id'] ?? '');

        return $this->response->setJSON([
            'status'             => true,
            'account'            => $account,
            'mapped_account_ids' => $mappedAccountIds,
        ]);
    }

    /**
     * Display Edit Account Form with pre-selected Department Mappings.
     */
    public function edit(string $privateKey)
    {
        if ($authRedirect = $this->checkSuperadminAccess()) {
            return $authRedirect;
        }

        $account = $this->accountModel->getAccountByPrivateKey($privateKey);
        if (!$account) {
            return redirect()->to(base_url('master/account'))->with('error', 'Account not found.');
        }

        $mappedAccountIds     = $this->accountModel->parseMappedAccountIds($account['map_with_accounts_id'] ?? '');
        $availableDepartments = $this->accountModel->getAvailableMappingAccounts((int)$account['account_id']);

        $data = [
            'title'                => 'Edit Process: ' . $account['account_name'],
            'formType'             => 'Edit',
            'account'              => $account,
            'mappedAccountIds'     => $mappedAccountIds,
            'availableDepartments' => $availableDepartments,
            'pendingCount'         => $this->getPendingRawWavesCount(),
        ];

        return view('master/account/form', $data);
    }

    /**
     * Update account details and department mappings.
     */
    public function update(string $privateKey)
    {
        if ($authRedirect = $this->checkSuperadminAccess()) {
            return $authRedirect;
        }

        $account = $this->accountModel->getAccountByPrivateKey($privateKey);
        if (!$account) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON(['status' => false, 'error' => 'Account not found.']);
            }
            return redirect()->to(base_url('master/account'))->with('error', 'Account not found.');
        }

        $post = $this->request->getPost();
        if (empty($post)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON(['status' => false, 'error' => 'No data submitted.']);
            }
            return redirect()->back()->with('error', 'No data submitted.');
        }

        $accountName = trim((string)($post['account_name'] ?? ''));
        $accountType = trim((string)($post['account_type'] ?? ''));
        $lossType    = !empty($post['loss_type']) ? trim((string)$post['loss_type']) : null;
        $lossPercent = isset($post['loss_percentage']) ? (float)$post['loss_percentage'] : (float)$account['loss_percentage'];
        $status      = isset($post['account_status']) && (string)$post['account_status'] === '0' ? '0' : '1';

        // Validation
        if ($accountName === '') {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['status' => false, 'error' => 'Account Name is required.']);
            }
            return redirect()->back()->withInput()->with('error', 'Account Name is required.');
        }

        if (!in_array($accountType, ['process', 'stock', 'loss'], true)) {
            $accountType = $account['account_type'];
        }

        // Check duplicate name excluding self
        $duplicate = $this->accountModel->where('account_name', $accountName)
            ->where('account_id !=', $account['account_id'])
            ->first();
        if ($duplicate) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON(['status' => false, 'error' => 'Account Name already in use by another account.']);
            }
            return redirect()->back()->withInput()->with('error', 'Account Name already in use by another account.');
        }

        // Parse and format selected department mappings into map_with_accounts_id (# delimited)
        $selectedDepts = $post['map_with_accounts'] ?? [];
        if (!is_array($selectedDepts)) {
            $selectedDepts = [];
        }
        $mapWithAccountsId = $this->accountModel->formatMappedAccountIds($selectedDepts);

        $updateData = [
            'account_name'         => $accountName,
            'account_type'         => $accountType,
            'loss_type'            => ($accountType === 'loss') ? $lossType : null,
            'loss_percentage'      => $lossPercent,
            'map_with_accounts_id' => $mapWithAccountsId,
            'account_status'       => $status,
        ];

        $db = \Config\Database::connect();
        $db->transStart();

        $updated = $this->accountModel->update($account['account_id'], $updateData);

        $db->transComplete();

        if ($db->transStatus() === false || !$updated) {
            $dbError = $db->error();
            $modelErrors = $this->accountModel->errors();
            $errMsg = !empty($modelErrors) ? implode(', ', $modelErrors) : (!empty($dbError['message']) ? $dbError['message'] : 'Failed to update account.');
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(500)->setJSON(['status' => false, 'error' => $errMsg]);
            }
            return redirect()->back()->withInput()->with('error', $errMsg);
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => true,
                'message' => "Process '{$accountName}' and department mappings updated successfully.",
            ]);
        }

        return redirect()->to(base_url('master/account'))->with('success', "Process '{$accountName}' and department mappings updated successfully.");
    }

    /**
     * Delete an account / process after verifying dependencies and foreign references.
     */
    public function delete(string $privateKey)
    {
        if ($authRedirect = $this->checkSuperadminAccess()) {
            return $authRedirect;
        }

        $account = $this->accountModel->getAccountByPrivateKey($privateKey);
        if (!$account) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON(['status' => false, 'error' => 'Account / Process not found.']);
            }
            return redirect()->to(base_url('master/account'))->with('error', 'Account / Process not found.');
        }

        $accountId = (int)$account['account_id'];

        // Comprehensive dependency validation
        $depCheck = $this->accountModel->checkAccountDependencies($accountId);
        if (!$depCheck['can_delete']) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => false,
                    'error'  => $depCheck['reason'],
                ]);
            }
            return redirect()->to(base_url('master/account'))->with('error', $depCheck['reason']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Clean up references to this account from other accounts' map_with_accounts_id
        $otherAccounts = $this->accountModel->where('account_id !=', $accountId)->findAll();
        foreach ($otherAccounts as $other) {
            $mapped = $this->accountModel->parseMappedAccountIds($other['map_with_accounts_id'] ?? '');
            if (in_array($accountId, $mapped, true)) {
                $filtered = array_values(array_diff($mapped, [$accountId]));
                $newMapStr = $this->accountModel->formatMappedAccountIds($filtered);
                $this->accountModel->update($other['account_id'], ['map_with_accounts_id' => $newMapStr]);
            }
        }

        // 2. Delete from mst_process
        $deleted = $this->accountModel->delete($accountId);

        $db->transComplete();

        if ($db->transStatus() === false || !$deleted) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(500)->setJSON(['status' => false, 'error' => 'Database error while deleting process.']);
            }
            return redirect()->to(base_url('master/account'))->with('error', 'Database error while deleting process.');
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'  => true,
                'message' => "Process '{$account['account_name']}' deleted successfully.",
            ]);
        }

        return redirect()->to(base_url('master/account'))->with('success', "Process '{$account['account_name']}' deleted successfully.");
    }

    /**
     * Dedicated Department Mapping (Route Mapping) page.
     * Corresponds to legacy master/map_with_account.
     */
    public function mapping(string $privateKey)
    {
        if ($authRedirect = $this->checkSuperadminAccess()) {
            return $authRedirect;
        }

        $account = $this->accountModel->getAccountByPrivateKey($privateKey);
        if (!$account) {
            return redirect()->to(base_url('master/account'))->with('error', 'Account not found.');
        }

        $mappedAccountIds     = $this->accountModel->parseMappedAccountIds($account['map_with_accounts_id'] ?? '');
        $availableDepartments = $this->accountModel->getAvailableMappingAccounts((int)$account['account_id']);

        $data = [
            'title'                => 'Department Mapping: ' . $account['account_name'],
            'account'              => $account,
            'mappedAccountIds'     => $mappedAccountIds,
            'availableDepartments' => $availableDepartments,
            'pendingCount'         => $this->getPendingRawWavesCount(),
        ];

        return view('master/account/map_with_account', $data);
    }

    /**
     * Update Department Mapping endpoint (AJAX & Form POST).
     * Corresponds to legacy master/update_mapping_data.
     */
    public function updateMapping()
    {
        if ($authRedirect = $this->checkSuperadminAccess()) {
            return $authRedirect;
        }

        $post = $this->request->getPost();
        $privateKey = trim((string)($post['private_key'] ?? ''));

        if ($privateKey === '') {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON(['status' => false, 'error' => 'Account private key is required.']);
            }
            return redirect()->back()->with('error', 'Account private key is required.');
        }

        $account = $this->accountModel->getAccountByPrivateKey($privateKey);
        if (!$account) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON(['status' => false, 'error' => 'Account not found.']);
            }
            return redirect()->to(base_url('master/account'))->with('error', 'Account not found.');
        }

        $selectedDepts = $post['map_with_accounts'] ?? [];
        if (!is_array($selectedDepts)) {
            $selectedDepts = [];
        }

        $mapWithAccountsId = $this->accountModel->formatMappedAccountIds($selectedDepts);

        $updated = $this->accountModel->update($account['account_id'], [
            'map_with_accounts_id' => $mapWithAccountsId,
        ]);

        if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            if ($updated) {
                // Return 'true' for legacy compatibility and rich JSON for modern AJAX
                if ($this->request->getHeaderLine('Accept') === 'text/plain') {
                    return $this->response->setHeader('Content-Type', 'text/plain')->setBody('true');
                }
                return $this->response->setJSON([
                    'status'               => true,
                    'result'               => 'true',
                    'message'              => 'Department mapping updated successfully.',
                    'map_with_accounts_id' => $mapWithAccountsId,
                ]);
            }
            return $this->response->setStatusCode(500)->setJSON(['status' => false, 'result' => 'false', 'error' => 'Failed to update department mapping.']);
        }

        return redirect()->to(base_url('master/account'))->with('success', 'Department mapping updated successfully.');
    }

    /**
     * Toggle Account Status (Active/Inactive).
     */
    public function toggleStatus(string $privateKey)
    {
        if ($authRedirect = $this->checkSuperadminAccess()) {
            return $authRedirect;
        }

        $account = $this->accountModel->getAccountByPrivateKey($privateKey);
        if (!$account) {
            return redirect()->to(base_url('master/account'))->with('error', 'Account not found.');
        }

        $newStatus = ($account['account_status'] === '1') ? '0' : '1';
        $this->accountModel->update($account['account_id'], ['account_status' => $newStatus]);

        $statusLabel = ($newStatus === '1') ? 'activated' : 'deactivated';
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'     => true,
                'new_status' => $newStatus,
                'message'    => "Process '{$account['account_name']}' {$statusLabel} successfully.",
            ]);
        }
        return redirect()->to(base_url('master/account'))->with('success', "Process '{$account['account_name']}' {$statusLabel} successfully.");
    }

    /**
     * Legacy CI2/CI3 form submission handler for master/insert_account.
     */
    public function insertAccountLegacy()
    {
        if ($authRedirect = $this->checkSuperadminAccess()) {
            return $authRedirect;
        }

        $post = $this->request->getPost();
        $formType = trim((string)($post['form_type'] ?? ''));

        if ($formType === 'Edit') {
            $privateKey = trim((string)($post['private_key'] ?? ''));
            return $this->update($privateKey);
        }

        return $this->store();
    }
}

