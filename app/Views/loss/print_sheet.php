<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= esc($lossTitle) ?> - Department Ledger</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 15px;
        }
        h2, h4 { margin: 5px 0; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 6px;
        }
        .table th {
            background-color: #f5f5f5;
        }
        .row {
            display: flex;
            gap: 15px;
        }
        .col-6 {
            flex: 1;
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 0 !important;
            }
            body {
                margin: 0 !important;
                padding: 10mm 15mm !important;
                box-sizing: border-box !important;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
        <button onclick="window.print();" style="padding: 6px 14px; font-weight: bold; cursor: pointer;">Print</button>
        <button onclick="window.close();" style="padding: 6px 14px; cursor: pointer;">Close</button>
    </div>

    <div class="text-center" style="margin-bottom: 20px;">
        <h2>SPIDERWAVES MANUFACTURING WORKSHOP</h2>
        <h4>Department Ledger: <?= esc($lossTitle) ?></h4>
        <p>Printed on: <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <div class="row">
        <!-- Receipt Column -->
        <div class="col-6">
            <h4>Receipt Stock (<?= count($receiptData) ?>)</h4>
            <table class="table">
                <thead>
                    <tr>
                        <th>From</th>
                        <th class="text-right">Weight</th>
                        <th class="text-right">P(%)</th>
                        <th class="text-center">Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($receiptData)): ?>
                        <?php foreach ($receiptData as $r): ?>
                        <?php 
                            $gross = abs((float)$r['gross_weight']);
                            $purity = (float)$r['purity'];
                            $fromDept = !empty($r['from_name']) ? $r['from_name'] : $accountName;
                        ?>
                        <tr>
                            <td><?= esc($fromDept) ?> #<?= esc($r['process_waves_id']) ?></td>
                            <td class="text-right"><?= number_format($gross, 3, '.', '') ?></td>
                            <td class="text-right"><?= number_format($purity, 2, '.', '') ?></td>
                            <td class="text-center"><?= date('d/m/Y H:i', strtotime($r['date'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No receipts recorded.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total Gross</th>
                        <th class="text-right"><?= number_format($currentPendingGross, 3, '.', '') ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Issue Column -->
        <div class="col-6">
            <h4>Issue Stock (<?= count($issueData) ?>)</h4>
            <table class="table">
                <thead>
                    <tr>
                        <th class="text-right">Weight</th>
                        <th class="text-right">P(%)</th>
                        <th class="text-center">Time</th>
                        <th>To</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($issueData)): ?>
                        <?php foreach ($issueData as $i): ?>
                        <?php 
                            $gross = abs((float)$i['gross_weight']);
                            $purity = (float)$i['purity'];
                        ?>
                        <tr>
                            <td class="text-right"><?= number_format($gross, 3, '.', '') ?></td>
                            <td class="text-right"><?= number_format($purity, 2, '.', '') ?></td>
                            <td class="text-center"><?= date('d/m/Y H:i', strtotime($i['date'])) ?></td>
                            <td><?= esc($i['to_name'] ?: 'Refining') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No issues recorded.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th class="text-right"><?= number_format($currentIssueGross, 3, '.', '') ?></th>
                        <th colspan="3">Total Issued</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <script type="text/javascript">
        window.print();
    </script>
</body>
</html>
