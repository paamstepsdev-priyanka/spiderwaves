<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?><?= ($form_type === 'Edit') ? 'Edit Wave' : 'Add Wave' ?> - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>
<a href="<?= base_url('waves') ?>">Waves</a> / <?= ($form_type === 'Edit') ? 'Edit Wave' : 'Add Wave' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1 style="margin: 0;"><?= ($form_type === 'Edit') ? 'Edit Wave Transaction' : 'Record Wave Transaction' ?></h1>
    </div>
    <div class="sw-page-actions">
        <a href="<?= base_url('waves') ?>" class="sw-btn-secondary">
            <i class="icon-arrow-left52"></i>
            <span>Back to Ledger</span>
        </a>
    </div>
</div>

<?php 
    $waveRecord = (!empty($waves_data) && isset($waves_data[0])) ? $waves_data[0] : null;
    $selectedMode = ($form_type === 'Edit' && $waveRecord) ? $waveRecord->transactional_mode : 'RK';
    $selectedType = ($form_type === 'Edit' && $waveRecord) ? $waveRecord->transactional_type : 'receipt';
    $selectedCustId = ($form_type === 'Edit' && $waveRecord) ? $waveRecord->cust_id : '';
    $grossWeight = ($form_type === 'Edit' && $waveRecord && $waveRecord->gross_weight > 0) ? view_round_up($waveRecord->gross_weight) : '';
    $purity = ($form_type === 'Edit' && $waveRecord && $waveRecord->purity > 0) ? view_round_up($waveRecord->purity, 2) : '';
    $amount = ($form_type === 'Edit' && $waveRecord && $waveRecord->amount > 0) ? (float)$waveRecord->amount : '';
    $remarkVal = ($form_type === 'Edit' && $waveRecord) ? $waveRecord->remark : '';
    $isOtherRemark = false;
    if ($form_type === 'Edit' && !empty($remarkVal)) {
        $isOtherRemark = true;
        if (!empty($remark_data)) {
            foreach ($remark_data as $rmk) {
                if ($rmk['remark'] === $remarkVal) {
                    $isOtherRemark = false;
                    break;
                }
            }
        }
    }
?>

