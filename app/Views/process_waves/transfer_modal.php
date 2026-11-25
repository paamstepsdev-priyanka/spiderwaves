<?php if (!empty($isLocker)): ?>
<!-- ============================================================== -->
<!-- LOCKER 2-MODEL TRANSFER (Exact CI2 Study Report Match)        -->
<!-- Shows only 2 models/steps: 1. To, 2. Remark                   -->
<!-- Uses strictly Limitless theme existing CSS (no custom CSS)     -->
<!-- ============================================================== -->

<div class="modal-header bg-success">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title">Process Waves</h4>
</div>

<form id="formProcessTransfer" novalidate>
    <div class="modal-body">
        <!-- Hidden Context Inputs -->
        <input type="hidden" name="idempotency_token" id="transfer_idempotency_token" value="<?= esc($idempotencyToken ?? bin2hex(random_bytes(16))) ?>">
        <input type="hidden" name="form_type" id="transfer_form_type" value="Add">
        <input type="hidden" name="from_id" id="transfer_from_id" value="<?= esc($fromAccount['account_id']) ?>">
        <input type="hidden" name="from_name" id="transfer_from_name" value="<?= esc($fromAccount['account_name']) ?>">
        <input type="hidden" name="process_waves_id" id="transfer_process_waves_id" value="<?= esc($processWavesId ?? '') ?>">
        <input type="hidden" name="private_key" id="transfer_private_key" value="<?= esc($batch['private_key'] ?? '') ?>">
        <input type="hidden" name="recent_gross_weight" id="transfer_recent_gross_weight" value="<?= esc($batch['gross_weight'] ?? '') ?>">
        <input type="hidden" name="gross_weight" id="transfer_gross_weight" value="<?= esc($batch['gross_weight'] ?? '') ?>">
        <input type="hidden" name="purity" id="transfer_purity" value="<?= esc($batch['purity'] ?? '') ?>">
        <input type="hidden" name="mapped_cust_id" id="transfer_mapped_cust_id" value="<?= esc($batch['mapped_cust_id'] ?? '') ?>">
        <input type="hidden" name="cust_id" id="transfer_cust_id" value="<?= esc($batch['cust_id'] ?? '') ?>">
        <input type="hidden" name="is_stock_check" id="transfer_is_stock_check" value="<?= esc($isStockCheck) ?>">
        <input type="hidden" name="row_private_key" id="transfer_row_private_key" value="<?= esc($rowPrivateKey ?? '') ?>">

        <!-- MODEL 1: To / Destination -->
        <fieldset id="step_to" class="transfer-step show">
            <div class="row">
                <div class="form-group">
                    <label>To</label>
                    <select class="select" name="to_id" id="transfer_to_id" required="true">
                        <option value="">Select To</option>
                        <?php if (in_array($accessLevel, ['admin', 'superadmin', 'locker'], true)): ?>
                        <option value="cust">Customer</option>
                        <?php endif; ?>
                        <?php if (!empty($destinationAccounts)): ?>
                            <?php foreach ($destinationAccounts as $dest): ?>
                                <option value="<?= esc($dest['account_id']) ?>" data-name="<?= esc($dest['account_name']) ?>">
                                    <?= esc($dest['account_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <label id="to_id-error" class="validation-error-label text-danger" for="transfer_to_id" style="display: none;"></label>
                </div>
            </div>
        </fieldset>

        <!-- MODEL 2: Remark -->
        <fieldset id="step_remark" class="transfer-step hide" style="display: none;">
            <div class="row">
                <div class="form-group">
                    <label>Remark</label>
                    <input type="text" class="form-control" name="remark" id="transfer_remark" placeholder="Remark" value="" maxlength="60">
                </div>
            </div>
            <div class="row">
                <button type="button" class="button-back btn btn-default" onclick="back_to_to_account();"><i class="icon-arrow-left13 position-left"></i> Back</button>
                <button type="submit" id="submit_button" class="btn btn-primary stepy-finish pull-right"><span id="submit_button_text">Submit</span> <i id="submit_button_icon" class="icon-check position-right"></i></button>
            </div>
        </fieldset>
    </div>
</form>

<script>
$(document).ready(function() {
    if ($.fn.select2) {
        $('#transfer_to_id').select2({
            placeholder: "Select To",
            width: '100%'
        });
    }

    setTimeout(function() {
        try {
            $('#transfer_to_id').select2('open');
        } catch(e) {
            $('#transfer_to_id').focus();
        }
    }, 200);

    $('#transfer_to_id').on('change select2:select', function() {
        to_id_change();
    });

    $(document).off('keypress.lockerTransfer').on('keypress.lockerTransfer', function(e) {
        if (e.which === 13 || e.keyCode === 13) {
            if ($('#step_to').is(':visible')) {
                e.preventDefault();
                to_id_change();
                var to_id = $('#transfer_to_id').val();
                if (to_id !== 'cust' && !to_id) {
                    try { $('#transfer_to_id').select2('open'); } catch(err) {}
                }
            } else if ($('#step_remark').is(':visible')) {
                e.preventDefault();
                // Route Enter key through form submit to ensure single execution path
                $('#formProcessTransfer').submit();
            }
        }
    });

    $('#formProcessTransfer').on('submit', function(e) {
        e.preventDefault();
        submitTransfer();
    });
});

function to_id_change() {
    var toId = $('#transfer_to_id').val();
    var toIdText = $('#transfer_to_id option:selected').text().trim();

    if (!toId) {
        $('#to_id-error').text('This field is required.').show();
        return;
    }
    $('#to_id-error').hide();

    if (toId === 'cust' || toIdText === 'Customer') {
        var privateKey = $('#transfer_private_key').val();
        $('#ajaxTransferModal').modal('hide');
        window.location.href = '<?= base_url('raw-waves/index') ?>/' + encodeURIComponent(privateKey);
        return;
    }

    // Advance to Model 2: Remark (only these 2 models for Locker)
    $('#step_to').removeClass('show').addClass('hide').hide();
    $('#step_remark').removeClass('hide').addClass('show').show();
    setTimeout(function() {
        $('#transfer_remark').focus();
    }, 100);
}

function back_to_to_account() {
    $('#step_remark').removeClass('show').addClass('hide').hide();
    $('#step_to').removeClass('hide').addClass('show').show();
    setTimeout(function() {
        try {
            $('#transfer_to_id').select2('open');
        } catch(e) {
            $('#transfer_to_id').focus();
        }
    }, 100);
}

var isSubmitting = false;
function submitTransfer() {
    var $form = $('#formProcessTransfer');
    if (isSubmitting || $form.data('submitting') === true) return;

    var toId = $('#transfer_to_id').val();
    if (!toId) {
        back_to_to_account();
        return;
    }

    isSubmitting = true;
    $form.data('submitting', true);

    var $submitBtn = $('#submit_button');
    $submitBtn.prop('disabled', true).addClass('disabled');
    $submitBtn.html('<i class="icon-spinner2 spinner position-left"></i> Processing...');
    $('#transfer_remark').prop('disabled', true);

    var formData = new FormData($form[0]);

    $.ajax({
        url: '<?= base_url('process-waves/insert-transfer') ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(resp) {
            if (resp && (resp.status === 'true' || resp.status === true)) {
                $('#ajaxTransferModal').modal('hide');

                if (resp.process_waves_id) {
                    window.open('<?= base_url('process-waves/print-transaction') ?>/' + resp.process_waves_id, 'popup', 'width=650,height=650');
                }

                var handled = false;
                var finishSuccess = function() {
                    if (handled) return;
                    handled = true;
                    try {
                        if (typeof swal !== 'undefined' && swal.close) {
                            swal.close();
                        }
                    } catch(e) {}
                    $('.sweet-alert, .sweet-overlay').remove();
                    window.location.reload();
                };

                if (document.activeElement && document.activeElement.blur) {
                    document.activeElement.blur();
                }

                swal({
                    title: "Success!",
                    text: "Process Waves Inserted!",
                    type: "success",
                    timer: 1500,
                    showConfirmButton: false,
                    closeOnConfirm: true
                }, function() {
                    finishSuccess();
                });

                $('.sweet-alert button.confirm').hide();

                setTimeout(function() {
                    finishSuccess();
                }, 1500);
            } else if (resp && resp.status === 'redirect') {
                $('#ajaxTransferModal').modal('hide');
                window.location.href = resp.redirect;
            } else {
                isSubmitting = false;
                $form.data('submitting', false);
                $submitBtn.prop('disabled', false).removeClass('disabled');
                $submitBtn.html('<span id="submit_button_text">Submit</span> <i id="submit_button_icon" class="icon-check position-right"></i>');
                $('#transfer_remark').prop('disabled', false).focus();
                swal({
                    title: "Transfer Failed",
                    text: (resp && resp.message) ? resp.message : "Failed to complete transfer.",
                    confirmButtonColor: "#EF5350",
                    type: "error"
                });
            }
        },
        error: function(xhr) {
            isSubmitting = false;
            $form.data('submitting', false);
            $submitBtn.prop('disabled', false).removeClass('disabled');
            $submitBtn.html('<span id="submit_button_text">Submit</span> <i id="submit_button_icon" class="icon-check position-right"></i>');
            $('#transfer_remark').prop('disabled', false).focus();
            var errMsg = "Server error processing transfer.";
            try {
                var json = JSON.parse(xhr.responseText);
                if (json && json.message) errMsg = json.message;
            } catch(e) {}
            swal({
                title: "Error",
                text: errMsg,
                confirmButtonColor: "#EF5350",
                type: "error"
            });
        }
    });
}
</script>

<?php else: ?>
<!-- ============================================================== -->
<!-- STANDARD MULTI-STAGE PROCESS TRANSFER (Non-Locker accounts)    -->
<!-- Retains full 5-step flow, untouched for all other departments   -->
<!-- ============================================================== -->

<div class="modal-header" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff; padding: 14px 20px;">
    <button type="button" class="close" data-dismiss="modal" style="color: #ffffff; opacity: 0.9;">&times;</button>
    <h5 class="modal-title" style="color: #ffffff; font-weight: 600; font-size: 15px; margin: 0;">
        <i class="icon-rocket position-left"></i> 
        Process Transfer: <?= esc($fromAccount['account_name']) ?>
    </h5>
</div>

<form id="formProcessTransfer" class="form-horizontal" novalidate>
    <div class="modal-body" style="padding: 24px;">
        <!-- Hidden Context Inputs -->
        <input type="hidden" name="idempotency_token" id="non_locker_transfer_idempotency_token" value="<?= esc($idempotencyToken ?? bin2hex(random_bytes(16))) ?>">
        <input type="hidden" name="from_id" id="transfer_from_id" value="<?= esc($fromAccount['account_id']) ?>">
        <input type="hidden" name="from_name" id="transfer_from_name" value="<?= esc($fromAccount['account_name']) ?>">
        <input type="hidden" name="process_waves_id" id="transfer_process_waves_id" value="<?= esc($processWavesId ?? '') ?>">
        <input type="hidden" name="recent_gross_weight" id="transfer_recent_gross_weight" value="<?= esc($batch['gross_weight'] ?? $availableBalance) ?>">
        <input type="hidden" name="is_stock_check" id="transfer_is_stock_check" value="<?= esc($isStockCheck) ?>">
        <input type="hidden" name="row_private_key" id="transfer_row_private_key" value="<?= esc($rowPrivateKey ?? '') ?>">

        <!-- STEP 1: To / Destination -->
        <fieldset id="step_to" class="transfer-step show" style="border: none; padding: 0; margin: 0;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="control-label text-semibold text-size-base" style="margin-bottom: 8px; display: block; color: #1e293b;">
                    To / Destination (To) <span class="text-danger">*</span>
                </label>
                <div>
                    <select name="to_id" id="transfer_to_id" class="select2-modal" style="width: 100%;" required>
                        <option value="">-- Select Destination --</option>
                        <?php if ((int)$fromAccount['account_id'] === 1): // Locker can transfer to customer ?>
                        <option value="cust">Customer (Dispatch)</option>
                        <?php endif; ?>

                        <?php if (!empty($destinationAccounts)): ?>
                            <?php foreach ($destinationAccounts as $dest): ?>
                                <option value="<?= esc($dest['account_id']) ?>" data-name="<?= esc($dest['account_name']) ?>">
                                    <?= esc($dest['account_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <span class="help-block text-danger" id="err_transfer_to" style="display: none; margin-top: 6px;">Please select destination department.</span>
                </div>
            </div>
        </fieldset>

        <!-- STEP 2: Mapped Cust Name -->
        <fieldset id="step_mapped_cust" class="transfer-step hide" style="border: none; padding: 0; margin: 0; display: none;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="control-label text-semibold text-size-base" style="margin-bottom: 8px; display: block; color: #1e293b;">
                    Mapped Cust Name <span class="text-danger">*</span>
                </label>
                <div>
                    <select name="mapped_cust_id" id="transfer_mapped_cust_id" class="select2-modal" style="width: 100%;" required>
                        <option value="">-- Select Mapped Customer --</option>
                        <?php if (!empty($mappedCustList)): ?>
                            <?php foreach ($mappedCustList as $mc): ?>
                                <option value="<?= esc($mc['mapped_cust_id']) ?>" 
                                        data-purity="<?= esc(number_format((float)$mc['purity'], 2, '.', '')) ?>"
                                        <?= (isset($selectedMappedCustId) && (int)$selectedMappedCustId === (int)$mc['mapped_cust_id']) ? 'selected' : '' ?>>
                                    <?= esc($mc['mapped_cust_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <span class="help-block text-danger" id="err_transfer_mapped_cust" style="display: none; margin-top: 6px;">Please select Mapped Customer Name.</span>
                </div>
            </div>
        </fieldset>

        <!-- STEP 3: Gross Weight (g) -->
        <fieldset id="step_gross_weight" class="transfer-step hide" style="border: none; padding: 0; margin: 0; display: none;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="control-label text-semibold text-size-base" style="margin-bottom: 8px; display: block; color: #1e293b;">
                    Gross Weight (g) <span class="text-danger">*</span>
                </label>
                <div>
                    <input type="number" 
                           step="0.001" 
                           name="gross_weight" 
                           id="transfer_gross_weight" 
                           class="form-control" 
                           style="font-size: 16px; font-weight: 600; height: 42px;"
                           placeholder="0.000" 
                           value="" 
                           required>
                    <span class="help-block text-danger" id="err_transfer_weight" style="display: none; margin-top: 6px;"></span>
                </div>
            </div>
        </fieldset>

        <!-- STEP 4: Purity (%) -->
        <fieldset id="step_purity" class="transfer-step hide" style="border: none; padding: 0; margin: 0; display: none;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="control-label text-semibold text-size-base" style="margin-bottom: 8px; display: block; color: #1e293b;">
                    Purity (%) <span class="text-danger">*</span>
                </label>
                <div>
                    <select name="purity" 
                            id="transfer_purity" 
                            class="select2-modal form-control" 
                            style="font-size: 16px; font-weight: 600; height: 42px; width: 100%;"
                            required>
                        <option value="">-- Select Purity --</option>
                        <?php if (!empty($activePurities)): ?>
                            <?php foreach ($activePurities as $p): ?>
                                <?php $pval = number_format((float)$p['purity_value'], 2, '.', ''); ?>
                                <option value="<?= $pval ?>"><?= esc($p['purity_name']) ?> (<?= $pval ?>%)</option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <span class="help-block text-danger" id="err_transfer_purity" style="display: none; margin-top: 6px;">Please select valid purity.</span>
                </div>
            </div>
        </fieldset>

        <!-- STEP 5: Remark -->
        <fieldset id="step_remark" class="transfer-step hide" style="border: none; padding: 0; margin: 0; display: none;">
            <div class="form-group" style="margin-bottom: 15px;">
                <label class="control-label text-semibold text-size-base" style="margin-bottom: 8px; display: block; color: #1e293b;">
                    Remark
                </label>
                <div>
                    <input type="text" 
                           name="remark" 
                           id="transfer_remark" 
                           class="form-control" 
                           style="font-size: 15px; height: 42px;"
                           placeholder="Remark" 
                           value="" 
                           maxlength="60">
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <button type="button" class="btn btn-default pull-left" onclick="goToStep('step_purity');">
                    <i class="icon-arrow-left13 position-left"></i> Back
                </button>
                <button type="submit" id="non_locker_submit_button" class="btn btn-primary pull-right">
                    <span id="non_locker_submit_text">Transfer</span> <i id="non_locker_submit_icon" class="icon-check position-right"></i>
                </button>
            </div>
        </fieldset>
    </div>
</form>

<script>
$(document).ready(function() {
    var $modal = $('#ajaxTransferModal');

    // Initialize Select2 on Destination & Mapped Customer dropdowns
    if ($.fn.select2) {
        $('#transfer_to_id').select2({
            placeholder: "-- Select Destination --",
            allowClear: false,
            width: '100%',
            dropdownParent: $modal
        });
        $('#transfer_mapped_cust_id').select2({
            placeholder: "-- Select Mapped Customer --",
            allowClear: false,
            width: '100%',
            dropdownParent: $modal
        });
        $('#transfer_purity').select2({
            placeholder: "-- Select Purity --",
            allowClear: false,
            width: '100%',
            dropdownParent: $modal
        });
    }

    // Auto-focus and open destination Select2 once modal is rendered
    setTimeout(function() {
        if ($('#transfer_to_id').is(':visible')) {
            try {
                $('#transfer_to_id').select2('open');
            } catch (err) {
                $('#transfer_to_id').focus();
            }
        }
    }, 250);

    // Navigation function between sequential step fieldsets
    function goToStep(stepId) {
        $('.transfer-step').hide().removeClass('show').addClass('hide');
        $('#' + stepId).show().removeClass('hide').addClass('show');

        if (stepId === 'step_to') {
            setTimeout(function() {
                try {
                    $('#transfer_to_id').select2('open');
                } catch (e) {
                    $('#transfer_to_id').focus();
                }
            }, 100);
        } else if (stepId === 'step_mapped_cust') {
            setTimeout(function() {
                try {
                    $('#transfer_mapped_cust_id').select2('open');
                } catch (e) {
                    $('#transfer_mapped_cust_id').focus();
                }
            }, 100);
        } else if (stepId === 'step_gross_weight') {
            setTimeout(function() {
                $('#transfer_gross_weight').focus().select();
            }, 100);
        } else if (stepId === 'step_purity') {
            setTimeout(function() {
                try {
                    $('#transfer_purity').select2('open');
                } catch (e) {
                    $('#transfer_purity').focus();
                }
            }, 100);
        } else if (stepId === 'step_remark') {
            setTimeout(function() {
                $('#transfer_remark').focus().select();
            }, 100);
        }
    }

    // Step 1: Destination advance
    function advanceFromTo() {
        var toVal = $('#transfer_to_id').val();
        var toName = $('#transfer_to_id').find('option:selected').data('name');
        var fromId = $('#transfer_from_id').val();
        var fromName = $('#transfer_from_name').val();

        if (!toVal) {
            $('#err_transfer_to').show();
            try {
                $('#transfer_to_id').select2('open');
            } catch (e) {}
            return;
        }
        $('#err_transfer_to').hide();

        if (toVal === 'cust' || toName === 'Customer') {
            var pKey = $('#transfer_private_key').val();
            window.location.href = '<?= base_url('raw-waves/index') ?>/' + encodeURIComponent(pKey);
            return;
        }

        if (toName === 'Loss') {
            $.post('<?= base_url('process-waves/check-loss') ?>', {
                from_id: fromId,
                from_name: fromName
            }, function(res) {
                if (res && res.status === 'loss_percentage_error') {
                    swal({
                        title: "Loss Limit Warning",
                        text: "Loss threshold exceeded for " + (res.msg || 'department'),
                        type: "warning",
                        confirmButtonColor: "#2563eb",
                        confirmButtonText: "OK"
                    });
                }
                goToStep('step_mapped_cust');
            }, 'json').fail(function() {
                goToStep('step_mapped_cust');
            });
        } else {
            goToStep('step_mapped_cust');
        }
    }

    $('#transfer_to_id').on('change select2:select', function() {
        var val = $(this).val();
        if (val) {
            advanceFromTo();
        }
    });

    // Step 2: Mapped Cust Name advance
    function advanceFromMappedCust() {
        var mappedCustId = $('#transfer_mapped_cust_id').val();
        if (!mappedCustId) {
            $('#err_transfer_mapped_cust').show();
            try {
                $('#transfer_mapped_cust_id').select2('open');
            } catch (e) {}
            return;
        }
        $('#err_transfer_mapped_cust').hide();
        var selectedPurity = $('#transfer_mapped_cust_id option:selected').data('purity');
        if (selectedPurity && !$('#transfer_purity').val()) {
            var formattedPurity = parseFloat(selectedPurity).toFixed(2);
            if ($('#transfer_purity option[value="' + formattedPurity + '"]').length === 0) {
                $('#transfer_purity').append(new Option(formattedPurity + '%', formattedPurity, true, true));
            }
            $('#transfer_purity').val(formattedPurity).trigger('change');
        }
        goToStep('step_gross_weight');
    }

    $('#transfer_mapped_cust_id').on('change select2:select', function() {
        var val = $(this).val();
        if (val) {
            advanceFromMappedCust();
        }
    });

    // Step 3: Gross Weight advance
    function advanceFromWeight() {
        var grossWt = parseFloat($('#transfer_gross_weight').val());
        var fromId = $('#transfer_from_id').val();
        var toId = $('#transfer_to_id').val();
        var defaultPurityVal = <?= !empty($activePurities) ? (float)$activePurities[0]['purity_value'] : 92.0 ?>;
        var purity = parseFloat($('#transfer_purity').val()) || defaultPurityVal;

        if (isNaN(grossWt) || grossWt <= 0) {
            $('#err_transfer_weight').text('Please enter a valid gross weight (> 0).').show();
            $('#transfer_gross_weight').focus().select();
            return;
        }
        $('#err_transfer_weight').hide();

        // Perform stock check against available departmental inventory
        $.post('<?= base_url('process-waves/check-weight') ?>', {
            from_id: fromId,
            to_id: toId,
            gross_weight: grossWt,
            purity: purity
        }, function(valid) {
            if (valid !== 'true') {
                $('#err_transfer_weight').text('Available stock in source department is insufficient.').show();
                swal({
                    title: "Insufficient Stock",
                    text: "Source department does not have enough balance for this transfer.",
                    type: "error",
                    confirmButtonColor: "#2563eb",
                    confirmButtonText: "OK"
                });
                $('#transfer_gross_weight').focus().select();
                return;
            }

            $('#err_transfer_weight').hide();
            var toName = $('#transfer_to_id option:selected').data('name');
            if (toName === 'Refining') {
                if ($('#transfer_purity option[value="100.00"]').length === 0) {
                    $('#transfer_purity').append(new Option('100.00%', '100.00', true, true));
                }
                $('#transfer_purity').val('100.00').trigger('change');
                goToStep('step_remark');
            } else {
                goToStep('step_purity');
            }
        }).fail(function() {
            goToStep('step_purity');
        });
    }

    // Step 4: Purity advance
    function advanceFromPurity() {
        var purity = parseFloat($('#transfer_purity').val());
        if (isNaN(purity) || purity <= 0 || purity > 100) {
            $('#err_transfer_purity').show();
            try { $('#transfer_purity').select2('open'); } catch(e) { $('#transfer_purity').focus(); }
            return;
        }
        $('#err_transfer_purity').hide();
        goToStep('step_remark');
    }

    $('#transfer_purity').on('change select2:select', function() {
        var val = $(this).val();
        if (val && $('#step_purity').is(':visible')) {
            advanceFromPurity();
        }
    });

    // Step 5: Submission
    var isSubmitting = false;
    function submitTransfer() {
        var $form = $('#formProcessTransfer');
        if (isSubmitting || $form.data('submitting') === true) return;

        var fromId = $('#transfer_from_id').val();
        var toId = $('#transfer_to_id').val();
        var mappedCustId = $('#transfer_mapped_cust_id').val();
        var grossWt = parseFloat($('#transfer_gross_weight').val());
        var purity = parseFloat($('#transfer_purity').val());

        if (!toId) {
            goToStep('step_to');
            return;
        }
        if (!mappedCustId) {
            goToStep('step_mapped_cust');
            return;
        }
        if (isNaN(grossWt) || grossWt <= 0) {
            goToStep('step_gross_weight');
            return;
        }
        if (isNaN(purity) || purity <= 0 || purity > 100) {
            goToStep('step_purity');
            return;
        }

        isSubmitting = true;
        $form.data('submitting', true);

        var $submitBtn = $('#non_locker_submit_button');
        $submitBtn.prop('disabled', true).addClass('disabled');
        $submitBtn.html('<i class="icon-spinner2 spinner position-left"></i> Processing...');
        $('#transfer_remark').prop('disabled', true);

        var formData = new FormData($form[0]);

        $.ajax({
            url: '<?= base_url('process-waves/insert-transfer') ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.status === 'true') {
                    $('#ajaxTransferModal').modal('hide');

                    // Open printable voucher popup
                    if (resp.process_waves_id) {
                        window.open('<?= base_url('process-waves/print-transaction') ?>/' + resp.process_waves_id, 'popup', 'width=650,height=650');
                    }

                    var hasReloaded = false;
                    function finishAndReload() {
                        if (hasReloaded) return;
                        hasReloaded = true;
                        try {
                            if (typeof swal !== 'undefined' && swal.close) {
                                swal.close();
                            }
                        } catch(e) {}
                        $('.sweet-alert, .sweet-overlay').remove();
                        window.location.reload();
                    }

                    if (document.activeElement && document.activeElement.blur) {
                        document.activeElement.blur();
                    }

                    var autoCloseTimer = setTimeout(function() {
                        finishAndReload();
                    }, 1500);

                    swal({
                        title: "Success!",
                        text: "Process Waves transfer completed successfully!",
                        type: "success",
                        timer: 1500,
                        showConfirmButton: false,
                        confirmButtonColor: "#2563eb",
                        confirmButtonText: "OK",
                        closeOnConfirm: true
                    }, function() {
                        clearTimeout(autoCloseTimer);
                        finishAndReload();
                    });

                    $('.sweet-alert button.confirm').hide();
                } else {
                    isSubmitting = false;
                    $form.data('submitting', false);
                    $submitBtn.prop('disabled', false).removeClass('disabled');
                    $submitBtn.html('<span id="non_locker_submit_text">Transfer</span> <i id="non_locker_submit_icon" class="icon-check position-right"></i>');
                    $('#transfer_remark').prop('disabled', false).focus();
                    swal({
                        title: "Transfer Failed",
                        text: (resp && resp.message) ? resp.message : "Failed to complete transfer.",
                        type: "error",
                        confirmButtonColor: "#2563eb",
                        confirmButtonText: "OK"
                    });
                }
            },
            error: function(xhr) {
                isSubmitting = false;
                $form.data('submitting', false);
                $submitBtn.prop('disabled', false).removeClass('disabled');
                $submitBtn.html('<span id="non_locker_submit_text">Transfer</span> <i id="non_locker_submit_icon" class="icon-check position-right"></i>');
                $('#transfer_remark').prop('disabled', false).focus();
                var errMsg = "Server error processing transfer.";
                try {
                    var json = JSON.parse(xhr.responseText);
                    if (json && json.message) errMsg = json.message;
                } catch(e) {}
                swal({
                    title: "Error",
                    text: errMsg,
                    type: "error",
                    confirmButtonColor: "#2563eb",
                    confirmButtonText: "OK"
                });
            }
        });
    }

    // Keyboard Enter navigation across all sequential steps
    $('#formProcessTransfer').on('keydown', function(e) {
        if (e.which === 13 || e.keyCode === 13) {
            var activeStep = $('.transfer-step.show').attr('id');
            if (activeStep === 'step_to') {
                e.preventDefault();
                advanceFromTo();
            } else if (activeStep === 'step_mapped_cust') {
                e.preventDefault();
                advanceFromMappedCust();
            } else if (activeStep === 'step_gross_weight') {
                e.preventDefault();
                advanceFromWeight();
            } else if (activeStep === 'step_purity') {
                e.preventDefault();
                advanceFromPurity();
            } else if (activeStep === 'step_remark') {
                e.preventDefault();
                // Route Enter key through form submit to ensure single execution path
                $('#formProcessTransfer').submit();
            }
        }
    });

    // Form submit event handler
    $('#formProcessTransfer').on('submit', function(e) {
        e.preventDefault();
        var activeStep = $('.transfer-step.show').attr('id');
        if (activeStep === 'step_remark') {
            submitTransfer();
        }
    });
});
</script>
<?php endif; ?>
