<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDeliveryModal">
            <i class="bi bi-plus-circle me-1"></i> Create Delivery Receipt
        </button>
        <button type="button" class="btn btn-outline-primary ms-2" id="printDRBtn">
            <i class="bi bi-printer me-1"></i> Print DR
        </button>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <select id="filterCustomer" class="form-select form-select-sm filter-select" style="width:200px">
            <option value="">All Customers</option>
        </select>
        <select id="filterItem" class="form-select form-select-sm filter-select" style="width:200px">
            <option value="">All Items</option>
        </select>
        <select id="filterDR" class="form-select form-select-sm filter-select" style="width:160px">
            <option value="">All DR Numbers</option>
        </select>
        <input type="date" id="filterDate" class="form-control form-control-sm" style="width:160px" title="Filter by Delivery Date">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearFilters"><i class="bi bi-x-circle me-1"></i>Clear</button>
    </div>
    <div class="search-box" style="width: 300px;">
        <i class="bi bi-search"></i>
        <input type="text" id="searchDelivery" class="form-control" placeholder="Search PO number...">
    </div>
</div>

<div class="mb-2">
    <small><i class="bi bi-info-circle me-1"></i><strong>Remarks:</strong> <span style="color:black;">Black</span> = Normal, <span style="color:red;font-weight:bold;">Red</span> = Reported, <span style="color:#e6a800;font-weight:bold;">Yellow</span> = Edited by Admin</small>
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Customer</th>
                    <th>Items / Lots</th>
                    <th>DR Number</th>
                    <th>Total Delivered</th>
                    <th>Cases</th>
                    <th>Type</th>
                    <th>Delivery Date</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="deliveryTableBody">
                <?php foreach ($deliveries as $d): ?>
                <?php
                    $lotItems = json_decode($d['lot_items'] ?? '[]', true);
                    $hasLotItems = is_array($lotItems) && count($lotItems) > 0;
                    $itemSummary = '';
                    $casesSummary = '';
                    if ($hasLotItems) {
                        $grouped = [];
                        foreach ($lotItems as $li) {
                            $key = $li['item_description'] ?? $li['item_code'] ?? 'Unknown';
                            if (!isset($grouped[$key])) $grouped[$key] = ['qty' => 0, 'lots' => [], 'conv' => null, 'uom' => ''];
                            $grouped[$key]['qty'] += $li['qty'] ?? 0;
                            $grouped[$key]['lots'][] = $li['lot_number'] ?? '?';
                            if (!empty($li['uom_conversion'])) $grouped[$key]['conv'] = $li['uom_conversion'];
                            if (!empty($li['item_uom'])) $grouped[$key]['uom'] = $li['item_uom'];
                        }
                        $parts = [];
                        $caseParts = [];
                        foreach ($grouped as $desc => $info) {
                            $parts[] = htmlspecialchars($desc) . ' (' . $info['qty'] . ' - ' . implode(', ', $info['lots']) . ')';
                            $conv = $info['conv'];
                            $uom = $info['uom'];
                            if ($conv && $uom !== 'CS') {
                                $caseParts[] = htmlspecialchars($desc) . ': ' . round($info['qty'] / $conv, 2) . ' CS';
                            }
                        }
                        $itemSummary = implode('<br>', $parts);
                        $casesSummary = implode('<br>', $caseParts);
                    } else {
                        $itemSummary = htmlspecialchars(($d['item_code'] ?? '-') . ' - ' . ($d['item_description'] ?? ''));
                        if (!empty($d['lot_number'])) $itemSummary .= '<br><small>' . htmlspecialchars($d['lot_number']) . '</small>';
                        $conv = $d['uom_conversion'] ?? null;
                        $itemUom = $d['item_uom'] ?? '';
                        if ($conv && $itemUom !== 'CS') {
                            $casesSummary = round(($d['delivery_quantity'] ?? 0) / $conv, 2) . ' CS';
                        }
                    }
                ?>
                <?php $isActive = ($d['active_status'] ?? 1) == 1; ?>
                <tr class="<?= $isActive ? '' : 'text-decoration-line-through opacity-50' ?>">
                    <td><strong class="text-primary"><?= $d['customer_po_number'] ?></strong></td>
                    <td><?= htmlspecialchars($d['customer_name'] ?? '-') ?></td>
                    <td><small><?= $itemSummary ?></small></td>
                    <td><?= htmlspecialchars($d['dr_number'] ?? '') ?: '<span class="text-muted">-</span>' ?></td>
                    <td><?= $d['delivery_quantity'] ?? 0 ?></td>
                    <td>
                        <?php if (!empty($casesSummary)): ?>
                            <small><?= $casesSummary ?></small>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (($d['production_type'] ?? 'normal') === 'advance'): ?>
                            <span class="badge bg-info">Advance</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Normal</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('Y-m-d', strtotime($d['delivery_date'])) ?></td>
                    <td>
                        <?php if (!empty($d['remarks'])): ?>
                            <?php
                            $rmType = $d['remarks_type'] ?? '';
                            if ($rmType === 'report') $rmStyle = 'color:red;font-weight:bold;';
                            elseif ($rmType === 'edited') $rmStyle = 'color:#e6a800;font-weight:bold;';
                            else $rmStyle = '';
                            ?>
                            <span style="<?= $rmStyle ?>"><?= htmlspecialchars($d['remarks']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $disAttr = $isActive ? '' : 'disabled'; ?>
                        <?php $disClass = $isActive ? '' : 'disabled'; ?>
                        <?php if ($hasLotItems): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary viewDeliveryBtn <?= $disClass ?>" <?= $disAttr ?>
                            data-bs-toggle="modal" data-bs-target="#viewDeliveryModal"
                            data-dr="<?= htmlspecialchars($d['dr_number']) ?>"
                            data-po="<?= htmlspecialchars($d['customer_po_number']) ?>"
                            data-customer="<?= htmlspecialchars($d['customer_name'] ?? '') ?>"
                            data-date="<?= date('Y-m-d', strtotime($d['delivery_date'])) ?>"
                            data-remarks="<?= htmlspecialchars($d['remarks'] ?? '') ?>"
                            data-remarks-type="<?= htmlspecialchars($d['remarks_type'] ?? '') ?>"
                            data-lot-items="<?= htmlspecialchars($d['lot_items'] ?? '[]') ?>"
                            data-delivered-by="<?= htmlspecialchars($d['delivered_by_name'] ?? '') ?>"
                            data-receipt-id="<?= $receipts_map[$d['delivery_id']]['receipt_id'] ?? '' ?>"
                            data-receipt-path="<?= htmlspecialchars($receipts_map[$d['delivery_id']]['file_path'] ?? '') ?>">
                            <i class="bi bi-eye"></i> View
                        </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-outline-danger reportDeliveryBtn <?= $disClass ?>" <?= $disAttr ?>
                            data-delivery-id="<?= $d['delivery_id'] ?>"
                            data-dr="<?= htmlspecialchars($d['dr_number'] ?? '') ?>">
                            <i class="bi bi-flag"></i> Report
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success attachDRBtn <?= $disClass ?>" <?= $disAttr ?>
                            data-delivery-id="<?= $d['delivery_id'] ?>"
                            data-po-id="<?= $d['po_id'] ?>"
                            data-dr="<?= htmlspecialchars($d['dr_number'] ?? '') ?>">
                            <i class="bi bi-paperclip"></i> Attach
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($deliveries)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No deliveries found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination justify-content-center mt-4">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?controller=warehouse&action=deliveries&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- DR Number Input Modal -->
<div class="modal fade" id="drInputModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Enter DR Number</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="drNumberInput" class="form-control" placeholder="Enter DR Number" autofocus>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="drInputOkBtn">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- DR Number Confirm Modal -->
<div class="modal fade" id="drConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-question-circle me-2"></i>Confirm DR Number</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure for this DR number?</p>
                <p class="fw-bold text-primary mb-0" id="drConfirmNumber">-</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="drConfirmEditBtn"><i class="bi bi-pencil me-1"></i>Edit</button>
                <button type="button" class="btn btn-primary" id="drConfirmYesBtn"><i class="bi bi-check-lg me-1"></i>Yes</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createDeliveryModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-truck me-2"></i>Record Delivery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?controller=warehouse&action=createMultipleDelivery">
                <div class="modal-body">
                    <!-- DR Number (required) -->
                    <div class="mb-3">
                        <label class="form-label">Delivery Receipt (DR) Number *</label>
                        <input type="text" name="dr_number" id="modalDrNumber" class="form-control" placeholder="Enter DR number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purchase Order</label>
                        <select name="po_id" id="poSelect" class="form-select" required>
                            <option value="">Select PO</option>
                            <?php foreach ($purchase_orders as $po): ?>
                                <option value="<?= $po['po_id'] ?>" data-type="<?= $po['production_type'] ?? 'normal' ?>">
                                    <?= $po['customer_po_number'] ?> - <?= $po['customer_name'] ?>
                                    [<?= ($po['production_type'] ?? 'normal') === 'advance' ? 'Advance' : 'Normal' ?>]
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="itemRow" style="display: none;">
                        <label class="form-label">Item *</label>
                        <select name="poi_id" id="poiSelect" class="form-select">
                            <option value="">Select Item</option>
                        </select>
                        <input type="hidden" id="itemQty" value="0">
                        <input type="hidden" id="itemProduced" value="0">
                        <input type="hidden" id="itemDelivered" value="0">
                    </div>
                    <div class="mb-3" id="lotRow" style="display: none;">
                        <label class="form-label">Select Lots *</label>
                        <!-- Container for lot checkboxes (multiple selection) -->
                        <div id="lotCheckboxContainer" class="form-check">
                            <!-- checkboxes will be injected via JS -->
                        </div>
                        <!-- hidden field to collect selected lot IDs on submit -->
                        <input type="hidden" name="lot_ids" id="selectedLotIds">
                    </div>
                    <div class="mb-3" id="itemQtyRow" style="display: none;">
                        <label class="form-label">PO Quantity</label>
                        <input type="text" id="itemQtyDisplay" class="form-control" readonly>
                    </div>
                    <div class="mb-3" id="availableRow" style="display: none;">
                        <label class="form-label">Available for Delivery</label>
                        <input type="text" id="availableQty" class="form-control" readonly>
                    </div>
                    <!-- Delivery quantity is now derived from selected lots, so we hide the manual field -->
                    <div class="mb-3" style="display: none;">
                        <label class="form-label">Delivery Quantity *</label>
                        <input type="number" name="delivery_quantity" id="deliveryQty" class="form-control" min="1">
                        <div class="invalid-feedback" id="deliveryError"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Delivery Date</label>
                        <input type="date" name="delivery_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Save Delivery</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Delivery Modal -->
<div class="modal fade" id="viewDeliveryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Delivery Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>DR Number:</strong> <span id="viewDrNumber">-</span>
                    </div>
                    <div class="col-md-6">
                        <strong>PO Number:</strong> <span id="viewPoNumber">-</span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Customer:</strong> <span id="viewCustomer">-</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Delivery Date:</strong> <span id="viewDate">-</span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Remarks:</strong> <span id="viewRemarks">-</span>
                    </div>
                </div>
                <hr>
                <h6 class="mb-3">Lot Items</h6>
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Item Code</th>
                            <th>Item Description</th>
                            <th>Lot Number</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Cases</th>
                        </tr>
                    </thead>
                    <tbody id="viewLotItemsBody">
                    </tbody>
                    <tfoot id="viewLotItemsFoot">
                        <tr class="table-light fw-bold">
                            <td colspan="3" class="text-end">Total:</td>
                            <td class="text-end" id="viewTotalQty">0</td>
                            <td class="text-end" id="viewTotalCases">0 CS</td>
                        </tr>
                    </tfoot>
                </table>
                <hr>
                <h6 class="mb-2"><i class="bi bi-camera me-1"></i>DR Photo</h6>
                <div id="viewDRPhotoSection">
                    <div id="viewDRPhotoContainer"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Report Delivery Modal -->
<div class="modal fade" id="reportDeliveryModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-flag me-2"></i>Report Delivery Issue</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="reportDeliveryForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">DR Number</label>
                        <input type="text" id="reportDeliveryDr" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Concern / Issue <span class="text-danger">*</span></label>
                        <textarea id="reportDeliveryRemarks" class="form-control" rows="4" placeholder="Describe the issue (e.g. cancel delivery, mistyped DR number, etc.)" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-send me-1"></i>Submit Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Attach DR Photo Modal -->
<div class="modal fade" id="attachDRModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-camera me-2"></i>Attach DR Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Upload a photo of the physical Delivery Receipt as proof it was printed.</p>
                <div class="mb-3">
                    <label class="form-label">DR Number</label>
                    <input type="text" id="attachDRNumber" class="form-control" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Actual DR Photo <span class="text-danger">*</span></label>
                    <div id="dropZone" class="border border-secondary border-dashed rounded p-4 text-center" style="cursor:pointer; min-height: 120px; display:flex; align-items:center; justify-content:center; flex-direction:column;">
                        <i class="bi bi-cloud-arrow-up fs-1 text-muted"></i>
                        <p class="mb-1 text-muted">Drag & drop photo here or <strong>click to browse</strong></p>
                        <small class="text-muted">JPG, PNG, GIF, WebP only (max 10MB)</small>
                        <input type="file" id="drPhotoInput" accept="image/jpeg,image/png,image/gif,image/webp" class="d-none">
                    </div>
                    <div id="photoPreview" class="mt-3" style="display:none;">
                        <img id="previewImg" src="" alt="Preview" class="img-fluid rounded" style="max-height:200px;">
                        <p id="previewName" class="text-muted mt-1 mb-0"></p>
                    </div>
                </div>
                <input type="hidden" id="attachDeliveryId">
                <input type="hidden" id="attachPoId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="submitDRPhotoBtn"><i class="bi bi-upload me-1"></i>Upload</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('searchDelivery').addEventListener('keyup', function() {
    applyDeliveryFilters();
});

