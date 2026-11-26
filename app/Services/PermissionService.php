<?php

namespace App\Services;

use App\Models\RoleModel;
use App\Models\MenuModel;
use App\Models\PermissionModel;
use App\Models\RolePermissionModel;
use App\Models\PermissionAuditLogModel;
use App\Models\UserModel;

/**
 * Enterprise PermissionService
 *
 * Implements simplified Two-Table Role & Permission Management architecture:
 * 1. roles
 * 2. role_permissions (using permission_key)
 */
class PermissionService
{
    protected RoleModel $roleModel;
    protected MenuModel $menuModel;
    protected PermissionModel $permissionModel;
    protected RolePermissionModel $rolePermModel;
    protected PermissionAuditLogModel $auditModel;
    protected UserModel $userModel;

    // Request-scoped memoization to avoid redundant DB queries within the same request
    protected static ?array $memoizedUser = null;
    protected static array $memoizedRoleMeta = [];
    protected static array $memoizedRolePermKeys = [];
    protected static array $memoizedPermissions = [];

    /**
     * Authoritative Menu-to-Permission Mapping.
     * Maps sidebar/navbar menus to their corresponding *.view permissions in role_permissions.
     */
    protected static array $menuToPermissionMap = [
        'main_dashboard'          => 'dashboard.view',
        'ops_waves'               => 'raw_waves.view',
        'ops_process'             => 'process_waves.view',
        'ops_locker'              => 'locker.view',
        'ops_locker_verification' => 'locker_verification.view',
        'ops_loss'                => 'loss.view',
        'loss_hub'                => 'loss.view',
        'ops_refining'            => 'refining.view',
        'ops_stock'               => 'stock.view',
        'masters'                 => [
            'loss_percentage.view',
            'petty_cash.view',
            'remark.view',
            'user_master.view',
            'revalidate_masters.view',
        ],
        'system_masters'          => [
            'customer.view',
            'customer_mapping.view',
            'collection.view',
            'purity.view',
        ],
        'master_customer'         => 'customer.view',
        'master_customer_mapping' => 'customer_mapping.view',
        'master_collection'       => 'collection.view',
        'master_purity'           => ['collection.view', 'purity.view'],
        'master_loss_percentage'  => 'loss_percentage.view',
        'master_petty_cash'       => 'petty_cash.view',
        'master_remark'           => 'remark.view',
        'master_user'             => 'user_master.view',
        'master_ucc_wastage'      => ['customer.view', 'revalidate_masters.view'],
        'master_ucc_revalidate'   => ['customer.view', 'revalidate_masters.view'],
        'account_master'          => 'account_master.view',
        'permission_management'   => 'permission.superadmin',
        'reports'                 => [
            'reports.cust_wise',
            'reports.customer_ledger',
            'reports.daily_report',
        ],
        'report_cust_wise'        => 'reports.cust_wise',
        'report_daily'            => 'reports.daily_report',
        'cust_statement'          => 'customer_portal.view',
        'navbar_pending_badge'    => 'raw_waves.view',
        'navbar_profile_menu'     => 'dashboard.view',
    ];

    public function __construct()
    {
        $this->roleModel         = new RoleModel();
        $this->menuModel         = new MenuModel();
        $this->permissionModel   = new PermissionModel();
        $this->rolePermModel     = new RolePermissionModel();
        $this->auditModel        = new PermissionAuditLogModel();
        $this->userModel         = new UserModel();
    }

