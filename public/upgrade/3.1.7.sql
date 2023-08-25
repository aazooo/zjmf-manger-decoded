ALTER TABLE `shd_host` ADD COLUMN `percent_value` DECIMAL(10,2) NOT NULL DEFAULT 120 COMMENT '购买该商品时的上游价格百分比';
ALTER TABLE `shd_products` ADD COLUMN `unretired_time` INT(11) NOT NULL DEFAULT '0' COMMENT '上架时间';
ALTER TABLE `shd_host` ADD COLUMN `agent_client` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否代理商客户购买';
ALTER TABLE `shd_zjmf_finance_api` ADD COLUMN `is_resource` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否资源池api:1是，0否默认';
ALTER TABLE `shd_zjmf_finance_api` ADD COLUMN `ticket_open` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否开启工单传递：1时，0否默认';