<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Locker Stock Verification - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Home / Locker<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Page Header Section -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: #0f172a;">Locker Stock Verification</h1>
    </div>

    <div class="sw-page-actions">
        <!-- Restart Locker Verification Process Button (Exact CI2 Parity) -->
        <button type="button" class="btn btn-default btn-xs" onclick="restart_locker_verification_process();">
            Restart Locker Verification Process
        </button>
    </div>
</div>

<input type="hidden" name="process_account_id" id="process_account_id" value="<?= esc($accountId) ?>">
<input type="hidden" name="base_url" id="base_url" value="<?= base_url() ?>">

<!-- Main Dual-Column Grid Layout: Current Stock (Left) & Scan Barcode (Right) -->
<div class="row">
    <!-- Left Column: Current Stock -->
    <div class="col-md-6 col-sm-12" style="margin-bottom: 24px;">
        <div class="sw-table-card">
            <!-- Header -->
            <div class="sw-table-header" style="height: 52px; min-height: 52px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between; padding: 10px 20px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-block; width: 10px; height: 10px; background-color: #2563eb; border-radius: 50%;"></span>
                    <h3 class="sw-table-title" style="margin: 0;">Current Stock</h3>
                </div>
            </div>

            <!-- Table Container -->
            <div class="sw-table-responsive">
                <table class="sw-table" id="current_stock_table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>From</th>
                            <th style="text-align: right;">Weight</th>
                            <th style="text-align: right;">P(%)</th>
                            <th style="text-align: center;">Time</th>
                        </tr>
                    </thead>
                    <tbody id="current_stock_tbody">
                        <?php if (!empty($unverifiedStock)): ?>
                            <?php foreach ($unverifiedStock as $row): ?>
                            <tr id="<?= esc($row['process_waves_id']) ?>">
                                <td style="font-weight: 600; color: #0f172a;">
                                    <?= esc($row['process_waves_id']) ?>
                                </td>
                                <td style="font-weight: 600; color: #0f172a;">
                                    <?= esc($row['mapped_cust_name'] ?: ($row['cust_name'] ?: '')) ?>
                                </td>
                                <td style="color: #475569;">
                                    <?= esc($row['from_name'] ?: '') ?>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #0f172a;">
                                    <?= number_format((float)$row['gross_weight'], 3, '.', '') ?>
                                </td>
                                <td style="text-align: right; color: #475569;">
                                    <?= number_format((float)$row['purity'], 3, '.', '') ?>
                                </td>
                                <td style="text-align: center; font-size: 12px; color: #64748b; white-space: nowrap;">
                                    <?= date('d/m/Y H:i:s', strtotime($row['date'])) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr id="no_stock_row">
                                <td colspan="6" style="text-align: center; padding: 35px 20px; color: #64748b;">
                                    <i class="icon-inbox text-muted" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>
                                    There is no result for you.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f8fafc; border-top: 2px solid #e2e8f0; font-weight: 700;">
                            <td style="color: #0f172a; font-size: 13px; font-weight: 700; white-space: nowrap;">Fine MT</td>
                            <td colspan="5" style="text-align: left; color: #0f172a; font-size: 13px; font-weight: 700;">
                                <?= number_format((float)$receiveTotalBalance, 3, '.', '') ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Scan Barcode for Verification -->
    <div class="col-md-6 col-sm-12" style="margin-bottom: 24px;">
        <div class="sw-table-card">
            <!-- Header -->
            <div class="sw-table-header" style="height: 52px; min-height: 52px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between; padding: 10px 20px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="display: inline-block; width: 10px; height: 10px; background-color: #2563eb; border-radius: 50%;"></span>
                    <h3 class="sw-table-title" style="margin: 0;">Scan Barcode for Verification</h3>
                </div>
            </div>

            <!-- Panel Content -->
            <div style="padding: 20px;">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="font-weight: 600; color: #334155; margin-bottom: 6px; display: block;">Bar-code</label>
                    <input type="text" 
                           class="form-control" 
                           name="barcode_process_waves_id" 
                           id="barcode_process_waves_id" 
                           placeholder="Bar-Code" 
                           value="" 
                           autocomplete="off">
                </div>

                <div style="margin-bottom: 15px;">
                    <button type="button" id="barcode_button" class="btn btn-success">
                        Confirm This Data <i class="icon-check position-right"></i>
                    </button>
                </div>

                <!-- Success Alert Message -->
                <div class="row" id="success_div" style="display:none; margin-top: 15px; margin-left: 0; margin-right: 0;">
                    <div class="sw-alert sw-alert-success" style="margin-bottom: 0;">
                        <i class="icon-checkmark-circle"></i>
                        <span><strong>Well done!</strong> You successfully verified this stock in locker using barcode.</span>
                        <button type="button" class="close" data-dismiss="alert" style="margin-left: auto; background: none; border: none; font-size: 16px; color: inherit; cursor: pointer; line-height: 1;">&times;</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $("#barcode_process_waves_id").focus();  

    $("#barcode_process_waves_id").change(function() {  
        clear_this_process_wave();
    });

    $("#barcode_process_waves_id").on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            clear_this_process_wave();
        }
    });

    $("#barcode_button").click(function() {  
        clear_this_process_wave();
    });
});

