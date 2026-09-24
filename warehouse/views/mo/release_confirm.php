<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Confirm MO Release</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">You are about to release the following Manufacturing Order. This will change its status to <strong>Released</strong>.</p>

                <table class="table table-bordered mb-3">
                    <tr>
                        <th style="width:180px">MO Number</th>
                        <td><?= htmlspecialchars($mo['mo_number'] ?? '') ?></td>
                    </tr>
                    <tr>
                        <th>Customer</th>
                        <td><?= htmlspecialchars($mo['customer_name'] ?? $mo['customer_code'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>MO Type</th>
                        <td><?= htmlspecialchars($mo['mo_type'] ?? 'Standard') ?></td>
                    </tr>
                    <tr>
                        <th>Order Date</th>
                        <td><?= !empty($mo['order_date']) ? date('Y-m-d', strtotime($mo['order_date'])) : '-' ?></td>
                    </tr>
                    <tr>
                        <th>Due Date</th>
                        <td><?= !empty($mo['due_date']) ? date('Y-m-d', strtotime($mo['due_date'])) : '-' ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><span class="badge bg-info"><?= htmlspecialchars($mo['mo_status'] ?? 'Planned') ?></span></td>
                    </tr>
                </table>

                <?php if (!empty($items)): ?>
                <h6>Line Items:</h6>
                <table class="table table-sm table-bordered mb-3">
                    <thead class="table-light">
                        <tr>
                            <th>Item Code</th>
                            <th>Description</th>
                            <th>UOM</th>
                            <th>BOM Code</th>
                            <th class="text-end">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['item_code'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($item['item_description'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($item['uom'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($item['bom_code'])): ?>
                                    <span class="text-muted"><?= htmlspecialchars($item['bom_code']) ?></span>
                                <?php else: ?>
                                    <small class="text-danger">No BOM</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= number_format($item['qty_ordered'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <!-- BOM Feasibility Check -->
                <?php if (!empty($breakdown)): ?>
                <div class="card mb-3 <?= $validation['valid'] ? 'border-success' : 'border-danger' ?>">
                    <div class="card-header <?= $validation['valid'] ? 'bg-success text-white' : 'bg-danger text-white' ?>">
                        <i class="bi bi-<?= $validation['valid'] ? 'check-circle' : 'exclamation-triangle' ?> me-1"></i>
                        BOM Feasibility Check
                    </div>
                    <div class="card-body">
                        <?php if ($validation['valid']): ?>
                            <p class="text-success mb-0"><strong>All materials available.</strong> This MO can be safely released.</p>
                        <?php else: ?>
                            <p class="text-danger fw-bold mb-2">The following materials lack sufficient stock:</p>
                            <table class="table table-sm table-bordered mb-3">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item Code</th>
                                        <th>Description</th>
                                        <th class="text-end">Required</th>
                                        <th class="text-end">Available</th>
                                        <th class="text-end">Short By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($validation['shortages'] as $s): ?>
                                    <tr class="table-danger">
                                        <td><?= htmlspecialchars($s['item_code']) ?></td>
                                        <td><?= htmlspecialchars($s['item_description'] ?? $s['reason'] ?? '') ?></td>
                                        <td class="text-end"><?= $s['required'] > 0 ? number_format($s['required'], 2) : '-' ?></td>
                                        <td class="text-end"><?= $s['available'] > 0 ? number_format($s['available'], 2) : '-' ?></td>
                                        <td class="text-end fw-bold">
                                            <?php if ($s['short_by'] > 0): ?>
                                                <?= number_format($s['short_by'], 2) ?> <?= htmlspecialchars($s['uom'] ?? '') ?>
                                            <?php else: ?>
                                                <?= htmlspecialchars($s['reason'] ?? '') ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                        <!-- Inline component breakdown -->
                        <details class="mt-2">
                            <summary class="text-muted small" style="cursor:pointer">Show full component breakdown</summary>
                            <div class="mt-2">
                            <?php foreach ($breakdown as $item): ?>
                                <?php if ($item['bom_status'] !== 'ok' || empty($item['components'])) continue; ?>
                                <div class="card mb-2">
                                    <div class="card-header py-1 px-2 d-flex justify-content-between align-items-center small">
                                        <strong><?= htmlspecialchars($item['item_code']) ?> &mdash; <?= htmlspecialchars($item['item_description']) ?></strong>
                                        <span>
                                            BOM: <?= htmlspecialchars($item['bom_code']) ?>
                                            | Fill: <?= $item['fill_volume'] ?? $item['batch_qty'] ?> <?= htmlspecialchars($item['uom'] ?? $item['batch_uom'] ?? '') ?>
                                            <?php if (!empty($item['is_legacy_formula'])): ?>
                                                | Legacy batches: <?= $item['batches_needed'] ?>
                                            <?php else: ?>
                                                | UOM Div: <?= number_format($item['batch_unit_divisor'] ?? 1000, 0) ?>
                                                | Bulk: <?= number_format($item['bulk_batch'] ?? 0, 3) ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-sm mb-0">
                                            <thead class="table-light">
                                                <tr><th>Component</th><th>Description</th><th class="text-end">Required</th><th class="text-end">SOH</th><th class="text-end">Allocated</th><th class="text-end">Net Available</th><th>Status</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($item['components'] as $c): ?>
                                                <tr>
                                                    <td class="small"><?= htmlspecialchars($c['item_code']) ?></td>
                                                    <td class="small"><?= htmlspecialchars($c['item_description']) ?></td>
                                                    <td class="text-end small"><?= number_format($c['required_qty'], 4) ?></td>
                                                    <td class="text-end small"><?= number_format($c['soh'], 4) ?></td>
                                                    <td class="text-end small"><?= number_format($c['allocated'] ?? 0, 4) ?></td>
                                                    <td class="text-end small"><?= number_format($c['net_available'], 4) ?></td>
                                                    <td>
                                                        <?php if ($c['status'] === 'available'): ?>
                                                            <span class="badge bg-success">Available</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Lacking <?= number_format($c['shortage'], 2) ?> <?= htmlspecialchars($c['item_uom'] ?? '') ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </details>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Admin Force Release (only when shortages exist) -->
                <?php if (!$validation['valid'] && $isAdmin): ?>
                <div class="card border-warning mb-3">
                    <div class="card-header bg-warning text-dark">
                        <i class="bi bi-shield-exclamation me-1"></i>Admin Force Release
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-2">Stock shortages detected. As an admin, you may override this block. A remark is <strong>required</strong> for audit purposes.</p>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="confirmForceRelease" name="force_release" value="1">
                            <label class="form-check-label fw-bold text-danger" for="confirmForceRelease">
                                I confirm I want to force-release this MO despite stock shortages
                            </label>
                        </div>
                        <textarea class="form-control" name="force_reason" rows="2" placeholder="Mandatory reason for force release..." required></textarea>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <?php if (!$validation['valid'] && !$isAdmin): ?>
                    <div class="alert alert-danger mb-0">
                        <i class="bi bi-lock me-1"></i><strong>Release blocked.</strong> Resolve stock shortages via MRP before releasing. Only admin users can force-release.
                    </div>
                <?php else: ?>
                <div class="d-flex gap-2">
                    <form method="POST" action="?controller=mo&action=releaseExecute">
                        <input type="hidden" name="mo_id" value="<?= (int) ($mo['mo_id'] ?? 0) ?>">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i><?= $validation['valid'] ? 'Yes, Release MO' : 'Force Release MO' ?>
                        </button>
                    </form>
                    <a href="?controller=mo&action=index" class="btn btn-secondary">
                        <i class="bi bi-x-lg me-1"></i>Cancel
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
