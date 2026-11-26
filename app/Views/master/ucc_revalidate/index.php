<?= $this->extend('layouts/default') ?>

<?= $this->section('content') ?>

<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>UCC Webhook Sync Status</h1>
        <p class="text-muted" style="margin-top: 4px; font-size: 13px;">
            Webhook notifications are recorded as Pending Sync. Click Sync Now to fetch and apply UCC master updates.
        </p>
    </div>
    <div class="sw-page-actions">
        <?php if (!empty($pendingCount)): ?>
            <span class="sw-pill-badge inactive">
                <span class="sw-badge-dot"></span> Pending Sync: <?= (int)$pendingCount ?>
            </span>
        <?php else: ?>
            <span class="sw-pill-badge active">
                <span class="sw-badge-dot"></span> Manual Sync Mode
            </span>
        <?php endif; ?>
        <button type="button" class="sw-btn-primary js-ucc-sync-now" data-sync-url="<?= base_url('master/ucc-revalidate/sync-now') ?>" <?= !$isConfigured ? 'disabled' : '' ?>>
            <i class="icon-sync"></i>
            <span>Sync Now</span>
        </button>
    </div>
</div>

<!-- Status Alert -->
<?php if (!$isConfigured): ?>
<div class="alert alert-warning" style="border-radius: 8px; margin-bottom: 20px;">
    <strong>UCC Connection Not Configured:</strong> Please configure UCC API and webhook credentials in <code>.env</code>.
</div>
<?php elseif (!empty($pendingCount)): ?>
<div class="alert alert-warning" style="border-radius: 8px; margin-bottom: 20px;">
    <strong><i class="icon-warning22"></i> Pending Sync:</strong>
    There are <strong><?= (int)$pendingCount ?></strong> pending UCC webhook notification(s) waiting to be synchronized. Click <strong>Sync Now</strong> to apply updates.
</div>
<?php elseif (!empty($state['last_error'])): ?>
<div class="alert alert-danger" style="border-radius: 8px; margin-bottom: 20px;">
    <strong><i class="icon-cross2"></i> Last Sync Error:</strong>
    <?= esc($state['last_error']) ?> — Click <strong>Sync Now</strong> to retry.
</div>
<?php elseif (!empty($state['last_synced_at'])): ?>
<div class="alert alert-success" style="border-radius: 8px; margin-bottom: 20px;">
    <strong><i class="icon-checkmark-circle"></i> Up to Date:</strong>
    No pending UCC webhook notifications. Last synchronized on <?= esc($state['last_synced_at']) ?>.
</div>
<?php endif; ?>

<!-- Summary Cards -->
<?php
    $lastVersion = !empty($state['last_version']) ? (string)$state['last_version'] : '';
    $shortVersion = $lastVersion !== '' ? substr($lastVersion, 0, 12) . '...' : 'None';
    $latestWhStatus = $latestWebhook['processing_status'] ?? null;
