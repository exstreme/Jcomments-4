ALTER TABLE `#__jcomments` CHANGE `date` `date` DATETIME NOT NULL, CHANGE `checked_out` `checked_out` INT(11) UNSIGNED NULL, CHANGE `checked_out_time` `checked_out_time` DATETIME NULL;
ALTER TABLE `#__jcomments_blacklist` CHANGE `created` `created` DATETIME NOT NULL, CHANGE `expire` `expire` DATETIME NOT NULL, CHANGE `checked_out` `checked_out` INT(11) UNSIGNED NULL, CHANGE `checked_out_time` `checked_out_time` DATETIME NULL;
ALTER TABLE `#__jcomments_custom_bbcodes` CHANGE `checked_out` `checked_out` INT(11) UNSIGNED NULL, CHANGE `checked_out_time` `checked_out_time` DATETIME NULL;
ALTER TABLE `#__jcomments_objects` CHANGE `created` `created` DATETIME NOT NULL;
ALTER TABLE `#__jcomments_objects` CHANGE `modified` `modified` DATETIME NOT NULL;
ALTER TABLE `#__jcomments_reports` CHANGE `date` `date` DATETIME NOT NULL;
ALTER TABLE `#__jcomments_smilies` CHANGE `checked_out` `checked_out` INT(11) UNSIGNED NULL, CHANGE `checked_out_time` `checked_out_time` DATETIME NULL;
ALTER TABLE `#__jcomments_subscriptions` CHANGE `checked_out` `checked_out` INT(11) UNSIGNED NULL, CHANGE `checked_out_time` `checked_out_time` DATETIME NULL;
ALTER TABLE `#__jcomments_votes` CHANGE `date` `date` DATETIME NOT NULL;
