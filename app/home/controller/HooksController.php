<?php

namespace app\home\controller;

/**
 * @title 钩子文档
 * @description 接口说明：这这里编写添加的钩子文档(hook 名和hook中参数)
 */
class HooksController
{
	/**
	 * @title 在管理区添加工单备注
	 * @description 接口说明:无
	 * @author hh
	 * @url ticket_add_note
	 * @method 
	 * @param .name:ticketid type:int require:1 default:0 other: desc:工单ID
	 * @param .name:content type:string require:1 default:0 other: desc:备注内容
	 * @param .name:attachment type:array require:1 default:0 other: desc:工单附件储存路径
	 * @param .name:adminid type:int require:1 default:0 other: desc:管理员ID
	 * @return 响应：不支持回复
	 */
	public function ticket_add_note()
	{
	}
	/**
	 * @title 在管理区回复工单
	 * @description 接口说明:无
	 * @author hh
	 * @url ticket_admin_reply
	 * @method 
	 * @param .name:ticketid type:int require:1 default:0 other: desc:工单ID
	 * @param .name:replyid type:int require:1 default:0 other: desc:工单回复ID
	 * @param .name:dptid type:int require:1 default:0 other: desc:工单部门ID
	 * @param .name:dptname type:string require:1 default:0 other: desc:工单部门名称
	 * @param .name:title type:string require:1 default:0 other: desc:工单标题
	 * @param .name:content type:string require:0 default:0 other: desc:回复内容
	 * @param .name:priority type:string require:0 default:0 other: desc:工单优先级
	 * @param .name:admin type:string require:0 default:0 other: desc:管理员名称
	 * @param .name:status type:int require:0 default:0 other: desc:工单状态ID
	 * @param .name:status_title type:string require:0 default:0 other: desc:工单状态名称
	 * @return 响应：不支持回复
	 */
	public function ticket_admin_reply()
	{
	}
	/**
	 * @title 关闭工单
	 * @description 当工单关闭后执行
	 * @author hh
	 * @url ticket_close
	 * @method 
	 * @param .name:ticketid type:int require:1 default:0 other: desc:工单ID
	 * @return 响应：不支持回复
	 */
	public function ticket_close()
	{
	}
	/**
	 * @title 在管理区删除工单
	 * @description 接口说明:无
	 * @author hh
	 * @url ticket_delete
	 * @method 
	 * @param .name:ticketid type:array require:1 default:0 other: desc:工单ID
	 * @param .name:adminid type:int require:1 default:0 other: desc:操作的管理员ID
	 * @return 响应：不支持回复
	 */
	public function ticket_delete()
	{
	}
	/**
	 * @title 删除工单工单回复后执行
	 * @description 接口说明:无
	 * @author hh
	 * @url ticket_delete_reply
	 * @method 
	 * @param .name:ticketid type:int require:1 default:0 other: desc:工单ID
	 * @param .name:replyid type:int require:1 default:0 other: desc:工单回复ID
	 * @param .name:adminid type:int require:1 default:0 other: desc:操作的管理员ID
	 * @return 响应：不支持回复
	 */
	public function ticket_delete_reply()
	{
	}
	/**
	 * @title 工单部门变更后执行
	 * @description 接口说明:无
	 * @author hh
	 * @url ticket_department_change
	 * @method 
	 * @param .name:ticketid type:int require:1 default:0 other: desc:工单ID
	 * @param .name:dptid type:int require:1 default:0 other: desc:新部门ID
	 * @param .name:dptname type:string require:1 default:0 other: desc:新部门名称
	 * @return 响应：不支持回复
	 */
	public function ticket_department_change()
	{
	}
	/**
	 * @title 用户新建工单后执行
	 * @description 接口说明:无
	 * @author hh
	 * @url ticket_open
	 * @method 
	 * @param .name:ticketid type:int require:1 default:0 other: desc:工单ID
	 * @param .name:tid type:string require:1 default:0 other: desc:工单号
	 * @param .name:uid type:int require:1 default:0 other: desc:用户ID
	 * @param .name:dptid type:int require:1 default:0 other: desc:部门ID
	 * @param .name:dptname type:string require:1 default:0 other: desc:部门名称
	 * @param .name:title type:string require:1 default:0 other: desc:工单标题
	 * @param .name:content type:string require:1 default:0 other: desc:工单内容
	 * @param .name:priority type:string require:1 default:0 other: desc:优先级
	 * @param .name:hostid type:int require:1 default:0 other: desc:产品ID
	 * @param .name:attachment type:array require:1 default:0 other: desc:附件
	 * @return 响应：不支持回复
	 */
	public function ticket_open()
	{
	}
	/**
	 * @title 在管理区新建工单
	 * @description 接口说明:无
	 * @author hh
	 * @url ticket_open_admin
	 * @method 
	 * @param .name:ticketid type:int require:1 default:0 other: desc:工单ID
	 * @param .name:tid type:string require:1 default:0 other: desc:工单号
	 * @param .name:uid type:int require:1 default:0 other: desc:用户ID
	 * @param .name:dptid type:int require:0 default:0 other: desc:部门ID
	 * @param .name:dptname type:string require:0 default:0 other: desc:部门名称
	 * @param .name:title type:string require:0 default:0 other: desc:工单标题
	 * @param .name:content type:string require:0 default:0 other: desc:工单内容
	 * @param .name:priority type:string require:0 default:0 other: desc:优先级high高,medium中,low低
	 * @param .name:attachment type:array require:1 default:0 other: desc:附件
	 * @return 响应：不支持回复
	 */
	public function ticket_open_admin()
	{
	}
	/**
	 * @title 工单状态被管理员手动改变时执行
	 * @description 接口说明:无
	 * @author hh
	 * @url ticket_status_change
	 * @method 
	 * @param .name:ticketid type:array require:1 default:0 other: desc:工单ID
	 * @param .name:status type:int require:1 default:0 other: desc:新状态ID
	 * @param .name:status_title type:string require:1 default:0 other: desc:新状态名称
	 * @param .name:adminid type:int require:1 default:0 other: desc:管理员ID
	 * @return 响应：不支持回复
	 */
	public function ticket_status_change()
	{
	}
	/**
	 * @title 工单标题变更后执行
	 * @description 接口说明:无
	 * @author hh
	 * @url ticket_title_change
	 * @method 
	 * @param .name:ticketid type:int require:1 default:0 other: desc:工单ID
	 * @param .name:title type:string require:1 default:0 other: desc:新标题
	 * @return 响应：不支持回复
	 */
	public function ticket_title_change()
	{
	}
	/**
	 * @title 用户回复工单后执行
	 * @description 接口说明:无
	 * @author hh
	 * @url ticket_user_reply
	 * @method 
	 * @param .name:ticketid type:int require:1 default:0 other: desc:工单ID
	 * @param .name:replyid type:int require:1 default:0 other: desc:工单回复ID
	 * @param .name:uid type:int require:1 default:0 other: desc:用户ID
	 * @param .name:dptid type:int require:1 default:0 other: desc:工单部门ID
	 * @param .name:dptname type:string require:1 default:0 other: desc:工单部门名称
	 * @param .name:title type:string require:1 default:0 other: desc:工单标题
	 * @param .name:content type:string require:0 default:0 other: desc:回复内容
	 * @param .name:priority type:string require:0 default:0 other: desc:工单优先级
	 * @param .name:status type:int require:0 default:0 other: desc:工单状态ID
	 * @param .name:status_title type:string require:0 default:0 other: desc:工单状态名称
	 * @return 响应：不支持回复
	 */
	public function ticket_user_reply()
	{
	}
	/**
	 * @title 每次定时任务之后执行
	 * @author hh
	 * @url after_cron
	 * @method 
	 * @return 响应：不支持回复
	 */
	public function after_cron()
	{
	}
	/**
	 * @title 每次定时任务之前执行
	 * @author hh
	 * @url before_cron
	 * @method 
	 * @return 响应：不支持回复
	 */
	public function before_cron()
	{
	}
	/**
	 * @title 每五分钟定时任务之后执行
	 * @author hh
	 * @url after_five_minute_cron
	 * @method 
	 * @return 响应：不支持回复
	 */
	public function after_five_minute_cron()
	{
	}
	/**
	 * @title 每天定时任务之后执行
	 * @author hh
	 * @url after_daily_cron
	 * @method 
	 * @return 响应：不支持回复
	 */
	public function after_daily_cron()
	{
	}
	/**
	 * @title 每天定时任务之前执行
	 * @author hh
	 * @url before_daily_cron
	 * @method 
	 * @return 响应：不支持回复
	 */
	public function before_daily_cron()
	{
	}
	/**
	 * @title 定时任务保存后执行
	 * @author hh
	 * @url cron_config_save
	 * @method 
	 * @param .name:adminid type:int require:1 default:0 other: desc:操作的管理员ID
	 * @return 响应：不支持回复
	 */
	public function cron_config_save()
	{
	}
	/**
	 * @title 模块升降级成功之后执行
	 * @author hh
	 * @url after_module_change_package
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 响应：不支持回复
	 */
	public function after_module_change_package()
	{
	}
	/**
	 * @title 模块升降级失败之后执行
	 * @author hh
	 * @url after_module_change_package_failed
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @param .name:msg type:string require:1 default:0 other: desc:失败原因
	 * @return 响应：不支持回复
	 */
	public function after_module_change_package_failed()
	{
	}
	/**
	 * @title 模块重置密码成功之后执行
	 * @author hh
	 * @url after_module_crack_password
	 * @method 
	 * @param .name:hostid type:int require:1 default:0 other: desc:产品ID
	 * @param .name:oldpassword type:string require:1 default:0 other: desc:原密码
	 * @param .name:newspassword type:string require:1 default:0 other: desc:新密码
	 * @return 响应：不支持回复
	 */
	public function after_module_crack_password()
	{
	}
	/**
	 * @title 模块重置密码失败之后执行
	 * @author hh
	 * @url after_module_crack_password_failed
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @param .name:msg type:string require:1 default:0 other: desc:失败原因
	 * @return 响应：不支持回复
	 */
	public function after_module_crack_password_failed()
	{
	}
	/**
	 * @title 模块开通成功之后执行
	 * @author hh
	 * @url after_module_create
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 响应：不支持回复
	 */
	public function after_module_create()
	{
	}
	/**
	 * @title 模块开通失败之后执行
	 * @author hh
	 * @url after_module_create_failed
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @param .name:msg type:string require:1 default:0 other: desc:失败原因
	 * @return 响应：不支持回复
	 */
	public function after_module_create_failed()
	{
	}
	/**
	 * @title 模块暂停成功之后执行
	 * @author hh
	 * @url after_module_suspend
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 响应：不支持回复
	 */
	public function after_module_suspend()
	{
	}
	/**
	 * @title 模块暂停失败之后执行
	 * @author hh
	 * @url after_module_suspend_failed
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @param .name:msg type:string require:1 default:0 other: desc:失败原因
	 * @return 响应：不支持回复
	 */
	public function after_module_suspend_failed()
	{
	}
	/**
	 * @title 模块删除成功之后执行
	 * @author hh
	 * @url after_module_terminate
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 响应：不支持回复
	 */
	public function after_module_terminate()
	{
	}
	/**
	 * @title 模块删除失败之后执行
	 * @author hh
	 * @url after_module_terminate_failed
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @param .name:msg type:string require:1 default:0 other: desc:失败原因
	 * @return 响应：不支持回复
	 */
	public function after_module_terminate_failed()
	{
	}
	/**
	 * @title 模块解除暂停成功之后执行
	 * @author hh
	 * @url after_module_unsuspend
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 响应：不支持回复
	 */
	public function after_module_unsuspend()
	{
	}
	/**
	 * @title 模块解除暂停之后执行
	 * @author hh
	 * @url after_module_unsuspend_failed
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @param .name:msg type:string require:1 default:0 other: desc:失败原因
	 * @return 响应：不支持回复
	 */
	public function after_module_unsuspend_failed()
	{
	}
	/**
	 * @title 模块开机成功之后执行
	 * @author hh
	 * @url after_module_on
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 响应：不支持回复
	 */
	public function after_module_on()
	{
	}
	/**
	 * @title 模块开机失败之后执行
	 * @author hh
	 * @url after_module_on_failed
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @param .name:msg type:string require:1 default:0 other: desc:失败原因
	 * @return 响应：不支持回复
	 */
	public function after_module_on_failed()
	{
	}
	/**
	 * @title 模块关机成功之后执行
	 * @author hh
	 * @url after_module_off
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 响应：不支持回复
	 */
	public function after_module_off()
	{
	}
	/**
	 * @title 模块关机失败之后执行
	 * @author hh
	 * @url after_module_off_failed
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @param .name:msg type:string require:1 default:0 other: desc:失败原因
	 * @return 响应：不支持回复
	 */
	public function after_module_off_failed()
	{
	}
	/**
	 * @title 模块重启成功之后执行
	 * @author hh
	 * @url after_module_reboot
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 响应：不支持回复
	 */
	public function after_module_reboot()
	{
	}
	/**
	 * @title 模块重启失败之后执行
	 * @author hh
	 * @url after_module_reboot_failed
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @param .name:msg type:string require:1 default:0 other: desc:失败原因
	 * @return 响应：不支持回复
	 */
	public function after_module_reboot_failed()
	{
	}
	/**
	 * @title 模块硬关机成功之后执行
	 * @author hh
	 * @url after_module_hard_off
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 响应：不支持回复
	 */
	public function after_module_hard_off()
	{
	}
	/**
	 * @title 模块硬关机失败之后执行
	 * @author hh
	 * @url after_module_hard_off_failed
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @param .name:msg type:string require:1 default:0 other: desc:失败原因
	 * @return 响应：不支持回复
	 */
	public function after_module_hard_off_failed()
	{
	}
	/**
	 * @title 模块硬重启成功之后执行
	 * @author hh
	 * @url after_module_hard_reboot
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 响应：不支持回复
	 */
	public function after_module_hard_reboot()
	{
	}
	/**
	 * @title 模块硬重启失败之后执行
	 * @author hh
	 * @url after_module_hard_reboot_failed
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @param .name:msg type:string require:1 default:0 other: desc:失败原因
	 * @return 响应：不支持回复
	 */
	public function after_module_hard_reboot_failed()
	{
	}
	/**
	 * @title 模块重装系统成功之后执行
	 * @author hh
	 * @url after_module_reinstall
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 响应：不支持回复
	 */
	public function after_module_reinstall()
	{
	}
	/**
	 * @title 模块重装系统失败之后执行
	 * @author hh
	 * @url after_module_reinstall_failed
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @param .name:msg type:string require:1 default:0 other: desc:失败原因
	 * @return 响应：不支持回复
	 */
	public function after_module_reinstall_failed()
	{
	}
	/**
	 * @title 模块救援系统成功之后执行
	 * @author hh
	 * @url after_module_rescue_system
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 响应：不支持回复
	 */
	public function after_module_rescue_system()
	{
	}
	/**
	 * @title 模块救援系统失败之后执行
	 * @author hh
	 * @url after_module_rescue_system_failed
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @param .name:msg type:string require:1 default:0 other: desc:失败原因
	 * @return 响应：不支持回复
	 */
	public function after_module_rescue_system_failed()
	{
	}
	/**
	 * @title 模块拉取信息成功之后执行
	 * @author hh
	 * @url after_module_sync
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 响应：不支持回复
	 */
	public function after_module_sync()
	{
	}
	/**
	 * @title 模块拉取信息失败之后执行
	 * @author hh
	 * @url after_module_sync_failed
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @param .name:msg type:string require:1 default:0 other: desc:失败原因
	 * @return 响应：不支持回复
	 */
	public function after_module_sync_failed()
	{
	}
	/**
	 * @title 模块升降级之前执行
	 * @author hh
	 * @url before_module_change_package
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 返回键值对,键值对将会覆盖原来相同键的params,返回exit_module=true将会中断模块方法
	 */
	public function before_module_change_package()
	{
	}
	/**
	 * @title 模块重置密码之前执行
	 * @author hh
	 * @url before_module_crack_password
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 返回键值对,键值对将会覆盖原来相同键的params,返回exit_module=true将会中断模块方法
	 */
	public function before_module_crack_password()
	{
	}
	/**
	 * @title 模块开通之前执行
	 * @author hh
	 * @url before_module_create
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 返回键值对,键值对将会覆盖原来相同键的params,返回exit_module=true将会中断模块方法
	 */
	public function before_module_create()
	{
	}
	/**
	 * @title 模块续费之前执行
	 * @author hh
	 * @url before_module_renew
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 返回键值对,键值对将会覆盖原来相同键的params,返回exit_module=true将会中断模块方法
	 */
	public function before_module_renew()
	{
	}
	/**
	 * @title 模块暂停之前执行
	 * @author hh
	 * @url before_module_suspend
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 返回键值对,键值对将会覆盖原来相同键的params,返回exit_module=true将会中断模块方法
	 */
	public function before_module_suspend()
	{
	}
	/**
	 * @title 模块删除之前执行
	 * @author hh
	 * @url before_module_terminate
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 返回键值对,键值对将会覆盖原来相同键的params,返回exit_module=true将会中断模块方法
	 */
	public function before_module_terminate()
	{
	}
	/**
	 * @title 模块解除暂停之前执行
	 * @author hh
	 * @url before_module_unsuspend
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 返回键值对,键值对将会覆盖原来相同键的params,返回exit_module=true将会中断模块方法
	 */
	public function before_module_unsuspend()
	{
	}
	/**
	 * @title 模块开机之前执行
	 * @author hh
	 * @url before_module_on
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 返回键值对,键值对将会覆盖原来相同键的params,返回exit_module=true将会中断模块方法
	 */
	public function before_module_on()
	{
	}
	/**
	 * @title 模块关机之前执行
	 * @author hh
	 * @url before_module_off
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 返回键值对,键值对将会覆盖原来相同键的params,返回exit_module=true将会中断模块方法
	 */
	public function before_module_off()
	{
	}
	/**
	 * @title 模块重启之前执行
	 * @author hh
	 * @url before_module_reboot
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 返回键值对,键值对将会覆盖原来相同键的params,返回exit_module=true将会中断模块方法
	 */
	public function before_module_reboot()
	{
	}
	/**
	 * @title 模块硬关机之前执行
	 * @author hh
	 * @url before_module_hard_off
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 返回键值对,键值对将会覆盖原来相同键的params,返回exit_module=true将会中断模块方法
	 */
	public function before_module_hard_off()
	{
	}
	/**
	 * @title 模块硬重启之前执行
	 * @author hh
	 * @url before_module_hard_reboot
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 返回键值对,键值对将会覆盖原来相同键的params,返回exit_module=true将会中断模块方法
	 */
	public function before_module_hard_reboot()
	{
	}
	/**
	 * @title 模块重装系统之前执行
	 * @author hh
	 * @url before_module_reinstall
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 返回键值对,键值对将会覆盖原来相同键的params,返回exit_module=true将会中断模块方法
	 */
	public function before_module_reinstall()
	{
	}
	/**
	 * @title 模块救援系统之前执行
	 * @author hh
	 * @url before_module_rescue_system
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 返回键值对,键值对将会覆盖原来相同键的params,返回exit_module=true将会中断模块方法
	 */
	public function before_module_rescue_system()
	{
	}
	/**
	 * @title 模块拉取信息之前执行
	 * @author hh
	 * @url before_module_sync
	 * @method 
	 * @param .name:params type:array require:1 default:0 other: desc:参考模块开发的params
	 * @return 返回键值对,键值对将会覆盖原来相同键的params,返回exit_module=true将会中断模块方法
	 */
	public function before_module_sync()
	{
	}
	/**
	 * @title 后台手动添加交易流水后执行
	 * @author hh
	 * @url after_admin_add_account
	 * @method 
	 * @param .name:account_id type:int require:1 default:0 other: desc:交易流水ID
	 * @param .name:amount_in type:float require:0  other: desc:收入
	 * @param .name:amount_out type:float require:0  other: desc:支出
	 * @param .name:currency type:string require:0  other: desc:货币代码
	 * @param .name:description type:string require:0  other: desc:描述
	 * @param .name:trans_id type:string require:0  other: desc:付款流水号
	 * @param .name:invoice_id type:int require:0  other: desc:账单ID
	 * @param .name:gateway type:string require:0  other: desc:付款方式
	 * @param .name:refund type:int require:0  other: desc:是否退款至余额
	 * @param .name:uid type:int require:0  other: desc:用户ID
	 * @return 无
	 */
	public function after_admin_add_account()
	{
	}
	/**
	 * @title 后台手动编辑交易流水后执行
	 * @author hh
	 * @url after_admin_edit_account
	 * @method 
	 * @param .name:account_id type:int require:1 default:0 other: desc:交易流水ID
	 * @param .name:amount_in type:float require:0  other: desc:收入
	 * @param .name:amount_out type:float require:0  other: desc:支出
	 * @param .name:invoice_id type:int require:0  other: desc:账单ID
	 * @param .name:gateway type:string require:0  other: desc:付款方式
	 * @return 无
	 */
	public function after_admin_edit_account()
	{
	}
	/**
	 * @title 后台手动删除交易流水后执行
	 * @author hh
	 * @url after_admin_delete_account
	 * @method 
	 * @param .name:account_id type:int require:1 default:0 other: desc:交易流水ID
	 * @return 无
	 */
	public function after_admin_delete_account()
	{
	}
	/**
	 * @title 管理员退出登录执行
	 * @author hh
	 * @url admin_logout
	 * @method 
	 * @param .name:adminid type:int require:1 default:0 other: desc:管理员ID
	 * @return 无
	 */
	public function admin_logout()
	{
	}
	/**
	 * @title 管理员登录执行
	 * @author hh
	 * @url admin_login
	 * @method 
	 * @param .name:adminid type:int require:1 default:0 other: desc:管理员ID
	 * @param .name:admin type:string require:1 default:0 other: desc:管理员账号
	 * @param .name:nickname type:string require:1 default:0 other: desc:管理员昵称
	 * @return 无
	 */
	public function admin_login()
	{
	}
	/**
	 * @title 管理员登录系统验证全通过后执行
	 * @author hh
	 * @url auth_admin_login
	 * @method 
	 * @return status:true通过验证/false验证失败
	 * @return msg:失败信息
	 */
	public function auth_admin_login()
	{
	}
	/**
	 * @title 添加管理员后执行
	 * @author hh
	 * @url add_admin
	 * @method 
	 * @param .name:adminid type:int require:1 default:0 other: desc:管理员ID
	 */
	public function add_admin()
	{
	}
	/**
	 * @title 编辑管理员后执行
	 * @author hh
	 * @url edit_admin
	 * @method 
	 * @param .name:adminid type:int require:1 default:0 other: desc:管理员ID
	 */
	public function edit_admin()
	{
	}
	/**
	 * @title 删除管理员后执行
	 * @author hh
	 * @url delete_admin
	 * @method 
	 * @param .name:adminid type:int require:1 default:0 other: desc:管理员ID
	 */
	public function delete_admin()
	{
	}
	/**
	 * @title 管理员手动保存产品后执行
	 * @author hh
	 * @url after_admin_edit_service
	 * @method 
	 * @param .name:adminid type:int require:1 default:0 other: desc:管理员ID
	 * @param .name:hostid type:int require:1 default:0 other: desc:服务ID
	 */
	public function after_admin_edit_service()
	{
	}
	/**
	 * @title 产品转移后执行
	 * @author 萧十一郎
	 * @url transfer_service
	 * @method GET
	 * @param .name:uid type:int require:1 default:0 other: desc:用户id
	 * @param .name:transfer_uid type:int require:1 default:0 other: desc:接收用户id
	 * @param .name:hostid type:int require:1 default:0 other: desc:服务id
	 * @return 响应：不支持回复
	 */
	public function transfer_service()
	{
	}
	/**
	 * @title 删除服务后执行。
	 * @author 萧十一郎
	 * @url service_delete
	 * @method GET
	 * @param .name:uid type:int require:1 default:0 other: desc:用户id
	 * @param .name:hostid type:int require:1 default:0 other: desc:服务id
	 * @return 响应：不支持回复
	 */
	public function service_delete()
	{
	}
	/**
	 * @title 删除商品后执行
	 * @author 萧十一郎
	 * @url product_delete
	 * @method 
	 * @param .name:pid type:int require:1 default:0 other: desc:商品id
	 * @return 响应：不支持回复
	 */
	public function product_delete()
	{
	}
	/**
	 * @title 商品创建后执行
	 * @author 萧十一郎
	 * @url product_create
	 * @method 
	 * @param .name:pid type:int require:1 default:0 other: desc:商品id
	 * @return 响应：不支持回复
	 */
	public function product_create()
	{
	}
	/**
	 * @title 商品编辑后执行
	 * @author 萧十一郎
	 * @url product_edit
	 * @method 
	 * @param .name:pid type:int require:1 default:0 other: desc:商品id
	 * @return 响应：不支持回复
	 */
	public function product_edit()
	{
	}
	/**
	 * @title 在创建取消请求时执行
	 * @author 萧十一郎
	 * @url cancellation_request
	 * @method GET
	 * @param .name:uid type:int require:1 default:0 other: desc:用户id
	 * @param .name:relid type:int require:1 default:0 other: desc:服务被取消的ID
	 * @param .name:reason type:int require: default:0 other: desc:取消原因
	 * @param .name:type type:string require: default:0 other: desc:取消类型
	 * @return 响应：不支持回复
	 */
	public function cancellation_request()
	{
	}
	/**
	 * @title 产品升级后执行
	 * @author wyh
	 * @url after_product_upgrade
	 * @method GET
	 * @param .name:upgradeid type:int require:1 default:0 other: desc:升级ID
	 * @return 响应：不支持回复
	 */
	public function after_product_upgrade()
	{
	}
	/**
	 * @title 账单支付后邮件发送前执行
	 * @author hh
	 * @url invoice_paid_before_email
	 * @method 
	 * @param .name:invoiceid type:int require:1 default:0 other: desc:账单ID
	 * @return 响应：不支持回复
	 */
	public function invoice_paid_before_email()
	{
	}
	/**
	 * @title 账单支付后邮件发送后执行
	 * @author hh
	 * @url invoice_paid
	 * @method 
	 * @param .name:invoiceid type:int require:1 default:0 other: desc:账单ID
	 * @return 响应：不支持回复
	 */
	public function invoice_paid()
	{
	}
	/**
	 * @title 当账单标记为未支付后执行
	 * @author hh
	 * @url invoice_mark_unpaid
	 * @method 
	 * @param .name:invoiceid type:int require:1 default:0 other: desc:账单ID
	 * @return 响应：不支持回复
	 */
	public function invoice_mark_unpaid()
	{
	}
	/**
	 * @title 当账单标记为已取消后执行
	 * @author hh
	 * @url invoice_mark_cancelled
	 * @method 
	 * @param .name:invoiceid type:int require:1 default:0 other: desc:账单ID
	 * @return 响应：不支持回复
	 */
	public function invoice_mark_cancelled()
	{
	}
	/**
	 * @title 当账单删除后执行
	 * @author hh
	 * @url invoice_delete
	 * @method 
	 * @param .name:invoiceid type:int require:1 default:0 other: desc:账单ID
	 * @return 响应：不支持回复
	 */
	public function invoice_delete()
	{
	}
	/**
	 * @title 账单退款后执行
	 * @author hh
	 * @url invoice_refunded
	 * @method 
	 * @param .name:invoiceid type:int require:1 default:0 other: desc:账单ID
	 * @param .name:amount type:float require:1 default:0 other: desc:退款金额
	 * @return 响应：不支持回复
	 */
	public function invoice_refunded()
	{
	}
	/**
	 * @title 账单备注后执行
	 * @author hh
	 * @url invoice_notes
	 * @method 
	 * @param .name:invoiceid type:int require:1 default:0 other: desc:账单ID
	 * @param .name:content type:string require:1 default:0 other: desc:备注内容
	 * @return 响应：不支持回复
	 */
	public function invoice_notes()
	{
	}
	/**
	 * @title 创建续费账单后
	 * @author hh
	 * @url renew_invoice_create
	 * @method GET
	 * @param .name:invoiceid type:int require:1 default:0 other: desc:生成的账单id
	 * @param .name:hostid type:int require:1 default:0 other: desc:产品id
	 * @return 响应：不支持回复
	 */
	public function renew_invoice_create()
	{
	}
	/**
	 * @title 创建流量包账单后
	 * @author hh
	 * @url flow_packet_invoice_create
	 * @method GET
	 * @param .name:invoiceid type:int require:1 default:0 other: desc:生成的账单id
	 * @param .name:hostid type:int require:1 default:0 other: desc:产品id
	 * @param .name:price type:float require:1 default:0 other: desc:流量包价格
	 * @param .name:name type:string require:1 default:0 other: desc:流量包名称
	 * @param .name:capacity type:string require:1 default:0 other: desc:流量包大小
	 * @param .name:flowpacketid type:string require:1 default:0 other: desc:流量包ID
	 * @return 响应：不支持回复
	 */
	public function flow_packet_invoice_create()
	{
	}
	/**
	 * @title 合并账单后执行
	 * @author hh
	 * @url invoice_combine
	 * @method GET
	 * @param .name:invoiceid type:int require:1 default:0 other: desc:生成的账单id
	 * @param .name:combined_invoice type:array require:1 default:0 other: desc:合并的账单ID
	 * @return 响应：不支持回复
	 */
	public function invoice_combine()
	{
	}
	/**
	 * @title 订单审核通过后执行
	 * @author hh
	 * @url order_pass_check
	 * @method GET
	 * @param .name:orderid type:int require:1 default:0 other: desc:订单id
	 * @return 响应：不支持回复
	 */
	public function order_pass_check()
	{
	}
	/**
	 * @title 订单取消后执行
	 * @author hh
	 * @url order_cancel
	 * @method GET
	 * @param .name:orderid type:int require:1 default:0 other: desc:订单id
	 * @return 响应：不支持回复
	 */
	public function order_cancel()
	{
	}
	/**
	 * @title 订单删除后执行
	 * @author hh
	 * @url order_delete
	 * @method GET
	 * @param .name:orderid type:int require:1 default:0 other: desc:订单id
	 * @return 响应：不支持回复
	 */
	public function order_delete()
	{
	}
	/**
	 * @title 客户添加后
	 * @author wyh
	 * @url client_add
	 * @method POST
	 * @param .name:userid type:int require:1 default:1 other: desc:用户名ID
	 * @param .name:username type:int require:1 default:1 other: desc:用户名
	 * @param .name:sex type:int require:1 default:1 other: desc:性别
	 * @param .name:avatar type:int require:0 default:1 other: desc:头像
	 * @param .name:profession type:int require:0 default:1 other: desc:职业
	 * @param .name:signature type:int require:0 default:1 other: desc:个性签名
	 * @param .name:companyname type:int require:0 default:1 other: desc:所在公司
	 * @param .name:email type:int require:0 default:0 other: desc:邮件
	 * @param .name:country type:int require:0 default:0 other: desc:国家
	 * @param .name:province type:int require:0 default:0 other: desc:省份
	 * @param .name:city type:int require:0 default:0 other: desc:城市
	 * @param .name:region type:int require:0 default:0 other: desc:区
	 * @param .name:address1 type:int require:0 default:1 other: desc:具体地址1
	 * @param .name:address2 type:int require:0 default:1 other: desc:具体地址2
	 * @param .name:postcode type:int require:0 default:1 other: desc:邮编
	 * @param .name:phone_code type:int require:0 default:1 other: desc:电话区号
	 * @param .name:phonenumber type:int require:0 default:1 other: desc:电话
	 * @param .name:notes type:int require:0 default:0 other: desc:管理员备注
	 * @param .name:groupid type:int require:0 default:0 other: desc:用户组ID
	 * @return 响应：不支持回复
	 */
	public function client_add()
	{
	}
	/**
	 * @title 客户编辑
	 * @author wyh
	 * @url client_edit
	 * @method POST
	 * @param .name:userid type:int require:1 default:1 other: desc:用户名ID
	 * @param .name:username type:int require:1 default:1 other: desc:用户名
	 * @param .name:sex type:int require:1 default:1 other: desc:性别
	 * @param .name:avatar type:int require:0 default:1 other: desc:头像
	 * @param .name:profession type:int require:0 default:1 other: desc:职业
	 * @param .name:signature type:int require:0 default:1 other: desc:个性签名
	 * @param .name:companyname type:int require:0 default:1 other: desc:所在公司
	 * @param .name:email type:int require:0 default:0 other: desc:邮件
	 * @param .name:country type:int require:0 default:0 other: desc:国家
	 * @param .name:province type:int require:0 default:0 other: desc:省份
	 * @param .name:city type:int require:0 default:0 other: desc:城市
	 * @param .name:region type:int require:0 default:0 other: desc:区
	 * @param .name:address1 type:int require:0 default:1 other: desc:具体地址1
	 * @param .name:address2 type:int require:0 default:1 other: desc:具体地址2
	 * @param .name:postcode type:int require:0 default:1 other: desc:邮编
	 * @param .name:phone_code type:int require:0 default:1 other: desc:电话区号
	 * @param .name:phonenumber type:int require:0 default:1 other: desc:电话
	 * @param .name:notes type:int require:0 default:0 other: desc:管理员备注
	 * @param .name:groupid type:int require:0 default:0 other: desc:用户组ID
	 * @return 响应：不支持回复
	 */
	public function client_edit()
	{
	}
	/**
	 * @title 关闭客户后
	 * @author wyh
	 * @url client_close
	 * @method GET
	 * @param .name:userid type:int require:1 default:1 other: desc:客户ID
	 * @return 响应：不支持回复
	 */
	public function client_close()
	{
	}
	/**
	 * @title 删除客户前
	 * @author wyh
	 * @url pre_client_delete
	 * @method GET
	 * @param .name:userid type:int require:1 default:1 other: desc:客户ID
	 * @return 响应：不支持回复
	 */
	public function pre_client_delete()
	{
	}
	/**
	 * @title 删除客户后
	 * @author wyh
	 * @url client_delete
	 * @method GET
	 * @param .name:userid type:int require:1 default:1 other: desc:客户ID
	 * @return 响应：不支持回复
	 */
	public function client_delete()
	{
	}
	/**
	 * @title 添加客户前验证(客户端添加或者管理端添加)
	 * @author wyh
	 * @url client_details_validate
	 * @method POST
	 * @param .name:username type:int require:1 default:1 other: desc:用户名
	 * @param .name:sex type:int require:1 default:1 other: desc:性别
	 * @param .name:avatar type:int require:0 default:1 other: desc:头像
	 * @param .name:profession type:int require:0 default:1 other: desc:职业
	 * @param .name:signature type:int require:0 default:1 other: desc:个性签名
	 * @param .name:companyname type:int require:0 default:1 other: desc:所在公司
	 * @param .name:email type:int require:0 default:0 other: desc:邮件
	 * @param .name:country type:int require:0 default:0 other: desc:国家
	 * @param .name:province type:int require:0 default:0 other: desc:省份
	 * @param .name:city type:int require:0 default:0 other: desc:城市
	 * @param .name:region type:int require:0 default:0 other: desc:区
	 * @param .name:address1 type:int require:0 default:1 other: desc:具体地址1
	 * @param .name:address2 type:int require:0 default:1 other: desc:具体地址2
	 * @param .name:postcode type:int require:0 default:1 other: desc:邮编
	 * @param .name:phone_code type:int require:0 default:1 other: desc:电话区号
	 * @param .name:phonenumber type:int require:0 default:1 other: desc:电话
	 * @param .name:notes type:int require:0 default:0 other: desc:管理员备注
	 * @param .name:groupid type:int require:0 default:0 other: desc:用户组ID
	 * @return array.错误信息
	 */
	public function client_details_validate()
	{
		return ["Error message feedback error 1", "Error message feedback error 2"];
	}
	/**
	 * @title 用户登录后执行
	 * @author hh
	 * @url client_login
	 * @method 
	 * @param .name:uid type:int require:1 default:0 other: desc:用户ID
	 * @param .name:name type:string require:1 default:0 other: desc:用户名称
	 * @param .name:ip type:string require:1 default:0 other: desc:登录IP
	 * @param .name:jwt type:string require:1 default:0 other: desc:登录jwt
	 * @return 无
	 */
	public function client_login()
	{
	}
	/**
	 * @title 用户API登录后执行
	 * @author hh
	 * @url client_api_login
	 * @method 
	 * @param .name:uid type:int require:1 default:0 other: desc:用户ID
	 * @param .name:name type:string require:1 default:0 other: desc:用户名称
	 * @param .name:ip type:string require:1 default:0 other: desc:登录IP
	 * @return 无
	 */
	public function client_api_login()
	{
	}
	/**
	 * @title 用户重置密码后执行
	 * @author hh
	 * @url client_reset_password
	 * @method 
	 * @param .name:uid type:int require:1 default:0 other: desc:用户ID
	 * @param .name:password type:string require:1 default:0 other: desc:新密码
	 * @return 无
	 */
	public function client_reset_password()
	{
	}
	/**
	 * @title 用户退出登录后执行
	 * @author hh
	 * @url client_logout
	 * @method 
	 * @param .name:uid type:int require:1 default:0 other: desc:用户ID
	 * @return 无
	 */
	public function client_logout()
	{
	}
	/**
	 * @title 前台购物车修改购买产品数量后执行
	 * @author hh
	 * @url shopping_cart_modify_num
	 * @method 
	 * @param .name:pid type:int require:1 default:0 other: desc:产品ID
	 * @param .name:num type:int require:1 default:0 other: desc:修改后的数量
	 * @return 响应：不支持回复
	 */
	public function shopping_cart_modify_num()
	{
	}
	/**
	 * @title 前台购物车结算后执行
	 * @author hh
	 * @url shopping_cart_settle
	 * @method 
	 * @param .name:total type:int require:1 default:0 other: desc:结算金额(可能是免费)
	 * @param .name:invoiceid type:int require:1 default:0 other: desc:生成的账单ID
	 * @param .name:hostid type:array require:1 default:0 other: desc:生成的产品ID
	 * @return 响应：不支持回复
	 */
	public function shopping_cart_settle()
	{
	}
	/**
	 * @title 前台购物车添加商品后执行
	 * @author hh
	 * @url shopping_cart_add_product
	 * @method 
	 * @param  .name:pid type:number require:1 default: orther: desc:产品ID
	 * @param  .name:qty type:string require:1  default: orther: desc:产品数量
	 * @param  .name:serverid type:number require:0 default: orther: desc:服务器可用区ID
	 * @param  .name:configoption type:array require:1 default: orther: desc:产品配置数组
	 * @param  .name:customfield type:array require:1 default: orther: desc:产品自定义字段数组
	 * @param  .name:currencyid type:array require:1 default: orther: desc:货币ID
	 * @param  .name:host type:string require:0 default: orther: desc:主机名
	 * @param  .name:password type:string require:0 default: orther: desc:密码
	 * @return 响应：不支持回复
	 */
	public function shopping_cart_add_product()
	{
	}
	/**
	 * @title 前台购物车移除商品后执行
	 * @author hh
	 * @url shopping_cart_remove_product
	 * @method 
	 * @param  .name:pid type:number require:1 default: orther: desc:产品ID
	 * @param  .name:qty type:string require:1  default: orther: desc:产品数量
	 * @param  .name:serverid type:number require:0 default: orther: desc:服务器可用区ID
	 * @param  .name:configoption type:array require:1 default: orther: desc:产品配置数组
	 * @param  .name:customfield type:array require:1 default: orther: desc:产品自定义字段数组
	 * @param  .name:currencyid type:array require:1 default: orther: desc:货币ID
	 * @param  .name:host type:string require:0 default: orther: desc:主机名
	 * @param  .name:password type:string require:0 default: orther: desc:密码
	 * @return 响应：不支持回复
	 */
	public function shopping_cart_remove_product()
	{
	}
	/**
	 * @title 前台购物车清空后执行
	 * @author hh
	 * @url shopping_cart_clear
	 * @method 
	 * @param  .name:data type:array require:1 default: orther: desc:二维数组(pid=产品ID,billingcycle=购买周期,num=购买数量)
	 * @return 响应：不支持回复
	 */
	public function shopping_cart_clear()
	{
	}
	/**
	 * @title 添加服务器后
	 * @author wyh
	 * @url server_add
	 * @method GET
	 * @param .name:serverid type:int require:1 default:0 other: desc:服务器ID
	 * @return 响应：不支持回复
	 */
	public function server_add()
	{
	}
	/**
	 * @title 删除服务器前
	 * @author wyh
	 * @url server_delete
	 * @method GET
	 * @param .name:serverid type:int require:1 default:0 other: desc:服务器ID
	 * @return 响应：不支持回复
	 */
	public function server_delete()
	{
	}
	/**
	 * @title 编辑服务器前
	 * @author wyh
	 * @url server_edit
	 * @method GET
	 * @param .name:serverid type:int require:1 default:0 other: desc:服务器ID
	 * @return 响应：不支持回复
	 */
	public function server_edit()
	{
	}
	/**
	 * @title 在删除日志前执行
	 * @author hh
	 * @url before_delete_log
	 * @method GET
	 * @param .name:adminid type:int require:1 default:0 other: desc:管理员ID
	 * @param .name:type type:string require:1 default:0 other: desc:日志类型
	 * @return 响应：不支持回复
	 */
	public function before_delete_log()
	{
	}
	/**
	 * @title 添加系统活动日志
	 * @author 萧十一郎
	 * @url log_activity
	 * @method GET
	 * @param .name:description type:int require:1 default:0 other: desc:描述
	 * @param .name:user type:int require:1 default:0 other: desc:操作名(Sub-Account,Client,System)
	 * @param .name:uid type:int require: default:0 other: desc:用户id
	 * @param .name:ipaddress type:string require: default:0 other: desc:ip地址
	 * @return 响应：不支持回复
	 */
	public function log_activity()
	{
	}
	/**
	 * @title 用户推介计划激活后执行
	 * @author hh
	 * @url affiliate_activation
	 * @method GET
	 * @param .name:uid type:int require:1 default:0 other: desc:用户ID
	 * @param .name:affid type:int require:1 default:0 other: desc:推介ID
	 * @return 响应：不支持回复
	 */
	public function affiliate_activation()
	{
	}
	/**
	 * @title 自定义字段值更新时执行
	 * @author hh
	 * @url custom_field_save
	 * @method GET
	 * @param .name:fieldid type:int require:1 default:0 other: desc:自定义字段ID
	 * @param .name:relid type:int require:1 default:0 other: desc:关联ID
	 * @param .name:value type:string require:1 default:0 other: desc:自定义字段值
	 * @return 返回['value'=>'新value']用来覆盖自定义字段值
	 */
	public function custom_field_save()
	{
	}
	/**
	 * @title 邮件发送前执行
	 * @author hh
	 * @url before_email_send
	 * @method GET
	 * @param .name:email type:string require:1 default:0 other: desc:邮箱
	 * @param .name:subject type:string require:1 default:0 other: desc:主题
	 * @param .name:content type:string require:1 default:0 other: desc:邮件正文
	 * @return 
	 */
	public function before_email_send()
	{
	}
	public function custom_host_create()
	{
	}
	/**
	 * @title 购物车添加优惠码后执行,只执行一次
	 * @author wyh
	 * @url after_shop_add_promo
	 * @method GET
	 * @param .name:uid type:string require:1 default:0 other: desc:客户ID
	 * @param .name:id type:string require:1 default:0 other: desc:优惠码ID
	 * @return ['status'=>200/400,'msg'=>'消息'] 200成功,400失败且程序不再执行
	 */
	public function after_shop_add_promo()
	{
	}
	public function check_divert_invoice()
	{
	}
	public function product_divert_upgrade()
	{
	}
	public function product_divert_delete()
	{
	}
	/**
	 * @title 签订合同之后
	 * @author wyh
	 * @url after_sign_contract
	 * @method GET
	 * @param .name:id type:int require:1 default:0 other: desc:合同ID
	 */
	public function after_sign_contract()
	{
	}
	/**
	 * @title header头部,模板钩子
	 * @author wyh
	 * @url client_area_head_output
	 * @method GET
	 */
	public function client_area_head_output()
	{
	}
	/**
	 * @title footer底部,模板钩子
	 * @author wyh
	 * @url client_area_footer_output
	 * @method GET
	 */
	public function client_area_footer_output()
	{
	}
}