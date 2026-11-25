<?php

namespace App\Models;

class UccCustomerModel extends UccBaseModel
{
    protected $table            = 'ucc_customer';
    protected $primaryKey       = 'customer_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'customer_id',
        'private_key',
        'customer_name',
        'is_active',
        'ucc_created_at',
        'ucc_updated_at',
        'row_hash',
        'synced_at',
        'deleted_at',
    ];

    protected $useTimestamps = false;

    /**
     * Retrieve paginated active UCC customers (deleted_at IS NULL).
     */
    public function getPaginatedCustomers(int $perPage = 10, ?string $search = null, ?string $type = null, ?string $status = null): array
    {
        $builder = $this->select("customer_id AS cust_id, private_key, customer_name AS cust_name, 'cust' AS type, CAST(is_active AS CHAR) AS cust_status")
            ->where('deleted_at IS NULL');

        if (!empty($search)) {
            $builder->like('customer_name', $search);
        }

        if ($type === 'vendor') {
            $builder->where('1 = 0');
        }

        if ($status !== null && $status !== '') {
            $builder->where('is_active', (int)$status);
        }

        return $builder->orderBy('customer_id', 'DESC')->paginate($perPage);
    }

    /**
     * Summary stats for UCC customers.
     */
    public function getSummaryStats(): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(*) AS total_accounts,
                COUNT(*) AS total_customers,
                0 AS total_vendors,
                COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) AS total_active,
                COALESCE(SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END), 0) AS total_inactive
            FROM {$this->table}
            WHERE deleted_at IS NULL
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
     * Get all active UCC customers for dropdown filters.
     */
    public function getActiveCustomers(): array
    {
        return $this->select('customer_id AS cust_id, private_key, customer_name AS cust_name')
            ->where('deleted_at IS NULL')
            ->where('is_active', 1)
            ->orderBy('customer_name', 'ASC')
            ->findAll();
    }
}

