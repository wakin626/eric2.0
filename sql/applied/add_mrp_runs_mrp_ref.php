<?php
/**
 * Migration: custom MRP reference on MRP runs.
 *
 * mrp_runs.mrp_ref stores the user-entered reference (e.g. "MRP-2026-001")
 * asked for by the Save & Transfer prompt; transferred purchase requests copy
 * it into supplier_orders.mrp_ref so the Purchasing/Receiving tables can badge
 * the origin. Width matches supplier_orders.mrp_ref (varchar(30)).
 *
 * Existing runs fall back to the auto "MRP-#<id>" form.
 *
 * Idempotent: column creation is guarded by information_schema.
 */
require_once __DIR__ . '/../../core/BaseModel.php';
require_once __DIR__ . '/../../core/Config.php';
App\Core\Config::init();
$pdo = App\Core\BaseModel::getConnection();

function runRefHasColumn($pdo, $table, $column) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

$applied = [];

if (!runRefHasColumn($pdo, 'mrp_runs', 'mrp_ref')) {
    $pdo->exec('ALTER TABLE mrp_runs ADD mrp_ref VARCHAR(30) NULL');
    $applied[] = 'mrp_runs.mrp_ref';
}

$backfilled = $pdo->exec(
    "UPDATE mrp_runs SET mrp_ref = CONCAT('MRP-#', run_id) WHERE mrp_ref IS NULL"
);
if ($backfilled > 0) {
    $applied[] = "backfilled {$backfilled} row(s)";
}

echo $applied
    ? 'Applied: ' . implode(', ', $applied) . PHP_EOL
    : 'Nothing to do (already migrated).' . PHP_EOL;
