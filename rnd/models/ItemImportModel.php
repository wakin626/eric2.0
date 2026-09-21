<?php
namespace App\Models;

use App\Core\BaseModel;

class ItemImportModel extends BaseModel {
    protected $table = 'items';

    public function getItemByCode($code) {
        $sql = "SELECT * FROM {$this->table} WHERE item_code = :code";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['code' => $code]);
        return $stmt->fetch();
    }

    public function upsertItem($data) {
        $existing = $this->getItemByCode($data['item_code']);
        $conn = self::getConnection();

        if ($existing) {
            $sql = "UPDATE {$this->table} SET
                    item_description = :item_description,
                    item_type = :item_type,
                    raw_c_type = :raw_c_type,
                    item_uom = :item_uom
                    WHERE item_code = :item_code";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'item_code' => $data['item_code'],
                'item_description' => $data['item_description'],
                'item_type' => $data['item_type'],
                'raw_c_type' => $data['raw_c_type'] ?? null,
                'item_uom' => $data['item_uom']
            ]);
            return $existing['item_id'];
        } else {
            $sql = "INSERT INTO {$this->table}
                    (item_code, item_description, item_type, raw_c_type, item_uom, status)
                    VALUES (:item_code, :item_description, :item_type, :raw_c_type, :item_uom, 1)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'item_code' => $data['item_code'],
                'item_description' => $data['item_description'],
                'item_type' => $data['item_type'],
                'raw_c_type' => $data['raw_c_type'] ?? null,
                'item_uom' => $data['item_uom']
            ]);
            return $conn->lastInsertId();
        }
    }

    public function upsertInventoryBalance($itemId, $siteCode, $qtyOnHand, $qtyForInspect) {
        $sql = "INSERT INTO inventory_balances (item_id, site_code, qty_on_hand, qty_allocated, qty_for_inspect)
                VALUES (:item_id, :site_code, :qty_on_hand, 0, :qty_for_inspect)
                ON DUPLICATE KEY UPDATE
                    qty_on_hand = VALUES(qty_on_hand),
                    qty_for_inspect = VALUES(qty_for_inspect)";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute([
            'item_id' => $itemId,
            'site_code' => $siteCode,
            'qty_on_hand' => $qtyOnHand,
            'qty_for_inspect' => $qtyForInspect
        ]);
    }

    public static function mapItemType($cType) {
        $cType = strtoupper(trim($cType));
        if (strpos($cType, 'RM') === 0) return 'RM';
        if (strpos($cType, 'PM') === 0) return 'PM';
        if (strpos($cType, 'FG') === 0) return 'FG';
        if (strpos($cType, 'SFG') === 0 || strpos($cType, 'FB') === 0) return 'SFG';
        return 'SUPPLIES';
    }

    public function getAllWithStock($filters = []) {
        $sql = "SELECT v.*, i.customer_id, i.date_created, i.status, i.remove
                FROM view_inventory_status v
                JOIN items i ON v.item_id = i.item_id
                WHERE i.remove = 0";
        $params = [];

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (v.item_code LIKE :search1 OR v.item_description LIKE :search2)";
            $params['search1'] = $like;
            $params['search2'] = $like;
        }
        if (!empty($filters['item_type'])) {
            $sql .= " AND v.item_type = :item_type";
            $params['item_type'] = $filters['item_type'];
        }

        $sql .= " ORDER BY v.item_code ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
