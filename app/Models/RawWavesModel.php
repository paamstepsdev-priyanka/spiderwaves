<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * RawWavesModel
 * 
 * Handles persistence and queries for the Raw Waves staging table (raw_waves).
 * Manages counter receipts, issues, scale verification data, and pending queue workflows.
 */
class RawWavesModel extends Model
{
    protected $table            = 'raw_waves';
    protected $primaryKey       = 'in_out_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'private_key',
        'from_id',
        'to_id',
        'verification_code',
        'user_by',
        'cust_id',
        'delivery_by',
        'date',
        'time',
        'transactional_mode',
        'transactional_type',
        'packet_weight',
        'net_weight',
        'gross_weight',
        'less_weight',
        'purity',
        'wastage',
        'collection_id',
        'amount',
        'key1',
        'val1',
        'key2',
        'val2',
        'key3',
        'val3',
        'key4',
        'val4',
        'remark',
        'approved_data',
        'approved_by',
        'approved_date',
        'approved_time',
        'is_deleted',
        'created_at',
        'approved_at',
    ];

    protected $useTimestamps = false;

    /**
     * Count pending raw waves awaiting verification.
     * 
     * @param string|null $location Current user branch location
     * @param string|null $accessLevel Current user access level
     * @return int
     */
    public function getPendingCount(?string $location = null, ?string $accessLevel = null): int
    {
        $builder = $this->db->table($this->table)
            ->where('raw_waves.approved_data', '0')
            ->where('raw_waves.is_deleted', '0');

        $isAdminOrSupervisor = in_array($accessLevel, ['admin', 'superadmin', 'process_manager'], true);

        if (!$isAdminOrSupervisor && !empty($location)) {
            $builder->join('user', 'raw_waves.user_by = user.user_id')
                ->where('user.location !=', $location);
        }

        return (int) $builder->countAllResults();
    }

    /**
     * Get paginated pending raw waves queue for verification and management.
     * 
     * @param int         $perPage
     * @param string|null $search
     * @param string|null $mode
     * @param string|null $type
     * @param string|null $fromDate
     * @param string|null $toDate
     * @param string|null $location
     * @param string|null $accessLevel
     * @return array [records: array, pager: \CodeIgniter\Pager\PagerInterface, total: int]
     */
    public function getPaginatedQueue(
        int $perPage = 15,
        ?string $search = null,
        ?string $mode = null,
        ?string $type = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $location = null,
        ?string $accessLevel = null
    ): array {
        $builder = $this->select('raw_waves.*, user.username as agent_name, user.location as agent_location, mst_customer.cust_name, mst_collection.collection_name')
            ->join('user', 'raw_waves.user_by = user.user_id', 'left')
            ->join('mst_customer', 'raw_waves.cust_id = mst_customer.cust_id', 'left')
            ->join('mst_collection', 'raw_waves.collection_id = mst_collection.collection_id', 'left')
            ->where('raw_waves.is_deleted', '0');

        $isAdminOrSupervisor = in_array($accessLevel, ['admin', 'superadmin', 'process_manager'], true);

        if (!$isAdminOrSupervisor) {
            // Non-admin queue shows unapproved records from other locations for independent verification
            $builder->where('raw_waves.approved_data', '0');
            if (!empty($location)) {
                $builder->where('user.location !=', $location);
            }
        }

        if (!empty($search)) {
            $search = trim($search);
            $builder->groupStart()
                ->like('raw_waves.verification_code', $search)
                ->orLike('mst_customer.cust_name', $search)
                ->orLike('raw_waves.delivery_by', $search)
                ->orLike('raw_waves.remark', $search)
            ->groupEnd();
        }

        if (!empty($mode) && in_array(strtoupper($mode), ['MT', 'OR', 'RK'], true)) {
            $builder->where('raw_waves.transactional_mode', strtoupper($mode));
        }

        if (!empty($type) && in_array(strtolower($type), ['receipt', 'issue'], true)) {
            $builder->where('raw_waves.transactional_type', strtolower($type));
        }

        if (!empty($fromDate)) {
            $builder->where('raw_waves.date >=', $fromDate);
        }

        if (!empty($toDate)) {
            $builder->where('raw_waves.date <=', $toDate);
        }

        $records = $builder->orderBy('raw_waves.date', 'DESC')
            ->orderBy('raw_waves.time', 'DESC')
            ->orderBy('raw_waves.in_out_id', 'DESC')
            ->paginate($perPage);

        return [
            'records' => $records,
            'pager'   => $this->pager,
            'total'   => $this->pager ? $this->pager->getTotal() : count($records),
        ];
    }

    /**
     * Find a pending raw wave record by its 5-digit verification code.
     * 
     * @param string      $code
     * @param string|null $location Current user location for verification check
     * @param string|null $accessLevel Current user access level
     * @return array|null
     */
    public function getPendingRawWaveByCode(string $code, ?string $location = null, ?string $accessLevel = null): ?array
    {
        $builder = $this->select('raw_waves.*, user.username as agent_name, user.location as agent_location, mst_customer.cust_name, mst_collection.collection_name')
            ->join('user', 'raw_waves.user_by = user.user_id', 'left')
            ->join('mst_customer', 'raw_waves.cust_id = mst_customer.cust_id', 'left')
            ->join('mst_collection', 'raw_waves.collection_id = mst_collection.collection_id', 'left')
            ->where('raw_waves.verification_code', trim($code))
            ->where('raw_waves.is_deleted', '0');

        return $builder->first();
    }

    /**
     * Find raw wave record by private_key (32-char GUID).
     * 
     * @param string $privateKey
     * @return array|null
     */
    public function getPendingRawWaveByPrivateKey(string $privateKey): ?array
    {
        return $this->select('raw_waves.*, user.username as agent_name, user.location as agent_location, mst_customer.cust_name, mst_collection.collection_name')
            ->join('user', 'raw_waves.user_by = user.user_id', 'left')
            ->join('mst_customer', 'raw_waves.cust_id = mst_customer.cust_id', 'left')
            ->join('mst_collection', 'raw_waves.collection_id = mst_collection.collection_id', 'left')
            ->where('raw_waves.private_key', trim($privateKey))
            ->where('raw_waves.is_deleted', '0')
            ->first();
    }

    /**
     * Find raw wave record with all join details by in_out_id.
     * 
     * @param int $inOutId
     * @return array|null
     */
    public function getWithDetails(int $inOutId): ?array
    {
        return $this->select('raw_waves.*, user.username as agent_name, mst_customer.cust_name, mst_collection.collection_name')
            ->join('user', 'raw_waves.user_by = user.user_id', 'left')
            ->join('mst_customer', 'raw_waves.cust_id = mst_customer.cust_id', 'left')
            ->join('mst_collection', 'raw_waves.collection_id = mst_collection.collection_id', 'left')
            ->where('raw_waves.in_out_id', $inOutId)
            ->first();
    }

    /**
     * Insert raw wave data (compat with legacy model).
     * 
     * @param array $data
     * @return int Insert ID
     */
    public function insertInOutData(array $data): int
    {
        $this->insert($data);
        return (int) $this->getInsertID();
    }

    /**
     * Update raw wave data by condition array.
     * 
     * @param array $data
     * @param array $condition
     * @return bool
     */
    public function updateInOutData(array $data, array $condition): bool
    {
        return (bool) $this->set($data)->where($condition)->update();
    }
}
