<?php
/**
 * Comprehensive Fix & Verification Script for Excess Production
 * Run this on your production server: php fix_excess_production.php
 */

require_once 'core/Config.php';
require_once 'core/BaseModel.php';
require_once 'warehouse/models/WarehouseModel.php';

echo "=== EXCESS PRODUCTION FIX & VERIFICATION ===\n\n";

$m = new App\Models\WarehouseModel();

// ============================================
// STEP 1: Verify code fixes are in place
// ============================================
echo "STEP 1: Verifying code fixes...\n";

$reflection = new ReflectionClass($m);

// Check getAvailableLotsForTransfer method
$method = $reflection->getMethod('getAvailableLotsForTransfer');
$method->setAccessible(true);
$source = file($method->getFileName());
$methodCode = implode('', array_slice($source, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));

$checks = [
    'return statement' => strpos($methodCode, "return ['lots'") !== false,
    'excess_remaining = max(0, totalProduced - orderedQty)' => strpos($methodCode, 'max(0, \$totalProduced - \$orderedQty)') !== false,
    'crossover lot fix' => strpos($methodCode, 'prevCumulative < \$orderedQty') !== false,
    'cumulativeProduced logic' => strpos($methodCode, '\$cumulativeProduced += intval(\$lot') !== false,
];

foreach ($checks as $name => $passed) {
    echo "  " . ($passed ? "✓" : "✗") . " $name\n";
}

// Check syncExcessProduction
$syncMethod = $reflection->getMethod('syncExcessProduction');
$syncMethod->setAccessible(true);
$syncSource = file($syncMethod->getFileName());
$syncCode = implode('', array_slice($syncSource, $syncMethod->getStartLine() - 1, $syncMethod->getEndLine() - $syncMethod->getStartLine() + 1));

$syncChecks = [
    'totalProduced - orderedQty' => strpos($syncCode, 'max(0, \$totalProduced - \$orderedQty)') !== false,
    'no delivery deduction in total' => strpos($syncCode, '\$lotExcess = max(0, \$totalProduced') !== false,
];

foreach ($syncChecks as $name => $passed) {
    echo "  " . ($passed ? "✓" : "✗") . " sync: $name\n";
}

echo "\n";

// ============================================
// STEP 2: Run the sync to fix database values
// ============================================
echo "STEP 2: Running syncExcessProduction()...\n";
$m->syncExcessProduction();
echo "  Sync completed.\n\n";

// ============================================
// STEP 3: Verify database values
// ============================================
echo "STEP 3: Verifying database values...\n";

