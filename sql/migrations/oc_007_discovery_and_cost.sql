-- oc_007_discovery_and_cost.sql
-- OpenChair — the three gaps 05-prd.md Q8 found AFTER 30-data-model.md was written.
-- These have to land before anything is built on oc_001..oc_006, because the first
-- one has no workaround: the discovery flow in 45-flows.md §2 parks a candidate as
-- `not_bookable`, and there is nowhere to park it.
--
--   (a) oc_discovery_candidate — a business we found but have NOT confirmed has
--       public online booking.  It cannot live in oc_business: that table's
--       platform_id is NOT NULL with an FK to oc_platform, and the whole point of
--       a candidate is that we do not know its platform yet (and may never).
--       This is also where FR-2.3's honest coverage ratio comes from — "38 of 91
--       have online booking" is COUNT(verified) over COUNT(*) on this table, not
--       a number we can compute anywhere else.
--
--   (b) oc_platform.rollout — an adapter must be able to run in `shadow` (results
--       written, never published) before it is trusted.  20-architecture.md §3.9
--       makes this the mechanism by which a new adapter ships monthly WITHOUT
--       redeploy risk, and §9.2 makes it where the canary parks a platform whose
--       output went wrong-but-plausible.  Without it, "freeze publishing for that
--       platform" (FR-9.2) has no state to write.
--
--   (c) oc_scan_run.tier + .bytes — cost attribution.  oc_003 gave us cost_micros,
--       which says WHAT a scan cost but not WHY.  The anti-bot tier is the cost
--       driver (20-architecture §7.2: T1 ~$0.0001, T2 ~$0.0007, T3 ~$0.014), so
--       without the tier on the row the $0.02 budget cannot be attributed, and the
--       cost circuit in PRD Q5 has nothing to reason about.  Tier 3 is present in
--       the enum ONLY so the guard can RECOGNISE and REFUSE it — see the CHECK
--       constraint below.  It is a hard ceiling, not a config flag.
--
-- Idempotent: safe to re-run.  MariaDB 10.4, strict mode.

-- ─────────────────────────────────────────────────────────────────────────────
-- (a) Discovery candidates — the waiting room in front of oc_business.
-- ─────────────────────────────────────────────────────────────────────────────
-- Lifecycle:  new → probing → verified   (a booking signature was found; an
--                                         oc_business row is created and linked)
--                        └─→ not_bookable (no public booking; parked, and NOT
--                                         re-probed until recheck_after)
--                        └─→ rejected     (permanently out: do-not-scan list,
--                                         owner removal request, refused platform)
--
-- The 90-day park in 45-flows.md §2 is `recheck_after`, not a delete: a shop that
-- adopts Booksy next quarter should be found then, and re-probing it nightly in
-- the meantime is pure COGS for a known answer.
CREATE TABLE IF NOT EXISTS `oc_discovery_candidate` (
  `id`               int(11)      NOT NULL AUTO_INCREMENT,
  `source`           enum('places','manual','import','referral') NOT NULL DEFAULT 'places',
  `source_ref`       varchar(191) DEFAULT NULL,                  -- the provider's own place id — dedupes re-runs of the same market
  `city_key`         varchar(80)  NOT NULL DEFAULT '',           -- 'us-fl-tampa'; matches oc_business.city_key
  `niche_key`        varchar(48)  NOT NULL DEFAULT '',
  `name`             varchar(191) DEFAULT NULL,
  `website`          varchar(512) DEFAULT NULL,
  `phone`            varchar(32)  DEFAULT NULL,
  `address_line`     varchar(255) DEFAULT NULL,
  `lat`              decimal(9,6) DEFAULT NULL,
  `lng`              decimal(9,6) DEFAULT NULL,
  `status`           enum('new','probing','verified','not_bookable','rejected') NOT NULL DEFAULT 'new',
  `detected_platform_key` varchar(32) DEFAULT NULL,              -- loose string, NOT an FK: detection can name a platform we do not adapt
  `detect_confidence` tinyint(3) unsigned DEFAULT NULL,          -- 0..100 (20-architecture §4.4)
  `detect_evidence`  varchar(512) DEFAULT NULL,                  -- which signature matched, for debugging a false negative
  `business_id`      int(11)      DEFAULT NULL,                  -- -> oc_business.id once verified
  `probe_count`      smallint(6)  NOT NULL DEFAULT 0,
  `last_probed_at`   datetime     DEFAULT NULL,
  `recheck_after`    date         DEFAULT NULL,                  -- the 90-day park; NULL = never re-probe (rejected)
  `last_error`       varchar(255) DEFAULT NULL,
  `created_at`       datetime     NOT NULL DEFAULT current_timestamp(),
  `updated_at`       datetime     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  -- One row per source listing. Re-running discovery on a market UPSERTs; it does
  -- not accumulate duplicates of the same shop.
  UNIQUE KEY `uq_cand_source` (`source`,`source_ref`),
  -- FR-2.3's coverage ratio: COUNT(status='verified') / COUNT(*) for a market.
  KEY `idx_cand_market`  (`city_key`,`niche_key`,`status`),
  -- The re-probe sweep: everything parked whose horizon has come around.
  KEY `idx_cand_recheck` (`status`,`recheck_after`),
  KEY `idx_cand_biz`     (`business_id`),
  CONSTRAINT `fk_cand_business` FOREIGN KEY (`business_id`) REFERENCES `oc_business` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- (b) Adapter rollout state.
-- ─────────────────────────────────────────────────────────────────────────────
--   research  — being written; never scheduled
--   shadow    — scans run and are stored, but NOTHING is published to a tenant.
--               This is how an adapter earns trust against fixtures + live data
--               before a buyer ever sees its numbers.
--   live      — published normally
--   frozen    — the canary tripped (FR-9.2): keep scanning for diagnosis, publish
--               nothing, and serve last-good labelled stale (FR-3.5)
--
-- Adding a column is not idempotent in MariaDB 10.4 (no IF NOT EXISTS on ADD
-- COLUMN in a way that is safe across versions), so this is guarded by a lookup
-- in information_schema and run through a prepared statement.
SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''oc_platform.rollout already present''',
    'ALTER TABLE `oc_platform`
       ADD COLUMN `rollout` enum(''research'',''shadow'',''live'',''frozen'')
         NOT NULL DEFAULT ''research'' AFTER `enabled`,
       ADD COLUMN `rollout_changed_at` datetime DEFAULT NULL AFTER `rollout`,
       ADD COLUMN `rollout_note` varchar(255) DEFAULT NULL AFTER `rollout_changed_at`')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_platform' AND COLUMN_NAME = 'rollout'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- The scheduler asks "what may I schedule, and of that, what may I publish?"
SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''idx_platform_rollout already present''',
    'ALTER TABLE `oc_platform` ADD KEY `idx_platform_rollout` (`rollout`,`enabled`)')
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_platform' AND INDEX_NAME = 'idx_platform_rollout'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Everything seeded by oc_001 predates this column and was written on the
-- assumption it would be scanned. Nothing has run yet, so the honest state for an
-- unproven adapter is 'research' (the column default) — but make it explicit so a
-- re-run of oc_001 cannot quietly leave a new platform in a different state.
UPDATE `oc_platform` SET `rollout` = 'research'
 WHERE `rollout` = 'live' AND `adapter_version` IS NULL;

