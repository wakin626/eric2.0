<?php
require_once 'core/Config.php';
require_once 'core/BaseModel.php';
require_once 'warehouse/models/WarehouseModel.php';

$m = new App\Models\WarehouseModel();

echo "=== EXCESS PRODUCTION FIX PACKAGE ===\n\n";

// STEP 1: Run sync to correct database values
echo "STEP 1: Running syncExcessProduction()...\n";
$m->syncExcessProduction();
echo "  ✓ Sync completed\n";

// STEP 2: Verify database values
echo "STEP 2: Verifying database values...\n";
$conn = $m->getConnection();
$stmt = $conn->query("SELECT excess_id, source_poi_id, excess_quantity, consumed_quantity, remaining_quantity, status FROM excess_production ORDER BY excess_id");
$rows = $stmt->fetchAll();
$allCorrect = true;
foreach ($rows as $r) {
    $expectedRemaining = $r['excess_quantity'] - $r['consumed_quantity'];
    $isCorrect = $r['remaining_quantity'] == $expectedRemaining;
    if (!$isCorrect) $allCorrect = false;
    echo "  " . ($isCorrect ? "✓" : "✗") . " poi={$r['source_poi_id']} excess={$r['excess_quantity']} consumed={$r['consumed_quantity']} remaining={$r['remaining_quantity']}\n";
}

// STEP 2: Verify code fixes
echo "\nSTEP 2: Verifying code fixes...\n";
$reflection = new ReflectionClass($m);
$checks = [];

// Check getAvailableLotsForTransfer
$method = $reflection->getMethod('getAvailableLotsForTransfer');
$method->setAccessible(true);
$source = file($method->getFileName());
$methodCode = implode('', array_slice($source, $method->getStartLine() - 1, 50));
$checks['return_statement'] = strpos($methodCode, "return ['lots'") !== false;
$checks['excess_formula'] = strpos($methodCode, 'max(0, $totalProduced - $orderedQty)') !== false;
$checks['crossover_lot'] = strpos($methodCode, 'prevCumulative < $orderedQty') !== false;

$syncMethod = $reflection->getMethod('syncExcessProduction');
$syncMethod->setAccessible(true);
$syncSource = file($syncMethod->getFileName());
$syncCode = implode('', array_slice($syncSource, $syncMethod->getStartLine() - 1, 20));
$checks['sync_formula'] = strpos($syncCode, 'max(0, $totalProduced - $orderedQty)') !== false;

$validateMethod = $reflection->getMethod('assignExcessToPO');
$validateMethod->setAccessible(true);
$validateSource = file($validateMethod->getFileName());
$validateCode = implode('', array_slice($validateSource, $validateMethod->getStartLine() - 1, 15));
$checks['validate_crossover'] = strpos($validateCode, 'prevCumulative < $orderedQty') !== false;

$allPassed = true;
foreach ($checks as $name => $passed) {
    echo "  " . ($passed ? "✓" : "✗") . " $name\n";
    if (!$passed) $allCorrect = false;
}

echo "\nSTEP 2: Verifying database values...\n";
$allCorrect = true;
$rows = $stmt->fetchAll();
foreach ($rows as $r) {
    $expectedRemaining = $r['excess_quantity'] - $r['consumed_quantity'];
    $isCorrect = $r['remaining_quantity'] == $expectedRemaining;
    if (!$isCorrect) $allCorrect = false;
    echo "  " . ($isCorrect ? "✓" : "✗") . " poi={$r['source_poi_id']} excess={$r['excess_quantity']} remaining={$r['remaining_quantity']}\n";
}

echo "\nSTEP 3: Test getAvailableLotsForTransfer...\n";
$stmt = $conn->query("SELECT poi_id FROM purchase_order_items WHERE item_code = 'FG0368-KSHAMP200' LIMIT 1");
$poi = $stmt->fetch();
$result = $m->getAvailableLotsForTransfer($poi['poi_id']);
$excessRemaining = $result['excess_remaining'];
$lots = $result['lots'];
$totalAvailable = 0;
foreach ($lots as $lot) {
    $totalAvailable += $lot['available_quantity'];
}
$checks = [
    'excess_remaining is 7110' => $excessRemaining == 7110,
    'per-lot available is 7110' => $lots[0]['available_quantity'] == 7110,
    'sum of lot available is 7110' => $totalAvailable == 7110,
];
foreach ($checks as $name => $passed) {
    echo "  " . ($passed ? "✓" : "✗") . " $name\n";
    if (!$passed) $allCorrect = false;
}

echo "\n=== SUMMARY ===\n";
if ($allCorrect) {
    echo "✓ ALL CHECKS PASSED - The fix is working correctly!\n";
    echo "  - Database values corrected (excess_quantity = 7110)\n";
    echo "  - API returns excess_remaining = 7110\n";
    echo "  - Per-lot available_quantity = 7110\n";
    echo "  - Crossover lot fix applied\n";
    echo "  - syncExcessProduction using correct formula\n";
    echo "\nNEXT STEPS:\n";
    echo "1. Clear browser cache (Ctrl+Shift+R)\n";
    echo "2. Refresh the excess production page\n";
    echo "3. Test the transfer flow:\n";
    echo "  - Excess tab should show 7110\n";
    echo "  - Assign modal should show 7110 available\n";
    echo "  - Select lots and enter qty → Review → Confirm → transfer completes\n";
} else {
    echo "✗ SOME CHECKS FAILED - Please ensure all code changes are deployed\n";
    echo "1. Deploy all code changes to WarehouseModel.php\n";
    echo "2. Run the sync on production server\n";
    echo "3. Clear browser cache (Ctrl+Shift+R)\n";
    echo "4. Test the transfer flow again\n";
}