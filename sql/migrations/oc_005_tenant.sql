-- oc_005_tenant.sql
-- OpenChair — the PER-TENANT layer.  Everything above this line was global.
--
-- Two tables, and the split matters:
--
--   oc_market    a saved search ("barbershop, Tampa").  It is a RULE, not a list.
--                It is also the reverse index Radar reads to decide which markets
--                are worth crawling at all — a business nobody watches is not
--                scanned, and a business fifty tenants watch is still scanned once.
--                FE = 1 city x 3 niches; OTO1 lifts the cap and unlocks 'slammed'
--                in `buckets`.  Entitlement is enforced in code against the access
--                code, not by schema.
--
--   oc_prospect  the tenant's own row ABOUT a global business: pipeline stage,
--                notes, mute, and the handoff pointers to the engine (`client_id`
--                -> clients.id once they sign, `funnel_id` -> funnels.id if a
--                Bridge gets built for them).  UNIQUE(user_id, business_id) — a
--                tenant sees a business once; a business is seen by many tenants.
--
-- Tenancy convention: `user_id` = Ion Auth `users.id`, exactly as `offers`,
-- `traffic_assets` and `funnels` do.  `client_id` = `clients.id` for agency
-- sub-accounts / white-label scoping.  Both are LOOSE INT links (house style: the
-- engine has 3 FKs total).  No `site_uid` anywhere in OpenChair — deliberate: it
-- keeps us clear of the funnels(utf8mb4_unicode_ci) / subscribers(general_ci)
-- collation trap, because there is no site_uid column to be tempted to JOIN.
--
-- Idempotent: safe to re-run.

CREATE TABLE IF NOT EXISTS `oc_market` (
  `id`                int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`           int(11)      NOT NULL,                      -- -> users.id (the consultant; the tenant key)
  `client_id`         int(11)      DEFAULT NULL,                  -- -> clients.id (agency sub-account scope; NULL = the owner's own)
  `label`             varchar(120) DEFAULT NULL,                  -- display name; defaults to "Barbershops · Tampa, FL"
  `city_key`          varchar(80)  NOT NULL,                      -- 'us-fl-tampa' — must match oc_business.city_key exactly
  `city`              varchar(120) DEFAULT NULL,
  `region_code`       varchar(8)   DEFAULT NULL,
  `country_code`      char(2)      DEFAULT NULL,
  `niche_key`         varchar(48)  NOT NULL,                      -- -> oc_niche.niche_key
  `platform_id`       int(11)      NOT NULL DEFAULT 0,            -- 0 = ALL platforms (sentinel, so the UNIQUE below actually enforces)
  `radius_km`         smallint(6)  DEFAULT NULL,                  -- optional geo widening beyond the city key
  `auto_add`          tinyint(1)   NOT NULL DEFAULT 1,            -- auto-create oc_prospect rows for newly-discovered businesses
  `alert_enabled`     tinyint(1)   NOT NULL DEFAULT 1,
  `alert_hour`        tinyint(4)   NOT NULL DEFAULT 8,            -- the "8am list" — local to alert_timezone
  `alert_timezone`    varchar(64)  NOT NULL DEFAULT 'UTC',        -- the CONSULTANT's zone (not the business's)
  `buckets`           set('hungry','slammed') NOT NULL DEFAULT 'hungry', -- FE ships Hungry only; OTO1 adds 'slammed'
  `min_vacancy_bp`    smallint(5) unsigned NOT NULL DEFAULT 6000, -- per-market override of oc_niche.hungry_threshold_bp
  `max_results`       smallint(6)  NOT NULL DEFAULT 25,           -- cap on the daily digest length
  `status`            enum('active','paused') NOT NULL DEFAULT 'active',
  `business_count`    int(11)      NOT NULL DEFAULT 0,            -- cached size of the market (UI only; refreshed nightly)
  `last_refreshed_at` datetime     DEFAULT NULL,
  `created_at`        datetime     NOT NULL DEFAULT current_timestamp(),
  `updated_at`        datetime     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_market_scope` (`user_id`,`city_key`,`niche_key`,`platform_id`),
  KEY `idx_market_user`  (`user_id`,`status`),                    -- the tenant's market list
  KEY `idx_market_demand`(`city_key`,`niche_key`,`status`),       -- REVERSE: which markets anyone still wants crawled
  KEY `idx_market_client`(`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `oc_prospect` (
  `id`              int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`         int(11)      NOT NULL,                        -- -> users.id (owner)
  `business_id`     int(11)      NOT NULL,                        -- -> oc_business.id (the SHARED, globally-scanned row)
  `market_id`       int(11)      DEFAULT NULL,                    -- -> oc_market.id that surfaced it; NULL = added by hand
  `client_id`       int(11)      DEFAULT NULL,                    -- -> clients.id once they became a managed client (the Ledger handoff)
  `funnel_id`       int(11)      DEFAULT NULL,                    -- -> funnels.id if a Bridge was built for them
  `stage`           enum('new','watching','contacted','meeting','pitched','won','lost','archived') NOT NULL DEFAULT 'new',
  `bucket_at_add`   enum('hungry','neutral','slammed') DEFAULT NULL, -- which pitch they were sourced for
  `pinned`          tinyint(1)   NOT NULL DEFAULT 0,
  `alert_muted`     tinyint(1)   NOT NULL DEFAULT 0,              -- keep scanning, stop paging me
  `tags`            varchar(255) DEFAULT NULL,                    -- comma-separated freeform (UI filter only)
  `notes`           text         DEFAULT NULL,
  `first_seen_at`   datetime     DEFAULT NULL,
  `last_alerted_on` date         DEFAULT NULL,                    -- digest de-duplication: don't re-page the same shop daily
  `last_pitched_at` datetime     DEFAULT NULL,
  `created_at`      datetime     NOT NULL DEFAULT current_timestamp(),
  `updated_at`      datetime     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  -- One row per (tenant, business).  This IS the many-to-many between the tenant
  -- layer and the global layer, and the reason a shared scan is safe.
  UNIQUE KEY `uq_prospect_user_biz` (`user_id`,`business_id`),
  -- Q4  the 8am claim: user_id + alert_muted are EQUALITIES, last_alerted_on the
  --     range — that column order is load-bearing, do not "tidy" it.
  KEY `idx_prospect_user_alert` (`user_id`,`alert_muted`,`last_alerted_on`),
  -- the pipeline board (group by stage, pinned first)
  KEY `idx_prospect_user_stage` (`user_id`,`stage`,`pinned`),
  -- REVERSE: who watches this business → drives oc_scan_queue.watcher_count/priority
  KEY `idx_prospect_business`   (`business_id`),
  KEY `idx_prospect_market`     (`market_id`),
  KEY `idx_prospect_client`     (`client_id`),
  CONSTRAINT `fk_prospect_business` FOREIGN KEY (`business_id`) REFERENCES `oc_business` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prospect_market`   FOREIGN KEY (`market_id`)   REFERENCES `oc_market` (`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
