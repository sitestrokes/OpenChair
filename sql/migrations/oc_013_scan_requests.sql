-- oc_013_scan_requests.sql — "New scan" as a tenant's request over the global
-- discovery table.
--
-- docs/70-radar-build.md R1. oc_discovery_candidate is GLOBAL (one row per
-- listing, deduped across every tenant who ever pasted it) — the same split as
-- oc_business. What a tenant needs is "the scan I asked for and how it is going",
-- so the request lives in the tenant layer and points AT the shared candidates.
--
-- Idempotent: safe to re-run.

CREATE TABLE IF NOT EXISTS `oc_scan_request` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`     int(11)      NOT NULL,                   -- tenant (agency) — oc_012
  `created_by`  int(11)      DEFAULT NULL,               -- the team member who asked
  `city_key`    varchar(80)  NOT NULL,
  `niche_key`   varchar(48)  NOT NULL,
  `city_label`  varchar(120) NOT NULL DEFAULT '',
  `url_count`   smallint(6)  NOT NULL DEFAULT 0,
  `created_at`  datetime     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_scanreq_user` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `oc_scan_request_item` (
  `request_id`   int(11) NOT NULL,                       -- -> oc_scan_request.id
  `candidate_id` int(11) NOT NULL,                       -- -> oc_discovery_candidate.id (global)
  PRIMARY KEY (`request_id`,`candidate_id`),
  KEY `idx_scanitem_candidate` (`candidate_id`),
  CONSTRAINT `fk_scanitem_request`   FOREIGN KEY (`request_id`)   REFERENCES `oc_scan_request` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_scanitem_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `oc_discovery_candidate` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
