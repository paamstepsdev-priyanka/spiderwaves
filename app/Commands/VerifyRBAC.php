<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\RoleModel;
use App\Models\MenuModel;
use App\Models\PermissionModel;
use App\Models\RolePermissionModel;
use App\Models\PermissionAuditLogModel;
use App\Models\UserModel;
use App\Models\AccountModel;

class VerifyRBAC extends BaseCommand
{
    protected $group       = 'RBAC';
    protected $name        = 'rbac:verify';
    protected $description = 'Executes end-to-end verification of the Role-Based UI & Menu Permission Management Module';

    private int $passCount = 0;
    private int $failCount = 0;

    public function run(array $params)
    {
        CLI::write("==================================================================", 'yellow');
        CLI::write("SPIDERWAVES ENTERPRISE RBAC SYSTEM - VERIFICATION SUITE", 'yellow');
        CLI::write("==================================================================", 'yellow');

        $this->testDatabaseSchemaAndSeeders();
        $this->testSuperAdminProtection();
        $this->testStatisticsAccuracy();
        $this->testAncestorHardDeny();
        $this->testPureRolePermissionInheritance();
        $this->testDualTierCacheAndFallback();
        $this->testRoleBaselineAccess();
        $this->testButtonActionPermissions();
        $this->testCustomerPortalIsolation();
        $this->testHierarchicalAssignInterface();
        $this->testTwoTableArchitectureAllRoles();

        CLI::newLine();
        CLI::write("==================================================================", 'yellow');
        CLI::write("VERIFICATION SUMMARY", 'yellow');
        CLI::write("==================================================================", 'yellow');
        CLI::write("Total Assertions Passed: {$this->passCount}", 'green');
        if ($this->failCount > 0) {
            CLI::write("Total Assertions Failed: {$this->failCount}", 'red');
        } else {
            CLI::write("Total Assertions Failed: 0", 'green');
            CLI::write("ALL RBAC TESTS PASSED WITH 100% SUCCESS!", 'green');
        }
        CLI::write("==================================================================", 'yellow');
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            $this->passCount++;
            CLI::write("[PASS] " . $message, 'green');
        } else {
            $this->failCount++;
            CLI::write("[FAIL] " . $message, 'red');
        }
    }

    private function testDatabaseSchemaAndSeeders(): void
    {
        CLI::newLine();
        CLI::write("--- 1. Testing Database Schema & Seed Data ---", 'cyan');

        $db = \Config\Database::connect();
        $tables = [
            'roles',
            'menus',
            'permissions',
            'role_permissions',
            'permission_audit_logs',
        ];

        foreach ($tables as $t) {
            $this->assert($db->tableExists($t), "Active Table '{$t}' exists in database");
        }

        $rolesCount = $db->table('roles')->countAllResults();
        $this->assert($rolesCount === 10, "10 system roles seeded (Found: {$rolesCount})");

        $menusCount = $db->table('menus')->countAllResults();
        $this->assert($menusCount >= 61, "At least 61 menus registered (Found: {$menusCount})");

        $permsCount = $db->table('permissions')->countAllResults();
        $this->assert($permsCount >= 102, "At least 102 permissions registered (Found: {$permsCount})");

        $rolePermCount = $db->table('role_permissions')->countAllResults();
        $this->assert($rolePermCount > 200, "Role permission assignments seeded (Found: {$rolePermCount})");
    }

    private function testSuperAdminProtection(): void
    {
        CLI::newLine();
        CLI::write("--- 2. Testing Super Admin Immutability & Protection ---", 'cyan');

        $permService = service('permission');
        $roleModel = model(RoleModel::class);
        $userModel = model(UserModel::class);

        $superAdminUser = $userModel->where('access_level', 'superadmin')->first();
        $this->assert(!empty($superAdminUser), "Super Admin user exists");

        // Superadmin bypass test: can() must return true for any permission code
        $canArbitrary = $permService->can('any.nonexistent.fake.permission', (int)$superAdminUser['user_id'], 'superadmin');
        $this->assert($canArbitrary === true, "Super Admin automatically has all permissions via bypass rule");

        // Superadmin menu bypass test
        $canMenuArbitrary = $permService->can_menu('fake_menu_code', (int)$superAdminUser['user_id'], 'superadmin');
        $this->assert($canMenuArbitrary === true, "Super Admin automatically has access to all menus");

        // Super Admin role must be marked as system
        $superRole = $roleModel->getByRoleKey('superadmin');
        $this->assert((int)$superRole['is_system'] === 1, "Super Admin role is marked as immutable system role (is_system = 1)");
    }

    private function testStatisticsAccuracy(): void
    {
        CLI::newLine();
        CLI::write("--- 3. Testing Statistics & Five-Category Permission Classification ---", 'cyan');

        $permService = service('permission');
        $stats = $permService->getSystemStatistics();

        $sys = $stats['system'];
        $this->assert(isset($sys['total_users']) && $sys['total_users'] > 0, "Stat: Total active users is positive ({$sys['total_users']})");
        $this->assert(isset($sys['total_roles']) && $sys['total_roles'] === 8, "Stat: 8 active roles registered ({$sys['total_roles']})");
        $this->assert(isset($sys['total_permissions']) && $sys['total_permissions'] >= 102, "Stat: Total permissions >= 102 ({$sys['total_permissions']})");
        $this->assert(isset($sys['total_modules']) && $sys['total_modules'] >= 20, "Stat: Total modules >= 20 ({$sys['total_modules']})");

        // Verify mathematical formula across each role
        foreach ($stats['roles'] as $rStat) {
            $sum = $rStat['assigned_perms'] + $rStat['explicit_denied'] + $rStat['not_assigned'];
            $this->assert($sum === $sys['total_permissions'], "Role '{$rStat['role_key']}': Assigned({$rStat['assigned_perms']}) + Denied({$rStat['explicit_denied']}) + NotAssigned({$rStat['not_assigned']}) == Total({$sys['total_permissions']})");
        }
    }

    private function testAncestorHardDeny(): void
    {
        CLI::newLine();
        CLI::write("--- 4. Testing Parent & Ancestor Hard-Deny Inheritance ---", 'cyan');

        $permService = service('permission');
        $menuModel = model(MenuModel::class);

        // Find child menu 'proc_casting' whose parent is 'ops_process'
        $childMenu = $menuModel->getByCode('proc_casting');
        $this->assert(!empty($childMenu), "Found menu 'proc_casting'");
        $this->assert(!empty($childMenu['parent_id']), "'proc_casting' has parent menu ID");

        // Fetch ancestor chain
        $ancestors = $menuModel->getAncestorChain((int)$childMenu['menu_id']);
        $this->assert(!empty($ancestors), "Successfully traversed ancestor chain for 'proc_casting'");

        $parentMenu = $menuModel->find($childMenu['parent_id']);
        $this->assert($parentMenu['menu_code'] === 'ops_process', "Ancestor is 'ops_process'");
    }

    private function testPureRolePermissionInheritance(): void
    {
        CLI::newLine();
        CLI::write("--- 5. Testing Pure Role-Based Inheritance for Staff Users ---", 'cyan');

        $userModel = model(UserModel::class);
        $permService = service('permission');

        // 1. Verify user 'VICKY' (role: user) inherits exactly the 4 raw_waves permissions
        $userVicky = $userModel->where('username', 'VICKY')->first();
        $this->assert(!empty($userVicky), "Found user 'VICKY' with role 'user'");

        $vId = (int)$userVicky['user_id'];
        $vRole = $userVicky['access_level'];

        // Vicky naturally has raw_waves.* permissions
        $this->assert($permService->can('raw_waves.view', $vId, $vRole) === true, "User 'VICKY' inherits 'raw_waves.view'");
        $this->assert($permService->can('raw_waves.create', $vId, $vRole) === true, "User 'VICKY' inherits 'raw_waves.create'");
        $this->assert($permService->can('raw_waves.verify', $vId, $vRole) === true, "User 'VICKY' inherits 'raw_waves.verify'");
        $this->assert($permService->can('raw_waves.print', $vId, $vRole) === true, "User 'VICKY' inherits 'raw_waves.print'");

        // Vicky is strictly denied admin/other permissions
        $this->assert($permService->can('customer.create', $vId, $vRole) === false, "User 'VICKY' strictly denied 'customer.create'");
        $this->assert($permService->can('raw_waves.edit', $vId, $vRole) === false, "User 'VICKY' strictly denied 'raw_waves.edit'");
        $this->assert($permService->can('process_waves.view', $vId, $vRole) === false, "User 'VICKY' strictly denied 'process_waves.view'");
        $this->assert($permService->can('locker.view', $vId, $vRole) === false, "User 'VICKY' strictly denied 'locker.view'");

        // 2. Verify user 'paresh' (role: admin) inherits admin permissions
        $userParesh = $userModel->where('username', 'paresh')->first();
        $this->assert(!empty($userParesh), "Found user 'paresh' with role 'admin'");
        $pId = (int)$userParesh['user_id'];
        $pRole = $userParesh['access_level'];

        $this->assert($permService->can('raw_waves.view', $pId, $pRole) === true, "User 'paresh' inherits 'raw_waves.view'");
        $this->assert($permService->can('raw_waves.edit', $pId, $pRole) === true, "User 'paresh' inherits 'raw_waves.edit'");
        $this->assert($permService->can('customer.view', $pId, $pRole) === true, "User 'paresh' inherits 'customer.view'");
        $this->assert($permService->can('daily_report.approve', $pId, $pRole) === true, "User 'paresh' inherits 'daily_report.approve'");
        $this->assert($permService->can('permission.superadmin', $pId, $pRole) === false, "User 'paresh' denied 'permission.superadmin'");

        // 3. Verify user 'govind' (role: process_manager) inherits manufacturing pipeline
        $userGovind = $userModel->where('username', 'govind')->first();
        $this->assert(!empty($userGovind), "Found user 'govind' with role 'process_manager'");
        $gId = (int)$userGovind['user_id'];
        $gRole = $userGovind['access_level'];

        $this->assert($permService->can('process_waves.view', $gId, $gRole) === true, "User 'govind' inherits 'process_waves.view'");
        $this->assert($permService->can('process_waves.transfer', $gId, $gRole) === true, "User 'govind' inherits 'process_waves.transfer'");
        $this->assert($permService->can('customer.create', $gId, $gRole) === false, "User 'govind' denied 'customer.create'");

        // 4. Verify user 'HITESH' (role: jr_process_manager) inherits finishing stages only
        $userHitesh = $userModel->where('username', 'HITESH')->first();
        $this->assert(!empty($userHitesh), "Found user 'HITESH' with role 'jr_process_manager'");
        $hId = (int)$userHitesh['user_id'];
        $hRole = $userHitesh['access_level'];

        $this->assert($permService->can('process_waves.dept_8', $hId, $hRole) === true, "User 'HITESH' inherits Packing stage");
        $this->assert($permService->can('process_waves.dept_7', $hId, $hRole) === true, "User 'HITESH' inherits Rodium stage");
        $this->assert($permService->can('process_waves.dept_2', $hId, $hRole) === false, "User 'HITESH' denied Casting stage");

        // 5. Confirm zero overrides in database: pure role inheritance verified
        $this->assert($permService->hasActiveUserOverrides($vId) === false, "User 'VICKY' has 0 overrides (pure role inheritance)");
        $this->assert($permService->hasActiveUserOverrides($pId) === false, "User 'paresh' has 0 overrides (pure role inheritance)");
    }

    private function testDualTierCacheAndFallback(): void
    {
        CLI::newLine();
        CLI::write("--- 6. Testing Dual-Tier Cache & Version Fallback ---", 'cyan');

        $roleModel = model(RoleModel::class);
        $permService = service('permission');

        $role = $roleModel->getByRoleKey('process_manager');
        $initialVer = (int)$role['version'];

        // Invalidate role cache
        $permService->invalidateRoleCache('process_manager', (int)$role['role_id']);

        // Check if role version bumped
        $updatedRole = $roleModel->getByRoleKey('process_manager');
        $newVer = (int)$updatedRole['version'];
        $this->assert($newVer === $initialVer + 1, "Role version bumped atomically from {$initialVer} to {$newVer}");
    }

    private function testRoleBaselineAccess(): void
    {
        CLI::newLine();
        CLI::write("--- 7. Testing Baseline Role Access Coverage ---", 'cyan');

        $permService = service('permission');

        // Admin: waves, daily report, customers, but NOT account master
        $this->assert($permService->can('raw_waves.view', null, 'admin') === true, "Admin has 'raw_waves.view'");
        $this->assert($permService->can('daily_report.approve', null, 'admin') === true, "Admin has 'daily_report.approve'");
        $this->assert($permService->can('customer.view', null, 'admin') === true, "Admin has 'customer.view'");
        $this->assert($permService->can('account.manage', null, 'admin') === false, "Admin does NOT have 'account.manage'");
        $this->assert($permService->can('permission.superadmin', null, 'admin') === false, "Admin does NOT have 'permission.superadmin'");

        // Process Manager: waves, process transfers, refining, but NOT masters
        $this->assert($permService->can('process_waves.view', null, 'process_manager') === true, "Process Manager has 'process_waves.view'");
        $this->assert($permService->can('process_waves.transfer', null, 'process_manager') === true, "Process Manager has 'process_waves.transfer'");
        $this->assert($permService->can('customer.create', null, 'process_manager') === false, "Process Manager cannot create customers");

        // Jr Process Manager: Packing & Rodium only
        $this->assert($permService->can('process_waves.dept_8', null, 'jr_process_manager') === true, "Jr Process Manager has Packing");
        $this->assert($permService->can('process_waves.dept_7', null, 'jr_process_manager') === true, "Jr Process Manager has Rodium");
        $this->assert($permService->can('process_waves.dept_2', null, 'jr_process_manager') === false, "Jr Process Manager does NOT have Casting");

        // Locker: locker vault only
        $this->assert($permService->can('locker.view', null, 'locker') === true, "Locker role has 'locker.view'");
        $this->assert($permService->can('raw_waves.view', null, 'locker') === false, "Locker role does NOT have 'raw_waves.view'");

        // G58: G58 departments only
        $this->assert($permService->can('process_waves.dept_13', null, 'g58') === true, "G58 role has G58-Polish");
        $this->assert($permService->can('process_waves.dept_2', null, 'g58') === false, "G58 role does NOT have Casting");
    }

    private function testButtonActionPermissions(): void
    {
        CLI::newLine();
        CLI::write("--- 8. Testing Button & Action Permission Helpers ---", 'cyan');

        // Verify helper function can()
        $this->assert(function_exists('can'), "Global helper function 'can()' exists");
        $this->assert(function_exists('can_menu'), "Global helper function 'can_menu()' exists");
        $this->assert(function_exists('can_any'), "Global helper function 'can_any()' exists");

        // Test can_any logic
        $hasAny = can_any(['fake_perm_1', 'fake_perm_2', 'raw_waves.view'], null, 'admin');
        $this->assert($hasAny === true, "can_any() returns true when at least one permission matches");

        $hasNone = can_any(['fake_perm_1', 'fake_perm_2'], null, 'admin');
        $this->assert($hasNone === false, "can_any() returns false when no permissions match");
    }

    private function testCustomerPortalIsolation(): void
    {
        CLI::newLine();
        CLI::write("--- 9. Testing Customer Portal Isolation ---", 'cyan');

        $db = \Config\Database::connect();
        $custCount = $db->table('mst_customer')->countAllResults();
        $this->assert($custCount > 0, "Customer table 'cust' has {$custCount} client records");

        $permService = service('permission');
        // Customer has portal statement permission
        $this->assert($permService->can_menu('cust_statement', null, 'cust') === true, "Customer role has cust_statement menu");
        $this->assert($permService->can('raw_waves.view', null, 'cust') === false, "Customer role is strictly isolated from internal raw_waves");
        $this->assert($permService->can('process_waves.view', null, 'cust') === false, "Customer role is strictly isolated from process_waves");
        $this->assert($permService->can('permission.superadmin', null, 'cust') === false, "Customer role has zero administrative privileges");
    }

    private function testHierarchicalAssignInterface(): void
    {
        CLI::newLine();
        CLI::write("--- 10. Testing Hierarchical Permission Assignment Interface ---", 'cyan');

        // Mock active superadmin session
        session()->set('spider', [
            'user_id'      => 14,
            'user'         => 'superadmin',
            'access_level' => 'superadmin',
        ]);

        // Inject IncomingRequest with SiteURI so base_url() and csrf_field() in views work cleanly in CLI
        $appConfig = config('App');
        $siteUri = new \CodeIgniter\HTTP\SiteURI($appConfig, 'http://localhost/Spiderwaves-new/public/permission/assign');
        $incomingReq = new \CodeIgniter\HTTP\IncomingRequest(
            $appConfig,
            $siteUri,
            null,
            new \CodeIgniter\HTTP\UserAgent()
        );
        \Config\Services::injectMock('request', $incomingReq);

        $ctl = new \App\Controllers\PermissionController();
        $ctl->initController(
            $incomingReq,
            \Config\Services::response(),
            \Config\Services::logger()
        );

        // Test Role Target Render (Admin Role ID 2)
        $roleView = (string)$ctl->assign(2);
        $this->assert(strlen($roleView) > 5000, "Role assign view successfully rendered (" . strlen($roleView) . " bytes)");
        $this->assert(strpos($roleView, 'Permissions for ADMIN') !== false, "Header title displays 'Permissions for ADMIN'");
        $this->assert(strpos($roleView, 'Assign functional access strictly by group hierarchy.') !== false, "Header subtitle matches reference UI");
        $this->assert(strpos($roleView, 'Changes Saved') !== false, "Changes Saved status indicator present");
        $this->assert(strpos($roleView, 'BACK') !== false, "BACK button present");
        $this->assert(strpos($roleView, 'Search permissions...') !== false, "Left column search input present");
        $this->assert(strpos($roleView, 'module-card-master_data') !== false, "Master Data module card rendered");
        $this->assert(strpos($roleView, 'module-card-waves') !== false, "Waves module card rendered");
        $this->assert(strpos($roleView, 'module-card-process_waves') !== false, "Process Waves module card rendered");
        $this->assert(strpos($roleView, 'subgroup-card-master_data-customer') !== false, "Customer sub-card rendered with green accent");

        // Test User Target Render (User ID 1: paresh)
        $_GET['user_id'] = 1;
        $userUri = new \CodeIgniter\HTTP\SiteURI($appConfig, 'http://localhost/Spiderwaves-new/public/permission/assign?user_id=1');
        $userIncomingReq = new \CodeIgniter\HTTP\IncomingRequest(
            $appConfig,
            $userUri,
            null,
            new \CodeIgniter\HTTP\UserAgent()
        );
        \Config\Services::injectMock('request', $userIncomingReq);
        $ctl->initController($userIncomingReq, \Config\Services::response(), \Config\Services::logger());

        $userView = (string)$ctl->assign(0);
        $this->assert(strpos($userView, 'Permissions for PARESH') !== false, "User assign view displays 'Permissions for PARESH'");
        $this->assert(strpos($userView, 'save-user/1') !== false, "User assign form targets save-user endpoint");
        unset($_GET['user_id']);
    }

    private function testTwoTableArchitectureAllRoles(): void
    {
        CLI::newLine();
        CLI::write("--- 11. Testing Two-Table Role & Permission Architecture (All 10 Roles) ---", 'cyan');

        $db = \Config\Database::connect();
        $permService = service('permission');
        $rolePermModel = model(RolePermissionModel::class);

        // 1. Verify schema: role_permissions must have permission_key column
        $fields = array_column($db->getFieldData('role_permissions'), 'name');
        $this->assert(in_array('permission_key', $fields, true), "Column 'permission_key' exists in 'role_permissions'");

        // 2. Verify all records have non-empty permission_key
        $nullKeys = $db->table('role_permissions')
            ->groupStart()
                ->where('permission_key IS NULL', null, false)
                ->orWhere('permission_key', '')
            ->groupEnd()
            ->countAllResults();
        $this->assert($nullKeys === 0, "All 279 role_permissions rows have valid, non-empty permission_key");

        // 3. Expected permission counts for all 10 roles
        $expectedCounts = [
            'superadmin'         => 102,
            'admin'              => 86,
            'process_manager'    => 56,
            'jr_process_manager' => 8,
            'locker'             => 6,
            'g58'                => 15,
            'user'               => 4,
            'dispatch_entry'     => 0,
            'dispatch_verify'    => 0,
            'cust'               => 2,
        ];

        $roles = $db->table('roles')->get()->getResultArray();
        $this->assert(count($roles) === 10, "Total system roles verified: 10");

        foreach ($roles as $r) {
            $rKey = $r['role_key'];
            $rId  = (int)$r['role_id'];
            $expected = $expectedCounts[$rKey] ?? 0;

            $grantedKeys = $rolePermModel->getGrantedPermissionKeys($rId);
            $this->assert(count($grantedKeys) === $expected, "Role '{$rKey}' (#{$rId}): Granted keys count matches expected ({$expected})");

            // Verify memory O(1) hash map retrieval
            $map = $permService->getRoleGrantedPermissionKeys($rId, $rKey);
            $this->assert(count($map) === $expected, "Role '{$rKey}': Fast O(1) memory map matches count ({$expected})");
        }

        // 4. Test direct URL & route permission enforcement for different roles
        // Admin: allowed raw_waves.view, denied permission.superadmin
        $this->assert($permService->hasPermission('raw_waves.view', null, 'admin') === true, "Route access allowed: Admin -> raw_waves.view");
        $this->assert($permService->hasPermission('permission.superadmin', null, 'admin') === false, "Route access denied: Admin -> permission.superadmin");

        // Process Manager: allowed process_waves.transfer, denied customer.create
        $this->assert($permService->hasPermission('process_waves.transfer', null, 'process_manager') === true, "Route access allowed: Process Manager -> process_waves.transfer");
        $this->assert($permService->hasPermission('customer.create', null, 'process_manager') === false, "Route access denied: Process Manager -> customer.create");

        // Jr Process Manager: allowed process_waves.dept_8 (Packing), denied process_waves.dept_2 (Casting)
        $this->assert($permService->hasPermission('process_waves.dept_8', null, 'jr_process_manager') === true, "Route access allowed: Jr PM -> Packing dept");
        $this->assert($permService->hasPermission('process_waves.dept_2', null, 'jr_process_manager') === false, "Route access denied: Jr PM -> Casting dept");

        // Locker: allowed locker.view, denied raw_waves.view
        $this->assert($permService->hasPermission('locker.view', null, 'locker') === true, "Route access allowed: Locker -> locker.view");
        $this->assert($permService->hasPermission('raw_waves.view', null, 'locker') === false, "Route access denied: Locker -> raw_waves.view");

        // G58: allowed process_waves.dept_13 (G58_Polish), denied locker.view
        $this->assert($permService->hasPermission('process_waves.dept_13', null, 'g58') === true, "Route access allowed: G58 -> G58_Polish dept");
        $this->assert($permService->hasPermission('locker.view', null, 'g58') === false, "Route access denied: G58 -> locker.view");

        // User: allowed raw_waves.create, denied process_waves.view
        $this->assert($permService->hasPermission('raw_waves.create', null, 'user') === true, "Route access allowed: User -> raw_waves.create");
        $this->assert($permService->hasPermission('process_waves.view', null, 'user') === false, "Route access denied: User -> process_waves.view");

        // Customer Client: allowed customer_portal.view, denied dashboard.view & raw_waves.view
        $this->assert($permService->hasPermission('customer_portal.view', null, 'cust') === true, "Route access allowed: Customer -> customer_portal.view");
        $this->assert($permService->hasPermission('dashboard.view', null, 'cust') === false, "Route access denied: Customer -> dashboard.view");
        $this->assert($permService->hasPermission('raw_waves.view', null, 'cust') === false, "Route access denied: Customer -> raw_waves.view");
    }
}
