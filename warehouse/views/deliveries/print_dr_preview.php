<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Receipt - <?= htmlspecialchars($dr_number) ?></title>
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

        /* ─── Page Container ─── */
        .dr-page {
            width: 8.5in;
            height: 11in;
            position: relative;
            overflow: hidden;
            background: #fff;
            margin: 10px auto;
            font-family: Calibri, sans-serif;
        }

        /* ─── HEADER FIELDS ─── */

        .dr-field-delivered-to {
            position: absolute;
            top: 2.37in;
            left: 1.70in;
            width: 4.00in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: left;
        }

        .dr-field-date {
            position: absolute;
            top: 2.38in;
            right: .50in;
            width: 2.00in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: left;
        }

        .dr-field-address {
            position: absolute;
            top: 2.60in;
            left: 1.70in;
            width: 5.50in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: left;
        }

        .dr-field-tin {
            position: absolute;
            top: 2.85in;
            left: 1.70in;
            width: 3.00in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: left;
        }

        .dr-field-terms {
            position: absolute;
            top: 2.85in;
            left: 4.65in;
            width: 1.50in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: left;
        }

        .dr-field-po-number {
            position: absolute;
            top: 2.85in;
            right: .45in;
            width: 2.00in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: left;
        }

        /* ─── TABLE BODY START ─── */
        .dr-table-start {
            position: absolute;
            top: 3.60in;
            left: 0.50in;
            right: 0.50in;
        }

        /* ─── TABLE ROW (repeated per lot) ─── */
        .dr-row {
            display: flex;
            align-items: flex-start;
            height: 0.35in;
            border-bottom: 1px solid transparent;
        }

        .dr-col-qty {
            width: 1.00in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: center;
            padding: 0 4px;
            flex-shrink: 0;
        }

        .dr-col-unit {
            width: .85in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: center;
            padding: 0 4px;
            flex-shrink: 0;
        }

        .dr-col-desc {
            flex: 1;
            margin-left: .35in;
            font-family: Calibri, sans-serif;
            font-size: 11pt;
            text-align: left;
            padding: 0 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: clip;
        }

        /* ─── Print Styles ─── */
        @media print {
            @page { size: 8.5in 11in; margin: 0 !important; }
            html, body { padding: 0 !important; margin: 0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; background: #fff; }
            .no-print { display: none !important; }
            .dr-page { margin: 0 !important; box-shadow: none; background: #fff; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <a href="?controller=warehouse&action=deliveries" class="btn btn-secondary" style="margin-right: 10px;"><i class="bi bi-arrow-left me-1"></i> Back</a>
    <button onclick="window.print()"><i class="bi bi-printer"></i> Print Delivery Receipt</button>
</div>

<div class="dr-page">

    <!-- HEADER FIELDS -->
    <div class="dr-field-delivered-to"><?= htmlspecialchars($po['customer_name'] ?? '') ?></div>
    <div class="dr-field-date"><?= !empty($dr_deliveries[0]['delivery_date']) ? date('d-M-Y', strtotime($dr_deliveries[0]['delivery_date'])) : date('d-M-Y') ?></div>
    <div class="dr-field-address"><?= htmlspecialchars($po['customer_address'] ?? '') ?></div>
    <div class="dr-field-tin"><?= htmlspecialchars($po['customer_tin'] ?? '') ?></div>
    <div class="dr-field-terms"><?= htmlspecialchars($po['customer_terms'] ?? '') ?> DAYS</div>
    <div class="dr-field-po-number"><?= htmlspecialchars($po['customer_po_number'] ?? '') ?></div>

    <?php
    $allRemarks = [];
    foreach ($dr_deliveries as $dd) {
        $r = trim($dd['remarks'] ?? '');
        if ($r !== '' && !in_array($r, $allRemarks)) $allRemarks[] = $r;
    }
    ?>
    <?php if (!empty($allRemarks)): ?>
    <div style="margin-top: 8px; font-size: 11px;">
        <strong>Remarks:</strong> <?= htmlspecialchars(implode('; ', $allRemarks)) ?>
    </div>
    <?php endif; ?>

    <!-- TABLE BODY -->
    <div class="dr-table-start">
        <?php if (!empty($dr_deliveries)): ?>
            <?php foreach ($dr_deliveries as $d):
                $qty = $d['delivery_quantity'] ?? 0;
                $conv = $d['actual_uom_conversion'] ?? $d['uom_conversion'] ?? null;
                $itemUom = $d['item_uom'] ?? '';
                $cases = ($conv && $itemUom !== 'CS') ? floor($qty / $conv) : 0;

                $descParts = [];
                if (!empty($d['item_description'])) $descParts[] = $d['item_description'];
                if ($cases > 0) $descParts[] = $cases . ' CS';
                if (!empty($d['lot_number'])) $descParts[] = $d['lot_number'];
                $fullDesc = implode(' | ', $descParts);
            ?>
            <div class="dr-row">
                <div class="dr-col-qty"><?= number_format($qty) ?></div>
                <div class="dr-col-unit">Pcs</div>
                <div class="dr-col-desc"><?= htmlspecialchars($fullDesc) ?></div>
            </div>
            <?php endforeach; ?>
            <div class="dr-row" style="margin-top: 4px;">
                <div class="dr-col-qty"></div>
                <div class="dr-col-unit"></div>
                <div class="dr-col-desc" style="font-style: italic; font-weight: bold;">Nothing follows.</div>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
