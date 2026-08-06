<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <a href="?controller=admin&action=delivered" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Deliveries
        </a>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <select id="filterCustomer" class="form-select form-select-sm filter-select" style="width:200px">
            <option value="">All Customers</option>
            <?php foreach (($customers ?? []) as $c): ?>
                <option value="<?= $c['customer_id'] ?>" <?= ($filterCustomer ?? '') == $c['customer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['customer_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <a href="?controller=admin&action=viewBackloads" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>Clear</a>
    </div>
    <div class="search-box" style="width: 300px;">
        <form method="GET" class="d-flex align-items-center">
            <input type="hidden" name="controller" value="admin">
            <input type="hidden" name="action" value="viewBackloads">
            <input type="hidden" name="filter_customer" value="<?= htmlspecialchars($filterCustomer ?? '') ?>">
            <i class="bi bi-search"></i>
            <input type="text" name="search" class="form-control" placeholder="Search DR or PO number..." value="<?= htmlspecialchars($search ?? '') ?>">
        </form>
    </div>
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>DR Number</th>
                    <th>PO Number</th>
                    <th>Customer</th>
                    <th>Item</th>
                    <th>Lot Number</th>
                    <th>Cases</th>
                    <th>Qty Returned (pcs)</th>
                    <th>Reason</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($backloads)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No backloads found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($backloads as $bl): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($bl['date_created'])) ?></td>
                            <td><strong><?= htmlspecialchars($bl['dr_number']) ?></strong></td>
                            <td><?= htmlspecialchars($bl['customer_po_number']) ?></td>
                            <td><?= htmlspecialchars($bl['customer_name']) ?></td>
                            <td><?= htmlspecialchars($bl['item_description'] ?? $bl['item_code']) ?></td>
                            <td><span class="badge bg-dark"><?= htmlspecialchars($bl['lot_number']) ?></span></td>
                            <td><?= $bl['cases'] !== null ? number_format($bl['cases']) . ' CS' : '-' ?></td>
                            <td class="fw-bold text-danger"><?= number_format($bl['quantity']) ?> pcs</td>
                            <td><?= htmlspecialchars($bl['reason']) ?></td>
                            <td><?= htmlspecialchars($bl['backloaded_by_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing <?= count($backloads) ?> of <?= $total ?> backloads</small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?controller=admin&action=viewBackloads&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&filter_customer=<?= urlencode($filterCustomer) ?>">Previous</a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?controller=admin&action=viewBackloads&page=<?= $i ?>&search=<?= urlencode($search) ?>&filter_customer=<?= urlencode($filterCustomer) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?controller=admin&action=viewBackloads&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&filter_customer=<?= urlencode($filterCustomer) ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('filterCustomer').addEventListener('change', function() {
    var params = new URLSearchParams(window.location.search);
    if (this.value) {
        params.set('filter_customer', this.value);
    } else {
        params.delete('filter_customer');
    }
    params.set('controller', 'admin');
    params.set('action', 'viewBackloads');
    window.location.href = '?' + params.toString();
});
</script>
