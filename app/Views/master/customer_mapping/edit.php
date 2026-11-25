<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Update Mapped Customer - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Update Mapped Customer<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Update Mapped Customer</h1>
    </div>
    <div class="sw-page-actions">
        <a href="<?= base_url('master/customer-mapping') ?>" class="sw-btn-secondary">
            <i class="icon-arrow-left52"></i>
            <span>Back to List</span>
        </a>
    </div>
</div>

<!-- Flash Alerts -->
<?php if (session()->getFlashdata('error')): ?>
    <div class="sw-alert sw-alert-danger">
        <i class="icon-alert"></i>
        <span><?= esc(session()->getFlashdata('error')) ?></span>
    </div>
<?php endif; ?>

<!-- Form Card -->
<div class="sw-form-card">
    <form id="add_edit_cust_mapping_form" action="<?= base_url('master/customer-mapping/update/' . $mapping['private_key']) ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="form_type" id="form_type" value="Edit">
        <input type="hidden" name="base_url" id="base_url" value="<?= base_url() ?>">
        <input type="hidden" name="private_key" id="private_key" value="<?= esc($mapping['private_key']) ?>">

        <div class="sw-form-section-title">
            <i class="icon-git-branch" style="margin-right: 6px; color: var(--sw-primary);"></i> Mapped Customer Details
        </div>

        <div class="row">
            <!-- Mapped Customer Name -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Mapped Customer Code / Name <span class="req">*</span></label>
                    <input type="text" class="sw-input" style="padding-left: 14px; text-transform: uppercase;" name="mapped_cust_name" id="mapped_cust_name" placeholder="e.g. SO 92" value="<?= esc($mapping['mapped_cust_name']) ?>" required autofocus>
                    <div id="mapped_cust_name-error" style="color: #dc2626; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                </div>
            </div>

            <!-- Purity (%) -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Purity (%) <span class="req">*</span></label>
                    <select class="select2" style="width: 100%;" name="purity" id="purity" required>
                        <option value="">Select Purity</option>
                        <?php 
                        $currentPurity = isset($mapping['purity']) ? (float)$mapping['purity'] : null;
                        $purityMatched = false;
                        if (!empty($activePurities)): 
                            foreach ($activePurities as $p): 
                                $valStr = number_format((float)$p['purity_value'], 2, '.', '');
                                $isSelected = ($currentPurity !== null && round($currentPurity, 2) == round((float)$p['purity_value'], 2));
                                if ($isSelected) {
                                    $purityMatched = true;
                                }
                        ?>
                                <option value="<?= $valStr ?>" <?= $isSelected ? 'selected' : '' ?>>
                                    <?= esc($p['purity_name']) ?> (<?= $valStr ?>%)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if ($currentPurity !== null && !$purityMatched): ?>
                            <option value="<?= number_format($currentPurity, 2, '.', '') ?>" selected>
                                <?= number_format($currentPurity, 2, '.', '') ?>% (Current)
                            </option>
                        <?php endif; ?>
                    </select>
                    <div id="purity-error" style="color: #dc2626; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Customer Dropdown -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Target Customer Profile <span class="req">*</span></label>
                    <select class="select2" style="width: 100%;" name="cust_id" id="cust_id" required>
                        <option value="">Select Customer Name</option>
                        <?php if (!empty($activeCustomers)): ?>
                            <?php foreach ($activeCustomers as $c): ?>
                                <option value="<?= $c['cust_id'] ?>" <?= ((int)$mapping['cust_id'] === (int)$c['cust_id']) ? 'selected' : '' ?>>
                                    <?= esc($c['cust_name']) ?> (ID #<?= (int)$c['cust_id'] ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div id="cust_id-error" style="color: #dc2626; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                </div>
            </div>

            <!-- Collection Dropdown -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Product Collection <span class="req">*</span></label>
                    <select class="select2" style="width: 100%;" name="collection_id" id="collection_id" required>
                        <option value="">Select Product Collection</option>
                        <?php if (!empty($activeCollections)): ?>
                            <?php foreach ($activeCollections as $coll): ?>
                                <option value="<?= $coll['collection_id'] ?>" <?= ((int)$mapping['collection_id'] === (int)$coll['collection_id']) ? 'selected' : '' ?>>
                                    <?= esc($coll['collection_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div id="collection_id-error" style="color: #dc2626; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Mapping Status -->
            <div class="col-sm-6">
                <div class="sw-form-group">
                    <label class="sw-label">Status</label>
                    <div style="display: flex; gap: 24px; padding-top: 6px;">
                        <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13.5px; cursor: pointer; color: #1e293b;">
                            <input type="radio" value="1" name="mapped_cust_status" <?= ($mapping['mapped_cust_status'] === '1') ? 'checked' : '' ?>>
                            <span style="font-weight: 500;">Active</span>
                        </label>
                        <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13.5px; cursor: pointer; color: #64748b;">
                            <input type="radio" value="0" name="mapped_cust_status" <?= ($mapping['mapped_cust_status'] === '0') ? 'checked' : '' ?>>
                            <span style="font-weight: 500;">Inactive</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="sw-form-actions">
            <a href="<?= base_url('master/customer-mapping') ?>" class="sw-btn-secondary">
                Cancel
            </a>
            <button type="submit" id="saveMappingBtn" class="sw-btn-primary">
                <i class="icon-checkmark4"></i>
                <span>Update Mapped Customer</span>
            </button>
        </div>
    </form>
</div>

<!-- Form Script -->
<script type="text/javascript">
$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2').select2({
            placeholder: "Select an option",
            allowClear: true
        });
    }

    $("#add_edit_cust_mapping_form").validate({
        rules: {
            mapped_cust_name: {
                required: true,
                minlength: 1
            },
            purity: {
                required: true,
                number: true,
                min: 0.01,
                max: 100
            },
            cust_id: {
                required: true
            },
            collection_id: {
                required: true
            }
        },
        messages: {
            mapped_cust_name: "Please enter a mapped customer name",
            purity: "Please enter a valid purity percentage (e.g. 92.00)",
            cust_id: "Please select a customer profile",
            collection_id: "Please select a product collection"
        },
        errorPlacement: function(error, element) {
            var errorId = '#' + element.attr('name') + '-error';
            if ($(errorId).length) {
                $(errorId).html(error.text()).show();
            } else {
                error.insertAfter(element);
            }
        },
        success: function(label, element) {
            var errorId = '#' + $(element).attr('name') + '-error';
            $(errorId).hide().html('');
        },
        submitHandler: function(form) {
            var btn = $('#saveMappingBtn');
            btn.prop('disabled', true).css('opacity', 0.7);

            var baseUrl = $('#base_url').val();
            var url = baseUrl + "master/customer-mapping/store";
            var formData = new FormData($("#add_edit_cust_mapping_form")[0]);

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    btn.prop('disabled', false).css('opacity', 1);
                    var isSuccess = false;
                    var message = "Update Successfully!";

                    if (typeof response === 'object') {
                        isSuccess = (response.status === 'success');
                        message = response.message || message;
                    } else {
                        var trimmed = $.trim(response);
                        isSuccess = (trimmed === 'true');
                    }

                    if (isSuccess) {
                        swal({
                            title: "Success!",
                            text: message,
                            confirmButtonColor: "#2563eb",
                            type: "success",
                            closeOnConfirm: false
                        }, function() {
                            location.replace(baseUrl + "master/customer-mapping");
                        });
                    } else {
                        var errMsg = "Mapped Cust Name Already Existed !";
                        swal({
                            title: "Error!",
                            text: errMsg,
                            confirmButtonColor: "#ef4444",
                            type: "error"
                        }, function() {
                            $('#mapped_cust_name-error').show().html(errMsg);
                            $('#mapped_cust_name').focus();
                        });
                    }
                },
                error: function() {
                    btn.prop('disabled', false).css('opacity', 1);
                    swal({
                        title: "Error!",
                        text: "An unexpected server error occurred.",
                        confirmButtonColor: "#ef4444",
                        type: "error"
                    });
                },
                cache: false,
                contentType: false,
                processData: false
            });
            return false;
        }
    });
});
</script>
<?= $this->endSection() ?>
