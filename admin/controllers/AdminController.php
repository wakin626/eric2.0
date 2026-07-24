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
        $data['history'] = $this->warehouseModel->getProductionHistory();
        $data['page_title'] = 'Admin Dashboard';
        $this->render('dashboard', $data);
    }

    public function customers() {
        $search = $_GET['search'] ?? '';
        $filters = [];
        if ($search) $filters['search'] = $search;

        $hasFilters = ($search !== '');
        if ($hasFilters) {
            $allCustomers = $this->customerModel->getAllFiltered($filters);
            $data['customers'] = $allCustomers;
            $data['page'] = 1;
            $data['totalPages'] = 1;
            $data['total'] = count($allCustomers);
        } else {
            $allCustomers = $this->customerModel->getAll(false);
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
        $search = $_GET['search'] ?? '';
        $filters = [];
        if ($search) $filters['search'] = $search;
        $allCustomers = $this->customerModel->getAllFiltered($filters);

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
        $search = $_GET['search'] ?? '';
        $filters = [];
        if ($search) $filters['search'] = $search;
        $allCustomers = $this->customerModel->getAllFiltered($filters);

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
            try {
                $oldCustomer = $this->customerModel->getById($id);
                $result = $this->customerModel->update($id, $_POST);
                if ($result) {
                    AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Updated customer: ' . ($oldCustomer['customer_name'] ?? ''), $oldCustomer, ['customer_name' => $_POST['customer_name'] ?? '', 'customer_code' => $_POST['customer_code'] ?? ''], 'customer', $id);
                    $_SESSION['success'] = 'Customer updated successfully';
                    header('Location: ?controller=admin&action=customers');
                    exit;
                }
            } catch (\Exception $e) {
                error_log('customerEdit error: ' . $e->getMessage());
                $_SESSION['error'] = 'Failed to update customer: ' . $e->getMessage();
                header('Location: ?controller=admin&action=customers');
                exit;
            }
        }
        $data['page_title'] = 'Edit Customer';
        $this->render('customers/form', $data);
    }

    public function customerDelete() {
        $id = $_GET['id'] ?? null;
        try {
            $oldCustomer = $this->customerModel->getById($id);
            $this->customerModel->softDelete($id);
            AuditModel::log($_SESSION['user_id'], 'DELETE', 'admin', 'Deleted customer: ' . ($oldCustomer['customer_name'] ?? $id), $oldCustomer, null, 'customer', $id);
            $_SESSION['success'] = 'Customer deleted successfully';
        } catch (\Exception $e) {
            error_log('customerDelete error: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete customer: ' . $e->getMessage();
        }
        header('Location: ?controller=admin&action=customers');
        exit;
    }

    public function items() {
        $search = $_GET['search'] ?? '';
        $customerFilter = $_GET['customer_id'] ?? '';
        $filters = [];
        if ($search) $filters['search'] = $search;
        if ($customerFilter) $filters['customer_id'] = $customerFilter;

        $hasFilters = ($search !== '' || $customerFilter !== '');
        if ($hasFilters) {
            $allItems = $this->itemModel->getAllFiltered($filters);
            $data['items'] = $allItems;
            $data['page'] = 1;
            $data['totalPages'] = 1;
            $data['total'] = count($allItems);
        } else {
            $allItems = $this->itemModel->getAll(false);
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
        $search = $_GET['search'] ?? '';
        $customerFilter = $_GET['customer_id'] ?? '';
        $filters = [];
        if ($search) $filters['search'] = $search;
        if ($customerFilter) $filters['customer_id'] = $customerFilter;
        $allItems = $this->itemModel->getAllFiltered($filters);

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
        $search = $_GET['search'] ?? '';
        $customerFilter = $_GET['customer_id'] ?? '';
        $filters = [];
        if ($search) $filters['search'] = $search;
        if ($customerFilter) $filters['customer_id'] = $customerFilter;
        $allItems = $this->itemModel->getAllFiltered($filters);

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
                    AuditModel::log($_SESSION['user_id'], 'CREATE', 'admin', 'Created item: ' . ($_POST['item_description'] ?? ''), null, ['item_code' => $_POST['item_code'] ?? '', 'description' => $_POST['item_description'] ?? ''], 'item', $result);
                    $_SESSION['success'] = 'Item created successfully';
                    $search = $_POST['filter_search'] ?? '';
                    $customerFilter = $_POST['filter_customer_id'] ?? '';
                    $redirect = '?controller=admin&action=items';
                    if ($search !== '' || $customerFilter !== '') {
                        $redirect .= '&search=' . urlencode($search) . '&customer_id=' . urlencode($customerFilter);
                    }
                    header('Location: ' . $redirect);
                    exit;
                }
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                $search = $_POST['filter_search'] ?? '';
                $customerFilter = $_POST['filter_customer_id'] ?? '';
                $redirect = '?controller=admin&action=items';
                if ($search !== '' || $customerFilter !== '') {
                    $redirect .= '&search=' . urlencode($search) . '&customer_id=' . urlencode($customerFilter);
                }
                header('Location: ' . $redirect);
                exit;
            }
        }
        $data['customers'] = $this->customerModel->getAll(false);
        $data['page_title'] = 'Add Item';
        $this->render('items/form', $data);
    }

    public function itemEdit() {
        $id = $_GET['id'] ?? null;
        $search = $_GET['search'] ?? '';
        $customerFilter = $_GET['customer_id'] ?? '';
        $data['item'] = $this->itemModel->getById($id);
        if (!$data['item']) {
            $_SESSION['error'] = 'Item not found';
            $redirect = '?controller=admin&action=items';
            if ($search !== '' || $customerFilter !== '') {
                $redirect .= '&search=' . urlencode($search) . '&customer_id=' . urlencode($customerFilter);
            }
            header('Location: ' . $redirect);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $oldItem = $this->itemModel->getById($id);
            $result = $this->itemModel->update($id, $_POST);
            if ($result) {
                AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Updated item: ' . ($oldItem['item_description'] ?? ''), $oldItem, ['item_code' => $_POST['item_code'] ?? '', 'description' => $_POST['item_description'] ?? ''], 'item', $id);
                $_SESSION['success'] = 'Item updated successfully';
                $redirect = '?controller=admin&action=items';
                if ($search !== '' || $customerFilter !== '') {
                    $redirect .= '&search=' . urlencode($search) . '&customer_id=' . urlencode($customerFilter);
                }
                header('Location: ' . $redirect);
                exit;
            }
        }
        $data['customers'] = $this->customerModel->getAll(false);
        $data['page_title'] = 'Edit Item';
        $this->render('items/form', $data);
    }

    public function itemDelete() {
        $id = $_GET['id'] ?? null;
        try {
            $oldItem = $this->itemModel->getById($id);
            $this->itemModel->softDelete($id);
            AuditModel::log($_SESSION['user_id'], 'DELETE', 'admin', 'Deleted item: ' . ($oldItem['item_description'] ?? $id), $oldItem, null, 'item', $id);
            $_SESSION['success'] = 'Item deleted successfully';
        } catch (\Exception $e) {
            error_log('itemDelete error: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to delete item: ' . $e->getMessage();
        }
        $search = $_GET['search'] ?? '';
        $customerFilter = $_GET['customer_id'] ?? '';
        $redirect = '?controller=admin&action=items';
        if ($search !== '' || $customerFilter !== '') {
            $redirect .= '&search=' . urlencode($search) . '&customer_id=' . urlencode($customerFilter);
        }
        header('Location: ' . $redirect);
        exit;
    }

    public function itemToggleStatus() {
        $id = $_GET['id'] ?? null;
        try {
            $oldItem = $this->itemModel->getById($id);
            $this->itemModel->toggleStatus($id);
            $newStatus = $oldItem['status'] ? 'Inactive' : 'Active';
            AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Toggled item status to ' . $newStatus . ': ' . ($oldItem['item_description'] ?? $id), $oldItem, null, 'item', $id);
            $_SESSION['success'] = 'Item status changed to ' . $newStatus;
        } catch (\Exception $e) {
            error_log('itemToggleStatus error: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to update status: ' . $e->getMessage();
        }
        $redirect = '?controller=admin&action=items';
        $search = $_GET['search'] ?? '';
        $customerFilter = $_GET['customer_id'] ?? '';
        if ($search !== '' || $customerFilter !== '') {
            $redirect .= '&search=' . urlencode($search) . '&customer_id=' . urlencode($customerFilter);
        }
        header('Location: ' . $redirect);
        exit;
    }

    public function customerToggleStatus() {
        $id = $_GET['id'] ?? null;
        try {
            $oldCustomer = $this->customerModel->getById($id);
            $this->customerModel->toggleStatus($id);
            AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Toggled customer status: ' . ($oldCustomer['customer_name'] ?? $id), $oldCustomer, null, 'customer', $id);
            $_SESSION['success'] = 'Customer status updated';
        } catch (\Exception $e) {
            error_log('customerToggleStatus error: ' . $e->getMessage());
            $_SESSION['error'] = 'Failed to update status: ' . $e->getMessage();
        }
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
            try {
                $id = $_POST['customer_id'] ?? null;
                $oldCustomer = $this->customerModel->getById($id);
                $result = $this->customerModel->update($id, $_POST);
                if ($result) {
                    AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Updated customer (inline): ' . ($oldCustomer['customer_name'] ?? ''), $oldCustomer, ['customer_name' => $_POST['customer_name'] ?? '', 'customer_code' => $_POST['customer_code'] ?? ''], 'customer', $id);
                    $_SESSION['success'] = 'Customer updated successfully';
                }
            } catch (\Exception $e) {
                error_log('customerUpdate error: ' . $e->getMessage());
                $_SESSION['error'] = 'Failed to update customer: ' . $e->getMessage();
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
                    AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Updated item (inline): ' . ($oldItem['item_description'] ?? ''), $oldItem, ['item_code' => $_POST['item_code'] ?? '', 'description' => $_POST['item_description'] ?? ''], 'item', $id);
                    $_SESSION['success'] = 'Item updated successfully';
                }
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
        }
        $search = $_POST['filter_search'] ?? '';
        $customerFilter = $_POST['filter_customer_id'] ?? '';
        $redirect = '?controller=admin&action=items';
        if ($search !== '' || $customerFilter !== '') {
            $redirect .= '&search=' . urlencode($search) . '&customer_id=' . urlencode($customerFilter);
        }
        header('Location: ' . $redirect);
        exit;
    }

public function purchaseOrders() {
    $search = $_GET['search'] ?? '';
    $filterCustomer = $_GET['filter_customer'] ?? '';
    $filterItem = $_GET['filter_item'] ?? '';
    $filterDate = $_GET['filter_date'] ?? '';

    $hasFilter = $search || $filterCustomer || $filterItem || $filterDate;
    if ($hasFilter) {
        $filters = [];
        if ($search) $filters['search'] = $search;
        if ($filterCustomer) $filters['customer_name'] = $filterCustomer;
        if ($filterItem) $filters['item_description'] = $filterItem;
        if ($filterDate) $filters['date'] = $filterDate;
        $allPOs = $this->warehouseModel->getPurchaseOrdersFiltered($filters);
        $allCustomers = array_values(array_unique(array_filter(array_column($allPOs, 'customer_name'))));
        $pagination = ['items' => $allPOs, 'page' => 1, 'perPage' => count($allPOs), 'total' => count($allPOs), 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false];
    } else {
        $allPOs = $this->warehouseModel->getPurchaseOrders();
        $allCustomers = array_values(array_unique(array_filter(array_column($allPOs, 'customer_name'))));
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
    $search = $_GET['search'] ?? '';
    $filterCustomer = $_GET['filter_customer'] ?? '';
    $filterItem = $_GET['filter_item'] ?? '';
    $filterDR = $_GET['filter_dr'] ?? '';
    $filterDate = $_GET['filter_date'] ?? '';
    $filterPo = $_GET['filter_po'] ?? '';
    $filterDeliveredBy = $_GET['filter_delivered_by'] ?? '';
    $filterType = $_GET['filter_type'] ?? '';
    $filterReports = isset($_GET['filter_reports']) && $_GET['filter_reports'] === '1';

    $hasFilter = $search || $filterCustomer || $filterItem || $filterDR || $filterDate || $filterPo || $filterDeliveredBy || $filterType || $filterReports;
    if ($hasFilter) {
        $filters = [];
        if ($search) $filters['search'] = $search;
        if ($filterCustomer) $filters['customer_name'] = $filterCustomer;
        if ($filterItem) $filters['item_description'] = $filterItem;
        if ($filterDR) $filters['dr_number'] = $filterDR;
        if ($filterDate) $filters['delivery_date'] = $filterDate;
        if ($filterPo) $filters['po_number'] = $filterPo;
        if ($filterDeliveredBy) $filters['delivered_by'] = $filterDeliveredBy;
        if ($filterType) $filters['production_type'] = $filterType;
        if ($filterReports) $filters['has_reports'] = true;
        $allDeliveries = $this->warehouseModel->getDeliveriesFiltered($filters);
    } else {
        $allDeliveries = $this->warehouseModel->getDeliveries();
    }

    $reportedCount = 0;
    foreach ($allDeliveries as $d) {
        if (($d['remarks_type'] ?? '') === 'report') $reportedCount++;
    }

    usort($allDeliveries, function($a, $b) {
        $aReported = ($a['remarks_type'] ?? '') === 'report' ? 1 : 0;
        $bReported = ($b['remarks_type'] ?? '') === 'report' ? 1 : 0;
        if ($aReported !== $bReported) return $bReported - $aReported;
        return strtotime($b['date_created'] ?? '') - strtotime($a['date_created'] ?? '');
    });

    $allCustomers = array_values(array_unique(array_filter(array_column($allDeliveries, 'customer_name'))));
    $allItems = [];
    foreach ($allDeliveries as $d) {
        $lotItems = json_decode($d['lot_items'] ?? '[]', true);
        if (is_array($lotItems)) {
            foreach ($lotItems as $li) { if (!empty($li['item_description'])) $allItems[] = $li['item_description']; }
        } elseif (!empty($d['item_description'])) {
            $allItems[] = $d['item_description'];
        }
    }
    $allItems = array_values(array_unique($allItems));
    $allDRs = array_values(array_unique(array_filter(array_column($allDeliveries, 'dr_number'))));
    $allPOs = array_values(array_unique(array_filter(array_column($allDeliveries, 'customer_po_number'))));
    $allDeliveredBy = array_values(array_unique(array_filter(array_column($allDeliveries, 'delivered_by_name'))));

    if ($hasFilter) {
        $pagination = ['items' => $allDeliveries, 'page' => 1, 'perPage' => count($allDeliveries), 'total' => count($allDeliveries), 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false];
    } else {
        $pagination = Pagination::paginate($allDeliveries, 20);
    }

    $data['deliveries'] = $pagination['items'];
    $deliveryIds = array_column($pagination['items'], 'delivery_id');
        $receiptsMap = [];
        if (!empty($deliveryIds)) {
            $placeholders = implode(',', array_fill(0, count($deliveryIds), '?'));
            $conn = $this->warehouseModel::getConnection();
            $stmt = $conn->prepare("SELECT * FROM delivery_receipts WHERE delivery_id IN ($placeholders) AND `remove` = 0 ORDER BY date_created ASC");
            $stmt->execute($deliveryIds);
            foreach ($stmt->fetchAll() as $r) {
                $receiptsMap[$r['delivery_id']][] = $r;
            }
        }
        $data['receipts_map'] = $receiptsMap;

    $data['page'] = $pagination['page'];
    $data['totalPages'] = $pagination['totalPages'];
    $data['total'] = $pagination['total'];
    $data['search'] = $search;
    $data['filterCustomer'] = $filterCustomer;
    $data['filterItem'] = $filterItem;
    $data['filterDR'] = $filterDR;
    $data['filterDate'] = $filterDate;
    $data['filterPo'] = $filterPo;
    $data['filterDeliveredBy'] = $filterDeliveredBy;
    $data['filterType'] = $filterType;
    $data['filterReports'] = $filterReports;
    $data['reportedCount'] = $reportedCount;
    $data['allCustomers'] = $allCustomers;
    $data['allItems'] = $allItems;
    $data['allDRs'] = $allDRs;
    $data['allPOs'] = $allPOs;
    $data['allDeliveredBy'] = $allDeliveredBy;
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
            http_response_code(400);
            echo json_encode(['error' => 'Missing delivery_id']);
            exit;
        }
        $newStatus = $this->warehouseModel->toggleDeliveryStatus($deliveryId);
        AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Toggled delivery status #' . $deliveryId, null, ['active_status' => (int)$newStatus], 'delivery', $deliveryId);
        echo json_encode(['success' => true, 'active_status' => (int)$newStatus]);
    } catch (\Exception $e) {
        error_log('toggleDeliveryStatus error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to toggle delivery status']);
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
    try {
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
            http_response_code(400);
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
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        } else {
            AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Updated delivery #' . $deliveryId, null, ['dr_number' => $drNumber, 'delivery_date' => $deliveryDate], 'delivery', $deliveryId);
            echo json_encode(['success' => true]);
        }
    } catch (\Exception $e) {
        error_log('updateDelivery error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update delivery']);
    }
    exit;
}

