<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-box-arrow-in-down me-2"></i>Receiving Purchasing PO (View Only)</h4>
    <div class="d-flex gap-2 flex-wrap">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="controller" value="admin">
            <input type="hidden" name="action" value="receivingPo">
            <select name="status" class="form-select form-select-sm" style="width:180px">
                <option value="">All Status</option>
                <option value="processed" <?= ($filters['status'] ?? '') === 'processed' ? 'selected' : '' ?>>Processed</option>
                <option value="partially_received" <?= ($filters['status'] ?? '') === 'partially_received' ? 'selected' : '' ?>>Partially Received</option>
                <option value="received" <?= ($filters['status'] ?? '') === 'received' ? 'selected' : '' ?>>Received</option>
            </select>
            <select name="supplier" class="form-select form-select-sm" style="width:200px">
                <option value="">All Suppliers</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>" <?= ($filters['supplier'] ?? '') === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search PO Ref, Item..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>" style="width:220px">
            <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
            <a href="?controller=admin&action=receivingPo" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
        </form>
    </div>
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>PO Ref</th>
                    <th>Supplier</th>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th class="text-end">Qty Ordered</th>
                    <th class="text-end">Received Qty</th>
                    <th>Received Date</th>
                    <th>Status</th>
                    <th>Access</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">No purchasing POs ready for receiving.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><?= $o['supplier_order_id'] ?></td>
                    <td><?= $o['customer_po_number'] ?? ($o['po_id'] ? 'PO #' . $o['po_id'] : 'SO #' . $o['supplier_order_id']) ?></td>
                    <td><?= htmlspecialchars($o['supplier_name']) ?></td>
                    <td><code><?= htmlspecialchars($o['item_code']) ?></code></td>
                    <td><?= htmlspecialchars($o['item_description']) ?></td>
                    <td class="text-end"><?= number_format($o['quantity'], 4) ?></td>
                    <td class="text-end"><?= $o['received_qty'] > 0 ? number_format($o['received_qty'], 4) : '<span class="text-muted">-</span>' ?></td>
                    <td><?= $o['received_date'] ? date('m/d/Y', strtotime($o['received_date'])) : '-' ?></td>
                    <td>
                        <?php if ($o['status'] === 'processed'): ?>
                            <span class="badge bg-primary">Processed</span>
                        <?php elseif ($o['status'] === 'partially_received'): ?>
                            <span class="badge bg-info text-dark">Partially Received</span>
                        <?php elseif ($o['status'] === 'received'): ?>
                            <span class="badge bg-success">Received</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= ucfirst($o['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-secondary-subtle text-secondary">Read-only</span></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
