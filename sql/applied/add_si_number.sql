ALTER TABLE `deliveries` ADD COLUMN `si_number` VARCHAR(50) DEFAULT NULL AFTER `dr_number`;
ALTER TABLE `deliveries` ADD UNIQUE KEY `uk_si_number` (`si_number`);
