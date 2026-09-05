-- oc_004_rollups.sql
-- OpenChair — the Meter and the Ledger: what survives after the raw grid is gone.
--
-- Three tiers, deliberately different shapes:
--
--   oc_vacancy_daily   one row per business per scan-day.  The whole 7-day horizon
--                      collapsed to ~12 integers.  Feeds the daily list, the 8am
--                      alert and the weekly rollup.  ~400-day retention.
--   oc_vacancy_weekly  one row per business per ISO week.  The 8-week Ledger — the
--                      retention product ("41% → 68%").  Kept forever; it is 1/7th
--                      the volume of daily and it is the artefact clients pay for.
--   oc_deadzone_cell   FIXED cardinality: 7 day-of-week x <=12 hour-blocks = <=84
--                      rows per business, FOR EVER.  This is the key decision in the
--                      whole schema: the Dead-Zone Map is a materialised accumulator
--                      updated in place on every scan, not a time series.  Storing
--                      the day x block grid per observation would be ~70 rows per
--                      business per night (2.5 BILLION rows/yr at 100k businesses);
--                      as an accumulator it is 5.6M rows that never grow.
--
-- Everything is stored as INTEGERS: percentages as basis points (0..10000) and
-- money as cents.  The Ledger's headline is a before/after comparison — it must be
-- byte-reproducible, and DECIMAL/FLOAT averages of averages are not.
--
-- All day-of-week / hour maths is in the business's LOCAL timezone
-- (`oc_business.timezone`); `observed_on` is the UTC date of the scan pass.
--
-- Idempotent: safe to re-run.

