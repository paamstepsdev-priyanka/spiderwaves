<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Add Wastage Rule - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Add Wastage Rule<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Add Customer Wastage Contract</h1>
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
    <form action="<?= base_url('master/wastage/store') ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="sw-form-section-title">
            <i class="icon-percent" style="margin-right: 6px; color: var(--sw-primary);"></i> Wastage Allowance Contract
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <!-- Customer Selection -->
            <div class="sw-form-group">
                <label class="sw-label" for="customer_id">
                    Customer <span class="req">*</span>
                </label>
                <select name="customer_id" id="customer_id" class="select2" style="width: 100%;" required>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $cust): ?>
                        <?php 
                            $cId = is_array($cust) ? ($cust['cust_id'] ?? 0) : ($cust->cust_id ?? 0);
                            $cName = is_array($cust) ? ($cust['cust_name'] ?? '') : ($cust->cust_name ?? '');
                        ?>
                        <option value="<?= $cId ?>" <?= old('customer_id') == $cId ? 'selected' : '' ?>>
                            <?= esc($cName) ?> (ID: #<?= (int)$cId ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: var(--sw-slate); font-size: 11px;">Client gold account receiving the wastage allowance.</small>
            </div>

            <!-- Collection Selection -->
            <div class="sw-form-group">
                <label class="sw-label" for="collection_id">
                    Collection / Category <span class="req">*</span>
                </label>
                <select name="collection_id" id="collection_id" class="select2" style="width: 100%;" required>
                    <option value="">-- Select Collection --</option>
                    <?php foreach ($collections as $col): ?>
                        <?php 
                            $colId = is_array($col) ? ($col['collection_id'] ?? 0) : ($col->collection_id ?? 0);
                            $colName = is_array($col) ? ($col['collection_name'] ?? '') : ($col->collection_name ?? '');
                        ?>
                        <option value="<?= $colId ?>" <?= old('collection_id') == $colId ? 'selected' : '' ?>>
                            <?= esc($colName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: var(--sw-slate); font-size: 11px;">Jewellery category or product line defining the contract.</small>
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
                           placeholder="e.g. 2.500" 
                           value="<?= old('wastage', '0.000') ?>" 
                           required>
                    <span style="font-weight: 600; color: var(--sw-slate); font-size: 16px;">%</span>
                </div>
                <small style="color: var(--sw-slate); font-size: 11px;">
                    In OR (Old Rate) mode: Fine Weight = Gross × (Purity + Wastage) / 100.
                </small>
            </div>
        </div>

        <!-- Form Action Buttons -->
        <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid var(--sw-border); padding-top: 20px;">
            <a href="<?= base_url('master/wastage') ?>" class="sw-btn-secondary">
                <span>Cancel</span>
            </a>
            <button type="submit" class="sw-btn-primary" id="btn_submit_wastage">
                <i class="icon-checkmark-circle"></i>
                <span>Save Wastage Contract</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
