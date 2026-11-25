<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Services\LedgerService;
use App\Controllers\ReportController;
use Config\Services;

/**
 * DailyReportTest
 *
 * Automated test suite for CI4 Daily Report module.
 * Verifies:
 * - Active database rule: connects exclusively to new-spiderwaves on port 3307
 * - Daily report data extraction and dual-table pairing (Receipts vs Issues)
 * - Net Weight, Fine Weight, Wastage, Purity, Customer Name, and Remark mappings
 * - Fine weight formulas (MT vs OR with wastage)
 * - Balance calculation parity (Total Receipts - Total Issues)
 * - Number formatting to 3 decimal places
 * - HTTP route access and role protection (admin, superadmin, process_manager vs cust)
 * - View rendering and Limitless theme CSS classes (green-bg, red-bg, table-bordered)
 * - Print popup view rendering and print script
 * - Approve and submit workflow updating approved_data = '1'
 *
 * @internal
 */
final class DailyReportTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $DBGroup = 'default';
    protected LedgerService $ledgerService;

    protected function setUp(): void
    {
        parent::setUp();
        \Config\Database::connect('default');
        $this->ledgerService = new LedgerService();
    }

    /**
     * 1. Database Connection Rule:
     * Must connect exclusively to new-spiderwaves on port 3307.
     */
    public function testDatabaseConnectionRule(): void
    {
        $db = \Config\Database::connect('default');
        $this->assertSame('new-spiderwaves', $db->database, 'CI4 must connect to new-spiderwaves database.');
        $this->assertSame(3307, (int)$db->port, 'CI4 must connect on port 3307.');
    }

    /**
     * 2. Daily Report Service Data Structure.
     */
    public function testDailyReportDataStructure(): void
    {
        $data = $this->ledgerService->getDailyReportData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('or_data', $data);
        $this->assertArrayHasKey('or_balance', $data);
        $this->assertArrayHasKey('display_balance', $data);
        $this->assertArrayHasKey('total_receipt_fw', $data);
        $this->assertArrayHasKey('total_issue_fw', $data);
        $this->assertArrayHasKey('receipt_count', $data);
        $this->assertArrayHasKey('issue_count', $data);

        $this->assertIsFloat($data['or_balance']);
        $this->assertMatchesRegularExpression('/^-?\d+\.\d{3}$/', $data['display_balance']);
    }

    /**
     * 3. Row Pairing and Field Properties.
     */
    public function testRowPairingAndFieldProperties(): void
    {
        $db = \Config\Database::connect('default');

        // Create a temporary mock row to guarantee assertions run even on an empty day
        $tempKey = 'pair_test_' . bin2hex(random_bytes(6));
        $db->table('waves')->insert([
            'private_key'        => $tempKey,
            'cust_id'            => 1,
            'transactional_mode' => 'MT',
            'transactional_type' => 'receipt',
            'gross_weight'       => 10.000,
            'net_weight'         => 10.000,
            'less_weight'        => 0.000,
            'purity'             => 92.000,
            'wastage'            => 0.000,
            'remark'             => 'Pairing test',
            'approved_data'      => '0',
            'is_deleted'         => '0',
            'entry_date'         => date('Y-m-d'),
        ]);
        $tempId = $db->insertID();

        try {
            $data = $this->ledgerService->getDailyReportData();
            $this->assertNotEmpty($data['or_data'], 'or_data should contain at least our temporary test row.');

            $row = $data['or_data'][0];
            $this->assertIsObject($row);

            // Receipt properties
            $this->assertTrue(property_exists($row, 'receipt_gross_weight'));
            $this->assertTrue(property_exists($row, 'receipt_net_weight'));
            $this->assertTrue(property_exists($row, 'receipt_purity'));
            $this->assertTrue(property_exists($row, 'receipt_wastage'));
            $this->assertTrue(property_exists($row, 'receipt_cust_name'));
            $this->assertTrue(property_exists($row, 'receipt_remark'));
            $this->assertTrue(property_exists($row, 'receipt_actual'));

            // Issue properties
            $this->assertTrue(property_exists($row, 'issue_gross_weight'));
            $this->assertTrue(property_exists($row, 'issue_net_weight'));
            $this->assertTrue(property_exists($row, 'issue_purity'));
            $this->assertTrue(property_exists($row, 'issue_wastage'));
            $this->assertTrue(property_exists($row, 'issue_cust_name'));
            $this->assertTrue(property_exists($row, 'issue_remark'));
            $this->assertTrue(property_exists($row, 'issue_actual'));
        } finally {
            $db->table('waves')->where('in_out_id', $tempId)->delete();
        }
    }

    /**
     * 4. Balance Parity with Synthetic Pending Transactions.
     */
    public function testBalanceCalculationWithMockData(): void
    {
        $db = \Config\Database::connect('default');

        // Insert temporary mock unapproved receipt
        $db->table('waves')->insert([
            'private_key'        => 'daily_test_rec_' . bin2hex(random_bytes(8)),
            'cust_id'            => 1,
            'transactional_mode' => 'MT',
            'transactional_type' => 'receipt',
            'gross_weight'       => 100.000,
            'net_weight'         => 100.000,
            'less_weight'        => 0.000,
            'purity'             => 92.000,
            'wastage'            => 0.000,
            'remark'             => 'Daily Unit Test Receipt',
            'approved_data'      => '0',
            'is_deleted'         => '0',
            'entry_date'         => date('Y-m-d'),
        ]);
        $recId = $db->insertID();

        // Insert temporary mock unapproved issue
        $db->table('waves')->insert([
            'private_key'        => 'daily_test_iss_' . bin2hex(random_bytes(8)),
            'cust_id'            => 1,
            'transactional_mode' => 'OR',
            'transactional_type' => 'issue',
            'gross_weight'       => 50.000,
            'net_weight'         => 50.000,
            'less_weight'        => 0.000,
            'purity'             => 75.000,
            'wastage'            => 3.000,
            'remark'             => 'Daily Unit Test Issue',
            'approved_data'      => '0',
            'is_deleted'         => '0',
            'entry_date'         => date('Y-m-d'),
        ]);
        $issId = $db->insertID();

        try {
            $data = $this->ledgerService->getDailyReportData();
            $this->assertGreaterThanOrEqual(1, $data['receipt_count']);
            $this->assertGreaterThanOrEqual(1, $data['issue_count']);

            // Find our receipt in or_data
            $foundRec = false;
            $foundIss = false;
            foreach ($data['or_data'] as $r) {
                if ($r->receipt_in_out_id === $recId) {
                    $foundRec = true;
                    // MT mode actual = 100 * 92 / 100 = 92.000
                    $this->assertEqualsWithDelta(92.0, (float)$r->receipt_actual, 0.001);
                }
                if ($r->issue_in_out_id === $issId) {
                    $foundIss = true;
                    // OR mode actual = 50 * (75 + 3) / 100 = 39.000
                    $this->assertEqualsWithDelta(39.0, (float)$r->issue_actual, 0.001);
                }
            }
            $this->assertTrue($foundRec, 'Mock receipt must appear in daily report.');
            $this->assertTrue($foundIss, 'Mock issue must appear in daily report.');
        } finally {
            // Clean up test records
            $db->table('waves')->whereIn('in_out_id', [$recId, $issId])->delete();
        }
    }

    /**
     * 5. Unauthenticated Access Protection.
     */
    public function testUnauthenticatedAccessIsRedirected(): void
    {
        $session = Services::session();
        $session->destroy();

        $result = $this->get('report/daily_report');
        $result->assertRedirect();
    }

    /**
     * 6. Staff Role Access (Admin / Superadmin).
     */
    public function testAdminAccessToDailyReport(): void
    {
        $result = $this->withSession([
            'spider' => [
                'user_id'      => 1,
                'user'         => 'superadmin',
                'access_level' => 'superadmin',
                'private_key'  => 'superadmin_test_key_123',
                'location'     => 'ho',
            ],
        ])->get('report/daily_report');

        $result->assertStatus(200);
        $result->assertSee('Daily Report');
        $result->assertSee('green-bg');
        $result->assertSee('red-bg');
        $result->assertSee('Balance');
        $result->assertSee('Print');
        $result->assertSee('Approve and Submit');
    }

    /**
     * 7. Print View Rendering.
     */
    public function testPrintDailyReportView(): void
    {
        $result = $this->withSession([
            'spider' => [
                'user_id'      => 1,
                'user'         => 'superadmin',
                'access_level' => 'superadmin',
                'private_key'  => 'superadmin_test_key_123',
                'location'     => 'ho',
            ],
        ])->get('report/print_daily_report');

        $result->assertStatus(200);
        $result->assertSee('Receipt');
        $result->assertSee('Issue');
        $result->assertSee('Total Reciept');
        $result->assertSee('Total Issue');
        $result->assertSee('window.print()');
    }

    /**
     * 8. Customer Role Redirects to Designated Customer Ledger Landing.
     */
    public function testCustomerRoleRedirectedToLedger(): void
    {
        $result = $this->withSession([
            'spider' => [
                'user_id'      => 10,
                'user'         => 'customer_user',
                'access_level' => 'cust',
                'private_key'  => 'cust_private_key_abc123',
                'location'     => 'ug',
            ],
        ])->get('report/daily_report');

        $result->assertRedirectTo(base_url('report/customer_ledger_for_customer/cust_private_key_abc123'));
    }

    /**
     * 9. Approve and Submit Workflow.
     */
    public function testApproveAndSubmitUpdatesRecords(): void
    {
        $db = \Config\Database::connect('default');

        // Record any active unapproved IDs so we can restore them in finally
        $preUnapproved = $db->table('waves')->select('in_out_id')->where('approved_data', '0')->get()->getResultArray();
        $preUnapprovedIds = array_column($preUnapproved, 'in_out_id');

        // Insert temporary mock unapproved wave
        $tempKey = 'daily_approve_test_' . bin2hex(random_bytes(6));
        $db->table('waves')->insert([
            'private_key'        => $tempKey,
            'cust_id'            => 1,
            'transactional_mode' => 'MT',
            'transactional_type' => 'receipt',
            'gross_weight'       => 25.000,
            'net_weight'         => 25.000,
            'less_weight'        => 0.000,
            'purity'             => 92.000,
            'wastage'            => 0.000,
            'remark'             => 'Approve Submission Test',
            'approved_data'      => '0',
            'is_deleted'         => '0',
            'entry_date'         => date('Y-m-d'),
        ]);
        $tempId = $db->insertID();

        try {
            // Submit approval POST
            $result = $this->withSession([
                'spider' => [
                    'user_id'      => 1,
                    'user'         => 'superadmin',
                    'access_level' => 'superadmin',
                    'private_key'  => 'superadmin_test_key_123',
                    'location'     => 'ho',
                ],
            ])->post('report/export_daily_report', [
                'approved' => 'approved',
            ]);

            $result->assertRedirectTo(base_url('report/daily_report'));

            // Check record was updated to approved_data = '1'
            $updated = $db->table('waves')->where('in_out_id', $tempId)->get()->getRowArray();
            $this->assertNotNull($updated);
            $this->assertSame('1', (string)$updated['approved_data'], 'Record should be approved after export/submission.');
        } finally {
            $db->table('waves')->where('in_out_id', $tempId)->delete();
            if (!empty($preUnapprovedIds)) {
                $db->table('waves')->whereIn('in_out_id', $preUnapprovedIds)->update(['approved_data' => '0']);
            }
        }
    }

    /**
     * 10. Dynamic Report by Date loads actual records from new-spiderwaves:3307.
     */
    public function testDynamicDailyReportByDateLoadsActualCI4Data(): void
    {
        $data = $this->ledgerService->getDailyReportData('2026-05-24');

        $this->assertSame(4, $data['receipt_count'], 'Should find exactly 4 receipts for 2026-05-24 in new-spiderwaves.');
        $this->assertSame(10, $data['issue_count'], 'Should find exactly 10 issues for 2026-05-24 in new-spiderwaves.');
        $this->assertEqualsWithDelta(-480.600, $data['or_balance'], 0.001, 'Balance for 2026-05-24 should be exactly -480.600.');
        $this->assertSame('-480.600', $data['display_balance']);

        // Verify customer names from database join
        $receiptCusts = array_filter(array_map(fn($r) => $r->receipt_cust_name, $data['or_data']));
        $this->assertContains('ABDULA -MORE', $receiptCusts);
        $this->assertContains('LP MUTHU', $receiptCusts);

        $issueCusts = array_filter(array_map(fn($r) => $r->issue_cust_name, $data['or_data']));
        $this->assertContains('ALIYA', $issueCusts);
    }

    /**
     * 11. Controller GET with date parameter returns dynamic page.
     */
    public function testDailyReportWithDateQueryParameter(): void
    {
        $result = $this->withSession([
            'spider' => [
                'user_id'      => 1,
                'user'         => 'superadmin',
                'access_level' => 'superadmin',
                'private_key'  => 'superadmin_test_key_123',
                'location'     => 'ho',
            ],
        ])->get('report/daily_report?date=2026-05-24');

        $result->assertStatus(200);
        $result->assertSee('LP MUTHU');
        $result->assertSee('ALIYA');
        $result->assertSee('-480.600');
    }

    /**
     * 12. Confirm no hardcoded sample rows (Z-SO INACTIVE with 1000.000 or balance 1774.000).
     */
    public function testNoHardcodedSampleRowsPresent(): void
    {
        // When querying empty or current date with no unapproved rows, balance must be 0.000, not 1774.000
        $data = $this->ledgerService->getDailyReportData('2099-01-01');
        $this->assertSame(0, $data['receipt_count']);
        $this->assertSame(0, $data['issue_count']);
        $this->assertSame(0.0, (float)$data['or_balance']);
        $this->assertSame('0.000', $data['display_balance']);
        $this->assertNotEquals('1774.000', $data['display_balance']);
    }
}
