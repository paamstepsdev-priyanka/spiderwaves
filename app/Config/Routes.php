<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Public Authentication Routes
$routes->get('/', 'AuthController::index');
$routes->get('login', 'AuthController::index');
$routes->post('login', 'AuthController::authenticate');
$routes->post('login/authenticate', 'AuthController::authenticate');
$routes->get('login/destroy', 'AuthController::destroy');
$routes->get('logout', 'AuthController::destroy');

// Authenticated Landing Routes (Protected by auth filter)
$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes) {
    // Dashboard landing for admin, superadmin, process managers, locker, g58
    $routes->get('dashboard', static function () {
        return view('dashboard/index');
    }, ['filter' => ['auth', 'role:admin,superadmin,process_manager,jr_process_manager,locker,g58']]);

    // Raw Waves Counter & Entry Routes (user, admin, superadmin, process_manager, locker)
    $routes->group('', ['filter' => ['auth', 'role:admin,superadmin,process_manager,user,locker']], static function (RouteCollection $routes) {
        $routes->get('raw-waves', 'RawWavesController::index');
        $routes->get('raw-waves/index', 'RawWavesController::index');
        $routes->get('raw-waves/index/(:any)', 'RawWavesController::index/$1');
        $routes->post('raw-waves/store', 'RawWavesController::insert_in_out');
        $routes->get('raw-waves/voucher/(:num)', 'RawWavesController::download_voucher/$1');
        $routes->get('raw-waves/download-voucher/(:num)', 'RawWavesController::download_voucher/$1');

        // Queue view for user, admin, superadmin, process_manager
        $routes->get('raw-waves/manage', 'RawWavesController::manage');
        $routes->get('raw_waves/manage_raw_waves', 'RawWavesController::manage_raw_waves');

        // Backward compatibility aliases
        $routes->get('raw_waves', 'RawWavesController::index');
        $routes->get('raw_waves/index', 'RawWavesController::index');
        $routes->get('raw_waves/index/(:any)', 'RawWavesController::index/$1');
        $routes->post('raw_waves/insert_in_out', 'RawWavesController::insert_in_out');
        $routes->get('raw_waves/download_voucher/(:num)', 'RawWavesController::download_voucher/$1');
        $routes->match(['GET', 'POST'], 'raw_waves/get_customer_wastage_data_by_mapped_customer_id/(:any)', 'RawWavesController::get_customer_wastage_data_by_mapped_customer_id/$1');
    });

    // Raw Waves Admin Actions: Edit Wave (admin, superadmin only)
    $routes->group('', ['filter' => ['auth', 'role:admin,superadmin']], static function (RouteCollection $routes) {
        $routes->get('raw-waves/edit/(:any)', 'RawWavesController::edit_in_out/$1');
        $routes->get('raw_waves/edit_in_out/(:any)', 'RawWavesController::edit_in_out/$1');
    });

    // Raw Waves Supervisor Queue & Verification (admin, superadmin, process_manager, user)
    $routes->group('', ['filter' => ['auth', 'role:admin,superadmin,process_manager,user']], static function (RouteCollection $routes) {
        $routes->match(['GET', 'POST'], 'raw-waves/view-voucher/(:any)', 'RawWavesController::view_voucher/$1');
        $routes->match(['GET', 'POST'], 'raw-waves/view-voucher/(:any)/(:any)', 'RawWavesController::view_voucher/$1/$2');
        $routes->match(['GET', 'POST'], 'raw_waves/view_voucher/(:any)', 'RawWavesController::view_voucher/$1');
        $routes->match(['GET', 'POST'], 'raw_waves/view_voucher/(:any)/(:any)', 'RawWavesController::view_voucher/$1/$2');

        $routes->post('raw-waves/verify', 'RawWavesController::verified_raw_waves');
        $routes->post('raw_waves/verified_raw_waves', 'RawWavesController::verified_raw_waves');
    });

    // Waves Permanent Financial Ledger Routes (admin, superadmin, process_manager)
    $routes->group('', ['filter' => ['auth', 'role:admin,superadmin,process_manager']], static function (RouteCollection $routes) {
        $routes->get('waves', 'WavesController::index');
        $routes->get('waves/index', 'WavesController::index');
        $routes->get('waves/in_out', 'WavesController::in_out');
        $routes->get('waves/in-out', 'WavesController::in_out');
        $routes->get('waves/create', 'WavesController::in_out');
        $routes->post('waves/insert_in_out', 'WavesController::insert_in_out');
        $routes->post('waves/store', 'WavesController::insert_in_out');
        $routes->post('waves/update/(:any)', 'WavesController::insert_in_out');
        $routes->get('waves/get_suggestion_cust_name', 'WavesController::get_suggestion_cust_name');
        $routes->get('waves/view_details/(:any)', 'WavesController::view_details/$1');
        $routes->get('waves/details/(:any)', 'WavesController::view_details/$1');
    });

    // Waves Edit Wave Action (admin, superadmin only)
    $routes->group('', ['filter' => ['auth', 'role:admin,superadmin']], static function (RouteCollection $routes) {
        $routes->get('waves/edit_in_out/(:any)', 'WavesController::edit_in_out/$1');
        $routes->get('waves/edit/(:any)', 'WavesController::edit_in_out/$1');
    });

    // Customer Portal (cust role only)
    $routes->get('report/customer_ledger_for_customer/(:segment)', 'ReportController::customerLedgerForCustomer/$1', ['filter' => ['auth', 'role:cust']]);
    $routes->post('report/cust_approved/(:segment)', 'ReportController::custApproved/$1', ['filter' => ['auth', 'role:cust']]);
});

