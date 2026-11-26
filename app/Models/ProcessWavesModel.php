<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ProcessWavesModel
 * 
 * Manages database operations for manufacturing workshop metal pipeline transfers (table: process_waves).
 * Handles:
 * - Locker physical vault receipts, issues, stock verification
 * - Departmental process receipts & issues (Casting, Filing, Setting, Polish, Packing, etc.)
 * - Inter-departmental transfer transactions and piece tracking
 * - SQL-level SUM aggregations for Fine MT, Gross MT, and Pcs
 */
class ProcessWavesModel extends Model
{
    protected $table            = 'process_waves';
    protected $primaryKey       = 'process_waves_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'private_key',
        'cust_id',
        'from_id',
        'to_id',
        'mapped_cust_id',
        'product',
        'gross_weight',
        'purity',
        'net_loss',
        'loss_account_id',
        'pcs',
        'inner_transfer',
        'stock_inner_transfer',
        'further_to_process_waves_id',
        'locker_stock_check_done',
        'user_by',
        'date',
        'remark',
    ];

    protected $useTimestamps = false;

    /**
     * Insert workshop process pipeline transfer record.
     * Compatible with legacy Process_waves_model::insert_process_waves_data()
     * 
     * @param array $data
     * @return int Insert ID
     */
    public function insertProcessWavesData(array $data): int
    {
        if (!isset($data['inner_transfer'])) {
            $data['inner_transfer'] = '0';
        }
        if (!isset($data['stock_inner_transfer'])) {
            $data['stock_inner_transfer'] = '0';
        }
        if (!isset($data['locker_stock_check_done'])) {
            $data['locker_stock_check_done'] = '0';
        }

        $this->insert($data);
        return (int) $this->getInsertID();
    }

    /**
     * Update process wave record by condition array or ID.
     * 
     * @param array $data
     * @param array $condition
     * @return bool
     */
    public function updateProcessWavesData(array $data, array $condition): bool
    {
        return (bool) $this->builder()->where($condition)->set($data)->update();
    }

    /**
     * Get process wave record by private key.
     */
    public function getByPrivateKey(string $privateKey): ?array
    {
        return $this->where('private_key', $privateKey)->first();
    }

    /**
     * Get process wave record by ID.
     */
    public function getById(int $processWavesId): ?array
    {
        return $this->where('process_waves_id', $processWavesId)->first();
    }

    /**
     * Join both legacy SpiderWaves (mst_*) and UCC (ucc_*) customer/mapping tables.
     */
    protected function applyCustomerSelectAndJoins($builder, string $extraSelect = ''): void
    {
        $select = 'pw.*, ' .
            'COALESCE(CONVERT(cm.mapped_cust_name USING utf8mb4) COLLATE utf8mb4_general_ci, ucm.mapped_customer_name) AS mapped_cust_name, ' .
            'COALESCE(CONVERT(c.cust_name USING utf8mb4) COLLATE utf8mb4_general_ci, uc.customer_name) AS cust_name';
        if ($extraSelect !== '') {
            $select .= ', ' . $extraSelect;
        }
        $builder->select($select);
        $builder->join('mst_cust_mapping cm', 'cm.mapped_cust_id = pw.mapped_cust_id', 'left');
        $builder->join('ucc_mapped_customer ucm', 'ucm.mapped_customer_id = pw.mapped_cust_id AND ucm.deleted_at IS NULL', 'left');
        $builder->join('mst_customer c', 'c.cust_id = pw.cust_id', 'left');
        $builder->join('ucc_customer uc', 'uc.customer_id = pw.cust_id AND uc.deleted_at IS NULL', 'left');
    }

    /**
     * Get Locker Receipt stock (incoming metal awaiting transfer into workshop).
     * 
     * Legacy condition:
     * to_id = 1 AND (date >= '$startDate' OR (inner_transfer = '0' OR stock_inner_transfer = '0')) AND further_to_process_waves_id IS NULL AND ABS(gross_weight) > 0.100
     */
    public function getLockerReceiptData(string $startDate): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('process_waves pw');
        $this->applyCustomerSelectAndJoins($builder, 'a_from.account_name as from_name');
        $builder->join('mst_process a_from', 'a_from.account_id = pw.from_id', 'left');
        $builder->where('pw.to_id', 1);
        $builder->where("(pw.date >= '{$startDate}' OR (pw.inner_transfer = '0' OR pw.stock_inner_transfer = '0'))");
        $builder->where('pw.further_to_process_waves_id IS NULL', null, false);
        $builder->where('ABS(pw.gross_weight) >', 0.100);
        $builder->orderBy('pw.date', 'DESC')->orderBy('pw.process_waves_id', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * Get Locker Issue stock (metal transferred out of Locker to Casting or other stages).
     * 
     * Legacy condition:
     * from_id = 1 AND date >= '$startDate' AND further_to_process_waves_id IS NULL AND ABS(gross_weight) > 0.100
     */
    public function getLockerIssueData(string $startDate): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('process_waves pw');
        $this->applyCustomerSelectAndJoins($builder, 'a_to.account_name as to_name');
        $builder->join('mst_process a_to', 'a_to.account_id = pw.to_id', 'left');
        $builder->where('pw.from_id', 1);
        $builder->where('pw.date >=', $startDate);
        $builder->where('pw.further_to_process_waves_id IS NULL', null, false);
        $builder->where('ABS(pw.gross_weight) >', 0.100);
        $builder->orderBy('pw.date', 'DESC')->orderBy('pw.process_waves_id', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * Get Locker Unverified Stock for physical stock barcode verification.
     * 
     * Legacy condition:
     * to_id = 1 AND (date >= '$startDate' OR stock_inner_transfer = '0') AND inner_transfer = '0' AND gross_weight > 0.001 AND locker_stock_check_done = '0' AND further_to_process_waves_id IS NULL
     */
    public function getLockerUnverifiedStock(string $startDate): array
    {
        $db = $this->db ?? \Config\Database::connect();
        $builder = $db->table('process_waves pw');
        $this->applyCustomerSelectAndJoins($builder, 'a_from.account_name as from_name, a_loss.account_name as loss_account_name');
        $builder->join('mst_process a_from', 'a_from.account_id = pw.from_id', 'left');
        $builder->join('mst_process a_loss', 'a_loss.account_id = pw.loss_account_id', 'left');
        $builder->where('pw.to_id', 1);
        $builder->where("(pw.date >= '{$startDate}' OR pw.stock_inner_transfer = '0')");
        $builder->where('pw.inner_transfer', '0');
        $builder->where('ABS(pw.gross_weight) >', 0.100);
        $builder->where('pw.locker_stock_check_done', '0');
        $builder->where('pw.further_to_process_waves_id IS NULL', null, false);
        $builder->orderBy('pw.date', 'DESC')->orderBy('pw.process_waves_id', 'DESC');

        return $builder->get()->getResultArray();
    }

    /**
     * Mark a Locker stock batch as physically verified.
     */
    public function markLockerVerificationDone(int $processWavesId): bool
    {
        return (bool) $this->builder()
            ->where('process_waves_id', $processWavesId)
            ->set(['locker_stock_check_done' => '1'])
            ->update();
    }

    /**
     * Restart Locker physical verification process.
     * Resets locker_stock_check_done = '0' for all previously verified batches.
     */
    public function restartLockerVerification(): bool
    {
        return (bool) $this->builder()
            ->where('locker_stock_check_done', '1')
            ->set(['locker_stock_check_done' => '0'])
            ->update();
    }

    /**
     * Get Department Receipt stock for Process Account ledger.
     */
    public function getDepartmentReceiptData(int $accountId, string $startDate = '', ?int $fromAccountId = null, ?int $limit = null, int $offset = 0, bool $onlyPending = true): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('process_waves pw');
        $this->applyCustomerSelectAndJoins($builder, 'a_from.account_name as from_name, a_loss.account_name as loss_account_name');
        $builder->join('mst_process a_from', 'a_from.account_id = pw.from_id', 'left');
        $builder->join('mst_process a_loss', 'a_loss.account_id = pw.loss_account_id', 'left');

        if ($accountId === AccountModel::LOSS_ID) {
            $builder->where('pw.to_id', $accountId);
            if ($fromAccountId) {
                $builder->where('pw.from_id', $fromAccountId);
            }
            if ($onlyPending) {
                $builder->where('pw.further_to_process_waves_id IS NULL', null, false);
            }
        } elseif ($accountId === AccountModel::REFINING_ID) {
            $builder->where('pw.to_id', $accountId);
            if ($fromAccountId) {
                $builder->where('pw.from_id', $fromAccountId);
            }
        } elseif ($accountId === AccountModel::STOCK_ID) {
            $builder->where('pw.to_id', $accountId);
            $builder->where("(pw.date >= '{$startDate}' OR (pw.inner_transfer = '0' OR pw.stock_inner_transfer = '0'))");
            $builder->where('pw.further_to_process_waves_id IS NULL', null, false);
        } else {
            $builder->where('pw.to_id', $accountId);
            if (!empty($startDate)) {
                $builder->where('pw.date >=', $startDate);
            }
        }

        // Legacy CI2 filter: exclude dust entries with gross weight <= 0.100
        $builder->where('ABS(pw.gross_weight) >', 0.100);

        // Consistent descending order: newly created transfers appear first on top
        $builder->orderBy('pw.date', 'DESC')->orderBy('pw.process_waves_id', 'DESC');

        if ($limit !== null && $limit > 0) {
            $builder->limit($limit, $offset);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Get Department Issue stock for Process Account ledger.
     */
    public function getDepartmentIssueData(int $accountId, string $startDate = '', ?int $fromAccountId = null, ?int $limit = null, int $offset = 0): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('process_waves pw');
        $this->applyCustomerSelectAndJoins($builder, 'a_to.account_name as to_name, a_loss.account_name as loss_account_name');
        $builder->join('mst_process a_to', 'a_to.account_id = pw.to_id', 'left');
        $builder->join('mst_process a_loss', 'a_loss.account_id = pw.loss_account_id', 'left');

        if ($accountId === AccountModel::LOSS_ID) {
            $builder->where('pw.from_id', $accountId);
            if ($fromAccountId) {
                $builder->where('pw.loss_account_id', $fromAccountId);
            }
        } elseif ($accountId === AccountModel::REFINING_ID) {
            $builder->where('pw.from_id', $accountId);
            if ($fromAccountId) {
                $builder->where('pw.to_id', $fromAccountId);
            }
        } elseif ($accountId === AccountModel::STOCK_ID) {
            $builder->where('pw.from_id', $accountId);
            $builder->where("(pw.date >= '{$startDate}' OR pw.inner_transfer = '0')");
            $builder->where('pw.further_to_process_waves_id IS NULL', null, false);
        } else {
            $builder->where('pw.from_id', $accountId);
            if (!empty($startDate)) {
                $builder->where('pw.date >=', $startDate);
            }
        }

        // Legacy CI2 filter: exclude dust entries with gross weight <= 0.100
        $builder->where('ABS(pw.gross_weight) >', 0.100);

        // Consistent descending order: newly created transfers appear first on top
        $builder->orderBy('pw.date', 'DESC')->orderBy('pw.process_waves_id', 'DESC');

        if ($limit !== null && $limit > 0) {
            $builder->limit($limit, $offset);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Get total count of department receipt transactions matching criteria.
     */
    public function getDepartmentReceiptCount(int $accountId, string $startDate = '', ?int $fromAccountId = null, bool $onlyPending = true): int
    {
        $db = \Config\Database::connect();
        $builder = $db->table('process_waves pw');
        $builder->where('pw.to_id', $accountId);

        if ($accountId === AccountModel::LOSS_ID) {
            if ($fromAccountId) {
                $builder->where('pw.from_id', $fromAccountId);
            }
            if ($onlyPending) {
                $builder->where('pw.further_to_process_waves_id IS NULL', null, false);
            }
        } elseif ($accountId === AccountModel::REFINING_ID) {
            if ($fromAccountId) {
                $builder->where('pw.from_id', $fromAccountId);
            }
        } elseif ($accountId === AccountModel::STOCK_ID) {
            $builder->where("(pw.date >= '{$startDate}' OR (pw.inner_transfer = '0' OR pw.stock_inner_transfer = '0'))");
            $builder->where('pw.further_to_process_waves_id IS NULL', null, false);
        } else {
            if (!empty($startDate)) {
                $builder->where('pw.date >=', $startDate);
            }
        }

        $builder->where('ABS(pw.gross_weight) >', 0.100);
        return $builder->countAllResults();
    }

    /**
     * Get total count of department issue transactions matching criteria.
     */
    public function getDepartmentIssueCount(int $accountId, string $startDate = '', ?int $fromAccountId = null): int
    {
        $db = \Config\Database::connect();
        $builder = $db->table('process_waves pw');
        $builder->where('pw.from_id', $accountId);

        if ($accountId === AccountModel::LOSS_ID) {
            if ($fromAccountId) {
                $builder->where('pw.loss_account_id', $fromAccountId);
            }
        } elseif ($accountId === AccountModel::REFINING_ID) {
            if ($fromAccountId) {
                $builder->where('pw.to_id', $fromAccountId);
            }
        } elseif ($accountId === AccountModel::STOCK_ID) {
            $builder->where("(pw.date >= '{$startDate}' OR pw.inner_transfer = '0')");
            $builder->where('pw.further_to_process_waves_id IS NULL', null, false);
        } else {
            if (!empty($startDate)) {
                $builder->where('pw.date >=', $startDate);
            }
        }

        $builder->where('ABS(pw.gross_weight) >', 0.100);
        return $builder->countAllResults();
    }

    /**
     * Fast SQL SUM calculation for balance.
     * Compatible with legacy Process_waves_model::get_balance_sum()
     * 
     * @param string $whereSql Raw SQL WHERE clause
     * @param string $balanceType 'fine' (default) or 'gross'
     * @return float
     */
    public function getBalanceSum(string $whereSql, string $balanceType = 'fine'): float
    {
        $db = \Config\Database::connect();
        $builder = $db->table('process_waves');

        if ($balanceType === 'gross') {
            $builder->select('SUM(gross_weight) AS total', false);
        } else {
            $builder->select('SUM((gross_weight * purity) / 100) AS total', false);
        }

        if (trim($whereSql) !== '') {
            $builder->where($whereSql, null, false);
        }

        $row = $builder->get()->getRow();
        return ($row && $row->total !== null) ? (float) $row->total : 0.0;
    }

    /**
     * Fast SQL SUM calculation for piece count.
     * Compatible with legacy Process_waves_model::get_pcs_sum()
     */
    public function getPcsSum(string $whereSql): int
    {
        $db = \Config\Database::connect();
        $builder = $db->table('process_waves');
        $builder->select('SUM(pcs) AS total', false);

        if (trim($whereSql) !== '') {
            $builder->where($whereSql, null, false);
        }

        $row = $builder->get()->getRow();
        return ($row && $row->total !== null) ? (int) $row->total : 0;
    }

    /**
     * Get transaction print data including customer and department accounts.
     */
    public function getTransactionPrintData(int $processWavesId): ?array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('process_waves pw');
        $this->applyCustomerSelectAndJoins($builder, 'a_from.account_name as from_name, a_to.account_name as to_name');
        $builder->join('mst_process a_from', 'a_from.account_id = pw.from_id', 'left');
        $builder->join('mst_process a_to', 'a_to.account_id = pw.to_id', 'left');
        $builder->where('pw.process_waves_id', $processWavesId);

        return $builder->get()->getRowArray();
    }

    /**
     * Get summary of pending and transferred loss across all departments.
     * 
     * @param array $departmentAccountIds Optional list of department IDs to filter
     * @return array Map of department_id => summary data
     */
    public function getLossDepartmentSummary(array $departmentAccountIds = []): array
    {
        $db = \Config\Database::connect();

        // 1. Pending Loss Receipts (to_id = 12 AND further_to_process_waves_id IS NULL)
        $pBuilder = $db->table('process_waves pw');
        $pBuilder->select('pw.from_id as dept_id, COUNT(*) as pending_count, SUM(pw.gross_weight) as pending_gross, SUM((pw.gross_weight * pw.purity)/100) as pending_fine');
        $pBuilder->where('pw.to_id', AccountModel::LOSS_ID);
        $pBuilder->where('pw.further_to_process_waves_id IS NULL', null, false);
        $pBuilder->where('ABS(pw.gross_weight) >', 0.100);
        if (!empty($departmentAccountIds)) {
            $pBuilder->whereIn('pw.from_id', $departmentAccountIds);
        }
        $pBuilder->groupBy('pw.from_id');
        $pendingResults = $pBuilder->get()->getResultArray();

        // 2. Transferred Loss Issues (from_id = 12 AND loss_account_id IS NOT NULL)
        $tBuilder = $db->table('process_waves pw');
        $tBuilder->select('pw.loss_account_id as dept_id, COUNT(*) as transferred_count, SUM(pw.gross_weight) as transferred_gross, SUM((pw.gross_weight * pw.purity)/100) as transferred_fine');
        $tBuilder->where('pw.from_id', AccountModel::LOSS_ID);
        $tBuilder->where('pw.loss_account_id IS NOT NULL', null, false);
        if (!empty($departmentAccountIds)) {
            $tBuilder->whereIn('pw.loss_account_id', $departmentAccountIds);
        }
        $tBuilder->groupBy('pw.loss_account_id');
        $transferredResults = $tBuilder->get()->getResultArray();

        $summary = [];
        foreach ($pendingResults as $row) {
            $id = (int)$row['dept_id'];
            $summary[$id] = [
                'pending_count'      => (int)$row['pending_count'],
                'pending_gross'      => (float)$row['pending_gross'],
                'pending_fine'       => (float)$row['pending_fine'],
                'transferred_count'  => 0,
                'transferred_gross'  => 0.0,
                'transferred_fine'   => 0.0,
            ];
        }

        foreach ($transferredResults as $row) {
            $id = (int)$row['dept_id'];
            if (!isset($summary[$id])) {
                $summary[$id] = [
                    'pending_count'      => 0,
                    'pending_gross'      => 0.0,
                    'pending_fine'       => 0.0,
                    'transferred_count'  => 0,
                    'transferred_gross'  => 0.0,
                    'transferred_fine'   => 0.0,
                ];
            }
            $summary[$id]['transferred_count'] = (int)$row['transferred_count'];
            $summary[$id]['transferred_gross'] = (float)$row['transferred_gross'];
            $summary[$id]['transferred_fine']  = (float)$row['transferred_fine'];
        }

        return $summary;
    }

    /**
     * Atomically consolidate and transfer selected loss batches to Refining.
     * 
     * @param int         $fromLossId    Account ID of Loss (12)
     * @param int         $toRefiningId  Account ID of Refining (9)
     * @param int         $sourceDeptId  Department from which loss was accumulated
     * @param array<int>  $batchIds      Selected source process_waves_id list
     * @param float       $grossWeight   Total gross weight transferred
     * @param float       $purity        Purity (typically 100.00 for refining)
     * @param int         $userId        ID of current user
     * @param string|null $remark        Optional remark
     * @return array [status => 'true'|'false', 'message' => ..., 'process_waves_id' => ...]
     */
    public function consolidateLossBatches(
        int $fromLossId, 
        int $toRefiningId, 
        int $sourceDeptId, 
        array $batchIds, 
        float $grossWeight, 
        float $purity, 
        int $userId, 
        ?string $remark = null
    ): array {
        if (empty($batchIds) || $grossWeight <= 0) {
            return ['status' => 'false', 'message' => 'Invalid batches or gross weight.'];
        }

        $db = \Config\Database::connect();
        $isMySQL = ($db->DBDriver === 'MySQLi');
        $db->transBegin();

        try {
            // Validate and lock all source batches
            $placeholders = implode(',', array_fill(0, count($batchIds), '?'));
            $sql = "SELECT process_waves_id, further_to_process_waves_id FROM process_waves WHERE process_waves_id IN ($placeholders)";
            if ($isMySQL) {
                $sql .= " FOR UPDATE";
            }
            $existingBatches = $db->query($sql, $batchIds)->getResultArray();

            if (count($existingBatches) !== count($batchIds)) {
                $db->transRollback();
                return ['status' => 'false', 'message' => 'One or more source loss batches were not found.'];
            }

            foreach ($existingBatches as $eb) {
                if (!empty($eb['further_to_process_waves_id'])) {
                    $db->transRollback();
                    return ['status' => 'false', 'message' => 'One or more source loss batches have already been transferred.'];
                }
            }

            $guid = bin2hex(random_bytes(16));
            $insertData = [
                'private_key'                 => $guid,
                'from_id'                     => $fromLossId,
                'to_id'                       => $toRefiningId,
                'loss_account_id'             => $sourceDeptId,
                'mapped_cust_id'              => null,
                'cust_id'                     => null,
                'product'                     => 'MT',
                'gross_weight'                => $grossWeight,
                'purity'                      => $purity,
                'net_loss'                    => null,
                'pcs'                         => null,
                'inner_transfer'              => '0',
                'stock_inner_transfer'        => '0',
                'further_to_process_waves_id' => null,
                'locker_stock_check_done'     => '0',
                'user_by'                     => $userId,
                'date'                        => date('Y-m-d H:i:s'),
                'remark'                      => $remark ?: null,
            ];

            $newProcessWavesId = $this->insertProcessWavesData($insertData);

            // Set further_to_process_waves_id to itself on the consolidated transfer batch (legacy CI2 rule)
            $this->builder()
                ->where('process_waves_id', $newProcessWavesId)
                ->set(['further_to_process_waves_id' => $newProcessWavesId])
                ->update();

            // Update all source batches to link them to the new transfer batch
            $this->builder()
                ->whereIn('process_waves_id', $batchIds)
                ->where('further_to_process_waves_id IS NULL', null, false)
                ->set(['further_to_process_waves_id' => $newProcessWavesId])
                ->update();

            if ($db->transStatus() === false) {
                $db->transRollback();
                return ['status' => 'false', 'message' => 'Database error while recording loss consolidation.'];
            }

            $db->transCommit();
            return [
                'status'           => 'true', 
                'message'          => 'Loss consolidated and transferred to Refining successfully.', 
                'process_waves_id' => $newProcessWavesId
            ];
        } catch (\Throwable $e) {
            $db->transRollback();
            return ['status' => 'false', 'message' => 'Exception: ' . $e->getMessage()];
        }
    }
}
