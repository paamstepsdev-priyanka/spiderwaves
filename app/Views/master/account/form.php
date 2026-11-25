<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?><?= esc($title) ?> - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>
    <a href="<?= base_url('master/account') ?>" style="color: inherit; text-decoration: none;">Account Master</a> / <?= esc($title) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
    $selectedOld = old('map_with_accounts');
    if ($selectedOld !== null && is_array($selectedOld)) {
        $mappedAccountIds = array_map('intval', $selectedOld);
    }
?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a;">
            <?= ($formType === 'Edit') ? 'Edit Process: ' . esc($account['account_name'] ?? '') : 'Create New Process' ?>
        </h1>
        <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">
            Configure process parameters, operational status, and assigned department transfer routes.
        </p>
    </div>
    <div class="sw-page-actions">
        <a href="<?= base_url('master/account') ?>" class="btn btn-default" style="padding: 6px 14px; font-weight: 600;">
            <i class="icon-arrow-left52 position-left"></i> Back to Accounts
        </a>
    </div>
</div>

<!-- Flash Alerts -->
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-styled-left alert-arrow-left alert-bordered" style="margin-bottom: 20px;">
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span><span class="sr-only">Close</span></button>
        <span class="text-semibold">Error!</span> <?= esc(session()->getFlashdata('error')) ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-styled-left alert-arrow-left alert-bordered" style="margin-bottom: 20px;">
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span><span class="sr-only">Close</span></button>
        <span class="text-semibold">Success!</span> <?= esc(session()->getFlashdata('success')) ?>
    </div>
<?php endif; ?>

