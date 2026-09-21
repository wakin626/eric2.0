<?php
namespace App\Controllers;

use App\Models\MoModel;
use App\Models\CatalogModel;

class MoController {
    private $moModel;
    private $catalogModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?controller=auth&action=login');
            exit;
        }

        $this->moModel = new MoModel();
        $this->catalogModel = new CatalogModel();
    }

    public function index() {
        $status = $_GET['status'] ?? 'all';
        $orders = $this->moModel->getAll($status);

        $data = [
            'page_title' => 'Manufacturing Orders',
            'orders' => $orders,
            'status' => $status,
            'customers' => $this->catalogModel->getCustomers(),
        ];

        $this->render('mo/index', $data);
    }

    public function create() {
        $data = [
            'page_title' => 'Create MO',
            'customers' => $this->catalogModel->getCustomers(),
            'items' => $this->catalogModel->getItems(),
        ];

        $this->render('mo_entry', $data);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=mo&action=index');
            exit;
        }

        $itemsJson = $_POST['line_items_json'] ?? '[]';
        $items = json_decode($itemsJson, true);
        if (!is_array($items)) {
            $items = [];
        }

        $customerId = $_POST['customer_id'] ?? $_POST['customer_code'] ?? null;
        $customer = null;
        if ($customerId) {
            foreach ($this->catalogModel->getCustomers() as $cust) {
                if ((string) $cust['customer_id'] === (string) $customerId) {
                    $customer = $cust;
                    break;
                }
            }
        }

        $header = [
            'mo_number' => trim($_POST['mo_number'] ?? ''),
            'mo_type' => $_POST['mo_type'] ?? 'Standard',
            'mo_site' => $_POST['mo_site'] ?? '001 - Sterling Technopark',
            'order_date' => $_POST['order_date'] ?? date('Y-m-d'),
            'due_date' => $_POST['due_date'] ?? date('Y-m-d'),
            'planned_start_date' => $_POST['planned_start_date'] ?? date('Y-m-d'),
            'priority' => (int) ($_POST['priority'] ?? 3),
            'reference_no' => $_POST['reference_no'] ?? null,
            'mo_status' => $_POST['mo_status'] ?? 'Planned',
            'customer_id' => $customer['customer_id'] ?? null,
            'customer_code' => $customer['customer_code'] ?? ($_POST['customer_code'] ?? null),
            'customer_name' => $customer['customer_name'] ?? ($_POST['customer_name'] ?? null),
            'batch_lot_no' => $_POST['batch_lot_no'] ?? null,
            'po_number' => $_POST['po_number'] ?? null,
        ];

        if (empty($header['mo_number'])) {
            $_SESSION['error'] = 'MO Number is required.';
            header('Location: ?controller=mo&action=create');
            exit;
        }

        try {
            $this->moModel->create($header, $items);
            $_SESSION['success'] = 'Manufacturing Order created successfully.';
            header('Location: ?controller=mo&action=index');
            exit;
        } catch (\Exception $e) {
            error_log('MoController::store error: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to save MO: ' . $e->getMessage();
            header('Location: ?controller=mo&action=create');
            exit;
        }
    }

    public function edit() {
        $id = $_GET['id'] ?? 0;
        $order = $this->moModel->findById($id);
        if (!$order) {
            $_SESSION['error'] = 'MO not found.';
            header('Location: ?controller=mo&action=index');
            exit;
        }

        $data = [
            'page_title' => 'Edit MO',
            'mo' => $order,
            'items' => $this->moModel->getItemsByMoId($id),
            'customers' => $this->catalogModel->getCustomers(),
            'allItems' => $this->catalogModel->getItems(),
        ];

        $this->render('mo_entry', $data);
    }

    public function release() {
        $id = $_GET['id'] ?? 0;
        if ($id) {
            $this->moModel->updateStatus($id, 'Released');
            $_SESSION['success'] = 'MO released successfully.';
        }
        header('Location: ?controller=mo&action=index');
        exit;
    }

    public function printLmr() {
        $id = $_GET['id'] ?? 0;
        $order = $this->moModel->findById($id);
        if (!$order) {
            $_SESSION['error'] = 'MO not found.';
            header('Location: ?controller=mo&action=index');
            exit;
        }

        $data = [
            'page_title' => 'LMR Print',
            'mo' => $order,
            'items' => $this->moModel->getItemsByMoId($id),
        ];

        $this->render('mo/print_lmr', $data);
    }

    private function render($view, $data = []) {
        extract($data);
        ob_start();
        include __DIR__ . "/../views/{$view}.php";
        $content = ob_get_clean();
        include __DIR__ . "/../views/layouts/main.php";
    }
}
