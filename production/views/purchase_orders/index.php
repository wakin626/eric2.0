<h4><i class="bi bi-cart3 me-2"></i>Customer PO</h4>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2 flex-wrap">
        <select id="filterCustomer" class="form-select form-select-sm filter-select" style="width:200px">
            <option value="">All Customers</option>
        </select>
        <select id="filterItem" class="form-select form-select-sm filter-select" style="width:200px">
            <option value="">All Items</option>
        </select>
        <input type="date" id="filterDate" class="form-control form-control-sm" style="width:160px" title="Filter by PO Date">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearFilters"><i class="bi bi-x-circle me-1"></i>Clear</button>
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
                    <th class="sortable" data-sort="po_date">PO Date <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="customer">Customer <i class="bi bi-chevron-expand"></i></th>
                    <th>Item</th>
                    <th class="sortable" data-sort="progress">Produced PO QTY <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="delivered">Delivered PO QTY <i class="bi bi-chevron-expand"></i></th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="poTableBody">
                <?php foreach ($purchase_orders as $po):
                    $items = $po_items_map[$po['po_id']] ?? [];
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($po['customer_po_number']) ?></strong></td>
                    <td><?= date('Y-m-d', strtotime($po['customer_po_date'])) ?></td>
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
                <small class="text-muted text-nowrap"><?= $itemProduced ?>/<?= $qty ?> pcs</small>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <small class="text-muted">-</small>
    <?php endif; ?>
</td>
<td>
    <?php if (!empty($items)): ?>
        <?php foreach ($items as $idx => $item):
            $itemQty = $item['quantity'] ?? 0;
            $itemDelivered = $item['delivered_quantity'] ?? 0;
            $itemRemaining = max(0, $itemQty - $itemDelivered);
        ?>
            <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
            <?php $conv = $item['uom_conversion'] ?? null; ?>
            <small class="text-nowrap"><?= $itemDelivered ?>/<?= $itemQty ?> pcs, <?= $conv ? round($itemDelivered / $conv, 2) . '/' . round($itemQty / $conv, 2) . ' cs' : '—/—' ?></small>
        <?php endforeach; ?>
    <?php else: ?>
        <small class="text-muted">-</small>
    <?php endif; ?>
</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-primary view-po-btn" data-po-id="<?= $po['po_id'] ?>">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success update-po-btn" data-po-id="<?= $po['po_id'] ?>" data-produced="<?= $po['produced_quantity'] ?? 0 ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($purchase_orders)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No purchase orders found</td></tr>
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
            <a class="page-link" href="?controller=production&action=purchaseOrders&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

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

