-- oc_999_rollback.sql
-- OpenChair — full rollback for oc_001 … oc_006.
--
-- This file is short, and that is the point: OpenChair adds NO column to any engine
-- table and creates NO object that is not prefixed `oc_`.  There is nothing to
-- un-ALTER — uninstalling OpenChair is exactly "drop the oc_ island".  (Contrast
-- ic_999_rollback.sql, which has to walk four engine tables dropping columns.)
--
-- Idempotent: only drops what exists.
-- WARNING: this destroys every scan ever taken.  The global observation history is
-- expensive to rebuild — it can only be re-accumulated one night at a time.
-- Back up first.  Tables are listed children-first; FK checks are also disabled so
-- the order cannot bite on a partially-applied install.

SET @oc_fk := @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

-- oc_006 — alerts / pitch / ledger (tenant surfaces) -------------------------
DROP TABLE IF EXISTS `oc_ledger_report`;
DROP TABLE IF EXISTS `oc_pitch`;
DROP TABLE IF EXISTS `oc_alert_item`;
DROP TABLE IF EXISTS `oc_alert_run`;

-- oc_005 — tenant layer ------------------------------------------------------
DROP TABLE IF EXISTS `oc_prospect`;
DROP TABLE IF EXISTS `oc_market`;

-- oc_004 — rollups -----------------------------------------------------------
DROP TABLE IF EXISTS `oc_deadzone_cell`;
DROP TABLE IF EXISTS `oc_vacancy_weekly`;
DROP TABLE IF EXISTS `oc_vacancy_daily`;

-- oc_003 — Radar -------------------------------------------------------------
DROP TABLE IF EXISTS `oc_scan_snapshot`;
DROP TABLE IF EXISTS `oc_scan_run`;
DROP TABLE IF EXISTS `oc_scan_queue`;

-- oc_002 — global business layer ---------------------------------------------
DROP TABLE IF EXISTS `oc_staff`;
DROP TABLE IF EXISTS `oc_business_service`;
DROP TABLE IF EXISTS `oc_business`;

-- oc_001 — reference ---------------------------------------------------------
DROP TABLE IF EXISTS `oc_niche`;
DROP TABLE IF EXISTS `oc_platform`;

SET FOREIGN_KEY_CHECKS = @oc_fk;

-- Engine tables touched by this rollback: NONE.
