<h4><i class="bi bi-clock-history me-2"></i>Production History</h4>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2 flex-wrap">
        <select id="filterCustomer" class="form-select form-select-sm filter-select" style="width:180px">
            <option value="">All Customers</option>
        </select>
        <select id="filterItem" class="form-select form-select-sm filter-select" style="width:180px">
            <option value="">All Items</option>
        </select>
        <select id="filterLot" class="form-select form-select-sm filter-select" style="width:160px">
            <option value="">All Lots</option>
        </select>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearHistoryFilters"><i class="bi bi-x-circle me-1"></i>Clear</button>
    </div>
    <div class="search-box" style="width: 300px;">
        <form method="GET" class="d-flex align-items-center">
            <input type="hidden" name="controller" value="production">
            <input type="hidden" name="action" value="history">
            <i class="bi bi-search"></i>
            <input type="text" name="search" id="searchHistory" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search ?? '') ?>">
        </form>
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
                    <th class="sortable" data-sort="sts_ref">STS Ref <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="lot">Lot No. <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="prev">Previous Qty <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="added">Added Qty <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="new">New Qty <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="excess">Excess <i class="bi bi-chevron-expand"></i></th>
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
                    <td><strong>
                    <?php
                    $hPoiId = $h['poi_id'] ?? null;
                    $hNormalCr = $hPoiId ? (($normal_consumption_records ?? [])[$hPoiId] ?? []) : [];
                    if (!empty($hNormalCr) && ($h['production_type'] ?? 'normal') !== 'advance'):
                    ?><span style="opacity:0.75"><?= htmlspecialchars($hNormalCr[0]['advance_po_number']) ?></span>/<?php endif; ?><?= htmlspecialchars($h['customer_po_number'] ?? '-') ?>
                    </strong></td>
                    <td><?= htmlspecialchars($h['customer_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($h['item_description'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($h['sts_ref'] ?? '') ?: '<span class="text-muted">-</span>' ?></td>
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
                    <td>
                        <?php
                        $ordered = $h['ordered_quantity'] ?? 0;
                        $excess = $ordered > 0 ? $h['new_quantity'] - $ordered : 0;
                        ?>
                        <?php if ($excess > 0): ?>
                            <span class="badge bg-danger">+<?= $excess ?></span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
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
                <tr><td colspan="12" class="text-center text-muted py-4">No production history yet</td></tr>
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
<?php $pages = \App\Helpers\Pagination::getPageRange($page, $totalPages); ?>
<nav>
    <ul class="pagination justify-content-center mt-4">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?controller=production&action=history&page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>">&laquo; Prev</a>
        </li>
        <?php foreach ($pages as $p): ?>
            <?php if ($p === '...'): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php else: ?>
            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                <a class="page-link" href="?controller=production&action=history&page=<?= $p ?>&search=<?= urlencode($search ?? '') ?>"><?= $p ?></a>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?controller=production&action=history&page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>">Next &raquo;</a>
        </li>
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

function populateHistoryFilters() {
    var custData = <?= json_encode(array_values(array_unique(array_filter(array_column($history, 'customer_name'))))) ?>;
    var itemData = <?= json_encode(array_values(array_unique(array_filter(array_column($history, 'item_description'))))) ?>;
    var lotData = <?= json_encode(array_values(array_unique(array_filter(array_column($history, 'lot_number'))))) ?>;
    var custSel = document.getElementById('filterCustomer');
    custData.forEach(function(c) { var o = document.createElement('option'); o.value = c; o.textContent = c; custSel.appendChild(o); });
    var itemSel = document.getElementById('filterItem');
    itemData.forEach(function(i) { var o = document.createElement('option'); o.value = i; o.textContent = i; itemSel.appendChild(o); });
    var lotSel = document.getElementById('filterLot');
    lotData.forEach(function(l) { var o = document.createElement('option'); o.value = l; o.textContent = l; lotSel.appendChild(o); });
}

function applyHistoryFilters() {
    const custFilter = document.getElementById('filterCustomer').value.toLowerCase();
    const itemFilter = document.getElementById('filterItem').value.toLowerCase();
    const lotFilter = document.getElementById('filterLot').value.toLowerCase();
    document.querySelectorAll('#historyTableBody tr').forEach(row => {
        if (row.querySelector('td[colspan]')) { row.style.display = ''; return; }
        const cust = row.cells[2] ? row.cells[2].textContent.trim().toLowerCase() : '';
        const item = row.cells[3] ? row.cells[3].textContent.trim().toLowerCase() : '';
        const lotStrong = row.cells[4] ? row.cells[4].querySelector('strong') : null;
        const lot = lotStrong ? lotStrong.textContent.trim().toLowerCase() : '';
        let show = true;
        if (custFilter && !cust.includes(custFilter)) show = false;
        if (itemFilter && !item.includes(itemFilter)) show = false;
        if (lotFilter && !lot.includes(lotFilter)) show = false;
        row.style.display = show ? '' : 'none';
    });
}

document.getElementById('filterCustomer').addEventListener('change', applyHistoryFilters);
document.getElementById('filterItem').addEventListener('change', applyHistoryFilters);
document.getElementById('filterLot').addEventListener('change', applyHistoryFilters);
var _searchTimer;
document.getElementById('searchHistory').addEventListener('input', function() {
    clearTimeout(_searchTimer);
    var form = this.closest('form');
    _searchTimer = setTimeout(function() { form.submit(); }, 500);
});

(function() {
    var s = document.getElementById('searchHistory');
    if (s && s.value) { s.focus(); s.setSelectionRange(s.value.length, s.value.length); }
})();
document.getElementById('clearHistoryFilters').addEventListener('click', function() {
    document.getElementById('filterCustomer').value = '';
    document.getElementById('filterItem').value = '';
    document.getElementById('filterLot').value = '';
    document.getElementById('searchHistory').value = '';
    var form = document.querySelector('#searchHistory').closest('form');
    if (form) form.submit();
    else applyHistoryFilters();
});

document.addEventListener('DOMContentLoaded', populateHistoryFilters);

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
