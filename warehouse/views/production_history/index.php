<h4><i class="bi bi-clock-history me-2"></i>Production History</h4>

<div class="d-flex justify-content-end mb-3">
    <div class="search-box" style="width: 300px;">
        <i class="bi bi-search"></i>
        <input type="text" id="searchHistory" class="form-control" placeholder="Search...">
    </div>
</div>

<?php if (!empty($reportsCount) && $reportsCount > 0): ?>
<div class="alert alert-warning d-flex align-items-center mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong><?= $reportsCount ?></strong>&nbsp;lot number report(s) pending action.
    <a href="#pendingReports" class="ms-2 text-decoration-underline">View pending</a>
</div>
<?php endif; ?>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="sortable" data-sort="date">Date <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="po">PO Number <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="customer">Customer <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="item">Item <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="lot">Lot No. <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="prev">Previous Qty <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="added">Added Qty <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="new">New Qty <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="user">Updated By <i class="bi bi-chevron-expand"></i></th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="historyTableBody">
                <?php foreach ($history as $h): ?>
                <tr>
                    <td><?= date('Y-m-d H:i', strtotime($h['date_created'])) ?></td>
                    <td><strong><?= htmlspecialchars($h['customer_po_number'] ?? '-') ?></strong></td>
                    <td><?= htmlspecialchars($h['customer_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($h['item_description'] ?? '-') ?></td>
                    <td>
                        <?php if (!empty($h['lot_number'])): ?>
                            <strong><?= htmlspecialchars($h['lot_number']) ?></strong>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $h['previous_quantity'] ?></td>
                    <td><span class="text-success">+<?= $h['added_quantity'] ?></span></td>
                    <td><strong><?= $h['new_quantity'] ?></strong></td>
                    <td><?= htmlspecialchars($h['full_name'] ?? '-') ?></td>
                    <td>
                        <?php if (!empty($h['report_id']) && $h['report_status'] === 'pending'): ?>
                            <span class="badge bg-warning text-dark" title="<?= htmlspecialchars($h['report_reason'] ?? '') ?>">Reported</span>
                        <?php endif; ?>
                        <?php if (!empty($h['poi_id'])): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick="openEditModal(<?= $h['history_id'] ?>, '<?= htmlspecialchars(addslashes($h['lot_number'] ?? ''), ENT_QUOTES) ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($history)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No production history yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Lot Modal -->
<div class="modal fade" id="editLotModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Lot Number</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editHistoryId">
                <div class="mb-3">
                    <label class="form-label">Current Lot Number</label>
                    <input type="text" id="editCurrentLot" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Lot Number <span class="text-danger">*</span></label>
                    <input type="text" id="editNewLot" class="form-control" placeholder="Enter correct lot number" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveEditLot()"><i class="bi bi-check-lg me-1"></i>Save</button>
            </div>
        </div>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination justify-content-center mt-4">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?controller=warehouse&action=productionHistory&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<script>
function openEditModal(historyId, currentLot) {
    document.getElementById('editHistoryId').value = historyId;
    document.getElementById('editCurrentLot').value = currentLot;
    document.getElementById('editNewLot').value = '';
    new bootstrap.Modal(document.getElementById('editLotModal')).show();
}

function saveEditLot() {
    const historyId = document.getElementById('editHistoryId').value;
    const newLot = document.getElementById('editNewLot').value.trim();
    if (!newLot) { alert('Please enter a new lot number.'); return; }
    
    fetch('?controller=warehouse&action=editHistoryLot', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'history_id=' + encodeURIComponent(historyId) + '&new_lot_number=' + encodeURIComponent(newLot)
    }).then(r => r.json()).then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update lot number.');
        }
    }).catch(() => alert('An error occurred.'));
}

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
