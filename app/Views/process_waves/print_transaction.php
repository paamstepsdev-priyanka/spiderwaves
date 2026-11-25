<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transaction Voucher - <?= esc($tx['process_waves_id']) ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
    <style>
        #Transaction {
            font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
            font-size: 11px;
        }
        #Transaction td, #Transaction th {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: center;
        }
        #Transaction th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        @media print {
            @page {
                margin: 0 !important;
            }
            body { margin: 0; padding: 5px; }
        }
    </style>
</head>
<body>
    <table id="Transaction">
        <tr>
            <th colspan="4"><?= esc($customerName) ?></th>
        </tr>
        <tr>
            <th colspan="4"><?= esc($detail) ?></th>
        </tr>
        <tr>
            <td><strong>Gwt</strong></td>
            <td><strong><?= esc($grossWeight) ?>g</strong></td>
            <td rowspan="3" style="width: 70px;">
                <div id="qrcode" style="display: flex; justify-content: center;"></div>
            </td>
        </tr>
        <tr>
            <td><strong>Pt</strong></td>
            <td><?= number_format((float)$purity, 2, '.', '') ?>%</td>
        </tr>
        <tr>
            <td colspan="2"><?= date('d-m-Y H:i:s', strtotime($dateTime)) ?></td>
        </tr>
    </table>

    <script type="text/javascript">
    $(document).ready(function() {
        new QRCode(document.getElementById("qrcode"), {
            text: "<?= esc($customerName) ?> | <?= esc($grossWeight) ?>g | <?= esc($detail) ?>",
            width: 64,
            height: 64,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        setTimeout(function() {
            window.print();
        }, 300);
    });
    </script>
</body>
</html>
