<?php
namespace App\Controllers;

use App\Models\ItemModel;
use App\Models\WarehouseModel;
use App\Models\BomModel;
use App\Models\ItemImportModel;
use App\Helpers\Pagination;
use App\Models\AuditModel;
use App\Helpers\SpreadsheetReader;

class RndController {
    private $itemModel;
    private $warehouseModel;
    private $bomModel;
    private $itemImportModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ?controller=auth&action=login');
            exit;
        }
        if (($_SESSION['department'] ?? '') !== 'rnd') {
            $_SESSION['error'] = 'Unauthorized access';
            header('Location: ?controller=admin');
            exit;
        }
        $this->itemModel = new ItemModel();
        $this->warehouseModel = new WarehouseModel();
        $this->bomModel = new BomModel();
        $this->itemImportModel = new ItemImportModel();
    }

    public function index() {
        $this->items();
    }

    // ─── Raw Materials Management ─────────────────────────────────────────────

    // ─── Raw Materials (Deprecated — use Items module) ──────────────────────

    public function rawMaterials() {
        $_SESSION['error'] = 'Raw Materials are now managed under Items.';
        header('Location: ?controller=rnd&action=items');
        exit;
    }

    public function rawMaterialCreate() { $this->rawMaterials(); }
    public function rawMaterialUpdate() { $this->rawMaterials(); }
    public function rawMaterialDelete() { $this->rawMaterials(); }
    public function rawMaterialToggleStatus() { $this->rawMaterials(); }
    public function rawMaterialImportPreview() { $this->rawMaterials(); }
    public function rawMaterialImportConfirm() { $this->rawMaterials(); }

    // ─── BOM Management ──────────────────────────────────────────────────────

    public function boms() {
        $search = $_GET['search'] ?? '';
        $filters = [];
        if ($search) $filters['search'] = $search;

        $hasFilters = ($search !== '');
        if ($hasFilters) {
            $all = $this->bomModel->getAllFiltered($filters);
            $data['boms'] = $all;
            $data['page'] = 1;
            $data['totalPages'] = 1;
            $data['total'] = count($all);
        } else {
            $all = $this->bomModel->getAll();
            $pagination = Pagination::paginate($all, 15);
            $data['boms'] = $pagination['items'];
            $data['page'] = $pagination['page'];
            $data['totalPages'] = $pagination['totalPages'];
            $data['total'] = $pagination['total'];
        }
        $data['search'] = $search;
        $data['allItems'] = $this->itemModel->getAll(false);
        $conn = \App\Core\BaseModel::getConnection();
        $stmt = $conn->prepare("SELECT item_id, item_code, item_description, item_uom
            FROM items WHERE item_type IN ('RM','PM','SFG') AND status = 1 AND `remove` = 0
            ORDER BY item_code ASC");
        $stmt->execute();
        $data['allIngredients'] = $stmt->fetchAll();
        $data['page_title'] = 'BOM Recipes';
        $this->render('boms/index', $data);
    }

    public function bomCreate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $fgItemId = $_POST['fg_item_id'] ?? null;
                $bomCode = trim($_POST['bom_code'] ?? '');
                $batchQty = floatval($_POST['batch_qty'] ?? 1.0);
                $batchUom = trim($_POST['batch_uom'] ?? 'PCS');
                if (!$fgItemId) {
                    throw new \Exception("Finished good item is required.");
                }
                if ($batchQty <= 0) {
                    throw new \Exception("Batch quantity must be greater than 0.");
                }
                $existing = $this->bomModel->getBomForItem($fgItemId);
                if ($existing) {
                    throw new \Exception("A BOM already exists for this finished good. Edit the existing one.");
                }

                $componentItemIds = $_POST['component_item_id'] ?? [];
                $componentDosages = $_POST['component_dosage'] ?? [];
                $componentWastages = $_POST['component_wastage'] ?? [];

                $items = [];
                if (!empty($componentItemIds)) {
                    foreach ($componentItemIds as $idx => $itemId) {
                        $itemId = intval($itemId);
                        $dosage = floatval($componentDosages[$idx] ?? 0);
                        $wastage = floatval($componentWastages[$idx] ?? 0);
                        if ($itemId > 0 && $dosage > 0) {
                            $items[] = [
                                'item_id' => $itemId,
                                'dosage_rate' => $dosage,
                                'wastage_allowance_pct' => $wastage
                            ];
                        }
                    }
                }

                if (empty($items)) {
                    throw new \Exception("Please add at least one component with a valid dosage.");
                }

                $bomId = $this->bomModel->create($fgItemId, $bomCode ?: null, $batchQty, $batchUom);
                $this->bomModel->replaceBomItems($bomId, $items);

                AuditModel::log($_SESSION['user_id'], 'CREATE', 'rnd',
                    'Created BOM for item #' . $fgItemId . ' with ' . count($items) . ' components',
                    null, ['fg_item_id' => $fgItemId, 'bom_code' => $bomCode, 'batch_qty' => $batchQty, 'batch_uom' => $batchUom, 'components' => count($items)],
                    'bom', $bomId);

                $_SESSION['success'] = 'BOM created with ' . count($items) . ' components';
                header('Location: ?controller=rnd&action=boms');
                exit;
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header('Location: ?controller=rnd&action=boms');
                exit;
            }
        }
        header('Location: ?controller=rnd&action=boms');
        exit;
    }

    public function bomEdit() {
        $id = $_GET['id'] ?? null;
        $data['bom'] = $this->bomModel->getById($id);
        if (!$data['bom']) {
            $_SESSION['error'] = 'BOM not found';
            header('Location: ?controller=rnd&action=boms');
            exit;
        }
        $data['bomItems'] = $this->bomModel->getItemsByBomId($id);
        $data['allItems'] = $this->itemModel->getAll(false);
        $conn = \App\Core\BaseModel::getConnection();
        $stmt = $conn->prepare("SELECT item_id, item_code, item_description, item_uom
            FROM items WHERE item_type IN ('RM','PM','SFG') AND status = 1 AND `remove` = 0
            ORDER BY item_code ASC");
        $stmt->execute();
        $data['allIngredients'] = $stmt->fetchAll();
        $data['page_title'] = 'Edit BOM - ' . ($data['bom']['fg_name'] ?? '');
        $this->render('boms/edit', $data);
    }

    public function bomUpdate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = $_POST['bom_id'] ?? null;
                $fgItemId = $_POST['fg_item_id'] ?? null;
                $bomCode = trim($_POST['bom_code'] ?? '');
                $batchQty = floatval($_POST['batch_qty'] ?? 1.0);
                $batchUom = trim($_POST['batch_uom'] ?? 'PCS');
                if (!$fgItemId) {
                    throw new \Exception("Finished good item is required.");
                }
                if ($batchQty <= 0) {
                    throw new \Exception("Batch quantity must be greater than 0.");
                }
                $this->bomModel->update($id, $fgItemId, $bomCode ?: null, $batchQty, $batchUom);
                AuditModel::log($_SESSION['user_id'], 'UPDATE', 'rnd', 'Updated BOM #' . $id, null, ['fg_item_id' => $fgItemId, 'bom_code' => $bomCode, 'batch_qty' => $batchQty, 'batch_uom' => $batchUom], 'bom', $id);
                $_SESSION['success'] = 'BOM updated';
            } catch (\Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
        }
        header('Location: ?controller=rnd&action=bomEdit&id=' . ($_POST['bom_id'] ?? ''));
        exit;
    }

    public function bomDelete() {
        $id = $_GET['id'] ?? null;
        try {
            $old = $this->bomModel->getById($id);
            $this->bomModel->delete($id);
            AuditModel::log($_SESSION['user_id'], 'DELETE', 'rnd', 'Deleted BOM: ' . ($old['fg_name'] ?? $id), $old, null, 'bom', $id);
            $_SESSION['success'] = 'BOM deleted';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to delete BOM: ' . $e->getMessage();
        }
        header('Location: ?controller=rnd&action=boms');
        exit;
    }

    public function bomAddItem() {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            $bomId = $_POST['bom_id'] ?? null;
            $itemId = $_POST['item_id'] ?? null;
            $dosageRate = $_POST['dosage_rate'] ?? 0;
            $wastagePct = $_POST['wastage_allowance_pct'] ?? 0;

            if (!$bomId || !$itemId) {
                http_response_code(400);
                echo json_encode(['error' => 'BOM ID and ingredient item are required']);
                exit;
            }

            $result = $this->bomModel->addItem($bomId, $itemId, $dosageRate, $wastagePct);
            AuditModel::log($_SESSION['user_id'], 'CREATE', 'rnd', 'Added ingredient to BOM #' . $bomId, null, ['item_id' => $itemId, 'dosage_rate' => $dosageRate], 'bom_item', $result);
            echo json_encode(['success' => true, 'id' => $result]);
        } catch (\Exception $e) {
            error_log('bomAddItem error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add item: ' . $e->getMessage()]);
        }
        exit;
    }

    public function bomUpdateItem() {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            $bomItemId = $_POST['bom_item_id'] ?? null;
            $itemId = $_POST['item_id'] ?? null;
            $dosageRate = $_POST['dosage_rate'] ?? 0;
            $wastagePct = $_POST['wastage_allowance_pct'] ?? 0;

            if (!$bomItemId || !$itemId) {
                http_response_code(400);
                echo json_encode(['error' => 'BOM item ID and ingredient item are required']);
                exit;
            }

            $result = $this->bomModel->updateItem($bomItemId, $itemId, $dosageRate, $wastagePct);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('bomUpdateItem error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update item']);
        }
        exit;
    }

    public function bomRemoveItem() {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            $itemId = $_POST['item_id'] ?? null;
            if (!$itemId) {
                http_response_code(400);
                echo json_encode(['error' => 'Item ID required']);
                exit;
            }
            $this->bomModel->removeItem($itemId);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('bomRemoveItem error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to remove item']);
        }
        exit;
    }

    public function whereUsed() {
        header('Content-Type: application/json');
        $itemId = $_GET['item_id'] ?? null;
        if (!$itemId) {
            echo json_encode([]);
            exit;
        }
        $results = $this->bomModel->getBomsByItem($itemId);
        echo json_encode($results);
        exit;
    }

    // ─── Bulk Import: BOM Recipes ─────────────────────────────────────────────

    public function bomImportPreview() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['import_file'])) {
            $_SESSION['error'] = 'No file uploaded.';
            header('Location: ?controller=rnd&action=boms');
            exit;
        }

        $file = $_FILES['import_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xlsx'])) {
            $_SESSION['error'] = 'Only .csv and .xlsx files are supported.';
            header('Location: ?controller=rnd&action=boms');
            exit;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            $_SESSION['error'] = 'File size must be less than 5MB.';
            header('Location: ?controller=rnd&action=boms');
            exit;
        }

        try {
            $tempPath = sys_get_temp_dir() . '/' . uniqid('bom_') . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $tempPath);
            $rows = SpreadsheetReader::read($tempPath);
            unlink($tempPath);
        } catch (\Exception $e) {
            if (isset($tempPath) && file_exists($tempPath)) unlink($tempPath);
            $_SESSION['error'] = 'Failed to read file: ' . $e->getMessage();
            header('Location: ?controller=rnd&action=boms');
            exit;
        }

        if (empty($rows)) {
            error_log('BOM Import: 0 rows. File: ' . $file['name'] . ' | Ext: ' . $ext);
            $_SESSION['error'] = 'No data rows found. Ensure Row 1 has headers.';
            header('Location: ?controller=rnd&action=boms');
            exit;
        }
        error_log('BOM Import: Headers: ' . json_encode(array_keys($rows[0])) . ' | Rows: ' . count($rows));

        $conn = \App\Core\BaseModel::getConnection();
        $preview = [];

        foreach ($rows as $idx => $row) {
            $rowNum = $idx + 2;
            $errors = [];
            $fgCode = trim($row['fg_item_code'] ?? '');
            $bomCode = trim($row['bom_code'] ?? '');
            $rmCode = trim($row['rm_code'] ?? '');
            $dosage = floatval($row['dosage_rate'] ?? 0);
            $wastage = floatval($row['wastage_pct'] ?? 0);

            if ($fgCode === '') $errors[] = 'FG_ITEM_CODE is required';
            if ($rmCode === '') $errors[] = 'RM_CODE is required';
            if ($dosage <= 0) $errors[] = 'DOSAGE_RATE must be > 0';

            $fgItem = null;
            if ($fgCode !== '') {
                $stmt = $conn->prepare("SELECT item_id, item_code, item_description FROM items WHERE item_code = :code AND `remove` = 0");
                $stmt->execute(['code' => $fgCode]);
                $fgItem = $stmt->fetch();
                if (!$fgItem) $errors[] = 'FG item not found: ' . htmlspecialchars($fgCode);
            }

            $rmItem = null;
            if ($rmCode !== '') {
                $stmt = $conn->prepare("SELECT item_id, item_code, item_description FROM items WHERE item_code = :code AND item_type IN ('RM','PM','SFG') AND `remove` = 0");
                $stmt->execute(['code' => $rmCode]);
                $rmItem = $stmt->fetch();
                if (!$rmItem) $errors[] = 'Ingredient item not found: ' . htmlspecialchars($rmCode);
            }

            $existingBom = null;
            if ($fgItem) {
                $existingBom = $this->bomModel->getBomForItem($fgItem['item_id']);
            }

            $preview[] = [
                'row' => $rowNum,
                'fg_item_code' => $fgCode,
                'fg_item_id' => $fgItem['item_id'] ?? null,
                'fg_name' => $fgItem['item_description'] ?? '',
                'bom_code' => $bomCode,
                'rm_code' => $rmCode,
                'item_id' => $rmItem['item_id'] ?? null,
                'item_name' => $rmItem['item_description'] ?? '',
                'dosage_rate' => $dosage,
                'wastage_pct' => $wastage,
                'existing_bom_id' => $existingBom['id'] ?? null,
                'status' => $existingBom ? 'update' : 'new',
                'errors' => $errors,
            ];
        }

        $_SESSION['import_preview_bom'] = $preview;
        $validRows = array_filter($preview, fn($r) => empty($r['errors']));
        $fgGroups = [];
        foreach ($validRows as $r) {
            $fgGroups[$r['fg_item_code']] = true;
        }
        $data['preview'] = $preview;
        $data['fgCount'] = count($fgGroups);
        $data['lineCount'] = count($validRows);
        $data['errorCount'] = count(array_filter($preview, fn($r) => !empty($r['errors'])));
        $data['page_title'] = 'Import BOM Recipes - Preview';
        $this->render('boms/import_preview', $data);
    }

    public function bomImportConfirm() {
        $preview = $_SESSION['import_preview_bom'] ?? null;
        if (!$preview) {
            $_SESSION['error'] = 'No import data found. Please upload again.';
            header('Location: ?controller=rnd&action=boms');
            exit;
        }
        unset($_SESSION['import_preview_bom']);

        $validRows = array_filter($preview, fn($r) => empty($r['errors']));
        $fgGroups = [];
        foreach ($validRows as $r) {
            $fgGroups[$r['fg_item_code']][] = $r;
        }

        $bomsCreated = 0;
        $bomsUpdated = 0;
        $lineCount = 0;
        $errors = count(array_filter($preview, fn($r) => !empty($r['errors'])));

        foreach ($fgGroups as $fgCode => $group) {
            $first = $group[0];
            try {
                $bomId = $first['existing_bom_id'];
                if ($bomId) {
                    $this->bomModel->update($bomId, $first['fg_item_id'], $first['bom_code'] ?: null);
                    $bomsUpdated++;
                } else {
                    $bomId = $this->bomModel->create($first['fg_item_id'], $first['bom_code'] ?: null);
                    $bomsCreated++;
                }

                $items = [];
                foreach ($group as $r) {
                    if ($r['item_id']) {
                        $items[] = [
                            'item_id' => $r['item_id'],
                            'dosage_rate' => $r['dosage_rate'],
                            'wastage_allowance_pct' => $r['wastage_pct']
                        ];
                        $lineCount++;
                    }
                }
                $this->bomModel->replaceBomItems($bomId, $items);
            } catch (\Exception $e) {
                $errors++;
            }
        }

        AuditModel::log($_SESSION['user_id'], 'IMPORT', 'rnd', "Bulk imported BOMs: {$bomsCreated} created, {$bomsUpdated} updated, {$lineCount} line items, {$errors} errors", null, ['boms_created' => $bomsCreated, 'boms_updated' => $bomsUpdated, 'line_items' => $lineCount, 'errors' => $errors], 'bom', null);
        $_SESSION['success'] = "BOM import complete: {$bomsCreated} created, {$bomsUpdated} updated, {$lineCount} line items imported";
        header('Location: ?controller=rnd&action=boms');
        exit;
    }

    // ─── Items Management & ERIC Import ───────────────────────────────────────

    public function items() {
        $search = $_GET['search'] ?? '';
        $typeFilter = $_GET['item_type'] ?? '';
        $filters = [];
        if ($search) $filters['search'] = $search;
        if ($typeFilter) $filters['item_type'] = $typeFilter;

        $all = $this->itemImportModel->getAllWithStock($filters);
        $pagination = Pagination::paginate($all, 20);
        $data['items'] = $pagination['items'];
        $data['page'] = $pagination['page'];
        $data['totalPages'] = $pagination['totalPages'];
        $data['total'] = $pagination['total'];
        $data['search'] = $search;
        $data['typeFilter'] = $typeFilter;
        $data['page_title'] = 'Item Master List';
        $this->render('items/index', $data);
    }

    public function itemImportForm() {
        $data['page_title'] = 'Import ERIC Items';
        $this->render('items/import_form', $data);
    }

    public function itemImportPreview() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['import_file'])) {
            $_SESSION['error'] = 'No file uploaded.';
            header('Location: ?controller=rnd&action=itemImportForm');
            exit;
        }

        $file = $_FILES['import_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xlsx'])) {
            $_SESSION['error'] = 'Only .csv and .xlsx files are supported.';
            header('Location: ?controller=rnd&action=itemImportForm');
            exit;
        }
        if ($file['size'] > 10 * 1024 * 1024) {
            $_SESSION['error'] = 'File size must be less than 10MB.';
            header('Location: ?controller=rnd&action=itemImportForm');
            exit;
        }

        try {
            $tempPath = sys_get_temp_dir() . '/' . uniqid('item_') . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $tempPath);
            $rows = SpreadsheetReader::read($tempPath);
            unlink($tempPath);
        } catch (\Exception $e) {
            if (isset($tempPath) && file_exists($tempPath)) unlink($tempPath);
            $_SESSION['error'] = 'Failed to read file: ' . $e->getMessage();
            header('Location: ?controller=rnd&action=itemImportForm');
            exit;
        }

        if (empty($rows)) {
            error_log('ERIC Import: 0 rows parsed. File: ' . $file['name'] . ' | Extension: ' . $ext . ' | Headers detected: ' . json_encode($rows));
            $_SESSION['error'] = 'No data rows found in the file. Please check the file format and ensure Row 1 contains headers.';
            header('Location: ?controller=rnd&action=itemImportForm');
            exit;
        }

        $detectedHeaders = array_keys($rows[0] ?? []);
        error_log('ERIC Import: Headers detected: ' . json_encode($detectedHeaders) . ' | Total rows: ' . count($rows));

        $preview = [];
        foreach ($rows as $idx => $row) {
            $rowNum = $idx + 2;
            $errors = [];
            $itemCode = trim($row['c_item'] ?? '');
            $description = trim($row['c_desc'] ?? '');
            $cType = trim($row['c_type'] ?? '');
            $um = trim($row['um'] ?? '');
            $site = trim($row['site'] ?? '');
            $qtyOnHand = floatval($row['qty_on_hand'] ?? 0);
            $qtyForInspect = floatval($row['qty_for_inspect'] ?? 0);

            if ($itemCode === '') $errors[] = 'c_item is required';
            if ($description === '') $errors[] = 'c_desc is required';
            if ($cType === '') $errors[] = 'c_type is required';
            if ($um === '') $errors[] = 'um is required';
            if ($site === '') $site = 'MAIN';

            $itemType = $cType !== '' ? ItemImportModel::mapItemType($cType) : 'SUPPLIES';
            $existing = $itemCode !== '' ? $this->itemImportModel->getItemByCode($itemCode) : null;

            $preview[] = [
                'row' => $rowNum,
                'item_code' => $itemCode,
                'description' => $description,
                'item_type' => $itemType,
                'raw_c_type' => $cType,
                'uom' => $um,
                'site' => $site,
                'qty_on_hand' => $qtyOnHand,
                'qty_for_inspect' => $qtyForInspect,
                'status' => $existing ? 'update' : 'new',
                'errors' => $errors,
            ];
        }

        $_SESSION['import_preview_items'] = $preview;
        $data['preview'] = $preview;
        $data['newCount'] = count(array_filter($preview, fn($r) => $r['status'] === 'new' && empty($r['errors'])));
        $data['updateCount'] = count(array_filter($preview, fn($r) => $r['status'] === 'update' && empty($r['errors'])));
        $data['errorCount'] = count(array_filter($preview, fn($r) => !empty($r['errors'])));
        $data['page_title'] = 'Import ERIC Items - Preview';
        $this->render('items/import_preview', $data);
    }

    public function itemImportConfirm() {
        $preview = $_SESSION['import_preview_items'] ?? null;
        if (!$preview) {
            $_SESSION['error'] = 'No import data found. Please upload again.';
            header('Location: ?controller=rnd&action=itemImportForm');
            exit;
        }
        unset($_SESSION['import_preview_items']);

        $conn = \App\Core\BaseModel::getConnection();
        $conn->beginTransaction();

        $created = 0;
        $updated = 0;
        $inventoryRows = 0;
        $errors = 0;

        try {
            foreach ($preview as $row) {
                if (!empty($row['errors'])) {
                    $errors++;
                    continue;
                }

                $itemId = $this->itemImportModel->upsertItem([
                    'item_code' => $row['item_code'],
                    'item_description' => $row['description'],
                    'item_type' => $row['item_type'],
                    'raw_c_type' => $row['raw_c_type'],
                    'item_uom' => $row['uom']
                ]);

                $existing = $this->itemImportModel->getItemByCode($row['item_code']);
                if ($existing && !isset($createdMap[$row['item_code']])) {
                    // Track whether this was a new insert or an update
                }

                if ($row['qty_on_hand'] > 0 || $row['qty_for_inspect'] > 0) {
                    $this->itemImportModel->upsertInventoryBalance(
                        $itemId,
                        $row['site'],
                        $row['qty_on_hand'],
                        $row['qty_for_inspect']
                    );
                    $inventoryRows++;
                }

                // Count new vs update
                // We check after upsert: if the item existed before, it's an update
                // This is approximate since we don't track pre-upsert state per row
            }

            $conn->commit();

            // Count new vs updated from preview data
            $created = count(array_filter($preview, fn($r) => empty($r['errors']) && $r['status'] === 'new'));
            $updated = count(array_filter($preview, fn($r) => empty($r['errors']) && $r['status'] === 'update'));

            AuditModel::log($_SESSION['user_id'], 'IMPORT', 'rnd', "ERIC item import: {$created} new, {$updated} updated, {$inventoryRows} inventory records, {$errors} errors", null, ['created' => $created, 'updated' => $updated, 'inventory' => $inventoryRows, 'errors' => $errors], 'item', null);
            $_SESSION['success'] = "Import complete: {$created} new items, {$updated} updated, {$inventoryRows} inventory records";
            header('Location: ?controller=rnd&action=items');
            exit;
        } catch (\Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = 'Import failed: ' . $e->getMessage();
            header('Location: ?controller=rnd&action=itemImportForm');
            exit;
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function getDbErrorMessage(\PDOException $e, $indexName, $fieldLabel) {
        $errorCode = $e->errorInfo[1] ?? null;
        $errorMessage = $e->getMessage();
        if ($errorCode === 1062) {
            return "$fieldLabel already exists. Please try a new value.";
        }
        return "Database error: $errorMessage";
    }

    private function render($view, $data = []) {
        extract($data);
        ob_start();
        include __DIR__ . "/../views/{$view}.php";
        $content = ob_get_clean();
        include __DIR__ . "/../views/layouts/main.php";
    }
}
