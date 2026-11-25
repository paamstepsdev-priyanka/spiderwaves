<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\UccSyncService;
use App\Services\UccClient;
use App\Models\UccWebhookLogModel;

class UccSyncCommand extends BaseCommand
{
    protected $group       = 'UCC';
    protected $name        = 'ucc:sync';
    protected $description = 'Synchronize UCC master tables (customers, mapped_customers, wastages, purities, collections)';
    protected $usage       = 'ucc:sync [options]';
    protected $options     = [
        '--full'        => 'Force a full snapshot synchronization across all entities.',
        '--webhook-log' => 'Optional ucc_webhook_log ID to update with sync result.',
    ];

    public function run(array $params)
    {
        CLI::write("=== SpiderWaves UCC Master Data Synchronization ===", 'yellow');

        $webhookLogId = (int)(CLI::getOption('webhook-log') ?: ($params['webhook-log'] ?? 0));
        $webhookLogModel = $webhookLogId > 0 ? new UccWebhookLogModel() : null;

        $client = new UccClient();
        if (!$client->isConfigured()) {
            $err = 'UCC API credentials are not configured in .env.';
            if ($webhookLogModel) {
                $webhookLogModel->markResult($webhookLogId, 'failed', $err);
            }
            CLI::error("ERROR: " . $err);
            CLI::write("Please define UCC_API_BASE_URL, UCC_BEARER_TOKEN, and UCC_HMAC_SECRET before running sync.", 'red');
            return 1;
        }

        $forceFull = array_key_exists('full', $params) || CLI::getOption('full');
        $syncService = new UccSyncService($client);

        try {
            $logger = function (string $msg) {
                CLI::write("  " . $msg, 'light_gray');
            };

            $result = $syncService->sync($forceFull, $logger);

            if ($webhookLogModel) {
                if (($result['status'] ?? '') === 'locked') {
                    $webhookLogModel->markResult($webhookLogId, 'success', 'Concurrent sync already active; coalesced.');
                } else {
                    $webhookLogModel->markResult($webhookLogId, 'success', null);
                }
            }

            CLI::write("\nSync Result: " . json_encode($result, JSON_PRETTY_PRINT), 'green');
            CLI::write("=== UCC Sync Completed Successfully ===", 'green');
            return 0;
        } catch (\Throwable $e) {
            if ($webhookLogModel) {
                $webhookLogModel->markResult($webhookLogId, 'failed', $e->getMessage());
            }
            log_message('error', 'UCC SyncCommand failed: ' . $e->getMessage());
            CLI::error("Sync failed: " . $e->getMessage());
            return 1;
        }
    }
}
