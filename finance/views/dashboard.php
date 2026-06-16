<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stat-card p-3 h-100">
            <h6 class="text-muted">Total PO</h6>
            <h3><?= $stats['total_pos'] ?? 0 ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card p-3 h-100 border-warning">
            <h6 class="text-muted">Ready to Deliver</h6>
            <h3 class="text-warning"><?= $stats['ready_to_deliver'] ?? 0 ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card p-3 h-100">
            <h6 class="text-muted">Total Deliveries</h6>
            <h3><?= $stats['total_deliveries'] ?? 0 ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card p-3 h-100">
            <h6 class="text-muted">Receipts Attached</h6>
            <h3><?= $stats['total_receipts'] ?? 0 ?></h3>
        </div>
    </div>
</div>

<div class="card data-card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-box-seam me-2"></i>POs Ready to Deliver</span>
        <a href="?controller=finance&action=readyToDeliver" class="btn btn-primary btn-sm">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Customer</th>
                    <th>Total Required</th>
                    <th>Produced</th>
                    <th>Delivered</th>
                    <th>Available</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($ready_to_deliver ?? [], 0, 10) as $po): ?>
                <tr>
                    <td><strong class="text-primary"><?= htmlspecialchars($po['customer_po_number']) ?></strong></td>
                    <td><?= htmlspecialchars($po['customer_name'] ?? '-') ?></td>
                    <td><?= $po['total_quantity'] ?? 0 ?></td>
                    <td><?= $po['produced_quantity'] ?? 0 ?></td>
                    <td><?= $po['delivered_quantity'] ?? 0 ?></td>
                    <td>
                        <span class="badge bg-success"><?= $po['available_for_delivery'] ?? 0 ?></span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-primary" onclick="viewPODetails(<?= $po['po_id'] ?>)" title="View Details">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($ready_to_deliver)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No POs ready to deliver</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card data-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-truck me-2"></i>Recent Deliveries</span>
        <a href="?controller=finance&action=deliveries" class="btn btn-primary btn-sm">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>PO Number</th>
                    <th>Customer</th>
                    <th>Delivery Date</th>
                    <th>Quantity</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_deliveries ?? [] as $d): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($d['customer_po_number'] ?? '-') ?></strong></td>
                    <td><?= htmlspecialchars($d['customer_name'] ?? '-') ?></td>
                    <td><?= date('Y-m-d', strtotime($d['delivery_date'])) ?></td>
                    <td><?= $d['delivery_quantity'] ?? 0 ?></td>
                    <td class="text-center">
                        <a href="?controller=finance&action=viewDelivery&id=<?= $d['delivery_id'] ?>" class="btn btn-sm btn-outline-success" title="Attach Receipt">
                            <i class="bi bi-paperclip"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recent_deliveries)): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No deliveries yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

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
                <a href="?controller=finance&action=viewPO&id=${po.po_id}" class="btn btn-primary btn-sm">View Full Details</a>
            </div>`;

            body.innerHTML = html;
        });
}
</script>
