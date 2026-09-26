<style>
    .mo-card {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
    }

    .mo-section-title {
        font-size: 0.78rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
    }

    .mo-toolbar {
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

    #moEntryForm .is-invalid,
    #moEntryForm select.is-invalid,
    #moEntryForm input.is-invalid {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
    }

    #formToast {
        position: fixed;
        top: 1.25rem;
        right: 1.25rem;
        z-index: 9999;
        min-width: 360px;
        max-width: 480px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        transition: opacity 0.3s, transform 0.3s;
        opacity: 0;
        transform: translateX(20px);
        pointer-events: none;
    }

    #formToast.show {
        opacity: 1;
        transform: translateX(0);
        pointer-events: auto;
    }
</style>

<div class="mo-toolbar sticky-toolbar pb-2">
    <div>
        <h5 class="mb-1"><i class="bi bi-clipboard-check me-2 text-primary"></i>Manufacturing Order Entry</h5>
        <small class="text-muted">PPIC work order entry and material line tracking</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="?controller=mo&action=index" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to MO List
        </a>
        <button type="button" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-text me-1"></i>Draft
        </button>
        <button type="button" id="btn-save-mo" class="btn btn-primary btn-sm">
            <i class="bi bi-check2-circle me-1"></i>Save MO
        </button>
    </div>
</div>

<div id="formValidationAlert" class="alert alert-danger d-none" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <span id="formValidationMessage"></span>
</div>

<div id="formToast" class="alert alert-danger d-flex align-items-start" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2 mt-1 flex-shrink-0"></i>
    <div id="formToastBody" class="flex-grow-1"></div>
    <button type="button" class="btn-close ms-2 mt-1" onclick="document.getElementById('formToast').classList.remove('show');"></button>
</div>