    /**
     * Resolves the authoritative user record directly against the MySQL database.
     * Synchronizes any role/status alterations made in the DB with the active session.
     */
    public function resolveAuthoritativeUser(?int $userId = null, ?string $roleKey = null): ?array
    {
        if (($userId === null || $userId <= 0) && $roleKey !== null) {
            $role = null;
            try {
                $role = $this->roleModel->getByRoleKey($roleKey);
            } catch (\Throwable $e) {
                $role = null;
            }

            if (!$role) {
                return [
                    'user_id'      => 0,
                    'username'     => 'test_' . $roleKey,
                    'access_level' => $roleKey,
                    'location'     => 'ho',
                    'status'       => '1',
                    'role_id'      => 0,
                    'role_name'    => ucwords(str_replace('_', ' ', $roleKey)),
                    'role_status'  => '1',
                ];
            }
            return [
                'user_id'      => 0,
                'username'     => 'test_' . $roleKey,
                'access_level' => $roleKey,
                'location'     => 'ho',
                'status'       => '1',
                'role_id'      => (int)$role['role_id'],
                'role_name'    => $role['role_name'],
                'role_status'  => $role['status'],
            ];
        }

        if ($userId === null && $roleKey === null && self::$memoizedUser !== null) {
            return self::$memoizedUser;
        }

        $session = session();
        $spider  = $session->get('spider') ?? [];

        if ($userId === null) {
            $userId = !empty($spider['user_id']) ? (int)$spider['user_id'] : null;
        }

        if (!$userId) {
            $accessLevel = $spider['access_level'] ?? '';
            if ($accessLevel !== '') {
                return [
                    'user_id'      => 0,
                    'username'     => $spider['user'] ?? 'User',
                    'access_level' => $accessLevel,
                    'location'     => $spider['location'] ?? 'ho',
                    'status'       => '1',
                    'role_id'      => 0,
                    'role_name'    => ucwords(str_replace('_', ' ', $accessLevel)),
                    'role_status'  => '1',
                ];
            }
            return null;
        }

        $dbUser = $this->userModel->find($userId);
        if (!$dbUser || !in_array((string)$dbUser['status'], ['1', 'true'], true)) {
            return null;
        }

        // Fetch corresponding role from roles table (with safe fallback if roles table is not present)
        $role = null;
        try {
            $role = $this->roleModel->getByRoleKey($dbUser['access_level']);
        } catch (\Throwable $e) {
            $role = null;
        }

        $dbUser['role_id']     = $role ? (int)$role['role_id'] : 0;
        $dbUser['role_name']   = $role['role_name'] ?? ucwords(str_replace('_', ' ', $dbUser['access_level']));
        $dbUser['role_status'] = $role['status'] ?? '1';

        // Authoritative synchronization with session ONLY if this is the active session user
        $sessionUserId = !empty($spider['user_id']) ? (int)$spider['user_id'] : null;
        if ($sessionUserId !== null && $userId === $sessionUserId) {
            $currentAccess = $spider['access_level'] ?? '';
            if ($currentAccess !== $dbUser['access_level']) {
                $spider['access_level'] = $dbUser['access_level'];
                $spider['location']     = $dbUser['location'];
                $session->set('spider', $spider);
                $this->invalidateUserCache($userId);
            }
            self::$memoizedUser = $dbUser;
        }

        return $dbUser;
    }

    /**
     * Authoritative per-request role metadata lookup.
     */
    public function getAuthoritativeRoleMetadata(int $roleId): array
    {
        if (isset(self::$memoizedRoleMeta[$roleId])) {
            return self::$memoizedRoleMeta[$roleId];
        }

        $role = null;
        try {
            $role = $this->roleModel->find($roleId);
        } catch (\Throwable $e) {
            $role = null;
        }

        $meta = [
            'role_id'    => $roleId,
            'role_key'   => $role['role_key'] ?? '',
            'version'    => (int)($role['version'] ?? 1),
            'updated_at' => $role['updated_at'] ?? '2026-01-01 00:00:00',
        ];

        self::$memoizedRoleMeta[$roleId] = $meta;
        return $meta;
    }

