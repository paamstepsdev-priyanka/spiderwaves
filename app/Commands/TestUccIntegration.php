<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\UccHasher;
use App\Services\UccClient;
use App\Services\UccSyncService;
use App\Services\UccRevalidateService;
use App\Models\UccCustomerModel;
use App\Models\UccMappedCustomerModel;
use App\Models\UccWastageModel;
use App\Models\UccPurityModel;
use App\Models\UccCollectionModel;
use App\Models\UccSyncStateModel;
use App\Models\UccSyncConflictModel;

class TestUccIntegration extends BaseCommand
{
    protected $group       = 'UCC';
    protected $name        = 'ucc:test';
    protected $description = 'Execute full test vector verification for UCC Master Sync';

    public function run(array $params)
    {
        CLI::write("====================================================", 'yellow');
        CLI::write("  UCC MASTER DATA SYNCHRONIZATION TEST SUITE       ", 'yellow');
        CLI::write("====================================================\n", 'yellow');

        $testVectorPath = file_exists('c:/Users/Administrator.IMAGE-WIN2019/Downloads/spider_dev (1)/spider_dev/test_vectors.json')
            ? 'c:/Users/Administrator.IMAGE-WIN2019/Downloads/spider_dev (1)/spider_dev/test_vectors.json'
            : 'c:/Users/Administrator.IMAGE-WIN2019/Downloads/spider_dev/spider_dev/test_vectors.json';
        if (!file_exists($testVectorPath)) {
            CLI::error("Test vector file not found at: " . $testVectorPath);
            return 1;
        }

        $vectors = json_decode(file_get_contents($testVectorPath), true);
        $passed = 0;
        $failed = 0;

        // ---------------------------------------------------------------------
        // TEST 1: Request Signing Test Vectors
        // ---------------------------------------------------------------------
        CLI::write("--- 1. Testing Request Signing Vectors ---", 'cyan');
        $secret = $vectors['signing_secret'];

        foreach ($vectors['signing'] as $i => $tv) {
            $computedSignature = UccHasher::signRequest(
                $tv['method'],
                $tv['path'],
                $tv['timestamp'],
                $tv['nonce'],
                $tv['body'],
                $secret
            );

            $bodySha = hash('sha256', $tv['body']);
            if ($bodySha !== $tv['body_sha256']) {
                CLI::error("  [FAIL] Signing case #{$i} Body SHA256 mismatch!");
                $failed++;
                continue;
            }

            if (hash_equals($tv['expected_signature'], $computedSignature)) {
                CLI::write("  [PASS] Case #{$i} ({$tv['method']} {$tv['path']}) Signature matches exactly: {$computedSignature}", 'green');
                $passed++;
            } else {
                CLI::error("  [FAIL] Case #{$i} Signature mismatch! Expected: {$tv['expected_signature']}, Got: {$computedSignature}");
                $failed++;
            }

            // Verify signature verification method
            $verified = UccHasher::verifySignature(
                $tv['method'],
                $tv['path'],
                $tv['timestamp'],
                $tv['nonce'],
                $tv['body'],
                $tv['expected_signature'],
                $secret,
                3000000000 // Large tolerance for historical test timestamp
            );
            if ($verified) {
                CLI::write("  [PASS] Case #{$i} verifySignature() correctly validated.", 'green');
                $passed++;
            } else {
                CLI::error("  [FAIL] Case #{$i} verifySignature() failed to validate.");
                $failed++;
            }
        }

        // ---------------------------------------------------------------------
        // TEST 2: Canonical JSON & row_hash Test Vectors
        // ---------------------------------------------------------------------
        CLI::write("\n--- 2. Testing Canonical JSON & row_hash Vectors ---", 'cyan');
        foreach ($vectors['row_hash'] as $i => $rh) {
            $computedJson = UccHasher::toCanonicalJson($rh['entity'], $rh['row']);
            $computedHash = UccHasher::computeRowHash($rh['entity'], $rh['row']);

            if ($computedJson === $rh['canonical_json']) {
                CLI::write("  [PASS] Case #{$i} ({$rh['entity']}) Canonical JSON matches exact string representation.", 'green');
                $passed++;
            } else {
                CLI::error("  [FAIL] Case #{$i} JSON mismatch!\n    Expected: {$rh['canonical_json']}\n    Got:      {$computedJson}");
                $failed++;
            }

            if (hash_equals($rh['expected_row_hash'], $computedHash)) {
                CLI::write("  [PASS] Case #{$i} ({$rh['entity']}) row_hash matches: {$computedHash}", 'green');
                $passed++;
            } else {
                CLI::error("  [FAIL] Case #{$i} row_hash mismatch!\n    Expected: {$rh['expected_row_hash']}\n    Got:      {$computedHash}");
                $failed++;
            }
        }

        // ---------------------------------------------------------------------
        // TEST 3: Manifest Checksum Test Vectors
        // ---------------------------------------------------------------------
        CLI::write("\n--- 3. Testing Manifest Checksum Vectors ---", 'cyan');
        foreach ($vectors['manifest_checksum'] as $i => $mc) {
            $expectedChecksum = $mc['expected_checksum'];
            $computedChecksum = hash('sha256', $mc['lines']);

            if (hash_equals($expectedChecksum, $computedChecksum)) {
                CLI::write("  [PASS] Case #{$i} ({$mc['entity']}, {$mc['count']} items) Checksum matches: {$computedChecksum}", 'green');
                $passed++;
            } else {
                CLI::error("  [FAIL] Case #{$i} Checksum mismatch! Expected: {$expectedChecksum}, Got: {$computedChecksum}");
                $failed++;
            }
        }

        // ---------------------------------------------------------------------
        // TEST 3b: Master Version Test Vector
        // ---------------------------------------------------------------------
        CLI::write("\n--- 3b. Testing Master Version Vector ---", 'cyan');
        if (isset($vectors['master_version'])) {
            $mv = is_array($vectors['master_version']) && isset($vectors['master_version'][0]) ? $vectors['master_version'][0] : $vectors['master_version'];
            $computedMv = UccHasher::computeMasterVersion($mv['checksums']);
            if (hash_equals($mv['expected_master_version'], $computedMv)) {
                CLI::write("  [PASS] Master Version matches expected token: {$computedMv}", 'green');
                $passed++;
            } else {
                CLI::error("  [FAIL] Master Version mismatch! Expected: {$mv['expected_master_version']}, Got: {$computedMv}");
                $failed++;
            }
        }

        // ---------------------------------------------------------------------
        // TEST 3c: Webhook Receiver Test Vectors & Security Drills
        // ---------------------------------------------------------------------
        CLI::write("\n--- 3c. Testing Webhook Verification Vectors & Drills ---", 'cyan');
        
        // Nonce store simulator
        $seenNonces = [];
        $testRememberNonce = function (string $nonce) use (&$seenNonces): bool {
            if (isset($seenNonces[$nonce])) {
                return false;
            }
            $seenNonces[$nonce] = true;
            return true;
        };

        // 1. Vector: Normal webhook notification
        if (isset($vectors['webhook'])) {
            $wh = $vectors['webhook'];
            $headers = [
                'Authorization' => 'Bearer ' . $wh['bearer'],
                'X-Timestamp'   => $wh['timestamp'],
                'X-Nonce'       => $wh['nonce'],
                'X-Signature'   => $wh['expected_signature'],
            ];

            [$status, $body] = UccHasher::verifyWebhook(
                $headers,
                $wh['method'],
                $wh['path'],
                $wh['body'],
                $wh['bearer'],
                $wh['secret'],
                $testRememberNonce,
                (int)$wh['timestamp']
            );

            if ($status === 200 && $body['status'] === 'ok' && $body['is_test'] === false) {
                CLI::write("  [PASS] Webhook notification vector verified successfully (HTTP 200, is_test: false).", 'green');
                $passed++;
            } else {
                CLI::error("  [FAIL] Webhook notification failed! Status: {$status}");
                $failed++;
            }

            // Drill: Replay of same nonce must fail with 401
            [$replayStatus] = UccHasher::verifyWebhook(
                $headers,
                $wh['method'],
                $wh['path'],
                $wh['body'],
                $wh['bearer'],
                $wh['secret'],
                $testRememberNonce,
                (int)$wh['timestamp']
            );
            if ($replayStatus === 401) {
                CLI::write("  [PASS] Replay attack correctly rejected with HTTP 401 (Nonce already used).", 'green');
                $passed++;
            } else {
                CLI::error("  [FAIL] Replay attack was not rejected! Status: {$replayStatus}");
                $failed++;
            }

            // Drill: Tampered body must fail signature check with 401
            $tamperedHeaders = $headers;
            $tamperedHeaders['X-Nonce'] = 'webhook-nonce-fresh-01';
            [$tamperStatus] = UccHasher::verifyWebhook(
                $tamperedHeaders,
                $wh['method'],
                $wh['path'],
                $wh['body'] . ' ',
                $wh['bearer'],
                $wh['secret'],
                $testRememberNonce,
                (int)$wh['timestamp']
            );
            if ($tamperStatus === 401) {
                CLI::write("  [PASS] Tampered payload correctly rejected with HTTP 401 (Invalid signature).", 'green');
                $passed++;
            } else {
                CLI::error("  [FAIL] Tampered payload was not rejected! Status: {$tamperStatus}");
                $failed++;
            }

            // Drill: Timestamp 301 seconds in the past must fail with 401
            $expiredHeaders = $headers;
            $expiredHeaders['X-Nonce'] = 'webhook-nonce-fresh-02';
            [$expiredStatus] = UccHasher::verifyWebhook(
                $expiredHeaders,
                $wh['method'],
                $wh['path'],
                $wh['body'],
                $wh['bearer'],
                $wh['secret'],
                $testRememberNonce,
                (int)$wh['timestamp'] + 301
            );
            if ($expiredStatus === 401) {
                CLI::write("  [PASS] Expired timestamp (+301s) correctly rejected with HTTP 401.", 'green');
                $passed++;
            } else {
                CLI::error("  [FAIL] Expired timestamp was not rejected! Status: {$expiredStatus}");
                $failed++;
            }
        }

        // 2. Vector: Subfolder test ping (is_test: true)
        if (isset($vectors['webhook_subfolder_test'])) {
            $whSub = $vectors['webhook_subfolder_test'];
            $headersSub = [
                'Authorization' => 'Bearer ' . $whSub['bearer'],
                'X-Timestamp'   => $whSub['timestamp'],
                'X-Nonce'       => $whSub['nonce'],
                'X-Signature'   => $whSub['expected_signature'],
            ];

            [$statusSub, $bodySub] = UccHasher::verifyWebhook(
                $headersSub,
                $whSub['method'],
                $whSub['path'],
                $whSub['body'],
                $whSub['bearer'],
                $whSub['secret'],
                $testRememberNonce,
                (int)$whSub['timestamp']
            );

            if ($statusSub === 200 && $bodySub['status'] === 'ok' && $bodySub['is_test'] === true) {
                CLI::write("  [PASS] Webhook subfolder test ping verified successfully (HTTP 200, is_test: true).", 'green');
                $passed++;
            } else {
                CLI::error("  [FAIL] Webhook subfolder test failed! Status: {$statusSub}");
                $failed++;
            }
        }

        // ---------------------------------------------------------------------
        // TEST 4: Read-Only Model Protection Verification
        // ---------------------------------------------------------------------
        CLI::write("\n--- 4. Testing Read-Only Model Architecture ---", 'cyan');
        $customerModel = new UccCustomerModel();

        // 4a. Direct insert must throw RuntimeException
        $insertBlocked = false;
        try {
            $customerModel->insert([
                'customer_id'   => 999999,
                'private_key'   => 'test-direct-key',
                'customer_name' => 'Illegal Direct Insert',
                'row_hash'      => str_repeat('0', 64),
                'synced_version'=> 1,
                'synced_at'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\RuntimeException $e) {
            $insertBlocked = true;
        }
        if ($insertBlocked) {
            CLI::write("  [PASS] Direct model insert() was properly blocked with Read-Only exception.", 'green');
            $passed++;
        } else {
            CLI::error("  [FAIL] Direct model insert() was NOT blocked!");
            $failed++;
        }

        // 4b. Direct update must throw RuntimeException
        $updateBlocked = false;
        try {
            $customerModel->update(999999, ['customer_name' => 'Illegal Update']);
        } catch (\RuntimeException $e) {
            $updateBlocked = true;
        }
        if ($updateBlocked) {
            CLI::write("  [PASS] Direct model update() was properly blocked with Read-Only exception.", 'green');
            $passed++;
        } else {
            CLI::error("  [FAIL] Direct model update() was NOT blocked!");
            $failed++;
        }

        // 4c. Direct delete must throw RuntimeException
        $deleteBlocked = false;
        try {
            $customerModel->delete(999999);
        } catch (\RuntimeException $e) {
            $deleteBlocked = true;
        }
        if ($deleteBlocked) {
            CLI::write("  [PASS] Direct model delete() was properly blocked with Read-Only exception.", 'green');
            $passed++;
        } else {
            CLI::error("  [FAIL] Direct model delete() was NOT blocked!");
            $failed++;
        }

        // 4d. Write via executeSyncWrite() must succeed
        $syncWriteWorked = false;
        try {
            $customerModel->executeSyncWrite(function ($m) {
                // Test upsert of test vector customer 1
                $testRow = [
                    'customer_id'        => 888801,
                    'private_key'        => 'test-sync-key-888801',
                    'customer_name'      => 'Sync Write Test Customer',
                    'is_active'          => 1,
                    'row_hash'           => str_repeat('a', 64),
                    'synced_at'          => date('Y-m-d H:i:s'),
                ];
                $m->insert($testRow, false);
            });
            $syncWriteWorked = true;
        } catch (\Throwable $e) {
            CLI::error("Sync write failed: " . $e->getMessage());
        }

        if ($syncWriteWorked) {
            CLI::write("  [PASS] Authorized sync write via executeSyncWrite() succeeded.", 'green');
            $passed++;

            // Clean up test row
            $customerModel->executeSyncWrite(function ($m) {
                $m->delete(888801, true);
            });
        } else {
            CLI::error("  [FAIL] Authorized sync write failed!");
            $failed++;
        }

        // ---------------------------------------------------------------------
        // TEST 5: Unconfigured Client Safety Verification
        // ---------------------------------------------------------------------
        CLI::write("\n--- 5. Testing Unconfigured Client Safety ---", 'cyan');
        $unconfiguredClient = new UccClient('', '', '');
        if (!$unconfiguredClient->isConfigured()) {
            CLI::write("  [PASS] isConfigured() correctly returns false when credentials are empty.", 'green');
            $passed++;
        } else {
            CLI::error("  [FAIL] isConfigured() returned true for empty credentials!");
            $failed++;
        }

        $notConfiguredExceptionThrown = false;
        try {
            $unconfiguredClient->getHealth();
        } catch (\App\Exceptions\UccNotConfiguredException $e) {
            $notConfiguredExceptionThrown = true;
        }
        if ($notConfiguredExceptionThrown) {
            CLI::write("  [PASS] API call correctly threw UccNotConfiguredException without dispatching fake requests.", 'green');
            $passed++;
        } else {
            CLI::error("  [FAIL] UccNotConfiguredException was NOT thrown!");
            $failed++;
        }

        // ---------------------------------------------------------------------
        // TEST 6: Revalidate Service Standalone Integrity Check
        // ---------------------------------------------------------------------
        CLI::write("\n--- 6. Testing Revalidate Service Offline Diagnostics ---", 'cyan');
        $revalService = new UccRevalidateService($unconfiguredClient);
        $summary = $revalService->runCheck('Test_Runner');

        if ($summary['handshake']['status'] === 'unconfigured') {
            CLI::write("  [PASS] Revalidation handshake safely reports 'unconfigured' when credentials are unpopulated.", 'green');
            $passed++;
        } else {
            CLI::error("  [FAIL] Revalidation did not report unconfigured status.");
            $failed++;
        }

        // Verify CSV generation
        $csv = $revalService->generateDifferencesCsv($summary);
        if (str_contains($csv, 'Entity') && str_contains($csv, 'Difference Type') && str_contains($csv, 'Private Key')) {
            CLI::write("  [PASS] Differences CSV generated successfully with canonical headers.", 'green');
            $passed++;
        } else {
            CLI::error("  [FAIL] CSV export header validation failed.");
            $failed++;
        }

        // ---------------------------------------------------------------------
        // TEST 7: Database Schema Audit vs Senior Specification
        // ---------------------------------------------------------------------
        CLI::write("\n--- 7. Testing Senior SQL Schema Compliance ---", 'cyan');
        $db = \Config\Database::connect();

        $expectedSchemas = [
            'ucc_customer' => [
                'customer_id', 'private_key', 'customer_name', 'is_active',
                'ucc_created_at', 'ucc_updated_at', 'row_hash',
                'synced_at', 'deleted_at',
            ],
            'ucc_mapped_customer' => [
                'mapped_customer_id', 'private_key', 'mapped_customer_name',
                'customer_id', 'collection_id', 'wastage_id', 'purity_id',
                'stone_type', 'gold_type', 'is_active', 'ucc_created_at',
                'ucc_updated_at', 'row_hash', 'synced_at',
                'deleted_at',
            ],
            'ucc_wastage' => [
                'wastage_id', 'private_key', 'wastage', 'is_active',
                'row_hash', 'synced_at', 'deleted_at',
            ],
            'ucc_purity' => [
                'purity_id', 'private_key', 'purity', 'gold_multiplier', 'is_active',
                'ucc_created_at', 'ucc_updated_at', 'row_hash',
                'synced_at', 'deleted_at',
            ],
            'ucc_collection' => [
                'collection_id', 'private_key', 'parent_id', 'collection_name', 'shortcode',
                'is_active', 'ucc_created_at', 'ucc_updated_at', 'row_hash',
                'synced_at', 'deleted_at',
            ],
            'ucc_sync_state' => [
                'id', 'last_version', 'last_synced_at', 'last_error',
            ],
            'ucc_sync_conflict' => [
                'conflict_id', 'entity', 'ucc_id', 'ucc_private_key', 'local_id',
                'local_private_key', 'reason', 'detected_at', 'resolved_at',
            ],
            'ucc_sync_check_log' => [
                'check_id', 'run_by', 'run_at', 'summary_json',
            ],
        ];

        foreach ($expectedSchemas as $table => $expectedFields) {
            if (!$db->tableExists($table)) {
                CLI::error("  [FAIL] Table '{$table}' does not exist in MariaDB!");
                $failed++;
                continue;
            }

            $actualFields = $db->getFieldNames($table);
            $missing = array_diff($expectedFields, $actualFields);
            $extra   = array_diff($actualFields, $expectedFields);

            if (empty($missing) && empty($extra)) {
                CLI::write("  [PASS] Table '{$table}' has EXACT MATCH (" . count($expectedFields) . " fields, 0 missing, 0 extra).", 'green');
                $passed++;
            } else {
                if (!empty($missing)) {
                    CLI::error("  [FAIL] Table '{$table}' missing columns: " . implode(', ', $missing));
                }
                if (!empty($extra)) {
                    CLI::error("  [FAIL] Table '{$table}' has unexpected extra columns: " . implode(', ', $extra));
                }
                $failed++;
            }
        }

        // Detailed SHOW CREATE TABLE dump for proof
        CLI::write("\n--- 8. Actual MariaDB Table Definitions (SHOW CREATE TABLE) ---", 'cyan');
        foreach (array_keys($expectedSchemas) as $table) {
            $row = $db->query("SHOW CREATE TABLE `{$table}`")->getRowArray();
            CLI::write("\n/* Table: {$table} */", 'yellow');
            CLI::write($row['Create Table'] . ";\n");
        }

        // ---------------------------------------------------------------------
        // Summary
        // ---------------------------------------------------------------------
        CLI::write("\n====================================================", 'yellow');
        CLI::write(sprintf("  TOTAL PASSED: %d  |  FAILED: %d", $passed, $failed), $failed === 0 ? 'green' : 'red');
        CLI::write("====================================================\n", 'yellow');

        return $failed === 0 ? 0 : 1;
    }
}
