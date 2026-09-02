<?php
/**
 * MIGRATION SCRIPT: Fix phantom lot_id 208 (32,256 pcs) on PO-180
 * 
 * This lot was created by assignExcessToPO as a transfer from PO-167,
 * but it's a phantom — the production didn't actually happen on PO-167.
 * 
 * What this script does:
 * 1. Deletes lot_id 208 from production_lots
 * 2. Updates DR17479 lot_items JSON to reference remaining lots (211+217)
 * 3. Recalculates produced_quantity on PO-180 from production_lots
 * 
 * BEFORE RUNNING: Back up the database!
 */

$host = 'localhost';
$db   = 'manufacturing_mgmt';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->beginTransaction();

    echo "=== Fixing phantom lot_id 208 on PO-180 ===\n\n";

    // 1. Verify lot_id 208 exists
    $stmt = $conn->prepare("SELECT lot_id, po_id, poi_id, lot_number, quantity_produced, transferred_from_po_id FROM production_lots WHERE lot_id = 208");
    $stmt->execute();
    $lot = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$lot) {
        echo "lot_id 208 not found — already cleaned up or different lot_id on live.\n";
        echo "Aborting.\n";
        $conn->rollBack();
        exit;
    }
    echo "Found lot_id 208:\n";
    echo "  po_id: {$lot['po_id']}\n";
    echo "  poi_id: {$lot['poi_id']}\n";
    echo "  lot_number: {$lot['lot_number']}\n";
    echo "  quantity_produced: {$lot['quantity_produced']}\n";
    echo "  transferred_from_po_id: {$lot['transferred_from_po_id']}\n\n";

    // 2. Check remaining lots for 158-249 on PO-180
    $stmt = $conn->prepare("SELECT lot_id, lot_number, quantity_produced, transferred_from_po_id FROM production_lots WHERE po_id = :po_id AND lot_number = '158-249' AND is_removed = 0 AND lot_id != 208");
    $stmt->execute(['po_id' => $lot['po_id']]);
    $remainingLots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Remaining lots for 158-249 on PO-180:\n";
    foreach ($remainingLots as $rl) {
        echo "  lot_id {$rl['lot_id']}: {$rl['quantity_produced']} pcs (transferred_from: {$rl['transferred_from_po_id']})\n";
    }
    echo "\n";

    // 3. Delete lot_id 208
    $stmt = $conn->prepare("DELETE FROM production_lots WHERE lot_id = 208");
    $stmt->execute();
    echo "Deleted lot_id 208 from production_lots.\n\n";

    // 4. Update DR17479 lot_items JSON
    // Find the delivery that references lot_id 208
    $stmt = $conn->prepare("SELECT delivery_id, dr_number, lot_items FROM deliveries WHERE lot_items LIKE '%\"lot_id\":208%' AND remove = 0");
    $stmt->execute();
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($deliveries as $del) {
        echo "Updating {$del['dr_number']} (delivery_id={$del['delivery_id']})...\n";
        $items = json_decode($del['lot_items'], true);
        $newItems = [];
        foreach ($items as $item) {
            if (intval($item['lot_id']) === 208) {
                // Split 23,904 across remaining lots: 211 (12,672) + 217 (11,232)
                $remaining = intval($item['qty']);
                $lot211Qty = min($remaining, 12672);
                $remaining -= $lot211Qty;
                $lot217Qty = min($remaining, 11232);
                $remaining -= $lot217Qty;
                
                if ($lot211Qty > 0) {
                    $newItem = $item;
                    $newItem['lot_id'] = 211;
                    $newItem['qty'] = $lot211Qty;
                    $newItems[] = $newItem;
                }
                if ($lot217Qty > 0) {
                    $newItem = $item;
                    $newItem['lot_id'] = 217;
                    $newItem['qty'] = $lot217Qty;
                    $newItems[] = $newItem;
                }
                echo "  Replaced lot_id 208 ({$item['qty']} pcs) with lot_id 211 ({$lot211Qty}) + lot_id 217 ({$lot217Qty})\n";
            } else {
                $newItems[] = $item;
            }
        }
        
        $newJson = json_encode($newItems, JSON_UNESCAPED_SLASHES);
        $stmt2 = $conn->prepare("UPDATE deliveries SET lot_items = :json WHERE delivery_id = :id");
        $stmt2->execute(['json' => $newJson, 'id' => $del['delivery_id']]);
        echo "  Updated lot_items JSON.\n\n";
    }

    // 5. Recalculate produced_quantity for PO-180
    $poiId = $lot['poi_id']; // 115
    $poId = $lot['po_id'];   // 46
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity_produced), 0) FROM production_lots WHERE poi_id = :poi_id AND is_removed = 0");
    $stmt->execute(['poi_id' => $poiId]);
    $newProduced = intval($stmt->fetchColumn());
    
    $stmt = $conn->prepare("UPDATE purchase_order_items SET produced_quantity = :qty WHERE poi_id = :poi_id");
    $stmt->execute(['qty' => $newProduced, 'poi_id' => $poiId]);
    echo "Recalculated poi_id {$poiId}: produced_quantity = {$newProduced}\n";

    // Recalculate PO-level
    $stmt = $conn->prepare("UPDATE purchase_orders SET produced_quantity = (SELECT COALESCE(SUM(produced_quantity), 0) FROM purchase_order_items WHERE po_id = :po_id) WHERE po_id = :po_id2");
    $stmt->execute(['po_id' => $poId, 'po_id2' => $poId]);
    
    $stmt = $conn->prepare("SELECT produced_quantity FROM purchase_orders WHERE po_id = :po_id");
    $stmt->execute(['po_id' => $poId]);
    $poTotal = $stmt->fetchColumn();
    echo "Recalculated po_id {$poId}: produced_quantity = {$poTotal}\n\n";

    $conn->commit();
    echo "=== Migration complete! ===\n";

} catch (Exception $e) {
    $conn->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
