-- Excess Production Table
CREATE TABLE IF NOT EXISTS excess_production (
    excess_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    item_id INT NOT NULL,
    source_po_id INT NOT NULL,
    source_poi_id INT NOT NULL,
    excess_quantity INT NOT NULL,
    consumed_quantity INT DEFAULT 0,
    remaining_quantity INT GENERATED ALWAYS AS (excess_quantity - consumed_quantity) STORED,
    status ENUM('pending','partial','consumed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (item_id) REFERENCES items(item_id),
    FOREIGN KEY (source_po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (source_poi_id) REFERENCES purchase_order_items(poi_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
