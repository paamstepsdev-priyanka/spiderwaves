<?php

namespace App\Controllers;

use App\Models\CollectionModel;
use App\Models\CustomerMappingModel;
use App\Models\CustomerModel;
use App\Models\CustomerWastageModel;
use App\Models\FirmModel;
use App\Models\ProcessWavesModel;
use App\Models\RawWavesModel;
use App\Models\RemarkModel;
use App\Models\WavesModel;
use App\Services\LedgerService;
use App\Services\UnifiedMasterService;
use App\Services\WastageService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * RawWavesController
 * 
 * CodeIgniter 4 Controller for the Raw Waves Module.
 * Preserves 100% of legacy SpiderWaves business logic:
 * - Counter Inward Receipt and Outward Issue data entry (MT, OR, RK modes)
 * - Dynamic collection-specific customer wastage binding
 * - Voucher & Barcode/QR generation with unique 5-digit verification codes
 * - Physical scale re-weigh verification (<= 30 mg / 0.030g tolerance)
 * - Atomic Dual Ledger Commit (waves ledger + process_waves Locker Account 1)
 * - Soft-delete archival (is_deleted = '1')
 * - Role-based authorization & location verification rules
 */
class RawWavesController extends BaseController
{
    protected RawWavesModel $rawWavesModel;
    protected WavesModel $wavesModel;
    protected ProcessWavesModel $processWavesModel;
    protected CustomerModel $customerModel;
    protected CustomerMappingModel $customerMappingModel;
    protected CollectionModel $collectionModel;
    protected RemarkModel $remarkModel;
    protected FirmModel $firmModel;
    protected LedgerService $ledgerService;
    protected UnifiedMasterService $unifiedMasterService;
    protected WastageService $wastageService;
    protected $db;

    public function __construct()
    {
        $this->rawWavesModel        = new RawWavesModel();
        $this->wavesModel           = new WavesModel();
        $this->processWavesModel    = new ProcessWavesModel();
        $this->customerModel        = new CustomerModel();
        $this->customerMappingModel = new CustomerMappingModel();
        $this->collectionModel      = new CollectionModel();
        $this->remarkModel          = new RemarkModel();
        $this->firmModel            = new FirmModel();
        $this->ledgerService        = new LedgerService();
        $this->unifiedMasterService = new UnifiedMasterService();
        $this->wastageService       = new WastageService();
        $this->db                   = \Config\Database::connect();
    }