-- ─────────────────────────────────────────────────────────────────────────────
-- (c) Cost attribution on the scan run.
-- ─────────────────────────────────────────────────────────────────────────────
-- tier 1 = plain GET, no browser            ~$0.0001/scan
-- tier 2 = headless browser + datacenter/ISP proxy  ~$0.0007/scan
-- tier 3 = stealth + residential rotation   ~$0.014/scan — OUT OF SCOPE.
--
-- Tier 3 exists in this enum so the guard can name what it is refusing and so an
-- attempt is visible in the data rather than silent. The CHECK constraint below
-- makes a tier-3 row unwritable: the ceiling is enforced by the database, not by
-- a config flag someone can flip at 2am. See 05-prd.md §4.2 and 50-risk A1.3.
SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''oc_scan_run.tier already present''',
    'ALTER TABLE `oc_scan_run`
       ADD COLUMN `tier`  tinyint(3) unsigned NOT NULL DEFAULT 1 AFTER `cost_micros`,
       ADD COLUMN `bytes` int(10) unsigned DEFAULT NULL AFTER `tier`')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_scan_run' AND COLUMN_NAME = 'tier'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''chk_scan_tier_ceiling already present''',
    'ALTER TABLE `oc_scan_run` ADD CONSTRAINT `chk_scan_tier_ceiling` CHECK (`tier` IN (1,2))')
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_scan_run'
    AND CONSTRAINT_NAME = 'chk_scan_tier_ceiling'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Cost-per-tier rollup for the nightly circuit (PRD Q5) and the ops console.
SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''idx_scan_cost already present''',
    'ALTER TABLE `oc_scan_run` ADD KEY `idx_scan_cost` (`observed_on`,`tier`)')
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_scan_run' AND INDEX_NAME = 'idx_scan_cost'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Note on the fourth Q8 item: the per-fetch audit (FR-3.4) is deliberately NOT a
-- table. At one row per fetch it is ~800k rows/night, which buys us nothing a
-- rotated JSONL file on the egress box does not. See openchair-egress/src/audit/.
