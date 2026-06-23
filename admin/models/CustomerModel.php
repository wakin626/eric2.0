<?php
namespace App\Models;

use App\Core\BaseModel;
use PDO;

class CustomerModel extends BaseModel {
    protected $table = 'customers';

    public function getAll($activeOnly = true) {
        $sql = "SELECT * FROM {$this->table} WHERE `remove` = 0";
        if ($activeOnly) {
            $sql .= " AND status = 1";
        }
        $sql .= " ORDER BY customer_code ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE customer_id = :id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getByCode($code) {
        $sql = "SELECT * FROM {$this->table} WHERE customer_code = :code AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['code' => $code]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO {$this->table} (customer_code, customer_name, customer_address, customer_type, customer_tin) 
                VALUES (:customer_code, :customer_name, :customer_address, :customer_type, :customer_tin)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'customer_code' => $data['customer_code'],
            'customer_name' => $data['customer_name'],
            'customer_address' => $data['customer_address'] ?? null,
            'customer_type' => $data['customer_type'] ?? 'vat',
            'customer_tin' => $data['customer_tin'] ?? null
        ]);
        return self::getConnection()->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
                customer_code = :customer_code, 
                customer_name = :customer_name, 
                customer_address = :customer_address, 
                customer_type = :customer_type,
                customer_tin = :customer_tin,
                status = :status
                WHERE customer_id = :id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'customer_code' => $data['customer_code'],
            'customer_name' => $data['customer_name'],
            'customer_address' => $data['customer_address'] ?? null,
            'customer_type' => $data['customer_type'] ?? 'vat',
            'customer_tin' => $data['customer_tin'] ?? null,
            'status' => $data['status'] ?? 1
        ]);
    }

    public function softDelete($id) {
        $sql = "UPDATE {$this->table} SET `remove` = 1 WHERE customer_id = :id";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function toggleStatus($id) {
        $sql = "UPDATE {$this->table} SET status = NOT status WHERE customer_id = :id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function search($keyword) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE `remove` = 0 AND status = 1 
                AND (customer_code LIKE :kw OR customer_name LIKE :kw)
                ORDER BY customer_code ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['kw' => "%{$keyword}%"]);
        return $stmt->fetchAll();
    }
}