<div class="modal fade" id="updatePOModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Update Production - <span id="updatePONumber"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <p class="mb-1"><strong>Customer:</strong> <span id="updateCustomerName"></span></p>
                </div>
                <form method="POST" action="?controller=production&action=updateQuantity" id="updatePOForm" novalidate>
                    <input type="hidden" name="po_id" id="updatePoIdInput" value="">
                    <input type="hidden" name="from" value="purchaseOrders">

                    <!-- Single item mode with lot rows -->
                    <div id="singleItemGroup">
                        <input type="hidden" name="poi_id" id="updatePoiIdInput" value="">
                        <div class="mb-3">
                            <p class="mb-1"><strong>Required Quantity:</strong> <span id="updateTotalQty">-</span></p>
                            <p class="mb-1"><strong>Current Produced:</strong> <span id="updateCurrentProduced">-</span></p>
                        </div>
                        <label class="form-label mb-2">Lot Entries</label>
                        <div id="singleLotContainer">
                            <div class="row g-2 mb-2 align-items-end single-lot-row">
                                <div class="col-md-5">
                                    <label class="form-label">Lot Number</label>
                                    <input type="text" name="lot_number[]" class="form-control" placeholder="e.g. LOT-001" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Add Quantity</label>
                                    <input type="number" name="added_quantity[]" class="form-control" min="1" required>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-single-lot" style="display:none;"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-1" id="addSingleLotBtn"><i class="bi bi-plus"></i> Add Lot</button>
                    </div>

                    <!-- Bulk items mode -->
                    <div id="bulkItemsGroup" class="d-none">
                        <label class="form-label mb-2">Items</label>
                        <div id="bulkItemsContainer"></div>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addBulkItemBtn"><i class="bi bi-plus"></i> Add Item</button>
                    </div>

                    <div class="modal-footer px-0 pb-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Production PO page loaded');
    
    document.querySelectorAll('.view-po-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const poId = this.getAttribute('data-po-id');
            console.log('View button clicked, poId:', poId);
            fetch('?controller=production&action=getPODetails&id=' + poId)
                .then(function(response) {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('HTTP error ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
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
                                        '<small class="text-muted">' + itemProduced + '/' + qty + ' pcs</small>' +
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

    function createBulkRow() {
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 align-items-end bulk-item-row';
        row.innerHTML =
            '<div class="col-md-4">' +
                '<label class="form-label">Item</label>' +
                '<select name="poi_id[]" class="form-select bulk-item-select" required>' +
                    '<option value="">-- Select Item --</option>' +
                '</select>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<label class="form-label">Required / Produced</label>' +
                '<input type="text" class="form-control" readonly>' +
            '</div>' +
            '<div class="col-md-3">' +
                '<label class="form-label">Lot Number</label>' +
                '<input type="text" name="lot_number[]" class="form-control" placeholder="e.g. LOT-001">' +
            '</div>' +
            '<div class="col-md-2">' +
                '<label class="form-label">Add Quantity</label>' +
                '<input type="number" name="added_quantity[]" class="form-control bulk-qty" min="0" required>' +
            '</div>' +
            '<div class="col-md-1 text-end">' +
                '<button type="button" class="btn btn-outline-danger btn-sm mt-4 remove-bulk-item"><i class="bi bi-trash"></i></button>' +
            '</div>';
        return row;
    }

    function populateBulkSelect(selectEl, items) {
        selectEl.innerHTML = '<option value="">-- Select Item --</option>';
        items.forEach(function(item) {
            const opt = document.createElement('option');
            opt.value = item.poi_id;
            opt.textContent = item.item_description + ' (' + (item.produced_quantity || 0) + '/' + item.quantity + ')';
            opt.setAttribute('data-qty', item.quantity);
            opt.setAttribute('data-produced', item.produced_quantity || 0);
            selectEl.appendChild(opt);
        });
    }

    function updateBulkRowInfo(selectEl) {
        const row = selectEl.closest('.bulk-item-row');
        const infoInput = row.querySelector('input[readonly]');
        const selected = selectEl.options[selectEl.selectedIndex];
        if (selectEl.value && selected) {
            infoInput.value = selected.getAttribute('data-qty') + ' / ' + selected.getAttribute('data-produced');
        } else {
            infoInput.value = '';
        }
    }

    function updateBulkRemoveButtons() {
        const rows = document.querySelectorAll('.bulk-item-row');
        rows.forEach(function(row) {
            const btn = row.querySelector('.remove-bulk-item');
            if (btn) {
                btn.style.display = rows.length > 1 ? 'inline-flex' : 'none';
            }
        });
    }

    document.getElementById('addSingleLotBtn').addEventListener('click', function() {
        const container = document.getElementById('singleLotContainer');
        const template = container.querySelector('.single-lot-row').cloneNode(true);
        template.querySelectorAll('input').forEach(el => el.value = '');
        template.querySelector('.remove-single-lot').style.display = '';
        container.appendChild(template);
        updateSingleLotRemoveButtons();
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-single-lot')) {
            const row = e.target.closest('.single-lot-row');
            const container = document.getElementById('singleLotContainer');
            if (container.querySelectorAll('.single-lot-row').length > 1) {
                row.remove();
                updateSingleLotRemoveButtons();
            }
        }
    });

    function updateSingleLotRemoveButtons() {
        const rows = document.querySelectorAll('#singleLotContainer .single-lot-row');
        rows.forEach(function(row) {
            const btn = row.querySelector('.remove-single-lot');
            if (btn) btn.style.display = rows.length > 1 ? '' : 'none';
        });
    }

    document.querySelectorAll('.update-po-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const poId = this.getAttribute('data-po-id');
            
            fetch('?controller=production&action=getPODetails&id=' + poId)
                .then(response => response.json())
                .then(data => {
                    const po = data.po;
                    const items = data.po_items || [];
                    
                    document.getElementById('updatePONumber').textContent = po.customer_po_number || '-';
                    document.getElementById('updateCustomerName').textContent = po.customer_name || '-';
                    document.getElementById('updatePoIdInput').value = poId;
                    document.getElementById('updatePoiIdInput').value = '';
                    document.getElementById('updateTotalQty').textContent = '-';
                    document.getElementById('updateCurrentProduced').textContent = '-';

                    const singleGroup = document.getElementById('singleItemGroup');
                    const bulkGroup = document.getElementById('bulkItemsGroup');
                    const bulkContainer = document.getElementById('bulkItemsContainer');
                    const singleLotContainer = document.getElementById('singleLotContainer');
                    
                    if (items.length > 1) {
                        singleGroup.classList.add('d-none');
                        bulkGroup.classList.remove('d-none');
                        bulkContainer.innerHTML = '';
                        window._currentBulkItems = items;

                        document.getElementById('updatePoiIdInput').disabled = true;
                        singleLotContainer.querySelectorAll('input').forEach(function(el) { el.disabled = true; });

                        const firstRow = createBulkRow();
                        const firstSelect = firstRow.querySelector('.bulk-item-select');
                        populateBulkSelect(firstSelect, items);
                        firstSelect.addEventListener('change', function() {
                            updateBulkRowInfo(this);
                        });
                        bulkContainer.appendChild(firstRow);
                        updateBulkRemoveButtons();
                    } else if (items.length === 1) {
                        singleGroup.classList.remove('d-none');
                        bulkGroup.classList.add('d-none');
                        bulkContainer.innerHTML = '';
                        window._currentBulkItems = [];
                        const item = items[0];
                        document.getElementById('updatePoiIdInput').disabled = false;
                        document.getElementById('updatePoiIdInput').value = item.poi_id;
                        document.getElementById('updateTotalQty').textContent = item.quantity || 0;
                        document.getElementById('updateCurrentProduced').textContent = item.produced_quantity || 0;

                        singleLotContainer.innerHTML = '<div class="row g-2 mb-2 align-items-end single-lot-row">' +
                            '<div class="col-md-5"><label class="form-label">Lot Number</label><input type="text" name="lot_number[]" class="form-control" placeholder="e.g. LOT-001" required></div>' +
                            '<div class="col-md-5"><label class="form-label">Add Quantity</label><input type="number" name="added_quantity[]" class="form-control" min="1" required></div>' +
                            '<div class="col-md-2 text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-single-lot" style="display:none;"><i class="bi bi-trash"></i></button></div>' +
                            '</div>';
                        updateSingleLotRemoveButtons();
                    } else {
                        singleGroup.classList.remove('d-none');
                        bulkGroup.classList.add('d-none');
                        bulkContainer.innerHTML = '';
                        window._currentBulkItems = [];
                        document.getElementById('updatePoiIdInput').disabled = false;
                        singleLotContainer.innerHTML = '<div class="row g-2 mb-2 align-items-end single-lot-row">' +
                            '<div class="col-md-5"><label class="form-label">Lot Number</label><input type="text" name="lot_number[]" class="form-control" placeholder="e.g. LOT-001" required></div>' +
                            '<div class="col-md-5"><label class="form-label">Add Quantity</label><input type="number" name="added_quantity[]" class="form-control" min="1" required></div>' +
                            '<div class="col-md-2 text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-single-lot" style="display:none;"><i class="bi bi-trash"></i></button></div>' +
                            '</div>';
                        updateSingleLotRemoveButtons();
                    }
                    
                    const modal = new bootstrap.Modal(document.getElementById('updatePOModal'));
                    modal.show();
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    alert('Failed to load PO details');
                });
        });
    });

    document.getElementById('addBulkItemBtn').addEventListener('click', function() {
        const items = window._currentBulkItems || [];
        const container = document.getElementById('bulkItemsContainer');
        const row = createBulkRow();
        const select = row.querySelector('.bulk-item-select');
        populateBulkSelect(select, items);
        select.addEventListener('change', function() {
            updateBulkRowInfo(this);
        });
        container.appendChild(row);
        updateBulkRemoveButtons();
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-bulk-item')) {
            const container = document.getElementById('bulkItemsContainer');
            const rows = container.querySelectorAll('.bulk-item-row');
            if (rows.length > 1) {
                e.target.closest('.bulk-item-row').remove();
                updateBulkRemoveButtons();
            }
        }
    });

    document.getElementById('updatePOForm').addEventListener('submit', function(e) {
        const singleGroup = document.getElementById('singleItemGroup');
        const bulkGroup = document.getElementById('bulkItemsGroup');

        if (!bulkGroup.classList.contains('d-none')) {
            let hasSelection = false;
            let hasQuantity = false;
            document.querySelectorAll('.bulk-item-select').forEach(function(sel) {
                if (sel.value) hasSelection = true;
            });
            document.querySelectorAll('.bulk-qty').forEach(function(inp) {
                if (inp.value && parseInt(inp.value) > 0) hasQuantity = true;
            });
            if (!hasSelection) {
                e.preventDefault();
                alert('Please select at least one item.');
                return;
            }
            if (!hasQuantity) {
                e.preventDefault();
                alert('Please enter a quantity for at least one item.');
                return;
            }
        } else {
            let hasLot = false;
            let hasQty = false;
            document.querySelectorAll('#singleLotContainer input[name="lot_number[]"]').forEach(function(inp) {
                if (inp.value.trim()) hasLot = true;
            });
            document.querySelectorAll('#singleLotContainer input[name="added_quantity[]"]').forEach(function(inp) {
                if (inp.value && parseInt(inp.value) > 0) hasQty = true;
            });
            if (!hasLot) {
                e.preventDefault();
                alert('Please enter a lot number.');
                return;
            }
            if (!hasQty) {
                e.preventDefault();
                alert('Please enter an add quantity.');
                return;
            }
        }
    });

    document.getElementById('updatePOModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('updatePoiIdInput').disabled = false;
        window._currentBulkItems = [];
    });
});

