<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        echo "Seeding Roles...\n";
        $now = date('Y-m-d H:i:s');

        // 1. SEED ROLES
        $roles = [
            [
                'role_key'    => 'superadmin',
                'role_name'   => 'Super Admin',
                'description' => 'Unrestricted master access across all system modules, configurations, and accounts.',
                'is_system'   => 1,
                'status'      => '1',
                'version'     => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'role_key'    => 'admin',
                'role_name'   => 'Admin',
                'description' => 'Full operational manufacturing management, master data CRUD, and daily report approval (HO scope).',
                'is_system'   => 1,
                'status'      => '1',
                'version'     => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'role_key'    => 'process_manager',
                'role_name'   => 'Process Manager',
                'description' => 'Workshop operations manager overseeing manufacturing stages, raw waves queue, and refining.',
                'is_system'   => 1,
                'status'      => '1',
                'version'     => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'role_key'    => 'jr_process_manager',
                'role_name'   => 'Jr. Process Manager',
                'description' => 'Finishing stage operations covering Packing, Transfer Packing, and Rodium.',
                'is_system'   => 1,
                'status'      => '1',
                'version'     => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'role_key'    => 'locker',
                'role_name'   => 'Locker',
                'description' => 'Locker vault custodian managing safe inventory balances and stock verification.',
                'is_system'   => 1,
                'status'      => '1',
                'version'     => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'role_key'    => 'g58',
                'role_name'   => 'G58',
                'description' => 'Specialized G58 manufacturing unit and associated loss recovery ledgers.',
                'is_system'   => 1,
                'status'      => '1',
                'version'     => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'role_key'    => 'user',
                'role_name'   => 'User',
                'description' => 'Floor counter entry operator and incoming cross-location batch verifier.',
                'is_system'   => 1,
                'status'      => '1',
                'version'     => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'role_key'    => 'dispatch_entry',
                'role_name'   => 'Dispatch Entry',
                'description' => 'Reserved role for future finished goods dispatch counter entries.',
                'is_system'   => 1,
                'status'      => '0',
                'version'     => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'role_key'    => 'dispatch_verify',
                'role_name'   => 'Dispatch Verify',
                'description' => 'Reserved role for future dispatch verification and gate pass approvals.',
                'is_system'   => 1,
                'status'      => '0',
                'version'     => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'role_key'    => 'cust',
                'role_name'   => 'Customer Client',
                'description' => 'Customer portal role for external client ledger statement access.',
                'is_system'   => 1,
                'status'      => '1',
                'version'     => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        foreach ($roles as $r) {
            $existing = $db->table('roles')->where('role_key', $r['role_key'])->get()->getRowArray();
            if (!$existing) {
                $db->table('roles')->insert($r);
            }
        }

        $roleMap = [];
        $rows = $db->table('roles')->get()->getResultArray();
        foreach ($rows as $row) {
            $roleMap[$row['role_key']] = (int)$row['role_id'];
        }

        echo "Seeding Menus...\n";
        // 2. SEED MENUS
        $menus = [
            // MAIN SECTION
            [
                'menu_code'      => 'main_dashboard',
                'parent_code'    => null,
                'title'          => 'Dashboard',
                'url'            => 'dashboard',
                'icon'           => 'icon-grid',
                'menu_type'      => 'sidebar',
                'section_header' => 'Main',
                'order_no'       => 10,
                'module_key'     => 'dashboard',
            ],
            // OPERATIONS SECTION
            [
                'menu_code'      => 'ops_waves',
                'parent_code'    => null,
                'title'          => 'Waves',
                'url'            => 'raw-waves/manage',
                'icon'           => 'icon-loop4',
                'menu_type'      => 'sidebar',
                'section_header' => 'Operations',
                'badge_callback' => 'get_number_of_pending_raw_waves',
                'order_no'       => 20,
                'module_key'     => 'raw_waves',
            ],
            [
                'menu_code'      => 'ops_process',
                'parent_code'    => null,
                'title'          => 'Process',
                'url'            => '#menuProcess',
                'icon'           => 'icon-sync',
                'menu_type'      => 'sidebar',
                'section_header' => 'Operations',
                'order_no'       => 30,
                'module_key'     => 'process_waves',
            ],
            // 18 PROCESS DEPARTMENTS
            ['menu_code' => 'proc_casting', 'parent_code' => 'ops_process', 'title' => 'Casting', 'url' => 'process-waves/manage/80f372f8dffb5126736fa8c02a24c9fa', 'icon' => 'icon-primitive-dot', 'order_no' => 1, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_filing', 'parent_code' => 'ops_process', 'title' => 'Filing', 'url' => 'process-waves/manage/0eca01acd25aada9700d6a22406795fa', 'icon' => 'icon-primitive-dot', 'order_no' => 2, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_heera_light', 'parent_code' => 'ops_process', 'title' => 'Heera Light', 'url' => 'process-waves/manage/cb4e1da17c5c64278fe575d4dcfe7491', 'icon' => 'icon-primitive-dot', 'order_no' => 3, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_packing', 'parent_code' => 'ops_process', 'title' => 'Packing', 'url' => 'process-waves/manage/68f2968985d8728d155d65ab5505ab34', 'icon' => 'icon-primitive-dot', 'order_no' => 4, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_transfer_packing', 'parent_code' => 'ops_process', 'title' => 'Transfer Packing', 'url' => 'process-waves/manage/aagahedr01a6ff8a58427cef2095asvf', 'icon' => 'icon-primitive-dot', 'order_no' => 5, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_polish', 'parent_code' => 'ops_process', 'title' => 'Polish', 'url' => 'process-waves/manage/7634c0ea21ffed80632a521eec65a841', 'icon' => 'icon-primitive-dot', 'order_no' => 6, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_g58_polish', 'parent_code' => 'ops_process', 'title' => 'G58-Polish', 'url' => 'process-waves/manage/43sdfh3sdklf239gjklfd43gkls4gfr4', 'icon' => 'icon-primitive-dot', 'order_no' => 7, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_g58_filing', 'parent_code' => 'ops_process', 'title' => 'G58-Filing', 'url' => 'process-waves/manage/423fwr46jk8563db447hddv467jhm57h', 'icon' => 'icon-primitive-dot', 'order_no' => 8, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_g58_setting', 'parent_code' => 'ops_process', 'title' => 'G58-Setting', 'url' => 'process-waves/manage/dsfs159422hzzqkd439fdpo60923jkd2', 'icon' => 'icon-primitive-dot', 'order_no' => 9, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_g58_transfer', 'parent_code' => 'ops_process', 'title' => 'G58-Transfer', 'url' => 'process-waves/manage/bb8e719f01a6ff8ca84634ce2095cf03', 'icon' => 'icon-primitive-dot', 'order_no' => 10, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_po_check', 'parent_code' => 'ops_process', 'title' => 'PO Check', 'url' => 'process-waves/manage/8e10da49a72eaffc20sa6f5hgvara85f', 'icon' => 'icon-primitive-dot', 'order_no' => 11, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_galai', 'parent_code' => 'ops_process', 'title' => 'Galai', 'url' => 'process-waves/manage/fer32fgd3fif952mcmbje8234gis3824', 'icon' => 'icon-primitive-dot', 'order_no' => 12, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_tash', 'parent_code' => 'ops_process', 'title' => 'Tash', 'url' => 'process-waves/manage/n4l742kfdg96k3mbdw05k73mfmv6693k', 'icon' => 'icon-primitive-dot', 'order_no' => 13, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_sapphire', 'parent_code' => 'ops_process', 'title' => 'Sapphire', 'url' => 'process-waves/manage/zr4th43570jmf23679jbde5tghy65f4h', 'icon' => 'icon-primitive-dot', 'order_no' => 14, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_cnc', 'parent_code' => 'ops_process', 'title' => 'Megnet', 'url' => 'process-waves/manage/gqe2953fg4knbmassqww533fghjbc456', 'icon' => 'icon-primitive-dot', 'order_no' => 15, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_rodium', 'parent_code' => 'ops_process', 'title' => 'Rodium', 'url' => 'process-waves/manage/592d02d58179f9b92b590ea1fea5ff2b', 'icon' => 'icon-primitive-dot', 'order_no' => 16, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_setting', 'parent_code' => 'ops_process', 'title' => 'Setting', 'url' => 'process-waves/manage/d3dc6550b750332ff3d5c369791c8eda', 'icon' => 'icon-primitive-dot', 'order_no' => 17, 'module_key' => 'process_waves'],
            ['menu_code' => 'proc_zero_balance', 'parent_code' => 'ops_process', 'title' => 'Zero Balance', 'url' => 'process-waves/manage/43434325b7f95cbfe2e5edfbb1228c4a', 'icon' => 'icon-primitive-dot', 'order_no' => 18, 'module_key' => 'process_waves'],

            // LOCKER & VERIFICATION
            [
                'menu_code'      => 'ops_locker',
                'parent_code'    => null,
                'title'          => 'Locker',
                'url'            => 'locker',
                'icon'           => 'icon-safe',
                'menu_type'      => 'sidebar',
                'section_header' => 'Operations',
                'order_no'       => 40,
                'module_key'     => 'locker',
            ],
            [
                'menu_code'      => 'ops_locker_verification',
                'parent_code'    => null,
                'title'          => 'Locker Stock Verification',
                'url'            => 'process_waves/locker_stock_verification',
                'icon'           => 'icon-checkmark-circle',
                'menu_type'      => 'sidebar',
                'section_header' => 'Operations',
                'order_no'       => 50,
                'module_key'     => 'locker_verification',
            ],

            // LOSS ACCORDION
            [
                'menu_code'      => 'ops_loss',
                'parent_code'    => null,
                'title'          => 'Loss',
                'url'            => '#menuLoss',
                'icon'           => 'icon-pulse2',
                'menu_type'      => 'sidebar',
                'section_header' => 'Operations',
                'order_no'       => 60,
                'module_key'     => 'loss',
            ],
            ['menu_code' => 'loss_hub', 'parent_code' => 'ops_loss', 'title' => 'Loss Hub', 'url' => 'loss', 'icon' => 'icon-grid', 'order_no' => 1, 'module_key' => 'loss'],
            ['menu_code' => 'loss_casting', 'parent_code' => 'ops_loss', 'title' => 'Casting Loss', 'url' => 'loss/manage/80f372f8dffb5126736fa8c02a24c9fa', 'icon' => 'icon-primitive-dot', 'order_no' => 2, 'module_key' => 'loss'],
            ['menu_code' => 'loss_filing', 'parent_code' => 'ops_loss', 'title' => 'Filing Loss', 'url' => 'loss/manage/0eca01acd25aada9700d6a22406795fa', 'icon' => 'icon-primitive-dot', 'order_no' => 3, 'module_key' => 'loss'],
            ['menu_code' => 'loss_heera_light', 'parent_code' => 'ops_loss', 'title' => 'Heera Light Loss', 'url' => 'loss/manage/cb4e1da17c5c64278fe575d4dcfe7491', 'icon' => 'icon-primitive-dot', 'order_no' => 4, 'module_key' => 'loss'],
            ['menu_code' => 'loss_packing', 'parent_code' => 'ops_loss', 'title' => 'Packing Loss', 'url' => 'loss/manage/68f2968985d8728d155d65ab5505ab34', 'icon' => 'icon-primitive-dot', 'order_no' => 5, 'module_key' => 'loss'],
            ['menu_code' => 'loss_polish', 'parent_code' => 'ops_loss', 'title' => 'Polish Loss', 'url' => 'loss/manage/7634c0ea21ffed80632a521eec65a841', 'icon' => 'icon-primitive-dot', 'order_no' => 6, 'module_key' => 'loss'],
            ['menu_code' => 'loss_rodium', 'parent_code' => 'ops_loss', 'title' => 'Rodium Loss', 'url' => 'loss/manage/592d02d58179f9b92b590ea1fea5ff2b', 'icon' => 'icon-primitive-dot', 'order_no' => 7, 'module_key' => 'loss'],
            ['menu_code' => 'loss_setting', 'parent_code' => 'ops_loss', 'title' => 'Setting Loss', 'url' => 'loss/manage/d3dc6550b750332ff3d5c369791c8eda', 'icon' => 'icon-primitive-dot', 'order_no' => 8, 'module_key' => 'loss'],
            ['menu_code' => 'loss_g58_polish', 'parent_code' => 'ops_loss', 'title' => 'G58-Polish', 'url' => 'loss/manage/43sdfh3sdklf239gjklfd43gkls4gfr4', 'icon' => 'icon-primitive-dot', 'order_no' => 9, 'module_key' => 'loss'],
            ['menu_code' => 'loss_g58_filing', 'parent_code' => 'ops_loss', 'title' => 'G58-Filing', 'url' => 'loss/manage/423fwr46jk8563db447hddv467jhm57h', 'icon' => 'icon-primitive-dot', 'order_no' => 10, 'module_key' => 'loss'],
            ['menu_code' => 'loss_g58_setting', 'parent_code' => 'ops_loss', 'title' => 'G58-Setting', 'url' => 'loss/manage/dsfs159422hzzqkd439fdpo60923jkd2', 'icon' => 'icon-primitive-dot', 'order_no' => 11, 'module_key' => 'loss'],
            ['menu_code' => 'loss_galai', 'parent_code' => 'ops_loss', 'title' => 'Galai', 'url' => 'loss/manage/fer32fgd3fif952mcmbje8234gis3824', 'icon' => 'icon-primitive-dot', 'order_no' => 12, 'module_key' => 'loss'],
            ['menu_code' => 'loss_tash', 'parent_code' => 'ops_loss', 'title' => 'Tash', 'url' => 'loss/manage/n4l742kfdg96k3mbdw05k73mfmv6693k', 'icon' => 'icon-primitive-dot', 'order_no' => 13, 'module_key' => 'loss'],
            ['menu_code' => 'loss_sapphire', 'parent_code' => 'ops_loss', 'title' => 'Sapphire', 'url' => 'loss/manage/zr4th43570jmf23679jbde5tghy65f4h', 'icon' => 'icon-primitive-dot', 'order_no' => 14, 'module_key' => 'loss'],
            ['menu_code' => 'loss_magnet', 'parent_code' => 'ops_loss', 'title' => 'Magnet', 'url' => 'loss/manage/gqe2953fg4knbmassqww533fghjbc456', 'icon' => 'icon-primitive-dot', 'order_no' => 15, 'module_key' => 'loss'],
            ['menu_code' => 'loss_zero_balance', 'parent_code' => 'ops_loss', 'title' => 'Zero Balance', 'url' => 'loss/manage/43434325b7f95cbfe2e5edfbb1228c4a', 'icon' => 'icon-primitive-dot', 'order_no' => 16, 'module_key' => 'loss'],

            // REFINING & STOCK
            [
                'menu_code'      => 'ops_refining',
                'parent_code'    => null,
                'title'          => 'Refining',
                'url'            => 'refining',
                'icon'           => 'icon-loop4',
                'menu_type'      => 'sidebar',
                'section_header' => 'Operations',
                'order_no'       => 70,
                'module_key'     => 'refining',
            ],
            [
                'menu_code'      => 'ops_stock',
                'parent_code'    => null,
                'title'          => 'Stock',
                'url'            => 'stock',
                'icon'           => 'icon-box',
                'menu_type'      => 'sidebar',
                'section_header' => 'Operations',
                'order_no'       => 80,
                'module_key'     => 'stock',
            ],

            // MASTERS & REPORTS SECTION
            [
                'menu_code'      => 'masters',
                'parent_code'    => null,
                'title'          => 'Master',
                'url'            => '#menuMasters',
                'icon'           => 'icon-gear',
                'menu_type'      => 'sidebar',
                'section_header' => 'Masters & Reports',
                'order_no'       => 90,
                'module_key'     => 'master',
            ],
            ['menu_code' => 'master_loss_percentage', 'parent_code' => 'masters', 'title' => 'Loss Percentage', 'url' => 'master/loss_percentage', 'icon' => 'icon-primitive-dot', 'order_no' => 1, 'module_key' => 'loss_percentage'],
            ['menu_code' => 'master_petty_cash', 'parent_code' => 'masters', 'title' => 'Petty Cash Cust', 'url' => 'master/petty-cash', 'icon' => 'icon-primitive-dot', 'order_no' => 2, 'module_key' => 'petty_cash'],
            ['menu_code' => 'master_remark', 'parent_code' => 'masters', 'title' => 'Remark', 'url' => 'master/remark', 'icon' => 'icon-primitive-dot', 'order_no' => 3, 'module_key' => 'remark_master'],
            ['menu_code' => 'master_recent_files', 'parent_code' => 'masters', 'title' => 'Recent File', 'url' => '#menuRecentFile', 'icon' => 'icon-primitive-dot', 'order_no' => 4, 'module_key' => 'recent_files'],
            ['menu_code' => 'recent_cust_ledger', 'parent_code' => 'master_recent_files', 'title' => 'Recent Cust Ledger', 'url' => 'master/manage_recent_cust_ledger_file', 'icon' => 'icon-primitive-dot', 'order_no' => 1, 'module_key' => 'recent_files'],
            ['menu_code' => 'recent_daily_report', 'parent_code' => 'master_recent_files', 'title' => 'Recent Daily Report', 'url' => 'master/manage_recent_daily_report_file', 'icon' => 'icon-primitive-dot', 'order_no' => 2, 'module_key' => 'recent_files'],
            ['menu_code' => 'master_user', 'parent_code' => 'masters', 'title' => 'User', 'url' => 'master/user', 'icon' => 'icon-primitive-dot', 'order_no' => 5, 'module_key' => 'user_master'],

            // SYSTEM MASTER SECTION (UCC Master Data)
            [
                'menu_code'      => 'system_masters',
                'parent_code'    => null,
                'title'          => 'System Master',
                'url'            => '#menuSystemMasters',
                'icon'           => 'icon-database',
                'menu_type'      => 'sidebar',
                'section_header' => 'Masters & Reports',
                'order_no'       => 95,
                'module_key'     => 'system_masters',
            ],
            ['menu_code' => 'master_customer', 'parent_code' => 'system_masters', 'title' => 'Customer', 'url' => 'master/customer', 'icon' => 'icon-primitive-dot', 'order_no' => 1, 'module_key' => 'customer_master'],
            ['menu_code' => 'master_customer_mapping', 'parent_code' => 'system_masters', 'title' => 'Mapped Customer', 'url' => 'master/customer-mapping', 'icon' => 'icon-primitive-dot', 'order_no' => 2, 'module_key' => 'customer_mapping'],
            ['menu_code' => 'master_collection', 'parent_code' => 'system_masters', 'title' => 'Collection', 'url' => 'master/collection', 'icon' => 'icon-primitive-dot', 'order_no' => 3, 'module_key' => 'collection_master'],
            ['menu_code' => 'master_purity', 'parent_code' => 'system_masters', 'title' => 'Purity', 'url' => 'master/purity', 'icon' => 'icon-primitive-dot', 'order_no' => 4, 'module_key' => 'collection_master'],

            // ACCOUNT MASTER (Super Admin Standalone)
            [
                'menu_code'      => 'account_master',
                'parent_code'    => null,
                'title'          => 'Account',
                'url'            => 'account',
                'icon'           => 'icon-office',
                'menu_type'      => 'sidebar',
                'section_header' => 'Masters & Reports',
                'order_no'       => 100,
                'module_key'     => 'account_master',
            ],

            // PERMISSION MANAGEMENT MODULE (Super Admin Standalone)
            [
                'menu_code'      => 'permission_management',
                'parent_code'    => null,
                'title'          => 'Permission Management',
                'url'            => 'permission',
                'icon'           => 'icon-shield-check',
                'menu_type'      => 'sidebar',
                'section_header' => 'Masters & Reports',
                'order_no'       => 105,
                'module_key'     => 'permission',
            ],

            // REPORTS ACCORDION
            [
                'menu_code'      => 'reports',
                'parent_code'    => null,
                'title'          => 'Report',
                'url'            => '#menuReports',
                'icon'           => 'icon-file-spreadsheet',
                'menu_type'      => 'sidebar',
                'section_header' => 'Masters & Reports',
                'order_no'       => 110,
                'module_key'     => 'reports',
            ],
            ['menu_code' => 'report_cust_wise', 'parent_code' => 'reports', 'title' => 'Cust Wise Report', 'url' => 'report/cust_wise_report', 'icon' => 'icon-primitive-dot', 'order_no' => 1, 'module_key' => 'reports'],
            ['menu_code' => 'report_daily', 'parent_code' => 'reports', 'title' => 'Daily Report', 'url' => 'report/daily_report', 'icon' => 'icon-primitive-dot', 'order_no' => 2, 'module_key' => 'reports'],

            // CUSTOMER PORTAL
            [
                'menu_code'      => 'cust_statement',
                'parent_code'    => null,
                'title'          => 'My Statement',
                'url'            => 'report/customer_ledger_for_customer',
                'icon'           => 'icon-file-text2',
                'menu_type'      => 'sidebar',
                'section_header' => 'My Account',
                'order_no'       => 120,
                'module_key'     => 'customer_portal',
            ],

            // NAVBAR ITEMS
            [
                'menu_code'      => 'navbar_pending_badge',
                'parent_code'    => null,
                'title'          => 'Pending Waves Alert Badge',
                'url'            => 'raw-waves/manage',
                'icon'           => 'icon-bell2',
                'menu_type'      => 'navbar',
                'section_header' => 'Navbar',
                'order_no'       => 1,
                'module_key'     => 'raw_waves',
            ],
            [
                'menu_code'      => 'navbar_profile_menu',
                'parent_code'    => null,
                'title'          => 'User Profile & Sign Out',
                'url'            => 'logout',
                'icon'           => 'icon-user',
                'menu_type'      => 'navbar',
                'section_header' => 'Navbar',
                'order_no'       => 2,
                'module_key'     => 'auth',
            ],
        ];

        // Insert parent menus first, then children
        $menuIdMap = [];
        foreach ($menus as $m) {
            if ($m['parent_code'] === null) {
                $existing = $db->table('menus')->where('menu_code', $m['menu_code'])->get()->getRowArray();
                if (!$existing) {
                    $insertData = $m;
                    unset($insertData['parent_code']);
                    $insertData['parent_id']  = null;
                    $insertData['status']     = '1';
                    $insertData['created_at'] = $now;
                    $insertData['updated_at'] = $now;
                    $db->table('menus')->insert($insertData);
                    $menuIdMap[$m['menu_code']] = $db->insertID();
                } else {
                    $menuIdMap[$m['menu_code']] = (int)$existing['menu_id'];
                }
            }
        }

        foreach ($menus as $m) {
            if ($m['parent_code'] !== null) {
                $existing = $db->table('menus')->where('menu_code', $m['menu_code'])->get()->getRowArray();
                $parentId = $menuIdMap[$m['parent_code']] ?? null;
                if (!$existing) {
                    $insertData = $m;
                    unset($insertData['parent_code']);
                    $insertData['parent_id']  = $parentId;
                    $insertData['status']     = '1';
                    $insertData['created_at'] = $now;
                    $insertData['updated_at'] = $now;
                    $db->table('menus')->insert($insertData);
                    $menuIdMap[$m['menu_code']] = $db->insertID();
                } else {
                    $menuIdMap[$m['menu_code']] = (int)$existing['menu_id'];
                }
            }
        }

        echo "Seeding Permissions...\n";
        // 3. SEED PERMISSIONS
        $perms = [
            // Dashboard
            ['module_key' => 'dashboard', 'permission_code' => 'dashboard.view', 'permission_name' => 'View Dashboard', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'main_dashboard'],

            // Raw Waves
            ['module_key' => 'raw_waves', 'permission_code' => 'raw_waves.view', 'permission_name' => 'View Raw Waves Queue', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'ops_waves'],
            ['module_key' => 'raw_waves', 'permission_code' => 'raw_waves.create', 'permission_name' => 'Add New Counter Raw Wave', 'permission_type' => 'button', 'action_key' => 'create', 'menu_code' => 'ops_waves'],
            ['module_key' => 'raw_waves', 'permission_code' => 'raw_waves.edit', 'permission_name' => 'Edit Counter Raw Wave', 'permission_type' => 'button', 'action_key' => 'edit', 'menu_code' => 'ops_waves'],
            ['module_key' => 'raw_waves', 'permission_code' => 'raw_waves.verify', 'permission_name' => 'Verify & Re-weigh Incoming Waves', 'permission_type' => 'button', 'action_key' => 'verify', 'menu_code' => 'ops_waves'],
            ['module_key' => 'raw_waves', 'permission_code' => 'raw_waves.print', 'permission_name' => 'Download / Print Raw Wave Voucher', 'permission_type' => 'button', 'action_key' => 'print', 'menu_code' => 'ops_waves'],

            // Waves Financial Ledger
            ['module_key' => 'waves', 'permission_code' => 'waves.view', 'permission_name' => 'View Waves Ledger', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'ops_waves'],
            ['module_key' => 'waves', 'permission_code' => 'waves.create', 'permission_name' => 'Create Waves In/Out Record', 'permission_type' => 'button', 'action_key' => 'create', 'menu_code' => 'ops_waves'],
            ['module_key' => 'waves', 'permission_code' => 'waves.edit', 'permission_name' => 'Edit Waves In/Out Record', 'permission_type' => 'button', 'action_key' => 'edit', 'menu_code' => 'ops_waves'],
            ['module_key' => 'waves', 'permission_code' => 'waves.view_details', 'permission_name' => 'View Waves Transaction Details', 'permission_type' => 'button', 'action_key' => 'view', 'menu_code' => 'ops_waves'],

            // Process Waves Core Actions
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.view', 'permission_name' => 'View Process Waves Sheets', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'ops_process'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.transfer', 'permission_name' => 'Transfer Batches to Next Department', 'permission_type' => 'button', 'action_key' => 'transfer', 'menu_code' => 'ops_process'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.checkpoint', 'permission_name' => 'Insert Mid-shift Stock Checkpoint', 'permission_type' => 'button', 'action_key' => 'checkpoint', 'menu_code' => 'ops_process'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.print', 'permission_name' => 'Print Process Transactions and Sheets', 'permission_type' => 'button', 'action_key' => 'print', 'menu_code' => 'ops_process'],

            // Process Department Permissions (18 departments mapped to account_id)
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_2', 'permission_name' => 'Access Casting Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_casting'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_4', 'permission_name' => 'Access Filing Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_filing'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_3', 'permission_name' => 'Access Heera Light Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_heera_light'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_8', 'permission_name' => 'Access Packing Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_packing'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_18', 'permission_name' => 'Access Transfer Packing Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_transfer_packing'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_6', 'permission_name' => 'Access Polish Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_polish'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_13', 'permission_name' => 'Access G58-Polish Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_g58_polish'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_15', 'permission_name' => 'Access G58-Filing Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_g58_filing'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_14', 'permission_name' => 'Access G58-Setting Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_g58_setting'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_17', 'permission_name' => 'Access G58-Transfer Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_g58_transfer'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_19', 'permission_name' => 'Access PO Check Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_po_check'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_20', 'permission_name' => 'Access Galai Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_galai'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_21', 'permission_name' => 'Access Tash Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_tash'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_22', 'permission_name' => 'Access Sapphire Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_sapphire'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_23', 'permission_name' => 'Access Megnet (CNC) Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_cnc'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_7', 'permission_name' => 'Access Rodium Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_rodium'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_5', 'permission_name' => 'Access Setting Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_setting'],
            ['module_key' => 'process_waves', 'permission_code' => 'process_waves.dept_16', 'permission_name' => 'Access Zero Balance Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'proc_zero_balance'],

            // Locker & Verification
            ['module_key' => 'locker', 'permission_code' => 'locker.view', 'permission_name' => 'View Locker Vault Ledger', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'ops_locker'],
            ['module_key' => 'locker', 'permission_code' => 'locker.check_stock', 'permission_name' => 'Check Locker Stock Calculation', 'permission_type' => 'button', 'action_key' => 'check_stock', 'menu_code' => 'ops_locker'],
            ['module_key' => 'locker', 'permission_code' => 'locker.transfer', 'permission_name' => 'Transfer from Locker to Department', 'permission_type' => 'button', 'action_key' => 'transfer', 'menu_code' => 'ops_locker'],
            ['module_key' => 'locker_verification', 'permission_code' => 'locker_verification.view', 'permission_name' => 'View Locker Stock Verification', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'ops_locker_verification'],
            ['module_key' => 'locker_verification', 'permission_code' => 'locker_verification.manage', 'permission_name' => 'Confirm Data & Restart Verification', 'permission_type' => 'button', 'action_key' => 'manage', 'menu_code' => 'ops_locker_verification'],

            // Loss Core Actions & Department Permissions
            ['module_key' => 'loss', 'permission_code' => 'loss.view', 'permission_name' => 'View Loss Hub Overview', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'ops_loss'],
            ['module_key' => 'loss', 'permission_code' => 'loss.consolidate', 'permission_name' => 'Consolidate Loss to Refining', 'permission_type' => 'button', 'action_key' => 'consolidate', 'menu_code' => 'ops_loss'],
            ['module_key' => 'loss', 'permission_code' => 'loss.print', 'permission_name' => 'Print Loss Sheet', 'permission_type' => 'button', 'action_key' => 'print', 'menu_code' => 'ops_loss'],
            ['module_key' => 'loss', 'permission_code' => 'loss.dept_2', 'permission_name' => 'Access Casting Loss', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'loss_casting'],
            ['module_key' => 'loss', 'permission_code' => 'loss.dept_4', 'permission_name' => 'Access Filing Loss', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'loss_filing'],
            ['module_key' => 'loss', 'permission_code' => 'loss.dept_3', 'permission_name' => 'Access Heera Light Loss', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'loss_heera_light'],
            ['module_key' => 'loss', 'permission_code' => 'loss.dept_8', 'permission_name' => 'Access Packing Loss', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'loss_packing'],
            ['module_key' => 'loss', 'permission_code' => 'loss.dept_6', 'permission_name' => 'Access Polish Loss', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'loss_polish'],
            ['module_key' => 'loss', 'permission_code' => 'loss.dept_7', 'permission_name' => 'Access Rodium Loss', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'loss_rodium'],
            ['module_key' => 'loss', 'permission_code' => 'loss.dept_5', 'permission_name' => 'Access Setting Loss', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'loss_setting'],
            ['module_key' => 'loss', 'permission_code' => 'loss.dept_13', 'permission_name' => 'Access G58-Polish Loss', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'loss_g58_polish'],
            ['module_key' => 'loss', 'permission_code' => 'loss.dept_15', 'permission_name' => 'Access G58-Filing Loss', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'loss_g58_filing'],
            ['module_key' => 'loss', 'permission_code' => 'loss.dept_14', 'permission_name' => 'Access G58-Setting Loss', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'loss_g58_setting'],
            ['module_key' => 'loss', 'permission_code' => 'loss.dept_20', 'permission_name' => 'Access Galai Loss', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'loss_galai'],
            ['module_key' => 'loss', 'permission_code' => 'loss.dept_21', 'permission_name' => 'Access Tash Loss', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'loss_tash'],
            ['module_key' => 'loss', 'permission_code' => 'loss.dept_22', 'permission_name' => 'Access Sapphire Loss', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'loss_sapphire'],
            ['module_key' => 'loss', 'permission_code' => 'loss.dept_23', 'permission_name' => 'Access Magnet Loss', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'loss_magnet'],
            ['module_key' => 'loss', 'permission_code' => 'loss.dept_16', 'permission_name' => 'Access Zero Balance Loss', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'loss_zero_balance'],

            // Refining Department
            ['module_key' => 'refining', 'permission_code' => 'refining.view', 'permission_name' => 'View Refining Department', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'ops_refining'],
            ['module_key' => 'refining', 'permission_code' => 'refining.transfer', 'permission_name' => 'Transfer Refining to Locker', 'permission_type' => 'button', 'action_key' => 'transfer', 'menu_code' => 'ops_refining'],
            ['module_key' => 'refining', 'permission_code' => 'refining.print', 'permission_name' => 'Print Refining Receipt', 'permission_type' => 'button', 'action_key' => 'print', 'menu_code' => 'ops_refining'],

            // Stock Matrix
            ['module_key' => 'stock', 'permission_code' => 'stock.view', 'permission_name' => 'View Factory Stock Matrix Report', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'ops_stock'],

            // Customer Master
            ['module_key' => 'customer_master', 'permission_code' => 'customer.view', 'permission_name' => 'View Customer Master Table', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'master_customer'],
            ['module_key' => 'customer_master', 'permission_code' => 'customer.create', 'permission_name' => 'Create New Customer', 'permission_type' => 'button', 'action_key' => 'create', 'menu_code' => 'master_customer'],
            ['module_key' => 'customer_master', 'permission_code' => 'customer.edit', 'permission_name' => 'Edit Customer Record', 'permission_type' => 'button', 'action_key' => 'edit', 'menu_code' => 'master_customer'],
            ['module_key' => 'customer_master', 'permission_code' => 'customer.toggle', 'permission_name' => 'Toggle Customer Active Status', 'permission_type' => 'button', 'action_key' => 'toggle', 'menu_code' => 'master_customer'],
            ['module_key' => 'customer_master', 'permission_code' => 'customer.wastage', 'permission_name' => 'Manage Customer Wastage Rules', 'permission_type' => 'button', 'action_key' => 'manage', 'menu_code' => 'master_customer'],

            // Customer Mapping Master
            ['module_key' => 'customer_mapping', 'permission_code' => 'customer_mapping.view', 'permission_name' => 'View Customer Mapping List', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'master_customer_mapping'],
            ['module_key' => 'customer_mapping', 'permission_code' => 'customer_mapping.create', 'permission_name' => 'Create New Mapping', 'permission_type' => 'button', 'action_key' => 'create', 'menu_code' => 'master_customer_mapping'],
            ['module_key' => 'customer_mapping', 'permission_code' => 'customer_mapping.edit', 'permission_name' => 'Edit Mapping Record', 'permission_type' => 'button', 'action_key' => 'edit', 'menu_code' => 'master_customer_mapping'],
            ['module_key' => 'customer_mapping', 'permission_code' => 'customer_mapping.import', 'permission_name' => 'Import Customer Mappings from Excel', 'permission_type' => 'button', 'action_key' => 'import', 'menu_code' => 'master_customer_mapping'],
            ['module_key' => 'customer_mapping', 'permission_code' => 'customer_mapping.toggle', 'permission_name' => 'Toggle Mapping Status', 'permission_type' => 'button', 'action_key' => 'toggle', 'menu_code' => 'master_customer_mapping'],

            // Collection Master
            ['module_key' => 'collection_master', 'permission_code' => 'collection.view', 'permission_name' => 'View Collection Master List', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'master_collection'],
            ['module_key' => 'collection_master', 'permission_code' => 'collection.create', 'permission_name' => 'Create New Collection', 'permission_type' => 'button', 'action_key' => 'create', 'menu_code' => 'master_collection'],
            ['module_key' => 'collection_master', 'permission_code' => 'collection.edit', 'permission_name' => 'Edit Collection Record', 'permission_type' => 'button', 'action_key' => 'edit', 'menu_code' => 'master_collection'],
            ['module_key' => 'collection_master', 'permission_code' => 'collection.toggle', 'permission_name' => 'Toggle Collection Status', 'permission_type' => 'button', 'action_key' => 'toggle', 'menu_code' => 'master_collection'],

            // Loss Percentage Master
            ['module_key' => 'loss_percentage', 'permission_code' => 'loss_percentage.view', 'permission_name' => 'View Loss Percentage Matrix', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'master_loss_percentage'],
            ['module_key' => 'loss_percentage', 'permission_code' => 'loss_percentage.edit', 'permission_name' => 'Update Department Loss Percentages', 'permission_type' => 'button', 'action_key' => 'edit', 'menu_code' => 'master_loss_percentage'],

            // Petty Cash Master
            ['module_key' => 'petty_cash', 'permission_code' => 'petty_cash.view', 'permission_name' => 'View Petty Cash Accounts', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'master_petty_cash'],
            ['module_key' => 'petty_cash', 'permission_code' => 'petty_cash.create', 'permission_name' => 'Designate Petty Cash Customer', 'permission_type' => 'button', 'action_key' => 'create', 'menu_code' => 'master_petty_cash'],
            ['module_key' => 'petty_cash', 'permission_code' => 'petty_cash.edit', 'permission_name' => 'Update Petty Cash Customer', 'permission_type' => 'button', 'action_key' => 'edit', 'menu_code' => 'master_petty_cash'],
            ['module_key' => 'petty_cash', 'permission_code' => 'petty_cash.toggle', 'permission_name' => 'Toggle Petty Cash Status', 'permission_type' => 'button', 'action_key' => 'toggle', 'menu_code' => 'master_petty_cash'],

            // Remark Master
            ['module_key' => 'remark_master', 'permission_code' => 'remark.view', 'permission_name' => 'View Remark Master List', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'master_remark'],
            ['module_key' => 'remark_master', 'permission_code' => 'remark.create', 'permission_name' => 'Create New Remark', 'permission_type' => 'button', 'action_key' => 'create', 'menu_code' => 'master_remark'],
            ['module_key' => 'remark_master', 'permission_code' => 'remark.edit', 'permission_name' => 'Edit Remark Record', 'permission_type' => 'button', 'action_key' => 'edit', 'menu_code' => 'master_remark'],
            ['module_key' => 'remark_master', 'permission_code' => 'remark.toggle', 'permission_name' => 'Toggle Remark Status', 'permission_type' => 'button', 'action_key' => 'toggle', 'menu_code' => 'master_remark'],

            // Recent Files
            ['module_key' => 'recent_files', 'permission_code' => 'recent_files.view', 'permission_name' => 'View & Download Recent Report Files', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'master_recent_files'],
            ['module_key' => 'recent_files', 'permission_code' => 'recent_files.delete', 'permission_name' => 'Delete Recent Report Files', 'permission_type' => 'button', 'action_key' => 'delete', 'menu_code' => 'master_recent_files'],

            // User Master
            ['module_key' => 'user_master', 'permission_code' => 'user_master.view', 'permission_name' => 'View User Master List', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'master_user'],
            ['module_key' => 'user_master', 'permission_code' => 'user_master.create', 'permission_name' => 'Create New User Account', 'permission_type' => 'button', 'action_key' => 'create', 'menu_code' => 'master_user'],
            ['module_key' => 'user_master', 'permission_code' => 'user_master.edit', 'permission_name' => 'Edit User Account & Role', 'permission_type' => 'button', 'action_key' => 'edit', 'menu_code' => 'master_user'],
            ['module_key' => 'user_master', 'permission_code' => 'user_master.view_details', 'permission_name' => 'View User Details Modal', 'permission_type' => 'button', 'action_key' => 'view', 'menu_code' => 'master_user'],
            ['module_key' => 'user_master', 'permission_code' => 'user_master.toggle', 'permission_name' => 'Activate / Deactivate User Status', 'permission_type' => 'button', 'action_key' => 'toggle', 'menu_code' => 'master_user'],

            // Account Master
            ['module_key' => 'account_master', 'permission_code' => 'account_master.view', 'permission_name' => 'View Account Master List', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'account_master'],
            ['module_key' => 'account_master', 'permission_code' => 'account_master.create', 'permission_name' => 'Create New Production Account', 'permission_type' => 'button', 'action_key' => 'create', 'menu_code' => 'account_master'],
            ['module_key' => 'account_master', 'permission_code' => 'account_master.edit', 'permission_name' => 'Edit Account Record', 'permission_type' => 'button', 'action_key' => 'edit', 'menu_code' => 'account_master'],
            ['module_key' => 'account_master', 'permission_code' => 'account_master.mapping', 'permission_name' => 'Configure Account Department Mappings', 'permission_type' => 'button', 'action_key' => 'mapping', 'menu_code' => 'account_master'],
            ['module_key' => 'account_master', 'permission_code' => 'account_master.toggle', 'permission_name' => 'Toggle Account Active Status', 'permission_type' => 'button', 'action_key' => 'toggle', 'menu_code' => 'account_master'],

            // Reports
            ['module_key' => 'reports', 'permission_code' => 'reports.cust_wise', 'permission_name' => 'View Customer Wise Report', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'report_cust_wise'],
            ['module_key' => 'reports', 'permission_code' => 'reports.customer_ledger', 'permission_name' => 'View Customer Transaction Ledger', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'report_cust_wise'],
            ['module_key' => 'reports', 'permission_code' => 'reports.daily_report', 'permission_name' => 'View Daily Production Report', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'report_daily'],
            ['module_key' => 'reports', 'permission_code' => 'daily_report.approve', 'permission_name' => 'Approve & Submit Daily Production Report', 'permission_type' => 'button', 'action_key' => 'approve', 'menu_code' => 'report_daily'],

            // Customer Portal
            ['module_key' => 'customer_portal', 'permission_code' => 'customer_portal.view', 'permission_name' => 'View Personal Customer Statement', 'permission_type' => 'page', 'action_key' => 'view', 'menu_code' => 'cust_statement'],
            ['module_key' => 'customer_portal', 'permission_code' => 'customer_portal.approve', 'permission_name' => 'Approve Personal Statement', 'permission_type' => 'button', 'action_key' => 'approve', 'menu_code' => 'cust_statement'],

            // Permission Management (Super Admin Standalone)
            ['module_key' => 'permission', 'permission_code' => 'permission.superadmin', 'permission_name' => 'Manage System Roles & Permissions', 'permission_type' => 'page', 'action_key' => 'manage', 'menu_code' => 'permission_management'],
        ];

        $permIdMap = [];
        foreach ($perms as $p) {
            $existing = $db->table('permissions')->where('permission_code', $p['permission_code'])->get()->getRowArray();
            $menuId   = $menuIdMap[$p['menu_code']] ?? null;
            if (!$existing) {
                $insertData = [
                    'module_key'      => $p['module_key'],
                    'permission_code' => $p['permission_code'],
                    'permission_name' => $p['permission_name'],
                    'permission_type' => $p['permission_type'],
                    'action_key'      => $p['action_key'],
                    'menu_id'         => $menuId,
                    'description'     => $p['permission_name'],
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
                $db->table('permissions')->insert($insertData);
                $permIdMap[$p['permission_code']] = $db->insertID();
            } else {
                $permIdMap[$p['permission_code']] = (int)$existing['permission_id'];
            }
        }

        echo "Seeding Baseline Role Permissions and Menu Visibility...\n";
        // 4. SEED BASELINE ROLE PERMISSIONS
        // Superadmin receives 100% of menus and 100% of permissions
        $allMenuIds = array_values($menuIdMap);
        $allPermIds = array_values($permIdMap);

        $superadminRoleId = $roleMap['superadmin'] ?? 14;
        if ($db->tableExists('role_menu_permissions')) {
            foreach ($allMenuIds as $mId) {
                $db->table('role_menu_permissions')->replace([
                    'role_id'    => $superadminRoleId,
                    'menu_id'    => $mId,
                    'is_visible' => 1,
                    'order_no'   => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
        foreach ($allPermIds as $pId) {
            $db->table('role_permissions')->replace([
                'role_id'    => $superadminRoleId,
                'permission_id' => $pId,
                'is_granted' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Helper closures for assigning baseline permissions
        $hasRoleMenuTable = $db->tableExists('role_menu_permissions');
        $assignRole = function(string $roleKey, array $visibleMenuCodes, array $grantedPermCodes) use ($db, $roleMap, $menuIdMap, $permIdMap, $now, $hasRoleMenuTable) {
            $roleId = $roleMap[$roleKey] ?? null;
            if (!$roleId) return;

            if ($hasRoleMenuTable) {
                foreach ($visibleMenuCodes as $mCode) {
                    if (isset($menuIdMap[$mCode])) {
                        $db->table('role_menu_permissions')->replace([
                            'role_id'    => $roleId,
                            'menu_id'    => $menuIdMap[$mCode],
                            'is_visible' => 1,
                            'order_no'   => 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }

            foreach ($grantedPermCodes as $pCode) {
                if (isset($permIdMap[$pCode])) {
                    $db->table('role_permissions')->replace([
                        'role_id'        => $roleId,
                        'permission_id'  => $permIdMap[$pCode],
                        'permission_key' => $pCode,
                        'is_granted'     => 1,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ]);
                }
            }
        };

        // ADMIN ROLE: full manufacturing, masters (except account), reports (daily report), cross-location raw waves
        $adminMenus = [
            'main_dashboard', 'ops_waves', 'ops_process',
            'proc_casting', 'proc_filing', 'proc_heera_light', 'proc_polish', 'proc_g58_polish', 'proc_g58_filing', 'proc_g58_setting', 'proc_g58_transfer',
            'proc_po_check', 'proc_galai', 'proc_tash', 'proc_sapphire', 'proc_cnc', 'proc_setting', 'proc_zero_balance',
            'masters', 'master_customer', 'master_customer_mapping', 'master_collection', 'master_loss_percentage', 'master_petty_cash', 'master_remark', 'master_recent_files', 'recent_cust_ledger', 'recent_daily_report', 'master_user',
            'reports', 'report_daily', 'navbar_pending_badge', 'navbar_profile_menu'
        ];
        $adminPerms = [
            'dashboard.view', 'raw_waves.view', 'raw_waves.create', 'raw_waves.edit', 'raw_waves.verify', 'raw_waves.print',
            'waves.view', 'waves.create', 'waves.edit', 'waves.view_details',
            'process_waves.view', 'process_waves.transfer', 'process_waves.checkpoint', 'process_waves.print',
            'process_waves.dept_2', 'process_waves.dept_4', 'process_waves.dept_3', 'process_waves.dept_6', 'process_waves.dept_13', 'process_waves.dept_15', 'process_waves.dept_14', 'process_waves.dept_17', 'process_waves.dept_19', 'process_waves.dept_20', 'process_waves.dept_21', 'process_waves.dept_22', 'process_waves.dept_23', 'process_waves.dept_5', 'process_waves.dept_16',
            'customer.view', 'customer.create', 'customer.edit', 'customer.toggle', 'customer.wastage',
            'customer_mapping.view', 'customer_mapping.create', 'customer_mapping.edit', 'customer_mapping.import', 'customer_mapping.toggle',
            'collection.view', 'collection.create', 'collection.edit', 'collection.toggle',
            'loss_percentage.view', 'loss_percentage.edit',
            'petty_cash.view', 'petty_cash.create', 'petty_cash.edit', 'petty_cash.toggle',
            'remark.view', 'remark.create', 'remark.edit', 'remark.toggle',
            'recent_files.view', 'recent_files.delete',
            'user_master.view', 'user_master.create', 'user_master.edit', 'user_master.view_details', 'user_master.toggle',
            'reports.daily_report', 'daily_report.approve'
        ];
        $assignRole('admin', $adminMenus, $adminPerms);

        // PROCESS_MANAGER ROLE: manufacturing pipeline (all 18 depts), Raw Waves, Locker view, Loss, Refining
        $pmMenus = [
            'main_dashboard', 'ops_waves', 'ops_process',
            'proc_casting', 'proc_filing', 'proc_heera_light', 'proc_packing', 'proc_transfer_packing', 'proc_polish', 'proc_g58_polish', 'proc_g58_filing', 'proc_g58_setting', 'proc_g58_transfer',
            'proc_po_check', 'proc_galai', 'proc_tash', 'proc_sapphire', 'proc_cnc', 'proc_rodium', 'proc_setting', 'proc_zero_balance',
            'ops_locker', 'ops_locker_verification', 'ops_loss',
            'loss_hub', 'loss_casting', 'loss_filing', 'loss_heera_light', 'loss_packing', 'loss_polish', 'loss_rodium', 'loss_setting', 'loss_g58_polish', 'loss_g58_filing', 'loss_g58_setting', 'loss_galai', 'loss_tash', 'loss_sapphire', 'loss_magnet', 'loss_zero_balance',
            'ops_refining', 'navbar_pending_badge', 'navbar_profile_menu'
        ];
        $pmPerms = [
            'dashboard.view', 'raw_waves.view', 'raw_waves.create', 'raw_waves.verify', 'raw_waves.print',
            'waves.view', 'waves.create', 'waves.view_details',
            'process_waves.view', 'process_waves.transfer', 'process_waves.checkpoint', 'process_waves.print',
            'process_waves.dept_2', 'process_waves.dept_4', 'process_waves.dept_3', 'process_waves.dept_8', 'process_waves.dept_18', 'process_waves.dept_6', 'process_waves.dept_13', 'process_waves.dept_15', 'process_waves.dept_14', 'process_waves.dept_17', 'process_waves.dept_19', 'process_waves.dept_20', 'process_waves.dept_21', 'process_waves.dept_22', 'process_waves.dept_23', 'process_waves.dept_7', 'process_waves.dept_5', 'process_waves.dept_16',
            'locker.view', 'locker.check_stock', 'locker.transfer', 'locker_verification.view', 'locker_verification.manage',
            'loss.view', 'loss.consolidate', 'loss.print',
            'loss.dept_2', 'loss.dept_4', 'loss.dept_3', 'loss.dept_8', 'loss.dept_6', 'loss.dept_7', 'loss.dept_5', 'loss.dept_13', 'loss.dept_15', 'loss.dept_14', 'loss.dept_20', 'loss.dept_21', 'loss.dept_22', 'loss.dept_23', 'loss.dept_16',
            'refining.view', 'refining.transfer', 'refining.print'
        ];
        $assignRole('process_manager', $pmMenus, $pmPerms);

        // JR_PROCESS_MANAGER ROLE: Finishing stages only (Packing, Transfer Packing, Rodium)
        $jpmMenus = [
            'main_dashboard', 'ops_process', 'proc_packing', 'proc_transfer_packing', 'proc_rodium', 'navbar_profile_menu'
        ];
        $jpmPerms = [
            'dashboard.view', 'process_waves.view', 'process_waves.transfer', 'process_waves.checkpoint', 'process_waves.print',
            'process_waves.dept_8', 'process_waves.dept_18', 'process_waves.dept_7'
        ];
        $assignRole('jr_process_manager', $jpmMenus, $jpmPerms);

        // LOCKER ROLE: Central Locker vault ledger & verification
        $lockerMenus = [
            'main_dashboard', 'ops_locker', 'ops_locker_verification', 'navbar_profile_menu'
        ];
        $lockerPerms = [
            'dashboard.view', 'locker.view', 'locker.check_stock', 'locker.transfer', 'locker_verification.view', 'locker_verification.manage'
        ];
        $assignRole('locker', $lockerMenus, $lockerPerms);

        // G58 ROLE: G58 division stages and loss
        $g58Menus = [
            'main_dashboard', 'ops_process', 'proc_g58_polish', 'proc_g58_filing', 'proc_g58_setting', 'proc_g58_transfer',
            'ops_loss', 'loss_g58_polish', 'loss_g58_filing', 'loss_g58_setting', 'navbar_profile_menu'
        ];
        $g58Perms = [
            'dashboard.view', 'process_waves.view', 'process_waves.transfer', 'process_waves.checkpoint', 'process_waves.print',
            'process_waves.dept_13', 'process_waves.dept_15', 'process_waves.dept_14', 'process_waves.dept_17',
            'loss.view', 'loss.consolidate', 'loss.print',
            'loss.dept_13', 'loss.dept_15', 'loss.dept_14'
        ];
        $assignRole('g58', $g58Menus, $g58Perms);

        // USER ROLE: Counter entry & inward verification
        $userMenus = [
            'ops_waves', 'navbar_pending_badge', 'navbar_profile_menu'
        ];
        $userPerms = [
            'raw_waves.view', 'raw_waves.create', 'raw_waves.verify', 'raw_waves.print'
        ];
        $assignRole('user', $userMenus, $userPerms);

        // CUST ROLE: Customer Statement only
        $custMenus = [
            'cust_statement', 'navbar_profile_menu'
        ];
        $custPerms = [
            'customer_portal.view', 'customer_portal.approve'
        ];
        $assignRole('cust', $custMenus, $custPerms);

        echo "Seeding completed successfully.\n";
    }
}