?>
<div class="row" style="margin-bottom: 20px;">
    <div class="col-md-4 col-sm-6">
        <div class="sw-card" style="padding: 18px 20px;">
            <div class="text-muted" style="font-size: 12px; font-weight: 600; text-transform: uppercase;">Sync Mode</div>
            <div style="font-size: 18px; font-weight: 700; margin-top: 6px;">
                Manual (Sync Now)
            </div>
            <div class="text-muted" style="font-size: 12px; margin-top: 4px;">
                Version: <?= esc($shortVersion) ?>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-6">
        <div class="sw-card" style="padding: 18px 20px;">
            <div class="text-muted" style="font-size: 12px; font-weight: 600; text-transform: uppercase;">Last Synced</div>
            <div style="font-size: 18px; font-weight: 700; margin-top: 6px;">
                <?= !empty($state['last_synced_at']) ? esc($state['last_synced_at']) : 'Never' ?>
            </div>
            <div class="text-muted" style="font-size: 12px; margin-top: 4px;">Last successful manual sync</div>
        </div>
    </div>

    <div class="col-md-4 col-sm-12">
        <div class="sw-card" style="padding: 18px 20px;">
            <div class="text-muted" style="font-size: 12px; font-weight: 600; text-transform: uppercase;">Latest Webhook</div>
            <div style="font-size: 18px; font-weight: 700; margin-top: 6px;">
                <?php if ($latestWhStatus === 'success'): ?>
                    <span class="text-success"><i class="icon-checkmark-circle"></i> Synced</span>
                <?php elseif ($latestWhStatus === 'failed'): ?>
                    <span class="text-danger"><i class="icon-cross2"></i> Failed</span>
                <?php elseif ($latestWhStatus === 'pending'): ?>
                    <span class="text-warning"><i class="icon-warning22"></i> Pending Sync</span>
                <?php else: ?>
                    <span class="text-muted">No Deliveries Yet</span>
                <?php endif; ?>
            </div>
            <div class="text-muted" style="font-size: 12px; margin-top: 4px;">
                <?= !empty($latestWebhook['received_at']) ? esc($latestWebhook['received_at']) : 'Waiting for UCC webhook event' ?>
            </div>
        </div>
    </div>
</div>

<!-- Local Master Data Summary Table -->
<div class="sw-table-card" style="margin-bottom: 20px;">
    <div class="sw-table-header">
        <div class="sw-table-title">UCC Master Data Summary (Local Cache)</div>
    </div>
    <div class="sw-table-responsive">
        <table class="sw-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Master Data</th>
                    <th style="width: 25%; text-align: center;">In SpiderWaves</th>
                    <th style="width: 30%; text-align: center;">Status</th>
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
                foreach ($entities as $eKey => $eLabel):
                    $localCount = (int)($entityCounts[$eKey] ?? 0);
                ?>
                <tr>
                    <td><strong><?= esc($eLabel) ?></strong></td>
                    <td style="text-align: center;"><?= $localCount ?></td>
                    <td style="text-align: center;">
                        <?php if ($localCount > 0): ?>
                            <span class="sw-pill-badge active"><span class="sw-badge-dot"></span> Synced</span>
                        <?php else: ?>
                            <span class="sw-pill-badge">Empty</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent & Pending Webhook Activity Table -->
<div class="sw-table-card">
    <div class="sw-table-header">
        <div class="sw-table-title">Pending &amp; Recent Webhook Notifications</div>
    </div>
    <div class="sw-table-responsive">
        <table class="sw-table">
            <thead>
                <tr>
                    <th>Received At</th>
                    <th>Source IP</th>
                    <th>Master Version</th>
                    <th style="text-align: center;">HTTP</th>
                    <th style="text-align: center;">Status</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentWebhooks)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted" style="padding: 20px;">
                        No webhook requests recorded yet.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($recentWebhooks as $wh): ?>
                    <?php
                        $whStatus = (string)($wh['processing_status'] ?? '');
                        $badgeClass = $whStatus === 'success' ? 'active' : ($whStatus === 'failed' ? 'inactive' : '');
                        $statusLabel = $whStatus === 'pending' ? 'Pending Sync' : ucfirst($whStatus);
                        $ver = (string)($wh['master_version'] ?? '');
                    ?>
                    <tr>
                        <td><?= esc($wh['received_at'] ?? '') ?></td>
                        <td><?= esc($wh['source_ip'] ?? '') ?></td>
                        <td><code><?= esc($ver !== '' ? substr($ver, 0, 12) . '...' : '-') ?></code></td>
                        <td style="text-align: center;"><?= (int)($wh['http_status'] ?? 0) ?></td>
                        <td style="text-align: center;">
                            <span class="sw-pill-badge <?= esc($badgeClass) ?>">
                                <span class="sw-badge-dot"></span> <?= esc($statusLabel) ?>
                            </span>
                        </td>
                        <td><?= esc($wh['error_details'] ?? (!empty($wh['is_test']) ? 'Test ping verified' : 'Synced')) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