// UCC Webhook Receiver (HMAC-SHA256 signature, timestamp, and nonce verified by controller)
$routes->post('webhooks/ucc/master_changed', 'WebhooksController::masterChanged');

// Master Data Routes (Protected by AuthFilter and RoleFilter: admin, superadmin)
$routes->group('master', ['filter' => ['auth', 'role:admin,superadmin']], static function (RouteCollection $routes) {
    // UCC Revalidate Masters modern routes
    $routes->get('ucc-revalidate', 'UccRevalidateController::index');
    $routes->get('ucc-revalidate/live-status', 'UccRevalidateController::liveStatus');
    $routes->post('ucc-revalidate/run-check', 'UccRevalidateController::runCheck');
    $routes->post('ucc-revalidate/sync-now', 'UccRevalidateController::syncNow');
    $routes->post('ucc-revalidate/sync-differences', 'UccRevalidateController::syncDifferences');
    $routes->post('ucc-revalidate/soft-delete-extra', 'UccRevalidateController::softDeleteExtra');
    $routes->post('ucc-revalidate/full-snapshot', 'UccRevalidateController::fullSnapshot');
    $routes->get('ucc-revalidate/download-csv', 'UccRevalidateController::downloadCsv');

    // Customer Master modern routes
    $routes->get('customer', 'MasterController::customer');
    $routes->get('customer/create', 'MasterController::createCustomer');
    $routes->post('customer/store', 'MasterController::storeCustomer');
    $routes->get('customer/edit/(:any)', 'MasterController::editCustomer/$1');
    $routes->post('customer/update/(:any)', 'MasterController::updateCustomer/$1');
    $routes->get('customer/toggle/(:any)', 'MasterController::toggleCustomerStatus/$1');
    $routes->get('customer_wastage_mapping/(:any)', 'MasterController::customerWastageMapping/$1');
    $routes->get('customer/wastage/(:any)', 'MasterController::customerWastageMapping/$1');
    $routes->post('insert_customer_wastage_data', 'MasterController::insertCustomerWastageData');
    $routes->post('customer/wastage/store', 'MasterController::insertCustomerWastageData');

    // Backward-compatible CI3 aliases
    $routes->get('manage_cust', 'MasterController::manage_cust');
    $routes->get('add_cust', 'MasterController::add_cust');
    $routes->get('edit_cust/(:any)', 'MasterController::edit_cust/$1');
    $routes->post('insert_cust', 'MasterController::insert_cust');

    // Customer Mapping modern routes
    $routes->get('customer-mapping', 'MasterController::customerMapping');
    $routes->get('customer_mapping', 'MasterController::customerMapping');
    $routes->get('customer-mapping/create', 'MasterController::createCustomerMapping');
    $routes->post('customer-mapping/store', 'MasterController::insertCustomerMapping');
    $routes->get('customer-mapping/edit/(:any)', 'MasterController::editCustomerMapping/$1');
    $routes->post('customer-mapping/update/(:any)', 'MasterController::insertCustomerMapping');
    $routes->post('customer-mapping/import', 'MasterController::importCustomerMapping');
    $routes->get('customer-mapping/toggle/(:any)', 'MasterController::toggleCustomerMappingStatus/$1');

    // Customer Mapping backward-compatible CI3 aliases
    $routes->get('manage_cust_mapping', 'MasterController::manage_cust_mapping');
    $routes->get('add_cust_mapping', 'MasterController::add_cust_mapping');
    $routes->get('edit_cust_mapping/(:any)', 'MasterController::edit_cust_mapping/$1');
    $routes->post('insert_cust_mapping', 'MasterController::insert_cust_mapping');
    $routes->post('insert_mapped_customer_name_from_excel', 'MasterController::insert_mapped_customer_name_from_excel');

    // Collection Master modern routes
    $routes->get('collection', 'MasterController::collection');
    $routes->get('collection/create', 'MasterController::createCollection');
    $routes->post('collection/store', 'MasterController::insertCollection');
    $routes->get('collection/edit/(:any)', 'MasterController::editCollection/$1');
    $routes->post('collection/update/(:any)', 'MasterController::insertCollection');
    $routes->get('collection/toggle/(:any)', 'MasterController::toggleCollectionStatus/$1');

    // Collection Master backward-compatible CI3 aliases
    $routes->get('manage_collection', 'MasterController::manage_collection');
    $routes->get('add_collection', 'MasterController::add_collection');
    $routes->get('edit_collection/(:any)', 'MasterController::edit_collection/$1');
    $routes->post('insert_collection', 'MasterController::insert_collection');

    // Purity Master modern routes
    $routes->get('purity', 'MasterController::purity');
    $routes->get('purity/create', 'MasterController::createPurity');
    $routes->post('purity/store', 'MasterController::insertPurity');
    $routes->get('purity/edit/(:any)', 'MasterController::editPurity/$1');
    $routes->post('purity/update/(:any)', 'MasterController::insertPurity');
    $routes->get('purity/toggle/(:any)', 'MasterController::togglePurityStatus/$1');

    // Purity Master backward-compatible CI3 aliases
    $routes->get('manage_purity', 'MasterController::manage_purity');
    $routes->get('add_purity', 'MasterController::add_purity');
    $routes->get('edit_purity/(:any)', 'MasterController::edit_purity/$1');
    $routes->post('insert_purity', 'MasterController::insert_purity');

    // UCC Wastage Master (Read-Only)
    $routes->get('ucc-wastage', 'MasterController::uccWastage');

    // User Management modern routes
    $routes->get('user', 'MasterController::user');
    $routes->get('user/create', 'MasterController::createUser');
    $routes->post('user/store', 'MasterController::insertUser');
    $routes->get('user/edit/(:any)', 'MasterController::editUser/$1');
    $routes->post('user/update/(:any)', 'MasterController::insertUser');
    $routes->get('user/view/(:any)', 'MasterController::viewUser/$1');
    $routes->get('user/toggle/(:any)', 'MasterController::toggleUserStatus/$1');

    // User Management backward-compatible CI3 aliases
    $routes->get('manage_user', 'MasterController::manage_user');
    $routes->get('add_user', 'MasterController::add_user');
    $routes->get('edit_user/(:any)', 'MasterController::edit_user/$1');
    $routes->get('view_user/(:any)', 'MasterController::view_user/$1');
    $routes->post('insert_user', 'MasterController::insert_user');

    // Petty Cash Master modern routes
    $routes->get('petty-cash', 'MasterController::pettyCash');
    $routes->get('petty-cash/create', 'MasterController::createPettyCash');
    $routes->post('petty-cash/store', 'MasterController::storePettyCash');
    $routes->get('petty-cash/edit/(:any)', 'MasterController::editPettyCash/$1');
    $routes->post('petty-cash/update/(:any)', 'MasterController::updatePettyCash/$1');
    $routes->get('petty-cash/toggle/(:any)', 'MasterController::togglePettyCashStatus/$1');
    $routes->post('petty-cash/bulk', 'MasterController::bulkUpdatePettyCash');

    // Petty Cash backward-compatible CI3 aliases
    $routes->get('manage_petty_cash_cust', 'MasterController::manage_petty_cash_cust');
    $routes->get('petty_cash_cust', 'MasterController::manage_petty_cash_cust');
    $routes->post('insert_petty_cash_cust', 'MasterController::insert_petty_cash_cust');

    // Wastage / Loss Percentage Master modern routes
    $routes->get('wastage', 'MasterController::wastage');
    $routes->get('wastage/create', 'MasterController::createWastage');
    $routes->post('wastage/store', 'MasterController::storeWastage');
    $routes->get('wastage/edit/(:any)', 'MasterController::editWastage/$1');
    $routes->post('wastage/update/(:any)', 'MasterController::updateWastage/$1');
    $routes->post('wastage/toggle/(:any)', 'MasterController::toggleWastageStatus/$1');
    $routes->get('wastage/delete/(:any)', 'MasterController::toggleWastageStatus/$1');

    // Workshop Stage Loss Percentage routes
    $routes->get('loss_percentage', 'MasterController::lossPercentage');
    $routes->get('loss-percentage', 'MasterController::lossPercentage');
    $routes->post('loss_percentage/update', 'MasterController::updateLossPercentage');
    $routes->post('loss-percentage/update', 'MasterController::updateLossPercentage');

    // Backward-compatible CI3 aliases for Loss Percentage
    $routes->get('set_account_loss_percentage', 'MasterController::set_account_loss_percentage');
    $routes->post('insert_account_loss_percentage', 'MasterController::insert_account_loss_percentage');

    // Remark Master modern routes
    $routes->get('remark', 'MasterController::remark');
    $routes->get('remark/create', 'MasterController::createRemark');
    $routes->post('remark/store', 'MasterController::storeRemark');
    $routes->get('remark/edit/(:segment)', 'MasterController::editRemark/$1');
    $routes->post('remark/update/(:segment)', 'MasterController::updateRemark/$1');
    $routes->get('remark/toggle/(:segment)', 'MasterController::toggleRemarkStatus/$1');

    // Remark Master backward-compatible CI3 aliases
    $routes->get('manage_remark', 'MasterController::manage_remark');
    $routes->get('add_remark', 'MasterController::add_remark');
    $routes->get('edit_remark/(:segment)', 'MasterController::edit_remark/$1');
    $routes->post('insert_remark', 'MasterController::insert_remark');

    // Account Master & Department Mapping routes (Superadmin by default, grantable via Permissions)
    $routes->get('account', 'AccountController::index', ['filter' => ['auth', 'permission:account_master.view']]);
    $routes->get('account/create', 'AccountController::create', ['filter' => ['auth', 'permission:account_master.create']]);
    $routes->post('account/store', 'AccountController::store', ['filter' => ['auth', 'permission:account_master.create']]);
    $routes->get('account/get-process/(:any)', 'AccountController::getProcessData/$1', ['filter' => ['auth', 'permission:account_master.view']]);
    $routes->get('account/edit/(:any)', 'AccountController::edit/$1', ['filter' => ['auth', 'permission:account_master.edit']]);
    $routes->post('account/update/(:any)', 'AccountController::update/$1', ['filter' => ['auth', 'permission:account_master.edit']]);
    $routes->match(['GET', 'POST'], 'account/delete/(:any)', 'AccountController::delete/$1', ['filter' => ['auth', 'permission:account_master.toggle']]);
    $routes->get('account/mapping/(:any)', 'AccountController::mapping/$1', ['filter' => ['auth', 'permission:account_master.mapping']]);
    $routes->post('account/update-mapping', 'AccountController::updateMapping', ['filter' => ['auth', 'permission:account_master.mapping']]);
    $routes->get('account/toggle/(:any)', 'AccountController::toggleStatus/$1', ['filter' => ['auth', 'permission:account_master.toggle']]);
    $routes->post('account/toggle/(:any)', 'AccountController::toggleStatus/$1', ['filter' => ['auth', 'permission:account_master.toggle']]);

    // Backward-compatible CI2/CI3 aliases for Account Master
    $routes->get('manage_account', 'AccountController::index', ['filter' => ['auth', 'permission:account_master.view']]);
    $routes->get('add_account', 'AccountController::create', ['filter' => ['auth', 'permission:account_master.create']]);
    $routes->get('edit_account/(:any)', 'AccountController::edit/$1', ['filter' => ['auth', 'permission:account_master.edit']]);
    $routes->post('insert_account', 'AccountController::insertAccountLegacy', ['filter' => ['auth', 'permission:account_master.edit']]);
    $routes->get('map_with_account/(:any)', 'AccountController::mapping/$1', ['filter' => ['auth', 'permission:account_master.mapping']]);
    $routes->post('update_mapping_data', 'AccountController::updateMapping', ['filter' => ['auth', 'permission:account_master.mapping']]);
});