    /**
     * Efficiently fetches all granted permission keys for a role with two-tier caching.
     * Returns an associative hash map of ['permission_key' => true] for O(1) lookup.
     */
    public function getRoleGrantedPermissionKeys(int $roleId, string $roleKey): array
    {
        if (isset(self::$memoizedRolePermKeys[$roleId])) {
            return self::$memoizedRolePermKeys[$roleId];
        }

        $cache = service('cache');
        $cacheKey = "sw_perm_keys_role_{$roleKey}";
        $roleMeta = $this->getAuthoritativeRoleMetadata($roleId);

        $cached = $cache->get($cacheKey);
        if (is_array($cached) && isset($cached['version'], $cached['keys'])) {
            if ((int)$cached['version'] === (int)$roleMeta['version']) {
                self::$memoizedRolePermKeys[$roleId] = $cached['keys'];
                return $cached['keys'];
            }
        }

        // Direct two-table query against role_permissions using permission_key
        $keysMap = [];
        try {
            $db = \Config\Database::connect();
            $rows = $db->table('role_permissions')
                ->select('permission_key')
                ->where('role_id', $roleId)
                ->where('is_granted', 1)
                ->get()
                ->getResultArray();

            foreach ($rows as $r) {
                if (!empty($r['permission_key'])) {
                    $keysMap[$r['permission_key']] = true;
                }
            }
        } catch (\Throwable $e) {
            $keysMap = [];
        }

        // Store back into cache
        try {
            $cache->save($cacheKey, [
                'version' => $roleMeta['version'],
                'keys'    => $keysMap,
            ], 86400);
        } catch (\Throwable $e) {
            log_message('warning', '[RBAC] Failed to write role permission keys cache: ' . $e->getMessage());
        }

        self::$memoizedRolePermKeys[$roleId] = $keysMap;
        return $keysMap;
    }

    /**
     * Alias for hasPermission.
     */
    public function can(string $permissionCode, ?int $userId = null, ?string $roleKey = null): bool
    {
        return $this->hasPermission($permissionCode, $userId, $roleKey);
    }

    /**
     * Check if a menu is visible for a user by menu code.
     * Uses Two-Table architecture by resolving menu codes directly to *.view permissions.
     */
    public function can_menu(string $menuCode, ?int $userId = null, ?string $roleKey = null): bool
    {
        $user = $this->resolveAuthoritativeUser($userId, $roleKey);
        if (!$user) {
            return false;
        }

        // Super Admin bypass
        if ($user['access_level'] === 'superadmin') {
            return true;
        }

        // Inactive role check
        if (isset($user['role_status']) && (string)$user['role_status'] !== '1') {
            return false;
        }

        // Global profile menu is always accessible to all authenticated users
        if ($menuCode === 'navbar_profile_menu') {
            return true;
        }

        $userIdParam = ($userId !== null && $userId > 0) ? (int)$userId : (!empty($user['user_id']) ? (int)$user['user_id'] : null);
        $roleKeyVal = (string)($user['access_level'] ?? '');

        // 1. Check mapped menu permissions
        if (isset(self::$menuToPermissionMap[$menuCode])) {
            $mapped = self::$menuToPermissionMap[$menuCode];
            if (is_array($mapped)) {
                foreach ($mapped as $permKey) {
                    if ($this->hasPermission($permKey, $userIdParam, $roleKeyVal)) {
                        return true;
                    }
                }
                return false;
            }
            return $this->hasPermission($mapped, $userIdParam, $roleKeyVal);
        }

        // 2. Department menu code shortcuts (e.g. proc_2 => process_waves.dept_2, loss_2 => loss.dept_2)
        if (str_starts_with($menuCode, 'proc_')) {
            $deptId = substr($menuCode, 5);
            return $this->hasPermission("process_waves.dept_{$deptId}", $userIdParam, $roleKeyVal);
        }
        if (str_starts_with($menuCode, 'loss_') && $menuCode !== 'loss_hub') {
            $deptId = substr($menuCode, 5);
            return $this->hasPermission("loss.dept_{$deptId}", $userIdParam, $roleKeyVal);
        }

        // 3. Fallback: treat menuCode itself as a permission key (e.g. dashboard.view, waves.view)
        return $this->hasPermission($menuCode, $userIdParam, $roleKeyVal);
    }

