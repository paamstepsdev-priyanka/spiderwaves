<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Edit Wastage Rule - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Edit Wastage Rule<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Edit Wastage Contract</h1>
    </div>
    <div class="sw-page-actions">
        <a href="<?= base_url('master/wastage') ?>" class="sw-btn-secondary">
            <i class="icon-arrow-left52"></i>
            <span>Back to Wastage Master</span>
        </a>
    </div>
</div>

<!-- Flash Alerts -->
<?php if (session()->getFlashdata('error')): ?>
    <div class="sw-alert sw-alert-danger">
        <i class="icon-alert"></i>
        <span><?= esc(session()->getFlashdata('error')) ?></span>
    </div>
<?php endif; ?>

<!-- Form Card -->
<div class="sw-form-card">
    <form action="<?= base_url('master/wastage/update/' . $wastage->cw_id) ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="sw-form-section-title">
            <i class="icon-pencil5" style="margin-right: 6px; color: var(--sw-primary);"></i> Contract Parameters
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <!-- Customer (Read-only summary) -->
            <div class="sw-form-group">
                <label class="sw-label">Customer</label>
                <input type="text" class="sw-input" value="<?= esc($wastage->cust_name) ?> (ID: #<?= (int)$wastage->customer_id ?>)" readonly style="background-color: var(--sw-slate-50, #f8fafc); cursor: not-allowed;">
                <input type="hidden" name="customer_id" value="<?= (int)$wastage->customer_id ?>">
            </div>

            <!-- Collection (Read-only summary) -->
            <div class="sw-form-group">
                <label class="sw-label">Collection / Category</label>
                <input type="text" class="sw-input" value="<?= esc($wastage->collection_name) ?> (ID: #<?= (int)$wastage->collection_id ?>)" readonly style="background-color: var(--sw-slate-50, #f8fafc); cursor: not-allowed;">
                <input type="hidden" name="collection_id" value="<?= (int)$wastage->collection_id ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <!-- Wastage Percentage -->
            <div class="sw-form-group">
                <label class="sw-label" for="wastage">
                    Wastage Percentage (%) <span class="req">*</span>
                </label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="number" 
                           step="0.001" 
                           min="0" 
                           max="100" 
                           name="wastage" 
                           id="wastage" 
                           class="sw-input" 
                           value="<?= esc(old('wastage', $wastage->wastage)) ?>" 
                           required>
                    <span style="font-weight: 600; color: var(--sw-slate); font-size: 16px;">%</span>
                </div>
                <small style="color: var(--sw-slate); font-size: 11px;">
                    Updated allowance applied to all future Old Rate (OR) receipt calculations for this collection.
                </small>
            </div>
        </div>

        <!-- Form Action Buttons -->
        <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sw-border); padding-top: 20px;">
            <a href="<?= base_url('master/wastage') ?>" class="sw-btn-secondary">
                <span>Cancel</span>
            </a>
            <button type="submit" class="sw-btn-primary" id="btn_update_wastage">
                <i class="icon-checkmark-circle"></i>
                <span>Update Wastage Contract</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
