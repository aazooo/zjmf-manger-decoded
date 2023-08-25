ALTER TABLE `shd_activity_log` ADD COLUMN `port` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '端口号' AFTER `usertype`;
ALTER TABLE `shd_activity_log` ADD COLUMN `type_data_id` INT(10) NULL DEFAULT 0 COMMENT '操作类型数据对应id' AFTER `port`;
ALTER TABLE `shd_message_log` ADD COLUMN `port` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '端口号' AFTER `ip`;
ALTER TABLE `shd_email_log` ADD COLUMN `port` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '端口号' AFTER `ip`;
ALTER TABLE `shd_admin_log` ADD COLUMN `port` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '端口号';
ALTER TABLE `shd_api_resource_log` ADD COLUMN `port` VARCHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '端口号';
ALTER TABLE `shd_clients` ADD COLUMN `api_create_time` INT(11) NOT NULL DEFAULT 0 COMMENT 'api开通时间';
ALTER TABLE `shd_clients` ADD COLUMN `lock_reason` TEXT COMMENT 'api锁定原因';
ALTER TABLE `shd_clients` ADD COLUMN `api_lock_time` INT(11) NOT NULL DEFAULT 0 COMMENT 'api锁定时间';
ALTER TABLE `shd_server_groups` ADD COLUMN `capacity` INT(10) NOT NULL DEFAULT '0' COMMENT '最大接口容量';
ALTER TABLE `shd_server_groups` ADD COLUMN `mode` INT(1) NOT NULL DEFAULT '1' COMMENT '分配方式（1：平均分配  2 满一个算一个）';
CREATE TABLE `shd_api_user_product` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` INT(11) NOT NULL DEFAULT '0' COMMENT '客户ID',
  `pid` INT(11) NOT NULL DEFAULT '0' COMMENT '产品ID',
  `ontrial` INT(11) NOT NULL DEFAULT '0' COMMENT '试用数量',
  `qty` INT(11) NOT NULL DEFAULT '0' COMMENT '最大购买数量',
  PRIMARY KEY (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `shd_products` ADD COLUMN `upstream_qty` INT(11) NOT NULL DEFAULT 0 COMMENT '上游库存';
ALTER TABLE `shd_products` ADD COLUMN `upstream_product_shopping_url` TEXT COMMENT '上游购物链接';
ALTER TABLE `shd_api_resource_log` ADD COLUMN `source` VARCHAR(255) NOT NULL DEFAULT 'API' COMMENT '来源：API,WEB';
CREATE TABLE `shd_invoicesid_tmp` (
  `original_invoicesid` INT(10) NOT NULL DEFAULT '0',
  `old_invoicesid` INT(10) NOT NULL DEFAULT '0',
  `new_invoicesid` INT(10) NOT NULL DEFAULT '0',
  `total` DECIMAL(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT;
CREATE TABLE `shd_run_maping`  (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL COMMENT '客户（产品拥有人）',
  `user` VARCHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '客户（产品拥有人）',
  `host_id` INT(11) NOT NULL COMMENT '产品id',
  `description` VARCHAR(320) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '描述（动作详情）',
  `from_type` INT(11) NULL COMMENT '来源类型 （100定时任务、200手动任务、300异步触发、400对接上游、500下游发起）',
  `active_user` VARCHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '操作人 （Systeam、系统管理员、用户）',
  `active_type` INT(11) NOT NULL COMMENT '操作类型（保存用于，发起重试时对应到操作方法 1开通、2暂停、3解除暂停、4删除、5续费、6升降级）',
  `active_type_param` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '' COMMENT '操作类型对应参数（保持重试，与首次发起的，请求情景一致性）',
  `status` TINYINT(2) NULL DEFAULT 0 COMMENT '状态 0 失败 1成功',
  `create_time` INT(11) NULL COMMENT '创建时间',
  `last_execute_time` INT(11) NULL COMMENT '最后执行时间',
  PRIMARY KEY (`id`)
)ENGINE=INNODB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT;
ALTER TABLE `shd_product_config_options` ADD `is_rebate` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否折扣';
UPDATE `shd_plugin` SET `help_url`='https://market.idcsmart.com/cart?fid=1&gid=22' WHERE `name`='Idcsmart' AND `module`='sms';
ALTER TABLE `shd_menus` ADD COLUMN `sort` INT(1) NOT NULL DEFAULT '0' COMMENT '排序';
INSERT INTO `shd_nav`(`name`,`url`,`pid`,`order`,`fa_icon`,`nav_type`,`relid`,`menuid`,`lang`,`plugin`,`menu_type`) VALUES('API管理','apimanage',3,0,'',0,1,1,'','',1);
