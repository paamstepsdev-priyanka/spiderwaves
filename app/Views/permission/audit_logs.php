<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?><?= esc($title) ?> - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Permissions / Audit Trail<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Permission Audit Trail</h1>
        <p>Immutable forensic record of all role assignments, user overrides, and security privilege modifications</p>
    </div>
    <div class="sw-page-actions">
        <a href="<?= base_url('permission') ?>" class="sw-btn-secondary">
            <i class="icon-meter-fast"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?= base_url('permission/assign') ?>" class="sw-btn-secondary">
            <i class="icon-tree7"></i>
            <span>Role Permissions</span>
        </a>
        <a href="<?= base_url('permission/summary') ?>" class="sw-btn-secondary">
            <i class="icon-table2"></i>
            <span>Role Summary</span>
        </a>
        <a href="<?= base_url('permission/users') ?>" class="sw-btn-secondary">
            <i class="icon-users4"></i>
            <span>User Details</span>
        </a>
    </div>
</div>

<!-- Audit Trail Panel -->
<div class="panel panel-flat" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); box-shadow: var(--sw-shadow-sm); background: #fff;">
    <div class="panel-heading" style="padding: 16px 20px; border-bottom: 1px solid var(--sw-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h5 class="panel-title" style="font-size: 16px; font-weight: 600; color: var(--sw-text-main); margin: 0;">
                <i class="icon-history" style="margin-right: 8px; color: var(--sw-primary);"></i>
                Recent Security Audit Logs (Latest <?= count($logs) ?> Events)
            </h5>
            <span class="text-muted text-size-mini">All events are permanently captured with user identity and timestamp</span>
        </div>
        <div style="width: 280px;">
            <input type="text" id="auditSearchInput" class="form-control" placeholder="Search actor, action, target, or IP..." style="height: 34px; font-size: 13px;">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-striped" id="auditLogsTable" style="margin-bottom: 0;">
            <thead>
                <tr style="background: var(--sw-surface-alt); font-size: 12px; text-transform: uppercase; color: var(--sw-text-muted);">
                    <th style="font-weight: 600; width: 60px;">ID</th>
                    <th style="font-weight: 600; width: 160px;">Timestamp</th>
                    <th style="font-weight: 600; width: 140px;">Actor</th>
                    <th style="font-weight: 600; width: 150px;">Target</th>
                    <th style="font-weight: 600; width: 170px;">Action Type</th>
                    <th style="font-weight: 600;">Details &amp; Audit Payload</th>
                    <th style="font-weight: 600; width: 120px; text-align: right;">IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 30px;">
                            <i class="icon-info22" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                            No permission change events recorded yet. Changes will appear here automatically.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <?php
                            $actorName = $userMap[$l['actor_user_id']] ?? ('User #' . $l['actor_user_id']);
                            $targetDesc = '';
                            if ($l['target_type'] === 'role') {
                                $targetDesc = 'Role: ' . ($roleMap[$l['target_id']] ?? ('Role #' . $l['target_id']));
                            } else {
                                $targetDesc = 'User: ' . ($userMap[$l['target_id']] ?? ('User #' . $l['target_id']));
                            }

                            $detailsArr = json_decode($l['details'] ?? '{}', true) ?: [];
                            $searchPayload = strtolower(esc(
                                $actorName . ' ' . 
                                $l['action'] . ' ' . 
                                $targetDesc . ' ' . 
                                $l['ip_address'] . ' ' . 
                                ($l['details'] ?? '')
                            ));
                        ?>
                        <tr class="audit-row" data-search="<?= $searchPayload ?>">
                            <td style="font-weight: 600; color: #64748b;">#<?= $l['id'] ?></td>
                            <td style="font-size: 12px; color: #475569; white-space: nowrap;">
                                <?= date('Y-m-d H:i:s', strtotime($l['created_at'])) ?>
                            </td>
                            <td>
                                <div style="font-weight: 600; font-size: 13px; color: var(--sw-text-main);">
                                    <?= esc($actorName) ?>
                                </div>
                                <span class="text-muted text-size-mini">UID: <?= (int)$l['actor_user_id'] ?></span>
                            </td>
                            <td>
                                <span class="label <?= $l['target_type'] === 'role' ? 'label-primary' : 'label-info' ?>" style="font-size: 11px;">
                                    <?= esc($targetDesc) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($l['action'] === 'update_role_permissions'): ?>
                                    <span class="label label-warning" style="font-size: 11px;">
                                        <i class="icon-tree7"></i> Role Permissions
                                    </span>
                                <?php elseif ($l['action'] === 'set_user_override'): ?>
                                    <span class="label label-success" style="font-size: 11px;">
                                        <i class="icon-user-plus"></i> Set Override
                                    </span>
                                <?php elseif ($l['action'] === 'remove_user_override'): ?>
                                    <span class="label label-danger" style="font-size: 11px;">
                                        <i class="icon-user-minus"></i> Remove Override
                                    </span>
                                <?php else: ?>
                                    <span class="label label-default" style="font-size: 11px;">
                                        <?= esc($l['action']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size: 12px;">
                                    <?php if ($l['action'] === 'update_role_permissions'): ?>
                                        <span>Updated role <strong><?= esc($detailsArr['role_key'] ?? '') ?></strong>: </span>
                                        <span class="badge" style="background: #e0f2fe; color: #0284c7; font-size: 10px;"><?= $detailsArr['menus_count'] ?? 0 ?> menus</span>
                                        <span class="badge" style="background: #ecfdf5; color: #059669; font-size: 10px;"><?= $detailsArr['perms_count'] ?? 0 ?> permissions</span>
                                    <?php elseif ($l['action'] === 'set_user_override'): ?>
                                        <span>Target <strong><?= esc($detailsArr['target_username'] ?? '') ?></strong> &bull; Perm <code><?= esc($detailsArr['permission_code'] ?? '') ?></code></span>
                                        <?= !empty($detailsArr['is_granted']) ? '<span class="text-success text-semibold">(GRANTED)</span>' : '<span class="text-danger text-semibold">(RESTRICTED)</span>' ?>
                                        <div class="text-muted text-size-mini" style="margin-top: 2px;">
                                            <em>Reason:</em> <?= esc($detailsArr['reason'] ?? 'N/A') ?>
                                        </div>
                                    <?php elseif ($l['action'] === 'remove_user_override'): ?>
                                        <span>Reverted override #<?= esc($detailsArr['override_id'] ?? '') ?> for perm ID #<?= esc($detailsArr['permission_id'] ?? '') ?></span>
                                    <?php else: ?>
                                        <pre style="margin: 0; padding: 4px 8px; font-size: 11px; background: #f8fafc; border: 1px solid #e2e8f0;"><?= esc(json_encode($detailsArr, JSON_PRETTY_PRINT)) ?></pre>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="text-align: right; font-family: monospace; font-size: 11px; color: #64748b; white-space: nowrap;">
                                <?= esc($l['ip_address'] ?? '127.0.0.1') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('auditSearchInput');
    const rows = document.querySelectorAll('.audit-row');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            rows.forEach(r => {
                const search = r.getAttribute('data-search') || '';
                r.style.display = search.includes(q) ? '' : 'none';
            });
        });
    }
});
</script>
<?= $this->endSection() ?>

