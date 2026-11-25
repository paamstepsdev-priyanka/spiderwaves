<?php

namespace App\Services;

use App\Models\RawWavesModel;
use App\Models\ProcessWavesModel;
use App\Models\AccountModel;
use App\Models\StockPointModel;

/**
 * LedgerService
 * 
 * Central business calculation, stock validation, and verification service for SpiderWaves:
 * - Fine weight calculations for MT, OR, and RK transaction modes
 * - Net weight and stone percentage calculations
 * - Scale weight tolerance verification (<= 0.030g / 30 mg)
 * - Unique verification code & GUID generation
 * - Departmental process account balance calculation (Fine MT, Gross MT, Pieces)
 * - Inter-department stock sufficiency validation
 * - Process loss & checkpoint validation
 */
class LedgerService
{
    /**
     * Scale weight tolerance in grams: 30 mg = 0.030g
     */
    public const SCALE_TOLERANCE_GRAMS = 0.030;

    /**
     * Accounts that allow negative loss balances during workshop processing.
     * Setting (5), Rodium (7), Casting (2), G58 Setting (14), Galai (20), Tash (21).
     */
    public const NEGATIVE_LOSS_ACCOUNTS = [5, 7, 2, 14, 20, 21];

    /**
     * Calculate Fine Gold Weight based on transaction mode.
     * 
     * MT Mode:
     *   Fine Weight = Net Weight * Purity / 100
     * 
     * OR Mode:
     *   Fine Weight = Net Weight * (Purity + Wastage) / 100
     * 
     * RK Mode:
     *   Cash/Rupee transaction (metal fine weight = 0.000)
     * 
     * @param float  $netWeight Net metal weight in grams
     * @param float  $purity    Base purity percentage (e.g., 75.0, 92.0)
     * @param float  $wastage   Wastage percentage for OR mode (e.g., 2.50)
     * @param string $mode      Transaction mode ('MT', 'OR', 'RK')
     * @return float Milligram precision (3 decimal places)
     */
    public function calculateFineWeight(float $netWeight, float $purity, float $wastage = 0.0, string $mode = 'MT'): float
    {
        $mode = strtoupper(trim($mode));

        if ($mode === 'RK' || $netWeight <= 0) {
            return 0.000;
        }

        if ($mode === 'OR') {
            $effectivePurity = $purity + $wastage;
            return round(($netWeight * $effectivePurity) / 100, 3);
        }

        // Default MT mode
        return round(($netWeight * $purity) / 100, 3);
    }

    /**
     * Calculate Net Weight from Gross Weight and Less Weight.
     * Formula: Net Weight = Gross Weight - Less Weight
     */
    public function calculateNetWeight(float $grossWeight, float $lessWeight = 0.0): float
    {
        $net = $grossWeight - $lessWeight;
        return round(max(0, $net), 3);
    }

    /**
     * Calculate Stone / Less Percentage.
     * Formula: Stone % = (Less Weight / Net Weight) * 100
     */
    public function calculateStonePercentage(float $lessWeight, float $netWeight): float
    {
        if ($netWeight <= 0 || $lessWeight <= 0) {
            return 0.0;
        }

        return round(($lessWeight / $netWeight) * 100, 1);
    }

    /**
     * Verify scale weight reading against entered gross weight.
     * Documented tolerance: <= 30 mg (0.030 gram).
     */
    public function verifyScaleWeight(float $grossWeight, float $scaleWeight, float $tolerance = self::SCALE_TOLERANCE_GRAMS): array
    {
        $variance = abs(round(($scaleWeight * 1000) - ($grossWeight * 1000)) / 1000);
        $isValid = $variance <= $tolerance;

        return [
            'valid'     => $isValid,
            'variance'  => $variance,
            'tolerance' => $tolerance,
            'message'   => $isValid 
                ? 'Scale weight verified within permissible tolerance.' 
                : sprintf('Scale weight variance (%.3fg) exceeds maximum tolerance of %.3fg (30 mg).', $variance, $tolerance)
        ];
    }

    /**
     * Generate a unique 5-digit verification code.
     */
    public function generateVerificationCode(?RawWavesModel $rawWavesModel = null): string
    {
        if ($rawWavesModel === null && class_exists(RawWavesModel::class)) {
            try {
                $rawWavesModel = new RawWavesModel();
            } catch (\Throwable $e) {
                $rawWavesModel = null;
            }
        }
        
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $code = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
            if ($rawWavesModel) {
                try {
                    $exists = $rawWavesModel->where('verification_code', $code)
                        ->where('is_deleted', '0')
                        ->first();
                    if (!$exists) {
                        return $code;
                    }
                } catch (\Throwable $e) {
                    return $code;
                }
            } else {
                return $code;
            }
        }

