<?php
$pdo = new PDO('mysql:host=localhost;dbname=manufacturing_mgmt','root','');
$items = $pdo->query("SELECT item_id, item_code, item_description, item_uom, uom_conversion FROM items WHERE remove = 0 ORDER BY item_code")->fetchAll();
foreach($items as $i) {
    echo $i['item_code'] . ' - ' . $i['item_uom'] . ' conv=' . ($i['uom_conversion'] ?? 'NULL') . "\n";
}
?>