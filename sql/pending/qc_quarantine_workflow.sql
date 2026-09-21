CREATE TABLE IF NOT EXISTS receiving_items (
    receiving_item_id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NULL,
    source_type VARCHAR(50) NOT NULL DEFAULT 'purchase_order',
    source_id INT NULL,
    supplier_name VARCHAR(255) NULL,
    item_id INT NULL,
    item_code VARCHAR(100) NULL,
    item_description VARCHAR(255) NULL,
    lot_number VARCHAR(100) NULL,
    received_qty DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    received_date DATE NOT NULL,
    status ENUM('QUARANTINE','PASSED','REJECTED') NOT NULL DEFAULT 'QUARANTINE',
    qc_status ENUM('QUARANTINE','PASSED','REJECTED') NULL,
    passed_qty DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    rejected_qty DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    qc_inspected_by VARCHAR(255) NULL,
    qc_inspected_at DATETIME NULL,
    qc_remarks TEXT NULL,
    remarks TEXT NULL,
    created_by INT NULL,
    warehouse_user_id INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_status_received (status, received_date),
    KEY idx_po_item (po_id, item_id),
    KEY idx_lot_number (lot_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qc_inspections (
    inspection_id INT AUTO_INCREMENT PRIMARY KEY,
    receiving_item_id INT NOT NULL,
    decision ENUM('PASSED','REJECTED') NOT NULL,
    passed_qty DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    rejected_qty DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    inspector_name VARCHAR(255) NOT NULL,
    remarks TEXT NULL,
    inspected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_receiving_item_id (receiving_item_id),
    CONSTRAINT fk_qc_receiving_item FOREIGN KEY (receiving_item_id) REFERENCES receiving_items(receiving_item_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inventory_stock (
    stock_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    site_code VARCHAR(50) NOT NULL DEFAULT 'MAIN',
    lot_number VARCHAR(100) NULL,
    qty_on_hand DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    qty_blocked DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    qty_rejected DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    status ENUM('PASSED','REJECTED') NOT NULL DEFAULT 'PASSED',
    source_receiving_item_id INT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id INT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_stock_item_lot_site (item_id, site_code, lot_number, status),
    KEY idx_status (status),
    KEY idx_source_receiving_item (source_receiving_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE OR REPLACE VIEW vw_lmr_available_stock AS
SELECT
    item_id,
    site_code,
    lot_number,
    SUM(qty_on_hand) AS available_qty,
    MAX(updated_at) AS last_updated
FROM inventory_stock
WHERE status = 'PASSED' AND qty_on_hand > 0
GROUP BY item_id, site_code, lot_number;
