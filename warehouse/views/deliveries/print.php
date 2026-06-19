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

        /* ─── Header: Company Name ─── */
        .print-header {
            position: absolute;
            top: 0.4in;
            left: 0;
            width: 100%;
            text-align: center;
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }

        .print-header .company-name {
            font-size: 14px;
            text-decoration: underline;
        }

        .print-header .company-tin {
            font-size: 11px;
            margin-top: 2px;
        }

        .print-header .company-address {
            font-size: 10px;
            margin-top: 2px;
        }

        /* ─── Sales Invoice Title ─── */
        .print-title {
            position: absolute;
            top: 1.35in;
            left: 0;
            width: 100%;
            text-align: center;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
        }

        /* ─── Invoice Number (top right) ─── */
        .print-invoice-number {
            position: absolute;
            top: 0.45in;
            right: 0.5in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
        }

        /* ─── Date (below invoice number) ─── */
        .print-date {
            position: absolute;
            top: 0.7in;
            right: 0.5in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
        }

        /* ─── Terms (below date) ─── */
        .print-terms {
            position: absolute;
            top: 0.95in;
            right: 0.5in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
        }

        /* ─── Customer Block (left side) ─── */
        .print-customer-block {
            position: absolute;
            top: 1.65in;
            left: 0.5in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            line-height: 1.6;
        }

        .print-customer-block .label {
            display: inline-block;
            width: 1.3in;
        }

        /* ─── Item Table Header ─── */
        .print-table-header {
            position: absolute;
            top: 3.1in;
            left: 0.4in;
            width: 7.7in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            display: flex;
        }

        .print-table-header .col-no       { width: 0.4in; text-align: center; flex-shrink: 0; }
        .print-table-header .col-desc     { flex: 1; text-align: left; }
        .print-table-header .col-unit     { width: 0.6in; text-align: center; flex-shrink: 0; }
        .print-table-header .col-qty      { width: 0.7in; text-align: right; flex-shrink: 0; }
        .print-table-header .col-price    { width: 0.9in; text-align: right; flex-shrink: 0; }
        .print-table-header .col-amount   { width: 1.1in; text-align: right; flex-shrink: 0; }

        /* ─── Item Rows ─── */
        .print-item-rows {
            position: absolute;
            top: 3.35in;
            left: 0.4in;
            width: 7.7in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
        }

        .print-item-row {
            display: flex;
            padding: 3px 0;
            overflow: hidden;
        }

        .print-item-row .col-no       { width: 0.4in; text-align: center; flex-shrink: 0; }
        .print-item-row .col-desc     { flex: 1; text-align: left; overflow: hidden; white-space: nowrap; text-overflow: clip; }
        .print-item-row .col-unit     { width: 0.6in; text-align: center; flex-shrink: 0; }
        .print-item-row .col-qty      { width: 0.7in; text-align: right; flex-shrink: 0; }
        .print-item-row .col-price    { width: 0.9in; text-align: right; flex-shrink: 0; }
        .print-item-row .col-amount   { width: 1.1in; text-align: right; flex-shrink: 0; }

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

        .print-totals-block .totals-label {
            text-align: right;
            min-width: 1.5in;
        }

        .print-totals-block .totals-value {
            text-align: right;
            min-width: 1.5in;
        }

        .print-totals-block .totals-grand {
            font-weight: bold;
            margin-top: 2px;
            border-top: 1px solid #000;
            padding-top: 2px;
        }

        /* ─── Amount in Words ─── */
        .print-amount-words {
            position: absolute;
            bottom: 2.4in;
            left: 0.5in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            line-height: 1.6;
        }

        /* ─── Signature Block ─── */
        .print-signatures {
            position: absolute;
            bottom: 0.6in;
            left: 0.5in;
            width: 7.5in;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            display: flex;
            justify-content: space-between;
        }

        .print-signature-block {
            text-align: center;
            width: 2.2in;
        }

        .print-signature-block .sig-line {
            border-bottom: 1px solid #000;
            height: 0.5in;
            margin-bottom: 4px;
        }

        .print-signature-block .sig-label {
            font-size: 10px;
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

    <!-- COMPANY HEADER -->
    <div class="print-header">
        <div class="company-name">SKINTEC ADVANCE INCORPORATED</div>
        <div class="company-tin">TIN: 008-434-783-000</div>
        <div class="company-address">BYPASS ROAD BULIHAN PLARIDEL BULACAN 3004</div>
    </div>

    <!-- SALES INVOICE TITLE -->
    <div class="print-title">SALES INVOICE</div>

    <!-- INVOICE NUMBER (top right) -->
    <div class="print-invoice-number">SI No: 000001</div>

    <!-- DATE -->
    <div class="print-date">Date: 03-Jun-2026</div>

    <!-- TERMS -->
    <div class="print-terms">Terms: 90 DAYS</div>

    <!-- CUSTOMER BLOCK -->
    <div class="print-customer-block">
        <div><span class="label">Customer Name:</span> SAMPLE CUSTOMER Corp.</div>
        <div><span class="label">Customer Code:</span> SKI-CC-02199</div>
        <div><span class="label">Address:</span> Manila, Philippines</div>
        <div><span class="label">TIN:</span> 123-456-789-000</div>
        <div><span class="label">PO Number:</span> 16529</div>
    </div>

    <!-- TABLE HEADER -->
    <div class="print-table-header">
        <div class="col-no">No.</div>
        <div class="col-desc">Description</div>
        <div class="col-unit">Unit</div>
        <div class="col-qty">Qty</div>
        <div class="col-price">Unit Price</div>
        <div class="col-amount">Amount</div>
    </div>

    <!-- ITEM ROWS -->
    <div class="print-item-rows">
        <div class="print-item-row">
            <div class="col-no">1</div>
            <div class="col-desc">Empress Shampoo Long and Healthy 21mlx24pck (11+1)</div>
            <div class="col-unit">Pck</div>
            <div class="col-qty">4,224</div>
            <div class="col-price">29.75</div>
            <div class="col-amount">125,664.00</div>
        </div>
        <div class="print-item-row">
            <div class="col-no">2</div>
            <div class="col-desc">Empress Shampoo Smooth and Silky 21mlx24pck (11+1)</div>
            <div class="col-unit">Pck</div>
            <div class="col-qty">2,000</div>
            <div class="col-price">29.75</div>
            <div class="col-amount">59,500.00</div>
        </div>
        <div class="print-item-row">
            <div class="col-no">3</div>
            <div class="col-desc">Empress Shampoo Dandruff Care 21mlx24pck (11+1)</div>
            <div class="col-unit">Pck</div>
            <div class="col-qty">1,500</div>
            <div class="col-price">29.75</div>
            <div class="col-amount">44,625.00</div>
        </div>
    </div>

    <!-- TOTALS BLOCK -->
    <div class="print-totals-block">
        <div class="totals-row">
            <span class="totals-label">Subtotal:</span>
            <span class="totals-value">229,789.00</span>
        </div>
        <div class="totals-row">
            <span class="totals-label">VAT (12%):</span>
            <span class="totals-value">27,574.68</span>
        </div>
        <div class="totals-row">
            <span class="totals-label">Discount:</span>
            <span class="totals-value">-</span>
        </div>
        <div class="totals-row totals-grand">
            <span class="totals-label">TOTAL:</span>
            <span class="totals-value">257,363.68</span>
        </div>
    </div>

    <!-- AMOUNT IN WORDS -->
    <div class="print-amount-words">
        <div><strong>Amount in Words:</strong></div>
        <div>Two Hundred Fifty-Seven Thousand Three Hundred Sixty-Three Pesos & 68/100</div>
    </div>

    <!-- SIGNATURE BLOCKS -->
    <div class="print-signatures">
        <div class="print-signature-block">
            <div class="sig-line"></div>
            <div class="sig-label">Prepared By</div>
        </div>
        <div class="print-signature-block">
            <div class="sig-line"></div>
            <div class="sig-label">Received By</div>
        </div>
        <div class="print-signature-block">
            <div class="sig-line"></div>
            <div class="sig-label">Authorized Signature</div>
        </div>
    </div>

</div>

</body>
</html>
