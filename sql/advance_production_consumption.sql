-- Advance Production Consumption Table
-- Tracks when advance production items are allocated to normal POs
CREATE TABLE IF NOT EXISTS advance_production_consumption (
    id INT AUTO_INCREMENT PRIMARY KEY,
    advance_poi_id INT NOT NULL COMMENT 'Which advance PO item was consumed',
    advance_po_id INT NOT NULL,
    normal_poi_id INT NOT NULL COMMENT 'Which normal PO item received it',
    normal_po_id INT NOT NULL,
    quantity INT NOT NULL COMMENT 'How much was allocated',
    date_allocated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (advance_poi_id) REFERENCES purchase_order_items(poi_id),
    FOREIGN KEY (advance_po_id) REFERENCES purchase_orders(po_id),
    FOREIGN KEY (normal_poi_id) REFERENCES purchase_order_items(poi_id),
    FOREIGN KEY (normal_po_id) REFERENCES purchase_orders(po_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
