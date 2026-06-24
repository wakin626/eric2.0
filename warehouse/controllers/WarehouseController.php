<?php
namespace App\Controllers;

use App\Models\WarehouseModel;
use App\Helpers\Pagination;

class WarehouseController {
    private $warehouseModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?controller=auth&action=login');
            exit;
        }
        $action = $_GET['action'] ?? '';
        if ($action !== 'getPODetails' && ($_SESSION['department'] ?? '') !== 'warehouse') {
            header('Location: ?controller=admin');
            exit;
        }
        $this->warehouseModel = new WarehouseModel();
    }

    public function index() {
        $data['page_title'] = 'Warehouse Dashboard';
        $data['customers'] = $this->warehouseModel->getCustomers();
        $data['items'] = $this->warehouseModel->getItems();
        $allPOs = $this->warehouseModel->getPurchaseOrders();
        $data['purchase_orders'] = $allPOs;
        $poIds = array_column($allPOs, 'po_id');
        $data['po_items_map'] = $this->warehouseModel->getPurchaseOrderItemsByPOIds($poIds);
        $data['deliveries'] = $this->warehouseModel->getDeliveries();
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

    public function createPO() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $po_id = $this->warehouseModel->createPurchaseOrder([
                'customer_po_number' => $_POST['customer_po_number'],
                'customer_po_date' => $_POST['customer_po_date'],
                'customer_id' => $_POST['customer_id'],
                'requested_by' => $_SESSION['user_id'],
                'customer_terms' => $_POST['customer_terms'] ?? 0,
                'production_type' => $_POST['production_type'] ?? 'normal'
            ]);

            $items = json_decode($_POST['items_json'], true);
            foreach ($items as $item) {
                $this->warehouseModel->createPurchaseOrderItem(
                    $po_id,
                    $item['item_id'],
                    $item['quantity'],
                    $item['unit_price']
                );
            }

            $_SESSION['success'] = 'Purchase Order ' . $_POST['customer_po_number'] . ' created successfully';
            header('Location: ?controller=warehouse&action=index');
            exit;
        }
        $data['page_title'] = 'Create PO';
        $data['customers'] = $this->warehouseModel->getCustomers();
        $data['items'] = $this->warehouseModel->getItems();
        $this->render('purchase_orders/create', $data);
    }

    public function viewPO() {
        $id = $_GET['id'] ?? null;
        $data['page_title'] = 'PO Details';
        $data['po'] = $this->warehouseModel->getPurchaseOrderById($id);
        $data['po_items'] = $this->warehouseModel->getPurchaseOrderItems($id);
        $this->render('purchase_orders/view', $data);
    }

    public function getPODetails() {
        header('Content-Type: application/json');
        $id = $_GET['id'] ?? null;
        $po = $this->warehouseModel->getPurchaseOrderById($id);
        $po_items = $this->warehouseModel->getPurchaseOrderItems($id);
        echo json_encode(['po' => $po, 'po_items' => $po_items]);
        exit;
    }

    public function deliveries() {
        $allDeliveries = $this->warehouseModel->getDeliveries();
        $pagination = Pagination::paginate($allDeliveries, 10);
        $data['deliveries'] = $pagination['items'];
        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['purchase_orders'] = $this->warehouseModel->getPurchaseOrders();
        $data['page_title'] = 'Deliveries';
        $this->render('deliveries/index', $data);
    }

    public function createDelivery() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->warehouseModel->createDelivery([
                'po_id' => $_POST['po_id'],
                'poi_id' => $_POST['poi_id'] ?? null,
                'lot_id' => $_POST['lot_id'] ?? null,
                'delivered_by' => $_SESSION['user_id'],
                'delivery_date' => $_POST['delivery_date'],
                'delivery_quantity' => $_POST['delivery_quantity'],
                'remarks' => $_POST['remarks'] ?? ''
            ]);
            $_SESSION['success'] = 'Delivery recorded successfully';
            header('Location: ?controller=warehouse&action=deliveries');
            exit;
        }
        $data['page_title'] = 'Record Delivery';
        $data['purchase_orders'] = $this->warehouseModel->getPurchaseOrders();
        $this->render('deliveries/create', $data);
    }

    public function printDelivery() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: ?controller=warehouse&action=deliveries');
            exit;
        }
        $delivery = $this->warehouseModel->getDeliveryById($id);
        if (!$delivery) {
            $_SESSION['error'] = 'Delivery not found';
            header('Location: ?controller=warehouse&action=deliveries');
            exit;
        }
        $po = $this->warehouseModel->getPurchaseOrderById($delivery['po_id']);
        $po_items = $this->warehouseModel->getPurchaseOrderItems($delivery['po_id']);
        include __DIR__ . "/../views/deliveries/print.php";
        exit;
    }

    public function updateDRNumber() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }
        $delivery_id = $_POST['delivery_id'] ?? null;
        $dr_number = trim($_POST['dr_number'] ?? '');
        if (!$delivery_id) {
            echo json_encode(['success' => false, 'message' => 'Delivery ID is required']);
            exit;
        }
        $this->warehouseModel->updateDRNumber($delivery_id, $dr_number);
        echo json_encode(['success' => true, 'dr_number' => $dr_number]);
        exit;
    }

    public function getAvailableLots() {
        header('Content-Type: application/json');
        $poi_id = $_GET['poi_id'] ?? null;
        if (!$poi_id) {
            echo json_encode([]);
            exit;
        }
        $lots = $this->warehouseModel->getAvailableLotsForDelivery($poi_id);
        echo json_encode($lots);
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