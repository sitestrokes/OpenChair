-- oc_000_engine_base.sql - the platform tables OpenChair inherits.
--
-- OpenChair runs on the same CodeIgniter-3 engine as Agentic Affiliates, so it
-- needs that engine's account layer. These thirteen tables are ported AS-IS
-- rather than redesigned: Ion Auth (users, groups, users_groups, login_attempts,
-- login_devices), the settings store (per-user BYOK keys AND the white-label
-- brand row, both keyed by meta_type), the agency sub-account pair, platform
-- config, and the few odds the ported modules read directly.
--
-- Numbered 000 because everything in oc_001..oc_007 assumes accounts already
-- exist: oc_market.user_id and oc_prospect.user_id point at `users` by loose INT.
-- That is the boundary 30-data-model.md section 7 draws - the oc_ island links
-- OUT to the engine and never inward, so there are no cross-schema foreign keys
-- and either half can migrate without the other.
--
-- WHAT IS DELIBERATELY ABSENT, and will stay absent:
--
--   subscribers, funnels, campaigns, sequence_queue, twilio_call_logs,
--   twilio_call_services, voice_call_config
--
-- OpenChair has no leads of its own and no outbound channel. 05-prd.md section 8
-- rule 7 requires that no code path exist from Pitch into a queue or telephony,
-- and the cheapest way to guarantee that for good is for the queue not to exist
-- in this database. A future Pitch "send" feature would fail at the schema, which
-- is exactly where we want it to fail.
--
-- Idempotent: safe to re-run.

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(254) COLLATE utf8_unicode_ci NOT NULL,
  `activation_code` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `forgotten_password_code` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `forgotten_password_time` int(10) unsigned DEFAULT NULL,
  `forgotten_password_selector` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `remember_code` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_on` int(10) unsigned NOT NULL,
  `last_login` int(10) unsigned DEFAULT NULL,
  `active` tinyint(3) unsigned DEFAULT NULL,
  `first_name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `company` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `access` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `remember_selector` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_start_date` text COLLATE utf8_unicode_ci NOT NULL,
  `created_end_date` text COLLATE utf8_unicode_ci NOT NULL,
  `product_id` text COLLATE utf8_unicode_ci NOT NULL,
  `split_payment_num` tinyint(4) NOT NULL DEFAULT 0,
  `parent_user_id` int(10) NOT NULL,
  `count_research` int(10) NOT NULL DEFAULT 0,
  `campaignCount` int(11) NOT NULL DEFAULT 0,
  `landing_page_id` text COLLATE utf8_unicode_ci NOT NULL,
  `ses_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9011 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `groups` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `users_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `group_id` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uc_users_groups` (`user_id`,`group_id`),
  KEY `fk_users_groups_users1_idx` (`user_id`),
  KEY `fk_users_groups_groups1_idx` (`group_id`),
  CONSTRAINT `fk_users_groups_groups1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_users_groups_users1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `login` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `time` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `login_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `_url` text COLLATE utf8_unicode_ci NOT NULL,
  `ip_address` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `login` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `meta_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_data` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_settings_user` (`user_id`,`meta_type`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `business_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `website` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL,
  `industry` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `services` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `extra_context` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `kb_files` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `knowledgebase` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('active','draft','archived') COLLATE utf8_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `archived_at` datetime DEFAULT NULL,
  `archived_by` int(11) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `language_code` varchar(8) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'en',
  `custom_domain` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `custom_domain_status` enum('pending','live','broken') COLLATE utf8_unicode_ci DEFAULT NULL,
  `custom_domain_added_at` datetime DEFAULT NULL,
  `portal_token` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `portal_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `portal_last_seen` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_client_custom_domain` (`custom_domain`),
  UNIQUE KEY `uq_clients_portal_token` (`portal_token`),
  KEY `idx_user` (`user_id`),
  KEY `idx_name` (`business_name`),
  KEY `idx_clients_archived_at` (`archived_at`),
  KEY `idx_clients_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `client_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `ion_user_id` int(11) DEFAULT NULL,
  `email` varchar(254) COLLATE utf8_unicode_ci NOT NULL,
  `first_name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_name` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `permissions` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('active','suspended') COLLATE utf8_unicode_ci DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_client` (`client_id`),
  KEY `idx_ion_user` (`ion_user_id`),
  CONSTRAINT `client_users_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `doe` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `antispam` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `autoresponder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `param3` text NOT NULL,
  `status` int(11) NOT NULL,
  `respondertype` varchar(100) NOT NULL,
  `param1` text NOT NULL,
  `param2` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `place_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `place_hours` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `website` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `yelpUrl` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(90) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reviews` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `count_res` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `yelpData` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` int(11) NOT NULL COMMENT '1- Google 2-Yelp',
  `status` int(11) NOT NULL,
  `createAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `updatedAt` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `images` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `place_id` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dealpage_id` int(11) NOT NULL,
  `additional_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_analysis` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `competitors_id` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pageData` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `c_value` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `featured` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) NOT NULL,
  `place_cordinates` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `approve_status` int(11) NOT NULL DEFAULT 1,
  `search_keywords` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `claim_status` int(11) NOT NULL DEFAULT 0,
  `free_listing` int(11) NOT NULL,
  `location` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Leads Page',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `whitelabel_settings` (
  `id` int(100) NOT NULL AUTO_INCREMENT,
  `image_name` varchar(255) NOT NULL,
  `status` int(100) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `user_id` int(100) NOT NULL,
  `bonus_tab` varchar(10) NOT NULL DEFAULT '1',
  `tutorial_tab` varchar(10) NOT NULL DEFAULT '1',
  `support_tab` int(11) NOT NULL,
  `footer_text` varchar(150) NOT NULL DEFAULT 'Copyright &copy; Funnel 2021. All Rights Reserved | Cookies Policy | Disclaimers',
  `brand_text` varchar(150) NOT NULL DEFAULT 'Local Funnel',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
