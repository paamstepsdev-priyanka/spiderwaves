<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>
<span>Customer Statement</span>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header Section -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1><?= esc($customer['cust_name']) ?></h1>
    </div>
    <div style="display: flex; align-items: center; gap: 10px;">
        <a href="javascript:void(0);" data-url="<?= base_url('report/cust_approved/' . $customer['private_key']) ?>" id="btnApproveCustomerStatement" class="sw-btn-primary" title="Approve Statement">
            <i class="icon-checkmark4"></i>
            <span>Approve Statement</span>
        </a>
        <button type="button" onclick="window.print();" class="sw-btn-secondary" title="Print Statement">
            <i class="icon-printer2"></i>
            <span>Print Statement</span>
        </button>
    </div>
</div>

<!-- Date Filter Toolbar Card -->
<div class="sw-card" style="margin-bottom: 20px; padding: 16px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
    <form method="GET" action="<?= base_url('report/customer_ledger_for_customer/' . $customer['private_key']) ?>" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
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
            <a href="<?= base_url('report/customer_ledger_for_customer/' . $customer['private_key']) ?>" class="sw-btn-secondary" style="height: 40px; display: inline-flex; align-items: center; gap: 6px;">
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
                <span class="sw-table-info">Showing complete active statement</span>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Ledger Summary Metric Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px;">
    <!-- MT Balance Card -->
    <div class="sw-card" style="padding: 16px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
        <div style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
            Fine Metal (MT)
        </div>
        <div style="font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">
            <?= esc($totals['display_closing_mt']) ?> <span style="font-size: 14px; font-weight: 500; color: #64748b;">g</span>
        </div>
        <div style="font-size: 11.5px; color: #64748b;">Opening: <?= esc($openings['display_mt']) ?> g</div>
    </div>

    <!-- OR Balance Card -->
    <div class="sw-card" style="padding: 16px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
        <div style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
            Old Rate (OR)
        </div>
        <div style="font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">
            <?= esc($totals['display_closing_or']) ?> <span style="font-size: 14px; font-weight: 500; color: #64748b;">g</span>
        </div>
        <div style="font-size: 11.5px; color: #64748b;">Opening: <?= esc($openings['display_or']) ?> g</div>
    </div>

    <!-- Net Combined Metal Card -->
    <div class="sw-card" style="padding: 16px 20px; background: #f8fafc; border: 2px solid <?= ((float)$totals['closing_metal'] >= 0) ? '#16a34a' : '#dc2626' ?>; border-radius: 10px;">
        <div style="font-size: 12px; font-weight: 700; color: <?= ((float)$totals['closing_metal'] >= 0) ? '#16a34a' : '#dc2626' ?>; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
            Combined Gold (MT + OR)
        </div>
        <div style="font-size: 24px; font-weight: 800; color: <?= ((float)$totals['closing_metal'] >= 0) ? '#16a34a' : '#dc2626' ?>; margin-bottom: 4px;">
            <?= esc($totals['display_closing_metal']) ?> <span style="font-size: 14px; font-weight: 600;">g</span>
        </div>
        <div style="font-size: 11.5px; color: #475569;">Total Metal Balance</div>
    </div>

    <!-- Cash Balance Card -->
    <div class="sw-card" style="padding: 16px 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px;">
        <div style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">
            Rupee Value (RK)
        </div>
        <div style="font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">
            &#8377; <?= esc($totals['display_closing_rk']) ?>
        </div>
        <div style="font-size: 11.5px; color: #64748b;">Opening: &#8377; <?= esc($openings['display_rk']) ?></div>
    </div>
</div>

