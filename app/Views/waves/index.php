<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Waves Financial Ledger - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Waves Ledger<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1 style="margin: 0;">Waves Ledger</h1>
    </div>
    <div class="sw-page-actions" style="display: flex; align-items: center; gap: 10px;">
        <button type="button" class="sw-btn-primary" style="padding: 6px 14px; font-size: 12px; font-weight: 700;" data-toggle="modal" data-target="#waveEntryModal">
            <i class="icon-plus2" style="font-size: 11px;"></i> NEW
        </button>
        <a href="<?= base_url('waves/in_out') ?>" class="sw-btn-secondary">
            <i class="icon-pencil7"></i>
            <span>Entry Page</span>
        </a>
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
    <form method="get" action="<?= base_url('waves') ?>" id="wavesFilterForm">
        <div class="sw-filter-grid">
            <!-- Keyword search -->
            <div class="sw-search-group" style="flex: 2; min-width: 200px;">
                <input type="text" name="search" class="sw-input" style="padding-left: 14px;" placeholder="Search customer, remark, voucher ID..." value="<?= esc($search ?? '') ?>" autocomplete="off">
            </div>

            <!-- Customer Filter -->
            <div style="flex: 1.5; min-width: 170px;">
                <select name="cust_id" id="filter_cust_id" class="select2" style="width: 100%;" onchange="document.getElementById('wavesFilterForm').submit()">
                    <option value="">All Customers</option>
                    <?php if (!empty($cust_data)): ?>
                        <?php foreach ($cust_data as $cust): ?>
                            <option value="<?= $cust['cust_id'] ?>" <?= ($cust_id == $cust['cust_id']) ? 'selected' : '' ?>>
                                <?= esc($cust['cust_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <!-- Mode Filter -->
            <div style="flex: 1; min-width: 110px;">
                <select name="mode" id="filter_mode" class="select2" style="width: 100%;" onchange="document.getElementById('wavesFilterForm').submit()">
                    <option value="">All Modes</option>
                    <option value="MT" <?= ($mode === 'MT') ? 'selected' : '' ?>>MT (Fine)</option>
                    <option value="OR" <?= ($mode === 'OR') ? 'selected' : '' ?>>OR (Metal)</option>
                    <option value="RK" <?= ($mode === 'RK') ? 'selected' : '' ?>>RK (Cash)</option>
                </select>
            </div>

            <!-- Type Filter -->
            <div style="flex: 1; min-width: 110px;">
                <select name="type" id="filter_type" class="select2" style="width: 100%;" onchange="document.getElementById('wavesFilterForm').submit()">
                    <option value="">All Types</option>
                    <option value="receipt" <?= ($type === 'receipt') ? 'selected' : '' ?>>Receipt</option>
                    <option value="issue" <?= ($type === 'issue') ? 'selected' : '' ?>>Issue</option>
                </select>
            </div>

            <!-- Date range -->
            <div style="display: flex; gap: 8px; align-items: center;">
                <span style="color: var(--sw-text-muted, #64748b); font-size: 13px; font-weight: 500;">From</span>
                <input type="date" name="from_date" class="sw-input" value="<?= esc($from_date ?? '') ?>" style="width: 155px; height: 40px; padding: 0 10px; font-size: 13px;" title="From Date">
                <span style="color: var(--sw-text-muted, #64748b); font-size: 13px; font-weight: 500;">to</span>
                <input type="date" name="to_date" class="sw-input" value="<?= esc($to_date ?? '') ?>" style="width: 155px; height: 40px; padding: 0 10px; font-size: 13px;" title="To Date">
            </div>

            <!-- Submit & Reset Buttons -->
            <div style="display: flex; gap: 6px;">
                <button type="submit" class="sw-btn-primary" style="height: 40px; padding: 0 16px;">
                    <i class="icon-filter3"></i>
                    <span>Filter</span>
                </button>
                <a href="<?= base_url('waves') ?>" class="sw-btn-secondary" style="height: 40px; padding: 0 14px;" title="Reset Filters">
                    <i class="icon-reset"></i>
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Ledger Table Card -->
<div class="sw-table-card">
    <div class="sw-table-header">
        <div>
            <h3 class="sw-table-title">Permanent Ledger Transactions</h3>
            <span class="sw-table-info">
                Showing <?= count($records) ?> of <?= number_format($total_records) ?> entries (Page <?= $current_page ?> of <?= $total_pages ?>)
            </span>
        </div>
    </div>

    <div class="sw-table-responsive">
        <table class="sw-table">
            <thead>
                <tr>
                    <th style="width: 85px;">ID</th>
                    <th style="width: 120px;">Date</th>
                    <th style="min-width: 180px;">Customer Name</th>
                    <th style="text-align: center; width: 110px;">Type</th>
                    <th style="text-align: right; width: 110px;">Gross Wt</th>
                    <th style="text-align: right; width: 110px;">Net Wt</th>
                    <th style="text-align: right; width: 100px;">Touch %</th>
                    <th style="text-align: right; width: 100px;">Wstg %</th>
                    <th style="text-align: right; width: 120px;">Fine Wt</th>
                    <th style="text-align: center; width: 110px;">Status</th>
                    <th style="text-align: center; width: 90px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 40px; color: var(--sw-text-muted, #64748b);">
                            <i class="icon-inbox" style="font-size: 36px; display: block; margin-bottom: 8px; opacity: 0.5;"></i>
                            No Waves ledger transactions found matching the filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($records as $row): ?>
                        <tr>
                            <!-- Transaction ID -->
                            <td style="font-weight: 600; color: var(--sw-text-muted, #64748b);">
                                #<?= esc($row['in_out_id']) ?>
                            </td>

                            <!-- Entry Date -->
                            <td style="font-size: 13px; color: var(--sw-text-main, #0f172a); white-space: nowrap;">
                                <?= !empty($row['entry_date']) ? date('d-M-Y', strtotime($row['entry_date'])) : '—' ?>
                            </td>

                            <!-- Customer Name -->
                            <td>
                                <span class="sw-cust-name"><?= esc($row['cust_name'] ?? '—') ?></span>
                                <?php if (!empty($row['cust_type'])): ?>
                                    <span class="sw-pill-badge sw-badge-neutral" style="font-size: 10px; padding: 1px 6px; margin-left: 6px;">
                                        <?= strtoupper(esc($row['cust_type'])) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Transactional Type (Arrow removed) -->
                            <td style="text-align: center;">
                                <?php if ($row['transactional_type'] === 'receipt'): ?>
                                    <span class="sw-pill-badge sw-badge-success" style="font-size: 11px; font-weight: 600; padding: 3px 10px; text-transform: uppercase;">
                                        Receipt
                                    </span>
                                <?php else: ?>
                                    <span class="sw-pill-badge sw-badge-danger" style="font-size: 11px; font-weight: 600; padding: 3px 10px; text-transform: uppercase;">
                                        Issue
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Gross Weight -->
                            <td style="text-align: right; font-weight: 500;">
                                <?= ((float)$row['gross_weight'] > 0) ? view_round_up($row['gross_weight']) : '—' ?>
                            </td>

                            <!-- Net Weight -->
                            <td style="text-align: right; font-weight: 500;">
                                <?= ((float)$row['net_weight'] > 0) ? view_round_up($row['net_weight']) : '—' ?>
                            </td>

                            <!-- Purity / Touch -->
                            <td style="text-align: right;">
                                <?= ((float)$row['purity'] > 0) ? view_round_up($row['purity'], 2) : '—' ?>
                            </td>

                            <!-- Wastage -->
                            <td style="text-align: right;">
                                <?= ((float)$row['wastage'] > 0) ? view_round_up($row['wastage'], 2) : '—' ?>
                            </td>

                            <!-- Fine Weight -->
                            <td style="text-align: right; font-weight: 600; color: #2563eb;">
                                <?= ((float)$row['fine_weight'] > 0) ? view_round_up($row['fine_weight']) : '—' ?>
                            </td>

                            <!-- Status -->
                            <td style="text-align: center;">
                                <?php if ($row['approved_data'] === '1'): ?>
                                    <span class="sw-pill-badge sw-badge-success" style="font-size: 11px; text-transform: uppercase;">Approved</span>
                                <?php else: ?>
                                    <span class="sw-pill-badge sw-badge-warning" style="font-size: 11px; text-transform: uppercase;">Pending</span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 6px; justify-content: center;">
                                    <a href="<?= base_url('waves/edit_in_out/' . $row['private_key']) ?>" class="sw-icon-btn" title="Edit Wave" style="color: #2563eb;">
                                        <i class="icon-pencil5"></i>
                                    </a>
                                    <button type="button" class="sw-icon-btn view-details-btn" data-key="<?= esc($row['private_key']) ?>" title="View Details" style="color: #64748b;">
                                        <i class="icon-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <?php if ($total_pages > 1): ?>
        <?php
            $queryParams = [
                'search'    => $search,
                'cust_id'   => $cust_id ?: null,
                'mode'      => $mode ?: null,
                'type'      => $type ?: null,
                'from_date' => $from_date ?: null,
                'to_date'   => $to_date ?: null,
            ];
            $queryParams = array_filter($queryParams);
            
            $buildPageUrl = function($pageNum) use ($queryParams) {
                $params = array_merge($queryParams, ['page' => $pageNum]);
                return base_url('waves') . '?' . http_build_query($params);
            };
        ?>
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-top: 1px solid var(--sw-border, #e2e8f0); background: #ffffff;">
            <div style="font-size: 13px; color: var(--sw-text-muted, #64748b);">
                Page <strong><?= $current_page ?></strong> of <strong><?= $total_pages ?></strong>
            </div>
            <div style="display: flex; gap: 4px;">
                <?php if ($current_page > 1): ?>
                    <a href="<?= $buildPageUrl(1) ?>" class="sw-btn-secondary" style="padding: 0 10px; height: 34px; font-size: 12px;">First</a>
                    <a href="<?= $buildPageUrl($current_page - 1) ?>" class="sw-btn-secondary" style="padding: 0 10px; height: 34px; font-size: 12px;">&laquo; Prev</a>
                <?php endif; ?>

                <?php 
                    $startPage = max(1, $current_page - 2);
                    $endPage   = min($total_pages, $current_page + 2);
                    for ($p = $startPage; $p <= $endPage; $p++):
                ?>
                    <a href="<?= $buildPageUrl($p) ?>" class="<?= ($p === $current_page) ? 'sw-btn-primary' : 'sw-btn-secondary' ?>" style="padding: 0 12px; height: 34px; font-size: 12px;">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="<?= $buildPageUrl($current_page + 1) ?>" class="sw-btn-secondary" style="padding: 0 10px; height: 34px; font-size: 12px;">Next &raquo;</a>
                    <a href="<?= $buildPageUrl($total_pages) ?>" class="sw-btn-secondary" style="padding: 0 10px; height: 34px; font-size: 12px;">Last</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: New Wave Entry -->
<div class="modal fade" id="waveEntryModal" tabindex="-1" role="dialog" aria-labelledby="waveEntryModalLabel">
    <div class="modal-dialog" role="document" style="max-width: 580px; margin: 30px auto;">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15); overflow: hidden;">
            <div class="modal-header" style="background: #ffffff; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h4 class="modal-title" id="waveEntryModalLabel" style="margin: 0; font-weight: 700; color: #0f172a; font-size: 17px;">
                        New Wave Entry
                    </h4>
                    <p style="margin: 2px 0 0; font-size: 12px; color: #64748b;">Direct financial gold and cash transaction entry</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 24px; color: #94a3b8; opacity: 1; outline: none;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="quickWaveEntryForm" method="post" action="<?= base_url('waves/insert_in_out') ?>" novalidate>
                <input type="hidden" name="form_type" value="Add">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

                <div class="modal-body" style="padding: 24px;">
                    <!-- Mode Selection: RK / MT / OR -->
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 8px;">
                            Transaction Mode <span class="text-danger">*</span>
                        </label>
                        <div style="display: flex; gap: 12px;">
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-weight: 500;">
                                <input type="radio" name="transactional_mode" value="RK" checked class="modal-mode-radio">
                                <span>RK (Cash)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-weight: 500;">
                                <input type="radio" name="transactional_mode" value="MT" class="modal-mode-radio">
                                <span>MT (Fine Gold)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-weight: 500;">
                                <input type="radio" name="transactional_mode" value="OR" class="modal-mode-radio">
                                <span>OR (Metal Weight)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Type Selection: Issue / Receipt -->
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 8px;">
                            Transaction Type <span class="text-danger">*</span>
                        </label>
                        <div style="display: flex; gap: 12px;">
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-weight: 500;">
                                <input type="radio" name="transactional_type" value="issue" required>
                                <span style="color: #dc2626; font-weight: 600;">Issue (Outward)</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 8px 16px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; font-weight: 500;">
                                <input type="radio" name="transactional_type" value="receipt" checked required>
                                <span style="color: #2563eb; font-weight: 600;">Receipt (Inward)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Customer Selection -->
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label for="modal_cust_id" style="display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px;">
                            Customer Name <span class="text-danger">*</span>
                        </label>
                        <select name="cust_id" id="modal_cust_id" class="select2" style="width: 100%;" required>
                            <option value="">Select Customer</option>
                            <?php if (!empty($cust_data)): ?>
                                <?php foreach ($cust_data as $cust): ?>
                                    <option value="<?= $cust['cust_id'] ?>">
                                        <?= esc($cust['cust_name']) ?> (<?= strtoupper(esc($cust['type'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Metal Fields (MT / OR mode) -->
                    <div class="modal-metal-fields" style="display: none;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                            <div>
                                <label for="modal_gross_weight" style="display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px;">
                                    Gross Weight (g) <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.001" name="gross_weight" id="modal_gross_weight" class="sw-input" placeholder="0.000" style="width: 100%; height: 42px;">
                            </div>
                            <div>
                                <label for="modal_purity" style="display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px;">
                                    Purity / Touch (%) <span class="text-danger">*</span>
                                </label>
                                <select name="purity" id="modal_purity" class="select2" style="width: 100%;">
                                    <option value="">Select Purity</option>
                                    <?php if (!empty($active_purities)): ?>
                                        <?php foreach ($active_purities as $p): ?>
                                            <?php $valStr = number_format((float)$p['purity_value'], 2, '.', ''); ?>
                                            <option value="<?= $valStr ?>">
                                                <?= esc($p['purity_name']) ?> (<?= $valStr ?>%)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Cash Amount (RK mode) -->
                    <div class="modal-rk-field" style="margin-bottom: 18px;">
                        <label for="modal_amount" style="display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px;">
                            Amount (&#8377;) <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.01" name="amount" id="modal_amount" class="sw-input" placeholder="Enter rupee amount" style="width: 100%; height: 42px;" required>
                    </div>

                    <!-- Remark Selection -->
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label for="modal_remark" style="display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px;">
                            Remark <span class="text-danger">*</span>
                        </label>
                        <select name="remark" id="modal_remark" class="select2" style="width: 100%;" required>
                            <option value="">Select Remark</option>
                            <?php if (!empty($remark_data)): ?>
                                <?php foreach ($remark_data as $rmk): ?>
                                    <option value="<?= esc($rmk['remark']) ?>"><?= esc($rmk['remark']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <option value="other">Other Remark</option>
                        </select>
                    </div>

                    <!-- Other Remark Input -->
                    <div class="modal-other-remark" style="display: none; margin-bottom: 18px;">
                        <label for="modal_other_remark" style="display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px;">
                            Specify Other Remark <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="other_remark" id="modal_other_remark" class="sw-input" placeholder="Type custom remark..." style="width: 100%; height: 42px;">
                    </div>
                </div>

                <div class="modal-footer" style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="sw-btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="sw-btn-primary" id="modalSubmitBtn">
                        <i class="icon-checkmark-circle"></i>
                        <span>Save Wave</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: View Details -->
<div class="modal fade" id="waveDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document" style="max-width: 520px; margin: 50px auto;">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.15); overflow: hidden;">
            <div class="modal-header" style="background: #ffffff; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <h4 class="modal-title" style="margin: 0; font-weight: 700; color: #0f172a; font-size: 17px;">
                    Transaction Details
                </h4>
                <button type="button" class="close" data-dismiss="modal" style="font-size: 24px; color: #94a3b8; opacity: 1; outline: none;">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="waveDetailsBody" style="padding: 24px;">
                <div style="text-align: center; padding: 30px;">
                    <i class="icon-spinner3 spinner" style="font-size: 24px; color: #2563eb;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inline JavaScript for Interactivity -->
<script>
$(document).ready(function() {
    // Mode switcher in Modal
    $('.modal-mode-radio').change(function() {
        var mode = $(this).val();
        if (mode === 'MT' || mode === 'OR') {
            $('.modal-metal-fields').show();
            $('.modal-rk-field').hide();
            $('#modal_gross_weight').attr('required', 'true');
            $('#modal_purity').attr('required', 'true');
            $('#modal_amount').removeAttr('required').val('');
        } else {
            $('.modal-metal-fields').hide();
            $('.modal-rk-field').show();
            $('#modal_amount').attr('required', 'true');
            $('#modal_gross_weight').removeAttr('required').val('');
            $('#modal_purity').removeAttr('required').val('').trigger('change');
        }
    });

    // Initialize Select2 in Modal upon opening
    $('#waveEntryModal').on('shown.bs.modal', function() {
        if ($.fn.select2) {
            $('#modal_cust_id').select2({
                placeholder: "Select Customer",
                allowClear: true,
                width: '100%'
            });
            $('#modal_purity').select2({
                placeholder: "Select Purity",
                allowClear: true,
                width: '100%'
            });
            $('#modal_remark').select2({
                placeholder: "Select Remark",
                allowClear: true,
                width: '100%'
            });
        }
    });

    // Reset Modal Select2 on close
    $('#waveEntryModal').on('hidden.bs.modal', function() {
        if ($.fn.select2) {
            $('#modal_cust_id').select2('val', '');
            $('#modal_purity').select2('val', '');
            $('#modal_remark').select2('val', '');
        }
    });

    // Remark other handler
    $('#modal_remark').on('change', function() {
        if ($(this).val() === 'other') {
            $('.modal-other-remark').show();
            $('#modal_other_remark').attr('required', 'true');
        } else {
            $('.modal-other-remark').hide();
            $('#modal_other_remark').removeAttr('required').val('');
        }
    });

    // Form Submission via AJAX with SweetAlert feedback
    $('#quickWaveEntryForm').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = $('#modalSubmitBtn');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(resp) {
                if (resp === 'true' || resp.result === 'true') {
                    $('#waveEntryModal').modal('hide');
                    swal({
                        title: "Success!",
                        text: "Wave Transaction Recorded Successfully!",
                        type: "success",
                        confirmButtonColor: "#2563eb",
                        closeOnConfirm: true
                    }, function() {
                        window.location.reload();
                    });
                } else {
                    swal({
                        title: "Error!",
                        text: "Failed to record wave transaction. Please verify inputs.",
                        type: "error",
                        confirmButtonColor: "#ef4444"
                    });
                    submitBtn.prop('disabled', false);
                }
            },
            error: function() {
                swal({
                    title: "System Error",
                    text: "An error occurred while connecting to server.",
                    type: "error",
                    confirmButtonColor: "#ef4444"
                });
                submitBtn.prop('disabled', false);
            }
        });
    });

    // View Details Modal
    $('.view-details-btn').click(function() {
        var key = $(this).data('key');
        var url = '<?= base_url("waves/view_details") ?>/' + key;
        $('#waveDetailsBody').html('<div style="text-align: center; padding: 30px;"><i class="icon-spinner3 spinner" style="font-size: 24px; color: #2563eb;"></i></div>');
        $('#waveDetailsModal').modal('show');
        $.get(url, function(data) {
            $('#waveDetailsBody').html(data);
        }).fail(function() {
            $('#waveDetailsBody').html('<div class="alert alert-danger">Unable to load transaction details.</div>');
        });
    });
});
</script>
<?= $this->endSection() ?>
