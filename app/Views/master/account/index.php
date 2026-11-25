<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Account - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Account<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Page Header -->
<div class="sw-page-header">
    <div class="sw-page-title-box">
        <h1>Account</h1>
        <p>Manage workshop departments, stock vaults, loss stages, and department transfer route mappings.</p>
    </div>
    <div class="sw-page-actions">
        <a href="<?= base_url('master/account/create') ?>" class="sw-btn-primary" id="btnOpenCreateProcess">
            <i class="icon-plus2"></i>
            <span>Create New Process</span>
        </a>
    </div>
</div>

<!-- Flash Alerts -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-styled-left alert-arrow-left alert-bordered" style="margin-bottom: 20px;">
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span><span class="sr-only">Close</span></button>
        <span class="text-semibold">Success!</span> <?= esc(session()->getFlashdata('success')) ?>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-styled-left alert-arrow-left alert-bordered" style="margin-bottom: 20px;">
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span><span class="sr-only">Close</span></button>
        <span class="text-semibold">Error!</span> <?= esc(session()->getFlashdata('error')) ?>
    </div>
<?php endif; ?>

<!-- Search Filter Toolbar -->
<div class="sw-filter-card" style="margin-bottom: 20px;">
    <form method="get" action="<?= base_url('master/account') ?>" id="accountFilterForm">
        <div class="sw-filter-grid" style="gap: 12px;">
            <div class="sw-search-group" style="min-width: 220px;">
                <input type="text"
                       name="search"
                       class="sw-input"
                       placeholder="Search by account name or type..."
                       value="<?= esc($search ?? '') ?>"
                       autocomplete="off">
            </div>

            <button type="submit" class="sw-btn-secondary" style="height: 40px; padding: 0 18px; justify-content: center; flex-shrink: 0;">
                <i class="icon-filter4"></i>
                <span>Filter</span>
            </button>

            <?php if (!empty($search)): ?>
                <a href="<?= base_url('master/account') ?>" class="sw-btn-secondary" title="Reset Search" style="height: 40px; padding: 0 14px; justify-content: center; flex-shrink: 0; color: #64748b;">
                    <i class="icon-cross2"></i>
                    <span>Reset</span>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Accounts Data Table Card -->
