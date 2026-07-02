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
                WHERE po.`remove` = 0
                ORDER BY po.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getPurchaseOrderById($id) {
        $sql = "SELECT po.*, c.customer_name, c.customer_code, c.customer_tin, c.customer_address
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
        
        $sql = "INSERT INTO deliveries (po_id, poi_id, lot_id, delivered_by, delivery_date, delivery_quantity, dr_number, lot_items, remarks) 
                VALUES (:po_id, :poi_id, :lot_id, :delivered_by, :delivery_date, :delivery_quantity, :dr_number, :lot_items, :remarks)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'po_id' => $data['po_id'],
            'poi_id' => $data['poi_id'] ?? null,
            'lot_id' => $data['lot_id'] ?? null,
            'delivered_by' => $data['delivered_by'],
            'delivery_date' => $data['delivery_date'],
            'delivery_quantity' => $data['delivery_quantity'],
            'dr_number' => $data['dr_number'] ?? null,
            'lot_items' => $data['lot_items'] ?? null,
            'remarks' => $data['remarks'] ?? ''
        ]);
        
        $conn->prepare("UPDATE purchase_orders SET delivered_quantity = delivered_quantity + :added WHERE po_id = :po_id")
            ->execute(['added' => $data['delivery_quantity'], 'po_id' => $data['po_id']]);
        
        $lotItems = json_decode($data['lot_items'] ?? '[]', true);
        if (is_array($lotItems) && count($lotItems) > 0) {
            $perPoi = [];
            foreach ($lotItems as $li) {
                $poiId = $li['poi_id'] ?? null;
                if ($poiId) {
                    $perPoi[$poiId] = ($perPoi[$poiId] ?? 0) + intval($li['qty'] ?? 0);
                }
            }
            foreach ($perPoi as $poiId => $qty) {
                $conn->prepare("UPDATE purchase_order_items SET delivered_quantity = delivered_quantity + :added WHERE poi_id = :poi_id")
                    ->execute(['added' => $qty, 'poi_id' => $poiId]);
            }
        } elseif (!empty($data['poi_id'])) {
            $conn->prepare("UPDATE purchase_order_items SET delivered_quantity = delivered_quantity + :added WHERE poi_id = :poi_id")
                ->execute(['added' => $data['delivery_quantity'], 'poi_id' => $data['poi_id']]);
        }
        
        return $conn->lastInsertId();
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
        $sql = "SELECT d.*, po.customer_po_number, po.customer_po_date, po.total_quantity, po.delivered_quantity, po.production_type, c.customer_name,
                   poi.quantity as item_quantity, i.item_code, i.item_description, i.item_uom, i.uom_conversion, pl.lot_number,
                   u.full_name as delivered_by_name
            FROM deliveries d 
            LEFT JOIN purchase_orders po ON d.po_id = po.po_id 
            LEFT JOIN customers c ON po.customer_id = c.customer_id 
            LEFT JOIN purchase_order_items poi ON d.poi_id = poi.poi_id
            LEFT JOIN items i ON poi.item_id = i.item_id
            LEFT JOIN production_lots pl ON d.lot_id = pl.lot_id
            LEFT JOIN users u ON d.delivered_by = u.user_id
            WHERE d.`remove` = 0
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

    public function updateItemProducedQuantity($poi_id, $added_quantity, $user_id = null, $lot_number = null, $item_description = null) {
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
                $conn->prepare("INSERT INTO production_history (po_id, poi_id, lot_number, item_description, user_id, previous_quantity, added_quantity, new_quantity, date_created) 
                    VALUES (:po_id, :poi_id, :lot_number, :item_description, :user_id, :previous_quantity, :added_quantity, :new_quantity, NOW())")
                    ->execute([
                        'po_id' => $item['po_id'],
                        'poi_id' => $poi_id,
                        'lot_number' => $lot_number,
                        'item_description' => $item_description,
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
        $sql = "SELECT ph.*, po.customer_po_number, c.customer_name, u.full_name,
                    eu.full_name as edited_by_name, ph.date_edited,
                    pr.report_id, pr.status as report_status, pr.reason as report_reason,
                    pr.report_type as report_type, pr.new_lot_number as resolved_lot
                FROM production_history ph 
                LEFT JOIN purchase_orders po ON ph.po_id = po.po_id 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON ph.user_id = u.user_id
                LEFT JOIN users eu ON ph.edited_by = eu.user_id
                LEFT JOIN production_reports pr ON ph.history_id = pr.history_id AND pr.status = 'pending'
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

    public function getDeliveriesByPOId($po_id) {
        $sql = "SELECT d.*, l.lot_number
                FROM deliveries d
                LEFT JOIN production_lots l ON d.lot_id = l.lot_id
                WHERE d.po_id = :po_id AND d.`remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['po_id' => $po_id]);
        return $stmt->fetchAll();
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
                WHERE poi_id = :poi_id AND lot_number = :lot_number AND `is_removed` = 0";
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
                WHERE poi_id = :poi_id AND `is_removed` = 0 
                ORDER BY lot_number ASC, date_created ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['poi_id' => $poi_id]);
        return $stmt->fetchAll();
    }

    public function getAvailableLotsForDelivery($poi_id) {
        $sql = "SELECT l.*, 
                    COALESCE(
                        (SELECT SUM(d.delivery_quantity) FROM deliveries d 
                         WHERE d.lot_id = l.lot_id AND d.`remove` = 0), 0
                    ) AS delivered_legacy
                FROM production_lots l 
                WHERE l.poi_id = :poi_id AND l.`is_removed` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['poi_id' => $poi_id]);
        $lots = $stmt->fetchAll();
        $conn = self::getConnection();
        $stmt2 = $conn->prepare("SELECT lot_items FROM deliveries 
                WHERE lot_items IS NOT NULL AND `remove` = 0");
        $stmt2->execute();
        $jsonDelivered = [];
        while ($r = $stmt2->fetch()) {
            $items = json_decode($r['lot_items'], true);
            if (!is_array($items)) continue;
            foreach ($items as $li) {
                if (isset($li['lot_id'])) {
                    $lid = intval($li['lot_id']);
                    $jsonDelivered[$lid] = ($jsonDelivered[$lid] ?? 0) + intval($li['qty'] ?? 0);
                }
            }
        }
        $result = [];
        foreach ($lots as $lot) {
            $lot['available_quantity'] = max(0, $lot['quantity_produced'] - ($lot['delivered_legacy'] ?? 0) - ($jsonDelivered[$lot['lot_id']] ?? 0));
            if ($lot['available_quantity'] > 0) $result[] = $lot;
        }
        usort($result, function($a, $b) { return strcmp($a['lot_number'], $b['lot_number']); });
        return $result;
    }

    public function getItemByPoiId($poi_id) {
        if (!$poi_id) return null;
        $sql = "SELECT i.item_id, i.item_code, i.item_description, i.item_uom, i.uom_conversion, poi.unit_price
                FROM purchase_order_items poi
                JOIN items i ON poi.item_id = i.item_id
                WHERE poi.poi_id = :poi_id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['poi_id' => $poi_id]);
        return $stmt->fetch();
    }

    public function getLotById($lot_id) {
        $sql = "SELECT * FROM production_lots WHERE lot_id = :lot_id AND `is_removed` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['lot_id' => $lot_id]);
        return $stmt->fetch();
    }

    public function getLotRemaining($lot_id) {
        $lot = $this->getLotById($lot_id);
        if (!$lot) return 0;
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT COALESCE(SUM(delivery_quantity), 0) AS total_delivered 
                FROM deliveries WHERE lot_id = :lot_id AND `remove` = 0");
        $stmt->execute(['lot_id' => $lot_id]);
        $row = $stmt->fetch();
        $deliveredLegacy = $row['total_delivered'] ?? 0;
        $stmt2 = $conn->prepare("SELECT lot_items FROM deliveries 
                WHERE lot_items IS NOT NULL AND `remove` = 0");
        $stmt2->execute();
        $deliveredJson = 0;
        while ($r = $stmt2->fetch()) {
            $items = json_decode($r['lot_items'], true);
            if (!is_array($items)) continue;
            foreach ($items as $li) {
                if (isset($li['lot_id']) && intval($li['lot_id']) === intval($lot_id)) {
                    $deliveredJson += intval($li['qty'] ?? 0);
                }
            }
        }
        return max(0, $lot['quantity_produced'] - $deliveredLegacy - $deliveredJson);
    }

    public function getLotsByPOForPrint($po_id) {
        $sql = "SELECT l.*, 
                    poi.quantity AS poi_quantity, poi.unit_price, poi.item_id,
                    i.item_code, i.item_description, i.item_uom, i.uom_conversion,
                    COALESCE(
                        (SELECT SUM(d.delivery_quantity) FROM deliveries d 
                         WHERE d.lot_id = l.lot_id AND d.`remove` = 0), 0
                    ) AS delivered_legacy
                FROM production_lots l
                LEFT JOIN purchase_order_items poi ON l.poi_id = poi.poi_id
                LEFT JOIN items i ON poi.item_id = i.item_id
                WHERE l.po_id = :po_id AND l.`is_removed` = 0
                ORDER BY i.item_description ASC, l.lot_number ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['po_id' => $po_id]);
        $rows = $stmt->fetchAll();
        $conn = self::getConnection();
        $stmt2 = $conn->prepare("SELECT lot_items FROM deliveries 
                WHERE lot_items IS NOT NULL AND `remove` = 0");
        $stmt2->execute();
        $jsonDelivered = [];
        while ($r = $stmt2->fetch()) {
            $items = json_decode($r['lot_items'], true);
            if (!is_array($items)) continue;
            foreach ($items as $li) {
                if (isset($li['lot_id'])) {
                    $lid = intval($li['lot_id']);
                    $jsonDelivered[$lid] = ($jsonDelivered[$lid] ?? 0) + intval($li['qty'] ?? 0);
                }
            }
        }
        foreach ($rows as &$row) {
            $row['available_quantity'] = max(0, $row['quantity_produced'] - ($row['delivered_legacy'] ?? 0) - ($jsonDelivered[$row['lot_id']] ?? 0));
        }
        unset($row);

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
                    i.item_code, i.item_description, i.item_uom, i.uom_conversion,
                    COALESCE((SELECT SUM(d.delivery_quantity) FROM deliveries d WHERE d.lot_id = l.lot_id AND d.`remove` = 0), 0) AS total_delivered
                FROM production_lots l
                LEFT JOIN purchase_order_items poi ON l.poi_id = poi.poi_id
                LEFT JOIN items i ON poi.item_id = i.item_id
                WHERE l.lot_id IN ($placeholders) AND l.`is_removed` = 0
                ORDER BY i.item_description ASC, l.lot_number ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($lotIds);
        return $stmt->fetchAll();
    }

    public function getDeliveriesByDRNumber($dr_number) {
        $sql = "SELECT d.*, po.customer_po_number, po.customer_terms, c.customer_name, c.customer_code, c.customer_address, c.customer_tin
                FROM deliveries d
                LEFT JOIN purchase_orders po ON d.po_id = po.po_id
                LEFT JOIN customers c ON po.customer_id = c.customer_id
                WHERE d.dr_number = :dr_number AND d.`remove` = 0
                ORDER BY d.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['dr_number' => $dr_number]);
        $deliveries = $stmt->fetchAll();
        $result = [];
        foreach ($deliveries as $d) {
            $lotItems = json_decode($d['lot_items'] ?? '[]', true);
            if (is_array($lotItems) && count($lotItems) > 0) {
                foreach ($lotItems as $li) {
                    $result[] = [
                        'delivery_id' => $d['delivery_id'],
                        'po_id' => $d['po_id'],
                        'lot_number' => $li['lot_number'] ?? '',
                        'item_code' => $li['item_code'] ?? '',
                        'item_description' => $li['item_description'] ?? '',
                        'delivery_quantity' => $li['qty'] ?? 0,
                        'delivery_date' => $d['delivery_date'],
                        'dr_number' => $d['dr_number'],
                        'customer_po_number' => $d['customer_po_number'] ?? '',
                        'customer_name' => $d['customer_name'] ?? '',
                        'customer_code' => $d['customer_code'] ?? '',
                        'customer_address' => $d['customer_address'] ?? '',
                        'customer_tin' => $d['customer_tin'] ?? '',
                        'customer_terms' => $d['customer_terms'] ?? 0,
                        'unit_price' => $li['unit_price'] ?? 0,
                        'item_uom' => $li['item_uom'] ?? '',
                        'uom_conversion' => $li['uom_conversion'] ?? null,
                        'item_id' => $li['item_id'] ?? null,
                    ];
                }
            } else {
                $result[] = $d;
            }
        }
        return $result;
    }

    public function getLotsByDRNumber($dr_number) {
        $sql = "SELECT l.lot_id
                FROM deliveries d
                LEFT JOIN production_lots l ON d.lot_id = l.lot_id
                WHERE d.dr_number = :dr_number AND d.`remove` = 0 AND d.lot_id IS NOT NULL";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['dr_number' => $dr_number]);
        return array_column($stmt->fetchAll(), 'lot_id');
    }

    public function saveDRNumberForLots($lotIds, $dr_number) {
        if (empty($lotIds)) return;
        $placeholders = implode(',', array_fill(0, count($lotIds), '?'));
        $sql = "UPDATE deliveries SET dr_number = ? WHERE lot_id IN ($placeholders) AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $params = array_merge([$dr_number], $lotIds);
        $stmt->execute($params);
    }

    public function checkDRNumber($dr_number) {
        $sql = "SELECT DISTINCT po_id FROM deliveries WHERE dr_number = :dr_number AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['dr_number' => $dr_number]);
        $rows = $stmt->fetchAll();
        return [
            'exists' => count($rows) > 0,
            'po_ids' => array_column($rows, 'po_id')
        ];
    }

    public function reportDelivery($deliveryId, $remarks) {
        $sql = "UPDATE deliveries SET remarks = :remarks, remarks_type = 'report'
                WHERE delivery_id = :delivery_id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['remarks' => $remarks, 'delivery_id' => $deliveryId]);
    }

    public function updateDelivery($deliveryId, $data) {
        $fields = [];
        $params = ['delivery_id' => $deliveryId];

        if (isset($data['dr_number'])) {
            $fields[] = 'dr_number = :dr_number';
            $params['dr_number'] = $data['dr_number'];
        }
        if (isset($data['delivery_date'])) {
            $fields[] = 'delivery_date = :delivery_date';
            $params['delivery_date'] = $data['delivery_date'];
        }
        if (isset($data['remarks'])) {
            $fields[] = 'remarks = :remarks';
            $params['remarks'] = $data['remarks'];
            $fields[] = "remarks_type = 'edited'";
        }

        if (empty($fields)) return;

        $sql = "UPDATE deliveries SET " . implode(', ', $fields) .
               " WHERE delivery_id = :delivery_id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
    }

    public function attachDRPhoto($data) {
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

    public function getDRPhotoByDeliveryId($delivery_id) {
        $sql = "SELECT * FROM delivery_receipts
                WHERE delivery_id = :delivery_id AND `remove` = 0
                ORDER BY date_created DESC LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['delivery_id' => $delivery_id]);
        return $stmt->fetch();
    }

    public function getReceiptsByPOId($po_id) {
        $sql = "SELECT dr.* FROM delivery_receipts dr
                WHERE dr.po_id = :po_id AND dr.`remove` = 0
                ORDER BY dr.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['po_id' => $po_id]);
        return $stmt->fetchAll();
    }

    public function deleteDRPhoto($receiptId) {
        $receipt = $this->getReceiptById($receiptId);
        if ($receipt && file_exists(__DIR__ . '/../../' . $receipt['file_path'])) {
            unlink(__DIR__ . '/../../' . $receipt['file_path']);
        }
        $sql = "UPDATE delivery_receipts SET `remove` = 1 WHERE receipt_id = :receipt_id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['receipt_id' => $receiptId]);
    }

    public function getReceiptById($receiptId) {
        $sql = "SELECT * FROM delivery_receipts WHERE receipt_id = :receipt_id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['receipt_id' => $receiptId]);
        return $stmt->fetch();
    }

    public function toggleDeliveryStatus($deliveryId) {
        $conn = self::getConnection();
        $sql = "UPDATE deliveries SET active_status = IF(active_status = 1, 0, 1)
                WHERE delivery_id = :delivery_id AND `remove` = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['delivery_id' => $deliveryId]);

        $sql2 = "SELECT active_status FROM deliveries WHERE delivery_id = :delivery_id";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->execute(['delivery_id' => $deliveryId]);
        return $stmt2->fetchColumn();
    }

    public function getReportedRemarksCount() {
        $sql = "SELECT COUNT(*) FROM deliveries WHERE remarks_type = 'report' AND `remove` = 0";
        return self::getConnection()->query($sql)->fetchColumn();
    }

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

    public function createProductionReport($history_id, $poi_id, $po_id, $old_lot_number, $user_id, $reason, $report_type = 'lot_number') {
        $sql = "INSERT INTO production_reports (history_id, poi_id, po_id, old_lot_number, reported_by, reason, report_type)
                VALUES (:history_id, :poi_id, :po_id, :old_lot_number, :reported_by, :reason, :report_type)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'history_id' => $history_id,
            'poi_id' => $poi_id,
            'po_id' => $po_id,
            'old_lot_number' => $old_lot_number,
            'reported_by' => $user_id,
            'reason' => $reason,
            'report_type' => $report_type
        ]);
        return self::getConnection()->lastInsertId();
    }

    public function getProductionReportsCount() {
        $sql = "SELECT COUNT(*) FROM production_reports WHERE status = 'pending'";
        return self::getConnection()->query($sql)->fetchColumn();
    }

    public function getProductionReportById($report_id) {
        $sql = "SELECT pr.*, ph.lot_number as history_lot, ph.item_description, ph.added_quantity,
                    u.full_name as reporter_name
                FROM production_reports pr
                LEFT JOIN production_history ph ON pr.history_id = ph.history_id
                LEFT JOIN users u ON pr.reported_by = u.user_id
                WHERE pr.report_id = :report_id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['report_id' => $report_id]);
        return $stmt->fetch();
    }

    public function resolveProductionReport($report_id, $new_lot_number, $resolved_by) {
        $conn = self::getConnection();
        $report = $this->getProductionReportById($report_id);
        if (!$report) return false;

        $conn->prepare("UPDATE production_reports SET status = 'resolved', resolved_by = :resolved_by, 
            new_lot_number = :new_lot_number, date_resolved = NOW() WHERE report_id = :report_id")
            ->execute(['resolved_by' => $resolved_by, 'new_lot_number' => $new_lot_number, 'report_id' => $report_id]);

        $conn->prepare("UPDATE production_history SET lot_number = :lot_number WHERE history_id = :history_id")
            ->execute(['lot_number' => $new_lot_number, 'history_id' => $report['history_id']]);

        if ($report['poi_id'] && $report['old_lot_number']) {
            $conn->prepare("UPDATE production_lots SET lot_number = :new_lot 
                WHERE poi_id = :poi_id AND lot_number = :old_lot AND `is_removed` = 0")
                ->execute(['new_lot' => $new_lot_number, 'poi_id' => $report['poi_id'], 'old_lot' => $report['old_lot_number']]);
        }

        return true;
    }

    public function updateHistoryLotNumber($history_id, $new_lot_number) {
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT poi_id, lot_number FROM production_history WHERE history_id = :history_id");
        $stmt->execute(['history_id' => $history_id]);
        $history = $stmt->fetch();
        if (!$history) return false;

        $conn->prepare("UPDATE production_history SET lot_number = :lot_number WHERE history_id = :history_id")
            ->execute(['lot_number' => $new_lot_number, 'history_id' => $history_id]);

        if ($history['poi_id'] && $history['lot_number']) {
            $conn->prepare("UPDATE production_lots SET lot_number = :new_lot 
                WHERE poi_id = :poi_id AND lot_number = :old_lot AND `is_removed` = 0")
                ->execute(['new_lot' => $new_lot_number, 'poi_id' => $history['poi_id'], 'old_lot' => $history['lot_number']]);
        }

        $conn->prepare("UPDATE production_reports SET status = 'resolved', new_lot_number = :new_lot, date_resolved = NOW()
            WHERE history_id = :history_id AND status = 'pending'")
            ->execute(['new_lot' => $new_lot_number, 'history_id' => $history_id]);

        return true;
    }

    public function editHistoryRecord($history_id, $new_added_quantity, $new_lot_number, $edited_by) {
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT poi_id, added_quantity, po_id, lot_number FROM production_history WHERE history_id = :history_id");
        $stmt->execute(['history_id' => $history_id]);
        $history = $stmt->fetch();
        if (!$history) return false;

        $old_added = $history['added_quantity'];
        $delta = $new_added_quantity - $old_added;
        $new_new_quantity = $history['added_quantity'] + ($new_added_quantity - $old_added);
        $new_new_quantity = $history['previous_quantity'] + $new_added_quantity;

        $conn->prepare("UPDATE production_history 
            SET added_quantity = :added, new_quantity = :new_qty, lot_number = :lot, edited_by = :edited_by, date_edited = NOW()
            WHERE history_id = :history_id")
            ->execute([
                'added' => $new_added_quantity,
                'new_qty' => $new_new_quantity,
                'lot' => $new_lot_number,
                'edited_by' => $edited_by,
                'history_id' => $history_id
            ]);

        if ($history['poi_id'] && $delta != 0) {
            $conn->prepare("UPDATE purchase_order_items SET produced_quantity = produced_quantity + :delta WHERE poi_id = :poi_id")
                ->execute(['delta' => $delta, 'poi_id' => $history['poi_id']]);

            $conn->prepare("UPDATE purchase_orders SET produced_quantity = (
                SELECT COALESCE(SUM(produced_quantity), 0) FROM purchase_order_items WHERE po_id = :po_id
            ) WHERE po_id = :po_id2")
                ->execute(['po_id' => $history['po_id'], 'po_id2' => $history['po_id']]);

            if ($new_lot_number && $history['lot_number'] && $new_lot_number !== $history['lot_number']) {
                $conn->prepare("UPDATE production_lots SET lot_number = :new_lot 
                    WHERE poi_id = :poi_id AND lot_number = :old_lot AND `is_removed` = 0")
                    ->execute(['new_lot' => $new_lot_number, 'poi_id' => $history['poi_id'], 'old_lot' => $history['lot_number']]);
            }

            $conn->prepare("UPDATE production_lots SET quantity_produced = quantity_produced + :delta 
                WHERE poi_id = :poi_id AND lot_number = :lot AND `is_removed` = 0")
                ->execute(['delta' => $delta, 'poi_id' => $history['poi_id'], 'lot' => $new_lot_number]);
        }

        $conn->prepare("UPDATE production_reports SET status = 'resolved', new_lot_number = :new_lot, date_resolved = NOW()
            WHERE history_id = :history_id AND status = 'pending'")
            ->execute(['new_lot' => $new_lot_number, 'history_id' => $history_id]);

        return true;
    }
}