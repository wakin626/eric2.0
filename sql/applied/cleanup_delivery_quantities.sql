-- Cleanup script: Fix existing delivery quantities that may be out of sync
-- Run this AFTER deploying the code fix to repair any existing data drift

-- Step 1: Fix POI delivered_quantity by recalculating from all active deliveries
UPDATE purchase_order_items poi
SET delivered_quantity = (
    SELECT COALESCE(SUM(d.delivery_quantity), 0) FROM deliveries d
    WHERE d.poi_id = poi.poi_id AND d.`remove` = 0
);

-- Step 2: Fix PO delivered_quantity by recalculating from all active deliveries
UPDATE purchase_orders po
SET delivered_quantity = (
    SELECT COALESCE(SUM(d.delivery_quantity), 0) FROM deliveries d
    WHERE d.po_id = po.po_id AND d.`remove` = 0
);

-- Verification query: Check for deliveries where delivery_quantity doesn't
-- match the sum of lot_items quantities (run manually to inspect)
-- Note: This requires examining the JSON in lot_items
-- SELECT d.delivery_id, d.po_id, d.delivery_quantity, d.lot_items
-- FROM deliveries d
-- WHERE d.lot_items IS NOT NULL AND d.`remove` = 0
-- ORDER BY d.delivery_id DESC;
