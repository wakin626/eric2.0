<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Invoice (WD)</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
        }

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

        /* ─── HEADER LEFT: Customer Info ─── */
        .print-customer-name {
            position: absolute;
            top: 2.20in;
            left: 1.79in;
            width: 3.5in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            text-align: left;
        }

        .print-customer-tin {
            position: absolute;
            top: 2.40in;
            left: 1.79in;
            width: 3.5in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            text-align: left;
        }

        .print-customer-address {
            position: absolute;
            top: 2.60in;
            left: 1.79in;
            width: 3.5in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            text-align: left;
        }

        /* ─── HEADER RIGHT: Date, Terms, PO, DR ─── */
        .print-date {
            position: absolute;
            top: 1.69in;
            right: 1.25in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            text-align: left;
        }

        .print-terms {
            position: absolute;
            top: 2.21in;
            right: 1.25in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            text-align: left;
        }

        .print-po-number {
            position: absolute;
            top: 2.43in;
            right: 1.25in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            text-align: left;
        }

        .print-dr-number {
            position: absolute;
            top: 2.69in;
            right: 1.25in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            text-align: left;
        }

        /* ─── BODY: Item Row ─── */
        .print-item-desc {
            position: absolute;
            top: 3.95in;
            left: 0.76in;
            width: 4.2in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: left;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: clip;
        }

        .print-item-cases {
            position: absolute;
            top: 3.39in;
            left: 4.09in;
            width: 0.7in;
            font-family: Calibri, sans-serif;
            font-size: 10pt;
            text-align: center;
        }

        .print-item-lot {
            position: absolute;
            top: 3.39in;
            left: 3.50in;
            width: 0.8in;
            font-family: Calibri, sans-serif;
            font-size: 10pt;
            text-align: center;
        }

        .print-item-unit {
            position: absolute;
            top: 3.39in;
            left: 4.56in;
            width: 0.6in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: center;
        }

        .print-item-qty {
            position: absolute;
            top: 3.39in;
            left: 4.81in;
            width: 0.8in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: right;
        }

        .print-item-price {
            position: absolute;
            top: 3.39in;
            left: 5.60in;
            width: 0.8in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: right;
        }

        .print-item-amount {
            position: absolute;
            top: 3.50in;
            left: 6.46in;
            width: 1.1in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: right;
        }

        /* ─── LEFT COLUMNS: VAT Breakdown ─── */
        .print-vatable-sales {
            position: absolute;
            left: 3.00in;
            bottom: 3.75in;
            width: 2.0in;
            font-family: Calibri, sans-serif;
            font-size: 10pt;
            text-align: right;
        }

        .print-vat {
            position: absolute;
            left: 3.00in;
            bottom: 3.45in;
            width: 2.0in;
            font-family: Calibri, sans-serif;
            font-size: 10pt;
            text-align: right;
        }

        .print-zero-rated-sales {
            position: absolute;
            left: 3.00in;
            bottom: 3.15in;
            width: 2.0in;
            font-family: Calibri, sans-serif;
            font-size: 10pt;
            text-align: right;
        }

        .print-vat-exempt-sales {
            position: absolute;
            left: 3.00in;
            bottom: 2.90in;
            width: 2.0in;
            font-family: Calibri, sans-serif;
            font-size: 10pt;
            text-align: right;
        }

        /* ─── RIGHT COLUMNS: Total Sales Breakdown ─── */
        .print-total-sales {
            position: absolute;
            left: 5.75in;
            bottom: 3.77in;
            width: 1.7in;
            font-family: Calibri, sans-serif;
            font-size: 10pt;
            text-align: right;
        }

        .print-less-vat {
            position: absolute;
            left: 5.75in;
            bottom: 3.47in;
            width: 1.7in;
            font-family: Calibri, sans-serif;
            font-size: 10pt;
            text-align: right;
        }

        .print-net-of-vat {
            position: absolute;
            left: 5.75in;
            bottom: 3.25in;
            width: 1.7in;
            font-family: Calibri, sans-serif;
            font-size: 10pt;
            text-align: right;
        }

        .print-less-discount {
            position: absolute;
            left: 5.75in;
            bottom: 2.90in;
            width: 1.7in;
            font-family: Calibri, sans-serif;
            font-size: 10pt;
            text-align: right;
        }

        .print-add-vat {
            position: absolute;
            left: 5.75in;
            bottom: 2.60in;
            width: 1.7in;
            font-family: Calibri, sans-serif;
            font-size: 10pt;
            text-align: right;
        }

        .print-less-withholding-tax {
            position: absolute;
            left: 5.75in;
            bottom: 2.30in;
            width: 1.7in;
            font-family: Calibri, sans-serif;
            font-size: 10pt;
            text-align: right;
        }

        .print-total-amount-due {
            position: absolute;
            left: 5.75in;
            bottom: 2.05in;
            width: 1.7in;
            font-family: Calibri, sans-serif;
            font-size: 10pt;
            font-weight: bold;
            text-align: right;
        }

        /* ─── Print Styles ─── */
        @media print {
            @page {
                size: 8.5in 11in;
                margin: 0 !important;
            }

            html, body {
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print { display: none !important; }

            .receipt-container {
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none;
                background: #fff;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()"><i class="bi bi-printer"></i> Print Sales Invoice (WD)</button>
    <button onclick="window.close()" style="background:#6c757d; margin-left:8px;">Close</button>
</div>

<div class="receipt-container">

    <!-- HEADER LEFT: Customer Info -->
    <div class="print-customer-name"><?= htmlspecialchars($customer_name) ?></div>
    <div class="print-customer-tin"><?= htmlspecialchars($customer_tin) ?></div>
    <div class="print-customer-address"><?= htmlspecialchars($customer_address) ?></div>

    <!-- HEADER RIGHT: Date, Terms, PO, DR -->
    <div class="print-date"><?= htmlspecialchars($date) ?></div>
    <div class="print-terms"><?= htmlspecialchars($customer_terms) ?></div>
    <div class="print-po-number"><?= htmlspecialchars($po_number) ?></div>
    <div class="print-dr-number"><?= htmlspecialchars($dr_number) ?></div>

    <!-- BODY: Item Rows -->
    <?php
    $rowTop = 3.45;
    $rowHeight = 0.30;
    foreach ($items as $idx => $item):
    ?>
    <div class="print-item-desc" style="top: <?= $rowTop + ($idx * $rowHeight) ?>in;"><?= htmlspecialchars($item['item_description']) ?></div>
    <div class="print-item-lot" style="top: <?= $rowTop + ($idx * $rowHeight) ?>in;"><?= htmlspecialchars($item['lot_number'] ?? '') ?></div>
    <div class="print-item-cases" style="top: <?= $rowTop + ($idx * $rowHeight) ?>in;"><?= $item['cases'] > 0 ? $item['cases'] . ' CS' : '' ?></div>
    <div class="print-item-unit" style="top: <?= $rowTop + ($idx * $rowHeight) ?>in;"><?= htmlspecialchars($item['item_uom']) ?></div>
    <div class="print-item-qty" style="top: <?= $rowTop + ($idx * $rowHeight) ?>in;"><?= number_format($item['qty']) ?></div>
    <div class="print-item-price" style="top: <?= $rowTop + ($idx * $rowHeight) ?>in;"><?= number_format($item['price'], 2) ?></div>
    <div class="print-item-amount" style="top: <?= $rowTop + ($idx * $rowHeight) ?>in;"><?= number_format($item['amount'], 2) ?></div>
    <?php endforeach; ?>

    <!-- LEFT COLUMNS: VAT Breakdown -->
    <div class="print-vatable-sales"><?= $vatable_sales > 0 ? number_format($vatable_sales, 2) : '-' ?></div>
    <div class="print-vat"><?= $vat_amount > 0 ? number_format($vat_amount, 2) : '-' ?></div>
    <div class="print-zero-rated-sales"><?= $zero_rated_sales > 0 ? number_format($zero_rated_sales, 2) : '-' ?></div>
    <div class="print-vat-exempt-sales"><?= $vat_exempt_sales > 0 ? number_format($vat_exempt_sales, 2) : '-' ?></div>

    <!-- RIGHT COLUMNS: Total Sales Breakdown -->
    <div class="print-total-sales"><?= number_format($grand_total, 2) ?></div>
    <div class="print-less-vat"><?= $vatType === 'vat' ? number_format($vat, 2) : '-' ?></div>
    <div class="print-net-of-vat"><?= $subtotal > 0 ? number_format($subtotal, 2) : '-' ?></div>
    <div class="print-less-discount">-</div>
    <div class="print-add-vat"><?= $vatType === 'vat' && $vat > 0 ? number_format($vat, 2) : '-' ?></div>
    <div class="print-less-withholding-tax">-</div>
    <div class="print-total-amount-due"><?= number_format($grand_total, 2) ?></div>

</div>

</body>
</html>
