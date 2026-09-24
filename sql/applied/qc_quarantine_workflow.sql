-- QC quarantine/inspection workflow tables.
-- NOTE: receiving_items already exists in the live schema (PK `id`).
-- This migration creates only the missing audit + stock tables.

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
    CONSTRAINT fk_qc_receiving_item FOREIGN KEY (receiving_item_id) REFERENCES receiving_items(`id`) ON DELETE CASCADE
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