    /**
     * Entry Screen for Raw Waves (Inward / Outward / Transfer).
     * 
     * @param string|null $processWavesPrivateKey
     * @return string
     */
    public function index(?string $processWavesPrivateKey = null)
    {
        $session = session()->get('spider') ?? [];
        $accessLevel = $session['access_level'] ?? '';
        $processWavesPrivateKey = $processWavesPrivateKey ?: $this->request->getGet('private_key');

        // Security: Role locker can only access Raw Waves for batch transfer to customer
        if ($accessLevel === 'locker' && empty($processWavesPrivateKey)) {
            return redirect()->to(base_url('locker'));
        }

        $data = [
            'process_waves_private_key' => $processWavesPrivateKey,
            'form_type'                 => 'Add',
            'gross_weight'              => '',
            'mapped_cust_id'            => '',
            'cust_name'                 => '',
            'collection_name'           => '',
            'wastage'                   => '',
            'purity'                    => '',
            'selected_mapped_cust_data' => null,
            'waves_data'                => null,
        ];

        if (!empty($processWavesPrivateKey)) {
            $data['form_type'] = 'transfer_to_cust';
            $processWave = $this->processWavesModel->where('private_key', $processWavesPrivateKey)
                ->where('inner_transfer', '0')
                ->first();
            if (!$processWave) {
                $processWave = $this->processWavesModel->where('private_key', $processWavesPrivateKey)->first();
            }

            if ($processWave) {
                $data['gross_weight']   = $processWave['gross_weight'];
                $data['mapped_cust_id'] = $processWave['mapped_cust_id'];

                $mappedCust = $this->unifiedMasterService->findMappingById((int) $data['mapped_cust_id']);
                if ($mappedCust) {
                    $data['selected_mapped_cust_data'] = $mappedCust;
                    $data['purity']                    = !empty($mappedCust['purity']) ? (float)$mappedCust['purity'] : (!empty($processWave['purity']) ? (float)$processWave['purity'] : '');

                    $data['cust_name'] = $mappedCust['cust_name'] ?? '';
                    if (empty($data['cust_name']) && !empty($mappedCust['cust_id'])) {
                        $cust = $this->unifiedMasterService->findCustomerById((int) $mappedCust['cust_id']);
                        $data['cust_name'] = $cust ? $cust['cust_name'] : '';
                    }

                    $data['collection_name'] = $mappedCust['collection_name'] ?? '';
                    if (empty($data['collection_name']) && !empty($mappedCust['collection_id'])) {
                        $coll = $this->collectionModel->find($mappedCust['collection_id']);
                        $data['collection_name'] = $coll ? $coll['collection_name'] : '';
                    }

                    $custWastageModel = new CustomerWastageModel();
                    $wRow = $custWastageModel->where('customer_id', $mappedCust['cust_id'])
                        ->where('collection_id', $mappedCust['collection_id'])
                        ->first();
                    $data['wastage'] = $wRow ? (string) $wRow['wastage'] : '0.000';
                }
            }
        }

        // Active mapped customers
        if (!empty($data['mapped_cust_id']) && $data['form_type'] === 'transfer_to_cust') {
            $singleMapping = $this->unifiedMasterService->findMappingById((int) $data['mapped_cust_id'], true);
            $data['mapped_cust_data'] = $singleMapping ? [$singleMapping] : $this->unifiedMasterService->getActiveMappings();
        } else {
            $data['mapped_cust_data'] = $this->unifiedMasterService->getActiveMappings();
        }

        // Active customers
        $data['cust_data'] = $this->unifiedMasterService->getActiveCustomers();

        // Active firms
        $data['firm_data'] = $this->firmModel->getActiveFirms();

        // Active remarks
        $data['remark_data'] = $this->remarkModel->getActiveRemarks();

        // Active collections
        $data['collection_data'] = $this->collectionModel->getActiveCollections();

        $data['title'] = 'Raw Waves Entry';
        return view('raw_waves/index', $data);
    }

