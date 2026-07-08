<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDeliveryModal">
            <i class="bi bi-plus-circle me-1"></i> Create Delivery Receipt
        </button>
        <button type="button" class="btn btn-primary ms-2" id="printDRBtn">
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
        <form method="GET" class="d-flex align-items-center">
            <input type="hidden" name="controller" value="warehouse">
            <input type="hidden" name="action" value="deliveries">
            <i class="bi bi-search"></i>
            <input type="text" name="search" id="searchDelivery" class="form-control" placeholder="Search PO number..." value="<?= htmlspecialchars($search ?? '') ?>">
        </form>
    </div>
</div>

<div class="mb-2">
    <small><i class="bi bi-info-circle me-1"></i><strong>Report/Edit:</strong> <span style="color:red;font-weight:bold;">Red</span> = Reported, <span style="color:#e6a800;font-weight:bold;">Yellow</span> = Edited by Admin</small>
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
                    <th>Report / Edit</th>
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
                                $caseParts[] = htmlspecialchars($desc) . ': ' . floor($info['qty'] / $conv) . ' CS';
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
                            $casesSummary = floor(($d['delivery_quantity'] ?? 0) / $conv) . ' CS';
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
                            <span><?= htmlspecialchars($d['remarks']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($d['report_remarks'])): ?>
                            <?php
                            $rmType = $d['remarks_type'] ?? '';
                            if ($rmType === 'report') $rmStyle = 'color:red;font-weight:bold;';
                            elseif ($rmType === 'edited') $rmStyle = 'color:#e6a800;font-weight:bold;';
                            else $rmStyle = '';
                            ?>
                            <span style="<?= $rmStyle ?>"><?= htmlspecialchars($d['report_remarks']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $disAttr = $isActive ? '' : 'disabled'; ?>
                        <?php $disClass = $isActive ? '' : 'disabled'; ?>
                        <?php if ($hasLotItems): ?>
                        <button type="button" class="btn btn-sm btn-primary viewDeliveryBtn <?= $disClass ?>" <?= $disAttr ?>
                            data-bs-toggle="modal" data-bs-target="#viewDeliveryModal"
                            data-dr="<?= htmlspecialchars($d['dr_number']) ?>"
                            data-po="<?= htmlspecialchars($d['customer_po_number']) ?>"
                            data-customer="<?= htmlspecialchars($d['customer_name'] ?? '') ?>"
                            data-date="<?= date('Y-m-d', strtotime($d['delivery_date'])) ?>"
                            data-remarks="<?= htmlspecialchars($d['remarks'] ?? '') ?>"
                            data-report-remarks="<?= htmlspecialchars($d['report_remarks'] ?? '') ?>"
                            data-remarks-type="<?= htmlspecialchars($d['remarks_type'] ?? '') ?>"
                            data-lot-items="<?= htmlspecialchars($d['lot_items'] ?? '[]') ?>"
                            data-delivered-by="<?= htmlspecialchars($d['delivered_by_name'] ?? '') ?>"
                            data-receipt-id="<?= $receipts_map[$d['delivery_id']]['receipt_id'] ?? '' ?>"
                            data-receipt-path="<?= htmlspecialchars($receipts_map[$d['delivery_id']]['file_path'] ?? '') ?>">
                            <i class="bi bi-eye"></i> View
                        </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-sm btn-danger reportDeliveryBtn <?= $disClass ?>" <?= $disAttr ?>
                            data-delivery-id="<?= $d['delivery_id'] ?>"
                            data-dr="<?= htmlspecialchars($d['dr_number'] ?? '') ?>"
                            data-po-id="<?= $d['po_id'] ?>"
                            data-poi-id="<?= $d['poi_id'] ?? '' ?>"
                            data-lot-items="<?= htmlspecialchars($d['lot_items'] ?? '[]') ?>">
                            <i class="bi bi-flag"></i> Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-success attachDRBtn <?= $disClass ?>" <?= $disAttr ?>
                            data-delivery-id="<?= $d['delivery_id'] ?>"
                            data-po-id="<?= $d['po_id'] ?>"
                            data-dr="<?= htmlspecialchars($d['dr_number'] ?? '') ?>">
                            <i class="bi bi-paperclip"></i> Attach
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($deliveries)): ?>
                <tr><td colspan="11" class="text-center text-muted py-4">No deliveries found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<?php $pages = \App\Helpers\Pagination::getPageRange($page, $totalPages); ?>
