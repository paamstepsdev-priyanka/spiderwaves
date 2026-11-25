<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Dashboard - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php 
    $sessionData = session()->get('spider') ?? []; 
    $username    = $sessionData['user'] ?? 'User';
    $accessLevel = $sessionData['access_level'] ?? '';
?>

<!-- Quick Access Modules Grid Based on Role -->
<div class="row">

    <?php if ($accessLevel === 'admin'): ?>
        <!-- Admin Dashboard: 7 Process Department Cards (Redirect to Department Manage Page) -->
        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . casting_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Casting</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . filing_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Filing</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . heera_light_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Heera Light</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . polish_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Polish</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . setting_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Setting</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('refining') ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Refining</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . zero_balance_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Zero Balance</span></div>
                </div>
            </a>
        </div>

    <?php elseif (in_array($accessLevel, ['superadmin', 'process_manager'], true)): ?>
        <!-- Superadmin & Process Manager Dashboard: 13 Process Stage Cards (Redirect to Department Manage Page) -->
        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . casting_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Casting</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . filing_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Filing</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . heera_light_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Heera Light</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . packing_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Packing</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . polish_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Polish</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . rodium_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Rodium</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . transfer_packing_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Transfer_Packing</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . po_check_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>PO Check</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . galai_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Galai</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . tash_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Tash</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . sapphire_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Sapphire</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . setting_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Setting</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . zero_balance_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Zero Balance</span></div>
                </div>
            </a>
        </div>

    <?php elseif ($accessLevel === 'jr_process_manager'): ?>
        <!-- Jr. Process Manager Dashboard: 3 Process Stage Cards -->
        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . packing_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Packing</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . transfer_packing_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Transfer_Packing</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . rodium_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Rodium</span></div>
                </div>
            </a>
        </div>

    <?php elseif ($accessLevel === 'locker'): ?>
        <!-- Locker Dashboard: 1 Card -->
        <div class="col-sm-4">
            <a href="<?= base_url('locker') ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>Locker</span></div>
                </div>
            </a>
        </div>

    <?php elseif ($accessLevel === 'g58'): ?>
        <!-- G58 Division Dashboard: 4 Cards -->
        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . g58_polish_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>G58 Polish</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . g58_setting_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>G58 Setting</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . g58_filing_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>G58 Filing</span></div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="<?= base_url('process-waves/manage/' . g58_transfer_private_key) ?>" style="text-decoration: none;">
                <div class="panel" style="padding: 10px 10px; margin-bottom: 20px;">
                    <div class="demo-color"><span>G58 Transfer</span></div>
                </div>
            </a>
        </div>
    <?php endif; ?>

</div>
<?= $this->endSection() ?>
