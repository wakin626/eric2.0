-- Adds the timestamp used when a PO first reaches full delivery.
ALTER TABLE purchase_orders ADD COLUMN completed_at DATETIME NULL AFTER status;
