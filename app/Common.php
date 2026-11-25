<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

date_default_timezone_set('Asia/Kolkata');
ini_set('date.timezone', 'Asia/Kolkata');

if (!function_exists('get_number_of_pending_raw_waves')) {
    /**
     * Get count of pending raw waves awaiting workshop verification.
     */
    function get_number_of_pending_raw_waves(): int
    {
        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('raw_waves')) {
                return (int)$db->table('raw_waves')
                    ->where('approved_data', '0')
                    ->where('is_deleted', '0')
                    ->countAllResults();
            }
        } catch (\Throwable $e) {
            // Table may not yet be migrated
        }
        return 0;
    }
}

if (!function_exists('view_round_up')) {
    /**
     * Format decimal numbers to specific precision matching legacy view_round_up.
     *
     * @param float|int|string|null $number
     * @param int                   $decimalValue
     * @return string
     */
    function view_round_up($number = 0, int $decimalValue = 3): string
    {
        return number_format((float) ($number ?? 0), $decimalValue, '.', '');
    }
}

if (!function_exists('total_rk_mt_or_balance')) {
    /**
     * Calculate total company metal and cash balances matching legacy total_rk_mt_or_balance.
     *
     * @return array{mt_total_balance: float, or_total_balance: float, rk_total_balance: float}
     */
    function total_rk_mt_or_balance(): array
    {
        $ledgerService = new \App\Services\LedgerService();
        return $ledgerService->getTotalBalances();
    }
}

if (!function_exists('can')) {
    /**
     * Universal RBAC helper function to verify if the current user possesses permission.
     * Incorporates complete Ancestor Hard-Deny rule and individual user overrides.
     */
    function can(string $permissionCode, ?int $userId = null, ?string $roleKey = null): bool
    {
        /** @var \App\Services\PermissionService $permService */
        $permService = service('permission');
        return $permService->hasPermission($permissionCode, $userId, $roleKey);
    }
}

if (!function_exists('can_menu')) {
    /**
     * Helper function to verify if a menu item is accessible.
     */
    function can_menu(string $menuCode, ?int $userId = null, ?string $roleKey = null): bool
    {
        /** @var \App\Services\PermissionService $permService */
        $permService = service('permission');
        return $permService->can_menu($menuCode, $userId, $roleKey);
    }
}

if (!function_exists('can_any')) {
    /**
     * Helper function to check if the current user possesses at least one of the given permissions.
     */
    function can_any(array $permissionCodes, ?int $userId = null, ?string $roleKey = null): bool
    {
        foreach ($permissionCodes as $code) {
            if (can($code, $userId, $roleKey)) {
                return true;
            }
        }
        return false;
    }
}

