<?php
namespace App\Controllers;

use App\Models\MoModel;
use App\Models\CatalogModel;
use App\Models\AuditModel;

class MoController {
    private $moModel;
    private $catalogModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?controller=auth&action=login');
            exit;
        }

        $allowedDepartments = ['warehouse', 'admin', 'rnd', 'production'];
        $dept = $_SESSION['department'] ?? '';
        if (!in_array($dept, $allowedDepartments)) {
            $_SESSION['error'] = 'You do not have access to Manufacturing Orders.';
            header('Location: ?controller=' . ($dept === 'finance' ? 'finance' : 'admin'));
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
            'items' => [],
            'allItems' => $this->catalogModel->getItems(),
        ];

        $this->render('mo_entry', $data);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=mo&action=index');
            exit;
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
              && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

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
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'MO Number is required.']);
                exit;
            }
            $_SESSION['error'] = 'MO Number is required.';
            header('Location: ?controller=mo&action=create');
            exit;
        }

        if (empty($header['customer_id']) && empty($header['customer_code'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Please select a customer.']);
                exit;
            }
            $_SESSION['error'] = 'Please select a customer.';
            header('Location: ?controller=mo&action=create');
            exit;
        }

        if (empty($header['po_number'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'PO Number is required for LMR printing.']);
                exit;
            }
            $_SESSION['error'] = 'PO Number is required for LMR printing.';
            header('Location: ?controller=mo&action=create');
            exit;
        }

        try {
            $this->moModel->create($header, $items);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => '?controller=mo&action=index']);
                exit;
            }
            $_SESSION['success'] = 'Manufacturing Order created successfully.';
            header('Location: ?controller=mo&action=index');
            exit;
        } catch (\Exception $e) {
            error_log('MoController::store error: ' . $e->getMessage());
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Failed to save MO: ' . $e->getMessage()]);
                exit;
            }
            $_SESSION['error'] = 'Failed to save MO: ' . $e->getMessage();
            header('Location: ?controller=mo&action=create');
            exit;
        }
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=mo&action=index');
            exit;
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
              && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

        $moId = intval($_POST['mo_id'] ?? 0);
        if (!$moId) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid MO ID.']);
                exit;
            }
            $_SESSION['error'] = 'Invalid MO ID.';
            header('Location: ?controller=mo&action=index');
            exit;
        }

        $order = $this->moModel->findById($moId);
        if (!$order) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'MO not found.']);
                exit;
            }
            $_SESSION['error'] = 'MO not found.';
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
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'MO Number is required.']);
                exit;
            }
            $_SESSION['error'] = 'MO Number is required.';
            header('Location: ?controller=mo&action=edit&id=' . $moId);
            exit;
        }

        if (empty($header['customer_id']) && empty($header['customer_code'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Please select a customer.']);
                exit;
            }
            $_SESSION['error'] = 'Please select a customer.';
            header('Location: ?controller=mo&action=edit&id=' . $moId);
            exit;
        }

        if (empty($header['po_number'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'PO Number is required for LMR printing.']);
                exit;
            }
            $_SESSION['error'] = 'PO Number is required for LMR printing.';
            header('Location: ?controller=mo&action=edit&id=' . $moId);
            exit;
        }

        try {
            $this->moModel->update($moId, $header, $items);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => '?controller=mo&action=index']);
                exit;
            }
            $_SESSION['success'] = 'Manufacturing Order updated successfully.';
            header('Location: ?controller=mo&action=index');
            exit;
        } catch (\Exception $e) {
            error_log('MoController::update error: ' . $e->getMessage());
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Failed to update MO: ' . $e->getMessage()]);
                exit;
            }
            $_SESSION['error'] = 'Failed to update MO: ' . $e->getMessage();
            header('Location: ?controller=mo&action=edit&id=' . $moId);
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
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            $_SESSION['error'] = 'Invalid MO.';
            header('Location: ?controller=mo&action=index');
            exit;
        }
        $order = $this->moModel->findById($id);
        if (!$order) {
            $_SESSION['error'] = 'MO not found.';
            header('Location: ?controller=mo&action=index');
            exit;
        }

        $breakdown = $this->moModel->getMoBomBreakdown($id);
        $validation = $this->moModel->validateMoRelease($id);

        $data = [
            'page_title' => 'Confirm Release MO',
            'mo' => $order,
            'items' => $this->moModel->getItemsByMoId($id),
            'breakdown' => $breakdown,
            'validation' => $validation,
            'isAdmin' => ($_SESSION['department'] ?? '') === 'admin',
        ];
        $this->render('mo/release_confirm', $data);
    }

    public function releaseExecute() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=mo&action=index');
            exit;
        }
        $id = intval($_POST['mo_id'] ?? 0);
        if (!$id) {
            $_SESSION['error'] = 'Invalid MO.';
            header('Location: ?controller=mo&action=index');
            exit;
        }
        $order = $this->moModel->findById($id);
        if (!$order) {
            $_SESSION['error'] = 'MO not found.';
            header('Location: ?controller=mo&action=index');
            exit;
        }

        $validation = $this->moModel->validateMoRelease($id);
        $isForceRelease = !empty($_POST['force_release']);
        $forceReason = trim($_POST['force_reason'] ?? '');
        $isAdmin = ($_SESSION['department'] ?? '') === 'admin';

        if (!$validation['valid']) {
            if ($isForceRelease && $isAdmin && !empty($forceReason)) {
                AuditModel::log(
                    $_SESSION['user_id'], 'FORCE_RELEASE', 'mo',
                    'Force released MO ' . $order['mo_number'] . ' despite stock shortages. Reason: ' . $forceReason,
                    null,
                    ['shortages' => $validation['shortages'], 'reason' => $forceReason],
                    'manufacturing_order', $id
                );
            } else {
                $shortageList = '';
                foreach ($validation['shortages'] as $s) {
                    $shortageList .= "\n- " . $s['item_code'] . ' (' . ($s['item_description'] ?? '') . ')';
                    if (!empty($s['reason'])) {
                        $shortageList .= ' — ' . $s['reason'];
                    } else {
                        $shortageList .= ' — Short by ' . number_format($s['short_by'], 2) . ' ' . ($s['uom'] ?? '');
                    }
                }
                $_SESSION['error'] = 'Cannot Release MO-' . htmlspecialchars($order['mo_number']) . ': Lacking Materials' . $shortageList;
                header('Location: ?controller=mo&action=release&id=' . $id);
                exit;
            }
        }

        try {
            \App\Core\BaseModel::beginTransaction();

            $this->moModel->updateStatus($id, 'Released');
            $this->moModel->allocateMaterials($id);

            \App\Core\BaseModel::commit();
        } catch (\Exception $e) {
            \App\Core\BaseModel::rollback();
            $_SESSION['error'] = 'Release failed: ' . $e->getMessage();
            header('Location: ?controller=mo&action=release&id=' . $id);
            exit;
        }

        if (!$isForceRelease) {
            AuditModel::log(
                $_SESSION['user_id'], 'RELEASE', 'mo',
                'Released MO ' . $order['mo_number'],
                null, ['mo_status' => $order['mo_status']],
                'manufacturing_order', $id
            );
        }

        $_SESSION['success'] = 'MO ' . htmlspecialchars($order['mo_number']) . ' released successfully. Material allocations updated.';
        header('Location: ?controller=mo&action=index');
        exit;
    }

    public function cancel() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=mo&action=index');
            exit;
        }
        $id = intval($_POST['mo_id'] ?? $_GET['id'] ?? 0);
        if (!$id) {
            $_SESSION['error'] = 'Invalid MO.';
            header('Location: ?controller=mo&action=index');
            exit;
        }
        $order = $this->moModel->findById($id);
        if (!$order) {
            $_SESSION['error'] = 'MO not found.';
            header('Location: ?controller=mo&action=index');
            exit;
        }

        if ($order['mo_status'] !== 'Released') {
            $_SESSION['error'] = 'Only Released MOs can be cancelled.';
            header('Location: ?controller=mo&action=index');
            exit;
        }

        try {
            \App\Core\BaseModel::beginTransaction();

            $this->moModel->deallocateMaterials($id);
            $this->moModel->updateStatus($id, 'Cancelled');

            \App\Core\BaseModel::commit();
        } catch (\Exception $e) {
            \App\Core\BaseModel::rollback();
            $_SESSION['error'] = 'Cancel failed: ' . $e->getMessage();
            header('Location: ?controller=mo&action=index');
            exit;
        }

        AuditModel::log(
            $_SESSION['user_id'], 'CANCEL', 'mo',
            'Cancelled MO ' . $order['mo_number'] . ' and deallocated materials',
            null, ['mo_status' => 'Released'],
            'manufacturing_order', $id
        );

        $_SESSION['success'] = 'MO ' . htmlspecialchars($order['mo_number']) . ' cancelled. Material allocations released.';
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
            'breakdown' => $this->moModel->getMoBomBreakdown($id),
            'documentId' => 'LMR-' . $order['mo_number'],
        ];

        extract($data);
        include __DIR__ . "/../views/lmr_print.php";
        exit;
    }

    public function getBomBreakdown() {
        header('Content-Type: application/json');
        try {
            $moId = intval($_GET['mo_id'] ?? 0);
            if ($moId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid MO ID']);
                exit;
            }
            $order = $this->moModel->findById($moId);
            if (!$order) {
                http_response_code(404);
                echo json_encode(['error' => 'MO not found']);
                exit;
            }
            $breakdown = $this->moModel->getMoBomBreakdown($moId);
            echo json_encode([
                'mo' => $order,
                'breakdown' => $breakdown,
            ]);
        } catch (\Exception $e) {
            error_log('getBomBreakdown error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to load BOM breakdown']);
        }
        exit;
    }

    public function getBomByItem() {
        header('Content-Type: application/json');
        try {
            $itemId = intval($_GET['item_id'] ?? 0);
            $qty = floatval($_GET['qty'] ?? 0);
            if ($itemId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid item ID']);
                exit;
            }
            if ($qty <= 0) {
                echo json_encode([
                    'bom_code' => null,
                    'batch_qty' => 0,
                    'batches_needed' => 0,
                    'fill_volume' => 0,
                    'uom' => '',
                    'batch_unit_divisor' => 1000,
                    'is_legacy_formula' => 0,
                    'bulk_batch' => 0,
                    'components' => [],
                    'has_shortage' => false,
                    'no_bom' => false,
                    'not_found' => false,
                ]);
                exit;
            }
            $breakdown = $this->moModel->getBomBreakdownForItem($itemId, $qty);
            echo json_encode($breakdown);
        } catch (\Exception $e) {
            error_log('getBomByItem error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to load BOM data']);
        }
        exit;
    }

    public function view() {
        $id = intval($_GET['id'] ?? 0);
        $order = $this->moModel->findById($id);
        if (!$order) {
            $_SESSION['error'] = 'MO not found.';
            header('Location: ?controller=mo&action=index');
            exit;
        }

        $breakdown = $this->moModel->getMoBomBreakdown($id);

        $data = [
            'page_title' => 'MO Details',
            'mo' => $order,
            'items' => $this->moModel->getItemsByMoId($id),
            'breakdown' => $breakdown,
        ];
        $this->render('mo/view', $data);
    }

    private function render($view, $data = []) {
        extract($data);
        ob_start();
        include __DIR__ . "/../views/{$view}.php";
        $content = ob_get_clean();
        include __DIR__ . "/../views/layouts/main.php";
    }
}
