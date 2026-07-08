<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?? 'Print' ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 20px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @page { size: A4 landscape; margin: 15mm 15mm 20mm 15mm; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 18px; margin-bottom: 5px; }
        .header p { font-size: 11px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; font-size: 11px; }
        th { background: #f0f0f0; font-weight: bold; }
        tr:nth-child(even) { background: #fafafa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
        @page { @bottom-center { content: "Page " counter(page) " of " counter(pages); } }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:right; margin-bottom:10px;">
        <button onclick="window.print()" style="padding:6px 16px; cursor:pointer; background:#007bff; color:#fff; border:none; border-radius:4px;">Print / Save as PDF</button>
    </div>
    <div class="header" style="display:flex; align-items:center; gap:15px; text-align:left;">
        <img src="public/images/logo.png" alt="Logo" style="height:60px;">
        <div>
            <h1 style="font-size:16px; margin-bottom:2px;"><?= $pageTitle ?? 'Price List' ?></h1>
            <p style="font-size:10px; color:#555; margin:0;">Blk 7 Lot 7 Springbook St. Sterling Technopark Brgy. Maguyam, Silang, Cavite</p>
            <p style="font-size:10px; color:#888; margin:0;">Generated: <?= date('F d, Y h:i A') ?> | Total: <?= $total ?? 0 ?> records
            <?php if (!empty($search)): ?>
                | Search: "<?= htmlspecialchars($search) ?>"
            <?php endif; ?>
            <?php if (($filterStatus ?? '') !== ''): ?>
                | Status: <?= $filterStatus == '1' ? 'Active' : 'Inactive' ?>
            <?php endif; ?>
            <?php if (($filterCustomer ?? '') !== ''): ?>
                | Customer: <?= htmlspecialchars($filterCustomer) ?>
            <?php endif; ?>
            </p>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Product Name</th>
                <th>Customer</th>
                <th>Item Code</th>
                <th>Net/Size</th>
                <th class="text-right">Price/Piece</th>
                <th class="text-right">Price/Pack</th>
                <th class="text-right">Price/Case</th>
                <th class="text-center">VAT Type</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; foreach ($items as $item): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td><?= htmlspecialchars($item['customer_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($item['item_code'] ?? '-') ?></td>
                <td><?= htmlspecialchars($item['net_size'] ?? '-') ?></td>
                <td class="text-right">₱<?= number_format($item['price_per_piece'] ?? 0, 2) ?></td>
                <td class="text-right">₱<?= number_format($item['price_per_pack'], 2) ?></td>
                <td class="text-right">₱<?= number_format($item['price_per_case'], 2) ?></td>
                <td class="text-center"><?= $item['vat_type'] === 'vat' ? 'VAT' : 'Non-VAT' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
