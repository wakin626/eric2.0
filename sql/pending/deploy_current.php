<?php
require_once __DIR__ . '/../../core/BaseModel.php';

use App\Core\BaseModel;

$pdo = BaseModel::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    migration_key VARCHAR(150) PRIMARY KEY,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
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

function addColumn(PDO $pdo, string $table, string $column, string $definition, ?string $after = null): void
{
    if (columnExists($pdo, $table, $column)) {
        echo "Already exists: {$table}.{$column}\n";
        return;
    }

    $position = $after === null ? '' : ' AFTER `' . $after . '`';
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}{$position}");
    echo "Added: {$table}.{$column}\n";
}

function makeNullable(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->prepare(
        'SELECT IS_NULLABLE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    $nullable = $stmt->fetchColumn();

    if ($nullable === false || $nullable === 'YES') {
        echo "Already nullable or missing: {$table}.{$column}\n";
        return;
    }

    $pdo->exec("ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$definition} NULL");
    echo "Made nullable: {$table}.{$column}\n";
}

echo "Updating manufacturing_mgmt schema...\n";

addColumn($pdo, 'production_history', 'poi_id', 'INT NULL', 'po_id');
addColumn($pdo, 'production_history', 'lot_number', 'VARCHAR(100) NULL', 'poi_id');
addColumn($pdo, 'production_history', 'item_description', 'VARCHAR(255) NULL', 'lot_number');
addColumn($pdo, 'production_history', 'sts_ref', 'VARCHAR(255) NULL', 'item_description');
addColumn($pdo, 'production_history', 'shift', 'VARCHAR(50) NULL', 'sts_ref');
addColumn($pdo, 'production_history', 'mo_no', 'VARCHAR(100) NULL', 'shift');
addColumn($pdo, 'production_history', 'material_type', 'VARCHAR(100) NULL', 'mo_no');
addColumn($pdo, 'production_history', 'reject_status', 'VARCHAR(100) NULL', 'material_type');
addColumn($pdo, 'production_history', 'sts_remarks', 'TEXT NULL', 'reject_status');
addColumn($pdo, 'production_history', 'pcs_per_case', 'INT NULL', 'sts_remarks');
addColumn($pdo, 'production_history', 'prepared_by_name', 'VARCHAR(255) NULL', 'pcs_per_case');
addColumn($pdo, 'production_history', 'checked_by_name', 'VARCHAR(255) NULL', 'prepared_by_name');
addColumn($pdo, 'production_history', 'received_by_name', 'VARCHAR(255) NULL', 'checked_by_name');
addColumn($pdo, 'production_history', 'edited_by', 'INT NULL', 'user_id');
addColumn($pdo, 'production_history', 'date_edited', 'DATETIME NULL', 'date_created');
addColumn($pdo, 'production_history', 'old_lot_number', 'VARCHAR(100) NULL', 'date_edited');
addColumn($pdo, 'production_history', 'old_added_quantity', 'INT NULL', 'old_lot_number');
addColumn($pdo, 'production_history', 'item_id', 'INT NULL', 'poi_id');
addColumn($pdo, 'production_history', 'qc_remark', 'TEXT NULL', 'sts_remarks');
addColumn($pdo, 'production_history', 'qc_inspected_by', 'INT NULL', 'qc_remark');
addColumn($pdo, 'production_history', 'qc_inspected_at', 'DATETIME NULL', 'qc_inspected_by');
addColumn($pdo, 'production_history', 'qc_inspector_name', 'VARCHAR(255) NULL', 'qc_inspected_at');
addColumn($pdo, 'production_history', 'qa_remark', 'TEXT NULL', 'qc_inspector_name');
addColumn($pdo, 'production_history', 'qa_inspected_by', 'INT NULL', 'qa_remark');
addColumn($pdo, 'production_history', 'qa_inspected_at', 'DATETIME NULL', 'qa_inspected_by');
addColumn($pdo, 'production_history', 'qa_inspector_name', 'VARCHAR(255) NULL', 'qa_inspected_at');
addColumn($pdo, 'production_history', 'is_removed', 'TINYINT(1) DEFAULT 0', 'qa_inspector_name');

addColumn($pdo, 'production_lots', 'item_id', 'INT NULL', 'poi_id');
addColumn($pdo, 'production_lots', 'pcs_per_case', 'INT NULL', 'quantity_produced');
makeNullable($pdo, 'production_lots', 'po_id', 'INT');
makeNullable($pdo, 'production_lots', 'poi_id', 'INT');
makeNullable($pdo, 'production_history', 'po_id', 'INT');

addColumn($pdo, 'deliveries', 'is_over_shipment', 'TINYINT(1) DEFAULT 0', 'logistic_provider');

if (!tableExists($pdo, 'production_reports')) {
    $pdo->exec("CREATE TABLE `production_reports` (
        `report_id` INT NOT NULL AUTO_INCREMENT,
        `history_id` INT NOT NULL,
        `poi_id` INT NULL,
        `po_id` INT NULL,
        `old_lot_number` VARCHAR(100) NULL,
        `reported_by` INT NOT NULL,
        `reason` TEXT NOT NULL,
        `report_type` ENUM('lot_number','quantity') DEFAULT 'lot_number',
        `status` ENUM('pending','resolved') DEFAULT 'pending',
        `resolved_by` INT NULL,
        `new_lot_number` VARCHAR(100) NULL,
        `date_reported` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `date_resolved` DATETIME NULL,
        PRIMARY KEY (`report_id`),
        KEY `idx_history_id` (`history_id`),
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created: production_reports\n";
} else {
    echo "Already exists: production_reports\n";
}

if (!indexExists($pdo, 'production_lots', 'idx_item_lot_active')) {
    $pdo->exec('ALTER TABLE production_lots ADD INDEX idx_item_lot_active (item_id, lot_number, is_removed)');
    echo "Added: production_lots.idx_item_lot_active\n";
}

$department = $pdo->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'department'")->fetchColumn();
if ($department !== false && (strpos($department, "'rnd'") === false)) {
    $pdo->exec("ALTER TABLE users MODIFY department ENUM('admin', 'warehouse', 'production', 'finance', 'qc', 'qa', 'rnd') NOT NULL");
    echo "Updated: users.department enum\n";
}

// ─── Phase 1: MRP Tables ─────────────────────────────────────────────────────

if (!tableExists($pdo, 'raw_materials')) {
    $pdo->exec("CREATE TABLE `raw_materials` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `item_code` VARCHAR(50) NOT NULL UNIQUE,
        `trade_name` VARCHAR(255) NOT NULL,
        `category` ENUM('raw_material', 'packaging') NOT NULL DEFAULT 'raw_material',
        `uom` VARCHAR(20) NOT NULL DEFAULT 'Kg',
        `stock_on_hand` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        `allocated_stock` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        `reorder_level` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created: raw_materials\n";
} else {
    echo "Already exists: raw_materials\n";
}

if (!tableExists($pdo, 'fg_boms')) {
    $pdo->exec("CREATE TABLE `fg_boms` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `fg_item_id` INT NOT NULL,
        `bom_code` VARCHAR(50) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`fg_item_id`) REFERENCES `items`(`item_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created: fg_boms\n";
} else {
    echo "Already exists: fg_boms\n";
}

if (!tableExists($pdo, 'fg_bom_items')) {
    $pdo->exec("CREATE TABLE `fg_bom_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `bom_id` INT NOT NULL,
        `raw_material_id` INT NOT NULL,
        `dosage_rate` DECIMAL(15,6) DEFAULT 0.000000,
        `wastage_allowance_pct` DECIMAL(5,2) DEFAULT 0.00,
        FOREIGN KEY (`bom_id`) REFERENCES `fg_boms`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials`(`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created: fg_bom_items\n";
} else {
    echo "Already exists: fg_bom_items\n";
}

// ─── End Phase 1 ─────────────────────────────────────────────────────────────

echo "Backfilling item references...\n";
$pdo->exec('UPDATE production_lots pl
    JOIN purchase_order_items poi ON pl.poi_id = poi.poi_id
    SET pl.item_id = poi.item_id
    WHERE pl.item_id IS NULL AND pl.poi_id IS NOT NULL');
$pdo->exec('UPDATE production_history ph
    JOIN purchase_order_items poi ON ph.poi_id = poi.poi_id
    SET ph.item_id = poi.item_id
    WHERE ph.item_id IS NULL AND ph.poi_id IS NOT NULL');

$record = $pdo->prepare('INSERT INTO schema_migrations (migration_key) VALUES (?)
    ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP');
$record->execute(['current-production-schema-v2-mrp-phase1']);

// ─── Phase 2: ERIC Item Master & Inventory Balances ──────────────────────────

echo "Phase 2: ERIC Item Master & Inventory Balances...\n";

// Add columns to items table
if (!columnExists($pdo, 'items', 'item_type')) {
    $pdo->exec("ALTER TABLE items ADD COLUMN item_type ENUM('RM','PM','FG','SFG','SUPPLIES') DEFAULT NULL AFTER item_code");
    echo "Added: items.item_type\n";
}
if (!columnExists($pdo, 'items', 'raw_c_type')) {
    $pdo->exec("ALTER TABLE items ADD COLUMN raw_c_type VARCHAR(50) DEFAULT NULL AFTER item_type");
    echo "Added: items.raw_c_type\n";
}
if (!columnExists($pdo, 'items', 'reorder_level')) {
    $pdo->exec("ALTER TABLE items ADD COLUMN reorder_level DECIMAL(15,4) DEFAULT 0.0000 AFTER item_amount");
    echo "Added: items.reorder_level\n";
}

// Create inventory_balances table
if (!tableExists($pdo, 'inventory_balances')) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS inventory_balances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        site_code VARCHAR(20) DEFAULT 'MAIN',
        qty_on_hand DECIMAL(15,4) DEFAULT 0.0000,
        qty_allocated DECIMAL(15,4) DEFAULT 0.0000,
        qty_for_inspect DECIMAL(15,4) DEFAULT 0.0000,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE,
        UNIQUE KEY uq_item_site (item_id, site_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created: inventory_balances\n";
}

// Create view_inventory_status view
$pdo->exec("CREATE OR REPLACE VIEW view_inventory_status AS
    SELECT
        i.item_id, i.item_code, i.item_description, i.item_type, i.item_uom,
        i.reorder_level,
        COALESCE(SUM(ib.qty_on_hand), 0) AS total_soh,
        COALESCE(SUM(ib.qty_allocated), 0) AS total_allocated,
        (COALESCE(SUM(ib.qty_on_hand), 0) - COALESCE(SUM(ib.qty_allocated), 0)) AS available_stock,
        CASE
            WHEN (COALESCE(SUM(ib.qty_on_hand), 0) - COALESCE(SUM(ib.qty_allocated), 0)) <= 0 THEN 'OUT OF STOCK'
            WHEN (COALESCE(SUM(ib.qty_on_hand), 0) - COALESCE(SUM(ib.qty_allocated), 0)) <= i.reorder_level THEN 'LOW STOCK'
            ELSE 'IN STOCK'
        END AS inventory_status
    FROM items i
    LEFT JOIN inventory_balances ib ON i.item_id = ib.item_id
    GROUP BY i.item_id");
echo "Created/Updated: view_inventory_status\n";

// Seed existing items as FG
$pdo->exec("UPDATE items SET item_type = 'FG' WHERE item_type IS NULL");
echo "Seeded: items.item_type = 'FG' for existing items\n";

// Migrate raw_materials into unified items table
$pdo->exec("INSERT INTO items (item_code, item_description, item_type, raw_c_type, item_uom, reorder_level)
    SELECT rm.item_code, rm.trade_name,
           CASE WHEN rm.category = 'packaging' THEN 'PM' ELSE 'RM' END,
           rm.category, rm.uom, rm.reorder_level
    FROM raw_materials rm
    WHERE rm.is_active = 1
      AND NOT EXISTS (SELECT 1 FROM items i WHERE i.item_code = rm.item_code)");
echo "Migrated: raw_materials → items (master records)\n";

// Migrate raw_materials stock into inventory_balances
$pdo->exec("INSERT INTO inventory_balances (item_id, site_code, qty_on_hand, qty_allocated)
    SELECT i.item_id, 'MAIN', rm.stock_on_hand, rm.allocated_stock
    FROM raw_materials rm
    JOIN items i ON i.item_code = rm.item_code
    WHERE rm.is_active = 1
      AND (rm.stock_on_hand > 0 OR rm.allocated_stock > 0)");
echo "Migrated: raw_materials stock → inventory_balances\n";

$record2 = $pdo->prepare('INSERT INTO schema_migrations (migration_key) VALUES (?)
    ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP');
$record2->execute(['eric-item-master-inventory-v1']);

// ─── End Phase 2 ─────────────────────────────────────────────────────────────

// ─── Phase 3: Migrate BOM items from raw_materials → items ──────────────────

echo "\n--- Phase 3: BOM items FK migration ---\n";

// Step 1: Migrate data — update raw_material_id values to corresponding items.item_id
if (columnExists($pdo, 'fg_bom_items', 'raw_material_id')) {
    $countBefore = $pdo->query("SELECT COUNT(*) FROM fg_bom_items")->fetchColumn();
    echo "fg_bom_items rows before migration: {$countBefore}\n";

    $pdo->exec("UPDATE fg_bom_items bi
        JOIN raw_materials rm ON bi.raw_material_id = rm.id
        JOIN items i ON i.item_code = rm.item_code
        SET bi.raw_material_id = i.item_id");

    $migrated = $pdo->exec("UPDATE fg_bom_items bi
        JOIN items i ON bi.raw_material_id = i.item_id
        SET bi.raw_material_id = bi.raw_material_id");
    echo "Migrated: fg_bom_items.raw_material_id values → items.item_id values\n";

    // Step 2: Drop existing foreign key
    $fks = $pdo->query("SELECT CONSTRAINT_NAME
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'fg_bom_items'
          AND CONSTRAINT_TYPE = 'FOREIGN KEY'")->fetchAll(\PDO::FETCH_COLUMN);

    foreach ($fks as $fk) {
        $pdo->exec("ALTER TABLE fg_bom_items DROP FOREIGN KEY `{$fk}`");
        echo "Dropped FK: {$fk}\n";
    }

    // Step 3: Rename column
    $pdo->exec("ALTER TABLE fg_bom_items CHANGE `raw_material_id` `item_id` INT NOT NULL");
    echo "Renamed: fg_bom_items.raw_material_id → item_id\n";

    // Step 4: Add new foreign key to items table
    $pdo->exec("ALTER TABLE fg_bom_items ADD CONSTRAINT fk_bom_items_item
        FOREIGN KEY (item_id) REFERENCES items(item_id)");
    echo "Added FK: fg_bom_items.item_id → items.item_id\n";

    // Verify
    $countAfter = $pdo->query("SELECT COUNT(*) FROM fg_bom_items")->fetchColumn();
    echo "fg_bom_items rows after migration: {$countAfter}\n";
    if ($countBefore == $countAfter) {
        echo "✓ Row count preserved\n";
    } else {
        echo "⚠ Row count mismatch! Before: {$countBefore}, After: {$countAfter}\n";
    }
} else {
    echo "Column raw_material_id not found — migration may have already run or table doesn't exist\n";
}

$record3 = $pdo->prepare('INSERT INTO schema_migrations (migration_key) VALUES (?)
    ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP');
$record3->execute(['bom-items-fk-to-items-v1']);

// ─── End Phase 3 ─────────────────────────────────────────────────────────────

// ─── Phase 4: Add batch_qty and batch_uom to fg_boms ────────────────────────

echo "\n--- Phase 4: BOM batch fields ---\n";

if (!columnExists($pdo, 'fg_boms', 'batch_qty')) {
    $pdo->exec("ALTER TABLE fg_boms ADD COLUMN `batch_qty` DECIMAL(15,4) NOT NULL DEFAULT 1.0000 AFTER `bom_code`");
    echo "Added: fg_boms.batch_qty\n";
} else {
    echo "Already exists: fg_boms.batch_qty\n";
}

if (!columnExists($pdo, 'fg_boms', 'batch_uom')) {
    $pdo->exec("ALTER TABLE fg_boms ADD COLUMN `batch_uom` VARCHAR(20) NOT NULL DEFAULT 'PCS' AFTER `batch_qty`");
    echo "Added: fg_boms.batch_uom\n";
} else {
    echo "Already exists: fg_boms.batch_uom\n";
}

$record4 = $pdo->prepare('INSERT INTO schema_migrations (migration_key) VALUES (?)
    ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP');
$record4->execute(['bom-batch-qty-uom-v1']);

// ─── End Phase 4 ─────────────────────────────────────────────────────────────

// ─── Phase 5: Procurement PO, MRP Snapshots & Production Orders ───────────

echo "\n--- Phase 5: Procurement PO, MRP Snapshots & Production Orders ---\n";

// 1. supplier_orders — raw material procurement at factory level
if (!tableExists($pdo, 'supplier_orders')) {
    $pdo->exec("CREATE TABLE `supplier_orders` (
        `supplier_order_id` INT AUTO_INCREMENT PRIMARY KEY,
        `supplier_name` VARCHAR(150) NOT NULL,
        `item_id` INT NOT NULL,
        `quantity` DECIMAL(15,4) NOT NULL,
        `unit_cost` DECIMAL(15,2) DEFAULT 0.00,
        `order_date` DATE,
        `expected_date` DATE,
        `status` ENUM('pending','received','cancelled') DEFAULT 'pending',
        `received_qty` DECIMAL(15,4) DEFAULT 0.0000,
        `received_date` DATE NULL,
        `remarks` TEXT NULL,
        `created_by` INT NOT NULL,
        `remove` TINYINT(1) DEFAULT 0,
        `date_created` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `last_update` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`item_id`) REFERENCES `items`(`item_id`),
        FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created: supplier_orders\n";
} else {
    echo "Already exists: supplier_orders\n";
}

// 2. mrp_runs — snapshot header (one row per PO per save)
if (!tableExists($pdo, 'mrp_runs')) {
    $pdo->exec("CREATE TABLE `mrp_runs` (
        `run_id` INT AUTO_INCREMENT PRIMARY KEY,
        `po_id` INT NOT NULL,
        `customer_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `date_created` DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`po_id`) REFERENCES `purchase_orders`(`po_id`),
        FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`),
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created: mrp_runs\n";
} else {
    echo "Already exists: mrp_runs\n";
}

// 3. mrp_run_items — snapshot detail (one row per component per FG)
if (!tableExists($pdo, 'mrp_run_items')) {
    $pdo->exec("CREATE TABLE `mrp_run_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `run_id` INT NOT NULL,
        `fg_item_id` INT NOT NULL,
        `component_item_id` INT NOT NULL,
        `total_reqt` DECIMAL(15,4) DEFAULT 0.0000,
        `soh` DECIMAL(15,4) DEFAULT 0.0000,
        `allocated` DECIMAL(15,4) DEFAULT 0.0000,
        `pending` DECIMAL(15,4) DEFAULT 0.0000,
        `excess` DECIMAL(15,4) DEFAULT 0.0000,
        `remarks` VARCHAR(50) DEFAULT NULL,
        FOREIGN KEY (`run_id`) REFERENCES `mrp_runs`(`run_id`) ON DELETE CASCADE,
        FOREIGN KEY (`fg_item_id`) REFERENCES `items`(`item_id`),
        FOREIGN KEY (`component_item_id`) REFERENCES `items`(`item_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created: mrp_run_items\n";
} else {
    echo "Already exists: mrp_run_items\n";
}

// 4. production_orders — with mrp_saved status for MRP-generated orders
if (!tableExists($pdo, 'production_orders')) {
    $pdo->exec("CREATE TABLE `production_orders` (
        `production_order_id` INT AUTO_INCREMENT PRIMARY KEY,
        `po_id` INT NULL,
        `customer_id` INT NOT NULL,
        `item_id` INT NOT NULL,
        `quantity` INT NOT NULL,
        `status` ENUM('pending','in_production','completed','mrp_saved','cancelled') DEFAULT 'pending',
        `run_id` INT NULL,
        `remarks` TEXT NULL,
        `created_by` INT NOT NULL,
        `remove` TINYINT(1) DEFAULT 0,
        `date_created` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `last_update` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`po_id`) REFERENCES `purchase_orders`(`po_id`),
        FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`),
        FOREIGN KEY (`item_id`) REFERENCES `items`(`item_id`),
        FOREIGN KEY (`run_id`) REFERENCES `mrp_runs`(`run_id`),
        FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created: production_orders\n";
} else {
    echo "Already exists: production_orders\n";
}

// Update supplier_orders status ENUM to include 'requested'
$soStatus = $pdo->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'supplier_orders' AND COLUMN_NAME = 'status'")->fetchColumn();
if ($soStatus !== false && strpos($soStatus, "'requested'") === false) {
    $pdo->exec("ALTER TABLE supplier_orders MODIFY status ENUM('pending','received','cancelled','requested') DEFAULT 'pending'");
    echo "Updated: supplier_orders.status enum added 'requested'\n";
}

$record5 = $pdo->prepare('INSERT INTO schema_migrations (migration_key) VALUES (?)
    ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP');
$record5->execute(['supplier-orders-mrp-snapshots-v1']);

// ─── End Phase 5 ────────────────────────────────────────────────────────────

// ─── Phase 6: Procurement PO po_id link + completed status ───────────────

echo "\n--- Phase 6: Procurement PO po_id link + completed status ---\n";

addColumn($pdo, 'supplier_orders', 'po_id', 'INT NULL', 'created_by');

$soStatus = $pdo->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'supplier_orders' AND COLUMN_NAME = 'status'")->fetchColumn();
if ($soStatus !== false && strpos($soStatus, "'completed'") === false) {
    $pdo->exec("ALTER TABLE supplier_orders MODIFY status ENUM('pending','received','cancelled','requested','completed') DEFAULT 'pending'");
    echo "Updated: supplier_orders.status enum added 'completed'\n";
}

$record6 = $pdo->prepare('INSERT INTO schema_migrations (migration_key) VALUES (?)
    ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP');
$record6->execute(['supplier-orders-po-id-completed-v1']);

// Backfill po_id on existing auto-generated requested/pending procurement POs
$backfill = $pdo->exec("UPDATE supplier_orders so
    JOIN mrp_runs mr ON so.remarks = CONCAT('Auto-generated from MRP Run #', mr.run_id)
    SET so.po_id = mr.po_id
    WHERE so.po_id IS NULL AND so.status IN ('requested','pending')");
echo "Backfilled po_id on {$backfill} existing procurement POs from MRP runs\n";

// ─── End Phase 6 ──────────────────────────────────────────────────────────

// ─── Phase 7: Cleanup duplicate procurement requests ────────────────────

echo "\n--- Phase 7: Cleanup duplicate procurement requests ---\n";

$cleanup = $pdo->exec("DELETE p1 FROM supplier_orders p1
    INNER JOIN supplier_orders p2
    ON p1.po_id = p2.po_id
      AND p1.item_id = p2.item_id
      AND p1.supplier_order_id > p2.supplier_order_id
    WHERE p1.status = 'requested'
      AND p2.status = 'requested'
      AND p1.`remove` = 0 AND p2.`remove` = 0");
echo "Cleaned up {$cleanup} duplicate unprocessed procurement requests\n";

$record7 = $pdo->prepare('INSERT INTO schema_migrations (migration_key) VALUES (?)
    ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP');
$record7->execute(['cleanup-duplicate-procurement-requests-v1']);

// ─── End Phase 7 ──────────────────────────────────────────────────────

echo "\nSchema deployment complete.\n";
