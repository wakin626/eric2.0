-- Migration: Remove Advance & Excess Production modules
-- Date: 2026-09-02
-- These tables are no longer used. Kept in DB for historical reference only.

-- Mark advance_production_consumption as unused (no DROP for safety)
-- The table remains but all code references are removed.

-- Mark excess_production as unused (no DROP for safety)
-- The table remains but all code references are removed.

-- Hide advance POs from normal queries by ensuring production_type is always 'normal' going forward
-- No schema change needed - we just filter them out in code.
