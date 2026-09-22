<?php $isReadOnly = !empty($readOnly) || (($_SESSION['department'] ?? '') !== 'warehouse'); ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-box-arrow-in-down me-2"></i>Receiving Purchasing PO</h4>
    <?php if (!$isReadOnly): ?>
    <div class="d-flex gap-2 flex-wrap">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="controller" value="warehouse">
            <input type="hidden" name="action" value="receivingPo">
            <select name="status" class="form-select form-select-sm" style="width:180px">
                <option value="">Active Orders</option>
                <option value="all" <?= ($filters['status'] ?? '') === 'all' ? 'selected' : '' ?>>All Statuses</option>
                <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="processed" <?= ($filters['status'] ?? '') === 'processed' ? 'selected' : '' ?>>Processed</option>
                <option value="partially_received" <?= ($filters['status'] ?? '') === 'partially_received' ? 'selected' : '' ?>>Partially Received</option>
                <option value="For Inspection" <?= ($filters['status'] ?? '') === 'For Inspection' ? 'selected' : '' ?>>For Inspection</option>
                <option value="received" <?= ($filters['status'] ?? '') === 'received' ? 'selected' : '' ?>>Received</option>
                <option value="rejected" <?= ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
            <select name="supplier" class="form-select form-select-sm" style="width:200px">
                <option value="">All Suppliers</option>
                <?php foreach ($suppliers as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>" <?= ($filters['supplier'] ?? '') === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search PO Ref, Item..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>" style="width:220px">
            <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
            <a href="?controller=warehouse&action=receivingPo" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
        </form>
    </div>
    <?php endif; ?>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">No purchasing POs ready for receiving.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($orders as $o): ?>
                <?php
                    $status = strtolower(trim((string) ($o['status'] ?? '')));
                    $remainingQty = floatval($o['quantity']) - floatval($o['received_qty'] ?? 0);
                ?>
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
                        <?php if ($status === 'requested'): ?>
                            <span class="badge bg-info">Requested</span>
                        <?php elseif ($status === 'pending'): ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                        <?php elseif ($status === 'processed'): ?>
                            <span class="badge bg-primary">Processed</span>
                        <?php elseif ($status === 'partially_received'): ?>
                            <span class="badge bg-info text-dark">Partially Received</span>
                        <?php elseif ($status === 'for inspection'): ?>
                            <span class="badge bg-warning text-dark">For Inspection</span>
                        <?php elseif (in_array($status, ['approved', 'received'])): ?>
                            <span class="badge bg-success">Received & Approved</span>
                        <?php elseif ($status === 'rejected'): ?>
                            <span class="badge bg-danger">Rejected</span>
                        <?php elseif ($status === 'cancelled'): ?>
                            <span class="badge bg-secondary">Cancelled</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst((string) ($o['status'] ?? 'Unknown'))) ?: 'Unknown' ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isReadOnly): ?>
                            <span class="text-muted">-</span>
                        <?php elseif (in_array($status, ['pending', 'processed']) && $remainingQty > 0.0001): ?>
                            <button class="btn btn-sm btn-success receive-shipment-btn"
                                    data-id="<?= $o['supplier_order_id'] ?>"
                                    data-supplier="<?= htmlspecialchars($o['supplier_name']) ?>"
                                    data-item-code="<?= htmlspecialchars($o['item_code']) ?>"
                                    data-item-desc="<?= htmlspecialchars($o['item_description']) ?>"
                                    data-ordered-qty="<?= $o['quantity'] ?>"
                                    data-received-qty="<?= $o['received_qty'] ?? 0 ?>"
                                    data-uom="<?= htmlspecialchars($o['item_uom']) ?>"
                                    data-po-ref="<?= htmlspecialchars($o['customer_po_number'] ?? ($o['po_id'] ? 'PO #'.$o['po_id'] : 'SO #'.$o['supplier_order_id'])) ?>"
                                    title="Receive Shipment">
                                <i class="bi bi-box-arrow-in-down"></i> Receive
                            </button>
                        <?php elseif ($status === 'requested'): ?>
                            <span class="badge bg-secondary">Awaiting Purchasing</span>
                        <?php elseif ($status === 'for inspection'): ?>
                            <span class="badge bg-warning text-dark">In QC Queue</span>
                        <?php elseif (in_array($status, ['approved', 'received'])): ?>
                            <span class="badge bg-success">Received & Approved</span>
                        <?php elseif ($status === 'rejected'): ?>
                            <span class="badge bg-danger">Rejected</span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$isReadOnly): ?>
