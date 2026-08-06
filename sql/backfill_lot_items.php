<?php
/**
 * One-time backfill: group lot_items by lot_number for all existing deliveries.
 * Run on server: php backfill_lot_items.php
 * Safe to run multiple times - only updates records that have duplicates.
 */

$host = 'localhost';
$db   = 'manufacturing_mgmt';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db}", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT delivery_id, lot_items FROM deliveries WHERE lot_items IS NOT NULL AND lot_items != '' AND `remove` = 0");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updated = 0;
    foreach ($rows as $row) {
        $items = json_decode($row['lot_items'], true);
        if (!is_array($items) || empty($items)) continue;

        // Check if grouping is needed
        $lotNumbers = array_column($items, 'lot_number');
        if (count($lotNumbers) === count(array_unique($lotNumbers))) continue;

        $grouped = [];
        foreach ($items as $li) {
            $key = $li['lot_number'] ?? uniqid();
            if (!isset($grouped[$key])) {
                $grouped[$key] = $li;
                $grouped[$key]['qty'] = 0;
            }
            $grouped[$key]['qty'] += intval($li['qty'] ?? 0);
            if (isset($li['returned_qty'])) {
                $grouped[$key]['returned_qty'] = ($grouped[$key]['returned_qty'] ?? 0) + intval($li['returned_qty']);
            }
        }
        $groupedArr = array_values($grouped);
        $newJson = json_encode($groupedArr);

        $upd = $pdo->prepare("UPDATE deliveries SET lot_items = :lot_items WHERE delivery_id = :id");
        $upd->execute(['lot_items' => $newJson, 'id' => $row['delivery_id']]);
        echo "Updated delivery_id={$row['delivery_id']} (" . count($lotNumbers) . " entries -> " . count($groupedArr) . ")\n";
        $updated++;
    }

    echo "\nDone. {$updated} deliveries updated out of " . count($rows) . " total.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