function populateDeliveryFilters() {
    const customers = new Set();
    const items = new Set();
    const drNumbers = new Set();
    document.querySelectorAll('#deliveryTableBody tr').forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        const cust = row.cells[1] ? row.cells[1].textContent.trim() : '';
        if (cust) customers.add(cust);
        const itemCell = row.cells[2];
        if (itemCell) {
            itemCell.querySelectorAll('small').forEach(s => {
                const t = s.textContent.trim().split('(')[0].trim();
                if (t && t !== '-') items.add(t);
            });
        }
        const dr = row.cells[3] ? row.cells[3].textContent.trim() : '';
        if (dr && dr !== '-') drNumbers.add(dr);
    });
    const custSel = document.getElementById('filterCustomer');
    customers.forEach(c => { const o = document.createElement('option'); o.value = c; o.textContent = c; custSel.appendChild(o); });
    const itemSel = document.getElementById('filterItem');
    items.forEach(i => { const o = document.createElement('option'); o.value = i; o.textContent = i; itemSel.appendChild(o); });
    const drSel = document.getElementById('filterDR');
    drNumbers.forEach(d => { const o = document.createElement('option'); o.value = d; o.textContent = d; drSel.appendChild(o); });
}

function applyDeliveryFilters() {
    const custFilter = document.getElementById('filterCustomer').value.toLowerCase();
    const itemFilter = document.getElementById('filterItem').value.toLowerCase();
    const drFilter = document.getElementById('filterDR').value.toLowerCase();
    const dateFilter = document.getElementById('filterDate').value;
    const searchQuery = document.getElementById('searchDelivery').value.toLowerCase();
    document.querySelectorAll('#deliveryTableBody tr').forEach(row => {
        if (row.querySelector('td[colspan]')) { row.style.display = ''; return; }
        const cust = row.cells[1] ? row.cells[1].textContent.trim().toLowerCase() : '';
        const itemText = row.cells[2] ? row.cells[2].textContent.trim().toLowerCase() : '';
        const drText = row.cells[3] ? row.cells[3].textContent.trim().toLowerCase() : '';
        const deliveryDate = row.cells[7] ? row.cells[7].textContent.trim() : '';
        const rowText = row.textContent.toLowerCase();
        let show = true;
        if (custFilter && !cust.includes(custFilter)) show = false;
        if (itemFilter && !itemText.includes(itemFilter)) show = false;
        if (drFilter && !drText.includes(drFilter)) show = false;
        if (dateFilter && deliveryDate !== dateFilter) show = false;
        if (searchQuery && !rowText.includes(searchQuery)) show = false;
        row.style.display = show ? '' : 'none';
    });
}

