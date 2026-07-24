<?php
namespace App\Models;

use App\Core\BaseModel;

class ItemModel extends BaseModel {
    protected $table = 'items';

    public function getAll($activeOnly = true) {
        $sql = "SELECT i.*, c.customer_name FROM {$this->table} i
                LEFT JOIN customers c ON i.customer_id = c.customer_id AND c.`remove` = 0
                WHERE i.`remove` = 0";
        if ($activeOnly) {
            $sql .= " AND i.status = 1";
        }
        $sql .= " ORDER BY i.status DESC, i.item_id DESC";
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
        $customerId = !empty($data['customer_id']) ? $data['customer_id'] : null;
        if (!$customerId) {
            throw new \Exception("Customer is required.");
        }
        $conn = self::getConnection();

        $check = $conn->prepare("SELECT item_id FROM {$this->table} WHERE item_code = :code AND customer_id = :cid AND `remove` = 0");
        $check->execute(['code' => $data['item_code'], 'cid' => $customerId]);
        if ($check->fetch()) {
            throw new \Exception("Item code already exists for this customer.");
        }

        $sql = "INSERT INTO {$this->table} (item_code, item_description, customer_id, item_uom, uom_conversion, item_size, item_amount) 
                VALUES (:item_code, :item_description, :customer_id, :item_uom, :uom_conversion, :item_size, :item_amount)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'item_code' => $data['item_code'],
            'item_description' => $data['item_description'],
            'customer_id' => $customerId,
            'item_uom' => $data['item_uom'] ?? 'PCS',
            'uom_conversion' => !empty($data['uom_conversion']) ? $data['uom_conversion'] : null,
            'item_size' => $data['item_size'] ?? null,
            'item_amount' => $data['item_amount'] ?? 0.00
        ]);
        return $conn->lastInsertId();
    }

    public function update($id, $data) {
        $customerId = !empty($data['customer_id']) ? $data['customer_id'] : null;
        $conn = self::getConnection();

        if ($customerId !== null) {
            $check = $conn->prepare("SELECT item_id FROM {$this->table} WHERE item_code = :code AND customer_id = :cid AND item_id != :id AND `remove` = 0");
            $check->execute(['code' => $data['item_code'], 'cid' => $customerId, 'id' => $id]);
        } else {
            $check = $conn->prepare("SELECT item_id FROM {$this->table} WHERE item_code = :code AND customer_id IS NULL AND item_id != :id AND `remove` = 0");
            $check->execute(['code' => $data['item_code'], 'id' => $id]);
        }
        if ($check->fetch()) {
            throw new \Exception("Item code already exists for this customer.");
        }

        $sql = "UPDATE {$this->table} SET 
                item_code = :item_code, 
                item_description = :item_description, 
                customer_id = :customer_id,
                item_uom = :item_uom, 
                uom_conversion = :uom_conversion,
                item_size = :item_size,
                item_amount = :item_amount,
                status = :status
                WHERE item_id = :id AND `remove` = 0";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'item_code' => $data['item_code'],
            'item_description' => $data['item_description'],
            'customer_id' => $customerId,
            'item_uom' => $data['item_uom'] ?? 'PCS',
            'uom_conversion' => !empty($data['uom_conversion']) ? $data['uom_conversion'] : null,
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
                ORDER BY item_id DESC";
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

    public function getAllFiltered($filters = [], $activeOnly = false) {
        $sql = "SELECT i.*, c.customer_name FROM {$this->table} i
                LEFT JOIN customers c ON i.customer_id = c.customer_id AND c.`remove` = 0
                WHERE i.`remove` = 0";
        $params = [];

        if ($activeOnly) {
            $sql .= " AND i.status = 1";
        }

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (i.item_code LIKE :search1 
                       OR i.item_description LIKE :search2
                       OR c.customer_name LIKE :search3
                       OR i.item_uom LIKE :search4)";
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
        }
        if (!empty($filters['customer_id'])) {
            $sql .= " AND i.customer_id = :filter_customer_id";
            $params['filter_customer_id'] = (int)$filters['customer_id'];
        }

        $sql .= " ORDER BY i.status DESC, i.item_id DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}