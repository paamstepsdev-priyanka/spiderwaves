<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?><?= esc($title) ?> - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Permissions / Role Summary<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Role-wise Permission Summary</h1>
        <p>Comparative matrix and five-category permission statistics across all system roles</p>
    </div>
    <div class="sw-page-actions">
        <a href="<?= base_url('permission') ?>" class="sw-btn-secondary">
            <i class="icon-meter-fast"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?= base_url('permission/assign') ?>" class="sw-btn-primary">
            <i class="icon-tree7"></i>
            <span>Configure Role</span>
        </a>
        <a href="<?= base_url('permission/users') ?>" class="sw-btn-secondary">
            <i class="icon-users4"></i>
            <span>User Details</span>
        </a>
        <a href="<?= base_url('permission/audit-logs') ?>" class="sw-btn-secondary">
            <i class="icon-history"></i>
            <span>Audit Trail</span>
        </a>
    </div>
</div>

<!-- Quick System Highlights -->
<div class="row" style="margin-bottom: 24px;">
    <div class="col-sm-6 col-md-3">
        <div class="panel panel-body" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); padding: 16px 20px; box-shadow: var(--sw-shadow-sm); margin-bottom: 16px;">
            <div class="media no-margin">
                <div class="media-body">
                    <h3 class="no-margin text-semibold" style="color: var(--sw-text-main); font-size: 24px;"><?= count($roles) ?></h3>
                    <span class="text-uppercase text-size-mini text-muted" style="letter-spacing: 0.5px; font-weight: 600;">Active Roles</span>
                </div>
                <div class="media-right media-middle">
                    <i class="icon-shield-check text-muted" style="font-size: 28px; color: var(--sw-primary);"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="panel panel-body" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); padding: 16px 20px; box-shadow: var(--sw-shadow-sm); margin-bottom: 16px;">
            <div class="media no-margin">
                <div class="media-body">
                    <h3 class="no-margin text-semibold" style="color: var(--sw-text-main); font-size: 24px;"><?= (int)$systemStats['total_permissions'] ?></h3>
                    <span class="text-uppercase text-size-mini text-muted" style="letter-spacing: 0.5px; font-weight: 600;">Action Permissions</span>
                </div>
                <div class="media-right media-middle">
                    <i class="icon-key text-muted" style="font-size: 28px; color: #059669;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="panel panel-body" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); padding: 16px 20px; box-shadow: var(--sw-shadow-sm); margin-bottom: 16px;">
            <div class="media no-margin">
                <div class="media-body">
                    <h3 class="no-margin text-semibold" style="color: var(--sw-text-main); font-size: 24px;"><?= (int)$systemStats['total_sidebar'] ?></h3>
                    <span class="text-uppercase text-size-mini text-muted" style="letter-spacing: 0.5px; font-weight: 600;">Total Menus</span>
                </div>
                <div class="media-right media-middle">
                    <i class="icon-menu7 text-muted" style="font-size: 28px; color: #d97706;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="panel panel-body" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); padding: 16px 20px; box-shadow: var(--sw-shadow-sm); margin-bottom: 16px;">
            <div class="media no-margin">
                <div class="media-body">
                    <h3 class="no-margin text-semibold" style="color: var(--sw-text-main); font-size: 24px;"><?= (int)$systemStats['total_users'] ?></h3>
                    <span class="text-uppercase text-size-mini text-muted" style="letter-spacing: 0.5px; font-weight: 600;">Staff Users</span>
                </div>
                <div class="media-right media-middle">
                    <i class="icon-users4 text-muted" style="font-size: 28px; color: #6366f1;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section 1: Five-Category Role Breakdown Table -->