document.getElementById('filterCustomer').addEventListener('change', applyDeliveryFilters);
document.getElementById('filterItem').addEventListener('change', applyDeliveryFilters);
document.getElementById('filterDR').addEventListener('change', applyDeliveryFilters);
document.getElementById('filterDate').addEventListener('change', applyDeliveryFilters);
document.getElementById('clearFilters').addEventListener('click', function() {
    document.getElementById('filterCustomer').value = '';
    document.getElementById('filterItem').value = '';
    document.getElementById('filterDR').value = '';
    document.getElementById('filterDate').value = '';
    document.getElementById('searchDelivery').value = '';
    applyDeliveryFilters();
});

document.addEventListener('DOMContentLoaded', populateDeliveryFilters);

let poItemsCache = [];

function renderLotCheckboxes(lots, lotRow, lotContainer, itemName) {
    if (lots && lots.length > 0) {
        var hasNew = false;
        for (var i = 0; i < lots.length; i++) {
            if (document.getElementById('lotChk_' + lots[i].lot_id)) continue;
            if (!hasNew && itemName) {
                var hdr = document.createElement('div');
                hdr.className = 'fw-bold text-primary small mb-1 mt-2';
                hdr.textContent = itemName;
                lotContainer.appendChild(hdr);
            }
            hasNew = true;
            var lot = lots[i];
            const wrapper = document.createElement('div');
            wrapper.className = 'd-flex align-items-center mb-2 p-2 border rounded bg-light';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'form-check-input me-2';
            checkbox.value = lot.lot_id;
            checkbox.id = 'lotChk_' + lot.lot_id;
            const label = document.createElement('label');
            label.className = 'form-check-label me-2 fw-bold';
            label.htmlFor = checkbox.id;
            label.style.whiteSpace = 'nowrap';
            label.textContent = lot.lot_number;
            const availBadge = document.createElement('span');
            availBadge.className = 'badge bg-secondary me-2';
            availBadge.textContent = 'Avail: ' + lot.available_quantity;
            const qtyInput = document.createElement('input');
            qtyInput.type = 'number';
            qtyInput.className = 'form-control form-control-sm';
            qtyInput.style.width = '100px';
            qtyInput.min = '1';
            qtyInput.max = lot.available_quantity;
            qtyInput.placeholder = 'Qty';
            qtyInput.disabled = true;
            qtyInput.dataset.lotId = lot.lot_id;
            qtyInput.dataset.max = lot.available_quantity;
            qtyInput.id = 'lotQty_' + lot.lot_id;
            const hint = document.createElement('small');
            hint.className = 'text-muted ms-1';
            hint.style.whiteSpace = 'nowrap';
            hint.textContent = 'Leave blank for max';
            wrapper.appendChild(checkbox);
            wrapper.appendChild(label);
            wrapper.appendChild(availBadge);
            wrapper.appendChild(qtyInput);
            wrapper.appendChild(hint);
            lotContainer.appendChild(wrapper);
            checkbox.addEventListener('change', function() {
                qtyInput.disabled = !this.checked;
                if (!this.checked) {
                    qtyInput.value = '';
                } else {
                    qtyInput.value = lot.available_quantity;
                    qtyInput.select();
                }
            });
        }
        lotRow.style.display = 'block';
    } else {
        if (lotContainer.children.length === 0) {
            lotRow.style.display = 'none';
        }
    }
}

