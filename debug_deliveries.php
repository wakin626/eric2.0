<?php
require_once __DIR__ . '/core/Config.php';
\App\Core\Config::init();
require_once __DIR__ . '/core/BaseModel.php';
$pdo = \App\Core\BaseModel::getConnection();

// Check deliveries for PO 52 (PO-00000309)
echo "=== Deliveries for PO 52 ===\n";
$stmt = $pdo->prepare("SELECT delivery_id, dr_number, poi_id, delivery_quantity, lot_items, delivery_date FROM deliveries WHERE po_id = 52 AND remove = 0 ORDER BY delivery_date");
$stmt->execute();
$allLotItems = [];
$poi138Lots = [];
while ($r = $stmt->fetch()) {
    echo "  Delivery " . $r['delivery_id'] . " | DR:" . $r['dr_number'] . " | POI:" . $r['poi_id'] . " | Lot:" . $r['lot_number'] . " | Qty:" . $r['delivery_quantity'] . " | Date:" . $r['delivery_date'] . "\n";
    
    $lotItems = json_decode($r['lot_items'], true);
    if (is_array($lotItems)) {
        foreach ($lotItems as $li) {
            $lid = intval($li['lot_id'] ?? 0);
            $lPoi = $li['poi_id'] ?? null;
            $lQty = $li['qty'] ?? 0;
            $lLotNum = $li['lot_number'] ?? '';
            
            if (!isset($allLotItems[$lid])) {
                $allLotItems[$lid] = ['lot_number' => $lLotNum, 'poi_id' => $lPoi, 'total_delivered' => 0, 'deliveries' => []];
            }
            $allLotItems[$lid]['total_delivered'] += $lQty;
            $allLotItems[$lid]['deliveries'][] = ['delivery_id' => $r['delivery_id'], 'dr' => $r['dr_number'], 'qty' => $lQty];
            
            if ($lPoi == 138) {
                $poi138Lots[$lid] = ['lot_number' => $lLotNum, 'delivered' => $lQty];
            }
        }
    }
}

echo "\n=== Delivered lots for POI 138 (item 108) ===\n";
$poi138Total = 0;
foreach ($poi138Lots as $lid => $info) {
    echo "  Lot ID:" . $lid . " | Lot:" . $info['lot_number'] . " | Delivered:" . number_format($info['delivered']) . "\n";
    $poi138Total += $info['delivered'];
}
echo "  TOTAL DELIVERED: " . number_format($poi138Total) . "\n";

echo "\n=== All delivered lot IDs for PO 52 ===\n";
$allDeliveredTotal = 0;
foreach ($allLotItems as $lid => $info) {
    echo "  Lot ID:" . $lid . " | Lot:" . $info['lot_number'] . " | POI:" . $info['poi_id'] . " | Delivered:" . number_format($info['total_delivered']) . "\n";
    $allDeliveredTotal += $info['total_delivered'];
}
echo "  ALL DELIVERED: " . number_format($allDeliveredTotal) . "\n";

echo "\n=== Raw lot_items JSON for first delivery ===\n";
$stmt2 = $pdo->prepare("SELECT lot_items FROM deliveries WHERE po_id = 52 AND remove = 0 AND lot_items IS NOT NULL LIMIT 1");
$stmt2->execute();
$r2 = $stmt2->fetch();
if ($r2) echo $r2['lot_items'] . "\n";
