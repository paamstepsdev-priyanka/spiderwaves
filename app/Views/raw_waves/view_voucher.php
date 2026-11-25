<?php
$session = session()->get('spider');
$voucher = is_array($voucher_data) ? (object)$voucher_data : $voucher_data;
?>
<div class="modal-dialog" style="max-width: 370px; margin: 30px auto;" id="view_voucher_model">
    <div class="modal-content" style="border-radius: var(--sw-radius); border: 1px solid var(--sw-border); box-shadow: var(--sw-shadow-lg); overflow: hidden;">
        <div class="modal-header" style="background: var(--sw-primary); color: #ffffff; padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; border-bottom: none;">
            <h5 class="modal-title" style="margin: 0; font-size: 14px; font-weight: 700; color: #ffffff;">
                <span class="sr-only" style="display:none;">Verification & Approval</span>
                <?php if ($voucher): ?>
                    Confirmation(<?= esc(ucfirst($voucher->transactional_type ?? 'Receipt')) ?>)
                <?php else: ?>
                    Verification Notice
                <?php endif; ?>
            </h5>
            <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.9; font-size: 20px; font-weight: 400; line-height: 1; border: none; background: transparent; padding: 0; cursor: pointer;">&times;</button>
        </div>

        <div class="modal-body" style="padding: 12px 14px 20px 14px; background: #ffffff;">
            <?php if (!empty($authorization_error_msg)): ?>
                <div class="sw-alert sw-alert-danger" style="margin-bottom: 12px;">
                    <i class="icon-alert"></i>
                    <span><?= esc($authorization_error_msg) ?></span>
                </div>
                <div style="text-align: right; margin-top: 10px;">
                    <button type="button" class="sw-btn-secondary" data-dismiss="modal" style="height: 32px; padding: 4px 14px; font-size: 12.5px;">Close</button>
                </div>
            <?php elseif ($voucher): ?>
                <input type="hidden" name="transaction_type" id="modal_transaction_type" value="<?= esc($voucher->transactional_type) ?>">
                <input type="hidden" name="gross_weight" id="modal_gross_weight" value="<?= esc($voucher->gross_weight) ?>">
                <input type="hidden" name="less_weight" id="modal_less_weight" value="<?= esc($voucher->less_weight) ?>">
                <input type="hidden" name="private_key" id="modal_private_key" value="<?= esc($voucher->private_key) ?>">
                <input type="hidden" name="transactional_mode" id="modal_transactional_mode" value="<?= esc($voucher->transactional_mode) ?>">

                <!-- Voucher Preview Slip -->
                <div style="margin-bottom: 10px; display: flex; justify-content: center;">
                    <?= view('raw_waves/download_voucher', ['voucher_data' => $voucher, 'single_copy' => true]) ?>
                </div>

                <!-- Scale Re-Weigh Input (Only for receipts) -->
                <?php if ($voucher->transactional_type === 'receipt' && $voucher->transactional_mode !== 'RK'): ?>
                <div class="sw-form-group" style="margin-bottom: 10px;">
                    <label class="sw-label" style="color: #0284c7; font-weight: 700; font-size: 11.5px; margin-bottom: 4px; display: block;">
                        Weight from Scale
                    </label>
                    <input type="number" step="0.001" class="sw-input" name="scale_weight" id="modal_scale_weight" placeholder="Weight" value="" required autofocus autocomplete="off" style="height: 34px; font-size: 13px;">
                    <div id="scale_variance_msg" style="font-size: 11px; margin-top: 3px; display: none;"></div>
                </div>
                <?php endif; ?>

                <!-- Bottom Row: Select Mapped Cust on Left, Verify & Cancel on Right -->
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 14px;">
                    <div style="flex: 1; min-width: 0; max-width: 220px;">
                        <select class="select2" name="mapped_cust_id" id="modal_mapped_cust_id" style="width: 100%;">
                            <?php if (!empty($mapped_cust_data)): ?>
                                <?php if (count($mapped_cust_data) > 1): ?>
                                    <option value="">Select Mapped Cust</option>
                                <?php endif; ?>
                                <?php foreach ($mapped_cust_data as $mobj): ?>
                                    <option value="<?= esc($mobj['mapped_cust_id']) ?>" selected>
                                        <?= esc($mobj['mapped_cust_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">Select Mapped Cust</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                        <button type="button" class="sw-btn-primary" id="btn_modal_verify" disabled style="height: 32px; padding: 4px 14px; font-size: 12.5px; font-weight: 600;">
                            <span id="btn_modal_verify_text">Verify</span>
                        </button>
                        <button type="button" class="sw-btn-secondary" data-dismiss="modal" style="height: 32px; padding: 4px 12px; font-size: 12.5px; font-weight: 600;">
                            Cancel
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    var $scaleInput = $('#modal_scale_weight');
    var $verifyBtn  = $('#btn_modal_verify');
    var $mappedCust = $('#modal_mapped_cust_id');
    var grossWeight = parseFloat($('#modal_gross_weight').val()) || 0;
    var lessWeight  = parseFloat($('#modal_less_weight').val()) || 0;
    var transType   = $('#modal_transaction_type').val();
    var privateKey  = $('#modal_private_key').val();

    function checkEnableVerify() {
        var isScaleValid = true;

        if (transType === 'receipt' && $scaleInput.length) {
            var rawVal = $scaleInput.val();
            var scaleVal = parseFloat(rawVal);
            if (!rawVal || isNaN(scaleVal) || scaleVal <= 0) {
                isScaleValid = false;
            } else {
                var variance = Math.abs(Math.round(scaleVal * 1000 - grossWeight * 1000) / 1000);
                if (variance > 0.030) {
                    isScaleValid = false;
                }
            }
        }

        var isCustValid = true;
        if ($mappedCust.length && $mappedCust.find('option').length > 1 && !$mappedCust.val()) {
            isCustValid = false;
        }

        if (isScaleValid && isCustValid) {
            $verifyBtn.prop('disabled', false);
        } else {
            $verifyBtn.prop('disabled', true);
        }
    }

    if ($scaleInput.length) {
        $scaleInput.on('input change keyup', function() {
            var rawVal = $(this).val();
            var scaleVal = parseFloat(rawVal);
            if (!rawVal || isNaN(scaleVal)) {
                $('#scale_variance_msg').hide();
                checkEnableVerify();
                return;
            }

            var variance = Math.abs(Math.round(scaleVal * 1000 - grossWeight * 1000) / 1000);
            var isAcceptable = variance <= 0.030;

            if (!isAcceptable) {
                var msg = (scaleVal < grossWeight) 
                    ? 'Weight is less than Gross Weight.' 
                    : 'Weight is greater than Gross Weight.';
                $('#scale_variance_msg').show()
                    .css('color', '#dc2626')
                    .text(msg);
            } else {
                $('#scale_variance_msg').hide();
            }

            checkEnableVerify();
        });

        // Initial validation check
        checkEnableVerify();
    } else {
        checkEnableVerify();
    }

    if ($mappedCust.length) {
        $mappedCust.on('change', checkEnableVerify);
    }

    // Handle Verify click
    $verifyBtn.on('click', function() {
        var scaleWeight = $scaleInput.length ? parseFloat($scaleInput.val()) : grossWeight;
        var mappedCustId = $mappedCust.length ? $mappedCust.val() : null;

        var netWeight = scaleWeight - lessWeight;
        var stonePercentage = netWeight > 0 ? ((lessWeight / netWeight) * 100).toFixed(1) : '0.0';

        var promptText = "Confirm approval and dual ledger commit?";
        if (lessWeight > 0) {
            promptText = "Less / Stone Percentage is " + stonePercentage + "%. Do you want to proceed with verification?";
        }

        if (typeof swal !== 'undefined') {
            swal({
                title: "Confirm Approval",
                text: promptText,
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#2563eb",
                confirmButtonText: "Yes, Approve & Commit",
                cancelButtonText: "Cancel",
                closeOnConfirm: false
            }, function(isConfirm) {
                if (isConfirm) {
                    performDualLedgerCommit(privateKey, scaleWeight, mappedCustId);
                }
            });
        } else {
            if (confirm(promptText)) {
                performDualLedgerCommit(privateKey, scaleWeight, mappedCustId);
            }
        }
    });

    function performDualLedgerCommit(pKey, sWeight, mCustId) {
        $verifyBtn.prop('disabled', true);
        $('#btn_modal_verify_text').text('Processing...');

        $.ajax({
            url: '<?= base_url("raw-waves/verify") ?>',
            type: 'POST',
            data: {
                private_key: pKey,
                scale_weight: sWeight,
                mapped_cust_id: mCustId,
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'true') {
                    if (typeof swal !== 'undefined') {
                        swal({
                            title: "Approved!",
                            text: "Transaction successfully committed to Waves Ledger" + (res.process_waves_id ? " and Process Waves Locker." : "."),
                            type: "success",
                            confirmButtonColor: "#2563eb"
                        }, function() {
                            $('#viewVoucherModal').modal('hide');
                            window.location.reload();
                        });
                    } else {
                        alert("Transaction approved and committed successfully!");
                        $('#viewVoucherModal').modal('hide');
                        window.location.reload();
                    }
                } else {
                    if (typeof swal !== 'undefined') {
                        swal("Verification Failed", res.message || "Failed to commit ledger records.", "error");
                    } else {
                        alert(res.message || "Verification Failed");
                    }
                    $verifyBtn.prop('disabled', false);
                    $('#btn_modal_verify_text').text('Verify');
                }
            },
            error: function(xhr, status, error) {
                if (typeof swal !== 'undefined') {
                    swal("System Error", "Communication error: " + error, "error");
                } else {
                    alert("System error: " + error);
                }
                $verifyBtn.prop('disabled', false);
                $('#btn_modal_verify_text').text('Verify');
            }
        });
    }
});
</script>
