-- oc_admin_seed.sql — the first account.
--
-- A fresh clone has no way in: Ion Auth needs a row in `users` and a matching
-- `users_groups` row before the login form can do anything. This seeds one admin
-- so the app is walkable immediately.
--
-- LOCAL DEVELOPMENT ONLY. The password below is committed on purpose so a new
-- machine can get in, which also means it must be changed or the row deleted
-- before this ever faces the internet. It is not a secret and must never be
-- treated as one.
--
--   email:    admin@openchair.local
--   password: OpenChair!2026
--
-- Idempotent: safe to re-run (re-running resets the password to the value above).

INSERT INTO `groups` (`id`, `name`, `description`) VALUES
  (1, 'admin',   'Administrator'),
  (2, 'members', 'General User')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- access '1,51' = FE (login) + JV Admin (Manage Users, White-label, Adapters).
INSERT INTO `users`
  (`id`, `ip_address`, `username`, `password`, `email`, `activation_code`,
   `forgotten_password_code`, `created_on`, `last_login`, `active`,
   `first_name`, `last_name`, `company`, `phone`, `access`,
   `created_start_date`, `created_end_date`, `product_id`)
VALUES
  (1, '127.0.0.1', 'admin', '$2y$12$lniV.ZHHhkwv0GKSA5pnEOvnJrynWr61l0VV76rdWrASTL.2IH1Fm', 'admin@openchair.local', '', NULL, 1788618156, 1788618156, 1,
   'OpenChair', 'Admin', 'OpenChair', '', '1,51', '', '', '')
ON DUPLICATE KEY UPDATE
  `password` = VALUES(`password`),
  `email`    = VALUES(`email`),
  `active`   = 1,
  `access`   = VALUES(`access`);

INSERT INTO `users_groups` (`user_id`, `group_id`) VALUES (1, 1)
ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`);