public function resolveDeliveryReport() {
    header('Content-Type: application/json');
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        $reportId = $_POST['report_id'] ?? null;
        $newQuantity = $_POST['new_quantity'] ?? null;
        $newDrNumber = trim($_POST['new_dr_number'] ?? '');

        if (!$reportId) {
            http_response_code(400);
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
            http_response_code(404);
            echo json_encode(['error' => 'Report not found']);
        }
    } catch (\Exception $e) {
        error_log('resolveDeliveryReport error: ' . $e->getMessage());
        http_response_code(500);
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
    $search = $_GET['search'] ?? '';
    $filterCustomer = $_GET['filter_customer'] ?? '';
    $filterItem = $_GET['filter_item'] ?? '';
    $filterLot = $_GET['filter_lot'] ?? '';
    $filterPo = $_GET['filter_po'] ?? '';
    $filterDateFrom = $_GET['filter_date_from'] ?? '';
    $filterDateTo = $_GET['filter_date_to'] ?? '';
    $filterReports = isset($_GET['filter_reports']) && $_GET['filter_reports'] === '1';

    $hasFilter = $search || $filterCustomer || $filterItem || $filterLot || $filterPo || $filterDateFrom || $filterDateTo || $filterReports;
    if ($hasFilter) {
        $filters = [];
        if ($search) $filters['search'] = $search;
        if ($filterCustomer) $filters['customer_name'] = $filterCustomer;
        if ($filterItem) $filters['item_description'] = $filterItem;
        if ($filterLot) $filters['lot_number'] = $filterLot;
        if ($filterPo) $filters['po_number'] = $filterPo;
        if ($filterDateFrom) $filters['date_from'] = $filterDateFrom;
        if ($filterDateTo) $filters['date_to'] = $filterDateTo;
        if ($filterReports) $filters['has_reports'] = true;
        $allHistory = $this->warehouseModel->getProductionHistoryFiltered($filters);
    } else {
        $allHistory = $this->warehouseModel->getProductionHistory();
    }

    $reportsCount = 0;
    foreach ($allHistory as $h) {
        if (($h['report_status'] ?? '') === 'pending') $reportsCount++;
    }

    usort($allHistory, function($a, $b) {
        $aReported = ($a['report_status'] ?? '') === 'pending' ? 1 : 0;
        $bReported = ($b['report_status'] ?? '') === 'pending' ? 1 : 0;
        if ($aReported !== $bReported) return $bReported - $aReported;
        return strtotime($b['date_created'] ?? '') - strtotime($a['date_created'] ?? '');
    });

    $allCustomers = array_values(array_unique(array_filter(array_column($allHistory, 'customer_name'))));
    $allItems = array_values(array_unique(array_filter(array_column($allHistory, 'item_description'))));
    $allLots = array_values(array_unique(array_filter(array_column($allHistory, 'lot_number'))));
    $allPos = array_values(array_unique(array_filter(array_column($allHistory, 'customer_po_number'))));

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
    $data['filterPo'] = $filterPo;
    $data['filterDateFrom'] = $filterDateFrom;
    $data['filterDateTo'] = $filterDateTo;
    $data['filterReports'] = $filterReports;
    $data['allCustomers'] = $allCustomers;
    $data['allItems'] = $allItems;
    $data['allLots'] = $allLots;
    $data['allPos'] = $allPos;
    $data['reportsCount'] = $reportsCount;
    $data['deliveryReportsCount'] = $this->warehouseModel->getDeliveryReportsCount();
    $data['page_title'] = 'Production History';
    $this->render('production_history/index', $data);
}

