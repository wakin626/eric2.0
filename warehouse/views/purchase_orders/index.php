<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPOModal">
            <i class="bi bi-plus-circle me-1"></i> Create New PO
        </button>
    </div>
    <div class="search-box" style="width: 300px;">
        <i class="bi bi-search"></i>
        <input type="text" id="searchPO" class="form-control" placeholder="Search PO...">
    </div>
</div>

<div class="card data-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="po_number">PO Number <i class="bi bi-chevron-expand"></i></th>
                        <th class="sortable" data-sort="customer">Customer <i class="bi bi-chevron-expand"></i></th>
                        <th>Item</th>
                        <th>Production Status</th>
                        <th>Type</th>
                        <th class="sortable" data-sort="created_by">Created By <i class="bi bi-chevron-expand"></i></th>
                        <th class="sortable" data-sort="date">Date Created <i class="bi bi-chevron-expand"></i></th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="poTableBody">
                    <?php foreach ($purchase_orders as $po):
                        $items = $po_items_map[$po['po_id']] ?? [];
                    ?>
                    <tr>
                        <td><strong class="text-primary"><?= $po['customer_po_number'] ?></strong></td>
                        <td><?= htmlspecialchars($po['customer_name'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $idx => $item): ?>
                                    <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
                                    <small><?= htmlspecialchars($item['item_description'] ?? '-') ?></small>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $idx => $item):
                                    $qty = $item['quantity'] ?? 0;
                                    $itemProduced = $item['produced_quantity'] ?? 0;
                                    $itemPercent = $qty > 0 ? round(($itemProduced / $qty) * 100) : 0;
                                ?>
                                    <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 12px; width: 50px;">
                                            <div class="progress-bar <?= $itemPercent >= 100 ? 'bg-success' : 'bg-warning' ?>" style="width: <?= $itemPercent ?>%"></div>
                                        </div>
                                        <small class="text-muted text-nowrap"><?= $itemProduced ?>/<?= $qty ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($po['production_type'] ?? 'normal') === 'advance'): ?>
                                <span class="badge bg-info">Advance</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Normal</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($po['requested_by_name'] ?? '-') ?></td>
                        <td><?= date('Y-m-d', strtotime($po['date_created'])) ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary view-po-btn" data-po-id="<?= $po['po_id'] ?>">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($purchase_orders)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No purchase orders found</td></tr>
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
            <a class="page-link" href="?controller=warehouse&action=purchaseOrders&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<div class="modal fade" id="createPOModal">
    <div class="modal-dialog modal-xl modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cart3 me-2"></i>Create Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?controller=warehouse&action=createPO">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <select id="customerSelect" name="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                            <?php 
                            $customers = (new \App\Models\WarehouseModel())->getCustomers();
                            foreach ($customers as $c): 
                            ?>
                                <option value="<?= $c['customer_id'] ?>"
                                    data-code="<?= htmlspecialchars($c['customer_code']) ?>"
                                    data-name="<?= htmlspecialchars($c['customer_name']) ?>"
                                    data-address="<?= htmlspecialchars($c['customer_address']) ?>"
                                    data-tin="<?= htmlspecialchars($c['customer_tin'] ?? '') ?>">
                                    <?= $c['customer_code'] ?> - <?= $c['customer_name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="customerDetails" class="row g-2 mb-3 d-none">
                        <div class="col-md-2">
                            <label class="form-label">Customer Code</label>
                            <input type="text" id="customerCode" class="form-control" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Customer Name</label>
                            <input type="text" id="customerName" class="form-control" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Delivery Address</label>
                            <input type="text" id="customerAddress" class="form-control" readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Customer TIN</label>
                            <input type="text" id="customerTin" class="form-control" readonly>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Terms (Days)</label>
                            <div class="input-group">
                                <input type="number" name="customer_terms" id="customerTerms" class="form-control" min="0" placeholder="e.g. 30" required>
                                <span class="input-group-text">days</span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Customer PO Number</label>
                            <input type="text" name="customer_po_number" class="form-control" placeholder="Enter PO Number" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Customer PO Date</label>
                            <input type="date" name="customer_po_date" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Production Process</label>
                            <select name="production_type" class="form-select" required>
                                <option value="normal">Normal Production</option>
                                <option value="advance">Advance Production</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Items</label>
                        <div id="itemsContainer">
                            <div class="row g-2 mb-2 item-row align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">Item</label>
                                    <select name="item_id[]" class="form-select item-select" required>
                                        <option value="">Select Item</option>
                                        <?php 
                                        $items = (new \App\Models\WarehouseModel())->getItems();
                                        foreach ($items as $i): 
                                        ?>
                                            <option value="<?= $i['item_id'] ?>"
                                                data-code="<?= htmlspecialchars($i['item_code']) ?>"
                                                data-description="<?= htmlspecialchars($i['item_description']) ?>"
                                                data-uom="<?= htmlspecialchars($i['item_uom']) ?>"
                                                data-price="<?= $i['item_amount'] ?>">
                                                <?= $i['item_code'] ?> - <?= $i['item_description'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Item Number</label>
                                    <input type="text" class="form-control item-code" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Description</label>
                                    <input type="text" class="form-control item-description" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">UOM</label>
                                    <input type="text" name="uom[]" class="form-control item-uom" readonly>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">Qty</label>
                                    <input type="number" name="quantity[]" class="form-control" min="1" placeholder="Qty" required>
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-item mt-4"><i class="bi bi-trash"></i></button>
                                </div>
                                <input type="hidden" name="unit_price[]" class="unit-price">
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addItemBtn"><i class="bi bi-plus"></i> Add Item</button>
                    </div>
                    <input type="hidden" name="items_json" id="itemsJson">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Create PO</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="viewPOModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>PO Details - <span id="viewPONumber"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Customer Code:</strong></p>
                        <p class="text-muted" id="viewCustomerCode">-</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Customer Name:</strong></p>
                        <p class="text-muted" id="viewCustomerName">-</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Customer TIN:</strong></p>
                        <p class="text-muted" id="viewCustomerTin">-</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Terms:</strong></p>
                        <p class="text-muted" id="viewCustomerTerms">-</p>
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
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody id="viewPOItems">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('createPOModal').addEventListener('shown.bs.modal', function() {
    updateItemDropdowns();
});

document.getElementById('createPOModal').addEventListener('hidden.bs.modal', function() {
    const form = this.querySelector('form');
    form.reset();

    const customerDetails = document.getElementById('customerDetails');
    customerDetails.classList.add('d-none');

    const customerCode = document.getElementById('customerCode');
    const customerName = document.getElementById('customerName');
    const customerAddress = document.getElementById('customerAddress');
    const customerTin = document.getElementById('customerTin');
    const customerTerms = document.getElementById('customerTerms');
    customerCode.value = customerName.value = customerAddress.value = customerTin.value = '';
    customerTerms.value = '';

    const itemsContainer = document.getElementById('itemsContainer');
    const firstRow = itemsContainer.querySelector('.item-row');
    itemsContainer.innerHTML = '';
    itemsContainer.appendChild(firstRow.cloneNode(true));
    const newFirstRow = itemsContainer.querySelector('.item-row');
    newFirstRow.querySelectorAll('input').forEach(el => el.value = '');
    newFirstRow.querySelector('select').value = '';
    setupItemRow(newFirstRow);

    updateRemoveButtons();
    updateItemDropdowns();

    document.getElementById('itemsJson').value = '';
});

document.getElementById('searchPO').addEventListener('keyup', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#poTableBody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
});

const customerSelect = document.getElementById('customerSelect');
const customerDetails = document.getElementById('customerDetails');
const customerCode = document.getElementById('customerCode');
const customerName = document.getElementById('customerName');
const customerAddress = document.getElementById('customerAddress');
const customerTin = document.getElementById('customerTin');
const customerTerms = document.getElementById('customerTerms');

customerSelect.addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    if (!this.value) {
        customerDetails.classList.add('d-none');
        customerCode.value = customerName.value = customerAddress.value = customerTin.value = '';
        customerTerms.value = '';
        return;
    }
    customerCode.value = option.dataset.code || '';
    customerName.value = option.dataset.name || '';
    customerAddress.value = option.dataset.address || '';
    customerTin.value = option.dataset.tin || '';
    customerTerms.value = '';
    customerDetails.classList.remove('d-none');
});

