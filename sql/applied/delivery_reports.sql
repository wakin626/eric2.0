-- Create delivery_reports table
CREATE TABLE IF NOT EXISTS `delivery_reports` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `delivery_id` int(11) NOT NULL,
  `poi_id` int(11) DEFAULT NULL,
  `po_id` int(11) NOT NULL,
  `lot_id` int(11) DEFAULT NULL,
  `old_quantity` int(11) DEFAULT NULL,
  `reported_by` int(11) NOT NULL,
  `reason` text NOT NULL,
  `report_type` enum('dr_number','quantity') DEFAULT 'dr_number',
  `status` enum('pending','resolved') DEFAULT 'pending',
  `resolved_by` int(11) DEFAULT NULL,
  `new_quantity` int(11) DEFAULT NULL,
  `date_reported` datetime DEFAULT current_timestamp(),
  `date_resolved` datetime DEFAULT NULL,
  PRIMARY KEY (`report_id`),
  KEY `idx_delivery_id` (`delivery_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
