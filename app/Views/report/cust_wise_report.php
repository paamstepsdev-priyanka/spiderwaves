<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?><?= esc($breadcrumb) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header Section -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Customer Wise Report</h1>
    </div>
    <div style="display: flex; align-items: center; gap: 10px;">
        <button type="button" onclick="window.print();" class="sw-btn-secondary" title="Print Customer Wise Report">
            <i class="icon-printer2"></i>
            <span>Print Report</span>
        </button>
    </div>
</div>

<!-- Search Filter Bar -->
<div style="margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
    <div style="position: relative; max-width: 360px; width: 100%;">
        <input type="text" id="custSearchInput" class="sw-input" style="padding-left: 14px;" placeholder="Search by customer name..." onkeyup="filterCustomerTable();">
    </div>
    <div style="display: flex; align-items: center; gap: 8px;">
        <span class="sw-table-info" id="searchMatchCount">Showing all <?= count($items) ?> records</span>
    </div>
</div>

<!-- Customer List Table Card -->
<div class="sw-table-card">
    <div class="sw-table-header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="icon-file-spreadsheet text-primary" style="font-size: 16px;"></i>
            <span class="sw-table-title">Customer Accounts & Metal Balances</span>
        </div>
        <span class="sw-table-info">Values rounded to 3 decimal places</span>
    </div>

    <div class="sw-table-responsive">
        <table class="sw-table" id="customerTable">
            <thead>
                <tr>
                    <th style="width: 60px; text-align: center;">S.No.</th>
                    <th>Customer Name</th>
                    <th style="width: 150px; text-align: right;">MT+OR</th>
                    <th style="width: 12%;">&nbsp;</th>
                    <th style="width: 12%;">&nbsp;</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $row): ?>
                    <tr class="cust-row" data-name="<?= esc(strtolower($row['cust_name'])) ?>">
                        <td style="text-align: center; color: #64748b; font-weight: 500;">
                            <?= esc($row['sno']) ?>
                        </td>
                        <td>
                            <a href="<?= base_url('report/customer_ledger/' . $row['private_key']) ?>" 
                               class="sw-cust-name" 
                               style="color: #2563eb; text-decoration: none; font-weight: 600;"
                               title="View Detailed Ledger for <?= esc($row['cust_name']) ?>">
                                <?= esc($row['cust_name']) ?>
                            </a>
                        </td>
                        <td style="text-align: right; font-weight: 700; font-family: monospace; font-size: 13.5px; color: <?= ($row['balance'] < 0) ? '#dc2626' : (($row['balance'] > 0) ? '#16a34a' : '#334155') ?>;">
                            <?= esc($row['display_balance']) ?>
                        </td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 48px; color: #64748b;">
                            <div style="font-size: 36px; margin-bottom: 8px; opacity: 0.5;">
                                <i class="icon-info22"></i>
                            </div>
                            <p style="font-size: 15px; font-weight: 500; margin: 0;">No customer accounts found.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background-color: #f8fafc; font-weight: 700; border-top: 2px solid #e2e8f0;">
                    <td colspan="2" style="text-align: right; padding: 12px 16px; color: #334155; font-size: 13.5px;">
                        Total Balance (All Customers):
                    </td>
                    <td style="text-align: right; padding: 12px 16px; font-family: monospace; font-size: 14px; color: <?= ($totalBalance < 0) ? '#dc2626' : (($totalBalance > 0) ? '#16a34a' : '#334155') ?>;">
                        <?= esc($displayTotal) ?>
                    </td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
function filterCustomerTable() {
    var input = document.getElementById('custSearchInput');
    var filter = input.value.toLowerCase().trim();
    var table = document.getElementById('customerTable');
    var rows = table.getElementsByClassName('cust-row');
    var visibleCount = 0;

    for (var i = 0; i < rows.length; i++) {
        var name = rows[i].getAttribute('data-name') || '';
        if (name.indexOf(filter) > -1) {
            rows[i].style.display = '';
            visibleCount++;
        } else {
            rows[i].style.display = 'none';
        }
    }

    var countLabel = document.getElementById('searchMatchCount');
    if (countLabel) {
        if (filter.length > 0) {
            countLabel.textContent = 'Showing ' + visibleCount + ' matching customer' + (visibleCount === 1 ? '' : 's');
        } else {
            countLabel.textContent = 'Showing all ' + rows.length + ' records';
        }
    }
}
</script>

<?= $this->endSection() ?>