        return str_pad((string) (time() % 100000), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a 32-character hexadecimal GUID for transactions.
     */
    public function generateGuid(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (\Throwable $e) {
            return md5(uniqid((string) mt_rand(), true));
        }
    }

    /**
     * Get active stock point start date from session or database.
     */
    public function getActiveStockPointDate(?string $providedDate = null): string
    {
        if ($providedDate && trim($providedDate) !== '') {
            return $providedDate;
        }

        $session = session();
        $spiderSession = $session->get('spider');
        if (!empty($spiderSession['stock_point_start_date_time'])) {
            return $spiderSession['stock_point_start_date_time'];
        }

        $stockPointModel = model(StockPointModel::class);
        $sp = $stockPointModel->getLatestStockPoint();
        return $sp['date_time'] ?? date('Y-m-d 00:00:00');
    }

    /**
     * Get Total Receive Balance for a workshop account.
     * Preserves exact SQL filtering from legacy common_func_helper::get_account_total_receive_balance().
     */
    public function getAccountTotalReceiveBalance(int $accountId, ?int $fromAccountId = null, string $balanceType = 'fine', ?string $startDate = null): float
    {
        $startDate = $this->getActiveStockPointDate($startDate);
        $processWavesModel = model(ProcessWavesModel::class);

        if ($accountId === AccountModel::LOSS_ID) {
            $filter = "to_id = " . $accountId . " AND further_to_process_waves_id IS NULL";
            if ($fromAccountId) {
                $filter = "from_id = " . $fromAccountId . " AND to_id = " . $accountId . " AND further_to_process_waves_id IS NULL";
            }
        } elseif ($accountId === AccountModel::REFINING_ID) {
            $filter = "to_id = " . $accountId;
            if ($fromAccountId) {
                $filter = "from_id = " . $fromAccountId . " AND to_id = " . $accountId;
            }
        } elseif ($accountId === AccountModel::STOCK_ID) {
            $filter = "to_id = " . $accountId . " AND (date >= '" . $startDate . "' OR (inner_transfer = '0' OR stock_inner_transfer = '0')) AND further_to_process_waves_id IS NULL";
        } else {
            $filter = "to_id = " . $accountId . " AND date >= '" . $startDate . "'";
        }

        return $processWavesModel->getBalanceSum($filter, $balanceType);
    }

    /**
     * Get Total Issue Balance for a workshop account.
     * Preserves exact SQL filtering from legacy common_func_helper::get_account_total_issue_balance().
     */
    public function getAccountTotalIssueBalance(int $accountId, ?int $fromAccountId = null, string $balanceType = 'fine', ?string $startDate = null): float
    {
        $startDate = $this->getActiveStockPointDate($startDate);
        $processWavesModel = model(ProcessWavesModel::class);

        if ($accountId === AccountModel::LOSS_ID) {
            $filter = "from_id = " . $accountId;
            if ($fromAccountId) {
                $filter .= " AND loss_account_id = " . $fromAccountId;
            }
        } elseif ($accountId === AccountModel::REFINING_ID) {
            $filter = "from_id = " . $accountId;
            if ($fromAccountId) {
                $filter .= " AND to_id = " . $fromAccountId;
            }
        } elseif ($accountId === AccountModel::STOCK_ID) {
            $filter = "from_id = " . $accountId . " AND (date >= '" . $startDate . "' OR inner_transfer = '0') AND further_to_process_waves_id IS NULL";
        } else {
            $filter = "from_id = " . $accountId . " AND date >= '" . $startDate . "'";
        }

        return $processWavesModel->getBalanceSum($filter, $balanceType);
    }

    /**
     * Get opening balance for an account from closing_balance snapshots.
     */
    public function getOpeningBalance(int $accountId): float
    {
        $stockPointModel = model(StockPointModel::class);
        return $stockPointModel->getLastClosingBalance($accountId);
    }

    /**
     * Calculate net balance for an account.
     * Balance = (Receive + Opening) - Issue
     */
    public function getAccountNetBalance(int $accountId, ?int $fromAccountId = null, string $balanceType = 'fine', ?string $startDate = null): float
    {
        $opening = $this->getOpeningBalance($accountId);
        $receive = $this->getAccountTotalReceiveBalance($accountId, $fromAccountId, $balanceType, $startDate);
        $issue   = $this->getAccountTotalIssueBalance($accountId, $fromAccountId, $balanceType, $startDate);

        return round(($receive + $opening) - $issue, 3);
    }

    /**
     * Get Total Pieces Received for an account.
     */
    public function getAccountTotalPcsReceiveBalance(int $accountId, ?int $mappedCustId = null, ?string $startDate = null): int
    {
        $startDate = $this->getActiveStockPointDate($startDate);
        $processWavesModel = model(ProcessWavesModel::class);

        if ($accountId === AccountModel::LOSS_ID || $accountId === AccountModel::REFINING_ID) {
            $filter = "to_id = " . $accountId;
            if ($accountId === AccountModel::LOSS_ID) {
                $filter = "to_id = " . $accountId . " AND further_to_process_waves_id IS NULL";
            }
        } elseif ($accountId === AccountModel::STOCK_ID) {
            $filter = "to_id = " . $accountId . " AND (date >= '" . $startDate . "' OR (inner_transfer = '0' OR stock_inner_transfer = '0')) AND further_to_process_waves_id IS NULL";
        } else {
            $filter = "to_id = " . $accountId . " AND date >= '" . $startDate . "'";
        }

        if ($mappedCustId) {
            $filter .= " AND mapped_cust_id = " . (int)$mappedCustId;
        }

        return $processWavesModel->getPcsSum($filter);
    }

    /**
     * Get Total Pieces Issued for an account.
     */
    public function getAccountTotalPcsIssueBalance(int $accountId, ?int $mappedCustId = null, ?string $startDate = null): int
    {
        $startDate = $this->getActiveStockPointDate($startDate);
        $processWavesModel = model(ProcessWavesModel::class);

        if ($accountId === AccountModel::LOSS_ID || $accountId === AccountModel::REFINING_ID) {
            $filter = "from_id = " . $accountId;
        } elseif ($accountId === AccountModel::STOCK_ID) {
            $filter = "from_id = " . $accountId . " AND (date >= '" . $startDate . "' OR inner_transfer = '0') AND further_to_process_waves_id IS NULL";
        } else {
            $filter = "from_id = " . $accountId . " AND date >= '" . $startDate . "'";
        }

        if ($mappedCustId) {
            $filter .= " AND mapped_cust_id = " . (int)$mappedCustId;
        }

        return $processWavesModel->getPcsSum($filter);
    }

    /**
     * Validate source account stock sufficiency before inter-department transfer.
     * Enforces strict non-negative stock verification across all manufacturing stages,
     * incorporating opening balance snapshots to guarantee physical and digital audit integrity.
     */
    public function checkWeight(int $fromId, int $toId, float $grossWeight, float $purity = 100.0, ?string $startDate = null): bool
    {
        if ($grossWeight <= 0.0) {
            return false;
        }

        $startDate = $this->getActiveStockPointDate($startDate);
        // Net balance strictly incorporates (Opening Balance + Total Received) - Total Issued
        $balance   = $this->getAccountNetBalance($fromId, null, 'fine', $startDate);
        $mtBalance = round(($grossWeight * $purity) / 100, 3);

        return ($balance >= $mtBalance);
    }

    /**
     * Validate loss percentage for process account.
     */
    public function checkLossValidation(int $accountId, string $accountName, ?string $startDate = null): array
    {
        $receive = $this->getAccountTotalReceiveBalance($accountId, null, 'fine', $startDate);
        $issue   = $this->getAccountTotalIssueBalance($accountId, null, 'fine', $startDate);
        $balance = round($receive - $issue, 3);

        $lossPercentage = ($balance != 0 && $receive != 0) 
            ? round(($balance / $receive) * 100, 2) 
            : 0.0;

        $accountModel = model(AccountModel::class);
        $setLossPercentage = $accountModel->getLossPercentage($accountId);

        if ($lossPercentage <= $setLossPercentage) {
            return ['status' => 'true', 'msg' => ''];
        }

        return [
            'status' => 'loss_percentage_error',
            'msg'    => $accountName . '(' . number_format($lossPercentage, 2) . '%)'
        ];
    }

    /**
     * Validate all departmental stock balances prior to creating a stock checkpoint.
     * Preserves exact legacy rules from Process_waves::check_stock_validation().
     */
    public function checkStockValidation(?string $startDate = null): array
    {
        $accounts = [
            AccountModel::CASTING_ID          => 'Casting',
            AccountModel::FILING_ID           => 'Filing',
            AccountModel::G58_FILING_ID       => 'g58_filing',
            AccountModel::HEERA_LIGHT_ID      => 'Heera Light',
            AccountModel::CNC_ID              => 'Megnet',
            AccountModel::POLISH_ID           => 'Polish',
            AccountModel::G58_POLISH_ID       => 'g58_polish',
            AccountModel::RODIUM_ID           => 'Rodium',
            AccountModel::SETTING_ID          => 'Setting',
            AccountModel::G58_SETTING_ID      => 'g58_setting',
            AccountModel::PACKING_ID          => 'Packing',
            AccountModel::TRANSFER_PACKING_ID => 'Transfer Packing',
            AccountModel::PO_CHECK_ID         => 'PO Check',
            AccountModel::G58_TRANSFER_ID     => 'g58_transfer',
            AccountModel::ZERO_BALANCE_ID     => 'Zero Balance',
            AccountModel::GALAI_ID            => 'galai',
            AccountModel::TASH_ID             => 'tash',
            AccountModel::SAPPHIRE_ID         => 'sapphire',
        ];

        $errors = [];

        foreach ($accounts as $id => $name) {
            $receive = $this->getAccountTotalReceiveBalance($id, null, 'fine', $startDate);
            $issue   = $this->getAccountTotalIssueBalance($id, null, 'fine', $startDate);
            $balance = round($receive - $issue, 3);

            if ($balance != 0) {
                $lossPct = ($receive > 0) ? round(($balance / $receive) * 100, 2) : 0;
                $errors[] = $name . '(' . number_format($lossPct, 2) . '%)';
            }
        }

        if (empty($errors)) {
            return ['status' => 'true', 'msg' => ''];
        }

        return [
            'status' => 'loss_percentage_error',
            'msg'    => implode(', ', $errors)
        ];
    }

    /**
     * Compute total company balances across MT, OR, and RK modes.
     */
    public function getTotalBalances(): array
    {
        $wavesModel = model(\App\Models\WavesModel::class);
        $customerModel = model(\App\Models\CustomerModel::class);

        $totals = $wavesModel->getBalanceTotalsByMode();
        $closing = $customerModel->getClosingTotalBalances();

        $mtReceipt = (float)($totals['mt_receipt'] ?? 0);
        $mtIssue   = (float)($totals['mt_issue'] ?? 0);
        $orReceipt = (float)($totals['or_receipt'] ?? 0);
        $orIssue   = (float)($totals['or_issue'] ?? 0);
        $rkReceipt = (float)($totals['rk_receipt'] ?? 0);
        $rkIssue   = (float)($totals['rk_issue'] ?? 0);

        $mtBal = ($mtReceipt - $mtIssue) + $closing['mt_total'];
        $orBal = ($orReceipt - $orIssue) + $closing['or_total'];
        $rkBal = ($rkReceipt - $rkIssue) + $closing['rk_total'];

        return [
            'mt_total_balance' => round($mtBal, 3),
            'or_total_balance' => round($orBal, 3),
            'rk_total_balance' => round($rkBal, 2),
            'breakdown' => [
                'mt_receipt' => round($mtReceipt, 3),
                'mt_issue'   => round($mtIssue, 3),
                'mt_closing' => round($closing['mt_total'], 3),
                'or_receipt' => round($orReceipt, 3),
                'or_issue'   => round($orIssue, 3),
                'or_closing' => round($closing['or_total'], 3),
                'rk_receipt' => round($rkReceipt, 2),
                'rk_issue'   => round($rkIssue, 2),
                'rk_closing' => round($closing['rk_total'], 2),
            ]
        ];
    }

    /**
     * Get Stock Report data for all departments matching legacy report/loss_report.
     * Uses optimized SQL aggregation to prevent N+1 queries over process_waves.
     *
     * @param string|null $startDate Optional checkpoint timestamp override
     * @return array{stock_point_date: string, items: array, total_balance: float, display_total: string}
     */
    public function getStockReportData(?string $startDate = null): array
    {
        $startDate = $this->getActiveStockPointDate($startDate);
        $accountModel = model(AccountModel::class);
        $db = \Config\Database::connect();

        $accountIds = $accountModel->getStockReportAccountIds();

        // 1. Fetch account details in bulk
        $accounts = $accountModel->whereIn('account_id', $accountIds)->findAll();
        $accountMap = [];
        foreach ($accounts as $acc) {
            $accountMap[(int)$acc['account_id']] = $acc;
        }

        // Standard process accounts (excluding Refining 9, Loss 12, Locker 1)
        $standardIds = [2, 3, 4, 5, 6, 7, 8, 13, 14, 15, 16, 17, 20, 21, 22, 23];
        $standardIdsList = implode(',', $standardIds);

        // 2. High-performance batch query for receive balances
        $receiveSql = "SELECT 
            to_id, 
            SUM((gross_weight * purity) / 100) AS total_receive
        FROM process_waves 
        WHERE 
            (to_id IN ({$standardIdsList}) AND date >= ?)
            OR (to_id = ?)
            OR (to_id = ? AND further_to_process_waves_id IS NULL)
            OR (to_id = ? AND (date >= ? OR (inner_transfer = '0' OR stock_inner_transfer = '0')) AND further_to_process_waves_id IS NULL)
        GROUP BY to_id";

        $receiveRows = $db->query($receiveSql, [
            $startDate,
            AccountModel::REFINING_ID,
            AccountModel::LOSS_ID,
            AccountModel::STOCK_ID,
            $startDate
        ])->getResultArray();

        $receives = [];
        foreach ($receiveRows as $row) {
            $receives[(int)$row['to_id']] = (float)$row['total_receive'];
        }

        // 3. High-performance batch query for issue balances
        $issueSql = "SELECT 
            from_id, 
            SUM((gross_weight * purity) / 100) AS total_issue
        FROM process_waves 
        WHERE 
            (from_id IN ({$standardIdsList}) AND date >= ?)
            OR (from_id = ?)
            OR (from_id = ?)
            OR (from_id = ? AND (date >= ? OR inner_transfer = '0') AND further_to_process_waves_id IS NULL)
        GROUP BY from_id";

        $issueRows = $db->query($issueSql, [
            $startDate,
            AccountModel::REFINING_ID,
            AccountModel::LOSS_ID,
            AccountModel::STOCK_ID,
            $startDate
        ])->getResultArray();

        $issues = [];
        foreach ($issueRows as $row) {
            $issues[(int)$row['from_id']] = (float)$row['total_issue'];
        }

        // 4. Assemble rows in exact legacy sequence
        $reportData = [];
        $sno = 1;
        $totalBalance = 0.0;

        foreach ($accountIds as $accId) {
            if (!isset($accountMap[$accId])) {
                continue;
            }
            $acc = $accountMap[$accId];
            $rec = $receives[$accId] ?? 0.0;
            $iss = $issues[$accId] ?? 0.0;
            $bal = $rec - $iss;
            $totalBalance += $bal;

            $reportData[] = [
                'sno'             => $sno++,
                'account_id'      => $accId,
                'account_name'    => trim($acc['account_name']),
                'private_key'     => $acc['private_key'],
                'receive_balance' => $rec,
                'issue_balance'   => $iss,
                'balance'         => $bal,
                'display_balance' => view_round_up($bal, 3),
            ];
        }

        return [
            'stock_point_date' => $startDate,
            'items'            => $reportData,
            'total_balance'    => $totalBalance,
            'display_total'    => view_round_up($totalBalance, 3),
        ];
    }

    /**
     * Customer-Wise Report: high-performance grouped SQL aggregation.
     * Computes net combined metal balance (MT + OR) for all customers without N+1 queries.
     *
     * @return array{items: array, total_count: int, total_balance: float, display_total: string}
     */
    public function getCustomerWiseReportData(): array
    {
        $db = \Config\Database::connect('default');

        $sql = "
            SELECT 
                c.cust_id,
                CONVERT(c.cust_name USING utf8mb4) COLLATE utf8mb4_general_ci AS cust_name,
                CONVERT(c.private_key USING utf8mb4) COLLATE utf8mb4_general_ci AS private_key,
                CONVERT(COALESCE(c.mobile_no, '') USING utf8mb4) COLLATE utf8mb4_general_ci AS mobile_no,
                CONVERT(COALESCE(c.address_1, '') USING utf8mb4) COLLATE utf8mb4_general_ci AS address_1,
                CONVERT(COALESCE(c.address_2, '') USING utf8mb4) COLLATE utf8mb4_general_ci AS address_2,
                CONVERT(COALESCE(c.address_3, '') USING utf8mb4) COLLATE utf8mb4_general_ci AS address_3,
                c.MT_closing_balance,
                c.OR_closing_balance,
                c.RK_closing_balance,
                COALESCE(w.total_receipt, 0) AS total_receipt,
                COALESCE(w.total_issue, 0) AS total_issue,
                (c.MT_closing_balance + c.OR_closing_balance + COALESCE(w.total_receipt, 0) - COALESCE(w.total_issue, 0)) AS net_balance
            FROM mst_customer c
            LEFT JOIN (
                SELECT 
                    cust_id,
                    SUM(CASE 
                        WHEN transactional_type = 'receipt' AND transactional_mode = 'MT' THEN (net_weight * purity) / 100
                        WHEN transactional_type = 'receipt' AND transactional_mode = 'OR' THEN (net_weight * (purity + wastage)) / 100
                        ELSE 0 END) AS total_receipt,
                    SUM(CASE 
                        WHEN transactional_type = 'issue' AND transactional_mode = 'MT' THEN (net_weight * purity) / 100
                        WHEN transactional_type = 'issue' AND transactional_mode = 'OR' THEN (net_weight * (purity + wastage)) / 100
                        ELSE 0 END) AS total_issue
                FROM waves
                WHERE approved_data = '1'
                  AND is_deleted = '0'
                  AND (transactional_mode = 'MT' OR transactional_mode = 'OR')
                GROUP BY cust_id
            ) w ON c.cust_id = w.cust_id
            UNION ALL
            SELECT
                u.customer_id AS cust_id,
                CONVERT(u.customer_name USING utf8mb4) COLLATE utf8mb4_general_ci AS cust_name,
                u.private_key,
                '' AS mobile_no,
                '' AS address_1,
                '' AS address_2,
                '' AS address_3,
                0 AS MT_closing_balance,
                0 AS OR_closing_balance,
                0 AS RK_closing_balance,
                0 AS total_receipt,
                0 AS total_issue,
                0 AS net_balance
            FROM ucc_customer u
            WHERE u.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM mst_customer m2
                  WHERE CONVERT(m2.private_key USING utf8mb4) COLLATE utf8mb4_general_ci = u.private_key
                     OR LOWER(TRIM(CONVERT(m2.cust_name USING utf8mb4))) COLLATE utf8mb4_general_ci = LOWER(TRIM(u.customer_name)) COLLATE utf8mb4_general_ci
              )
            ORDER BY cust_name ASC
        ";

        $rows = $db->query($sql)->getResultArray();
        $items = [];
        $sno = 1;
        $totalBalance = 0.0;

        foreach ($rows as $row) {
            $bal = (float)$row['net_balance'];
            $totalBalance += $bal;

            $items[] = [
                'sno'             => $sno++,
                'cust_id'         => (int)$row['cust_id'],
                'cust_name'       => trim($row['cust_name']),
                'private_key'     => $row['private_key'],
                'mobile_no'       => trim($row['mobile_no'] ?? ''),
                'city'            => trim($row['address_3'] ?: ($row['address_2'] ?: $row['address_1'])),
                'total_receipt'   => (float)$row['total_receipt'],
                'total_issue'     => (float)$row['total_issue'],
                'balance'         => $bal,
                'display_balance' => view_round_up($bal, 3),
            ];
        }

        return [
            'items'         => $items,
            'total_count'   => count($items),
            'total_balance' => $totalBalance,
            'display_total' => view_round_up($totalBalance, 3),
        ];
    }

