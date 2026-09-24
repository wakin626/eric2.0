<?php
/**
 * Migration: BOM fill volume / batch divisor / phase code / legacy formula flag.
 *
 * - fg_boms.bom_code          → NOT NULL (backfilled as BOM-{id})
 * - fg_boms.batch_unit_divisor → DECIMAL(15,4) NOT NULL DEFAULT 1000.00 (new)
 * - fg_boms.is_legacy_formula  → TINYINT(1) NOT NULL DEFAULT 0 (new; existing rows = 1)
 * - fg_bom_items.phase_code    → VARCHAR(20) NOT NULL DEFAULT '101' (new)
 *
 * NOTE: batch_qty / batch_uom columns are intentionally NOT renamed.
 *       batch_qty now stores fill volume and batch_uom stores the fill-volume UOM
 *       for non-legacy BOMs; PHP aliases expose them as fill_volume / uom.
 *
 * Run: php sql/pending/bom_fill_volume_phase_code.php
 */
require_once __DIR__ . '/../../core/BaseModel.php';

use App\Core\BaseModel;

$pdo = BaseModel::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== BOM Fill Volume / Phase Code Migration ===\n";

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

// ── Step 1: Require bom_code ────────────────────────────────────────────────
echo "1. Making fg_boms.bom_code NOT NULL...\n";

if (columnExists($pdo, 'fg_boms', 'bom_code')) {
    $pdo->exec("UPDATE fg_boms SET bom_code = CONCAT('BOM-', id)
                WHERE bom_code IS NULL OR bom_code = ''");
    echo "   Backfilled empty bom_code values as BOM-{id}\n";

    $col = $pdo->query(
        "SELECT IS_NULLABLE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fg_boms' AND COLUMN_NAME = 'bom_code'"
    )->fetchColumn();
    if ($col !== 'NO') {
        $pdo->exec("ALTER TABLE fg_boms MODIFY bom_code VARCHAR(50) NOT NULL");
        echo "   Altered: fg_boms.bom_code → NOT NULL\n";
    } else {
        echo "   Already NOT NULL\n";
    }
}

// ── Step 2: batch_unit_divisor ──────────────────────────────────────────────
echo "2. Adding fg_boms.batch_unit_divisor...\n";

if (!columnExists($pdo, 'fg_boms', 'batch_unit_divisor')) {
    $pdo->exec("ALTER TABLE fg_boms ADD COLUMN `batch_unit_divisor` DECIMAL(15,4) NOT NULL DEFAULT 1000.00 AFTER `batch_uom`");
    echo "   Added: fg_boms.batch_unit_divisor (default 1000.00)\n";
} else {
    echo "   Already exists: fg_boms.batch_unit_divisor\n";
}

// ── Step 3: is_legacy_formula flag ─────────────────────────────────────────
echo "3. Adding fg_boms.is_legacy_formula...\n";

if (!columnExists($pdo, 'fg_boms', 'is_legacy_formula')) {
    $pdo->exec("ALTER TABLE fg_boms ADD COLUMN `is_legacy_formula` TINYINT(1) NOT NULL DEFAULT 0 AFTER `batch_unit_divisor`");
    // All pre-existing BOMs use legacy lot-based dosages — flag them so the
    // MRP/LMR engine keeps the old formula until an operator re-saves them.
    $pdo->exec("UPDATE fg_boms SET is_legacy_formula = 1");
    $flagged = $pdo->query("SELECT COUNT(*) FROM fg_boms WHERE is_legacy_formula = 1")->fetchColumn();
    echo "   Added: fg_boms.is_legacy_formula — flagged {$flagged} existing BOM(s) as legacy\n";
} else {
    echo "   Already exists: fg_boms.is_legacy_formula\n";
}

// ── Step 4: phase_code on components ───────────────────────────────────────
echo "4. Adding fg_bom_items.phase_code...\n";

if (!columnExists($pdo, 'fg_bom_items', 'phase_code')) {
    $pdo->exec("ALTER TABLE fg_bom_items ADD COLUMN `phase_code` VARCHAR(20) NOT NULL DEFAULT '101' AFTER `wastage_allowance_pct`");
    echo "   Added: fg_bom_items.phase_code (default '101' for legacy rows)\n";
} else {
    echo "   Already exists: fg_bom_items.phase_code\n";
}

// ── Record migration ───────────────────────────────────────────────────────
$record = $pdo->prepare('INSERT INTO schema_migrations (migration_key) VALUES (?)
    ON DUPLICATE KEY UPDATE applied_at = CURRENT_TIMESTAMP');
$record->execute(['bom-fill-volume-phase-code-v1']);

echo "=== Migration complete ===\n";
