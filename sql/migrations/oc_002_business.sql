-- oc_002_business.sql
-- OpenChair — the GLOBAL business layer.  Scanned once, shared by every tenant.
--
-- This is the single most important boundary in the schema.  A physical shop (one
-- Booksy listing) exists here EXACTLY ONCE, no matter how many consultants watch
-- it.  Tenants never own a business — they own an `oc_prospect` row that points at
-- one (oc_005).  Cost, politeness and the "never touch a slot" rule all depend on
-- that: 500 tenants watching the same Tampa barbershop must still be one HTTP pass
-- per night.  Nothing in this file carries a user_id.
--
-- `oc_business` also carries a denormalised `latest_*` projection, rewritten in the
-- same transaction that writes today's `oc_vacancy_daily` row.  That projection is
-- what makes the demo query — "barbershop, Tampa → 47 pins, reddest first" — a
-- single index range scan instead of a 100k-row join against a 40M-row time series.
--
-- `oc_staff` is the ONLY place in OpenChair that may hold a natural person's name.
-- `label` ("Chair 1") is always safe to render; `display_name` is personal data,
-- nullable, and purged by the retention job (see oc_006 / 30-data-model.md §9).
-- Snapshots (oc_003) store `staff_key` only — never names.
--
-- FK policy: hard FKs INSIDE the oc_ island (they give correct cascade deletes for
-- the "remove this business" request); loose INT links OUT to engine tables
-- (`users`, `clients`, `funnels`) per house style — the live DB has 3 FKs total.
--
-- Idempotent: safe to re-run.

