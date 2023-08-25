ALTER TABLE `shd_product_config_options` ADD COLUMN `copy_id` INT(11) NOT NULL DEFAULT 0 COMMENT '复制ID';
ALTER TABLE `shd_product_config_options_sub` ADD COLUMN `copy_id` INT(11) NOT NULL DEFAULT 0 COMMENT '复制ID';
ALTER TABLE `shd_host` ADD COLUMN `upstream_cost` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '上游成本';
ALTER TABLE `shd_host` MODIFY COLUMN `upstream_cost` VARCHAR(25) NOT NULL DEFAULT '' COMMENT '上游成本,存文本';
ALTER TABLE `shd_products` ADD COLUMN `upstream_stock_control` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '上游是否开启库存控制:1是，0否';
ALTER TABLE `shd_zjmf_finance_api` ADD COLUMN `auto_update` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '前台订购实时更新库存和商品,1开启默认,0关闭';
CREATE TABLE `shd_product_first_groups_customfields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `relid` int(11) NOT NULL DEFAULT '0' COMMENT '关联一级分组',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '字段名称',
  `value` varchar(255) NOT NULL DEFAULT '' COMMENT '值',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `relid` (`relid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `shd_product_groups_customfields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `relid` int(11) NOT NULL DEFAULT '0' COMMENT '关联商品分组',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '字段名称',
  `value` varchar(255) NOT NULL DEFAULT '' COMMENT '值',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `relid` (`relid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;