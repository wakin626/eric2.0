<?php if (!$delivery): ?>
<div class="alert alert-danger">Delivery not found.</div>
<?php else: ?>

<div class="mb-3">
    <a href="?controller=finance&action=deliveries" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Deliveries
    </a>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card data-card mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>Delivery Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>PO Number:</strong> <?= htmlspecialchars($delivery['customer_po_number'] ?? '-') ?></p>
                        <p><strong>Customer:</strong> <?= htmlspecialchars($delivery['customer_name'] ?? '-') ?></p>
                        <p><strong>Customer Code:</strong> <?= htmlspecialchars($delivery['customer_code'] ?? '-') ?></p>
                        <p><strong>Customer Address:</strong> <?= htmlspecialchars($delivery['customer_address'] ?? '-') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Delivery Date:</strong> <?= date('F d, Y', strtotime($delivery['delivery_date'])) ?></p>
                        <p><strong>Delivery Quantity:</strong> <?= $delivery['delivery_quantity'] ?? 0 ?></p>
                        <p><strong>Delivered By:</strong> <?= htmlspecialchars($delivery['delivered_by_name'] ?? '-') ?></p>
                        <p><strong>Remarks:</strong> <?= htmlspecialchars($delivery['remarks'] ?? 'None') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card data-card mb-4">
            <div class="card-header">
                <i class="bi bi-box me-2"></i>Delivered Item
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Description</th>
                            <th>UOM</th>
                            <th>Size</th>
                            <th>Qty Ordered</th>
                            <th>Production Progress</th>
                            <th>Delivered</th>
                            <th>Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($poi_item)):
                            $qty = $poi_item['quantity'] ?? 0;
                            $itemProduced = $poi_item['produced_quantity'] ?? 0;
                            $itemDelivered = $poi_item['delivered_quantity'] ?? 0;
                            $remaining = max(0, $qty - $itemDelivered);
                            $itemPercent = $qty > 0 ? round(($itemProduced / $qty) * 100) : 0;
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($poi_item['item_code']) ?></strong></td>
                            <td><?= htmlspecialchars($poi_item['item_description']) ?></td>
                            <td><?= htmlspecialchars($poi_item['item_uom']) ?></td>
                            <td><?= htmlspecialchars($poi_item['item_size'] ?? '-') ?></td>
                            <td><?= $qty ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 14px; width: 80px;">
                                        <div class="progress-bar <?= $itemPercent >= 100 ? 'bg-success' : 'bg-warning' ?>" style="width: <?= $itemPercent ?>%"></div>
                                    </div>
                                    <small class="text-muted text-nowrap"><?= $itemProduced ?>/<?= $qty ?></small>
                                </div>
                            </td>
                            <td><small class="text-muted"><?= $itemDelivered ?>/<?= $qty ?></small></td>
                            <td><small class="badge <?= $remaining <= 0 ? 'bg-success' : 'bg-warning' ?>"><?= $remaining ?></small></td>
                        </tr>
                        <?php else: ?>
                        <tr><td colspan="8" class="text-center text-muted py-3">Item not found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="col-md-4">
        <div class="card data-card mb-4">
            <div class="card-body">
                <a href="?controller=finance&action=printSalesInvoice" target="_blank" class="btn btn-primary w-100">
                    <i class="bi bi-printer me-2"></i>Print Sales Invoice
                </a>
            </div>
        </div>

        <div class="card data-card mb-4">
            <div class="card-header">
                <i class="bi bi-paperclip me-2"></i>Delivery Receipts
            </div>
            <div class="card-body">
                <?php if (!empty($receipts)): ?>
                    <?php foreach ($receipts as $r): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <i class="bi bi-file-earmark me-2"></i>
                            <strong><?= htmlspecialchars($r['file_name']) ?></strong>
                            <br>
                            <small class="text-muted">
                                <?= number_format($r['file_size'] / 1024, 1) ?> KB &middot; 
                                <?= date('M d, Y', strtotime($r['date_created'])) ?>
                            </small>
                        </div>
                        <div class="btn-group">
                            <a href="<?= $r['file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="?controller=finance&action=deleteReceipt&id=<?= $r['receipt_id'] ?>&delivery_id=<?= $delivery['delivery_id'] ?>" 
                               class="btn btn-sm btn-outline-danger" title="Delete"
                               onclick="return confirm('Are you sure you want to delete this receipt?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-muted py-3">No receipts attached yet</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card data-card">
            <div class="card-header">
                <i class="bi bi-upload me-2"></i>Attach Receipt
            </div>
            <div class="card-body">
                <form method="POST" action="?controller=finance&action=uploadReceipt" enctype="multipart/form-data">
                    <input type="hidden" name="delivery_id" value="<?= $delivery['delivery_id'] ?>">
                    <input type="hidden" name="po_id" value="<?= $delivery['po_id'] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Select File</label>
                        <input type="file" name="receipt_file" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx" required>
                        <div class="form-text">Allowed: JPG, PNG, GIF, WebP, PDF, DOC, DOCX (max 10MB)</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-upload me-2"></i>Upload Receipt
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>
