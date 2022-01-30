DROP TABLE IF EXISTS `#__jcomments_settings`;
DROP TABLE IF EXISTS `#__jcomments_version`;

ALTER TABLE `#__jcomments` CHARSET=utf8mb4, COLLATE=utf8mb4_general_ci;
ALTER TABLE `#__jcomments_blacklist` CHARSET=utf8mb4, COLLATE=utf8mb4_general_ci;
ALTER TABLE `#__jcomments_custom_bbcodes` CHARSET=utf8mb4, COLLATE=utf8mb4_general_ci;
ALTER TABLE `#__jcomments_mailq` CHARSET=utf8mb4, COLLATE=utf8mb4_general_ci;
ALTER TABLE `#__jcomments_objects` CHARSET=utf8mb4, COLLATE=utf8mb4_general_ci;
ALTER TABLE `#__jcomments_reports` CHARSET=utf8mb4, COLLATE=utf8mb4_general_ci;
ALTER TABLE `#__jcomments_smilies` CHARSET=utf8mb4, COLLATE=utf8mb4_general_ci;
ALTER TABLE `#__jcomments_subscriptions` CHARSET=utf8mb4, COLLATE=utf8mb4_general_ci;
