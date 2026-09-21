<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0">Manufacturing Orders</h4>
    </div>
    <a href="?controller=mo&action=create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Create New MO
    </a>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?= ($status ?? 'all') === 'all' ? 'active' : '' ?>" href="?controller=mo&action=index&status=all">All Orders</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($status ?? '') === 'Planned' ? 'active' : '' ?>" href="?controller=mo&action=index&status=Planned">Planned Orders</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($status ?? '') === 'Released' ? 'active' : '' ?>" href="?controller=mo&action=index&status=Released">Released Orders</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($status ?? '') === 'Completed' ? 'active' : '' ?>" href="?controller=mo&action=index&status=Completed">Completed Orders</a>
            </li>
        </ul>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>MO Number</th>
                        <th>Customer</th>
                        <th>Item Code</th>
                        <th>Qty Ordered</th>
                        <th>Batch / Lot No</th>
                        <th>Planned Start Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No manufacturing orders found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $mo): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($mo['mo_number'] ?? '-') ?></strong></td>
                                <td><?= htmlspecialchars($mo['customer_name'] ?? $mo['customer_code'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($mo['item_code'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($mo['qty_ordered'] ?? 0) ?></td>
                                <td><?= htmlspecialchars($mo['batch_lot_no'] ?? '-') ?></td>
                                <td><?= !empty($mo['planned_start_date']) ? date('Y-m-d', strtotime($mo['planned_start_date'])) : '-' ?></td>
                                <td>
                                    <?php
                                        $statusClass = 'secondary';
                                        if (($mo['mo_status'] ?? 'Planned') === 'Planned') $statusClass = 'info';
                                        elseif (($mo['mo_status'] ?? '') === 'Released') $statusClass = 'primary';
                                        elseif (($mo['mo_status'] ?? '') === 'Completed') $statusClass = 'success';
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($mo['mo_status'] ?? 'Planned') ?></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="?controller=mo&action=edit&id=<?= (int) ($mo['mo_id'] ?? 0) ?>" class="btn btn-outline-primary">Edit</a>
                                        <a href="?controller=mo&action=release&id=<?= (int) ($mo['mo_id'] ?? 0) ?>" class="btn btn-outline-success">Release MO</a>
                                        <?php if (($mo['mo_status'] ?? '') === 'Released'): ?>
                                            <a href="?controller=mo&action=printLmr&id=<?= (int) ($mo['mo_id'] ?? 0) ?>" class="btn btn-outline-dark" target="_blank">Print LMR</a>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary" type="button" disabled>Print LMR</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
