ALTER TABLE `#__jcomments` COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jcomments_blacklist` COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jcomments_custom_bbcodes` COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jcomments_mailq` COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jcomments_objects` COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jcomments_reports` COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jcomments_smilies` COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jcomments_subscriptions` COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `#__jcomments_votes` COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `#__jcomments` CHANGE `object_group` `object_group` VARCHAR(100) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL, CHANGE `lang` `lang` CHAR(7) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL;
ALTER TABLE `#__jcomments_objects` CHANGE `object_group` `object_group` VARCHAR(100) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL, CHANGE `lang` `lang` CHAR(7) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL;
ALTER TABLE `#__jcomments_subscriptions` CHANGE `object_group` `object_group` VARCHAR(100) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL, CHANGE `lang` `lang` CHAR(7) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL;

UPDATE `#__assets`
SET `rules` = '{\"comment.comment\":{\"9\":1,\"6\":1,\"2\":1},\"comment.reply\":{\"9\":1,\"6\":1,\"2\":1},\"comment.autopublish\":{\"9\":0,\"6\":1,\"2\":1},\"comment.captcha\":{\"9\":0,\"6\":1,\"2\":0},\"comment.flood\":{\"9\":0,\"6\":1,\"2\":1},\"comment.length_check\":{\"9\":0,\"6\":1,\"2\":0},\"comment.subscribe\":{\"9\":0,\"6\":1,\"2\":1},\"comment.terms_of_use\":{\"9\":0,\"6\":1,\"2\":0},\"comment.vote\":{\"9\":0,\"6\":1,\"2\":1},\"comment.report\":{\"9\":0,\"6\":1,\"2\":1},\"comment.ban\":{\"9\":0,\"6\":1,\"2\":0},\"comment.edit\":{\"9\":0,\"6\":1,\"2\":0},\"comment.edit.own\":{\"9\":0,\"6\":1,\"2\":1},\"comment.edit.own.articles\":{\"9\":0,\"6\":0,\"2\":1},\"comment.publish\":{\"9\":0,\"6\":1,\"2\":0},\"comment.publish.own\":{\"9\":0,\"6\":0,\"2\":1},\"comment.delete\":{\"9\":0,\"6\":0,\"2\":0},\"comment.delete.own\":{\"9\":0,\"6\":1,\"2\":1},\"comment.delete.own.articles\":{\"9\":0,\"6\":0,\"2\":1},\"comment.bbcode.b\":{\"9\":0,\"6\":1,\"2\":1},\"comment.bbcode.i\":{\"9\":0,\"6\":1,\"2\":1},\"comment.bbcode.u\":{\"9\":0,\"6\":1,\"2\":1},\"comment.bbcode.s\":{\"9\":0,\"6\":1,\"2\":1},\"comment.bbcode.url\":{\"9\":0,\"6\":0,\"2\":1},\"comment.bbcode.img\":{\"9\":0,\"6\":0,\"2\":1},\"comment.bbcode.list\":{\"9\":0,\"6\":1,\"2\":1},\"comment.bbcode.hide\":{\"9\":0,\"6\":1,\"2\":1},\"comment.bbcode.quote\":{\"9\":0,\"6\":1,\"2\":1},\"comment.autolink\":{\"9\":0,\"6\":1,\"2\":1},\"comment.email.protect\":{\"9\":0,\"6\":1,\"2\":1},\"comment.gravatar\":{\"9\":0,\"6\":0,\"2\":0},\"comment.view.email\":{\"9\":0,\"6\":1,\"2\":1},\"comment.view.site\":{\"9\":0,\"6\":1,\"2\":0},\"comment.view.ip\":{\"9\":0,\"6\":1,\"2\":0},\"core.admin\":{\"9\":0,\"6\":0,\"2\":0},\"core.manage\":{\"9\":0,\"6\":0,\"2\":0},\"core.create\":{\"9\":0,\"6\":0,\"2\":0},\"core.delete\":{\"9\":0,\"6\":0,\"2\":0},\"core.edit\":{\"9\":0,\"6\":0,\"2\":1},\"core.edit.state\":{\"9\":0,\"6\":0,\"2\":1}}'
WHERE `name` = 'com_jcomments';

