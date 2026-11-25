<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?><?= esc($title) ?> - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Permissions / User Details<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>User-wise Permission Details & Overrides</h1>
        <p>Audit individual user effective privileges and apply granular overrides</p>
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
        <a href="<?= base_url('permission/audit-logs') ?>" class="sw-btn-secondary">
            <i class="icon-history"></i>
            <span>Audit Trail</span>
        </a>
    </div>
</div>

<?php
$totalUsers = count($users);
$totalOverriddenUsers = 0;
$totalGrants = 0;
$totalRestrictions = 0;

foreach ($users as $u) {
    if (!empty($u['overrides'])) {
        $totalOverriddenUsers++;
        $totalGrants += $u['extra_grants'];
        $totalRestrictions += $u['restrictions'];
    }
}

// Build permission dictionary
$permLookup = [];
$permsByModule = [];
foreach ($allPerms as $p) {
    $permLookup[$p['permission_id']] = $p;
    $permsByModule[$p['module_key']][] = $p;
}
?>

<!-- Quick Overview Metrics -->
<div class="row" style="margin-bottom: 24px;">
    <div class="col-sm-6 col-md-3">
        <div class="panel panel-body" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); padding: 16px 20px; box-shadow: var(--sw-shadow-sm); margin-bottom: 16px;">
            <div class="media no-margin">
                <div class="media-body">
                    <h3 class="no-margin text-semibold" style="color: var(--sw-text-main); font-size: 24px;"><?= $totalUsers ?></h3>
                    <span class="text-uppercase text-size-mini text-muted" style="letter-spacing: 0.5px; font-weight: 600;">Internal Staff Users</span>
                </div>
                <div class="media-right media-middle">
                    <i class="icon-users4 text-muted" style="font-size: 28px; color: var(--sw-primary);"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="panel panel-body" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); padding: 16px 20px; box-shadow: var(--sw-shadow-sm); margin-bottom: 16px;">
            <div class="media no-margin">
                <div class="media-body">
                    <h3 class="no-margin text-semibold" style="color: var(--sw-text-main); font-size: 24px;"><?= $totalOverriddenUsers ?></h3>
                    <span class="text-uppercase text-size-mini text-muted" style="letter-spacing: 0.5px; font-weight: 600;">Custom Overridden Users</span>
                </div>
                <div class="media-right media-middle">
                    <i class="icon-user-check text-muted" style="font-size: 28px; color: #d97706;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="panel panel-body" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); padding: 16px 20px; box-shadow: var(--sw-shadow-sm); margin-bottom: 16px;">
            <div class="media no-margin">
                <div class="media-body">
                    <h3 class="no-margin text-semibold" style="color: var(--sw-text-main); font-size: 24px;"><?= $totalGrants ?></h3>
                    <span class="text-uppercase text-size-mini text-muted" style="letter-spacing: 0.5px; font-weight: 600;">Individual Grants (+)</span>
                </div>
                <div class="media-right media-middle">
                    <i class="icon-plus-circle2 text-muted" style="font-size: 28px; color: #059669;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="panel panel-body" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); padding: 16px 20px; box-shadow: var(--sw-shadow-sm); margin-bottom: 16px;">
            <div class="media no-margin">
                <div class="media-body">
                    <h3 class="no-margin text-semibold" style="color: var(--sw-text-main); font-size: 24px;"><?= $totalRestrictions ?></h3>
                    <span class="text-uppercase text-size-mini text-muted" style="letter-spacing: 0.5px; font-weight: 600;">Explicit Restrictions (-)</span>
                </div>
                <div class="media-right media-middle">
                    <i class="icon-minus-circle2 text-muted" style="font-size: 28px; color: #dc2626;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Users Table Panel -->
