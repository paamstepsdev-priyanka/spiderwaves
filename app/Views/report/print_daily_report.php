<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Report Print - SpiderWaves</title>
    <style>
    table {
      font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
      border-collapse: collapse;
      width: 100%;
      font-size : 12px;
    }

    table td, table th {
      border: 1px solid #ddd;
      padding: 8px;
    }

    table tr:nth-child(even){background-color: #f2f2f2;}

    table tr:hover {background-color: #ddd;}

    table th {
      padding-top: 12px;
      padding-bottom: 12px;
      text-align: left;
      background-color: #eeeeee;
      color: black;
    }

    .text-right {
      text-align: right;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 0 !important;
        }
        body {
            margin: 0 !important;
            padding: 10mm 10mm !important;
            box-sizing: border-box !important;
        }
    }
    </style>
</head>
<body>
<table style="border: solid 1px black;">
    <thead>
    	<tr>
            <td colspan="10" style="text-align: center; font-weight: bold; font-size: 14px;"><?= date("d-m-Y"); ?></td>
        </tr>
        <tr>
            <th colspan="5" style="text-align: center; border-right: solid 2px black;">Receipt</th>
            <th colspan="5" style="text-align: center;">Issue</th>
        </tr>
    </thead>
    <tbody>
        <tr style="font-weight: 500;">                                    
            <td>NW</td>
            <td>WSTG</td>
            <td>TN</td>
            <td>CUST</td>
            <td style="border-right: solid 2px black;">REM</td>
            
            <td>NW</td>
            <td>WSTG</td>
            <td>TN</td>
            <td>CUST</td>
            <td>REM</td>
        </tr>
        <?php
        $total_receipt = 0;
        $total_issue = 0;
        if (!empty($or_data)) {
            foreach ($or_data as $orobj) {
                if (!empty($orobj->receipt_gross_weight) || !empty($orobj->issue_gross_weight)) {
                    $total_receipt += ((float)$orobj->receipt_net_weight * (float)$orobj->receipt_purity / 100);
                    $total_issue   += ((float)$orobj->issue_net_weight * (float)$orobj->issue_purity / 100);
        ?>
        <tr>                                    
            <td class="text-right"><?= view_round_up($orobj->receipt_net_weight); ?></td>
            <td class="text-right"><?= view_round_up($orobj->receipt_wastage); ?></td>
            <td class="text-right"><?= view_round_up($orobj->receipt_purity); ?></td>
            <td><?= esc($orobj->receipt_cust_name); ?></td>
            <td style="border-right: solid 2px black;"><?= esc($orobj->receipt_remark); ?></td>
            
            <td class="text-right"><?= view_round_up($orobj->issue_net_weight); ?></td>
            <td class="text-right"><?= view_round_up($orobj->issue_wastage); ?></td>
            <td class="text-right"><?= view_round_up($orobj->issue_purity); ?></td>
            <td><?= esc($orobj->issue_cust_name); ?></td>
            <td><?= esc($orobj->issue_remark); ?></td>
        </tr>
        <?php
                }
            }
        }
        ?>
        <tr>
            <td colspan="3" style="text-align: right; font-weight: bold;">Total Reciept</td>
            <td colspan="2" style="text-align: left; border-right: solid 2px black; font-weight: bold;"><?= view_round_up($total_receipt); ?></td>
            <td colspan="3" style="text-align: right; font-weight: bold;">Total Issue</td>
            <td colspan="2" style="text-align: left; font-weight: bold;"><?= view_round_up($total_issue); ?></td>
        </tr>
        
        <tr>
            <td colspan="8" style="text-align: right; font-weight: bold;">Balance</td>
            <td colspan="2" style="text-align: left; font-weight: bold;"><?= view_round_up($total_receipt - $total_issue); ?></td>
        </tr>
    </tbody>
</table>
                    
<script type="text/javascript">
window.print();
</script>
</body>
</html>