// Top-level direct routes for Account Master (Superadmin by default, grantable via Permissions)
$routes->group('account', ['filter' => ['auth', 'permission:account_master.view']], static function (RouteCollection $routes) {
    $routes->get('/', 'AccountController::index');
    $routes->get('create', 'AccountController::create');
    $routes->post('store', 'AccountController::store');
    $routes->get('get-process/(:any)', 'AccountController::getProcessData/$1');
    $routes->get('edit/(:any)', 'AccountController::edit/$1');
    $routes->post('update/(:any)', 'AccountController::update/$1');
    $routes->match(['GET', 'POST'], 'delete/(:any)', 'AccountController::delete/$1');
    $routes->get('mapping/(:any)', 'AccountController::mapping/$1');
    $routes->post('update-mapping', 'AccountController::updateMapping');
    $routes->get('toggle/(:any)', 'AccountController::toggleStatus/$1');
    $routes->post('toggle/(:any)', 'AccountController::toggleStatus/$1');
});

// Authenticated Downstream AJAX Endpoints (accessible across operational roles during wave processing)
$routes->group('master', ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->post('get_mapped_cust_option', 'MasterController::get_mapped_cust_option');
    $routes->post('get_mapped_cust_purity_by_mapped_cust_id', 'MasterController::get_mapped_cust_purity_by_mapped_cust_id');
    $routes->post('get_cust_name_by_mapped_cust', 'MasterController::get_cust_name_by_mapped_cust');
    $routes->match(['GET', 'POST'], 'get_collection_name_by_mapped_customer_id/(:any)', 'MasterController::get_collection_name_by_mapped_customer_id/$1');
    $routes->match(['GET', 'POST'], 'get_customer_wastage_data_by_mapped_customer_id/(:any)', 'MasterController::get_customer_wastage_data_by_mapped_customer_id/$1');
    $routes->get('get_active_purities', 'MasterController::getActivePuritiesJson');
});

