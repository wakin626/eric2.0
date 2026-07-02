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
                    <th class="sortable" data-sort="item">Item <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="lot">Lot No. <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="prev">Previous Qty <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="added">Added Qty <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="new">New Qty <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="user">Updated By <i class="bi bi-chevron-expand"></i></th>
                    <th>Report</th>
                </tr>
            </thead>
            <tbody id="historyTableBody">
                <?php foreach ($history as $h): ?>
                <tr>
                    <td>
                        <?= date('Y-m-d H:i', strtotime($h['date_created'])) ?>
                        <?php if (!empty($h['date_edited'])): ?>
                            <br><small class="text-info" title="Edited by <?= htmlspecialchars($h['edited_by_name'] ?? '') ?>">
                                <i class="bi bi-pencil-square"></i> Edited <?= date('m/d H:i', strtotime($h['date_edited'])) ?>
                            </small>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($h['customer_po_number'] ?? '-') ?></strong></td>
                    <td><?= htmlspecialchars($h['customer_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($h['item_description'] ?? '-') ?></td>
                    <td>
                        <?php if (!empty($h['lot_number'])): ?>
                            <strong><?= htmlspecialchars($h['lot_number']) ?></strong>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                        <?php if (!empty($h['date_edited']) && !empty($h['old_lot_number'])): ?>
                            <br><small class="text-muted">(old: <?= htmlspecialchars($h['old_lot_number']) ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td><?= $h['previous_quantity'] ?></td>
                    <td>
                        <span class="text-success">+<?= $h['added_quantity'] ?></span>
                        <?php if (!empty($h['date_edited']) && $h['old_added_quantity'] !== null): ?>
                            <br><small class="text-muted">(old: +<?= $h['old_added_quantity'] ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= $h['new_quantity'] ?></strong></td>
                    <td><?= htmlspecialchars($h['full_name'] ?? '-') ?></td>
                    <td>
                        <?php if (!empty($h['report_id']) && $h['report_status'] === 'pending'): ?>
                            <span class="badge bg-warning text-dark" title="<?= htmlspecialchars($h['report_reason'] ?? '') ?>">Reported</span>
                        <?php elseif (!empty($h['lot_number'])): ?>
                            <button class="btn btn-sm btn-outline-danger" onclick="openReportModal(<?= $h['history_id'] ?>, '<?= htmlspecialchars(addslashes($h['lot_number'] ?? ''), ENT_QUOTES) ?>', <?= $h['added_quantity'] ?>)">
                                <i class="bi bi-flag"></i>
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

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="?controller=production&action=reportHistory">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalTitle"><i class="bi bi-flag me-2"></i>Report Issue</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="history_id" id="reportHistoryId">
                    <div class="mb-3">
                        <label class="form-label">Report Type <span class="text-danger">*</span></label>
                        <select name="report_type" id="reportType" class="form-select" required onchange="updateReportTitle()">
                            <option value="lot_number">Lot No.</option>
                            <option value="quantity">Quantity</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="reportDisplayLabel">Current Lot Number</label>
                        <input type="text" id="reportDisplayValue" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Report <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" id="reportReason" placeholder="Explain the issue..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-flag me-1"></i>Submit Report</button>
                </div>
            </form>
        </div>
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
function openReportModal(historyId, lotNumber, addedQty) {
    document.getElementById('reportHistoryId').value = historyId;
    document.getElementById('reportDisplayValue').value = lotNumber;
    document.getElementById('reportType').value = 'lot_number';
    document.getElementById('reportReason').value = '';
    updateReportTitle();
    new bootstrap.Modal(document.getElementById('reportModal')).show();
}

function updateReportTitle() {
    const type = document.getElementById('reportType').value;
    const title = document.getElementById('reportModalTitle');
    const label = document.getElementById('reportDisplayLabel');
    const display = document.getElementById('reportDisplayValue');
    const placeholder = document.getElementById('reportReason');
    if (type === 'quantity') {
        title.innerHTML = '<i class="bi bi-flag me-2"></i>Report Wrong Quantity';
        label.textContent = 'Current Quantity';
        placeholder.placeholder = 'Explain the quantity issue...';
    } else {
        title.innerHTML = '<i class="bi bi-flag me-2"></i>Report Wrong Lot Number';
        label.textContent = 'Current Lot Number';
        placeholder.placeholder = 'Explain why this lot number is wrong...';
    }
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
