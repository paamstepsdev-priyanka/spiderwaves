<?php

namespace App\Services;

use App\Models\UccCustomerModel;
use App\Models\UccMappedCustomerModel;
use App\Models\UccWastageModel;
use App\Models\UccPurityModel;
use App\Models\UccCollectionModel;
use App\Models\UccSyncStateModel;
use App\Models\UccSyncConflictModel;
use App\Exceptions\UccException;

class UccSyncService
{
    /**
     * Strict dependency order for synchronization.
     * purities -> wastages -> collections -> customers -> mapped_customers
     */
    public const ENTITY_ORDER = [
        'purities',
        'wastages',
        'collections',
        'customers',
        'mapped_customers',
    ];

    protected UccClient $client;
    protected UccCustomerModel $customerModel;
    protected UccMappedCustomerModel $mappedCustomerModel;
    protected UccWastageModel $wastageModel;
    protected UccPurityModel $purityModel;
    protected UccCollectionModel $collectionModel;
    protected UccSyncStateModel $syncStateModel;
    protected UccSyncConflictModel $conflictModel;

    public function __construct(?UccClient $client = null)
    {
        $this->client              = $client ?? new UccClient();
        $this->customerModel       = new UccCustomerModel();
        $this->mappedCustomerModel = new UccMappedCustomerModel();
        $this->wastageModel        = new UccWastageModel();
        $this->purityModel         = new UccPurityModel();
        $this->collectionModel     = new UccCollectionModel();
        $this->syncStateModel      = new UccSyncStateModel();
        $this->conflictModel       = new UccSyncConflictModel();
    }

    /**
     * Get corresponding model for an entity.
     */
    public function getModelForEntity(string $entity)
    {
        $entity = UccHasher::normalizeEntityName($entity);
        return match ($entity) {
            'customers'        => $this->customerModel,
            'mapped_customers' => $this->mappedCustomerModel,
            'wastages'         => $this->wastageModel,
            'purities'         => $this->purityModel,
            'collections'      => $this->collectionModel,
            default            => throw new \InvalidArgumentException("Unsupported entity: {$entity}"),
        };
    }

    /**
     * Get entity primary key field name.
     */
    public function getIdFieldForEntity(string $entity): string
    {
        $entity = UccHasher::normalizeEntityName($entity);
        return match ($entity) {
            'customers'        => 'customer_id',
            'mapped_customers' => 'mapped_customer_id',
            'wastages'         => 'wastage_id',
            'purities'         => 'purity_id',
            'collections'      => 'collection_id',
            default            => 'id',
        };
    }

