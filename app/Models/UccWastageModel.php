<?php

namespace App\Models;

class UccWastageModel extends UccBaseModel
{
    protected $table            = 'ucc_wastage';
    protected $primaryKey       = 'wastage_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'wastage_id',
        'private_key',
        'wastage',
        'is_active',
        'row_hash',
        'synced_at',
        'deleted_at',
    ];

    protected $useTimestamps = false;

    /**
     * Retrieve paginated active UCC wastage records (deleted_at IS NULL).
     */
    public function getPaginatedWastages(int $perPage = 10, ?string $search = null, ?string $status = null): array
    {
        $builder = $this->select('wastage_id, private_key, wastage, is_active, synced_at')
            ->where('deleted_at IS NULL');

        if (!empty($search)) {
            $builder->groupStart()
                ->like('CAST(wastage AS CHAR)', $search)
                ->orLike('CAST(wastage_id AS CHAR)', $search)
                ->orLike('private_key', $search)
                ->groupEnd();
        }

        if ($status !== null && $status !== '') {
            $builder->where('is_active', (int)$status);
        }

        return $builder->orderBy('wastage', 'ASC')
            ->orderBy('wastage_id', 'ASC')
            ->paginate($perPage);
    }
}

