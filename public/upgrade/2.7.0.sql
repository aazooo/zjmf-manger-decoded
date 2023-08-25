INSERT INTO `shd_configuration`(`setting`, `value`, `create_time`, `update_time`) VALUES ('cron_order_unpaid_time_high','0',UNIX_TIMESTAMP(NOW()),0);
ALTER TABLE `shd_products` ADD COLUMN `cancel_control` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '取消控制(1:启用)默认0';
ALTER TABLE `shd_credit` ADD COLUMN `balance` DECIMAL(10,2) NOT NULL DEFAULT '0.00';
ALTER TABLE `shd_certifi_log` ADD COLUMN `custom_fields_log` VARCHAR(255) DEFAULT '' COMMENT '自定义字段json';
CREATE TABLE `shd_run_croning`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cron_type` int(11) NOT NULL DEFAULT 0 COMMENT '定时任务类型',
  `active_type` int(11) NOT NULL DEFAULT 0 COMMENT '对应队列操作类型id',
  `status` tinyint(2) NOT NULL DEFAULT 0 COMMENT '执行状态',
  `create_time` int(11) NOT NULL DEFAULT 0,
  `datetime` int(11) NOT NULL DEFAULT 0 COMMENT '时间范围YYYYMMDD',
  `unique_tab` varchar(64) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '0' COMMENT '任务唯一标识',
  PRIMARY KEY (`id`),
  INDEX `sts_dat_unt_idx`(`status`, `datetime`, `unique_tab`)
);