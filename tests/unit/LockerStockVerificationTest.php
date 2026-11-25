<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\AccountModel;
use App\Models\ProcessWavesModel;

final class LockerStockVerificationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $DBGroup = 'default';

    protected function setUp(): void
    {
        parent::setUp();
        \Config\Database::connect('default');
    }

    public function testLockerStockVerificationPageAccessAndElements(): void
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

        // 1. Unauthorized role access
        $unauthSession = [
            'spider' => [
                'user_id'      => 99,
                'user'         => 'unauth_user',
                'access_level' => 'user',
                'location'     => 'ug',
            ],
        ];
        $resUnauth = $this->withSession($unauthSession)->get('process_waves/locker_stock_verification');
        $resUnauth->assertRedirectTo(base_url('raw-waves'));

        // 2. Authorized access to process_waves/locker_stock_verification and alias locker-stock-verification
        $res = $this->withSession($session)->get('process_waves/locker_stock_verification');
        $res->assertStatus(200);
        $body = $res->getBody();

        $this->assertStringContainsString('Locker Stock Verification', $body);
        $this->assertStringContainsString('Restart Locker Verification Process', $body);
        $this->assertStringContainsString('Current Stock', $body);
        $this->assertStringContainsString('Scan Barcode for Verification', $body);
        $this->assertStringContainsString('id="barcode_process_waves_id"', $body);
        $this->assertStringContainsString('id="barcode_button"', $body);
        $this->assertStringContainsString('Confirm This Data', $body);
        $this->assertStringContainsString('Fine MT', $body);

        // Test alias route as well
        $resAlias = $this->withSession($session)->get('locker-stock-verification');
        $resAlias->assertStatus(200);
        $this->assertStringContainsString('Locker Stock Verification', $resAlias->getBody());
    }

    public function testMarkLockerStockVerificationDoneAndRestart(): void
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

        $processWavesModel = new ProcessWavesModel();

        // 1. Insert a test batch in Locker waiting for verification
        $batchKey = md5(uniqid('verif_batch_', true));
        $testBatch = [
            'private_key'              => $batchKey,
            'cust_id'                  => 1,
            'from_id'                  => AccountModel::CASTING_ID,
            'to_id'                    => AccountModel::STOCK_ID, // 1 (Locker)
            'mapped_cust_id'           => 1,
            'product'                  => 'MT',
            'gross_weight'             => 123.456,
            'purity'                   => 92.000,
            'inner_transfer'           => '0',
            'stock_inner_transfer'     => '0',
            'locker_stock_check_done'  => '0',
            'date'                     => date('Y-m-d H:i:s'),
            'user_by'                  => 2,
        ];
        $batchId = $processWavesModel->insertProcessWavesData($testBatch);
        $this->assertNotEmpty($batchId);

        try {
            // Verify it is visible in the verification page
            $pageRes = $this->withSession($session)->get('process_waves/locker_stock_verification');
            $pageRes->assertStatus(200);
            $this->assertStringContainsString("id=\"{$batchId}\"", $pageRes->getBody());
            $this->assertStringContainsString('123.456', $pageRes->getBody());

            // 2. Mark done via process_waves/mark_locker_stock_verification_done
            $markRes = $this->withSession($session)->post('process_waves/mark_locker_stock_verification_done', [
                'process_waves_id' => $batchId,
            ]);
            $markRes->assertStatus(200);
            $this->assertSame('true', trim($markRes->response()->getBody()));

            // Verify in DB that locker_stock_check_done = '1'
            $updated = $processWavesModel->getById($batchId);
            $this->assertSame('1', (string) $updated['locker_stock_check_done']);

            // Verify it no longer appears in current unverified stock table
            $pageResAfter = $this->withSession($session)->get('process_waves/locker_stock_verification');
            $this->assertStringNotContainsString("id=\"{$batchId}\"", $pageResAfter->getBody());

            // 3. Test invalid process_waves_id
            $invalidRes = $this->withSession($session)->post('process_waves/mark_locker_stock_verification_done', [
                'process_waves_id' => 999999999,
            ]);
            $this->assertSame('false', trim($invalidRes->response()->getBody()));

            // 4. Test Restart verification process
            $restartRes = $this->withSession($session)->post('process_waves/restart_locker_verification_process');
            $restartRes->assertStatus(200);
            $this->assertSame('true', trim($restartRes->response()->getBody()));

            // Verify in DB that it is reset to '0'
            $resetBatch = $processWavesModel->getById($batchId);
            $this->assertSame('0', (string) $resetBatch['locker_stock_check_done']);

            // Verify it appears again in the verification page
            $pageResRestarted = $this->withSession($session)->get('process_waves/locker_stock_verification');
            $this->assertStringContainsString("id=\"{$batchId}\"", $pageResRestarted->getBody());

        } finally {
            // Cleanup
            $processWavesModel->delete($batchId);
        }
    }
}
