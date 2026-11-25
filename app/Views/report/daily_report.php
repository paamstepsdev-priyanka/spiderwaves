<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?><?= esc($breadcrumb) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Flash Message Notifications -->
<?php if (session()->getFlashdata('success')): ?>
    <div class="sw-alert sw-alert-success">
        <i class="icon-checkmark-circle"></i>
        <span><strong>Success!</strong> <?= esc(session()->getFlashdata('success')) ?></span>
        <button type="button" class="close" data-dismiss="alert" style="margin-left: auto; background: none; border: none; font-size: 16px; color: inherit; cursor: pointer; line-height: 1;">&times;</button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="sw-alert sw-alert-danger">
        <i class="icon-alert"></i>
        <span><strong>Notice:</strong> <?= esc(session()->getFlashdata('error')) ?></span>
        <button type="button" class="close" data-dismiss="alert" style="margin-left: auto; background: none; border: none; font-size: 16px; color: inherit; cursor: pointer; line-height: 1;">&times;</button>
    </div>
<?php endif; ?>

<!-- CI2-Style Breadcrumb Header Bar -->
<div class="breadcrumb-line" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px 16px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
    <ul class="breadcrumb" style="margin: 0; padding: 0; background: transparent; display: inline-flex; align-items: center; list-style: none; font-size: 13px;">
        <li>
            <a href="<?= base_url('dashboard'); ?>" style="color: #334155; text-decoration: none; font-weight: 500;">
                <i class="icon-home2 position-left" style="margin-right: 6px; font-size: 13px;"></i> Home
            </a>
        </li>       
        <li class="active" style="color: #64748b; font-weight: 500;">Daily Report</li>
    </ul>            
    <div class="text-danger" style="margin: 0; font-size: 13px;">
        <a href="<?= in_array($accessLevel, ['admin', 'superadmin', 'process_manager']) ? base_url('raw-waves/manage') : base_url('raw-waves'); ?>" class="text-danger" style="color: #ff3636; text-decoration: none;">
            Pending : <strong><?= get_number_of_pending_raw_waves(); ?></strong>
        </a>
    </div>            
</div>

