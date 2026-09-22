<?php
namespace App\Models;

use App\Core\BaseModel;

class QcModel extends BaseModel {

    public function getProductionHistoryForQC($filters = []) {
        $sql = "SELECT ph.*, po.customer_po_number, po.production_type, c.customer_name, u.full_name,
                    eu.full_name as edited_by_name, ph.date_edited,
                    ph.qc_remark, ph.qc_inspected_by, ph.qc_inspected_at,
                    ph.qc_inspector_name,
                    ph.qa_remark, ph.qa_inspected_at,
                    ph.qa_inspector_name,
                    poi.quantity as ordered_quantity,
                    COALESCE(i.item_code, ifa.item_code) as item_code
                FROM production_history ph 
                LEFT JOIN purchase_orders po ON ph.po_id = po.po_id 
                LEFT JOIN customers c ON po.customer_id = c.customer_id 
                LEFT JOIN users u ON ph.user_id = u.user_id
                LEFT JOIN users eu ON ph.edited_by = eu.user_id
                LEFT JOIN purchase_order_items poi ON ph.poi_id = poi.poi_id
                LEFT JOIN items i ON ph.item_id = i.item_id
                LEFT JOIN items ifa ON ph.item_description = ifa.item_description AND ph.item_id IS NULL
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

        $stmt = self::getConnection()->prepare("SELECT COUNT(*) as inspected FROM production_history WHERE qc_remark IS NOT NULL");
        $stmt->execute();
        $inspected = $stmt->fetch()['inspected'];

        return [
            'total' => $total,
            'inspected' => $inspected,
            'remaining' => $total - $inspected
        ];
    }

    public function updateQcRemark($historyId, $inspectorName, $remark) {
        $stmt = self::getConnection()->prepare(
            "UPDATE production_history SET qc_remark = :remark, qc_inspector_name = :inspector_name, qc_inspected_at = NOW() WHERE history_id = :history_id"
        );
        return $stmt->execute([
            'remark' => $remark,
            'inspector_name' => $inspectorName,
            'history_id' => $historyId
        ]);
    }

