<h4><i class="bi bi-graph-up me-2"></i>PO Summary Report</h4>

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

$baseUrl = '?controller=qa&action=reports';
if ($filterSearch !== '') $baseUrl .= '&search=' . urlencode($filterSearch);
if ($filterPo !== '') $baseUrl .= '&po_filter=' . urlencode($filterPo);
if ($filterItem !== '') $baseUrl .= '&item_filter=' . urlencode($filterItem);
if ($filterStatus !== '') $baseUrl .= '&status_filter=' . urlencode($filterStatus);
?>

<div class="card data-card" id="po-item-summary">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0"><i class="bi bi-box-seam me-2"></i>PO Item Summary</h6>
        <div class="d-flex gap-2 align-items-center flex-wrap">
        <form method="GET" class="d-flex gap-2 flex-wrap" id="poFilterForm">
            <input type="hidden" name="controller" value="qa">
            <input type="hidden" name="action" value="reports">
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
});
</script>
