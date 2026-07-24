<?php
/**
 * Repair Script: Fix inflated excess_production records
 * 
 * Recalculates excess_quantity for each record based on the actual
 * produced_quantity vs ordered quantity on the source PO item.
 * 
 * Usage: php sql/repair-excess-production.php [--dry-run]
 */

$host = 'localhost';
$dbname = 'manufacturing_mgmt';
$user = 'root';
$pass = '';

$isDryRun = in_array('--dry-run', $argv);

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== Excess Production Repair Script ===\n";
    echo "Mode: " . ($isDryRun ? "DRY RUN (no changes)" : "LIVE (changes will be applied)") . "\n\n";

    $stmt = $conn->prepare("
        SELECT ep.excess_id, ep.customer_id, ep.item_id, ep.source_poi_id,
               ep.excess_quantity, ep.consumed_quantity, ep.remaining_quantity, ep.status,
               poi.quantity as ordered_quantity, poi.produced_quantity as poi_produced,
               po.customer_po_number
        FROM excess_production ep
        LEFT JOIN purchase_order_items poi ON ep.source_poi_id = poi.poi_id
        LEFT JOIN purchase_orders po ON ep.source_po_id = po.po_id
        ORDER BY ep.excess_id ASC
    ");
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($records) . " excess_production records.\n\n";

    $fixed = 0;
    $deleted = 0;
    $unchanged = 0;

    foreach ($records as $record) {
        $excessId = $record['excess_id'];
        $currentExcess = (int)$record['excess_quantity'];
        $consumed = (int)$record['consumed_quantity'];
        $ordered = (int)($record['ordered_quantity'] ?? 0);
        $produced = (int)($record['poi_produced'] ?? 0);
        $poNumber = $record['customer_po_number'] ?? 'N/A';

        $actualExcess = max(0, $produced - $ordered);
        $actualRemaining = max(0, $actualExcess - $consumed);

        if ($actualExcess === $currentExcess) {
            $unchanged++;
            continue;
        }

        echo "[FIX] Excess #{$excessId} (PO: {$poNumber}): ";
        echo "excess: {$currentExcess} -> {$actualExcess}, ";

        if (!$isDryRun) {
            if ($actualRemaining <= 0 && $consumed <= 0) {
                $conn->prepare("DELETE FROM excess_production WHERE excess_id = :id")->execute(['id' => $excessId]);
                echo "DELETED (no actual excess)";
            } elseif ($actualRemaining <= 0) {
                $conn->prepare("DELETE FROM excess_production WHERE excess_id = :id")->execute(['id' => $excessId]);
                echo "DELETED (fully consumed)";
            } else {
                $newStatus = $consumed > 0 ? 'partial' : 'pending';
                $conn->prepare("UPDATE excess_production SET excess_quantity = :qty, status = :status WHERE excess_id = :id")
                    ->execute(['qty' => $actualExcess, 'status' => $newStatus, 'id' => $excessId]);
                echo "UPDATED";
            }
        } else {
            echo ($actualRemaining <= 0 ? "WOULD DELETE" : "WOULD UPDATE");
        }

        echo "\n";
        $fixed++;
    }

    echo "\n=== Summary ===\n";
    echo "Unchanged: {$unchanged}\n";
    echo "Fixed: {$fixed}\n";
    echo "  - Updated excess_quantity\n";
    echo "  - Deleted records with no actual excess\n";

    if ($isDryRun) {
        echo "\n[DRY RUN] No changes made. Run without --dry-run to apply.\n";
    } else {
        echo "\nDone.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
