<div class="d-flex justify-content-between mb-4">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#customerModal">Add Customer</button>
    <input type="text" id="searchCustomer" class="form-control w-25" placeholder="Search...">
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th><th>Name</th><th>Delivery Address</th><th>TIN</th><th>Terms (Days)</th>
                    <th>Status</th><th>Created</th><th>Updated</th><th>Actions</th>
                </tr>
            </thead>
            <tbody id="customerTableBody">
                <?php foreach ($customers as $c): ?>
                <tr>
                    <td><strong class="text-primary"><?= $c['customer_code'] ?></strong></td>
                    <td><?= $c['customer_name'] ?></td>
                    <td><small><?= $c['customer_address'] ?? '-' ?></small></td>
                    <td><?= $c['customer_tin'] ?? '-' ?></td>
                    <td><?= ($c['customer_terms'] ?? 0) > 0 ? $c['customer_terms'] . ' days' : '-' ?></td>
                    <td><span class="badge bg-<?= $c['status'] ? 'success' : 'secondary' ?>"><?= $c['status'] ? 'Active' : 'Inactive' ?></span></td>
                    <td><?= date('Y-m-d', strtotime($c['date_created'])) ?></td>
                    <td><?= date('Y-m-d H:i', strtotime($c['last_update'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#customerEditModal" data-id="<?= $c['customer_id'] ?>" data-code="<?= htmlspecialchars($c['customer_code']) ?>" data-name="<?= htmlspecialchars($c['customer_name']) ?>" data-address="<?= htmlspecialchars($c['customer_address'] ?? '') ?>" data-tin="<?= htmlspecialchars($c['customer_tin'] ?? '') ?>" data-terms="<?= $c['customer_terms'] ?? 0 ?>"><i class="bi bi-pencil"></i></button>
                        <a href="?controller=admin&action=customerToggleStatus&id=<?= $c['customer_id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-toggle-on"></i></a>
                        <a href="?controller=admin&action=customerDelete&id=<?= $c['customer_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination justify-content-center mt-4">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?controller=admin&action=customers&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<div class="modal fade" id="customerModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Customer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="?controller=admin&action=customerCreate">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Customer Code *</label><input type="text" name="customer_code" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Customer Name *</label><input type="text" name="customer_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Delivery Address</label><textarea name="customer_address" class="form-control"></textarea></div>
                    <div class="mb-3"><label class="form-label">TIN</label><input type="text" name="customer_tin" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Terms (Days)</label><select name="customer_terms" class="form-select"><option value="15">15 days</option><option value="30">30 days</option><option value="60">60 days</option><option value="90">90 days</option><option value="120">120 days</option></select></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="customerEditModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Customer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="?controller=admin&action=customerUpdate">
                <div class="modal-body">
                    <input type="hidden" name="customer_id" id="edit_customer_id">
                    <div class="mb-3"><label class="form-label">Customer Code *</label><input type="text" name="customer_code" id="edit_customer_code" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Customer Name *</label><input type="text" name="customer_name" id="edit_customer_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Delivery Address</label><textarea name="customer_address" id="edit_customer_address" class="form-control"></textarea></div>
                    <div class="mb-3"><label class="form-label">TIN</label><input type="text" name="customer_tin" id="edit_customer_tin" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Terms (Days)</label><select name="customer_terms" id="edit_customer_terms" class="form-select"><option value="15">15 days</option><option value="30">30 days</option><option value="60">60 days</option><option value="90">90 days</option><option value="120">120 days</option></select></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('searchCustomer').onkeyup = function() {
    let q = this.value.toLowerCase();
    document.querySelectorAll('#customerTableBody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none');
};

document.getElementById('customerEditModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    document.getElementById('edit_customer_id').value = button.getAttribute('data-id');
    document.getElementById('edit_customer_code').value = button.getAttribute('data-code');
    document.getElementById('edit_customer_name').value = button.getAttribute('data-name');
    document.getElementById('edit_customer_address').value = button.getAttribute('data-address');
    document.getElementById('edit_customer_tin').value = button.getAttribute('data-tin');
    document.getElementById('edit_customer_terms').value = button.getAttribute('data-terms') || '0';
});
</script>