<nav>
    <ul class="pagination justify-content-center mt-4">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?controller=warehouse&action=deliveries&page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>">&laquo; Prev</a>
        </li>
        <?php foreach ($pages as $p): ?>
            <?php if ($p === '...'): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php else: ?>
            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                <a class="page-link" href="?controller=warehouse&action=deliveries&page=<?= $p ?>&search=<?= urlencode($search ?? '') ?>"><?= $p ?></a>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?controller=warehouse&action=deliveries&page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>">Next &raquo;</a>
        </li>
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
    <div class="modal-dialog" style="max-width: 95vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-truck me-2"></i>Record Delivery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?controller=warehouse&action=createMultipleDelivery">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
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
                            <div class="mb-3" id="lotRow" style="display: none;">
                                <label class="form-label fw-bold">Select Items &amp; Lots *</label>
                                <div id="lotCheckboxContainer" class="form-check"></div>
                                <input type="hidden" name="lot_ids" id="selectedLotIds">
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
                        <div class="col-md-6">
                            <div id="poItemSummary" style="display: none;">
                                <label class="form-label fw-bold">PO Item Summary</label>
                                <table class="table table-sm table-bordered mb-0" id="poItemTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th class="text-end">PO Qty</th>
                                            <th class="text-end">Produced</th>
                                            <th class="text-end">Delivered</th>
                                            <th class="text-end">Balance</th>
                                            <th class="text-end text-success">Available</th>
                                        </tr>
                                    </thead>
                                    <tbody id="poItemTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div style="display: none;">
                        <input type="number" name="delivery_quantity" id="deliveryQty" class="form-control" min="1">
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

<!-- Delivery Preview Modal -->
<div class="modal fade" id="deliveryPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Confirm Delivery</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Please review the delivery details before saving.</p>
                <table class="table table-bordered mb-0">
                    <tr><th style="width:40%">DR Number</th><td id="previewDR"></td></tr>
                    <tr><th>Purchase Order</th><td id="previewPO"></td></tr>
                    <tr><th>Item</th><td id="previewItem"></td></tr>
                    <tr><th>Lot Details</th><td id="previewLots"></td></tr>
                    <tr><th>Total Quantity</th><td id="previewQty"></td></tr>
                    <tr><th>Delivery Date</th><td id="previewDate"></td></tr>
                    <tr><th>Remarks</th><td id="previewRemarks"></td></tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmDeliveryBtn"><i class="bi bi-check-lg me-1"></i>Confirm & Save</button>
            </div>
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
                    <div class="col-md-6">
                        <strong>Report / Edit:</strong> <span id="viewReportRemarks">-</span>
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
                        <label class="form-label">Type of Report <span class="text-danger">*</span></label>
                        <select id="reportDeliveryType" class="form-select" required>
                            <option value="dr_number">DR Number</option>
                            <option value="quantity">Quantity</option>
                        </select>
                    </div>
                    <div class="mb-3" id="reportLotPickerRow" style="display:none;">
                        <label class="form-label">Select Lot <span class="text-danger">*</span></label>
                        <select id="reportLotPicker" class="form-select">
                            <option value="">-- Select Lot --</option>
                        </select>
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
var _searchTimer;
document.getElementById('searchDelivery').addEventListener('input', function() {
    clearTimeout(_searchTimer);
    var form = this.closest('form');
    _searchTimer = setTimeout(function() { form.submit(); }, 500);
});

(function() {
    var s = document.getElementById('searchDelivery');
    if (s && s.value) { s.focus(); s.setSelectionRange(s.value.length, s.value.length); }
})();

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
            const divs = itemCell.querySelectorAll('div');
            if (divs.length > 0) {
                divs.forEach(d => {
                    const t = d.textContent.trim().split('(')[0].trim();
                    if (t && t !== '-') items.add(t);
                });
            } else {
                itemCell.querySelectorAll('small').forEach(s => {
                    const t = s.textContent.trim().split('(')[0].trim();
                    if (t && t !== '-') items.add(t);
                });
            }
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
    document.querySelectorAll('#deliveryTableBody tr').forEach(row => {
        if (row.querySelector('td[colspan]')) { row.style.display = ''; return; }
        const cust = row.cells[1] ? row.cells[1].textContent.trim().toLowerCase() : '';
        const itemText = row.cells[2] ? row.cells[2].textContent.trim().toLowerCase() : '';
        const drText = row.cells[3] ? row.cells[3].textContent.trim().toLowerCase() : '';
        const deliveryDate = row.cells[7] ? row.cells[7].textContent.trim() : '';
        let show = true;
        if (custFilter && !cust.includes(custFilter)) show = false;
        if (itemFilter && !itemText.includes(itemFilter)) show = false;
        if (drFilter && !drText.includes(drFilter)) show = false;
        if (dateFilter && deliveryDate !== dateFilter) show = false;
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
    var form = document.querySelector('#searchDelivery').closest('form');
    if (form) form.submit();
    else applyDeliveryFilters();
});

