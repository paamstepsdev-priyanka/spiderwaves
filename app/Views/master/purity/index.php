<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Purity Master - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Purity Master<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Purity Master</h1>
    </div>
    <div class="sw-page-actions">
        <span class="badge badge-primary"><i class="icon-database"></i> UCC Master (Read-Only)</span>
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
    <form method="get" action="<?= base_url('master/purity') ?>" id="purityFilterForm">
        <div class="sw-filter-grid">
            <!-- Search Keyword Input -->
            <div class="sw-search-group" style="flex: 2;">
                <input type="text" name="q" class="sw-input" style="padding-left: 14px;" placeholder="Search by purity name or percentage value..." value="<?= esc($search ?? '') ?>" autocomplete="off">
            </div>

            <!-- Status Dropdown -->
            <div style="flex: 1; min-width: 120px;">
                <select name="status" class="select2" onchange="document.getElementById('purityFilterForm').submit()" style="width: 100%;">
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

            <?php if (!empty($search) || ($status !== null && $status !== '')): ?>
                <a href="<?= base_url('master/purity') ?>" class="sw-btn-secondary" title="Reset Filters" style="color: #64748b; padding: 0 12px;">
                    <i class="icon-cross2"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Purity Data Table Card -->
<div class="sw-table-card">
    <div class="sw-table-header">
        <div class="sw-table-title">Purity Records</div>
        <div class="sw-table-info">
            Showing <?= count($purities) ?> records
        </div>
    </div>

    <div class="sw-table-responsive">
        <table class="sw-table">
            <thead>
                <tr>
                    <th style="width: 8%; text-align: center;">S.No.</th>
                    <th style="width: 42%;">Purity Name / Label</th>
                    <th style="width: 25%; text-align: center;">Purity Value (%)</th>
                    <th style="width: 12%; text-align: center;">Status</th>
                    <th style="width: 13%; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($purities)): ?>
                    <?php
                        $currentPage = $pager->getCurrentPage() ?: 1;
                        $perPage     = $pager->getPerPage() ?: 10;
                        $index       = ($currentPage - 1) * $perPage + 1;
                    ?>
                    <?php foreach ($purities as $obj): ?>
                        <tr>
                            <!-- S.No -->
                            <td style="text-align: center; color: var(--sw-text-muted); font-size: 12.5px;">
                                <?= $index++; ?>
                            </td>

                            <!-- Purity Name -->
                            <td>
                                <div style="font-size: 13.5px; font-weight: 700; color: var(--sw-text-main); line-height: 1.25;">
                                    <?= esc($obj['purity_name']) ?>
                                </div>
                            </td>

                            <!-- Purity Value (%) -->
                            <td style="text-align: center;">
                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 12px; border-radius: 4px; font-size: 13px; font-weight: 700; background: #f1f5f9; color: #0f172a; border: 1px solid #e2e8f0; font-family: monospace;">
                                    <?= number_format((float)$obj['purity_value'], 3) ?>%
                                </span>
                            </td>

                            <!-- Status Pill Badge -->
                            <td style="text-align: center;">
                                <?php if ($obj['purity_status'] === '1'): ?>
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
                                <span class="text-muted"><i class="icon-lock2"></i> Read-Only</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 48px 20px;">
                            <div style="font-size: 36px; color: var(--sw-text-subtle); margin-bottom: 12px;">
                                <i class="icon-search4"></i>
                            </div>
                            <h4 style="font-size: 16px; font-weight: 600; color: var(--sw-text-main); margin-bottom: 4px;">
                                No purities found
                            </h4>
                            <p style="color: var(--sw-text-muted); font-size: 13px; max-width: 400px; margin: 0 auto 16px auto;">
                                Try adjusting your search query or filter parameters.
                            </p>
                            <a href="<?= base_url('master/purity') ?>" class="sw-btn-secondary" style="display: inline-flex;">
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
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="text/javascript">
$(document).ready(function() {
    $('.sw-confirm-toggle').on('click', function(e) {
        e.preventDefault();
        var url    = $(this).attr('href');
        var action = $(this).data('action') || 'Toggle';
        var name   = $(this).data('name') || 'this purity';
        var isDeact = (action.toLowerCase() === 'deactivate');

        swal({
            title: action + " Purity?",
            text: "Are you sure you want to " + action.toLowerCase() + " '" + name + "'?",
            type: isDeact ? "warning" : "info",
            showCancelButton: true,
            confirmButtonColor: isDeact ? "#ef4444" : "#2563eb",
            confirmButtonText: "Yes, " + action + "!",
            cancelButtonText: "Cancel",
            closeOnConfirm: false
        }, function(isConfirm) {
            if (isConfirm) {
                window.location.href = url;
            }
        });
    });
});
</script>
<?= $this->endSection() ?>

