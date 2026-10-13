<?php
/**
 * Delete the MRP snapshot records (History) — every remaining run is test
 * data: MRP1 / MRP2 / MRP3 on the dummy FGs FG001 / DFG 1, whose
 * requisitions were purged earlier. The History page reads mrp_runs +
 * mrp_run_items, so removing them clears it out.
 *
 * Mirrors deleteMrpRun() before the hard delete:
 *   1. Release each run item's committed stock (qty_committed) from
 *      inventory_balances.qty_allocated — otherwise the test runs leave
 *      permanently reserved ALLOCATED behind on the test items.
 *   2. Delete mrp_run_items, then mrp_runs (explicit, not relying on the
 *      ON DELETE CASCADE).
 *
 * No dangling references: supplier_orders.mrp_run_id rows were all purged
 * (verified 0) and mrp_allocations is empty.
 *
 * Runs are resolved live (SELECT, not hard-coded IDs). Items NOT committed
 * by these runs — e.g. the small real-item allocations from older cleanups —
 * are never touched: each release is keyed by that run's own item + qty.
 *
 * Idempotent: with no runs left, a re-run matches nothing.
 *
 * Usage:
 *   CLI:    php sql/pending/delete_mrp_snapshots.php
 *   Runner: php sql/run_pending.php
 */
require_once __DIR__ . '/../../core/BaseModel.php';

$pdo = App\Core\BaseModel::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Delete MRP snapshot records ===\n";

$runIds = array_map('intval', $pdo->query(
    "SELECT run_id FROM mrp_runs ORDER BY run_id"
)->fetchAll(PDO::FETCH_COLUMN));
if (!$runIds) {
    echo "  No mrp_runs rows — nothing to do.\n=== Done ===\n";
    return;
}
$inList = implode(',', $runIds);
echo "  Runs found: " . count($runIds) . " ({$inList})\n";

$pdo->beginTransaction();
try {
    // Snapshot sizes before they go (for the report).
    $stats = $pdo->query(
        "SELECT run_id, COUNT(*) c, COALESCE(SUM(qty_committed), 0) committed
           FROM mrp_run_items WHERE run_id IN ({$inList}) GROUP BY run_id"
    )->fetchAll();

    // 1. Release this run's commitments (per run item, exactly like
    //    deleteMrpRun does before removing the snapshot).
    $release = $pdo->prepare(
        "UPDATE inventory_balances
            SET qty_allocated = GREATEST(qty_allocated - :qty, 0)
          WHERE item_id = :item_id AND site_code = '001'"
    );
    $releasedTotal = 0.0;
    $releaseStmt = $pdo->query(
        "SELECT component_item_id, qty_committed FROM mrp_run_items
          WHERE run_id IN ({$inList}) AND qty_committed > 0"
    );
    foreach ($releaseStmt->fetchAll() as $ri) {
        $qty = floatval($ri['qty_committed']);
        $release->execute(['qty' => $qty, 'item_id' => $ri['component_item_id']]);
        $releasedTotal += $qty;
    }

    // 2. Delete the snapshot rows, then the runs themselves.
    $itemsDeleted = (int) $pdo->exec(
        "DELETE FROM mrp_run_items WHERE run_id IN ({$inList})"
    );
    $runsDeleted = (int) $pdo->exec(
        "DELETE FROM mrp_runs WHERE run_id IN ({$inList})"
    );

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

$runsLeft = (int) $pdo->query("SELECT COUNT(*) FROM mrp_runs")->fetchColumn();
$allocLeft = (float) $pdo->query(
    "SELECT COALESCE(SUM(qty_allocated), 0) FROM inventory_balances
      WHERE item_id IN (SELECT item_id FROM items
                         WHERE item_code IN ('item001','item002','item003','item004','item005',
                                             'DIRM1','DIRM2','DIRM3','DIRM4','DIPM1'))"
)->fetchColumn();

foreach ($stats as $s) {
    echo "  Run #{$s['run_id']}: {$s['c']} line(s), {$s['committed']} committed\n";
}
echo "  ALLOCATED released: {$releasedTotal}\n";
echo "  mrp_run_items deleted: {$itemsDeleted}\n";
echo "  mrp_runs deleted:      {$runsDeleted}\n";
echo "  mrp_runs remaining:    {$runsLeft}\n";
echo "  Test-item ALLOCATED left: {$allocLeft}\n";
echo "=== Done ===\n";
