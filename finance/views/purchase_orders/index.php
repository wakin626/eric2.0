<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex gap-2 flex-wrap">
        <select id="filterCustomer" class="form-select form-select-sm filter-select" style="width:200px">
            <option value="">All Customers</option>
        </select>
        <select id="filterItem" class="form-select form-select-sm filter-select" style="width:200px">
            <option value="">All Items</option>
        </select>
        <input type="date" id="filterDate" class="form-control form-control-sm" style="width:160px" title="Filter by Date Created">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearFilters"><i class="bi bi-x-circle me-1"></i>Clear</button>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted">Showing <?= count($purchase_orders) ?> of <?= $total ?> purchase orders</span>
        <div class="search-box" style="width: 300px;">
            <i class="bi bi-search"></i>
            <input type="text" id="searchPO" class="form-control" placeholder="Search PO...">
        </div>
    </div>
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="sortable" data-sort="po_number">PO Number</th>
                    <th>Customer</th>
                    <th>Item</th>
<th class="sortable" data-sort="produced">Produced PO QTY</th>
<th class="sortable" data-sort="delivered">Delivered PO QTY</th>
                    <th>Type</th>
                    <th class="sortable" data-sort="date">Date Created</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="poTableBody">
                <?php foreach ($purchase_orders as $po): 
                    $items = $po_items_map[$po['po_id']] ?? [];
                ?>
                <tr>
                    <td><strong class="text-primary"><?= htmlspecialchars($po['customer_po_number']) ?></strong></td>
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
                            ?>
                                <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
<?php $conv = $item['uom_conversion'] ?? null; ?>
<small class="text-muted"><?= $itemDelivered ?>/<?= $itemQty ?> pcs, <?= $conv ? round($itemDelivered / $conv, 2) . '/' . round($itemQty / $conv, 2) . ' cs' : '—/—' ?></small>
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
                    <td><?= date('Y-m-d', strtotime($po['date_created'])) ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewPODetails(<?= $po['po_id'] ?>)" title="View Details">
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
            <a class="page-link" href="?controller=finance&action=purchaseOrders&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<div class="modal fade" id="viewPOModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>PO Details - <span id="viewPONumber"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="poDetailBody">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('searchPO').addEventListener('keyup', function() {
    applyFilters();
});

function populateFilters() {
    const customers = new Set();
    const items = new Set();
    document.querySelectorAll('#poTableBody tr').forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        const cust = row.cells[1] ? row.cells[1].textContent.trim() : '';
        if (cust) customers.add(cust);
        const itemCell = row.cells[2];
        if (itemCell) {
            itemCell.querySelectorAll('small').forEach(s => {
                const t = s.textContent.trim();
                if (t && t !== '-') items.add(t);
            });
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
        const cust = row.cells[1] ? row.cells[1].textContent.trim().toLowerCase() : '';
        const itemText = row.cells[2] ? row.cells[2].textContent.trim().toLowerCase() : '';
        const poDate = row.cells[6] ? row.cells[6].textContent.trim() : '';
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
    th.style.cursor = 'pointer';
    th.addEventListener('click', function() {
        const table = document.querySelector('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const idx = Array.from(this.parentNode.children).indexOf(this);
        const asc = this.dataset.sort !== 'asc';
        this.dataset.sort = asc ? 'asc' : 'desc';

        rows.sort((a, b) => {
            let aVal = a.children[idx].textContent.trim();
            let bVal = b.children[idx].textContent.trim();
            if (!isNaN(aVal) && !isNaN(bVal)) {
                return asc ? aVal - bVal : bVal - aVal;
            }
            return asc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
        });
        rows.forEach(r => tbody.appendChild(r));
    });
});

function viewPODetails(poId) {
    const modal = new bootstrap.Modal(document.getElementById('viewPOModal'));
    const body = document.getElementById('poDetailBody');
    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    modal.show();

    fetch('?controller=finance&action=getPODetails&id=' + poId)
        .then(r => r.json())
        .then(data => {
            const po = data.po;
            const items = data.po_items;
            const deliveries = data.deliveries;
            const receipts = data.receipts;

            document.getElementById('viewPONumber').textContent = po.customer_po_number || '-';

            let html = `
                <div class="row mb-3">
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Customer Code:</strong></p>
                        <p class="text-muted">${po.customer_code || '-'}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Customer Name:</strong></p>
                        <p class="text-muted">${po.customer_name || '-'}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Customer TIN:</strong></p>
                        <p class="text-muted">${po.customer_tin || '-'}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Terms:</strong></p>
                        <p class="text-muted">${(po.customer_terms || 0)} days</p>
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
                                <th>Production Progress</th>
                                <th>Delivered</th>
                                <th>Remaining</th>
                            </tr>
                        </thead>
                        <tbody>`;
            if (items && items.length > 0) {
                items.forEach(item => {
                    const qty = item.quantity || 0;
                    const itemProduced = item.produced_quantity || 0;
                    const itemDelivered = item.delivered_quantity || 0;
                    const remaining = Math.max(0, qty - itemDelivered);
                    const itemPercent = qty > 0 ? Math.round((itemProduced / qty) * 100) : 0;
                    const barClass = itemPercent >= 100 ? 'bg-success' : 'bg-warning';
                    var conv = item.uom_conversion || null;
                    var deliveredText = itemDelivered + '/' + qty + ' pcs, ' + (conv ? (Math.round(itemDelivered / conv * 100) / 100) + '/' + (Math.round(qty / conv * 100) / 100) + ' cs' : '—/—');
                    html += `<tr>
                        <td>${item.item_code || '-'}</td>
                        <td>${item.item_description || '-'}</td>
                        <td>${item.item_uom || '-'}</td>
                        <td>${qty}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 me-2" style="height: 14px; width: 80px;">
                                    <div class="progress-bar ${barClass}" style="width: ${itemPercent}%"></div>
                                </div>
                                <small class="text-muted">${itemProduced}/${qty} pcs</small>
                            </div>
                        </td>
                        <td><small class="text-muted">${deliveredText}</small></td>
                        <td><small class="badge ${remaining <= 0 ? 'bg-success' : 'bg-warning'}">${remaining}</small></td>
                    </tr>`;
                });
            } else {
                html += '<tr><td colspan="7" class="text-center text-muted py-3">No items found</td></tr>';
            }
            html += `</tbody></table></div>`;

            if (deliveries && deliveries.length > 0) {
                html += `<h6 class="mt-3"><i class="bi bi-truck me-1"></i>Delivery History</h6>
                <table class="table table-sm table-bordered mb-3">
                    <thead><tr><th>Date</th><th>Item</th><th>Qty</th><th>By</th><th>Remarks</th></tr></thead>
                    <tbody>`;
                deliveries.forEach(d => {
                    html += `<tr>
                        <td>${d.delivery_date}</td>
                        <td>${d.item_description || '-'}</td>
                        <td>${d.delivery_quantity}</td>
                        <td>${d.delivered_by_name || '-'}</td>
                        <td>${d.remarks || '-'}</td>
                    </tr>`;
                });
                html += `</tbody></table>`;
            }

            if (receipts && receipts.length > 0) {
                html += `<h6><i class="bi bi-paperclip me-1"></i>Delivery Receipts</h6>
                <ul class="list-group mb-3">`;
                receipts.forEach(r => {
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-file-earmark me-2"></i>${r.file_name}</span>
                        <a href="${r.file_path}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                    </li>`;
                });
                html += `</ul>`;
            }

            body.innerHTML = html;
        });
}
</script>
