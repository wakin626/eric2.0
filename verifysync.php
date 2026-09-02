<?php
require 'core/Config.php';
require 'core/BaseModel.php';
require 'warehouse/models/WarehouseModel.php';

$m = new App\Models\WarehouseModel();
$m->syncExcessProduction();
echo "Sync done\n";

$conn = $m->getConnection();
$stmt = $conn->query("SELECT excess_id, source_poi_id, excess_quantity, consumed_quantity, remaining_quantity, status FROM excess_production ORDER BY excess_id");
$rows = $stmt->fetchAll();
foreach ($rows as $r) {
    echo "poi=" . $r['source_poi_id'] . " excess=" . $r['excess_quantity'] . " consumed=" . $r['consumed_quantity'] . " remaining=" . $r['remaining_quantity'] . "\n";
}