    /**
     * Edit In/Out entry before approval.
     * 
     * @param string $privateKey
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function edit_in_out(string $privateKey)
    {
        $spider = session()->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';
        if (!in_array($accessLevel, ['admin', 'superadmin'], true)) {
            session()->setFlashdata('error', 'Unauthorized access to Edit Wave.');
            return redirect()->to(base_url('raw-waves/manage'));
        }

        $rawWave = $this->rawWavesModel->where('private_key', $privateKey)
            ->where('is_deleted', '0')
            ->first();

        if (!$rawWave) {
            session()->setFlashdata('error', 'Raw Wave entry not found or already verified.');
            return redirect()->to(base_url('raw-waves/manage'));
        }

        $mappedCust = null;
        $mappedCustId = null;

        // 1. Resolve mapped customer by to_id if stored
        if (!empty($rawWave['to_id'])) {
            $mappedCust = $this->unifiedMasterService->findMappingById((int) $rawWave['to_id'], true)
                ?? $this->unifiedMasterService->findMappingById((int) $rawWave['to_id']);
        }

        // 2. Fallback: resolve using cust_id, collection_id, and purity
        if (!$mappedCust && !empty($rawWave['cust_id'])) {
            $purityFilter = (isset($rawWave['purity']) && (float) $rawWave['purity'] > 0) ? (float) $rawWave['purity'] : null;
            $collFilter = !empty($rawWave['collection_id']) ? (int) $rawWave['collection_id'] : null;

            $matches = $this->unifiedMasterService->getActiveMappingsForCustomer((int) $rawWave['cust_id'], $purityFilter, $collFilter);
            if (empty($matches) && $collFilter) {
                $matches = $this->unifiedMasterService->getActiveMappingsForCustomer((int) $rawWave['cust_id'], null, $collFilter);
            }
            if (empty($matches)) {
                $matches = $this->unifiedMasterService->getActiveMappingsForCustomer((int) $rawWave['cust_id']);
            }
            $mappedCust = $matches[0] ?? null;
        }

        if ($mappedCust) {
            $mappedCustId = (int) $mappedCust['mapped_cust_id'];
        }

        $custName = '';
        $collectionName = '';
        $wastage = (string) $rawWave['wastage'];

        $collId = !empty($rawWave['collection_id']) ? $rawWave['collection_id'] : ($mappedCust['collection_id'] ?? null);
        if (!empty($collId)) {
            $coll = $this->collectionModel->find($collId);
            $collectionName = $coll ? $coll['collection_name'] : '';
        }

        $cId = !empty($rawWave['cust_id']) ? $rawWave['cust_id'] : ($mappedCust['cust_id'] ?? null);
        if (!empty($cId)) {
            $cust = $this->unifiedMasterService->findCustomerById((int) $cId);
            $custName = $cust ? $cust['cust_name'] : '';
        }

        $mappedCustData = $this->unifiedMasterService->getActiveMappings();

        if ($mappedCust && !in_array((string)$mappedCust['mapped_cust_id'], array_column($mappedCustData, 'mapped_cust_id'), true)) {
            $mappedCustData[] = $mappedCust;
        }

        $data = [
            'process_waves_private_key' => null,
            'form_type'                 => 'Edit',
            'private_key'               => $privateKey,
            'waves_data'                => [$rawWave],
            'raw_wave'                  => $rawWave,
            'gross_weight'              => $rawWave['gross_weight'],
            'mapped_cust_id'            => $mappedCustId ? (string) $mappedCustId : '',
            'cust_name'                 => $custName,
            'collection_name'           => $collectionName,
            'wastage'                   => $wastage,
            'purity'                    => number_format((float)$rawWave['purity'], 2, '.', ''),
            'mapped_cust_data'          => $mappedCustData,
            'cust_data'                 => $this->unifiedMasterService->getActiveCustomers(),
            'firm_data'                 => $this->firmModel->getActiveFirms(),
            'remark_data'               => $this->remarkModel->getActiveRemarks(),
            'collection_data'           => $this->collectionModel->getActiveCollections(),
            'title'                     => 'Edit Raw Wave Entry',
        ];

        return view('raw_waves/index', $data);
    }

    /**
     * Store or Update Raw Wave entry via AJAX.
     * Generates 32-char private_key and unique 5-digit verification_code.
     * 
     * @return ResponseInterface
     */
    public function insert_in_out(): ResponseInterface
    {
        $session = session()->get('spider');
        if (!$session) {
            return $this->response->setJSON(['result' => 'false', 'message' => 'Unauthorized session', 'waves_id' => 0]);
        }

        $userId   = (int) ($session['user_id'] ?? 0);
        $location = $session['location'] ?? '';
        $input    = $this->request->getPost();

        $formType = $input['form_type'] ?? 'Add';
        $mappedCustId = !empty($input['mapped_cust_id']) ? (int) $input['mapped_cust_id'] : null;

        // Resolve customer_id and collection_id from mapped_cust_id or directly
        $custId = null;
        $collectionId = !empty($input['collection_id']) ? (int) $input['collection_id'] : null;

        if ($mappedCustId) {
            $mappedCust = $this->unifiedMasterService->findMappingById((int) $mappedCustId);
            if ($mappedCust) {
                $custId = (int) $mappedCust['cust_id'];
                if (!$collectionId) {
                    $collectionId = (int) $mappedCust['collection_id'];
                }
            }
        } elseif (!empty($input['cust_id'])) {
            $custId = (int) $input['cust_id'];
        }

        if (!$custId) {
            return $this->response->setJSON([
                'result'   => 'false',
                'message'  => 'Invalid Customer or Mapped Customer selection.',
                'waves_id' => 0
            ]);
        }

        $transactionalMode = strtoupper(trim($input['transactional_mode'] ?? 'MT'));
        $transactionalType = strtolower(trim($input['transactional_type'] ?? 'receipt'));

        if ($formType === 'transfer_to_cust') {
            $transactionalType = 'issue';
        }

        if (!in_array($transactionalMode, ['MT', 'OR', 'RK'], true)) {
            $transactionalMode = 'MT';
        }
        if (!in_array($transactionalType, ['receipt', 'issue'], true)) {
            $transactionalType = 'receipt';
        }

        // Weight calculations
        $grossWeight  = isset($input['weight']) ? (float) $input['weight'] : (float) ($input['gross_weight'] ?? 0);
        $lessWeight   = (float) ($input['less_weight'] ?? 0);
        $packetWeight = (float) ($input['packet_weight'] ?? 0);

        if ($transactionalMode === 'RK') {
            $netWeight = 0.000;
            $purity    = 0.000;
            $wastage   = 0.000;
            $amount    = (float) ($input['amount'] ?? 0);
        } else {
            if ($formType === 'transfer_to_cust' && !empty($input['net_weight'])) {
                $netWeight = (float) $input['net_weight'];
            } else {
                $netWeight = $this->ledgerService->calculateNetWeight($grossWeight, $lessWeight);
            }
            $purity    = (float) ($input['purity'] ?? 0);
            $wastage   = (float) ($input['wastage'] ?? 0);
            $amount    = 0.00;

            // Enforce less weight check: (less_weight / gross_weight) * 100 <= 40%
            if ($transactionalType === 'receipt' && $grossWeight > 0) {
                $lessPercent = ($lessWeight / $grossWeight) * 100;
                if ($lessPercent > 40.0) {
                    return $this->response->setJSON([
                        'result'   => 'false',
                        'message'  => 'Less weight is too high (exceeds 40% of gross weight).',
                        'waves_id' => 0
                    ]);
                }
            }
        }

        // Remark handling
        $remark = trim($input['remark'] ?? '');
        if ($remark === 'other' || empty($remark)) {
            $remark = trim($input['other_remark'] ?? 'Other');
        }

        $deliveryBy = trim($input['delivery_by'] ?? '');
        if (empty($deliveryBy)) {
            $deliveryBy = $session['user'] ?? 'Counter';
        }

        // Key-value items
        $key1 = strtoupper(trim($input['key1'] ?? ''));
        $val1 = trim($input['val1'] ?? '');
        $key2 = strtoupper(trim($input['key2'] ?? ''));
        $val2 = trim($input['val2'] ?? '');
        $key3 = strtoupper(trim($input['key3'] ?? ''));
        $val3 = trim($input['val3'] ?? '');
        $key4 = strtoupper(trim($input['key4'] ?? ''));
        $val4 = trim($input['val4'] ?? '');

        $data = [
            'from_id'            => !empty($input['from_id']) ? (int) $input['from_id'] : null,
            'to_id'              => $mappedCustId ?: (!empty($input['to_id']) ? (int) $input['to_id'] : null),
            'user_by'            => $userId,
            'cust_id'            => $custId,
            'delivery_by'        => $deliveryBy,
            'date'               => date('Y-m-d'),
            'time'               => date('h:i A'),
            'transactional_mode' => $transactionalMode,
            'transactional_type' => $transactionalType,
            'packet_weight'      => $packetWeight,
            'net_weight'         => $netWeight,
            'gross_weight'       => $grossWeight,
            'less_weight'        => $lessWeight,
            'purity'             => $purity,
            'wastage'            => $wastage,
            'collection_id'      => $collectionId ?? 0,
            'amount'             => $amount,
            'key1'               => $key1 ?: null,
            'val1'               => $val1 ?: null,
            'key2'               => $key2 ?: null,
            'val2'               => $val2 ?: null,
            'key3'               => $key3 ?: null,
            'val3'               => $val3 ?: null,
            'key4'               => $key4 ?: null,
            'val4'               => $val4 ?: null,
            'remark'             => $remark,
            'approved_data'      => '0',
            'approved_by'        => null,
            'approved_date'      => null,
            'approved_time'      => null,
            'is_deleted'         => '0',
        ];

        $this->db->transBegin();

        try {
            if ($formType === 'Add' || $formType === 'transfer_to_cust') {
                $verificationCode = $this->ledgerService->generateVerificationCode();
                $privateKey       = $this->ledgerService->generateGuid();

                $data['private_key']       = $privateKey;
                $data['verification_code'] = $verificationCode;
                $data['created_at']        = date('Y-m-d H:i:s');

                if ($formType === 'transfer_to_cust') {
                    $stockData = [
                        'private_key'          => $this->ledgerService->generateGuid(),
                        'from_id'              => defined('stock_id') ? stock_id : 1,
                        'to_id'                => null,
                        'cust_id'              => $custId,
                        'mapped_cust_id'       => $mappedCustId,
                        'product'              => $transactionalMode,
                        'gross_weight'         => $netWeight,
                        'purity'               => $purity,
                        'pcs'                  => null,
                        'inner_transfer'       => '1',
                        'stock_inner_transfer' => '0',
                        'date'                 => date('Y-m-d H:i:s'),
                        'remark'               => $remark,
                        'user_by'              => $userId,
                    ];
                    $this->processWavesModel->insertProcessWavesData($stockData);

                    if (!empty($input['process_waves_private_key'])) {
                        $this->processWavesModel->updateProcessWavesData(
                            ['inner_transfer' => '1'],
                            ['private_key' => $input['process_waves_private_key']]
                        );
                    }
                }

                $wavesId = $this->rawWavesModel->insertInOutData($data);
            } elseif ($formType === 'Edit') {
                $privateKey = $input['private_key'] ?? '';
                if (empty($privateKey)) {
                    $this->db->transRollback();
                    return $this->response->setJSON(['result' => 'false', 'message' => 'Missing private key', 'waves_id' => 0]);
                }

                $existing = $this->rawWavesModel->where('private_key', $privateKey)->where('is_deleted', '0')->first();
                if (!$existing || $existing['approved_data'] === '1') {
                    $this->db->transRollback();
                    return $this->response->setJSON(['result' => 'false', 'message' => 'Record is already approved or not found.', 'waves_id' => 0]);
                }

                $this->rawWavesModel->updateInOutData($data, ['private_key' => $privateKey]);
                $wavesId = $existing['in_out_id'];
            } else {
                $this->db->transRollback();
                return $this->response->setJSON(['result' => 'false', 'message' => 'Unknown form type', 'waves_id' => 0]);
            }

            if ($this->db->transStatus() === false) {
                $dbErr = $this->db->error();
                $this->db->transRollback();
                return $this->response->setJSON(['result' => 'false', 'message' => 'Database transaction failed: ' . ($dbErr['message'] ?? ''), 'waves_id' => 0]);
            }

            $this->db->transCommit();

            return $this->response->setJSON([
                'result'            => 'true',
                'waves_id'          => $wavesId,
                'form_type'         => $formType,
                'private_key'       => $data['private_key'] ?? ($input['private_key'] ?? ''),
                'verification_code' => $data['verification_code'] ?? ($existing['verification_code'] ?? ''),
                'message'           => 'Raw Wave Inserted Successfully!'
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'result'   => 'false',
                'message'  => 'System error: ' . $e->getMessage(),
                'waves_id' => 0
            ]);
        }
    }

