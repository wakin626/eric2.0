<h4><i class="bi bi-clock-history me-2"></i>Production History</h4>

<div class="d-flex justify-content-end mb-3">
    <div class="search-box" style="width: 300px;">
        <i class="bi bi-search"></i>
        <input type="text" id="searchHistory" class="form-control" placeholder="Search...">
    </div>
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="sortable" data-sort="date">Date <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="po">PO Number <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="customer">Customer <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="prev">Previous Qty <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="added">Added Qty <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="new">New Qty <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="user">Updated By <i class="bi bi-chevron-expand"></i></th>
                </tr>
            </thead>
            <tbody id="historyTableBody">
                <?php foreach ($history as $h): ?>
                <tr>
                    <td><?= date('Y-m-d H:i', strtotime($h['date_created'])) ?></td>
                    <td><strong><?= htmlspecialchars($h['customer_po_number'] ?? '-') ?></strong></td>
                    <td><?= htmlspecialchars($h['customer_name'] ?? '-') ?></td>
                    <td><?= $h['previous_quantity'] ?></td>
                    <td><span class="text-success">+<?= $h['added_quantity'] ?></span></td>
                    <td><strong><?= $h['new_quantity'] ?></strong></td>
                    <td><?= htmlspecialchars($h['full_name'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($history)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No production history yet</td></tr>
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
            <a class="page-link" href="?controller=production&action=history&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<script>
document.getElementById('searchHistory').addEventListener('keyup', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#historyTableBody tr').forEach(row => {
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