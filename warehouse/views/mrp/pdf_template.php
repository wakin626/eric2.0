<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
@page { size: A4 landscape; margin: 12mm 15mm; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 9px; color: #000; margin: 0; padding: 0; }
.company-header { border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 10px; overflow: hidden; }
.company-header .logo-area { float: left; width: 80px; height: 50px; border: 1px dashed #ccc; text-align: center; line-height: 50px; font-size: 7px; color: #999; }
.company-header .company-info { margin-left: 10px; float: left; }
.company-header .company-name { font-size: 15px; font-weight: bold; color: #1a1a2e; }
.company-header .company-address { font-size: 8px; color: #555; margin-top: 2px; }
.company-header .po-info { float: right; text-align: right; font-size: 9px; }
.mrp-title { font-size: 13px; font-weight: bold; text-align: center; margin: 6px 0 2px 0; text-transform: uppercase; letter-spacing: 0.5px; }
.po-subtitle { text-align: center; font-size: 9px; margin-bottom: 8px; color: #333; }
.fg-section { margin-bottom: 12px; }
.fg-header { background: #e0e0e0; padding: 4px 8px; font-weight: bold; font-size: 9px; margin-bottom: 4px; border: 1px solid #999; }
.fg-meta { font-size: 8px; color: #444; margin-bottom: 4px; padding-left: 8px; }
table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
th { background: #f0f0f0; border: 1px solid #999; padding: 3px 4px; font-size: 8px; font-weight: bold; text-align: left; white-space: nowrap; }
td { border: 1px solid #ccc; padding: 2px 4px; font-size: 8px; }
.num { text-align: right; }
.negative { color: #cc0000; font-weight: bold; }
.shortage-row { background: #fffde7; }
.remarks-lacking { color: #cc0000; font-weight: bold; }
.remarks-ok { color: #006600; }
.remarks-warn { color: #cc6600; font-weight: bold; }
.consolidated-header { background: #0d6efd; color: #fff; font-weight: bold; }
.consolidated-header th { background: #0d6efd; color: #fff; }
.signature-blocks { margin-top: 20px; overflow: hidden; }
.sig-block { float: left; width: 30%; margin-right: 3%; }
.sig-block:last-child { margin-right: 0; }
.sig-line { border-top: 1px solid #333; margin-top: 30px; padding-top: 3px; font-size: 8px; }
.sig-role { font-size: 7px; color: #555; margin-top: 1px; }
.page-break { page-break-before: always; }
</style>
</head>
<body>

<!-- Company Header -->
<div class="company-header">
    <div class="logo-area">LOGO</div>
    <div class="company-info">
        <div class="company-name">[Company Name]</div>
        <div class="company-address">[Company Address Line 1]</div>
        <div class="company-address">[City, Province, ZIP]</div>
    </div>
    <div class="po-info">
        <strong>MRP for:</strong> <?= htmlspecialchars($po['customer_po_number'] ?? '') ?><br>
        <strong>Date:</strong> <?= date('m/d/Y') ?><br>
        <strong>As of:</strong> <?= date('M d, Y 5pm') ?>
    </div>
</div>

<!-- MRP Title -->
<div class="mrp-title">MRP for: <?= htmlspecialchars($po['customer_po_number'] ?? '') ?></div>
<div class="po-subtitle">
    <?= htmlspecialchars($po['customer_name'] ?? '') ?>
    | <?= strtoupper($po['production_type'] ?? '') ?> PRODUCTION
</div>

<?php foreach ($mrpSections as $sec): ?>
<div class="fg-section">
    <div class="fg-header">
        <?= htmlspecialchars($sec['fg_code'] . ' - ' . $sec['fg_name']) ?>
        &nbsp;&nbsp;|&nbsp;&nbsp; Prod. Qty: <?= number_format($sec['target_qty']) . ' ' . htmlspecialchars($sec['item_uom']) ?>
        &nbsp;&nbsp;|&nbsp;&nbsp; Lot size: <?= number_format($sec['batch_qty'], 4) . ' ' . htmlspecialchars($sec['batch_uom']) ?>
        &nbsp;&nbsp;|&nbsp;&nbsp; Batches: <?= number_format($sec['batches_needed'], 1) ?>
    </div>
    <table>
        <thead>
            <tr>
                <th>Item Code</th>
                <th>Trade Name / Description</th>
                <th>UoM</th>
                <th class="num">Total Reqt</th>
                <th class="num">SOH</th>
                <th class="num">Allocated</th>
                <th class="num">Pending PO/RR</th>
                <th class="num">Supplier</th>
                <th class="num">EXCESS / (LACKING)</th>
                <th>Date</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sec['components'] as $row): ?>
            <tr class="<?= $row['excess'] < 0 ? 'shortage-row' : '' ?>">
                <td><?= htmlspecialchars($row['item_code']) ?></td>
                <td><?= htmlspecialchars($row['item_description']) ?></td>
                <td><?= htmlspecialchars($row['item_uom']) ?></td>
                <td class="num"><?= number_format($row['total_reqt'], 2) ?></td>
                <td class="num"><?= number_format($row['soh'], 2) ?></td>
                <td class="num"><?= number_format($row['allocated'], 2) ?></td>
                <td class="num"><?= number_format($row['pending'], 2) ?></td>
                <td class="num"><?= ($row['supplier_pending'] ?? 0) > 0 ? number_format($row['supplier_pending'], 2) : '0.00' ?></td>
                <td class="num <?= $row['excess'] < 0 ? 'negative' : '' ?>">
                    <?= $row['excess'] < 0 ? '(' . number_format(abs($row['excess']), 2) . ')' : number_format($row['excess'], 2) ?>
                </td>
                <td><?= date('m/d/Y') ?></td>
                <td class="remarks-<?= strtolower(str_replace([' ', '/'], ['-', '-'], $row['remarks'])) ?>">
                    <?= htmlspecialchars($row['remarks']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

<!-- Consolidated Summary -->
<?php if (!empty($consolidated)): ?>
<div class="fg-section">
    <div class="fg-header consolidated-header">CONSOLIDATED SUMMARY (All Ingredients)</div>
    <table>
        <thead>
            <tr>
                <th>Item Code</th>
                <th>Trade Name / Description</th>
                <th>UoM</th>
                <th class="num">Total Reqt</th>
                <th class="num">SOH</th>
                <th class="num">Allocated</th>
                <th class="num">Pending PO/RR</th>
                <th class="num">Supplier</th>
                <th class="num">EXCESS / (LACKING)</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($consolidated as $row): ?>
            <tr class="<?= $row['excess'] < 0 ? 'shortage-row' : '' ?>">
                <td><?= htmlspecialchars($row['item_code']) ?></td>
                <td><?= htmlspecialchars($row['item_description']) ?></td>
                <td><?= htmlspecialchars($row['item_uom']) ?></td>
                <td class="num"><?= number_format($row['total_reqt'], 2) ?></td>
                <td class="num"><?= number_format($row['soh'], 2) ?></td>
                <td class="num"><?= number_format($row['allocated'], 2) ?></td>
                <td class="num"><?= number_format($row['pending'], 2) ?></td>
                <td class="num"><?= ($row['supplier_pending'] ?? 0) > 0 ? number_format($row['supplier_pending'], 2) : '0.00' ?></td>
                <td class="num <?= $row['excess'] < 0 ? 'negative' : '' ?>">
                    <?= $row['excess'] < 0 ? '(' . number_format(abs($row['excess']), 2) . ')' : number_format($row['excess'], 2) ?>
                </td>
                <td class="remarks-<?= strtolower(str_replace([' ', '/'], ['-', '-'], $row['remarks'])) ?>">
                    <?= htmlspecialchars($row['remarks']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Signature Blocks -->
<div class="signature-blocks">
    <div class="sig-block">
        <div class="sig-line">Prepared By: <?= htmlspecialchars($userName) ?></div>
        <div class="sig-role"><?= htmlspecialchars(ucfirst($userDept)) ?></div>
    </div>
    <div class="sig-block">
        <div class="sig-line">Reviewed By: _______________</div>
        <div class="sig-role">&nbsp;</div>
    </div>
    <div class="sig-block">
        <div class="sig-line">Approved By: _______________</div>
        <div class="sig-role">&nbsp;</div>
    </div>
</div>

</body>
</html>
