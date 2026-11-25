<div class="modal-header" style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 16px 24px;">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true" style="font-size: 24px; color: #64748b;">&times;</button>
    <div style="display: flex; align-items: center; gap: 10px;">
        <span class="sw-pill-badge" style="background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 11px;">
            <i class="icon-loop4"></i> Batch #<?= esc($processWavesId) ?>
        </span>
        <h4 class="modal-title" style="margin: 0; font-size: 17px; font-weight: 700; color: #0f172a;">
            Transfer Refined Gold to Locker
        </h4>
    </div>
</div>

<form id="refiningTransferForm" method="post" action="<?= base_url('refining/insert-transfer') ?>" novalidate>
    <input type="hidden" name="process_waves_id" id="refining_pw_id" value="<?= esc($processWavesId) ?>">
    <input type="hidden" id="modal_incoming_gross" value="<?= esc($incomingGross) ?>">

    <div class="modal-body" style="padding: 24px;">
        <!-- Error Alert Area -->
        <div id="modalAlertBox" style="display: none; margin-bottom: 16px;"></div>

        <!-- Source Batch Metadata Box -->
        <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; margin-bottom: 20px;">
            <div class="row">
                <div class="col-xs-6" style="margin-bottom: 8px;">
                    <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; display: block;">Source Department</span>
                    <strong style="font-size: 14px; color: #0f172a;"><?= esc($sourceDeptName) ?></strong>
                </div>
                <div class="col-xs-6" style="margin-bottom: 8px;">
                    <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; display: block;">Destination Account</span>
                    <span class="sw-pill-badge" style="background-color: #eff6ff; color: #2563eb; font-weight: 600; font-size: 12px;">
                        <i class="icon-safe"></i> Locker / Central Vault
                    </span>
                </div>
                <div class="col-xs-6">
                    <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; display: block;">Incoming Weight</span>
                    <strong style="font-size: 16px; color: #2563eb;"><?= esc($incomingGross) ?> <span style="font-size: 12px; font-weight: 500; color: #64748b;">g</span></strong>
                </div>
                <div class="col-xs-6">
                    <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; display: block;">Incoming Purity</span>
                    <strong style="font-size: 14px; color: #0f172a;"><?= esc($incomingPurity) ?> %</strong>
                </div>
            </div>
        </div>

        <!-- Form Inputs Grid -->
        <div class="row">
            <!-- Output Gross Weight -->
            <div class="col-md-6 col-sm-12">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="modal_gross_weight" style="font-size: 12px; font-weight: 600; color: #334155;">
                        Refined Gross Weight (g) <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="number" 
                           step="0.001" 
                           class="form-control" 
                           name="gross_weight" 
                           id="modal_gross_weight" 
                           placeholder="e.g. <?= esc($incomingGross) ?>" 
                           max="<?= esc($incomingGross) ?>"
                           style="border-radius: 6px; font-weight: 700; font-size: 15px; color: #0f172a;"
                           required>
                    <span class="help-block" style="font-size: 11px; color: #64748b; margin-top: 4px;">
                        Cannot exceed incoming weight of <?= esc($incomingGross) ?> g
                    </span>
                </div>
            </div>

            <!-- Output Purity -->
            <div class="col-md-6 col-sm-12">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="modal_purity" style="font-size: 12px; font-weight: 600; color: #334155;">
                        Refined Purity (%) <span style="color: #ef4444;">*</span>
                    </label>
                    <select class="form-control" 
                            name="purity" 
                            id="modal_purity" 
                            required 
                            style="border-radius: 6px; font-weight: 700; font-size: 15px; color: #0f172a;">
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
                    <span class="help-block" style="font-size: 11px; color: #64748b; margin-top: 4px;">
                        Assay purity (strictly formatted to 2 decimals)
                    </span>
                </div>
            </div>
        </div>

        <!-- Dynamic Real-Time Loss Preview -->
        <div class="row" style="margin-bottom: 16px;">
            <div class="col-xs-6">
                <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 10px 14px;">
                    <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #b45309; display: block;">Loss Weight (Gross)</span>
                    <strong style="font-size: 16px; color: #d97706;" id="preview_loss_weight">0.000</strong> <span style="font-size: 11px; color: #b45309;">g</span>
                </div>
            </div>
            <div class="col-xs-6">
                <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 10px 14px;">
                    <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: #b91c1c; display: block;">Net Loss (Fine MT)</span>
                    <strong style="font-size: 16px; color: #ef4444;" id="preview_net_loss">0.000</strong> <span style="font-size: 11px; color: #b91c1c;">g</span>
                </div>
            </div>
        </div>

        <!-- Remark Field -->
        <div class="form-group" style="margin-bottom: 0;">
            <label for="modal_remark" style="font-size: 12px; font-weight: 600; color: #334155;">Remark (Optional)</label>
            <input type="text" class="form-control" name="remark" id="modal_remark" placeholder="Enter refining batch remarks..." style="border-radius: 6px;">
        </div>
    </div>

    <div class="modal-footer" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 14px 24px; display: flex; justify-content: flex-end; gap: 8px;">
        <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 6px; font-weight: 600; padding: 8px 16px;">
            Cancel
        </button>
        <button type="submit" id="btnSubmitRefiningTransfer" class="btn btn-primary" style="background-color: #0f172a; border-color: #0f172a; border-radius: 6px; font-weight: 600; padding: 8px 20px;">
            <i class="icon-rocket position-left"></i> Transfer to Locker
        </button>
    </div>
