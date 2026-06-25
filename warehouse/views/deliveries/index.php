<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDeliveryModal">
            <i class="bi bi-plus-circle me-1"></i> Create Delivery Receipt
        </button>
        <button type="button" class="btn btn-outline-primary ms-2" id="printDRBtn">
            <i class="bi bi-printer me-1"></i> Print DR
        </button>
    </div>
    <div class="search-box" style="width: 300px;">
        <i class="bi bi-search"></i>
        <input type="text" id="searchDelivery" class="form-control" placeholder="Search delivery...">
    </div>
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Customer</th>
                    <th>Item</th>
                    <th>Lot Number</th>
                    <th>DR Number</th>
                    <th>PO Quantity</th>
                    <th>Delivered</th>
                    <th>Remaining Balance</th>
                    <th>Type</th>
                    <th>Delivery Date</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody id="deliveryTableBody">
                <?php foreach ($deliveries as $d): ?>
                <tr>
                    <td><strong class="text-primary"><?= $d['customer_po_number'] ?></strong></td>
                    <td><?= htmlspecialchars($d['customer_name'] ?? '-') ?></td>
                    <td><small><?= htmlspecialchars(($d['item_code'] ?? '-') . ' - ' . ($d['item_description'] ?? '')) ?></small></td>
                    <td><small><?= htmlspecialchars($d['lot_number'] ?? '-') ?></small></td>
                    <td><?= htmlspecialchars($d['dr_number'] ?? '') ?: '<span class="text-muted">-</span>' ?></td>
                    <td><?= $d['total_quantity'] ?? 0 ?></td>
                    <td><?= $d['delivery_quantity'] ?? 0 ?></td>
                    <td><?= ($d['total_quantity'] ?? 0) - ($d['delivered_quantity'] ?? 0) ?></td>
                    <td>
                        <?php if (($d['production_type'] ?? 'normal') === 'advance'): ?>
                            <span class="badge bg-info">Advance</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Normal</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('Y-m-d', strtotime($d['delivery_date'])) ?></td>
                    <td><?= htmlspecialchars($d['remarks'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($deliveries)): ?>
                <tr><td colspan="11" class="text-center text-muted py-4">No deliveries found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination justify-content-center mt-4">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?controller=warehouse&action=deliveries&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- DR Number Input Modal -->
<div class="modal fade" id="drInputModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Enter DR Number</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="drNumberInput" class="form-control" placeholder="Enter DR Number" autofocus>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="drInputOkBtn">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- DR Number Confirm Modal -->
<div class="modal fade" id="drConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-question-circle me-2"></i>Confirm DR Number</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure for this DR number?</p>
                <p class="fw-bold text-primary mb-0" id="drConfirmNumber">-</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="drConfirmEditBtn"><i class="bi bi-pencil me-1"></i>Edit</button>
                <button type="button" class="btn btn-primary" id="drConfirmYesBtn"><i class="bi bi-check-lg me-1"></i>Yes</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createDeliveryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-truck me-2"></i>Record Delivery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?controller=warehouse&action=createMultipleDelivery">
                <div class="modal-body">
                    <!-- DR Number (required) -->
                    <div class="mb-3">
                        <label class="form-label">Delivery Receipt (DR) Number *</label>
                        <input type="text" name="dr_number" id="modalDrNumber" class="form-control" placeholder="Enter DR number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purchase Order</label>
                        <select name="po_id" id="poSelect" class="form-select" required>
                            <option value="">Select PO</option>
                            <?php foreach ($purchase_orders as $po): ?>
                                <option value="<?= $po['po_id'] ?>" data-type="<?= $po['production_type'] ?? 'normal' ?>">
                                    <?= $po['customer_po_number'] ?> - <?= $po['customer_name'] ?>
                                    [<?= ($po['production_type'] ?? 'normal') === 'advance' ? 'Advance' : 'Normal' ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="itemRow" style="display: none;">
                        <label class="form-label">Item *</label>
                        <select name="poi_id" id="poiSelect" class="form-select">
                            <option value="">Select Item</option>
                        </select>
                        <input type="hidden" id="itemQty" value="0">
                        <input type="hidden" id="itemProduced" value="0">
                        <input type="hidden" id="itemDelivered" value="0">
                    </div>
                    <div class="mb-3" id="lotRow" style="display: none;">
                        <label class="form-label">Select Lots *</label>
                        <!-- Container for lot checkboxes (multiple selection) -->
                        <div id="lotCheckboxContainer" class="form-check">
                            <!-- checkboxes will be injected via JS -->
                        </div>
                        <!-- hidden field to collect selected lot IDs on submit -->
                        <input type="hidden" name="lot_ids" id="selectedLotIds">
                    </div>
                    <div class="mb-3" id="itemQtyRow" style="display: none;">
                        <label class="form-label">PO Quantity</label>
                        <input type="text" id="itemQtyDisplay" class="form-control" readonly>
                    </div>
                    <div class="mb-3" id="availableRow" style="display: none;">
                        <label class="form-label">Available for Delivery</label>
                        <input type="text" id="availableQty" class="form-control" readonly>
                    </div>
                    <!-- Delivery quantity is now derived from selected lots, so we hide the manual field -->
                    <div class="mb-3" style="display: none;">
                        <label class="form-label">Delivery Quantity *</label>
                        <input type="number" name="delivery_quantity" id="deliveryQty" class="form-control" min="1">
                        <div class="invalid-feedback" id="deliveryError"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Delivery Date</label>
                        <input type="date" name="delivery_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Save Delivery</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('searchDelivery').addEventListener('keyup', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#deliveryTableBody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
});

