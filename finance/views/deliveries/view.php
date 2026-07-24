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
                        <p><strong>Delivery Address:</strong> <?= htmlspecialchars($delivery['customer_address'] ?? '-') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Delivery Date:</strong> <?= date('F d, Y', strtotime($delivery['delivery_date'])) ?></p>
                        <p><strong>DR No.:</strong> <?= htmlspecialchars($delivery['dr_number'] ?? '-') ?></p>
                        <p><strong>Delivered By:</strong> <?= htmlspecialchars($delivery['delivered_by_name'] ?? '-') ?></p>
                        <p><strong>Remarks:</strong> <?= htmlspecialchars($delivery['remarks'] ?? 'None') ?></p>
                        <p><strong>Report / Edit:</strong> <?= htmlspecialchars($delivery['report_remarks'] ?? 'None') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card data-card mb-4">
            <div class="card-header">
                <i class="bi bi-box me-2"></i>Delivered Items
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Description</th>
                            <th>Lot Number</th>
                            <th>UOM</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Cases</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $lotItems = json_decode($delivery['lot_items'] ?? '[]', true);
                        $hasLotItems = is_array($lotItems) && count($lotItems) > 0;
                        $totalQty = 0;
                        $totalCases = 0;
                        if ($hasLotItems):
                            foreach ($lotItems as $li):
                                $liQty = $li['qty'] ?? 0;
                                $conv = $li['actual_uom_conversion'] ?? $li['uom_conversion'] ?? null;
                                $uom = $li['item_uom'] ?? '';
                                $cases = ($conv && $uom !== 'CS') ? floor($liQty / $conv) : 0;
                                $totalQty += $liQty;
                                $totalCases += $cases;
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($li['item_code'] ?? '-') ?></strong></td>
                            <td><?= htmlspecialchars($li['item_description'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($li['lot_number'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($uom) ?></td>
                            <td class="text-end"><?= number_format($liQty) ?></td>
                            <td class="text-end"><?= $cases > 0 ? $cases . ' CS' : '---' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-light fw-bold">
                            <td colspan="4" class="text-end">Total:</td>
                            <td class="text-end"><?= number_format($totalQty) ?></td>
                            <td class="text-end"><?= $totalCases > 0 ? $totalCases . ' CS' : '---' ?></td>
                        </tr>
                        <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No lot items found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="col-md-4">
        <div class="card data-card mb-4">
            <div class="card-body">
                <a href="?controller=finance&action=printSalesInvoice&id=<?= $delivery['delivery_id'] ?>" target="_blank" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-printer me-2"></i>Print Sales Invoice (SB)
                </a>
                <a href="?controller=finance&action=printSalesInvoiceWD&id=<?= $delivery['delivery_id'] ?>" target="_blank" class="btn btn-success w-100">
                    <i class="bi bi-printer me-2"></i>Print Sales Invoice (WD)
                </a>
            </div>
        </div>

        <div class="card data-card mb-4">
            <div class="card-header">
                <i class="bi bi-paperclip me-2"></i>Receipts
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
