ALTER TABLE `#__jcomments` CHANGE `ip` `ip` VARCHAR(45) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL, CHANGE `email` `email` VARCHAR(319) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL, DROP COLUMN `object_params`, DROP COLUMN `editor`, DROP INDEX `idx_source`, DROP INDEX `idx_email`;
ALTER TABLE `#__jcomments_subscriptions` CHANGE `hash` `hash` CHAR(32) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `#__jcomments_custom_bbcodes` CHANGE `checked_out` `checked_out` INT UNSIGNED NULL, CHANGE `checked_out_time` `checked_out_time` DATETIME NULL;
ALTER TABLE `#__jcomments_blacklist` CHANGE `ip` `ip` VARCHAR(45) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL, DROP COLUMN `editor`;
ALTER TABLE `#__jcomments_reports` CHANGE `ip` `ip` VARCHAR(45) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL;
ALTER TABLE `#__jcomments_votes` CHANGE `ip` `ip` VARCHAR(45) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL;
