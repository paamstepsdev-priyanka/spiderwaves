<?php

namespace App\Models;

class UccCollectionModel extends UccBaseModel
{
    protected $table            = 'ucc_collection';
    protected $primaryKey       = 'collection_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'collection_id',
        'private_key',
        'parent_id',
        'collection_name',
        'shortcode',
        'is_active',
        'ucc_created_at',
        'ucc_updated_at',
        'row_hash',
        'synced_at',
        'deleted_at',
    ];

    protected $useTimestamps = false;

    /**
     * Get paginated UCC collections with mapped customer counts (deleted_at IS NULL).
     */
    public function getPaginatedCollections(int $perPage = 10, ?string $search = null, ?string $status = null): array
    {
        $builder = $this->select(
            'ucc_collection.collection_id, ucc_collection.private_key, ucc_collection.parent_id, ' .
            'ucc_collection.collection_name, ucc_collection.shortcode, ' .
            'CAST(ucc_collection.is_active AS CHAR) AS collection_status, ' .
            'COUNT(ucc_mapped_customer.mapped_customer_id) AS mapping_count'
        )
            ->join('ucc_mapped_customer', 'ucc_mapped_customer.collection_id = ucc_collection.collection_id AND ucc_mapped_customer.deleted_at IS NULL', 'left')
            ->where('ucc_collection.deleted_at IS NULL')
            ->groupBy('ucc_collection.collection_id');

        if (!empty($search)) {
            $builder->like('ucc_collection.collection_name', $search);
        }

        if ($status !== null && $status !== '') {
            $builder->where('ucc_collection.is_active', (int)$status);
        }

        return $builder->orderBy('ucc_collection.collection_id', 'DESC')->paginate($perPage);
    }

    /**
     * Get all active UCC collections.
     */
    public function getActiveCollections(): array
    {
        return $this->where('deleted_at IS NULL')
            ->where('is_active', 1)
            ->orderBy('collection_name', 'ASC')
            ->findAll();
    }
}

