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
if ($department !== false && (strpos($department, "'qc'") === false || strpos($department, "'qa'") === false)) {
    $pdo->exec("ALTER TABLE users MODIFY department ENUM('admin', 'warehouse', 'production', 'finance', 'qc', 'qa') NOT NULL");
    echo "Updated: users.department enum\n";
}

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
$record->execute(['current-production-schema']);

echo "Schema deployment complete.\n";
