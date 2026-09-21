<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1">LMR Print</h4>
                <small class="text-muted">Manufacturing Order Release</small>
            </div>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted mb-1">MO Number</div>
                    <strong><?= htmlspecialchars($mo['mo_number'] ?? '-') ?></strong>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <div class="small text-muted mb-1">Customer</div>
                    <strong><?= htmlspecialchars($mo['customer_name'] ?? $mo['customer_code'] ?? '-') ?></strong>
                </div>
            </div>
        </div>

        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th>Qty Ordered</th>
                    <th>UOM</th>
                    <th>BOM Code</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No items attached to this MO.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['item_code'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($item['item_description'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($item['qty_ordered'] ?? 0) ?></td>
                            <td><?= htmlspecialchars($item['uom'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($item['bom_code'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
