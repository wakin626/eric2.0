<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Transfer Slip - <?= htmlspecialchars($sts_ref) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Consolas', 'Lucida Console', monospace; font-size: 7pt; font-weight: bold; color: #000; background: #e0e0e0; }
        .no-print { text-align: center; padding: 10px; }
        .no-print button {
            padding: 8px 24px; font-size: 12px; cursor: pointer;
            background: #0d6efd; color: #fff; border: none; border-radius: 5px;
        }
        .no-print button:hover { background: #0b5ed7; }

        .sts-page {
            width: 58mm; min-height: 297mm; background: #fff;
            margin: 10px auto; padding: 3mm 2mm 3mm 5mm;
            font-family: 'Consolas', 'Lucida Console', monospace;
            font-size: 8pt;
            font-weight: bold;
            line-height: 1.4;
        }

        .sts-header { text-align: center; margin-bottom: 4px; padding-bottom: 2px; }
        .sts-header .company { font-size: 9pt; font-weight: bold; }
        .sts-header h1 { font-size: 8pt; font-weight: bold; margin: 2px 0; }
        .sts-header .sts-datetime { font-size: 8pt; font-weight: bold; color: #000; }

        .sts-meta { margin-bottom: 6px; }
        .sts-meta p { margin-bottom: 1px; }
        .sts-meta strong { display: inline-block; min-width: 48px; }

        .sts-divider { border-top: 1px dashed #000; margin: 9px 0; }

        .sts-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; table-layout: fixed; word-wrap: break-word; overflow-wrap: break-word; }
        .sts-table th, .sts-table td { padding: 2px 1px; font-size: 7pt; font-weight: bold; word-wrap: break-word; overflow-wrap: break-word; }
        .sts-table th { font-weight: bold; text-align: center; border-bottom: 1px solid #000; }
        .sts-table td { text-align: left; }
        .sts-table .text-center { text-align: center; }

        .sts-details { margin-bottom: 6px; }
        .sts-details p { margin-bottom: 2px; }

        .sts-signatures { margin-top: 16px; }
        .sig-block { margin-bottom: 8px; width: 100%; }
        .sig-name { font-size: 7pt; font-weight: bold; margin-bottom: 1px; min-height: 10px; }
        .sig-line { border-top: 1px solid #000; padding-top: 2px; font-size: 7pt; font-weight: bold; }

        @media print {
            body { background: none; }
            .no-print { display: none !important; }
            .sts-page { margin: 0; box-shadow: none; border: none; width: 58mm; min-height: 297mm; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">Print STS</button>
</div>

<div class="sts-page">

    <div class="sts-header">
        <div class="company">Cianan Corp.</div>
        <h1>Stock Transfer Slip</h1>
        <div class="sts-datetime"><?= !empty($date_created) ? date('m/d/Y H:i:s', strtotime($date_created)) : '' ?></div>
    </div>

    <div class="sts-divider"></div>

    <div class="sts-meta">
        <p><strong>STS Ref:</strong> <?= htmlspecialchars($sts_ref) ?></p>
        <p><strong>Shift:</strong> <?= htmlspecialchars($entries[0]['shift'] ?? '') ?></p>
        <p><strong>Date:</strong> <?= !empty($date_created) ? date('m/d/Y', strtotime($date_created)) : '' ?></p>
        <p><strong>PO NO:</strong> <?= htmlspecialchars($po['customer_po_number'] ?: $po['po_number']) ?></p>
        <p><strong>Status:</strong> Finished Goods</p>
    </div>

    <div class="sts-divider"></div>

    <table class="sts-table">
        <thead>
            <tr>
                <th width="12%">QTY</th>
                <th width="12%">UNIT</th>
                <th width="22%">LOT NO</th>
                <th width="58%">ITEM DESCRIPTION</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry):
                $qty = $entry['added_quantity'] ?? 0;
                $conv = $entry['pcs_per_case'] ?? null;
            ?>
            <tr>
                <td class="text-center"><?= ($conv && $conv > 0) ? number_format($qty / $conv) : number_format($qty) ?></td>
                <td class="text-center"><?= ($conv && $conv > 0) ? 'cs' : 'pcs' ?></td>
                <td><?= htmlspecialchars($entry['lot_number'] ?? '') ?></td>
                <td><?= htmlspecialchars($entry['item_description'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php
    $rejectList = array_filter(array_unique(array_map(function($e) { return trim($e['reject_status'] ?? ''); }, $entries)));
    $remarksList = array_filter(array_unique(array_map(function($e) { return trim($e['sts_remarks'] ?? ''); }, $entries)));
    ?>
    <?php if (!empty($rejectList) || !empty($remarksList)): ?>
    <div class="sts-divider"></div>
    <div class="sts-details">
        <?php if (!empty($rejectList)): ?>
        <p><strong>Status:</strong> <?= htmlspecialchars(implode(', ', $rejectList)) ?></p>
        <?php endif; ?>
        <?php if (!empty($remarksList)): ?>
        <p><strong>Remarks:</strong> <?= htmlspecialchars(implode(', ', $remarksList)) ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="sts-divider"></div>

    <div class="sts-signatures">
        <div class="sig-block">
            <div class="sig-name"><?= htmlspecialchars($entries[0]['prepared_by_name'] ?? $prepared_by) ?></div>
            <div class="sig-line">Prepared by</div>
        </div>
        <div class="sig-block">
            <div class="sig-name"><?= htmlspecialchars($entries[0]['checked_by_name'] ?? '') ?></div>
            <div class="sig-line">Checked by</div>
        </div>
        <div class="sig-block">
            <div class="sig-name"><?= htmlspecialchars($entries[0]['received_by_name'] ?? '') ?></div>
            <div class="sig-line">Received by</div>
        </div>
    </div>

</div>

</body>
</html>
