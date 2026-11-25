<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Assign Petty Cash - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Assign Petty Cash<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Assign Petty Cash Account</h1>
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
    <form id="assign_petty_cash_form" action="<?= base_url('master/petty-cash/store') ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="form_type" value="Add">

        <div class="sw-form-section-title">
            <i class="icon-coins" style="margin-right: 6px; color: var(--sw-primary);"></i> Account Designation Details
        </div>

        <div class="row">
            <!-- Customer Select -->
            <div class="col-sm-7">
                <div class="sw-form-group">
                    <label class="sw-label">Select Customer / Vendor Account <span class="req">*</span></label>
                    <select name="private_key" id="private_key" class="select2" style="width: 100%;" required>
                        <option value="">-- Choose Account --</option>
                        <?php if (!empty($availableCustomers)): ?>
                            <?php foreach ($availableCustomers as $cust): ?>
                                <option value="<?= esc($cust['private_key']) ?>" <?= (old('private_key') === $cust['private_key']) ? 'selected' : '' ?>>
                                    <?= esc($cust['cust_name']) ?> (<?= ucfirst(esc($cust['type'])) ?><?= !empty($cust['mobile_no']) ? ' - ' . esc($cust['mobile_no']) : '' ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div id="private_key-error" style="color: #dc2626; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                </div>
            </div>

            <!-- Petty Cash Setting -->
            <div class="col-sm-5">
                <div class="sw-form-group">
                    <label class="sw-label">Designate as Petty Cash? <span class="req">*</span></label>
                    <div class="sw-radio-group">
                        <label class="sw-radio-label">
                            <input type="radio" name="is_petty_cash" value="1" checked> Yes (Petty Cash Account)
                        </label>
                        <label class="sw-radio-label">
                            <input type="radio" name="is_petty_cash" value="0"> No (Standard Account)
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Actions -->
        <div class="sw-form-actions">
            <a href="<?= base_url('master/petty-cash') ?>" class="sw-btn-secondary">Cancel</a>
            <button type="submit" class="sw-btn-primary" id="btnSubmitPettyCash">
                <i class="icon-checkmark4"></i>
                <span id="btnSubmitText">Assign Petty Cash</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="text/javascript">
$(document).ready(function() {
    var $form = $('#assign_petty_cash_form');
    var $btn  = $('#btnSubmitPettyCash');

    $form.on('submit', function(e) {
        e.preventDefault();

        var privateKey = $.trim($('#private_key').val());
        if (!privateKey) {
            $('#private_key-error').text('Please select a customer or vendor account.').show();
            $('#private_key').focus();
            return false;
        }
        $('#private_key-error').hide();

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

                if (resp.trim() === 'true') {
                    swal({
                        title: "Assigned Successfully!",
                        text: "Account has been designated as Petty Cash.",
                        type: "success",
                        confirmButtonColor: "#2563eb",
                        confirmButtonText: "OK"
                    }, function() {
                        window.location.href = "<?= base_url('master/petty-cash') ?>";
                    });
                } else {
                    swal({
                        title: "Failed to Assign",
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
                    text: "An error occurred while saving the designation.",
                    type: "error",
                    confirmButtonColor: "#ef4444"
                });
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
