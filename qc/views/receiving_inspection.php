<script>
function submitQcDecisionOnce(form) {
    // Prevent accidental double-clicks from recording the inspection twice.
    var submitBtn = form.querySelector('button[type=submit]');
    if (submitBtn) {
        if (submitBtn.disabled) return false;
        submitBtn.disabled = true;
    }
    return true;
}
</script>

<div class="card mb-4 shadow-sm border-0">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Receiving Inspection Queue</h5>
        <span class="badge bg-warning text-dark"><?= count($pendingQcItems ?? []) ?> pending</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>PO Ref</th>
                        <th>Supplier</th>
                        <th>Item Code</th>
                        <th>Description</th>
                        <th class="text-end">Qty Received</th>
                        <th>Date Received</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pendingQcItems)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No incoming PO receipts are waiting for QC approval.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pendingQcItems as $item): ?>
                            <?php $itemId = (int) ($item['receiving_item_id'] ?? 0); ?>
                            <?php $receivedQty = (float) ($item['received_qty'] ?? 0); ?>
                            <?php $currentStatus = strtoupper((string) ($item['qc_status'] ?? 'PENDING_QC')); ?>
                            <tr>
                                <td><?= htmlspecialchars($item['customer_po_number'] ?? ($item['po_ref'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars($item['supplier_name'] ?? '-') ?></td>
                                <td><strong><?= htmlspecialchars($item['item_code'] ?? '-') ?></strong></td>
                                <td><?= htmlspecialchars($item['item_description'] ?? '-') ?></td>
                                <td class="text-end"><?= number_format($receivedQty, 4) ?></td>
                                <td><?= !empty($item['received_date']) ? date('Y-m-d', strtotime($item['received_date'])) : '-' ?></td>
                                <td>
                                    <span class="badge bg-warning text-dark"><?= htmlspecialchars($currentStatus === 'PENDING_QC' ? 'Pending Inspection' : $currentStatus) ?></span>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="?controller=qc&action=approveReceivingInspection" class="d-inline-flex gap-2"
                                          onsubmit="return submitQcDecisionOnce(this);">
                                        <input type="hidden" name="receiving_item_id" value="<?= $itemId ?>">
                                        <input type="hidden" name="received_qty" value="<?= $receivedQty ?>">
                                        <input type="hidden" name="passed_qty" value="<?= $receivedQty ?>">
                                        <input type="hidden" name="rejected_qty" value="0">
                                        <input type="hidden" name="decision" value="PASSED">
                                        <input type="hidden" name="inspector_name" value="<?= htmlspecialchars($_SESSION['full_name'] ?? 'QC') ?>">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this receiving inspection and release the stock into SOH?')">
                                            <i class="bi bi-check-lg me-1"></i>Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="?controller=qc&action=approveReceivingInspection" class="d-inline-flex gap-2 mt-1 mt-md-0"
                                          onsubmit="return submitQcDecisionOnce(this);">
                                        <input type="hidden" name="receiving_item_id" value="<?= $itemId ?>">
                                        <input type="hidden" name="received_qty" value="<?= $receivedQty ?>">
                                        <input type="hidden" name="passed_qty" value="0">
                                        <input type="hidden" name="rejected_qty" value="<?= $receivedQty ?>">
                                        <input type="hidden" name="decision" value="REJECTED">
                                        <input type="hidden" name="inspector_name" value="<?= htmlspecialchars($_SESSION['full_name'] ?? 'QC') ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this receiving inspection and keep the stock excluded from SOH?')">
                                            <i class="bi bi-x-lg me-1"></i>Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>QC Inspection Record</h5>
        <span class="badge bg-secondary"><?= count($completedQcItems ?? []) ?> recorded</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>PO Ref</th>
                        <th>Supplier</th>
                        <th>Item Code</th>
                        <th>Description</th>
                        <th class="text-end">Received Qty</th>
                        <th>Date Received</th>
                        <th>Decision</th>
                        <th>Inspector</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($completedQcItems)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No approved or rejected inspections have been recorded yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($completedQcItems as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['customer_po_number'] ?? ($item['po_ref'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars($item['supplier_name'] ?? '-') ?></td>
                                <td><strong><?= htmlspecialchars($item['item_code'] ?? '-') ?></strong></td>
                                <td><?= htmlspecialchars($item['item_description'] ?? '-') ?></td>
                                <td class="text-end"><?= number_format((float) ($item['received_qty'] ?? 0), 4) ?></td>
                                <td><?= !empty($item['received_date']) ? date('Y-m-d', strtotime($item['received_date'])) : '-' ?></td>
                                <td>
                                    <?php $decision = strtoupper((string) ($item['qc_status'] ?? '')); ?>
                                    <span class="badge <?= $decision === 'PASSED' ? 'bg-success' : 'bg-danger' ?>"><?= htmlspecialchars($decision === 'PASSED' ? 'Approved' : 'Rejected') ?></span>
                                </td>
                                <td><?= htmlspecialchars($item['inspected_by'] ?? 'QC') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
