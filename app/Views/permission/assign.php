<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?><?= esc($title) ?> - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Permissions / <?= esc($isUserTarget ? 'User Assignment' : 'Role Assignment') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- 1. Top Header (Modeled strictly after user reference UI) -->
<div class="sw-page-header" style="margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
    <div class="sw-page-title-box">
        <h1 style="font-size: 22px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0;">
            Permissions for <?= esc(strtoupper($targetName)) ?>
        </h1>
        <p style="color: #64748b; font-size: 13px; margin: 0;">
            Assign functional access strictly by group hierarchy.
        </p>
    </div>
    
    <div class="sw-page-actions" style="display: flex; align-items: center; gap: 10px;">
        <!-- Live Save Status Indicator Badge -->
        <span id="saveStatusBadge" class="sw-pill-badge sw-badge-success" style="padding: 6px 14px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; border-radius: 9999px;">
            <i class="icon-checkmark4 position-left" style="margin-right: 6px;"></i>
            <span id="saveStatusText">Changes Saved</span>
        </span>

        <?php if (!$isSuperAdminRole): ?>
            <!-- Save Changes Primary Button -->
            <button type="button" id="btnSavePermissions" class="sw-btn-primary" style="padding: 7px 20px; font-size: 13px; font-weight: 600; border-radius: 6px;">
                <i class="icon-floppy-disk position-left" style="margin-right: 6px;"></i>
                <span id="saveBtnText">Save Changes</span>
            </button>
        <?php endif; ?>

        <!-- Back to Permission Management Table -->
        <a href="<?= base_url('permission') ?>" class="btn btn-default" style="font-weight: 600; padding: 7px 16px; border-radius: 6px; font-size: 13px; color: #334155; border: 1px solid #cbd5e1;">
            <i class="icon-arrow-left52 position-left" style="margin-right: 6px;"></i>
            <span>BACK</span>
        </a>
    </div>
</div>

<?php if ($isSuperAdminRole): ?>
    <div class="alert alert-info alert-styled-left" style="background-color: #eff6ff; border-color: #3b82f6; color: #1e3a8a; border-radius: 8px; margin-bottom: 20px;">
        <span class="text-semibold"><i class="icon-shield-check position-left"></i> Security Invariant:</span> 
        Super Admin permissions are permanently protected and locked to 100% full system access to prevent accidental system lockout.
    </div>
<?php endif; ?>

