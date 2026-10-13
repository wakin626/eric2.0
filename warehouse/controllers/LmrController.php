<?php
namespace App\Controllers;

use App\Models\LmrModel;
use App\Models\CatalogModel;
use App\Models\AuditModel;

class LmrController {
    private $lmrModel;
    private $catalogModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?controller=auth&action=login');
            exit;
        }

        $allowedDepartments = ['warehouse', 'admin', 'rnd', 'production'];
        $dept = $_SESSION['department'] ?? '';
        if (!in_array($dept, $allowedDepartments)) {
            $_SESSION['error'] = 'You do not have access to LMRs.';
            header('Location: ?controller=' . ($dept === 'finance' ? 'finance' : 'admin'));
            exit;
        }

        $this->lmrModel = new LmrModel();
        $this->catalogModel = new CatalogModel();
    }

    public function index() {
        $status = $_GET['status'] ?? 'all';
        $orders = $this->lmrModel->getAll($status);

        $data = [
            'page_title' => 'LMRs',
            'orders' => $orders,
            'status' => $status,
            'customers' => $this->catalogModel->getCustomers(),
        ];

        $this->render('lmr/index', $data);
    }

    public function create() {
        $data = [
            'page_title' => 'Create LMR',
            'customers' => $this->catalogModel->getCustomers(),
            'items' => [],
            'allItems' => $this->catalogModel->getLmrProductItems(),
        ];

        $this->render('lmr_entry', $data);
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=lmr&action=index');
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
            'lmr_number' => trim($_POST['lmr_number'] ?? ''),
            'lmr_type' => $_POST['lmr_type'] ?? 'Standard',
            'lmr_site' => $_POST['lmr_site'] ?? '001 - Sterling Technopark',
            'order_date' => $_POST['order_date'] ?? date('Y-m-d'),
            'due_date' => $_POST['due_date'] ?? date('Y-m-d'),
            'planned_start_date' => $_POST['planned_start_date'] ?? date('Y-m-d'),
            'priority' => (int) ($_POST['priority'] ?? 3),
            'reference_no' => $_POST['reference_no'] ?? null,
            'lmr_status' => $_POST['lmr_status'] ?? 'Planned',
            'customer_id' => $customer['customer_id'] ?? null,
            'customer_code' => $customer['customer_code'] ?? ($_POST['customer_code'] ?? null),
            'customer_name' => $customer['customer_name'] ?? ($_POST['customer_name'] ?? null),
            'batch_lot_no' => $_POST['batch_lot_no'] ?? null,
            'po_number' => $_POST['po_number'] ?? null,
        ];

        if (empty($header['lmr_number'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'LMR Number is required.']);
                exit;
            }
            $_SESSION['error'] = 'LMR Number is required.';
            header('Location: ?controller=lmr&action=create');
            exit;
        }

        if (empty($header['customer_id']) && empty($header['customer_code'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Please select a customer.']);
                exit;
            }
            $_SESSION['error'] = 'Please select a customer.';
            header('Location: ?controller=lmr&action=create');
            exit;
        }

        if (empty($header['po_number'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'PO Number is required for LMR printing.']);
                exit;
            }
            $_SESSION['error'] = 'PO Number is required for LMR printing.';
            header('Location: ?controller=lmr&action=create');
            exit;
        }

        try {
            $this->lmrModel->create($header, $items);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => '?controller=lmr&action=index']);
                exit;
            }
            $_SESSION['success'] = 'LMR created successfully.';
            header('Location: ?controller=lmr&action=index');
            exit;
        } catch (\Exception $e) {
            error_log('LmrController::store error: ' . $e->getMessage());
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Failed to save LMR: ' . $e->getMessage()]);
                exit;
            }
            $_SESSION['error'] = 'Failed to save LMR: ' . $e->getMessage();
            header('Location: ?controller=lmr&action=create');
            exit;
        }
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=lmr&action=index');
            exit;
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
              && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

        $lmrId = intval($_POST['lmr_id'] ?? 0);
        if (!$lmrId) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid LMR ID.']);
                exit;
            }
            $_SESSION['error'] = 'Invalid LMR ID.';
            header('Location: ?controller=lmr&action=index');
            exit;
        }

        $order = $this->lmrModel->findById($lmrId);
        if (!$order) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'LMR not found.']);
                exit;
            }
            $_SESSION['error'] = 'LMR not found.';
            header('Location: ?controller=lmr&action=index');
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
            'lmr_number' => trim($_POST['lmr_number'] ?? ''),
            'lmr_type' => $_POST['lmr_type'] ?? 'Standard',
            'lmr_site' => $_POST['lmr_site'] ?? '001 - Sterling Technopark',
            'order_date' => $_POST['order_date'] ?? date('Y-m-d'),
            'due_date' => $_POST['due_date'] ?? date('Y-m-d'),
            'planned_start_date' => $_POST['planned_start_date'] ?? date('Y-m-d'),
            'priority' => (int) ($_POST['priority'] ?? 3),
            'reference_no' => $_POST['reference_no'] ?? null,
            'lmr_status' => $_POST['lmr_status'] ?? 'Planned',
            'customer_id' => $customer['customer_id'] ?? null,
            'customer_code' => $customer['customer_code'] ?? ($_POST['customer_code'] ?? null),
            'customer_name' => $customer['customer_name'] ?? ($_POST['customer_name'] ?? null),
            'batch_lot_no' => $_POST['batch_lot_no'] ?? null,
            'po_number' => $_POST['po_number'] ?? null,
        ];

        if (empty($header['lmr_number'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'LMR Number is required.']);
                exit;
            }
            $_SESSION['error'] = 'LMR Number is required.';
            header('Location: ?controller=lmr&action=edit&id=' . $lmrId);
            exit;
        }

        if (empty($header['customer_id']) && empty($header['customer_code'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Please select a customer.']);
                exit;
            }
            $_SESSION['error'] = 'Please select a customer.';
            header('Location: ?controller=lmr&action=edit&id=' . $lmrId);
            exit;
        }

        if (empty($header['po_number'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'PO Number is required for LMR printing.']);
                exit;
            }
            $_SESSION['error'] = 'PO Number is required for LMR printing.';
            header('Location: ?controller=lmr&action=edit&id=' . $lmrId);
            exit;
        }

        try {
            $this->lmrModel->update($lmrId, $header, $items);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'redirect' => '?controller=lmr&action=index']);
                exit;
            }
            $_SESSION['success'] = 'LMR updated successfully.';
            header('Location: ?controller=lmr&action=index');
            exit;
        } catch (\Exception $e) {
            error_log('LmrController::update error: ' . $e->getMessage());
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Failed to update LMR: ' . $e->getMessage()]);
                exit;
            }
            $_SESSION['error'] = 'Failed to update LMR: ' . $e->getMessage();
            header('Location: ?controller=lmr&action=edit&id=' . $lmrId);
            exit;
        }
    }

    public function edit() {
        $id = $_GET['id'] ?? 0;
        $order = $this->lmrModel->findById($id);
        if (!$order) {
            $_SESSION['error'] = 'LMR not found.';
            header('Location: ?controller=lmr&action=index');
            exit;
        }

        $data = [
            'page_title' => 'Edit LMR',
            'lmr' => $order,
            'items' => $this->lmrModel->getItemsByLmrId($id),
            'customers' => $this->catalogModel->getCustomers(),
            'allItems' => $this->catalogModel->getLmrProductItems(),
        ];

        $this->render('lmr_entry', $data);
    }

    public function release() {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            $_SESSION['error'] = 'Invalid LMR.';
            header('Location: ?controller=lmr&action=index');
            exit;
        }
        $order = $this->lmrModel->findById($id);
        if (!$order) {
            $_SESSION['error'] = 'LMR not found.';
            header('Location: ?controller=lmr&action=index');
            exit;
        }

        $breakdown = $this->lmrModel->getLmrBomBreakdown($id);
        $validation = $this->lmrModel->validateMoRelease($id);

        $data = [
            'page_title' => 'Confirm Release LMR',
            'lmr' => $order,
            'items' => $this->lmrModel->getItemsByLmrId($id),
            'breakdown' => $breakdown,
            'validation' => $validation,
            'isAdmin' => ($_SESSION['department'] ?? '') === 'admin',
        ];
        $this->render('lmr/release_confirm', $data);
    }

    public function releaseExecute() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=lmr&action=index');
            exit;
        }
        $id = intval($_POST['lmr_id'] ?? 0);
        if (!$id) {
            $_SESSION['error'] = 'Invalid LMR.';
            header('Location: ?controller=lmr&action=index');
            exit;
        }
        $order = $this->lmrModel->findById($id);
        if (!$order) {
            $_SESSION['error'] = 'LMR not found.';
            header('Location: ?controller=lmr&action=index');
            exit;
        }

        $validation = $this->lmrModel->validateMoRelease($id);
        $isForceRelease = !empty($_POST['force_release']);
        $forceReason = trim($_POST['force_reason'] ?? '');
        $isAdmin = ($_SESSION['department'] ?? '') === 'admin';

        if (!$validation['valid']) {
            if ($isForceRelease && $isAdmin && !empty($forceReason)) {
                AuditModel::log(
                    $_SESSION['user_id'], 'FORCE_RELEASE', 'lmr',
                    'Force released LMR ' . $order['lmr_number'] . ' despite stock shortages. Reason: ' . $forceReason,
                    null,
                    ['shortages' => $validation['shortages'], 'reason' => $forceReason],
                    'lmr_order', $id
                );
            } else {
                $shortageList = '';
                foreach ($validation['shortages'] as $s) {
                    $shortageList .= "\n- " . $s['item_code'] . ' (' . ($s['item_description'] ?? '') . ')';
                    if (!empty($s['reason'])) {
                        $shortageList .= ' — ' . $s['reason'];
                    } else {
                        $shortageList .= ' — Short by ' . formatQty($s['short_by'], 2) . ' ' . ($s['uom'] ?? '');
                    }
                }
                $_SESSION['error'] = 'Cannot Release LMR-' . htmlspecialchars($order['lmr_number']) . ': Lacking Materials' . $shortageList;
                header('Location: ?controller=lmr&action=release&id=' . $id);
                exit;
            }
        }

        try {
            \App\Core\BaseModel::beginTransaction();

            $this->lmrModel->updateStatus($id, 'Released');
            $this->lmrModel->allocateMaterials($id);

            \App\Core\BaseModel::commit();
        } catch (\Exception $e) {
            \App\Core\BaseModel::rollback();
            $_SESSION['error'] = 'Release failed: ' . $e->getMessage();
            header('Location: ?controller=lmr&action=release&id=' . $id);
            exit;
        }

        if (!$isForceRelease) {
            AuditModel::log(
                $_SESSION['user_id'], 'RELEASE', 'lmr',
                'Released LMR ' . $order['lmr_number'],
                null, ['lmr_status' => $order['lmr_status']],
                'lmr_order', $id
            );
        }

        $_SESSION['success'] = 'LMR ' . htmlspecialchars($order['lmr_number']) . ' released successfully. Material allocations updated.';
        header('Location: ?controller=lmr&action=index');
        exit;
    }

    public function cancel() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=lmr&action=index');
            exit;
        }
        $id = intval($_POST['lmr_id'] ?? $_GET['id'] ?? 0);
        if (!$id) {
            $_SESSION['error'] = 'Invalid LMR.';
            header('Location: ?controller=lmr&action=index');
            exit;
        }
        $order = $this->lmrModel->findById($id);
        if (!$order) {
            $_SESSION['error'] = 'LMR not found.';
            header('Location: ?controller=lmr&action=index');
            exit;
        }

        if ($order['lmr_status'] !== 'Released') {
            $_SESSION['error'] = 'Only Released MOs can be cancelled.';
            header('Location: ?controller=lmr&action=index');
            exit;
        }

        try {
            \App\Core\BaseModel::beginTransaction();

            $this->lmrModel->deallocateMaterials($id);
            $this->lmrModel->updateStatus($id, 'Cancelled');

            \App\Core\BaseModel::commit();
        } catch (\Exception $e) {
            \App\Core\BaseModel::rollback();
            $_SESSION['error'] = 'Cancel failed: ' . $e->getMessage();
            header('Location: ?controller=lmr&action=index');
            exit;
        }

        AuditModel::log(
            $_SESSION['user_id'], 'CANCEL', 'lmr',
            'Cancelled LMR ' . $order['lmr_number'] . ' and deallocated materials',
            null, ['lmr_status' => 'Released'],
            'lmr_order', $id
        );

        $_SESSION['success'] = 'LMR ' . htmlspecialchars($order['lmr_number']) . ' cancelled. Material allocations released.';
        header('Location: ?controller=lmr&action=index');
        exit;
    }

    public function printLmr() {
        $id = $_GET['id'] ?? 0;
        $order = $this->lmrModel->findById($id);
        if (!$order) {
            $_SESSION['error'] = 'LMR not found.';
            header('Location: ?controller=lmr&action=index');
            exit;
        }

        $data = [
            'page_title' => 'LMR Print',
            'lmr' => $order,
            'items' => $this->lmrModel->getItemsByLmrId($id),
            'breakdown' => $this->lmrModel->getLmrBomBreakdown($id),
            'documentId' => 'LMR-' . $order['lmr_number'],
        ];

        extract($data);
        include __DIR__ . "/../views/lmr_print.php";
        exit;
    }

    public function getBomBreakdown() {
        header('Content-Type: application/json');
        try {
            $lmrId = intval($_GET['lmr_id'] ?? 0);
            if ($lmrId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid LMR ID']);
                exit;
            }
            $order = $this->lmrModel->findById($lmrId);
            if (!$order) {
                http_response_code(404);
                echo json_encode(['error' => 'LMR not found']);
                exit;
            }
            $breakdown = $this->lmrModel->getLmrBomBreakdown($lmrId);
            echo json_encode([
                'lmr' => $order,
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
            $breakdown = $this->lmrModel->getBomBreakdownForItem($itemId, $qty);
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
        $order = $this->lmrModel->findById($id);
        if (!$order) {
            $_SESSION['error'] = 'LMR not found.';
            header('Location: ?controller=lmr&action=index');
            exit;
        }

        $breakdown = $this->lmrModel->getLmrBomBreakdown($id);

        $data = [
            'page_title' => 'LMR Details',
            'lmr' => $order,
            'items' => $this->lmrModel->getItemsByLmrId($id),
            'breakdown' => $breakdown,
        ];
        $this->render('lmr/view', $data);
    }

    private function render($view, $data = []) {
        extract($data);
        ob_start();
        include __DIR__ . "/../views/{$view}.php";
        $content = ob_get_clean();
        include __DIR__ . "/../views/layouts/main.php";
    }
}
