<h4><i class="bi bi-truck me-2"></i>Delivered PO</h4>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2 flex-wrap">
        <select id="filterCustomer" class="form-select form-select-sm filter-select" style="width:180px">
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
        <input type="text" id="searchDelivered" class="form-control" placeholder="Search...">
    </div>
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Customer</th>
                    <th>DR Number</th>
                    <th>Item</th>
                    <th>Lot Number</th>
                    <th>Quantity</th>
                    <th>Delivery Date</th>
                    <th>Cases</th>
                    <th>Type</th>
                    <th>Remarks</th>
                    <th>Report / Edit</th>
                    <th>Delivered By</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="deliveryTableBody">
                <?php if (!empty($deliveries)): ?>
                <?php foreach ($deliveries as $d):
                    $lotItems = json_decode($d['lot_items'] ?? '[]', true);
                    $hasLotItems = is_array($lotItems) && count($lotItems) > 0;
                    $isActive = ($d['active_status'] ?? 1) == 1;
                ?>
                <tr class="<?= $isActive ? '' : 'text-decoration-line-through' ?>">
                    <td><strong class="text-primary"><?= htmlspecialchars($d['customer_po_number']) ?></strong></td>
                    <td><?= htmlspecialchars($d['customer_name'] ?? '-') ?></td>
                    <td>
                        <?= htmlspecialchars($d['dr_number'] ?? '') ?: '<span class="text-muted">-</span>' ?>
                        <?php if (!empty($d['old_dr_number'])): ?>
                            <br><small style="color:#e6a800;font-weight:bold;">(old: <?= htmlspecialchars($d['old_dr_number']) ?>)</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($hasLotItems): ?>
                            <?php foreach ($lotItems as $idx => $li): ?>
                                <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
                                <small><?= htmlspecialchars($li['item_description'] ?? '') ?></small>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <small><?= htmlspecialchars($d['item_code'] ?? '-') ?> - <?= htmlspecialchars($d['item_description'] ?? '') ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($hasLotItems): ?>
                            <?php foreach ($lotItems as $idx => $li): ?>
                                <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
                                <small class="text-muted"><?= htmlspecialchars($li['lot_number'] ?? '-') ?></small>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <small class="text-muted"><?= htmlspecialchars($d['lot_number'] ?? '-') ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $oldQtyMap = json_decode($d['old_quantity'] ?? '{}', true);
                        if (!is_array($oldQtyMap)) $oldQtyMap = [];
                        ?>
                        <?php if ($hasLotItems): ?>
                            <?php foreach ($lotItems as $idx => $li): ?>
                                <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
                                <?= $li['qty'] ?? 0 ?>
                                <?php if (isset($oldQtyMap[strval($li['lot_id'] ?? '')])): ?>
                                    <br><small style="color:#e6a800;font-weight:bold;">(old: <?= $oldQtyMap[strval($li['lot_id'])] ?>)</small>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?= $d['delivery_quantity'] ?? 0 ?>
                            <?php if (!empty($oldQtyMap)): ?>
                                <?php $firstOld = reset($oldQtyMap); ?>
                                <br><small style="color:#e6a800;font-weight:bold;">(old: <?= $firstOld ?>)</small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= date('Y-m-d', strtotime($d['delivery_date'])) ?></td>
                    <td>
                        <?php
                        if ($hasLotItems) {
                            $grouped = [];
                            foreach ($lotItems as $li) {
                                $key = $li['item_description'] ?? $li['item_code'] ?? 'Item';
                                if (!isset($grouped[$key])) $grouped[$key] = ['qty' => 0, 'conv' => null, 'uom' => ''];
                                $grouped[$key]['qty'] += $li['qty'] ?? 0;
                                if (!empty($li['uom_conversion'])) $grouped[$key]['conv'] = $li['uom_conversion'];
                                if (!empty($li['item_uom'])) $grouped[$key]['uom'] = $li['item_uom'];
                            }
                            $caseParts = [];
                            foreach ($grouped as $desc => $info) {
                                $c = $info['conv'];
                                $u = $info['uom'];
                                if ($c && $u !== 'CS') {
                                    $caseParts[] = htmlspecialchars($desc) . ': ' . floor($info['qty'] / $c) . ' CS';
                                }
                            }
                            echo !empty($caseParts) ? implode('<br>', $caseParts) : '<span class="text-muted">—</span>';
                        } else {
                            $conv = $d['uom_conversion'] ?? null;
                            $itemUom = $d['item_uom'] ?? '';
                            $desc = $d['item_description'] ?? '';
                            if ($conv && $itemUom !== 'CS') {
                                echo htmlspecialchars($desc) . ': ' . floor(($d['delivery_quantity'] ?? 0) / $conv) . ' CS';
                            } else {
                                echo '<span class="text-muted">—</span>';
                            }
                        }
                        ?>
                    </td>
                    <td>
                        <?php if (($d['production_type'] ?? 'normal') === 'advance'): ?>
                            <span class="badge bg-info">Advance</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Normal</span>
                        <?php endif; ?>
                    </td>
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
                            if ($rmType === 'edited') $rmStyle = 'color:#e6a800;font-weight:bold;';
                            elseif ($rmType === 'report') $rmStyle = 'color:red;font-weight:bold;';
                            else $rmStyle = '';
                            ?>
                            <span style="<?= $rmStyle ?>"><?= htmlspecialchars($d['report_remarks']) ?></span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($d['delivered_by_name'] ?? '-') ?></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-primary view-po-btn"
                            data-po-id="<?= $d['po_id'] ?>"
                            data-delivery-id="<?= $d['delivery_id'] ?>"
                            data-dr="<?= htmlspecialchars($d['dr_number'] ?? '') ?>"
                            data-lot-items="<?= htmlspecialchars($d['lot_items'] ?? '[]') ?>"
                            data-delivery-date="<?= date('Y-m-d', strtotime($d['delivery_date'])) ?>"
                            title="View PO Details">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning editDeliveryBtn"
                            data-delivery-id="<?= $d['delivery_id'] ?>"
                            data-dr="<?= htmlspecialchars($d['dr_number'] ?? '') ?>"
                            data-date="<?= date('Y-m-d', strtotime($d['delivery_date'])) ?>"
                            data-lot-items="<?= htmlspecialchars($d['lot_items'] ?? '[]') ?>"
                            data-po-id="<?= $d['po_id'] ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <a href="?controller=admin&action=deleteDelivery&id=<?= $d['delivery_id'] ?>"
                           class="btn btn-sm btn-outline-danger"
                           onclick="return confirm('Delete this delivery and roll back its quantities?')"
                           title="Delete delivery and undo its impact">
                            <i class="bi bi-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="12" class="text-center text-muted py-4">No delivery records found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Delivery Modal -->
