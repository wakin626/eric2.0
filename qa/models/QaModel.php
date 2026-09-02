<?php
namespace App\Models;

use App\Core\BaseModel;

class QaModel extends BaseModel {

    public function getProductionHistoryForQA($filters = []) {
        $sql = "SELECT ph.*, po.customer_po_number, po.production_type, c.customer_name, u.full_name,
                    eu.full_name as edited_by_name, ph.date_edited,
                    ph.qc_remark, ph.qc_inspected_by, ph.qc_inspected_at,
                    ph.qc_inspector_name,
                    ph.qa_remark, ph.qa_inspected_by, ph.qa_inspected_at,
                    ph.qa_inspector_name,
                    poi.quantity as ordered_quantity
                FROM production_history ph 
                LEFT JOIN purchase_orders po ON ph.po_id = po.po_id 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON ph.user_id = u.user_id
                LEFT JOIN users eu ON ph.edited_by = eu.user_id
                LEFT JOIN purchase_order_items poi ON ph.poi_id = poi.poi_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (ph.lot_number LIKE :search1 
                       OR ph.item_description LIKE :search2
                       OR po.customer_po_number LIKE :search3
                       OR c.customer_name LIKE :search4
                       OR ph.sts_ref LIKE :search5
                       OR u.full_name LIKE :search6)";
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
            $params['search5'] = $like;
            $params['search6'] = $like;
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

        $sql .= " ORDER BY ph.date_created ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $lotItemTotals = [];
        foreach ($rows as &$row) {
            $lot = $row['lot_number'] ?? '';
            $item = $row['item_description'] ?? '';
            $lotItemKey = $lot . '|' . $item;

            if (!isset($lotItemTotals[$lotItemKey])) $lotItemTotals[$lotItemKey] = 0;
            $row['computed_prev_lot_qty'] = $lotItemTotals[$lotItemKey];
            $lotItemTotals[$lotItemKey] += $row['added_quantity'];
            $row['computed_new_lot_qty'] = $lotItemTotals[$lotItemKey];
        }
        unset($row);

        krsort($rows);
        return array_values($rows);
    }

    public function getInspectionCounts() {
        $stmt = self::getConnection()->prepare("SELECT COUNT(*) as total FROM production_history");
        $stmt->execute();
        $total = $stmt->fetch()['total'];

        $stmt = self::getConnection()->prepare("SELECT COUNT(*) as inspected FROM production_history WHERE qa_remark IS NOT NULL");
        $stmt->execute();
        $inspected = $stmt->fetch()['inspected'];

        return [
            'total' => $total,
            'inspected' => $inspected,
            'remaining' => $total - $inspected
        ];
    }

    public function updateQaRemark($historyId, $inspectorName, $remark) {
        $stmt = self::getConnection()->prepare(
            "UPDATE production_history SET qa_remark = :remark, qa_inspector_name = :inspector_name, qa_inspected_at = NOW() WHERE history_id = :history_id"
        );
        return $stmt->execute([
            'remark' => $remark,
            'inspector_name' => $inspectorName,
            'history_id' => $historyId
        ]);
    }
}
