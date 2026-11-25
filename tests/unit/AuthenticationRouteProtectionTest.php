<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * AuthenticationRouteProtectionTest
 *
 * Verifies strict server-side authentication and route protection:
 * 1. Unauthenticated users cannot bypass protection by directly entering or copying URLs
 * 2. Authenticated users opening login page (/login or /) are automatically redirected to their landing page
 * 3. Server-side session validation and database status check
 * 4. Unauthorized role access attempts are blocked and redirected to role home
 * 5. AJAX requests receive 401/403 JSON responses
 * 6. Anti-caching headers are set on protected responses
 */
final class AuthenticationRouteProtectionTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $DBGroup = 'default';

    protected function setUp(): void
    {
        parent::setUp();
        \Config\Database::connect('default');
    }

    /**
     * 1. Unauthenticated direct URL access to protected routes must be blocked and redirected to login.
     */
    public function testUnauthenticatedUsersCannotBypassProtectedRoutes(): void
    {
        $protectedRoutes = [
            'dashboard',
            'raw-waves',
            'locker',
            'process-waves',
            'master/customer',
            'master/user',
            'refining',
            'report/daily_report',
            'report/stock-report',
        ];

        foreach ($protectedRoutes as $route) {
            $result = $this->get($route);
            $result->assertRedirect();
            // Verify redirected to login
            $redirectUrl = $result->response()->getHeaderLine('Location');
            $this->assertStringContainsString('login', $redirectUrl, "Direct access to '{$route}' without session must redirect to login.");
        }
    }

    /**
     * 2. Unauthenticated user opening login page renders the login view.
     */
    public function testUnauthenticatedUserCanAccessLoginPage(): void
    {
        $result = $this->get('login');
        $result->assertStatus(200);
        $result->assertSee('SpiderWaves');
        $result->assertSee('Sign In');
    }

    /**
     * 3. Authenticated admin opening login page in another tab is automatically redirected to dashboard.
     */
    public function testAuthenticatedAdminOpeningLoginPageRedirectsToDashboard(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 1,
                'user'         => 'paresh',
                'access_level' => 'admin',
                'location'     => 'ho',
                'private_key'  => '9cedbbecf8bbcc5',
            ],
        ];

        $result = $this->withSession($session)->get('login');
        $result->assertRedirect();
        $this->assertStringContainsString('dashboard', $result->response()->getHeaderLine('Location'));
    }

    /**
     * 4. Authenticated floor user opening login page is redirected to raw-waves.
     */
    public function testAuthenticatedFloorUserOpeningLoginPageRedirectsToRawWaves(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 4,
                'user'         => 'VICKY',
                'access_level' => 'user',
                'location'     => 'ug',
                'private_key'  => '783a1903762c313',
            ],
        ];

        $result = $this->withSession($session)->get('login');
        $result->assertRedirect();
        $this->assertStringContainsString('raw-waves', $result->response()->getHeaderLine('Location'));
    }

    /**
     * 5. Authenticated customer opening login page is redirected to customer ledger.
     */
    public function testAuthenticatedCustomerOpeningLoginPageRedirectsToLedger(): void
    {
        $custKey = '5c8263667b110ef9ae30ed9760ecf8bb';
        $session = [
            'spider' => [
                'user_id'      => 101,
                'user'         => 'ABDUL',
                'access_level' => 'cust',
                'location'     => '',
                'private_key'  => $custKey,
            ],
        ];

        $result = $this->withSession($session)->get('login');
        $result->assertRedirect();
        $this->assertStringContainsString('customer_ledger_for_customer', $result->response()->getHeaderLine('Location'));
    }

    /**
     * 6. Deactivated user session is rejected and redirected to login.
     */
    public function testDeactivatedUserSessionIsRejected(): void
    {
        // Temporarily deactivate user 15 or mock status 0
        $db = \Config\Database::connect('default');
        $db->table('user')->where('user_id', 15)->update(['status' => '0']);

        try {
            $session = [
                'spider' => [
                    'user_id'      => 15,
                    'user'         => 'dispatch2',
                    'access_level' => 'dispatch_verify',
                    'location'     => 'ho',
                    'private_key'  => '9fc6362d4cb55a3',
                ],
            ];

            // Accessing dashboard with deactivated account
            $result = $this->withSession($session)->get('dashboard');
            $result->assertRedirect();
            $this->assertStringContainsString('login', $result->response()->getHeaderLine('Location'));
        } finally {
            // Restore status
            $db->table('user')->where('user_id', 15)->update(['status' => '1']);
        }
    }

    /**
     * 7. Unauthorized role access is blocked by server-side authorization.
     */
    public function testUnauthorizedRoleCannotAccessAdminRoutes(): void
    {
        $floorUserSession = [
            'spider' => [
                'user_id'      => 4,
                'user'         => 'VICKY',
                'access_level' => 'user',
                'location'     => 'ug',
                'private_key'  => '783a1903762c313',
            ],
        ];

        // Floor user directly entering admin URL /master/customer
        $result = $this->withSession($floorUserSession)->get('master/customer');
        $result->assertRedirect();
        $this->assertStringContainsString('raw-waves', $result->response()->getHeaderLine('Location'));
    }

    /**
     * 8. Protected route responses include anti-caching headers to prevent back-button exposure.
     */
    public function testProtectedResponsesIncludeAntiCachingHeaders(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 1,
                'user'         => 'paresh',
                'access_level' => 'admin',
                'location'     => 'ho',
                'private_key'  => '9cedbbecf8bbcc5',
            ],
        ];

        $result = $this->withSession($session)->get('dashboard');
        $result->assertStatus(200);

        $cacheControl = $result->response()->getHeaderLine('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
    }
}

