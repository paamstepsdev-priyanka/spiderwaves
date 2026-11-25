<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>UCC Master Sync Status</h1>
        <p class="text-muted" style="margin-top: 4px; font-size: 13px;">
            View whether your UCC master data updates are reflected in SpiderWaves and sync any pending changes.
        </p>
    </div>
    <div class="sw-page-actions">
        <button type="button" class="sw-btn-primary" id="btnSyncNow" <?= !$isConfigured ? 'disabled' : '' ?>>
            <i class="icon-download4"></i>
            <span>Sync Updates Now</span>
        </button>
    </div>
</div>

<!-- Configuration Alert -->
<?php if (!$isConfigured): ?>
<div class="alert alert-warning" style="border-radius: 8px; margin-bottom: 20px;">
    <strong>UCC Connection Not Configured:</strong> Please contact your administrator to configure UCC API credentials.
</div>
<?php endif; ?>

<!-- Status Banner -->
<?php if ($isConfigured && !empty($liveStatus)): ?>
    <?php if (!empty($liveStatus['has_pending_changes'])): ?>
    <div class="alert alert-warning" style="border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
        <div>
            <strong style="font-size: 14px;"><i class="icon-warning22"></i> New UCC Updates Available</strong>
            <div style="font-size: 13px; margin-top: 4px;">
                There are <strong><?= (int)($liveStatus['pending_count'] ?? 0) ?> pending update(s)</strong> in UCC
                <?php if (!empty($liveStatus['changed_entities'])): ?>
                    (<?= esc(ucwords(str_replace('_', ' ', implode(', ', $liveStatus['changed_entities'])))) ?>)
                <?php endif; ?>
                not yet synced to SpiderWaves.
            </div>
        </div>
        <button type="button" class="sw-btn-primary" id="btnBannerSyncNow" style="white-space: nowrap;">
            <i class="icon-sync"></i> Sync Now
        </button>
    </div>
    <?php elseif (($liveStatus['sync_status'] ?? '') === 'error'): ?>
    <div class="alert alert-danger" style="border-radius: 8px; margin-bottom: 20px;">
        <strong><i class="icon-cross2"></i> Unable to Reach UCC:</strong>
        <?= esc($liveStatus['remote_error'] ?? 'Connection error while checking UCC.') ?>
    </div>
    <?php elseif (($liveStatus['sync_status'] ?? '') === 'in_sync'): ?>
    <div class="alert alert-success" style="border-radius: 8px; margin-bottom: 20px;">
        <strong><i class="icon-checkmark-circle"></i> Everything is Up to Date:</strong>
        All UCC master data changes are reflected in SpiderWaves.
    </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Simple 3-Card Summary -->
<?php
    $isMatched = !empty($liveStatus)
        ? empty($liveStatus['has_pending_changes']) && (($liveStatus['sync_status'] ?? '') === 'in_sync')
        : !empty($latestSummary['version_comparison']['is_matched']);
    $pendingCnt = (int)($liveStatus['pending_count'] ?? 0);
    $isConnected = ($liveStatus['sync_status'] ?? '') !== 'error' && $isConfigured;