<div class="modal fade" id="editDeliveryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Delivery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editDeliveryForm">
                <div class="modal-body">
                    <input type="hidden" name="delivery_id" id="editDeliveryId">
                    <input type="hidden" name="po_id" id="editPoId">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">DR Number</label>
                            <input type="text" name="dr_number" id="editDrNumber" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Delivery Date</label>
                            <input type="date" name="delivery_date" id="editDeliveryDate" class="form-control">
                        </div>
                    </div>
                    <div id="editLotItemsRow" style="display:none;">
                        <label class="form-label fw-bold">Lot Items — Edit Quantity</label>
                        <div class="border rounded p-2" style="background:#f8f9fa;">
                            <div class="row mb-1">
                                <div class="col-md-4"><small class="text-muted fw-bold">Item</small></div>
                                <div class="col-md-3"><small class="text-muted fw-bold">Lot Number</small></div>
                                <div class="col-md-3"><small class="text-muted fw-bold">Quantity</small></div>
                                <div class="col-md-2"><small class="text-muted fw-bold">Cases</small></div>
                            </div>
                            <div id="editLotItemsContainer"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View PO Details Modal -->
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
                                <th>Lot Number</th>
                                <th class="text-end">Delivered</th>
                                <th class="text-end">Cases</th>
                                <th>DR Number</th>
                            </tr>
                        </thead>
                        <tbody id="viewPOItems"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.view-po-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const poId = this.getAttribute('data-po-id');
            const drNumber = this.getAttribute('data-dr') || '-';
            const lotItemsRaw = this.getAttribute('data-lot-items') || '[]';
            const lotItemsRawArr = JSON.parse(lotItemsRaw);
            const mergedLI = {};
            lotItemsRawArr.forEach(function(item) {
                var key = (item.lot_number || '') + '||' + (item.item_code || '');
                if (mergedLI[key]) { mergedLI[key].qty += item.qty || 0; } else { mergedLI[key] = Object.assign({}, item); }
            });
            const lotItems = Object.values(mergedLI);
            const hasLotItems = Array.isArray(lotItems) && lotItems.length > 0;

            fetch('?controller=warehouse&action=getPODetails&id=' + poId)
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    const po = data.po;

                    document.getElementById('viewPONumber').textContent = po.customer_po_number || '-';
                    document.getElementById('viewCustomerCode').textContent = po.customer_code || '-';
                    document.getElementById('viewCustomerName').textContent = po.customer_name || '-';
                    document.getElementById('viewCustomerTin').textContent = po.customer_tin || '-';
                    document.getElementById('viewCustomerTerms').textContent = (po.customer_terms || 0) + ' days';

                    const tbody = document.getElementById('viewPOItems');
                    tbody.innerHTML = '';

                    if (hasLotItems) {
                        lotItems.forEach(function(li) {
                            var qty = li.qty || 0;
                            var conv = li.uom_conversion || null;
                            var uom = li.item_uom || '';
                            var cases = (conv && uom !== 'CS') ? Math.floor(qty / conv) : 0;

                            var row = '<tr>' +
                                '<td>' + (li.item_code || '-') + '</td>' +
                                '<td>' + (li.item_description || '-') + '</td>' +
                                '<td>' + (li.lot_number || '-') + '</td>' +
                                '<td class="text-end">' + qty + ' pcs</td>' +
                                '<td class="text-end">' + (cases > 0 ? cases + ' CS' : '---') + '</td>' +
                                '<td><span class="badge bg-secondary">' + drNumber + '</span></td>' +
                                '</tr>';
                            tbody.innerHTML += row;
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No lot items found for this delivery</td></tr>';
                    }

                    const modal = new bootstrap.Modal(document.getElementById('viewPOModal'));
                    modal.show();
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    alert('Failed to load PO details: ' + error.message);
                });
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    var customers = new Set();
    var items = new Set();
    var drNumbers = new Set();
    document.querySelectorAll('#deliveryTableBody tr').forEach(function(row) {
        if (row.querySelector('td[colspan]')) return;
        var cust = row.cells[1] ? row.cells[1].textContent.trim() : '';
        if (cust) customers.add(cust);
        var itemCell = row.cells[3];
        if (itemCell) {
            var divs = itemCell.querySelectorAll('div');
            if (divs.length > 0) {
                divs.forEach(function(d) {
                    var t = d.textContent.trim().split('(')[0].trim();
                    if (t && t !== '-') items.add(t);
                });
            } else {
                itemCell.querySelectorAll('small').forEach(function(s) {
                    var t = s.textContent.trim().split('(')[0].trim();
                    if (t && t !== '-') items.add(t);
                });
            }
        }
        var dr = row.cells[2] ? row.cells[2].textContent.trim() : '';
        if (dr && dr !== '-') drNumbers.add(dr);
    });
    var custSel = document.getElementById('filterCustomer');
    customers.forEach(function(c) { var o = document.createElement('option'); o.value = c; o.textContent = c; custSel.appendChild(o); });
    var itemSel = document.getElementById('filterItem');
    items.forEach(function(i) { var o = document.createElement('option'); o.value = i; o.textContent = i; itemSel.appendChild(o); });
    var drSel = document.getElementById('filterDR');
    drNumbers.forEach(function(d) { var o = document.createElement('option'); o.value = d; o.textContent = d; drSel.appendChild(o); });
});