    /**
     * Customer Ledger: fetch receipts, issues, running balance and opening balance.
     * Respects legacy CI2 transactional rules and supports optional from_date and to_date filters.
     *
     * @param string      $privateKey Customer 32-char private key
     * @param string|null $fromDate   Filter start date (Y-m-d)
     * @param string|null $toDate     Filter end date (Y-m-d)
     * @return array|null Null if customer not found
     */
    public function getCustomerLedgerData(string $privateKey, ?string $fromDate = null, ?string $toDate = null): ?array
    {
        $db = \Config\Database::connect('default');

        // 1. Fetch customer profile (SpiderWaves first, fallback to UCC synced customer)
        $cust = $db->table('mst_customer')->where('private_key', $privateKey)->get()->getRowArray();

        if (!$cust) {
            $uccCust = $db->table('ucc_customer')
                ->where('private_key', $privateKey)
                ->where('deleted_at IS NULL')
                ->get()
                ->getRowArray();

            if (!$uccCust) {
                return null;
            }

            $cust = [
                'cust_id'            => (int)$uccCust['customer_id'],
                'private_key'        => $uccCust['private_key'],
                'cust_name'          => $uccCust['customer_name'],
                'mobile_no'          => '',
                'address_1'          => '',
                'address_2'          => '',
                'address_3'          => '',
                'MT_closing_balance' => 0,
                'OR_closing_balance' => 0,
                'RK_closing_balance' => 0,
                'is_ucc_only'        => true,
            ];
        }

        // Avoid ID collision on waves table for UCC-only customers not yet in mst_customer
        $custId   = !empty($cust['is_ucc_only']) ? -1 : (int)$cust['cust_id'];
        $fromDate = !empty($fromDate) ? trim($fromDate) : null;
        $toDate   = !empty($toDate) ? trim($toDate) : null;

        $baseMt = (float)$cust['MT_closing_balance'];
        $baseOr = (float)$cust['OR_closing_balance'];
        $baseRk = (float)$cust['RK_closing_balance'];

        // 2. Compute Opening Balances prior to from_date if date filter is active
        if ($fromDate) {
            $priorSql = "
                SELECT 
                    SUM(CASE WHEN transactional_type='receipt' AND transactional_mode='MT' THEN (net_weight * purity) / 100 ELSE 0 END) AS prior_rec_mt,
                    SUM(CASE WHEN transactional_type='issue' AND transactional_mode='MT' THEN (net_weight * purity) / 100 ELSE 0 END) AS prior_iss_mt,
                    SUM(CASE WHEN transactional_type='receipt' AND transactional_mode='OR' THEN (net_weight * (purity + wastage)) / 100 ELSE 0 END) AS prior_rec_or,
                    SUM(CASE WHEN transactional_type='issue' AND transactional_mode='OR' THEN (net_weight * (purity + wastage)) / 100 ELSE 0 END) AS prior_iss_or,
                    SUM(CASE WHEN transactional_type='receipt' AND transactional_mode='RK' THEN amount ELSE 0 END) AS prior_rec_rk,
                    SUM(CASE WHEN transactional_type='issue' AND transactional_mode='RK' THEN amount ELSE 0 END) AS prior_iss_rk
                FROM waves
                WHERE cust_id = ?
                  AND approved_data = '1'
                  AND is_deleted = '0'
                  AND entry_date < ?
            ";
            $prior = $db->query($priorSql, [$custId, $fromDate])->getRowArray();

            $mtOpening = $baseMt + ((float)($prior['prior_rec_mt'] ?? 0) - (float)($prior['prior_iss_mt'] ?? 0));
            $orOpening = $baseOr + ((float)($prior['prior_rec_or'] ?? 0) - (float)($prior['prior_iss_or'] ?? 0));
            $rkOpening = $baseRk + ((float)($prior['prior_rec_rk'] ?? 0) - (float)($prior['prior_iss_rk'] ?? 0));
        } else {
            $mtOpening = $baseMt;
            $orOpening = $baseOr;
            $rkOpening = $baseRk;
        }

        $metalOpening = $mtOpening + $orOpening;

        // 3. Query active unarchived waves in date range
        $params = [$custId];
        $dateCondition = "";
        if ($fromDate) {
            $dateCondition .= " AND w.entry_date >= ?";
            $params[] = $fromDate;
        }
        if ($toDate) {
            $dateCondition .= " AND w.entry_date <= ?";
            $params[] = $toDate;
        }

        $wavesSql = "
            SELECT 
                w.in_out_id,
                w.entry_date,
                w.collection_id,
                c.collection_name,
                w.transactional_mode,
                w.transactional_type,
                w.net_weight,
                w.gross_weight,
                w.purity,
                w.wastage,
                w.amount,
                w.remark
            FROM waves w
            LEFT JOIN mst_collection c ON w.collection_id = c.collection_id
            WHERE w.cust_id = ?
              AND w.approved_data = '1'
              AND w.is_deleted = '0'
              {$dateCondition}
            ORDER BY w.entry_date ASC, w.in_out_id ASC
        ";

        $wavesRows = $db->query($wavesSql, $params)->getResultArray();

        $receipts = [];
        $issues   = [];

        // 4. Push Opening Balance according to verified CI2 rule
        // Positive opening component -> Receipt side. Negative opening component -> Issue side (absolute value).
        $hasReceiptOpening = ($mtOpening > 0 || $orOpening > 0 || $rkOpening > 0);
        if ($hasReceiptOpening) {
            $receipts[] = [
                'date'        => $fromDate ? date('d-m-Y', strtotime($fromDate)) : '',
                'description' => 'Opening Balance',
                'fine_mt'     => max(0.0, $mtOpening),
                'fine_or'     => max(0.0, $orOpening),
                'rk_amount'   => max(0.0, $rkOpening),
                'remark'      => 'Opening Balance',
                'is_opening'  => true,
            ];
        }

        $hasIssueOpening = ($mtOpening < 0 || $orOpening < 0 || $rkOpening < 0);
        if ($hasIssueOpening) {
            $issues[] = [
                'date'        => $fromDate ? date('d-m-Y', strtotime($fromDate)) : '',
                'description' => 'Opening Balance',
                'fine_mt'     => ($mtOpening < 0) ? abs($mtOpening) : 0.0,
                'fine_or'     => ($orOpening < 0) ? abs($orOpening) : 0.0,
                'rk_amount'   => ($rkOpening < 0) ? abs($rkOpening) : 0.0,
                'remark'      => 'Opening Balance',
                'is_opening'  => true,
            ];
        }

        // 5. Populate and categorize range transactions
        foreach ($wavesRows as $w) {
            $mode   = strtoupper(trim($w['transactional_mode']));
            $fineMt = 0.0;
            $fineOr = 0.0;
            $rkAmt  = 0.0;

            if ($mode === 'MT') {
                $fineMt = ((float)$w['net_weight'] * (float)$w['purity']) / 100;
            } elseif ($mode === 'OR') {
                $fineOr = ((float)$w['net_weight'] * ((float)$w['purity'] + (float)$w['wastage'])) / 100;
            } elseif ($mode === 'RK') {
                $rkAmt  = (float)$w['amount'];
            }

            $desc  = !empty($w['collection_name']) ? trim($w['collection_name']) : $mode;
            $gross = (float)($w['gross_weight'] ?? 0);
            $net   = (float)($w['net_weight'] ?? 0);
            $less  = max(0.0, round($gross - $net, 3));

            $rowItem = [
                'in_out_id'    => (int)$w['in_out_id'],
                'date'         => $w['entry_date'] ? date('d-m-Y', strtotime($w['entry_date'])) : '',
                'raw_date'     => $w['entry_date'],
                'description'  => $desc,
                'mode'         => $mode,
                'gross_weight' => ($gross > 0) ? view_round_up($gross, 3) : '-',
                'less_weight'  => ($less > 0) ? view_round_up($less, 3) : '-',
                'net_weight'   => ($net > 0) ? view_round_up($net, 3) : '-',
                'purity'       => ((float)$w['purity'] > 0) ? number_format((float)$w['purity'], 2, '.', '') : '-',
                'fine_mt'      => $fineMt,
                'fine_or'      => $fineOr,
                'rk_amount'    => $rkAmt,
                'remark'       => trim($w['remark'] ?? ''),
                'is_opening'   => false,
            ];

            if (strtolower($w['transactional_type']) === 'receipt') {
                $receipts[] = $rowItem;
            } else {
                $issues[] = $rowItem;
            }
        }

        // 6. Aggregate totals
        $totRecMt = 0.0;
        $totRecOr = 0.0;
        $totRecRk = 0.0;
        foreach ($receipts as $r) {
            $totRecMt += $r['fine_mt'];
            $totRecOr += $r['fine_or'];
            $totRecRk += $r['rk_amount'];
        }

        $totIssMt = 0.0;
        $totIssOr = 0.0;
        $totIssRk = 0.0;
        foreach ($issues as $i) {
            $totIssMt += $i['fine_mt'];
            $totIssOr += $i['fine_or'];
            $totIssRk += $i['rk_amount'];
        }

        $closingMt    = $totRecMt - $totIssMt;
        $closingOr    = $totRecOr - $totIssOr;
        $closingRk    = $totRecRk - $totIssRk;
        $closingMetal = $closingMt + $closingOr;

        // 7. Assemble paired rows for matched side-by-side ledger presentation
        $maxCount   = max(count($receipts), count($issues));
        $pairedRows = [];
        for ($idx = 0; $idx < $maxCount; $idx++) {
            $pairedRows[] = [
                'receipt' => $receipts[$idx] ?? null,
                'issue'   => $issues[$idx] ?? null,
            ];
        }

        return [
            'customer' => [
                'cust_id'     => $custId,
                'cust_name'   => trim($cust['cust_name']),
                'private_key' => $cust['private_key'],
                'mobile_no'   => trim($cust['mobile_no'] ?? ''),
                'city'        => trim($cust['address_3'] ?: ($cust['address_2'] ?: $cust['address_1'])),
            ],
            'filters' => [
                'from_date' => $fromDate,
                'to_date'   => $toDate,
            ],
            'opening_balances' => [
                'mt'    => $mtOpening,
                'or'    => $orOpening,
                'rk'    => $rkOpening,
                'metal' => $metalOpening,
            ],
            'receipts'   => $receipts,
            'issues'     => $issues,
            'pairedRows' => $pairedRows,
            'totals'     => [
                'receipt_mt'            => $totRecMt,
                'issue_mt'              => $totIssMt,
                'receipt_or'            => $totRecOr,
                'issue_or'              => $totIssOr,
                'receipt_rk'            => $totRecRk,
                'issue_rk'              => $totIssRk,
                'closing_mt'            => $closingMt,
                'closing_or'            => $closingOr,
                'closing_rk'            => $closingRk,
                'closing_metal'         => $closingMetal,
                // Display formatting
                'display_receipt_mt'    => view_round_up($totRecMt, 3),
                'display_issue_mt'      => view_round_up($totIssMt, 3),
                'display_receipt_or'    => view_round_up($totRecOr, 3),
                'display_issue_or'      => view_round_up($totIssOr, 3),
                'display_receipt_rk'    => number_format($totRecRk, 2, '.', ''),
                'display_issue_rk'      => number_format($totIssRk, 2, '.', ''),
                'display_closing_mt'    => view_round_up($closingMt, 3),
                'display_closing_or'    => view_round_up($closingOr, 3),
                'display_closing_rk'    => number_format($closingRk, 2, '.', ''),
                'display_closing_metal' => view_round_up($closingMetal, 3),
            ],
        ];
    }

