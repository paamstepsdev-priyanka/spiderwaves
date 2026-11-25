<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Edit Petty Cash Status - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Edit Petty Cash<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Edit Petty Cash Designation</h1>
    </div>
    <div class="sw-page-actions">
        <a href="<?= base_url('master/petty-cash') ?>" class="sw-btn-secondary">
            <i class="icon-arrow-left52"></i>
            <span>Back to Petty Cash List</span>
        </a>
    </div>
</div>

<!-- Flash Alerts -->
<?php if (session()->getFlashdata('error')): ?>
    <div class="sw-alert sw-alert-danger">
        <i class="icon-alert"></i>
        <span><?= esc(session()->getFlashdata('error')) ?></span>
    </div>
<?php endif; ?>

<!-- Form Card -->
<div class="sw-form-card">
    <form id="edit_petty_cash_form" action="<?= base_url('master/petty-cash/update/' . $customer['private_key']) ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="form_type" value="Edit">
        <input type="hidden" name="private_key" value="<?= esc($customer['private_key']) ?>">

        <div class="sw-form-section-title">
            <i class="icon-coins" style="margin-right: 6px; color: var(--sw-primary);"></i> Account Details & Configuration
        </div>

        <div class="row">
            <!-- Account Info Box -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Account Name</label>
                    <div style="font-size: 15px; font-weight: 600; color: #1e293b; padding: 8px 0;">
                        <?= esc($customer['cust_name']) ?>
                        <span class="sw-badge" style="margin-left: 8px; font-size: 11px; <?= ($customer['type'] === 'vendor') ? 'background: #fef3c7; color: #92400e;' : 'background: #eff6ff; color: #1e40af;' ?>">
                            <?= ucfirst(esc($customer['type'])) ?>
                        </span>
                    </div>
                    <div style="font-size: 12px; color: #64748b;">
                        Mobile: <?= !empty($customer['mobile_no']) ? esc($customer['mobile_no']) : 'N/A' ?> &bull; 
                        Status: <?= ($customer['cust_status'] === '1') ? '<span style="color:#2563eb; font-weight:500;">Active</span>' : '<span style="color:#ef4444; font-weight:500;">Inactive</span>' ?>
                    </div>
                </div>
            </div>

            <!-- Petty Cash Setting -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Petty Cash Status <span class="req">*</span></label>
                    <div class="sw-radio-group">
                        <label class="sw-radio-label">
                            <input type="radio" name="is_petty_cash" value="1" <?= ($customer['is_petty_cash'] === '1') ? 'checked' : '' ?>> Yes (Petty Cash Account)
                        </label>
                        <label class="sw-radio-label">
                            <input type="radio" name="is_petty_cash" value="0" <?= ($customer['is_petty_cash'] === '0') ? 'checked' : '' ?>> No (Standard Account)
                        </label>
                    </div>
                    <div style="font-size: 12px; color: #94a3b8; margin-top: 6px;">
                        When set to Yes, transactions for this customer can be marked as petty cash payments in wave entries and reports.
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Actions -->
        <div class="sw-form-actions">
            <a href="<?= base_url('master/petty-cash') ?>" class="sw-btn-secondary">Cancel</a>
            <button type="submit" class="sw-btn-primary" id="btnUpdatePettyCash">
                <i class="icon-checkmark4"></i>
                <span id="btnUpdateText">Update Status</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="text/javascript">
$(document).ready(function() {
    var $form = $('#edit_petty_cash_form');
    var $btn  = $('#btnUpdatePettyCash');

    $form.on('submit', function(e) {
        e.preventDefault();

        var origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="icon-spinner2 spinner"></i> <span>Updating...</span>');

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

                if (resp.trim() === 'true') {
                    swal({
                        title: "Updated Successfully!",
                        text: "Account petty cash status has been updated.",
                        type: "success",
                        confirmButtonColor: "#2563eb",
                        confirmButtonText: "OK"
                    }, function() {
                        window.location.href = "<?= base_url('master/petty-cash') ?>";
                    });
                } else {
                    swal({
                        title: "Failed to Update",
                        text: "Could not update account petty cash status. Please try again.",
                        type: "error",
                        confirmButtonColor: "#ef4444"
                    });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html(origHtml);
                swal({
                    title: "Server Error",
                    text: "An error occurred while saving the update.",
                    type: "error",
                    confirmButtonColor: "#ef4444"
                });
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
