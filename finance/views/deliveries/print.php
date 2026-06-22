<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Invoice</title>
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
            /* border removed for print */
        }

        /* ─── Date (top right) ─── */
        .print-date {
            position: absolute;
            top: 1.65in;
            right: 0.65in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            text-align: left;
        }

        /* ─── Company Name (center-left) ─── */
        .print-company-name {
            position: absolute;
            top: 2.3in;
            left: 1.45in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            text-align: left;
        }

        /* ─── Company TIN (center-left) ─── */
        .print-company-tin {
            position: absolute;
            top: 2.5in;
            left: 1.55in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            text-align: left;
        }

        /* ─── Company Address (center-left) ─── */
        .print-company-address {
            position: absolute;
            top: 2.8in;
            left: 1.4in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            text-align: left;
        }

        /* ─── Terms (right side, same row as company) ─── */
        .print-terms {
            position: absolute;
            top: 2.2in;
            right: 0.72in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            text-align: left;
        }

        /* ─── Customer Code (right side, below terms) ─── */
        .print-customer-code {
            position: absolute;
            top: 2.45in;
            right: 0.6in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            text-align: left;
        }

        /* ─── PO Number (right side, below customer code) ─── */
        .print-po-number {
            position: absolute;
            top: 2.75in;
            right: 0.75in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            font-weight: bold;
            text-align: left;
        }

        /* ─── Item Description ─── */
        .print-item-desc {
            position: absolute;
            top: 3.85in;
            left: 0.55in;
            width: 4.2in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: left;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: clip;
        }

        /* ─── Item Unit ─── */
        .print-item-unit {
            position: absolute;
            top: 3.85in;
            left: 4.6in;
            width: 0.6in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: center;
        }

        /* ─── Item Qty ─── */
        .print-item-qty {
            position: absolute;
            top: 3.85in;
            left: 5.2in;
            width: 0.8in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: right;
        }

        /* ─── Item Price ─── */
        .print-item-price {
            position: absolute;
            top: 3.85in;
            left: 6.1in;
            width: 0.8in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: right;
        }

        /* ─── Item Amount ─── */
        .print-item-amount {
            position: absolute;
           top: 3.85in;
            left: 7.0in;
            width: 1.1in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: right;
        }

        /* ─── Totals Block (lower right) ─── */
        .print-totals-block {
            position: absolute;
            bottom: 2.0in;
            left: 3.5in;
            font-family: Calibri, sans-serif;
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
    <button onclick="window.print()"><i class="bi bi-printer"></i> Print Sales Invoice</button>
    <button onclick="window.close()" style="background:#6c757d; margin-left:8px;">Close</button>
</div>

<div class="receipt-container">

    <!-- DATE (top right) -->
    <div class="print-date">3-Jun-2026</div>

    <!-- COMPANY INFO (center-left) -->
    <div class="print-company-name">SKINTEC ADVANCE INCORPORATED</div>
    <div class="print-company-tin">008-434-783-000</div>
    <div class="print-company-address">BYPASS ROAD BULIHAN PLARIDEL BULACAN 3004</div>

    <!-- TERMS (right side) -->
    <div class="print-terms">90 DAYS</div>

    <!-- CUSTOMER CODE (right side) -->
    <div class="print-customer-code">SKI-CC-02199</div>

    <!-- PO NUMBER (right side) -->
    <div class="print-po-number">16529</div>

    <!-- ITEM ROW (individual elements) -->
    <div class="print-item-desc">Empress Shampoo Long and Healthy 21mlx24pck (11+1)</div>
    <div class="print-item-unit">Pck</div>
    <div class="print-item-qty">4,224</div>
    <div class="print-item-price">29.75</div>
    <div class="print-item-amount">125,664.00</div>

    <!-- TOTALS BLOCK -->
    <div class="print-totals-block">
        <div class="totals-row">
            <span class="totals-value">112,200.00</span>
        </div>
        <div class="totals-row">
            <span class="totals-value">13,464.00</span>
        </div>
        <div class="totals-row">
            <span class="totals-value">-</span>
        </div>
        <div class="totals-row">
            <span class="totals-value">-</span>
        </div>
        <div class="totals-row totals-grand">
            <span class="totals-value">125,664.00</span>
        </div>
    </div>

</div>

</body>
</html>
