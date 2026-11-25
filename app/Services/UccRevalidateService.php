<?php

namespace App\Services;

use App\Models\UccSyncStateModel;
use App\Models\UccSyncConflictModel;
use App\Models\UccSyncCheckLogModel;
use App\Models\UccCustomerModel;
use App\Models\UccMappedCustomerModel;
use App\Models\UccWastageModel;
use App\Models\UccPurityModel;
use App\Models\UccCollectionModel;

class UccRevalidateService
{
    protected UccClient $client;
    protected UccSyncService $syncService;
    protected UccSyncStateModel $stateModel;
    protected UccSyncConflictModel $conflictModel;
    protected UccSyncCheckLogModel $checkLogModel;

    public function __construct(?UccClient $client = null, ?UccSyncService $syncService = null)
    {
        $this->client       = $client ?? new UccClient();
        $this->syncService  = $syncService ?? new UccSyncService($this->client);
        $this->stateModel   = new UccSyncStateModel();
        $this->conflictModel= new UccSyncConflictModel();
        $this->checkLogModel= new UccSyncCheckLogModel();
    }

    /**
     * Rate-limiting check: enforce max 1 run per minute per user or IP.
     */
    public function isRateLimited(string $identifier = 'system'): bool
    {
        $latest = $this->checkLogModel->getLatestLog();
        if (!$latest) {
            return false;
        }

        $lastRunTime = strtotime((string)$latest['run_at']);
        return (time() - $lastRunTime) < 60;
    }

    /**
     * Run full revalidation workflow.
     */
    public function runCheck(string $runBy = 'Admin'): array
    {
        $summary = [
            'run_by'            => $runBy,
            'run_at'            => date('Y-m-d H:i:s'),
            'is_configured'     => $this->client->isConfigured(),
            'handshake'         => [
                'status'         => 'pending',
                'ucc_reachable'  => false,
                'tls_valid'      => false,
                'auth_valid'     => false,
                'clock_in_sync'  => false,
                'clock_drift_sec'=> null,
                'api_version'    => null,
                'master_version' => null,
                'error'          => null,
            ],
            'version_comparison'=> [
                'local_last_version' => null,
                'ucc_version'        => null,
                'is_matched'         => false,
            ],
            'entities'          => [],
            'orphaned_references' => [],
            'open_conflicts'    => [],
            'has_differences'   => false,
        ];

        // 1. Check local state & conflicts
        $state = $this->stateModel->getState();
        $localLastVersion = !empty($state['last_version']) ? (string)$state['last_version'] : null;
        $summary['version_comparison']['local_last_version'] = $localLastVersion;
        $summary['open_conflicts'] = $this->conflictModel->getOpenConflicts();

        // Check local orphaned references
        $mappedCustomerModel = new UccMappedCustomerModel();
        $summary['orphaned_references'] = $mappedCustomerModel->getOrphanedReferences();

        // 2. If client is not configured, record status and stop gracefully without fake results
        if (!$this->client->isConfigured()) {
            $summary['handshake']['status'] = 'unconfigured';
            $summary['handshake']['error'] = 'UCC API credentials are not yet configured in .env.';
            $this->checkLogModel->logCheck($runBy, $summary);
            return $summary;
        }

        // 3. Step 1: Handshake (GET /health)
        try {
            $health = $this->client->getHealth();
            $data = $health['data'] ?? [];

            $serverTime = (int)($data['server_time'] ?? time());
            $localTime  = time();
            $driftSec   = abs($localTime - $serverTime);

            $summary['handshake']['status']          = 'ok';
            $summary['handshake']['ucc_reachable']   = true;
            $summary['handshake']['tls_valid']       = true;
            $summary['handshake']['auth_valid']      = true;
            $summary['handshake']['clock_in_sync']   = ($driftSec <= 300);
            $summary['handshake']['clock_drift_sec'] = $driftSec;
            $summary['handshake']['api_version']     = (string)($data['api_version'] ?? 'unknown');
            $summary['handshake']['master_version']  = (string)($data['master_version'] ?? '');

            $summary['version_comparison']['ucc_version'] = $summary['handshake']['master_version'];
            $summary['version_comparison']['is_matched']  = (!empty($localLastVersion) && hash_equals($localLastVersion, $summary['handshake']['master_version']));

        } catch (\App\Exceptions\UccAuthException $e) {
            $summary['handshake']['status']         = 'auth_failed';
            $summary['handshake']['ucc_reachable']  = true;
            $summary['handshake']['tls_valid']      = true;
            $summary['handshake']['auth_valid']     = false;
            $summary['handshake']['error']          = $e->getMessage();
            $this->checkLogModel->logCheck($runBy, $summary);
            return $summary;
        } catch (\Throwable $e) {
            $summary['handshake']['status'] = 'failed';
            $summary['handshake']['error']  = $e->getMessage();
            $this->checkLogModel->logCheck($runBy, $summary);
            return $summary;
        }

        // 4. Step 2 & 3: Manifest Comparison (GET /masters/manifest)
        try {
            $manifestRes = $this->client->getManifest();
            $uccManifest = $manifestRes['data'] ?? [];

            foreach (UccSyncService::ENTITY_ORDER as $entity) {
                $summary['entities'][$entity] = $this->compareEntityManifest($entity, $uccManifest[$entity] ?? null);
                if (!empty($summary['entities'][$entity]['missing_keys']) ||
                    !empty($summary['entities'][$entity]['changed_keys']) ||
                    !empty($summary['entities'][$entity]['extra_keys'])) {
                    $summary['has_differences'] = true;
                }
            }

        } catch (\Throwable $e) {
            $summary['manifest_error'] = $e->getMessage();
        }

        // 5. Persist check log
        $this->checkLogModel->logCheck($runBy, $summary);
        $this->clearStatusCache();

        return $summary;
    }