</form>

<script>
$(document).ready(function() {
    var incomingGross = parseFloat($('#modal_incoming_gross').val()) || 0;

    function recalculateRefiningLoss() {
        var gw = parseFloat($('#modal_gross_weight').val());
        var purity = parseFloat($('#modal_purity').val());

        if (isNaN(gw) || gw < 0) {
            $('#preview_loss_weight').text('0.000');
            $('#preview_net_loss').text('0.000');
            return;
        }

        if (isNaN(purity) || purity <= 0) {
            purity = 100.00;
        }

        var lossWeight = Math.max(0, incomingGross - gw);
        var netLoss = (lossWeight * purity) / 100;

        $('#preview_loss_weight').text(lossWeight.toFixed(3));
        $('#preview_net_loss').text(netLoss.toFixed(3));
    }

    $('#modal_gross_weight, #modal_purity').on('input change keyup', recalculateRefiningLoss);

    // Format purity to 2 decimals on blur
    $('#modal_purity').on('blur', function() {
        var val = parseFloat($(this).val());
        if (!isNaN(val) && val > 0 && val <= 100) {
            $(this).val(val.toFixed(2));
        }
        recalculateRefiningLoss();
    });

    // Form Submit via AJAX
    $('#refiningTransferForm').on('submit', function(e) {
        e.preventDefault();

        var gw = parseFloat($('#modal_gross_weight').val());
        var purity = parseFloat($('#modal_purity').val());

        if (isNaN(gw) || gw <= 0) {
            showModalError('Please enter a valid gross weight greater than 0.');
            return;
        }

        if (gw > incomingGross) {
            showModalError('Gross weight cannot exceed incoming batch weight (' + incomingGross.toFixed(3) + ' g).');
            return;
        }

        if (isNaN(purity) || purity <= 0 || purity > 100) {
            showModalError('Purity must be between 0.01% and 100.00%.');
            return;
        }

        var $submitBtn = $('#btnSubmitRefiningTransfer');
        $submitBtn.prop('disabled', true).html('<i class="icon-spinner2 spinner position-left"></i> Transferring...');

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'true') {
                    $('#ajaxRefiningTransferModal').modal('hide');
                    swal({
                        title: "Transferred to Locker!",
                        text: "Batch successfully refined and transferred to Central Vault.\nNet Loss: " + res.net_loss + " g",
                        type: "success",
                        timer: 2000,
                        showConfirmButton: true,
                        confirmButtonColor: "#0f172a"
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 1200);
                } else {
                    $submitBtn.prop('disabled', false).html('<i class="icon-rocket position-left"></i> Transfer to Locker');
                    showModalError(res.message || 'Error occurred during refining transfer.');
                }
            },
            error: function(xhr, status, error) {
                $submitBtn.prop('disabled', false).html('<i class="icon-rocket position-left"></i> Transfer to Locker');
                showModalError('Server communication error: ' + error);
            }
        });
    });

    function showModalError(msg) {
        $('#modalAlertBox').html('<div class="alert alert-danger" style="padding: 10px 14px; font-size: 13px; margin: 0;"><i class="icon-warning2 position-left"></i> ' + msg + '</div>').slideDown(150);
    }
});
</script>