<!-- Paired Transaction Table Card -->
<div class="sw-card" style="padding: 0; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; margin-bottom: 25px;">
    <div style="padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
        <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;">Customer Statement of Account</h3>
        <span class="sw-pill-badge" style="background-color: #ffffff; border: 1px solid #cbd5e1; font-weight: 600; font-size: 12px;">
            <?= count($rows) ?> Statement Entries
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped" style="margin-bottom: 0; font-size: 12.5px;">
            <thead>
                <!-- Top Headers -->
                <tr style="background: #0f172a; color: #ffffff;">
                    <th colspan="7" style="text-align: center; border-right: 2px solid #334155; font-size: 13px; font-weight: 700; letter-spacing: 0.5px; background: #16a34a;">
                        <i class="icon-arrow-down8"></i> RECEIPTS (DEPOSITS)
                    </th>
                    <th colspan="7" style="text-align: center; border-right: 2px solid #334155; font-size: 13px; font-weight: 700; letter-spacing: 0.5px; background: #dc2626;">
                        <i class="icon-arrow-up8"></i> ISSUES (WITHDRAWALS)
                    </th>
                    <th colspan="2" style="text-align: center; font-size: 13px; font-weight: 700; letter-spacing: 0.5px; background: #1e293b;">
                        RUNNING BALANCES
                    </th>
                </tr>
                <!-- Sub-Headers -->
                <tr style="background: #f1f5f9; color: #334155; font-size: 11.5px; text-transform: uppercase;">
                    <!-- Receipt Columns -->
                    <th style="width: 80px;">Date</th>
                    <th>Mode</th>
                    <th style="text-align: right;">Gross</th>
                    <th style="text-align: right;">Less</th>
                    <th style="text-align: right;">Net</th>
                    <th style="text-align: right;">Purity</th>
                    <th style="text-align: right; border-right: 2px solid #cbd5e1; background: #dcfce7; color: #166534;">Fine/Amt</th>

                    <!-- Issue Columns -->
                    <th style="width: 80px;">Date</th>
                    <th>Mode</th>
                    <th style="text-align: right;">Gross</th>
                    <th style="text-align: right;">Less</th>
                    <th style="text-align: right;">Net</th>
                    <th style="text-align: right;">Purity</th>
                    <th style="text-align: right; border-right: 2px solid #cbd5e1; background: #fee2e2; color: #991b1b;">Fine/Amt</th>

                    <!-- Balance Columns -->
                    <th style="text-align: right; width: 110px;">Metal (g)</th>
                    <th style="text-align: right; width: 110px;">Cash (&#8377;)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening Balance Row -->
                <tr style="background: #fffbeb; font-weight: 700; color: #92400e;">
                    <td colspan="6" style="border-right: 1px solid #e2e8f0;">
                        <i class="icon-history"></i> OPENING BALANCE
                    </td>
                    <td style="text-align: right; border-right: 2px solid #cbd5e1; font-family: monospace;">
                        <?= esc($openings['display_metal']) ?>
                    </td>
                    <td colspan="6" style="border-right: 1px solid #e2e8f0; color: #64748b; font-weight: 500;">
                        Initial Balance
                    </td>
                    <td style="text-align: right; border-right: 2px solid #cbd5e1; font-family: monospace; color: #64748b;">
                        -
                    </td>
                    <td style="text-align: right; font-family: monospace; font-weight: 700; color: #0f172a;">
                        <?= esc($openings['display_metal']) ?>
                    </td>
                    <td style="text-align: right; font-family: monospace; font-weight: 700; color: #0f172a;">
                        <?= esc($openings['display_rk']) ?>
                    </td>
                </tr>

                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <!-- Receipt Columns -->
                        <td><?= esc($r['r_date']) ?></td>
                        <td>
                            <?php if (!empty($r['r_mode'])): ?>
                                <span class="sw-pill-badge" style="background: #e2e8f0; font-size: 10.5px; font-weight: 700; padding: 2px 6px;">
                                    <?= esc($r['r_mode']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; font-family: monospace;"><?= esc($r['r_gross']) ?></td>
                        <td style="text-align: right; font-family: monospace;"><?= esc($r['r_less']) ?></td>
                        <td style="text-align: right; font-family: monospace;"><?= esc($r['r_net']) ?></td>
                        <td style="text-align: right; font-family: monospace;"><?= esc($r['r_purity']) ?></td>
                        <td style="text-align: right; font-family: monospace; font-weight: 600; border-right: 2px solid #cbd5e1; color: #166534; background: #f0fdf4;">
                            <?= esc($r['r_fine_val']) ?>
                        </td>

                        <!-- Issue Columns -->
                        <td><?= esc($r['i_date']) ?></td>
                        <td>
                            <?php if (!empty($r['i_mode'])): ?>
                                <span class="sw-pill-badge" style="background: #e2e8f0; font-size: 10.5px; font-weight: 700; padding: 2px 6px;">
                                    <?= esc($r['i_mode']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; font-family: monospace;"><?= esc($r['i_gross']) ?></td>
                        <td style="text-align: right; font-family: monospace;"><?= esc($r['i_less']) ?></td>
                        <td style="text-align: right; font-family: monospace;"><?= esc($r['i_net']) ?></td>
                        <td style="text-align: right; font-family: monospace;"><?= esc($r['i_purity']) ?></td>
                        <td style="text-align: right; font-family: monospace; font-weight: 600; border-right: 2px solid #cbd5e1; color: #991b1b; background: #fef2f2;">
                            <?= esc($r['i_fine_val']) ?>
                        </td>

                        <!-- Running Balances -->
                        <td style="text-align: right; font-family: monospace; font-weight: 700; color: <?= ((float)($r['run_metal'] ?? 0) >= 0) ? '#16a34a' : '#dc2626' ?>;">
                            <?= esc($r['run_metal']) ?>
                        </td>
                        <td style="text-align: right; font-family: monospace; font-weight: 600; color: #0f172a;">
                            <?= esc($r['run_cash']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="16" style="text-align: center; padding: 30px; color: #64748b;">
                            <i class="icon-file-empty" style="font-size: 28px; color: #cbd5e1; display: block; margin-bottom: 6px;"></i>
                            No transaction records found for this period.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background: #0f172a; color: #ffffff; font-weight: 700; font-size: 13px;">
                    <td colspan="6" style="border-right: 1px solid #334155;">CLOSING METAL BALANCE</td>
                    <td style="text-align: right; border-right: 2px solid #334155; font-family: monospace; color: #86efac;">
                        <?= esc($totals['display_closing_metal']) ?> g
                    </td>
                    <td colspan="6" style="border-right: 1px solid #334155;">CLOSING CASH BALANCE</td>
                    <td style="text-align: right; border-right: 2px solid #334155; font-family: monospace; color: #fde047;">
                        &#8377; <?= esc($totals['display_closing_rk']) ?>
                    </td>
                    <td style="text-align: right; font-family: monospace; color: #86efac;"><?= esc($totals['display_closing_metal']) ?></td>
                    <td style="text-align: right; font-family: monospace; color: #fde047;"><?= esc($totals['display_closing_rk']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#btnApproveCustomerStatement').on('click', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        if (typeof swal !== 'undefined') {
            swal({
                title: "Approve Statement?",
                text: "Are you sure you want to approve this statement?",
                type: "info",
                showCancelButton: true,
                confirmButtonColor: "#2563eb",
                confirmButtonText: "Yes, Approve",
                cancelButtonText: "Cancel",
                closeOnConfirm: false
            }, function(isConfirm) {
                if (isConfirm) {
                    window.location.href = url;
                }
            });
        } else {
            window.location.href = url;
        }
    });
});
</script>

<?= $this->endSection() ?>
