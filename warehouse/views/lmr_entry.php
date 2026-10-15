<style>
    .lmr-card {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
    }

    .lmr-section-title {
        font-size: 0.78rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
    }

    .lmr-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 1.25rem;
    }

    .table-soft {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }

    .table-soft thead th {
        background: #f8fafc;
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #475569;
        border-bottom: 1px solid #e2e8f0;
    }

    .table-soft tbody td {
        vertical-align: middle;
        border-bottom: 1px solid #edf2f7;
        padding: 0.7rem 0.75rem;
        font-size: 0.9rem;
    }

    .sticky-toolbar {
        position: sticky;
        top: 0;
        z-index: 3;
        background: rgba(255,255,255,0.96);
        backdrop-filter: blur(8px);
    }

    #formValidationAlert {
        font-size: 0.88rem;
    }

    #formValidationAlert ul {
        margin-bottom: 0;
        padding-left: 1.2rem;
    }

    #lmrEntryForm .is-invalid,
    #lmrEntryForm select.is-invalid,
    #lmrEntryForm input.is-invalid {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
    }

</style>

<div class="lmr-toolbar sticky-toolbar pb-2">
    <div>
        <h5 class="mb-1"><i class="bi bi-clipboard-check me-2 text-primary"></i>LMR Entry</h5>
        <small class="text-muted">PPIC work order entry and material line tracking</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="?controller=lmr&action=index" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to LMR List
        </a>
        <button type="button" id="btn-save-lmr" class="btn btn-primary btn-sm">
            <i class="bi bi-check2-circle me-1"></i>Save LMR
        </button>
    </div>
</div>

<div id="formValidationAlert" class="alert alert-danger d-none" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2 mt-1 flex-shrink-0"></i>
    <span id="formValidationMessage" class="flex-grow-1"></span>
    <button type="button" class="btn-close ms-2 mt-1" id="formValidationClose" aria-label="Close"></button>
</div>

