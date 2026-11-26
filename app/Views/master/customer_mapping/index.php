<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Mapped Customer - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Mapped Customer<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Mapped Customer</h1>
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
    <form method="get" action="<?= base_url('master/customer-mapping') ?>" id="mappingFilterForm">
        <div class="sw-filter-grid">
            <!-- Search Keyword Input -->
            <div class="sw-search-group" style="flex: 2;">
                <input type="text" name="q" class="sw-input" style="padding-left: 14px;" placeholder="Search by mapped code or customer name..." value="<?= esc($search ?? '') ?>" autocomplete="off">
            </div>

            <!-- Customer Filter Dropdown -->
            <div style="flex: 1.5; min-width: 170px;">
                <select name="cust_id" class="select2" onchange="document.getElementById('mappingFilterForm').submit()" style="width: 100%;">
                    <option value="">All Customers</option>
                    <?php if (!empty($activeCustomers)): ?>
                        <?php foreach ($activeCustomers as $c): ?>
                            <option value="<?= $c['cust_id'] ?>" <?= ((int)($custId ?? 0) === (int)$c['cust_id']) ? 'selected' : '' ?>>
                                <?= esc($c['cust_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Collection Filter Dropdown -->
            <div style="flex: 1.5; min-width: 170px;">
                <select name="collection_id" class="select2" onchange="document.getElementById('mappingFilterForm').submit()" style="width: 100%;">
                    <option value="">All Collections</option>
                    <?php if (!empty($activeCollections)): ?>
                        <?php foreach ($activeCollections as $coll): ?>
                            <option value="<?= $coll['collection_id'] ?>" <?= ((int)($collectionId ?? 0) === (int)$coll['collection_id']) ? 'selected' : '' ?>>
                                <?= esc($coll['collection_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Status Filter Dropdown -->
            <div style="flex: 1; min-width: 110px;">
                <select name="status" class="select2" onchange="document.getElementById('mappingFilterForm').submit()" style="width: 100%;">
                    <option value="">All Statuses</option>
                    <option value="1" <?= ($status === '1') ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= ($status === '0') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <!-- Sort Dropdown -->
            <div style="flex: 1.2; min-width: 145px;">
                <select name="sort" class="select2" onchange="document.getElementById('mappingFilterForm').submit()" style="width: 100%;">
                    <option value="">Sort: Default</option>
                    <option value="mapped_asc" <?= (($sort ?? '') === 'mapped_asc') ? 'selected' : '' ?>>Mapped Name (A-Z)</option>
                    <option value="mapped_desc" <?= (($sort ?? '') === 'mapped_desc') ? 'selected' : '' ?>>Mapped Name (Z-A)</option>
                    <option value="cust_asc" <?= (($sort ?? '') === 'cust_asc') ? 'selected' : '' ?>>Customer (A-Z)</option>
                    <option value="cust_desc" <?= (($sort ?? '') === 'cust_desc') ? 'selected' : '' ?>>Customer (Z-A)</option>
                    <option value="purity_asc" <?= (($sort ?? '') === 'purity_asc') ? 'selected' : '' ?>>Purity (Low-High)</option>
                    <option value="purity_desc" <?= (($sort ?? '') === 'purity_desc') ? 'selected' : '' ?>>Purity (High-Low)</option>
                    <option value="id_desc" <?= (($sort ?? '') === 'id_desc') ? 'selected' : '' ?>>ID (High-Low)</option>
                    <option value="id_asc" <?= (($sort ?? '') === 'id_asc') ? 'selected' : '' ?>>ID (Low-High)</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <button type="submit" class="sw-btn-secondary" style="padding: 0 16px;">
                <i class="icon-filter4"></i>
                <span>Filter</span>
            </button>

            <?php if (!empty($search) || !empty($custId) || !empty($collectionId) || ($status !== null && $status !== '') || !empty($sort)): ?>
                <a href="<?= base_url('master/customer-mapping') ?>" class="sw-btn-secondary" title="Reset Filters" style="color: #64748b; padding: 0 12px;">
                    <i class="icon-cross2"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Mapping Data Table Card -->
<div class="sw-table-card">
    <div class="sw-table-header">
        <div class="sw-table-title">Mapped Customer Records</div>
        <div class="sw-table-info">
            Showing <?= count($mappings) ?> records
        </div>
    </div>

    <div class="sw-table-responsive">
        <table class="sw-table">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">S.No.</th>
                    <th style="width: 18%;">Mapped Name</th>
                    <th style="width: 20%;">Private Key</th>
                    <th style="width: 19%;">Customer Name</th>
                    <th style="width: 10%; text-align: center;">Purity (%)</th>
                    <th style="width: 12%;">Collection</th>
                    <th style="width: 8%; text-align: center;">Status</th>
                    <th style="width: 8%; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($mappings)): ?>
                    <?php
                        $currentPage = $pager->getCurrentPage() ?: 1;
                        $perPage     = $pager->getPerPage() ?: 10;
                        $index       = ($currentPage - 1) * $perPage + 1;
                    ?>
                    <?php foreach ($mappings as $obj): ?>
                        <tr>
                            <!-- S.No -->
                            <td style="text-align: center; color: var(--sw-text-muted); font-size: 12.5px;">
                                <?= $index++; ?>
                            </td>

                            <!-- Mapped Name -->
                            <td>
                                <div style="font-size: 13px; font-weight: 700; color: var(--sw-text-main); line-height: 1.25;">
                                    <?= esc($obj['mapped_cust_name']) ?>
                                </div>
                            </td>

                            <!-- Private Key -->
                            <td>
                                <code style="font-size: 11px;"><?= esc($obj['private_key'] ?? '-') ?></code>
                            </td>

                            <!-- Customer Name -->
                            <td>
                                <div style="font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.25;">
                                    <?= esc($obj['cust_name'] ?? '—') ?>
                                </div>
                                <div style="font-size: 11px; color: var(--sw-text-muted); line-height: 1; margin-top: 1px;">
                                    Cust ID #<?= (int)$obj['cust_id'] ?>
                                </div>
                            </td>

                            <!-- Purity (%) -->
                            <td style="text-align: center;">
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 4px; background: #eff6ff; color: #1d4ed8; font-weight: 700; font-size: 12px; border: 1px solid #bfdbfe;">
                                    <?= number_format((float)$obj['purity'], 2, '.', '') ?>%
                                </span>
                            </td>

                            <!-- Collection -->
                            <td>
                                <span style="font-size: 12.5px; font-weight: 500; color: #334155;">
                                    <i class="icon-stack2" style="font-size: 11px; color: #94a3b8; margin-right: 4px;"></i>
                                    <?= esc($obj['collection_name'] ?? '—') ?>
                                </span>
                            </td>

                            <!-- Status Pill Badge -->
                            <td style="text-align: center;">
                                <?php if ($obj['mapped_cust_status'] === '1'): ?>
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
                        <td colspan="8" style="text-align: center; padding: 48px 20px;">
                            <div style="font-size: 36px; color: var(--sw-text-subtle); margin-bottom: 12px;">
                                <i class="icon-search4"></i>
                            </div>
                            <h4 style="font-size: 16px; font-weight: 600; color: var(--sw-text-main); margin-bottom: 4px;">
                                No mapped customers found
                            </h4>
                            <p style="color: var(--sw-text-muted); font-size: 13px; max-width: 400px; margin: 0 auto 16px auto;">
                                Try adjusting your search query, collection, or customer filter parameters.
                            </p>
                            <a href="<?= base_url('master/customer-mapping') ?>" class="sw-btn-secondary" style="display: inline-flex;">
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

<!-- Excel Bulk Import Modal -->
<div class="modal fade" id="importExcelModal" tabindex="-1" role="dialog" aria-labelledby="importExcelModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: var(--sw-radius); border: 1px solid var(--sw-border); box-shadow: var(--sw-shadow-lg);">
            <form action="<?= base_url('master/customer-mapping/import') ?>" method="post" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>
                <div class="modal-header" style="border-bottom: 1px solid var(--sw-border); padding: 18px 24px;">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 22px;">&times;</button>
                    <h4 class="modal-title" id="importExcelModalLabel" style="font-weight: 700; color: var(--sw-text-main);">
                        <i class="icon-file-excel text-success" style="margin-right: 8px;"></i> Import Mapped Customers via Excel
                    </h4>
                </div>
                <div class="modal-body" style="padding: 24px;">
                    <!-- Instructions Card -->
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                        <div style="font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">
                            <i class="icon-info22 text-primary" style="margin-right: 4px;"></i> Required Spreadsheet Layout:
                        </div>
                        <ul style="font-size: 12.5px; color: #475569; margin: 0; padding-left: 20px; line-height: 1.6;">
                            <li><strong>Cell I1:</strong> Collection Name (e.g. <em>Plain</em>, <em>PINK PLAIN</em>, <em>CNC -PLAIN-YELLOW-92</em>)</li>
                            <li><strong>Row 3 Onwards:</strong></li>
                            <li style="margin-left: 12px;"><strong>Column B:</strong> Customer Name (matched against Customer Master)</li>
                            <li style="margin-left: 12px;"><strong>Column I:</strong> Mapped Customer Name (e.g. <em>SO 92</em>)</li>
                            <li style="margin-left: 12px;"><strong>Column J:</strong> Purity (e.g. <em>0.92</em> or <em>92</em>)</li>
                        </ul>
                    </div>

                    <!-- File Input -->
                    <div class="sw-form-group">
                        <label class="sw-label">Select Excel File (.xlsx, .xls) <span class="req">*</span></label>
                        <input type="file" name="mapped_cust_sheet" id="mapped_cust_sheet" class="sw-input" style="padding: 8px 12px;" accept=".xlsx,.xls,.csv" required>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--sw-border); padding: 14px 24px;">
                    <button type="button" class="sw-btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="sw-btn-primary">
                        <i class="icon-upload"></i>
                        <span>Upload &amp; Import</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
