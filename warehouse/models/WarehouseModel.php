<?php
namespace App\Models;

use App\Core\BaseModel;

class WarehouseModel extends BaseModel {
    protected $table = 'users';

    private $backloadModel;
    private $catalogModel;

    public function __construct() {
        $this->backloadModel = new BackloadModel();
        $this->backloadModel->setWarehouseModel($this);
        $this->catalogModel = new CatalogModel();
    }

    public function getByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = :username AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    // ─── Catalog (delegated to CatalogModel) ────────────────────────────────

    public function getCustomers() {
        return $this->catalogModel->getCustomers();
    }

    public function getItems() {
        return $this->catalogModel->getItems();
    }

    public function getItemsByCustomer($customer_id) {
        return $this->catalogModel->getItemsByCustomer($customer_id);
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

    public function createPurchaseOrderItem($po_id, $item_id, $quantity, $unit_price, $uom = 'PCS', $item_code = null) {
        $sql = "INSERT INTO purchase_order_items (po_id, item_id, quantity, unit_price, item_uom, item_code) 
                VALUES (:po_id, :item_id, :quantity, :unit_price, :item_uom, :item_code)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'po_id' => $po_id,
            'item_id' => $item_id,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'item_uom' => $uom,
            'item_code' => $item_code
        ]);
        
        $poi_id = self::getConnection()->lastInsertId();
        
        // Update the total quantity for the PO
        $this->updatePOTotalQuantity($po_id);
        
        return $poi_id;
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

    public function updatePurchaseOrderItem($poi_id, $quantity, $item_id = null, $unit_price = null, $item_uom = null, $item_code = null) {
        $fields = ['quantity' => $quantity, 'poi_id' => $poi_id];
        $set = "quantity = :quantity";
        if ($item_id !== null) {
            $set .= ", item_id = :item_id";
            $fields['item_id'] = $item_id;
        }
        if ($unit_price !== null) {
            $set .= ", unit_price = :unit_price";
            $fields['unit_price'] = $unit_price;
        }
        if ($item_uom !== null) {
            $set .= ", item_uom = :item_uom";
            $fields['item_uom'] = $item_uom;
        }
        if ($item_code !== null) {
            $set .= ", item_code = :item_code";
            $fields['item_code'] = $item_code;
        }
        $sql = "UPDATE purchase_order_items SET $set WHERE poi_id = :poi_id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($fields);
        
        $poSql = "SELECT po_id FROM purchase_order_items WHERE poi_id = :poi_id";
        $poStmt = self::getConnection()->prepare($poSql);
        $poStmt->execute(['poi_id' => $poi_id]);
        $row = $poStmt->fetch();
        if ($row) {
            $this->updatePOTotalQuantity($row['po_id']);
        }
        
        return $stmt->rowCount();
    }

