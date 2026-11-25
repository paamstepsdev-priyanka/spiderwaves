<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Locker - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Locker<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header Section -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #0f172a;"><?= esc($accountName) ?></h1>
    </div>

    <div class="sw-page-actions">
        <!-- Check Stock Button (Only button, exactly matching CI2 Locker) -->
        <?php if (can('locker.check_stock')): ?>
        <button type="button" class="sw-btn-primary" id="btnCheckStock" onclick="check_stock('<?= date('Y-m-d H:i:s') ?>');">
            <i class="icon-check"></i>
            <span>Check Stock</span>
        </button>
        <?php endif; ?>
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
                    <?= count($receiptData ?? []) ?> records
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
                            <tr id="pw_row_<?= esc($row['process_waves_id']) ?>">
                                <td style="text-align: center; width: 38px; vertical-align: middle;">
                                    <span data-popup="tooltip" data-toggle="tooltip" data-placement="top" title="Transaction ID: <?= (int)$row['process_waves_id'] ?>" style="cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="icon-info22 text-muted" style="font-size: 13px;"></i>
                                    </span>
                                </td>
                                <td style="font-weight: 600; color: #0f172a;">
                                    <?= esc($row['mapped_cust_name'] ?: ($row['cust_name'] ?: 'N/A')) ?>
                                </td>
                                <td style="color: #475569;">
                                    <?= esc($row['from_name'] ?: 'N/A') ?>
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
                                    <?php if (can('locker.transfer') && (string)($row['inner_transfer'] ?? '') === '0' && (float)$row['gross_weight'] > 0.001): ?>
                                    <button type="button" 
                                            onclick="openTransferModal(<?= (int)$row['to_id'] ?>, <?= (int)$row['process_waves_id'] ?>);" 
                                            class="btn btn-default btn-xs" 
                                            style="padding: 3px 8px; border-radius: 4px;" 
                                            title="Transfer into Workshop">
                                        <i class="icon-rocket text-success"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 35px 20px; color: #64748b;">
                                    <i class="icon-inbox text-muted" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>
                                    There is no receipt stock recorded for Locker.
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
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Issue Stock -->
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
                                    <?= esc($row['mapped_cust_name'] ?: ($row['cust_name'] ?: 'N/A')) ?>
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
                                        <?= esc($row['to_name'] ?: 'Customer') ?>
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
                                    There is no issue stock recorded.
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

                        <tr style="<?= ((float)$fineMtBalance < 0) ? 'background-color: #fee2e2; border-top: 1px solid #fca5a5;' : 'background-color: #eff6ff; border-top: 1px solid #bfdbfe;' ?> font-weight: 700;">
                            <td colspan="2" style="<?= ((float)$fineMtBalance < 0) ? 'color: #b91c1c;' : 'color: #1e40af;' ?> font-size: 13px; font-weight: 700; white-space: nowrap;">Fine MT Balance</td>
                            <td colspan="5" style="text-align: left; <?= ((float)$fineMtBalance < 0) ? 'color: #b91c1c;' : 'color: #2563eb;' ?> font-size: 13px; font-weight: 700;" id="get_gross_weight">
                                <?= number_format((float)$fineMtBalance, 3, '.', '') ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Print Locker Sheet Action Button (CI2 Parity) -->
<div class="row" style="margin-top: 4px; margin-bottom: 24px;">
    <div class="col-xs-12">
        <a href="javascript:void(0);" 
           target="popup" 
           onclick="window.open('<?= base_url('process_waves/print_process_waves/' . $accountPrivateKey) ?>', 'popup', 'width=1000,height=800'); return false;" 
           class="btn btn-primary btn-xs" 
           style="padding: 6px 16px; font-size: 12.5px; font-weight: 600; border-radius: 3px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.06);">
            Print
        </a>
    </div>
</div>

<!-- Dynamic Transfer Modal Container -->
<div class="modal fade" id="ajaxTransferModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content" id="transferModalContent">
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
    var url = '<?= base_url('process-waves/transfer') ?>/' + fromAccountId + '/' + processWavesId;
    $('#transferModalContent').html('<div class="modal-body text-center"><i class="icon-spinner2 spinner position-left"></i><p class="text-muted">Loading transfer details...</p></div>');
    $('#ajaxTransferModal').modal('show');
    
    $.get(url, function(html) {
        $('#transferModalContent').html(html);
    }).fail(function() {
        $('#transferModalContent').html('<div class="alert alert-danger">Unable to load transfer form. Please try again.</div>');
    });
}

function check_stock(date) {
    var checkUrl = '<?= base_url('locker/check-stock') ?>';
    $.post(checkUrl, function(data) {
        if (typeof data === 'string') {
            try { data = JSON.parse(data); } catch (e) {}
        }
        if (data.status === 'pcs_error') {
            swal({
                title: "Error!",
                text: "Pc's Error in " + data.msg + " !",
                confirmButtonColor: "#EF5350",
                type: "error"
            });
            return;
        }
        if (data.status === 'loss_percentage_error') {
            swal({
                title: "Error!",
                text: "Loss Percentage Error in " + data.msg + " !",
                confirmButtonColor: "#EF5350",
                type: "error"
            });
            return;
        }
        if (data.status === 'true') {
            insert_check_point(date);
        }
    }, 'json').fail(function() {
        swal({
            title: "Network Error",
            text: "Could not execute stock validation.",
            type: "error",
            confirmButtonColor: "#EF5350"
        });
    });
}

function insert_check_point(date) {
    var insertUrl = '<?= base_url('process-waves/insert-checkpoint') ?>';
    $.post(insertUrl, {
        end_date_time: date
    }, function() {
        swal({
            title: "Success!",
            text: "Stock Point Created Successfully!",
            confirmButtonColor: "#66BB6A",
            type: "success",
            closeOnConfirm: true
        }, function() {
            window.location.reload();
        });
    });
}

function triggerCheckStock(dateTime) {
    check_stock(dateTime);
}
</script>

<?= $this->endSection() ?>
