-- oc_001_reference.sql
-- OpenChair — Live Capacity Prospecting.  Reference / lookup layer.
--
-- `oc_platform`: one row per booking platform Radar can read (one adapter each).
-- Kept as a TABLE rather than an ENUM precisely because the launch plan ships 6
-- adapters and adds more monthly as OTO value — a new adapter must be a seed row,
-- not a schema migration.
--
-- `oc_niche`: the vocabulary shared by a business (`oc_business.niche_key`) and a
-- tenant's saved market (`oc_market.niche_key`), plus the per-vertical tuning the
-- Meter needs: a fallback ticket price for Empty-Chair Revenue when the menu scrape
-- fails, and the Hungry / Slammed thresholds (the brief's 60%-open / 2-weeks-out
-- rules are sane defaults, but a med spa and a barbershop are not the same shape).
--
-- OpenChair alters NO engine table.  Every object here is new and prefixed `oc_`.
-- Tenancy is `user_id` (Ion Auth users.id) exactly like `offers` / `traffic_assets`;
-- OpenChair deliberately carries NO `site_uid` column, which sidesteps the
-- funnels/subscribers collation trap entirely.
--
-- Idempotent: safe to re-run.

CREATE TABLE IF NOT EXISTS `oc_platform` (
  `id`                      int(11)      NOT NULL AUTO_INCREMENT,
  `platform_key`            varchar(32)  NOT NULL,                -- booksy|vagaro|fresha|… (stable; matches the adapter filename)
  `name`                    varchar(80)  NOT NULL,
  `vertical`                enum('beauty','fitness','clinic','home_services','restaurant','other') NOT NULL DEFAULT 'other',
  `adapter_version`         varchar(20)  DEFAULT NULL,            -- adapter semver; stamped on every oc_scan_run for replayability
  `grid_horizon_days`       tinyint(4)   NOT NULL DEFAULT 14,     -- how far ahead this platform exposes availability
  `slot_minutes`            smallint(6)  NOT NULL DEFAULT 30,     -- default slot granularity when the grid does not state one
  `supports_staff`          tinyint(1)   NOT NULL DEFAULT 1,      -- grid is per staff member / chair
  `supports_capacity`       tinyint(1)   NOT NULL DEFAULT 0,      -- class capacity instead ("4 of 12 spots left")
  `supports_next_available` tinyint(1)   NOT NULL DEFAULT 1,      -- exposes a next-available date (drives the Slammed bucket)
  `supports_prices`         tinyint(1)   NOT NULL DEFAULT 1,      -- publishes a service menu with prices
  `rate_limit_rpm`          smallint(6)  NOT NULL DEFAULT 20,     -- Radar politeness ceiling, requests/minute per platform
  `min_scan_interval_hours` smallint(6)  NOT NULL DEFAULT 24,     -- the "one pass per business per day" floor (read-only rule)
  `requires_headless`       tinyint(1)   NOT NULL DEFAULT 1,      -- needs a real browser (anti-bot); drives COGS
  `enabled`                 tinyint(1)   NOT NULL DEFAULT 1,
  `tier`                    enum('fe','oto1','backend') NOT NULL DEFAULT 'fe', -- which SKU unlocks this adapter
  `notes`                   varchar(255) DEFAULT NULL,
  `created_at`              datetime     NOT NULL DEFAULT current_timestamp(),
  `updated_at`              datetime     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_platform_key` (`platform_key`),
  KEY `idx_platform_enabled` (`enabled`,`tier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `oc_niche` (
  `id`                   int(11)      NOT NULL AUTO_INCREMENT,
  `niche_key`            varchar(48)  NOT NULL,                   -- barbershop|hair_salon|… (denormalised onto oc_business + oc_market)
  `label`                varchar(80)  NOT NULL,
  `vertical`             enum('beauty','fitness','clinic','home_services','restaurant','other') NOT NULL DEFAULT 'other',
  `default_ticket_cents` int(11)      NOT NULL DEFAULT 5000,      -- Empty-Chair Revenue fallback when the menu scrape fails
  `default_slot_minutes` smallint(6)  NOT NULL DEFAULT 30,
  `hungry_threshold_bp`  smallint(5) unsigned NOT NULL DEFAULT 6000, -- >= 60.00% open ⇒ Hungry (basis points; 10000 = 100%)
  `slammed_vacancy_bp`   smallint(5) unsigned NOT NULL DEFAULT 500,  -- <= 5.00% open ⇒ Slammed candidate …
  `slammed_next_days`    smallint(6)  NOT NULL DEFAULT 14,        -- … and next-available >= this many days out ⇒ Slammed
  `enabled`              tinyint(1)   NOT NULL DEFAULT 1,
  `created_at`           datetime     NOT NULL DEFAULT current_timestamp(),
  `updated_at`           datetime     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_niche_key` (`niche_key`),
  KEY `idx_niche_vertical` (`vertical`,`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed: the 6 launch adapters (tier fe) + the OTO / backend roadmap -------------
INSERT INTO `oc_platform`
  (`platform_key`,`name`,`vertical`,`grid_horizon_days`,`slot_minutes`,`supports_staff`,`supports_capacity`,`supports_next_available`,`supports_prices`,`requires_headless`,`tier`) VALUES
  ('booksy',             'Booksy',             'beauty',        14,30,1,0,1,1,1,'fe'),
  ('vagaro',             'Vagaro',             'beauty',        14,30,1,0,1,1,1,'fe'),
  ('fresha',             'Fresha',             'beauty',        14,15,1,0,1,1,1,'fe'),
  ('glossgenius',        'GlossGenius',        'beauty',        14,30,1,0,1,1,1,'fe'),
  ('square_appointments','Square Appointments','beauty',        14,15,1,0,1,1,1,'fe'),
  ('acuity',             'Acuity Scheduling',  'other',         14,30,1,0,1,1,0,'fe'),
  ('mindbody',           'Mindbody',           'fitness',        7,60,0,1,1,1,1,'oto1'),
  ('momence',            'Momence',            'fitness',        7,60,0,1,1,1,0,'oto1'),
  ('zocdoc',             'Zocdoc',             'clinic',        14,30,1,0,1,0,1,'oto1'),
  ('opentable',          'OpenTable',          'restaurant',     7,15,0,1,1,0,1,'backend'),
  ('resy',               'Resy',               'restaurant',     7,15,0,1,1,0,1,'backend'),
  ('housecall_pro',      'Housecall Pro',      'home_services', 14,60,0,0,1,0,0,'backend'),
  ('jobber',             'Jobber',             'home_services', 14,60,0,0,1,0,0,'backend'),
  ('servicetitan',       'ServiceTitan',       'home_services', 14,60,0,0,1,0,0,'backend')
ON DUPLICATE KEY UPDATE
  `name`                    = VALUES(`name`),
  `vertical`                = VALUES(`vertical`),
  `grid_horizon_days`       = VALUES(`grid_horizon_days`),
  `slot_minutes`            = VALUES(`slot_minutes`),
  `supports_staff`          = VALUES(`supports_staff`),
  `supports_capacity`       = VALUES(`supports_capacity`),
  `supports_next_available` = VALUES(`supports_next_available`),
  `supports_prices`         = VALUES(`supports_prices`),
  `requires_headless`       = VALUES(`requires_headless`),
  `tier`                    = VALUES(`tier`);

-- Seed: niches.  default_ticket_cents is a deliberately conservative US median —
-- the Empty-Chair Revenue headline must never over-claim (sales-page risk #1).
INSERT INTO `oc_niche`
  (`niche_key`,`label`,`vertical`,`default_ticket_cents`,`default_slot_minutes`,`hungry_threshold_bp`,`slammed_vacancy_bp`,`slammed_next_days`) VALUES
  ('barbershop',     'Barbershop',         'beauty',         3500,30,6000, 500,14),
  ('hair_salon',     'Hair Salon',         'beauty',         8500,60,6000, 500,14),
  ('nail_salon',     'Nail Salon',         'beauty',         5500,45,6000, 500,14),
  ('lash_brow',      'Lash & Brow Studio', 'beauty',         9500,60,6000, 500,14),
  ('med_spa',        'Med Spa',            'beauty',        25000,60,5500, 700,21),
  ('massage',        'Massage Therapy',    'beauty',         9000,60,6000, 500,14),
  ('tattoo',         'Tattoo Studio',      'beauty',        15000,90,5500, 700,21),
  ('dental',         'Dental Practice',    'clinic',        20000,30,5000,1000,21),
  ('chiropractic',   'Chiropractic',       'clinic',         7500,20,5500, 800,14),
  ('physio',         'Physiotherapy',      'clinic',         9000,30,5500, 800,14),
  ('yoga_studio',    'Yoga Studio',        'fitness',        2500,60,6500, 500, 7),
  ('pilates_studio', 'Pilates Studio',     'fitness',        3500,50,6500, 500, 7),
  ('gym_pt',         'Personal Training',  'fitness',        7500,60,6000, 500,14),
  ('restaurant',     'Restaurant',         'restaurant',     6500,15,5000,1000, 7),
  ('hvac',           'HVAC',               'home_services', 25000,60,5000,1000,14),
  ('plumbing',       'Plumbing',           'home_services', 22000,60,5000,1000,14)
ON DUPLICATE KEY UPDATE
  `label`                = VALUES(`label`),
  `vertical`             = VALUES(`vertical`),
  `default_ticket_cents` = VALUES(`default_ticket_cents`),
  `default_slot_minutes` = VALUES(`default_slot_minutes`),
  `hungry_threshold_bp`  = VALUES(`hungry_threshold_bp`),
  `slammed_vacancy_bp`   = VALUES(`slammed_vacancy_bp`),
  `slammed_next_days`    = VALUES(`slammed_next_days`);
