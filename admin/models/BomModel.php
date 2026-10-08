<?php
namespace App\Models;

use App\Core\BaseModel;

class BomModel extends BaseModel {
    protected $table = 'fg_boms';

    /**
     * Expose fill_volume / uom aliases alongside legacy batch_qty / batch_uom keys
     * so both old and new call sites work without notices.
     */
    private function normalizeHeader(&$row) {
        if (!is_array($row) || !$row) return;
        $row['fill_volume'] = $row['fill_volume'] ?? $row['batch_qty'] ?? 1.0;
        $row['uom'] = $row['uom'] ?? $row['batch_uom'] ?? 'PCS';
        $row['batch_unit_divisor'] = $row['batch_unit_divisor'] ?? 1000;
        $row['is_legacy_formula'] = $row['is_legacy_formula'] ?? 0;
    }

    private function normalizeAll(array $rows) {
        foreach ($rows as &$row) {
            $this->normalizeHeader($row);
        }
        return $rows;
    }

    public function getAll() {
        $sql = "SELECT b.*, i.item_code AS fg_code, i.item_description AS fg_name
                FROM {$this->table} b
                JOIN items i ON b.fg_item_id = i.item_id AND i.`remove` = 0
                ORDER BY b.id DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $this->normalizeAll($stmt->fetchAll());
    }

    public function getById($id) {
        $sql = "SELECT b.*, i.item_code AS fg_code, i.item_description AS fg_name
                FROM {$this->table} b
                JOIN items i ON b.fg_item_id = i.item_id AND i.`remove` = 0
                WHERE b.id = :id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        $this->normalizeHeader($row);
        return $row;
    }

