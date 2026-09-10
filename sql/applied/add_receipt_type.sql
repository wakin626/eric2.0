ALTER TABLE `delivery_receipts` ADD COLUMN `type` ENUM('dr','si') DEFAULT 'dr' AFTER `file_size`;

-- Existing warehouse uploads remain as 'dr' (default)
-- Finance SI uploads will be 'si'
