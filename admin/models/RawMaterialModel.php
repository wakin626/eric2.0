<?php
namespace App\Models;

use App\Core\BaseModel;

class RawMaterialModel extends BaseModel {
    protected $table = 'raw_materials';

    public function getAll($activeOnly = true) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY is_active DESC, item_code ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getByCode($code) {
        $sql = "SELECT * FROM {$this->table} WHERE item_code = :code";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['code' => $code]);
        return $stmt->fetch();
    }

    public function create($data) {
        $conn = self::getConnection();
        $check = $conn->prepare("SELECT id FROM {$this->table} WHERE item_code = :code");
        $check->execute(['code' => $data['item_code']]);
        if ($check->fetch()) {
            throw new \Exception("Item code already exists.");
        }

        $sql = "INSERT INTO {$this->table} (item_code, trade_name, category, uom, stock_on_hand, allocated_stock, reorder_level, is_active)
                VALUES (:item_code, :trade_name, :category, :uom, :stock_on_hand, :allocated_stock, :reorder_level, :is_active)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'item_code' => $data['item_code'],
            'trade_name' => $data['trade_name'],
            'category' => $data['category'] ?? 'raw_material',
            'uom' => $data['uom'] ?? 'Kg',
            'stock_on_hand' => $data['stock_on_hand'] ?? 0,
            'allocated_stock' => $data['allocated_stock'] ?? 0,
            'reorder_level' => $data['reorder_level'] ?? 0,
            'is_active' => $data['is_active'] ?? 1
        ]);
        return $conn->lastInsertId();
    }

    public function update($id, $data) {
        $conn = self::getConnection();
        $check = $conn->prepare("SELECT id FROM {$this->table} WHERE item_code = :code AND id != :id");
        $check->execute(['code' => $data['item_code'], 'id' => $id]);
        if ($check->fetch()) {
            throw new \Exception("Item code already exists.");
        }

        $sql = "UPDATE {$this->table} SET
                item_code = :item_code,
                trade_name = :trade_name,
                category = :category,
                uom = :uom,
                stock_on_hand = :stock_on_hand,
                allocated_stock = :allocated_stock,
                reorder_level = :reorder_level,
                is_active = :is_active
                WHERE id = :id";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'item_code' => $data['item_code'],
            'trade_name' => $data['trade_name'],
            'category' => $data['category'] ?? 'raw_material',
            'uom' => $data['uom'] ?? 'Kg',
            'stock_on_hand' => $data['stock_on_hand'] ?? 0,
            'allocated_stock' => $data['allocated_stock'] ?? 0,
            'reorder_level' => $data['reorder_level'] ?? 0,
            'is_active' => $data['is_active'] ?? 1
        ]);
    }

    public function softDelete($id) {
        $sql = "UPDATE {$this->table} SET is_active = 0 WHERE id = :id";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function toggleStatus($id) {
        $sql = "UPDATE {$this->table} SET is_active = NOT is_active WHERE id = :id";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function upsertByCode($data) {
        $sql = "INSERT INTO {$this->table}
                (item_code, trade_name, category, uom, stock_on_hand, allocated_stock, reorder_level, is_active)
                VALUES (:item_code, :trade_name, :category, :uom, :stock_on_hand, :allocated_stock, :reorder_level, :is_active)
                ON DUPLICATE KEY UPDATE
                    trade_name = VALUES(trade_name),
                    category = VALUES(category),
                    uom = VALUES(uom),
                    stock_on_hand = VALUES(stock_on_hand),
                    allocated_stock = VALUES(allocated_stock),
                    reorder_level = VALUES(reorder_level),
                    is_active = VALUES(is_active)";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute([
            'item_code' => $data['item_code'],
            'trade_name' => $data['trade_name'],
            'category' => $data['category'] ?? 'raw_material',
            'uom' => $data['uom'] ?? 'Kg',
            'stock_on_hand' => $data['stock_on_hand'] ?? 0,
            'allocated_stock' => $data['allocated_stock'] ?? 0,
            'reorder_level' => $data['reorder_level'] ?? 0,
            'is_active' => $data['is_active'] ?? 1
        ]);
    }

    public function getAllFiltered($filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (item_code LIKE :search1 OR trade_name LIKE :search2 OR uom LIKE :search3)";
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
        }
        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params['category'] = $filters['category'];
        }

        $sql .= " ORDER BY is_active DESC, item_code ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
