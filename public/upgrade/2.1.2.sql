ALTER TABLE `shd_invoice_items` ADD COLUMN `description2` VARCHAR(5000) NOT NULL DEFAULT '' COMMENT '前台账单内页描述' AFTER `description`;
ALTER TABLE `shd_hook_plugin` MODIFY COLUMN `module` VARCHAR(25) NOT NULL DEFAULT 'addons' COMMENT '插件钩子所属模块';
alter table `shd_product_groups` add column `alias` varchar(100) not null DEFAULT '' comment '别名';
alter table `shd_news_type` add column `alias` varchar(100) not null DEFAULT '' comment '别名';