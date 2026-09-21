<?php
require_once __DIR__ . '/../../core/BaseModel.php';

$conn = \App\Core\BaseModel::getConnection();

echo "Step 1: Syncing production_lots.quantity_produced from production_history (source of truth)...\n";

$affected = $conn->exec("
    UPDATE production_lots pl
    SET quantity_produced = (
        SELECT COALESCE(SUM(added_quantity), 0)
        FROM production_history ph
        WHERE ph.lot_number = pl.lot_number
          AND ph.item_id = pl.item_id
          AND ph.is_removed = 0
    )
    WHERE pl.is_removed = 0
");
echo "Updated $affected lot records.\n\n";

echo "Step 2: Recalculating produced_quantity on purchase_order_items...\n";
$conn->exec("
    UPDATE purchase_order_items poi SET produced_quantity = (
        SELECT COALESCE(SUM(pl.quantity_produced), 0) FROM production_lots pl
        WHERE pl.poi_id = poi.poi_id AND pl.is_removed = 0
    )
");
echo "Done.\n\n";

echo "Step 3: Recalculating PO-level produced_quantity...\n";
$conn->exec("
    UPDATE purchase_orders po SET produced_quantity = (
        SELECT COALESCE(SUM(produced_quantity), 0) FROM purchase_order_items WHERE po_id = po.po_id
    )
");
echo "Done.\n\n";

echo "Verifying lot balances...\n";
$check = $conn->query("
    SELECT pl.lot_id, pl.lot_number, pl.item_id, pl.quantity_produced,
           (SELECT COALESCE(SUM(added_quantity), 0) FROM production_history ph
            WHERE ph.lot_number = pl.lot_number AND ph.item_id = pl.item_id AND ph.is_removed = 0) as history_total
    FROM production_lots pl
    WHERE pl.is_removed = 0
    HAVING pl.quantity_produced != history_total
    ORDER BY pl.lot_id
");
$mismatches = 0;
while ($r = $check->fetch()) {
    echo "  MISMATCH lot#{$r['lot_id']} ({$r['lot_number']}): lots={$r['quantity_produced']} history={$r['history_total']}\n";
    $mismatches++;
}
if ($mismatches === 0) {
    echo "  All lot balances are in sync.\n";
}
echo "\nAll done!\n";
