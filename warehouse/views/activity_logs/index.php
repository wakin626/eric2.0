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
$formatValues = function($json) use ($humanize, $display) {
    $data = $json ? json_decode($json, true) : null;
    if (empty($data)) return '<span class="text-muted">None</span>';
    $skip = ['poi_id','item_id','uom','items_json','controller','action','po_id','customer_id','requested_by','customer_terms','item_uom','lot_ids'];
    $parts = [];
    foreach ($data as $key => $val) {
        if (in_array($key, $skip)) continue;
        $label = $humanize($key);
        $parts[] = '<strong>' . htmlspecialchars($label) . ':</strong> ' . htmlspecialchars($display($val));
    }
    return $parts ? implode('<br>', $parts) : '<span class="text-muted">None</span>';
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
                    <th width="50"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs['items'])): ?>
                    <?php foreach ($logs['items'] as $log): ?>
                        <tr>
                            <td><small class="text-muted"><?= date('M d, Y H:i:s', strtotime($log['created_at'])) ?></small></td>
                            <td><strong><?= htmlspecialchars($log['username']) ?></strong></td>
                            <?php if (!$hideDeptColumn): ?>
                                <td><?= ucfirst(htmlspecialchars($log['department'])) ?></td>
                            <?php endif; ?>
                            <td>
                                <?php
                                $actColors = ['CREATE'=>'success','UPDATE'=>'primary','DELETE'=>'danger','LOGIN'=>'info','LOGOUT'=>'secondary'];
                                $actColor = $actColors[$log['action']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $actColor ?>"><?= $log['action'] ?></span>
                            </td>
                            <td><?= ucfirst(htmlspecialchars($log['module'])) ?></td>
                            <td><small><?= htmlspecialchars($log['description']) ?></small></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-info viewLogBtn"
                                    data-timestamp="<?= htmlspecialchars(date('M d, Y H:i:s', strtotime($log['created_at']))) ?>"
                                    data-username="<?= htmlspecialchars($log['username']) ?>"
                                    data-department="<?= htmlspecialchars($log['department']) ?>"
                                    data-action="<?= htmlspecialchars($log['action']) ?>"
                                    data-module="<?= htmlspecialchars($log['module']) ?>"
                                    data-description="<?= htmlspecialchars($log['description']) ?>"
                                    data-oldvalues="<?= htmlspecialchars($log['old_values'] ?? '') ?>"
                                    data-newvalues="<?= htmlspecialchars($log['new_values'] ?? '') ?>"
                                    data-targettype="<?= htmlspecialchars($log['target_type'] ?? '') ?>"
                                    data-targetid="<?= htmlspecialchars($log['target_id'] ?? '') ?>"
                                    title="View Details">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="<?= $hideDeptColumn ? '6' : '7' ?>" class="text-center text-muted py-4">No logs found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- View Log Modal -->
<div class="modal fade" id="viewLogModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6"><strong>Timestamp:</strong> <span id="logTimestamp">-</span></div>
                    <div class="col-md-6"><strong>User:</strong> <span id="logUser">-</span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4"><strong>Department:</strong> <span id="logDept">-</span></div>
                    <div class="col-md-4"><strong>Action:</strong> <span id="logAction">-</span></div>
                    <div class="col-md-4"><strong>Module:</strong> <span id="logModule">-</span></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6"><strong>Target Type:</strong> <span id="logTargetType">-</span></div>
                    <div class="col-md-6"><strong>Target ID:</strong> <span id="logTargetId">-</span></div>
                </div>
                <hr>
                <div class="mb-3">
                    <strong>Description:</strong>
                    <div id="logDescription" class="mt-1 p-2 rounded" style="background:#f8f9fa; white-space:pre-wrap;"></div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Old Values:</strong>
                        <div id="logOldValues" class="mt-1 p-2 rounded" style="background:#fef2f2; min-height:60px; font-size:0.85rem;"></div>
                    </div>
                    <div class="col-md-6">
                        <strong>New Values:</strong>
                        <div id="logNewValues" class="mt-1 p-2 rounded" style="background:#f0fdf4; min-height:60px; font-size:0.85rem;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
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

<script>
document.querySelectorAll('.viewLogBtn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('logTimestamp').textContent = this.dataset.timestamp || '-';
        document.getElementById('logUser').textContent = this.dataset.username || '-';
        document.getElementById('logDept').textContent = this.dataset.department || '-';
        document.getElementById('logAction').innerHTML = '<span class="badge bg-' + ({
            CREATE:'success',UPDATE:'primary',DELETE:'danger',LOGIN:'info',LOGOUT:'secondary'
        }[this.dataset.action] || 'secondary') + '">' + (this.dataset.action || '-') + '</span>';
        document.getElementById('logModule').textContent = this.dataset.module || '-';
        document.getElementById('logTargetType').textContent = this.dataset.targettype || '-';
        document.getElementById('logTargetId').textContent = this.dataset.targetid || '-';
        document.getElementById('logDescription').textContent = this.dataset.description || '-';

        var oldVals = this.dataset.oldvalues;
        var newVals = this.dataset.newvalues;
        document.getElementById('logOldValues').innerHTML = formatValues(oldVals);
        document.getElementById('logNewValues').innerHTML = formatValues(newVals);

        var modal = new bootstrap.Modal(document.getElementById('viewLogModal'));
        modal.show();
    });
});

function formatValues(json) {
    if (!json) return '<span class="text-muted">None</span>';
    var data;
    try { data = JSON.parse(json); } catch(e) { return '<span class="text-muted">None</span>'; }
    if (!data || Object.keys(data).length === 0) return '<span class="text-muted">None</span>';
    var skip = ['poi_id','item_id','uom','items_json','controller','action','po_id','customer_id','requested_by','customer_terms','item_uom','lot_ids'];
    var labels = <?= json_encode($fieldLabels) ?>;
    var html = '';
    for (var key in data) {
        if (skip.indexOf(key) !== -1) continue;
        var label = labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, function(l){ return l.toUpperCase(); });
        var val = data[key];
        if (typeof val === 'object' && val !== null) val = JSON.stringify(val);
        html += '<strong>' + label + ':</strong> ' + (val !== null && val !== undefined ? val : '(empty)') + '<br>';
    }
    return html || '<span class="text-muted">None</span>';
}
</script>
