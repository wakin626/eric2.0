<?php
require_once __DIR__ . '/../../core/BaseModel.php';

$conn = \App\Core\BaseModel::getConnection();

echo "Step 1: Consolidate duplicate production_lots rows (same item_id + lot_number)...\n";

$duplicates = $conn->query("
    SELECT item_id, lot_number, GROUP_CONCAT(lot_id ORDER BY lot_id ASC) as lot_ids,
           SUM(quantity_produced) as total_qty,
           MIN(pcs_per_case) as min_pcs,
           MIN(date_created) as oldest_date,
           MIN(created_by) as oldest_creator
    FROM production_lots
    WHERE is_removed = 0 AND item_id IS NOT NULL
    GROUP BY item_id, lot_number
    HAVING COUNT(*) > 1
")->fetchAll(\PDO::FETCH_ASSOC);

$consolidated = 0;
foreach ($duplicates as $dup) {
    $lotIds = array_map('intval', explode(',', $dup['lot_ids']));
    $keepId = $lotIds[0]; // keep the oldest
    $removeIds = array_slice($lotIds, 1);

    echo "  Lot '{$dup['lot_number']}' (item#{$dup['item_id']}): {$dup['total_qty']} total across " . count($lotIds) . " rows. Keeping lot#$keepId, removing " . implode(',', $removeIds) . "\n";

    // Update the kept lot with the total quantity
    $upd = $conn->prepare("UPDATE production_lots SET quantity_produced = :qty WHERE lot_id = :lot_id");
    $upd->execute(['qty' => intval($dup['total_qty']), 'lot_id' => $keepId]);

    // Soft-delete the duplicate rows
    $placeholders = implode(',', array_fill(0, count($removeIds), '?'));
    $del = $conn->prepare("UPDATE production_lots SET is_removed = 1 WHERE lot_id IN ($placeholders)");
    $del->execute($removeIds);

    // Fix any deliveries referencing removed lot_ids — point them to the kept lot
    foreach ($removeIds as $oldLotId) {
        // Update lot_items JSON in deliveries
        $delStmt = $conn->prepare("SELECT delivery_id, lot_items FROM deliveries WHERE lot_items IS NOT NULL AND `remove` = 0");
        $delStmt->execute();
        while ($r = $delStmt->fetch()) {
            $items = json_decode($r['lot_items'], true);
            if (!is_array($items)) continue;
            $changed = false;
            foreach ($items as &$li) {
                if (isset($li['lot_id']) && intval($li['lot_id']) === $oldLotId) {
                    $li['lot_id'] = $keepId;
                    $changed = true;
                }
            }
            unset($li);
            if ($changed) {
                $conn->prepare("UPDATE deliveries SET lot_items = ? WHERE delivery_id = ?")
                    ->execute([json_encode($items), $r['delivery_id']]);
            }
        }

        // Update backloads referencing the removed lot_id
        $conn->prepare("UPDATE backloads SET lot_id = ? WHERE lot_id = ? AND `remove` = 0")
            ->execute([$keepId, $oldLotId]);
    }

    $consolidated++;
}
echo "Consolidated $consolidated duplicate lot groups.\n\n";

echo "Step 2: Nullify legacy po_id and poi_id bindings on all active lots...\n";

$poUnbind = $conn->exec("UPDATE production_lots SET po_id = NULL WHERE po_id IS NOT NULL AND is_removed = 0");
echo "Unbound $poUnbind lots from po_id.\n";

$poiUnbind = $conn->exec("UPDATE production_lots SET poi_id = NULL WHERE poi_id IS NOT NULL AND is_removed = 0");
echo "Unbound $poiUnbind lots from poi_id.\n\n";

echo "Step 3: Recalculating produced_quantity on purchase_order_items from production_lots...\n";
$conn->exec("
    UPDATE purchase_order_items poi SET produced_quantity = (
        SELECT COALESCE(SUM(pl.quantity_produced), 0) FROM production_lots pl
        WHERE pl.item_id = poi.item_id AND pl.is_removed = 0
    )
");
echo "Done.\n\n";

echo "Step 4: Recalculating PO-level produced_quantity...\n";
$conn->exec("
    UPDATE purchase_orders po SET produced_quantity = (
        SELECT COALESCE(SUM(produced_quantity), 0) FROM purchase_order_items WHERE po_id = po.po_id
    )
");
echo "Done.\n\n";

echo "Verifying lot state...\n";
$check = $conn->query("
    SELECT lot_id, lot_number, item_id, quantity_produced, po_id, poi_id, is_removed
    FROM production_lots WHERE is_removed = 0
    ORDER BY item_id, lot_number
    LIMIT 20
");
while ($r = $check->fetch()) {
    echo "  lot#{$r['lot_id']} {$r['lot_number']} item={$r['item_id']} qty={$r['quantity_produced']} po=" . ($r['po_id'] ?? 'NULL') . " poi=" . ($r['poi_id'] ?? 'NULL') . "\n";
}
echo "\nAll done!\n";
