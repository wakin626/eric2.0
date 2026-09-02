<h4><i class="bi bi-graph-up me-2"></i>Warehouse Reports</h4>

<!-- Customer Filter & Week Navigation -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <input type="hidden" name="controller" value="admin">
            <input type="hidden" name="action" value="reports">
            <input type="hidden" name="week_offset" value="<?= $weekOffset ?? 0 ?>">
            <select name="customer_id" class="form-select form-select-sm" style="width:220px" onchange="this.form.submit()">
                <option value="">All Customers</option>
                <?php foreach (($customers ?? []) as $c): ?>
                    <option value="<?= $c['customer_id'] ?>" <?= ($selectedCustomer ?? '') == $c['customer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['customer_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="?controller=admin&action=reports<?= ($selectedCustomer ?? '') ? '&customer_id=' . urlencode($selectedCustomer) : '' ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>Clear</a>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <?php
        $offset = $weekOffset ?? 0;
        $weekFrom = ($offset * 12) + 1;
        $weekTo = $weekFrom + 11;
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $firstYw = intval($startYearWeek ?? 0);
        if ($firstYw > 0) {
            $lastYw = $firstYw;
            $yw = $firstYw;
            for ($i = 0; $i < 11; $i++) {
                $y = floor($yw / 100);
                $w = $yw % 100 + 1;
                if ($w > 52) { $y++; $w = 1; }
                $yw = $y * 100 + $w;
                $lastYw = $yw;
            }
            $firstDate = new DateTime();
            $firstDate->setISODate(floor($firstYw / 100), $firstYw % 100, 1);
            $lastDate = new DateTime();
            $lastDate->setISODate(floor($lastYw / 100), $lastYw % 100, 7);
            $dateRange = $monthNames[intval($firstDate->format('m')) - 1] . ' ' . $firstDate->format('d') . ' - ' . $monthNames[intval($lastDate->format('m')) - 1] . ' ' . $lastDate->format('d') . ', ' . $lastDate->format('Y');
        } else {
            $dateRange = '';
        }
        ?>
        <?php
        $extraParams = '';
        if (!empty($_GET['po_filter'])) $extraParams .= '&po_filter=' . urlencode($_GET['po_filter']);
        if (!empty($_GET['item_filter'])) $extraParams .= '&item_filter=' . urlencode($_GET['item_filter']);
        if (!empty($_GET['status_filter'])) $extraParams .= '&status_filter=' . urlencode($_GET['status_filter']);
        if (!empty($_GET['lot_item_id'])) $extraParams .= '&lot_item_id=' . urlencode($_GET['lot_item_id']);
        ?>
        <?php if (!empty($hasMoreWeeks)): ?>
        <a href="?controller=admin&action=reports&week_offset=<?= $offset + 1 ?><?= ($selectedCustomer ?? '') ? '&customer_id=' . urlencode($selectedCustomer) : '' ?><?= $extraParams ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-chevron-left me-1"></i>Older Weeks
        </a>
        <?php endif; ?>
        <span class="text-muted small">Weeks <?= $weekFrom ?>–<?= $weekTo ?> (<?= $dateRange ?>)</span>
        <?php if ($offset > 0): ?>
            <a href="?controller=admin&action=reports&week_offset=<?= $offset - 1 ?><?= ($selectedCustomer ?? '') ? '&customer_id=' . urlencode($selectedCustomer) : '' ?><?= $extraParams ?>" class="btn btn-sm btn-outline-primary">
                Newer Weeks<i class="bi bi-chevron-right ms-1"></i>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Weekly Delivery Graph -->
<div class="card data-card" style="cursor: pointer;">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Weekly Deliveries (Weeks <?= $weekFrom ?>–<?= $weekTo ?> &mdash; <?= $dateRange ?>) <small class="text-muted">— Click a bar to see details</small></h6>
    </div>
    <div class="card-body" style="height: 400px;">
        <canvas id="weeklyDeliveryChart"></canvas>
    </div>
</div>

<!-- Delivery Detail Modal -->
<div class="modal fade" id="deliveryDetailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalTitle"><i class="bi bi-truck me-2"></i>Delivery Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="deliveryDetailTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>DR #</th>
                                <th>Customer</th>
                                <th>PO Number</th>
                                <th>Item</th>
                                <th>Cases</th>
                                <th>Total Qty</th>
                                <th>Delivered By</th>
                            </tr>
                        </thead>
                        <tbody id="deliveryDetailBody">
                        </tbody>
                    </table>
                </div>
                <div id="noDeliveriesMsg" class="text-center text-muted py-4 d-none">
                    <i class="bi bi-inbox fs-1"></i>
                    <p class="mt-2">No deliveries for this week.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- PO Item Summary Section -->
<?php
$poItems = $poItemSummary ?? [];
$totalItems = count($poItems);
$completedCount = 0;
$inProgressCount = 0;
$pendingCount = 0;
$poNumbers = [];
$itemNames = [];
foreach ($poItems as $item) {
    $delivered = intval($item['delivered_quantity']);
    $ordered = intval($item['po_qty']);
    if ($delivered >= $ordered) {
        $completedCount++;
    } elseif ($delivered > 0) {
        $inProgressCount++;
    } else {
        $pendingCount++;
    }
    $poNumbers[$item['customer_po_number']] = $item['customer_po_number'];
    $itemNames[$item['item_description']] = $item['item_description'];
}
asort($poNumbers);
asort($itemNames);
$filterPo = $_GET['po_filter'] ?? '';
$filterItem = $_GET['item_filter'] ?? '';
$filterStatus = $_GET['status_filter'] ?? '';
$filterSearch = $_GET['search'] ?? '';
$poPage = max(1, intval($_GET['po_page'] ?? 1));
$poPageSize = 10;

$filteredItems = $poItems;
if ($filterSearch !== '') {
    $searchLower = strtolower($filterSearch);
    $filteredItems = array_filter($filteredItems, fn($i) =>
        str_contains(strtolower($i['customer_name'] ?? ''), $searchLower)
        || str_contains(strtolower($i['customer_po_number'] ?? ''), $searchLower)
        || str_contains(strtolower($i['item_description'] ?? ''), $searchLower)
    );
}
if ($filterPo !== '') {
    $filteredItems = array_filter($filteredItems, fn($i) => strtolower($i['customer_po_number']) === $filterPo);
}
if ($filterItem !== '') {
    $filteredItems = array_filter($filteredItems, fn($i) => strtolower($i['item_description']) === $filterItem);
}
if ($filterStatus !== '') {
    $filteredItems = array_filter($filteredItems, function($i) use ($filterStatus) {
        $d = intval($i['delivered_quantity']);
        $q = intval($i['po_qty']);
        if ($filterStatus === 'completed') return $p >= $q && $d >= $q;
        if ($filterStatus === 'in-progress') return $d > 0 && $d < $q;
        if ($filterStatus === 'pending') return $d === 0;
        return true;
    });
}
$filteredItems = array_values($filteredItems);
$filteredTotal = count($filteredItems);
$filteredTotalPages = max(1, ceil($filteredTotal / $poPageSize));
if ($poPage > $filteredTotalPages) $poPage = $filteredTotalPages;
$poOffset = ($poPage - 1) * $poPageSize;
$pageItems = array_slice($filteredItems, $poOffset, $poPageSize);

$baseUrl = '?controller=admin&action=reports';
if ($weekOffset ?? 0) $baseUrl .= '&week_offset=' . intval($weekOffset);
if ($filterSearch !== '') $baseUrl .= '&search=' . urlencode($filterSearch);
if ($filterPo !== '') $baseUrl .= '&po_filter=' . urlencode($filterPo);
if ($filterItem !== '') $baseUrl .= '&item_filter=' . urlencode($filterItem);
if ($filterStatus !== '') $baseUrl .= '&status_filter=' . urlencode($filterStatus);
if (!empty($_GET['lot_item_id'])) $baseUrl .= '&lot_item_id=' . urlencode($_GET['lot_item_id']);
?>

<div class="card data-card mt-4" id="po-item-summary">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-box-seam me-2"></i>PO Item Summary</h6>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <?php
            $exportParams = http_build_query(array_filter([
                'controller' => 'admin',
                'action' => 'exportReports',
                'customer_id' => $selectedCustomer ?? '',
                'week_offset' => $weekOffset ?? 0,
                'search' => $filterSearch,
                'po_filter' => $filterPo,
                'item_filter' => $filterItem,
                'status_filter' => $filterStatus,
                'lot_item_id' => $_GET['lot_item_id'] ?? '',
            ]));
            ?>
            <a href="?<?= $exportParams ?>" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1"></i>Export Excel</a>
        <form method="GET" class="d-flex gap-2 flex-wrap" id="poFilterForm">
            <input type="hidden" name="controller" value="admin">
            <input type="hidden" name="action" value="reports">
            <?php if ($weekOffset ?? 0): ?>
                <input type="hidden" name="week_offset" value="<?= intval($weekOffset) ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['lot_item_id'])): ?><input type="hidden" name="lot_item_id" value="<?= htmlspecialchars($_GET['lot_item_id']) ?>"><?php endif; ?>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search customer, PO, item..." value="<?= htmlspecialchars($filterSearch) ?>" style="width: 220px;">
            <select name="po_filter" class="form-select form-select-sm d-none" id="poFilterSelect">
                <option value="">All PO Numbers</option>
                <?php foreach ($poNumbers as $po): ?>
                    <option value="<?= htmlspecialchars(strtolower($po)) ?>" <?= $filterPo === strtolower($po) ? 'selected' : '' ?>><?= htmlspecialchars($po) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="searchable-wrap" style="width:200px;">
                <input type="text" class="form-control form-control-sm searchable-input" placeholder="Search PO number..." autocomplete="off" id="poFilterInput">
                <i class="bi bi-chevron-down searchable-arrow"></i>
                <ul class="searchable-list" id="poFilterList"></ul>
            </div>
            <select name="item_filter" class="form-select form-select-sm d-none" id="itemFilterSelect">
                <option value="">All Items</option>
                <?php foreach ($itemNames as $itemName): ?>
                    <option value="<?= htmlspecialchars(strtolower($itemName)) ?>" <?= $filterItem === strtolower($itemName) ? 'selected' : '' ?>><?= htmlspecialchars($itemName) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="searchable-wrap" style="width:200px;">
                <input type="text" class="form-control form-control-sm searchable-input" placeholder="Search item..." autocomplete="off" id="itemFilterInput">
                <i class="bi bi-chevron-down searchable-arrow"></i>
                <ul class="searchable-list" id="itemFilterList"></ul>
            </div>
            <select name="status_filter" class="form-select form-select-sm" style="width: 160px;" onchange="document.getElementById('poFilterForm').submit()">
                <option value="">All Status</option>
                <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="in-progress" <?= $filterStatus === 'in-progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
            </select>
        </form>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card text-center border-primary">
                    <div class="card-body py-2">
                        <div class="text-primary fs-4 fw-bold"><?= $totalItems ?></div>
                        <small class="text-muted">Total Items</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-success">
                    <div class="card-body py-2">
                        <div class="text-success fs-4 fw-bold"><?= $completedCount ?></div>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-warning">
                    <div class="card-body py-2">
                        <div class="text-warning fs-4 fw-bold"><?= $inProgressCount ?></div>
                        <small class="text-muted">In Progress</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center border-secondary">
                    <div class="card-body py-2">
                        <div class="text-secondary fs-4 fw-bold"><?= $pendingCount ?></div>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="poItemSummaryTable">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>PO Number</th>
                        <th>Item Code</th>
                        <th>Item</th>
                        <th class="text-end">PO Qty</th>
                        <th class="text-end">Delivered</th>
                        <th class="text-end">Balance</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pageItems)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4"><i class="bi bi-inbox fs-4 d-block mb-2"></i>No PO items found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pageItems as $item):
                            $ordered = intval($item['po_qty']);
                            $delivered = intval($item['delivered_quantity']);
                            $balance = $ordered - $delivered;
                            $uomConv = intval($item['uom_conversion'] ?? 0);
                            if ($delivered >= $ordered) {
                                $status = 'Completed';
                                $badgeClass = 'bg-success';
                            } elseif ($delivered > 0) {
                                $status = 'In Progress';
                                $badgeClass = 'bg-warning text-dark';
                            } else {
                                $status = 'Pending';
                                $badgeClass = 'bg-secondary';
                            }
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($item['customer_name']) ?></td>
                                <td><?= htmlspecialchars($item['customer_po_number']) ?></td>
                                <td><?= htmlspecialchars($item['item_code'] ?? '') ?></td>
                                <td><?= htmlspecialchars($item['item_description']) ?></td>
                                <td class="text-end"><?= number_format($ordered) ?><?php if ($uomConv > 0): ?> <small class="text-muted">/ <?= number_format(intval($ordered / $uomConv)) ?> cs</small><?php endif; ?></td>
                                <td class="text-end"><?= number_format($delivered) ?><?php if ($uomConv > 0): ?> <small class="text-muted">/ <?= number_format(intval($delivered / $uomConv)) ?> cs</small><?php endif; ?></td>
                                <td class="text-end"><?= number_format($balance) ?><?php if ($uomConv > 0): ?> <small class="text-muted">/ <?= number_format(intval($balance / $uomConv)) ?> cs</small><?php endif; ?></td>
                                <td class="text-center"><span class="badge <?= $badgeClass ?>"><?= $status ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($filteredTotalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted small">Showing <?= $poOffset + 1 ?>–<?= min($poOffset + $poPageSize, $filteredTotal) ?> of <?= $filteredTotal ?> items</span>
            <div class="d-flex gap-1">
                <?php
                $pageBaseUrl = $baseUrl . '&po_page=';
                ?>
                <a href="<?= $pageBaseUrl ?>1#po-item-summary" class="btn btn-sm btn-outline-secondary <?= $poPage === 1 ? 'disabled' : '' ?>"><i class="bi bi-chevron-double-left"></i></a>
                <a href="<?= $pageBaseUrl . ($poPage - 1) ?>#po-item-summary" class="btn btn-sm btn-outline-secondary <?= $poPage === 1 ? 'disabled' : '' ?>"><i class="bi bi-chevron-left"></i></a>
                <?php
                $range = [];
                for ($i = max(1, $poPage - 2); $i <= min($filteredTotalPages, $poPage + 2); $i++) $range[] = $i;
                if ($range[0] > 1) array_unshift($range, '...');
                if (end($range) < $filteredTotalPages) $range[] = '...';
                foreach ($range as $p):
                    if ($p === '...'):
                ?>
                    <span class="btn btn-sm btn-outline-secondary disabled">...</span>
                <?php else: ?>
                    <a href="<?= $pageBaseUrl . $p ?>#po-item-summary" class="btn btn-sm <?= $p === $poPage ? 'btn-primary' : 'btn-outline-primary' ?>"><?= $p ?></a>
                <?php endif; endforeach; ?>
                <a href="<?= $pageBaseUrl . ($poPage + 1) ?>#po-item-summary" class="btn btn-sm btn-outline-secondary <?= $poPage === $filteredTotalPages ? 'disabled' : '' ?>"><i class="bi bi-chevron-right"></i></a>
                <a href="<?= $pageBaseUrl . $filteredTotalPages ?>#po-item-summary" class="btn btn-sm btn-outline-secondary <?= $poPage === $filteredTotalPages ? 'disabled' : '' ?>"><i class="bi bi-chevron-double-right"></i></a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$lotBaseUrl = '?controller=admin&action=reports';
if ($weekOffset ?? 0) $lotBaseUrl .= '&week_offset=' . intval($weekOffset);
if ($filterPo !== '') $lotBaseUrl .= '&po_filter=' . urlencode($filterPo);
if ($filterItem !== '') $lotBaseUrl .= '&item_filter=' . urlencode($filterItem);
if ($filterStatus !== '') $lotBaseUrl .= '&status_filter=' . urlencode($filterStatus);
$lotPage = max(1, intval($_GET['lot_page'] ?? 1));
$lotPageSize = 15;
$lotTotal = count($lotData);
$lotTotalPages = max(1, ceil($lotTotal / $lotPageSize));
if ($lotPage > $lotTotalPages) $lotPage = $lotTotalPages;
$lotOffset = ($lotPage - 1) * $lotPageSize;
$pageLotData = array_slice($lotData, $lotOffset, $lotPageSize);
?>

<div class="card data-card mt-4" id="stock-on-hand">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-box-seam me-2"></i>Stock on Hand</h6>
        <form method="GET" class="d-flex gap-2 align-items-center" id="lotFilterForm">
            <input type="hidden" name="controller" value="admin">
            <input type="hidden" name="action" value="reports">
            <?php if ($weekOffset ?? 0): ?>
                <input type="hidden" name="week_offset" value="<?= intval($weekOffset) ?>">
            <?php endif; ?>
            <?php if (!empty($_GET['po_filter'])): ?><input type="hidden" name="po_filter" value="<?= htmlspecialchars($_GET['po_filter']) ?>"><?php endif; ?>
            <?php if (!empty($_GET['item_filter'])): ?><input type="hidden" name="item_filter" value="<?= htmlspecialchars($_GET['item_filter']) ?>"><?php endif; ?>
            <?php if (!empty($_GET['status_filter'])): ?><input type="hidden" name="status_filter" value="<?= htmlspecialchars($_GET['status_filter']) ?>"><?php endif; ?>
            <label class="text-muted small mb-0">Select Item:</label>
            <select name="lot_item_id" class="form-select form-select-sm d-none" id="lotItemSelect">
                <option value="">-- Choose an item --</option>
                <?php foreach ($lotItems as $li): ?>
                    <option value="<?= $li['item_id'] ?>" <?= ($selectedLotItem ?? '') == $li['item_id'] ? 'selected' : '' ?>><?= htmlspecialchars($li['item_description']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="searchable-wrap" style="width:350px;">
                <input type="text" class="form-control form-control-sm searchable-input" placeholder="Type to search item..." autocomplete="off" id="lotItemInput">
                <i class="bi bi-chevron-down searchable-arrow"></i>
                <ul class="searchable-list" id="lotItemList"></ul>
            </div>
        </form>
    </div>
    <div class="card-body">
        <?php if (empty($lotData)): ?>
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                <p>No lots found.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>PO Number</th>
                            <th>Lot Number</th>
                            <th class="text-end">Stock on Hand</th>
                            <th class="text-end">Delivered</th>
                            <th>Expiration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pageLotData as $lot):
                            $stockOnHandPcs = max(0, $lot['quantity_produced'] - $lot['quantity_delivered'] + ($lot['quantity_backloaded'] ?? 0));
                            $conv = intval($lot['uom_conversion'] ?? 0);
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($lot['customer_name']) ?></td>
                                <td><?= htmlspecialchars($lot['customer_po_number']) ?></td>
                                <td><strong><?= htmlspecialchars($lot['lot_number']) ?></strong> <small class="text-muted"><?= htmlspecialchars($lot['item_description'] ?? $lot['item_code'] ?? '') ?></small></td>
                                <td class="text-end"><?php if ($conv > 0): ?><?= number_format(intval($stockOnHandPcs / $conv)) ?> cs<?php else: ?><?= number_format($stockOnHandPcs) ?> pcs<?php endif; ?></td>
                                <td class="text-end"><?php if ($conv > 0): ?><?= number_format(intval($lot['quantity_delivered'] / $conv)) ?> cs<?php else: ?><?= number_format($lot['quantity_delivered']) ?> pcs<?php endif; ?></td>
                                <td><?= $lot['lot_date'] ? date('M Y', strtotime($lot['lot_date'] . ' +3 years')) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($lotTotalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted small">Showing <?= $lotOffset + 1 ?>–<?= min($lotOffset + $lotPageSize, $lotTotal) ?> of <?= $lotTotal ?> lots</span>
                <div class="d-flex gap-1">
                    <?php $lotPageBase = $lotBaseUrl . ($selectedLotItem ? '&lot_item_id=' . urlencode($selectedLotItem) : '') . '&lot_page='; ?>
                    <a href="<?= $lotPageBase ?>1#stock-on-hand" class="btn btn-sm btn-outline-secondary <?= $lotPage === 1 ? 'disabled' : '' ?>"><i class="bi bi-chevron-double-left"></i></a>
                    <a href="<?= $lotPageBase . ($lotPage - 1) ?>#stock-on-hand" class="btn btn-sm btn-outline-secondary <?= $lotPage === 1 ? 'disabled' : '' ?>"><i class="bi bi-chevron-left"></i></a>
                    <?php
                    $lotRange = [];
                    for ($i = max(1, $lotPage - 2); $i <= min($lotTotalPages, $lotPage + 2); $i++) $lotRange[] = $i;
                    if ($lotRange[0] > 1) array_unshift($lotRange, '...');
                    if (end($lotRange) < $lotTotalPages) $lotRange[] = '...';
                    foreach ($lotRange as $lp):
                        if ($lp === '...'):
                    ?>
                        <span class="btn btn-sm btn-outline-secondary disabled">...</span>
                    <?php else: ?>
                        <a href="<?= $lotPageBase . $lp ?>#stock-on-hand" class="btn btn-sm <?= $lp === $lotPage ? 'btn-primary' : 'btn-outline-primary' ?>"><?= $lp ?></a>
                    <?php endif; endforeach; ?>
                    <a href="<?= $lotPageBase . ($lotPage + 1) ?>#stock-on-hand" class="btn btn-sm btn-outline-secondary <?= $lotPage === $lotTotalPages ? 'disabled' : '' ?>"><i class="bi bi-chevron-right"></i></a>
                    <a href="<?= $lotPageBase . $lotTotalPages ?>#stock-on-hand" class="btn btn-sm btn-outline-secondary <?= $lotPage === $lotTotalPages ? 'disabled' : '' ?>"><i class="bi bi-chevron-double-right"></i></a>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script src="public/js/chart.min.js"></script>
<script>
function initSearchable(inputId, listId, selectId, formSubmit, allLabel) {
    var input = document.getElementById(inputId);
    var list = document.getElementById(listId);
    var select = document.getElementById(selectId);
    if (!input || !list || !select) return;

    function rebuildList() {
        list.innerHTML = '';
        if (allLabel) {
            var allLi = document.createElement('li');
            allLi.textContent = allLabel;
            allLi.dataset.value = '';
            if (!select.value) allLi.classList.add('active');
            list.appendChild(allLi);
        }
        Array.from(select.options).forEach(function(opt) {
            if (!opt.value) return;
            var li = document.createElement('li');
            li.textContent = opt.textContent;
            li.dataset.value = opt.value;
            if (opt.value === select.value) li.classList.add('active');
            list.appendChild(li);
        });
    }

    rebuildList();

    input.value = select.options[select.selectedIndex] && select.value ? select.options[select.selectedIndex].textContent : (allLabel || '');

    input.addEventListener('focus', function() {
        this.value = '';
        rebuildList();
        list.classList.add('show');
    });

    input.addEventListener('input', function() {
        var term = this.value.toLowerCase();
        var found = false;
        list.querySelectorAll('li').forEach(function(li) {
            var match = li.textContent.toLowerCase().indexOf(term) > -1;
            li.style.display = match ? '' : 'none';
            if (match) found = true;
        });
        if (!found && term) {
            list.innerHTML = '<li class="no-results">No results found</li>';
            list.classList.add('show');
        } else if (!term) {
            rebuildList();
            list.classList.add('show');
        }
    });

    list.addEventListener('mousedown', function(e) {
        var li = e.target.closest('li');
        if (!li || li.classList.contains('no-results')) return;
        select.value = li.dataset.value;
        input.value = li.textContent;
        list.classList.remove('show');
        if (formSubmit) formSubmit();
    });

    input.addEventListener('blur', function() {
        setTimeout(function() {
            list.classList.remove('show');
            if (!select.value) input.value = allLabel || '';
        }, 150);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initSearchable('poFilterInput', 'poFilterList', 'poFilterSelect', function() {
        document.getElementById('poFilterForm').submit();
    }, 'All PO Numbers');
    initSearchable('itemFilterInput', 'itemFilterList', 'itemFilterSelect', function() {
        document.getElementById('poFilterForm').submit();
    }, 'All Items');
    initSearchable('lotItemInput', 'lotItemList', 'lotItemSelect', function() {
        var f = document.getElementById('lotFilterForm');
        var lotSel = document.getElementById('lotItemSelect');
        var p = new URLSearchParams(new FormData(f));
        p.set('controller', 'admin');
        p.set('action', 'reports');
        p.set('lot_item_id', lotSel.value);
        window.location = '?' + p.toString() + '#stock-on-hand';
    }, 'All Items');
    var rawStats = <?= json_encode(array_values($weeklyStats ?? [])) ?>;
    var weeklyDetails = <?= json_encode($weeklyDetails ?? []) ?>;

    var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    function getWeekDateRange(yearWeek) {
        var y = Math.floor(yearWeek / 100);
        var w = yearWeek % 100;
        var jan4 = new Date(y, 0, 4);
        var dayOffset = (w - 1) * 7 - jan4.getDay() + 1;
        var mon = new Date(y, 0, 4 + dayOffset);
        var sun = new Date(mon);
        sun.setDate(mon.getDate() + 6);
        return monthNames[mon.getMonth()] + ' ' + mon.getDate() + ' - ' + monthNames[sun.getMonth()] + ' ' + sun.getDate();
    }

    var chartLabels = [];
    var chartDeliveryData = [];
    var chartCasesData = [];
    var yearWeekKeys = [];
    var weekDates = [];

    var statsMap = {};
    var casesMap = {};
    rawStats.forEach(function(s) {
        statsMap[parseInt(s.year_week)] = parseInt(s.delivery_count);
        casesMap[parseInt(s.year_week)] = parseInt(s.total_cases);
    });

    function nextYearWeek(yw) {
        var y = Math.floor(yw / 100);
        var w = yw % 100;
        w++;
        if (w > 52) { y++; w = 1; }
        return y * 100 + w;
    }

    var firstYw = <?= $startYearWeek ?? 0 ?>;
    var weekFrom = <?= ($weekOffset ?? 0) * 12 + 1 ?>;
    for (var i = 0; i < 12; i++) {
        var yw = firstYw;
        for (var j = 0; j < i; j++) { yw = nextYearWeek(yw); }
        chartLabels.push('Week ' + (weekFrom + i));
        chartDeliveryData.push(statsMap[yw] || 0);
        chartCasesData.push(casesMap[yw] || 0);
        yearWeekKeys.push(String(yw));
        weekDates.push(getWeekDateRange(yw));
    }

    function showDeliveryDetails(dataIndex) {
        var yearWeek = yearWeekKeys[dataIndex];
        var deliveries = weeklyDetails[yearWeek] || [];
        var weekNum = weekFrom + dataIndex;
        var dateRange = weekDates[dataIndex];

        document.getElementById('detailModalTitle').innerHTML = '<i class="bi bi-truck me-2"></i>Week ' + weekNum + ' (' + dateRange + ') &mdash; ' + chartDeliveryData[dataIndex] + ' deliveries, ' + chartCasesData[dataIndex] + ' cases';

        var tbody = document.getElementById('deliveryDetailBody');
        var noMsg = document.getElementById('noDeliveriesMsg');

        if (deliveries.length === 0) {
            tbody.innerHTML = '';
            noMsg.classList.remove('d-none');
            tbody.closest('table').classList.add('d-none');
        } else {
            noMsg.classList.add('d-none');
            tbody.closest('table').classList.remove('d-none');
            var html = '';
            deliveries.forEach(function(d) {
                var date = new Date(d.delivery_date);
                var dateStr = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
                html += '<tr>';
                html += '<td>' + dateStr + '</td>';
                html += '<td>' + (d.dr_number || '-') + '</td>';
                html += '<td>' + (d.customer_name || '-') + '</td>';
                html += '<td>' + (d.customer_po_number || '-') + '</td>';
                html += '<td>' + (d.item_description || '-') + '</td>';
                html += '<td>' + d.cases_delivered + '</td>';
                html += '<td>' + d.delivery_quantity.toLocaleString() + ' ' + (d.item_uom || '') + '</td>';
                html += '<td>' + (d.delivered_by_name || '-') + '</td>';
                html += '</tr>';
            });
            tbody.innerHTML = html;
        }

        new bootstrap.Modal(document.getElementById('deliveryDetailModal')).show();
    }

    var ctx = document.getElementById('weeklyDeliveryChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Deliveries & Cases',
                data: chartDeliveryData,
                backgroundColor: 'rgba(74, 144, 217, 0.7)',
                borderColor: 'rgba(74, 144, 217, 1)',
                borderWidth: 1,
                borderRadius: 4,
                hoverBackgroundColor: 'rgba(74, 144, 217, 1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            onClick: function(evt, elements) {
                if (elements.length > 0) {
                    showDeliveryDetails(elements[0].index);
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: function(items) {
                            return items[0].label;
                        },
                        afterTitle: function(items) {
                            var idx = items[0].dataIndex;
                            var d = chartDeliveryData[idx];
                            var c = chartCasesData[idx];
                            return d + ' ' + (d === 1 ? 'delivery' : 'deliveries') + ' | ' + c + ' ' + (c === 1 ? 'case' : 'cases');
                        },
                        label: function() {
                            return '';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    title: {
                        display: true,
                        text: 'Number of Deliveries'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Week'
                    }
                }
            }
        }
    });
});
</script>
