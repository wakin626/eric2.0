<div class="card quick-add">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i><?= isset($customer) ? 'Edit Customer' : 'Add New Customer' ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" autocomplete="off">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Customer Code <span class="text-danger">*</span></label>
                    <input type="text" name="customer_code" class="form-control" 
                           value="<?= htmlspecialchars($customer['customer_code'] ?? '') ?>" 
                           placeholder="e.g., CUST-001" required autofocus>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                    <input type="text" name="customer_name" class="form-control" 
                           value="<?= htmlspecialchars($customer['customer_name'] ?? '') ?>" 
                           placeholder="Enter customer name" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Delivery Address</label>
                    <textarea name="customer_address" class="form-control" rows="2" 
                              placeholder="Enter full address"><?= htmlspecialchars($customer['customer_address'] ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select name="customer_type" class="form-select">
                        <option value="vat" <?= ($customer['customer_type'] ?? 'vat') === 'vat' ? 'selected' : '' ?>>VAT %</option>
                        <option value="non_vat" <?= ($customer['customer_type'] ?? '') === 'non_vat' ? 'selected' : '' ?>>Non-VAT</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">TIN Number</label>
                    <input type="text" name="customer_tin" class="form-control" 
                           value="<?= htmlspecialchars($customer['customer_tin'] ?? '') ?>" 
                           placeholder="e.g., 123-456-789">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Terms (Days)</label>
                    <input type="number" name="customer_terms" class="form-control" min="0"
                           value="<?= $customer['customer_terms'] ?? 0 ?>" 
                           placeholder="e.g. 30">
                </div>
                <?php if (isset($customer)): ?>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="1" <?= ($customer['status'] ?? 1) == 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= ($customer['status'] ?? 1) == 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> <?= isset($customer) ? 'Update Customer' : 'Save Customer' ?>
                    </button>
                    <a href="?controller=admin&action=customers" class="btn btn-secondary ms-2">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>