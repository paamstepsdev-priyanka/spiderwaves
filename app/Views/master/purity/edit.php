<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Edit Purity - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Edit Purity<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Edit Purity: <?= esc($purity['purity_name']) ?></h1>
    </div>
    <div class="sw-page-actions">
        <a href="<?= base_url('master/purity') ?>" class="sw-btn-secondary">
            <i class="icon-arrow-left52"></i>
            <span>Back to Purities</span>
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
    <form id="add_edit_purity_form" action="<?= base_url('master/purity/update/' . $purity['private_key']) ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="form_type" id="form_type" value="Edit">
        <input type="hidden" name="private_key" id="private_key" value="<?= esc($purity['private_key']) ?>">
        <input type="hidden" name="base_url" id="base_url" value="<?= base_url() ?>">

        <div class="sw-form-section-title">
            <i class="icon-stars" style="margin-right: 6px; color: var(--sw-primary);"></i> Edit Purity Details
        </div>

        <div class="row">
            <!-- Purity Name / Label -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Purity Name / Label <span class="req">*</span></label>
                    <input type="text" class="sw-input" style="padding-left: 14px;" name="purity_name" id="purity_name" value="<?= esc($purity['purity_name']) ?>" required autocomplete="off">
                    <div id="purity_name-error" style="color: #dc2626; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                </div>
            </div>

            <!-- Purity Percentage Value -->
            <div class="col-sm-3">
                <div class="sw-form-group">
                    <label class="sw-label">Purity Percentage (%) <span class="req">*</span></label>
                    <input type="number" step="0.001" min="0.001" max="100" class="sw-input" style="padding-left: 14px;" name="purity_value" id="purity_value" value="<?= esc($purity['purity_value']) ?>" required autocomplete="off">
                    <div id="purity_value-error" style="color: #dc2626; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                </div>
            </div>

            <!-- Status -->
            <div class="col-sm-3">
                <div class="sw-form-group">
                    <label class="sw-label">Purity Status</label>
                    <div class="sw-radio-group">
                        <label class="sw-radio-label">
                            <input type="radio" name="purity_status" value="1" <?= ($purity['purity_status'] === '1') ? 'checked' : '' ?>> Active
                        </label>
                        <label class="sw-radio-label">
                            <input type="radio" name="purity_status" value="0" <?= ($purity['purity_status'] === '0') ? 'checked' : '' ?>> Inactive
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Bar -->
        <div class="sw-form-actions">
            <a href="<?= base_url('master/purity') ?>" class="sw-btn-secondary">Cancel</a>
            <button type="submit" class="sw-btn-primary" id="btnSubmitPurity">
                <i class="icon-checkmark4"></i>
                <span id="btnSubmitText">Update Purity</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="text/javascript">
$(document).ready(function() {
    var $form = $('#add_edit_purity_form');
    var $btn  = $('#btnSubmitPurity');

    $form.on('submit', function(e) {
        e.preventDefault();

        var purityName  = $.trim($('#purity_name').val());
        var purityValue = parseFloat($('#purity_value').val());

        var hasError = false;

        if (!purityName) {
            $('#purity_name-error').text('Purity name / label is required.').show();
            if (!hasError) $('#purity_name').focus();
            hasError = true;
        } else {
            $('#purity_name-error').hide();
        }

        if (isNaN(purityValue) || purityValue <= 0 || purityValue > 100) {
            $('#purity_value-error').text('Enter a valid purity value between 0.001% and 100%.').show();
            if (!hasError) $('#purity_value').focus();
            hasError = true;
        } else {
            $('#purity_value-error').hide();
        }

        if (hasError) return false;

        $btn.prop('disabled', true).css('opacity', 0.7);
        $('#btnSubmitText').text('Updating...');

        var formData = new FormData($form[0]);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                var res = $.trim(response);
                if (res === 'true' || res.indexOf('true') === 0) {
                    swal({
                        title: "Success!",
                        text: "Purity updated successfully!",
                        type: "success",
                        confirmButtonColor: "#2563eb",
                        confirmButtonText: "View Purities"
                    }, function() {
                        window.location.href = "<?= base_url('master/purity') ?>";
                    });
                } else {
                    $btn.prop('disabled', false).css('opacity', 1);
                    $('#btnSubmitText').text('Update Purity');
                    $('#purity_name-error').text('Purity name or value already exists in another record.').show();
                    $('#purity_name').focus();
                    swal({
                        title: "Error!",
                        text: "Purity Name or Percentage Value Already Exists!",
                        type: "error",
                        confirmButtonColor: "#ef4444"
                    });
                }
            },
            error: function() {
                $btn.prop('disabled', false).css('opacity', 1);
                $('#btnSubmitText').text('Update Purity');
                swal({
                    title: "Network Error",
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

