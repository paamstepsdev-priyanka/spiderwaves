<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?><?= esc($title) ?> - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Permissions / User-wise Management<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>User-wise Role &amp; Permission Management</h1>
        <p>Audit staff user access privileges, assigned roles, and inherited module permissions</p>
    </div>
</div>

<!-- 1. Dashboard Summary Cards -->
<div class="sw-stats-grid" style="margin-bottom: 24px;">
    <!-- Total Staff Users -->
    <div class="sw-stat-card">
        <div class="sw-stat-info">
            <span class="sw-stat-label">Total Staff Users</span>
            <span class="sw-stat-value" id="summaryTotalUsers"><?= (int)$summary['total_staff_users'] ?></span>
        </div>
        <div class="sw-stat-icon-wrapper blue">
            <i class="icon-users4"></i>
        </div>
    </div>

    <!-- Total Active Roles -->
    <div class="sw-stat-card">
        <div class="sw-stat-info">
            <span class="sw-stat-label">Total Active Roles</span>
            <span class="sw-stat-value" id="summaryTotalRoles"><?= (int)$summary['total_active_roles'] ?></span>
        </div>
        <div class="sw-stat-icon-wrapper emerald">
            <i class="icon-shield-check"></i>
        </div>
    </div>

    <!-- Total Enabled Module Permissions -->
    <div class="sw-stat-card">
        <div class="sw-stat-info">
            <span class="sw-stat-label">Total Enabled Module Permissions</span>
            <span class="sw-stat-value" id="summaryTotalEnabled"><?= (int)$summary['total_enabled_modules'] ?></span>
        </div>
        <div class="sw-stat-icon-wrapper amber">
            <i class="icon-key"></i>
        </div>
    </div>

    <!-- Total Users with Access -->
    <div class="sw-stat-card">
        <div class="sw-stat-info">
            <span class="sw-stat-label">Total Users with Access</span>
            <span class="sw-stat-value" id="summaryUsersWithAccess"><?= (int)$summary['total_users_with_access'] ?></span>
        </div>
        <div class="sw-stat-icon-wrapper slate">
            <i class="icon-user-check"></i>
        </div>
    </div>
</div>

