<h4><i class="bi bi-exclamation-triangle me-2"></i>Excess Production</h4>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2 flex-wrap">
        <select id="filterCustomer" class="form-select form-select-sm" style="width:200px">
            <option value="">All Customers</option>
            <?php foreach ($customers as $c): ?>
                <option value="<?= $c['customer_id'] ?>" <?= ($_GET['customer_id'] ?? '') == $c['customer_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['customer_code'] . ' - ' . $c['customer_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select id="filterStatus" class="form-select form-select-sm" style="width:160px">
            <option value="">All Status</option>
            <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="partial" <?= ($_GET['status'] ?? '') === 'partial' ? 'selected' : '' ?>>Partial</option>
            <option value="consumed" <?= ($_GET['status'] ?? '') === 'consumed' ? 'selected' : '' ?>>Consumed</option>
        </select>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearFilters"><i class="bi bi-x-circle me-1"></i>Clear</button>
    </div>
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Item</th>
                    <th>Source PO</th>
                    <th>Excess Qty</th>
                    <th>Consumed</th>
                    <th>Remaining</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Notes</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($excess)): ?>
                    <?php foreach ($excess as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['customer_code'] ?? '') ?> - <?= htmlspecialchars($e['customer_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($e['item_code'] ?? '') ?> - <?= htmlspecialchars($e['item_description'] ?? '') ?></td>
                            <td><?= htmlspecialchars($e['source_po_number'] ?? '-') ?></td>
                            <td><?= $e['excess_quantity'] ?></td>
                            <td><?= $e['consumed_quantity'] ?></td>
                            <td><strong><?= $e['remaining_quantity'] ?></strong></td>
                            <td>
                                <?php if ($e['status'] === 'pending'): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php elseif ($e['status'] === 'partial'): ?>
                                    <span class="badge bg-info">Partial</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Consumed</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('Y-m-d', strtotime($e['created_at'])) ?></td>
                            <td>
                                <small class="text-muted"><?= htmlspecialchars($e['notes'] ?? '-') ?></small>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary edit-notes-btn" 
                                    data-excess-id="<?= $e['excess_id'] ?>" 
                                    data-notes="<?= htmlspecialchars($e['notes'] ?? '') ?>"
                                    title="Edit Notes">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No excess production records found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="editNotesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Notes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editExcessId">
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea id="editNotes" class="form-control" rows="3" placeholder="Enter notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveNotesBtn"><i class="bi bi-save me-1"></i>Save</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('filterCustomer').addEventListener('change', applyFilters);
document.getElementById('filterStatus').addEventListener('change', applyFilters);
document.getElementById('clearFilters').addEventListener('click', function() {
    document.getElementById('filterCustomer').value = '';
    document.getElementById('filterStatus').value = '';
    applyFilters();
});

function applyFilters() {
    var customer = document.getElementById('filterCustomer').value;
    var status = document.getElementById('filterStatus').value;
    var url = '?controller=warehouse&action=excessProduction';
    if (customer) url += '&customer_id=' + customer;
    if (status) url += '&status=' + status;
    window.location.href = url;
}

document.querySelectorAll('.edit-notes-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('editExcessId').value = this.dataset.excessId;
        document.getElementById('editNotes').value = this.dataset.notes || '';
        new bootstrap.Modal(document.getElementById('editNotesModal')).show();
    });
});

document.getElementById('saveNotesBtn').addEventListener('click', function() {
    var excessId = document.getElementById('editExcessId').value;
    var notes = document.getElementById('editNotes').value;
    fetch('?controller=warehouse&action=updateExcessNotes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'excess_id=' + encodeURIComponent(excessId) + '&notes=' + encodeURIComponent(notes)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Failed to save notes');
        }
    });
});
</script>
