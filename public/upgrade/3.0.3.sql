ALTER TABLE `shd_product_config_options` ADD COLUMN `linkage_pid` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `shd_product_config_options` ADD COLUMN `linkage_top_pid` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `shd_product_config_options` ADD COLUMN `linkage_level`VARCHAR(255) NOT NULL DEFAULT '0';
ALTER TABLE `shd_product_config_options_sub` ADD COLUMN `linkage_pid` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `shd_product_config_options_sub` ADD COLUMN `linkage_top_pid` INT(11) NOT NULL DEFAULT '0';
ALTER TABLE `shd_product_config_options_sub` ADD COLUMN `linkage_level` VARCHAR(255) NOT NULL DEFAULT '0';
ALTER TABLE `shd_host` modify COLUMN `domainstatus` enum('Pending','Active','Suspended','Cancelled','Fraud','Completed','Deleted','Verifiy_Active','Overdue_Active','Issue_Active') NOT NULL DEFAULT 'Pending' COMMENT '状态';