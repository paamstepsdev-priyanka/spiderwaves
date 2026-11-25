<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Refining - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Refining<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header Section -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #0f172a;"><?= esc($accountName) ?></h1>
    </div>

    <div class="sw-page-actions" style="display: flex; align-items: center; gap: 10px;">
        <span class="sw-pill-badge" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 12px; padding: 6px 12px;">
            <i class="icon-calendar52"></i> Shift Start: <?= esc(date('d/m/Y H:i', strtotime($startDate))) ?>
        </span>
    </div>
</div>

<input type="hidden" id="process_account_id" value="<?= esc($accountId) ?>">
<input type="hidden" id="base_url" value="<?= base_url() ?>">



<!-- Main Dual-Column Grid Layout: Receipt (Left) & Issue (Right) -->
<div class="row">
    <!-- Left Column: Receipt Stock (Incoming Loss Batches) -->
    <div class="col-md-6 col-sm-12" style="margin-bottom: 24px;">
        <div class="sw-table-card">
            <!-- Header -->
            <div class="sw-table-header" style="height: 52px; min-height: 52px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between; padding: 10px 20px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-block; width: 10px; height: 10px; background-color: #2563eb; border-radius: 50%;"></span>
                    <h3 class="sw-table-title" style="margin: 0;">Receipt</h3>
                </div>
                <span class="sw-table-info">
                    <?= count($receiptData ?? []) ?> records
                </span>
            </div>

            <!-- Table Container -->
            <div class="sw-table-responsive" style="max-height: 580px; overflow-y: auto;">
                <table class="sw-table">
                    <thead>
                        <tr>
                            <th style="width: 38px; text-align: center;">#</th>
                            <th>From</th>
                            <th style="text-align: right;">Weight</th>
                            <th style="text-align: right;">P(%)</th>
                            <th style="text-align: center;">Time</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($openingFine != 0): ?>
                        <tr style="background-color: #eff6ff; font-weight: 600;">
                            <td colspan="2" style="color: #1d4ed8;"><strong>Opening MT</strong></td>
                            <td colspan="4" style="color: #1d4ed8; font-weight: 700;">
                                <?= number_format(abs((float)$openingFine), 3, '.', '') ?>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php if (!empty($receiptData)): ?>
                            <?php foreach ($receiptData as $row): ?>
                            <?php
                                $gw = abs((float)$row['gross_weight']);
                                $purity = (float)$row['purity'];
                                $fineMt = abs(($gw * $purity) / 100);
                                $isTransferred = ((string)$row['inner_transfer'] === '1');
                                $fromDept = !empty($row['loss_account_name']) ? $row['loss_account_name'] : (!empty($row['from_name']) ? $row['from_name'] : 'Loss Consolidation');
                            ?>
                            <tr id="pw_row_<?= esc($row['process_waves_id']) ?>" style="<?= $isTransferred ? 'opacity: 0.75;' : '' ?>">
                                <td style="text-align: center; width: 38px; vertical-align: middle;">
                                    <span data-popup="tooltip" data-toggle="tooltip" data-placement="top" title="Transaction ID: <?= (int)$row['process_waves_id'] ?>" style="cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="icon-info22 text-muted" style="font-size: 13px;"></i>
                                    </span>
                                </td>
                                <td style="font-weight: 600; color: #0f172a;">
                                    <span class="sw-pill-badge" style="background-color: #f1f5f9; color: #334155; font-size: 11px;">
                                        <?= esc($fromDept) ?>
                                    </span>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #0f172a;">
                                    <?= number_format($gw, 3, '.', '') ?>
                                </td>
                                <td style="text-align: right; color: #475569;">
                                    <?= number_format($purity, 2, '.', '') ?>
                                </td>
                                <td style="text-align: center; font-size: 12px; color: #64748b; white-space: nowrap;">
                                    <?= date('d/m/Y H:i', strtotime($row['date'])) ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if (!$isTransferred && $gw > 0.001): ?>
                                    <button type="button" 
                                            onclick="openRefiningTransferModal(<?= (int)$row['process_waves_id'] ?>);" 
                                            class="btn btn-default btn-xs" 
                                            style="padding: 3px 8px; border-radius: 4px;" 
                                            title="Transfer Refined Gold to Locker">
                                        <i class="icon-rocket text-success"></i>
                                    </button>
                                    <?php else: ?>
                                    <span class="sw-pill-badge" style="background-color: #f8fafc; color: #94a3b8; border: 1px solid #e2e8f0; font-size: 10px;">
                                        <i class="icon-checkmark4"></i> Transferred
                                    </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 35px 20px; color: #64748b;">
                                    <i class="icon-inbox text-muted" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>
                                    There is no receipt stock recorded for Refining.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot style="position: sticky; bottom: 0; z-index: 10;">
                        <tr style="background-color: #ffffff; font-weight: 700;">
                            <td colspan="2" style="position: sticky; bottom: 0; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700; border-top: 2px solid #cbd5e1; box-shadow: 0 -3px 8px rgba(0,0,0,0.06); z-index: 10; white-space: nowrap;">Fine MT</td>
                            <td colspan="4" style="position: sticky; bottom: 0; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700; text-align: left; border-top: 2px solid #cbd5e1; box-shadow: 0 -3px 8px rgba(0,0,0,0.06); z-index: 10;">
                                <?= number_format(abs((float)$receiveTotalFine), 3, '.', '') ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Issue Stock (Refined Issues to Locker) -->
    <div class="col-md-6 col-sm-12" style="margin-bottom: 24px;">
        <div class="sw-table-card">
            <!-- Header -->
            <div class="sw-table-header" style="height: 52px; min-height: 52px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between; padding: 10px 20px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-block; width: 10px; height: 10px; background-color: #ef4444; border-radius: 50%;"></span>
                    <h3 class="sw-table-title" style="margin: 0;">Issue</h3>
                </div>
                <span class="sw-table-info">
                    <?= count($issueData ?? []) ?> records
                </span>
            </div>

            <!-- Table Container -->
            <div class="sw-table-responsive" style="max-height: 580px; overflow-y: auto;">
                <table class="sw-table">
                    <thead>
                        <tr>
                            <th style="width: 38px; text-align: center;">#</th>
                            <th>Weight</th>
                            <th style="text-align: right;">P(%)</th>
                            <th style="text-align: center;">Time</th>
                            <th>To</th>
                            <th style="text-align: right;">Loss</th>
                            <th style="text-align: center;">Print</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($issueData)): ?>
                            <?php foreach ($issueData as $row): ?>
                            <?php
                                $gw = abs((float)$row['gross_weight']);
                                $purity = (float)$row['purity'];
                                $netLoss = isset($row['net_loss']) ? abs((float)$row['net_loss']) : 0.0;
                                $toName = !empty($row['to_name']) ? $row['to_name'] : 'Locker';
                            ?>
                            <tr>
                                <td style="text-align: center; width: 38px; vertical-align: middle;">
                                    <span data-popup="tooltip" data-toggle="tooltip" data-placement="top" title="Transaction ID: <?= (int)$row['process_waves_id'] ?>" style="cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="icon-info22 text-muted" style="font-size: 13px;"></i>
                                    </span>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #0f172a;">
                                    <?= number_format($gw, 3, '.', '') ?>
                                </td>
                                <td style="text-align: right; color: #475569;">
                                    <?= number_format($purity, 2, '.', '') ?>
                                </td>
                                <td style="text-align: center; font-size: 12px; color: #64748b; white-space: nowrap;">
                                    <?= date('d/m/Y H:i', strtotime($row['date'])) ?>
                                </td>
                                <td>
                                    <span class="label label-primary" style="font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 4px;">
                                        <?= esc($toName) ?>
                                    </span>
                                </td>
                                <td style="text-align: right; font-weight: 600; color: #d97706;">
                                    <?= number_format($netLoss, 3, '.', '') ?>
                                </td>
                                <td style="text-align: center;">
                                    <a href="javascript:void(0);" 
                                       onclick="window.open('<?= base_url('process-waves/print-transaction/' . $row['process_waves_id']) ?>', 'popup', 'width=650,height=650'); return false;" 
                                       class="btn btn-default btn-xs" 
                                       style="padding: 3px 8px; border-radius: 4px;" 
                                       title="Print Refining Slip">
                                        <i class="icon-printer2 text-primary"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 35px 20px; color: #64748b;">
                                    <i class="icon-inbox text-muted" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>
                                    There is no issue stock recorded for Refining.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot style="position: sticky; bottom: 0; z-index: 10;">
                        <tr style="background-color: #ffffff; font-weight: 700;">
                            <td colspan="2" style="position: sticky; bottom: 37px; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700; border-top: 2px solid #cbd5e1; box-shadow: 0 -3px 8px rgba(0,0,0,0.06); z-index: 11; white-space: nowrap;">Fine MT</td>
                            <td colspan="5" style="position: sticky; bottom: 37px; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700; text-align: left; border-top: 2px solid #cbd5e1; box-shadow: 0 -3px 8px rgba(0,0,0,0.06); z-index: 11;">
                                <?= number_format(abs((float)$issueTotalFine), 3, '.', '') ?>
                            </td>
                        </tr>

                        <tr style="background-color: #eff6ff; font-weight: 700;">
                            <td colspan="2" style="position: sticky; bottom: 0; background: #eff6ff; color: #1e40af; font-size: 13px; font-weight: 700; border-top: 1px solid #bfdbfe; z-index: 10; white-space: nowrap;">Fine MT Balance</td>
                            <td colspan="5" style="position: sticky; bottom: 0; background: #eff6ff; color: #2563eb; font-size: 13px; font-weight: 700; text-align: left; border-top: 1px solid #bfdbfe; z-index: 10;" id="get_gross_weight">
                                <?= number_format(abs((float)$fineBalance), 3, '.', '') ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic Transfer Modal Container -->
<div class="modal fade" id="ajaxRefiningTransferModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content" id="refiningModalContent" style="border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <!-- Dynamically populated via AJAX -->
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($.fn.tooltip) {
        $('[data-popup="tooltip"], [data-toggle="tooltip"]').tooltip();
    }
});

function openRefiningTransferModal(processWavesId) {
    var url = '<?= base_url('refining/transfer-modal') ?>/' + processWavesId;
    $('#refiningModalContent').html('<div style="padding: 40px; text-align: center;"><i class="icon-spinner2 spinner position-left" style="font-size: 24px; color: #2563eb;"></i><p style="margin-top: 12px; color: #64748b;">Loading batch details...</p></div>');
    $('#ajaxRefiningTransferModal').modal('show');
    
    $.get(url, function(html) {
        $('#refiningModalContent').html(html);
    }).fail(function() {
        $('#refiningModalContent').html('<div class="alert alert-danger" style="margin: 20px;">Unable to load refining transfer form. Please try again.</div>');
    });
}
</script>

<?= $this->endSection() ?>
