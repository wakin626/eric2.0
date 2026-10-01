<?php
/**
 * Migration: merge legacy site_code 'MAIN' inventory rows into canonical '001'.
 *
 * QC/receiving/MRP/MO allocation used to write stock to site_code 'MAIN'
 * while the rest of the system (CSV imports, MO site) uses '001'. This
 * merges each item's MAIN row quantities into its '001' row (respecting the
 * unique item_id+site_code key), renames any remaining MAIN rows to '001',
 * and aligns the column defaults. Per-item total SOH is unchanged.
 */
require_once __DIR__ . '/../../core/BaseModel.php';

use App\Core\BaseModel;

$pdo = BaseModel::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Merge site_code MAIN into 001 ===\n";

$pdo->beginTransaction();

try {
    // Snapshot totals for verification.
    $before = $pdo->query(
        "SELECT COUNT(*) rows_cnt, COALESCE(SUM(qty_on_hand),0) soh,
                COALESCE(SUM(qty_allocated),0) alloc, COALESCE(SUM(qty_for_inspect),0) inspect
         FROM inventory_balances"
    )->fetch();
    echo "Before: {$before['rows_cnt']} rows, SOH={$before['soh']}, allocated={$before['alloc']}, inspect={$before['inspect']}\n";

    // 1) Fold each MAIN row into the item's existing '001' row.
    $merged = $pdo->exec("
        UPDATE inventory_balances t
        JOIN inventory_balances m ON m.item_id = t.item_id AND m.site_code = 'MAIN'
        SET t.qty_on_hand = t.qty_on_hand + m.qty_on_hand,
            t.qty_allocated = t.qty_allocated + m.qty_allocated,
            t.qty_for_inspect = t.qty_for_inspect + m.qty_for_inspect
        WHERE t.site_code = '001'
    ");
    echo "Merged quantities into existing 001 rows for {$merged} matching pairs.\n";

    // 2) Delete MAIN rows that now have an 001 counterpart (qty already folded).
    $deleted = $pdo->exec("
        DELETE m FROM inventory_balances m
        JOIN inventory_balances t ON t.item_id = m.item_id AND t.site_code = '001'
        WHERE m.site_code = 'MAIN'
    ");
    echo "Deleted {$deleted} merged MAIN row(s).\n";

    // 3) Remaining MAIN rows (item had no 001 row) are renamed.
    $renamed = $pdo->exec("UPDATE inventory_balances SET site_code = '001' WHERE site_code = 'MAIN'");
    echo "Renamed {$renamed} MAIN row(s) to 001.\n";

    // 4) Same treatment for the lot-level inventory_stock table.
    $stockMerged = $pdo->exec("
        UPDATE inventory_stock t
        JOIN inventory_stock m ON m.item_id = t.item_id
             AND m.site_code = 'MAIN' AND m.lot_number <=> t.lot_number AND m.status = t.status
        SET t.qty_on_hand = t.qty_on_hand + m.qty_on_hand,
            t.qty_blocked = t.qty_blocked + m.qty_blocked,
            t.qty_rejected = t.qty_rejected + m.qty_rejected
        WHERE t.site_code = '001'
    ");
    echo "inventory_stock: merged quantities for {$stockMerged} pair(s).\n";

    $stockDeleted = $pdo->exec("
        DELETE m FROM inventory_stock m
        JOIN inventory_stock t ON t.item_id = m.item_id
             AND t.site_code = '001' AND t.lot_number <=> m.lot_number AND t.status = m.status
        WHERE m.site_code = 'MAIN'
    ");
    echo "inventory_stock: deleted {$stockDeleted} merged MAIN row(s).\n";

    $stockRenamed = $pdo->exec("UPDATE inventory_stock SET site_code = '001' WHERE site_code = 'MAIN'");
    echo "inventory_stock: renamed {$stockRenamed} MAIN row(s) to 001.\n";

    // 5) Verify: no MAIN left, per-item totals unchanged. (Column defaults are
    //    aligned after COMMIT — ALTER causes an implicit commit in MySQL.)
    $balLeft = (int) $pdo->query("SELECT COUNT(*) FROM inventory_balances WHERE site_code = 'MAIN'")->fetchColumn();
    $stockLeft = (int) $pdo->query("SELECT COUNT(*) FROM inventory_stock WHERE site_code = 'MAIN'")->fetchColumn();
    $left = $balLeft + $stockLeft;
    if ($left !== 0) {
        throw new RuntimeException("{$left} MAIN row(s) remain after merge.");
    }

    $after = $pdo->query(
        "SELECT COUNT(*) rows_cnt, COALESCE(SUM(qty_on_hand),0) soh,
                COALESCE(SUM(qty_allocated),0) alloc, COALESCE(SUM(qty_for_inspect),0) inspect
         FROM inventory_balances"
    )->fetch();
    echo "After: {$after['rows_cnt']} rows, SOH={$after['soh']}, allocated={$after['alloc']}, inspect={$after['inspect']}\n";

    foreach (['soh', 'alloc', 'inspect'] as $col) {
        if (bccomp((string) $before[$col], (string) $after[$col], 4) !== 0) {
            throw new RuntimeException("Total {$col} changed: {$before[$col]} -> {$after[$col]}");
        }
    }

    $pdo->commit();
    echo "Done. Totals preserved, no MAIN rows remain.\n";

    // Align column defaults so future inserts without an explicit site land
    // on '001' instead of reintroducing 'MAIN'. Runs after COMMIT because
    // ALTER causes an implicit commit in MySQL.
    $pdo->exec("ALTER TABLE inventory_balances ALTER site_code SET DEFAULT '001'");
    echo "Default: inventory_balances.site_code -> '001'\n";
    $pdo->exec("ALTER TABLE inventory_stock ALTER site_code SET DEFAULT '001'");
    echo "Default: inventory_stock.site_code -> '001'\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    echo "FAILED: " . $e->getMessage() . "\n";
    throw $e;
}
