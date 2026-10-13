<?php
/**
 * Migration: Unify customer_finished_goods back into the items table.
 *
 * The 2026-10 split (create_customer_finished_goods) copied Admin customer FG
 * rows into a separate table with its own auto-increment item_id. New FGs
 * created after the split received IDs that collide with unrelated rows in
 * `items` (e.g. FGWAK1 got item_id 557, which is raw material A001 in items).
 * purchase_order_items stores those IDs and every display query joins
 * poi.item_id -> items.item_id, so collided POs show the wrong item.
 *
 * Fix: single catalog. customer_finished_goods becomes a VIEW over items
 * filtered to item_type='FG' with a customer, so all existing code keeps
 * working but always uses real items IDs.
 *
 * Steps:
 *   1. Sync curated FG fields (description/UOM/size/price/status) from the
 *      customer_finished_goods table into the ID-aligned items rows.
 *   2. Insert FG rows that only exist in customer_finished_goods into items.
 *   3. Repair purchase_order_items rows whose item_id points at the wrong
 *      items row (matched by item_code + PO customer).
 *   4. Replace the customer_finished_goods table with a view over items.
 */
require_once __DIR__ . '/../../core/BaseModel.php';

use App\Core\BaseModel;

$pdo = BaseModel::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Unify customer_finished_goods into items ===\n";

$tableType = null;
$stmt = $pdo->prepare(
    "SELECT TABLE_TYPE FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer_finished_goods'"
);
$stmt->execute();
$tableType = $stmt->fetchColumn();

// ── Step 1: Sync curated FG fields from cfg into ID-aligned items rows ──────
echo "1. Syncing FG catalog fields into items...\n";

if ($tableType === 'BASE TABLE') {
    $synced = $pdo->exec("
        UPDATE customer_finished_goods cfg
        JOIN items i ON i.item_code = cfg.item_code AND i.customer_id = cfg.customer_id
        SET i.item_description = cfg.item_description,
            i.item_uom = cfg.item_uom,
            i.item_size = cfg.item_size,
            i.item_amount = cfg.item_amount,
            i.status = cfg.status,
            i.last_update = CURRENT_TIMESTAMP
        WHERE cfg.`remove` = 0 AND i.`remove` = 0 AND i.item_type = 'FG'
          AND (i.item_description != cfg.item_description
            OR i.item_uom != cfg.item_uom
            OR COALESCE(i.item_size, '') != COALESCE(cfg.item_size, '')
            OR i.item_amount != cfg.item_amount
            OR i.status != cfg.status)
    ");
    echo "   Synced $synced row(s).\n";
} else {
    echo "   customer_finished_goods is not a base table. Skipping.\n";
}

// ── Step 2: Insert cfg-only FG rows into items ──────────────────────────────
echo "2. Inserting missing FG rows into items...\n";

if ($tableType === 'BASE TABLE') {
    $inserted = $pdo->exec("
        INSERT INTO items (item_code, item_description, item_type, customer_id,
                           item_uom, uom_conversion, item_size, item_amount,
                           date_created, status, `remove`)
        SELECT cfg.item_code, cfg.item_description, 'FG', cfg.customer_id,
               cfg.item_uom, cfg.uom_conversion, cfg.item_size, cfg.item_amount,
               cfg.date_created, cfg.status, cfg.`remove`
        FROM customer_finished_goods cfg
        LEFT JOIN items i
               ON i.item_code = cfg.item_code
              AND ((i.customer_id = cfg.customer_id)
                OR (i.customer_id IS NULL AND cfg.customer_id IS NULL))
        WHERE i.item_id IS NULL
    ");
    echo "   Inserted $inserted row(s).\n";
} else {
    echo "   customer_finished_goods is not a base table. Skipping.\n";
}

// ── Step 3: Repair purchase_order_items pointing at collided items IDs ──────
echo "3. Repairing purchase_order_items item references...\n";

$repaired = $pdo->exec("
    UPDATE purchase_order_items poi
    JOIN purchase_orders po ON po.po_id = poi.po_id
    JOIN items i ON i.item_code = poi.item_code
       AND i.customer_id = po.customer_id
       AND i.item_type = 'FG'
       AND i.`remove` = 0
    SET poi.item_id = i.item_id
    WHERE poi.item_code IS NOT NULL
      AND poi.item_id != i.item_id
");
echo "   Repaired $repaired row(s).\n";

// ── Step 4: Replace customer_finished_goods table with a view ───────────────
echo "4. Replacing customer_finished_goods table with a view over items...\n";

if ($tableType === 'BASE TABLE') {
    $pdo->exec("DROP TABLE customer_finished_goods");
    echo "   Dropped table.\n";
}

$pdo->exec("
    CREATE OR REPLACE VIEW customer_finished_goods AS
    SELECT item_id, item_code, item_description, item_type, customer_id, item_uom,
           uom_conversion, item_size, item_amount, date_created, status,
           `remove`, last_update
    FROM items
    WHERE item_type = 'FG'
      AND customer_id IS NOT NULL
      AND customer_id != 0
");
echo "   View created/updated.\n";

echo "\n=== Migration complete ===\n";
echo "customer_finished_goods is now a view over items (item_type='FG' with customer).\n";
echo "PO forms and Admin FG CRUD use real items IDs; ID collisions cannot recur.\n";
