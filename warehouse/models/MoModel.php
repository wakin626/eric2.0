<?php
namespace App\Models;

use App\Core\BaseModel;

class MoModel extends BaseModel {
    public function getAll($status = null) {
        $sql = "SELECT mo.*
                FROM manufacturing_orders mo";
        $params = [];

        if ($status && $status !== 'all') {
            $sql .= " WHERE mo.mo_status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY mo.created_at DESC";

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$order) {
            $order['items'] = $this->getItemsByMoId($order['mo_id']);
            $order['item_code'] = $order['items'][0]['item_code'] ?? '-';
            $order['qty_ordered'] = $order['items'][0]['qty_ordered'] ?? 0;
        }

        return $orders;
    }

    public function getItemsByMoId($moId) {
        $sql = "SELECT * FROM manufacturing_order_items WHERE mo_id = :mo_id ORDER BY moi_id ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['mo_id' => $moId]);
        return $stmt->fetchAll();
    }

    /**
     * Legacy-aware required quantity for one BOM component.
     * Legacy: (orderQty / batch_qty) * dosage * wastage
     * New:    RM → ((orderQty * fill_volume) / divisor) * dosage% * wastage
     *         others → orderQty * dosage * wastage
     */
    private function computeRequiredQty($orderQty, $bom, $comp) {
        $dosage = floatval($comp['dosage_rate'] ?? 0);
        $wastage = floatval($comp['wastage_allowance_pct'] ?? 0);

        if (!empty($bom['is_legacy_formula'])) {
            $batchQty = floatval($bom['fill_volume'] ?? $bom['batch_qty'] ?? 1) ?: 1;
            $base = ($orderQty / $batchQty) * $dosage;
        } elseif (($comp['item_type'] ?? '') === 'RM') {
            $fillVolume = floatval($bom['fill_volume'] ?? $bom['batch_qty'] ?? 0);
            $divisor = floatval($bom['batch_unit_divisor'] ?? 1000) ?: 1000;
            $base = (($orderQty * $fillVolume) / $divisor) * ($dosage / 100);
        } else {
            $base = $orderQty * $dosage;
        }

        return $base * (1 + $wastage / 100);
    }

    /**
     * Display/meta keys for a BOM against an order quantity.
     * Exposes both legacy keys (batch_qty/batches_needed) and new keys
     * (fill_volume/uom/batch_unit_divisor/bulk_batch).
     */
    private function bomMeta($bom, $orderQty) {
        $fillVolume = floatval($bom['fill_volume'] ?? $bom['batch_qty'] ?? 1) ?: 1;
        $uom = $bom['uom'] ?? $bom['batch_uom'] ?? 'PCS';
        $divisor = floatval($bom['batch_unit_divisor'] ?? 1000) ?: 1000;
        $isLegacy = !empty($bom['is_legacy_formula']);

        return [
            'is_legacy_formula' => $isLegacy,
            'fill_volume' => $fillVolume,
            'uom' => $uom,
            'batch_unit_divisor' => $divisor,
            'batch_qty' => $fillVolume,
            'batch_uom' => $uom,
            'batches_needed' => $isLegacy ? ($orderQty / $fillVolume) : 0,
            'bulk_batch' => $isLegacy ? 0 : (($orderQty * $fillVolume) / $divisor),
        ];
    }