CREATE TABLE IF NOT EXISTS `oc_business` (
  `id`                    int(11)      NOT NULL AUTO_INCREMENT,
  `platform_id`           int(11)      NOT NULL,                  -- -> oc_platform.id
  `platform_business_key` varchar(191) NOT NULL,                  -- the platform's own listing id/slug — the global identity
  `canonical_id`          int(11)      DEFAULT NULL,              -- -> oc_business.id when this row was merged into another (same shop, 2 platforms)
  `dedupe_key`            char(40)     DEFAULT NULL,              -- sha1(normalised name|phone|rounded geo) — merge candidate finder
  `name`                  varchar(191) DEFAULT NULL,
  `booking_url`           varchar(512) DEFAULT NULL,              -- the public "Book now" page Radar reads (read-only, always)
  `website`               varchar(512) DEFAULT NULL,
  `phone`                 varchar(32)  DEFAULT NULL,              -- public listing contact; check suppression_list/phone_blocklist before ANY outreach
  `email`                 varchar(191) DEFAULT NULL,
  `address_line`          varchar(255) DEFAULT NULL,
  `city`                  varchar(120) DEFAULT NULL,
  `region_code`           varchar(8)   DEFAULT NULL,              -- state / province
  `country_code`          char(2)      DEFAULT NULL,
  `postal_code`           varchar(16)  DEFAULT NULL,
  `city_key`              varchar(80)  NOT NULL DEFAULT '',       -- normalised market key 'us-fl-tampa'; leading column of every list query
  `niche_key`             varchar(48)  NOT NULL DEFAULT '',       -- -> oc_niche.niche_key (loose: a lookup, not an ownership edge)
  `lat`                   decimal(9,6) DEFAULT NULL,
  `lng`                   decimal(9,6) DEFAULT NULL,
  `timezone`              varchar(64)  NOT NULL DEFAULT 'UTC',    -- IANA. ALL slot / day-of-week / hour-block maths is in THIS zone
  `staff_count`           smallint(6)  DEFAULT NULL,              -- bookable resources ("chairs") seen on the last good scan
  `slot_minutes`          smallint(6)  DEFAULT NULL,
  `avg_price_cents`       int(11)      DEFAULT NULL,              -- menu average; the Empty-Chair Revenue multiplier
  `price_source`          enum('menu','niche_default','manual') NOT NULL DEFAULT 'niche_default',
  `currency`              char(3)      NOT NULL DEFAULT 'USD',
  `rating`                decimal(2,1) DEFAULT NULL,
  `review_count`          int(11)      DEFAULT NULL,
  `status`                enum('active','paused','unreachable','gone','merged','suppressed') NOT NULL DEFAULT 'active',
  `first_seen_at`         datetime     DEFAULT NULL,
  `last_scan_at`          datetime     DEFAULT NULL,
  `last_good_scan_at`     datetime     DEFAULT NULL,
  `consecutive_failures`  smallint(6)  NOT NULL DEFAULT 0,        -- N in a row -> status='unreachable', backs the scan off
  -- Denormalised "latest observation" projection (written with oc_vacancy_daily) ---
  `latest_observed_on`      date       DEFAULT NULL,
  `latest_vacancy_bp`       smallint(5) unsigned DEFAULT NULL,    -- 0..10000 basis points; integer so the 41%→68% proof never float-drifts
  `latest_open_slots`       mediumint(9) DEFAULT NULL,
  `latest_bookable_slots`   mediumint(9) DEFAULT NULL,
  `latest_empty_chair_cents` int(11)   DEFAULT NULL,              -- "$1,860 unsold this week"
  `latest_days_to_next`     smallint(6) DEFAULT NULL,             -- days to next available — the Slammed sort key
  `latest_bucket`           enum('hungry','neutral','slammed') DEFAULT NULL,
  `latest_confidence`       tinyint(3) unsigned DEFAULT NULL,     -- 0..100; drops on a partial/blocked grid
  -- Removal request (see 30-data-model.md §9) -----------------------------------
  `suppressed_at`         datetime     DEFAULT NULL,              -- set on an owner opt-out; children are purged, this row survives as the tombstone
  `suppression_reason`    varchar(191) DEFAULT NULL,
  `created_at`            datetime     NOT NULL DEFAULT current_timestamp(),
  `updated_at`            datetime     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  -- The identity that makes "scan once, share to all" enforceable in the schema:
  UNIQUE KEY `uq_biz_platform` (`platform_id`,`platform_business_key`),
  -- Q1  list a market ordered by vacancy score (the map / the reddest pin):
  KEY `idx_biz_market_vacancy` (`city_key`,`niche_key`,`status`,`latest_vacancy_bp`),
  -- Q1b the Slammed bucket, ordered by how far out next-available is:
  KEY `idx_biz_market_next`    (`city_key`,`niche_key`,`status`,`latest_days_to_next`),
  -- Q1c bucket-filtered market list (FE ships Hungry only; OTO1 unlocks Slammed):
  KEY `idx_biz_market_bucket`  (`city_key`,`niche_key`,`latest_bucket`,`latest_vacancy_bp`),
  KEY `idx_biz_geo`            (`country_code`,`region_code`,`city`),
  KEY `idx_biz_dedupe`         (`dedupe_key`),
  KEY `idx_biz_canonical`      (`canonical_id`),
  KEY `idx_biz_scan_due`       (`status`,`last_scan_at`),         -- the nightly enqueue sweep
  CONSTRAINT `fk_biz_platform`  FOREIGN KEY (`platform_id`)  REFERENCES `oc_platform` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_biz_canonical` FOREIGN KEY (`canonical_id`) REFERENCES `oc_business` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- The published service menu.  Replaced wholesale on each successful scan that
-- returns prices; feeds avg_price_cents and the pitch one-pager's "their own menu
-- prices" line.  service_key = sha1(lower(trim(name))) so the natural key fits an
-- index without a 191-char prefix.
CREATE TABLE IF NOT EXISTS `oc_business_service` (
  `id`           int(11)      NOT NULL AUTO_INCREMENT,
  `business_id`  int(11)      NOT NULL,                           -- -> oc_business.id
  `service_key`  char(40)     NOT NULL,                           -- sha1(lower(trim(name))) — stable natural key
  `name`         varchar(191) NOT NULL,
  `duration_min` smallint(6)  DEFAULT NULL,
  `price_cents`  int(11)      DEFAULT NULL,
  `currency`     char(3)      NOT NULL DEFAULT 'USD',
  `position`     smallint(6)  NOT NULL DEFAULT 0,                 -- menu order as published
  `seen_on`      date         DEFAULT NULL,                       -- last scan date this service was still listed
  `created_at`   datetime     NOT NULL DEFAULT current_timestamp(),
  `updated_at`   datetime     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_svc_biz_key` (`business_id`,`service_key`),
  KEY `idx_svc_seen` (`seen_on`),
  CONSTRAINT `fk_svc_business` FOREIGN KEY (`business_id`) REFERENCES `oc_business` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Bookable resources ("chairs").  The ONLY table that may hold a person's name.
-- Default posture: label only.  display_name is opt-in, short-lived and never
-- leaves the account that scanned it (never in a white-label Ledger, never in a
-- snapshot blob, blurred in the grid screenshot unless the tenant turns it off).
CREATE TABLE IF NOT EXISTS `oc_staff` (
  `id`               int(11)      NOT NULL AUTO_INCREMENT,
  `business_id`      int(11)      NOT NULL,                       -- -> oc_business.id
  `staff_key`        varchar(120) NOT NULL,                       -- the platform's own resource id — pseudonymous, stable
  `staff_hash`       char(40)     DEFAULT NULL,                   -- sha1(business_id|staff_key) — the id used inside snapshot blobs
  `label`            varchar(40)  NOT NULL DEFAULT '',            -- 'Chair 1' / 'Stylist A' — PII-free, always safe to render
  `display_name`     varchar(120) DEFAULT NULL,                   -- PERSONAL DATA. Optional, purged by retention. Never exported.
  `name_captured_at` datetime     DEFAULT NULL,                   -- starts the display_name retention clock
  `role`             varchar(60)  DEFAULT NULL,                   -- as published ("Barber", "Colourist")
  `active`           tinyint(1)   NOT NULL DEFAULT 1,
  `first_seen_at`    datetime     DEFAULT NULL,
  `last_seen_at`     datetime     DEFAULT NULL,
  `created_at`       datetime     NOT NULL DEFAULT current_timestamp(),
  `updated_at`       datetime     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_staff_biz_key` (`business_id`,`staff_key`),
  KEY `idx_staff_name_purge` (`name_captured_at`),                -- drives the nightly display_name purge
  CONSTRAINT `fk_staff_business` FOREIGN KEY (`business_id`) REFERENCES `oc_business` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
