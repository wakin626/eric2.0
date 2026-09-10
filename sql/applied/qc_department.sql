-- QC Department Migration
-- Run this SQL to add QC department support

-- Add 'qc' to the department ENUM in users table
ALTER TABLE users MODIFY department ENUM('admin', 'warehouse', 'production', 'finance', 'qc') NOT NULL;

-- Add QC inspection columns to production_history
ALTER TABLE production_history
  ADD COLUMN qc_remark TEXT DEFAULT NULL AFTER `sts_remarks`,
  ADD COLUMN qc_inspected_by INT(11) DEFAULT NULL AFTER `qc_remark`,
  ADD COLUMN qc_inspected_at DATETIME DEFAULT NULL AFTER `qc_inspected_by`,
  ADD COLUMN qc_inspector_name VARCHAR(255) DEFAULT NULL AFTER `qc_inspected_at`;