document.getElementById('poSelect').addEventListener('change', function() {
    const poId = this.value;
    const itemRow = document.getElementById('itemRow');
    const itemQtyRow = document.getElementById('itemQtyRow');
    const availableRow = document.getElementById('availableRow');
    const poiSelect = document.getElementById('poiSelect');
    const lotRow = document.getElementById('lotRow');
    const lotContainer = document.getElementById('lotCheckboxContainer');

    itemRow.style.display = 'none';
    itemQtyRow.style.display = 'none';
    availableRow.style.display = 'none';
    poiSelect.innerHTML = '<option value="">Select Item</option>';
    poiSelect.disabled = false;
    var existingHidden = poiSelect.parentNode.querySelector('input[name="poi_id"][type="hidden"]');
    if (existingHidden) existingHidden.remove();
    document.getElementById('deliveryQty').value = '';
    document.getElementById('deliveryQty').classList.remove('is-invalid');
    document.getElementById('deliveryError').textContent = '';
    lotRow.style.display = 'none';
    lotContainer.innerHTML = '';

    if (!poId) return;

    fetch('?controller=warehouse&action=getPODetails&id=' + poId)
        .then(function(response) { return response.json(); })
        .then(function(data) {
            const items = data.po_items || [];
            poItemsCache = items;

            if (items.length > 1) {
                items.forEach(function(item) {
                    const opt = document.createElement('option');
                    opt.value = item.poi_id;
                    opt.textContent = item.item_description || '-';
                    opt.dataset.qty = item.quantity || 0;
                    opt.dataset.produced = item.produced_quantity || 0;
                    opt.dataset.delivered = item.delivered_quantity || 0;
                    poiSelect.appendChild(opt);
                });
                itemRow.style.display = 'block';
            } else if (items.length === 1) {
                const item = items[0];
                document.getElementById('itemQty').value = item.quantity || 0;
                document.getElementById('itemProduced').value = item.produced_quantity || 0;
                document.getElementById('itemDelivered').value = item.delivered_quantity || 0;

                document.getElementById('itemQtyDisplay').value = item.quantity || 0;
                document.getElementById('availableQty').value = '';

                itemQtyRow.style.display = 'block';
                availableRow.style.display = 'none';

                poiSelect.innerHTML = '<option value="' + item.poi_id + '">' + (item.item_description || '-') + '</option>';
                poiSelect.value = item.poi_id;
                poiSelect.disabled = true;
                var hiddenPoi = document.createElement('input');
                hiddenPoi.type = 'hidden';
                hiddenPoi.name = 'poi_id';
                hiddenPoi.value = item.poi_id;
                poiSelect.parentNode.appendChild(hiddenPoi);
                itemRow.style.display = 'block';

                fetch('?controller=warehouse&action=getAvailableLots&poi_id=' + item.poi_id)
                    .then(function(response) { return response.json(); })
                    .then(function(lots) {
                        renderLotCheckboxes(lots, lotRow, lotContainer, item.item_description || '-');
                    });
            }
        });
});