    public function getPurchaseOrders() {
        $sql = "SELECT po.*, c.customer_name, u.full_name as requested_by_name 
                FROM purchase_orders po 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON po.requested_by = u.user_id 
                WHERE po.`remove` = 0
                ORDER BY po.last_update DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActivePOsForDashboard($limit = 5) {
        $sql = "SELECT po.*, c.customer_name, u.full_name as requested_by_name 
                FROM purchase_orders po 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON po.requested_by = u.user_id 
                WHERE po.`remove` = 0 AND po.delivered_quantity < po.total_quantity
                AND po.production_type != 'advance'
                ORDER BY po.last_update DESC
                LIMIT :limit";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getPurchaseOrderById($id) {
        $sql = "SELECT po.*, c.customer_name, c.customer_code, c.customer_tin, c.customer_address
                FROM purchase_orders po 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                WHERE po.po_id = :id AND po.`remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getPurchaseOrderItems($po_id) {
        $sql = "SELECT poi.*, i.item_code, i.item_description, COALESCE(poi.item_uom, i.item_uom) as item_uom, i.uom_conversion 
            FROM purchase_order_items poi 
            LEFT JOIN items i ON poi.item_id = i.item_id 
            WHERE poi.po_id = :po_id AND i.remove = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['po_id' => $po_id]);
        $rows = $stmt->fetchAll();
        return $rows;
    }

    public function getPurchaseOrderItemById($poi_id) {
        $sql = "SELECT poi.*, i.item_code, i.item_description, COALESCE(poi.item_uom, i.item_uom) as item_uom, i.uom_conversion,
            po.po_number, po.customer_po_number, c.customer_name
            FROM purchase_order_items poi 
            LEFT JOIN items i ON poi.item_id = i.item_id 
            LEFT JOIN purchase_orders po ON poi.po_id = po.po_id
            LEFT JOIN customers c ON po.customer_id = c.customer_id
            WHERE poi.poi_id = :poi_id AND i.remove = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['poi_id' => $poi_id]);
        return $stmt->fetch();
    }

    public function getPurchaseOrderItemsByPOIds($poIds) {
        if (empty($poIds)) return [];
        $placeholders = implode(',', array_fill(0, count($poIds), '?'));
        $sql = "SELECT poi.*, i.item_code, i.item_description, COALESCE(poi.item_uom, i.item_uom) as item_uom, i.uom_conversion 
                FROM purchase_order_items poi 
                LEFT JOIN items i ON poi.item_id = i.item_id 
                WHERE poi.po_id IN ($placeholders) AND i.remove = 0
                ORDER BY poi.po_id, poi.produced_quantity DESC, poi.poi_id ASC";
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
        $conn->beginTransaction();
        try {
        $lotItems = json_decode($data['lot_items'] ?? '[]', true);
        $actualDeliveryQty = intval($data['delivery_quantity']);

        if (is_array($lotItems) && count($lotItems) > 0) {
            $actualDeliveryQty = 0;
            $perPoi = [];
            foreach ($lotItems as $li) {
                $qty = intval($li['qty'] ?? 0);
                $actualDeliveryQty += $qty;
                $poiId = $li['poi_id'] ?? null;
                if ($poiId) {
                    $perPoi[$poiId] = ($perPoi[$poiId] ?? 0) + $qty;
                }
            }
        } else {
            $perPoi = [];
        }

        $sql = "INSERT INTO deliveries (po_id, poi_id, lot_id, delivered_by, delivery_date, delivery_quantity, dr_number, plate_number, vehicle_type, logistic_provider, lot_items, remarks, is_over_shipment) 
                VALUES (:po_id, :poi_id, :lot_id, :delivered_by, :delivery_date, :delivery_quantity, :dr_number, :plate_number, :vehicle_type, :logistic_provider, :lot_items, :remarks, :is_over_shipment)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'po_id' => $data['po_id'],
            'poi_id' => $data['poi_id'] ?? null,
            'lot_id' => $data['lot_id'] ?? null,
            'delivered_by' => $data['delivered_by'],
            'delivery_date' => $data['delivery_date'],
            'delivery_quantity' => $actualDeliveryQty,
            'dr_number' => $data['dr_number'] ?? null,
            'plate_number' => $data['plate_number'] ?? null,
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'logistic_provider' => $data['logistic_provider'] ?? null,
            'lot_items' => $data['lot_items'] ?? null,
            'remarks' => $data['remarks'] ?? '',
            'is_over_shipment' => $data['is_over_shipment'] ?? 0
        ]);
        $deliveryId = $conn->lastInsertId();

        if (!empty($perPoi)) {
            foreach ($perPoi as $poiId => $qty) {
                $conn->prepare("UPDATE purchase_order_items poi SET delivered_quantity = GREATEST(0,
                    (SELECT COALESCE(SUM(d.delivery_quantity), 0) FROM deliveries d
                    WHERE d.poi_id = poi.poi_id AND d.`remove` = 0)
                    - COALESCE((SELECT SUM(b.quantity) FROM backloads b WHERE b.poi_id = poi.poi_id AND b.`remove` = 0), 0)
                ) WHERE poi.poi_id = :poi_id")
                    ->execute(['poi_id' => $poiId]);
            }
        } elseif (!empty($data['poi_id'])) {
            $conn->prepare("UPDATE purchase_order_items poi SET delivered_quantity = GREATEST(0,
                (SELECT COALESCE(SUM(d.delivery_quantity), 0) FROM deliveries d
                WHERE d.poi_id = poi.poi_id AND d.`remove` = 0)
                - COALESCE((SELECT SUM(b.quantity) FROM backloads b WHERE b.poi_id = poi.poi_id AND b.`remove` = 0), 0)
            ) WHERE poi.poi_id = :poi_id")
                ->execute(['poi_id' => $data['poi_id']]);
        }

        $conn->prepare("UPDATE purchase_orders po SET delivered_quantity = (
            SELECT COALESCE(SUM(delivered_quantity), 0) FROM purchase_order_items WHERE po_id = :po_id
        ) WHERE po.po_id = :po_id2")
            ->execute(['po_id' => $data['po_id'], 'po_id2' => $data['po_id']]);

        if (!empty($data['po_id'])) {
            $this->recalculatePODeliveryStatus($data['po_id']);
        }

        $conn->commit();
        return $deliveryId;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function recalculatePODeliveryStatus($poId) {
        $poId = intval($poId);
        if ($poId <= 0) return false;

        $conn = self::getConnection();
        $itemStmt = $conn->prepare("SELECT poi_id, quantity FROM purchase_order_items WHERE po_id = :po_id");
        $itemStmt->execute(['po_id' => $poId]);
        $poItems = $itemStmt->fetchAll();
        if (empty($poItems)) return false;

        $deliveredByPoi = [];
        $deliveryStmt = $conn->prepare("SELECT poi_id, delivery_quantity, lot_items FROM deliveries
                WHERE po_id = :po_id AND `remove` = 0");
        $deliveryStmt->execute(['po_id' => $poId]);
        while ($delivery = $deliveryStmt->fetch()) {
            $lotItems = json_decode($delivery['lot_items'] ?? '', true);
            if (is_array($lotItems) && !empty($lotItems)) {
                foreach ($lotItems as $lotItem) {
                    $poiId = intval($lotItem['poi_id'] ?? 0);
                    if ($poiId > 0) {
                        $deliveredByPoi[$poiId] = ($deliveredByPoi[$poiId] ?? 0) + intval($lotItem['qty'] ?? 0);
                    }
                }
            } elseif (!empty($delivery['poi_id'])) {
                $poiId = intval($delivery['poi_id']);
                $deliveredByPoi[$poiId] = ($deliveredByPoi[$poiId] ?? 0) + intval($delivery['delivery_quantity'] ?? 0);
            }
        }

        $backloadStmt = $conn->prepare("SELECT poi_id, SUM(quantity) AS quantity FROM backloads
                WHERE po_id = :po_id AND `remove` = 0 GROUP BY poi_id");
        $backloadStmt->execute(['po_id' => $poId]);
        while ($backload = $backloadStmt->fetch()) {
            $poiId = intval($backload['poi_id'] ?? 0);
            if ($poiId > 0) {
                $deliveredByPoi[$poiId] = max(0, ($deliveredByPoi[$poiId] ?? 0) - intval($backload['quantity'] ?? 0));
            }
        }

        $totalDelivered = 0;
        $allItemsComplete = true;
        foreach ($poItems as $poItem) {
            $poiId = intval($poItem['poi_id']);
            $delivered = max(0, intval($deliveredByPoi[$poiId] ?? 0));
            $ordered = intval($poItem['quantity'] ?? 0);
            $totalDelivered += $delivered;
            if ($delivered < $ordered) $allItemsComplete = false;
            $conn->prepare("UPDATE purchase_order_items SET delivered_quantity = :delivered WHERE poi_id = :poi_id")
                ->execute(['delivered' => $delivered, 'poi_id' => $poiId]);
        }

        $conn->prepare("UPDATE purchase_orders SET delivered_quantity = :delivered WHERE po_id = :po_id")
            ->execute(['delivered' => $totalDelivered, 'po_id' => $poId]);

        if ($allItemsComplete) {
            $conn->prepare("UPDATE purchase_orders SET status = 'delivered', completed_at = COALESCE(completed_at, NOW()) WHERE po_id = :po_id")
                ->execute(['po_id' => $poId]);
        } else {
            $conn->prepare("UPDATE purchase_orders SET status = CASE WHEN :delivered > 0 THEN 'accepted' ELSE 'pending' END,
                    completed_at = NULL WHERE po_id = :po_id")
                ->execute(['delivered' => $totalDelivered, 'po_id' => $poId]);
        }
        return true;
    }

    public function getDeliveryById($delivery_id) {
        $sql = "SELECT d.*, po.customer_po_number, po.total_quantity, po.delivered_quantity, 
                       po.customer_terms, po.customer_id,
                       c.customer_name, c.customer_code, c.customer_address, c.customer_tin,
                       u.full_name as delivered_by_name,
                       i.item_code as delivery_item_code, i.item_description as delivery_item_description,
                       COALESCE(poi.item_uom, i.item_uom) as item_uom, i.uom_conversion,
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
                   poi.quantity as item_quantity, i.item_code, i.item_description, COALESCE(poi.item_uom, i.item_uom) as item_uom, i.uom_conversion, pl.lot_number,
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

    public function deleteDelivery($deliveryId) {
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT delivery_id, po_id, poi_id, lot_items FROM deliveries WHERE delivery_id = :delivery_id AND `remove` = 0");
        $stmt->execute(['delivery_id' => $deliveryId]);
        $delivery = $stmt->fetch();
        if (!$delivery) return false;

        $conn->beginTransaction();
        try {
            $conn->prepare("UPDATE deliveries SET `remove` = 1 WHERE delivery_id = :delivery_id")
                ->execute(['delivery_id' => $deliveryId]);

            $poiIds = [];
            if (!empty($delivery['poi_id'])) {
                $poiIds[] = $delivery['poi_id'];
            }
            $lotItems = json_decode($delivery['lot_items'] ?? '[]', true);
            if (is_array($lotItems)) {
                foreach ($lotItems as $li) {
                    if (!empty($li['poi_id']) && !in_array($li['poi_id'], $poiIds)) {
                        $poiIds[] = $li['poi_id'];
                    }
                }
            }

            foreach ($poiIds as $poiId) {
                $conn->prepare("UPDATE purchase_order_items poi SET delivered_quantity = GREATEST(0,
                    (SELECT COALESCE(SUM(d.delivery_quantity), 0) FROM deliveries d
                    WHERE d.poi_id = poi.poi_id AND d.`remove` = 0)
                    - COALESCE((SELECT SUM(b.quantity) FROM backloads b WHERE b.poi_id = poi.poi_id AND b.`remove` = 0), 0)
                ) WHERE poi.poi_id = :poi_id")
                    ->execute(['poi_id' => $poiId]);
            }

            if (!empty($delivery['po_id'])) {
                $conn->prepare("UPDATE purchase_orders po SET delivered_quantity = (
                    SELECT COALESCE(SUM(delivered_quantity), 0) FROM purchase_order_items WHERE po_id = :po_id
                ) WHERE po.po_id = :po_id2")
                    ->execute(['po_id' => $delivery['po_id'], 'po_id2' => $delivery['po_id']]);
                $this->recalculatePODeliveryStatus($delivery['po_id']);
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function deleteProductionHistory($historyId) {
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT poi_id, po_id, added_quantity, lot_number FROM production_history WHERE history_id = :history_id");
        $stmt->execute(['history_id' => $historyId]);
        $history = $stmt->fetch();
        if (!$history) return false;

        $conn->beginTransaction();
        try {
            if (!empty($history['poi_id']) && !empty($history['lot_number'])) {
                $conn->prepare("UPDATE production_lots SET quantity_produced = GREATEST(0, quantity_produced - :removed_qty)
                    WHERE poi_id = :poi_id AND lot_number = :lot_number AND `is_removed` = 0")
                    ->execute([
                        'removed_qty' => $history['added_quantity'],
                        'poi_id' => $history['poi_id'],
                        'lot_number' => $history['lot_number']
                    ]);

                $conn->prepare("UPDATE production_lots SET `is_removed` = 1
                    WHERE poi_id = :poi_id AND lot_number = :lot_number AND quantity_produced <= 0 AND `is_removed` = 0")
                    ->execute([
                        'poi_id' => $history['poi_id'],
                        'lot_number' => $history['lot_number']
                    ]);
            }

            if (!empty($history['poi_id'])) {
                $this->recalculateProducedQuantity($history['poi_id'], $conn);
            }

            $conn->prepare("DELETE FROM production_reports WHERE history_id = :history_id")
                ->execute(['history_id' => $historyId]);

            $conn->prepare("DELETE FROM production_history WHERE history_id = :history_id")
                ->execute(['history_id' => $historyId]);

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function getNextPONumber() {
        $sql = "SELECT MAX(CAST(SUBSTRING(customer_po_number, 4) AS UNSIGNED)) as max_num FROM purchase_orders WHERE `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        $next = ($result['max_num'] ?? 0) + 1;
        return 'PO-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function updateProducedQuantity($po_id, $added_quantity, $user_id) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
        $stmt = $conn->prepare("SELECT produced_quantity FROM purchase_orders WHERE po_id = :po_id FOR UPDATE");
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
        
        $conn->commit();
        return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function recalculateProducedQuantity($poiId, $conn = null) {
        return $this->recalculateProducedQuantityFromDelivery($poiId, $conn);
    }

    public function updateItemProducedQuantity($poi_id, $added_quantity, $user_id = null, $lot_number = null, $item_description = null, $sts_ref = null, $extraStsData = []) {
        $conn = self::getConnection();

        $stmt = $conn->prepare("SELECT produced_quantity, po_id FROM purchase_order_items WHERE poi_id = :poi_id");
        $stmt->execute(['poi_id' => $poi_id]);
        $item = $stmt->fetch();
        $previous_quantity = $item['produced_quantity'] ?? 0;
        $new_quantity = $previous_quantity + $added_quantity;

        $this->recalculateProducedQuantity($poi_id, $conn);

        if ($item) {
            $stmt2 = $conn->prepare("SELECT produced_quantity FROM purchase_order_items WHERE poi_id = :poi_id");
            $stmt2->execute(['poi_id' => $poi_id]);
            $new_quantity = intval($stmt2->fetchColumn());

            if ($user_id) {
                $shift = $extraStsData['shift'] ?? null;
                $rejectStatus = $extraStsData['reject_status'] ?? null;
                $stsRemarks = $extraStsData['sts_remarks'] ?? null;
                $pcsPerCase = $extraStsData['pcs_per_case'] ?? null;
                $preparedByName = $extraStsData['prepared_by_name'] ?? null;
                $checkedByName = $extraStsData['checked_by_name'] ?? null;
                $receivedByName = $extraStsData['received_by_name'] ?? null;

                $conn->prepare("INSERT INTO production_history (po_id, poi_id, lot_number, item_description, sts_ref, shift, reject_status, sts_remarks, pcs_per_case, prepared_by_name, checked_by_name, received_by_name, user_id, previous_quantity, added_quantity, new_quantity, date_created) 
                    VALUES (:po_id, :poi_id, :lot_number, :item_description, :sts_ref, :shift, :reject_status, :sts_remarks, :pcs_per_case, :prepared_by_name, :checked_by_name, :received_by_name, :user_id, :previous_quantity, :added_quantity, :new_quantity, NOW())")
                    ->execute([
                        'po_id' => $item['po_id'],
                        'poi_id' => $poi_id,
                        'lot_number' => $lot_number,
                        'item_description' => $item_description,
                        'sts_ref' => $sts_ref,
                        'shift' => $shift,
                        'reject_status' => $rejectStatus,
                        'sts_remarks' => $stsRemarks,
                        'pcs_per_case' => $pcsPerCase,
                        'prepared_by_name' => $preparedByName,
                        'checked_by_name' => $checkedByName,
                        'received_by_name' => $receivedByName,
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
        $sql = "SELECT ph.*, po.customer_po_number, po.production_type, c.customer_name, u.full_name,
                UNIX_TIMESTAMP(ph.date_created) + 300 as undo_deadline,
                (ph.date_created >= NOW() - INTERVAL 5 MINUTE) as undo_available,
                    eu.full_name as edited_by_name, ph.date_edited,
                    pr.report_id, pr.status as report_status, pr.reason as report_reason,
                    pr.report_type as report_type, pr.new_lot_number as resolved_lot,
                    poi.quantity as ordered_quantity, poi.produced_quantity as poi_produced_quantity,
                    ph.qc_remark, ph.qc_inspected_by, ph.qc_inspected_at,
                    ph.qc_inspector_name,
                    ph.qa_remark, ph.qa_inspected_by, ph.qa_inspected_at,
                    ph.qa_inspector_name,
                    COALESCE(i.item_code, ifa.item_code) as item_code
                FROM production_history ph 
                LEFT JOIN purchase_orders po ON ph.po_id = po.po_id 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON ph.user_id = u.user_id
                LEFT JOIN users eu ON ph.edited_by = eu.user_id
                LEFT JOIN production_reports pr ON ph.history_id = pr.history_id AND pr.status = 'pending'
                LEFT JOIN purchase_order_items poi ON ph.poi_id = poi.poi_id
                LEFT JOIN items i ON ph.item_id = i.item_id
                LEFT JOIN items ifa ON ph.item_description = ifa.item_description AND ph.item_id IS NULL
                WHERE ph.is_removed = 0
                ORDER BY ph.date_created ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $transferredLots = self::getConnection()->query("
            SELECT pl.item_id, pl.po_id as target_po_id, pl.poi_id, pl.lot_number, i.item_description, SUM(pl.quantity_produced) as transfer_qty, po.customer_po_number as source_po_number
            FROM production_lots pl
            LEFT JOIN purchase_order_items poi ON pl.poi_id = poi.poi_id
            LEFT JOIN items i ON poi.item_id = i.item_id
            LEFT JOIN purchase_orders po ON pl.transferred_from_po_id = po.po_id
            WHERE pl.transferred_from_po_id IS NOT NULL AND pl.is_removed = 0
            GROUP BY pl.item_id, pl.po_id, pl.poi_id, pl.lot_number, i.item_description, po.customer_po_number
        ")->fetchAll(\PDO::FETCH_ASSOC);
        $transferredMap = [];
        foreach ($transferredLots as $tl) {
            $key = ($tl['item_id'] ?? 0) . '|' . $tl['lot_number'];
            $transferredMap[$key] = ['qty' => (int)$tl['transfer_qty'], 'source' => $tl['source_po_number'] ?? ''];
        }

        $lotItemTotals = [];
        foreach ($rows as &$row) {
            $lot = $row['lot_number'] ?? '';
            $lotItemKey = ($row['item_id'] ?? 0) . '|' . $lot;

            if (!isset($lotItemTotals[$lotItemKey])) {
                if (isset($transferredMap[$lotItemKey]) && !empty($transferredMap[$lotItemKey]['qty'])) {
                    $lotItemTotals[$lotItemKey] = $transferredMap[$lotItemKey]['qty'];
                    $row['transfer_source_po'] = $transferredMap[$lotItemKey]['source'];
                    $row['transfer_qty'] = $transferredMap[$lotItemKey]['qty'];
                } else {
                    $lotItemTotals[$lotItemKey] = 0;
                }
            }
            $row['computed_prev_lot_qty'] = $lotItemTotals[$lotItemKey];
            $lotItemTotals[$lotItemKey] += $row['added_quantity'];
            $row['computed_new_lot_qty'] = $lotItemTotals[$lotItemKey];
        }
        unset($row);

        usort($rows, function ($first, $second) {
            $dateComparison = strcmp($second['date_created'] ?? '', $first['date_created'] ?? '');
            return $dateComparison !== 0
                ? $dateComparison
                : ((int)($second['history_id'] ?? 0) <=> (int)($first['history_id'] ?? 0));
        });
        return array_values($rows);
    }

    public function getProductionHistoryById($historyId) {
        $stmt = self::getConnection()->prepare("SELECT history_id, po_id, poi_id, lot_number, added_quantity, previous_quantity, new_quantity, old_added_quantity, old_lot_number FROM production_history WHERE history_id = :history_id");
        $stmt->execute(['history_id' => $historyId]);
        return $stmt->fetch();
    }

    public function getNormalProductionPOs() {
        $sql = "SELECT po.*, c.customer_name, u.full_name as requested_by_name 
                FROM purchase_orders po 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON po.requested_by = u.user_id 
                WHERE po.production_type = 'normal' AND po.`remove` = 0
                ORDER BY po.last_update DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getPurchaseOrdersFiltered($filters = []) {
        $sql = "SELECT po.*, c.customer_name, u.full_name as requested_by_name 
                FROM purchase_orders po 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON po.requested_by = u.user_id 
                WHERE po.`remove` = 0";
        $params = [];

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (po.customer_po_number LIKE :search1 
                       OR po.po_number LIKE :search2 
                       OR c.customer_name LIKE :search3
                       OR u.full_name LIKE :search4
                       OR po.production_type LIKE :search5)";
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
            $params['search5'] = $like;
        }
        if (!empty($filters['customer_name'])) {
            $sql .= " AND c.customer_name LIKE :filter_customer";
            $params['filter_customer'] = '%' . $filters['customer_name'] . '%';
        }
        if (!empty($filters['date'])) {
            $sql .= " AND DATE(po.date_created) = :filter_date";
            $params['filter_date'] = $filters['date'];
        }
        if (!empty($filters['item_description'])) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM purchase_order_items poi2
                LEFT JOIN items i2 ON poi2.item_id = i2.item_id
                WHERE poi2.po_id = po.po_id AND i2.item_description LIKE :filter_item
            )";
            $params['filter_item'] = '%' . $filters['item_description'] . '%';
        }
        if (!empty($filters['production_type'])) {
            $sql .= " AND po.production_type = :filter_prod_type";
            $params['filter_prod_type'] = $filters['production_type'];
        }
        if (!empty($filters['delivery_status'])) {
            if ($filters['delivery_status'] === 'open') {
                $sql .= " AND po.delivered_quantity < po.total_quantity";
            } elseif ($filters['delivery_status'] === 'closed') {
                $sql .= " AND po.delivered_quantity >= po.total_quantity";
            }
        }

        $sql .= " ORDER BY po.last_update DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getDeliveriesFiltered($filters = []) {
        $sql = "SELECT d.*, po.customer_po_number, po.customer_po_date, po.total_quantity, po.delivered_quantity, po.production_type, c.customer_name,
                   poi.quantity as item_quantity, i.item_code, i.item_description, COALESCE(poi.item_uom, i.item_uom) as item_uom, i.uom_conversion, pl.lot_number,
                   u.full_name as delivered_by_name
            FROM deliveries d 
            LEFT JOIN purchase_orders po ON d.po_id = po.po_id 
            LEFT JOIN customers c ON po.customer_id = c.customer_id 
            LEFT JOIN purchase_order_items poi ON d.poi_id = poi.poi_id
            LEFT JOIN items i ON poi.item_id = i.item_id
            LEFT JOIN production_lots pl ON d.lot_id = pl.lot_id
            LEFT JOIN users u ON d.delivered_by = u.user_id
            WHERE d.`remove` = 0";
        $params = [];

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (d.dr_number LIKE :search1 
                       OR po.customer_po_number LIKE :search2
                       OR c.customer_name LIKE :search3
                       OR i.item_description LIKE :search4
                       OR pl.lot_number LIKE :search5
                       OR u.full_name LIKE :search6
                       OR d.remarks LIKE :search7)";
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
            $params['search5'] = $like;
            $params['search6'] = $like;
            $params['search7'] = $like;
        }
        if (!empty($filters['customer_name'])) {
            $sql .= " AND c.customer_name LIKE :filter_customer";
            $params['filter_customer'] = '%' . $filters['customer_name'] . '%';
        }
        if (!empty($filters['item_description'])) {
            $sql .= " AND i.item_description LIKE :filter_item";
            $params['filter_item'] = '%' . $filters['item_description'] . '%';
        }
        if (!empty($filters['dr_number'])) {
            $sql .= " AND d.dr_number LIKE :filter_dr";
            $params['filter_dr'] = '%' . $filters['dr_number'] . '%';
        }
        if (!empty($filters['delivery_date'])) {
            $sql .= " AND DATE(d.delivery_date) = :filter_date";
            $params['filter_date'] = $filters['delivery_date'];
        }
        if (!empty($filters['po_number'])) {
            $sql .= " AND po.customer_po_number LIKE :filter_po";
            $params['filter_po'] = '%' . $filters['po_number'] . '%';
        }
        if (!empty($filters['delivered_by'])) {
            $sql .= " AND u.full_name LIKE :filter_delivered_by";
            $params['filter_delivered_by'] = '%' . $filters['delivered_by'] . '%';
        }
        if (!empty($filters['production_type'])) {
            $sql .= " AND po.production_type = :filter_prod_type";
            $params['filter_prod_type'] = $filters['production_type'];
        }
        if (!empty($filters['has_reports'])) {
            $sql .= " AND d.remarks_type = 'report'";
        }

        $sql .= " ORDER BY d.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getProductionHistoryFiltered($filters = []) {
        $sql = "SELECT ph.*, po.customer_po_number, po.production_type, c.customer_name, u.full_name,
                UNIX_TIMESTAMP(ph.date_created) + 300 as undo_deadline,
                (ph.date_created >= NOW() - INTERVAL 5 MINUTE) as undo_available,
                    eu.full_name as edited_by_name, ph.date_edited,
                    pr.report_id, pr.status as report_status, pr.reason as report_reason,
                    pr.report_type as report_type, pr.new_lot_number as resolved_lot,
                    poi.quantity as ordered_quantity, poi.produced_quantity as poi_produced_quantity,
                    ph.qc_remark, ph.qc_inspected_by, ph.qc_inspected_at,
                    ph.qc_inspector_name,
                    ph.qa_remark, ph.qa_inspected_by, ph.qa_inspected_at,
                    ph.qa_inspector_name,
                    COALESCE(i.item_code, ifa.item_code) as item_code
                FROM production_history ph 
                LEFT JOIN purchase_orders po ON ph.po_id = po.po_id 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON ph.user_id = u.user_id
                LEFT JOIN users eu ON ph.edited_by = eu.user_id
                LEFT JOIN production_reports pr ON ph.history_id = pr.history_id AND pr.status = 'pending'
                LEFT JOIN purchase_order_items poi ON ph.poi_id = poi.poi_id
                LEFT JOIN items i ON ph.item_id = i.item_id
                LEFT JOIN items ifa ON ph.item_description = ifa.item_description AND ph.item_id IS NULL
                WHERE ph.is_removed = 0";
        $params = [];

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (ph.lot_number LIKE :search1 
                       OR ph.item_description LIKE :search2
                       OR po.customer_po_number LIKE :search3
                       OR c.customer_name LIKE :search4
                       OR ph.sts_ref LIKE :search5
                       OR u.full_name LIKE :search6
                       OR pr.reason LIKE :search7)";
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
            $params['search5'] = $like;
            $params['search6'] = $like;
            $params['search7'] = $like;
        }
        if (!empty($filters['customer_name'])) {
            $sql .= " AND c.customer_name LIKE :filter_customer";
            $params['filter_customer'] = '%' . $filters['customer_name'] . '%';
        }
        if (!empty($filters['item_description'])) {
            $sql .= " AND ph.item_description LIKE :filter_item";
            $params['filter_item'] = '%' . $filters['item_description'] . '%';
        }
        if (!empty($filters['lot_number'])) {
            $sql .= " AND ph.lot_number = :filter_lot";
            $params['filter_lot'] = $filters['lot_number'];
        }
        if (!empty($filters['po_number'])) {
            $sql .= " AND po.customer_po_number LIKE :filter_po";
            $params['filter_po'] = '%' . $filters['po_number'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(ph.date_created) >= :filter_date_from";
            $params['filter_date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(ph.date_created) <= :filter_date_to";
            $params['filter_date_to'] = $filters['date_to'];
        }
        if (!empty($filters['has_reports'])) {
            $sql .= " AND pr.status = 'pending'";
        }

        $sql .= " ORDER BY ph.date_created ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $transferredSql = "SELECT pl.item_id, pl.po_id as target_po_id, pl.poi_id, pl.lot_number, i.item_description, SUM(pl.quantity_produced) as transfer_qty, po.customer_po_number as source_po_number
            FROM production_lots pl
            LEFT JOIN purchase_order_items poi ON pl.poi_id = poi.poi_id
            LEFT JOIN items i ON poi.item_id = i.item_id
            LEFT JOIN purchase_orders po ON pl.transferred_from_po_id = po.po_id
            WHERE pl.transferred_from_po_id IS NOT NULL AND pl.is_removed = 0";
        $transferredParams = [];
        if (!empty($filters['lot_number'])) {
            $transferredSql .= " AND pl.lot_number = :filter_lot";
            $transferredParams['filter_lot'] = $filters['lot_number'];
        }
        $transferredSql .= " GROUP BY pl.item_id, pl.po_id, pl.poi_id, pl.lot_number, i.item_description, po.customer_po_number";
        $transferredStmt = self::getConnection()->prepare($transferredSql);
        $transferredStmt->execute($transferredParams);
        $transferredLots = $transferredStmt->fetchAll(\PDO::FETCH_ASSOC);
        $transferredMap = [];
        foreach ($transferredLots as $tl) {
            $key = ($tl['item_id'] ?? 0) . '|' . $tl['lot_number'];
            $transferredMap[$key] = ['qty' => (int)$tl['transfer_qty'], 'source' => $tl['source_po_number'] ?? ''];
        }

        $lotItemTotals = [];
        foreach ($rows as &$row) {
            $lot = $row['lot_number'] ?? '';
            $lotItemKey = ($row['item_id'] ?? 0) . '|' . $lot;

            if (!isset($lotItemTotals[$lotItemKey])) {
                if (isset($transferredMap[$lotItemKey]) && !empty($transferredMap[$lotItemKey]['qty'])) {
                    $lotItemTotals[$lotItemKey] = $transferredMap[$lotItemKey]['qty'];
                    $row['transfer_source_po'] = $transferredMap[$lotItemKey]['source'];
                    $row['transfer_qty'] = $transferredMap[$lotItemKey]['qty'];
                } else {
                    $lotItemTotals[$lotItemKey] = 0;
                }
            }
            $row['computed_prev_lot_qty'] = $lotItemTotals[$lotItemKey];
            $lotItemTotals[$lotItemKey] += $row['added_quantity'];
            $row['computed_new_lot_qty'] = $lotItemTotals[$lotItemKey];
        }
        unset($row);

        usort($rows, function ($first, $second) {
            $dateComparison = strcmp($second['date_created'] ?? '', $first['date_created'] ?? '');
            return $dateComparison !== 0
                ? $dateComparison
                : ((int)($second['history_id'] ?? 0) <=> (int)($first['history_id'] ?? 0));
        });
        return array_values($rows);
    }

    public function getPOsReadyToDeliverFiltered($filters = []) {
        $sql = "SELECT po.*, c.customer_name, c.customer_code, u.full_name as requested_by_name,
                (po.produced_quantity - po.delivered_quantity) as available_for_delivery
                FROM purchase_orders po 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON po.requested_by = u.user_id 
                WHERE po.`remove` = 0 
                AND po.delivered_quantity < po.total_quantity";
        $params = [];

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (po.customer_po_number LIKE :search1 
                       OR po.po_number LIKE :search2
                       OR c.customer_name LIKE :search3
                       OR u.full_name LIKE :search4)";
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
        }

        $sql .= " ORDER BY po.last_update DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
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
        $sql = "INSERT INTO production_lots (po_id, poi_id, lot_number, quantity_produced, pcs_per_case, lot_date, created_by)
                VALUES (:po_id, :poi_id, :lot_number, :quantity_produced, :pcs_per_case, :lot_date, :created_by)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'po_id' => $data['po_id'],
            'poi_id' => $data['poi_id'],
            'lot_number' => $data['lot_number'],
            'quantity_produced' => $data['quantity_produced'] ?? 0,
            'pcs_per_case' => $data['pcs_per_case'] ?? null,
            'lot_date' => $data['lot_date'] ?? date('Y-m-d'),
            'created_by' => $data['created_by'] ?? null
        ]);
        return self::getConnection()->lastInsertId();
    }

    public function updateLotQuantity($poi_id, $lot_number, $added_quantity, $user_id, $po_id = null, $pcs_per_case = null) {
        $conn = self::getConnection();
        $sql = "SELECT lot_id, quantity_produced, pcs_per_case FROM production_lots 
                WHERE poi_id = :poi_id AND lot_number = :lot_number AND `is_removed` = 0 FOR UPDATE";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['poi_id' => $poi_id, 'lot_number' => $lot_number]);
        $lot = $stmt->fetch();

        if ($lot) {
            $existingPcs = $lot['pcs_per_case'] !== null ? intval($lot['pcs_per_case']) : null;
            $newPcs = $pcs_per_case !== null ? intval($pcs_per_case) : null;
            $pcsMatches = ($existingPcs === $newPcs);

            if ($pcsMatches) {
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
                    'pcs_per_case' => $pcs_per_case,
                    'created_by' => $user_id
                ]);
            }
        } else {
            return $this->createLot([
                'po_id' => $po_id,
                'poi_id' => $poi_id,
                'lot_number' => $lot_number,
                'quantity_produced' => $added_quantity,
                'pcs_per_case' => $pcs_per_case,
                'created_by' => $user_id
            ]);
        }
    }

    public function getLotsByPOItem($poi_id, $po_id = null) {
        $conn = self::getConnection();

        // When po_id is provided, derive lots from deliveries' lot_items JSON
        // This matches how delivered_quantity is computed in purchase_order_items
        if (!is_null($po_id)) {
            $stmt = $conn->prepare("SELECT lot_items FROM deliveries 
                WHERE po_id = :po_id AND lot_items IS NOT NULL AND `remove` = 0");
            $stmt->execute(['po_id' => $po_id]);

            $lotMap = [];
            while ($r = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $items = json_decode($r['lot_items'], true);
                if (!is_array($items)) continue;
                foreach ($items as $li) {
                    $lPoi = (int)($li['poi_id'] ?? 0);
                    $qty = (int)($li['qty'] ?? 0);
                    $lotNum = $li['lot_number'] ?? '';
                    if ($lPoi == $poi_id && $qty > 0 && $lotNum !== '') {
                        if (!isset($lotMap[$lotNum])) {
                            $lotMap[$lotNum] = ['lot_number' => $lotNum, 'quantity_delivered' => 0];
                        }
                        $lotMap[$lotNum]['quantity_delivered'] += $qty;
                    }
                }
            }

            ksort($lotMap);
            return array_values($lotMap);
        }

        // Fallback: return production lots (used by FG inventory / reports)
        $sql = "SELECT pl.lot_id, pl.poi_id, pl.lot_number, pl.quantity_produced,
                       MIN(pl.lot_date) as lot_date, MIN(pl.created_by) as created_by, MIN(pl.date_created) as date_created
                FROM production_lots pl
                WHERE pl.poi_id = :poi_id
                AND pl.is_removed = 0 
                GROUP BY pl.lot_id, pl.lot_number, pl.quantity_produced
                ORDER BY lot_number ASC, date_created ASC";

        $stmt = $conn->prepare($sql);
        $stmt->execute(['poi_id' => $poi_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAvailableLotsForPO($po_id) {
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT delivery_id, delivery_date, lot_items FROM deliveries 
                WHERE po_id = :po_id AND lot_items IS NOT NULL AND `remove` = 0
                ORDER BY delivery_date ASC, delivery_id ASC");
        $stmt->execute(['po_id' => $po_id]);
        $jsonDelivered = [];
        $returnedConsumed = [];
        while ($r = $stmt->fetch()) {
            $items = json_decode($r['lot_items'], true);
            if (!is_array($items)) continue;
            foreach ($items as $li) {
                if (isset($li['lot_id'])) {
                    $lid = intval($li['lot_id']);
                    $qty = intval($li['qty'] ?? 0);
                    $jsonDelivered[$lid] = ($jsonDelivered[$lid] ?? 0) + $qty;
                    $returnedConsumed[$lid] = ($returnedConsumed[$lid] ?? 0) + intval($li['returned_qty'] ?? 0);
                }
            }
        }

        $jsonBackloaded = [];
        $blStmt = $conn->prepare("SELECT b.lot_id, b.quantity
                FROM backloads b WHERE b.po_id = :po_id AND b.`remove` = 0");
        $blStmt->execute(['po_id' => $po_id]);
        while ($bl = $blStmt->fetch()) {
            $lid = intval($bl['lot_id']);
            $jsonBackloaded[$lid] = ($jsonBackloaded[$lid] ?? 0) + intval($bl['quantity']);
        }

        $stmtPoi = $conn->prepare("SELECT poi_id, item_id FROM purchase_order_items WHERE po_id = :po_id");
        $stmtPoi->execute(['po_id' => $po_id]);
        $normalItems = $stmtPoi->fetchAll();
        $allPoiIds = array_column($normalItems, 'poi_id');
        if (empty($allPoiIds)) return [];

        $placeholders = implode(',', array_fill(0, count($allPoiIds), '?'));
        $stmt2 = $conn->prepare("SELECT l.* FROM production_lots l 
                WHERE l.poi_id IN ($placeholders) AND l.`is_removed` = 0");
        $stmt2->execute(array_values($allPoiIds));
        $lots = $stmt2->fetchAll();

        $merged = [];
        foreach ($lots as $lot) {
            $lid = $lot['lot_id'];
            $lot['available_quantity'] = max(0, $lot['quantity_produced'] - ($jsonDelivered[$lid] ?? 0) + ($jsonBackloaded[$lid] ?? 0));
            $lot['backloaded_qty'] = max(0, ($jsonBackloaded[$lid] ?? 0) - ($returnedConsumed[$lid] ?? 0));
            if ($lot['available_quantity'] <= 0) continue;

            $key = $lot['lot_number'] . '_' . $lot['poi_id'];
            if (isset($merged[$key])) {
                $merged[$key]['available_quantity'] += $lot['available_quantity'];
                $merged[$key]['quantity_produced'] += $lot['quantity_produced'];
                $merged[$key]['backloaded_qty'] += $lot['backloaded_qty'];
            } else {
                $merged[$key] = $lot;
            }
        }
        $result = array_values($merged);
        usort($result, function($a, $b) {
            $dateCmp = strtotime($a['date_created']) - strtotime($b['date_created']);
            return $dateCmp !== 0 ? $dateCmp : strcmp($a['lot_number'], $b['lot_number']);
        });
        return $result;
    }

    public function getAvailableLotsForDelivery($poi_id) {
        $conn = self::getConnection();
        $poiStmt = $conn->prepare("SELECT po_id FROM purchase_order_items WHERE poi_id = :poi_id");
        $poiStmt->execute(['poi_id' => $poi_id]);
        $po_id = $poiStmt->fetchColumn();
        if (!$po_id) return [];

        $sql = "SELECT l.* FROM production_lots l 
                WHERE l.poi_id = :poi_id AND l.`is_removed` = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['poi_id' => $poi_id]);
        $lots = $stmt->fetchAll();

        $stmt2 = $conn->prepare("SELECT delivery_id, lot_items FROM deliveries 
                WHERE po_id = :po_id AND lot_items IS NOT NULL AND `remove` = 0");
        $stmt2->execute(['po_id' => $po_id]);
        $jsonDelivered = [];
        $returnedConsumed = [];
        while ($r = $stmt2->fetch()) {
            $items = json_decode($r['lot_items'], true);
            if (!is_array($items)) continue;
            foreach ($items as $li) {
                if (isset($li['lot_id'])) {
                    $lid = intval($li['lot_id']);
                    $jsonDelivered[$lid] = ($jsonDelivered[$lid] ?? 0) + intval($li['qty'] ?? 0);
                    $returnedConsumed[$lid] = ($returnedConsumed[$lid] ?? 0) + intval($li['returned_qty'] ?? 0);
                }
            }
        }

        $jsonBackloaded = [];
        $blStmt = $conn->prepare("SELECT b.lot_id, b.quantity
                FROM backloads b INNER JOIN deliveries d ON b.delivery_id = d.delivery_id
                WHERE d.po_id = :po_id AND b.`remove` = 0");
        $blStmt->execute(['po_id' => $po_id]);
        while ($bl = $blStmt->fetch()) {
            $lid = intval($bl['lot_id']);
            $jsonBackloaded[$lid] = ($jsonBackloaded[$lid] ?? 0) + intval($bl['quantity']);
        }

        $merged = [];
        foreach ($lots as $lot) {
            $lid = $lot['lot_id'];
            $lot['available_quantity'] = max(0, $lot['quantity_produced'] - ($jsonDelivered[$lid] ?? 0) + ($jsonBackloaded[$lid] ?? 0));
            $lot['backloaded_qty'] = max(0, ($jsonBackloaded[$lid] ?? 0) - ($returnedConsumed[$lid] ?? 0));
            if ($lot['available_quantity'] <= 0) continue;
            $key = $lot['lot_number'] . '_' . $lot['poi_id'];
            if (isset($merged[$key])) {
                $merged[$key]['available_quantity'] += $lot['available_quantity'];
                $merged[$key]['quantity_produced'] += $lot['quantity_produced'];
                $merged[$key]['backloaded_qty'] += $lot['backloaded_qty'];
            } else {
                $merged[$key] = $lot;
            }
        }
        $result = array_values($merged);
        usort($result, function($a, $b) {
            $dateCmp = strtotime($a['date_created']) - strtotime($b['date_created']);
            return $dateCmp !== 0 ? $dateCmp : strcmp($a['lot_number'], $b['lot_number']);
        });
        return $result;
    }

    public function getItemByPoiId($poi_id) {
        return $this->catalogModel->getItemByPoiId($poi_id);
    }

    public function getItemById($item_id) {
        return $this->catalogModel->getItemById($item_id);
    }

    public function getLotById($lot_id) {
        $sql = "SELECT * FROM production_lots WHERE lot_id = :lot_id AND `is_removed` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['lot_id' => $lot_id]);
        return $stmt->fetch();
    }

    public function getLotsByLotNumber($lot_number, $poi_id) {
        $conn = self::getConnection();
        $stmt = $conn->prepare("SELECT * FROM production_lots 
                WHERE lot_number = :lot_number AND poi_id = :poi_id AND `is_removed` = 0");
        $stmt->execute(['lot_number' => $lot_number, 'poi_id' => $poi_id]);
        return $stmt->fetchAll();
    }

    public function getLotRemaining($lot_id) {
        $lot = $this->getLotById($lot_id);
        if (!$lot) return 0;
        $conn = self::getConnection();
        $poi_id = $lot['poi_id'];

        if ($poi_id) {
            $poiStmt = $conn->prepare("SELECT po_id FROM purchase_order_items WHERE poi_id = :poi_id");
            $poiStmt->execute(['poi_id' => $poi_id]);
            $po_id = $poiStmt->fetchColumn();
            if (!$po_id) return 0;
            $stmt2 = $conn->prepare("SELECT lot_items FROM deliveries 
                    WHERE po_id = :po_id AND lot_items IS NOT NULL AND `remove` = 0");
            $stmt2->execute(['po_id' => $po_id]);
        } else {
            // For independent lots, search deliveries containing this lot_id in JSON
            $lotIdJson = json_encode(intval($lot_id));
            $stmt2 = $conn->prepare("SELECT lot_items FROM deliveries 
                    WHERE lot_items IS NOT NULL AND `remove` = 0
                    AND lot_items LIKE :pattern");
            $stmt2->execute(['pattern' => '%"lot_id":' . $lot_id . '%']);
        }
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

        if ($poi_id) {
            $backloadedStmt = $conn->prepare("SELECT COALESCE(SUM(b.quantity), 0) FROM backloads b
                    INNER JOIN deliveries d ON b.delivery_id = d.delivery_id
                    WHERE d.po_id = :po_id AND b.lot_id = :lot_id AND b.`remove` = 0");
            $backloadedStmt->execute(['po_id' => $po_id, 'lot_id' => $lot_id]);
        } else {
            $backloadedStmt = $conn->prepare("SELECT COALESCE(SUM(b.quantity), 0) FROM backloads b
                    WHERE b.lot_id = :lot_id AND b.`remove` = 0");
            $backloadedStmt->execute(['lot_id' => $lot_id]);
        }
        $backloaded = intval($backloadedStmt->fetchColumn());

        return max(0, $lot['quantity_produced'] - $deliveredJson + $backloaded);
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
        $lotActualConv = [];
        while ($r = $stmt2->fetch()) {
            $items = json_decode($r['lot_items'], true);
            if (!is_array($items)) continue;
            foreach ($items as $li) {
                if (isset($li['lot_id'])) {
                    $lid = intval($li['lot_id']);
                    $jsonDelivered[$lid] = ($jsonDelivered[$lid] ?? 0) + intval($li['qty'] ?? 0);
                    if (!isset($lotActualConv[$lid]) && !empty($li['actual_uom_conversion'])) {
                        $lotActualConv[$lid] = intval($li['actual_uom_conversion']);
                    }
                }
            }
        }
        foreach ($rows as &$row) {
            $row['available_quantity'] = max(0, $row['quantity_produced'] - ($row['delivered_legacy'] ?? 0) - ($jsonDelivered[$row['lot_id']] ?? 0));
            if (isset($lotActualConv[$row['lot_id']])) {
                $row['actual_uom_conversion'] = $lotActualConv[$row['lot_id']];
            }
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
        $temp = [];
        foreach ($deliveries as $d) {
            $lotItems = json_decode($d['lot_items'] ?? '[]', true);
            if (is_array($lotItems) && count($lotItems) > 0) {
                foreach ($lotItems as $li) {
                    $key = ($li['lot_number'] ?? '') . '||' . ($li['item_code'] ?? '');
                    if (isset($temp[$key])) {
                        $temp[$key]['delivery_quantity'] += $li['qty'] ?? 0;
                    } else {
                        $temp[$key] = [
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
                            'actual_uom_conversion' => $li['actual_uom_conversion'] ?? null,
                            'item_id' => $li['item_id'] ?? null,
                            'remarks' => $d['remarks'] ?? '',
                            'plate_number' => $d['plate_number'] ?? '',
                            'vehicle_type' => $d['vehicle_type'] ?? '',
                            'logistic_provider' => $d['logistic_provider'] ?? '',
                        ];
                    }
                }
            } else {
                $temp[] = $d;
            }
        }
        return array_values($temp);
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
        $sql = "UPDATE deliveries SET report_remarks = :remarks, remarks_type = 'report'
                WHERE delivery_id = :delivery_id AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['remarks' => $remarks, 'delivery_id' => $deliveryId]);
    }

    public function createDeliveryReport($deliveryId, $poiId, $poId, $lotId, $oldQuantity, $userId, $reason, $reportType = 'dr_number') {
        $sql = "INSERT INTO delivery_reports (delivery_id, poi_id, po_id, lot_id, old_quantity, reported_by, reason, report_type)
                VALUES (:delivery_id, :poi_id, :po_id, :lot_id, :old_quantity, :reported_by, :reason, :report_type)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'delivery_id' => $deliveryId,
            'poi_id' => $poiId,
            'po_id' => $poId,
            'lot_id' => $lotId,
            'old_quantity' => $oldQuantity,
            'reported_by' => $userId,
            'reason' => $reason,
            'report_type' => $reportType
        ]);
        return self::getConnection()->lastInsertId();
    }

    public function getDeliveryReportsByDeliveryId($deliveryId) {
        $sql = "SELECT dr.*, u.full_name as reporter_name, ru.full_name as resolver_name
                FROM delivery_reports dr
                LEFT JOIN users u ON dr.reported_by = u.user_id
                LEFT JOIN users ru ON dr.resolved_by = ru.user_id
                WHERE dr.delivery_id = :delivery_id
                ORDER BY dr.date_reported DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['delivery_id' => $deliveryId]);
        return $stmt->fetchAll();
    }

    public function getDeliveryReportsCount() {
        $sql = "SELECT COUNT(DISTINCT dr.delivery_id) FROM delivery_reports dr
                INNER JOIN deliveries d ON dr.delivery_id = d.delivery_id
                WHERE dr.status = 'pending' AND d.remove = 0";
        return self::getConnection()->query($sql)->fetchColumn();
    }

    public function getDeliveryReportById($reportId) {
        $sql = "SELECT dr.*, u.full_name as reporter_name, d.lot_items, d.dr_number
                FROM delivery_reports dr
                LEFT JOIN users u ON dr.reported_by = u.user_id
                LEFT JOIN deliveries d ON dr.delivery_id = d.delivery_id
                WHERE dr.report_id = :report_id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['report_id' => $reportId]);
        return $stmt->fetch();
    }

    public function resolveDeliveryReport($reportId, $newQuantity, $resolvedBy, $newDrNumber = null) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
        $report = $this->getDeliveryReportById($reportId);
        if (!$report) { $conn->rollBack(); return false; }

        $deliveryId = $report['delivery_id'];

        // 1. Mark report as resolved
        $conn->prepare("UPDATE delivery_reports SET status = 'resolved', resolved_by = :resolved_by,
            new_quantity = :new_quantity, date_resolved = NOW() WHERE report_id = :report_id")
            ->execute(['resolved_by' => $resolvedBy, 'new_quantity' => $newQuantity, 'report_id' => $reportId]);

        // 2. Update delivery remarks_type from 'report' to 'edited'
        $conn->prepare("UPDATE deliveries SET remarks_type = 'edited'
            WHERE delivery_id = :delivery_id AND remarks_type = 'report'")
            ->execute(['delivery_id' => $deliveryId]);

        if ($report['report_type'] === 'quantity' && $report['lot_id']) {
            // 3a. Quantity report — update lot_items JSON
            $lotId = $report['lot_id'];
            $lotItems = json_decode($report['lot_items'] ?? '[]', true);
            if (!is_array($lotItems)) $lotItems = [];
            foreach ($lotItems as &$li) {
                if (isset($li['lot_id']) && intval($li['lot_id']) === intval($lotId)) {
                    $li['qty'] = $newQuantity;
                    break;
                }
            }
            unset($li);
            $newLotItemsJson = json_encode($lotItems);

            // 4a. Recalculate delivery_quantity = SUM of all lot_items qty
            $newDeliveryQty = 0;
            foreach ($lotItems as $li) {
                $newDeliveryQty += intval($li['qty'] ?? 0);
            }

            $conn->prepare("UPDATE deliveries SET lot_items = :lot_items, delivery_quantity = :delivery_quantity
                WHERE delivery_id = :delivery_id")
                ->execute(['lot_items' => $newLotItemsJson, 'delivery_quantity' => $newDeliveryQty, 'delivery_id' => $deliveryId]);

            // 5a. Recalculate purchase_order_items.delivered_quantity
            $conn->prepare("UPDATE purchase_order_items poi SET delivered_quantity = GREATEST(0,
                (SELECT COALESCE(SUM(d.delivery_quantity), 0) FROM deliveries d
                WHERE d.poi_id = poi.poi_id AND d.`remove` = 0)
                - COALESCE((SELECT SUM(b.quantity) FROM backloads b WHERE b.poi_id = poi.poi_id AND b.`remove` = 0), 0)
            ) WHERE poi.poi_id = :poi_id")
                ->execute(['poi_id' => $report['poi_id']]);

            // 6a. Recalculate purchase_orders.delivered_quantity
            $conn->prepare("UPDATE purchase_orders po SET delivered_quantity = (
                SELECT COALESCE(SUM(delivered_quantity), 0) FROM purchase_order_items WHERE po_id = :po_id
            ) WHERE po.po_id = :po_id2")
                ->execute(['po_id' => $report['po_id'], 'po_id2' => $report['po_id']]);
        } elseif ($report['report_type'] === 'dr_number' && $newDrNumber) {
            // 3b. DR Number report — update dr_number
            $conn->prepare("UPDATE deliveries SET dr_number = :dr_number
                WHERE delivery_id = :delivery_id")
                ->execute(['dr_number' => $newDrNumber, 'delivery_id' => $deliveryId]);
        }

        $conn->commit();
        return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function updateDelivery($deliveryId, $data) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
        $fields = [];
        $params = ['delivery_id' => $deliveryId];

        if (isset($data['dr_number'])) {
            // Get current dr_number to store as old
            $stmt = $conn->prepare("SELECT dr_number FROM deliveries WHERE delivery_id = :delivery_id");
            $stmt->execute(['delivery_id' => $deliveryId]);
            $current = $stmt->fetch();
            if ($current && $current['dr_number'] !== $data['dr_number'] && !empty($current['dr_number'])) {
                $fields[] = 'old_dr_number = :old_dr_number';
                $params['old_dr_number'] = $current['dr_number'];
            }
            $fields[] = 'dr_number = :dr_number';
            $params['dr_number'] = $data['dr_number'];
        }
        if (isset($data['delivery_date'])) {
            $fields[] = 'delivery_date = :delivery_date';
            $params['delivery_date'] = $data['delivery_date'];
        }

        // Handle lot quantity changes
        $lotChanges = $data['lot_changes'] ?? [];
        if (!empty($lotChanges)) {
            // Get current lot_items
            $stmt = $conn->prepare("SELECT lot_items, po_id, poi_id, delivery_quantity FROM deliveries WHERE delivery_id = :delivery_id");
            $stmt->execute(['delivery_id' => $deliveryId]);
            $delivery = $stmt->fetch();
            if ($delivery) {
                $lotItems = json_decode($delivery['lot_items'] ?? '[]', true);
                if (!is_array($lotItems)) $lotItems = [];

                // Validate: check available quantity for each lot change
                foreach ($lotChanges as $change) {
                    $changeLotId = intval($change['lot_id'] ?? 0);
                    $newQty = intval($change['new_qty'] ?? 0);

                    // Get lot produced quantity
                    $stmtLot = $conn->prepare("SELECT quantity_produced FROM production_lots WHERE lot_id = :lot_id AND `is_removed` = 0");
                    $stmtLot->execute(['lot_id' => $changeLotId]);
                    $lotProduced = $stmtLot->fetchColumn();

                    // Count this lot from OTHER deliveries' lot_items JSON
                    $stmtJson = $conn->prepare("SELECT lot_items FROM deliveries WHERE `remove` = 0 AND delivery_id != :delivery_id AND lot_items IS NOT NULL");
                    $stmtJson->execute(['delivery_id' => $deliveryId]);
                    $otherJsonDelivered = 0;
                    while ($row = $stmtJson->fetch()) {
                        $items = json_decode($row['lot_items'], true);
                        if (!is_array($items)) continue;
                        foreach ($items as $li) {
                            if (isset($li['lot_id']) && intval($li['lot_id']) === $changeLotId) {
                                $otherJsonDelivered += intval($li['qty'] ?? 0);
                            }
                        }
                    }

                    // Get current qty for this lot in THIS delivery
                    $currentQty = 0;
                    foreach ($lotItems as $li) {
                        if (isset($li['lot_id']) && intval($li['lot_id']) === $changeLotId) {
                            $currentQty = intval($li['qty'] ?? 0);
                            break;
                        }
                    }

                    // Available = produced - (other deliveries' qty for this lot)
                    // The current delivery's old qty is being replaced, so it doesn't count
                    $available = intval($lotProduced) - $otherJsonDelivered;

                    if ($newQty > $available) {
                        return ['success' => false, 'error' => 'Cannot set quantity to ' . $newQty . '. Only ' . $available . ' available for lot ' . $changeLotId . ' (produced: ' . $lotProduced . ', delivered by others: ' . $otherJsonDelivered . ')'];
                    }
                }

                foreach ($lotChanges as $change) {
                    $changeLotId = intval($change['lot_id'] ?? 0);
                    $newQty = intval($change['new_qty'] ?? 0);
                    $actualConv = !empty($change['actual_uom_conversion']) ? intval($change['actual_uom_conversion']) : null;
                    foreach ($lotItems as &$li) {
                        if (isset($li['lot_id']) && intval($li['lot_id']) === $changeLotId) {
                            $oldQty = intval($li['qty'] ?? 0);
                            $li['qty'] = $newQty;
                            if ($actualConv !== null) {
                                $li['actual_uom_conversion'] = $actualConv;
                            }
                            if ($oldQty !== $newQty) {
                                $existingOld = json_decode($delivery['old_quantity'] ?? '{}', true);
                                if (!is_array($existingOld)) $existingOld = [];
                                $existingOld[strval($changeLotId)] = $oldQty;
                                $fields[] = 'old_quantity = :old_quantity';
                                $params['old_quantity'] = json_encode($existingOld);
                            }
                            break;
                        }
                    }
                }
                unset($li);

                // Group lot_items by lot_number to merge same lots
                $groupedLotItems = [];
                foreach ($lotItems as $li) {
                    $key = $li['lot_number'] ?? uniqid();
                    if (!isset($groupedLotItems[$key])) {
                        $groupedLotItems[$key] = $li;
                        $groupedLotItems[$key]['qty'] = 0;
                    }
                    $groupedLotItems[$key]['qty'] += intval($li['qty'] ?? 0);
                    if (isset($li['returned_qty'])) {
                        $groupedLotItems[$key]['returned_qty'] = ($groupedLotItems[$key]['returned_qty'] ?? 0) + intval($li['returned_qty']);
                    }
                }
                $lotItems = array_values($groupedLotItems);

                // Recalculate delivery_quantity
                $newDeliveryQty = 0;
                foreach ($lotItems as $li) {
                    $newDeliveryQty += intval($li['qty'] ?? 0);
                }

                $fields[] = 'lot_items = :lot_items';
                $params['lot_items'] = json_encode($lotItems);
                $fields[] = 'delivery_quantity = :delivery_quantity';
                $params['delivery_quantity'] = $newDeliveryQty;

                // Recalculate purchase_order_items.delivered_quantity for ALL poi_ids
                $poiIds = [];
                if (!empty($delivery['poi_id'])) {
                    $poiIds[] = $delivery['poi_id'];
                }
                foreach ($lotItems as $li) {
                    if (!empty($li['poi_id']) && !in_array($li['poi_id'], $poiIds)) {
                        $poiIds[] = $li['poi_id'];
                    }
                }
                foreach ($poiIds as $poiId) {
                    $conn->prepare("UPDATE purchase_order_items poi SET delivered_quantity = GREATEST(0,
                        (SELECT COALESCE(SUM(d.delivery_quantity), 0) FROM deliveries d
                        WHERE d.poi_id = poi.poi_id AND d.`remove` = 0)
                        - COALESCE((SELECT SUM(b.quantity) FROM backloads b WHERE b.poi_id = poi.poi_id AND b.`remove` = 0), 0)
                    ) WHERE poi.poi_id = :poi_id")->execute(['poi_id' => $poiId]);
                }

                // Recalculate purchase_orders.delivered_quantity
                $poId = $delivery['po_id'];
                if ($poId) {
                    $conn->prepare("UPDATE purchase_orders po SET delivered_quantity = (
                        SELECT COALESCE(SUM(delivered_quantity), 0) FROM purchase_order_items WHERE po_id = :po_id
                    ) WHERE po.po_id = :po_id2")->execute(['po_id' => $poId, 'po_id2' => $poId]);
                    $this->recalculatePODeliveryStatus($poId);
                }
            }
        }

        // Auto-resolve pending delivery reports when editing
        $conn->prepare("UPDATE delivery_reports SET status = 'resolved', resolved_by = 1,
            date_resolved = NOW() WHERE delivery_id = :delivery_id AND status = 'pending'")
            ->execute(['delivery_id' => $deliveryId]);

        if (!empty($fields)) {
            $fields[] = "remarks_type = 'edited'";
        }

        if (empty($fields)) return true;

        $sql = "UPDATE deliveries SET " . implode(', ', $fields) .
               " WHERE delivery_id = :delivery_id AND `remove` = 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $conn->commit();
        return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
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
                ORDER BY po.last_update DESC";
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
        $conn->beginTransaction();
        try {
        $report = $this->getProductionReportById($report_id);
        if (!$report) { $conn->rollBack(); return false; }

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

        $conn->commit();
        return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function updateHistoryLotNumber($history_id, $new_lot_number) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
        $stmt = $conn->prepare("SELECT poi_id, lot_number FROM production_history WHERE history_id = :history_id");
        $stmt->execute(['history_id' => $history_id]);
        $history = $stmt->fetch();
        if (!$history) { $conn->rollBack(); return false; }

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

        $conn->commit();
        return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function editHistoryRecord($history_id, $new_added_quantity, $new_lot_number, $edited_by) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare("SELECT poi_id, added_quantity, previous_quantity, po_id, lot_number FROM production_history WHERE history_id = :history_id");
            $stmt->execute(['history_id' => $history_id]);
            $history = $stmt->fetch();
            if (!$history) { $conn->rollBack(); return false; }

            $old_added = $history['added_quantity'];
            $delta = $new_added_quantity - $old_added;
            $new_new_quantity = $history['previous_quantity'] + $new_added_quantity;
            $lot_changed = $new_lot_number !== $history['lot_number'];

            $conn->prepare("UPDATE production_history 
                SET added_quantity = :added, new_quantity = :new_qty, lot_number = :lot, 
                    old_added_quantity = :old_added, old_lot_number = :old_lot,
                    edited_by = :edited_by, date_edited = NOW()
                WHERE history_id = :history_id")
                ->execute([
                    'added' => $new_added_quantity,
                    'new_qty' => $new_new_quantity,
                    'lot' => $new_lot_number,
                    'old_added' => $old_added,
                    'old_lot' => $history['lot_number'],
                    'edited_by' => $edited_by,
                    'history_id' => $history_id
                ]);

            if ($history['poi_id'] && $delta != 0) {
                $this->recalculateProducedQuantity($history['poi_id'], $conn);
            }

            if ($lot_changed && $history['poi_id'] && $history['lot_number']) {
                $check = $conn->prepare("SELECT lot_id, quantity_produced FROM production_lots 
                    WHERE poi_id = :poi_id AND lot_number = :new_lot AND `is_removed` = 0");
                $check->execute(['poi_id' => $history['poi_id'], 'new_lot' => $new_lot_number]);
                $existingTarget = $check->fetch();

                $oldLots = $conn->prepare("SELECT lot_id FROM production_lots 
                    WHERE poi_id = :poi_id AND lot_number = :old_lot AND `is_removed` = 0");
                $oldLots->execute(['poi_id' => $history['poi_id'], 'old_lot' => $history['lot_number']]);
                $oldLotRows = $oldLots->fetchAll();

                if ($existingTarget) {
                    if ($delta != 0) {
                        $conn->prepare("UPDATE production_lots SET quantity_produced = quantity_produced + :delta 
                            WHERE lot_id = :lot_id")
                            ->execute(['delta' => $delta, 'lot_id' => $existingTarget['lot_id']]);
                    }
                    foreach ($oldLotRows as $ol) {
                        if ($ol['lot_id'] != $existingTarget['lot_id']) {
                            $conn->prepare("UPDATE production_lots SET `is_removed` = 1 WHERE lot_id = :lot_id")
                                ->execute(['lot_id' => $ol['lot_id']]);
                        }
                    }
                } else {
                    $conn->prepare("UPDATE production_lots SET lot_number = :new_lot 
                        WHERE poi_id = :poi_id AND lot_number = :old_lot AND `is_removed` = 0")
                        ->execute(['new_lot' => $new_lot_number, 'poi_id' => $history['poi_id'], 'old_lot' => $history['lot_number']]);

                    if ($delta != 0) {
                        $conn->prepare("UPDATE production_lots SET quantity_produced = quantity_produced + :delta 
                            WHERE poi_id = :poi_id AND lot_number = :lot AND `is_removed` = 0")
                            ->execute(['delta' => $delta, 'poi_id' => $history['poi_id'], 'lot' => $new_lot_number]);
                    }
                }
            } elseif ($history['poi_id'] && $delta != 0) {
                $conn->prepare("UPDATE production_lots SET quantity_produced = quantity_produced + :delta 
                    WHERE poi_id = :poi_id AND lot_number = :lot AND `is_removed` = 0")
                    ->execute(['delta' => $delta, 'poi_id' => $history['poi_id'], 'lot' => $history['lot_number']]);
            }

            $conn->prepare("UPDATE production_reports SET status = 'resolved', new_lot_number = :new_lot, date_resolved = NOW()
                WHERE history_id = :history_id AND status = 'pending'")
                ->execute(['new_lot' => $new_lot_number, 'history_id' => $history_id]);

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function getWeeklyDeliveryStats($customerId = null, $weekOffset = 0) {
        $startOffset = $weekOffset * 12;
        $sql = "SELECT 
                    YEARWEEK(d.delivery_date, 1) as year_week,
                    COUNT(*) as delivery_count,
                    COALESCE(SUM(CASE 
                        WHEN i.uom_conversion IS NOT NULL AND i.uom_conversion > 0 
                        THEN FLOOR(d.delivery_quantity / i.uom_conversion) 
                        ELSE d.delivery_quantity 
                    END), 0) as total_cases
                FROM deliveries d
                LEFT JOIN purchase_order_items poi ON d.poi_id = poi.poi_id
                LEFT JOIN items i ON poi.item_id = i.item_id
                WHERE d.`remove` = 0
                  AND d.delivery_date >= DATE_SUB(CURDATE(), INTERVAL " . ($startOffset + 24) . " WEEK)";
        $params = [];

        if ($customerId) {
            $sql .= " AND d.po_id IN (SELECT po_id FROM purchase_orders WHERE customer_id = :customer_id)";
            $params['customer_id'] = $customerId;
        }

        $sql .= " GROUP BY year_week ORDER BY year_week ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCustomersWithDeliveries() {
        $sql = "SELECT DISTINCT c.customer_id, c.customer_name
                FROM customers c
                INNER JOIN purchase_orders po ON c.customer_id = po.customer_id
                INNER JOIN deliveries d ON po.po_id = d.po_id
                WHERE d.`remove` = 0
                ORDER BY c.customer_name";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getDeliveryDetailsForWeek($yearWeek, $customerId = null) {
        $sql = "SELECT 
                    d.delivery_id,
                    d.delivery_date,
                    d.dr_number,
                    d.delivery_quantity,
                    d.lot_items,
                    po.customer_po_number,
                    c.customer_name,
                    i.item_description,
                    i.item_uom,
                    i.uom_conversion,
                    u.full_name as delivered_by_name
                FROM deliveries d
                LEFT JOIN purchase_orders po ON d.po_id = po.po_id
                LEFT JOIN customers c ON po.customer_id = c.customer_id
                LEFT JOIN purchase_order_items poi ON d.poi_id = poi.poi_id
                LEFT JOIN items i ON poi.item_id = i.item_id
                LEFT JOIN users u ON d.delivered_by = u.user_id
                WHERE d.`remove` = 0
                  AND YEARWEEK(d.delivery_date, 1) = :year_week";
        $params = ['year_week' => $yearWeek];

        if ($customerId) {
            $sql .= " AND d.po_id IN (SELECT po_id FROM purchase_orders WHERE customer_id = :customer_id)";
            $params['customer_id'] = $customerId;
        }

        $sql .= " ORDER BY d.delivery_date DESC, d.delivery_id DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $lotItems = json_decode($row['lot_items'], true);
            if (is_array($lotItems) && count($lotItems) > 0) {
                $totalCases = 0;
                foreach ($lotItems as $li) {
                    $qty = intval($li['qty'] ?? 0);
                    $conv = !empty($li['actual_uom_conversion']) ? intval($li['actual_uom_conversion']) : 0;
                    if ($conv > 0) {
                        $totalCases += floor($qty / $conv);
                    } else {
                        $itemUom = $row['uom_conversion'] ?? 0;
                        $totalCases += $itemUom > 0 ? floor($qty / $itemUom) : 0;
                    }
                }
                $row['cases_delivered'] = $totalCases;
            } else {
                $conv = $row['uom_conversion'] ?? 0;
                $row['cases_delivered'] = ($conv > 0) ? floor($row['delivery_quantity'] / $conv) : $row['delivery_quantity'];
            }
        }
        return $rows;
    }

    public function getPoItemSummary($customerId = null) {
        $sql = "SELECT 
                    poi.poi_id,
                    poi.po_id,
                    poi.quantity as po_qty,
                    poi.produced_quantity,
                    poi.delivered_quantity,
                    poi.unit_price,
                    po.customer_po_number,
                    po.date_created as po_date,
                    c.customer_name,
                    i.item_code,
                    i.item_description,
                    i.item_uom,
                    i.uom_conversion
                FROM purchase_order_items poi
                LEFT JOIN purchase_orders po ON poi.po_id = po.po_id
                LEFT JOIN customers c ON po.customer_id = c.customer_id
                LEFT JOIN items i ON poi.item_id = i.item_id
                WHERE poi.quantity > 0";
        $params = [];

        if ($customerId) {
            $sql .= " AND po.customer_id = :customer_id";
            $params['customer_id'] = $customerId;
        }

        $sql .= " ORDER BY c.customer_name ASC, po.customer_po_number ASC, i.item_description ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        return $rows;
    }

    public function getUniqueItemsForLots() {
        $sql = "SELECT DISTINCT i.item_id, i.item_code, i.item_description
                FROM production_lots pl
                LEFT JOIN purchase_order_items poi ON pl.poi_id = poi.poi_id
            LEFT JOIN items i ON COALESCE(poi.item_id, pl.item_id) = i.item_id
                WHERE pl.is_removed = 0
                  AND i.item_id IS NOT NULL
                ORDER BY i.item_description ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getLotsByItem($itemId) {
        // Fetch PO-linked lots for this item
        $poLotsSql = "SELECT 
                    pl.lot_number,
                    pl.po_id,
                    MIN(pl.poi_id) as poi_id,
                    MIN(pl.lot_date) as lot_date,
                    MIN(pl.created_by) as created_by,
                    SUM(pl.quantity_produced) as quantity_produced,
                    po.customer_po_number,
                    c.customer_name,
                    MIN(u.full_name) as created_by_name,
                    i.uom_conversion,
                    i.item_description,
                    i.item_code
                FROM production_lots pl
                LEFT JOIN purchase_order_items poi ON pl.poi_id = poi.poi_id
                LEFT JOIN items i ON poi.item_id = i.item_id
                LEFT JOIN purchase_orders po ON pl.po_id = po.po_id
                LEFT JOIN customers c ON po.customer_id = c.customer_id
                LEFT JOIN users u ON pl.created_by = u.user_id
                WHERE pl.is_removed = 0
                  AND pl.poi_id IS NOT NULL
                  AND pl.item_id = :item_id
                GROUP BY pl.lot_number, pl.po_id, po.customer_po_number, c.customer_name, i.uom_conversion, i.item_description, i.item_code";
        $stmt = self::getConnection()->prepare($poLotsSql);
        $stmt->execute(['item_id' => $itemId]);
        $poLots = $stmt->fetchAll();

        // Fetch independent lots (poi_id IS NULL) for this item
        $indLotsSql = "SELECT 
                    pl.lot_number,
                    pl.po_id,
                    MIN(pl.poi_id) as poi_id,
                    MIN(pl.lot_date) as lot_date,
                    MIN(pl.created_by) as created_by,
                    SUM(pl.quantity_produced) as quantity_produced,
                    NULL as customer_po_number,
                    NULL as customer_name,
                    MIN(u.full_name) as created_by_name,
                    i.uom_conversion,
                    i.item_description,
                    i.item_code
                FROM production_lots pl
                LEFT JOIN items i ON pl.item_id = i.item_id
                LEFT JOIN users u ON pl.created_by = u.user_id
                WHERE pl.is_removed = 0
                  AND pl.poi_id IS NULL
                  AND pl.item_id = :item_id
                GROUP BY pl.lot_number, i.uom_conversion, i.item_description, i.item_code";
        $stmt2 = self::getConnection()->prepare($indLotsSql);
        $stmt2->execute(['item_id' => $itemId]);
        $indLots = $stmt2->fetchAll();

        $lots = array_merge($poLots, $indLots);

        $allDelivered = $this->getLotDeliveries();
        $allLotIds = $this->getLotIdsByItem($itemId);

        $backloadStmt = self::getConnection()->prepare(
            "SELECT lot_id, SUM(quantity) as total_backloaded FROM backloads WHERE `remove` = 0 GROUP BY lot_id"
        );
        $backloadStmt->execute();
        $allBackloaded = [];
        while ($bl = $backloadStmt->fetch()) {
            $allBackloaded[intval($bl['lot_id'])] = intval($bl['total_backloaded']);
        }

        $deliveredByKey = [];
        foreach ($allLotIds as $lid) {
            $key = $lid['lot_number'] . '_' . $lid['po_id'] . '_' . $lid['item_id'];
            $deliveredByKey[$key] = ($deliveredByKey[$key] ?? 0) + ($allDelivered[$lid['lot_id']] ?? 0);
        }
        foreach ($lots as &$lot) {
            $key = $lot['lot_number'] . '_' . $lot['po_id'] . '_' . $itemId;
            $lot['quantity_delivered'] = $deliveredByKey[$key] ?? 0;
            $lotBackloaded = 0;
            foreach ($allLotIds as $lid) {
                if ($lid['lot_number'] === $lot['lot_number'] && $lid['po_id'] == $lot['po_id'] && $lid['item_id'] == $itemId) {
                    $lotBackloaded += $allBackloaded[$lid['lot_id']] ?? 0;
                }
            }
            $lot['quantity_backloaded'] = $lotBackloaded;
        }
        return $lots;
    }

    public function getAllLotsStockOnHand() {
        // Fetch PO-linked lots (with poi_id set)
        $poLotsSql = "SELECT 
                    pl.lot_number,
                    pl.po_id,
                    poi.item_id,
                    MIN(pl.lot_id) as lot_id,
                    MIN(pl.poi_id) as poi_id,
                    MIN(pl.lot_date) as lot_date,
                    MIN(pl.created_by) as created_by,
                    SUM(pl.quantity_produced) as quantity_produced,
                    po.customer_po_number,
                    c.customer_name,
                    MIN(u.full_name) as created_by_name,
                    i.uom_conversion,
                    i.item_code,
                    i.item_description
                FROM production_lots pl
                LEFT JOIN purchase_order_items poi ON pl.poi_id = poi.poi_id
                LEFT JOIN items i ON poi.item_id = i.item_id
                LEFT JOIN purchase_orders po ON pl.po_id = po.po_id
                LEFT JOIN customers c ON po.customer_id = c.customer_id
                LEFT JOIN users u ON pl.created_by = u.user_id
                WHERE pl.is_removed = 0 AND pl.poi_id IS NOT NULL
                GROUP BY pl.lot_number, pl.po_id, poi.item_id, po.customer_po_number, c.customer_name, i.uom_conversion, i.item_code, i.item_description
                ORDER BY MIN(pl.lot_id) DESC, i.item_description ASC";
        $stmt = self::getConnection()->prepare($poLotsSql);
        $stmt->execute();
        $poLots = $stmt->fetchAll();

        // Fetch independent lots (poi_id IS NULL, but item_id is set)
        $independentLotsSql = "SELECT 
                    pl.lot_number,
                    pl.po_id,
                    pl.item_id as item_id,
                    MIN(pl.lot_id) as lot_id,
                    MIN(pl.poi_id) as poi_id,
                    MIN(pl.lot_date) as lot_date,
                    MIN(pl.created_by) as created_by,
                    SUM(pl.quantity_produced) as quantity_produced,
                    NULL as customer_po_number,
                    NULL as customer_name,
                    MIN(u.full_name) as created_by_name,
                    i.uom_conversion,
                    i.item_code,
                    i.item_description
                FROM production_lots pl
                LEFT JOIN items i ON pl.item_id = i.item_id
                LEFT JOIN users u ON pl.created_by = u.user_id
                WHERE pl.is_removed = 0 AND pl.poi_id IS NULL
                GROUP BY pl.lot_number, pl.item_id, i.uom_conversion, i.item_code, i.item_description
                ORDER BY MIN(pl.lot_id) DESC, i.item_description ASC";
        $stmt2 = self::getConnection()->prepare($independentLotsSql);
        $stmt2->execute();
        $independentLots = $stmt2->fetchAll();

        $allLots = array_merge($poLots, $independentLots);

        $allDelivered = $this->getLotDeliveries();

        $backloadStmt = self::getConnection()->prepare(
            "SELECT lot_id, SUM(quantity) as total_backloaded FROM backloads WHERE `remove` = 0 GROUP BY lot_id"
        );
        $backloadStmt->execute();
        $allBackloaded = [];
        while ($bl = $backloadStmt->fetch()) {
            $allBackloaded[intval($bl['lot_id'])] = intval($bl['total_backloaded']);
        }

        $lotIdMap = self::getConnection()->prepare(
            "SELECT pl.lot_id, pl.lot_number, pl.po_id, pl.item_id
                FROM production_lots pl
                WHERE pl.is_removed = 0"
        );
        $lotIdMap->execute();
        $allLotIds = $lotIdMap->fetchAll();

        $deliveredByKey = [];
        foreach ($allLotIds as $lid) {
            $key = $lid['lot_number'] . '_' . $lid['po_id'] . '_' . $lid['item_id'];
            $deliveredByKey[$key] = ($deliveredByKey[$key] ?? 0) + ($allDelivered[intval($lid['lot_id'])] ?? 0);
        }

        foreach ($allLots as &$lot) {
            $key = $lot['lot_number'] . '_' . $lot['po_id'] . '_' . $lot['item_id'];
            $lot['quantity_delivered'] = $deliveredByKey[$key] ?? 0;
            $lotBackloaded = 0;
            foreach ($allLotIds as $lid) {
                if ($lid['lot_number'] === $lot['lot_number'] && $lid['po_id'] == $lot['po_id'] && $lid['item_id'] == $lot['item_id']) {
                    $lotBackloaded += $allBackloaded[intval($lid['lot_id'])] ?? 0;
                }
            }
            $lot['quantity_backloaded'] = $lotBackloaded;
        }
        return $allLots;
    }

    private function getLotIdsByItem($itemId) {
        $sql = "SELECT pl.lot_id, pl.lot_number, pl.po_id, pl.item_id
                FROM production_lots pl
                WHERE pl.is_removed = 0 AND pl.item_id = :item_id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['item_id' => $itemId]);
        return $stmt->fetchAll();
    }

    private function getLotDeliveries() {
        $result = [];

        $sql = "SELECT delivery_id, lot_items FROM deliveries WHERE remove = 0 AND lot_items IS NOT NULL";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $items = json_decode($row['lot_items'], true);
            if (!is_array($items)) continue;
            foreach ($items as $li) {
                $lotId = intval($li['lot_id']);
                $qty = intval($li['qty'] ?? 0);
                if ($lotId > 0) {
                    $result[$lotId] = ($result[$lotId] ?? 0) + $qty;
                }
            }
        }

        $sql2 = "SELECT lot_id, delivery_quantity FROM deliveries WHERE remove = 0 AND lot_items IS NULL AND lot_id IS NOT NULL AND delivery_quantity > 0";
        $stmt2 = self::getConnection()->prepare($sql2);
        $stmt2->execute();
        $rows2 = $stmt2->fetchAll();
        foreach ($rows2 as $row) {
            $lotId = intval($row['lot_id']);
            $qty = intval($row['delivery_quantity']);
            if ($lotId > 0) {
                $result[$lotId] = ($result[$lotId] ?? 0) + $qty;
            }
        }

        return $result;
    }

    public function getDeliveryReportData($filters = []) {
        $sql = "SELECT d.delivery_id, d.dr_number, d.si_number, d.plate_number, d.vehicle_type, d.logistic_provider,
                       d.delivery_date, d.delivery_quantity,
                       d.lot_items, d.remarks, d.report_remarks, d.date_created,
                       po.customer_po_number, po.customer_po_date, po.total_quantity, po.production_type,
                       c.customer_name,
                       poi.quantity as item_quantity, poi.poi_id,
                       i.item_code, i.item_description, COALESCE(poi.item_uom, i.item_uom) as item_uom,
                       pl.lot_number,
                       u.full_name as delivered_by_name
                FROM deliveries d
                LEFT JOIN purchase_orders po ON d.po_id = po.po_id
                LEFT JOIN customers c ON po.customer_id = c.customer_id
                LEFT JOIN purchase_order_items poi ON d.poi_id = poi.poi_id
                LEFT JOIN items i ON poi.item_id = i.item_id
                LEFT JOIN production_lots pl ON d.lot_id = pl.lot_id
                LEFT JOIN users u ON d.delivered_by = u.user_id
                WHERE d.`remove` = 0";
        $params = [];

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (d.dr_number LIKE :s1 OR d.si_number LIKE :s2
                       OR po.customer_po_number LIKE :s3 OR c.customer_name LIKE :s4
                       OR i.item_description LIKE :s5 OR pl.lot_number LIKE :s6
                       OR u.full_name LIKE :s7 OR d.remarks LIKE :s8)";
            $params['s1'] = $like;
            $params['s2'] = $like;
            $params['s3'] = $like;
            $params['s4'] = $like;
            $params['s5'] = $like;
            $params['s6'] = $like;
            $params['s7'] = $like;
            $params['s8'] = $like;
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND d.delivery_date >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND d.delivery_date <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        if (!empty($filters['customer_id'])) {
            $sql .= " AND po.customer_id = :customer_id";
            $params['customer_id'] = $filters['customer_id'];
        }

        $sql .= " ORDER BY d.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getDeliveryReportCustomers() {
        $sql = "SELECT DISTINCT c.customer_id, c.customer_name
                FROM deliveries d
                JOIN purchase_orders po ON d.po_id = po.po_id
                JOIN customers c ON po.customer_id = c.customer_id
                WHERE d.`remove` = 0
                ORDER BY c.customer_name ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActivePOsByCustomer($customer_id) {
        $sql = "SELECT po.po_id, po.customer_po_number, po.date_created, po.production_type
                FROM purchase_orders po
                WHERE po.customer_id = :customer_id AND po.`remove` = 0
                ORDER BY po.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['customer_id' => $customer_id]);
        return $stmt->fetchAll();
    }

    public function getAllActivePOs() {
        $sql = "SELECT po.po_id, po.po_number, po.customer_po_number, c.customer_name
                FROM purchase_orders po
                JOIN customers c ON c.customer_id = po.customer_id
                WHERE po.`remove` = 0
                ORDER BY po.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ─── Backloads (delegated to BackloadModel) ─────────────────────────────

    public function createBackload($data) {
        return $this->backloadModel->createBackload($data);
    }

    public function getBackloads($filters = []) {
        return $this->backloadModel->getBackloads($filters);
    }

    public function getBackloadsByDeliveryId($delivery_id) {
        return $this->backloadModel->getBackloadsByDeliveryId($delivery_id);
    }

    public function getDeliveryLotsForBackload($delivery_id) {
        return $this->backloadModel->getDeliveryLotsForBackload($delivery_id);
    }

    // ==================== INDEPENDENT FG PRODUCTION ====================

    public function createIndependentLot($data) {
        $sql = "INSERT INTO production_lots (po_id, poi_id, item_id, lot_number, quantity_produced, pcs_per_case, lot_date, created_by)
                VALUES (NULL, NULL, :item_id, :lot_number, :quantity_produced, :pcs_per_case, :lot_date, :created_by)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'item_id' => $data['item_id'] ?? null,
            'lot_number' => $data['lot_number'],
            'quantity_produced' => $data['quantity_produced'] ?? 0,
            'pcs_per_case' => $data['pcs_per_case'] ?? null,
            'lot_date' => $data['lot_date'] ?? date('Y-m-d'),
            'created_by' => $data['created_by'] ?? null
        ]);
        return self::getConnection()->lastInsertId();
    }

    public function getLotByItemAndLotNumber($item_id, $lot_number) {
        $sql = "SELECT lot_id, quantity_produced, pcs_per_case FROM production_lots
                WHERE item_id = :item_id AND lot_number = :lot_number AND `is_removed` = 0
                LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['item_id' => $item_id, 'lot_number' => $lot_number]);
        return $stmt->fetch() ?: null;
    }

    public function undoProductionHistory($historyIds, $userId = null) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
            $ids = array_map('intval', $historyIds);
            if (empty($ids)) {
                $conn->rollBack();
                return false;
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

                $userCondition = $userId !== null ? ' AND user_id = ?' : '';
                $queryParams = $ids;
                if ($userId !== null) {
                $queryParams[] = $userId;
                }
                $stmt = $conn->prepare("SELECT history_id, item_id, lot_number, added_quantity, date_created
                    FROM production_history
                    WHERE history_id IN ($placeholders) AND is_removed = 0
                    AND date_created >= NOW() - INTERVAL 5 MINUTE$userCondition");
                $stmt->execute($queryParams);
            $rows = $stmt->fetchAll();
            if (empty($rows)) {
                $conn->rollBack();
                return false;
            }

            $validIds = array_column($rows, 'history_id');
            $validPlaceholders = implode(',', array_fill(0, count($validIds), '?'));
            $conn->prepare("UPDATE production_history SET is_removed = 1 WHERE history_id IN ($validPlaceholders)")
                ->execute($validIds);

            $lotDeductions = [];
            foreach ($rows as $row) {
                $key = $row['item_id'] . '_' . $row['lot_number'];
                if (!isset($lotDeductions[$key])) {
                    $lotDeductions[$key] = [
                        'item_id' => $row['item_id'],
                        'lot_number' => $row['lot_number'],
                        'total_deducted' => 0,
                    ];
                }
                $lotDeductions[$key]['total_deducted'] += intval($row['added_quantity']);
            }

            foreach ($lotDeductions as $deduction) {
                $conn->prepare("UPDATE production_lots SET quantity_produced = GREATEST(0, quantity_produced - :deducted)
                        WHERE item_id = :item_id AND lot_number = :lot_number AND is_removed = 0")
                    ->execute([
                        'deducted' => $deduction['total_deducted'],
                        'item_id' => $deduction['item_id'],
                        'lot_number' => $deduction['lot_number'],
                    ]);
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function upsertItemLot($data) {
        $conn = self::getConnection();
        $item_id = $data['item_id'];
        $lot_number = $data['lot_number'];
        $added_quantity = intval($data['quantity_produced'] ?? 0);
        $pcs_per_case = $data['pcs_per_case'] ?? null;

        $sql = "SELECT lot_id, quantity_produced, pcs_per_case FROM production_lots
                WHERE item_id = :item_id AND lot_number = :lot_number AND `is_removed` = 0
                FOR UPDATE";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['item_id' => $item_id, 'lot_number' => $lot_number]);
        $existing = $stmt->fetch();

        if ($existing) {
            $existingPcs = $existing['pcs_per_case'] !== null ? intval($existing['pcs_per_case']) : null;
            $newPcs = $pcs_per_case !== null ? intval($pcs_per_case) : null;
            if ($existingPcs === $newPcs) {
                $newQty = intval($existing['quantity_produced']) + $added_quantity;
                $upd = $conn->prepare("UPDATE production_lots SET quantity_produced = :qty WHERE lot_id = :lot_id");
                $upd->execute(['qty' => $newQty, 'lot_id' => $existing['lot_id']]);
                return $existing['lot_id'];
            } else {
                return $this->createIndependentLot([
                    'item_id' => $item_id,
                    'lot_number' => $lot_number,
                    'quantity_produced' => $added_quantity,
                    'pcs_per_case' => $pcs_per_case,
                    'created_by' => $data['created_by'] ?? null
                ]);
            }
        } else {
            return $this->createIndependentLot([
                'item_id' => $item_id,
                'lot_number' => $lot_number,
                'quantity_produced' => $added_quantity,
                'pcs_per_case' => $pcs_per_case,
                'created_by' => $data['created_by'] ?? null
            ]);
        }
    }

    public function getAllAvailableItemsForDelivery($po_id = null) {
        $conn = self::getConnection();
        $selectedPoId = $po_id !== null && $po_id !== '' ? intval($po_id) : null;

        $selectedPoMatch = $selectedPoId === null
            ? '1'
            : '(EXISTS (
                    SELECT 1 FROM purchase_order_items selected_poi
                    WHERE selected_poi.po_id = ?
                    AND selected_poi.item_id = COALESCE(i2.item_id, i.item_id)
                ))';
        $sql = "SELECT COALESCE(i2.item_id, i.item_id) as item_id,
                COALESCE(i2.item_code, i.item_code) as item_code,
                COALESCE(i2.item_description, i.item_description) as item_description,
                COALESCE(i2.item_uom, i.item_uom) as item_uom,
                COALESCE(i2.uom_conversion, i.uom_conversion) as uom_conversion,
                l.lot_id, l.lot_number, l.quantity_produced, l.pcs_per_case, l.lot_date, l.po_id, l.date_created,
                po.po_number, po.customer_po_number,
                CASE WHEN $selectedPoMatch THEN 1 ELSE 0 END AS in_selected_po
                FROM production_lots l
                LEFT JOIN items i ON l.item_id = i.item_id
                LEFT JOIN purchase_order_items poi ON l.poi_id = poi.poi_id
                LEFT JOIN items i2 ON poi.item_id = i2.item_id
                LEFT JOIN purchase_orders po ON l.po_id = po.po_id
                WHERE l.`is_removed` = 0
                AND (i.`remove` = 0 OR i2.`remove` = 0)
                AND COALESCE(i2.item_id, i.item_id) IS NOT NULL";

        $params = $selectedPoId === null ? [] : [$selectedPoId];

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $lots = $stmt->fetchAll();
        if (empty($lots)) return [];

        $deliveredStmt = $conn->prepare("SELECT delivery_id, lot_items FROM deliveries 
                WHERE lot_items IS NOT NULL AND `remove` = 0
                ORDER BY delivery_date ASC, delivery_id ASC");
        $deliveredStmt->execute();
        $jsonDelivered = [];
        while ($r = $deliveredStmt->fetch()) {
            $items = json_decode($r['lot_items'], true);
            if (!is_array($items)) continue;
            foreach ($items as $li) {
                if (isset($li['lot_id'])) {
                    $lid = intval($li['lot_id']);
                    $jsonDelivered[$lid] = ($jsonDelivered[$lid] ?? 0) + intval($li['qty'] ?? 0);
                }
            }
        }

        $jsonBackloaded = [];
        $blStmt = $conn->prepare("SELECT lot_id, quantity FROM backloads WHERE `remove` = 0");
        $blStmt->execute();
        while ($bl = $blStmt->fetch()) {
            $lid = intval($bl['lot_id']);
            $jsonBackloaded[$lid] = ($jsonBackloaded[$lid] ?? 0) + intval($bl['quantity']);
        }

        $grouped = [];
        foreach ($lots as $lot) {
            $lid = $lot['lot_id'];
            $available = max(0, $lot['quantity_produced'] - ($jsonDelivered[$lid] ?? 0) + ($jsonBackloaded[$lid] ?? 0));
            if ($available <= 0) continue;

            $iid = $lot['item_id'];
            $ln = $lot['lot_number'];
            if (!isset($grouped[$iid])) {
                $grouped[$iid] = [
                    'item_id' => $iid,
                    'item_code' => $lot['item_code'],
                    'item_description' => $lot['item_description'],
                    'item_uom' => $lot['item_uom'] ?? 'PCS',
                    'uom_conversion' => $lot['uom_conversion'] ?? null,
                    'lots' => [],
                ];
            }

            $key = $iid . '_' . $ln;
            if (!isset($grouped[$iid]['lots'][$key])) {
                $grouped[$iid]['lots'][$key] = [
                    'lot_id' => $lid,
                    'lot_ids' => [$lid],
                    'lot_number' => $ln,
                    'available_quantity' => $available,
                    'po_id' => $lot['po_id'] ? intval($lot['po_id']) : null,
                    'po_number' => $lot['customer_po_number'] ?: $lot['po_number'] ?? null,
                    'pcs_per_case' => $lot['pcs_per_case'],
                    'lot_date' => $lot['lot_date'],
                    'date_created' => $lot['date_created'],
                    'uom_conversion' => $lot['uom_conversion'] ?? null,
                    'in_selected_po' => (int)($lot['in_selected_po'] ?? 1),
                    'sub_lots' => [['lot_id' => $lid, 'available' => $available]],
                ];
            } else {
                $gl = &$grouped[$iid]['lots'][$key];
                $gl['lot_ids'][] = $lid;
                $gl['available_quantity'] += $available;
                $gl['in_selected_po'] = (int)($gl['in_selected_po'] ?? 1) && (int)($lot['in_selected_po'] ?? 1);
                $gl['sub_lots'][] = ['lot_id' => $lid, 'available' => $available];
            }
        }

        $result = array_values($grouped);
        foreach ($result as &$item) {
            $item['lots'] = array_values($item['lots']);
            usort($item['lots'], function($a, $b) {
                return strtotime($a['date_created']) - strtotime($b['date_created']);
            });
        }
        unset($item);

        if ($selectedPoId !== null) {
            $poiStmt = $conn->prepare("SELECT poi.item_id, poi.quantity, poi.delivered_quantity, i.uom_conversion
                    FROM purchase_order_items poi
                    JOIN items i ON poi.item_id = i.item_id
                    WHERE poi.po_id = ?");
            $poiStmt->execute([$selectedPoId]);
            $poiFulfillment = [];
            while ($row = $poiStmt->fetch()) {
                $poiFulfillment[intval($row['item_id'])] = [
                    'quantity' => intval($row['quantity']),
                    'delivered_quantity' => intval($row['delivered_quantity']),
                    'uom_conversion' => $row['uom_conversion'] ? intval($row['uom_conversion']) : null,
                ];
            }
            foreach ($result as &$item) {
                $iid = intval($item['item_id']);
                if (isset($poiFulfillment[$iid])) {
                    $item['po_quantity'] = $poiFulfillment[$iid]['quantity'];
                    $item['po_delivered'] = $poiFulfillment[$iid]['delivered_quantity'];
                    $item['po_uom_conversion'] = $poiFulfillment[$iid]['uom_conversion'];
                }
            }
            unset($item);
        }

        usort($result, function($a, $b) { return strcmp($a['item_code'], $b['item_code']); });
        return $result;
    }

    public function getPOsContainingItem($item_id) {
        $sql = "SELECT po.po_id, po.customer_po_number, po.customer_po_date, po.production_type,
                       c.customer_name, poi.poi_id, poi.quantity, poi.produced_quantity, poi.delivered_quantity
                FROM purchase_order_items poi
                JOIN purchase_orders po ON poi.po_id = po.po_id
                JOIN customers c ON po.customer_id = c.customer_id
                WHERE poi.item_id = :item_id AND po.`remove` = 0 AND poi.`remove` = 0
                AND po.status != 'rejected'
                ORDER BY po.customer_po_number ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['item_id' => $item_id]);
        return $stmt->fetchAll();
    }

    public function recalculateProducedQuantityFromDelivery($poiId, $conn = null) {
        $ownsConn = false;
        if (!$conn) {
            $conn = self::getConnection();
            $ownsConn = true;
        }

        $prodStmt = $conn->prepare("SELECT COALESCE(SUM(quantity_produced), 0) FROM production_lots WHERE poi_id = :poi_id AND is_removed = 0");
        $prodStmt->execute(['poi_id' => $poiId]);
        $produced = intval($prodStmt->fetchColumn());

        $conn->prepare("UPDATE purchase_order_items SET produced_quantity = :produced WHERE poi_id = :poi_id")
            ->execute(['produced' => $produced, 'poi_id' => $poiId]);

        $poStmt = $conn->prepare("SELECT po_id FROM purchase_order_items WHERE poi_id = :poi_id");
        $poStmt->execute(['poi_id' => $poiId]);
        $poId = $poStmt->fetchColumn();

        if ($poId) {
            $conn->prepare("UPDATE purchase_orders SET produced_quantity = (
                SELECT COALESCE(SUM(produced_quantity), 0) FROM purchase_order_items WHERE po_id = :po_id
            ) WHERE po_id = :po_id2")
                ->execute(['po_id' => $poId, 'po_id2' => $poId]);
        }

        return $produced;
    }

    // ==================== FG INVENTORY ====================

    public function getFGInventory($filters = []) {
        $conn = self::getConnection();

        $sql = "SELECT 
                    i.item_id, i.item_code, i.item_description, i.item_uom, i.uom_conversion,
                    COALESCE(SUM(pl.quantity_produced), 0) as total_produced,
                    COUNT(pl.lot_id) as total_lots,
                    MAX(pl.last_update) as last_updated
                FROM items i
                LEFT JOIN production_lots pl ON pl.item_id = i.item_id AND pl.is_removed = 0
                WHERE i.`remove` = 0 AND i.status = 1
                GROUP BY i.item_id, i.item_code, i.item_description, i.item_uom, i.uom_conversion
                HAVING total_produced > 0";

        $params = [];

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (i.item_code LIKE :search1 OR i.item_description LIKE :search2)";
            $params['search1'] = $like;
            $params['search2'] = $like;
        }

        $sql .= " ORDER BY MAX(pl.last_update) DESC, i.item_code ASC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        if (empty($rows)) return [];

        $itemIds = array_column($rows, 'item_id');
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));

        $lotStmt = $conn->prepare("SELECT lot_id, item_id, quantity_produced FROM production_lots WHERE item_id IN ($placeholders) AND is_removed = 0");
        $lotStmt->execute($itemIds);
        $allLotIds = [];
        while ($r = $lotStmt->fetch()) {
            $allLotIds[intval($r['lot_id'])] = ['item_id' => intval($r['item_id']), 'qty' => intval($r['quantity_produced'])];
        }

        $allDelivered = $this->getLotDeliveries();

        $backloadStmt = $conn->prepare("SELECT lot_id, SUM(quantity) as total_backloaded FROM backloads WHERE `remove` = 0 GROUP BY lot_id");
        $backloadStmt->execute();
        $allBackloaded = [];
        while ($bl = $backloadStmt->fetch()) {
            $allBackloaded[intval($bl['lot_id'])] = intval($bl['total_backloaded']);
        }

        $itemDelivered = [];
        $itemBackloaded = [];
        foreach ($allLotIds as $lid => $lotInfo) {
            $iid = $lotInfo['item_id'];
            $itemDelivered[$iid] = ($itemDelivered[$iid] ?? 0) + ($allDelivered[$lid] ?? 0);
            $itemBackloaded[$iid] = ($itemBackloaded[$iid] ?? 0) + ($allBackloaded[$lid] ?? 0);
        }

        foreach ($rows as &$row) {
            $iid = $row['item_id'];
            $delivered = $itemDelivered[$iid] ?? 0;
            $backloaded = $itemBackloaded[$iid] ?? 0;
            $row['total_delivered'] = $delivered;
            $row['available_stock'] = max(0, intval($row['total_produced']) - $delivered + $backloaded);
        }
        unset($row);

        return $rows;
    }

    public function getLotsByItemForInventory($item_id) {
        $conn = self::getConnection();

        $sql = "SELECT pl.lot_id, pl.lot_number, pl.pcs_per_case, pl.quantity_produced,
                       pl.lot_date, pl.created_by, pl.item_id,
                       u.full_name as created_by_name
                FROM production_lots pl
                LEFT JOIN users u ON pl.created_by = u.user_id
                WHERE pl.item_id = :item_id AND pl.is_removed = 0
                ORDER BY pl.last_update DESC, pl.date_created DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['item_id' => $item_id]);
        $lots = $stmt->fetchAll();
        if (empty($lots)) return [];

        $allDelivered = $this->getLotDeliveries();

        $blStmt = $conn->prepare("SELECT lot_id, SUM(quantity) as total_backloaded FROM backloads WHERE `remove` = 0 GROUP BY lot_id");
        $blStmt->execute();
        $allBackloaded = [];
        while ($bl = $blStmt->fetch()) {
            $allBackloaded[intval($bl['lot_id'])] = intval($bl['total_backloaded']);
        }

        foreach ($lots as &$lot) {
            $lid = intval($lot['lot_id']);
            $delivered = $allDelivered[$lid] ?? 0;
            $backloaded = $allBackloaded[$lid] ?? 0;
            $lot['quantity_delivered'] = $delivered;
            $lot['available_balance'] = max(0, intval($lot['quantity_produced']) - $delivered + $backloaded);
        }
        unset($lot);

        return $lots;
    }

public function searchItems($query) {
        return $this->catalogModel->searchItems($query);
    }

    // ─── MRP Methods ──────────────────────────────────────────────────────────

    public function getCustomersWithOpenPOs() {
        $sql = "SELECT DISTINCT c.customer_id, c.customer_code, c.customer_name
                FROM customers c
                JOIN purchase_orders po ON c.customer_id = po.customer_id
                WHERE c.`remove` = 0 AND c.status = 1
                  AND po.delivered_quantity < po.total_quantity
                  AND po.`remove` = 0
                ORDER BY c.customer_code ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getOpenPOsByCustomer($customer_id) {
        $sql = "SELECT po.*, c.customer_name
                FROM purchase_orders po
                JOIN customers c ON po.customer_id = c.customer_id
                WHERE po.customer_id = :customer_id
                  AND po.delivered_quantity < po.total_quantity
                  AND po.`remove` = 0
                ORDER BY po.customer_po_date DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['customer_id' => $customer_id]);
        return $stmt->fetchAll();
    }

    public function getPOItemsWithBOM($po_id) {
        $sql = "SELECT poi.poi_id, poi.quantity, poi.item_uom, poi.item_id,
                       i.item_code, i.item_description,
                       b.id AS bom_id, b.bom_code, b.batch_qty, b.batch_uom
                FROM purchase_order_items poi
                JOIN items i ON poi.item_id = i.item_id AND i.`remove` = 0
                LEFT JOIN fg_boms b ON b.fg_item_id = poi.item_id
                WHERE poi.po_id = :po_id
                ORDER BY i.item_code ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['po_id' => $po_id]);
        return $stmt->fetchAll();
    }

    public function getBOMComponentsWithStock($bom_ids) {
        if (empty($bom_ids)) return [];
        $placeholders = implode(',', array_fill(0, count($bom_ids), '?'));
        $sql = "SELECT bi.bom_id, bi.item_id AS component_item_id, bi.dosage_rate, bi.wastage_allowance_pct,
                       i.item_code, i.item_description, i.item_uom,
                       COALESCE(v.total_soh, 0) AS soh,
                       COALESCE(v.total_allocated, 0) AS allocated,
                       COALESCE(v.available_stock, 0) AS available_stock
                FROM fg_bom_items bi
                JOIN items i ON bi.item_id = i.item_id AND i.`remove` = 0
                LEFT JOIN view_inventory_status v ON v.item_id = bi.item_id
                WHERE bi.bom_id IN ({$placeholders})
                ORDER BY bi.bom_id, bi.id ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($bom_ids);
        return $stmt->fetchAll();
    }

    public function getPendingAllocationsAcrossPOs($exclude_poi_ids, $ingredient_item_ids) {
        if (empty($ingredient_item_ids)) return [];
        $placeholders = implode(',', array_fill(0, count($ingredient_item_ids), '?'));
        $sql = "SELECT bi.item_id AS component_item_id,
                       SUM((poi.quantity / b.batch_qty) * bi.dosage_rate * (1 + bi.wastage_allowance_pct / 100)) AS total_pending
                FROM purchase_order_items poi
                JOIN fg_boms b ON b.fg_item_id = poi.item_id
                JOIN fg_bom_items bi ON bi.bom_id = b.id
                JOIN purchase_orders po ON poi.po_id = po.po_id
                WHERE po.delivered_quantity < po.total_quantity
                  AND po.`remove` = 0
                  AND bi.item_id IN ({$placeholders})";
        $params = $ingredient_item_ids;
        if (!empty($exclude_poi_ids)) {
            $exPlaceholders = implode(',', array_fill(0, count($exclude_poi_ids), '?'));
            $sql .= " AND poi.poi_id NOT IN ({$exPlaceholders})";
            $params = array_merge($params, $exclude_poi_ids);
        }
        $sql .= " GROUP BY bi.item_id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['component_item_id']] = $row['total_pending'];
        }
        return $result;
    }

    // ─── BOM Lookup ──────────────────────────────────────────────────────────

    public function hasBOM($item_id) {
        $sql = "SELECT id, bom_code, batch_qty, batch_uom
                FROM fg_boms
                WHERE fg_item_id = :item_id
                LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['item_id' => $item_id]);
        return $stmt->fetch() ?: false;
    }

    public function getBomByCode($bomCode) {
        $sql = "SELECT b.id, b.bom_code, b.batch_qty, b.batch_uom,
                       b.fg_item_id,
                       i.item_code AS fg_code, i.item_description AS fg_name
                FROM fg_boms b
                JOIN items i ON b.fg_item_id = i.item_id AND i.`remove` = 0
                WHERE b.bom_code = :bom_code
                LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['bom_code' => $bomCode]);
        return $stmt->fetch() ?: false;
    }

    // ─── Procurement PO ─────────────────────────────────────────────────────

    public function getSupplierOrdersFiltered($filters = []) {
        $where = ["so.`remove` = 0"];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "so.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['supplier_name'])) {
            $where[] = "so.supplier_name LIKE :supplier_name";
            $params['supplier_name'] = '%' . $filters['supplier_name'] . '%';
        }
        if (!empty($filters['search'])) {
            $where[] = "(so.supplier_name LIKE :search OR i.item_code LIKE :search OR i.item_description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT so.*, i.item_code, i.item_description, i.item_uom,
                       u.full_name AS created_by_name,
                       po.customer_po_number
                FROM supplier_orders so
                JOIN items i ON so.item_id = i.item_id
                JOIN users u ON so.created_by = u.user_id
                LEFT JOIN purchase_orders po ON so.po_id = po.po_id
                WHERE {$whereSql}
                ORDER BY so.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getSupplierOrderById($id) {
        $sql = "SELECT so.*, i.item_code, i.item_description, i.item_uom
                FROM supplier_orders so
                JOIN items i ON so.item_id = i.item_id
                WHERE so.supplier_order_id = :id AND so.`remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: false;
    }

    public function createSupplierOrder($data) {
        $status = $data['status'] ?? 'pending';
        $poId = $data['po_id'] ?? null;
        $sql = "INSERT INTO supplier_orders (supplier_name, item_id, quantity, unit_cost, order_date, expected_date, remarks, created_by, status, po_id)
                VALUES (:supplier_name, :item_id, :quantity, :unit_cost, :order_date, :expected_date, :remarks, :created_by, :status, :po_id)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'supplier_name' => $data['supplier_name'],
            'item_id' => $data['item_id'],
            'quantity' => $data['quantity'],
            'unit_cost' => $data['unit_cost'],
            'order_date' => $data['order_date'],
            'expected_date' => $data['expected_date'],
            'remarks' => $data['remarks'],
            'created_by' => $data['created_by'],
            'status' => $status,
            'po_id' => $poId
        ]);
        return self::getConnection()->lastInsertId();
    }

    public function receiveSupplierOrder($id, $receivedQty) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare("UPDATE supplier_orders SET status = 'completed', received_qty = :qty, received_date = CURDATE() WHERE supplier_order_id = :id");
            $stmt->execute(['qty' => $receivedQty, 'id' => $id]);

            $order = $this->getSupplierOrderById($id);
            if ($order) {
                $conn->prepare("INSERT INTO inventory_balances (item_id, site_code, qty_on_hand) VALUES (:item_id, 'MAIN', :qty) ON DUPLICATE KEY UPDATE qty_on_hand = qty_on_hand + :qty2")
                    ->execute(['item_id' => $order['item_id'], 'qty' => $receivedQty, 'qty2' => $receivedQty]);
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function cancelSupplierOrder($id) {
        $sql = "UPDATE supplier_orders SET status = 'cancelled' WHERE supplier_order_id = :id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function deleteSupplierOrder($id) {
        $sql = "UPDATE supplier_orders SET `remove` = 1 WHERE supplier_order_id = :id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function getPendingSupplierOrdersForItems($item_ids, $exclude_po_id = null) {
        if (empty($item_ids)) return [];
        $namedParams = [];
        $itemPlaceholders = [];
        foreach ($item_ids as $idx => $id) {
            $key = ':item_' . $idx;
            $itemPlaceholders[] = $key;
            $namedParams[$key] = $id;
        }
        $itemList = implode(',', $itemPlaceholders);
        $sql = "SELECT item_id, SUM(quantity - received_qty) AS pending_supplier_qty
                FROM supplier_orders
                WHERE status = 'pending' AND `remove` = 0
                  AND item_id IN ({$itemList})";
        if ($exclude_po_id !== null) {
            $sql .= " AND (po_id IS NULL OR po_id != :exclude_po_id)";
            $namedParams[':exclude_po_id'] = $exclude_po_id;
        }
        $sql .= " GROUP BY item_id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($namedParams);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['item_id']] = $row['pending_supplier_qty'];
        }
        return $result;
    }

    public function getExistingRequestedOrders($item_ids) {
        if (empty($item_ids)) return [];
        $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
        $sql = "SELECT item_id, supplier_order_id
                FROM supplier_orders
                WHERE status = 'requested' AND `remove` = 0
                  AND item_id IN ({$placeholders})
                GROUP BY item_id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($item_ids);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['item_id']] = $row['supplier_order_id'];
        }
        return $result;
    }

    public function processSupplierOrder($id, $data) {
        $sql = "UPDATE supplier_orders
                SET supplier_name = :supplier_name,
                    quantity = :quantity,
                    unit_cost = :unit_cost,
                    order_date = :order_date,
                    expected_date = :expected_date,
                    remarks = :remarks,
                    po_id = :po_id,
                    status = 'pending'
                WHERE supplier_order_id = :id AND status = 'requested'";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'supplier_name' => $data['supplier_name'],
            'quantity' => $data['quantity'],
            'unit_cost' => $data['unit_cost'],
            'order_date' => $data['order_date'],
            'expected_date' => $data['expected_date'],
            'remarks' => $data['remarks'],
            'po_id' => $data['po_id'] ?? null,
            'id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function batchProcessSupplierOrders($ids, $data) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $checkSql = "SELECT supplier_order_id, status FROM supplier_orders
                         WHERE supplier_order_id IN ({$placeholders}) AND `remove` = 0";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute($ids);
            $rows = $checkStmt->fetchAll();

            if (count($rows) !== count($ids)) {
                $conn->rollBack();
                throw new \RuntimeException('One or more selected orders were not found or have been deleted.');
            }

            foreach ($rows as $row) {
                if ($row['status'] !== 'requested') {
                    $conn->rollBack();
                    throw new \RuntimeException("Order #{$row['supplier_order_id']} is no longer in Requested status. Please refresh and try again.");
                }
            }

            $updateSql = "UPDATE supplier_orders
                          SET supplier_name = :supplier_name,
                              unit_cost = :unit_cost,
                              order_date = :order_date,
                              expected_date = :expected_date,
                              status = 'pending'
                          WHERE supplier_order_id IN ({$placeholders})";
            $params = [
                'supplier_name' => $data['supplier_name'],
                'unit_cost' => $data['unit_cost'],
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'],
            ];
            foreach ($ids as $idx => $id) {
                $params[":id_{$idx}"] = $id;
            }
            $stmt = $conn->prepare($updateSql);
            $stmt->execute($params);

            $conn->commit();
            return $stmt->rowCount();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    // ─── Purchasing PO (renamed from Procurement PO) ────────────────────────────

    public function getPurchasingPoFiltered($filters = []) {
        $where = ["so.`remove` = 0"];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "so.status = :status";
            $params['status'] = $filters['status'];
        } else {
            // Default: show only requested and pending for purchasing module
            $where[] = "so.status IN ('requested', 'pending')";
        }
        if (!empty($filters['supplier_name'])) {
            $where[] = "so.supplier_name LIKE :supplier_name";
            $params['supplier_name'] = '%' . $filters['supplier_name'] . '%';
        }
        if (!empty($filters['search'])) {
            $where[] = "(so.supplier_name LIKE :search OR i.item_code LIKE :search OR i.item_description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT so.*, i.item_code, i.item_description, i.item_uom,
                       u.full_name AS created_by_name,
                       po.customer_po_number
                FROM supplier_orders so
                JOIN items i ON so.item_id = i.item_id
                JOIN users u ON so.created_by = u.user_id
                LEFT JOIN purchase_orders po ON so.po_id = po.po_id
                WHERE {$whereSql}
                ORDER BY so.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getPurchasingPoById($id) {
        $sql = "SELECT so.*, i.item_code, i.item_description, i.item_uom
                FROM supplier_orders so
                JOIN items i ON so.item_id = i.item_id
                WHERE so.supplier_order_id = :id AND so.`remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: false;
    }

    public function createPurchasingPo($data) {
        $status = $data['status'] ?? 'requested';
        $poId = $data['po_id'] ?? null;
        $sql = "INSERT INTO supplier_orders (supplier_name, item_id, quantity, unit_cost, order_date, expected_date, remarks, created_by, status, po_id)
                VALUES (:supplier_name, :item_id, :quantity, :unit_cost, :order_date, :expected_date, :remarks, :created_by, :status, :po_id)";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'supplier_name' => $data['supplier_name'],
            'item_id' => $data['item_id'],
            'quantity' => $data['quantity'],
            'unit_cost' => $data['unit_cost'],
            'order_date' => $data['order_date'],
            'expected_date' => $data['expected_date'],
            'remarks' => $data['remarks'],
            'created_by' => $data['created_by'],
            'status' => $status,
            'po_id' => $poId
        ]);
        return self::getConnection()->lastInsertId();
    }

    public function processPurchasingPo($id, $data) {
        $sql = "UPDATE supplier_orders
                SET supplier_name = :supplier_name,
                    quantity = :quantity,
                    unit_cost = :unit_cost,
                    order_date = :order_date,
                    expected_date = :expected_date,
                    remarks = :remarks,
                    po_id = :po_id,
                    status = 'processed'
                WHERE supplier_order_id = :id AND status IN ('requested', 'pending')";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'supplier_name' => $data['supplier_name'],
            'quantity' => $data['quantity'],
            'unit_cost' => $data['unit_cost'],
            'order_date' => $data['order_date'],
            'expected_date' => $data['expected_date'],
            'remarks' => $data['remarks'],
            'po_id' => $data['po_id'] ?? null,
            'id' => $id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function batchProcessPurchasingPo($ids, $data) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $checkSql = "SELECT supplier_order_id, status FROM supplier_orders
                         WHERE supplier_order_id IN ({$placeholders}) AND `remove` = 0";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute($ids);
            $rows = $checkStmt->fetchAll();

            if (count($rows) !== count($ids)) {
                $conn->rollBack();
                throw new \RuntimeException('One or more selected orders were not found or have been deleted.');
            }

            foreach ($rows as $row) {
                if (!in_array($row['status'], ['requested', 'pending'])) {
                    $conn->rollBack();
                    throw new \RuntimeException("Order #{$row['supplier_order_id']} is no longer in Requested/Pending status. Please refresh and try again.");
                }
            }

            $updateSql = "UPDATE supplier_orders
                          SET supplier_name = :supplier_name,
                              unit_cost = :unit_cost,
                              order_date = :order_date,
                              expected_date = :expected_date,
                              status = 'processed'
                          WHERE supplier_order_id IN ({$placeholders})";
            $params = [
                'supplier_name' => $data['supplier_name'],
                'unit_cost' => $data['unit_cost'],
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'],
            ];
            foreach ($ids as $idx => $id) {
                $params[":id_{$idx}"] = $id;
            }
            $stmt = $conn->prepare($updateSql);
            $stmt->execute($params);

            $conn->commit();
            return $stmt->rowCount();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function cancelPurchasingPo($id) {
        $sql = "UPDATE supplier_orders SET status = 'cancelled' WHERE supplier_order_id = :id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function deletePurchasingPo($id) {
        $sql = "UPDATE supplier_orders SET `remove` = 1 WHERE supplier_order_id = :id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    // ─── Receiving Purchasing PO ────────────────────────────────────────────────

    public function getReceivingPoFiltered($filters = []) {
        $where = ["so.`remove` = 0", "so.status IN ('pending', 'processed', 'partially_received', 'received')"];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "so.status = :status";
            $params['status'] = $filters['status'];
        }
        if (!empty($filters['supplier'])) {
            $where[] = "so.supplier_name = :supplier";
            $params['supplier'] = $filters['supplier'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(so.supplier_name LIKE :search OR i.item_code LIKE :search OR i.item_description LIKE :search OR po.customer_po_number LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT so.*, i.item_code, i.item_description, i.item_uom,
                       u.full_name AS created_by_name,
                       po.customer_po_number
                FROM supplier_orders so
                JOIN items i ON so.item_id = i.item_id
                JOIN users u ON so.created_by = u.user_id
                LEFT JOIN purchase_orders po ON so.po_id = po.po_id
                WHERE {$whereSql}
                ORDER BY so.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getPurchasingPoSuppliers() {
        $sql = "SELECT DISTINCT supplier_name FROM supplier_orders WHERE `remove` = 0 AND status IN ('pending', 'processed', 'partially_received', 'received') ORDER BY supplier_name";
        $stmt = self::getConnection()->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function receivePurchasingPo($id, $data) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
            $order = $this->getPurchasingPoById($id);
            if (!$order) {
                throw new \RuntimeException('Purchasing PO not found.');
            }

            $receivedQty = floatval($data['received_qty']);
            $currentReceived = floatval($order['received_qty'] ?? 0);
            $orderedQty = floatval($order['quantity']);
            $newReceivedQty = $currentReceived + $receivedQty;

            // Determine new status
            $newStatus = ($newReceivedQty >= $orderedQty) ? 'received' : 'partially_received';

            $qcGateStatus = 'pending_qc';
            $sql = "UPDATE supplier_orders 
                    SET received_qty = :received_qty,
                        received_date = :received_date,
                        status = :status,
                        last_update = NOW()
                    WHERE supplier_order_id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'received_qty' => $newReceivedQty,
                'received_date' => $data['received_date'],
                'status' => $qcGateStatus,
                'id' => $id
            ]);

            // Gatekeeping: received goods sit in inspection hold until QC approves them.
            // Available SOH is computed from qty_on_hand only; qty_for_inspect is excluded.
            $conn->prepare("
                INSERT INTO inventory_balances (item_id, site_code, qty_on_hand, qty_for_inspect)
                VALUES (:item_id, 'MAIN', 0, :qty)
                ON DUPLICATE KEY UPDATE qty_for_inspect = qty_for_inspect + :qty2
            ")->execute([
                'item_id' => $order['item_id'],
                'qty' => $receivedQty,
                'qty2' => $receivedQty
            ]);

            // Log the receipt details
            if (!empty($data['remarks']) || !empty($data['lot_number']) || !empty($data['delivery_receipt_no'])) {
                $receiptInfo = [];
                if (!empty($data['lot_number'])) $receiptInfo[] = "Lot: {$data['lot_number']}";
                if (!empty($data['expiry_date'])) $receiptInfo[] = "Expiry: {$data['expiry_date']}";
                if (!empty($data['delivery_receipt_no'])) $receiptInfo[] = "DR: {$data['delivery_receipt_no']}";
                if (!empty($data['remarks'])) $receiptInfo[] = $data['remarks'];
                
                $receiptRemarks = implode(' | ', $receiptInfo);
                $existingRemarks = $order['remarks'] ?? '';
                $updatedRemarks = $existingRemarks ? $existingRemarks . "\n[Receipt] " . $receiptRemarks : "[Receipt] " . $receiptRemarks;
                
                $conn->prepare("UPDATE supplier_orders SET remarks = :remarks WHERE supplier_order_id = :id")
                    ->execute(['remarks' => $updatedRemarks, 'id' => $id]);
            }

            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    // ─── MRP Snapshots ──────────────────────────────────────────────────────

    public function saveMrpRun($po_id, $customer_id, $user_id, $sections, $consolidated) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare("INSERT INTO mrp_runs (po_id, customer_id, user_id) VALUES (:po_id, :customer_id, :user_id)");
            $stmt->execute(['po_id' => $po_id, 'customer_id' => $customer_id, 'user_id' => $user_id]);
            $runId = $conn->lastInsertId();

            $itemStmt = $conn->prepare("INSERT INTO mrp_run_items (run_id, fg_item_id, component_item_id, total_reqt, soh, allocated, pending, excess, remarks)
                VALUES (:run_id, :fg_item_id, :component_item_id, :total_reqt, :soh, :allocated, :pending, :excess, :remarks)");

            foreach ($sections as $sec) {
                foreach ($sec['components'] as $comp) {
                    $itemStmt->execute([
                        'run_id' => $runId,
                        'fg_item_id' => $sec['fg_item_id'],
                        'component_item_id' => $comp['component_item_id'],
                        'total_reqt' => $comp['total_reqt'],
                        'soh' => $comp['soh'],
                        'allocated' => $comp['allocated'],
                        'pending' => $comp['pending'],
                        'excess' => $comp['excess'],
                        'remarks' => $comp['remarks']
                    ]);
                }
            }

            $conn->commit();
            return $runId;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function getMrpRunsByPO($po_id) {
        $sql = "SELECT mr.*, u.full_name AS user_name
                FROM mrp_runs mr
                JOIN users u ON mr.user_id = u.user_id
                WHERE mr.po_id = :po_id
                ORDER BY mr.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['po_id' => $po_id]);
        return $stmt->fetchAll();
    }

    public function getMrpRunItems($run_id) {
        $sql = "SELECT mri.*, i.item_code AS fg_code, i.item_description AS fg_name,
                       ic.item_code AS component_code, ic.item_description AS component_name, ic.item_uom AS component_uom
                FROM mrp_run_items mri
                JOIN items i ON mri.fg_item_id = i.item_id
                JOIN items ic ON mri.component_item_id = ic.item_id
                WHERE mri.run_id = :run_id
                ORDER BY mri.fg_item_id, mri.component_item_id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['run_id' => $run_id]);
        return $stmt->fetchAll();
    }

    public function getExistingMrpRunForPO($po_id) {
        $sql = "SELECT run_id FROM mrp_runs WHERE po_id = :po_id LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['po_id' => $po_id]);
        return $stmt->fetch();
    }

    public function getLinkedSupplierOrdersForRun($run_id) {
        $sql = "SELECT supplier_order_id, item_id, quantity, status
                FROM supplier_orders
                WHERE remarks = CONCAT('Auto-generated from MRP Run #', :run_id)
                  AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['run_id' => $run_id]);
        return $stmt->fetchAll();
    }

    public function cancelLinkedSupplierOrdersForRun($run_id) {
        $sql = "UPDATE supplier_orders SET status = 'cancelled'
                WHERE remarks = CONCAT('Auto-generated from MRP Run #', :run_id)
                  AND status = 'requested' AND `remove` = 0";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['run_id' => $run_id]);
        return $stmt->rowCount();
    }

    public function deleteMrpRun($run_id) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
            $linkedOrders = $this->getLinkedSupplierOrdersForRun($run_id);

            foreach ($linkedOrders as $order) {
                if (in_array($order['status'], ['pending', 'received', 'completed'])) {
                    $conn->rollBack();
                    return [
                        'success' => false,
                        'message' => 'Cannot delete MRP snapshot because associated Procurement POs have already been processed with suppliers.'
                    ];
                }
            }

            $this->cancelLinkedSupplierOrdersForRun($run_id);

            $runStmt = $conn->prepare("DELETE FROM mrp_runs WHERE run_id = :run_id");
            $runStmt->execute(['run_id' => $run_id]);

            $conn->commit();
            return ['success' => true];
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function getAllMrpRuns() {
        $sql = "SELECT mr.*, u.full_name AS user_name,
                       po.customer_po_number, po.total_quantity, po.produced_quantity, po.delivered_quantity,
                       c.customer_name, c.customer_code
                FROM mrp_runs mr
                JOIN users u ON mr.user_id = u.user_id
                JOIN purchase_orders po ON mr.po_id = po.po_id
                JOIN customers c ON mr.customer_id = c.customer_id
                ORDER BY mr.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getMrpRunSnapshotData($run_id) {
        $conn = self::getConnection();

        $run = $conn->prepare("SELECT mr.*, u.full_name AS user_name
                FROM mrp_runs mr
                JOIN users u ON mr.user_id = u.user_id
                WHERE mr.run_id = :run_id");
        $run->execute(['run_id' => $run_id]);
        $runRow = $run->fetch();
        if (!$runRow) return false;

        $poStmt = $conn->prepare("SELECT po.*, c.customer_name, c.customer_code
                FROM purchase_orders po
                JOIN customers c ON po.customer_id = c.customer_id
                WHERE po.po_id = :po_id");
        $poStmt->execute(['po_id' => $runRow['po_id']]);
        $poHeader = $poStmt->fetch();
        if (!$poHeader) return false;

        $allPoItems = $this->getPOItemsWithBOM($runRow['po_id']);

        $snapshotItems = $this->getMrpRunItems($run_id);

        $batchMeta = [];
        foreach ($allPoItems as $poi) {
            if (empty($poi['bom_id'])) continue;
            $batchQty = floatval($poi['batch_qty'] ?: 1);
            $batchMeta[$poi['item_id']] = [
                'target_qty' => $poi['quantity'],
                'item_uom' => $poi['item_uom'],
                'batch_qty' => $batchQty,
                'batch_uom' => $poi['batch_uom'],
                'batches_needed' => floatval($poi['quantity']) / $batchQty,
                'fg_code' => $poi['item_code'],
                'fg_name' => $poi['item_description'],
            ];
        }

        $sectionsGrouped = [];
        foreach ($snapshotItems as $item) {
            $fgId = $item['fg_item_id'];
            if (!isset($sectionsGrouped[$fgId])) {
                $meta = $batchMeta[$fgId] ?? [
                    'target_qty' => 0, 'item_uom' => '', 'batch_qty' => 1,
                    'batch_uom' => 'PCS', 'batches_needed' => 0,
                    'fg_code' => $item['fg_code'], 'fg_name' => $item['fg_name'],
                ];
                $sectionsGrouped[$fgId] = [
                    'fg_item_id' => $fgId,
                    'fg_code' => $meta['fg_code'],
                    'fg_name' => $meta['fg_name'],
                    'target_qty' => $meta['target_qty'],
                    'item_uom' => $meta['item_uom'],
                    'batch_qty' => $meta['batch_qty'],
                    'batch_uom' => $meta['batch_uom'],
                    'batches_needed' => $meta['batches_needed'],
                    'components' => [],
                ];
            }
            $sectionsGrouped[$fgId]['components'][] = [
                'component_item_id' => $item['component_item_id'],
                'item_code' => $item['component_code'],
                'item_description' => $item['component_name'],
                'item_uom' => $item['component_uom'],
                'total_reqt' => floatval($item['total_reqt']),
                'soh' => floatval($item['soh']),
                'allocated' => floatval($item['allocated']),
                'pending' => floatval($item['pending']),
                'supplier_pending' => floatval($item['pending'] ?? 0) - floatval($item['allocated'] ?? 0),
                'excess' => floatval($item['excess']),
                'remarks' => $item['remarks'],
            ];
        }
        $mrpSections = array_values($sectionsGrouped);

        $consolidatedMap = [];
        foreach ($mrpSections as $section) {
            foreach ($section['components'] as $row) {
                $key = $row['item_code'];
                if (!isset($consolidatedMap[$key])) {
                    $consolidatedMap[$key] = $row;
                } else {
                    $consolidatedMap[$key]['total_reqt'] += $row['total_reqt'];
                    $consolidatedMap[$key]['soh'] = $row['soh'];
                    $consolidatedMap[$key]['allocated'] += $row['allocated'];
                    $consolidatedMap[$key]['pending'] += $row['pending'];
                    $consolidatedMap[$key]['supplier_pending'] = ($consolidatedMap[$key]['supplier_pending'] ?? 0) + ($row['supplier_pending'] ?? 0);
                    $consolidatedMap[$key]['excess'] = ($consolidatedMap[$key]['soh'] - $consolidatedMap[$key]['allocated']) - $consolidatedMap[$key]['total_reqt'] + ($consolidatedMap[$key]['supplier_pending'] ?? 0);
                    if ($consolidatedMap[$key]['excess'] < 0) {
                        $consolidatedMap[$key]['remarks'] = 'LACKING';
                    }
                }
            }
        }
        $consolidated = array_values($consolidatedMap);

        return [
            'po' => $poHeader,
            'mrpSections' => $mrpSections,
            'consolidated' => $consolidated,
            'userName' => $runRow['user_name'],
            'userDept' => $_SESSION['department'] ?? '',
            'snapshotDate' => $runRow['date_created'],
            'runId' => $run_id,
        ];
    }

}