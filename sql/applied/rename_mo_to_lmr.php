<?php
/**
 * Rename the MO module to LMR (Lot Manufacturing Record).
 *
 *   manufacturing_orders        -> lmr_orders
 *   manufacturing_order_items   -> lmr_order_items
 *   mrp_allocations             (kept; mo_id/moi_id columns renamed)
 *
 * Columns: mo_id -> lmr_id, moi_id -> lri_id, mo_number -> lmr_number,
 * mo_type -> lmr_type, mo_site -> lmr_site, mo_status -> lmr_status.
 * FKs / indexes renamed: fk_mo_items_mo, idx_mo_items_mo_id, uk_mo_item,
 * fk_alloc_mo, fk_alloc_moi, idx_mo_id (alocations), uk_manufacturing_orders_number.
 *
 * Dependent FKs are dropped before the column renames and re-created after.
 *
 * Idempotent: no-ops when lmr_orders already exists.
 *
 * Usage:
 *   CLI:    php sql/pending/rename_mo_to_lmr.php
 *   Runner: php sql/run_pending.php
 */
require_once __DIR__ . '/../../core/BaseModel.php';

$pdo = App\Core\BaseModel::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Rename MO -> LMR ===\n";

$alreadyDone = (bool) $pdo->query(
    "SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lmr_orders'"
)->fetchColumn();
if ($alreadyDone) {
    echo "  lmr_orders already exists — nothing to do.\n=== Done ===\n";
    return;
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
try {
    // 1. Drop dependent FKs / indexes on mrp_allocations + order items
    foreach (['fk_alloc_mo', 'fk_alloc_moi'] as $fk) {
        $pdo->exec("ALTER TABLE mrp_allocations DROP FOREIGN KEY {$fk}");
    }
    foreach (['uk_mo_item', 'idx_mo_id'] as $ix) {
        $pdo->exec("ALTER TABLE mrp_allocations DROP INDEX {$ix}");
    }
    $pdo->exec("ALTER TABLE manufacturing_order_items DROP FOREIGN KEY fk_mo_items_mo");
    $pdo->exec("ALTER TABLE manufacturing_order_items DROP INDEX idx_mo_items_mo_id");

    // 2. Rename tables
    $pdo->exec("RENAME TABLE manufacturing_orders TO lmr_orders");
    $pdo->exec("RENAME TABLE manufacturing_order_items TO lmr_order_items");

    // 3. Rename columns
    $pdo->exec("ALTER TABLE lmr_orders
        CHANGE COLUMN mo_id lmr_id INT(11) NOT NULL AUTO_INCREMENT,
        CHANGE COLUMN mo_number lmr_number VARCHAR(100) NOT NULL,
        CHANGE COLUMN mo_type lmr_type VARCHAR(50) NOT NULL DEFAULT 'Standard',
        CHANGE COLUMN mo_site lmr_site VARCHAR(100) NOT NULL DEFAULT '001 - Sterling Technopark',
        CHANGE COLUMN mo_status lmr_status VARCHAR(50) NOT NULL DEFAULT 'Planned'");
    $pdo->exec("ALTER TABLE lmr_orders RENAME INDEX uk_manufacturing_orders_number TO uk_lmr_orders_number");

    $pdo->exec("ALTER TABLE lmr_order_items
        CHANGE COLUMN mo_id lmr_id INT(11) NOT NULL,
        CHANGE COLUMN moi_id lri_id INT(11) NOT NULL AUTO_INCREMENT");

    $pdo->exec("ALTER TABLE mrp_allocations
        CHANGE COLUMN mo_id lmr_id INT(11) NOT NULL,
        CHANGE COLUMN moi_id lri_id INT(11) NOT NULL");

    // 4. Re-create FKs / indexes with new names
    $pdo->exec("ALTER TABLE lmr_order_items
        ADD CONSTRAINT fk_lmr_items_lmr FOREIGN KEY (lmr_id) REFERENCES lmr_orders (lmr_id) ON DELETE CASCADE,
        ADD INDEX idx_lmr_items_lmr_id (lmr_id)");

    $pdo->exec("ALTER TABLE mrp_allocations
        ADD CONSTRAINT fk_alloc_lmr FOREIGN KEY (lmr_id) REFERENCES lmr_orders (lmr_id) ON DELETE CASCADE,
        ADD CONSTRAINT fk_alloc_lri FOREIGN KEY (lri_id) REFERENCES lmr_order_items (lri_id) ON DELETE CASCADE");
    $pdo->exec("ALTER TABLE mrp_allocations
        ADD UNIQUE KEY uk_lmr_item (lmr_id, item_id),
        ADD INDEX idx_lmr_id (lmr_id)");
} catch (\Throwable $e) {
    throw $e;
} finally {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
}

$counts = $pdo->query(
    "SELECT (SELECT COUNT(*) FROM lmr_orders) AS orders,
            (SELECT COUNT(*) FROM lmr_order_items) AS items,
            (SELECT COUNT(*) FROM mrp_allocations) AS allocations"
)->fetch();
echo "  lmr_orders={$counts['orders']} lmr_order_items={$counts['items']} mrp_allocations={$counts['allocations']}\n";
echo "=== Done ===\n";