    /**
     * Modern Raw Waves Management & Pending Queue Dashboard.
     * 
     * @return string
     */
    public function manage()
    {
        return $this->manage_raw_waves();
    }

    /**
     * Backward compatible manage_raw_waves method.
     * 
     * @return string
     */
    public function manage_raw_waves()
    {
        $session     = session()->get('spider');
        $location    = $session['location'] ?? '';
        $accessLevel = $session['access_level'] ?? '';

        if ($accessLevel === 'locker') {
            return redirect()->to(base_url('locker'));
        }

        $perPage  = 15;
        $search   = $this->request->getGet('search') ?? $this->request->getGet('verification_code');
        $mode     = $this->request->getGet('mode');
        $type     = $this->request->getGet('type');
        $fromDate = $this->request->getGet('from_date');
        $toDate   = $this->request->getGet('to_date');

        $result = $this->rawWavesModel->getPaginatedQueue(
            $perPage,
            $search,
            $mode,
            $type,
            $fromDate,
            $toDate,
            $location,
            $accessLevel
        );

        $pendingCount = $this->rawWavesModel->getPendingCount($location, $accessLevel);

        $data = [
            'raw_waves_data' => $result['records'],
            'pager'          => $result['pager'],
            'total'          => $result['total'],
            'pending_count'  => $pendingCount,
            'search'         => $search,
            'mode'           => $mode,
            'type'           => $type,
            'from_date'      => $fromDate,
            'to_date'        => $toDate,
            'access_level'   => $accessLevel,
            'title'          => 'Manage Raw Waves Queue',
        ];

        return view('raw_waves/manage', $data);
    }