document.addEventListener('DOMContentLoaded', populateDeliveryFilters);

let poItemsCache = [];

function renderLotCheckboxes(lots, lotRow, lotContainer, itemName, poiId) {
    if (!lots || lots.length === 0) return;
    var hdr = document.createElement('div');
    hdr.className = 'fw-bold text-primary small mb-1 mt-2 lot-header';
    hdr.dataset.poiId = poiId;
    hdr.textContent = itemName;
    lotContainer.appendChild(hdr);
    for (var i = 0; i < lots.length; i++) {
        var lot = lots[i];
        if (document.getElementById('lotChk_' + lot.lot_id)) continue;
        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex align-items-center mb-2 p-2 border rounded bg-light lot-wrapper';
        wrapper.dataset.poiId = poiId;
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
        hint.textContent = 'Required';
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
                qtyInput.value = '';
                qtyInput.focus();
            }
        });
    }
}

document.getElementById('poSelect').addEventListener('change', function() {
    const poId = this.value;
    const lotRow = document.getElementById('lotRow');
    const lotContainer = document.getElementById('lotCheckboxContainer');
    const summaryDiv = document.getElementById('poItemSummary');
    const summaryBody = document.getElementById('poItemTableBody');

    lotRow.style.display = 'none';
    lotContainer.innerHTML = '';
    summaryDiv.style.display = 'none';
    summaryBody.innerHTML = '';

    if (!poId) return;

    Promise.all([
        fetch('?controller=warehouse&action=getPODetails&id=' + poId).then(function(r) { return r.json(); }),
        fetch('?controller=warehouse&action=getAvailableLots&po_id=' + poId).then(function(r) { return r.json(); })
    ]).then(function(results) {
        var data = results[0];
        var lots = results[1];
        var items = data.po_items || [];
        poItemsCache = items;

        if (items.length === 0) return;

        // Calculate available per item from lot data
        var availableByPoi = {};
        lots.forEach(function(lot) {
            var pid = lot.poi_id;
            availableByPoi[pid] = (availableByPoi[pid] || 0) + lot.available_quantity;
        });

        summaryBody.innerHTML = '';
        items.forEach(function(item) {
            var qty = item.quantity || 0;
            var produced = item.produced_quantity || 0;
            var delivered = item.delivered_quantity || 0;
            var balance = Math.max(0, qty - delivered);
            var available = availableByPoi[item.poi_id] || 0;
            var tr = document.createElement('tr');
            tr.dataset.poiId = item.poi_id;
            tr.innerHTML = '<td>' + (item.item_description || '-') + '</td>' +
                '<td class="text-end">' + qty + '</td>' +
                '<td class="text-end">' + produced + '</td>' +
                '<td class="text-end">' + delivered + '</td>' +
                '<td class="text-end fw-bold">' + balance + '</td>' +
                '<td class="text-end fw-bold text-success">' + available + '</td>';
            summaryBody.appendChild(tr);
        });
        summaryDiv.style.display = 'block';

        // Render lots grouped by item
        var grouped = {};
        items.forEach(function(item) {
            grouped[item.poi_id] = item.item_description || '-';
        });
        var poiIds = Object.keys(grouped);
        var lotsByPoi = {};
        lots.forEach(function(lot) {
            var pid = lot.poi_id;
            if (!lotsByPoi[pid]) lotsByPoi[pid] = [];
            lotsByPoi[pid].push(lot);
        });
        poiIds.forEach(function(pid) {
            var itemName = grouped[pid];
            var poiLots = lotsByPoi[pid] || [];
            renderLotCheckboxes(poiLots, lotRow, lotContainer, itemName, pid);
        });
        if (lotContainer.children.length > 0) {
            lotRow.style.display = 'block';
        }
    });
});

