-- oc_003_scan.sql
-- OpenChair — Radar: the scan queue, the per-pass audit log, and the raw grid.
--
-- `oc_scan_queue` has a UNIQUE on business_id.  That is not decoration: it is the
-- schema-level guarantee that a business can be enqueued only ONCE per cycle no
-- matter how many tenants watch it.  `watcher_count` (how many oc_prospect rows
-- point at the business) raises priority; it never multiplies the work.
--
-- `oc_scan_run` has UNIQUE(business_id, observed_on) — the brief's "one pass per
-- business per day, rate-limited, cached" rule enforced by the database rather
-- than by hope.  Retries inside the same day UPSERT this row (attempts++), they do
-- not create a second pass.  cost_micros makes the $0.02/scan COGS a queryable
-- number instead of an estimate.
--
-- `oc_scan_snapshot` is the ONLY raw storage.  The grid is normalised to a compact
-- JSON document and stored gzipped in `grid_gz`:
--
--   {"tz":"America/New_York","slot_min":30,"day0":"2026-09-05",
--    "staff":["<staff_hash>","<staff_hash>"],
--    "open":["0011111000001100","0000111100000000", …]}   // one bitstring per staff per day
--                                                          // index = slot ordinal, '1' = online-bookable open
--
-- Bitstrings compress ~10:1; a 14-day x 6-staff x 24-slot grid lands at ~2-4 KB.
-- Staff are referenced by hash — a snapshot NEVER contains a person's name.
-- Retention is 14 days (see purge_after); everything the product needs after that
-- lives in the oc_004 rollups, and a pitch that needs its evidence forever freezes
-- its own copy in oc_pitch.evidence_gz (oc_006).
--
-- Idempotent: safe to re-run.

CREATE TABLE IF NOT EXISTS `oc_scan_queue` (
  `id`            int(11)      NOT NULL AUTO_INCREMENT,
  `business_id`   int(11)      NOT NULL,                          -- -> oc_business.id  (UNIQUE: one durable slot per business)
  `platform_id`   int(11)      NOT NULL,                          -- denormalised so the runner can shard/limit per platform
  `priority`      tinyint(4)   NOT NULL DEFAULT 5,                -- lower runs sooner; derived from watcher_count + staleness
  `watcher_count` smallint(6)  NOT NULL DEFAULT 0,                -- how many tenants watch it — raises priority, never duplicates work
  `due_at`        datetime     NOT NULL,
  `status`        enum('due','locked','done','failed','paused') NOT NULL DEFAULT 'due',
  `attempts`      smallint(6)  NOT NULL DEFAULT 0,                -- reset when status returns to 'due'
  `locked_by`     varchar(64)  DEFAULT NULL,                      -- worker id holding the claim
  `locked_at`     datetime     DEFAULT NULL,                      -- stale-lock reaper input
  `last_error`    varchar(255) DEFAULT NULL,
  `created_at`    datetime     NOT NULL DEFAULT current_timestamp(),
  `updated_at`    datetime     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_queue_business` (`business_id`),
  KEY `idx_queue_due`    (`status`,`due_at`,`priority`),          -- the claim query
  KEY `idx_queue_lock`   (`status`,`locked_at`),                  -- the stale-lock reaper
  KEY `idx_queue_plat`   (`platform_id`,`status`),                -- per-platform rate limiting
  CONSTRAINT `fk_queue_business` FOREIGN KEY (`business_id`) REFERENCES `oc_business` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `oc_scan_run` (
  `id`              bigint(20)   NOT NULL AUTO_INCREMENT,
  `business_id`     int(11)      NOT NULL,                        -- -> oc_business.id
  `platform_id`     int(11)      NOT NULL,
  `observed_on`     date         NOT NULL,                        -- UTC date of the pass (the time-series axis)
  `started_at`      datetime     NOT NULL DEFAULT current_timestamp(),
  `finished_at`     datetime     DEFAULT NULL,
  `duration_ms`     mediumint(9) DEFAULT NULL,
  `status`          enum('ok','partial','blocked','captcha','not_found','timeout','error') NOT NULL DEFAULT 'error',
  `http_status`     smallint(6)  DEFAULT NULL,
  `adapter_version` varchar(20)  DEFAULT NULL,                    -- replayability: which adapter produced this grid
  `proxy_pool`      varchar(40)  DEFAULT NULL,                    -- residential pool used (anti-bot forensics)
  `attempts`        smallint(6)  NOT NULL DEFAULT 1,              -- retries inside the day UPSERT this row, not a new one
  `cost_micros`     int(11)      NOT NULL DEFAULT 0,              -- COGS in millionths of a unit ($0.02 = 20000)
  `error_code`      varchar(40)  DEFAULT NULL,
  `error_note`      varchar(255) DEFAULT NULL,
  `created_at`      datetime     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  -- "One pass per business per day" — enforced, not assumed:
  UNIQUE KEY `uq_run_biz_day`  (`business_id`,`observed_on`),
  KEY `idx_run_day_status`     (`observed_on`,`status`),          -- Radar health board + the nightly purge
  KEY `idx_run_biz_time`       (`business_id`,`started_at`),
  KEY `idx_run_plat_day`       (`platform_id`,`observed_on`,`status`), -- per-adapter block rate (build risk #2)
  CONSTRAINT `fk_run_business` FOREIGN KEY (`business_id`) REFERENCES `oc_business` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `oc_scan_snapshot` (
  `id`            bigint(20)   NOT NULL AUTO_INCREMENT,
  `scan_run_id`   bigint(20)   NOT NULL,                          -- -> oc_scan_run.id (1:1)
  `business_id`   int(11)      NOT NULL,                          -- denormalised: every read is business-scoped
  `captured_on`   date         NOT NULL,                          -- = oc_scan_run.observed_on
  `horizon_days`  tinyint(4)   NOT NULL DEFAULT 14,               -- how many days of grid this blob actually holds
  `slot_minutes`  smallint(6)  DEFAULT NULL,
  `staff_count`   smallint(6)  DEFAULT NULL,
  `grid_codec`    enum('gzip_json_v1','zstd_json_v1') NOT NULL DEFAULT 'gzip_json_v1',
  `grid_sha1`     char(40)     DEFAULT NULL,                      -- unchanged-grid detection (a dead shop emits the same blob nightly)
  `grid_bytes`    mediumint(9) DEFAULT NULL,                      -- compressed size — storage telemetry, no need to read the blob
  `grid_gz`       mediumblob   DEFAULT NULL,                      -- the compressed normalised grid. staff_hash only — NEVER names.
  `purge_after`   date         NOT NULL,                          -- captured_on + retention window; the purge job's only input
  `created_at`    datetime     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_snap_run`  (`scan_run_id`),
  KEY `idx_snap_biz_day`    (`business_id`,`captured_on`),        -- "show me their real grid" (pitch pack, demo)
  KEY `idx_snap_purge`      (`purge_after`),                      -- DELETE … WHERE purge_after < CURDATE() LIMIT 5000
  CONSTRAINT `fk_snap_run`      FOREIGN KEY (`scan_run_id`) REFERENCES `oc_scan_run` (`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_snap_business` FOREIGN KEY (`business_id`) REFERENCES `oc_business` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
