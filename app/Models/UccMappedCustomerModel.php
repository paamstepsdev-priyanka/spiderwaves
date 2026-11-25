<?php

namespace App\Models;

class UccMappedCustomerModel extends UccBaseModel
{
    protected $table            = 'ucc_mapped_customer';
    protected $primaryKey       = 'mapped_customer_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'mapped_customer_id',
        'private_key',
        'mapped_customer_name',
        'customer_id',
        'collection_id',
        'wastage_id',
        'purity_id',
        'stone_type',
        'gold_type',
        'is_active',
        'ucc_created_at',
        'ucc_updated_at',
        'row_hash',
        'synced_at',
        'deleted_at',
    ];

    protected $useTimestamps = false;

    /**
     * Check for orphaned mapped customers referencing missing or soft-deleted parent master rows.
     */
    public function getOrphanedReferences(): array
    {
        $db = \Config\Database::connect();
        
        $sql = "
            SELECT mc.mapped_customer_id, mc.private_key, mc.mapped_customer_name,
                   mc.customer_id, mc.collection_id, mc.wastage_id, mc.purity_id,
                   CASE WHEN c.customer_id IS NULL THEN 'Missing Customer' ELSE NULL END AS missing_customer,
                   CASE WHEN col.collection_id IS NULL THEN 'Missing Collection' ELSE NULL END AS missing_collection,
                   CASE WHEN w.wastage_id IS NULL THEN 'Missing Wastage' ELSE NULL END AS missing_wastage,
                   CASE WHEN p.purity_id IS NULL THEN 'Missing Purity' ELSE NULL END AS missing_purity
            FROM ucc_mapped_customer mc
            LEFT JOIN ucc_customer c ON c.customer_id = mc.customer_id AND c.deleted_at IS NULL
            LEFT JOIN ucc_collection col ON col.collection_id = mc.collection_id AND col.deleted_at IS NULL
            LEFT JOIN ucc_wastage w ON w.wastage_id = mc.wastage_id AND w.deleted_at IS NULL
            LEFT JOIN ucc_purity p ON p.purity_id = mc.purity_id AND p.deleted_at IS NULL
            WHERE mc.deleted_at IS NULL
              AND (c.customer_id IS NULL OR col.collection_id IS NULL OR w.wastage_id IS NULL OR p.purity_id IS NULL)
        ";

        return $db->query($sql)->getResultArray();
    }

    /**
     * Retrieve paginated UCC mapped customers joined with UCC customer, collection, and purity.
     */
    public function getPaginatedMappings(
        int $perPage = 10,
        ?string $search = null,
        ?int $custId = null,
        ?int $collectionId = null,
        ?string $status = null
    ): array {
        $builder = $this->select(
            'ucc_mapped_customer.mapped_customer_id AS mapped_cust_id, ' .
            'ucc_mapped_customer.private_key, ' .
            'ucc_mapped_customer.mapped_customer_name AS mapped_cust_name, ' .
            'ucc_mapped_customer.customer_id AS cust_id, ' .
            'ucc_mapped_customer.collection_id, ' .
            'ucc_mapped_customer.wastage_id, ' .
            'ucc_mapped_customer.purity_id, ' .
            'ucc_mapped_customer.stone_type, ' .
            'ucc_mapped_customer.gold_type, ' .
            'CAST(ucc_mapped_customer.is_active AS CHAR) AS mapped_cust_status, ' .
            'ucc_customer.customer_name AS cust_name, ' .
            'ucc_collection.collection_name, ' .
            'ucc_purity.purity'
        )
            ->join('ucc_customer', 'ucc_customer.customer_id = ucc_mapped_customer.customer_id AND ucc_customer.deleted_at IS NULL', 'left')
            ->join('ucc_collection', 'ucc_collection.collection_id = ucc_mapped_customer.collection_id AND ucc_collection.deleted_at IS NULL', 'left')
            ->join('ucc_purity', 'ucc_purity.purity_id = ucc_mapped_customer.purity_id AND ucc_purity.deleted_at IS NULL', 'left')
            ->where('ucc_mapped_customer.deleted_at IS NULL');

        if (!empty($search)) {
            $builder->groupStart()
                ->like('ucc_mapped_customer.mapped_customer_name', $search)
                ->orLike('ucc_customer.customer_name', $search)
                ->groupEnd();
        }

        if (!empty($custId)) {
            $builder->where('ucc_mapped_customer.customer_id', $custId);
        }

        if (!empty($collectionId)) {
            $builder->where('ucc_mapped_customer.collection_id', $collectionId);
        }

        if ($status !== null && $status !== '') {
            $builder->where('ucc_mapped_customer.is_active', (int)$status);
        }

        return $builder->orderBy('ucc_mapped_customer.mapped_customer_id', 'DESC')
            ->paginate($perPage);
    }

    /**
     * Get summary KPI statistics for UCC mapped customers.
     */
    public function getSummaryStats(): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(*) AS total_mappings,
                COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) AS total_active,
                COALESCE(SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END), 0) AS total_inactive,
                COUNT(DISTINCT customer_id) AS total_customers_mapped,
                COUNT(DISTINCT collection_id) AS total_collections_mapped
            FROM {$this->table}
            WHERE deleted_at IS NULL
        ")->getRowArray();

        return [
            'total_mappings'           => (int)($row['total_mappings'] ?? 0),
            'total_active'             => (int)($row['total_active'] ?? 0),
            'total_inactive'           => (int)($row['total_inactive'] ?? 0),
            'total_customers_mapped'   => (int)($row['total_customers_mapped'] ?? 0),
            'total_collections_mapped' => (int)($row['total_collections_mapped'] ?? 0),
        ];
    }

    /**
     * Get active mapped customers for dropdowns.
     */
    public function getActiveMappings(): array
    {
        return $this->select('mapped_customer_id AS mapped_cust_id, mapped_customer_name AS mapped_cust_name')
            ->where('deleted_at IS NULL')
            ->where('is_active', 1)
            ->orderBy('mapped_customer_name', 'ASC')
            ->findAll();
    }

    public function getPurityByMappedId(int $mappedCustId): ?float
    {
        $row = $this->select('ucc_purity.purity')
            ->join('ucc_purity', 'ucc_purity.purity_id = ucc_mapped_customer.purity_id AND ucc_purity.deleted_at IS NULL', 'left')
            ->where('ucc_mapped_customer.deleted_at IS NULL')
            ->where('ucc_mapped_customer.mapped_customer_id', $mappedCustId)
            ->first();

        return isset($row['purity']) ? (float)$row['purity'] : null;
    }

    public function getCustomerNameByMappedId(int $mappedCustId): string
    {
        $row = $this->select('ucc_customer.customer_name')
            ->join('ucc_customer', 'ucc_customer.customer_id = ucc_mapped_customer.customer_id AND ucc_customer.deleted_at IS NULL', 'left')
            ->where('ucc_mapped_customer.deleted_at IS NULL')
            ->where('ucc_mapped_customer.mapped_customer_id', $mappedCustId)
            ->first();

        return (string)($row['customer_name'] ?? '');
    }

    public function getCollectionNameByMappedId(int $mappedCustId): string
    {
        $row = $this->select('ucc_collection.collection_name')
            ->join('ucc_collection', 'ucc_collection.collection_id = ucc_mapped_customer.collection_id AND ucc_collection.deleted_at IS NULL', 'left')
            ->where('ucc_mapped_customer.deleted_at IS NULL')
            ->where('ucc_mapped_customer.mapped_customer_id', $mappedCustId)
            ->first();

        return (string)($row['collection_name'] ?? '');
    }

    public function getWastageByMappedId(int $mappedCustId): ?float
    {
        $row = $this->select('ucc_wastage.wastage')
            ->join('ucc_wastage', 'ucc_wastage.wastage_id = ucc_mapped_customer.wastage_id AND ucc_wastage.deleted_at IS NULL', 'left')
            ->where('ucc_mapped_customer.deleted_at IS NULL')
            ->where('ucc_mapped_customer.is_active', 1)
            ->where('ucc_mapped_customer.mapped_customer_id', $mappedCustId)
            ->first();

        return isset($row['wastage']) ? (float)$row['wastage'] : null;
    }
}

