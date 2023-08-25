<?php

return ["zh-cn" => ["refund_invoice" => "从账单id：%d 退款到余额", "recharge_ok" => "充值成功 账单：%d", "User_admin_list" => "查看管理员列表 - 搜索关键字:%s", "User_admin_addpage" => "进入管理员添加页面", "User_admin_create_success" => "管理员添加 - Admin ID:%d - 成功", "User_admin_create_fail" => "管理员添加 - 失败", "User_admin_edit_page" => "进入管理员编辑页面 - Admin ID:%d", "User_admin_edit_page_fail" => "编辑管理员 - Admin ID:%d -修改前[ 用户名:%s , 用户邮箱:%s , 用户类型:%s , 用户状态:%s , 用户昵称:%s , 个性签名:%s , 语言:%s , 角色 Role ID:%d ] - 修改后[  用户名:%s , 用户邮箱:%s , 用户类型:%s , 用户状态:%s , 用户昵称:%s , 个性签名:%s , 语言:%s , 角色 Role ID:%d ] - 错误:%s", "User_admin_edit_page_fail_over" => "编辑管理员 - Admin ID:%d - 错误:超出权限", "User_admin_edit_page_fail_close" => "编辑管理员 - Admin ID:%d - 错误:您不能关闭自己的账号", "User_admin_edit_page_success" => "编辑管理员 - Admin ID:%d - %s", "User_admin_delete_fail" => "删除管理员 - Admin ID:%d - 错误:%s", "User_admin_delete_fail_admin" => "删除管理员 - Admin ID:%d - 错误:不可能删除删除超管", "User_admin_delete_success" => "删除管理员 - Admin ID:%d - 成功", "User_admin_ban_success" => "停用管理员 - Admin ID:%d - 成功", "User_admin_ban_fail" => "停用管理员 - Admin ID:%d - 错误:%s", "User_admin_ban_fail_admin" => "停用管理员 - Admin ID:%d - 错误:不可能停用超级管理员", "User_admin_cancelBan_success" => "停用管理员 - Admin ID:%d - 成功", "User_admin_cancelBan_fail" => "停用管理员 - Admin ID:%d - 错误:%s", "UserManage_user_clientlist" => "查看客户列表 - 搜索关键用户名:%s,公司名:%s,邮箱:%s,手机号:%s,状态:%s", "UserManage_user_summary_fail" => "查看用户资料 - User ID:%d - 错误:%s", "UserManage_user_summary" => "查看用户资料 - User ID:%d", "UserManage_user_profile" => "进入用户资料修改页面 - User ID:%d", "UserManage_user_profilePost_success" => "修改用户资料#User ID:%d - %s", "UserManage_user_profilePost_success_home" => "修改用户资料", "UserManage_user_profilePost_fail" => "修改用户资料 - 修改前[User ID:%d ,
          客户名:%s , 密码:%s , 性别:%s ,头像:%s ,职业:%s ,个性签名:%s ,
          所在公司:%s ,邮件:%s ,国家:%s ,省份:%s ,城市:%s ,区:%s ,具体地址1:%s ,具体地址2:%s ,
          邮编:%s ,国际电话区号:%s ,电话:%s ,币种Currency ID:%d ,选择默认支付接口:%s ,备注:%s ,客户分组Group ID:%d ,状态:%s , 语言:%s ,了解途径:%s ,
          自定义字段:%s] - 修改后[客户名:%s , 密码:%s , 性别:%s ,头像:%s ,职业:%s ,个性签名:%s ,
          所在公司:%s ,邮件:%s ,国家:%s ,省份:%s ,城市:%s ,区:%s ,具体地址1:%s ,具体地址2:%s ,
          邮编:%s ,国际电话区号:%s ,电话:%s ,币种Currency ID:%d ,选择默认支付接口:%s ,备注:%s ,客户分组Group ID:%d ,状态:%s , 语言:%s ,了解途径:%s ,
          自定义字段:%s] - 错误:%s", "UserManage_user_profilePost_fail_email_common" => "修改用户资料 - User ID:%d - 错误:%s", "UserManage_user_profilePost_fail_email" => "修改用户资料 - 修改前[User ID:%d, 邮件:%s] - 修改后[邮件:%s] - 错误:邮箱已存在用户使用", "UserManage_user_profilePost_fail_phone" => "修改用户资料 - 修改前[User ID:%d, 电话:%s] - 修改后[电话:%s] - 错误:电话已存在用户使用", "UserManage_user_profilePost_fail_common" => "修改用户资料 - User ID:%d - 错误:%s", "UserManage_user_createClient" => "进入添加客户页面", "UserManage_user_createClientPost_success" => "添加客户(%s) - User ID:%d -成功", "UserManage_user_createClientPost_fail" => "添加客户页面 -错误：%s", "UserManage_user_createClientPost_fail_email" => "添加客户页面 -错误：邮箱已存在用户使用", "UserManage_user_createClientPost_fail_phone" => "添加客户页面 -错误：手机已存在用户使用", "UserManage_user_closeClient_success" => "关闭客户(%s) - User ID:%d", "UserManage_user_closeClient_fail" => "关闭客户 - User ID:%d -错误:%s", "UserManage_user_deleteClient_success" => "删除客户(%s) - User ID:%d -成功", "UserManage_user_deleteClient_fail" => "删除客户 - User ID:%d -工单相关删除(ticket_reply,ticket_note,ticket) -  资产记录删除(credit) - 日志记录删除(activity_log) - 客户删除(clients) - 实名认证删除(certifi_company,certifi_person)'.
              ' - 合同删除（contract_pdf）- 升降级删除(upgrades) - 用户自定义字段删除(customfields,customfieldsvalues) -错误:%s", "UserManage_user_cerify_list" => "查询认证列表 - 认证类型:%d", "UserManage_user_cerifyLogList" => "查询 实名认证日志列表 - 认证类型:%d - 状态:%d - User ID:%d", "UserManage_user_certifiPersonDetail" => "查看客户个人实名认证详情 -  User ID:%d", "UserManage_user_loginByUser_success" => "以该客户登录 -  User ID:%d - 成功", "UserManage_user_loginByUser_fail" => "以该客户登录 -  User ID:%d - 错误:%s", "UserManage_user_addUserInvoice_success" => "添加账单 -  User ID:%d - 账单Invoice ID:%d - 成功", "UserManage_user_addUserInvoice_fail" => "添加账单 -  User ID:%d - 错误:%s", "UserManage_user_userInvoice" => "查看用户账单列表 -  User ID:%d - 付款方式:%d - 付款状态:%d - 账单生成日:%s - 账单逾期日:%s - 账单支付日:%s - 按总计搜索(小值)%s - 按总计搜索(大值):%s", "Order_admin_index" => "查看用户订单列表 -  Order ID:%d - 客户名:%s - 开始时间:%s - 结束时间:%s - 订单号:%s - 金额:%s - 状态%s - 付款状态:%s", "Order_admin_check_success" => "订单审核 -  User ID:%d  -  Order ID:%d  - 成功", "Order_admin_check_fail" => "订单审核 -  Order ID:%d - 修改orders(status):%s - 修改host(domainstatus):%s - 错误:%s", "Order_admin_cancel_success" => "订单取消 -  User ID:%d  -  Order ID:%d  - 成功", "Order_admin_cancel_fail" => "订单取消 -  Order ID:%d - 修改orders(status):%s - 修改invoices(status):%s - 错误:%s", "Order_admin_delete_success" => "订单删除 -  User ID:%d  -  Order ID:%d  - 成功", "Order_admin_delete_fail" => "订单删除 -  Order ID:%d - orders记录删除 - host_config_options 记录删除 - host 记录删除 - invoice_items 记录删除 - invoices 记录删除 - 错误:%s", "Order_admin_createPage" => "进入创建订单页面", "Order_admin_setConfig" => "进入选择配置页面", "Order_admin_getMultiTotal_success" => "计算总价 - User ID:%d -小计:%s - 优惠折扣:%s - 总计:%s - 成功", "Order_admin_getMultiTotal_fail" => "计算总价 - User ID:%d - 错误:%s", "Order_admin_save_success" => "创建订单 - User ID:%d - 订单 Order ID:%d - 成功", "Order_admin_save_fail" => "创建订单 - User ID:%d - 错误:%s", "Order_admin_saveinvoice_success" => "创建账单 - User ID:%d  - 账单 Invoice ID:%d - 成功", "Order_admin_saveinvoice_fail" => "创建账单 - User ID:%d  - 错误:%s", "Order_admin_updateinvoice_status" => "修改账单状态 - User ID:%d - Invoice ID:%d -  status修改前:%s - 修改后:%s", "Order_admin_read" => "查看订单详情 - Order ID:%d", "Order_admin_notes" => "添加备注 - Order ID:%d - notes修改前:%s - 修改后:%s ", "Order_admin_active" => "Host状态审核通过 - Order ID:%d  - Host ID :%d - %s ", "Order_admin_active_home" => "Host状态审核通过 - Order ID:%d  - Host ID :%d", "Order_admin_ordersttaus" => "修改订单状态#Order ID:%d  - User ID:%d -%s", "Order_admin_ordersttaus_fail" => "修改订单状态 - Order ID:%d - 修改前[status:%s,时间:%s] - 修改后:[status:%s,时间:%s] - 错误:%s", "Order_admin_create_customPromo_success" => "创建定制优惠码 -promo code: -成功", "Order_admin_create_customPromo_fail" => "创建定制优惠码 -错误:%s", "Order_admin_products_updateqty" => "修改产品库存数量 - User ID:%d - Product ID:%d -  qty修改前:%s - 修改后:%s ", "Order_admin_promo_code_updateused" => "修改优惠码使用次数 - User ID:%d - Promo_codeID :%d -  used修改前:%s - 修改后:%s ", "Order_admin_clients_updatecredit_success" => "修改用户余额 - User ID:%d -  used修改前:%s - 修改后:%s -成功", "Order_admin_clients_updatecredit_fail" => "修改用户余额 - User ID:%d - 错误:余额不足", "Order_admin_clients_updatecreditlimit_fail" => "修改用户剩余信用额 - User ID:%d - 错误:剩余信用额不足", "Order_admin_clients_create_credit" => "创建用户资产信息 - User ID:%d - Create ID:%d - 金额 :%s", "ClientsServices_admin_index" => "查看后台用户产品服务内页 -用户id :%d - 产品id :%s - 产品/服务id :%s", "ClientsServices_admin_postInfo" => "修改的产品/服务 - User ID:%d - Host ID:%d，%s", "ClientsServices_admin_postTransfer" => "转移产品和服务 - 将 - Host ID:%d - 从 - User ID:%d - 移动到 User ID:%d - 成功", "ClientsServices_admin_postTransfer_suffer" => "接收到转移的产品和服务 - Host ID:%d - 来自 - User ID:%d", "ClientsServices_admin_postTransfer_fail" => "转移产品和服务 - \"将 - Host ID:%d - 从 - User ID:%d - 移动到 User ID:%d - 错误:用户相同，不能转移", "ClientsServices_admin_postTransfer_fail2" => "转移产品和服务 - \"将 - Host ID:%d - 从 - User ID:%d - 移动到 User ID:%d - 错误:产品未找到", "ClientsServices_admin_postTransfer_fail3" => "转移产品和服务 - \"将 - Host ID:%d - 从 - User ID:%d - 移动到 User ID:%d - 错误:接收用户不存在", "ClientsServices_admin_deleteHost_success" => " 删除产品和服务 - Host ID:%d - User ID:%d - Product ID:%d - 成功", "ClientsServices_admin_deleteHost_fail" => " 删除产品和服务 - Host ID:%d - User ID:%d - Product ID:%d - 错误:失败", "ClientsServices_admin_hostRenew" => " 生成续费账单成功 - User ID:%d - Host ID:%s - Invoice ID:%s ", "ClientsServices_admin_hostRenew_fail_invoice" => " 生成续费账单失败 - User ID:%d - Host ID:%d  - 续费账单:%s - 购买时长:%s - 金额:%s - 支付方式:%s - 成功", "ClientsServices_admin_hostRenew_delete_invoice" => " 删除原账单  - User ID:%d - Host ID:%d - (删除invoices,invoice_items记录)Invoice ID:%d -  - 续费账单:%s - 购买时长:%s - 金额:%s - 支付方式:%s - 成功", "ClientsServices_admin_hostRenew_refund_clients" => " 已支付金额小于新账单续费金额,退款至余额  - User ID:%d - 修改前余额:%s - 修改后余额:%s -Invoice ID:%d", "ClientsServices_admin_hostRenew_refund_clients1" => " 已支付金额大于新账单续费金额,退款多余金额至余额  - User ID:%d - 修改前余额:%s - 修改后余额:%s -Invoice ID:%d", "ClientsServices_admin_hostRenew_unsuspend_success" => "解除暂停 -  Host ID:%d  - 修改前domainstatus:%s - 修改后domainstatus:%s", "ClientsServices_admin_hostRenew_unsuspend_fail" => "解除暂停 -  Host ID:%d - 错误:%s", "ClientsServices_admin_hostRenew_updatehost_nextduedate" => "变更产品到期时间 - User ID:%d -  Host ID:%d - 变更前[续费金额:%s,付款周期:%s,到期时间:%s,下次生成账单时间:%s] - 变更后[续费金额:%s,付款周期:%s,到期时间:%s,下次生成账单时间:%s]", "ClientsServices_admin_hostRenew_updateinvoices_status" => " 改变账单状态- User ID:%d - Invoice ID:%d - 变更前[状态:%s,到期时间:%s,支付时间:%s] - 变更后[状态:%s,到期时间:%s,支付时间:%s]", "ClientsServices_admin_hostRenew_calculatedPrice" => "计算新周期续费金额 - Host ID:%d - User ID:%d - 续费周期:%s - 金额:%s", "ClientsServices_admin_hostRenew_fail_1" => " 产品续费 - Host ID:%d - User ID:%d - 错误:生成新续费账单失败", "ClientsServices_admin_hostRenew_fail_2" => " 产品续费 - User ID:%d - Host ID:%d - 续费周期:%s - Currency ID:%d -  错误:续费周期无效", "ClientsServices_admin_hostRenew_fail_3" => " 产品续费 -  错误:产品状态必须是已激活或已暂停", "ClientsServices_admin_postSearchClient" => "搜索用户 -关键字 :%s", "Host_admin_getList" => "查看产品/服务列表页数据 -搜索字段  - 产品类型:%s - 服务器id:%s - 产品id:%s - 支付方式:%s - 付款周期:%s - 主机状态:%s", "Invoice_admin_paid" => "标记账单为已支付 - User ID:%d - Invoice ID:%d- 生成交易流水(accounts):%s", "Invoice_admin_unpaid" => "标记账单为未支付 - User ID:%d - Invoice ID:%d ", "Invoice_admin_cancelled" => "标记账单为被取消 - User ID:%d - Invoice ID:%d", "Invoice_admin_addItem" => "增加账单项目 - User ID:%d - Invoice ID:%d - 账单项id#%s", "Invoice_admin_editItem" => "修改账单项目  - Invoice ID:%d - 账单项id#%s - %s", "Invoice_admin_delete" => "删除账单 - User ID:%d - Invoice ID:%d", "Invoice_admin_duplicate" => "复制账单 - User ID:%d - Invoice ID:%d", "Invoice_admin_addPay" => "新增付款账单 - User ID:%d - Invoice ID:%d", "Invoice_admin_option" => "选项页面提交option - User ID:%d - Invoice ID:%d", "Invoice_admin_addPayInvoice" => "添加付款金额到账单 - User ID:%d - Invoice ID:%d", "Invoice_admin_deletePayInvoice" => "从账单中删除付款金额 - User ID:%d - Invoice ID:%d", "Invoice_admin_refund" => "账单退款 - User ID:%d - Invoice ID:%d - 金额: %s", "Invoice_admin_notes" => "账单备注 - User ID:%d - Invoice ID:%d - %s 修改为- %s", "Invoice_admin_deleteItems" => "账单项目ID#%d删除 - User ID:%d - Invoice ID:%d", "Invoice_admin_delAccount" => "删除账单流水 - User ID:%d - Invoice ID:%d", "Invoice_admin_renewHandle" => "续费产品 - User ID:%d - Invoice ID:%d", "Ticket_admin_add" => " 新建工单 - 用户id#User ID:%d - 工单id#Ticket ID:%d - 标题:%s", "Ticket_admin_add1" => " 新建工单 - 工单id#Ticket ID:%d - 标题:%s", "Ticket_admin_reply" => " 回复工单 - 用户id#User ID:%d - 工单id#Ticket ID:%d - 标题:%s", "Ticket_admin_saveReply" => " 编辑工单回复工单 - 用户id#User ID:%d - 工单id#Ticket ID:%d - 标题:%s", "Ticket_admin_mergeTicket" => "合并工单 - 用户id#User ID:%d - 工单id#Ticket ID:%d", "Ticket_admin_closeTicket" => "修正工单 - 用户id#User ID:%d - 工单id#Ticket ID:%d - 状态:%s", "Ticket_admin_deleteTicket" => "删除工单- 用户id#User ID:%d - 工单id#Ticket ID:%d - 标题:%s", "Ticket_admin_addNote" => "添加工单备注- 用户id#User ID:%d - 工单id#Ticket ID:%d - 标题:%s", "Ticket_admin_deleteNote" => "删除工单备注- 用户id#User ID:%d - 工单id#Ticket ID:%d - 标题:%s", "Ticket_admin_deleteReply" => "删除工单回复- 用户id#User ID:%d - 工单id#Ticket ID:%d - 标题:%s", "Ticket_admin_saveTicket" => "修改工单- 用户id#User ID:%d - 工单id#Ticket ID:%d - 标题:%s - %s", "Dcim_admin_addServer" => "添加服务器 - Service ID:%d", "Dcim_admin_editServer" => "修改服务器 - Service ID:%d - %s ", "Dcim_admin_delServer" => "删除服务器 - Service ID:%d", "Dcim_admin_delRecord" => "删除购买记录 - Service ID:%d", "Dcim_admin_addFlowPacket" => "添加流量包 -FlowPacket ID:%d", "Dcim_admin_editFlowPacket" => "修改流量包 - FlowPacket ID:%d - %s", "Dcim_admin_delFlowPacket" => "删除流量包 - FlowPacket ID:%d", "Account_admin_add" => "添加交易流水#ID:%d - 用户id User ID:%d - 账单id Invoice ID:%d - 交易流水id Transaction ID:%s", "Account_admin_update" => "修改交易流水#ID:%d - 用户id User ID:%d - 账单id Invoice ID:%d - %s", "Account_admin_delete" => "删除交易流水#ID:%d - 用户id User ID:%d - 账单id Invoice ID:%d - 交易流水id Transaction ID:%s", "Plugin_admin_plInstall" => "安装%s接口", "Plugin_admin_plUninstall" => "卸载%s接口", "Plugin_admin_plToggle_open" => "开启%s接口", "Plugin_admin_plToggle_close" => "禁用%s接口", "Plugin_admin_plSetting" => "修改%s接口 - %s", "News_admin_postaddContent" => "添加新闻#News ID:%d 标题:%s", "News_admin_postEditContent" => "修改新闻#News ID:%d - %s", "News_admin_delete" => "删除新闻#News ID:%d  标题:%s", "News_admin_postaddCat" => "添加分类#%d", "News_admin_postEditCat1" => "修改分类#%d - 分类名称修改前:%s - 修改后:%s - 隐藏", "News_admin_postEditCat2" => "修改分类#%d - 分类名称修改前:%s - 修改后:%s - 不隐藏", "News_admin_deletecat" => "删除分类#%d", "TicketDepartment_admin_add" => "添加工单部门#TD ID:%d -  名称:%s", "TicketDepartment_admin_save" => "修改工单部门#TD ID:%d - %s", "TicketDeliver_admin_add" => "添加工单传递规则#TD ID:%d", "TicketDeliver_admin_save" => "修改工单传递规则#TD ID:%d - %s", "TicketDepartmentStatus_admin_save" => "修改工单状态成功#ID:%d - 标题%s - %s", "Product_admin_saveProductGroup" => "保存产品分组#ProductGroup ID:%d，%s", "Product_admin_saveProductGroup_add" => "添加产品分组:%s - ProductGroup ID:%d", "Product_admin_delete" => "删除产品:%s - Product ID:%d", "Product_admin_deleteGroup" => "删除产品分组:%s - ProductGroup ID:%d", "Product_admin_create" => "添加产品:%s - Product ID:%d", "Product_admin_edit" => "修改产品#Product ID:%d，%s", "Product_admin_duplicate" => "复制产品#Product ID:%d 产品名:%s", "Configserver_admin_addServersPost" => "添加服务器#Service ID:%d", "Configserver_admin_editServersPost" => "修改服务器#Service ID:%d - %s", "Configserver_admin_delete" => "删除服务器#Service ID:%d", "Configserver_admin_createGroupsPost" => "创建服务器组#%d", "Configserver_admin_editServerGroupsPost" => "修改服务器组#%d - %s", "Configserver_admin_deleteServerGroups" => "删除服务器组#%d", "Configoption_admin_createGroupsPost" => "创建可选项配置组#%d", "Configoption_admin_editGroupsPost" => "编辑可配置选项组#%d %s", "Configoption_admin_add" => "添加可配置选项组#PCG ID:%d", "Configoption_admin_delete" => "删除可配置选项组#PCG ID:%d", "Configoption_admin_duplicateGroupsPost" => "复制可配置选项组#PCG ID:%d", "Configoption_admin_editConfigPost" => "编辑可配置项#PCG ID:%d %s", "ClientGroup_admin_add" => "添加客户分组:%s-分组ID#%d", "ClientGroup_admin_update" => "修改客户分组:分组ID#%d，%s", "ClientGroup_admin_delete" => "删除客户分组:%s-分组ID#%d", "Set_admin_postCustomFields_add" => "%s", "Set_admin_postCustomFields_edit" => "%s", "Set_admin_postCustomFields_delete" => "删除自定义字段#%d", "Currency_admin_addCurrency" => "添加货币种类#Currency ID:%d", "Currency_admin_deleteCurrency" => "删除货币种类#Currency ID:%d", "Currency_admin_updateCurrency" => "编辑货币种类#Currency ID:%d %s", "Currency_admin_default" => "设为默认货币#Currency ID:%d", "Currency_admin_updateRate" => "汇率更新", "Currency_admin_updatePrice" => "价格更新", "ConfigCer_admin_update" => "修改实名认证设置:%s", "PROMO_CODE_EDIT_SUCCESS" => "促销优惠码修改%d- %s", "System_admin_postOptimizeTables" => "优化数据库表", "System_admin_postDownDataBackup" => "下载数据库备份", "ConfigGen_admin_postGeneral" => "常规设置更改 %s", "ConfigGen_admin_postRecharge" => "支付设置更改 %s", "Rabc_admin_addRole" => " 添加角色#Role ID:%s", "Rabc_admin_editRole" => "角色编辑#Role ID:%d %s", "Rabc_admin_deleteRole" => "角色删除#Role ID:%d", "Rabcpage_admin_addRole" => " 添加页面#%s", "Rabcpage_admin_editRole" => "页面编辑#%d %s", "Rabcpage_admin_deleteRole" => "页面删除#%d", "Cron_home_editCron" => "自动任务编辑 %s", "ConfigMessage_admin_editCron" => "短信设置 %s", "ConfigGen_admin_emailIndexPost" => "邮件设置 %s", "Downloads_admin_editCron" => "文件下载编辑 %s", "EmailTem_admin_createTemplatePost" => "创建邮件模板 %s", "EmailTem_admin_add" => "添加语言#%s", "EmailTem_admin_disabled" => "禁用语言 %s", "EmailTem_admin_delete" => "删除邮件模板#%s", "EmailTem_admin_edit" => "编辑邮件模板#%s %s", "ConfigMessage_admin_update" => "更新短信模板审核状态#%s %s", "ConfigMessage_admin_create" => "创建短信模板#%d", "ConfigMessage_admin_edit" => "更新短信模板#%d", "ConfigMessage_admin_delete" => "删除短信模板#%d", "ConfigMessage_admin_SetSmsTemplatePost" => "发送设置修改id#%d - 改成邮件模板id#%s %s", "ConfigMessage_admin_SetSmsTemplateadd" => "设置修改添加#%d", "Sale_admin_addSalegroup" => "添加销售产品分组#%d", "Sale_admin_editSalegroup" => "修改销售产品分组#%d - %s", "Sale_admin_delSalegroup" => "删除销售产品分组#%d", "Sale_admin_addSaleladder" => "添加阶梯设置#%d", "Sale_admin_editSaleladder" => "修改阶梯设置#%d - %s", "Sale_admin_delSaleladder" => "删除阶梯设置#%d", "Aff_admin_aff" => "推介计划设置修改，%s", "Aff_admin_addSaleladder" => "添加推荐阶梯设置#ID:%d", "Aff_admin_editSaleladder" => "修改推荐阶梯设置#ID:%d - %s", "Aff_admin_delSaleladder" => "删除推荐阶梯设置#ID:%d", "Aff_admin_editAdminList" => "修改用户销售设置- User ID:%d - %s", "Aff_admin_useraffiPost" => "修改用户推荐设置#ID:%d - %s", "Aff_admin_useraffiAdd" => "添加用户推荐设置#ID:%d", "Aff_admin_productaffiPost" => "修改产品推荐设置#ID:%d，%s", "Aff_admin_productaffiAdd" => "添加产品:%s-推荐设置#ID:%d", "Aff_admin_useraffibalance" => "修改用户推荐金额#%d - %s", "Aff_admin_withdrawsh" => "提现审核通过#%d - User ID:%d -  提现方式- %s", "Aff_admin_withdrawsh_no" => "提现审核不通过#%d - User ID:%d - 原因: %s", "Aff_cron_update_balance" => "更新用户可提现余额 - User ID:%d - 余额: %s 改为 :%s", "Aff_cron_update_audited_balance" => "更新用户待确认余额 - User ID:%d - 余额: %s 改为 :%s", "Aff_cron_update_payamount" => "更新用户待订购数量 - User ID:%d - 数量改为 :%s", "Product_admin_addProductgroup" => "添加产品分组%s - #ID:%d", "Product_admin_editProductgroup" => "修改产品分组#ID:%d，%s", "Product_admin_delProductgroup" => "删除产品分组%s - #ID:%d", "Product_admin_UserProductgroup" => "添加产品分组金额#ID:%d，客户组#ID:%d，产品组#ID:%d", "Product_admin_UserProductgroupedit" => "修改产品分组金额#ID:%d，%s，客户组#ID:%d，产品组#ID:%d", "Ur_admin_add" => "添加上游#%d", "Ur_admin_edit" => "修改上游#%d - %s", "Ur_admin_del" => "删除上游#%d", "Ur_admin_addupper" => "添加资源配置#%d", "Ur_admin_editupper" => "修改资源配置#%d - %s", "Ur_admin_delupper" => "删除资源配置#%d", "ConfigGen_admin_delpg" => "删除产品分组#%d", "ConfigGen_admin_addpg" => "添加产品分组#%d", "ConfigGen_admin_editpg" => "修改产品分组#%d - %s", "UserManage_user_openClient_success" => "开启客户(%s) - User ID:%d", "Download_admin_createcates" => "添加分类#ID:%d - 分类名:%s", "Download_admin_delcates" => "删除分类#ID:%d - 分类名:%s", "Download_admin_updatecates" => "修改分类#ID:%d，%s", "Download_admin_postaddfile" => "添加文件#ID:%d - 文件名:%s", "Download_admin_deletefile" => "删除文件#ID:%d - 文件名:%s", "Download_admin_postsavefile" => "修改文件#ID:%d，%s", "Download_admin_postadduserfile" => "添加用户附件#ID:%d - 附件名:%s", "Download_admin_deleteuserfile" => "删除用户附件#ID:%d - 附件名:%s", "Download_admin_postsaveuserfile" => "修改用户附件#ID:%d，%s", "Download_admin_postupdatesort" => "分类#ID:%d，%s", "Usermanage_del_reason" => " 删除请求原因#ID:%d - 原因:%s", "Usermanage_add_reason" => " 取消请求原因增加#ID:%d - 原因:%s", "Usermanage_edit_reason" => " 取消请求原因修改#ID:%d，%s", "Contract_setting" => "合同基础设置:%s"], "en-us" => ["refund_invoice" => "Credit from Refund of Invoice ID %d", "recharge_ok" => "recharge ok of Invoice ID %d", "User_admin_list" => "查看管理员列表 - 搜索关键字:%s", "User_admin_addpage" => "进入管理员添加页面", "User_admin_create_success" => "管理员添加 - Admin ID:%d - 成功", "User_admin_create_fail" => "管理员添加 - 失败", "User_admin_edit_page" => "进入管理员编辑页面 - Admin ID:%d", "User_admin_edit_page_fail" => "编辑管理员 - Admin ID:%d -修改前[ 用户名:%s , 用户邮箱:%s , 用户类型:%s , 用户状态:%s , 用户昵称:%s , 个性签名:%s , 语言:%s , 角色 Role ID:%d ] - 修改后[  用户名:%s , 用户邮箱:%s , 用户类型:%s , 用户状态:%s , 用户昵称:%s , 个性签名:%s , 语言:%s , 角色 Role ID:%d ] - 错误:%s", "User_admin_edit_page_fail_over" => "编辑管理员 - Admin ID:%d - 错误:超出权限", "User_admin_edit_page_fail_close" => "编辑管理员 - Admin ID:%d - 错误:您不能关闭自己的账号", "User_admin_edit_page_success" => "编辑管理员 - Admin ID:%d - %s", "User_admin_delete_fail" => "删除管理员 - Admin ID:%d - 错误:%s", "User_admin_delete_fail_admin" => "删除管理员 - Admin ID:%d - 错误:不可能删除删除超管", "User_admin_delete_success" => "删除管理员 - Admin ID:%d - 成功", "User_admin_ban_success" => "停用管理员 - Admin ID:%d - 成功", "User_admin_ban_fail" => "停用管理员 - Admin ID:%d - 错误:%s", "User_admin_ban_fail_admin" => "停用管理员 - Admin ID:%d - 错误:不可能停用超级管理员", "User_admin_cancelBan_success" => "停用管理员 - Admin ID:%d - 成功", "User_admin_cancelBan_fail" => "停用管理员 - Admin ID:%d - 错误:%s", "UserManage_user_clientlist" => "查看客户列表 - 搜索关键用户名:%s,公司名:%s,邮箱:%s,手机号:%s,状态:%s", "UserManage_user_summary_fail" => "查看用户资料 - User ID:%d - 错误:%s", "UserManage_user_summary" => "查看用户资料 - User ID:%d", "UserManage_user_profile" => "进入用户资料修改页面 - User ID:%d", "UserManage_user_profilePost_success" => "修改用户资料#User ID:%d - %s", "UserManage_user_profilePost_success_home" => "修改用户资料", "UserManage_user_profilePost_fail" => "修改用户资料 - 修改前[User ID:%d ,
          客户名:%s , 密码:%s , 性别:%s ,头像:%s ,职业:%s ,个性签名:%s ,
          所在公司:%s ,邮件:%s ,国家:%s ,省份:%s ,城市:%s ,区:%s ,具体地址1:%s ,具体地址2:%s ,
          邮编:%s ,国际电话区号:%s ,电话:%s ,币种Currency ID:%d ,选择默认支付接口:%s ,备注:%s ,客户分组Group ID:%d ,状态:%s , 语言:%s ,了解途径:%s ,
          自定义字段:%s] - 修改后[客户名:%s , 密码:%s , 性别:%s ,头像:%s ,职业:%s ,个性签名:%s ,
          所在公司:%s ,邮件:%s ,国家:%s ,省份:%s ,城市:%s ,区:%s ,具体地址1:%s ,具体地址2:%s ,
          邮编:%s ,国际电话区号:%s ,电话:%s ,币种Currency ID:%d ,选择默认支付接口:%s ,备注:%s ,客户分组Group ID:%d ,状态:%s , 语言:%s ,了解途径:%s ,
          自定义字段:%s] - 错误:%s", "UserManage_user_profilePost_fail_email_common" => "修改用户资料 - User ID:%d - 错误:%s", "UserManage_user_profilePost_fail_email" => "修改用户资料 - 修改前[User ID:%d, 邮件:%s] - 修改后[邮件:%s] - 错误:邮箱已存在用户使用", "UserManage_user_profilePost_fail_phone" => "修改用户资料 - 修改前[User ID:%d, 电话:%s] - 修改后[电话:%s] - 错误:电话已存在用户使用", "UserManage_user_profilePost_fail_common" => "修改用户资料 - User ID:%d - 错误:%s", "UserManage_user_createClient" => "进入添加客户页面", "UserManage_user_createClientPost_success" => "添加客户(%s) - User ID:%d -成功", "UserManage_user_createClientPost_fail" => "添加客户页面 -错误：%s", "UserManage_user_createClientPost_fail_email" => "添加客户页面 -错误：邮箱已存在用户使用", "UserManage_user_createClientPost_fail_phone" => "添加客户页面 -错误：手机已存在用户使用", "UserManage_user_closeClient_success" => "关闭客户(%s) - User ID:%d", "UserManage_user_closeClient_fail" => "关闭客户 - User ID:%d -错误:%s", "UserManage_user_deleteClient_success" => "删除客户(%s) - User ID:%d -成功", "UserManage_user_deleteClient_fail" => "删除客户 - User ID:%d -工单相关删除(ticket_reply,ticket_note,ticket) -  资产记录删除(credit) - 日志记录删除(activity_log) - 客户删除(clients) - 实名认证删除(certifi_company,certifi_person)'.
              ' - 合同删除（contract_pdf）- 升降级删除(upgrades) - 用户自定义字段删除(customfields,customfieldsvalues) -错误:%s", "UserManage_user_cerify_list" => "查询认证列表 - 认证类型:%d", "UserManage_user_cerifyLogList" => "查询 实名认证日志列表 - 认证类型:%d - 状态:%d - User ID:%d", "UserManage_user_certifiPersonDetail" => "查看客户个人实名认证详情 -  User ID:%d", "UserManage_user_loginByUser_success" => "以该客户登录 -  User ID:%d - 成功", "UserManage_user_loginByUser_fail" => "以该客户登录 -  User ID:%d - 错误:%s", "UserManage_user_addUserInvoice_success" => "添加账单 -  User ID:%d - 账单Invoice ID:%d - 成功", "UserManage_user_addUserInvoice_fail" => "添加账单 -  User ID:%d - 错误:%s", "UserManage_user_userInvoice" => "查看用户账单列表 -  User ID:%d - 付款方式:%d - 付款状态:%d - 账单生成日:%s - 账单逾期日:%s - 账单支付日:%s - 按总计搜索(小值)%s - 按总计搜索(大值):%s", "Order_admin_index" => "查看用户订单列表 -  Order ID:%d - 客户名:%s - 开始时间:%s - 结束时间:%s - 订单号:%s - 金额:%s - 状态%s - 付款状态:%s", "Order_admin_check_success" => "订单审核 -  User ID:%d  -  Order ID:%d  - 成功", "Order_admin_check_fail" => "订单审核 -  Order ID:%d - 修改orders(status):%s - 修改host(domainstatus):%s - 错误:%s", "Order_admin_cancel_success" => "订单取消 -  User ID:%d  -  Order ID:%d  - 成功", "Order_admin_cancel_fail" => "订单取消 -  Order ID:%d - 修改orders(status):%s - 修改invoices(status):%s - 错误:%s", "Order_admin_delete_success" => "订单删除 -  User ID:%d  -  Order ID:%d  - 成功", "Order_admin_delete_fail" => "订单删除 -  Order ID:%d - orders记录删除 - host_config_options 记录删除 - host 记录删除 - invoice_items 记录删除 - invoices 记录删除 - 错误:%s", "Order_admin_createPage" => "进入创建订单页面", "Order_admin_setConfig" => "进入选择配置页面", "Order_admin_getMultiTotal_success" => "计算总价 - User ID:%d -小计:%s - 优惠折扣:%s - 总计:%s - 成功", "Order_admin_getMultiTotal_fail" => "计算总价 - User ID:%d - 错误:%s", "Order_admin_save_success" => "创建订单 - User ID:%d - 订单 Order ID:%d - 成功", "Order_admin_save_fail" => "创建订单 - User ID:%d - 错误:%s", "Order_admin_saveinvoice_success" => "创建账单 - User ID:%d  - 账单 Invoice ID:%d - 成功", "Order_admin_saveinvoice_fail" => "创建账单 - User ID:%d  - 错误:%s", "Order_admin_updateinvoice_status" => "修改账单状态 - User ID:%d - Invoice ID:%d -  status修改前:%s - 修改后:%s", "Order_admin_read" => "查看订单详情 - Order ID:%d", "Order_admin_notes" => "添加备注 - Order ID:%d - notes修改前:%s - 修改后:%s ", "Order_admin_active" => "Host状态审核通过 - Order ID:%d  - Host ID:%d - %s ", "Order_admin_active_home" => "Host状态审核通过 - Order ID:%d  - Host ID:%d", "Order_admin_ordersttaus" => "修改订单状态#Order ID:%d  - User ID:%d -%s", "Order_admin_ordersttaus_fail" => "修改订单状态 - Order ID:%d - 修改前[status:%s,时间:%s] - 修改后:[status:%s,时间:%s] - 错误:%s", "Order_admin_create_customPromo_success" => "创建定制优惠码 -promo code: -成功", "Order_admin_create_customPromo_fail" => "创建定制优惠码 -错误:%s", "Order_admin_products_updateqty" => "修改产品库存数量 - User ID:%d - Product ID:%d -  qty修改前:%s - 修改后:%s ", "Order_admin_promo_code_updateused" => "修改优惠码使用次数 - User ID:%d - Promo_codeID :%d -  used修改前:%s - 修改后:%s ", "Order_admin_clients_updatecredit_success" => "修改用户余额 - User ID:%d -  used修改前:%s - 修改后:%s -成功", "Order_admin_clients_updatecredit_fail" => "修改用户余额 - User ID:%d - 错误:余额不足", "Order_admin_clients_updatecreditlimit_fail" => "修改用户剩余信用额 - User ID:%d - 错误:剩余信用额不足", "Order_admin_clients_create_credit" => "创建用户资产信息 - User ID:%d - Create ID:%d - 金额 :%s", "ClientsServices_admin_index" => "查看后台用户产品服务内页 -用户id :%d - 产品id :%s - 产品/服务id :%s", "ClientsServices_admin_postInfo" => "修改的产品/服务 - User ID:%d - Host ID:%d - 内容:%s", "ClientsServices_admin_postTransfer" => "转移产品和服务 - 将 - Host ID:%d - 从 - User ID:%d - 移动到 User ID:%d - 成功", "ClientsServices_admin_postTransfer_suffer" => "接收到转移的产品和服务 - Host ID:%d - 来自 - User ID:%d", "ClientsServices_admin_postTransfer_fail" => "转移产品和服务 - \"将 - Host ID:%d - 从 - User ID:%d - 移动到 User ID:%d - 错误:用户相同，不能转移", "ClientsServices_admin_postTransfer_fail2" => "转移产品和服务 - \"将 - Host ID:%d - 从 - User ID:%d - 移动到 User ID:%d - 错误:产品未找到", "ClientsServices_admin_postTransfer_fail3" => "转移产品和服务 - \"将 - Host ID:%d - 从 - User ID:%d - 移动到 User ID:%d - 错误:接收用户不存在", "ClientsServices_admin_deleteHost_success" => " 删除产品和服务 - Host ID:%d - User ID:%d - Product ID:%d - 成功", "ClientsServices_admin_deleteHost_fail" => " 删除产品和服务 - Host ID:%d - User ID:%d - Product ID:%d - 错误:失败", "ClientsServices_admin_hostRenew" => " 生成续费账单成功 - User ID:%d - Host ID:%s - Invoice ID:%s ", "ClientsServices_admin_hostRenew_fail_invoice" => " 生成续费账单失败 - User ID:%d - Host ID:%d  - 续费账单:%s - 购买时长:%s - 金额:%s - 支付方式:%s - 成功", "ClientsServices_admin_hostRenew_delete_invoice" => " 删除原账单  - User ID:%d - Host ID:%d - (删除invoices,invoice_items记录)Invoice ID:%d -  - 续费账单:%s - 购买时长:%s - 金额:%s - 支付方式:%s - 成功", "ClientsServices_admin_hostRenew_refund_clients" => " 已支付金额小于新账单续费金额,退款至余额  - User ID:%d - 修改前余额:%s - 修改后余额:%s -Invoice ID:%d", "ClientsServices_admin_hostRenew_refund_clients1" => " 已支付金额大于新账单续费金额,退款多余金额至余额  - User ID:%d - 修改前余额:%s - 修改后余额:%s -Invoice ID:%d", "ClientsServices_admin_hostRenew_unsuspend_success" => "解除暂停 -  Host ID:%d  - 修改前domainstatus:%s - 修改后domainstatus:%s", "ClientsServices_admin_hostRenew_unsuspend_fail" => "解除暂停 -  Host ID:%d - 错误:%s", "ClientsServices_admin_hostRenew_updatehost_nextduedate" => "变更产品到期时间 - User ID:%d -  Host ID:%d - 变更前[续费金额:%s,付款周期:%s,到期时间:%s,下次生成账单时间:%s] - 变更后[续费金额:%s,付款周期:%s,到期时间:%s,下次生成账单时间:%s]", "ClientsServices_admin_hostRenew_updateinvoices_status" => " 改变账单状态- User ID:%d - Invoice ID:%d - 变更前[状态:%s,到期时间:%s,支付时间:%s] - 变更后[状态:%s,到期时间:%s,支付时间:%s]", "ClientsServices_admin_hostRenew_calculatedPrice" => "计算新周期续费金额 - Host ID:%d - User ID:%d - 续费周期:%s - 金额:%s", "ClientsServices_admin_hostRenew_fail_1" => " 产品续费 - Host ID:%d - User ID:%d - 错误:生成新续费账单失败", "ClientsServices_admin_hostRenew_fail_2" => " 产品续费 - User ID:%d - Host ID:%d - 续费周期:%s - Currency ID:%d -  错误:续费周期无效", "ClientsServices_admin_hostRenew_fail_3" => " 产品续费 -  错误:产品状态必须是已激活或已暂停", "ClientsServices_admin_postSearchClient" => "搜索用户 -关键字 :%s", "Host_admin_getList" => "查看产品/服务列表页数据 -搜索字段  - 产品类型:%s - 服务器id:%s - 产品id:%s - 支付方式:%s - 付款周期:%s - 主机状态:%s", "Invoice_admin_paid" => "标记账单为已支付 - User ID:%d - Invoice ID:%d- 生成交易流水(accounts):%s", "Invoice_admin_unpaid" => "标记账单为未支付 - User ID:%d - Invoice ID:%d ", "Invoice_admin_cancelled" => "标记账单为被取消 - User ID:%d - Invoice ID:%d", "Invoice_admin_addItem" => "增加账单项目 - User ID:%d - Invoice ID:%d - 账单项id#%s", "Invoice_admin_editItem" => "修改账单项目  - Invoice ID:%d - 账单项id#%s - %s", "Invoice_admin_delete" => "删除账单 - User ID:%d - Invoice ID:%d", "Invoice_admin_duplicate" => "复制账单 - User ID:%d - Invoice ID:%d", "Invoice_admin_addPay" => "新增付款账单 - User ID:%d - Invoice ID:%d", "Invoice_admin_option" => "选项页面提交option - User ID:%d - Invoice ID:%d", "Invoice_admin_addPayInvoice" => "添加付款金额到账单 - User ID:%d - Invoice ID:%d", "Invoice_admin_deletePayInvoice" => "从账单中删除付款金额 - User ID:%d - Invoice ID:%d", "Invoice_admin_refund" => "账单退款 - User ID:%d - Invoice ID:%d - 金额: %s", "Invoice_admin_notes" => "账单备注 - User ID:%d - Invoice ID:%d - %s 修改为- %s", "Invoice_admin_deleteItems" => "账单项目ID#%d删除 - User ID:%d - Invoice ID:%d", "Invoice_admin_delAccount" => "删除账单流水 - User ID:%d - Invoice ID:%d", "Invoice_admin_renewHandle" => "续费产品 - User ID:%d - Invoice ID:%d", "Ticket_admin_add" => " 新建工单 - 用户id#User ID:%d - 工单id#Ticket ID:%d - 标题:%s", "Ticket_admin_add1" => " 新建工单 - 工单id#Ticket ID:%d - 标题:%s", "Ticket_admin_saveReply" => " 编辑工单回复工单 - User ID:%d - Ticket ID:%d - 标题:%s", "Ticket_admin_mergeTicket" => "合并工单 - User ID:%d - Ticket ID:%d", "Ticket_admin_closeTicket" => "修正工单 - User ID:%d - Ticket ID:%d - 状态:%s", "Ticket_admin_deleteTicket" => "删除工单- User ID:%d - Ticket ID:%d", "Ticket_admin_addNote" => "添加工单备注- User ID:%d - Ticket ID:%d - 标题:%s", "Ticket_admin_deleteNote" => "删除工单备注- User ID:%d - Ticket ID:%d", "Ticket_admin_deleteReply" => "删除工单回复- User ID:%d - Ticket ID:%d", "Ticket_admin_saveTicket" => "修改工单- User ID:%d - Ticket ID:%d - %s", "Dcim_admin_addServer" => "添加服务器 - Service ID:%d", "Dcim_admin_editServer" => "修改服务器 - Service ID:%d - %s ", "Dcim_admin_delServer" => "删除服务器 - Service ID:%d", "Dcim_admin_delRecord" => "删除购买记录 - Service ID:%d", "Dcim_admin_addFlowPacket" => "添加流量包 -FlowPacket ID:%d", "Dcim_admin_editFlowPacket" => "修改流量包 - FlowPacket ID:%d - %s", "Dcim_admin_delFlowPacket" => "删除流量包 - FlowPacket ID:%d", "Account_admin_add" => "添加交易流水#ID:%d - 用户id User ID:%d - 账单id Invoice ID:%d - 交易流水id Transaction ID:%s", "Account_admin_update" => "修改交易流水#ID:%d - 用户id User ID:%d - 账单id Invoice ID:%d - %s", "Account_admin_delete" => "删除交易流水#ID:%d - 用户id User ID:%d - 账单id Invoice ID:%d - 交易流水id Transaction ID:%s", "Plugin_admin_plInstall" => "安装%s接口", "Plugin_admin_plUninstall" => "卸载%s接口", "Plugin_admin_plToggle_open" => "开启%s接口", "Plugin_admin_plToggle_close" => "禁用%s接口", "Plugin_admin_plSetting" => "修改%s接口 - %s", "News_admin_postaddContent" => "添加新闻#News ID:%d 标题:%s", "News_admin_postEditContent" => "修改新闻#News ID:%d - %s", "News_admin_delete" => "删除新闻#News ID:%d  标题:%s", "News_admin_postaddCat" => "添加分类#%d", "News_admin_postEditCat1" => "修改分类#%d - 分类名称修改前:%s - 修改后:%s - 隐藏", "News_admin_postEditCat2" => "修改分类#%d - 分类名称修改前:%s - 修改后:%s - 不隐藏", "News_admin_deletecat" => "删除分类#%d", "TicketDepartment_admin_add" => "添加工单部门#TD ID:%d -  名称:%s", "TicketDepartment_admin_save" => "修改工单部门#TD ID:%d - %s", "TicketDepartmentStatus_admin_save" => "修改工单状态成功#%d - %s", "Product_admin_saveProductGroup" => "保存产品分组#ProductGroup ID:%d，%s", "Product_admin_saveProductGroup_add" => "添加产品分组:%s - ProductGroup ID:%d", "Product_admin_delete" => "删除产品:%s - Product ID:%d", "Product_admin_deleteGroup" => "删除产品分组:%s - ProductGroup ID:%d", "Product_admin_create" => "添加产品:%s - Product ID:%d", "Product_admin_edit" => "修改产品#Product ID:%d，%s", "Product_admin_duplicate" => "复制产品#Product ID:%d 产品名:%s", "Configserver_admin_addServersPost" => "添加服务器#Service ID:%d", "Configserver_admin_editServersPost" => "修改服务器#Service ID:%d - %s", "Configserver_admin_delete" => "删除服务器#Service ID:%d", "Configserver_admin_createGroupsPost" => "创建服务器组#%d", "Configserver_admin_editServerGroupsPost" => "修改服务器组#%d - %s", "Configserver_admin_deleteServerGroups" => "删除服务器组#%d", "Configoption_admin_createGroupsPost" => "创建可选项配置组#%d", "Configoption_admin_editGroupsPost" => "编辑可配置选项组#%d %s", "Configoption_admin_add" => "添加可配置选项组#PCG ID:%d", "Configoption_admin_delete" => "删除可配置选项组#PCG ID:%d", "Configoption_admin_duplicateGroupsPost" => "复制可配置选项组#PCG ID:%d", "Configoption_admin_editConfigPost" => "编辑可配置项#PCG ID:%d %s", "ClientGroup_admin_add" => "添加客户分组:%s-分组ID#%d", "ClientGroup_admin_update" => "修改客户分组:分组ID#%d，%s", "ClientGroup_admin_delete" => "删除客户分组:%s-分组ID#%d", "Set_admin_postCustomFields_add" => "%s", "Set_admin_postCustomFields_edit" => "%s", "Set_admin_postCustomFields_delete" => "删除自定义字段#%d", "Currency_admin_addCurrency" => "添加货币种类#Currency ID:%d", "Currency_admin_deleteCurrency" => "删除货币种类#Currency ID:%d", "Currency_admin_updateCurrency" => "编辑货币种类#Currency ID:%d %s", "Currency_admin_default" => "设为默认货币#Currency ID:%d", "Currency_admin_updateRate" => "汇率更新", "Currency_admin_updatePrice" => "价格更新", "ConfigCer_admin_update" => "修改实名认证设置:%s", "PROMO_CODE_EDIT_SUCCESS" => "促销优惠码修改%d- %s", "System_admin_postOptimizeTables" => "优化数据库表", "System_admin_postDownDataBackup" => "下载数据库备份", "ConfigGen_admin_postGeneral" => "常规设置更改 %s", "ConfigGen_admin_postRecharge" => "支付设置更改 %s", "Rabc_admin_addRole" => " 添加角色#Role ID:%s", "Rabc_admin_editRole" => "角色编辑#Role ID:%d %s", "Rabc_admin_deleteRole" => "角色删除#Role ID:%d", "Rabcpage_admin_addRole" => " 添加页面#%s", "Rabcpage_admin_editRole" => "页面编辑#%d %s", "Rabcpage_admin_deleteRole" => "页面删除#%d", "Cron_home_editCron" => "自动任务编辑 %s", "ConfigMessage_admin_editCron" => "短信设置 %s", "ConfigGen_admin_emailIndexPost" => "邮件设置 %s", "Downloads_admin_editCron" => "文件下载编辑 %s", "EmailTem_admin_createTemplatePost" => "创建邮件模板 %s", "EmailTem_admin_add" => "添加语言#%s", "EmailTem_admin_disabled" => "禁用语言 %s", "EmailTem_admin_delete" => "删除邮件模板#%s", "EmailTem_admin_edit" => "编辑邮件模板#%s %s", "ConfigMessage_admin_update" => "更新短信模板审核状态#%s %s", "ConfigMessage_admin_create" => "创建短信模板#%d", "ConfigMessage_admin_edit" => "更新短信模板#%d", "ConfigMessage_admin_delete" => "删除短信模板#%d", "ConfigMessage_admin_SetSmsTemplatePost" => "发送设置修改id#%d - 改成邮件模板id#%s %s", "ConfigMessage_admin_SetSmsTemplateadd" => "设置修改添加#%d", "Sale_admin_addSalegroup" => "添加销售产品分组#%d", "Sale_admin_editSalegroup" => "修改销售产品分组#%d - %s", "Sale_admin_delSalegroup" => "删除销售产品分组#%d", "Sale_admin_addSaleladder" => "添加阶梯设置#%d", "Sale_admin_editSaleladder" => "修改阶梯设置#%d - %s", "Sale_admin_delSaleladder" => "删除阶梯设置#%d", "Aff_admin_aff" => "推介计划设置修改，%s", "Aff_admin_addSaleladder" => "添加推荐阶梯设置#%d", "Aff_admin_editSaleladder" => "修改推荐阶梯设置#%d - %s", "Aff_admin_delSaleladder" => "删除推荐阶梯设置#%d", "Aff_admin_editAdminList" => "修改用户销售设置- User ID:%d - %s", "Aff_admin_useraffiPost" => "修改用户推荐设置#%d - %s", "Aff_admin_useraffiAdd" => "添加用户推荐设置#%d", "Aff_admin_productaffiPost" => "修改产品推荐设置#ID:%d，%s", "Aff_admin_productaffiAdd" => "添加产品:%s-推荐设置#ID:%d", "Aff_admin_useraffibalance" => "修改用户推荐金额#%d - %s", "Aff_admin_withdrawsh" => "提现审核通过#%d - User ID:%d -  提现方式- %s", "Aff_admin_withdrawsh_no" => "提现审核不通过#%d - User ID:%d - 原因: %s", "Aff_cron_update_balance" => "更新用户可提现余额 - User ID:%d - 余额: %s 改为 :%s", "Aff_cron_update_audited_balance" => "更新用户待确认余额 - User ID:%d - 余额: %s 改为 :%s", "Aff_cron_update_payamount" => "更新用户待订购数量 - User ID:%d - 数量改为 :%s", "Product_admin_addProductgroup" => "添加产品分组%s - #ID:%d", "Product_admin_editProductgroup" => "修改产品分组#ID:%d，%s", "Product_admin_delProductgroup" => "删除产品分组%s - #ID:%d", "Product_admin_UserProductgroup" => "添加产品分组金额#ID:%d，客户组#ID:%d，产品组#ID:%d", "Product_admin_UserProductgroupedit" => "修改产品分组金额#ID:%d，%s，客户组#ID:%d，产品组#ID:%d", "Ur_admin_add" => "添加上游#%d", "Ur_admin_edit" => "修改上游#%d - %s", "Ur_admin_del" => "删除上游#%d", "Ur_admin_addupper" => "添加资源配置#%d", "Ur_admin_editupper" => "修改资源配置#%d - %s", "Ur_admin_delupper" => "删除资源配置#%d", "ConfigGen_admin_delpg" => "删除产品分组#%d", "ConfigGen_admin_addpg" => "添加产品分组#%d", "ConfigGen_admin_editpg" => "修改产品分组#%d - %s", "UserManage_user_openClient_success" => "开启客户(%s) - User ID:%d", "Contract_setting" => "合同基础设置:%s"]];