<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card data-card">
            <div class="card-header"><i class="bi bi-box-arrow-in-down me-2"></i>Receive Procurement PO</div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Supplier:</strong> <?= htmlspecialchars($order['supplier_name']) ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Item:</strong> <code><?= htmlspecialchars($order['item_code']) ?></code> - <?= htmlspecialchars($order['item_description']) ?>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Ordered Qty:</strong> <?= number_format($order['quantity'], 4) ?> <?= htmlspecialchars($order['item_uom']) ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Order Date:</strong> <?= $order['order_date'] ? date('m/d/Y', strtotime($order['order_date'])) : '-' ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Expected:</strong> <?= $order['expected_date'] ? date('m/d/Y', strtotime($order['expected_date'])) : '-' ?>
                    </div>
                </div>
                <?php if ($order['remarks']): ?>
                <div class="mb-3"><strong>Remarks:</strong> <?= htmlspecialchars($order['remarks']) ?></div>
                <?php endif; ?>

                <hr>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Received Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="received_qty" class="form-control" min="0.01" step="0.0001"
                               value="<?= $order['quantity'] ?>" required>
                        <small class="text-muted">Enter the quantity actually received (may differ from ordered).</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Confirm Receipt</button>
                        <a href="?controller=warehouse&action=supplierOrders" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