<!-- 2. Search and Multi-Filter Card -->
<div class="panel panel-flat" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); box-shadow: var(--sw-shadow-sm); margin-bottom: 24px; background: #fff;">
    <div class="panel-body" style="padding: 18px 20px;">
        <div class="row">
            <!-- Search by Username or User ID -->
            <div class="col-sm-6 col-md-5">
                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="text-semibold text-uppercase text-size-mini text-muted" style="letter-spacing: 0.5px; margin-bottom: 6px; display: block;">
                        Search User
                    </label>
                    <div class="has-feedback has-feedback-left">
                        <input type="text" id="filterSearch" class="form-control" placeholder="Username or User ID..." style="font-size: 13px;">
                        <div class="form-control-feedback"><i class="icon-search4 text-muted"></i></div>
                    </div>
                </div>
            </div>

            <!-- Filter by Role -->
            <div class="col-sm-6 col-md-3">
                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="text-semibold text-uppercase text-size-mini text-muted" style="letter-spacing: 0.5px; margin-bottom: 6px; display: block;">
                        Role
                    </label>
                    <select id="filterRole" class="form-control" style="font-size: 13px;">
                        <option value="">All Roles</option>
                        <?php foreach ($allRoles as $r): ?>
                            <option value="<?= esc($r['role_key']) ?>"><?= esc($r['role_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Filter by Account Status -->
            <div class="col-sm-6 col-md-3">
                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="text-semibold text-uppercase text-size-mini text-muted" style="letter-spacing: 0.5px; margin-bottom: 6px; display: block;">
                        Account Status
                    </label>
                    <select id="filterStatus" class="form-control" style="font-size: 13px;">
                        <option value="">All Status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Reset Button -->
            <div class="col-sm-6 col-md-1">
                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="text-semibold text-uppercase text-size-mini text-muted" style="letter-spacing: 0.5px; margin-bottom: 6px; display: block;">
                        Reset
                    </label>
                    <button type="button" id="btnResetFilters" class="btn btn-default btn-block" title="Reset all filters" style="height: 38px;">
                        <i class="icon-reset"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3. User-wise Permission Table -->
<div class="sw-table-card">
    <div class="sw-table-header">
        <div>
            <div class="sw-table-title">Staff User Access Matrix</div>
            <div class="sw-table-info">Accurate permissions inherited directly from assigned operational roles in the authoritative database</div>
        </div>
        <div>
            <span id="filterCounterPill" class="sw-pill-badge active" style="font-weight: 600;">
                <span class="sw-badge-dot"></span>
                <span id="filterCounterText">Showing <?= count($users) ?> of <?= count($users) ?> Users</span>
            </span>
        </div>
    </div>

    <div class="sw-table-responsive">
        <table class="sw-table" id="usersPermissionTable">
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">#</th>
                    <th style="width: 22%;">Name</th>
                    <th style="width: 20%;">User Name</th>
                    <th style="width: 24%;">Role</th>
                    <th class="text-center" style="width: 17%;">Status</th>
                    <th class="text-right" style="width: 17%;">Action</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <?php 
                    $sno = 1;
                    foreach ($users as $u): 
                        $isSuper = ($u['access_level'] === 'superadmin');
                        $modWaves = !empty($u['modules']['waves']);
                        $modLocker = !empty($u['modules']['locker']);
                        $modLoss = !empty($u['modules']['loss']);
                        $modStock = !empty($u['modules']['stock']);
                        $pct = ($u['total_enabled'] / 4) * 100;
                        $displayName = ucwords(strtolower(str_replace('_', ' ', $u['username'])));
                        if ($isSuper) {
                            $displayName = 'Super Admin';
                        }
                ?>
                <tr class="user-row" 
                    id="user-row-<?= (int)$u['user_id'] ?>"
                    data-user-id="<?= (int)$u['user_id'] ?>"
                    data-name="<?= strtolower(esc($displayName)) ?>"
                    data-username="<?= strtolower(esc($u['username'])) ?>"
                    data-role="<?= esc($u['access_level']) ?>"
                    data-location="<?= esc($u['location']) ?>"
                    data-status="<?= esc($u['status']) ?>"
                    data-waves="<?= $modWaves ? '1' : '0' ?>"
                    data-locker="<?= $modLocker ? '1' : '0' ?>"
                    data-loss="<?= $modLoss ? '1' : '0' ?>"
                    data-stock="<?= $modStock ? '1' : '0' ?>"
                    data-total="<?= (int)$u['total_enabled'] ?>">
                    
                    <!-- 1. # (Serial Number) -->
                    <td class="text-center" style="color: var(--sw-text-muted); font-weight: 500; font-size: 13px;">
                        <span class="user-sno"><?= $sno++ ?></span>
                    </td>

                    <!-- 2. Name -->
                    <td>
                        <div class="sw-cust-name" style="font-weight: 700; color: var(--sw-text-main); font-size: 13.5px;">
                            <?= esc($displayName) ?>
                        </div>
                    </td>

                    <!-- 3. User Name -->
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-weight: 600; color: #334155; font-size: 13px;">
                                <?= esc($u['username']) ?>
                            </span>
                            <?php if ($isSuper): ?>
                                <span class="sw-pill-badge active" style="font-size: 10px; padding: 2px 6px; font-weight: 700;">
                                    Super Admin
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>

                    <!-- 4. Role -->
                    <td>
                        <span class="sw-pill-badge customer" style="font-weight: 600;">
                            <?= esc($u['role_name']) ?>
                        </span>
                    </td>

                    <!-- 5. Account Status -->
                    <td class="text-center">
                        <span class="sw-pill-badge <?= ($u['status'] === '1') ? 'active' : 'inactive' ?>" style="font-weight: 600;">
                            <span class="sw-badge-dot"></span>
                            <?= ($u['status'] === '1') ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>

                    <!-- 6. Action: Assign Permissions -->
                    <td class="text-right" style="white-space: nowrap;">
                        <a href="<?= base_url('permission/assign?user_id=' . (int)$u['user_id']) ?>" class="btn btn-default btn-xs" style="padding: 5px 14px; font-weight: 600; border-radius: 4px;" title="Hierarchical Permission Tree">
                            <i class="icon-tree6 position-left"></i> Assign
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>

                <!-- Zero Filter Matches Row -->
                <tr id="noUsersMatchRow" style="display: none;">
                    <td colspan="6" class="text-center" style="padding: 30px; color: var(--sw-text-muted);">
                        <i class="icon-search4 text-muted" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>
                        <span style="font-size: 14px; font-weight: 600;">No staff users found matching your active filter criteria.</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 4. VIEW PERMISSIONS MODAL                                                 -->
<!-- ========================================================================= -->
<div class="modal fade" id="viewUserModal" tabindex="-1" role="dialog" aria-labelledby="viewUserModalTitle">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: var(--sw-radius); border: 1px solid var(--sw-border); box-shadow: var(--sw-shadow-lg);">
            <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid var(--sw-border); padding: 16px 22px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="viewUserModalTitle" style="font-weight: 700; color: var(--sw-text-main);">
                    <i class="icon-user-check position-left text-primary"></i>
                    User Permissions Audit &bull; <span id="modalUsername" style="color: var(--sw-primary);"></span>
                </h4>
            </div>
            
            <div class="modal-body" style="padding: 22px;">
                <!-- User Summary Meta Card -->
                <div class="panel panel-body" style="background: #f8fafc; border: 1px solid var(--sw-border); border-radius: var(--sw-radius); padding: 14px 20px; margin-bottom: 20px;">
                    <div class="row">
                        <div class="col-xs-6 col-sm-3">
                            <span class="text-muted text-uppercase text-size-mini text-semibold" style="letter-spacing: 0.5px;">User ID</span>
                            <div class="text-semibold" id="modalMetaUserId" style="font-size: 15px; color: var(--sw-text-main); margin-top: 2px;"></div>
                        </div>
                        <div class="col-xs-6 col-sm-3">
                            <span class="text-muted text-uppercase text-size-mini text-semibold" style="letter-spacing: 0.5px;">Assigned Role</span>
                            <div style="margin-top: 2px;"><span class="sw-pill-badge customer" id="modalMetaRole" style="font-weight: 700;"></span></div>
                        </div>
                        <div class="col-xs-6 col-sm-3">
                            <span class="text-muted text-uppercase text-size-mini text-semibold" style="letter-spacing: 0.5px;">Location</span>
                            <div style="margin-top: 2px;"><span class="sw-pill-badge active" id="modalMetaLocation" style="font-weight: 700;"></span></div>
                        </div>
                        <div class="col-xs-6 col-sm-3">
                            <span class="text-muted text-uppercase text-size-mini text-semibold" style="letter-spacing: 0.5px;">Account Status</span>
                            <div style="margin-top: 2px;"><span class="sw-pill-badge active" id="modalMetaStatus" style="font-weight: 700;"></span></div>
                        </div>
                    </div>
                </div>

                <!-- 4 Core Modules Overview Grid -->
                <div style="margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h5 style="margin: 0; font-weight: 700; color: var(--sw-text-main);">
                            <i class="icon-grid position-left text-primary"></i>
                            Four Core Operations Modules
                        </h5>
                        <span id="modalTotalEnabledPill" class="sw-pill-badge active" style="font-weight: 700;">
                            3 out of 4 Enabled
                        </span>
                    </div>

                    <div class="row" style="margin-left: -6px; margin-right: -6px;">
                        <!-- Waves -->
                        <div class="col-sm-6 col-md-3" style="padding-left: 6px; padding-right: 6px;">
                            <div class="panel panel-body" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); padding: 14px 16px; margin-bottom: 12px; background: #fff;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="icon-loop4 text-primary" style="font-size: 16px;"></i>
                                        <span style="font-weight: 700; color: var(--sw-text-main);">Waves</span>
                                    </div>
                                    <span id="modalPermBadgeWaves" class="sw-pill-badge active" style="font-weight: 700;">ON</span>
                                </div>
                                <div class="text-muted" style="font-size: 11px; line-height: 1.4;">
                                    Raw waves queue, voucher management, wave ledger
                                </div>
                            </div>
                        </div>

                        <!-- Locker -->
                        <div class="col-sm-6 col-md-3" style="padding-left: 6px; padding-right: 6px;">
                            <div class="panel panel-body" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); padding: 14px 16px; margin-bottom: 12px; background: #fff;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="icon-safe text-success" style="font-size: 16px;"></i>
                                        <span style="font-weight: 700; color: var(--sw-text-main);">Locker</span>
                                    </div>
                                    <span id="modalPermBadgeLocker" class="sw-pill-badge active" style="font-weight: 700;">ON</span>
                                </div>
                                <div class="text-muted" style="font-size: 11px; line-height: 1.4;">
                                    Locker vault ledger, stock transfer, verification
                                </div>
                            </div>
                        </div>

                        <!-- Loss -->
                        <div class="col-sm-6 col-md-3" style="padding-left: 6px; padding-right: 6px;">
                            <div class="panel panel-body" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); padding: 14px 16px; margin-bottom: 12px; background: #fff;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="icon-pulse2 text-warning" style="font-size: 16px;"></i>
                                        <span style="font-weight: 700; color: var(--sw-text-main);">Loss</span>
                                    </div>
                                    <span id="modalPermBadgeLoss" class="sw-pill-badge active" style="font-weight: 700;">ON</span>
                                </div>
                                <div class="text-muted" style="font-size: 11px; line-height: 1.4;">
                                    Department loss records, loss hub, consolidation
                                </div>
                            </div>
                        </div>

                        <!-- Stock -->
                        <div class="col-sm-6 col-md-3" style="padding-left: 6px; padding-right: 6px;">
                            <div class="panel panel-body" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); padding: 14px 16px; margin-bottom: 12px; background: #fff;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="icon-box text-info" style="font-size: 16px;"></i>
                                        <span style="font-weight: 700; color: var(--sw-text-main);">Stock</span>
                                    </div>
                                    <span id="modalPermBadgeStock" class="sw-pill-badge active" style="font-weight: 700;">ON</span>
                                </div>
                                <div class="text-muted" style="font-size: 11px; line-height: 1.4;">
                                    Factory stock matrix, stock balances, loss report
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Complete Sidebar Menus Options Breakdown -->
                <div>
                    <h5 style="margin: 0 0 12px 0; font-weight: 700; color: var(--sw-text-main);">
                        <i class="icon-menu7 position-left text-primary"></i>
                        Sidebar Menu Navigation Options
                    </h5>
                    <div class="table-responsive" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); max-height: 260px; overflow-y: auto;">
                        <table class="table table-hover" style="margin-bottom: 0;">
                            <thead>
                                <tr style="background: #f8fafc; font-size: 11px; text-transform: uppercase;">
                                    <th style="font-weight: 700; color: var(--sw-text-muted);">Section</th>
                                    <th style="font-weight: 700; color: var(--sw-text-main);">Sidebar Menu Option</th>
                                    <th style="width: 120px; text-align: center; font-weight: 700; color: var(--sw-text-main);">Access Status</th>
                                </tr>
                            </thead>
                            <tbody id="modalSidebarMenuListBody">
                                <!-- Populated dynamically by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid var(--sw-border); padding: 14px 22px; display: flex; justify-content: space-between; align-items: center;">
                <div id="modalSuperAdminLockNotice" style="display: none; font-size: 12px; color: var(--sw-text-muted);">
                    <i class="icon-lock2 text-primary position-left"></i> Super Admin permissions are protected and immutable.
                </div>
                <div id="modalRoleActionContainer" style="display: flex; gap: 8px;">
                    <a id="btnModalHierarchicalAssign" href="#" class="btn btn-default" style="font-weight: 600; padding: 8px 16px;">
                        <i class="icon-tree6 position-left"></i>
                        <span>Assign Permissions Tree</span>
                    </a>
                    <button type="button" id="btnManageRoleFromModal" class="sw-btn-primary" style="padding: 8px 18px; font-size: 13px;">
                        <i class="icon-cog position-left"></i>
                        <span>Manage <span id="modalBtnRoleName">Admin</span> Permissions</span>
                    </button>
                </div>
                <button type="button" class="btn btn-default" data-dismiss="modal" style="font-weight: 600;">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 5. CENTRALIZED ROLE PERMISSION MANAGEMENT MODAL                           -->