// =========================================================================
// MODULE 1: PROCESS WAVES
// =========================================================================
$routes->group('', ['filter' => ['auth', 'role:admin,superadmin,process_manager,jr_process_manager,g58,locker']], static function (RouteCollection $routes) {
    $routes->get('process-waves', 'ProcessWavesController::index');
    $routes->get('process-waves/manage', 'ProcessWavesController::manage');

    // Legacy Loss direct routes (before generic wildcard)
    $routes->get('process-waves/manage/3442203db2c3bf62b928ac74f3d64b88', 'LossController::index');
    $routes->get('process_waves/manage/3442203db2c3bf62b928ac74f3d64b88', 'LossController::index');
    $routes->get('process-waves/manage/3442203db2c3bf62b928ac74f3d64b88/(:any)', 'LossController::manage/$1');
    $routes->get('process_waves/manage/3442203db2c3bf62b928ac74f3d64b88/(:any)', 'LossController::manage/$1');

    // Legacy Refining direct routes (before generic wildcard)
    $routes->get('process-waves/manage/860d4bcf649e0a2393ef85ae72cb2dd9', 'RefiningController::index');
    $routes->get('process_waves/manage/860d4bcf649e0a2393ef85ae72cb2dd9', 'RefiningController::index');

    $routes->get('process-waves/manage/(:any)', 'ProcessWavesController::manage/$1');
    $routes->get('process-waves/manage/(:any)/(:any)', 'ProcessWavesController::manage/$1/$2');

    $routes->match(['GET', 'POST'], 'process-waves/transfer', 'ProcessWavesController::transfer');
    $routes->match(['GET', 'POST'], 'process-waves/transfer/(:num)', 'ProcessWavesController::transfer/$1');
    $routes->match(['GET', 'POST'], 'process-waves/transfer/(:num)/(:num)', 'ProcessWavesController::transfer/$1/$2');
    $routes->match(['GET', 'POST'], 'process-waves/transfer/(:num)/(:num)/(:any)', 'ProcessWavesController::transfer/$1/$2/$3');
    $routes->match(['GET', 'POST'], 'process-waves/transfer/(:num)/(:num)/(:any)/(:any)', 'ProcessWavesController::transfer/$1/$2/$3/$4');

    $routes->post('process-waves/insert-transfer', 'ProcessWavesController::insertTransfer');
    $routes->post('process-waves/check-weight', 'ProcessWavesController::checkWeight');
    $routes->post('process-waves/check-loss', 'ProcessWavesController::checkLossValidation');
    $routes->post('process-waves/check-pcs', 'ProcessWavesController::checkNumberOfPcs');
    $routes->post('process-waves/insert-checkpoint', 'ProcessWavesController::insertStockCheckPoint');

    $routes->get('process-waves/print-transaction/(:num)', 'ProcessWavesController::printTransaction/$1');
    $routes->get('process-waves/print-sheet', 'ProcessWavesController::printSheet');
    $routes->get('process-waves/print-sheet/(:any)', 'ProcessWavesController::printSheet/$1');
    $routes->get('process-waves/print-sheet/(:any)/(:any)', 'ProcessWavesController::printSheet/$1/$2');

    // Backward compatibility aliases for Process Waves
    $routes->get('process_waves', 'ProcessWavesController::index');
    $routes->get('process_waves/index', 'ProcessWavesController::index');
    $routes->get('process_waves/manage', 'ProcessWavesController::manage');
    $routes->get('process_waves/manage/(:any)', 'ProcessWavesController::manage/$1');
    $routes->get('process_waves/manage/(:any)/(:any)', 'ProcessWavesController::manage/$1/$2');
    $routes->match(['GET', 'POST'], 'process_waves/transfer_process_waves', 'ProcessWavesController::transfer');
    $routes->match(['GET', 'POST'], 'process_waves/transfer_process_waves/(:num)', 'ProcessWavesController::transfer/$1');
    $routes->match(['GET', 'POST'], 'process_waves/transfer_process_waves/(:num)/(:num)', 'ProcessWavesController::transfer/$1/$2');
    $routes->match(['GET', 'POST'], 'process_waves/transfer_process_waves/(:num)/(:num)/(:any)', 'ProcessWavesController::transfer/$1/$2/$3');
    $routes->match(['GET', 'POST'], 'process_waves/transfer_process_waves/(:num)/(:num)/(:any)/(:any)', 'ProcessWavesController::transfer/$1/$2/$3/$4');
    $routes->post('process_waves/insert_process_waves', 'ProcessWavesController::insertTransfer');
    $routes->post('process_waves/check_weight', 'ProcessWavesController::checkWeight');
    $routes->post('process_waves/check_loss_validation', 'ProcessWavesController::checkLossValidation');
    $routes->post('process_waves/check_number_of_pcs', 'ProcessWavesController::checkNumberOfPcs');
    $routes->post('process_waves/insert_stock_check_point', 'ProcessWavesController::insertStockCheckPoint');
    $routes->get('process_waves/print_process_waves_transaction/(:num)', 'ProcessWavesController::printTransaction/$1');
    $routes->get('process_waves/print_process_waves', 'ProcessWavesController::printSheet');
    $routes->get('process_waves/print_process_waves/(:any)', 'ProcessWavesController::printSheet/$1');
    $routes->get('process_waves/print_process_waves/(:any)/(:any)', 'ProcessWavesController::printSheet/$1/$2');
});

