CREATE TABLE `shd_zjmf_pushhost` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `host_id` INT(10) NOT NULL COMMENT '主机ID',
  `status` CHAR(1) NOT NULL DEFAULT '0' COMMENT '1成功,0失败',
  `url` TEXT NOT NULL COMMENT 'url',
  `post_data` TEXT NOT NULL COMMENT '推送给下游信息',
  `time` INT(10) NOT NULL COMMENT '上一次推送时间',
  `num` TINYINT(2) NOT NULL COMMENT '推送次数',
  PRIMARY KEY (`id`),
  KEY `ststus` (`status`),
  KEY `host_id` (`host_id`),
  KEY `num` (`num`)
) ENGINE=INNODB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT COMMENT='';
DELETE FROM shd_areas WHERE `area_id`=820303 OR `area_id`=820302;
ALTER TABLE `shd_zjmf_finance_api` ADD COLUMN `is_resource` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否资源池api:1是，0否默认';
ALTER TABLE `shd_zjmf_finance_api` ADD COLUMN `ticket_open` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否开启工单传递：1时，0否默认';
ALTER TABLE `shd_host` ADD COLUMN `percent_value` DECIMAL(10,2) NOT NULL DEFAULT 120 COMMENT '购买该商品时的上游价格百分比';
ALTER TABLE `shd_products` ADD COLUMN `unretired_time` INT(11) NOT NULL DEFAULT '0' COMMENT '上架时间';
ALTER TABLE `shd_host` ADD COLUMN `agent_client` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否代理商客户购买';
ALTER TABLE `shd_zjmf_finance_api` ADD COLUMN `is_using` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否正在使用的资源池账号';