<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Add User - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Add User<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Add New User</h1>
    </div>
    <div class="sw-page-actions">
        <a href="<?= base_url('master/user') ?>" class="sw-btn-secondary">
            <i class="icon-arrow-left52"></i>
            <span>Back to Users</span>
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
    <form id="add_edit_user_form" action="<?= base_url('master/user/store') ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="form_type" id="form_type" value="Add">
        <input type="hidden" name="base_url" id="base_url" value="<?= base_url() ?>">

        <div class="sw-form-section-title">
            <i class="icon-user-lock" style="margin-right: 6px; color: var(--sw-primary);"></i> Account Credentials &amp; Access Details
        </div>

        <div class="row">
            <!-- Username -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Username <span class="req">*</span></label>
                    <input type="text" class="sw-input" style="padding-left: 14px;" name="user_full_name" id="user_full_name" placeholder="e.g. jsmith or ops_manager" value="<?= esc(old('user_full_name')) ?>" required autofocus autocomplete="off">
                    <div id="user_full_name-error" style="color: #dc2626; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                </div>
            </div>

            <!-- Password -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 2px;">
                        <label class="sw-label" style="margin-bottom: 0;">Password <span class="req">*</span></label>
                        <a href="javascript:void(0)" id="togglePasswordBtn" style="font-size: 12px; font-weight: 600; color: var(--sw-primary); text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="icon-eye" id="togglePasswordIcon"></i>
                            <span id="togglePasswordText">Show</span>
                        </a>
                    </div>
                    <div style="position: relative;">
                        <input type="password" class="sw-input" style="padding-left: 14px; padding-right: 42px;" name="user_password" id="user_password" placeholder="Enter secure password" required minlength="4" autocomplete="new-password">
                        <button type="button" id="togglePasswordBtnInside" title="Show / Hide Password" style="position: absolute; right: 0; top: 0; bottom: 0; width: 40px; border: none; background: transparent; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; outline: none;">
                            <i class="icon-eye" id="togglePasswordIconInside"></i>
                        </button>
                    </div>
                    <div id="user_password-error" style="color: #dc2626; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Access Level -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Access Role <span class="req">*</span></label>
                    <select class="select2" style="width: 100%;" name="user_access_level" id="user_access_level" required>
                        <option value="">Select Role</option>
                        <?php foreach ($roles as $key => $label): ?>
                            <option value="<?= esc($key) ?>" <?= (old('user_access_level') === $key) ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Location -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Location / Hub <span class="req">*</span></label>
                    <select class="select2" style="width: 100%;" name="user_type" id="user_type" required>
                        <option value="">Select Location</option>
                        <?php foreach ($locations as $locKey => $locLabel): ?>
                            <option value="<?= esc($locKey) ?>" <?= (old('user_type') === $locKey) ? 'selected' : '' ?>>
                                <?= esc($locLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Status -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Account Status</label>
                    <div class="sw-radio-group">
                        <label class="sw-radio-label">
                            <input type="radio" name="user_status" value="1" checked> Active
                        </label>
                        <label class="sw-radio-label">
                            <input type="radio" name="user_status" value="0"> Inactive
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Bar -->
        <div class="sw-form-actions">
            <a href="<?= base_url('master/user') ?>" class="sw-btn-secondary">Cancel</a>
            <button type="submit" class="sw-btn-primary" id="btnSubmitUser">
                <i class="icon-checkmark4"></i>
                <span id="btnSubmitText">Create User</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="text/javascript">
$(document).ready(function() {
    var $form = $('#add_edit_user_form');
    var $btn = $('#btnSubmitUser');

    function togglePasswordVisibility() {
        var $pwd = $('#user_password');
        var isPassword = $pwd.attr('type') === 'password';
        if (isPassword) {
            $pwd.attr('type', 'text');
            $('#togglePasswordIcon, #togglePasswordIconInside').removeClass('icon-eye').addClass('icon-eye-blocked');
            $('#togglePasswordText').text('Hide');
        } else {
            $pwd.attr('type', 'password');
            $('#togglePasswordIcon, #togglePasswordIconInside').removeClass('icon-eye-blocked').addClass('icon-eye');
            $('#togglePasswordText').text('Show');
        }
    }

    $('#togglePasswordBtn, #togglePasswordBtnInside').on('click', function(e) {
        e.preventDefault();
        togglePasswordVisibility();
    });

    $form.on('submit', function(e) {
        e.preventDefault();

        var username = $.trim($('#user_full_name').val());
        var password = $('#user_password').val();
        var role     = $('#user_access_level').val();
        var location = $('#user_type').val();

        if (!username) {
            $('#user_full_name-error').text('Username is required.').show();
            $('#user_full_name').focus();
            return false;
        } else {
            $('#user_full_name-error').hide();
        }

        if (!password) {
            $('#user_password-error').text('Password is required.').show();
            $('#user_password').focus();
            return false;
        } else {
            $('#user_password-error').hide();
        }

        if (!role) {
            swal({
                title: "Validation Error",
                text: "Please select an access role for the user.",
                type: "warning",
                confirmButtonColor: "#2563eb"
            });
            return false;
        }

        $btn.prop('disabled', true).css('opacity', 0.7);
        $('#btnSubmitText').text('Saving...');

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
                        text: "User profile created successfully!",
                        type: "success",
                        confirmButtonColor: "#2563eb",
                        confirmButtonText: "View Users"
                    }, function() {
                        window.location.href = "<?= base_url('master/user') ?>";
                    });
                } else {
                    $btn.prop('disabled', false).css('opacity', 1);
                    $('#btnSubmitText').text('Create User');
                    swal({
                        title: "Failed!",
                        text: "Could not create user. The username may already be in use.",
                        type: "error",
                        confirmButtonColor: "#ef4444"
                    });
                }
            },
            error: function() {
                $btn.prop('disabled', false).css('opacity', 1);
                $('#btnSubmitText').text('Create User');
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