<!-- Form Card -->
<div class="sw-table-card" style="max-width: 800px; margin: 0 auto; padding: 30px;">
    <form id="add_in_out_form" action="<?= base_url('waves/insert_in_out') ?>" method="post" class="form-horizontal" novalidate>
        <input type="hidden" name="form_type" id="form_type" value="<?= esc($form_type) ?>">
        <input type="hidden" name="base_url" id="base_url" value="<?= base_url() ?>">
        <input type="hidden" name="private_key" id="private_key" value="<?= ($form_type === 'Edit' && $waveRecord) ? esc($waveRecord->private_key) : '' ?>">
        <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

        <!-- Transaction Mode: RK / MT / OR -->
        <div class="form-group" style="margin-bottom: 24px;">
            <label class="col-sm-3 control-label" style="font-weight: 600; color: #0f172a; padding-top: 8px;">
                Mode <span class="text-danger">*</span>
            </label>
            <div class="col-sm-9">
                <div style="display: flex; gap: 16px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 18px; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500;">
                        <input type="radio" name="transactional_mode" id="mode_rk" value="RK" <?= ($selectedMode === 'RK') ? 'checked' : '' ?> class="mode-radio">
                        <span>RK (Cash)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 18px; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500;">
                        <input type="radio" name="transactional_mode" id="mode_mt" value="MT" <?= ($selectedMode === 'MT') ? 'checked' : '' ?> class="mode-radio">
                        <span>MT (Fine Gold)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 18px; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500;">
                        <input type="radio" name="transactional_mode" id="mode_or" value="OR" <?= ($selectedMode === 'OR') ? 'checked' : '' ?> class="mode-radio">
                        <span>OR (Metal Weight)</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Transaction Type: Issue / Receipt -->
        <div class="form-group" style="margin-bottom: 24px;">
            <label class="col-sm-3 control-label" style="font-weight: 600; color: #0f172a; padding-top: 8px;">
                Type <span class="text-danger">*</span>
            </label>
            <div class="col-sm-9">
                <div style="display: flex; gap: 16px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 18px; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500;">
                        <input type="radio" name="transactional_type" id="type_receipt" value="receipt" <?= ($selectedType === 'receipt') ? 'checked' : '' ?> required>
                        <span style="color: #2563eb; font-weight: 600;"><i class="icon-arrow-down5"></i> Receipt (Inward)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 18px; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 500;">
                        <input type="radio" name="transactional_type" id="type_issue" value="issue" <?= ($selectedType === 'issue') ? 'checked' : '' ?> required>
                        <span style="color: #dc2626; font-weight: 600;"><i class="icon-arrow-up5"></i> Issue (Outward)</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Customer Selection -->
        <div class="form-group" style="margin-bottom: 24px;">
            <label for="cust_id" class="col-sm-3 control-label" style="font-weight: 600; color: #0f172a; padding-top: 8px;">
                Customer Name <span class="text-danger">*</span>
            </label>
            <div class="col-sm-9">
                <select name="cust_id" id="cust_id" class="select2" style="width: 100%;" required>
                    <option value="">Select Customer</option>
                    <?php if (!empty($cust_data)): ?>
                        <?php foreach ($cust_data as $cust): ?>
                            <option value="<?= $cust['cust_id'] ?>" <?= ($selectedCustId == $cust['cust_id']) ? 'selected' : '' ?>>
                                <?= esc($cust['cust_name']) ?> (<?= strtoupper(esc($cust['type'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Metal Fields: Gross Weight (MT / OR) -->
        <div class="form-group MT" style="<?= in_array($selectedMode, ['MT', 'OR']) ? 'display: block;' : 'display: none;' ?> margin-bottom: 24px;">
            <label for="gross_weight" class="col-sm-3 control-label" style="font-weight: 600; color: #0f172a; padding-top: 8px;">
                Gross Weight (g) <span class="text-danger">*</span>
            </label>
            <div class="col-sm-9">
                <input type="number" step="0.001" class="sw-input" name="gross_weight" id="gross_weight" placeholder="0.000" value="<?= esc($grossWeight) ?>" style="width: 100%; height: 42px;">
            </div>
        </div>

        <!-- Metal Fields: Purity / Touch (MT / OR) -->
        <div class="form-group MT" style="<?= in_array($selectedMode, ['MT', 'OR']) ? 'display: block;' : 'display: none;' ?> margin-bottom: 24px;">
            <label for="purity" class="col-sm-3 control-label" style="font-weight: 600; color: #0f172a; padding-top: 8px;">
                Purity / Touch (%) <span class="text-danger">*</span>
            </label>
            <div class="col-sm-9">
                <select name="purity" id="purity" class="select2" style="width: 100%;">
                    <option value="">Select Purity</option>
                    <?php 
                    $currPurity = (float)($purity ?? 0);
                    $matched = false;
                    if (!empty($active_purities)): 
                        foreach ($active_purities as $p): 
                            $valStr = number_format((float)$p['purity_value'], 2, '.', '');
                            $isSel = ($currPurity > 0 && round($currPurity, 2) == round((float)$p['purity_value'], 2));
                            if ($isSel) $matched = true;
                    ?>
                        <option value="<?= $valStr ?>" <?= $isSel ? 'selected' : '' ?>>
                            <?= esc($p['purity_name']) ?> (<?= $valStr ?>%)
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if ($currPurity > 0 && !$matched): ?>
                        <option value="<?= number_format($currPurity, 2, '.', '') ?>" selected>
                            <?= number_format($currPurity, 2, '.', '') ?>% (Current)
                        </option>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Cash Amount (RK) -->
        <div class="form-group RK" style="<?= ($selectedMode === 'RK') ? 'display: block;' : 'display: none;' ?> margin-bottom: 24px;">
            <label for="amount" class="col-sm-3 control-label" style="font-weight: 600; color: #0f172a; padding-top: 8px;">
                Amount (&#8377;) <span class="text-danger">*</span>
            </label>
            <div class="col-sm-9">
                <input type="number" step="0.01" class="sw-input" name="amount" id="amount" placeholder="Enter rupee cash amount" value="<?= esc($amount) ?>" style="width: 100%; height: 42px;" <?= ($selectedMode === 'RK') ? 'required' : '' ?>>
            </div>
        </div>

        <!-- Remark -->
        <div class="form-group" style="margin-bottom: 24px;">
            <label for="remark" class="col-sm-3 control-label" style="font-weight: 600; color: #0f172a; padding-top: 8px;">
                Remark <span class="text-danger">*</span>
            </label>
            <div class="col-sm-9">
                <select name="remark" id="remark" class="select2" style="width: 100%;" required>
                    <option value="">Select Remark</option>
                    <?php if (!empty($remark_data)): ?>
                        <?php foreach ($remark_data as $rmk): ?>
                            <option value="<?= esc($rmk['remark']) ?>" <?= (!$isOtherRemark && $remarkVal === $rmk['remark']) ? 'selected' : '' ?>>
                                <?= esc($rmk['remark']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <option value="other" <?= $isOtherRemark ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
        </div>

        <!-- Other Remark Input -->
        <div class="form-group other_remark_row" style="<?= $isOtherRemark ? 'display: block;' : 'display: none;' ?> margin-bottom: 24px;">
            <label for="other_remark" class="col-sm-3 control-label" style="font-weight: 600; color: #0f172a; padding-top: 8px;">
                Other Remark <span class="text-danger">*</span>
            </label>
            <div class="col-sm-9">
                <input type="text" class="sw-input" name="other_remark" id="other_remark" placeholder="Enter custom remark" value="<?= $isOtherRemark ? esc($remarkVal) : '' ?>" style="width: 100%; height: 42px;">
            </div>
        </div>

        <hr style="border-top: 1px solid #e2e8f0; margin: 30px 0 24px;">

        <!-- Total Balances & Submit Button -->
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div style="display: flex; gap: 20px; font-size: 13px;">
                <div>
                    <span style="color: #64748b;">RK Balance:</span>
                    <strong style="color: #d97706; margin-left: 4px;">&#8377; <?= number_format(abs((float)$rk_total_balance), 2) ?></strong>
                </div>
                <div>
                    <span style="color: #64748b;">MT Balance:</span>
                    <strong style="color: #2563eb; margin-left: 4px;"><?= view_round_up($mt_total_balance) ?> g</strong>
                </div>
                <div>
                    <span style="color: #64748b;">OR Balance:</span>
                    <strong style="color: #2563eb; margin-left: 4px;"><?= view_round_up($or_total_balance) ?> g</strong>
                </div>
            </div>

            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="<?= base_url('waves') ?>" class="sw-btn-secondary" style="height: 42px; padding: 0 20px;">Cancel</a>
                <button type="submit" class="sw-btn-primary" id="submitBtn" style="height: 42px; padding: 0 24px;">
                    <i class="icon-checkmark-circle"></i>
                    <span><?= ($form_type === 'Edit') ? 'Update Wave' : 'Submit Wave' ?></span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Initialize Select2 on Customer and Remark dropdowns
    if ($.fn.select2) {
        $('#cust_id').select2({
            placeholder: "Select Customer",
            allowClear: true,
            width: '100%'
        });
        $('#remark').select2({
            placeholder: "Select Remark",
            allowClear: true,
            width: '100%'
        });
    }

    // Mode switcher
    $('.mode-radio').change(function() {
        var mode = $(this).val();
        if (mode === 'MT' || mode === 'OR') {
            $('.MT').show();
            $('.RK').hide();
            $('#gross_weight').attr('required', 'true');
            $('#purity').attr('required', 'true');
            $('#amount').removeAttr('required').val('');
        } else {
            $('.MT').hide();
            $('.RK').show();
            $('#amount').attr('required', 'true');
            $('#gross_weight').removeAttr('required').val('');
            $('#purity').removeAttr('required').val('').trigger('change');
        }
    });

    // Remark switcher
    $('#remark').on('change', function() {
        var remark = $(this).val();
        if (remark === 'other') {
            $('.other_remark_row').show();
            $('#other_remark').attr('required', 'true');
        } else {
            $('.other_remark_row').hide();
            $('#other_remark').removeAttr('required').val('');
        }
    });

    // Form submit handler with SweetAlert feedback
    $('#add_in_out_form').submit(function(e) {
        e.preventDefault();
        var form = $(this);
        var formType = $('#form_type').val();
        var submitBtn = $('#submitBtn');
        submitBtn.prop('disabled', true);

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(resp) {
                if (resp === 'true' || resp.result === 'true') {
                    var successMsg = (formType === 'Edit') 
                        ? "Wave Updated Successfully!" 
                        : "Wave Inserted Successfully!";

                    swal({
                        title: "Success!",
                        text: successMsg,
                        type: "success",
                        confirmButtonColor: "#2563eb",
                        closeOnConfirm: true
                    }, function() {
                        window.location.href = "<?= base_url('waves') ?>";
                    });
                } else {
                    swal({
                        title: "Error!",
                        text: "Failed to record transaction. Please verify your inputs.",
                        type: "error",
                        confirmButtonColor: "#ef4444"
                    });
                    submitBtn.prop('disabled', false);
                }
            },
            error: function() {
                swal({
                    title: "System Error",
                    text: "An error occurred while submitting form.",
                    type: "error",
                    confirmButtonColor: "#ef4444"
                });
                submitBtn.prop('disabled', false);
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
