-- Fix independent lots: link production_lots to PO items when they were delivered with poi_id assignment
-- This handles cases where lots were produced independently (poi_id=NULL) but delivered against a PO

-- Step 1: Update production_lots.po_id and poi_id from delivery lot_items JSON
UPDATE production_lots pl
JOIN (
    SELECT 
        JSON_UNQUOTE(JSON_EXTRACT(d.lot_items, CONCAT('$[', idx.i, '].lot_id'))) AS lot_id,
        JSON_UNQUOTE(JSON_EXTRACT(d.lot_items, CONCAT('$[', idx.i, '].poi_id'))) AS poi_id,
        d.po_id
    FROM deliveries d
    JOIN JSON_TABLE(
        JSON_KEYS(d.lot_items),
        '$[*]' COLUMNS (i FOR ORDINALITY)
    ) AS idx
    WHERE d.`remove` = 0
      AND d.lot_items IS NOT NULL
      AND JSON_UNQUOTE(JSON_EXTRACT(d.lot_items, CONCAT('$[', idx.i, '].poi_id'))) IS NOT NULL
      AND JSON_UNQUOTE(JSON_EXTRACT(d.lot_items, CONCAT('$[', idx.i, '].poi_id'))) != 'null'
      AND JSON_UNQUOTE(JSON_EXTRACT(d.lot_items, CONCAT('$[', idx.i, '].poi_id'))) != ''
) AS delivery_data ON pl.lot_id = delivery_data.lot_id
SET 
    pl.po_id = delivery_data.po_id,
    pl.poi_id = delivery_data.poi_id
WHERE pl.poi_id IS NULL
  AND pl.po_id IS NULL
  AND pl.is_removed = 0;

-- Step 2: Recalculate all purchase_order_items.produced_quantity
UPDATE purchase_order_items poi
SET produced_quantity = (
    SELECT COALESCE(SUM(pl.quantity_produced), 0)
    FROM production_lots pl
    WHERE pl.poi_id = poi.poi_id AND pl.is_removed = 0
);

-- Step 3: Recalculate all purchase_order_items.delivered_quantity  
UPDATE purchase_order_items poi
SET delivered_quantity = GREATEST(0,
    (SELECT COALESCE(SUM(d.delivery_quantity), 0) FROM deliveries d
    WHERE d.poi_id = poi.poi_id AND d.`remove` = 0)
    - COALESCE((SELECT SUM(b.quantity) FROM backloads b WHERE b.poi_id = poi.poi_id AND b.`remove` = 0), 0)
);

-- Step 4: Recalculate all purchase_orders.produced_quantity
UPDATE purchase_orders po
SET produced_quantity = (
    SELECT COALESCE(SUM(produced_quantity), 0) FROM purchase_order_items WHERE po_id = po.po_id
);

-- Step 5: Recalculate all purchase_orders.delivered_quantity
UPDATE purchase_orders po
SET delivered_quantity = (
    SELECT COALESCE(SUM(delivered_quantity), 0) FROM purchase_order_items WHERE po_id = po.po_id
);