    public function getItemsByBomId($bomId) {
        $sql = "SELECT bi.*, i.item_code AS rm_code, i.item_description AS rm_name, i.item_uom AS rm_uom, i.item_type
                FROM fg_bom_items bi
                JOIN items i ON bi.item_id = i.item_id AND i.`remove` = 0
                WHERE bi.bom_id = :bom_id
                ORDER BY bi.phase_code ASC, bi.id ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['bom_id' => $bomId]);
        return $stmt->fetchAll();
    }

    /**
     * @param bool $isLegacy Import-created BOMs stay on the legacy lot-based
     *                       formula until an operator re-saves with fill volume.
     *
     * Multi-BOM: an FG/SFG may hold several formulations. The first active BOM
     * created for an item becomes its default (fg_boms.is_default = 1,
     * bom_type = 'Primary'); every later one is an alternative (is_default = 0,
     * bom_type = 'Alternative') so MRP runs always resolve a formulation.
     * Formulations are told apart by their BOM code — there is no separate
     * variant/formulation name.
     */
    public function create($fgItemId, $bomCode, $fillVolume = 1.0, $uom = 'PCS', $batchUnitDivisor = 1000, $isLegacy = false, $customerId = null, $status = 'active') {
        $conn = self::getConnection();

        $activeStatus = $status === 'inactive' ? 'inactive' : 'active';
        $isDefault = 0;
        if ($activeStatus === 'active') {
            $chk = $conn->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE fg_item_id = :fg AND status = 'active'"
            );
            $chk->execute(['fg' => $fgItemId]);
            $isDefault = ((int) $chk->fetchColumn() === 0) ? 1 : 0;
        }
        $bomType = $isDefault ? 'Primary' : 'Alternative';

        $sql = "INSERT INTO {$this->table} (fg_item_id, customer_id, bom_code, batch_qty, batch_uom, batch_unit_divisor, is_legacy_formula, status, is_default, bom_type)
                VALUES (:fg_item_id, :customer_id, :bom_code, :batch_qty, :batch_uom, :batch_unit_divisor, :is_legacy_formula, :status, :is_default, :bom_type)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'fg_item_id' => $fgItemId,
            'customer_id' => $customerId !== null && $customerId !== '' ? $customerId : null,
            'bom_code' => $bomCode,
            'batch_qty' => $fillVolume > 0 ? $fillVolume : 1.0,
            'batch_uom' => $uom ?: 'PCS',
            'batch_unit_divisor' => $batchUnitDivisor > 0 ? $batchUnitDivisor : 1000,
            'is_legacy_formula' => $isLegacy ? 1 : 0,
            'status' => $activeStatus,
            'is_default' => $isDefault,
            'bom_type' => $bomType,
        ]);
        return $conn->lastInsertId();
    }

    /**
     * Active BOM count per finished good — drives the "Existing BOM" badge in
     * the Create New BOM item dropdown (complete across list pages, unlike a
     * slice of the current pagination).
     *
     * @return array<int,int> fg_item_id => active BOM count
     */
    public function getItemBomCounts() {
        $sql = "SELECT fg_item_id, COUNT(*) AS c
                FROM {$this->table}
                WHERE status = 'active'
                GROUP BY fg_item_id";
        $out = [];
        foreach (self::getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $out[(int) $row['fg_item_id']] = (int) $row['c'];
        }
        return $out;
    }

    /**
     * Primary (default) active BOM of an FG/SFG plus its component lines,
     * shaped for pre-filling the Create New BOM modal when the chosen item
     * already has a formulation. Also returns a non-conflicting next BOM code
     * (base_ALT, base_ALT2, …) so the copy can be saved without a clash —
     * formulations of one item are told apart by bom_code alone.
     *
     * @return array{header: array, suggested_bom_code: string, components: array}|false
     */
    public function getPrimaryBomDetails($fgItemId) {
        $header = $this->getBomForItem($fgItemId);
        if (!$header || ($header['status'] ?? '') !== 'active') {
            return false;
        }

        $components = [];
        foreach ($this->getItemsByBomId($header['id']) as $c) {
            $components[] = [
                'item_id' => (int) $c['item_id'],
                'item_code' => (string) ($c['rm_code'] ?? ''),
                'item_name' => (string) ($c['rm_name'] ?? ''),
                'item_type' => (string) ($c['item_type'] ?? ''),
                'phase_code' => (string) ($c['phase_code'] ?? ''),
                'dosage_rate' => floatval($c['dosage_rate'] ?? 0),
                'wastage_pct' => floatval($c['wastage_allowance_pct'] ?? 0),
                'uom' => (string) ($c['uom'] ?? ''),
            ];
        }

        return [
            'header' => [
                'bom_id' => (int) $header['id'],
                'bom_code' => (string) $header['bom_code'],
                'customer_id' => !empty($header['customer_id']) ? (int) $header['customer_id'] : null,
                'fill_volume' => floatval($header['fill_volume'] ?? 0),
                'uom' => (string) ($header['uom'] ?? 'PCS'),
                'batch_unit_divisor' => floatval($header['batch_unit_divisor'] ?? 1000) ?: 1000,
                'is_default' => !empty($header['is_default']),
            ],
            'suggested_bom_code' => $this->suggestBomCodeForItem((int) $fgItemId, (string) $header['bom_code']),
            'components' => $components,
        ];
    }

    /**
     * Next free BOM code for this item: BASE_ALT, BASE_ALT2, … truncated so
     * the result always fits fg_boms.bom_code VARCHAR(50).
     */
    private function suggestBomCodeForItem($fgItemId, $base) {
        $base = trim((string) $base);
        if ($base === '') $base = 'BOM';
        for ($i = 1; $i <= 50; $i++) {
            $suffix = '_ALT' . ($i === 1 ? '' : $i);
            $candidate = $base . $suffix;
            if (mb_strlen($candidate) > 50) {
                $candidate = mb_substr($base, 0, 50 - mb_strlen($suffix)) . $suffix;
            }
            if (!$this->bomCodeExistsForItem($fgItemId, $candidate)) {
                return $candidate;
            }
        }
        // Unreachable in practice; still guarantees a free, ≤50-char code.
        $suffix = '_ALT' . substr((string) (time() % 100000), 0, 5);
        return mb_substr($base, 0, 50 - mb_strlen($suffix)) . $suffix;
    }

    /**
     * Multi-BOM uniqueness: one bom_code per finished good (several BOMs of the
     * same FG are allowed as long as their codes differ).
     */
    public function bomCodeExistsForItem($fgItemId, $bomCode, $excludeBomId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table}
                WHERE fg_item_id = :fg AND bom_code = :code";
        $params = ['fg' => intval($fgItemId), 'code' => trim((string) $bomCode)];
        if (!empty($excludeBomId)) {
            $sql .= " AND id <> :exclude";
            $params['exclude'] = intval($excludeBomId);
        }
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return ((int) $stmt->fetchColumn()) > 0;
    }

    /**
     * @param bool $markNonLegacy When true (normal edit/re-save path) the legacy
     *                            formula flag is cleared. Import passes false so
     *                            the flag and header basis are preserved.
     */
    public function update($id, $fgItemId, $bomCode, $fillVolume = 1.0, $uom = 'PCS', $batchUnitDivisor = 1000, $markNonLegacy = true, $customerId = null, $status = null) {
        $sql = "UPDATE {$this->table}
                SET fg_item_id = :fg_item_id,
                    bom_code = :bom_code,
                    batch_qty = :batch_qty,
                    batch_uom = :batch_uom,
                    batch_unit_divisor = :batch_unit_divisor" .
                ($markNonLegacy ? ", is_legacy_formula = 0" : "") .
                ($customerId !== null ? ", customer_id = :customer_id" : "") .
                ($status !== null ? ", status = :status" : "") .
                " WHERE id = :id";
        $params = [
            'id' => $id,
            'fg_item_id' => $fgItemId,
            'bom_code' => $bomCode,
            'batch_qty' => $fillVolume > 0 ? $fillVolume : 1.0,
            'batch_uom' => $uom ?: 'PCS',
            'batch_unit_divisor' => $batchUnitDivisor > 0 ? $batchUnitDivisor : 1000,
        ];
        if ($customerId !== null) {
            $params['customer_id'] = $customerId !== '' ? $customerId : null;
        }
        if ($status !== null) {
            $params['status'] = $status === 'inactive' ? 'inactive' : 'active';
        }
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
            // Capture the FG before the header goes away: if this was the
            // default formulation the oldest remaining active BOM takes over,
            // so the item never ends up without a default.
            $fgStmt = $conn->prepare("SELECT fg_item_id, is_default FROM {$this->table} WHERE id = :id");
            $fgStmt->execute(['id' => $id]);
            $fgRow = $fgStmt->fetch(\PDO::FETCH_ASSOC);

            $stmt = $conn->prepare("DELETE FROM fg_bom_items WHERE bom_id = :id");
            $stmt->execute(['id' => $id]);
            $stmt = $conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $stmt->execute(['id' => $id]);

            if ($fgRow && !empty($fgRow['is_default'])) {
                // Keep bom_type in step with is_default while promoting.
                $promote = $conn->prepare(
                    "UPDATE {$this->table} SET is_default = 1, bom_type = 'Primary'
                     WHERE fg_item_id = :fg AND status = 'active'
                     ORDER BY id ASC LIMIT 1"
                );
                $promote->execute(['fg' => $fgRow['fg_item_id']]);
                if ($promote->rowCount() > 0) {
                    $clear = $conn->prepare(
                        "UPDATE {$this->table} SET is_default = 0, bom_type = 'Alternative'
                         WHERE fg_item_id = :fg AND status = 'active' AND is_default = 1
                           AND id <> (SELECT id FROM (SELECT id FROM {$this->table}
                                      WHERE fg_item_id = :fg2 AND status = 'active' AND is_default = 1
                                      ORDER BY id ASC LIMIT 1) t)"
                    );
                    $clear->execute(['fg' => $fgRow['fg_item_id'], 'fg2' => $fgRow['fg_item_id']]);
                }
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function addItem($bomId, $itemId, $dosageRate, $wastagePct, $phaseCode = '101', $uom = null) {
        $sql = "INSERT INTO fg_bom_items (bom_id, item_id, dosage_rate, wastage_allowance_pct, phase_code, uom)
                VALUES (:bom_id, :item_id, :dosage_rate, :wastage_allowance_pct, :phase_code, :uom)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'bom_id' => $bomId,
            'item_id' => $itemId,
            'dosage_rate' => $dosageRate,
            'wastage_allowance_pct' => $wastagePct,
            'phase_code' => $phaseCode !== '' ? $phaseCode : '101',
            'uom' => $uom !== null && $uom !== '' ? $uom : null,
        ]);
        return self::getConnection()->lastInsertId();
    }

    public function updateItem($itemId, $newItemId, $dosageRate, $wastagePct, $phaseCode = '101', $uom = null) {
        $sql = "UPDATE fg_bom_items
                SET item_id = :item_id,
                    dosage_rate = :dosage_rate,
                    wastage_allowance_pct = :wastage_allowance_pct,
                    phase_code = :phase_code,
                    uom = :uom
                WHERE id = :id";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute([
            'id' => $itemId,
            'item_id' => $newItemId,
            'dosage_rate' => $dosageRate,
            'wastage_allowance_pct' => $wastagePct,
            'phase_code' => $phaseCode !== '' ? $phaseCode : '101',
            'uom' => $uom !== null && $uom !== '' ? $uom : null,
        ]);
    }

    public function removeItem($itemId) {
        $sql = "DELETE FROM fg_bom_items WHERE id = :id";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute(['id' => $itemId]);
    }

    /**
     * First BOM of a finished good. Multi-BOM: resolves deterministically to
     * the active default formulation (oldest active BOM as fallback) so import
     * and legacy callers never match an arbitrary row.
     */
    public function getBomForItem($fgItemId) {
        $sql = "SELECT * FROM {$this->table}
                WHERE fg_item_id = :fg_item_id
                ORDER BY (status = 'active') DESC, is_default DESC, id ASC
                LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['fg_item_id' => $fgItemId]);
        $row = $stmt->fetch();
        $this->normalizeHeader($row);
        return $row;
    }

    public function replaceBomItems($bomId, $items) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare("DELETE FROM fg_bom_items WHERE bom_id = :bom_id");
            $stmt->execute(['bom_id' => $bomId]);

            if (!empty($items)) {
                $placeholders = [];
                $params = [];
                foreach ($items as $idx => $item) {
                    $offset = $idx * 6;
                    $phaseCode = ($item['phase_code'] ?? '') !== '' ? $item['phase_code'] : '101';
                    $uom = isset($item['uom']) && $item['uom'] !== '' ? mb_substr($item['uom'], 0, 50) : null;
                    $placeholders[] = "(:bom_id_{$offset}, :item_id_{$offset}, :dosage_{$offset}, :wastage_{$offset}, :phase_{$offset}, :uom_{$offset})";
                    $params["bom_id_{$offset}"] = $bomId;
                    $params["item_id_{$offset}"] = $item['item_id'];
                    $params["dosage_{$offset}"] = $item['dosage_rate'];
                    $params["wastage_{$offset}"] = $item['wastage_allowance_pct'];
                    $params["phase_{$offset}"] = $phaseCode;
                    $params["uom_{$offset}"] = $uom;
                }
                $sql = "INSERT INTO fg_bom_items (bom_id, item_id, dosage_rate, wastage_allowance_pct, phase_code, uom) VALUES " . implode(', ', $placeholders);
                $conn->prepare($sql)->execute($params);
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function getBomsByItem($itemId) {
        $sql = "SELECT b.id AS bom_id, b.bom_code, b.is_legacy_formula,
                       i.item_code AS fg_code, i.item_description AS fg_name
                FROM fg_bom_items bi
                JOIN fg_boms b ON bi.bom_id = b.id
                JOIN items i ON b.fg_item_id = i.item_id AND i.`remove` = 0
                WHERE bi.item_id = :item_id
                ORDER BY i.item_code ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['item_id' => $itemId]);
        return $stmt->fetchAll();
    }

    public function getAllFiltered($filters = []) {
        $sql = "SELECT b.*, i.item_code AS fg_code, i.item_description AS fg_name
                FROM {$this->table} b
                JOIN items i ON b.fg_item_id = i.item_id AND i.`remove` = 0
                WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (b.bom_code LIKE :search1 OR i.item_code LIKE :search2 OR i.item_description LIKE :search3)";
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
        }

        $sql .= " ORDER BY b.id DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $this->normalizeAll($stmt->fetchAll());
    }
}
