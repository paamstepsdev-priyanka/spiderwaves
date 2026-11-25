<?php
/**
 * Raw Waves Voucher Slip
 * Renders dual identical receipts side-by-side for print,
 * or single receipt when embedded in verification modal.
 */
$voucher = is_array($voucher_data) ? (object)$voucher_data : $voucher_data;
$custName = $customer_name ?? ($voucher->cust_name ?? ($voucher->customer_name ?? ''));
$collName = $collection_name ?? ($voucher->collection_name ?? '');
$transType = strtoupper($voucher->transactional_type ?? 'RECEIPT');
$pw = number_format((float)($packet_weight ?? ($voucher->packet_weight ?? 0)), 3, '.', '');
$nw = number_format((float)($net_weight ?? ($voucher->net_weight ?? 0)), 3, '.', '');
$gw = number_format((float)($gross_weight ?? ($voucher->gross_weight ?? 0)), 3, '.', '');
$purityVal = number_format((float)($purity ?? ($voucher->purity ?? 0)), 2, '.', '');
$vCode = $verification_code ?? ($voucher->verification_code ?? '');
$delBy = $delivery_by ?? ($voucher->delivery_by ?? '');
$rawDate = $voucher->date ?? date('Y-m-d');
$rawTime = $voucher->time ?? date('h:i:s A');
$formattedDate = date('d/m/Y', strtotime($rawDate)) . ' ' . $rawTime;
$privateKey = $voucher->private_key ?? '';
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode($privateKey);

$k1 = $key1 ?? ($voucher->key1 ?? '');
$v1 = $val1 ?? ($voucher->val1 ?? '');
$k2 = $key2 ?? ($voucher->key2 ?? '');
$v2 = $val2 ?? ($voucher->val2 ?? '');
$k3 = $key3 ?? ($voucher->key3 ?? '');
$v3 = $val3 ?? ($voucher->val3 ?? '');
$k4 = $key4 ?? ($voucher->key4 ?? '');
$v4 = $val4 ?? ($voucher->val4 ?? '');
$hasItems = ($k1 && $v1) || ($k2 && $v2) || ($k3 && $v3) || ($k4 && $v4);

$isSingle = !empty($single_copy);
?>
<style>
@media print {
    @page {
        size: A4 portrait;
        margin: 0 !important;
    }
    body {
        margin: 0 !important;
        padding: 10mm 8mm 0 8mm !important;
        background: #ffffff !important;
        box-sizing: border-box !important;
    }
    .no-print {
        display: none !important;
    }
    .sw-dual-voucher-container {
        display: flex !important;
        flex-direction: row !important;
        justify-content: flex-start !important;
        gap: 8mm !important;
        page-break-inside: avoid !important;
    }
    .sw-voucher-box {
        width: 95mm !important;
        box-sizing: border-box !important;
        border: 1px solid #000000 !important;
        box-shadow: none !important;
        page-break-inside: avoid !important;
    }
}
</style>

