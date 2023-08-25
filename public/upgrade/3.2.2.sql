CREATE TABLE `shd_userdownloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '附件名',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '文件地址',
  `remarks` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  `downame` varchar(255) NOT NULL DEFAULT '' COMMENT '文件名',
  `adminid` int(11) NOT NULL DEFAULT '0' COMMENT '管理员id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;
ALTER TABLE `shd_message_template` CHANGE `template_id` `template_id` VARCHAR(64) NOT NULL DEFAULT '';