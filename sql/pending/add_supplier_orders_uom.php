<?php
/**
 * Migration: supplier_orders.uom — per-order unit of measure.
 *
 * - supplier_orders.uom → VARCHAR(50) NULL after quantity
 *
 * Backfill: each order's uom is set from the item's ACTIVE BOM component uom
 * (fg_bom_items.uom) when available (MRP transfer semantics), otherwise from
 * the item master (items.item_uom).
 *
 * Run: php sql/pending/add_supplier_orders_uom.php
 */
require_once __DIR__ . '/../../core/BaseModel.php';

use App\Core\BaseModel;

$pdo = BaseModel::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== supplier_orders.uom Migration ===\n";

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

echo "1. Adding supplier_orders.uom...\n";

if (!columnExists($pdo, 'supplier_orders', 'uom')) {
    $pdo->exec("ALTER TABLE `supplier_orders` ADD COLUMN `uom` VARCHAR(50) NULL AFTER `quantity`");
    echo "   Added: supplier_orders.uom\n";
} else {
    echo "   Already exists: supplier_orders.uom\n";
}

echo "2. Backfilling uom (active BOM component first, then item master)...\n";

$backfilled = $pdo->exec(
    "UPDATE supplier_orders so
     JOIN items i ON i.item_id = so.item_id
     LEFT JOIN (
         SELECT bi.item_id, MAX(bi.uom) AS bom_uom
         FROM fg_bom_items bi
         JOIN fg_boms b ON b.id = bi.bom_id AND b.status = 'active'
         WHERE bi.uom IS NOT NULL AND bi.uom <> ''
         GROUP BY bi.item_id
     ) bu ON bu.item_id = so.item_id
     SET so.uom = COALESCE(bu.bom_uom, i.item_uom)
     WHERE so.uom IS NULL OR so.uom = ''"
);
$nullLeft = (int) $pdo->query(
    "SELECT COUNT(*) FROM supplier_orders WHERE uom IS NULL OR uom = ''"
)->fetchColumn();
echo "   Backfilled {$backfilled} order(s); {$nullLeft} still without a uom\n";

$record = $pdo->prepare('INSERT INTO schema_migrations (migration_key) VALUES (?)
    ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP');
$record->execute(['supplier-orders-uom-v1']);

echo "=== Migration complete ===\n";
