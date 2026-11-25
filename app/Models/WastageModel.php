<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * WastageModel
 * 
 * Handles database operations for:
 * 1. Customer Wastage Contracts (table: cust_wastage)
 * 2. Workshop Stage Loss Percentages (table: account, field: loss_percentage)
 *
 * Adheres strictly to the existing schema and preserves legacy business rules.
 */
class WastageModel extends Model
{
    protected $table            = 'mst_cust_wastage';
    protected $primaryKey       = 'cw_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'customer_id',
        'collection_id',
        'wastage',
    ];

    // Dates
    protected $useTimestamps = false;

    // Validation
    protected $validationRules = [
        'customer_id'   => 'required|is_natural_no_zero',
        'collection_id' => 'required|is_natural_no_zero',
        'wastage'       => 'required|numeric|greater_than_equal_to[0]|less_than_equal_to[100]',
    ];

    protected $validationMessages = [
        'customer_id' => [
            'required' => 'Customer selection is required.',
            'is_natural_no_zero' => 'Valid Customer must be selected.',
        ],
        'collection_id' => [
            'required' => 'Collection selection is required.',
            'is_natural_no_zero' => 'Valid Collection must be selected.',
        ],
        'wastage' => [
            'required' => 'Wastage percentage is required.',
            'numeric' => 'Wastage percentage must be a valid number.',
            'greater_than_equal_to' => 'Wastage percentage cannot be negative.',
            'less_than_equal_to' => 'Wastage percentage cannot exceed 100%.',
        ],
    ];

    // All manufacturing process accounts from legacy constants.php:
    // array('2','3','4','5','6','7','8','9', '13', '14', '15', '16', '17', '20', '21', '22', '23')
    public const PROCESS_STAGE_IDS = [2, 3, 4, 5, 6, 7, 8, 9, 13, 14, 15, 16, 17, 20, 21, 22, 23];

    // =========================================================================
    // 1. CUSTOMER WASTAGE CONTRACTS (cust_wastage)
    // =========================================================================

    /**
     * Get paginated customer wastage records with search & filters.
     * Joins cust and collection tables to retrieve customer and collection details.
     *
     * @param string|null $search
     * @param int|null $customerId
     * @param int|null $collectionId
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function getFilteredWastages(?string $search = null, ?int $customerId = null, ?int $collectionId = null, int $perPage = 25, int $page = 1): array
    {
        $builder = $this->db->table($this->table . ' cw')
            ->select('cw.cw_id, cw.customer_id, cw.collection_id, cw.wastage, c.cust_name, c.private_key as cust_private_key, c.cust_status, col.collection_name, col.collection_status')
            ->join('mst_customer c', 'c.cust_id = cw.customer_id', 'inner')
            ->join('mst_collection col', 'col.collection_id = cw.collection_id', 'inner');

        if (!empty($customerId)) {
            $builder->where('cw.customer_id', $customerId);
        }

        if (!empty($collectionId)) {
            $builder->where('cw.collection_id', $collectionId);
        }

        if (!empty($search)) {
            $builder->groupStart()
                ->like('c.cust_name', $search)
                ->orLike('col.collection_name', $search)
                ->orLike('cw.wastage', $search)
                ->groupEnd();
        }

        // Count total matching
        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        // Fetch paginated rows
        $offset = ($page - 1) * $perPage;
        $builder->orderBy('cw.cw_id', 'DESC')
                ->limit($perPage, $offset);

        $records = $builder->get()->getResult();

        return [
            'data'    => $records,
            'total'   => $total,
            'perPage' => $perPage,
            'page'    => $page,
            'lastPage'=> (int) ceil($total / $perPage),
        ];
    }

    /**
     * Get aggregate statistics for Customer Wastage.
     *
     * @return array
     */
    public function getWastageStats(): array
    {
        $builder = $this->db->table($this->table);
        $totalRules = $builder->countAllResults();

        $stats = $this->db->table($this->table)
            ->select('AVG(wastage) as avg_wastage, MAX(wastage) as max_wastage, COUNT(DISTINCT customer_id) as unique_customers, COUNT(DISTINCT collection_id) as unique_collections')
            ->get()
            ->getRow();

        return [
            'total_rules'        => $totalRules,
            'avg_wastage'        => $stats ? round((float)$stats->avg_wastage, 3) : 0.000,
            'max_wastage'        => $stats ? round((float)$stats->max_wastage, 3) : 0.000,
            'unique_customers'   => $stats ? (int)$stats->unique_customers : 0,
            'unique_collections' => $stats ? (int)$stats->unique_collections : 0,
        ];
    }

    /**
     * Find single Customer Wastage record by cw_id with joined names.
     *
     * @param int $cwId
     * @return object|null
     */
    public function findWastageDetails(int $cwId): ?object
    {
        return $this->db->table($this->table . ' cw')
            ->select('cw.cw_id, cw.customer_id, cw.collection_id, cw.wastage, c.cust_name, c.private_key as cust_private_key, col.collection_name')
            ->join('mst_customer c', 'c.cust_id = cw.customer_id', 'inner')
            ->join('mst_collection col', 'col.collection_id = cw.collection_id', 'inner')
            ->where('cw.cw_id', $cwId)
            ->get()
            ->getRow();
    }

    /**
     * Get Wastage by Customer ID and Collection ID.
     *
     * @param int $customerId
     * @param int $collectionId
     * @return object|null
     */
    public function getWastageByCustomerAndCollection(int $customerId, int $collectionId): ?object
    {
        return $this->where('customer_id', $customerId)
                    ->where('collection_id', $collectionId)
                    ->first();
    }

    /**
     * Get all wastage mappings for a specific customer ID.
     *
     * @param int $customerId
     * @return array
     */
    public function getCustomerWastageMappingData(array $filter = []): array
    {
        $builder = $this->builder();
        if (!empty($filter)) {
            $builder->where($filter);
        }
        return $builder->get()->getResult();
    }

    /**
     * Delete customer wastage rules by customer ID (used before bulk replacing in Customer Edit).
     *
     * @param int $customerId
     * @return bool
     */
    public function deleteCustomerWastageDataByCustomerId(int $customerId): bool
    {
        return (bool) $this->where('customer_id', $customerId)->delete();
    }

    /**
     * Insert customer wastage mapping data.
     *
     * @param array $data
     * @return int|string|false
     */
    public function insertCustomerWastageData(array $data)
    {
        return $this->insert($data, true);
    }

    /**
     * Get wastage percentage for a mapped customer ID.
     * Core lookup used in Raw Waves OR mode transactions.
     *
     * @param int $mappedCustId
     * @return float
     */
    public function getWastageByMappedCustomer(int $mappedCustId): float
    {
        $mapping = $this->db->table('mst_cust_mapping')
            ->select('cust_id, collection_id')
            ->where('mapped_cust_id', $mappedCustId)
            ->where('mapped_cust_status', '1')
            ->get()
            ->getRow();

        if (!$mapping) {
            return 0.000;
        }

        $wastageRow = $this->where('customer_id', $mapping->cust_id)
            ->where('collection_id', $mapping->collection_id)
            ->first();

        return $wastageRow ? (float) $wastageRow->wastage : 0.000;
    }

    // =========================================================================
    // 2. WORKSHOP STAGE LOSS PERCENTAGES (account.loss_percentage)
    // =========================================================================

    /**
     * Get all manufacturing process stage accounts for Stage Loss configuration.
     * Preserves exact filtering from legacy master.php:set_account_loss_percentage:
     * $filter = "account_id IN(" . implode(', ', $account_ids_array) . ")";
     *
     * @return array
     */
    public function getStageLossAccounts(): array
    {
        return $this->db->table('mst_process')
            ->select('account_id, private_key, account_name, account_type, loss_type, loss_percentage, account_status')
            ->whereIn('account_id', self::PROCESS_STAGE_IDS)
            ->orderBy('account_id', 'ASC')
            ->get()
            ->getResult();
    }

    /**
     * Get stage loss percentage for a specific account.
     * Replaces master_model.php:get_account_loss_percentage($account_id).
     *
     * @param int $accountId
     * @return float
     */
    public function getAccountLossPercentage(int $accountId): float
    {
        $row = $this->db->table('mst_process')
            ->select('loss_percentage')
            ->where('account_id', $accountId)
            ->get()
            ->getRow();

        return $row ? (float) $row->loss_percentage : 0.00;
    }

    /**
     * Bulk update workshop stage loss percentages.
     * Matches legacy master.php:insert_account_loss_percentage:
     * Receives array of [private_key => loss_percentage].
     *
     * @param array $postData [private_key => float]
     * @return bool
     */
    public function updateStageLossPercentages(array $postData): bool
    {
        $updated = 0;
        foreach ($postData as $key => $percentage) {
            // Check if valid 32-char hex private key or valid account identifier
            if (!is_numeric($percentage) || $percentage < 0) {
                continue;
            }

            // Verify account exists
            $acc = $this->db->table('mst_process')
                ->where('private_key', $key)
                ->get()
                ->getRow();

            if ($acc) {
                $this->db->table('mst_process')
                    ->where('private_key', $key)
                    ->update(['loss_percentage' => (float) $percentage]);
                $updated++;
            }
        }

        return $updated > 0;
    }
}