<form id="lmrEntryForm" class="lmr-card p-4" method="POST" action="<?= !empty($lmr) ? '?controller=lmr&action=update' : '?controller=lmr&action=store' ?>" novalidate>
    <?php if (!empty($lmr)): ?>
        <input type="hidden" name="lmr_id" value="<?= (int) $lmr['lmr_id'] ?>">
    <?php endif; ?>
    <input type="hidden" name="customer_id" id="customerIdHidden">
    <input type="hidden" name="customer_name" id="customerNameHidden">
    <input type="hidden" name="customer_code" id="customerCodeHidden">
    <div class="row g-4 mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">LMR Number <span class="text-danger">*</span></label>
                <input type="text" name="lmr_number" class="form-control" placeholder="Enter LMR number" value="<?= htmlspecialchars($lmr['lmr_number'] ?? '') ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">LMR Site</label>
                <select name="lmr_site" class="form-select filter-select">
                    <option value="001 - Sterling Technopark" selected>001 - Sterling Technopark</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Customer Code</label>
                <select name="customer_code" id="customerCode" class="form-select filter-select">
                    <option value="">Select customer</option>
                    <?php foreach ($customers ?? [] as $customer): ?>
                        <option value="<?= htmlspecialchars($customer['customer_id']) ?>" data-name="<?= htmlspecialchars($customer['customer_name'] ?? '') ?>" data-code="<?= htmlspecialchars($customer['customer_code'] ?? '') ?>" <?= (int) ($lmr['customer_id'] ?? 0) === (int) $customer['customer_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($customer['customer_code'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Customer Name</label>
                <input type="text" id="customerName" class="form-control" readonly placeholder="Auto-fills after selecting customer" value="<?= htmlspecialchars($lmr['customer_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">PO Number <span class="text-danger">*</span></label>
                <select name="po_number" id="poNumber" class="form-select filter-select" data-selected-po="<?= htmlspecialchars($lmr['po_number'] ?? '') ?>">
                    <option value="">Select open PO</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Formulation (BOM Code) <span class="text-danger">*</span></label>
                <select name="bom_code" id="bomCode" class="form-select filter-select" required data-selected-bom="<?= htmlspecialchars($lmr['bom_code'] ?? '') ?>">
                    <option value="">Select customer to load formulations</option>
                    <?php foreach ($boms ?? [] as $bom): ?>
                        <option value="<?= htmlspecialchars($bom['bom_code']) ?>" data-item-id="<?= htmlspecialchars($bom['fg_item_id']) ?>" data-item-code="<?= htmlspecialchars($bom['item_code'] ?? '') ?>" data-item-description="<?= htmlspecialchars($bom['item_description'] ?? '') ?>" data-customer-id="<?= htmlspecialchars($bom['customer_id'] ?? '') ?>">
                            <?= htmlspecialchars($bom['bom_code']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Item Number</label>
                <input type="text" name="item_code" id="headerItemCode" class="form-control" readonly placeholder="Auto-fills from formulation" value="<?= htmlspecialchars($lmr['item_code'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Item Description</label>
                <input type="text" name="item_description" id="headerItemDescription" class="form-control" readonly placeholder="Auto-fills from formulation" value="<?= htmlspecialchars($lmr['item_description'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Qty Ordered <span class="text-danger">*</span></label>
                <input type="number" name="qty_ordered" id="headerQtyOrdered" class="form-control" placeholder="Required" value="<?= htmlspecialchars($lmr['qty_ordered'] ?? '') ?>" min="0.01" step="any" required>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Batch / Lot Series</label>
                <select name="lot_series" class="form-select">
                    <option value="">Select</option>
                    <?php $savedLotSeries = htmlspecialchars($lmr['lot_series'] ?? ''); for ($i = 1; $i <= 9; $i++): $bln = '1/' . $i; ?>
                        <option value="<?= $bln ?>"<?= $savedLotSeries === $bln ? ' selected' : '' ?>><?= $bln ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Lot Number</label>
                <input type="text" name="lot_number" class="form-control" placeholder="Optional" value="<?= htmlspecialchars($lmr['lot_number'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Date Started</label>
                <input type="date" name="date_started" class="form-control" value="<?= htmlspecialchars($lmr['date_started'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Date Finished</label>
                <input type="date" name="date_finished" class="form-control" value="<?= htmlspecialchars($lmr['date_finished'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Expiry Date</label>
                <input type="date" name="expiry_date" class="form-control" value="<?= htmlspecialchars($lmr['expiry_date'] ?? '') ?>">
            </div>
        </div>
    </div>

    <div class="mb-4">
        <div class="lmr-section-title mb-3">Component Breakdown <span class="text-muted fs-6 fw-normal">(updates from Formulation &amp; Qty Ordered)</span></div>
        <div id="bomStatusAlert" style="display:none;"></div>
        <div id="bomShortageWarning" style="display:none;"></div>
        <div id="bomComponentPreview" style="display:none;">
            <div class="table-responsive border rounded-3">
                <table class="table table-sm mb-0 align-middle" id="bomComponentTable">
                    <thead class="table-light">
                        <tr>
                            <th>Component Item</th>
                            <th>UOM</th>
                            <th class="text-end">Req. Qty</th>
                            <th class="text-end">SOH</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="bomComponentBody"></tbody>
                </table>
            </div>
            <small class="text-muted d-block mt-1" id="bomBatchInfo"></small>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const customerSelect = document.getElementById('customerCode');
    const customerNameInput = document.getElementById('customerName');
    const customerIdHidden = document.getElementById('customerIdHidden');
    const customerNameHidden = document.getElementById('customerNameHidden');
    const customerCodeHidden = document.getElementById('customerCodeHidden');
    const poSelect = document.getElementById('poNumber');
    const bomCodeSelect = document.getElementById('bomCode');
    const headerItemCode = document.getElementById('headerItemCode');
    const headerItemDescription = document.getElementById('headerItemDescription');
    const headerQtyOrdered = document.getElementById('headerQtyOrdered');
    const bomStatusAlert = document.getElementById('bomStatusAlert');
    const bomComponentPreview = document.getElementById('bomComponentPreview');
    const bomComponentBody = document.getElementById('bomComponentBody');
    const bomBatchInfo = document.getElementById('bomBatchInfo');
    const bomShortageWarning = document.getElementById('bomShortageWarning');

    let bomFetchTimeout = null;

    var allBomOptions = [];
    var bomOptionsCaptured = false;

    function filterBomOptionsByCustomer(customerId, customerCode) {
        if (!bomOptionsCaptured) {
            bomCodeSelect.querySelectorAll('option[data-customer-id]').forEach(function (opt) {
                allBomOptions.push(opt.cloneNode(true));
            });
            bomOptionsCaptured = true;
        }

        var matchCount = 0;
        var prevSel = bomCodeSelect.dataset.selectedBom || '';

        bomCodeSelect.innerHTML = '';

        if (String(customerId) === '') {
            var customerPlaceholder = document.createElement('option');
            customerPlaceholder.value = '';
            customerPlaceholder.textContent = 'Select customer to load formulations';
            bomCodeSelect.appendChild(customerPlaceholder);
        } else {
            allBomOptions.forEach(function (opt) {
                if ((opt.dataset.customerId || '').trim() === String(customerId)) {
                    var clone = opt.cloneNode(true);
                    if (opt.value === prevSel) {
                        clone.selected = true;
                    }
                    bomCodeSelect.appendChild(clone);
                    matchCount++;
                }
            });
            var emptyPlaceholder = document.createElement('option');
            emptyPlaceholder.value = '';
            if (matchCount === 0) {
                emptyPlaceholder.textContent = 'No formulation yet for ' + (customerCode || 'this customer');
            } else {
                emptyPlaceholder.textContent = 'Select formulation';
            }
            bomCodeSelect.insertBefore(emptyPlaceholder, bomCodeSelect.firstChild);
        }

        if (matchCount > 0 && bomCodeSelect.value === prevSel) {
            var selOpt = bomCodeSelect.options[bomCodeSelect.selectedIndex] || null;
            if (selOpt) {
                headerItemCode.value = selOpt.getAttribute('data-item-code') || '';
                headerItemDescription.value = selOpt.getAttribute('data-item-description') || '';
                return;
            }
        }
        bomCodeSelect.value = '';
        headerItemCode.value = '';
        headerItemDescription.value = '';
        resetBomPreview();
        delete bomCodeSelect.dataset.selectedBom;
    }

    function populateCustomerName() {
        const selectedOption = customerSelect.selectedOptions[0];
        const name = selectedOption ? selectedOption.dataset.name || '' : '';
        const code = selectedOption ? selectedOption.dataset.code || '' : '';
        customerNameInput.value = name;
        customerIdHidden.value = selectedOption ? selectedOption.value : '';
        customerNameHidden.value = name;
        customerCodeHidden.value = code;

        const customerId = selectedOption ? selectedOption.value : '';
        poSelect.innerHTML = '<option value="">Select open PO</option>';
        filterBomOptionsByCustomer(customerId, code);

        if (!customerId) {
            return;
        }

        fetch('?controller=warehouse&action=getCustomerPOs&customer_id=' + encodeURIComponent(customerId))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || data.length === 0) {
                    poSelect.innerHTML = '<option value="">No open PO for this customer</option>';
                    delete poSelect.dataset.selectedPo;
                    showFormValidation('No open purchase orders found for <strong>' + escapeHtml(name || code || 'this customer') + '</strong>. Create a Customer PO first.');
                    return;
                }
                var selectedPo = poSelect.dataset.selectedPo || '';
                data.forEach(function (po) {
                    const option = document.createElement('option');
                    option.value = po.po_number || '';
                    option.textContent = po.label || po.po_number || 'PO';
                    if (selectedPo && option.value === selectedPo) {
                        option.selected = true;
                    }
                    poSelect.appendChild(option);
                });
                delete poSelect.dataset.selectedPo;
            })
            .catch(function () {
                poSelect.innerHTML = '<option value="">No open PO found</option>';
            });
    }

    function resetBomPreview() {
        bomStatusAlert.style.display = 'none';
        bomStatusAlert.innerHTML = '';
        bomComponentPreview.style.display = 'none';
        bomComponentBody.innerHTML = '';
        bomBatchInfo.textContent = '';
        bomShortageWarning.style.display = 'none';
        bomShortageWarning.innerHTML = '';
    }

    function renderBomPreview(data) {
        bomStatusAlert.style.display = 'block';
        bomComponentPreview.style.display = 'none';
        bomComponentBody.innerHTML = '';
        bomBatchInfo.textContent = '';
        bomShortageWarning.style.display = 'none';
        bomShortageWarning.innerHTML = '';

        if (data.no_bom) {
            bomStatusAlert.innerHTML = '<div class="alert alert-danger py-2 mb-0"><i class="bi bi-exclamation-triangle me-1"></i><strong>No active BOM found</strong> for this item.</div>';
            return;
        }

        if (data.not_found) {
            bomStatusAlert.innerHTML = '<div class="alert alert-danger py-2 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>BOM <strong>' + (data.bom_code || '') + '</strong> not found in system.</div>';
            return;
        }

        if (!data.components || data.components.length === 0) {
            bomStatusAlert.innerHTML = '<div class="alert alert-success py-2 mb-0"><i class="bi bi-check-circle me-1"></i>BOM <strong>' + data.bom_code + '</strong> found. No components to check.</div>';
            return;
        }

        var batchInfoText;
        if (data.is_legacy_formula) {
            batchInfoText = 'Batch size: ' + data.batch_qty + ' | Batches needed: ' + Number(data.batches_needed || 0).toFixed(4) + ' (legacy formula)';
        } else {
            batchInfoText = 'Fill volume: ' + (data.fill_volume ?? data.batch_qty) + ' ' + (data.uom ?? data.batch_uom ?? '') +
                ' | UOM Divisor: ' + (data.batch_unit_divisor ?? 1000) +
                ' | Bulk batch: ' + Number(data.bulk_batch || 0).toFixed(4);
        }
        bomBatchInfo.textContent = batchInfoText;

        var hasShortage = data.components.some(function (c) { return Number(c.shortage) > 0; });
        if (hasShortage) {
            bomStatusAlert.innerHTML = '<div class="alert alert-warning py-2 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>BOM <strong>' + data.bom_code + '</strong> found — stock shortages detected below.</div>';
            bomShortageWarning.style.display = 'block';
            var shortageItems = data.components.filter(function (c) { return Number(c.shortage) > 0; });
            var shortageListHtml = shortageItems.map(function (c) {
                return '<li><strong>' + c.item_code + '</strong> (' + (c.item_description || '') + ') — Short by <strong>' + c.shortage.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 4}) + ' ' + (c.item_uom || '') + '</strong></li>';
            }).join('');
            bomShortageWarning.innerHTML = '<div class="alert alert-danger py-2 mb-0"><i class="bi bi-x-circle me-1"></i><strong>Shortage Summary:</strong><ul class="mb-0 mt-1 ps-3">' + shortageListHtml + '</ul></div>';
        } else {
            bomStatusAlert.innerHTML = '<div class="alert alert-success py-2 mb-0"><i class="bi bi-check-circle me-1"></i>BOM <strong>' + data.bom_code + '</strong> found — all materials available.</div>';
        }

        bomComponentPreview.style.display = 'block';
        var html = '';
        data.components.forEach(function (c) {
            var statusBadge = c.status === 'available'
                ? '<span class="badge bg-success"><i class="bi bi-check-lg"></i> In Stock</span>'
                : '<span class="badge bg-danger"><i class="bi bi-x-lg"></i> Shortage ' + c.shortage.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 4}) + '</span>';
            html += '<tr>';
            html += '<td><strong>' + c.item_code + '</strong> ' + (c.item_description || '') + '</td>';
            html += '<td>' + (c.item_uom || '') + '</td>';
            html += '<td class="text-end">' + c.required_qty.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 4}) + '</td>';
            html += '<td class="text-end">' + c.soh.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 4}) + '</td>';
            html += '<td>' + statusBadge + '</td>';
            html += '</tr>';
        });
        bomComponentBody.innerHTML = html;
    }

    function fetchBomBreakdown(itemId, qty) {
        if (bomFetchTimeout) {
            clearTimeout(bomFetchTimeout);
        }
        if (!itemId || !qty || qty <= 0) {
            resetBomPreview();
            return;
        }
        bomFetchTimeout = setTimeout(function () {
            fetch('?controller=lmr&action=getBomByItem&item_id=' + encodeURIComponent(itemId) + '&qty=' + encodeURIComponent(qty))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error) {
                        resetBomPreview();
                        return;
                    }
                    renderBomPreview(data);
                })
                .catch(function () {
                    resetBomPreview();
                });
        }, 250);
    }

    function refreshHeaderBomBreakdown() {
        var opt = bomCodeSelect.options[bomCodeSelect.selectedIndex] || null;
        var itemId = (bomCodeSelect.value && opt) ? (opt.getAttribute('data-item-id') || '') : '';
        fetchBomBreakdown(itemId, headerQtyOrdered.value || 0);
    }

    customerSelect.addEventListener('change', populateCustomerName);

    bomCodeSelect.addEventListener('change', function () {
        var opt = this.options[this.selectedIndex];
        headerItemCode.value = this.value ? (opt.getAttribute('data-item-code') || '') : '';
        headerItemDescription.value = this.value ? (opt.getAttribute('data-item-description') || '') : '';
        refreshHeaderBomBreakdown();
    });

    headerQtyOrdered.addEventListener('input', refreshHeaderBomBreakdown);

    var FORM_ALERT_FADE_DELAY = 4500;
    var FORM_ALERT_FADE_MS = 600;
    var validationAlertEl = document.getElementById('formValidationAlert');

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function hideValidationAlertNow() {
        clearTimeout(validationAlertEl._fadeTimer);
        clearTimeout(validationAlertEl._hideTimer);
        validationAlertEl.classList.add('d-none');
        validationAlertEl.style.opacity = '1';
        document.getElementById('formValidationMessage').innerHTML = '';
        document.querySelectorAll('#lmrEntryForm .is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
    }

    function showFormValidation(message) {
        clearTimeout(validationAlertEl._fadeTimer);
        clearTimeout(validationAlertEl._hideTimer);
        validationAlertEl.style.transition = 'opacity ' + (FORM_ALERT_FADE_MS / 1000) + 's ease';
        validationAlertEl.style.opacity = '1';
        document.getElementById('formValidationMessage').innerHTML = message;
        validationAlertEl.classList.remove('d-none');
        validationAlertEl.scrollIntoView({ behavior: 'smooth', block: 'center' });

        validationAlertEl._fadeTimer = setTimeout(function () {
            validationAlertEl.style.opacity = '0';
            validationAlertEl._hideTimer = setTimeout(hideValidationAlertNow, FORM_ALERT_FADE_MS);
        }, FORM_ALERT_FADE_DELAY);
    }

    document.getElementById('formValidationClose').addEventListener('click', hideValidationAlertNow);

    function clearFormValidation() {
        clearTimeout(validationAlertEl._fadeTimer);
        validationAlertEl.classList.add('d-none');
        validationAlertEl.style.opacity = '1';
        document.getElementById('formValidationMessage').innerHTML = '';
        document.querySelectorAll('#lmrEntryForm .is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
    }

    function setSubmitLoading(loading) {
        var btn = document.getElementById('btn-save-lmr');
        if (!btn) return;
        if (loading) {
            btn.disabled = true;
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving...';
        } else {
            btn.disabled = false;
            if (btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
                delete btn.dataset.originalHtml;
            }
        }
    }

    document.getElementById('btn-save-lmr').addEventListener('click', async function () {
        console.log('Save LMR button clicked!');
        try {
            clearFormValidation();

            var errors = [];
            var moNumberInput = document.querySelector('input[name="lmr_number"]');
            var customerSelectEl = document.getElementById('customerCode');
            var firstInvalid = null;

            if (!moNumberInput.value.trim()) {
                errors.push('LMR Number is required.');
                moNumberInput.classList.add('is-invalid');
                if (!firstInvalid) firstInvalid = moNumberInput;
            }

            if (!customerSelectEl.value) {
                errors.push('Please select a Customer Code.');
                customerSelectEl.classList.add('is-invalid');
                if (!firstInvalid) firstInvalid = customerSelectEl;
            }

            var poSelectEl = document.getElementById('poNumber');
            if (!poSelectEl.value) {
                errors.push('PO Number is required for LMR printing linkage.');
                poSelectEl.classList.add('is-invalid');
                if (!firstInvalid) firstInvalid = poSelectEl;
            }

            var qtyOrderedEl = document.getElementById('headerQtyOrdered');
            var bomSelectEl = document.getElementById('bomCode');
            if (!qtyOrderedEl.value || parseFloat(qtyOrderedEl.value) <= 0) {
                errors.push('Qty Ordered must be greater than zero so the component breakdown can be calculated.');
                qtyOrderedEl.classList.add('is-invalid');
                if (!firstInvalid) firstInvalid = qtyOrderedEl;
            }

            if (!bomSelectEl.value) {
                errors.push('Formulation (BOM Code) is required so raw material availability can be checked.');
                bomSelectEl.classList.add('is-invalid');
                if (!firstInvalid) firstInvalid = bomSelectEl;
            }
            else if (bomShortageWarning.style.display === 'block') {
                errors.push('Cannot save: stock shortages exist for component items listed in the Component Breakdown.');
            }

            if (errors.length > 0) {
                showFormValidation('<strong>Please fix the following:</strong><ul><li>' + errors.join('</li><li>') + '</li></ul>');
                if (firstInvalid) firstInvalid.focus();
                console.warn('Form validation failed:', errors);
                return;
            }

            console.log('Form validation passed. Submitting via AJAX...');

            setSubmitLoading(true);

            var formData = new FormData(document.getElementById('lmrEntryForm'));

            var response = await fetch('?controller=lmr&action=store', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            console.log('HTTP ' + response.status + ' ' + response.statusText);

            var data;
            try {
                data = await response.json();
            } catch (parseErr) {
                console.error('Failed to parse server response as JSON:', parseErr);
                var rawText = await response.text().catch(function () { return '(could not read body)'; });
                console.error('Raw response body:', rawText);
                throw new Error('Server returned an invalid response (HTTP ' + response.status + '). Check the console for details.');
            }

            console.log('Server response:', data);

            if (!response.ok) {
                var serverMsg = (data && data.error) ? data.error : 'Server error (HTTP ' + response.status + ' ' + response.statusText + ').';
                console.error('Backend error — HTTP ' + response.status + ':', data);
                throw new Error(serverMsg);
            }

            if (data.success) {
                console.log('LMR saved successfully. Redirecting to:', data.redirect);
                window.location.href = data.redirect || '?controller=lmr&action=index';
            } else {
                setSubmitLoading(false);
                var errMsg = data.error || 'An unknown error occurred. Please try again.';
                console.error('Save failed:', errMsg);
                showFormValidation(errMsg);
            }
        } catch (err) {
            setSubmitLoading(false);
            console.error('Client JS Error:', err);
            alert('Script error: ' + err.message);
        }
    });

    // Instant-clear banners as soon as the user starts typing/changing values.
    var entryFormEl = document.getElementById('lmrEntryForm');
    if (entryFormEl) {
        entryFormEl.addEventListener('input', hideValidationAlertNow);
        entryFormEl.addEventListener('change', hideValidationAlertNow);
    }

    // Edit mode: populateCustomerName() filters BOM options by the selected
    // customer and re-selects the saved formulation; then populate item fields
    // and re-fetch the breakdown.
    populateCustomerName();
    if (bomCodeSelect.value) {
        var initOpt = bomCodeSelect.options[bomCodeSelect.selectedIndex];
        headerItemCode.value = initOpt ? (initOpt.getAttribute('data-item-code') || '') : '';
        headerItemDescription.value = initOpt ? (initOpt.getAttribute('data-item-description') || '') : '';
        refreshHeaderBomBreakdown();
    }
});
</script>
