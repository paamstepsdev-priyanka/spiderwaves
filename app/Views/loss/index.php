<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Loss Management Hub - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Loss Hub<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header Section -->
<div class="sw-page-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
    <div class="sw-page-title-box">
        <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #0f172a;">Loss Management Hub</h1>
    </div>
    <div style="display: flex; align-items: center; gap: 10px;">
        <a href="<?= base_url('loss/manage') ?>" class="sw-btn-secondary">
            <i class="icon-list3"></i>
            <span>All Loss Batches</span>
        </a>
    </div>
</div>



<!-- Department Loss Cards Grid -->
<div class="row">
    <?php foreach ($cards as $c): ?>
    <div class="col-lg-4 col-md-6 col-sm-12" style="margin-bottom: 20px;">
        <div class="sw-table-card" style="border-radius: 8px; border: 1px solid #e2e8f0; background: #ffffff; display: flex; flex-direction: column; height: 100%; transition: box-shadow 0.2s ease, transform 0.2s ease;">
            <!-- Card Header -->
            <div style="padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;"><?= esc($c['loss_title']) ?></h3>
                    <span style="font-size: 12px; color: #64748b;">Dept: <?= esc($c['account_name']) ?></span>
                </div>
                <?php if ($c['loss_percentage'] > 0): ?>
                    <span class="sw-pill-badge" style="background-color: #f8fafc; color: #475569; border: 1px solid #e2e8f0; font-size: 11px;">
                        Loss: <?= number_format($c['loss_percentage'], 2, '.', '') ?>%
                    </span>
                <?php endif; ?>
            </div>

            <!-- Card Body Metrics -->
            <div style="padding: 20px; flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span style="font-size: 13px; color: #64748b;">Pending Batches:</span>
                    <span style="font-weight: 700; font-size: 14px; color: #0f172a; background: #f1f5f9; padding: 2px 8px; border-radius: 4px;">
                        <?= number_format($c['pending_count']) ?>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span style="font-size: 13px; color: #64748b;">Pending Gross Wt:</span>
                    <span style="font-weight: 700; font-size: 15px; color: #d97706;">
                        <?= number_format($c['pending_gross'], 3, '.', '') ?> g
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span style="font-size: 13px; color: #64748b;">Pending Fine MT:</span>
                    <span style="font-weight: 700; font-size: 15px; color: #059669;">
                        <?= number_format($c['pending_fine'], 3, '.', '') ?> g
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px dashed #e2e8f0;">
                    <span style="font-size: 12px; color: #94a3b8;">Total Refined:</span>
                    <span style="font-size: 13px; font-weight: 600; color: #64748b;">
                        <?= number_format($c['transferred_gross'], 3, '.', '') ?> g (<?= $c['transferred_count'] ?> transfers)
                    </span>
                </div>
            </div>

            <!-- Card Action Footer -->
            <div style="padding: 12px 20px; background: #fafafa; border-top: 1px solid #f1f5f9; border-radius: 0 0 8px 8px;">
                <a href="<?= base_url('loss/manage/' . $c['private_key']) ?>" class="btn btn-default btn-block" style="font-weight: 600; color: #2563eb; border-color: #cbd5e1; background: #ffffff;">
                    <i class="icon-arrow-right14 position-left"></i> Manage & Transfer Batches
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
