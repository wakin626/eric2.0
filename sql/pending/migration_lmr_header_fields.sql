-- LMR Header Reconfiguration: per PPIC spec
-- Adds optional shop-floor date fields, lot tracking fields, and informational
-- header formulation/item/qty fields to lmr_orders. All nullable.
ALTER TABLE lmr_orders
  ADD COLUMN date_started  DATE NULL AFTER planned_start_date,
  ADD COLUMN date_finished DATE NULL AFTER date_started,
  ADD COLUMN expiry_date   DATE NULL AFTER date_finished,
  ADD COLUMN lot_series    VARCHAR(100) NULL AFTER batch_lot_no,
  ADD COLUMN lot_number    VARCHAR(100) NULL AFTER lot_series,
  ADD COLUMN bom_code      VARCHAR(50) NULL AFTER po_number,
  ADD COLUMN item_code     VARCHAR(50) NULL AFTER bom_code,
  ADD COLUMN item_description VARCHAR(255) NULL AFTER item_code,
  ADD COLUMN qty_ordered   DECIMAL(15,2) NULL AFTER item_description;