var isVerifying = false;

function clear_this_process_wave() {
    var rawInput = $.trim($('#barcode_process_waves_id').val());
    if (!rawInput) {
        $("#barcode_process_waves_id").focus();
        return;
    }

    if (isVerifying) return;

    var process_waves_id = rawInput;
    var base_url = $('#base_url').val();

    if ($("#" + process_waves_id).length) {
        isVerifying = true;
        var url = base_url + 'process_waves/mark_locker_stock_verification_done';
        $.post(url, {
            process_waves_id: process_waves_id
        }, function(data) {
            isVerifying = false;
            if (data == "true") {
                $("#" + process_waves_id).hide(1000, function() {
                    $(this).remove();
                    if ($("#current_stock_tbody tr").length === 0) {
                        $("#current_stock_tbody").html('<tr id="no_stock_row"><td colspan="6" style="text-align: center; padding: 35px 20px; color: #64748b;"><i class="icon-inbox text-muted" style="font-size: 28px; display: block; margin-bottom: 8px;"></i>There is no result for you.</td></tr>');
                    }
                });

                $("#success_div").show().delay(500).fadeOut(3000);
                $('#barcode_process_waves_id').val("");
                $("#barcode_process_waves_id").focus(); 
            } else {
                if ($.fn.effect) {
                    $("#barcode_process_waves_id").effect("shake");
                }
                if (typeof swal !== 'undefined') {
                    swal({
                        title: "Error!",
                        text: "There was some issue with updating data!",
                        confirmButtonColor: "#EF5350",
                        type: "error"
                    }, function() {                            
                        $("#barcode_process_waves_id").focus(); 
                    });
                } else {
                    alert("There was some issue with updating data!");
                    $("#barcode_process_waves_id").focus();
                }
            }
        }).fail(function() {
            isVerifying = false;
            if (typeof swal !== 'undefined') {
                swal({
                    title: "Error!",
                    text: "There was some issue communicating with the server!",
                    confirmButtonColor: "#EF5350",
                    type: "error"
                }, function() {
                    $("#barcode_process_waves_id").focus();
                });
            }
        });
    } else {
        if (typeof swal !== 'undefined') {
            swal({
                title: "Error!",
                text: "This Stock does not Exists!",
                confirmButtonColor: "#EF5350",
                type: "error"
            }, function() {                            
                $("#barcode_process_waves_id").focus(); 
            });
        } else {
            alert("This Stock does not Exists!");
            $("#barcode_process_waves_id").focus();
        }
    }
}

function restart_locker_verification_process() {
    if (typeof swal !== 'undefined') {
        swal({
            title: "Are you sure?",
            text: "Do you want to restart the Locker verification process?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#2563eb",
            confirmButtonText: "Yes, Restart!",
            closeOnConfirm: false
        }, function(isConfirm) {
            if (isConfirm) {
                send_restart_request();
            }
        });
    } else {
        if (confirm("Do you want to restart the Locker verification process?")) {
            send_restart_request();
        }
    }
}

function send_restart_request() {
    var base_url = $('#base_url').val();
    var url = base_url + 'process_waves/restart_locker_verification_process';
    $.post(url, function(data) {
        if (data == "true") {
            if (typeof swal !== 'undefined') {
                swal({
                    title: "Success!",
                    text: "Locker Verification Restarted Successfully!",
                    confirmButtonColor: "#66BB6A",
                    type: "success",
                    closeOnConfirm: false
                }, function() {
                    location.reload(true);                                                              
                });
            } else {
                alert("Locker Verification Restarted Successfully!");
                location.reload(true);
            }
        } else {
            if (typeof swal !== 'undefined') {
                swal({
                    title: "Error!",
                    text: "There was some issue with updating data!",
                    confirmButtonColor: "#EF5350",
                    type: "error"
                });
            } else {
                alert("There was some issue with updating data!");
            }
        }
    }).fail(function() {
        if (typeof swal !== 'undefined') {
            swal({
                title: "Error!",
                text: "Failed to communicate with verification server.",
                type: "error"
            });
        }
    });
}
</script>

<?= $this->endSection() ?>
