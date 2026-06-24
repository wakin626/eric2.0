<div class="card quick-add">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i><?= isset($item) ? 'Edit Item' : 'Add New Item' ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" autocomplete="off">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Item Code <span class="text-danger">*</span></label>
                    <input type="text" name="item_code" class="form-control" 
                           value="<?= htmlspecialchars($item['item_code'] ?? '') ?>" 
                           placeholder="e.g., ITEM-001" required autofocus>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Description <span class="text-danger">*</span></label>
                    <input type="text" name="item_description" class="form-control" 
                           value="<?= htmlspecialchars($item['item_description'] ?? '') ?>" 
                           placeholder="Enter item description" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Unit of Measurement <span class="text-danger">*</span></label>
                    <select name="item_uom" id="form_item_uom" class="form-select" required>
                        <option value="">Select UOM</option>
                        <?php foreach ($uoms as $uom): ?>
                            <option value="<?= $uom ?>" <?= ($item['item_uom'] ?? '') == $uom ? 'selected' : '' ?>><?= $uom ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4" id="formConversionGroup" style="display:none;">
                    <label class="form-label">Conversion (per case)</label>
                    <input type="number" name="uom_conversion" class="form-control" min="1"
                           value="<?= htmlspecialchars($item['uom_conversion'] ?? '') ?>"
                           placeholder="e.g. 10 means 10 PCS = 1 CS">
                </div>
                <?php if (isset($item)): ?>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="1" <?= ($item['status'] ?? 1) == 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= ($item['status'] ?? 1) == 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> <?= isset($item) ? 'Update Item' : 'Save Item' ?>
                    </button>
                    <a href="?controller=admin&action=items" class="btn btn-secondary ms-2">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var uomSel = document.getElementById('form_item_uom');
    var convGroup = document.getElementById('formConversionGroup');
    function toggleConversion() {
        convGroup.style.display = (uomSel.value === 'PCS' || uomSel.value === 'PCKS') ? '' : 'none';
    }
    uomSel.addEventListener('change', toggleConversion);
    toggleConversion();
})();
</script>