    /**
     * Primary permission verification incorporating Two-Table role_permissions lookup.
     */
    public function hasPermission(string $permissionCode, ?int $userId = null, ?string $roleKey = null): bool
    {
        $user = $this->resolveAuthoritativeUser($userId, $roleKey);
        if (!$user) {
            return false;
        }

        // Super Admin possesses immutable, unrestricted system bypass
        if ($user['access_level'] === 'superadmin') {
            return true;
        }

        // Inactive role check
        if (isset($user['role_status']) && (string)$user['role_status'] !== '1') {
            return false;
        }

        $roleId  = (int)$user['role_id'];
        $roleKey = (string)$user['access_level'];

        // Authoritative Role Permissions Check (O(1) in-memory hash map from role_permissions)
        $grantedMap = $this->getRoleGrantedPermissionKeys($roleId, $roleKey);
        return isset($grantedMap[$permissionCode]);
    }

    /**
     * Checks user override for a specific permission code (pure role inheritance returns null).
     */
    protected function checkUserOverride(int $userId, string $permissionCode): ?bool
    {
        return null;
    }

    /**
     * Checks if a menu item is visible for a user by menuId (backward compatibility).
     */
    public function isMenuVisibleForUser(int $menuId, array $user): bool
    {
        if ($user['access_level'] === 'superadmin') {
            return true;
        }

        $menu = $this->menuModel->find($menuId);
        if (!$menu) {
            return false;
        }

        return $this->can_menu($menu['menu_code'], (int)$user['user_id'], $user['access_level']);
    }

    /**
     * Compiles and caches the dynamic Sidebar Menu tree adhering to the Dual-Tier Cache Strategy.
     */
    public function getSidebarMenuTree(?string $roleKey = null, ?int $userId = null): array
    {
        $user = $this->resolveAuthoritativeUser($userId, $roleKey);
        if (!$user) {
            return [];
        }

        $targetRoleId  = (int)$user['role_id'];
        $targetRoleKey = $user['access_level'];

        $cacheKey = "sw_perm_menus_role_{$targetRoleKey}";
        $roleMeta = $this->getAuthoritativeRoleMetadata($targetRoleId);

        // Try reading from cache with reliable stale detection
        $cache = service('cache');
        $cached = $cache->get($cacheKey);

        if (is_array($cached) && isset($cached['role_updated_at'], $cached['tree'])) {
            if ($cached['role_updated_at'] === $roleMeta['updated_at']) {
                return $cached['tree']; // Sub-millisecond hit
            }
        }

        // Cache miss or stale: Compile directly
        $tree = $this->compileSidebarMenuTree($targetRoleId, $user);

        // Store back in cache
        try {
            $cache->save($cacheKey, [
                'role_updated_at' => $roleMeta['updated_at'],
                'tree'            => $tree,
            ], 86400);
        } catch (\Throwable $e) {
            log_message('warning', '[RBAC] Failed to write menu cache: ' . $e->getMessage());
        }

        return $tree;
    }

    /**
     * Builds the hierarchical sidebar menu tree using Two-Table permission checks.
     */
    protected function compileSidebarMenuTree(int $roleId, array $user): array
    {
        $allMenus = $this->menuModel->getActiveMenus('sidebar');

        // Build quick lookup map
        $menuMap = [];
        foreach ($allMenus as $m) {
            $m['menu_id']   = (int)$m['menu_id'];
            $m['parent_id'] = $m['parent_id'] ? (int)$m['parent_id'] : null;
            $m['children']  = [];
            $menuMap[$m['menu_id']] = $m;
        }

        $tree = [];
        foreach ($menuMap as $id => $menu) {
            if (!$this->can_menu($menu['menu_code'], (int)$user['user_id'], $user['access_level'])) {
                continue;
            }

            if ($menu['parent_id'] === null) {
                $tree[$id] = $menu;
            } else {
                // If parent is also visible, nest under parent
                $parentId = $menu['parent_id'];
                if (isset($menuMap[$parentId]) && $this->can_menu($menuMap[$parentId]['menu_code'], (int)$user['user_id'], $user['access_level'])) {
                    $menuMap[$parentId]['children'][$id] = $menu;
                }
            }
        }

        // Populate nested children into root items
        foreach ($tree as $id => &$rootItem) {
            if (isset($menuMap[$id]['children'])) {
                $rootItem['children'] = $menuMap[$id]['children'];
                foreach ($rootItem['children'] as $childId => &$childItem) {
                    if (isset($menuMap[$childId]['children'])) {
                        $childItem['children'] = $menuMap[$childId]['children'];
                    }
                }
            }
        }

        return $tree;
    }

