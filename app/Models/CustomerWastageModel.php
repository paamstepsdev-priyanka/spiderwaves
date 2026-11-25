<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CustomerWastageModel
 *
 * Manages customer collection-wise wastage percentages in table 'cust_wastage'.
 */
class CustomerWastageModel extends Model
{
    protected $table            = 'mst_cust_wastage';
    protected $primaryKey       = 'cw_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'customer_id',
        'collection_id',
        'wastage',
    ];

    protected $useTimestamps = false;

    /**
     * Retrieve all wastage values for a specific customer keyed by collection_id.
     *
     * @param int $customerId
     * @return array [collection_id => wastage]
     */
    public function getWastageMapByCustomerId(int $customerId): array
    {
        $rows = $this->where('customer_id', $customerId)->findAll();
        $map  = [];

        foreach ($rows as $row) {
            $map[(int)$row['collection_id']] = (float)$row['wastage'];
        }

        return $map;
    }

    /**
     * Replace customer wastage mapping in an atomic transaction.
     *
     * @param int $customerId
     * @param array $inputData [collection_id => wastage_value]
     * @param array $activeCollections List of active collection records
     * @return bool
     */
    public function saveCustomerWastage(int $customerId, array $inputData, array $activeCollections): bool
    {
        $this->db->transBegin();

        try {
            // Delete existing wastage records for this customer
            $this->where('customer_id', $customerId)->delete();

            // Insert new wastage rows for all active collections
            foreach ($activeCollections as $col) {
                $colId   = (int)$col['collection_id'];
                $rawVal  = $inputData[$colId] ?? $inputData[(string)$colId] ?? 0;
                $wastage = is_numeric($rawVal) ? (float)$rawVal : 0.0;

                $this->insert([
                    'customer_id'   => $customerId,
                    'collection_id' => $colId,
                    'wastage'       => $wastage,
                ]);
            }

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                return false;
            }

            $this->db->transCommit();
            return true;

        } catch (\Throwable $e) {
            $this->db->transRollback();
            return false;
        }
    }

    /**
     * Get wastage value for a mapped customer ID.
     *
     * @param int $mappedCustId
     * @return float|null
     */
    public function getWastageForMappedCustomer(int $mappedCustId): ?float
    {
        $mapping = $this->db->table('mst_cust_mapping')
            ->select('cust_id, collection_id')
            ->where('mapped_cust_id', $mappedCustId)
            ->where('mapped_cust_status', '1')
            ->get()
            ->getRowArray();

        if (!$mapping) {
            return null;
        }

        $row = $this->where('customer_id', $mapping['cust_id'])
            ->where('collection_id', $mapping['collection_id'])
            ->first();

        return $row ? (float)$row['wastage'] : 0.0;
    }
}
