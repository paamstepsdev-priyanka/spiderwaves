<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?><?= esc($lossTitle) ?> - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Loss / <?= esc($lossTitle) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header Section -->
<div class="sw-page-header" style="margin-bottom: 20px;">
    <div class="sw-page-title-box">
        <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #0f172a;"><?= esc($lossTitle) ?></h1>
    </div>
</div>

<input type="hidden" id="source_dept_id" value="<?= esc($sourceDeptId) ?>">
<input type="hidden" id="base_url" value="<?= base_url() ?>">

<!-- Main Dual-Column Grid Layout: Receipt (Left) & Issue (Right) matching Legacy CI2 Structure -->
<div class="row">
    <!-- Left Column: Receipt Table (Pending Loss Batches) -->
    <div class="col-md-6 col-sm-12" style="margin-bottom: 24px;">
        <div class="sw-table-card">
            <!-- Header -->
            <div class="sw-table-header" style="height: auto; min-height: 54px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-block; width: 10px; height: 10px; background-color: #2563eb; border-radius: 50%;"></span>
                    <h3 class="sw-table-title" style="margin: 0; font-size: 15px;">Receipt <span style="font-size: 12px; font-weight: 500; color: #64748b;">(Loss Receipts)</span></h3>
                    <span class="sw-table-info" style="font-size: 12px; color: #64748b;">
                        (<?= count($receiptData) ?> <?= $onlyPending ? 'pending' : 'total' ?>)
                    </span>
                </div>
            </div>

            <!-- Table Container -->
            <div class="sw-table-responsive" style="max-height: 600px; overflow-y: auto;">
                <form id="loss_account_transefer_form" novalidate>
                    <input type="hidden" id="from_account_id" value="<?= esc($sourceDeptId) ?>">
                    <table class="sw-table" id="receiptLossTable">
                        <thead>
                            <tr>
                                <th style="position: sticky; top: 0; background: #f8fafc; z-index: 10;">From</th>
                                <th style="position: sticky; top: 0; background: #f8fafc; text-align: right; z-index: 10;">Weight</th>
                                <th style="position: sticky; top: 0; background: #f8fafc; text-align: right; z-index: 10;">P(%)</th>
                                <th style="position: sticky; top: 0; background: #f8fafc; text-align: center; z-index: 10;">Time</th>
                                <th style="position: sticky; top: 0; background: #f8fafc; width: 48px; text-align: center; vertical-align: middle; z-index: 10;"></th>
                                <?php if ($isShowingAll): ?>
                                    <th style="position: sticky; top: 0; background: #f8fafc; text-align: center; z-index: 10;">Status</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($receiptData)): ?>
                                <?php foreach ($receiptData as $row): ?>
                                <?php 
                                    $isPending = empty($row['further_to_process_waves_id']);
                                    $gross = abs((float)$row['gross_weight']);
                                    $purity = (float)$row['purity'];
                                    $fine = abs($gross * $purity / 100);
                                    $fromDept = !empty($row['from_name']) ? $row['from_name'] : $accountName;
                                ?>
                                <tr id="loss_row_<?= esc($row['process_waves_id']) ?>" style="<?= !$isPending ? 'opacity: 0.6; background-color: #f8fafc;' : '' ?>">
                                    <td style="color: #475569; font-size: 13px; font-weight: 600;">
                                        <?= esc($fromDept) ?> <span style="font-size: 11px; font-weight: 500; color: #94a3b8;">#<?= esc($row['process_waves_id']) ?></span>
                                    </td>
                                    <td style="text-align: right; font-weight: 700; color: #0f172a;">
                                        <?= number_format($gross, 3, '.', '') ?>
                                    </td>
                                    <td style="text-align: right; color: #475569;">
                                        <?= number_format($purity, 2, '.', '') ?>
                                    </td>
                                    <td style="text-align: center; font-size: 12px; color: #64748b; white-space: nowrap;">
                                        <?= date('d/m/Y H:i:s', strtotime($row['date'])) ?>
                                    </td>
                                    <td style="text-align: center; vertical-align: middle;">
                                        <?php if ($isPending): ?>
                                            <input type="checkbox" 
                                                   name="process_waves[]"
                                                   class="loss-batch-checkbox" 
                                                   value="<?= (int)$row['process_waves_id'] ?>" 
                                                   data-gross="<?= $gross ?>" 
                                                   data-fine="<?= $fine ?>"
                                                   data-dept="<?= (int)$row['from_id'] ?>"
                                                   style="width: 19px; height: 19px; cursor: pointer; accent-color: #2563eb; vertical-align: middle;"
                                                   onchange="onBatchCheckboxChange();">
                                        <?php else: ?>
                                            <i class="icon-checkmark4 text-success" style="font-size: 16px;" title="Consolidated & Transferred"></i>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($isShowingAll): ?>
                                    <td style="text-align: center;">
                                        <?php if ($isPending): ?>
                                            <span class="label label-warning" style="font-size: 10px;">Pending</span>
                                        <?php else: ?>
                                            <span class="label label-success" style="font-size: 10px;">Refined #<?= esc($row['further_to_process_waves_id']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?= $isShowingAll ? 6 : 5 ?>" style="text-align: center; padding: 30px; color: #94a3b8;">
                                        <i class="icon-inbox" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>
                                        There is no result for you.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot style="position: sticky; bottom: 0; z-index: 10;">
                            <tr style="background: #ffffff; font-weight: 700;">
                                <td style="position: sticky; bottom: 0; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700; border-top: 2px solid #cbd5e1; box-shadow: 0 -3px 8px rgba(0,0,0,0.06); z-index: 10; white-space: nowrap;">Fine MT</td>
                                <td style="position: sticky; bottom: 0; background: #ffffff; text-align: left; color: #0f172a; font-size: 13px; font-weight: 700; border-top: 2px solid #cbd5e1; box-shadow: 0 -3px 8px rgba(0,0,0,0.06); z-index: 10;">
                                    <?= number_format((float)$totalReceiveFine, 3, '.', '') ?>
                                </td>
                                <td colspan="<?= $isShowingAll ? 4 : 3 ?>" style="position: sticky; bottom: 0; background: #ffffff; text-align: right; border-top: 2px solid #cbd5e1; box-shadow: 0 -3px 8px rgba(0,0,0,0.06); z-index: 10;">
                                    <?php if (can('loss.consolidate')): ?>
                                    <button type="button" 
                                            class="btn btn-success" 
                                            id="btnTransferRefiningInline" 
                                            onclick="openConsolidateModal();" 
                                            disabled>
                                        Transfer
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Issue Table (Transfers from Loss to Refining) -->
    <div class="col-md-6 col-sm-12" style="margin-bottom: 24px;">
        <div class="sw-table-card">
            <!-- Header -->
            <div class="sw-table-header" style="height: auto; min-height: 54px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between; padding: 10px 16px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-block; width: 10px; height: 10px; background-color: #059669; border-radius: 50%;"></span>
                    <h3 class="sw-table-title" style="margin: 0; font-size: 15px;">Issue <span style="font-size: 12px; font-weight: 500; color: #64748b;">(Transfers to Refining)</span></h3>
                </div>
                <span class="sw-table-info" style="font-size: 12px; color: #64748b;">
                    <?= count($issueData) ?> records
                </span>
            </div>

            <!-- Table Container -->
            <div class="sw-table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="sw-table" id="issueLossTable">
                    <thead>
                        <tr>
                            <th style="position: sticky; top: 0; background: #f8fafc; text-align: right; z-index: 10;">Weight</th>
                            <th style="position: sticky; top: 0; background: #f8fafc; text-align: right; z-index: 10;">P(%)</th>
                            <th style="position: sticky; top: 0; background: #f8fafc; text-align: center; z-index: 10;">Time</th>
                            <th style="position: sticky; top: 0; background: #f8fafc; z-index: 10;">To</th>
                            <th style="position: sticky; top: 0; background: #f8fafc; text-align: center; z-index: 10;">Print</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($issueData)): ?>
                            <?php foreach ($issueData as $row): ?>
                            <?php 
                                $gross = abs((float)$row['gross_weight']);
                                $purity = (float)$row['purity'];
                                $fine = abs($gross * $purity / 100);
                            ?>
                            <tr id="issue_row_<?= esc($row['process_waves_id']) ?>">
                                <td style="text-align: right; font-weight: 700; color: #0f172a;">
                                    <span style="font-size: 11px; font-weight: 500; color: #94a3b8; display: block;">#<?= esc($row['process_waves_id']) ?></span>
                                    <?= number_format($gross, 3, '.', '') ?>
                                </td>
                                <td style="text-align: right; color: #475569;">
                                    <?= number_format($purity, 2, '.', '') ?>
                                </td>
                                <td style="text-align: center; font-size: 12px; color: #64748b; white-space: nowrap;">
                                    <?= date('d/m/Y H:i:s', strtotime($row['date'])) ?>
                                </td>
                                <td>
                                    <span class="label label-primary" style="font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 4px;">
                                        <?= esc($row['to_name'] ?: 'Refining') ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if (can('loss.print')): ?>
                                    <a href="javascript:void(0);" 
                                       onclick="window.open('<?= base_url('process-waves/print-transaction/' . $row['process_waves_id']) ?>', 'popup', 'width=650,height=650'); return false;" 
                                       class="btn btn-default btn-xs" 
                                       style="padding: 3px 8px; border-radius: 4px;" 
                                       title="Print Transfer Slip">
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
                                <td colspan="5" style="text-align: center; padding: 30px; color: #94a3b8;">
                                    <i class="icon-inbox" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>
                                    There is no result for you.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($issueData)): ?>
                    <tfoot style="position: sticky; bottom: 0; z-index: 10;">
                        <tr style="background: #ffffff; font-weight: 700;">
                            <td style="position: sticky; bottom: 37px; background: #ffffff; color: #0f172a; font-size: 13px; font-weight: 700; border-top: 2px solid #cbd5e1; box-shadow: 0 -3px 8px rgba(0,0,0,0.06); z-index: 11; white-space: nowrap;">Fine MT</td>
                            <td colspan="4" style="position: sticky; bottom: 37px; background: #ffffff; text-align: left; color: #0f172a; font-size: 13px; font-weight: 700; border-top: 2px solid #cbd5e1; box-shadow: 0 -3px 8px rgba(0,0,0,0.06); z-index: 11;">
                                <?= number_format((float)$totalIssueFine, 3, '.', '') ?>
                            </td>
                        </tr>
                        <tr style="<?= ((float)$fineMtBalance < 0) ? 'background-color: #fee2e2; border-top: 1px solid #fca5a5;' : 'background-color: #eff6ff; border-top: 1px solid #bfdbfe;' ?> font-weight: 700;">
                            <td style="position: sticky; bottom: 0; <?= ((float)$fineMtBalance < 0) ? 'background: #fee2e2; color: #b91c1c;' : 'background: #eff6ff; color: #1e40af;' ?> font-size: 13px; font-weight: 700; border-top: 1px solid <?= ((float)$fineMtBalance < 0) ? '#fca5a5' : '#bfdbfe' ?>; z-index: 10; white-space: nowrap;">Fine MT Balance</td>
                            <td colspan="4" style="text-align: left; position: sticky; bottom: 0; <?= ((float)$fineMtBalance < 0) ? 'background: #fee2e2; color: #b91c1c;' : 'background: #eff6ff; color: #2563eb;' ?> font-size: 13px; font-weight: 700; border-top: 1px solid <?= ((float)$fineMtBalance < 0) ? '#fca5a5' : '#bfdbfe' ?>; z-index: 10;">
                                <?= number_format((float)$fineMtBalance, 3, '.', '') ?>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (can('loss.print')): ?>