    public function getPendingQcItems() {
        $sql = "SELECT
                    ri.id AS receiving_item_id,
                    ri.po_ref AS customer_po_number,
                    ri.supplier AS supplier_name,
                    ri.item_code,
                    ri.item_name AS item_description,
                    ri.uom AS item_uom,
                    ri.ordered_qty,
                    ri.received_qty,
                    ri.passed_qty,
                    ri.rejected_qty,
                    ri.lot_number,
                    ri.received_date,
                    ri.remarks,
                    ri.qc_status,
                    ri.supplier_order_id,
                    i.item_id
                FROM receiving_items ri
                LEFT JOIN items i ON ri.item_code = i.item_code
                WHERE ri.qc_status = 'PENDING_QC'
                ORDER BY ri.received_date ASC, ri.id ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCompletedQcItems() {
        $sql = "SELECT
                    ri.id AS receiving_item_id,
                    ri.po_ref AS customer_po_number,
                    ri.supplier AS supplier_name,
                    ri.item_code,
                    ri.item_name AS item_description,
                    ri.uom AS item_uom,
                    ri.ordered_qty,
                    ri.received_qty,
                    ri.passed_qty,
                    ri.rejected_qty,
                    ri.lot_number,
                    ri.received_date,
                    ri.inspected_at,
                    ri.inspected_by,
                    ri.remarks,
                    ri.qc_status,
                    ri.supplier_order_id,
                    i.item_id
                FROM receiving_items ri
                LEFT JOIN items i ON ri.item_code = i.item_code
                WHERE ri.qc_status IN ('PASSED', 'REJECTED')
                ORDER BY ri.inspected_at DESC, ri.id DESC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getReceivingItemById($id) {
        $sql = "SELECT
                    ri.*,
                    ri.id AS receiving_item_id,
                    ri.po_ref AS customer_po_number,
                    ri.supplier AS supplier_name,
                    ri.item_name AS item_description,
                    ri.uom AS item_uom,
                    i.item_id
                FROM receiving_items ri
                LEFT JOIN items i ON ri.item_code = i.item_code
                WHERE ri.id = :id";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function createReceivingItem($data) {
        // Duplicate prevention: one pending inspection row per po_ref + item_code.
        $poRef = $data['po_ref'] ?? ($data['po_id'] ?? null);
        $itemCode = $data['item_code'] ?? null;
        $conn = self::getConnection();
        if ($poRef !== null && $itemCode !== null) {
            $dup = $conn->prepare(
                "SELECT id FROM receiving_items
                 WHERE po_ref = :po_ref AND item_code = :item_code AND qc_status = 'PENDING_QC'
                 LIMIT 1"
            );
            $dup->execute(['po_ref' => $poRef, 'item_code' => $itemCode]);
            $dupRow = $dup->fetch();
            if ($dupRow) {
                $conn->prepare(
                    "UPDATE receiving_items
                     SET received_qty = :received_qty,
                         received_date = :received_date,
                         lot_number = :lot_number,
                         expiry_date = :expiry_date,
                         dr_invoice_no = :dr_invoice_no,
                         remarks = :remarks
                     WHERE id = :rid"
                )->execute([
                    'received_qty' => $data['received_qty'] ?? 0,
                    'received_date' => $data['received_date'] ?? date('Y-m-d'),
                    'lot_number' => $data['lot_number'] ?? null,
                    'expiry_date' => $data['expiry_date'] ?? null,
                    'dr_invoice_no' => $data['dr_invoice_no'] ?? null,
                    'remarks' => $data['remarks'] ?? null,
                    'rid' => $dupRow['id'],
                ]);
                return (int) $dupRow['id'];
            }
        }

        $sql = "INSERT INTO receiving_items (
                    po_ref, supplier, item_code, item_name, uom,
                    ordered_qty, received_qty, passed_qty, rejected_qty,
                    lot_number, expiry_date, dr_invoice_no, received_date,
                    remarks, qc_status
                ) VALUES (
                    :po_ref, :supplier, :item_code, :item_name, :uom,
                    :ordered_qty, :received_qty, :passed_qty, :rejected_qty,
                    :lot_number, :expiry_date, :dr_invoice_no, :received_date,
                    :remarks, :qc_status
                )";

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute([
            'po_ref' => $data['po_ref'] ?? ($data['po_id'] ?? null),
            'supplier' => $data['supplier_name'] ?? null,
            'item_code' => $data['item_code'] ?? null,
            'item_name' => $data['item_description'] ?? null,
            'uom' => $data['uom'] ?? null,
            'ordered_qty' => $data['ordered_qty'] ?? ($data['received_qty'] ?? 0),
            'received_qty' => $data['received_qty'] ?? 0,
            'passed_qty' => $data['passed_qty'] ?? 0,
            'rejected_qty' => $data['rejected_qty'] ?? 0,
            'lot_number' => $data['lot_number'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'dr_invoice_no' => $data['dr_invoice_no'] ?? null,
            'received_date' => $data['received_date'] ?? date('Y-m-d'),
            'remarks' => $data['remarks'] ?? null,
            'qc_status' => 'PENDING_QC',
        ]);

        return (int) self::getConnection()->lastInsertId();
    }

    public function recordQcInspection($data) {
        $receivingItemId = (int) ($data['receiving_item_id'] ?? 0);
        $decision = strtoupper((string) ($data['decision'] ?? ''));
        $receivedQty = (float) ($data['received_qty'] ?? 0);
        $passedQty = (float) ($data['passed_qty'] ?? 0);
        $rejectedQty = (float) ($data['rejected_qty'] ?? 0);
        $inspectorName = trim((string) ($data['inspector_name'] ?? ''));
        $remarks = trim((string) ($data['remarks'] ?? ''));

        if ($receivingItemId <= 0) {
            throw new \RuntimeException('Receiving item is required.');
        }
        if (!in_array($decision, ['PASSED', 'REJECTED'], true)) {
            throw new \RuntimeException('QC decision must be PASSED or REJECTED.');
        }
        if ($receivedQty <= 0) {
            throw new \RuntimeException('Received quantity must be greater than zero.');
        }
        if (abs(($passedQty + $rejectedQty) - $receivedQty) > 0.0001) {
            throw new \RuntimeException('Passed + rejected quantities must equal the received quantity.');
        }
        if ($decision === 'PASSED' && $passedQty <= 0) {
            throw new \RuntimeException('Passed quantity must be greater than zero for accepted goods.');
        }
        if ($decision === 'REJECTED' && $rejectedQty <= 0) {
            throw new \RuntimeException('Rejected quantity must be greater than zero for rejected goods.');
        }
        if ($inspectorName === '') {
            throw new \RuntimeException('Inspector name is required.');
        }

        $receivingItem = $this->getReceivingItemById($receivingItemId);
        if (!$receivingItem) {
            throw new \RuntimeException('Receiving item was not found.');
        }
        // Server-side duplicate guard: a queue entry can only be inspected once
        // (blocks double-clicked Approve/Reject submits).
        $currentQcStatus = strtoupper((string) ($receivingItem['qc_status'] ?? ''));
        if ($currentQcStatus !== 'PENDING_QC') {
            throw new \RuntimeException('This receiving item has already been inspected (status: ' . $currentQcStatus . ').');
        }
        $itemId = (int) ($receivingItem['item_id'] ?? 0);
        if ($itemId <= 0) {
            throw new \RuntimeException('Could not resolve item for receiving item #' . $receivingItemId . '.');
        }

        $finalStatus = $decision;
        $conn = self::getConnection();
        $conn->beginTransaction();

        try {
            $inspectorUserId = (int) ($_SESSION['user_id'] ?? 0);

            $insStmt = $conn->prepare("INSERT INTO qc_inspections (
                    receiving_item_id, decision, passed_qty, rejected_qty, inspector_name, remarks, inspected_at
                ) VALUES (
                    :receiving_item_id, :decision, :passed_qty, :rejected_qty, :inspector_name, :remarks, NOW()
                )");
            $insStmt->execute([
                'receiving_item_id' => $receivingItemId,
                'decision' => $decision,
                'passed_qty' => $passedQty,
                'rejected_qty' => $rejectedQty,
                'inspector_name' => $inspectorName,
                'remarks' => $remarks,
            ]);

            $updateStmt = $conn->prepare("UPDATE receiving_items
                SET qc_status = :status,
                    inspected_by = :inspector_id,
                    inspected_at = NOW(),
                    remarks = :remarks,
                    passed_qty = :passed_qty,
                    rejected_qty = :rejected_qty
                WHERE id = :receiving_item_id");
            $updateStmt->execute([
                'status' => $finalStatus,
                'inspector_id' => $inspectorUserId ?: null,
                'remarks' => $remarks,
                'passed_qty' => $passedQty,
                'rejected_qty' => $rejectedQty,
                'receiving_item_id' => $receivingItemId,
            ]);

            // Reflect QC decision on the source supplier order (never delete/hide it).
            $supplierOrderId = (int) ($receivingItem['supplier_order_id'] ?? 0);
            if ($supplierOrderId > 0) {
                if ($decision === 'PASSED') {
                    $conn->prepare("UPDATE supplier_orders
                        SET status = IF(received_qty >= quantity, 'received', 'partially_received'),
                            last_update = NOW()
                        WHERE supplier_order_id = :sid AND `remove` = 0")->execute(['sid' => $supplierOrderId]);
                } else {
                    $conn->prepare("UPDATE supplier_orders
                        SET status = 'rejected', last_update = NOW()
                        WHERE supplier_order_id = :sid AND `remove` = 0")->execute(['sid' => $supplierOrderId]);
                }
            }

            $qtyToRelease = $decision === 'PASSED' ? $passedQty : $rejectedQty;
            if ($qtyToRelease > 0) {
                $conn->prepare("UPDATE inventory_balances
                    SET qty_for_inspect = GREATEST(qty_for_inspect - :qty_to_release, 0)
                    WHERE item_id = :item_id AND site_code = 'MAIN'")->execute([
                    'qty_to_release' => $qtyToRelease,
                    'item_id' => $itemId,
                ]);
            }

            if ($decision === 'PASSED' && $passedQty > 0) {
                $stockSql = "INSERT INTO inventory_stock (
                                item_id, site_code, lot_number, qty_on_hand, qty_blocked, qty_rejected, status, source_receiving_item_id, reference_type, reference_id, updated_at
                            ) VALUES (
                                :item_id, 'MAIN', :lot_number, :qty_on_hand, 0, 0, 'PASSED', :source_receiving_item_id, 'receiving_item', :source_id, NOW()
                            ) ON DUPLICATE KEY UPDATE
                                qty_on_hand = qty_on_hand + VALUES(qty_on_hand),
                                status = 'PASSED',
                                updated_at = NOW()";
                $conn->prepare($stockSql)->execute([
                    'item_id' => $itemId,
                    'lot_number' => $receivingItem['lot_number'],
                    'qty_on_hand' => $passedQty,
                    'source_receiving_item_id' => $receivingItemId,
                    'source_id' => $receivingItemId,
                ]);

                $conn->prepare("INSERT INTO inventory_balances (item_id, site_code, qty_on_hand, qty_for_inspect)
                    VALUES (:item_id, 'MAIN', :qty_on_hand, 0)
                    ON DUPLICATE KEY UPDATE qty_on_hand = qty_on_hand + VALUES(qty_on_hand)")->execute([
                    'item_id' => $itemId,
                    'qty_on_hand' => $passedQty,
                ]);
            }

            if ($decision === 'REJECTED' && $rejectedQty > 0) {
                $stockSql = "INSERT INTO inventory_stock (
                                item_id, site_code, lot_number, qty_on_hand, qty_blocked, qty_rejected, status, source_receiving_item_id, reference_type, reference_id, updated_at
                            ) VALUES (
                                :item_id, 'MAIN', :lot_number, 0, :qty_blocked, :qty_rejected, 'REJECTED', :source_receiving_item_id, 'receiving_item', :source_id, NOW()
                            ) ON DUPLICATE KEY UPDATE
                                qty_blocked = qty_blocked + VALUES(qty_blocked),
                                qty_rejected = qty_rejected + VALUES(qty_rejected),
                                status = 'REJECTED',
                                updated_at = NOW()";
                $conn->prepare($stockSql)->execute([
                    'item_id' => $itemId,
                    'lot_number' => $receivingItem['lot_number'],
                    'qty_blocked' => $rejectedQty,
                    'qty_rejected' => $rejectedQty,
                    'source_receiving_item_id' => $receivingItemId,
                    'source_id' => $receivingItemId,
                ]);
            }

            $conn->commit();
            return [
                'success' => true,
                'decision' => $decision,
                'receiving_item_id' => $receivingItemId,
            ];
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function approveReceivingInspection($id, $data = []) {
        $receivingItemId = (int) ($id ?: ($data['receiving_item_id'] ?? 0));
        $decision = strtoupper((string) ($data['decision'] ?? 'PASSED'));
        $payload = [
            'receiving_item_id' => $receivingItemId,
            'decision' => $decision,
            'received_qty' => isset($data['received_qty']) ? (float) $data['received_qty'] : 0,
            'passed_qty' => isset($data['passed_qty']) ? (float) $data['passed_qty'] : 0,
            'rejected_qty' => isset($data['rejected_qty']) ? (float) $data['rejected_qty'] : 0,
            'inspector_name' => trim((string) ($data['inspector_name'] ?? '')) ?: ($_SESSION['full_name'] ?? 'QC'),
            'remarks' => trim((string) ($data['remarks'] ?? '')),
        ];

        return $this->recordQcInspection($payload);
    }
}
