<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>SpiderWaves - Login</title>

        <!-- Google Fonts: Inter & Roboto -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

        <!-- Theme Stylesheets -->
        <link href="<?= base_url('assets/css/icons/icomoon/styles.css') ?>" rel="stylesheet" type="text/css">
        <link href="<?= base_url('assets/css/minified/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
        <link href="<?= base_url('assets/css/minified/core.min.css') ?>" rel="stylesheet" type="text/css">
        <link href="<?= base_url('assets/css/minified/components.min.css') ?>" rel="stylesheet" type="text/css">
        <link href="<?= base_url('assets/css/minified/colors.min.css') ?>" rel="stylesheet" type="text/css">

        <!-- Core JS files -->
        <script type="text/javascript" src="<?= base_url('assets/js/core/libraries/jquery.min.js') ?>"></script>
        <script type="text/javascript" src="<?= base_url('assets/js/core/libraries/bootstrap.min.js') ?>"></script>

        <style>
            html, body {
                height: 100%;
                margin: 0;
                padding: 0;
            }
            body {
                font-family: 'Inter', 'Roboto', -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #e2e8f0 100%);
                color: #1e293b;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 24px 16px;
            }
            .auth-wrapper {
                width: 100%;
                max-width: 410px;
                margin: auto;
            }
            .login-card {
                background: #ffffff;
                border-radius: 16px;
                box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
                border: 1px solid #e2e8f0;
                padding: 36px 32px 32px 32px;
                transition: box-shadow 0.2s ease-in-out;
            }
            @media (max-width: 480px) {
                .login-card {
                    padding: 28px 20px 24px 20px;
                    border-radius: 14px;
                }
            }
            .brand-badge {
                width: 48px;
                height: 48px;
                border-radius: 12px;
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
            }
            .brand-badge i {
                font-size: 24px;
                color: #ffffff;
            }
            .brand-title {
                font-size: 21px;
                font-weight: 700;
                color: #0f172a;
                margin: 12px 0 2px 0;
                letter-spacing: -0.5px;
            }
            .brand-title .brand-accent {
                color: #2563eb;
            }
            .brand-tagline {
                font-size: 11px;
                color: #64748b;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.8px;
                margin: 0;
            }
            .input-label {
                display: block;
                font-size: 12px;
                font-weight: 600;
                color: #334155;
                margin-bottom: 6px;
                letter-spacing: 0.2px;
                text-transform: uppercase;
            }
            .custom-input {
                height: 44px !important;
                border-radius: 8px !important;
                border: 1px solid #cbd5e1 !important;
                font-size: 14px !important;
                padding-left: 42px !important;
                background-color: #f8fafc !important;
                color: #0f172a !important;
                transition: all 0.2s ease-in-out !important;
            }
            .custom-input:focus {
                background-color: #ffffff !important;
                border-color: #2563eb !important;
                box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
                outline: none !important;
            }
            .custom-feedback {
                height: 44px !important;
                line-height: 44px !important;
                width: 44px !important;
            }
            .custom-feedback i {
                font-size: 16px !important;
                color: #94a3b8 !important;
                transition: color 0.2s ease-in-out;
            }
            .has-feedback:focus-within .custom-feedback i {
                color: #2563eb !important;
            }
            .btn-signin {
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
                border: none !important;
                color: #ffffff !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                height: 44px !important;
                line-height: 44px !important;
                padding: 0 20px !important;
                border-radius: 8px !important;
                letter-spacing: 0.3px !important;
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15) !important;
                transition: all 0.2s ease-in-out !important;
                display: block !important;
                width: 100% !important;
            }
            .btn-signin:hover, .btn-signin:focus {
                background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
                box-shadow: 0 6px 16px rgba(15, 23, 42, 0.22) !important;
                transform: translateY(-1px);
                color: #ffffff !important;
            }
            .btn-signin:active {
                transform: translateY(0);
                box-shadow: 0 2px 8px rgba(15, 23, 42, 0.15) !important;
            }
            .custom-alert {
                border-radius: 8px;
                padding: 10px 14px;
                font-size: 13px;
                margin-bottom: 18px;
                background-color: #fef2f2;
                border: 1px solid #fecaca;
                color: #b91c1c;
                display: flex;
                align-items: center;
            }
            .custom-alert .close {
                color: #b91c1c;
                opacity: 0.6;
                font-size: 18px;
                margin-left: auto;
            }
            .custom-alert .close:hover {
                opacity: 1;
            }
            .auth-footer-tag {
                margin-top: 22px;
                padding-top: 16px;
                border-top: 1px solid #f1f5f9;
                font-size: 12px;
                color: #64748b;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
            }
            .auth-footer-tag i {
                font-size: 14px;
                color: #2563eb;
            }
            .page-footer {
                margin-top: 24px;
                font-size: 12px;
                color: #94a3b8;
                text-align: center;
            }
            .page-footer a {
                color: #64748b;
                text-decoration: none;
                font-weight: 500;
            }
            .page-footer a:hover {
                color: #0f172a;
            }
            .custom-input.error,
            .custom-input.is-invalid {
                border-color: #ef4444 !important;
                box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.15) !important;
            }
            .validation-error-label:before,
            .validation-valid-label:before {
                display: none !important;
                content: "" !important;
            }
            .validation-error-label {
                padding-left: 0 !important;
                color: #ef4444 !important;
                font-size: 11.5px !important;
                font-weight: 500 !important;
                margin-top: 5px !important;
                display: block !important;
            }
        </style>
    </head>

    <body>
        <div class="auth-wrapper">
            <div class="login-card">

                <!-- Header / Branding -->
                <div class="text-center" style="margin-bottom: 26px;">
                    <div class="brand-badge">
                        <i class="icon-shield-check"></i>
                    </div>
                    <h3 class="brand-title">Spider<span class="brand-accent">Waves</span></h3>
                    <p class="brand-tagline">Workshop &amp; Precious Metal System</p>
                </div>

                <!-- Error Notification -->
                <?php if (!empty($hasError)): ?>
                    <div class="custom-alert">
                        <i class="icon-warning2 position-left" style="margin-right: 8px;"></i>
                        <span><strong>Error:</strong> <?= esc($errorMessage ?? 'Invalid username or password.') ?></span>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form action="<?= base_url('login/authenticate') ?>" method="post" autocomplete="off" class="form-validate" novalidate>
                    <?= csrf_field() ?>

                    <!-- Username Field -->
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label class="input-label" for="user_name">Username</label>
                        <div class="has-feedback has-feedback-left">
                            <input type="text" name="user_name" id="user_name" class="form-control custom-input" placeholder="Enter your username" required autofocus>
                            <div class="form-control-feedback custom-feedback">
                                <i class="icon-user"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group" style="margin-bottom: 22px;">
                        <label class="input-label" for="password">Password</label>
                        <div class="has-feedback has-feedback-left">
                            <input type="password" name="password" id="password" class="form-control custom-input" placeholder="Enter your password" required>
                            <div class="form-control-feedback custom-feedback">
                                <i class="icon-lock2"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <button type="submit" class="btn btn-signin">
                            Sign In <i class="icon-circle-right2 position-right"></i>
                        </button>
                    </div>

                    <!-- Trust / Session Tag -->
                    <div class="auth-footer-tag">
                        <i class="icon-shield-check"></i>
                        <span>Secure Workshop Session</span>
                    </div>
                </form>

            </div>

            <!-- Page Footer -->
            <div class="page-footer">
                &copy; <?= date('Y') ?> <span style="font-weight: 600; color: #475569;">SpiderWaves</span> &bull; All rights reserved.
            </div>
        </div>

        <!-- Theme Validation & Plugins -->
        <script type="text/javascript" src="<?= base_url('assets/js/plugins/forms/validation/validate.min.js') ?>"></script>
        <script type="text/javascript" src="<?= base_url('assets/js/core/app.js') ?>"></script>

        <script>
        $('.form-validate').validate({
            ignore: 'input[type=hidden], .select2-input',
            errorClass: 'validation-error-label',
            successClass: 'validation-valid-label',
            highlight: function(element, errorClass) {
                $(element).addClass('error is-invalid');
            },
            unhighlight: function(element, errorClass) {
                $(element).removeClass('error is-invalid');
            },
            errorPlacement: function(error, element) {
                if (element.parents('div').hasClass("has-feedback")) {
                    error.insertAfter(element.closest('.has-feedback'));
                } else {
                    error.insertAfter(element);
                }
            },
            validClass: "validation-valid-label",
            rules: {
                user_name: {
                    required: true
                },
                password: {
                    required: true
                }
            },
            messages: {
                user_name: {
                    required: "Please enter your username"
                },
                password: {
                    required: "Please enter your password"
                }
            }
        });
        </script>
    </body>
</html>