<!-- Print Loss Sheet Action Button below tables (CI2 Parity) -->
<div class="row" style="margin-top: 4px; margin-bottom: 24px;">
    <div class="col-xs-12">
        <a href="javascript:void(0);" 
           target="popup" 
           onclick="window.open('<?= base_url('loss/print/' . $departmentKey) ?>', 'popup', 'width=1000,height=800'); return false;" 
           class="btn btn-primary btn-xs" 
           id="btnPrintLossSheet" 
           style="padding: 6px 16px; font-size: 12.5px; font-weight: 600; border-radius: 3px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.06);">
            Print
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Modal Container -->
<div id="lossModalContainer"></div>

<script type="text/javascript">
function onBatchCheckboxChange() {
    var checkedBoxes = document.querySelectorAll('.loss-batch-checkbox:checked');
    var count = checkedBoxes.length;
    var totalGross = 0.0;
    var totalFine = 0.0;

    checkedBoxes.forEach(function(cb) {
        var g = parseFloat(cb.getAttribute('data-gross')) || 0.0;
        var f = parseFloat(cb.getAttribute('data-fine')) || 0.0;
        totalGross += g;
        totalFine += f;
    });

    var summaryBadge = document.getElementById('selectionSummary');
    var countSpan = document.getElementById('selectedCount');
    var grossSpan = document.getElementById('selectedGross');
    var fineSpan = document.getElementById('selectedFine');
    var transferBtnInline = document.getElementById('btnTransferRefiningInline');
    var transferBtnBottom = document.getElementById('btnTransferRefiningBottom');

    if (count > 0) {
        if (summaryBadge) summaryBadge.style.display = 'inline-block';
        if (countSpan) countSpan.textContent = count;
        if (grossSpan) grossSpan.textContent = totalGross.toFixed(3);
        if (fineSpan) fineSpan.textContent = totalFine.toFixed(3);

        if (transferBtnInline) transferBtnInline.removeAttribute('disabled');
        if (transferBtnBottom) transferBtnBottom.removeAttribute('disabled');
    } else {
        if (summaryBadge) summaryBadge.style.display = 'none';
        if (transferBtnInline) transferBtnInline.setAttribute('disabled', 'disabled');
        if (transferBtnBottom) transferBtnBottom.setAttribute('disabled', 'disabled');
    }
}

