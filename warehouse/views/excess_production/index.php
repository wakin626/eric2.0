<h4><i class="bi bi-exclamation-triangle me-2"></i>Excess &amp; Advance Production</h4>

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

<?php
    $combined = [];
    if (!empty($excess)) {
        foreach ($excess as $e) {
            $status = $e['status'] ?? 'pending';
            if (!empty($_GET['status']) && $status !== $_GET['status']) continue;
            $combined[] = [
                'type' => 'Excess',
                'customer' => ($e['customer_code'] ?? '') . ' - ' . ($e['customer_name'] ?? ''),
                'item' => ($e['item_code'] ?? '') . ' - ' . ($e['item_description'] ?? ''),
                'source_po' => $e['source_po_number'] ?? '-',
                'produced' => $e['excess_quantity'] ?? 0,
                'consumed' => $e['consumed_quantity'] ?? 0,
                'remaining' => $e['remaining_quantity'] ?? 0,
                'status' => $status,
                'date' => $e['created_at'] ?? '',
                'notes' => $e['notes'] ?? '',
                'excess_id' => $e['excess_id'] ?? null,
                'customer_id' => $e['customer_id'] ?? null,
                'item_id' => $e['item_id'] ?? null,
                'source_po_id' => $e['source_po_id'] ?? null,
                'source_poi_id' => $e['source_poi_id'] ?? null,
            ];
        }
    }
    if (!empty($advance)) {
        foreach ($advance as $a) {
            $status = $a['status'] ?? 'pending';
            if (!empty($_GET['status']) && $status !== $_GET['status']) continue;
            $combined[] = [
                'type' => 'Advance',
                'customer' => ($a['customer_code'] ?? '') . ' - ' . ($a['customer_name'] ?? ''),
                'item' => ($a['item_code'] ?? '') . ' - ' . ($a['item_description'] ?? ''),
                'source_po' => $a['source_po_number'] ?? '-',
                'produced' => $a['produced_quantity'] ?? 0,
                'consumed' => $a['consumed_quantity'] ?? 0,
                'remaining' => $a['remaining_quantity'] ?? 0,
                'status' => $status,
                'date' => $a['date_created'] ?? '',
                'notes' => '',
                'excess_id' => null,
            ];
        }
    }
?>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Customer</th>
                    <th>Item</th>
                    <th>Source PO</th>
                    <th>Produced</th>
                    <th>Consumed</th>
                    <th>Remaining</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Notes</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($combined)): ?>
                    <?php foreach ($combined as $row): ?>
                        <tr>
                            <td>
                                <?php if ($row['type'] === 'Advance'): ?>
                                    <span class="badge bg-primary">Advance</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Excess</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['customer']) ?></td>
                            <td><?= htmlspecialchars($row['item']) ?></td>
                            <td><?= htmlspecialchars($row['source_po']) ?></td>
                            <td><?= $row['produced'] ?></td>
                            <td><?= $row['consumed'] ?></td>
                            <td><strong><?= $row['remaining'] ?></strong></td>
                            <td>
                                <?php if ($row['status'] === 'pending'): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php elseif ($row['status'] === 'partial'): ?>
                                    <span class="badge bg-info">Partial</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Consumed</span>
                                <?php endif; ?>
                            </td>
                            <td><?= !empty($row['date']) ? date('Y-m-d', strtotime($row['date'])) : '-' ?></td>
                            <td>
                                <small class="text-muted"><?= htmlspecialchars($row['notes'] ?: '-') ?></small>
                            </td>
                            <td class="text-center">
                                <?php if ($row['type'] === 'Excess' && $row['remaining'] > 0 && $row['excess_id']): ?>
                                <button type="button" class="btn btn-sm btn-outline-success assign-excess-btn"
                                    data-excess-id="<?= $row['excess_id'] ?>"
                                    data-customer-id="<?= $row['customer_id'] ?>"
                                    data-item-id="<?= $row['item_id'] ?>"
                                    data-remaining="<?= $row['remaining'] ?>"
                                    data-source-po="<?= htmlspecialchars($row['source_po']) ?>"
                                    data-source-po-id="<?= $row['source_po_id'] ?>"
                                    data-source-poi-id="<?= $row['source_poi_id'] ?? '' ?>"
                                    data-item="<?= htmlspecialchars($row['item']) ?>"
                                    title="Assign Excess to PO">
                                    <i class="bi bi-arrow-left-right"></i> Assign
                                </button>
                                <?php endif; ?>
                                <?php if ($row['excess_id']): ?>
                                <button type="button" class="btn btn-sm btn-outline-primary edit-notes-btn" 
                                    data-excess-id="<?= $row['excess_id'] ?>" 
                                    data-notes="<?= htmlspecialchars($row['notes']) ?>"
                                    title="Edit Notes">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">No excess or advance production records found</td></tr>
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

