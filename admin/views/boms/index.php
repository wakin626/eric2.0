<div class="d-flex justify-content-between mb-4">
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bomModal">Add BOM</button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importBomModal"><i class="bi bi-upload me-1"></i>Import BOMs</button>
    </div>
    <form method="GET" class="d-flex" style="width:30%">
        <input type="hidden" name="controller" value="admin">
        <input type="hidden" name="action" value="boms">
        <input type="text" name="search" id="searchBom" class="form-control" placeholder="Search BOM code, item..." value="<?= htmlspecialchars($search ?? '') ?>">
    </form>
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>BOM Code</th>
                    <th>Finished Good</th>
                    <th>FG Item Code</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($boms as $b): ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td>
                        <strong class="text-primary"><?= htmlspecialchars($b['bom_code'] ?? '-') ?></strong>
                        <?php if (!empty($b['is_legacy_formula'])): ?>
                        <span class="badge bg-warning text-dark" title="This BOM uses legacy lot-based calculations. Edit and re-save to convert.">Legacy</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($b['fg_name']) ?></td>
                    <td><?= htmlspecialchars($b['fg_code']) ?></td>
                    <td><?= date('Y-m-d', strtotime($b['created_at'])) ?></td>
                    <td>
                        <a href="?controller=admin&action=bomEdit&id=<?= $b['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></a>
                        <a href="?controller=admin&action=bomDelete&id=<?= $b['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this BOM and all its line items?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($boms)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No BOMs found</td></tr>
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
            <a class="page-link" href="?controller=admin&action=boms&page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>">&laquo; Prev</a>
        </li>
        <?php foreach ($pages as $p): ?>
            <?php if ($p === '...'): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php else: ?>
            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                <a class="page-link" href="?controller=admin&action=boms&page=<?= $p ?>&search=<?= urlencode($search ?? '') ?>"><?= $p ?></a>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?controller=admin&action=boms&page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>">Next &raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<!-- Create BOM Modal -->
<div class="modal fade" id="bomModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Create New BOM</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="?controller=admin&action=bomCreate">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Finished Good Item *</label>
                        <select name="fg_item_id" class="form-select filter-select" required>
                            <option value="">-- Select Finished Good --</option>
                            <?php
                            $existingItemIds = array_column($boms, 'fg_item_id');
                            foreach ($allItems as $item):
                                $alreadyHasBom = in_array($item['item_id'], $existingItemIds);
                            ?>
                            <option value="<?= $item['item_id'] ?>" <?= $alreadyHasBom ? 'disabled' : '' ?>>
                                <?= htmlspecialchars($item['item_code'] . ' - ' . $item['item_description']) ?>
                                <?= $alreadyHasBom ? ' (BOM exists)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Items with existing BOMs are disabled.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">BOM Code *</label>
                        <input type="text" name="bom_code" class="form-control" placeholder="e.g. BOM-001" required>
                    </div>
                    <div class="row g-3 mb-2">
                        <div class="col-md-4">
                            <label class="form-label">Fill Volume *</label>
                            <input type="number" step="0.0001" min="0.0001" name="fill_volume" class="form-control" value="1.0000" required>
                            <small class="text-muted">Net volume/weight per piece.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">UOM *</label>
                            <select name="uom" class="form-select filter-select" required>
                                <?php
                                $uoms = ['Kg','g','mg','L','mL','PCS','Set','Box','Pack','Roll','Meter','cm','mm','ft','inch','yd','Pairs','Pcs/Case','Pallet','Sheet','Bag','Drum','Barrel','Carton','Lot','Unit'];
                                foreach ($uoms as $u): ?>
                                    <option value="<?= $u ?>" <?= $u === 'mL' ? 'selected' : '' ?>><?= $u ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">UOM Divisor *</label>
                            <input type="number" step="any" min="0.0001" name="batch_unit_divisor" class="form-control" value="1000" required>
                            <small class="text-muted">(Order Qty &times; Fill Volume) / UOM Divisor.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Create & Edit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import BOMs Modal -->
<div class="modal fade" id="importBomModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Import BOM Components</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="?controller=admin&action=bomImportPreview" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select File (.csv or .xlsx)</label>
                        <input type="file" name="import_file" class="form-control" accept=".csv,.xlsx" required>
                    </div>
                    <div class="alert alert-info py-2 mb-0">
                        <strong>Expected columns:</strong> FG_ITEM_CODE, BOM_CODE, RM_CODE, DOSAGE_RATE, WASTAGE_PCT<br>
                        <small>Multiple rows with same FG_ITEM_CODE = one BOM with multiple components. Importing replaces the entire BOM component list.</small>
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

<script>
var _searchTimer;
document.getElementById('searchBom').addEventListener('input', function() {
    clearTimeout(_searchTimer);
    var form = this.closest('form');
    _searchTimer = setTimeout(function() { form.submit(); }, 500);
});
</script>