<!-- ========================================================================= -->
<div class="modal fade" id="manageRoleModal" tabindex="-1" role="dialog" aria-labelledby="manageRoleModalTitle">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content" style="border-radius: var(--sw-radius); border: 1px solid var(--sw-border); box-shadow: var(--sw-shadow-lg);">
            <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid var(--sw-border); padding: 16px 22px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="manageRoleModalTitle" style="font-weight: 700; color: var(--sw-text-main);">
                    <i class="icon-shield-check position-left text-primary"></i>
                    Manage Role Module Permissions
                </h4>
            </div>

            <div class="modal-body" style="padding: 22px;">
                <!-- Role Dropdown Selector -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="text-semibold text-uppercase text-size-mini text-muted" style="letter-spacing: 0.5px; display: block; margin-bottom: 8px;">
                        Select Role to Configure
                    </label>
                    <select id="modalRoleSelector" class="form-control" style="font-size: 14px; font-weight: 600;">
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int)$r['role_id'] ?>" data-key="<?= esc($r['role_key']) ?>">
                                <?= esc($r['role_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="help-block text-muted" style="margin-top: 6px; font-size: 12px;">
                        <i class="icon-info22 position-left text-primary"></i>
                        All users assigned to this role will automatically inherit these access permissions.
                    </span>
                </div>

                <!-- 4 Core Modules ON/OFF Switch Table -->
                <div class="table-responsive" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); overflow: hidden; margin-bottom: 16px;">
                    <table class="table" style="margin-bottom: 0;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid var(--sw-border);">
                                <th style="width: 140px; font-weight: 700; color: var(--sw-text-main);">Module</th>
                                <th style="font-weight: 700; color: var(--sw-text-main);">Description</th>
                                <th style="width: 150px; text-align: center; font-weight: 700; color: var(--sw-text-main);">Permission</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Waves -->
                            <tr style="border-bottom: 1px solid var(--sw-border);">
                                <td style="vertical-align: middle;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="icon-loop4 text-primary" style="font-size: 16px;"></i>
                                        <span style="font-weight: 700; color: var(--sw-text-main);">Waves</span>
                                    </div>
                                </td>
                                <td style="vertical-align: middle; color: var(--sw-text-muted); font-size: 12px;">
                                    Raw waves queue &amp; wave transactions
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <div style="display: inline-flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" id="modal_sw_waves" data-mod="waves" class="switchery-role">
                                        <span id="modal_badge_waves" class="sw-pill-badge active" style="min-width: 44px; text-align: center; font-weight: 700; cursor: pointer;">ON</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Locker -->
                            <tr style="border-bottom: 1px solid var(--sw-border);">
                                <td style="vertical-align: middle;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="icon-safe text-success" style="font-size: 16px;"></i>
                                        <span style="font-weight: 700; color: var(--sw-text-main);">Locker</span>
                                    </div>
                                </td>
                                <td style="vertical-align: middle; color: var(--sw-text-muted); font-size: 12px;">
                                    Locker balances &amp; stock verification
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <div style="display: inline-flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" id="modal_sw_locker" data-mod="locker" class="switchery-role">
                                        <span id="modal_badge_locker" class="sw-pill-badge active" style="min-width: 44px; text-align: center; font-weight: 700; cursor: pointer;">ON</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Loss -->
                            <tr style="border-bottom: 1px solid var(--sw-border);">
                                <td style="vertical-align: middle;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="icon-pulse2 text-warning" style="font-size: 16px;"></i>
                                        <span style="font-weight: 700; color: var(--sw-text-main);">Loss</span>
                                    </div>
                                </td>
                                <td style="vertical-align: middle; color: var(--sw-text-muted); font-size: 12px;">
                                    Loss hub, departments &amp; consolidation
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <div style="display: inline-flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" id="modal_sw_loss" data-mod="loss" class="switchery-role">
                                        <span id="modal_badge_loss" class="sw-pill-badge active" style="min-width: 44px; text-align: center; font-weight: 700; cursor: pointer;">ON</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- Stock -->
                            <tr>
                                <td style="vertical-align: middle;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="icon-box text-info" style="font-size: 16px;"></i>
                                        <span style="font-weight: 700; color: var(--sw-text-main);">Stock</span>
                                    </div>
                                </td>
                                <td style="vertical-align: middle; color: var(--sw-text-muted); font-size: 12px;">
                                    Factory stock matrix &amp; loss reports
                                </td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <div style="display: inline-flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" id="modal_sw_stock" data-mod="stock" class="switchery-role">
                                        <span id="modal_badge_stock" class="sw-pill-badge active" style="min-width: 44px; text-align: center; font-weight: 700; cursor: pointer;">ON</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="modalRoleLoading" class="text-muted text-center" style="display: none; padding: 10px;">
                    <i class="icon-spinner2 spinner position-left"></i> Loading role permissions...
                </div>
            </div>

            <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid var(--sw-border); padding: 14px 22px;">
                <button type="button" class="btn btn-default" data-dismiss="modal" style="font-weight: 600;">Cancel</button>
                <button type="button" id="btnModalSavePermissions" class="sw-btn-primary" style="padding: 8px 20px;">
                    <i class="icon-checkmark4 position-left"></i>
                    <span>Save Permissions</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript" src="<?= base_url('assets/js/plugins/forms/styling/switchery.min.js') ?>"></script>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    // -------------------------------------------------------------------------
    // DATA STORES & REFERENCES
    // -------------------------------------------------------------------------
    var allUsersData = <?= json_encode($users) ?>;
    var sidebarMenusMeta = <?= json_encode($sidebarMenuList) ?>;
    var userRows = document.querySelectorAll('.user-row');
    
    // Filter Elements
    var filterSearch = document.getElementById('filterSearch');
    var filterRole = document.getElementById('filterRole');
    var filterStatus = document.getElementById('filterStatus');
    var btnReset = document.getElementById('btnResetFilters');
    var counterText = document.getElementById('filterCounterText');
    var counterPill = document.getElementById('filterCounterPill');
    var noMatchRow = document.getElementById('noUsersMatchRow');

    // -------------------------------------------------------------------------
    // 1. FILTERING ENGINE
    // -------------------------------------------------------------------------
    function applyFilters() {
        var query = (filterSearch.value || '').trim().toLowerCase();
        var selectedRole = filterRole.value;
        var selectedStatus = filterStatus.value;

        var visibleCount = 0;
        var totalCount = userRows.length;
        var visibleIndex = 1;

        userRows.forEach(function(row) {
            var userId = row.getAttribute('data-user-id') || '';
            var name = row.getAttribute('data-name') || '';
            var username = row.getAttribute('data-username') || '';
            var role = row.getAttribute('data-role') || '';
            var status = row.getAttribute('data-status') || '';
            
            // Text search match (matches name, username, or user ID)
            var matchesSearch = !query || name.indexOf(query) !== -1 || username.indexOf(query) !== -1 || userId === query || ('#' + userId).indexOf(query) !== -1;
            
            // Dropdown filters match
            var matchesRole = !selectedRole || role === selectedRole;
            var matchesStatus = !selectedStatus || status === selectedStatus;

            if (matchesSearch && matchesRole && matchesStatus) {
                row.style.display = '';
                var snoEl = row.querySelector('.user-sno');
                if (snoEl) {
                    snoEl.textContent = visibleIndex++;
                }
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update counter badge
        if (counterText) {
            counterText.textContent = 'Showing ' + visibleCount + ' of ' + totalCount + ' Users';
        }
        if (noMatchRow) {
            noMatchRow.style.display = (visibleCount === 0) ? '' : 'none';
        }
    }

    // Attach filter listeners
    filterSearch.addEventListener('input', applyFilters);
    filterRole.addEventListener('change', applyFilters);
    filterStatus.addEventListener('change', applyFilters);

    btnReset.addEventListener('click', function() {
        filterSearch.value = '';
        filterRole.value = '';
        filterStatus.value = '';
        applyFilters();
    });

    // -------------------------------------------------------------------------
    // 2. VIEW USER PERMISSIONS MODAL
    // -------------------------------------------------------------------------
    var currentUserModalRole = null;

    $('#usersTableBody').on('click', '.btn-view-user', function() {
        var rawUser = this.getAttribute('data-user');
        if (!rawUser) return;
        var u = JSON.parse(rawUser);

        currentUserModalRole = u.access_level;
        document.getElementById('modalUsername').textContent = u.username;
        document.getElementById('modalMetaUserId').textContent = '#' + u.user_id;
        document.getElementById('modalMetaRole').textContent = u.role_name;
        document.getElementById('modalMetaLocation').textContent = u.location.toUpperCase();
        document.getElementById('modalMetaStatus').textContent = (u.status === '1') ? 'Active' : 'Inactive';
        document.getElementById('modalMetaStatus').className = 'sw-pill-badge ' + (u.status === '1' ? 'active' : 'inactive');

        // Module badges
        updateModalBadge('modalPermBadgeWaves', !!u.modules.waves);
        updateModalBadge('modalPermBadgeLocker', !!u.modules.locker);
        updateModalBadge('modalPermBadgeLoss', !!u.modules.loss);
        updateModalBadge('modalPermBadgeStock', !!u.modules.stock);

        var totalPill = document.getElementById('modalTotalEnabledPill');
        totalPill.textContent = u.total_enabled + ' out of 4 Enabled';
        totalPill.className = 'sw-pill-badge ' + (u.total_enabled >= 3 ? 'active' : (u.total_enabled >= 1 ? 'customer' : 'inactive'));

        // Render full sidebar menus breakdown
        var tbody = document.getElementById('modalSidebarMenuListBody');
        tbody.innerHTML = '';

        sidebarMenusMeta.forEach(function(m) {
            var hasAccess = !!(u.sidebar_menus && u.sidebar_menus[m.code]);
            if (u.access_level === 'superadmin') hasAccess = true;

            var tr = document.createElement('tr');
            tr.innerHTML = 
                '<td style="color: var(--sw-text-muted); font-size: 11px; font-weight: 600;">' + escapeHtml(m.section) + '</td>' +
                '<td>' +
                    '<div style="display: flex; align-items: center; gap: 8px;">' +
                        '<i class="' + escapeHtml(m.icon) + ' text-primary" style="font-size: 14px;"></i>' +
                        '<span style="font-weight: 600; color: var(--sw-text-main); font-size: 13px;">' + escapeHtml(m.title) + '</span>' +
                    '</div>' +
                '</td>' +
                '<td style="text-align: center;">' +
                    (hasAccess 
                        ? '<span class="sw-pill-badge active" style="font-weight: 700; min-width: 44px;"><i class="icon-checkmark4 position-left" style="font-size: 9px;"></i>ON</span>'
                        : '<span class="sw-pill-badge inactive" style="font-weight: 700; min-width: 44px;"><i class="icon-cross2 position-left" style="font-size: 9px;"></i>OFF</span>'
                    ) +
                '</td>';
            tbody.appendChild(tr);
        });

        // Superadmin vs standard role action buttons
        var isSuper = (u.access_level === 'superadmin');
        document.getElementById('modalSuperAdminLockNotice').style.display = isSuper ? 'inline-block' : 'none';
        document.getElementById('modalRoleActionContainer').style.display = isSuper ? 'none' : 'inline-block';
        document.getElementById('modalBtnRoleName').textContent = u.role_name;

        var btnManageRole = document.getElementById('btnManageRoleFromModal');
        btnManageRole.setAttribute('data-role-id', u.role_id);
        btnManageRole.setAttribute('data-role-key', u.access_level);

        var btnHierarchical = document.getElementById('btnModalHierarchicalAssign');
        if (btnHierarchical) {
            btnHierarchical.href = '<?= base_url('permission/assign') ?>?user_id=' + u.user_id;
        }

        $('#viewUserModal').modal('show');
    });

    function updateModalBadge(elemId, isEnabled) {
        var el = document.getElementById(elemId);
        if (el) {
            el.textContent = isEnabled ? 'ON' : 'OFF';
            el.className = 'sw-pill-badge ' + (isEnabled ? 'active' : 'inactive');
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // -------------------------------------------------------------------------
    // 3. ROLE PERMISSION MANAGEMENT MODAL & SWITCHERY
    // -------------------------------------------------------------------------
    var modalRoleSelector = $('#modalRoleSelector');
    var btnModalSave = document.getElementById('btnModalSavePermissions');
    var roleLoadingIndicator = document.getElementById('modalRoleLoading');
    var switcheryInstances = {};

    // Initialize Switchery toggles for the 4 modules inside the modal
    document.querySelectorAll('.switchery-role').forEach(function(el) {
        var modKey = el.getAttribute('data-mod');
        var sw = new Switchery(el, {
            color: '#0284c7',
            secondaryColor: '#cbd5e1',
            jackColor: '#ffffff',
            size: 'default'
        });
        switcheryInstances[modKey] = sw;

        el.addEventListener('change', function() {
            updateRoleBadge(modKey, this.checked);
        });

        // Click on badge also toggles switch
        var badge = document.getElementById('modal_badge_' + modKey);
        if (badge) {
            badge.addEventListener('click', function() {
                el.checked = !el.checked;
                sw.setPosition(false);
                sw.handleOnchange(true);
                updateRoleBadge(modKey, el.checked);
            });
        }
    });

    function updateRoleBadge(modKey, isChecked) {
        var badge = document.getElementById('modal_badge_' + modKey);
        if (badge) {
            badge.textContent = isChecked ? 'ON' : 'OFF';
            badge.className = 'sw-pill-badge ' + (isChecked ? 'active' : 'inactive');
        }
    }

    function setRoleSwitchState(modKey, isChecked) {
        var el = document.getElementById('modal_sw_' + modKey);
        var sw = switcheryInstances[modKey];
        if (el && sw) {
            if (el.checked !== isChecked) {
                el.checked = isChecked;
                sw.setPosition(false);
                sw.handleOnchange(true);
            }
            updateRoleBadge(modKey, isChecked);
        }
    }

    function loadRoleModulesData(roleId) {
        if (!roleId) return;
        btnModalSave.disabled = true;
        if (roleLoadingIndicator) roleLoadingIndicator.style.display = 'block';

        fetch('<?= base_url('permission/role-modules') ?>/' + roleId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            btnModalSave.disabled = false;
            if (roleLoadingIndicator) roleLoadingIndicator.style.display = 'none';

            if (data && data.status && data.modules) {
                for (var modKey in data.modules) {
                    if (data.modules.hasOwnProperty(modKey)) {
                        setRoleSwitchState(modKey, !!data.modules[modKey]);
                    }
                }
            }
        })
        .catch(function(err) {
            btnModalSave.disabled = false;
            if (roleLoadingIndicator) roleLoadingIndicator.style.display = 'none';
            console.error('Failed to load role permissions:', err);
        });
    }

    // Role selector change in modal
    modalRoleSelector.on('change', function() {
        loadRoleModulesData($(this).val());
    });

    // Opening role modal from View User modal
    $('#btnManageRoleFromModal').on('click', function() {
        var rId = this.getAttribute('data-role-id');
        $('#viewUserModal').modal('hide');
        
        setTimeout(function() {
            modalRoleSelector.val(rId).trigger('change');
            $('#manageRoleModal').modal('show');
        }, 350);
    });

    // Opening role modal from page header
    $('#btnOpenRoleModal').on('click', function() {
        var currentSelected = modalRoleSelector.val();
        loadRoleModulesData(currentSelected);
    });

    // Save Role Permissions Button Click
    btnModalSave.addEventListener('click', function() {
        var roleId = modalRoleSelector.val();
        var selectedRoleKey = modalRoleSelector.find('option:selected').attr('data-key') || '';
        var selectedRoleName = modalRoleSelector.find('option:selected').text().trim();

        if (!roleId) return;

        var postData = new URLSearchParams();
        document.querySelectorAll('.switchery-role').forEach(function(el) {
            var modKey = el.getAttribute('data-mod');
            postData.append('modules[' + modKey + ']', el.checked ? '1' : '0');
        });

        btnModalSave.disabled = true;
        btnModalSave.innerHTML = '<i class="icon-spinner2 spinner position-left"></i> Saving...';

        fetch('<?= base_url('permission/save-modules') ?>/' + roleId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: postData.toString()
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            btnModalSave.disabled = false;
            btnModalSave.innerHTML = '<i class="icon-checkmark4 position-left"></i> Save Permissions';

            if (data && data.status) {
                // Real-time update of all table rows with this role
                var updatedMods = data.modules || {};
                var totalPermsEnabled = 0;
                for (var k in updatedMods) {
                    if (updatedMods[k]) totalPermsEnabled++;
                }

                userRows.forEach(function(row) {
                    if (row.getAttribute('data-role') === selectedRoleKey) {
                        // Update data attributes
                        row.setAttribute('data-waves', updatedMods.waves ? '1' : '0');
                        row.setAttribute('data-locker', updatedMods.locker ? '1' : '0');
                        row.setAttribute('data-loss', updatedMods.loss ? '1' : '0');
                        row.setAttribute('data-stock', updatedMods.stock ? '1' : '0');
                        row.setAttribute('data-total', totalPermsEnabled);


                        // Update total perms count & progress bar
                        var totalCell = row.querySelector('.cell-total-perms');
                        if (totalCell) {
                            var valSpan = totalCell.querySelector('.val-total-perms');
                            var progBar = totalCell.querySelector('.progress-bar');
                            if (valSpan) valSpan.textContent = totalPermsEnabled + '/4';
                            if (progBar) {
                                progBar.style.width = (totalPermsEnabled / 4 * 100) + '%';
                                progBar.className = 'progress-bar ' + (totalPermsEnabled >= 3 ? 'progress-bar-success' : (totalPermsEnabled >= 1 ? 'progress-bar-primary' : 'progress-bar-danger'));
                            }
                        }

                        // Update stored JSON on View button
                        var viewBtn = row.querySelector('.btn-view-user');
                        if (viewBtn) {
                            try {
                                var parsed = JSON.parse(viewBtn.getAttribute('data-user'));
                                parsed.modules = updatedMods;
                                parsed.total_enabled = totalPermsEnabled;
                                viewBtn.setAttribute('data-user', JSON.stringify(parsed));
                            } catch(e) {}
                        }
                    }
                });

                // Update summary dashboard card for total enabled permissions
                recalculateSummaryTotals();

                // Close modal and show SweetAlert
                $('#manageRoleModal').modal('hide');

                if (typeof swal === 'function') {
                    swal({
                        title: 'Permissions Saved!',
                        text: data.message || 'Permissions for role ' + selectedRoleName + ' updated successfully.',
                        type: 'success',
                        confirmButtonColor: '#0284c7',
                        timer: 1800,
                        showConfirmButton: false
                    });
                }
            } else {
                if (typeof swal === 'function') {
                    swal({
                        title: 'Error',
                        text: (data && data.error) ? data.error : 'Failed to save permissions.',
                        type: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                }
            }
        })
        .catch(function(err) {
            btnModalSave.disabled = false;
            btnModalSave.innerHTML = '<i class="icon-checkmark4 position-left"></i> Save Permissions';
            console.error('Save request failed:', err);
            if (typeof swal === 'function') {
                swal({ title: 'Network Error', text: 'Failed to communicate with server.', type: 'error' });
            }
        });
    });

    function updateTableCellBadge(cell, isEnabled) {
        if (!cell) return;
        if (isEnabled) {
            cell.innerHTML = '<span class="sw-pill-badge active" style="font-weight: 700; min-width: 48px;"><i class="icon-checkmark4 position-left" style="font-size: 10px;"></i>ON</span>';
        } else {
            cell.innerHTML = '<span class="sw-pill-badge inactive" style="font-weight: 700; min-width: 48px;"><i class="icon-cross2 position-left" style="font-size: 10px;"></i>OFF</span>';
        }
    }

    function recalculateSummaryTotals() {
        var sumEnabled = 0;
        var usersWithAccess = 0;
        userRows.forEach(function(r) {
            var total = parseInt(r.getAttribute('data-total') || '0', 10);
            var status = r.getAttribute('data-status');
            sumEnabled += total;
            if (status === '1' && total > 0) {
                usersWithAccess++;
            }
        });

        var sumEl = document.getElementById('summaryTotalEnabled');
        if (sumEl) sumEl.textContent = sumEnabled;

        var accEl = document.getElementById('summaryUsersWithAccess');
        if (accEl) accEl.textContent = usersWithAccess;
    }

    // Trigger initial filter evaluation
    applyFilters();
});
</script>
<?= $this->endSection() ?>
