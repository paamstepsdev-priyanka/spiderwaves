<span class="sw-pill-badge">
    Total Entries: <strong><?= number_format((int)($totalAll ?? $total ?? 0)) ?></strong>
</span>
<?php if (!empty($hasFilters)): ?>
    <span class="sw-pill-badge active">
        Matching: <strong><?= number_format((int)($total ?? 0)) ?></strong>
    </span>
<?php endif; ?>
<button type="button" class="sw-btn-primary js-ucc-sync-now" data-sync-url="<?= base_url('master/ucc-revalidate/sync-now') ?>">
    <i class="icon-sync"></i>
    <span>Sync Now</span>
</button>
