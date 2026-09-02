<?php
/**
 * MIGRATION: Adapt live database to match local fixes
 * 
 * Changes applied:
 * 1. Add transferred_from_po_id column to production_lots
 * 2. Delete phantom lot_id 208 (32,256) from PO-180
 * 3. Update DR17479 lot_items JSON (lot_id 208 -> 211+217)
 * 4. Soft delete lot_ids 219, 220 (158-250 phantom excess)
 * 5. Reduce lot_ids 238, 239 to match delivered amounts
 * 6. Delete excess_production excess_id 15
 * 7. Set produced_quantity = 288,000 on poi_id 115 (PO-180)
 * 8. Recalculate PO-180 totals
 * 
 * BACK UP YOUR DATABASE BEFORE RUNNING!
 */

$host = 'localhost';
$db   = 'manufacturing_mgmt';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "============================================\n";
    echo "  LIVE MIGRATION: PO-180 Phantom Lot Fix\n";
    echo "============================================\n\n";

    // Check if transferred_from_po_id already exists
    $stmt = $conn->query("SHOW COLUMNS FROM production_lots LIKE 'transferred_from_po_id'");
    if ($stmt->rowCount() === 0) {
        echo "[1/8] Adding transferred_from_po_id column...\n";
        $conn->exec("ALTER TABLE production_lots ADD COLUMN transferred_from_po_id INT(11) NULL DEFAULT NULL AFTER is_removed");
        $conn->exec("ALTER TABLE production_lots ADD INDEX idx_transferred_from (transferred_from_po_id)");
        echo "  Done.\n\n";
    } else {
        echo "[1/8] transferred_from_po_id already exists. Skipping.\n\n";
    }

    $conn->exec("START TRANSACTION");

    // 2. Delete phantom lot_id 208
    echo "[2/8] Deleting phantom lot_id 208...\n";
    $stmt = $conn->prepare("SELECT lot_id, po_id, poi_id, lot_number, quantity_produced FROM production_lots WHERE lot_id = 208");
    $stmt->execute();
    $lot = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($lot) {
        echo "  Found: lot_id={$lot['lot_id']}, qty={$lot['quantity_produced']}, po_id={$lot['po_id']}\n";
        $conn->exec("DELETE FROM production_lots WHERE lot_id = 208");
        echo "  Deleted.\n\n";
    } else {
        echo "  lot_id 208 not found. Skipping.\n\n";
    }

    // 3. Update DR17479 lot_items JSON
    echo "[3/8] Updating DR17479 lot_items JSON...\n";
    $stmt = $conn->prepare("SELECT delivery_id, lot_items FROM deliveries WHERE dr_number = 'DR17479' AND remove = 0");
    $stmt->execute();
    $del = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($del) {
        $items = json_decode($del['lot_items'], true);
        $newItems = [];
        foreach ($items as $item) {
            if (intval($item['lot_id']) === 208) {
                // Split 23904 across lot_ids 211 (12672) + 217 (11232)
                $item211 = $item;
                $item211['lot_id'] = 211;
                $item211['qty'] = 12672;
                $newItems[] = $item211;

                $item217 = $item;
                $item217['lot_id'] = 217;
                $item217['qty'] = 11232;
                $newItems[] = $item217;
                echo "  Replaced lot_id 208 (23904) with lot_id 211 (12672) + lot_id 217 (11232)\n";
            } else {
                $newItems[] = $item;
            }
        }
        $newJson = json_encode($newItems, JSON_UNESCAPED_SLASHES);
        $stmt2 = $conn->prepare("UPDATE deliveries SET lot_items = :json WHERE delivery_id = :id");
        $stmt2->execute(['json' => $newJson, 'id' => $del['delivery_id']]);
        echo "  Updated.\n\n";
    } else {
        echo "  DR17479 not found. Skipping.\n\n";
    }

    // 4. Soft delete lot_ids 219, 220 (158-250 phantom excess)
    echo "[4/8] Soft deleting lot_ids 219, 220 (158-250 phantom excess)...\n";
    $conn->exec("UPDATE production_lots SET is_removed = 1 WHERE lot_id IN (219, 220)");
    echo "  Deleted lot_id 219 (8064 pcs) and lot_id 220 (4608 pcs)\n\n";

    // 5. Reduce lot_ids 238, 239 to match delivered (25344 -> 23904)
    echo "[5/8] Reducing lot_ids 238, 239 to match delivered...\n";
    $conn->exec("UPDATE production_lots SET quantity_produced = 23904 WHERE lot_id = 238");
    $conn->exec("UPDATE production_lots SET quantity_produced = 23904 WHERE lot_id = 239");
    echo "  lot_id 238: 25344 -> 23904 (removed 1440)\n";
    echo "  lot_id 239: 25344 -> 23904 (removed 1440)\n\n";

    // 6. Delete excess_production
    echo "[6/8] Deleting excess_production excess_id 15...\n";
    $conn->exec("DELETE FROM excess_production WHERE excess_id = 15");
    echo "  Deleted.\n\n";

    // 7. Set produced_quantity = 288000 on poi_id 115
    echo "[7/8] Setting produced_quantity = 288000 on poi_id 115...\n";
    $conn->exec("UPDATE purchase_order_items SET produced_quantity = 288000 WHERE poi_id = 115");
    echo "  Done.\n\n";

    // 8. Recalculate PO-180 totals
    echo "[8/8] Recalculating PO-180 totals...\n";
    $conn->exec("UPDATE purchase_orders SET produced_quantity = (SELECT COALESCE(SUM(produced_quantity), 0) FROM purchase_order_items WHERE po_id = 46) WHERE po_id = 46");
    $stmt = $conn->query("SELECT produced_quantity FROM purchase_orders WHERE po_id = 46");
    $poTotal = $stmt->fetchColumn();
    echo "  PO-180 produced_quantity = {$poTotal}\n\n";

    $conn->exec("COMMIT");

    // Final verification
    echo "============================================\n";
    echo "  VERIFICATION\n";
    echo "============================================\n\n";

    $stmt = $conn->query("SELECT poi_id, quantity, produced_quantity, delivered_quantity FROM purchase_order_items WHERE poi_id = 115");
    $poi = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "PO-180 Item (poi_id=115):\n";
    echo "  Ordered:    {$poi['quantity']}\n";
    echo "  Produced:   {$poi['produced_quantity']}\n";
    echo "  Delivered:  {$poi['delivered_quantity']}\n";
    echo "  Progress:   {$poi['produced_quantity']}/{$poi['quantity']}\n\n";

    $stmt = $conn->query("SELECT lot_id, lot_number, quantity_produced, is_removed FROM production_lots WHERE poi_id = 115 AND is_removed = 0 ORDER BY lot_id");
    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Remaining active lots:\n";
    $total = 0;
    foreach ($lots as $l) {
        echo "  lot_id {$l['lot_id']}: {$l['lot_number']} = {$l['quantity_produced']} pcs\n";
        $total += $l['quantity_produced'];
    }
    echo "  TOTAL: {$total} pcs\n\n";

    echo "============================================\n";
    echo "  Migration complete!\n";
    echo "============================================\n";

} catch (Exception $e) {
    $conn->exec("ROLLBACK");
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "Transaction rolled back.\n";
    exit(1);
}