    /**
     * Verification Modal View loaded via AJAX for scale re-weigh verification.
     * 
     * @param string      $verificationCode
     * @param string|null $transactionType
     * @return string
     */
    public function view_voucher(string $verificationCode, ?string $transactionType = null)
    {
        $session     = session()->get('spider');
        $location    = $session['location'] ?? '';
        $accessLevel = $session['access_level'] ?? '';

        $voucherData = $this->rawWavesModel->getPendingRawWaveByCode($verificationCode, $location, $accessLevel);

        $authErrorMsg = '';
        $mappedCustData = [];

        if ($voucherData) {
            $custId = $voucherData['cust_id'];
            $cust = $this->unifiedMasterService->findCustomerById((int) $custId);
            $voucherData['customer_name'] = $cust ? $cust['cust_name'] : 'Unknown';

            if (!empty($voucherData['collection_id'])) {
                $coll = $this->collectionModel->find($voucherData['collection_id']);
                $voucherData['collection_name'] = $coll ? $coll['collection_name'] : null;
            } else {
                $voucherData['collection_name'] = null;
            }

            // Location check for non-admin: cannot verify own location inward/outward
            $isAdminOrSupervisor = in_array($accessLevel, ['admin', 'superadmin', 'process_manager'], true);
            if (!$isAdminOrSupervisor && !empty($voucherData['agent_location']) && $voucherData['agent_location'] === $location) {
                $authErrorMsg = 'You are not authorized to verify entries originating from your own location.';
                $voucherData = null;
            } else {
                if ($voucherData['transactional_type'] === 'receipt') {
                    if (!empty($voucherData['to_id'])) {
                        $singleMapping = $this->unifiedMasterService->findMappingById((int) $voucherData['to_id'], true);
                        $mappedCustData = $singleMapping ? [$singleMapping] : [];
                    }

                    if (empty($mappedCustData)) {
                        $purityFilter = (!empty($voucherData['purity']) && (float) $voucherData['purity'] > 0) ? (float) $voucherData['purity'] : null;
                        $collFilter = !empty($voucherData['collection_id']) ? (int) $voucherData['collection_id'] : null;
                        $mappedCustData = $this->unifiedMasterService->getActiveMappingsForCustomer((int) $custId, $purityFilter, $collFilter);
                    }

                    if (empty($mappedCustData)) {
                        $mappedCustData = $this->unifiedMasterService->getActiveMappingsForCustomer((int) $custId);
                    }
                }
            }
        } else {
            // Check if already verified or non-existent
            $anyRecord = $this->rawWavesModel->where('verification_code', trim($verificationCode))->first();
            if ($anyRecord) {
                if ($anyRecord['approved_data'] === '1' || $anyRecord['is_deleted'] === '1') {
                    $authErrorMsg = 'This voucher has already been verified and processed.';
                } else {
                    $authErrorMsg = 'You are not authorized to view this record.';
                }
            } else {
                $authErrorMsg = 'No entry found for the provided verification code.';
            }
        }

        $data = [
            'voucher_data'           => $voucherData,
            'authorization_error_msg' => $authErrorMsg,
            'mapped_cust_data'       => $mappedCustData,
            'verification_code'      => $verificationCode,
            'transaction_type'       => $voucherData ? $voucherData['transactional_type'] : $transactionType,
        ];

        return view('raw_waves/view_voucher', $data);
    }

