<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
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
        'address_1',
        'address_2',
        'address_3',
        'MT_closing_balance',
        'OR_closing_balance',
        'RK_closing_balance',
        'mobile_no',
        'send_sms',
        'is_petty_cash',
        'cust_status',
    ];

    // Dates
    protected $useTimestamps = false;

    // Validation rules
    protected $validationRules = [
        'cust_name'   => 'required|min_length[2]|max_length[225]',
        'type'        => 'required|in_list[cust,vendor]',
        'mobile_no'   => 'permit_empty|max_length[12]',
        'send_sms'    => 'required|in_list[0,1]',
        'cust_status' => 'required|in_list[0,1]',
    ];

    protected $validationMessages = [
        'cust_name' => [
            'required'   => 'Customer name is required.',
            'min_length' => 'Customer name must be at least 2 characters.',
            'max_length' => 'Customer name cannot exceed 225 characters.',
        ],
        'type' => [
            'required' => 'Please select whether this profile is Customer or Vendor.',
            'in_list'  => 'Type must be either Customer or Vendor.',
        ],
    ];

    /**
     * Find single customer/vendor profile by private_key (32-char GUID).
     *
     * @param string $privateKey
     * @return array|null
     */
    public function getByPrivateKey(string $privateKey): ?array
    {
        return $this->where('private_key', $privateKey)->first();
    }

    /**
     * Check if a customer name already exists (case-insensitive check).
     *
     * @param string      $custName
     * @param string|null $excludePrivateKey
     * @return bool
     */
    public function isNameExists(string $custName, ?string $excludePrivateKey = null): bool
    {
        $builder = $this->where('cust_name', $custName);

        if (!empty($excludePrivateKey)) {
            $builder->where('private_key !=', $excludePrivateKey);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Get customer name by ID.
     *
     * @param int $custId
     * @return string
     */
    public function getCustName(int $custId): string
    {
        $record = $this->select('cust_name')->find($custId);
        return $record['cust_name'] ?? '';
    }

    /**
     * Get customer type by ID.
     *
     * @param int $custId
     * @return string
     */
    public function getCustType(int $custId): string
    {
        $record = $this->select('type')->find($custId);
        return $record['type'] ?? '';
    }

    /**
     * Get customer ID by private key.
     *
     * @param string $privateKey
     * @return int|null
     */
    public function getCustId(string $privateKey): ?int
    {
        $record = $this->select('cust_id')->where('private_key', $privateKey)->first();
        return isset($record['cust_id']) ? (int)$record['cust_id'] : null;
    }

    /**
     * Get customer ID by exact name.
     *
     * @param string $custName
     * @return int|null
     */
    public function getCustIdByName(string $custName): ?int
    {
        $record = $this->select('cust_id')->where('cust_name', $custName)->first();
        return isset($record['cust_id']) ? (int)$record['cust_id'] : null;
    }

    /**
     * Retrieve paginated customers with optional search & filter.
     *
     * @param int         $perPage
     * @param string|null $search
     * @param string|null $type
     * @param string|null $status
     * @return array
     */
    public function getPaginatedCustomers(int $perPage = 10, ?string $search = null, ?string $type = null, ?string $status = null): array
    {
        $builder = $this;

        if (!empty($search)) {
            $builder->groupStart()
                ->like('cust_name', $search)
                ->orLike('mobile_no', $search)
                ->groupEnd();
        }

        if (!empty($type) && in_array($type, ['cust', 'vendor'], true)) {
            $builder->where('type', $type);
        }

        if ($status !== null && $status !== '') {
            $builder->where('cust_status', $status);
        }

        return $builder->orderBy('cust_id', 'DESC')->paginate($perPage);
    }

    /**
     * Efficiently calculate customer & vendor summary stats in a single query.
     *
     * @return array{total_accounts: int, total_customers: int, total_vendors: int, total_active: int, total_inactive: int}
     */
    public function getSummaryStats(): array
    {
        $row = $this->db->query("
            SELECT 
                COUNT(*) as total_accounts,
                COALESCE(SUM(CASE WHEN type = 'cust' THEN 1 ELSE 0 END), 0) as total_customers,
                COALESCE(SUM(CASE WHEN type = 'vendor' THEN 1 ELSE 0 END), 0) as total_vendors,
                COALESCE(SUM(CASE WHEN cust_status = '1' THEN 1 ELSE 0 END), 0) as total_active,
                COALESCE(SUM(CASE WHEN cust_status = '0' THEN 1 ELSE 0 END), 0) as total_inactive
            FROM {$this->table}
        ")->getRowArray();

        return [
            'total_accounts'  => (int)($row['total_accounts'] ?? 0),
            'total_customers' => (int)($row['total_customers'] ?? 0),
            'total_vendors'   => (int)($row['total_vendors'] ?? 0),
            'total_active'    => (int)($row['total_active'] ?? 0),
            'total_inactive'  => (int)($row['total_inactive'] ?? 0),
        ];
    }

    /**
     * Efficiently get total customer opening/closing balances across MT, OR, and RK modes.
     *
     * @return array{mt_total: float, or_total: float, rk_total: float}
     */
    public function getClosingTotalBalances(): array
    {
        $row = $this->db->query("
            SELECT 
                COALESCE(SUM(MT_closing_balance), 0) as mt_total,
                COALESCE(SUM(OR_closing_balance), 0) as or_total,
                COALESCE(SUM(RK_closing_balance), 0) as rk_total
            FROM {$this->table}
        ")->getRowArray();

        return [
            'mt_total' => (float)($row['mt_total'] ?? 0),
            'or_total' => (float)($row['or_total'] ?? 0),
            'rk_total' => (float)($row['rk_total'] ?? 0),
        ];
    }

    /**
     * Backward-compatible helper for legacy master_model::get_MT_closing_total_balance()
     */
    public function getMTClosingTotalBalance(): float
    {
        $balances = $this->getClosingTotalBalances();
        return $balances['mt_total'];
    }

    /**
     * Backward-compatible helper for legacy master_model::get_OR_closing_total_balance()
     */
    public function getORClosingTotalBalance(): float
    {
        $balances = $this->getClosingTotalBalances();
        return $balances['or_total'];
    }

    /**
     * Backward-compatible helper for legacy master_model::get_RK_closing_total_balance()
     */
    public function getRKClosingTotalBalance(): float
    {
        $balances = $this->getClosingTotalBalances();
        return $balances['rk_total'];
    }
}
