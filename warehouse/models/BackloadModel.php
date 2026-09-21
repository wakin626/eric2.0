<?php
namespace App\Models;

use App\Core\BaseModel;

class BackloadModel extends BaseModel {
    protected $table = 'backloads';

    private $warehouseModel;

    public function setWarehouseModel(WarehouseModel $warehouseModel) {
        $this->warehouseModel = $warehouseModel;
    }

    public function createBackload($data) {
        $conn = self::getConnection();
        $conn->beginTransaction();
        try {
            $conn->prepare("INSERT INTO backloads (delivery_id, po_id, poi_id, lot_id, lot_number, quantity, `cases`, reason, backloaded_by, backload_date)
                VALUES (:delivery_id, :po_id, :poi_id, :lot_id, :lot_number, :quantity, :cases, :reason, :backloaded_by, :backload_date)")
                ->execute([
                    'delivery_id' => $data['delivery_id'],
                    'po_id' => $data['po_id'],
                    'poi_id' => $data['poi_id'],
                    'lot_id' => $data['lot_id'],
                    'lot_number' => $data['lot_number'] ?? '',
                    'quantity' => $data['quantity'],
                    'cases' => $data['cases'] ?? null,
                    'reason' => $data['reason'] ?? '',
                    'backloaded_by' => $data['backloaded_by'],
                    'backload_date' => $data['backload_date'] ?? date('Y-m-d')
                ]);

            $backloadId = $conn->lastInsertId();

            $poiId = $data['poi_id'];

            $delStmt = $conn->prepare("SELECT lot_items FROM deliveries WHERE po_id = :po_id AND `remove` = 0 AND lot_items IS NOT NULL");
            $delStmt->execute(['po_id' => $data['po_id']]);
            $totalDelivered = 0;
            while ($delRow = $delStmt->fetch()) {
                $items = json_decode($delRow['lot_items'], true);
                if (!is_array($items)) continue;
                foreach ($items as $li) {
                    if (intval($li['poi_id'] ?? 0) === intval($poiId)) {
                        $totalDelivered += intval($li['qty'] ?? 0);
                    }
                }
            }

            $backStmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) FROM backloads WHERE poi_id = :poi_id AND `remove` = 0");
            $backStmt->execute(['poi_id' => $poiId]);
            $totalBackloaded = intval($backStmt->fetchColumn());

            $newDeliveredQty = max(0, $totalDelivered - $totalBackloaded);
            $conn->prepare("UPDATE purchase_order_items SET delivered_quantity = :qty WHERE poi_id = :poi_id")
                ->execute(['qty' => $newDeliveredQty, 'poi_id' => $poiId]);

            $conn->prepare("UPDATE purchase_orders SET delivered_quantity = (
                SELECT COALESCE(SUM(delivered_quantity), 0) FROM purchase_order_items WHERE po_id = :po_id
            ) WHERE po_id = :po_id2")
                ->execute(['po_id' => $data['po_id'], 'po_id2' => $data['po_id']]);

            $this->warehouseModel->recalculatePODeliveryStatus($data['po_id']);

            $conn->commit();
            return $backloadId;
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function getBackloads($filters = []) {
        $sql = "SELECT b.*, d.dr_number, po.customer_po_number, c.customer_name,
                       i.item_code, i.item_description, u.full_name as backloaded_by_name
                FROM backloads b
                INNER JOIN deliveries d ON b.delivery_id = d.delivery_id
                INNER JOIN purchase_orders po ON b.po_id = po.po_id
                INNER JOIN customers c ON po.customer_id = c.customer_id
                INNER JOIN purchase_order_items poi ON b.poi_id = poi.poi_id
                INNER JOIN items i ON poi.item_id = i.item_id
                INNER JOIN users u ON b.backloaded_by = u.user_id
                WHERE b.`remove` = 0";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (po.customer_po_number LIKE :search OR d.dr_number LIKE :search2)";
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['customer_id'])) {
            $sql .= " AND po.customer_id = :customer_id";
            $params['customer_id'] = $filters['customer_id'];
        }

        $sql .= " ORDER BY b.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getBackloadsByDeliveryId($delivery_id) {
        $sql = "SELECT b.*, i.item_code, i.item_description, u.full_name as backloaded_by_name
                FROM backloads b
                INNER JOIN purchase_order_items poi ON b.poi_id = poi.poi_id
                INNER JOIN items i ON poi.item_id = i.item_id
                INNER JOIN users u ON b.backloaded_by = u.user_id
                WHERE b.delivery_id = :delivery_id AND b.`remove` = 0
                ORDER BY b.date_created DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['delivery_id' => $delivery_id]);
        return $stmt->fetchAll();
    }

    public function getDeliveryLotsForBackload($delivery_id) {
        $sql = "SELECT d.lot_items, d.delivery_id, d.po_id
                FROM deliveries d
                WHERE d.delivery_id = :delivery_id AND d.`remove` = 0 AND d.lot_items IS NOT NULL";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['delivery_id' => $delivery_id]);
        $delivery = $stmt->fetch();
        if (!$delivery) return [];

        $lotItems = json_decode($delivery['lot_items'], true);
        if (!is_array($lotItems)) return [];

        $result = [];
        foreach ($lotItems as $li) {
            $lotId = $li['lot_id'] ?? 0;
            $poiId = $li['poi_id'] ?? 0;
            $deliveredQty = intval($li['qty'] ?? 0);

            $backloadedStmt = self::getConnection()->prepare(
                "SELECT COALESCE(SUM(b.quantity), 0) as total_backloaded
                 FROM backloads b
                 WHERE b.delivery_id = :delivery_id AND b.lot_id = :lot_id AND b.`remove` = 0"
            );
            $backloadedStmt->execute(['delivery_id' => $delivery_id, 'lot_id' => $lotId]);
            $backloaded = intval($backloadedStmt->fetchColumn());

            $available = $deliveredQty - $backloaded;

            if ($available > 0) {
                $result[] = [
                    'lot_id' => $lotId,
                    'poi_id' => $poiId,
                    'lot_number' => $li['lot_number'] ?? '',
                    'item_code' => $li['item_code'] ?? '',
                    'item_description' => $li['item_description'] ?? '',
                    'uom_conversion' => intval($li['actual_uom_conversion'] ?? $li['uom_conversion'] ?? 0),
                    'item_uom' => $li['item_uom'] ?? 'PCS',
                    'delivered_qty' => $deliveredQty,
                    'already_backloaded' => $backloaded,
                    'available_to_backload' => $available
                ];
            }
        }
        return $result;
    }
}