?>
<div class="row" style="margin-bottom: 20px;">
    <div class="col-md-4 col-sm-6">
        <div class="sw-card" style="padding: 18px 20px;">
            <div class="text-muted" style="font-size: 12px; font-weight: 600; text-transform: uppercase;">Sync Status</div>
            <div style="font-size: 18px; font-weight: 700; margin-top: 6px;" class="<?= $isMatched ? 'text-success' : 'text-warning' ?>">
                <?= $isMatched ? 'Up to Date' : ('Updates Pending (' . $pendingCnt . ')') ?>
            </div>
            <div class="text-muted" style="font-size: 12px; margin-top: 4px;">
                Last checked: <?= esc($liveStatus['checked_now_at'] ?? ($latestLog['run_at'] ?? 'Never')) ?>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-6">
        <div class="sw-card" style="padding: 18px 20px;">
            <div class="text-muted" style="font-size: 12px; font-weight: 600; text-transform: uppercase;">Last Synced</div>
            <div style="font-size: 18px; font-weight: 700; margin-top: 6px;">
                <?= !empty($state['last_synced_at']) ? esc($state['last_synced_at']) : 'Never' ?>
            </div>
            <div class="text-muted" style="font-size: 12px; margin-top: 4px;">Last update pulled from UCC</div>
        </div>
    </div>

    <div class="col-md-4 col-sm-12">
        <div class="sw-card" style="padding: 18px 20px;">
            <div class="text-muted" style="font-size: 12px; font-weight: 600; text-transform: uppercase;">UCC Connection</div>
            <div style="font-size: 18px; font-weight: 700; margin-top: 6px;">
                <?php if (!$isConfigured): ?>
                    <span class="text-warning"><i class="icon-warning22"></i> Not Configured</span>
                <?php elseif ($isConnected): ?>
                    <span class="text-success"><i class="icon-checkmark-circle"></i> Connected</span>
                <?php else: ?>
                    <span class="text-danger"><i class="icon-cross2"></i> Offline</span>
                <?php endif; ?>
            </div>
            <div class="text-muted" style="font-size: 12px; margin-top: 4px;">Live UCC server link</div>
        </div>
    </div>
</div>

<!-- Simple Master Data Summary Table -->
<div class="sw-table-card">
    <div class="sw-table-header">
        <div class="sw-table-title">UCC Master Data Summary</div>
    </div>
    <div class="sw-table-responsive">
        <table class="sw-table">
            <thead>
                <tr>
                    <th style="width: 30%;">Master Data</th>
                    <th style="width: 18%; text-align: center;">In SpiderWaves</th>
                    <th style="width: 18%; text-align: center;">In UCC</th>
                    <th style="width: 18%; text-align: center;">Status</th>
                    <th style="width: 16%; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $entities = [
                    'customers'        => 'Customers',
                    'mapped_customers' => 'Mapped Customers',
                    'collections'      => 'Collections',
                    'purities'         => 'Purities',
                    'wastages'         => 'Wastages',
                ];
                $changedList = $liveStatus['changed_entities'] ?? [];
                foreach ($entities as $eKey => $eLabel):
                    $eData = $latestSummary['entities'][$eKey] ?? null;
                    $localCount = $eData['local_count'] ?? 0;
                    $uccCount = $eData['ucc_count'] ?? '-';
                    $missing = count($eData['missing_keys'] ?? []);
                    $changed = count($eData['changed_keys'] ?? []);
                    $extra   = count($eData['extra_keys'] ?? []);
                    $pendingRowCount = $missing + $changed + $extra;
                    $hasLiveDiff = in_array($eKey, $changedList, true);
                    $isEntitySynced = ($eData !== null) && ($pendingRowCount === 0) && !$hasLiveDiff;
                ?>
                <tr data-entity="<?= esc($eKey) ?>">
                    <td><strong><?= esc($eLabel) ?></strong></td>
                    <td style="text-align: center;"><?= (int)$localCount ?></td>
                    <td style="text-align: center;"><?= esc($uccCount) ?></td>
                    <td style="text-align: center;">
                        <?php if ($eData === null && !$hasLiveDiff): ?>
                            <span class="sw-pill-badge">Not Checked</span>
                        <?php elseif ($isEntitySynced): ?>
                            <span class="sw-pill-badge active"><span class="sw-badge-dot"></span> Up to Date</span>
                        <?php else: ?>
                            <span class="sw-pill-badge inactive"><span class="sw-badge-dot"></span> <?= $pendingRowCount > 0 ? ($pendingRowCount . ' Pending') : 'Update Available' ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;">
                        <?php if ($missing > 0 || $changed > 0 || $hasLiveDiff): ?>
                            <button type="button" class="btn btn-xs btn-primary btn-sync-entity" data-entity="<?= esc($eKey) ?>">
                                <i class="icon-sync"></i> Sync
                            </button>
                        <?php elseif ($extra > 0): ?>
                            <button type="button" class="btn btn-xs btn-warning btn-softdelete-entity" data-entity="<?= esc($eKey) ?>">
                                <i class="icon-trash"></i> Clean Up (<?= $extra ?>)
                            </button>
                        <?php else: ?>
                            <span class="text-muted"><i class="icon-checkmark4"></i> Synced</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- JavaScript Interactivity -->
