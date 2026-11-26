<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use App\Models\CustomerMappingModel;
use App\Models\CollectionModel;
use App\Models\UserModel;
use App\Models\PettyCashModel;
use App\Models\CustomerWastageModel;
use App\Models\WastageModel;
use App\Models\RemarkModel;
use App\Models\PurityModel;
use App\Models\UccCustomerModel;
use App\Models\UccMappedCustomerModel;
use App\Models\UccCollectionModel;
use App\Models\UccPurityModel;
use App\Models\UccWastageModel;
use App\Services\UnifiedMasterService;
use App\Services\WastageService;
use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MasterController extends BaseController
{
    protected CustomerModel $customerModel;
    protected CustomerMappingModel $customerMappingModel;
    protected CollectionModel $collectionModel;
    protected UserModel $userModel;
    protected PettyCashModel $pettyCashModel;
    protected CustomerWastageModel $customerWastageModel;
    protected WastageModel $wastageModel;
    protected RemarkModel $remarkModel;
    protected PurityModel $purityModel;
    protected UccCustomerModel $uccCustomerModel;
    protected UccMappedCustomerModel $uccMappedCustomerModel;
    protected UccCollectionModel $uccCollectionModel;
    protected UccPurityModel $uccPurityModel;
    protected UccWastageModel $uccWastageModel;
    protected UnifiedMasterService $unifiedMasterService;
    protected WastageService $wastageService;

    public function __construct()
    {
        $this->customerModel          = new CustomerModel();
        $this->customerMappingModel   = new CustomerMappingModel();
        $this->collectionModel        = new CollectionModel();
        $this->userModel              = new UserModel();
        $this->pettyCashModel         = new PettyCashModel();
        $this->customerWastageModel   = new CustomerWastageModel();
        $this->wastageModel           = new WastageModel();
        $this->remarkModel            = new RemarkModel();
        $this->purityModel            = new PurityModel();
        $this->uccCustomerModel       = new UccCustomerModel();
        $this->uccMappedCustomerModel = new UccMappedCustomerModel();
        $this->uccCollectionModel     = new UccCollectionModel();
        $this->uccPurityModel         = new UccPurityModel();
        $this->uccWastageModel        = new UccWastageModel();
        $this->unifiedMasterService   = new UnifiedMasterService();
        $this->wastageService         = new WastageService();
    }

    /**
     * Customer Master - List view with pagination, search, and filtering.
     */
    public function customer(): string
    {
        $search = trim((string)($this->request->getGet('q') ?? ''));
        $type   = trim((string)($this->request->getGet('type') ?? ''));
        $status = $this->request->getGet('status');
        $sort   = trim((string)($this->request->getGet('sort') ?? ''));

        $perPage      = 20;
        $page         = (int)($this->request->getGet('page') ?? 1);
        $res          = $this->unifiedMasterService->getPaginatedCustomers($perPage, $search, $type, $status, $page, $sort);
        $customers    = $res['rows'];
        $pager        = $res['pager'];
        $summaryStats = $this->unifiedMasterService->getCustomerSummaryStats();

        $data = [
            'title'        => 'Customer Master',
            'customers'    => $customers,
            'pager'        => $pager,
            'total'        => $res['total'],
            'totalAll'     => (int)($summaryStats['total_customers'] ?? $res['total']),
            'hasFilters'   => ($search !== '' || $type !== '' || ($status !== null && $status !== '')),
            'search'       => $search,
            'type'         => $type,
            'status'       => $status,
            'sort'         => $sort,
            'summaryStats' => $summaryStats,
        ];

        return view('master/customer/index', $data);
    }

    /**
     * Backward-compatible alias for manage_cust().
     */
    public function manage_cust()
    {
        return $this->customer();
    }

    /**
     * Customer Master - Display Add form.
     */
    public function createCustomer()
    {
        return redirect()->to(base_url('master/customer'))->with('error', 'Customer Master is managed centrally in UCC and synchronized as read-only. Manual creation in SpiderWaves is disabled.');
    }

    /**
     * Backward-compatible alias for add_cust().
     */
    public function add_cust()
    {
        return $this->createCustomer();
    }

    /**
     * Customer Master - Store new customer disabled (read-only from UCC).
     */
    public function storeCustomer()
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Customer Master is managed centrally in UCC and synchronized as read-only.',
            ]);
        }
        return redirect()->to(base_url('master/customer'))->with('error', 'Customer Master is managed centrally in UCC and synchronized as read-only. Manual creation in SpiderWaves is disabled.');
    }

    /**
     * Customer Master - Display Edit form disabled (read-only from UCC).
     *
     * @param string $idOrKey
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function editCustomer(string $idOrKey)
    {
        return redirect()->to(base_url('master/customer'))->with('error', 'Customer Master is managed centrally in UCC and synchronized as read-only. Manual modification in SpiderWaves is disabled.');
    }

    /**
     * Backward-compatible alias for edit_cust().
     */
    public function edit_cust(string $idOrKey)
    {
        return $this->editCustomer($idOrKey);
    }

    /**
     * Customer Master - Update existing customer disabled (read-only from UCC).
     *
     * @param string $idOrKey
     * @return \CodeIgniter\HTTP\RedirectResponse|ResponseInterface
     */
    public function updateCustomer(string $idOrKey)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Customer Master is managed centrally in UCC and synchronized as read-only.',
            ]);
        }
        return redirect()->to(base_url('master/customer'))->with('error', 'Customer Master is managed centrally in UCC and synchronized as read-only.');
    }

    /**
     * Backward-compatible handler for old insert_cust endpoint disabled (read-only from UCC).
     */
    public function insert_cust()
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Customer Master is managed centrally in UCC and synchronized as read-only.',
            ]);
        }
        return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
    }

    /**
     * Soft toggle / deactivate customer status without physical deletion.
     *
     * @param string $idOrKey
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function toggleCustomerStatus(string $idOrKey)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Customer status is managed centrally in UCC and synchronized as read-only.',
            ]);
        }
        return redirect()->to(base_url('master/customer'))->with('error', 'Customer status is managed centrally in UCC and synchronized as read-only.');
    }

    // =========================================================================
    // CUSTOMER MAPPING METHODS
    // =========================================================================

    /**
     * Customer Mapping - List view with pagination, search, and filtering.
     */
    public function customerMapping(): string
    {
        $search       = trim((string)($this->request->getGet('q') ?? ''));
        $custId       = (int)($this->request->getGet('cust_id') ?? 0);
        $collectionId = (int)($this->request->getGet('collection_id') ?? 0);
        $status       = $this->request->getGet('status');
        $sort         = trim((string)($this->request->getGet('sort') ?? ''));

        $perPage      = 20;
        $page         = (int)($this->request->getGet('page') ?? 1);
        $res          = $this->unifiedMasterService->getPaginatedMappings(
            $perPage,
            $search ?: null,
            $custId ?: null,
            $collectionId ?: null,
            $status !== null && $status !== '' ? (string)$status : null,
            $page,
            $sort
        );
        $mappings     = $res['rows'];
        $pager        = $res['pager'];

        $activeCustomers   = $this->unifiedMasterService->getActiveCustomers();
        $activeCollections = $this->unifiedMasterService->getActiveCollections();
        $summaryStats      = $this->unifiedMasterService->getMappedCustomerSummaryStats();

        $data = [
            'title'             => 'Mapped Customer',
            'mappings'          => $mappings,
            'pager'             => $pager,
            'total'             => $res['total'],
            'totalAll'          => (int)($summaryStats['total_mappings'] ?? $res['total']),
            'hasFilters'        => ($search !== '' || $custId > 0 || $collectionId > 0 || ($status !== null && $status !== '')),
            'search'            => $search,
            'custId'            => $custId,
            'collectionId'      => $collectionId,
            'status'            => $status,
            'sort'              => $sort,
            'activeCustomers'   => $activeCustomers,
            'activeCollections' => $activeCollections,
            'summaryStats'      => $summaryStats,
        ];

        return view('master/customer_mapping/index', $data);
    }

    /**
     * Backward-compatible alias for manage_cust_mapping().
     */
    public function manage_cust_mapping()
    {
        return $this->customerMapping();
    }

    /**
     * Customer Mapping - Display Add form.
     */
    public function createCustomerMapping()
    {
        return redirect()->to(base_url('master/customer-mapping'))->with('error', 'Mapped Customer is managed centrally in UCC and synchronized as read-only. Manual creation in SpiderWaves is disabled.');
    }

    /**
     * Backward-compatible alias for add_cust_mapping().
     */
    public function add_cust_mapping()
    {
        return $this->createCustomerMapping();
    }

    /**
     * Customer Mapping - Display Edit form.
     */
    public function editCustomerMapping(string $privateKey)
    {
        return redirect()->to(base_url('master/customer-mapping'))->with('error', 'Mapped Customer is managed centrally in UCC and synchronized as read-only. Manual modification in SpiderWaves is disabled.');
    }

    /**
     * Backward-compatible alias for edit_cust_mapping().
     */
    public function edit_cust_mapping(string $privateKey)
    {
        return $this->editCustomerMapping($privateKey);
    }

    /**
     * Customer Mapping - Insert or Update handler disabled (read-only from UCC).
     */
    public function insertCustomerMapping()
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Mapped Customer is managed centrally in UCC and synchronized as read-only.',
            ]);
        }
        return redirect()->to(base_url('master/customer-mapping'))->with('error', 'Mapped Customer is managed centrally in UCC and synchronized as read-only.');
    }

    /**
     * Backward-compatible alias for insert_cust_mapping().
     */
    public function insert_cust_mapping()
    {
        return $this->insertCustomerMapping();
    }

    /**
     * Customer Mapping - Excel bulk import using PhpSpreadsheet.
     * Preserves exact CI3 format:
     * - Cell I1: Collection Name
     * - Row 3+: Column B (Customer Name), Column I (Mapped Customer Name), Column J (Purity)
     */
    public function importCustomerMapping()
    {
        return redirect()->to(base_url('master/customer-mapping'))->with('error', 'Excel import is disabled. Master data is synchronized directly from UCC.');
    }

    /**
     * Backward-compatible alias for insert_mapped_customer_name_from_excel().
     */
    public function insert_mapped_customer_name_from_excel()
    {
        return $this->importCustomerMapping();
    }

    /**
     * Soft toggle active/inactive status for Customer Mapping disabled (read-only from UCC).
     */
    public function toggleCustomerMappingStatus(string $idOrKey)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Mapped Customer status is managed centrally in UCC and synchronized as read-only.',
            ]);
        }
        return redirect()->to(base_url('master/customer-mapping'))->with('error', 'Mapped Customer status is managed centrally in UCC and synchronized as read-only.');
    }

    // =========================================================================
    // DOWNSTREAM AJAX HELPER ENDPOINTS
    // =========================================================================

    /**
     * AJAX endpoint: Returns <option> dropdown list of active mapped customers.
     */
    public function get_mapped_cust_option()
    {
        $mappings = $this->unifiedMasterService->getActiveMappings();

        $output = '<option value="">Select Mapped Cust</option>';
        foreach ($mappings as $m) {
            $output .= '<option value="' . esc($m['mapped_cust_id']) . '">' . esc($m['mapped_cust_name']) . '</option>';
        }

        return $this->response->setHeader('Content-Type', 'text/html')->setBody($output);
    }

    /**
     * AJAX endpoint: Returns purity float for mapped customer ID.
     */
    public function get_mapped_cust_purity_by_mapped_cust_id()
    {
        $mappedCustId = (int)($this->request->getPost('mapped_cust_id') ?? 0);
        $purity       = $this->customerMappingModel->getPurityByMappedId($mappedCustId)
            ?? $this->uccMappedCustomerModel->getPurityByMappedId($mappedCustId);

        return $this->response->setHeader('Content-Type', 'text/plain')->setBody($purity !== null ? number_format((float)$purity, 2, '.', '') : '');
    }

    /**
     * AJAX endpoint: Returns customer name string for mapped customer ID.
     */
    public function get_cust_name_by_mapped_cust()
    {
        $mappedCustId = (int)($this->request->getPost('mapped_cust_id') ?? 0);
        $custId       = $this->customerMappingModel->getCustomerIdByMappedId($mappedCustId);
        $custName     = $custId ? $this->customerModel->getCustName($custId) : '';
        if ($custName === '') {
            $custName = $this->uccMappedCustomerModel->getCustomerNameByMappedId($mappedCustId);
        }

        return $this->response->setHeader('Content-Type', 'text/plain')->setBody($custName);
    }

    /**
     * AJAX endpoint: Returns collection name string for mapped customer ID.
     */
    public function get_collection_name_by_mapped_customer_id($id = null)
    {
        $mappedCustId = (int)($id ?: ($this->request->getPost('mapped_cust_id') ?? 0));
        $collectionId = $this->customerMappingModel->getCollectionIdByMappedId($mappedCustId);
        $collName     = $collectionId ? $this->collectionModel->getCollectionName($collectionId) : '';
        if ($collName === '') {
            $collName = $this->uccMappedCustomerModel->getCollectionNameByMappedId($mappedCustId);
        }

        return $this->response->setHeader('Content-Type', 'text/plain')->setBody($collName);
    }

    /**
     * AJAX endpoint: Returns customer wastage decimal for mapped customer ID.
     */
    public function get_customer_wastage_data_by_mapped_customer_id($id = null)
    {
        $mappedCustId = (int)($id ?: ($this->request->getPost('mapped_cust_id') ?? 0));
        $mapping      = $this->customerMappingModel->find($mappedCustId);

        if ($mapping && $mapping['mapped_cust_status'] === '1') {
            $wastage = $this->customerMappingModel->getCustomerWastage((int)$mapping['cust_id'], (int)$mapping['collection_id']);
            if ($wastage !== null) {
                return $this->response->setHeader('Content-Type', 'text/plain')->setBody((string)$wastage);
            }
        }

        $wastage = $this->uccMappedCustomerModel->getWastageByMappedId($mappedCustId);

        return $this->response->setHeader('Content-Type', 'text/plain')->setBody($wastage !== null ? (string)$wastage : '');
    }

    // =========================================================================
    // COLLECTION MASTER MODULE
    // =========================================================================

    /**
     * Collection Master - Main list view with pagination, search, and status filter.
     */
    public function collection(): string
    {
        $search = trim((string)($this->request->getGet('q') ?? ''));
        $status = $this->request->getGet('status');

        $perPage      = 20;
        $page         = (int)($this->request->getGet('page') ?? 1);
        $res          = $this->unifiedMasterService->getPaginatedCollections($perPage, $search, $status, $page);
        $collections  = $res['rows'];
        $pager        = $res['pager'];

        $data = [
            'title'        => 'Collection Master',
            'collections'  => $collections,
            'pager'        => $pager,
            'total'        => $res['total'],
            'totalAll'     => $this->unifiedMasterService->getTotalCollectionsCount(),
            'hasFilters'   => ($search !== '' || ($status !== null && $status !== '')),
            'search'       => $search,
            'status'       => $status,
        ];

        return view('master/collection/index', $data);
    }

    /**
     * Backward-compatible alias for manage_collection.
     */
    public function manage_collection()
    {
        return $this->collection();
    }

    /**
     * Display Add Collection form disabled (read-only from UCC).
     */
    public function createCollection()
    {
        return redirect()->to(base_url('master/collection'))->with('error', 'Collection Master is managed centrally in UCC and synchronized as read-only. Manual creation in SpiderWaves is disabled.');
    }

    /**
     * Backward-compatible alias for add_collection.
     */
    public function add_collection()
    {
        return $this->createCollection();
    }

    /**
     * Display Edit Collection form disabled (read-only from UCC).
     */
    public function editCollection(string $privateKey)
    {
        return redirect()->to(base_url('master/collection'))->with('error', 'Collection Master is managed centrally in UCC and synchronized as read-only. Manual modification in SpiderWaves is disabled.');
    }

    /**
     * Backward-compatible alias for edit_collection.
     */
    public function edit_collection(string $privateKey)
    {
        return $this->editCollection($privateKey);
    }

    /**
     * Insert or update collection disabled (read-only from UCC).
     */
    public function insertCollection()
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Collection Master is managed centrally in UCC and synchronized as read-only.',
            ]);
        }
        return redirect()->to(base_url('master/collection'))->with('error', 'Collection Master is managed centrally in UCC and synchronized as read-only.');
    }

    /**
     * Backward-compatible alias for insert_collection.
     */
    public function insert_collection()
    {
        return $this->insertCollection();
    }

    /**
     * Safe soft toggle disabled (read-only from UCC).
     */
    public function toggleCollectionStatus(string $privateKey)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Collection status is managed centrally in UCC and synchronized as read-only.',
            ]);
        }
        return redirect()->to(base_url('master/collection'))->with('error', 'Collection status is managed centrally in UCC and synchronized as read-only.');
    }

    // =========================================================================
    // PURITY MASTER MODULE
    // =========================================================================

    /**
     * Purity Master - Main list view with pagination, search, and status filter.
     */
    public function purity(): string
    {
        $search = trim((string)($this->request->getGet('q') ?? ''));
        $status = $this->request->getGet('status');

        $perPage      = 20;
        $page         = (int)($this->request->getGet('page') ?? 1);
        $res          = $this->unifiedMasterService->getPaginatedPurities($perPage, $search, $status, $page);
        $purities     = $res['rows'];
        $pager        = $res['pager'];
        $summaryStats = $this->unifiedMasterService->getPuritySummaryStats();

        $data = [
            'title'        => 'Purity Master',
            'purities'     => $purities,
            'pager'        => $pager,
            'total'        => $res['total'],
            'totalAll'     => (int)($summaryStats['total_purities'] ?? $res['total']),
            'hasFilters'   => ($search !== '' || ($status !== null && $status !== '')),
            'search'       => $search,
            'status'       => $status,
            'summaryStats' => $summaryStats,
        ];

        return view('master/purity/index', $data);
    }

    /**
     * Backward-compatible alias for manage_purity.
     */
    public function manage_purity()
    {
        return $this->purity();
    }

    /**
     * Display Add Purity form disabled (read-only from UCC).
     */
    public function createPurity()
    {
        return redirect()->to(base_url('master/purity'))->with('error', 'Purity Master is managed centrally in UCC and synchronized as read-only. Manual creation in SpiderWaves is disabled.');
    }

    /**
     * Backward-compatible alias for add_purity.
     */
    public function add_purity()
    {
        return $this->createPurity();
    }

    /**
     * Display Edit Purity form disabled (read-only from UCC).
     */
    public function editPurity(string $privateKey)
    {
        return redirect()->to(base_url('master/purity'))->with('error', 'Purity Master is managed centrally in UCC and synchronized as read-only. Manual modification in SpiderWaves is disabled.');
    }

    /**
     * Backward-compatible alias for edit_purity.
     */
    public function edit_purity(string $privateKey)
    {
        return $this->editPurity($privateKey);
    }

    /**
     * Insert or update purity disabled (read-only from UCC).
     */
    public function insertPurity()
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Purity Master is managed centrally in UCC and synchronized as read-only.',
            ]);
        }
        return redirect()->to(base_url('master/purity'))->with('error', 'Purity Master is managed centrally in UCC and synchronized as read-only.');
    }

    /**
     * Backward-compatible alias for insert_purity.
     */
    public function insert_purity()
    {
        return $this->insertPurity();
    }

    /**
     * Safe soft toggle disabled (read-only from UCC).
     */
    public function togglePurityStatus(string $privateKey)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Purity status is managed centrally in UCC and synchronized as read-only.',
            ]);
        }
        return redirect()->to(base_url('master/purity'))->with('error', 'Purity status is managed centrally in UCC and synchronized as read-only.');
    }

    /**
     * Return active purities JSON for dropdowns / AJAX pickers.
     */
    public function getActivePuritiesJson()
    {
        return $this->response->setJSON($this->unifiedMasterService->getActivePurities());
    }

    // =========================================================================
    // UCC WASTAGE MASTER MODULE (READ-ONLY)
    // =========================================================================

    /**
     * UCC Wastage Master - Read-only list view from ucc_wastage.
     */
    public function uccWastage(): string
    {
        $search = trim((string)($this->request->getGet('q') ?? ''));
        $status = $this->request->getGet('status');

        $perPage  = 20;
        $wastages = $this->uccWastageModel->getPaginatedWastages(
            $perPage,
            $search !== '' ? $search : null,
            ($status !== null && $status !== '') ? (string)$status : null
        );
        $pager    = $this->uccWastageModel->pager;
        $totalAll = (int)$this->uccWastageModel->where('deleted_at IS NULL')->countAllResults();

        return view('master/ucc_wastage/index', [
            'title'      => 'Wastage Master',
            'wastages'   => $wastages,
            'pager'      => $pager,
            'total'      => $pager->getTotal(),
            'totalAll'   => $totalAll,
            'hasFilters' => ($search !== '' || ($status !== null && $status !== '')),
            'search'     => $search,
            'status'     => $status,
        ]);
    }

    // =========================================================================
    // USER MANAGEMENT MODULE
    // =========================================================================

    /**
     * User Management - Main List View.
     */
    public function user(): string
    {
        $search      = trim((string)($this->request->getGet('q') ?? ''));
        $accessLevel = trim((string)($this->request->getGet('access_level') ?? ''));
        $location    = trim((string)($this->request->getGet('location') ?? ''));
        $status      = $this->request->getGet('status');

        $perPage      = 10;
        $users        = $this->userModel->getPaginatedUsers($perPage, $search, $accessLevel, $location, $status);
        $pager        = $this->userModel->pager;
        $summaryStats = $this->userModel->getSummaryStats();
        $roles        = $this->userModel->getAvailableRoles();
        $locations    = $this->userModel->getAvailableLocations();

        $data = [
            'title'        => 'User Management',
            'users'        => $users,
            'pager'        => $pager,
            'search'       => $search,
            'accessLevel'  => $accessLevel,
            'location'     => $location,
            'status'       => $status,
            'roles'        => $roles,
            'locations'    => $locations,
            'summaryStats' => $summaryStats,
        ];

        return view('master/user/index', $data);
    }

    /**
     * Backward-compatible alias for manage_user.
     */
    public function manage_user()
    {
        return $this->user();
    }

    /**
     * Display Add User form.
     */
    public function createUser(): string
    {
        $data = [
            'title'     => 'Add User',
            'formType'  => 'Add',
            'roles'     => $this->userModel->getAvailableRoles(),
            'locations' => $this->userModel->getAvailableLocations(),
        ];

        return view('master/user/create', $data);
    }

    /**
     * Backward-compatible alias for add_user.
     */
    public function add_user()
    {
        return $this->createUser();
    }

    /**
     * Display Edit User form.
     */
    public function editUser(string $privateKey)
    {
        $user = $this->userModel->getByPrivateKey($privateKey);
        if (!$user) {
            return redirect()->to(base_url('master/user'))->with('error', 'User not found.');
        }

        $data = [
            'title'     => 'Edit User',
            'formType'  => 'Edit',
            'user'      => $user,
            'roles'     => $this->userModel->getAvailableRoles(),
            'locations' => $this->userModel->getAvailableLocations(),
        ];

        return view('master/user/edit', $data);
    }

    /**
     * Backward-compatible alias for edit_user.
     */
    public function edit_user(string $privateKey)
    {
        return $this->editUser($privateKey);
    }

    /**
     * View user details modal.
     */
    public function viewUser(string $privateKey)
    {
        $user = $this->userModel->getByPrivateKey($privateKey);
        if (!$user) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setBody('User not found.');
            }
            return redirect()->to(base_url('master/user'))->with('error', 'User not found.');
        }

        $data = [
            'title'     => 'View User',
            'user'      => $user,
            'roles'     => $this->userModel->getAvailableRoles(),
            'locations' => $this->userModel->getAvailableLocations(),
        ];

        return view('master/user/view_modal', $data);
    }

    /**
     * Backward-compatible alias for view_user.
     */
    public function view_user(string $privateKey)
    {
        return $this->viewUser($privateKey);
    }

    /**
     * Store or Update user via AJAX (backward-compatible output: 'true'/'false').
     */
    public function insertUser()
    {
        $postData = $this->request->getPost();
        if (empty($postData)) {
            return $this->response->setBody('false');
        }

        $formType    = $postData['form_type'] ?? 'Add';
        $privateKey  = $postData['private_key'] ?? '';
        $username    = trim((string)($postData['user_full_name'] ?? ''));
        $password    = (string)($postData['user_password'] ?? '');
        $accessLevel = (string)($postData['user_access_level'] ?? '');
        $location    = !empty($postData['user_type']) ? (string)$postData['user_type'] : null;
        $status      = isset($postData['user_status']) ? (string)$postData['user_status'] : '1';

        if (empty($username) || empty($accessLevel)) {
            return $this->response->setBody('false');
        }

        // Validate access level
        $availableRoles = array_keys($this->userModel->getAvailableRoles());
        if (!in_array($accessLevel, $availableRoles, true)) {
            return $this->response->setBody('false');
        }

        // Validate location
        if ($location !== null && !in_array($location, ['ho', 'ug'], true)) {
            $location = null;
        }

        if ($formType === 'Add') {
            if (empty($password)) {
                return $this->response->setBody('false');
            }

            // Check duplicate username
            if ($this->userModel->isUsernameExists($username)) {
                return $this->response->setBody('false');
            }

            $record = [
                'private_key'  => md5(uniqid((string)mt_rand(), true)),
                'username'     => $username,
                'password'     => $password,
                'access_level' => $accessLevel,
                'location'     => $location,
                'status'       => $status,
            ];

            $inserted = $this->userModel->insert($record);
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody($inserted ? 'true' : 'false');
        }

        if ($formType === 'Edit') {
            $existingUser = $this->userModel->getByPrivateKey($privateKey);
            if (!$existingUser) {
                return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
            }

            // Check duplicate username excluding this user
            if ($this->userModel->isUsernameExists($username, $privateKey)) {
                return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
            }

            $updateData = [
                'username'     => $username,
                'access_level' => $accessLevel,
                'location'     => $location,
                'status'       => $status,
            ];

            // If a password is provided, store in plain text (no bcrypt)
            if ($password !== '') {
                $updateData['password'] = $password;
            }

            $updated = $this->userModel->update($existingUser['user_id'], $updateData);
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody($updated !== false ? 'true' : 'false');
        }

        return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
    }

    /**
     * Backward-compatible alias for insert_user.
     */
    public function insert_user()
    {
        return $this->insertUser();
    }

    /**
     * Soft toggle active/inactive status for user.
     */
    public function toggleUserStatus(string $privateKey)
    {
        $result = $this->userModel->toggleUserStatus($privateKey);

        if ($result['success']) {
            return redirect()->to(base_url('master/user'))->with('success', $result['message']);
        }

        return redirect()->to(base_url('master/user'))->with('error', $result['message']);
    }

    /**
     * =========================================================================
     * PETTY CASH MASTER
     * =========================================================================
     */

    /**
     * Petty Cash Master listing view with pagination, search, and status filter.
     */
    public function pettyCash(): string
    {
        $search = trim((string)($this->request->getGet('q') ?? ''));
        $status = $this->request->getGet('status');

        $perPage      = 25;
        $customers    = $this->pettyCashModel->getPaginatedPettyCash($perPage, $search, $status);
        $pager        = $this->pettyCashModel->pager;
        $summaryStats = $this->pettyCashModel->getSummaryStats();

        $data = [
            'title'        => 'Petty Cash Cust Master',
            'customers'    => $customers,
            'pager'        => $pager,
            'search'       => $search,
            'status'       => $status,
            'summaryStats' => $summaryStats,
        ];

        return view('master/petty_cash/index', $data);
    }

    /**
     * Backward-compatible alias for manage_petty_cash_cust.
     */
    public function manage_petty_cash_cust()
    {
        return $this->pettyCash();
    }

    /**
     * Form to assign / designate an existing customer as Petty Cash.
     */
    public function createPettyCash(): string
    {
        $availableCustomers = $this->pettyCashModel->getAvailableCustomersForPettyCash();

        $data = [
            'title'              => 'Assign Petty Cash',
            'availableCustomers' => $availableCustomers,
        ];

        return view('master/petty_cash/create', $data);
    }

    /**
     * Store new Petty Cash designation from create form.
     */
    public function storePettyCash()
    {
        $postData = $this->request->getPost();
        if (empty($postData)) {
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
        }

        $privateKey  = trim((string)($postData['private_key'] ?? ''));
        $isPettyCash = (string)($postData['is_petty_cash'] ?? '1');

        if (empty($privateKey) || !in_array($isPettyCash, ['0', '1'], true)) {
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
        }

        $customer = $this->pettyCashModel->getByPrivateKey($privateKey);
        if (!$customer) {
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
        }

        $updated = $this->pettyCashModel->setPettyCash($privateKey, $isPettyCash);

        if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody($updated ? 'true' : 'false');
        }

        if ($updated) {
            return redirect()->to(base_url('master/petty-cash'))->with('success', 'Petty Cash designation saved successfully.');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to save Petty Cash designation.');
    }

    /**
     * Edit Petty Cash designation for a single customer.
     */
    public function editPettyCash(string $privateKey)
    {
        $customer = $this->pettyCashModel->getByPrivateKey($privateKey);
        if (!$customer) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Customer not found for key: {$privateKey}");
        }

        $data = [
            'title'    => 'Edit Petty Cash Status',
            'customer' => $customer,
        ];

        return view('master/petty_cash/edit', $data);
    }

    /**
     * Update Petty Cash designation from edit form.
     */
    public function updatePettyCash(string $privateKey)
    {
        $postData = $this->request->getPost();
        if (empty($postData)) {
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
        }

        $isPettyCash = (string)($postData['is_petty_cash'] ?? '0');
        if (!in_array($isPettyCash, ['0', '1'], true)) {
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
        }

        $customer = $this->pettyCashModel->getByPrivateKey($privateKey);
        if (!$customer) {
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
        }

        $updated = $this->pettyCashModel->setPettyCash($privateKey, $isPettyCash);

        if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody($updated ? 'true' : 'false');
        }

        if ($updated) {
            return redirect()->to(base_url('master/petty-cash'))->with('success', 'Petty Cash status updated successfully.');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to update Petty Cash status.');
    }

    /**
     * One-click toggle of Petty Cash status ('1' <-> '0').
     */
    public function togglePettyCashStatus(string $privateKey)
    {
        $newStatus = $this->pettyCashModel->togglePettyCash($privateKey);

        if ($newStatus !== false) {
            $msg = ($newStatus === '1')
                ? 'Account designated as Petty Cash successfully.'
                : 'Account reverted to standard status.';
            return redirect()->to(base_url('master/petty-cash'))->with('success', $msg);
        }

        return redirect()->to(base_url('master/petty-cash'))->with('error', 'Failed to toggle Petty Cash status.');
    }

    /**
     * Bulk update customer petty cash status (replicates legacy insert_petty_cash_cust).
     */
    public function bulkUpdatePettyCash()
    {
        $postData = $this->request->getPost();
        if (empty($postData)) {
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
        }

        $updated = $this->pettyCashModel->bulkUpdatePettyCash($postData);

        if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody($updated ? 'true' : 'false');
        }

        if ($updated) {
            return redirect()->to(base_url('master/petty-cash'))->with('success', 'Petty Cash configurations updated successfully.');
        }

        return redirect()->to(base_url('master/petty-cash'))->with('error', 'No configurations were updated.');
    }

    /**
     * Backward-compatible alias for legacy insert_petty_cash_cust.
     */
    public function insert_petty_cash_cust()
    {
        return $this->bulkUpdatePettyCash();
    }

    /**
     * =========================================================================
     * CUSTOMER WASTAGE MAPPING
     * =========================================================================
     */

    /**
     * Display Customer Wastage Mapping view for a given customer private_key.
     */
    public function customerWastageMapping(string $custPrivateKey)
    {
        $customer = $this->customerModel->getByPrivateKey($custPrivateKey);
        if (!$customer) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Customer not found for key: {$custPrivateKey}");
        }

        $activeCollections = $this->collectionModel->getActiveCollections();
        $wastageMap        = $this->customerWastageModel->getWastageMapByCustomerId((int)$customer['cust_id']);

        $data = [
            'title'        => 'Manage Wastage for Customer ' . $customer['cust_name'],
            'customer'     => $customer,
            'collections'  => $activeCollections,
            'wastageMap'   => $wastageMap,
        ];

        return view('master/customer/wastage_mapping', $data);
    }

    /**
     * Backward-compatible alias for customer_wastage_mapping.
     */
    public function customer_wastage_mapping(string $key)
    {
        return $this->customerWastageMapping($key);
    }

    /**
     * Store or update customer wastage mapping (replicates legacy insert_customer_wastage_data).
     */
    public function insertCustomerWastageData()
    {
        $postData   = $this->request->getPost();
        $customerId = isset($postData['customer_id']) ? (int)$postData['customer_id'] : 0;

        if ($customerId <= 0) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return $this->response->setHeader('Content-Type', 'text/plain')->setBody('rollback');
            }
            return redirect()->back()->with('error', 'Invalid customer identifier.');
        }

        $activeCollections = $this->collectionModel->getActiveCollections();
        $saved = $this->customerWastageModel->saveCustomerWastage($customerId, $postData, $activeCollections);

        if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody($saved ? 'commit' : 'rollback');
        }

        if ($saved) {
            return redirect()->back()->with('success', 'Customer wastage values updated successfully.');
        }

        return redirect()->back()->with('error', 'Failed to update customer wastage.');
    }

    /**
     * Backward-compatible alias for insert_customer_wastage_data.
     */
    public function insert_customer_wastage_data()
    {
        return $this->insertCustomerWastageData();
    }

    /*
     * =========================================================================
     * WASTAGE / LOSS PERCENTAGE MASTER
     * =========================================================================
     */

    /**
     * Loss Percentage Master - Displays workshop process account loss percentages table.
     * Replicates legacy master.php:set_account_loss_percentage.
     */
    public function wastage()
    {
        $stageAccounts = $this->wastageModel->getStageLossAccounts();

        $data = [
            'title'         => 'Account Loss Percentage',
            'stageAccounts' => $stageAccounts,
        ];

        return view('master/wastage/index', $data);
    }

    /**
     * Create View for Customer Wastage Contract.
     */
    public function createWastage()
    {
        $customers   = $this->unifiedMasterService->getActiveCustomers('cust');
        $collections = $this->unifiedMasterService->getActiveCollections();

        $data = [
            'title'       => 'Add Customer Wastage Contract',
            'customers'   => $customers,
            'collections' => $collections,
        ];

        return view('master/wastage/create', $data);
    }

    /**
     * Store new Customer Wastage Contract in cust_wastage.
     */
    public function storeWastage()
    {
        $rules = [
            'customer_id'   => 'required|is_natural_no_zero',
            'collection_id' => 'required|is_natural_no_zero',
            'wastage'       => 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[100]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $customerId   = (int)$this->request->getPost('customer_id');
        $collectionId = (int)$this->request->getPost('collection_id');
        $wastage      = (float)$this->request->getPost('wastage');

        // Check if a contract already exists for this customer + collection pair
        $existing = $this->wastageModel->getWastageByCustomerAndCollection($customerId, $collectionId);
        if ($existing) {
            $this->wastageModel->update($existing->cw_id, ['wastage' => $wastage]);
            return redirect()->to(base_url('master/wastage'))->with('success', 'Existing wastage contract updated to ' . number_format($wastage, 3) . '%.');
        }

        $this->wastageModel->insert([
            'customer_id'   => $customerId,
            'collection_id' => $collectionId,
            'wastage'       => $wastage,
        ]);

        return redirect()->to(base_url('master/wastage'))->with('success', 'Customer wastage contract created successfully.');
    }

    /**
     * Edit View for Customer Wastage Contract.
     */
    public function editWastage(string $key)
    {
        $cwId = (int)$key;
        $wastage = $this->wastageModel->findWastageDetails($cwId);

        if (!$wastage) {
            return redirect()->to(base_url('master/wastage'))->with('error', 'Wastage contract record not found.');
        }

        $data = [
            'title'   => 'Edit Wastage Contract - ' . $wastage->cust_name,
            'wastage' => $wastage,
        ];

        return view('master/wastage/edit', $data);
    }

    /**
     * Update Customer Wastage Contract.
     */
    public function updateWastage(string $key)
    {
        $cwId = (int)$key;
        $wastage = $this->wastageModel->findWastageDetails($cwId);

        if (!$wastage) {
            return redirect()->to(base_url('master/wastage'))->with('error', 'Wastage contract record not found.');
        }

        $rules = [
            'wastage' => 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[100]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $newWastage = (float)$this->request->getPost('wastage');
        $this->wastageModel->update($cwId, ['wastage' => $newWastage]);

        return redirect()->to(base_url('master/wastage'))->with('success', 'Wastage contract updated successfully to ' . number_format($newWastage, 3) . '%.');
    }

    /**
     * Delete / Toggle Customer Wastage Contract.
     */
    public function toggleWastageStatus(string $key)
    {
        $cwId = (int)$key;
        $wastage = $this->wastageModel->findWastageDetails($cwId);

        if (!$wastage) {
            return redirect()->to(base_url('master/wastage'))->with('error', 'Wastage contract record not found.');
        }

        $this->wastageModel->delete($cwId);

        return redirect()->to(base_url('master/wastage'))->with('success', 'Wastage rule removed for ' . $wastage->cust_name . ' (' . $wastage->collection_name . ').');
    }

    /**
     * Workshop Stage Loss Percentage view.
     * Routes: /master/loss_percentage, /master/loss-percentage, /master/set_account_loss_percentage
     */
    public function lossPercentage()
    {
        return $this->wastage();
    }

    /**
     * Backward-compatible alias for set_account_loss_percentage.
     */
    public function set_account_loss_percentage()
    {
        return $this->wastage();
    }

    /**
     * Bulk update workshop stage loss percentages (account.loss_percentage).
     * Replicates legacy master.php:insert_account_loss_percentage.
     * Returns 'true' or 'false' for AJAX compatibility.
     */
    public function updateLossPercentage()
    {
        $postData = $this->request->getPost();
        $updated = $this->wastageModel->updateStageLossPercentages($postData);

        if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody($updated ? 'true' : 'false');
        }

        if ($updated) {
            return redirect()->to(base_url('master/loss_percentage'))->with('success', 'Update Successfully!');
        }

        return redirect()->to(base_url('master/loss_percentage'))->with('error', 'Please Try Again!');
    }

    /**
     * Backward-compatible alias for insert_account_loss_percentage.
     */
    public function insert_account_loss_percentage()
    {
        return $this->updateLossPercentage();
    }

    // =========================================================================
    // REMARK MASTER MODULE
    // =========================================================================

    /**
     * Remark Master - Main list view with pagination, search, and status filter.
     */
    public function remark(): string
    {
        $search = trim((string)($this->request->getGet('q') ?? ''));
        $status = $this->request->getGet('status');

        $perPage      = 10;
        $remarks      = $this->remarkModel->getPaginatedRemarks($perPage, $search, $status);
        $pager        = $this->remarkModel->pager;
        $summaryStats = $this->remarkModel->getSummaryStats();

        $data = [
            'title'        => 'Remark Master',
            'remarks'      => $remarks,
            'pager'        => $pager,
            'search'       => $search,
            'status'       => $status,
            'summaryStats' => $summaryStats,
        ];

        return view('master/remark/index', $data);
    }

    /**
     * Backward-compatible alias for manage_remark.
     */
    public function manage_remark()
    {
        return $this->remark();
    }

    /**
     * Display Add Remark form.
     */
    public function createRemark(): string
    {
        $data = [
            'title'    => 'Add Remark',
            'formType' => 'Add',
        ];

        return view('master/remark/create', $data);
    }

    /**
     * Backward-compatible alias for add_remark.
     */
    public function add_remark()
    {
        return $this->createRemark();
    }

    /**
     * Store new remark record.
     * Supports both traditional redirect and AJAX response ('true'/'false').
     */
    public function storeRemark()
    {
        $postData = $this->request->getPost();
        if (empty($postData)) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
            }
            return redirect()->to(base_url('master/remark/create'))->with('error', 'No data submitted.');
        }

        $remarkText = trim((string)($postData['remark'] ?? ''));
        $status     = isset($postData['status']) ? (string)$postData['status'] : '1';

        if (empty($remarkText)) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
            }
            return redirect()->to(base_url('master/remark/create'))->with('error', 'Remark text is required.');
        }

        // CI3 capitalization standard: ucwords($input_data['remark'])
        $remarkText = ucwords($remarkText);

        // Check duplicate active remark (preserving legacy validation)
        if ($this->remarkModel->isRemarkExists($remarkText, null, true)) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
            }
            return redirect()->to(base_url('master/remark/create'))->with('error', 'Remark Already Existed !');
        }

        $record = [
            'remark' => $remarkText,
            'status' => $status,
        ];

        $insertedId = $this->remarkModel->insert($record);

        if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody($insertedId ? 'true' : 'false');
        }

        if ($insertedId) {
            return redirect()->to(base_url('master/remark'))->with('success', 'Remark Inserted Successfully!');
        }

        return redirect()->to(base_url('master/remark/create'))->with('error', 'Failed to insert remark.');
    }

    /**
     * Display Edit Remark form.
     */
    public function editRemark(string $id)
    {
        $remarkId = (int)$id;
        $remark   = $this->remarkModel->find($remarkId);

        if (!$remark) {
            return redirect()->to(base_url('master/remark'))->with('error', 'Remark record not found.');
        }

        $data = [
            'title'    => 'Edit Remark',
            'formType' => 'Edit',
            'remark'   => $remark,
        ];

        return view('master/remark/edit', $data);
    }

    /**
     * Backward-compatible alias for edit_remark.
     */
    public function edit_remark(string $id)
    {
        return $this->editRemark($id);
    }

    /**
     * Update remark record.
     * Supports both traditional redirect and AJAX response ('true'/'false').
     */
    public function updateRemark(string $id)
    {
        $remarkId = (int)$id;
        $existing = $this->remarkModel->find($remarkId);

        if (!$existing) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
            }
            return redirect()->to(base_url('master/remark'))->with('error', 'Remark record not found.');
        }

        $postData   = $this->request->getPost();
        $remarkText = trim((string)($postData['remark'] ?? $existing['remark']));
        $status     = isset($postData['status']) ? (string)$postData['status'] : $existing['status'];

        if (empty($remarkText)) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
            }
            return redirect()->to(base_url('master/remark/edit/' . $remarkId))->with('error', 'Remark text is required.');
        }

        $remarkText = ucwords($remarkText);

        // Check duplicate active remark excluding current record
        if ($this->remarkModel->isRemarkExists($remarkText, $remarkId, true)) {
            if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
                return $this->response->setHeader('Content-Type', 'text/plain')->setBody('false');
            }
            return redirect()->to(base_url('master/remark/edit/' . $remarkId))->with('error', 'Remark text already exists.');
        }

        $updated = $this->remarkModel->update($remarkId, [
            'remark' => $remarkText,
            'status' => $status,
        ]);

        if ($this->request->isAJAX() || $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return $this->response->setHeader('Content-Type', 'text/plain')->setBody($updated !== false ? 'true' : 'false');
        }

        if ($updated !== false) {
            return redirect()->to(base_url('master/remark'))->with('success', 'Update Successfully!');
        }

        return redirect()->to(base_url('master/remark/edit/' . $remarkId))->with('error', 'Failed to update remark.');
    }

    /**
     * Backward-compatible alias for insert_remark (handles both Add and Edit).
     */
    public function insert_remark()
    {
        $postData = $this->request->getPost();
        $formType = $postData['form_type'] ?? 'Add';

        if ($formType === 'Edit') {
            $remarkId = (string)($postData['remark_id'] ?? '');
            return $this->updateRemark($remarkId);
        }

        return $this->storeRemark();
    }

    /**
     * Safe soft toggle between Active (1) and Inactive (0) status.
     */
    public function toggleRemarkStatus(string $id)
    {
        $remarkId = (int)$id;
        $result   = $this->remarkModel->toggleRemarkStatus($remarkId);

        if ($result['success']) {
            return redirect()->to(base_url('master/remark'))->with('success', $result['message']);
        }

        return redirect()->to(base_url('master/remark'))->with('error', $result['message']);
    }
}

