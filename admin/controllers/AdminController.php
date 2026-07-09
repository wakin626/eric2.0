<?php
namespace App\Controllers;

use App\Models\CustomerModel;
use App\Models\ItemModel;
use App\Models\WarehouseModel;
use App\Helpers\Pagination;
use App\Helpers\CsvExport;

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
        $data['purchase_orders'] = $this->warehouseModel->getActivePOsForDashboard(5);
        $poIds = array_column($data['purchase_orders'], 'po_id');
        $data['po_items_map'] = $this->warehouseModel->getPurchaseOrderItemsByPOIds($poIds);

        $allPoiIds = [];
        foreach ($data['po_items_map'] as $items) {
            foreach ($items as $item) {
                $allPoiIds[] = $item['poi_id'];
            }
        }
        $rawNormalConsumption = $this->warehouseModel->getAdvanceConsumptionByNormalPoiIds($allPoiIds);
        $normalConsumptionByPoi = [];
        foreach ($rawNormalConsumption as $cr) {
            $normalConsumptionByPoi[$cr['normal_poi_id']][] = $cr;
        }
        $data['normal_consumption_records'] = $normalConsumptionByPoi;

        $data['deliveries'] = $this->warehouseModel->getDeliveries();
        $data['deliveryReportsCount'] = $this->warehouseModel->getDeliveryReportsCount();
        $data['reportsCount'] = $this->warehouseModel->getProductionReportsCount();
        $data['page_title'] = 'Admin Dashboard';
        $this->render('dashboard', $data);
    }

    public function customers() {
        $allCustomers = $this->customerModel->getAll(false);
        $search = $_GET['search'] ?? '';
        if ($search) $allCustomers = Pagination::filterBySearch($allCustomers, $search);

        $hasFilters = ($search !== '');
        if ($hasFilters) {
            $data['customers'] = $allCustomers;
            $data['page'] = 1;
            $data['totalPages'] = 1;
            $data['total'] = count($allCustomers);
        } else {
            $pagination = Pagination::paginate($allCustomers, 10);
            $data['customers'] = $pagination['items'];
            $data['page'] = $pagination['page'];
            $data['totalPages'] = $pagination['totalPages'];
            $data['total'] = $pagination['total'];
        }
        $data['search'] = $search;
        $data['deliveryReportsCount'] = $this->warehouseModel->getDeliveryReportsCount();
        $data['reportsCount'] = $this->warehouseModel->getProductionReportsCount();
        $data['page_title'] = 'Customer Management';
        $this->render('customers/index', $data);
    }

    public function customersExport() {
        $allCustomers = $this->customerModel->getAll(false);
        $search = $_GET['search'] ?? '';
        if ($search) $allCustomers = Pagination::filterBySearch($allCustomers, $search);

        $headers = ['Code', 'Name', 'Delivery Address', 'TIN', 'Terms'];
        $rows = [];
        foreach ($allCustomers as $c) {
            $terms = $c['customer_terms'] ?? '';
            $termsDisplay = $terms !== '' && $terms !== '0' ? $terms . (is_numeric($terms) ? ' days' : '') : '-';
            $rows[] = [
                $c['customer_code'],
                $c['customer_name'],
                $c['customer_address'] ?? '-',
                $c['customer_tin'] ?? '-',
                $termsDisplay
            ];
        }
        CsvExport::export('customers_' . date('Y-m-d') . '.csv', $headers, $rows);
    }

    public function customersPrint() {
        $allCustomers = $this->customerModel->getAll(false);
        $search = $_GET['search'] ?? '';
        if ($search) $allCustomers = Pagination::filterBySearch($allCustomers, $search);

        $data['customers'] = $allCustomers;
        $data['search'] = $search;
        $data['total'] = count($allCustomers);
        $data['pageTitle'] = 'Customer List';
        extract($data);
        include __DIR__ . "/../views/customers/print.php";
        exit;
    }

    public function customerCreate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $result = $this->customerModel->create($_POST);
                if ($result) {
                    $_SESSION['success'] = 'Customer created successfully';
                    header('Location: ?controller=admin&action=customers');
                    exit;
                }
            } catch (\PDOException $e) {
                $_SESSION['error'] = $this->getDbErrorMessage($e, 'customer_code', 'Customer code');
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
        $search = $_GET['search'] ?? '';
        $customerFilter = $_GET['customer_id'] ?? '';
        if ($search) $allItems = Pagination::filterBySearch($allItems, $search);
        if ($customerFilter) {
            $allItems = array_filter($allItems, function($item) use ($customerFilter) {
                return isset($item['customer_id']) && $item['customer_id'] == $customerFilter;
            });
            $allItems = array_values($allItems);
        }

        $hasFilters = ($search !== '' || $customerFilter !== '');
        if ($hasFilters) {
            $data['items'] = $allItems;
            $data['page'] = 1;
            $data['totalPages'] = 1;
            $data['total'] = count($allItems);
        } else {
            $pagination = Pagination::paginate($allItems, 10);
            $data['items'] = $pagination['items'];
            $data['page'] = $pagination['page'];
            $data['totalPages'] = $pagination['totalPages'];
            $data['total'] = $pagination['total'];
        }
        $data['customers'] = $this->customerModel->getWithItems();
        $data['allCustomers'] = $this->customerModel->getAll(false);
        $data['search'] = $search;
        $data['customerFilter'] = $customerFilter;
        $data['page_title'] = 'Item Management';
        $this->render('items/index', $data);
    }

    public function itemsExport() {
        $allItems = $this->itemModel->getAll(false);
        $search = $_GET['search'] ?? '';
        $customerFilter = $_GET['customer_id'] ?? '';
        if ($search) $allItems = Pagination::filterBySearch($allItems, $search);
        if ($customerFilter) {
            $allItems = array_filter($allItems, function($item) use ($customerFilter) {
                return isset($item['customer_id']) && $item['customer_id'] == $customerFilter;
            });
            $allItems = array_values($allItems);
        }

        $headers = ['Code', 'Description', 'Customer', 'UOM', 'Conversion'];
        $rows = [];
        foreach ($allItems as $item) {
            $rows[] = [
                $item['item_code'],
                $item['item_description'],
                $item['customer_name'] ?? '-',
                $item['item_uom'],
                $item['uom_conversion'] ?? '-'
            ];
        }
        CsvExport::export('items_' . date('Y-m-d') . '.csv', $headers, $rows);
    }

    public function itemsPrint() {
        $allItems = $this->itemModel->getAll(false);
        $search = $_GET['search'] ?? '';
        $customerFilter = $_GET['customer_id'] ?? '';
        if ($search) $allItems = Pagination::filterBySearch($allItems, $search);
        if ($customerFilter) {
            $allItems = array_filter($allItems, function($item) use ($customerFilter) {
                return isset($item['customer_id']) && $item['customer_id'] == $customerFilter;
            });
            $allItems = array_values($allItems);
        }

        $data['items'] = $allItems;
        $data['search'] = $search;
        $data['customerFilter'] = $customerFilter;
        $data['total'] = count($allItems);
        $data['pageTitle'] = 'Item List';
        extract($data);
        include __DIR__ . "/../views/items/print.php";
        exit;
    }

    public function itemCreate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $result = $this->itemModel->create($_POST);
                if ($result) {
                    $_SESSION['success'] = 'Item created successfully';
                    header('Location: ?controller=admin&action=items');
                    exit;
                }
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: ?controller=admin&action=items');
                exit;
            }
        }
        $data['customers'] = $this->customerModel->getAll(false);
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
        $data['customers'] = $this->customerModel->getAll(false);
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

    private function getDbErrorMessage(\PDOException $e, $indexName, $fieldLabel) {
        $errorCode = $e->errorInfo[1] ?? null;
        $errorMessage = $e->getMessage();
        if ($errorCode === 1062) {
            return "$fieldLabel already exists. Please try a new value.";
        }
        return "Database error: $errorMessage";
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
            try {
                $id = $_POST['item_id'] ?? null;
                $result = $this->itemModel->update($id, $_POST);
                if ($result) {
                    $_SESSION['success'] = 'Item updated successfully';
                }
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
        }
        header('Location: ?controller=admin&action=items');
        exit;
    }

