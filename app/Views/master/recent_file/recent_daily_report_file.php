<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Recent Daily Report File - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Master / Recent File / Recent Daily Report<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="panel panel-flat">
    <div class="panel-heading panel-body">
        <h5 class="panel-title" style="margin: 0; font-weight: 700; color: #0f172a;">Recent Daily Report File</h5>
    </div>
    <table class="table table-togglable table-hover">
        <thead>
            <tr class="bg-blue">
                <th style="width: 1%;">S.No.</th>
                <th>File Name</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($recent_daily_file_data)): ?>
                <?php $index = 1; foreach ($recent_daily_file_data as $obj): 
                    $name = str_replace('_', ' ', substr($obj, 0, 12));
                    $date = str_replace('_', '/', substr($obj, 13, 10));
                    $time = str_replace('_', ':', substr($obj, 24, 8));
                    $time_pre_fix = substr($obj, 33, 2);
                    $fileNameFormatted = ucwords($name . ' ' . $date . ' ' . $time . ' ' . $time_pre_fix);
                ?>
                <tr id="row_<?= md5($obj) ?>">
                    <td><?= $index++ ?></td>
                    <td style="font-weight: 600;">
                        <a href="<?= base_url('master/download_recent_daily_report_file/' . esc($obj)) ?>" style="color: #0f172a; text-decoration: none;" title="Download <?= esc($fileNameFormatted) ?>">
                            <?= esc($fileNameFormatted) ?>
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
function delete_daily_recent_file(fileName, rowId) {
    var performDelete = function() {
        $.post('<?= base_url('master/delete_recent_daily_report_file') ?>/' + encodeURIComponent(fileName), {}, function(res) {
            if (res.trim() === 'true') {
                $('#row_' + rowId).fadeOut();
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

