-- Add over-shipment flag to deliveries table
ALTER TABLE deliveries ADD COLUMN is_over_shipment TINYINT(1) DEFAULT 0;
