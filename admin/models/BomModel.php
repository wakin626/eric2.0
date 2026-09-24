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
     */
    public function create($fgItemId, $bomCode, $fillVolume = 1.0, $uom = 'PCS', $batchUnitDivisor = 1000, $isLegacy = false) {
        $conn = self::getConnection();
        $sql = "INSERT INTO {$this->table} (fg_item_id, bom_code, batch_qty, batch_uom, batch_unit_divisor, is_legacy_formula)
                VALUES (:fg_item_id, :bom_code, :batch_qty, :batch_uom, :batch_unit_divisor, :is_legacy_formula)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'fg_item_id' => $fgItemId,
            'bom_code' => $bomCode,
            'batch_qty' => $fillVolume > 0 ? $fillVolume : 1.0,
            'batch_uom' => $uom ?: 'PCS',
            'batch_unit_divisor' => $batchUnitDivisor > 0 ? $batchUnitDivisor : 1000,
            'is_legacy_formula' => $isLegacy ? 1 : 0,
        ]);
        return $conn->lastInsertId();
    }

    /**
     * @param bool $markNonLegacy When true (normal edit/re-save path) the legacy
     *                            formula flag is cleared. Import passes false so
     *                            the flag and header basis are preserved.
     */
    public function update($id, $fgItemId, $bomCode, $fillVolume = 1.0, $uom = 'PCS', $batchUnitDivisor = 1000, $markNonLegacy = true) {
        $sql = "UPDATE {$this->table}
                SET fg_item_id = :fg_item_id,
                    bom_code = :bom_code,
                    batch_qty = :batch_qty,
                    batch_uom = :batch_uom,
                    batch_unit_divisor = :batch_unit_divisor" .
                ($markNonLegacy ? ", is_legacy_formula = 0" : "") .
                " WHERE id = :id";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'fg_item_id' => $fgItemId,
            'bom_code' => $bomCode,
            'batch_qty' => $fillVolume > 0 ? $fillVolume : 1.0,
            'batch_uom' => $uom ?: 'PCS',
            'batch_unit_divisor' => $batchUnitDivisor > 0 ? $batchUnitDivisor : 1000,
        ]);
    }

    public function delete($id) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare("DELETE FROM fg_bom_items WHERE bom_id = :id");
            $stmt->execute(['id' => $id]);
            $stmt = $conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function addItem($bomId, $itemId, $dosageRate, $wastagePct, $phaseCode = '101') {
        $sql = "INSERT INTO fg_bom_items (bom_id, item_id, dosage_rate, wastage_allowance_pct, phase_code)
                VALUES (:bom_id, :item_id, :dosage_rate, :wastage_allowance_pct, :phase_code)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'bom_id' => $bomId,
            'item_id' => $itemId,
            'dosage_rate' => $dosageRate,
            'wastage_allowance_pct' => $wastagePct,
            'phase_code' => $phaseCode !== '' ? $phaseCode : '101',
        ]);
        return self::getConnection()->lastInsertId();
    }

    public function updateItem($itemId, $newItemId, $dosageRate, $wastagePct, $phaseCode = '101') {
        $sql = "UPDATE fg_bom_items
                SET item_id = :item_id,
                    dosage_rate = :dosage_rate,
                    wastage_allowance_pct = :wastage_allowance_pct,
                    phase_code = :phase_code
                WHERE id = :id";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute([
            'id' => $itemId,
            'item_id' => $newItemId,
            'dosage_rate' => $dosageRate,
            'wastage_allowance_pct' => $wastagePct,
            'phase_code' => $phaseCode !== '' ? $phaseCode : '101',
        ]);
    }

    public function removeItem($itemId) {
        $sql = "DELETE FROM fg_bom_items WHERE id = :id";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute(['id' => $itemId]);
    }

    public function getBomForItem($fgItemId) {
        $sql = "SELECT * FROM {$this->table} WHERE fg_item_id = :fg_item_id LIMIT 1";
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
                    $offset = $idx * 5;
                    $phaseCode = ($item['phase_code'] ?? '') !== '' ? $item['phase_code'] : '101';
                    $placeholders[] = "(:bom_id_{$offset}, :item_id_{$offset}, :dosage_{$offset}, :wastage_{$offset}, :phase_{$offset})";
                    $params["bom_id_{$offset}"] = $bomId;
                    $params["item_id_{$offset}"] = $item['item_id'];
                    $params["dosage_{$offset}"] = $item['dosage_rate'];
                    $params["wastage_{$offset}"] = $item['wastage_allowance_pct'];
                    $params["phase_{$offset}"] = $phaseCode;
                }
                $sql = "INSERT INTO fg_bom_items (bom_id, item_id, dosage_rate, wastage_allowance_pct, phase_code) VALUES " . implode(', ', $placeholders);
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