<script>
$(document).ready(function() {
    var entityDiffs = <?= json_encode($latestSummary['entities'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    // Sync All Pending Updates Now
    $('#btnSyncNow, #btnBannerSyncNow').on('click', function() {
        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="icon-spinner2 spinner"></i> Syncing...');

        $.ajax({
            url: '<?= base_url('master/ucc-revalidate/sync-now') ?>',
            type: 'POST',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(resp) {
                $btn.prop('disabled', false).html(originalHtml);
                if (resp.status === 'success') {
                    swal({
                        title: "Sync Complete",
                        text: resp.message || "UCC updates are now reflected in SpiderWaves.",
                        type: "success",
                        confirmButtonColor: "#2563eb"
                    }, function() {
                        location.reload();
                    });
                } else {
                    swal("Sync Error", resp.message || "An error occurred.", "error");
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalHtml);
                var err = xhr.responseJSON ? xhr.responseJSON.message : "Server error executing sync.";
                swal("Sync Error", err, "error");
            }
        });
    });

    // Per-entity Sync
    $('.btn-sync-entity').on('click', function() {
        var entity = $(this).data('entity');
        var eInfo  = entityDiffs[entity] || {};
        var keys   = [].concat(eInfo.missing_keys || [], eInfo.changed_keys || []);
        if (!keys.length) {
            $('#btnSyncNow').trigger('click');
            return;
        }

        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="icon-spinner2 spinner"></i> Syncing...');

        $.ajax({
            url: '<?= base_url('master/ucc-revalidate/sync-differences') ?>',
            type: 'POST',
            data: { entity: entity, keys: keys },
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(resp) {
                $btn.prop('disabled', false).html(originalHtml);
                if (resp.status === 'success') {
                    swal({
                        title: "Updated",
                        text: resp.message,
                        type: "success",
                        confirmButtonColor: "#2563eb"
                    }, function() {
                        location.reload();
                    });
                } else {
                    swal("Sync Error", resp.message || "An error occurred.", "error");
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html(originalHtml);
                var err = xhr.responseJSON ? xhr.responseJSON.message : "Server error syncing entity.";
                swal("Sync Error", err, "error");
            }
        });
    });

    // Per-entity Soft-Delete Extra Rows
    $('.btn-softdelete-entity').on('click', function() {
        var entity = $(this).data('entity');
        var eInfo  = entityDiffs[entity] || {};
        var keys   = eInfo.extra_keys || [];
        if (!keys.length) return;

        swal({
            title: "Remove Deleted UCC Records?",
            text: "This will archive " + keys.length + " record(s) removed from UCC.",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d97706",
            confirmButtonText: "Yes, Continue",
            closeOnConfirm: false
        }, function() {
            $.ajax({
                url: '<?= base_url('master/ucc-revalidate/soft-delete-extra') ?>',
                type: 'POST',
                data: { entity: entity, keys: keys },
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(resp) {
                    if (resp.status === 'success') {
                        swal({
                            title: "Updated",
                            text: resp.message,
                            type: "success"
                        }, function() {
                            location.reload();
                        });
                    } else {
                        swal("Error", resp.message || "Failed to update records.", "error");
                    }
                },
                error: function(xhr) {
                    var err = xhr.responseJSON ? xhr.responseJSON.message : "Server error.";
                    swal("Error", err, "error");
                }
            });
        });
    });

});
</script>

<?= $this->endSection() ?>
