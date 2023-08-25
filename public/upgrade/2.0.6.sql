UPDATE `shd_configuration` SET `value`='default' WHERE `setting`='order_page_style' AND `value`=0;
UPDATE `shd_configuration` SET `value`='province' WHERE `setting`='order_page_style' AND `value`=1;
UPDATE `shd_configuration` SET `value`='area' WHERE `setting`='order_page_style' AND `value`=2;
UPDATE `shd_product_groups` SET `order_frm_tpl`='default' WHERE `order_frm_tpl`=0;
UPDATE `shd_product_groups` SET `order_frm_tpl`='province' WHERE `order_frm_tpl`=1;
UPDATE `shd_product_groups` SET `order_frm_tpl`='area' WHERE `order_frm_tpl`=2;
INSERT INTO `shd_configuration`(`setting`, `value`, `create_time`, `update_time`) VALUES ('clientarea_default_themes','default',UNIX_TIMESTAMP(NOW()),0);
DELETE FROM `shd_nav` WHERE `id` IN(30,31,32,33,34);