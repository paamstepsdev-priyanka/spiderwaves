<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>User Management - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>User Management<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>User Management</h1>
    </div>
    <div class="sw-page-actions">
        <?php if (can('user.manage')): ?>
        <a href="<?= base_url('master/user/create') ?>" class="sw-btn-primary" style="padding: 6px 14px; font-size: 12px; font-weight: 700;">
            <i class="icon-plus2" style="font-size: 11px;"></i> NEW
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Flash Alerts -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="sw-alert sw-alert-success">
        <i class="icon-checkmark-circle"></i>
        <span><?= esc(session()->getFlashdata('success')) ?></span>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="sw-alert sw-alert-danger">
        <i class="icon-alert"></i>
        <span><?= esc(session()->getFlashdata('error')) ?></span>
    </div>
<?php endif; ?>

<!-- Search & Filter Toolbar -->
<div class="sw-filter-card">
    <form method="get" action="<?= base_url('master/user') ?>" id="userFilterForm">
        <div class="sw-filter-grid">
            <!-- Search Keyword Input -->
            <div class="sw-search-group" style="flex: 2;">
                <input type="text" name="q" class="sw-input" style="padding-left: 14px;" placeholder="Search by username..." value="<?= esc($search ?? '') ?>" autocomplete="off">
            </div>

            <!-- Access Level Dropdown -->
            <div style="flex: 1.5; min-width: 160px;">
                <select name="access_level" class="select2" onchange="document.getElementById('userFilterForm').submit()" style="width: 100%;">
                    <option value="">All Access Levels</option>
                    <?php foreach ($roles as $key => $label): ?>
                        <option value="<?= esc($key) ?>" <?= ($accessLevel === $key) ? 'selected' : '' ?>>
                            <?= esc($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Location Dropdown -->
            <div style="flex: 1.2; min-width: 140px;">
                <select name="location" class="select2" onchange="document.getElementById('userFilterForm').submit()" style="width: 100%;">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $locKey => $locLabel): ?>
                        <option value="<?= esc($locKey) ?>" <?= ($location === $locKey) ? 'selected' : '' ?>>
                            <?= esc($locLabel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Dropdown -->
            <div style="flex: 1; min-width: 110px;">
                <select name="status" class="select2" onchange="document.getElementById('userFilterForm').submit()" style="width: 100%;">
                    <option value="">All Statuses</option>
                    <option value="1" <?= ($status === '1') ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= ($status === '0') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <button type="submit" class="sw-btn-secondary" style="padding: 0 16px;">
                <i class="icon-filter4"></i>
                <span>Filter</span>
            </button>

            <?php if (!empty($search) || !empty($accessLevel) || !empty($location) || ($status !== null && $status !== '')): ?>
                <a href="<?= base_url('master/user') ?>" class="sw-btn-secondary" title="Reset Filters" style="color: #64748b; padding: 0 12px;">
                    <i class="icon-cross2"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- User Data Table Card -->
<div class="sw-table-card">
    <div class="sw-table-header">
        <div class="sw-table-title">System User Records</div>
        <div class="sw-table-info">
            Showing <?= count($users) ?> records
        </div>
    </div>

    <div class="sw-table-responsive">
        <table class="sw-table">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">S.No.</th>
                    <th style="width: 25%;">User Name</th>
                    <th style="width: 25%;">Access Level</th>
                    <th style="width: 15%; text-align: center;">Location</th>
                    <th style="width: 15%; text-align: center;">Status</th>
                    <th style="width: 15%; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php
                        $currentPage = $pager->getCurrentPage() ?: 1;
                        $perPage     = $pager->getPerPage() ?: 10;
                        $index       = ($currentPage - 1) * $perPage + 1;
                    ?>
                    <?php foreach ($users as $obj): ?>
                        <tr>
                            <!-- S.No -->
                            <td style="text-align: center; color: var(--sw-text-muted); font-size: 12.5px;">
                                <?= $index++; ?>
                            </td>

                            <!-- Username -->
                            <td>
                                <div style="font-size: 13.5px; font-weight: 700; color: var(--sw-text-main); line-height: 1.25;">
                                    <?= esc($obj['username']) ?>
                                </div>
                            </td>

                            <!-- Access Level -->
                            <td>
                                <?php
                                    $roleName = $roles[$obj['access_level']] ?? ucwords(str_replace('_', ' ', $obj['access_level']));
                                    $isAdmin = in_array($obj['access_level'], ['admin', 'superadmin']);
                                    $isMgr   = in_array($obj['access_level'], ['process_manager', 'jr_process_manager']);
                                ?>
                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 9px; border-radius: 4px; font-size: 12px; font-weight: 600; background: <?= $isAdmin ? '#fef3c7' : ($isMgr ? '#ede9fe' : '#f1f5f9') ?>; color: <?= $isAdmin ? '#92400e' : ($isMgr ? '#5b21b6' : '#334155') ?>; border: 1px solid <?= $isAdmin ? '#fde68a' : ($isMgr ? '#ddd6fe' : '#e2e8f0') ?>;">
                                    <i class="<?= $isAdmin ? 'icon-shield-check' : 'icon-user' ?>" style="font-size: 11px;"></i>
                                    <?= esc($roleName) ?>
                                </span>
                            </td>

                            <!-- Location -->
                            <td style="text-align: center;">
                                <?php if (!empty($obj['location'])): ?>
                                    <?php $locUpper = strtoupper($obj['location']); ?>
                                    <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; background: #f8fafc; color: #475569; font-weight: 600; font-size: 11.5px; border: 1px solid #cbd5e1;">
                                        <?= esc($locUpper) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 12px;">—</span>
                                <?php endif; ?>
                            </td>

                            <!-- Status Pill Badge -->
                            <td style="text-align: center;">
                                <?php if ($obj['status'] === '1'): ?>
                                    <span class="sw-pill-badge active">
                                        <span class="sw-badge-dot"></span> Active
                                    </span>
                                <?php else: ?>
                                    <span class="sw-pill-badge inactive">
                                        <span class="sw-badge-dot"></span> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td style="text-align: center;">
                                <div class="sw-action-buttons">
                                    <!-- View User Modal Trigger -->
                                    <a href="javascript:void(0)" class="sw-icon-btn btn-view-user" title="View User" data-key="<?= esc($obj['private_key']) ?>" data-user="<?= esc($obj['username']) ?>" data-role="<?= esc($roleName) ?>" data-location="<?= esc(!empty($obj['location']) ? strtoupper($obj['location']) : '—') ?>" data-status="<?= $obj['status'] === '1' ? 'Active' : 'Inactive' ?>" data-id="<?= (int)$obj['user_id'] ?>">
                                        <i class="icon-eye"></i>
                                    </a>

                                    <!-- Edit User -->
                                    <?php if (can('user.manage')): ?>
                                    <a href="<?= base_url('master/user/edit/' . $obj['private_key']) ?>" class="sw-icon-btn edit" title="Edit User">
                                        <i class="icon-pencil4"></i>
                                    </a>
                                    <?php endif; ?>

                                    <!-- Deactivate / Activate Toggle with SweetAlert -->
                                    <?php 
                                        $currentUserId = session()->get('spider')['user_id'] ?? null;
                                        $isSelf = ($currentUserId == $obj['user_id']);
                                    ?>
                                    <?php if (!$isSelf && can('user.manage')): ?>
                                        <?php if ($obj['status'] === '1'): ?>
                                            <a href="<?= base_url('master/user/toggle/' . $obj['private_key']) ?>" 
                                               class="sw-icon-btn toggle-deactivate sw-confirm-toggle" 
                                               title="Deactivate User" 
                                               data-action="Deactivate" 
                                               data-name="<?= esc($obj['username']) ?>">
                                                <i class="icon-blocked"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= base_url('master/user/toggle/' . $obj['private_key']) ?>" 
                                               class="sw-icon-btn toggle-activate sw-confirm-toggle" 
                                               title="Activate User" 
                                               data-action="Activate" 
                                               data-name="<?= esc($obj['username']) ?>">
                                                <i class="icon-checkmark-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php elseif ($isSelf): ?>
                                        <span class="sw-icon-btn" style="opacity: 0.35; cursor: not-allowed;" title="Current User Session">
                                            <i class="icon-lock2"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 48px 20px;">
                            <div style="font-size: 36px; color: var(--sw-text-subtle); margin-bottom: 12px;">
                                <i class="icon-search4"></i>
                            </div>
                            <h4 style="font-size: 16px; font-weight: 600; color: var(--sw-text-main); margin-bottom: 4px;">
                                No users found
                            </h4>
                            <p style="color: var(--sw-text-muted); font-size: 13px; max-width: 400px; margin: 0 auto 16px auto;">
                                Try adjusting your search query, access level, or location filter parameters.
                            </p>
                            <a href="<?= base_url('master/user') ?>" class="sw-btn-secondary" style="display: inline-flex;">
                                Clear Filters
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modern Card Pagination Footer -->
    <?php if ($pager->getPageCount() > 1): ?>
        <div class="sw-table-footer">
            <?= $pager->links('default', 'modern') ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modern User Profile Modal -->
<div class="modal fade" id="userViewModal" tabindex="-1" role="dialog" aria-labelledby="userViewModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: var(--sw-radius); border: 1px solid var(--sw-border); box-shadow: var(--sw-shadow-lg);">
            <div class="modal-header" style="border-bottom: 1px solid var(--sw-border); padding: 18px 24px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 22px;">&times;</button>
                <h4 class="modal-title" id="userViewModalLabel" style="font-weight: 700; color: var(--sw-text-main);">
                    <i class="icon-user-lock text-primary" style="margin-right: 8px;"></i> User Profile Details
                </h4>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9;">
                    <div id="modalAvatar" style="width: 52px; height: 52px; border-radius: 50%; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; border: 2px solid #bfdbfe;">
                        U
                    </div>
                    <div>
                        <h3 id="modalUsername" style="margin: 0 0 4px 0; font-size: 17px; font-weight: 700; color: #0f172a;">Username</h3>
                        <div style="font-size: 12px; color: #64748b;">User ID: <span id="modalUserId" style="font-weight: 600; color: #1e293b;">#0</span></div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div style="background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Access Level</div>
                        <div id="modalRole" style="font-size: 14px; font-weight: 700; color: #0f172a;">—</div>
                    </div>
                    <div style="background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Department Location</div>
                        <div id="modalLocation" style="font-size: 14px; font-weight: 700; color: #0f172a;">—</div>
                    </div>
                    <div style="background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Account Status</div>
                        <div id="modalStatus" style="font-size: 14px; font-weight: 700; color: #0f172a;">—</div>
                    </div>
                    <div style="background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; margin-bottom: 4px;">Security</div>
                        <div style="font-size: 13px; font-weight: 600; color: #2563eb;">
                            <i class="icon-shield-check" style="margin-right: 4px;"></i> Encrypted
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--sw-border); padding: 14px 24px;">
                <button type="button" class="sw-btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="text/javascript">
$(document).ready(function() {
    $('.btn-view-user').on('click', function() {
        var user     = $(this).data('user');
        var role     = $(this).data('role');
        var location = $(this).data('location');
        var status   = $(this).data('status');
        var id       = $(this).data('id');
        var initial  = user ? user.charAt(0).toUpperCase() : 'U';

        $('#modalAvatar').text(initial);
        $('#modalUsername').text(user);
        $('#modalUserId').text('#' + id);
        $('#modalRole').text(role);
        $('#modalLocation').text(location);
        if (status === 'Active') {
            $('#modalStatus').html('<span class="sw-pill-badge active"><span class="sw-badge-dot"></span> Active</span>');
        } else {
            $('#modalStatus').html('<span class="sw-pill-badge inactive"><span class="sw-badge-dot"></span> Inactive</span>');
        }

        $('#userViewModal').modal('show');
    });
});
</script>
<?= $this->endSection() ?>
