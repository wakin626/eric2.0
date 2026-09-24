<div class="d-flex justify-content-between mb-4">
    <div class="d-flex gap-2">
        <a href="?controller=rnd&action=itemImportForm" class="btn btn-success"><i class="bi bi-upload me-1"></i>Import ERIC Items</a>
    </div>
    <form method="GET" class="d-flex gap-2" style="width:50%">
        <input type="hidden" name="controller" value="rnd">
        <input type="hidden" name="action" value="items">
        <input type="text" name="search" class="form-control" placeholder="Search code, description..." value="<?= htmlspecialchars($search ?? '') ?>">
        <select name="item_type" class="form-select filter-select" style="width:auto" onchange="this.form.submit()">
            <option value="">All Types</option>
            <option value="RM" <?= ($typeFilter ?? '') === 'RM' ? 'selected' : '' ?>>Raw Material</option>
            <option value="PM" <?= ($typeFilter ?? '') === 'PM' ? 'selected' : '' ?>>Packaging</option>
            <option value="FG" <?= ($typeFilter ?? '') === 'FG' ? 'selected' : '' ?>>Finished Good</option>
            <option value="SFG" <?= ($typeFilter ?? '') === 'SFG' ? 'selected' : '' ?>>Semi-Finished</option>
            <option value="SUPPLIES" <?= ($typeFilter ?? '') === 'SUPPLIES' ? 'selected' : '' ?>>Supplies</option>
        </select>
    </form>
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>UOM</th>
                    <th>SOH</th>
                    <th>Allocated</th>
                    <th>Available</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><strong class="text-primary"><?= htmlspecialchars($item['item_code']) ?></strong></td>
                    <td><?= htmlspecialchars($item['item_description']) ?></td>
                    <td>
                        <?php
                        $typeBadges = ['RM' => 'danger', 'PM' => 'info', 'FG' => 'success', 'SFG' => 'warning', 'SUPPLIES' => 'secondary'];
                        $badge = $typeBadges[$item['item_type']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $badge ?>"><?= $item['item_type'] ?? '—' ?></span>
                    </td>
                    <td><?= htmlspecialchars($item['item_uom']) ?></td>
                    <td><?= number_format($item['total_soh'], 4) ?></td>
                    <td><?= number_format($item['total_allocated'], 4) ?></td>
                    <td><strong><?= number_format($item['available_stock'], 4) ?></strong></td>
                    <td>
                        <?php
                        $statusColors = ['OUT OF STOCK' => 'danger', 'LOW STOCK' => 'warning', 'IN STOCK' => 'success'];
                        $color = $statusColors[$item['inventory_status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $color ?>"><?= $item['inventory_status'] ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No items found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($totalPages ?? 1) > 1): ?>
<?php $pages = \App\Helpers\Pagination::getPageRange($page, $totalPages); ?>
<nav>
    <ul class="pagination justify-content-center mt-4">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?controller=rnd&action=items&page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>&item_type=<?= urlencode($typeFilter ?? '') ?>">&laquo; Prev</a>
        </li>
        <?php foreach ($pages as $p): ?>
            <?php if ($p === '...'): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php else: ?>
            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                <a class="page-link" href="?controller=rnd&action=items&page=<?= $p ?>&search=<?= urlencode($search ?? '') ?>&item_type=<?= urlencode($typeFilter ?? '') ?>"><?= $p ?></a>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?controller=rnd&action=items&page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>&item_type=<?= urlencode($typeFilter ?? '') ?>">Next &raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>