// =========================================================================
// MODULE 2: LOCKER
// =========================================================================
$routes->group('', ['filter' => ['auth', 'role:admin,superadmin,process_manager,locker']], static function (RouteCollection $routes) {
    $routes->get('locker', 'LockerController::index');
    $routes->post('locker/check-stock', 'LockerController::checkStock');
    $routes->post('process_waves/check_stock_validation', 'LockerController::checkStock');
});

// =========================================================================
// MODULE 3: LOCKER STOCK VERIFICATION
// =========================================================================
$routes->group('', ['filter' => ['auth', 'role:admin,superadmin,process_manager,locker']], static function (RouteCollection $routes) {
    $routes->get('locker-stock-verification', 'LockerStockVerificationController::index');
    $routes->post('locker-stock-verification/mark-done', 'LockerStockVerificationController::markDone');
    $routes->post('locker-stock-verification/restart', 'LockerStockVerificationController::restart');

    // Backward compatibility aliases for Locker Stock Verification
    $routes->get('process_waves/locker_stock_verification', 'LockerStockVerificationController::index');
    $routes->post('process_waves/mark_locker_stock_verification_done', 'LockerStockVerificationController::markDone');
    $routes->post('process_waves/restart_locker_verification_process', 'LockerStockVerificationController::restart');
});

