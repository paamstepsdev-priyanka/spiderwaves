<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Manage Raw Waves Queue - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Manage Raw Waves<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1 style="margin: 0;">Manage Raw Waves</h1>
    </div>
    <div class="sw-page-actions" style="display: flex; align-items: center; gap: 10px;">
        <!-- Quick Verification Code Search Form -->
        <?php if (can('raw_waves.verify')): ?>
        <form id="quickVerifyForm" novalidate style="display: flex; gap: 6px; margin: 0;">
            <input type="text" class="sw-input" name="quick_code" id="quick_code" data-label="Verification Code" placeholder="Enter 5-digit code..." style="width: 170px; height: 38px;" maxlength="10" autocomplete="off" required>
            <button type="submit" class="sw-btn-primary" style="height: 38px; padding: 0 14px;">
                <i class="icon-shield-check"></i>
                <span>Verify</span>
            </button>
        </form>
        <?php endif; ?>

        <?php if (can('raw_waves.create')): ?>
        <a href="<?= base_url('raw-waves') ?>" class="sw-btn-primary" style="padding: 6px 14px; font-size: 12px; font-weight: 700;">
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
    <form method="get" action="<?= base_url('raw-waves/manage') ?>" id="rawWavesFilterForm">
        <div class="sw-filter-grid">
            <!-- Keyword search -->
            <div class="sw-search-group" style="flex: 2; min-width: 200px;">
                <input type="text" name="search" class="sw-input" style="padding-left: 14px;" placeholder="Search customer, code, remark, delivery..." value="<?= esc($search ?? '') ?>" autocomplete="off">
            </div>

            <!-- Mode Filter -->            <div style="flex: 1; min-width: 110px;">
                <select name="mode" id="filter_mode" class="select2" style="width: 100%;" onchange="document.getElementById('rawWavesFilterForm').submit()">
                    <option value="">All Modes</option>
                    <option value="MT" <?= ($mode === 'MT') ? 'selected' : '' ?>>MT</option>
                    <option value="OR" <?= ($mode === 'OR') ? 'selected' : '' ?>>OR</option>
                    <option value="RK" <?= ($mode === 'RK') ? 'selected' : '' ?>>RK</option>
                </select>
            </div>

            <!-- Type Filter -->
            <div style="flex: 1; min-width: 110px;">
                <select name="type" id="filter_type" class="select2" style="width: 100%;" onchange="document.getElementById('rawWavesFilterForm').submit()">
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

            <!-- Filter Buttons -->
            <button type="submit" class="sw-btn-secondary" style="padding: 0 16px;">
                <i class="icon-filter4"></i>
                <span>Filter</span>
            </button>

            <?php if (!empty($search) || !empty($mode) || !empty($type) || !empty($from_date) || !empty($to_date)): ?>
                <a href="<?= base_url('raw-waves/manage') ?>" class="sw-btn-secondary" title="Reset Filters" style="color: #64748b; padding: 0 12px;">
                    <i class="icon-cross2"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Table Card -->
