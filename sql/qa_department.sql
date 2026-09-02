-- QA Department Migration
-- Run this SQL to add QA department support

-- Add 'qa' to the department ENUM in users table
ALTER TABLE users MODIFY department ENUM('admin', 'warehouse', 'production', 'finance', 'qc', 'qa') NOT NULL;

-- Add QA inspection columns to production_history
ALTER TABLE production_history
  ADD COLUMN qa_remark TEXT DEFAULT NULL AFTER `qc_inspector_name`,
  ADD COLUMN qa_inspected_by INT(11) DEFAULT NULL AFTER `qa_remark`,
  ADD COLUMN qa_inspected_at DATETIME DEFAULT NULL AFTER `qa_inspected_by`,
  ADD COLUMN qa_inspector_name VARCHAR(255) DEFAULT NULL AFTER `qa_inspected_at`;
