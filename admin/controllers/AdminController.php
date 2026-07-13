<?php
namespace App\Controllers;

use App\Models\CustomerModel;
use App\Models\ItemModel;
use App\Models\WarehouseModel;
use App\Helpers\Pagination;
use App\Helpers\CsvExport;
use App\Models\AuditModel;

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
                    AuditModel::log($_SESSION['user_id'], 'CREATE', 'admin', 'Created customer: ' . ($_POST['customer_name'] ?? ''), null, ['customer_name' => $_POST['customer_name'] ?? '', 'customer_code' => $_POST['customer_code'] ?? ''], 'customer', $result);
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
            $oldCustomer = $this->customerModel->getById($id);
            $result = $this->customerModel->update($id, $_POST);
            if ($result) {
                AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Updated customer: ' . ($oldCustomer['customer_name'] ?? ''), $oldCustomer, ['customer_name' => $_POST['customer_name'] ?? '', 'customer_code' => $_POST['customer_code'] ?? ''], 'customer', $id);
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
        $oldCustomer = $this->customerModel->getById($id);
        $this->customerModel->softDelete($id);
        AuditModel::log($_SESSION['user_id'], 'DELETE', 'admin', 'Deleted customer: ' . ($oldCustomer['customer_name'] ?? $id), $oldCustomer, null, 'customer', $id);
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
                    AuditModel::log($_SESSION['user_id'], 'CREATE', 'admin', 'Created item: ' . ($_POST['item_name'] ?? ''), null, ['item_name' => $_POST['item_name'] ?? '', 'item_code' => $_POST['item_code'] ?? ''], 'item', $result);
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
            $oldItem = $this->itemModel->getById($id);
            $result = $this->itemModel->update($id, $_POST);
            if ($result) {
                AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Updated item: ' . ($oldItem['item_name'] ?? ''), $oldItem, ['item_name' => $_POST['item_name'] ?? '', 'item_code' => $_POST['item_code'] ?? ''], 'item', $id);
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
        $oldItem = $this->itemModel->getById($id);
        $this->itemModel->softDelete($id);
        AuditModel::log($_SESSION['user_id'], 'DELETE', 'admin', 'Deleted item: ' . ($oldItem['item_name'] ?? $id), $oldItem, null, 'item', $id);
        $_SESSION['success'] = 'Item deleted successfully';
        header('Location: ?controller=admin&action=items');
        exit;
    }

    public function itemToggleStatus() {
        $id = $_GET['id'] ?? null;
        $oldItem = $this->itemModel->getById($id);
        $this->itemModel->toggleStatus($id);
        AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Toggled item status: ' . ($oldItem['item_name'] ?? $id), $oldItem, null, 'item', $id);
        header('Location: ?controller=admin&action=items');
        exit;
    }

    public function customerToggleStatus() {
        $id = $_GET['id'] ?? null;
        $oldCustomer = $this->customerModel->getById($id);
        $this->customerModel->toggleStatus($id);
        AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Toggled customer status: ' . ($oldCustomer['customer_name'] ?? $id), $oldCustomer, null, 'customer', $id);
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
            $oldCustomer = $this->customerModel->getById($id);
            $result = $this->customerModel->update($id, $_POST);
            if ($result) {
                AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Updated customer (inline): ' . ($oldCustomer['customer_name'] ?? ''), $oldCustomer, ['customer_name' => $_POST['customer_name'] ?? '', 'customer_code' => $_POST['customer_code'] ?? ''], 'customer', $id);
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
                $oldItem = $this->itemModel->getById($id);
                $result = $this->itemModel->update($id, $_POST);
                if ($result) {
                    AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Updated item (inline): ' . ($oldItem['item_name'] ?? ''), $oldItem, ['item_name' => $_POST['item_name'] ?? '', 'item_code' => $_POST['item_code'] ?? '', 'description' => $_POST['description'] ?? ''], 'item', $id);
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
    $filterCustomer = $_GET['filter_customer'] ?? '';
    $filterItem = $_GET['filter_item'] ?? '';
    $filterDate = $_GET['filter_date'] ?? '';

    if ($search) $allPOs = Pagination::filterBySearch($allPOs, $search);

    $allCustomers = array_values(array_unique(array_filter(array_column($allPOs, 'customer_name'))));

    if ($filterCustomer) {
        $allPOs = array_values(array_filter($allPOs, fn($po) => stripos($po['customer_name'] ?? '', $filterCustomer) !== false));
    }
    if ($filterItem) {
        $allPOs = array_values(array_filter($allPOs, function($po) use ($filterItem) {
            $items = $this->warehouseModel->getPurchaseOrderItemsByPOIds([$po['po_id']]);
            foreach (($items[$po['po_id']] ?? []) as $item) {
                if (stripos($item['item_description'] ?? '', $filterItem) !== false) return true;
            }
            return false;
        }));
    }
    if ($filterDate) {
        $allPOs = array_values(array_filter($allPOs, fn($po) => substr($po['date_created'] ?? '', 0, 10) === $filterDate));
    }

    $hasFilter = $search || $filterCustomer || $filterItem || $filterDate;
    if ($hasFilter) {
        $pagination = ['items' => $allPOs, 'page' => 1, 'perPage' => count($allPOs), 'total' => count($allPOs), 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false];
    } else {
        $pagination = Pagination::paginate($allPOs, 10);
    }

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
    $data['filterCustomer'] = $filterCustomer;
    $data['filterItem'] = $filterItem;
    $data['filterDate'] = $filterDate;
    $data['allCustomers'] = $allCustomers;
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
        AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Toggled delivery status #' . $deliveryId, null, ['active_status' => (int)$newStatus], 'delivery', $deliveryId);
        echo json_encode(['success' => true, 'active_status' => (int)$newStatus]);
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

public function deleteDelivery() {
    $deliveryId = $_GET['id'] ?? null;
    if (!$deliveryId) {
        $_SESSION['error'] = 'Missing delivery id';
        header('Location: ?controller=admin&action=delivered');
        exit;
    }

    try {
        $deleted = $this->warehouseModel->deleteDelivery($deliveryId);
        if ($deleted) {
            AuditModel::log($_SESSION['user_id'], 'DELETE', 'admin', 'Deleted delivery #' . $deliveryId, null, ['delivery_id' => $deliveryId], 'delivery', $deliveryId);
            $_SESSION['success'] = 'Delivery deleted and quantities were rolled back.';
        } else {
            $_SESSION['error'] = 'Delivery not found';
        }
    } catch (\Exception $e) {
        $_SESSION['error'] = 'Failed to delete delivery: ' . $e->getMessage();
    }

    header('Location: ?controller=admin&action=delivered');
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
        AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Updated delivery #' . $deliveryId, null, ['dr_number' => $drNumber, 'delivery_date' => $deliveryDate], 'delivery', $deliveryId);
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
        AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Resolved delivery report #' . $reportId, null, ['new_quantity' => $newQuantity, 'new_dr_number' => $newDrNumber], 'delivery_report', $reportId);
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
    $filterCustomer = $_GET['filter_customer'] ?? '';
    $filterItem = $_GET['filter_item'] ?? '';
    $filterLot = $_GET['filter_lot'] ?? '';

    if ($search) $allHistory = Pagination::filterBySearch($allHistory, $search);
    if ($filterCustomer) {
        $allHistory = array_values(array_filter($allHistory, fn($h) => stripos($h['customer_name'] ?? '', $filterCustomer) !== false));
    }
    if ($filterItem) {
        $allHistory = array_values(array_filter($allHistory, fn($h) => stripos($h['item_description'] ?? '', $filterItem) !== false));
    }
    if ($filterLot) {
        $allHistory = array_values(array_filter($allHistory, fn($h) => stripos($h['lot_number'] ?? '', $filterLot) !== false));
    }

    $allCustomers = array_values(array_unique(array_filter(array_column($allHistory, 'customer_name'))));
    $allItems = array_values(array_unique(array_filter(array_column($allHistory, 'item_description'))));
    $allLots = array_values(array_unique(array_filter(array_column($allHistory, 'lot_number'))));

    $hasFilter = $filterCustomer || $filterItem || $filterLot;
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
    $before = $this->warehouseModel->getProductionHistoryById($history_id);
    $result = $this->warehouseModel->editHistoryRecord($history_id, $new_added_quantity, $new_lot, $_SESSION['user_id']);
    if ($result) {
        $after = $this->warehouseModel->getProductionHistoryById($history_id);
        $oldValues = $before ? [
            'previous_quantity' => $before['previous_quantity'] ?? null,
            'added_quantity' => $before['added_quantity'] ?? null,
            'new_quantity' => $before['new_quantity'] ?? null,
            'lot_number' => $before['lot_number'] ?? null,
            'old_added_quantity' => $before['old_added_quantity'] ?? null,
            'old_lot_number' => $before['old_lot_number'] ?? null,
        ] : null;
        $newValues = $after ? [
            'previous_quantity' => $after['previous_quantity'] ?? null,
            'added_quantity' => $after['added_quantity'] ?? null,
            'new_quantity' => $after['new_quantity'] ?? null,
            'lot_number' => $after['lot_number'] ?? null,
            'old_added_quantity' => $after['old_added_quantity'] ?? null,
            'old_lot_number' => $after['old_lot_number'] ?? null,
        ] : null;
        AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Updated production quantity and lot for history #' . $history_id, $oldValues, $newValues, 'production_history', $history_id);
    }
    echo json_encode(['success' => $result]);
    exit;
}

public function deleteProductionHistory() {
    $historyId = $_GET['id'] ?? null;
    if (!$historyId) {
        $_SESSION['error'] = 'Missing history id';
        header('Location: ?controller=admin&action=productionHistory');
        exit;
    }

    try {
        $deleted = $this->warehouseModel->deleteProductionHistory($historyId);
        if ($deleted) {
            AuditModel::log($_SESSION['user_id'], 'DELETE', 'admin', 'Deleted production history #' . $historyId, null, ['history_id' => $historyId], 'production_history', $historyId);
            $_SESSION['success'] = 'Production history deleted and quantities were rolled back.';
        } else {
            $_SESSION['error'] = 'Production history not found';
        }
    } catch (\Exception $e) {
        $_SESSION['error'] = 'Failed to delete production history: ' . $e->getMessage();
    }

    header('Location: ?controller=admin&action=productionHistory');
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
        AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Updated excess notes for #' . $excess_id, null, ['notes' => $notes], 'excess_production', $excess_id);
        echo json_encode(['success' => true]);
        exit;
    }

    public function activityLogs() {
        $auditModel = new AuditModel();
        $filters = [
            'user_id'    => $_GET['user_id'] ?? '',
            'department' => $_GET['department'] ?? '',
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
        $data['logController'] = 'admin';
        $data['departmentLocked'] = false;
        $data['hideDeptColumn'] = false;
        $data['stats'] = [
            'today_count' => AuditModel::getLogStats()['today_count'] ?? 0,
            'by_department' => [],
        ];
        $data['page_title'] = 'Activity Logs';
        $this->render('activity_logs/index', $data);
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