function updateRemoveButtons() {
    const rows = document.querySelectorAll('.item-row');
    rows.forEach((row) => {
        const removeButton = row.querySelector('.remove-item');
        if (removeButton) {
            removeButton.style.display = rows.length > 1 ? 'inline-flex' : 'none';
            removeButton.disabled = rows.length <= 1;
        }
    });
}

function updateItemDropdowns() {
    const selects = document.querySelectorAll('.item-select');
    const selectedValues = [];
    selects.forEach(function(sel) {
        if (sel.value) selectedValues.push(sel.value);
    });
    selects.forEach(function(sel) {
        Array.from(sel.options).forEach(function(opt) {
            if (!opt.value) return;
            const isSelectedElsewhere = selectedValues.includes(opt.value) && sel.value !== opt.value;
            opt.disabled = isSelectedElsewhere;
        });
    });
}

function setupItemRow(row) {
    const select = row.querySelector('.item-select');
    const codeInput = row.querySelector('.item-code');
    const descInput = row.querySelector('.item-description');
    const uomInput = row.querySelector('.item-uom');
    const priceInput = row.querySelector('.unit-price');

    select.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        codeInput.value = selected.dataset.code || '';
        descInput.value = selected.dataset.description || '';
        uomInput.value = selected.dataset.uom || '';
        priceInput.value = selected.dataset.price || '';
        updateItemDropdowns();
    });

    const removeButton = row.querySelector('.remove-item');
    removeButton.addEventListener('click', function() {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length > 1) {
            row.remove();
            updateRemoveButtons();
            updateItemDropdowns();
        }
    });
}

