-- MRP Allocations Table
-- Tracks material allocations when MOs are released
-- Prevents double-allocation by summing allocated_qty per item

CREATE TABLE IF NOT EXISTS mrp_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mo_id INT NOT NULL,
    moi_id INT NOT NULL,
    item_id INT NOT NULL,
    allocated_qty DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
    status ENUM('active','released','cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_mo_item (mo_id, item_id),
    KEY idx_mo_id (mo_id),
    KEY idx_item_id (item_id),
    KEY idx_status (status),
    CONSTRAINT fk_alloc_mo FOREIGN KEY (mo_id) REFERENCES manufacturing_orders(mo_id) ON DELETE CASCADE,
    CONSTRAINT fk_alloc_moi FOREIGN KEY (moi_id) REFERENCES manufacturing_order_items(moi_id) ON DELETE CASCADE,
    CONSTRAINT fk_alloc_item FOREIGN KEY (item_id) REFERENCES items(item_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