    /**
     * Download or render printable voucher slip.
     * 
     * @param int $rawWavesId
     * @return string
     */
    public function download_voucher(int $rawWavesId)
    {
        $rawWave = $this->rawWavesModel->getWithDetails($rawWavesId);
        if (!$rawWave) {
            return '<div class="alert alert-danger">Voucher record not found.</div>';
        }

        $data = [
            'voucher_data'      => (object) $rawWave,
            'customer_name'     => $rawWave['cust_name'] ?? '',
            'collection_name'   => $rawWave['collection_name'] ?? '',
            'packet_weight'     => $rawWave['packet_weight'],
            'net_weight'        => $rawWave['net_weight'],
            'purity'            => number_format((float)$rawWave['purity'], 2, '.', ''),
            'delivery_by'       => $rawWave['delivery_by'],
            'verification_code' => $rawWave['verification_code'],
            'key1'              => $rawWave['key1'],
            'val1'              => $rawWave['val1'],
            'key2'              => $rawWave['key2'],
            'val2'              => $rawWave['val2'],
            'key3'              => $rawWave['key3'],
            'val3'              => $rawWave['val3'],
            'key4'              => $rawWave['key4'],
            'val4'              => $rawWave['val4'],
        ];

        return view('raw_waves/download_voucher', $data);
    }

