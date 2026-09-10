-- Add soft-delete column to production_history for 5-minute undo feature
ALTER TABLE production_history ADD COLUMN is_removed TINYINT(1) DEFAULT 0;
