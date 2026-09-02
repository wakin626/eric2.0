<?php
require_once __DIR__ . '/../core/BaseModel.php';

$conn = \App\Core\BaseModel::getConnection();

echo "Step 1: Fix deliveries with poi=NULL in lot_items but po_id set...\n";

$delStmt = $conn->prepare("SELECT delivery_id, po_id, lot_items FROM deliveries WHERE `remove` = 0 AND lot_items IS NOT NULL");
$delStmt->execute();
$deliveries = $delStmt->fetchAll();

$fixed = 0;
foreach ($deliveries as $del) {
    $items = json_decode($del['lot_items'], true);
    if (!is_array($items)) continue;

    $updatedItems = $items;
    $changed = false;

    foreach ($items as $idx => $li) {
        $lotId = $li['lot_id'] ?? null;
        $poiId = $li['poi_id'] ?? null;

        if (!$lotId) continue;

        if (!$poiId && $del['po_id']) {
            $lotStmt = $conn->prepare("SELECT item_id FROM production_lots WHERE lot_id = ? AND is_removed = 0");
            $lotStmt->execute([$lotId]);
            $lot = $lotStmt->fetch();
            if ($lot && !empty($lot['item_id'])) {
                $poiSt = $conn->prepare("SELECT poi_id FROM purchase_order_items WHERE po_id = ? AND item_id = ? LIMIT 1");
                $poiSt->execute([$del['po_id'], $lot['item_id']]);
                $resolvedPoiId = $poiSt->fetchColumn();
                if (!$resolvedPoiId) {
                    $codeSt = $conn->prepare("SELECT item_code FROM items WHERE item_id = ?");
                    $codeSt->execute([$lot['item_id']]);
                    $itemCode = $codeSt->fetchColumn();
                    if ($itemCode) {
                        $poiSt2 = $conn->prepare("SELECT poi.poi_id FROM purchase_order_items poi JOIN items i ON poi.item_id = i.item_id WHERE poi.po_id = ? AND i.item_code = ? LIMIT 1");
                        $poiSt2->execute([$del['po_id'], $itemCode]);
                        $resolvedPoiId = $poiSt2->fetchColumn();
                    }
                }
                if ($resolvedPoiId) {
                    $updatedItems[$idx]['poi_id'] = intval($resolvedPoiId);
                    $poiId = $resolvedPoiId;
                    $changed = true;
                    echo "  del#" . $del['delivery_id'] . ": Resolved lot#$lotId -> poi#$poiId from po#{$del['po_id']} + item#{$lot['item_id']}\n";
                }
            }
        }

        if ($poiId) {
            $lotStmt2 = $conn->prepare("SELECT poi_id, po_id FROM production_lots WHERE lot_id = ? AND is_removed = 0");
            $lotStmt2->execute([$lotId]);
            $lotData = $lotStmt2->fetch();
            if ($lotData && empty($lotData['poi_id'])) {
                $upd = $conn->prepare("UPDATE production_lots SET poi_id = ?, po_id = ? WHERE lot_id = ?");
                $upd->execute([$poiId, $del['po_id'], $lotId]);
                $fixed++;
                echo "  Linked lot#$lotId -> poi#$poiId, po#" . $del['po_id'] . "\n";
            }
        }
    }

    if ($changed) {
        $updDel = $conn->prepare("UPDATE deliveries SET lot_items = ?, poi_id = ? WHERE delivery_id = ?");
        $updDel->execute([json_encode($updatedItems), $updatedItems[0]['poi_id'] ?? null, $del['delivery_id']]);
        echo "  Updated delivery #" . $del['delivery_id'] . "\n";
    }
}
echo "Fixed $fixed lots.\n\n";

echo "Step 2: Recalculating produced_quantity on purchase_order_items...\n";
$conn->exec("UPDATE purchase_order_items poi SET produced_quantity = (
    SELECT COALESCE(SUM(pl.quantity_produced), 0) FROM production_lots pl
    WHERE pl.poi_id = poi.poi_id AND pl.is_removed = 0
)");
echo "Done.\n\n";

echo "Step 3: Recalculating delivered_quantity on purchase_order_items...\n";
$conn->exec("UPDATE purchase_order_items poi SET delivered_quantity = GREATEST(0,
    (SELECT COALESCE(SUM(d.delivery_quantity), 0) FROM deliveries d
    WHERE d.poi_id = poi.poi_id AND d.`remove` = 0)
    - COALESCE((SELECT SUM(b.quantity) FROM backloads b WHERE b.poi_id = poi.poi_id AND b.`remove` = 0), 0)
)");
echo "Done.\n\n";

echo "Step 4: Recalculating PO-level produced_quantity...\n";
$conn->exec("UPDATE purchase_orders po SET produced_quantity = (
    SELECT COALESCE(SUM(produced_quantity), 0) FROM purchase_order_items WHERE po_id = po.po_id
)");
echo "Done.\n\n";

echo "Step 5: Recalculating PO-level delivered_quantity...\n";
$conn->exec("UPDATE purchase_orders po SET delivered_quantity = (
    SELECT COALESCE(SUM(delivered_quantity), 0) FROM purchase_order_items WHERE po_id = po.po_id
)");
echo "Done.\n\n";

echo "Verifying results...\n";
$check = $conn->query("SELECT poi_id, po_id, item_id, quantity, produced_quantity, delivered_quantity FROM purchase_order_items ORDER BY poi_id");
while ($r = $check->fetch()) {
    echo "  poi#{$r['poi_id']} po={$r['po_id']} item={$r['item_id']} qty={$r['quantity']} produced={$r['produced_quantity']} delivered={$r['delivered_quantity']}\n";
}
echo "\nAll done!\n";
