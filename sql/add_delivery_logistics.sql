ALTER TABLE `deliveries` ADD COLUMN `plate_number` VARCHAR(50) NULL AFTER `si_number`;
ALTER TABLE `deliveries` ADD COLUMN `vehicle_type` VARCHAR(50) NULL AFTER `plate_number`;
ALTER TABLE `deliveries` ADD COLUMN `logistic_provider` VARCHAR(100) NULL AFTER `vehicle_type`;
