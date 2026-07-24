<div class="row g-3 mb-4">
        <div class="col-md-3">
            <a href="?controller=admin&action=customers" class="text-decoration-none">
                <div class="card stat-card stat-card-blue h-100" style="cursor:pointer;">
                    <div class="card-body">
                        <div class="stat-icon"><i class="bi bi-people"></i></div>
                        <div>
                            <h6>Total Customers</h6>
                            <h3><?= count($customers ?? []) ?></h3>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="?controller=admin&action=items" class="text-decoration-none">
                <div class="card stat-card stat-card-green h-100" style="cursor:pointer;">
                    <div class="card-body">
                        <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
                        <div>
                            <h6>Total Items</h6>
                            <h3><?= count($items ?? []) ?></h3>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="?controller=admin&action=purchaseOrders" class="text-decoration-none">
                <div class="card stat-card stat-card-orange h-100" style="cursor:pointer;">
                    <div class="card-body">
                        <div class="stat-icon"><i class="bi bi-cart3"></i></div>
                        <div>
                            <h6>Total Customer PO</h6>
                            <h3><?= $allPOCount ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <div class="card stat-card stat-card-purple h-100" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#reportModal">
                <div class="card-body">
                    <div class="stat-icon"><i class="bi bi-clipboard-data"></i></div>
                    <div>
                        <h6>Reports</h6>
                        <h3><?= ($deliveryReportsCount ?? 0) + ($reportsCount ?? 0) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card data-card mb-4">
        <div class="card-header d-flex justify-content-between">
            <span><i class="bi bi-truck me-2"></i>Recent Deliveries</span>
            <a href="?controller=admin&action=delivered" class="btn btn-primary btn-sm">View All</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Customer</th>
                        <th>Item / Lot</th>
                        <th>PO Qty</th>
                        <th>Delivered</th>
                        <th>Type</th>
                        <th>Delivery Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($deliveries ?? [], 0, 5) as $d):
                        $lotItems = json_decode($d['lot_items'] ?? '[]', true);
                        $hasLotItems = is_array($lotItems) && count($lotItems) > 0;
                        $poItems = $po_items_map[$d['po_id']] ?? [];
                        $poItemLookup = [];
                        foreach ($poItems as $pi) { $poItemLookup[$pi['item_code']] = $pi; }

                        $itemLines = [];
                        $poQtyLines = [];
                        $deliveredLines = [];

                        if ($hasLotItems) {
                            $liCount = count($lotItems);
                            foreach ($lotItems as $idx => $li) {
                                $liCode = $li['item_code'] ?? '';
                                $liQty = $li['qty'] ?? 0;
                                $liDesc = $li['item_description'] ?? $liCode;
                                $liLot = $li['lot_number'] ?? '';
                                $poItem = $poItemLookup[$liCode] ?? null;
                                $poQty = $poItem ? $poItem['quantity'] : 0;
                                $sep = $idx < $liCount - 1 ? ' border-bottom pb-2 mb-2' : '';
                                $itemLines[] = '<div class="' . $sep . '"><small>' . htmlspecialchars($liDesc) . '</small><br><small class="text-muted">' . htmlspecialchars($liLot) . '</small></div>';
                                $poQtyLines[] = '<div class="' . $sep . '">' . $poQty . '</div>';
                                $deliveredLines[] = '<div class="' . $sep . '">' . $liQty . '</div>';
                            }
                        } else {
                            $dItemQty = $d['item_quantity'] ?? 0;
                            $dDelivered = $d['delivery_quantity'] ?? 0;
                            $itemLines[] = '<small>' . htmlspecialchars(($d['item_code'] ?? '-') . ' - ' . ($d['item_description'] ?? '')) . '</small>';
                            $poQtyLines[] = $dItemQty;
                            $deliveredLines[] = $dDelivered;
                        }
                    ?>
                    <tr>
                        <td><strong class="text-primary"><?= $d['customer_po_number'] ?></strong></td>
                        <td><?= htmlspecialchars($d['customer_name'] ?? '-') ?></td>
                        <td><?= implode('', $itemLines) ?></td>
                        <td><?= implode('', $poQtyLines) ?></td>
                        <td><?= implode('', $deliveredLines) ?></td>
                        <td>
                            <?php if (($d['production_type'] ?? 'normal') === 'advance'): ?>
                                <span class="badge bg-info">Advance</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Normal</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('Y-m-d', strtotime($d['delivery_date'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($deliveries)): ?>
                    <tr><td colspan="7" class="text-center py-4"><div class="empty-state"><i class="bi bi-truck"></i><p>No deliveries yet</p></div></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card data-card mb-4">
        <div class="card-header d-flex justify-content-between">
            <span><i class="bi bi-cart3 me-2"></i>Open Purchase Order</span>
            <a href="?controller=admin&action=purchaseOrders" class="btn btn-primary btn-sm">View All</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>PO Date</th>
                        <th>Customer</th>
                        <th>Item</th>
                        <th>Produced PO QTY</th>
                        <th>Delivered PO QTY</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchase_orders ?? [] as $po):
                        $items = $po_items_map[$po['po_id']] ?? [];
                    ?>
                    <tr>
                        <td><strong class="text-primary">
                        <?php
                        $allNormalCr = [];
                        if (!empty($items) && ($po['production_type'] ?? 'normal') !== 'advance') {
                            foreach ($items as $item) {
                                $ncrRecords = ($normal_consumption_records ?? [])[$item['poi_id']] ?? [];
                                foreach ($ncrRecords as $ncr) {
                                    $allNormalCr[] = $ncr;
                                }
                            }
                        }
                        if (!empty($allNormalCr)):
                        ?><span style="opacity:0.75"><?= htmlspecialchars($allNormalCr[0]['advance_po_number']) ?></span>/<?php endif; ?><?= $po['customer_po_number'] ?>
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
                                    <div class="d-flex align-items-center" style="min-height: 20px;">
                                        <div class="progress flex-grow-1 me-2" style="height: 12px; width: 50px;">
                                            <div class="progress-bar <?= $isExcess ? 'bg-danger' : ($itemPercent >= 100 ? 'bg-success' : 'bg-warning') ?>" style="width: <?= min($itemPercent, 100) ?>%"></div>
</div>

<small class="text-muted text-nowrap"><?= $itemProduced ?>/<?= $qty ?> pcs</small>
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
                                ?>
                                    <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
                                    <?php $conv = $item['uom_conversion'] ?? null; ?>
                                    <small class="text-muted"><?= $itemDelivered ?>/<?= $itemQty ?> pcs, <?= $conv ? round($itemDelivered / $conv) . '/' . round($itemQty / $conv) . ' cs' : '—/—' ?></small>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($po['production_type'] ?? 'normal') === 'advance'): ?>
                                <span class="badge bg-info">Advance</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Normal</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($purchase_orders)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4"><div class="empty-state"><i class="bi bi-cart3"></i><p>No customer PO yet</p></div></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card data-card">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-people me-2"></i>Customers</span>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#customerModal">Add</button>
            </div>
            <div class="list-group list-group-flush">
                <a href="?controller=admin&action=customers" class="list-group-item list-group-item-action">View All</a>
                <button class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#customerModal">Add New Customer</button>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card data-card">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-box-seam me-2"></i>Items</span>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#itemModal">Add</button>
            </div>
            <div class="list-group list-group-flush">
                <a href="?controller=admin&action=items" class="list-group-item list-group-item-action">View All</a>
                <button class="list-group-item list-group-item-action" data-bs-toggle="modal" data-bs-target="#itemModal">Add New Item</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="customerModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Customer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="?controller=admin&action=customerCreate">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Customer Code *</label><input type="text" name="customer_code" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Customer Name *</label><input type="text" name="customer_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Delivery Address</label><textarea name="customer_address" class="form-control"></textarea></div>
                    <div class="mb-3"><label class="form-label">TIN</label><input type="text" name="customer_tin" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Terms (Days)</label><select name="customer_terms" class="form-select"><option value="15">15 days</option><option value="30">30 days</option><option value="60">60 days</option><option value="90">90 days</option><option value="120">120 days</option></select></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="itemModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="?controller=admin&action=itemCreate">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Item Code *</label><input type="text" name="item_code" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description *</label><input type="text" name="item_description" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">UOM</label><input type="text" class="form-control" value="PCS" readonly><input type="hidden" name="item_uom" value="PCS"></div>
                    <div class="mb-3"><label class="form-label">Cases Conversion</label><input type="number" name="uom_conversion" class="form-control" min="1" placeholder="e.g. 10 means 10 PCS = 1 CS"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clipboard-data me-2"></i>Reports</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php
                $deliveryReports = array_filter($deliveries ?? [], fn($d) => ($d['remarks_type'] ?? '') === 'report');
                $deliveryReports = array_slice(array_values($deliveryReports), 0, 10);
                ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="bi bi-truck me-2"></i>Delivery Reports <span class="badge bg-danger ms-1"><?= $deliveryReportsCount ?? 0 ?></span></h6>
                    <a href="?controller=admin&action=delivered&filter_reports=1" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="table-responsive mb-4">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Customer</th>
                                <th>Item</th>
                                <th>DR Number</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deliveryReports as $d):
                                $lotItems = json_decode($d['lot_items'] ?? '[]', true);
                                $itemDesc = '';
                                if (is_array($lotItems) && count($lotItems) > 0) {
                                    $itemDesc = $lotItems[0]['item_description'] ?? '';
                                } else {
                                    $itemDesc = $d['item_description'] ?? '-';
                                }
                            ?>
                            <tr>
                                <td><strong class="text-primary"><?= htmlspecialchars($d['customer_po_number'] ?? '-') ?></strong></td>
                                <td><?= htmlspecialchars($d['customer_name'] ?? '-') ?></td>
                                <td><small><?= htmlspecialchars($itemDesc) ?></small></td>
                                <td><small><?= htmlspecialchars($d['dr_number'] ?? '-') ?></small></td>
                                <td><small><?= date('Y-m-d', strtotime($d['delivery_date'])) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($deliveryReports)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No pending delivery reports</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="bi bi-flag me-2"></i>Lot Number Reports <span class="badge bg-warning text-dark ms-1"><?= $reportsCount ?? 0 ?></span></h6>
                    <a href="?controller=admin&action=productionHistory&filter_reports=1" class="btn btn-outline-primary btn-sm">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Customer</th>
                                <th>Item</th>
                                <th>Old Lot #</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pendingReports = array_filter($history ?? [], fn($h) => ($h['report_status'] ?? '') === 'pending');
                            $pendingReports = array_slice(array_values($pendingReports), 0, 10);
                            foreach ($pendingReports as $h):
                            ?>
                            <tr>
                                <td><strong class="text-primary"><?= htmlspecialchars($h['customer_po_number'] ?? '-') ?></strong></td>
                                <td><?= htmlspecialchars($h['customer_name'] ?? '-') ?></td>
                                <td><small><?= htmlspecialchars($h['item_description'] ?? '-') ?></small></td>
                                <td><small><?= htmlspecialchars($h['lot_number'] ?? '-') ?></small></td>
                                <td><small class="text-muted"><?= htmlspecialchars($h['report_reason'] ?? '-') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($pendingReports)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No pending lot number reports</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