<?php
$renderSlip = function() use (
    $voucher, $custName, $collName, $transType, $pw, $nw, $gw, 
    $purityVal, $vCode, $delBy, $formattedDate, $qrUrl, $hasItems,
    $k1, $v1, $k2, $v2, $k3, $v3, $k4, $v4
) {
?>
<div class="sw-voucher-box" style="box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; font-size: 11px; padding: 10px 12px; width: 95mm; border: 1px solid #000000; background: #ffffff; color: #000000; overflow: hidden; text-align: left; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
    <!-- Header: Customer Name (Left) & Transaction Type (Right) -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
        <div style="font-weight: 700; font-size: 13.5px; text-transform: uppercase; color: #000000; letter-spacing: -0.2px; line-height: 1.2; max-width: 65%;">
            <?= esc(ucwords($custName)) ?>
        </div>
        <div style="font-weight: 700; font-size: 13px; text-transform: uppercase; color: #000000; text-align: right;">
            <?= esc($transType) ?>
        </div>
    </div>

    <!-- Body: Left and Right Columns -->
    <div style="display: flex; justify-content: space-between; gap: 8px;">
        <!-- Left Column: Weights Table & Verification Code -->
        <div style="flex: 1; display: flex; flex-direction: column; justify-content: flex-end;">
            <?php if ($hasItems): ?>
            <table style="width: 100%; font-size: 9.5px; border-collapse: collapse; border: 1px solid #000000; margin-bottom: 6px;">
                <thead>
                    <tr style="font-weight: 700; height: 16px;">
                        <th style="border: 1px solid #000000; padding: 2px 4px; text-align: left;">ITEM</th>
                        <th style="border: 1px solid #000000; padding: 2px 4px; text-align: right;">QTY</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($k1 && $v1): ?>
                    <tr style="height: 15px;"><td style="border: 1px solid #000000; padding: 1px 4px;"><strong><?= esc($k1) ?></strong></td><td style="border: 1px solid #000000; padding: 1px 4px; text-align: right;"><?= esc($v1) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($k2 && $v2): ?>
                    <tr style="height: 15px;"><td style="border: 1px solid #000000; padding: 1px 4px;"><strong><?= esc($k2) ?></strong></td><td style="border: 1px solid #000000; padding: 1px 4px; text-align: right;"><?= esc($v2) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($k3 && $v3): ?>
                    <tr style="height: 15px;"><td style="border: 1px solid #000000; padding: 1px 4px;"><strong><?= esc($k3) ?></strong></td><td style="border: 1px solid #000000; padding: 1px 4px; text-align: right;"><?= esc($v3) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($k4 && $v4): ?>
                    <tr style="height: 15px;"><td style="border: 1px solid #000000; padding: 1px 4px;"><strong><?= esc($k4) ?></strong></td><td style="border: 1px solid #000000; padding: 1px 4px; text-align: right;"><?= esc($v4) ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Weights Table -->
            <table style="width: 100%; font-size: 10px; border-collapse: collapse; border: 1px solid #000000;">
                <?php if (($voucher->transactional_mode ?? 'MT') !== 'RK'): ?>
                <tr style="height: 18px;">
                    <td style="border: 1px solid #000000; font-weight: 700; padding: 2px 6px;">P.W.</td>
                    <td style="border: 1px solid #000000; text-align: right; padding: 2px 6px; font-weight: 600;"><?= $pw ?></td>
                </tr>
                <tr style="height: 18px;">
                    <td style="border: 1px solid #000000; font-weight: 700; padding: 2px 6px;">N.W.</td>
                    <td style="border: 1px solid #000000; text-align: right; padding: 2px 6px; font-weight: 700;"><?= $nw ?></td>
                </tr>
                <tr style="height: 18px;">
                    <td style="border: 1px solid #000000; font-weight: 700; padding: 2px 6px;">TNCH</td>
                    <td style="border: 1px solid #000000; text-align: right; padding: 2px 6px; font-weight: 600;"><?= $purityVal ?> %</td>
                </tr>
                <?php else: ?>
                <tr style="height: 24px;">
                    <td style="border: 1px solid #000000; font-weight: 700; padding: 3px 6px;">AMOUNT</td>
                    <td style="border: 1px solid #000000; text-align: right; padding: 3px 6px; font-weight: 700; font-size: 12.5px;">₹ <?= number_format((float)($voucher->amount ?? 0), 2) ?></td>
                </tr>
                <?php endif; ?>
            </table>

            <!-- Verification Code (Clean, bold, centered) -->
            <div style="margin-top: 10px; text-align: center; font-size: 22px; font-weight: 800; letter-spacing: 1px; color: #000000;">
                <?= esc($vCode) ?>
            </div>
        </div>

        <!-- Right Column: QR Code, Timestamp, Collection, Del By, Sign -->
        <div style="flex: 1; display: flex; flex-direction: column; align-items: flex-end;">
            <!-- QR Code -->
            <div style="width: 100%; display: flex; flex-direction: column; align-items: flex-end; margin-bottom: 6px;">
                <img src="<?= esc($qrUrl) ?>" alt="QR" style="width: 72px; height: 72px; display: block;" />
                <div style="font-size: 9px; font-weight: 700; color: #000000; margin-top: 3px; white-space: nowrap;">
                    <?= esc($formattedDate) ?>
                </div>
            </div>

            <!-- Collection, Del By, SIGN Table -->
            <table style="width: 100%; font-size: 10px; border-collapse: collapse; border: 1px solid #000000;">
                <tr style="height: 18px;">
                    <td style="border: 1px solid #000000; padding: 2px 6px; font-size: 9.5px;">
                        <strong>Collection :</strong> <?= esc($collName) ?>
                    </td>
                </tr>
                <tr style="height: 18px;">
                    <td style="border: 1px solid #000000; padding: 2px 6px; font-size: 9.5px;">
                        <strong>Del. By:</strong> <?= esc($delBy) ?>
                    </td>
                </tr>
                <tr style="height: 38px;">
                    <td style="border: 1px solid #000000; padding: 2px 6px; vertical-align: top; font-size: 9.5px;">
                        <strong>SIGN</strong>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
<?php
};
?>

<?php if ($isSingle): ?>
    <div class="sw-single-voucher-wrapper" style="display: flex; justify-content: center; width: 100%;">
        <?php $renderSlip(); ?>
    </div>
<?php else: ?>
    <div class="sw-dual-voucher-container" style="display: flex; flex-direction: row; justify-content: center; align-items: stretch; gap: 20px; width: 100%;">
        <?php $renderSlip(); ?>
        <?php $renderSlip(); ?>
    </div>
<?php endif; ?>
