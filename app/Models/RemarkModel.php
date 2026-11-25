<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * RemarkModel
 *
 * Handles database operations for Master Remarks (table: remark).
 * Remarks serve as standardized descriptors across transactions (waves ledger, raw waves in/out).
 */
class RemarkModel extends Model
{
    protected $table            = 'remark';
    protected $primaryKey       = 'remark_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'remark',
        'status',
    ];

    protected $useTimestamps = false;

    // Validation rules
    protected $validationRules = [
        'remark' => 'required|min_length[2]|max_length[1000]',
        'status' => 'required|in_list[0,1]',
    ];

    protected $validationMessages = [
        'remark' => [
            'required'   => 'Remark text is required.',
            'min_length' => 'Remark text must be at least 2 characters.',
            'max_length' => 'Remark text cannot exceed 1000 characters.',
        ],
        'status' => [
            'required' => 'Please select a status.',
            'in_list'  => 'Status must be Active (1) or Inactive (0).',
        ],
    ];

    /**
     * Get paginated remarks with optional search and status filters.
     *
     * @param int         $perPage
     * @param string|null $search
     * @param string|null $status
     * @return array
     */
    public function getPaginatedRemarks(int $perPage = 10, ?string $search = null, ?string $status = null): array
    {
        if (!empty($search)) {
            $this->like('remark', trim($search));
        }

        if ($status !== null && $status !== '') {
            $this->where('status', $status);
        }

        return $this->orderBy('remark_id', 'DESC')->paginate($perPage);
    }

    /**
     * Check if a remark text already exists (case-insensitive and trimmed).
     *
     * @param string   $remark
     * @param int|null $excludeId
     * @param bool     $activeOnly Check active records only if true (legacy behavior)
     * @return bool
     */
    public function isRemarkExists(string $remark, ?int $excludeId = null, bool $activeOnly = false): bool
    {
        $this->where('LOWER(TRIM(remark))', strtolower(trim($remark)));

        if ($excludeId !== null && $excludeId > 0) {
            $this->where('remark_id !=', $excludeId);
        }

        if ($activeOnly) {
            $this->where('status', '1');
        }

        return $this->countAllResults() > 0;
    }

    /**
     * Soft toggle between Active (1) and Inactive (0) status.
     *
     * @param int $remarkId
     * @return array{success: bool, newStatus?: string, message: string}
     */
    public function toggleRemarkStatus(int $remarkId): array
    {
        $remark = $this->find($remarkId);
        if (!$remark) {
            return ['success' => false, 'message' => 'Remark record not found.'];
        }

        $newStatus = ($remark['status'] === '1') ? '0' : '1';
        $this->update($remarkId, ['status' => $newStatus]);

        $action = ($newStatus === '1') ? 'activated' : 'deactivated';
        return [
            'success'   => true,
            'newStatus' => $newStatus,
            'message'   => "Remark '{$remark['remark']}' successfully {$action}."
        ];
    }

    /**
     * Retrieve summary counts for dashboard or header stats.
     *
     * @return array{total_remarks: int, total_active: int, total_inactive: int}
     */
    public function getSummaryStats(): array
    {
        $row = $this->db->query("
            SELECT 
                COUNT(*) as total_remarks,
                COALESCE(SUM(CASE WHEN status = '1' THEN 1 ELSE 0 END), 0) as total_active,
                COALESCE(SUM(CASE WHEN status = '0' THEN 1 ELSE 0 END), 0) as total_inactive
            FROM {$this->table}
        ")->getRowArray();

        return [
            'total_remarks'  => (int)($row['total_remarks'] ?? 0),
            'total_active'   => (int)($row['total_active'] ?? 0),
            'total_inactive' => (int)($row['total_inactive'] ?? 0),
        ];
    }

    /**
     * Get active remarks for transaction dropdown selections.
     *
     * @return array
     */
    public function getActiveRemarks(): array
    {
        return $this->where('status', '1')
                    ->orderBy('remark', 'ASC')
                    ->findAll();
    }
}
