<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Add Collection - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Add Collection<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Add New Collection</h1>
    </div>
    <div class="sw-page-actions">
        <a href="<?= base_url('master/collection') ?>" class="sw-btn-secondary">
            <i class="icon-arrow-left52"></i>
            <span>Back to Collections</span>
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
    <form id="add_edit_collection_form" action="<?= base_url('master/collection/store') ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="form_type" id="form_type" value="Add">
        <input type="hidden" name="base_url" id="base_url" value="<?= base_url() ?>">

        <div class="sw-form-section-title">
            <i class="icon-stack2" style="margin-right: 6px; color: var(--sw-primary);"></i> Collection Details
        </div>

        <div class="row">
            <!-- Collection Name -->
            <div class="col-sm-8">
                <div class="sw-form-group">
                    <label class="sw-label">Collection Name <span class="req">*</span></label>
                    <input type="text" class="sw-input" style="padding-left: 14px;" name="collection_name" id="collection_name" placeholder="e.g. PINK PLAIN or CNC -PLAIN-YELLOW-92" value="<?= esc(old('collection_name')) ?>" required autofocus autocomplete="off">
                    <div id="collection_name-error" style="color: #dc2626; font-size: 11.5px; margin-top: 4px; display: none;"></div>
                </div>
            </div>

            <!-- Status -->
            <div class="col-sm-4">
                <div class="sw-form-group">
                    <label class="sw-label">Collection Status</label>
                    <div class="sw-radio-group">
                        <label class="sw-radio-label">
                            <input type="radio" name="collection_status" value="1" checked> Active
                        </label>
                        <label class="sw-radio-label">
                            <input type="radio" name="collection_status" value="0"> Inactive
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Bar -->
        <div class="sw-form-actions">
            <a href="<?= base_url('master/collection') ?>" class="sw-btn-secondary">Cancel</a>
            <button type="submit" class="sw-btn-primary" id="btnSubmitCollection">
                <i class="icon-checkmark4"></i>
                <span id="btnSubmitText">Create Collection</span>
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="text/javascript">
$(document).ready(function() {
    var $form = $('#add_edit_collection_form');
    var $btn  = $('#btnSubmitCollection');

    $form.on('submit', function(e) {
        e.preventDefault();

        var collectionName = $.trim($('#collection_name').val());

        if (!collectionName) {
            $('#collection_name-error').text('Collection name is required.').show();
            $('#collection_name').focus();
            return false;
        } else {
            $('#collection_name-error').hide();
        }

        $btn.prop('disabled', true).css('opacity', 0.7);
        $('#btnSubmitText').text('Saving...');

        var formData = new FormData($form[0]);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                var res = $.trim(response);
                if (res === 'true' || res.indexOf('true') === 0) {
                    swal({
                        title: "Success!",
                        text: "Collection created successfully!",
                        type: "success",
                        confirmButtonColor: "#2563eb",
                        confirmButtonText: "View Collections"
                    }, function() {
                        window.location.href = "<?= base_url('master/collection') ?>";
                    });
                } else {
                    $btn.prop('disabled', false).css('opacity', 1);
                    $('#btnSubmitText').text('Create Collection');
                    $('#collection_name-error').text('Collection already exists.').show();
                    $('#collection_name').focus();
                    swal({
                        title: "Error!",
                        text: "Collection Already Exists!",
                        type: "error",
                        confirmButtonColor: "#ef4444"
                    });
                }
            },
            error: function() {
                $btn.prop('disabled', false).css('opacity', 1);
                $('#btnSubmitText').text('Create Collection');
                swal({
                    title: "Network Error",
                    text: "An error occurred while communicating with the server.",
                    type: "error",
                    confirmButtonColor: "#ef4444"
                });
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
