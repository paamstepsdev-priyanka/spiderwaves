<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\AccountModel;
use App\Models\ProcessWavesModel;
use App\Services\LedgerService;

final class NegativeStockPreventionTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $DBGroup = 'default';

    protected function setUp(): void
    {
        parent::setUp();
        \Config\Database::connect('default');
    }

    private function getAdminSession(): array
    {
        return [
            'spider' => [
                'user_id'      => 1,
                'user'         => 'superadmin',
                'access_level' => 'superadmin',
                'location'     => 'ug',
                'private_key'  => 'superadmin_test_key',
            ],
        ];
    }

    /**
     * Test 1: Casting, Setting, and Rhodium transfers are strictly rejected when stock is insufficient.
     * Verifies Root Cause 1 (NEGATIVE_LOSS_ACCOUNTS bypass removal).
     */
    public function testCastingSettingRhodiumRejectInsufficientStock(): void
    {
        $ledgerService = new LedgerService();
        $startDate = $ledgerService->getActiveStockPointDate();

        // 1. Casting (ID 2): Currently has negative stock (-3488g). Must be REJECTED.
        $this->assertFalse(
            $ledgerService->checkWeight(AccountModel::CASTING_ID, AccountModel::FILING_ID, 10.000, 92.00, $startDate),
            'checkWeight for Casting must return false when stock is negative or insufficient'
        );

        $resCasting = $this->withSession($this->getAdminSession())->post('process-waves/insert-transfer', [
            'idempotency_token' => bin2hex(random_bytes(16)),
            'from_id'           => AccountModel::CASTING_ID,
            'to_id'             => AccountModel::FILING_ID,
            'gross_weight'      => 50.000,
            'purity'            => 92.00,
            'remark'            => 'Negative stock attempt from Casting',
        ]);
        $jsonCasting = json_decode($resCasting->getJSON(), true);
        $this->assertEquals('false', $jsonCasting['status']);
        $this->assertStringContainsString('insufficient', strtolower($jsonCasting['message']));

        // 2. Setting (ID 5): Has 0 stock. Must be REJECTED.
        $this->assertFalse(
            $ledgerService->checkWeight(AccountModel::SETTING_ID, AccountModel::POLISH_ID, 5.000, 92.00, $startDate),
            'checkWeight for Setting must return false when stock is 0'
        );

        $resSetting = $this->withSession($this->getAdminSession())->post('process-waves/insert-transfer', [
            'idempotency_token' => bin2hex(random_bytes(16)),
            'from_id'           => AccountModel::SETTING_ID,
            'to_id'             => AccountModel::POLISH_ID,
            'gross_weight'      => 20.000,
            'purity'            => 92.00,
            'remark'            => 'Zero stock attempt from Setting',
        ]);
        $jsonSetting = json_decode($resSetting->getJSON(), true);
        $this->assertEquals('false', $jsonSetting['status']);
        $this->assertStringContainsString('insufficient', strtolower($jsonSetting['message']));

        // 3. Rodium (ID 7): Has 0 stock. Must be REJECTED.
        $this->assertFalse(
            $ledgerService->checkWeight(AccountModel::RODIUM_ID, AccountModel::PACKING_ID, 2.000, 92.00, $startDate),
            'checkWeight for Rodium must return false when stock is 0'
        );

        $resRodium = $this->withSession($this->getAdminSession())->post('process-waves/insert-transfer', [
            'idempotency_token' => bin2hex(random_bytes(16)),
            'from_id'           => AccountModel::RODIUM_ID,
            'to_id'             => AccountModel::PACKING_ID,
            'gross_weight'      => 10.000,
            'purity'            => 92.00,
            'remark'            => 'Zero stock attempt from Rodium',
        ]);
        $jsonRodium = json_decode($resRodium->getJSON(), true);
        $this->assertEquals('false', $jsonRodium['status']);
        $this->assertStringContainsString('insufficient', strtolower($jsonRodium['message']));
    }

    /**
     * Test 2: Transfer gross weight cannot exceed source batch gross weight.
     * Verifies Root Cause 2 (Batch Weight Validation).
     */
    public function testBatchTransferWeightExceedingSourceBatchIsRejected(): void
    {
        $processWavesModel = new ProcessWavesModel();

        // Create a test source batch of 25.000g in Filing (ID 4)
        $sourceBatchId = $processWavesModel->insertProcessWavesData([
            'private_key'          => md5(uniqid('src_batch_', true)),
            'cust_id'              => 1,
            'from_id'              => AccountModel::CASTING_ID,
            'to_id'                => AccountModel::FILING_ID,
            'mapped_cust_id'       => 1,
            'product'              => 'MT',
            'gross_weight'         => 25.000,
            'purity'               => 92.00,
            'inner_transfer'       => '0',
            'stock_inner_transfer' => '0',
            'date'                 => date('Y-m-d H:i:s'),
            'remark'               => 'Source batch 25g',
            'user_by'              => 1,
        ]);

        // Attempt to transfer 35.000g from the 25.000g batch
        $res = $this->withSession($this->getAdminSession())->post('process-waves/insert-transfer', [
            'idempotency_token'   => bin2hex(random_bytes(16)),
            'from_id'             => AccountModel::FILING_ID,
            'to_id'               => AccountModel::POLISH_ID,
            'process_waves_id'    => $sourceBatchId,
            'mapped_cust_id'      => 1,
            'gross_weight'        => 35.000, // Exceeds 25.000g!
            'purity'              => 92.00,
            'recent_gross_weight' => 25.000,
            'remark'              => 'Attempt weight gain',
        ]);

        $json = json_decode($res->getJSON(), true);
        $this->assertEquals('false', $json['status']);
        $this->assertStringContainsString('exceeds source batch gross weight', $json['message']);

        // Clean up
        $processWavesModel->delete($sourceBatchId);
    }

    /**
     * Test 3: Batch transfer exceeding available department net stock is rejected.
     * Verifies Root Cause 2 (Universal Department Stock Validation for Batch Transfers).
     */
    public function testBatchTransferExceedingAvailableDepartmentStockIsRejected(): void
    {
        $processWavesModel = new ProcessWavesModel();

        // Create an isolated batch with huge weight (100,000g) in Filing
        $sourceBatchId = $processWavesModel->insertProcessWavesData([
            'private_key'          => md5(uniqid('huge_batch_', true)),
            'cust_id'              => 1,
            'from_id'              => AccountModel::CASTING_ID,
            'to_id'                => AccountModel::FILING_ID,
            'mapped_cust_id'       => 1,
            'product'              => 'MT',
            'gross_weight'         => 999999.000,
            'purity'               => 92.00,
            'inner_transfer'       => '0',
            'stock_inner_transfer' => '0',
            'date'                 => '2020-01-01 00:00:00', // Received before stock checkpoint, so not in current balance!
            'remark'               => 'Historical batch',
            'user_by'              => 1,
        ]);

        // Attempt to transfer the 999,999g batch.
        // Even though it is an un-transferred batch, Filing does not have 999,999g in current inventory.
        $res = $this->withSession($this->getAdminSession())->post('process-waves/insert-transfer', [
            'idempotency_token'   => bin2hex(random_bytes(16)),
            'from_id'             => AccountModel::FILING_ID,
            'to_id'               => AccountModel::POLISH_ID,
            'process_waves_id'    => $sourceBatchId,
            'mapped_cust_id'      => 1,
            'gross_weight'        => 999999.000,
            'purity'              => 92.00,
            'recent_gross_weight' => 999999.000,
            'remark'              => 'Massive batch overdraft',
        ]);

        $json = json_decode($res->getJSON(), true);
        $this->assertEquals('false', $json['status']);
        $this->assertStringContainsString('insufficient', strtolower($json['message']));

        // Clean up
        $processWavesModel->delete($sourceBatchId);
    }

    /**
     * Test 4: Piece count validation prevents transferring more pieces than available.
     * Verifies Root Cause 5 (Server-side Piece Count Validation).
     */
    public function testPieceCountValidationPreventsOverdraft(): void
    {
        $processWavesModel = new ProcessWavesModel();

        // Create a batch in Polish (Account 6) with 5 pcs and 10g
        $batchId = $processWavesModel->insertProcessWavesData([
            'private_key'          => md5(uniqid('pcs_batch_', true)),
            'cust_id'              => 1,
            'from_id'              => AccountModel::SETTING_ID,
            'to_id'                => AccountModel::POLISH_ID,
            'mapped_cust_id'       => 1,
            'product'              => 'MT',
            'gross_weight'         => 10.000,
            'purity'               => 92.00,
            'pcs'                  => 5,
            'inner_transfer'       => '0',
            'stock_inner_transfer' => '0',
            'date'                 => date('Y-m-d H:i:s'),
            'remark'               => '5 pcs batch',
            'user_by'              => 1,
        ]);

        // Attempt to transfer 20 pieces (only 5 pieces exist in Polish for this mapped customer)
        $res = $this->withSession($this->getAdminSession())->post('process-waves/insert-transfer', [
            'idempotency_token'   => bin2hex(random_bytes(16)),
            'from_id'             => AccountModel::POLISH_ID,
            'to_id'               => AccountModel::RODIUM_ID,
            'process_waves_id'    => $batchId,
            'mapped_cust_id'      => 1,
            'gross_weight'        => 10.000,
            'purity'              => 92.00,
            'recent_gross_weight' => 10.000,
            'pcs'                 => 20, // Exceeds 5 pcs!
            'remark'              => 'Piece count overdraft',
        ]);

        $json = json_decode($res->getJSON(), true);
        $this->assertEquals('false', $json['status']);
        $this->assertStringContainsString('insufficient piece count', strtolower($json['message']));

        // Clean up
        $processWavesModel->delete($batchId);
    }

    /**
     * Test 5: Opening balance is correctly included in checkWeight and available balance.
     * Verifies Root Cause 4 (Opening Balance Inclusion).
     */
    public function testOpeningBalanceInclusionInAvailableStock(): void
    {
        $ledgerService = new LedgerService();
        $startDate = $ledgerService->getActiveStockPointDate();

        // Filing (Account 4) has ~857g balance.
        $currentBal = $ledgerService->getAccountNetBalance(AccountModel::FILING_ID, null, 'fine', $startDate);
        $this->assertGreaterThan(0, $currentBal);

        // checkWeight for a valid transfer <= current balance must be TRUE
        $this->assertTrue($ledgerService->checkWeight(AccountModel::FILING_ID, AccountModel::POLISH_ID, 1.000, 92.00, $startDate));

        // checkWeight for a transfer > current balance must be FALSE
        $hugeWeight = ($currentBal + 500) / 0.92;
        $this->assertFalse($ledgerService->checkWeight(AccountModel::FILING_ID, AccountModel::POLISH_ID, $hugeWeight, 92.00, $startDate));
    }

    /**
     * Test 6: Verify negative balances are accurately reported without abs() masking.
     * Verifies Root Cause 6 (Negative Balance Display).
     */
    public function testNegativeBalanceReportingWithoutAbsMasking(): void
    {
        $ledgerService = new LedgerService();
        $startDate = $ledgerService->getActiveStockPointDate();

        // Casting has -3488.255g in current database
        $castingBal = $ledgerService->getAccountNetBalance(AccountModel::CASTING_ID, null, 'fine', $startDate);
        $this->assertLessThan(0, $castingBal, 'Casting balance must be negative, reflecting real physical deficit');

        // Verify stock report preserves the true negative balance
        $stockReport = $ledgerService->getStockReportData();
        $castingItem = null;
        foreach ($stockReport['items'] as $item) {
            if ($item['account_id'] === AccountModel::CASTING_ID) {
                $castingItem = $item;
                break;
            }
        }

        $this->assertNotNull($castingItem);
        $this->assertLessThan(0, $castingItem['balance']);
        $this->assertStringStartsWith('-', $castingItem['display_balance']);
    }
}

