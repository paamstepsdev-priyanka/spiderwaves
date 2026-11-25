<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>
<a href="<?= base_url('report/cust_wise_report') ?>" style="color: #64748b; text-decoration: none;">Customer Wise Report</a> / <?= esc($customer['cust_name']) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header Section -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1><?= esc($customer['cust_name']) ?></h1>
    </div>
    <div style="display: flex; align-items: center; gap: 10px;">
        <a href="<?= base_url('report/cust_wise_report') ?>" class="sw-btn-secondary" title="Back to Customer Wise Report">
            <i class="icon-arrow-left7"></i>
            <span>All Customers</span>
        </a>
        <button type="button" onclick="window.print();" class="sw-btn-secondary" title="Print Customer Ledger">
            <i class="icon-printer2"></i>
            <span>Print Ledger</span>
        </button>
    </div>
</div>

<!-- Date Filter Toolbar Card -->
<div class="sw-card" style="margin-bottom: 20px; padding: 16px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
    <form method="GET" action="<?= base_url('report/customer_ledger/' . $customer['private_key']) ?>" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <label for="from_date" style="margin: 0; font-size: 13px; font-weight: 600; color: #334155;">From Date:</label>
                <input type="date" id="from_date" name="from_date" value="<?= esc($filters['from_date'] ?? '') ?>" class="sw-input" style="width: 170px;">
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <label for="to_date" style="margin: 0; font-size: 13px; font-weight: 600; color: #334155;">To Date:</label>
                <input type="date" id="to_date" name="to_date" value="<?= esc($filters['to_date'] ?? '') ?>" class="sw-input" style="width: 170px;">
            </div>
            <button type="submit" class="sw-btn-primary" style="height: 40px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="icon-filter3"></i>
                <span>Filter</span>
            </button>
            <a href="<?= base_url('report/customer_ledger/' . $customer['private_key']) ?>" class="sw-btn-secondary" style="height: 40px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="icon-reload-alt"></i>
                <span>Reset</span>
            </a>
        </div>
        <div>
            <?php if (!empty($filters['from_date']) || !empty($filters['to_date'])): ?>
                <span class="sw-pill-badge" style="background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-size: 12px; font-weight: 500;">
                    <i class="icon-calendar52"></i> Range: <?= esc($filters['from_date'] ?: 'Start') ?> to <?= esc($filters['to_date'] ?: 'Present') ?>
                </span>
            <?php else: ?>
                <span class="sw-table-info">Showing complete active ledger</span>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Ledger Summary Metric Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px;">
    <!-- MT Balance Card -->
    <div class="sw-card" style="padding: 16px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
        <div style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
            Fine MT Balance
        </div>
        <div style="font-size: 20px; font-weight: 700; font-family: monospace; color: <?= ($totals['closing_mt'] < 0) ? '#dc2626' : (($totals['closing_mt'] > 0) ? '#16a34a' : '#0f172a') ?>;">
            <?= esc($totals['display_closing_mt']) ?> g
        </div>
        <div style="font-size: 11.5px; color: #94a3b8; margin-top: 4px;">
            Receipt: <?= esc($totals['display_receipt_mt']) ?> | Issue: <?= esc($totals['display_issue_mt']) ?>
        </div>
    </div>

    <!-- OR Balance Card -->
    <div class="sw-card" style="padding: 16px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
        <div style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
            OR Balance
        </div>
        <div style="font-size: 20px; font-weight: 700; font-family: monospace; color: <?= ($totals['closing_or'] < 0) ? '#dc2626' : (($totals['closing_or'] > 0) ? '#16a34a' : '#0f172a') ?>;">
            <?= esc($totals['display_closing_or']) ?> g
        </div>
        <div style="font-size: 11.5px; color: #94a3b8; margin-top: 4px;">
            Receipt: <?= esc($totals['display_receipt_or']) ?> | Issue: <?= esc($totals['display_issue_or']) ?>
        </div>
    </div>

    <!-- Combined Metal Balance Card -->
    <div class="sw-card" style="padding: 16px 20px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px;">
        <div style="font-size: 12px; font-weight: 600; color: #1e40af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
            Total Metal (MT + OR)
        </div>
        <div style="font-size: 20px; font-weight: 700; font-family: monospace; color: <?= ($totals['closing_metal'] < 0) ? '#dc2626' : (($totals['closing_metal'] > 0) ? '#16a34a' : '#1e3a8a') ?>;">
            <?= esc($totals['display_closing_metal']) ?> g
        </div>
        <div style="font-size: 11.5px; color: #3b82f6; margin-top: 4px;">
            Net Current Metal Position
        </div>
    </div>

    <!-- RK Cash Balance Card -->
    <div class="sw-card" style="padding: 16px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
        <div style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
            RK (Cash / ₹) Balance
        </div>
        <div style="font-size: 20px; font-weight: 700; font-family: monospace; color: <?= ($totals['closing_rk'] < 0) ? '#dc2626' : (($totals['closing_rk'] > 0) ? '#16a34a' : '#0f172a') ?>;">
            ₹ <?= esc($totals['display_closing_rk']) ?>
        </div>
        <div style="font-size: 11.5px; color: #94a3b8; margin-top: 4px;">
            Receipt: ₹ <?= esc($totals['display_receipt_rk']) ?> | Issue: ₹ <?= esc($totals['display_issue_rk']) ?>
        </div>
    </div>
