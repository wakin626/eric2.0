<?php
namespace App\Models;

use App\Core\BaseModel;

class PriceListModel extends BaseModel {

    public function getAll() {
        $sql = "SELECT pl.*, i.item_code, i.item_description, i.item_size as item_item_size, c.customer_name
                FROM price_list pl
                LEFT JOIN items i ON pl.item_id = i.item_id
                LEFT JOIN customers c ON i.customer_id = c.customer_id
                WHERE pl.`remove` = 0 ORDER BY pl.status DESC, pl.product_name ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByItemId($item_id) {
        $sql = "SELECT * FROM price_list WHERE item_id = :item_id AND `remove` = 0 AND status = 1 LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['item_id' => $item_id]);
        return $stmt->fetch();
    }

    public function getUnpricedActiveItems() {
        $sql = "SELECT i.* FROM items i
                WHERE i.`remove` = 0 AND i.status = 1
                  AND NOT EXISTS (
                      SELECT 1 FROM price_list pl
                      WHERE pl.item_id = i.item_id AND pl.`remove` = 0
                  )
                ORDER BY i.item_code ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUnpricedItemsByCustomer($customer_id) {
        $sql = "SELECT i.item_id, i.item_code, i.item_description, i.item_size, i.item_uom, i.item_amount
                FROM items i
                WHERE i.`remove` = 0 AND i.status = 1 AND i.customer_id = :customer_id
                  AND NOT EXISTS (
                      SELECT 1 FROM price_list pl
                      WHERE pl.item_id = i.item_id AND pl.`remove` = 0
                  )
                ORDER BY i.item_code ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['customer_id' => $customer_id]);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $sql = "SELECT pl.*, i.item_code, i.item_description
                FROM price_list pl
                LEFT JOIN items i ON pl.item_id = i.item_id
                WHERE pl.price_list_id = :id AND pl.`remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO price_list (item_id, product_name, net_size, price_per_pack, price_per_case, price_per_piece, vat_type) 
                VALUES (:item_id, :product_name, :net_size, :price_per_pack, :price_per_case, :price_per_piece, :vat_type)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'item_id' => $data['item_id'] ?? null,
            'product_name' => $data['product_name'],
            'net_size' => $data['net_size'] ?? null,
            'price_per_pack' => $data['price_per_pack'],
            'price_per_case' => $data['price_per_case'],
            'price_per_piece' => $data['price_per_piece'],
            'vat_type' => $data['vat_type']
        ]);
        return self::getConnection()->lastInsertId();
    }

    public function update($id, $data) {
        $sql = "UPDATE price_list SET 
                item_id = :item_id,
                product_name = :product_name, 
                net_size = :net_size, 
                price_per_pack = :price_per_pack, 
                price_per_case = :price_per_case, 
                price_per_piece = :price_per_piece,
                vat_type = :vat_type 
                WHERE price_list_id = :id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'item_id' => $data['item_id'] ?? null,
            'product_name' => $data['product_name'],
            'net_size' => $data['net_size'] ?? null,
            'price_per_pack' => $data['price_per_pack'],
            'price_per_case' => $data['price_per_case'],
            'price_per_piece' => $data['price_per_piece'],
            'vat_type' => $data['vat_type']
        ]);
    }

    public function toggleStatus($id) {
        $sql = "UPDATE price_list SET status = NOT status WHERE price_list_id = :id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function getActiveCustomers() {
        $sql = "SELECT customer_id, customer_name
                FROM customers
                WHERE `remove` = 0 AND status = 1
                ORDER BY customer_name ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function softDelete($id) {
        $sql = "UPDATE price_list SET `remove` = 1 WHERE price_list_id = :id";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
