<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AccountModel
 * 
 * Manages manufacturing workshop process departments, stock vaults, and loss accounts.
 * Table: mst_process
 */
class AccountModel extends Model
{
    protected $table            = 'mst_process';
    protected $primaryKey       = 'account_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'private_key',
        'account_name',
        'account_type',
        'loss_type',
        'loss_percentage',
        'map_with_accounts_id',
        'account_status',
    ];

    protected $useTimestamps = false;

    // Hardcoded account IDs from legacy domain
    public const STOCK_ID             = 1;  // Locker / Stock Vault
    public const CASTING_ID           = 2;  // Casting
    public const HEERA_LIGHT_ID       = 3;  // Heera Light
    public const FILING_ID            = 4;  // Filing
    public const SETTING_ID           = 5;  // Setting
    public const POLISH_ID            = 6;  // Polish
    public const RODIUM_ID            = 7;  // Rodium / Electroplating
    public const PACKING_ID           = 8;  // Packing
    public const REFINING_ID          = 9;  // Refining
    public const NET_LOSS_ID          = 10; // Net Loss
    public const MORE_ID              = 11; // More
    public const LOSS_ID              = 12; // Loss
    public const G58_POLISH_ID        = 13; // G58 Polish
    public const G58_SETTING_ID       = 14; // G58 Setting
    public const G58_FILING_ID        = 15; // G58 Filing
    public const ZERO_BALANCE_ID      = 16; // Zero Balance
    public const G58_TRANSFER_ID      = 17; // G58 Transfer
    public const TRANSFER_PACKING_ID  = 18; // Transfer Packing
    public const PO_CHECK_ID          = 19; // PO Check
    public const GALAI_ID             = 20; // Galai
    public const TASH_ID              = 21; // Tash
    public const SAPPHIRE_ID          = 22; // Sapphire
    public const CNC_ID               = 23; // CNC / Megnet

    public const LOCKER_PRIVATE_KEY           = '87890fca66eacc10c9c7cf440a956262';
    public const LOSS_PRIVATE_KEY             = '3442203db2c3bf62b928ac74f3d64b88';
    public const REFINING_PRIVATE_KEY         = '860d4bcf649e0a2393ef85ae72cb2dd9';
    public const CASTING_PRIVATE_KEY          = '80f372f8dffb5126736fa8c02a24c9fa';
    public const PACKING_PRIVATE_KEY          = '68f2968985d8728d155d65ab5505ab34';
    public const TRANSFER_PACKING_PRIVATE_KEY = 'aagahedr01a6ff8a58427cef2095asvf';
    public const RODIUM_PRIVATE_KEY           = '592d02d58179f9b92b590ea1fea5ff2b';
    public const G58_POLISH_PRIVATE_KEY       = '43sdfh3sdklf239gjklfd43gkls4gfr4';
    public const G58_FILING_PRIVATE_KEY       = '423fwr46jk8563db447hddv467jhm57h';
    public const G58_SETTING_PRIVATE_KEY      = 'dsfs159422hzzqkd439fdpo60923jkd2';
    public const G58_TRANSFER_PRIVATE_KEY     = 'bb8e719f01a6ff8ca84634ce2095cf03';

    /**
     * Get account record by ID.
     */
    public function getAccountById(int $accountId): ?array
    {
        return $this->where('account_id', $accountId)->first();
    }

    /**
     * Get account record by private key.
     */
    public function getAccountByPrivateKey(string $privateKey): ?array
    {
        return $this->where('private_key', $privateKey)->first();
    }

    /**
     * Get active process accounts for navigation/dropdowns.
     */
    public function getActiveProcessAccounts(): array
    {
        return $this->where('account_status', '1')
            ->orderBy('account_name', 'ASC')
            ->findAll();
    }

    /**
     * Get destination accounts mapped to a source account based on map_with_accounts_id (e.g. "2#4#3#6#...").
     */
    public function getMappedDestinationAccounts(string $mapWithAccountsId): array
    {
        $ids = $this->parseMappedAccountIds($mapWithAccountsId);
        if (empty($ids)) {
            return [];
        }

        return $this->whereIn('account_id', $ids)
            ->where('account_status', '1')
            ->orderBy('account_name', 'ASC')
            ->findAll();
    }

    /**
     * Parse mapped account IDs into an array of integer account IDs.
     * Supports both JSON format (e.g. "[2,4,3]") and legacy "#" delimited format (e.g. "2#4#3").
     */
    public function parseMappedAccountIds(?string $mapWithAccountsId): array
    {
        $clean = trim((string)$mapWithAccountsId);
        if ($clean === '' || $clean === 'null' || $clean === '[]') {
            return [];
        }

        // Support JSON array format (e.g. [2,4,3] or ["2","4","3"])
        if ($clean[0] === '[' || $clean[0] === '{') {
            $decoded = json_decode($clean, true);
            if (is_array($decoded)) {
                $ids = [];
                foreach ($decoded as $v) {
                    $intVal = intval($v);
                    if ($intVal > 0 && !in_array($intVal, $ids, true)) {
                        $ids[] = $intVal;
                    }
                }
                return $ids;
            }
        }

        // Backward compatibility for "#" delimited format (e.g. "2#4#3#6")
        $parts = explode('#', $clean);
        $ids = [];
        foreach ($parts as $p) {
            $val = intval(trim($p));
            if ($val > 0 && !in_array($val, $ids, true)) {
                $ids[] = $val;
            }
        }

        return $ids;
    }

    /**
     * Format an array of account IDs into the database JSON array string.
     * e.g. [2, 4, 3] -> "[2,4,3]"
     */
    public function formatMappedAccountIds(array $accountIds): ?string
    {
        $validIds = [];
        foreach ($accountIds as $id) {
            $val = intval($id);
            if ($val > 0 && !in_array($val, $validIds, true)) {
                $validIds[] = $val;
            }
        }

        return !empty($validIds) ? json_encode(array_values($validIds)) : null;
    }

    /**
     * Get all active accounts available to be mapped with, optionally excluding a specific account.
     */
    public function getAvailableMappingAccounts(?int $excludeAccountId = null): array
    {
        $builder = $this->where('account_status', '1');
        if ($excludeAccountId !== null && $excludeAccountId > 0) {
            $builder->where('account_id !=', $excludeAccountId);
        }

        return $builder->orderBy('account_type', 'ASC')
            ->orderBy('account_name', 'ASC')
            ->findAll();
    }

    /**
     * Get paginated accounts list for management with optional search filter.
     */
    public function getPaginatedAccounts(?string $search = null, int $perPage = 25): array
    {
        $builder = $this;

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $builder = $builder->groupStart()
                ->like('account_name', $term)
                ->orLike('account_type', $term)
                ->groupEnd();
        }

        return [
            'accounts' => $builder->orderBy('account_id', 'ASC')->paginate($perPage),
            'pager'    => $this->pager,
            'total'    => $builder->countAllResults(false),
        ];
    }

    /**
     * Get loss percentage configured for an account.
     */
    public function getLossPercentage(int $accountId): float
    {
        $acc = $this->select('loss_percentage')->where('account_id', $accountId)->first();
        return $acc ? (float)$acc['loss_percentage'] : 0.0;
    }

    /**
     * Get account name by ID.
     */
    public function getAccountName(int $accountId): string
    {
        $acc = $this->select('account_name')->where('account_id', $accountId)->first();
        return $acc ? (string)$acc['account_name'] : '';
    }

    /**
     * Get process navigation accounts in exact sequence matching legacy CI2/CI3 menu.
     * Integrates with dynamic RBAC permission management and ancestor hard-deny rules.
     *
     * @param string   $accessLevel User role (admin, superadmin, process_manager, jr_process_manager, g58)
     * @param int|null $userId      Optional specific user ID for individual override evaluation
     * @return array
     */
    public function getProcessNavigationAccounts(string $accessLevel = '', ?int $userId = null): array
    {
        // Parent Hard-Deny: If ops_process accordion menu is hidden or disabled, no child depts can be rendered
        if (function_exists('can_menu') && $accessLevel !== 'superadmin' && !empty($accessLevel)) {
            if (!can_menu('ops_process', $userId, $accessLevel)) {
                return [];
            }
        }

        // Defined sequence matching legacy CI2/CI3 menu as documented in the study report
        $sequence = [
            self::CASTING_ID          => 'Casting',
            self::FILING_ID           => 'Filing',
            self::HEERA_LIGHT_ID      => 'Heera Light',
            self::PACKING_ID          => 'Packing',
            self::TRANSFER_PACKING_ID => 'Transfer Packing',
            self::POLISH_ID           => 'Polish',
            self::G58_POLISH_ID       => 'G58-Polish',
            self::G58_FILING_ID       => 'G58-Filing',
            self::G58_SETTING_ID      => 'G58-Setting',
            self::G58_TRANSFER_ID     => 'G58-Transfer',
            self::PO_CHECK_ID         => 'PO Check',
            self::GALAI_ID            => 'Galai',
            self::TASH_ID             => 'Tash',
            self::SAPPHIRE_ID         => 'Sapphire',
            self::CNC_ID              => 'Megnet',
            self::RODIUM_ID           => 'Rodium',
            self::SETTING_ID          => 'Setting',
            self::ZERO_BALANCE_ID     => 'Zero Balance',
        ];

        // Fetch all active process accounts from database dynamically
        $dbAccounts = $this->where('account_type', 'process')
            ->where('account_status', '1')
            ->orderBy('account_id', 'ASC')
            ->findAll();

        $accountMap = [];
        foreach ($dbAccounts as $acc) {
            $accountMap[(int)$acc['account_id']] = $acc;
        }

        // Determine allowed department IDs
        $allowedIds = [];
        if ($accessLevel === 'superadmin' || $accessLevel === 'admin' || $accessLevel === 'process_manager') {
            $allowedIds = array_keys($accountMap);
        } elseif (function_exists('can')) {
            // Check dynamic RBAC permission for each department
            foreach ($accountMap as $deptId => $acc) {
                if (can("process_waves.dept_{$deptId}", $userId, $accessLevel)) {
                    $allowedIds[] = $deptId;
                }
            }
        }

        // Fallback to static role rules if RBAC returned nothing or not initialized
        if (empty($allowedIds) && !empty($accessLevel) && $accessLevel !== 'superadmin') {
            if ($accessLevel === 'jr_process_manager') {
                $allowedIds = [self::PACKING_ID, self::TRANSFER_PACKING_ID, self::RODIUM_ID];
            } elseif ($accessLevel === 'g58') {
                $allowedIds = [self::G58_POLISH_ID, self::G58_FILING_ID, self::G58_SETTING_ID, self::G58_TRANSFER_ID];
            } elseif ($accessLevel === 'admin') {
                $adminSequence = $sequence;
                unset($adminSequence[self::PACKING_ID], $adminSequence[self::TRANSFER_PACKING_ID], $adminSequence[self::RODIUM_ID]);
                $allowedIds = array_keys($adminSequence);
            } elseif ($accessLevel === 'process_manager') {
                $allowedIds = array_keys($accountMap);
            }
        }

        $result = [];
        $addedIds = [];

        // 1. Maintain preferred legacy sequence for legacy departments
        foreach ($sequence as $id => $label) {
            if (isset($accountMap[$id]) && in_array($id, $allowedIds, true)) {
                $acc = $accountMap[$id];
                $acc['display_name'] = $label;
                $result[] = $acc;
                $addedIds[$id] = true;
            }
        }

        // 2. Dynamically append any newly created or custom active process accounts
        foreach ($dbAccounts as $acc) {
            $id = (int)$acc['account_id'];
            if (!isset($addedIds[$id]) && in_array($id, $allowedIds, true)) {
                $acc['display_name'] = $acc['account_name'];
                $result[] = $acc;
                $addedIds[$id] = true;
            }
        }

        return $result;
    }

    /**
     * Get loss navigation accounts in exact sequence matching legacy CI2/CI3 menu.
     * Integrates with dynamic RBAC permission management and ancestor hard-deny rules.
     *
     * @param string   $accessLevel User role (admin, superadmin, process_manager, g58)
     * @param int|null $userId      Optional specific user ID for individual override evaluation
     * @return array
     */
    public function getLossNavigationAccounts(string $accessLevel = '', ?int $userId = null): array
    {
        // Parent Hard-Deny: If ops_loss accordion menu is hidden or disabled, no child loss depts can be rendered
        if (function_exists('can_menu') && $accessLevel !== 'superadmin' && !empty($accessLevel)) {
            if (!can_menu('ops_loss', $userId, $accessLevel)) {
                return [];
            }
        }

        $sequence = [
            self::REFINING_ID         => 'Refining Loss',
            self::CASTING_ID          => 'Casting Loss',
            self::FILING_ID           => 'Filing Loss',
            self::HEERA_LIGHT_ID      => 'Heera Light Loss',
            self::PACKING_ID          => 'Packing Loss',
            self::POLISH_ID           => 'Polish Loss',
            self::RODIUM_ID           => 'Rodium Loss',
            self::SETTING_ID          => 'Setting Loss',
            self::G58_POLISH_ID       => 'G58-Polish',
            self::G58_FILING_ID       => 'G58-Filing',
            self::G58_SETTING_ID      => 'G58-Setting',
            self::GALAI_ID            => 'Galai',
            self::TASH_ID             => 'Tash',
            self::SAPPHIRE_ID         => 'Sapphire',
            self::CNC_ID              => 'Megnet',
        ];

        // Fetch active workshop process stages and loss-type accounts from database
        // Excludes stock accounts (Locker, More) and general loss account (Loss ID 12, Net Loss ID 10)
        $dbAccounts = $this->whereIn('account_type', ['process', 'loss'])
            ->where('account_status', '1')
            ->whereNotIn('account_id', [self::STOCK_ID, self::LOSS_ID, self::MORE_ID, self::NET_LOSS_ID])
            ->findAll();

        $accountMap = [];
        foreach ($dbAccounts as $acc) {
            $accountMap[(int)$acc['account_id']] = $acc;
        }

        // Determine allowed loss account IDs
        $allowedIds = [];
        if ($accessLevel === 'superadmin' || $accessLevel === 'admin' || $accessLevel === 'process_manager') {
            $allowedIds = array_keys($accountMap);
        } elseif (function_exists('can')) {
            // Check dynamic RBAC permission for each loss department
            foreach ($accountMap as $deptId => $acc) {
                if (can("loss.dept_{$deptId}", $userId, $accessLevel)) {
                    $allowedIds[] = $deptId;
                }
            }
        }

        // Fallback to static role rules if RBAC returned nothing or not initialized
        if (empty($allowedIds) && !empty($accessLevel) && $accessLevel !== 'superadmin') {
            if ($accessLevel === 'g58') {
                $allowedIds = [self::G58_POLISH_ID, self::G58_FILING_ID, self::G58_SETTING_ID];
            } elseif (in_array($accessLevel, ['admin', 'process_manager'], true)) {
                $allowedIds = array_keys($accountMap);
            }
        }

        if (empty($allowedIds)) {
            $allowedIds = array_keys($accountMap);
        }

        $result = [];
        $addedIds = [];

        // 1. Maintain sequence matching old SpiderWaves
        foreach ($sequence as $id => $label) {
            if (isset($accountMap[$id]) && in_array($id, $allowedIds, true)) {
                $acc = $accountMap[$id];
                $acc['loss_title']   = $label;
                $acc['display_name'] = $label;
                $result[] = $acc;
                $addedIds[$id] = true;
            }
        }

        // 2. Dynamically append any other active process or loss accounts
        foreach ($dbAccounts as $acc) {
            $id = (int)$acc['account_id'];
            if (!isset($addedIds[$id]) && in_array($id, $allowedIds, true)) {
                $rawName       = $acc['account_name'];
                $formattedName = str_replace('_', ' ', $rawName);
                $title         = !str_ends_with(strtolower($formattedName), 'loss')
                    ? $formattedName . ' Loss'
                    : $formattedName;
                $acc['loss_title']   = $title;
                $acc['display_name'] = $title;
                $result[] = $acc;
                $addedIds[$id] = true;
            }
        }

        return $result;
    }

    /**
     * Check if an account has any dependent operational transactions or balances.
     * Prevents deletion of accounts referenced in active financial records.
     *
     * @param int $accountId
     * @return array ['can_delete' => bool, 'is_core' => bool, 'reason' => string, 'total_transactions' => int]
     */
    public function checkAccountDependencies(int $accountId): array
    {
        $db = \Config\Database::connect();
        $isCore = ($accountId <= 23);

        $pwReceiptCount = 0;
        $pwIssueCount   = 0;
        if ($db->tableExists('process_waves')) {
            $pwReceiptCount = (int)$db->table('process_waves')->where('to_id', $accountId)->countAllResults();
            $pwIssueCount   = (int)$db->table('process_waves')->where('from_id', $accountId)->countAllResults();
        }

        $cbCount = 0;
        if ($db->tableExists('closing_balance')) {
            $cbCount = (int)$db->table('closing_balance')->where('account_id', $accountId)->countAllResults();
        }

        $totalTransactions = $pwReceiptCount + $pwIssueCount + $cbCount;
        $canDelete = (!$isCore && $totalTransactions === 0);

        $reason = '';
        if ($isCore) {
            $reason = 'Core system department accounts (ID 1-23) cannot be deleted. You can deactivate this account instead.';
        } elseif ($totalTransactions > 0) {
            $parts = [];
            if ($pwReceiptCount > 0) $parts[] = "{$pwReceiptCount} receipt transaction(s)";
            if ($pwIssueCount > 0)   $parts[] = "{$pwIssueCount} issue transaction(s)";
            if ($cbCount > 0)        $parts[] = "{$cbCount} stock checkpoint balance(s)";
            $reason = 'Cannot delete process because it has active records: ' . implode(', ', $parts) . '. Please deactivate it instead.';
        }

        return [
            'can_delete'         => $canDelete,
            'is_core'            => $isCore,
            'receipt_count'      => $pwReceiptCount,
            'issue_count'        => $pwIssueCount,
            'closing_count'      => $cbCount,
            'total_transactions' => $totalTransactions,
            'reason'             => $reason,
        ];
    }

    /**
     * Get account IDs for the Stock Report in exact legacy CI2 sequence.
     * Sequence: all_process_account_ids (2, 3, 4, 5, 6, 7, 8, 9, 13, 14, 15, 16, 17, 20, 21, 22, 23) + Loss (12) + Locker (1).
     *
     * @return int[]
     */
    public function getStockReportAccountIds(): array
    {
        return [
            self::CASTING_ID,           // 2
            self::HEERA_LIGHT_ID,       // 3
            self::FILING_ID,            // 4
            self::SETTING_ID,           // 5
            self::POLISH_ID,            // 6
            self::RODIUM_ID,            // 7
            self::PACKING_ID,           // 8
            self::REFINING_ID,          // 9
            self::G58_POLISH_ID,        // 13
            self::G58_SETTING_ID,       // 14
            self::G58_FILING_ID,        // 15
            self::ZERO_BALANCE_ID,      // 16
            self::G58_TRANSFER_ID,      // 17
            self::GALAI_ID,             // 20
            self::TASH_ID,              // 21
            self::SAPPHIRE_ID,          // 22
            self::CNC_ID,               // 23
            self::LOSS_ID,              // 12
            self::STOCK_ID,             // 1
        ];
    }
}

