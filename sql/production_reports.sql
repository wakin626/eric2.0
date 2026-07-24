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

-- Add STS ref column to production_history
ALTER TABLE `production_history`
  ADD COLUMN `sts_ref` VARCHAR(255) DEFAULT NULL AFTER `item_description`;

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

-- Add STS additional fields from Google Form
ALTER TABLE `production_history`
  ADD COLUMN `shift` VARCHAR(50) DEFAULT NULL AFTER `sts_ref`,
  ADD COLUMN `mo_no` VARCHAR(100) DEFAULT NULL AFTER `shift`,
  ADD COLUMN `material_type` VARCHAR(100) DEFAULT NULL AFTER `mo_no`,
  ADD COLUMN `reject_status` VARCHAR(100) DEFAULT NULL AFTER `material_type`,
  ADD COLUMN `sts_remarks` TEXT DEFAULT NULL AFTER `reject_status`;

-- Add PCS per case column to production_history
ALTER TABLE `production_history`
  ADD COLUMN `pcs_per_case` INT(11) DEFAULT NULL AFTER `sts_remarks`;

-- Add signature columns to production_history
ALTER TABLE `production_history`
  ADD COLUMN `prepared_by_name` VARCHAR(255) DEFAULT NULL AFTER `pcs_per_case`,
  ADD COLUMN `checked_by_name` VARCHAR(255) DEFAULT NULL AFTER `prepared_by_name`,
  ADD COLUMN `received_by_name` VARCHAR(255) DEFAULT NULL AFTER `checked_by_name`;

-- Add pcs_per_case to production_lots for warehouse connection
ALTER TABLE `production_lots`
  ADD COLUMN `pcs_per_case` INT(11) DEFAULT NULL AFTER `quantity_produced`;
