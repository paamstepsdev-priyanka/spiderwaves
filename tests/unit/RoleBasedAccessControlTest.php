<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\AccountModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * RoleBasedAccessControlTest
 *
 * Verifies end-to-end RBAC, route access, menu visibility, and data scope
 * matching the legacy CI2 audit across all 8 active roles:
 * 1. superadmin
 * 2. admin
 * 3. process_manager
 * 4. jr_process_manager
 * 5. locker
 * 6. g58
 * 7. user (floor agent)
 * 8. cust (customer statement portal)
 *
 * Also verifies:
 * - Dispatch module exclusion
 * - Database safety (new-spiderwaves on port 3307)
 */
final class RoleBasedAccessControlTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $DBGroup = 'default';

    protected string $castingKey;
    protected string $packingKey;
    protected string $transferPackingKey;
    protected string $rhodiumKey;
    protected string $g58PolishKey;
    protected string $g58FilingKey;
    protected string $g58SettingKey;
    protected string $g58TransferKey;
    protected string $custKey = '5c8263667b110ef9ae30ed9760ecf8bb';
    protected string $otherCustKey = 'ce556bdd5b8f904dbb5664c2d8381dbc';

    protected function setUp(): void
    {
        parent::setUp();
        \Config\Database::connect('default');

        $accountModel = model(AccountModel::class);
        $this->castingKey         = $accountModel->find(AccountModel::CASTING_ID)['private_key'] ?? '';
        $this->packingKey         = $accountModel->find(AccountModel::PACKING_ID)['private_key'] ?? '';
        $this->transferPackingKey = $accountModel->find(AccountModel::TRANSFER_PACKING_ID)['private_key'] ?? '';
        $this->rhodiumKey         = $accountModel->find(AccountModel::RODIUM_ID)['private_key'] ?? '';
        $this->g58PolishKey       = $accountModel->find(AccountModel::G58_POLISH_ID)['private_key'] ?? '';
        $this->g58FilingKey       = $accountModel->find(AccountModel::G58_FILING_ID)['private_key'] ?? '';
        $this->g58SettingKey      = $accountModel->find(AccountModel::G58_SETTING_ID)['private_key'] ?? '';
        $this->g58TransferKey     = $accountModel->find(AccountModel::G58_TRANSFER_ID)['private_key'] ?? '';
    }

    /**
     * 1. Database Safety Verification.
     * Active DB MUST be new-spiderwaves on port 3307.
     */
    public function testDatabaseSafety(): void
    {
        $db = \Config\Database::connect('default');
        $this->assertSame('new-spiderwaves', $db->database, 'Active database must be new-spiderwaves.');
        $this->assertSame(3307, (int) $db->port, 'Active database port must be 3307.');
        $this->assertNotSame('spiderwaves', $db->database, 'CI2 reference database must not be the active database.');
    }

    /**
     * 2. Role 1: Superadmin (ho).
     * Has full access to dashboard, raw waves, cross-location visibility, process waves,
     * locker, loss, refining, reports, and all master modules.
     */
    public function testSuperadminRoleAccess(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 9,
                'user'         => 'superadmin',
                'access_level' => 'superadmin',
                'location'     => 'ho',
                'private_key'  => 'bcd5ac657b110ef9ae30ed9760ecf8bb',
            ],
        ];

        // Dashboard - Exact 13 Process Stage Cards Parity
        $res = $this->withSession($session)->get('dashboard');
        $res->assertStatus(200);
        $body = $res->getBody();
        $this->assertStringContainsString('Casting', $body);
        $this->assertStringContainsString('Filing', $body);
        $this->assertStringContainsString('Heera Light', $body);
        $this->assertStringContainsString('Packing', $body);
        $this->assertStringContainsString('Polish', $body);
        $this->assertStringContainsString('Rodium', $body);
        $this->assertStringContainsString('Transfer_Packing', $body);
        $this->assertStringContainsString('PO Check', $body);
        $this->assertStringContainsString('Galai', $body);
        $this->assertStringContainsString('Tash', $body);
        $this->assertStringContainsString('Sapphire', $body);
        $this->assertStringContainsString('Setting', $body);
        $this->assertStringContainsString('Zero Balance', $body);
        $this->assertStringContainsString('process-waves/manage/' . $this->castingKey, $body);

        // Masters
        $res = $this->withSession($session)->get('master/user');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('master/customer');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('master/customer-mapping');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('master/collection');
        $res->assertStatus(200);

        // Raw Waves
        $res = $this->withSession($session)->get('raw-waves');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('raw-waves/manage');
        $res->assertStatus(200);

        // Process Waves (Casting)
        $res = $this->withSession($session)->get('process-waves/manage/' . $this->castingKey);
        $res->assertStatus(200);

        // Locker
        $res = $this->withSession($session)->get('locker');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('locker-stock-verification');
        $res->assertStatus(200);

        // Loss & Refining
        $res = $this->withSession($session)->get('loss');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('refining');
        $res->assertStatus(200);

        // Reports
        $res = $this->withSession($session)->get('report/stock-report');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('report/cust_wise_report');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('report/daily_report');
        $res->assertStatus(200);
    }

    /**
     * 3. Role 2: Admin (ho - paresh, admin).
     * Has dashboard, raw waves, cross-location visibility, process waves,
     * reports, locker, loss, refining, and all master modules.
     */
    public function testAdminRoleAccess(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 1,
                'user'         => 'paresh',
                'access_level' => 'admin',
                'location'     => 'ho',
                'private_key'  => '9cedbbec7b110ef9ae30ed9760ecf8bb',
            ],
        ];

        // Dashboard
        $res = $this->withSession($session)->get('dashboard');
        $res->assertStatus(200);

        // Masters
        $res = $this->withSession($session)->get('master/user');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('master/customer');
        $res->assertStatus(200);

        // Raw Waves Actions
        $res = $this->withSession($session)->get('raw-waves');
        $res->assertStatus(200);

        // Process Waves
        $res = $this->withSession($session)->get('process-waves/manage/' . $this->castingKey);
        $res->assertStatus(200);

        // Reports
        $res = $this->withSession($session)->get('report/daily_report');
        $res->assertStatus(200);

        // Stock Report is blocked for Admin (restricted exclusively to Superadmin)
        $res = $this->withSession($session)->get('report/stock-report');
        $res->assertRedirectTo(base_url('dashboard'));
        $res = $this->withSession($session)->get('stock');
        $res->assertRedirectTo(base_url('dashboard'));

        // Recent File routes (CI2 feature for Admin)
        $res = $this->withSession($session)->get('master/manage_recent_cust_ledger_file');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('master/manage_recent_daily_report_file');
        $res->assertStatus(200);

        // Dashboard view contains exact 7 CI2 modules from screenshot
        $dashboardRes = $this->withSession($session)->get('dashboard');
        $dashboardRes->assertStatus(200);
        $body = $dashboardRes->getBody();
        $this->assertStringContainsString('Casting', $body);
        $this->assertStringContainsString('Filing', $body);
        $this->assertStringContainsString('Heera Light', $body);
        $this->assertStringContainsString('Polish', $body);
        $this->assertStringContainsString('Setting', $body);
        $this->assertStringContainsString('Refining', $body);
        $this->assertStringContainsString('Zero Balance', $body);

        // Admin navbar menu verification matching screenshot
        $this->assertStringContainsString('Waves', $body);
        $this->assertStringContainsString('Recent File', $body);
        $this->assertStringContainsString('Recent Cust Ledger', $body);
        $this->assertStringContainsString('Recent Daily Report', $body);

        // Stock menu item is hidden from Admin sidebar
        $this->assertStringNotContainsString('<span>Stock</span>', $body);
    }

    /**
     * 4. Role 3: Process Manager (ug - govind).
     * Operational manufacturing access, Raw Waves, Process Waves, Locker view/verification,
     * Loss, Refining. MUST NOT access Masters, User Master, or Financial Reports.
     */
    public function testProcessManagerRoleAccessAndRestrictions(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 4,
                'user'         => 'govind',
                'access_level' => 'process_manager',
                'location'     => 'ug',
                'private_key'  => '9f82e9537b110ef9ae30ed9760ecf8bb',
            ],
        ];

        // Permitted
        $res = $this->withSession($session)->get('dashboard');
        $res->assertStatus(200);
        $res->assertSee('Dashboard');
        $res->assertSee('Waves');
        $res->assertSee('Process');
        $res->assertSee('Locker');
        $res->assertSee('Locker Stock Verification');
        // Ensure sidebar excludes non-assigned modules (Loss, Refining, Stock, Masters, Reports) matching CI2
        $res->assertDontSee('id="menuLoss"');
        $res->assertDontSee('Loss Hub');
        $res->assertDontSee('id="menuMasters"');
        $res->assertDontSee('Customer Mapping');
        $res->assertDontSee('id="menuReports"');
        $res = $this->withSession($session)->get('raw-waves');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('process-waves/manage/' . $this->castingKey);
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('locker');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('locker-stock-verification');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('loss');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('refining');
        $res->assertStatus(200);

        // RESTRICTED: Masters
        $res = $this->withSession($session)->get('master/user');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('master/customer');
        $res->assertRedirect();

        // RESTRICTED: Financial & Admin Reports
        $res = $this->withSession($session)->get('report/daily_report');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('report/stock-report');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('report/cust_wise_report');
        $res->assertRedirect();
    }

    /**
     * 5. Role 4: Jr Process Manager (ug - HITESH).
     * Dashboard, Packing, Transfer Packing, Rhodium only.
     * MUST NOT access Casting, Filing, Polish, Raw Waves, Locker, Loss, Refining, Reports, Masters.
     */
    public function testJrProcessManagerRoleAccessAndRestrictions(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 5,
                'user'         => 'HITESH',
                'access_level' => 'jr_process_manager',
                'location'     => 'ug',
                'private_key'  => '185b8e747b110ef9ae30ed9760ecf8bb',
            ],
        ];

        // Permitted
        $res = $this->withSession($session)->get('dashboard');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('process-waves/manage/' . $this->packingKey);
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('process-waves/manage/' . $this->transferPackingKey);
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('process-waves/manage/' . $this->rhodiumKey);
        $res->assertStatus(200);

        // RESTRICTED: Other workshop stages (e.g. Casting)
        $res = $this->withSession($session)->get('process-waves/manage/' . $this->castingKey);
        $res->assertRedirect();

        // RESTRICTED: Raw Waves
        $res = $this->withSession($session)->get('raw-waves');
        $res->assertRedirect();

        // RESTRICTED: Locker & Locker Stock Verification
        $res = $this->withSession($session)->get('locker');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('locker-stock-verification');
        $res->assertRedirect();

        // RESTRICTED: Loss & Refining
        $res = $this->withSession($session)->get('loss');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('refining');
        $res->assertRedirect();

        // RESTRICTED: Masters & Reports
        $res = $this->withSession($session)->get('master/user');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('report/daily_report');
        $res->assertRedirect();
    }

    /**
     * 6. Role 5: Locker (ug - locker).
     * Dashboard, Locker ledger, Locker Stock Verification, Process Transfer.
     * MUST NOT access general process waves, raw waves, loss, refining, reports, masters.
     */
    public function testLockerRoleAccessAndRestrictions(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 2,
                'user'         => 'locker',
                'access_level' => 'locker',
                'location'     => 'ug',
                'private_key'  => '7cedbbec7b110ef9ae30ed9760ecf8bb',
            ],
        ];

        // Permitted
        $res = $this->withSession($session)->get('dashboard');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('locker');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('locker-stock-verification');
        $res->assertStatus(200);

        // RESTRICTED: General workshop stages (redirects to /locker)
        $res = $this->withSession($session)->get('process-waves/manage/' . $this->castingKey);
        $res->assertRedirect();

        // RESTRICTED: Raw Waves
        $res = $this->withSession($session)->get('raw-waves');
        $res->assertRedirect();

        // RESTRICTED: Loss & Refining
        $res = $this->withSession($session)->get('loss');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('refining');
        $res->assertRedirect();

        // RESTRICTED: Masters & Reports
        $res = $this->withSession($session)->get('master/customer');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('report/stock-report');
        $res->assertRedirect();
    }

    /**
     * 7. Role 6: G58 (ug - G58).
     * Dashboard, G58 Polish, G58 Filing, G58 Setting, G58 Transfer, G58 Loss.
     * MUST NOT access main manufacturing stages, locker, refining, reports, masters.
     */
    public function testG58RoleAccessAndRestrictions(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 8,
                'user'         => 'G58',
                'access_level' => 'g58',
                'location'     => 'ug',
                'private_key'  => '6f3h46r97b110ef9ae30ed9760ecf8bb',
            ],
        ];

        // Permitted
        $res = $this->withSession($session)->get('dashboard');
        $res->assertStatus(200);
        $res->assertSee('Dashboard');
        // Ensure G58 sees assigned dashboard cards
        $res->assertSee('G58 Polish');
        $res->assertSee('G58 Setting');
        $res->assertSee('G58 Filing');
        $res->assertSee('G58 Transfer');
        // Ensure G58 sidebar navbar is strictly limited to Dashboard only (no Operations, Process, Loss, Masters, Reports)
        $res->assertDontSee('Operations');
        $res->assertDontSee('id="menuProcess"');
        $res->assertDontSee('id="menuLoss"');
        $res->assertDontSee('id="menuMasters"');
        $res->assertDontSee('id="menuReports"');
        $res = $this->withSession($session)->get('process-waves/manage/' . $this->g58PolishKey);
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('process-waves/manage/' . $this->g58FilingKey);
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('process-waves/manage/' . $this->g58SettingKey);
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('process-waves/manage/' . $this->g58TransferKey);
        $res->assertStatus(200);
        // Permitted: G58 Loss Hub and G58 Polish Loss
        $res = $this->withSession($session)->get('loss');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('loss/manage/' . $this->g58PolishKey);
        $res->assertStatus(200);

        // RESTRICTED: Main manufacturing stages
        $res = $this->withSession($session)->get('process-waves/manage/' . $this->castingKey);
        $res->assertRedirect();

        // RESTRICTED: Raw Waves
        $res = $this->withSession($session)->get('raw-waves');
        $res->assertRedirect();

        // RESTRICTED: Locker & Refining
        $res = $this->withSession($session)->get('locker');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('refining');
        $res->assertRedirect();

        // RESTRICTED: Main Dept Loss (e.g. Casting Loss) & General Loss
        $res = $this->withSession($session)->get('loss/manage/' . $this->castingKey);
        $res->assertRedirect();
        $res = $this->withSession($session)->get('loss/manage/' . AccountModel::LOSS_PRIVATE_KEY);
        $res->assertRedirect();

        // RESTRICTED: Masters & Reports
        $res = $this->withSession($session)->get('master/user');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('report/daily_report');
        $res->assertRedirect();
    }

    /**
     * 8. Role 7: User / Floor Agent (ug - VICKY).
     * Raw Waves counter entry & incoming pending verification workflow.
     * MUST NOT access Dashboard, Process Waves, Stock, Master, Reports, Locker, Loss, Refining.
     */
    public function testFloorUserRoleAccessAndRestrictions(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 3,
                'user'         => 'VICKY',
                'access_level' => 'user',
                'location'     => 'ug',
                'private_key'  => '783a19037b110ef9ae30ed9760ecf8bb',
            ],
        ];

        // Permitted: Raw Waves
        $res = $this->withSession($session)->get('raw-waves');
        $res->assertStatus(200);
        $res = $this->withSession($session)->get('raw-waves/manage');
        $res->assertStatus(200);

        // RESTRICTED: Dashboard (redirects to /raw-waves)
        $res = $this->withSession($session)->get('dashboard');
        $res->assertRedirect();

        // RESTRICTED: Process Waves
        $res = $this->withSession($session)->get('process-waves/manage/' . $this->castingKey);
        $res->assertRedirect();

        // RESTRICTED: Locker & Locker Stock Verification
        $res = $this->withSession($session)->get('locker');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('locker-stock-verification');
        $res->assertRedirect();

        // RESTRICTED: Loss & Refining
        $res = $this->withSession($session)->get('loss');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('refining');
        $res->assertRedirect();

        // RESTRICTED: Masters & Reports
        $res = $this->withSession($session)->get('master/user');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('report/daily_report');
        $res->assertRedirect();
    }

    /**
     * 9. Role 8: Customer (cust).
     * Customer portal: own customer statement ledger & statement approval.
     * MUST NOT access Dashboard, Masters, Process Waves, Locker, Loss, Refining, Reports, other customers.
     */
    public function testCustomerRoleAccessAndRestrictions(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 101,
                'user'         => 'ABDUL',
                'access_level' => 'cust',
                'location'     => '',
                'private_key'  => $this->custKey,
            ],
        ];

        // Permitted: Own customer statement
        $res = $this->withSession($session)->get('report/customer_ledger_for_customer/' . $this->custKey);
        $res->assertStatus(200);

        // RESTRICTED: Other customer statement (redirects to own statement)
        $res = $this->withSession($session)->get('report/customer_ledger_for_customer/' . $this->otherCustKey);
        $res->assertRedirect();

        // RESTRICTED: Admin customer ledger route
        $res = $this->withSession($session)->get('report/customer_ledger/' . $this->custKey);
        $res->assertRedirect();

        // RESTRICTED: Dashboard
        $res = $this->withSession($session)->get('dashboard');
        $res->assertRedirect();

        // RESTRICTED: Raw Waves
        $res = $this->withSession($session)->get('raw-waves');
        $res->assertRedirect();

        // RESTRICTED: Process Waves
        $res = $this->withSession($session)->get('process-waves/manage/' . $this->castingKey);
        $res->assertRedirect();

        // RESTRICTED: Locker, Loss, Refining, Masters, Internal Reports
        $res = $this->withSession($session)->get('locker');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('loss');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('refining');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('master/user');
        $res->assertRedirect();
        $res = $this->withSession($session)->get('report/daily_report');
        $res->assertRedirect();
    }

    /**
     * 10. Dispatch Module Exclusion Verification.
     * The Dispatch module is intentionally excluded from the current CI4 migration scope.
     * Verifies dispatch routes are not exposed as active endpoints.
     */
    public function testDispatchModuleExclusion(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 1,
                'user'         => 'paresh',
                'access_level' => 'admin',
                'location'     => 'ho',
                'private_key'  => '9cedbbec7b110ef9ae30ed9760ecf8bb',
            ],
        ];

        $routesToTest = ['dispatch', 'dispatch/entry', 'dispatch/verify'];

        foreach ($routesToTest as $r) {
            try {
                $res = $this->withSession($session)->get($r);
                $this->assertTrue(in_array($res->response()->getStatusCode(), [404, 302], true));
            } catch (PageNotFoundException $e) {
                // Expected: Route does not exist in scope
                $this->assertTrue(true);
            }
        }
    }
}
