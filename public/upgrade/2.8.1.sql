ALTER TABLE `shd_zjmf_finance_api` ADD COLUMN `contact_way` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '联系方式';
ALTER TABLE `shd_zjmf_finance_api` ADD COLUMN `type` VARCHAR(25) NOT NULL DEFAULT 'zjmf_api' COMMENT '上游类型:zjmf_api智简魔方，manual手动';
CREATE TABLE `shd_upper_manual_info` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `hid` INT(11) NOT NULL DEFAULT '0' COMMENT '产品ID,host表id',
  `regate` TEXT COMMENT '到期时间',
  `amount` TEXT COMMENT '金额',
  `billingcycle` TEXT COMMENT '周期',
  `dedicatedip` TEXT COMMENT 'ip',
  `assignedips` TEXT COMMENT '分配ip',
  `create_time` TEXT COMMENT '开通时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hid` (`hid`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;
UPDATE `shd_auth_rule` SET `is_display` = 0 WHERE `id` = 2100;
UPDATE `shd_auth_rule` SET `pid` = 2110 WHERE `id` = 1603;
ALTER TABLE `shd_product_config_options` ADD COLUMN `qty_stage` INT(11) NOT NULL DEFAULT 0 COMMENT '数量阶梯';
ALTER TABLE `shd_user` MODIFY `last_login_ip` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '最后登录ip';
ALTER TABLE `shd_product_groups` ADD COLUMN `is_upstream` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否上游资源';
ALTER TABLE `shd_product_groups` ADD COLUMN `zjfm_api_id` INT(11) NOT NULL DEFAULT 0 COMMENT '接口id';
ALTER TABLE `shd_product_config_options` ADD `is_rebate` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否折扣';
ALTER TABLE `shd_invoices` ADD COLUMN `is_delete` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '1:标记删除';
CREATE TABLE `shd_user_tastes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `uid` INT(11) NOT NULL COMMENT '管理员id',
  `ticket_refresh` CHAR(20) NOT NULL DEFAULT 'never' COMMENT '工单自动刷新',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tastes_idx_uid` (`uid`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COMMENT='管理员喜好';
ALTER TABLE `shd_user` ADD COLUMN `cat_ownerless` TINYINT(4) NOT NULL DEFAULT '1' COMMENT '未分配客户所有人可见0关闭 1开启';