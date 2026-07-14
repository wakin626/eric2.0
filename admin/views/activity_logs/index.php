<?php
$logController = $logController ?? 'admin';
$departmentLocked = $departmentLocked ?? false;
$hideDeptColumn = $hideDeptColumn ?? false;
$fieldLabels = [
    'produced_quantity' => 'Produced Quantity',
    'delivered_quantity' => 'Delivered Quantity',
    'delivery_quantity' => 'Delivery Quantity',
    'quantity' => 'Quantity',
    'status' => 'Status',
    'username' => 'Username',
    'po_number' => 'PO Number',
    'customer_name' => 'Customer Name',
    'item_name' => 'Item Name',
    'unit_price' => 'Unit Price',
    'total_price' => 'Total Price',
    'delivery_date' => 'Delivery Date',
    'notes' => 'Notes',
    'description' => 'Description',
    'department' => 'Department',
    'full_name' => 'Full Name',
    'dr_number' => 'DR Number',
    'file' => 'File',
    'path' => 'File Path',
    'active_status' => 'Active Status',
    'added_quantity' => 'Added Quantity',
    'lot_number' => 'Lot Number',
    'new_quantity' => 'New Quantity',
    'new_dr_number' => 'New DR Number',
    'lot_ids' => 'Lot IDs',
];
$humanize = function($field) use ($fieldLabels) {
    if (isset($fieldLabels[$field])) return $fieldLabels[$field];
    return ucwords(str_replace('_', ' ', $field));
};
$display = function($v) {
    if (is_bool($v)) return $v ? 'Yes' : 'No';
    if (is_null($v)) return '(empty)';
    if (is_array($v)) {
        if (isset($v['poi_id']) && isset($v['quantity'])) {
            return count($v) . ' item(s)';
        }
        return implode(', ', array_map('strval', $v));
    }
    $s = (string)$v;
    if (strlen($s) > 80) return substr($s, 0, 80) . '...';
    return $s;
};
$skipFields = [
    'poi_id', 'item_id', 'uom', 'items_json', 'controller', 'action',
    'po_id', 'customer_id', 'requested_by', 'customer_terms', 'item_uom',
    'produced_quantity', 'delivered_quantity', 'delivery_quantity',
    'status', 'remove', 'total_quantity', 'unit_price', 'items',
    'lot_ids',
];
$buildChangeSummary = function($log) use ($humanize, $display, $skipFields) {
    $old = $log['old_values'] ? json_decode($log['old_values'], true) : null;
    $new = $log['new_values'] ? json_decode($log['new_values'], true) : null;
    if (in_array($log['action'], ['LOGIN', 'LOGOUT'])) return null;
    $allKeys = array_unique(array_merge(array_keys($old ?? []), array_keys($new ?? [])));
    if (empty($allKeys)) return null;
    $parts = [];
    $productionQtyContext = false;
    if (($log['target_type'] ?? '') === 'production_history' || (stripos($log['description'] ?? '', 'production') !== false && stripos($log['description'] ?? '', 'history') !== false)) {
        $productionQtyContext = true;
    }
    foreach ($allKeys as $key) {
        if (in_array($key, $skipFields, true)) continue;
        if ($productionQtyContext && in_array($key, ['added_quantity','previous_quantity','new_quantity','old_added_quantity','old_lot_number','lot_number'], true)) {
            $oldAdded = $old['added_quantity'] ?? null;
            $newAdded = $new['added_quantity'] ?? null;
            $oldPrev = $old['previous_quantity'] ?? null;
            $newPrev = $new['previous_quantity'] ?? null;
            $oldNewQty = $old['new_quantity'] ?? null;
            $newNewQty = $new['new_quantity'] ?? null;
            $oldLot = $old['lot_number'] ?? $old['old_lot_number'] ?? null;
            $newLot = $new['lot_number'] ?? $new['old_lot_number'] ?? null;
            if ($oldAdded !== null || $newAdded !== null || $oldPrev !== null || $newPrev !== null || $oldNewQty !== null || $newNewQty !== null) {
                $qtyParts = [];
                if ($oldLot !== null || $newLot !== null) {
                    $lotLabel = $newLot !== null ? $newLot : ($oldLot !== null ? $oldLot : '?');
                    $qtyParts[] = 'lot ' . $lotLabel . ' quantity ' . ($oldPrev !== null ? $oldPrev : '?') . ' → ' . ($newNewQty !== null ? $newNewQty : ($newPrev !== null ? $newPrev : '?'));
                } else {
                    if ($oldPrev !== null || $newPrev !== null) {
                        $qtyParts[] = 'previous quantity ' . ($oldPrev !== null ? $oldPrev : '?') . ' → ' . ($newPrev !== null ? $newPrev : '?');
                    }
                    if ($oldAdded !== null || $newAdded !== null) {
                        $qtyParts[] = 'added quantity ' . ($oldAdded !== null ? $oldAdded : '?') . ' → ' . ($newAdded !== null ? $newAdded : '?');
                    }
                    if ($oldNewQty !== null || $newNewQty !== null) {
                        $qtyParts[] = 'new quantity ' . ($oldNewQty !== null ? $oldNewQty : '?') . ' → ' . ($newNewQty !== null ? $newNewQty : '?');
                    }
                }
                $parts[] = 'Production record updated: ' . implode('; ', $qtyParts);
            }
            continue;
        }
        if ($key === 'items' && (is_array($old[$key] ?? null) || is_array($new[$key] ?? null))) {
            $oldItems = $old['items'] ?? [];
            $newItems = $new['items'] ?? [];
            $oldMap = [];
            foreach ($oldItems as $item) {
                if (!empty($item['poi_id'])) {
                    $oldMap[$item['poi_id']] = $item;
                }
            }
            $newMap = [];
            foreach ($newItems as $item) {
                if (!empty($item['poi_id'])) {
                    $newMap[$item['poi_id']] = $item;
                }
            }
            $allPoiIds = array_unique(array_merge(array_keys($oldMap), array_keys($newMap)));
            foreach ($allPoiIds as $poiId) {
                $oldItem = $oldMap[$poiId] ?? null;
                $newItem = $newMap[$poiId] ?? null;
                $itemId = $newItem['item_id'] ?? $oldItem['item_id'] ?? 0;
                if ($oldItem && $newItem) {
                    $oldQty = $oldItem['quantity'] ?? 0;
                    $newQty = $newItem['quantity'] ?? 0;
                    if ($oldQty != $newQty) {
                        $parts[] = "Item #{$itemId} (POI #{$poiId}): Quantity {$oldQty} → {$newQty}";
                    }
                } elseif ($newItem && !$oldItem) {
                    $qty = $newItem['quantity'] ?? 0;
                    $parts[] = "Added Item #{$itemId} (POI #{$poiId}): Qty {$qty}";
                } elseif ($oldItem && !$newItem) {
                    $oldQty = $oldItem['quantity'] ?? 0;
                    $parts[] = "Removed Item #{$itemId} (POI #{$poiId}) (was Qty {$oldQty})";
                }
            }
            continue;
        }
        $label = $humanize($key);
        $oldVal = $old[$key] ?? null;
        $newVal = $new[$key] ?? null;
        if ($oldVal !== null && $newVal !== null && $oldVal != $newVal) {
            $parts[] = $label . ': ' . $display($oldVal) . ' → ' . $display($newVal);
        } elseif ($newVal !== null && $oldVal === null) {
            $parts[] = $label . ': ' . $display($newVal);
        } elseif ($oldVal !== null && $newVal === null) {
            $parts[] = $label . ' (was ' . $display($oldVal) . ')';
        }
    }
    return $parts ? implode(' | ', $parts) : null;
};
?>

