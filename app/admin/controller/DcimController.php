<?php

namespace app\admin\controller;

/**
 * @title 后台对接DCIM管理
 * @description 接口说明
 */
class DcimController extends AdminBaseController
{
	private $is_certifi = ["traffic" => 0, "kvm" => 0, "ikvm" => 0, "bmc" => 0, "reinstall" => 0, "reboot" => 0, "on" => 0, "off" => 0, "novnc" => 0, "rescue" => 0, "crack_pass" => 0, "enable_ip_custom" => 0];
	/**
	 * @time 2020-05-13
	 * @title 添加服务器
	 * @description 添加服务器
	 * @url /admin/dcim/server
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param  .name:name type:string require:1 desc:名称
	 * @param  .name:hostname type:string require:1 desc:地址(IP或者域名)
	 * @param  .name:username type:string require:0 desc:用户名
	 * @param  .name:password type:string require:0 desc:密码
	 * @param  .name:port type:string require:0 desc:端口
	 * @param  .name:secure type:int require:0 desc:是否https(0不是1是) default:0
	 * @param  .name:disabled type:int require:0 desc:是否启用(0启用1禁用) default:1
	 * @param  .name:user_prefix type:string require:0 desc:财务标识
	 */
	public function addServer()
	{
		$params = input("post.");
		$validate = new \app\admin\validate\DcimServerValidate();
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$insert = ["name" => $params["name"], "hostname" => $params["hostname"] ?? "", "username" => $params["username"] ?? "", "password" => aesPasswordEncode(html_entity_decode($params["password"], ENT_QUOTES)), "port" => $params["port"] ?? 0, "secure" => $params["secure"] ?? 0, "disabled" => $params["disabled"] ?? 0, "server_type" => "dcim"];
		if (!empty($params["user_prefix"])) {
			$insert["accesshash"] = "user_prefix:" . $params["user_prefix"];
		} else {
			$insert["accesshash"] = "";
		}
		$auth = ["traffic" => "off", "kvm" => "off", "ikvm" => "off", "bmc" => "on", "reinstall" => "off", "reboot" => "off", "on" => "off", "off" => "off", "novnc" => "off", "rescue" => "off", "crack_pass" => "off", "enable_ip_custom" => "off"];
		$is_certifi = $this->is_certifi;
		\think\Db::startTrans();
		try {
			$group = \think\Db::name("server_groups")->insertGetId(["name" => $params["name"], "system_type" => "dcim"]);
			$insert["gid"] = $group;
			$id = \think\Db::name("servers")->insertGetId($insert);
			if (empty($id)) {
				throw new \Exception("error");
			}
			\think\Db::name("dcim_servers")->insert(["serverid" => $id, "auth" => json_encode($auth), "area" => "", "bill_type" => "month", "flow_remind" => "", "is_certifi" => json_encode($is_certifi)]);
			\think\Db::commit();
			active_log(sprintf($this->lang["Dcim_admin_addServer"], $id));
			$result["status"] = 200;
			$result["msg"] = lang("ADD SUCCESS");
			$dcim = new \app\common\logic\Dcim($id);
			$dcim->is_admin = true;
			$dcim->createApi($insert["hostname"]);
		} catch (\Exception $e) {
			\think\Db::rollback();
			$result["status"] = 406;
			$result["msg"] = $e->getMessage();
		}
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-13
	 * @title 修改服务器
	 * @description 修改服务器
	 * @url /admin/dcim/server
	 * @method  PUT
	 * @author huanghao
	 * @version v1
	 * @param  .name:id type:int require:1 desc:服务器ID
	 * @param  .name:name type:string require:1 desc:名称
	 * @param  .name:hostname type:string require:1 desc:地址(IP或者域名)
	 * @param  .name:username type:string require:0 desc:用户名
	 * @param  .name:password type:string require:0 desc:密码
	 * @param  .name:port type:string require:0 desc:端口
	 * @param  .name:secure type:int require:0 desc:是否https(0不是1是) default:0
	 * @param  .name:disabled type:int require:0 desc:是否启用(0启用1禁用) default:1
	 * @param  .name:reinstall_times type:int require:0 desc:重装次数 default:0
	 * @param  .name:buy_times type:int require:0 desc:启用购买重装 default:0
	 * @param  .name:reinstall_price type:float require:0 desc:重装价格 default:0
	 * @param  .name:bill_type type:string require:0 desc:流量计费方式(month自然月last_30days订购日至下月)
	 * @param  .name:percent type:array require:0 desc:流量提醒比例(单个比例范围1-100)
	 * @param  .name:tid type:array require:0 desc:流量提醒邮件模板
	 * @param  .name:traffic type:string require:0 desc:流量图(on开启off关闭)
	 * @param  .name:kvm type:string require:0 desc:kvm(on开启off关闭)
	 * @param  .name:ikvm type:string require:0 desc:ikvm(on开启off关闭)
	 * @param  .name:bmc type:string require:0 desc:重置BMC(on开启off关闭)
	 * @param  .name:reinstall type:string require:0 desc:重装系统(on开启off关闭)
	 * @param  .name:reboot type:string require:0 desc:重启(on开启off关闭)
	 * @param  .name:on type:string require:0 desc:开机(on开启off关闭)
	 * @param  .name:off type:string require:0 desc:关机(on开启off关闭)
	 * @param  .name:novnc type:string require:0 desc:novnc(on开启off关闭)
	 * @param  .name:rescue type:string require:0 desc:救援系统(on开启off关闭)
	 * @param  .name:crack_pass type:string require:0 desc:重置密码(on开启off关闭)
	 * @param  .name:enable_ip_custom type:string require:0 desc:是否启用IP自定义字段(on开启off关闭)
	 * @param  .name:area type:array require:0 desc:对应区域名称
	 * @param  .name:ip_customid type:int require:0 desc:IP自定义字段ID
	 * @param  .name:is_certifi[操作] type:array require:0 desc:是否实名,1是,0否,详情返回的is_certifi里的操作
	 * @param  .name:user_prefix type:string require:0 desc:财务标识
	 */
	public function editServer()
	{
		$params = input("post.");
		$id = $params["id"];
		$server_info = \think\Db::name("servers")->alias("a")->field("a.id,a.gid,a.name,a.hostname,a.username,a.password,a.port,a.secure,a.disabled,b.reinstall_times,b.buy_times,b.reinstall_price,b.auth,b.bill_type,b.area,b.is_certifi")->leftJoin("dcim_servers b", "a.id=b.serverid")->where("a.server_type", "dcim")->where("a.id", $id)->find();
		if (empty($server_info)) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$validate = new \app\admin\validate\DcimServerValidate();
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$update_server = ["name" => $params["name"], "hostname" => $params["hostname"] ?? ""];
		$dec = "";
		if ($params["name"] != $server_info["name"]) {
			$dec .= "名称由“" . $server_info["name"] . "”改为“" . $params["name"] . "”，";
		}
		if ($params["hostname"] != $server_info["hostname"]) {
			$dec .= "主机名由“" . $server_info["hostname"] . "”改为“" . $params["hostname"] . "”，";
		}
		if (isset($params["username"])) {
			$update_server["username"] = $params["username"] ?? "";
		}
		if ($params["username"] != $server_info["username"]) {
			$dec .= "用户名由“" . $server_info["username"] . "”改为“" . $params["username"] . "”，";
		}
		if (isset($params["password"])) {
			$update_server["password"] = aesPasswordEncode(html_entity_decode($params["password"], ENT_QUOTES));
			$params["password"] = $update_server["password"];
		}
		if ($params["password"] != $server_info["password"]) {
			$dec .= "密码有修改，";
		}
		if (isset($params["port"])) {
			$update_server["port"] = $params["port"] ?? 0;
		}
		if ($params["port"] != $server_info["port"]) {
			$dec .= "端口由“" . $server_info["port"] . "”改为“" . $params["port"] . "”，";
		}
		if (isset($params["secure"])) {
			$update_server["secure"] = $params["secure"] ?? 0;
		}
		if ($params["secure"] != $server_info["secure"]) {
			if ($params["secure"] == 1) {
				$dec .= "使用SSL连接模式“关闭”改为“开启”，";
			} else {
				$dec .= "使用SSL连接模式“开启”改为“关闭”，";
			}
		}
		if (isset($params["disabled"])) {
			$update_server["disabled"] = $params["disabled"] ?? 0;
		}
		if ($params["disabled"] != $server_info["disabled"]) {
			if ($params["disabled"] == 1) {
				$dec .= "由“禁用”改为“启用”，";
			} else {
				$dec .= "由“启用”改为“禁用”，";
			}
		}
		$auth = json_decode($server_info["auth"], true);
		foreach ($auth as $k => $v) {
			if (isset($params[$k]) && $k != "enable_ip_custom") {
				$auth[$k] = $params[$k];
			}
		}
		$close_ip_custom = false;
		if (in_array($params["enable_ip_custom"], ["on", "off"])) {
			if ($auth["enable_ip_custom"] == "on" && $params["enable_ip_custom"] == "off") {
				$close_ip_custom = true;
			}
			$auth["enable_ip_custom"] = $params["enable_ip_custom"];
		}
		$update_dcim_server = [];
		$update_dcim_server["auth"] = json_encode($auth);
		$update_dcim_server["is_certifi"] = json_encode($params["is_certifi"]);
		if (isset($params["reinstall_times"])) {
			$update_dcim_server["reinstall_times"] = $params["reinstall_times"];
		}
		if ($params["reinstall_times"] != $server_info["reinstall_times"]) {
			$dec .= "每周重装次数由“" . $server_info["reinstall_times"] . "”改为“" . $params["reinstall_times"] . "”，";
		}
		if (isset($params["buy_times"])) {
			$update_dcim_server["buy_times"] = $params["buy_times"];
		}
		if ($params["buy_times"] != $server_info["buy_times"]) {
			if ($params["buy_times"] == 0) {
				$dec .= "付费重装“启用”改为“禁用”，";
			} else {
				$dec .= "付费重装“禁用”改为“启用”，";
			}
		}
		if (isset($params["reinstall_price"])) {
			$update_dcim_server["reinstall_price"] = $params["reinstall_price"];
		}
		if ($params["reinstall_price"] != $server_info["reinstall_price"]) {
			$dec .= "重装单次价格由“" . $server_info["reinstall_price"] . "”改为“" . $params["reinstall_price"] . "”，";
		}
		if (isset($params["bill_type"])) {
			$update_dcim_server["bill_type"] = $params["bill_type"];
		}
		if ($params["bill_type"] != $server_info["bill_type"]) {
			$dec .= "流量计费方式由“" . $server_info["bill_type"] . "”改为“" . $params["bill_type"] . "”，";
		}
		if (isset($params["percent"]) && isset($params["tid"])) {
			if (count($params["percent"]) != count($params["tid"])) {
				$result["status"] = 400;
				$result["msg"] = "流量提醒设置错误";
				return jsonrule($result);
			}
			$update_dcim_server["flow_remind"] = [];
			foreach ($params["percent"] as $k => $v) {
				if (is_numeric($v) && ($v > 100 || $v <= 0)) {
					$result["status"] = 400;
					$result["msg"] = "比例只能是1-100";
					return jsonrule($result);
				}
				$update_dcim_server["flow_remind"][] = ["percent" => $v, "tid" => $params["tid"][$k]];
			}
			$update_dcim_server["flow_remind"] = json_encode($update_dcim_server["flow_remind"]);
			if ($params["flow_remind"] != $server_info["flow_remind"]) {
				$dec .= "流量提醒设置由“" . $server_info["flow_remind"] . "”改为“" . $params["flow_remind"] . "”，";
			}
		}
		if (isset($params["ip_customid"])) {
			$update_dcim_server["ip_customid"] = \intval($params["ip_customid"]);
		}
		if ($close_ip_custom) {
			$change_host = \think\Db::name("host")->field("id,dedicatedip,assignedips")->where("serverid", $id)->select()->toArray();
		}
		if (!empty($params["user_prefix"])) {
			$update_server["accesshash"] = "user_prefix:" . $params["user_prefix"];
		} else {
			$update_server["accesshash"] = "";
		}
		\think\Db::startTrans();
		try {
			\think\Db::name("server_groups")->where("id", $server_info["gid"])->update(["name" => $params["name"]]);
			\think\Db::name("servers")->where("id", $id)->update($update_server);
			\think\Db::name("dcim_servers")->where("serverid", $id)->update($update_dcim_server);
			if (!empty($change_host)) {
				foreach ($change_host as $v) {
					if (empty($v["assignedips"])) {
						continue;
					}
					$v["assignedips"] = explode(",", $v["assignedips"]);
					foreach ($v["assignedips"] as $kk => $vv) {
						if ($vv == $v["dedicatedip"]) {
							unset($v["assignedips"][$kk]);
							continue;
						}
						if (strpos($vv, "(") === false) {
							continue;
						}
						$vv = explode("(", $vv);
						if (strpos($vv[0], "/") !== false) {
							continue;
						}
						$v["assignedips"][$kk] = $vv[0];
					}
					$v["assignedips"] = empty($v["assignedips"]) ? "" : implode(",", $v["assignedips"]);
					\think\Db::name("host")->where("id", $v["id"])->update(["assignedips" => $v["assignedips"]]);
				}
			}
			\think\Db::commit();
			if (empty($dec)) {
				$dec .= "没有任何修改";
			}
			active_log(sprintf($this->lang["Dcim_admin_editServer"], $id, $dec));
			unset($dec);
			$result["status"] = 200;
			$result["msg"] = lang("UPDATE SUCCESS");
			$dcim = new \app\common\logic\Dcim($id);
			$dcim->is_admin = true;
			$dcim->createApi($update_server["hostname"]);
		} catch (\Exception $e) {
			\think\Db::rollback();
			$result["status"] = 400;
			$result["msg"] = lang("UPDATE FAIL");
		}
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-13
	 * @title 服务器详情
	 * @description 服务器详情
	 * @url /admin/dcim/server/:id
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:服务器ID
	 * @return  id:服务器ID
	 * @return  name:名称
	 * @return  hostname:服务器地址
	 * @return  username:用户名
	 * @return  password:密码
	 * @return  port:端口
	 * @return  secure:是否https(0不是1是)
	 * @return  disabled:是否禁用(0启用1禁用)
	 * @return  reinstall_times:重装次数限制
	 * @return  buy_times:超出次数是否可以购买次数
	 * @return  reinstall_price:重装次数价格
	 * @return  traffic:流量图(on开启off关闭)
	 * @return  kvm:kvm(on开启off关闭)
	 * @return  ikvm:ikvm(on开启off关闭)
	 * @return  bmc:重置bmc(on开启off关闭)
	 * @return  reinstall:重装系统(on开启off关闭)
	 * @return  reboot:重启(on开启off关闭)
	 * @return  on:开机(on开启off关闭)
	 * @return  off:关机(on开启off关闭)
	 * @return  novnc:novnc(on开启off关闭)
	 * @return  rescue:救援系统(on开启off关闭)
	 * @return  crack_pass:重置密码(on开启off关闭)
	 * @return  enable_ip_custom:是否启用自定义IP(on开启off关闭)
	 * @return  area:区域@
	 * @area  id:区域ID
	 * @area  area:区域代码
	 * @area  name:区域名称
	 * @return  bill_type:流量计费方式(month自然月last_30days订购日至下月)
	 * @return  flow_remind:流量提醒设置@
	 * flow_remind  percent:比例
	 * flow_remind  tid:邮件模板ID
	 * @return  email_template:邮件模板@
	 * email_template  id:邮件模板ID
	 * email_template  name:邮件模板名称
	 * @return  ip_customid:IP自定义字段ID
	 * @return  is_certifi:操作实名认证情况(array)
	 * @return  user_prefix:财务标识
	 */
	public function serverDetail($id)
	{
		$data = \think\Db::name("servers")->alias("a")->field("a.id,a.name,a.hostname,a.username,a.password,a.port,a.secure,a.disabled,a.accesshash,b.reinstall_times,b.buy_times,b.reinstall_price,b.auth,b.area,b.bill_type,b.flow_remind,b.ip_customid,b.is_certifi")->leftJoin("dcim_servers b", "a.id=b.serverid")->where("a.server_type", "dcim")->where("a.id", $id)->find();
		if (empty($data)) {
			return jsonrule(["status" => "error", "msg" => lang("ID_ERROR")]);
		}
		$data["password"] = aesPasswordDecode($data["password"]);
		$data["area"] = json_decode($data["area"], true) ?: [];
		$data["flow_remind"] = json_decode($data["flow_remind"], true) ?: [];
		$data["bill_type"] = $data["bill_type"] ?: "month";
		$auth = json_decode($data["auth"], true);
		unset($data["auth"]);
		$data = array_merge($data, $auth);
		$data["is_certifi"] = json_decode($data["is_certifi"], true) ?: $this->is_certifi;
		if (!isset($data["enable_ip_custom"])) {
			$data["enable_ip_custom"] = "off";
		}
		$data["ip_customid"] = $data["ip_customid"] ?: "";
		$accesshash = $data["accesshash"];
		unset($data["accesshash"]);
		if (!empty($accesshash)) {
			$accesshash = explode(":", trim($accesshash));
			unset($accesshash[0]);
			$data["user_prefix"] = trim(implode("", $accesshash));
		} else {
			$data["user_prefix"] = "";
		}
		$language = configuration("language");
		$config = config("language.list");
		$result["status"] = 200;
		$result["data"] = $data;
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-14
	 * @title 删除服务器
	 * @description 删除服务器
	 * @url /admin/dcim/server
	 * @method  DELETE
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:服务器ID 
	 */
	public function delServer()
	{
		$id = input("post.id", 0, "intval");
		$server = \think\Db::name("host")->where("serverid", $id)->find();
		$server_group = \think\Db::name("host")->alias("a")->leftJoin("servers b", "a.serverid = b.id")->leftJoin("server_groups c", "c.id = b.gid")->where("c.system_type", "dcim")->where("b.id", $id)->find();
		if (!empty($server) || !empty($server_group)) {
			return jsonrule(["status" => 400, "msg" => lang("SERVER_USING")]);
		} else {
			$info = \think\Db::name("servers")->where("server_type", "dcim")->where("id", $id)->find();
			if (empty($info)) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$product = \think\Db::name("products")->where("server_group", $info["gid"])->find();
			if (!empty($product)) {
				return jsonrule(["status" => 400, "msg" => lang("SERVER_USING")]);
			}
			\think\Db::startTrans();
			try {
				\think\Db::name("servers")->where("id", $id)->where("server_type", "dcim")->delete();
				\think\Db::name("server_groups")->where("id", $info["gid"])->where("system_type", "dcim")->delete();
				\think\Db::name("dcim_servers")->where("serverid", $id)->delete();
				\think\Db::commit();
				active_log(sprintf($this->lang["Dcim_admin_delServer"], $id));
				$result["status"] = 200;
				$result["msg"] = lang("DELETE SUCCESS");
			} catch (\Exception $e) {
				\think\Db::rollback();
				$result["status"] = 400;
				$result["msg"] = lang("DELETE FAIL");
			}
			return jsonrule($result);
		}
	}
	/**
	 * @time 2020-05-14
	 * @title 服务器列表
	 * @description 服务器列表
	 * @url /admin/dcim/server
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:page type:int require:0 desc:页数 default:1
	 * @param   .name:limit type:int require:0 desc:每页条数 default:10
	 * @param   .name:orderby type:string require:0 desc:排序(id,name,hostname,server_num,api_status) default:id
	 * @param   .name:sort type:string require:0 desc:排序方向(asc,desc) default:asc
	 * @param   .name:search type:string require:0 desc:搜索
	 * @return  list:列表数据@
	 * @list  id:服务器ID
	 * @list  name:服务器名称
	 * @list  hostname:服务器地址
	 * @list  server_num:服务器数量
	 * @list  api_status:连接状态
	 * @list  removable:是否可以删除
	 */
	public function serverList()
	{
		$page = input("get.page", 1, "intval");
		$limit = input("get.limit", 10, "intval");
		$orderby = input("get.orderby", "id");
		$sort = input("get.sort", "asc");
		$search = input("get.search", "");
		$page = $page > 0 ? $page : 1;
		$limit = $limit > 0 ? $limit : 10;
		if (!in_array($orderby, ["id", "name", "hostname", "server_num", "api_status"])) {
			$orderby = "id";
		}
		if (!in_array($sort, ["asc", "desc"])) {
			$sort = "asc";
		}
		$count = \think\Db::name("servers")->alias("a")->where("a.name LIKE '%{$search}%' OR a.hostname LIKE '%{$search}%'")->where("a.server_type", "dcim")->count();
		$data = \think\Db::name("servers")->alias("a")->field("a.id,a.name,a.hostname,count(DISTINCT b.id) server_num,c.api_status,count(DISTINCT d.id) product_num")->leftJoin("host b", "b.serverid=a.id AND (b.domainstatus=\"Active\" OR b.domainstatus=\"Suspended\")")->leftJoin("dcim_servers c", "c.serverid=a.id")->leftJoin("products d", "a.gid=d.server_group")->where("a.name LIKE '%{$search}%' OR a.hostname LIKE '%{$search}%'")->where("a.server_type", "dcim")->group("a.id")->order($orderby, $sort)->page($page)->limit($limit)->select()->toArray();
		$max_page = ceil($count / $limit);
		foreach ($data as $k => $v) {
			if ($v["server_num"] == 0 && $v["product_num"] == 0) {
				$data[$k]["removable"] = true;
			} else {
				$data[$k]["removable"] = false;
			}
		}
		$result["status"] = 200;
		$result["data"]["page"] = $page;
		$result["data"]["limit"] = $limit;
		$result["data"]["sum"] = $count;
		$result["data"]["max_page"] = $max_page;
		$result["data"]["orderby"] = $orderby;
		$result["data"]["sort"] = $sort;
		$result["data"]["list"] = $data;
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-13
	 * @title 获取服务器状态
	 * @description 获取服务器状态
	 * @url /admin/dcim/server/:id/status
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:服务器ID 
	 * @return  server_status:服务器状态(1连接测试成功,0失败)
	 */
	public function refreshServerStatus($id)
	{
		$server_info = \think\Db::name("servers")->field("id,hostname,username,password,secure,port,accesshash")->where("id", $id)->where("server_type", "dcim")->find();
		if (empty($server_info)) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$link_status = $dcim->init($server_info)->testLink();
		if (!empty($dcim->curl_error) || !empty($dcim->link_error_msg)) {
			\think\Db::name("dcim_servers")->where("serverid", $id)->update(["api_status" => 0]);
			$result["status"] = 200;
			if (!empty($dcim->curl_error)) {
				$result["msg"] = "连接失败curl错误：" . $dcim->curl_error;
			} else {
				$result["msg"] = $dcim->link_error_msg;
			}
			$result["server_status"] = 0;
		} else {
			$check_api = $dcim->checkApi($server_info["hostname"]);
			if ($check_api["status"] == 200) {
				\think\Db::name("dcim_servers")->where("serverid", $id)->update(["api_status" => 1]);
				$result["status"] = 200;
				$result["server_status"] = 1;
			} else {
				$create_api = $dcim->createApi($server_info["hostname"], false);
				if ($create_api["status"] == 200) {
					\think\Db::name("dcim_servers")->where("serverid", $id)->update(["api_status" => 1]);
					$result["status"] = 200;
					$result["server_status"] = 1;
				} else {
					\think\Db::name("dcim_servers")->where("serverid", $id)->update(["api_status" => 0]);
					$result["status"] = 200;
					$result["server_status"] = 0;
					$result["msg"] = "财务系统连接DCIM成功,但同步API未成功创建";
				}
			}
		}
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-14
	 * @title 刷新所有服务器状态
	 * @description 刷新所有服务器状态
	 * @url /admin/dcim/server/status
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @return  0:列表数据@
	 * @0  id:服务器ID
	 * @0  status:服务器状态(0连接失败1连接成功)
	 * @0  msg:连接描述
	 */
	public function refreshAllServerStatus()
	{
		$server_info = \think\Db::name("servers")->field("id,hostname,username,password,secure,port")->where("server_type", "dcim")->select()->toArray();
		if (empty($server_info)) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$data = [];
		foreach ($server_info as $v) {
			$protocol = $v["secure"] == 1 ? "https://" : "http://";
			$url = $protocol . $v["hostname"];
			if (!empty($v["port"])) {
				$url .= ":" . $v["port"];
			}
			$data[$v["id"]] = ["url" => $url . "/index.php?m=api&a=getHouse", "data" => ["username" => $v["username"], "password" => aesPasswordDecode($v["password"]) ?? ""]];
		}
		$res = batch_curl_post($data, 3);
		$result["data"] = [];
		foreach ($res as $k => $v) {
			$one["id"] = $k;
			if ($v["http_code"] != 200) {
				$one["status"] = 0;
				$one["msg"] = $v["msg"] ?? "";
			} else {
				$one["status"] = 1;
				$one["msg"] = "";
			}
			$result["data"][] = $one;
		}
		$result["status"] = 200;
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-13
	 * @title 流量包/重装下单记录
	 * @description 流量包/重装下单记录
	 * @url /admin/dcim/buy_record
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:page type:int require:0 desc:页数 default:1
	 * @param   .name:limit type:int require:0 desc:每页条数 default:10
	 * @param   .name:orderby type:string require:0 desc:排序(id,capacity,price,status,sale_times) default:id
	 * @param   .name:sort type:string require:0 desc:排序方向(asc,desc) default:asc
	 * @param   .name:search type:string require:0 desc:搜索
	 * @return  list:列表数据@
	 * @list  id:记录ID
	 * @list  uid:用户ID
	 * @list  price:价格
	 * @list  status:0未支付1已付款
	 * @list  create_time:创建时间
	 * @list  pay_time:支付时间
	 * @list  username:用户名
	 * @list  removable:是否可以删除
	 */
	public function listBuyRecord()
	{
		$page = input("get.page", 1, "intval");
		$limit = input("get.limit", 10, "intval");
		$orderby = input("get.orderby", "id");
		$sort = input("get.sort", "asc");
		$search = input("get.search", "");
		$getUserCtol = new GetUserController();
		$page = $page > 0 ? $page : 1;
		$limit = $limit > 0 ? $limit : 10;
		if (!in_array($orderby, ["id", "capacity", "price", "status", "sale_times"])) {
			$orderby = "id";
		}
		if (!in_array($sort, ["asc", "desc"])) {
			$sort = "asc";
		}
		$count = \think\Db::name("dcim_buy_record")->alias("a")->leftJoin("clients b", "a.uid=b.id")->whereLike("a.name|b.username|b.phonenumber|b.email", "%{$search}%");
		if ($getUserCtol->user["id"] != 1 && $getUserCtol->user["is_sale"]) {
			$count->whereIn("b.id", $getUserCtol->str);
		}
		$count = $count->count();
		$data = \think\Db::name("dcim_buy_record")->alias("a")->field("a.id,a.uid,a.name,a.price,a.status,a.create_time,a.pay_time,b.username,c.status as invoice_status,c.payment")->leftJoin("clients b", "a.uid=b.id")->leftJoin("invoices c", "a.invoiceid = c.id")->whereLike("a.name|b.username|b.phonenumber|b.email", "%{$search}%")->withAttr("payment", function ($value) {
			$gateways = gateway_list();
			foreach ($gateways as $v) {
				if ($v["name"] == $value) {
					return $v["title"];
				}
			}
		});
		if ($getUserCtol->user["id"] != 1 && $getUserCtol->user["is_sale"]) {
			$data->whereIn("b.id", $getUserCtol->str);
		}
		$data = $data->order($orderby, $sort)->page($page)->limit($limit)->select()->toArray();
		$max_page = ceil($count / $limit);
		foreach ($data as $k => $v) {
			$data[$k]["invoice_status"] = config("invoice_payment_status")[$v["invoice_status"]];
			if ($v["status"] == 0) {
				$data[$k]["removable"] = true;
			} else {
				$data[$k]["removable"] = false;
			}
		}
		$result["status"] = 200;
		$result["data"]["page"] = $page;
		$result["data"]["limit"] = $limit;
		$result["data"]["sum"] = $count;
		$result["data"]["max_page"] = $max_page;
		$result["data"]["orderby"] = $orderby;
		$result["data"]["sort"] = $sort;
		$result["data"]["list"] = $data;
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-13
	 * @title 删除购买记录
	 * @description 删除购买记录
	 * @url /admin/dcim/buy_record
	 * @method  DELETE
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:记录ID
	 */
	public function delRecord()
	{
		$id = input("post.id", 0, "intval");
		$record = \think\Db::name("dcim_buy_record")->where("id", $id)->find();
		if (empty($record)) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		if ($record["status"] == 1) {
			return jsonrule(["status" => 400, "msg" => "不能删除"]);
		}
		\think\Db::startTrans();
		try {
			$r = \think\Db::name("dcim_buy_record")->where("id", $id)->where("status", 0)->delete();
			\think\Db::name("orders")->where("invoiceid", $record["invoiceid"])->delete();
			\think\Db::name("invoices")->where("id", $record["invoiceid"])->delete();
			\think\Db::name("invoice_items")->where("invoice_id", $record["invoiceid"])->delete();
			active_log(sprintf($this->lang["Dcim_admin_delRecord"], $id));
			if (empty($r)) {
				throw new \Exception("error");
			}
			\think\Db::commit();
			$result["status"] = 200;
			$result["msg"] = lang("DELETE SUCCESS");
		} catch (\Exception $e) {
			\think\Db::rollback();
			$result["status"] = 406;
			$result["msg"] = lang("DELETE FAIL");
		}
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-13
	 * @title 流量包列表
	 * @description 流量包列表
	 * @url /admin/dcim/flowpacket
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:page type:int require:0 desc:页数 default:1
	 * @param   .name:limit type:int require:0 desc:每页条数 default:10
	 * @param   .name:orderby type:string require:0 desc:排序(id,capacity,price,status,sale_times) default:id
	 * @param   .name:sort type:string require:0 desc:排序方向(asc,desc) default:asc
	 * @param   .name:search type:string require:0 desc:搜索
	 * @return  list:列表数据@
	 * @list  id:流量包ID
	 * @list  name:流量包名称
	 * @list  capacity:流量包容量
	 * @list  price:流量包价格
	 * @list  status:状态(0禁用1启用)
	 * @list  sale_times:销售次数
	 * @list  stock:库存总量(0表示不限)
	 * @list  create_time:创建时间
	 * @return page:当前页数
	 * @return limit:每页条数
	 * @return sum:总条数
	 * @return max_page:总页数
	 * @return orderby:排序字段
	 * @return sort:排序方向
	 */
	public function listFlowPacket()
	{
		$page = input("get.page", 1, "intval");
		$limit = input("get.limit", 10, "intval");
		$orderby = input("get.orderby", "id");
		$sort = input("get.sort", "asc");
		$search = input("get.search", "");
		$page = $page > 0 ? $page : 1;
		$limit = $limit > 0 ? $limit : 10;
		if (!in_array($orderby, ["id", "capacity", "price", "status", "sale_times"])) {
			$orderby = "id";
		}
		if (!in_array($sort, ["asc", "desc"])) {
			$sort = "asc";
		}
		$count = \think\Db::name("dcim_flow_packet")->whereLike("name", "%{$search}%")->count();
		$max_page = ceil($count / $page);
		$data = \think\Db::name("dcim_flow_packet")->field("id,name,capacity,price,status,sale_times,stock,create_time")->whereLike("name", "%{$search}%")->order($orderby, $sort)->page($page)->limit($limit)->select()->toArray();
		$currency = \think\Db::name("currencies")->where("default", 1)->find();
		foreach ($data as $k => $v) {
			$data[$k]["capacity"] = $v["capacity"] . "GB";
			$data[$k]["price"] = $currency["prefix"] . $v["price"] . $currency["suffix"];
		}
		$result["status"] = 200;
		$result["data"]["page"] = $page;
		$result["data"]["limit"] = $limit;
		$result["data"]["sum"] = $count;
		$result["data"]["max_page"] = $max_page;
		$result["data"]["orderby"] = $orderby;
		$result["data"]["sort"] = $sort;
		$result["data"]["list"] = $data;
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-13
	 * @title 添加流量包页面
	 * @description 添加流量包页面
	 * @url /admin/dcim/flowpacket_page
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @return products:可用产品@
	 * @products  id:产品ID
	 * @products  name:产品名称
	 */
	public function addFlowPacketPage()
	{
		$result["status"] = 200;
		$result["data"]["products"] = getProductList();
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-13
	 * @title 修改流量包页面
	 * @desc 修改流量包页面
	 * @url /admin/dcim/flowpacket_page/:id
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:name type:int require:1 desc:流量包ID
	 * @return  products:可用产品@ 
	 * @products  id:产品ID
	 * @products  name:产品名称
	 * @return  flowpacket.id:流量包ID
	 * @return  flowpacket.name:流量包名称
	 * @return  flowpacket.capacity:流量包容量
	 * @return  flowpacket.price:价格
	 * @return  flowpacket.allow_products:允许产品ID
	 * @return  flowpacket.status:状态(0禁用1启用)
	 * @return  flowpacket.create_time:创建时间
	 * @return  flowpacket.sales_time:销售次数
	 * @return  flowpacket.stock:库存
	 */
	public function editFlowPacketPage($id)
	{
		$data = \think\Db::name("dcim_flow_packet")->field("id,name,capacity,price,allow_products,status,create_time,sale_times,stock")->where("id", $id)->find();
		if (empty($data)) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$data["allow_products"] = explode(",", $data["allow_products"]) ?? [];
		if (!empty($data["allow_products"])) {
			foreach ($data["allow_products"] as $k => $v) {
				$data["allow_products"][$k] = \intval($v);
			}
		}
		$result["status"] = 200;
		$result["products"] = getProductList();
		$result["flowpacket"] = $data;
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-13
	 * @title 添加流量包
	 * @description 添加流量包
	 * @url /admin/dcim/flowpacket
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param  .name:name type:string require:1 desc:名称
	 * @param  .name:capacity type:int require:1 desc:流量包大小(G)最少1
	 * @param  .name:price type:float require:1 desc:价格最少0.01
	 * @param  .name:status type:int require:1 desc:状态(0禁用1启用)
	 * @param  .name:stock type:int require:0 desc:库存(0不限) default:0
	 * @param  .name:allow_products type:array require:0 desc:允许的产品ID
	 */
	public function addFlowPacket()
	{
		$params = input("post.");
		$validate = new \app\admin\validate\DcimFlowPacketValidate();
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		if (!empty($params["allow_products"]) && is_array($params["allow_products"])) {
			$products = \think\Db::name("products")->whereIn("id", $params["allow_products"])->column("id");
			$params["allow_products"] = implode(",", $products);
		} else {
			$params["allow_products"] = "";
		}
		$insert = ["name" => $params["name"], "capacity" => $params["capacity"], "price" => $params["price"], "allow_products" => $params["allow_products"], "status" => $params["status"], "create_time" => time(), "stock" => $params["stock"] ?? 0];
		$id = \think\Db::name("dcim_flow_packet")->insertGetId($insert);
		active_log(sprintf($this->lang["Dcim_admin_addFlowPacket"], $id));
		$result["status"] = 200;
		$result["msg"] = lang("ADD SUCCESS");
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-13
	 * @title 修改流量包
	 * @description 修改流量包
	 * @url /admin/dcim/flowpacket
	 * @method  PUT
	 * @author huanghao
	 * @version v1
	 * @param  .name:id type:int require:1 desc:流量包ID
	 * @param  .name:name type:string require:0 desc:名称
	 * @param  .name:capacity type:int require:0 desc:流量包大小(G)最少1
	 * @param  .name:price type:float require:0 desc:价格最少0.01
	 * @param  .name:status type:int require:0 desc:状态(0禁用1启用)
	 * @param  .name:stock type:int require:0 desc:库存(0不限)
	 * @param  .name:allow_products type:array require:0 desc:允许的产品ID
	 */
	public function editFlowPacket()
	{
		$params = input("post.");
		$id = $params["id"];
		$exist = \think\Db::name("dcim_flow_packet")->where("id", $id)->find();
		if (empty($exist)) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$validate = new \app\admin\validate\DcimFlowPacketValidate();
		$validate_result = $validate->scene("edit")->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$dec = "";
		$update["update_time"] = time();
		if (!empty($params["name"])) {
			$update["name"] = $params["name"];
			if ($params["name"] != $exist["name"]) {
				$dec .= " 流量包名称" . $exist["name"] . "改为" . $params["name"];
			}
		}
		if (!empty($params["capacity"])) {
			$update["capacity"] = $params["capacity"];
			if ($params["capacity"] != $exist["capacity"]) {
				$dec .= " 流量包容量(G)" . $exist["capacity"] . "改为" . $params["capacity"];
			}
		}
		if (!empty($params["price"])) {
			$update["price"] = $params["price"];
			if ($params["price"] != $exist["price"]) {
				$dec .= " 价格" . $exist["price"] . "改为" . $params["price"];
			}
		}
		if (isset($params["status"])) {
			$update["status"] = $params["status"];
			if ($params["status"] == 1) {
				$dec .= " 启用";
			} else {
				$dec .= " 禁用";
			}
		}
		if (isset($params["stock"])) {
			$update["stock"] = $params["stock"] ?? 0;
			if ($params["stock"] != $exist["stock"]) {
				$dec .= " 库存" . $exist["stock"] . "改为" . $params["stock"];
			}
		}
		if (!empty($params["allow_products"]) && is_array($params["allow_products"])) {
			$products = \think\Db::name("products")->whereIn("id", $params["allow_products"])->column("id");
			$update["allow_products"] = implode(",", $products);
		} else {
			if (isset($params["allow_products"])) {
				$update["allow_products"] = "";
			}
		}
		\think\Db::name("dcim_flow_packet")->where("id", $id)->update($update);
		active_log(sprintf($this->lang["Dcim_admin_editFlowPacket"], $id, $dec));
		unset($dec);
		$result["status"] = 200;
		$result["msg"] = lang("UPDATE SUCCESS");
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-13
	 * @title 删除流量包
	 * @description 删除流量包
	 * @url /admin/dcim/flowpacket
	 * @method  DELETE
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:流量包ID
	 */
	public function delFlowPacket()
	{
		$id = input("post.id", 0, "intval");
		$r = \think\Db::name("dcim_flow_packet")->where("id", $id)->delete();
		active_log(sprintf($this->lang["Dcim_admin_delFlowPacket"], $id));
		$result["status"] = 200;
		$result["msg"] = lang("DELETE SUCCESS");
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-14
	 * @title 开机
	 * @description 开机
	 * @url /admin/dcim/on
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:产品ID
	 */
	public function on()
	{
		$id = input("post.id", 0, "intval");
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->on($id);
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-14
	 * @title 关机
	 * @description 关机
	 * @url /admin/dcim/off
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:产品ID
	 */
	public function off()
	{
		$id = input("post.id", 0, "intval");
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->off($id);
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-14
	 * @title 重启
	 * @description 重启
	 * @url /admin/dcim/reboot
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 */
	public function reboot()
	{
		$id = input("post.id", 0, "intval");
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->reboot($id);
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-15
	 * @title 重置BMC
	 * @description 重置BMC
	 * @url /admin/dcim/bmc
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 */
	public function bmc()
	{
		$id = input("post.id", 0, "intval");
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->bmc($id);
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-15
	 * @title 获取kvm
	 * @description 获取kvm
	 * @url /admin/dcim/kvm
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @return  name:下载的文件名
	 */
	public function kvm()
	{
		$id = input("post.id", 0, "intval");
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->kvm($id);
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-15
	 * @title 获取ikvm
	 * @description 获取ikvm
	 * @url /admin/dcim/ikvm
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @return  name:下载的文件名
	 */
	public function ikvm()
	{
		$id = input("post.id", 0, "intval");
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->ikvm($id);
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-18
	 * @title 下载java文件
	 * @description 下载java文件
	 * @url /admin/dcim/download
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:name type:string require:1 desc:要下载的文件名
	 */
	public function download()
	{
		$name = input("get.name");
		header("Access-Control-Expose-Headers: Content-disposition");
		$file = UPLOAD_PATH . "common/default/" . $name . ".jnlp";
		if (file_exists($file)) {
			$length = filesize($file);
			$showname = $name . ".jnlp";
			$expire = 1800;
			header("Pragma: public");
			header("Cache-control: max-age=" . $expire);
			header("Expires: " . gmdate("D, d M Y H:i:s", time() + $expire) . "GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . "GMT");
			header("Content-Disposition: attachment; filename=" . $showname);
			header("Content-Length: " . $length);
			header("Content-type: text/x-java-source");
			header("Content-Encoding: none");
			header("Content-Transfer-Encoding: binary");
			readfile($file);
			sleep(2);
			unlink($file);
		} else {
			return \think\Response::create()->code(404);
		}
	}
	/**
	 * @time 2020-05-18
	 * @title 重装系统
	 * @description 重装系统
	 * @url /admin/dcim/reinstall
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @param   .name:os type:int require:1 desc:操作系统ID
	 * @param   .name:password type:string require:1 desc:密码(六位以上且由大小写字母数字三种组成)
	 * @param   .name:mcon type:int require:0 desc:附加配置ID
	 * @param   .name:action type:int require:1 desc:分区(0默认1附加配置)
	 * @param   .name:port type:int require:1 desc:端口号
	 * @param   .name:part_type type:int require:0 desc:分区类型(windows才有0全盘格式化1第一分区格式化) default:0
	 * @param   .name:disk type:int require:0 desc:磁盘号(从0开始分区为附加配置时不需要) default:0
	 * @param   .name:check_disk_size type:int require:0 desc:是否验证磁盘 default:0
	 * @return   confirm:失败时可能会返回,true弹出确认框取消或者继续安装,继续安装把参数check_disk_size=0和其他原有参数重新发起重装即可
	 */
	public function reinstall()
	{
		$params = input("post.");
		$id = $params["id"];
		$validate = new \app\common\validate\DcimValidate();
		$validate_result = $validate->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$data = ["rootpass" => $params["password"], "action" => $params["action"], "mos" => $params["os"], "mcon" => $params["mcon"], "port" => $params["port"], "disk" => $params["disk"] ?? 0, "check_disk_size" => $params["check_disk_size"] ?? 0, "part_type" => $params["part_type"] ?? 0];
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->reinstall($id, $data);
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-18
	 * @title 获取重装,救援系统,重置密码进度
	 * @description 获取重装,救援系统,重置密码进度
	 * @url /admin/dcim/resintall_status
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @return  disk_check:弹出错误时@
	 * @disk_check  value:disk_part的值
	 * @disk_check  description:描述
	 * @return  error_type:0,1,2,其他(当error_type>0并且progress>=20时弹出磁盘分区错误提示,1Windows磁盘错误,2Windows分区错误,其他Windows磁盘分区提示)
	 * @return  error_msg:当error_type>0时弹出磁盘分区错误提示信息
	 * @return  disk_info:当显示弹出磁盘分区错误提示@
	 * @disk_info  disk:磁盘
	 * @disk_info  part:分区
	 * @disk_info  size:大小
	 * @disk_info  type:类型
	 * @disk_info  windows:类型
	 * @return  progress:进度
	 * @return  windows_finish:是否是windows已完成
	 * @return  hostid:当前产品ID
	 * @return  task_type:类型(0重装系统,1救援系统,2重置密码,3获取硬件信息)
	 * @return  reinstall_msg:重装信息
	 * @return  crackPwd:当有数据返回时,弹出重置密码用户选择@
	 * @crackPwd  user:可选择的用户
	 * @crackPwd  password:重置的密码
	 * @return  step:当前步骤描述
	 * @return  last_result:上次执行结果@
	 * @last_result  act:操作名称
	 * @last_result  status:1成功
	 * @last_result  msg:描述
	 */
	public function getReinstallStatus()
	{
		$id = input("get.id", 0, "intval");
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->reinstallStatus($id);
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-18
	 * @title 救援系统
	 * @description 救援系统
	 * @url /admin/dcim/rescue
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @param   .name:system type:int require:1 desc:操作系统(1Linux2Windows)
	 */
	public function rescue()
	{
		$id = input("post.id", 0, "intval");
		$system = input("post.system", 0, "intval");
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->rescue($id, $system);
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-18
	 * @title 重置密码
	 * @description 重置密码
	 * @url /admin/dcim/crack_pass
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @param   .name:password type:string require:1 desc:密码
	 * @param   .name:other_user type:int require:0 desc:是否重置其他用户(0不是1是) default:0
	 * @param   .name:user type:string require:0 desc:自定义需要重置的用户名(用户名不能包含中文空格@符)
	 * @param   .name:action type:string require:0 desc:获取进度有crackPwd时选择用户后传chooseUser
	 */
	public function crackPass()
	{
		$params = input("post.");
		$id = $params["id"];
		$data = ["crack_password" => $params["password"], "other_user" => intval($params["other_user"]), "user" => $params["user"] ?? "", "action" => $params["action"] ?? ""];
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->crackPass($id, $data);
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-18
	 * @title 获取用量信息
	 * @description 获取用量信息
	 * @url /admin/dcim/traffic_usage
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @param   .name:start type:string require:0 desc:开始日期(YYYY-MM-DD)
	 * @param   .name:end type:string require:0 desc:结束日期(YYYY-MM-DD)
	 * @return  0:流量数据@
	 * @0  time:横坐标值
	 * @0  value:纵坐标值(单位Mbps)
	 */
	public function getTrafficUsage()
	{
		$id = input("get.id");
		$host = \think\Db::name("host")->alias("a")->field("a.regdate")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.id", $id)->where("b.type", "dcim")->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		$end = input("get.end");
		$start = input("get.start");
		$end = strtotime($end) ? date("Y-m-d", strtotime($end)) : date("Y-m-d");
		$start = strtotime($start) ? date("Y-m-d", strtotime($start)) : date("Y-m-d", strtotime("-30 days"));
		if (str_replace("-", "", $start) < str_replace("-", "", date("Y-m-d", $host["regdate"]))) {
			$start = date("Y-m-d", $host["regdate"]);
		}
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->getTrafficUsage($id, $start, $end);
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-18
	 * @title 取消重装,救援,重置密码
	 * @description 取消重装,救援,重置密码
	 * @url /admin/dcim/cancel_task
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 */
	public function cancelReinstall()
	{
		$id = input("post.id");
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->cancelReinstall($id);
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-19
	 * @title 重装解除暂停
	 * @description 重装解除暂停,重装有disk_check时可以调用
	 * @url /admin/dcim/unsuspend_reinstall
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @param   .name:disk_part type:string require:1 desc:重装返回的disk_part
	 */
	public function unsuspendReload()
	{
		$id = input("post.id");
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->unsuspendReload($id, input("post.disk_part"));
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-19
	 * @title 获取流量图信息
	 * @description 获取流量图信息
	 * @url /admin/dcim/trafiic
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @param   .name:switch_id type:int require:1 desc:交换机ID
	 * @param   .name:port_name type:string require:1 desc:端口名称
	 * @param   .name:start_time type:int require:0 desc:开始时间(毫秒时间戳)
	 * @param   .name:end_time type:int require:0 desc:结束时间(毫秒时间戳)
	 * @return  unit:流量单位
	 * @return  traffic:流量数据@
	 * @traffic  time:毫秒时间戳
	 * @traffic  value:值
	 * @traffic  type:类型(in进流量,out出流量)
	 */
	public function traffic()
	{
		$id = input("post.id");
		$params = input("post.");
		if (empty($params["end_time"])) {
			$params["end_time"] = time() . "000";
		}
		if (empty($params["start_time"])) {
			$params["start_time"] = strtotime("-7 days") . "000";
		}
		if ($params["start_time"] > $params["end_time"]) {
			$result["status"] = 400;
			$result["msg"] = "开始时间不能晚于结束时间";
		}
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->traffic($id, $params);
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-21
	 * @title 获取novnc
	 * @description 获取novnc
	 * @url /admin/dcim/novnc
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @return  password:vnc密码
	 * @return  url:vnc地址
	 */
	public function novnc()
	{
		$id = input("post.id", 0, "intval");
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->novnc($id);
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-22
	 * @title novnc页面
	 * @description novnc页面
	 * @url /admin/dcim/novnc
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:password type:string require:1 desc:novnc返回的密码
	 * @param   .name:url type:int require:1 desc:novnc返回的url
	 * @param   .name:host_token type:string require:1 desc:加密的密码
	 */
	public function novncPage()
	{
		$password = input("get.password");
		$url = input("get.url");
		$url = base64_decode(urldecode($url));
		$host_token = input("get.host_token");
		$type = input("get.type");
		$id = input("get.id", 0, "intval");
		$this->assign("url", $url);
		$this->assign("password", $password);
		$this->assign("host_token", !empty($host_token) ? aesPasswordDecode($host_token) : "");
		$this->assign("id", $id);
		if (!empty($host_token)) {
			$this->assign("paste_button", "<div id=\"pastePassword\">粘贴密码</div>");
		} else {
			$this->assign("paste_button", "");
		}
		if ($type == "dcim") {
			$this->assign("restart_vnc", "<div id=\"restart_vnc\">强制刷新vnc</div>");
		} else {
			$this->assign("restart_vnc", "");
		}
		return $this->fetch("./vendor/dcim/novnc.html");
	}
	/**
	 * @time 2020-05-26
	 * @title 获取DCIM产品详情
	 * @description 获取DCIM产品详情
	 * @url /dcim/detail
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @return  switch:交换机数据@
	 * @switch  switch_id:连接的交换机ID
	 * @switch  name:端口名称
	 * @return  password:操作系统密码
	 * @return  username:操作系统名称
	 * @return  os_ostype:当前操作系统ostype
	 * @return  os_osname:当前操作系统真实名称
	 * @return  disk_num:服务器磁盘数量
	 */
	public function detail()
	{
		$id = input("post.id");
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$detail = $dcim->detail($id);
		if (!empty($detail["server"])) {
			$result["status"] = 200;
			$result["data"]["switch"] = [];
			foreach ($detail["switch"] as $v) {
				$result["data"]["switch"][] = ["switch_id" => $v["id"], "name" => $v["switch_num_name"]];
			}
			$result["data"]["password"] = $detail["server"]["ospassword"];
			$result["data"]["username"] = $detail["server"]["osusername"];
			$result["data"]["os_ostype"] = $detail["server"]["os_ostype"];
			$result["data"]["os_osname"] = $detail["server"]["os_osname"];
			$result["data"]["disk_num"] = $detail["server"]["disk_num"];
		} else {
			$result["status"] = 400;
			$result["msg"] = "获取失败";
		}
		return json($result);
	}
	/**
	 * @time 2020-05-27
	 * @title 获取销售服务器
	 * @description 获取销售服务器
	 * @url /admin/dcim/sales
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:host ID
	 * @param   .name:page type:int require:0 desc:页数 default:1
	 * @param   .name:limit type:int require:0 desc:每页条数 default:100
	 * @param   .name:group type:int require:0 desc:分组
	 * @param   .name:status type:int require:0 desc:状态(1空闲3正常) 
	 * @param   .name:search type:string require:0 desc:搜索IP 
	 * @return  list:列表数据@
	 * @list  id:DCIM服务器ID
	 * @list  wltag:标签
	 * @list  typename:型号
	 * @list  group_name:分组
	 * @list  mainip:主IP
	 * @list  ip_num:IP数量
	 * @list  in_bw:进带宽
	 * @list  out_bw:出带宽
	 * @list  remarks:备注
	 * @list  status:状态(1空闲3正常)
	 * @list  email:客户信息
	 * @list  hostid:产品ID
	 * @list  uid:用户ID
	 * @list  self:是否属于该系统
	 * @list  dcim_url:DCIM服务器详情链接地址
	 * @list  token:财务系统唯一标识
	 * @list  type:服务器类型(rent租用trust托管)
	 * @list  cpu:显示的cpu
	 * @list  ram:显示的内存
	 * @list  disk:显示的磁盘
	 * @list  cpu_detail:CPU详情@
	 * @cpu_detail assign:采购信息
	 * @cpu_detail real:实际信息
	 * @list  ram_detail:内存详情@
	 * @ram_detail assign:采购信息
	 * @ram_detail real:实际信息
	 * @list  disk_detail:磁盘详情@
	 * @disk_detail assign:采购信息
	 * @disk_detail real:实际信息
	 * @return  count:总条数
	 * @return  limit:每页条数
	 * @return  page:当前页数
	 * @return  max_page:最大页数
	 * @return  server_group:分组列表@
	 * @server_group  id:分组ID
	 * @server_group  name:分组名称
	 */
	public function getSalesServer()
	{
		$id = input("get.id");
		$page = input("get.page", 1, "intval");
		$limit = input("get.limit", 100, "intval");
		$group = input("get.group", 0, "intval");
		$status = input("get.status", 0);
		$search = input("get.search", "");
		$page = $page > 0 ? $page : 1;
		$limit = $limit > 0 ? $limit : 100;
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$dcim->setUrlByHost($id);
		$result = $dcim->sales($page, $limit, $group, $status, $search);
		return json($result);
	}
	/**
	 * @time 2020-05-27
	 * @title 分配设置
	 * @description 分配设置
	 * @url /admin/dcim/assign
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:hostID
	 * @param   .name:dcimid type:int require:1 desc:Dcim服务器ID
	 */
	public function assignServer()
	{
		$id = input("post.id");
		$dcimid = input("post.dcimid");
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.dcimid,b.api_type,b.zjmf_api_id")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.id", $id)->where("b.type", "dcim")->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return json($result);
		}
		if (!empty($host["dcimid"])) {
			$result["status"] = 400;
			$result["msg"] = "已有服务器ID不用分配";
			return json($result);
		}
		if ($host["api_type"] == "zjmf_api") {
			$result["status"] = 400;
			$result["msg"] = "代理产品不能使用分配";
			return json($result);
		}
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->assignServer($id, $dcimid);
		return json($result);
	}
	/**
	 * @time 2020-05-28
	 * @title 删除设备ID
	 * @description 删除设备ID
	 * @url /admin/dcim/delete
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:hostID
	 */
	public function delete()
	{
		$id = input("post.id", 0, "intval");
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->free($id);
		return json($result);
	}
	/**
	 * @time 2020-05-29
	 * @title 获取电源状态
	 * @description 获取电源状态
	 * @url /admin/dcim/refresh_power_status
	 * @method  POST
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:hostID
	 * @return  power:电源状态(on开机off关机error无法连接not_support不支持电源控制)
	 * @return  msg:状态信息描述
	 */
	public function refreshPowerStatus()
	{
		$id = input("post.id", 0, "intval");
		$dcim = new \app\common\logic\Dcim();
		$dcim->is_admin = true;
		$result = $dcim->refreshPowerStatus($id);
		return json($result);
	}
}