<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?><?= esc($accountName) ?> - Process Waves - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Process Waves / <?= esc($accountName) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header Section -->
<div class="sw-page-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
    <div class="sw-page-title-box">
        <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #0f172a;"><?= esc($accountName) ?></h1>
    </div>
</div>

<input type="hidden" id="process_account_id" value="<?= esc($accountId) ?>">
<input type="hidden" id="base_url" value="<?= base_url() ?>">

<!-- Main Dual-Column Grid Layout: Receipt (Left) & Issue (Right) -->
<div class="row">
    <!-- Left Column: Receipt Stock -->
    <div class="col-md-6 col-sm-12" style="margin-bottom: 24px;">
        <div class="sw-table-card">
            <!-- Header -->
            <div class="sw-table-header" style="height: 52px; min-height: 52px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between; padding: 10px 20px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-block; width: 10px; height: 10px; background-color: #2563eb; border-radius: 50%;"></span>
                    <h3 class="sw-table-title" style="margin: 0;">Receipt</h3>
                </div>
                <span class="sw-table-info">
                    <?= count($receiptData ?? []) ?> <?= !$isShowingAll ? 'shift entries' : ('of ' . ($totalReceiptRecords ?? count($receiptData)) . ' records') ?>
                </span>
            </div>

            <!-- Table Container -->
            <div class="sw-table-responsive">
                <table class="sw-table">
                    <thead>
                        <tr>
                            <th style="width: 38px; text-align: center;">#</th>
                            <th>Name</th>
                            <th>From</th>
                            <th style="text-align: right;">Weight</th>
                            <th style="text-align: right;">P(%)</th>
                            <th style="text-align: center;">Time</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($openingBalance != 0): ?>
                        <tr style="background-color: #eff6ff; font-weight: 600;">
                            <td colspan="2" style="color: #1d4ed8; font-weight: 700; white-space: nowrap;"><strong>Opening MT</strong></td>
                            <td colspan="5" style="color: #1d4ed8; font-weight: 700; text-align: left;">
                                <?= number_format(abs((float)$openingBalance), 3, '.', '') ?>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php if (!empty($receiptData)): ?>
                            <?php foreach ($receiptData as $row): ?>
                            <tr>
                                <td style="text-align: center; width: 38px; vertical-align: middle;">
                                    <span data-popup="tooltip" data-toggle="tooltip" data-placement="top" title="Transaction ID: <?= (int)$row['process_waves_id'] ?>" style="cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="icon-info22 text-muted" style="font-size: 13px;"></i>
                                    </span>
                                </td>
                                <td style="font-weight: 600; color: #0f172a;">
                                    <?= esc($row['mapped_cust_name'] ?: ($row['cust_name'] ?: '')) ?>
                                </td>
                                <td style="color: #475569;">
                                    <?= esc((!empty($row['loss_account_name']) ? ($row['loss_account_name'] . ' ') : '') . ($row['from_name'] ?: '')) ?>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #0f172a;">
                                    <?= number_format((float)$row['gross_weight'], 3, '.', '') ?>
                                </td>
                                <td style="text-align: right; color: #475569;">
                                    <?= number_format((float)$row['purity'], 2, '.', '') ?>
                                </td>
                                <td style="text-align: center; font-size: 12px; color: #64748b; white-space: nowrap;">
                                    <?= date('d/m/Y H:i:s', strtotime($row['date'])) ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if (can('process_waves.transfer') && $row['inner_transfer'] === '0' && (float)$row['gross_weight'] > 0.001): ?>
                                    <button type="button" 
                                            onclick="openTransferModal(<?= (int)$accountId ?>, <?= (int)$row['process_waves_id'] ?>);" 
                                            class="btn btn-default btn-xs" 
                                            style="padding: 3px 8px; border-radius: 4px;" 
                                            title="Transfer batch">
                                        <i class="icon-rocket text-success"></i>
                                    </button>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 11px;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 35px 20px; color: #64748b;">
                                    <i class="icon-inbox text-muted" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>
                                    There is no result for you.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f8fafc; border-top: 2px solid #e2e8f0; font-weight: 700;">
                            <td colspan="2" style="color: #0f172a; font-size: 13px; font-weight: 700; white-space: nowrap;">Fine MT</td>
                            <td colspan="5" style="text-align: left; color: #0f172a; font-size: 13px; font-weight: 700;">
                                <?= number_format((float)$receiveTotalBalance, 3, '.', '') ?>
                            </td>
                        </tr>
                        <?php if ($accountId === 4): // Filing Gross MT ?>
                        <tr style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-weight: 700;">
                            <td colspan="2" style="color: #0f172a; font-size: 13px; font-weight: 700; white-space: nowrap;">Gross MT</td>
                            <td colspan="5" style="text-align: left; color: #0f172a; font-size: 13px; font-weight: 700;">
                                <?= number_format((float)$receiveTotalGrossBalance, 3, '.', '') ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Issue Stock -->
    <div class="col-md-6 col-sm-12" style="margin-bottom: 24px;">
        <div class="sw-table-card">
            <!-- Header with NEW Transfer Button -->
            <div class="sw-table-header" style="height: 52px; min-height: 52px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between; padding: 10px 20px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-block; width: 10px; height: 10px; background-color: #ef4444; border-radius: 50%;"></span>
                    <h3 class="sw-table-title" style="margin: 0;">Issue</h3>
                </div>
                
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="sw-table-info">
                        <?= count($issueData ?? []) ?> <?= !$isShowingAll ? 'shift entries' : ('of ' . ($totalIssueRecords ?? count($issueData)) . ' records') ?>
                    </span>
                    <?php if (can('process_waves.transfer')): ?>
                    <button type="button" 
                            class="sw-btn-primary" 
                            style="padding: 6px 14px; font-size: 12px; font-weight: 700;" 
                            onclick="openTransferModal(<?= (int)$accountId ?>, null);">
                        <i class="icon-plus2" style="font-size: 11px;"></i> NEW
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Table Container -->
            <div class="sw-table-responsive">
                <table class="sw-table">
                    <thead>
                        <tr>
                            <th style="width: 38px; text-align: center;">#</th>
                            <th>Name</th>
                            <th style="text-align: right;">Weight</th>
                            <th style="text-align: right;">P(%)</th>
                            <th style="text-align: center;">Time</th>
                            <th>To</th>
                            <th style="text-align: center;">Print</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($issueData)): ?>
                            <?php foreach ($issueData as $row): ?>
                            <tr>
                                <td style="text-align: center; width: 38px; vertical-align: middle;">
                                    <span data-popup="tooltip" data-toggle="tooltip" data-placement="top" title="Transaction ID: <?= (int)$row['process_waves_id'] ?>" style="cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="icon-info22 text-muted" style="font-size: 13px;"></i>
                                    </span>
                                </td>
                                <td style="font-weight: 600; color: #0f172a;">
                                    <?= esc($row['mapped_cust_name'] ?: ($row['cust_name'] ?: '')) ?>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #0f172a;">
                                    <?= number_format((float)$row['gross_weight'], 3, '.', '') ?>
                                </td>
                                <td style="text-align: right; color: #475569;">
                                    <?= number_format((float)$row['purity'], 2, '.', '') ?>
                                </td>
                                <td style="text-align: center; font-size: 12px; color: #64748b; white-space: nowrap;">
                                    <?= date('d/m/Y H:i:s', strtotime($row['date'])) ?>
                                </td>
                                <td>
                                    <span class="label label-primary" style="font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 4px;">
                                        <?= esc($row['to_name'] ?: 'cust') ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if (can('process_waves.print')): ?>
                                    <a href="javascript:void(0);" 
                                       onclick="window.open('<?= base_url('process-waves/print-transaction/' . $row['process_waves_id']) ?>', 'popup', 'width=650,height=650'); return false;" 
                                       class="btn btn-default btn-xs" 
                                       style="padding: 3px 8px; border-radius: 4px;" 
                                       title="Print Slip">
                                        <i class="icon-printer2 text-primary"></i>
                                    </a>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 11px;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 35px 20px; color: #64748b;">
                                    <i class="icon-inbox text-muted" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>
                                    There is no result for you.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f8fafc; border-top: 2px solid #e2e8f0; font-weight: 700;">
                            <td colspan="2" style="color: #0f172a; font-size: 13px; font-weight: 700; white-space: nowrap;">Fine MT</td>
                            <td colspan="5" style="text-align: left; color: #0f172a; font-size: 13px; font-weight: 700;">
                                <?= number_format((float)$issueTotalBalance, 3, '.', '') ?>
                            </td>
                        </tr>
                        <?php if ($accountId === 4): // Filing Gross MT ?>
                        <tr style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-weight: 700;">
                            <td colspan="2" style="color: #0f172a; font-size: 13px; font-weight: 700; white-space: nowrap;">Gross MT</td>
                            <td colspan="5" style="text-align: left; color: #0f172a; font-size: 13px; font-weight: 700;">
                                <?= number_format((float)$issueTotalGrossBalance, 3, '.', '') ?>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <tr style="<?= ((float)$balance < 0) ? 'background-color: #fee2e2; border-top: 1px solid #fca5a5;' : 'background-color: #eff6ff; border-top: 1px solid #bfdbfe;' ?> font-weight: 700;">
                            <td colspan="2" style="<?= ((float)$balance < 0) ? 'color: #b91c1c;' : 'color: #1e40af;' ?> font-size: 13px; font-weight: 700; white-space: nowrap;">Fine MT Balance</td>
                            <td colspan="5" style="text-align: left; <?= ((float)$balance < 0) ? 'color: #b91c1c;' : 'color: #2563eb;' ?> font-size: 13px; font-weight: 700;" id="get_gross_weight">
                                <?= number_format((float)$balance, 3, '.', '') ?>
                            </td>
                        </tr>

                        <?php if ($accountId === 4): // Filing Gross MT Balance ?>
                        <tr style="<?= ((float)$grossBalance < 0) ? 'background-color: #fee2e2; border-top: 1px solid #fca5a5;' : 'background-color: #eff6ff; border-top: 1px solid #bfdbfe;' ?> font-weight: 700;">
                            <td colspan="2" style="<?= ((float)$grossBalance < 0) ? 'color: #b91c1c;' : 'color: #1e40af;' ?> font-size: 13px; font-weight: 700; white-space: nowrap;">Gross MT Balance</td>
                            <td colspan="5" style="text-align: left; <?= ((float)$grossBalance < 0) ? 'color: #b91c1c;' : 'color: #2563eb;' ?> font-size: 13px; font-weight: 700;">
                                <?= number_format((float)$grossBalance, 3, '.', '') ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (can('process_waves.print')): ?>