<!-- Form Card -->
<div class="sw-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 30px;">
    <div style="padding: 18px 24px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="display: inline-block; width: 10px; height: 10px; background-color: #2563eb; border-radius: 50%;"></span>
            <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;">
                <?= ($formType === 'Edit') ? 'Edit Process Details: ' . esc($account['account_name'] ?? '') : 'New Process Information' ?>
            </h3>
        </div>
        <span style="font-size: 12px; color: #64748b; font-weight: 500;">
            <i class="icon-shield-check text-success"></i> Superadmin Control
        </span>
    </div>

    <form method="post" 
          action="<?= ($formType === 'Edit') ? base_url('master/account/update/' . $account['private_key']) : base_url('master/account/store') ?>" 
          id="accountForm" 
          novalidate
          style="padding: 24px;">
        <?= csrf_field() ?>
        <input type="hidden" name="form_type" value="<?= esc($formType) ?>">
        <?php if ($formType === 'Edit'): ?>
            <input type="hidden" name="private_key" value="<?= esc($account['private_key'] ?? '') ?>">
        <?php endif; ?>

        <!-- Row 1: Account Name & Type -->
        <div class="row">
            <div class="col-md-6 col-sm-12">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="account_name" style="font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                        Process Name <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="account_name" 
                           name="account_name" 
                           value="<?= esc(old('account_name', $account['account_name'] ?? '')) ?>" 
                           placeholder="e.g. Casting, Laser Marking, Polish, CNC..." 
                           required 
                           style="height: 40px; font-size: 13.5px; border-radius: 6px;">
                </div>
            </div>

            <div class="col-md-6 col-sm-12">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="account_type" style="font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                        Process Type <span class="text-danger">*</span>
                    </label>
                    <select class="form-control select2" 
                            id="account_type" 
                            name="account_type" 
                            required 
                            onchange="toggleLossTypeSection(this.value);"
                            style="width: 100%;">
                        <option value="process" <?= (old('account_type', $account['account_type'] ?? 'process') === 'process') ? 'selected' : '' ?>>
                            Process (Manufacturing Workshop Stage)
                        </option>
                        <option value="stock" <?= (old('account_type', $account['account_type'] ?? '') === 'stock') ? 'selected' : '' ?>>
                            Stock (Locker / Vault)
                        </option>
                        <option value="loss" <?= (old('account_type', $account['account_type'] ?? '') === 'loss') ? 'selected' : '' ?>>
                            Loss Stage
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Hidden Loss Percentage to preserve existing data without showing input -->
        <input type="hidden" id="loss_percentage" name="loss_percentage" value="<?= esc(old('loss_percentage', $account['loss_percentage'] ?? '0')) ?>">

        <!-- Row 2: Account Status & Optional Loss Type -->
        <div class="row">
            <div class="col-md-6 col-sm-12">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label style="font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">
                        Account Status
                    </label>
                    <div style="display: flex; gap: 20px; align-items: center; height: 40px;">
                        <label class="radio-inline" style="font-weight: 500; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" 
                                   name="account_status" 
                                   value="1" 
                                   <?= (old('account_status', $account['account_status'] ?? '1') === '1') ? 'checked' : '' ?>
                                   style="width: 16px; height: 16px; margin: 0; cursor: pointer; accent-color: #16a34a;">
                            <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 68px; height: 23px; padding: 0 10px; font-size: 11px; font-weight: 700; border-radius: 9999px; background-color: #16a34a; color: #ffffff; text-transform: uppercase; letter-spacing: 0.3px; line-height: 1; white-space: nowrap;">ACTIVE</span>
                        </label>
                        <label class="radio-inline" style="font-weight: 500; color: #64748b; margin: 0; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="radio" 
                                   name="account_status" 
                                   value="0" 
                                   <?= (old('account_status', $account['account_status'] ?? '') === '0') ? 'checked' : '' ?>
                                   style="width: 16px; height: 16px; margin: 0; cursor: pointer; accent-color: #dc2626;">
                            <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 68px; height: 23px; padding: 0 10px; font-size: 11px; font-weight: 700; border-radius: 9999px; background-color: #dc2626; color: #ffffff; text-transform: uppercase; letter-spacing: 0.3px; line-height: 1; white-space: nowrap;">INACTIVE</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-sm-12" id="lossTypeCol" style="display: <?= (old('account_type', $account['account_type'] ?? '') === 'loss') ? 'block' : 'none' ?>;">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="loss_type" style="font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">
                        Loss Type
                    </label>
                    <select class="form-control select2" id="loss_type" name="loss_type" style="width: 100%;">
                        <option value="">Select Loss Type</option>
                        <option value="recoverable_loss" <?= (old('loss_type', $account['loss_type'] ?? '') === 'recoverable_loss') ? 'selected' : '' ?>>
                            Recoverable Loss
                        </option>
                        <option value="net_loss" <?= (old('loss_type', $account['loss_type'] ?? '') === 'net_loss') ? 'selected' : '' ?>>
                            Net Loss
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section 2: Department Transfer Route Mapping Card -->
        <div class="sw-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-top: 25px; margin-bottom: 25px;">
            <!-- Header Banner -->
            <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-block; width: 10px; height: 10px; background-color: #2563eb; border-radius: 50%;"></span>
                    <div>
                        <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;">
                            Department Transfer Route Mapping
                        </h3>
                        <span style="font-size: 12px; color: #64748b;">
                            Configure allowed outbound transfer destination departments for this process.
                        </span>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" class="btn btn-default btn-xs" onclick="selectAllCheckboxes(true);" style="font-weight: 600; padding: 5px 10px; border-radius: 4px;">
                        <i class="icon-checkbox-checked position-left text-primary" style="margin-right: 4px;"></i> Select All
                    </button>
                    <button type="button" class="btn btn-default btn-xs" onclick="selectAllCheckboxes(false);" style="font-weight: 600; padding: 5px 10px; border-radius: 4px;">
                        <i class="icon-checkbox-unchecked position-left text-muted" style="margin-right: 4px;"></i> Clear All
                    </button>
                </div>
            </div>

            <!-- Card Body with Filter & Grid -->
            <div style="padding: 20px;">
                <!-- Search / Quick Filter Bar -->
                <div style="margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                    <div style="flex: 1; position: relative;">
                        <input type="text" 
                               id="deptSearchInput" 
                               class="form-control" 
                               placeholder="Type to filter departments below..." 
                               oninput="filterDepartmentGrid(this.value);"
                               style="height: 38px; border-radius: 6px; padding-left: 36px;">
                        <i class="icon-search4 text-muted" style="position: absolute; left: 12px; top: 12px; font-size: 14px;"></i>
                    </div>
                    <span class="badge" id="selectedBadgeCount" style="padding: 7px 14px; font-size: 12px; font-weight: 700; border-radius: 4px; background-color: #16a34a; color: #ffffff; white-space: nowrap;">
                        <?= count($mappedAccountIds) ?> Mapped
                    </span>
                </div>

                <!-- Checkbox Grid of Available Departments -->
                <div class="row" id="departmentGrid" style="margin-left: -8px; margin-right: -8px;">
                    <?php if (!empty($availableDepartments)): ?>
                        <?php foreach ($availableDepartments as $dept): ?>
                            <?php 
                                $isMapped = in_array((int)$dept['account_id'], $mappedAccountIds, true);
                            ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 dept-item" 
                                 data-name="<?= esc(strtolower($dept['account_name'])) ?>"
                                 style="padding: 6px 8px;">
                                <label style="display: flex; align-items: center; gap: 10px; background: <?= $isMapped ? '#f0fdf4' : '#f8fafc' ?>; border: 1px solid <?= $isMapped ? '#86efac' : '#e2e8f0' ?>; padding: 10px 14px; border-radius: 6px; cursor: pointer; margin: 0; transition: all 0.15s ease-in-out;"
                                       class="dept-card-label"
                                       id="label_dept_<?= (int)$dept['account_id'] ?>">
                                    <input type="checkbox" 
                                           name="map_with_accounts[]" 
                                           value="<?= (int)$dept['account_id'] ?>" 
                                           class="dept-checkbox"
                                           <?= $isMapped ? 'checked="checked"' : '' ?>
                                           onchange="onDeptCheckboxChange(this, <?= (int)$dept['account_id'] ?>);"
                                           style="width: 17px; height: 17px; cursor: pointer; accent-color: #2563eb;">
                                    <div style="flex: 1; overflow: hidden;">
                                        <span style="display: block; font-weight: 600; color: #0f172a; font-size: 13px; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                                            <?= esc($dept['account_name']) ?>
                                        </span>
                                        <span style="font-size: 11px; color: #64748b; text-transform: capitalize;">
                                            <?= esc($dept['account_type']) ?> (ID: <?= (int)$dept['account_id'] ?>)
                                        </span>
                                    </div>
                                    <i class="icon-checkmark-circle text-success" id="check_icon_<?= (int)$dept['account_id'] ?>" style="font-size: 14px; <?= $isMapped ? '' : 'display: none;' ?>"></i>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-xs-12" style="text-align: center; padding: 40px; color: #94a3b8;">
                            No other active departments available to map.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Form Submission Actions -->
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px; padding-top: 10px;">
            <a href="<?= base_url('master/account') ?>" class="btn btn-default" style="padding: 8px 20px; font-weight: 600;">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary" style="padding: 8px 24px; font-weight: 700; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">
                <i class="icon-checkmark position-left"></i>
                <?= ($formType === 'Edit') ? 'Update Process' : 'Save Process' ?>
            </button>
        </div>
    </form>
