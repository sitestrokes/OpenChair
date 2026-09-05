-- oc_006_alerts_pitch_ledger.sql
-- OpenChair — the Alert, Target and Ledger surfaces (all per-tenant).
--
-- `oc_alert_run` / `oc_alert_item`: the 8am list, persisted.  It could be a live
-- query, but it must not be: the digest has to know what it already said yesterday
-- (`is_new`, oc_prospect.last_alerted_on), and a "you told me 40 open slots" support
-- ticket needs the exact numbers that were sent, not today's recomputation.
--
-- `oc_pitch` freezes its own evidence.  oc_scan_snapshot is purged at 14 days, but a
-- one-pager the consultant walks in with must still render its grid in March.  So a
-- pitch keeps `evidence_gz` — a private copy of the grid blob at generation time —
-- and never depends on the snapshot surviving.  This is why oc_pitch links to the
-- snapshot with a LOOSE int, not an FK.
--
-- `oc_ledger_report` likewise freezes `series` (the 8 weekly points) rather than
-- re-querying oc_vacancy_weekly: a white-label PDF the client already received must
-- never silently change.  `share_token` backs the public read-only link.
--
-- Compliance boundary (deliberate, do not soften): nothing here enrols anyone into
-- `sequence_queue`, the voice lanes or SMS.  A scraped business has given no
-- consent.  OpenChair produces ARTEFACTS; if a consultant later dials, that goes
-- through the engine's existing suppression_list / phone_blocklist / dnc_check_cache
-- path like any other outbound.
--
-- Idempotent: safe to re-run.

