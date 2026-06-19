<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Invoice</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Courier New', monospace;
            font-size: 11px;
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
            font-family: 'Courier New', monospace;
            border: 2px solid #000;
        }

        /* ─── Date (top right) ─── */
        .print-date {
            position: absolute;
            top: 0.5in;
            right: 0.5in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
        }

        /* ─── Company Info (center-left) ─── */
        .print-company-info {
            position: absolute;
            top: 0.9in;
            left: 1.35in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
            line-height: 1.5;
        }

        /* ─── Terms (right side, same row as company) ─── */
        .print-terms {
            position: absolute;
            top: 0.9in;
            right: 0.5in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
        }

        /* ─── Customer Code (right side, below terms) ─── */
        .print-customer-code {
            position: absolute;
            top: 1.1in;
            right: 0.5in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
        }

        /* ─── PO Number (right side, below customer code) ─── */
        .print-po-number {
            position: absolute;
            top: 1.3in;
            right: 0.5in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
        }

        /* ─── Item Row (single line) ─── */
        .print-item-row {
            position: absolute;
            top: 2.55in;
            left: 0.4in;
            width: 7.7in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            display: flex;
            overflow: hidden;
        }

        .print-item-row .col-desc     { flex: 1; text-align: left; overflow: hidden; white-space: nowrap; text-overflow: clip; }
        .print-item-row .col-unit     { width: 0.5in; text-align: center; flex-shrink: 0; }
        .print-item-row .col-qty      { width: 0.7in; text-align: right; flex-shrink: 0; }
        .print-item-row .col-price    { width: 0.7in; text-align: right; flex-shrink: 0; }
        .print-item-row .col-amount   { width: 1in; text-align: right; flex-shrink: 0; }

        /* ─── Totals Block (lower right) ─── */
        .print-totals-block {
            position: absolute;
            bottom: 3.0in;
            left: 3.5in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
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
                margin: 0;
            }

            body {
                background: none;
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print { display: none !important; }

            .receipt-container {
                margin: 0;
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
    <div class="print-company-info">
        <div>SKINTEC ADVANCE INCORPORATED</div>
        <div>008-434-783-000</div>
        <div>BYPASS ROAD BULIHAN PLARIDEL BULACAN 3004</div>
    </div>

    <!-- TERMS (right side) -->
    <div class="print-terms">90 DAYS</div>

    <!-- CUSTOMER CODE (right side) -->
    <div class="print-customer-code">SKI-CC-02199</div>

    <!-- PO NUMBER (right side) -->
    <div class="print-po-number">16529</div>

    <!-- ITEM ROW (single line) -->
    <div class="print-item-row">
        <div class="col-desc">Empress Shampoo Long and Healthy 21mlx24pck (11+1)</div>
        <div class="col-unit">Pck</div>
        <div class="col-qty">4,224</div>
        <div class="col-price">29.75</div>
        <div class="col-amount">125,664.00</div>
    </div>

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
