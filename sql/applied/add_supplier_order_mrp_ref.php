<?php
/**
 * Migration: traceability of MRP-generated Purchasing PO lines.
 *
 * - supplier_orders.mrp_run_id / mrp_ref record which MRP run produced a line
 *   (readable badge "MRP-#5" on the Purchasing PO table)
 * - backfills existing rows from the legacy remarks convention
 *   "Auto-generated from MRP Run #<id>"
 *
 * No FK to mrp_runs on purpose: an MRP run may be deleted from History while
 * its converted (pending/received) PO lines must survive and keep their origin.
 *
 * Idempotent: column and index creation are guarded by information_schema.
 */
require_once __DIR__ . '/../../core/BaseModel.php';
require_once __DIR__ . '/../../core/Config.php';
App\Core\Config::init();
$pdo = App\Core\BaseModel::getConnection();

function refHasColumn($pdo, $table, $column) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function refHasIndex($pdo, $table, $index) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.statistics
         WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?"
    );
    $stmt->execute([$table, $index]);
    return (int) $stmt->fetchColumn() > 0;
}

$applied = [];

if (!refHasColumn($pdo, 'supplier_orders', 'mrp_run_id')) {
    $pdo->exec('ALTER TABLE supplier_orders ADD mrp_run_id INT NULL');
    $applied[] = 'supplier_orders.mrp_run_id';
}
if (!refHasColumn($pdo, 'supplier_orders', 'mrp_ref')) {
    $pdo->exec('ALTER TABLE supplier_orders ADD mrp_ref VARCHAR(30) NULL');
    $applied[] = 'supplier_orders.mrp_ref';
}
if (!refHasIndex($pdo, 'supplier_orders', 'idx_supplier_orders_mrp_run')) {
    $pdo->exec('CREATE INDEX idx_supplier_orders_mrp_run ON supplier_orders (mrp_run_id)');
    $applied[] = 'index idx_supplier_orders_mrp_run';
}

// Backfill rows written before this column existed (legacy remarks convention).
$backfilled = $pdo->exec(
    "UPDATE supplier_orders
     SET mrp_run_id = CAST(SUBSTRING_INDEX(remarks, '#', -1) AS UNSIGNED),
         mrp_ref    = CONCAT('MRP-#', SUBSTRING_INDEX(remarks, '#', -1))
     WHERE remarks REGEXP '^Auto-generated from MRP Run #[0-9]+$'
       AND mrp_run_id IS NULL"
);
if ($backfilled > 0) {
    $applied[] = "backfilled {$backfilled} row(s)";
}

echo $applied
    ? 'Applied: ' . implode(', ', $applied) . PHP_EOL
    : 'Nothing to do (already migrated).' . PHP_EOL;
