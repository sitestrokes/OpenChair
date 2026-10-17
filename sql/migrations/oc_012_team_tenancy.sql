-- oc_012_team_tenancy.sql — an agency's team shares one pipeline.
--
-- docs/70-radar-build.md R6. Until now every tenant row (oc_prospect, oc_market,
-- oc_pitch, oc_alert_run, oc_share_link) was keyed by the individual user, so a
-- sub-user ("Team & roles") saw an empty pipeline and "Assigned to Maya R." could
-- never point at anyone who could see the prospect.
--
-- From here the tenant is the AGENCY: users.parent_user_id when it is set and is
-- not the user themself, else the user. The tenant id goes in the existing
-- user_id columns (no rename — every index and query already keys on it). What
-- the tenant column cannot say is WHO did something, so the two artefacts with an
-- activity trail gain created_by: the share link and the pitch.
--
-- No data migration is needed for single-user accounts (tenant = user). Rows a
-- sub-user created before this migration stay under the sub-user's id; the
-- backfill below moves them to their agency so nothing goes missing.
--
-- Idempotent: safe to re-run.

SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''oc_share_link.created_by already present''',
    'ALTER TABLE `oc_share_link`
       ADD COLUMN `created_by` int(11) DEFAULT NULL
         COMMENT ''-> users.id; the team member who made the link (user_id is the agency)''
         AFTER `user_id`')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_share_link' AND COLUMN_NAME = 'created_by'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl := (
  SELECT IF(COUNT(*) > 0,
    'SELECT ''oc_pitch.created_by already present''',
    'ALTER TABLE `oc_pitch`
       ADD COLUMN `created_by` int(11) DEFAULT NULL
         COMMENT ''-> users.id; the team member who generated it (user_id is the agency)''
         AFTER `user_id`')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oc_pitch' AND COLUMN_NAME = 'created_by'
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Who made what, for rows written before this column existed.
UPDATE `oc_share_link` SET `created_by` = `user_id` WHERE `created_by` IS NULL;
UPDATE `oc_pitch`      SET `created_by` = `user_id` WHERE `created_by` IS NULL;

-- Move sub-users' existing rows to their agency. A prospect both the sub-user and
-- the agency already watch would collide on uq_prospect_user_biz; IGNORE keeps
-- the agency's row and the duplicate stays behind under the sub-user, unseen.
UPDATE IGNORE `oc_prospect` p JOIN `users` u ON u.id = p.user_id
   SET p.user_id = u.parent_user_id
 WHERE u.parent_user_id > 0 AND u.parent_user_id <> u.id;
UPDATE `oc_share_link` s JOIN `users` u ON u.id = s.user_id
   SET s.user_id = u.parent_user_id
 WHERE u.parent_user_id > 0 AND u.parent_user_id <> u.id;
UPDATE `oc_pitch` x JOIN `users` u ON u.id = x.user_id
   SET x.user_id = u.parent_user_id
 WHERE u.parent_user_id > 0 AND u.parent_user_id <> u.id;
