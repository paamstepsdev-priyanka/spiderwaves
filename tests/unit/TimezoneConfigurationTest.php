<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\RawWavesModel;

final class TimezoneConfigurationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $DBGroup = 'default';

    protected function setUp(): void
    {
        parent::setUp();
        \Config\Database::connect('default');
    }

    public function testGlobalTimezoneIsConfiguredToAsiaKolkata(): void
    {
        // 1. App configuration
        $config = config(\Config\App::class);
        $this->assertSame('Asia/Kolkata', $config->appTimezone);
        $this->assertSame('Asia/Kolkata', app_timezone());

        // 2. PHP runtime timezone
        $this->assertSame('Asia/Kolkata', date_default_timezone_get());

        // 3. MySQL session timezone and now matches PHP date within 2 seconds
        $db = \Config\Database::connect('default');
        $row = $db->query("SELECT NOW() as db_now")->getRowArray();
        $this->assertNotEmpty($row['db_now']);

        $phpNow = date('Y-m-d H:i:s');
        $dbTime = strtotime($row['db_now']);
        $phpTime = strtotime($phpNow);

        $diff = abs($dbTime - $phpTime);
        $this->assertLessThanOrEqual(2, $diff, "MySQL NOW() and PHP date() must be aligned in IST (+05:30)");
    }

    public function testControllerWebRequestsEnforceAsiaKolkataTimezone(): void
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

        // Access dashboard
        $res = $this->withSession($session)->get('dashboard');
        $res->assertOK();

        // Verify runtime timezone remained Asia/Kolkata after request execution
        $this->assertSame('Asia/Kolkata', date_default_timezone_get());
        $this->assertSame('Asia/Kolkata', app_timezone());
    }

    public function testRawWaveStoreSavesCurrentIndianStandardTime(): void
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
        $expectedDate = date('Y-m-d');
        $expectedTimePrefix = date('h:i'); // e.g. "04:30"
        $expectedAmPm = date('A');

        $postData = [
            'form_type'          => 'Add',
            'mapped_cust_id'     => 2377, // KHIMRAJ MORE 75
            'transactional_mode' => 'OR',
            'transactional_type' => 'receipt',
            'weight'             => '50.000',
            'less_weight'        => '0.000',
            'net_weight'         => '50.000',
            'purity'             => '75.000',
            'wastage'            => '0.000',
            'delivery_by'        => 'superadmin',
            'remark'             => 'Timezone IST Test',
        ];

        $res = $this->withSession($session)->post('raw-waves/store', $postData);
        $res->assertOK();

        $json = json_decode($res->getJSON(), true);
        $this->assertSame('true', $json['result'] ?? '');
        $wavesId = (int) ($json['waves_id'] ?? 0);
        $this->assertGreaterThan(0, $wavesId);

        try {
            $inserted = $rawWavesModel->find($wavesId);
            $this->assertNotEmpty($inserted);
            $this->assertSame($expectedDate, $inserted['date']);
            $this->assertStringStartsWith($expectedTimePrefix, $inserted['time']);
            $this->assertStringEndsWith($expectedAmPm, $inserted['time']);

            // Verify created_at is within 5 seconds of current IST
            $createdAtTime = strtotime($inserted['created_at']);
            $diff = abs(time() - $createdAtTime);
            $this->assertLessThanOrEqual(5, $diff, "created_at must be recorded in Indian Standard Time");
        } finally {
            $rawWavesModel->delete($wavesId);
        }
    }
}

