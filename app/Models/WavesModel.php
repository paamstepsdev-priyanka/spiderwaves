<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * WavesModel
 * 
 * Handles database operations for the permanent customer financial gold ledger (table: waves).
 * Tracks MT, OR, and RK customer metal and cash movements.
 */
class WavesModel extends Model
{
    protected $table            = 'waves';
    protected $primaryKey       = 'in_out_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'private_key',
        'cust_id',
        'transactional_mode',
        'transactional_type',
        'net_weight',
        'gross_weight',
        'less_weight',
        'purity',
        'wastage',
        'collection_id',
        'amount',
        'remark',
        'approved_data',
        'approved_by_cust',
        'taken_by',
        'entry_date',
        'is_deleted',
    ];

    protected $useTimestamps = false;

    /**
     * Insert customer gold ledger transaction.
     * Compatible with legacy Waves_model::insert_in_out_data()
     * 
     * @param array $data
     * @return int Insert ID
     */
    public function insertInOutData(array $data): int
    {
        if (!isset($data['approved_data'])) {
            $data['approved_data'] = '1';
        }
        if (!isset($data['approved_by_cust'])) {
            $data['approved_by_cust'] = '0';
        }
        if (!isset($data['is_deleted'])) {
            $data['is_deleted'] = '0';
        }
        if (!isset($data['entry_date'])) {
            $data['entry_date'] = date('Y-m-d');
        }

        $this->insert($data);
        return (int) $this->getInsertID();
    }

    /**
     * Update customer gold ledger transaction by condition.
     * Compatible with legacy Waves_model::update_in_out_data()
     * 
     * @param array $data
     * @param array $condition
     * @return bool
     */
    public function updateInOutData(array $data, array $condition): bool
    {
        return (bool) $this->builder()->where($condition)->set($data)->update();
    }

    /**
     * Delete / Empty customer gold ledger transactions by condition.
     * Compatible with legacy Waves_model::delete_in_out_data()
     * 
     * @param array|null $condition
     * @return bool
     */
    public function deleteInOutData(?array $condition = null): bool
    {
        if ($condition) {
            return (bool) $this->builder()->where($condition)->delete();
        }
        return (bool) $this->builder()->emptyTable();
    }

    /**
     * Retrieve single wave record with customer details by private_key.
     * 
     * @param string $privateKey
     * @return array|null
     */
    public function getByPrivateKey(string $privateKey): ?array
    {
        return $this->select('waves.*, mst_customer.cust_name, mst_customer.type as cust_type, mst_customer.mobile_no,
            (waves.net_weight * waves.purity) / 100 as actual,
            (waves.net_weight * (waves.purity + waves.wastage)) / 100 as actual_or')
            ->join('mst_customer', 'waves.cust_id = mst_customer.cust_id', 'left')
            ->where('waves.private_key', $privateKey)
            ->first();
    }

    /**
     * Get transactions by customer id.
     * 
     * @param int $custId
     * @param int $limit
     * @return array
     */
    public function getCustomerTransactions(int $custId, int $limit = 50): array
    {
        return $this->where('cust_id', $custId)
            ->where('is_deleted', '0')
            ->orderBy('entry_date', 'DESC')
            ->orderBy('in_out_id', 'DESC')
            ->findAll($limit);
    }

    /**
     * Query balance totals grouped by mode & transaction type in a single efficient query.
     * Compatible with legacy Waves_model::get_balance_totals_by_mode().
     * 
     * @return array{mt_receipt: float, mt_issue: float, or_receipt: float, or_issue: float, rk_receipt: float, rk_issue: float}
     */
    public function getBalanceTotalsByMode(): array
    {
        $row = $this->db->query('
            SELECT
                COALESCE(SUM(CASE WHEN transactional_mode = "MT" AND transactional_type = "receipt" THEN (gross_weight * purity) / 100 ELSE 0 END), 0) AS mt_receipt,
                COALESCE(SUM(CASE WHEN transactional_mode = "MT" AND transactional_type = "issue"   THEN (gross_weight * purity) / 100 ELSE 0 END), 0) AS mt_issue,
                COALESCE(SUM(CASE WHEN transactional_mode = "OR" AND transactional_type = "receipt" THEN gross_weight ELSE 0 END), 0) AS or_receipt,
                COALESCE(SUM(CASE WHEN transactional_mode = "OR" AND transactional_type = "issue"   THEN gross_weight ELSE 0 END), 0) AS or_issue,
                COALESCE(SUM(CASE WHEN transactional_mode = "RK" AND transactional_type = "receipt" THEN amount ELSE 0 END), 0) AS rk_receipt,
                COALESCE(SUM(CASE WHEN transactional_mode = "RK" AND transactional_type = "issue"   THEN amount ELSE 0 END), 0) AS rk_issue
            FROM waves
            WHERE is_deleted = "0"
        ')->getRowArray();

        return [
            'mt_receipt' => (float)($row['mt_receipt'] ?? 0),
            'mt_issue'   => (float)($row['mt_issue'] ?? 0),
            'or_receipt' => (float)($row['or_receipt'] ?? 0),
            'or_issue'   => (float)($row['or_issue'] ?? 0),
            'rk_receipt' => (float)($row['rk_receipt'] ?? 0),
            'rk_issue'   => (float)($row['rk_issue'] ?? 0),
        ];
    }

    /**
     * Retrieve ledger transactions with customer information.
     * Compatible with legacy Waves_model::get_in_out_data_for_ledger().
     * 
     * @param array|string|null $filter
     * @param int|null          $limit
     * @param int|null          $offset
     * @param string|null       $orderBy
     * @return array
     */
    public function getInOutDataForLedger($filter = null, ?int $limit = null, ?int $offset = null, ?string $orderBy = 'waves.in_out_id DESC'): array
    {
        $builder = $this->select('waves.*, mst_customer.cust_name, mst_customer.type as cust_type, mst_customer.mobile_no,
            (waves.net_weight * waves.purity) / 100 as actual,
            (waves.net_weight * (waves.purity + waves.wastage)) / 100 as actual_or')
            ->join('mst_customer', 'waves.cust_id = mst_customer.cust_id', 'left');

        if ($filter) {
            $builder->where($filter);
        }

        if ($orderBy) {
            $builder->orderBy($orderBy);
        }

        if ($limit !== null) {
            $builder->limit($limit, $offset ?? 0);
        }

        return $builder->findAll();
    }

    /**
     * Retrieve paginated ledger records with comprehensive search and filtering.
     * Optimized for high volume (80k+ records).
     * 
     * @param int   $perPage
     * @param array $filters
     * @return array{records: array, total: int, per_page: int, current_page: int, total_pages: int}
     */
    public function getPaginatedLedger(int $perPage = 25, array $filters = []): array
    {
        $builder = $this->select('
            waves.in_out_id,
            waves.private_key,
            waves.cust_id,
            waves.transactional_mode,
            waves.transactional_type,
            waves.gross_weight,
            waves.less_weight,
            waves.net_weight,
            waves.purity,
            waves.wastage,
            waves.collection_id,
            waves.amount,
            waves.remark,
            waves.approved_data,
            waves.approved_by_cust,
            waves.taken_by,
            waves.entry_date,
            waves.is_deleted,
            mst_customer.cust_name,
            mst_customer.type as cust_type
        ')->join('mst_customer', 'waves.cust_id = mst_customer.cust_id', 'left');

        // Always exclude soft-deleted records
        $builder->where('waves.is_deleted', '0');

        // Apply filters
        $this->applyFilters($builder, $filters);

        // Sorting: newest transactions first
        $builder->orderBy('waves.entry_date', 'DESC')
                ->orderBy('waves.in_out_id', 'DESC');

        // Pagination
        $page = max(1, (int)($filters['page'] ?? 1));
        $offset = ($page - 1) * $perPage;

        // Clone builder for counting total results
        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        $records = $builder->limit($perPage, $offset)->findAll();

        // Compute Fine Weight on the fly for each row based on transaction mode
        foreach ($records as &$rec) {
            $mode = $rec['transactional_mode'];
            $nw   = (float)$rec['net_weight'];
            $gw   = (float)$rec['gross_weight'];
            $p    = (float)$rec['purity'];
            $wst  = (float)$rec['wastage'];

            // Fallback net weight to gross weight if net_weight is 0 but gross_weight > 0
            $effectiveWeight = ($nw > 0) ? $nw : $gw;

            if ($mode === 'MT') {
                $rec['fine_weight'] = round(($effectiveWeight * $p) / 100, 3);
            } elseif ($mode === 'OR') {
                $rec['fine_weight'] = round(($effectiveWeight * ($p + $wst)) / 100, 3);
            } else { // RK cash
                $rec['fine_weight'] = 0.000;
            }
        }
        unset($rec);

        $totalPages = (int) ceil($total / $perPage);

        return [
            'records'      => $records,
            'total'        => (int) $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => max(1, $totalPages),
        ];
    }

    /**
     * Compute summary statistics for filtered ledger dataset.
     * 
     * @param array $filters
     * @return array{total_records: int, receipt_metal_weight: float, issue_metal_weight: float, receipt_cash_amount: float, issue_cash_amount: float}
     */
    public function getLedgerStats(array $filters = []): array
    {
        $builder = $this->db->table($this->table)
            ->select('
                COUNT(*) as total_records,
                COALESCE(SUM(CASE WHEN transactional_type = "receipt" AND transactional_mode IN ("MT", "OR") THEN gross_weight ELSE 0 END), 0) as receipt_metal_weight,
                COALESCE(SUM(CASE WHEN transactional_type = "issue"   AND transactional_mode IN ("MT", "OR") THEN gross_weight ELSE 0 END), 0) as issue_metal_weight,
                COALESCE(SUM(CASE WHEN transactional_type = "receipt" AND transactional_mode = "RK" THEN amount ELSE 0 END), 0) as receipt_cash_amount,
                COALESCE(SUM(CASE WHEN transactional_type = "issue"   AND transactional_mode = "RK" THEN amount ELSE 0 END), 0) as issue_cash_amount
            ')
            ->join('mst_customer', 'waves.cust_id = mst_customer.cust_id', 'left')
            ->where('waves.is_deleted', '0');

        $this->applyFilters($builder, $filters);

        $row = $builder->get()->getRowArray();

        return [
            'total_records'        => (int)($row['total_records'] ?? 0),
            'receipt_metal_weight' => (float)($row['receipt_metal_weight'] ?? 0),
            'issue_metal_weight'   => (float)($row['issue_metal_weight'] ?? 0),
            'receipt_cash_amount'  => (float)($row['receipt_cash_amount'] ?? 0),
            'issue_cash_amount'    => (float)($row['issue_cash_amount'] ?? 0),
        ];
    }

    /**
     * Shared filter applicator across query builder instances.
     * 
     * @param mixed $builder
     * @param array $filters
     */
    private function applyFilters(&$builder, array $filters): void
    {
        // 1. Keyword search (remark, customer name, in_out_id, private_key)
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('waves.remark', $search)
                ->orLike('mst_customer.cust_name', $search)
                ->orLike('waves.in_out_id', $search)
                ->orLike('waves.private_key', $search)
                ->groupEnd();
        }

        // 2. Customer ID
        if (!empty($filters['cust_id'])) {
            $builder->where('waves.cust_id', (int)$filters['cust_id']);
        }

        // 3. Transactional Mode (MT, OR, RK)
        if (!empty($filters['mode']) && in_array(strtoupper($filters['mode']), ['MT', 'OR', 'RK'], true)) {
            $builder->where('waves.transactional_mode', strtoupper($filters['mode']));
        }

        // 4. Transactional Type (receipt, issue)
        if (!empty($filters['type']) && in_array(strtolower($filters['type']), ['receipt', 'issue'], true)) {
            $builder->where('waves.transactional_type', strtolower($filters['type']));
        }

        // 5. From Date
        if (!empty($filters['from_date'])) {
            $builder->where('waves.entry_date >=', $filters['from_date']);
        }

        // 6. To Date
        if (!empty($filters['to_date'])) {
            $builder->where('waves.entry_date <=', $filters['to_date']);
        }

        // 7. Status (approved_data)
        if (isset($filters['approved_data']) && $filters['approved_data'] !== '') {
            $builder->where('waves.approved_data', (string)$filters['approved_data']);
        }
    }

    // ==============================================================
    // Legacy Parity Helpers (matching legacy Waves_model methods)
    // ==============================================================

    public function getMTReceiptClosingBalance(?int $custId = null, ?int $userId = null): float
    {
        $builder = $this->select('SUM((gross_weight * purity) / 100) as receipt_total')
            ->where('transactional_mode', 'MT')
            ->where('transactional_type', 'receipt')
            ->where('approved_data', '1')
            ->where('is_deleted', '0');

        if ($custId) {
            $builder->where('cust_id', $custId);
        }
        if ($userId) {
            $builder->where('taken_by', $userId);
        }

        $res = $builder->first();
        return (float)($res['receipt_total'] ?? 0);
    }

    public function getMTIssueClosingBalance(int $custId, ?int $userId = null): float
    {
        $builder = $this->select('SUM((gross_weight * purity) / 100) as issue_total')
            ->where('cust_id', $custId)
            ->where('transactional_mode', 'MT')
            ->where('transactional_type', 'issue')
            ->where('approved_data', '1')
            ->where('is_deleted', '0');

        if ($userId) {
            $builder->where('taken_by', $userId);
        }

        $res = $builder->first();
        return (float)($res['issue_total'] ?? 0);
    }

    public function getORReceiptClosingBalance(?int $custId = null, ?int $userId = null): float
    {
        $builder = $this->select('SUM((gross_weight * (purity + wastage)) / 100) as receipt_total')
            ->where('transactional_mode', 'OR')
            ->where('transactional_type', 'receipt')
            ->where('approved_data', '1')
            ->where('is_deleted', '0');

        if ($custId) {
            $builder->where('cust_id', $custId);
        }
        if ($userId) {
            $builder->where('taken_by', $userId);
        }

        $res = $builder->first();
        return (float)($res['receipt_total'] ?? 0);
    }

    public function getORIssueClosingBalance(int $custId, ?int $userId = null): float
    {
        $builder = $this->select('SUM((gross_weight * (purity + wastage)) / 100) as issue_total')
            ->where('cust_id', $custId)
            ->where('transactional_mode', 'OR')
            ->where('transactional_type', 'issue')
            ->where('approved_data', '1')
            ->where('is_deleted', '0');

        if ($userId) {
            $builder->where('taken_by', $userId);
        }

        $res = $builder->first();
        return (float)($res['issue_total'] ?? 0);
    }

    public function getRKReceiptClosingBalance(int $custId): float
    {
        $res = $this->select('SUM(amount) as receipt_total')
            ->where('cust_id', $custId)
            ->where('transactional_mode', 'RK')
            ->where('transactional_type', 'receipt')
            ->where('approved_data', '1')
            ->where('is_deleted', '0')
            ->first();

        return (float)($res['receipt_total'] ?? 0);
    }

    public function getRKIssueClosingBalance(int $custId): float
    {
        $res = $this->select('SUM(amount) as issue_total')
            ->where('cust_id', $custId)
            ->where('transactional_mode', 'RK')
            ->where('transactional_type', 'issue')
            ->where('approved_data', '1')
            ->where('is_deleted', '0')
            ->first();

        return (float)($res['issue_total'] ?? 0);
    }

    public function getMTReceiptTotalBalance(): float
    {
        $res = $this->select('SUM((gross_weight * purity) / 100) as receipt_total')
            ->where('transactional_mode', 'MT')
            ->where('transactional_type', 'receipt')
            ->where('is_deleted', '0')
            ->first();

        return (float)($res['receipt_total'] ?? 0);
    }

    public function getMTIssueTotalBalance(): float
    {
        $res = $this->select('SUM((gross_weight * purity) / 100) as issue_total')
            ->where('transactional_mode', 'MT')
            ->where('transactional_type', 'issue')
            ->where('is_deleted', '0')
            ->first();

        return (float)($res['issue_total'] ?? 0);
    }

    public function getORReceiptTotalBalance(): float
    {
        $res = $this->select('SUM(gross_weight) as receipt_total')
            ->where('transactional_mode', 'OR')
            ->where('transactional_type', 'receipt')
            ->where('is_deleted', '0')
            ->first();

        return (float)($res['receipt_total'] ?? 0);
    }

    public function getORIssueTotalBalance(): float
    {
        $res = $this->select('SUM(gross_weight) as issue_total')
            ->where('transactional_mode', 'OR')
            ->where('transactional_type', 'issue')
            ->where('is_deleted', '0')
            ->first();

        return (float)($res['issue_total'] ?? 0);
    }

    public function getRKReceiptTotalBalance(): float
    {
        $res = $this->select('SUM(amount) as receipt_total')
            ->where('transactional_mode', 'RK')
            ->where('transactional_type', 'receipt')
            ->where('is_deleted', '0')
            ->first();

        return (float)($res['receipt_total'] ?? 0);
    }

    public function getRKIssueTotalBalance(): float
    {
        $res = $this->select('SUM(amount) as issue_total')
            ->where('transactional_mode', 'RK')
            ->where('transactional_type', 'issue')
            ->where('is_deleted', '0')
            ->first();

        return (float)($res['issue_total'] ?? 0);
    }
}
