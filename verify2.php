<?php
require 'core/Config.php';
require 'core/BaseModel.php';
require 'warehouse/models/WarehouseModel.php';

$m = new App\Models\WarehouseModel();
$m->syncExcessProduction();
echo "Sync done\n";

$conn = $m->getConnection();
$stmt = $conn->query("SELECT excess_id, source_poi_id, excess_quantity, consumed_quantity, remaining_quantity, status FROM excess_production WHERE source_poi_id = 112");
$row = $stmt->fetch();
echo "poi=112 excess=" . $row['excess_quantity'] . " remaining=" . $row['remaining_quantity'] . "\n";