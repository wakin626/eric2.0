<?php
namespace App\Controllers;

use App\Models\WarehouseModel;
use App\Models\BackloadModel;
use App\Models\CatalogModel;
use App\Models\AuditModel;
use App\Helpers\Pagination;
use App\Helpers\NotificationHelper;
use App\Helpers\XlsxExport;

class WarehouseController {
    private $warehouseModel;
    private $backloadModel;
    private $catalogModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?controller=auth&action=login');
            exit;
        }
        $action = $_GET['action'] ?? '';
        $dept = $_SESSION['department'] ?? '';
        $mrpAllowed = in_array($action, ['mrp', 'mrpPDF', 'saveMrpCalculation']) && in_array($dept, ['warehouse', 'rnd', 'admin']);
        $apiActions = ['getPODetails', 'getItemsByCustomer', 'backloadDelivery', 'getDeliveryLotsForBackload',
            'getLotsByPOItem', 'getPOItemsForAssignment', 'getActivePOsForAssignment', 'getLotsForTransfer',
            'viewBackloads', 'getPOsContainingItem', 'getAvailableItemsForDelivery', 'searchItems',
            'mrpRunDetail', 'purchasingPo', 'receivingPo', 'moEntry', 'getCustomerPOs', 'getItemBomInfo'];
        if (!$mrpAllowed && !in_array($action, $apiActions) && $dept !== 'warehouse') {
            header('Location: ?controller=admin');
            exit;
        }
        $this->warehouseModel = new WarehouseModel();
        $this->backloadModel = new BackloadModel();
        $this->catalogModel = new CatalogModel();
    }

    private function redirectToRoleHome(string $action): void {
        $department = $_SESSION['department'] ?? 'warehouse';
        $controller = ($department === 'admin') ? 'admin' : 'warehouse';
        header('Location: ?controller=' . $controller . '&action=' . $action);
        exit;
    }

    private function enforceReadOnlyPurchasingPo(): void {
        if (($_SESSION['department'] ?? '') !== 'warehouse') {
            $_SESSION['error'] = 'Purchasing PO is read-only for this role.';
            $this->redirectToRoleHome('purchasingPo');
        }
    }

    private function enforceWarehouseReceivingAccess(): void {
        if (($_SESSION['department'] ?? '') !== 'warehouse') {
            $_SESSION['error'] = 'Only warehouse users can receive purchasing shipments.';
            $this->redirectToRoleHome('receivingPo');
        }
    }

    public function index() {
        $data['page_title'] = 'Warehouse Dashboard';
        $data['customers'] = $this->catalogModel->getCustomers();
        $data['items'] = $this->catalogModel->getItems();
        $data['purchase_orders'] = $this->warehouseModel->getActivePOsForDashboard(5);
        $poIds = array_column($data['purchase_orders'], 'po_id');
        $data['po_items_map'] = $this->warehouseModel->getPurchaseOrderItemsByPOIds($poIds);

        $data['deliveries'] = $this->warehouseModel->getDeliveries();
        $this->render('dashboard', $data);
    }

    public function moEntry() {
        $data['page_title'] = 'MO Entry';
        $data['customers'] = $this->catalogModel->getCustomers();
        $data['items'] = [];
        $data['allItems'] = $this->catalogModel->getItems();
        $this->render('mo_entry', $data);
    }

    public function getCustomerPOs() {
        header('Content-Type: application/json');
        $customerId = intval($_GET['customer_id'] ?? 0);
        if ($customerId <= 0) {
            echo json_encode([]);
            exit;
        }

        $pos = $this->warehouseModel->getOpenPOsByCustomer($customerId);
        $payload = [];
        foreach ($pos as $po) {
            $poNumber = trim((string) ($po['customer_po_number'] ?? $po['po_number'] ?? ''));
            if ($poNumber === '') {
                $poNumber = 'PO #' . ($po['po_id'] ?? '');
            }
            $payload[] = [
                'po_id' => $po['po_id'] ?? null,
                'po_number' => $poNumber,
                'po_date' => $po['customer_po_date'] ?? null,
                'label' => $poNumber . (isset($po['customer_po_date']) && $po['customer_po_date'] ? ' • ' . date('Y-m-d', strtotime($po['customer_po_date'])) : '')
            ];
        }

        echo json_encode($payload);
        exit;
    }

    public function getItemBomInfo() {
        header('Content-Type: application/json');
        $itemId = intval($_GET['item_id'] ?? 0);
        if ($itemId <= 0) {
            echo json_encode(['bom_code' => '', 'batch_qty' => '', 'batch_uom' => '', 'fill_volume' => '', 'uom' => '', 'batch_unit_divisor' => 1000, 'is_legacy_formula' => 0]);
            exit;
        }

        $bom = $this->warehouseModel->hasBOM($itemId);
        $fillVolume = $bom ? ($bom['fill_volume'] ?? $bom['batch_qty'] ?? '') : '';
        $uom = $bom ? ($bom['uom'] ?? $bom['batch_uom'] ?? '') : '';
        echo json_encode([
            'bom_code' => $bom ? ($bom['bom_code'] ?? '') : '',
            'batch_qty' => $fillVolume,
            'batch_uom' => $uom,
            'fill_volume' => $fillVolume,
            'uom' => $uom,
            'batch_unit_divisor' => $bom ? floatval($bom['batch_unit_divisor'] ?? 1000) : 1000,
            'is_legacy_formula' => $bom ? !empty($bom['is_legacy_formula']) : false,
        ]);
        exit;
    }

    /**
     * Legacy-aware required quantity for one MRP component row.
     * Legacy: (orderQty / batch_qty) * dosage * wastage
     * New:    RM → ((orderQty * fill_volume) / divisor) * dosage% * wastage
     *         PM/SFG → orderQty * dosage * wastage  (direct unit rate, no /100)
     */
    private function computeMrpRequiredQty($orderQty, $poi, $comp) {
        $dosage = floatval($comp['dosage_rate'] ?? 0);
        $wastage = floatval($comp['wastage_allowance_pct'] ?? 0);
        $type = strtoupper($comp['item_type'] ?? $comp['category'] ?? '');

        if (!empty($poi['is_legacy_formula'])) {
            $batchQty = floatval($poi['fill_volume'] ?? $poi['batch_qty'] ?? 1) ?: 1;
            $base = ($orderQty / $batchQty) * $dosage;
        } elseif ($type === 'RM') {
            $fillVolume = floatval($poi['fill_volume'] ?? $poi['batch_qty'] ?? 0);
            $divisor = floatval($poi['batch_unit_divisor'] ?? 1000) ?: 1000;
            $base = (($orderQty * $fillVolume) / $divisor) * ($dosage / 100);
        } else {
            // PM/SFG: direct unit dosage per finished item
            $base = $orderQty * $dosage;
        }

        return $base * (1 + $wastage / 100);
    }

    /**
     * Display/meta keys for an MRP section (legacy + new keys side by side).
     */
    private function mrpBomMeta($poi) {
        $fillVolume = floatval($poi['fill_volume'] ?? $poi['batch_qty'] ?? 1) ?: 1;
        $uom = $poi['uom'] ?? $poi['batch_uom'] ?? 'PCS';
        $divisor = floatval($poi['batch_unit_divisor'] ?? 1000) ?: 1000;
        $isLegacy = !empty($poi['is_legacy_formula']);
        $targetQty = floatval($poi['quantity']);

        return [
            'fill_volume' => $fillVolume,
            'uom' => $uom,
            'batch_unit_divisor' => $divisor,
            'is_legacy_formula' => $isLegacy,
            'batch_qty' => $fillVolume,
            'batch_uom' => $uom,
            'batches_needed' => $isLegacy ? ($targetQty / $fillVolume) : 0,
            'bulk_batch' => $isLegacy ? 0 : (($targetQty * $fillVolume) / $divisor),
        ];
    }

    /**
     * Item-pool MRP required qty (new Customer+FG+Qty flow).
     * RM:    (target * fill_volume / divisor) * (dosage/100) * (1 + wastage/100)
     * PM/SFG: target * dosage * (1 + wastage/100)
     *   — dosage is a direct unit rate per finished item (e.g. 1.0 pc/unit),
     *   NOT a percentage and NOT scaled by fill volume / UOM divisor.
     */
    private function computeMrpPoolRequiredQty($targetQty, $bomMeta, $comp) {
        $dosage = floatval($comp['dosage_rate'] ?? 0);
        $wastage = floatval($comp['wastage_allowance_pct'] ?? 0);
        $type = strtoupper($comp['item_type'] ?? $comp['category'] ?? '');

        if ($type === 'RM') {
            $fillVolume = floatval($bomMeta['fill_volume'] ?? 0);
            $divisor = floatval($bomMeta['batch_unit_divisor'] ?? 1000) ?: 1000;
            $base = (($targetQty * $fillVolume) / $divisor) * ($dosage / 100);
        } else {
            // PM / SFG / FG: direct unit dosage (e.g. 1 pc label per unit)
            $base = $targetQty * $dosage;
        }

        return $base * (1 + $wastage / 100);
    }

    public function purchaseOrders() {
        $search = $_GET['search'] ?? '';
        $filterCustomer = $_GET['filter_customer'] ?? '';
        $filterItem = $_GET['filter_item'] ?? '';
        $filterDate = $_GET['filter_date'] ?? '';
        $filterDeliveryStatus = $_GET['delivery_status'] ?? '';

        $hasFilter = $search || $filterCustomer || $filterItem || $filterDate || $filterDeliveryStatus;
        if ($hasFilter) {
            $filters = [];
            if ($search) $filters['search'] = $search;
            if ($filterCustomer) $filters['customer_name'] = $filterCustomer;
            if ($filterItem) $filters['item_description'] = $filterItem;
            if ($filterDate) $filters['date'] = $filterDate;
            if ($filterDeliveryStatus) $filters['delivery_status'] = $filterDeliveryStatus;
            $allPOs = $this->warehouseModel->getPurchaseOrdersFiltered($filters);
            $allCustomers = array_values(array_unique(array_filter(array_column($allPOs, 'customer_name'))));
            $pagination = ['items' => $allPOs, 'page' => 1, 'perPage' => count($allPOs), 'total' => count($allPOs), 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false];
        } else {
            $allPOs = $this->warehouseModel->getPurchaseOrders();
            $allCustomers = array_values(array_unique(array_filter(array_column($allPOs, 'customer_name'))));
            $pagination = Pagination::paginate($allPOs, 10);
        }

        $poIds = array_column($pagination['items'], 'po_id');
        $data['purchase_orders'] = $pagination['items'];
        $data['po_items_map'] = $this->warehouseModel->getPurchaseOrderItemsByPOIds($poIds);

        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['search'] = $search;
        $data['filterCustomer'] = $filterCustomer;
        $data['filterItem'] = $filterItem;
        $data['filterDate'] = $filterDate;
        $data['filterDeliveryStatus'] = $filterDeliveryStatus;
        $data['allCustomers'] = $allCustomers;
        $data['page_title'] = 'Customer PO';
        $this->render('purchase_orders/index', $data);
    }

    public function createPO() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $customer_id = intval($_POST['customer_id'] ?? 0);
                if ($customer_id <= 0) {
                    throw new \RuntimeException('Please select a valid customer.');
                }

                $items = json_decode($_POST['items_json'] ?? '', true);
                if (!is_array($items) || empty($items)) {
                    throw new \RuntimeException('Please add at least one item to the purchase order.');
                }
                foreach ($items as $item) {
                    if (empty($item['item_id']) || !isset($item['quantity']) || intval($item['quantity']) <= 0) {
                        throw new \RuntimeException('Each item must have a valid item and quantity greater than zero.');
                    }
                }

                $production_type = $_POST['production_type'] ?? 'normal';
                $conn = \App\Core\BaseModel::getConnection();

                $po_id = $this->warehouseModel->createPurchaseOrder([
                    'customer_po_number' => $_POST['customer_po_number'],
                    'customer_po_date' => $_POST['customer_po_date'],
                    'customer_id' => $customer_id,
                    'requested_by' => $_SESSION['user_id'],
                    'customer_terms' => $_POST['customer_terms'] ?? 0,
                    'production_type' => $production_type
                ]);

                $items = json_decode($_POST['items_json'], true);
                foreach ($items as $item) {
                    $poi_id = $this->warehouseModel->createPurchaseOrderItem(
                        $po_id,
                        $item['item_id'],
                        $item['quantity'],
                        $item['unit_price'],
                        $item['uom'] ?? 'PCS',
                        $item['item_code'] ?? null
                    );

                }

                // Recalculate PO-level produced_quantity (once after all items)
                $conn->prepare("UPDATE purchase_orders SET produced_quantity = (
                    SELECT COALESCE(SUM(produced_quantity), 0) FROM purchase_order_items WHERE po_id = :po_id
                ) WHERE po_id = :po_id2")
                    ->execute(['po_id' => $po_id, 'po_id2' => $po_id]);

                $po = $this->warehouseModel->getPurchaseOrderById($po_id);
                $cleanData = [
                    'customer_po_number' => $_POST['customer_po_number'] ?? '',
                    'customer_po_date' => $_POST['customer_po_date'] ?? '',
                    'production_type' => $_POST['production_type'] ?? 'normal',
                ];
                $poLabel = $po['customer_po_number'] ?? $po['po_number'] ?? 'PO #' . $po_id;
                $customerLabel = $po['customer_name'] ?? 'customer #' . $customer_id;
                AuditModel::log($_SESSION['user_id'], 'CREATE', 'warehouse', 'Created purchase order ' . $poLabel . ' for ' . $customerLabel . ' (normal production)', null, $cleanData, 'purchase_order', $po_id);

                NotificationHelper::poCreated($poLabel, $customerLabel, $po_id, $_SESSION['user_id']);

                $_SESSION['success'] = 'Purchase Order ' . $_POST['customer_po_number'] . ' created successfully';
                header('Location: ?controller=warehouse&action=purchaseOrders');
                exit;
            } catch (\Exception $e) {
                error_log('createPO error: ' . $e->getMessage());
                $_SESSION['error'] = 'Failed to create purchase order. Please check your input and try again.';
                header('Location: ?controller=warehouse&action=createPO');
                exit;
            }
        }
        $data['page_title'] = 'Create PO';
        $data['customers'] = $this->catalogModel->getCustomers();
        $data['items'] = $this->catalogModel->getItems();
        $this->render('purchase_orders/create', $data);
    }

    public function editPO() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $po_id = $_POST['po_id'] ?? null;
            if (!$po_id) {
                $_SESSION['error'] = 'Invalid PO';
                header('Location: ?controller=warehouse&action=purchaseOrders');
                exit;
            }

            $conn = \App\Core\BaseModel::getConnection();
            $conn->beginTransaction();
            try {

            // Fetch OLD PO header data BEFORE any updates (for audit log)
            $oldPoStmt = $conn->prepare("SELECT customer_id, customer_po_number, customer_po_date, production_type FROM purchase_orders WHERE po_id = :po_id");
            $oldPoStmt->execute(['po_id' => $po_id]);
            $oldPoData = $oldPoStmt->fetch();

            $customer_id = $oldPoData['customer_id'] ?? 0;
            $production_type = $oldPoData['production_type'] ?? 'normal';

            if (isset($_POST['customer_po_date'])) {
                $stmt = $conn->prepare("UPDATE purchase_orders SET customer_po_number = :po_number, customer_po_date = :date, production_type = :type WHERE po_id = :po_id");
                $stmt->execute([
                    'po_number' => $_POST['customer_po_number'] ?? '',
                    'date' => $_POST['customer_po_date'],
                    'type' => $_POST['production_type'] ?? 'normal',
                    'po_id' => $po_id
                ]);
                $production_type = $_POST['production_type'] ?? $production_type;
            }

            $items = json_decode($_POST['items_json'], true);
            if (!empty($items)) {
                // Fetch current PO items before updating to capture quantity changes
                $oldItems = [];
                $stmt = $conn->prepare("SELECT poi_id, quantity, item_id FROM purchase_order_items WHERE po_id = :po_id");
                $stmt->execute(['po_id' => $po_id]);
                $currentItems = $stmt->fetchAll();
                foreach ($currentItems as $ci) {
                    $oldItems[$ci['poi_id']] = ['quantity' => $ci['quantity'], 'item_id' => $ci['item_id']];
                }

                $itemChanges = [];

                foreach ($items as $item) {
                    if (!empty($item['poi_id'])) {
                        // Fetch current item state before updating
                        $poiStmt = $conn->prepare("SELECT quantity, produced_quantity, item_id FROM purchase_order_items WHERE poi_id = :poi_id");
                        $poiStmt->execute(['poi_id' => $item['poi_id']]);
                        $currentItem = $poiStmt->fetch();

                        // Track quantity changes
                        if (isset($oldItems[$item['poi_id']])) {
                            $oldQty = (int)$oldItems[$item['poi_id']]['quantity'];
                            $newQty = (int)$item['quantity'];
                            if ($oldQty !== $newQty) {
                                $itemChanges[] = [
                                    'action' => 'quantity_changed',
                                    'poi_id' => $item['poi_id'],
                                    'item_id' => $oldItems[$item['poi_id']]['item_id'],
                                    'old_quantity' => $oldQty,
                                    'new_quantity' => $newQty,
                                ];
                            }
                        }

                        $this->warehouseModel->updatePurchaseOrderItem(
                            $item['poi_id'],
                            $item['quantity'],
                            $item['item_id'] ?? null,
                            $item['unit_price'] ?? null,
                            $item['uom'] ?? null
                        );
                    } else {
                        $poi_id = $this->warehouseModel->createPurchaseOrderItem(
                            $po_id,
                            $item['item_id'],
                            $item['quantity'],
                            $item['unit_price'],
                            $item['uom'] ?? 'PCS',
                            $item['item_code'] ?? null
                        );

                    }
                }
            }

            $this->warehouseModel->updatePOTotalQuantity($po_id);

            // Track items added and removed
            $newPoiIds = [];
            foreach ($items as $item) {
                if (!empty($item['poi_id'])) {
                    $newPoiIds[] = $item['poi_id'];
                }
            }
            $oldPoiIds = array_keys($oldItems);
            $addedPoiIds = array_diff($newPoiIds, $oldPoiIds);
            $removedPoiIds = array_diff($oldPoiIds, $newPoiIds);

            foreach ($addedPoiIds as $poiId) {
                $item = $this->warehouseModel->getPurchaseOrderItemById($poiId);
                if ($item) {
                    $itemChanges[] = [
                        'action' => 'item_added',
                        'poi_id' => $poiId,
                        'item_id' => $item['item_id'] ?? 0,
                        'quantity' => $item['quantity'] ?? 0,
                    ];
                }
            }
            foreach ($removedPoiIds as $poiId) {
                $itemChanges[] = [
                    'action' => 'item_removed',
                    'poi_id' => $poiId,
                    'item_id' => $oldItems[$poiId]['item_id'] ?? 0,
                    'old_quantity' => $oldItems[$poiId]['quantity'] ?? 0,
                ];
                // Cascade removal: soft-delete where supported, hard-delete where not
                // production_lots: has is_removed column
                $conn->prepare("UPDATE production_lots SET `is_removed` = 1 WHERE poi_id = :poi_id AND `is_removed` = 0")
                    ->execute(['poi_id' => $poiId]);
                // production_history: has is_removed column
                $conn->prepare("UPDATE production_history SET `is_removed` = 1 WHERE poi_id = :poi_id AND `is_removed` = 0")
                    ->execute(['poi_id' => $poiId]);
                // production_reports: no soft-delete column, hard delete
                $conn->prepare("DELETE FROM production_reports WHERE poi_id = :poi_id")
                    ->execute(['poi_id' => $poiId]);
                // delivery_reports: no soft-delete column, hard delete
                $conn->prepare("DELETE FROM delivery_reports WHERE poi_id = :poi_id")
                    ->execute(['poi_id' => $poiId]);
                // delivery_receipts: soft-delete via joined deliveries
                $conn->prepare("UPDATE delivery_receipts dr 
                    INNER JOIN deliveries d ON dr.delivery_id = d.delivery_id 
                    SET dr.`remove` = 1 
                    WHERE d.poi_id = :poi_id AND dr.`remove` = 0")
                    ->execute(['poi_id' => $poiId]);
                // deliveries: has remove column
                $conn->prepare("UPDATE deliveries SET `remove` = 1 WHERE poi_id = :poi_id AND `remove` = 0")
                    ->execute(['poi_id' => $poiId]);
                // purchase_order_items: no soft-delete column, hard delete (parent record)
                $conn->prepare("DELETE FROM purchase_order_items WHERE poi_id = :poi_id")
                    ->execute(['poi_id' => $poiId]);
            }

            // Recalculate PO-level quantities after deletes
            $conn->prepare("UPDATE purchase_orders SET produced_quantity = (
                SELECT COALESCE(SUM(produced_quantity), 0) FROM purchase_order_items WHERE po_id = :po_id
            ) WHERE po_id = :po_id2")
                ->execute(['po_id' => $po_id, 'po_id2' => $po_id]);
            $conn->prepare("UPDATE purchase_orders SET delivered_quantity = GREATEST(0,
                (SELECT COALESCE(SUM(d.delivery_quantity), 0) FROM deliveries d WHERE d.po_id = :po_id AND d.`remove` = 0)
                - COALESCE((SELECT SUM(b.quantity) FROM backloads b WHERE b.poi_id IN (SELECT poi_id FROM purchase_order_items WHERE po_id = :po_id3) AND b.`remove` = 0), 0)
            ) WHERE po_id = :po_id2")
                ->execute(['po_id' => $po_id, 'po_id2' => $po_id, 'po_id3' => $po_id]);

            $po = $this->warehouseModel->getPurchaseOrderById($po_id);
            $oldValues = [
                'customer_po_number' => $oldPoData['customer_po_number'] ?? '',
                'customer_po_date' => $oldPoData['customer_po_date'] ?? '',
                'production_type' => $oldPoData['production_type'] ?? 'normal',
            ];
            if (!empty($oldItems)) {
                $oldValues['items'] = array_map(function($item) {
                    return [
                        'poi_id' => $item['poi_id'],
                        'item_id' => $item['item_id'],
                        'quantity' => $item['quantity'],
                    ];
                }, array_values($oldItems));
            }
            $newValues = [
                'customer_po_number' => $_POST['customer_po_number'] ?? '',
                'customer_po_date' => $_POST['customer_po_date'] ?? '',
                'production_type' => $_POST['production_type'] ?? 'normal',
            ];
            // Build new items state from POST data (the actual submitted items)
            if (!empty($items)) {
                $newValues['items'] = array_map(function($item) {
                    return [
                        'poi_id' => $item['poi_id'] ?? null,
                        'item_id' => $item['item_id'] ?? null,
                        'quantity' => $item['quantity'] ?? 0,
                    ];
                }, array_values($items));
            }
            $poLabel = $po['customer_po_number'] ?? $po['po_number'] ?? 'PO #' . $po_id;
            AuditModel::log($_SESSION['user_id'], 'UPDATE', 'warehouse', 'Updated purchase order ' . $poLabel . ' with the latest header and item changes', $oldValues, $newValues, 'purchase_order', $po_id);

            $conn->commit();
            $_SESSION['success'] = 'Purchase Order updated successfully';
            header('Location: ?controller=warehouse&action=purchaseOrders');
            exit;

            } catch (\Exception $e) {
                $conn->rollBack();
                $_SESSION['error'] = 'Failed to update PO: ' . $e->getMessage();
                header('Location: ?controller=warehouse&action=purchaseOrders');
                exit;
            }
        }
    }

    public function viewPO() {
        $id = $_GET['id'] ?? null;
        $data['page_title'] = 'PO Details';
        $data['po'] = $this->warehouseModel->getPurchaseOrderById($id);
        $data['po_items'] = $this->warehouseModel->getPurchaseOrderItems($id);

        $this->render('purchase_orders/view', $data);
    }

    public function getItemsByCustomer() {
        header('Content-Type: application/json');
        $customer_id = $_GET['customer_id'] ?? null;
        if (!$customer_id) {
            echo json_encode([]);
            exit;
        }
        $items = $this->catalogModel->getItemsByCustomer($customer_id);
        echo json_encode($items);
        exit;
    }

    public function getPODetails() {
        header('Content-Type: application/json');
        try {
            $id = $_GET['id'] ?? null;
            $po = $this->warehouseModel->getPurchaseOrderById($id);
            $po_items = $this->warehouseModel->getPurchaseOrderItems($id);
        
        // Fetch all active deliveries for this PO and map them to their corresponding items
        $deliveries = $this->warehouseModel->getDeliveriesByPOId($id);
        $dr_map = [];
        $delivery_ids = [];
        foreach ($deliveries as $d) {
            $dr = $d['dr_number'] ?? '';
            $delivery_id = $d['delivery_id'] ?? null;
            if (empty($dr)) continue;
            
            if (!empty($d['poi_id'])) {
                $poi_id = $d['poi_id'];
                $qty = $d['delivery_quantity'] ?? 0;
                if (!isset($dr_map[$poi_id])) {
                    $dr_map[$poi_id] = [];
                }
                $dr_map[$poi_id][] = [
                    'dr_number' => $dr,
                    'qty' => $qty,
                    'delivery_date' => $d['delivery_date'],
                    'lot_number' => $d['lot_number'] ?? null,
                    'delivery_id' => $delivery_id
                ];
                if ($delivery_id) $delivery_ids[] = $delivery_id;
            }
            
            if (!empty($d['lot_items'])) {
                $lotItems = json_decode($d['lot_items'], true);
                if (is_array($lotItems)) {
                    foreach ($lotItems as $li) {
                        $poi_id = $li['poi_id'] ?? null;
                        $qty = $li['qty'] ?? 0;
                        if ($poi_id) {
                            if (!isset($dr_map[$poi_id])) {
                                $dr_map[$poi_id] = [];
                            }
                            $dr_map[$poi_id][] = [
                                'dr_number' => $dr,
                                'qty' => $qty,
                                'delivery_date' => $d['delivery_date'],
                                'lot_number' => $li['lot_number'] ?? null,
                                'delivery_id' => $delivery_id
                            ];
                            if ($delivery_id) $delivery_ids[] = $delivery_id;
                        }
                    }
                }
            }
        }
        
        // Fetch receipts for this PO and map by delivery_id
        $receipts = [];
        if (!empty($id)) {
            $allReceipts = $this->warehouseModel->getReceiptsByPOId($id);
            foreach ($allReceipts as $r) {
                $rid = $r['delivery_id'];
                if (!isset($receipts[$rid])) {
                    $receipts[$rid] = $r;
                }
            }
        }
        
        foreach ($po_items as &$item) {
            $poi_id = $item['poi_id'];
            $item['deliveries'] = $dr_map[$poi_id] ?? [];
            foreach ($item['deliveries'] as &$del) {
                $did = $del['delivery_id'] ?? null;
                $del['receipt'] = ($did && isset($receipts[$did])) ? $receipts[$did] : null;
            }
            unset($del);
            $item['backloaded'] = 0;
        }
        unset($item);

        if (!empty($po_items)) {
            $poiIds = array_column($po_items, 'poi_id');
            $placeholders = implode(',', array_fill(0, count($poiIds), '?'));
            $conn = \App\Core\BaseModel::getConnection();

            $lotConvMap = [];
            $lotPoiMap = [];
            foreach ($deliveries as $d) {
                $lotItems = json_decode($d['lot_items'] ?? '[]', true);
                if (!is_array($lotItems)) continue;
                foreach ($lotItems as $li) {
                    $lid = intval($li['lot_id'] ?? 0);
                    if ($lid && !isset($lotConvMap[$lid])) {
                        $lotConvMap[$lid] = [
                            'conv' => intval($li['actual_uom_conversion'] ?? $li['uom_conversion'] ?? 0),
                            'uom' => $li['item_uom'] ?? ''
                        ];
                        $lotPoiMap[$lid] = intval($li['poi_id'] ?? 0);
                    }
                }
            }

            $lotProdStmt = $conn->prepare("SELECT lot_id, poi_id, quantity_produced FROM production_lots WHERE poi_id IN ($placeholders) AND `is_removed` = 0");
            $lotProdStmt->execute($poiIds);
            $lotProduced = [];
            while ($lr = $lotProdStmt->fetch()) {
                $lid = intval($lr['lot_id']);
                $lotProduced[$lid] = intval($lr['quantity_produced']);
                if (!isset($lotPoiMap[$lid])) $lotPoiMap[$lid] = intval($lr['poi_id']);
            }

            $lotDelivered = [];
            $lotReturned = [];
            foreach ($deliveries as $d) {
                $lotItems = json_decode($d['lot_items'] ?? '[]', true);
                if (!is_array($lotItems)) continue;
                foreach ($lotItems as $li) {
                    $lid = intval($li['lot_id'] ?? 0);
                    if ($lid) {
                        $lotDelivered[$lid] = ($lotDelivered[$lid] ?? 0) + intval($li['qty'] ?? 0);
                        $lotReturned[$lid] = ($lotReturned[$lid] ?? 0) + intval($li['returned_qty'] ?? 0);
                    }
                }
            }

            $blStmt = $conn->prepare("SELECT lot_id, poi_id, quantity FROM backloads WHERE poi_id IN ($placeholders) AND `remove` = 0");
            $blStmt->execute($poiIds);

            $lotBackloaded = [];
            while ($bl = $blStmt->fetch()) {
                $lid = intval($bl['lot_id']);
                $lotBackloaded[$lid] = ($lotBackloaded[$lid] ?? 0) + intval($bl['quantity']);
            }

            $blCsMap = [];
            $balanceMap = [];
            foreach ($poiIds as $pid) {
                $totalBackloaded = 0;
                $totalActive = 0;
                foreach ($lotBackloaded as $lid => $blQty) {
                    if (($lotPoiMap[$lid] ?? null) != $pid) continue;
                    $totalBackloaded += $blQty;
                    $produced = $lotProduced[$lid] ?? 0;
                    $delivered = $lotDelivered[$lid] ?? 0;
                    $consumed = max(0, $delivered - $produced);
                    $active = max(0, $blQty - $consumed);
                    $totalActive += $active;
                }
                $convInfo = null;
                foreach ($lotBackloaded as $lid => $blQty) {
                    if (($lotPoiMap[$lid] ?? null) == $pid) {
                        $convInfo = $lotConvMap[$lid] ?? null;
                        break;
                    }
                }
                if ($convInfo && $convInfo['conv'] > 0 && $convInfo['uom'] !== 'CS') {
                    $blCsMap[$pid] = ($blCsMap[$pid] ?? 0) + floor($totalBackloaded / $convInfo['conv']);
                    $balanceMap[$pid] = ($balanceMap[$pid] ?? 0) + floor($totalActive / $convInfo['conv']);
                } else {
                    $blCsMap[$pid] = ($blCsMap[$pid] ?? 0) + $totalBackloaded;
                    $balanceMap[$pid] = ($balanceMap[$pid] ?? 0) + $totalActive;
                }
            }
            foreach ($po_items as &$item) {
                $item['backloaded'] = $blCsMap[$item['poi_id']] ?? 0;
                $item['backload_balance'] = $balanceMap[$item['poi_id']] ?? 0;
            }
            unset($item);
        }
        
        echo json_encode(['po' => $po, 'po_items' => $po_items]);
        exit;
        } catch (\Exception $e) {
            error_log('getPODetails error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to load PO details']);
            exit;
        }
    }

    public function getLotsByPOItem() {
        header('Content-Type: application/json');
        $poiId = $_GET['poi_id'] ?? null;
        if (!$poiId) {
            echo json_encode([]);
            exit;
        }
        $conn = \App\Core\BaseModel::getConnection();
        $stmt = $conn->prepare("SELECT po_id FROM purchase_order_items WHERE poi_id = ?");
        $stmt->execute([$poiId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $poId = $row ? $row['po_id'] : null;
        $lots = $this->warehouseModel->getLotsByPOItem($poiId, $poId);
        echo json_encode($lots);
        exit;
    }

    public function deliveries() {
        $search = $_GET['search'] ?? '';
        $filterCustomer = $_GET['filter_customer'] ?? '';
        $filterItem = $_GET['filter_item'] ?? '';

        $hasFilter = $search || $filterCustomer || $filterItem;
        if ($hasFilter) {
            $filters = [];
            if ($search) $filters['search'] = $search;
            if ($filterCustomer) $filters['customer_name'] = $filterCustomer;
            if ($filterItem) $filters['item_description'] = $filterItem;
            $allDeliveries = $this->warehouseModel->getDeliveriesFiltered($filters);
            $allCustomers = array_values(array_unique(array_filter(array_column($allDeliveries, 'customer_name'))));
            $pagination = ['items' => $allDeliveries, 'page' => 1, 'perPage' => count($allDeliveries), 'total' => count($allDeliveries), 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false];
        } else {
            $allDeliveries = $this->warehouseModel->getDeliveries();
            $allCustomers = array_values(array_unique(array_filter(array_column($allDeliveries, 'customer_name'))));
            $pagination = Pagination::paginate($allDeliveries, 10);
        }

        $data['deliveries'] = $pagination['items'];
        $deliveryIds = array_column($pagination['items'], 'delivery_id');
        $receiptsMap = [];
        $backloadsMap = [];
        if (!empty($deliveryIds)) {
            $conn = $this->warehouseModel::getConnection();
            $placeholders = implode(',', array_fill(0, count($deliveryIds), '?'));
            $stmt = $conn->prepare("SELECT * FROM delivery_receipts WHERE delivery_id IN ($placeholders) AND `remove` = 0 ORDER BY date_created ASC");
            $stmt->execute($deliveryIds);
            foreach ($stmt->fetchAll() as $r) {
                $receiptsMap[$r['delivery_id']][] = $r;
            }

            $blStmt = $conn->prepare("SELECT * FROM backloads WHERE delivery_id IN ($placeholders) AND `remove` = 0 ORDER BY date_created ASC");
            $blStmt->execute($deliveryIds);
            foreach ($blStmt->fetchAll() as $bl) {
                $backloadsMap[$bl['delivery_id']][] = $bl;
            }
        }
        $data['receipts_map'] = $receiptsMap;
        $data['backloads_map'] = $backloadsMap;

        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['search'] = $search;
        $data['filterCustomer'] = $filterCustomer;
        $data['filterItem'] = $filterItem;
        $data['allCustomers'] = $allCustomers;
        $data['purchase_orders'] = array_values(array_filter($this->warehouseModel->getPurchaseOrders(), function($po) {
            return ($po['production_type'] ?? 'normal') === 'normal';
        }));
        $data['page_title'] = 'Deliveries';
        $this->render('deliveries/index', $data);
    }

    public function viewBackloads() {
        $search = $_GET['search'] ?? '';
        $filterCustomer = $_GET['filter_customer'] ?? '';

        $filters = [];
        if ($search) $filters['search'] = $search;
        if ($filterCustomer) $filters['customer_id'] = $filterCustomer;

        $allBackloads = $this->backloadModel->getBackloads($filters);
        $pagination = Pagination::paginate($allBackloads, 15);

        $customers = $this->catalogModel->getCustomers();

        $data['backloads'] = $pagination['items'];
        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['search'] = $search;
        $data['filterCustomer'] = $filterCustomer;
        $data['customers'] = $customers;
        $data['page_title'] = 'Backloads';
        $this->render('deliveries/backloads', $data);
    }

    public function readyToDeliver() {
        $search = $_GET['search'] ?? '';
        if ($search) {
            $allPOs = $this->warehouseModel->getPOsReadyToDeliverFiltered(['search' => $search]);
        } else {
            $allPOs = $this->warehouseModel->getPOsReadyToDeliver();
        }
        $pagination = Pagination::paginate($allPOs, 10);
        $data['purchase_orders'] = $pagination['items'];
        $poIds = array_column($pagination['items'], 'po_id');
        $data['po_items_map'] = $this->warehouseModel->getPurchaseOrderItemsByPOIds($poIds);

        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['search'] = $search;
        $data['page_title'] = 'Ready to Deliver';
        $this->render('purchase_orders/ready_to_deliver', $data);
    }

    public function deleteDRPhoto() {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            $receiptId = $_POST['receipt_id'] ?? null;
            if (!$receiptId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing receipt_id']);
                exit;
            }
            $receipt = $this->warehouseModel->getReceiptById($receiptId);
            $this->warehouseModel->deleteDRPhoto($receiptId);
            AuditModel::log($_SESSION['user_id'], 'DELETE', 'warehouse', 'Deleted DR photo for receipt #' . $receiptId . ' from the delivery record', ['path' => $receipt['file_path'] ?? ''], null, 'delivery_photo', $receiptId);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('deleteDRPhoto error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete photo']);
        }
        exit;
    }

    public function getDeliveryLotsForBackload() {
        header('Content-Type: application/json');
        $delivery_id = $_GET['delivery_id'] ?? null;
        if (!$delivery_id) { echo json_encode([]); exit; }

        $lots = $this->backloadModel->getDeliveryLotsForBackload($delivery_id);
        echo json_encode($lots);
        exit;
    }

    public function backloadDelivery() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=warehouse&action=deliveries');
            exit;
        }
        try {
            $delivery_id = $_POST['delivery_id'] ?? null;
            if (!$delivery_id) {
                $_SESSION['error'] = 'Invalid delivery';
                header('Location: ?controller=warehouse&action=deliveries');
                exit;
            }

            $conn = \App\Core\BaseModel::getConnection();
            $stmt = $conn->prepare("SELECT d.delivery_id, d.po_id, d.dr_number, po.customer_po_number
                FROM deliveries d
                INNER JOIN purchase_orders po ON d.po_id = po.po_id
                WHERE d.delivery_id = :delivery_id AND d.`remove` = 0");
            $stmt->execute(['delivery_id' => $delivery_id]);
            $delivery = $stmt->fetch();
            if (!$delivery) {
                $_SESSION['error'] = 'Delivery not found';
                header('Location: ?controller=warehouse&action=deliveries');
                exit;
            }

            $lotIds = $_POST['lot_id'] ?? [];
            $quantities = $_POST['backload_qty'] ?? [];
            $casesArr = $_POST['backload_cases'] ?? [];
            $reasons = $_POST['backload_reason'] ?? [];

            $totalBackloaded = 0;
            foreach ($lotIds as $idx => $lotId) {
                $qty = intval($quantities[$idx] ?? 0);
                if ($qty <= 0 || empty($reasons[$idx])) continue;

                $lotId = intval($lotId);
                $lotStmt = $conn->prepare("SELECT poi_id, lot_number FROM production_lots WHERE lot_id = :lot_id");
                $lotStmt->execute(['lot_id' => $lotId]);
                $lot = $lotStmt->fetch();
                if (!$lot) continue;

                $poiId = $lot['poi_id'];

                $this->backloadModel->createBackload([
                    'delivery_id' => $delivery_id,
                    'po_id' => $delivery['po_id'],
                    'poi_id' => $poiId,
                    'lot_id' => $lotId,
                    'lot_number' => $lot['lot_number'],
                    'quantity' => $qty,
                    'cases' => intval($casesArr[$idx] ?? 0) ?: null,
                    'reason' => $reasons[$idx],
                    'backloaded_by' => $_SESSION['user_id'],
                    'backload_date' => date('Y-m-d')
                ]);
                $totalBackloaded += $qty;
            }

            AuditModel::log($_SESSION['user_id'], 'UPDATE', 'warehouse', 'Backloaded ' . $totalBackloaded . ' units from delivery #' . $delivery_id, null, ['quantity' => $totalBackloaded], 'delivery', $delivery_id);

            NotificationHelper::backloadCreated($delivery['dr_number'], $delivery['customer_po_number'], $totalBackloaded, $_SESSION['user_id']);

            $_SESSION['success'] = 'Backload recorded successfully (' . $totalBackloaded . ' units)';
            header('Location: ?controller=warehouse&action=deliveries');
            exit;
        } catch (\Exception $e) {
            error_log('backloadDelivery error: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to create backload: ' . $e->getMessage();
            header('Location: ?controller=warehouse&action=deliveries');
            exit;
        }
    }

    public function createMultipleDelivery() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=warehouse&action=deliveries');
            exit;
        }
        try {
            $po_id = !empty($_POST['po_id']) ? intval($_POST['po_id']) : null;
            $dr_number = trim($_POST['dr_number'] ?? '');
            $plate_number = trim($_POST['plate_number'] ?? '');
            $vehicle_type = trim($_POST['vehicle_type'] ?? '');
            $logistic_provider = trim($_POST['logistic_provider'] ?? '');
            $lotIdsRaw = $_POST['lot_ids'] ?? '';
            $delivery_date = $_POST['delivery_date'] ?? date('Y-m-d');
            $remarks = $_POST['remarks'] ?? '';
            $isOverShipment = 0;
            if (empty($dr_number) || empty($lotIdsRaw) || empty($plate_number) || empty($vehicle_type) || empty($logistic_provider)) {
                $_SESSION['error'] = 'Missing required fields for delivery.';
                header('Location: ?controller=warehouse&action=deliveries');
                exit;
            }
            $drCheck = $this->warehouseModel->checkDRNumber($dr_number);
            if ($drCheck['exists']) {
                $_SESSION['error'] = 'DR number "' . htmlspecialchars($dr_number) . '" already exists. Please use a unique DR number.';
                header('Location: ?controller=warehouse&action=deliveries');
                exit;
            }
            $pairs = explode(',', $lotIdsRaw);
            $lotItems = [];
            $totalQty = 0;
            $firstPoiId = null;
            $assignedPoiIds = [];
            foreach ($pairs as $pair) {
                $parts = explode(':', $pair);
                if (count($parts) < 2) continue;
                $lotId = intval($parts[0]);
                $deliveryQty = intval($parts[1]);
                $returnedQty = (count($parts) >= 3) ? intval($parts[2]) : 0;
                $actualConversion = (count($parts) >= 4 && !empty($parts[3]) && is_numeric($parts[3])) ? intval($parts[3]) : null;
                if ($lotId <= 0 || $deliveryQty <= 0) continue;
                if ($returnedQty < 0) $returnedQty = 0;
                if ($returnedQty > $deliveryQty) $returnedQty = $deliveryQty;
                $lot = $this->warehouseModel->getLotById($lotId);
                if (!$lot) continue;
                $poiId = $lot['poi_id'] ?? null;
                if ($po_id && !empty($lot['item_id'])) {
                    $conn = \App\Core\BaseModel::getConnection();
                    $poiSt = $conn->prepare("SELECT poi_id FROM purchase_order_items WHERE po_id = ? AND item_id = ? LIMIT 1");
                    $poiSt->execute([$po_id, $lot['item_id']]);
                    $resolvedPoiId = $poiSt->fetchColumn();
                    if (!$resolvedPoiId) {
                        $codeSt = $conn->prepare("SELECT item_code FROM items WHERE item_id = ?");
                        $codeSt->execute([$lot['item_id']]);
                        $itemCode = $codeSt->fetchColumn();
                        if ($itemCode) {
                            $poiSt2 = $conn->prepare("SELECT poi.poi_id FROM purchase_order_items poi JOIN items i ON poi.item_id = i.item_id WHERE poi.po_id = ? AND i.item_code = ? LIMIT 1");
                            $poiSt2->execute([$po_id, $itemCode]);
                            $resolvedPoiId = $poiSt2->fetchColumn();
                        }
                    }
                    if ($resolvedPoiId) {
                        $poiId = intval($resolvedPoiId);
                    }
                }
                if (!$firstPoiId) $firstPoiId = $poiId;
                if ($poiId) $assignedPoiIds[] = $poiId;

                if ($poiId) {
                    $siblings = $this->warehouseModel->getLotsByLotNumber($lot['lot_number'], $poiId);
                } else {
                    $siblings = [$lot];
                }

                if (count($siblings) > 1) {
                    $totalRemaining = 0;
                    $remMap = [];
                    foreach ($siblings as $sib) {
                        $rem = $this->warehouseModel->getLotRemaining($sib['lot_id']);
                        $remMap[$sib['lot_id']] = $rem;
                        $totalRemaining += $rem;
                    }
                    if ($deliveryQty > $totalRemaining) $deliveryQty = $totalRemaining;
                    if ($returnedQty > $deliveryQty) $returnedQty = $deliveryQty;
                    $toSplit = $deliveryQty;
                    $toSplitRet = $returnedQty;
                    $siblingCount = count($siblings);
                    foreach ($siblings as $idx => $sib) {
                        $sibId = $sib['lot_id'];
                        if ($idx === $siblingCount - 1) {
                            $sibQty = $toSplit;
                            $sibRet = $toSplitRet;
                        } else {
                            $sibQty = ($totalRemaining > 0) ? round($deliveryQty * $remMap[$sibId] / $totalRemaining) : 0;
                            $sibRet = ($deliveryQty > 0) ? round($returnedQty * $sibQty / $deliveryQty) : 0;
                            $toSplit -= $sibQty;
                            $toSplitRet -= $sibRet;
                        }
                        if ($sibQty > 0) {
                            $item = $poiId ? $this->catalogModel->getItemByPoiId($poiId) : $this->catalogModel->getItemById($lot['item_id'] ?? null);
                            $lotItems[] = [
                                'lot_id' => $sibId,
                                'poi_id' => $poiId,
                                'lot_number' => $sib['lot_number'] ?? '',
                                'item_code' => $item['item_code'] ?? '',
                                'item_description' => $item['item_description'] ?? '',
                                'qty' => $sibQty,
                                'returned_qty' => $sibRet,
                                'item_uom' => $item['item_uom'] ?? '',
                                'uom_conversion' => $item['uom_conversion'] ?? null,
                                'actual_uom_conversion' => $actualConversion,
                            ];
                            $totalQty += $sibQty;
                        }
                    }
                } else {
                    $remaining = $this->warehouseModel->getLotRemaining($lotId);
                    if ($deliveryQty > $remaining) $deliveryQty = $remaining;
                    if ($returnedQty > $deliveryQty) $returnedQty = $deliveryQty;
                    if ($deliveryQty <= 0) continue;
                    $item = $poiId ? $this->catalogModel->getItemByPoiId($poiId) : $this->catalogModel->getItemById($lot['item_id'] ?? null);
                    $lotItems[] = [
                        'lot_id' => $lotId,
                        'poi_id' => $poiId,
                        'lot_number' => $lot['lot_number'] ?? '',
                        'item_code' => $item['item_code'] ?? '',
                        'item_description' => $item['item_description'] ?? '',
                        'qty' => $deliveryQty,
                        'returned_qty' => $returnedQty,
                        'item_uom' => $item['item_uom'] ?? '',
                        'uom_conversion' => $item['uom_conversion'] ?? null,
                        'actual_uom_conversion' => $actualConversion,
                    ];
                    $totalQty += $deliveryQty;
                }
            }

            if ($po_id) {
                $perPoiDelivery = [];
                foreach ($lotItems as $li) {
                    $poiId = $li['poi_id'] ?? null;
                    if (!$poiId) continue;
                    $perPoiDelivery[$poiId] = ($perPoiDelivery[$poiId] ?? 0) + intval($li['qty']);
                }
                $conn = \App\Core\BaseModel::getConnection();
                foreach ($perPoiDelivery as $poiId => $requestedQty) {
                    $poiStmt = $conn->prepare("SELECT poi.quantity, poi.delivered_quantity
                            FROM purchase_order_items poi WHERE poi.poi_id = ?");
                    $poiStmt->execute([$poiId]);
                    $poiData = $poiStmt->fetch();
                    if ($poiData) {
                        $remaining = intval($poiData['quantity']) - intval($poiData['delivered_quantity']);
                        if ($requestedQty > $remaining) {
                            $_SESSION['error'] = "Delivery blocked: Requested quantity ({$requestedQty}) exceeds open PO balance ({$remaining}) for PO item #{$poiId}.";
                            header('Location: ?controller=warehouse&action=deliveries');
                            exit;
                        }
                    }
                }
            }

            if (empty($lotItems)) {
                $_SESSION['error'] = 'No valid lots selected for delivery.';
                header('Location: ?controller=warehouse&action=deliveries');
                exit;
            }
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
            $deliveryId = $this->warehouseModel->createDelivery([
                'po_id' => $po_id,
                'poi_id' => $firstPoiId,
                'delivered_by' => $_SESSION['user_id'],
                'delivery_date' => $delivery_date,
                'delivery_quantity' => $totalQty,
                'dr_number' => $dr_number,
                'plate_number' => $plate_number,
                'vehicle_type' => $vehicle_type,
                'logistic_provider' => $logistic_provider,
                'lot_items' => json_encode($lotItems),
                'remarks' => $remarks,
                'is_over_shipment' => $isOverShipment
            ]);

            $uniquePoiIds = array_unique(array_filter($assignedPoiIds));
            $oldPoiIdsToRecalc = [];

            foreach ($lotItems as $li) {
                $lotId = $li['lot_id'] ?? null;
                $poiId = $li['poi_id'] ?? null;
                if (!$lotId || !$poiId) continue;
                $lot = $this->warehouseModel->getLotById($lotId);
                if ($lot) {
                    $oldLotPoiId = $lot['poi_id'] ? intval($lot['poi_id']) : null;
                    $newPoiId = intval($poiId);
                    if ($oldLotPoiId && $oldLotPoiId !== $newPoiId) {
                        $oldPoiIdsToRecalc[] = $oldLotPoiId;
                    }
                }
            }

            $allPoiIdsToRecalc = array_unique(array_merge($uniquePoiIds, $oldPoiIdsToRecalc));
            foreach ($allPoiIdsToRecalc as $recalcPoiId) {
                $this->warehouseModel->recalculateProducedQuantityFromDelivery($recalcPoiId);
            }

            $deliveryLabel = 'Delivery';
            if ($po_id) {
                $poDel = $this->warehouseModel->getPurchaseOrderById($po_id);
                $deliveryLabel = $poDel['customer_po_number'] ?? $poDel['po_number'] ?? 'PO #' . $po_id;
            }
            AuditModel::log($_SESSION['user_id'], 'CREATE', 'warehouse', 'Created delivery records for ' . $deliveryLabel . ($dr_number ? ' with DR ' . $dr_number : ''), null, ['lot_ids' => $_POST['lot_ids'] ?? []], 'delivery', null);

            NotificationHelper::deliveryCreated($deliveryLabel, $dr_number, $totalQty, $_SESSION['user_id']);
            NotificationHelper::siNumberNeeded($deliveryId, $deliveryLabel, $_SESSION['user_id']);

            $_SESSION['success'] = "Delivery recorded successfully for DR {$dr_number}.";
            header('Location: ?controller=warehouse&action=deliveries');
            exit;
        } catch (\Exception $e) {
            error_log('createMultipleDelivery error: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to create delivery';
            header('Location: ?controller=warehouse&action=deliveries');
            exit;
        }
    }

    public function updateDRNumber() {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            $delivery_id = $_POST['delivery_id'] ?? null;
            $dr_number = trim($_POST['dr_number'] ?? '');
            if (!$delivery_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Delivery ID is required']);
                exit;
            }
            $this->warehouseModel->updateDRNumber($delivery_id, $dr_number);
            AuditModel::log($_SESSION['user_id'], 'UPDATE', 'warehouse', 'Updated DR number to ' . $dr_number . ' for delivery #' . $delivery_id, null, ['dr_number' => $dr_number], 'delivery', $delivery_id);
            echo json_encode(['success' => true, 'dr_number' => $dr_number]);
        } catch (\Exception $e) {
            error_log('updateDRNumber error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update DR number']);
        }
        exit;
    }

    public function getAvailableItemsForDelivery() {
        header('Content-Type: application/json');
        try {
            $po_id = !empty($_GET['po_id']) ? intval($_GET['po_id']) : null;
            $items = $this->warehouseModel->getAllAvailableItemsForDelivery($po_id);
            echo json_encode($items);
        } catch (\Exception $e) {
            error_log('getAvailableItemsForDelivery error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to load items']);
        }
        exit;
    }

    public function getPOsContainingItem() {
        header('Content-Type: application/json');
        try {
            $item_id = $_GET['item_id'] ?? null;
            if (!$item_id) {
                echo json_encode([]);
                exit;
            }
            $pos = $this->warehouseModel->getPOsContainingItem($item_id);
            echo json_encode($pos);
        } catch (\Exception $e) {
            error_log('getPOsContainingItem error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to load POs']);
        }
        exit;
    }

    public function getLotsForPrint() {
        header('Content-Type: application/json');
        try {
            $po_id = $_GET['po_id'] ?? null;
            if (!$po_id) {
                echo json_encode([]);
                exit;
            }
            $lots = $this->warehouseModel->getLotsByPOForPrint($po_id);
            echo json_encode($lots);
        } catch (\Exception $e) {
            error_log('getLotsForPrint error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to load lots']);
        }
        exit;
    }

    public function checkDRNumber() {
        header('Content-Type: application/json');
        try {
            $dr_number = $_GET['dr_number'] ?? '';
            if (empty($dr_number)) {
                echo json_encode(['exists' => false]);
                exit;
            }
            $result = $this->warehouseModel->checkDRNumber($dr_number);
            echo json_encode($result);
        } catch (\Exception $e) {
            error_log('checkDRNumber error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to check DR number']);
        }
        exit;
    }

    public function reportDelivery() {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            $deliveryId = $_POST['delivery_id'] ?? null;
            $remarks = trim($_POST['remarks'] ?? '');
            $reportType = $_POST['report_type'] ?? 'dr_number';
            $lotId = $_POST['lot_id'] ?? null ? intval($_POST['lot_id']) : null;
            $poiId = $_POST['poi_id'] ?? null ? intval($_POST['poi_id']) : null;
            $poId = $_POST['po_id'] ?? null ? intval($_POST['po_id']) : null;
            $oldQuantity = $_POST['old_quantity'] ?? null ? intval($_POST['old_quantity']) : null;

            if (!$deliveryId || empty($remarks)) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing delivery_id or remarks']);
                exit;
            }

            $this->warehouseModel->reportDelivery($deliveryId, $remarks);

            if ($poId) {
                $this->warehouseModel->createDeliveryReport(
                    $deliveryId, $poiId, $poId, $lotId, $oldQuantity,
                    $_SESSION['user_id'], $remarks, $reportType
                );
            }

            AuditModel::log($_SESSION['user_id'], 'CREATE', 'warehouse', 'Reported delivery issue for delivery #' . $deliveryId . ' (' . $reportType . ')', null, $_POST, 'delivery_report', $deliveryId);

            $deliveryRecord = $this->warehouseModel->getDeliveryById($deliveryId);
            $drLabel = $deliveryRecord['dr_number'] ?? ('#' . $deliveryId);
            NotificationHelper::deliveryReported($deliveryId, $drLabel, $remarks, $_SESSION['user_id']);

            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('reportDelivery error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to submit report']);
        }
        exit;
    }

    public function uploadDRPhoto() {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }

            $deliveryId = $_POST['delivery_id'] ?? null;
            $poId = $_POST['po_id'] ?? null;

            if (!$deliveryId || !$poId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing delivery_id or po_id']);
                exit;
            }

            if (!isset($_FILES['dr_photo']) || $_FILES['dr_photo']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['error' => 'Please select a file to upload']);
                exit;
            }

            $file = $_FILES['dr_photo'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];

            if (!in_array($file['type'], $allowedTypes)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP, PDF']);
                exit;
            }

            $maxSize = 10 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                http_response_code(400);
                echo json_encode(['error' => 'File size must be less than 10MB']);
                exit;
            }

            $uploadDir = __DIR__ . '/../../uploads/receipts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'dr_photo_' . $deliveryId . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to upload file']);
                exit;
            }

            $this->warehouseModel->attachDRPhoto([
                'delivery_id' => $deliveryId,
                'po_id' => $poId,
                'file_name' => $file['name'],
                'file_path' => 'uploads/receipts/' . $fileName,
                'file_type' => $file['type'],
                'file_size' => $file['size'],
                'uploaded_by' => $_SESSION['user_id']
            ]);

            AuditModel::log($_SESSION['user_id'], 'CREATE', 'warehouse', 'Uploaded DR file for delivery #' . $deliveryId . ' as ' . $fileName, null, ['file' => $fileName], 'delivery_photo', $deliveryId);

            echo json_encode(['success' => true, 'file_path' => 'uploads/receipts/' . $fileName]);
        } catch (\Exception $e) {
            error_log('uploadDRPhoto error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to upload DR photo']);
        }
        exit;
    }

    public function printDR() {
        $data['purchase_orders'] = $this->warehouseModel->getPurchaseOrders();
        $data['page_title'] = 'Print Delivery Receipt';
        $selectedPoId = $_GET['po_id'] ?? null;
        $dr_number = $_GET['dr_number'] ?? '';
        $data['dr_number'] = $dr_number;
        $data['selected_po_id'] = $selectedPoId;
        $data['existing_lot_ids'] = [];
        $data['lots_by_item'] = [];

        if ($selectedPoId) {
            $data['lots_by_item'] = $this->warehouseModel->getLotsByPOForPrint($selectedPoId);
        }

        if (!empty($dr_number) && $selectedPoId) {
            $data['existing_lot_ids'] = $this->warehouseModel->getLotsByDRNumber($dr_number);
        }

        $this->render('deliveries/print_dr', $data);
    }

    public function printDRPreview() {
        $po_id = $_GET['po_id'] ?? null;
        $dr_number = $_GET['dr_number'] ?? '';
        if (!$dr_number) {
            header('Location: ?controller=warehouse&action=printDR');
            exit;
        }
        $dr_deliveries = $this->warehouseModel->getDeliveriesByDRNumber($dr_number);
        if (empty($dr_deliveries)) {
            echo "<div class='container mt-5'><div class='alert alert-danger'>Error: DR number \"" . htmlspecialchars($dr_number) . "\" not found.</div><a href='?controller=warehouse&action=deliveries' class='btn btn-secondary'>Back</a></div>";
            exit;
        }
        if (!$po_id && !empty($dr_deliveries[0]['po_id'])) {
            $po_id = $dr_deliveries[0]['po_id'];
        }
        $data['po'] = $po_id ? $this->warehouseModel->getPurchaseOrderById($po_id) : null;
        $data['dr_deliveries'] = $dr_deliveries;
        $data['dr_number'] = $dr_number;
        extract($data);
        include __DIR__ . "/../views/deliveries/print_dr_preview.php";
        exit;
    }

    public function saveDRNumberForLots() {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            $lotIds = $_POST['lot_ids'] ?? '';
            $dr_number = trim($_POST['dr_number'] ?? '');
            $po_id = $_POST['po_id'] ?? null;
            if (empty($lotIds) || empty($dr_number) || empty($po_id)) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing parameters']);
                exit;
            }
            $lotIdArray = array_map('intval', explode(',', $lotIds));
            $lotIdArray = array_filter($lotIdArray);
            $lotItems = [];
            $totalQty = 0;
            $firstPoiId = null;
            foreach ($lotIdArray as $lotId) {
                $lot = $this->warehouseModel->getLotById($lotId);
                if (!$lot) continue;
                $remaining = $this->warehouseModel->getLotRemaining($lotId);
                if ($remaining <= 0) continue;
                $poiId = $lot['poi_id'] ?? null;
                $item = $this->catalogModel->getItemByPoiId($poiId);
                $lotItems[] = [
                    'lot_id' => $lotId,
                    'poi_id' => $poiId,
                    'lot_number' => $lot['lot_number'] ?? '',
                    'item_code' => $item['item_code'] ?? '',
                    'item_description' => $item['item_description'] ?? '',
                    'qty' => $remaining,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'item_uom' => $item['item_uom'] ?? '',
                    'uom_conversion' => $item['uom_conversion'] ?? null,
                    'actual_uom_conversion' => $lot['pcs_per_case'] ?? $item['uom_conversion'] ?? null,
                    'item_id' => $item['item_id'] ?? null,
                ];
                $totalQty += $remaining;
                if (!$firstPoiId) $firstPoiId = $poiId;
            }
            if (empty($lotItems)) {
                http_response_code(400);
                echo json_encode(['error' => 'No available lots found']);
                exit;
            }
            // Group lot_items by lot_number to merge same lots
            $groupedLotItems = [];
            foreach ($lotItems as $li) {
                $key = $li['lot_number'] ?? uniqid();
                if (!isset($groupedLotItems[$key])) {
                    $groupedLotItems[$key] = $li;
                    $groupedLotItems[$key]['qty'] = 0;
                }
                $groupedLotItems[$key]['qty'] += intval($li['qty'] ?? 0);
            }
            $lotItems = array_values($groupedLotItems);

            $conn = \App\Core\BaseModel::getConnection();
            $perPoiDelivery = [];
            foreach ($lotItems as $li) {
                $poiId = $li['poi_id'] ?? null;
                if (!$poiId) continue;
                $perPoiDelivery[$poiId] = ($perPoiDelivery[$poiId] ?? 0) + intval($li['qty']);
            }
            foreach ($perPoiDelivery as $poiId => $requestedQty) {
                $poiStmt = $conn->prepare("SELECT poi.quantity, poi.delivered_quantity
                        FROM purchase_order_items poi WHERE poi.poi_id = ?");
                $poiStmt->execute([$poiId]);
                $poiData = $poiStmt->fetch();
                if ($poiData) {
                    $remaining = intval($poiData['quantity']) - intval($poiData['delivered_quantity']);
                    if ($requestedQty > $remaining) {
                        http_response_code(400);
                        echo json_encode(['error' => "Delivery blocked: Requested quantity ({$requestedQty}) exceeds open PO balance ({$remaining}) for PO item #{$poiId}."]);
                        exit;
                    }
                }
            }

            $this->warehouseModel->createDelivery([
                'po_id' => $po_id,
                'poi_id' => $firstPoiId,
                'delivered_by' => $_SESSION['user_id'],
                'delivery_date' => date('Y-m-d'),
                'delivery_quantity' => $totalQty,
                'dr_number' => $dr_number,
                'lot_items' => json_encode($lotItems),
                'remarks' => ''
            ]);
            AuditModel::log($_SESSION['user_id'], 'UPDATE', 'warehouse', 'Saved DR number ' . $dr_number . ' for ' . count($lotIdArray) . ' lot(s) on PO #' . $po_id, null, ['dr_number' => $dr_number, 'lot_ids' => $lotIdArray], 'delivery', null);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('saveDRNumberForLots error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save DR number']);
        }
        exit;
    }

    public function activityLogs() {
        $auditModel = new \App\Models\AuditModel();
        $filters = [
            'department' => $_SESSION['department'] ?? 'warehouse',
            'user_id'    => $_GET['user_id'] ?? '',
            'module'     => $_GET['module'] ?? '',
            'log_action' => $_GET['log_action'] ?? '',
            'date_from'  => $_GET['date_from'] ?? '',
            'date_to'    => $_GET['date_to'] ?? '',
            'search'     => $_GET['search'] ?? '',
        ];
        foreach ($filters as $k => $v) { if ($v === '') unset($filters[$k]); }
        $logs = $auditModel->getLogs($filters, $_GET['page'] ?? 1, 20);
        $data['logs'] = $logs;
        $data['users'] = $auditModel->getAllUsers();
        $data['filters'] = $_GET;
        $data['logController'] = 'warehouse';
        $data['departmentLocked'] = true;
        $data['hideDeptColumn'] = true;
        $data['stats'] = [
            'today_count' => \App\Models\AuditModel::getLogStats('warehouse')['today_count'] ?? 0,
            'by_department' => [],
        ];
        $data['page_title'] = 'Activity Logs';
        $this->render('activity_logs/index', $data);
    }

    public function reports() {
        $customerId = $_GET['customer_id'] ?? null;
        $weekOffset = max(0, intval($_GET['week_offset'] ?? 0));

        $weeklyStats = $this->warehouseModel->getWeeklyDeliveryStats($customerId, $weekOffset);
        $customers = $this->warehouseModel->getCustomersWithDeliveries();

        $allStats = $this->warehouseModel->getWeeklyDeliveryStats(null, $weekOffset);
        $startYearWeek = !empty($allStats) ? intval($allStats[0]['year_week']) : 0;

        $weeklyDetails = [];
        foreach ($weeklyStats as $ws) {
            $yearWeek = $ws['year_week'];
            $weeklyDetails[$yearWeek] = $this->warehouseModel->getDeliveryDetailsForWeek($yearWeek, $customerId);
        }

        $poItemSummary = $this->warehouseModel->getPoItemSummary();

        $lotItems = $this->warehouseModel->getUniqueItemsForLots();
        $selectedLotItem = $_GET['lot_item_id'] ?? null;
        if ($selectedLotItem) {
            $lotData = $this->warehouseModel->getLotsByItem($selectedLotItem);
        } else {
            $lotData = $this->warehouseModel->getAllLotsStockOnHand();
        }

        $data = [
            'weeklyStats' => $weeklyStats,
            'customers' => $customers,
            'selectedCustomer' => $customerId,
            'weeklyDetails' => $weeklyDetails,
            'weekOffset' => $weekOffset,
            'startYearWeek' => $startYearWeek,
            'hasMoreWeeks' => count(array_filter($weeklyStats, fn($s) => intval($s['delivery_count']) > 0)) >= 12,
            'poItemSummary' => $poItemSummary,
            'lotItems' => $lotItems,
            'selectedLotItem' => $selectedLotItem,
            'lotData' => $lotData,
            'page_title' => 'Warehouse Reports'
        ];
        $this->render('reports/index', $data);
    }

    public function deliveryReport() {
        $filters = [
            'search'      => $_GET['search'] ?? '',
            'date_from'   => $_GET['date_from'] ?? '',
            'date_to'     => $_GET['date_to'] ?? '',
            'customer_id' => $_GET['customer_id'] ?? '',
        ];
        $deliveries = $this->warehouseModel->getDeliveryReportData($filters);
        $customers = $this->warehouseModel->getDeliveryReportCustomers();

        $data = [
            'deliveries'      => $deliveries,
            'customers'       => $customers,
            'filters'         => $filters,
            'page_title'      => 'Delivery Report'
        ];
        $this->render('reports/delivery_report', $data);
    }

    public function exportDeliveryReport() {
        $filters = [
            'search'      => $_GET['search'] ?? '',
            'date_from'   => $_GET['date_from'] ?? '',
            'date_to'     => $_GET['date_to'] ?? '',
            'customer_id' => $_GET['customer_id'] ?? '',
        ];
        $deliveries = $this->warehouseModel->getDeliveryReportData($filters);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="delivery_report_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, ['#', 'DR Number', 'SI Number', 'Customer', 'PO Number', 'Item Code', 'Item', 'Lot Number', 'Quantity', 'Cases', 'Plate No.', 'Vehicle', 'Logistic Provider', 'Type', 'Delivery Date', 'Delivered By', 'Remarks']);
        $rowNum = 0;
        foreach ($deliveries as $d) {
            $lotItems = json_decode($d['lot_items'] ?? '[]', true);
            if (!is_array($lotItems) || count($lotItems) === 0) {
                $lotItems = [[
                    'item_description' => $d['item_description'] ?? 'Unknown',
                    'lot_number' => $d['lot_number'] ?? '—',
                    'qty' => $d['delivery_quantity'] ?? 0,
                    'actual_uom_conversion' => $d['actual_uom_conversion'] ?? null,
                    'uom_conversion' => $d['uom_conversion'] ?? null,
                    'item_uom' => $d['item_uom'] ?? ''
                ]];
            }
            foreach ($lotItems as $li) {
                $rowNum++;
                $qty = intval($li['qty'] ?? 0);
                $conv = $li['actual_uom_conversion'] ?? $li['uom_conversion'] ?? null;
                $uom = $li['item_uom'] ?? '';
                $cases = ($conv && $uom !== 'CS') ? floor($qty / $conv) . ' CS' : '';
                $remarks = $d['report_remarks'] ?? $d['remarks'] ?? '';
                fputcsv($output, [
                    $rowNum,
                    $d['dr_number'] ?? '',
                    $d['si_number'] ?? '',
                    $d['customer_name'] ?? '',
                    $d['customer_po_number'] ?? '',
                    $li['item_code'] ?? '',
                    $li['item_description'] ?? '',
                    $li['lot_number'] ?? '',
                    $qty,
                    $cases,
                    $d['plate_number'] ?? '',
                    $d['vehicle_type'] ?? '',
                    $d['logistic_provider'] ?? '',
                    $d['production_type'] ?? '',
                    $d['delivery_date'] ?? '',
                    $d['delivered_by_name'] ?? '',
                    $remarks
                ]);
            }
        }

        fclose($output);
        exit;
    }

    public function exportReports() {
        $customerId = $_GET['customer_id'] ?? null;
        $weekOffset = max(0, intval($_GET['week_offset'] ?? 0));

        $allStats = $this->warehouseModel->getWeeklyDeliveryStats($customerId, $weekOffset);

        $poItemSummary = $this->warehouseModel->getPoItemSummary();

        $lotItems = $this->warehouseModel->getUniqueItemsForLots();
        $selectedLotItem = $_GET['lot_item_id'] ?? null;
        $allLotData = [];
        if ($selectedLotItem) {
            $allLotData = $this->warehouseModel->getLotsByItem($selectedLotItem);
        } else {
            foreach ($lotItems as $li) {
                $lots = $this->warehouseModel->getLotsByItem($li['item_id']);
                $allLotData = array_merge($allLotData, $lots);
            }
        }

        $filterSearch = $_GET['search'] ?? '';
        $filterPo = $_GET['po_filter'] ?? '';
        $filterItem = $_GET['item_filter'] ?? '';
        $filterStatus = $_GET['status_filter'] ?? '';

        $filteredItems = $poItemSummary;
        if ($filterSearch !== '') {
            $searchLower = strtolower($filterSearch);
            $filteredItems = array_filter($filteredItems, fn($i) =>
                str_contains(strtolower($i['customer_name'] ?? ''), $searchLower)
                || str_contains(strtolower($i['customer_po_number'] ?? ''), $searchLower)
                || str_contains(strtolower($i['item_description'] ?? ''), $searchLower)
            );
        }
        if ($filterPo !== '') {
            $filteredItems = array_filter($filteredItems, fn($i) => strtolower($i['customer_po_number']) === $filterPo);
        }
        if ($filterItem !== '') {
            $filteredItems = array_filter($filteredItems, fn($i) => strtolower($i['item_description']) === $filterItem);
        }
        if ($filterStatus !== '') {
            $filteredItems = array_filter($filteredItems, function($i) use ($filterStatus) {
                $p = intval($i['produced_quantity']);
                $d = intval($i['delivered_quantity']);
                $q = intval($i['po_qty']);
                if ($filterStatus === 'completed') return $d >= $q;
                if ($filterStatus === 'in-progress') return $p > 0 && ($p < $q || $d < $q);
                if ($filterStatus === 'pending') return $p === 0;
                return true;
            });
        }
        $filteredItems = array_values($filteredItems);

        $xlsx = new XlsxExport();

        $weekFrom = ($weekOffset * 12) + 1;

        $xlsx->addSheet('Weekly Delivery Graph');
        $xlsx->addRow(['WEEKLY DELIVERY GRAPH'], 1);
        $xlsx->addMerge('A', 1, 'E', 1);
        $weekHeaderRow = $xlsx->addRow(['Week', 'Year', 'Date Range', 'Deliveries', 'Cases'], 2);
        $xlsx->setAutoFilter('A', $weekHeaderRow, 'E', $weekHeaderRow);
        $weekNum = $weekFrom;
        foreach ($allStats as $ws) {
            $yw = $ws['year_week'];
            $year = intval(floor($yw / 100));
            $week = intval($yw % 100);
            $jan4 = mktime(0, 0, 0, 1, 4, $year);
            $dayOffset = ($week - 1) * 7 - date('w', $jan4) + 1;
            $mon = date('M d', mktime(0, 0, 0, 1, 4 + $dayOffset, $year));
            $sun = date('M d', mktime(0, 0, 0, 1, 4 + $dayOffset + 6, $year));
            $xlsx->addRow([$weekNum, $year, $mon . ' - ' . $sun, intval($ws['delivery_count']), intval($ws['total_cases'])]);
            $weekNum++;
        }
        $xlsx->autoFitColumns();

        $xlsx->addSheet('PO Item Summary');
        $xlsx->addRow(['PO ITEM SUMMARY'], 1);
        $xlsx->addMerge('A', 1, 'I', 1);
        $poHeaderRow = $xlsx->addRow(['Customer', 'PO Number', 'Item Code', 'Item', 'PO Qty', 'Produced', 'Delivered', 'Balance', 'Status'], 2);
        $xlsx->setAutoFilter('A', $poHeaderRow, 'I', $poHeaderRow);
        foreach ($filteredItems as $item) {
            $conv = intval($item['uom_conversion'] ?? 0);
            $ordered = intval($item['po_qty']);
            $produced = intval($item['produced_quantity']);
            $delivered = intval($item['delivered_quantity']);
            $balance = $ordered - $delivered;
            if ($conv > 0) {
                $ordered = floor($ordered / $conv);
                $produced = floor($produced / $conv);
                $delivered = floor($delivered / $conv);
                $balance = floor($balance / $conv);
            }
            if ($delivered >= $ordered) {
                $status = 'Completed';
            } elseif ($produced > 0) {
                $status = 'In Progress';
            } else {
                $status = 'Pending';
            }
            $xlsx->addRow([
                $item['customer_name'] ?? '',
                $item['customer_po_number'] ?? '',
                $item['item_code'] ?? '',
                $item['item_description'] ?? '',
                $ordered, $produced, $delivered, $balance, $status
            ]);
        }
        $xlsx->autoFitColumns();

        $xlsx->addSheet('Stock on Hand');
        $xlsx->addRow(['STOCK ON HAND'], 1);
        $xlsx->addMerge('A', 1, 'G', 1);
        $sohHeaderRow = $xlsx->addRow(['Customer', 'PO Number', 'Lot Number', 'Stock on Hand (cs)', 'Delivered (cs)', 'Expiration Date', 'Created By'], 2);
        $xlsx->setAutoFilter('A', $sohHeaderRow, 'G', $sohHeaderRow);
        foreach ($allLotData as $lot) {
            $stockOnHandPcs = max(0, $lot['quantity_produced'] - $lot['quantity_delivered']);
            $conv = intval($lot['uom_conversion'] ?? 0);
            $stockCs = $conv > 0 ? floor($stockOnHandPcs / $conv) : $stockOnHandPcs;
            $deliveredCs = $conv > 0 ? floor($lot['quantity_delivered'] / $conv) : $lot['quantity_delivered'];
            $stockLabel = $conv > 0 ? $stockCs . ' cs' : $stockOnHandPcs . ' pcs';
            $deliveredLabel = $conv > 0 ? $deliveredCs . ' cs' : $lot['quantity_delivered'] . ' pcs';
            $expiry = $lot['lot_date'] ? date('M Y', strtotime($lot['lot_date'] . ' +3 years')) : '-';
            $xlsx->addRow([
                $lot['customer_name'] ?? '',
                $lot['customer_po_number'] ?? '',
                $lot['lot_number'] ?? '',
                $stockLabel,
                $deliveredLabel,
                $expiry,
                $lot['created_by_name'] ?? '-'
            ]);
        }
        $xlsx->autoFitColumns();

        $xlsx->download('warehouse_reports_' . date('Y-m-d') . '.xlsx');
    }

    public function getPOItemsForAssignment() {
        header('Content-Type: application/json');
        $po_id = $_GET['po_id'] ?? null;
        if (!$po_id) {
            echo json_encode([]);
            exit;
        }
        $items = $this->warehouseModel->getPurchaseOrderItems($po_id);
        echo json_encode($items);
        exit;
    }

    public function getLotsForTransfer() {
        header('Content-Type: application/json');
        $poi_id = $_GET['poi_id'] ?? null;
        if (!$poi_id) {
            echo json_encode([]);
            exit;
        }
        $result = $this->warehouseModel->getAvailableLotsForTransfer($poi_id);
        echo json_encode($result);
        exit;
    }

    public function getActivePOsForAssignment() {
        header('Content-Type: application/json');
        $pos = $this->warehouseModel->getAllActivePOs();
        echo json_encode($pos);
        exit;
    }

    // ─── MRP Sheet ────────────────────────────────────────────────────────────

    public function mrp() {
        $customers = $this->warehouseModel->getActiveCustomersForMrp();
        $fgOptions = [];
        $fgHeader = null;
        $mrpSection = null;
        $customerId = isset($_GET['customer_id']) && $_GET['customer_id'] !== '' ? intval($_GET['customer_id']) : null;
        $fgItemId = isset($_GET['fg_item_id']) && $_GET['fg_item_id'] !== '' ? intval($_GET['fg_item_id']) : null;
        $targetQty = isset($_GET['target_qty']) && $_GET['target_qty'] !== '' ? floatval($_GET['target_qty']) : null;
        $calculate = !empty($_GET['calculate']);
        $noFgsForCustomer = false;

        // FG dropdown: filtered by customer when selected; else all FGs with active BOMs
        $fgOptions = $this->warehouseModel->getFgsWithBomByCustomer($customerId);
        if (!empty($customerId) && empty($fgOptions)) {
            $noFgsForCustomer = true;
        }

        if ($calculate && $fgItemId && $targetQty !== null && $targetQty > 0) {
            $bom = $this->warehouseModel->getBomForFg($fgItemId);
            if ($bom) {
                $components = $this->warehouseModel->getBOMComponentsWithStock([$bom['bom_id']]);
                $meta = [
                    'fill_volume' => floatval($bom['fill_volume'] ?? 0),
                    'uom' => $bom['uom'] ?? '',
                    'batch_unit_divisor' => floatval($bom['batch_unit_divisor'] ?? 1000) ?: 1000,
                    'is_legacy_formula' => !empty($bom['is_legacy_formula']),
                ];
                $rows = [];
                foreach ($components as $comp) {
                    $required = $this->computeMrpPoolRequiredQty($targetQty, $meta, $comp);
                    $soh = floatval($comp['soh']);
                    $allocated = floatval($comp['allocated']);
                    $available = $soh - $allocated;
                    $lacking = $required - $available;
                    if ($lacking <= 0) {
                        $lacking = 0;
                    }
                    $rows[] = [
                        'component_item_id' => $comp['component_item_id'],
                        'item_code' => $comp['item_code'],
                        'item_description' => $comp['item_description'],
                        'item_uom' => $comp['item_uom'],
                        'item_type' => $comp['item_type'] ?? '',
                        'category' => $comp['item_type'] ?? '-',
                        'phase_code' => $comp['phase_code'] ?? '101',
                        'required_qty' => $required,
                        'soh' => $soh,
                        'allocated' => $allocated,
                        'available' => $available,
                        'lacking_qty' => $lacking,
                        'dosage_rate' => floatval($comp['dosage_rate'] ?? 0),
                        'wastage_allowance_pct' => floatval($comp['wastage_allowance_pct'] ?? 0),
                    ];
                }
                $fgHeader = [
                    'fg_item_id' => $bom['fg_item_id'],
                    'fg_code' => $bom['fg_code'],
                    'fg_name' => $bom['fg_name'],
                    'item_uom' => $bom['item_uom'],
                    'bom_code' => $bom['bom_code'],
                    'fill_volume' => $meta['fill_volume'],
                    'uom' => $meta['uom'],
                    'batch_unit_divisor' => $meta['batch_unit_divisor'],
                    'is_legacy_formula' => $meta['is_legacy_formula'],
                    'target_qty' => $targetQty,
                ];
                $mrpSection = [
                    'fg_item_id' => $bom['fg_item_id'],
                    'fg_code' => $bom['fg_code'],
                    'fg_name' => $bom['fg_name'],
                    'target_qty' => $targetQty,
                    'item_uom' => $bom['item_uom'],
                    'fill_volume' => $meta['fill_volume'],
                    'uom' => $meta['uom'],
                    'batch_unit_divisor' => $meta['batch_unit_divisor'],
                    'is_legacy_formula' => $meta['is_legacy_formula'],
                    'components' => $rows,
                ];
            }
        }

        $this->render('mrp/preview', [
            'customers' => $customers,
            'fgOptions' => $fgOptions,
            'fgHeader' => $fgHeader,
            'mrpSection' => $mrpSection,
            'selectedCustomer' => $customerId,
            'selectedFg' => $fgItemId,
            'targetQty' => $targetQty,
            'didCalculate' => $calculate,
            'noFgsForCustomer' => $noFgsForCustomer,
            // legacy/PO keys kept for header actions that still reference them
            'openPOs' => [],
            'poHeader' => null,
            'consolidated' => [],
            'selectedPO' => null,
            'hasExistingSnapshot' => false,
        ]);
    }

    /**
     * Queue lacking materials from an MRP calculation as Purchase Requests
     * (supplier_orders.status = 'requested') so Procurement can process them
     * directly from the Purchasing PO screen.
     */
    public function saveMrpCalculation() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=warehouse&action=mrp');
            exit;
        }

        $customerId = intval($_POST['customer_id'] ?? 0);
        $fgItemId = intval($_POST['fg_item_id'] ?? 0);
        $targetQty = floatval($_POST['target_qty'] ?? 0);
        $postedLacking = json_decode($_POST['lacking_json'] ?? '[]', true);

        try {
            if ($fgItemId <= 0 || $targetQty <= 0) {
                throw new \RuntimeException('Select a finished good and enter a target quantity first.');
            }
            if (!is_array($postedLacking) || empty($postedLacking)) {
                throw new \RuntimeException('No lacking items to transfer.');
            }

            $bom = $this->warehouseModel->getBomForFg($fgItemId);
            if (!$bom) {
                throw new \RuntimeException('No active BOM found for this finished good.');
            }

            $components = $this->warehouseModel->getBOMComponentsWithStock([$bom['bom_id']]);
            $meta = [
                'fill_volume' => floatval($bom['fill_volume'] ?? 0),
                'uom' => $bom['uom'] ?? '',
                'batch_unit_divisor' => floatval($bom['batch_unit_divisor'] ?? 1000) ?: 1000,
                'is_legacy_formula' => !empty($bom['is_legacy_formula']),
            ];

            // Recompute server-side: the posted payload only marks intent,
            // quantities are authoritative from here (same math as Calculate).
            $recomputed = [];
            foreach ($components as $comp) {
                $required = $this->computeMrpPoolRequiredQty($targetQty, $meta, $comp);
                $available = floatval($comp['soh']) - floatval($comp['allocated']);
                $lacking = $required - $available;
                if ($lacking > 0) {
                    $recomputed[intval($comp['component_item_id'])] = $lacking;
                }
            }

            $postedIds = [];
            foreach ($postedLacking as $row) {
                $id = intval($row['component_item_id'] ?? 0);
                if ($id > 0) {
                    $postedIds[$id] = true;
                }
            }
            $targets = array_intersect_key($recomputed, $postedIds);
            if (empty($targets)) {
                throw new \RuntimeException('Nothing lacking for this calculation.');
            }

            $existing = $this->warehouseModel->getExistingRequestedOrders(array_keys($targets));
            $queued = 0;
            $skipped = 0;
            foreach ($targets as $itemId => $qty) {
                if (isset($existing[$itemId])) {
                    $skipped++;
                    continue;
                }
                $this->warehouseModel->createSupplierOrder([
                    'supplier_name' => 'Pending Selection',
                    'item_id' => $itemId,
                    'quantity' => $qty,
                    'unit_cost' => 0,
                    'order_date' => date('Y-m-d'),
                    'expected_date' => null,
                    'remarks' => 'Auto-generated from MRP calculation (' . ($bom['fg_code'] ?? '')
                        . ', target ' . $targetQty . ')',
                    'created_by' => $_SESSION['user_id'],
                    'status' => 'requested',
                    'po_id' => null,
                ]);
                $queued++;
            }

            $_SESSION['success'] = "{$queued} purchase request(s) queued for Procurement"
                . ($skipped ? " ({$skipped} skipped: already requested)" : '') . '.';
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $params = ['controller=warehouse', 'action=mrp'];
        if ($customerId > 0) $params[] = 'customer_id=' . $customerId;
        if ($fgItemId > 0) $params[] = 'fg_item_id=' . $fgItemId;
        if ($targetQty > 0) $params[] = 'target_qty=' . rawurlencode($targetQty);
        $params[] = 'calculate=1';
        header('Location: ?' . implode('&', $params));
        exit;
    }

    public function mrpPDF() {
        $customerId = $_GET['customer_id'] ?? null;
        $poId = $_GET['po_id'] ?? null;
        if (!$poId) {
            header('Location: ?controller=warehouse&action=mrp');
            exit;
        }

        $poHeader = $this->warehouseModel->getPurchaseOrderById($poId);
        $allPoItems = $this->warehouseModel->getPOItemsWithBOM($poId);

        $bomIds = [];
        $poiIds = [];
        foreach ($allPoItems as $poi) {
            if (!empty($poi['bom_id'])) $bomIds[] = $poi['bom_id'];
            $poiIds[] = $poi['poi_id'];
        }

        $allComponents = $this->warehouseModel->getBOMComponentsWithStock($bomIds);
        $componentsByBom = [];
        $allIngredientIds = [];
        foreach ($allComponents as $comp) {
            $componentsByBom[$comp['bom_id']][] = $comp;
            $allIngredientIds[$comp['component_item_id']] = true;
        }

        $pendingAllocations = $this->warehouseModel->getPendingAllocationsAcrossPOs(
            $poiIds,
            array_keys($allIngredientIds)
        );

        $pendingSupplierOrders = $this->warehouseModel->getPendingSupplierOrdersForItems(array_keys($allIngredientIds), $poId);

        $mrpSections = [];
        foreach ($allPoItems as $poi) {
            if (empty($poi['bom_id'])) continue;
            $meta = $this->mrpBomMeta($poi);
            $components = $componentsByBom[$poi['bom_id']] ?? [];
            $rows = [];
            foreach ($components as $comp) {
                $totalReqt = $this->computeMrpRequiredQty(floatval($poi['quantity']), $poi, $comp);
                $soh = floatval($comp['soh']);
                $allocated = floatval($pendingAllocations[$comp['component_item_id']] ?? 0);
                $supplierPending = floatval($pendingSupplierOrders[$comp['component_item_id']] ?? 0);
                $available = $soh - $allocated;
                $excess = $available - $totalReqt + $supplierPending;

                if ($totalReqt == 0) $remarks = 'NO NEED';
                elseif ($available <= 0 && $supplierPending == 0) $remarks = 'NO stock for next order mfg';
                elseif ($excess < 0) $remarks = 'LACKING';
                else $remarks = 'OK';

                $rows[] = [
                    'component_item_id' => $comp['component_item_id'],
                    'item_code' => $comp['item_code'],
                    'item_description' => $comp['item_description'],
                    'item_uom' => $comp['item_uom'],
                    'phase_code' => $comp['phase_code'] ?? '101',
                    'total_reqt' => $totalReqt,
                    'soh' => $soh,
                    'allocated' => $allocated,
                    'pending' => $allocated + $supplierPending,
                    'supplier_pending' => $supplierPending,
                    'excess' => $excess,
                    'remarks' => $remarks,
                ];
            }
            $mrpSections[] = [
                'fg_code' => $poi['item_code'],
                'fg_name' => $poi['item_description'],
                'target_qty' => $poi['quantity'],
                'item_uom' => $poi['item_uom'],
                'batch_qty' => $meta['batch_qty'],
                'batch_uom' => $meta['batch_uom'],
                'batches_needed' => $meta['batches_needed'],
                'fill_volume' => $meta['fill_volume'],
                'uom' => $meta['uom'],
                'batch_unit_divisor' => $meta['batch_unit_divisor'],
                'is_legacy_formula' => $meta['is_legacy_formula'],
                'bulk_batch' => $meta['bulk_batch'],
                'components' => $rows,
            ];
        }

        $consolidatedMap = [];
        foreach ($mrpSections as $section) {
            foreach ($section['components'] as $row) {
                $key = $row['item_code'];
                if (!isset($consolidatedMap[$key])) {
                    $consolidatedMap[$key] = $row;
                } else {
                    $consolidatedMap[$key]['total_reqt'] += $row['total_reqt'];
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

        $data = [
            'po' => $poHeader,
            'mrpSections' => $mrpSections,
            'consolidated' => $consolidated,
            'userName' => $_SESSION['full_name'] ?? '',
            'userDept' => $_SESSION['department'] ?? '',
        ];
        extract($data);

        ob_start();
        include __DIR__ . "/../views/mrp/pdf_template.php";
        $html = ob_get_clean();

        require_once __DIR__ . '/../../public/vendor/dompdf/autoload.inc.php';
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $poNumber = $poHeader['customer_po_number'] ?? 'PO';
        $dompdf->stream("MRP_{$poNumber}.pdf", ['Attachment' => false]);
        exit;
    }

    // ─── Purchasing PO (renamed from Procurement PO) ────────────────────────────

    public function purchasingPo() {
        $filters = [
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];
        $orders = $this->warehouseModel->getPurchasingPoFiltered($filters);
        $data['page_title'] = 'Purchasing PO';
        $data['orders'] = $orders;
        $data['filters'] = $filters;
        $data['readOnly'] = (($_SESSION['department'] ?? '') !== 'warehouse');
        $this->render('purchasingPo/index', $data);
    }

    public function createPurchasingPo() {
        $this->enforceReadOnlyPurchasingPo();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=warehouse&action=purchasingPo');
            exit;
        }
        try {
            $supplierName = trim($_POST['supplier_name'] ?? '');
            $itemId = intval($_POST['item_id'] ?? 0);
            $quantity = floatval($_POST['quantity'] ?? 0);
            $unitCost = floatval($_POST['unit_cost'] ?? 0);
            $orderDate = $_POST['order_date'] ?: null;
            $expectedDate = $_POST['expected_date'] ?: null;
            $remarks = trim($_POST['remarks'] ?? '') ?: null;

            if (empty($supplierName) || $itemId <= 0 || $quantity <= 0) {
                throw new \RuntimeException('Supplier name, item, and quantity are required.');
            }

            $this->warehouseModel->createPurchasingPo([
                'supplier_name' => $supplierName,
                'item_id' => $itemId,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'order_date' => $orderDate,
                'expected_date' => $expectedDate,
                'remarks' => $remarks,
                'created_by' => $_SESSION['user_id']
            ]);

            $_SESSION['success'] = 'Purchasing PO created successfully.';
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        header('Location: ?controller=warehouse&action=purchasingPo');
        exit;
    }

    public function processPurchasingPo() {
        $this->enforceReadOnlyPurchasingPo();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=warehouse&action=purchasingPo');
            exit;
        }
        try {
            $id = intval($_POST['supplier_order_id'] ?? 0);
            if ($id <= 0) throw new \RuntimeException('Invalid order.');

            $supplierName = trim($_POST['supplier_name'] ?? '');
            $quantity = floatval($_POST['quantity'] ?? 0);
            if (empty($supplierName) || $quantity <= 0) {
                throw new \RuntimeException('Supplier name and quantity are required.');
            }

            $this->warehouseModel->processPurchasingPo($id, [
                'supplier_name' => $supplierName,
                'quantity' => $quantity,
                'unit_cost' => floatval($_POST['unit_cost'] ?? 0),
                'order_date' => $_POST['order_date'] ?: null,
                'expected_date' => $_POST['expected_date'] ?: null,
                'remarks' => trim($_POST['remarks'] ?? '') ?: null,
                'po_id' => !empty($_POST['po_id']) ? intval($_POST['po_id']) : null,
            ]);

            $_SESSION['success'] = 'Purchasing PO processed successfully.';
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        header('Location: ?controller=warehouse&action=purchasingPo');
        exit;
    }

    public function batchProcessPurchasingPo() {
        $this->enforceReadOnlyPurchasingPo();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=warehouse&action=purchasingPo');
            exit;
        }
        try {
            $orderIds = array_filter(array_map('intval', explode(',', $_POST['order_ids'] ?? '')));
            if (empty($orderIds)) {
                throw new \RuntimeException('No orders selected.');
            }

            $supplierName = trim($_POST['supplier_name'] ?? '');
            if (empty($supplierName)) {
                throw new \RuntimeException('Supplier name is required.');
            }

            $this->warehouseModel->batchProcessPurchasingPo($orderIds, [
                'supplier_name' => $supplierName,
                'unit_cost' => floatval($_POST['unit_cost'] ?? 0),
                'order_date' => $_POST['order_date'] ?: null,
                'expected_date' => $_POST['expected_date'] ?: null,
            ]);

            $_SESSION['success'] = count($orderIds) . ' purchasing PO(s) processed successfully.';
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        header('Location: ?controller=warehouse&action=purchasingPo');
        exit;
    }

    public function cancelPurchasingPo() {
        $this->enforceReadOnlyPurchasingPo();
        $id = intval($_GET['id'] ?? 0);
        try {
            if ($id <= 0) {
                throw new \RuntimeException('Invalid purchasing PO.');
            }
            $this->warehouseModel->cancelPurchasingPo($id);
            $_SESSION['success'] = 'Purchasing PO cancelled. Any pending QC inspection entries were cleared from the queue.';
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        header('Location: ?controller=warehouse&action=purchasingPo');
        exit;
    }

    public function deletePurchasingPo() {
        $this->enforceReadOnlyPurchasingPo();
        $id = intval($_GET['id'] ?? 0);
        if ($id > 0 && $this->warehouseModel->deletePurchasingPo($id)) {
            $_SESSION['success'] = 'Purchasing PO deleted.';
        } else {
            $_SESSION['error'] = 'Failed to delete purchasing PO.';
        }
        header('Location: ?controller=warehouse&action=purchasingPo');
        exit;
    }

    // ─── Receiving Purchasing PO ────────────────────────────────────────────────

    public function receivingPo() {
        $filters = [
            'status' => $_GET['status'] ?? '',
            'supplier' => $_GET['supplier'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];
        $orders = $this->warehouseModel->getReceivingPoFiltered($filters);
        $suppliers = $this->warehouseModel->getPurchasingPoSuppliers();

        $data['page_title'] = 'Receiving Purchasing PO';
        $data['orders'] = $orders;
        $data['filters'] = $filters;
        $data['suppliers'] = $suppliers;
        $data['readOnly'] = (($_SESSION['department'] ?? '') !== 'warehouse');
        $this->render('receivingPo/index', $data);
    }

    public function receivePurchasingPo() {
        $this->enforceWarehouseReceivingAccess();
        $id = intval($_GET['id'] ?? $_POST['supplier_order_id'] ?? 0);
        if ($id <= 0) {
            header('Location: ?controller=warehouse&action=receivingPo');
            exit;
        }
        $order = $this->warehouseModel->getPurchasingPoById($id);
        if (!$order) {
            $_SESSION['error'] = 'Purchasing PO not found.';
            header('Location: ?controller=warehouse&action=receivingPo');
            exit;
        }

        $orderStatus = strtolower(trim((string) ($order['status'] ?? '')));
        if ($orderStatus === 'requested') {
            $_SESSION['error'] = 'This purchasing order is still in Requested status. Purchasing must process it before warehouse receiving can proceed.';
            header('Location: ?controller=warehouse&action=receivingPo');
            exit;
        }

        if (in_array($orderStatus, ['cancelled', 'received', 'rejected', 'for inspection'])) {
            $_SESSION['error'] = 'Cannot receive a purchasing PO with status "' . ucfirst($order['status'] ?? 'Unknown') . '".';
            header('Location: ?controller=warehouse&action=receivingPo');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $receivedQty = floatval($_POST['received_qty'] ?? 0);
                if ($receivedQty <= 0) {
                    throw new \RuntimeException('Received quantity must be greater than zero.');
                }

                $lotNumber = trim($_POST['lot_number'] ?? '') ?: null;
                $expiryDate = $_POST['expiry_date'] ?: null;
                $deliveryReceiptNo = trim($_POST['delivery_receipt_no'] ?? '') ?: null;
                $receivedDate = $_POST['received_date'] ?: date('Y-m-d');
                $remarks = trim($_POST['remarks'] ?? '') ?: null;

                $result = $this->warehouseModel->receivePurchasingPo($id, [
                    'received_qty' => $receivedQty,
                    'lot_number' => $lotNumber,
                    'expiry_date' => $expiryDate,
                    'delivery_receipt_no' => $deliveryReceiptNo,
                    'received_date' => $receivedDate,
                    'remarks' => $remarks,
                    'received_by' => $_SESSION['user_id']
                ]);

                $this->notifyProcurementOfReceipt($order, $receivedQty);
                try {
                    $poRef = $order['customer_po_number'] ?? ($order['po_id'] ? 'PO #' . $order['po_id'] : 'SO #' . $id);
                    NotificationHelper::qcInspectionNeeded($poRef, $lotNumber ?: ($order['item_code'] ?? 'N/A'), $_SESSION['user_id'] ?? null);
                } catch (\Exception $e) {
                    error_log('qcInspectionNeeded error: ' . $e->getMessage());
                }

                $_SESSION['success'] = 'Shipment received and staged for QC inspection (Status: For Inspection).';
                header('Location: ?controller=warehouse&action=receivingPo');
                exit;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
        }

        header('Location: ?controller=warehouse&action=receivingPo&receive=' . $id);
        exit;
    }

    private function notifyProcurementOfReceipt($order, $receivedQty) {
        try {
            $poRef = $order['customer_po_number'] ?? ($order['po_id'] ? 'PO #' . $order['po_id'] : 'SO #' . $order['supplier_order_id']);
            $itemDesc = $order['item_description'] ?? '';
            $supplier = $order['supplier_name'] ?? 'Unknown Supplier';
            $newReceived = floatval($order['received_qty'] ?? 0) + floatval($receivedQty);
            $status = $newReceived >= floatval($order['quantity']) ? 'fully received' : 'partially received';

            $message = "Purchasing PO {$poRef} ({$itemDesc}) from {$supplier} has been {$status}. Received qty: " . number_format($receivedQty, 4);

            foreach (['admin', 'finance'] as $department) {
                NotificationHelper::create(
                    'receipt',
                    'Purchasing PO Received',
                    $message,
                    $department,
                    '?controller=warehouse&action=receivingPo',
                    $_SESSION['user_id'] ?? null
                );
            }
        } catch (\Exception $e) {
            error_log('notifyProcurementOfReceipt error: ' . $e->getMessage());
        }
    }

    // ─── MRP Snapshots ────────────────────────────────────────────────────────

    public function saveMrpSnapshot() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=warehouse&action=mrp');
            exit;
        }
        try {
            $poId = intval($_POST['po_id'] ?? 0);
            $customerId = intval($_POST['customer_id'] ?? 0);
            if ($poId <= 0 || $customerId <= 0) {
                throw new \RuntimeException('Invalid PO or Customer.');
            }

            $existingRun = $this->warehouseModel->getExistingMrpRunForPO($poId);
            if ($existingRun) {
                $linkedOrders = $this->warehouseModel->getLinkedSupplierOrdersForRun($existingRun['run_id']);
                $hasLockedOrders = false;
                foreach ($linkedOrders as $order) {
                    if (in_array($order['status'], ['pending', 'received', 'completed'])) {
                        $hasLockedOrders = true;
                        break;
                    }
                }
                if ($hasLockedOrders) {
                    $_SESSION['error'] = 'Cannot recalculate MRP. Procurement POs for this snapshot have already been processed. Delete the existing snapshot first.';
                    header("Location: ?controller=warehouse&action=mrp&customer_id={$customerId}&po_id={$poId}");
                    exit;
                }
                $this->warehouseModel->cancelLinkedSupplierOrdersForRun($existingRun['run_id']);
            }

            $allPoItems = $this->warehouseModel->getPOItemsWithBOM($poId);
            $bomIds = [];
            $poiIds = [];
            foreach ($allPoItems as $poi) {
                if (!empty($poi['bom_id'])) $bomIds[] = $poi['bom_id'];
                $poiIds[] = $poi['poi_id'];
            }

            $allComponents = $this->warehouseModel->getBOMComponentsWithStock($bomIds);
            $componentsByBom = [];
            $allIngredientIds = [];
            foreach ($allComponents as $comp) {
                $componentsByBom[$comp['bom_id']][] = $comp;
                $allIngredientIds[$comp['component_item_id']] = true;
            }

            $pendingAllocations = $this->warehouseModel->getPendingAllocationsAcrossPOs($poiIds, array_keys($allIngredientIds));
            $pendingSupplierOrders = $this->warehouseModel->getPendingSupplierOrdersForItems(array_keys($allIngredientIds), $poId);

            $sections = [];
            foreach ($allPoItems as $poi) {
                if (empty($poi['bom_id'])) continue;
                $components = $componentsByBom[$poi['bom_id']] ?? [];
                $rows = [];
                foreach ($components as $comp) {
                    $totalReqt = $this->computeMrpRequiredQty(floatval($poi['quantity']), $poi, $comp);
                    $soh = floatval($comp['soh']);
                    $allocated = floatval($pendingAllocations[$comp['component_item_id']] ?? 0);
                    $supplierPending = floatval($pendingSupplierOrders[$comp['component_item_id']] ?? 0);
                    $available = $soh - $allocated;
                    $excess = $available - $totalReqt + $supplierPending;

                    if ($totalReqt == 0) $remarks = 'NO NEED';
                    elseif ($available <= 0 && $supplierPending == 0) $remarks = 'NO stock for next order mfg';
                    elseif ($excess < 0) $remarks = 'LACKING';
                    elseif ($excess < ($available * 0.1)) $remarks = 'LOW STOCK';
                    else $remarks = 'OK';

                    $rows[] = [
                        'component_item_id' => $comp['component_item_id'],
                        'total_reqt' => $totalReqt,
                        'soh' => $soh,
                        'allocated' => $allocated,
                        'pending' => $allocated + $supplierPending,
                        'excess' => $excess,
                        'remarks' => $remarks,
                    ];
                }
                $sections[] = [
                    'fg_item_id' => $poi['item_id'],
                    'components' => $rows,
                ];
            }

            $runId = $this->warehouseModel->saveMrpRun($poId, $customerId, $_SESSION['user_id'], $sections, []);

            $lackingItems = [];
            foreach ($sections as $sec) {
                foreach ($sec['components'] as $comp) {
                    if ($comp['excess'] < 0) {
                        $itemId = $comp['component_item_id'];
                        $lackingItems[$itemId] = ($lackingItems[$itemId] ?? 0) + abs($comp['excess']);
                    }
                }
            }

            if (!empty($lackingItems)) {
                $existingRequested = $this->warehouseModel->getExistingRequestedOrders(array_keys($lackingItems));
                foreach ($lackingItems as $itemId => $qty) {
                    if (isset($existingRequested[$itemId])) continue;
                    $this->warehouseModel->createSupplierOrder([
                        'supplier_name' => 'Pending Selection',
                        'item_id' => $itemId,
                        'quantity' => $qty,
                        'unit_cost' => 0,
                        'order_date' => date('Y-m-d'),
                        'expected_date' => null,
                        'remarks' => 'Auto-generated from MRP Run #' . $runId,
                        'created_by' => $_SESSION['user_id'],
                        'status' => 'requested',
                        'po_id' => $poId
                    ]);
                }
            }

            $_SESSION['success'] = "MRP snapshot saved (Run #{$runId}).";
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: ?controller=warehouse&action=mrp&customer_id={$customerId}&po_id={$poId}");
        exit;
    }

    public function mrpHistory() {
        $poId = intval($_GET['po_id'] ?? 0);
        $data['page_title'] = 'MRP Snapshot History';
        $data['selectedPO'] = $poId ?: null;

        if ($poId > 0) {
            $data['poHeader'] = $this->warehouseModel->getPurchaseOrderById($poId);
            $data['runs'] = $this->warehouseModel->getMrpRunsByPO($poId);
        } else {
            $data['poHeader'] = null;
            $data['runs'] = $this->warehouseModel->getAllMrpRuns();
        }

        $this->render('mrp/history', $data);
    }

    public function mrpSnapshotPDF() {
        $runId = intval($_GET['run_id'] ?? 0);
        if ($runId <= 0) {
            header('Location: ?controller=warehouse&action=mrpHistory');
            exit;
        }

        $snapshotData = $this->warehouseModel->getMrpRunSnapshotData($runId);
        if (!$snapshotData) {
            $_SESSION['error'] = 'MRP snapshot not found.';
            header('Location: ?controller=warehouse&action=mrpHistory');
            exit;
        }

        $data = $snapshotData;
        extract($data);

        ob_start();
        include __DIR__ . "/../views/mrp/pdf_template.php";
        $html = ob_get_clean();

        require_once __DIR__ . '/../../public/vendor/dompdf/autoload.inc.php';
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $poNumber = $data['po']['po_number'] ?? 'snapshot';
        $snapDate = date('Ymd', strtotime($data['snapshotDate']));
        $dompdf->stream("MRP_{$poNumber}_{$snapDate}.pdf", ['Attachment' => false]);
        exit;
    }

    public function mrpRunDetail() {
        $runId = intval($_GET['run_id'] ?? 0);
        if ($runId <= 0) {
            header('Location: ?controller=warehouse&action=mrp');
            exit;
        }
        $items = $this->warehouseModel->getMrpRunItems($runId);
        header('Content-Type: application/json');
        echo json_encode($items);
        exit;
    }

    public function deleteMrpRun() {
        $runId = intval($_GET['run_id'] ?? 0);
        $poId = intval($_GET['po_id'] ?? 0);
        if ($runId > 0) {
            $result = $this->warehouseModel->deleteMrpRun($runId);
            if ($result['success']) {
                $_SESSION['success'] = 'MRP snapshot deleted.';
            } else {
                $_SESSION['error'] = $result['message'];
            }
        }
        header("Location: ?controller=warehouse&action=mrpHistory&po_id={$poId}");
        exit;
    }

    // ─── Backward Compatibility (Procurement PO → Purchasing PO) ────────────────

    public function supplierOrders() {
        header('Location: ?controller=warehouse&action=purchasingPo');
        exit;
    }

    public function createSupplierOrder() {
        header('Location: ?controller=warehouse&action=createPurchasingPo');
        exit;
    }

    public function receiveSupplierOrder() {
        header('Location: ?controller=warehouse&action=receivingPo');
        exit;
    }

    public function cancelSupplierOrder() {
        header('Location: ?controller=warehouse&action=cancelPurchasingPo&id=' . ($_GET['id'] ?? ''));
        exit;
    }

    public function deleteSupplierOrder() {
        header('Location: ?controller=warehouse&action=deletePurchasingPo&id=' . ($_GET['id'] ?? ''));
        exit;
    }

    public function processSupplierOrder() {
        header('Location: ?controller=warehouse&action=processPurchasingPo');
        exit;
    }

    public function batchProcessSupplierOrders() {
        header('Location: ?controller=warehouse&action=batchProcessPurchasingPo');
        exit;
    }

    private function render($view, $data = []) {
        extract($data);
        ob_start();
        include __DIR__ . "/../views/{$view}.php";
        $content = ob_get_clean();
        include __DIR__ . "/../views/layouts/main.php";
    }
}