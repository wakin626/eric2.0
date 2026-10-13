<?php
/**
 * Flush ghost inventory allocations.
 *
 * inventory_balances.qty_allocated is written by two sources:
 *   1. MO allocation      — mrp_allocations rows (status='active') on
 *                           non-terminal MOs (allocateMaterials / MoModel).
 *   2. MRP run commitment — mrp_run_items.qty_committed on live mrp_runs
 *                           (released only via deleteMrpRun).
 * Any allocated qty whose item has neither backing entry is a ghost left by
 * old test runs / cascade-deleted allocation ledgers and is reset here.
 *
 * Idempotent: re-run matches only rows with qty_allocated <> 0 that are
 * still unbacked.
 *
 * Usage:
 *   CLI:    php sql/pending/flush_ghost_allocations.php
 *   Runner: php sql/run_pending.php
 */
require_once __DIR__ . '/../../core/BaseModel.php';

$pdo = App\Core\BaseModel::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Flush ghost inventory allocations ===\n";

$before = $pdo->query(
    "SELECT COUNT(*) AS rows_affected, COALESCE(SUM(qty_allocated), 0) AS total
       FROM inventory_balances WHERE qty_allocated <> 0"
)->fetch();
echo "  Before: {$before['rows_affected']} row(s), total allocated {$before['total']}\n";

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        "UPDATE inventory_balances ib
            SET ib.qty_allocated = 0
          WHERE ib.qty_allocated <> 0
            AND NOT EXISTS (
                SELECT 1
                  FROM mrp_allocations ma
                  JOIN manufacturing_orders mo ON mo.mo_id = ma.mo_id
                 WHERE ma.item_id = ib.item_id
                   AND ma.status = 'active'
                   AND mo.mo_status NOT IN ('Completed', 'Cancelled')
            )
            AND NOT EXISTS (
                SELECT 1
                  FROM mrp_run_items ri
                  JOIN mrp_runs mr ON mr.run_id = ri.run_id
                 WHERE ri.component_item_id = ib.item_id
                   AND ri.qty_committed > 0
            )"
    );
    $stmt->execute();
    $flushed = $stmt->rowCount();
    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

$after = $pdo->query(
    "SELECT COUNT(*) AS rows_affected, COALESCE(SUM(qty_allocated), 0) AS total
       FROM inventory_balances WHERE qty_allocated <> 0"
)->fetch();

echo "  Ghost rows flushed: {$flushed}\n";
echo "  After: {$after['rows_affected']} row(s) still allocated (backed), total {$after['total']}\n";
echo "=== Done ===\n";
