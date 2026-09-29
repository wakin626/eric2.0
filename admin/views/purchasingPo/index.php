<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-cart3 me-2"></i>Purchasing PO (View Only)</h4>
    <div class="d-flex gap-2 flex-wrap">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="controller" value="admin">
            <input type="hidden" name="action" value="purchasingPo">
            <select name="status" class="form-select form-select-sm filter-select" style="width:170px">
                <option value="">Any status (per tab)</option>
                <option value="all" <?= ($filters['status'] ?? '') === 'all' ? 'selected' : '' ?>>All Statuses</option>
                <option value="requested" <?= ($filters['status'] ?? '') === 'requested' ? 'selected' : '' ?>>Requested</option>
                <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="processed" <?= ($filters['status'] ?? '') === 'processed' ? 'selected' : '' ?>>Processed</option>
                <option value="partially_received" <?= ($filters['status'] ?? '') === 'partially_received' ? 'selected' : '' ?>>Partially Received</option>
                <option value="For Inspection" <?= ($filters['status'] ?? '') === 'For Inspection' ? 'selected' : '' ?>>For Inspection</option>
                <option value="received" <?= ($filters['status'] ?? '') === 'received' ? 'selected' : '' ?>>Received</option>
                <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search supplier/item..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>" style="width:220px">
            <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
            <a href="?controller=admin&action=purchasingPo" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
        </form>
    </div>
</div>

<div class="card data-card">
    <?php
    $activeTab = $filters['tab'] ?: 'all';
    $tabCounts = $tabCounts ?? [];
    $poTabs = [
        'all'       => 'All Orders',
        'requested' => 'Requisitions (Requested)',
        'active'    => 'Active POs (Pending)',
        'history'   => 'History (Received / Cancelled)',
    ];
    ?>
    <div class="card-body pb-0">
        <ul class="nav nav-pills flex-wrap gap-2" id="purchasingTabs">
            <?php foreach ($poTabs as $tabKey => $tabLabel):
                $tabHref = '?controller=admin&action=purchasingPo&tab=' . rawurlencode($tabKey);
                if (!empty($filters['search'])) {
                    $tabHref .= '&search=' . rawurlencode($filters['search']);
                }
            ?>
            <li class="nav-item">
                <a class="nav-link px-3 py-1 border rounded-pill <?= $activeTab === $tabKey ? 'active' : '' ?>"
                   href="<?= $tabHref ?>">
                    <?= $tabLabel ?> <span class="badge <?= $activeTab === $tabKey ? 'bg-white text-primary' : 'bg-secondary' ?>"><?= (int) ($tabCounts[$tabKey] ?? 0) ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Supplier</th>
                    <th>Item Code</th>
                    <th>Description</th>
                    <th class="text-end">Quantity</th>
                    <th class="text-end">Unit Cost</th>
                    <th>Order Date</th>
                    <th>Expected</th>
                    <th>PO REF / SOURCE</th>
                    <th>Status</th>
                    <th class="text-end">Received Qty</th>
                    <th>Received Date</th>
                    <th>Created By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="14" class="text-center text-muted py-4">No purchasing POs found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($orders as $index => $o): $qty = floatval($o['quantity']); ?>
                <tr>
                    <td title="Order #<?= $o['supplier_order_id'] ?>"><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($o['supplier_name']) ?></td>
                    <td><code><?= htmlspecialchars($o['item_code']) ?></code></td>
                    <td><?= htmlspecialchars($o['item_description']) ?></td>
                    <td class="text-end"><?= number_format($qty, $qty == floor($qty) ? 0 : 4) ?> <?= htmlspecialchars($o['item_uom'] ?? '') ?></td>
                    <td class="text-end"><?= number_format($o['unit_cost'], 2) ?></td>
                    <td><?= $o['order_date'] ? date('m/d/Y', strtotime($o['order_date'])) : '-' ?></td>
                    <td><?= $o['expected_date'] ? date('m/d/Y', strtotime($o['expected_date'])) : '-' ?></td>
                    <td>
                        <?= htmlspecialchars($o['po_ref_display']) ?>
                        <?php if (!empty($o['mrp_ref'])): ?>
                            <div class="mt-1"><span class="badge bg-secondary"><?= htmlspecialchars($o['mrp_ref']) ?></span></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($o['status'] === 'requested'): ?>
                            <span class="badge bg-info">Requested</span>
                        <?php elseif ($o['status'] === 'pending'): ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                        <?php elseif ($o['status'] === 'processed'): ?>
                            <span class="badge bg-primary">Processed</span>
                        <?php elseif ($o['status'] === 'partially_received'): ?>
                            <span class="badge bg-info text-dark">Partially Received</span>
                        <?php elseif ($o['status'] === 'For Inspection'): ?>
                            <span class="badge bg-warning text-dark">For Inspection</span>
                        <?php elseif ($o['status'] === 'received'): ?>
                            <span class="badge bg-success">Received</span>
                        <?php elseif ($o['status'] === 'cancelled'): ?>
                            <span class="badge bg-secondary">Cancelled</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= htmlspecialchars(ucfirst($o['status'])) ?: 'Unknown' ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?php
                        $recvQty = floatval($o['received_qty'] ?? 0);
                        echo $recvQty > 0
                            ? rtrim(rtrim(number_format($recvQty, 4), '0'), '.') . ' ' . htmlspecialchars($o['item_uom'] ?? '')
                            : '-';
                    ?></td>
                    <td><?= $o['received_date'] ? date('m/d/Y', strtotime($o['received_date'])) : '-' ?></td>
                    <td><?= htmlspecialchars($o['created_by_name'] ?? '') ?></td>
                    <td>
                        <?php if ($o['status'] === 'requested'): ?>
                            <span class="text-muted">-</span>
                        <?php elseif ($o['status'] === 'pending'): ?>
                            <span class="text-muted">-</span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
