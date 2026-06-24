<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Receipt</title>
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
            height: 11in;
            position: relative;
            overflow: hidden;
            background: #fff;
            margin: 10px auto;
            font-family: Calibri, sans-serif;
        }
        .print-date {
            position: absolute;
            top: 1.8in;
            right: 0.5in;
            font-size: 11pt;
            font-weight: bold;
        }
        .print-company-name {
            position: absolute;
            top: 2.2in;
            left: 1.35in;
            font-size: 11pt;
            font-weight: bold;
        }
        .print-company-tin {
            position: absolute;
            top: 2.4in;
            left: 1.35in;
            font-size: 11pt;
            font-weight: bold;
        }
        .print-company-address {
            position: absolute;
            top: 2.6in;
            left: 1.35in;
            font-size: 11pt;
            font-weight: bold;
        }
        .print-terms {
            position: absolute;
            top: 2.2in;
            right: 0.5in;
            font-size: 11pt;
            font-weight: bold;
        }
        .print-customer-code {
            position: absolute;
            top: 2.4in;
            right: 0.5in;
            font-size: 11pt;
            font-weight: bold;
        }
        .print-po-number {
            position: absolute;
            top: 2.6in;
            right: 0.5in;
            font-size: 11pt;
            font-weight: bold;
        }
        .print-items-area {
            position: absolute;
            top: 3.8in;
            left: 0.55in;
            width: 7.4in;
        }
        .print-items-area table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
        }
        .print-items-area th {
            text-align: left;
            border-bottom: 1px solid #000;
            padding: 4px 2px;
            font-size: 10pt;
        }
        .print-items-area td {
            padding: 4px 2px;
            vertical-align: top;
        }
        .print-items-area .text-right { text-align: right; }
        .print-items-area .text-center { text-align: center; }
        .lot-header {
            font-weight: bold;
            background: #f0f0f0;
            padding: 3px 2px;
            margin-top: 8px;
            border-bottom: 1px solid #999;
        }
        .print-totals-block {
            position: absolute;
            bottom: 3.0in;
            left: 3.5in;
            font-size: 11pt;
            line-height: 1.8;
        }
        .print-totals-block .totals-row {
            display: flex;
            justify-content: flex-end;
            width: 4.2in;
        }
        .print-totals-block .totals-row .totals-value {
            text-align: right;
            min-width: 1.5in;
        }
        .print-totals-block .totals-grand {
            font-weight: bold;
            margin-top: 2px;
        }
        @media print {
            @page { size: 8.5in 11in; margin: 0 !important; }
            html, body { padding: 0 !important; margin: 0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .receipt-container { margin: 0 !important; padding: 0 !important; box-shadow: none; background: #fff; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()"><i class="bi bi-printer"></i> Print Delivery Receipt</button>
</div>

<div class="receipt-container">
    <div class="print-date"><?= date('d-M-Y', strtotime($delivery['delivery_date'])) ?></div>

    <div class="print-company-name">SKINTEC ADVANCE INCORPORATED</div>
    <div class="print-company-tin">008-434-783-000</div>
    <div class="print-company-address">BYPASS ROAD BULIHAN PLARIDEL BULACAN 3004</div>

    <div class="print-terms"><?= htmlspecialchars($delivery['customer_terms'] ?? '') ?> DAYS</div>
    <div class="print-customer-code"><?= htmlspecialchars($delivery['customer_code'] ?? '') ?></div>
    <div class="print-po-number"><?= htmlspecialchars($delivery['customer_po_number'] ?? '') ?></div>

    <div class="print-items-area">
        <table>
            <thead>
                <tr>
                    <th style="width:45%">Item Description</th>
                    <th class="text-center" style="width:10%">UOM</th>
                    <th class="text-right" style="width:15%">Quantity</th>
                    <th class="text-right" style="width:15%">Price</th>
                    <th class="text-right" style="width:15%">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($delivery['lot_number'])): ?>
                <tr>
                    <td colspan="5" class="lot-header">Lot: <?= htmlspecialchars($delivery['lot_number']) ?></td>
                </tr>
                <?php endif; ?>
                <?php
                $itemDesc = $delivery['delivery_item_description'] ?? '-';
                $itemUom = $delivery['item_uom'] ?? '';
                $itemQty = $delivery['delivery_quantity'] ?? 0;
                $conv = $delivery['uom_conversion'] ?? null;
                $cases = '';
                if ($conv && $itemUom !== 'CS') {
                    $cases = ' / ' . round($itemQty / $conv, 2) . ' CS';
                }
                $grandTotal = $itemQty * ($po_items[0]['unit_price'] ?? 0);
                ?>
                <tr>
                    <td><?= htmlspecialchars($itemDesc) ?></td>
                    <td class="text-center"><?= htmlspecialchars($itemUom) ?><?= $cases ?></td>
                    <td class="text-right"><?= number_format($itemQty) ?></td>
                    <td class="text-right"><?= number_format($po_items[0]['unit_price'] ?? 0, 2) ?></td>
                    <td class="text-right"><?= number_format($grandTotal, 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="print-totals-block">
        <div class="totals-row totals-grand">
            <span class="totals-value"><?= number_format($grandTotal, 2) ?></span>
        </div>
    </div>
</div>

</body>
</html>
