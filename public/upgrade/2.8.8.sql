ALTER TABLE `shd_product_config_options` ADD COLUMN `unit` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '单位';
ALTER TABLE `shd_product_config_options` ADD COLUMN `senior` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否开启高级设置:1是，0否';
ALTER TABLE `shd_invoices` ADD COLUMN `credit_limit_prepayment` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '1:信用额提前还款';
ALTER TABLE `shd_invoices` ADD COLUMN `credit_limit_prepayment_invoices` TEXT NOT NULL COMMENT '提前还款的账单ID';
CREATE TABLE `shd_affiliates_user_temp` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '临时表',
  `uid` INT(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `affid_uid` INT(11) NOT NULL DEFAULT '0' COMMENT '推荐id临时记录用uid代替',
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `affid` (`affid_uid`)
) ENGINE=INNODB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
ALTER TABLE `shd_cancel_requests` ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '执行状态:0未执行,1成功,2失败' AFTER `delete_time`;
INSERT INTO `shd_configuration` (`setting`,`value`) VALUES ('artificial_auto_send_msg','0'),('certifi_business_open','0'),('certifi_business_is_upload','0'),('certifi_business_is_author','0'),('certifi_business_author_path','');
ALTER TABLE `shd_certifi_company` ADD `img_four` VARCHAR(255) NOT NULL DEFAULT '';