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
            <input type="hidden" name="controller" value="admin">
            <input type="hidden" name="action" value="productionHistory">
            <i class="bi bi-search"></i>
            <input type="text" name="search" id="searchHistory" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search ?? '') ?>">
        </form>
    </div>
</div>

<?php if (!empty($reportsCount) && $reportsCount > 0): ?>
<div class="alert alert-warning d-flex align-items-center mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong><?= $reportsCount ?></strong>&nbsp;production report(s) pending action.
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
                    <th class="sortable" data-sort="sts_ref">STS Ref <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="lot">Lot No. <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="prev">Previous Qty <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="added">Added Qty <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="new">New Qty <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="excess">Excess <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="user">Updated By <i class="bi bi-chevron-expand"></i></th>
                    <th>Report</th>
                    <th>Action</th>
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
                        <?php
                        $hasReport = !empty($h['report_id']);
                        $reportPending = $hasReport && $h['report_status'] === 'pending';
                        $reportResolved = $hasReport && $h['report_status'] === 'resolved';
                        $reportType = $h['report_type'] ?? 'lot_number';
                        $typeLabel = $reportType === 'quantity' ? 'Quantity' : 'Lot No.';
                        ?>
                        <?php if ($reportPending): ?>
                            <span style="color:red;font-weight:bold;" title="<?= htmlspecialchars($h['report_reason'] ?? '') ?>">
                                <?= $typeLabel ?>: <?= htmlspecialchars($h['report_reason'] ?? '') ?>
                            </span>
                        <?php elseif ($reportResolved): ?>
                            <span style="color:#e6a800;font-weight:bold;" title="Resolved">
                                <?= $typeLabel ?>: <?= htmlspecialchars($h['report_reason'] ?? '') ?> &rarr; Resolved
                            </span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($h['poi_id'])): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick="openEditModal(<?= $h['history_id'] ?>, '<?= htmlspecialchars(addslashes($h['lot_number'] ?? ''), ENT_QUOTES) ?>', <?= $h['added_quantity'] ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($history)): ?>
                <tr><td colspan="13" class="text-center text-muted py-4">No production history yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Record Modal -->
<div class="modal fade" id="editRecordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Production Record</h5>
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
                <div class="mb-3">
                    <label class="form-label">Current Quantity</label>
                    <input type="text" id="editCurrentQty" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Quantity <span class="text-danger">*</span></label>
                    <input type="number" id="editNewQty" class="form-control" min="1" required>
                </div>
                <div class="alert alert-info py-2 mb-0">
                    <small><i class="bi bi-info-circle me-1"></i>Changing quantity will update production progress.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="previewEditRecord()"><i class="bi bi-eye me-1"></i>Preview</button>
            </div>
        </div>
    </div>
</div>

<!-- Production Edit Preview Modal -->
<div class="modal fade" id="editPreviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Confirm Production Edit</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Please review the changes before saving.</p>
                <table class="table table-bordered mb-0">
                    <tr><th style="width:40%">History ID</th><td id="prevHistId"></td></tr>
                    <tr><th>Previous Lot</th><td id="prevOldLot"></td></tr>
                    <tr><th>New Lot</th><td id="prevNewLot"></td></tr>
                    <tr><th>Previous Qty</th><td id="prevOldQty"></td></tr>
                    <tr><th>New Qty</th><td id="prevNewQty"></td></tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmEditBtn"><i class="bi bi-check-lg me-1"></i>Confirm & Save</button>
            </div>
        </div>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<?php $pages = \App\Helpers\Pagination::getPageRange($page, $totalPages); ?>
<nav>
    <ul class="pagination justify-content-center mt-4">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?controller=admin&action=productionHistory&page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>">&laquo; Prev</a>
        </li>
        <?php foreach ($pages as $p): ?>
            <?php if ($p === '...'): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php else: ?>
            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                <a class="page-link" href="?controller=admin&action=productionHistory&page=<?= $p ?>&search=<?= urlencode($search ?? '') ?>"><?= $p ?></a>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?controller=admin&action=productionHistory&page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>">Next &raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<script>
function openEditModal(historyId, currentLot, currentQty) {
    document.getElementById('editHistoryId').value = historyId;
    document.getElementById('editCurrentLot').value = currentLot;
    document.getElementById('editNewLot').value = currentLot;
    document.getElementById('editCurrentQty').value = currentQty;
    document.getElementById('editNewQty').value = currentQty;
    new bootstrap.Modal(document.getElementById('editRecordModal')).show();
}

function previewEditRecord() {
    var historyId = document.getElementById('editHistoryId').value;
    var oldLot = document.getElementById('editCurrentLot').value;
    var newLot = document.getElementById('editNewLot').value.trim();
    var oldQty = document.getElementById('editCurrentQty').value;
    var newQty = parseInt(document.getElementById('editNewQty').value);

    if (!newLot) { alert('Please enter a lot number.'); return; }
    if (!newQty || newQty <= 0) { alert('Please enter a valid quantity.'); return; }

    document.getElementById('prevHistId').textContent = historyId;
    document.getElementById('prevOldLot').textContent = oldLot;
    document.getElementById('prevNewLot').textContent = newLot;
    document.getElementById('prevOldQty').textContent = oldQty;
    document.getElementById('prevNewQty').textContent = newQty;

    bootstrap.Modal.getInstance(document.getElementById('editRecordModal')).hide();
    new bootstrap.Modal(document.getElementById('editPreviewModal')).show();
}

function saveEditRecord() {
    var historyId = document.getElementById('editHistoryId').value;
    var newLot = document.getElementById('editNewLot').value.trim();
    var newQty = parseInt(document.getElementById('editNewQty').value);

    fetch('?controller=admin&action=editHistoryRecord', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'history_id=' + encodeURIComponent(historyId) + 
              '&new_lot_number=' + encodeURIComponent(newLot) +
              '&new_added_quantity=' + encodeURIComponent(newQty)
    }).then(function(r) { return r.json(); }).then(function(data) {
        bootstrap.Modal.getInstance(document.getElementById('editPreviewModal')).hide();
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update record.');
        }
    }).catch(function() { alert('An error occurred.'); });
}

document.getElementById('confirmEditBtn').addEventListener('click', saveEditRecord);

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
