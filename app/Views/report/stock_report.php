<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Stock - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Stock<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header Section -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Stock</h1>
    </div>
</div>

<!-- Stock Report Table Card -->
<div class="sw-table-card">
    <div class="sw-table-header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="icon-box text-primary" style="font-size: 16px;"></i>
            <span class="sw-table-title">Department Stock Balances</span>
        </div>
        <span class="sw-table-info"><?= count($reportItems) ?> Departments</span>
    </div>

    <div class="sw-table-responsive">
        <table class="sw-table">
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">S.No.</th>
                    <th>Name</th>
                    <th style="width: 150px; text-align: right;">MT+OR</th>
                    <th style="width: 12%;">&nbsp;</th>
                    <th style="width: 12%;">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reportItems)): ?>
                    <?php foreach ($reportItems as $row): ?>
                    <tr>
                        <td style="text-align: center; color: #64748b; font-weight: 500;">
                            <?= esc($row['sno']) ?>
                        </td>
                        <td>
                            <a href="<?= base_url('process-waves/manage/' . $row['private_key']) ?>" 
                               class="sw-cust-name" 
                               style="color: #2563eb; text-decoration: none; font-weight: 600;"
                               title="View <?= esc($row['account_name']) ?> Process Ledger">
                                <?= esc($row['account_name']) ?>
                            </a>
                        </td>
                        <td style="text-align: right; font-weight: 600; font-variant-numeric: tabular-nums; color: #0f172a;">
                            <?= esc($row['display_balance']) ?>
                        </td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px 20px; color: #64748b;">
                            <i class="icon-inbox text-muted" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                            <p style="margin: 0; font-size: 14px;">There is no stock data available.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($reportItems)): ?>
            <tfoot>
                <tr style="background-color: #f8fafc; border-top: 2px solid var(--sw-border);">
                    <td style="text-align: center; font-weight: 700; color: #475569;">Total</td>
                    <td style="font-weight: 700; color: #0f172a;">All Departments Combined</td>
                    <td style="text-align: right; font-weight: 700; font-size: 14px; font-variant-numeric: tabular-nums; color: #2563eb;">
                        <?= esc($displayTotal) ?>
                    </td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
