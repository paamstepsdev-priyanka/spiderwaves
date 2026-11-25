<?php

namespace Tests\Unit;

use App\Models\AccountModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * AccountDepartmentMappingTest
 *
 * Verifies complete frontend-based Account and Department Mapping Management:
 * 1. Superadmin-only access control (Admin and other roles blocked).
 * 2. Create and Edit forms render Department Mapping multi-select with checkboxes.
 * 3. Saving and updating properly persists '#' delimited IDs in map_with_accounts_id.
 * 4. Pre-selection of existing mappings on edit.
 * 5. Dynamic reflection in transfer modals and getMappedDestinationAccounts.
 *
 * @internal
 */
final class AccountDepartmentMappingTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected AccountModel $accountModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountModel = model(AccountModel::class);
    }

    protected function getSuperadminSession(): array
    {
        return [
            'spider' => [
                'user_id'      => 14,
                'user'         => 'superadmin',
                'access_level' => 'superadmin',
                'location'     => 'ug',
                'private_key'  => 'superadmin_test_key',
                'logged_in'    => true,
            ],
        ];
    }

    protected function getAdminSession(): array
    {
        return [
            'spider' => [
                'user_id'      => 1,
                'user'         => 'admin',
                'access_level' => 'admin',
                'location'     => 'ho',
                'private_key'  => 'admin_test_key',
                'logged_in'    => true,
            ],
        ];
    }

    protected function getProcessManagerSession(): array
    {
        return [
            'spider' => [
                'user_id'      => 5,
                'user'         => 'pm_user',
                'access_level' => 'process_manager',
                'location'     => 'ho',
                'private_key'  => 'pm_test_key',
                'logged_in'    => true,
            ],
        ];
    }

    /**
     * 0. Helper methods properly handle JSON format and legacy "#" delimited format.
     */
    public function testDepartmentMappingJsonHelpers(): void
    {
        // JSON parsing
        $this->assertSame([2, 4, 3], $this->accountModel->parseMappedAccountIds('[2,4,3]'));
        $this->assertSame([2, 4, 3], $this->accountModel->parseMappedAccountIds('["2","4","3"]'));
        $this->assertSame([], $this->accountModel->parseMappedAccountIds('[]'));
        $this->assertSame([], $this->accountModel->parseMappedAccountIds(null));
        $this->assertSame([], $this->accountModel->parseMappedAccountIds(''));

        // Legacy "#" parsing
        $this->assertSame([2, 4, 3], $this->accountModel->parseMappedAccountIds('2#4#3'));
        $this->assertSame([18], $this->accountModel->parseMappedAccountIds('18'));

        // JSON formatting
        $this->assertSame('[2,4,3]', $this->accountModel->formatMappedAccountIds([2, 4, 3]));
        $this->assertSame('[2,4,3]', $this->accountModel->formatMappedAccountIds(['2', '4', '3', '2']));
        $this->assertNull($this->accountModel->formatMappedAccountIds([]));
    }

    /**
     * 1. Superadmin can access Account Master / Account index.
     */
    public function testSuperadminCanAccessAccountMaster(): void
    {
        $response = $this->withSession($this->getSuperadminSession())->get('account');
        $response->assertOK();
        $response->assertSee('Account');
        $response->assertSee('Registered Accounts');
        $response->assertSee('Locker');
        $response->assertSee('Casting');

        // Also works via master/account
        $legacyResponse = $this->withSession($this->getSuperadminSession())->get('master/account');
        $legacyResponse->assertOK();
        $legacyResponse->assertSee('Account');
    }

    /**
     * 2. Admin and other roles are strictly blocked from Account Master.
     */
    public function testAdminAndOtherRolesForbiddenFromAccountMaster(): void
    {
        // Admin blocked
        $adminRes = $this->withSession($this->getAdminSession())->get('master/account');
        $this->assertTrue(
            $adminRes->isRedirect() || $adminRes->response()->getStatusCode() === 403,
            'Admin must be redirected or receive 403 when trying to access Account Master.'
        );

        // Process Manager blocked
        $pmRes = $this->withSession($this->getProcessManagerSession())->get('master/account');
        $this->assertTrue(
            $pmRes->isRedirect() || $pmRes->response()->getStatusCode() === 403,
            'Process Manager must be redirected or receive 403 when trying to access Account Master.'
        );

        // Anonymous blocked
        $anonRes = $this->get('master/account');
        $this->assertTrue(
            $anonRes->isRedirect() || $anonRes->response()->getStatusCode() === 403,
            'Unauthenticated user must be redirected.'
        );
    }

    /**
     * 3. Superadmin can view Create Account form with Department Mapping multi-select.
     */
    public function testSuperadminCanViewCreateAccountForm(): void
    {
        $response = $this->withSession($this->getSuperadminSession())->get('master/account/create');
        $response->assertOK();
        $response->assertSee('Create New Account');
        $response->assertSee('Department Mapping');
        $response->assertSee('map_with_accounts[]');
        $response->assertSee('multiselect-select-all-filtering');
    }

    /**
     * 4. Superadmin can create a new account with selected department mappings.
     */
    public function testSuperadminCanCreateAccountWithDepartmentMappings(): void
    {
        $testAccountName = 'Test_Dept_' . uniqid();
        $postData = [
            'account_name'       => $testAccountName,
            'account_type'       => 'process',
            'loss_percentage'    => '2.500',
            'account_status'     => '1',
            'map_with_accounts'  => ['1', '2', '4'], // Locker (1), Casting (2), Filing (4)
        ];

        $response = $this->withSession($this->getSuperadminSession())->post('master/account/store', $postData);
        $response->assertRedirect();

        $created = $this->accountModel->where('account_name', $testAccountName)->first();
        $this->assertNotNull($created, 'New account must be created in the database.');

        try {
            // Verify mapped IDs formatted correctly as JSON
            $this->assertSame('[1,2,4]', $created['map_with_accounts_id']);
            $this->assertSame('process', $created['account_type']);
            $this->assertSame('1', $created['account_status']);
            $this->assertEquals(2.5, (float)$created['loss_percentage']);
        } finally {
            $this->accountModel->where('account_name', $testAccountName)->delete();
        }
    }

    /**
     * 5. Superadmin can view Edit Account form with pre-selected department mappings.
     */
    public function testSuperadminCanEditAccountAndSeePreselectedMappings(): void
    {
        // Account ID 1 is Locker, which has mappings: 2#4#3#6#...
        $locker = $this->accountModel->getAccountById(AccountModel::STOCK_ID);
        $this->assertNotNull($locker);

        $response = $this->withSession($this->getSuperadminSession())->get('master/account/edit/' . $locker['private_key']);
        $response->assertOK();
        $response->assertSee('Update Account');
        $response->assertSee('Department Mapping');

        $body = $response->getBody();
        $this->assertMatchesRegularExpression('/<option[^>]*value="2"[^>]*selected/i', $body, 'Casting (ID 2) must be preselected for Locker.');
        $this->assertMatchesRegularExpression('/<option[^>]*value="4"[^>]*selected/i', $body, 'Filing (ID 4) must be preselected for Locker.');
        $response->assertSee('Casting (Process)');
        $response->assertSee('Filing (Process)');
    }

    /**
     * 6. Superadmin can update department mappings dynamically without manual phpMyAdmin changes.
     */
    public function testSuperadminCanUpdateDepartmentMappingsDynamically(): void
    {
        $testAccountName = 'Test_Dept_Dynamic_' . uniqid();
        $testKey = bin2hex(random_bytes(16));

        $this->accountModel->insert([
            'private_key'          => $testKey,
            'account_name'         => $testAccountName,
            'account_type'         => 'process',
            'loss_percentage'      => 1.0,
            'account_status'       => '1',
            'map_with_accounts_id' => '1#2', // Initially Locker and Casting
        ]);

        try {
            // Update mappings to Heera Light (3), Setting (5), Polish (6)
            $updateData = [
                'account_name'      => $testAccountName,
                'account_type'      => 'process',
                'loss_percentage'   => 1.5,
                'account_status'    => '1',
                'map_with_accounts' => ['3', '5', '6'],
            ];

            $response = $this->withSession($this->getSuperadminSession())->post('master/account/update/' . $testKey, $updateData);
            $response->assertRedirect();

            $updated = $this->accountModel->where('private_key', $testKey)->first();
            $this->assertNotNull($updated);
            $this->assertSame('[3,5,6]', $updated['map_with_accounts_id']);

            // Verify getMappedDestinationAccounts reflects changes dynamically
            $destinations = $this->accountModel->getMappedDestinationAccounts($updated['map_with_accounts_id']);
            $this->assertCount(3, $destinations);
            $destNames = array_column($destinations, 'account_name');
            $this->assertContains('Heera Light', $destNames);
            $this->assertContains('Setting', $destNames);
            $this->assertContains('Polish', $destNames);
            $this->assertNotContains('Casting', $destNames);
        } finally {
            $this->accountModel->where('private_key', $testKey)->delete();
        }
    }

    /**
     * 7. Dedicated Route Mapping page and update-mapping endpoint work properly.
     */
    public function testDedicatedRouteMappingPageAndUpdateEndpoint(): void
    {
        $testAccountName = 'Test_Dept_Route_' . uniqid();
        $testKey = bin2hex(random_bytes(16));

        $this->accountModel->insert([
            'private_key'          => $testKey,
            'account_name'         => $testAccountName,
            'account_type'         => 'process',
            'loss_percentage'      => 0.0,
            'account_status'       => '1',
            'map_with_accounts_id' => '2',
        ]);

        try {
            // GET mapping page
            $getRes = $this->withSession($this->getSuperadminSession())->get('master/account/mapping/' . $testKey);
            $getRes->assertOK();
            $getRes->assertSee('Department Transfer Route Mapping');
            $getRes->assertSee($testAccountName);

            // POST to update-mapping
            $postRes = $this->withSession($this->getSuperadminSession())->post('master/account/update-mapping', [
                'private_key'       => $testKey,
                'map_with_accounts' => ['4', '7'], // Filing (4), Rodium (7)
            ]);
            $postRes->assertRedirect();

            $updated = $this->accountModel->where('private_key', $testKey)->first();
            $this->assertSame('[4,7]', $updated['map_with_accounts_id']);
        } finally {
            $this->accountModel->where('private_key', $testKey)->delete();
        }
    }

    /**
     * 8. Account status can be toggled.
     */
    public function testToggleAccountStatus(): void
    {
        $testAccountName = 'Test_Dept_Toggle_' . uniqid();
        $testKey = bin2hex(random_bytes(16));

        $this->accountModel->insert([
            'private_key'          => $testKey,
            'account_name'         => $testAccountName,
            'account_type'         => 'process',
            'loss_percentage'      => 0.0,
            'account_status'       => '1',
            'map_with_accounts_id' => null,
        ]);

        try {
            // Toggle to inactive (0)
            $res1 = $this->withSession($this->getSuperadminSession())->get('master/account/toggle/' . $testKey);
            $res1->assertRedirect();
            $acc1 = $this->accountModel->where('private_key', $testKey)->first();
            $this->assertSame('0', $acc1['account_status']);

            // Toggle back to active (1)
            $res2 = $this->withSession($this->getSuperadminSession())->get('master/account/toggle/' . $testKey);
            $res2->assertRedirect();
            $acc2 = $this->accountModel->where('private_key', $testKey)->first();
            $this->assertSame('1', $acc2['account_status']);
        } finally {
            $this->accountModel->where('private_key', $testKey)->delete();
        }
    }

    /**
     * 9. Process Waves Transfer Modal dynamically reflects newly configured department mappings.
     */
    public function testProcessWavesTransferModalDynamicallyReflectsDepartmentMappings(): void
    {
        $testAccountName = 'Test_Dept_Modal_' . uniqid();
        $testKey = bin2hex(random_bytes(16));

        // Create temporary test account mapped to only Rodium (7) and PO_Check (19)
        $id = $this->accountModel->insert([
            'private_key'          => $testKey,
            'account_name'         => $testAccountName,
            'account_type'         => 'process',
            'loss_percentage'      => 0.0,
            'account_status'       => '1',
            'map_with_accounts_id' => '7#19',
        ]);

        try {
            // Open transfer modal with fromAccountId = $id
            $modalRes = $this->withSession($this->getSuperadminSession())->get("process-waves/transfer/{$id}");
            $modalRes->assertOK();

            $body = $modalRes->getBody();
            // Should see Rodium and PO_Check as options
            $this->assertStringContainsString('Rodium', $body);
            $this->assertStringContainsString('PO_Check', $body);

            // Should NOT see Casting or Setting in destination options
            $this->assertStringNotContainsString('value="2" data-name="Casting"', $body);
            $this->assertStringNotContainsString('value="5" data-name="Setting"', $body);
        } finally {
            $this->accountModel->where('private_key', $testKey)->delete();
        }
    }
}