public function purchaseOrders() {
    $allPOs = $this->warehouseModel->getPurchaseOrders();
    $search = $_GET['search'] ?? '';
    if ($search) $allPOs = Pagination::filterBySearch($allPOs, $search);
    $pagination = Pagination::paginate($allPOs, 10);
    $poIds = array_column($pagination['items'], 'po_id');
    $data['allPOs'] = $pagination['items'];
    $data['po_items_map'] = $this->warehouseModel->getPurchaseOrderItemsByPOIds($poIds);

    // Get advance consumption records for advance PO items on this page
    $allPoiIds = [];
    foreach ($data['po_items_map'] as $items) {
        foreach ($items as $item) {
            $allPoiIds[] = $item['poi_id'];
        }
    }
    $rawConsumption = $this->warehouseModel->getAdvanceConsumptionByPoiIds($allPoiIds);
    $consumptionByPoi = [];
    foreach ($rawConsumption as $cr) {
        $consumptionByPoi[$cr['advance_poi_id']][] = $cr;
    }
    $data['consumption_records'] = $consumptionByPoi;

    // Get advance consumption records for normal PO items (reverse lookup)
    $rawNormalConsumption = $this->warehouseModel->getAdvanceConsumptionByNormalPoiIds($allPoiIds);
    $normalConsumptionByPoi = [];
    foreach ($rawNormalConsumption as $cr) {
        $normalConsumptionByPoi[$cr['normal_poi_id']][] = $cr;
    }
    $data['normal_consumption_records'] = $normalConsumptionByPoi;

    $data['page'] = $pagination['page'];
    $data['totalPages'] = $pagination['totalPages'];
    $data['total'] = $pagination['total'];
    $data['search'] = $search;
    $data['deliveryReportsCount'] = $this->warehouseModel->getDeliveryReportsCount();
    $data['reportsCount'] = $this->warehouseModel->getProductionReportsCount();
    $data['page_title'] = 'Customer PO';
    $this->render('purchase_orders/index', $data);
}

