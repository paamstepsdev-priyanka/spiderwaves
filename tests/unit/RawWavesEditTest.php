<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\RawWavesModel;
use App\Models\CustomerMappingModel;

final class RawWavesEditTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $DBGroup = 'default';

    protected function setUp(): void
    {
        parent::setUp();
        \Config\Database::connect('default');
    }

    public function testEditRawWavePreselectsMappedCustomer(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 14,
                'user'         => 'superadmin',
                'access_level' => 'superadmin',
                'location'     => 'ug',
                'private_key'  => 'superadmin_test_key',
            ],
        ];

        $rawWavesModel = new RawWavesModel();
        $mappingModel  = new CustomerMappingModel();

        // 1. Create a test mapping record
        $testMapping = $mappingModel->where('mapped_cust_status', '1')->first();
        $this->assertNotNull($testMapping, 'Must have at least one active customer mapping.');
        $mappedCustId = (int) $testMapping['mapped_cust_id'];

        // 2. Create a test unapproved raw wave with to_id set to $mappedCustId
        $testKey = md5(uniqid('raw_edit_test_', true));
        $testWave = [
            'private_key'        => $testKey,
            'from_id'            => null,
            'to_id'              => $mappedCustId,
            'verification_code'  => '99881',
            'user_by'            => 14,
            'cust_id'            => (int) $testMapping['cust_id'],
            'delivery_by'        => 'superadmin',
            'date'               => date('Y-m-d'),
            'time'               => date('h:i A'),
            'transactional_mode' => 'OR',
            'transactional_type' => 'receipt',
            'packet_weight'      => 0.000,
            'net_weight'         => 100.000,
            'packet_weight'      => 0.000,
            'amount'             => 0,
            'gross_weight'       => 100.000,
            'less_weight'        => 0.000,
            'purity'             => (float) $testMapping['purity'],
            'wastage'            => 0.000,
            'collection_id'      => (int) $testMapping['collection_id'],
            'amount'             => 0,
            'remark'             => 'Test Edit Mapped Cust',
            'approved_data'      => '0',
            'is_deleted'         => '0',
            'created_at'         => date('Y-m-d H:i:s'),
        ];

        $rawWavesModel->insert($testWave);

        try {
            // Test 1: GET edit page with to_id set
            $res = $this->withSession($session)->get('raw-waves/edit/' . $testKey);
            $res->assertOK();
            $body = $res->getBody();

            $expectedOption = 'value="' . $mappedCustId . '" selected';
            $this->assertStringContainsString(
                $expectedOption,
                $body,
                "Edit page must mark option value={$mappedCustId} as selected."
            );

            // Test 2: Fallback when to_id is null/0 but cust_id, collection_id, purity match
            $rawWavesModel->updateInOutData(['to_id' => null], ['private_key' => $testKey]);
            $resFallback = $this->withSession($session)->get('raw-waves/edit/' . $testKey);
            $resFallback->assertOK();
            $fallbackBody = $resFallback->getBody();

            $this->assertStringContainsString(
                $expectedOption,
                $fallbackBody,
                "Edit page must resolve and select mapped customer via cust_id/collection_id/purity fallback."
            );

            // Test 3: Updating via insert_in_out with form_type = Edit
            $postData = [
                'form_type'          => 'Edit',
                'private_key'        => $testKey,
                'mapped_cust_id'     => $mappedCustId,
                'transactional_mode' => 'OR',
                'transactional_type' => 'receipt',
                'weight'             => '100.000',
                'less_weight'        => '0.000',
                'net_weight'         => '100.000',
                'purity'             => (string) $testMapping['purity'],
                'wastage'            => '0.000',
                'delivery_by'        => 'superadmin',
                'remark'             => 'Updated Remark Test',
            ];

            $postRes = $this->withSession($session)->post('raw-waves/store', $postData);
            $postRes->assertOK();
            $json = json_decode($postRes->getJSON(), true);
            $this->assertSame('true', $json['result'] ?? '', 'Response error: ' . ($json['message'] ?? $postRes->getJSON()));

            // Verify updated record in DB
            $updatedWave = $rawWavesModel->where('private_key', $testKey)->first();
            $this->assertSame((int) $mappedCustId, (int) $updatedWave['to_id']);
            $this->assertSame('Updated Remark Test', $updatedWave['remark']);

        } finally {
            // Clean up test wave
            $rawWavesModel->where('private_key', $testKey)->delete();
        }
    }

    public function testEditSpecificRawWaveRecordFromUserScreenshot(): void
    {
        $session = [
            'spider' => [
                'user_id'      => 14,
                'user'         => 'superadmin',
                'access_level' => 'superadmin',
                'location'     => 'ug',
                'private_key'  => 'superadmin_test_key',
            ],
        ];

        $rawWavesModel = new RawWavesModel();
        $testKey = 'test_edit_screenshot_' . uniqid();

        // Ensure an active unapproved test record exists with mapped customer 2377
        $testWaveId = $rawWavesModel->insert([
            'private_key'        => $testKey,
            'verification_code'  => '99882',
            'user_by'            => 14,
            'to_id'              => 2377,
            'cust_id'            => 171,
            'collection_id'      => 4,
            'transactional_mode' => 'OR',
            'transactional_type' => 'receipt',
            'packet_weight'      => 0.000,
            'amount'             => 0,
            'gross_weight'       => 100.000,
            'less_weight'        => 0.000,
            'net_weight'         => 100.000,
            'purity'             => 75.00,
            'wastage'            => 0.000,
            'fine'               => 75.000,
            'delivery_by'        => 'superadmin',
            'date'               => date('Y-m-d'),
            'time'               => date('h:i A'),
            'remark'             => 'Test Remark',
            'approved_data'      => '0',
            'is_deleted'         => '0',
            'entry_date'         => date('Y-m-d H:i:s'),
            'created_at'         => date('Y-m-d H:i:s'),
        ]);

        try {
            $res = $this->withSession($session)->get('raw-waves/edit/' . $testKey);
            $res->assertOK();
            $body = $res->getBody();

            // Must pre-select mapped customer 2377 (KHIMRAJ MORE 75)
            $this->assertStringContainsString('value="2377" selected', $body);
            $this->assertStringContainsString('KHIMRAJ MORE 75', $body);
            $this->assertStringContainsString('KHIMRAJ MORE', $body);
            $this->assertStringContainsString('Premium', $body);
        } finally {
            $rawWavesModel->where('private_key', $testKey)->delete();
        }
    }
}