let poItemsCache = [];

function renderLotCheckboxes(lots, lotRow, lotContainer) {
    lotContainer.innerHTML = '';
    if (lots && lots.length > 0) {
        lots.forEach(function(lot) {
            const wrapper = document.createElement('div');
            wrapper.className = 'form-check';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'form-check-input';
            checkbox.value = lot.lot_id;
            checkbox.id = 'lotChk_' + lot.lot_id;
            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = checkbox.id;
            label.textContent = lot.lot_number + ' (Available: ' + lot.available_quantity + ')';
            wrapper.appendChild(checkbox);
            wrapper.appendChild(label);
            lotContainer.appendChild(wrapper);
        });
        lotRow.style.display = 'block';
    } else {
        lotRow.style.display = 'none';
    }
}

document.getElementById('poSelect').addEventListener('change', function() {
    const poId = this.value;
    const itemRow = document.getElementById('itemRow');
    const itemQtyRow = document.getElementById('itemQtyRow');
    const availableRow = document.getElementById('availableRow');
    const poiSelect = document.getElementById('poiSelect');
    const lotRow = document.getElementById('lotRow');
    const lotContainer = document.getElementById('lotCheckboxContainer');

    itemRow.style.display = 'none';
    itemQtyRow.style.display = 'none';
    availableRow.style.display = 'none';
    poiSelect.innerHTML = '<option value="">Select Item</option>';
    poiSelect.disabled = false;
    var existingHidden = poiSelect.parentNode.querySelector('input[name="poi_id"][type="hidden"]');
    if (existingHidden) existingHidden.remove();
    document.getElementById('deliveryQty').value = '';
    document.getElementById('deliveryQty').classList.remove('is-invalid');
    document.getElementById('deliveryError').textContent = '';
    lotRow.style.display = 'none';
    lotContainer.innerHTML = '';

    if (!poId) return;

    fetch('?controller=warehouse&action=getPODetails&id=' + poId)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            const items = data.po_items || [];
            poItemsCache = items;

            if (items.length > 1) {
                items.forEach(function(item) {
                    const opt = document.createElement('option');
                    opt.value = item.poi_id;
                    opt.textContent = item.item_description || '-';
                    opt.dataset.qty = item.quantity || 0;
                    opt.dataset.produced = item.produced_quantity || 0;
                    opt.dataset.delivered = item.delivered_quantity || 0;
                    poiSelect.appendChild(opt);
                });
                itemRow.style.display = 'block';
            } else if (items.length === 1) {
                const item = items[0];
                document.getElementById('itemQty').value = item.quantity || 0;
                document.getElementById('itemProduced').value = item.produced_quantity || 0;
                document.getElementById('itemDelivered').value = item.delivered_quantity || 0;

                document.getElementById('itemQtyDisplay').value = item.quantity || 0;
                document.getElementById('availableQty').value = '';

                itemQtyRow.style.display = 'block';
                availableRow.style.display = 'none';

                poiSelect.innerHTML = '<option value="' + item.poi_id + '">' + (item.item_description || '-') + '</option>';
                poiSelect.value = item.poi_id;
                poiSelect.disabled = true;
                var hiddenPoi = document.createElement('input');
                hiddenPoi.type = 'hidden';
                hiddenPoi.name = 'poi_id';
                hiddenPoi.value = item.poi_id;
                poiSelect.parentNode.appendChild(hiddenPoi);
                itemRow.style.display = 'block';

                fetch('?controller=warehouse&action=getAvailableLots&poi_id=' + item.poi_id)
                    .then(function(response) { return response.json(); })
                    .then(function(lots) {
                        renderLotCheckboxes(lots, lotRow, lotContainer);
                    });
            }
        });
});

