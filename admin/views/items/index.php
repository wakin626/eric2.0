<div class="d-flex justify-content-between mb-4">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#itemModal">Add Item</button>
    <input type="text" id="searchItem" class="form-control w-25" placeholder="Search...">
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th><th>Description</th><th>UOM</th>
                    <th>Status</th><th>Created</th><th>Updated</th><th>Actions</th>
                </tr>
            </thead>
            <tbody id="itemTableBody">
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><strong class="text-primary"><?= $item['item_code'] ?></strong></td>
                    <td><?= $item['item_description'] ?></td>
                    <td><span class="badge bg-info"><?= $item['item_uom'] ?></span></td>
                    <td><span class="badge bg-<?= $item['status'] ? 'success' : 'secondary' ?>"><?= $item['status'] ? 'Active' : 'Inactive' ?></span></td>
                    <td><?= date('Y-m-d', strtotime($item['date_created'])) ?></td>
                    <td><?= date('Y-m-d H:i', strtotime($item['last_update'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#itemEditModal" data-id="<?= $item['item_id'] ?>" data-code="<?= htmlspecialchars($item['item_code']) ?>" data-desc="<?= htmlspecialchars($item['item_description']) ?>" data-uom="<?= $item['item_uom'] ?>"><i class="bi bi-pencil"></i></button>
                        <a href="?controller=admin&action=itemToggleStatus&id=<?= $item['item_id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-toggle-on"></i></a>
                        <a href="?controller=admin&action=itemDelete&id=<?= $item['item_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
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
            <a class="page-link" href="?controller=admin&action=items&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<div class="modal fade" id="itemModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Add Item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="?controller=admin&action=itemCreate">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Item Code *</label><input type="text" name="item_code" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description *</label><input type="text" name="item_description" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">UOM *</label><select name="item_uom" class="form-select" required><option value="">Select</option><option>PCS</option><option>PCKS</option><option>CS</option></select></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="itemEditModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Edit Item</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="?controller=admin&action=itemUpdate">
                <div class="modal-body">
                    <input type="hidden" name="item_id" id="edit_item_id">
                    <div class="mb-3"><label class="form-label">Item Code *</label><input type="text" name="item_code" id="edit_item_code" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description *</label><input type="text" name="item_description" id="edit_item_description" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">UOM *</label><select name="item_uom" id="edit_item_uom" class="form-select" required><option value="">Select</option><option>PCS</option><option>PCKS</option><option>CS</option></select></div>
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
document.getElementById('searchItem').onkeyup = function() {
    let q = this.value.toLowerCase();
    document.querySelectorAll('#itemTableBody tr').forEach(r => r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none');
};

document.getElementById('itemEditModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    document.getElementById('edit_item_id').value = button.getAttribute('data-id');
    document.getElementById('edit_item_code').value = button.getAttribute('data-code');
    document.getElementById('edit_item_description').value = button.getAttribute('data-desc');
    document.getElementById('edit_item_uom').value = button.getAttribute('data-uom');
});
</script>