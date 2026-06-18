<?php
namespace App\Models;

use App\Core\BaseModel;

class FinanceModel extends BaseModel {

    public function getPOsReadyToDeliver() {
        $sql = "SELECT po.*, c.customer_name, c.customer_code, u.full_name as requested_by_name,
                (po.produced_quantity - po.delivered_quantity) as available_for_delivery
                FROM purchase_orders po 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON po.requested_by = u.user_id 
                WHERE po.`remove` = 0 
                AND po.produced_quantity > po.delivered_quantity
                ORDER BY po.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllPurchaseOrders() {
        $sql = "SELECT po.*, c.customer_name, c.customer_code, u.full_name as requested_by_name 
                FROM purchase_orders po 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON po.requested_by = u.user_id 
                WHERE po.`remove` = 0
                ORDER BY po.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getPurchaseOrderById($id) {
        $sql = "SELECT po.*, c.customer_name, c.customer_code, c.customer_address, c.customer_tin
                FROM purchase_orders po 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                WHERE po.po_id = :id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getPurchaseOrderItems($po_id) {
        $sql = "SELECT poi.*, i.item_code, i.item_description, i.item_uom, i.item_size 
                FROM purchase_order_items poi 
                LEFT JOIN items i ON poi.item_id = i.item_id 
                WHERE poi.po_id = :po_id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['po_id' => $po_id]);
        return $stmt->fetchAll();
    }

    public function getDeliveriesByPO($po_id) {
        $sql = "SELECT d.*, u.full_name as delivered_by_name
                FROM deliveries d 
                LEFT JOIN users u ON d.delivered_by = u.user_id 
                WHERE d.po_id = :po_id AND d.`remove` = 0
                ORDER BY d.delivery_date DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['po_id' => $po_id]);
        return $stmt->fetchAll();
    }

    public function getAllDeliveries() {
        $sql = "SELECT d.*, po.customer_po_number, po.total_quantity, po.delivered_quantity, 
                c.customer_name, u.full_name as delivered_by_name
                FROM deliveries d 
                LEFT JOIN purchase_orders po ON d.po_id = po.po_id 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON d.delivered_by = u.user_id 
                WHERE d.`remove` = 0
                ORDER BY d.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getDeliveryById($id) {
        $sql = "SELECT d.*, po.customer_po_number, po.total_quantity, po.delivered_quantity,
                c.customer_name, c.customer_code, c.customer_address, c.customer_tin,
                u.full_name as delivered_by_name
                FROM deliveries d 
                LEFT JOIN purchase_orders po ON d.po_id = po.po_id 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON d.delivered_by = u.user_id 
                WHERE d.delivery_id = :id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function attachReceipt($data) {
        $sql = "INSERT INTO delivery_receipts (delivery_id, po_id, file_name, file_path, file_type, file_size, uploaded_by) 
                VALUES (:delivery_id, :po_id, :file_name, :file_path, :file_type, :file_size, :uploaded_by)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'delivery_id' => $data['delivery_id'],
            'po_id' => $data['po_id'],
            'file_name' => $data['file_name'],
            'file_path' => $data['file_path'],
            'file_type' => $data['file_type'],
            'file_size' => $data['file_size'],
            'uploaded_by' => $data['uploaded_by']
        ]);
        return self::getConnection()->lastInsertId();
    }

    public function getReceiptsByDelivery($delivery_id) {
        $sql = "SELECT dr.*, u.full_name as uploaded_by_name
                FROM delivery_receipts dr 
                LEFT JOIN users u ON dr.uploaded_by = u.user_id 
                WHERE dr.delivery_id = :delivery_id AND dr.`remove` = 0
                ORDER BY dr.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['delivery_id' => $delivery_id]);
        return $stmt->fetchAll();
    }

    public function getReceiptsByPO($po_id) {
        $sql = "SELECT dr.*, d.delivery_date, u.full_name as uploaded_by_name
                FROM delivery_receipts dr 
                LEFT JOIN deliveries d ON dr.delivery_id = d.delivery_id
                LEFT JOIN users u ON dr.uploaded_by = u.user_id 
                WHERE dr.po_id = :po_id AND dr.`remove` = 0
                ORDER BY dr.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['po_id' => $po_id]);
        return $stmt->fetchAll();
    }

    public function deleteReceipt($receipt_id) {
        $sql = "UPDATE delivery_receipts SET `remove` = 1 WHERE receipt_id = :id";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute(['id' => $receipt_id]);
    }

    public function getReceiptById($id) {
        $sql = "SELECT * FROM delivery_receipts WHERE receipt_id = :id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getFinanceStats() {
        $data = [];

        $stmt = self::getConnection()->query("SELECT COUNT(*) as cnt FROM purchase_orders WHERE `remove` = 0");
        $data['total_pos'] = $stmt->fetch()['cnt'];

        $stmt = self::getConnection()->query("SELECT COUNT(*) as cnt FROM purchase_orders WHERE `remove` = 0 AND produced_quantity > delivered_quantity");
        $data['ready_to_deliver'] = $stmt->fetch()['cnt'];

        $stmt = self::getConnection()->query("SELECT COUNT(*) as cnt FROM deliveries WHERE `remove` = 0");
        $data['total_deliveries'] = $stmt->fetch()['cnt'];

        $stmt = self::getConnection()->query("SELECT COUNT(*) as cnt FROM delivery_receipts WHERE `remove` = 0");
        $data['total_receipts'] = $stmt->fetch()['cnt'];

        return $data;
    }

    public function getAllPurchaseOrderItems() {
        $sql = "SELECT poi.*, i.item_code, i.item_description, i.item_uom, i.item_size 
                FROM purchase_order_items poi 
                LEFT JOIN items i ON poi.item_id = i.item_id 
                WHERE poi.`remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        $all = $stmt->fetchAll();
        $map = [];
        foreach ($all as $item) {
            $map[$item['po_id']][] = $item;
        }
        return $map;
    }

    public function getDeliveriesByPOWithItems($po_id) {
        $sql = "SELECT d.*, u.full_name as delivered_by_name, i.item_description
                FROM deliveries d 
                LEFT JOIN users u ON d.delivered_by = u.user_id 
                LEFT JOIN purchase_order_items poi ON d.poi_id = poi.poi_id
                LEFT JOIN items i ON poi.item_id = i.item_id
                WHERE d.po_id = :po_id AND d.`remove` = 0
                ORDER BY d.delivery_date DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['po_id' => $po_id]);
        return $stmt->fetchAll();
    }
}