<div class="modal fade" id="assignExcessModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Assign Excess to PO</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assignExcessId">
                <input type="hidden" id="assignCustomerId">
                <input type="hidden" id="assignItemId">
                <input type="hidden" id="assignLotId">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Customer</label>
                        <p id="assignCustomerName" class="text-muted mb-0">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Item</label>
                        <p id="assignItemName" class="text-muted mb-0">-</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Source PO</label>
                        <p id="assignSourcePO" class="text-muted mb-0">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Available Excess</label>
                        <p class="mb-0"><span id="assignAvailableQty" class="badge bg-danger fs-6">0</span></p>
                    </div>
                </div>

                <div id="assignSourceLotSection" class="mb-3" style="display:none;">
                    <label class="form-label fw-bold"><i class="bi bi-upc-scan me-1"></i>Lot Number (source)</label>
                    <div class="d-flex align-items-center gap-3">
                        <span id="assignLotNumber" class="badge bg-dark fs-6">-</span>
                        <span class="text-muted">Qty Produced: <strong id="assignLotQty">0</strong></span>
                        <span class="text-muted">Date: <span id="assignLotDate">-</span></span>
                    </div>
                </div>

                <hr>

                <div class="mb-3">
                    <label class="form-label fw-bold">Destination PO Number <span class="text-danger">*</span></label>
                    <select id="assignTargetPO" class="form-select" required>
                        <option value="">-- Select Destination PO --</option>
                    </select>
                    <small class="text-muted">Only POs for the same customer are shown.</small>
                </div>

                <div id="assignTargetPOItems" class="mb-3" style="display:none;">
                    <label class="form-label fw-bold">Items in Destination PO</label>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead><tr><th>Item</th><th>Qty</th><th>Produced</th><th>Status</th></tr></thead>
                            <tbody id="assignTargetPOItemsBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Quantity to Assign <span class="text-danger">*</span></label>
                    <input type="number" id="assignQty" class="form-control" min="1" placeholder="Enter quantity" required>
                    <small class="text-muted">Max: <span id="assignMaxQty">0</span></small>
                </div>

                <div id="assignErrorMsg" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmAssignBtn"><i class="bi bi-check-lg me-1"></i>Confirm Assignment</button>
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

document.querySelectorAll('.assign-excess-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var excessId = this.dataset.excessId;
        var customerId = this.dataset.customerId;
        var itemId = this.dataset.itemId;
        var remaining = this.dataset.remaining;
        var sourcePo = this.dataset.sourcePo;
        var sourcePoId = this.dataset.sourcePoId;
        var sourcePoiId = this.dataset.sourcePoiId;
        var itemName = this.dataset.item;

        document.getElementById('assignExcessId').value = excessId;
        document.getElementById('assignCustomerId').value = customerId;
        document.getElementById('assignItemId').value = itemId;
        document.getElementById('assignSourcePO').textContent = sourcePo;
        document.getElementById('assignItemName').textContent = itemName;
        document.getElementById('assignAvailableQty').textContent = remaining;
        document.getElementById('assignMaxQty').textContent = remaining;
        document.getElementById('assignQty').value = '';
        document.getElementById('assignQty').max = remaining;
        document.getElementById('assignTargetPO').value = '';
        document.getElementById('assignTargetPOItems').style.display = 'none';
        document.getElementById('assignTargetPOItemsBody').innerHTML = '';
        document.getElementById('assignErrorMsg').classList.add('d-none');
        document.getElementById('assignLotId').value = '';

        var customerParts = this.closest('tr').querySelector('td:nth-child(2)').textContent.trim();
        document.getElementById('assignCustomerName').textContent = customerParts;

        var lotSection = document.getElementById('assignSourceLotSection');
        lotSection.style.display = 'none';

        if (sourcePoiId) {
            fetch('?controller=warehouse&action=getLastExcessLot&poi_id=' + sourcePoiId)
                .then(function(r) { return r.json(); })
                .then(function(lot) {
                    if (lot && lot.lot_id) {
                        document.getElementById('assignLotId').value = lot.lot_id;
                        document.getElementById('assignLotNumber').textContent = lot.lot_number || '-';
                        document.getElementById('assignLotQty').textContent = lot.quantity_produced || 0;
                        document.getElementById('assignLotDate').textContent = lot.lot_date || '-';
                        lotSection.style.display = '';
                    }
                });
        }

        fetch('?controller=warehouse&action=getActivePOsForAssignment&customer_id=' + customerId)
            .then(function(r) { return r.json(); })
            .then(function(pos) {
                var select = document.getElementById('assignTargetPO');
                select.innerHTML = '<option value="">-- Select Destination PO --</option>';
                pos.forEach(function(po) {
                    if (po.po_id == sourcePoId) return;
                    var opt = document.createElement('option');
                    opt.value = po.po_id;
                    opt.textContent = po.customer_po_number + ' (' + (po.production_type || 'normal') + ' - ' + po.date_created + ')';
                    select.appendChild(opt);
                });
            });

        new bootstrap.Modal(document.getElementById('assignExcessModal')).show();
    });
});

