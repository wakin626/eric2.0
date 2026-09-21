<?php
require_once __DIR__ . '/../core/Config.php';
\App\Core\Config::init();

spl_autoload_register(function ($class) {
    $paths = [
        'C:\\xampp\\htdocs\\order-billing-system\\core' => 'App\\Core',
        'C:\\xampp\\htdocs\\order-billing-system\\app\\helpers' => 'App\\Helpers',
        'C:\\xampp\\htdocs\\order-billing-system\\admin\\controllers' => 'App\\Controllers',
        'C:\\xampp\\htdocs\\order-billing-system\\admin\\models' => 'App\\Models',
        'C:\\xampp\\htdocs\\order-billing-system\\auth\\controllers' => 'App\\Controllers',
        'C:\\xampp\\htdocs\\order-billing-system\\auth\\models' => 'App\\Models',
        'C:\\xampp\\htdocs\\order-billing-system\\warehouse\\controllers' => 'App\\Controllers',
        'C:\\xampp\\htdocs\\order-billing-system\\warehouse\\models' => 'App\\Models',
        'C:\\xampp\\htdocs\\order-billing-system\\production\\controllers' => 'App\\Controllers',
        'C:\\xampp\\htdocs\\order-billing-system\\production\\models' => 'App\\Models',
        'C:\\xampp\\htdocs\\order-billing-system\\finance\\controllers' => 'App\\Controllers',
        'C:\\xampp\\htdocs\\order-billing-system\\finance\\models' => 'App\\Models',
        'C:\\xampp\\htdocs\\order-billing-system\\qc\\controllers' => 'App\\Controllers',
        'C:\\xampp\\htdocs\\order-billing-system\\qc\\models' => 'App\\Models',
        'C:\\xampp\\htdocs\\order-billing-system\\qa\\controllers' => 'App\\Controllers',
        'C:\\xampp\\htdocs\\order-billing-system\\qa\\models' => 'App\\Models',
        'C:\\xampp\\htdocs\\order-billing-system\\rnd\\controllers' => 'App\\Controllers',
        'C:\\xampp\\htdocs\\order-billing-system\\rnd\\models' => 'App\\Models'
    ];

    foreach ($paths as $baseDir => $prefix) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});

header('Content-Type: application/json');

try {
    $payload = $_POST;
    if (empty($payload)) {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
    }

    $receivedQty = (float) ($payload['received_qty'] ?? 0);
    $itemId = $payload['item_id'] ?? null;
    $itemCode = trim((string) ($payload['item_code'] ?? ''));
    $itemDescription = trim((string) ($payload['item_description'] ?? ''));
    $supplierName = trim((string) ($payload['supplier_name'] ?? ''));
    $poId = $payload['po_id'] ?? null;
    $lotNumber = trim((string) ($payload['lot_number'] ?? '')) ?: null;
    $receivedDate = $payload['received_date'] ?? date('Y-m-d');
    $sourceType = trim((string) ($payload['source_type'] ?? 'purchase_order')) ?: 'purchase_order';
    $sourceId = $payload['source_id'] ?? null;
    $remarks = trim((string) ($payload['remarks'] ?? '')) ?: null;

    if ($receivedQty <= 0) {
        throw new RuntimeException('Received quantity must be greater than zero.');
    }
    if ($supplierName === '' || ($itemId === null && $itemCode === '')) {
        throw new RuntimeException('Supplier and item are required.');
    }

    $qcModel = new \App\Models\QcModel();
    $recordId = $qcModel->createReceivingItem([
        'po_id' => $poId,
        'source_type' => $sourceType,
        'source_id' => $sourceId,
        'supplier_name' => $supplierName,
        'item_id' => $itemId,
        'item_code' => $itemCode,
        'item_description' => $itemDescription,
        'lot_number' => $lotNumber,
        'received_qty' => $receivedQty,
        'received_date' => $receivedDate,
        'remarks' => $remarks,
        'created_by' => $_SESSION['user_id'] ?? null,
        'warehouse_user_id' => $_SESSION['user_id'] ?? null,
    ]);

    $poLabel = $poId ? 'PO #' . $poId : ($sourceType === 'purchase_order' ? 'Purchasing PO' : 'Shipment');
    if (class_exists('\App\Helpers\NotificationHelper')) {
        \App\Helpers\NotificationHelper::qcInspectionNeeded($poLabel, $lotNumber ?: $itemCode ?: 'N/A', $_SESSION['user_id'] ?? null);
    }

    echo json_encode([
        'success' => true,
        'status' => 'QUARANTINE',
        'receiving_item_id' => $recordId,
        'message' => 'Shipment received and placed into quarantine.'
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
