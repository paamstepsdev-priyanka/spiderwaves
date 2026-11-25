<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PettyCashModel
 * 
 * Manages Customer Petty Cash designations in SpiderWaves.
 * Operates on table 'cust' specifically controlling the 'is_petty_cash' enum flag ('1'/'0').
 */
class PettyCashModel extends Model
{
    protected $table            = 'mst_customer';
    protected $primaryKey       = 'cust_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'private_key',
        'cust_name',
        'type',
        'mobile_no',
        'is_petty_cash',
        'cust_status',
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'private_key'   => 'permit_empty|max_length[50]',
        'is_petty_cash' => 'required|in_list[0,1]',
    ];

    /**
     * Retrieve paginated customers with search and petty cash status filters.
     *
     * @param int $perPage
     * @param string|null $search
     * @param string|null $status '1' = Petty Cash, '0' = Standard
     * @return array
     */
    public function getPaginatedPettyCash(int $perPage = 25, ?string $search = null, ?string $status = null): array
    {
        $builder = $this->builder();
        $builder->select('cust_id, private_key, cust_name, type, mobile_no, is_petty_cash, cust_status');

        if (!empty($search)) {
            $search = trim($search);
            $builder->groupStart()
                ->like('cust_name', $search)
                ->orLike('mobile_no', $search)
                ->groupEnd();
        }

        if ($status !== null && in_array($status, ['0', '1'], true)) {
            $builder->where('is_petty_cash', $status);
        }

        $builder->orderBy('is_petty_cash', 'DESC');
        $builder->orderBy('cust_id', 'DESC');

        return $this->paginate($perPage);
    }

    /**
     * Lookup customer by private_key.
     *
     * @param string $privateKey
     * @return array|null
     */
    public function getByPrivateKey(string $privateKey): ?array
    {
        return $this->where('private_key', $privateKey)->first();
    }

    /**
     * Calculate summary KPI counts for Petty Cash Master.
     *
     * @return array
     */
    public function getSummaryStats(): array
    {
        $row = $this->db->table($this->table)
            ->select("
                COUNT(*) as total_customers,
                SUM(CASE WHEN is_petty_cash = '1' THEN 1 ELSE 0 END) as petty_cash_count,
                SUM(CASE WHEN is_petty_cash = '0' THEN 1 ELSE 0 END) as standard_count
            ")
            ->get()
            ->getRowArray();

        return [
            'total'      => (int) ($row['total_customers'] ?? 0),
            'petty_cash' => (int) ($row['petty_cash_count'] ?? 0),
            'standard'   => (int) ($row['standard_count'] ?? 0),
        ];
    }

    /**
     * Toggle is_petty_cash status between '1' and '0'.
     *
     * @param string $privateKey
     * @return string|false New status ('1' or '0') or false on failure
     */
    public function togglePettyCash(string $privateKey)
    {
        $customer = $this->getByPrivateKey($privateKey);
        if (!$customer) {
            return false;
        }

        $newStatus = ($customer['is_petty_cash'] === '1') ? '0' : '1';
        $updated = $this->update($customer['cust_id'], ['is_petty_cash' => $newStatus]);

        return $updated ? $newStatus : false;
    }

    /**
     * Explicitly set is_petty_cash status for a single customer.
     *
     * @param string $privateKey
     * @param string $status '1' or '0'
     * @return bool
     */
    public function setPettyCash(string $privateKey, string $status): bool
    {
        if (!in_array($status, ['0', '1'], true)) {
            return false;
        }

        $customer = $this->getByPrivateKey($privateKey);
        if (!$customer) {
            return false;
        }

        return (bool) $this->update($customer['cust_id'], ['is_petty_cash' => $status]);
    }

    /**
     * Bulk update customer petty cash statuses from POST array (replicates legacy insert_petty_cash_cust).
     *
     * @param array $postData Array of [private_key => '0'|'1']
     * @return bool
     */
    public function bulkUpdatePettyCash(array $postData): bool
    {
        if (empty($postData)) {
            return false;
        }

        $successCount = 0;
        foreach ($postData as $key => $val) {
            // Validate key is 32-char hex and val is '0' or '1'
            if (strlen($key) === 32 && in_array($val, ['0', '1', 0, 1], true)) {
                $statusVal = (string) $val;
                $updated = $this->where('private_key', $key)->set(['is_petty_cash' => $statusVal])->update();
                if ($updated) {
                    $successCount++;
                }
            }
        }

        return $successCount > 0;
    }

    /**
     * Retrieve active standard customers that can be designated as Petty Cash.
     *
     * @return array
     */
    public function getAvailableCustomersForPettyCash(): array
    {
        return $this->select('cust_id, private_key, cust_name, type, mobile_no')
            ->where('is_petty_cash', '0')
            ->where('cust_status', '1')
            ->orderBy('cust_name', 'ASC')
            ->findAll();
    }

    /**
     * Retrieve active customers already designated as Petty Cash.
     *
     * @return array
     */
    public function getActivePettyCashCustomers(): array
    {
        return $this->select('cust_id, private_key, cust_name, type, mobile_no')
            ->where('is_petty_cash', '1')
            ->where('cust_status', '1')
            ->orderBy('cust_name', 'ASC')
            ->findAll();
    }
}