<div class="panel panel-flat" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius); box-shadow: var(--sw-shadow-sm); background: #fff;">
    <div class="panel-heading" style="padding: 16px 20px; border-bottom: 1px solid var(--sw-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h5 class="panel-title" style="font-size: 16px; font-weight: 600; color: var(--sw-text-main); margin: 0;">
            <i class="icon-users4" style="margin-right: 8px; color: var(--sw-primary);"></i>
            Staff User Permissions & Custom Overrides
        </h5>
        <div style="width: 250px;">
            <input type="text" id="userSearchInput" class="form-control" placeholder="Search user or role..." style="height: 34px; font-size: 13px;">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-striped" id="usersTable">
            <thead>
                <tr style="background: var(--sw-surface-alt); font-size: 12px; text-transform: uppercase; color: var(--sw-text-muted);">
                    <th style="font-weight: 600; width: 60px;">ID</th>
                    <th style="font-weight: 600;">Username</th>
                    <th style="font-weight: 600;">Assigned Role</th>
                    <th style="font-weight: 600; text-align: center;">Location</th>
                    <th style="font-weight: 600; text-align: center;">Status</th>
                    <th style="font-weight: 600; text-align: center;">Role Base Perms</th>
                    <th style="font-weight: 600; text-align: center;">User Grants (+)</th>
                    <th style="font-weight: 600; text-align: center;">User Denies (-)</th>
                    <th style="font-weight: 600; text-align: center;">Effective Total</th>
                    <th style="font-weight: 600; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <?php 
                        $isSuper = ($u['access_level'] === 'superadmin');
                        $hasOverrides = !empty($u['overrides']);
                    ?>
                    <tr class="user-row" data-search="<?= strtolower(esc($u['username'] . ' ' . $u['access_level'] . ' ' . $u['role_name'] . ' ' . $u['location'])) ?>">
                        <td style="font-weight: 600; color: #64748b;"><?= $u['user_id'] ?></td>
                        <td>
                            <div style="font-weight: 600; color: var(--sw-text-main); font-size: 14px;">
                                <?= esc($u['username']) ?>
                                <?php if ($isSuper): ?>
                                    <span class="badge badge-danger" style="font-size: 10px; margin-left: 4px;">Super Admin</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="label label-primary" style="font-size: 11px;">
                                <?= esc($u['role_name']) ?>
                            </span>
                            <div class="text-muted text-size-mini"><code><?= esc($u['access_level']) ?></code></div>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <span class="badge badge-default" style="text-transform: uppercase; font-size: 11px;">
                                <?= esc($u['location'] ?: 'ALL') ?>
                            </span>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <?php if ((int)$u['status'] === 1): ?>
                                <span class="label label-success" style="font-size: 10px;">Active</span>
                            <?php else: ?>
                                <span class="label label-danger" style="font-size: 10px;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <span class="badge" style="background: #e0f2fe; color: #0284c7; font-weight: 600;">
                                <?= $isSuper ? 'ALL (102)' : $u['role_perms_count'] ?>
                            </span>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <?php if ($u['extra_grants'] > 0): ?>
                                <span class="badge" style="background: #ecfdf5; color: #059669; font-weight: 600;">
                                    +<?= $u['extra_grants'] ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">&bull;</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <?php if ($u['restrictions'] > 0): ?>
                                <span class="badge" style="background: #fef2f2; color: #dc2626; font-weight: 600;">
                                    -<?= $u['restrictions'] ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">&bull;</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <strong style="color: <?= $isSuper || $u['effective_count'] > 0 ? '#059669' : '#94a3b8' ?>; font-size: 15px;">
                                <?= $isSuper ? '102' : $u['effective_count'] ?>
                            </strong>
                        </td>
                        <td style="text-align: right; vertical-align: middle;">
                            <?php if ($isSuper): ?>
                                <button type="button" class="btn btn-xs btn-default disabled" title="Super Admin permissions are immutable">
                                    <i class="icon-lock2"></i> Locked
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-xs btn-default btn-manage-override" 
                                        data-user='<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>'>
                                    <i class="icon-cog5"></i> Manage Overrides
                                    <?php if ($hasOverrides): ?>
                                        <span class="badge badge-warning" style="font-size: 9px; margin-left: 2px;">
                                            <?= count($u['overrides']) ?>
                                        </span>
                                    <?php endif; ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- User Overrides Modal -->
