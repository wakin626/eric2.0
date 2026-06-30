<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <span class="text-muted">Showing <?= count($deliveries) ?> of <?= $total ?> deliveries</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <select id="filterCustomer" class="form-select form-select-sm" style="width:200px">
            <option value="">All Customers</option>
        </select>
        <select id="filterItem" class="form-select form-select-sm" style="width:200px">
            <option value="">All Items</option>
        </select>
        <input type="date" id="filterDateFrom" class="form-control form-control-sm" style="width: 160px;" title="From date">
        <input type="date" id="filterDateTo" class="form-control form-control-sm" style="width: 160px;" title="To date">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearFilters"><i class="bi bi-x-circle me-1"></i>Clear</button>
        <div class="search-box" style="width: 250px;">
            <i class="bi bi-search"></i>
            <input type="text" id="searchDelivery" class="form-control" placeholder="Search delivery...">
        </div>
    </div>
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="sortable" data-sort="po_number">PO Number <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="customer">Customer <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="item">Item <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="date">Delivery Date <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="dr_number">DR No. <i class="bi bi-chevron-expand"></i></th>
                    <th>Type</th>
                    <th class="sortable" data-sort="by">Delivered By <i class="bi bi-chevron-expand"></i></th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="deliveryTableBody">
                <?php foreach ($deliveries as $d): ?>
                <?php
                    $lotItems = json_decode($d['lot_items'] ?? '[]', true);
                    $hasLotItems = is_array($lotItems) && count($lotItems) > 0;
                    $itemSummary = '';
                    if ($hasLotItems) {
                        $grouped = [];
                        foreach ($lotItems as $li) {
                            $key = $li['item_description'] ?? $li['item_code'] ?? 'Unknown';
                            if (!isset($grouped[$key])) $grouped[$key] = ['qty' => 0, 'lots' => []];
                            $grouped[$key]['qty'] += $li['qty'] ?? 0;
                            $grouped[$key]['lots'][] = $li['lot_number'] ?? '?';
                        }
                        $parts = [];
                        foreach ($grouped as $desc => $info) {
                            $parts[] = htmlspecialchars($desc) . ' (' . $info['qty'] . ' - ' . implode(', ', $info['lots']) . ')';
                        }
                        $itemSummary = implode('<br>', $parts);
                    } else {
                        $itemSummary = htmlspecialchars(($d['item_code'] ?? '-') . ' - ' . ($d['item_description'] ?? ''));
                        if (!empty($d['lot_number'])) $itemSummary .= '<br><small>' . htmlspecialchars($d['lot_number']) . '</small>';
                    }
                ?>
                <tr data-date="<?= date('Y-m-d', strtotime($d['delivery_date'])) ?>">
                    <td><strong class="text-primary"><?= htmlspecialchars($d['customer_po_number'] ?? '-') ?></strong></td>
                    <td><?= htmlspecialchars($d['customer_name'] ?? '-') ?></td>
                    <td><small><?= $itemSummary ?></small></td>
                    <td><?= date('Y-m-d', strtotime($d['delivery_date'])) ?></td>
                    <td><?= htmlspecialchars($d['dr_number'] ?? '-') ?></td>
                    <td>
                        <?php if (($d['production_type'] ?? 'normal') === 'advance'): ?>
                            <span class="badge bg-info">Advance</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Normal</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($d['delivered_by_name'] ?? '-') ?></td>
                    <td class="text-center">
                        <?php if ($hasLotItems): ?>
                        <button type="button" class="btn btn-sm btn-outline-info viewDeliveryBtn"
                            data-bs-toggle="modal" data-bs-target="#viewDeliveryModal"
                            data-dr="<?= htmlspecialchars($d['dr_number']) ?>"
                            data-po="<?= htmlspecialchars($d['customer_po_number']) ?>"
                            data-customer="<?= htmlspecialchars($d['customer_name'] ?? '') ?>"
                            data-date="<?= date('Y-m-d', strtotime($d['delivery_date'])) ?>"
                            data-lot-items="<?= htmlspecialchars($d['lot_items'] ?? '[]') ?>"
                            data-delivered-by="<?= htmlspecialchars($d['delivered_by_name'] ?? '') ?>"
                            title="View Lot Items">
                            <i class="bi bi-list-ul"></i>
                        </button>
                        <?php endif; ?>
                        <a href="?controller=finance&action=viewDelivery&id=<?= $d['delivery_id'] ?>" class="btn btn-sm btn-outline-primary" title="View Delivery">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($deliveries)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No deliveries found</td></tr>
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
            <a class="page-link" href="?controller=finance&action=deliveries&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<script>
