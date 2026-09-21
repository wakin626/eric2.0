<div class="d-flex justify-content-between mb-4">
    <div class="d-flex gap-2">
        <a href="?controller=rnd&action=items" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Items</a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card data-card">
            <div class="card-header"><i class="bi bi-upload me-2"></i>Import ERIC Item Master & Stock</div>
            <div class="card-body">
                <form method="POST" action="?controller=rnd&action=itemImportPreview" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label">Select ERIC Export File (.csv or .xlsx)</label>
                        <input type="file" name="import_file" class="form-control" accept=".csv,.xlsx" required>
                    </div>

                    <div class="alert alert-info py-3 mb-4">
                        <h6 class="alert-heading mb-2"><i class="bi bi-info-circle me-1"></i>Expected Columns</h6>
                        <code>c_item, c_desc, c_type, grade, um, site, qty_on_hand, qty_for_inspect</code>
                        <hr class="my-2">
                        <small><strong>grade</strong> column is ignored during import.</small>
                    </div>

                    <div class="card mb-4" style="border-left: 4px solid var(--accent);">
                        <div class="card-body py-3">
                            <h6 class="card-title mb-2"><i class="bi bi-diagram-3 me-1"></i>Item Type Mapping (c_type → item_type)</h6>
                            <table class="table table-sm mb-0">
                                <thead><tr><th>c_type starts with</th><th>item_type</th><th>Description</th></tr></thead>
                                <tbody>
                                    <tr><td><code>RM</code></td><td><span class="badge bg-danger">RM</span></td><td>Raw Material</td></tr>
                                    <tr><td><code>PM</code></td><td><span class="badge bg-info">PM</span></td><td>Packaging Material</td></tr>
                                    <tr><td><code>FG</code></td><td><span class="badge bg-success">FG</span></td><td>Finished Good</td></tr>
                                    <tr><td><code>SFG</code> or <code>FB</code></td><td><span class="badge bg-warning text-dark">SFG</span></td><td>Semi-Finished Good</td></tr>
                                    <tr><td>Other</td><td><span class="badge bg-secondary">SUPPLIES</span></td><td>Supplies / Misc</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="alert alert-warning py-2 mb-4">
                        <small><i class="bi bi-exclamation-triangle me-1"></i>Items with existing <code>item_code</code> will be updated. New items will be created. Inventory balances are recorded per site (default: MAIN).</small>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="?controller=rnd&action=items" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success"><i class="bi bi-upload me-1"></i>Upload & Preview</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