<div class="sw-table-card">
    <div class="sw-table-header">
        <div class="sw-table-title">Raw Waves Queue</div>
        <div class="sw-table-info">
            Showing <?= count($raw_waves_data) ?> of <?= esc($total ?? count($raw_waves_data)) ?> records
        </div>
    </div>

    <?php 
        $userAccessLevel = $access_level ?? (session()->get('spider')['access_level'] ?? '');
        $showAction = ($userAccessLevel === 'admin');
    ?>

    <div class="sw-table-responsive">
        <table class="sw-table">
            <thead>
                <tr>
                    <th style="width: 50px;">S.No.</th>
                    <th>Cust Name</th>
                    <th>Delivery By</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Status</th>
                    <?php if ($showAction): ?>
                        <th style="text-align: center; width: 120px;">Action</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($raw_waves_data) && count($raw_waves_data) > 0): ?>
                    <?php 
                        $currentPage = $pager ? $pager->getCurrentPage() : 1;
                        $perPage = 15;
                        $startIndex = ($currentPage - 1) * $perPage;
                    ?>
                    <?php foreach ($raw_waves_data as $idx => $row): ?>
                        <?php 
                            $isApproved = ($row['approved_data'] === '1');
                            $typeColor = $row['transactional_type'] === 'receipt' ? 'sw-badge-success' : 'sw-badge-danger';
                        ?>
                        <tr id="row_raw_wave_<?= esc($row['private_key']) ?>">
                            <td><?= $startIndex + $idx + 1 ?></td>
                            <td>
                                <strong style="color: #0f172a;"><?= esc($row['cust_name'] ?? 'Unknown') ?></strong>
                                <?php if (!empty($row['collection_name'])): ?>
                                    <div style="font-size: 11px; color: #64748b;"><?= esc($row['collection_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= esc(ucwords($row['delivery_by'] ?? '')) ?></td>
                            <td>
                                <span class="sw-pill-badge <?= $typeColor ?>"><?= esc(ucfirst($row['transactional_type'])) ?></span>
                            </td>
                            <td style="white-space: nowrap; font-size: 11.5px; color: #64748b;">
                                <div><?= date('d/m/Y', strtotime($row['date'])) ?></div>
                                <div style="font-size: 10.5px; color: #94a3b8;"><?= esc($row['time']) ?></div>
                            </td>
                            <td id="status_td_<?= esc($row['private_key']) ?>">
                                <?php if ($isApproved): ?>
                                    <span class="sw-pill-badge sw-badge-success">Approved</span>
                                <?php else: ?>
                                    <span class="sw-pill-badge sw-badge-danger">Pending</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($showAction): ?>
                            <td style="text-align: center;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <?php if (can('raw_waves.verify')): ?>
                                    <!-- Verify Modal Trigger -->
                                    <button type="button" class="sw-btn-secondary btn-verify-wave" data-code="<?= esc($row['verification_code']) ?>" data-type="<?= esc($row['transactional_type']) ?>" title="Verify & Re-weigh" style="padding: 4px 8px; font-size: 12px;">
                                        <i class="icon-eye" style="color: var(--sw-primary);"></i>
                                    </button>
                                    <?php endif; ?>

                                    <?php if (can('raw_waves.print')): ?>
                                    <!-- Print Slip Voucher Trigger -->
                                    <a href="<?= base_url('raw-waves/voucher/' . $row['in_out_id']) ?>" target="_blank" class="sw-btn-secondary" title="Print Voucher" style="padding: 4px 8px; font-size: 12px; color: #475569;">
                                        <i class="icon-printer"></i>
                                    </a>
                                    <?php endif; ?>

                                    <?php if (!$isApproved && can('raw_waves.edit')): ?>
                                    <!-- Edit Trigger -->
                                    <a href="<?= base_url('raw-waves/edit/' . $row['private_key']) ?>" class="sw-btn-secondary" title="Edit Raw Wave" style="padding: 4px 8px; font-size: 12px; color: #475569;">
                                        <i class="icon-pencil4"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= $showAction ? 7 : 6 ?>" style="text-align: center; padding: 40px 20px; color: #64748b;">
                            <i class="icon-file-empty" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                            No raw waves found matching current criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Table Footer with Summary Counter and Pagination -->
    <?php if ($pager && $pager->getPageCount() > 1): ?>
        <div class="sw-table-footer">
            <div style="font-size: 13px; color: var(--sw-text-muted);">
                Showing page <strong><?= $pager->getCurrentPage() ?></strong> of <strong><?= $pager->getPageCount() ?></strong> (<?= esc($total ?? count($raw_waves_data)) ?> records)
            </div>
            <div>
                <?= $pager->links('default', 'modern') ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Dynamic Verification Modal Container -->
<div class="modal fade" id="viewVoucherModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div id="voucherModalContent">
        <!-- Content loaded dynamically via AJAX -->
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="text/javascript">
$(document).ready(function() {
    var $modal = $('#viewVoucherModal');
    var $modalContent = $('#voucherModalContent');

    function openVerificationModal(code, transType) {
        $modalContent.html('<div class="modal-dialog"><div class="modal-content" style="padding: 40px; text-align: center;"><i class="icon-spinner2 spinner" style="font-size: 28px; color: var(--sw-primary);"></i><p style="margin-top: 10px; color: #64748b;">Loading voucher verification details...</p></div></div>');
        $modal.modal('show');

        var url = '<?= base_url("raw-waves/view-voucher") ?>/' + encodeURIComponent(code);
        if (transType) {
            url += '/' + encodeURIComponent(transType);
        }

        $.get(url, function(html) {
            $modalContent.html(html);
        }).fail(function() {
            $modalContent.html('<div class="modal-dialog"><div class="modal-content" style="padding: 24px;"><div class="sw-alert sw-alert-danger"><i class="icon-alert"></i><span>Failed to load voucher details from server.</span></div><div class="text-right" style="margin-top: 14px;"><button type="button" class="sw-btn-secondary" data-dismiss="modal">Close</button></div></div></div>');
        });
    }

    // Quick verification code search
    $('#quickVerifyForm').on('submit', function(e) {
        e.preventDefault();
        var code = $.trim($('#quick_code').val());
        if (!code) return;
        openVerificationModal(code, null);
    });

    // Row action verify button click
    $(document).on('click', '.btn-verify-wave', function(e) {
        e.preventDefault();
        var code = $(this).data('code');
        var transType = $(this).data('type');
        openVerificationModal(code, transType);
    });
});
</script>
<?= $this->endSection() ?>
