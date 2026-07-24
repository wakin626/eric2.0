-- Migration: Add UNIQUE constraint on sts_ref to prevent duplicate STS numbers
-- Run this BEFORE deploying the code fix

-- Step 1: Find any duplicate sts_ref values (excluding NULLs)
-- Run this query first to check for duplicates:
-- SELECT sts_ref, COUNT(*) as cnt FROM production_history WHERE sts_ref IS NOT NULL GROUP BY sts_ref HAVING cnt > 1;

-- Step 2: If duplicates exist, clean them up by keeping only the first occurrence
-- UPDATE production_history ph1
-- INNER JOIN (
--     SELECT MIN(history_id) as keep_id, sts_ref
--     FROM production_history
--     WHERE sts_ref IS NOT NULL
--     GROUP BY sts_ref
--     HAVING COUNT(*) > 1
-- ) ph2 ON ph1.sts_ref = ph2.sts_ref AND ph1.history_id > ph2.keep_id
-- SET ph1.sts_ref = CONCAT(ph1.sts_ref, '-dup-', ph1.history_id);

-- Step 3: Add the unique index (only run after cleaning up duplicates)
ALTER TABLE production_history ADD UNIQUE INDEX idx_sts_ref_unique (sts_ref);
