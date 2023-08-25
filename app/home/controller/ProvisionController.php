<?php

namespace app\home\controller;

/**
 * @title 前台service module及接口
 * @description 接口说明：前台模块功能及接口
 */
class ProvisionController extends CommonController
{
	/**
	 * @title 执行模块默认方法
	 * @description 执行开机,关机,重启,重装系统
	 * @param       .name:id type:int|array require:1 default: other: desc:hostid
	 * @param       .name:func type:string require:1 default: other: desc:执行的方法
	 * @param       .name:os type:int require:1 default: other: desc:重装系统的操作系统id
	 * @param       .name:code type:int require:1 default: other: desc:验证码
	 * @param       .name:is_api type:int require:0 default: other: desc:1表示是api接口请求,0否(此参数是为了二次验证增加)
	 * @author hh
	 * @url         /provision/default
	 * @method      POST
	 * @time        2020-06-28
	 */
	public function execute()
	{
		$func = input("post.func", "");
		$os = input("post.os", 0, "intval");
		if (in_array($func, ["on", "off", "reboot", "hard_off", "hard_reboot", "status"])) {
			$id = input("post.id");
			if (is_array($id)) {
				$id = array_unique(array_filter($id, function ($x) {
					return is_numeric($x) && $x > 0;
				}));
			} else {
				$id = \intval($id);
			}
		} else {
			$id = input("post.id", 0, "int");
		}
		if (empty($id)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		if (!intval(request()->is_api)) {
			$res = secondVerifyResultHome($func);
			if ($res["status"] != 200) {
				return jsons($res);
			}
		}
		$host = new \app\common\logic\Host();
		$host->is_admin = false;
		switch ($func) {
			case "on":
				if (is_numeric($id)) {
					$result = $host->on($id);
				}
				break;
			case "off":
				if (is_numeric($id)) {
					$result = $host->off($id);
				}
				break;
			case "reboot":
				if (is_numeric($id)) {
					$result = $host->reboot($id);
				}
				break;
			case "hard_off":
				if (is_numeric($id)) {
					$result = $host->hardOff($id);
				}
				break;
			case "hard_reboot":
				if (is_numeric($id)) {
					$result = $host->hardReboot($id);
				}
				break;
			case "vnc":
				$result = $host->vnc($id);
				break;
			case "status":
				$result = $host->status($id);
				break;
			case "reinstall":
				$port = input("post.port", 0, "intval");
				$result = $host->reinstall($id, $os, $port);
				break;
			case "crack_pass":
				$password = input("post.password");
				$result = $host->crackPass($id, $password);
				break;
			case "rescue_system":
				$system = input("post.system", 1, "intval");
				$result = $host->rescueSystem($id, $system);
				break;
			case "suspend":
				$reason = input("post.reason", "");
				$result = $host->suspend($id, "self", $reason);
				$is_api = intval(request()->is_api);
				$logic_run_map = new \app\common\logic\RunMap();
				$model_host = new \app\common\model\HostModel();
				$data_i = [];
				$data_i["host_id"] = $id;
				$data_i["active_type_param"] = [$id, "self", $reason, 0];
				$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
				if ($result["status"] == 200) {
					$data_i["description"] = " 模块命令 - 暂停 Host ID:{$id}的产品成功";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 400, 2, 2);
					}
					if (!$is_zjmf && $is_api) {
						$logic_run_map->saveMap($data_i, 1, 500, 2, 2);
					}
					if (!$is_zjmf && !$is_api) {
						$logic_run_map->saveMap($data_i, 1, 200, 2, 2);
					}
				} else {
					$data_i["description"] = " 模块命令 - 暂停 Host ID:{$id}的产品失败：{$result["msg"]}";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 400, 2, 2);
					}
					if (!$is_zjmf && $is_api) {
						$logic_run_map->saveMap($data_i, 0, 500, 2, 2);
					}
					if (!$is_zjmf && !$is_api) {
						$logic_run_map->saveMap($data_i, 0, 200, 2, 2);
					}
				}
				break;
			case "unsuspend":
				$result = $host->unsuspend($id, 0, "self");
				$is_api = intval(request()->is_api);
				$logic_run_map = new \app\common\logic\RunMap();
				$model_host = new \app\common\model\HostModel();
				$data_i = [];
				$data_i["host_id"] = $id;
				$data_i["active_type_param"] = [$id, 0, "self", 0];
				$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
				if ($result["status"] == 200) {
					$data_i["description"] = " 模块命令 - 解除暂停 Host ID:{$id}的产品成功";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 400, 3, 2);
					}
					if (!$is_zjmf && $is_api) {
						$logic_run_map->saveMap($data_i, 1, 500, 3, 2);
					}
					if (!$is_zjmf && !$is_api) {
						$logic_run_map->saveMap($data_i, 1, 200, 3, 2);
					}
				} else {
					$data_i["description"] = " 模块命令 - 解除暂停 Host ID:{$id}的产品失败：{$result["msg"]}";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 400, 3, 2);
					}
					if (!$is_zjmf && $is_api) {
						$logic_run_map->saveMap($data_i, 0, 500, 3, 2);
					}
					if (!$is_zjmf && !$is_api) {
						$logic_run_map->saveMap($data_i, 0, 200, 3, 2);
					}
				}
				break;
			default:
				$result["status"] = 406;
				$result["msg"] = lang("NO_SUPPORT_FUNCTION");
				break;
		}
		if (is_array($id) && $func != "status") {
			header("Content-Length: {$size}");
			header("Connection: close");
			header("HTTP/1.1 200 OK");
			header("Content-Type: application/json;charset=utf-8");
			echo json_encode(["status" => 200, "msg" => "请求发起成功"]);
			ignore_user_abort(true);
			set_time_limit(count($id) * 60);
			switch ($func) {
				case "on":
					$result = $host->on($id);
					break;
				case "off":
					$result = $host->off($id);
					break;
				case "reboot":
					$result = $host->reboot($id);
					break;
				case "hard_off":
					$result = $host->hardOff($id);
					break;
				case "hard_reboot":
					$result = $host->hardReboot($id);
					break;
				default:
					break;
			}
			exit;
		} else {
			return json($result);
		}
	}
	/**
	 * 时间 2020-07-01
	 * @title 获取自定义内容
	 * @desc 获取自定义内容
	 * @url /provision/custom/content
	 * @method  GET
	 * @param   .name:id type:int require:1 default: other: desc:hostid
	 * @param   .name:key type:string require:1 default: other: desc:module_client_area里面的key
	 * @param   .name:jwt type:string require:1 default:
	 * @return  html内容
	 * @author hh
	 */
	public function getClientAreaContent()
	{
		$userGetCookie = userGetCookie();
		if ($userGetCookie) {
			$jwt = $userGetCookie;
		} else {
			$jwt = input("get.jwt");
			$jwt = html_entity_decode($jwt);
		}
		$key = config("jwtkey");
		$v10 = input("get.v10", false);
		try {
			if (empty($jwt) || $jwt == "null" || count(explode(".", $jwt)) != 3) {
				throw new \Exception("请登陆后再试");
			}
			$tmp = \think\facade\Cache::get("client_user_login_token_" . $jwt);
			if (!$tmp) {
				throw new \Exception("请登陆后再试");
			}
			$jwtAuth = json_encode(\Firebase\JWT\JWT::decode($jwt, $key, ["HS256"]));
			$authInfo = json_decode($jwtAuth, true);
			$msg = [];
			if (!empty($authInfo["userinfo"])) {
				$checkJwtToken = ["status" => 1001, "msg" => "Token验证通过", "id" => $authInfo["userinfo"]["id"], "nbf" => $authInfo["nbf"], "ip" => $authInfo["ip"], "contactid" => $authInfo["userinfo"]["contactid"] ?? 0, "username" => $authInfo["userinfo"]["username"]];
			} else {
				throw new \Exception("Token验证不通过,用户不存在");
			}
		} catch (\Firebase\JWT\SignatureInvalidException $e) {
			throw new \Exception("Token验证不通过,用户不存在");
		} catch (\Firebase\JWT\ExpiredException $e) {
			throw new \Exception("登录已过期");
		} catch (\think\Exception $e) {
			exit($e->getMessage());
		}
		$pass = \think\facade\Cache::get("client_user_update_pass_" . $tmp);
		if ($pass && $checkJwtToken["nbf"] < $pass) {
			return json(["status" => 405, "msg" => "密码已修改,请重新登陆"]);
		}
		$ip_check = configuration("home_ip_check");
		if (get_client_ip() !== $checkJwtToken["ip"] && $ip_check == 1) {
			return json(["status" => 405, "msg" => "登录失效,请重新登录"]);
		}
		if ($checkJwtToken["status"] == 1001 && $tmp == $checkJwtToken["id"]) {
		} else {
			return json(["status" => 405, "msg" => "请登陆后再试"]);
		}
		$id = input("get.id", 0, "int");
		$key = input("get.key", "");
		if (empty($id)) {
			return \think\Response::create("")->code(200);
		}
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		if (empty($host)) {
			return \think\Response::create("")->code(200);
		}
		if ($checkJwtToken["id"] != $host["uid"]) {
			return \think\Response::create("")->code(200);
		}
		if ($host["api_type"] == "zjmf_api") {
			$post_data["id"] = $host["dcimid"];
			$post_data["key"] = $key;
			$post_data["api_url"] = request()->domain() . request()->rootUrl() . "/provision/custom/" . $id;
			$post_data["now_jwt"] = $jwt;
			$res = zjmfCurl($host["zjmf_api_id"], "/zjmf_api/provision/custom/content?jwt=" . urlencode($jwt), $post_data);
			if ($res["status"] == 200) {
				$html = $res["data"]["html"];
			} else {
				$html = "";
			}
		} elseif ($host["api_type"] == "resource") {
			$post_data["id"] = $host["dcimid"];
			$post_data["key"] = $key;
			$post_data["api_url"] = request()->domain() . request()->rootUrl() . "/provision/custom/" . $id;
			$post_data["now_jwt"] = $jwt;
			$res = resourceCurl($host["productid"], "/zjmf_api/provision/custom/content?jwt=" . urlencode($jwt), $post_data);
			if ($res["status"] == 200) {
				$html = $res["data"]["html"];
			} else {
				$html = "";
			}
		} elseif ($host["type"] == "dcimcloud") {
			$dcimcloud = new \app\common\logic\DcimCloud();
			$html = $dcimcloud->moduleClientAreaDetail($id, $key, "", $v10);
			if ($v10) {
				return json($html);
			}
		} elseif ($host["type"] == "dcim") {
			$dcim = new \app\common\logic\Dcim();
			$html = $dcim->moduleClientAreaDetail($id, $key);
		} else {
			$provision = new \app\common\logic\Provision();
			$html = $provision->clientAreaDetail($id, $key);
		}
		return \think\Response::create($html)->code(200);
	}
	public function ssl()
	{
	}
	/**
	 * 时间 2020-08-06
	 * @title 获取自定义内容
	 * @desc 获取自定义内容
	 * @url /zjmf_api/provision/custom/content
	 * @method  POST
	 * @author hh
	 * @param   .name:id type:int require:1 default: other: desc:hostid
	 * @param   .name:key type:string require:1 default: other: desc:module_client_area里面的key
	 * @param   .name:api_url type:string require:1 default: other: desc:替换原来模板内的接口地址
	 * @return  html:html内容
	 */
	public function postClientAreaContent(\think\Request $request)
	{
		$uid = $request->uid;
		$id = input("post.id", 0, "intval");
		$key = input("post.key", "");
		$api_url = input("post.api_url", "");
		if (empty($id)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		if ($uid != $host["uid"]) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		if ($host["api_type"] == "zjmf_api") {
			$post_data["id"] = $host["dcimid"];
			$post_data["key"] = $key;
			$post_data["api_url"] = $api_url;
			$post_data["now_jwt"] = input("post.now_jwt");
			$result = zjmfCurl($host["zjmf_api_id"], "/zjmf_api/provision/custom/content?jwt=" . urlencode($post_data["now_jwt"]), $post_data);
		} elseif ($host["api_type"] == "resource") {
			$post_data["id"] = $host["dcimid"];
			$post_data["key"] = $key;
			$post_data["api_url"] = $api_url;
			$post_data["now_jwt"] = input("post.now_jwt");
			$result = resourceCurl($host["productid"], "/zjmf_api/provision/custom/content?jwt=" . urlencode($post_data["now_jwt"]), $post_data);
		} else {
			if ($host["type"] == "dcimcloud") {
				$dcimcloud = new \app\common\logic\DcimCloud();
				$html = $dcimcloud->moduleClientAreaDetail($id, $key, $api_url);
			} elseif ($host["type"] == "dcim") {
				$dcim = new \app\common\logic\Dcim();
				$html = $dcim->moduleClientAreaDetail($id, $key, $api_url);
			} else {
				$provision = new \app\common\logic\Provision();
				$html = $provision->clientAreaDetail($id, $key, $api_url);
			}
			$result = [];
			$result["status"] = 200;
			$result["data"]["html"] = $html;
		}
		return jsonrule($result);
	}
	/**
	 * 时间 2020-07-02
	 * @title 执行自定义模块方块
	 * @desc 执行自定义模块方块
	 * @url /provision/custom/:id
	 * @method  POST
	 * @author hh
	 * @version v1
	 * @param   .name:func type:string require:1 default: other: desc:执行的方法
	 * @return  [type] [description]
	 */
	public function customFunc($id)
	{
		$func = input("post.func", "");
		if (empty($id)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return json($result);
		}
		if (empty($func)) {
			$result["status"] = 406;
			$result["msg"] = "方法不能为空";
			return json($result);
		}
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return json($result);
		}
		if ($host["domainstatus"] != "Active" || request()->uid != $host["uid"]) {
			$result["status"] = 406;
			$result["msg"] = "不能执行该操作";
			return json($result);
		}
		if ($host["api_type"] == "zjmf_api") {
			$res = zjmfCurl($host["zjmf_api_id"], "/provision/custom/" . $host["dcimid"], input("post."));
		} elseif ($host["api_type"] == "resource") {
			$res = resourceCurl($host["productid"], "/provision/custom/" . $host["dcimid"], input("post."));
		} elseif ($host["type"] == "dcim") {
			$dcim = new \app\common\logic\Dcim();
			$allow_func = $dcim->moduleAllowFunction($id);
			if (in_array($func, $allow_func)) {
				$res = $dcim->{$func}($id);
			} else {
				$result["status"] = 406;
				$result["msg"] = "不能执行该操作";
				return json($result);
			}
		} elseif ($host["type"] == "dcimcloud") {
			$dcimcloud = new \app\common\logic\DcimCloud();
			$allow_func = $dcimcloud->moduleAllowFunction($id);
			if (in_array($func, $allow_func)) {
				$res = $dcimcloud->{$func}($id);
			} else {
				$result["status"] = 406;
				$result["msg"] = "不能执行该操作";
				return json($result);
			}
		} else {
			$provision = new \app\common\logic\Provision();
			$res = $provision->execCustomFunc($func, $id);
		}
		$result = $res;
		if ($res["status"] == "success" || $res["status"] == 200) {
			$result["status"] = 200;
			$result["msg"] = $res["msg"] ?: "";
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"] ?: "";
		}
		return json($result);
	}
	/**
	 * 时间 2020-07-06
	 * @title 获取模块图表数据
	 * @desc 获取模块图表数据
	 * @url /provision/chart/:id
	 * @method  GET
	 * @author hh
	 * @version v1
	 * @param   .name:type type:string require:1 default: other: desc:module_chart里面的type
	 * @param   .name:select type:string require:0 default: other: desc:module_chart里面的select的value
	 * @param   .name:start type:int require:0 default: desc:开始毫秒时间戳
	 * @param   .name:end type:int require:0 default: desc:结束毫秒时间戳
	 * @return  unit:单位
	 * @return  chart_type:line线性图
	 * @return  list:图表数据@
	 * @list  time:时间
	 * @value  value:值
	 * @return  label:对应list鼠标over显示内容
	 */
	public function getChartData($id)
	{
		$type = input("get.type");
		$select = input("get.select");
		$params = ["type" => input("get.type"), "select" => input("get.select"), "start" => input("get.start"), "end" => input("get.end")];
		if (!is_numeric($params["end"]) || empty($params["end"])) {
			$params["end"] = time() . "000";
		}
		if (!is_numeric($params["start"]) || empty($params["start"])) {
			$params["start"] = time() - 604800 . "000";
		}
		if (empty($id)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return json($result);
		}
		if (empty($type)) {
			$result["status"] = 406;
			$result["msg"] = "类型错误";
			return json($result);
		}
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.regdate,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return json($result);
		}
		if ($host["domainstatus"] != "Active" || request()->uid != $host["uid"]) {
			$result["status"] = 406;
			$result["msg"] = "不能执行该操作";
			return json($result);
		}
		if ($params["start"] < $host["regdate"] . "000") {
			$params["start"] = $host["regdate"] . "000";
		}
		if ($host["api_type"] == "zjmf_api") {
			$res = zjmfCurl($host["zjmf_api_id"], "/provision/chart/" . $host["dcimid"], $params, 30, "GET");
		} elseif ($host["api_type"] == "resource") {
			$res = resourceCurl($host["productid"], "/provision/chart/" . $host["dcimid"], $params, 30, "GET");
		} else {
			if ($host["type"] == "dcimcloud") {
				$dcimcloud = new \app\common\logic\DcimCloud();
				$res = $dcimcloud->getChartData($id, $params);
			} else {
				$provision = new \app\common\logic\Provision();
				$res = $provision->getChartData($id, $params);
			}
		}
		if ($res["status"] == "success") {
			$result["status"] = 200;
			$result["data"] = $res["data"] ?: [];
			if (empty($result["data"]["list"])) {
				$result["data"]["list"] = [];
				foreach ($res["data"]["label"] as $v) {
					$result["data"]["list"][] = [];
				}
			}
		} elseif ($res["status"] == 200) {
			$result = $res;
		} else {
			$result["status"] = 400;
			$result["msg"] = $res["msg"] ?: "";
		}
		return json($result);
	}
	/**
	 * @title 执行模块自定义按钮方法
	 * @description 执行模块自定义按钮方法
	 * @author huanghao
	 * @url         /provision/button
	 * @method      POST
	 * @time        2020-07-29
	 * @param       .name:id type:int require:1 default: other: desc:服务id
	 * @param       .name:func type:string require:1 default: other: desc:自定义方法名称
	 * @return      
	 */
	public function execCustomButton()
	{
		$hostid = input("post.id", 0, "int");
		$func = input("post.func", "");
		$host = \think\Db::name("host")->alias("h")->field("h.id,h.serverid,h.dcimid,h.uid,p.type,p.api_type,p.zjmf_api_id,p.config_option1")->leftjoin("products p", "h.productid=p.id")->where("h.id", $hostid)->find();
		if ($host["api_type"] == "zjmf_api") {
			$post_data["id"] = $host["dcimid"];
			$post_data["func"] = $func;
			$result = zjmfCurl($params["zjmf_api_id"], "/provision/button", $post_data);
		} elseif ($host["api_type"] == "normal") {
			if ($host["type"] == "dcimcloud") {
				$dcimcloud = new \app\common\logic\DcimCloud();
				$dcimcloud->is_admin = false;
				$result = $dcimcloud->execCustomButton($hostid, $func);
			} elseif ($host["type"] == "dcim") {
				if ($host["config_option1"] == "bms") {
					$dcim = new \app\common\logic\Dcim();
					$dcim->is_admin = false;
					$result = $dcim->execCustomButton($host, $func);
				} else {
					$result["status"] = 400;
					$result["msg"] = "接口类型错误";
				}
			} else {
				$provision = new \app\common\logic\Provision();
				$result = $provision->execClientButton($hostid, $func);
				$result["status"] = $result["status"] == "success" || $result["status"] == 200 ? 200 : 406;
				if ($result["status"] == 200) {
					$result["url"] = $result["url"] ?: "";
				}
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = "接口类型错误";
		}
		return jsonrule($result);
	}
	public function sslCertCustomButton()
	{
		try {
			$func = input("post.func", "");
			$id = input("post.id", 0);
			$req = $this->request;
			if (empty($id)) {
				$result["status"] = 406;
				$result["msg"] = "ID错误";
				return json($result);
			}
			if (empty($func)) {
				$result["status"] = 406;
				$result["msg"] = "方法不能为空";
				return json($result);
			}
			$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.regdate,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
			if (empty($host)) {
				$result["status"] = 406;
				$result["msg"] = "ID错误";
				return json($result);
			}
			if ($host["domainstatus"] == "Pending" || request()->uid != $host["uid"]) {
				$result["status"] = 406;
				$result["msg"] = "不能执行该操作";
				return json($result);
			}
			$provision = new \app\common\logic\Provision();
			if ($host["api_type"] == "zjmf_api") {
				$post_data = $req->param();
				$post_data["id"] = $host["dcimid"];
				$post_data["func"] = $func;
				$res = zjmfCurl($host["zjmf_api_id"], "/provision/sslCertFunc", $post_data);
			} else {
				if ($host["type"] == "ssl") {
					$res = $provision->sslCertCustomButton($func, $req, $id);
				} else {
					$res = $provision->moduleCustomButton($func, $req, $id);
				}
			}
			$result = $res;
			if ($res["status"] == "success" || $res["status"] == 200) {
				if ($host["api_type"] == "zjmf_api") {
					if ($result["data"]["host"]) {
						\think\Db::name("host")->where("id", $id)->update(["domainstatus" => $result["data"]["host"]["domainstatus"]]);
					}
				}
				$result["status"] = 200;
				$result["msg"] = $res["msg"] ?: "";
			} else {
				$result["status"] = 400;
				$result["msg"] = $res["msg"] ?: "";
			}
			return json($result);
		} catch (\Throwable $e) {
			throw $e;
		}
	}
	public function sslCertDown($orderNo)
	{
		if (empty($orderNo)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return json($result);
		}
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.regdate,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $orderNo)->find();
		if (empty($host)) {
			$result["status"] = 406;
			$result["msg"] = "ID错误";
			return json($result);
		}
		if ($host["domainstatus"] == "Pending" || request()->uid != $host["uid"]) {
			$result["status"] = 406;
			$result["msg"] = "不能执行该操作";
			return json($result);
		}
		$result = ["orderInfo" => "", "downLoad" => ""];
		if ($host["api_type"] == "zjmf_api") {
			$res = zjmfCurl($host["zjmf_api_id"], "/provision/sslCertFunc", ["id" => $host["dcimid"], "func" => "getAllInfo"]);
			if ($res["status"] == 200) {
				$result = ["orderInfo" => $res["data"]["orderInfo"], "downLoad" => $res["data"]["downLoad"]];
			} else {
				return json($res);
			}
		} else {
			$result["orderInfo"] = \think\Db::name("certssl_orderinfo")->where("hostid", $orderNo)->find();
			if ($result["orderInfo"]) {
				$result["orderInfo"]["domainNames_arr"] = explode(PHP_EOL, $result["orderInfo"]["domainNames"]);
				$result["orderInfo"]["domainNames_arr"] = array_filter($result["orderInfo"]["domainNames_arr"]);
				$result["downLoad"] = \think\Db::name("certssl_download")->where("co_id", $result["orderInfo"]["id"])->find();
			}
		}
		$path = $this->assembleDownloadInfo($result);
		if (!$path) {
			throw new \think\Exception("资源不存在");
		}
		if (!file_exists(CMF_ROOT . "public/" . $path)) {
			throw new \think\Exception("证书文件不存在，请联系管理员！");
		}
		return download(CMF_ROOT . "public/" . $path, "cert" . time() . ".zip");
	}
	public function assembleDownloadInfo($model)
	{
		$dir = "sslcert/sslcert_" . $model["orderInfo"]["orderNo"];
		$this->mkdirs($dir);
		file_put_contents($dir . "/cert.pem", $model["downLoad"]["certContent"]);
		file_put_contents($dir . "/rootcert.pem", $model["downLoad"]["midCertContent"]);
		file_put_contents($dir . "/privatekey.key", $model["orderInfo"]["csr_key"]);
		$file_name = $dir . "/ceshi.zip";
		$zip = new \ZipArchive();
		$zip->open($file_name, \ZipArchive::CREATE);
		$zip->addFile($dir . "/cert.pem", basename($dir . "/cert.pem"));
		$zip->addFile($dir . "/rootcert.pem", basename($dir . "/rootcert.pem"));
		$zip->addFile($dir . "/privatekey.key", basename($dir . "/privatekey.key"));
		$zip->close();
		unlink($dir . "/cert.pem");
		unlink($dir . "/rootcert.pem");
		unlink($dir . "/privatekey.key");
		return $file_name;
	}
	private function mkdirs($dir, $mode = 493)
	{
		if (is_dir($dir) || @mkdir($dir, $mode)) {
			return true;
		}
		if (!$this->mkdirs(dirname($dir), $mode)) {
			return false;
		}
		return @mkdir($dir, $mode);
	}
}