document.getElementById('poiSelect').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const lotRow = document.getElementById('lotRow');
    const lotContainer = document.getElementById('lotCheckboxContainer');

    if (!this.value) {
        document.getElementById('itemQtyRow').style.display = 'none';
        document.getElementById('availableRow').style.display = 'none';
        document.getElementById('deliveryQty').value = '';
        return;
    }

    const qty = parseInt(selected.dataset.qty) || 0;
    const itemName = selected.textContent.trim();

    document.getElementById('itemQtyDisplay').value = qty;
    document.getElementById('itemQtyRow').style.display = 'block';
    document.getElementById('availableRow').style.display = 'none';
    document.getElementById('availableQty').value = '';

    document.getElementById('deliveryQty').value = '';
    document.getElementById('deliveryQty').classList.remove('is-invalid');
    document.getElementById('deliveryError').textContent = '';

    fetch('?controller=warehouse&action=getAvailableLots&poi_id=' + this.value)
        .then(function(response) { return response.json(); })
        .then(function(lots) {
            renderLotCheckboxes(lots, lotRow, lotContainer, itemName);
        });
});

document.querySelector('#createDeliveryModal form').addEventListener('submit', function(e) {
    const poSelect = document.getElementById('poSelect');
    if (!poSelect.value) {
        e.preventDefault();
        alert('Please select a Purchase Order');
        return;
    }

    const checkedBoxes = document.querySelectorAll('#lotCheckboxContainer input[type="checkbox"]:checked');
    if (checkedBoxes.length === 0) {
        e.preventDefault();
        alert('Please select at least one lot');
        return;
    }
    const lotPairs = [];
    let hasError = false;
    checkedBoxes.forEach(function(cb) {
        const lotId = cb.value;
        const qtyInput = document.getElementById('lotQty_' + lotId);
        let qty = parseInt(qtyInput.value) || 0;
        const max = parseInt(qtyInput.dataset.max) || 0;
        if (qty <= 0) {
            qty = max;
        }
        if (qty > max) {
            hasError = true;
            alert('Quantity ' + qty + ' exceeds available ' + max + ' for lot ' + lotId);
            return;
        }
        lotPairs.push(lotId + ':' + qty);
    });
    if (hasError) {
        e.preventDefault();
        return;
    }
    document.getElementById('selectedLotIds').value = lotPairs.join(',');
});

