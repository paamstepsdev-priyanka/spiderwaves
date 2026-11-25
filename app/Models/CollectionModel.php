<?php

namespace App\Models;

use CodeIgniter\Model;

class CollectionModel extends Model
{
    protected $table            = 'mst_collection';
    protected $primaryKey       = 'collection_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'private_key',
        'collection_name',
        'collection_status',
    ];

    protected $useTimestamps = false;

    // Validation rules
    protected $validationRules = [
        'collection_name'   => 'required|min_length[2]|max_length[225]',
        'collection_status' => 'required|in_list[0,1]',
    ];

    protected $validationMessages = [
        'collection_name' => [
            'required'   => 'Collection name is required.',
            'min_length' => 'Collection name must be at least 2 characters.',
            'max_length' => 'Collection name cannot exceed 225 characters.',
        ],
        'collection_status' => [
            'required' => 'Please select status.',
            'in_list'  => 'Status must be Active (1) or Inactive (0).',
        ],
    ];

    /**
     * Get paginated collections with optional search and status filter.
     *
     * @param int         $perPage
     * @param string|null $search
     * @param string|null $status
     * @return array
     */
    public function getPaginatedCollections(int $perPage = 10, ?string $search = null, ?string $status = null): array
    {
        $builder = $this->select('mst_collection.*, COUNT(mst_cust_mapping.mapped_cust_id) AS mapping_count')
                        ->join('mst_cust_mapping', 'mst_cust_mapping.collection_id = mst_collection.collection_id', 'left')
                        ->groupBy('mst_collection.collection_id');

        if (!empty($search)) {
            $builder->like('mst_collection.collection_name', $search);
        }

        if ($status !== null && $status !== '') {
            $builder->where('mst_collection.collection_status', $status);
        }

        return $builder->orderBy('mst_collection.collection_id', 'DESC')->paginate($perPage);
    }

    /**
     * Find single collection record by private_key (32-character GUID).
     *
     * @param string $privateKey
     * @return array|null
     */
    public function getByPrivateKey(string $privateKey): ?array
    {
        return $this->where('private_key', $privateKey)->first();
    }

    /**
     * Check if a collection name already exists (case-insensitive check).
     *
     * @param string      $collectionName
     * @param string|null $excludePrivateKey
     * @return bool
     */
    public function isNameExists(string $collectionName, ?string $excludePrivateKey = null): bool
    {
        $builder = $this->where('LOWER(collection_name)', strtolower(trim($collectionName)));

        if (!empty($excludePrivateKey)) {
            $builder->where('private_key !=', $excludePrivateKey);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Safe soft toggle between Active (1) and Inactive (0) status.
     *
     * @param string $privateKey
     * @return array{success: bool, newStatus?: string, message: string}
     */
    public function toggleCollectionStatus(string $privateKey): array
    {
        $collection = $this->getByPrivateKey($privateKey);
        if (!$collection) {
            return ['success' => false, 'message' => 'Collection not found.'];
        }

        $newStatus = ($collection['collection_status'] === '1') ? '0' : '1';
        $this->update($collection['collection_id'], ['collection_status' => $newStatus]);

        $action = ($newStatus === '1') ? 'activated' : 'deactivated';
        return [
            'success'   => true,
            'newStatus' => $newStatus,
            'message'   => "Collection '{$collection['collection_name']}' successfully {$action}."
        ];
    }

    /**
     * Retrieve summary statistics for collections.
     *
     * @return array{total_collections: int, total_active: int, total_inactive: int}
     */
    public function getSummaryStats(): array
    {
        $row = $this->db->query("
            SELECT 
                COUNT(*) as total_collections,
                COALESCE(SUM(CASE WHEN collection_status = '1' THEN 1 ELSE 0 END), 0) as total_active,
                COALESCE(SUM(CASE WHEN collection_status = '0' THEN 1 ELSE 0 END), 0) as total_inactive
            FROM {$this->table}
        ")->getRowArray();

        return [
            'total_collections' => (int)($row['total_collections'] ?? 0),
            'total_active'      => (int)($row['total_active'] ?? 0),
            'total_inactive'    => (int)($row['total_inactive'] ?? 0),
        ];
    }

    /**
     * Get all active collections.
     */
    public function getActiveCollections(): array
    {
        return $this->where('collection_status', '1')
                    ->orderBy('collection_name', 'ASC')
                    ->findAll();
    }

    /**
     * Get collection name by collection ID.
     */
    public function getCollectionName(int $collectionId): string
    {
        $collection = $this->select('collection_name')
                           ->find($collectionId);

        return $collection['collection_name'] ?? '';
    }

    /**
     * Get collection ID by name (case-insensitive, space-normalized matching).
     */
    public function getCollectionIdByName(string $name): ?int
    {
        $cleanName = trim($name);
        if ($cleanName === '') {
            return null;
        }

        // Direct match first
        $exact = $this->where('LOWER(collection_name)', strtolower($cleanName))->first();
        if ($exact) {
            return (int) $exact['collection_id'];
        }

        // Space-normalized match (matching CI3 logic: str_replace(' ','',strtolower(...)))
        $all = $this->findAll();
        $normalizedSearch = str_replace(' ', '', strtolower($cleanName));
        foreach ($all as $item) {
            $normalizedItem = str_replace(' ', '', strtolower($item['collection_name']));
            if ($normalizedSearch === $normalizedItem) {
                return (int) $item['collection_id'];
            }
        }

        return null;
    }
}
