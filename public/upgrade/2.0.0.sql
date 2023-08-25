CREATE TABLE `shd_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(50) NOT NULL DEFAULT '' COMMENT '菜单类型 client会员中心,www官网',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;
insert  into `shd_menu`(`id`,`name`,`type`) values (1,'会员中心-默认会员中心','client');
CREATE TABLE `shd_menu_active` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(100) NOT NULL DEFAULT '' COMMENT 'client会员中心,www_top官网顶部,www_bottom官网底部',
  `menuid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;
insert  into `shd_menu_active`(`id`,`type`,`menuid`) values (1,'client',1),(2,'www_top',0),(3,'www_bottom',0);
CREATE TABLE `shd_nav` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '名称',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '上级id',
  `order` int(11) NOT NULL DEFAULT '0',
  `fa_icon` varchar(255) NOT NULL DEFAULT '',
  `nav_type` tinyint(7) NOT NULL DEFAULT '0' COMMENT '导航类型 0系统类型,1自定义页面,2产品中心',
  `relid` text NOT NULL COMMENT '关联的商品ID',
  `menuid` int(11) NOT NULL DEFAULT '0' COMMENT '菜单ID',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`) USING BTREE,
  KEY `menuid` (`menuid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4;
insert  into `shd_nav`(`id`,`name`,`url`,`pid`,`order`,`fa_icon`,`nav_type`,`relid`,`menuid`) values (1,'用户中心','clientarea',0,0,'bx bx-home-circle',0,'1',1),(2,'产品与服务','',0,0,'bx bxs-grid-alt',0,'1',1),(3,'账户管理','',0,0,'bx bx-user',0,'1',1),(4,'财务管理','',0,0,'bx bx-dollar-circle',0,'1',1),(5,'技术支持','',0,0,'bx bx-detail',0,'1',1),(6,'开发者中心','',0,0,'bx bx-shopping-bag',0,'1',1),(7,'推介计划','affiliates',0,0,'bx bxs-paper-plane',0,'1',1),(8,'订购产品','cart',2,0,'',0,'1',1),(9,'云服务器','service?groupid=1',2,0,'',0,'1',1),(10,'独立服务器','service?groupid=2',2,0,'',0,'1',1),(11,'其他服务器','service?groupid=3',2,0,'',0,'1',1),(12,'个人信息','details',3,0,'',0,'1',1),(13,'安全中心','security',3,0,'',0,'1',1),(14,'实名认证','verified',3,0,'',0,'1',1),(15,'消息中心','message',3,0,'',0,'1',1),(17,'系统日志','systemlog',3,0,'',0,'1',1),(19,'账单列表','billing',4,0,'',0,'1',1),(20,'发票管理','',4,2,'',0,'1',1),(21,'发票列表','invoicelist',20,0,'',0,'1',1),(22,'发票抬头','invoicecompany',20,0,'',0,'1',1),(23,'收货地址','invoiceaddress',20,0,'',0,'1',1),(24,'账户充值','addfunds',4,0,'',0,'1',1),(25,'工单列表','supporttickets',5,0,'',0,'1',1),(26,'提交工单','submitticket',5,0,'',0,'1',1),(27,'帮助中心','knowledgebase',5,0,'',0,'1',1),(28,'新闻中心','news',5,0,'',0,'1',1),(29,'资源下载','downloads',5,0,'',0,'1',1),(35,'交易记录','transaction',4,1,'',0,'1',1);