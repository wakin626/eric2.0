<h4><i class="bi bi-cart3 me-2"></i>Customer PO</h4>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2 flex-wrap">
        <select id="filterCustomer" class="form-select form-select-sm filter-select" style="width:200px">
            <option value="">All Customers</option>
        </select>
        <select id="filterItem" class="form-select form-select-sm filter-select" style="width:200px">
            <option value="">All Items</option>
        </select>
        <input type="date" id="filterDate" class="form-control form-control-sm" style="width:160px" title="Filter by PO Date">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearFilters"><i class="bi bi-x-circle me-1"></i>Clear</button>
    </div>
    <div class="search-box" style="width: 300px;">
        <form method="GET" class="d-flex align-items-center">
            <input type="hidden" name="controller" value="production">
            <input type="hidden" name="action" value="purchaseOrders">
            <i class="bi bi-search"></i>
            <input type="text" name="search" id="searchPO" class="form-control" placeholder="Search PO..." value="<?= htmlspecialchars($search ?? '') ?>">
        </form>
    </div>
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="sortable" data-sort="po_number">PO Number <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="po_date">PO Date <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="customer">Customer <i class="bi bi-chevron-expand"></i></th>
                    <th>Item</th>
                    <th class="sortable" data-sort="progress">Produced PO QTY <i class="bi bi-chevron-expand"></i></th>
                    <th class="sortable" data-sort="delivered">Delivered PO QTY <i class="bi bi-chevron-expand"></i></th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="poTableBody">
                <?php foreach ($purchase_orders as $po):
                    $items = $po_items_map[$po['po_id']] ?? [];
                ?>
                <tr>
                    <td><strong>
                    <?php
                    $allNormalCr = [];
                    if (!empty($items) && ($po['production_type'] ?? 'normal') !== 'advance') {
                        foreach ($items as $item) {
                            $ncrRecords = ($normal_consumption_records ?? [])[$item['poi_id']] ?? [];
                            foreach ($ncrRecords as $ncr) { $allNormalCr[] = $ncr; }
                        }
                    }
                    if (!empty($allNormalCr)):
                    ?><span style="opacity:0.75"><?= htmlspecialchars($allNormalCr[0]['advance_po_number']) ?></span>/<?php endif; ?><?= htmlspecialchars($po['customer_po_number']) ?>
                    </strong></td>
                    <td><?= date('Y-m-d', strtotime($po['customer_po_date'])) ?></td>
                    <td><?= htmlspecialchars($po['customer_name'] ?? '-') ?></td>
                    <td>
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $idx => $item): ?>
                                <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
                                <div class="d-flex align-items-center" style="min-height: 20px;">
                                    <small><?= htmlspecialchars($item['item_description'] ?? '-') ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <small class="text-muted">-</small>
                        <?php endif; ?>
                    </td>
<td>
    <?php if (!empty($items)): ?>
        <?php foreach ($items as $idx => $item):
            $qty = $item['quantity'] ?? 0;
            $itemProduced = $item['produced_quantity'] ?? 0;
            $itemPercent = $qty > 0 ? round(($itemProduced / $qty) * 100) : 0;
            $isExcess = $itemProduced > $qty;
        ?>
            <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
            <div class="d-flex align-items-center flex-wrap gap-1" style="min-height: 20px;">
                <div class="progress flex-grow-1 me-2" style="height: 12px; width: 50px;">
                    <div class="progress-bar <?= $isExcess ? 'bg-danger' : ($itemPercent >= 100 ? 'bg-success' : 'bg-warning') ?>" style="width: <?= min($itemPercent, 100) ?>%"></div>
                </div>
                <small class="text-muted text-nowrap"><?= $itemProduced ?>/<?= $qty ?> pcs</small>
                <?php if ($isExcess): ?>
                    <span class="badge bg-danger">+<?= $itemProduced - $qty ?></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <small class="text-muted">-</small>
    <?php endif; ?>
</td>
<td>
    <?php if (!empty($items)): ?>
        <?php foreach ($items as $idx => $item):
            $itemQty = $item['quantity'] ?? 0;
            $itemDelivered = $item['delivered_quantity'] ?? 0;
            $itemRemaining = max(0, $itemQty - $itemDelivered);
        ?>
            <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
            <?php $conv = $item['uom_conversion'] ?? null; ?>
            <small class="text-nowrap"><?= $itemDelivered ?>/<?= $itemQty ?> pcs, <?= $conv ? round($itemDelivered / $conv) . '/' . round($itemQty / $conv) . ' cs' : '—/—' ?></small>
        <?php endforeach; ?>
    <?php else: ?>
        <small class="text-muted">-</small>
    <?php endif; ?>