// =========================================================================
// MODULE 4: LOSS RECOVERY & CONSOLIDATION
// =========================================================================
$routes->group('', ['filter' => ['auth', 'role:admin,superadmin,process_manager,g58']], static function (RouteCollection $routes) {
    $routes->get('loss', 'LossController::index');
    $routes->get('loss/index', 'LossController::index');
    $routes->get('loss/manage', 'LossController::manage');
    $routes->get('loss/manage/(:any)', 'LossController::manage/$1');
    $routes->get('loss/manage/(:any)/(:any)', 'LossController::manage/$1/$2');

    $routes->match(['GET', 'POST'], 'loss/transfer-modal', 'LossController::transferModal');
    $routes->match(['GET', 'POST'], 'loss/transfer_modal', 'LossController::transferModal');
    $routes->post('loss/consolidate', 'LossController::consolidateTransfer');
    $routes->post('loss/consolidate-transfer', 'LossController::consolidateTransfer');

    $routes->get('loss/print', 'LossController::print');
    $routes->get('loss/print/(:any)', 'LossController::print/$1');
    $routes->get('loss/print-sheet', 'LossController::print');
    $routes->get('loss/print-sheet/(:any)', 'LossController::print/$1');

    // Legacy CI2 / CI3 URL Routing Aliases
    $routes->get('process-waves/manage/3442203db2c3bf62b928ac74f3d64b88', 'LossController::index');
    $routes->get('process_waves/manage/3442203db2c3bf62b928ac74f3d64b88', 'LossController::index');
    $routes->get('process-waves/manage/3442203db2c3bf62b928ac74f3d64b88/(:any)', 'LossController::manage/$1');
    $routes->get('process_waves/manage/3442203db2c3bf62b928ac74f3d64b88/(:any)', 'LossController::manage/$1');
});

