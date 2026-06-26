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
        
        // Fetch all active deliveries for this PO and map them to their corresponding items
        $deliveries = $this->warehouseModel->getDeliveriesByPOId($id);
        $dr_map = [];
        $delivery_ids = [];
        foreach ($deliveries as $d) {
            $dr = $d['dr_number'] ?? '';
            $delivery_id = $d['delivery_id'] ?? null;
            if (empty($dr)) continue;
            
            if (!empty($d['poi_id'])) {
                $poi_id = $d['poi_id'];
                $qty = $d['delivery_quantity'] ?? 0;
                if (!isset($dr_map[$poi_id])) {
                    $dr_map[$poi_id] = [];
                }
                $dr_map[$poi_id][] = [
                    'dr_number' => $dr,
                    'qty' => $qty,
                    'delivery_date' => $d['delivery_date'],
                    'lot_number' => $d['lot_number'] ?? null,
                    'delivery_id' => $delivery_id
                ];
                if ($delivery_id) $delivery_ids[] = $delivery_id;
            }
            
            if (!empty($d['lot_items'])) {
                $lotItems = json_decode($d['lot_items'], true);
                if (is_array($lotItems)) {
                    foreach ($lotItems as $li) {
                        $poi_id = $li['poi_id'] ?? null;
                        $qty = $li['qty'] ?? 0;
                        if ($poi_id) {
                            if (!isset($dr_map[$poi_id])) {
                                $dr_map[$poi_id] = [];
                            }
                            $dr_map[$poi_id][] = [
                                'dr_number' => $dr,
                                'qty' => $qty,
                                'delivery_date' => $d['delivery_date'],
                                'lot_number' => $li['lot_number'] ?? null,
                                'delivery_id' => $delivery_id
                            ];
                            if ($delivery_id) $delivery_ids[] = $delivery_id;
                        }
                    }
                }
            }
        }
        
        // Fetch receipts for this PO and map by delivery_id
        $receipts = [];
        if (!empty($id)) {
            $allReceipts = $this->warehouseModel->getReceiptsByPOId($id);
            foreach ($allReceipts as $r) {
                $rid = $r['delivery_id'];
                if (!isset($receipts[$rid])) {
                    $receipts[$rid] = $r;
                }
            }
        }
        
        foreach ($po_items as &$item) {
            $poi_id = $item['poi_id'];
            $item['deliveries'] = $dr_map[$poi_id] ?? [];
            foreach ($item['deliveries'] as &$del) {
                $did = $del['delivery_id'] ?? null;
                $del['receipt'] = ($did && isset($receipts[$did])) ? $receipts[$did] : null;
            }
            unset($del);
        }
        unset($item);
        
        echo json_encode(['po' => $po, 'po_items' => $po_items]);
        exit;
    }

    public function deliveries() {
        $allDeliveries = $this->warehouseModel->getDeliveries();
        $pagination = Pagination::paginate($allDeliveries, 10);
        $data['deliveries'] = $pagination['items'];
        $deliveryIds = array_column($pagination['items'], 'delivery_id');
        $receiptsMap = [];
        if (!empty($deliveryIds)) {
            $placeholders = implode(',', array_fill(0, count($deliveryIds), '?'));
            $conn = $this->warehouseModel::getConnection();
            $stmt = $conn->prepare("SELECT * FROM delivery_receipts WHERE delivery_id IN ($placeholders) AND `remove` = 0");
            $stmt->execute($deliveryIds);
            foreach ($stmt->fetchAll() as $r) {
                if (!isset($receiptsMap[$r['delivery_id']])) {
                    $receiptsMap[$r['delivery_id']] = $r;
                }
            }
        }
        $data['receipts_map'] = $receiptsMap;
        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['purchase_orders'] = $this->warehouseModel->getPurchaseOrders();
        $data['page_title'] = 'Deliveries';
        $this->render('deliveries/index', $data);
    }

    public function deleteDRPhoto() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        $receiptId = $_POST['receipt_id'] ?? null;
        if (!$receiptId) {
            echo json_encode(['error' => 'Missing receipt_id']);
            exit;
        }
        $this->warehouseModel->deleteDRPhoto($receiptId);
        echo json_encode(['success' => true]);
        exit;
    }

    public function createMultipleDelivery() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?controller=warehouse&action=deliveries');
            exit;
        }
        $po_id = $_POST['po_id'] ?? null;
        $dr_number = trim($_POST['dr_number'] ?? '');
        $lotIdsRaw = $_POST['lot_ids'] ?? '';
        $delivery_date = $_POST['delivery_date'] ?? date('Y-m-d');
        $remarks = $_POST['remarks'] ?? '';
        if (empty($po_id) || empty($dr_number) || empty($lotIdsRaw)) {
            $_SESSION['error'] = 'Missing required fields for delivery.';
            header('Location: ?controller=warehouse&action=deliveries');
            exit;
        }
        $pairs = explode(',', $lotIdsRaw);
        $lotItems = [];
        $totalQty = 0;
        $firstPoiId = null;
        foreach ($pairs as $pair) {
            $parts = explode(':', $pair);
            if (count($parts) !== 2) continue;
            $lotId = intval($parts[0]);
            $deliveryQty = intval($parts[1]);
            if ($lotId <= 0 || $deliveryQty <= 0) continue;
            $lot = $this->warehouseModel->getLotById($lotId);
            if (!$lot) continue;
            $remaining = $this->warehouseModel->getLotRemaining($lotId);
            if ($deliveryQty > $remaining) $deliveryQty = $remaining;
            if ($deliveryQty <= 0) continue;
            $poiId = $lot['poi_id'] ?? null;
            $item = $this->warehouseModel->getItemByPoiId($poiId);
            $lotItems[] = [
                'lot_id' => $lotId,
                'poi_id' => $poiId,
                'lot_number' => $lot['lot_number'] ?? '',
                'item_code' => $item['item_code'] ?? '',
                'item_description' => $item['item_description'] ?? '',
                'qty' => $deliveryQty,
                'item_uom' => $item['item_uom'] ?? '',
                'uom_conversion' => $item['uom_conversion'] ?? null,
            ];
            $totalQty += $deliveryQty;
            if (!$firstPoiId) $firstPoiId = $poiId;
        }
        if (empty($lotItems)) {
            $_SESSION['error'] = 'No valid lots selected for delivery.';
            header('Location: ?controller=warehouse&action=deliveries');
            exit;
        }
        $deliveryId = $this->warehouseModel->createDelivery([
            'po_id' => $po_id,
            'poi_id' => $firstPoiId,
            'delivered_by' => $_SESSION['user_id'],
            'delivery_date' => $delivery_date,
            'delivery_quantity' => $totalQty,
            'dr_number' => $dr_number,
            'lot_items' => json_encode($lotItems),
            'remarks' => $remarks
        ]);
        $_SESSION['success'] = "Delivery recorded successfully for DR {$dr_number}.";
        header('Location: ?controller=warehouse&action=deliveries');
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

    public function getLotsForPrint() {
        header('Content-Type: application/json');
        $po_id = $_GET['po_id'] ?? null;
        if (!$po_id) {
            echo json_encode([]);
            exit;
        }
        $lots = $this->warehouseModel->getLotsByPOForPrint($po_id);
        echo json_encode($lots);
        exit;
    }

    public function checkDRNumber() {
        header('Content-Type: application/json');
        $dr_number = $_GET['dr_number'] ?? '';
        if (empty($dr_number)) {
            echo json_encode(['exists' => false]);
            exit;
        }
        $result = $this->warehouseModel->checkDRNumber($dr_number);
        echo json_encode($result);
        exit;
    }

    public function reportDelivery() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        $deliveryId = $_POST['delivery_id'] ?? null;
        $remarks = trim($_POST['remarks'] ?? '');
        if (!$deliveryId || empty($remarks)) {
            echo json_encode(['error' => 'Missing delivery_id or remarks']);
            exit;
        }
        $this->warehouseModel->reportDelivery($deliveryId, $remarks);
        echo json_encode(['success' => true]);
        exit;
    }

    public function uploadDRPhoto() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        $deliveryId = $_POST['delivery_id'] ?? null;
        $poId = $_POST['po_id'] ?? null;

        if (!$deliveryId || !$poId) {
            echo json_encode(['error' => 'Missing delivery_id or po_id']);
            exit;
        }

        if (!isset($_FILES['dr_photo']) || $_FILES['dr_photo']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'Please select a file to upload']);
            exit;
        }

        $file = $_FILES['dr_photo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP']);
            exit;
        }

        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            echo json_encode(['error' => 'File size must be less than 10MB']);
            exit;
        }

        $uploadDir = __DIR__ . '/../../uploads/receipts/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'dr_photo_' . $deliveryId . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            echo json_encode(['error' => 'Failed to upload file']);
            exit;
        }

        $this->warehouseModel->attachDRPhoto([
            'delivery_id' => $deliveryId,
            'po_id' => $poId,
            'file_name' => $file['name'],
            'file_path' => 'uploads/receipts/' . $fileName,
            'file_type' => $file['type'],
            'file_size' => $file['size'],
            'uploaded_by' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'file_path' => 'uploads/receipts/' . $fileName]);
        exit;
    }

    public function printDR() {
        $data['purchase_orders'] = $this->warehouseModel->getPurchaseOrders();
        $data['page_title'] = 'Print Delivery Receipt';
        $selectedPoId = $_GET['po_id'] ?? null;
        $dr_number = $_GET['dr_number'] ?? '';
        $data['dr_number'] = $dr_number;
        $data['selected_po_id'] = $selectedPoId;
        $data['existing_lot_ids'] = [];
        $data['lots_by_item'] = [];

        if ($selectedPoId) {
            $data['lots_by_item'] = $this->warehouseModel->getLotsByPOForPrint($selectedPoId);
        }

        if (!empty($dr_number) && $selectedPoId) {
            $data['existing_lot_ids'] = $this->warehouseModel->getLotsByDRNumber($dr_number);
        }

        $this->render('deliveries/print_dr', $data);
    }

    public function printDRPreview() {
        $po_id = $_GET['po_id'] ?? null;
        $dr_number = $_GET['dr_number'] ?? '';
        if (!$dr_number) {
            header('Location: ?controller=warehouse&action=printDR');
            exit;
        }
        $dr_deliveries = $this->warehouseModel->getDeliveriesByDRNumber($dr_number);
        if (empty($dr_deliveries)) {
            echo "<div class='container mt-5'><div class='alert alert-danger'>Error: DR number \"" . htmlspecialchars($dr_number) . "\" not found.</div><a href='?controller=warehouse&action=deliveries' class='btn btn-secondary'>Back</a></div>";
            exit;
        }
        if (!$po_id && !empty($dr_deliveries[0]['po_id'])) {
            $po_id = $dr_deliveries[0]['po_id'];
        }
        $data['po'] = $po_id ? $this->warehouseModel->getPurchaseOrderById($po_id) : null;
        $data['dr_deliveries'] = $dr_deliveries;
        $data['dr_number'] = $dr_number;
        extract($data);
        include __DIR__ . "/../views/deliveries/print_dr_preview.php";
        exit;
    }

    public function saveDRNumberForLots() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false]);
            exit;
        }
        $lotIds = $_POST['lot_ids'] ?? '';
        $dr_number = trim($_POST['dr_number'] ?? '');
        $po_id = $_POST['po_id'] ?? null;
        if (empty($lotIds) || empty($dr_number) || empty($po_id)) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            exit;
        }
        $lotIdArray = array_map('intval', explode(',', $lotIds));
        $lotIdArray = array_filter($lotIdArray);
        $lotItems = [];
        $totalQty = 0;
        $firstPoiId = null;
        foreach ($lotIdArray as $lotId) {
            $lot = $this->warehouseModel->getLotById($lotId);
            if (!$lot) continue;
            $remaining = $this->warehouseModel->getLotRemaining($lotId);
            if ($remaining <= 0) continue;
            $poiId = $lot['poi_id'] ?? null;
            $item = $this->warehouseModel->getItemByPoiId($poiId);
            $lotItems[] = [
                'lot_id' => $lotId,
                'poi_id' => $poiId,
                'lot_number' => $lot['lot_number'] ?? '',
                'item_code' => $item['item_code'] ?? '',
                'item_description' => $item['item_description'] ?? '',
                'qty' => $remaining,
                'unit_price' => $item['unit_price'] ?? 0,
                'item_uom' => $item['item_uom'] ?? '',
                'uom_conversion' => $item['uom_conversion'] ?? null,
                'item_id' => $item['item_id'] ?? null,
            ];
            $totalQty += $remaining;
            if (!$firstPoiId) $firstPoiId = $poiId;
        }
        if (empty($lotItems)) {
            echo json_encode(['success' => false, 'message' => 'No available lots found']);
            exit;
        }
        $this->warehouseModel->createDelivery([
            'po_id' => $po_id,
            'poi_id' => $firstPoiId,
            'delivered_by' => $_SESSION['user_id'],
            'delivery_date' => date('Y-m-d'),
            'delivery_quantity' => $totalQty,
            'dr_number' => $dr_number,
            'lot_items' => json_encode($lotItems),
            'remarks' => ''
        ]);
        echo json_encode(['success' => true]);
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