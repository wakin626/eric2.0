<div class="d-flex justify-content-between mb-4">
    <div class="d-flex gap-2">
        <?php if ($canEdit ?? false): ?>
        <a href="?controller=warehouse&action=itemImportForm" class="btn btn-success"><i class="bi bi-upload me-1"></i>Import ERIC Items</a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="bi bi-plus-lg me-1"></i> Add Item
        </button>
        <?php endif; ?>
    </div>
    <form method="GET" class="d-flex gap-2" style="width:50%">
        <input type="hidden" name="controller" value="warehouse">
        <input type="hidden" name="action" value="items">
        <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search code, description..." value="<?= htmlspecialchars($search ?? '') ?>" data-server-search="<?= htmlspecialchars($search ?? '') ?>" autocomplete="off">
        <select name="item_type" class="form-select filter-select" style="width:auto" onchange="this.form.submit()">
            <option value="">All Types</option>
            <option value="RM" <?= ($typeFilter ?? '') === 'RM' ? 'selected' : '' ?>>Raw Material</option>
            <option value="PM" <?= ($typeFilter ?? '') === 'PM' ? 'selected' : '' ?>>Packaging</option>
            <option value="FG" <?= ($typeFilter ?? '') === 'FG' ? 'selected' : '' ?>>Finished Good</option>
            <option value="SFG" <?= ($typeFilter ?? '') === 'SFG' ? 'selected' : '' ?>>SFG - Semi-Finished Goods (Bulk)</option>
            <option value="SUPPLIES" <?= ($typeFilter ?? '') === 'SUPPLIES' ? 'selected' : '' ?>>Supplies</option>
        </select>
        <button type="submit" class="d-none">Search</button>
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
                    <?php if ($canEdit ?? false): ?><th class="text-end">Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody id="itemsTableBody">
                <?php foreach ($items as $item): ?>
                <tr data-item-row>
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
                    <td><?= formatQty($item['total_soh'] ?? 0) ?></td>
                    <td><?= formatQty($item['total_allocated'] ?? 0) ?></td>
                    <td><strong><?= formatQty($item['available_stock'] ?? 0) ?></strong></td>
                    <td>
                        <?php
                        $statusColors = ['OUT OF STOCK' => 'danger', 'LOW STOCK' => 'warning', 'IN STOCK' => 'success'];
                        $color = $statusColors[$item['inventory_status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $color ?>"><?= $item['inventory_status'] ?></span>
                    </td>
                    <?php if ($canEdit ?? false): ?>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary adjust-soh-btn"
                                data-item-id="<?= (int)$item['item_id'] ?>"
                                data-code="<?= htmlspecialchars($item['item_code']) ?>"
                                data-description="<?= htmlspecialchars($item['item_description']) ?>"
                                title="Adjust SOH" aria-label="Adjust SOH">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr data-empty-row><td colspan="<?= ($canEdit ?? false) ? 9 : 8 ?>" class="text-center text-muted py-4">No items found</td></tr>
<?php endif; ?>
                <tr data-no-match-row style="display:none"><td colspan="<?= ($canEdit ?? false) ? 9 : 8 ?>" class="text-center text-muted py-4">No items match your search</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php if (($totalPages ?? 1) > 1): ?>
<?php $pages = \App\Helpers\Pagination::getPageRange($page, $totalPages); ?>
<nav>
    <ul class="pagination justify-content-center mt-4">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?controller=warehouse&action=items&page=<?= $page - 1 ?>&search=<?= urlencode($search ?? '') ?>&item_type=<?= urlencode($typeFilter ?? '') ?>">&laquo; Prev</a>
        </li>
        <?php foreach ($pages as $p): ?>
            <?php if ($p === '...'): ?>
            <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php else: ?>
            <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                <a class="page-link" href="?controller=warehouse&action=items&page=<?= $p ?>&search=<?= urlencode($search ?? '') ?>&item_type=<?= urlencode($typeFilter ?? '') ?>"><?= $p ?></a>
            </li>
            <?php endif; ?>
        <?php endforeach; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?controller=warehouse&action=items&page=<?= $page + 1 ?>&search=<?= urlencode($search ?? '') ?>&item_type=<?= urlencode($typeFilter ?? '') ?>">Next &raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php if ($canEdit ?? false): ?>
<!-- ADD ITEM MODAL -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="?controller=warehouse&action=storeItem">
            <div class="modal-header">
                <h5 class="modal-title" id="addItemModalLabel"><i class="bi bi-plus-lg me-2"></i>Add Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="addItemCode" class="form-label">Item Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="addItemCode" name="code" maxlength="50" placeholder="e.g. RM-0001" required>
                </div>
                <div class="mb-3">
                    <label for="addItemDescription" class="form-label">Description <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="addItemDescription" name="description" maxlength="255" placeholder="Item description" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="addItemType" class="form-label">Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="addItemType" name="type" required>
                            <option value="">Select type...</option>
                            <option value="RM">RM - Raw Material</option>
                            <option value="PM">PM - Packaging</option>
                            <option value="FG">FG - Finished Good</option>
                            <option value="SFG">SFG - Semi-Finished Good</option>
                            <option value="SUPPLIES">SUPPLIES</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="addItemUom" class="form-label">UOM <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="addItemUom" name="uom" list="addItemUomList" maxlength="50" placeholder="e.g. Kg" required>
                        <datalist id="addItemUomList">
                            <option value="Kg"></option>
                            <option value="g"></option>
                            <option value="L"></option>
                            <option value="mL"></option>
                            <option value="Pc"></option>
                            <option value="PCS"></option>
                            <option value="CS"></option>
                            <option value="Ltr"></option>
                        </datalist>
                    </div>
                </div>
                <div class="mb-1">
                    <label for="addItemSoh" class="form-label">Initial SOH</label>
                    <input type="number" class="form-control" id="addItemSoh" name="soh" step="0.0001" min="0" value="0.0000">
                    <small class="text-muted">Status is derived automatically: IN STOCK when SOH &gt; 0, OUT OF STOCK when 0.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Create Item</button>
            </div>
        </form>
    </div>
</div>

<!-- ADJUST SOH MODAL -->
<div class="modal fade" id="adjustSohModal" tabindex="-1" aria-labelledby="adjustSohModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="?controller=warehouse&action=adjustItemSoh">
            <input type="hidden" name="item_id" id="adjustSohItemId" value="">
            <input type="hidden" name="back_search" value="<?= htmlspecialchars($search ?? '') ?>">
            <input type="hidden" name="back_type" value="<?= htmlspecialchars($typeFilter ?? '') ?>">
            <input type="hidden" name="back_page" value="<?= (int)($page ?? 1) ?>">
            <div class="modal-header">
                <h5 class="modal-title" id="adjustSohModalLabel"><i class="bi bi-pencil-square me-2"></i>Adjust SOH</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="fw-bold text-primary" id="adjustSohCode">&mdash;</div>
                    <div class="text-muted small" id="adjustSohDesc"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <span class="text-muted small">Total SOH (all sites)</span>
                        <div class="fw-semibold" id="adjustSohTotal">&mdash;</div>
                    </div>
                    <div class="col-6">
                        <span class="text-muted small">Allocated</span>
                        <div class="fw-semibold" id="adjustSohAllocated">&mdash;</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="adjustSohSite" class="form-label">Site</label>
                    <select class="form-select" id="adjustSohSite" name="site_code" required>
                        <option value="">Loading...</option>
                    </select>
                </div>
                <div class="mb-1">
                    <label for="adjustSohQty" class="form-label">New SOH for this site <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="adjustSohQty" name="qty_on_hand" step="0.0001" min="0" required>
                    <small class="text-muted">Sets <code>qty_on_hand</code> for the selected site only &mdash; the item's total SOH is the sum of all sites. Allocated quantity is not changed.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput') || document.querySelector('input[placeholder*="Search"]');
    const tbody = document.getElementById('itemsTableBody');
    if (!searchInput || !tbody) return;

    const form = searchInput.form;
    const serverSearch = searchInput.dataset.serverSearch || '';
    const rows = tbody.querySelectorAll('tr[data-item-row]');
    const noMatchRow = tbody.querySelector('tr[data-no-match-row]');
    let debounceTimer = null;

    function filterRows() {
        const query = searchInput.value.toLowerCase().trim();
        let visible = 0;
        rows.forEach(function(row) {
            const code = (row.children[0] ? row.children[0].textContent : '').toLowerCase();
            const desc = (row.children[1] ? row.children[1].textContent : '').toLowerCase();
            const match = code.includes(query) || desc.includes(query);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        if (noMatchRow) {
            noMatchRow.style.display = (query !== '' && rows.length > 0 && visible === 0) ? '' : 'none';
        }
    }

    function submitServerSearch() {
        try { sessionStorage.setItem('itemsSearchFocus', '1'); } catch (err) {}
        form.submit();
    }

    searchInput.addEventListener('input', function() {
        filterRows();
        clearTimeout(debounceTimer);
        if (searchInput.value === serverSearch) return;
        debounceTimer = setTimeout(submitServerSearch, 600);
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(debounceTimer);
            if (searchInput.value !== serverSearch) submitServerSearch();
        }
    });

    filterRows();

    if (sessionStorage.getItem('itemsSearchFocus')) {
        sessionStorage.removeItem('itemsSearchFocus');
        searchInput.focus();
        const len = searchInput.value.length;
        searchInput.setSelectionRange(len, len);
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('adjustSohModal');
    if (!modalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const idInput = document.getElementById('adjustSohItemId');
    const codeEl = document.getElementById('adjustSohCode');
    const descEl = document.getElementById('adjustSohDesc');
    const totalEl = document.getElementById('adjustSohTotal');
    const allocEl = document.getElementById('adjustSohAllocated');
    const siteSelect = document.getElementById('adjustSohSite');
    const qtyInput = document.getElementById('adjustSohQty');

    function fmtQty(v) {
        const n = Number(v || 0);
        return n.toLocaleString(undefined, { maximumFractionDigits: 4 });
    }

    function fillSites(balances) {
        const sites = {};
        balances.forEach(function(b) { sites[b.site_code] = Number(b.qty_on_hand); });
        if (!('001' in sites)) sites['001'] = null;

        siteSelect.innerHTML = '';
        Object.keys(sites).forEach(function(code) {
            const qty = sites[code];
            const opt = document.createElement('option');
            opt.value = code;
            if (qty === null) {
                opt.textContent = code + ' (new)';
            } else {
                opt.textContent = code + ' \u2014 ' + fmtQty(qty);
                opt.dataset.qty = qty;
            }
            siteSelect.appendChild(opt);
        });

        const firstStocked = Array.from(siteSelect.options).find(function(o) { return o.dataset.qty !== undefined; });
        siteSelect.value = (firstStocked || siteSelect.options[0]).value;
        const sel = siteSelect.selectedOptions[0];
        qtyInput.value = sel && sel.dataset.qty !== undefined ? sel.dataset.qty : 0;
    }

    siteSelect.addEventListener('change', function() {
        const opt = siteSelect.selectedOptions[0];
        qtyInput.value = opt && opt.dataset.qty !== undefined ? opt.dataset.qty : 0;
    });

    document.querySelectorAll('.adjust-soh-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const itemId = btn.dataset.itemId;
            idInput.value = itemId;
            codeEl.textContent = btn.dataset.code;
            descEl.textContent = btn.dataset.description;
            totalEl.textContent = '\u2014';
            allocEl.textContent = '\u2014';
            siteSelect.innerHTML = '<option value="">Loading...</option>';
            qtyInput.value = '';
            qtyInput.disabled = true;
            modal.show();

            fetch('?controller=warehouse&action=getItemBalances&item_id=' + encodeURIComponent(itemId))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success) throw new Error(data.message || 'Failed to load item.');
                    totalEl.textContent = fmtQty(data.total_soh);
                    allocEl.textContent = fmtQty(data.total_allocated);
                    fillSites(data.balances || []);
                    qtyInput.disabled = false;
                })
                .catch(function(err) {
                    siteSelect.innerHTML = '<option value="">Failed to load balances</option>';
                    console.error(err);
                });
        });
    });
});
</script>
