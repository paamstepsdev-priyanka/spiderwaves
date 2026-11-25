<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Waves - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Waves<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php 
$session = session()->get('spider') ?? [];
$isEdit = ($form_type === 'Edit');
$isTransfer = ($form_type === 'transfer_to_cust');
$currentGross = $isEdit && !empty($waves_data[0]['gross_weight']) ? $waves_data[0]['gross_weight'] : ($isTransfer ? '' : ($gross_weight ?? ''));
$currentNet   = $isEdit && !empty($waves_data[0]['net_weight']) ? $waves_data[0]['net_weight'] : ($gross_weight ?? '');
$currentDelBy = $isEdit && !empty($waves_data[0]['delivery_by']) ? $waves_data[0]['delivery_by'] : ($session['user'] ?? '');
$currentRemark = $isEdit && !empty($waves_data[0]['remark']) ? $waves_data[0]['remark'] : '';
?>

<!-- Flash Alerts -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="sw-alert sw-alert-success" style="margin-bottom: 16px;">
        <i class="icon-checkmark-circle"></i>
        <span><?= esc(session()->getFlashdata('success')) ?></span>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="sw-alert sw-alert-danger" style="margin-bottom: 16px;">
        <i class="icon-alert"></i>
        <span><?= esc(session()->getFlashdata('error')) ?></span>
    </div>
<?php endif; ?>

<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1><?= $isTransfer ? 'Waves' : 'Waves Inward Entry' ?></h1>
    </div>
    <div class="sw-page-actions">
        <?php if (($session['access_level'] ?? '') === 'locker' || $isTransfer): ?>
            <a href="<?= base_url('locker') ?>" class="sw-btn-secondary">
                <i class="icon-arrow-left13"></i>
                <span>Back to Locker</span>
            </a>
        <?php else: ?>
            <a href="<?= base_url('raw-waves/manage') ?>" class="sw-btn-secondary">
                <i class="icon-list2"></i>
                <span>Waves Queue</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Main Form Card -->