</div>

<!-- Ledger Two-Column Table Card -->
<div class="sw-table-card">
    <div class="sw-table-header">
        <div style="display: flex; align-items: center; gap: 10px;">
            <i class="icon-newspaper text-primary" style="font-size: 16px;"></i>
            <span class="sw-table-title">Customer Ledger Statement</span>
        </div>
        <span class="sw-table-info"><?= count($pairedRows) ?> Statement Rows</span>
    </div>

    <div class="sw-table-responsive">
        <table class="sw-table" style="font-size: 12.5px;">
            <thead>
                <!-- Section Headings -->
                <tr>
                    <th colspan="6" style="text-align: center; background-color: #ecfdf5; color: #065f46; font-size: 13px; font-weight: 700; border-right: 2px solid #cbd5e1; padding: 10px;">
                        <i class="icon-arrow-down52"></i> RECEIPTS (DEPOSITS / JAMA)
                    </th>
                    <th colspan="6" style="text-align: center; background-color: #fef2f2; color: #991b1b; font-size: 13px; font-weight: 700; padding: 10px;">
                        <i class="icon-arrow-up52"></i> ISSUES (WITHDRAWALS / UDHAR)
                    </th>
                </tr>
                <!-- Column Headings -->
                <tr style="background-color: #f8fafc; font-size: 12px;">
                    <!-- Left: Receipts -->
                    <th style="width: 85px;">Date</th>
                    <th style="min-width: 110px;">Description</th>
                    <th style="width: 85px; text-align: right;">Fine MT</th>
                    <th style="width: 85px; text-align: right;">OR</th>
                    <th style="width: 85px; text-align: right;">RK (₹)</th>
                    <th style="min-width: 100px; border-right: 2px solid #cbd5e1;">Remark</th>
                    
                    <!-- Right: Issues -->
                    <th style="width: 85px;">Date</th>
                    <th style="min-width: 110px;">Description</th>
                    <th style="width: 85px; text-align: right;">Fine MT</th>
                    <th style="width: 85px; text-align: right;">OR</th>
                    <th style="width: 85px; text-align: right;">RK (₹)</th>
                    <th style="min-width: 100px;">Remark</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($pairedRows)): ?>
                    <?php foreach ($pairedRows as $pair): ?>
                    <?php 
                        $r = $pair['receipt'];
                        $i = $pair['issue'];
                        $isOpeningRow = (!empty($r['is_opening']) || !empty($i['is_opening']));
                        $rowBg = $isOpeningRow ? 'background-color: #eff6ff; font-weight: 600;' : '';
                    ?>
                    <tr style="<?= $rowBg ?>">
                        <!-- Left: Receipt Columns -->
                        <?php if ($r): ?>
                            <td style="color: #475569; font-size: 12px;"><?= esc($r['date']) ?></td>
                            <td style="color: #1e293b;"><?= esc($r['description']) ?></td>
                            <td style="text-align: right; font-family: monospace; font-weight: 600; color: #065f46;">
                                <?= ($r['fine_mt'] > 0) ? view_round_up($r['fine_mt'], 3) : '-' ?>
                            </td>
                            <td style="text-align: right; font-family: monospace; font-weight: 600; color: #065f46;">
                                <?= ($r['fine_or'] > 0) ? view_round_up($r['fine_or'], 3) : '-' ?>
                            </td>
                            <td style="text-align: right; font-family: monospace; color: #475569;">
                                <?= ($r['rk_amount'] > 0) ? number_format($r['rk_amount'], 2, '.', '') : '-' ?>
                            </td>
                            <td style="color: #64748b; font-size: 11.5px; border-right: 2px solid #cbd5e1;"><?= esc($r['remark']) ?></td>
                        <?php else: ?>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td style="text-align: right;">-</td>
                            <td style="text-align: right;">-</td>
                            <td style="text-align: right;">-</td>
                            <td style="border-right: 2px solid #cbd5e1;">&nbsp;</td>
                        <?php endif; ?>

                        <!-- Right: Issue Columns -->
                        <?php if ($i): ?>
                            <td style="color: #475569; font-size: 12px;"><?= esc($i['date']) ?></td>
                            <td style="color: #1e293b;"><?= esc($i['description']) ?></td>
                            <td style="text-align: right; font-family: monospace; font-weight: 600; color: #991b1b;">
                                <?= ($i['fine_mt'] > 0) ? view_round_up($i['fine_mt'], 3) : '-' ?>
                            </td>
                            <td style="text-align: right; font-family: monospace; font-weight: 600; color: #991b1b;">
                                <?= ($i['fine_or'] > 0) ? view_round_up($i['fine_or'], 3) : '-' ?>
                            </td>
                            <td style="text-align: right; font-family: monospace; color: #475569;">
                                <?= ($i['rk_amount'] > 0) ? number_format($i['rk_amount'], 2, '.', '') : '-' ?>
                            </td>
                            <td style="color: #64748b; font-size: 11.5px;"><?= esc($i['remark']) ?></td>
                        <?php else: ?>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td style="text-align: right;">-</td>
                            <td style="text-align: right;">-</td>
                            <td style="text-align: right;">-</td>
                            <td>&nbsp;</td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="12" style="text-align: center; padding: 48px; color: #64748b;">
                            <div style="font-size: 36px; margin-bottom: 8px; opacity: 0.5;">
                                <i class="icon-info22"></i>
                            </div>
                            <p style="font-size: 15px; font-weight: 500; margin: 0;">No active ledger transactions found for this customer.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <!-- Total Receipts and Issues Row -->
                <tr style="background-color: #f8fafc; font-weight: 700; border-top: 2px solid #cbd5e1;">
                    <td colspan="2" style="text-align: right; color: #334155;">Total Receipts:</td>
                    <td style="text-align: right; font-family: monospace; color: #065f46;"><?= esc($totals['display_receipt_mt']) ?></td>
                    <td style="text-align: right; font-family: monospace; color: #065f46;"><?= esc($totals['display_receipt_or']) ?></td>
                    <td style="text-align: right; font-family: monospace; color: #334155;"><?= esc($totals['display_receipt_rk']) ?></td>
                    <td style="border-right: 2px solid #cbd5e1;">&nbsp;</td>

                    <td colspan="2" style="text-align: right; color: #334155;">Total Issues:</td>
                    <td style="text-align: right; font-family: monospace; color: #991b1b;"><?= esc($totals['display_issue_mt']) ?></td>
                    <td style="text-align: right; font-family: monospace; color: #991b1b;"><?= esc($totals['display_issue_or']) ?></td>
                    <td style="text-align: right; font-family: monospace; color: #334155;"><?= esc($totals['display_issue_rk']) ?></td>
                    <td>&nbsp;</td>
                </tr>

                <!-- Net Closing Balance Row -->
                <tr style="background-color: #eff6ff; font-weight: 700; border-top: 1px solid #bfdbfe;">
                    <td colspan="6" style="text-align: right; padding: 12px 16px; color: #1e3a8a; border-right: 2px solid #cbd5e1; font-size: 13px;">
                        Closing Balances (Net Position = Receipts - Issues):
                    </td>
                    <td colspan="6" style="padding: 12px 16px;">
                        <div style="display: flex; align-items: center; justify-content: space-around; gap: 16px; flex-wrap: wrap;">
                            <div>
                                <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">Closing MT:</span>
                                <span style="font-family: monospace; font-size: 13.5px; color: <?= ($totals['closing_mt'] < 0) ? '#dc2626' : (($totals['closing_mt'] > 0) ? '#16a34a' : '#0f172a') ?>;">
                                    <?= esc($totals['display_closing_mt']) ?> g
                                </span>
                            </div>
                            <div>
                                <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">Closing OR:</span>
                                <span style="font-family: monospace; font-size: 13.5px; color: <?= ($totals['closing_or'] < 0) ? '#dc2626' : (($totals['closing_or'] > 0) ? '#16a34a' : '#0f172a') ?>;">
                                    <?= esc($totals['display_closing_or']) ?> g
                                </span>
                            </div>
                            <div>
                                <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">Closing RK:</span>
                                <span style="font-family: monospace; font-size: 13.5px; color: <?= ($totals['closing_rk'] < 0) ? '#dc2626' : (($totals['closing_rk'] > 0) ? '#16a34a' : '#0f172a') ?>;">
                                    ₹ <?= esc($totals['display_closing_rk']) ?>
                                </span>
                            </div>
                            <div style="background: #ffffff; padding: 4px 10px; border-radius: 6px; border: 1px solid #bfdbfe;">
                                <span style="font-size: 11.5px; color: #1e40af; font-weight: 700;">Closing Metal (MT+OR):</span>
                                <span style="font-family: monospace; font-size: 14px; font-weight: 700; color: <?= ($totals['closing_metal'] < 0) ? '#dc2626' : (($totals['closing_metal'] > 0) ? '#16a34a' : '#1e3a8a') ?>;">
                                    <?= esc($totals['display_closing_metal']) ?> g
                                </span>
                            </div>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
