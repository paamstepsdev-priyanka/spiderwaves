<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\UserModel;

final class UserManagementPasswordAndLocationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $DBGroup = 'default';
    protected UserModel $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userModel = new UserModel();
    }

    /**
     * Test 1: Locations must only return clean 'HO' and 'UG', without 'Andheri' or 'Mumbai'.
     */
    public function testAvailableLocationsDoNotContainAndheriOrMumbai(): void
    {
        $locations = $this->userModel->getAvailableLocations();

        $this->assertArrayHasKey('ho', $locations);
        $this->assertArrayHasKey('ug', $locations);
        $this->assertSame('HO', $locations['ho']);
        $this->assertSame('UG', $locations['ug']);

        foreach ($locations as $val) {
            $this->assertStringNotContainsStringIgnoringCase('Andheri', $val);
            $this->assertStringNotContainsStringIgnoringCase('Mumbai', $val);
        }
    }

    /**
     * Test 2: Passwords must be verified and stored in plain text (no bcrypt hashing).
     */
    public function testPlainTextPasswordStorageAndVerification(): void
    {
        // 1. Verify admin plain text authentication
        $admin = $this->userModel->verifyCredentials('admin', 'TARGET100');
        $this->assertIsArray($admin);
        $this->assertSame('admin', $admin['username']);

        // Check password in database is NOT a bcrypt hash
        $dbUser = $this->userModel->findByUsername('admin');
        $this->assertIsArray($dbUser);
        $this->assertStringStartsNotWith('$2y$', $dbUser['password']);
        $this->assertStringStartsNotWith('$2a$', $dbUser['password']);

        // 2. Verify plain text user authentication for standard users
        $govind = $this->userModel->verifyCredentials('govind', '255@jiggi');
        $this->assertIsArray($govind);
        $this->assertSame('255@jiggi', $govind['password']);

        // 3. Test insert and update with plain text password
        $testUsername = 'test_plain_usr_' . mt_rand(1000, 9999);
        $plainPassword = 'PlainPassword!@#123';

        // Set admin session for permission
        $session = [
            'spider' => [
                'user_id'      => 8,
                'user'         => 'admin',
                'username'     => 'admin',
                'access_level' => 'admin',
                'location'     => 'ho',
                'status'       => '1',
                'logged_in'    => true,
            ],
        ];

        // Insert new user via master/user/store
        $insertResponse = $this->withSession($session)->post('master/user/store', [
            'form_type'         => 'Add',
            'user_full_name'    => $testUsername,
            'user_password'     => $plainPassword,
            'user_access_level' => 'user',
            'user_type'         => 'ho',
            'user_status'       => '1',
        ]);

        $insertResponse->assertOK();
        $this->assertStringContainsString('true', $insertResponse->getBody());

        $createdUser = $this->userModel->findByUsername($testUsername);
        $this->assertNotNull($createdUser);
        // Password must be strictly plain text, exactly what was provided
        $this->assertSame($plainPassword, $createdUser['password']);
        $this->assertStringStartsNotWith('$2y$', $createdUser['password']);

        // Verify credentials with newly created user
        $verified = $this->userModel->verifyCredentials($testUsername, $plainPassword);
        $this->assertIsArray($verified);
        $this->assertSame($plainPassword, $verified['password']);

        // Update user with a new plain text password
        $updatedPassword = 'NewPlainTextPassword$456';
        $updateResponse = $this->withSession($session)->post('master/user/update/' . $createdUser['private_key'], [
            'form_type'         => 'Edit',
            'private_key'       => $createdUser['private_key'],
            'user_full_name'    => $testUsername,
            'user_password'     => $updatedPassword,
            'user_access_level' => 'user',
            'user_type'         => 'ug',
            'user_status'       => '1',
        ]);

        $updateResponse->assertOK();
        $this->assertStringContainsString('true', $updateResponse->getBody());

        $refreshedUser = $this->userModel->find($createdUser['user_id']);
        $this->assertSame($updatedPassword, $refreshedUser['password']);
        $this->assertStringStartsNotWith('$2y$', $refreshedUser['password']);

        // Cleanup
        $this->userModel->delete($createdUser['user_id'], true);
    }

    /**
     * Test 3: Edit User form must have Show/Hide password toggle option and plain text password value.
     */
    public function testShowHidePasswordOptionInEditForm(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 8,
                'user'         => 'admin',
                'username'     => 'admin',
                'access_level' => 'admin',
                'location'     => 'ho',
                'status'       => '1',
                'logged_in'    => true,
            ],
        ];

        $admin = $this->userModel->findByUsername('admin');
        $this->assertNotNull($admin);

        $response = $this->withSession($session)->get('master/user/edit/' . $admin['private_key']);
        $response->assertOK();

        $body = $response->getBody();

        // Check for Show/Hide Password controls and icons
        $this->assertStringContainsString('togglePasswordBtn', $body);
        $this->assertStringContainsString('icon-eye', $body);
        $this->assertStringContainsString('id="user_password"', $body);

        // Check that plain text password is value in input
        $this->assertStringContainsString('value="' . esc($admin['password']) . '"', $body);

        // Check that Location dropdown only contains HO and UG, not Andheri or Mumbai
        $this->assertMatchesRegularExpression('/<option[^>]*value="ho"[^>]*>\s*HO\s*<\/option>/i', $body);
        $this->assertMatchesRegularExpression('/<option[^>]*value="ug"[^>]*>\s*UG\s*<\/option>/i', $body);
        $this->assertStringNotContainsString('HO (Andheri)', $body);
        $this->assertStringNotContainsString('UG (Mumbai)', $body);
    }

    /**
     * Test 4: User list page must render HO and UG without 'Andheri' or 'Mumbai'.
     */
    public function testUserListDoesNotRenderAndheriOrMumbai(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 8,
                'user'         => 'admin',
                'username'     => 'admin',
                'access_level' => 'admin',
                'location'     => 'ho',
                'status'       => '1',
                'logged_in'    => true,
            ],
        ];

        $response = $this->withSession($session)->get('master/user');
        $response->assertOK();

        $body = $response->getBody();

        // Must not contain "HO (Andheri)" or "UG (Mumbai)"
        $this->assertStringNotContainsString('HO (Andheri)', $body);
        $this->assertStringNotContainsString('UG (Mumbai)', $body);

        // Must not contain circular initial avatars in username table cells
        $this->assertStringNotContainsString('width: 32px; height: 32px; border-radius: 50%;', $body);
    }

    /**
     * Test 5: Inactive pill badges must be styled red in theme CSS.
     */
    public function testInactivePillBadgesAreStyledRed(): void
    {
        $cssContent = file_get_contents(FCPATH . 'assets/css/modern_theme.css');
        $this->assertNotFalse($cssContent);

        // Verify .sw-pill-badge.inactive is mapped to danger status variables
        $this->assertMatchesRegularExpression('/\.sw-pill-badge\.inactive[^{]*\{[^}]*--sw-danger-bg/s', $cssContent);
        $this->assertMatchesRegularExpression('/\.sw-pill-badge\.inactive[^{]*\{[^}]*--sw-danger-text/s', $cssContent);
        $this->assertMatchesRegularExpression('/\.sw-pill-badge\.inactive[^{]*\{[^}]*--sw-danger-border/s', $cssContent);
    }

    /**
     * Test 6: Master Remarks page must not render ID # (like ID #2) under remarks.
     */
    public function testRemarkListDoesNotContainIdLabels(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 8,
                'user'         => 'admin',
                'username'     => 'admin',
                'access_level' => 'admin',
                'location'     => 'ho',
                'status'       => '1',
                'logged_in'    => true,
            ],
        ];

        $response = $this->withSession($session)->get('master/remark');
        $response->assertOK();

        $body = $response->getBody();

        // Must not contain "ID #2" or "ID #"
        $this->assertStringNotContainsString('ID #2', $body);
        $this->assertStringNotContainsString('ID #', $body);
    }

    /**
     * Test 7: Filter input fields must not contain search icons or close/plus icons.
     */
    public function testFilterInputsDoNotContainIcons(): void
    {
        $cssContent = file_get_contents(FCPATH . 'assets/css/modern_theme.css');
        $this->assertNotFalse($cssContent);

        // Search icon hidden
        $this->assertMatchesRegularExpression('/\.sw-search-icon\s*\{[^}]*display:\s*none\s*!important/s', $cssContent);

        // Select2 close/plus icon hidden
        $this->assertMatchesRegularExpression('/\.select2-container\s+\.select2-choice\s+abbr\.select2-search-choice-close\s*\{[^}]*display:\s*none\s*!important/s', $cssContent);
    }

    /**
     * Test 8: Customer Master page must not render Mobile column.
     */
    public function testCustomerListDoesNotContainMobileColumn(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 8,
                'user'         => 'admin',
                'username'     => 'admin',
                'access_level' => 'admin',
                'location'     => 'ho',
                'status'       => '1',
                'logged_in'    => true,
            ],
        ];

        $response = $this->withSession($session)->get('master/customer');
        $response->assertOK();

        $body = $response->getBody();

        // Must not contain Mobile column header or mobile number cell icon
        $this->assertStringNotContainsString('>Mobile</th>', $body);
        $this->assertStringNotContainsString('icon-phone2', $body);
    }

    /**
     * Test 9: Raw Waves queue column visibility for superadmin and admin.
     * For superadmin: Only S.No., Cust Name, Delivery By, Type, Date, Status are displayed (no Action column).
     * For admin: Action column is displayed.
     */
    public function testRawWavesQueueColumnsForSuperadminAndAdmin(): void
    {
        // 1. Superadmin role
        $superadminSession = [
            'spider' => [
                'user_id'      => 1,
                'user'         => 'superadmin',
                'username'     => 'superadmin',
                'access_level' => 'superadmin',
                'location'     => 'ho',
                'status'       => '1',
                'logged_in'    => true,
            ],
        ];

        $superadminResponse = $this->withSession($superadminSession)->get('raw-waves/manage');
        $superadminResponse->assertOK();

        $superadminBody = $superadminResponse->getBody();
        $this->assertStringContainsString('S.No.', $superadminBody);
        $this->assertStringContainsString('Cust Name', $superadminBody);
        $this->assertStringContainsString('Delivery By', $superadminBody);
        $this->assertStringContainsString('Type', $superadminBody);
        $this->assertStringContainsString('Date', $superadminBody);
        $this->assertStringContainsString('Status', $superadminBody);
        $this->assertStringNotContainsString('>Action</th>', $superadminBody);

        // 2. Admin role
        $adminSession = [
            'spider' => [
                'user_id'      => 8,
                'user'         => 'admin',
                'username'     => 'admin',
                'access_level' => 'admin',
                'location'     => 'ho',
                'status'       => '1',
                'logged_in'    => true,
            ],
        ];

        $adminResponse = $this->withSession($adminSession)->get('raw-waves/manage');
        $adminResponse->assertOK();

        $adminBody = $adminResponse->getBody();
        $this->assertStringContainsString('S.No.', $adminBody);
        $this->assertStringContainsString('Cust Name', $adminBody);
        $this->assertStringContainsString('Delivery By', $adminBody);
        $this->assertStringContainsString('Type', $adminBody);
        $this->assertStringContainsString('Date', $adminBody);
        $this->assertStringContainsString('Status', $adminBody);
        $this->assertStringContainsString('>Action</th>', $adminBody);
    }

    /**
     * Test 10: All Create buttons across pages must match the + NEW button format.
     */
    public function testCreateButtonsConsistentAcrossAllPages(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 8,
                'user'         => 'admin',
                'username'     => 'admin',
                'access_level' => 'admin',
                'location'     => 'ho',
                'status'       => '1',
                'logged_in'    => true,
            ],
        ];

        $routesToTest = [
            'master/customer',
            'master/user',
            'master/collection',
            'master/remark',
            'master/customer-mapping',
            'raw-waves/manage',
            'waves',
        ];

        foreach ($routesToTest as $route) {
            $response = $this->withSession($session)->get($route);
            $response->assertOK();
            $body = $response->getBody();

            $this->assertStringContainsString('icon-plus2', $body, "Route {$route} must contain icon-plus2 for the Create button.");
            $this->assertStringContainsString('NEW', $body, "Route {$route} must contain NEW label for the Create button.");
            $this->assertStringContainsString('sw-btn-primary', $body, "Route {$route} must use sw-btn-primary class.");
        }
    }

    /**
     * Test 11: Stock menu is in Operations section and labeled "Stock" (not "Stock Report").
     */
    public function testStockMenuLocationAndLabelInOperations(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 1,
                'user'         => 'superadmin',
                'username'     => 'superadmin',
                'access_level' => 'superadmin',
                'location'     => 'ho',
                'status'       => '1',
                'logged_in'    => true,
            ],
        ];

        $response = $this->withSession($session)->get('dashboard');
        $response->assertOK();

        $body = $response->getBody();

        // 1. Must contain "Stock" link under operations with icon-box
        $this->assertStringContainsString('<span>Stock</span>', $body);
        $this->assertStringContainsString('icon-box', $body);

        // 2. Must NOT contain "Stock Report" in navigation
        $this->assertStringNotContainsString('<span>Stock Report</span>', $body);

        // 3. Test /stock route directly
        $stockResponse = $this->withSession($session)->get('stock');
        $stockResponse->assertOK();

        $stockBody = $stockResponse->getBody();
        $this->assertStringContainsString('<h1>Stock</h1>', $stockBody);
    }

    /**
     * Test 12: In Recent Files, the Action columns must be removed from both Recent Cust Ledger and Recent Daily Report.
     */
    public function testRecentFilesActionColumnRemovedFromBoth(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 8,
                'user'         => 'admin',
                'username'     => 'admin',
                'access_level' => 'admin',
                'location'     => 'ho',
                'status'       => '1',
                'logged_in'    => true,
            ],
        ];

        // 1. Recent Cust Ledger
        $custResponse = $this->withSession($session)->get('master/manage_recent_cust_ledger_file');
        $custResponse->assertOK();

        $custBody = $custResponse->getBody();
        $this->assertStringContainsString('S.No.', $custBody);
        $this->assertStringContainsString('Cust Name', $custBody);
        $this->assertStringNotContainsString('Action', $custBody);

        // 2. Recent Daily Report
        $dailyResponse = $this->withSession($session)->get('master/manage_recent_daily_report_file');
        $dailyResponse->assertOK();

        $dailyBody = $dailyResponse->getBody();
        $this->assertStringContainsString('S.No.', $dailyBody);
        $this->assertStringContainsString('File Name', $dailyBody);
        $this->assertStringNotContainsString('Action', $dailyBody);
    }

    /**
     * Test 13: Process Waves has the green Print button below and properly aligned Fine MT totals under Weight.
     */
    public function testProcessWavesPrintButtonAndFineMtAlignment(): void
    {
        $accountModel = model(\App\Models\AccountModel::class);
        $casting = $accountModel->getAccountById(\App\Models\AccountModel::CASTING_ID);
        $this->assertNotEmpty($casting);
        $castingKey = $casting['private_key'];

        $session = [
            'spider' => [
                'user_id'      => 8,
                'user'         => 'admin',
                'username'     => 'admin',
                'access_level' => 'admin',
                'location'     => 'ho',
                'status'       => '1',
                'logged_in'    => true,
            ],
        ];

        $response = $this->withSession($session)->get('process-waves/manage/' . $castingKey);
        $response->assertOK();

        $body = $response->getBody();

        // 1. Verify Blue Print button below tables
        $this->assertStringContainsString('process_waves/print_process_waves/' . $castingKey, $body);
        $this->assertStringContainsString('btn btn-primary btn-xs', $body);
        $this->assertStringContainsString('Print', $body);

        // 2. Verify Receipt table Fine MT alignment adjacent to label (no empty spacer cell)
        $this->assertDoesNotMatchRegularExpression(
            '/<td[^>]*>Fine MT<\/td>\s*<td>&nbsp;<\/td>/i',
            $body,
            'Receipt table Fine MT must not have an empty spacer cell pushing it to column 3'
        );
        $this->assertMatchesRegularExpression(
            '/<td[^>]*>Fine MT<\/td>\s*<td colspan="5"[^>]*text-align:\s*left;/i',
            $body,
            'Receipt table Fine MT numeric balance must be positioned directly adjacent to label with left alignment'
        );

        // 3. Verify Issue table Fine MT alignment adjacent to label
        $this->assertMatchesRegularExpression(
            '/<td[^>]*>Fine MT<\/td>\s*<td colspan="5"[^>]*text-align:\s*left;/i',
            $body,
            'Issue table Fine MT numeric total must be positioned directly adjacent to label with left alignment'
        );

        // 4. Verify Issue table Fine MT Balance alignment adjacent to label
        $this->assertMatchesRegularExpression(
            '/<td[^>]*>Fine MT Balance<\/td>\s*<td colspan="5"[^>]*text-align:\s*left;[^"]*" id="get_gross_weight"[^>]*>/i',
            $body,
            'Issue table Fine MT Balance numeric value must be positioned directly adjacent to label with left alignment'
        );
    }
}
