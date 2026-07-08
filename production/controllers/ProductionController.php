<?php
namespace App\Controllers;

use App\Models\WarehouseModel;
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
        $this->render('dashboard', $data);
    }

    public function purchaseOrders() {
        $allPOs = $this->warehouseModel->getNormalProductionPOs();
        $search = $_GET['search'] ?? '';
        if ($search) $allPOs = Pagination::filterBySearch($allPOs, $search);
        $pagination = Pagination::paginate($allPOs, 10);
        $poIds = array_column($pagination['items'], 'po_id');
        $data['purchase_orders'] = $pagination['items'];
        $data['po_items_map'] = $this->warehouseModel->getPurchaseOrderItemsByPOIds($poIds);
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
            $sts_ref_input = $_POST['sts_ref'] ?? null;
            $sts_ref_values = null;

            if (is_array($sts_ref_input)) {
                $sts_ref_values = array_map(function($value) {
                    return is_string($value) ? trim($value) : null;
                }, $sts_ref_input);
            } else {
                $sts_ref_values = trim((string)$sts_ref_input) ?: null;
            }

            if (is_array($_POST['poi_id'] ?? null)) {
                $poi_ids = $_POST['poi_id'];
                $quantities = $_POST['added_quantity'] ?? [];
                $lot_numbers = $_POST['lot_number'] ?? [];
                foreach ($poi_ids as $i => $poi_id) {
                    if ($poi_id && isset($quantities[$i]) && $quantities[$i] > 0) {
                        $lot = $lot_numbers[$i] ?? null;
                        $sts_ref = is_array($sts_ref_values) ? ($sts_ref_values[$i] ?? null) : $sts_ref_values;
                        $poi = $this->warehouseModel->getPurchaseOrderItemById($poi_id);
                        $itemDesc = $poi['item_description'] ?? null;
                        $this->warehouseModel->updateItemProducedQuantity($poi_id, $quantities[$i], $_SESSION['user_id'], $lot, $itemDesc, $sts_ref);
                        if ($lot && $lot !== '') {
                            $this->warehouseModel->updateLotQuantity($poi_id, $lot, $quantities[$i], $_SESSION['user_id'], $poi['po_id'] ?? $po_id);
                        }
                        $this->checkAndRecordExcess($poi_id, $po_id);
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
                        $lot = $lot_numbers[$i] ?? null;
                        $sts_ref = is_array($sts_ref_values) ? ($sts_ref_values[$i] ?? null) : $sts_ref_values;
                        $poi = $this->warehouseModel->getPurchaseOrderItemById($poi_id);
                        $itemDesc = $poi['item_description'] ?? null;
                        $this->warehouseModel->updateItemProducedQuantity($poi_id, $qty, $_SESSION['user_id'], $lot, $itemDesc, $sts_ref);
                        if ($lot && $lot !== '') {
                            $this->warehouseModel->updateLotQuantity($poi_id, $lot, $qty, $_SESSION['user_id'], $poi['po_id'] ?? $po_id);
                        }
                        $this->checkAndRecordExcess($poi_id, $po_id);
                    }
                }
            }

            $_SESSION['success'] = 'Production quantity updated successfully';
            $from = $_POST['from'] ?? 'purchaseOrders';
            header('Location: ?controller=production&action=' . $from);
            exit;
        }
    }

    private function checkAndRecordExcess($poi_id, $po_id) {
        $poi = $this->warehouseModel->getPurchaseOrderItemById($poi_id);
        if (!$poi) return;

        $ordered = $poi['quantity'] ?? 0;
        $produced = $poi['produced_quantity'] ?? 0;

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
        $allHistory = $this->warehouseModel->getProductionHistory();
        $search = $_GET['search'] ?? '';
        if ($search) $allHistory = Pagination::filterBySearch($allHistory, $search);
        $pagination = Pagination::paginate($allHistory, 10);
        $data['history'] = $pagination['items'];
        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['search'] = $search;
        $data['reportsCount'] = $this->warehouseModel->getProductionReportsCount();
        $data['page_title'] = 'Production History';
        $this->render('history/index', $data);
    }

    public function reportHistory() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                $_SESSION['success'] = 'Report submitted successfully.';
            } else {
                $_SESSION['error'] = 'History record not found.';
            }
            header('Location: ?controller=production&action=history');
            exit;
        }
    }

    public function advanceProduction() {
        $allPOs = $this->warehouseModel->getAdvanceProductionPOs();
        $search = $_GET['search'] ?? '';
        if ($search) $allPOs = Pagination::filterBySearch($allPOs, $search);
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