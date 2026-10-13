<?php
/**
 * Cleanup: repoint open purchase requisitions created against the dummy
 * label test items (item005 / DIPM1) to the real label item PM00097.
 *
 * MRP runs against the test FG BOMs (FG001, DFG 1) generated supplier_orders
 * rows keyed to the dummy items. The Purchasing PO page and the MRP sheet's
 * PENDING PO / RR column both read supplier_orders by item_id, so those open
 * requisitions never showed up against PM00097 — the label line looked
 * uncovered while the queue held its stock.
 *
 * supplier_orders has no item_code column; item identity is the items FK, so
 * repointing item_id is the code-string equivalent of the intended
 * "UPDATE ... SET item_code = 'PM00097'" cleanup. The denormalized
 * item_code columns on purchase_order_items / receiving_items are normalized
 * defensively too (0 matching rows in the current data).
 *
 * Scope: requisition/PO mappings only. Frozen history (mrp_run_items), the
 * test FG BOMs, the dummy items themselves and their committed
 * inventory_balances.qty_allocated are intentionally untouched — history
 * stays as it was recorded, and deleting an old MRP run still releases its
 * own commitment.
 *
 * Idempotent: after repointing, no supplier_orders rows reference the dummy
 * items, so a re-run matches nothing. Safe to re-run.
 *
 * Usage:
 *   CLI:    php sql/pending/repoint_dummy_label_requisitions.php
 *   Runner: php sql/run_pending.php
 */
require_once __DIR__ . '/../../core/BaseModel.php';

$pdo = App\Core\BaseModel::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Repoint dummy label requisitions to PM00097 ===\n";

// item_code is unique (migrate_item_code_unique), so this resolves to at
// most one row — no ambiguity about which item receives the requisitions.
$realStmt = $pdo->query("SELECT item_id FROM items WHERE item_code = 'PM00097'");
$realId = (int) $realStmt->fetchColumn();
if ($realId <= 0) {
    throw new RuntimeException("Real label item PM00097 not found in items — aborting.");
}

$dummyIds = array_map('intval', $pdo->query(
    "SELECT item_id FROM items WHERE item_code IN ('item005', 'DIPM1')"
)->fetchAll(PDO::FETCH_COLUMN));
if (!$dummyIds) {
    echo "  Dummy items item005 / DIPM1 not found — nothing to do.\n";
    echo "=== Done ===\n";
    return;
}
$inList = implode(',', $dummyIds);

$before = (int) $pdo->query(
    "SELECT COUNT(*) FROM supplier_orders WHERE item_id IN ({$inList})"
)->fetchColumn();
$openBefore = (int) $pdo->query(
    "SELECT COUNT(*) FROM supplier_orders WHERE item_id IN ({$inList}) AND `remove` = 0"
)->fetchColumn();

$moved = (int) $pdo->exec(
    "UPDATE supplier_orders SET item_id = {$realId}, last_update = NOW()
     WHERE item_id IN ({$inList})"
);

// Denormalized code columns on the receiving / customer-PO tables: normalize
// any leftovers so every screen shows the real code for the same material.
$poiItems = (int) $pdo->exec(
    "UPDATE purchase_order_items SET item_id = {$realId}
     WHERE item_id IN ({$inList})"
);
$poiCodes = (int) $pdo->exec(
    "UPDATE purchase_order_items SET item_code = 'PM00097'
     WHERE item_code IN ('item005', 'DIPM1')"
);
$receivingCodes = (int) $pdo->exec(
    "UPDATE receiving_items
     SET item_code = 'PM00097', item_name = 'Label - LB Soothing Cream 300ml (new)'
     WHERE item_code IN ('item005', 'DIPM1')"
);

$after = (int) $pdo->query(
    "SELECT COUNT(*) FROM supplier_orders WHERE item_id IN ({$inList})"
)->fetchColumn();
$openAfter = (int) $pdo->query(
    "SELECT COUNT(*) FROM supplier_orders WHERE item_id = {$realId} AND `remove` = 0 AND status = 'requested'"
)->fetchColumn();

echo "  supplier_orders rows on dummy items before: {$before} ({$openBefore} open)\n";
echo "  Repointed to PM00097 (item_id {$realId}):  {$moved}\n";
echo "  supplier_orders rows left on dummy items:   {$after}\n";
echo "  purchase_order_items item_id fixes:         {$poiItems}\n";
echo "  purchase_order_items item_code fixes:       {$poiCodes}\n";
echo "  receiving_items item_code fixes:            {$receivingCodes}\n";
echo "  Open requested rows now on PM00097:         {$openAfter}\n";
echo "=== Done ===\n";