var drInputModal, drConfirmModal;
var drState = { drNumber: '' };

document.getElementById('printDRBtn').addEventListener('click', function() {
    document.getElementById('drNumberInput').value = '';
    drInputModal = new bootstrap.Modal(document.getElementById('drInputModal'));
    drInputModal.show();
});

document.getElementById('drInputOkBtn').addEventListener('click', function() {
    var value = document.getElementById('drNumberInput').value.trim();
    if (value === '') {
        alert('Please enter a DR number');
        return;
    }
    drState.drNumber = value;
    drInputModal.hide();
    document.getElementById('drConfirmNumber').textContent = value;
    drConfirmModal = new bootstrap.Modal(document.getElementById('drConfirmModal'));
    drConfirmModal.show();
});

document.getElementById('drNumberInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('drInputOkBtn').click();
    }
});

document.getElementById('drConfirmEditBtn').addEventListener('click', function() {
    drConfirmModal.hide();
    document.getElementById('drNumberInput').value = drState.drNumber;
    drInputModal = new bootstrap.Modal(document.getElementById('drInputModal'));
    drInputModal.show();
});

document.getElementById('drConfirmYesBtn').addEventListener('click', function() {
    var drNumber = drState.drNumber;
    drConfirmModal.hide();

    fetch('?controller=warehouse&action=checkDRNumber&dr_number=' + encodeURIComponent(drNumber))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.exists && data.po_ids && data.po_ids.length > 0) {
                window.location.href = '?controller=warehouse&action=printDRPreview&dr_number=' + encodeURIComponent(drNumber) + '&po_id=' + data.po_ids[0];
            } else {
                alert('Error: DR number "' + drNumber + '" not found. Please check the DR number and try again.');
            }
        })
        .catch(function() {
            alert('Error: Could not verify DR number. Please try again.');
        });
});

