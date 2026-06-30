<?php
namespace App\Controllers;

use App\Models\FinanceModel;
use App\Models\PriceListModel;
use App\Helpers\Pagination;

class FinanceController {
    private $financeModel;
    private $priceListModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?controller=auth&action=login');
            exit;
        }
        $action = $_GET['action'] ?? '';
        $allowedActions = ['getPODetails', 'getDeliveryDetails'];
        if (!in_array($action, $allowedActions) && ($_SESSION['department'] ?? '') !== 'finance') {
            header('Location: ?controller=admin');
            exit;
        }
        $this->financeModel = new FinanceModel();
        $this->priceListModel = new PriceListModel();
    }

    public function index() {
        $data['page_title'] = 'Finance Dashboard';
        $data['stats'] = $this->financeModel->getFinanceStats();
        $data['ready_to_deliver'] = $this->financeModel->getPOsReadyToDeliver();
        $data['recent_deliveries'] = array_slice($this->financeModel->getAllDeliveries(), 0, 5);
        $data['po_items_map'] = $this->financeModel->getAllPurchaseOrderItems();
        $this->render('dashboard', $data);
    }

    public function purchaseOrders() {
        $allPOs = $this->financeModel->getAllPurchaseOrders();
        $pagination = Pagination::paginate($allPOs, 10);
        $data['purchase_orders'] = $pagination['items'];
        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['po_items_map'] = $this->financeModel->getAllPurchaseOrderItems();
        $data['page_title'] = 'Customer PO';
        $this->render('purchase_orders/index', $data);
    }

    public function readyToDeliver() {
        $readyPOs = $this->financeModel->getPOsReadyToDeliver();
        $pagination = Pagination::paginate($readyPOs, 10);
        $data['purchase_orders'] = $pagination['items'];
        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['po_items_map'] = $this->financeModel->getAllPurchaseOrderItems();
        $data['page_title'] = 'Ready to Deliver';
        $this->render('purchase_orders/ready_to_deliver', $data);
    }

    public function deliveries() {
        $allDeliveries = $this->financeModel->getAllDeliveries();
        $pagination = Pagination::paginate($allDeliveries, 10);
        $data['deliveries'] = $pagination['items'];
        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['page_title'] = 'Deliveries';
        $this->render('deliveries/index', $data);
    }

    public function viewDelivery() {
        $id = $_GET['id'] ?? null;
        $data['delivery'] = $this->financeModel->getDeliveryById($id);
        $data['receipts'] = $this->financeModel->getReceiptsByDelivery($id);
        $data['poi_item'] = $this->financeModel->getDeliveryPoiItem($data['delivery']['poi_id'] ?? 0);
        $data['price_list'] = !empty($data['poi_item']['item_id']) ? $this->priceListModel->getByItemId($data['poi_item']['item_id']) : null;
        $data['page_title'] = 'Delivery Details';
        $this->render('deliveries/view', $data);
    }

    public function uploadReceipt() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $delivery_id = $_POST['delivery_id'] ?? null;
            $po_id = $_POST['po_id'] ?? null;

            if (!$delivery_id || !$po_id) {
                $_SESSION['error'] = 'Invalid delivery or PO ID';
                header('Location: ?controller=finance&action=deliveries');
                exit;
            }

            if (!isset($_FILES['receipt_file']) || $_FILES['receipt_file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = 'Please select a file to upload';
                header("Location: ?controller=finance&action=viewDelivery&id={$delivery_id}");
                exit;
            }

            $file = $_FILES['receipt_file'];
            $allowedTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];

            if (!in_array($file['type'], $allowedTypes)) {
                $_SESSION['error'] = 'Invalid file type. Allowed: JPG, PNG, GIF, WebP, PDF, DOC, DOCX';
                header("Location: ?controller=finance&action=viewDelivery&id={$delivery_id}");
                exit;
            }

            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($file['size'] > $maxSize) {
                $_SESSION['error'] = 'File size must be less than 10MB';
                header("Location: ?controller=finance&action=viewDelivery&id={$delivery_id}");
                exit;
            }

            $uploadDir = __DIR__ . '/../../uploads/receipts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'receipt_' . $delivery_id . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                $_SESSION['error'] = 'Failed to upload file';
                header("Location: ?controller=finance&action=viewDelivery&id={$delivery_id}");
                exit;
            }

            $this->financeModel->attachReceipt([
                'delivery_id' => $delivery_id,
                'po_id' => $po_id,
                'file_name' => $file['name'],
                'file_path' => 'uploads/receipts/' . $fileName,
                'file_type' => $file['type'],
                'file_size' => $file['size'],
                'uploaded_by' => $_SESSION['user_id']
            ]);

            $_SESSION['success'] = 'Receipt uploaded successfully';
            header("Location: ?controller=finance&action=viewDelivery&id={$delivery_id}");
            exit;
        }
    }

    public function deleteReceipt() {
        $receipt_id = $_GET['id'] ?? null;
        $delivery_id = $_GET['delivery_id'] ?? null;

        if ($receipt_id) {
            $receipt = $this->financeModel->getReceiptById($receipt_id);
            if ($receipt && file_exists(__DIR__ . '/../../' . $receipt['file_path'])) {
                unlink(__DIR__ . '/../../' . $receipt['file_path']);
            }
            $this->financeModel->deleteReceipt($receipt_id);
            $_SESSION['success'] = 'Receipt deleted';
        }

        header("Location: ?controller=finance&action=viewDelivery&id={$delivery_id}");
        exit;
    }

    public function getPODetails() {
        header('Content-Type: application/json');
        $id = $_GET['id'] ?? null;
        $po = $this->financeModel->getPurchaseOrderById($id);
        $po_items = $this->financeModel->getPurchaseOrderItems($id);
        $deliveries = $this->financeModel->getDeliveriesByPOWithItems($id);
        $receipts = $this->financeModel->getReceiptsByPO($id);
        echo json_encode([
            'po' => $po, 
            'po_items' => $po_items,
            'deliveries' => $deliveries,
            'receipts' => $receipts
        ]);
        exit;
    }

    public function getDeliveryDetails() {
        header('Content-Type: application/json');
        $id = $_GET['id'] ?? null;
        $delivery = $this->financeModel->getDeliveryById($id);
        $receipts = $this->financeModel->getReceiptsByDelivery($id);
        echo json_encode(['delivery' => $delivery, 'receipts' => $receipts]);
        exit;
    }

    public function priceList() {
        $allItems = $this->priceListModel->getAll();
        $pagination = Pagination::paginate($allItems, 20);
        $data['price_items'] = $pagination['items'];
        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['all_items'] = $this->financeModel->getAllActiveItems();
        $data['page_title'] = 'Price List';
        $this->render('price_list', $data);
    }

    public function priceListCreate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->priceListModel->create([
                'item_id' => $_POST['item_id'] ?? null,
                'product_name' => $_POST['product_name'] ?? '',
                'net_size' => $_POST['net_size'] ?? null,
                'price_per_pack' => $_POST['price_per_pack'] ?? 0,
                'price_per_case' => $_POST['price_per_case'] ?? 0,
                'price_per_piece' => $_POST['price_per_piece'] ?? 0,
                'vat_type' => $_POST['vat_type'] ?? 'vat'
            ]);
            $_SESSION['success'] = 'Price list item added successfully';
        }
        header('Location: ?controller=finance&action=priceList');
        exit;
    }

    public function priceListUpdate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['price_list_id'] ?? null;
            if ($id) {
                $this->priceListModel->update($id, [
                    'item_id' => $_POST['item_id'] ?? null,
                    'product_name' => $_POST['product_name'] ?? '',
                    'net_size' => $_POST['net_size'] ?? null,
                    'price_per_pack' => $_POST['price_per_pack'] ?? 0,
                    'price_per_case' => $_POST['price_per_case'] ?? 0,
                    'price_per_piece' => $_POST['price_per_piece'] ?? 0,
                    'vat_type' => $_POST['vat_type'] ?? 'vat'
                ]);
                $_SESSION['success'] = 'Price list item updated successfully';
            }
        }
        header('Location: ?controller=finance&action=priceList');
        exit;
    }

    public function priceListToggle() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $this->priceListModel->toggleStatus($id);
            $_SESSION['success'] = 'Status updated successfully';
        }
        header('Location: ?controller=finance&action=priceList');
        exit;
    }

    public function printSalesInvoice() {
        $id = $_GET['id'] ?? null;
        $delivery = $this->financeModel->getDeliveryById($id);

        $priceList = null;
        if (!empty($delivery['poi_item_id'])) {
            $priceList = $this->priceListModel->getByItemId($delivery['poi_item_id']);
        }

        $lotItems = json_decode($delivery['lot_items'] ?? '[]', true);
        if (!is_array($lotItems)) $lotItems = [];

        $items = [];
        $grandTotalQty = 0;
        $grandTotalAmount = 0;
        $vatType = $priceList['vat_type'] ?? 'non_vat';

        foreach ($lotItems as $li) {
            $liQty = $li['qty'] ?? 0;
            $itemPrice = $priceList['price_per_piece'] ?? 0;
            $itemAmount = $liQty * $itemPrice;

            $items[] = [
                'item_description' => $li['item_description'] ?? '',
                'item_uom' => $li['item_uom'] ?? '',
                'qty' => $liQty,
                'price' => $itemPrice,
                'amount' => $itemAmount,
            ];

            $grandTotalQty += $liQty;
            $grandTotalAmount += $itemAmount;
        }

        if (empty($items) && $delivery) {
            $qty = $delivery['delivery_quantity'] ?? 0;
            $price = $priceList['price_per_piece'] ?? 0;
            $amount = $qty * $price;
            $items[] = [
                'item_description' => $delivery['item_description'] ?? '',
                'item_uom' => $delivery['item_uom'] ?? '',
                'qty' => $qty,
                'price' => $price,
                'amount' => $amount,
            ];
            $grandTotalQty = $qty;
            $grandTotalAmount = $amount;
        }

        if ($vatType === 'vat') {
            $subtotal = $grandTotalAmount / 1.12;
            $vat = $grandTotalAmount - $subtotal;
        } else {
            $subtotal = $grandTotalAmount;
            $vat = 0;
        }

        $data = [
            'delivery' => $delivery,
            'date' => !empty($delivery['delivery_date']) ? date('j-M-Y', strtotime($delivery['delivery_date'])) : '',
            'customer_name' => $delivery['customer_name'] ?? '',
            'customer_tin' => $delivery['customer_tin'] ?? '',
            'customer_address' => $delivery['customer_address'] ?? '',
            'customer_terms' => ($delivery['customer_terms'] ?? 0) . ' DAYS',
            'customer_code' => $delivery['customer_code'] ?? '',
            'po_number' => $delivery['customer_po_number'] ?? '',
            'dr_number' => $delivery['dr_number'] ?? '',
            'items' => $items,
            'subtotal' => $subtotal,
            'vat' => $vat,
            'vatType' => $vatType,
            'grand_total' => $grandTotalAmount,
            'vatable_sales' => $vatType === 'vat' ? $subtotal : 0,
            'vat_amount' => $vat,
            'zero_rated_sales' => 0,
            'vat_exempt_sales' => $vatType !== 'vat' ? $grandTotalAmount : 0,
        ];

        extract($data);
        include __DIR__ . "/../views/deliveries/print.php";
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
