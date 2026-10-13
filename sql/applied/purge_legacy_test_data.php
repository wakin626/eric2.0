<?php
/**
 * Purge legacy test data and release stuck inspection holds.
 *
 * Three cleanups in one idempotent pass:
 *
 * 1. STUCK INSPECTION HOLDS — inventory_balances.qty_for_inspect is only
 *    releasable through a receiving_items row with qc_status = 'PENDING_QC'
 *    (QcModel approve/reject, or the purchasing-PO cancel path). When the
 *    queue is empty, any positive qty_for_inspect is unreachable: it can
 *    never be inspected, never be released, and inflates the MRP sheet's
 *    PENDING PO / RR forever. Zero every hold that has no live queue row
 *    (18 items / 439,944.46 units in the current data — includes PM00097's
 *    phantom 2,000). Holds WITH a PENDING_QC row are preserved.
 *
 * 2. TEST RECEIPTS + QC RECORDS — the receiving/QC rows created by test
 *    runs (POTEST1 / potest11 / potest100 on item004 / DIRM1 / DIRM2), their
 *    qc_inspections and the inventory_stock lots they produced. The test
 *    items' qty_on_hand is reduced by the lot quantities those receipts
 *    passed (25 / 30 / 50) so no phantom stock remains.
 *
 * 3. TEST SUPPLIER ORDERS — every requisition from the dummy-FG MRP test
 *    runs (runs on FG001 / DFG 1: MRP1, MRP2, MRP3), any row whose
 *    mrp_run_id is orphaned (run hard-deleted, e.g. run 12's leftovers),
 *    and any row still pointing at a known test item code. Soft-deleted
 *    or not, these are test records — hard deleted.
 *
 * NOT touched: test ITEM masters / dummy FGs / BOMs (still chosen to stay),
 * MRP run snapshots (frozen history) and their inventory commitments.
 *
 * Safe to re-run: after the first pass no test rows and no stuck holds
 * remain, so every statement matches nothing.
 *
 * Usage:
 *   CLI:    php sql/pending/purge_legacy_test_data.php
 *   Runner: php sql/run_pending.php
 */
require_once __DIR__ . '/../../core/BaseModel.php';

$pdo = App\Core\BaseModel::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$testItemCodes = "'item001','item002','item003','item004','item005','DIRM1','DIRM2','DIRM3','DIRM4','DIPM1'";

echo "=== Purge legacy test data + release stuck inspection holds ===\n";

// ── 3a. Resolve the test supplier orders first (children link to them) ──────
$testOrderIds = array_map('intval', $pdo->query(
    "SELECT supplier_order_id FROM supplier_orders
      WHERE mrp_run_id IN (SELECT run_id FROM mrp_runs
                            WHERE fg_item_id IN (SELECT item_id FROM items
                                                  WHERE item_code IN ('FG001', 'DFG 1')))
         OR (mrp_run_id IS NOT NULL
             AND mrp_run_id NOT IN (SELECT run_id FROM mrp_runs))
         OR item_id IN (SELECT item_id FROM items WHERE item_code IN ({$testItemCodes}))"
)->fetchAll(PDO::FETCH_COLUMN));
echo '  Test supplier orders found: ' . count($testOrderIds)
    . ($testOrderIds ? ' (' . implode(', ', $testOrderIds) . ')' : '') . "\n";

// ── 2a. Resolve test receipts (by test item code, test po_ref, or link to ──
//        a test supplier order) so their lots can be reversed first.
$testOrderIn = $testOrderIds ? implode(',', $testOrderIds) : 'NULL';
$testReceipts = $pdo->query(
    "SELECT ri.id, i.item_id, ri.item_code, ri.passed_qty
       FROM receiving_items ri
       LEFT JOIN items i ON i.item_code = ri.item_code
      WHERE ri.item_code IN ({$testItemCodes})
         OR LOWER(COALESCE(ri.po_ref, '')) LIKE '%test%'
         OR ri.supplier_order_id IN ({$testOrderIn})"
)->fetchAll();
$testRiIds = [];
foreach ($testReceipts as $r) {
    $testRiIds[] = (int) $r['id'];
}
echo '  Test receiving rows found: ' . count($testRiIds)
    . ($testRiIds ? ' (' . implode(', ', $testRiIds) . ')' : '') . "\n";