document.getElementById('createDeliveryModal').addEventListener('hidden.bs.modal', function() {
    var form = this.querySelector('form');
    form.reset();
    document.getElementById('lotCheckboxContainer').innerHTML = '';
    document.getElementById('lotRow').style.display = 'none';
    document.getElementById('poItemSummary').style.display = 'none';
    document.getElementById('poItemTableBody').innerHTML = '';
    document.getElementById('selectedLotIds').value = '';
    deliveryFormConfirmed = false;
});

document.querySelector('#createDeliveryModal form').addEventListener('submit', function(e) {
    if (deliveryFormConfirmed) return; // skip preview, actually submit
    e.preventDefault();

    const poSelect = document.getElementById('poSelect');
    if (!poSelect.value) {
        alert('Please select a Purchase Order');
        return;
    }

    const checkedBoxes = document.querySelectorAll('#lotCheckboxContainer input[type="checkbox"]:checked');
    if (checkedBoxes.length === 0) {
        alert('Please select at least one lot');
        return;
    }

    const lotPairs = [];
    let hasError = false;
    checkedBoxes.forEach(function(cb) {
        const lotId = cb.value;
        const lotLabel = cb.parentNode.querySelector('label') ? cb.parentNode.querySelector('label').textContent.trim() : lotId;
        const qtyInput = document.getElementById('lotQty_' + lotId);
        let qty = parseInt(qtyInput.value) || 0;
        const max = parseInt(qtyInput.dataset.max) || 0;
        if (qty <= 0) {
            hasError = true;
            alert('Please enter a quantity for ' + lotLabel);
            return;
        }
        if (qty > max) {
            hasError = true;
            alert('Quantity ' + qty + ' exceeds available ' + max + ' for ' + lotLabel);
            return;
        }
        lotPairs.push(lotId + ':' + qty);
    });
    if (hasError) return;

    document.getElementById('selectedLotIds').value = lotPairs.join(',');

    // Build preview
    var drNumber = document.getElementById('modalDrNumber').value.trim();
    var poOption = poSelect.options[poSelect.selectedIndex];
    var poText = poOption ? poOption.textContent.trim() : '-';
    var deliveryDate = document.querySelector('#createDeliveryModal input[name="delivery_date"]').value;
    var remarks = document.querySelector('#createDeliveryModal textarea[name="remarks"]').value.trim() || '-';

    document.getElementById('previewDR').textContent = drNumber;
    document.getElementById('previewPO').textContent = poText;
    document.getElementById('previewDate').textContent = deliveryDate;
    document.getElementById('previewRemarks').textContent = remarks;

    // Build lot details grouped by item
    var lotsHtml = '<table class="table table-sm table-bordered mb-0">';
    lotsHtml += '<thead><tr><th>Item</th><th>Lot No.</th><th>Qty</th></tr></thead><tbody>';
    var totalQty = 0;
    var itemNames = {};
    checkedBoxes.forEach(function(cb) {
        var lotId = cb.value;
        var wrapper = cb.closest('.lot-wrapper');
        var poiId = wrapper ? wrapper.dataset.poiId : '';
        var header = document.querySelector('#lotCheckboxContainer .lot-header[data-poi-id="' + poiId + '"]');
        var itemName = header ? header.textContent.trim() : '-';
        itemNames[itemName] = true;
        var lotLabel = cb.parentNode.querySelector('label') ? cb.parentNode.querySelector('label').textContent.trim() : lotId;
        var qtyInput = document.getElementById('lotQty_' + lotId);
        var qty = parseInt(qtyInput.value) || parseInt(qtyInput.dataset.max) || 0;
        totalQty += qty;
        lotsHtml += '<tr><td>' + itemName + '</td><td>' + lotLabel + '</td><td>' + qty + '</td></tr>';
    });
    lotsHtml += '</tbody></table>';
    document.getElementById('previewLots').innerHTML = lotsHtml;
    document.getElementById('previewItem').textContent = Object.keys(itemNames).join(', ') || '-';
    document.getElementById('previewQty').textContent = totalQty;

    // Show preview modal
    var previewModal = new bootstrap.Modal(document.getElementById('deliveryPreviewModal'));
    previewModal.show();
});

var deliveryFormConfirmed = false;

