-- Migration: Allow duplicate item_code for different customers
-- Run this SQL on your existing database

-- 1. Drop the UNIQUE constraint on item_code
ALTER TABLE items DROP INDEX item_code;

-- 2. Add customer_id column if it doesn't exist
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'items'
    AND COLUMN_NAME = 'customer_id'
);
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE items ADD COLUMN customer_id INT(11) DEFAULT NULL AFTER item_description',
    'SELECT "customer_id column already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