const initialRow = document.querySelector('.item-row');
if (initialRow) {
    setupItemRow(initialRow);
}
updateRemoveButtons();
updateItemDropdowns();

document.getElementById('addItemBtn').addEventListener('click', function() {
    const container = document.getElementById('itemsContainer');
    const itemTemplate = document.querySelector('.item-row').cloneNode(true);
    itemTemplate.querySelectorAll('input').forEach(el => el.value = '');
    itemTemplate.querySelector('select').value = '';
    setupItemRow(itemTemplate);
    container.appendChild(itemTemplate);
    updateRemoveButtons();
    updateItemDropdowns();
});

document.querySelector('form').addEventListener('submit', function() {
    const items = [];
    document.querySelectorAll('.item-row').forEach(row => {
        const itemId = row.querySelector('[name="item_id[]"]').value;
        const quantity = row.querySelector('[name="quantity[]"]').value;
        const unitPrice = row.querySelector('.unit-price').value;
        const uom = row.querySelector('[name="uom[]"]').value;
        const itemCode = row.querySelector('.item-code').value;
        const itemDescription = row.querySelector('.item-description').value;

        if (itemId && quantity) {
            items.push({
                item_id: itemId,
                quantity: quantity,
                unit_price: unitPrice,
                uom: uom,
                item_code: itemCode,
                item_description: itemDescription
            });
        }
    });
    document.getElementById('itemsJson').value = JSON.stringify(items);
});

document.querySelectorAll('.view-po-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const poId = this.dataset.poId || this.getAttribute('data-po-id');
        console.log('View clicked, poId:', poId);
        fetch('?controller=warehouse&action=getPODetails&id=' + poId)
            .then(function(response) {
                console.log('Response:', response.status);
                return response.json();
            })
            .then(function(data) {
                const po = data.po;
                const items = data.po_items;
                
                document.getElementById('viewPONumber').textContent = po.customer_po_number || '-';
                document.getElementById('viewCustomerCode').textContent = po.customer_code || '-';
                document.getElementById('viewCustomerName').textContent = po.customer_name || '-';
                document.getElementById('viewCustomerTin').textContent = po.customer_tin || '-';
                document.getElementById('viewCustomerTerms').textContent = (po.customer_terms || 0) + ' days';
                
                const total = po.total_quantity || 0;
                const produced = po.produced_quantity || 0;
                
                const tbody = document.getElementById('viewPOItems');
                tbody.innerHTML = '';
                if (items && items.length > 0) {
                    items.forEach(function(item, idx) {
                        const qty = item.quantity || 0;
                        const itemProduced = item.produced_quantity || 0;
                        const itemPercent = qty > 0 ? Math.round((itemProduced / qty) * 100) : 0;
                        const barClass = itemPercent >= 100 ? 'bg-success' : 'bg-warning';
                        const lineTotal = item.quantity * item.unit_price;
                        const row = '<tr>' +
                            '<td>' + (item.item_code || '-') + '</td>' +
                            '<td>' + (item.item_description || '-') + '</td>' +
                            '<td>' + (item.item_uom || '-') + '</td>' +
                            '<td>' + qty + '</td>' +
                            '<td>' +
                                '<div class="d-flex align-items-center">' +
                                    '<div class="progress flex-grow-1 me-2" style="height: 14px; width: 80px;">' +
                                        '<div class="progress-bar ' + barClass + '" style="width: ' + itemPercent + '%"></div>' +
                                    '</div>' +
                                    '<small class="text-muted">' + itemProduced + '/' + qty + '</small>' +
                                '</div>' +
                            '</td>' +
                            '</tr>';
                        tbody.innerHTML += row;
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No items found</td></tr>';
                }
                
                const modal = new bootstrap.Modal(document.getElementById('viewPOModal'));
                modal.show();
            })
            .catch(function(error) {
                console.error('Error:', error);
                alert('Failed to load PO details');
            });
    });
});

document.querySelectorAll('.sortable').forEach(th => {
    th.addEventListener('click', function() {
        const table = this.closest('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const col = this.cellIndex;
        const asc = !this.classList.contains('asc');
        
        table.querySelectorAll('.sortable').forEach(h => {
            h.classList.remove('asc', 'desc');
            h.querySelector('i').className = 'bi bi-chevron-expand';
        });
        
        this.classList.add(asc ? 'asc' : 'desc');
        this.querySelector('i').className = asc ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
        
        rows.sort((a, b) => {
            let aVal = a.cells[col].textContent.trim();
            let bVal = b.cells[col].textContent.trim();
            if (!isNaN(aVal) && !isNaN(bVal)) {
                aVal = parseFloat(aVal);
                bVal = parseFloat(bVal);
            }
            return asc ? aVal.localeCompare(bVal, undefined, {numeric: true}) : bVal.localeCompare(aVal, undefined, {numeric: true});
        });
        
        rows.forEach(row => tbody.appendChild(row));
    });
});
</script>