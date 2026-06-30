<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPriceModal">
            <i class="bi bi-plus-circle me-1"></i>Add Price
        </button>
        <span class="text-muted ms-3">Showing <?= count($price_items) ?> of <?= $total ?> items</span>
    </div>
    <input type="text" id="searchItem" class="form-control w-25" placeholder="Search items...">
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Item Code</th>
                    <th>Net/Size</th>
                    <th class="text-end">Price per Piece</th>
                    <th class="text-end">Price per Pack</th>
                    <th class="text-end">Price per Case</th>
                    <th class="text-center">VAT Type</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Date Created</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="itemTableBody">
                <?php if (empty($price_items)): ?>
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">No price list items found</td>
                </tr>
                <?php else: ?>
                <?php $i = ($page - 1) * 20 + 1; ?>
                <?php foreach ($price_items as $item): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($item['product_name']) ?></strong></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($item['item_code'] ?? '—') ?></span></td>
                    <td><?= htmlspecialchars($item['net_size'] ?? '—') ?></td>
                    <td class="text-end">₱<?= number_format($item['price_per_piece'] ?? 0, 2) ?></td>
                    <td class="text-end">₱<?= number_format($item['price_per_pack'], 2) ?></td>
                    <td class="text-end">₱<?= number_format($item['price_per_case'], 2) ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $item['vat_type'] === 'vat' ? 'warning' : 'info' ?>">
                            <?= $item['vat_type'] === 'vat' ? 'VAT' : 'Non-VAT' ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-<?= $item['status'] ? 'success' : 'secondary' ?>">
                            <?= $item['status'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="text-center"><?= date('Y-m-d', strtotime($item['date_created'])) ?></td>
                    <td class="text-center">
                        <a href="?controller=finance&action=priceListToggle&id=<?= $item['price_list_id'] ?>" 
                           class="btn btn-sm btn-<?= $item['status'] ? 'success' : 'outline-secondary' ?>"
                           onclick="return confirm('Are you sure you want to change the status?')"
                           title="<?= $item['status'] ? 'Deactivate' : 'Activate' ?>">
                            <i class="bi bi-toggle-<?= $item['status'] ? 'on' : 'off' ?>"></i>
                        </a>
                        <button class="btn btn-sm btn-primary edit-btn"
                            data-id="<?= $item['price_list_id'] ?>"
                            data-item_id="<?= $item['item_id'] ?? '' ?>"
                            data-product_name="<?= htmlspecialchars($item['product_name']) ?>"
                            data-net_size="<?= htmlspecialchars($item['net_size'] ?? '') ?>"
                            data-price_per_pack="<?= $item['price_per_pack'] ?>"
                            data-price_per_case="<?= $item['price_per_case'] ?>"
                            data-price_per_piece="<?= $item['price_per_piece'] ?? 0 ?>"
                            data-vat_type="<?= $item['vat_type'] ?>"
                            data-bs-toggle="modal" data-bs-target="#editPriceModal">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
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
            <a class="page-link" href="?controller=finance&action=priceList&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- Add Price Modal -->
<div class="modal fade" id="addPriceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Price List Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?controller=finance&action=priceListCreate">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Product *</label>
                        <select name="item_id" id="add_item_id" class="form-select" required>
                            <option value="">-- Select a product --</option>
                            <?php foreach ($all_items as $itm): ?>
                            <option value="<?= $itm['item_id'] ?>"
                                data-name="<?= htmlspecialchars($itm['item_description']) ?>"
                                data-size="<?= htmlspecialchars($itm['item_size'] ?? '') ?>">
                                <?= htmlspecialchars($itm['item_code'] . ' - ' . $itm['item_description']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="product_name" id="add_product_name" class="form-control" required readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Net/Size</label>
                        <input type="text" name="net_size" id="add_net_size" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price per Piece *</label>
                            <input type="number" name="price_per_piece" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price per Pack *</label>
                            <input type="number" name="price_per_pack" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price per Case *</label>
                            <input type="number" name="price_per_case" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">VAT Type *</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="vat_type" id="addVat" value="vat" checked>
                                <label class="form-check-label" for="addVat">VAT</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="vat_type" id="addNonVat" value="non_vat">
                                <label class="form-check-label" for="addNonVat">Non-VAT</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Price Modal -->
<div class="modal fade" id="editPriceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Price List Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?controller=finance&action=priceListUpdate">
                <div class="modal-body">
                    <input type="hidden" name="price_list_id" id="edit_price_list_id">
                    <input type="hidden" name="item_id" id="edit_item_id">
                    <div class="mb-3">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="product_name" id="edit_product_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Net/Size</label>
                        <input type="text" name="net_size" id="edit_net_size" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price per Pack *</label>
                            <input type="number" name="price_per_pack" id="edit_price_per_pack" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price per Case *</label>
                            <input type="number" name="price_per_case" id="edit_price_per_case" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price per Piece *</label>
                            <input type="number" name="price_per_piece" id="edit_price_per_piece" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">VAT Type *</label>
                        <div class="d-flex gap-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="vat_type" id="editVat" value="vat">
                                <label class="form-check-label" for="editVat">VAT</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="vat_type" id="editNonVat" value="non_vat">
                                <label class="form-check-label" for="editNonVat">Non-VAT</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('searchItem').onkeyup = function() {
    let q = this.value.toLowerCase();
    document.querySelectorAll('#itemTableBody tr').forEach(function(r) {
        r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
};

document.getElementById('add_item_id').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    document.getElementById('add_product_name').value = selected.dataset.name || '';
    document.getElementById('add_net_size').value = selected.dataset.size || '';
});

document.querySelectorAll('.edit-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('edit_price_list_id').value = this.dataset.id;
        document.getElementById('edit_item_id').value = this.dataset.item_id;
        document.getElementById('edit_product_name').value = this.dataset.product_name;
        document.getElementById('edit_net_size').value = this.dataset.net_size;
        document.getElementById('edit_price_per_pack').value = this.dataset.price_per_pack;
        document.getElementById('edit_price_per_case').value = this.dataset.price_per_case;
        document.getElementById('edit_price_per_piece').value = this.dataset.price_per_piece;
        
        var vatType = this.dataset.vat_type;
        if (vatType === 'vat') {
            document.getElementById('editVat').checked = true;
        } else {
            document.getElementById('editNonVat').checked = true;
        }
    });
});
</script>
