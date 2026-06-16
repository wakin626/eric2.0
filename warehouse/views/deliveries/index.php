<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDeliveryModal">
            <i class="bi bi-plus-circle me-1"></i> Create PO Delivery
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
                    <th>Total Required</th>
                    <th>Delivered</th>
                    <th>Remaining Balance</th>
                    <th>Delivery Date</th>
                    <th>Remarks</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody id="deliveryTableBody">
                <?php foreach ($deliveries as $d): ?>
                <tr>
                    <td><strong class="text-primary"><?= $d['customer_po_number'] ?></strong></td>
                    <td><?= htmlspecialchars($d['customer_name'] ?? '-') ?></td>
                    <td><small><?= htmlspecialchars(($d['item_code'] ?? '-') . ' - ' . ($d['item_description'] ?? '')) ?></small></td>
                    <td><?= $d['total_quantity'] ?? 0 ?></td>
                    <td><?= $d['delivery_quantity'] ?? 0 ?></td>
                    <td><?= ($d['total_quantity'] ?? 0) - ($d['delivered_quantity'] ?? 0) ?></td>
                    <td><?= date('Y-m-d', strtotime($d['delivery_date'])) ?></td>
                    <td><?= htmlspecialchars($d['remarks'] ?? '-') ?></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary print-delivery-btn" data-po-id="<?= $d['po_id'] ?>" data-delivery-id="<?= $d['delivery_id'] ?>">
                            <i class="bi bi-printer"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($deliveries)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No deliveries found</td></tr>
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

<div class="modal fade" id="createDeliveryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-truck me-2"></i>Record Delivery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?controller=warehouse&action=createDelivery">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Purchase Order</label>
                        <select name="po_id" id="poSelect" class="form-select" required>
                            <option value="">Select PO</option>
                            <?php foreach ($purchase_orders as $po): ?>
                                <option value="<?= $po['po_id'] ?>">
                                    <?= $po['customer_po_number'] ?> - <?= $po['customer_name'] ?>
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
                    <div class="mb-3" id="itemQtyRow" style="display: none;">
                        <label class="form-label">Item Quantity</label>
                        <input type="text" id="itemQtyDisplay" class="form-control" readonly>
                    </div>
                    <div class="mb-3" id="availableRow" style="display: none;">
                        <label class="form-label">Available for Delivery</label>
                        <input type="text" id="availableQty" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Delivery Quantity *</label>
                        <input type="number" name="delivery_quantity" id="deliveryQty" class="form-control" min="1" required>
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

document.getElementById('poSelect').addEventListener('change', function() {
    const poId = this.value;
    const itemRow = document.getElementById('itemRow');
    const itemQtyRow = document.getElementById('itemQtyRow');
    const availableRow = document.getElementById('availableRow');
    const poiSelect = document.getElementById('poiSelect');

    itemRow.style.display = 'none';
    itemQtyRow.style.display = 'none';
    availableRow.style.display = 'none';
    poiSelect.innerHTML = '<option value="">Select Item</option>';
    poiSelect.disabled = false;
    document.getElementById('deliveryQty').value = '';
    document.getElementById('deliveryQty').classList.remove('is-invalid');
    document.getElementById('deliveryError').textContent = '';

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

                const itemAvailable = Math.max(0, (item.produced_quantity || 0) - (item.delivered_quantity || 0));
                document.getElementById('itemQtyDisplay').value = item.quantity || 0;
                document.getElementById('availableQty').value = itemAvailable;
                document.getElementById('deliveryQty').max = itemAvailable;

                itemQtyRow.style.display = 'block';
                availableRow.style.display = 'block';

                poiSelect.innerHTML = '<option value="' + item.poi_id + '">' + (item.item_description || '-') + '</option>';
                poiSelect.value = item.poi_id;
                poiSelect.disabled = true;
                itemRow.style.display = 'block';
            }
        });
});

document.getElementById('poiSelect').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    if (!this.value) {
        document.getElementById('itemQtyRow').style.display = 'none';
        document.getElementById('availableRow').style.display = 'none';
        document.getElementById('deliveryQty').value = '';
        return;
    }

    const qty = parseInt(selected.dataset.qty) || 0;
    const produced = parseInt(selected.dataset.produced) || 0;
    const delivered = parseInt(selected.dataset.delivered) || 0;
    const available = Math.max(0, produced - delivered);

    document.getElementById('itemQtyDisplay').value = qty;
    document.getElementById('availableQty').value = available;
    document.getElementById('deliveryQty').max = available;

    document.getElementById('itemQtyRow').style.display = 'block';
    document.getElementById('availableRow').style.display = 'block';

    document.getElementById('deliveryQty').value = '';
    document.getElementById('deliveryQty').classList.remove('is-invalid');
    document.getElementById('deliveryError').textContent = '';
});

document.querySelector('#createDeliveryModal form').addEventListener('submit', function(e) {
    const poSelect = document.getElementById('poSelect');
    if (!poSelect.value) {
        e.preventDefault();
        alert('Please select a PO');
        return;
    }

    const poiSelect = document.getElementById('poiSelect');
    if (poiSelect.offsetParent !== null && !poiSelect.value) {
        e.preventDefault();
        alert('Please select an item');
        return;
    }

    const available = parseInt(document.getElementById('availableQty').value) || 0;
    const deliveryQty = parseInt(document.getElementById('deliveryQty').value) || 0;

    if (deliveryQty > available) {
        e.preventDefault();
        document.getElementById('deliveryQty').classList.add('is-invalid');
        document.getElementById('deliveryError').textContent = 'Cannot deliver ' + deliveryQty + '. Available: ' + available;
        return;
    }

    if (deliveryQty <= 0) {
        e.preventDefault();
        alert('Delivery quantity must be at least 1');
        return;
    }
});

document.querySelectorAll('.print-delivery-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var deliveryId = this.dataset.deliveryId;
        window.open('?controller=warehouse&action=printDelivery&id=' + deliveryId, '_blank');
    });
});
</script>