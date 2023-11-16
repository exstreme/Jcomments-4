CREATE TABLE IF NOT EXISTS `#__jcomments_users` (
    `id` int(11) unsigned NOT NULL,
    `labels` text NOT NULL,
    `terms_of_use` tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `#__jcomments_blacklist` ADD KEY `idx_userid` (`userid`);
ALTER TABLE `#__jcomments_custom_bbcodes` DROP COLUMN `button_prompt`;
ALTER TABLE `#__jcomments_custom_bbcodes` DROP COLUMN `button_css`;
