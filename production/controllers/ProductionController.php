<?php
namespace App\Controllers;

use App\Models\WarehouseModel;
use App\Models\AuditModel;
use App\Helpers\Pagination;

class ProductionController {
    private $warehouseModel;

    public function __construct() {
        $action = $_GET['action'] ?? '';
        if (!isset($_SESSION['user_id'])) {
            if ($action === 'getPODetails') {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => 'Session expired. Please log in again.']);
                exit;
            }
            header('Location: ?controller=auth&action=login');
            exit;
        }
        if ($action !== 'getPODetails' && ($_SESSION['department'] ?? '') !== 'production') {
            header('Location: ?controller=admin');
            exit;
        }
        $this->warehouseModel = new WarehouseModel();
    }

    public function index() {
        $data['page_title'] = 'Production Dashboard';
        $data['purchase_orders'] = $this->warehouseModel->getActivePOsForDashboard(5);
        $poIds = array_column($data['purchase_orders'], 'po_id');
        $data['po_items_map'] = $this->warehouseModel->getPurchaseOrderItemsByPOIds($poIds);

        $allPoiIds = [];
        foreach ($data['po_items_map'] as $items) {
            foreach ($items as $item) { $allPoiIds[] = $item['poi_id']; }
        }
        $rawNormalConsumption = $this->warehouseModel->getAdvanceConsumptionByNormalPoiIds($allPoiIds);
        $normalConsumptionByPoi = [];
        foreach ($rawNormalConsumption as $cr) { $normalConsumptionByPoi[$cr['normal_poi_id']][] = $cr; }
        $data['normal_consumption_records'] = $normalConsumptionByPoi;

        $this->render('dashboard', $data);
    }

    public function purchaseOrders() {
        $search = $_GET['search'] ?? '';
        if ($search) {
            $allPOs = $this->warehouseModel->getPurchaseOrdersFiltered(['search' => $search, 'production_type' => 'normal']);
        } else {
            $allPOs = $this->warehouseModel->getNormalProductionPOs();
        }
        $pagination = Pagination::paginate($allPOs, 10);
        $poIds = array_column($pagination['items'], 'po_id');
        $data['purchase_orders'] = $pagination['items'];
        $data['po_items_map'] = $this->warehouseModel->getPurchaseOrderItemsByPOIds($poIds);

        $allPoiIds = [];
        foreach ($data['po_items_map'] as $items) {
            foreach ($items as $item) { $allPoiIds[] = $item['poi_id']; }
        }
        $rawNormalConsumption = $this->warehouseModel->getAdvanceConsumptionByNormalPoiIds($allPoiIds);
        $normalConsumptionByPoi = [];
        foreach ($rawNormalConsumption as $cr) { $normalConsumptionByPoi[$cr['normal_poi_id']][] = $cr; }
        $data['normal_consumption_records'] = $normalConsumptionByPoi;

        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['search'] = $search;
        $data['page_title'] = 'Customer PO';
        $this->render('purchase_orders/index', $data);
    }

    public function updateQuantity() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $po_id = $_POST['po_id'] ?? '';
            $maxRetries = 3;

            for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
                $conn = \App\Core\BaseModel::getConnection();
                $conn->beginTransaction();
                try {
                    $stmtLast = $conn->prepare("SELECT sts_ref FROM production_history WHERE sts_ref LIKE 'STS-%' ORDER BY history_id DESC LIMIT 1 FOR UPDATE");
                    $stmtLast->execute();
                    $lastSts = $stmtLast->fetchColumn();
                    if ($lastSts && preg_match('/STS-(\d+)/', $lastSts, $m)) {
                        $nextNum = intval($m[1]) + 1;
                    } else {
                        $nextNum = 1;
                    }

                $shifts = $_POST['shift'] ?? [];
                $rejectStatuses = $_POST['reject_status'] ?? [];
                $stsRemarks = $_POST['sts_remarks'] ?? [];
                $pcsPerCases = $_POST['pcs_per_case'] ?? [];
                $preparedByName = trim($_POST['prepared_by_name'] ?? '') ?: null;
                $checkedByName = trim($_POST['checked_by_name'] ?? '') ?: null;
                $receivedByName = trim($_POST['received_by_name'] ?? '') ?: null;
                if (!is_array($shifts)) $shifts = [$shifts];
                if (!is_array($rejectStatuses)) $rejectStatuses = [$rejectStatuses];
                if (!is_array($stsRemarks)) $stsRemarks = [$stsRemarks];
                if (!is_array($pcsPerCases)) $pcsPerCases = [$pcsPerCases];

                if (is_array($_POST['poi_id'] ?? null)) {
                    $poi_ids = $_POST['poi_id'];
                    $quantities = $_POST['added_quantity'] ?? [];
                    $lot_numbers = $_POST['lot_number'] ?? [];
                    foreach ($poi_ids as $i => $poi_id) {
                        if ($poi_id && isset($quantities[$i]) && $quantities[$i] > 0) {
                            $autoStsRef = 'STS-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
                            $nextNum++;
                            $lot = $lot_numbers[$i] ?? null;
                            $poi = $this->warehouseModel->getPurchaseOrderItemById($poi_id);
                            $itemDesc = $poi['item_description'] ?? null;
                            $previousProduced = intval($poi['produced_quantity'] ?? 0);
                            $addedQty = intval($quantities[$i] ?? 0);
                            $newProduced = $previousProduced + $addedQty;
                            $previousLotQty = $previousProduced;
                            $newLotQty = $newProduced;
                            if ($lot && $lot !== '') {
                                $lotQtyStmt = \App\Core\BaseModel::getConnection()->prepare("SELECT quantity_produced FROM production_lots WHERE poi_id = :poi_id AND lot_number = :lot_number AND `is_removed` = 0 LIMIT 1");
                                $lotQtyStmt->execute(['poi_id' => $poi_id, 'lot_number' => $lot]);
                                $currentLot = $lotQtyStmt->fetch();
                                $previousLotQty = isset($currentLot['quantity_produced']) ? intval($currentLot['quantity_produced']) : 0;
                                $newLotQty = $previousLotQty + $addedQty;
                                $this->warehouseModel->updateLotQuantity($poi_id, $lot, $addedQty, $_SESSION['user_id'], $poi['po_id'] ?? $po_id, intval($pcsPerCases[$i] ?? 0) ?: null);
                            }
                            $extraStsData = [
                                'shift' => trim($shifts[$i] ?? '') ?: null,
                                'reject_status' => trim($rejectStatuses[$i] ?? '') ?: null,
                                'sts_remarks' => trim($stsRemarks[$i] ?? '') ?: null,
                                'pcs_per_case' => intval($pcsPerCases[$i] ?? 0) ?: null,
                                'prepared_by_name' => $preparedByName,
                                'checked_by_name' => $checkedByName,
                                'received_by_name' => $receivedByName,
                            ];
                            $this->warehouseModel->updateItemProducedQuantity($poi_id, $addedQty, $_SESSION['user_id'], $lot, $itemDesc, $autoStsRef, $extraStsData);
                            $this->checkAndRecordExcess($poi_id, $po_id, $conn);
                            $poLabel = $poi['customer_po_number'] ?? $poi['po_number'] ?? 'PO item #' . $poi_id;
                            $lotText = $lot ? ' for lot ' . $lot : '';
                            $lotQtyText = $lot ? ' (lot quantity ' . $previousLotQty . ' → ' . $newLotQty . ')' : '';
                            $description = 'Updated production quantity for ' . $poLabel . ': added ' . $addedQty . ' pcs' . $lotText . ' (previous ' . $previousProduced . ' → new ' . $newProduced . ')' . $lotQtyText . ' [' . $autoStsRef . ']';
                            AuditModel::log($_SESSION['user_id'], 'UPDATE', 'production', $description, [
                                'previous_quantity' => $previousLotQty,
                                'added_quantity' => 0,
                                'new_quantity' => $previousLotQty,
                                'lot_number' => $lot,
                                'sts_ref' => $autoStsRef,
                            ], [
                                'previous_quantity' => $previousLotQty,
                                'added_quantity' => $addedQty,
                                'new_quantity' => $newLotQty,
                                'lot_number' => $lot,
                                'sts_ref' => $autoStsRef,
                            ], 'purchase_order_item', $poi_id);
                        }
                    }
                } else {
                    $poi_id = $_POST['poi_id'] ?? null;
                    $quantities = $_POST['added_quantity'] ?? [];
                    $lot_numbers = $_POST['lot_number'] ?? [];
                    if (!is_array($quantities)) $quantities = [$quantities];
                    if (!is_array($lot_numbers)) $lot_numbers = [$lot_numbers];
                    foreach ($quantities as $i => $qty) {
                        if ($poi_id && $qty > 0) {
                            $autoStsRef = 'STS-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
                            $nextNum++;
                            $lot = $lot_numbers[$i] ?? null;
                            $poi = $this->warehouseModel->getPurchaseOrderItemById($poi_id);
                            $itemDesc = $poi['item_description'] ?? null;
                            $previousProduced = intval($poi['produced_quantity'] ?? 0);
                            $addedQty = intval($qty ?? 0);
                            $newProduced = $previousProduced + $addedQty;
                            $previousLotQty = $previousProduced;
                            $newLotQty = $newProduced;
                            if ($lot && $lot !== '') {
                                $lotQtyStmt = \App\Core\BaseModel::getConnection()->prepare("SELECT quantity_produced FROM production_lots WHERE poi_id = :poi_id AND lot_number = :lot_number AND `is_removed` = 0 LIMIT 1");
                                $lotQtyStmt->execute(['poi_id' => $poi_id, 'lot_number' => $lot]);
                                $currentLot = $lotQtyStmt->fetch();
                                $previousLotQty = isset($currentLot['quantity_produced']) ? intval($currentLot['quantity_produced']) : 0;
                                $newLotQty = $previousLotQty + $addedQty;
                                $this->warehouseModel->updateLotQuantity($poi_id, $lot, $addedQty, $_SESSION['user_id'], $poi['po_id'] ?? $po_id, intval($pcsPerCases[$i] ?? 0) ?: null);
                            }
                            $extraStsData = [
                                'shift' => trim($shifts[$i] ?? '') ?: null,
                                'reject_status' => trim($rejectStatuses[$i] ?? '') ?: null,
                                'sts_remarks' => trim($stsRemarks[$i] ?? '') ?: null,
                                'pcs_per_case' => intval($pcsPerCases[$i] ?? 0) ?: null,
                                'prepared_by_name' => $preparedByName,
                                'checked_by_name' => $checkedByName,
                                'received_by_name' => $receivedByName,
                            ];
                            $this->warehouseModel->updateItemProducedQuantity($poi_id, $addedQty, $_SESSION['user_id'], $lot, $itemDesc, $autoStsRef, $extraStsData);
                            $this->checkAndRecordExcess($poi_id, $po_id, $conn);
                            $poLabel = $poi['customer_po_number'] ?? $poi['po_number'] ?? 'PO item #' . $poi_id;
                            $lotText = $lot ? ' for lot ' . $lot : '';
                            $lotQtyText = $lot ? ' (lot quantity ' . $previousLotQty . ' → ' . $newLotQty . ')' : '';
                            $description = 'Updated production quantity for ' . $poLabel . ': added ' . $addedQty . ' pcs' . $lotText . ' (previous ' . $previousProduced . ' → new ' . $newProduced . ')' . $lotQtyText . ' [' . $autoStsRef . ']';
                            AuditModel::log($_SESSION['user_id'], 'UPDATE', 'production', $description, [
                                'previous_quantity' => $previousLotQty,
                                'added_quantity' => 0,
                                'new_quantity' => $previousLotQty,
                                'lot_number' => $lot,
                                'sts_ref' => $autoStsRef,
                            ], [
                                'previous_quantity' => $previousLotQty,
                                'added_quantity' => $addedQty,
                                'new_quantity' => $newLotQty,
                                'lot_number' => $lot,
                                'sts_ref' => $autoStsRef,
                            ], 'purchase_order_item', $poi_id);
                        }
                    }
                }

                $conn->commit();
                $_SESSION['success'] = 'Production quantity updated successfully';
                $from = $_POST['from'] ?? 'purchaseOrders';
                header('Location: ?controller=production&action=' . $from);
                exit;
            } catch (\PDOException $e) {
                $conn->rollBack();
                if ($e->errorInfo[1] == 23000 && $attempt < $maxRetries - 1) {
                    continue;
                }
                $_SESSION['error'] = 'Failed to update production: ' . $e->getMessage();
                $from = $_POST['from'] ?? 'purchaseOrders';
                header('Location: ?controller=production&action=' . $from);
                exit;
            } catch (\Exception $e) {
                $conn->rollBack();
                $_SESSION['error'] = 'Failed to update production: ' . $e->getMessage();
                $from = $_POST['from'] ?? 'purchaseOrders';
                header('Location: ?controller=production&action=' . $from);
                exit;
            }
            }
        }
    }

    public function getNextStsRef() {
        header('Content-Type: application/json');
        try {
            $conn = \App\Core\BaseModel::getConnection();
            $stmt = $conn->prepare("SELECT sts_ref FROM production_history WHERE sts_ref LIKE 'STS-%' ORDER BY history_id DESC LIMIT 1");
            $stmt->execute();
            $lastSts = $stmt->fetchColumn();
            if ($lastSts && preg_match('/STS-(\d+)/', $lastSts, $m)) {
                $nextNum = intval($m[1]) + 1;
            } else {
                $nextNum = 1;
            }
            echo json_encode(['sts_ref' => 'STS-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT)]);
        } catch (\Exception $e) {
            error_log('getNextStsRef error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get STS reference']);
        }
        exit;
    }

    private function checkAndRecordExcess($poi_id, $po_id, $conn = null) {
        $ownsConn = false;
        if (!$conn) {
            $conn = \App\Core\BaseModel::getConnection();
            $ownsConn = true;
        }

        $stmt = $conn->prepare("SELECT quantity, produced_quantity, item_id
            FROM purchase_order_items WHERE poi_id = :poi_id FOR UPDATE");
        $stmt->execute(['poi_id' => $poi_id]);
        $poi = $stmt->fetch();
        if (!$poi) return;

        $ordered = intval($poi['quantity']);
        $produced = intval($poi['produced_quantity']);

        if ($produced > $ordered) {
            $excess = $produced - $ordered;
            $po = $this->warehouseModel->getPurchaseOrderById($po_id);
            if ($po) {
                $this->warehouseModel->insertExcessProduction([
                    'customer_id' => $po['customer_id'],
                    'item_id' => $poi['item_id'],
                    'source_po_id' => $po_id,
                    'source_poi_id' => $poi_id,
                    'excess_quantity' => $excess,
                    'notes' => 'Excess from PO ' . ($po['customer_po_number'] ?? $po_id)
                ]);
            }
        }
    }

    public function history() {
        $search = $_GET['search'] ?? '';
        $filterCustomer = $_GET['filter_customer'] ?? '';
        $filterItem = $_GET['filter_item'] ?? '';
        $filterLot = $_GET['filter_lot'] ?? '';

        $hasFilter = $search || $filterCustomer || $filterItem || $filterLot;
        if ($hasFilter) {
            $filters = [];
            if ($search) $filters['search'] = $search;
            if ($filterCustomer) $filters['customer_name'] = $filterCustomer;
            if ($filterItem) $filters['item_description'] = $filterItem;
            if ($filterLot) $filters['lot_number'] = $filterLot;
            $allHistory = $this->warehouseModel->getProductionHistoryFiltered($filters);
        } else {
            $allHistory = $this->warehouseModel->getProductionHistory();
        }

        $allCustomers = array_values(array_unique(array_filter(array_column($allHistory, 'customer_name'))));
        $allItems = array_values(array_unique(array_filter(array_column($allHistory, 'item_description'))));
        $allLots = array_values(array_unique(array_filter(array_column($allHistory, 'lot_number'))));

        if ($hasFilter) {
            $pagination = ['items' => $allHistory, 'page' => 1, 'perPage' => count($allHistory), 'total' => count($allHistory), 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false];
        } else {
            $pagination = Pagination::paginate($allHistory, 10);
        }
        $data['history'] = $pagination['items'];

        $poiIds = array_column($data['history'], 'poi_id');
        $poiIds = array_filter($poiIds);
        $rawNormalConsumption = !empty($poiIds) ? $this->warehouseModel->getAdvanceConsumptionByNormalPoiIds(array_values($poiIds)) : [];
        $normalConsumptionByPoi = [];
        foreach ($rawNormalConsumption as $cr) { $normalConsumptionByPoi[$cr['normal_poi_id']][] = $cr; }
        $data['normal_consumption_records'] = $normalConsumptionByPoi;

        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['search'] = $search;
        $data['filterCustomer'] = $filterCustomer;
        $data['filterItem'] = $filterItem;
        $data['filterLot'] = $filterLot;
        $data['allCustomers'] = $allCustomers;
        $data['allItems'] = $allItems;
        $data['allLots'] = $allLots;
        $data['reportsCount'] = $this->warehouseModel->getProductionReportsCount();
        $data['page_title'] = 'Production History';
        $this->render('history/index', $data);
    }

    public function reportHistory() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $history_id = $_POST['history_id'] ?? null;
                $reason = trim($_POST['reason'] ?? '');
                $report_type = $_POST['report_type'] ?? 'lot_number';
                if (!$history_id || empty($reason)) {
                    $_SESSION['error'] = 'Missing history ID or reason.';
                    header('Location: ?controller=production&action=history');
                    exit;
                }
                $conn = \App\Core\BaseModel::getConnection();
                $stmt = $conn->prepare("SELECT history_id, poi_id, po_id, lot_number FROM production_history WHERE history_id = :hid");
                $stmt->execute(['hid' => $history_id]);
                $history = $stmt->fetch();
                if ($history) {
                    $this->warehouseModel->createProductionReport(
                        $history['history_id'],
                        $history['poi_id'],
                        $history['po_id'],
                        $history['lot_number'],
                        $_SESSION['user_id'],
                        $reason,
                        $report_type
                    );
                    AuditModel::log($_SESSION['user_id'], 'CREATE', 'production', 'Reported production history entry #' . $history_id . ' with reason: ' . $reason, null, $_POST, 'production_history', null);
                    $_SESSION['success'] = 'Report submitted successfully.';
                } else {
                    $_SESSION['error'] = 'History record not found.';
                }
            } catch (\Exception $e) {
                error_log('reportHistory error: ' . $e->getMessage());
                $_SESSION['error'] = 'Failed to submit report';
            }
            header('Location: ?controller=production&action=history');
            exit;
        }
    }

    public function advanceProduction() {
        $search = $_GET['search'] ?? '';
        if ($search) {
            $allPOs = $this->warehouseModel->getPurchaseOrdersFiltered(['search' => $search, 'production_type' => 'advance']);
        } else {
            $allPOs = $this->warehouseModel->getAdvanceProductionPOs();
        }
        $pagination = Pagination::paginate($allPOs, 10);
        $poIds = array_column($pagination['items'], 'po_id');
        $data['purchase_orders'] = $pagination['items'];
        $data['po_items_map'] = $this->warehouseModel->getPurchaseOrderItemsByPOIds($poIds);

        // Get all advance PO item IDs to fetch consumption records
        $allPoiIds = [];
        foreach ($data['po_items_map'] as $items) {
            foreach ($items as $item) {
                $allPoiIds[] = $item['poi_id'];
            }
        }
        $rawConsumption = $this->warehouseModel->getAdvanceConsumptionByPoiIds($allPoiIds);

        // Group by advance_poi_id for easy lookup in view
        $consumptionByPoi = [];
        foreach ($rawConsumption as $cr) {
            $consumptionByPoi[$cr['advance_poi_id']][] = $cr;
        }
        $data['consumption_records'] = $consumptionByPoi;

        $rawNormalConsumption = $this->warehouseModel->getAdvanceConsumptionByNormalPoiIds($allPoiIds);
        $normalConsumptionByPoi = [];
        foreach ($rawNormalConsumption as $cr) { $normalConsumptionByPoi[$cr['normal_poi_id']][] = $cr; }
        $data['normal_consumption_records'] = $normalConsumptionByPoi;

        $data['excess_records'] = $this->warehouseModel->getAllExcessForProduction();
        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['search'] = $search;
        $data['page_title'] = 'Advance Production';
        $this->render('advance_production/index', $data);
    }

    public function getPODetails() {
        header('Content-Type: application/json');
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing PO ID']);
                exit;
            }

            $po = $this->warehouseModel->getPurchaseOrderById($id);
            if (!$po) {
                http_response_code(404);
                echo json_encode(['error' => 'PO not found']);
                exit;
            }

            $po_items = $this->warehouseModel->getPurchaseOrderItems($id);
            foreach ($po_items as &$item) {
                $item['lots'] = $this->warehouseModel->getLotsByPOItem($item['poi_id']);
            }
            unset($item);

            echo json_encode(['po' => $po, 'po_items' => $po_items]);
        } catch (\Exception $e) {
            error_log('getPODetails error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to load PO details']);
        }
        exit;
    }

    public function getLotsByPOItem() {
        header('Content-Type: application/json');
        try {
            $poiId = $_GET['poi_id'] ?? null;
            if (!$poiId) {
                echo json_encode([]);
                exit;
            }
            $lots = $this->warehouseModel->getLotsByPOItem($poiId);
            echo json_encode($lots);
        } catch (\Exception $e) {
            error_log('getLotsByPOItem error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to load lots']);
        }
        exit;
    }

    public function activityLogs() {
        try {
            $auditModel = new \App\Models\AuditModel();
            $filters = [
                'department' => $_SESSION['department'] ?? 'production',
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
            $data['logController'] = 'production';
            $data['departmentLocked'] = true;
            $data['hideDeptColumn'] = true;
            $data['stats'] = [
                'today_count' => \App\Models\AuditModel::getLogStats('production')['today_count'] ?? 0,
                'by_department' => [],
            ];
            $data['page_title'] = 'Activity Logs';
            $this->render('activity_logs/index', $data);
        } catch (\Exception $e) {
            error_log('activityLogs error: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to load activity logs';
            header('Location: ?controller=production&action=dashboard');
            exit;
        }
    }

    public function printSTS() {
        try {
            $stsRef = trim($_GET['sts_ref'] ?? '');
            if (empty($stsRef)) {
                echo "<div class='container mt-5'><div class='alert alert-danger'>Missing STS reference.</div></div>";
                exit;
            }

            $conn = \App\Core\BaseModel::getConnection();
            $stmt = $conn->prepare("
                SELECT ph.*, 
                   po.customer_po_number, po.po_number,
                   c.customer_name, c.customer_code, c.customer_address, c.customer_tin,
                   u.full_name AS prepared_by_name
                FROM production_history ph
                LEFT JOIN purchase_orders po ON ph.po_id = po.po_id
                LEFT JOIN purchase_order_items poi ON ph.poi_id = poi.poi_id
                LEFT JOIN items i ON poi.item_id = i.item_id
                LEFT JOIN customers c ON po.customer_id = c.customer_id
                LEFT JOIN users u ON ph.user_id = u.user_id
                WHERE ph.sts_ref = :sts_ref
                ORDER BY ph.history_id ASC
            ");
            $stmt->execute(['sts_ref' => $stsRef]);
            $entries = $stmt->fetchAll();

            if (empty($entries)) {
                echo "<div class='container mt-5'><div class='alert alert-danger'>STS \"" . htmlspecialchars($stsRef) . "\" not found.</div></div>";
                exit;
            }

            $data = [
                'sts_ref' => $stsRef,
                'entries' => $entries,
                'po' => [
                    'po_number' => $entries[0]['po_number'] ?? '',
                    'customer_po_number' => $entries[0]['customer_po_number'] ?? '',
                    'customer_name' => $entries[0]['customer_name'] ?? '',
                    'customer_code' => $entries[0]['customer_code'] ?? '',
                    'customer_address' => $entries[0]['customer_address'] ?? '',
                    'customer_tin' => $entries[0]['customer_tin'] ?? '',
                ],
                'prepared_by' => $entries[0]['prepared_by_name'] ?? '',
                'date_created' => $entries[0]['date_created'] ?? '',
            ];

            extract($data);
            include __DIR__ . "/../views/production/print_sts.php";
        } catch (\Exception $e) {
            error_log('printSTS error: ' . $e->getMessage());
            echo "<div class='container mt-5'><div class='alert alert-danger'>Failed to load STS. Please try again.</div></div>";
        }
        exit;
    }

    private function render($view, $data = []) {
        $data['reportsCount'] = $this->warehouseModel->getProductionReportsCount();
        extract($data);
        ob_start();
        include __DIR__ . "/../views/{$view}.php";
        $content = ob_get_clean();
        include __DIR__ . "/../views/layouts/main.php";
    }
}