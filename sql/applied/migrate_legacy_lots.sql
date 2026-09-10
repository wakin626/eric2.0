-- Migration: Create LEGACY production_lots for old deliveries without lot_id
-- Date: 2026-06-24

-- Create LEGACY lot for poi_id 14 (PO 15, Item 2, delivery_id 5, qty 100)
INSERT INTO production_lots (po_id, poi_id, lot_number, quantity_produced, lot_date, created_by)
VALUES (15, 14, 'LEGACY', 100, CURDATE(), 1);

SET @legacy_lot_14 = LAST_INSERT_ID();

-- Link delivery 5 to this lot
UPDATE deliveries SET lot_id = @legacy_lot_14 WHERE delivery_id = 5;

-- Create LEGACY lot for poi_id 13 (PO 14, Alcoplus, delivery_id 6, qty 200)
INSERT INTO production_lots (po_id, poi_id, lot_number, quantity_produced, lot_date, created_by)
VALUES (14, 13, 'LEGACY', 200, CURDATE(), 1);

SET @legacy_lot_13 = LAST_INSERT_ID();

-- Link delivery 6 to this lot
UPDATE deliveries SET lot_id = @legacy_lot_13 WHERE delivery_id = 6;

-- Verify
SELECT d.delivery_id, d.po_id, d.poi_id, pl.lot_number, d.delivery_quantity, d.dr_number
FROM deliveries d
LEFT JOIN production_lots pl ON d.lot_id = pl.lot_id
WHERE d.`remove` = 0;