<!-- Print Process Waves Sheet Action Button -->
<div class="row" style="margin-top: 4px; margin-bottom: 24px;">
    <div class="col-xs-12">
        <a href="#" target="popup" onclick="window.open('<?= base_url('process_waves/print_process_waves/' . $accountPrivateKey); ?>','popup','width=1000,height=800'); return false;" class="btn btn-primary btn-xs" style="padding: 6px 16px; font-size: 12.5px; font-weight: 600; border-radius: 3px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.06);">
            Print
        </a>
    </div>
</div>
<?php endif; ?>

<?php if ($isShowingAll && (max($totalReceiptRecords ?? 0, $totalIssueRecords ?? 0) > $limit)): 
    $totalPages = (int) ceil(max($totalReceiptRecords, $totalIssueRecords) / $limit);
?>
<!-- Pagination Controls for All History View -->
<div style="display: flex; justify-content: center; align-items: center; gap: 12px; margin-bottom: 30px;">
    <?php if ($page > 1): ?>
        <a href="<?= base_url('process-waves/manage/' . $accountPrivateKey . '?filter=all&page=' . ($page - 1) . '&limit=' . $limit) ?>" class="btn btn-default btn-sm">
            <i class="icon-arrow-left7 position-left"></i> Previous
        </a>
    <?php endif; ?>

    <span style="font-size: 13px; color: #64748b; font-weight: 600;">
        Page <?= $page ?> of <?= $totalPages ?>
    </span>

    <?php if ($page < $totalPages): ?>
        <a href="<?= base_url('process-waves/manage/' . $accountPrivateKey . '?filter=all&page=' . ($page + 1) . '&limit=' . $limit) ?>" class="btn btn-default btn-sm">
            Next <i class="icon-arrow-right7 position-right"></i>
        </a>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Dynamic Transfer Modal Container -->
<div class="modal fade" id="ajaxTransferModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content" id="transferModalContent" style="border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
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

function openTransferModal(fromAccountId, processWavesId) {
    var url = '<?= base_url('process-waves/transfer') ?>/' + fromAccountId + (processWavesId ? '/' + processWavesId : '');
    $('#transferModalContent').html('<div style="padding: 40px; text-align: center;"><i class="icon-spinner2 spinner position-left" style="font-size: 24px; color: #2563eb;"></i><p style="margin-top: 12px; color: #64748b;">Loading transfer details...</p></div>');
    $('#ajaxTransferModal').modal('show');
    
    $.get(url, function(html) {
        $('#transferModalContent').html(html);
    }).fail(function() {
        $('#transferModalContent').html('<div class="alert alert-danger" style="margin: 20px;">Unable to load transfer form. Please try again.</div>');
    });
}
</script>

<?= $this->endSection() ?>
