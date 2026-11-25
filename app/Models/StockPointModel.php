<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * StockPointModel
 * 
 * Manages periodic stock reconciliation checkpoints and frozen closing balance snapshots.
 * Tables: stock_point, closing_balance
 */
class StockPointModel extends Model
{
    protected $table            = 'stock_point';
    protected $primaryKey       = 'stock_point_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'date_time',
    ];

    protected $useTimestamps = false;

    /**
     * Get latest stock point reconciliation checkpoint.
     */
    public function getLatestStockPoint(): ?array
    {
        return $this->orderBy('stock_point_id', 'DESC')->first();
    }

    /**
     * Insert new stock point milestone.
     */
    public function insertStockPoint(string $dateTime): int
    {
        $this->insert(['date_time' => $dateTime]);
        return (int) $this->getInsertID();
    }

    /**
     * Insert departmental closing balance snapshot.
     */
    public function insertClosingBalance(int $stockPointId, int $accountId, float $mtClosingBalance): int
    {
        $db = \Config\Database::connect();
        $builder = $db->table('closing_balance');
        $builder->insert([
            'stock_point_id'     => $stockPointId,
            'account_id'         => $accountId,
            'mt_closing_balance' => $mtClosingBalance,
        ]);
        return (int) $db->insertID();
    }

    /**
     * Get last closing balance for an account before the latest stock checkpoint.
     */
    public function getLastClosingBalance(int $accountId): float
    {
        $db = \Config\Database::connect();
        $row = $db->table('closing_balance')
            ->select('mt_closing_balance')
            ->where('account_id', $accountId)
            ->orderBy('closing_balance_id', 'DESC')
            ->limit(1)
            ->get()
            ->getRow();

        return $row ? (float) $row->mt_closing_balance : 0.0;
    }
}
