<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Customer Master - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Customer Master<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Customer Master</h1>
    </div>
    <div class="sw-page-actions">
        <?= view('master/_ucc_header_badges', ['totalAll' => $totalAll ?? null, 'total' => $total ?? 0, 'hasFilters' => $hasFilters ?? false]) ?>
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
    <form method="get" action="<?= base_url('master/customer') ?>" id="customerFilterForm">
        <div class="sw-filter-grid">
            <!-- Search Keyword Input -->
            <div class="sw-search-group">
                <input type="text" name="q" class="sw-input" style="padding-left: 14px;" placeholder="Search customer..." value="<?= esc($search ?? '') ?>" autocomplete="off">
            </div>

            <!-- Type Filter Dropdown -->
            <div style="flex: 1; min-width: 120px;">
                <select name="type" class="select2" onchange="document.getElementById('customerFilterForm').submit()" style="width: 100%;">
                    <option value="">All Types</option>
                    <option value="cust" <?= ($type === 'cust') ? 'selected' : '' ?>>Customer</option>
                    <option value="vendor" <?= ($type === 'vendor') ? 'selected' : '' ?>>Vendor</option>
                </select>
            </div>

            <!-- Status Filter Dropdown -->
            <div style="flex: 1; min-width: 120px;">
                <select name="status" class="select2" onchange="document.getElementById('customerFilterForm').submit()" style="width: 100%;">
                    <option value="">All Statuses</option>
                    <option value="1" <?= ($status === '1') ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= ($status === '0') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <!-- Sort Dropdown -->
            <div style="flex: 1; min-width: 140px;">
                <select name="sort" class="select2" onchange="document.getElementById('customerFilterForm').submit()" style="width: 100%;">
                    <option value="">Sort: Default</option>
                    <option value="name_asc" <?= (($sort ?? '') === 'name_asc') ? 'selected' : '' ?>>Name (A-Z)</option>
                    <option value="name_desc" <?= (($sort ?? '') === 'name_desc') ? 'selected' : '' ?>>Name (Z-A)</option>
                    <option value="id_desc" <?= (($sort ?? '') === 'id_desc') ? 'selected' : '' ?>>ID (High-Low)</option>
                    <option value="id_asc" <?= (($sort ?? '') === 'id_asc') ? 'selected' : '' ?>>ID (Low-High)</option>
                    <option value="status_asc" <?= (($sort ?? '') === 'status_asc') ? 'selected' : '' ?>>Active First</option>
                </select>
            </div>

            <!-- Search Action Button -->
            <button type="submit" class="sw-btn-primary" style="padding: 10px 18px;">
                <i class="icon-filter4"></i>
                <span>Search</span>
            </button>

            <!-- Reset Button (if filtering active) -->
            <?php if (!empty($search) || !empty($type) || ($status !== null && $status !== '') || !empty($sort)): ?>
                <a href="<?= base_url('master/customer') ?>" class="sw-btn-secondary" title="Clear Filters">
                    <i class="icon-cross2"></i>
                    <span>Reset</span>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Customer Table Card -->
<div class="sw-table-card">
    <div class="sw-table-responsive">
        <table class="sw-table">
            <thead>
                <tr>
                    <th style="width: 8%; text-align: center;">#</th>
                    <th style="width: 52%;">Customer Name</th>
                    <th style="width: 20%;">Type</th>
                    <th style="width: 20%;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($customers)): ?>
                    <?php foreach ($customers as $obj): ?>
                        <tr>
                            <!-- Primary Key ID -->
                            <td style="text-align: center; color: var(--sw-text-muted); font-size: 13px;">
                                #<?= (int)$obj['cust_id'] ?>
                            </td>

                            <!-- Customer Name -->
                            <td>
                                <div class="sw-cust-name"><?= esc($obj['cust_name']) ?></div>
                            </td>

                            <!-- Type Pill Badge -->
                            <td>
                                <?php if ($obj['type'] === 'cust'): ?>
                                    <span class="sw-pill-badge customer">
                                        <span class="sw-badge-dot"></span> Customer
                                    </span>
                                <?php else: ?>
                                    <span class="sw-pill-badge vendor">
                                        <span class="sw-badge-dot"></span> Vendor
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Status Pill Badge -->
                            <td>
                                <?php if ($obj['cust_status'] === '1'): ?>
                                    <span class="sw-pill-badge active">
                                        <span class="sw-badge-dot"></span> Active
                                    </span>
                                <?php else: ?>
                                    <span class="sw-pill-badge inactive">
                                        <span class="sw-badge-dot"></span> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 48px 20px; color: var(--sw-text-muted);">
                            <i class="icon-search4" style="font-size: 32px; color: var(--sw-text-subtle); display: block; margin-bottom: 8px;"></i>
                            <div style="font-weight: 600; font-size: 14px;">No customer records found</div>
                            <div style="font-size: 12px; margin-top: 4px;">Try modifying your keyword search or filter criteria</div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Footer with Summary Counter and Pagination -->
    <div class="sw-table-footer">
        <div style="font-size: 13px; color: var(--sw-text-muted);">
            Showing page <strong><?= $pager->getCurrentPage() ?></strong> of <strong><?= max(1, $pager->getPageCount()) ?></strong>
            (<strong><?= (int)($total ?? $pager->getTotal()) ?></strong> total records)
        </div>
        <div>
            <?= $pager->links('default', 'modern') ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
