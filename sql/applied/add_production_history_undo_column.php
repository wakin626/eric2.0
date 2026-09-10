<?php
$columnCheck = $pdo->prepare(
    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'production_history'
       AND COLUMN_NAME = 'is_removed'"
);
$columnCheck->execute();

if ((int)$columnCheck->fetchColumn() === 0) {
    $pdo->exec("ALTER TABLE production_history ADD COLUMN is_removed TINYINT(1) DEFAULT 0 COMMENT '0=active, 1=undone'");
}
