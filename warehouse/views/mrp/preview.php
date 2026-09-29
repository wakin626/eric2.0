<?php
// formatQty() is global (app/helpers/format_helper.php, required by index.php);
// this guard only matters if the view is ever rendered standalone.
if (!function_exists('formatQty')) {
    function formatQty($val, $maxDecimals = 4) {
        $formatted = number_format((float)$val, $maxDecimals, '.', ',');
        return str_contains($formatted, '.') ? rtrim(rtrim($formatted, '0'), '.') : $formatted;
    }
}

$mrpLackingRows = [];
if (!empty($mrpSection['components'])) {
    foreach ($mrpSection['components'] as $mrpRow) {
        if (floatval($mrpRow['lacking_qty']) > 0) {
            $mrpLackingRows[] = [
                'component_item_id' => (int)$mrpRow['component_item_id'],
                'lacking_qty' => $mrpRow['lacking_qty'],
            ];
        }
    }
}
$showSaveBtn = !empty($didCalculate) && !empty($mrpSection) && !empty($mrpSection['components']);
?>
<style>
.mrp-section { page-break-inside: avoid; margin-bottom: 20px; }
.mrp-fg-header { background: #e8ecef; padding: 8px 12px; border-radius: 4px; margin-bottom: 8px; font-size: 0.9rem; }
.mrp-fg-header strong { color: #1a1a2e; }
.mrp-meta { font-size: 0.8rem; color: #555; margin-top: 4px; }
.mrp-table th { background: #f0f0f0; font-size: 0.78rem; white-space: nowrap; }
.mrp-table td { font-size: 0.8rem; }
.mrp-table .num { text-align: right; font-variant-numeric: tabular-nums; }
.mrp-lacking { color: #dc3545; font-weight: 700; }
.mrp-ok { color: #198754; font-weight: 600; }
.mrp-shortage-row { background: #fff8e1 !important; }
.mrp-empty { text-align: center; padding: 40px; color: #888; }
.mrp-signature { margin-top: 30px; display: flex; justify-content: space-between; gap: 20px; }
.mrp-signature .sig-block { flex: 1; }
.mrp-signature .sig-line { border-top: 1px solid #333; margin-top: 35px; padding-top: 5px; font-size: 0.8rem; }
.mrp-signature .sig-role { font-size: 0.7rem; color: #666; margin-top: 2px; }
.mrp-cat-badge { font-size: 0.7rem; }
@media print {
    .no-print { display: none !important; }
    .main-wrapper { margin: 0 !important; padding: 10px !important; }
    .sidebar { display: none !important; }
    .mrp-section { page-break-inside: avoid; }
    body { font-size: 10px; }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h4 class="mb-0"><i class="bi bi-calculator me-2"></i>MRP Sheet — Material Requirements</h4>
    <div class="d-flex gap-2">
        <?php if (!empty($poHeader)): ?>
        <?php if (!empty($hasExistingSnapshot)): ?>
            <span class="btn btn-success disabled">
                <i class="bi bi-check-circle me-1"></i>Snapshot Saved
            </span>
        <?php else: ?>
        <form method="POST" action="?controller=warehouse&action=saveMrpSnapshot" class="d-inline" id="saveMrpForm">
            <input type="hidden" name="po_id" value="<?= $selectedPO ?>">
            <input type="hidden" name="customer_id" value="<?= $selectedCustomer ?>">
            <button type="submit" class="btn btn-success" id="saveMrpBtn" onclick="return confirm('Save current MRP as snapshot?')">
                <i class="bi bi-save me-1"></i>Save Snapshot
            </button>
        </form>
        <script>
        document.getElementById('saveMrpForm')?.addEventListener('submit', function() {
            var btn = document.getElementById('saveMrpBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';
        });
        </script>
        <?php endif; ?>
        <?php endif; ?>
        <a href="?controller=warehouse&action=mrpHistory" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i>History
        </a>
        <?php if (!empty($poHeader)): ?>
        <a href="?controller=warehouse&action=mrpPDF&customer_id=<?= $selectedCustomer ?>&po_id=<?= $selectedPO ?>" class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>Print PDF
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Selection Bar: Customer + FG + Target Qty -->
<div class="card data-card mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="controller" value="warehouse">
            <input type="hidden" name="action" value="mrp">

            <div class="col-md-3">
                <label class="form-label fw-bold">Select Customer</label>
                <select name="customer_id" id="mrpCustomer" class="form-select filter-select">
                    <option value="">-- All Customers --</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['customer_id'] ?>" <?= ($selectedCustomer == $c['customer_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['customer_code'] . ' - ' . $c['customer_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">Select Finished Good / SFG</label>
                <select name="fg_item_id" id="mrpFg" class="form-select filter-select" <?= empty($fgOptions) ? 'disabled' : '' ?>>
                    <?php if (empty($fgOptions)): ?>
                    <option value=""><?= !empty($noFgsForCustomer) ? 'No FG/SFG with active BOM for this customer' : 'No FG/SFG with active BOM' ?></option>
                    <?php else: ?>
                    <option value="">-- Select Finished Good / SFG --</option>
                    <?php foreach ($fgOptions as $fg): ?>
                    <option value="<?= $fg['item_id'] ?>" <?= ($selectedFg == $fg['item_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($fg['item_code'] . ' — ' . $fg['item_description']) ?>
                    </option>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-bold">Target Quantity / To Produce</label>
                <input type="number" name="target_qty" id="mrpTargetQty" class="form-control"
                       min="0" step="any" placeholder="e.g. 10000"
                       value="<?= $targetQty !== null && $targetQty !== '' ? htmlspecialchars($targetQty) : '' ?>">
            </div>

            <div class="col-md-4">
                <div class="d-flex gap-2 align-items-stretch">
                    <button type="submit" name="calculate" value="1" class="btn btn-primary flex-grow-1"
                            <?= empty($fgOptions) ? 'disabled' : '' ?>>
                        <i class="bi bi-calculator me-1"></i>Calculate
                    </button>
                    <?php if ($showSaveBtn): ?>
                    <button type="button" id="saveMrpCalculationBtn" class="btn btn-success flex-grow-1"
                            data-lacking="<?= htmlspecialchars(json_encode($mrpLackingRows), ENT_QUOTES) ?>"
                            <?= empty($mrpLackingRows) ? 'disabled title="Nothing lacking — no purchase requests needed"' : '' ?>>
                        <i class="bi bi-cart-plus me-1"></i>Save &amp; Transfer Lacking to Purchasing PO
                    </button>
                    <?php endif; ?>
                    <?php if (!empty($selectedCustomer) || !empty($selectedFg)): ?>
                    <a href="?controller=warehouse&action=mrp" class="btn btn-outline-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($noFgsForCustomer)): ?>
<div class="card data-card mb-4 no-print">
    <div class="mrp-empty">
        <i class="bi bi-box-seam" style="font-size: 3rem; opacity: 0.3;"></i>
        <h5 class="mt-3">No FG/SFG with active BOM for this customer</h5>
        <p class="text-muted">Create or activate a BOM for this customer's FG/SFG items, or choose another customer.</p>
    </div>
</div>
<?php endif; ?>

<?php if (empty($fgHeader) || empty($mrpSection)): ?>
<?php if (empty($noFgsForCustomer)): ?>
<div class="card data-card">
    <div class="mrp-empty">
        <i class="bi bi-calculator" style="font-size: 3rem; opacity: 0.3;"></i>
        <h5 class="mt-3">Select a Customer, Finished Good / SFG, and enter Target Quantity to calculate material requirements.</h5>
        <p class="text-muted">Choose a customer, pick an FG/SFG with an active BOM, enter the target production quantity, then click Calculate.</p>
    </div>
</div>
<?php endif; ?>

<?php else: ?>

<!-- FG / Target Header -->
<div class="card data-card mb-4">
    <div class="card-header"><i class="bi bi-info-circle me-2"></i>Calculation Basis</div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"><strong>FG Code:</strong> <code><?= htmlspecialchars($fgHeader['fg_code']) ?></code></div>
            <div class="col-md-4"><strong>Description:</strong> <?= htmlspecialchars($fgHeader['fg_name']) ?></div>
            <div class="col-md-2"><strong>Target Qty:</strong> <?= formatQty($fgHeader['target_qty']) ?> Pcs</div>
            <div class="col-md-3"><strong>BOM:</strong> <?= htmlspecialchars($fgHeader['bom_code']) ?></div>
        </div>
        <div class="row mt-2">
            <div class="col-md-3"><strong>Fill Volume:</strong> <?= formatQty($fgHeader['fill_volume']) ?> <?= htmlspecialchars($fgHeader['uom']) ?></div>
            <div class="col-md-3"><strong>UOM Divisor:</strong> <?= number_format($fgHeader['batch_unit_divisor'], 0) ?></div>
            <?php if (!empty($fgHeader['is_legacy_formula'])): ?>
            <div class="col-md-3"><span class="badge bg-warning text-dark">Legacy formula flag on BOM</span></div>
            <?php endif; ?>
        </div>
        <div class="mrp-meta mt-2 text-muted">
            RM: (Target &times; Fill Volume &divide; Divisor) &times; (Dosage&thinsp;/&thinsp;100) &times; (1 + Wastage&thinsp;/&thinsp;100)
            &nbsp;|&nbsp;
            PM/SFG: Target &times; Dosage &times; (1 + Wastage&thinsp;/&thinsp;100)
            &nbsp;|&nbsp;
            Lacking = max(0, Required &minus; (SOH &minus; Allocated))
        </div>
    </div>
</div>

<?php if (empty($mrpSection['components'])): ?>
<div class="card data-card">
    <div class="mrp-empty">
        <h5>No BOM components found</h5>
        <p class="text-muted">Add components to this finished good's BOM.</p>
    </div>
</div>
<?php else: ?>

<!-- Components Table -->
<div class="card data-card mb-4 mrp-section">
    <div class="card-header">
        <i class="bi bi-list-check me-2"></i>
        <strong>Material Requirements — <?= htmlspecialchars($mrpSection['fg_code'] . ' - ' . $mrpSection['fg_name']) ?></strong>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0 mrp-table">
            <thead>
                <tr>
                    <th>Component Code</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th class="num">Required Qty</th>
                    <th class="num">SOH</th>
                    <th class="num">Allocated</th>
                    <th class="num">Available</th>
                    <th class="num">Lacking Qty</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mrpSection['components'] as $row): ?>
                <tr class="<?= $row['lacking_qty'] > 0 ? 'mrp-shortage-row' : '' ?>">
                    <td><code><?= htmlspecialchars($row['item_code']) ?></code></td>
                    <td><?= htmlspecialchars($row['item_description']) ?></td>
                    <td><span class="badge bg-light text-dark border mrp-cat-badge"><?= htmlspecialchars($row['category']) ?></span></td>
                    <td class="num"><?= formatQty($row['required_qty']) ?> <?= htmlspecialchars($row['display_uom'] ?? $row['item_uom']) ?></td>
                    <td class="num"><?= formatQty($row['soh']) ?></td>
                    <td class="num"><?= formatQty($row['allocated']) ?></td>
                    <td class="num <?= $row['available'] >= $row['required_qty'] ? 'text-success' : 'text-danger' ?> fw-bold"><?= formatQty($row['available']) ?></td>
                    <td class="num <?= $row['lacking_qty'] > 0 ? 'text-danger fw-bold' : 'text-success' ?>">
                        <?= formatQty($row['lacking_qty']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Signature Blocks -->
<div class="card data-card no-print">
    <div class="card-body">
        <div class="mrp-signature">
            <div class="sig-block">
                <div class="sig-line">Prepared By: <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
                <div class="sig-role"><?= htmlspecialchars(ucfirst($_SESSION['department'] ?? '')) ?></div>
            </div>
            <div class="sig-block">
                <div class="sig-line">Reviewed By: _______________</div>
                <div class="sig-role">&nbsp;</div>
            </div>
            <div class="sig-block">
                <div class="sig-line">Approved By: _______________</div>
                <div class="sig-role">&nbsp;</div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>
<?php endif; ?>

<!-- Custom MRP Reference prompt (Save & Transfer) -->
<div class="modal fade" id="mrpRefModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-tag me-2"></i>MRP Reference</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2 text-muted" id="mrpRefModalSummary">Queue lacking material(s) as Purchase Requests for Procurement.</p>
                <label class="form-label fw-bold" for="mrpRefInput">Enter Custom MRP Reference Number (e.g., MRP-2026-001):</label>
                <input type="text" class="form-control" id="mrpRefInput" maxlength="30"
                       placeholder="MRP-2026-001" autocomplete="off" spellcheck="false">
                <div class="text-danger small mt-1 d-none" id="mrpRefError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="mrpRefConfirmBtn">
                    <i class="bi bi-cart-plus me-1"></i>Save &amp; Transfer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var customer = document.getElementById('mrpCustomer');
    var fg = document.getElementById('mrpFg');
    if (customer) {
        customer.addEventListener('change', function () {
            var params = new URLSearchParams(window.location.search);
            params.set('controller', 'warehouse');
            params.set('action', 'mrp');
            if (this.value) {
                params.set('customer_id', this.value);
            } else {
                params.delete('customer_id');
            }
            params.delete('fg_item_id');
            params.delete('target_qty');
            params.delete('calculate');
            window.location.search = params.toString();
        });
    }
    if (fg && typeof window.refreshSearchableDropdown === 'function') {
        window.refreshSearchableDropdown(fg);
    }
    if (customer && typeof window.refreshSearchableDropdown === 'function') {
        window.refreshSearchableDropdown(customer);
    }

    // Save & Transfer Lacking → prompt for a custom MRP reference, then build
    // a POST form (cannot nest inside the GET form). Cancelling or submitting
    // an empty reference aborts the save entirely.
    //
    // Wired on DOMContentLoaded: this view is rendered from $content BEFORE the
    // layout loads public/js/bootstrap.bundle.min.js, so touching `bootstrap`
    // at parse time would throw a ReferenceError and silently skip attaching
    // this handler (same reason the receiving view defers its own modal code).
    document.addEventListener('DOMContentLoaded', function () {
        var saveBtn = document.getElementById('saveMrpCalculationBtn');
        var refModalEl = document.getElementById('mrpRefModal');
        if (!saveBtn || !refModalEl) return;
        var refModal = bootstrap.Modal.getOrCreateInstance(refModalEl);
        var refInput = document.getElementById('mrpRefInput');
        var refError = document.getElementById('mrpRefError');
        var pendingRows = [];

        var showRefError = function (msg) {
            refError.textContent = msg;
            refError.classList.remove('d-none');
        };
        var clearRefError = function () {
            refError.textContent = '';
            refError.classList.add('d-none');
        };
        // Runs whenever the modal closes (Cancel, Esc, backdrop) — unless the
        // save has already been committed and the page is navigating away.
        var resetRefPrompt = function () {
            pendingRows = [];
            refInput.value = '';
            clearRefError();
            if (!saveBtn.dataset.saving) saveBtn.disabled = false;
        };

        saveBtn.addEventListener('click', function () {
            if (saveBtn.disabled) return;   // debounce: ignore any further clicks
            var rows = [];
            try { rows = JSON.parse(saveBtn.getAttribute('data-lacking') || '[]'); } catch (e) {}
            if (!rows.length) return;

            pendingRows = rows;
            document.getElementById('mrpRefModalSummary').textContent =
                'Queue ' + rows.length + ' lacking material(s) as Purchase Requests for Procurement?';
            refInput.value = '';
            clearRefError();
            refModal.show();
        });

        refModalEl.addEventListener('shown.bs.modal', function () { refInput.focus(); });

        var commitRefPrompt = function () {
            var ref = (refInput.value || '').trim();
            if (!ref) {
                showRefError('Enter a custom MRP reference number before saving.');
                refInput.focus();
                return;                       // abort: nothing is saved
            }
            if (ref.length > 30) {
                showRefError('The reference may be at most 30 characters.');
                refInput.focus();
                return;                       // abort
            }
            if (!pendingRows.length) { refModal.hide(); return; }

            // Commit: lock the button so a double click cannot submit twice,
            // then close the prompt and submit the form.
            saveBtn.disabled = true;
            saveBtn.dataset.saving = '1';
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Transferring...';
            refModal.hide();

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '?controller=warehouse&action=saveMrpCalculation';
            var fields = {
                customer_id: <?= (int)($selectedCustomer ?? 0) ?>,
                fg_item_id: <?= (int)($selectedFg ?? 0) ?>,
                target_qty: <?= json_encode((string)($targetQty ?? '')) ?>,
                calculate: '1',
                lacking_json: JSON.stringify(pendingRows),
                custom_mrp_ref: ref
            };
            Object.keys(fields).forEach(function (name) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = fields[name];
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        };

        document.getElementById('mrpRefConfirmBtn').addEventListener('click', commitRefPrompt);
        refInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); commitRefPrompt(); }
        });
        refInput.addEventListener('input', clearRefError);
        refModalEl.addEventListener('hidden.bs.modal', resetRefPrompt);
    });
})();
</script>