</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-primary view-po-btn" data-po-id="<?= $po['po_id'] ?>">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-success update-po-btn" data-po-id="<?= $po['po_id'] ?>" data-produced="<?= $po['produced_quantity'] ?? 0 ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($purchase_orders)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No purchase orders found</td></tr>
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
            <a class="page-link" href="?controller=production&action=purchaseOrders&page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>">&laquo; Prev</a>
        </li>
        <?php foreach ($pages as $p): ?>
            <?php if ($p === '...'): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php else: ?>
            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                <a class="page-link" href="?controller=production&action=purchaseOrders&page=<?= $p ?>&search=<?= urlencode($search ?? '') ?>"><?= $p ?></a>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?controller=production&action=purchaseOrders&page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>">Next &raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<div class="modal fade" id="viewPOModal">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>PO Details - <span id="viewPONumber"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Customer Code:</strong></p>
                        <p class="text-muted" id="viewCustomerCode">-</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Customer Name:</strong></p>
                        <p class="text-muted" id="viewCustomerName">-</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Customer TIN:</strong></p>
                        <p class="text-muted" id="viewCustomerTin">-</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1"><strong>Terms:</strong></p>
                        <p class="text-muted" id="viewCustomerTerms">-</p>
                    </div>
                </div>

                <h5 class="mb-3">Items</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Description</th>
                                <th>UOM</th>
                                <th>Quantity</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody id="viewPOItems">
                        </tbody>
                    </table>
                </div>

                <hr>
                <h5 class="mb-3">Lot Tracker</h5>
                <div class="row mb-3 align-items-center">
                    <div class="col-md-4">
                        <label for="lotTrackerItem" class="form-label fw-bold">Select Item</label>
                        <select id="lotTrackerItem" class="form-select form-select-sm">
                            <option value="">-- Select Item --</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0" id="lotTrackerTable">
                        <thead>
                            <tr>
                                <th>Lot No.</th>
                                <th class="text-end">Qty Produced</th>
                            </tr>
                        </thead>
                        <tbody id="lotTrackerBody">
                            <tr><td colspan="2" class="text-center text-muted py-3">Select an item to view lots</td></tr>
                        </tbody>
                        <tfoot id="lotTrackerFoot" style="display:none;">
                            <tr class="table-light fw-bold">
                                <td>Total</td>
                                <td class="text-end" id="lotTrackerTotal">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="updatePOModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Stock Transfer Slip - <span id="updatePONumber"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <p class="mb-1"><strong>Customer:</strong> <span id="updateCustomerName"></span></p>
                    <p class="mb-1" id="updateItemNameRow" style="display:none;"><strong>Item:</strong> <span id="updateItemName"></span></p>
                </div>
                <form method="POST" action="?controller=production&action=updateQuantity" id="updatePOForm" novalidate>
                    <input type="hidden" name="po_id" id="updatePoIdInput" value="">
                    <input type="hidden" name="from" value="purchaseOrders">
                    <!-- Single item mode with lot rows -->
                    <div id="singleItemGroup">
                        <input type="hidden" name="poi_id" id="updatePoiIdInput" value="">
                        <label class="form-label mb-2">Lot Entries</label>
                        <div id="singleLotContainer">
                            <div class="lot-entry mb-2 border rounded p-2 bg-light">
                                <div class="row g-2 align-items-end single-lot-row">
                                    <div class="col-md-3">
                                        <label class="form-label">Item</label>
                                        <input type="text" class="form-control" readonly value="-">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Produced / Required</label>
                                        <input type="text" class="form-control" readonly value="-">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Lot Number <span class="text-danger">*</span></label>
                                        <input type="text" name="lot_number[]" class="form-control" placeholder="e.g. LOT-001" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Add Quantity <span class="text-danger">*</span></label>
                                        <input type="number" name="added_quantity[]" class="form-control" min="1" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">PCS to CASE</label>
                                        <input type="number" name="pcs_per_case[]" class="form-control" min="1" placeholder="PCS to CASE">
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <button type="button" class="btn btn-danger btn-sm mt-4 remove-single-lot" style="display:none;"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                                <div class="row g-2 mt-1">
                                    <div class="col-md-2">
                                        <label class="form-label small">Shift <span class="text-danger">*</span></label>
                                        <select name="shift[]" class="form-select form-select-sm" required>
                                            <option value="">-- Select --</option>
                                            <option value="1st Shift">1st Shift</option>
                                            <option value="2nd Shift">2nd Shift</option>
                                            <option value="3rd Shift">3rd Shift</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">Status</label>
                                        <select name="reject_status[]" class="form-select form-select-sm">
                                            <option value="Good">Good</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Remarks</label>
                                        <input type="text" name="sts_remarks[]" class="form-control form-control-sm" placeholder="Optional remarks">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm mt-1" id="addSingleLotBtn"><i class="bi bi-plus"></i> Add Lot</button>
                    </div>

                    <!-- Bulk items mode -->
                    <div id="bulkItemsGroup" class="d-none">
                        <label class="form-label mb-2">Items</label>
                        <div id="bulkItemsContainer"></div>
                        <button type="button" class="btn btn-primary btn-sm mt-2" id="addBulkItemBtn"><i class="bi bi-plus"></i> Add Item</button>
                    </div>

                    <hr class="my-3">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Prepared by <span class="text-danger">*</span></label>
                            <input type="text" name="prepared_by_name" class="form-control" required placeholder="e.g. Juan Dela Cruz" value="<?= htmlspecialchars($_SESSION['full_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Checked by</label>
                            <input type="text" name="checked_by_name" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Received by</label>
                            <input type="text" name="received_by_name" class="form-control" placeholder="Optional">
                        </div>
                    </div>

                    <div class="modal-footer px-0 pb-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- STS Preview Modal -->
