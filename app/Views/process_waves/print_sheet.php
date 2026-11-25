<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= esc($account['account_name']) ?> - Department Ledger</title>
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
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print();" style="padding: 6px 12px; cursor: pointer;">Print Ledger</button>
    </div>

    <div class="text-center" style="margin-bottom: 20px;">
        <h2>SPIDERWAVES MANUFACTURING WORKSHOP</h2>
        <h4>Department Ledger: <?= esc($account['account_name']) ?></h4>
        <p>Period: From <?= date('d/m/Y H:i:s', strtotime($startDate)) ?> to <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <div class="row">
        <!-- Receipt Column -->
        <div class="col-6">
            <h4>Receipt Stock</h4>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>From</th>
                        <th class="text-right">Weight</th>
                        <th class="text-right">P(%)</th>
                        <th class="text-center">Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($openingBalance != 0): ?>
                    <tr>
                        <td><strong>Opening MT</strong></td>
                        <td colspan="4" class="text-right"><strong><?= number_format(abs((float)$openingBalance), 3) ?></strong></td>
                    </tr>
                    <?php endif; ?>

                    <?php if (!empty($receiptData)): ?>
                        <?php foreach ($receiptData as $r): ?>
                        <tr>
                            <td><?= esc($r['mapped_cust_name'] ?: ($r['cust_name'] ?: 'N/A')) ?></td>
                            <td><?= esc($r['from_name'] ?: 'N/A') ?></td>
                            <td class="text-right"><?= number_format((float)$r['gross_weight'], 3) ?></td>
                            <td class="text-right"><?= number_format((float)$r['purity'], 2, '.', '') ?></td>
                            <td class="text-center"><?= date('d/m/Y H:i', strtotime($r['date'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No receipts recorded.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Fine MT</th>
                        <th colspan="3" class="text-left"><?= number_format($receiveTotalBalance, 3) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Issue Column -->
        <div class="col-6">
            <h4>Issue Stock</h4>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="text-right">Weight</th>
                        <th class="text-right">P(%)</th>
                        <th class="text-center">Time</th>
                        <th>To</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($issueData)): ?>
                        <?php foreach ($issueData as $i): ?>
                        <tr>
                            <td><?= esc($i['mapped_cust_name'] ?: ($i['cust_name'] ?: 'N/A')) ?></td>
                            <td class="text-right"><?= number_format((float)$i['gross_weight'], 3) ?></td>
                            <td class="text-right"><?= number_format((float)$i['purity'], 2, '.', '') ?></td>
                            <td class="text-center"><?= date('d/m/Y H:i', strtotime($i['date'])) ?></td>
                            <td><?= esc($i['to_name'] ?: 'cust') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No issues recorded.</td></tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2">Fine MT</th>
                        <th colspan="3" class="text-left"><?= number_format($issueTotalBalance, 3) ?></th>
                    </tr>
                    <tr>
                        <th colspan="2">Fine MT Balance</th>
                        <th colspan="3" class="text-left"><?= number_format(abs((float)$fineBalance), 3) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</body>
</html>