function applyAdminFilters() {
    var custFilter = document.getElementById('filterCustomer').value.toLowerCase();
    var itemFilter = document.getElementById('filterItem').value.toLowerCase();
    var drFilter = document.getElementById('filterDR').value.toLowerCase();
    var dateFilter = document.getElementById('filterDate').value;
    var searchQuery = document.getElementById('searchDelivered').value.toLowerCase();
    document.querySelectorAll('#deliveryTableBody tr').forEach(function(row) {
        if (row.querySelector('td[colspan]')) { row.style.display = ''; return; }
        var cust = row.cells[1] ? row.cells[1].textContent.trim().toLowerCase() : '';
        var itemText = row.cells[3] ? row.cells[3].textContent.trim().toLowerCase() : '';
        var drText = row.cells[2] ? row.cells[2].textContent.trim().toLowerCase() : '';
        var deliveryDate = row.cells[6] ? row.cells[6].textContent.trim() : '';
        var rowText = row.textContent.toLowerCase();
        var show = true;
        if (custFilter && !cust.includes(custFilter)) show = false;
        if (itemFilter && !itemText.includes(itemFilter)) show = false;
        if (drFilter && !drText.includes(drFilter)) show = false;
        if (dateFilter && deliveryDate !== dateFilter) show = false;
        if (searchQuery && !rowText.includes(searchQuery)) show = false;
        row.style.display = show ? '' : 'none';
    });
}

