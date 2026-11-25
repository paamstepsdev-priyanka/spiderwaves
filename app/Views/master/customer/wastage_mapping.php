<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Manage Wastage - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Customer Wastage<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Manage Wastage for Customer: <span style="color: var(--sw-primary);"><?= esc($customer['cust_name']) ?></span></h1>
    </div>
    <div class="sw-page-actions">
        <a href="<?= base_url('master/customer') ?>" class="sw-btn-secondary">
            <i class="icon-arrow-left52"></i>
            <span>Back to Customers</span>
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

<!-- Customer Details Card -->
<div class="sw-table-card" style="padding: 16px 20px; margin-bottom: 20px;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div>
            <span style="font-size: 13px; color: #64748b;">Account Classification:</span>
            <span class="sw-badge" style="margin-left: 6px; <?= ($customer['type'] === 'vendor') ? 'background: #fef3c7; color: #92400e;' : 'background: #eff6ff; color: #1e40af;' ?>">
                <?= ucfirst(esc($customer['type'])) ?>
            </span>
            <span style="margin: 0 10px; color: #cbd5e1;">&bull;</span>
            <span style="font-size: 13px; color: #64748b;">Mobile:</span>
            <span style="font-weight: 500; color: #1e293b; margin-left: 4px;">
                <?= !empty($customer['mobile_no']) ? esc($customer['mobile_no']) : 'N/A' ?>
            </span>
        </div>
        <div style="font-size: 12px; color: #94a3b8; font-family: monospace;">
            Customer Key: <?= esc($customer['private_key']) ?>
        </div>
    </div>
</div>

<!-- Wastage Mapping Form & Table -->
<form id="customer_wastage_form" action="<?= base_url('master/insert_customer_wastage_data') ?>" method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="customer_id" id="customer_id" value="<?= esc($customer['cust_id']) ?>">
    <input type="hidden" name="base_url" id="base_url" value="<?= base_url() ?>">

    <div class="sw-table-card">
        <div class="sw-table-wrapper">
            <table class="sw-table" style="table-layout: fixed; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 70px; text-align: center;">#</th>
                        <th style="width: 55%;">Collection Name</th>
                        <th style="width: 35%;">Wastage (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($collections)): ?>
                        <?php foreach ($collections as $idx => $col): ?>
                            <?php 
                                $colId = (int)$col['collection_id'];
                                $val   = isset($wastageMap[$colId]) ? (float)$wastageMap[$colId] : 0.0;
                                // If whole number or clean decimal
                                $displayVal = (floor($val) == $val) ? (string)(int)$val : (string)$val;
                            ?>
                            <tr>
                                <td style="text-align: center; color: #94a3b8; font-weight: 500;">
                                    <?= $idx + 1 ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #1e293b;">
                                        <?= esc($col['collection_name']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <input type="number" 
                                               step="0.001" 
                                               min="0" 
                                               max="100" 
                                               name="<?= $colId ?>" 
                                               id="col_<?= $colId ?>" 
                                               class="sw-input" 
                                               style="max-width: 220px; padding-left: 14px;" 
                                               value="<?= esc($displayVal) ?>" 
                                               placeholder="0">
                                        <span style="font-size: 13px; color: #94a3b8; font-weight: 500;">%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 48px 16px; color: #94a3b8;">
                                <div style="font-size: 40px; margin-bottom: 12px; color: #cbd5e1;">
                                    <i class="icon-stack2"></i>
                                </div>
                                <div style="font-size: 15px; font-weight: 500; color: #64748b;">No active collections available</div>
                                <p style="font-size: 13px; margin-top: 4px;">Please create active product collections first in Collection Master.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Submit Bar -->
        <div class="sw-table-footer" style="display: flex; justify-content: flex-end; align-items: center; gap: 12px; padding: 14px 20px; border-top: 1px solid #f1f5f9;">
            <a href="<?= base_url('master/customer') ?>" class="sw-btn-secondary">Cancel</a>
            <button type="submit" id="btnSaveWastage" class="sw-btn-primary">
                <i class="icon-checkmark4"></i>
                <span id="btnSaveText">Set Wastage</span>
            </button>
        </div>
    </div>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="text/javascript">
$(document).ready(function() {
    var $form = $('#customer_wastage_form');
    var $btn  = $('#btnSaveWastage');

    $form.on('submit', function(e) {
        e.preventDefault();

        var origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="icon-spinner2 spinner"></i> <span>Saving...</span>');

        var formData = new FormData($form[0]);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'text',
            success: function(resp) {
                $btn.prop('disabled', false).html(origHtml);

                var trimmed = resp.trim();
                if (trimmed === 'commit' || trimmed === 'true') {
                    swal({
                        title: "Success!",
                        text: "Update Successfully!",
                        type: "success",
                        confirmButtonColor: "#2563eb",
                        confirmButtonText: "OK",
                        closeOnConfirm: true
                    }, function() {
                        window.location.reload();
                    });
                } else {
                    swal({
                        title: "Error!",
                        text: "Please Try Again!",
                        type: "error",
                        confirmButtonColor: "#ef4444",
                        confirmButtonText: "OK"
                    });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html(origHtml);
                swal({
                    title: "Server Error",
                    text: "An error occurred while communicating with the server.",
                    type: "error",
                    confirmButtonColor: "#ef4444"
                });
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
