<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Petty Cash Cust Master - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Petty Cash Cust<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Petty Cash Cust Master</h1>
    </div>
</div>

<!-- Flash Alerts -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="sw-alert sw-alert-success">
        <i class="icon-checkmark-circle"></i>
        <span><?= esc(session()->getFlashdata('success')) ?></span>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="sw-alert sw-alert-danger">
        <i class="icon-alert"></i>
        <span><?= esc(session()->getFlashdata('error')) ?></span>
    </div>
<?php endif; ?>

<!-- Search & Filter Toolbar -->
<div class="sw-filter-card">
    <form id="pettyCashFilterForm" onsubmit="return false;">
        <div class="row" style="margin: 0; align-items: center;">
            <!-- Search Keyword Input (Col-10) -->
            <div class="col-sm-10 col-xs-12" style="padding: 0 8px 0 0;">
                <div class="sw-search-group" style="width: 100%;">
                    <input type="text" name="q" id="searchKeyword" class="sw-input" style="padding-left: 14px;" placeholder="Search by customer name..." value="<?= esc($search ?? '') ?>" autocomplete="off">
                </div>
            </div>

            <!-- Petty Cash Status Filter (Col-2) -->
            <div class="col-sm-2 col-xs-12" style="padding: 0;">
                <select name="status" id="statusSelect" class="select2" style="width: 100%;">
                    <option value="">All Accounts</option>
                    <option value="1" <?= ($status === '1') ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= ($status === '0') ? 'selected' : '' ?>>No</option>
                </select>
            </div>
        </div>
    </form>
</div>

<!-- Table Container with Bulk Save Form -->
<form id="pettyCashBulkForm" method="post" action="<?= base_url('master/petty-cash/bulk') ?>" novalidate>
    <?= csrf_field() ?>
    <div class="sw-table-card" id="pettyCashTableCard">
        <div class="sw-table-wrapper" id="pettyCashTableWrapper">
            <table class="sw-table" style="table-layout: fixed; width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 70px; text-align: center;">#</th>
                        <th style="width: 45%;">Customer / Vendor</th>
                        <th style="width: 25%; text-align: center;">Type</th>
                        <th style="width: 30%; text-align: center;">Is Petty Cash</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers)): ?>
                        <?php 
                            $page = (int)($pager->getCurrentPage() ?? 1);
                            $perPage = (int)($pager->getPerPage() ?? 25);
                            $startNo = (($page - 1) * $perPage) + 1;
                        ?>
                        <?php foreach ($customers as $idx => $cust): ?>
                            <tr>
                                <td style="text-align: center; color: #94a3b8; font-weight: 500;">
                                    <?= $startNo + $idx ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #1e293b;">
                                        <?= esc($cust['cust_name']) ?>
                                    </div>
                                    <div style="font-size: 11px; color: #94a3b8; font-family: monospace;">
                                        Key: <?= esc(substr($cust['private_key'], 0, 8)) ?>...
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($cust['type'] === 'vendor'): ?>
                                        <span class="sw-badge" style="background: #fef3c7; color: #92400e;">
                                            <i class="icon-store2" style="font-size: 10px; margin-right: 3px;"></i> Vendor
                                        </span>
                                    <?php else: ?>
                                        <span class="sw-badge" style="background: #eff6ff; color: #1e40af;">
                                            <i class="icon-user" style="font-size: 10px; margin-right: 3px;"></i> Customer
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; align-items: center; justify-content: center; gap: 24px;">
                                        <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; margin: 0; font-weight: 500; font-size: 13px; color: #2563eb;">
                                            <input type="radio" name="<?= esc($cust['private_key']) ?>" value="1" <?= ($cust['is_petty_cash'] === '1') ? 'checked' : '' ?> style="accent-color: #2563eb; cursor: pointer; width: 16px; height: 16px;">
                                            <span>Yes</span>
                                        </label>
                                        <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; margin: 0; font-weight: 500; font-size: 13px; color: #64748b;">
                                            <input type="radio" name="<?= esc($cust['private_key']) ?>" value="0" <?= ($cust['is_petty_cash'] === '0') ? 'checked' : '' ?> style="accent-color: #64748b; cursor: pointer; width: 16px; height: 16px;">
                                            <span>No</span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 48px 16px; color: #94a3b8;">
                                <div style="font-size: 40px; margin-bottom: 12px; color: #cbd5e1;">
                                    <i class="icon-search4"></i>
                                </div>
                                <div style="font-size: 15px; font-weight: 500; color: #64748b;">No customer accounts found</div>
                                <p style="font-size: 13px; margin-top: 4px;">Try adjusting your search criteria or filter options.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer / Toolbar -->
        <div class="sw-table-footer" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; padding: 14px 20px; border-top: 1px solid #f1f5f9;">
            <div>
                <?php if (!empty($customers)): ?>
                    <button type="submit" id="saveBulkBtn" class="sw-btn-primary">
                        <i class="icon-floppy-disk"></i>
                        <span>Save Configuration</span>
                    </button>
                    <span style="font-size: 12px; color: #64748b; margin-left: 12px;">
                        Quickly update all visible accounts on this page
                    </span>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <div id="pettyCashPaginationWrapper">
                <?= $pager->links() ?>
            </div>
        </div>
    </div>
