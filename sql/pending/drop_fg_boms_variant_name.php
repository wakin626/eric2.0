<?php
/**
 * Migration: drop fg_boms.variant_name.
 *
 * The VARIANT / FORMULATION NAME input was removed from the Create New BOM
 * modal — formulations of the same finished good are differentiated by their
 * BOM code alone. The column is therefore a database dependency of a feature
 * that no longer exists and is dropped.
 *
 * Kept: fg_boms.bom_type / is_default (they drive the MRP default-BOM
 * resolution and are not part of the removed input).
 *
 * Run: php sql/pending/drop_fg_boms_variant_name.php
 */
require_once __DIR__ . '/../../core/BaseModel.php';

use App\Core\BaseModel;

$pdo = BaseModel::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Drop fg_boms.variant_name Migration ===\n";

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

if (columnExists($pdo, 'fg_boms', 'variant_name')) {
    $nonNull = (int) $pdo->query(
        "SELECT COUNT(*) FROM fg_boms WHERE variant_name IS NOT NULL AND variant_name <> ''"
    )->fetchColumn();
    $pdo->exec("ALTER TABLE `fg_boms` DROP COLUMN `variant_name`");
    echo "   Dropped: fg_boms.variant_name ({$nonNull} row(s) had a value — differentiation now relies on bom_code)\n";
} else {
    echo "   Already absent: fg_boms.variant_name\n";
}

$record = $pdo->prepare('INSERT INTO schema_migrations (migration_key) VALUES (?)
    ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP');
$record->execute(['fg-boms-drop-variant-name-v1']);

echo "=== Migration complete ===\n";
