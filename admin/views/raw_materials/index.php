<div class="d-flex justify-content-between mb-4">
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rawMaterialModal">Add Raw Material</button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importRmModal"><i class="bi bi-upload me-1"></i>Import Excel</button>
    </div>
    <form method="GET" class="d-flex gap-2" style="width:40%">
        <input type="hidden" name="controller" value="admin">
        <input type="hidden" name="action" value="rawMaterials">
        <input type="text" name="search" id="searchRM" class="form-control" placeholder="Search code, name..." value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="category" id="filterCategory" class="form-select filter-select" style="width:auto">
            <option value="">All Categories</option>
            <option value="raw_material" <?= ($categoryFilter ?? '') === 'raw_material' ? 'selected' : '' ?>>Raw Material</option>
            <option value="packaging" <?= ($categoryFilter ?? '') === 'packaging' ? 'selected' : '' ?>>Packaging</option>
        </select>
    </form>
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Trade Name</th>
                    <th>Category</th>
                    <th>UOM</th>
                    <th>Stock on Hand</th>
                    <th>Allocated</th>
                    <th>Reorder Level</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rawMaterials as $rm): ?>
                <tr>
                    <td><strong class="text-primary"><?= htmlspecialchars($rm['item_code']) ?></strong></td>
                    <td><?= htmlspecialchars($rm['trade_name']) ?></td>
                    <td><span class="badge bg-<?= $rm['category'] === 'packaging' ? 'info' : 'secondary' ?>"><?= ucfirst(str_replace('_', ' ', $rm['category'])) ?></span></td>
                    <td><?= htmlspecialchars($rm['uom']) ?></td>
                    <td><?= number_format($rm['stock_on_hand'], 4) ?></td>
                    <td><?= number_format($rm['allocated_stock'], 4) ?></td>
                    <td><?= number_format($rm['reorder_level'], 4) ?></td>
                    <td><span class="badge bg-<?= $rm['is_active'] ? 'success' : 'secondary' ?>"><?= $rm['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#rmEditModal"
                            data-id="<?= $rm['id'] ?>"
                            data-code="<?= htmlspecialchars($rm['item_code']) ?>"
                            data-name="<?= htmlspecialchars($rm['trade_name']) ?>"
                            data-category="<?= $rm['category'] ?>"
                            data-uom="<?= htmlspecialchars($rm['uom']) ?>"
                            data-stock="<?= $rm['stock_on_hand'] ?>"
                            data-allocated="<?= $rm['allocated_stock'] ?>"
                            data-reorder="<?= $rm['reorder_level'] ?>"
                            data-active="<?= $rm['is_active'] ?>"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-info text-white where-used-btn" data-rm-id="<?= $rm['id'] ?>" data-rm-name="<?= htmlspecialchars($rm['trade_name']) ?>" title="Where Used"><i class="bi bi-diagram-3"></i></button>
                        <a href="?controller=admin&action=rawMaterialToggleStatus&id=<?= $rm['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-toggle-on"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($rawMaterials)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No raw materials found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($totalPages ?? 1) > 1): ?>
<?php $pages = \App\Helpers\Pagination::getPageRange($page, $totalPages); ?>
<nav>
    <ul class="pagination justify-content-center mt-4">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?controller=admin&action=rawMaterials&page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>&category=<?= urlencode($categoryFilter ?? '') ?>">&laquo; Prev</a>
        </li>
        <?php foreach ($pages as $p): ?>
            <?php if ($p === '...'): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php else: ?>
            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                <a class="page-link" href="?controller=admin&action=rawMaterials&page=<?= $p ?>&search=<?= urlencode($search ?? '') ?>&category=<?= urlencode($categoryFilter ?? '') ?>"><?= $p ?></a>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?controller=admin&action=rawMaterials&page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>&category=<?= urlencode($categoryFilter ?? '') ?>">Next &raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<!-- Add Modal -->
