<div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card p-3 h-100">
                <h6 class="text-muted">Total Customer PO</h6>
                <h3><?= count($purchase_orders ?? []) ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-3 h-100">
                <h6 class="text-muted">In Progress</h6>
                <h3><?= count(array_filter($purchase_orders ?? [], function($po) { return ($po['produced_quantity'] ?? 0) < ($po['total_quantity'] ?? 0); })) ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card p-3 h-100">
                <h6 class="text-muted">Completed</h6>
                <h3><?= count(array_filter($purchase_orders ?? [], function($po) { return ($po['produced_quantity'] ?? 0) >= ($po['total_quantity'] ?? 0); })) ?></h3>
            </div>
        </div>
    </div>
    
    <div class="card data-card mb-4">
        <div class="card-header d-flex justify-content-between">
            <span><i class="bi bi-cart3 me-2"></i>Open Purchase Order</span>
            <a href="?controller=production&action=purchaseOrders" class="btn btn-primary btn-sm">View All</a>
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
<tr><td colspan="7" class="text-center text-muted py-3">No customer PO yet</td></tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