    /**
     * Daily Report: fetch transactions dynamically from `new-spiderwaves` database (port 3307),
     * paired side-by-side into Receipts (R) and Issues (I) matching legacy CI2 structure.
     *
     * @param string|null $reportDate Specific date string (YYYY-MM-DD) or null for active batch
     * @return array{or_data: array, or_balance: float, display_balance: string, total_receipt_fw: float, total_issue_fw: float, receipt_count: int, issue_count: int, report_date: ?string}
     */
    public function getDailyReportData(?string $reportDate = null): array
    {
        $db = \Config\Database::connect('default');

        $activeDate = null;
        $receipts = [];
        $issues   = [];

        if (!empty($reportDate)) {
            // Explicit date requested: fetch transactions for that entry_date
            $activeDate = trim($reportDate);
            $receipts = $db->table('waves w')
                ->select('w.*, c.cust_name')
                ->join('mst_customer c', 'w.cust_id = c.cust_id')
                ->where('w.transactional_type', 'receipt')
                ->where('w.entry_date', $activeDate)
                ->where('w.is_deleted', '0')
                ->orderBy('w.in_out_id', 'ASC')
                ->get()->getResultArray();

            $issues = $db->table('waves w')
                ->select('w.*, c.cust_name')
                ->join('mst_customer c', 'w.cust_id = c.cust_id')
                ->where('w.transactional_type', 'issue')
                ->where('w.entry_date', $activeDate)
                ->where('w.is_deleted', '0')
                ->orderBy('w.in_out_id', 'ASC')
                ->get()->getResultArray();
        } else {
            // CI2 primary rule: unapproved staging records awaiting daily approve and submit
            $receipts = $db->table('waves w')
                ->select('w.*, c.cust_name')
                ->join('mst_customer c', 'w.cust_id = c.cust_id')
                ->where('w.transactional_type', 'receipt')
                ->where('w.approved_data', '0')
                ->where('w.is_deleted', '0')
                ->orderBy('w.in_out_id', 'ASC')
                ->get()->getResultArray();

            $issues = $db->table('waves w')
                ->select('w.*, c.cust_name')
                ->join('mst_customer c', 'w.cust_id = c.cust_id')
                ->where('w.transactional_type', 'issue')
                ->where('w.approved_data', '0')
                ->where('w.is_deleted', '0')
                ->orderBy('w.in_out_id', 'ASC')
                ->get()->getResultArray();

            // If no unapproved records exist, check for transactions recorded on the current business date
            if (empty($receipts) && empty($issues)) {
                $today = date('Y-m-d');
                $todayRec = $db->table('waves w')
                    ->select('w.*, c.cust_name')
                    ->join('mst_customer c', 'w.cust_id = c.cust_id')
                    ->where('w.transactional_type', 'receipt')
                    ->where('w.entry_date', $today)
                    ->where('w.is_deleted', '0')
                    ->orderBy('w.in_out_id', 'ASC')
                    ->get()->getResultArray();

                $todayIss = $db->table('waves w')
                    ->select('w.*, c.cust_name')
                    ->join('mst_customer c', 'w.cust_id = c.cust_id')
                    ->where('w.transactional_type', 'issue')
                    ->where('w.entry_date', $today)
                    ->where('w.is_deleted', '0')
                    ->orderBy('w.in_out_id', 'ASC')
                    ->get()->getResultArray();

                if (!empty($todayRec) || !empty($todayIss)) {
                    $receipts   = $todayRec;
                    $issues     = $todayIss;
                    $activeDate = $today;
                }
            }
        }

        // Re-arrange into side-by-side pairs matching legacy MT_OR_array_re_arrange
        $receiptCount = count($receipts);
        $issueCount   = count($issues);
        $maxCount     = max($receiptCount, $issueCount);

        $orData             = [];
        $totalReceiptActual = 0.0;
        $totalIssueActual   = 0.0;
        $totalReceiptFw     = 0.0;
        $totalIssueFw       = 0.0;

        for ($idx = 0; $idx < $maxCount; $idx++) {
            $row = new \stdClass();

            // Receipt side
            if ($idx < $receiptCount && !empty($receipts[$idx])) {
                $r = $receipts[$idx];
                $row->receipt_in_out_id    = (int)$r['in_out_id'];
                $row->receipt_gross_weight = $r['gross_weight'];
                $row->receipt_net_weight   = $r['net_weight'];
                $row->receipt_purity       = $r['purity'];
                $row->receipt_wastage      = $r['wastage'];
                $row->receipt_cust_id      = (int)$r['cust_id'];
                $row->receipt_cust_name    = trim($r['cust_name']);
                $row->receipt_remark       = trim($r['remark'] ?? '');
                $row->receipt_private_key  = $r['private_key'];
                $row->receipt_mode         = strtoupper(trim($r['transactional_mode']));

                $netWt  = (float)$r['net_weight'];
                $purity = (float)$r['purity'];
                $wstg   = (float)$r['wastage'];

                if ($row->receipt_mode === 'MT') {
                    $actual = ($netWt * $purity) / 100;
                } else {
                    $actual = ($netWt * ($purity + $wstg)) / 100;
                }
                $fw = ($netWt * $purity) / 100;

                $row->receipt_actual = $actual;
                $row->receipt_fw     = $fw;
                $totalReceiptActual += $actual;
                $totalReceiptFw     += $fw;
            } else {
                $row->receipt_in_out_id    = null;
                $row->receipt_gross_weight = '';
                $row->receipt_net_weight   = '';
                $row->receipt_purity       = '';
                $row->receipt_wastage      = '';
                $row->receipt_cust_id      = null;
                $row->receipt_cust_name    = '';
                $row->receipt_remark       = '';
                $row->receipt_private_key  = '';
                $row->receipt_mode         = '';
                $row->receipt_actual       = '';
                $row->receipt_fw           = 0.0;
            }

            // Issue side
            if ($idx < $issueCount && !empty($issues[$idx])) {
                $iss = $issues[$idx];
                $row->issue_in_out_id    = (int)$iss['in_out_id'];
                $row->issue_gross_weight = $iss['gross_weight'];
                $row->issue_net_weight   = $iss['net_weight'];
                $row->issue_purity       = $iss['purity'];
                $row->issue_wastage      = $iss['wastage'];
                $row->issue_cust_id      = (int)$iss['cust_id'];
                $row->issue_cust_name    = trim($iss['cust_name']);
                $row->issue_remark       = trim($iss['remark'] ?? '');
                $row->issue_private_key  = $iss['private_key'];
                $row->issue_mode         = strtoupper(trim($iss['transactional_mode']));

                $netWt  = (float)$iss['net_weight'];
                $purity = (float)$iss['purity'];
                $wstg   = (float)$iss['wastage'];

                if ($row->issue_mode === 'MT') {
                    $actual = ($netWt * $purity) / 100;
                } else {
                    $actual = ($netWt * ($purity + $wstg)) / 100;
                }
                $fw = ($netWt * $purity) / 100;

                $row->issue_actual = $actual;
                $row->issue_fw     = $fw;
                $totalIssueActual += $actual;
                $totalIssueFw     += $fw;
            } else {
                $row->issue_in_out_id    = null;
                $row->issue_gross_weight = '';
                $row->issue_net_weight   = '';
                $row->issue_purity       = '';
                $row->issue_wastage      = '';
                $row->issue_cust_id      = null;
                $row->issue_cust_name    = '';
                $row->issue_remark       = '';
                $row->issue_private_key  = '';
                $row->issue_mode         = '';
                $row->issue_actual       = '';
                $row->issue_fw           = 0.0;
            }

            $orData[] = $row;
        }

        $balance = $totalReceiptActual - $totalIssueActual;

        return [
            'or_data'          => $orData,
            'or_balance'       => $balance,
            'display_balance'  => view_round_up($balance, 3),
            'total_receipt_fw' => $totalReceiptFw,
            'total_issue_fw'   => $totalIssueFw,
            'receipt_count'    => $receiptCount,
            'issue_count'      => $issueCount,
            'report_date'      => $activeDate,
        ];
    }