document.querySelectorAll('.viewDeliveryBtn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (this.disabled) return;
        document.getElementById('viewDrNumber').textContent = this.dataset.dr || '-';
        document.getElementById('viewPoNumber').textContent = this.dataset.po || '-';
        document.getElementById('viewCustomer').textContent = this.dataset.customer || '-';
        document.getElementById('viewDate').textContent = this.dataset.date || '-';
        var remarks = this.dataset.remarks || '-';
        var remarksEl = document.getElementById('viewRemarks');
        if (this.dataset.remarksType === 'report') {
            remarksEl.innerHTML = '<span style="color:red;font-weight:bold;">' + remarks.replace(/</g, '&lt;') + '</span>';
        } else if (this.dataset.remarksType === 'edited') {
            remarksEl.innerHTML = '<span style="color:#e6a800;font-weight:bold;">' + remarks.replace(/</g, '&lt;') + '</span>';
        } else {
            remarksEl.textContent = remarks;
        }
        var lotItems = JSON.parse(this.dataset.lotItems || '[]');
        var tbody = document.getElementById('viewLotItemsBody');
        tbody.innerHTML = '';
        var total = 0;
        var totalCases = 0;
        lotItems.forEach(function(item) {
            total += item.qty || 0;
            var conv = item.uom_conversion || null;
            var uom = item.item_uom || '';
            var cases = (conv && uom !== 'CS') ? Math.round((item.qty || 0) / conv * 100) / 100 : 0;
            totalCases += cases;
            var tr = document.createElement('tr');
            tr.innerHTML = '<td>' + (item.item_code || '-') + '</td>' +
                '<td>' + (item.item_description || '-') + '</td>' +
                '<td>' + (item.lot_number || '-') + '</td>' +
                '<td class="text-end">' + (item.qty || 0) + '</td>' +
                '<td class="text-end">' + (cases > 0 ? cases + ' CS' : '—') + '</td>';
            tbody.appendChild(tr);
        });
        document.getElementById('viewTotalQty').textContent = total;
        document.getElementById('viewTotalCases').textContent = totalCases > 0 ? totalCases + ' CS' : '—';

        var photoContainer = document.getElementById('viewDRPhotoContainer');
        var receiptPath = this.dataset.receiptPath || '';
        if (receiptPath) {
            photoContainer.innerHTML = '<a href="' + receiptPath + '" target="_blank"><img src="' + receiptPath + '" alt="DR Photo" style="max-height:120px; border-radius:6px; border:1px solid #ddd;"></a>';
        } else {
            photoContainer.innerHTML = '<span class="text-muted">No attachment attached for this DR</span>';
        }
    });
});