document.getElementById('searchPO').addEventListener('keyup', function() {
    applyFilters();
});

function populateFilters() {
    const customers = new Set();
    const items = new Set();
    document.querySelectorAll('#poTableBody tr').forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        const cust = row.cells[2] ? row.cells[2].textContent.trim() : '';
        if (cust) customers.add(cust);
        const itemCell = row.cells[3];
        if (itemCell) {
            const divs = itemCell.querySelectorAll('div');
            if (divs.length > 0) {
                divs.forEach(d => {
                    const t = d.textContent.trim();
                    if (t && t !== '-') items.add(t);
                });
            } else {
                itemCell.querySelectorAll('small').forEach(s => {
                    const t = s.textContent.trim();
                    if (t && t !== '-') items.add(t);
                });
            }
        }
    });
    const custSel = document.getElementById('filterCustomer');
    customers.forEach(c => { const o = document.createElement('option'); o.value = c; o.textContent = c; custSel.appendChild(o); });
    const itemSel = document.getElementById('filterItem');
    items.forEach(i => { const o = document.createElement('option'); o.value = i; o.textContent = i; itemSel.appendChild(o); });
}

function applyFilters() {
    const custFilter = document.getElementById('filterCustomer').value.toLowerCase();
    const itemFilter = document.getElementById('filterItem').value.toLowerCase();
    const dateFilter = document.getElementById('filterDate').value;
    const searchQuery = document.getElementById('searchPO').value.toLowerCase();
    document.querySelectorAll('#poTableBody tr').forEach(row => {
        if (row.querySelector('td[colspan]')) { row.style.display = ''; return; }
        const cust = row.cells[2] ? row.cells[2].textContent.trim().toLowerCase() : '';
        const itemText = row.cells[3] ? row.cells[3].textContent.trim().toLowerCase() : '';
        const poDate = row.cells[1] ? row.cells[1].textContent.trim() : '';
        const rowText = row.textContent.toLowerCase();
        let show = true;
        if (custFilter && !cust.includes(custFilter)) show = false;
        if (itemFilter && !itemText.includes(itemFilter)) show = false;
        if (dateFilter && poDate !== dateFilter) show = false;
        if (searchQuery && !rowText.includes(searchQuery)) show = false;
        row.style.display = show ? '' : 'none';
    });
}

document.getElementById('filterCustomer').addEventListener('change', applyFilters);
document.getElementById('filterItem').addEventListener('change', applyFilters);
document.getElementById('filterDate').addEventListener('change', applyFilters);
document.getElementById('clearFilters').addEventListener('click', function() {
    document.getElementById('filterCustomer').value = '';
    document.getElementById('filterItem').value = '';
    document.getElementById('filterDate').value = '';
    document.getElementById('searchPO').value = '';
    applyFilters();
});

document.addEventListener('DOMContentLoaded', populateFilters);

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