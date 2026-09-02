<h4><i class="bi bi-box-seam me-2"></i>Finished Goods(FG)</h4>
<p class="text-muted mb-4">Input finished goods production</p>

<div class="card data-card">
    <div class="card-body">
        <form method="POST" action="?controller=production&action=saveFgInput" id="fgInputForm" novalidate>
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Prepared by *</label>
                    <input type="text" name="prepared_by_name" class="form-control" required placeholder="e.g. Juan Dela Cruz" value="<?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Checked by</label>
                    <input type="text" name="checked_by_name" class="form-control" placeholder="Optional">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Received by</label>
                    <input type="text" name="received_by_name" class="form-control" placeholder="Optional">
                </div>
            </div>

            <hr>

            <div class="mb-3">
                <label class="form-label fw-bold mb-0">Lot Entries</label>
            </div>

            <div id="lotContainer">
                <div class="lot-entry mb-3 border rounded p-3 bg-light" data-entry-index="0">
                    <div class="row g-2 mb-2">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Select Item *</label>
                            <div class="position-relative">
                                <input type="text" name="item_search[]" class="form-control item-search-input" placeholder="Search by item code or description..." autocomplete="off" required>
                                <input type="hidden" name="item_id[]" class="selected-item-id" value="">
                                <div class="item-dropdown position-absolute w-100 bg-white border rounded shadow-sm d-none" style="z-index: 1050; max-height: 300px; overflow-y: auto;"></div>
                            </div>
                            <div class="selected-item-info mt-1 d-none">
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i><span class="selected-item-name"></span></span>
                                <button type="button" class="btn btn-sm btn-link text-danger clear-item-btn"><i class="bi bi-x"></i> Clear</button>
                            </div>
                        </div>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">Lot Number <span class="text-danger">*</span></label>
                            <input type="text" name="lot_number[]" class="form-control" placeholder="e.g. 152-202" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Add Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="added_quantity[]" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">PCS to CASE</label>
                            <input type="number" name="pcs_per_case[]" class="form-control" min="1" placeholder="PCS to CASE">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Shift <span class="text-danger">*</span></label>
                            <select name="shift[]" class="form-select" required>
                                <option value="">-- Select --</option>
                                <option value="1st Shift">1st Shift</option>
                                <option value="2nd Shift">2nd Shift</option>
                                <option value="3rd Shift">3rd Shift</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="reject_status[]" class="form-select">
                                <option value="Good">Good</option>
                            </select>
                        </div>
                        <div class="col-md-2 text-end">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-danger btn-sm remove-lot" style="display:none;"><i class="bi bi-trash"></i> Remove</button>
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label class="form-label small">Remarks</label>
                            <input type="text" name="sts_remarks[]" class="form-control form-control-sm" placeholder="Optional remarks">
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-primary btn-sm mb-3" id="addLotBtn"><i class="bi bi-plus"></i> Add Lot</button>

            <hr>

            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">STS reference numbers will be auto-generated upon save.</small>
                <div class="d-flex gap-2">
                    <a href="?controller=production&action=purchaseOrders" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="submitBtn"><i class="bi bi-save me-2"></i>Save FG Input</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Confirm FG Input</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Please review the FG production details before saving.</p>
                <div id="prevLots"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmSaveBtn"><i class="bi bi-check-lg me-1"></i>Confirm & Save</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function initItemSearch(entry) {
        var searchInput = entry.querySelector('.item-search-input');
        var dropdown = entry.querySelector('.item-dropdown');
        var selectedIdInput = entry.querySelector('.selected-item-id');
        var selectedItemInfo = entry.querySelector('.selected-item-info');
        var selectedItemName = entry.querySelector('.selected-item-name');
        var clearBtn = entry.querySelector('.clear-item-btn');
        var searchTimeout = null;

        searchInput.addEventListener('input', function() {
            var q = this.value.trim();
            clearTimeout(searchTimeout);
            if (q.length < 1) {
                dropdown.classList.add('d-none');
                return;
            }
            searchTimeout = setTimeout(function() {
                fetch('?controller=production&action=searchItems&q=' + encodeURIComponent(q))
                    .then(function(r) { return r.json(); })
                    .then(function(items) {
                        dropdown.innerHTML = '';
                        if (!items || items.length === 0) {
                            dropdown.innerHTML = '<div class="p-2 text-muted">No items found</div>';
                            dropdown.classList.remove('d-none');
                            return;
                        }
                        var seenIds = new Set();
                        items.forEach(function(item) {
                            if (seenIds.has(item.item_id)) return;
                            seenIds.add(item.item_id);
                            var div = document.createElement('div');
                            div.className = 'p-2 border-bottom item-option';
                            div.style.cursor = 'pointer';
                            div.innerHTML = '<strong>' + (item.item_code || '') + '</strong> - ' + (item.item_description || '') +
                                '<br><small class="text-muted">UOM: ' + (item.item_uom || '') + (item.uom_conversion ? ' (' + item.uom_conversion + ' pcs/case)' : '') + '</small>';
                            div.addEventListener('click', function() {
                                selectedIdInput.value = item.item_id;
                                searchInput.value = item.item_code + ' - ' + item.item_description;
                                selectedItemName.textContent = item.item_code + ' - ' + item.item_description;
                                selectedItemInfo.classList.remove('d-none');
                                dropdown.classList.add('d-none');
                            });
                            div.addEventListener('mouseenter', function() { this.style.background = '#f0f4ff'; });
                            div.addEventListener('mouseleave', function() { this.style.background = ''; });
                            dropdown.appendChild(div);
                        });
                        dropdown.classList.remove('d-none');
                    });
            }, 300);
        });

        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 1 && dropdown.children.length > 0) {
                dropdown.classList.remove('d-none');
            }
        });

        clearBtn.addEventListener('click', function() {
            selectedIdInput.value = '';
            searchInput.value = '';
            selectedItemInfo.classList.add('d-none');
        });
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.item-search-input') && !e.target.closest('.item-dropdown')) {
            document.querySelectorAll('.item-dropdown').forEach(function(d) { d.classList.add('d-none'); });
        }
    });

    function initLotEntry(entry) {
        initItemSearch(entry);
        updateRemoveButtons();
    }

    var firstEntry = document.querySelector('#lotContainer .lot-entry');
    if (firstEntry) initLotEntry(firstEntry);

    document.getElementById('addLotBtn').addEventListener('click', function() {
        var container = document.getElementById('lotContainer');
        var entries = container.querySelectorAll('.lot-entry');
        var firstEntry = entries[0];
        var entry = firstEntry.cloneNode(true);

        entry.querySelectorAll('input').forEach(function(el) { el.value = ''; });
        entry.querySelectorAll('select').forEach(function(el) { el.selectedIndex = 0; });
        entry.querySelectorAll('.selected-item-info').forEach(function(el) { el.classList.add('d-none'); });
        entry.querySelectorAll('.item-dropdown').forEach(function(el) { el.classList.add('d-none'); });

        container.appendChild(entry);
        initLotEntry(entry);
        entry.scrollIntoView({ behavior: 'smooth', block: 'center' });
        var firstInput = entry.querySelector('.item-search-input');
        if (firstInput) firstInput.focus();
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-lot')) {
            var entry = e.target.closest('.lot-entry');
            var container = document.getElementById('lotContainer');
            if (container.querySelectorAll('.lot-entry').length > 1) {
                entry.remove();
                updateRemoveButtons();
            }
        }
    });

    function updateRemoveButtons() {
        var entries = document.querySelectorAll('#lotContainer .lot-entry');
        entries.forEach(function(entry) {
            var btn = entry.querySelector('.remove-lot');
            if (btn) btn.style.display = entries.length > 1 ? 'inline-flex' : 'none';
        });
    }

    document.getElementById('fgInputForm').addEventListener('submit', function(e) {
        e.preventDefault();

        var entries = document.querySelectorAll('#lotContainer .lot-entry');
        var hasValidLot = false;
        var missingItem = false;
        entries.forEach(function(entry) {
            var itemId = entry.querySelector('.selected-item-id').value;
            var lotNum = entry.querySelector('input[name="lot_number[]"]').value.trim();
            var qty = parseInt(entry.querySelector('input[name="added_quantity[]"]').value) || 0;
            if (lotNum && qty > 0) {
                hasValidLot = true;
                if (!itemId) missingItem = true;
            }
        });

        if (!hasValidLot) {
            alert('Please enter at least one valid lot with a lot number and quantity.');
            return;
        }
        if (missingItem) {
            alert('Please select an item for all filled lot entries.');
            return;
        }

        var shiftOk = true;
        entries.forEach(function(entry) {
            var lotNum = entry.querySelector('input[name="lot_number[]"]').value.trim();
            var qty = parseInt(entry.querySelector('input[name="added_quantity[]"]').value) || 0;
            if (lotNum && qty > 0 && !entry.querySelector('select[name="shift[]"]').value) {
                shiftOk = false;
            }
        });
        if (!shiftOk) {
            alert('Please select a shift for all filled lot entries.');
            return;
        }

        var lotsHtml = '<table class="table table-sm table-bordered"><thead><tr><th>Item</th><th>Lot No.</th><th>Quantity</th><th>PCS/Case</th><th>Shift</th></tr></thead><tbody>';
        entries.forEach(function(entry) {
            var lotNum = entry.querySelector('input[name="lot_number[]"]').value.trim();
            var qty = entry.querySelector('input[name="added_quantity[]"]').value;
            var conv = entry.querySelector('input[name="pcs_per_case[]"]').value;
            var shift = entry.querySelector('select[name="shift[]"]').value;
            var itemName = entry.querySelector('.selected-item-name') ? entry.querySelector('.selected-item-name').textContent : '';
            if (lotNum && parseInt(qty) > 0) {
                lotsHtml += '<tr><td>' + itemName + '</td><td>' + lotNum + '</td><td>' + qty + '</td><td>' + (conv || '-') + '</td><td>' + shift + '</td></tr>';
            }
        });
        lotsHtml += '</tbody></table>';
        document.getElementById('prevLots').innerHTML = lotsHtml;

        var previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
        previewModal.show();
    });

    document.getElementById('confirmSaveBtn').addEventListener('click', function() {
        document.getElementById('fgInputForm').submit();
    });
});
</script>
