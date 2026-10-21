-- oc_015_search_and_scan_columns.sql — columns New scan and Search need.
--
-- 1. oc_scan_request.created_by / url_count. oc_013 creates the table with
--    CREATE TABLE IF NOT EXISTS, so a database that already had an older,
--    wider oc_scan_request (status, candidates_* counters…) kept it and never
--    got these two columns — and every "Start scan" failed with
--    "Unknown column 'created_by'". Added here, guarded, so both shapes end up
--    with what Oc_scan_model::create() writes. The older columns all have
--    defaults and are left alone.
--
-- 2. oc_discovery_candidate.rating / review_count / maps_url / primary_type.
--    Search shows what Google Places returned for a niche + city as cards; the
--    rating and Maps link are kept on the candidate so a repeat search is read
--    from the database instead of paying for the same lookup again.
--
-- Idempotent: every ALTER checks information_schema first.

SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''oc_scan_request.created_by already present''',
    'ALTER TABLE `oc_scan_request`
       ADD COLUMN `created_by` int(11) DEFAULT NULL
         COMMENT ''-> users.id; the team member who asked (user_id is the agency)''
         AFTER `user_id`')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_scan_request' AND COLUMN_NAME = 'created_by'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''oc_scan_request.url_count already present''',
    'ALTER TABLE `oc_scan_request`
       ADD COLUMN `url_count` smallint(6) NOT NULL DEFAULT 0
         COMMENT ''links pasted into this scan''')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_scan_request' AND COLUMN_NAME = 'url_count'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''oc_discovery_candidate.rating already present''',
    'ALTER TABLE `oc_discovery_candidate`
       ADD COLUMN `rating`       decimal(2,1) DEFAULT NULL COMMENT ''Google rating, 1.0–5.0'' AFTER `lng`,
       ADD COLUMN `review_count` int(11)      DEFAULT NULL COMMENT ''Google review count'' AFTER `rating`,
       ADD COLUMN `maps_url`     varchar(512) DEFAULT NULL COMMENT ''Google Maps page for the place'' AFTER `review_count`,
       ADD COLUMN `primary_type` varchar(64)  DEFAULT NULL COMMENT ''Google primary type, e.g. barber_shop'' AFTER `maps_url`')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_discovery_candidate' AND COLUMN_NAME = 'rating'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
