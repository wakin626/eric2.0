<?php
/**
 * Migration: Create customer_finished_goods table and migrate Admin FG data.
 * Separates Admin Commercial FG items from R&D Master Items to prevent data collision.
 *
 * NOTE: For the initial setup, import sql/customer_finished_goods.sql directly.
 * This script handles upgrades and data migration from the items table.
 */
require_once __DIR__ . '/../../core/BaseModel.php';

use App\Core\BaseModel;

$pdo = BaseModel::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Create customer_finished_goods Migration ===\n";

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

// ── Step 1: Create the dedicated customer_finished_goods table ────────────────
echo "1. Creating customer_finished_goods table...\n";

if (tableExists($pdo, 'customer_finished_goods')) {
    echo "   Table already exists. Skipping creation.\n";
} else {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customer_finished_goods (
            item_id INT AUTO_INCREMENT PRIMARY KEY,
            item_code VARCHAR(50) NOT NULL,
            item_description VARCHAR(255) NOT NULL,
            customer_id INT(11) DEFAULT NULL,
            item_uom VARCHAR(50) NOT NULL COMMENT 'Unit of Measurement',
            uom_conversion INT(11) DEFAULT NULL COMMENT 'Units per case',
            item_size VARCHAR(50) DEFAULT NULL,
            item_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
            date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
            status TINYINT(1) DEFAULT 1 COMMENT '0=inactive, 1=active',
            remove TINYINT(1) DEFAULT 0 COMMENT '0=active, 1=soft deleted',
            last_update TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_item_code (item_code),
            KEY idx_status (status),
            KEY idx_remove (remove)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "   Created customer_finished_goods table.\n";
}

// ── Step 2: Migrate existing Admin FG data from items table ──────────────────
echo "2. Migrating existing FG items with customer_id...\n";

$migrated = $pdo->exec("
    INSERT IGNORE INTO customer_finished_goods (item_code, item_description, customer_id, item_uom, uom_conversion, item_amount, status)
    SELECT 
        i.item_code,
        i.item_description,
        i.customer_id,
        i.item_uom,
        i.uom_conversion,
        COALESCE(i.item_amount, 0.00),
        i.status
    FROM items i
    WHERE i.item_type = 'FG'
      AND i.customer_id IS NOT NULL
      AND i.customer_id != 0
      AND i.`remove` = 0
");
echo "   Migrated $migrated new row(s) from items table.\n";

// ── Step 3: Drop FK constraint on purchase_order_items.item_id ────────────────
echo "3. Dropping FK constraint on purchase_order_items.item_id...\n";

$fks = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_order_items' AND CONSTRAINT_TYPE = 'FOREIGN KEY'")->fetchAll();
foreach ($fks as $fk) {
    if (strpos($fk['CONSTRAINT_NAME'], 'item_id') !== false || strpos($fk['CONSTRAINT_NAME'], 'ibfk_2') !== false) {
        $pdo->exec("ALTER TABLE purchase_order_items DROP FOREIGN KEY " . $fk['CONSTRAINT_NAME']);
        echo "   Dropped: " . $fk['CONSTRAINT_NAME'] . "\n";
    }
}

// ── Step 4: Add item_code column to purchase_order_items ─────────────────────
echo "4. Adding item_code column to purchase_order_items...\n";

if (!columnExists($pdo, 'purchase_order_items', 'item_code')) {
    $pdo->exec("ALTER TABLE purchase_order_items ADD COLUMN item_code VARCHAR(100) DEFAULT NULL AFTER item_id");
    echo "   Added item_code column.\n";
} else {
    echo "   Column already exists.\n";
}

// ── Step 5: Backfill item_code in purchase_order_items ────────────────────────
echo "5. Backfilling item_code in purchase_order_items...\n";

$backfilled = $pdo->exec("
    UPDATE purchase_order_items poi
    JOIN items i ON poi.item_id = i.item_id
    SET poi.item_code = i.item_code
    WHERE poi.item_code IS NULL
");
echo "   Backfilled $backfilled row(s).\n";

// ── Done ─────────────────────────────────────────────────────────────────────
echo "\n=== Migration complete ===\n";
echo "Admin FG items now live in customer_finished_goods.\n";
echo "R&D continues using the items table for RM/PM/FG(Technical)/SFG/SUPPLIES.\n";
