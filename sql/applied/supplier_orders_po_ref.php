<?php
require_once __DIR__ . '/../../core/BaseModel.php';

$conn = \App\Core\BaseModel::getConnection();

// Explicit PO REF column on supplier_orders so procurement enters the vendor
// purchase order reference directly (New/Process Purchasing PO modals).
// Display falls back to '-' when empty - no auto-generated "SO #n" values.
$hasCol = $conn->query("SHOW COLUMNS FROM supplier_orders LIKE 'po_ref'")->fetch();
if (!$hasCol) {
    $conn->exec("ALTER TABLE supplier_orders ADD COLUMN po_ref VARCHAR(50) NULL AFTER remarks");
    echo "supplier_orders.po_ref column added.\n";
} else {
    echo "supplier_orders.po_ref already exists - skipped.\n";
}
