<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $this->renderSection('title') ?: 'SpiderWaves Enterprise' ?></title>

        <!-- Google Fonts: Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Theme Icons & Bootstrap -->
        <link href="<?= base_url('assets/css/icons/icomoon/styles.css') ?>" rel="stylesheet" type="text/css">
        <link href="<?= base_url('assets/css/minified/bootstrap.min.css') ?>" rel="stylesheet" type="text/css">
        <link href="<?= base_url('assets/css/minified/core.min.css') ?>" rel="stylesheet" type="text/css">
        <link href="<?= base_url('assets/css/minified/components.min.css') ?>" rel="stylesheet" type="text/css">
        <link href="<?= base_url('assets/css/minified/colors.min.css') ?>" rel="stylesheet" type="text/css">
        <link href="<?= base_url('assets/css/custom.css') ?>?v=20261102" rel="stylesheet" type="text/css">

        <!-- SpiderWaves 2026 Modern Theme Stylesheet -->
        <link href="<?= base_url('assets/css/modern_theme.css') ?>?v=20261107_pagination_fix" rel="stylesheet" type="text/css">
        
        <!-- Core JS files -->
        <script type="text/javascript" src="<?= base_url('assets/js/core/libraries/jquery.min.js') ?>"></script>
        <script type="text/javascript" src="<?= base_url('assets/js/core/libraries/bootstrap.min.js') ?>"></script>
        <script type="text/javascript" src="<?= base_url('assets/js/plugins/loaders/blockui.min.js') ?>"></script>
        <script type="text/javascript" src="<?= base_url('assets/js/plugins/forms/selects/select2.min.js') ?>"></script>
        <script type="text/javascript" src="<?= base_url('assets/js/plugins/forms/styling/uniform.min.js') ?>"></script>
        <script type="text/javascript" src="<?= base_url('assets/js/plugins/forms/validation/validate.min.js') ?>"></script>
        <script type="text/javascript" src="<?= base_url('assets/js/plugins/notifications/sweet_alert.min.js') ?>"></script>
        <!-- SpiderWaves Universal Form Validation -->
        <script type="text/javascript" src="<?= base_url('assets/js/sw_validation.js') ?>?v=20261124_v2"></script>
    </head>

    <body class="modern-theme">
        <input type="hidden" name="base_url" id="base_url" value="<?= base_url() ?>">
        <?php 
            $sessionData = session()->get('spider') ?? []; 
            $username    = $sessionData['user'] ?? 'User';
            $accessLevel = $sessionData['access_level'] ?? '';
            $userId      = !empty($sessionData['user_id']) ? (int)$sessionData['user_id'] : null;
            $initial     = strtoupper(substr($username, 0, 1));
            $roleLabel   = ucwords(str_replace('_', ' ', $accessLevel));
            $pendingCount = get_number_of_pending_raw_waves();
            $processNavAccounts = model(\App\Models\AccountModel::class)->getProcessNavigationAccounts($accessLevel, $userId);
            $lossNavAccounts    = model(\App\Models\AccountModel::class)->getLossNavigationAccounts($accessLevel, $userId);

            // Helpers for dynamic menu visibility
            $showDashboard      = can_menu('main_dashboard', $userId, $accessLevel);
            $showWaves          = can_menu('ops_waves', $userId, $accessLevel);
            $showProcess        = can_menu('ops_process', $userId, $accessLevel) && !empty($processNavAccounts);
            $showLocker         = can_menu('ops_locker', $userId, $accessLevel);
            $showLockerVerif    = can_menu('ops_locker_verification', $userId, $accessLevel);
            $showLoss           = can_menu('ops_loss', $userId, $accessLevel) && !empty($lossNavAccounts);
            $showRefining       = can_menu('ops_refining', $userId, $accessLevel);
            $showStock          = can_menu('ops_stock', $userId, $accessLevel);

            $showOperations     = $showWaves || $showProcess || $showLocker || $showLockerVerif || $showLoss || $showRefining || $showStock;

            $showMasterMenu       = can_menu('masters', $userId, $accessLevel);
            $showSystemMasterMenu = can_menu('system_masters', $userId, $accessLevel)
                || can_menu('master_customer', $userId, $accessLevel)
                || can_menu('master_customer_mapping', $userId, $accessLevel)
                || can_menu('master_collection', $userId, $accessLevel)
                || can_menu('master_purity', $userId, $accessLevel);
            $showAccountMenu      = can_menu('account_master', $userId, $accessLevel);
            $showPermMenu         = can_menu('permission_management', $userId, $accessLevel) || can('permission.superadmin', $userId, $accessLevel);
            $showReportMenu       = can_menu('reports', $userId, $accessLevel);
            $showMastersSection   = $showMasterMenu || $showSystemMasterMenu || $showAccountMenu || $showPermMenu || $showReportMenu;
        ?>

        <div class="sw-app-wrapper">
            <!-- Modern Sidebar -->
            <aside class="sw-sidebar" id="swSidebar">
                <!-- Brand Header -->
                <a href="<?= ($accessLevel === 'cust') ? base_url('report/customer_ledger_for_customer/' . ($sessionData['private_key'] ?? '')) : (($accessLevel === 'user') ? base_url('raw-waves') : base_url('dashboard')) ?>" class="sw-sidebar-brand">
                    <span class="sw-brand-name">Spider<span>Waves</span></span>
                </a>

                <!-- Navigation List -->
                <div class="sw-sidebar-nav">
                    <?php if ($showDashboard): ?>
                    <!-- Main Section -->
                    <div class="sw-nav-section-title">Main</div>
                    <ul class="sw-nav-list">
                        <li class="sw-nav-item <?= (url_is('dashboard') || url_is('dashboard/*')) ? 'active' : '' ?>">
                            <a href="<?= base_url('dashboard') ?>" class="sw-nav-link">
                                <i class="icon-grid"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                    </ul>
                    <?php endif; ?>

                    <?php if ($showOperations): ?>
                    <!-- Operations Section -->
                    <div class="sw-nav-section-title">Operations</div>
                    <ul class="sw-nav-list">
                        <?php if ($showWaves): ?>
                        <li class="sw-nav-item <?= (url_is('raw_waves*') || url_is('raw-waves*')) ? 'active' : '' ?>">
                            <a href="<?= base_url('raw-waves/manage') ?>" class="sw-nav-link">
                                <i class="icon-loop4"></i>
                                <span style="flex: 1;">Waves</span>
                                <?php if ($pendingCount > 0): ?>
                                    <span class="sw-pill-badge sw-badge-danger" style="margin-left: auto; font-size: 11px; padding: 2px 7px; font-weight: 700;">
                                        <?= $pendingCount ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- Process Department Dropdown Menu -->
                        <?php if ($showProcess): ?>
                        <?php 
                            $isProcessActive = (url_is('process-waves*') || url_is('process_waves*')) 
                                && !url_is('*locker*') 
                                && !url_is('*3442203db2c3bf62b928ac74f3d64b88*') 
                                && !url_is('*860d4bcf649e0a2393ef85ae72cb2dd9*'); 
                        ?>
                        <li class="sw-nav-item">
                            <a href="#menuProcess" class="sw-nav-link" data-toggle="collapse" aria-expanded="<?= $isProcessActive ? 'true' : 'false' ?>" style="<?= $isProcessActive ? 'background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff; font-weight: 600;' : '' ?>">
                                <i class="icon-sync" style="<?= $isProcessActive ? 'color: #ffffff;' : '' ?>"></i>
                                <span style="flex: 1;">Process</span>
                                <i class="icon-arrow-down5 sw-nav-arrow" style="font-size: 11px; margin-left: auto; transition: transform 0.2s ease-in-out; <?= $isProcessActive ? 'color: #ffffff; transform: rotate(180deg);' : '' ?>"></i>
                            </a>
                            <ul id="menuProcess" class="collapse <?= $isProcessActive ? 'in' : '' ?> sw-nav-list" style="padding-left: 12px; margin-top: 2px;">
                                <?php foreach ($processNavAccounts as $pAccount): ?>
                                    <?php 
                                        $isCurProcess = url_is('*' . $pAccount['private_key'] . '*') || ((url_is('process-waves') || url_is('process_waves')) && (int)$pAccount['account_id'] === 2);
                                    ?>
                                    <li class="sw-nav-item <?= $isCurProcess ? 'active' : '' ?>">
                                        <a href="<?= base_url('process-waves/manage/' . $pAccount['private_key']) ?>" class="sw-nav-link" style="padding: 7px 12px 7px 18px; font-size: 13px;">
                                            <i class="icon-primitive-dot <?= $isCurProcess ? 'text-white' : 'text-muted' ?>" style="font-size: 8px; width: 14px;"></i>
                                            <span><?= esc($pAccount['display_name']) ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <?php endif; ?>

                        <?php if ($showLocker): ?>
                        <li class="sw-nav-item <?= (url_is('locker') || (url_is('locker/*') && !url_is('*stock*verification*')) || url_is('*87890fca66eacc10c9c7cf440a956262*')) ? 'active' : '' ?>">
                            <a href="<?= base_url('locker') ?>" class="sw-nav-link">
                                <i class="icon-safe"></i>
                                <span>Locker</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if ($showLockerVerif): ?>
                        <li class="sw-nav-item <?= (url_is('*locker*stock*verification*') || url_is('*locker_stock_verification*')) ? 'active' : '' ?>">
                            <a href="<?= base_url('process_waves/locker_stock_verification') ?>" class="sw-nav-link">
                                <i class="icon-checkmark-circle"></i>
                                <span>Locker Stock Verification</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- Loss Module Dropdown Menu -->
                        <?php if ($showLoss): ?>
                        <?php $isLossActive = (url_is('loss*') || url_is('process-waves/manage/3442203db2c3bf62b928ac74f3d64b88*') || url_is('process_waves/manage/3442203db2c3bf62b928ac74f3d64b88*')); ?>
                        <li class="sw-nav-item">
                            <a href="#menuLoss" class="sw-nav-link" data-toggle="collapse" aria-expanded="<?= $isLossActive ? 'true' : 'false' ?>" style="<?= $isLossActive ? 'background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff; font-weight: 600;' : '' ?>">
                                <i class="icon-pulse2" style="<?= $isLossActive ? 'color: #ffffff;' : '' ?>"></i>
                                <span style="flex: 1;">Loss</span>
                                <i class="icon-arrow-down5 sw-nav-arrow" style="font-size: 11px; margin-left: auto; transition: transform 0.2s ease-in-out; <?= $isLossActive ? 'color: #ffffff; transform: rotate(180deg);' : '' ?>"></i>
                            </a>
                            <ul id="menuLoss" class="collapse <?= $isLossActive ? 'in' : '' ?> sw-nav-list" style="padding-left: 12px; margin-top: 2px;">
                                <?php foreach ($lossNavAccounts as $lAccount): ?>
                                    <?php 
                                        $isCurLoss = (url_is('loss/manage/' . $lAccount['private_key'] . '*') 
                                            || url_is('process-waves/manage/3442203db2c3bf62b928ac74f3d64b88/' . $lAccount['private_key'] . '*')
                                            || url_is('process_waves/manage/3442203db2c3bf62b928ac74f3d64b88/' . $lAccount['private_key'] . '*'));
                                    ?>
                                    <li class="sw-nav-item <?= $isCurLoss ? 'active' : '' ?>">
                                        <a href="<?= base_url('loss/manage/' . $lAccount['private_key']) ?>" class="sw-nav-link" style="padding: 7px 12px 7px 18px; font-size: 13px;">
                                            <i class="icon-primitive-dot <?= $isCurLoss ? 'text-white' : 'text-muted' ?>" style="font-size: 8px; width: 14px;"></i>
                                            <span><?= esc($lAccount['loss_title'] ?? $lAccount['display_name'] ?? $lAccount['account_name']) ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <?php endif; ?>

                        <!-- Refining Module -->
                        <?php if ($showRefining): ?>
                        <?php $isRefiningActive = (url_is('refining*') || url_is('process-waves/manage/860d4bcf649e0a2393ef85ae72cb2dd9*') || url_is('process_waves/manage/860d4bcf649e0a2393ef85ae72cb2dd9*')); ?>
                        <li class="sw-nav-item <?= $isRefiningActive ? 'active' : '' ?>">
                            <a href="<?= base_url('refining') ?>" class="sw-nav-link">
                                <i class="icon-loop4"></i>
                                <span>Refining</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- Stock Module -->
                        <?php if ($showStock): ?>
                        <?php $isStockActive = (url_is('stock*') || url_is('report/stock-report*') || url_is('report/stock_report*') || url_is('report/loss_report*') || url_is('report/loss-report*')); ?>
                        <li class="sw-nav-item <?= $isStockActive ? 'active' : '' ?>">
                            <a href="<?= base_url('stock') ?>" class="sw-nav-link">
                                <i class="icon-box"></i>
                                <span>Stock</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <?php endif; ?>

                    <!-- Masters Section -->
                    <?php if ($showMastersSection): ?>
                    <div class="sw-nav-section-title">Masters</div>
                    <ul class="sw-nav-list" style="padding-bottom: 100px;">
                        <!-- Masters Dropdown -->
                        <?php if ($showMasterMenu): ?>
                        <?php 
                            $isAccountActive = (url_is('account*') || url_is('master/account*') || url_is('master/map_with_account*'));
                            $isSystemMasterActive = url_is('master/customer') || url_is('master/customer/*')
                                || url_is('master/manage_cust*') || url_is('master/add_cust*') || url_is('master/edit_cust*')
                                || url_is('master/customer-mapping*') || url_is('master/customer_mapping*')
                                || url_is('master/manage_cust_mapping*') || url_is('master/add_cust_mapping*') || url_is('master/edit_cust_mapping*')
                                || url_is('master/collection*') || url_is('master/manage_collection*') || url_is('master/add_collection*') || url_is('master/edit_collection*')
                                || url_is('master/purity*') || url_is('master/manage_purity*') || url_is('master/add_purity*') || url_is('master/edit_purity*')
                                || url_is('master/ucc-wastage*')
                                || url_is('master/ucc-revalidate*');
                            $isRecentFileActive = url_is('master/*recent*') || url_is('master/recent_*');
                            $isMasterActive = (url_is('master') || url_is('master/*')) && !$isAccountActive && !$isSystemMasterActive && !$isRecentFileActive;
                        ?>
                        <li class="sw-nav-item">
                            <a href="#menuMasters" class="sw-nav-link" data-toggle="collapse" aria-expanded="<?= $isMasterActive ? 'true' : 'false' ?>" style="<?= $isMasterActive ? 'color: #f8fafc;' : '' ?>">
                                <i class="icon-gear"></i>
                                <span style="flex: 1;">Master</span>
                                <i class="icon-arrow-down5 sw-nav-arrow" style="font-size: 11px; margin-left: auto; transition: transform 0.2s ease-in-out; <?= $isMasterActive ? 'transform: rotate(180deg);' : '' ?>"></i>
                            </a>
                            <ul id="menuMasters" class="collapse <?= $isMasterActive ? 'in' : '' ?> sw-nav-list" style="padding-left: 12px; margin-top: 2px;">
                                <?php if (can_menu('master_loss_percentage', $userId, $accessLevel)): ?>
                                <li class="sw-nav-item <?= (url_is('master/loss_percentage*') || url_is('master/loss-percentage*') || url_is('master/set_account_loss_percentage*') || url_is('master/wastage*')) ? 'active' : '' ?>">
                                    <a href="<?= base_url('master/loss_percentage') ?>" class="sw-nav-link" style="padding: 7px 12px; font-size: 13px;">
                                        <i class="icon-primitive-dot text-muted" style="font-size: 8px; width: 14px;"></i>
                                        <span>Loss Percentage</span>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php if (can_menu('master_remark', $userId, $accessLevel)): ?>
                                <li class="sw-nav-item <?= (url_is('master/remark*') || url_is('master/manage_remark*') || url_is('master/add_remark*') || url_is('master/edit_remark*')) ? 'active' : '' ?>">
                                    <a href="<?= base_url('master/remark') ?>" class="sw-nav-link" style="padding: 7px 12px; font-size: 13px;">
                                        <i class="icon-primitive-dot text-muted" style="font-size: 8px; width: 14px;"></i>
                                        <span>Remark</span>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php if (can_menu('master_user', $userId, $accessLevel)): ?>
                                <li class="sw-nav-item <?= (url_is('master/user*') || url_is('master/manage_user*') || url_is('master/add_user*') || url_is('master/edit_user*')) ? 'active' : '' ?>">
                                    <a href="<?= base_url('master/user') ?>" class="sw-nav-link" style="padding: 7px 12px; font-size: 13px;">
                                        <i class="icon-primitive-dot text-muted" style="font-size: 8px; width: 14px;"></i>
                                        <span>User</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <?php endif; ?>

                        <!-- System Master Dropdown -->
                        <?php if ($showSystemMasterMenu): ?>
                        <li class="sw-nav-item">
                            <a href="#menuSystemMasters" class="sw-nav-link" data-toggle="collapse" aria-expanded="<?= $isSystemMasterActive ? 'true' : 'false' ?>" style="<?= $isSystemMasterActive ? 'color: #f8fafc;' : '' ?>">
                                <i class="icon-database"></i>
                                <span style="flex: 1;">System Master</span>
                                <i class="icon-arrow-down5 sw-nav-arrow" style="font-size: 11px; margin-left: auto; transition: transform 0.2s ease-in-out; <?= $isSystemMasterActive ? 'transform: rotate(180deg);' : '' ?>"></i>
                            </a>
                            <ul id="menuSystemMasters" class="collapse <?= $isSystemMasterActive ? 'in' : '' ?> sw-nav-list" style="padding-left: 12px; margin-top: 2px;">
                                <?php if (can_menu('master_customer', $userId, $accessLevel)): ?>
                                <li class="sw-nav-item <?= ((url_is('master/customer') || url_is('master/customer/*') || url_is('master/manage_cust') || url_is('master/add_cust') || url_is('master/edit_cust/*')) && !url_is('master/customer-mapping*') && !url_is('master/customer_mapping*')) ? 'active' : '' ?>">
                                    <a href="<?= base_url('master/customer') ?>" class="sw-nav-link" style="padding: 7px 12px; font-size: 13px;">
                                        <i class="icon-primitive-dot text-muted" style="font-size: 8px; width: 14px;"></i>
                                        <span>Customer</span>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php if (can_menu('master_customer_mapping', $userId, $accessLevel)): ?>
                                <li class="sw-nav-item <?= (url_is('master/customer-mapping*') || url_is('master/customer_mapping*') || url_is('master/manage_cust_mapping*') || url_is('master/add_cust_mapping*') || url_is('master/edit_cust_mapping*')) ? 'active' : '' ?>">
                                    <a href="<?= base_url('master/customer-mapping') ?>" class="sw-nav-link" style="padding: 7px 12px; font-size: 13px;">
                                        <i class="icon-primitive-dot text-muted" style="font-size: 8px; width: 14px;"></i>
                                        <span>Mapped Customer</span>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php if (can_menu('master_collection', $userId, $accessLevel)): ?>
                                <li class="sw-nav-item <?= (url_is('master/collection*') || url_is('master/manage_collection*') || url_is('master/add_collection*') || url_is('master/edit_collection*')) ? 'active' : '' ?>">
                                    <a href="<?= base_url('master/collection') ?>" class="sw-nav-link" style="padding: 7px 12px; font-size: 13px;">
                                        <i class="icon-primitive-dot text-muted" style="font-size: 8px; width: 14px;"></i>
                                        <span>Collection</span>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php if (can_menu('master_purity', $userId, $accessLevel)): ?>
                                <li class="sw-nav-item <?= (url_is('master/purity*') || url_is('master/manage_purity*') || url_is('master/add_purity*') || url_is('master/edit_purity*')) ? 'active' : '' ?>">
                                    <a href="<?= base_url('master/purity') ?>" class="sw-nav-link" style="padding: 7px 12px; font-size: 13px;">
                                        <i class="icon-primitive-dot text-muted" style="font-size: 8px; width: 14px;"></i>
                                        <span>Purity</span>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php if (can_menu('master_ucc_wastage', $userId, $accessLevel)): ?>
                                <li class="sw-nav-item <?= url_is('master/ucc-wastage*') ? 'active' : '' ?>">
                                    <a href="<?= base_url('master/ucc-wastage') ?>" class="sw-nav-link" style="padding: 7px 12px; font-size: 13px;">
                                        <i class="icon-primitive-dot text-muted" style="font-size: 8px; width: 14px;"></i>
                                        <span>Wastage</span>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php if (can_menu('master_ucc_revalidate', $userId, $accessLevel)): ?>
                                <li class="sw-nav-item <?= url_is('master/ucc-revalidate*') ? 'active' : '' ?>">
                                    <a href="<?= base_url('master/ucc-revalidate') ?>" class="sw-nav-link" style="padding: 7px 12px; font-size: 13px;">
                                        <i class="icon-primitive-dot text-muted" style="font-size: 8px; width: 14px;"></i>
                                        <span>UCC Sync &amp; Verify</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <?php endif; ?>

                        <!-- Account Module -->
                        <?php if ($showAccountMenu): ?>
                        <li class="sw-nav-item <?= (url_is('account*') || url_is('master/account*') || url_is('master/map_with_account*')) ? 'active' : '' ?>">
                            <a href="<?= base_url('account') ?>" class="sw-nav-link">
                                <i class="icon-office"></i>
                                <span>Account</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- Permission Management Module (Enterprise RBAC) -->
                        <?php if ($showPermMenu): ?>
                        <li class="sw-nav-item <?= url_is('permission*') ? 'active' : '' ?>">
                            <a href="<?= base_url('permission') ?>" class="sw-nav-link">
                                <i class="icon-shield-check"></i>
                                <span>Permissions</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <!-- Reports Dropdown -->
                        <?php if ($showReportMenu): ?>
                        <?php $isReportActive = (url_is('report/cust_wise_report*') || url_is('report/cust-wise-report*') || url_is('report/customer_ledger*') || url_is('report/customer-ledger*') || url_is('report/daily_report*')); ?>
                        <li class="sw-nav-item">
                            <a href="#menuReports" class="sw-nav-link" data-toggle="collapse" aria-expanded="<?= $isReportActive ? 'true' : 'false' ?>" style="<?= $isReportActive ? 'color: #f8fafc;' : '' ?>">
                                <i class="icon-file-spreadsheet"></i>
                                <span style="flex: 1;">Reports</span>
                                <i class="icon-arrow-down5 sw-nav-arrow" style="font-size: 11px; margin-left: auto; transition: transform 0.2s ease-in-out; <?= $isReportActive ? 'transform: rotate(180deg);' : '' ?>"></i>
                            </a>
                            <ul id="menuReports" class="collapse <?= $isReportActive ? 'in' : '' ?> sw-nav-list" style="padding-left: 12px; margin-top: 2px;">
                                <?php if (can_menu('report_cust_wise', $userId, $accessLevel)): ?>
                                <li class="sw-nav-item <?= (url_is('report/cust_wise_report*') || url_is('report/cust-wise-report*') || url_is('report/customer_ledger*') || url_is('report/customer-ledger*')) ? 'active' : '' ?>">
                                    <a href="<?= base_url('report/cust_wise_report') ?>" class="sw-nav-link" style="padding: 7px 12px; font-size: 13px;">
                                        <i class="icon-primitive-dot text-muted" style="font-size: 8px; width: 14px;"></i>
                                        <span>Cust Wise Report</span>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php if (can_menu('report_daily', $userId, $accessLevel)): ?>
                                <li class="sw-nav-item <?= url_is('report/daily_report*') ? 'active' : '' ?>">
                                    <a href="<?= base_url('report/daily_report') ?>" class="sw-nav-link" style="padding: 7px 12px; font-size: 13px;">
                                        <i class="icon-primitive-dot text-muted" style="font-size: 8px; width: 14px;"></i>
                                        <span>Daily Report</span>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </aside>
            <!-- /sidebar -->

            <!-- Main Content Area -->
            <div class="sw-main-wrapper">
                <!-- Modern Topbar Navigation -->
                <header class="sw-topbar">
                    <div class="sw-topbar-left">
                        <button type="button" class="sw-sidebar-toggle-btn" id="sidebarToggle" title="Toggle Sidebar">
                            <i class="icon-paragraph-justify3"></i>
                        </button>
                        <div class="sw-topbar-breadcrumbs">
                            <?php if ($accessLevel === 'cust'): ?>
                                <span class="current"><?= $this->renderSection('breadcrumb') ?: 'Customer Statement' ?></span>
                            <?php elseif ($accessLevel === 'user'): ?>
                                <a href="<?= base_url('raw-waves') ?>">Raw Waves</a>
                                <span class="divider">/</span>
                                <span class="current"><?= $this->renderSection('breadcrumb') ?: 'Inward Queue' ?></span>
                            <?php else: ?>
                                <a href="<?= base_url('dashboard') ?>">Dashboard</a>
                                <span class="divider">/</span>
                                <span class="current"><?= $this->renderSection('breadcrumb') ?: 'Overview' ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="sw-topbar-right">
                        <!-- Pending Raw Waves Alert Badge (Governed by navbar_pending_badge menu permission) -->
                        <?php if (can_menu('navbar_pending_badge', $userId, $accessLevel) && $pendingCount > 0): ?>
                        <a href="<?= base_url('raw-waves/manage') ?>" class="sw-pending-badge" title="Pending Raw Waves Inward Queue">
                            <span class="sw-pulse-dot"></span>
                            <span>Pending : <?= $pendingCount ?></span>
                        </a>
                        <?php endif; ?>

                        <!-- User Profile Menu (Always visible for all authenticated roles) -->
                        <?php if (!empty($username) || !empty($userId)): ?>
                        <div class="dropdown sw-profile-dropdown">
                            <button type="button" class="sw-profile-btn dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <div class="sw-avatar"><?= $initial ?></div>
                                <div class="sw-user-meta hidden-xs">
                                    <span class="sw-username"><?= esc($username) ?></span>
                                    <span class="sw-userrole"><?= esc($roleLabel) ?></span>
                                </div>
                                <i class="icon-arrow-down5 text-muted" style="font-size: 11px;"></i>
                            </button>

                            <ul class="dropdown-menu dropdown-menu-right" style="border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; min-width: 180px; padding: 6px 0;">
                                <li style="padding: 8px 16px; border-bottom: 1px solid #f1f5f9;">
                                    <div style="font-weight: 600; font-size: 13px; color: #0f172a;"><?= esc($username) ?></div>
                                    <div style="font-size: 11px; color: #64748b;"><?= esc($roleLabel) ?></div>
                                </li>
                                <li style="padding: 4px 0;">
                                    <a href="<?= base_url('logout') ?>" style="padding: 8px 16px; font-size: 13px; color: #dc2626; display: flex; align-items: center; gap: 8px;">
                                        <i class="icon-switch2"></i> Sign Out
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </header>
                <!-- /topbar -->

                <!-- Page Content -->
                <main class="sw-page-container">
                    <?= $this->renderSection('content') ?>
                </main>
            </div>
            <!-- /main wrapper -->
        </div>

        <script>
            // Universal Sidebar Toggle (Desktop Collapse & Mobile Drawer)
            var sidebarToggle = document.getElementById('sidebarToggle');
            var swSidebar = document.getElementById('swSidebar');

            if (sidebarToggle && swSidebar) {
                // Restore saved desktop collapsed state on page load
                try {
                    if (window.innerWidth > 768 && localStorage.getItem('sw_sidebar_collapsed') === '1') {
                        document.body.classList.add('sidebar-collapsed');
                        swSidebar.classList.add('collapsed');
                    }
                } catch(e) {}

                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (window.innerWidth <= 768) {
                        // Mobile: Toggle open drawer
                        swSidebar.classList.toggle('open');
                        document.body.classList.toggle('sw-sidebar-mobile-open');
                    } else {
                        // Desktop: Toggle collapse/expand
                        var isCollapsed = document.body.classList.toggle('sidebar-collapsed');
                        swSidebar.classList.toggle('collapsed', isCollapsed);
                        try {
                            if (isCollapsed) {
                                localStorage.setItem('sw_sidebar_collapsed', '1');
                            } else {
                                localStorage.removeItem('sw_sidebar_collapsed');
                            }
                        } catch(e) {}
                    }
                });

                // Mobile: Close drawer when tapping outside
                document.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768 && swSidebar.classList.contains('open')) {
                        if (!swSidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                            swSidebar.classList.remove('open');
                            document.body.classList.remove('sw-sidebar-mobile-open');
                        }
                    }
                });
            }

            // Sidebar Dropdown Arrow Animation on Bootstrap Collapse
            $('#menuMasters, #menuSystemMasters, #menuReports').on('show.bs.collapse', function() {
                $(this).prev('.sw-nav-link').find('.sw-nav-arrow').css('transform', 'rotate(180deg)');
            }).on('hide.bs.collapse', function() {
                $(this).prev('.sw-nav-link').find('.sw-nav-arrow').css('transform', 'rotate(0deg)');
            });

            // Global Enterprise SweetAlert Confirmation for Status Toggles
            $(document).on('click', '.sw-confirm-toggle', function(e) {
                e.preventDefault();
                var href = $(this).attr('href');
                var action = $(this).data('action') || 'Toggle';
                var name = $(this).data('name') || 'this record';
                var isDeactivate = (action.toLowerCase().indexOf('deactivat') !== -1 || action.toLowerCase().indexOf('delete') !== -1 || action.toLowerCase().indexOf('block') !== -1);

                swal({
                    title: action + " Confirmation",
                    text: "Are you sure you want to " + action.toLowerCase() + " '" + name + "'?",
                    type: isDeactivate ? "warning" : "info",
                    showCancelButton: true,
                    confirmButtonColor: isDeactivate ? "#ef4444" : "#2563eb",
                    confirmButtonText: "Yes, " + action,
                    cancelButtonText: "Cancel",
                    closeOnConfirm: false
                }, function() {
                    window.location.href = href;
                });
            });

            // Global Manual UCC Sync Now Handler (All UCC Masters + Sync Status Page)
            var isUccSyncRunning = false;
            $(document).on('click', '.js-ucc-sync-now', function(e) {
                e.preventDefault();
                if (isUccSyncRunning) return;

                var $btn = $(this);
                var syncUrl = $btn.data('sync-url') || '<?= base_url('master/ucc-revalidate/sync-now') ?>';

                swal({
                    title: "Sync UCC Master Data?",
                    text: "This will fetch the latest master updates from UCC and synchronize them into SpiderWaves.",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#2563eb",
                    confirmButtonText: "Yes, Sync Now",
                    cancelButtonText: "Cancel",
                    closeOnConfirm: false,
                    showLoaderOnConfirm: true
                }, function(isConfirm) {
                    if (!isConfirm || isUccSyncRunning) return;
                    isUccSyncRunning = true;

                    var $allBtns = $('.js-ucc-sync-now');
                    var originalHtml = $btn.html();
                    $allBtns.prop('disabled', true);
                    $btn.html('<i class="icon-spinner2 spinner"></i> <span>Syncing...</span>');

                    $.ajax({
                        url: syncUrl,
                        type: 'POST',
                        dataType: 'json',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success: function(resp) {
                            isUccSyncRunning = false;
                            $allBtns.prop('disabled', false);
                            $btn.html(originalHtml);

                            if (resp && resp.status === 'success') {
                                swal({
                                    title: "Sync Complete",
                                    text: resp.message || "UCC master data synchronized successfully.",
                                    type: "success",
                                    confirmButtonColor: "#2563eb"
                                }, function() {
                                    location.reload();
                                });
                            } else {
                                swal("Sync Failed", (resp && resp.message) ? resp.message : "Unable to synchronize UCC master data.", "error");
                            }
                        },
                        error: function(xhr) {
                            isUccSyncRunning = false;
                            $allBtns.prop('disabled', false);
                            $btn.html(originalHtml);

                            var err = (xhr.responseJSON && xhr.responseJSON.message)
                                ? xhr.responseJSON.message
                                : "Server error while synchronizing UCC master data.";
                            swal("Sync Failed", err, "error");
                        }
                    });
                });
            });

            // Universal Select2 Initialization for all dropdowns across the project
            function initProjectSelect2(context) {
                if (!$.fn.select2) return;
                var $scope = context ? $(context) : $(document);
                $scope.find('select.select2, select.select2-modal, select.sw-select').each(function() {
                    var $el = $(this);
                    if ($el.data('select2') || $el.hasClass('no-select2')) {
                        return;
                    }

                    // Remove sw-select so native browser select styles (border, svg arrow) aren't copied to select2-container
                    $el.removeClass('sw-select').addClass('select2');

                    var placeholder = $el.attr('placeholder') || $el.data('placeholder') || '';
                    if (!placeholder) {
                        var firstOpt = $el.find('option:first');
                        if (firstOpt.length && (firstOpt.val() === '' || firstOpt.text().indexOf('--') !== -1 || firstOpt.text().toLowerCase().indexOf('select') !== -1 || firstOpt.text().toLowerCase().indexOf('all') !== -1)) {
                            placeholder = firstOpt.text().replace(/^--\s*|\s*--$/g, '');
                        }
                    }

                    var hasEmptyOption = $el.find('option[value=""]').length > 0;
                    var optionCount = $el.find('option').length;

                    $el.select2({
                        placeholder: placeholder || 'Select an option',
                        allowClear: hasEmptyOption,
                        width: '100%',
                        minimumResultsForSearch: optionCount <= 5 ? -1 : 0,
                        adaptContainerCssClass: function(clazz) {
                            if (clazz === 'form-control' || clazz === 'sw-select' || (clazz && clazz.indexOf('sw-') === 0)) {
                                return null;
                            }
                            return clazz;
                        }
                    });

                    // Strip sw-select from the generated container if Select2 copied it
                    var s2 = $el.data('select2');
                    if (s2 && s2.container) {
                        s2.container.removeClass('sw-select form-control');
                    }
                });
            }

            $(document).ready(function() {
                initProjectSelect2();
                if ($.fn.tooltip) {
                    $('[data-popup="tooltip"], [data-toggle="tooltip"]').tooltip();
                }
            });

            // Auto-initialize when dynamic modals open
            $(document).on('shown.bs.modal', function(e) {
                initProjectSelect2(e.target);
                if ($.fn.tooltip) {
                    $('[data-popup="tooltip"], [data-toggle="tooltip"]').tooltip();
                }
            });

            // Auto-initialize after AJAX calls
            $(document).ajaxComplete(function() {
                initProjectSelect2();
                if ($.fn.tooltip) {
                    $('[data-popup="tooltip"], [data-toggle="tooltip"]').tooltip();
                }
            });

            // Universal AJAX Modal Handler for process waves transfers & dialogs
            $(document).on('click', '[data-toggle="ajaxModal"]', function(e) {
                e.preventDefault();
                var $this = $(this);
                var remote = $this.data('remote') || $this.attr('href');
                if (!remote) return;

                $('#ajaxTransferModal').remove();
                var modalHtml = '<div class="modal fade" id="ajaxTransferModal" tabindex="-1" role="dialog" aria-hidden="true">' +
                    '<div class="modal-dialog modal-md">' +
                        '<div class="modal-content" id="transferModalContent">' +
                            '<div class="modal-body text-center" style="padding: 30px;"><i class="icon-spinner2 spinner position-left"></i> Loading...</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
                $('body').append(modalHtml);
                $('#ajaxTransferModal').modal('show');

                $.get(remote, function(html) {
                    $('#transferModalContent').html(html);
                    if (typeof initProjectSelect2 === 'function') {
                        initProjectSelect2($('#transferModalContent'));
                    }
                }).fail(function() {
                    $('#transferModalContent').html('<div class="modal-body"><div class="alert alert-danger">Unable to load transfer form. Please try again.</div></div>');
                });
            });
        </script>

        <?= $this->renderSection('scripts') ?>
    </body>
</html>