document.getElementById('poiSelect').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const lotRow = document.getElementById('lotRow');
    const lotContainer = document.getElementById('lotCheckboxContainer');

    if (!this.value) {
        document.getElementById('itemQtyRow').style.display = 'none';
        document.getElementById('availableRow').style.display = 'none';
        document.getElementById('deliveryQty').value = '';
        lotRow.style.display = 'none';
        lotContainer.innerHTML = '';
        return;
    }

    const qty = parseInt(selected.dataset.qty) || 0;

    document.getElementById('itemQtyDisplay').value = qty;
    document.getElementById('itemQtyRow').style.display = 'block';
    document.getElementById('availableRow').style.display = 'none';
    document.getElementById('availableQty').value = '';

    document.getElementById('deliveryQty').value = '';
    document.getElementById('deliveryQty').classList.remove('is-invalid');
    document.getElementById('deliveryError').textContent = '';

    fetch('?controller=warehouse&action=getAvailableLots&poi_id=' + this.value)
        .then(function(response) { return response.json(); })
        .then(function(lots) {
            renderLotCheckboxes(lots, lotRow, lotContainer);
        });
});

document.querySelector('#createDeliveryModal form').addEventListener('submit', function(e) {
    const poSelect = document.getElementById('poSelect');
    if (!poSelect.value) {
        e.preventDefault();
        alert('Please select a Purchase Order');
        return;
    }

    const itemRow = document.getElementById('itemRow');
    const poiSelect = document.getElementById('poiSelect');
    if (itemRow.style.display !== 'none' && !poiSelect.value) {
        e.preventDefault();
        alert('Please select an item');
        return;
    }

    const checkedBoxes = document.querySelectorAll('#lotCheckboxContainer input:checked');
    if (checkedBoxes.length === 0) {
        e.preventDefault();
        alert('Please select at least one lot');
        return;
    }
    const lotIds = [];
    checkedBoxes.forEach(cb => lotIds.push(cb.value));
    document.getElementById('selectedLotIds').value = lotIds.join(',');
});

var drInputModal, drConfirmModal;
var drState = { drNumber: '' };

document.getElementById('printDRBtn').addEventListener('click', function() {
    document.getElementById('drNumberInput').value = '';
    drInputModal = new bootstrap.Modal(document.getElementById('drInputModal'));
    drInputModal.show();
});

document.getElementById('drInputOkBtn').addEventListener('click', function() {
    var value = document.getElementById('drNumberInput').value.trim();
    if (value === '') {
        alert('Please enter a DR number');
        return;
    }
    drState.drNumber = value;
    drInputModal.hide();
    document.getElementById('drConfirmNumber').textContent = value;
    drConfirmModal = new bootstrap.Modal(document.getElementById('drConfirmModal'));
    drConfirmModal.show();
});

document.getElementById('drNumberInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('drInputOkBtn').click();
    }
});

document.getElementById('drConfirmEditBtn').addEventListener('click', function() {
    drConfirmModal.hide();
    document.getElementById('drNumberInput').value = drState.drNumber;
    drInputModal = new bootstrap.Modal(document.getElementById('drInputModal'));
    drInputModal.show();
});

document.getElementById('drConfirmYesBtn').addEventListener('click', function() {
    var drNumber = drState.drNumber;
    drConfirmModal.hide();

    fetch('?controller=warehouse&action=checkDRNumber&dr_number=' + encodeURIComponent(drNumber))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.exists && data.po_ids && data.po_ids.length > 0) {
                window.location.href = '?controller=warehouse&action=printDR&dr_number=' + encodeURIComponent(drNumber) + '&po_id=' + data.po_ids[0];
            } else {
                window.location.href = '?controller=warehouse&action=printDR&dr_number=' + encodeURIComponent(drNumber);
            }
        })
        .catch(function() {
            window.location.href = '?controller=warehouse&action=printDR&dr_number=' + encodeURIComponent(drNumber);
        });
});
</script>