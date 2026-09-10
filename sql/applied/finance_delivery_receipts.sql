-- Delivery Receipts table for Finance module
-- Allows attaching receipt files to deliveries

CREATE TABLE IF NOT EXISTS delivery_receipts (
    receipt_id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_id INT NOT NULL,
    po_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100),
    file_size INT DEFAULT 0,
    uploaded_by INT NOT NULL,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    `remove` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (delivery_id) REFERENCES deliveries(delivery_id),
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