<div class="sw-form-card">
    <form id="add_in_out_form" action="<?= base_url('raw-waves/store') ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <!-- Hidden Operational Variables -->
        <input type="hidden" name="form_type" id="form_type" value="<?= esc($form_type) ?>">
        <input type="hidden" name="base_url" id="base_url" value="<?= base_url() ?>">
        <input type="hidden" name="private_key" id="private_key" value="<?= esc($private_key ?? '') ?>">
        <input type="hidden" name="process_waves_private_key" id="process_waves_private_key" value="<?= esc($process_waves_private_key ?? '') ?>">
        <input type="hidden" name="cust_id" id="cust_id_hidden" value="<?= esc($selected_mapped_cust_data['cust_id'] ?? ($raw_wave['cust_id'] ?? '')) ?>">
        <input type="hidden" name="transactional_mode" id="transactional_mode" value="OR">
        <input type="hidden" name="transactional_type" id="transactional_type" value="<?= $isTransfer ? 'issue' : 'receipt' ?>">
        <input type="hidden" name="less_weight" id="less_weight" value="0.000">
        <input type="hidden" name="amount" id="amount" value="0.00">
        <input type="hidden" name="other_remark" id="other_remark" value="">

        <div class="sw-form-section-title">
            <i class="icon-certificate" style="margin-right: 6px; color: var(--sw-primary);"></i> Waves
        </div>

        <div class="row">
            <!-- 1. Mapped Cust Name -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Mapped Cust Name <span class="req">*</span></label>
                    <select name="mapped_cust_id" id="mapped_cust_id" class="select2" style="width: 100%;" required autofocus>
                        <option value="">Select Mapped Customer</option>
                        <?php foreach ($mapped_cust_data as $obj): ?>
                            <option value="<?= esc($obj['mapped_cust_id']) ?>" <?= ((string)$mapped_cust_id === (string)$obj['mapped_cust_id']) ? 'selected' : '' ?>>
                                <?= esc($obj['mapped_cust_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- 2. Customer Name (Readonly) -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Customer Name <span class="req">*</span></label>
                    <input type="text" class="sw-input" style="padding-left: 14px; background-color: var(--sw-surface-alt);" name="show_cust_name" id="show_cust_name" placeholder="Customer Name" value="<?= esc($cust_name) ?>" readonly required>
                </div>
            </div>
        </div>

        <div class="row">
            <?php if ($isTransfer): ?>
            <!-- Firm -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Firm <span class="req">*</span></label>
                    <select name="firm_id" id="firm_id" class="select2" style="width: 100%;" required>
                        <option value="">Select Firm</option>
                        <?php if (!empty($firm_data)): ?>
                            <?php foreach ($firm_data as $fobj): ?>
                                <option value="<?= esc($fobj['firm_id']) ?>">
                                    <?= esc($fobj['firm_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <!-- Collection (Readonly) -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Collection <span class="req">*</span></label>
                    <input type="text" class="sw-input" style="padding-left: 14px; background-color: var(--sw-surface-alt);" name="collection_name" id="collection_name" placeholder="Collection Name" value="<?= esc($collection_name) ?>" readonly required>
                </div>
            </div>

            <?php if (!$isTransfer): ?>
            <!-- Wastage (Readonly) -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Wastage (%) <span class="req">*</span></label>
                    <input type="text" class="sw-input" style="padding-left: 14px; background-color: var(--sw-surface-alt);" name="wastage" id="wastage" placeholder="Wastage" value="<?= esc($wastage) ?>" readonly required>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($isTransfer): ?>
        <div class="row">
            <!-- Wastage (Readonly) -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Wastage <span class="req">*</span></label>
                    <input type="text" class="sw-input" style="padding-left: 14px; background-color: var(--sw-surface-alt);" name="wastage" id="wastage" placeholder="Wastage" value="<?= esc($wastage) ?>" readonly required>
                </div>
            </div>

            <!-- PW (Packet Weight) -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">PW</label>
                    <input type="number" step="0.001" class="sw-input" style="padding-left: 14px;" name="packet_weight" id="packet_weight" placeholder="PW" value="<?= esc($packet_weight ?? '') ?>">
                </div>
            </div>
        </div>
        <?php else: ?>
            <input type="hidden" name="packet_weight" id="packet_weight" value="0.000">
        <?php endif; ?>

        <div class="row">
            <!-- GW (Gross Weight Input) -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">GW <span class="req">*</span></label>
                    <input type="number" step="0.001" class="sw-input" style="padding-left: 14px;" name="gross_weight" id="weight" placeholder="GW" required value="<?= esc($currentGross) ?>" autocomplete="off">
                </div>
            </div>

            <!-- NW (Net Weight - Readonly) -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">NW <span class="req">*</span></label>
                    <input type="number" step="0.001" class="sw-input" style="padding-left: 14px; background-color: var(--sw-surface-alt);" name="net_weight" id="net_weight" placeholder="NW" required value="<?= esc($currentNet) ?>" readonly>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Tnch (Purity - Readonly) -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Tnch <span class="req">*</span></label>
                    <input type="number" step="0.01" class="sw-input" style="padding-left: 14px; background-color: var(--sw-surface-alt);" name="purity" id="purity" placeholder="Tnch" value="<?= esc($purity !== '' && is_numeric($purity) ? (float)$purity : $purity) ?>" readonly required>
                </div>
            </div>

            <!-- Delivery By -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Delivery By <span class="req">*</span></label>
                    <input type="text" class="sw-input" style="padding-left: 14px;" name="delivery_by" id="delivery_by" placeholder="Delivery By" value="<?= esc($currentDelBy) ?>" required autocomplete="off">
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Remark -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Remark <span class="req">*</span></label>
                    <select name="remark" id="remark" class="select2" style="width: 100%;" required>
                        <option value="">Select Remark</option>
                        <?php if (!empty($remark_data)): ?>
                            <?php foreach ($remark_data as $robj): ?>
                                <option value="<?= esc($robj['remark']) ?>" <?= ($currentRemark === $robj['remark']) ? 'selected' : '' ?>>
                                    <?= esc($robj['remark']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>

        <?php if ($isTransfer): ?>
        <!-- Key/Value Items 1 to 4 -->
        <div class="row" style="margin-top: 10px;">
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <input type="text" class="sw-input" style="padding-left: 14px;" name="key1" id="key1" placeholder="ITEM 1" value="">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <input type="text" class="sw-input" style="padding-left: 14px;" name="val1" id="val1" placeholder="QTY 1" value="">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <input type="text" class="sw-input" style="padding-left: 14px;" name="key2" id="key2" placeholder="ITEM 2" value="">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <input type="text" class="sw-input" style="padding-left: 14px;" name="val2" id="val2" placeholder="QTY 2" value="">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <input type="text" class="sw-input" style="padding-left: 14px;" name="key3" id="key3" placeholder="ITEM 3" value="">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <input type="text" class="sw-input" style="padding-left: 14px;" name="val3" id="val3" placeholder="QTY 3" value="">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <input type="text" class="sw-input" style="padding-left: 14px;" name="key4" id="key4" placeholder="ITEM 4" value="">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <input type="text" class="sw-input" style="padding-left: 14px;" name="val4" id="val4" placeholder="QTY 4" value="">
                </div>
            </div>
        </div>
        <?php else: ?>
            <input type="hidden" name="key1" value="">
            <input type="hidden" name="val1" value="">
            <input type="hidden" name="key2" value="">
            <input type="hidden" name="val2" value="">
            <input type="hidden" name="key3" value="">
            <input type="hidden" name="val3" value="">
            <input type="hidden" name="key4" value="">
            <input type="hidden" name="val4" value="">
        <?php endif; ?>

        <!-- Action / Submit Buttons -->
        <div style="margin-top: 28px; padding-top: 18px; border-top: 1px solid var(--sw-border); display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
            <a href="<?= (($session['access_level'] ?? '') === 'locker' || $isTransfer) ? base_url('locker') : base_url('raw-waves/manage') ?>" class="sw-btn-secondary">Cancel</a>
            <button type="submit" class="sw-btn-primary" id="btnSubmitWave">
                <i class="icon-checkmark4"></i>
                <span>Submit</span>
            </button>
        </div>
    </form>
</div>

<!-- Modal Container for Voucher Pop-Up after Insert -->
<div class="modal fade" id="voucherModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 840px; margin: 40px auto;">
        <div class="modal-content" style="border-radius: var(--sw-radius); border: 1px solid var(--sw-border); box-shadow: var(--sw-shadow-lg); overflow: hidden; background: #ffffff;">
            <div class="modal-header bg-primary" style="background: var(--sw-primary) !important; color: #ffffff; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; border-bottom: none; border-top-left-radius: calc(var(--sw-radius) - 1px); border-top-right-radius: calc(var(--sw-radius) - 1px);">
                <h5 class="modal-title" style="margin: 0; font-size: 15px; font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 8px;">
                    <i class="icon-qrcode" style="font-size: 16px;"></i>
                    <span>Voucher Slip Generated</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position: static !important; margin: 0 !important; color: #ffffff !important; opacity: 0.9; font-size: 22px; font-weight: 400; line-height: 1; border: none; background: transparent; padding: 0; cursor: pointer; text-shadow: none;">&times;</button>
            </div>
            <div class="modal-body" id="voucherModalBody" style="padding: 24px; background: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 200px;">
                <!-- Loaded dynamically -->
            </div>
            <div class="modal-footer" style="padding: 12px 20px; background: #ffffff; border-top: 1px solid var(--sw-border); display: flex; align-items: center; justify-content: flex-end; gap: 10px;">
                <button type="button" class="sw-btn-secondary" data-dismiss="modal" style="height: 36px; padding: 0 18px; font-size: 13px;">Close</button>
                <button type="button" class="sw-btn-primary" id="btnPrintModalVoucher" onclick="printVoucherSlip();" style="height: 36px; padding: 0 18px; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="icon-printer"></i>
                    <span>Print</span>
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="text/javascript">
$(document).ready(function() {
    var $form = $('#add_in_out_form');
    var $mappedCust = $('#mapped_cust_id');
    var $btnSubmit = $('#btnSubmitWave');
    var base_url = $('#base_url').val();

    // Initialize Select2 on searchable dropdowns
    if ($.fn.select2) {
        $('#mapped_cust_id').select2({
            placeholder: "Select Mapped Customer",
            allowClear: true
        });
        if ($('#firm_id').length) {
            $('#firm_id').select2({
                placeholder: "Select Firm",
                allowClear: true
            });
        }
        $('#remark').select2({
            placeholder: "Select Remark",
            allowClear: true
        });
    }

    // Mapped Customer Selection Change -> Resolve Customer Name, Collection, Wastage, Tnch
    $mappedCust.on('change', function() {
        var mId = $(this).val();
        if (!mId) {
            $('#show_cust_name').val('');
            $('#collection_name').val('');
            $('#purity').val('');
            $('#wastage').val('');
            return;
        }

        // 1. Get Customer Name
        $.post(base_url + 'master/get_cust_name_by_mapped_cust', { mapped_cust_id: mId }, function(res) {
            $('#show_cust_name').val(res).trigger('change');
        });

        // 2. Get Tnch (Purity)
        $.post(base_url + 'master/get_mapped_cust_purity_by_mapped_cust_id', { mapped_cust_id: mId }, function(res) {
            $('#purity').val(res).trigger('change');
        });

        // 3. Get Collection Name
        $.get(base_url + 'master/get_collection_name_by_mapped_customer_id/' + mId, function(res) {
            $('#collection_name').val(res).trigger('change');
        });

        // 4. Get Wastage
        $.get(base_url + 'master/get_customer_wastage_data_by_mapped_customer_id/' + mId, function(res) {
            $('#wastage').val(res || '0.000').trigger('change');
        });

        $('#weight').focus();
    });

    // GW input automatically updates NW only when transactional_type is receipt
    $('#weight').on('input change keyup', function() {
        var txType = $('#transactional_type').val();
        if (txType === 'receipt') {
            var gw = parseFloat($(this).val()) || 0;
            $('#net_weight').val(gw > 0 ? gw.toFixed(3) : '').trigger('change');
        }
    });

    // Form Submission
    $form.on('submit', function(e) {
        e.preventDefault();

        if (typeof window.validateForm === 'function' && !window.validateForm($form[0])) {
            return false;
        }

        var gw = parseFloat($('#weight').val()) || 0;
        if (gw <= 0) {
            if (typeof window.SpiderWaves !== 'undefined' && window.SpiderWaves.showFieldError) {
                window.SpiderWaves.showFieldError($('#weight')[0], 'GW is required');
            }
            $('#weight').focus();
            return false;
        }

        $btnSubmit.prop('disabled', true).css('opacity', 0.7);

        var formData = new FormData($form[0]);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.result === 'true') {
                    if (typeof swal !== 'undefined') {
                        swal({
                            title: "Success!",
                            text: "Raw Wave Inserted Successfully!",
                            type: "success",
                            confirmButtonColor: "#2563eb",
                            confirmButtonText: "OK"
                        }, function() {
                            handleSuccess(res);
                        });
                    } else {
                        alert("Raw Wave Inserted Successfully!");
                        handleSuccess(res);
                    }
                } else {
                    if (typeof swal !== 'undefined') {
                        swal("Error!", res.message || "Failed to save wave.", "error");
                    } else {
                        alert(res.message || "Failed to save wave.");
                    }
                    $btnSubmit.prop('disabled', false).css('opacity', 1);
                }
            },
            error: function(xhr, status, error) {
                alert("Error: " + error);
                $btnSubmit.prop('disabled', false).css('opacity', 1);
            }
        });
    });

    // On voucher modal close: if transfer to cust, redirect back to Locker; if Edit, redirect to Waves Queue
    $('#voucherModal').on('hidden.bs.modal', function () {
        if ($('#form_type').val() === 'transfer_to_cust') {
            window.location.href = '<?= base_url('locker') ?>';
        } else if ($('#form_type').val() === 'Edit') {
            window.location.href = '<?= base_url('raw-waves/manage') ?>';
        }
    });

    function handleSuccess(res) {
        if (res.waves_id) {
            $.get(base_url + 'raw-waves/download-voucher/' + res.waves_id, function(html) {
                $('#voucherModalBody').html(html);
                $('#voucherModal').modal('show');
            }).fail(function() {
                if ($('#form_type').val() === 'transfer_to_cust') {
                    window.location.href = '<?= base_url('locker') ?>';
                } else if ($('#form_type').val() === 'Edit') {
                    window.location.href = '<?= base_url('raw-waves/manage') ?>';
                }
            });
        } else if ($('#form_type').val() === 'transfer_to_cust') {
            window.location.href = '<?= base_url('locker') ?>';
        } else if ($('#form_type').val() === 'Edit') {
            window.location.href = '<?= base_url('raw-waves/manage') ?>';
        }

        if ($('#form_type').val() !== 'transfer_to_cust' && $('#form_type').val() !== 'Edit') {
            // Reset form for next entry
            $form[0].reset();
            if ($.fn.select2) {
                $('#mapped_cust_id').select2('val', '');
                $('#remark').select2('val', '');
            } else {
                $mappedCust.val('');
                $('#remark').val('');
            }
            $('#show_cust_name').val('');
            $('#collection_name').val('');
            $('#wastage').val('');
            $('#net_weight').val('');
            $('#purity').val('');
            $btnSubmit.prop('disabled', false).css('opacity', 1);
        }
    }
});

function printVoucherSlip() {
    var voucherContent = document.getElementById('voucherModalBody').innerHTML;
    if (!voucherContent) return;

    var iframe = document.getElementById('voucherPrintFrame');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'voucherPrintFrame';
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);
    }

    var doc = iframe.contentWindow.document;
    doc.open();
    doc.write('<!DOCTYPE html><html><head><title>Spider</title>');
    doc.write('<style>');
    doc.write('@page { size: A4 portrait; margin: 0 !important; }');
    doc.write('html, body { margin: 0; padding: 10mm 8mm 0 8mm; background: #ffffff; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; box-sizing: border-box; }');
    doc.write('.sw-dual-voucher-container { display: flex !important; flex-direction: row !important; justify-content: flex-start !important; gap: 8mm !important; }');
    doc.write('.sw-voucher-box { width: 95mm !important; box-sizing: border-box !important; border: 1px solid #000000 !important; }');
    doc.write('a[href]:after { content: none !important; }');
    doc.write('</style>');
    doc.write('</head><body>');
    doc.write(voucherContent);
    doc.write('</body></html>');
    doc.close();

    setTimeout(function() {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    }, 250);
}
</script>
<?= $this->endSection() ?>
