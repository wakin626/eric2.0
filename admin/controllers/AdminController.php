<?php
namespace App\Controllers;

use App\Models\CustomerModel;
use App\Models\ItemModel;
use App\Models\WarehouseModel;
use App\Helpers\Pagination;

class AdminController {
    private $customerModel;
    private $itemModel;
    private $warehouseModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?controller=auth&action=login');
            exit;
        }
        $this->customerModel = new CustomerModel();
        $this->itemModel = new ItemModel();
        $this->warehouseModel = new WarehouseModel();
    }

    public function index() {
        $data['customers'] = $this->customerModel->getAll(false);
        $data['items'] = $this->itemModel->getAll(false);
        $allPOs = $this->warehouseModel->getPurchaseOrders();
        $data['allPOCount'] = count($allPOs);
        $data['purchase_orders'] = $allPOs;
        $poIds = array_column($allPOs, 'po_id');
        $data['po_items_map'] = $this->warehouseModel->getPurchaseOrderItemsByPOIds($poIds);
        $data['page_title'] = 'Admin Dashboard';
        $this->render('dashboard', $data);
    }

    public function customers() {
        $allCustomers = $this->customerModel->getAll(false);
        $pagination = Pagination::paginate($allCustomers, 10);
        $data['customers'] = $pagination['items'];
        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['page_title'] = 'Customer Management';
        $this->render('customers/index', $data);
    }

    public function customerCreate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->customerModel->create($_POST);
            if ($result) {
                $_SESSION['success'] = 'Customer created successfully';
                header('Location: ?controller=admin&action=customers');
                exit;
            }
        }
        $data['page_title'] = 'Add Customer';
        $this->render('customers/form', $data);
    }

    public function customerEdit() {
        $id = $_GET['id'] ?? null;
        $data['customer'] = $this->customerModel->getById($id);
        if (!$data['customer']) {
            $_SESSION['error'] = 'Customer not found';
            header('Location: ?controller=admin&action=customers');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->customerModel->update($id, $_POST);
            if ($result) {
                $_SESSION['success'] = 'Customer updated successfully';
                header('Location: ?controller=admin&action=customers');
                exit;
            }
        }
        $data['page_title'] = 'Edit Customer';
        $this->render('customers/form', $data);
    }

    public function customerDelete() {
        $id = $_GET['id'] ?? null;
        $this->customerModel->softDelete($id);
        $_SESSION['success'] = 'Customer deleted successfully';
        header('Location: ?controller=admin&action=customers');
        exit;
    }

    public function items() {
        $allItems = $this->itemModel->getAll(false);
        $pagination = Pagination::paginate($allItems, 10);
        $data['items'] = $pagination['items'];
        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['page_title'] = 'Item Management';
        $this->render('items/index', $data);
    }

    public function itemCreate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->itemModel->create($_POST);
            if ($result) {
                $_SESSION['success'] = 'Item created successfully';
                header('Location: ?controller=admin&action=items');
                exit;
            }
        }
        $data['page_title'] = 'Add Item';
        $this->render('items/form', $data);
    }

    public function itemEdit() {
        $id = $_GET['id'] ?? null;
        $data['item'] = $this->itemModel->getById($id);
        if (!$data['item']) {
            $_SESSION['error'] = 'Item not found';
            header('Location: ?controller=admin&action=items');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $this->itemModel->update($id, $_POST);
            if ($result) {
                $_SESSION['success'] = 'Item updated successfully';
                header('Location: ?controller=admin&action=items');
                exit;
            }
        }
        $data['page_title'] = 'Edit Item';
        $this->render('items/form', $data);
    }

    public function itemDelete() {
        $id = $_GET['id'] ?? null;
        $this->itemModel->softDelete($id);
        $_SESSION['success'] = 'Item deleted successfully';
        header('Location: ?controller=admin&action=items');
        exit;
    }

    public function itemToggleStatus() {
        $id = $_GET['id'] ?? null;
        $this->itemModel->toggleStatus($id);
        header('Location: ?controller=admin&action=items');
        exit;
    }

    public function customerToggleStatus() {
        $id = $_GET['id'] ?? null;
        $this->customerModel->toggleStatus($id);
        header('Location: ?controller=admin&action=customers');
        exit;
    }

    public function customerUpdate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['customer_id'] ?? null;
            $result = $this->customerModel->update($id, $_POST);
            if ($result) {
                $_SESSION['success'] = 'Customer updated successfully';
            }
        }
        header('Location: ?controller=admin&action=customers');
        exit;
    }

    public function itemUpdate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['item_id'] ?? null;
            $result = $this->itemModel->update($id, $_POST);
            if ($result) {
                $_SESSION['success'] = 'Item updated successfully';
            }
        }
        header('Location: ?controller=admin&action=items');
        exit;
    }

public function purchaseOrders() {
    $allPOs = $this->warehouseModel->getPurchaseOrders();
    $pagination = Pagination::paginate($allPOs, 10);
    $poIds = array_column($pagination['items'], 'po_id');
    $data['allPOs'] = $pagination['items'];
    $data['po_items_map'] = $this->warehouseModel->getPurchaseOrderItemsByPOIds($poIds);
    $data['page'] = $pagination['page'];
    $data['totalPages'] = $pagination['totalPages'];
    $data['total'] = $pagination['total'];
    $data['page_title'] = 'Customer PO';
    $this->render('purchase_orders/index', $data);
}

public function delivered() {
    $data['deliveries'] = $this->warehouseModel->getDeliveries();
    $data['reportedCount'] = $this->warehouseModel->getReportedRemarksCount();
    $data['page_title'] = 'Deliveries';
    $this->render('delivered', $data);
}

public function toggleDeliveryStatus() {
    header('Content-Type: application/json');
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        $deliveryId = $_POST['delivery_id'] ?? null;
        if (!$deliveryId) {
            echo json_encode(['error' => 'Missing delivery_id']);
            exit;
        }
        $newStatus = $this->warehouseModel->toggleDeliveryStatus($deliveryId);
        echo json_encode(['success' => true, 'active_status' => (int)$newStatus]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

public function updateDelivery() {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    $deliveryId = $_POST['delivery_id'] ?? null;
    $drNumber = trim($_POST['dr_number'] ?? '');
    $deliveryDate = $_POST['delivery_date'] ?? '';
    $remarks = trim($_POST['remarks'] ?? '');

    if (!$deliveryId) {
        echo json_encode(['error' => 'Missing delivery_id']);
        exit;
    }

    $this->warehouseModel->updateDelivery($deliveryId, [
        'dr_number' => $drNumber,
        'delivery_date' => $deliveryDate,
        'remarks' => $remarks
    ]);
    echo json_encode(['success' => true]);
    exit;
}

    private function render($view, $data = []) {
        $data['reportedCount'] = $this->warehouseModel->getReportedRemarksCount();
        extract($data);
        ob_start();
        include __DIR__ . "/../views/{$view}.php";
        $content = ob_get_clean();
        include __DIR__ . "/../views/layouts/main.php";
    }
}