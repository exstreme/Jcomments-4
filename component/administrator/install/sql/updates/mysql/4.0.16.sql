ALTER TABLE `#__jcomments` CHANGE `path` `path` VARCHAR(190) NOT NULL DEFAULT '';
ALTER TABLE `#__jcomments_subscriptions` CHANGE `hash` `hash` CHAR(32) NOT NULL DEFAULT '', CHANGE `source` `source` VARCHAR(100) NOT NULL DEFAULT '';
