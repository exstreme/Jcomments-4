ALTER TABLE `#__jcomments_subscriptions` CHANGE `hash` `hash` CHAR(32) NOT NULL DEFAULT '';
ALTER TABLE `#__jcomments_subscriptions` CHANGE `source` `source` VARCHAR(100) NOT NULL DEFAULT '';
