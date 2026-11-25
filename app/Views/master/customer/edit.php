<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Update Customer - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Update Customer<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Update Customer Profile</h1>
    </div>
    <div class="sw-page-actions" style="display: flex; gap: 8px;">
        <a href="<?= base_url('master/customer_wastage_mapping/' . $customer['private_key']) ?>" class="sw-btn-secondary" title="Manage Collection Wastage for this Customer">
            <i class="icon-tab"></i>
            <span>Manage Wastage</span>
        </a>
        <a href="<?= base_url('master/customer') ?>" class="sw-btn-secondary">
            <i class="icon-arrow-left52"></i>
            <span>Back to List</span>
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
    <form id="add_edit_cust_form" action="<?= base_url('master/customer/update/' . $customer['private_key']) ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="form_type" id="form_type" value="Edit">
        <input type="hidden" name="base_url" id="base_url" value="<?= base_url() ?>">
        <input type="hidden" name="private_key" id="private_key" value="<?= esc($customer['private_key']) ?>">

        <div class="sw-form-section-title">
            <i class="icon-profile" style="margin-right: 6px; color: var(--sw-primary);"></i> Profile Details
        </div>

        <div class="row">
            <!-- Customer Name -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Customer Name <span class="req">*</span></label>
                    <input type="text" class="sw-input" style="padding-left: 14px;" name="cust_name" id="cust_name" placeholder="Full Name" value="<?= esc(old('cust_name', $customer['cust_name'])) ?>" required>
                    <div id="cust_name-error" style="color: #dc2626; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                </div>
            </div>

            <!-- Account Type -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Account Classification <span class="req">*</span></label>
                    <select class="select2" style="width: 100%;" name="type" id="type" required>
                        <option value="cust" <?= (old('type', $customer['type']) === 'cust') ? 'selected' : '' ?>>Customer</option>
                        <option value="vendor" <?= (old('type', $customer['type']) === 'vendor') ? 'selected' : '' ?>>Vendor</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Mobile Number -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Mobile Number <span class="req">*</span></label>
                    <input type="text" class="sw-input" style="padding-left: 14px;" name="mobile_no" id="mobile_no" placeholder="10-digit mobile number" value="<?= esc(old('mobile_no', $customer['mobile_no'] ?? '')) ?>" maxlength="12">
                    <div id="mobile_no-error" style="color: #dc2626; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                </div>
            </div>

            <!-- Send SMS Preference -->
            <div class="col-sm-3">
                <div class="sw-form-group">
                    <label class="sw-label">Send SMS Alerts</label>
                    <div class="sw-radio-group">
                        <label class="sw-radio-label">
                            <input type="radio" name="send_sms" value="1" <?= (old('send_sms', $customer['send_sms']) === '1') ? 'checked' : '' ?>> Yes
                        </label>
                        <label class="sw-radio-label">
                            <input type="radio" name="send_sms" value="0" <?= (old('send_sms', $customer['send_sms']) === '0') ? 'checked' : '' ?>> No
                        </label>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="col-sm-3">
                <div class="sw-form-group">
                    <label class="sw-label">Account Status</label>
                    <div class="sw-radio-group">
                        <label class="sw-radio-label">
                            <input type="radio" name="cust_status" value="1" <?= (old('cust_status', $customer['cust_status']) === '1') ? 'checked' : '' ?>> Active
                        </label>
                        <label class="sw-radio-label">
                            <input type="radio" name="cust_status" value="0" <?= (old('cust_status', $customer['cust_status']) === '0') ? 'checked' : '' ?>> Inactive
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="margin-top: 28px; padding-top: 18px; border-top: 1px solid var(--sw-border); display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
            <a href="<?= base_url('master/customer') ?>" class="sw-btn-secondary">Cancel</a>
            <button type="submit" class="sw-btn-primary" id="btnSubmit">
                <i class="icon-checkmark4"></i>
                <span>Update Customer</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $("#add_edit_cust_form").validate({
        rules: {
            cust_name: {
                required: true,
                minlength: 2
            },
            type: {
                required: true
            },
            mobile_no: {
                required: function() {
                    return $('input[name="send_sms"]:checked').val() === '1';
                }
            }
        },
        errorPlacement: function(error, element) {
            error.css({ "color": "#dc2626", "font-size": "11.5px", "margin-top": "4px" });
            error.insertAfter(element);
        },
        submitHandler: function(form) {
            var btn = $('#btnSubmit');
            btn.prop('disabled', true).css('opacity', 0.7);

            var baseUrl = $('#base_url').val();
            var privateKey = $('#private_key').val();
            var url = baseUrl + "master/customer/update/" + encodeURIComponent(privateKey);
            var formData = new FormData($("#add_edit_cust_form")[0]);

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    btn.prop('disabled', false).css('opacity', 1);
                    var isSuccess = false;
                    var message = "Update Successfully!";

                    if (typeof response === 'object') {
                        isSuccess = (response.status === 'success');
                        message = response.message || message;
                    } else {
                        var trimmed = $.trim(response);
                        isSuccess = (trimmed === 'true');
                    }

                    if (isSuccess) {
                        swal({
                            title: "Success!",
                            text: message,
                            confirmButtonColor: "#2563eb",
                            type: "success",
                            closeOnConfirm: false
                        }, function() {
                            location.replace(baseUrl + "master/customer");
                        });
                    } else {
                        var errMsg = (typeof response === 'object' && response.message) ? response.message : "Error Updating Customer!";
                        swal({
                            title: "Error!",
                            text: errMsg,
                            confirmButtonColor: "#ef4444",
                            type: "error"
                        }, function() {
                            $('#cust_name-error').show().html(errMsg);
                            $('#cust_name').focus();
                        });
                    }
                },
                error: function() {
                    btn.prop('disabled', false).css('opacity', 1);
                    swal({
                        title: "Error!",
                        text: "An unexpected server error occurred.",
                        confirmButtonColor: "#ef4444",
                        type: "error"
                    });
                },
                cache: false,
                contentType: false,
                processData: false
            });
            return false;
        }
    });
});
</script>
<?= $this->endSection() ?>
