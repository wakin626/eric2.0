<?php
/**
 * Migration: MRP runs without a purchase order (FG/customer/target calculator).
 *
 * - mrp_runs.po_id / customer_id become nullable (FG-based runs have no PO)
 * - mrp_runs gains fg_item_id / fg_code / target_qty context for history
 * - mrp_run_items gains qty_committed (what the run added to
 *   inventory_balances.qty_allocated) so deleting a run can release it
 *
 * Idempotent: every statement is guarded by an information_schema check.
 */
require_once __DIR__ . '/../../core/BaseModel.php';
require_once __DIR__ . '/../../core/Config.php';
App\Core\Config::init();
$pdo = App\Core\BaseModel::getConnection();

function migHasColumn($pdo, $table, $column) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function migIsNullable($pdo, $table, $column) {
    $stmt = $pdo->prepare(
        "SELECT is_nullable FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
    );
    $stmt->execute([$table, $column]);
    $val = $stmt->fetchColumn();
    return $val !== false && strtoupper((string) $val) === 'YES';
}

$applied = [];

if (!migHasColumn($pdo, 'mrp_runs', 'fg_item_id')) {
    $pdo->exec('ALTER TABLE mrp_runs ADD fg_item_id INT NULL');
    $applied[] = 'mrp_runs.fg_item_id';
}
if (!migHasColumn($pdo, 'mrp_runs', 'fg_code')) {
    $pdo->exec('ALTER TABLE mrp_runs ADD fg_code VARCHAR(50) NULL');
    $applied[] = 'mrp_runs.fg_code';
}
if (!migHasColumn($pdo, 'mrp_runs', 'target_qty')) {
    $pdo->exec('ALTER TABLE mrp_runs ADD target_qty DECIMAL(15,4) NULL');
    $applied[] = 'mrp_runs.target_qty';
}
if (!migIsNullable($pdo, 'mrp_runs', 'po_id')) {
    $pdo->exec('ALTER TABLE mrp_runs MODIFY po_id INT NULL');
    $applied[] = 'mrp_runs.po_id -> NULL';
}
if (!migIsNullable($pdo, 'mrp_runs', 'customer_id')) {
    $pdo->exec('ALTER TABLE mrp_runs MODIFY customer_id INT NULL');
    $applied[] = 'mrp_runs.customer_id -> NULL';
}
if (!migHasColumn($pdo, 'mrp_run_items', 'qty_committed')) {
    $pdo->exec('ALTER TABLE mrp_run_items ADD qty_committed DECIMAL(15,4) NOT NULL DEFAULT 0');
    $applied[] = 'mrp_run_items.qty_committed';
}

echo $applied
    ? 'Applied: ' . implode(', ', $applied) . PHP_EOL
    : 'Nothing to do (already migrated).' . PHP_EOL;