<!-- 2. Target Selector & Global Quick Controls -->
<div class="sw-filter-card" style="background: #ffffff; border: 1px solid var(--sw-border); border-radius: var(--sw-radius); padding: 12px 18px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
    <!-- Role / User Switcher -->
    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <label for="targetSelector" style="font-size: 12px; text-transform: uppercase; font-weight: 700; color: #64748b; margin: 0; white-space: nowrap;">
            <i class="icon-users4 position-left" style="margin-right: 4px;"></i> Select Target:
        </label>
        <select id="targetSelector" class="form-control" style="width: 340px; display: inline-block; font-weight: 600; height: 38px; border-radius: 6px; font-size: 13px;">
            <optgroup label="── Operational Roles ──">
                <?php foreach ($roles as $r): ?>
                    <option value="role_<?= (int)$r['role_id'] ?>" <?= (!$isUserTarget && (int)$r['role_id'] === (int)$targetRole['role_id']) ? 'selected' : '' ?>>
                        Role: <?= esc($r['role_name']) ?> (<?= esc($r['role_key']) ?>)
                    </option>
                <?php endforeach; ?>
            </optgroup>
            <optgroup label="── Staff Users ──">
                <?php foreach ($staffUsers as $u): ?>
                    <option value="user_<?= (int)$u['user_id'] ?>" <?= ($isUserTarget && (int)$u['user_id'] === (int)$targetUser['user_id']) ? 'selected' : '' ?>>
                        User: <?= esc($u['username']) ?> [<?= esc($u['access_level']) ?>]
                    </option>
                <?php endforeach; ?>
            </optgroup>
        </select>

        <?php if ($isUserTarget && !empty($targetRole)): ?>
            <span style="font-size: 12px; color: #64748b; background: #f1f5f9; padding: 4px 10px; border-radius: 4px;">
                Assigned Role: <strong style="color: #0f172a;"><?= esc($targetRole['role_name']) ?></strong>
            </span>
        <?php endif; ?>
    </div>

    <!-- Live Counter Summary & Quick Toggle Buttons -->
    <div style="display: flex; align-items: center; gap: 10px; margin-left: auto;">
        <span style="font-size: 13px; color: #64748b; font-weight: 500;">
            Total Access: <strong id="totalGrantedDisplay" style="color: #0f172a; font-weight: 700;"><?= (int)$totalGranted ?></strong> / <?= (int)$totalPerms ?>
        </span>

        <?php if (!$isSuperAdminRole): ?>
            <button type="button" class="btn btn-default btn-xs" id="btnSelectAllGlobal" style="font-weight: 600; padding: 5px 10px; border-radius: 4px; font-size: 12px;">
                <i class="icon-checkmark4 position-left"></i> Select All
            </button>
            <button type="button" class="btn btn-default btn-xs" id="btnDeselectAllGlobal" style="font-weight: 600; padding: 5px 10px; border-radius: 4px; font-size: 12px;">
                <i class="icon-cross2 position-left"></i> Deselect All
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- 3. Main 2-Column Master-Detail Layout -->
<form id="permissionAssignForm" method="POST" action="<?= esc($saveUrl) ?>">
    <?= csrf_field() ?>

    <div class="row">
        <!-- ================================================================= -->
        <!-- LEFT COLUMN: SEARCH INPUT + MODULE VERTICAL LIST (Col 3)         -->
        <!-- ================================================================= -->
        <div class="col-md-3 col-sm-4">
            <!-- Search permissions input -->
            <div style="position: relative; margin-bottom: 12px;">
                <i class="icon-search4 text-muted" style="position: absolute; left: 12px; top: 12px; font-size: 13px;"></i>
                <input type="text" id="permissionSearch" class="form-control" placeholder="Search permissions..." style="padding-left: 36px; border-radius: 6px; font-size: 13px; height: 38px; border: 1px solid var(--sw-border); box-shadow: none;">
            </div>

            <!-- Vertical list of modules with counters -->
            <div style="background: #ffffff; border: 1px solid var(--sw-border); border-radius: 8px; padding: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                <?php $isFirst = true; ?>
                <?php foreach ($groups as $gKey => $g): ?>
                    <div class="module-nav-item <?= $isFirst ? 'active' : '' ?>" 
                         data-target="module-card-<?= esc($g['key']) ?>" 
                         id="nav-item-<?= esc($g['key']) ?>"
                         style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 6px; margin-bottom: 3px; cursor: pointer; color: <?= $isFirst ? '#065f46' : '#334155' ?>; font-size: 13px; background: <?= $isFirst ? '#ecfdf5' : 'transparent' ?>; border-left: <?= $isFirst ? '3px solid #059669' : '3px solid transparent' ?>; font-weight: <?= $isFirst ? '600' : '500' ?>; transition: all 0.15s ease;">
                        <span class="module-nav-title" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 8px;">
                            <?= esc($g['title']) ?>
                        </span>
                        <span class="module-nav-badge" id="nav-badge-<?= esc($g['key']) ?>" style="padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; flex-shrink: 0;">
                            <?= (int)$g['granted_count'] ?>/<?= (int)$g['total_count'] ?>
                        </span>
                    </div>
                    <?php $isFirst = false; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- RIGHT COLUMN: HIERARCHICAL MODULE CARDS & CHECKLISTS (Col 9)     -->
        <!-- ================================================================= -->
        <div class="col-md-9 col-sm-8">
            <div id="permissionCardsContainer">
                <?php foreach ($groups as $gKey => $g): ?>
                    <!-- MASTER MODULE CARD -->
                    <div class="module-card panel panel-white" 
                         id="module-card-<?= esc($g['key']) ?>" 
                         data-group="<?= esc($g['key']) ?>"
                         style="border: 1px solid var(--sw-border); border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; background: #ffffff;">
                        
                        <!-- Master Card Header (Blue/Indigo vertical accent bar) -->
                        <div class="module-card-header" style="border-left: 4px solid var(--sw-primary); padding: 12px 18px; background: #ffffff; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                            <label style="display: flex; align-items: center; margin: 0; cursor: pointer; font-weight: 700; font-size: 15px; color: #0f172a;">
                                <input type="checkbox" 
                                       class="group-master-checkbox" 
                                       data-group="<?= esc($g['key']) ?>"
                                       <?= ($g['granted_count'] === $g['total_count'] && $g['total_count'] > 0) ? 'checked' : '' ?>
                                       <?= $isSuperAdminRole ? 'disabled' : '' ?>
                                       style="margin: 0; width: 17px; height: 17px; cursor: pointer;">
                                <span style="margin-left: 10px;"><?= esc($g['title']) ?></span>
                            </label>

                            <span class="group-counter-badge" id="group-counter-<?= esc($g['key']) ?>" style="padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">
                                <?= (int)$g['granted_count'] ?>/<?= (int)$g['total_count'] ?>
                            </span>
                        </div>

                        <!-- Master Card Body: Sub-groups -->
                        <div class="module-card-body" style="padding: 16px 18px;">
                            <?php foreach ($g['subgroups'] as $sgKey => $sg): ?>
                                <!-- SUBGROUP CARD (Green vertical accent bar) -->
                                <div class="subgroup-card" 
                                     id="subgroup-card-<?= esc($g['key']) ?>-<?= esc($sg['key']) ?>"
                                     data-group="<?= esc($g['key']) ?>"
                                     data-subgroup="<?= esc($sg['key']) ?>"
                                     style="border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 14px; background: #ffffff; overflow: hidden;">
                                    
                                    <!-- Subgroup Header -->
                                    <div class="subgroup-header" style="border-left: 3px solid #059669; padding: 10px 14px; background: #fafbfc; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                                        <label style="display: flex; align-items: center; margin: 0; cursor: pointer; font-weight: 600; font-size: 14px; color: #1e293b;">
                                            <input type="checkbox" 
                                                   class="subgroup-master-checkbox" 
                                                   data-group="<?= esc($g['key']) ?>" 
                                                   data-subgroup="<?= esc($sg['key']) ?>"
                                                   <?= ($sg['granted_count'] === $sg['total_count'] && $sg['total_count'] > 0) ? 'checked' : '' ?>
                                                   <?= $isSuperAdminRole ? 'disabled' : '' ?>
                                                   style="margin: 0; width: 16px; height: 16px; cursor: pointer;">
                                            <span style="margin-left: 10px;"><?= esc($sg['title']) ?></span>
                                        </label>

                                        <span class="subgroup-counter-badge" id="subgroup-counter-<?= esc($g['key']) ?>-<?= esc($sg['key']) ?>" style="padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">
                                            <?= (int)$sg['granted_count'] ?>/<?= (int)$sg['total_count'] ?>
                                        </span>
                                    </div>

                                    <!-- Subgroup Permissions Checklist -->
                                    <div class="subgroup-permissions" style="padding: 8px 16px;">
                                        <?php foreach ($sg['permissions'] as $p): ?>
                                            <div class="perm-row" 
                                                 data-code="<?= esc(strtolower($p['permission_code'])) ?>" 
                                                 data-name="<?= esc(strtolower($p['permission_name'])) ?>"
                                                 style="display: flex; align-items: center; padding: 7px 0; border-bottom: 1px solid #f8fafc;">
                                                <label style="display: flex; align-items: center; margin: 0; cursor: pointer; font-weight: 400; font-size: 13px; color: #334155; width: 100%;">
                                                    <input type="checkbox" 
                                                           name="permissions[]" 
                                                           value="<?= esc($p['permission_code']) ?>" 
                                                           class="perm-checkbox" 
                                                           data-group="<?= esc($g['key']) ?>" 
                                                           data-subgroup="<?= esc($sg['key']) ?>"
                                                           <?= !empty($p['is_granted']) ? 'checked' : '' ?>
                                                           <?= $isSuperAdminRole ? 'disabled' : '' ?>
                                                           style="margin: 0; width: 15px; height: 15px; cursor: pointer;">
                                                    <span style="margin-left: 10px;"><?= esc($p['permission_name']) ?></span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    let hasUnsavedChanges = false;
    const isSuperAdmin = <?= $isSuperAdminRole ? 'true' : 'false' ?>;

    // Helper to update unsaved changes indicator
    function markUnsaved() {
        if (isSuperAdmin) return;
        hasUnsavedChanges = true;
        $('#saveStatusBadge')
            .removeClass('sw-badge-success')
            .addClass('sw-badge-warning')
            .css({'background-color': '#fffbeb', 'color': '#b45309', 'border': '1px solid #fde68a'});
        $('#saveStatusBadge i').removeClass('icon-checkmark4').addClass('icon-primitive-dot');
        $('#saveStatusText').text('Unsaved Changes');
    }

    function markSaved() {
        hasUnsavedChanges = false;
        $('#saveStatusBadge')
            .removeClass('sw-badge-warning')
            .addClass('sw-badge-success')
            .css({'background-color': '', 'color': '', 'border': ''});
        $('#saveStatusBadge i').removeClass('icon-primitive-dot').addClass('icon-checkmark4');
        $('#saveStatusText').text('Changes Saved');
    }

    // Update all counters and master checkbox states across the hierarchy
    function updateHierarchyStates() {
        let globalGranted = 0;

        // Iterate over each module card
        $('.module-card').each(function() {
            const groupKey = $(this).data('group');
            let groupGranted = 0;
            let groupTotal = 0;

            // Iterate over each subgroup card inside this module
            $(this).find('.subgroup-card').each(function() {
                const sgKey = $(this).data('subgroup');
                const permCheckboxes = $(this).find('.perm-checkbox');
                const sgTotal = permCheckboxes.length;
                let sgGranted = 0;

                permCheckboxes.each(function() {
                    if ($(this).is(':checked')) {
                        sgGranted++;
                    }
                });

                groupGranted += sgGranted;
                groupTotal += sgTotal;

                // Update subgroup counter badge
                $('#subgroup-counter-' + groupKey + '-' + sgKey).text(sgGranted + '/' + sgTotal);

                // Update subgroup master checkbox state
                const sgMaster = $(this).find('.subgroup-master-checkbox');
                if (sgGranted === sgTotal && sgTotal > 0) {
                    sgMaster.prop('checked', true).prop('indeterminate', false);
                } else if (sgGranted === 0) {
                    sgMaster.prop('checked', false).prop('indeterminate', false);
                } else {
                    sgMaster.prop('checked', false).prop('indeterminate', true);
                }
            });

            globalGranted += groupGranted;

            // Update master module counter badge
            $('#group-counter-' + groupKey).text(groupGranted + '/' + groupTotal);

            // Update left sidebar nav badge
            $('#nav-badge-' + groupKey).text(groupGranted + '/' + groupTotal);

            // Update master group checkbox state
            const groupMaster = $(this).find('.group-master-checkbox');
            if (groupGranted === groupTotal && groupTotal > 0) {
                groupMaster.prop('checked', true).prop('indeterminate', false);
            } else if (groupGranted === 0) {
                groupMaster.prop('checked', false).prop('indeterminate', false);
            } else {
                groupMaster.prop('checked', false).prop('indeterminate', true);
            }
        });

        $('#totalGrantedDisplay').text(globalGranted);
    }

    // Initial pass on page load
    updateHierarchyStates();

    // 1. Group Master Checkbox Click
    $('.group-master-checkbox').on('change', function() {
        if (isSuperAdmin) return;
        const isChecked = $(this).is(':checked');
        const card = $(this).closest('.module-card');
        card.find('.subgroup-master-checkbox').prop('checked', isChecked).prop('indeterminate', false);
        card.find('.perm-checkbox').prop('checked', isChecked);
        markUnsaved();
        updateHierarchyStates();
    });

    // 2. Subgroup Master Checkbox Click
    $('.subgroup-master-checkbox').on('change', function() {
        if (isSuperAdmin) return;
        const isChecked = $(this).is(':checked');
        const sgCard = $(this).closest('.subgroup-card');
        sgCard.find('.perm-checkbox').prop('checked', isChecked);
        markUnsaved();
        updateHierarchyStates();
    });

    // 3. Individual Permission Checkbox Click
    $('.perm-checkbox').on('change', function() {
        if (isSuperAdmin) return;
        markUnsaved();
        updateHierarchyStates();
    });

    // 4. Global Select All / Deselect All
    $('#btnSelectAllGlobal').on('click', function() {
        if (isSuperAdmin) return;
        $('.perm-checkbox, .subgroup-master-checkbox, .group-master-checkbox').prop('checked', true).prop('indeterminate', false);
        markUnsaved();
        updateHierarchyStates();
    });

    $('#btnDeselectAllGlobal').on('click', function() {
        if (isSuperAdmin) return;
        $('.perm-checkbox, .subgroup-master-checkbox, .group-master-checkbox').prop('checked', false).prop('indeterminate', false);
        markUnsaved();
        updateHierarchyStates();
    });

    // 5. Left Nav Item Click: Smooth scroll to target card and set active
    $('.module-nav-item').on('click', function() {
        $('.module-nav-item').css({
            'background': 'transparent',
            'border-left': '3px solid transparent',
            'color': '#334155',
            'font-weight': '500'
        });

        $(this).css({
            'background': '#ecfdf5',
            'border-left': '3px solid #059669',
            'color': '#065f46',
            'font-weight': '600'
        });

        const targetId = $(this).data('target');
        const targetEl = document.getElementById(targetId);
        if (targetEl) {
            $('html, body').animate({
                scrollTop: $(targetEl).offset().top - 90
            }, 300);
        }
    });

    // 6. Target Switcher (Switch role or user - instant seamless switch without alerts)
    $('#targetSelector').on('change', function() {
        const val = $(this).val();
        if (val.startsWith('role_')) {
            const roleId = val.replace('role_', '');
            window.location.href = '<?= base_url('permission/assign') ?>/' + roleId;
        } else if (val.startsWith('user_')) {
            const userId = val.replace('user_', '');
            window.location.href = '<?= base_url('permission/assign') ?>?user_id=' + userId;
        }
    });

    // 7. Live Real-time Permission Search
    $('#permissionSearch').on('input keyup', function() {
        const term = $(this).val().toLowerCase().trim();

        if (term === '') {
            $('.perm-row').show();
            $('.subgroup-card').show();
            $('.module-card').show();
            $('.module-nav-item').show();
            return;
        }

        $('.module-card').each(function() {
            let cardHasMatch = false;

            $(this).find('.subgroup-card').each(function() {
                let sgHasMatch = false;

                $(this).find('.perm-row').each(function() {
                    const name = $(this).data('name') || '';
                    const code = $(this).data('code') || '';
                    if (name.includes(term) || code.includes(term)) {
                        $(this).show();
                        sgHasMatch = true;
                    } else {
                        $(this).hide();
                    }
                });

                if (sgHasMatch) {
                    $(this).show();
                    cardHasMatch = true;
                } else {
                    $(this).hide();
                }
            });

            const groupKey = $(this).data('group');
            if (cardHasMatch) {
                $(this).show();
                $('#nav-item-' + groupKey).show();
            } else {
                $(this).hide();
                $('#nav-item-' + groupKey).hide();
            }
        });
    });

    // 8. AJAX Save Permissions
    $('#btnSavePermissions').on('click', function(e) {
        e.preventDefault();
        if (isSuperAdmin) return;

        const form = $('#permissionAssignForm');
        const saveUrl = form.attr('action');
        const formData = form.serialize();

        const btn = $(this);
        const originalText = $('#saveBtnText').text();
        btn.prop('disabled', true);
        $('#saveBtnText').text('Saving...');

        $.ajax({
            url: saveUrl,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false);
                $('#saveBtnText').text(originalText);

                if (res.status) {
                    markSaved();
                    if (typeof swal !== 'undefined') {
                        swal({
                            title: 'Saved!',
                            text: res.message || 'Permissions have been updated and cached successfully.',
                            type: 'success',
                            confirmButtonColor: '#2563eb',
                            timer: 1800,
                            showConfirmButton: false
                        });
                    }
                } else {
                    if (typeof swal !== 'undefined') {
                        swal('Error', res.error || 'Failed to save permissions.', 'error');
                    }
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false);
                $('#saveBtnText').text(originalText);
                let msg = 'Failed to save permissions due to a server error.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                }
                if (typeof swal !== 'undefined') {
                    swal('Server Error', msg, 'error');
                }
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
