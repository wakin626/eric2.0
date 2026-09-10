-- Notifications System Migration
-- Run this SQL to add notification support

-- Notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('delivery','production','po','finance','qc','backload') NOT NULL COMMENT 'Notification category',
  `target_department` enum('admin','warehouse','production','finance','qc') NOT NULL COMMENT 'Department that should see this notification',
  `target_url` varchar(500) DEFAULT NULL COMMENT 'URL to navigate to when notification is clicked',
  `created_by` int(11) DEFAULT NULL COMMENT 'User who triggered the notification',
  `date_created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `idx_target_dept` (`target_department`),
  KEY `idx_type` (`type`),
  KEY `idx_date_created` (`date_created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Per-user read tracking
CREATE TABLE IF NOT EXISTS `notification_reads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_read` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_read` (`notification_id`, `user_id`),
  KEY `idx_user_id` (`user_id`),
  FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