<!-- Receive Shipment Modal -->
<div class="modal fade" id="receiveShipmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="?controller=warehouse&action=receivePurchasingPo" id="receiveShipmentForm"
                  onsubmit="return submitReceiveFormOnce(this);">
                <input type="hidden" name="supplier_order_id" id="recvOrderId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-down me-2"></i>Receive Shipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>PO Ref:</strong> <span id="recvPoRef"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Supplier:</strong> <span id="recvSupplier"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Item:</strong> <code id="recvItemCode"></code> - <span id="recvItemDesc"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>UOM:</strong> <span id="recvUom"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>Ordered Qty:</strong> <span id="recvOrderedQty"></span>
                        </div>
                        <div class="col-md-4">
                            <strong>Already Received:</strong> <span id="recvAlreadyReceived"></span>
                        </div>
                        <div class="col-md-4">
                            <strong>Remaining:</strong> <span id="recvRemaining" class="fw-bold text-primary"></span>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Received Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="received_qty" id="recvReceivedQty" class="form-control" min="0.0001" step="0.0001" required>
                        <small class="text-muted">Enter the quantity actually received (may differ from ordered).</small>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Lot / Batch Number <span class="text-danger">*</span></label>
                            <input type="text" name="lot_number" id="recvLotNumber" class="form-control" placeholder="Required" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Expiry Date</label>
                            <input type="date" name="expiry_date" id="recvExpiryDate" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Delivery Receipt / Invoice #</label>
                        <input type="text" name="delivery_receipt_no" id="recvDeliveryReceipt" class="form-control" placeholder="DR / Invoice # for verification">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Received Date <span class="text-danger">*</span></label>
                        <input type="date" name="received_date" id="recvReceivedDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea name="remarks" id="recvRemarks" class="form-control" rows="2" placeholder="Any notes about this receipt..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Confirm Receipt</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function submitReceiveFormOnce(form) {
    // Prevent accidental double-clicks from submitting the form twice.
    var submitBtn = form.querySelector('button[type=submit]');
    if (submitBtn) {
        if (submitBtn.disabled) return false;
        submitBtn.disabled = true;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.receive-shipment-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var ordered = parseFloat(this.dataset.orderedQty) || 0;
            var received = parseFloat(this.dataset.receivedQty) || 0;
            var remaining = ordered - received;

            document.getElementById('recvOrderId').value = this.dataset.id;
            document.getElementById('recvPoRef').textContent = this.dataset.poRef;
            document.getElementById('recvSupplier').textContent = this.dataset.supplier;
            document.getElementById('recvItemCode').textContent = this.dataset.itemCode;
            document.getElementById('recvItemDesc').textContent = this.dataset.itemDesc;
            document.getElementById('recvUom').textContent = this.dataset.uom;
            document.getElementById('recvOrderedQty').textContent = ordered.toFixed(4);
            document.getElementById('recvAlreadyReceived').textContent = received.toFixed(4);
            document.getElementById('recvRemaining').textContent = remaining.toFixed(4);
            document.getElementById('recvReceivedQty').value = remaining.toFixed(4);
            document.getElementById('recvReceivedQty').max = remaining.toFixed(4);
            document.getElementById('recvLotNumber').value = '';
            document.getElementById('recvExpiryDate').value = '';
            document.getElementById('recvDeliveryReceipt').value = '';
            document.getElementById('recvReceivedDate').value = '<?= date('Y-m-d') ?>';
            document.getElementById('recvRemarks').value = '';

            new bootstrap.Modal(document.getElementById('receiveShipmentModal')).show();
        });
    });

    // Reset form on modal close
    document.getElementById('receiveShipmentModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('receiveShipmentForm').reset();
    });
});
</script>
<?php endif; ?>