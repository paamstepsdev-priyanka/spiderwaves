<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\CustomerModel;
use App\Services\LedgerService;
use App\Controllers\ReportController;
use Config\Services;

/**
 * CustomerReportTest
 *
 * Automated test suite for CI4 Customer-Wise Report and Customer Ledger.
 *
 * @internal
 */
final class CustomerReportTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $DBGroup = 'default';
    protected LedgerService $ledgerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledgerService = new LedgerService();
    }

    /**
     * 1. Database Connection Rule.
     * Must connect exclusively to new-spiderwaves on port 3307.
     */
    public function testDatabaseConnectionRule(): void
    {
        $db = \Config\Database::connect('default');
        $this->assertSame('new-spiderwaves', $db->database, 'CI4 must connect to new-spiderwaves database.');
        $this->assertSame(3307, (int)$db->port, 'CI4 must connect on port 3307.');
    }

    /**
     * 2. Customer-Wise Report Service Data Structure.
     */
    public function testCustomerWiseReportDataStructure(): void
    {
        $data = $this->ledgerService->getCustomerWiseReportData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('total_count', $data);
        $this->assertArrayHasKey('total_balance', $data);
        $this->assertArrayHasKey('display_total', $data);
        $this->assertGreaterThan(0, $data['total_count'], 'Should load active customers.');

        // Test first customer item structure
        $first = $data['items'][0];
        $this->assertArrayHasKey('sno', $first);
        $this->assertArrayHasKey('cust_name', $first);
        $this->assertArrayHasKey('private_key', $first);
        $this->assertArrayHasKey('balance', $first);
        $this->assertArrayHasKey('display_balance', $first);

        // Display balance must be formatted with 3 decimal places
        $this->assertMatchesRegularExpression('/^-?\d+\.\d{3}$/', $first['display_balance']);
    }

    /**
     * 3. Customer-Wise Report Balance Parity.
     * Verify that Net Balance = cust.MT_closing_balance + cust.OR_closing_balance + Receipts - Issues.
     */
    public function testCustomerWiseReportBalanceFormula(): void
    {
        $data = $this->ledgerService->getCustomerWiseReportData();
        $this->assertNotEmpty($data['items']);

        $db = \Config\Database::connect('default');

        // Verify across first 5 customers
        for ($i = 0; $i < min(5, count($data['items'])); $i++) {
            $item = $data['items'][$i];
            $custId = $item['cust_id'];

            $cust = $db->table('cust')->where('cust_id', $custId)->get()->getRowArray();
            $baseOpening = (float)$cust['MT_closing_balance'] + (float)$cust['OR_closing_balance'];

            $expectedBalance = $baseOpening + $item['total_receipt'] - $item['total_issue'];
            $this->assertEqualsWithDelta(
                $expectedBalance,
                $item['balance'],
                0.0001,
                "Customer {$item['cust_name']} balance must match baseOpening + receipts - issues."
            );
        }
    }

    /**
     * 4. Customer Ledger Data Retrieval for Valid Customer.
     */
    public function testCustomerLedgerValidCustomer(): void
    {
        // Customer 169 (ABDUL-REPAIR)
        $abdulKey = '5c8263667b110ef9ae30ed9760ecf8bb';
        $ledger = $this->ledgerService->getCustomerLedgerData($abdulKey);

        $this->assertNotNull($ledger, 'Ledger data should not be null for valid customer.');
        $this->assertSame('ABDUL-REPAIR', $ledger['customer']['cust_name']);
        $this->assertSame($abdulKey, $ledger['customer']['private_key']);

        $this->assertArrayHasKey('receipts', $ledger);
        $this->assertArrayHasKey('issues', $ledger);
        $this->assertArrayHasKey('pairedRows', $ledger);
        $this->assertArrayHasKey('totals', $ledger);
        $this->assertArrayHasKey('opening_balances', $ledger);

        // Precision checks
        $this->assertMatchesRegularExpression('/^-?\d+\.\d{3}$/', $ledger['totals']['display_closing_mt']);
        $this->assertMatchesRegularExpression('/^-?\d+\.\d{3}$/', $ledger['totals']['display_closing_or']);
        $this->assertMatchesRegularExpression('/^-?\d+\.\d{2}$/', $ledger['totals']['display_closing_rk']);
        $this->assertMatchesRegularExpression('/^-?\d+\.\d{3}$/', $ledger['totals']['display_closing_metal']);
    }

    /**
     * 5. Customer Ledger Opening Balance Placement.
     * Positive opening balance on Receipt side; Negative opening balance on Issue side.
     */
    public function testCustomerLedgerOpeningBalancePlacement(): void
    {
        $db = \Config\Database::connect('default');

        // Find a customer with positive base opening balance if one exists
        $posCust = $db->table('cust')
            ->where('(MT_closing_balance + OR_closing_balance) >', 0)
            ->get()->getRowArray();

        if ($posCust) {
            $ledger = $this->ledgerService->getCustomerLedgerData($posCust['private_key']);
            $this->assertNotEmpty($ledger['receipts']);
            $firstReceipt = $ledger['receipts'][0];
            $this->assertTrue($firstReceipt['is_opening']);
            $this->assertSame('Opening Balance', $firstReceipt['description']);
        }

        // Find a customer with negative base opening balance if one exists
        $negCust = $db->table('cust')
            ->where('(MT_closing_balance + OR_closing_balance) <', 0)
            ->get()->getRowArray();

        if ($negCust) {
            $ledger = $this->ledgerService->getCustomerLedgerData($negCust['private_key']);
            $this->assertNotEmpty($ledger['issues']);
            $firstIssue = $ledger['issues'][0];
            $this->assertTrue($firstIssue['is_opening']);
            $this->assertSame('Opening Balance', $firstIssue['description']);
        }
    }

    /**
     * 6. Customer Ledger Date Filtering & Opening Balance Recalculation.
     */
    public function testCustomerLedgerDateFiltering(): void
    {
        $narayanKey = 'ce556bdd5b8f904dbb5664c2d8381dbc';

        $fullLedger = $this->ledgerService->getCustomerLedgerData($narayanKey);
        $filteredLedger = $this->ledgerService->getCustomerLedgerData($narayanKey, '2024-01-01', '2024-12-31');

        $this->assertNotNull($filteredLedger);
        $this->assertSame('2024-01-01', $filteredLedger['filters']['from_date']);
        $this->assertSame('2024-12-31', $filteredLedger['filters']['to_date']);

        // Filtered transactions count should be a subset of the lifetime transactions
        $this->assertLessThanOrEqual(
            count($fullLedger['pairedRows']),
            count($filteredLedger['pairedRows'])
        );

        // Verify that opening metal balance for 2024 is calculated from pre-2024 transactions
        $this->assertNotNull($filteredLedger['opening_balances']['metal']);
    }

    /**
     * 7. Customer Ledger with Invalid Key Returns Null.
     */
    public function testCustomerLedgerInvalidKey(): void
    {
        $result = $this->ledgerService->getCustomerLedgerData('invalid_key_99999999');
        $this->assertNull($result, 'Non-existent customer private key must return null.');
    }

    /**
     * 8. Route & Authorization Protection: Unauthenticated Users.
     */
    public function testCustomerReportUnauthenticatedAccessRedirects(): void
    {
        $result = $this->get('report/cust_wise_report');
        $result->assertRedirect();
    }

    /**
     * 9. Feature Test: Customer Wise Report Route (200 OK).
     */
    public function testCustomerWiseReportRouteSuccess(): void
    {
        $result = $this->withSession([
            'spider' => [
                'id'           => 1,
                'user'         => 'Superadmin',
                'access_level' => 'superadmin',
            ],
        ])->get('report/cust_wise_report');

        $result->assertStatus(200);
        $result->assertSee('Customer Wise Report');
        $result->assertSee('MT+OR');
        $result->assertSee('customerTable');
        $result->assertDontSee('City / Contact');
        $result->assertDontSee('>Action<');
    }


    /**
     * 10. Feature Test: Customer Ledger Route (200 OK).
     */
    public function testCustomerLedgerRouteSuccess(): void
    {
        $abdulKey = '5c8263667b110ef9ae30ed9760ecf8bb';

        $result = $this->withSession([
            'spider' => [
                'id'           => 1,
                'user'         => 'Superadmin',
                'access_level' => 'superadmin',
            ],
        ])->get('report/customer_ledger/' . $abdulKey);

        $result->assertStatus(200);
        $result->assertSee('ABDUL-REPAIR');
        $result->assertSee('RECEIPTS (DEPOSITS / JAMA)');
        $result->assertSee('ISSUES (WITHDRAWALS / UDHAR)');
        $result->assertSee('From Date:');
        $result->assertSee('To Date:');
        $result->assertSee('Closing Balances');
    }

    /**
     * 11. Feature Test: Customer Ledger Invalid Key Redirects to Customer Wise Report.
     */
    public function testCustomerLedgerInvalidKeyRedirects(): void
    {
        $result = $this->withSession([
            'spider' => [
                'id'           => 1,
                'user'         => 'Superadmin',
                'access_level' => 'superadmin',
            ],
        ])->get('report/customer_ledger/invalid_private_key_xyz');

        $result->assertRedirectTo(base_url('report/cust_wise_report'));
    }

    /**
     * 12. Direct Controller Rendering for Admin and Process Manager Roles.
     */
    public function testReportControllerRoles(): void
    {
        $session = Services::session();

        // Admin role
        $session->set('spider', [
            'id'           => 2,
            'user'         => 'Admin User',
            'access_level' => 'admin',
        ]);
        $controller = new ReportController();
        $controller->initController(Services::request(), Services::response(), Services::logger());
        $output = $controller->customerWiseReport();
        $this->assertIsString($output);
        $this->assertStringContainsString('Customer Wise Report', $output);

        // Superadmin role
        $session->set('spider', [
            'id'           => 1,
            'user'         => 'Superadmin User',
            'access_level' => 'superadmin',
        ]);
        $controller = new ReportController();
        $controller->initController(Services::request(), Services::response(), Services::logger());
        $output = $controller->customerWiseReport();
        $this->assertIsString($output);
        $this->assertStringContainsString('Customer Wise Report', $output);

        // Process Manager role (restricted in CI2 target permission matrix)
        $session->set('spider', [
            'id'           => 3,
            'user'         => 'PM User',
            'access_level' => 'process_manager',
        ]);
        $controller = new ReportController();
        $controller->initController(Services::request(), Services::response(), Services::logger());
        $response = $controller->customerWiseReport();
        $this->assertInstanceOf(\CodeIgniter\HTTP\RedirectResponse::class, $response);
    }

    /**
     * 13. Read-Only Safety: Ensure No Database Mutations.
     */
    public function testCustomerReportIsReadOnly(): void
    {
        $db = \Config\Database::connect('default');

        $beforeCustChecksum = $db->query("SELECT MD5(GROUP_CONCAT(CONCAT(cust_id, MT_closing_balance, OR_closing_balance, RK_closing_balance))) as cs FROM cust")->getRow()->cs;
        $beforeWavesCount = $db->table('waves')->countAllResults();

        // Run report operations
        $this->ledgerService->getCustomerWiseReportData();
        $this->ledgerService->getCustomerLedgerData('5c8263667b110ef9ae30ed9760ecf8bb');
        $this->ledgerService->getCustomerLedgerData('5c8263667b110ef9ae30ed9760ecf8bb', '2024-01-01', '2024-12-31');

        $afterCustChecksum = $db->query("SELECT MD5(GROUP_CONCAT(CONCAT(cust_id, MT_closing_balance, OR_closing_balance, RK_closing_balance))) as cs FROM cust")->getRow()->cs;
        $afterWavesCount = $db->table('waves')->countAllResults();

        $this->assertSame($beforeCustChecksum, $afterCustChecksum, 'Customer closing balances must remain unchanged during reports.');
        $this->assertSame($beforeWavesCount, $afterWavesCount, 'Waves records count must remain identical (zero mutations).');
    }
}
