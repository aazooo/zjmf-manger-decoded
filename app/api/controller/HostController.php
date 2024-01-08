<?php

namespace app\api\controller;

class HostController
{
	/**
	 * @time 2020-05-28
	 * @title 修改DCIM服务器
	 * @description 修改DCIM服务器
	 * @url /api/host
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:dcim服务器ID
	 * @param   .name:hostid type:int require:0 desc:产品ID
	 * @param   .name:username type:string require:0 desc:操作系统用户
	 * @param   .name:password type:string require:0 desc:操作系统密码
	 * @param   .name:port type:int require:0 desc:端口
	 * @param   .name:mainip type:string require:0 desc:主IP
	 * @param   .name:assignedips type:string require:0 desc:分配IP
	 * @param   .name:os type:string require:0 desc:操作系统名称
	 * @param   .name:dcim_os type:int require:0 desc:操作系统ID
	 */
	public function editDcimHost()
	{
		$params = input("post.");
		$id = intval($params["id"]);
		if (empty($id)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.uid,a.serverid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.dcimid", $id)->where("a.id", $params["hostid"])->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		$update = [];
		if (isset($params["username"])) {
			$update["username"] = $params["username"];
		}
		if (isset($params["password"])) {
			$update["password"] = cmf_encrypt($params["password"]);
		}
		if (isset($params["port"])) {
			$update["port"] = \intval($params["port"]);
		}
		if (isset($params["mainip"])) {
			$update["dedicatedip"] = $params["mainip"] ?? "";
		}
		if (isset($params["assignedips"])) {
			$arr = explode(",", $params["assignedips"]) ?? [];
			$dcim_server = \think\Db::name("dcim_servers")->where("serverid", $host["serverid"])->find();
			$dcim_server["auth"] = json_decode($dcim_server["auth"], true);
			if ($dcim_server["auth"]["enable_ip_custom"] != "on") {
				foreach ($arr as $k => $v) {
					if (empty($v) || $v == $params["mainip"]) {
						unset($arr[$k]);
					}
				}
			}
			$update["assignedips"] = implode(",", $arr);
		}
		if (isset($params["os"])) {
			$update["os"] = $params["os"];
		}
		if (isset($params["dcim_os"])) {
			$update["dcim_os"] = intval($params["dcim_os"]);
		}
		if (isset($params["ippassword"])) {
			$dcim = new \app\common\logic\Dcim();
			$dcim->savePanelPass($params["hostid"], $host["productid"], $params["ippassword"]);
		}
		if (!empty($update)) {
			active_log("API同步服务器信息成功 - Host ID:" . $host["id"], $host["uid"], 2, 1, 2);
			\think\Db::name("host")->where("id", $host["id"])->update($update);
			pushHostInfo($host["id"]);
		}
		$result["status"] = 200;
		$result["msg"] = lang("EDIT SUCCESS");
		return json($result);
	}
	public function getDcimServerSetting()
	{
		$params = input("post.");
		$id = intval($params["id"]);
		if (empty($id)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.uid,a.serverid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.dcimid", $id)->where("a.id", $params["hostid"])->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		$dcim_server = \think\Db::name("dcim_servers")->where("serverid", $host["serverid"])->find();
		$dcim_server["auth"] = json_decode($dcim_server["auth"], true);
		if ($dcim_server["auth"]["enable_ip_custom"] == "on") {
			$result["status"] = 200;
			$result["data"] = $dcim_server["ip_customid"];
		} else {
			$result["status"] = 400;
			$result["msg"] = "off";
		}
		return json($result);
	}
	/**
	 * 时间 2020-07-13
	 * @title 空闲DCIM服务器
	 * @desc 空闲DCIM服务器
	 * @url /api/host/free
	 * @method  POST
	 * @author hh
	 * @version v1
	 * @param   .name:id type:array require:1 desc:hostid
	 */
	public function freeDcimHost()
	{
		$id = array_filter(input("post.id"), function ($x) {
			return is_numeric($x) && $x > 0;
		});
		if (!is_array($id) || empty($id)) {
			$result["status"] = 400;
			$result["msg"] = "ID错误";
			$description = "API删除设备失败, 原因:ID错误";
			active_log($description, 0, 2);
			return json($result);
		}
		$product = \think\Db::name("host")->alias("a")->field("a.id,a.uid,a.productid")->leftJoin("products b", "a.productid=b.id")->where("b.type", "dcim")->where("a.dcimid", ">", 0)->whereIn("a.id", $id)->select()->toArray();
		if (empty($product)) {
			$result["status"] = 400;
			$result["msg"] = "服务器ID错误";
			$description = "API删除设备失败, 原因:ID错误";
			active_log($description, 0, 2);
			return json($result);
		}
		$update = ["dcimid" => 0, "reinstall_info" => "", "show_last_act_message" => 1, "dedicatedip" => "", "assignedips" => "", "username" => "", "password" => "", "port" => 0, "dcim_area" => 0];
		\think\Db::name("host")->whereIn("id", array_column($product, "id"))->update($update);
		$dcim = new \app\common\logic\Dcim();
		$dcim->savePanelPass($id, $product["productid"], "");
		$result["status"] = 200;
		$result["msg"] = "删除成功";
		foreach ($product as $v) {
			$description = sprintf("API删除设备成功 - Host ID:%d", $v["id"]);
			active_log($description, $v["uid"], 2, 1, 2);
			pushHostInfo($v["id"]);
		}
		return json($result);
	}
	/**
	 * 时间 2020-08-18
	 * @title 同步信息
	 * @desc 同步信息(基于上游推送)
	 * @url /api/host/sync
	 * @method  POST
	 * @author hh
	 * @version v1
	 * @param   .name:id type:int require:1 desc:hostid
	 * @param   .name:domain type:string require:1 desc:主机名
	 * @param   .name:domainstatus type:string require:1 desc:产品状态
	 * @param   .name:username type:string require:1 desc:用户名
	 * @param   .name:password type:string require:1 desc:密码
	 * @param   .name:dedicatedip type:string require:1 desc:主IP
	 * @param   .name:assignedips type:string require:1 desc:分配IP
	 * @param   .name:port type:int require:1 desc:端口
	 * @param   .name:signature type:string require:1 desc:签名
	 * @param   .name:rand_str type:string require:1 desc:随机串
	 */
	public function syncInfo()
	{
		$params = input("post.");
		$id = \intval($params["id"]);
		if (empty($params["signature"])) {
			$result["status"] = 400;
			$result["msg"] = "签名错误";
			return json($result);
		}
		$host = \think\Db::name("host")->alias("a")->field("a.port,a.stream_info")->field("a.id,a.uid,a.productid,a.domainstatus,a.regdate,b.welcome_email,b.type,c.email,a.billingcycle,b.pay_type,b.name,a.nextduedate,a.billingcycle,a.dedicatedip,a.username,a.password,a.os,a.assignedips,a.create_time")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		$stream_info = json_decode($host["stream_info"], true);
		$token = $stream_info["token"];
		if (empty($token)) {
			$result["status"] = 400;
			$result["msg"] = "该产品不能使用该接口";
			return json($result);
		}
		$params["password"] = html_entity_decode($params["password"], ENT_QUOTES);
		$params["token"] = $token;
		if (!validateSign($params, $params["signature"])) {
			$result["status"] = 400;
			$result["msg"] = "签名验证失败";
			return json($result);
		}
		$update = ["domain" => $params["domain"], "username" => $params["username"], "password" => cmf_encrypt($params["password"]), "os" => $params["os"], "os_url" => $params["os_url"] ?: "", "dedicatedip" => $params["dedicatedip"], "assignedips" => $params["assignedips"], "port" => \intval($params["port"]), "suspendreason" => $params["suspendreason"]];
		if (!empty($params["nextduedate"])) {
			$update["nextduedate"] = $params["nextduedate"];
		}
		if (isset($params["domainstatus"]) && in_array($params["domainstatus"], ["Pending", "Active", "Cancelled", "Fraud", "Deleted", "Suspended"])) {
			$update["domainstatus"] = $params["domainstatus"];
		}
		if (isset($params["suspendreason"])) {
			$update["suspendreason"] = $params["suspendreason"];
		}
		$r = \think\Db::name("host")->where("id", $id)->update($update);
		if ($params["type"] == "create") {
			if ($host["domainstatus"] == "Active") {
				$result["status"] = 200;
				$result["msg"] = "更新成功";
				return json($result);
			}
			$logic_run_map = new \app\common\logic\RunMap();
			$data_i = [];
			$data_i["host_id"] = $params["id"];
			$data_i["active_type_param"] = [$params["id"], ""];
			$products = \think\Db::name("products")->where("id", $host["productid"])->find();
			$server_groups = \think\Db::name("server_groups")->where("id", $products["server_group"])->find();
			$servers = \think\Db::name("servers")->where("id", $host["serverid"])->find();
			if ($params["domainstatus"] == "Active" && $host["domainstatus"] == "Pending") {
				$message_template_type = array_column(config("message_template_type"), "id", "name");
				$sms = new \app\common\logic\Sms();
				$client = check_type_is_use($message_template_type[strtolower("Default_Product_Welcome")], $host["uid"], $sms);
				if ($client) {
					$billing_cycle = config("billing_cycle");
					if ($billing_cycle[$host["billingcycle"]] == "免费" || $billing_cycle[$host["billingcycle"]] == "一次性") {
						$time = "不到期";
					} else {
						$time = date("Y-m-d H:i:s", $host["nextduedate"]);
					}
					$params = ["product_name" => $host["name"], "product_mainip" => $host["dedicatedip"], "product_user" => $host["username"], "product_passwd" => cmf_decrypt($host["password"]), "product_dcimbms_os" => $host["os"], "product_addonip" => $host["assignedips"], "product_first_time" => date("Y-m-d H:i:s", $host["create_time"]), "product_end_time" => $time, "product_binlly_cycle" => $billing_cycle[$host["billingcycle"]]];
					$params["product_mainip"] .= $host["port"] ? ":" . $host["port"] : "";
					$sms->sendSms($message_template_type[strtolower("Default_Product_Welcome")], $client["phone_code"] . $client["phonenumber"], $params, false, $host["uid"]);
				}
				if ($host["welcome_email"] > 0) {
					$email = new \app\common\logic\Email();
					$email->sendEmail($host["welcome_email"], $id, !empty($ip) ? $ip : get_client_ip6());
				}
				active_log_final(sprintf("开通host - User ID:%d - Host ID:%d - 服务器模块:%s - 接口:%s - IP:%s - 成功", $host["uid"], $id, $server_groups["name"], $servers["name"], $host["dedicatedip"]), $host["uid"], 2, $id);
				$data_i["description"] = "订单 - 开通 Host ID:{$data_i["host_id"]}的产品成功";
				$logic_run_map->saveMap($data_i, 1, 400, 1);
			} else {
				if ($params["domainstatus"] == "Pending") {
					active_log_final(sprintf("模块命令:开通host - User ID:%d - Host ID:%d - 失败 - 原因：%s", $host["uid"], $id, "上游开通失败"), $host["uid"], 2, $id);
					$data_i["description"] = "订单 - 开通 Host ID:{$data_i["host_id"]}的产品失败。原因:上游开通失败";
					$logic_run_map->saveMap($data_i, 0, 400, 1);
					$result["status"] = 200;
					$result["msg"] = "更新成功";
					return json($result);
				} else {
					$result["status"] = 400;
					$result["msg"] = "更新失败";
					return json($result);
				}
			}
			pushHostInfo($id, "domainstatus", "create");
		}
		if (isset($params["ippassword"])) {
			$dcim = new \app\common\logic\Dcim();
			$dcim->savePanelPass($id, $host["productid"], $params["ippassword"]);
		}
		if (!empty($stream_info["downstream_url"])) {
			if ($params["type"] == "create" && $params["domainstatus"] == "Active") {
				pushHostInfo($id, "domainstatus", "create");
			} else {
				pushHostInfo($id);
			}
		}
		if ($r) {
			$desc = "";
			$desc_arr = ["domain" => "主机名", "domainstatus" => "状态", "username" => "用户名", "password" => "密码", "dedicatedip" => "IP地址", "assignedips" => "其他IP", "port" => "端口", "os" => "操作系统", "suspendreason" => "暂停原因"];
			$old = $host;
			$old["password"] = cmf_decrypt($old["password"]);
			$old["domainstatus"] = config("domainstatus")[$old["domainstatus"]];
			$update["password"] = $params["password"];
			$update["domainstatus"] = config("domainstatus")[$update["domainstatus"]];
			$desc = "";
			foreach ($update as $k => $v) {
				if ($old[$k] != $v && !empty($desc_arr[$k])) {
					if ($k == "password") {
						$desc .= ",密码变更";
					} else {
						$desc .= sprintf(",%s由%s修改为%s", $desc_arr[$k], $old[$k] ?: "空", $v ?: "空");
					}
				}
			}
			if (!empty($desc)) {
				$description = sprintf("API上游服务器信息更新成功%s - Host ID:%d", $desc, $id);
				active_log($description, $host["uid"], 2, 1, 2);
			}
		}
		$result["status"] = 200;
		$result["msg"] = "更新成功";
		return json($result);
	}
	/**
	 * 时间 2020-12-17
	 * @title 同步回复
	 * @desc 同步回复(基于上游推送)
	 * @url /api/ticket_reply/sync
	 * @method  POST
	 * @author xujin
	 * @version v1
	 * @param   .name:tid type:string require:1 desc:工单id
	 * @param   .name:content type:string require:1 desc:回复内容
	 */
	public function syncTicketReply()
	{
		$params = input("post.");
		if (empty($params["content"])) {
			$result["status"] = 406;
			$result["msg"] = lang("TICKET_REPLY_CONTENT_EMPTY");
			return jsonrule($result);
		}
		$tid = $params["tid"];
		$info = \think\Db::name("ticket")->where("upstream_tid", $tid)->where("merged_ticket_id", 0)->find();
		$id = $info["id"];
		if (empty($tid) || empty($info)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		$upload = new \app\common\logic\Upload();
		foreach ($params["attachment"] as $v) {
			$tmp = $upload->moveTo($v, config("ticket_attachments"));
			if (isset($tmp["error"])) {
				return jsonrule(["status" => 400, "msg" => $tmp["error"]]);
			}
		}
		$admin = cmf_get_current_admin_id();
		$admin_info = \think\Db::name("user")->field("user_login")->where("id", $admin)->find();
		$data["tid"] = $id;
		$data["admin_id"] = $admin;
		$data["create_time"] = time();
		$data["content"] = model("Ticket")->parse($params["content"], model("Ticket")->getUser());
		$data["admin"] = $admin_info["user_login"] ?: "";
		$data["attachment"] = implode(",", $params["attachment"]) ?? "";
		$data["is_receive"] = 1;
		$data["source"] = 1;
		\think\Db::startTrans();
		try {
			\think\Db::name("ticket")->where("id", $id)->update(["last_reply_time" => time(), "client_unread" => 1, "status" => 2]);
			$r = \think\Db::name("ticket_reply")->insertGetId($data);
			\think\Db::commit();
			pushTicketReply($r);
			$result["status"] = 200;
			$result["msg"] = lang("TICKET_REPLY_SUCCESS");
		} catch (\Exception $e) {
			\think\Db::rollback();
			$result["status"] = 406;
			$result["msg"] = lang("TICKET_REPLY_FAILED");
		}
		if ($result["status"] == 200) {
			$arr_admin = ["relid" => $id, "name" => "工单已回复提醒", "type" => "support", "sync" => true, "admin" => false, "ip" => get_client_ip6()];
			$curl_multi_data[0] = ["url" => "async", "data" => $arr_admin];
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			$sms = new \app\common\logic\Sms();
			$client = check_type_is_use($message_template_type["ticket_reply"], $info["uid"], $sms);
			if ($client) {
				$params = ["subject" => $info["title"]];
				$arr = ["name" => $message_template_type[strtolower("ticket_reply")], "phone" => $client["phone_code"] . $client["phonenumber"], "params" => json_encode($params), "sync" => false, "uid" => $info["uid"], "delay_time" => 0, "is_market" => false];
				$curl_multi_data[count($curl_multi_data)] = ["url" => "async_sms", "data" => $arr];
			}
			asyncCurlMulti($curl_multi_data);
			active_log(sprintf($this->lang["Ticket_admin_reply"], $info["uid"], $id, $info["title"]), $info["uid"]);
			model("SystemLog")->log(lang("TICKET_REPLY_SUCCESS_LOG", [$info["tid"]]), "ticket", $id);
			$hook_data = ["tid" => $id, "content" => $data["content"], "attachment" => $params["attachment"]];
			hook("ticket_reply_admin", $hook_data);
		} else {
			model("SystemLog")->log(lang("TICKET_REPLY_FAILED_LOG", [$info["tid"]]), "ticket", $id);
		}
		return json($result);
	}
	/**
	 * 时间 2020-12-17
	 * @title 回复工单
	 * @desc 回复工单
	 * @url /api/ticket_reply
	 * @method  POST
	 * @author xujin
	 * @version v1
	 * @param   .name:id type:string require:1 desc:工单id
	 * @param   .name:content type:string require:1 desc:回复内容
	 * @param   .name:attachment type:file require:1 desc:附件
	 * @param   .name:token type:string require:1 desc:工单token
	 */
	public function replyTicket()
	{
		$params = input("post.");
		if (empty($params["content"])) {
			$result["status"] = 406;
			$result["msg"] = lang("TICKET_REPLY_CONTENT_EMPTY");
			return jsonrule($result);
		}
		$tid = $params["id"];
		$info = \think\Db::name("ticket")->where("id", $tid)->where("merged_ticket_id", 0)->find();
		$id = $info["id"];
		if (empty($tid) || empty($info) || empty($info["token"])) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		if (empty($params["token"]) || $info["token"] != $params["token"]) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		$upload = new \app\common\logic\Upload();
		$data["attachment"] = "";
		foreach ($params["attachment"] as $v) {
			$tmp = $upload->moveTo($v, config("ticket_attachments"));
			if (isset($tmp["error"])) {
				return jsonrule(["status" => 400, "msg" => $tmp["error"]]);
			}
			$data["attachment"] = implode(",", $tmp);
		}
		$data["tid"] = $id;
		$data["admin_id"] = 1;
		$data["create_time"] = time();
		$data["content"] = model("Ticket")->parse($params["content"], model("Ticket")->getUser());
		$data["admin"] = "admin";
		\think\Db::startTrans();
		try {
			\think\Db::name("ticket")->where("id", $id)->update(["last_reply_time" => time(), "client_unread" => 1, "status" => 2]);
			$r = \think\Db::name("ticket_reply")->insert($data);
			\think\Db::commit();
			pushTicketReply($r);
			$result["status"] = 200;
			$result["msg"] = lang("TICKET_REPLY_SUCCESS");
		} catch (\Exception $e) {
			\think\Db::rollback();
			$result["status"] = 406;
			$result["msg"] = lang("TICKET_REPLY_FAILED");
		}
		if ($result["status"] == 200) {
			$arr_admin = ["relid" => $id, "name" => "工单已回复提醒", "type" => "support", "sync" => true, "admin" => false, "ip" => get_client_ip6()];
			$curl_multi_data[0] = ["url" => "async", "data" => $arr_admin];
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			$sms = new \app\common\logic\Sms();
			$client = check_type_is_use($message_template_type["ticket_reply"], $info["uid"], $sms);
			if ($client) {
				$params = ["subject" => $info["title"]];
				$arr = ["name" => $message_template_type[strtolower("ticket_reply")], "phone" => $client["phone_code"] . $client["phonenumber"], "params" => json_encode($params), "sync" => false, "uid" => $info["uid"], "delay_time" => 0, "is_market" => false];
				$curl_multi_data[count($curl_multi_data)] = ["url" => "async_sms", "data" => $arr];
			}
			asyncCurlMulti($curl_multi_data);
			active_log(sprintf($this->lang["Ticket_admin_reply"], $info["uid"], $id, $info["title"]), $info["uid"]);
			model("SystemLog")->log(lang("TICKET_REPLY_SUCCESS_LOG", [$info["tid"]]), "ticket", $id);
			$hook_data = ["tid" => $id, "content" => $data["content"], "attachment" => $params["attachment"]];
			hook("ticket_reply_admin", $hook_data);
		} else {
			model("SystemLog")->log(lang("TICKET_REPLY_FAILED_LOG", [$info["tid"]]), "ticket", $id);
		}
		return json($result);
	}
	/**
	 * 时间 2021-01-11
	 * @title 执行service module方法
	 * @desc 执行service module方法
	 * @url /api/exec_module_func
	 * @method  GET|POST
	 * @author hh
	 * @version v1
	 * @param   .name:module_name type:string require:1 desc:模块名称
	 * @param   .name:module_func type:string require:1 desc:模块方法
	 */
	public function execProvision(\think\Request $request)
	{
		$module_name = $request->get("module_name");
		if (empty($module_name)) {
			return json(["status" => 400, "msg" => "模块名不能为空"]);
		}
		if (!preg_match("/^[a-zA-Z0-9_\\-]+\$/", $module_name)) {
			return json(["status" => 400, "msg" => "模块名含有非法字符"]);
		}
		$module_func = $request->get("module_func");
		if (empty($module_func)) {
			return json(["status" => 400, "msg" => "模块方法不能为空"]);
		}
		$ban = ["createaccount", "suspendaccount", "unsuspendaccount", "terminateaccount", "renew", "changepackage", "on", "off", "reboot", "hardoff", "hardreboot", "reinstall", "crackpassword", "rescuesystem", "vnc", "sync", "status", "managepanel", "chartdata", "usageupdate", "flowpacketpaid", "testlink", "trafficusage", "createticket", "replyticket", "clientbutton", "adminbutton", "chart", "fiveminutecron", "dailycron", "adminbuttonhide", "allowfunction", "clientareaoutput", "clientarea", "apiauth"];
		if (in_array(strtolower($module_func), $ban)) {
			return json(["status" => 400, "msg" => "不能使用该方法"]);
		}
		$func = $module_name . "_" . $module_func;
		if (function_exists($func)) {
			return json(["status" => 400, "msg" => "方法不属于模块"]);
		}
		$provision = new \app\common\logic\Provision();
		if ($provision->checkAndRequire($module_name)) {
			if (!function_exists($module_name . "_ApiAuth")) {
				return json(["status" => 400, "msg" => "未实现鉴权方法"]);
			}
			$r = call_user_func($module_name . "_ApiAuth", ["func" => $module_func]);
			if ($r) {
				if (function_exists($func)) {
					call_user_func($func);
				} else {
					return json(["status" => 400, "msg" => "方法不存在"]);
				}
			} else {
				return json(["status" => 400, "msg" => "鉴权错误"]);
			}
		} else {
			return json(["status" => 400, "msg" => "模块不存在"]);
		}
	}
}