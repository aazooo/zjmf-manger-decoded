INSERT INTO `shd_configuration` (`setting`,`value`,`create_time`, `update_time`) VALUES ('allow_custom_clients_id','0',UNIX_TIMESTAMP(NOW()),0);
INSERT INTO `shd_configuration` (`setting`,`value`,`create_time`, `update_time`) VALUES ('custom_clients_id_start','0',UNIX_TIMESTAMP(NOW()),0);
ALTER TABLE `shd_clients` ADD COLUMN `api_open` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否开启API';
ALTER TABLE `shd_plugin` MODIFY COLUMN `author` TEXT COMMENT '作者';
ALTER TABLE `shd_plugin` MODIFY COLUMN `author_url` TEXT COMMENT '作者链接';
ALTER TABLE `shd_plugin` MODIFY COLUMN `url` TEXT COMMENT '图标地址';
ALTER TABLE `shd_plugin` MODIFY COLUMN `description` TEXT COMMENT '插件描述';
ALTER TABLE `shd_activity_log` ADD COLUMN `port` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '端口号';