<h4><i class="bi bi-clock-history me-2"></i>Activity Logs<?php if ($departmentLocked): ?> <small class="text-muted">(<?= ucfirst(htmlspecialchars($_SESSION['department'] ?? '')) ?>)</small><?php endif; ?></h4>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card p-3">
            <div class="d-flex align-items-center">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:45px;height:45px;background:rgba(74,144,217,0.1)">
                    <i class="bi bi-clock-history text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Today's Logs</div>
                    <div class="fw-bold fs-5"><?= $stats['today_count'] ?? 0 ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php if (!$departmentLocked): ?>
        <?php foreach (($stats['by_department'] ?? []) as $dept): ?>
            <div class="col-md-3">
                <div class="stat-card p-3">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:45px;height:45px;background:rgba(74,144,217,0.1)">
                            <i class="bi bi-people text-primary"></i>
                        </div>
                        <div>
                            <div class="text-muted small"><?= ucfirst(htmlspecialchars($dept['department'])) ?></div>
                            <div class="fw-bold fs-5"><?= $dept['cnt'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card data-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <input type="hidden" name="controller" value="<?= htmlspecialchars($logController) ?>">
            <input type="hidden" name="action" value="activityLogs">

            <select name="user_id" class="form-select form-select-sm" style="width:170px">
                <option value="">All Users</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['user_id'] ?>" <?= ($filters['user_id'] ?? '') == $u['user_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if (!$departmentLocked): ?>
            <select name="department" class="form-select form-select-sm" style="width:140px">
                <option value="">All Depts</option>
                <?php foreach (['admin', 'warehouse', 'production', 'finance'] as $d): ?>
                    <option value="<?= $d ?>" <?= ($filters['department'] ?? '') === $d ? 'selected' : '' ?>><?= ucfirst($d) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="module" class="form-select form-select-sm" style="width:140px">
                <option value="">All Modules</option>
                <?php foreach (['auth', 'admin', 'warehouse', 'production', 'finance'] as $m): ?>
                    <option value="<?= $m ?>" <?= ($filters['module'] ?? '') === $m ? 'selected' : '' ?>><?= ucfirst($m) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <select name="log_action" class="form-select form-select-sm" style="width:130px">
                <option value="">All Actions</option>
                <?php foreach (['LOGIN', 'LOGOUT', 'CREATE', 'UPDATE', 'DELETE'] as $a): ?>
                    <option value="<?= $a ?>" <?= ($filters['log_action'] ?? '') === $a ? 'selected' : '' ?>><?= $a ?></option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="date_from" class="form-control form-control-sm" style="width:145px" placeholder="From" value="<?= $filters['date_from'] ?? '' ?>">
            <input type="date" name="date_to" class="form-control form-control-sm" style="width:145px" placeholder="To" value="<?= $filters['date_to'] ?? '' ?>">

            <input type="text" name="search" class="form-control form-control-sm" style="width:200px" placeholder="Search description..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">

            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
            <a href="?controller=<?= htmlspecialchars($logController) ?>&action=activityLogs" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>Clear</a>
        </form>
    </div>
</div>

<!-- Logs Table -->
<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <?php if (!$hideDeptColumn): ?>
                        <th>Department</th>
                    <?php endif; ?>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs['items'])): ?>
                    <?php foreach ($logs['items'] as $log): ?>
                        <?php $changeSummary = $buildChangeSummary($log); ?>
                        <tr>
                            <td><small class="text-muted"><?= date('M d, Y H:i:s', strtotime($log['created_at'])) ?></small></td>
                            <td><strong><?= htmlspecialchars($log['username']) ?></strong></td>
                            <?php if (!$hideDeptColumn): ?>
                                <td><?= ucfirst(htmlspecialchars($log['department'])) ?></td>
                            <?php endif; ?>
                            <td><?= $log['action'] ?></td>
                            <td><?= ucfirst($log['module']) ?></td>
                            <td>
                                <?= htmlspecialchars($log['description']) ?>
                                <?php if ($changeSummary): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($changeSummary) ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="<?= $hideDeptColumn ? '5' : '6' ?>" class="text-center text-muted py-4">No logs found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($logs['totalPages'] > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php
        $baseUrl = '?controller=' . htmlspecialchars($logController) . '&action=activityLogs';
        foreach (['user_id', 'department', 'module', 'log_action', 'date_from', 'date_to', 'search'] as $param) {
            if (!empty($filters[$param])) {
                $baseUrl .= '&' . $param . '=' . urlencode($filters[$param]);
            }
        }
        ?>
        <li class="page-item <?= $logs['page'] <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $baseUrl ?>&page=<?= $logs['page'] - 1 ?>">&laquo;</a>
        </li>
        <?php for ($i = max(1, $logs['page'] - 3); $i <= min($logs['totalPages'], $logs['page'] + 3); $i++): ?>
            <li class="page-item <?= $i === $logs['page'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= $baseUrl ?>&page=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $logs['page'] >= $logs['totalPages'] ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $baseUrl ?>&page=<?= $logs['page'] + 1 ?>">&raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
