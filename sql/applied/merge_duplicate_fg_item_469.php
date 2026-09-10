<?php
$pdo->beginTransaction();
try {
    // Item 108 is the established FG item; item 469 is its duplicate created later.
    $pdo->prepare("UPDATE production_lots SET item_id = 108 WHERE item_id = 469 AND is_removed = 0")
        ->execute();
    $pdo->prepare("UPDATE production_history SET item_id = 108 WHERE item_id = 469 AND is_removed = 0")
        ->execute();
    $pdo->prepare("UPDATE items SET `remove` = 1 WHERE item_id = 469")
        ->execute();
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}