<div class="panel panel-flat" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); box-shadow: var(--sw-shadow-sm); margin-bottom: 24px; background: #fff;">
    <div class="panel-heading" style="padding: 16px 20px; border-bottom: 1px solid var(--sw-border); display: flex; justify-content: space-between; align-items: center;">
        <h5 class="panel-title" style="font-size: 16px; font-weight: 600; color: var(--sw-text-main); margin: 0;">
            <i class="icon-shield-check" style="margin-right: 8px; color: var(--sw-primary);"></i>
            Role Access Statistics (Five-Category Permission Universe)
        </h5>
        <span class="badge badge-primary"><?= count($roleStats) ?> Roles Configured</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr style="background: var(--sw-surface-alt); font-size: 12px; text-transform: uppercase; color: var(--sw-text-muted);">
                    <th style="font-weight: 600;">Role / Key</th>
                    <th style="font-weight: 600; text-align: center;">Active Users</th>
                    <th style="font-weight: 600; text-align: center;">Visible Menus</th>
                    <th style="font-weight: 600; text-align: center;">Explicit Granted</th>
                    <th style="font-weight: 600; text-align: center;">Explicit Denied</th>
                    <th style="font-weight: 600; text-align: center;">Not Assigned</th>
                    <th style="font-weight: 600; text-align: center;">Effective Access</th>
                    <th style="font-weight: 600; width: 140px;">Coverage</th>
                    <th style="font-weight: 600; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roleStats as $r): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--sw-text-main); font-size: 14px;">
                                <?= esc($r['role_name']) ?>
                                <?php if ($r['is_superadmin']): ?>
                                    <span class="badge badge-danger" style="font-size: 10px; margin-left: 6px;">Super Admin</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted text-size-mini">
                                <code><?= esc($r['role_key']) ?></code> &bull; <?= esc($r['description'] ?? 'Standard role') ?>
                            </div>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <span class="badge badge-default" style="font-weight: 600; background: #e2e8f0; color: #334155;">
                                <?= (int)$r['users_count'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <span class="badge" style="background: #e0f2fe; color: #0284c7; font-weight: 600;">
                                <?= (int)$r['visible_menus'] ?> / <?= (int)$systemStats['total_sidebar'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <span class="badge" style="background: #ecfdf5; color: #059669; font-weight: 600;">
                                <?= $r['is_superadmin'] ? 'ALL (102)' : (int)$r['assigned_perms'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <span class="badge" style="background: #fef2f2; color: #dc2626; font-weight: 600;">
                                <?= (int)$r['explicit_denied'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <span class="badge" style="background: #f1f5f9; color: #64748b; font-weight: 600;">
                                <?= (int)$r['not_assigned'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <strong style="color: <?= $r['effective_perms'] > 0 ? '#059669' : '#94a3b8' ?>; font-size: 15px;">
                                <?= (int)$r['effective_perms'] ?>
                            </strong>
                        </td>
                        <td style="vertical-align: middle;">
                            <div style="font-size: 11px; font-weight: 600; margin-bottom: 3px; display: flex; justify-content: space-between;">
                                <span><?= $r['effective_pct'] ?>%</span>
                            </div>
                            <div class="progress" style="height: 6px; margin: 0; background-color: #e2e8f0; border-radius: 3px;">
                                <div class="progress-bar <?= $r['is_superadmin'] ? 'progress-bar-danger' : ($r['effective_pct'] > 50 ? 'progress-bar-success' : ($r['effective_pct'] > 20 ? 'progress-bar-primary' : 'progress-bar-warning')) ?>" 
                                     style="width: <?= $r['effective_pct'] ?>%;">
                                </div>
                            </div>
                        </td>
                        <td style="text-align: right; vertical-align: middle;">
                            <?php if ($r['is_superadmin']): ?>
                                <button type="button" class="btn btn-xs btn-default disabled" title="Super Admin permissions are locked">
                                    <i class="icon-lock2"></i> Immutable
                                </button>
                            <?php else: ?>
                                <a href="<?= base_url('permission/assign/' . $r['role_id']) ?>" class="btn btn-xs btn-primary">
                                    <i class="icon-pencil5"></i> Edit Permissions
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Section 2: Detailed Cross-Role Permission Matrix -->
<div class="panel panel-flat" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); box-shadow: var(--sw-shadow-sm); background: #fff;">
    <div class="panel-heading" style="padding: 16px 20px; border-bottom: 1px solid var(--sw-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <h5 class="panel-title" style="font-size: 16px; font-weight: 600; color: var(--sw-text-main); margin: 0;">
                <i class="icon-grid" style="margin-right: 8px; color: var(--sw-primary);"></i>
                Comprehensive Permission Matrix Across Roles
            </h5>
            <span class="text-muted text-size-mini">Checkmark indicates explicit action permission granted to the role</span>
        </div>
        
        <div style="display: flex; gap: 10px; align-items: center;">
            <div style="position: relative; width: 260px;">
                <input type="text" id="matrixFilterInput" class="form-control" placeholder="Search module, action, or code..." style="height: 34px; font-size: 13px;">
            </div>
            <button type="button" id="clearFilterBtn" class="btn btn-default btn-sm" title="Clear filter">
                <i class="icon-cross2"></i>
            </button>
        </div>
    </div>

    <div class="table-responsive" style="max-height: 720px; overflow-y: auto;">
        <table class="table table-bordered table-hover" id="permMatrixTable" style="margin-bottom: 0;">
            <thead style="position: sticky; top: 0; background: #f8fafc; z-index: 10; box-shadow: 0 1px 2px rgba(0,0,0,0.06);">
                <tr style="font-size: 11px; text-transform: uppercase; color: var(--sw-text-muted);">
                    <th style="min-width: 140px; font-weight: 600;">Module</th>
                    <th style="min-width: 220px; font-weight: 600;">Permission / Action</th>
                    <th style="min-width: 70px; text-align: center; font-weight: 600;">Type</th>
                    <?php foreach ($roles as $r): ?>
                        <th style="text-align: center; font-weight: 600; min-width: 90px;" title="<?= esc($r['role_name']) ?>">
                            <?= esc($r['role_name']) ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groupedPerms as $moduleKey => $perms): ?>
                    <tr class="module-group-header" data-module="<?= esc($moduleKey) ?>" style="background: #f1f5f9; font-weight: 600;">
                        <td colspan="<?= 3 + count($roles) ?>" style="padding: 8px 16px; color: var(--sw-text-main);">
                            <i class="icon-folder5" style="margin-right: 6px; color: var(--sw-primary);"></i>
                            <?= esc(ucwords(str_replace('_', ' ', $moduleKey))) ?> 
                            <span class="badge badge-default" style="margin-left: 8px; font-size: 10px;"><?= count($perms) ?> permissions</span>
                        </td>
                    </tr>
                    <?php foreach ($perms as $p): ?>
                        <tr class="perm-row" data-search="<?= strtolower(esc($moduleKey . ' ' . $p['permission_code'] . ' ' . $p['permission_name'] . ' ' . ($p['description'] ?? ''))) ?>">
                            <td style="color: var(--sw-text-muted); font-size: 12px; font-weight: 500;">
                                <?= esc($moduleKey) ?>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--sw-text-main); font-size: 13px;">
                                    <?= esc($p['permission_name']) ?>
                                </div>
                                <code style="font-size: 11px; color: #475569;"><?= esc($p['permission_code']) ?></code>
                            </td>
                            <td style="text-align: center; vertical-align: middle;">
                                <?php if ($p['permission_type'] === 'page'): ?>
                                    <span class="label label-info" style="font-size: 10px;">Page</span>
                                <?php else: ?>
                                    <span class="label label-default" style="font-size: 10px; background: #e2e8f0; color: #475569;">Button</span>
                                <?php endif; ?>
                            </td>
                            <?php foreach ($roles as $r): ?>
                                <?php
                                    $isSuper = ($r['role_key'] === 'superadmin');
                                    $isGranted = $isSuper || isset($rolePermMap[$r['role_id']][$p['permission_id']]);
                                ?>
                                <td style="text-align: center; vertical-align: middle;">
                                    <?php if ($isGranted): ?>
                                        <span class="text-success" title="Granted to <?= esc($r['role_name']) ?>" style="font-size: 16px;">
                                            <i class="icon-checkmark3"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted text-subtle" title="Not assigned" style="opacity: 0.25; font-size: 14px;">
                                            &bull;
                                        </span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterInput = document.getElementById('matrixFilterInput');
    const clearBtn = document.getElementById('clearFilterBtn');
    const rows = document.querySelectorAll('.perm-row');
    const groupHeaders = document.querySelectorAll('.module-group-header');

    function applyFilter() {
        const query = filterInput.value.trim().toLowerCase();
        if (!query) {
            rows.forEach(r => r.style.display = '');
            groupHeaders.forEach(gh => gh.style.display = '');
            return;
        }

        const visibleModules = new Set();
        rows.forEach(r => {
            const searchData = r.getAttribute('data-search') || '';
            if (searchData.includes(query)) {
                r.style.display = '';
                const mod = r.querySelector('td').innerText.trim();
                visibleModules.add(mod);
            } else {
                r.style.display = 'none';
            }
        });

        groupHeaders.forEach(gh => {
            const mod = gh.getAttribute('data-module');
            if (visibleModules.has(mod)) {
                gh.style.display = '';
            } else {
                gh.style.display = 'none';
            }
        });
    }

    filterInput.addEventListener('input', applyFilter);
    clearBtn.addEventListener('click', function () {
        filterInput.value = '';
        applyFilter();
        filterInput.focus();
    });
});
</script>
<?= $this->endSection() ?>

