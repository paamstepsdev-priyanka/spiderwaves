<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerMappingModel extends Model
{
    protected $table            = 'mst_cust_mapping';
    protected $primaryKey       = 'mapped_cust_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'private_key',
        'mapped_cust_name',
        'cust_id',
        'purity',
        'collection_id',
        'mapped_cust_status',
    ];

    protected $useTimestamps = false;

    // Validation rules for Customer Mapping
    protected $validationRules = [
        'mapped_cust_name'   => 'required|min_length[1]|max_length[225]',
        'cust_id'            => 'required|is_natural_no_zero',
        'collection_id'      => 'required|is_natural_no_zero',
        'purity'             => 'required|numeric|greater_than[0]',
        'mapped_cust_status' => 'required|in_list[0,1]',
    ];

    protected $validationMessages = [
        'mapped_cust_name' => [
            'required' => 'Mapped Customer Name is required.',
        ],
        'cust_id' => [
            'required'            => 'Please select a Customer.',
            'is_natural_no_zero' => 'Invalid Customer selection.',
        ],
        'collection_id' => [
            'required'            => 'Please select a Collection.',
            'is_natural_no_zero' => 'Invalid Collection selection.',
        ],
        'purity' => [
            'required'     => 'Purity is required.',
            'numeric'      => 'Purity must be a valid number.',
            'greater_than' => 'Purity must be greater than zero.',
        ],
        'mapped_cust_status' => [
            'required' => 'Status is required.',
            'in_list'  => 'Status must be Active or Inactive.',
        ],
    ];

    /**
     * Get paginated customer mapping records joined with Customer and Collection tables.
     */
    public function getPaginatedMappings(
        int $perPage = 10,
        ?string $search = null,
        ?int $custId = null,
        ?int $collectionId = null,
        ?string $status = null
    ): array {
        $builder = $this->select('mst_cust_mapping.*, mst_customer.cust_name, mst_collection.collection_name')
                        ->join('mst_customer', 'mst_customer.cust_id = mst_cust_mapping.cust_id', 'left')
                        ->join('mst_collection', 'mst_collection.collection_id = mst_cust_mapping.collection_id', 'left');

        if (!empty($search)) {
            $builder->groupStart()
                    ->like('mst_cust_mapping.mapped_cust_name', $search)
                    ->orLike('mst_customer.cust_name', $search)
                    ->groupEnd();
        }

        if (!empty($custId)) {
            $builder->where('mst_cust_mapping.cust_id', $custId);
        }

        if (!empty($collectionId)) {
            $builder->where('mst_cust_mapping.collection_id', $collectionId);
        }

        if ($status !== null && $status !== '') {
            $builder->where('mst_cust_mapping.mapped_cust_status', (string) $status);
        }

        return $builder->orderBy('mst_cust_mapping.mapped_cust_id', 'DESC')
                       ->paginate($perPage);
    }

    /**
     * Get customer mapping record by private key joined with Customer and Collection names.
     */
    public function getByPrivateKey(string $privateKey): ?array
    {
        return $this->select('mst_cust_mapping.*, mst_customer.cust_name, mst_collection.collection_name')
                    ->join('mst_customer', 'mst_customer.cust_id = mst_cust_mapping.cust_id', 'left')
                    ->join('mst_collection', 'mst_collection.collection_id = mst_cust_mapping.collection_id', 'left')
                    ->where('mst_cust_mapping.private_key', $privateKey)
                    ->first();
    }

    /**
     * Check if a mapped customer name already exists (case-insensitive).
     */
    public function isMappedNameExists(string $mappedName, ?string $excludePrivateKey = null): bool
    {
        $cleanName = strtoupper(trim($mappedName));
        if ($cleanName === '') {
            return false;
        }

        $builder = $this->where('mapped_cust_name', $cleanName);

        if ($excludePrivateKey) {
            $builder->where('private_key !=', $excludePrivateKey);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Generate unique private key (GUID).
     */
    public function generatePrivateKey(): string
    {
        return md5(uniqid('', true));
    }

    /**
     * Get mapped customer name by mapped_cust_id.
     */
    public function getMappedCustName(int $mappedCustId): string
    {
        $res = $this->select('mapped_cust_name')->find($mappedCustId);
        return $res['mapped_cust_name'] ?? '';
    }

    /**
     * Get customer ID by mapped_cust_id.
     */
    public function getCustomerIdByMappedId(int $mappedCustId): ?int
    {
        $res = $this->select('cust_id')->find($mappedCustId);
        return isset($res['cust_id']) ? (int) $res['cust_id'] : null;
    }

    /**
     * Get collection ID by mapped_cust_id.
     */
    public function getCollectionIdByMappedId(int $mappedCustId): ?int
    {
        $res = $this->select('collection_id')->find($mappedCustId);
        return isset($res['collection_id']) ? (int) $res['collection_id'] : null;
    }

    /**
     * Get purity by mapped_cust_id.
     */
    public function getPurityByMappedId(int $mappedCustId): ?float
    {
        $res = $this->select('purity')->find($mappedCustId);
        return isset($res['purity']) ? (float) $res['purity'] : null;
    }

    /**
     * Get customer wastage data from cust_wastage table.
     */
    public function getCustomerWastage(int $customerId, int $collectionId): ?float
    {
        $db = \Config\Database::connect();
        $row = $db->table('mst_cust_wastage')
                  ->select('wastage')
                  ->where('customer_id', $customerId)
                  ->where('collection_id', $collectionId)
                  ->get()
                  ->getRowArray();

        return isset($row['wastage']) ? (float) $row['wastage'] : null;
    }

    /**
     * Get summary KPI statistics.
     */
    public function getSummaryStats(): array
    {
        $db = \Config\Database::connect();
        $sql = "SELECT 
                    COUNT(*) as total_mappings,
                    SUM(CASE WHEN mapped_cust_status = '1' THEN 1 ELSE 0 END) as total_active,
                    SUM(CASE WHEN mapped_cust_status = '0' THEN 1 ELSE 0 END) as total_inactive,
                    COUNT(DISTINCT cust_id) as total_customers_mapped,
                    COUNT(DISTINCT collection_id) as total_collections_mapped
                FROM mst_cust_mapping";

        $query = $db->query($sql);
        $row = $query->getRowArray();

        return [
            'total_mappings'           => (int) ($row['total_mappings'] ?? 0),
            'total_active'             => (int) ($row['total_active'] ?? 0),
            'total_inactive'           => (int) ($row['total_inactive'] ?? 0),
            'total_customers_mapped'   => (int) ($row['total_customers_mapped'] ?? 0),
            'total_collections_mapped' => (int) ($row['total_collections_mapped'] ?? 0),
        ];
    }
}