<div class="modal fade" id="userOverridesModal" tabindex="-1" role="dialog" aria-labelledby="userOverridesModalTitle">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: var(--sw-radius); border: 1px solid var(--sw-border); box-shadow: var(--sw-shadow-lg);">
            <div class="modal-header" style="background: var(--sw-surface-alt); border-bottom: 1px solid var(--sw-border); padding: 16px 20px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="userOverridesModalTitle" style="font-weight: 600; color: var(--sw-text-main);">
                    <i class="icon-user-lock" style="color: var(--sw-primary); margin-right: 6px;"></i>
                    Manage User Overrides &bull; <span id="modalUsername" style="color: var(--sw-primary);"></span>
                </h4>
            </div>
            
            <div class="modal-body" style="padding: 20px;">
                <!-- User Summary Banner -->
                <div class="panel panel-body" style="background: #f8fafc; border: 1px solid var(--sw-border); border-radius: var(--sw-radius); padding: 14px 18px; margin-bottom: 20px;">
                    <div class="row">
                        <div class="col-xs-6 col-sm-3">
                            <span class="text-muted text-size-mini">USER ID:</span>
                            <div class="text-semibold" id="modalUserId"></div>
                        </div>
                        <div class="col-xs-6 col-sm-3">
                            <span class="text-muted text-size-mini">INHERITED ROLE:</span>
                            <div><span class="label label-primary" id="modalRoleName"></span></div>
                        </div>
                        <div class="col-xs-6 col-sm-3">
                            <span class="text-muted text-size-mini">BASE ROLE PERMS:</span>
                            <div class="text-semibold text-primary" id="modalRolePerms"></div>
                        </div>
                        <div class="col-xs-6 col-sm-3">
                            <span class="text-muted text-size-mini">EFFECTIVE PERMS:</span>
                            <div class="text-semibold text-success" id="modalEffectivePerms"></div>
                        </div>
                    </div>
                </div>

                <!-- Active Overrides Table Section -->
                <h6 class="text-semibold" style="margin-bottom: 10px; color: var(--sw-text-main);">
                    <i class="icon-list-unordered text-muted" style="margin-right: 4px;"></i>
                    Active User-Specific Overrides
                </h6>
                <div class="table-responsive" style="margin-bottom: 24px; border: 1px solid var(--sw-border); border-radius: var(--sw-radius-sm);">
                    <table class="table table-bordered table-striped" style="margin-bottom: 0;">
                        <thead>
                            <tr style="background: #f8fafc; font-size: 11px; text-transform: uppercase;">
                                <th>Permission</th>
                                <th>Type</th>
                                <th>Reason</th>
                                <th>Applied At</th>
                                <th style="text-align: right; width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="modalOverridesTableBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Add New Override Form -->
                <div class="panel panel-flat" style="border: 1px solid var(--sw-border); border-radius: var(--sw-radius-sm); background: #fdfdfd; padding: 16px;">
                    <h6 class="text-semibold" style="margin-top: 0; margin-bottom: 14px; color: var(--sw-text-main);">
                        <i class="icon-plus-circle2 text-success" style="margin-right: 4px;"></i>
                        Add New Individual Permission Override
                    </h6>
                    
                    <form id="addOverrideForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_id" id="overrideUserId" value="">

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="text-semibold text-size-mini">Select Permission <span class="text-danger">*</span></label>
                                <select name="permission_id" id="overridePermissionSelect" class="form-control" required style="font-size: 13px;">
                                    <option value="">-- Choose Permission to Override --</option>
                                    <?php foreach ($permsByModule as $modKey => $pList): ?>
                                        <optgroup label="<?= esc(ucwords(str_replace('_', ' ', $modKey))) ?>">
                                            <?php foreach ($pList as $p): ?>
                                                <option value="<?= $p['permission_id'] ?>">
                                                    <?= esc($p['permission_name']) ?> (<?= esc($p['permission_code']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 form-group">
                                <label class="text-semibold text-size-mini">Override Action <span class="text-danger">*</span></label>
                                <select name="is_granted" id="overrideIsGranted" class="form-control" required style="font-size: 13px;">
                                    <option value="1">GRANT Extra Permission (User has access even if role lacks it)</option>
                                    <option value="0">RESTRICT / DENY Permission (User is explicitly blocked even if role has it)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="text-semibold text-size-mini">Justification / Reason <span class="text-danger">*</span></label>
                            <input type="text" name="reason" id="overrideReason" class="form-control" placeholder="E.g., Authorized by Paresh for temporary holiday coverage" required style="font-size: 13px;">
                            <span class="help-block text-size-mini text-muted">A mandatory business reason is permanently recorded in the audit log.</span>
                        </div>

                        <div class="text-right">
                            <button type="submit" id="btnSubmitOverride" class="btn btn-primary btn-sm">
                                <i class="icon-floppy-disk"></i> Apply User Override
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal-footer" style="padding: 12px 20px; background: var(--sw-surface-alt); border-top: 1px solid var(--sw-border);">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const permLookup = <?= json_encode($permLookup) ?>;
    const userSearchInput = document.getElementById('userSearchInput');
    const userRows = document.querySelectorAll('.user-row');

    // Live search in users table
    userSearchInput.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        userRows.forEach(r => {
            const data = r.getAttribute('data-search') || '';
            r.style.display = data.includes(q) ? '' : 'none';
        });
    });

    // Modal elements
    const modal = $('#userOverridesModal');
    const modalUsername = document.getElementById('modalUsername');
    const modalUserId = document.getElementById('modalUserId');
    const modalRoleName = document.getElementById('modalRoleName');
    const modalRolePerms = document.getElementById('modalRolePerms');
    const modalEffectivePerms = document.getElementById('modalEffectivePerms');
    const modalBodyTable = document.getElementById('modalOverridesTableBody');
    const overrideUserIdInput = document.getElementById('overrideUserId');
    const addOverrideForm = document.getElementById('addOverrideForm');

    let currentUserData = null;

    // Handle "Manage Overrides" click
    document.querySelectorAll('.btn-manage-override').forEach(btn => {
        btn.addEventListener('click', function () {
            const u = JSON.parse(this.getAttribute('data-user'));
            currentUserData = u;

            modalUsername.innerText = u.username;
            modalUserId.innerText = '#' + u.user_id;
            modalRoleName.innerText = u.role_name;
            modalRolePerms.innerText = u.role_perms_count + ' permissions';
            modalEffectivePerms.innerText = u.effective_count + ' permissions';
            overrideUserIdInput.value = u.user_id;

            renderOverridesTable(u.overrides || []);

            // Reset form
            addOverrideForm.reset();
            overrideUserIdInput.value = u.user_id;

            modal.modal('show');
        });
    });

    function renderOverridesTable(overrides) {
        modalBodyTable.innerHTML = '';
        if (!overrides || overrides.length === 0) {
            modalBodyTable.innerHTML = `<tr><td colspan="5" class="text-center text-muted" style="padding: 16px;">No individual overrides configured for this user. Inheriting 100% standard role permissions.</td></tr>`;
            return;
        }

        overrides.forEach(ov => {
            const pId = parseInt(ov.permission_id);
            const p = permLookup[pId] || { permission_name: 'Unknown', permission_code: 'unknown' };
            const isGranted = parseInt(ov.is_granted) === 1;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div style="font-weight: 600; font-size: 13px;">${p.permission_name}</div>
                    <code style="font-size: 11px;">${p.permission_code}</code>
                </td>
                <td style="vertical-align: middle;">
                    ${isGranted 
                        ? '<span class="label label-success" style="font-size: 10px;"><i class="icon-plus-circle2"></i> Granted</span>' 
                        : '<span class="label label-danger" style="font-size: 10px;"><i class="icon-minus-circle2"></i> Restricted</span>'}
                </td>
                <td style="font-size: 12px; color: #475569; vertical-align: middle;">
                    ${ov.reason || 'N/A'}
                </td>
                <td style="font-size: 11px; color: #94a3b8; vertical-align: middle;">
                    ${ov.created_at || 'Recently'}
                </td>
                <td style="text-align: right; vertical-align: middle;">
                    <button type="button" class="btn btn-xs btn-danger btn-remove-override" data-override-id="${ov.id}" title="Remove override & revert to role default">
                        <i class="icon-trash"></i>
                    </button>
                </td>
            `;
            modalBodyTable.appendChild(tr);
        });

        // Attach delete events
        modalBodyTable.querySelectorAll('.btn-remove-override').forEach(delBtn => {
            delBtn.addEventListener('click', function () {
                const ovId = this.getAttribute('data-override-id');
                confirmAndRemoveOverride(ovId);
            });
        });
    }

    // Form Submit: Add Override
    addOverrideForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitOverride');
        btn.disabled = true;

        const formData = new FormData(addOverrideForm);

        fetch('<?= base_url('permission/override') ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.status) {
                swal({
                    title: "Override Saved!",
                    text: data.message,
                    type: "success",
                    confirmButtonColor: "#2563eb"
                }, function () {
                    location.reload();
                });
            } else {
                swal("Failed", data.error || "Could not save override", "error");
            }
        })
        .catch(err => {
            btn.disabled = false;
            swal("Error", "Network or server failure: " + err, "error");
        });
    });

    function confirmAndRemoveOverride(overrideId) {
        swal({
            title: "Remove Override?",
            text: "This user will immediately revert to the standard role permission inheritance.",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc2626",
            confirmButtonText: "Yes, Remove Override",
            closeOnConfirm: false
        }, function () {
            fetch('<?= base_url('permission/override/remove/') ?>/' + overrideId, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status) {
                    swal({
                        title: "Removed!",
                        text: data.message,
                        type: "success"
                    }, function () {
                        location.reload();
                    });
                } else {
                    swal("Failed", data.error || "Could not delete override", "error");
                }
            })
            .catch(err => {
                swal("Error", "Network or server error: " + err, "error");
            });
        });
    }
});
</script>
<?= $this->endSection() ?>