document.getElementById('searchDelivered').addEventListener('keyup', applyAdminFilters);
document.getElementById('filterCustomer').addEventListener('change', applyAdminFilters);
document.getElementById('filterItem').addEventListener('change', applyAdminFilters);
document.getElementById('filterDR').addEventListener('change', applyAdminFilters);
document.getElementById('filterDate').addEventListener('change', applyAdminFilters);
document.getElementById('clearFilters').addEventListener('click', function() {
    document.getElementById('filterCustomer').value = '';
    document.getElementById('filterItem').value = '';
    document.getElementById('filterDR').value = '';
    document.getElementById('filterDate').value = '';
    document.getElementById('searchDelivered').value = '';
    applyAdminFilters();
});

document.querySelectorAll('.editDeliveryBtn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('editDeliveryId').value = this.dataset.deliveryId;
        document.getElementById('editDrNumber').value = this.dataset.dr || '';
        document.getElementById('editDeliveryDate').value = this.dataset.date || '';
        document.getElementById('editPoId').value = this.dataset.poId || '';

        var lotItemsRaw = JSON.parse(this.dataset.lotItems || '[]');
        var mergedED = {};
        lotItemsRaw.forEach(function(item) {
            var key = (item.lot_number || '') + '||' + (item.item_code || '');
            if (mergedED[key]) { mergedED[key].qty += item.qty || 0; } else { mergedED[key] = Object.assign({}, item); }
        });
        var lotItems = Object.values(mergedED);
        var container = document.getElementById('editLotItemsContainer');
        container.innerHTML = '';

        if (lotItems.length > 0) {
            document.getElementById('editLotItemsRow').style.display = 'block';
            lotItems.forEach(function(li, idx) {
                var conv = li.uom_conversion || null;
                var uom = li.item_uom || '';
                var cases = (conv && uom !== 'CS') ? Math.floor((li.qty || 0) / conv) : 0;

                var row = document.createElement('div');
                row.className = 'row mb-2 align-items-center';
                row.innerHTML = '<div class="col-md-4"><small class="fw-bold">' + (li.item_description || '-') + '</small></div>' +
                    '<div class="col-md-3"><small class="text-muted">' + (li.lot_number || '-') + '</small></div>' +
                    '<div class="col-md-3"><input type="number" class="form-control form-control-sm edit-lot-qty" ' +
                    'data-lot-id="' + li.lot_id + '" data-poi-id="' + li.poi_id + '" ' +
                    'data-old-qty="' + (li.qty || 0) + '" value="' + (li.qty || 0) + '" min="1"></div>' +
                    '<div class="col-md-2"><small class="text-muted">' + (cases > 0 ? cases + ' CS' : '') + '</small></div>';
                container.appendChild(row);
            });
        } else {
            document.getElementById('editLotItemsRow').style.display = 'none';
        }

        var modal = new bootstrap.Modal(document.getElementById('editDeliveryModal'));
        modal.show();
    });
});

document.getElementById('editDeliveryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new FormData(this);

    // Collect lot quantity changes
    var lotChanges = [];
    document.querySelectorAll('.edit-lot-qty').forEach(function(input) {
        var oldQty = parseInt(input.dataset.oldQty) || 0;
        var newQty = parseInt(input.value) || 0;
        if (newQty !== oldQty) {
            lotChanges.push({
                lot_id: input.dataset.lotId,
                poi_id: input.dataset.poiId,
                old_qty: oldQty,
                new_qty: newQty
            });
        }
    });
    formData.append('lot_changes', JSON.stringify(lotChanges));

    fetch('?controller=admin&action=updateDelivery', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editDeliveryModal')).hide();
            alert('Delivery updated successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to update delivery'));
        }
    })
    .catch(function(err) {
        alert('Error updating delivery: ' + err.message);
    });
});
</script>