CREATE TABLE IF NOT EXISTS `oc_vacancy_daily` (
  `id`                bigint(20)   NOT NULL AUTO_INCREMENT,
  `business_id`       int(11)      NOT NULL,                      -- -> oc_business.id
  `observed_on`       date         NOT NULL,                      -- UTC date of the scan pass
  `scan_run_id`       bigint(20)   DEFAULT NULL,                  -- -> oc_scan_run.id (loose: runs are purged before dailies)
  `horizon_days`      tinyint(4)   NOT NULL DEFAULT 7,            -- the scoring horizon (brief: "next 7 days")
  `open_slots`        mediumint(9) NOT NULL DEFAULT 0,            -- online-bookable OPEN slots (never call it "empty shop" — risk #1)
  `bookable_slots`    mediumint(9) NOT NULL DEFAULT 0,            -- total online-bookable slots in the horizon
  `vacancy_bp`        smallint(5) unsigned NOT NULL DEFAULT 0,    -- open/bookable x 10000. The Vacancy Score.
  `staff_count`       smallint(6)  DEFAULT NULL,
  `avg_price_cents`   int(11)      DEFAULT NULL,                  -- price used for this row's revenue maths (menu or niche default)
  `empty_chair_cents` int(11)      NOT NULL DEFAULT 0,            -- open_slots x avg_price_cents = "$2,340 of unsold time this week"
  `next_available_at` datetime     DEFAULT NULL,                  -- business-local; NULL = nothing bookable in the horizon
  `days_to_next`      smallint(6)  DEFAULT NULL,                  -- NULL when next_available_at is beyond the horizon
  `bucket`            enum('hungry','neutral','slammed') NOT NULL DEFAULT 'neutral',
  `confidence`        tinyint(3) unsigned NOT NULL DEFAULT 100,   -- 0..100; drops on partial/blocked grids so a bad night can be excluded
  `created_at`        datetime     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  -- Q2  one business's trend: WHERE business_id=? AND observed_on BETWEEN ? AND ?
  UNIQUE KEY `uq_vd_biz_day` (`business_id`,`observed_on`),
  -- Purge + "today across everything" sweeps; also the weekly rollup's driving scan
  KEY `idx_vd_day_bucket`    (`observed_on`,`bucket`,`vacancy_bp`),
  CONSTRAINT `fk_vd_business` FOREIGN KEY (`business_id`) REFERENCES `oc_business` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `oc_vacancy_weekly` (
  `id`                    bigint(20)   NOT NULL AUTO_INCREMENT,
  `business_id`           int(11)      NOT NULL,                  -- -> oc_business.id
  `week_start`            date         NOT NULL,                  -- Monday (business-local week); the Ledger's x-axis
  `scan_days`             tinyint(4)   NOT NULL DEFAULT 0,        -- how many daily rows fed this week (<7 ⇒ show a gap, don't fake it)
  `avg_vacancy_bp`        smallint(5) unsigned NOT NULL DEFAULT 0,
  `min_vacancy_bp`        smallint(5) unsigned DEFAULT NULL,
  `max_vacancy_bp`        smallint(5) unsigned DEFAULT NULL,
  `fill_rate_bp`          smallint(5) unsigned NOT NULL DEFAULT 0, -- 10000 - avg_vacancy_bp; the number on the retainer report
  `avg_open_slots`        mediumint(9) NOT NULL DEFAULT 0,
  `avg_bookable_slots`    mediumint(9) NOT NULL DEFAULT 0,
  `avg_empty_chair_cents` int(11)      NOT NULL DEFAULT 0,
  `avg_days_to_next`      smallint(6)  DEFAULT NULL,
  `dominant_bucket`       enum('hungry','neutral','slammed') NOT NULL DEFAULT 'neutral',
  `created_at`            datetime     NOT NULL DEFAULT current_timestamp(),
  `updated_at`            datetime     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  -- Q2  the 8-week Ledger: WHERE business_id=? AND week_start >= ? ORDER BY week_start
  UNIQUE KEY `uq_vw_biz_week` (`business_id`,`week_start`),
  KEY `idx_vw_week` (`week_start`),                               -- cohort/benchmark reporting
  CONSTRAINT `fk_vw_business` FOREIGN KEY (`business_id`) REFERENCES `oc_business` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- The Dead-Zone Map.  A materialised day-of-week x hour-block accumulator, upserted
-- 56-84 rows at a time after each scan.  No AUTO_INCREMENT id on purpose: the
-- composite PK makes all of one business's cells a single clustered range read
-- (Q3), which is the entire point of the table.
--
-- block_start_hour is the LOCAL hour a block starts.  Default blocking is 2 hours
-- over 06:00-22:00 → 8 blocks x 7 days = 56 cells; widen to hourly (24 blocks) per
-- vertical if a niche ever needs it, and the table shape does not change.
--
-- city_key / niche_key are denormalised from oc_business so "every Tampa barbershop
-- whose Tuesday 13:00-15:00 is chronically dead" is one index range scan instead of
-- a 5.6M-row join.  A market change on oc_business must re-stamp these (rare; the
-- nightly maintenance job does it).
CREATE TABLE IF NOT EXISTS `oc_deadzone_cell` (
  `business_id`      int(11)      NOT NULL,                       -- -> oc_business.id
  `dow`              tinyint(4)   NOT NULL,                       -- 0=Monday .. 6=Sunday, business-LOCAL
  `block_start_hour` tinyint(4)   NOT NULL,                       -- local hour the block starts (06,08,10,… for 2h blocks)
  `block_hours`      tinyint(4)   NOT NULL DEFAULT 2,             -- block width, so a re-blocked niche is still self-describing
  `open_slots_sum`   int(11)      NOT NULL DEFAULT 0,             -- running sum over the window
  `total_slots_sum`  int(11)      NOT NULL DEFAULT 0,
  `obs_count`        smallint(6)  NOT NULL DEFAULT 0,             -- how many observations fed this cell (< 3 ⇒ don't colour it yet)
  `open_ratio_bp`    smallint(5) unsigned NOT NULL DEFAULT 0,     -- open_slots_sum/total_slots_sum x 10000 (the flat average)
  `ewma_bp`          smallint(5) unsigned NOT NULL DEFAULT 0,     -- recency-weighted (alpha .25) — what the UI colours and the offer targets
  `city_key`         varchar(80)  NOT NULL DEFAULT '',            -- denormalised from oc_business (see note above)
  `niche_key`        varchar(48)  NOT NULL DEFAULT '',
  `window_start`     date         DEFAULT NULL,                   -- oldest observation still represented
  `window_end`       date         DEFAULT NULL,                   -- newest
  `updated_at`       datetime     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`business_id`,`dow`,`block_start_hour`),
  -- Q3b cross-market dead-zone hunt ("where does the Tuesday offer go?")
  KEY `idx_dz_market` (`city_key`,`niche_key`,`dow`,`block_start_hour`,`ewma_bp`),
  CONSTRAINT `fk_dz_business` FOREIGN KEY (`business_id`) REFERENCES `oc_business` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
