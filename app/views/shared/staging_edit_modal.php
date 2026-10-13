<?php
// "Edit Receiving (staged)" modal behind .btn-edit-staging buttons.
// Edits the PENDING_QC receiving_items batch of a supplier order:
// received qty, lot number, expiry date and remarks. The order row id passed
// to the buttons is the supplier_order_id; the resolved staging row id is
// posted back for the save.
$stgEditTitle = ($stgEditTitle ?? 'Edit Receiving (staged)');
?>
<div class="modal fade" id="modalEditStaging" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i><?= htmlspecialchars($stgEditTitle) ?> — <span id="stgEditRef">-</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="stgEditSpinner" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-muted small mt-2">Loading record…</div>
                </div>
                <div id="stgEditError" class="alert alert-danger d-none" role="alert"></div>
                <form id="stgEditForm" novalidate>
                    <input type="hidden" id="stgEditOrderId">
                    <input type="hidden" id="stgEditRowId">
                    <div class="mb-2">
                        <b>Item:</b> <span id="stgEditItem">-</span>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Received Qty <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.0001" min="0.0001" id="stgEditQty" class="form-control" required>
                                <span class="input-group-text" id="stgEditUom">-</span>
                            </div>
                            <div class="form-text">Changing this adjusts the inspection-hold balance.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Staged On</label>
                            <input type="text" id="stgEditDate" class="form-control" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Lot Number</label>
                            <input type="text" id="stgEditLot" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Expiry Date</label>
                            <input type="date" id="stgEditExpiry" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea id="stgEditRemarks" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="stgEditSaveBtn"><i class="bi bi-check-lg me-1"></i>Save</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var modalEl = document.getElementById('modalEditStaging');
    if (!modalEl) return;
    var modal = null;

    function showError(message) {
        var err = document.getElementById('stgEditError');
        err.textContent = message;
        err.classList.remove('d-none');
    }

    function showLoading(show) {
        document.getElementById('stgEditSpinner').classList.toggle('d-none', !show);
        document.getElementById('stgEditForm').classList.toggle('d-none', show);
    }

    // Auto-generates the staging remarks the same way receiving does.
    function buildRemarks() {
        var parts = [];
        var lot = document.getElementById('stgEditLot').value.trim();
        var expiry = document.getElementById('stgEditExpiry').value.trim();
        var remarks = document.getElementById('stgEditRemarks').value.trim();
        if (lot) parts.push('Lot: ' + lot);
        if (expiry) parts.push('Expiry: ' + expiry);
        if (remarks) parts.push(remarks);
        return parts.join(' | ');
    }

    document.addEventListener('click', function(e) {
        var btn = e.target.closest ? e.target.closest('.btn-edit-staging') : null;
        if (!btn) return;
        e.preventDefault();

        var orderId = btn.getAttribute('data-id');
        if (!orderId) return;

        document.getElementById('stgEditError').classList.add('d-none');
        document.getElementById('stgEditRef').textContent = '…';
        showLoading(true);
        if (!modal) modal = new bootstrap.Modal(modalEl);
        modal.show();

        fetch('?controller=warehouse&action=updateReceivingStaging&id=' + encodeURIComponent(orderId), { credentials: 'same-origin' })
            .then(function(res) { return res.json(); })
            .then(function(res) {
                showLoading(false);
                if (!res.success) {
                    showError(res.error || 'Failed to load staged receipt.');
                    return;
                }
                var d = res.data;
                document.getElementById('stgEditRef').textContent = 'SO #' + d.supplier_order_id;
                document.getElementById('stgEditOrderId').value = d.supplier_order_id;
                document.getElementById('stgEditRowId').value = d.id;
                document.getElementById('stgEditItem').textContent = (d.item_code || '-') + (d.item_name ? ' — ' + d.item_name : '');                                document.getElementById('stgEditQty').value = (parseFloat(d.received_qty) || 0).toString();
                document.getElementById('stgEditUom').textContent = d.uom || '';
                document.getElementById('stgEditDate').value = d.received_date || '';
                document.getElementById('stgEditLot').value = d.lot_number || '';
                document.getElementById('stgEditExpiry').value = d.expiry_date || '';
                // Strip the auto-generated "Lot: ... | Expiry: ... |" prefix so the
                // textarea only holds the user's free-text remarks.
                var rawRemarks = d.remarks || '';
                rawRemarks = rawRemarks.replace(/^(Lot:[^|]*\|\s*)?(Expiry:[^|]*(\|\s*)?)?/i, '');
                document.getElementById('stgEditRemarks').value = rawRemarks.trim();
            })
            .catch(function() {
                showLoading(false);
                showError('Could not reach the server. Please try again.');
            });
    });

    document.getElementById('stgEditSaveBtn').addEventListener('click', function() {
        var errEl = document.getElementById('stgEditError');
        errEl.classList.add('d-none');

        var rowId = document.getElementById('stgEditRowId').value;
        var orderId = document.getElementById('stgEditOrderId').value;
        var qty = parseFloat(document.getElementById('stgEditQty').value);
        if (!rowId || !orderId) return showError('Invalid record.');
        if (!qty || qty <= 0) return showError('Received quantity must be greater than zero.');

        var body = new FormData();
        body.append('id', rowId);
        body.append('received_qty', qty);
        body.append('lot_number', document.getElementById('stgEditLot').value);
        body.append('expiry_date', document.getElementById('stgEditExpiry').value);
        body.append('remarks', buildRemarks());

        var saveBtn = document.getElementById('stgEditSaveBtn');
        saveBtn.disabled = true;

        fetch('?controller=warehouse&action=updateReceivingStaging&id=' + encodeURIComponent(rowId), {
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
