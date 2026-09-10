<?php
require_once __DIR__ . '/../core/Config.php';
\App\Core\Config::init();
require_once __DIR__ . '/../core/BaseModel.php';

$conn = \App\Core\BaseModel::getConnection();
$conn->beginTransaction();

try {
    $duplicates = $conn->query("SELECT item_code, MAX(item_id) AS canonical_id
        FROM items
        WHERE `remove` = 0
        GROUP BY item_code
        HAVING COUNT(*) > 1")->fetchAll(PDO::FETCH_ASSOC);

    $lotUpdates = 0;
    $historyUpdates = 0;
    $poiUpdates = 0;
    $itemsMerged = 0;

    foreach ($duplicates as $duplicate) {
        $canonicalId = (int)$duplicate['canonical_id'];
        $oldStmt = $conn->prepare("SELECT item_id FROM items
            WHERE item_code = :item_code AND `remove` = 0 AND item_id <> :canonical_id");
        $oldStmt->execute([
            'item_code' => $duplicate['item_code'],
            'canonical_id' => $canonicalId
        ]);

        foreach ($oldStmt->fetchAll(PDO::FETCH_COLUMN) as $oldId) {
            $updateLots = $conn->prepare("UPDATE production_lots SET item_id = :canonical_id WHERE item_id = :old_id");
            $updateLots->execute(['canonical_id' => $canonicalId, 'old_id' => $oldId]);
            $lotUpdates += $updateLots->rowCount();

            $updateHistory = $conn->prepare("UPDATE production_history SET item_id = :canonical_id WHERE item_id = :old_id");
            $updateHistory->execute(['canonical_id' => $canonicalId, 'old_id' => $oldId]);
            $historyUpdates += $updateHistory->rowCount();

            $updatePoi = $conn->prepare("UPDATE purchase_order_items SET item_id = :canonical_id WHERE item_id = :old_id");
            $updatePoi->execute(['canonical_id' => $canonicalId, 'old_id' => $oldId]);
            $poiUpdates += $updatePoi->rowCount();

            $softDelete = $conn->prepare("UPDATE items SET `remove` = 1 WHERE item_id = :old_id");
            $softDelete->execute(['old_id' => $oldId]);
            $itemsMerged += $softDelete->rowCount();
        }
    }

    $conn->commit();
    echo "DUPLICATE_CODES=" . count($duplicates) . PHP_EOL;
    echo "ITEMS_MERGED={$itemsMerged}" . PHP_EOL;
    echo "LOTS_RELINKED={$lotUpdates}" . PHP_EOL;
    echo "HISTORY_RELINKED={$historyUpdates}" . PHP_EOL;
    echo "PO_ITEMS_RELINKED={$poiUpdates}" . PHP_EOL;
} catch (Throwable $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    fwrite(STDERR, 'ERROR=' . $e->getMessage() . PHP_EOL);
    exit(1);
}