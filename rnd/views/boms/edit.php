<div class="d-flex justify-content-between mb-4">
    <div class="d-flex gap-2">
        <a href="?controller=rnd&action=boms" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to BOMs</a>
    </div>
</div>

<div class="row">
    <!-- BOM Header Card -->
    <div class="col-md-5">
        <div class="card data-card mb-4">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>BOM Details</div>
            <div class="card-body">
                <form method="POST" action="?controller=rnd&action=bomUpdate">
                    <input type="hidden" name="bom_id" value="<?= $bom['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Finished Good</label>
                        <select name="fg_item_id" class="form-select" required>
                            <?php foreach ($allItems as $item): ?>
                            <option value="<?= $item['item_id'] ?>" <?= $item['item_id'] == $bom['fg_item_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item['item_code'] . ' - ' . $item['item_description']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">BOM Code</label>
                        <input type="text" name="bom_code" class="form-control" value="<?= htmlspecialchars($bom['bom_code'] ?? '') ?>">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Batch Quantity</label>
                            <input type="number" step="0.0001" min="0.0001" name="batch_qty" class="form-control" value="<?= number_format($bom['batch_qty'] ?? 1, 4, '.', '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Batch UoM</label>
                            <select name="batch_uom" class="form-select" required>
                                <?php
                                $currentUom = $bom['batch_uom'] ?? 'PCS';
                                $uoms = ['Kg','g','mg','L','mL','PCS','Set','Box','Pack','Roll','Meter','cm','mm','ft','inch','yd','Pairs','Pcs/Case','Pallet','Sheet','Bag','Drum','Barrel','Carton','Lot','Unit'];
                                foreach ($uoms as $u): ?>
                                    <option value="<?= $u ?>" <?= $u === $currentUom ? 'selected' : '' ?>><?= $u ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Created</label>
                        <input type="text" class="form-control" value="<?= date('Y-m-d H:i', strtotime($bom['created_at'])) ?>" disabled>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update BOM</button>
                </form>
            </div>
        </div>
    </div>

    <!-- BOM Line Items Card -->
    <div class="col-md-7">
        <div class="card data-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ul me-2"></i>Recipe Components (<?= count($bomItems) ?>)</span>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal"><i class="bi bi-plus-lg me-1"></i>Add Component</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Ingredient</th>
                            <th>Code</th>
                            <th>UOM</th>
                            <th>Dosage Rate</th>
                            <th>Wastage %</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bomItemsBody">
                        <?php foreach ($bomItems as $bi): ?>
                        <tr data-item-id="<?= $bi['id'] ?>">
                            <td><?= htmlspecialchars($bi['rm_name']) ?></td>
                            <td><code><?= htmlspecialchars($bi['rm_code']) ?></code></td>
                            <td><?= htmlspecialchars($bi['rm_uom']) ?></td>
                            <td><?= number_format($bi['dosage_rate'], 6) ?></td>
                            <td><?= number_format($bi['wastage_allowance_pct'], 2) ?>%</td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-bom-item"
                                    data-bom-item-id="<?= $bi['id'] ?>"
                                    data-item-id="<?= $bi['item_id'] ?>"
                                    data-dosage="<?= $bi['dosage_rate'] ?>"
                                    data-wastage="<?= $bi['wastage_allowance_pct'] ?>"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-danger remove-bom-item" data-item-id="<?= $bi['id'] ?>"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bomItems)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No components added yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Component Modal -->
<div class="modal fade" id="addItemModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Component</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="add_bom_id" value="<?= $bom['id'] ?>">
                <div class="mb-3">
                    <label class="form-label">Ingredient *</label>
                    <select id="add_item_id" class="form-select" required>
                        <option value="">-- Select Ingredient --</option>
                        <?php foreach ($allIngredients as $ing): ?>
                        <option value="<?= $ing['item_id'] ?>"><?= htmlspecialchars($ing['item_code'] . ' - ' . $ing['item_description'] . ' (' . $ing['item_uom'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Dosage Rate *</label>
                        <input type="number" step="0.000001" id="add_dosage" class="form-control" value="0" required>
                        <small class="text-muted">Quantity per unit of FG</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Wastage Allowance %</label>
                        <input type="number" step="0.01" id="add_wastage" class="form-control" value="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="saveAddItem">Add</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Component Modal -->
<div class="modal fade" id="editItemModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Component</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="edit_bom_item_id">
                <div class="mb-3">
                    <label class="form-label">Ingredient *</label>
                    <select id="edit_item_id" class="form-select" required>
                        <option value="">-- Select Ingredient --</option>
                        <?php foreach ($allIngredients as $ing): ?>
                        <option value="<?= $ing['item_id'] ?>"><?= htmlspecialchars($ing['item_code'] . ' - ' . $ing['item_description'] . ' (' . $ing['item_uom'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Dosage Rate *</label>
                        <input type="number" step="0.000001" id="edit_dosage" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Wastage Allowance %</label>
                        <input type="number" step="0.01" id="edit_wastage" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="saveEditItem">Update</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('saveAddItem').addEventListener('click', function() {
    var bomId = document.getElementById('add_bom_id').value;
    var itemId = document.getElementById('add_item_id').value;
    var dosage = document.getElementById('add_dosage').value;
    var wastage = document.getElementById('add_wastage').value;

    if (!itemId) { alert('Select an ingredient'); return; }

    var formData = new FormData();
    formData.append('bom_id', bomId);
    formData.append('item_id', itemId);
    formData.append('dosage_rate', dosage);
    formData.append('wastage_allowance_pct', wastage);

    fetch('?controller=rnd&action=bomAddItem', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to add component');
            }
        })
        .catch(function() { alert('Network error'); });
});

document.querySelectorAll('.edit-bom-item').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('edit_bom_item_id').value = this.getAttribute('data-bom-item-id');
        document.getElementById('edit_item_id').value = this.getAttribute('data-item-id');
        document.getElementById('edit_dosage').value = this.getAttribute('data-dosage');
        document.getElementById('edit_wastage').value = this.getAttribute('data-wastage');
        new bootstrap.Modal(document.getElementById('editItemModal')).show();
    });
});

document.getElementById('saveEditItem').addEventListener('click', function() {
    var bomItemId = document.getElementById('edit_bom_item_id').value;
    var itemId = document.getElementById('edit_item_id').value;
    var dosage = document.getElementById('edit_dosage').value;
    var wastage = document.getElementById('edit_wastage').value;

    if (!itemId) { alert('Select an ingredient'); return; }

    var formData = new FormData();
    formData.append('bom_item_id', bomItemId);
    formData.append('item_id', itemId);
    formData.append('dosage_rate', dosage);
    formData.append('wastage_allowance_pct', wastage);

    fetch('?controller=rnd&action=bomUpdateItem', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to update component');
            }
        })
        .catch(function() { alert('Network error'); });
});

document.querySelectorAll('.remove-bom-item').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (!confirm('Remove this component?')) return;
        var itemId = this.getAttribute('data-item-id');
        var formData = new FormData();
        formData.append('item_id', itemId);

        fetch('?controller=rnd&action=bomRemoveItem', { method: 'POST', body: formData })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    var row = document.querySelector('tr[data-item-id="' + itemId + '"]');
                    if (row) row.remove();
                } else {
                    alert(data.error || 'Failed to remove');
                }
            })
            .catch(function() { alert('Network error'); });
    });
});
</script>