    /**
     * Approve and submit Daily Report transactions.
     * Transitions all active unapproved waves (approved_data = '0') to approved (approved_data = '1').
     *
     * @param string|null $reportDate Optional date filter if approving a specific date
     * @return int Number of records updated
     */
    public function approveDailyReport(?string $reportDate = null): int
    {
        $db = \Config\Database::connect('default');
        $builder = $db->table('waves');
        $builder->where('approved_data', '0');
        $builder->where('is_deleted', '0');
        if (!empty($reportDate)) {
            $builder->where('entry_date', $reportDate);
        }
        $builder->update(['approved_data' => '1']);

        return $db->affectedRows();
    }

    /**
     * Approve customer statement.
     * Sets approved_by_cust = '1' for all waves belonging to the given customer private key.
     *
     * @param string $custPrivateKey
     * @return int Number of records updated
     */
    public function approveCustomerStatement(string $custPrivateKey): int
    {
        $db = \Config\Database::connect('default');
        $cust = $db->table('mst_customer')->where('private_key', $custPrivateKey)->get()->getRowArray();
        if (!$cust) {
            return 0;
        }

        $db->table('waves')
            ->where('cust_id', (int)$cust['cust_id'])
            ->where('approved_data', '1')
            ->where('is_deleted', '0')
            ->update(['approved_by_cust' => '1']);

        return $db->affectedRows();
    }
}