var reportDeliveryModal = null;
document.querySelectorAll('.reportDeliveryBtn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (this.disabled) return;
        document.getElementById('reportDeliveryDr').value = this.dataset.dr || '-';
        document.getElementById('reportDeliveryRemarks').value = '';
        reportDeliveryModal = new bootstrap.Modal(document.getElementById('reportDeliveryModal'));
        reportDeliveryModal.show();

        document.getElementById('reportDeliveryForm').onsubmit = function(e) {
            e.preventDefault();
            var deliveryId = btn.dataset.deliveryId;
            var remarks = document.getElementById('reportDeliveryRemarks').value.trim();
            if (!remarks) {
                alert('Please describe the concern');
                return;
            }
            var formData = new FormData();
            formData.append('delivery_id', deliveryId);
            formData.append('remarks', remarks);

            fetch('?controller=warehouse&action=reportDelivery', {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    reportDeliveryModal.hide();
                    var cell = btn.closest('tr').querySelector('td:nth-child(9)');
                    cell.innerHTML = '<span style="color:red;font-weight:bold;">' + remarks.replace(/</g, '&lt;') + '</span>';
                } else {
                    alert('Error: ' + (data.error || 'Failed to submit report'));
                }
            })
            .catch(function(err) {
                alert('Error submitting report: ' + err.message);
            });
        };
    });
});

var attachDRModal = null;
var selectedDRFile = null;

document.querySelectorAll('.attachDRBtn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (this.disabled) return;
        document.getElementById('attachDeliveryId').value = this.dataset.deliveryId;
        document.getElementById('attachPoId').value = this.dataset.poId;
        document.getElementById('attachDRNumber').value = this.dataset.dr || '-';
        document.getElementById('photoPreview').style.display = 'none';
        document.getElementById('drPhotoInput').value = '';
        selectedDRFile = null;
        attachDRModal = new bootstrap.Modal(document.getElementById('attachDRModal'));
        attachDRModal.show();
    });
});

var dropZone = document.getElementById('dropZone');
var drPhotoInput = document.getElementById('drPhotoInput');

dropZone.addEventListener('click', function() { drPhotoInput.click(); });
dropZone.addEventListener('dragover', function(e) { e.preventDefault(); dropZone.classList.add('border-primary', 'bg-light'); });
dropZone.addEventListener('dragleave', function() { dropZone.classList.remove('border-primary', 'bg-light'); });
dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    dropZone.classList.remove('border-primary', 'bg-light');
    if (e.dataTransfer.files.length > 0) {
        handleDRFile(e.dataTransfer.files[0]);
    }
});

drPhotoInput.addEventListener('change', function() {
    if (this.files.length > 0) {
        handleDRFile(this.files[0]);
    }
});

function handleDRFile(file) {
    var allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowed.includes(file.type)) {
        alert('Invalid file type. Allowed: JPG, PNG, GIF, WebP');
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        alert('File size must be less than 10MB');
        return;
    }
    selectedDRFile = file;
    var reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('previewName').textContent = file.name;
        document.getElementById('photoPreview').style.display = 'block';
    };
    reader.readAsDataURL(file);
}

document.getElementById('submitDRPhotoBtn').addEventListener('click', function() {
    if (!selectedDRFile) {
        alert('Please select a photo first');
        return;
    }
    var formData = new FormData();
    formData.append('delivery_id', document.getElementById('attachDeliveryId').value);
    formData.append('po_id', document.getElementById('attachPoId').value);
    formData.append('dr_photo', selectedDRFile);

    fetch('?controller=warehouse&action=uploadDRPhoto', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            attachDRModal.hide();
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to upload'));
        }
    })
    .catch(function(err) {
        alert('Error uploading photo: ' + err.message);
    });
});
</script>