    /**
     * Clear cached live UCC status so next check queries UCC fresh.
     */
    public function clearStatusCache(): void
    {
        $cache = \Config\Services::cache();
        $cache->delete('ucc_live_status_snapshot');
    }

    /**
     * Lightweight live status check comparing SpiderWaves local state against UCC.
     * Caches remote version/manifest check for 60s unless $forceRemoteCheck is true.
     */
    public function getLiveStatus(bool $forceRemoteCheck = false): array
    {
        $cache = \Config\Services::cache();
        $cacheKey = 'ucc_live_status_snapshot';

        if (!$forceRemoteCheck) {
            $cached = $cache->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $state = $this->stateModel->getState();
        $latestLog = $this->checkLogModel->getLatestLog();
        $openConflicts = count($this->conflictModel->getOpenConflicts());

        $localVersion = !empty($state['last_version']) ? (string)$state['last_version'] : null;
        $lastSyncedAt = !empty($state['last_synced_at']) ? (string)$state['last_synced_at'] : null;
        $lastError    = !empty($state['last_error']) ? (string)$state['last_error'] : null;
        $lastCheckedAt = !empty($latestLog['run_at']) ? (string)$latestLog['run_at'] : null;

        $latestSummary = !empty($latestLog['summary_json'])
            ? json_decode((string)$latestLog['summary_json'], true)
            : null;

        $pendingFromLog = 0;
        if (!empty($latestSummary['entities']) && is_array($latestSummary['entities'])) {
            foreach ($latestSummary['entities'] as $eData) {
                $pendingFromLog += count($eData['missing_keys'] ?? [])
                    + count($eData['changed_keys'] ?? [])
                    + count($eData['extra_keys'] ?? []);
            }
        }

        $result = [
            'is_configured'       => $this->client->isConfigured(),
            'sync_status'         => 'unconfigured',
            'status_label'        => 'Not Configured',
            'has_pending_changes' => false,
            'pending_count'       => $pendingFromLog,
            'changed_entities'    => [],
            'local_version'       => $localVersion,
            'remote_version'      => null,
            'last_synced_at'      => $lastSyncedAt,
            'last_checked_at'     => $lastCheckedAt,
            'last_error'          => $lastError,
            'open_conflicts'      => $openConflicts,
            'remote_error'        => null,
            'checked_now_at'      => date('Y-m-d H:i:s'),
        ];

        if (!$result['is_configured']) {
            $cache->save($cacheKey, $result, 60);
            return $result;
        }

        try {
            $remoteVersion = $this->client->getMasterVersion();
            $result['remote_version'] = $remoteVersion;

            $versionMatches = (!empty($localVersion) && !empty($remoteVersion) && hash_equals($localVersion, $remoteVersion));

            if (!$versionMatches) {
                // Version differs from UCC: fetch lightweight manifest to see which entities have pending changes
                $manifestRes = $this->client->getManifest();
                $uccManifest = $manifestRes['data'] ?? [];
                $changedEntities = [];
                $approxDiffCount = 0;

                foreach (UccSyncService::ENTITY_ORDER as $entity) {
                    $localChecksum = $this->syncService->computeLocalEntityChecksum($entity);
                    $remoteChecksum = (string)($uccManifest[$entity]['checksum'] ?? '');
                    if (!hash_equals($localChecksum, $remoteChecksum)) {
                        $changedEntities[] = $entity;
                        $uccCount = (int)($uccManifest[$entity]['count'] ?? 0);
                        $model = $this->syncService->getModelForEntity($entity);
                        $localCount = $model->where('deleted_at IS NULL')->countAllResults();
                        $approxDiffCount += max(1, abs($uccCount - $localCount));
                    }
                }

                $result['changed_entities'] = $changedEntities;
                if (!empty($changedEntities) || $pendingFromLog > 0) {
                    $result['has_pending_changes'] = true;
                    $result['pending_count'] = max($pendingFromLog, $approxDiffCount);
                    $result['sync_status'] = 'pending_changes';
                    $result['status_label'] = 'UCC Updates Pending';
                } else {
                    $result['sync_status'] = 'in_sync';
                    $result['status_label'] = 'In Sync';
                }
            } elseif ($pendingFromLog > 0) {
                $result['has_pending_changes'] = true;
                $result['sync_status'] = 'pending_changes';
                $result['status_label'] = 'Local Drift Detected';
            } else {
                $result['sync_status'] = 'in_sync';
                $result['status_label'] = 'In Sync';
            }
        } catch (\Throwable $e) {
            $result['remote_error'] = $e->getMessage();
            $result['sync_status'] = 'error';
            $result['status_label'] = 'Connection Error';
        }

        $cache->save($cacheKey, $result, 60);
        return $result;
    }

    /**
     * Compare an individual entity against its UCC manifest.
     */
    protected function compareEntityManifest(string $entity, ?array $uccMeta): array
    {
        $model = $this->syncService->getModelForEntity($entity);
        $idField = $this->syncService->getIdFieldForEntity($entity);

        // Fetch all active live local rows
        $localRows = $model->where('deleted_at IS NULL')->orderBy($idField, 'ASC')->findAll();
        $localCount = count($localRows);

        // Recompute local row_hashes and manifest checksum from stored rows
        $preparedLiveRows = [];
        $localMap = [];

        foreach ($localRows as $r) {
            $hash = UccHasher::computeRowHash($entity, $r);
            $id   = $r[$idField];
            $key  = $r['private_key'];

            $preparedLiveRows[] = [
                'id'          => $id,
                'private_key' => $key,
                'row_hash'    => $hash,
            ];

            $localMap[$key] = [
                'id'       => $id,
                'row_hash' => $hash,
            ];
        }

        $localChecksum = UccHasher::computeManifestChecksum($preparedLiveRows);

        $uccCount      = (int)($uccMeta['count'] ?? 0);
        $uccChecksum   = (string)($uccMeta['checksum'] ?? '');
        $uccUnsyncable = (int)($uccMeta['unsyncable'] ?? 0);

        $result = [
            'entity'          => $entity,
            'local_count'     => $localCount,
            'ucc_count'       => $uccCount,
            'local_checksum'  => $localChecksum,
            'ucc_checksum'    => $uccChecksum,
            'is_matched'      => ($localCount === $uccCount && hash_equals($localChecksum, $uccChecksum)),
            'source_gap'      => $uccUnsyncable, // Incomplete in UCC itself
            'sync_gap'        => 0,              // UCC has it, Spider doesn't
            'missing_keys'    => [],
            'changed_keys'    => [],
            'extra_keys'      => [],
        ];

        // If checksums differ, fetch manifest with keys=1 to pinpoint exact differences
        if (!$result['is_matched']) {
            $diffs = $this->diffEntityKeys($entity, $localMap);
            $result['missing_keys'] = $diffs['missing'];
            $result['changed_keys'] = $diffs['changed'];
            $result['extra_keys']   = $diffs['extra'];
            $result['sync_gap']     = count($diffs['missing']);
        }

        return $result;
    }

    /**
     * Diff entity keys by fetching manifest?entity=...&keys=1.
     */
    protected function diffEntityKeys(string $entity, array $localMap): array
    {
        $missing = [];
        $changed = [];
        $seenUccKeys = [];

        $afterId = 0;
        do {
            $resp = $this->client->getManifest($entity, true, $afterId, 200);
            $uccRows = $resp['data'] ?? [];
            $meta    = $resp['meta'] ?? [];

            if (empty($uccRows)) {
                break;
            }

            foreach ($uccRows as $ur) {
                $uKey  = (string)$ur['private_key'];
                $uHash = (string)$ur['row_hash'];
                $seenUccKeys[$uKey] = true;

                if (!isset($localMap[$uKey])) {
                    $missing[] = $uKey;
                } elseif (!hash_equals($localMap[$uKey]['row_hash'], $uHash)) {
                    $changed[] = $uKey;
                }
            }

            $afterId = (int)($meta['next_after_id'] ?? 0);
        } while ($afterId > 0);

        // Extra in Spider: local keys that UCC no longer lists
        $extra = [];
        foreach (array_keys($localMap) as $lKey) {
            if (!isset($seenUccKeys[$lKey])) {
                $extra[] = $lKey;
            }
        }

        return [
            'missing' => $missing,
            'changed' => $changed,
            'extra'   => $extra,
        ];
    }

    /**
     * Generate CSV content of detected differences.
     */
    public function generateDifferencesCsv(array $revalidationSummary): string
    {
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, ['Entity', 'Difference Type', 'Private Key', 'Note']);

        $entities = $revalidationSummary['entities'] ?? [];
        foreach ($entities as $entity => $data) {
            foreach ($data['missing_keys'] ?? [] as $key) {
                fputcsv($fp, [$entity, 'Missing in Spider (Sync Gap)', $key, 'Row exists in UCC but missing in Spider']);
            }
            foreach ($data['changed_keys'] ?? [] as $key) {
                fputcsv($fp, [$entity, 'Changed / Outdated', $key, 'Row exists locally but row_hash differs from UCC']);
            }
            foreach ($data['extra_keys'] ?? [] as $key) {
                fputcsv($fp, [$entity, 'Extra in Spider', $key, 'Row exists locally but UCC no longer lists it']);
            }
        }

        // Orphaned mapped customers
        foreach ($revalidationSummary['orphaned_references'] ?? [] as $orphan) {
            $missing = array_filter([
                $orphan['missing_customer'] ?? null,
                $orphan['missing_collection'] ?? null,
                $orphan['missing_wastage'] ?? null,
                $orphan['missing_purity'] ?? null,
            ]);
            fputcsv($fp, [
                'mapped_customers',
                'Local Orphaned Reference',
                $orphan['private_key'],
                'Mapped Customer #' . $orphan['mapped_customer_id'] . ' (' . $orphan['mapped_customer_name'] . '): ' . implode(', ', $missing)
            ]);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv;
    }
}

