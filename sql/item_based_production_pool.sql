-- Migration: Item-Based Production Pool
-- Decouples production entries from POs. Production logs by item_id + lot_number.
-- Date: 2026-09-02

-- 1. Backfill item_id on production_lots where NULL (derive from poi_id)
UPDATE production_lots pl
JOIN purchase_order_items poi ON pl.poi_id = poi.poi_id
SET pl.item_id = poi.item_id
WHERE pl.item_id IS NULL AND pl.poi_id IS NOT NULL;

-- 2. Add item_id to production_history if missing
ALTER TABLE production_history
  ADD COLUMN IF NOT EXISTS item_id INT NULL AFTER poi_id;

-- 3. Backfill item_id on production_history where NULL (derive from poi_id)
UPDATE production_history ph
JOIN purchase_order_items poi ON ph.poi_id = poi.poi_id
SET ph.item_id = poi.item_id
WHERE ph.item_id IS NULL AND ph.poi_id IS NOT NULL;

-- 4. Make production_history.po_id nullable for item-based entries
ALTER TABLE production_history
  MODIFY COLUMN po_id INT NULL;

-- 5. Ensure indexes for UPSERT lookups
ALTER TABLE production_lots
  ADD INDEX IF NOT EXISTS idx_item_lot_active (item_id, lot_number, is_removed);

