<?php

namespace app\common\logic;

class Log
{
	/**
	 * @title 添加系统活动日志
	 * @description 在所有系统中关键节点操作上添加日志（订单创建，账单创建，产品修改，产品配置修改，货币相关，自动任务，邮件发送，模块执行，）
	 * @param [description]:描述信息格式eg: "创建续费账单 - Invoice ID: 1 - Host ID: 1 - 错误：余额不足", (动作描述 - 关键ID: 1 - [执行错误的结果])
	 * @other 统一ID记录， 账单id: Invoice ID, 订单id: Order ID, 主机id：Host ID, 用户id: User ID, 工单id:Ticket ID, 
	 * @other 产品id:Product ID, ip地址：IP, 服务器id:Servse ID, 交易流水id:Transaction ID, 管理员id:Admin ID, 
	 * @other (如有其他，请在后面添加，描述务必写成示例格式，日志中相应ID会被替换成可跳转)
	 * @param [userid]: 后台管理员操作时或系统任务操作时,涉及到用户相关,需要传递用户id
	 * @other 用户日志读取本日志
	 */
	public static function activeLog($description, $userid = 0)
	{
		$uid = request()->uid ?: $userid;
		$contact_id = request()->contactid;
		$remote_ip = get_client_ip();
		$admin_id = cmf_get_current_admin_id();
		$username = "";
		if (!is_null($admin_id)) {
			$admin_name = \think\Db::name("user")->where("id", $admin_id)->value("user_login");
			$username = $admin_name;
		} else {
			if (!is_null($uid) && !is_null($contact_id)) {
				$username = "Sub-Account" . $contact_id;
			} else {
				if (!is_null($uid)) {
					$username = "Client";
				} else {
					$username = "System";
				}
			}
		}
		if (strpos($description, "password") !== false) {
			$description = preg_replace("/(password(?:hash)?`=')(.*)(',|' )/", "\${1}--REDACTED--\${3}", $description);
		}
		$idata = ["create_time" => time(), "description" => $description, "user" => $username, "uid" => $uid, "ipaddr" => $remote_ip];
		\think\Db::name("activity_log")->insert($idata);
		hook("log_activity", ["description" => $description, "user" => $username, "uid" => \intval($uid), "ipaddress" => $remote_ip]);
	}
	/**
	 * @title 管理员登录登出日志
	 * @description 后台用户登录成功后设置session后调用一次，用户登出，注销session前执行一次
	 */
	public static function adminLog()
	{
		$admin_id = cmf_get_current_admin_id();
		$session_id = session_id();
		$remote_ip = get_client_ip();
		$username = "";
		if (empty($admin_id) || empty($session_id)) {
			return null;
		}
		$admin_name = \think\Db::name("user")->where("id", $admin_id)->value("user_login");
		$username = $admin_name;
		$exists_data = \think\Db::name("admin_log")->where("sessionid", $session_id)->find();
		if (!empty($exists_data) && empty($exists_data["logouttime"])) {
			\think\Db::name("admin_log")->where("sessionid", $session_id)->update(["lastvisit" => time(), "logouttime" => time()]);
		} else {
			$idata = ["admin_username" => $username, "logintime" => time(), "ipaddress" => $remote_ip, "sessionid" => $session_id];
			\think\Db::name("admin_log")->insert($idata);
		}
	}
	/**
	 * @title 通知日志
	 * @param .name:message type:string default: require:0 other: desc:信息内容
	 * @param .name:to type:string default: require:0 other: desc:类型为sms时为手机号(带地区号eg: +86.1234567890),类型为email时为邮箱,类型为微信时，看着办
	 * @param .name:type type:string default: require:0 other: desc:邮件(email), 短信(sms), 微信通知(wechat)
	 * @param .name:subject type:string default: require:0 other: desc:主题(发送模板的名称)
	 * @param .name:userid type:int default: require:0 other: desc:不传会获取gwt中的uid，默认0
	 * @param .name:cc type:int default:email require:0 other: desc:抄送
	 * @param .name:bcc type:int default:email require:0 other: desc:抄送
	 */
	public static function notifyLog($message = "", $to = "", $type = "email", $subject = "", $userid = 0, $cc = "", $bcc = "")
	{
		$uid = request()->uid ?: $userid;
		$idata = ["create_time" => time(), "to" => $to, "message" => $message, "type" => $type, "subject" => $subject, "uid" => $uid, "cc" => $cc, "bcc" => $bcc];
		\think\Db::name("notify_log")->insertGetId($idata);
	}
	/**
	 * @title 网关日志/错误日志
	 * @param .name:gateway type:string default: require:0 other: desc:网关名
	 * @param .name:data type:string default: require:0 other: desc:需要记录的访问数据(json)
	 * @param .result:gateway type:string default: require:0 other: desc:描述/错误信息
	 */
	public static function gatewayLog($gateway = "", $data = "", $result = "")
	{
		$idata = ["create_time" => time(), "gateway" => $gateway, "data" => $data, "result" => $result];
		\think\Db::name("gateway_log")->insertGetId($idata);
	}
}