function populateDeliveryFilters() {
    const customers = new Set();
    const items = new Set();
    document.querySelectorAll('#deliveryTableBody tr').forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        const cust = row.cells[1] ? row.cells[1].textContent.trim() : '';
        if (cust) customers.add(cust);
        const itemCell = row.cells[2];
        if (itemCell) {
            const t = itemCell.textContent.trim();
            if (t && t !== '-') items.add(t);
        }
    });
    const custSel = document.getElementById('filterCustomer');
    customers.forEach(c => { const o = document.createElement('option'); o.value = c; o.textContent = c; custSel.appendChild(o); });
    const itemSel = document.getElementById('filterItem');
    items.forEach(i => { const o = document.createElement('option'); o.value = i; o.textContent = i; itemSel.appendChild(o); });
}

function filterTable() {
    const query = document.getElementById('searchDelivery').value.toLowerCase();
    const custFilter = document.getElementById('filterCustomer').value.toLowerCase();
    const itemFilter = document.getElementById('filterItem').value.toLowerCase();
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    document.querySelectorAll('#deliveryTableBody tr').forEach(row => {
        if (row.querySelector('td[colspan]')) { row.style.display = ''; return; }
        const text = row.textContent.toLowerCase();
        const cust = row.cells[1] ? row.cells[1].textContent.trim().toLowerCase() : '';
        const itemText = row.cells[2] ? row.cells[2].textContent.trim().toLowerCase() : '';
        const rowDate = row.getAttribute('data-date') || '';
        const matchesSearch = text.includes(query);
        const matchesCust = !custFilter || cust.includes(custFilter);
        const matchesItem = !itemFilter || itemText.includes(itemFilter);
        const matchesFrom = !dateFrom || rowDate >= dateFrom;
        const matchesTo = !dateTo || rowDate <= dateTo;
        row.style.display = (matchesSearch && matchesCust && matchesItem && matchesFrom && matchesTo) ? '' : 'none';
    });
}

document.getElementById('searchDelivery').addEventListener('keyup', filterTable);
document.getElementById('filterCustomer').addEventListener('change', filterTable);
document.getElementById('filterItem').addEventListener('change', filterTable);
document.getElementById('filterDateFrom').addEventListener('change', filterTable);
document.getElementById('filterDateTo').addEventListener('change', filterTable);
document.getElementById('clearFilters').addEventListener('click', function() {
    document.getElementById('filterCustomer').value = '';
    document.getElementById('filterItem').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    document.getElementById('searchDelivery').value = '';
    filterTable();
});

document.addEventListener('DOMContentLoaded', populateDeliveryFilters);

document.querySelectorAll('.sortable').forEach(th => {
    th.style.cursor = 'pointer';
    th.addEventListener('click', function() {
        const table = document.querySelector('table');
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
                return asc ? aVal - bVal : bVal - aVal;
            }
            return asc ? aVal.localeCompare(bVal, undefined, {numeric: true}) : bVal.localeCompare(aVal, undefined, {numeric: true});
        });
        rows.forEach(row => tbody.appendChild(row));
    });
});

document.querySelectorAll('.viewDeliveryBtn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('modalPONumber').textContent = this.dataset.po || '-';
        document.getElementById('modalCustomer').textContent = this.dataset.customer || '-';
        document.getElementById('modalDR').textContent = this.dataset.dr || '-';
        document.getElementById('modalDate').textContent = this.dataset.date || '-';

        var lotItems = JSON.parse(this.dataset.lotItems || '[]');
        var tbody = document.getElementById('modalLotItemsBody');
        tbody.innerHTML = '';
        var total = 0;
        lotItems.forEach(function(item) {
            total += item.qty || 0;
            var conv = item.uom_conversion || null;
            var uom = item.item_uom || '';
            var cases = (conv && uom !== 'CS') ? Math.round((item.qty || 0) / conv * 100) / 100 : 0;
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>' + (item.item_code || '-') + '</td>' +
                '<td>' + (item.item_description || '-') + '</td>' +
                '<td>' + (item.lot_number || '-') + '</td>' +
                '<td class="text-end">' + (item.qty || 0) + '</td>' +
                '<td class="text-end">' + (cases > 0 ? cases + ' CS' : '---') + '</td>';
            tbody.appendChild(tr);
        });
        var totalTr = document.createElement('tr');
        totalTr.innerHTML = '<td colspan="3" class="text-end fw-bold">Total:</td>' +
            '<td class="text-end fw-bold">' + total + '</td><td></td>';
        tbody.appendChild(totalTr);
    });
});
</script>

<div class="modal fade" id="viewDeliveryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-list-ul me-2"></i>Lot Items - <span id="modalPONumber"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Customer:</strong> <span id="modalCustomer"></span></div>
                    <div class="col-md-4"><strong>DR No.:</strong> <span id="modalDR"></span></div>
                    <div class="col-md-4"><strong>Date:</strong> <span id="modalDate"></span></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Item Description</th>
                                <th>Lot Number</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Cases</th>
                            </tr>
                        </thead>
                        <tbody id="modalLotItemsBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
