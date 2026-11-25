<?php

namespace App\Models;

class UccPurityModel extends UccBaseModel
{
    protected $table            = 'ucc_purity';
    protected $primaryKey       = 'purity_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'purity_id',
        'private_key',
        'purity',
        'gold_multiplier',
        'is_active',
        'ucc_created_at',
        'ucc_updated_at',
        'row_hash',
        'synced_at',
        'deleted_at',
    ];

    protected $useTimestamps = false;

    /**
     * Get paginated UCC purities (deleted_at IS NULL).
     */
    public function getPaginatedPurities(int $perPage = 10, ?string $search = null, ?string $status = null): array
    {
        $builder = $this->select(
            "purity_id, private_key, CONCAT(TRIM(TRAILING '.00' FROM CAST(purity AS CHAR)), '%') AS purity_name, " .
            "purity AS purity_value, gold_multiplier, CAST(is_active AS CHAR) AS purity_status"
        )
            ->where('deleted_at IS NULL');

        if (!empty($search)) {
            $builder->like('purity', $search);
        }

        if ($status !== null && $status !== '') {
            $builder->where('is_active', (int)$status);
        }

        return $builder->orderBy('purity', 'ASC')
            ->orderBy('purity_id', 'DESC')
            ->paginate($perPage);
    }

    /**
     * Get summary stats for UCC purities.
     */
    public function getSummaryStats(): array
    {
        $row = $this->db->query("
            SELECT
                COUNT(*) AS total_purities,
                COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) AS total_active,
                COALESCE(SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END), 0) AS total_inactive
            FROM {$this->table}
            WHERE deleted_at IS NULL
        ")->getRowArray();

        return [
            'total_purities' => (int)($row['total_purities'] ?? 0),
            'total_active'   => (int)($row['total_active'] ?? 0),
            'total_inactive' => (int)($row['total_inactive'] ?? 0),
        ];
    }

    /**
     * Get all active UCC purities ordered by purity value.
     */
    public function getActivePurities(): array
    {
        return $this->select(
            "purity_id, private_key, CONCAT(TRIM(TRAILING '.00' FROM CAST(purity AS CHAR)), '%') AS purity_name, " .
            "purity AS purity_value, gold_multiplier, CAST(is_active AS CHAR) AS purity_status"
        )
            ->where('deleted_at IS NULL')
            ->where('is_active', 1)
            ->orderBy('purity', 'ASC')
            ->findAll();
    }
}

