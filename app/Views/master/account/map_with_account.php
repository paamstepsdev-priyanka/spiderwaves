<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?><?= esc($title) ?> - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>
    <a href="<?= base_url('account') ?>" style="color: inherit; text-decoration: none;">Account</a> / Department Mapping
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1 style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a;">
            Department Transfer Route Mapping
        </h1>
        <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">
            Configure allowed outbound transfer destination departments for <strong><?= esc($account['account_name']) ?></strong>.
        </p>
    </div>
    <div class="sw-page-actions">
        <a href="<?= base_url('account') ?>" class="btn btn-default" style="padding: 6px 14px; font-weight: 600;">
            <i class="icon-arrow-left52 position-left"></i> Back to Accounts
        </a>
    </div>
</div>

<!-- Flash Alerts -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-styled-left alert-arrow-left alert-bordered" style="margin-bottom: 20px;">
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span><span class="sr-only">Close</span></button>
        <span class="text-semibold">Success!</span> <?= esc(session()->getFlashdata('success')) ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-styled-left alert-arrow-left alert-bordered" style="margin-bottom: 20px;">
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span><span class="sr-only">Close</span></button>
        <span class="text-semibold">Error!</span> <?= esc(session()->getFlashdata('error')) ?>
    </div>
<?php endif; ?>

<!-- Main Mapping Card -->
<div class="sw-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 30px;">
    <!-- Account Info Banner -->
    <div style="padding: 18px 24px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="display: inline-block; width: 12px; height: 12px; background-color: #2563eb; border-radius: 50%;"></span>
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">
                    <?= esc($account['account_name']) ?>
                </h3>
                <span style="font-size: 12px; color: #64748b;">
                    Type: <strong style="text-transform: capitalize; color: #334155;"><?= esc($account['account_type']) ?></strong>
                    <?php if (!empty($account['loss_type'])): ?>
                        | Loss: <strong><?= esc($account['loss_type']) ?></strong>
                    <?php endif; ?>
                    | Status: <strong class="<?= ($account['account_status'] === '1') ? 'text-success' : 'text-muted' ?>"><?= ($account['account_status'] === '1') ? 'Active' : 'Inactive' ?></strong>
                </span>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 8px;">
            <button type="button" class="btn btn-default btn-xs" onclick="selectAllCheckboxes(true);" style="font-weight: 600;">
                <i class="icon-checkbox-checked position-left text-primary"></i> Select All
            </button>
            <button type="button" class="btn btn-default btn-xs" onclick="selectAllCheckboxes(false);" style="font-weight: 600;">
                <i class="icon-checkbox-unchecked position-left text-muted"></i> Clear All
            </button>
        </div>
    </div>

    <form method="post" action="<?= base_url('master/account/update-mapping') ?>" id="mappingForm" novalidate style="padding: 24px;">
        <?= csrf_field() ?>
        <input type="hidden" name="private_key" value="<?= esc($account['private_key']) ?>">

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
            <span class="badge badge-primary" id="selectedBadgeCount" style="padding: 7px 12px; font-size: 12.5px; border-radius: 4px;">
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
                            <?php if ($isMapped): ?>
                                <i class="icon-checkmark-circle text-success" id="check_icon_<?= (int)$dept['account_id'] ?>" style="font-size: 14px;"></i>
                            <?php else: ?>
                                <i class="icon-checkmark-circle text-success" id="check_icon_<?= (int)$dept['account_id'] ?>" style="font-size: 14px; display: none;"></i>
                            <?php endif; ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-xs-12" style="text-align: center; padding: 40px; color: #94a3b8;">
                    No other active departments available to map.
                </div>
            <?php endif; ?>
        </div>

        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0 20px;">

        <!-- Actions -->
        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
            <a href="<?= base_url('master/account') ?>" class="btn btn-default" style="padding: 8px 20px; font-weight: 600;">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary" style="padding: 8px 24px; font-weight: 700; box-shadow: 0 2px 4px rgba(37,99,235,0.2);">
                <i class="icon-checkmark position-left"></i> Save Department Mappings
            </button>
        </div>
    </form>
</div>

<script>
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
</script>

<?= $this->endSection() ?>