public function editHistoryRecord() {
    header('Content-Type: application/json');
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        $history_id = $_POST['history_id'] ?? null;
        $new_added_quantity = intval($_POST['new_added_quantity'] ?? 0);
        $new_lot = trim($_POST['new_lot_number'] ?? '');
        if (!$history_id || $new_added_quantity <= 0 || empty($new_lot)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
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
            echo json_encode(['success' => true]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'History record not found']);
        }
    } catch (\Exception $e) {
        error_log('editHistoryRecord error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update history record']);
    }
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
        $this->warehouseModel->syncExcessProduction();
        $data['excess'] = $this->warehouseModel->getAllExcess($filters);
        $data['advance'] = $this->warehouseModel->getAllAdvanceProduction($filters);
        $data['customers'] = $this->warehouseModel->getCustomers();
        $data['page_title'] = 'Excess Production';
        $this->render('excess_production/index', $data);
    }

    public function updateExcessNotes() {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            $excess_id = $_POST['excess_id'] ?? null;
            $notes = $_POST['notes'] ?? '';
            if (!$excess_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing excess_id']);
                exit;
            }
            $this->warehouseModel->updateExcessNotes($excess_id, $notes);
            AuditModel::log($_SESSION['user_id'], 'UPDATE', 'admin', 'Updated excess notes for #' . $excess_id, null, ['notes' => $notes], 'excess_production', $excess_id);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('updateExcessNotes error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update notes']);
        }
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

    public function getLotsByPOItem() {
        header('Content-Type: application/json');
        $poiId = $_GET['poi_id'] ?? null;
        if (!$poiId) {
            echo json_encode([]);
            exit;
        }
        $lots = $this->warehouseModel->getLotsByPOItem($poiId);
        echo json_encode($lots);
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