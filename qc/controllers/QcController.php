<?php
namespace App\Controllers;

use App\Models\QcModel;
use App\Helpers\Pagination;

class QcController {
    private $qcModel;

    public function __construct() {
        $action = $_GET['action'] ?? '';
        $isApiAction = in_array($action, ['apiGetPending', 'apiInspect'], true);

        if (!isset($_SESSION['user_id'])) {
            if ($isApiAction) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Session expired. Please log in again.']);
                exit;
            }
            header('Location: ?controller=auth&action=login');
            exit;
        }
        if (!$isApiAction && (($_SESSION['department'] ?? '') !== 'qc')) {
            header('Location: ?controller=admin');
            exit;
        }
        $this->qcModel = new QcModel();
    }

    public function index() {
        header('Location: ?controller=qc&action=receivingInspection');
        exit;
    }

    public function receivingInspection() {
        $pendingQcItems = $this->qcModel->getPendingQcItems();

        $data = [
            'pendingQcItems' => $pendingQcItems,
            'page_title' => 'Receiving Inspection'
        ];

        $this->render('receiving_inspection', $data);
    }

    public function deliveryInspection() {
        $search = $_GET['search'] ?? '';
        $filterCustomer = $_GET['filter_customer'] ?? '';
        $filterItem = $_GET['filter_item'] ?? '';
        $filterLot = $_GET['filter_lot'] ?? '';

        $hasFilter = $search || $filterCustomer || $filterItem || $filterLot;
        if ($hasFilter) {
            $filters = [];
            if ($search) $filters['search'] = $search;
            if ($filterCustomer) $filters['customer_name'] = $filterCustomer;
            if ($filterItem) $filters['item_description'] = $filterItem;
            if ($filterLot) $filters['lot_number'] = $filterLot;
            $allHistory = $this->qcModel->getProductionHistoryForQC($filters);
        } else {
            $allHistory = $this->qcModel->getProductionHistoryForQC();
        }

        $allCustomers = array_values(array_unique(array_filter(array_column($allHistory, 'customer_name'))));
        $allItems = array_values(array_unique(array_filter(array_column($allHistory, 'item_description'))));
        $allLots = array_values(array_unique(array_filter(array_column($allHistory, 'lot_number'))));

        if ($hasFilter) {
            $pagination = ['items' => $allHistory, 'page' => 1, 'perPage' => count($allHistory), 'total' => count($allHistory), 'totalPages' => 1, 'hasNext' => false, 'hasPrev' => false];
        } else {
            $pagination = Pagination::paginate($allHistory, 15);
        }

        $counts = $this->qcModel->getInspectionCounts();

        $data = [
            'history' => $pagination['items'],
            'page' => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'total' => $pagination['total'],
            'search' => $search,
            'filterCustomer' => $filterCustomer,
            'filterItem' => $filterItem,
            'filterLot' => $filterLot,
            'allCustomers' => $allCustomers,
            'allItems' => $allItems,
            'allLots' => $allLots,
            'totalInspection' => $counts['total'],
            'inspectedCount' => $counts['inspected'],
            'remainingCount' => $counts['remaining'],
            'page_title' => 'For Delivery Inspection'
        ];

        $this->render('delivery_inspection', $data);
    }

    public function apiGetPending() {
        header('Content-Type: application/json');
        try {
            echo json_encode(['success' => true, 'items' => $this->qcModel->getPendingQcItems()]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function apiInspect() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        try {
            $payload = $_POST;
            if (empty($_POST)) {
                $payload = json_decode(file_get_contents('php://input'), true) ?? [];
            }
            $result = $this->qcModel->recordQcInspection($payload);
            echo json_encode(['success' => true, 'result' => $result]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function approveReceivingInspection($id = null) {
        $receivingItemId = (int) ($id ?? ($_POST['receiving_item_id'] ?? $_GET['id'] ?? 0));
        if ($receivingItemId <= 0) {
            $_SESSION['error'] = 'Receiving item not found.';
            header('Location: ?controller=qc');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $decision = strtoupper((string) ($_POST['decision'] ?? 'PASSED'));
            $payload = [
                'receiving_item_id' => $receivingItemId,
                'decision' => $decision,
                'received_qty' => (float) ($_POST['received_qty'] ?? 0),
                'passed_qty' => (float) ($_POST['passed_qty'] ?? 0),
                'rejected_qty' => (float) ($_POST['rejected_qty'] ?? 0),
                'inspector_name' => trim((string) ($_POST['inspector_name'] ?? ($_SESSION['full_name'] ?? 'QC'))),
                'remarks' => trim((string) ($_POST['remarks'] ?? '')),
            ];

            try {
                $result = $this->qcModel->approveReceivingInspection($receivingItemId, $payload);
                $_SESSION['success'] = 'Receiving inspection ' . strtolower($decision) . ' and stock status updated.';
                header('Location: ?controller=qc');
                exit;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: ?controller=qc');
                exit;
            }
        }

        $receivingItem = $this->qcModel->getReceivingItemById($receivingItemId);
        if (!$receivingItem) {
            $_SESSION['error'] = 'Receiving item not found.';
            header('Location: ?controller=qc');
            exit;
        }

        $data = [
            'page_title' => 'Approve Receiving Inspection',
            'receivingItem' => $receivingItem,
        ];
        $this->render('dashboard/index', $data);
    }

    public function updateRemark() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $historyId = $_POST['history_id'] ?? null;
            $remark = trim($_POST['qc_remark'] ?? '');
            $inspectorName = trim($_POST['qc_inspector_name'] ?? '');

            if (!$historyId || empty($remark) || empty($inspectorName)) {
                $_SESSION['error'] = 'Please fill in all required fields.';
                header('Location: ?controller=qc');
                exit;
            }

            $this->qcModel->updateQcRemark($historyId, $inspectorName, $remark);
            $_SESSION['success'] = 'QC remark saved successfully.';
            header('Location: ?controller=qc');
            exit;
        }
    }

    private function render($view, $data = []) {
        extract($data);
        ob_start();
        include __DIR__ . "/../views/{$view}.php";
        $content = ob_get_clean();
        include __DIR__ . "/../views/layouts/main.php";
    }
}