    /**
     * Check if a user has active individual overrides (pure role inheritance returns false).
     */
    public function hasActiveUserOverrides(int $userId): bool
    {
        return false;
    }

    /**
     * Reliable post-commit cache invalidator for a role.
     */
    public function invalidateRoleCache(string $roleKey, int $roleId): void
    {
        $this->roleModel->bumpVersion($roleId);
        unset(self::$memoizedRoleMeta[$roleId], self::$memoizedRolePermKeys[$roleId]);

        $cache = service('cache');

        try {
            // Delete Tier-1 role cache
            $cache->delete("sw_perm_keys_role_{$roleKey}");
            $cache->delete("sw_perm_menus_role_{$roleKey}");
            $cache->delete("sw_perm_codes_role_{$roleKey}");
            $cache->delete("sw_perm_navbar_role_{$roleKey}");

            // Increment atomic global version
            $globalVersion = (int)$cache->get('sw_perm_global_version');
            $cache->save('sw_perm_global_version', $globalVersion + 1, 0);
        } catch (\Throwable $e) {
            log_message('warning', '[RBAC] Cache invalidation warning: ' . $e->getMessage());
        }
    }

    /**
     * Invalidate caches specific to an individual user.
     */
    public function invalidateUserCache(int $userId): void
    {
        self::$memoizedUser = null;
    }

