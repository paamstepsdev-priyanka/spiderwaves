<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\AccountModel;
use App\Models\ProcessWavesModel;
use App\Models\RawWavesModel;
use App\Models\CustomerModel;
use App\Models\CustomerMappingModel;

final class LockerCustomerTransferTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $DBGroup = 'default';

    protected function setUp(): void
    {
        parent::setUp();
        \Config\Database::connect('default');
    }

    public function testLockerCustomerTransferWorkflow(): void
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

        // 1. Unauthenticated / naked raw-waves access for locker must redirect to locker
        $res = $this->withSession($session)->get('raw-waves');
        $res->assertRedirectTo(base_url('locker'));

        $res = $this->withSession($session)->get('raw-waves/manage');
        $res->assertRedirectTo(base_url('locker'));

        // 2. Setup a dummy batch in Locker Receipt (process_waves)
        $processWavesModel = new ProcessWavesModel();
        $batchKey = md5(uniqid('batch_', true));

        $testBatch = [
            'private_key'          => $batchKey,
            'cust_id'              => 1,
            'from_id'              => AccountModel::CASTING_ID,
            'to_id'                => AccountModel::STOCK_ID, // 1 (Locker)
            'mapped_cust_id'       => 1,
            'product'              => 'OR',
            'gross_weight'         => 717.200,
            'purity'               => 92.00,
            'inner_transfer'       => '0',
            'stock_inner_transfer' => '0',
            'date'                 => date('Y-m-d H:i:s'),
            'remark'               => 'Test batch for customer transfer',
            'user_by'              => 2,
        ];
        $sourceBatchId = $processWavesModel->insertProcessWavesData($testBatch);
        $this->assertNotEmpty($sourceBatchId);

        // 3. Access raw-waves/index/{batchKey} as locker user
        $res = $this->withSession($session)->get('raw-waves/index/' . $batchKey);
        $res->assertStatus(200);
        $res->assertSee('717.200');
        $res->assertSee('Firm');
        $res->assertSee('PW');
        $res->assertSee('ITEM 1');

        // 4. Submit Customer Issue form (transfer_to_cust)
        $postData = [
            'form_type'                 => 'transfer_to_cust',
            'process_waves_private_key' => $batchKey,
            'mapped_cust_id'            => 1,
            'cust_id'                   => 1,
            'firm_id'                   => 1,
            'gross_weight'              => 717.200,
            'net_weight'                => 717.200,
            'purity'                    => 92.00,
            'packet_weight'             => 0.000,
            'delivery_by'               => 'Locker Transfer',
            'remark'                    => 'Transfer to Customer',
            'transactional_mode'        => 'OR',
            'transactional_type'        => 'issue',
            'key1'                      => 'RING',
            'val1'                      => '5',
        ];

        $res = $this->withSession($session)->post('raw-waves/store', $postData);
        $res->assertStatus(200);
        $json = json_decode($res->getJSON(), true);
        $this->assertSame('true', $json['result'] ?? '', 'Error msg: ' . ($json['message'] ?? 'none'));
        $this->assertSame('transfer_to_cust', $json['form_type'] ?? '');
        $this->assertNotEmpty($json['waves_id'] ?? 0);

        // 5. Verify source batch has inner_transfer = '1'
        $updatedSource = $processWavesModel->find($sourceBatchId);
        $this->assertSame('1', (string) $updatedSource['inner_transfer']);

        // 6. Verify outward issue in process_waves
        $issueWave = $processWavesModel->where('from_id', AccountModel::STOCK_ID)
            ->where('to_id', null)
            ->where('gross_weight', 717.200)
            ->where('purity', 92.00)
            ->orderBy('process_waves_id', 'DESC')
            ->first();

        $this->assertNotNull($issueWave);
        $this->assertSame('1', (string) $issueWave['inner_transfer']);
        $this->assertSame(1, (int) $issueWave['mapped_cust_id']);

        // 7. Verify print transaction slip as locker user
        $printRes = $this->withSession($session)->get('process-waves/print-transaction/' . $issueWave['process_waves_id']);
        $printRes->assertStatus(200);
        $printRes->assertDontSee('Unauthorized transaction slip for Locker.');
        $printRes->assertSee('717.200');

        // Clean up test records
        $processWavesModel->delete($sourceBatchId);
        $processWavesModel->delete($issueWave['process_waves_id']);
        if (!empty($json['waves_id'])) {
            (new RawWavesModel())->delete($json['waves_id']);
        }
    }

    public function testLockerPageShowsOnlyCheckStockButton(): void
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

        $res = $this->withSession($session)->get('locker');
        $res->assertStatus(200);
        $res->assertSee('Check Stock');
        $this->assertStringContainsString('id="btnCheckStock"', $res->getBody());

        // Check stock endpoint
        $checkStockRes = $this->withSession($session)->post('locker/check-stock');
        $checkStockRes->assertStatus(200);
        $data = json_decode($checkStockRes->getJSON(), true);
        $this->assertArrayHasKey('status', $data);
    }

    public function testLockerBatchActionRocketIconHiddenWhenIssued(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 1,
                'user'         => 'superadmin',
                'access_level' => 'superadmin',
                'location'     => 'ug',
                'private_key'  => '7cedbbec7b110ef9ae30ed9760ecf8bb',
            ],
        ];

        $processWavesModel = new ProcessWavesModel();

        // 1. Unissued batch in Locker (inner_transfer = '0')
        $unissuedId = $processWavesModel->insertProcessWavesData([
            'private_key'          => md5(uniqid('unissued_', true)),
            'cust_id'              => 1,
            'from_id'              => AccountModel::CASTING_ID,
            'to_id'                => AccountModel::STOCK_ID,
            'mapped_cust_id'       => 1,
            'product'              => 'MT',
            'gross_weight'         => 99.111,
            'purity'               => 92.00,
            'inner_transfer'       => '0',
            'stock_inner_transfer' => '0',
            'date'                 => date('Y-m-d H:i:s'),
            'user_by'              => 1,
        ]);

        // 2. Issued batch in Locker (inner_transfer = '1', e.g. issued to customer)
        $issuedId = $processWavesModel->insertProcessWavesData([
            'private_key'          => md5(uniqid('issued_', true)),
            'cust_id'              => 1,
            'from_id'              => AccountModel::CASTING_ID,
            'to_id'                => AccountModel::STOCK_ID,
            'mapped_cust_id'       => 1,
            'product'              => 'MT',
            'gross_weight'         => 88.222,
            'purity'               => 92.00,
            'inner_transfer'       => '1',
            'stock_inner_transfer' => '0',
            'date'                 => date('Y-m-d H:i:s'),
            'user_by'              => 1,
        ]);

        $res = $this->withSession($session)->get('locker');
        $res->assertStatus(200);
        $body = $res->getBody();

        // The unissued batch row must have openTransferModal
        $this->assertStringContainsString("pw_row_{$unissuedId}", $body);
        $this->assertStringContainsString("openTransferModal(1, {$unissuedId})", $body);

        // The issued batch row must NOT have openTransferModal
        $this->assertStringContainsString("pw_row_{$issuedId}", $body);
        $this->assertStringNotContainsString("openTransferModal(1, {$issuedId})", $body);

        // Clean up
        $processWavesModel->delete($unissuedId);
        $processWavesModel->delete($issuedId);
    }
}