<div class="modal fade" id="updatePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>Confirm Stock Transfer Slip</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Please review the stock transfer details before saving.</p>
                <table class="table table-bordered mb-2">
                    <tr><th style="width:35%">PO Number</th><td id="prevUPONumber"></td></tr>
                    <tr><th>Customer</th><td id="prevUCustomer"></td></tr>
                    <tr><th>Item</th><td id="prevUItem"></td></tr>
                </table>

                <div class="mb-3">
                    <strong>Existing Lots:</strong>
                    <div id="prevUExistingLots"></div>
                </div>

                <div>
                    <strong>Updating / Adding:</strong>
                    <div id="prevULots"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmUpdateBtn"><i class="bi bi-check-lg me-1"></i>Confirm & Save</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Production PO page loaded');
    
    document.querySelectorAll('.view-po-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const poId = this.getAttribute('data-po-id');
            console.log('View button clicked, poId:', poId);
            fetch('?controller=production&action=getPODetails&id=' + poId, { credentials: 'same-origin' })
                .then(function(response) {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error('HTTP error ' + response.status + ': ' + text);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    const po = data.po;
                    const items = data.po_items;
                    
                    document.getElementById('viewPONumber').textContent = po.customer_po_number || '-';
                    document.getElementById('viewCustomerCode').textContent = po.customer_code || '-';
                    document.getElementById('viewCustomerName').textContent = po.customer_name || '-';
                    document.getElementById('viewCustomerTin').textContent = po.customer_tin || '-';
                    document.getElementById('viewCustomerTerms').textContent = (po.customer_terms || 0) + ' days';
                    
                    const total = po.total_quantity || 0;
                    const produced = po.produced_quantity || 0;
                    
                    const tbody = document.getElementById('viewPOItems');
                    tbody.innerHTML = '';
                    if (items && items.length > 0) {
                        items.forEach(function(item, idx) {
                            const qty = item.quantity || 0;
                            const itemProduced = item.produced_quantity || 0;
                            const itemPercent = qty > 0 ? Math.round((itemProduced / qty) * 100) : 0;
                            const isExcess = itemProduced > qty;
                            const barClass = isExcess ? 'bg-danger' : (itemPercent >= 100 ? 'bg-success' : 'bg-warning');
                            const barWidth = Math.min(itemPercent, 100);
                            const excessBadge = isExcess ? '<span class="badge bg-danger">' + '+' + (itemProduced - qty) + '</span>' : '';
                            const lineTotal = item.quantity * item.unit_price;
                            const row = '<tr>' +
                                '<td>' + (item.item_code || '-') + '</td>' +
                                '<td>' + (item.item_description || '-') + '</td>' +
                                '<td>' + (item.item_uom || '-') + '</td>' +
                                '<td>' + qty + '</td>' +
                                '<td>' +
                                    '<div class="d-flex align-items-center flex-wrap gap-1">' +
                                        '<div class="progress flex-grow-1 me-2" style="height: 14px; width: 80px;">' +
                                            '<div class="progress-bar ' + barClass + '" style="width: ' + barWidth + '%"></div>' +
                                        '</div>' +
                                        '<small class="text-muted">' + itemProduced + '/' + qty + ' pcs</small>' +
                                        excessBadge +
                                    '</div>' +
                                '</td>' +
                                '</tr>';
                            tbody.innerHTML += row;
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No items found</td></tr>';
                    }

                    var lotSelect = document.getElementById('lotTrackerItem');
                    lotSelect.innerHTML = '<option value="">-- Select Item --</option>';
                    if (items && items.length > 0) {
                        items.forEach(function(item) {
                            lotSelect.innerHTML += '<option value="' + item.poi_id + '">' +
                                (item.item_code || '') + ' - ' + (item.item_description || '-') +
                                '</option>';
                        });
                    }
                    document.getElementById('lotTrackerBody').innerHTML = '<tr><td colspan="2" class="text-center text-muted py-3">Select an item to view lots</td></tr>';
                    document.getElementById('lotTrackerFoot').style.display = 'none';

                    const modal = new bootstrap.Modal(document.getElementById('viewPOModal'));
                    modal.show();
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    alert('Failed to load PO details: ' + (error.message || 'Unknown error'));
                });
        });
    });

    document.getElementById('lotTrackerItem').addEventListener('change', function() {
        var poiId = this.value;
        var tbody = document.getElementById('lotTrackerBody');
        var foot = document.getElementById('lotTrackerFoot');
        var totalEl = document.getElementById('lotTrackerTotal');
        if (!poiId) {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted py-3">Select an item to view lots</td></tr>';
            foot.style.display = 'none';
            return;
        }
        tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted py-3">Loading...</td></tr>';
        foot.style.display = 'none';
        fetch('?controller=production&action=getLotsByPOItem&poi_id=' + poiId)
            .then(function(r) { return r.json(); })
            .then(function(lots) {
                tbody.innerHTML = '';
                if (lots && lots.length > 0) {
                    var total = 0;
                    lots.forEach(function(lot) {
                        var qty = parseInt(lot.quantity_produced) || 0;
                        total += qty;
                        tbody.innerHTML += '<tr>' +
                            '<td><strong>' + (lot.lot_number || '-') + '</strong></td>' +
                            '<td class="text-end">' + qty.toLocaleString() + '</td>' +
                            '</tr>';
                    });
                    totalEl.textContent = total.toLocaleString();
                    foot.style.display = '';
                } else {
                    tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted py-3">No lots created</td></tr>';
                    foot.style.display = 'none';
                }
            })
            .catch(function() {
                tbody.innerHTML = '<tr><td colspan="2" class="text-center text-danger py-3">Failed to load lots</td></tr>';
                foot.style.display = 'none';
            });
    });

    function getStsSubRowHtml() {
        return '<div class="row g-2 mt-1">' +
            '<div class="col-md-2">' +
                '<label class="form-label small">Shift <span class="text-danger">*</span></label>' +
                '<select name="shift[]" class="form-select form-select-sm" required>' +
                    '<option value="">-- Select --</option>' +
                    '<option value="1st Shift">1st Shift</option>' +
                    '<option value="2nd Shift">2nd Shift</option>' +
                    '<option value="3rd Shift">3rd Shift</option>' +
                '</select>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<label class="form-label small">Status</label>' +
                '<select name="reject_status[]" class="form-select form-select-sm">' +
                    '<option value="Good">Good</option>' +
                '</select>' +
            '</div>' +
            '<div class="col-md-3">' +
                '<label class="form-label small">Remarks</label>' +
                '<input type="text" name="sts_remarks[]" class="form-control form-control-sm" placeholder="Optional remarks">' +
            '</div>' +
        '</div>';
    }

    function createBulkRow() {
        const entry = document.createElement('div');
        entry.className = 'lot-entry mb-2 border rounded p-2 bg-light';
        const row = document.createElement('div');
        row.className = 'row g-2 align-items-end bulk-item-row';
        row.innerHTML =
            '<div class="col-md-3">' +
                '<label class="form-label">Item</label>' +
                '<select name="poi_id[]" class="form-select bulk-item-select" required>' +
                    '<option value="">-- Select Item --</option>' +
                '</select>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<label class="form-label">Produced / Required</label>' +
                '<input type="text" class="form-control" readonly>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<label class="form-label">Lot Number <span class="text-danger">*</span></label>' +
                '<input type="text" name="lot_number[]" class="form-control" placeholder="e.g. LOT-001" required>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<label class="form-label">Add Quantity <span class="text-danger">*</span></label>' +
                '<input type="number" name="added_quantity[]" class="form-control bulk-qty" min="0" required>' +
            '</div>' +
            '<div class="col-md-2">' +
                '<label class="form-label">PCS to CASE</label>' +
                '<input type="number" name="pcs_per_case[]" class="form-control" min="1" placeholder="PCS to CASE">' +
            '</div>' +
            '<div class="col-md-1 text-end">' +
                '<button type="button" class="btn btn-danger btn-sm mt-4 remove-bulk-item"><i class="bi bi-trash"></i></button>' +
            '</div>';
        entry.appendChild(row);
        entry.insertAdjacentHTML('beforeend', getStsSubRowHtml());
        return entry;
    }

    function populateBulkSelect(selectEl, items) {
        selectEl.innerHTML = '<option value="">-- Select Item --</option>';
        items.forEach(function(item) {
            const opt = document.createElement('option');
            opt.value = item.poi_id;
            opt.textContent = item.item_description + ' (' + (item.produced_quantity || 0) + '/' + item.quantity + ')';
            opt.setAttribute('data-qty', item.quantity);
            opt.setAttribute('data-produced', item.produced_quantity || 0);
            opt.setAttribute('data-conv', item.uom_conversion || '');
            selectEl.appendChild(opt);
        });
    }

    function updateBulkRowInfo(selectEl) {
        const entry = selectEl.closest('.lot-entry');
        const row = selectEl.closest('.bulk-item-row');
        const infoInput = row.querySelector('input[readonly]');
        const selected = selectEl.options[selectEl.selectedIndex];
        if (selectEl.value && selected) {
            infoInput.value = selected.getAttribute('data-produced') + ' / ' + selected.getAttribute('data-qty');
            var conv = selected.getAttribute('data-conv') || '';
            var pcsInput = entry.querySelector('input[name="pcs_per_case[]"]');
            if (pcsInput) pcsInput.value = conv;
        } else {
            infoInput.value = '';
        }
    }

    function updateBulkRemoveButtons() {
        const entries = document.querySelectorAll('#bulkItemsContainer .lot-entry');
        entries.forEach(function(entry) {
            const btn = entry.querySelector('.remove-bulk-item');
            if (btn) {
                btn.style.display = entries.length > 1 ? 'inline-flex' : 'none';
            }
        });
        updateStsSequence();
    }

    document.getElementById('addSingleLotBtn').addEventListener('click', function() {
        const container = document.getElementById('singleLotContainer');
        const item = window._currentSingleItem || {};
        const entry = document.createElement('div');
        entry.className = 'lot-entry mb-2 border rounded p-2 bg-light';
        entry.innerHTML =
            '<div class="row g-2 align-items-end single-lot-row">' +
                '<div class="col-md-3"><label class="form-label">Item</label><input type="text" class="form-control" readonly value="' + (item.item_description || '-') + '"></div>' +
                '<div class="col-md-2"><label class="form-label">Produced / Required</label><input type="text" class="form-control" readonly value="' + (item.produced_quantity || 0) + ' / ' + (item.quantity || 0) + '"></div>' +
                '<div class="col-md-2"><label class="form-label">Lot Number <span class="text-danger">*</span></label><input type="text" name="lot_number[]" class="form-control" placeholder="e.g. LOT-001" required></div>' +
                '<div class="col-md-2"><label class="form-label">Add Quantity <span class="text-danger">*</span></label><input type="number" name="added_quantity[]" class="form-control" min="1" required></div>' +
                '<div class="col-md-2"><label class="form-label">PCS to CASE</label><input type="number" name="pcs_per_case[]" class="form-control" min="1" placeholder="PCS to CASE" value="' + (item.uom_conversion || '') + '"></div>' +
                '<div class="col-md-1 text-end"><button type="button" class="btn btn-danger btn-sm mt-4 remove-single-lot"><i class="bi bi-trash"></i></button></div>' +
            '</div>' +
            getStsSubRowHtml();
        container.appendChild(entry);
        updateSingleLotRemoveButtons();
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-single-lot')) {
            const entry = e.target.closest('.lot-entry');
            const container = document.getElementById('singleLotContainer');
            if (container.querySelectorAll('.lot-entry').length > 1) {
                entry.remove();
                updateSingleLotRemoveButtons();
            }
        }
    });

    function updateSingleLotRemoveButtons() {
        const entries = document.querySelectorAll('#singleLotContainer .lot-entry');
        entries.forEach(function(entry) {
            const btn = entry.querySelector('.remove-single-lot');
            if (btn) btn.style.display = entries.length > 1 ? '' : 'none';
        });
        updateStsSequence();
    }

    function updateStsSequence() {
        var base = window._nextStsBase || 1;
        var isSingle = !document.getElementById('singleItemGroup').classList.contains('d-none');
        var entries = isSingle
            ? document.querySelectorAll('#singleLotContainer .lot-entry')
            : document.querySelectorAll('#bulkItemsContainer .lot-entry');
        entries.forEach(function(entry, i) {
            var existing = entry.querySelector('.sts-ref-badge');
            if (!existing) {
                var badge = document.createElement('div');
                badge.className = 'sts-ref-badge mb-1';
                entry.insertBefore(badge, entry.firstChild);
            }
            var badge = entry.querySelector('.sts-ref-badge');
            badge.innerHTML = '<small class="text-primary fw-bold"><i class="bi bi-hash"></i> STS-' + String(base + i).padStart(4, '0') + '</small>';
        });
    }

    document.querySelectorAll('.update-po-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const poId = this.getAttribute('data-po-id');

            fetch('?controller=production&action=getPODetails&id=' + poId, { credentials: 'same-origin' })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error('HTTP error ' + response.status + ': ' + text);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    const po = data.po;
                    const items = data.po_items || [];
                    window._currentPOItems = items;

                    document.getElementById('updatePONumber').textContent = po.customer_po_number || '-';
                    document.getElementById('updateCustomerName').textContent = po.customer_name || '-';
                    document.getElementById('updatePoIdInput').value = poId;
                    document.getElementById('updatePoiIdInput').value = '';
                    document.getElementById('updateItemNameRow').style.display = 'none';

                    const singleGroup = document.getElementById('singleItemGroup');
                    const bulkGroup = document.getElementById('bulkItemsGroup');
                    const bulkContainer = document.getElementById('bulkItemsContainer');
                    const singleLotContainer = document.getElementById('singleLotContainer');
                    
                    if (items.length > 1) {
                        singleGroup.classList.add('d-none');
                        bulkGroup.classList.remove('d-none');
                        bulkContainer.innerHTML = '';
                        window._currentBulkItems = items;
                        window._currentSingleItem = null;

                        document.getElementById('updatePoiIdInput').disabled = true;
                        singleLotContainer.querySelectorAll('input').forEach(function(el) { el.disabled = true; });

                        var itemNames = items.map(function(item) { return item.item_description || '-'; });
                        document.getElementById('updateItemName').textContent = itemNames.join(', ');
                        document.getElementById('updateItemNameRow').style.display = '';

                        const firstRow = createBulkRow();
                        const firstSelect = firstRow.querySelector('.bulk-item-select');
                        populateBulkSelect(firstSelect, items);
                        firstSelect.addEventListener('change', function() {
                            updateBulkRowInfo(this);
                        });
                        bulkContainer.appendChild(firstRow);
                        updateBulkRemoveButtons();
                    } else if (items.length === 1) {
                        singleGroup.classList.remove('d-none');
                        bulkGroup.classList.add('d-none');
                        bulkContainer.innerHTML = '';
                        window._currentBulkItems = [];
                        const item = items[0];
                        window._currentSingleItem = item;
                        document.getElementById('updatePoiIdInput').disabled = false;
                        document.getElementById('updatePoiIdInput').value = item.poi_id;
                        document.getElementById('updateItemName').textContent = item.item_description || '-';
                        document.getElementById('updateItemNameRow').style.display = '';

                        singleLotContainer.innerHTML = '<div class="lot-entry mb-2 border rounded p-2 bg-light">' +
                            '<div class="row g-2 align-items-end single-lot-row">' +
                            '<div class="col-md-3"><label class="form-label">Item</label><input type="text" class="form-control" readonly value="' + (item.item_description || '-') + '"></div>' +
                            '<div class="col-md-2"><label class="form-label">Produced / Required</label><input type="text" class="form-control" readonly value="' + (item.produced_quantity || 0) + ' / ' + (item.quantity || 0) + '"></div>' +
                            '<div class="col-md-2"><label class="form-label">Lot Number <span class="text-danger">*</span></label><input type="text" name="lot_number[]" class="form-control" placeholder="e.g. LOT-001" required></div>' +
                            '<div class="col-md-2"><label class="form-label">Add Quantity <span class="text-danger">*</span></label><input type="number" name="added_quantity[]" class="form-control" min="1" required></div>' +
                            '<div class="col-md-2"><label class="form-label">PCS to CASE</label><input type="number" name="pcs_per_case[]" class="form-control" min="1" placeholder="PCS to CASE" value="' + (item.uom_conversion || '') + '"></div>' +
                            '<div class="col-md-1 text-end"><button type="button" class="btn btn-danger btn-sm mt-4 remove-single-lot" style="display:none;"><i class="bi bi-trash"></i></button></div>' +
                            '</div>' +
                            getStsSubRowHtml() +
                            '</div>';
                        updateSingleLotRemoveButtons();
                    } else {
                        singleGroup.classList.remove('d-none');
                        bulkGroup.classList.add('d-none');
                        bulkContainer.innerHTML = '';
                        window._currentBulkItems = [];
                        window._currentSingleItem = null;
                        document.getElementById('updatePoiIdInput').disabled = false;
                        singleLotContainer.innerHTML = '<div class="lot-entry mb-2 border rounded p-2 bg-light">' +
                            '<div class="row g-2 align-items-end single-lot-row">' +
                            '<div class="col-md-3"><label class="form-label">Item</label><input type="text" class="form-control" readonly value="-"></div>' +
                            '<div class="col-md-2"><label class="form-label">Produced / Required</label><input type="text" class="form-control" readonly value="-"></div>' +
                            '<div class="col-md-2"><label class="form-label">Lot Number <span class="text-danger">*</span></label><input type="text" name="lot_number[]" class="form-control" placeholder="e.g. LOT-001" required></div>' +
                            '<div class="col-md-2"><label class="form-label">Add Quantity <span class="text-danger">*</span></label><input type="number" name="added_quantity[]" class="form-control" min="1" required></div>' +
                            '<div class="col-md-2"><label class="form-label">PCS to CASE</label><input type="number" name="pcs_per_case[]" class="form-control" min="1" placeholder="PCS to CASE"></div>' +
                            '<div class="col-md-1 text-end"><button type="button" class="btn btn-danger btn-sm mt-4 remove-single-lot" style="display:none;"><i class="bi bi-trash"></i></button></div>' +
                            '</div>' +
                            getStsSubRowHtml() +
                            '</div>';
                        updateSingleLotRemoveButtons();
                    }
                    
                    const modal = new bootstrap.Modal(document.getElementById('updatePOModal'));
                    fetch('?controller=production&action=getNextStsRef')
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            var ref = data.sts_ref || 'N/A';
                            var num = parseInt(ref.replace('STS-', '')) || 1;
                            window._nextStsBase = num;
                            updateStsSequence();
                        })
                        .catch(function() {
                            window._nextStsBase = 1;
                            updateStsSequence();
                        });
                    modal.show();
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    alert('Failed to load PO details: ' + (error.message || 'Unknown error'));
                });
        });
    });

    document.getElementById('addBulkItemBtn').addEventListener('click', function() {
        const items = window._currentBulkItems || [];
        const container = document.getElementById('bulkItemsContainer');
        const row = createBulkRow();
        const select = row.querySelector('.bulk-item-select');
        populateBulkSelect(select, items);
        select.addEventListener('change', function() {
            updateBulkRowInfo(this);
        });
        container.appendChild(row);
        updateBulkRemoveButtons();
    });

    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-bulk-item')) {
            const container = document.getElementById('bulkItemsContainer');
            const entries = container.querySelectorAll('.lot-entry');
            if (entries.length > 1) {
                e.target.closest('.lot-entry').remove();
                updateBulkRemoveButtons();
            }
        }
    });

    document.getElementById('updatePOForm').addEventListener('submit', function(e) {
        if (window._updateFormConfirmed) return;
        e.preventDefault();

        const singleGroup = document.getElementById('singleItemGroup');
        const bulkGroup = document.getElementById('bulkItemsGroup');

        if (!bulkGroup.classList.contains('d-none')) {
            let hasSelection = false;
            let hasQuantity = false;
            let allLotsFilled = true;
            document.querySelectorAll('.bulk-item-select').forEach(function(sel) {
                if (sel.value) hasSelection = true;
            });
            document.querySelectorAll('.bulk-qty').forEach(function(inp) {
                if (inp.value && parseInt(inp.value) > 0) hasQuantity = true;
            });
            document.querySelectorAll('#bulkItemsContainer input[name="lot_number[]"]').forEach(function(inp) {
                if (!inp.value.trim()) allLotsFilled = false;
            });
            if (!hasSelection) { alert('Please select at least one item.'); return; }
            if (!allLotsFilled) { alert('Please enter a lot number for every row.'); return; }
            if (!hasQuantity) { alert('Please enter an add quantity for at least one item.'); return; }
            var allShiftsFilled = true;
            document.querySelectorAll('#bulkItemsContainer select[name="shift[]"]').forEach(function(sel) {
                if (!sel.value) allShiftsFilled = false;
            });
            if (!allShiftsFilled) { alert('Please select a shift for every row.'); return; }
        } else {
            let hasLot = true;
            let hasQty = false;
            let hasShift = true;
            document.querySelectorAll('#singleLotContainer input[name="lot_number[]"]').forEach(function(inp) {
                if (!inp.value.trim()) hasLot = false;
            });
            document.querySelectorAll('#singleLotContainer input[name="added_quantity[]"]').forEach(function(inp) {
                if (inp.value && parseInt(inp.value) > 0) hasQty = true;
            });
            document.querySelectorAll('#singleLotContainer select[name="shift[]"]').forEach(function(sel) {
                if (!sel.value) hasShift = false;
            });
            if (!hasLot) { alert('Please enter a lot number for every row.'); return; }
            if (!hasQty) { alert('Please enter an add quantity.'); return; }
            if (!hasShift) { alert('Please select a shift for every row.'); return; }
        }

        // Build preview
        var poNumber = document.getElementById('updatePONumber').textContent;
        var customer = document.getElementById('updateCustomerName').textContent;
        document.getElementById('prevUPONumber').textContent = poNumber;
        document.getElementById('prevUCustomer').textContent = customer;

        var items = window._currentPOItems || [];

        if (!bulkGroup.classList.contains('d-none')) {
            // Bulk mode
            var selectedItems = [];
            document.querySelectorAll('.bulk-item-row').forEach(function(row) {
                var sel = row.querySelector('.bulk-item-select');
                if (sel && sel.value && sel.options[sel.selectedIndex]) {
                    var name = sel.options[sel.selectedIndex].textContent.trim();
                    if (selectedItems.indexOf(name) === -1) selectedItems.push(name);
                }
            });
            document.getElementById('prevUItem').textContent = selectedItems.length === 1 ? selectedItems[0] : (selectedItems.length > 1 ? selectedItems.join(', ') : '-');

            // Existing lots: only for selected items per row
            var existingHtml = '<table class="table table-sm table-bordered mb-0"><thead><tr><th>Item</th><th>Lot No.</th><th>Produced</th></tr></thead><tbody>';
            var hasExisting = false;

            // Build a map of poi_id -> item for quick lookup
            var itemMap = {};
            items.forEach(function(item) { itemMap[item.poi_id] = item; });

            // Collect existing lots per selected item
            document.querySelectorAll('.bulk-item-row').forEach(function(row) {
                var sel = row.querySelector('.bulk-item-select');
                var poiId = sel ? sel.value : '';
                if (!poiId) return;
                var item = itemMap[poiId];
                if (!item) return;
                var lots = item.lots || [];
                lots.forEach(function(lot) {
                    hasExisting = true;
                    existingHtml += '<tr><td>' + (item.item_description || '-') + '</td><td>' + (lot.lot_number || '-') + '</td><td>' + (lot.quantity_produced || 0) + '</td></tr>';
                });
            });
            if (!hasExisting) {
                existingHtml += '<tr><td colspan="3" class="text-muted text-center">No lots recorded for selected items</td></tr>';
            }
            existingHtml += '</tbody></table>';
            document.getElementById('prevUExistingLots').innerHTML = existingHtml;

            // Updating/adding lots
            var lotsHtml = '<table class="table table-sm table-bordered mb-0"><thead><tr><th>Item</th><th>Lot No.</th><th>Qty</th><th>Shift</th><th>Status</th></tr></thead><tbody>';
            document.querySelectorAll('.bulk-item-row').forEach(function(row) {
                var sel = row.querySelector('.bulk-item-select');
                var poiId = sel ? sel.value : '';
                var itemText = sel && sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].textContent.trim() : '-';
                var qty = parseInt(row.querySelector('.bulk-qty').value) || 0;
                var lotInput = row.querySelector('input[name="lot_number[]"]');
                var lot = lotInput ? lotInput.value.trim() : '';
                var entry = row.closest('.lot-entry');
                var shift = entry ? (entry.querySelector('select[name="shift[]"]').value || '-') : '-';
                var rejectStatus = entry ? (entry.querySelector('select[name="reject_status[]"]').value || '-') : '-';
                if (lot && qty > 0) {
                    // Check if lot exists for THIS item
                    var item = itemMap[poiId];
                    var itemLots = item ? (item.lots || []).map(function(l) { return l.lot_number; }) : [];
                    var isNew = itemLots.indexOf(lot) === -1;
                    var badge = isNew
                        ? '<span class="badge bg-success">NEW</span>'
                        : '<span class="badge bg-warning text-dark">UPDATE</span>';
                    lotsHtml += '<tr><td>' + itemText + '</td><td>' + lot + '</td><td>' + qty + '</td><td>' + shift + '</td><td>' + badge + ' ' + rejectStatus + '</td></tr>';
                }
            });
            lotsHtml += '</tbody></table>';
            document.getElementById('prevULots').innerHTML = lotsHtml;

        } else {
            // Single-item mode
            var currentPoiId = document.getElementById('updatePoiIdInput').value;
            var currentItem = null;
            items.forEach(function(item) {
                if (String(item.poi_id) === String(currentPoiId)) currentItem = item;
            });

            var itemDesc = currentItem ? (currentItem.item_description || '-') : '-';
            document.getElementById('prevUItem').textContent = itemDesc;

            // Existing lots: only for this item
            var existingHtml = '<table class="table table-sm table-bordered mb-0"><thead><tr><th>Lot No.</th><th>Produced</th></tr></thead><tbody>';
            var existingLotNumbers = [];
            if (currentItem && currentItem.lots) {
                currentItem.lots.forEach(function(lot) {
                    existingLotNumbers.push(lot.lot_number);
                    existingHtml += '<tr><td>' + (lot.lot_number || '-') + '</td><td>' + (lot.quantity_produced || 0) + '</td></tr>';
                });
            }
            if (existingLotNumbers.length === 0) {
                existingHtml += '<tr><td colspan="2" class="text-muted text-center">No lots recorded yet</td></tr>';
            }
            existingHtml += '</tbody></table>';
            document.getElementById('prevUExistingLots').innerHTML = existingHtml;

            // Updating/adding lots
            var lotsHtml = '<table class="table table-sm table-bordered mb-0"><thead><tr><th>Lot No.</th><th>Qty</th><th>Shift</th><th>Status</th></tr></thead><tbody>';
            var totalAdd = 0;
            document.querySelectorAll('#singleLotContainer .lot-entry').forEach(function(entry) {
                var row = entry.querySelector('.single-lot-row');
                var lot = row.querySelector('input[name="lot_number[]"]').value.trim();
                var qty = parseInt(row.querySelector('input[name="added_quantity[]"]').value) || 0;
                var shift = entry.querySelector('select[name="shift[]"]').value || '-';
                var rejectStatus = entry.querySelector('select[name="reject_status[]"]').value || '-';
                if (lot && qty > 0) {
                    var isNew = existingLotNumbers.indexOf(lot) === -1;
                    var badge = isNew
                        ? '<span class="badge bg-success">NEW</span>'
                        : '<span class="badge bg-warning text-dark">UPDATE</span>';
                    lotsHtml += '<tr><td>' + lot + '</td><td>' + qty + '</td><td>' + shift + '</td><td>' + badge + ' ' + rejectStatus + '</td></tr>';
                    totalAdd += qty;
                }
            });
            lotsHtml += '</tbody></table>';
            document.getElementById('prevULots').innerHTML = lotsHtml;
        }

        new bootstrap.Modal(document.getElementById('updatePreviewModal')).show();
    });

    window._updateFormConfirmed = false;

    document.getElementById('confirmUpdateBtn').addEventListener('click', function() {
        bootstrap.Modal.getInstance(document.getElementById('updatePreviewModal')).hide();
        window._updateFormConfirmed = true;
        document.getElementById('updatePOForm').submit();
    });

    document.getElementById('updatePOModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('updatePoiIdInput').disabled = false;
        window._currentBulkItems = [];
        window._currentSingleItem = null;
        window._updateFormConfirmed = false;
        window._nextStsBase = 1;

        var form = document.getElementById('updatePOForm');
        if (form) form.reset();

        var lotContainer = document.getElementById('singleLotContainer');
        if (lotContainer) {
            var entries = lotContainer.querySelectorAll('.lot-entry');
            entries.forEach(function(entry, i) {
                var badge = entry.querySelector('.sts-ref-badge');
                if (badge) badge.remove();
                if (i === 0) {
                    entry.querySelectorAll('input, select').forEach(function(el) {
                        el.value = '';
                    });
                    var removeBtn = entry.querySelector('.remove-single-lot');
                    if (removeBtn) removeBtn.style.display = 'none';
                } else {
                    entry.remove();
                }
            });
        }

        var bulkContainer = document.getElementById('bulkItemsContainer');
        if (bulkContainer) bulkContainer.innerHTML = '';

        var itemNameRow = document.getElementById('updateItemNameRow');
        if (itemNameRow) itemNameRow.style.display = 'none';
    });
});

