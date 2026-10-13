<?php
// Reusable "Edit" modal for supplier-order records behind btn-edit-po buttons.
// Corrects typo-level fields (supplier, quantity, unit cost, dates, remarks)
// with received orders clamped to their received quantity server-side.
// Included at the bottom of the views that render .btn-edit-po buttons.
// $poEditContext: 'purchasing' (default) | 'receiving' | 'qc' — drives the title.
$poEditContext = ($poEditContext ?? 'purchasing');
$poEditTitles = [
    'purchasing' => 'Edit Purchasing PO',
    'receiving'  => 'Edit Receiving Record',
    'qc'         => 'Edit Inspected Record',
];
$poEditTitle = $poEditTitles[$poEditContext] ?? $poEditTitles['purchasing'];
?>
<div class="modal fade" id="modalEditPoDetails" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i><?= htmlspecialchars($poEditTitle) ?> — <span id="editPoRef">-</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="editSpinner" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-muted small mt-2">Loading record…</div>
                </div>
                <div id="editError" class="alert alert-danger d-none" role="alert"></div>
                <form id="editPoForm" novalidate>
                    <input type="hidden" id="editPoId">
                    <div class="mb-2">
                        <b>Item:</b> <span id="editPoItem">-</span>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                            <input type="text" id="editSupplier" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Unit Cost</label>
                            <input type="number" step="0.01" min="0" id="editUnitCost" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0.0001" id="editQuantity" class="form-control" required>
                                <span class="input-group-text" id="editUom">-</span>
                            </div>
                            <div class="form-text d-none" id="editReceivedNote"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <input type="text" id="editStatus" class="form-control" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Order Date</label>
                            <input type="date" id="editOrderDate" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Expected Date</label>
                            <input type="date" id="editExpectedDate" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea id="editRemarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editPoSaveBtn"><i class="bi bi-check-lg me-1"></i>Save</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var ENDPOINT = '?controller=warehouse&action=updatePurchasingPo';
    var modalEl = document.getElementById('modalEditPoDetails');
    if (!modalEl) return;
    var modal = null;

    function showError(message) {
        var err = document.getElementById('editError');
        err.textContent = message;
        err.classList.remove('d-none');
    }

    function showLoading(show) {
        document.getElementById('editSpinner').classList.toggle('d-none', !show);
        document.getElementById('editPoForm').classList.toggle('d-none', show);
    }

    document.addEventListener('click', function(e) {
        var btn = e.target.closest ? e.target.closest('.btn-edit-po') : null;
        if (!btn) return;
        e.preventDefault();

        var recordId = btn.getAttribute('data-id');
        if (!recordId) return;

        var errEl = document.getElementById('editError');
        errEl.classList.add('d-none');
        document.getElementById('editPoRef').textContent = '…';
        showLoading(true);
        if (!modal) modal = new bootstrap.Modal(modalEl);
        modal.show();

        fetch(ENDPOINT + '&id=' + encodeURIComponent(recordId), { credentials: 'same-origin' })
            .then(function(res) { return res.json(); })
            .then(function(res) {
                showLoading(false);
                if (!res.success) {
                    showError(res.error || 'Failed to load record.');
                    return;
                }
                var d = res.data;
                document.getElementById('editPoRef').textContent = d.supplier_order_id;
                document.getElementById('editPoId').value = d.supplier_order_id;
                document.getElementById('editPoItem').textContent = (d.item_code || '-') + (d.item_description ? ' — ' + d.item_description : '');
                document.getElementById('editSupplier').value = d.supplier_name || '';
                document.getElementById('editQuantity').value = d.quantity || '';
                document.getElementById('editUom').textContent = d.uom || '';
                document.getElementById('editUnitCost').value = d.unit_cost || '';
                document.getElementById('editStatus').value = d.status || '';
                document.getElementById('editOrderDate').value = d.order_date || '';
                document.getElementById('editExpectedDate').value = d.expected_date || '';
                document.getElementById('editRemarks').value = d.remarks || '';

                var saveBtn = document.getElementById('editPoSaveBtn');
                var receivedNote = document.getElementById('editReceivedNote');
                var received = parseFloat(d.received_qty) || 0;
                if (received > 0) {
                    receivedNote.textContent = 'Already received: ' + received + ' ' + (d.uom || '') + ' — quantity cannot go below this.';
                    receivedNote.classList.remove('d-none');
                } else if (receivedNote) {
                    receivedNote.classList.add('d-none');
                }
                if (d.editable) {
                    saveBtn.disabled = false;
                    document.querySelectorAll('#editPoForm input:not([disabled]), #editPoForm textarea').forEach(function(el) { el.disabled = false; });
                    if (received > 0) document.getElementById('editQuantity').min = received;
                } else {
                    saveBtn.disabled = true;
                    showError('Cancelled or rejected orders cannot be edited.');
                }
            })
            .catch(function() {
                showLoading(false);
                showError('Could not reach the server. Please try again.');
            });
    });

    document.getElementById('editPoSaveBtn').addEventListener('click', function() {
        var errEl = document.getElementById('editError');
        errEl.classList.add('d-none');

        var id = document.getElementById('editPoId').value;
        var supplier = document.getElementById('editSupplier').value.trim();
        var qty = parseFloat(document.getElementById('editQuantity').value);
        if (!id) return showError('Invalid record ID.');
        if (!supplier) return showError('Supplier is required.');
        if (!qty || qty <= 0) return showError('Quantity must be greater than zero.');

        var body = new FormData();
        body.append('supplier_order_id', id);
        body.append('supplier_name', supplier);
        body.append('quantity', qty);
        body.append('unit_cost', document.getElementById('editUnitCost').value);
        body.append('order_date', document.getElementById('editOrderDate').value);
        body.append('expected_date', document.getElementById('editExpectedDate').value);
        body.append('remarks', document.getElementById('editRemarks').value);

        var saveBtn = document.getElementById('editPoSaveBtn');
        saveBtn.disabled = true;

        fetch(ENDPOINT + '&id=' + encodeURIComponent(id), {
            method: 'POST',
            body: body,
            credentials: 'same-origin'
        })
            .then(function(res) { return res.json(); })
            .then(function(res) {
                if (!res.success) {
                    saveBtn.disabled = false;
                    return showError(res.error || 'Failed to save changes.');
                }
                window.location.reload();
            })
            .catch(function() {
                saveBtn.disabled = false;
                showError('Could not reach the server. Please try again.');
            });
    });
})();
</script>
