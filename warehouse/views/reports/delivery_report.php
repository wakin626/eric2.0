<div class="card data-card mb-4">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-end">
            <input type="hidden" name="controller" value="warehouse">
            <input type="hidden" name="action" value="deliveryReport">
            <div>
                <label class="form-label small text-muted">Search</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="DR#, SI#, PO#, Customer, Item..." value="<?= htmlspecialchars($filters['search']) ?>" style="width:280px">
            </div>
            <div>
                <label class="form-label small text-muted">Customer</label>
                <select name="customer_id" class="form-select form-select-sm" style="width:200px">
                    <option value="">All Customers</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['customer_id'] ?>" <?= $filters['customer_id'] == $c['customer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['customer_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label small text-muted">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from']) ?>">
            </div>
            <div>
                <label class="form-label small text-muted">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to']) ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
            <a href="?controller=warehouse&action=deliveryReport" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-circle me-1"></i>Clear</a>
        </form>
    </div>
</div>

<div class="card data-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-truck me-2"></i>Delivery Records</h6>
        <div class="d-flex gap-2 align-items-center">
            <?php
            $exportParams = http_build_query(array_filter([
                'controller' => 'warehouse',
                'action' => 'exportDeliveryReport',
                'search' => $filters['search'],
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
                'customer_id' => $filters['customer_id'],
            ]));
            ?>
            <a href="?<?= $exportParams ?>" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1"></i>Export Excel</a>
            <span class="badge bg-primary"><?= count($deliveries) ?> records</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>DR Number</th>
                    <th>SI Number</th>
                    <th>Customer</th>
                    <th>PO Number</th>
                    <th>Item</th>
                    <th>Item Code</th>
                    <th>Lot Number</th>
                    <th>Quantity</th>
                    <th>Cases</th>
                    <th>Plate No.</th>
                    <th>Vehicle</th>
                    <th>Logistic Provider</th>
                    <th>Type</th>
                    <th>Delivery Date</th>
                    <th>Delivered By</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($deliveries)): ?>
                    <tr>
                        <td colspan="17" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No delivery records found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $rowNum = 0; ?>
                    <?php foreach ($deliveries as $d): ?>
                        <?php
                        $lotItems = json_decode($d['lot_items'] ?? '[]', true);
                        if (!is_array($lotItems) || count($lotItems) === 0) {
                            $lotItems = [[
                                'item_description' => $d['item_description'] ?? 'Unknown',
                                'lot_number' => $d['lot_number'] ?? '—',
                                'qty' => $d['delivery_quantity'] ?? 0,
                                'actual_uom_conversion' => $d['actual_uom_conversion'] ?? null,
                                'uom_conversion' => $d['uom_conversion'] ?? null,
                                'item_uom' => $d['item_uom'] ?? ''
                            ]];
                        }
                        $totalLotRows = count($lotItems);
                        $rowNum++;

                        $grouped = [];
                        foreach ($lotItems as $li) {
                            $key = $li['item_description'] ?? $li['item_code'] ?? 'Unknown';
                            if (!isset($grouped[$key])) $grouped[$key] = ['code' => $li['item_code'] ?? '', 'items' => []];
                            $grouped[$key]['items'][] = $li;
                        }
                        ?>
                        <?php foreach ($grouped as $itemName => $group): ?>
                            <?php $items = $group['items']; ?>
                            <?php $itemRowCount = count($items); ?>
                            <?php foreach ($items as $idx => $li): ?>
                                <?php
                                $lotNo = htmlspecialchars($li['lot_number'] ?? '?');
                                $qty = intval($li['qty'] ?? 0);
                                $conv = $li['actual_uom_conversion'] ?? $li['uom_conversion'] ?? null;
                                $uom = $li['item_uom'] ?? '';
                                $cases = '';
                                if ($conv && $uom !== 'CS') {
                                    $cases = floor($qty / $conv) . ' CS';
                                }
                                ?>
                                <tr>
                                    <?php if ($itemName === array_key_first($grouped) && $idx === 0): ?>
                                        <td rowspan="<?= $totalLotRows ?>" class="text-muted align-middle"><?= $rowNum ?></td>
                                        <td rowspan="<?= $totalLotRows ?>" class="align-middle">
                                            <?php if (!empty($d['dr_number'])): ?>
                                                <span class="badge bg-success"><?= htmlspecialchars($d['dr_number']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td rowspan="<?= $totalLotRows ?>" class="align-middle">
                                            <?php if (!empty($d['si_number'])): ?>
                                                <span class="badge bg-info"><?= htmlspecialchars($d['si_number']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td rowspan="<?= $totalLotRows ?>" class="align-middle"><?= htmlspecialchars($d['customer_name'] ?? '') ?></td>
                                        <td rowspan="<?= $totalLotRows ?>" class="align-middle"><?= htmlspecialchars($d['customer_po_number'] ?? '') ?></td>
                                    <?php endif; ?>
                                    <?php if ($idx === 0): ?>
                                        <td rowspan="<?= $itemRowCount ?>" class="align-middle fw-semibold"><?= htmlspecialchars($itemName) ?></td>
                                        <td rowspan="<?= $itemRowCount ?>" class="align-middle"><?= htmlspecialchars($group['code']) ?></td>
                                    <?php endif; ?>
                                    <td><?= $lotNo ?></td>
                                    <td class="fw-bold"><?= number_format($qty) ?></td>
                                    <td><?= $cases ?></td>
                                    <?php if ($itemName === array_key_first($grouped) && $idx === 0): ?>
                                        <td rowspan="<?= $totalLotRows ?>" class="align-middle"><?= htmlspecialchars($d['plate_number'] ?? '—') ?></td>
                                        <td rowspan="<?= $totalLotRows ?>" class="align-middle"><?= htmlspecialchars($d['vehicle_type'] ?? '—') ?></td>
                                        <td rowspan="<?= $totalLotRows ?>" class="align-middle"><?= htmlspecialchars($d['logistic_provider'] ?? '—') ?></td>
                                        <td rowspan="<?= $totalLotRows ?>" class="align-middle"><span class="badge bg-secondary"><?= htmlspecialchars($d['production_type'] ?? '') ?></span></td>
                                        <td rowspan="<?= $totalLotRows ?>" class="align-middle"><?= htmlspecialchars($d['delivery_date'] ?? '') ?></td>
                                        <td rowspan="<?= $totalLotRows ?>" class="align-middle"><?= htmlspecialchars($d['delivered_by_name'] ?? '') ?></td>
                                        <td rowspan="<?= $totalLotRows ?>" class="align-middle" style="max-width:200px">
                                            <?php if (!empty($d['report_remarks'])): ?>
                                                <span class="text-danger small" title="<?= htmlspecialchars($d['report_remarks']) ?>">
                                                    <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars(mb_strimwidth($d['report_remarks'], 0, 30, '...')) ?>
                                                </span>
                                            <?php elseif (!empty($d['remarks'])): ?>
                                                <span class="text-muted small"><?= htmlspecialchars(mb_strimwidth($d['remarks'], 0, 30, '...')) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
