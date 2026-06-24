<?php
namespace App\Models;

use App\Core\BaseModel;

class WarehouseModel extends BaseModel {
    protected $table = 'users';

    public function getByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = :username AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    public function getCustomers() {
        $sql = "SELECT * FROM customers WHERE `remove` = 0 AND status = 1 ORDER BY customer_code ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getItems() {
        $sql = "SELECT * FROM items WHERE `remove` = 0 AND status = 1 ORDER BY item_code ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createPurchaseOrder($data) {
        $sql = "INSERT INTO purchase_orders (customer_po_number, customer_po_date, customer_id, requested_by, customer_terms, production_type, date_created) 
                VALUES (:customer_po_number, :customer_po_date, :customer_id, :requested_by, :customer_terms, :production_type, NOW())";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'customer_po_number' => $data['customer_po_number'],
            'customer_po_date' => $data['customer_po_date'],
            'customer_id' => $data['customer_id'],
            'requested_by' => $data['requested_by'],
            'customer_terms' => $data['customer_terms'] ?? 0,
            'production_type' => $data['production_type'] ?? 'normal'
        ]);
        return self::getConnection()->lastInsertId();
    }

    public function createPurchaseOrderItem($po_id, $item_id, $quantity, $unit_price) {
        $sql = "INSERT INTO purchase_order_items (po_id, item_id, quantity, unit_price) 
                VALUES (:po_id, :item_id, :quantity, :unit_price)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'po_id' => $po_id,
            'item_id' => $item_id,
            'quantity' => $quantity,
            'unit_price' => $unit_price
        ]);
        
        // Update the total quantity for the PO
        $this->updatePOTotalQuantity($po_id);
        
        return $stmt->rowCount();
    }
    
    public function updatePOTotalQuantity($po_id) {
        $sql = "UPDATE purchase_orders po 
                SET total_quantity = (
                    SELECT COALESCE(SUM(quantity), 0) 
                    FROM purchase_order_items 
                    WHERE po_id = :po_id
                )
                WHERE po.po_id = :po_id2";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute(['po_id' => $po_id, 'po_id2' => $po_id]);
    }

    public function getPurchaseOrders() {
        $sql = "SELECT po.*, c.customer_name, u.full_name as requested_by_name 
                FROM purchase_orders po 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON po.requested_by = u.user_id 
                ORDER BY po.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
return $stmt->fetchAll();
    }

    public function getPurchaseOrderById($id) {
        $sql = "SELECT po.*, c.customer_name, c.customer_code, c.customer_tin 
                FROM purchase_orders po 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                WHERE po.po_id = :id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getPurchaseOrderItems($po_id) {
        $sql = "SELECT poi.*, i.item_code, i.item_description, i.item_uom, i.uom_conversion 
                FROM purchase_order_items poi 
                LEFT JOIN items i ON poi.item_id = i.item_id 
                WHERE poi.po_id = :po_id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['po_id' => $po_id]);
        return $stmt->fetchAll();
    }

    public function getPurchaseOrderItemById($poi_id) {
        $sql = "SELECT poi.*, i.item_code, i.item_description, i.item_uom, i.uom_conversion 
                FROM purchase_order_items poi 
                LEFT JOIN items i ON poi.item_id = i.item_id 
                WHERE poi.poi_id = :poi_id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['poi_id' => $poi_id]);
        return $stmt->fetch();
    }

    public function getPurchaseOrderItemsByPOIds($poIds) {
        if (empty($poIds)) return [];
        $placeholders = implode(',', array_fill(0, count($poIds), '?'));
        $sql = "SELECT poi.*, i.item_code, i.item_description, i.item_uom, i.uom_conversion 
                FROM purchase_order_items poi 
                LEFT JOIN items i ON poi.item_id = i.item_id 
                WHERE poi.po_id IN ($placeholders)
                ORDER BY poi.po_id, poi.poi_id ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($poIds);
        $rows = $stmt->fetchAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['po_id']][] = $row;
        }
        return $grouped;
    }

    public function createDelivery($data) {
        $conn = self::getConnection();
        
        $sql = "INSERT INTO deliveries (po_id, poi_id, lot_id, delivered_by, delivery_date, delivery_quantity, remarks) 
                VALUES (:po_id, :poi_id, :lot_id, :delivered_by, :delivery_date, :delivery_quantity, :remarks)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'po_id' => $data['po_id'],
            'poi_id' => $data['poi_id'] ?? null,
            'lot_id' => $data['lot_id'] ?? null,
            'delivered_by' => $data['delivered_by'],
            'delivery_date' => $data['delivery_date'],
            'delivery_quantity' => $data['delivery_quantity'],
            'remarks' => $data['remarks'] ?? ''
        ]);
        
        $conn->prepare("UPDATE purchase_orders SET delivered_quantity = delivered_quantity + :added WHERE po_id = :po_id")
            ->execute(['added' => $data['delivery_quantity'], 'po_id' => $data['po_id']]);
        
        if (!empty($data['poi_id'])) {
            $conn->prepare("UPDATE purchase_order_items SET delivered_quantity = delivered_quantity + :added WHERE poi_id = :poi_id")
                ->execute(['added' => $data['delivery_quantity'], 'poi_id' => $data['poi_id']]);
        }
        
        return self::getConnection()->lastInsertId();
    }

    public function getDeliveryById($delivery_id) {
        $sql = "SELECT d.*, po.customer_po_number, po.total_quantity, po.delivered_quantity, 
                       po.customer_terms, po.customer_id,
                       c.customer_name, c.customer_code, c.customer_address, c.customer_tin,
                       u.full_name as delivered_by_name,
                       i.item_code as delivery_item_code, i.item_description as delivery_item_description,
                       i.item_uom, i.uom_conversion,
                       l.lot_number
                FROM deliveries d 
                LEFT JOIN purchase_orders po ON d.po_id = po.po_id 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON d.delivered_by = u.user_id
                LEFT JOIN purchase_order_items poi ON d.poi_id = poi.poi_id
                LEFT JOIN items i ON poi.item_id = i.item_id
                LEFT JOIN production_lots l ON d.lot_id = l.lot_id
                WHERE d.delivery_id = :delivery_id AND d.`remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['delivery_id' => $delivery_id]);
        return $stmt->fetch();
    }

    public function getDeliveries() {
    $sql = "SELECT d.*, po.customer_po_number, po.total_quantity, po.delivered_quantity, po.production_type, c.customer_name,
                   poi.quantity as item_quantity, i.item_code, i.item_description
            FROM deliveries d 
            LEFT JOIN purchase_orders po ON d.po_id = po.po_id 
            LEFT JOIN customers c ON po.customer_id = c.customer_id 
            LEFT JOIN purchase_order_items poi ON d.poi_id = poi.poi_id
            LEFT JOIN items i ON poi.item_id = i.item_id
            ORDER BY d.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getNextPONumber() {
        $sql = "SELECT MAX(CAST(SUBSTRING(customer_po_number, 4) AS UNSIGNED)) as max_num FROM purchase_orders";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        $next = ($result['max_num'] ?? 0) + 1;
        return 'PO-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function updateProducedQuantity($po_id, $added_quantity, $user_id) {
        $conn = self::getConnection();
        
        $stmt = $conn->prepare("SELECT produced_quantity FROM purchase_orders WHERE po_id = :po_id");
        $stmt->execute(['po_id' => $po_id]);
        $po = $stmt->fetch();
        $previous_quantity = $po['produced_quantity'] ?? 0;
        $new_quantity = $previous_quantity + $added_quantity;
        
        $conn->prepare("UPDATE purchase_orders SET produced_quantity = :produced_quantity WHERE po_id = :po_id")
            ->execute(['produced_quantity' => $new_quantity, 'po_id' => $po_id]);
        
        $conn->prepare("INSERT INTO production_history (po_id, user_id, previous_quantity, added_quantity, new_quantity, date_created) VALUES (:po_id, :user_id, :previous_quantity, :added_quantity, :new_quantity, NOW())")
            ->execute([
                'po_id' => $po_id,
                'user_id' => $user_id,
                'previous_quantity' => $previous_quantity,
                'added_quantity' => $added_quantity,
                'new_quantity' => $new_quantity
            ]);
        
        return true;
    }

    public function updateItemProducedQuantity($poi_id, $added_quantity, $user_id = null) {
        $conn = self::getConnection();

        $stmt = $conn->prepare("SELECT produced_quantity, po_id FROM purchase_order_items WHERE poi_id = :poi_id");
        $stmt->execute(['poi_id' => $poi_id]);
        $item = $stmt->fetch();
        $previous_quantity = $item['produced_quantity'] ?? 0;
        $new_quantity = $previous_quantity + $added_quantity;

        $conn->prepare("UPDATE purchase_order_items SET produced_quantity = :produced WHERE poi_id = :poi_id")
            ->execute(['produced' => $new_quantity, 'poi_id' => $poi_id]);

        if ($item) {
            $conn->prepare("UPDATE purchase_orders SET produced_quantity = (
                SELECT COALESCE(SUM(produced_quantity), 0) FROM purchase_order_items WHERE po_id = :po_id
            ) WHERE po_id = :po_id2")
            ->execute(['po_id' => $item['po_id'], 'po_id2' => $item['po_id']]);

            if ($user_id) {
                $conn->prepare("INSERT INTO production_history (po_id, user_id, previous_quantity, added_quantity, new_quantity, date_created) VALUES (:po_id, :user_id, :previous_quantity, :added_quantity, :new_quantity, NOW())")
                    ->execute([
                        'po_id' => $item['po_id'],
                        'user_id' => $user_id,
                        'previous_quantity' => $previous_quantity,
                        'added_quantity' => $added_quantity,
                        'new_quantity' => $new_quantity
                    ]);
            }
        }
        return true;
    }

    public function getProductionHistory() {
        $sql = "SELECT ph.*, po.customer_po_number, c.customer_name, u.full_name 
                FROM production_history ph 
                LEFT JOIN purchase_orders po ON ph.po_id = po.po_id 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON ph.user_id = u.user_id 
                ORDER BY ph.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAdvanceProductionPOs() {
        $sql = "SELECT po.*, c.customer_name, u.full_name as requested_by_name 
                FROM purchase_orders po 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON po.requested_by = u.user_id 
                WHERE po.production_type = 'advance' AND po.`remove` = 0
                ORDER BY po.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getNormalProductionPOs() {
        $sql = "SELECT po.*, c.customer_name, u.full_name as requested_by_name 
                FROM purchase_orders po 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON po.requested_by = u.user_id 
                WHERE po.production_type = 'normal' AND po.`remove` = 0
                ORDER BY po.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateDRNumber($delivery_id, $dr_number) {
        $sql = "UPDATE deliveries SET dr_number = :dr_number WHERE delivery_id = :delivery_id";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute([
            'dr_number' => $dr_number,
            'delivery_id' => $delivery_id
        ]);
    }

    public function getDRNumbersByPOIds($poIds) {
        if (empty($poIds)) return [];
        $placeholders = implode(',', array_fill(0, count($poIds), '?'));
        $sql = "SELECT po_id, dr_number FROM deliveries 
                WHERE po_id IN ($placeholders) AND dr_number IS NOT NULL AND dr_number != '' AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($poIds);
        $rows = $stmt->fetchAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['po_id']][] = $row['dr_number'];
        }
        return $grouped;
    }

    public function createLot($data) {
        $sql = "INSERT INTO production_lots (po_id, poi_id, lot_number, quantity_produced, lot_date, created_by)
                VALUES (:po_id, :poi_id, :lot_number, :quantity_produced, :lot_date, :created_by)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'po_id' => $data['po_id'],
            'poi_id' => $data['poi_id'],
            'lot_number' => $data['lot_number'],
            'quantity_produced' => $data['quantity_produced'] ?? 0,
            'lot_date' => $data['lot_date'] ?? date('Y-m-d'),
            'created_by' => $data['created_by'] ?? null
        ]);
        return self::getConnection()->lastInsertId();
    }

    public function updateLotQuantity($poi_id, $lot_number, $added_quantity, $user_id, $po_id = null) {
        $conn = self::getConnection();
        $sql = "SELECT lot_id, quantity_produced FROM production_lots 
                WHERE poi_id = :poi_id AND lot_number = :lot_number AND `remove` = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['poi_id' => $poi_id, 'lot_number' => $lot_number]);
        $lot = $stmt->fetch();

        if ($lot) {
            $newQty = $lot['quantity_produced'] + $added_quantity;
            $sql = "UPDATE production_lots SET quantity_produced = :qty WHERE lot_id = :lot_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['qty' => $newQty, 'lot_id' => $lot['lot_id']]);
            return $lot['lot_id'];
        } else {
            return $this->createLot([
                'po_id' => $po_id,
                'poi_id' => $poi_id,
                'lot_number' => $lot_number,
                'quantity_produced' => $added_quantity,
                'created_by' => $user_id
            ]);
        }
    }

    public function getLotsByPOItem($poi_id) {
        $sql = "SELECT * FROM production_lots 
                WHERE poi_id = :poi_id AND `remove` = 0 
                ORDER BY lot_number ASC, date_created ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['poi_id' => $poi_id]);
        return $stmt->fetchAll();
    }

    public function getAvailableLotsForDelivery($poi_id) {
        $sql = "SELECT l.*, 
                    l.quantity_produced - COALESCE(
                        (SELECT SUM(d.delivery_quantity) FROM deliveries d 
                         WHERE d.lot_id = l.lot_id AND d.`remove` = 0), 0
                    ) AS available_quantity
                FROM production_lots l 
                WHERE l.poi_id = :poi_id AND l.`remove` = 0 
                HAVING available_quantity > 0
                ORDER BY l.lot_number ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['poi_id' => $poi_id]);
        return $stmt->fetchAll();
    }

    public function getLotById($lot_id) {
        $sql = "SELECT * FROM production_lots WHERE lot_id = :lot_id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['lot_id' => $lot_id]);
        return $stmt->fetch();
    }

    public function getLotRemaining($lot_id) {
        $lot = $this->getLotById($lot_id);
        if (!$lot) return 0;
        $sql = "SELECT COALESCE(SUM(delivery_quantity), 0) AS total_delivered 
                FROM deliveries WHERE lot_id = :lot_id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['lot_id' => $lot_id]);
        $row = $stmt->fetch();
        return max(0, $lot['quantity_produced'] - ($row['total_delivered'] ?? 0));
    }

    public function getLotsByPOForPrint($po_id) {
        $sql = "SELECT l.*, 
                    poi.quantity AS poi_quantity, poi.unit_price, poi.item_id,
                    i.item_code, i.item_description, i.item_uom, i.uom_conversion,
                    l.quantity_produced - COALESCE(
                        (SELECT SUM(d.delivery_quantity) FROM deliveries d 
                         WHERE d.lot_id = l.lot_id AND d.`remove` = 0), 0
                    ) AS available_quantity
                FROM production_lots l
                LEFT JOIN purchase_order_items poi ON l.poi_id = poi.poi_id
                LEFT JOIN items i ON poi.item_id = i.item_id
                WHERE l.po_id = :po_id AND l.`remove` = 0
                ORDER BY i.item_description ASC, l.lot_number ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['po_id' => $po_id]);
        $rows = $stmt->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $itemId = $row['item_id'];
            if (!isset($grouped[$itemId])) {
                $grouped[$itemId] = [
                    'item_id' => $itemId,
                    'item_code' => $row['item_code'],
                    'item_description' => $row['item_description'],
                    'item_uom' => $row['item_uom'],
                    'uom_conversion' => $row['uom_conversion'],
                    'unit_price' => $row['unit_price'],
                    'lots' => []
                ];
            }
            $grouped[$itemId]['lots'][] = $row;
        }
        return $grouped;
    }

    public function getLotsByIds($lotIds) {
        if (empty($lotIds)) return [];
        $placeholders = implode(',', array_fill(0, count($lotIds), '?'));
        $sql = "SELECT l.*, 
                    poi.quantity AS poi_quantity, poi.unit_price, poi.item_id,
                    i.item_code, i.item_description, i.item_uom, i.uom_conversion
                FROM production_lots l
                LEFT JOIN purchase_order_items poi ON l.poi_id = poi.poi_id
                LEFT JOIN items i ON poi.item_id = i.item_id
                WHERE l.lot_id IN ($placeholders) AND l.`remove` = 0
                ORDER BY i.item_description ASC, l.lot_number ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($lotIds);
        return $stmt->fetchAll();
    }
}