    /**
     * Atomic Dual Ledger Verification & Approval Endpoint.
     * 
     * Executes:
     * 1. Scale tolerance verification (<= 30 mg / 0.030g)
     * 2. Insert into `waves` permanent financial ledger
     * 3. If transactional_mode != 'RK' AND transactional_type == 'receipt':
     *    Insert into `process_waves` workshop Locker (Account 1)
     * 4. Soft-delete archival in `raw_waves` (is_deleted = '1', approved_data = '1')
     * 
     * @return ResponseInterface
     */
    public function verified_raw_waves(): ResponseInterface
    {
        $session = session()->get('spider');
        if (!$session) {
            return $this->response->setJSON(['status' => 'false', 'message' => 'Session expired. Please login again.']);
        }

        $userId      = (int) ($session['user_id'] ?? 0);
        $location    = $session['location'] ?? '';
        $accessLevel = $session['access_level'] ?? '';

        $input               = $this->request->getPost();
        $rawWavesPrivateKey = trim($input['private_key'] ?? '');
        $mappedCustId        = !empty($input['mapped_cust_id']) ? (int) $input['mapped_cust_id'] : null;
        $scaleWeightInput    = isset($input['scale_weight']) && $input['scale_weight'] !== '' ? (float) $input['scale_weight'] : null;

        if (empty($rawWavesPrivateKey)) {
            return $this->response->setJSON(['status' => 'false', 'message' => 'Missing raw wave private key.']);
        }

        // Fetch raw wave record
        $rawWave = $this->rawWavesModel->getPendingRawWaveByPrivateKey($rawWavesPrivateKey);
        if (!$rawWave) {
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'Raw Wave entry not found or already verified.'
            ]);
        }

        if (!$mappedCustId && !empty($rawWave['to_id'])) {
            $mappedCustId = (int)$rawWave['to_id'];
        }

        if ($rawWave['approved_data'] === '1' || $rawWave['is_deleted'] === '1') {
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'This record has already been approved.'
            ]);
        }

        // Authorization check
        $isAdminOrSupervisor = in_array($accessLevel, ['admin', 'superadmin', 'process_manager'], true);
        if (!$isAdminOrSupervisor && !empty($rawWave['agent_location']) && $rawWave['agent_location'] === $location) {
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'Cross-location verification required. You cannot verify entries from your own location.'
            ]);
        }

        // Physical scale verification for receipts
        $grossWeight = (float) $rawWave['gross_weight'];
        $lessWeight  = (float) $rawWave['less_weight'];
        $netWeight   = (float) $rawWave['net_weight'];

        if ($rawWave['transactional_type'] === 'receipt' && $scaleWeightInput !== null) {
            $verification = $this->ledgerService->verifyScaleWeight($grossWeight, $scaleWeightInput);
            if (!$verification['valid']) {
                return $this->response->setJSON([
                    'status'    => 'false',
                    'message'   => $verification['message'],
                    'variance'  => $verification['variance'],
                    'tolerance' => $verification['tolerance']
                ]);
            }

            // Update to re-weighed scale reading
            $grossWeight = $scaleWeightInput;
            $netWeight   = $this->ledgerService->calculateNetWeight($grossWeight, $lessWeight);
        }

        // ATOMIC DATABASE TRANSACTION
        $this->db->transBegin();

        try {
            // 1. Insert permanent financial gold ledger record into `waves`
            $wavesPrivateKey = $this->ledgerService->generateGuid();
            $wavesData = [
                'private_key'        => $wavesPrivateKey,
                'cust_id'            => $rawWave['cust_id'],
                'transactional_mode' => $rawWave['transactional_mode'],
                'transactional_type' => $rawWave['transactional_type'],
                'gross_weight'       => $grossWeight,
                'less_weight'        => $lessWeight,
                'net_weight'         => $netWeight,
                'purity'             => $rawWave['purity'],
                'wastage'            => $rawWave['wastage'],
                'collection_id'      => $rawWave['collection_id'],
                'amount'             => $rawWave['amount'],
                'remark'             => $rawWave['remark'],
                'approved_data'      => '1',
                'approved_by_cust'   => '0',
                'taken_by'           => $rawWave['user_by'],
                'entry_date'         => $rawWave['date'],
                'is_deleted'         => '0',
            ];
            $wavesId = $this->wavesModel->insertInOutData($wavesData);

            // 2. Initialize workshop inventory in `process_waves` for non-RK receipts
            $stockWavesId = '';
            if ($rawWave['transactional_mode'] !== 'RK' && $rawWave['transactional_type'] === 'receipt') {
                $stockPrivateKey = $this->ledgerService->generateGuid();
                $stockData = [
                    'private_key'             => $stockPrivateKey,
                    'from_id'                 => null,
                    'to_id'                   => defined('stock_id') ? (int) stock_id : 1, // 1 = Locker
                    'cust_id'                 => $rawWave['cust_id'],
                    'mapped_cust_id'          => $mappedCustId,
                    'product'                 => $rawWave['transactional_mode'],
                    'gross_weight'            => $netWeight,
                    'purity'                  => $rawWave['purity'],
                    'net_loss'                => null,
                    'loss_account_id'         => null,
                    'pcs'                     => null,
                    'inner_transfer'          => '0',
                    'stock_inner_transfer'    => '0',
                    'further_to_process_waves_id' => null,
                    'locker_stock_check_done' => '0',
                    'user_by'                 => $userId,
                    'date'                    => date('Y-m-d H:i:s'),
                    'remark'                  => $rawWave['remark'],
                ];
                $stockWavesId = $this->processWavesModel->insertProcessWavesData($stockData);
            }

            // 3. Soft-delete archival of raw_waves record
            $updateRawWave = [
                'gross_weight'  => $grossWeight,
                'net_weight'    => $netWeight,
                'approved_data' => '1',
                'approved_by'   => $userId,
                'approved_date' => date('Y-m-d'),
                'approved_time' => date('h:i:s A'),
                'approved_at'   => date('Y-m-d H:i:s'),
                'is_deleted'    => '1',
            ];
            $this->rawWavesModel->updateInOutData($updateRawWave, ['private_key' => $rawWavesPrivateKey]);

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                return $this->response->setJSON([
                    'status'  => 'false',
                    'message' => 'Atomic dual-ledger transaction failed. Changes rolled back.'
                ]);
            }

            $this->db->transCommit();

            return $this->response->setJSON([
                'status'             => 'true',
                'to_id'              => '1',
                'process_waves_id'   => $stockWavesId,
                'waves_id'           => $wavesId,
                'date'               => date('Y-m-d'),
                'transactional_type' => $rawWave['transactional_type'],
                'message'            => 'Verification and Dual Ledger Commit completed successfully.'
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return $this->response->setJSON([
                'status'  => 'false',
                'message' => 'Fatal error during verification: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX endpoint: Get customer wastage data by mapped customer ID.
     * 
     * @param int $mappedCustomerId
     * @return ResponseInterface
     */
    public function get_customer_wastage_data_by_mapped_customer_id(int $mappedCustomerId): ResponseInterface
    {
        $mappedCust = $this->unifiedMasterService->findMappingById($mappedCustomerId);
        if (!$mappedCust) {
            return $this->response->setBody('0.000');
        }

        $custWastageModel = new CustomerWastageModel();
        $wRow = $custWastageModel->where('customer_id', $mappedCust['cust_id'])
            ->where('collection_id', $mappedCust['collection_id'])
            ->first();

        $wastage = $wRow ? (string) $wRow['wastage'] : '0.000';
        return $this->response->setBody($wastage);
    }
}
