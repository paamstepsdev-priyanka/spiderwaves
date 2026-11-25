<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * FirmModel
 * 
 * Manages external customer firm entities (table: firm).
 */
class FirmModel extends Model
{
    protected $table            = 'firm';
    protected $primaryKey       = 'firm_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'private_key',
        'firm_name',
        'address_1',
        'address_2',
        'address_3',
        'mobile_no',
        'firm_status',
    ];

    protected $useTimestamps = false;

    /**
     * Get active firms ordered by firm name.
     * 
     * @return array
     */
    public function getActiveFirms(): array
    {
        return $this->where('firm_status', '1')
            ->orderBy('firm_name', 'ASC')
            ->findAll();
    }
}
