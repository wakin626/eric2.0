<?php
/**
 * Printable Line Material Requisition (LMR) — Lot Manufacturing Record.
 * Clean minimalist paper-form layout (no grid borders / no barcode).
 * Standalone document (no layout wrapper). Print via browser (@media print).
 *
 * Expects: $mo, $items, $breakdown, $documentId
 */

$mo = $mo ?? [];
$items = $items ?? [];
$breakdown = $breakdown ?? [];
$documentId = $documentId ?? ('LMR-' . ($mo['mo_number'] ?? ''));

// First non-empty SO number across MO lines
$soNumber = '';
foreach ($items as $lineItem) {
    if (!empty($lineItem['so_number'])) {
        $soNumber = $lineItem['so_number'];
        break;
    }
}

// Formulation: first FG line (item_code / bom_code), with multi-line indicator
$formulation = '';
if (!empty($items)) {
    $first = $items[0];
    $formulation = trim(($first['item_code'] ?? '') . ' / ' . ($first['bom_code'] ?? ''), ' /');
    $extra = count($items) - 1;
    if ($extra > 0) {
        $formulation .= ' (+' . $extra . ' more)';
    }
}

// Consolidate BOM components across FG lines (sum required qty per item)
$components = [];
foreach ($breakdown as $line) {
    foreach (($line['components'] ?? []) as $comp) {
        $code = $comp['item_code'] ?? '';
        if ($code === '') {
            continue;
        }
        if (!isset($components[$code])) {
            $components[$code] = [
                'item_code' => $code,
                'item_description' => $comp['item_description'] ?? '',
                'item_uom' => $comp['item_uom'] ?? '',
                'phase_code' => (string) ($comp['phase_code'] ?? '101'),
                'required_qty' => 0.0,
            ];
        }
        $components[$code]['required_qty'] += floatval($comp['required_qty'] ?? 0);
    }
}
// Group ingredients by phase code for LMR procedure step numbering
usort($components, function ($a, $b) {
    return strcmp($a['phase_code'], $b['phase_code']);
});
$components = array_values($components);
$totalRawMaterial = array_sum(array_column($components, 'required_qty'));

$batchLot = !empty($mo['batch_lot_no']) ? $mo['batch_lot_no'] : '';
$soDisplay = $soNumber !== '' ? $soNumber : '';
$batchDisplay = $batchLot;

