<h4><i class="bi bi-truck me-2"></i>Delivered PO</h4>

<div class="d-flex justify-content-end mb-3">
    <div class="search-box" style="width: 300px;">
        <i class="bi bi-search"></i>
        <input type="text" id="searchDelivered" class="form-control" placeholder="Search...">
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
                    <th class="sortable" data-sort="total">PO Quantity <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="delivered">Delivered <i class="bi bi-chevron-expand"></i></th>
                    <th>Remaining Balance</th>
                    <th>Type</th>
                    <th>DR Number</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="deliveredTableBody">
                <?php foreach ($purchase_orders as $po):
                    $items = $po_items_map[$po['po_id']] ?? [];
                ?>
                <?php 
                    $total = $po['total_quantity'] ?? 0;
                    $delivered = $po['delivered_quantity'] ?? 0;
                    $balance = $total - $delivered;
                    if ($balance < 0) $balance = 0;
                    $percent = $total > 0 ? round(($delivered / $total) * 100) : 0;
                ?>
                <tr>
                    <td><strong class="text-primary"><?= htmlspecialchars($po['customer_po_number']) ?></strong></td>
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
                                $itemQty = $item['quantity'] ?? 0;
                            ?>
                                <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
                                <small><?= $itemQty ?></small>
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
                            ?>
                                <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
                                <small class="text-muted"><?= $itemDelivered ?>/<?= $itemQty ?></small>
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
                                $itemBalance = max(0, $itemQty - $itemDelivered);
                            ?>
                                <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
                                <small class="badge <?= $itemBalance <= 0 ? 'bg-success' : 'bg-warning' ?>">
                                    <?= $itemBalance ?>
                                </small>
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
                    <td>
                        <?php
                        $drNumbers = $dr_numbers_map[$po['po_id']] ?? [];
                        if (!empty($drNumbers)):
                        ?>
                            <small><?= htmlspecialchars(implode(', ', $drNumbers)) ?></small>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-primary view-po-btn" data-po-id="<?= $po['po_id'] ?>">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($purchase_orders)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No purchase orders found</td></tr>
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
            <a class="page-link" href="?controller=admin&action=delivered&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.view-po-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const poId = this.getAttribute('data-po-id');
            
            fetch('?controller=warehouse&action=getPODetails&id=' + poId)
                .then(function(response) {
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
                    
                    const tbody = document.getElementById('viewPOItems');
                    tbody.innerHTML = '';
                    if (items && items.length > 0) {
                        items.forEach(function(item) {
                            const qty = item.quantity || 0;
                            const itemDelivered = item.delivered_quantity || 0;
                            const remaining = Math.max(0, qty - itemDelivered);
                            const itemPercent = qty > 0 ? Math.round((itemDelivered / qty) * 100) : 0;
                            const barClass = itemPercent >= 100 ? 'bg-success' : 'bg-warning';
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
                                        '<small class="text-muted">' + itemDelivered + '/' + qty + '</small>' +
                                    '</div>' +
                                '</td>' +
                                '<td>' +
                                    '<small class="badge ' + (remaining <= 0 ? 'bg-success' : 'bg-warning') + '">' + remaining + '</small>' +
                                '</td>' +
                                '</tr>';
                            tbody.innerHTML += row;
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No items found</td></tr>';
                    }
                    
                    const modal = new bootstrap.Modal(document.getElementById('viewPOModal'));
                    modal.show();
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    alert('Failed to load PO details: ' + error.message);
                });
        });
    });
});

document.getElementById('searchDelivered').addEventListener('keyup', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#deliveredTableBody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
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
                                <th>Delivery Progress</th>
                                <th>Remaining</th>
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