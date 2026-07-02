<?php
namespace App\Controllers;

use App\Models\WarehouseModel;
use App\Helpers\Pagination;

class ProductionController {
    private $warehouseModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?controller=auth&action=login');
            exit;
        }
        $action = $_GET['action'] ?? '';
        if ($action !== 'getPODetails' && ($_SESSION['department'] ?? '') !== 'production') {
            header('Location: ?controller=admin');
            exit;
        }
        $this->warehouseModel = new WarehouseModel();
    }

    public function index() {
        $data['page_title'] = 'Production Dashboard';
        $allPOs = $this->warehouseModel->getPurchaseOrders();
        $data['purchase_orders'] = $allPOs;
        $poIds = array_column($allPOs, 'po_id');
        $data['po_items_map'] = $this->warehouseModel->getPurchaseOrderItemsByPOIds($poIds);
        $this->render('dashboard', $data);
    }

    public function purchaseOrders() {
        $allPOs = $this->warehouseModel->getNormalProductionPOs();
        $pagination = Pagination::paginate($allPOs, 10);
        $poIds = array_column($pagination['items'], 'po_id');
        $data['purchase_orders'] = $pagination['items'];
        $data['po_items_map'] = $this->warehouseModel->getPurchaseOrderItemsByPOIds($poIds);
        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['page_title'] = 'Customer PO';
        $this->render('purchase_orders/index', $data);
    }

    public function updateQuantity() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $po_id = $_POST['po_id'] ?? '';

            if (is_array($_POST['poi_id'] ?? null)) {
                $poi_ids = $_POST['poi_id'];
                $quantities = $_POST['added_quantity'] ?? [];
                $lot_numbers = $_POST['lot_number'] ?? [];
                foreach ($poi_ids as $i => $poi_id) {
                    if ($poi_id && isset($quantities[$i]) && $quantities[$i] > 0) {
                        $lot = $lot_numbers[$i] ?? null;
                        $poi = $this->warehouseModel->getPurchaseOrderItemById($poi_id);
                        $itemDesc = $poi['item_description'] ?? null;
                        $this->warehouseModel->updateItemProducedQuantity($poi_id, $quantities[$i], $_SESSION['user_id'], $lot, $itemDesc);
                        if ($lot && $lot !== '') {
                            $this->warehouseModel->updateLotQuantity($poi_id, $lot, $quantities[$i], $_SESSION['user_id'], $poi['po_id'] ?? $po_id);
                        }
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
                        $poi = $this->warehouseModel->getPurchaseOrderItemById($poi_id);
                        $itemDesc = $poi['item_description'] ?? null;
                        $this->warehouseModel->updateItemProducedQuantity($poi_id, $qty, $_SESSION['user_id'], $lot, $itemDesc);
                        if ($lot && $lot !== '') {
                            $this->warehouseModel->updateLotQuantity($poi_id, $lot, $qty, $_SESSION['user_id'], $poi['po_id'] ?? $po_id);
                        }
                    }
                }
            }

            $_SESSION['success'] = 'Production quantity updated successfully';
            $from = $_POST['from'] ?? 'purchaseOrders';
            header('Location: ?controller=production&action=' . $from);
            exit;
        }
    }

    public function history() {
        $allHistory = $this->warehouseModel->getProductionHistory();
        $pagination = Pagination::paginate($allHistory, 10);
        $data['history'] = $pagination['items'];
        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
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
        $pagination = Pagination::paginate($allPOs, 10);
        $poIds = array_column($pagination['items'], 'po_id');
        $data['purchase_orders'] = $pagination['items'];
        $data['po_items_map'] = $this->warehouseModel->getPurchaseOrderItemsByPOIds($poIds);
        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['page_title'] = 'Advance Production';
        $this->render('advance_production/index', $data);
    }

    public function getPODetails() {
        header('Content-Type: application/json');
        $id = $_GET['id'] ?? null;
        $po = $this->warehouseModel->getPurchaseOrderById($id);
        $po_items = $this->warehouseModel->getPurchaseOrderItems($id);
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