<!-- Full-Width Daily Report Table Panel -->
<div class="panel panel-flat" style="width: 100%; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px; padding: 18px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    <div class="table-responsive" style="width: 100%; overflow-x: auto;">
        <table class="table table-bordered table-striped table-togglable table-hover" style="width: 100%; margin-bottom: 0; border: 1px solid #ddd;">
            <thead>
                <tr>
                    <th colspan="<?= ($accessLevel === 'admin') ? '7' : '6'; ?>" class="text-center green-bg" style="background-color: #16a34a !important; color: #ffffff; font-weight: 700; font-size: 14px; text-align: center; padding: 9px; border: 1px solid #ddd; letter-spacing: 1px;">R</th>
                    <th colspan="<?= ($accessLevel === 'admin') ? '7' : '6'; ?>" class="text-center red-bg" style="background-color: #dc2626 !important; color: #ffffff; font-weight: 700; font-size: 14px; text-align: center; padding: 9px; border: 1px solid #ddd; letter-spacing: 1px;">I</th>
                </tr>
                <tr style="font-weight: 600; background-color: #f8fafc; font-size: 12px; color: #334155;">                                    
                    <td style="border: 1px solid #ddd; padding: 7px 10px; font-weight: 600;">NW</td>
                    <td style="border: 1px solid #ddd; padding: 7px 10px; font-weight: 600;">FW</td>
                    <td style="border: 1px solid #ddd; padding: 7px 10px; font-weight: 600;">WSTG</td>
                    <td style="border: 1px solid #ddd; padding: 7px 10px; font-weight: 600;">TN</td>
                    <td style="border: 1px solid #ddd; padding: 7px 10px; font-weight: 600;">CUST</td>
                    <td style="border: 1px solid #ddd; padding: 7px 10px; font-weight: 600;">REM</td>
                    <?php if ($accessLevel === 'admin'): ?>
                    <td style="border: 1px solid #ddd; padding: 7px 10px; font-weight: 600;">&nbsp;</td>
                    <?php endif; ?>
                    <td style="border: 1px solid #ddd; padding: 7px 10px; font-weight: 600;">NW</td>
                    <td style="border: 1px solid #ddd; padding: 7px 10px; font-weight: 600;">FW</td>
                    <td style="border: 1px solid #ddd; padding: 7px 10px; font-weight: 600;">WSTG</td>
                    <td style="border: 1px solid #ddd; padding: 7px 10px; font-weight: 600;">TN</td>
                    <td style="border: 1px solid #ddd; padding: 7px 10px; font-weight: 600;">CUST</td>
                    <td style="border: 1px solid #ddd; padding: 7px 10px; font-weight: 600;">REM</td>
                    <?php if ($accessLevel === 'admin'): ?>
                    <td style="border: 1px solid #ddd; padding: 7px 10px; font-weight: 600;">&nbsp;</td>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($or_data)): ?>
                    <?php foreach ($or_data as $orobj): ?>
                        <?php if (!empty($orobj->receipt_gross_weight) || !empty($orobj->issue_gross_weight)): ?>
                        <tr style="font-size: 13px;">                                    
                            <td class="text-right" style="border: 1px solid #ddd; padding: 7px 10px; font-family: monospace; text-align: right;"><?= view_round_up($orobj->receipt_net_weight); ?></td>
                            <td class="text-right" style="border: 1px solid #ddd; padding: 7px 10px; font-family: monospace; text-align: right;"><?= view_round_up((float)$orobj->receipt_net_weight * (float)$orobj->receipt_purity / 100); ?></td>
                            <td class="text-right" style="border: 1px solid #ddd; padding: 7px 10px; font-family: monospace; text-align: right;"><?= view_round_up($orobj->receipt_wastage); ?></td>
                            <td class="text-right" style="border: 1px solid #ddd; padding: 7px 10px; font-family: monospace; text-align: right;"><?= view_round_up($orobj->receipt_purity); ?></td>
                            <td style="border: 1px solid #ddd; padding: 7px 10px;"><?= esc($orobj->receipt_cust_name); ?></td>
                            <td style="border: 1px solid #ddd; padding: 7px 10px;"><?= esc($orobj->receipt_remark); ?></td>
                            <?php if ($accessLevel === 'admin'): ?>
                            <td style="border: 1px solid #ddd; padding: 7px 10px; text-align: center;">
                                <?php if (!empty($orobj->receipt_private_key)): ?>
                                <a href="<?= base_url('waves/edit_in_out/' . $orobj->receipt_private_key); ?>" style="color: #2563eb;" title="Edit Receipt">
                                    <i class="icon-pencil"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>

                            <td class="text-right" style="border: 1px solid #ddd; padding: 7px 10px; font-family: monospace; text-align: right;"><?= view_round_up($orobj->issue_net_weight); ?></td>
                            <td class="text-right" style="border: 1px solid #ddd; padding: 7px 10px; font-family: monospace; text-align: right;"><?= view_round_up((float)$orobj->issue_net_weight * (float)$orobj->issue_purity / 100); ?></td>
                            <td class="text-right" style="border: 1px solid #ddd; padding: 7px 10px; font-family: monospace; text-align: right;"><?= view_round_up($orobj->issue_wastage); ?></td>
                            <td class="text-right" style="border: 1px solid #ddd; padding: 7px 10px; font-family: monospace; text-align: right;"><?= view_round_up($orobj->issue_purity); ?></td>
                            <td style="border: 1px solid #ddd; padding: 7px 10px;"><?= esc($orobj->issue_cust_name); ?></td>
                            <td style="border: 1px solid #ddd; padding: 7px 10px;"><?= esc($orobj->issue_remark); ?></td>
                            <?php if ($accessLevel === 'admin'): ?>
                            <td style="border: 1px solid #ddd; padding: 7px 10px; text-align: center;">
                                <?php if (!empty($orobj->issue_private_key)): ?>
                                <a href="<?= base_url('waves/edit_in_out/' . $orobj->issue_private_key); ?>" style="color: #2563eb;" title="Edit Issue">
                                    <i class="icon-pencil"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= ($accessLevel === 'admin') ? '14' : '12'; ?>" class="text-center text-muted" style="padding: 24px; border: 1px solid #ddd; text-align: center; color: #94a3b8;">
                            No unapproved daily transactions found.
                        </td>
                    </tr>
                <?php endif; ?>

                <tr style="font-size: 13px;">
                    <td colspan="<?= ($accessLevel === 'admin') ? '12' : '10'; ?>" class="text-right" style="font-weight: 600; padding: 9px 12px; border: 1px solid #ddd; text-align: right;">Balance</td>
                    <td colspan="2" class="text-right" style="font-weight: 600; padding: 9px 12px; border: 1px solid #ddd; font-family: monospace; text-align: right;"><?= view_round_up($or_balance); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <br><hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 18px 0;">
    
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <?php if (can('daily_report.print')): ?>
            <a href="#" target="popup" onclick="window.open('<?= base_url('report/print_daily_report'); ?>','popup','width=1000,height=800'); return false;" class="btn btn-primary btn-xs" style="padding: 6px 16px; font-size: 12.5px; font-weight: 600; border-radius: 3px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.06);">
                Print
            </a>
            <?php endif; ?>
        </div>
        
        <div>
            <?php if (can('daily_report.approve')): ?>
            <form id="export_daily_report_form" action="<?= base_url('report/export_daily_report'); ?>" method="post" novalidate style="margin: 0;">
                <?= csrf_field() ?>
                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 14px;">
                    <label style="margin: 0; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; color: #1e293b;">
                        <input type="checkbox" name="approved" id="approved" value="approved" style="width: 17px; height: 17px; cursor: pointer; accent-color: #2563eb;" required>
                        <strong style="font-weight: 600;">Approved</strong>
                    </label>
                    <button type="submit" class="btn btn-primary btn-xs" style="padding: 6px 16px; font-size: 12.5px; font-weight: 600; border-radius: 3px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.06);">
                        Approve and Submit
                    </button>
                </div>
            </form>
            <?php endif; ?>                                
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($.fn.validate) {
        $("#export_daily_report_form").validate({
            rules: {
                approved: "required"
            },
            messages: {
                approved: "Please check Approved before submitting."
            }
        });
    }
});
</script>

<?= $this->endSection() ?>
