<?php
namespace App\Models;

use App\Core\BaseModel;

class ItemModel extends BaseModel {
    protected $table = 'items';

    public function getAll($activeOnly = true) {
        $sql = "SELECT * FROM {$this->table} WHERE `remove` = 0";
        if ($activeOnly) {
            $sql .= " AND status = 1";
        }
        $sql .= " ORDER BY item_code ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE item_id = :id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getByCode($code) {
        $sql = "SELECT * FROM {$this->table} WHERE item_code = :code AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['code' => $code]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (item_code, item_description, item_uom, item_size, item_amount) 
                VALUES (:item_code, :item_description, :item_uom, :item_size, :item_amount)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'item_code' => $data['item_code'],
            'item_description' => $data['item_description'],
            'item_uom' => $data['item_uom'],
            'item_size' => $data['item_size'] ?? null,
            'item_amount' => $data['item_amount'] ?? 0.00
        ]);
        return self::getConnection()->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                item_code = :item_code, 
                item_description = :item_description, 
                item_uom = :item_uom, 
                item_size = :item_size,
                item_amount = :item_amount,
                status = :status
                WHERE item_id = :id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'item_code' => $data['item_code'],
            'item_description' => $data['item_description'],
            'item_uom' => $data['item_uom'],
            'item_size' => $data['item_size'] ?? null,
            'item_amount' => $data['item_amount'] ?? 0.00,
            'status' => $data['status'] ?? 1
        ]);
    }

    public function softDelete($id) {
        $sql = "UPDATE {$this->table} SET `remove` = 1 WHERE item_id = :id";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function toggleStatus($id) {
        $sql = "UPDATE {$this->table} SET status = NOT status WHERE item_id = :id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function search($keyword) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE `remove` = 0 AND status = 1 
                AND (item_code LIKE :kw OR item_description LIKE :kw)
                ORDER BY item_code ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['kw' => "%{$keyword}%"]);
        return $stmt->fetchAll();
    }

    public function getActiveItems() {
        return $this->getAll(true);
    }

    public function getPriceByCode($code) {
        $sql = "SELECT item_amount FROM {$this->table} WHERE item_code = :code AND `remove` = 0 AND status = 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['code' => $code]);
        $result = $stmt->fetch();
        return $result ? $result['item_amount'] : null;
    }
}