<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\CustomerModel;
use App\Models\CustomerMappingModel;
use App\Models\CollectionModel;
use App\Models\RemarkModel;
use App\Models\ProcessWavesModel;
use App\Models\AccountModel;

final class DescendingOrderCreateModulesTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $DBGroup = 'default';

    protected function setUp(): void
    {
        parent::setUp();
        \Config\Database::connect('default');
    }

    public function testCustomerNewlySavedAppearsFirst(): void
    {
        $customerModel = new CustomerModel();
        $testName = 'ZZZ Newly Saved Customer ' . uniqid();
        $privateKey = md5(uniqid('cust_', true));

        $insertedId = $customerModel->insert([
            'private_key'        => $privateKey,
            'cust_name'          => $testName,
            'type'               => 'cust',
            'address_1'          => '',
            'address_2'          => '',
            'address_3'          => '',
            'MT_closing_balance' => 0.000,
            'OR_closing_balance' => 0.000,
            'RK_closing_balance' => 0.000,
            'mobile_no'          => '9876543210',
            'send_sms'           => '1',
            'is_petty_cash'      => '1',
            'cust_status'        => '1',
        ], true);

        try {
            $records = $customerModel->getPaginatedCustomers(10);
            $this->assertNotEmpty($records);
            $this->assertSame((int) $insertedId, (int) $records[0]['cust_id'], 'Newly created customer must be the first record (top of list)');
        } finally {
            $customerModel->delete($insertedId);
        }
    }

    public function testCustomerMappingNewlySavedAppearsFirst(): void
    {
        $mappingModel = new CustomerMappingModel();
        $testName = 'ZZZ Mapped Customer ' . uniqid();
        $privateKey = md5(uniqid('mapping_', true));

        $insertedId = $mappingModel->insert([
            'private_key'        => $privateKey,
            'mapped_cust_name'   => $testName,
            'cust_id'            => 1,
            'purity'             => 92.00,
            'collection_id'      => 1,
            'mapped_cust_status' => '1',
        ], true);

        try {
            $records = $mappingModel->getPaginatedMappings(10);
            $this->assertNotEmpty($records);
            $this->assertSame((int) $insertedId, (int) $records[0]['mapped_cust_id'], 'Newly created mapping must be the first record (top of list)');
        } finally {
            $mappingModel->delete($insertedId);
        }
    }

    public function testCollectionNewlySavedAppearsFirst(): void
    {
        $collectionModel = new CollectionModel();
        $testName = 'ZZZ Collection ' . uniqid();
        $privateKey = md5(uniqid('coll_', true));

        $insertedId = $collectionModel->insert([
            'private_key'       => $privateKey,
            'collection_name'   => $testName,
            'collection_status' => '1',
        ], true);

        try {
            $records = $collectionModel->getPaginatedCollections(10);
            $this->assertNotEmpty($records);
            $this->assertSame((int) $insertedId, (int) $records[0]['collection_id'], 'Newly created collection must be the first record (top of list)');
        } finally {
            $collectionModel->delete($insertedId);
        }
    }

    public function testRemarkNewlySavedAppearsFirst(): void
    {
        $remarkModel = new RemarkModel();
        $testText = 'ZZZ Remark ' . uniqid();

        $insertedId = $remarkModel->insert([
            'remark' => $testText,
            'status' => '1',
        ], true);

        try {
            $records = $remarkModel->getPaginatedRemarks(10);
            $this->assertNotEmpty($records);
            $this->assertSame((int) $insertedId, (int) $records[0]['remark_id'], 'Newly created remark must be the first record (top of list)');
        } finally {
            $remarkModel->delete($insertedId);
        }
    }

    public function testProcessWavesNewlySavedAppearsFirst(): void
    {
        $pwModel = new ProcessWavesModel();
        $batchKey = md5(uniqid('pw_test_', true));

        $testId = $pwModel->insert([
            'private_key'          => $batchKey,
            'cust_id'              => 1,
            'from_id'              => AccountModel::CASTING_ID,
            'to_id'                => AccountModel::FILING_ID,
            'mapped_cust_id'       => 1,
            'product'              => 'OR',
            'gross_weight'         => 125.500,
            'purity'               => 92.00,
            'inner_transfer'       => '0',
            'stock_inner_transfer' => '0',
            'user_by'              => 14,
            'date'                 => date('Y-m-d H:i:s'),
            'remark'               => 'Test Descending Order',
        ], true);

        try {
            $receiptData = $pwModel->getDepartmentReceiptData(AccountModel::FILING_ID, date('Y-m-d 00:00:00'), null, 10);
            $this->assertNotEmpty($receiptData);
            $this->assertSame((int) $testId, (int) $receiptData[0]['process_waves_id'], 'Newly created transfer batch must be first record (top of list)');
        } finally {
            $pwModel->delete($testId);
        }
    }
}