    /**
     * Computes the Five-Category Permission Classification statistics using Two-Table architecture.
     */
    public function getSystemStatistics(?int $roleId = null): array
    {
        $db = \Config\Database::connect();

        // CATEGORY 1: SYSTEM UNIVERSE
        $totalPerms = $this->permissionModel->countAllResults();
        if ($totalPerms === 0) {
            $totalPerms = (int)$db->query("SELECT COUNT(DISTINCT permission_key) as c FROM role_permissions")->getRow()->c;
        }

        $totalAvailablePages = (int)$db->query("SELECT COUNT(DISTINCT permission_key) as c FROM role_permissions WHERE permission_key LIKE '%.view'")->getRow()->c;
        $totalAvailableActions = max(0, $totalPerms - $totalAvailablePages);
        $totalSidebarMenus = $this->menuModel->whereIn('menu_type', ['sidebar', 'both'])->where('status', '1')->countAllResults();
        $totalNavbarItems  = $this->menuModel->whereIn('menu_type', ['navbar', 'both'])->where('status', '1')->countAllResults();
        $totalUsers        = $this->userModel->where('status', '1')->countAllResults();
        $totalRoles        = $this->roleModel->where('status', '1')->countAllResults();
        $totalModules      = count($db->query("SELECT DISTINCT module_key FROM menus WHERE status = '1'")->getResultArray());
        $totalSubmenus     = $this->menuModel->where('parent_id IS NOT NULL', null, false)->where('status', '1')->countAllResults();

        $roleStats = [];
        $roles = $this->roleModel->getActiveRoles();

        foreach ($roles as $r) {
            $rId = (int)$r['role_id'];

            // CATEGORY 2: EXPLICITLY GRANTED (ROLE)
            $assignedPerms = (int)$db->query("SELECT COUNT(*) as c FROM role_permissions WHERE role_id = {$rId} AND is_granted = 1")->getRow()->c;
            $assignedSidebarMenus = 0;
            foreach ($this->menuModel->getActiveMenus('sidebar') as $sm) {
                if ($this->can_menu($sm['menu_code'], null, $r['role_key'])) {
                    $assignedSidebarMenus++;
                }
            }
            $assignedNavbarItems = 0;
            foreach ($this->menuModel->whereIn('menu_type', ['navbar', 'both'])->where('status', '1')->findAll() as $nm) {
                if ($this->can_menu($nm['menu_code'], null, $r['role_key'])) {
                    $assignedNavbarItems++;
                }
            }

            // CATEGORY 3: EXPLICITLY DENIED (ROLE)
            $deniedPerms = (int)$db->query("SELECT COUNT(*) as c FROM role_permissions WHERE role_id = {$rId} AND is_granted = 0")->getRow()->c;

            // CATEGORY 4: NOT ASSIGNED
            $unassignedPerms = max(0, $totalPerms - ($assignedPerms + $deniedPerms));
            $unassignedSidebarMenus = max(0, $totalSidebarMenus - $assignedSidebarMenus);
            $unassignedNavbarItems  = max(0, $totalNavbarItems - $assignedNavbarItems);

            // CATEGORY 5: ACCESSIBLE PAGES & ACTIONS
            $accessiblePages = (int)$db->query("
                SELECT COUNT(*) as c 
                FROM role_permissions 
                WHERE role_id = {$rId} AND is_granted = 1 AND permission_key LIKE '%.view'
            ")->getRow()->c;
            $assignedActions = (int)$db->query("
                SELECT COUNT(*) as c 
                FROM role_permissions 
                WHERE role_id = {$rId} AND is_granted = 1 AND permission_key NOT LIKE '%.view'
            ")->getRow()->c;

            $usersInRole = (int)$db->query("SELECT COUNT(*) as c FROM user WHERE access_level = '{$r['role_key']}' AND status = '1'")->getRow()->c;
            $coverage = ($totalPerms > 0) ? round(($assignedPerms / $totalPerms) * 100, 1) : 0;

            $roleStats[$r['role_key']] = [
                'role_id'                => $rId,
                'role_key'               => $r['role_key'],
                'role_name'              => $r['role_name'],
                'total_perms'            => $totalPerms,
                'assigned_perms'         => $assignedPerms,
                'denied_perms'           => $deniedPerms,
                'explicit_denied'        => $deniedPerms,
                'unassigned_perms'       => $unassignedPerms,
                'not_assigned'           => $unassignedPerms,
                'total_sidebar_menus'    => $totalSidebarMenus,
                'assigned_sidebar_menus' => $assignedSidebarMenus,
                'unassigned_sidebar_menus' => $unassignedSidebarMenus,
                'total_navbar_items'     => $totalNavbarItems,
                'assigned_navbar_items'  => $assignedNavbarItems,
                'unassigned_navbar_items'=> $unassignedNavbarItems,
                'total_pages'            => $totalAvailablePages,
                'accessible_pages'       => $accessiblePages,
                'inaccessible_pages'     => max(0, $totalAvailablePages - $accessiblePages),
                'total_actions'          => $totalAvailableActions,
                'assigned_actions'       => $assignedActions,
                'unassigned_actions'     => max(0, $totalAvailableActions - $assignedActions),
                'users_count'            => $usersInRole,
                'coverage_pct'           => $coverage,
            ];
        }

        return [
            'system' => [
                'total_users'       => $totalUsers,
                'total_roles'       => $totalRoles,
                'total_menus'       => $totalSidebarMenus + $totalNavbarItems,
                'total_sidebar'     => $totalSidebarMenus,
                'total_submenus'    => $totalSubmenus,
                'total_modules'     => $totalModules,
                'total_permissions' => $totalPerms,
                'total_pages'       => $totalAvailablePages,
                'total_actions'     => $totalAvailableActions,
            ],
            'roles' => $roleStats,
        ];
    }
}