<div class="sw-table-card" id="accountTableCard">
    <div class="sw-table-header" style="height: auto; min-height: 52px; padding: 12px 20px; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="display: inline-block; width: 10px; height: 10px; background-color: #2563eb; border-radius: 50%; flex-shrink: 0;"></span>
            <h3 class="sw-table-title" style="margin: 0; font-size: 15px;">Registered Accounts &amp; Departments</h3>
        </div>
        <span class="sw-table-info" style="white-space: nowrap;">
            <?= count($accounts ?? []) ?> shown (<?= (int)$total ?> total accounts)
        </span>
    </div>

    <div class="sw-table-responsive" id="accountTableResponsive">
        <table class="sw-table" id="accountManagementTable">
            <thead>
                <tr>
                    <th class="text-center text-nowrap" style="width: 6%; padding: 11px 16px;">S.No.</th>
                    <th class="text-left" style="width: 32%; padding: 11px 16px;">Account / Department</th>
                    <th class="text-left text-nowrap" style="width: 18%; padding: 11px 16px;">Type</th>
                    <th class="text-center text-nowrap" style="width: 20%; padding: 11px 16px;">Mapped Departments</th>
                    <th class="text-center text-nowrap" style="width: 12%; padding: 11px 16px;">Status</th>
                    <th class="text-center text-nowrap" style="width: 12%; padding: 11px 16px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($accounts)): ?>
                    <?php 
                        $sno = 1;
                        if (isset($pager)) {
                            $currentPage = (int) ($pager->getCurrentPage() ?? 1);
                            $perPage = (int) ($pager->getPerPage() ?? 25);
                            $sno = (($currentPage - 1) * $perPage) + 1;
                        }
                    ?>
                    <?php foreach ($accounts as $row): ?>
                        <tr>
                            <td class="text-center text-nowrap" style="width: 6%; padding: 10px 16px; color: #64748b; font-weight: 500; vertical-align: middle;">
                                <?= $sno++ ?>
                            </td>
                            <td class="text-left" style="width: 32%; padding: 10px 16px; vertical-align: middle;">
                                <div class="sw-cust-name" style="line-height: 1.3;">
                                    <?= esc($row['account_name']) ?>
                                </div>
                            </td>
                            <td class="text-left text-nowrap" style="width: 18%; padding: 10px 16px; vertical-align: middle;">
                                <?php
                                    $typeColor = '#2563eb'; // Process (primary blue)
                                    $typeLower = strtolower(trim($row['account_type'] ?? ''));
                                    if ($typeLower === 'stock') {
                                        $typeColor = '#16a34a'; // Stock (emerald green)
                                    } elseif ($typeLower === 'loss') {
                                        $typeColor = '#dc2626'; // Loss (crimson red)
                                    }
                                ?>
                                <span style="color: <?= $typeColor ?>; font-weight: 600; font-size: 13px; text-transform: capitalize; letter-spacing: 0.2px; line-height: 1;">
                                    <?= esc($row['account_type']) ?>
                                </span>
                            </td>
                            <td class="text-center text-nowrap" style="width: 20%; padding: 10px 16px; vertical-align: middle;">
                                <?php $mappedCount = count($row['mapped_names'] ?? []); ?>
                                <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 90px; height: 23px; padding: 0 12px; font-size: 11px; font-weight: 700; border-radius: 9999px; background-color: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; letter-spacing: 0.3px; line-height: 1; white-space: nowrap; user-select: none;">
                                    <?= $mappedCount ?> MAPPED
                                </span>
                            </td>
                            <td class="text-center text-nowrap" style="width: 12%; padding: 10px 16px; vertical-align: middle;">
                                <?php if ((string)$row['account_status'] === '1'): ?>
                                    <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 72px; height: 23px; padding: 0 12px; font-size: 11px; font-weight: 700; border-radius: 9999px; background-color: #16a34a; color: #ffffff; text-transform: uppercase; letter-spacing: 0.3px; line-height: 1; white-space: nowrap;">ACTIVE</span>
                                <?php else: ?>
                                    <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 72px; height: 23px; padding: 0 12px; font-size: 11px; font-weight: 700; border-radius: 9999px; background-color: #dc2626; color: #ffffff; text-transform: uppercase; letter-spacing: 0.3px; line-height: 1; white-space: nowrap;">INACTIVE</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-nowrap" style="width: 12%; padding: 10px 16px; vertical-align: middle;">
                                <?php if (can('account.manage')): ?>
                                 <div class="btn-group" style="display: inline-flex; justify-content: center;">
                                    <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-width: 84px; height: 28px; padding: 0 10px; font-size: 12px; font-weight: 600; line-height: 1; border-radius: 4px;">
                                        Action <span class="caret" style="margin-left: 0;"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" style="font-size: 12.5px;">
                                        <li>
                                            <a href="<?= base_url('master/account/edit/' . $row['private_key']) ?>">
                                                <i class="icon-pencil7 text-primary"></i> Edit Process
                                            </a>
                                        </li>
                                        <li role="separator" class="divider" style="margin: 4px 0;"></li>
                                        <li>
                                            <a href="javascript:void(0);" 
                                               class="btn-toggle-account-status"
                                               data-url="<?= base_url('account/toggle/' . $row['private_key']) ?>"
                                               data-name="<?= esc($row['account_name']) ?>"
                                               data-action="<?= ($row['account_status'] === '1') ? 'Deactivate' : 'Activate' ?>">
                                                <i class="icon-switch text-muted"></i> <?= ($row['account_status'] === '1') ? 'Deactivate' : 'Activate' ?>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 11px;">View Only</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 40px 20px; color: #94a3b8;">
                            <i class="icon-inbox" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
                            No accounts found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <?php if (isset($pager) && $pager->getPageCount() > 1): ?>
        <div class="sw-table-footer">
            <?= $pager->links() ?>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    var baseUrl = '<?= base_url() ?>';

    // Prevent dropdown clipping inside responsive/rounded table container
    $(document).on('show.bs.dropdown', '#accountManagementTable .btn-group', function() {
        var $group = $(this);
        var $card = $('#accountTableCard');
        var $resp = $('#accountTableResponsive');
        var respEl = $resp[0];
        var hasHorizontalScroll = respEl && (respEl.scrollWidth > respEl.clientWidth);

        if (!hasHorizontalScroll) {
            $card.css('overflow', 'visible');
            $resp.css('overflow', 'visible');
        }

        var rect = this.getBoundingClientRect();
        var spaceBelow = window.innerHeight - rect.bottom;
        if (spaceBelow < 140 && rect.top > 140) {
            $group.addClass('dropup');
        } else {
            $group.removeClass('dropup');
        }
    });

    $(document).on('hidden.bs.dropdown', '#accountManagementTable .btn-group', function() {
        $(this).removeClass('dropup');
        $('#accountTableCard').css('overflow', '');
        $('#accountTableResponsive').css('overflow', '');
    });

    // -------------------------------------------------------------
    // DELETE PROCESS WITH DEPENDENCY VALIDATION
    // -------------------------------------------------------------
    $(document).on('click', '.btn-delete-process', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        var id = $(this).data('id');
        var name = $(this).data('name');

        if (id <= 23) {
            if (typeof swal !== 'undefined') {
                swal({
                    title: "Cannot Delete Core System Process",
                    text: "'" + name + "' is a core system process (ID: " + id + ") and cannot be deleted. You can deactivate it instead.",
                    type: "warning",
                    confirmButtonColor: "#2563eb"
                });
            } else {
                alert("Core system process '" + name + "' cannot be deleted.");
            }
            return false;
        }

        if (typeof swal !== 'undefined') {
            swal({
                title: "Delete " + name + "?",
                text: "Are you sure you want to delete this process? The system will verify there are no active transactions or closing balances linked to it.",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#EF5350",
                confirmButtonText: "Yes, Delete It",
                cancelButtonText: "Cancel",
                closeOnConfirm: false
            }, function(isConfirm) {
                if (isConfirm) {
                    swal({
                        title: "Verifying Dependencies...",
                        text: "Checking transaction records...",
                        showConfirmButton: false
                    });

                    $.ajax({
                        url: url,
                        type: 'POST',
                        dataType: 'json',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success: function(resp) {
                            if (resp && resp.status) {
                                swal({
                                    title: "Deleted!",
                                    text: resp.message || "Process deleted successfully.",
                                    type: "success",
                                    confirmButtonColor: "#2563eb"
                                }, function() {
                                    window.location.reload();
                                });
                            } else {
                                swal({
                                    title: "Cannot Delete Process",
                                    text: resp.error || "Cannot delete process due to existing dependencies.",
                                    type: "error",
                                    confirmButtonColor: "#2563eb"
                                });
                            }
                        },
                        error: function(xhr) {
                            var errMsg = "Error occurred while deleting process.";
                            if (xhr.responseJSON && xhr.responseJSON.error) {
                                errMsg = xhr.responseJSON.error;
                            }
                            swal({
                                title: "Cannot Delete Process",
                                text: errMsg,
                                type: "error",
                                confirmButtonColor: "#2563eb"
                            });
                        }
                    });
                }
            });
        }
    });

    // -------------------------------------------------------------
    // STATUS TOGGLE (ACTIVATE / DEACTIVATE)
    // -------------------------------------------------------------
    $(document).on('click', '.btn-toggle-account-status', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        var name = $(this).data('name');
        var action = $(this).data('action');
        var isDeactivate = (action === 'Deactivate');

        var title = isDeactivate ? 'Deactivate ' + name + '?' : 'Activate ' + name + '?';
        var text = isDeactivate 
            ? 'Are you sure you want to deactivate ' + name + '? It will no longer be available for transfers or appear in process menus.' 
            : 'Are you sure you want to activate ' + name + '?';
        var confirmText = isDeactivate ? 'Yes, Deactivate' : 'Yes, Activate';
        var confirmColor = isDeactivate ? '#EF5350' : '#2563eb';

        if (typeof swal !== 'undefined') {
            swal({
                title: title,
                text: text,
                type: isDeactivate ? "warning" : "info",
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                confirmButtonText: confirmText,
                cancelButtonText: "Cancel",
                closeOnConfirm: false
            }, function(isConfirm) {
                if (isConfirm) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        dataType: 'json',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        success: function(resp) {
                            if (resp && resp.status) {
                                swal({
                                    title: "Status Updated!",
                                    text: resp.message || "Status updated successfully.",
                                    type: "success",
                                    confirmButtonColor: "#2563eb"
                                }, function() {
                                    window.location.reload();
                                });
                            } else {
                                window.location.href = url;
                            }
                        },
                        error: function() {
                            window.location.href = url;
                        }
                    });
                }
            });
        } else {
            window.location.href = url;
        }
    });
});
</script>

<?= $this->endSection() ?>

