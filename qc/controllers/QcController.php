<?php
namespace App\Controllers;

use App\Models\QcModel;
use App\Helpers\Pagination;

class QcController {
    private $qcModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?controller=auth&action=login');
            exit;
        }
        if (($_SESSION['department'] ?? '') !== 'qc') {
            header('Location: ?controller=admin');
            exit;
        }
        $this->qcModel = new QcModel();
    }

    public function index() {
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
            'page_title' => 'QC Dashboard'
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