</form>

<script>
var filterTimeout = null;
var baseUrl = "<?= base_url('master/petty-cash') ?>";

// AJAX Live Filter Function
function fetchFilteredData(url) {
    var q = $('#searchKeyword').val();
    var status = $('#statusSelect').val();

    if (!url) {
        url = baseUrl;
    }

    var $card = $('#pettyCashTableCard');
    $card.css({ 'opacity': '0.5', 'pointer-events': 'none' });

    $.ajax({
        url: url,
        type: 'GET',
        data: { q: q, status: status },
        dataType: 'html',
        success: function(resp) {
            var $newContent = $(resp).find('#pettyCashTableCard');
            if ($newContent.length) {
                $card.html($newContent.html());
            }
            $card.css({ 'opacity': '1', 'pointer-events': 'auto' });

            // Update browser URL query string smoothly
            var queryString = $.param({ q: q, status: status });
            var newUrl = baseUrl + (queryString ? '?' + queryString : '');
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', newUrl);
            }
        },
        error: function() {
            $card.css({ 'opacity': '1', 'pointer-events': 'auto' });
        }
    });
}

// Live search on input with 300ms debounce
$('#searchKeyword').on('input', function() {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(function() {
        fetchFilteredData();
    }, 300);
});

// Live filter on status select change
$('#statusSelect').on('change', function() {
    clearTimeout(filterTimeout);
    fetchFilteredData();
});

// Intercept pagination clicks for seamless AJAX paging
$(document).on('click', '#pettyCashPaginationWrapper a', function(e) {
    e.preventDefault();
    var href = $(this).attr('href');
    if (href) {
        fetchFilteredData(href);
    }
});

// Bulk AJAX submission
document.getElementById('pettyCashBulkForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var btn = document.getElementById('saveBulkBtn');
    if (!btn) return;

    var origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="icon-spinner2 spinner"></i> <span>Saving...</span>';

    var form = this;
    var formData = new FormData(form);

    $.ajax({
        url: form.action,
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'text',
        success: function(resp) {
            btn.disabled = false;
            btn.innerHTML = origHtml;

            if (resp.trim() === 'true') {
                swal({
                    title: "Updated Successfully!",
                    text: "Customer petty cash configurations have been saved.",
                    type: "success",
                    confirmButtonColor: "#2563eb",
                    confirmButtonText: "OK"
                }, function() {
                    fetchFilteredData();
                });
            } else {
                swal({
                    title: "Failed to Update",
                    text: "Could not save configurations. Please try again.",
                    type: "error",
                    confirmButtonColor: "#ef4444"
                });
            }
        },
        error: function() {
            btn.disabled = false;
            btn.innerHTML = origHtml;
            swal({
                title: "Server Error",
                text: "An error occurred while communicating with the server.",
                type: "error",
                confirmButtonColor: "#ef4444"
            });
        }
    });
});
</script>
<?= $this->endSection() ?>