document.getElementById('confirmDeliveryBtn').addEventListener('click', function() {
    var previewModal = bootstrap.Modal.getInstance(document.getElementById('deliveryPreviewModal'));
    previewModal.hide();
    deliveryFormConfirmed = true;
    document.querySelector('#createDeliveryModal form').submit();
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
        remarksEl.textContent = remarks;
        var reportRemarks = this.dataset.reportRemarks || '-';
        var reportRemarksEl = document.getElementById('viewReportRemarks');
        if (this.dataset.remarksType === 'report') {
            reportRemarksEl.innerHTML = '<span style="color:red;font-weight:bold;">' + reportRemarks.replace(/</g, '&lt;') + '</span>';
        } else if (this.dataset.remarksType === 'edited') {
            reportRemarksEl.innerHTML = '<span style="color:#e6a800;font-weight:bold;">' + reportRemarks.replace(/</g, '&lt;') + '</span>';
        } else {
            reportRemarksEl.textContent = reportRemarks;
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
            var cases = (conv && uom !== 'CS') ? Math.floor((item.qty || 0) / conv) : 0;
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
var reportLotItems = [];
document.querySelectorAll('.reportDeliveryBtn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        if (this.disabled) return;
        document.getElementById('reportDeliveryDr').value = this.dataset.dr || '-';
        document.getElementById('reportDeliveryRemarks').value = '';
        document.getElementById('reportDeliveryType').value = 'dr_number';
        document.getElementById('reportLotPickerRow').style.display = 'none';
        document.getElementById('reportLotPicker').innerHTML = '<option value="">-- Select Lot --</option>';

        reportLotItems = JSON.parse(this.dataset.lotItems || '[]');
        reportDeliveryModal = new bootstrap.Modal(document.getElementById('reportDeliveryModal'));
        reportDeliveryModal.show();

        document.getElementById('reportDeliveryForm').onsubmit = function(e) {
            e.preventDefault();
            var deliveryId = btn.dataset.deliveryId;
            var poId = btn.dataset.poId;
            var remarks = document.getElementById('reportDeliveryRemarks').value.trim();
            var reportType = document.getElementById('reportDeliveryType').value;
            var lotPicker = document.getElementById('reportLotPicker');
            var selectedLotIndex = lotPicker.value;

            if (!remarks) {
                alert('Please describe the concern');
                return;
            }

            var formData = new FormData();
            formData.append('delivery_id', deliveryId);
            formData.append('remarks', remarks);
            formData.append('report_type', reportType);
            formData.append('po_id', poId);
            formData.append('poi_id', btn.dataset.poiId || '');

            if (reportType === 'quantity') {
                if (selectedLotIndex === '') {
                    alert('Please select a lot');
                    return;
                }
                var lot = reportLotItems[parseInt(selectedLotIndex)];
                formData.append('lot_id', lot.lot_id);
                formData.append('poi_id', lot.poi_id);
                formData.append('old_quantity', lot.qty);
            }

            fetch('?controller=warehouse&action=reportDelivery', {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    reportDeliveryModal.hide();
                    var cell = btn.closest('tr').querySelector('td:nth-child(10)');
                    cell.innerHTML = '<span style="color:red;font-weight:bold;">' + remarks.replace(/</g, '&lt;') + '</span>';
                    showToast('Report submitted successfully', 'success');
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

document.getElementById('reportDeliveryType').addEventListener('change', function() {
    var lotRow = document.getElementById('reportLotPickerRow');
    var lotPicker = document.getElementById('reportLotPicker');
    if (this.value === 'quantity') {
        lotPicker.innerHTML = '<option value="">-- Select Lot --</option>';
        reportLotItems.forEach(function(li, idx) {
            var opt = document.createElement('option');
            opt.value = idx;
            opt.textContent = (li.item_description || '') + ' | ' + (li.lot_number || '') + ' | Qty: ' + (li.qty || 0);
            lotPicker.appendChild(opt);
        });
        lotRow.style.display = 'block';
    } else {
        lotRow.style.display = 'none';
        lotPicker.value = '';
    }
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

function showToast(message, type) {
    var toast = document.createElement('div');
    toast.className = 'alert alert-' + (type || 'success') + ' alert-dismissible fade show position-fixed';
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
    toast.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    document.body.appendChild(toast);
    setTimeout(function() { if (toast.parentNode) toast.remove(); }, 3000);
}
</script>