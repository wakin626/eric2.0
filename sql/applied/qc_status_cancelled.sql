-- Allow cancelling pending QC queue entries when their purchasing PO is cancelled,
-- so ghost items leave the inspection queue without deleting the staging row.
ALTER TABLE receiving_items
    MODIFY qc_status ENUM('PENDING_QC','PASSED','REJECTED','CANCELLED') NOT NULL DEFAULT 'PENDING_QC';
