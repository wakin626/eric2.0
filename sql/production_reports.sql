-- Add item-level tracking to production_history
ALTER TABLE `production_history` 
  ADD COLUMN `poi_id` INT(11) DEFAULT NULL AFTER `po_id`,
  ADD COLUMN `lot_number` VARCHAR(100) DEFAULT NULL AFTER `poi_id`,
  ADD COLUMN `item_description` VARCHAR(255) DEFAULT NULL AFTER `lot_number`;

-- Add audit columns to production_history
ALTER TABLE `production_history`
  ADD COLUMN `edited_by` INT(11) DEFAULT NULL AFTER `user_id`,
  ADD COLUMN `date_edited` DATETIME DEFAULT NULL AFTER `date_created`;

-- Add old value columns to production_history
ALTER TABLE `production_history`
  ADD COLUMN `old_lot_number` VARCHAR(100) DEFAULT NULL AFTER `date_edited`,
  ADD COLUMN `old_added_quantity` INT(11) DEFAULT NULL AFTER `old_lot_number`;

-- Create production_reports table
CREATE TABLE IF NOT EXISTS `production_reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `history_id` int(11) NOT NULL,
  `poi_id` int(11) NOT NULL,
  `po_id` int(11) NOT NULL,
  `old_lot_number` varchar(100) DEFAULT NULL,
  `reported_by` int(11) NOT NULL,
  `reason` text NOT NULL,
  `report_type` enum('lot_number','quantity') DEFAULT 'lot_number',
  `status` enum('pending','resolved') DEFAULT 'pending',
  `resolved_by` int(11) DEFAULT NULL,
  `new_lot_number` varchar(100) DEFAULT NULL,
  `date_reported` datetime DEFAULT current_timestamp(),
  `date_resolved` datetime DEFAULT NULL,
  PRIMARY KEY (`report_id`),
  KEY `idx_history_id` (`history_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
