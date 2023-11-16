CREATE TABLE IF NOT EXISTS `#__jcomments` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `thread_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `path` VARCHAR(190) NOT NULL DEFAULT '',
    `level` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `object_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `object_group` VARCHAR(100) NOT NULL DEFAULT '',
    `lang` CHAR(7) NOT NULL DEFAULT '',
    `userid` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `name`VARCHAR(255) NOT NULL DEFAULT '',
    `username`VARCHAR(255) NOT NULL DEFAULT '',
    `email` VARCHAR(319) NOT NULL DEFAULT '',
    `homepage` VARCHAR(255) NOT NULL DEFAULT '',
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `comment` TEXT,
    `ip` VARCHAR(45) NOT NULL DEFAULT '',
    `date` DATETIME NOT NULL,
    `isgood` SMALLINT(5) NOT NULL DEFAULT '0',
    `ispoor` SMALLINT(5) NOT NULL DEFAULT '0',
    `published` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `subscribe` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `source` VARCHAR(255) NOT NULL DEFAULT '',
    `source_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `checked_out` INT(11) UNSIGNED,
    `checked_out_time` DATETIME,
    PRIMARY KEY  (`id`),
    KEY `idx_userid` (`userid`),
    KEY `idx_lang` (`lang`),
    KEY `idx_subscribe` (`subscribe`),
    KEY `idx_checkout` (`checked_out`),
    KEY `idx_object` (`object_id`, `object_group`, `published`, `date`),
    KEY `idx_path` (`path`, `level`),
    KEY `idx_thread` (`thread_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jcomments_votes` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `commentid` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `userid` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `ip` VARCHAR(45) NOT NULL DEFAULT '',
    `date` DATETIME NOT NULL,
    `value` TINYINT(1) NOT NULL,
    PRIMARY KEY  (`id`),
    KEY `idx_comment`(`commentid`,`userid`),
    KEY `idx_user` (`userid`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jcomments_subscriptions` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `object_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `object_group` VARCHAR(100) NOT NULL DEFAULT '',
    `lang` CHAR(7) NOT NULL DEFAULT '',
    `userid` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `name`VARCHAR(255) NOT NULL DEFAULT '',
    `email` VARCHAR(319) NOT NULL DEFAULT '',
    `hash` CHAR(32) NOT NULL DEFAULT '',
    `published` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `source` VARCHAR(100) NOT NULL DEFAULT '',
    `checked_out` INT(11) UNSIGNED,
    `checked_out_time` DATETIME,
    PRIMARY KEY (`id`),
    KEY `idx_object` (`object_id`, `object_group`),
    KEY `idx_lang` (`lang`),
    KEY `idx_source` (`source`),
    KEY `idx_hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jcomments_custom_bbcodes` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(64) NOT NULL DEFAULT '',
    `simple_pattern` VARCHAR(255) NOT NULL DEFAULT '',
    `simple_replacement_html` TEXT,
    `simple_replacement_text` TEXT,
    `pattern` VARCHAR(255) NOT NULL DEFAULT '',
    `replacement_html` TEXT,
    `replacement_text` TEXT,
    `button_acl` TEXT,
    `button_open_tag` VARCHAR(16) NOT NULL DEFAULT '',
    `button_close_tag` VARCHAR(16) NOT NULL DEFAULT '',
    `button_title` VARCHAR(255) NOT NULL DEFAULT '',
    `button_image` VARCHAR(255) NOT NULL DEFAULT '',
    `button_enabled` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `ordering` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `published` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
    `checked_out` INT(11) UNSIGNED,
    `checked_out_time` DATETIME,
    PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jcomments_reports` (
    `id` INT(11) UNSIGNED NOT NULL auto_increment,
    `commentid` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `userid` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `name`VARCHAR(255) NOT NULL DEFAULT '',
    `ip` VARCHAR(45) NOT NULL DEFAULT '',
    `date` DATETIME NOT NULL,
    `reason` TINYTEXT  NOT NULL,
    `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jcomments_blacklist` (
    `id` INT(11) UNSIGNED NOT NULL auto_increment,
    `ip` VARCHAR(45) NOT NULL DEFAULT '',
    `userid` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `created` DATETIME NOT NULL,
    `created_by` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `expire` DATETIME,
    `reason` TINYTEXT NOT NULL,
    `notes` TINYTEXT DEFAULT NULL,
    `checked_out` INT(11) UNSIGNED,
    `checked_out_time` DATETIME,
    PRIMARY KEY  (`id`),
    KEY `idx_checkout` (`checked_out`),
    KEY `idx_ip` (`ip`),
    KEY `idx_userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jcomments_objects` (
    `id` INT(11) UNSIGNED NOT NULL auto_increment,
    `object_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `object_group` VARCHAR(100) NOT NULL DEFAULT '',
    `category_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `lang` CHAR(7) NOT NULL DEFAULT '',
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `link` TEXT,
    `access` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
    `userid` INT(11) UNSIGNED NOT NULL DEFAULT '0',
    `expired` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
    `modified` DATETIME NOT NULL,
    PRIMARY KEY  (`id`),
    KEY `idx_object` (`object_id`, `object_group`, `lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jcomments_mailq` (
    `id` int(11) NOT NULL auto_increment,
    `name` varchar(255) NOT NULL,
    `email` varchar(319) NOT NULL,
    `subject` text NOT NULL,
    `body` text NOT NULL,
    `created` datetime NOT NULL,
    `attempts` tinyint(1) NOT NULL DEFAULT '0',
    `priority` tinyint(1) NOT NULL DEFAULT '0',
    `session_id` VARCHAR(200) DEFAULT NULL,
    PRIMARY KEY  (`id`),
    KEY `idx_priority` (`priority`),
    KEY `idx_attempts` (`attempts`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jcomments_smilies` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `code` varchar(39) NOT NULL DEFAULT '',
    `alias` varchar(39) NOT NULL DEFAULT '',
    `image` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `published` tinyint(1) NOT NULL DEFAULT '0',
    `ordering` int(11) unsigned NOT NULL DEFAULT '0',
    `checked_out` int(11) unsigned,
    `checked_out_time` datetime,
    PRIMARY KEY (`id`),
    KEY `idx_checkout` (`checked_out`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__jcomments_users` (
    `id` int(11) unsigned NOT NULL,
    `labels` text NOT NULL,
    `terms_of_use` tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
