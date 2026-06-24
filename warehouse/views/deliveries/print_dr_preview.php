<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Receipt - <?= htmlspecialchars($po['customer_po_number'] ?? '') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            color: #000;
            background: #e0e0e0;
        }
        .no-print { display: block; text-align: center; padding: 12px; }
        .no-print button {
            padding: 10px 30px;
            font-size: 14px;
            cursor: pointer;
            background: #0d6efd;
            color: #fff;
            border: none;
            border-radius: 5px;
        }
        .no-print button:hover { background: #0b5ed7; }
        .receipt-container {
            width: 8.5in;
            min-height: 11in;
            position: relative;
            background: #fff;
            margin: 10px auto;
            font-family: Calibri, sans-serif;
            padding: 0.5in;
        }
        .receipt-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .receipt-header .company-info {
            font-weight: bold;
            line-height: 1.6;
        }
        .receipt-header .po-info {
            text-align: right;
            font-weight: bold;
            line-height: 1.6;
        }
        .receipt-customer {
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .receipt-table th {
            text-align: left;
            border-bottom: 2px solid #000;
            padding: 6px 4px;
            font-size: 10pt;
        }
        .receipt-table td {
            padding: 5px 4px;
            vertical-align: top;
            border-bottom: 1px solid #eee;
        }
        .receipt-table .text-right { text-align: right; }
        .receipt-table .text-center { text-align: center; }
        .receipt-table .lot-row {
            background: #f8f8f8;
        }
        .receipt-table .lot-row td {
            border-bottom: 1px solid #ddd;
        }
        .receipt-table .item-separator td {
            padding-top: 12px;
            border-bottom: 2px solid #999;
            font-weight: bold;
        }
        .receipt-totals {
            display: flex;
            justify-content: flex-end;
            margin-top: 10px;
        }
        .receipt-totals table {
            border-collapse: collapse;
            min-width: 3in;
        }
        .receipt-totals td {
            padding: 3px 8px;
        }
        .receipt-totals .grand-total {
            font-weight: bold;
            border-top: 2px solid #000;
            font-size: 12pt;
        }
        @media print {
            @page { size: 8.5in 11in; margin: 0.25in; }
            html, body { padding: 0 !important; margin: 0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; background: #fff; }
            .no-print { display: none !important; }
            .receipt-container { margin: 0 !important; box-shadow: none; background: #fff; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="?controller=warehouse&action=printDR&po_id=<?= htmlspecialchars($po['po_id'] ?? '') ?>" class="btn btn-secondary" style="margin-right: 10px;"><i class="bi bi-arrow-left me-1"></i> Back</a>
    <button onclick="window.print()"><i class="bi bi-printer"></i> Print Delivery Receipt</button>
</div>

<div class="receipt-container">
    <div class="receipt-header">
        <div class="company-info">
            SKINTEC ADVANCE INCORPORATED<br>
            008-434-783-000<br>
            BYPASS ROAD BULIHAN PLARIDEL BULACAN 3004
        </div>
        <div class="po-info">
            Date: <?= date('d-M-Y') ?><br>
            PO Number: <?= htmlspecialchars($po['customer_po_number'] ?? '') ?><br>
            Terms: <?= htmlspecialchars($po['customer_terms'] ?? '') ?> DAYS
        </div>
    </div>

    <div class="receipt-customer">
        <strong>Customer:</strong> <?= htmlspecialchars($po['customer_code'] ?? '') ?> - <?= htmlspecialchars($po['customer_name'] ?? '') ?><br>
        <strong>Address:</strong> <?= htmlspecialchars($po['customer_address'] ?? '') ?>
    </div>

    <table class="receipt-table">
        <thead>
            <tr>
                <th style="width:12%">Lot #</th>
                <th style="width:30%">Item Description</th>
                <th class="text-center" style="width:8%">UOM</th>
                <th class="text-right" style="width:12%">Quantity</th>
                <th class="text-right" style="width:12%">Unit Price</th>
                <th class="text-right" style="width:14%">Amount</th>
                <th class="text-right" style="width:12%">Cases</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $grandTotalQty = 0;
            $grandTotalAmount = 0;
            $grandTotalCases = 0;
            $prevItemId = null;

            foreach ($selected_lots as $lot):
                $remaining = max(0, ($lot['quantity_produced'] ?? 0) - $lot['total_delivered'] ?? 0);
                $qty = min($remaining, $lot['quantity_produced'] ?? 0);
                $amount = $qty * ($lot['unit_price'] ?? 0);
                $conv = $lot['uom_conversion'] ?? null;
                $cases = ($conv && ($lot['item_uom'] ?? '') !== 'CS') ? round($qty / $conv, 2) : 0;

                $grandTotalQty += $qty;
                $grandTotalAmount += $amount;
                $grandTotalCases += $cases;
            ?>
                <?php if ($prevItemId !== null && $prevItemId != $lot['item_id']): ?>
                    <tr class="item-separator">
                        <td colspan="7"></td>
                    </tr>
                <?php endif; ?>
                <tr class="lot-row">
                    <td><strong><?= htmlspecialchars($lot['lot_number']) ?></strong></td>
                    <td><?= htmlspecialchars($lot['item_description'] ?? '') ?></td>
                    <td class="text-center"><?= htmlspecialchars($lot['item_uom'] ?? '') ?></td>
                    <td class="text-right"><?= number_format($qty) ?></td>
                    <td class="text-right"><?= number_format($lot['unit_price'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= number_format($amount, 2) ?></td>
                    <td class="text-right">
                        <?php if ($cases > 0): ?>
                            <?= $cases ?> CS
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php $prevItemId = $lot['item_id']; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="receipt-totals">
        <table>
            <tr>
                <td>Total Quantity:</td>
                <td class="text-right"><strong><?= number_format($grandTotalQty) ?></strong></td>
            </tr>
            <?php if ($grandTotalCases > 0): ?>
            <tr>
                <td>Total Cases:</td>
                <td class="text-right"><strong><?= $grandTotalCases ?> CS</strong></td>
            </tr>
            <?php endif; ?>
            <tr class="grand-total">
                <td>Total Amount:</td>
                <td class="text-right"><?= number_format($grandTotalAmount, 2) ?></td>
            </tr>
        </table>
    </div>
</div>

</body>
</html>
