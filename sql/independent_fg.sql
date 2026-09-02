-- Migration: Make production lots independent of PO
-- Adds item_id column and makes po_id/pi_id nullable for new independent FG production

ALTER TABLE production_lots 
  ADD COLUMN item_id INT NULL AFTER poi_id;

ALTER TABLE production_lots 
  MODIFY COLUMN po_id INT NULL,
  MODIFY COLUMN poi_id INT NULL;

ALTER TABLE production_history 
  MODIFY COLUMN po_id INT NULL;