$conn = $m->getConnection();
$stmt = $conn->query("
    SELECT ep.excess_id, ep.source_poi_id, ep.excess_quantity, ep.consumed_quantity, 
           ep.remaining_quantity, ep.status,
           i.item_code, i.item_description,
           po.customer_po_number, poi.quantity as ordered_qty
    FROM excess_production ep
    LEFT JOIN items i ON ep.item_id = i.item_id
    LEFT JOIN purchase_orders po ON ep.source_po_id = po.po_id
    LEFT JOIN purchase_order_items poi ON ep.source_poi_id = poi.poi_id
    ORDER BY ep.excess_id
");

$rows = $stmt->fetchAll();

$allCorrect = true;
foreach ($rows as $r) {
    $expectedExcess = max(0, $r['excess_quantity'] - $r['consumed_quantity']);
    $isCorrect = ($r['remaining_quantity'] == $expectedExcess);
    
    if (!$isCorrect) $allCorrect = false;
    
    $status = $isCorrect ? "✓" : "✗";
    echo "  $status poi={$r['source_poi_id']} ({$r['item_code']}) PO:{$r['customer_po_number']} excess={$r['excess_quantity']} consumed={$r['consumed_quantity']} remaining={$r['remaining_quantity']} status={$r['status']}\n";
}

if (!$allCorrect) {
    echo "  ⚠ Some values don't match - sync may need to be run again after code deployment\n";
}
echo "\n";

// ============================================
// STEP 4: Test getAvailableLotsForTransfer for specific PO
// ============================================
echo "STEP 4: Testing getAvailableLotsForTransfer for PO-00000180 (FG0368-KSHAMP200)...\n";

// Find the poi_id for PO-00000180 / FG0368-KSHAMP200
$stmt = $conn->query("
    SELECT poi.poi_id, poi.quantity as ordered_qty, i.item_code, i.item_description, po.customer_po_number
    FROM purchase_order_items poi
    JOIN purchase_orders po ON poi.po_id = po.po_id
    JOIN items i ON poi.item_id = i.item_id
    WHERE po.customer_po_number = 'PO-00000180' AND i.item_code = 'FG0368-KSHAMP200'
");
$poi = $stmt->fetch();

if ($poi) {
    echo "  Testing poi_id={$poi['poi_id']} ({$poi['item_code']} - {$poi['item_description']})\n";
    echo "  Ordered: {$poi['ordered_qty']}\n";
    
    $result = $m->getAvailableLotsForTransfer($poi['poi_id']);
    
    echo "  excess_remaining from API: " . ($result['excess_remaining'] ?? 'NULL') . "\n";
    echo "  Lots returned: " . count($result['lots']) . "\n";
    
    $totalAvailable = 0;
    foreach ($result['lots'] as $lot) {
        $totalAvailable += $lot['available_quantity'];
        echo "    Lot {$lot['lot_id']}: produced={$lot['quantity_produced']}, delivered={$lot['delivered_qty']}, available={$lot['available_quantity']}\n";
    }
    echo "  Sum of lot available_quantity: $totalAvailable\n";
    
    // Check consistency
    $excessRemaining = $result['excess_remaining'] ?? 0;
    if ($excessRemaining == 7110) {
        echo "  ✓ excess_remaining is CORRECT (7110)\n";
    } else {
        echo "  ✗ excess_remaining is WRONG: expected 7110, got $excessRemaining\n";
    }
    
    if ($totalAvailable == 7110) {
        echo "  ✓ Sum of lot available_quantity is CORRECT (7110)\n";
    } else {
        echo "  ✗ Sum of lot available_quantity is WRONG: expected 7110, got $totalAvailable\n";
    }
} else {
    echo "  ✗ Could not find PO-00000180 / FG0368-KSHAMP200\n";
}
echo "\n";

// ============================================
// STEP 5: Test assignExcessToPO flow (dry run)
// ============================================
echo "STEP 5: Testing assignExcessToPO validation...\n";

// Get an excess record with remaining > 0
$stmt = $conn->query("
    SELECT ep.excess_id, ep.source_poi_id, ep.excess_quantity, ep.consumed_quantity, ep.remaining_quantity
    FROM excess_production ep
    WHERE ep.remaining_quantity > 0
    LIMIT 1
");
$excess = $stmt->fetch();

if ($excess) {
    echo "  Testing with excess_id={$excess['excess_id']} (remaining={$excess['remaining_quantity']})\n";
    
    // Get available lots for this PO item
    $lotsResult = $m->getAvailableLotsForTransfer($excess['source_poi_id']);
    $availableQty = $lotsResult['excess_remaining'] ?? 0;
    
    echo "  Available for transfer (from API): $availableQty\n";
    echo "  Database remaining_quantity: {$excess['remaining_quantity']}\n";
    
    if ($availableQty > 0) {
        echo "  ✓ Transfer should be possible (qty > 0)\n";
    } else {
        echo "  ✗ Transfer blocked - available qty is 0\n";
    }
} else {
    echo "  No excess records with remaining > 0 found\n";
}
echo "\n";

// ============================================
// STEP 6: Summary
// ============================================
echo "=== SUMMARY ===\n";
echo "If all checks show ✓, the fix is working correctly.\n";
echo "If any checks show ✗, you need to:\n";
echo "  1. Ensure all code changes are deployed to production\n";
echo "  2. Run this script on production server\n";
echo "  3. Clear browser cache (Ctrl+Shift+R)\n";
echo "  4. Test the assign modal again\n\n";
echo "The correct excess for PO-00000180 (FG0368-KSHAMP200) is:\n";
echo "  14,310 produced - 7,200 ordered = 7,110 excess (not 7,080)\n";
echo "The 7,080 value was from the buggy algorithm that subtracted deliveries.\n";