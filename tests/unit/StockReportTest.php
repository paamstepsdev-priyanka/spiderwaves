<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\AccountModel;
use App\Models\StockPointModel;
use App\Services\LedgerService;
use App\Controllers\ReportController;
use Config\Services;

/**
 * StockReportTest
 *
 * Automated test suite for CI4 Stock Report module.
 * Verifies:
 * - Active database connection (new-spiderwaves:3307)
 * - Account/departments loading and legacy ordering
 * - Stock-point checkpoint temporal filtering
 * - Standard and special accounts (Loss, Refining, Locker) calculation rules
 * - MT fine gold calculation and 3-decimal display formatting
 * - Zero-balance handling
 * - Balance formula parity (Receive - Issue = Balance)
 * - Report routes and authentication/role protection
 * - Controller execution and view rendering
 *
 * @internal
 */
final class StockReportTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $DBGroup = 'default';

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure default database connection group is used
        \Config\Database::connect('default');
    }

    /**
     * 1. Database Connection Rule Verification.
     * Ensures CI4 test environment connects exclusively to new-spiderwaves on port 3307.
     */
    public function testDatabaseConnectionRule(): void
    {
        $db = \Config\Database::connect('default');
        $this->assertSame('new-spiderwaves', $db->database, 'CI4 must connect to new-spiderwaves database.');
        $this->assertSame(3307, (int)$db->port, 'CI4 must connect on port 3307.');
    }

    /**
     * 2. Account & Departments Loading.
     * Verifies that the Stock Report loads the exact 19 legacy department accounts in correct sequence.
     */
    public function testStockReportAccountLoading(): void
    {
        $accountModel = model(AccountModel::class);
        $accountIds = $accountModel->getStockReportAccountIds();

        $expectedIds = [
            AccountModel::CASTING_ID,          // 2
            AccountModel::HEERA_LIGHT_ID,      // 3
            AccountModel::FILING_ID,           // 4
            AccountModel::SETTING_ID,          // 5
            AccountModel::POLISH_ID,           // 6
            AccountModel::RODIUM_ID,           // 7
            AccountModel::PACKING_ID,          // 8
            AccountModel::REFINING_ID,         // 9
            AccountModel::G58_POLISH_ID,       // 13
            AccountModel::G58_SETTING_ID,      // 14
            AccountModel::G58_FILING_ID,       // 15
            AccountModel::ZERO_BALANCE_ID,     // 16
            AccountModel::G58_TRANSFER_ID,     // 17
            AccountModel::GALAI_ID,            // 20
            AccountModel::TASH_ID,             // 21
            AccountModel::SAPPHIRE_ID,         // 22
            AccountModel::CNC_ID,              // 23
            AccountModel::LOSS_ID,             // 12
            AccountModel::STOCK_ID,            // 1
        ];

        $this->assertSame($expectedIds, $accountIds, 'Account IDs must match legacy CI2 sequence.');
        $this->assertCount(19, $accountIds, 'Stock report must contain exactly 19 accounts.');

        // Verify each account exists in database and has 32-char private key
        $dbAccounts = $accountModel->whereIn('account_id', $accountIds)->findAll();
        $this->assertCount(19, $dbAccounts, 'All 19 department accounts must exist in account table.');

        foreach ($dbAccounts as $acc) {
            $this->assertNotEmpty($acc['account_name'], 'Account name must not be empty.');
            $this->assertSame(32, strlen($acc['private_key']), 'Private key must be a 32-character GUID.');
        }
    }

    /**
     * 3. Stock-Point Temporal Checkpoint Logic.
     * Verifies that the active stock-point date is retrieved correctly.
     */
    public function testStockPointCheckpointDate(): void
    {
        $ledgerService = new LedgerService();
        $stockPointModel = model(StockPointModel::class);

        $latestSp = $stockPointModel->getLatestStockPoint();
        $this->assertNotNull($latestSp, 'Stock point table must have checkpoints.');

        $activeDate = $ledgerService->getActiveStockPointDate();
        $this->assertNotEmpty($activeDate, 'Active stock point date must not be empty.');
        $this->assertSame($latestSp['date_time'], $activeDate, 'Active stock point must match latest stock_point record.');

        // Test explicit override parameter
        $testDate = '2026-01-01 00:00:00';
        $this->assertSame($testDate, $ledgerService->getActiveStockPointDate($testDate));
    }

    /**
     * 4. Metal Accounting Formulas & Display Precision.
     * Verifies Fine MT formula and view_round_up decimal formatting.
     */
    public function testFineWeightCalculationAndRounding(): void
    {
        $ledgerService = new LedgerService();

        // 100g at 75% purity = 75.000g fine
        $fineMt = $ledgerService->calculateFineWeight(100.0, 75.0, 0.0, 'MT');
        $this->assertEqualsWithDelta(75.000, $fineMt, 0.0001);

        // 100g at 75% purity + 2.5% wastage (OR mode) = 77.500g fine
        $fineOr = $ledgerService->calculateFineWeight(100.0, 75.0, 2.5, 'OR');
        $this->assertEqualsWithDelta(77.500, $fineOr, 0.0001);

        // Formatting with view_round_up
        $this->assertSame('75.000', view_round_up(75));
        $this->assertSame('123.456', view_round_up(123.4564));
        $this->assertSame('-559.000', view_round_up(-559));
        $this->assertSame('0.000', view_round_up(0));
    }

    /**
     * 5. Stock Report Data Structure, Aggregation and Balance Parity.
     * Verifies LedgerService::getStockReportData().
     */
    public function testStockReportDataParity(): void
    {
        $ledgerService = new LedgerService();
        $data = $ledgerService->getStockReportData();

        $this->assertArrayHasKey('stock_point_date', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('total_balance', $data);
        $this->assertArrayHasKey('display_total', $data);

        $items = $data['items'];
        $this->assertCount(19, $items, 'Stock report items must contain 19 department rows.');

        $calculatedSum = 0.0;
        foreach ($items as $item) {
            $this->assertArrayHasKey('sno', $item);
            $this->assertArrayHasKey('account_id', $item);
            $this->assertArrayHasKey('account_name', $item);
            $this->assertArrayHasKey('private_key', $item);
            $this->assertArrayHasKey('receive_balance', $item);
            $this->assertArrayHasKey('issue_balance', $item);
            $this->assertArrayHasKey('balance', $item);
            $this->assertArrayHasKey('display_balance', $item);

            // Verify math: balance = receive - issue
            $expectedBal = $item['receive_balance'] - $item['issue_balance'];
            $this->assertEqualsWithDelta($expectedBal, $item['balance'], 0.001, "Balance formula mismatch for {$item['account_name']}");
            $this->assertSame(view_round_up($expectedBal, 3), $item['display_balance']);

            $calculatedSum += $item['balance'];
        }

        $this->assertEqualsWithDelta($calculatedSum, $data['total_balance'], 0.001, 'Total balance must equal sum of rows.');
        $this->assertSame(view_round_up($calculatedSum, 3), $data['display_total']);
    }

    /**
     * 6. Special Accounts Handling (Loss, Refining, Locker).
     * Ensures special account queries match legacy domain logic.
     */
    public function testSpecialAccountsHandling(): void
    {
        $ledgerService = new LedgerService();
        $report = $ledgerService->getStockReportData();

        $accountMap = [];
        foreach ($report['items'] as $item) {
            $accountMap[$item['account_id']] = $item;
        }

        // Loss (12)
        $this->assertArrayHasKey(AccountModel::LOSS_ID, $accountMap);
        $loss = $accountMap[AccountModel::LOSS_ID];
        $this->assertGreaterThan(0, $loss['receive_balance'], 'Loss department must have accumulated lifetime receipts.');

        // Refining (9)
        $this->assertArrayHasKey(AccountModel::REFINING_ID, $accountMap);
        $refining = $accountMap[AccountModel::REFINING_ID];
        $this->assertGreaterThan(0, $refining['receive_balance'], 'Refining department must have accumulated lifetime receipts.');

        // Locker (1)
        $this->assertArrayHasKey(AccountModel::STOCK_ID, $accountMap);
        $locker = $accountMap[AccountModel::STOCK_ID];
        $this->assertGreaterThan(0, $locker['receive_balance'], 'Locker department must have receipt balance.');
    }

    /**
     * 7. Controller Execution & View Rendering.
     * Verifies ReportController::stockReport() and lossReport() alias.
     */
    public function testReportControllerRendering(): void
    {
        $session = Services::session();
        $session->set('spider', [
            'user'         => 'superadmin',
            'user_id'      => 1,
            'access_level' => 'superadmin',
            'location'     => 'ho',
        ]);

        $controller = new ReportController();
        $controller->initController(
            Services::request(),
            Services::response(),
            Services::logger()
        );

        Services::renderer(null, null, false);

        // Test modern stockReport method
        $result = $controller->stockReport();
        $this->assertIsString($result);
        $this->assertStringContainsString('Stock', $result);
        $this->assertStringContainsString('Department Stock Balances', $result);
        $this->assertStringContainsString('MT+OR', $result);
        $this->assertStringContainsString('Casting', $result);
        $this->assertStringContainsString('Locker', $result);
        $this->assertStringContainsString('Refining', $result);
        $this->assertStringContainsString('Loss', $result);

        // Test legacy alias lossReport
        $aliasResult = $controller->lossReport();
        $this->assertIsString($aliasResult);
        $this->assertStringContainsString('Stock', $aliasResult);
    }

    /**
     * 8. Unauthorized Role Access Control.
     * Ensures unauthorized roles are redirected away.
     */
    public function testUnauthorizedRoleRedirect(): void
    {
        $session = Services::session();
        $session->set('spider', [
            'user'         => 'counter_agent',
            'user_id'      => 5,
            'access_level' => 'user', // 'user' is inward counter agent, unauthorized for reports
            'location'     => 'ho',
        ]);

        $controller = new ReportController();
        $controller->initController(
            Services::request(),
            Services::response(),
            Services::logger()
        );

        $response = $controller->stockReport();
        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $response);
        $this->assertSame(302, $response->getStatusCode(), 'Unauthorized role must receive HTTP 302 redirect.');

        // Test Admin role is now blocked and redirected from Stock Report
        $session->set('spider', [
            'user'         => 'admin',
            'user_id'      => 8,
            'access_level' => 'admin',
            'location'     => 'ho',
        ]);

        $adminResponse = $controller->stockReport();
        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $adminResponse);
        $this->assertSame(302, $adminResponse->getStatusCode(), 'Admin role must be blocked and receive HTTP 302 redirect.');
    }
}