$inspectionsDeleted = $receiptsDeleted = $lotsDeleted = $ordersDeleted = 0;
$stockReversed = 0.0;

$pdo->beginTransaction();
try {
    // ── 2b. QC inspection records for those receipts (explicit, before the ──
    //        receiving rows — the FK would cascade them silently anyway).
    if ($testRiIds) {
        $riIn = implode(',', $testRiIds);
        $inspectionsDeleted = (int) $pdo->exec(
            "DELETE FROM qc_inspections WHERE receiving_item_id IN ({$riIn})"
        );

        // ── 2c. Test lots, then subtract them from the items' balances so ──
        //        the passed test receipts leave no phantom SOH behind.
        $lots = $pdo->query(
            "SELECT stock_id, item_id, qty_on_hand FROM inventory_stock
              WHERE source_receiving_item_id IN ({$riIn})"
        )->fetchAll();
        foreach ($lots as $lot) {
            $pdo->prepare(
                "UPDATE inventory_balances
                    SET qty_on_hand = GREATEST(qty_on_hand - :qty, 0)
                  WHERE item_id = :item_id AND site_code = '001'"
            )->execute(['qty' => $lot['qty_on_hand'], 'item_id' => $lot['item_id']]);
            $stockReversed += floatval($lot['qty_on_hand']);
        }
        $lotsDeleted = (int) $pdo->exec(
            "DELETE FROM inventory_stock WHERE source_receiving_item_id IN ({$riIn})"
        );
        $receiptsDeleted = (int) $pdo->exec(
            "DELETE FROM receiving_items WHERE id IN ({$riIn})"
        );
    }

    // ── 3b. The test supplier orders themselves (hard delete). ─────────────
    if ($testOrderIds) {
        $ordersDeleted = (int) $pdo->exec(
            "DELETE FROM supplier_orders WHERE supplier_order_id IN (" . implode(',', $testOrderIds) . ")"
        );
    }

    // ── 1. Release every inspection hold that has no live QC queue row. ────
    //        (receiving_items.item_code matches items.item_code; a hold is
    //        only preserved while an actual PENDING_QC row can release it.)
    $holdBefore = (float) $pdo->query(
        "SELECT COALESCE(SUM(qty_for_inspect), 0) FROM inventory_balances WHERE qty_for_inspect > 0"
    )->fetchColumn();
    $holdsReleased = (int) $pdo->exec(
        "UPDATE inventory_balances b
           JOIN items i ON i.item_id = b.item_id
            SET b.qty_for_inspect = 0
          WHERE b.qty_for_inspect > 0
            AND NOT EXISTS (SELECT 1 FROM receiving_items ri
                             WHERE ri.item_code = i.item_code
                               AND ri.qc_status = 'PENDING_QC')"
    );
    $holdAfter = (float) $pdo->query(
        "SELECT COALESCE(SUM(qty_for_inspect), 0) FROM inventory_balances WHERE qty_for_inspect > 0"
    )->fetchColumn();

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

$pending = (float) $pdo->query(
    "SELECT COALESCE(SUM(GREATEST(quantity - COALESCE(received_qty, 0), 0)), 0)
       FROM supplier_orders
      WHERE `remove` = 0
        AND status IN ('requested', 'pending', 'processed', 'partially_received', 'For Inspection')
        AND item_id = (SELECT item_id FROM items WHERE item_code = 'PM00097')"
)->fetchColumn();

echo "  QC inspections deleted:          {$inspectionsDeleted}\n";
echo "  Test lots deleted (SOH -{$stockReversed}): {$lotsDeleted}\n";
echo "  Test receiving rows deleted:     {$receiptsDeleted}\n";
echo "  Test supplier orders deleted:    {$ordersDeleted}\n";
echo "  Stuck holds released:            {$holdsReleased} row(s), {$holdBefore} -> {$holdAfter}\n";
echo "  PM00097 total PENDING PO/RR now: {$pending}\n";
echo "=== Done ===\n";
