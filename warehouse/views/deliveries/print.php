<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Receipt - <?= htmlspecialchars($delivery['customer_po_number'] ?? '') ?></title>
    <link href="../../public/css/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            color: #000;
            background: #f0f0f0;
            padding: 20px;
        }

        .print-container {
            background: #fff;
            width: 100%;
            max-width: 8.5in;
            margin: 0 auto;
            padding: 0.4in 0.5in;
            border: 1px solid #ccc;
        }

        /* Screen-only print button */
        .no-print { display: block; }
        .no-print button {
            display: block;
            margin: 0 auto 15px;
            padding: 10px 30px;
            font-size: 14px;
            cursor: pointer;
            background: #0d6efd;
            color: #fff;
            border: none;
            border-radius: 5px;
        }
        .no-print button:hover { background: #0b5ed7; }

        /* Header */
        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 12px;
        }
        .company-info {
            text-align: center;
            flex: 1;
        }
        .company-info h2 {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .company-info p {
            font-size: 11px;
            line-height: 1.4;
        }
        .receipt-title-block {
            text-align: right;
            min-width: 180px;
        }
        .receipt-title-block h3 {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .receipt-no {
            font-size: 11px;
            text-align: right;
        }
        .receipt-no span {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 100px;
            padding-bottom: 1px;
            font-weight: 600;
        }

        /* Meta info fields */
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 30px;
            margin-bottom: 16px;
            font-size: 11.5px;
        }
        .meta-field {
            display: flex;
            align-items: baseline;
            gap: 6px;
        }
        .meta-field label {
            font-weight: 600;
            white-space: nowrap;
        }
        .meta-field .field-value {
            flex: 1;
            border-bottom: 1px solid #000;
            min-height: 16px;
            padding-bottom: 1px;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 11.5px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
        }
        .items-table th {
            background: #f5f5f5;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10.5px;
            text-align: center;
        }
        .items-table td { vertical-align: top; }
        .items-table .col-qty { width: 10%; text-align: center; }
        .items-table .col-unit { width: 10%; text-align: center; }
        .items-table .col-desc { width: 35%; }
        .items-table .col-price { width: 15%; text-align: right; }
        .items-table .col-total { width: 15%; text-align: right; }
        .items-table tbody tr { min-height: 28px; }
        .items-table tbody tr.empty-row { height: 28px; }
        .items-table tfoot td {
            font-weight: 700;
            text-align: right;
        }

        /* Signatures section */
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1.2fr;
            gap: 20px;
            margin-top: 30px;
            font-size: 11px;
        }
        .sig-column h4 {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 14px;
            border-bottom: 1px solid #999;
            padding-bottom: 3px;
        }
        .sig-line {
            margin-bottom: 22px;
        }
        .sig-line .sig-label {
            font-size: 10px;
            color: #333;
            margin-bottom: 2px;
        }
        .sig-line .sig-line-box {
            border-bottom: 1px solid #000;
            height: 30px;
        }
        .sig-line .sig-date {
            font-size: 9.5px;
            color: #666;
            margin-top: 2px;
        }

        /* Right block - customer acknowledgement */
        .ack-block {
            border: 1px solid #000;
            padding: 10px;
            min-height: 120px;
        }
        .ack-block h4 {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 10px;
            border-bottom: 1px solid #999;
            padding-bottom: 3px;
        }
        .ack-text {
            font-size: 10.5px;
            line-height: 1.5;
            margin-bottom: 12px;
        }
        .ack-signature {
            margin-top: 20px;
        }
        .ack-signature .sig-label {
            font-size: 10px;
            color: #333;
            margin-bottom: 2px;
        }
        .ack-signature .sig-line-box {
            border-bottom: 1px solid #000;
            height: 28px;
        }

        /* Print-specific styles */
        @media print {
            body {
                background: none;
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print { display: none !important; }

            .print-container {
                border: none;
                padding: 0.3in 0.4in;
                max-width: 100%;
                margin: 0;
                box-shadow: none;
                background: #fff;
            }

            .receipt-header {
                border-bottom: 2px solid #000;
            }

            .items-table th {
                background: #f5f5f5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .items-table tbody tr.empty-row {
                min-height: 28px;
                page-break-inside: avoid;
            }

            .signatures {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()"><i class="bi bi-printer"></i> Print Delivery Receipt</button>
</div>

<div class="print-container">

    <!-- HEADER: Company info centered, Receipt title on right -->
    <div class="receipt-header">
        <div class="company-info">
            <h2>Your Company Name</h2>
            <p>Company Address Line 1, City, Province ZIP</p>
            <p>TIN: 123-456-789-000</p>
        </div>
        <div class="receipt-title-block">
            <h3>Delivery Receipt</h3>
            <div class="receipt-no">No. <span><?= htmlspecialchars($delivery['customer_po_number'] ?? '') ?></span></div>
        </div>
    </div>

    <!-- META INFO: Delivered to, Address, Date, Terms -->
    <div class="meta-grid">
        <div class="meta-field">
            <label>Delivered to:</label>
            <div class="field-value"><?= htmlspecialchars($delivery['customer_name'] ?? '') ?></div>
        </div>
        <div class="meta-field">
            <label>Date:</label>
            <div class="field-value"><?= date('F d, Y', strtotime($delivery['delivery_date'])) ?></div>
        </div>
        <div class="meta-field">
            <label>Address:</label>
            <div class="field-value"><?= htmlspecialchars($delivery['customer_address'] ?? '') ?></div>
        </div>
        <div class="meta-field">
            <label>Terms:</label>
            <div class="field-value"><?= ($delivery['customer_terms'] ?? 0) > 0 ? $delivery['customer_terms'] . ' days' : '' ?></div>
        </div>
        <div class="meta-field">
            <label>Customer Code:</label>
            <div class="field-value"><?= htmlspecialchars($delivery['customer_code'] ?? '') ?></div>
        </div>
        <div class="meta-field">
            <label>TIN:</label>
            <div class="field-value"><?= htmlspecialchars($delivery['customer_tin'] ?? '') ?></div>
        </div>
    </div>

    <!-- ITEMS TABLE -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-qty">Quantity</th>
                <th class="col-unit">Unit</th>
                <th class="col-desc">Description</th>
                <th class="col-price">Unit Price</th>
                <th class="col-total">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($po_items)): ?>
                <?php foreach ($po_items as $item): ?>
                <tr>
                    <td class="col-qty">
                        <?php if (!empty($delivery['poi_id']) && $item['poi_id'] == $delivery['poi_id']): ?>
                            <?= htmlspecialchars($delivery['delivery_quantity'] ?? $item['quantity']) ?>
                        <?php else: ?>
                            <?= htmlspecialchars($item['quantity'] ?? '') ?>
                        <?php endif; ?>
                    </td>
                    <td class="col-unit"><?= htmlspecialchars($item['item_uom'] ?? '') ?></td>
                    <td class="col-desc"><?= htmlspecialchars($item['item_description'] ?? '') ?></td>
                    <td class="col-price"><?= number_format($item['unit_price'] ?? 0, 2) ?></td>
                    <td class="col-total"><?= number_format(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0), 2) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php
            $rowCount = count($po_items ?? []);
            $emptyRows = max(0, 8 - $rowCount);
            for ($i = 0; $i < $emptyRows; $i++):
            ?>
            <tr class="empty-row">
                <td>&nbsp;</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <?php endfor; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: right; border: 1px solid #000;">Grand Total:</td>
                <td class="col-total" style="border: 1px solid #000;">
                    <?php
                    $grandTotal = 0;
                    if (!empty($po_items)):
                        foreach ($po_items as $item):
                            $grandTotal += ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
                        endforeach;
                    endif;
                    echo number_format($grandTotal, 2);
                    ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- REMARKS -->
    <?php if (!empty($delivery['remarks'])): ?>
    <div style="margin-bottom: 14px; font-size: 11px;">
        <strong>Remarks:</strong> <?= htmlspecialchars($delivery['remarks']) ?>
    </div>
    <?php endif; ?>

    <!-- SIGNATURES SECTION -->
    <div class="signatures">
        <!-- Left: Released by, Checked by, Verified by -->
        <div class="sig-column">
            <h4>Prepared By</h4>
            <div class="sig-line">
                <div class="sig-label">Released by:</div>
                <div class="sig-line-box"></div>
            </div>
            <div class="sig-line">
                <div class="sig-label">Checked by:</div>
                <div class="sig-line-box"></div>
            </div>
            <div class="sig-line">
                <div class="sig-label">Verified by:</div>
                <div class="sig-line-box"></div>
            </div>
        </div>

        <!-- Center: Delivered by, Plate No., Noted by -->
        <div class="sig-column">
            <h4>Delivery</h4>
            <div class="sig-line">
                <div class="sig-label">Delivered by:</div>
                <div class="sig-line-box"></div>
            </div>
            <div class="sig-line">
                <div class="sig-label">Plate No.:</div>
                <div class="sig-line-box"></div>
            </div>
            <div class="sig-line">
                <div class="sig-label">Noted by:</div>
                <div class="sig-line-box"></div>
            </div>
        </div>

        <!-- Right: Customer acknowledgement -->
        <div class="sig-column">
            <div class="ack-block">
                <h4>Customer Acknowledgement</h4>
                <div class="ack-text">
                    Received the above items in good order and condition.
                </div>
                <div class="ack-signature">
                    <div class="sig-label">Received by:</div>
                    <div class="sig-line-box"></div>
                </div>
                <div class="ack-signature" style="margin-top: 14px;">
                    <div class="sig-label">Signature over Printed Name:</div>
                    <div class="sig-line-box"></div>
                </div>
                <div class="ack-signature" style="margin-top: 14px;">
                    <div class="sig-label">Date Received:</div>
                    <div class="sig-line-box"></div>
                </div>
            </div>
        </div>
    </div>

</div>

</body>
</html>
