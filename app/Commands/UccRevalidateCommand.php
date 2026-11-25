<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\UccRevalidateService;

class UccRevalidateCommand extends BaseCommand
{
    protected $group       = 'UCC';
    protected $name        = 'ucc:revalidate';
    protected $description = 'Perform manual or nightly drift revalidation check against UCC master manifests';

    public function run(array $params)
    {
        CLI::write("=== UCC Masters Revalidation Check ===", 'yellow');

        $service = new UccRevalidateService();
        $summary = $service->runCheck('CLI_Runner');

        CLI::write("Executed At : " . $summary['run_at']);
        CLI::write("Configured  : " . ($summary['is_configured'] ? 'YES' : 'NO (Pending .env Credentials)'), $summary['is_configured'] ? 'green' : 'yellow');

        $hs = $summary['handshake'];
        CLI::write("\n--- Handshake Status ---", 'cyan');
        CLI::write("Status         : " . $hs['status']);
        CLI::write("UCC Reachable  : " . ($hs['ucc_reachable'] ? 'YES' : 'NO'));
        CLI::write("TLS Valid      : " . ($hs['tls_valid'] ? 'YES' : 'NO'));
        CLI::write("Auth Valid     : " . ($hs['auth_valid'] ? 'YES' : 'NO'));
        CLI::write("Clock Drift    : " . ($hs['clock_drift_sec'] !== null ? $hs['clock_drift_sec'] . "s" : 'N/A'));
        CLI::write("API Version    : " . ($hs['api_version'] ?? 'N/A'));
        CLI::write("Master Version : " . ($hs['master_version'] ?? 'N/A'));
        if ($hs['error']) {
            CLI::write("Error Detail   : " . $hs['error'], 'red');
        }

        $vc = $summary['version_comparison'];
        CLI::write("\n--- Version Status ---", 'cyan');
        CLI::write("Local last_version : " . ($vc['local_last_version'] ?? 'None (Initial)'));
        CLI::write("UCC Version        : " . ($vc['ucc_version'] ?? 'N/A'));
        CLI::write("In Sync            : " . ($vc['is_matched'] ? 'YES' : 'NO'), $vc['is_matched'] ? 'green' : 'yellow');

        if (!empty($summary['entities'])) {
            CLI::write("\n--- Entity Checksums & Differences ---", 'cyan');
            foreach ($summary['entities'] as $entity => $data) {
                $statusColor = $data['is_matched'] ? 'green' : 'red';
                CLI::write(sprintf(
                    "%-18s | Local: %-5d | UCC: %-5d | Matched: %-4s | Missing: %-3d | Changed: %-3d | Extra: %-3d | SourceGap: %-3d",
                    $entity,
                    $data['local_count'],
                    $data['ucc_count'],
                    $data['is_matched'] ? 'YES' : 'NO',
                    count($data['missing_keys']),
                    count($data['changed_keys']),
                    count($data['extra_keys']),
                    $data['source_gap']
                ), $statusColor);
            }
        }

        if (!empty($summary['orphaned_references'])) {
            CLI::write("\n--- Orphaned Mapped Customer References ---", 'red');
            foreach ($summary['orphaned_references'] as $orphan) {
                CLI::write(sprintf("  Mapped Cust ID #%d (%s) - Key: %s", 
                    $orphan['mapped_customer_id'], $orphan['mapped_customer_name'], $orphan['private_key']), 'yellow');
            }
        }

        if (!empty($summary['open_conflicts'])) {
            CLI::write("\n--- Open Sync Conflicts ---", 'red');
            foreach ($summary['open_conflicts'] as $c) {
                CLI::write(sprintf("  [%s] UCC ID %d, Key '%s': %s", 
                    $c['entity'], $c['ucc_id'], $c['ucc_private_key'], $c['reason']), 'red');
            }
        }

        CLI::write("\n=== Revalidation Check Complete ===", 'green');
    }
}