CREATE TABLE IF NOT EXISTS `oc_alert_run` (
  `id`            bigint(20)   NOT NULL AUTO_INCREMENT,
  `user_id`       int(11)      NOT NULL,                          -- -> users.id
  `market_id`     int(11)      NOT NULL DEFAULT 0,                -- -> oc_market.id; 0 = "all my markets" digest (sentinel, keeps the UNIQUE honest)
  `run_date`      date         NOT NULL,                          -- the consultant-local date this digest is for
  `channel`       enum('email','sms','telegram','in_app') NOT NULL DEFAULT 'email',
  `hungry_count`  smallint(6)  NOT NULL DEFAULT 0,
  `slammed_count` smallint(6)  NOT NULL DEFAULT 0,
  `total_count`   smallint(6)  NOT NULL DEFAULT 0,
  `status`        enum('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
  `sent_at`       datetime     DEFAULT NULL,
  `error`         varchar(255) DEFAULT NULL,
  `created_at`    datetime     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_alert_run` (`user_id`,`market_id`,`run_date`,`channel`), -- exactly one digest per tenant/market/day/channel
  KEY `idx_alert_user_date` (`user_id`,`run_date`),
  KEY `idx_alert_pending`   (`status`,`run_date`)                 -- the sender's claim query
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `oc_alert_item` (
  `id`                bigint(20)   NOT NULL AUTO_INCREMENT,
  `alert_run_id`      bigint(20)   NOT NULL,                      -- -> oc_alert_run.id
  `business_id`       int(11)      NOT NULL,                      -- -> oc_business.id
  `prospect_id`       int(11)      DEFAULT NULL,                  -- -> oc_prospect.id (loose: item survives a prospect delete for audit)
  `bucket`            enum('hungry','slammed') NOT NULL,
  `rank_pos`          smallint(6)  NOT NULL DEFAULT 0,            -- position in the digest as sent
  `vacancy_bp`        smallint(5) unsigned DEFAULT NULL,          -- the numbers AS SENT (never recomputed)
  `open_slots`        mediumint(9) DEFAULT NULL,
  `empty_chair_cents` int(11)      DEFAULT NULL,
  `days_to_next`      smallint(6)  DEFAULT NULL,
  `is_new`            tinyint(1)   NOT NULL DEFAULT 0,            -- first appearance for this tenant — the novelty flag
  `clicked_at`        datetime     DEFAULT NULL,
  `created_at`        datetime     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_alert_item_run` (`alert_run_id`,`rank_pos`),
  KEY `idx_alert_item_biz` (`business_id`),
  CONSTRAINT `fk_alert_item_run` FOREIGN KEY (`alert_run_id`) REFERENCES `oc_alert_run` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `oc_pitch` (
  `id`                    bigint(20)   NOT NULL AUTO_INCREMENT,
  `user_id`               int(11)      NOT NULL,                  -- -> users.id
  `business_id`           int(11)      NOT NULL,                  -- -> oc_business.id
  `prospect_id`           int(11)      DEFAULT NULL,              -- -> oc_prospect.id
  `kind`                  enum('hungry','slammed') NOT NULL,      -- which of the two pitches (same scan, opposite offer)
  `format`                enum('onepager','email','sms','lossreel') NOT NULL DEFAULT 'onepager',
  `headline`              varchar(255) DEFAULT NULL,
  `body`                  mediumtext   DEFAULT NULL,
  `artifact`              longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`artifact`)),
                                                                  -- structured pitch pack: dead-zone callouts, matched fix, offer copy
  `asset_path`            varchar(512) DEFAULT NULL,              -- rendered PNG/PDF on disk/CDN
  `evidence_snapshot_id`  bigint(20)   DEFAULT NULL,              -- -> oc_scan_snapshot.id (LOOSE — snapshots are purged at 14d)
  `evidence_codec`        enum('gzip_json_v1','zstd_json_v1') DEFAULT NULL,
  `evidence_gz`           mediumblob   DEFAULT NULL,              -- FROZEN copy of the grid so the one-pager stays reproducible forever
  `evidence_captured_on`  date         DEFAULT NULL,
  `vacancy_bp`            smallint(5) unsigned DEFAULT NULL,      -- the figures printed on the artefact
  `open_slots`            mediumint(9) DEFAULT NULL,
  `empty_chair_cents`     int(11)      DEFAULT NULL,
  `days_to_next`          smallint(6)  DEFAULT NULL,
  `sent_at`               datetime     DEFAULT NULL,
  `sent_channel`          varchar(24)  DEFAULT NULL,              -- how the consultant delivered it (self-reported)
  `created_at`            datetime     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pitch_user`     (`user_id`,`created_at`),
  KEY `idx_pitch_business` (`business_id`),
  KEY `idx_pitch_prospect` (`prospect_id`),
  CONSTRAINT `fk_pitch_business` FOREIGN KEY (`business_id`) REFERENCES `oc_business` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `oc_ledger_report` (
  `id`                 bigint(20)   NOT NULL AUTO_INCREMENT,
  `user_id`            int(11)      NOT NULL,                     -- -> users.id
  `business_id`        int(11)      NOT NULL,                     -- -> oc_business.id
  `client_id`          int(11)      DEFAULT NULL,                 -- -> clients.id (the paying client this report is for)
  `prospect_id`        int(11)      DEFAULT NULL,                 -- -> oc_prospect.id
  `period_start`       date         NOT NULL,
  `period_end`         date         NOT NULL,
  `weeks`              tinyint(4)   NOT NULL DEFAULT 8,           -- the 8-week window (the retention product)
  `start_fill_rate_bp` smallint(5) unsigned DEFAULT NULL,         -- "41%"
  `end_fill_rate_bp`   smallint(5) unsigned DEFAULT NULL,         -- "68%"
  `delta_bp`           smallint(6)  DEFAULT NULL,                 -- SIGNED: end - start, can go negative
  `series`             longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`series`)),
                                                                  -- FROZEN weekly points — a delivered PDF must never silently change
  `branding`           longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`branding`)),
                                                                  -- OTO2 white-label: logo, colours, agency name. NEVER contains staff names.
  `share_token`        varchar(64)  DEFAULT NULL,                 -- public read-only link; NULL = not shared
  `asset_path`         varchar(512) DEFAULT NULL,
  `status`             enum('draft','published','revoked') NOT NULL DEFAULT 'draft',
  `generated_at`       datetime     DEFAULT NULL,
  `created_at`         datetime     NOT NULL DEFAULT current_timestamp(),
  `updated_at`         datetime     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ledger_share` (`share_token`),
  KEY `idx_ledger_user`     (`user_id`,`created_at`),
  KEY `idx_ledger_business` (`business_id`,`period_end`),
  KEY `idx_ledger_client`   (`client_id`),
  CONSTRAINT `fk_ledger_business` FOREIGN KEY (`business_id`) REFERENCES `oc_business` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;
