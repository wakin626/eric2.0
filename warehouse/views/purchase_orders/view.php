<div class="card data-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-eye me-2"></i>PO Details - <?= $po['customer_po_number'] ?></span>
        <a href="?controller=warehouse&action=purchaseOrders" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-4">
                <p class="mb-1"><strong>Customer Code:</strong></p>
                <p class="text-muted"><?= htmlspecialchars($po['customer_code'] ?? '-') ?></p>
            </div>
            <div class="col-md-4">
                <p class="mb-1"><strong>Customer Name:</strong></p>
                <p class="text-muted"><?= htmlspecialchars($po['customer_name'] ?? '-') ?></p>
            </div>
            <div class="col-md-4">
<p class="mb-1"><strong>Status:</strong></p>
<span class="badge bg-info"><?= $po['status'] ?? 'Active' ?></span>
            </div>
        </div>
        <h5 class="mb-3">Items</h5>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>Description</th>
                        <th>UOM</th>
                        <th>Quantity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($po_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['item_code']) ?></td>
                        <td><?= htmlspecialchars($item['item_description']) ?></td>
                        <td><?= htmlspecialchars($item['item_uom']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($po_items)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">No items found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>