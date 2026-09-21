<?php
namespace App\Models;

use App\Core\BaseModel;

class MoModel extends BaseModel {
    public function ensureSchema() {
        $pdo = self::getConnection();

        $pdo->exec("CREATE TABLE IF NOT EXISTS manufacturing_orders (
            mo_id INT AUTO_INCREMENT PRIMARY KEY,
            mo_number VARCHAR(100) NOT NULL,
            mo_type VARCHAR(50) NOT NULL DEFAULT 'Standard',
            mo_site VARCHAR(100) NOT NULL DEFAULT '001 - Sterling Technopark',
            order_date DATE NULL,
            due_date DATE NULL,
            planned_start_date DATE NULL,
            priority INT NOT NULL DEFAULT 3,
            reference_no VARCHAR(100) NULL,
            mo_status VARCHAR(50) NOT NULL DEFAULT 'Planned',
            customer_id INT NULL,
            customer_code VARCHAR(50) NULL,
            customer_name VARCHAR(150) NULL,
            batch_lot_no VARCHAR(100) NULL,
            po_number VARCHAR(100) NULL,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uk_manufacturing_orders_number (mo_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS manufacturing_order_items (
            moi_id INT AUTO_INCREMENT PRIMARY KEY,
            mo_id INT NOT NULL,
            item_id INT NULL,
            item_code VARCHAR(100) NULL,
            item_description VARCHAR(255) NULL,
            uom VARCHAR(50) NULL,
            item_type VARCHAR(50) NULL,
            site VARCHAR(100) NULL,
            qty_ordered DECIMAL(15,2) NOT NULL DEFAULT 0,
            so_number VARCHAR(100) NULL,
            bom_code VARCHAR(100) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_mo_items_mo_id (mo_id),
            CONSTRAINT fk_mo_items_mo FOREIGN KEY (mo_id) REFERENCES manufacturing_orders(mo_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function getAll($status = null) {
        $this->ensureSchema();
        $sql = "SELECT mo.*
                FROM manufacturing_orders mo";
        $params = [];

        if ($status && $status !== 'all') {
            $sql .= " WHERE mo.mo_status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY mo.created_at DESC";

        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$order) {
            $order['items'] = $this->getItemsByMoId($order['mo_id']);
            $order['item_code'] = $order['items'][0]['item_code'] ?? '-';
            $order['qty_ordered'] = $order['items'][0]['qty_ordered'] ?? 0;
        }

        return $orders;
    }

    public function getItemsByMoId($moId) {
        $sql = "SELECT * FROM manufacturing_order_items WHERE mo_id = :mo_id ORDER BY moi_id ASC";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['mo_id' => $moId]);
        return $stmt->fetchAll();
    }

    public function create($data, $items) {
        $this->ensureSchema();
        $pdo = self::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("INSERT INTO manufacturing_orders (
                mo_number, mo_type, mo_site, order_date, due_date, planned_start_date, priority,
                reference_no, mo_status, customer_id, customer_code, customer_name,
                batch_lot_no, po_number, created_by
            ) VALUES (
                :mo_number, :mo_type, :mo_site, :order_date, :due_date, :planned_start_date, :priority,
                :reference_no, :mo_status, :customer_id, :customer_code, :customer_name,
                :batch_lot_no, :po_number, :created_by
            )");

            $stmt->execute([
                'mo_number' => trim($data['mo_number']),
                'mo_type' => $data['mo_type'] ?? 'Standard',
                'mo_site' => $data['mo_site'] ?? '001 - Sterling Technopark',
                'order_date' => $data['order_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'planned_start_date' => $data['planned_start_date'] ?? null,
                'priority' => (int) ($data['priority'] ?? 3),
                'reference_no' => $data['reference_no'] ?? null,
                'mo_status' => $data['mo_status'] ?? 'Planned',
                'customer_id' => $data['customer_id'] ?? null,
                'customer_code' => $data['customer_code'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'batch_lot_no' => $data['batch_lot_no'] ?? null,
                'po_number' => $data['po_number'] ?? null,
                'created_by' => $_SESSION['user_id'] ?? null
            ]);

            $moId = $pdo->lastInsertId();

            if (!empty($items)) {
                $itemStmt = $pdo->prepare("INSERT INTO manufacturing_order_items (
                    mo_id, item_id, item_code, item_description, uom, item_type, site, qty_ordered, so_number, bom_code
                ) VALUES (
                    :mo_id, :item_id, :item_code, :item_description, :uom, :item_type, :site, :qty_ordered, :so_number, :bom_code
                )");

                foreach ($items as $item) {
                    $itemStmt->execute([
                        'mo_id' => $moId,
                        'item_id' => $item['item_id'] ?? null,
                        'item_code' => $item['item_code'] ?? null,
                        'item_description' => $item['item_description'] ?? null,
                        'uom' => $item['uom'] ?? null,
                        'item_type' => $item['item_type'] ?? null,
                        'site' => $item['site'] ?? '001 - Sterling Technopark',
                        'qty_ordered' => (float) ($item['qty_ordered'] ?? 0),
                        'so_number' => $item['so_number'] ?? null,
                        'bom_code' => $item['bom_code'] ?? null,
                    ]);
                }
            }

            $pdo->commit();
            return $moId;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function findById($moId) {
        $this->ensureSchema();
        $sql = "SELECT * FROM manufacturing_orders WHERE mo_id = :mo_id LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['mo_id' => $moId]);
        return $stmt->fetch();
    }

    public function updateStatus($moId, $status) {
        $this->ensureSchema();
        $sql = "UPDATE manufacturing_orders SET mo_status = :status, updated_at = NOW() WHERE mo_id = :mo_id";
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute(['status' => $status, 'mo_id' => $moId]);
    }
}
