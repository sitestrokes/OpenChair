-- oc_014_name_from_slug.sql — know when a business name is only a placeholder.
--
-- docs/70-radar-build.md R1/R3. Discovery creates a business from a pasted URL
-- before anything has been read. Booksy URLs carry a slug ("fade-lab") that
-- title-cases into a decent guess; Square URLs carry nothing, so the row is
-- "Unnamed business". The first successful read reports the shop's published
-- name, and the completion pipeline may replace the name ONLY when it is still a
-- placeholder — this flag says so explicitly instead of comparing strings.
--
-- A name a person typed, or one an earlier read set, is never overwritten.
--
-- Idempotent: safe to re-run.

SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''oc_business.name_from_slug already present''',
    'ALTER TABLE `oc_business`
       ADD COLUMN `name_from_slug` tinyint(1) NOT NULL DEFAULT 0
         COMMENT ''1 = name is a discovery placeholder a scan may replace''
         AFTER `name`')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_business' AND COLUMN_NAME = 'name_from_slug'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `oc_business` SET `name_from_slug` = 1 WHERE `name` = 'Unnamed business' AND `name_from_slug` = 0;