// =========================================================================
// MODULE 5: REFINING DEPARTMENT
// =========================================================================
$routes->group('', ['filter' => ['auth', 'role:admin,superadmin,process_manager']], static function (RouteCollection $routes) {
    $routes->get('refining', 'RefiningController::index');
    $routes->get('refining/index', 'RefiningController::index');
    $routes->match(['GET', 'POST'], 'refining/transfer-modal/(:num)', 'RefiningController::transferModal/$1');
    $routes->match(['GET', 'POST'], 'refining/transfer_modal/(:num)', 'RefiningController::transferModal/$1');
    $routes->post('refining/insert-transfer', 'RefiningController::insertTransfer');
    $routes->post('refining/insert_transfer', 'RefiningController::insertTransfer');
    $routes->post('refining/check-weight', 'RefiningController::checkWeight');
    $routes->get('refining/print-transaction/(:num)', 'RefiningController::printTransaction/$1');

    // Legacy CI2 / CI3 URL Routing Aliases
    $routes->get('process-waves/manage/860d4bcf649e0a2393ef85ae72cb2dd9', 'RefiningController::index');
    $routes->get('process_waves/manage/860d4bcf649e0a2393ef85ae72cb2dd9', 'RefiningController::index');
});

// =========================================================================
// MODULE 6: REPORTS & STATEMENTS
// =========================================================================
$routes->group('', ['filter' => ['auth', 'role:admin,superadmin']], static function (RouteCollection $routes) {
    // Customer Wise Report
    $routes->get('report/cust_wise_report', 'ReportController::customerWiseReport');
    $routes->get('report/cust-wise-report', 'ReportController::customerWiseReport');

    // Customer Ledger
    $routes->match(['GET', 'POST'], 'report/customer_ledger/(:segment)', 'ReportController::customerLedger/$1');
    $routes->match(['GET', 'POST'], 'report/customer-ledger/(:segment)', 'ReportController::customerLedger/$1');

    // Stock Report modern routes (Governed by dynamic RBAC stock.view permission)
    $routes->get('report/stock-report', 'ReportController::stockReport', ['filter' => ['auth', 'role:superadmin,admin,process_manager,locker,g58,user']]);
    $routes->get('report/stock_report', 'ReportController::stockReport', ['filter' => ['auth', 'role:superadmin,admin,process_manager,locker,g58,user']]);
    $routes->get('stock', 'ReportController::stockReport', ['filter' => ['auth', 'role:superadmin,admin,process_manager,locker,g58,user']]);

    // Daily Report
    $routes->get('report/daily_report', 'ReportController::dailyReport');
    $routes->get('report/daily-report', 'ReportController::dailyReport');

    // Print Daily Report
    $routes->get('report/print_daily_report', 'ReportController::printDailyReport');
    $routes->get('report/print-daily-report', 'ReportController::printDailyReport');

    // Approve and Submit Daily Report
    $routes->match(['GET', 'POST'], 'report/export_daily_report', 'ReportController::exportDailyReport');
    $routes->match(['GET', 'POST'], 'report/export-daily-report', 'ReportController::exportDailyReport');

    // Legacy CI2 / CI3 URL Routing Aliases (Restricted exclusively to Superadmin)
    $routes->get('report/loss_report', 'ReportController::lossReport', ['filter' => 'role:superadmin']);
    $routes->get('report/loss-report', 'ReportController::lossReport', ['filter' => 'role:superadmin']);
});

// =========================================================================
// MODULE 7: ROLE-BASED UI & MENU PERMISSION MANAGEMENT (Super Admin Only)
// =========================================================================
$routes->group('permission', ['filter' => ['auth', 'permission:permission.superadmin']], static function (RouteCollection $routes) {
    $routes->get('', 'PermissionController::index');
    $routes->get('index', 'PermissionController::index');
    $routes->get('role-modules/(:num)', 'PermissionController::getRoleModules/$1');
    $routes->post('save-modules/(:num)', 'PermissionController::saveModulePermissions/$1');
    $routes->get('assign', 'PermissionController::assign');
    $routes->get('assign/(:num)', 'PermissionController::assign/$1');
    $routes->post('save/(:num)', 'PermissionController::savePermissions/$1');
    $routes->post('save-user/(:num)', 'PermissionController::saveUserPermissions/$1');
    $routes->get('summary', 'PermissionController::summary');
    $routes->get('users', 'PermissionController::users');
    $routes->post('override', 'PermissionController::saveUserOverride');
    $routes->post('override/remove/(:num)', 'PermissionController::removeUserOverride/$1');
    $routes->get('audit-logs', 'PermissionController::auditLogs');
});





