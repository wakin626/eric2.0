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
        $allPOs = $this->warehouseModel->getPurchaseOrders();
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
            $po_id = $_POST['po_id'];

            if (is_array($_POST['added_quantity'] ?? null)) {
                $poi_ids = $_POST['poi_id'] ?? [];
                $quantities = $_POST['added_quantity'];
                foreach ($poi_ids as $i => $poi_id) {
                    if ($poi_id && isset($quantities[$i]) && $quantities[$i] > 0) {
                        $this->warehouseModel->updateItemProducedQuantity($poi_id, $quantities[$i]);
                    }
                }
            } else {
                $added_quantity = $_POST['added_quantity'];
                $poi_id = $_POST['poi_id'] ?? null;
                if ($poi_id) {
                    $this->warehouseModel->updateItemProducedQuantity($poi_id, $added_quantity);
                } else {
                    $this->warehouseModel->updateProducedQuantity($po_id, $added_quantity, $_SESSION['user_id']);
                }
            }

            $_SESSION['success'] = 'Production quantity updated successfully';
            header('Location: ?controller=production&action=purchaseOrders');
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
        $data['page_title'] = 'Production History';
        $this->render('history/index', $data);
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
        extract($data);
        ob_start();
        include __DIR__ . "/../views/{$view}.php";
        $content = ob_get_clean();
        include __DIR__ . "/../views/layouts/main.php";
    }
}