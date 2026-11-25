<!-- Modern Loss Consolidation Modal -->
<div id="lossTransferModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="lossModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content" style="border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header" style="background: #ffffff; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 36px; height: 36px; border-radius: 8px; background: #eff6ff; display: flex; align-items: center; justify-content: center; color: #2563eb; font-size: 16px;">
                        <i class="icon-fire"></i>
                    </div>
                    <div>
                        <h4 class="modal-title" id="lossModalTitle" style="margin: 0; font-size: 17px; font-weight: 700; color: #0f172a;">Transfer to Refining</h4>
                        <span style="font-size: 12px; color: #64748b;">Consolidate selected pending loss batches</span>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 22px; color: #94a3b8; opacity: 0.8;">&times;</button>
            </div>

            <!-- Modal Form -->
            <form id="lossConsolidateForm" novalidate onsubmit="submitLossConsolidation(event);">
                <input type="hidden" name="dept_id" value="<?= esc($deptId) ?>">
                <input type="hidden" name="batch_ids" value="<?= implode(',', $batchIds) ?>">

                <div class="modal-body" style="padding: 24px; background: #fafafa;">
                    <!-- Batch Overview Summary Card -->
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                        <div class="row">
                            <div class="col-xs-6" style="border-right: 1px solid #f1f5f9;">
                                <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Source Dept</span>
                                <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 4px;">
                                    <?= esc($deptAccount['account_name'] ?? 'General') ?> Loss
                                </div>
                            </div>
                            <div class="col-xs-6" style="padding-left: 20px;">
                                <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Destination</span>
                                <div style="font-size: 15px; font-weight: 700; color: #2563eb; margin-top: 4px;">
                                    <i class="icon-shield-check" style="font-size: 13px;"></i> <?= esc($refiningAccount['account_name'] ?? 'Refining') ?>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                            <span style="font-size: 12px; color: #64748b;">Selected Batches:</span>
                            <span class="sw-pill-badge" style="background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; font-size: 11px; font-weight: 700;">
                                <?= count($batchIds) ?> batches
                            </span>
                        </div>
                    </div>

                    <!-- Gross Weight Field -->
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="modalGrossWeight" style="font-size: 13px; font-weight: 600; color: #334155;">
                            Consolidated Gross Weight (g) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" 
                                   step="0.001" 
                                   min="0.001" 
                                   class="form-control input-lg" 
                                   id="modalGrossWeight" 
                                   name="gross_weight" 
                                   value="<?= number_format($totalGross, 3, '.', '') ?>" 
                                   required 
                                   oninput="calculateModalFine();"
                                   style="font-size: 16px; font-weight: 700; color: #0f172a; border-radius: 6px 0 0 6px;">
                            <span class="input-group-addon" style="background: #f1f5f9; font-weight: 600; color: #64748b; border-radius: 0 6px 6px 0;">grams</span>
                        </div>
                    </div>

                    <!-- Purity Field (Dynamic from Purity Master) -->
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="modalPurity" style="font-size: 13px; font-weight: 600; color: #334155;">
                            Refining Purity (%) <span class="text-danger">*</span>
                        </label>
                        <select class="form-control input-lg" 
                                id="modalPurity" 
                                name="purity" 
                                required 
                                onchange="calculateModalFine();"
                                style="font-size: 16px; font-weight: 700; color: #0f172a; border-radius: 6px;">
                            <?php if (!empty($activePurities)): ?>
                                <?php foreach ($activePurities as $p): ?>
                                    <?php 
                                        $pval = number_format((float)$p['purity_value'], 2, '.', '');
                                        $isSelected = ($pval == '100.00');
                                    ?>
                                    <option value="<?= $pval ?>" <?= $isSelected ? 'selected' : '' ?>>
                                        <?= esc($p['purity_name']) ?> (<?= $pval ?>%)
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="100.00" selected>100.00%</option>
                            <?php endif; ?>
                        </select>
                        <small style="color: #64748b; font-size: 11px;">Standard Refining recovery purity (typically 100.00%)</small>
                    </div>

                    <!-- Calculated Fine MT Preview -->
                    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 13px; font-weight: 600; color: #166534;">Calculated Fine Metal:</span>
                        <span id="modalFinePreview" style="font-size: 17px; font-weight: 700; color: #15803d;">
                            <?= number_format(round($totalGross * $defaultPurity / 100, 3), 3, '.', '') ?> g
                        </span>
                    </div>

                    <!-- Remark Field -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="modalRemark" style="font-size: 13px; font-weight: 600; color: #334155;">
                            Consolidation Remark / Voucher Reference
                        </label>
                        <textarea class="form-control" 
                                  id="modalRemark" 
                                  name="remark" 
                                  rows="2" 
                                  placeholder="e.g. Batch consolidation for weekly refining..." 
                                  style="border-radius: 6px; font-size: 13px; resize: vertical;"></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer" style="padding: 16px 24px; background: #ffffff; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="font-weight: 600;">
                        Cancel
                    </button>
                    <button type="submit" id="btnSubmitLossConsolidate" class="btn btn-primary" style="font-weight: 700; background: #1e293b; border-color: #0f172a; padding: 8px 20px;">
                        <i class="icon-check position-left"></i> Confirm Transfer to Refining
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
function calculateModalFine() {
    var grossInput = document.getElementById('modalGrossWeight');
    var purityInput = document.getElementById('modalPurity');
    var preview = document.getElementById('modalFinePreview');

    var gross = parseFloat(grossInput ? grossInput.value : 0) || 0.0;
    var purity = parseFloat(purityInput ? purityInput.value : 0) || 0.0;
    var fine = (gross * purity) / 100.0;

    if (preview) {
        preview.textContent = fine.toFixed(3) + ' g';
    }
}

function submitLossConsolidation(e) {
    e.preventDefault();

    var form = document.getElementById('lossConsolidateForm');
    var btn = document.getElementById('btnSubmitLossConsolidate');
    var baseUrl = document.getElementById('base_url').value;

    btn.setAttribute('disabled', 'disabled');
    btn.innerHTML = '<i class="icon-spinner2 spinner position-left"></i> Consolidating...';

    var formData = $(form).serialize();

    $.ajax({
        url: baseUrl + 'loss/consolidate',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(resp) {
            btn.removeAttribute('disabled');
            btn.innerHTML = '<i class="icon-check position-left"></i> Confirm Transfer to Refining';

            if (resp && (resp.status === 'true' || resp.status === true)) {
                $('#lossTransferModal').modal('hide');
                swal({
                    title: "Transfer Completed",
                    text: resp.message || "Selected loss batches consolidated and transferred to Refining successfully.",
                    type: "success"
                }, function() {
                    // Reload ledger to show updated status and balances
                    window.location.reload();
                });
            } else {
                swal({
                    title: "Transfer Failed",
                    text: (resp && resp.message) ? resp.message : "An error occurred while consolidating batches.",
                    type: "error"
                });
            }
        },
        error: function(xhr) {
            btn.removeAttribute('disabled');
            btn.innerHTML = '<i class="icon-check position-left"></i> Confirm Transfer to Refining';
            swal({
                title: "Server Error",
                text: "Failed to communicate with server: " + xhr.statusText,
                type: "error"
            });
        }
    });
}
</script>
