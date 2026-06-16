<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="text-muted">Showing <?= count($purchase_orders) ?> of <?= $total ?> purchase orders</span>
    </div>
    <div class="search-box" style="width: 300px;">
        <i class="bi bi-search"></i>
        <input type="text" id="searchPO" class="form-control" placeholder="Search PO...">
    </div>
</div>

<div class="card data-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="sortable" data-sort="po_number">PO Number</th>
                    <th>Customer</th>
                    <th class="sortable" data-sort="total">Total Qty</th>
                    <th class="sortable" data-sort="produced">Produced</th>
                    <th class="sortable" data-sort="delivered">Delivered</th>
                    <th>Status</th>
                    <th class="sortable" data-sort="date">Date Created</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody id="poTableBody">
                <?php foreach ($purchase_orders as $po): 
                    $produced = $po['produced_quantity'] ?? 0;
                    $total = $po['total_quantity'] ?? 0;
                    $delivered = $po['delivered_quantity'] ?? 0;
                    $available = $produced - $delivered;
                    if ($available < 0) $available = 0;
                    $percent = $total > 0 ? round(($produced / $total) * 100) : 0;
                ?>
                <tr>
                    <td><strong class="text-primary"><?= htmlspecialchars($po['customer_po_number']) ?></strong></td>
                    <td><?= htmlspecialchars($po['customer_name'] ?? '-') ?></td>
                    <td><?= $total ?></td>
                    <td><?= $produced ?></td>
                    <td><?= $delivered ?></td>
                    <td>
                        <?php if ($available > 0): ?>
                            <span class="badge bg-warning">Ready to Deliver</span>
                        <?php elseif ($delivered >= $total && $total > 0): ?>
                            <span class="badge bg-success">Fully Delivered</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('Y-m-d', strtotime($po['date_created'])) ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewPODetails(<?= $po['po_id'] ?>)" title="View Details">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($purchase_orders)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No purchase orders found</td></tr>
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
            <a class="page-link" href="?controller=finance&action=purchaseOrders&page=<?= $i ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<div class="modal fade" id="viewPOModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-eye me-2"></i>PO Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="poDetailBody">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('searchPO').addEventListener('keyup', function() {
    const query = this.value.toLowerCase();
    document.querySelectorAll('#poTableBody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
});

document.querySelectorAll('.sortable').forEach(th => {
    th.style.cursor = 'pointer';
    th.addEventListener('click', function() {
        const table = document.querySelector('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const idx = Array.from(this.parentNode.children).indexOf(this);
        const asc = this.dataset.sort !== 'asc';
        this.dataset.sort = asc ? 'asc' : 'desc';

        rows.sort((a, b) => {
            let aVal = a.children[idx].textContent.trim();
            let bVal = b.children[idx].textContent.trim();
            if (!isNaN(aVal) && !isNaN(bVal)) {
                return asc ? aVal - bVal : bVal - aVal;
            }
            return asc ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
        });
        rows.forEach(r => tbody.appendChild(r));
    });
});

function viewPODetails(poId) {
    const modal = new bootstrap.Modal(document.getElementById('viewPOModal'));
    const body = document.getElementById('poDetailBody');
    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
    modal.show();

    fetch('?controller=finance&action=getPODetails&id=' + poId)
        .then(r => r.json())
        .then(data => {
            const po = data.po;
            const items = data.po_items;
            const deliveries = data.deliveries;
            const receipts = data.receipts;
            const produced = parseInt(po.produced_quantity) || 0;
            const delivered = parseInt(po.delivered_quantity) || 0;
            const total = parseInt(po.total_quantity) || 0;
            const available = produced - delivered;
            const percent = total > 0 ? Math.round((produced / total) * 100) : 0;

            let html = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>PO Number:</strong> ${po.customer_po_number}<br>
                        <strong>Customer:</strong> ${po.customer_name}<br>
                        <strong>Customer Code:</strong> ${po.customer_code || '-'}
                    </div>
                    <div class="col-md-6">
                        <strong>Total Required:</strong> ${total}<br>
                        <strong>Produced:</strong> ${produced}<br>
                        <strong>Delivered:</strong> ${delivered}<br>
                        <strong>Available:</strong> <span class="badge bg-success">${available > 0 ? available : 0}</span>
                    </div>
                </div>
                <div class="progress mb-3" style="height: 20px;">
                    <div class="progress-bar ${percent >= 100 ? 'bg-success' : 'bg-warning'}" style="width: ${percent}%">${percent}%</div>
                </div>
                <hr>
                <h6><i class="bi bi-box me-1"></i>Order Items</h6>
                <table class="table table-sm table-bordered mb-3">
                    <thead><tr><th>Item</th><th>Description</th><th>Qty</th></tr></thead>
                    <tbody>`;
            items.forEach(item => {
                html += `<tr>
                    <td>${item.item_code}</td>
                    <td>${item.item_description}</td>
                    <td>${item.quantity}</td>
                </tr>`;
            });
            html += `</tbody></table>`;

            if (deliveries && deliveries.length > 0) {
                html += `<h6><i class="bi bi-truck me-1"></i>Delivery History</h6>
                <table class="table table-sm table-bordered mb-3">
                    <thead><tr><th>Date</th><th>Qty</th><th>By</th><th>Remarks</th></tr></thead>
                    <tbody>`;
                deliveries.forEach(d => {
                    html += `<tr>
                        <td>${d.delivery_date}</td>
                        <td>${d.delivery_quantity}</td>
                        <td>${d.delivered_by_name || '-'}</td>
                        <td>${d.remarks || '-'}</td>
                    </tr>`;
                });
                html += `</tbody></table>`;
            }

            if (receipts && receipts.length > 0) {
                html += `<h6><i class="bi bi-paperclip me-1"></i>Delivery Receipts</h6>
                <ul class="list-group mb-3">`;
                receipts.forEach(r => {
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-file-earmark me-2"></i>${r.file_name}</span>
                        <a href="${r.file_path}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                    </li>`;
                });
                html += `</ul>`;
            }

            html += `<div class="text-end">
                <a href="?controller=finance&action=viewDelivery&id=${po.po_id}" class="btn btn-primary btn-sm">View Full Details</a>
            </div>`;

            body.innerHTML = html;
        });
}
</script>