var _searchTimer;
document.getElementById('searchPO').addEventListener('input', function() {
    clearTimeout(_searchTimer);
    var form = this.closest('form');
    _searchTimer = setTimeout(function() { form.submit(); }, 500);
});

(function() {
    var s = document.getElementById('searchPO');
    if (s && s.value) { s.focus(); s.setSelectionRange(s.value.length, s.value.length); }
})();

function populateFilters() {
    const customers = new Set();
    const items = new Set();
    document.querySelectorAll('#poTableBody tr').forEach(row => {
        if (row.querySelector('td[colspan]')) return;
        const cust = row.cells[2] ? row.cells[2].textContent.trim() : '';
        if (cust) customers.add(cust);
        const itemCell = row.cells[3];
        if (itemCell) {
            const divs = itemCell.querySelectorAll('div');
            if (divs.length > 0) {
                divs.forEach(d => {
                    const t = d.textContent.trim();
                    if (t && t !== '-') items.add(t);
                });
            } else {
                itemCell.querySelectorAll('small').forEach(s => {
                    const t = s.textContent.trim();
                    if (t && t !== '-') items.add(t);
                });
            }
        }
    });
    const custSel = document.getElementById('filterCustomer');
    customers.forEach(c => { const o = document.createElement('option'); o.value = c; o.textContent = c; custSel.appendChild(o); });
    const itemSel = document.getElementById('filterItem');
    items.forEach(i => { const o = document.createElement('option'); o.value = i; o.textContent = i; itemSel.appendChild(o); });
}