document.getElementById('assignTargetPO').addEventListener('change', function() {
    var poId = this.value;
    var targetItemsBody = document.getElementById('assignTargetPOItemsBody');
    var targetItemsDiv = document.getElementById('assignTargetPOItems');

    if (!poId) {
        targetItemsDiv.style.display = 'none';
        targetItemsBody.innerHTML = '';
        return;
    }

    var assignItemId = document.getElementById('assignItemId').value;

    fetch('?controller=warehouse&action=getPOItemsForAssignment&po_id=' + poId)
        .then(function(r) { return r.json(); })
        .then(function(items) {
            targetItemsBody.innerHTML = '';
            var foundMatch = false;
            items.forEach(function(item) {
                var tr = document.createElement('tr');
                var isMatch = item.item_id == assignItemId;
                if (isMatch) { tr.classList.add('table-success'); foundMatch = true; }
                tr.innerHTML = '<td>' + (item.item_description || '-') + (isMatch ? ' <i class="bi bi-check-circle-fill text-success"></i>' : '') + '</td>'
                    + '<td>' + (item.quantity || 0) + '</td>'
                    + '<td>' + (item.produced_quantity || 0) + '</td>'
                    + '<td>' + (isMatch ? '<span class="badge bg-success">Match</span>' : '<span class="badge bg-secondary">Different Item</span>') + '</td>';
                targetItemsBody.appendChild(tr);
            });

            if (!foundMatch) {
                document.getElementById('assignErrorMsg').textContent = 'Warning: The excess item does not match any item in the selected PO. You can still assign, but verify the item is correct.';
                document.getElementById('assignErrorMsg').classList.remove('d-none');
            } else {
                document.getElementById('assignErrorMsg').classList.add('d-none');
            }

            targetItemsDiv.style.display = items.length > 0 ? '' : 'none';
        });
});

document.getElementById('confirmAssignBtn').addEventListener('click', function() {
    var excessId = document.getElementById('assignExcessId').value;
    var targetPoId = document.getElementById('assignTargetPO').value;
    var qty = document.getElementById('assignQty').value;
    var lotId = document.getElementById('assignLotId').value;
    var remaining = parseInt(document.getElementById('assignAvailableQty').textContent);
    var errorMsg = document.getElementById('assignErrorMsg');

    errorMsg.classList.add('d-none');

    if (!targetPoId) {
        errorMsg.textContent = 'Please select a destination PO.';
        errorMsg.classList.remove('d-none');
        return;
    }
    if (!qty || parseInt(qty) <= 0) {
        errorMsg.textContent = 'Please enter a valid quantity.';
        errorMsg.classList.remove('d-none');
        return;
    }
    if (parseInt(qty) > remaining) {
        errorMsg.textContent = 'Quantity cannot exceed available excess (' + remaining + ').';
        errorMsg.classList.remove('d-none');
        return;
    }

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';

    var body = 'excess_id=' + encodeURIComponent(excessId)
        + '&target_po_id=' + encodeURIComponent(targetPoId)
        + '&quantity=' + encodeURIComponent(qty);
    if (lotId) body += '&lot_id=' + encodeURIComponent(lotId);

    fetch('?controller=warehouse&action=assignExcess', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('assignExcessModal')).hide();
            window.location.reload();
        } else {
            errorMsg.textContent = data.message || 'Failed to assign excess.';
            errorMsg.classList.remove('d-none');
            document.getElementById('confirmAssignBtn').disabled = false;
            document.getElementById('confirmAssignBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Confirm Assignment';
        }
    })
    .catch(function() {
        errorMsg.textContent = 'An error occurred. Please try again.';
        errorMsg.classList.remove('d-none');
        document.getElementById('confirmAssignBtn').disabled = false;
        document.getElementById('confirmAssignBtn').innerHTML = '<i class="bi bi-check-lg me-1"></i>Confirm Assignment';
    });
});
</script>