    public function create($data, $items) {
        $pdo = self::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("INSERT INTO manufacturing_orders (
                mo_number, mo_type, mo_site, order_date, due_date, planned_start_date, priority,
                reference_no, mo_status, customer_id, customer_code, customer_name,
                batch_lot_no, po_number, created_by
            ) VALUES (
                :mo_number, :mo_type, :mo_site, :order_date, :due_date, :planned_start_date, :priority,
                :reference_no, :mo_status, :customer_id, :customer_code, :customer_name,
                :batch_lot_no, :po_number, :created_by
            )");

            $stmt->execute([
                'mo_number' => trim($data['mo_number']),
                'mo_type' => $data['mo_type'] ?? 'Standard',
                'mo_site' => $data['mo_site'] ?? '001 - Sterling Technopark',
                'order_date' => $data['order_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'planned_start_date' => $data['planned_start_date'] ?? null,
                'priority' => (int) ($data['priority'] ?? 3),
                'reference_no' => $data['reference_no'] ?? null,
                'mo_status' => $data['mo_status'] ?? 'Planned',
                'customer_id' => $data['customer_id'] ?? null,
                'customer_code' => $data['customer_code'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'batch_lot_no' => $data['batch_lot_no'] ?? null,
                'po_number' => $data['po_number'] ?? null,
                'created_by' => $_SESSION['user_id'] ?? null
            ]);

            $moId = $pdo->lastInsertId();

            if (!empty($items)) {
                $itemStmt = $pdo->prepare("INSERT INTO manufacturing_order_items (
                    mo_id, item_id, item_code, item_description, uom, item_type, site, qty_ordered, so_number, bom_code
                ) VALUES (
                    :mo_id, :item_id, :item_code, :item_description, :uom, :item_type, :site, :qty_ordered, :so_number, :bom_code
                )");

                foreach ($items as $item) {
                    $itemStmt->execute([
                        'mo_id' => $moId,
                        'item_id' => $item['item_id'] ?? null,
                        'item_code' => $item['item_code'] ?? null,
                        'item_description' => $item['item_description'] ?? null,
                        'uom' => $item['uom'] ?? null,
                        'item_type' => $item['item_type'] ?? null,
                        'site' => $item['site'] ?? '001 - Sterling Technopark',
                        'qty_ordered' => (float) ($item['qty_ordered'] ?? 0),
                        'so_number' => $item['so_number'] ?? null,
                        'bom_code' => $item['bom_code'] ?? null,
                    ]);
                }
            }

            $pdo->commit();
            return $moId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function update($moId, $data, $items) {
        $pdo = self::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("UPDATE manufacturing_orders SET
                mo_number = :mo_number, mo_type = :mo_type, mo_site = :mo_site,
                order_date = :order_date, due_date = :due_date, planned_start_date = :planned_start_date,
                priority = :priority, reference_no = :reference_no, mo_status = :mo_status,
                customer_id = :customer_id, customer_code = :customer_code, customer_name = :customer_name,
                batch_lot_no = :batch_lot_no, po_number = :po_number, updated_at = NOW()
                WHERE mo_id = :mo_id");

            $stmt->execute([
                'mo_id' => $moId,
                'mo_number' => trim($data['mo_number']),
                'mo_type' => $data['mo_type'] ?? 'Standard',
                'mo_site' => $data['mo_site'] ?? '001 - Sterling Technopark',
                'order_date' => $data['order_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'planned_start_date' => $data['planned_start_date'] ?? null,
                'priority' => (int) ($data['priority'] ?? 3),
                'reference_no' => $data['reference_no'] ?? null,
                'mo_status' => $data['mo_status'] ?? 'Planned',
                'customer_id' => $data['customer_id'] ?? null,
                'customer_code' => $data['customer_code'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'batch_lot_no' => $data['batch_lot_no'] ?? null,
                'po_number' => $data['po_number'] ?? null,
            ]);

            $delStmt = $pdo->prepare("DELETE FROM manufacturing_order_items WHERE mo_id = :mo_id");
            $delStmt->execute(['mo_id' => $moId]);

            if (!empty($items)) {
                $itemStmt = $pdo->prepare("INSERT INTO manufacturing_order_items (
                    mo_id, item_id, item_code, item_description, uom, item_type, site, qty_ordered, so_number, bom_code
                ) VALUES (
                    :mo_id, :item_id, :item_code, :item_description, :uom, :item_type, :site, :qty_ordered, :so_number, :bom_code
                )");

                foreach ($items as $item) {
                    $itemStmt->execute([
                        'mo_id' => $moId,
                        'item_id' => $item['item_id'] ?? null,
                        'item_code' => $item['item_code'] ?? null,
                        'item_description' => $item['item_description'] ?? null,
                        'uom' => $item['uom'] ?? null,
                        'item_type' => $item['item_type'] ?? null,
                        'site' => $item['site'] ?? '001 - Sterling Technopark',
                        'qty_ordered' => (float) ($item['qty_ordered'] ?? 0),
                        'so_number' => $item['so_number'] ?? null,
                        'bom_code' => $item['bom_code'] ?? null,
                    ]);
                }
            }

            $pdo->commit();
            return $moId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function findById($moId) {
        $sql = "SELECT * FROM manufacturing_orders WHERE mo_id = :mo_id LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['mo_id' => $moId]);
        return $stmt->fetch();
    }

    public function updateStatus($moId, $status) {
        $sql = "UPDATE manufacturing_orders SET mo_status = :status, updated_at = NOW() WHERE mo_id = :mo_id";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute(['status' => $status, 'mo_id' => $moId]);
    }

    public function allocateMaterials($moId) {
        $pdo = self::getConnection();
        $warehouseModel = new \App\Models\WarehouseModel();

        $items = $this->getItemsByMoId($moId);

        foreach ($items as $item) {
            $bomCode = $item['bom_code'] ?? null;
            $qtyOrdered = floatval($item['qty_ordered'] ?? 0);

            if (empty($bomCode) || $qtyOrdered <= 0) continue;

            $bom = $warehouseModel->getBomByCode($bomCode);
            if (!$bom) continue;

            $components = $warehouseModel->getBOMComponentsWithStock([$bom['id']]);

            foreach ($components as $comp) {
                $requiredQty = round($this->computeRequiredQty($qtyOrdered, $bom, $comp), 4);

                if ($requiredQty <= 0) continue;

                $allocStmt = $pdo->prepare("
                    INSERT INTO mrp_allocations (mo_id, moi_id, item_id, allocated_qty, status)
                    VALUES (:mo_id, :moi_id, :item_id, :qty, 'active')
                    ON DUPLICATE KEY UPDATE
                        allocated_qty = allocated_qty + VALUES(allocated_qty),
                        moi_id = VALUES(moi_id),
                        status = 'active',
                        updated_at = NOW()
                ");
                $allocStmt->execute([
                    'mo_id' => $moId,
                    'moi_id' => $item['moi_id'],
                    'item_id' => $comp['component_item_id'],
                    'qty' => $requiredQty,
                ]);

                $invStmt = $pdo->prepare("
                    INSERT INTO inventory_balances (item_id, site_code, qty_on_hand, qty_allocated)
                    VALUES (:item_id, 'MAIN', 0, :qty)
                    ON DUPLICATE KEY UPDATE
                        qty_allocated = qty_allocated + VALUES(qty_allocated),
                        updated_at = NOW()
                ");
                $invStmt->execute([
                    'item_id' => $comp['component_item_id'],
                    'qty' => $requiredQty,
                ]);
            }
        }
    }

    public function deallocateMaterials($moId) {
        $pdo = self::getConnection();

        $stmt = $pdo->prepare("SELECT item_id, allocated_qty FROM mrp_allocations WHERE mo_id = :mo_id AND status = 'active'");
        $stmt->execute(['mo_id' => $moId]);
        $allocations = $stmt->fetchAll();

        foreach ($allocations as $alloc) {
            $invStmt = $pdo->prepare("
                UPDATE inventory_balances
                SET qty_allocated = GREATEST(0, qty_allocated - :qty),
                    updated_at = NOW()
                WHERE item_id = :item_id
            ");
            $invStmt->execute([
                'item_id' => $alloc['item_id'],
                'qty' => $alloc['allocated_qty'],
            ]);
        }

        $cancelStmt = $pdo->prepare("
            UPDATE mrp_allocations SET status = 'cancelled', updated_at = NOW()
            WHERE mo_id = :mo_id AND status = 'active'
        ");
        $cancelStmt->execute(['mo_id' => $moId]);
    }

    public function getMoBomBreakdown($moId) {
        $warehouseModel = new \App\Models\WarehouseModel();
        $items = $this->getItemsByMoId($moId);
        $breakdown = [];

        foreach ($items as $item) {
            $bomCode = $item['bom_code'] ?? null;
            $qtyOrdered = floatval($item['qty_ordered'] ?? 0);

            if (empty($bomCode)) {
                $breakdown[] = [
                    'moi_id' => $item['moi_id'],
                    'item_code' => $item['item_code'] ?? '-',
                    'item_description' => $item['item_description'] ?? '-',
                    'qty_ordered' => $qtyOrdered,
                    'bom_code' => null,
                    'bom_status' => 'no_bom',
                    'components' => [],
                ];
                continue;
            }

            $bom = $warehouseModel->getBomByCode($bomCode);
            if (!$bom) {
                $breakdown[] = [
                    'moi_id' => $item['moi_id'],
                    'item_code' => $item['item_code'] ?? '-',
                    'item_description' => $item['item_description'] ?? '-',
                    'qty_ordered' => $qtyOrdered,
                    'bom_code' => $bomCode,
                    'bom_status' => 'not_found',
                    'components' => [],
                ];
                continue;
            }

            $components = $warehouseModel->getBOMComponentsWithStock([$bom['id']]);

            $meta = $this->bomMeta($bom, $qtyOrdered);

            $componentDetails = [];
            $hasShortage = false;
            foreach ($components as $comp) {
                $dosage = floatval($comp['dosage_rate']);
                $wastage = floatval($comp['wastage_allowance_pct']);
                $requiredQty = $this->computeRequiredQty($qtyOrdered, $bom, $comp);
                $soh = floatval($comp['soh']);
                $allocated = floatval($comp['allocated']);
                $availableStock = floatval($comp['available_stock']);
                $shortage = max(0, $requiredQty - $availableStock);

                if ($shortage > 0) $hasShortage = true;

                $componentDetails[] = [
                    'component_item_id' => $comp['component_item_id'],
                    'item_code' => $comp['item_code'],
                    'item_description' => $comp['item_description'],
                    'item_uom' => $comp['item_uom'],
                    'item_type' => $comp['item_type'] ?? '',
                    'phase_code' => $comp['phase_code'] ?? '101',
                    'dosage_rate' => $dosage,
                    'wastage_pct' => $wastage,
                    'required_qty' => round($requiredQty, 4),
                    'soh' => $soh,
                    'allocated' => round($allocated, 4),
                    'net_available' => round($availableStock, 4),
                    'shortage' => round($shortage, 4),
                    'status' => $shortage > 0 ? 'lacking' : 'available',
                ];
            }

            $breakdown[] = [
                'moi_id' => $item['moi_id'],
                'item_code' => $item['item_code'] ?? '-',
                'item_description' => $item['item_description'] ?? '-',
                'qty_ordered' => $qtyOrdered,
                'bom_code' => $bomCode,
                'bom_status' => 'ok',
                'batch_qty' => $meta['batch_qty'],
                'batches_needed' => round($meta['batches_needed'], 4),
                'fill_volume' => $meta['fill_volume'],
                'uom' => $meta['uom'],
                'batch_unit_divisor' => $meta['batch_unit_divisor'],
                'is_legacy_formula' => $meta['is_legacy_formula'],
                'bulk_batch' => round($meta['bulk_batch'], 4),
                'components' => $componentDetails,
                'has_shortage' => $hasShortage,
            ];
        }

        return $breakdown;
    }

    public function validateMoRelease($moId) {
        $breakdown = $this->getMoBomBreakdown($moId);
        $shortages = [];

        foreach ($breakdown as $item) {
            if ($item['bom_status'] === 'not_found') {
                $shortages[] = [
                    'item_code' => $item['item_code'],
                    'item_description' => $item['item_description'] ?? '',
                    'reason' => 'BOM "' . htmlspecialchars($item['bom_code']) . '" not found in system',
                    'required' => 0,
                    'available' => 0,
                    'short_by' => 0,
                    'uom' => '',
                ];
                continue;
            }
            if ($item['bom_status'] === 'no_bom') {
                continue;
            }
            foreach ($item['components'] as $comp) {
                if ($comp['status'] === 'lacking') {
                    $shortages[] = [
                        'item_code' => $comp['item_code'],
                        'item_description' => $comp['item_description'],
                        'required' => $comp['required_qty'],
                        'available' => $comp['net_available'],
                        'short_by' => $comp['shortage'],
                        'uom' => $comp['item_uom'],
                    ];
                }
            }
        }

        return [
            'valid' => empty($shortages),
            'shortages' => $shortages,
        ];
    }

    public function getBomBreakdownForItem($itemId, $qty) {
        $warehouseModel = new \App\Models\WarehouseModel();
        $qty = floatval($qty);

        $bom = $warehouseModel->hasBOM($itemId);
        if (!$bom) {
            return [
                'bom_code' => null,
                'batch_qty' => 0,
                'batches_needed' => 0,
                'fill_volume' => 0,
                'uom' => '',
                'batch_unit_divisor' => 1000,
                'is_legacy_formula' => 0,
                'bulk_batch' => 0,
                'components' => [],
                'has_shortage' => false,
                'no_bom' => true,
                'not_found' => false,
            ];
        }

        $bomFull = $warehouseModel->getBomByCode($bom['bom_code']);
        if (!$bomFull) {
            return [
                'bom_code' => $bom['bom_code'],
                'batch_qty' => 0,
                'batches_needed' => 0,
                'fill_volume' => 0,
                'uom' => '',
                'batch_unit_divisor' => 1000,
                'is_legacy_formula' => 0,
                'bulk_batch' => 0,
                'components' => [],
                'has_shortage' => false,
                'no_bom' => false,
                'not_found' => true,
            ];
        }

        $components = $warehouseModel->getBOMComponentsWithStock([$bomFull['id']]);
        $meta = $this->bomMeta($bomFull, $qty);

        $componentDetails = [];
        $hasShortage = false;
        foreach ($components as $comp) {
            $dosage = floatval($comp['dosage_rate']);
            $wastage = floatval($comp['wastage_allowance_pct']);
            $requiredQty = $this->computeRequiredQty($qty, $bomFull, $comp);
            $soh = floatval($comp['soh']);
            $allocated = floatval($comp['allocated']);
            $availableStock = floatval($comp['available_stock']);
            $shortage = max(0, $requiredQty - $availableStock);

            if ($shortage > 0) $hasShortage = true;

            $componentDetails[] = [
                'item_code' => $comp['item_code'],
                'item_description' => $comp['item_description'],
                'item_uom' => $comp['item_uom'],
                'item_type' => $comp['item_type'] ?? '',
                'phase_code' => $comp['phase_code'] ?? '101',
                'dosage_rate' => $dosage,
                'wastage_pct' => $wastage,
                'required_qty' => round($requiredQty, 4),
                'soh' => $soh,
                'allocated' => round($allocated, 4),
                'net_available' => round($availableStock, 4),
                'status' => $shortage > 0 ? 'lacking' : 'available',
                'shortage' => round($shortage, 4),
            ];
        }

        return [
            'bom_code' => $bomFull['bom_code'],
            'batch_qty' => $meta['batch_qty'],
            'batches_needed' => round($meta['batches_needed'], 4),
            'fill_volume' => $meta['fill_volume'],
            'uom' => $meta['uom'],
            'batch_unit_divisor' => $meta['batch_unit_divisor'],
            'is_legacy_formula' => $meta['is_legacy_formula'],
            'bulk_batch' => round($meta['bulk_batch'], 4),
            'components' => $componentDetails,
            'has_shortage' => $hasShortage,
            'no_bom' => false,
            'not_found' => false,
        ];
    }
}
