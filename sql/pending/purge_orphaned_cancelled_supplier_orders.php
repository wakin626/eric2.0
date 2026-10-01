<?php
/**
 * Cleanup: hide Cancelled purchasing PO rows left behind by MRP runs that no
 * longer exist.
 *
 * Deleting an MRP run used to only flip its requisitions to 'cancelled' while
 * the run itself was removed — so the Purchasing PO History tab filled up with
 * dead rows whose mrp_run_id pointed at nothing. deleteMrpRun() now soft
 * deletes (remove = 1) unfulfilled requisitions going forward; this migration
 * clears the backlog once.
 *
 * Scope is deliberately narrow: only Cancelled rows explicitly stamped with an
 * MRP run id that is gone from mrp_runs. Rows without mrp_run_id, non-cancelled
 * rows and rows whose run still exists are never touched.
 *
 * Idempotent (guarded by remove = 0) and safe to re-run.
 *
 * Usage:
 *   CLI:    php sql/pending/purge_orphaned_cancelled_supplier_orders.php
 *   Runner: php sql/run_pending.php
 */
require_once __DIR__ . '/../../core/BaseModel.php';

$pdo = App\Core\BaseModel::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Purge orphaned Cancelled MRP purchase requests ===\n";

$before = (int) $pdo->query(
    "SELECT COUNT(*) FROM supplier_orders
     WHERE status = 'cancelled' AND remove = 0
       AND mrp_run_id IS NOT NULL
       AND mrp_run_id NOT IN (SELECT run_id FROM mrp_runs)"
)->fetchColumn();

$purged = (int) $pdo->exec(
    "UPDATE supplier_orders
     SET remove = 1, last_update = NOW()
     WHERE status = 'cancelled' AND remove = 0
       AND mrp_run_id IS NOT NULL
       AND mrp_run_id NOT IN (SELECT run_id FROM mrp_runs)"
);

$after = (int) $pdo->query(
    "SELECT COUNT(*) FROM supplier_orders
     WHERE status = 'cancelled' AND remove = 0
       AND mrp_run_id IS NOT NULL
       AND mrp_run_id NOT IN (SELECT run_id FROM mrp_runs)"
)->fetchColumn();

echo "  Orphaned Cancelled rows found: {$before}\n";
echo "  Hidden (remove = 1):           {$purged}\n";
echo "  Still visible:                 {$after}\n";
echo "=== Done ===\n";
