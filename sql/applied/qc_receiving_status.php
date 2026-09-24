<?php
require_once __DIR__ . '/../../core/BaseModel.php';

$conn = \App\Core\BaseModel::getConnection();

// Step 1: supplier_orders.status ENUM -> VARCHAR(50) so code statuses
// ('processed', 'partially_received', 'For Inspection', 'rejected') survive
// instead of being coerced to '' by non-strict SQL mode.
$col = $conn->query("SHOW COLUMNS FROM supplier_orders LIKE 'status'")->fetch();
if ($col && stripos($col['Type'], 'enum') === 0) {
    $conn->exec("ALTER TABLE supplier_orders MODIFY status VARCHAR(50) DEFAULT 'pending'");
    echo "supplier_orders.status converted ENUM -> VARCHAR(50).\n";
} else {
    echo "supplier_orders.status already VARCHAR - skipped.\n";
}

// Step 2: receiving_items.supplier_order_id link so QC approve/reject can
// update the exact supplier order row (po_ref parsing was unreliable).
$hasCol = $conn->query("SHOW COLUMNS FROM receiving_items LIKE 'supplier_order_id'")->fetch();
if (!$hasCol) {
    $conn->exec("ALTER TABLE receiving_items ADD COLUMN supplier_order_id INT NULL AFTER dr_invoice_no");
    $conn->exec("ALTER TABLE receiving_items ADD KEY idx_supplier_order_id (supplier_order_id)");
    echo "receiving_items.supplier_order_id column added.\n";
} else {
    echo "receiving_items.supplier_order_id already exists - skipped.\n";
}

// Step 3: Backfill rows whose status was coerced to '' by the old ENUM.
// Received goods go to 'For Inspection' with a Pending QC queue entry -
// every incoming shipment must pass through the inspection queue.
$rows = $conn->query("
    SELECT so.supplier_order_id, so.quantity, so.received_qty, so.received_date,
           so.po_id, so.supplier_name, so.created_by, so.remarks,
           i.item_code, i.item_description, i.item_uom,
           po.customer_po_number
    FROM supplier_orders so
    JOIN items i ON so.item_id = i.item_id
    LEFT JOIN purchase_orders po ON so.po_id = po.po_id
    WHERE so.status = '' OR so.status IS NULL
")->fetchAll();

$backfilled = 0;
foreach ($rows as $r) {
    $sid = (int) $r['supplier_order_id'];
    if (floatval($r['received_qty']) > 0) {
        $conn->prepare("UPDATE supplier_orders SET status = 'For Inspection' WHERE supplier_order_id = ?")
             ->execute([$sid]);

        $exists = $conn->prepare("SELECT id FROM receiving_items WHERE supplier_order_id = ? LIMIT 1");
        $exists->execute([$sid]);
        if (!$exists->fetch()) {
            $poRef = $r['customer_po_number'] ?: ('SO #' . $sid);
            $conn->prepare("
                INSERT INTO receiving_items
                    (po_ref, supplier, item_code, item_name, uom,
                     ordered_qty, received_qty, passed_qty, rejected_qty,
                     lot_number, expiry_date, dr_invoice_no, received_date,
                     remarks, qc_status, supplier_order_id)
                VALUES
                    (:po_ref, :supplier, :item_code, :item_name, :uom,
                     :ordered_qty, :received_qty, 0, 0,
                     NULL, NULL, NULL, :received_date,
                     :remarks, 'PENDING_QC', :supplier_order_id)
            ")->execute([
                'po_ref' => $poRef,
                'supplier' => $r['supplier_name'],
                'item_code' => $r['item_code'],
                'item_name' => $r['item_description'],
                'uom' => $r['item_uom'],
                'ordered_qty' => $r['quantity'],
                'received_qty' => $r['received_qty'],
                'received_date' => $r['received_date'] ?: date('Y-m-d'),
                'remarks' => 'Backfilled from legacy receipt (status was blanked by ENUM mismatch).',
                'supplier_order_id' => $sid,
            ]);
        }
        echo "Order #{$sid} -> For Inspection + Pending QC entry.\n";
    } else {
        $conn->prepare("UPDATE supplier_orders SET status = 'requested' WHERE supplier_order_id = ?")
             ->execute([$sid]);
        echo "Order #{$sid} -> requested (no goods received yet).\n";
    }
    $backfilled++;
}
echo "Backfilled {$backfilled} order(s).\n";
