<?php
/**
 * Migration: received_by — account that transacted a Receiving PO receipt.
 *
 * - supplier_orders.received_by → INT NULL FK users(user_id) after received_date
 *   (last account that posted a receipt against the order)
 * - receiving_items.received_by → INT NULL after received_date
 *   (per-batch receiver; partial receiving posts several batches per order)
 *
 * No backfill: the receiving account was never recorded before this migration,
 * so historical receipts keep NULL and display as "-".
 *
 * Run: php sql/pending/add_supplier_orders_received_by.php
 */
require_once __DIR__ . '/../../core/BaseModel.php';

use App\Core\BaseModel;

$pdo = BaseModel::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== supplier_orders.received_by Migration ===\n";

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

function indexExists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $stmt->execute([$table, $index]);
    return (bool) $stmt->fetchColumn();
}

function fkExists(PDO $pdo, string $table, string $constraint): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?'
    );
    $stmt->execute([$table, $constraint]);
    return (bool) $stmt->fetchColumn();
}

echo "1. Adding supplier_orders.received_by...\n";

if (!columnExists($pdo, 'supplier_orders', 'received_by')) {
    $pdo->exec("ALTER TABLE `supplier_orders` ADD COLUMN `received_by` INT NULL AFTER `received_date`");
    echo "   Added: supplier_orders.received_by\n";
} else {
    echo "   Already exists: supplier_orders.received_by\n";
}

if (!indexExists($pdo, 'supplier_orders', 'received_by')) {
    $pdo->exec("ALTER TABLE `supplier_orders` ADD KEY `received_by` (`received_by`)");
    echo "   Added index: supplier_orders.received_by\n";
} else {
    echo "   Already indexed: supplier_orders.received_by\n";
}

if (!fkExists($pdo, 'supplier_orders', 'supplier_orders_ibfk_3')) {
    $pdo->exec("ALTER TABLE `supplier_orders` ADD CONSTRAINT `supplier_orders_ibfk_3`
                FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`)");
    echo "   Added FK: supplier_orders.received_by → users.user_id\n";
} else {
    echo "   Already exists: supplier_orders_ibfk_3\n";
}

echo "2. Adding receiving_items.received_by...\n";

if (!columnExists($pdo, 'receiving_items', 'received_by')) {
    $pdo->exec("ALTER TABLE `receiving_items` ADD COLUMN `received_by` INT NULL AFTER `received_date`");
    echo "   Added: receiving_items.received_by\n";
} else {
    echo "   Already exists: receiving_items.received_by\n";
}

if (!indexExists($pdo, 'receiving_items', 'idx_receiving_items_received_by')) {
    $pdo->exec("ALTER TABLE `receiving_items` ADD KEY `idx_receiving_items_received_by` (`received_by`)");
    echo "   Added index: receiving_items.received_by\n";
} else {
    echo "   Already indexed: receiving_items.received_by\n";
}

$record = $pdo->prepare('INSERT INTO schema_migrations (migration_key) VALUES (?)
    ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP');
$record->execute(['supplier-orders-received-by-v1']);

echo "=== Migration complete ===\n";
