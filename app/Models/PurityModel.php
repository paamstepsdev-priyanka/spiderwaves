<?php

namespace App\Models;

use CodeIgniter\Model;

class PurityModel extends Model
{
    protected $table            = 'mst_purity';
    protected $primaryKey       = 'purity_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'private_key',
        'purity_name',
        'purity_value',
        'purity_status',
    ];

    protected $useTimestamps = false;

    // Validation rules
    protected $validationRules = [
        'purity_name'   => 'required|min_length[1]|max_length[100]',
        'purity_value'  => 'required|numeric|greater_than[0]|less_than_equal_to[100]',
        'purity_status' => 'required|in_list[0,1]',
    ];

    protected $validationMessages = [
        'purity_name' => [
            'required'   => 'Purity name / label is required.',
            'max_length' => 'Purity name cannot exceed 100 characters.',
        ],
        'purity_value' => [
            'required'              => 'Purity percentage value is required.',
            'numeric'               => 'Purity value must be a valid number.',
            'greater_than'          => 'Purity value must be greater than 0%.',
            'less_than_equal_to'    => 'Purity value cannot exceed 100%.',
        ],
        'purity_status' => [
            'required' => 'Please select status.',
            'in_list'  => 'Status must be Active (1) or Inactive (0).',
        ],
    ];

    /**
     * Get paginated purities with optional search and status filter.
     *
     * @param int         $perPage
     * @param string|null $search
     * @param string|null $status
     * @return array
     */
    public function getPaginatedPurities(int $perPage = 10, ?string $search = null, ?string $status = null): array
    {
        $builder = $this->select('mst_purity.*');

        if (!empty($search)) {
            $builder->groupStart()
                    ->like('purity_name', $search)
                    ->orLike('purity_value', $search)
                    ->groupEnd();
        }

        if ($status !== null && $status !== '') {
            $builder->where('purity_status', (string) $status);
        }

        return $builder->orderBy('purity_value', 'ASC')
                       ->orderBy('purity_id', 'DESC')
                       ->paginate($perPage);
    }

    /**
     * Find single purity record by private_key (32-character GUID).
     *
     * @param string $privateKey
     * @return array|null
     */
    public function getByPrivateKey(string $privateKey): ?array
    {
        return $this->where('private_key', $privateKey)->first();
    }

    /**
     * Check if a purity name or value already exists (excluding specified record).
     *
     * @param string      $purityName
     * @param float       $purityValue
     * @param string|null $excludePrivateKey
     * @return bool
     */
    public function isNameOrValueExists(string $purityName, float $purityValue, ?string $excludePrivateKey = null): bool
    {
        $builder = $this->groupStart()
                        ->where('LOWER(purity_name)', strtolower(trim($purityName)))
                        ->orWhere('ABS(purity_value - ' . (float)$purityValue . ') <', 0.0001)
                        ->groupEnd();

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
    public function togglePurityStatus(string $privateKey): array
    {
        $purity = $this->getByPrivateKey($privateKey);
        if (!$purity) {
            return ['success' => false, 'message' => 'Purity record not found.'];
        }

        $newStatus = ($purity['purity_status'] === '1') ? '0' : '1';
        $this->update($purity['purity_id'], ['purity_status' => $newStatus]);

        $action = ($newStatus === '1') ? 'activated' : 'deactivated';
        return [
            'success'   => true,
            'newStatus' => $newStatus,
            'message'   => "Purity '{$purity['purity_name']}' successfully {$action}."
        ];
    }

    /**
     * Retrieve summary statistics for purities.
     *
     * @return array{total_purities: int, total_active: int, total_inactive: int}
     */
    public function getSummaryStats(): array
    {
        $row = $this->db->query("
            SELECT 
                COUNT(*) as total_purities,
                COALESCE(SUM(CASE WHEN purity_status = '1' THEN 1 ELSE 0 END), 0) as total_active,
                COALESCE(SUM(CASE WHEN purity_status = '0' THEN 1 ELSE 0 END), 0) as total_inactive
            FROM {$this->table}
        ")->getRowArray();

        return [
            'total_purities' => (int)($row['total_purities'] ?? 0),
            'total_active'   => (int)($row['total_active'] ?? 0),
            'total_inactive' => (int)($row['total_inactive'] ?? 0),
        ];
    }

    /**
     * Get all active purities ordered by purity value (combines SpiderWaves + UCC).
     */
    public function getActivePurities(): array
    {
        return (new \App\Services\UnifiedMasterService($this->db))->getActivePurities();
    }
}

