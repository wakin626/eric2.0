<div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card p-3 h-100">
                <h6 class="text-muted">Total Customers</h6>
                <h3><?= count($customers ?? []) ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-3 h-100">
                <h6 class="text-muted">Total Items</h6>
                <h3><?= count($items ?? []) ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-3 h-100">
                <h6 class="text-muted">Total Customer PO</h6>
                <h3><?= $allPOCount ?? 0 ?></h3>
            </div>
        </div>
    </div>
    
    <div class="card data-card mb-4">
        <div class="card-header d-flex justify-content-between">
            <span><i class="bi bi-cart3 me-2"></i>Recent Customer PO</span>
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
                        <th>Production Progress</th>
                        <th>Delivered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($purchase_orders ?? [], 0, 5) as $po):
                        $items = $po_items_map[$po['po_id']] ?? [];
                    ?>
                    <tr>
                        <td><strong class="text-primary"><?= $po['customer_po_number'] ?></strong></td>
                        <td><?= date('Y-m-d', strtotime($po['customer_po_date'])) ?></td>
                        <td><?= htmlspecialchars($po['customer_name'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($items)): ?>
                                <?php foreach ($items as $idx => $item): ?>
                                    <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
                                    <small><?= htmlspecialchars($item['item_description'] ?? '-') ?></small>
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
                                ?>
                                    <?= $idx > 0 ? '<hr class="my-1 border-secondary">' : '' ?>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 12px; width: 50px;">
                                            <div class="progress-bar <?= $itemPercent >= 100 ? 'bg-success' : 'bg-warning' ?>" style="width: <?= $itemPercent ?>%"></div>
                                        </div>
                                        <small class="text-muted text-nowrap"><?= $itemProduced ?>/<?= $qty ?></small>
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
                                    <small class="text-muted"><?= $itemDelivered ?>/<?= $itemQty ?></small>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($purchase_orders)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">No customer PO yet</td>
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
                    <div class="mb-3"><label class="form-label">Address</label><textarea name="customer_address" class="form-control"></textarea></div>
                    <div class="mb-3"><label class="form-label">TIN</label><input type="text" name="customer_tin" class="form-control"></div>
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
                    <div class="mb-3"><label class="form-label">UOM *</label><select name="item_uom" class="form-select" required><option value="">Select</option><option>PCS</option><option>PCKS</option><option>CS</option></select></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>