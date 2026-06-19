<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="text-muted">Showing <?= count($deliveries) ?> of <?= $total ?> deliveries</span>
    </div>
    <div class="search-box" style="width: 300px;">
        <i class="bi bi-search"></i>
        <input type="text" id="searchDelivery" class="form-control" placeholder="Search delivery...">
    </div>
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Customer</th>
                    <th>Item</th>
                    <th>Delivery Date</th>
                    <th>Quantity</th>
                    <th>Delivered By</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="deliveryTableBody">
                <?php foreach ($deliveries as $d): ?>
                <tr>
                    <td><strong class="text-primary"><?= htmlspecialchars($d['customer_po_number'] ?? '-') ?></strong></td>
                    <td><?= htmlspecialchars($d['customer_name'] ?? '-') ?></td>
                    <td><small><?= htmlspecialchars($d['item_description'] ?? '-') ?></small></td>
                    <td><?= date('Y-m-d', strtotime($d['delivery_date'])) ?></td>
                    <td><?= $d['delivery_quantity'] ?? 0 ?></td>
                    <td><?= htmlspecialchars($d['delivered_by_name'] ?? '-') ?></td>
                    <td class="text-center">
                        <a href="?controller=finance&action=viewDelivery&id=<?= $d['delivery_id'] ?>" class="btn btn-sm btn-outline-primary" title="View Delivery">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($deliveries)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No deliveries found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination justify-content-center mt-4">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?controller=finance&action=deliveries&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<script>
document.getElementById('searchDelivery').addEventListener('keyup', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#deliveryTableBody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
});
</script>
