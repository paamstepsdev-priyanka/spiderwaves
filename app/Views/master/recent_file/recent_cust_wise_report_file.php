<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Recent Cust Wise Report File - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Master / Recent File / Recent Cust Ledger<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="panel panel-flat">
    <div class="panel-heading panel-body">
        <h5 class="panel-title" style="margin: 0; font-weight: 700; color: #0f172a;">Recent Cust Wise Report File</h5>
    </div>
    <table class="table table-togglable table-hover">
        <thead>
            <tr class="bg-blue">
                <th style="width: 1%;">S.No.</th>
                <th>Cust Name</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($recent_cust_data)): ?>
                <?php $index = 1; foreach ($recent_cust_data as $obj): ?>
                <tr id="recent_cust_file_<?= esc($obj->cust_id) ?>">
                    <td><?= $index++ ?></td>
                    <td style="font-weight: 600;">
                        <a href="<?= base_url('master/download_recent_cust_ledger_file?cust_id=' . esc($obj->cust_id)) ?>" style="color: #0f172a; text-decoration: none;" title="Download <?= esc($obj->cust_name) ?>">
                            <?= esc($obj->cust_name) ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2" class="text-center" style="padding: 30px; color: #64748b;">
                        <p style="margin: 0;">There is no result for you.</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function delete_cust_recent_file(custId) {
    var performDelete = function() {
        $.post('<?= base_url('master/delete_recent_cust_ledger_file') ?>', { cust_id: custId }, function(res) {
            if (res.trim() === 'true') {
                $('#recent_cust_file_' + custId).fadeOut();
            } else {
                if (typeof swal !== 'undefined') {
                    swal('Error', 'Failed to delete file.', 'error');
                } else {
                    alert('Failed to delete file.');
                }
            }
        });
    };

    if (typeof swal !== 'undefined') {
        swal({
            title: "Delete File?",
            text: "Are you sure you want to delete this file?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#EF5350",
            confirmButtonText: "Yes, Delete",
            cancelButtonText: "Cancel"
        }, function(isConfirm) {
            if (isConfirm) {
                performDelete();
            }
        });
    } else {
        if (confirm('Are you sure you want to delete this file?')) {
            performDelete();
        }
    }
}
</script>
<?= $this->endSection() ?>