<form id="moEntryForm" class="mo-card p-4" method="POST" action="<?= !empty($mo) ? '?controller=mo&action=update' : '?controller=mo&action=store' ?>" novalidate>
    <?php if (!empty($mo)): ?>
        <input type="hidden" name="mo_id" value="<?= (int) $mo['mo_id'] ?>">
    <?php endif; ?>
    <input type="hidden" name="line_items_json" id="lineItemsJson">
    <input type="hidden" name="customer_id" id="customerIdHidden">
    <input type="hidden" name="customer_name" id="customerNameHidden">
    <input type="hidden" name="customer_code" id="customerCodeHidden">
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="mo-section-title mb-3">Header Meta & Schedule</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">MO Number <span class="text-danger">*</span></label>
                    <input type="text" name="mo_number" class="form-control" placeholder="Enter MO number" value="<?= htmlspecialchars($mo['mo_number'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">MO Type</label>
                    <select name="mo_type" class="form-select filter-select">
                        <option value="Standard" <?= ($mo['mo_type'] ?? 'Standard') === 'Standard' ? 'selected' : '' ?>>Standard</option>
                        <option value="Rework" <?= ($mo['mo_type'] ?? '') === 'Rework' ? 'selected' : '' ?>>Rework</option>
                        <option value="Trial" <?= ($mo['mo_type'] ?? '') === 'Trial' ? 'selected' : '' ?>>Trial</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">MO Site</label>
                    <select name="mo_site" class="form-select filter-select">
                        <option value="001 - Sterling Technopark" selected>001 - Sterling Technopark</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Order Date</label>
                    <input type="date" name="order_date" class="form-control" value="<?= htmlspecialchars($mo['order_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Due Date</label>
                    <input type="date" name="due_date" class="form-control" value="<?= htmlspecialchars($mo['due_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Planned Start Date</label>
                    <input type="date" name="planned_start_date" class="form-control" value="<?= htmlspecialchars($mo['planned_start_date'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Priority</label>
                    <input type="number" name="priority" class="form-control" value="<?= (int) ($mo['priority'] ?? 3) ?>" min="1" max="9">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Reference No</label>
                    <input type="text" name="reference_no" class="form-control" placeholder="Optional" value="<?= htmlspecialchars($mo['reference_no'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="mo-section-title mb-3">Customer & Order Tracking</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">MO Status</label>
                    <select name="mo_status" class="form-select filter-select">
                        <option value="Planned" <?= ($mo['mo_status'] ?? 'Planned') === 'Planned' ? 'selected' : '' ?>>Planned</option>
                        <option value="Released" <?= ($mo['mo_status'] ?? '') === 'Released' ? 'selected' : '' ?>>Released</option>
                        <option value="Completed" <?= ($mo['mo_status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Cancelled" <?= ($mo['mo_status'] ?? '') === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Customer Code</label>
                    <select name="customer_code" id="customerCode" class="form-select filter-select">
                        <option value="">Select customer</option>
                        <?php foreach ($customers ?? [] as $customer): ?>
                            <option value="<?= htmlspecialchars($customer['customer_id']) ?>" data-name="<?= htmlspecialchars($customer['customer_name'] ?? '') ?>" data-code="<?= htmlspecialchars($customer['customer_code'] ?? '') ?>" <?= (int) ($mo['customer_id'] ?? 0) === (int) $customer['customer_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($customer['customer_code'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Customer Name</label>
                    <input type="text" id="customerName" class="form-control" readonly placeholder="Auto-fills after selecting customer" value="<?= htmlspecialchars($mo['customer_name'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Batch / Lot No</label>
                    <input type="text" name="batch_lot_no" class="form-control" placeholder="e.g. FB013-0826143" value="<?= htmlspecialchars($mo['batch_lot_no'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">PO Number <span class="text-danger">*</span></label>
                    <select name="po_number" id="poNumber" class="form-select filter-select" data-selected-po="<?= htmlspecialchars($mo['po_number'] ?? '') ?>">
                        <option value="">Select open PO</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="mo-section-title mb-0">Item / Material Lines</div>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#moItemModal">
            <i class="bi bi-plus-circle me-1"></i>Add Line Item
        </button>
    </div>

    <div class="table-responsive border rounded-3">
        <table class="table-soft mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Item Number</th>
                    <th>Description</th>
                    <th>UOM</th>
                    <th>Item Type</th>
                    <th>Site</th>
                    <th>Qty Ordered</th>
                    <th>SO Number</th>
                    <th>BOM Code</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="moLinesTableBody">
                <tr class="empty-state-row">
                    <td colspan="10" class="text-center text-muted py-3">
                        No line items added yet. Use "Add Line Item" to begin the MO.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</form>

<div class="modal fade" id="moItemModal" tabindex="-1" aria-labelledby="moItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title" id="moItemModalLabel"><i class="bi bi-plus-circle me-2"></i>Add MO Product Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Type</label>
                        <select id="lineType" class="form-select filter-select">
                            <option value="FG" selected>FG</option>
                            <option value="SFG">SFG - Semi-Finished Goods (Bulk)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Item Number</label>
                        <select id="lineItemSelect" class="form-select filter-select">
                            <option value="">Select item</option>
                            <?php foreach (($allItems ?? $items ?? []) as $item): ?>
                                <option value="<?= htmlspecialchars($item['item_id']) ?>" data-code="<?= htmlspecialchars($item['item_code'] ?? '') ?>" data-description="<?= htmlspecialchars($item['item_description'] ?? '') ?>" data-uom="<?= htmlspecialchars($item['item_uom'] ?? '') ?>" data-type="<?= htmlspecialchars($item['item_type'] ?? '') ?>">
                                    <?= htmlspecialchars($item['item_code'] ?? '') ?> - <?= htmlspecialchars($item['item_description'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" id="lineDescription" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">UOM</label>
                        <input type="text" id="lineUom" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Item Type</label>
                        <input type="text" id="lineItemType" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Site</label>
                        <input type="text" id="lineSite" class="form-control" value="001 - Sterling Technopark" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Qty Ordered</label>
                        <input type="number" id="lineQtyOrdered" class="form-control" min="1" placeholder="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">SO Number</label>
                        <input type="text" id="lineSoNumber" class="form-control" placeholder="Optional">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">BOM Code</label>
                        <input type="text" id="lineBomCode" class="form-control" readonly placeholder="Auto-populated from active formulation">
                    </div>

                    <div class="col-md-12" id="bomStatusAlert" style="display:none;"></div>

                    <div class="col-md-12" id="bomComponentPreview" style="display:none;">
                        <label class="form-label fw-semibold">Component Breakdown</label>
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

                    <div class="col-md-12" id="bomShortageWarning" style="display:none;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveLineItemBtn">Save Line</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const customerSelect = document.getElementById('customerCode');
    const customerNameInput = document.getElementById('customerName');
    const customerIdHidden = document.getElementById('customerIdHidden');
    const customerNameHidden = document.getElementById('customerNameHidden');
    const customerCodeHidden = document.getElementById('customerCodeHidden');
    const poSelect = document.getElementById('poNumber');
    const lineItemsJson = document.getElementById('lineItemsJson');
    const itemSelect = document.getElementById('lineItemSelect');
    const lineDescription = document.getElementById('lineDescription');
    const lineUom = document.getElementById('lineUom');
    const lineItemType = document.getElementById('lineItemType');
    const lineBomCode = document.getElementById('lineBomCode');
    const lineQtyOrdered = document.getElementById('lineQtyOrdered');
    const lineType = document.getElementById('lineType');
    const linesTable = document.getElementById('moLinesTableBody');
    const saveLineBtn = document.getElementById('saveLineItemBtn');
    const bomStatusAlert = document.getElementById('bomStatusAlert');
    const bomComponentPreview = document.getElementById('bomComponentPreview');
    const bomComponentBody = document.getElementById('bomComponentBody');
    const bomBatchInfo = document.getElementById('bomBatchInfo');
    const bomShortageWarning = document.getElementById('bomShortageWarning');

    let currentBomData = null;
    let bomFetchTimeout = null;

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

        if (!customerId) {
            return;
        }

        fetch('?controller=warehouse&action=getCustomerPOs&customer_id=' + encodeURIComponent(customerId))
            .then(function (r) { return r.json(); })
            .then(function (data) {
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

    function setSaveButtonState(enabled) {
        if (enabled) {
            saveLineBtn.disabled = false;
            saveLineBtn.classList.remove('btn-secondary');
            saveLineBtn.classList.add('btn-primary');
        } else {
            saveLineBtn.disabled = true;
            saveLineBtn.classList.remove('btn-primary');
            saveLineBtn.classList.add('btn-secondary');
        }
    }

    function resetBomPreview() {
        currentBomData = null;
        lineBomCode.value = '';
        bomStatusAlert.style.display = 'none';
        bomStatusAlert.innerHTML = '';
        bomComponentPreview.style.display = 'none';
        bomComponentBody.innerHTML = '';
        bomBatchInfo.textContent = '';
        bomShortageWarning.style.display = 'none';
        bomShortageWarning.innerHTML = '';
        setSaveButtonState(true);
    }

    function renderBomPreview(data) {
        currentBomData = data;
        bomStatusAlert.style.display = 'block';
        bomComponentPreview.style.display = 'none';
        bomComponentBody.innerHTML = '';
        bomBatchInfo.textContent = '';
        bomShortageWarning.style.display = 'none';
        bomShortageWarning.innerHTML = '';

        if (data.no_bom) {
            bomStatusAlert.innerHTML = '<div class="alert alert-danger py-2 mb-0"><i class="bi bi-exclamation-triangle me-1"></i><strong>No active BOM found</strong> for this item.</div>';
            lineBomCode.value = '';
            setSaveButtonState(false);
            return;
        }

        if (data.not_found) {
            bomStatusAlert.innerHTML = '<div class="alert alert-danger py-2 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>BOM <strong>' + (data.bom_code || '') + '</strong> not found in system.</div>';
            lineBomCode.value = data.bom_code || '';
            setSaveButtonState(false);
            return;
        }

        lineBomCode.value = data.bom_code || '';

        if (!data.components || data.components.length === 0) {
            bomStatusAlert.innerHTML = '<div class="alert alert-success py-2 mb-0"><i class="bi bi-check-circle me-1"></i>BOM <strong>' + data.bom_code + '</strong> found. No components to check.</div>';
            setSaveButtonState(true);
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

        if (data.has_shortage) {
            bomStatusAlert.innerHTML = '<div class="alert alert-warning py-2 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>BOM <strong>' + data.bom_code + '</strong> found — stock shortages detected below.</div>';
            bomShortageWarning.style.display = 'block';
            var shortageItems = data.components.filter(function (c) { return c.status === 'lacking'; });
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
                ? '<span class="badge bg-success"><i class="bi bi-check-lg"></i> Sufficient</span>'
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

        setSaveButtonState(true);
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
            fetch('?controller=mo&action=getBomByItem&item_id=' + encodeURIComponent(itemId) + '&qty=' + encodeURIComponent(qty))
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

    function updateLineDetails() {
        const selectedOption = itemSelect.selectedOptions[0];
        if (!selectedOption || !selectedOption.value) {
            lineDescription.value = '';
            lineUom.value = '';
            lineItemType.value = '';
            resetBomPreview();
            return;
        }

        const itemType = selectedOption.dataset.type || lineType.value || 'FG';
        lineDescription.value = selectedOption.dataset.description || '';
        lineUom.value = selectedOption.dataset.uom || '';
        lineItemType.value = itemType;

        fetchBomBreakdown(selectedOption.value, lineQtyOrdered.value || 0);
    }

    function filterItemOptionsByType() {
        var selectedType = lineType.value || 'FG';
        var selectedWasHidden = false;

        Array.from(itemSelect.options).forEach(function (opt) {
            if (!opt.value) {
                return;
            }
            var optType = (opt.dataset.type || '').trim();
            var visible = !optType || optType === selectedType;
            if (!visible && opt.selected) {
                selectedWasHidden = true;
            }
            opt.hidden = !visible;
            opt.disabled = !visible;
        });

        if (selectedWasHidden) {
            itemSelect.value = '';
            lineDescription.value = '';
            lineUom.value = '';
            lineItemType.value = '';
            resetBomPreview();
        }
    }

    function syncLineItemsPayload() {
        const rows = Array.from(linesTable.querySelectorAll('tr')).filter(function (row) {
            return !row.classList.contains('empty-state-row');
        });

        const payload = rows.map(function (row) {
            const cells = row.querySelectorAll('td');
            return {
                type: cells[0]?.textContent?.trim() || 'FG',
                item_code: cells[1]?.textContent?.trim() || '',
                item_description: cells[2]?.textContent?.trim() || '',
                uom: cells[3]?.textContent?.trim() || '',
                item_type: cells[4]?.textContent?.trim() || '',
                site: cells[5]?.textContent?.trim() || '001 - Sterling Technopark',
                qty_ordered: cells[6]?.textContent?.trim() || 0,
                so_number: cells[7]?.textContent?.trim() === '-' ? '' : cells[7]?.textContent?.trim() || '',
                bom_code: cells[8]?.textContent?.trim() === '—' ? '' : cells[8]?.textContent?.trim() || '',
                item_id: row.dataset.itemId || '',
            };
        });

        lineItemsJson.value = JSON.stringify(payload);
    }

    function addLineItem() {
        const selectedItem = itemSelect.selectedOptions[0];
        const qty = parseFloat(lineQtyOrdered.value || '0');

        if (!selectedItem || !selectedItem.value) {
            alert('Please select an item before saving the line.');
            return;
        }

        if (!qty || qty <= 0) {
            alert('Qty Ordered must be greater than zero.');
            return;
        }

        if (currentBomData && currentBomData.no_bom) {
            alert('Cannot add item to MO without an active BOM formulation.');
            return;
        }

        var existingEmptyRow = linesTable.querySelector('.empty-state-row');
        if (existingEmptyRow) {
            existingEmptyRow.remove();
        }

        const row = document.createElement('tr');
        row.dataset.itemId = selectedItem.value;
        row.innerHTML = `
            <td><span class="badge bg-light text-dark border">${lineType.value || 'FG'}</span></td>
            <td><strong>${selectedItem.dataset.code || ''}</strong></td>
            <td>${lineDescription.value || ''}</td>
            <td>${lineUom.value || ''}</td>
            <td>${lineItemType.value || 'FG'}</td>
            <td>001 - Sterling Technopark</td>
            <td>${qty}</td>
            <td>${(document.getElementById('lineSoNumber').value || '-')}</td>
            <td>${lineBomCode.value || '—'}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-line-item">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        linesTable.appendChild(row);
        syncLineItemsPayload();

        document.getElementById('lineSoNumber').value = '';
        lineQtyOrdered.value = '';
        itemSelect.value = '';
        lineDescription.value = '';
        lineUom.value = '';
        lineItemType.value = '';
        lineType.value = 'FG';
        resetBomPreview();

        var modal = bootstrap.Modal.getInstance(document.getElementById('moItemModal'));
        if (modal) {
            modal.hide();
        }
    }

    customerSelect.addEventListener('change', populateCustomerName);
    itemSelect.addEventListener('change', updateLineDetails);
    lineType.addEventListener('change', function () {
        filterItemOptionsByType();
        if (itemSelect.value) {
            updateLineDetails();
        }
    });

    lineQtyOrdered.addEventListener('input', function () {
        if (itemSelect.value) {
            fetchBomBreakdown(itemSelect.value, lineQtyOrdered.value || 0);
        }
    });

    document.getElementById('moItemModal').addEventListener('show.bs.modal', function () {
        filterItemOptionsByType();
        if (itemSelect.value) {
            fetchBomBreakdown(itemSelect.value, lineQtyOrdered.value || 0);
        }
    });

    document.getElementById('moItemModal').addEventListener('hidden.bs.modal', function () {
        resetBomPreview();
        itemSelect.value = '';
        lineDescription.value = '';
        lineUom.value = '';
        lineItemType.value = '';
        lineType.value = 'FG';
        lineQtyOrdered.value = '';
        document.getElementById('lineSoNumber').value = '';
    });

    saveLineBtn.addEventListener('click', addLineItem);

    linesTable.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.remove-line-item');
        if (!removeButton) {
            return;
        }
        const row = removeButton.closest('tr');
        if (row) {
            row.remove();
            syncLineItemsPayload();
            if (!linesTable.querySelector('tr') && !document.querySelector('#moLinesTableBody .empty-state-row')) {
                linesTable.innerHTML = `
                    <tr class="empty-state-row">
                        <td colspan="10" class="text-center text-muted py-3">
                            No line items added yet. Use "Add Line Item" to begin the MO.
                        </td>
                    </tr>
                `;
                lineItemsJson.value = '[]';
            }
        }
    });

    function showToast(message, type) {
        type = type || 'danger';
        var toast = document.getElementById('formToast');
        var body = document.getElementById('formToastBody');
        toast.className = 'alert alert-' + type + ' d-flex align-items-start';
        body.innerHTML = message;
        toast.classList.add('show');
        clearTimeout(showToast._timer);
        showToast._timer = setTimeout(function () {
            toast.classList.remove('show');
        }, 8000);
    }

    function showFormValidation(message) {
        var alertEl = document.getElementById('formValidationAlert');
        var msgEl = document.getElementById('formValidationMessage');
        msgEl.innerHTML = message;
        alertEl.classList.remove('d-none');
        alertEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        showToast(message, 'danger');
    }

    function clearFormValidation() {
        document.getElementById('formValidationAlert').classList.add('d-none');
        document.getElementById('formValidationMessage').innerHTML = '';
        document.getElementById('formToast').classList.remove('show');
        document.querySelectorAll('#moEntryForm .is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
    }

    function setSubmitLoading(loading) {
        var btn = document.getElementById('btn-save-mo');
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

    document.getElementById('btn-save-mo').addEventListener('click', async function () {
        console.log('Save MO button clicked!');
        try {
            clearFormValidation();

            var errors = [];
            var moNumberInput = document.querySelector('input[name="mo_number"]');
            var customerSelectEl = document.getElementById('customerCode');
            var firstInvalid = null;

            if (!moNumberInput.value.trim()) {
                errors.push('MO Number is required.');
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

            var dataRows = Array.from(linesTable.querySelectorAll('tr')).filter(function (row) {
                return !row.classList.contains('empty-state-row');
            });

            if (dataRows.length === 0) {
                errors.push('Please add at least one line item.');
            }

            if (errors.length > 0) {
                showFormValidation('<strong>Please fix the following:</strong><ul><li>' + errors.join('</li><li>') + '</li></ul>');
                if (firstInvalid) firstInvalid.focus();
                console.warn('Form validation failed:', errors);
                return;
            }

            console.log('Form validation passed. Submitting via AJAX...');
            syncLineItemsPayload();

            var payload = JSON.parse(lineItemsJson.value || '[]');
            console.log('Submitting MO payload:', payload);

            setSubmitLoading(true);

            var formData = new FormData(document.getElementById('moEntryForm'));

            var response = await fetch('?controller=mo&action=store', {
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
                console.log('MO saved successfully. Redirecting to:', data.redirect);
                window.location.href = data.redirect || '?controller=mo&action=index';
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

    lineItemsJson.value = '[]';
    filterItemOptionsByType();
    populateCustomerName();
    syncLineItemsPayload();

    var existingItems = <?= json_encode($items ?? []) ?>;
    if (existingItems.length > 0) {
        var existingEmptyRow = linesTable.querySelector('.empty-state-row');
        if (existingEmptyRow) existingEmptyRow.remove();

        existingItems.forEach(function (item) {
            var row = document.createElement('tr');
            row.dataset.itemId = item.item_id || '';
            row.innerHTML =
                '<td><span class="badge bg-light text-dark border">' + (item.item_type || 'FG') + '</span></td>' +
                '<td><strong>' + (item.item_code || '') + '</strong></td>' +
                '<td>' + (item.item_description || '') + '</td>' +
                '<td>' + (item.uom || '') + '</td>' +
                '<td>' + (item.item_type || 'FG') + '</td>' +
                '<td>' + (item.site || '001 - Sterling Technopark') + '</td>' +
                '<td>' + (item.qty_ordered || 0) + '</td>' +
                '<td>' + (item.so_number || '-') + '</td>' +
                '<td>' + (item.bom_code || '—') + '</td>' +
                '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-line-item"><i class="bi bi-trash"></i></button></td>';
            linesTable.appendChild(row);
        });
        syncLineItemsPayload();
    }
});
</script>