public function delivered() {
    $data['deliveries'] = $this->warehouseModel->getDeliveries();
    $data['reportedCount'] = $this->warehouseModel->getReportedRemarksCount();
    $data['deliveryReportsCount'] = $this->warehouseModel->getDeliveryReportsCount();
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
    $lotChangesJson = $_POST['lot_changes'] ?? '[]';

    if (!$deliveryId) {
        echo json_encode(['error' => 'Missing delivery_id']);
        exit;
    }

    $lotChanges = json_decode($lotChangesJson, true);
    if (!is_array($lotChanges)) $lotChanges = [];

    $result = $this->warehouseModel->updateDelivery($deliveryId, [
        'dr_number' => $drNumber,
        'delivery_date' => $deliveryDate,
        'lot_changes' => $lotChanges
    ]);

    if (is_array($result) && isset($result['success']) && !$result['success']) {
        echo json_encode(['error' => $result['error']]);
    } else {
        echo json_encode(['success' => true]);
    }
    exit;
}

public function resolveDeliveryReport() {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    $reportId = $_POST['report_id'] ?? null;
    $newQuantity = $_POST['new_quantity'] ?? null;
    $newDrNumber = trim($_POST['new_dr_number'] ?? '');

    if (!$reportId) {
        echo json_encode(['error' => 'Missing report_id']);
        exit;
    }

    $result = $this->warehouseModel->resolveDeliveryReport(
        $reportId,
        $newQuantity !== null ? intval($newQuantity) : null,
        $_SESSION['user_id'],
        $newDrNumber ?: null
    );
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to resolve report']);
    }
    exit;
}

public function getDeliveryReports() {
    header('Content-Type: application/json');
    $deliveryId = $_GET['delivery_id'] ?? null;
    if (!$deliveryId) {
        echo json_encode([]);
        exit;
    }
    $reports = $this->warehouseModel->getDeliveryReportsByDeliveryId($deliveryId);
    echo json_encode($reports);
    exit;
}

public function productionHistory() {
    $allHistory = $this->warehouseModel->getProductionHistory();
    $search = $_GET['search'] ?? '';
    if ($search) $allHistory = Pagination::filterBySearch($allHistory, $search);
    $pagination = Pagination::paginate($allHistory, 10);
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
    $data['reportsCount'] = $this->warehouseModel->getProductionReportsCount();
    $data['deliveryReportsCount'] = $this->warehouseModel->getDeliveryReportsCount();
    $data['page_title'] = 'Production History';
    $this->render('production_history/index', $data);
}

public function editHistoryRecord() {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    $history_id = $_POST['history_id'] ?? null;
    $new_added_quantity = intval($_POST['new_added_quantity'] ?? 0);
    $new_lot = trim($_POST['new_lot_number'] ?? '');
    if (!$history_id || $new_added_quantity <= 0 || empty($new_lot)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    $result = $this->warehouseModel->editHistoryRecord($history_id, $new_added_quantity, $new_lot, $_SESSION['user_id']);
    echo json_encode(['success' => $result]);
    exit;
}

    public function excessProduction() {
        $filters = [];
        if (!empty($_GET['customer_id'])) $filters['customer_id'] = $_GET['customer_id'];
        if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
        $data['excess'] = $this->warehouseModel->getAllExcess($filters);
        $data['customers'] = $this->warehouseModel->getCustomers();
        $data['page_title'] = 'Excess Production';
        $this->render('excess_production/index', $data);
    }

    public function updateExcessNotes() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false]);
            exit;
        }
        $excess_id = $_POST['excess_id'] ?? null;
        $notes = $_POST['notes'] ?? '';
        if (!$excess_id) {
            echo json_encode(['success' => false, 'message' => 'Missing excess_id']);
            exit;
        }
        $this->warehouseModel->updateExcessNotes($excess_id, $notes);
        echo json_encode(['success' => true]);
        exit;
    }

    private function render($view, $data = []) {
        $data['reportedCount'] = $this->warehouseModel->getReportedRemarksCount();
        $data['reportsCount'] = $this->warehouseModel->getProductionReportsCount();
        extract($data);
        ob_start();
        include __DIR__ . "/../views/{$view}.php";
        $content = ob_get_clean();
        include __DIR__ . "/../views/layouts/main.php";
    }
}