    /**
     * Main sync entrypoint. Run by cron (php spark ucc:sync) or webhook trigger.
     * Prevents overlapping runs using a non-blocking process lock.
     */
    public function sync(bool $forceFullSnapshot = false, ?callable $logger = null): array
    {
        $log = $logger ?? function (string $msg) {};

        // Establish process lock
        $lockDir = WRITEPATH . 'locks';
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0755, true);
        }
        $lockFile = fopen($lockDir . '/ucc_sync.lock', 'c+');
        if (!$lockFile || !flock($lockFile, LOCK_EX | LOCK_NB)) {
            $log("Sync aborted: another synchronization process is currently active.");
            return ['status' => 'locked', 'message' => 'Concurrent sync run active.'];
        }

        try {
            $state = $this->syncStateModel->getState();
            $lastVersion = !empty($state['last_version']) ? (string)$state['last_version'] : null;

            // 1. Check if anything changed via GET /masters/manifest
            $manifestRes = $this->client->getManifest();
            $remoteVersion = (string)($manifestRes['meta']['master_version'] ?? '');

            if (!$forceFullSnapshot && !empty($lastVersion) && hash_equals($lastVersion, $remoteVersion)) {
                $log("UCC master version {$remoteVersion} matches local last_version. Nothing to sync.");
                return [
                    'status'         => 'noop',
                    'message'        => 'Already up to date.',
                    'master_version' => $remoteVersion,
                ];
            }

            // 2. First load (last_version is null) or forced snapshot
            if ($forceFullSnapshot || empty($lastVersion)) {
                $log("Starting Full Snapshot synchronization...");
                return $this->runFullSnapshot($log);
            }

            // 3. Incremental sync driven by manifest checksums
            $log("Starting Manifest-Driven Incremental synchronization (local version: " . substr($lastVersion, 0, 8) . "..., remote: " . substr($remoteVersion, 0, 8) . "...)...");
            return $this->runIncrementalSync($manifestRes, $log);

        } catch (\Throwable $e) {
            $this->syncStateModel->recordError($e->getMessage());
            $log("Sync failed with error: " . $e->getMessage());
            throw $e;
        } finally {
            $pendingFlag = $lockDir . '/ucc_sync_pending.flag';
            if (is_file($pendingFlag)) {
                @unlink($pendingFlag);
            }
            try {
                \Config\Services::cache()->delete('ucc_live_status_snapshot');
            } catch (\Throwable $cacheErr) {
                // Ignore cache clear failure
            }
            if ($lockFile) {
                flock($lockFile, LOCK_UN);
                fclose($lockFile);
            }
        }
    }

    /**
     * Execute full snapshot sync across all five entities in dependency order.
     */
    public function runFullSnapshot(?callable $logger = null): array
    {
        $log = $logger ?? function (string $msg) {};

        $stats = [
            'type'        => 'full_snapshot',
            'counts'      => [],
            'conflicts'   => 0,
        ];

        // 1. Loop entities in strict dependency order: purities -> wastages -> collections -> customers -> mapped_customers
        $db = \Config\Database::connect();

        foreach (self::ENTITY_ORDER as $entity) {
            $log("Snapshotting {$entity}...");
            $model = $this->getModelForEntity($entity);
            $idField = $this->getIdFieldForEntity($entity);
            $afterId = 0;
            $entityCount = 0;

            do {
                $response = $this->client->getSnapshot($entity, $afterId, 200);
                $rows = $response['data'] ?? [];
                $meta = $response['meta'] ?? [];

                if (empty($rows)) {
                    break;
                }

                $db->transBegin();

                $model->executeSyncWrite(function () use ($entity, $rows, &$entityCount, &$stats, $log) {
                    foreach ($rows as $row) {
                        $conflict = $this->applySingleRow($entity, $row);
                        if ($conflict) {
                            $stats['conflicts']++;
                            $log("Conflict detected on {$entity} (ID: {$row[$this->getIdFieldForEntity($entity)]}): {$conflict}");
                        } else {
                            $entityCount++;
                        }
                    }
                });

                if ($db->transStatus() === false) {
                    $db->transRollback();
                    throw new UccException("Snapshot database transaction failed for entity {$entity} after_id {$afterId}.");
                }
                $db->transCommit();

                $afterId = (int)($meta['next_after_id'] ?? 0);
            } while ($afterId > 0);

            $stats['counts'][$entity] = $entityCount;
            $log("Completed {$entity}: {$entityCount} rows synchronized.");
        }

        // 2. Race guard: check manifest version after snapshotting
        $manifestRes = $this->client->getManifest();
        $finalVersion = (string)($manifestRes['meta']['master_version'] ?? '');

        // If changes occurred during snapshot, run incremental sync up to 3 rounds
        $round = 0;
        while ($round < 3) {
            $round++;
            $diffFound = false;
            foreach (self::ENTITY_ORDER as $entity) {
                $localChecksum = $this->computeLocalEntityChecksum($entity);
                $remoteChecksum = (string)($manifestRes['data'][$entity]['checksum'] ?? '');
                if (!hash_equals($localChecksum, $remoteChecksum)) {
                    $diffFound = true;
                    break;
                }
            }

            if (!$diffFound) {
                break;
            }

            $log("Race guard detected changes during snapshot. Running resolution round {$round}...");
            $this->runIncrementalSync($manifestRes, $log);
            $manifestRes = $this->client->getManifest();
            $finalVersion = (string)($manifestRes['meta']['master_version'] ?? '');
        }

        // 3. Save successfully applied master_version token
        $this->syncStateModel->updateVersion($finalVersion);
        \Config\Services::cache()->delete('ucc_live_status_snapshot');
        $stats['final_version'] = $finalVersion;
        $log("Full snapshot sync complete. Version token saved: {$finalVersion}");

        return $stats;
    }

    /**
     * Execute manifest-driven incremental sync loop.
     * Uses GET /masters/manifest + POST /masters/rows.
     */
    public function runIncrementalSync(?array $initialManifest = null, ?callable $logger = null): array
    {
        $log = $logger ?? function (string $msg) {};
        $db = \Config\Database::connect();

        $manifestRes = $initialManifest ?? $this->client->getManifest();
        $totalApplied = 0;
        $totalSoftDeleted = 0;
        $totalConflicts = 0;
        $finalVersion = '';

        // Up to 3 rounds of race guard
        for ($round = 1; $round <= 3; $round++) {
            $uccManifest = $manifestRes['data'] ?? [];
            $uccVersion  = (string)($manifestRes['meta']['master_version'] ?? '');
            $finalVersion = $uccVersion;

            $keysToFetchByEntity = [];
            $keysToSoftDeleteByEntity = [];
            $hasAnyEntityDifference = false;

            // Check each entity in dependency order
            foreach (self::ENTITY_ORDER as $entity) {
                $localChecksum = $this->computeLocalEntityChecksum($entity);
                $remoteChecksum = (string)($uccManifest[$entity]['checksum'] ?? '');

                if (hash_equals($localChecksum, $remoteChecksum)) {
                    continue; // In sync
                }

                $hasAnyEntityDifference = true;
                $log("Entity {$entity} checksum mismatch. Fetching keys manifest...");

                $diffs = $this->detectEntityDifferences($entity);
                $keysToFetch = array_merge($diffs['missing'], $diffs['changed']);
                $keysToFetchByEntity[$entity] = $keysToFetch;
                $keysToSoftDeleteByEntity[$entity] = $diffs['extra'];
            }

            if (!$hasAnyEntityDifference) {
                $log("All entities in sync with remote checksums.");
                break;
            }

            // Apply differences across all entities in ONE database transaction
            $db->transBegin();

            try {
                foreach (self::ENTITY_ORDER as $entity) {
                    $model = $this->getModelForEntity($entity);

                    // 1. Fetch and upsert missing & changed rows in chunks of 200
                    if (!empty($keysToFetchByEntity[$entity])) {
                        $chunks = array_chunk($keysToFetchByEntity[$entity], 200);
                        foreach ($chunks as $chunk) {
                            $rowsResp = $this->client->getRows($entity, $chunk);
                            $rows = $rowsResp['data'] ?? [];

                            $model->executeSyncWrite(function () use ($entity, $rows, &$totalApplied, &$totalConflicts, $log) {
                                foreach ($rows as $row) {
                                    $conflict = $this->applySingleRow($entity, $row);
                                    if ($conflict) {
                                        $totalConflicts++;
                                        $log("Conflict in {$entity}: {$conflict}");
                                    } else {
                                        $totalApplied++;
                                    }
                                }
                            });
                        }
                    }

                    // 2. Soft-delete extra local rows (deleted_at = date('Y-m-d H:i:s')). Never hard delete.
                    if (!empty($keysToSoftDeleteByEntity[$entity])) {
                        $now = date('Y-m-d H:i:s');
                        $softDeleteKeys = $keysToSoftDeleteByEntity[$entity];
                        $model->executeSyncWrite(function () use ($model, $softDeleteKeys, $now, &$totalSoftDeleted) {
                            foreach ($softDeleteKeys as $k) {
                                $row = $model->findAnyByPrivateKey($k);
                                if ($row && empty($row['deleted_at'])) {
                                    $idField = $model->primaryKey;
                                    $model->update($row[$idField], ['deleted_at' => $now]);
                                    $totalSoftDeleted++;
                                }
                            }
                        });
                    }
                }

                if ($db->transStatus() === false) {
                    $db->transRollback();
                    throw new UccException("Database transaction failed during incremental sync.");
                }

                $db->transCommit();
                $log("Round {$round} transaction committed ({$totalApplied} rows upserted, {$totalSoftDeleted} soft-deleted).");

            } catch (\Throwable $e) {
                $db->transRollback();
                throw $e;
            }

            // Race guard: check if remote master_version moved during our sync
            $manifestRes = $this->client->getManifest();
            $newUccVersion = (string)($manifestRes['meta']['master_version'] ?? '');

            if (hash_equals($uccVersion, $newUccVersion)) {
                // Remote version did not move, successfully synced!
                break;
            }

            $log("Race guard: remote master_version moved from {$uccVersion} to {$newUccVersion}. Retrying round " . ($round + 1) . "...");
        }

        // Save last_version token upon successful sync completion
        $this->syncStateModel->updateVersion($finalVersion);
        \Config\Services::cache()->delete('ucc_live_status_snapshot');
        $log("Incremental sync completed. Saved version token: {$finalVersion}");

        return [
            'type'          => 'incremental',
            'upserted'      => $totalApplied,
            'soft_deleted'  => $totalSoftDeleted,
            'conflicts'     => $totalConflicts,
            'final_version' => $finalVersion,
        ];
    }

    /**
     * Compute manifest checksum for an entity from active live rows.
     */
    public function computeLocalEntityChecksum(string $entity): string
    {
        $model = $this->getModelForEntity($entity);
        $idField = $this->getIdFieldForEntity($entity);

        $liveRows = $model->where('deleted_at IS NULL')->orderBy($idField, 'ASC')->findAll();
        $prepared = [];
        foreach ($liveRows as $r) {
            $prepared[] = [
                'id'          => $r[$idField],
                'private_key' => $r['private_key'],
                'row_hash'    => UccHasher::computeRowHash($entity, $r),
            ];
        }

        return UccHasher::computeManifestChecksum($prepared);
    }

    /**
     * Detect missing, changed, and extra keys for an entity by comparing against manifest?keys=1.
     */
    public function detectEntityDifferences(string $entity): array
    {
        $model = $this->getModelForEntity($entity);
        $idField = $this->getIdFieldForEntity($entity);

        $liveRows = $model->where('deleted_at IS NULL')->findAll();
        $localMap = [];
        foreach ($liveRows as $r) {
            $localMap[$r['private_key']] = [
                'id'       => $r[$idField],
                'row_hash' => UccHasher::computeRowHash($entity, $r),
            ];
        }

        $missing = [];
        $changed = [];
        $seenRemoteKeys = [];
        $afterId = 0;

        do {
            $resp = $this->client->getManifest($entity, true, $afterId, 200);
            $remoteRows = $resp['data'] ?? [];
            $meta = $resp['meta'] ?? [];

            if (empty($remoteRows)) {
                break;
            }

            foreach ($remoteRows as $ur) {
                $key  = (string)$ur['private_key'];
                $hash = (string)$ur['row_hash'];
                $seenRemoteKeys[$key] = true;

                if (!isset($localMap[$key])) {
                    $missing[] = $key;
                } elseif (!hash_equals($localMap[$key]['row_hash'], $hash)) {
                    $changed[] = $key;
                }
            }

            $afterId = (int)($meta['next_after_id'] ?? 0);
        } while ($afterId > 0);

        // Extra keys: local active keys not listed by UCC
        $extra = [];
        foreach (array_keys($localMap) as $lKey) {
            if (!isset($seenRemoteKeys[$lKey])) {
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
     * Apply a single row upsert with conflict detection.
     * Returns null if applied, or string reason if conflict detected.
     */
    public function applySingleRow(string $entity, array $rawRow): ?string
    {
        $model = $this->getModelForEntity($entity);
        $idField = $this->getIdFieldForEntity($entity);

        $canonical = UccHasher::formatCanonicalRow($entity, $rawRow);
        $uccId     = (int)$canonical[$idField];
        $key       = (string)$canonical['private_key'];
        $rowHash   = UccHasher::computeRowHash($entity, $canonical);

        // Conflict check: Check by ID and by private_key
        $rowById  = $model->findAnyById($uccId);
        $rowByKey = $model->findAnyByPrivateKey($key);

        if ($rowById && $rowById['private_key'] !== $key) {
            // ID matches, but private_key differs -> CONFLICT
            $reason = "ID {$uccId} already bound locally to key '{$rowById['private_key']}', received new key '{$key}'";
            $this->conflictModel->recordConflict($entity, $uccId, $key, (int)$rowById[$idField], $rowById['private_key'], $reason);
            return $reason;
        }

        if ($rowByKey && (int)$rowByKey[$idField] !== $uccId) {
            // Key matches, but ID differs -> CONFLICT
            $reason = "private_key '{$key}' already bound locally to ID {$rowByKey[$idField]}, received new ID {$uccId}";
            $this->conflictModel->recordConflict($entity, $uccId, $key, (int)$rowByKey[$idField], $rowByKey['private_key'], $reason);
            return $reason;
        }

        // Prepare storage payload
        $data = $canonical;
        $data['row_hash']   = $rowHash;
        $data['synced_at']  = date('Y-m-d H:i:s');
        $data['deleted_at'] = null; // Clear deleted_at if previously soft-deleted (supports returning records)

        // Map timestamps if present in canonical
        if (array_key_exists('created_at', $data)) {
            $data['ucc_created_at'] = $data['created_at'];
            unset($data['created_at']);
        }
        if (array_key_exists('updated_at', $data)) {
            $data['ucc_updated_at'] = $data['updated_at'];
            unset($data['updated_at']);
        }

        if ($rowById || $rowByKey) {
            $model->update($uccId, $data);
        } else {
            $model->insert($data, false);
        }

        return null;
    }

    /**
     * Synchronize a specific set of private keys (used by Revalidate Masters "Sync missing and changed rows").
     * Uses POST /masters/rows and the same upsert/conflict rules.
     */
    public function syncSpecificKeys(string $entity, array $privateKeys): array
    {
        $entity = UccHasher::normalizeEntityName($entity);
        $chunks = array_chunk($privateKeys, 200);
        $applied = 0;
        $conflicts = 0;

        $db = \Config\Database::connect();
        $model = $this->getModelForEntity($entity);

        foreach ($chunks as $chunk) {
            $res = $this->client->getRows($entity, $chunk);
            $rows = $res['data'] ?? [];

            $db->transBegin();
            $model->executeSyncWrite(function () use ($entity, $rows, &$applied, &$conflicts) {
                foreach ($rows as $row) {
                    $conflict = $this->applySingleRow($entity, $row);
                    if ($conflict) {
                        $conflicts++;
                    } else {
                        $applied++;
                    }
                }
            });

            if ($db->transStatus() === false) {
                $db->transRollback();
                throw new UccException("Failed to save synchronized rows for {$entity}.");
            }
            $db->transCommit();
        }

        return ['applied' => $applied, 'conflicts' => $conflicts];
    }

    /**
     * Soft-delete extra rows in Spider that UCC no longer lists (used by Revalidate Masters).
     */
    public function softDeleteExtraRows(string $entity, array $privateKeys): int
    {
        $model = $this->getModelForEntity($entity);
        $now = date('Y-m-d H:i:s');
        $count = 0;

        $model->executeSyncWrite(function () use ($model, $privateKeys, $now, &$count) {
            foreach ($privateKeys as $key) {
                $row = $model->findAnyByPrivateKey($key);
                if ($row && empty($row['deleted_at'])) {
                    $idField = $model->primaryKey;
                    $model->update($row[$idField], [
                        'deleted_at' => $now,
                    ]);
                    $count++;
                }
            }
        });

        return $count;
    }
}