<div class="modal fade" id="rawMaterialModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Raw Material</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="?controller=admin&action=rawMaterialCreate">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Item Code *</label><input type="text" name="item_code" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Trade Name *</label><input type="text" name="trade_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Category *</label><select name="category" class="form-select filter-select"><option value="raw_material">Raw Material</option><option value="packaging">Packaging</option></select></div>
                        <div class="col-md-6"><label class="form-label">UOM *</label><select name="uom" class="form-select filter-select"><option value="Kg">Kg</option><option value="g">Grams</option><option value="L">Liters</option><option value="mL">mL</option><option value="pcs">Pieces</option><option value="m">Meters</option><option value="roll">Rolls</option></select></div>
                        <div class="col-md-4"><label class="form-label">Stock on Hand</label><input type="number" step="0.0001" name="stock_on_hand" class="form-control" value="0"></div>
                        <div class="col-md-4"><label class="form-label">Allocated Stock</label><input type="number" step="0.0001" name="allocated_stock" class="form-control" value="0"></div>
                        <div class="col-md-4"><label class="form-label">Reorder Level</label><input type="number" step="0.0001" name="reorder_level" class="form-control" value="0"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="rmEditModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Raw Material</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="?controller=admin&action=rawMaterialUpdate">
                <div class="modal-body">
                    <input type="hidden" name="raw_material_id" id="edit_rm_id">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Item Code *</label><input type="text" name="item_code" id="edit_rm_code" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Trade Name *</label><input type="text" name="trade_name" id="edit_rm_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Category *</label><select name="category" id="edit_rm_category" class="form-select filter-select"><option value="raw_material">Raw Material</option><option value="packaging">Packaging</option></select></div>
                        <div class="col-md-6"><label class="form-label">UOM *</label><select name="uom" id="edit_rm_uom" class="form-select filter-select"><option value="Kg">Kg</option><option value="g">Grams</option><option value="L">Liters</option><option value="mL">mL</option><option value="pcs">Pieces</option><option value="m">Meters</option><option value="roll">Rolls</option></select></div>
                        <div class="col-md-4"><label class="form-label">Stock on Hand</label><input type="number" step="0.0001" name="stock_on_hand" id="edit_rm_stock" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Allocated Stock</label><input type="number" step="0.0001" name="allocated_stock" id="edit_rm_allocated" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Reorder Level</label><input type="number" step="0.0001" name="reorder_level" id="edit_rm_reorder" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Status</label><select name="is_active" id="edit_rm_active" class="form-select filter-select"><option value="1">Active</option><option value="0">Inactive</option></select></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Excel Modal -->
<div class="modal fade" id="importRmModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Import Raw Materials</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="?controller=admin&action=rawMaterialImportPreview" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select File (.csv or .xlsx)</label>
                        <input type="file" name="import_file" class="form-control" accept=".csv,.xlsx" required>
                    </div>
                    <div class="alert alert-info py-2 mb-0">
                        <strong>Expected columns:</strong> ITEM_CODE, TRADE_NAME, CATEGORY, UOM, STOCK_ON_HAND, REORDER_LEVEL<br>
                        <small>CATEGORY: raw_material or packaging | UOM: Kg, g, L, mL, pcs, m, roll</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success"><i class="bi bi-upload me-1"></i>Upload & Preview</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Where-Used Modal -->
<div class="modal fade" id="whereUsedModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Where Used: <span id="whereUsedRmName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="whereUsedLoading" class="text-center py-3 text-muted">Loading...</div>
                <div id="whereUsedResults" class="d-none">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>FG Code</th><th>Finished Good</th><th>BOM Code</th></tr>
                        </thead>
                        <tbody id="whereUsedBody"></tbody>
                    </table>
                    <div id="whereUsedEmpty" class="text-center py-3 text-muted d-none">Not used in any BOM.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var _searchTimer;
document.getElementById('searchRM').addEventListener('input', function() {
    clearTimeout(_searchTimer);
    var form = this.closest('form');
    _searchTimer = setTimeout(function() { form.submit(); }, 500);
});
document.getElementById('filterCategory').addEventListener('change', function() {
    this.closest('form').submit();
});

document.getElementById('rmEditModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    document.getElementById('edit_rm_id').value = button.getAttribute('data-id');
    document.getElementById('edit_rm_code').value = button.getAttribute('data-code');
    document.getElementById('edit_rm_name').value = button.getAttribute('data-name');
    document.getElementById('edit_rm_category').value = button.getAttribute('data-category');
    document.getElementById('edit_rm_uom').value = button.getAttribute('data-uom');
    document.getElementById('edit_rm_stock').value = button.getAttribute('data-stock');
    document.getElementById('edit_rm_allocated').value = button.getAttribute('data-allocated');
    document.getElementById('edit_rm_reorder').value = button.getAttribute('data-reorder');
    document.getElementById('edit_rm_active').value = button.getAttribute('data-active');
});

document.querySelectorAll('.where-used-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var rmId = this.getAttribute('data-rm-id');
        var rmName = this.getAttribute('data-rm-name');
        document.getElementById('whereUsedRmName').textContent = rmName;
        document.getElementById('whereUsedLoading').classList.remove('d-none');
        document.getElementById('whereUsedResults').classList.add('d-none');
        new bootstrap.Modal(document.getElementById('whereUsedModal')).show();

        fetch('?controller=admin&action=whereUsed&raw_material_id=' + rmId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                document.getElementById('whereUsedLoading').classList.add('d-none');
                document.getElementById('whereUsedResults').classList.remove('d-none');
                var tbody = document.getElementById('whereUsedBody');
                tbody.innerHTML = '';
                if (data.length === 0) {
                    document.getElementById('whereUsedEmpty').classList.remove('d-none');
                } else {
                    document.getElementById('whereUsedEmpty').classList.add('d-none');
                    data.forEach(function(row) {
                        var tr = document.createElement('tr');
                        tr.innerHTML = '<td><code>' + row.fg_code + '</code></td><td>' + row.fg_name + '</td><td>' + (row.bom_code || '-') + '</td>';
                        tbody.appendChild(tr);
                    });
                }
            })
            .catch(function() {
                document.getElementById('whereUsedLoading').textContent = 'Failed to load.';
            });
    });
});
</script>