function openConsolidateModal() {
    var checkedBoxes = document.querySelectorAll('.loss-batch-checkbox:checked');
    if (checkedBoxes.length === 0) {
        swal({
            title: "No Batches Selected",
            text: "Please select at least one pending loss batch from the list.",
            type: "warning"
        });
        return;
    }

    var batchIds = [];
    var deptId = document.getElementById('source_dept_id').value;

    checkedBoxes.forEach(function(cb) {
        batchIds.push(cb.value);
        if (!deptId) {
            deptId = cb.getAttribute('data-dept');
        }
    });

    var baseUrl = document.getElementById('base_url').value;
    
    // Block UI during fetch
    if (typeof $.blockUI === 'function') {
        $.blockUI({ message: '<h4>Loading Transfer Details...</h4>' });
    }

    $.ajax({
        url: baseUrl + 'loss/transfer-modal',
        type: 'POST',
        data: {
            batch_ids: batchIds,
            dept_id: deptId
        },
        success: function(html) {
            if (typeof $.unblockUI === 'function') {
                $.unblockUI();
            }
            $('#lossModalContainer').html(html);
            $('#lossTransferModal').modal('show');
        },
        error: function(xhr) {
            if (typeof $.unblockUI === 'function') {
                $.unblockUI();
            }
            swal({
                title: "Error",
                text: "Failed to open transfer modal. " + xhr.statusText,
                type: "error"
            });
        }
    });
}
</script>

<?= $this->endSection() ?>
