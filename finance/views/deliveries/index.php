<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="text-muted">Showing <?= count($deliveries) ?> of <?= $total ?> deliveries</span>
    </div>
    <div class="d-flex gap-2">
        <input type="date" id="filterDateFrom" class="form-control form-control-sm" style="width: 160px;" title="From date">
        <input type="date" id="filterDateTo" class="form-control form-control-sm" style="width: 160px;" title="To date">
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
                    <th class="sortable" data-sort="quantity">Quantity <i class="bi bi-chevron-expand"></i></th>
                    <th>Type</th>
                    <th class="sortable" data-sort="by">Delivered By <i class="bi bi-chevron-expand"></i></th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="deliveryTableBody">
                <?php foreach ($deliveries as $d): ?>
                <tr data-date="<?= date('Y-m-d', strtotime($d['delivery_date'])) ?>">
                    <td><strong class="text-primary"><?= htmlspecialchars($d['customer_po_number'] ?? '-') ?></strong></td>
                    <td><?= htmlspecialchars($d['customer_name'] ?? '-') ?></td>
                    <td><small><?= htmlspecialchars($d['item_description'] ?? '-') ?></small></td>
                    <td><?= date('Y-m-d', strtotime($d['delivery_date'])) ?></td>
                    <td><?= $d['delivery_quantity'] ?? 0 ?></td>
                    <td>
                        <?php if (($d['production_type'] ?? 'normal') === 'advance'): ?>
                            <span class="badge bg-info">Advance</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Normal</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($d['delivered_by_name'] ?? '-') ?></td>
                    <td class="text-center">
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
function filterTable() {
    const query = document.getElementById('searchDelivery').value.toLowerCase();
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    document.querySelectorAll('#deliveryTableBody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        const rowDate = row.getAttribute('data-date') || '';
        const matchesSearch = text.includes(query);
        const matchesFrom = !dateFrom || rowDate >= dateFrom;
        const matchesTo = !dateTo || rowDate <= dateTo;
        row.style.display = (matchesSearch && matchesFrom && matchesTo) ? '' : 'none';
    });
}

document.getElementById('searchDelivery').addEventListener('keyup', filterTable);
document.getElementById('filterDateFrom').addEventListener('change', filterTable);
document.getElementById('filterDateTo').addEventListener('change', filterTable);

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
</script>
