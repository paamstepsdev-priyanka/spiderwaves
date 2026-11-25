<?php

namespace App\Controllers;

use App\Models\RoleModel;
use App\Models\MenuModel;
use App\Models\PermissionModel;
use App\Models\RolePermissionModel;
use App\Models\PermissionAuditLogModel;
use App\Models\UserModel;
use App\Services\PermissionService;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionController extends BaseController
{
    protected RoleModel $roleModel;
    protected MenuModel $menuModel;
    protected PermissionModel $permissionModel;
    protected RolePermissionModel $rolePermModel;
    protected PermissionAuditLogModel $auditModel;
    protected UserModel $userModel;
    protected PermissionService $permissionService;

    /**
     * Managed core operational modules for the simplified Role-Based Permission UI.
     */
    protected array $managedModules = [
        'waves' => [
            'key'          => 'waves',
            'name'         => 'Waves',
            'icon'         => 'icon-loop4',
            'color'        => '#0284c7',
            'description'  => 'Raw waves inward queue, voucher management, and wave transactions',
            'primary_menu' => 'ops_waves',
            'primary_perm' => 'raw_waves.view',
            'menus'        => ['ops_waves', 'navbar_pending_badge'],
            'permissions'  => [
                'raw_waves.view', 'raw_waves.create', 'raw_waves.edit', 
                'raw_waves.verify', 'raw_waves.print',
                'waves.view', 'waves.create', 'waves.edit', 'waves.view_details',
            ],
        ],
        'locker' => [
            'key'          => 'locker',
            'name'         => 'Locker',
            'icon'         => 'icon-safe',
            'color'        => '#059669',
            'description'  => 'Physical locker stock balances, safe transfers, and physical stock verification',
            'primary_menu' => 'ops_locker',
            'primary_perm' => 'locker.view',
            'menus'        => ['ops_locker', 'ops_locker_verification'],
            'permissions'  => [
                'locker.view', 'locker.check_stock', 'locker.transfer',
                'locker_verification.view', 'locker_verification.manage',
            ],
        ],
        'loss' => [
            'key'          => 'loss',
            'name'         => 'Loss',
            'icon'         => 'icon-pulse2',
            'color'        => '#d97706',
            'description'  => 'Department loss hub, loss records, consolidation to refining, and loss sheets',
            'primary_menu' => 'ops_loss',
            'primary_perm' => 'loss.view',
            'menus'        => [
                'ops_loss', 'loss_hub', 'loss_casting', 'loss_filing', 'loss_heera_light',
                'loss_packing', 'loss_polish', 'loss_rodium', 'loss_setting', 'loss_g58_polish',
                'loss_g58_filing', 'loss_g58_setting', 'loss_galai', 'loss_tash', 'loss_sapphire',
                'loss_magnet', 'loss_zero_balance',
            ],
            'permissions'  => [
                'loss.view', 'loss.consolidate', 'loss.print',
                'loss.dept_2', 'loss.dept_4', 'loss.dept_3', 'loss.dept_8',
                'loss.dept_6', 'loss.dept_7', 'loss.dept_5', 'loss.dept_13',
                'loss.dept_15', 'loss.dept_14', 'loss.dept_20', 'loss.dept_21',
                'loss.dept_22', 'loss.dept_23', 'loss.dept_16',
            ],
        ],
        'stock' => [
            'key'          => 'stock',
            'name'         => 'Stock',
            'icon'         => 'icon-box',
            'color'        => '#7c3aed',
            'description'  => 'Factory-wide stock inventory balances, stock movements, and loss reports',
            'primary_menu' => 'ops_stock',
            'primary_perm' => 'stock.view',
            'menus'        => ['ops_stock'],
            'permissions'  => [
                'stock.view',
            ],
        ],
    ];

    /**
     * Complete list of main sidebar menu items (matching SpiderWaves sidebar navigation).
     */
    protected array $sidebarMenus = [
        ['code' => 'main_dashboard',         'title' => 'Dashboard',                 'icon' => 'icon-grid',             'section' => 'Main'],
        ['code' => 'ops_waves',              'title' => 'Waves',                     'icon' => 'icon-loop4',            'section' => 'Operations'],
        ['code' => 'ops_process',            'title' => 'Process',                   'icon' => 'icon-sync',             'section' => 'Operations'],
        ['code' => 'ops_locker',             'title' => 'Locker',                    'icon' => 'icon-safe',             'section' => 'Operations'],
        ['code' => 'ops_locker_verification','title' => 'Locker Stock Verification','icon' => 'icon-checkmark-circle','section' => 'Operations'],
        ['code' => 'ops_loss',               'title' => 'Loss',                      'icon' => 'icon-pulse2',           'section' => 'Operations'],
        ['code' => 'ops_refining',           'title' => 'Refining',                  'icon' => 'icon-loop4',            'section' => 'Operations'],
        ['code' => 'ops_stock',              'title' => 'Stock',                     'icon' => 'icon-box',              'section' => 'Operations'],
        ['code' => 'masters',                'title' => 'Master',                    'icon' => 'icon-gear',             'section' => 'Masters & Reports'],
        ['code' => 'system_masters',         'title' => 'System Master',             'icon' => 'icon-database',         'section' => 'Masters & Reports'],
        ['code' => 'account_master',         'title' => 'Account',                   'icon' => 'icon-office',           'section' => 'Masters & Reports'],
        ['code' => 'reports',                'title' => 'Report',                    'icon' => 'icon-file-text',        'section' => 'Masters & Reports'],
    ];

    public function __construct()
    {
        $this->roleModel         = new RoleModel();
        $this->menuModel         = new MenuModel();
        $this->permissionModel   = new PermissionModel();
        $this->rolePermModel     = new RolePermissionModel();
        $this->auditModel        = new PermissionAuditLogModel();
        $this->userModel         = new UserModel();
        $this->permissionService = service('permission');
    }

    /**
     * Enforce strict Super Admin access control on all actions in this controller.
     */
    protected function enforceSuperAdmin(): ?ResponseInterface
    {
        $user = $this->permissionService->resolveAuthoritativeUser();

        if (!$user || $user['access_level'] !== 'superadmin') {
            if (method_exists($this->request, 'isAJAX') && $this->request->isAJAX()) {
                return $this->response->setStatusCode(403)->setJSON([
                    'status' => false,
                    'error'  => 'Security Alert: Only Super Admin can access or manage system permissions.',
                ]);
            }

            session()->setFlashdata('error', 'Unauthorized access: Permission Management is restricted to Super Admin only.');
            return redirect()->to(base_url('dashboard'));
        }

        return null;
    }

    /**
     * Retrieve all active operational roles (Admin, Process Manager, Jr. Process Manager, Locker, G58, User).
     * Excludes immutable superadmin and external cust roles.
     */
    protected function getOperationalRoles(): array
    {
        return $this->roleModel->where('status', '1')
            ->whereNotIn('role_key', ['superadmin', 'cust', 'dispatch_entry', 'dispatch_verify'])
            ->orderBy('role_id', 'ASC')
            ->findAll();
    }

    /**
     * Compile ON/OFF boolean states for the 4 core modules for a given role.
     * Uses Two-Table architecture checking role_permissions via permission_key.
     */
    protected function getRoleModuleStates(int $roleId): array
    {
        $role = $this->roleModel->find($roleId);
        if ($role && $role['role_key'] === 'superadmin') {
            return [
                'waves'  => true,
                'locker' => true,
                'loss'   => true,
                'stock'  => true,
            ];
        }

        $grantedKeys = $this->rolePermModel->getGrantedPermissionKeys($roleId);
        $grantedKeyMap = array_flip($grantedKeys);

        $states = [];
        foreach ($this->managedModules as $modKey => $mod) {
            $pPerm = $mod['primary_perm'];
            $states[$modKey] = isset($grantedKeyMap[$pPerm]);
        }

        return $states;
    }

    /**
     * Compile full sidebar menu visibility breakdown for a given role.
     * Uses Two-Table architecture via PermissionService::can_menu().
     */
    protected function getRoleSidebarMenus(int $roleId): array
    {
        $role = $this->roleModel->find($roleId);
        if ($role && $role['role_key'] === 'superadmin') {
            $res = [];
            foreach ($this->sidebarMenus as $sm) {
                $res[$sm['code']] = true;
            }
            return $res;
        }

        $roleKey = $role['role_key'] ?? '';
        $res = [];
        foreach ($this->sidebarMenus as $sm) {
            $res[$sm['code']] = $this->permissionService->can_menu($sm['code'], null, $roleKey);
        }

        return $res;
    }

    /**
     * Primary Screen: Professional User-wise Role & Permission Management Table.
     * Displays summary cards, search & filters, user-wise table with 4 core module badges,
     * and modals for user audit and centralized role permission management.
     */
    public function index()
    {
        if ($guard = $this->enforceSuperAdmin()) return $guard;

        // Fetch all active roles
        $allRoles = $this->roleModel->where('status', '1')->orderBy('role_id', 'ASC')->findAll();
        $operationalRoles = $this->getOperationalRoles();

        $roleMap = [];
        foreach ($allRoles as $r) {
            $roleMap[$r['role_key']] = $r;
        }

        // Cache role module states and sidebar menu states to prevent redundant DB calls
        $roleModuleStates = [];
        $roleSidebarStates = [];
        foreach ($allRoles as $r) {
            $rId = (int)$r['role_id'];
            $roleModuleStates[$r['role_key']] = $this->getRoleModuleStates($rId);
            $roleSidebarStates[$r['role_key']] = $this->getRoleSidebarMenus($rId);
        }

        // Fetch all internal staff users
        $rawUsers = $this->userModel->orderBy('user_id', 'ASC')->findAll();
        $usersList = [];
        $totalEnabledAcrossUsers = 0;
        $usersWithAccessCount = 0;

        foreach ($rawUsers as $u) {
            $rKey = $u['access_level'];
            $role = $roleMap[$rKey] ?? null;
            $rId = $role ? (int)$role['role_id'] : 0;
            $rName = $role['role_name'] ?? ucwords(str_replace('_', ' ', $rKey));

            $modules = $roleModuleStates[$rKey] ?? [
                'waves'  => false,
                'locker' => false,
                'loss'   => false,
                'stock'  => false,
            ];

            $sidebarMenus = $roleSidebarStates[$rKey] ?? [];

            // Calculate total enabled modules count
            $enabledCount = 0;
            foreach ($modules as $mVal) {
                if ($mVal) $enabledCount++;
            }

            $totalEnabledAcrossUsers += $enabledCount;
            if ((string)$u['status'] === '1' && $enabledCount > 0) {
                $usersWithAccessCount++;
            }

            $usersList[] = [
                'user_id'       => (int)$u['user_id'],
                'username'      => $u['username'],
                'access_level'  => $rKey,
                'role_id'       => $rId,
                'role_name'     => $rName,
                'location'      => strtolower($u['location'] ?? 'ho'),
                'status'        => (string)$u['status'],
                'modules'       => $modules,
                'total_enabled' => $enabledCount,
                'sidebar_menus' => $sidebarMenus,
            ];
        }

        // Dynamic Dashboard Summary Cards
        $summary = [
            'total_staff_users'       => count($usersList),
            'total_active_roles'      => count($operationalRoles),
            'total_enabled_modules'   => $totalEnabledAcrossUsers,
            'total_users_with_access' => $usersWithAccessCount,
        ];

        return view('permission/index', [
            'title'           => 'User-wise Role & Permission Management',
            'summary'         => $summary,
            'users'           => $usersList,
            'roles'           => $operationalRoles,
            'allRoles'        => $allRoles,
            'sidebarMenuList' => $this->sidebarMenus,
            'managedModules'  => $this->managedModules,
        ]);
    }

    /**
     * AJAX Endpoint: Fetch module states and sidebar breakdown for selected role.
     */
    public function getRoleModules(int $roleId): ResponseInterface
    {
        if ($guard = $this->enforceSuperAdmin()) return $guard;

        $role = $this->roleModel->find($roleId);
        if (!$role) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => false,
                'error'  => 'Role not found.',
            ]);
        }

        $states = $this->getRoleModuleStates($roleId);
        $sidebarStates = $this->getRoleSidebarMenus($roleId);
        $usersCount = $this->userModel->where('access_level', $role['role_key'])->countAllResults();

        return $this->response->setJSON([
            'status'        => true,
            'role'          => [
                'role_id'     => (int)$role['role_id'],
                'role_key'    => $role['role_key'],
                'role_name'   => $role['role_name'],
                'is_system'   => (int)$role['is_system'],
                'users_count' => $usersCount,
            ],
            'modules'       => $states,
            'sidebar_menus' => $sidebarStates,
        ]);
    }

    /**
     * POST Action: Save module ON/OFF permissions for selected role.
     * Executes inside atomic DB transaction, logs audit trail, bumps version, and invalidates cache.
     */
    public function saveModulePermissions(int $roleId): ResponseInterface
    {
        if ($guard = $this->enforceSuperAdmin()) return $guard;

        $targetRole = $this->roleModel->find($roleId);
        if (!$targetRole) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => false,
                'error'  => 'Role not found.',
            ]);
        }

        if ($targetRole['role_key'] === 'superadmin') {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => false,
                'error'  => 'Security Violation: Super Admin permissions are immutable and cannot be modified.',
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $currentUserId = (int)session()->get('spider.user_id');

        $postedModules = (array)($this->request->getPost('modules') ?? $_POST['modules'] ?? []);
        $newStates = [
            'waves'  => !empty($postedModules['waves']),
            'locker' => !empty($postedModules['locker']),
            'loss'   => !empty($postedModules['loss']),
            'stock'  => !empty($postedModules['stock']),
        ];

        // Fetch all menu and permission mappings
        $allMenus = $this->menuModel->findAll();
        $menuByCode = [];
        foreach ($allMenus as $m) {
            $menuByCode[$m['menu_code']] = (int)$m['menu_id'];
        }

        $allPerms = $this->permissionModel->findAll();
        $permByCode = [];
        foreach ($allPerms as $p) {
            $permByCode[$p['permission_code']] = (int)$p['permission_id'];
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $priorStates = $this->getRoleModuleStates($roleId);

            foreach ($this->managedModules as $modKey => $modConfig) {
                $isTurnedOn = $newStates[$modKey] ?? false;

                if ($isTurnedOn) {
                    // GRANT / ENABLE: Insert or update role_permissions using permission_key
                    foreach ($modConfig['permissions'] as $pCode) {
                        $pId = $permByCode[$pCode] ?? null;
                        $exists = $this->rolePermModel
                            ->where('role_id', $roleId)
                            ->where('permission_key', $pCode)
                            ->first();

                        if ($exists) {
                            $this->rolePermModel->update($exists['id'], [
                                'permission_key' => $pCode,
                                'permission_id'  => $pId,
                                'is_granted'     => 1,
                                'updated_by'     => $currentUserId,
                                'updated_at'     => $now,
                            ]);
                        } else {
                            $this->rolePermModel->insert([
                                'role_id'        => $roleId,
                                'permission_key' => $pCode,
                                'permission_id'  => $pId,
                                'is_granted'     => 1,
                                'created_by'     => $currentUserId,
                                'updated_by'     => $currentUserId,
                                'created_at'     => $now,
                                'updated_at'     => $now,
                            ]);
                        }
                    }
                } else {
                    // REVOKE / DISABLE: Delete module permissions from role_permissions
                    $this->rolePermModel
                        ->where('role_id', $roleId)
                        ->whereIn('permission_key', $modConfig['permissions'])
                        ->delete();
                }
            }

            // Audit log
            $this->auditModel->logEvent(
                actorUserId: $currentUserId,
                targetType: 'role',
                targetId: $roleId,
                action: 'update_role_module_permissions',
                details: [
                    'role_key'     => $targetRole['role_key'],
                    'prior_states' => $priorStates,
                    'new_states'   => $newStates,
                ]
            );

            // Version bump
            $this->roleModel->bumpVersion($roleId);

            $db->transCommit();

        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[RBAC] Failed to save role module permissions: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => false,
                'error'  => 'Database transaction failed: ' . $e->getMessage(),
            ]);
        }

        // Post-commit cache invalidation
        $this->permissionService->invalidateRoleCache($targetRole['role_key'], $roleId);

        return $this->response->setJSON([
            'status'  => true,
            'message' => "Permissions for role '{$targetRole['role_name']}' updated successfully.",
            'modules' => $newStates,
        ]);
    }

    /**
     * Screen 2: Hierarchical Master-Detail Permission Assignment.
     * Supports both Role and User target selection with group-based checkboxes.
     */
    public function assign(int $roleId = 0)
    {
        if ($guard = $this->enforceSuperAdmin()) return $guard;

        $roles = $this->roleModel->getActiveRoles();
        $staffUsers = $this->userModel->orderBy('username', 'ASC')->findAll();

        $userId = (int)($this->request->getGet('user_id') ?: ($_GET['user_id'] ?? 0));
        $targetUser = null;
        $isUserTarget = false;

        if ($userId > 0) {
            $targetUser = $this->userModel->find($userId);
            if ($targetUser) {
                $isUserTarget = true;
                $targetRole = $this->roleModel->where('role_key', $targetUser['access_level'])->first();
                if (!$targetRole) {
                    $targetRole = [
                        'role_id'   => 0,
                        'role_key'  => $targetUser['access_level'],
                        'role_name' => ucwords(str_replace('_', ' ', $targetUser['access_level'])),
                    ];
                }
            }
        }

        if (!$isUserTarget) {
            if ($roleId <= 0 && !empty($roles)) {
                $roleId = (int)$roles[0]['role_id'];
                foreach ($roles as $r) {
                    if ($r['role_key'] === 'admin') {
                        $roleId = (int)$r['role_id'];
                        break;
                    }
                }
            }

            $targetRole = $this->roleModel->find($roleId);
            if (!$targetRole) {
                return redirect()->to(base_url('permission'))->with('error', 'Selected role does not exist.');
            }
        }

        $allPerms = $this->permissionModel->orderBy('permission_id', 'ASC')->findAll();
        $isSuperAdmin = $isUserTarget 
            ? ($targetUser['access_level'] === 'superadmin')
            : ($targetRole['role_key'] === 'superadmin');

        if ($isSuperAdmin) {
            $grantedPermKeys = array_column($allPerms, 'permission_code');
        } elseif ($isUserTarget) {
            // User permissions are inherited entirely from their assigned role
            $grantedPermKeys = !empty($targetRole['role_id']) ? $this->rolePermModel->getGrantedPermissionKeys((int)$targetRole['role_id']) : [];
        } else {
            $grantedPermKeys = $this->rolePermModel->getGrantedPermissionKeys((int)$targetRole['role_id']);
        }

        $groups = $this->getHierarchicalPermissionGroups($grantedPermKeys);

        $targetName = $isUserTarget ? esc($targetUser['username']) : esc($targetRole['role_name']);
        $saveUrl    = $isUserTarget 
            ? base_url('permission/save-user/' . $targetUser['user_id'])
            : base_url('permission/save/' . $targetRole['role_id']);

        return view('permission/assign', [
            'title'            => 'Permissions for ' . strtoupper($targetName),
            'targetName'       => $targetName,
            'roles'            => $roles,
            'staffUsers'       => $staffUsers,
            'targetRole'       => $targetRole,
            'targetUser'       => $targetUser,
            'isUserTarget'     => $isUserTarget,
            'isSuperAdminRole' => $isSuperAdmin,
            'groups'           => $groups,
            'saveUrl'          => $saveUrl,
            'totalPerms'       => count($allPerms),
            'totalGranted'     => count($grantedPermKeys),
        ]);
    }

    /**
     * Build the hierarchical permission groups for the Master-Detail 2-column UI.
     */
    private function getHierarchicalPermissionGroups(array $grantedPerms): array
    {
        $allPerms = $this->permissionModel->orderBy('permission_id', 'ASC')->findAll();
        $permMap  = [];
        foreach ($allPerms as $p) {
            $p['permission_id'] = (int)$p['permission_id'];
            $p['is_granted']    = in_array($p['permission_code'], $grantedPerms, true)
                               || in_array($p['permission_id'], $grantedPerms, true);
            $permMap[$p['permission_code']] = $p;
        }

        $definitions = [
            'dashboard' => [
                'key'       => 'dashboard',
                'title'     => 'Dashboard',
                'icon'      => 'icon-display4',
                'subgroups' => [
                    'overview' => [
                        'key'   => 'overview',
                        'title' => 'Dashboard Access',
                        'codes' => ['dashboard.view'],
                    ],
                ],
            ],
            'waves' => [
                'key'       => 'waves',
                'title'     => 'Waves',
                'icon'      => 'icon-loop3',
                'subgroups' => [
                    'raw_waves' => [
                        'key'   => 'raw_waves',
                        'title' => 'Raw Waves Queue & Operations',
                        'codes' => [
                            'raw_waves.view',
                            'raw_waves.create',
                            'raw_waves.edit',
                            'raw_waves.verify',
                            'raw_waves.print',
                        ],
                    ],
                    'waves_ledger' => [
                        'key'   => 'waves_ledger',
                        'title' => 'Waves Financial Ledger',
                        'codes' => [
                            'waves.view',
                            'waves.create',
                            'waves.edit',
                            'waves.view_details',
                        ],
                    ],
                ],
            ],
            'process_waves' => [
                'key'       => 'process_waves',
                'title'     => 'Process Waves',
                'icon'      => 'icon-sync',
                'subgroups' => [
                    'process_core' => [
                        'key'   => 'process_core',
                        'title' => 'Process Core Operations',
                        'codes' => [
                            'process_waves.view',
                            'process_waves.transfer',
                            'process_waves.checkpoint',
                            'process_waves.print',
                        ],
                    ],
                    'process_departments' => [
                        'key'   => 'process_departments',
                        'title' => 'Manufacturing Departments',
                        'codes' => [
                            'process_waves.dept_2',
                            'process_waves.dept_4',
                            'process_waves.dept_3',
                            'process_waves.dept_8',
                            'process_waves.dept_18',
                            'process_waves.dept_6',
                            'process_waves.dept_13',
                            'process_waves.dept_15',
                            'process_waves.dept_14',
                            'process_waves.dept_17',
                            'process_waves.dept_19',
                            'process_waves.dept_20',
                            'process_waves.dept_21',
                            'process_waves.dept_22',
                            'process_waves.dept_23',
                            'process_waves.dept_7',
                            'process_waves.dept_5',
                            'process_waves.dept_16',
                        ],
                    ],
                ],
            ],
            'locker' => [
                'key'       => 'locker',
                'title'     => 'Locker Vault',
                'icon'      => 'icon-cube3',
                'subgroups' => [
                    'locker_vault' => [
                        'key'   => 'locker_vault',
                        'title' => 'Locker Safe & Inventory Ledger',
                        'codes' => [
                            'locker.view',
                            'locker.check_stock',
                            'locker.transfer',
                        ],
                    ],
                    'locker_verification' => [
                        'key'   => 'locker_verification',
                        'title' => 'Locker Stock Verification',
                        'codes' => [
                            'locker_verification.view',
                            'locker_verification.manage',
                        ],
                    ],
                ],
            ],
            'loss' => [
                'key'       => 'loss',
                'title'     => 'Loss Tracking',
                'icon'      => 'icon-stats-bars2',
                'subgroups' => [
                    'loss_core' => [
                        'key'   => 'loss_core',
                        'title' => 'Loss Operations & Hub',
                        'codes' => [
                            'loss.view',
                            'loss.consolidate',
                            'loss.print',
                        ],
                    ],
                    'loss_departments' => [
                        'key'   => 'loss_departments',
                        'title' => 'Department Loss Registers',
                        'codes' => [
                            'loss.dept_2',
                            'loss.dept_4',
                            'loss.dept_3',
                            'loss.dept_8',
                            'loss.dept_6',
                            'loss.dept_7',
                            'loss.dept_5',
                            'loss.dept_13',
                            'loss.dept_15',
                            'loss.dept_14',
                            'loss.dept_20',
                            'loss.dept_21',
                            'loss.dept_22',
                            'loss.dept_23',
                            'loss.dept_16',
                        ],
                    ],
                ],
            ],
            'refining' => [
                'key'       => 'refining',
                'title'     => 'Refining',
                'icon'      => 'icon-fire',
                'subgroups' => [
                    'refining_ops' => [
                        'key'   => 'refining_ops',
                        'title' => 'Refining Department Operations',
                        'codes' => [
                            'refining.view',
                            'refining.transfer',
                            'refining.print',
                        ],
                    ],
                ],
            ],
            'stock' => [
                'key'       => 'stock',
                'title'     => 'Stock',
                'icon'      => 'icon-stack',
                'subgroups' => [
                    'stock_ops' => [
                        'key'   => 'stock_ops',
                        'title' => 'Factory Stock Matrix',
                        'codes' => [
                            'stock.view',
                        ],
                    ],
                ],
            ],
            'master_data' => [
                'key'       => 'master_data',
                'title'     => 'Master Data',
                'icon'      => 'icon-database',
                'subgroups' => [
                    'customer' => [
                        'key'   => 'customer',
                        'title' => 'Customer Master',
                        'codes' => [
                            'customer.view',
                            'customer.create',
                            'customer.edit',
                            'customer.toggle',
                            'customer.wastage',
                        ],
                    ],
                    'customer_mapping' => [
                        'key'   => 'customer_mapping',
                        'title' => 'Customer Mapping Master',
                        'codes' => [
                            'customer_mapping.view',
                            'customer_mapping.create',
                            'customer_mapping.edit',
                            'customer_mapping.import',
                            'customer_mapping.toggle',
                        ],
                    ],
                    'collection' => [
                        'key'   => 'collection',
                        'title' => 'Collection Master',
                        'codes' => [
                            'collection.view',
                            'collection.create',
                            'collection.edit',
                            'collection.toggle',
                        ],
                    ],
                    'loss_percentage' => [
                        'key'   => 'loss_percentage',
                        'title' => 'Loss Percentage Master',
                        'codes' => [
                            'loss_percentage.view',
                            'loss_percentage.edit',
                        ],
                    ],
                    'petty_cash' => [
                        'key'   => 'petty_cash',
                        'title' => 'Petty Cash Master',
                        'codes' => [
                            'petty_cash.view',
                            'petty_cash.create',
                            'petty_cash.edit',
                            'petty_cash.toggle',
                        ],
                    ],
                    'remark' => [
                        'key'   => 'remark',
                        'title' => 'Remark Master',
                        'codes' => [
                            'remark.view',
                            'remark.create',
                            'remark.edit',
                            'remark.toggle',
                        ],
                    ],
                    'recent_files' => [
                        'key'   => 'recent_files',
                        'title' => 'Recent Files Repository',
                        'codes' => [
                            'recent_files.view',
                            'recent_files.delete',
                        ],
                    ],
                    'user_master' => [
                        'key'   => 'user_master',
                        'title' => 'User Master',
                        'codes' => [
                            'user_master.view',
                            'user_master.create',
                            'user_master.edit',
                            'user_master.view_details',
                            'user_master.toggle',
                        ],
                    ],
                    'account_master' => [
                        'key'   => 'account_master',
                        'title' => 'Account Master',
                        'codes' => [
                            'account_master.view',
                            'account_master.create',
                            'account_master.edit',
                            'account_master.mapping',
                            'account_master.toggle',
                        ],
                    ],
                ],
            ],
            'reports' => [
                'key'       => 'reports',
                'title'     => 'Reports',
                'icon'      => 'icon-file-text',
                'subgroups' => [
                    'production_reports' => [
                        'key'   => 'production_reports',
                        'title' => 'Client & Production Reports',
                        'codes' => [
                            'reports.cust_wise',
                            'reports.customer_ledger',
                            'reports.daily_report',
                            'daily_report.approve',
                        ],
                    ],
                ],
            ],
            'customer_portal' => [
                'key'       => 'customer_portal',
                'title'     => 'Customer Portal',
                'icon'      => 'icon-user-check',
                'subgroups' => [
                    'portal_access' => [
                        'key'   => 'portal_access',
                        'title' => 'Customer Client Statements',
                        'codes' => [
                            'customer_portal.view',
                            'customer_portal.approve',
                        ],
                    ],
                ],
            ],
            'system_security' => [
                'key'       => 'system_security',
                'title'     => 'System & Security',
                'icon'      => 'icon-shield-check',
                'subgroups' => [
                    'rbac_admin' => [
                        'key'   => 'rbac_admin',
                        'title' => 'Role & Permission Management',
                        'codes' => [
                            'permission.superadmin',
                        ],
                    ],
                ],
            ],
        ];

        $placedCodes = [];
        $resultGroups = [];

        foreach ($definitions as $gKey => $gDef) {
            $groupGranted = 0;
            $groupTotal   = 0;
            $subgroups    = [];

            foreach ($gDef['subgroups'] as $sgKey => $sgDef) {
                $perms = [];
                $sgGranted = 0;

                foreach ($sgDef['codes'] as $code) {
                    if (isset($permMap[$code])) {
                        $p = $permMap[$code];
                        $perms[] = $p;
                        $placedCodes[$code] = true;
                        if (!empty($p['is_granted'])) {
                            $sgGranted++;
                        }
                    }
                }

                $sgTotal = count($perms);
                $groupGranted += $sgGranted;
                $groupTotal   += $sgTotal;

                $subgroups[$sgKey] = [
                    'key'           => $sgKey,
                    'title'         => $sgDef['title'],
                    'permissions'   => $perms,
                    'granted_count' => $sgGranted,
                    'total_count'   => $sgTotal,
                ];
            }

            $resultGroups[$gKey] = [
                'key'           => $gKey,
                'title'         => $gDef['title'],
                'icon'          => $gDef['icon'],
                'subgroups'     => $subgroups,
                'granted_count' => $groupGranted,
                'total_count'   => $groupTotal,
            ];
        }

        // Collect any unmapped permissions dynamically
        $unmappedPerms = [];
        $unmappedGranted = 0;
        foreach ($permMap as $code => $p) {
            if (!isset($placedCodes[$code])) {
                $unmappedPerms[] = $p;
                if (!empty($p['is_granted'])) {
                    $unmappedGranted++;
                }
            }
        }

        if (!empty($unmappedPerms)) {
            $resultGroups['other_modules'] = [
                'key'           => 'other_modules',
                'title'         => 'Additional Permissions',
                'icon'          => 'icon-grid6',
                'subgroups'     => [
                    'other_sub' => [
                        'key'           => 'other_sub',
                        'title'         => 'General Operational Permissions',
                        'permissions'   => $unmappedPerms,
                        'granted_count' => $unmappedGranted,
                        'total_count'   => count($unmappedPerms),
                    ],
                ],
                'granted_count' => $unmappedGranted,
                'total_count'   => count($unmappedPerms),
            ];
        }

        return $resultGroups;
    }

    /**
     * POST Action: Save Role Permissions and Menu Visibility.
     * Executes inside atomic DB transaction with post-commit cache invalidation and audit logging.
     */
    public function savePermissions(int $roleId): ResponseInterface
    {
        if ($guard = $this->enforceSuperAdmin()) return $guard;

        $targetRole = $this->roleModel->find($roleId);
        if (!$targetRole) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => false,
                'error'  => 'Target role does not exist.',
            ]);
        }

        // Protection: Super Admin role is immutable
        if ($targetRole['role_key'] === 'superadmin') {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => false,
                'error'  => 'Security Violation: Super Admin permissions are immutable and cannot be restricted or modified.',
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $currentUserId = (int)session()->get('spider.user_id');

        // Incoming selected permissions (accepts either string permission_key or integer permission_id)
        $rawPerms = (array)($this->request->getPost('permissions') ?? []);
        $allPerms = $this->permissionModel->findAll();
        $permCodeById = [];
        $permIdByCode = [];
        foreach ($allPerms as $p) {
            $permCodeById[(int)$p['permission_id']] = $p['permission_code'];
            $permIdByCode[$p['permission_code']]    = (int)$p['permission_id'];
        }

        $selectedPermKeys = [];
        $selectedPermIds  = [];
        foreach ($rawPerms as $val) {
            if (is_numeric($val) && isset($permCodeById[(int)$val])) {
                $selectedPermIds[]  = (int)$val;
                $selectedPermKeys[] = $permCodeById[(int)$val];
            } elseif (is_string($val) && !empty($val)) {
                $selectedPermKeys[] = $val;
                if (isset($permIdByCode[$val])) {
                    $selectedPermIds[] = $permIdByCode[$val];
                }
            }
        }
        $selectedPermKeys = array_values(array_unique($selectedPermKeys));
        $selectedPermIds  = array_values(array_unique($selectedPermIds));

        // -----------------------------------------------------------------
        // PHASE 1: ATOMIC DATABASE TRANSACTION
        // -----------------------------------------------------------------
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // Snapshot prior state for audit log
            $priorPerms = $this->rolePermModel->getGrantedPermissionKeys($roleId);

            // Sync role_permissions: Clear existing for this role
            $this->rolePermModel->where('role_id', $roleId)->delete();

            if (!empty($selectedPermKeys)) {
                $permInserts = [];
                foreach ($selectedPermKeys as $key) {
                    $pId = $permIdByCode[$key] ?? null;
                    $permInserts[] = [
                        'role_id'        => $roleId,
                        'permission_key' => $key,
                        'permission_id'  => $pId,
                        'is_granted'     => 1,
                        'created_by'     => $currentUserId,
                        'updated_by'     => $currentUserId,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }
                $this->rolePermModel->insertBatch($permInserts);
            }

            // Record Audit Log
            $this->auditModel->logEvent(
                actorUserId: $currentUserId,
                targetType: 'role',
                targetId: $roleId,
                action: 'update_role_permissions',
                details: [
                    'role_key'       => $targetRole['role_key'],
                    'perms_before'   => $priorPerms,
                    'perms_after'    => $selectedPermKeys,
                    'perms_count'    => count($selectedPermKeys),
                ]
            );

            // 4. Update version & timestamp on roles table (InnoDB row lock & atomic fallback timestamp)
            $this->roleModel->bumpVersion($roleId);

            // 5. COMMIT TRANSACTION FIRST
            $db->transCommit();

        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[RBAC] Failed to save role permissions: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status' => false,
                'error'  => 'Database transaction failed: ' . $e->getMessage(),
            ]);
        }

        // -----------------------------------------------------------------
        // PHASE 2: POST-COMMIT CACHE INVALIDATION WITH RELIABLE FALLBACK
        // -----------------------------------------------------------------
        $this->permissionService->invalidateRoleCache($targetRole['role_key'], $roleId);

        return $this->response->setJSON([
            'status'  => true,
            'message' => "Permissions for role '{$targetRole['role_name']}' updated and applied successfully.",
        ]);
    }

    /**
     * POST Action: Save Individual Staff User Permissions via Hierarchical UI.
     * Synchronizes overrides against base role inheritance with atomic transaction and cache invalidation.
     */
    public function saveUserPermissions(int $userId): ResponseInterface
    {
        if ($guard = $this->enforceSuperAdmin()) return $guard;

        $targetUser = $this->userModel->find($userId);
        if (!$targetUser) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => false,
                'error'  => 'Target staff user does not exist.',
            ]);
        }

        if ($targetUser['access_level'] === 'superadmin') {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => false,
                'error'  => 'Security Violation: Super Admin permissions are immutable and cannot be restricted or modified.',
            ]);
        }

        $targetRole = $this->roleModel->where('role_key', $targetUser['access_level'])->first();
        if (!$targetRole) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => false,
                'error'  => 'Assigned role does not exist.',
            ]);
        }

        // Centralized Two-Table update: saves permissions to role_permissions for user's assigned role
        return $this->savePermissions((int)$targetRole['role_id']);
    }

    /**
     * Screen 3: Role-wise Permission Summary.
     */
    public function summary()
    {
        if ($guard = $this->enforceSuperAdmin()) return $guard;

        $stats = $this->permissionService->getSystemStatistics();
        $roles = $this->roleModel->getActiveRoles();

        return view('permission/summary', [
            'title'       => 'Role-wise Permission Summary',
            'roles'       => $roles,
            'roleStats'   => $stats['roles'],
            'systemStats' => $stats['system'],
        ]);
    }

    /**
     * Screen 4: User-wise Permission Details & Overrides.
     */
    public function users()
    {
        if ($guard = $this->enforceSuperAdmin()) return $guard;

        $users = $this->userModel->orderBy('user_id', 'ASC')->findAll();
        $roles = $this->roleModel->getActiveRoles();
        $roleMap = [];
        foreach ($roles as $r) {
            $roleMap[$r['role_key']] = $r;
        }

        $allPerms = $this->permissionModel->orderBy('permission_id', 'ASC')->findAll();

        $userRows = [];
        foreach ($users as $u) {
            $uId = (int)$u['user_id'];
            $rKey = $u['access_level'];
            $role = $roleMap[$rKey] ?? null;
            $rId = $role ? (int)$role['role_id'] : 0;

            $assignedRolePerms = $role ? $this->rolePermModel->getGrantedPermissionKeys($rId) : [];

            $userRows[] = [
                'user_id'          => $uId,
                'username'         => $u['username'],
                'access_level'     => $u['access_level'],
                'role_name'        => $role['role_name'] ?? $u['access_level'],
                'location'         => $u['location'],
                'status'           => $u['status'],
                'role_perms_count' => count($assignedRolePerms),
                'extra_grants'     => 0,
                'restrictions'     => 0,
                'effective_count'  => count($assignedRolePerms),
                'overrides'        => [],
            ];
        }

        return view('permission/users', [
            'title'       => 'User-wise Permission Details & Overrides',
            'users'       => $userRows,
            'roles'       => $roles,
            'allPerms'    => $allPerms,
        ]);
    }

    /**
     * POST Action: Save Individual User Override (Deprecated in Two-Table architecture).
     */
    public function saveUserOverride(): ResponseInterface
    {
        if ($guard = $this->enforceSuperAdmin()) return $guard;

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'User permissions are managed centrally per role. Overrides are deprecated in the simplified architecture.',
        ]);
    }

    /**
     * POST Action: Delete / Remove a User Override (Deprecated in Two-Table architecture).
     */
    public function removeUserOverride(int $overrideId): ResponseInterface
    {
        if ($guard = $this->enforceSuperAdmin()) return $guard;

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Override removed. User inherits standard role permissions.',
        ]);
    }

    /**
     * Screen 5: Permission Audit Logs.
     */
    public function auditLogs()
    {
        if ($guard = $this->enforceSuperAdmin()) return $guard;

        $logs = $this->auditModel->orderBy('id', 'DESC')->findAll(100);

        return view('permission/audit_logs', [
            'title' => 'Permission Audit Trail',
            'logs'  => $logs,
        ]);
    }
}

