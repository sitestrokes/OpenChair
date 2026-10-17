-- oc_010_share_cards.sql — the signature visuals' share links and pipeline owner.
--
-- docs/60-signature-visuals.md §4.5. A consultant picks a card on the business
-- screen (Chair Week, Chair Map, Receipt, Same Tuesday, 8-week report) and gets a
-- link to send — by their own hand, from their own phone. OpenChair never
-- contacts the business (05-prd.md §8 rule 7); this table only records that a
-- link exists and how often it was opened.
--
-- The card is FROZEN into `payload` at the moment the link is made, for the same
-- reason a pitch freezes its evidence (oc_pitch.evidence_gz): the picture an
-- owner opens next Tuesday must be the picture the consultant chose to send, not
-- whatever the nightly scan says by then.
--
-- Rule 10 (never publish rankings): links are unguessable (128-bit token),
-- expire, are served noindex, and the payload never names another shop.
--
-- Idempotent: safe to re-run.

CREATE TABLE IF NOT EXISTS `oc_share_link` (
  `id`              bigint(20)   NOT NULL AUTO_INCREMENT,
  `token`           char(32)     NOT NULL,                       -- bin2hex(random_bytes(16)); the only key the public URL carries
  `user_id`         int(11)      NOT NULL,                       -- -> users.id (the consultant who made it)
  `business_id`     int(11)      NOT NULL,                       -- -> oc_business.id
  `kind`            enum('week','map','receipt','tuesday','progress') NOT NULL,
  `show_logo`       tinyint(1)   NOT NULL DEFAULT 1,             -- "Show my agency logo"
  `method`          varchar(16)  NOT NULL DEFAULT 'v1.0',         -- Chair Score method version printed on the card
  `payload`         longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `expires_at`      datetime     NOT NULL,
  `open_count`      int(11)      NOT NULL DEFAULT 0,             -- counted, not tracked: no IP, no UA, no cookie
  `last_opened_at`  datetime     DEFAULT NULL,
  `revoked_at`      datetime     DEFAULT NULL,
  `created_at`      datetime     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_share_token` (`token`),
  -- the Activity panel: this consultant's links for this business, newest first
  KEY `idx_share_user_biz` (`user_id`,`business_id`,`created_at`),
  KEY `idx_share_expiry`   (`expires_at`),
  CONSTRAINT `fk_share_business` FOREIGN KEY (`business_id`) REFERENCES `oc_business` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- "Assigned to Maya R." and "Next: send the pitch · due Mon, Oct 20" (PDF p.8).
-- Tenant-layer columns: who on the agency team owns this prospect, and when the
-- next step is due. The "3 due" badge on Home counts next_due_on <= today.
SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''oc_prospect.assigned_user_id already present''',
    'ALTER TABLE `oc_prospect`
       ADD COLUMN `assigned_user_id` int(11) DEFAULT NULL
         COMMENT ''-> users.id; the team member who owns this prospect''
         AFTER `user_id`')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_prospect'
    AND COLUMN_NAME = 'assigned_user_id'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''oc_prospect.next_due_on already present''',
    'ALTER TABLE `oc_prospect`
       ADD COLUMN `next_due_on` date DEFAULT NULL
         COMMENT ''when the next pipeline step is due''
         AFTER `last_pitched_at`')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_prospect'
    AND COLUMN_NAME = 'next_due_on'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''idx_prospect_user_due already present''',
    'ALTER TABLE `oc_prospect` ADD KEY `idx_prospect_user_due` (`user_id`,`next_due_on`)')
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_prospect'
    AND INDEX_NAME = 'idx_prospect_user_due'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
