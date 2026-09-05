-- oc_009_removal_requests.sql — the business opt-out path (FR-9.3, 50-risk A6.6).
--
-- A business owner who finds out we are reading their booking page must be able
-- to say stop, and be believed, on a published SLA. oc_business already has a
-- `suppressed_at` tombstone; what was missing is the INTAKE — who asked, when,
-- how we verified it was really them, and when we acted.
--
-- Three design points worth stating, because each is the sort of thing that gets
-- quietly simplified later:
--
-- 1. THE TOMBSTONE SURVIVES. Suppressing a business nulls its children (grids,
--    rollups, dead-zone cells) but keeps the oc_business row with suppressed_at
--    set. Deleting the row outright would mean the next discovery run finds the
--    same shop, adds it back, and starts scanning again — an opt-out that
--    silently expires is worse than none, because the owner believes it worked.
--
-- 2. VERIFICATION IS RECORDED, NOT ASSUMED. Anyone can type a business name into
--    a form. Acting on an unverified request lets a competitor remove a rival
--    from every consultant's list; refusing to act without a notarised letter
--    makes the promise hollow. So the method and evidence are stored, and the
--    SLA clock starts at request time regardless.
--
-- 3. THE SLA IS A COLUMN, NOT A POLICY PAGE. due_by is written on intake, so
--    "we honour removal requests within N days" is a query anyone can run
--    against reality rather than a claim on a website.
--
-- Idempotent: safe to re-run.

CREATE TABLE IF NOT EXISTS `oc_removal_request` (
  `id`              int(11)      NOT NULL AUTO_INCREMENT,
  `business_id`     int(11)      DEFAULT NULL,              -- NULL until matched to a row
  `platform_key`    varchar(32)  DEFAULT NULL,
  `booking_url`     varchar(512) DEFAULT NULL,              -- what the requester pointed at
  `business_name`   varchar(191) DEFAULT NULL,
  `requester_name`  varchar(191) DEFAULT NULL,
  `requester_email` varchar(191) DEFAULT NULL,              -- PERSONAL DATA; purged with the request
  `requester_role`  varchar(80)  DEFAULT NULL,              -- 'owner', 'manager', 'agent'
  `channel`         enum('form','email','phone','post','platform') NOT NULL DEFAULT 'form',
  `message`         text         DEFAULT NULL,

  -- Verification. An unverified request is still ACTED ON within the SLA; the
  -- record exists so an operator can see what basis they had, and so a pattern
  -- of anonymous requests against one competitor's rivals is visible.
  `verify_method`   enum('none','email_domain','phone_match','platform_contact','manual') NOT NULL DEFAULT 'none',
  `verify_note`     varchar(255) DEFAULT NULL,
  `verified_at`     datetime     DEFAULT NULL,

  `status`          enum('new','verifying','actioned','rejected','duplicate') NOT NULL DEFAULT 'new',
  `requested_at`    datetime     NOT NULL DEFAULT current_timestamp(),
  `due_by`          datetime     NOT NULL,                  -- the SLA, written on intake
  `actioned_at`     datetime     DEFAULT NULL,
  `actioned_by`     int(11)      DEFAULT NULL,              -- -> users.id, loose
  `outcome_note`    varchar(255) DEFAULT NULL,
  `created_at`      datetime     NOT NULL DEFAULT current_timestamp(),
  `updated_at`      datetime     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  -- The operator's real question: what is open and what is overdue.
  KEY `idx_removal_queue` (`status`,`due_by`),
  KEY `idx_removal_business` (`business_id`),
  KEY `idx_removal_url` (`booking_url`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- The permanent do-not-scan list.
--
-- Separate from oc_business.suppressed_at on purpose: this is keyed by the
-- PLATFORM LISTING, so it still matches when discovery meets the same shop
-- again under a new internal id, or on a second platform. Without it, "never
-- scan us again" lasts exactly until the next discovery sweep.
CREATE TABLE IF NOT EXISTS `oc_do_not_scan` (
  `id`                    int(11)      NOT NULL AUTO_INCREMENT,
  `platform_key`          varchar(32)  NOT NULL,
  `platform_business_key` varchar(191) NOT NULL,
  `booking_url`           varchar(512) DEFAULT NULL,
  `reason`                enum('owner_request','legal','platform_request','internal') NOT NULL DEFAULT 'owner_request',
  `removal_request_id`    int(11)      DEFAULT NULL,
  `note`                  varchar(255) DEFAULT NULL,
  `created_at`            datetime     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dns_listing` (`platform_key`,`platform_business_key`),
  KEY `idx_dns_reason` (`reason`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