function applyFilters() {
    const custFilter = document.getElementById('filterCustomer').value.toLowerCase();
    const itemFilter = document.getElementById('filterItem').value.toLowerCase();
    const dateFilter = document.getElementById('filterDate').value;
    document.querySelectorAll('#poTableBody tr').forEach(row => {
        if (row.querySelector('td[colspan]')) { row.style.display = ''; return; }
        const cust = row.cells[2] ? row.cells[2].textContent.trim().toLowerCase() : '';
        const itemText = row.cells[3] ? row.cells[3].textContent.trim().toLowerCase() : '';
        const poDate = row.cells[1] ? row.cells[1].textContent.trim() : '';
        let show = true;
        if (custFilter && !cust.includes(custFilter)) show = false;
        if (itemFilter && !itemText.includes(itemFilter)) show = false;
        if (dateFilter && poDate !== dateFilter) show = false;
        row.style.display = show ? '' : 'none';
    });
}

document.getElementById('filterCustomer').addEventListener('change', applyFilters);
document.getElementById('filterItem').addEventListener('change', applyFilters);
document.getElementById('filterDate').addEventListener('change', applyFilters);
document.getElementById('clearFilters').addEventListener('click', function() {
    document.getElementById('filterCustomer').value = '';
    document.getElementById('filterItem').value = '';
    document.getElementById('filterDate').value = '';
    document.getElementById('searchPO').value = '';
    var form = document.querySelector('#searchPO').closest('form');
    if (form) form.submit();
    else applyFilters();
});

document.addEventListener('DOMContentLoaded', populateFilters);

document.querySelectorAll('.sortable').forEach(th => {
    th.addEventListener('click', function() {
        const table = this.closest('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const col = this.cellIndex;
        const asc = !this.classList.contains('asc');
        
        table.querySelectorAll('.sortable').forEach(h => {
            h.classList.remove('asc', 'desc');
            h.querySelector('i').className = 'bi bi-chevron-expand';
        });
        
        this.classList.add(asc ? 'asc' : 'desc');
        this.querySelector('i').className = asc ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
        
        rows.sort((a, b) => {
            let aVal = a.cells[col].textContent.trim();
            let bVal = b.cells[col].textContent.trim();
            if (!isNaN(aVal) && !isNaN(bVal)) {
                aVal = parseFloat(aVal);
                bVal = parseFloat(bVal);
            }
            return asc ? aVal.localeCompare(bVal, undefined, {numeric: true}) : bVal.localeCompare(aVal, undefined, {numeric: true});
        });
        
        rows.forEach(row => tbody.appendChild(row));
    });
});
</script>