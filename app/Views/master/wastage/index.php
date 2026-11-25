<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Account Loss Percentage - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Loss Percentage<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Account Loss Percentage</h1>
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

<!-- Account Loss Percentage Form & Table -->
<div class="sw-form-card">
    <form id="account_loss_percentage_form" action="<?= base_url('master/insert_account_loss_percentage') ?>" method="post" class="form-horizontal" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="base_url" id="base_url" value="<?= base_url() ?>">

        <div class="sw-table-card" style="box-shadow: none; border: 1px solid var(--sw-border);">
            <div class="sw-table-wrapper">
                <table class="sw-table">
                    <thead>
                        <tr>
                            <th style="width: 100px;">S.No.</th>
                            <th style="width: 400px;">Account Name</th>
                            <th style="width: 300px;">Loss Percentage</th>
                            <th>&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($stageAccounts)): ?>
                            <?php $index = 1; ?>
                            <?php foreach ($stageAccounts as $obj): ?>
                                <tr>
                                    <td><?= $index++ ?></td>
                                    <td>
                                        <strong style="color: var(--sw-navy); font-size: 14px;"><?= esc($obj->account_name) ?></strong>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <input type="number" 
                                                   step="0.001" 
                                                   min="0" 
                                                   max="100" 
                                                   name="<?= esc($obj->private_key) ?>" 
                                                   id="<?= esc($obj->private_key) ?>" 
                                                   class="sw-input" 
                                                   style="width: 160px; font-weight: 600;" 
                                                   value="<?= esc($obj->loss_percentage) ?>">
                                            <span style="color: var(--sw-slate); font-weight: 600;">%</span>
                                        </div>
                                    </td>
                                    <td>&nbsp;</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 30px; color: var(--sw-slate);">
                                    There is no result for you.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="margin-top: 24px; display: flex; justify-content: flex-end;">
            <button type="submit" class="sw-btn-primary" id="btn_save_loss_percentage">
                <i class="icon-floppy-disk"></i>
                <span id="btn_save_loss_percentage_text">Set</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="text/javascript">
$(document).ready(function() {
    var $form = $('#account_loss_percentage_form');
    var $btn  = $('#btn_save_loss_percentage');

    $form.on('submit', function(e) {
        e.preventDefault();

        var origHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="icon-spinner2 spinner"></i> <span>Saving...</span>');

        var formData = new FormData($form[0]);
        var postUrl  = $form.attr('action') || '<?= base_url('master/insert_account_loss_percentage') ?>';

        $.ajax({
            url: postUrl,
            type: 'POST',
            data: formData,
            dataType: 'text',
            cache: false,
            contentType: false,
            processData: false,
            success: function(resp) {
                $btn.prop('disabled', false).html(origHtml);

                var trimmed = (resp || '').trim();
                if (trimmed === 'true' || trimmed === 'commit') {
                    if (typeof swal === 'function') {
                        swal({
                            title: "Success!",
                            text: "Update Successfully!",
                            confirmButtonColor: "#66BB6A",
                            type: "success"
                        });
                    } else {
                        alert("Update Successfully!");
                    }
                } else {
                    if (typeof swal === 'function') {
                        swal({
                            title: "Error!",
                            text: "Please Try Again!",
                            confirmButtonColor: "#EF5350",
                            type: "error"
                        });
                    } else {
                        alert("Please Try Again!");
                    }
                }
            },
            error: function(xhr, status, error) {
                $btn.prop('disabled', false).html(origHtml);
                if (typeof swal === 'function') {
                    swal({
                        title: "Error!",
                        text: "Please Try Again!",
                        confirmButtonColor: "#EF5350",
                        type: "error"
                    });
                } else {
                    alert("Please Try Again!");
                }
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
