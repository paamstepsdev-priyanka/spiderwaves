<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\AccountModel;
use App\Models\ProcessWavesModel;

final class InternalTransferDuplicatePreventionTest extends CIUnitTestCase
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

    public function testSingleBatchTransferPreventsDuplicateTransfers(): void
    {
        $processWavesModel = new ProcessWavesModel();
        $sourceKey = md5(uniqid('src_batch_', true));

        // Create an active source batch in Filing (ID: 4, has positive stock)
        $sourceBatchId = $processWavesModel->insertProcessWavesData([
            'private_key'          => $sourceKey,
            'cust_id'              => 1,
            'from_id'              => AccountModel::CASTING_ID,
            'to_id'                => AccountModel::FILING_ID,
            'mapped_cust_id'       => 1,
            'product'              => 'MT',
            'gross_weight'         => 125.500,
            'purity'               => 92.00,
            'inner_transfer'       => '0',
            'stock_inner_transfer' => '0',
            'date'                 => date('Y-m-d H:i:s'),
            'remark'               => 'Initial batch',
            'user_by'              => 1,
        ]);

        $this->assertNotEmpty($sourceBatchId);

        // 1. First transfer to Polish (ID: 6)
        $firstToken = bin2hex(random_bytes(16));
        $res1 = $this->withSession($this->getAdminSession())->post('process-waves/insert-transfer', [
            'idempotency_token'   => $firstToken,
            'from_id'             => AccountModel::FILING_ID,
            'to_id'               => AccountModel::POLISH_ID,
            'process_waves_id'    => $sourceBatchId,
            'mapped_cust_id'      => 1,
            'gross_weight'        => 125.500,
            'purity'              => 92.00,
            'recent_gross_weight' => 125.500,
            'remark'              => 'First transfer attempt',
        ]);

        $res1->assertStatus(200);
        $json1 = json_decode($res1->getJSON(), true);
        $this->assertEquals('true', $json1['status']);
        $newBatchId = (int) $json1['process_waves_id'];
        $this->assertGreaterThan(0, $newBatchId);

        // Verify source batch is now marked as inner_transfer = '1' and further_to_process_waves_id set
        $updatedSource = $processWavesModel->find($sourceBatchId);
        $this->assertEquals('1', (string) $updatedSource['inner_transfer']);
        $this->assertEquals($newBatchId, (int) $updatedSource['further_to_process_waves_id']);

        // 2. Second transfer attempt for the SAME source batch (e.g., repeated Enter key or multi-tab)
        $secondToken = bin2hex(random_bytes(16));
        $res2 = $this->withSession($this->getAdminSession())->post('process-waves/insert-transfer', [
            'idempotency_token'   => $secondToken,
            'from_id'             => AccountModel::FILING_ID,
            'to_id'               => AccountModel::POLISH_ID,
            'process_waves_id'    => $sourceBatchId,
            'mapped_cust_id'      => 1,
            'gross_weight'        => 125.500,
            'purity'              => 92.00,
            'recent_gross_weight' => 125.500,
            'remark'              => 'Duplicate transfer attempt',
        ]);

        $res2->assertStatus(200);
        $json2 = json_decode($res2->getJSON(), true);
        $this->assertEquals('false', $json2['status']);
        $this->assertStringContainsString('already been transferred', $json2['message']);

        // Clean up test records
        $processWavesModel->delete($newBatchId);
        $processWavesModel->delete($sourceBatchId);
    }

    public function testIdempotencyTokenPreventsDuplicateSubmission(): void
    {
        $processWavesModel = new ProcessWavesModel();
        $sourceKey = md5(uniqid('src_batch_', true));

        // Create an active source batch in Filing (ID: 4)
        $sourceBatchId = $processWavesModel->insertProcessWavesData([
            'private_key'          => $sourceKey,
            'cust_id'              => 1,
            'from_id'              => AccountModel::CASTING_ID,
            'to_id'                => AccountModel::FILING_ID,
            'mapped_cust_id'       => 1,
            'product'              => 'MT',
            'gross_weight'         => 80.000,
            'purity'               => 92.00,
            'inner_transfer'       => '0',
            'stock_inner_transfer' => '0',
            'date'                 => date('Y-m-d H:i:s'),
            'remark'               => 'Idempotency test batch',
            'user_by'              => 1,
        ]);

        $sharedToken = bin2hex(random_bytes(16));

        // 1. Initial submission
        $res1 = $this->withSession($this->getAdminSession())->post('process-waves/insert-transfer', [
            'idempotency_token'   => $sharedToken,
            'from_id'             => AccountModel::FILING_ID,
            'to_id'               => AccountModel::POLISH_ID,
            'process_waves_id'    => $sourceBatchId,
            'mapped_cust_id'      => 1,
            'gross_weight'        => 80.000,
            'purity'              => 92.00,
            'recent_gross_weight' => 80.000,
            'remark'              => 'Rapid double-click 1',
        ]);

        $json1 = json_decode($res1->getJSON(), true);
        $this->assertEquals('true', $json1['status']);
        $firstId = (int) $json1['process_waves_id'];

        // 2. Duplicate submission with identical idempotency token (simulating double click)
        $res2 = $this->withSession($this->getAdminSession())->post('process-waves/insert-transfer', [
            'idempotency_token'   => $sharedToken,
            'from_id'             => AccountModel::FILING_ID,
            'to_id'               => AccountModel::POLISH_ID,
            'process_waves_id'    => $sourceBatchId,
            'mapped_cust_id'      => 1,
            'gross_weight'        => 80.000,
            'purity'              => 92.00,
            'recent_gross_weight' => 80.000,
            'remark'              => 'Rapid double-click 2',
        ]);

        $json2 = json_decode($res2->getJSON(), true);
        $this->assertEquals('true', $json2['status']);
        $this->assertEquals($firstId, (int) $json2['process_waves_id'], 'Must return identical process_waves_id without creating duplicate');

        // Verify only ONE destination row was created in database with this token
        $db = \Config\Database::connect('default');
        $rows = $db->query("SELECT * FROM process_waves WHERE private_key = ?", [$sharedToken])->getResultArray();
        $this->assertCount(1, $rows, 'Exactly one row should exist for the given idempotency token');

        // Clean up
        $processWavesModel->delete($firstId);
        $processWavesModel->delete($sourceBatchId);
    }

    public function testMultiBatchAtomicRollbackIfAnyBatchAlreadyTransferred(): void
    {
        $processWavesModel = new ProcessWavesModel();

        // Batch 1: Available
        $id1 = $processWavesModel->insertProcessWavesData([
            'private_key'          => md5(uniqid('multi1_', true)),
            'cust_id'              => 1,
            'from_id'              => AccountModel::CASTING_ID,
            'to_id'                => AccountModel::FILING_ID,
            'mapped_cust_id'       => 1,
            'product'              => 'MT',
            'gross_weight'         => 50.000,
            'purity'               => 92.00,
            'inner_transfer'       => '0',
            'stock_inner_transfer' => '0',
            'date'                 => date('Y-m-d H:i:s'),
            'user_by'              => 1,
        ]);

        // Batch 2: Already transferred
        $id2 = $processWavesModel->insertProcessWavesData([
            'private_key'                 => md5(uniqid('multi2_', true)),
            'cust_id'                     => 1,
            'from_id'                     => AccountModel::CASTING_ID,
            'to_id'                       => AccountModel::FILING_ID,
            'mapped_cust_id'              => 1,
            'product'                     => 'MT',
            'gross_weight'                => 45.000,
            'purity'                      => 92.00,
            'inner_transfer'              => '1',
            'further_to_process_waves_id' => 888888,
            'stock_inner_transfer'        => '0',
            'date'                        => date('Y-m-d H:i:s'),
            'user_by'                     => 1,
        ]);

        $res = $this->withSession($this->getAdminSession())->post('process-waves/insert-transfer', [
            'idempotency_token'   => bin2hex(random_bytes(16)),
            'from_id'             => AccountModel::FILING_ID,
            'to_id'               => AccountModel::POLISH_ID,
            'process_waves_id'    => $id1,
            'process_waves_ids'   => "$id1,$id2",
            'mapped_cust_id'      => 1,
            'gross_weight'        => 95.000,
            'purity'              => 92.00,
            'recent_gross_weight' => 95.000,
        ]);

        $json = json_decode($res->getJSON(), true);
        $this->assertEquals('false', $json['status']);
        $this->assertStringContainsString('already been transferred', $json['message']);

        // Verify batch 1 was NOT modified (atomic rollback maintained)
        $check1 = $processWavesModel->find($id1);
        $this->assertEquals('0', (string) $check1['inner_transfer']);
        $this->assertNull($check1['further_to_process_waves_id']);

        // Clean up
        $processWavesModel->delete($id1);
        $processWavesModel->delete($id2);
    }

    public function testConsolidateLossBatchesPreventsDuplicateTransfer(): void
    {
        $processWavesModel = new ProcessWavesModel();

        // Create 2 source loss batches
        $loss1 = $processWavesModel->insertProcessWavesData([
            'private_key'          => md5(uniqid('loss1_', true)),
            'cust_id'              => null,
            'from_id'              => AccountModel::CASTING_ID,
            'to_id'                => AccountModel::NET_LOSS_ID,
            'loss_account_id'      => AccountModel::CASTING_ID,
            'mapped_cust_id'       => null,
            'product'              => 'MT',
            'gross_weight'         => 1.250,
            'purity'               => 100.00,
            'inner_transfer'       => '0',
            'stock_inner_transfer' => '0',
            'date'                 => date('Y-m-d H:i:s'),
            'user_by'              => 1,
        ]);

        $loss2 = $processWavesModel->insertProcessWavesData([
            'private_key'          => md5(uniqid('loss2_', true)),
            'cust_id'              => null,
            'from_id'              => AccountModel::CASTING_ID,
            'to_id'                => AccountModel::NET_LOSS_ID,
            'loss_account_id'      => AccountModel::CASTING_ID,
            'mapped_cust_id'       => null,
            'product'              => 'MT',
            'gross_weight'         => 0.750,
            'purity'               => 100.00,
            'inner_transfer'       => '0',
            'stock_inner_transfer' => '0',
            'date'                 => date('Y-m-d H:i:s'),
            'user_by'              => 1,
        ]);

        // 1. First consolidation -> succeeds
        $res1 = $processWavesModel->consolidateLossBatches(
            AccountModel::NET_LOSS_ID,
            AccountModel::REFINING_ID,
            AccountModel::CASTING_ID,
            [$loss1, $loss2],
            2.000,
            100.00,
            1,
            'Test loss consolidation'
        );

        $this->assertEquals('true', $res1['status']);
        $consolidatedId = (int) $res1['process_waves_id'];
        $this->assertGreaterThan(0, $consolidatedId);

        // 2. Second consolidation of the same batches -> rejected
        $res2 = $processWavesModel->consolidateLossBatches(
            AccountModel::NET_LOSS_ID,
            AccountModel::REFINING_ID,
            AccountModel::CASTING_ID,
            [$loss1, $loss2],
            2.000,
            100.00,
            1,
            'Duplicate loss consolidation attempt'
        );

        $this->assertEquals('false', $res2['status']);
        $this->assertStringContainsString('already been transferred', $res2['message']);

        // Clean up
        $processWavesModel->delete($consolidatedId);
        $processWavesModel->delete($loss1);
        $processWavesModel->delete($loss2);
    }
}