</div>

<script>
function toggleLossTypeSection(accountType) {
    var lossCol = document.getElementById('lossTypeCol');
    if (lossCol) {
        lossCol.style.display = (accountType === 'loss') ? 'block' : 'none';
    }
}

function onDeptCheckboxChange(checkbox, id) {
    var label = document.getElementById('label_dept_' + id);
    var icon = document.getElementById('check_icon_' + id);
    if (checkbox.checked) {
        if (label) {
            label.style.background = '#f0fdf4';
            label.style.borderColor = '#86efac';
        }
        if (icon) icon.style.display = 'inline-block';
    } else {
        if (label) {
            label.style.background = '#f8fafc';
            label.style.borderColor = '#e2e8f0';
        }
        if (icon) icon.style.display = 'none';
    }
    updateSelectedCount();
}

function updateSelectedCount() {
    var checked = document.querySelectorAll('.dept-checkbox:checked');
    var badge = document.getElementById('selectedBadgeCount');
    if (badge) {
        badge.textContent = checked.length + ' Mapped';
    }
}

function selectAllCheckboxes(state) {
    var checkboxes = document.querySelectorAll('.dept-checkbox');
    checkboxes.forEach(function(cb) {
        cb.checked = state;
        var id = cb.value;
        var label = document.getElementById('label_dept_' + id);
        var icon = document.getElementById('check_icon_' + id);
        if (state) {
            if (label) {
                label.style.background = '#f0fdf4';
                label.style.borderColor = '#86efac';
            }
            if (icon) icon.style.display = 'inline-block';
        } else {
            if (label) {
                label.style.background = '#f8fafc';
                label.style.borderColor = '#e2e8f0';
            }
            if (icon) icon.style.display = 'none';
        }
    });
    updateSelectedCount();
}

function filterDepartmentGrid(keyword) {
    var term = keyword.toLowerCase().trim();
    var items = document.querySelectorAll('.dept-item');
    items.forEach(function(item) {
        var name = item.getAttribute('data-name') || '';
        if (term === '' || name.indexOf(term) !== -1) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

$(document).ready(function() {
    updateSelectedCount();
    $('#account_type').on('change', function() {
        toggleLossTypeSection($(this).val());
    });
});
</script>

<?= $this->endSection() ?>