// Underline field helper: filled value or blank rule
$uf = function ($val) {
    $val = trim((string) $val);
    if ($val === '') {
        return '<span class="underline-field">&nbsp;</span>';
    }
    return '<span class="underline-field">' . htmlspecialchars($val) . '</span>';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LMR - <?= htmlspecialchars($mo['mo_number'] ?? '') ?></title>
<style>
    /* ── Base (screen preview mirrors print structure) ──────── */
    * { box-sizing: border-box; }
    body {
        font-family: Arial, sans-serif;
        font-size: 11px;
        color: #000;
        background: #525659;
        margin: 0;
        padding: 20px 0;
    }
    .sheet {
        background: #fff;
        width: 8.5in;
        min-height: 11in;
        margin: 0 auto;
        padding: 15mm 12mm;
        box-shadow: 0 4px 18px rgba(0,0,0,0.45);
    }

    /* ── Toolbar ────────────────────────────────────────────── */
    .toolbar {
        width: 8.5in;
        margin: 0 auto 14px;
        display: flex;
        justify-content: space-between;
        gap: 8px;
    }
    .toolbar a, .toolbar button {
        font-family: Arial, sans-serif;
        font-size: 12px;
        padding: 7px 16px;
        border: 1px solid #fff;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
    }
    .toolbar a { background: #fff; color: #000; }
    .toolbar button { background: #0d6efd; color: #fff; }

    /* ── Document header (text-only, top-right) ─────────────── */
    .doc-top {
        display: flex;
        justify-content: flex-end;
        text-align: right;
        margin-bottom: 6px;
    }
    .page-indicator { font-size: 11px; }
    .doc-ref { font-size: 11px; margin-top: 2px; }
    .doc-title {
        text-align: center;
        font-size: 15px;
        font-weight: bold;
        letter-spacing: 0.5px;
        margin: 4px 0 12px;
        text-transform: uppercase;
    }

    /* ── Header meta: two-column text + underline inputs ────── */
    .meta-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
    }
    .meta-table td {
        border: none !important;
        padding: 3px 5px;
        vertical-align: bottom;
    }
    .meta-label { white-space: nowrap; width: 16%; }
    .meta-value { width: 34%; }
    .underline-field {
        border-bottom: 1px solid #000 !important;
        display: inline-block;
        min-width: 120px;
        padding-bottom: 1px;
    }

    /* ── Inline MO summary: no boxed borders ────────────────── */
    .summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
    }
    .summary-table th,
    .summary-table td {
        border: none !important;
        padding: 3px 5px;
        vertical-align: middle;
        text-align: left;
    }
    .summary-table thead th { font-weight: bold; }

    /* ── Items table: header rules only (no grid) ───────────── */
    .items-table {
        width: 100%;
        border-collapse: collapse;
    }
    .items-table th,
    .items-table td {
        border: none !important;
        padding: 3px 5px;
        vertical-align: middle;
        text-align: left;
    }
    .items-table thead th {
        border-top: 1px solid #000 !important;
        border-bottom: 1px solid #000 !important;
        font-weight: bold;
    }
    .items-table tfoot td {
        border-top: 1px solid #000 !important;
        font-weight: bold;
    }
    .num { text-align: right !important; }

    .lot-subrow td:first-child { padding-left: 20px; }

    .phase-group-row td {
        font-weight: bold;
        border-top: 1px solid #000 !important;
        padding-top: 6px;
    }

    .empty-note {
        text-align: center;
        color: #333;
        font-style: italic;
        padding: 8px 5px;
    }

    /* ── Footer signatures: minimalist lines ────────────────── */
    .footer-signatures {
        margin-top: 50px;
        display: flex;
        justify-content: space-between;
    }
    .sig-block { width: 40%; }
    .sig-line {
        border-top: 1px solid #000;
        padding-top: 4px;
        min-height: 18px;
    }
    .sig-label {
        text-align: center;
        font-size: 11px;
        margin-top: 3px;
    }

    /* ── Print rules (per document spec) ────────────────────── */
    @media print {
        @page {
            size: letter portrait;
            margin: 15mm 12mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
            padding: 0;
        }
        .no-print { display: none !important; }
        .sheet {
            width: auto;
            min-height: auto;
            margin: 0;
            padding: 0;
            box-shadow: none;
        }

        /* Remove all default borders */
        table, th, td {
            border: none !important;
            padding: 3px 5px;
        }

        /* Header table lines only */
        .items-table th {
            border-top: 1px solid #000 !important;
            border-bottom: 1px solid #000 !important;
            font-weight: bold;
        }

        .items-table tfoot td {
            border-top: 1px solid #000 !important;
            font-weight: bold;
        }

        .underline-field {
            border-bottom: 1px solid #000 !important;
            display: inline-block;
            min-width: 120px;
        }
    }
</style>
</head>
<body>

<div class="toolbar no-print">
    <a href="?controller=mo&action=view&id=<?= (int) ($mo['mo_id'] ?? 0) ?>">&larr; Back to MO</a>
    <button type="button" onclick="window.print()">🖨 Print</button>
</div>

<div class="sheet">

    <!-- 1. Top Header Metadata -->
    <div class="doc-top">
        <div>
            <div class="page-indicator">Page 1 of 1</div>
            <div class="doc-ref">Document Ref ID: <?= htmlspecialchars($documentId) ?></div>
        </div>
    </div>

    <div class="doc-title">Lot Manufacturing Record</div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Site:</td>
            <td class="meta-value"><?= $uf($mo['mo_site'] ?? '') ?></td>
            <td class="meta-label">Formulation:</td>
            <td class="meta-value"><?= $uf($formulation) ?></td>
        </tr>
        <tr>
            <td class="meta-label">Customer:</td>
            <td class="meta-value"><?= $uf(($mo['customer_name'] ?? '') ?: ($mo['customer_code'] ?? '')) ?></td>
            <td class="meta-label">Supersedes:</td>
            <td class="meta-value"><?= $uf('') ?></td>
        </tr>
        <tr>
            <td class="meta-label">Customer PO No.:</td>
            <td class="meta-value"><?= $uf($mo['po_number'] ?? '') ?></td>
            <td class="meta-label">LMR Rev. No.:</td>
            <td class="meta-value"><?= $uf('') ?></td>
        </tr>
        <tr>
            <td class="meta-label">Date Started:</td>
            <td class="meta-value"><?= $uf('') ?></td>
            <td class="meta-label">Job No.:</td>
            <td class="meta-value"><?= $uf('') ?></td>
        </tr>
        <tr>
            <td class="meta-label">Date Finished:</td>
            <td class="meta-value"><?= $uf('') ?></td>
            <td class="meta-label">SO Number:</td>
            <td class="meta-value"><?= $uf($soDisplay) ?></td>
        </tr>
        <tr>
            <td class="meta-label">Expiry Date:</td>
            <td class="meta-value"><?= $uf('') ?></td>
            <td class="meta-label">Batch/Lot Series:</td>
            <td class="meta-value"><?= $uf($batchDisplay) ?></td>
        </tr>
    </table>

    <!-- 2. Manufacturing Order Line Summary (inline, no boxed borders) -->
    <table class="summary-table">
        <thead>
            <tr>
                <th>MO Number</th>
                <th>Item Number</th>
                <th>Description</th>
                <th>Qty Ordered</th>
                <th>Lot No.</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="5" class="empty-note">No items attached to this MO.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $lineItem): ?>
                    <tr>
                        <td><?= htmlspecialchars($mo['mo_number'] ?? '') ?></td>
                        <td><?= htmlspecialchars($lineItem['item_code'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($lineItem['item_description'] ?? '-') ?></td>
                        <td><?= number_format(floatval($lineItem['qty_ordered'] ?? 0), 3) ?></td>
                        <td><?= $batchLot !== '' ? htmlspecialchars($batchLot) : '<span class="underline-field">&nbsp;</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- 3. Raw Materials & Component List (header rules only) -->
    <table class="items-table">
        <thead>
            <tr>
                <th>Phase</th>
                <th>Item Number</th>
                <th>Description</th>
                <th class="num">Qty</th>
                <th>U/M</th>
                <th>Actual Qty</th>
                <th>Issued By</th>
                <th>Returned</th>
                <th>Additional</th>
                <th>Checked By</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($components)): ?>
                <tr>
                    <td colspan="10" class="empty-note">No BOM components available for this MO.</td>
                </tr>
            <?php else: ?>
                <?php $currentPhase = null; foreach ($components as $comp): ?>
                    <?php if ($comp['phase_code'] !== $currentPhase): $currentPhase = $comp['phase_code']; ?>
                    <tr class="phase-group-row">
                        <td colspan="10"><strong>PHASE <?= htmlspecialchars($currentPhase) ?></strong> &mdash; mix / handle all items in this phase</td>
                    </tr>
                    <?php endif; ?>
                    <!-- Main Item Row -->
                    <tr>
                        <td><?= htmlspecialchars($comp['phase_code']) ?></td>
                        <td><?= htmlspecialchars($comp['item_code']) ?></td>
                        <td><?= htmlspecialchars($comp['item_description']) ?></td>
                        <td class="num"><?= number_format($comp['required_qty'], 3) ?></td>
                        <td><?= htmlspecialchars($comp['item_uom']) ?></td>
                        <td>_______</td>
                        <td>_______</td>
                        <td>_______</td>
                        <td>_______</td>
                        <td>_______</td>
                    </tr>
                    <!-- Lot No. sub-row -->
                    <tr class="lot-subrow">
                        <td colspan="3">
                            Lot No.: ____________________
                        </td>
                        <td colspan="7"></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (!empty($components)): ?>
        <tfoot>
            <tr>
                <td colspan="3">Total Raw Material:</td>
                <td class="num"><?= number_format($totalRawMaterial, 3) ?></td>
                <td colspan="6"></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <!-- 4. Footer Signatures -->
    <div class="footer-signatures">
        <div class="sig-block">
            <div class="sig-line">&nbsp;</div>
            <div class="sig-label">Prepared By</div>
        </div>
        <div class="sig-block">
            <div class="sig-line">&nbsp;</div>
            <div class="sig-label">Noted By</div>
        </div>
    </div>

</div>

</body>
</html>
