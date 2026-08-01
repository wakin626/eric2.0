CREATE TABLE IF NOT EXISTS backloads (
    backload_id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_id INT NOT NULL,
    po_id INT NOT NULL,
    poi_id INT NOT NULL,
    lot_id INT NOT NULL,
    lot_number VARCHAR(50) NULL,
    quantity INT NOT NULL,
    reason TEXT,
    backloaded_by INT NOT NULL,
    backload_date DATE NOT NULL,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    `remove` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (delivery_id) REFERENCES deliveries(delivery_id),
    FOREIGN KEY (po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (poi_id) REFERENCES purchase_order_items(poi_id),
    FOREIGN KEY (lot_id) REFERENCES production_lots(lot_id),
    FOREIGN KEY (backloaded_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
