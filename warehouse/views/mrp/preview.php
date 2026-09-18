<style>
.mrp-section { page-break-inside: avoid; margin-bottom: 20px; }
.mrp-fg-header { background: #e8ecef; padding: 8px 12px; border-radius: 4px; margin-bottom: 8px; font-size: 0.9rem; }
.mrp-fg-header strong { color: #1a1a2e; }
.mrp-meta { font-size: 0.8rem; color: #555; margin-top: 4px; }
.mrp-table th { background: #f0f0f0; font-size: 0.78rem; white-space: nowrap; }
.mrp-table td { font-size: 0.8rem; }
.mrp-table .num { text-align: right; font-variant-numeric: tabular-nums; }
.mrp-negative { color: #dc3545; font-weight: 700; }
.mrp-positive { color: #198754; font-weight: 700; }
.mrp-shortage-row { background: #fff8e1 !important; }
.mrp-remarks-lacking { color: #dc3545; font-weight: 700; }
.mrp-remarks-ok { color: #198754; }
.mrp-remarks-warn { color: #fd7e14; font-weight: 600; }
.mrp-consolidated { border: 2px solid #0d6efd; }
.mrp-consolidated .card-header { background: #0d6efd; color: #fff; }
.mrp-empty { text-align: center; padding: 40px; color: #888; }
.mrp-signature { margin-top: 30px; display: flex; justify-content: space-between; gap: 20px; }
.mrp-signature .sig-block { flex: 1; }
.mrp-signature .sig-line { border-top: 1px solid #333; margin-top: 35px; padding-top: 5px; font-size: 0.8rem; }
.mrp-signature .sig-role { font-size: 0.7rem; color: #666; margin-top: 2px; }
@media print {
    .no-print { display: none !important; }
    .main-wrapper { margin: 0 !important; padding: 10px !important; }
    .sidebar { display: none !important; }
    .mrp-section { page-break-inside: avoid; }
    body { font-size: 10px; }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h4 class="mb-0"><i class="bi bi-calculator me-2"></i>MRP Sheet - Customer PO Requirements</h4>
    <div class="d-flex gap-2">
        <?php if (!empty($poHeader)): ?>
        <?php if (!empty($hasExistingSnapshot)): ?>
            <span class="btn btn-success disabled">
                <i class="bi bi-check-circle me-1"></i>Snapshot Saved
            </span>
        <?php else: ?>
        <form method="POST" action="?controller=warehouse&action=saveMrpSnapshot" class="d-inline" id="saveMrpForm">
            <input type="hidden" name="po_id" value="<?= $selectedPO ?>">
            <input type="hidden" name="customer_id" value="<?= $selectedCustomer ?>">
            <button type="submit" class="btn btn-success" id="saveMrpBtn" onclick="return confirm('Save current MRP as snapshot?')">
                <i class="bi bi-save me-1"></i>Save Snapshot
            </button>
        </form>
        <script>
        document.getElementById('saveMrpForm')?.addEventListener('submit', function() {
            var btn = document.getElementById('saveMrpBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
        });
        </script>
        <?php endif; ?>
        <?php endif; ?>
        <a href="?controller=warehouse&action=mrpHistory<?= !empty($selectedPO) ? '&po_id=' . $selectedPO : '' ?>" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i>History
        </a>
        <?php if (!empty($poHeader)): ?>
        <a href="?controller=warehouse&action=mrpPDF&customer_id=<?= $selectedCustomer ?>&po_id=<?= $selectedPO ?>" class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>Print PDF
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="card data-card mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="controller" value="warehouse">
            <input type="hidden" name="action" value="mrp">
            <div class="col-md-4">
                <label class="form-label fw-bold">Select Customer</label>
                <select name="customer_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- All Customers --</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['customer_id'] ?>" <?= ($selectedCustomer == $c['customer_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['customer_code'] . ' - ' . $c['customer_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Select Open PO</label>
                <select name="po_id" class="form-select" onchange="this.form.submit()" <?= empty($openPOs) ? 'disabled' : '' ?>>
                    <option value="">-- Select PO --</option>
                    <?php foreach ($openPOs as $po): ?>
                    <option value="<?= $po['po_id'] ?>" <?= ($selectedPO == $po['po_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($po['customer_po_number'] . ' (' . date('Y-m-d', strtotime($po['customer_po_date'])) . ')') ?>
                        — <?= number_format($po['total_quantity']) ?> pcs
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($selectedCustomer) && !empty($selectedPO)): ?>
            <div class="col-md-2">
                <a href="?controller=warehouse&action=mrp&customer_id=<?= $selectedCustomer ?>" class="btn btn-outline-secondary w-100">Clear PO</a>
            </div>
            <?php endif; ?>
            <?php if (!empty($selectedCustomer) && empty($selectedPO)): ?>
            <div class="col-md-2">
                <a href="?controller=warehouse&action=mrp" class="btn btn-outline-secondary w-100">Clear All</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (empty($poHeader)): ?>
<div class="card data-card">
    <div class="mrp-empty">
        <i class="bi bi-calculator" style="font-size: 3rem; opacity: 0.3;"></i>
        <h5 class="mt-3">Select a Customer and PO to generate MRP</h5>
        <p class="text-muted">Choose a customer above, then select an open Purchase Order to view material requirements.</p>
    </div>
</div>

<?php else: ?>

<!-- PO Header Info -->
<div class="card data-card mb-4">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i>PO Details</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"><strong>Customer:</strong> <?= htmlspecialchars($poHeader['customer_name']) ?></div>
            <div class="col-md-3"><strong>PO#:</strong> <?= htmlspecialchars($poHeader['customer_po_number']) ?></div>
            <div class="col-md-3"><strong>Date:</strong> <?= $poHeader['customer_po_date'] ? date('Y-m-d', strtotime($poHeader['customer_po_date'])) : '-' ?></div>
            <div class="col-md-3"><strong>Prod Type:</strong> <?= ucfirst($poHeader['production_type']) ?></div>
        </div>
        <div class="row mt-2">
            <div class="col-md-3"><strong>Total Qty:</strong> <?= number_format($poHeader['total_quantity']) ?></div>
            <div class="col-md-3"><strong>Produced:</strong> <?= number_format($poHeader['produced_quantity']) ?></div>
            <div class="col-md-3"><strong>Delivered:</strong> <?= number_format($poHeader['delivered_quantity']) ?></div>
            <div class="col-md-3"><strong>Status:</strong> <span class="badge bg-info"><?= ucfirst($poHeader['status']) ?></span></div>
        </div>
    </div>
</div>

<?php if (empty($mrpSections)): ?>
<div class="card data-card">
    <div class="mrp-empty">
        <h5>No BOM found for items on this PO</h5>
        <p class="text-muted">Ensure BOMs are created for the finished goods on this Purchase Order.</p>
    </div>
</div>

<?php else: ?>

<!-- FG Items Summary -->
<div class="card data-card mb-4">
    <div class="card-header"><i class="bi bi-box-seam me-2"></i>FG Items on this PO</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>FG Code</th>
                    <th>Description</th>
                    <th>Target Qty</th>
                    <th>Batch Size</th>
                    <th>Batches Needed</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($mrpSections as $sec): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><code><?= htmlspecialchars($sec['fg_code']) ?></code></td>
                    <td><?= htmlspecialchars($sec['fg_name']) ?></td>
                    <td class="num"><?= number_format($sec['target_qty']) ?> <?= htmlspecialchars($sec['item_uom']) ?></td>
                    <td class="num"><?= number_format($sec['batch_qty'], 4) ?> <?= htmlspecialchars($sec['batch_uom']) ?></td>
                    <td class="num"><strong><?= number_format($sec['batches_needed'], 1) ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Per-FG MRP Sections -->
<?php foreach ($mrpSections as $sec): ?>
<div class="card data-card mb-4 mrp-section">
    <div class="card-header">
        <i class="bi bi-calculator me-2"></i>
        <strong><?= htmlspecialchars($sec['fg_code'] . ' - ' . $sec['fg_name']) ?></strong>
    </div>
    <div class="card-body">
        <div class="mrp-meta mb-3">
            Prod. Qty: <strong><?= number_format($sec['target_qty']) ?> <?= htmlspecialchars($sec['item_uom']) ?></strong>
            &nbsp;|&nbsp; Lot size: <strong><?= number_format($sec['batch_qty'], 4) ?> <?= htmlspecialchars($sec['batch_uom']) ?></strong>
            &nbsp;|&nbsp; No. of Batches: <strong><?= number_format($sec['batches_needed'], 1) ?></strong>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mrp-table mb-0">
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
                    <tr class="<?= $row['excess'] < 0 ? 'mrp-shortage-row' : '' ?>">
                        <td><code><?= htmlspecialchars($row['item_code']) ?></code></td>
                        <td><?= htmlspecialchars($row['item_description']) ?></td>
                        <td><?= htmlspecialchars($row['item_uom']) ?></td>
                        <td class="num"><?= number_format($row['total_reqt'], 2) ?></td>
                        <td class="num"><?= number_format($row['soh'], 2) ?></td>
                        <td class="num"><?= number_format($row['allocated'], 2) ?></td>
                        <td class="num"><?= number_format($row['pending'], 2) ?></td>
                        <td class="num"><?= ($row['supplier_pending'] ?? 0) > 0 ? '<span class="text-success">' . number_format($row['supplier_pending'], 2) . '</span>' : '0.00' ?></td>
                        <td class="num <?= $row['excess'] < 0 ? 'mrp-negative' : 'mrp-positive' ?>">
                            <?= $row['excess'] < 0 ? '(' . number_format(abs($row['excess']), 2) . ')' : number_format($row['excess'], 2) ?>
                        </td>
                        <td><?= date('m/d/Y') ?></td>
                        <td class="mrp-remarks-<?= strtolower(str_replace([' ', '/'], ['-', '-'], $row['remarks'])) ?>">
                            <?= htmlspecialchars($row['remarks']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Consolidated Summary -->
<div class="card data-card mb-4 mrp-consolidated">
    <div class="card-header"><i class="bi bi-table me-2"></i>CONSOLIDATED SUMMARY (All Ingredients)</div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mrp-table mb-0">
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
                <tr class="<?= $row['excess'] < 0 ? 'mrp-shortage-row' : '' ?>">
                    <td><code><?= htmlspecialchars($row['item_code']) ?></code></td>
                    <td><?= htmlspecialchars($row['item_description']) ?></td>
                    <td><?= htmlspecialchars($row['item_uom']) ?></td>
                    <td class="num"><?= number_format($row['total_reqt'], 2) ?></td>
                    <td class="num"><?= number_format($row['soh'], 2) ?></td>
                    <td class="num"><?= number_format($row['allocated'], 2) ?></td>
                    <td class="num"><?= number_format($row['pending'], 2) ?></td>
                    <td class="num"><?= ($row['supplier_pending'] ?? 0) > 0 ? '<span class="text-success">' . number_format($row['supplier_pending'], 2) . '</span>' : '0.00' ?></td>
                    <td class="num <?= $row['excess'] < 0 ? 'mrp-negative' : 'mrp-positive' ?>">
                        <?= $row['excess'] < 0 ? '(' . number_format(abs($row['excess']), 2) . ')' : number_format($row['excess'], 2) ?>
                    </td>
                    <td class="mrp-remarks-<?= strtolower(str_replace([' ', '/'], ['-', '-'], $row['remarks'])) ?>">
                        <?= htmlspecialchars($row['remarks']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Signature Blocks -->
<div class="card data-card no-print">
    <div class="card-body">
        <div class="mrp-signature">
            <div class="sig-block">
                <div class="sig-line">Prepared By: <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
                <div class="sig-role"><?= htmlspecialchars(ucfirst($_SESSION['department'] ?? '')) ?></div>
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
    </div>
</div>

<?php endif; ?>
<?php endif; ?>
