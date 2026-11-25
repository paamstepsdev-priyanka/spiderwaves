<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Edit Remark - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Edit Remark<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Edit Remark: <?= esc($remark['remark']) ?></h1>
    </div>
    <div class="sw-page-actions">
        <a href="<?= base_url('master/remark') ?>" class="sw-btn-secondary">
            <i class="icon-arrow-left52"></i>
            <span>Back to Remarks</span>
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
    <form id="add_edit_remark_form" action="<?= base_url('master/remark/update/' . $remark['remark_id']) ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="form_type" id="form_type" value="Edit">
        <input type="hidden" name="remark_id" id="remark_id" value="<?= (int)$remark['remark_id'] ?>">
        <input type="hidden" name="base_url" id="base_url" value="<?= base_url() ?>">

        <div class="sw-form-section-title">
            <i class="icon-comment-discussion" style="margin-right: 6px; color: var(--sw-primary);"></i> Edit Remark Details
        </div>

        <div class="row">
            <!-- Remark Text -->
            <div class="col-sm-8">
                <div class="sw-form-group">
                    <label class="sw-label">Remark <span class="req">*</span></label>
                    <textarea class="sw-input" style="height: auto; min-height: 96px; padding: 10px 14px; resize: vertical;" name="remark" id="remark" required autocomplete="off"><?= esc($remark['remark']) ?></textarea>
                    <div id="remark-error" style="color: #dc2626; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                </div>
            </div>

            <!-- Status -->
            <div class="col-sm-4">
                <div class="sw-form-group">
                    <label class="sw-label">Status</label>
                    <div class="sw-radio-group">
                        <label class="sw-radio-label">
                            <input type="radio" name="status" value="1" <?= ($remark['status'] === '1') ? 'checked' : '' ?>> Active
                        </label>
                        <label class="sw-radio-label">
                            <input type="radio" name="status" value="0" <?= ($remark['status'] === '0') ? 'checked' : '' ?>> Inactive
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="margin-top: 28px; padding-top: 18px; border-top: 1px solid var(--sw-border); display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
            <a href="<?= base_url('master/remark') ?>" class="sw-btn-secondary">Cancel</a>
            <button type="submit" class="sw-btn-primary" id="btnSubmitRemark">
                <i class="icon-checkmark4"></i>
                <span id="btnSubmitText">Update Remark</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="text/javascript">
$(document).ready(function() {
    var $form = $('#add_edit_remark_form');
    var $btn  = $('#btnSubmitRemark');

    $form.on('submit', function(e) {
        e.preventDefault();

        var remarkText = $.trim($('#remark').val());

        if (!remarkText) {
            $('#remark-error').text('Remark text is required.').show();
            $('#remark').focus();
            return false;
        } else {
            $('#remark-error').hide();
        }

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
                        text: "Update Successfully!",
                        type: "success",
                        confirmButtonColor: "#2563eb",
                        confirmButtonText: "View Remarks"
                    }, function() {
                        window.location.href = "<?= base_url('master/remark') ?>";
                    });
                } else {
                    $btn.prop('disabled', false).css('opacity', 1);
                    $('#btnSubmitText').text('Update Remark');
                    $('#remark-error').text('Remark text already exists or could not be updated.').show();
                    $('#remark').focus();
                    swal({
                        title: "Error!",
                        text: "Could not update remark.",
                        type: "error",
                        confirmButtonColor: "#ef4444"
                    });
                }
            },
            error: function() {
                $btn.prop('disabled', false).css('opacity', 1);
                $('#btnSubmitText').text('Update Remark');
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
