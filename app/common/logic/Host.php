<?php

namespace app\common\logic;

class Host
{
	public $is_admin = false;
	public $lang;
	public function initialize()
	{
		$this->lang = get_system_langs();
	}
	/**
	 * 作者: huanghao
	 * 时间: 2019-12-13
	 * 开通
	 * @param  int    $id hostid
	 * @param  string $ip 客户端ip地址:异步开通,socket需要
	 * @return [type]     [description]
	 */
	public function create($id, $ip = "")
	{
		if (configuration("shd_allow_auto_create_queue")) {
			\app\queue\job\AutoCreate::push(["hid" => $id, "ip" => $ip]);
			return ["status" => 200, "msg" => lang("MODULE_CREATE_SUCCESS")];
		}
		return $this->createFinal($id, $ip);
	}
	public function createFinal($id, $ip = "")
	{
		$host = \think\Db::name("host")->alias("a")->field("a.orderid,b.server_group,a.id,a.uid,a.productid,a.domainstatus,a.regdate,a.dcimid,b.welcome_email,b.type,c.email,a.billingcycle,b.pay_type,b.name,a.nextduedate,a.billingcycle,a.dedicatedip,a.domain,a.username,a.password,a.os,a.assignedips,a.create_time,a.stream_info,b.api_type,b.zjmf_api_id,b.upstream_pid,b.server_group")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$cache_key = "HOST_DEFAULT_ACTION_CREATE_" . $id;
		if (cache($cache_key)) {
			$result["status"] = 406;
			$result["msg"] = "产品正在开通中";
			return $result;
		}
		cache($cache_key, 1, 300);
		$provision = new Provision();
		$provision->is_admin = $this->is_admin;
		$hook_data = $provision->getParams($id);
		$hook_res = hook("before_module_create", ["params" => $hook_data]);
		if (!empty($hook_res)) {
			foreach ($hook_res as $v) {
				if (is_array($v)) {
					$hook_data = array_merge($hook_data, $v);
				}
			}
			if ($hook_data["exit_module"]) {
				cache($cache_key, null);
				$result["status"] = 406;
				$result["msg"] = "当前操作被HOOK代码中断";
				return $result;
			}
		}
		if ($host["api_type"] == "zjmf_api") {
			$module_res = zjmfCurl($host["zjmf_api_id"], "/user_info", [], 30, "GET");
			if ($module_res["status"] == 200) {
				$token = md5(randStr(16) . time() . $id);
				$downstream_url = getDomain() . getRootUrl();
				$stream_info = \think\Db::name("host")->where("id", $id)->value("stream_info");
				$stream_info = json_decode($stream_info, true) ?: [];
				if (!empty($stream_info["token"])) {
					$token = $stream_info["token"];
				} else {
					$update["stream_info"] = $stream_info;
					$update["stream_info"]["token"] = $token;
					$update["stream_info"] = json_encode($update["stream_info"]);
					\think\Db::name("host")->where("id", $id)->update($update);
				}
				$post_data = [];
				$post_data["downstream_url"] = $downstream_url;
				$post_data["downstream_token"] = $token;
				$post_data["downstream_id"] = $id;
				$module_res2 = zjmfCurl($host["zjmf_api_id"], "/cart/clear", $post_data);
				if ($module_res2["status"] == 200) {
					if (!empty($module_res2["hostid"])) {
						$update["dcimid"] = $module_res2["hostid"];
						\think\Db::name("host")->where("id", $id)->update($update);
					}
					if (!empty($module_res2["invoiceid"])) {
						$post_data = [];
						$post_data["invoiceid"] = $module_res2["invoiceid"];
						$post_data["use_credit"] = 1;
						$post_data["enough"] = 1;
						$post_data["downstream_url"] = $downstream_url;
						$post_data["downstream_token"] = $token;
						$post_data["downstream_id"] = $id;
						$module_res = zjmfCurl($host["zjmf_api_id"], "/apply_credit", $post_data);
						if ($module_res["status"] == 1001) {
							$module_res["status"] = 200;
							$hostid = $module_res["data"]["hostid"][0];
							$update["dcimid"] = $hostid;
							\think\Db::name("host")->where("id", $id)->update($update);
						} elseif ($module_res["status"] == 200) {
							$module_res["status"] = 400;
						}
					} else {
						$post_data["pid"] = $host["upstream_pid"];
						$post_data["billingcycle"] = $host["billingcycle"];
						$post_data["host"] = $host["domain"];
						$post_data["password"] = cmf_decrypt($host["password"]);
						$post_data["currencyid"] = $module_res["user"]["currency"];
						$post_data["qty"] = 1;
						$configoption = \think\Db::name("host_config_options")->alias("a")->field("a.qty,b.option_type,b.upstream_id config_upstream,c.upstream_id")->leftJoin("product_config_options b", "a.configid=b.id")->leftJoin("product_config_options_sub c", "a.optionid=c.id")->where("a.relid", $id)->where("b.upstream_id", ">", 0)->where("c.upstream_id", ">", 0)->select()->toArray();
						foreach ($configoption as $v) {
							if (judgeQuantity($v["option_type"])) {
								$post_data["configoption"][$v["config_upstream"]] = $v["qty"];
							} else {
								$post_data["configoption"][$v["config_upstream"]] = $v["upstream_id"];
							}
						}
						$customfield = \think\Db::name("customfieldsvalues")->alias("a")->field("b.upstream_id,a.value")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.relid", $host["productid"])->select()->toArray();
						$post_data["customfield"] = array_column($customfield, "value", "upstream_id");
						$cart_data = $post_data;
						$module_res = zjmfCurl($host["zjmf_api_id"], "/cart/add_to_shop", $post_data);
						if ($module_res["status"] == 200) {
							$post_data = [];
							$post_data["downstream_url"] = $downstream_url;
							$post_data["downstream_token"] = $token;
							$post_data["downstream_id"] = $id;
							$api = \think\Db::name("zjmf_finance_api")->where("id", $host["zjmf_api_id"])->find();
							if ($api["is_resource"] == 1) {
								$post_data["agent_client"] = 1;
							}
							$cart_data["configoptions"] = $cart_data["configoption"];
							unset($cart_data["configoption"]);
							$post_data["cart_data"] = $cart_data;
							$module_res = zjmfCurl($host["zjmf_api_id"], "/cart/settle", $post_data);
							if ($module_res["status"] == 200) {
								if (!empty($module_res["data"]["hostid"][0])) {
									$update["dcimid"] = $module_res["data"]["hostid"][0];
									\think\Db::name("host")->where("id", $id)->update($update);
								}
								$post_data = [];
								$post_data["invoiceid"] = $module_res["data"]["invoiceid"];
								$post_data["use_credit"] = 1;
								$post_data["enough"] = 0;
								$post_data["downstream_url"] = $downstream_url;
								$post_data["downstream_token"] = $token;
								$post_data["downstream_id"] = $id;
								$invoiceid = $module_res["data"]["invoiceid"];
								$module_res = zjmfCurl($host["zjmf_api_id"], "/apply_credit", $post_data);
								if ($module_res["status"] == 1001) {
									$module_res["status"] = 200;
									$hostid = $module_res["data"]["hostid"][0];
									$update["dcimid"] = $hostid;
									\think\Db::name("host")->where("id", $id)->update($update);
								} else {
									$credit_limit = ["invoiceid" => $invoiceid, "use_credit_limit" => 1, "enough" => 0, "downstream_url" => $downstream_url, "downstream_token" => $token, "downstream_id" => $id];
									$module_res = zjmfCurl($host["zjmf_api_id"], "/apply_credit_limit", $credit_limit);
									if ($module_res["status"] == 1001) {
										$module_res["status"] = 200;
										$hostid = $module_res["data"]["hostid"][0];
										$update["dcimid"] = $hostid;
										\think\Db::name("host")->where("id", $id)->update($update);
									} else {
										$credit_unuse = ["invoiceid" => $invoiceid, "use_credit" => 0];
										$now_msg = $module_res["msg"];
										$module_res = zjmfCurl($host["zjmf_api_id"], "/apply_credit", $credit_unuse);
										if ($module_res["status"] != 200) {
											$module_res = zjmfCurl($host["zjmf_api_id"], "/apply_credit", $credit_unuse);
										}
										$module_res["status"] = 400;
										$module_res["msg"] = $now_msg;
									}
								}
							} elseif ($module_res["status"] == 1001) {
								$module_res["status"] = 200;
								$hostid = $module_res["data"]["hostid"][0];
								$update["dcimid"] = $hostid;
								\think\Db::name("host")->where("id", $id)->update($update);
							}
						}
					}
				} elseif ($module_res2["status"] == 400) {
					$module_res = $module_res2;
					if (!empty($module_res2["hostid"]) && $module_res2["domainstatus"] == "Active") {
						$update["dcimid"] = $module_res2["hostid"];
						$update["domainstatus"] = $module_res2["domainstatus"];
						\think\Db::name("host")->where("id", $id)->update($update);
						$module_res["status"] == 200;
					}
				}
			}
			if ($module_res["status"] == 400) {
				$module_res["msg"] = "上游：" . $module_res["msg"];
			}
		} elseif ($host["api_type"] == "resource") {
			if (function_exists("resourceHostCreate")) {
				$pid = $host["productid"];
				$module_res = resourceHostCreate($id);
				$hostid = intval($module_res["hostid"]);
				if (!empty($module_res["hostid"]) && $module_res["domainstatus"] == "Active") {
					$update["dcimid"] = $module_res["hostid"];
					$update["domainstatus"] = $module_res["domainstatus"];
					\think\Db::name("host")->where("id", $id)->update($update);
					$module_res["status"] == 200;
				}
				if ($module_res["status"] == 400) {
					$module_res["msg"] = "资源池：" . $module_res["msg"];
				}
			}
		} else {
			if (!empty($host["server_group"])) {
				if ($host["type"] == "dcim") {
					$dcim = new Dcim();
					$dcim->is_admin = $this->is_admin;
					$module_res = $dcim->createAccount($id);
				} elseif ($host["type"] == "dcimcloud") {
					$dcimcloud = new DcimCloud();
					$dcimcloud->is_admin = true;
					$module_res = $dcimcloud->createAccount($id);
				} else {
					$module_res = $provision->createAccount($id);
				}
			} else {
				$module_res = ["status" => 200];
			}
		}
		if ($module_res["status"] == "success" || $module_res["status"] == 200) {
			hook("after_module_create", ["params" => $hook_data]);
			$host1 = \think\Db::name("host")->alias("a")->field("a.port")->field("a.id,a.uid,a.productid,a.domainstatus,a.regdate,b.welcome_email,b.type,c.email,a.billingcycle,b.pay_type,b.name,a.nextduedate,a.billingcycle,a.dedicatedip,a.username,a.password,a.os,a.assignedips,a.create_time")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
			$result["status"] = 200;
			$result["msg"] = $module_res["msg"] ?? lang("MODULE_CREATE_SUCCESS");
			if ($host["api_type"] == "resource") {
				$post_data = [];
				$post_data["host_id"] = $hostid;
				$module_res = resourceCurl($pid, "/host/header", $post_data, 30, "GET");
				if ($module_res["status"] == 200) {
					$current_rate = \think\Db::name("host")->alias("a")->leftJoin("res_products b", "a.productid=b.productid")->where("a.id", $id)->value("b.current_rate");
					\think\Db::name("invoices")->alias("a")->leftJoin("orders b", "b.invoiceid=a.id")->where("b.id", $host["orderid"])->update(["cost" => bcmul($module_res["data"]["host_data"]["order_amount"], $current_rate, 20)]);
				}
			}
			if (!in_array($host["api_type"], ["zjmf_api", "resource"])) {
				$message_template_type = array_column(config("message_template_type"), "id", "name");
				$sms = new Sms();
				$client = check_type_is_use($message_template_type[strtolower("Default_Product_Welcome")], $host1["uid"], $sms);
				if ($client) {
					$billing_cycle = config("billing_cycle");
					if ($billing_cycle[$host1["billingcycle"]] == "免费" || $billing_cycle[$host1["billingcycle"]] == "一次性") {
						$time = "不到期";
					} else {
						$time = date("Y-m-d H:i:s", $host1["nextduedate"]);
					}
					$params = ["product_name" => $host1["name"], "product_mainip" => $host1["dedicatedip"], "product_user" => $host1["username"], "product_passwd" => cmf_decrypt($host1["password"]), "product_dcimbms_os" => $host1["os"], "product_addonip" => $host1["assignedips"], "product_first_time" => date("Y-m-d H:i:s", $host1["create_time"]), "product_end_time" => $time, "product_binlly_cycle" => $billing_cycle[$host1["billingcycle"]]];
					$params["product_mainip"] .= $host1["port"] ? ":" . $host1["port"] : "";
					$sms->sendSms($message_template_type[strtolower("Default_Product_Welcome")], $client["phone_code"] . $client["phonenumber"], $params, false, $host1["uid"]);
				}
				\think\Db::name("host")->where("id", $id)->update(["domainstatus" => "Active"]);
				if ($host["welcome_email"] > 0) {
					$email = new Email();
					$email->sendEmail($host["welcome_email"], $id, !empty($ip) ? $ip : get_client_ip6());
				}
				$host = \think\Db::name("host")->alias("a")->field("a.id,a.uid,a.dedicatedip,a.serverid,b.server_group")->leftJoin("products b", "a.productid=b.id")->where("a.id", $id)->find();
				$server_groups = \think\Db::name("server_groups")->where("id", $host["server_group"])->find();
				$servers = \think\Db::name("servers")->where("id", $host["serverid"])->find();
				active_log_final(sprintf("开通host - User ID:%d - Host ID:%d - 服务器模块:%s - 接口:%s - IP:%s - 成功", $host["uid"], $id, $server_groups["name"], $servers["name"], $host["dedicatedip"]), $host["uid"], 2, $id);
				pushHostInfo($id, "domainstatus", "create");
			}
		} else {
			hook("after_module_create_failed", ["params" => $hook_data, "msg" => $module_res["msg"]]);
			if (!empty($host["server_group"])) {
				active_log_final(sprintf("模块命令:开通host - User ID:%d - Host ID:%d - 失败 - 原因：%s", $host["uid"], $id, $module_res["msg"]), $host["uid"], 2, $id);
			}
			pushHostInfo($id, "domainstatus", "create");
			$result["status"] = 406;
			$result["msg"] = $module_res["msg"];
		}
		cache($cache_key, null);
		return $result;
	}
	/**
	 * 作者: huanghao
	 * 时间: 2019-12-13
	 * 暂停
	 * @param  int     $id     hostid
	 * @param  string  $reason 暂停原因
	 * @param  int     $send   是否发送暂停邮件
	 * @return [type]          [description]
	 */
	public function suspend($id, $reason_type = "other", $reason = "", $send = 0)
	{
		$host = \think\Db::name("host")->alias("a")->field("a.nextduedate,a.id,a.productid,a.domainstatus,a.dcimid,b.server_type,b.type,c.email,b.name,a.uid,a.domain,a.nextinvoicedate,b.api_type,b.zjmf_api_id,b.server_group,a.dedicatedip")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if (!$this->is_admin) {
			if (request()->uid != $host["uid"]) {
				$result["status"] = 406;
				$result["msg"] = "不能执行该操作";
				return $result;
			}
		}
		if ($reason_type == "self" && $host["domainstatus"] != "Active") {
			if (request()->is_api && $host["domainstatus"] == "Suspended") {
				$result["status"] = 200;
				$result["msg"] = lang("MODULE_SUSPEND_SUCCESS");
				return $result;
			}
			$result["status"] = 406;
			$result["msg"] = "只有激活和暂停状态才能暂停";
			return $result;
		}
		$provision = new Provision();
		$provision->is_admin = $this->is_admin;
		$hook_data = $provision->getParams($id);
		$hook_res = hook("before_module_suspend", ["params" => $hook_data]);
		if (!empty($hook_res)) {
			foreach ($hook_res as $v) {
				if (is_array($v)) {
					$hook_data = array_merge($hook_data, $v);
				}
			}
			if ($hook_data["exit_module"]) {
				$result["status"] = 406;
				$result["msg"] = "当前操作被HOOK代码中断";
				return $result;
			}
		}
		if ($host["api_type"] == "zjmf_api") {
			$post_data["id"] = $host["dcimid"];
			$post_data["func"] = "suspend";
			$post_data["reason"] = $reason ?: "代理商暂停";
			$post_data["is_api"] = 1;
			$module_res = zjmfCurl($host["zjmf_api_id"], "/provision/default", $post_data);
		} elseif ($host["api_type"] == "resource") {
			if (function_exists("resourceCurl")) {
				$post_data["id"] = $host["dcimid"];
				$post_data["func"] = "suspend";
				$post_data["reason"] = $reason ?: "代理商暂停";
				$post_data["is_api"] = 1;
				$module_res = resourceCurl($host["productid"], "/provision/default", $post_data);
			}
		} else {
			if (!empty($host["server_group"])) {
				if ($host["type"] == "dcim") {
					$dcim = new Dcim();
					$dcim->is_admin = $this->is_admin;
					$module_res = $dcim->suspend($id);
				} elseif ($host["type"] == "dcimcloud") {
					$dcimcloud = new DcimCloud();
					$dcimcloud->is_admin = $this->is_admin;
					$module_res = $dcimcloud->suspend($id, $reason_type);
				} else {
					$module_res = $provision->suspendAccount($id);
					if ($module_res["no_support_function"] == true) {
						$module_res = ["status" => 200];
					}
				}
			} else {
				$module_res = ["status" => 200];
			}
		}
		if ($module_res["status"] == "success" || $module_res["status"] == 200) {
			hook("after_module_suspend", ["params" => $hook_data]);
			$arr = ["flow" => "用量超额", "due" => "到期", "uncertifi" => "未实名认证", "self" => "代理商暂停"];
			\think\Db::name("host")->where("id", $id)->update(["domainstatus" => "Suspended", "suspendreason" => $reason_type . "-" . $reason, "suspend_time" => time()]);
			if ($send) {
				$email = new Email();
				$email->sendEmailBase($id, "Service_Suspension_Notification", "product", true);
				$admin = getReceiveAdmin();
			}
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			$sms = new Sms();
			$client = check_type_is_use($message_template_type[strtolower("host_suspend")], $host["uid"], $sms);
			if ($client) {
				$params = ["product_name" => $host["name"], "hostname" => $host["domain"], "description" => $reason, "product_end_time" => date("Y-m-d H:i:s", $host["nextduedate"]), "product_mainip" => $host["dedicatedip"] ?: ""];
				$sms->sendSms($message_template_type[strtolower("host_suspend")], $client["phone_code"] . $client["phonenumber"], $params, false, $host["uid"]);
			}
			active_log_final(sprintf("模块命令:暂停host - User ID:%d - Host ID:%s - 原因：%s", $host["uid"], $id, $reason_type . "-" . $reason), $host["uid"], 2, $id);
			if ($reason_type == "flow") {
				pushHostInfo($id, "domainstatus,suspendreason");
			}
			$result["status"] = 200;
			$result["msg"] = $module_res["msg"] ?: lang("MODULE_SUSPEND_SUCCESS");
		} else {
			hook("after_module_suspend_failed", ["params" => $hook_data, "msg" => $module_res["msg"]]);
			active_log_final(sprintf("模块命令:暂停host - User ID:%d - Host ID:%s - 失败：%s", $host["uid"], $id, $module_res["msg"] . "-" . $reason), $host["uid"], 2, $id);
			$result["status"] = 406;
			$result["msg"] = $module_res["msg"];
		}
		return $result;
	}
	public function unsuspend($id, $send = 0, $type = "", $force_success = false)
	{
		$host = \think\Db::name("host")->alias("a")->field("a.port")->field("a.id,a.productid,a.domainstatus,a.dcimid,b.server_type,b.type,c.email,a.uid,a.domain,a.nextinvoicedate,a.suspendreason,b.api_type,b.zjmf_api_id,b.server_group,a.dedicatedip,b.name")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		if (!$this->is_admin) {
			if (request()->uid != $host["uid"]) {
				$result["status"] = 406;
				$result["msg"] = "不能执行该操作";
				return $result;
			}
		}
		if (!empty($type)) {
			$host["suspendreason"] = explode("-", $host["suspendreason"])[0];
			if ($host["suspendreason"] != $type) {
				$result["status"] = 400;
				$result["msg"] = "不能解除该暂停";
				return $result;
			}
		}
		$provision = new Provision();
		$provision->is_admin = $this->is_admin;
		$hook_data = $provision->getParams($id);
		$hook_res = hook("before_module_unsuspend", ["params" => $hook_data]);
		if (!empty($hook_res)) {
			foreach ($hook_res as $v) {
				if (is_array($v)) {
					$hook_data = array_merge($hook_data, $v);
				}
			}
			if ($hook_data["exit_module"]) {
				$result["status"] = 406;
				$result["msg"] = "当前操作被HOOK代码中断";
				return $result;
			}
		}
		if ($host["api_type"] == "zjmf_api") {
			$post_data["id"] = $host["dcimid"];
			$post_data["func"] = "unsuspend";
			$post_data["is_api"] = 1;
			$module_res = zjmfCurl($host["zjmf_api_id"], "/provision/default", $post_data);
		} elseif ($host["api_type"] == "resource") {
			if (function_exists("resourceCurl")) {
				$post_data["id"] = $host["dcimid"];
				$post_data["func"] = "unsuspend";
				$post_data["is_api"] = 1;
				$module_res = resourceCurl($host["productid"], "/provision/default", $post_data);
			}
		} else {
			if (!empty($host["server_group"])) {
				if ($host["type"] == "dcim") {
					$dcim = new Dcim();
					$dcim->is_admin = $this->is_admin;
					$module_res = $dcim->unsuspend($id);
				} elseif ($host["type"] == "dcimcloud") {
					$dcimcloud = new DcimCloud();
					$dcimcloud->is_admin = $this->is_admin;
					$module_res = $dcimcloud->unsuspend($id);
				} else {
					$module_res = $provision->unsuspendAccount($id);
					if ($module_res["no_support_function"] == true) {
						$module_res = ["status" => 200];
					}
				}
			} else {
				$module_res = ["status" => 200];
			}
		}
		if ($module_res["status"] == "success" || $module_res["status"] == 200 || $force_success) {
			hook("after_module_unsuspend", ["params" => $hook_data]);
			$suspendreason = explode("-", $host["suspendreason"])[0];
			if ($suspendreason == "flow" || $suspendreason == "uncertifi" || $suspendreason == "due") {
				$sms_template = "Resume_Use";
			} else {
				$sms_template = "Service_Unsuspension_Notification";
			}
			$status = \think\Db::name("host")->field("domainstatus")->where("id", $id)->find();
			\think\Db::name("host")->where("id", $id)->update(["domainstatus" => "Active", "suspendreason" => ""]);
			if ($send) {
				$email = new Email();
				$email->sendEmailBase($id, $sms_template, "product", true);
			}
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			$sms = new Sms();
			$sms_template = strtolower($sms_template);
			$client = check_type_is_use($message_template_type[$sms_template], $host["uid"], $sms);
			if ($client) {
				$params = ["product_name" => $host["name"], "product_end_time" => date("Y-m-d H:i:s", $host["nextinvoicedate"]), "product_mainip" => $host["dedicatedip"]];
				$params["product_mainip"] .= $host["port"] ? ":" . $host["port"] : "";
				$sms->sendSms($message_template_type[$sms_template], $client["phone_code"] . $client["phonenumber"], $params, false, $host["uid"]);
			}
			active_log_final(sprintf("模块命令:解除暂停成功 - Host ID:%d", $id), $host["uid"], 2, $id);
			$result["status"] = 200;
			$result["msg"] = $module_res["msg"] ?: "解除暂停成功";
			$suspendreason = explode("-", $host["suspendreason"])[0];
			if ($host["domainstatus"] == "Suspended" && ($suspendreason == "用量超额" || $suspendreason == "flow")) {
				pushHostInfo($id, "domainstatus,suspendreason");
			}
		} else {
			hook("after_module_unsuspend_failed", ["params" => $hook_data, "msg" => $module_res["msg"]]);
			active_log_final(sprintf("模块命令:解除暂停失败#Host ID:%d - 原因:%s", $id, $module_res["msg"]), $host["uid"], 2, $id);
			$result["status"] = 406;
			$result["msg"] = $module_res["msg"];
		}
		return $result;
	}
	public function terminate($id, $cron = "")
	{
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.uid,a.productid,a.domainstatus,a.dcimid,a.domain,a.dedicatedip,a.assignedips,a.username,a.password,a.os,a.port,b.name,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email,b.auto_terminate_email,b.api_type,b.zjmf_api_id,b.server_group")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return $result;
		}
		$provision = new Provision();
		$provision->is_admin = $this->is_admin;
		$hook_data = $provision->getParams($id);
		$hook_res = hook("before_module_terminate", ["params" => $hook_data]);
		if (!empty($hook_res)) {
			foreach ($hook_res as $v) {
				if (is_array($v)) {
					$hook_data = array_merge($hook_data, $v);
				}
			}
			if ($hook_data["exit_module"]) {
				$result["status"] = 406;
				$result["msg"] = "当前操作被HOOK代码中断";
				return $result;
			}
		}
		$str = "";
		if ($cron == "cron") {
			$str = "原因:产品到期后未续费,自动删除";
		}
		if ($host["api_type"] == "zjmf_api") {
			$post_data["id"] = $host["dcimid"];
			$post_data["type"] = "Immediate";
			$post_data["reason"] = "立即删除";
			$module_res = zjmfCurl($host["zjmf_api_id"], "/host/cancel", $post_data);
		} elseif ($host["api_type"] == "resource") {
			if (function_exists("resourceCurl")) {
				$post_data["id"] = $host["dcimid"];
				$post_data["type"] = "Immediate";
				$post_data["reason"] = "立即删除";
				resourceCurl($host["productid"], "/host/cancel", $post_data);
			}
		} else {
			if (!empty($host["server_group"])) {
				if ($host["type"] == "dcim") {
					$dcim = new Dcim();
					$dcim->is_admin = $this->is_admin;
					$module_res = $dcim->terminate($id);
				} elseif ($host["type"] == "dcimcloud") {
					$dcimcloud = new DcimCloud();
					$dcimcloud->is_admin = $this->is_admin;
					$module_res = $dcimcloud->terminate($id);
				} else {
					$module_res = $provision->terminateAccount($id);
					if ($module_res["no_support_function"] == true) {
						$module_res = ["status" => 200];
					}
				}
			} else {
				$module_res = ["status" => 200];
			}
		}
		if ($module_res["status"] == "success" || $module_res["status"] == 200) {
			hook("after_module_terminate", ["params" => $hook_data]);
			$update["domainstatus"] = "Deleted";
			$update["termination_date"] = time();
			$update["dedicatedip"] = "";
			$update["assignedips"] = "";
			$api = \think\Db::name("zjmf_finance_api")->where("id", $host["zjmf_api_id"])->find();
			if ($host["api_type"] != "resource" && $api["is_resource"] != 1) {
				$update["dcimid"] = 0;
			}
			$update["username"] = "";
			$update["password"] = "";
			$update["port"] = 0;
			$update["dcim_area"] = 0;
			$note = ["产品名称：" . $host["name"], "IP地址：" . $host["dedicatedip"], "附加IP：" . implode("/", explode(",", $host["assignedips"])), "主机名：" . $host["domain"], "用户名：" . $host["username"], "密码：" . cmf_decrypt($host["password"]), "操作系统：" . $host["os"], "端口：" . $host["port"], "ID：" . $host["dcimid"]];
			$update["notes"] = implode("\r\n", $note);
			\think\Db::name("host")->where("id", $id)->update($update);
			if ($host["auto_terminate_email"]) {
				$email = new Email();
				$email->sendEmail($host["auto_terminate_email"], $id);
			}
			if ($cron == "cron") {
				active_log_final(sprintf("删除成功#User ID:%d - Host ID:%d - %s", $host["uid"], $id, $str), $host["uid"], 2, $id);
			} else {
				active_log_final(sprintf("模块命令:删除成功#User ID:%d - Host ID:%d - %s", $host["uid"], $id, $str), $host["uid"], 2, $id);
			}
			\think\Db::name("dcim_buy_record")->where("show_status", 0)->where("status", 1)->where("hostid", $id)->update(["show_status" => 1]);
			$result["status"] = 200;
			$result["msg"] = $module_res["msg"] ?: "删除成功";
			pushHostInfo($id);
			\think\Db::name("host")->where("id", $id)->update(["stream_info" => ""]);
		} else {
			hook("after_module_terminate_failed", ["params" => $hook_data, "msg" => $module_res["msg"]]);
			if ($cron == "cron") {
				active_log_final(sprintf("删除失败#User ID:%d - Host ID:%d - 原因:%s", $host["uid"], $id, $module_res["msg"]), $host["uid"], 2, $id);
			} else {
				active_log_final(sprintf("模块命令:删除失败#User ID:%d - Host ID:%d - 原因:%s", $host["uid"], $id, $module_res["msg"]), $host["uid"], 2, $id);
			}
			$result["status"] = 406;
			$result["msg"] = $module_res["msg"];
		}
		if ($result["status"] == 200 && in_array($host["domainstatus"], ["Pending", "Active", "Suspended"])) {
			\think\Db::name("products")->where("id", $host["productid"])->setInc("qty", 1);
		}
		return $result;
	}
	public function sync($id)
	{
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.uid,a.productid,a.domainstatus,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email,b.auto_terminate_email,b.server_group")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return $result;
		}
		$provision = new Provision();
		$provision->is_admin = $this->is_admin;
		$hook_data = $provision->getParams($id);
		$hook_res = hook("before_module_sync", ["params" => $hook_data]);
		if (!empty($hook_res)) {
			foreach ($hook_res as $v) {
				if (is_array($v)) {
					$hook_data = array_merge($hook_data, $v);
				}
			}
			if ($hook_data["exit_module"]) {
				$result["status"] = 406;
				$result["msg"] = "当前操作被HOOK代码中断";
				return $result;
			}
		}
		if ($host["api_type"] == "zjmf_api") {
			$post_data["host_id"] = $host["dcimid"];
			$module_res = zjmfCurl($host["zjmf_api_id"], "/host/header", $post_data, 30, "GET");
			if ($module_res["status"] == 200) {
				$update["dedicatedip"] = $module_res["data"]["host_data"]["dedicatedip"] ?? "";
				$update["assignedips"] = implode(",", $module_res["data"]["host_data"]["assignedips"]) ?? "";
				$update["domain"] = $module_res["data"]["host_data"]["domain"] ?? "";
				$update["bwlimit"] = \intval($module_res["data"]["host_data"]["bwlimit"]);
				$update["bwusage"] = \floatval($module_res["data"]["host_data"]["bwusage"]);
				$update["username"] = $module_res["data"]["host_data"]["username"] ?? "";
				$update["password"] = cmf_encrypt($module_res["data"]["host_data"]["password"]);
				$update["port"] = \intval($module_res["data"]["host_data"]["port"]);
				$update["os"] = $module_res["data"]["host_data"]["os"];
				if ($host["domainstatus"] == "Pending" && $module_res["data"]["host_data"]["domainstatus"] == "Active") {
					$update["domainstatus"] = $module_res["data"]["host_data"]["domainstatus"];
				}
				\think\Db::name("host")->where("id", $id)->update($update);
			}
		} elseif ($host["api_type"] == "resource") {
			if (function_exists("resourceCurl")) {
				$post_data["host_id"] = $host["dcimid"];
				$module_res = resourceCurl($host["productid"], "/host/header", $post_data, 30, "GET");
				if ($module_res["status"] == 200) {
					$update["dedicatedip"] = $module_res["data"]["host_data"]["dedicatedip"] ?? "";
					$update["assignedips"] = implode(",", $module_res["data"]["host_data"]["assignedips"]) ?? "";
					$update["domain"] = $module_res["data"]["host_data"]["domain"] ?? "";
					$update["bwlimit"] = \intval($module_res["data"]["host_data"]["bwlimit"]);
					$update["bwusage"] = \floatval($module_res["data"]["host_data"]["bwusage"]);
					$update["username"] = $module_res["data"]["host_data"]["username"] ?? "";
					$update["password"] = cmf_encrypt($module_res["data"]["host_data"]["password"]);
					$update["port"] = \intval($module_res["data"]["host_data"]["port"]);
					$update["os"] = $module_res["data"]["host_data"]["os"];
					if ($host["domainstatus"] == "Pending" && $module_res["data"]["host_data"]["domainstatus"] == "Active") {
						$update["domainstatus"] = $module_res["data"]["host_data"]["domainstatus"];
					}
					\think\Db::name("host")->where("id", $id)->update($update);
				}
			}
		} elseif ($host["api_type"] == "whmcs") {
			$up_id = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.type", "product")->where("b.relid", $host["productid"])->where("b.fieldname", "hostid")->value("value");
			$api = \think\Db::name("zjmf_finance_api")->where("id", $host["zjmf_api_id"])->find();
			$url_data = ["apiname" => $api["username"], "apikey" => aesPasswordDecode($api["password"])];
			$url = $api["hostname"] . "/" . "modules/addons/idcsmart_api/api.php?action=/v1/hosts/{$up_id}";
			$res = commonCurl($url, $url_data);
			if ($res["status"] == 200) {
				$update = ["dedicatedip" => $res["data"]["dedicatedip"], "assignedips" => $res["data"]["assignedips"], "domain" => $res["data"]["domain"], "bwlimit" => $res["data"]["bwlimit"], "bwusage" => $res["data"]["bwusage"], "username" => $res["data"]["username"], "password" => cmf_encrypt($res["data"]["password"]), "port" => $res["data"]["port"] ?: "", "os" => $res["data"]["os"] ?: ""];
				if ($host["domainstatus"] == "Pending" && $res["data"]["domainstatus"] == "Active") {
					$update["domainstatus"] = $res["data"]["domainstatus"];
				}
				\think\Db::name("host")->where("id", $id)->update($update);
				$module_res = $res;
			} else {
				$module_res = ["status" => 400, "msg" => $res["msg"] ?: "拉取信息失败"];
			}
		} elseif (!empty($host["server_group"])) {
			if ($host["type"] == "dcim") {
				$dcim = new Dcim();
				$dcim->is_admin = true;
				$module_res = $dcim->sync($id);
			} elseif ($host["type"] == "dcimcloud") {
				$dcimcloud = new DcimCloud();
				$dcimcloud->is_admin = true;
				$module_res = $dcimcloud->sync($id);
			} else {
				$module_res = $provision->sync($id);
			}
		} else {
			$module_res = ["status" => 200];
		}
		if ($module_res["status"] == "success" || $module_res["status"] == 200) {
			hook("after_module_sync", ["params" => $hook_data]);
			active_log_final(sprintf("模块命令:同步成功#Host ID:%d", $id), $host["uid"], 2, $id);
			$result["status"] = 200;
			$result["msg"] = $module_res["msg"] ?: "同步成功";
			pushHostInfo($id);
		} else {
			hook("after_module_sync_failed", ["params" => $hook_data, "msg" => $module_res["msg"]]);
			active_log_final(sprintf("模块命令:同步失败#Host ID:%d - 原因:%s", $id, $module_res["msg"]), $host["uid"], 2, $id);
			$result["status"] = 406;
			$result["msg"] = $module_res["msg"];
		}
		return $result;
	}
	public function on($id)
	{
		if (is_array($id)) {
			$result["status"] = 200;
			foreach ($id as $v) {
				$result["data"][$v] = $this->on($v);
			}
		} else {
			$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email,b.server_group")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
			if (empty($host)) {
				$result["status"] = 406;
				$result["msg"] = "ID错误";
				return $result;
			}
			if (!$this->is_admin) {
				if ($host["domainstatus"] != "Active" || request()->uid != $host["uid"]) {
					$result["status"] = 406;
					$result["msg"] = "不能执行该操作";
					return $result;
				}
			}
			$provision = new Provision();
			$provision->is_admin = $this->is_admin;
			$hook_data = $provision->getParams($id);
			$hook_res = hook("before_module_on", ["params" => $hook_data]);
			if (!empty($hook_res)) {
				foreach ($hook_res as $v) {
					if (is_array($v)) {
						$hook_data = array_merge($hook_data, $v);
					}
				}
				if ($hook_data["exit_module"]) {
					$result["status"] = 406;
					$result["msg"] = "当前操作被HOOK代码中断";
					return $result;
				}
			}
			if ($host["api_type"] == "zjmf_api") {
				$post_data["id"] = $host["dcimid"];
				$post_data["func"] = "on";
				$post_data["is_api"] = 1;
				$module_res = zjmfCurl($host["zjmf_api_id"], "/provision/default", $post_data);
			} elseif ($host["api_type"] == "manual") {
				$UpperReaches = new UpperReaches();
				$UpperReaches->is_admin = $this->is_admin;
				$module_res = $UpperReaches->on($id);
			} elseif ($host["api_type"] == "resource") {
				if (function_exists("resourceCurl")) {
					$post_data["id"] = $host["dcimid"];
					$post_data["func"] = "on";
					$post_data["is_api"] = 1;
					$module_res = resourceCurl($host["productid"], "/provision/default", $post_data);
				}
			} elseif ($host["api_type"] == "whmcs") {
				$module_res = whmcsCurlPost($id, "on");
			} elseif (!empty($host["server_group"])) {
				if ($host["type"] == "dcim") {
					$dcim = new Dcim();
					$dcim->is_admin = $this->is_admin;
					$module_res = $dcim->on($id, false);
				} elseif ($host["type"] == "dcimcloud") {
					$dcimcloud = new DcimCloud();
					$dcimcloud->is_admin = $this->is_admin;
					$module_res = $dcimcloud->on($id);
				} else {
					$module_res = $provision->On($id);
				}
			} else {
				$module_res = ["status" => 200];
			}
			if ($module_res["status"] == "success" || $module_res["status"] == 200) {
				hook("after_module_on", ["params" => $hook_data]);
				active_log_final(sprintf("模块命令:开机成功#Host ID:%d", $id), $host["uid"], 2, $id);
				$result["status"] = 200;
				$result["msg"] = $module_res["msg"] ?: "开机成功";
			} else {
				hook("after_module_on_failed", ["params" => $hook_data, "msg" => $module_res["msg"]]);
				active_log_final(sprintf("模块命令:开机失败#Host ID:%d - 原因:%s", $id, $module_res["msg"]), $host["uid"], 2, $id);
				$result["status"] = 406;
				$result["msg"] = $module_res["msg"];
			}
		}
		return $result;
	}
	public function off($id)
	{
		if (is_array($id)) {
			$result["status"] = 200;
			foreach ($id as $v) {
				$result["data"][$v] = $this->off($v);
			}
		} else {
			$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email,a.uid,b.server_group")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
			if (empty($host)) {
				$result["status"] = 406;
				$result["msg"] = "ID错误";
				return $result;
			}
			if (!$this->is_admin) {
				if ($host["domainstatus"] != "Active" || request()->uid != $host["uid"]) {
					$result["status"] = 406;
					$result["msg"] = "不能执行该操作";
					return $result;
				}
			}
			$provision = new Provision();
			$provision->is_admin = $this->is_admin;
			$hook_data = $provision->getParams($id);
			$hook_res = hook("before_module_off", ["params" => $hook_data]);
			if (!empty($hook_res)) {
				foreach ($hook_res as $v) {
					if (is_array($v)) {
						$hook_data = array_merge($hook_data, $v);
					}
				}
				if ($hook_data["exit_module"]) {
					$result["status"] = 406;
					$result["msg"] = "当前操作被HOOK代码中断";
					return $result;
				}
			}
			if ($host["api_type"] == "zjmf_api") {
				$post_data["id"] = $host["dcimid"];
				$post_data["func"] = "off";
				$post_data["is_api"] = 1;
				$module_res = zjmfCurl($host["zjmf_api_id"], "/provision/default", $post_data);
			} elseif ($host["api_type"] == "manual") {
				$UpperReaches = new UpperReaches();
				$UpperReaches->is_admin = $this->is_admin;
				$module_res = $UpperReaches->off($id);
			} elseif ($host["api_type"] == "resource") {
				if (function_exists("resourceCurl")) {
					$post_data["id"] = $host["dcimid"];
					$post_data["func"] = "off";
					$post_data["is_api"] = 1;
					$module_res = resourceCurl($host["productid"], "/provision/default", $post_data);
				}
			} elseif ($host["api_type"] == "whmcs") {
				$module_res = whmcsCurlPost($id, "off");
			} elseif (!empty($host["server_group"])) {
				if ($host["type"] == "dcim") {
					$dcim = new Dcim();
					$dcim->is_admin = $this->is_admin;
					$module_res = $dcim->off($id, false);
				} elseif ($host["type"] == "dcimcloud") {
					$dcimcloud = new DcimCloud();
					$dcimcloud->is_admin = $this->is_admin;
					$module_res = $dcimcloud->off($id);
				} else {
					$module_res = $provision->Off($id);
				}
			} else {
				$module_res = ["status" => 200];
			}
			if ($module_res["status"] == "success" || $module_res["status"] == 200) {
				hook("after_module_off", ["params" => $hook_data]);
				active_log_final(sprintf("模块命令:关机成功#Host ID:%d", $id), $host["uid"], 2, $id);
				$result["status"] = 200;
				$result["msg"] = $module_res["msg"] ?: "关机成功";
			} else {
				hook("after_module_off_failed", ["params" => $hook_data, "msg" => $module_res["msg"]]);
				active_log_final(sprintf("模块命令:关机失败#Host ID:%d - 原因:%s", $id, $module_res["msg"]), $host["uid"], 2, $id);
				$result["status"] = 406;
				$result["msg"] = $module_res["msg"];
			}
		}
		return $result;
	}
	public function reboot($id)
	{
		if (is_array($id)) {
			$result["status"] = 200;
			foreach ($id as $v) {
				$result["data"][$v] = $this->reboot($v);
			}
		} else {
			$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email,a.uid,b.server_group")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
			if (empty($host)) {
				$result["status"] = 406;
				$result["msg"] = "ID错误";
				return $result;
			}
			if (!$this->is_admin) {
				if ($host["domainstatus"] != "Active" || request()->uid != $host["uid"]) {
					$result["status"] = 406;
					$result["msg"] = "不能执行该操作";
					return $result;
				}
			}
			$provision = new Provision();
			$provision->is_admin = $this->is_admin;
			$hook_data = $provision->getParams($id);
			$hook_res = hook("before_module_reboot", ["params" => $hook_data]);
			if (!empty($hook_res)) {
				foreach ($hook_res as $v) {
					if (is_array($v)) {
						$hook_data = array_merge($hook_data, $v);
					}
				}
				if ($hook_data["exit_module"]) {
					$result["status"] = 406;
					$result["msg"] = "当前操作被HOOK代码中断";
					return $result;
				}
			}
			if ($host["api_type"] == "zjmf_api") {
				$post_data["id"] = $host["dcimid"];
				$post_data["func"] = "reboot";
				$post_data["is_api"] = 1;
				$module_res = zjmfCurl($host["zjmf_api_id"], "/provision/default", $post_data);
			} elseif ($host["api_type"] == "manual") {
				$UpperReaches = new UpperReaches();
				$UpperReaches->is_admin = $this->is_admin;
				$module_res = $UpperReaches->reboot($id);
			} elseif ($host["api_type"] == "resource") {
				if (function_exists("resourceCurl")) {
					$post_data["id"] = $host["dcimid"];
					$post_data["func"] = "reboot";
					$post_data["is_api"] = 1;
					$module_res = resourceCurl($host["productid"], "/provision/default", $post_data);
				}
			} elseif ($host["api_type"] == "whmcs") {
				$module_res = whmcsCurlPost($id, "reboot");
			} elseif (!empty($host["server_group"])) {
				if ($host["type"] == "dcim") {
					$dcim = new Dcim();
					$dcim->is_admin = $this->is_admin;
					$module_res = $dcim->reboot($id, false);
				} elseif ($host["type"] == "dcimcloud") {
					$dcimcloud = new DcimCloud();
					$dcimcloud->is_admin = $this->is_admin;
					$module_res = $dcimcloud->reboot($id);
				} else {
					$module_res = $provision->Reboot($id);
				}
			} else {
				$module_res = ["status" => 200];
			}
			if ($module_res["status"] == "success" || $module_res["status"] == 200) {
				hook("after_module_reboot", ["params" => $hook_data]);
				active_log_final(sprintf("模块命令:重启成功#Host ID:%d", $id), $host["uid"], 2, $id);
				$result["status"] = 200;
				$result["msg"] = $module_res["msg"] ?: "重启成功";
			} else {
				hook("after_module_reboot_failed", ["params" => $hook_data, "msg" => $module_res["msg"]]);
				active_log_final(sprintf("模块命令:重启失败#Host ID:%d - 原因:%s", $id, $module_res["msg"]), $host["uid"], 2, $id);
				$result["status"] = 406;
				$result["msg"] = $module_res["msg"];
			}
		}
		return $result;
	}
	public function hardOff($id)
	{
		if (is_array($id)) {
			$result["status"] = 200;
			foreach ($id as $v) {
				$result["data"][$v] = $this->hardOff($v);
			}
		} else {
			$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email,b.server_group")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
			if (empty($host)) {
				$result["status"] = 406;
				$result["msg"] = "ID错误";
				return $result;
			}
			if (!$this->is_admin) {
				if ($host["domainstatus"] != "Active" || request()->uid != $host["uid"]) {
					$result["status"] = 406;
					$result["msg"] = "不能执行该操作";
					return $result;
				}
			}
			$provision = new Provision();
			$provision->is_admin = $this->is_admin;
			$hook_data = $provision->getParams($id);
			$hook_res = hook("before_module_hard_off", ["params" => $hook_data]);
			if (!empty($hook_res)) {
				foreach ($hook_res as $v) {
					if (is_array($v)) {
						$hook_data = array_merge($hook_data, $v);
					}
				}
				if ($hook_data["exit_module"]) {
					$result["status"] = 406;
					$result["msg"] = "当前操作被HOOK代码中断";
					return $result;
				}
			}
			if ($host["api_type"] == "zjmf_api") {
				$post_data["id"] = $host["dcimid"];
				$post_data["func"] = "hard_off";
				$post_data["is_api"] = 1;
				$module_res = zjmfCurl($host["zjmf_api_id"], "/provision/default", $post_data);
			} elseif ($host["api_type"] == "resource") {
				if (function_exists("resourceCurl")) {
					$post_data["id"] = $host["dcimid"];
					$post_data["func"] = "hard_off";
					$post_data["is_api"] = 1;
					$module_res = resourceCurl($host["productid"], "/provision/default", $post_data);
				}
			} else {
				if (!empty($host["server_group"])) {
					if ($host["type"] == "dcim") {
						$dcim = new Dcim();
						$dcim->is_admin = $this->is_admin;
						$module_res = $dcim->bmsHardOff($id);
					} elseif ($host["type"] == "dcimcloud") {
						$dcimcloud = new DcimCloud();
						$dcimcloud->is_admin = $this->is_admin;
						$module_res = $dcimcloud->hardOff($id);
					} else {
						$module_res = $provision->HardOff($id);
					}
				} else {
					$module_res = ["status" => 200];
				}
			}
			if ($module_res["status"] == "success" || $module_res["status"] == 200) {
				hook("after_module_hard_off", ["params" => $hook_data]);
				active_log_final(sprintf("模块命令:硬关机成功#Host ID:%d", $id), $host["uid"], 2, $id);
				$result["status"] = 200;
				$result["msg"] = $module_res["msg"] ?: "硬关机成功";
			} else {
				hook("after_module_hard_off_failed", ["params" => $hook_data, "msg" => $module_res["msg"]]);
				active_log_final(sprintf("模块命令:硬关机失败#Host ID:%d - 原因:%s", $id, $module_res["msg"]), $host["uid"], 2, $id);
				$result["status"] = 406;
				$result["msg"] = $module_res["msg"];
			}
		}
		return $result;
	}
	public function hardReboot($id)
	{
		if (is_array($id)) {
			$result["status"] = 200;
			foreach ($id as $v) {
				$result["data"][$v] = $this->hardReboot($v);
			}
		} else {
			$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email,b.server_group")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
			if (empty($host)) {
				$result["status"] = 406;
				$result["msg"] = "ID错误";
				return $result;
			}
			if (!$this->is_admin) {
				if ($host["domainstatus"] != "Active" || request()->uid != $host["uid"]) {
					$result["status"] = 406;
					$result["msg"] = "不能执行该操作";
					return $result;
				}
			}
			$provision = new Provision();
			$provision->is_admin = $this->is_admin;
			$hook_data = $provision->getParams($id);
			$hook_res = hook("before_module_hard_reboot", ["params" => $hook_data]);
			if (!empty($hook_res)) {
				foreach ($hook_res as $v) {
					if (is_array($v)) {
						$hook_data = array_merge($hook_data, $v);
					}
				}
				if ($hook_data["exit_module"]) {
					$result["status"] = 406;
					$result["msg"] = "当前操作被HOOK代码中断";
					return $result;
				}
			}
			if ($host["api_type"] == "zjmf_api") {
				$post_data["id"] = $host["dcimid"];
				$post_data["func"] = "hard_reboot";
				$post_data["is_api"] = 1;
				$module_res = zjmfCurl($host["zjmf_api_id"], "/provision/default", $post_data);
			} elseif ($host["api_type"] == "resource") {
				if (function_exists("resourceCurl")) {
					$post_data["id"] = $host["dcimid"];
					$post_data["func"] = "hard_reboot";
					$post_data["is_api"] = 1;
					$module_res = resourceCurl($host["productid"], "/provision/default", $post_data);
				}
			} else {
				if (!empty($host["server_group"])) {
					if ($host["type"] == "dcim") {
						$dcim = new Dcim();
						$dcim->is_admin = $this->is_admin;
						$module_res = $dcim->bmsHardReboot($id);
					} elseif ($host["type"] == "dcimcloud") {
						$dcimcloud = new DcimCloud();
						$dcimcloud->is_admin = $this->is_admin;
						$module_res = $dcimcloud->hardReboot($id);
					} else {
						$module_res = $provision->HardReboot($id);
					}
				} else {
					$module_res = ["status" => 200];
				}
			}
			if ($module_res["status"] == "success" || $module_res["status"] == 200) {
				hook("after_module_hard_reboot", ["params" => $hook_data]);
				active_log_final(sprintf("模块命令:硬重启成功#Host ID:%d", $id), $host["uid"], 2, $id);
				$result["status"] = 200;
				$result["msg"] = $module_res["msg"] ?: "硬重启成功";
			} else {
				hook("after_module_hard_reboot_failed", ["params" => $hook_data, "msg" => $module_res["msg"]]);
				active_log_final(sprintf("模块命令:硬重启失败#Host ID:%d - 原因:%s", $id, $module_res["msg"]), $host["uid"], 2, $id);
				$result["status"] = 406;
				$result["msg"] = $module_res["msg"];
			}
		}
		return $result;
	}
	public function vnc($id)
	{
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email,b.server_group")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return $result;
		}
		if (!$this->is_admin) {
			if ($host["domainstatus"] != "Active" || request()->uid != $host["uid"]) {
				$result["status"] = 406;
				$result["msg"] = "不能执行该操作";
				return $result;
			}
		}
		if ($host["api_type"] == "zjmf_api") {
			$post_data["id"] = $host["dcimid"];
			$post_data["func"] = "vnc";
			$post_data["is_api"] = 1;
			$module_res = zjmfCurl($host["zjmf_api_id"], "/provision/default", $post_data);
		} elseif ($host["api_type"] == "manual") {
			$UpperReaches = new UpperReaches();
			$UpperReaches->is_admin = $this->is_admin;
			$module_res = $UpperReaches->vnc($id);
		} elseif ($host["api_type"] == "resource") {
			if (function_exists("resourceCurl")) {
				$post_data["id"] = $host["dcimid"];
				$post_data["func"] = "vnc";
				$post_data["is_api"] = 1;
				$module_res = resourceCurl($host["productid"], "/provision/default", $post_data);
			}
		} elseif ($host["api_type"] == "whmcs") {
			$module_res = whmcsCurlPost($id, "vnc");
		} elseif (!empty($host["server_group"])) {
			if ($host["type"] == "dcim") {
				$dcim = new Dcim();
				$dcim->is_admin = $this->is_admin;
				$module_res = $dcim->novnc($id, false, true);
			} elseif ($host["type"] == "dcimcloud") {
				$dcimcloud = new DcimCloud();
				$dcimcloud->is_admin = $this->is_admin;
				$module_res = $dcimcloud->vnc($id);
			} else {
				$provision = new Provision();
				$provision->is_admin = $this->is_admin;
				$module_res = $provision->Vnc($id);
			}
		} else {
			$module_res = ["status" => 200];
		}
		if ($module_res["status"] == "success" || $module_res["status"] == 200) {
			active_log_final(sprintf("模块命令:vnc启动成功#Host ID:%d", $id), $host["uid"], 2, $id);
			if ($host["api_type"] == "zjmf_api") {
				$result = $module_res;
				if ($host["type"] == "dcimcloud" && !$module_res["data"]["zjmfcloud_out_vnc"]) {
					if ($this->is_admin) {
						$result["data"]["url"] = request()->domain() . "/" . config("database.admin_application") . "/dcim/novnc" . substr($result["data"]["url"], strpos($result["data"]["url"], "?url="));
					} else {
						$result["data"]["url"] = request()->domain() . "/dcim/novnc" . substr($result["data"]["url"], strpos($result["data"]["url"], "?url="));
					}
				}
			} elseif ($host["api_type"] == "resource") {
				return $module_res;
			} else {
				$result["status"] = 200;
				$result["msg"] = $module_res["msg"] ?: "vnc启动成功";
				$result["data"]["url"] = $module_res["url"];
				$result["data"]["pass"] = $module_res["pass"];
				if ($host["type"] == "dcimcloud") {
					$result["data"]["zjmfcloud_out_vnc"] = $module_res["out"];
				}
			}
		} else {
			active_log_final(sprintf("模块命令:vnc启动失败#Host ID:%d - 原因:%s", $id, $module_res["msg"]), $host["uid"], 2, $id);
			$result["status"] = 406;
			$result["msg"] = $module_res["msg"];
		}
		return $result;
	}
	public function reinstall($id, $os, $port = 0)
	{
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.dcimid,a.os,a.username,b.server_type,b.type,b.api_type,b.zjmf_api_id,b.upstream_price_value,c.email,a.uid,b.server_group")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return $result;
		}
		if (!$this->is_admin) {
			if ($host["domainstatus"] != "Active" || request()->uid != $host["uid"]) {
				$result["status"] = 406;
				$result["msg"] = "不能执行该操作";
				return $result;
			}
		}
		$r = \think\Db::name("product_config_options_sub")->alias("a")->field("a.*")->leftJoin("product_config_options b", "a.config_id=b.id")->leftJoin("product_config_links c", "b.gid=c.gid")->where("c.pid", $host["productid"])->where("a.id", $os)->find();
		if (empty($r)) {
			$result["status"] = 406;
			$result["msg"] = "操作系统错误";
			return $result;
		}
		$configoption_res = \think\Db::name("host_config_options")->where("relid", $id)->select()->toArray();
		$configoption = [];
		foreach ($configoption_res as $k => $v) {
			$configoption[$v["configid"]] = $v["qty"] ?: $v["optionid"];
		}
		$configoption[$r["config_id"]] = $os;
		$senior = new SeniorConf();
		$msg = $senior->checkConf($host["productid"], $configoption);
		if ($msg) {
			$result["status"] = 406;
			$result["msg"] = $msg;
			return $result;
		}
		$arr = explode("|", $r["option_name"]);
		$os_id = $arr[0];
		if (strpos($arr[1], "^") !== false) {
			$os_arr = explode("^", $arr[1]);
			$os_name = $os_arr[1];
			$os_group = $os_arr[0];
		} else {
			$os_name = $arr[1];
			$os_group = "";
		}
		$provision = new Provision();
		$provision->is_admin = $this->is_admin;
		$hook_data = $provision->getParams($id);
		$hook_data["reinstall_os"] = $os_id;
		$hook_data["reinstall_os_name"] = $os_name;
		$hook_res = hook("before_module_reinstall", ["params" => $hook_data]);
		if (!empty($hook_res)) {
			foreach ($hook_res as $v) {
				if (is_array($v)) {
					$hook_data = array_merge($hook_data, $v);
				}
			}
			if ($hook_data["exit_module"]) {
				$result["status"] = 406;
				$result["msg"] = "当前操作被HOOK代码中断";
				return $result;
			}
		}
		if ($host["api_type"] == "zjmf_api") {
			$post_data["id"] = $host["dcimid"];
			$post_data["func"] = "reinstall";
			$post_data["os"] = $r["upstream_id"];
			$post_data["is_api"] = 1;
			if ($port > 0 && $port <= 65535) {
				$post_data["port"] = $port;
			}
			$post_data["format_data_disk"] = input("post.format_data_disk", 0, "intval");
			$module_res = zjmfCurl($host["zjmf_api_id"], "/provision/default", $post_data);
			if ($module_res["status"] != 200 && isset($module_res["price"]) && $host["upstream_price_type"] == "percent") {
				$module_res["price"] = round($module_res["price"] * $host["upstream_price_value"] / 100, 2);
			}
		} elseif ($host["api_type"] == "manual") {
			$UpperReaches = new UpperReaches();
			$UpperReaches->is_admin = $this->is_admin;
			$module_res = $UpperReaches->reinstall($id, $os_id);
		} elseif ($host["api_type"] == "resource") {
			if (function_exists("resourceCurl")) {
				$post_data["id"] = $host["dcimid"];
				$post_data["func"] = "reinstall";
				$post_data["os"] = $r["upstream_id"];
				$post_data["is_api"] = 1;
				if ($port > 0 && $port <= 65535) {
					$post_data["port"] = $port;
				}
				$post_data["format_data_disk"] = input("post.format_data_disk", 0, "intval");
				$module_res = resourceCurl($host["productid"], "/provision/default", $post_data);
			}
		} elseif ($host["api_type"] == "whmcs") {
		} elseif (!empty($host["server_group"])) {
			if ($host["type"] == "dcim") {
				$dcim = new Dcim();
				$dcim->is_admin = $this->is_admin;
				$module_res = $dcim->bmsReinstall($id, $os_id, $os_name);
			} elseif ($host["type"] == "dcimcloud") {
				$dcimcloud = new DcimCloud();
				$dcimcloud->is_admin = $this->is_admin;
				$module_res = $dcimcloud->reinstall($id, $os_id, $port);
			} else {
				$module_res = $provision->reinstall($id, $os_id, $os_name);
			}
		} else {
			$module_res = ["status" => 200];
		}
		if ($module_res["status"] == "success" || $module_res["status"] == 200) {
			hook("after_module_reinstall", ["params" => $hook_data]);
			$old = \think\Db::name("host_config_options")->where("relid", $id)->where("configid", $r["config_id"])->find();
			if (!empty($old)) {
				\think\Db::name("host_config_options")->where("id", $old["id"])->update(["optionid" => $r["id"]]);
			} else {
				$data = ["relid" => $id, "configid" => $r["config_id"], "optionid" => $r["id"], "qty" => 0];
				\think\Db::name("host_config_options")->insert($data);
			}
			\think\Db::name("host")->where("id", $id)->update(["os" => $os_name, "os_url" => $os_group]);
			active_log_final(sprintf("模块命令:重装系统发起成功,新系统:%s#Host ID:%d", $os_name, $id), $host["uid"], 2, $id);
			$new_host = \think\Db::name("host")->field("username")->where("id", $id)->find();
			pushHostInfo($id);
			$result["status"] = 200;
			$result["msg"] = $module_res["msg"] ?: "重装系统发起成功";
		} else {
			hook("after_module_reinstall_failed", ["params" => $hook_data, "msg" => $module_res["msg"]]);
			active_log_final(sprintf("模块命令:重装系统发起失败#Host ID:%d - 原因:%s", $id, $module_res["msg"]), $host["uid"], 2, $id);
			$result["status"] = 406;
			$result["msg"] = $module_res["msg"];
			if (isset($module_res["price"])) {
				$result["price"] = $module_res["price"];
			}
		}
		return $result;
	}
	public function status($id, $format = false)
	{
		if (is_array($id)) {
			$result["status"] = 200;
			foreach ($id as $v) {
				$result["data"][$v] = $this->status($v, true);
			}
		} else {
			$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email,b.server_group")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
			if (empty($host)) {
				$result["status"] = 406;
				$result["msg"] = "ID错误";
				return $result;
			}
			if (!$this->is_admin) {
				if ($host["domainstatus"] != "Active" || request()->uid != $host["uid"]) {
					$result["status"] = 406;
					$result["msg"] = "不能执行该操作";
					return $result;
				}
			}
			if ($host["api_type"] == "whmcs") {
				$dcimid = \think\Db::name("customfieldsvalues")->alias("a")->leftJoin("customfields b", "a.fieldid=b.id")->where("a.relid", $id)->where("b.type", "product")->where("b.relid", $host["productid"])->where("b.fieldname", "hostid")->value("value");
				$host["server_group"] = $dcimid;
			}
			if ($host["api_type"] == "zjmf_api") {
				$post_data["id"] = $host["dcimid"];
				$post_data["func"] = "status";
				$post_data["is_api"] = 1;
				$module_res = zjmfCurl($host["zjmf_api_id"], "/provision/default", $post_data);
			} elseif ($host["api_type"] == "manual") {
				$UpperReaches = new UpperReaches();
				$UpperReaches->is_admin = $this->is_admin;
				$module_res = $UpperReaches->getStatus($id);
			} elseif ($host["api_type"] == "resource") {
				if (function_exists("resourceCurl")) {
					$post_data["id"] = $host["dcimid"];
					$post_data["func"] = "status";
					$post_data["is_api"] = 1;
					$module_res = resourceCurl($host["productid"], "/provision/default", $post_data);
				}
			} elseif (!empty($host["server_group"])) {
				if ($host["type"] == "dcim") {
					$dcim = new Dcim();
					$dcim->is_admin = $this->is_admin;
					$module_res = $dcim->refreshPowerStatus($id);
					if ($format && $module_res["status"] == 200) {
						if (isset($module_res["data"]["power"])) {
							if ($module_res["data"]["power"] == "not_support") {
								$module_res["status"] = 406;
								$module_res["msg"] = $module_res["data"]["msg"];
							} else {
								$module_res["data"]["des"] = $module_res["data"]["msg"];
								$module_res["data"]["status"] = $module_res["data"]["power"] == "error" ? "unknown" : $module_res["data"]["power"];
							}
							unset($module_res["data"]["power"]);
							unset($module_res["data"]["msg"]);
						}
					}
				} elseif ($host["type"] == "dcimcloud") {
					$dcimcloud = new DcimCloud();
					$dcimcloud->is_admin = $this->is_admin;
					$module_res = $dcimcloud->getCloudStatus($id);
				} else {
					$provision = new Provision();
					$provision->is_admin = $this->is_admin;
					$module_res = $provision->status($id);
					if ($format && ($module_res["status"] == 200 || $module_res["status"] == "success")) {
						if (!isset($module_res["data"]["status"])) {
							$module_res = [];
							$module_res["status"] = "error";
							$module_res["msg"] = lang("NO_SUPPORT_FUNCTION");
						}
					}
				}
			} else {
				$module_res = ["status" => 200, "data" => ["status" => "unknown", "des" => "未知"]];
			}
			if ($module_res["status"] == "success" || $module_res["status"] == 200) {
				$result["status"] = 200;
				$result["data"] = $module_res["data"];
			} else {
				$result["status"] = 406;
				$result["msg"] = $module_res["msg"];
			}
		}
		return $result;
	}
	/**
	 * 时间 2020-07-06
	 * @desc 升降级
	 * @author hh
	 * @param   int $id  HOST Id
	 * @param   array $params 旧配置
	 * @param   bool $is_config 是否配置项升降级
	 * @param   bool $is_upstream_product 是否升降级至上游产品
	 */
	public function changePackage($id, $params, $is_config = false, $is_upstream_product = false, $upgrade_id = 0)
	{
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email,a.billingcycle,b.upstream_pid,b.server_group")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return $result;
		}
		$provision = new Provision();
		$provision->is_admin = $this->is_admin;
		$hook_data = $provision->getParams($id);
		$hook_data["old_configoptions"] = $params;
		$hook_res = hook("before_module_change_package", ["params" => $hook_data]);
		if (!empty($hook_res)) {
			foreach ($hook_res as $v) {
				if (is_array($v)) {
					$hook_data = array_merge($hook_data, $v);
				}
			}
			if ($hook_data["exit_module"]) {
				$result["status"] = 406;
				$result["msg"] = "当前操作被HOOK代码中断";
				return $result;
			}
		}
		if ($host["api_type"] == "zjmf_api" && $is_config) {
			$post_data = [];
			$configoption = \think\Db::name("host_config_options")->alias("a")->field("a.qty,b.option_type,b.upstream_id config_upstream,c.upstream_id")->leftJoin("product_config_options b", "a.configid=b.id")->leftJoin("product_config_options_sub c", "a.optionid=c.id")->where("a.relid", $id)->where("b.upstream_id", ">", 0)->where("c.upstream_id", ">", 0)->where("b.upgrade", 1)->select()->toArray();
			if (empty($configoption)) {
				$result["status"] = 400;
				$result["msg"] = "没有可升降级配置";
				return $result;
			}
			foreach ($configoption as $v) {
				if (judgeQuantity($v["option_type"])) {
					$post_data["configoption"][$v["config_upstream"]] = $v["qty"];
				} else {
					$post_data["configoption"][$v["config_upstream"]] = $v["upstream_id"];
				}
			}
			$post_data["hid"] = $host["dcimid"];
			$module_res = zjmfCurl($host["zjmf_api_id"], "/upgrade/upgrade_config_post", $post_data);
			if ($module_res["status"] == 200) {
				$module_res = zjmfCurl($host["zjmf_api_id"], "/upgrade/checkout_config_upgrade", $post_data);
				if ($module_res["status"] == 200) {
					$post_data = [];
					$post_data["invoiceid"] = $module_res["data"]["invoiceid"];
					$post_data["use_credit"] = 1;
					$post_data["enough"] = 1;
					$module_res = zjmfCurl($host["zjmf_api_id"], "/apply_credit", $post_data);
					if ($module_res["status"] == 1001) {
						$module_res["status"] = 200;
					} elseif ($module_res["status"] == 200) {
						$module_res["status"] = 400;
					}
				} elseif ($module_res["status"] == 1001) {
					$module_res["status"] = 200;
				}
			}
		} elseif ($host["api_type"] == "zjmf_api" && $is_upstream_product) {
			$post_data = [];
			$post_data["hid"] = $host["dcimid"];
			$post_data["pid"] = $host["upstream_pid"];
			$post_data["billingcycle"] = $host["billingcycle"];
			$module_res = zjmfCurl($host["zjmf_api_id"], "/upgrade/upgrade_product_post", $post_data);
			if ($module_res["status"] == 200) {
				$module_res = zjmfCurl($host["zjmf_api_id"], "/upgrade/checkout_upgrade_product", $post_data);
				if ($module_res["status"] == 200) {
					$post_data = [];
					$post_data["invoiceid"] = $module_res["data"]["invoiceid"];
					$post_data["use_credit"] = 1;
					$module_res = zjmfCurl($host["zjmf_api_id"], "/apply_credit", $post_data);
					if ($module_res["status"] == 1001) {
						$module_res["status"] = 200;
					} elseif ($module_res["status"] == 200) {
						$module_res["status"] = 400;
					}
				} elseif ($module_res["status"] == 1001) {
					$module_res["status"] = 200;
				}
			}
		} elseif ($host["api_type"] == "resource" && $is_config) {
			if (function_exists("resourceCurl")) {
				$res_products = \think\Db::name("host")->alias("a")->field("b.percent_value,b.price_type")->leftJoin("res_products b", "a.productid=b.productid")->where("a.id", $id)->find();
				$post_data = [];
				$configoption = \think\Db::name("host_config_options")->alias("a")->field("a.qty,b.option_type,b.upstream_id config_upstream,c.upstream_id")->leftJoin("product_config_options b", "a.configid=b.id")->leftJoin("product_config_options_sub c", "a.optionid=c.id")->where("a.relid", $id)->where("b.upstream_id", ">", 0)->where("c.upstream_id", ">", 0)->where("b.upgrade", 1)->select()->toArray();
				if (empty($configoption)) {
					$result["status"] = 400;
					$result["msg"] = "没有可升降级配置";
					return $result;
				}
				foreach ($configoption as $v) {
					if (judgeQuantity($v["option_type"])) {
						$post_data["configoption"][$v["config_upstream"]] = $v["qty"];
					} else {
						$post_data["configoption"][$v["config_upstream"]] = $v["upstream_id"];
					}
				}
				$post_data["hid"] = $host["dcimid"];
				$module_res = resourceCurl($host["productid"], "/upgrade/upgrade_config_post", $post_data);
				if ($module_res["status"] == 200) {
					if ($res_products["price_type"] == "supplier") {
						$percent = $res_products["percent_value"] ?: 100;
					} elseif ($res_products["price_type"] == "handling") {
						$percent = handlingPercent($id);
					} else {
						$percent = 100;
					}
					$post_data["resource_percent_value"] = bcdiv($percent, 100, 20);
					$module_res = resourceCurl($host["productid"], "/upgrade/checkout_config_upgrade", $post_data);
					$supplier_orderid = intval($module_res["data"]["orderid"]);
					if ($module_res["status"] == 200) {
						$post_data = [];
						$post_data["invoiceid"] = $module_res["data"]["invoiceid"];
						$post_data["use_credit"] = 1;
						$post_data["enough"] = 1;
						$module_res = resourceCurl($host["productid"], "/apply_credit", $post_data);
						if ($module_res["status"] == 1001) {
							$module_res["status"] = 200;
						} elseif ($module_res["status"] == 200) {
							$module_res["status"] = 400;
						}
					} elseif ($module_res["status"] == 1001) {
						$module_res["status"] = 200;
					}
				}
			}
		} elseif ($host["api_type"] == "resource" && $is_upstream_product) {
			$res_products = \think\Db::name("host")->alias("a")->field("b.percent_value,b.price_type")->leftJoin("res_products b", "a.productid=b.productid")->where("a.id", $id)->find();
			$post_data = [];
			$post_data["hid"] = $host["dcimid"];
			$post_data["pid"] = $host["upstream_pid"];
			$post_data["billingcycle"] = $host["billingcycle"];
			$module_res = resourceCurl($host["productid"], "/upgrade/upgrade_product_post", $post_data);
			if ($module_res["status"] == 200) {
				if ($res_products["price_type"] == "supplier") {
					$percent = $res_products["percent_value"] ?: 100;
				} elseif ($res_products["price_type"] == "handling") {
					$percent = handlingPercent($id);
				} else {
					$percent = 100;
				}
				$post_data["resource_percent_value"] = bcdiv($percent, 100, 20);
				$module_res = resourceCurl($host["productid"], "/upgrade/checkout_upgrade_product", $post_data);
				$supplier_orderid = intval($module_res["data"]["orderid"]);
				if ($module_res["status"] == 200) {
					$post_data = [];
					$post_data["invoiceid"] = $module_res["data"]["invoiceid"];
					$post_data["use_credit"] = 1;
					$module_res = resourceCurl($host["productid"], "/apply_credit", $post_data);
					if ($module_res["status"] == 1001) {
						$module_res["status"] = 200;
					} elseif ($module_res["status"] == 200) {
						$module_res["status"] = 400;
					}
				} elseif ($module_res["status"] == 1001) {
					$module_res["status"] = 200;
				}
			}
		} elseif (!empty($host["server_group"])) {
			if ($host["type"] == "dcim") {
				$dcim = new Dcim();
				$dcim->is_admin = $this->is_admin;
				$module_res = $dcim->upgrade($id);
			} elseif ($host["type"] == "dcimcloud") {
				$dcimcloud = new DcimCloud();
				$dcimcloud->is_admin = $this->is_admin;
				$module_res = $dcimcloud->upgrade($id, $params);
			} else {
				$module_res = $provision->changePackage($id, $params);
			}
		} else {
			$module_res = ["status" => 200];
		}
		if ($module_res["status"] == "success" || $module_res["status"] == 200) {
			hook("after_module_change_package", ["params" => $hook_data]);
			if ($host["api_type"] == "resource") {
				$orders = \think\Db::name("orders")->alias("a")->field("a.id,a.supplier_id,a.invoiceid")->leftJoin("upgrades b", "a.id=b.order_id")->where("b.id", $upgrade_id)->find();
				if (!empty($orders["supplier_id"])) {
					$ids = explode(",", $orders["supplier_id"]);
				} else {
					$ids = [];
				}
				if (!empty($supplier_orderid)) {
					if (!empty($ids[0])) {
						array_push($ids, $supplier_orderid);
					} else {
						$ids[] = $supplier_orderid;
					}
				}
				$id_str = implode(",", $ids);
				\think\Db::name("orders")->where("id", $orders["id"])->update(["supplier_id" => $id_str]);
				$post_data = [];
				$post_data["ids"] = $ids;
				$module_res = resourceCurl($host["productid"], "/host/upgradehost", $post_data, 30, "GET");
				if ($module_res["status"] == 200) {
					$current_rate = \think\Db::name("host")->alias("a")->leftJoin("res_products b", "a.productid=b.productid")->where("a.id", $id)->value("b.current_rate");
					\think\Db::name("invoices")->where("id", $orders["invoiceid"])->update(["cost" => bcmul($module_res["data"]["order_amount"], $current_rate, 20)]);
				}
			}
			pushHostInfo($id);
			$result["status"] = 200;
			$result["msg"] = $module_res["msg"] ?: "成功";
		} else {
			hook("after_module_change_package_failed", ["params" => $hook_data, "msg" => $module_res["msg"]]);
			$result["status"] = 406;
			$result["msg"] = $module_res["msg"];
		}
		return $result;
	}
	public function crackPass($id, $new_pass = "")
	{
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.uid,a.productid,a.domainstatus,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email,b.server_group,b.config_option1")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return $result;
		}
		if (empty($new_pass)) {
			$result["status"] = 406;
			$result["msg"] = "密码不能为空";
			return $result;
		}
		if (!$this->is_admin) {
			if ($host["domainstatus"] != "Active" || request()->uid != $host["uid"]) {
				$result["status"] = 406;
				$result["msg"] = "不能执行该操作";
				return $result;
			}
		}
		$Shop = new Shop(0);
		$check_pass = $Shop->checkHostPassword($new_pass, $host["productid"]);
		if ($check_pass["status"] != 200) {
			return $check_pass;
		}
		$provision = new Provision();
		$provision->is_admin = $this->is_admin;
		$hook_data = $provision->getParams($id);
		$hook_res = hook("before_module_crack_password", ["params" => $hook_data]);
		if (!empty($hook_res)) {
			foreach ($hook_res as $v) {
				if (is_array($v)) {
					$hook_data = array_merge($hook_data, $v);
				}
			}
			if ($hook_data["exit_module"]) {
				$result["status"] = 406;
				$result["msg"] = "当前操作被HOOK代码中断";
				return $result;
			}
		}
		if ($host["api_type"] == "zjmf_api") {
			$post_data["id"] = $host["dcimid"];
			$post_data["func"] = "crack_pass";
			$post_data["password"] = $new_pass;
			$post_data["is_api"] = 1;
			$module_res = zjmfCurl($host["zjmf_api_id"], "/provision/default", $post_data);
		} elseif ($host["api_type"] == "manual") {
			$UpperReaches = new UpperReaches();
			$UpperReaches->is_admin = $this->is_admin;
			$module_res = $UpperReaches->CrackPassword($id);
		} elseif ($host["api_type"] == "resource") {
			$post_data["id"] = $host["dcimid"];
			$post_data["func"] = "crack_pass";
			$post_data["password"] = $new_pass;
			$post_data["is_api"] = 1;
			$module_res = resourceCurl($host["productid"], "/provision/default", $post_data);
		} elseif ($host["api_type"] == "whmcs") {
			$module_res = whmcsCurlPost($id, "repassword");
		} elseif (!empty($host["server_group"])) {
			if ($host["type"] == "dcim") {
				$dcim = new Dcim();
				$dcim->is_admin = $this->is_admin;
				$module_res = $dcim->bmsCrackPassword($id, $new_pass);
			} elseif ($host["type"] == "dcimcloud") {
				$dcimcloud = new DcimCloud();
				$dcimcloud->is_admin = $this->is_admin;
				$module_res = $dcimcloud->CrackPassword($id, $new_pass);
			} else {
				$module_res = $provision->CrackPassword($id, $new_pass);
			}
		} else {
			$module_res = ["status" => 200];
		}
		if ($module_res["status"] == "success" || $module_res["status"] == 200) {
			hook("after_module_crack_password", ["hostid" => $id, "oldpassword" => $hook_data["password"], "newspassword" => $new_pass]);
			if (!empty($new_pass)) {
				\think\Db::name("host")->where("id", $id)->update(["password" => cmf_encrypt($new_pass)]);
			}
			active_log_final(sprintf("模块命令:重置密码成功#Host ID:%d", $id), $host["uid"], 2, $id);
			$result["status"] = 200;
			$result["msg"] = $module_res["msg"] ?: "重置密码成功";
		} else {
			hook("after_module_crack_password_failed", ["params" => $hook_data, "msg" => $module_res["msg"]]);
			active_log_final(sprintf("模块命令:重置密码失败#Host ID:%d - 原因:%s", $id, $module_res["msg"]), $host["uid"], 2, $id);
			$result["status"] = 406;
			$result["msg"] = $module_res["msg"];
		}
		return $result;
	}
	public function panel($id)
	{
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email,b.server_group")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return $result;
		}
		if ($host["api_type"] == "zjmf_api") {
		} else {
			if (!empty($host["server_group"])) {
				if ($host["type"] == "dcim") {
				} elseif ($host["type"] == "dcimcloud") {
					$dcimcloud = new DcimCloud();
					$dcimcloud->is_admin = true;
					$module_res = $dcimcloud->managePanel($id);
				} else {
					$provision = new Provision();
					$provision->is_admin = $this->is_admin;
					$module_res = $provision->managePanel($id);
				}
			} else {
				$module_res = ["status" => 200];
			}
			if ($module_res["status"] == "success" || $module_res["status"] == 200) {
				$result["status"] = 200;
				$result["data"] = $module_res["data"];
			} else {
				$result["status"] = 406;
				$result["msg"] = $module_res["msg"];
			}
		}
		return $result;
	}
	public function renew($id)
	{
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.uid,a.dcimid,a.billingcycle,b.server_type,b.type,b.api_type,b.zjmf_api_id,b.server_group")->leftJoin("products b", "a.productid=b.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return $result;
		}
		$provision = new Provision();
		$provision->is_admin = $this->is_admin;
		$hook_data = $provision->getParams($id);
		$hook_res = hook("before_module_renew", ["params" => $hook_data]);
		if (!empty($hook_res)) {
			foreach ($hook_res as $v) {
				if (is_array($v)) {
					$hook_data = array_merge($hook_data, $v);
				}
			}
			if ($hook_data["exit_module"]) {
				$result["status"] = 406;
				$result["msg"] = "当前操作被HOOK代码中断";
				return $result;
			}
		}
		if ($host["api_type"] == "zjmf_api") {
			$post_data["hostid"] = $host["dcimid"];
			$post_data["billingcycles"] = $host["billingcycle"];
			$module_res = zjmfCurl($host["zjmf_api_id"], "/host/renew", $post_data);
			if ($module_res["status"] == 200) {
				$post_data = [];
				$post_data["invoiceid"] = $module_res["data"]["invoiceid"];
				$post_data["use_credit"] = 1;
				$module_res = zjmfCurl($host["zjmf_api_id"], "/apply_credit", $post_data);
				if ($module_res["status"] == 1001) {
					$module_res["status"] = 200;
				} elseif ($module_res["status"] == 200) {
					$module_res["status"] = 400;
					$module_res["msg"] = $module_res["msg"];
				}
			}
		} elseif ($host["api_type"] == "resource") {
			$post_data["hostid"] = $host["dcimid"];
			$post_data["billingcycles"] = $host["billingcycle"];
			$price_type = \think\Db::name("host")->alias("a")->leftJoin("res_products d", "a.productid=d.productid")->where("a.id", $id)->value("d.price_type");
			if ($price_type == "handling") {
				$post_data["resource_handling"] = handlingPercent($id) / 100;
			}
			$module_res = resourceCurl($host["productid"], "/host/renew", $post_data);
			if ($module_res["status"] == 200) {
				$post_data = [];
				$post_data["invoiceid"] = $supplier_invoiceid = $module_res["data"]["invoiceid"];
				$post_data["use_credit"] = 1;
				$module_res = resourceCurl($host["productid"], "/apply_credit", $post_data);
				if ($module_res["status"] == 1001) {
					$module_res["status"] = 200;
				} elseif ($module_res["status"] == 200) {
					$module_res["status"] = 400;
					$module_res["msg"] = $module_res["msg"];
				}
			}
		} else {
			if (!empty($host["server_group"])) {
				if ($host["type"] == "dcim") {
					$module_res["status"] = 200;
				} elseif ($host["type"] == "dcimcloud") {
					$module_res["status"] = 200;
				} else {
					$module_res = $provision->renew($id);
				}
			} else {
				$module_res = ["status" => 200];
			}
		}
		if ($module_res["status"] == "success" || $module_res["status"] == 200) {
			hook("after_module_renew", ["params" => $hook_data]);
			if ($host["api_type"] == "resource") {
				$post_data = [];
				$post_data["host_id"] = $host["dcimid"];
				$module_res = resourceCurl($host["productid"], "/host/header", $post_data, 30, "GET");
				if ($module_res["status"] == 200) {
					$current_rate = \think\Db::name("host")->alias("a")->leftJoin("res_products b", "a.productid=b.productid")->where("a.id", $id)->value("b.current_rate");
					$amount = bcmul($module_res["data"]["host_data"]["amount"], $current_rate, 20);
					$invoiceid = \think\Db::name("invoice_items")->alias("a")->leftJoin("invoices b", "a.invoice_id=b.id")->where("a.type", "renew")->where("a.rel_id", $id)->where("b.status", "Paid")->order("a.id", "desc")->value("a.invoice_id");
					\think\Db::name("invoices")->where("id", $invoiceid)->update(["cost" => $amount, "supplier_invoiceid" => intval($supplier_invoiceid)]);
				}
			}
			$result["status"] = 200;
			$result["msg"] = $module_res["msg"] ?: "模块续费后操作执行成功";
		} else {
			hook("after_module_renew_failed", ["params" => $hook_data, "msg" => $module_res["msg"]]);
			$result["status"] = 406;
			$result["msg"] = $module_res["msg"];
		}
		return $result;
	}
	public function createTicket($id, $ticketid)
	{
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.uid,a.dcimid,a.billingcycle,b.server_type,b.type,b.api_type,b.zjmf_api_id,b.server_group")->leftJoin("products b", "a.productid=b.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return $result;
		}
		if ($host["api_type"] == "zjmf_api") {
		} elseif ($host["api_type"] == "normal") {
			if (!empty($host["server_group"])) {
				if ($host["type"] == "dcim") {
				} elseif ($host["type"] == "dcimcloud") {
				} else {
					$ticket_data = \think\Db::name("ticket")->field("id,tid,dptid,host_id,name,email,create_time,title,content,status,c,priority,attachment,last_reply_time,client_unread,admin_unread,star,is_auto_reply,token")->where("id", $ticketid)->find();
					$ticket_data["department"] = \think\Db::name("ticket_department")->field("id,name,description,email")->where("id", $ticket_data["dptid"])->find();
					$ticket_data["status_desc"] = \think\Db::name("ticket_status")->where("id", $ticket_data["status"])->value("title");
					$ticket_data["attachment"] = array_filter(explode(",", $ticket_data["attachment"]));
					foreach ($ticket_data["attachment"] as $k => $v) {
						$ticket_data["attachment"][$k] = config("ticket_attachments") . $v;
					}
					$provision = new Provision();
					$provision->createTicket($id, $ticket_data);
				}
			}
		}
	}
	public function replyTicket($id, $reply_data)
	{
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.uid,a.dcimid,a.billingcycle,b.server_type,b.type,b.api_type,b.zjmf_api_id,b.server_group")->leftJoin("products b", "a.productid=b.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return $result;
		}
		$params = [];
		$params["ticket"] = \think\Db::name("ticket")->field("id,tid,dptid,host_id,name,email,create_time,title,content,status,priority,attachment,last_reply_time,client_unread,admin_unread,star,is_auto_reply,token")->where("id", $reply_data["tid"])->find();
		if (empty($params["ticket"]["token"])) {
			$params["ticket"]["token"] = md5(uniqid() . randStr() . $ticket_data["tid"] . mt_rand(100, 999));
			\think\Db::name("ticket")->where("id", $reply_data["tid"])->update(["token" => $params["ticket"]["token"]]);
		}
		if ($host["api_type"] == "zjmf_api") {
		} elseif ($host["api_type"] == "normal") {
			if (!empty($host["server_group"])) {
				if ($host["type"] == "dcim") {
				} elseif ($host["type"] == "dcimcloud") {
				} else {
					$params["ticket"]["attachment"] = array_filter(explode(",", $params["ticket"]["attachment"]));
					foreach ($params["ticket"]["attachment"] as $k => $v) {
						$params["ticket"]["attachment"][$k] = config("ticket_attachments") . $v;
					}
					$params["ticket"]["department"] = \think\Db::name("ticket_department")->field("id,name,description,email")->where("id", $params["ticket"]["dptid"])->find();
					$params["ticket"]["status_desc"] = \think\Db::name("ticket_status")->where("id", $params["ticket"]["status"])->value("title");
					$params["ticket_reply"] = ["id" => \intval($reply_data["id"]), "tid" => $reply_data["tid"], "create_time" => $reply_data["create_time"], "content" => $reply_data["content"], "attachment" => []];
					$attachment = array_filter(explode(",", $reply_data["attachment"]));
					foreach ($attachment as $v) {
						$params["ticket_reply"]["attachment"][] = config("ticket_attachments") . $v;
					}
					if (isset($reply_data["admin_id"])) {
						$params["ticket_reply"]["user_type"] = "admin";
						$params["ticket_reply"]["uid"] = $reply_data["admin_id"];
						$params["ticket_reply"]["name"] = $reply_data["admin"];
					} else {
						$params["ticket_reply"]["user_type"] = "user";
						$params["ticket_reply"]["uid"] = $reply_data["uid"];
						$params["ticket_reply"]["name"] = $params["ticket"]["name"] ?: $params["user_info"]["username"];
					}
					$provision = new Provision();
					$provision->replyTicket($id, $params);
				}
			}
		}
	}
	public function rescueSystem($id, $system)
	{
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email,a.uid,b.server_group")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return $result;
		}
		if (!$this->is_admin) {
			if ($host["domainstatus"] != "Active" || request()->uid != $host["uid"]) {
				$result["status"] = 406;
				$result["msg"] = "不能执行该操作";
				return $result;
			}
		}
		$provision = new Provision();
		$provision->is_admin = $this->is_admin;
		$hook_data = $provision->getParams($id);
		$hook_res = hook("before_module_rescue_system", ["params" => $hook_data]);
		if (!empty($hook_res)) {
			foreach ($hook_res as $v) {
				if (is_array($v)) {
					$hook_data = array_merge($hook_data, $v);
				}
			}
			if ($hook_data["exit_module"]) {
				$result["status"] = 406;
				$result["msg"] = "当前操作被HOOK代码中断";
				return $result;
			}
		}
		if ($host["api_type"] == "zjmf_api") {
			$post_data["id"] = $host["dcimid"];
			$post_data["func"] = "rescueSystem";
			$post_data["system"] = $system;
			$post_data["is_api"] = 1;
			$module_res = zjmfCurl($host["zjmf_api_id"], "/provision/default", $post_data);
		} elseif ($host["api_type"] == "resource") {
			if (function_exists("resourceCurl")) {
				$post_data["id"] = $host["dcimid"];
				$post_data["func"] = "rescueSystem";
				$post_data["is_api"] = 1;
				$post_data["system"] = $system;
				$module_res = resourceCurl($host["productid"], "/provision/default", $post_data);
			}
		} else {
			if (!empty($host["server_group"])) {
				if ($host["type"] == "dcim") {
					$dcim = new Dcim();
					$dcim->is_admin = $this->is_admin;
					if (!$this->is_admin) {
						$check = check_dcim_auth($id, $host["uid"], "rescue");
						if ($check["status"] == 200) {
							$module_res = $dcim->rescue($id, $system);
						} else {
							$module_res = $check;
						}
					} else {
						$module_res = $dcim->rescue($id, $system);
					}
				} elseif ($host["type"] == "dcimcloud") {
					$temp_pass = input("post.temp_pass");
					$dcimcloud = new DcimCloud();
					$dcimcloud->is_admin = $this->is_admin;
					$module_res = $dcimcloud->rescue($id, $system, $temp_pass);
				} else {
					$module_res = $provision->RescueSystem($id);
				}
			} else {
				$module_res = ["status" => 200];
			}
		}
		if ($module_res["status"] == "success" || $module_res["status"] == 200) {
			hook("after_module_rescue_system", ["params" => $hook_data]);
			active_log_final(sprintf("模块命令:救援系统发起成功#Host ID:%d", $id), $host["uid"], 2, $id);
			$result["status"] = 200;
			$result["msg"] = $module_res["msg"] ?: "救援系统发起成功";
		} else {
			hook("after_module_rescue_system_failed", ["params" => $hook_data, "msg" => $module_res["msg"]]);
			active_log_final(sprintf("模块命令:救援系统发起失败#Host ID:%d - 原因:%s", $id, $module_res["msg"]), $host["uid"], 2, $id);
			$result["status"] = 406;
			$result["msg"] = $module_res["msg"];
		}
		return $result;
	}
}