<?php

namespace app\admin\controller;

/**
 * @title 后台对接魔方云管理
 * @description 接口说明
 */
class DcimCloudController extends AdminBaseController
{
	/**
	 * @time 2020-05-13
	 * @title 添加服务器
	 * @description 添加服务器
	 * @url /admin/dcimcloud/server
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
	 * @param  .name:account_type type:string require:0 desc:账号类型(admin=管理员,agent=代理商)
	 */
	public function addServer()
	{
		$params = input("post.");
		$validate = new \app\admin\validate\DcimCloudValidate();
		$validate_result = $validate->scene("server")->check($params);
		if (!$validate_result) {
			return jsonrule(["status" => 406, "msg" => $validate->getError()]);
		}
		$insert = ["name" => $params["name"], "hostname" => $params["hostname"] ?? "", "username" => $params["username"] ?? "", "password" => aesPasswordEncode(html_entity_decode($params["password"], ENT_QUOTES)), "port" => 0, "secure" => $params["secure"] ?? 0, "disabled" => $params["disabled"] ?? 0, "server_type" => "dcimcloud"];
		$accesshash = [];
		if (!empty($params["user_prefix"])) {
			$accesshash[] = "user_prefix:" . $params["user_prefix"];
		}
		if (!empty($params["account_type"])) {
			$accesshash[] = "account_type:" . $params["account_type"];
		} else {
			$accesshash[] = "account_type:admin";
		}
		$insert["accesshash"] = implode(PHP_EOL, $accesshash);
		\think\Db::startTrans();
		try {
			$group = \think\Db::name("server_groups")->insertGetId(["name" => $params["name"], "system_type" => "dcimcloud"]);
			$insert["gid"] = $group;
			$id = \think\Db::name("servers")->insertGetId($insert);
			if (empty($id)) {
				throw new \Exception("error");
			}
			\think\Db::name("dcim_servers")->insert(["serverid" => $id, "auth" => "", "area" => "", "bill_type" => "month", "flow_remind" => ""]);
			\think\Db::commit();
			active_log_final(sprintf($this->lang["Dcim_admin_addServer"], $id));
			$result["status"] = 200;
			$result["msg"] = lang("ADD SUCCESS");
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
	 * @url /admin/dcimcloud/server
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
	 * @param  .name:user_prefix type:string require:0 desc:财务标识
	 * @param  .name:account_type type:string require:0 desc:账号类型(admin=管理员,agent=代理商)
	 */
	public function editServer()
	{
		$params = input("post.");
		$id = $params["id"];
		$server_info = \think\Db::name("servers")->alias("a")->field("a.id,a.gid,a.name,a.hostname,a.username,a.password,a.port,a.secure,a.disabled,a.accesshash,b.reinstall_times,b.buy_times,b.reinstall_price,b.auth,b.bill_type,b.area,b.id dcim_server_id")->leftJoin("dcim_servers b", "a.id=b.serverid")->where("a.server_type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($server_info)) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$validate = new \app\admin\validate\DcimCloudValidate();
		$validate_result = $validate->scene("server")->check($params);
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
		if (isset($params["password"][0])) {
			$update_server["password"] = aesPasswordEncode(html_entity_decode($params["password"], ENT_QUOTES));
			$params["password"] = $update_server["password"];
		}
		if ($params["password"] != $server_info["password"]) {
			$dec .= "密码有修改，";
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
		$update_dcim_server = [];
		if (isset($params["buy_times"])) {
			$update_dcim_server["buy_times"] = $params["buy_times"];
			if ($params["buy_times"] != $server_info["buy_times"]) {
				if ($params["buy_times"] == 0) {
					$dec .= " -   付费重装禁用";
				} else {
					$dec .= " -   付费重装启用";
				}
			}
		} else {
			$update_dcim_server["buy_times"] = \intval($server_info["buy_times"]);
		}
		if ($update_dcim_server["buy_times"] == 0) {
			$params["reinstall_times"] = 0;
			$params["reinstall_price"] = 0;
		}
		if (isset($params["reinstall_times"])) {
			$update_dcim_server["reinstall_times"] = $params["reinstall_times"];
		}
		if ($params["reinstall_times"] != $server_info["reinstall_times"]) {
			$dec .= " - 每周重装次数" . $server_info["reinstall_times"] . "改为" . $params["reinstall_times"];
		}
		if (isset($params["reinstall_price"])) {
			$update_dcim_server["reinstall_price"] = $params["reinstall_price"];
		}
		if ($params["reinstall_price"] != $server_info["reinstall_price"]) {
			$dec .= " - 重装单次价格" . $server_info["reinstall_price"] . "改为" . $params["reinstall_price"];
		}
		$server_info["accesshash"] = explode(PHP_EOL, $server_info["accesshash"]);
		$old_accesshash = [];
		foreach ($server_info["accesshash"] as $v) {
			$v = explode(":", trim($v));
			if (!empty($v[0]) && ($v[0] == "account_type" || $v[0] == "user_prefix")) {
				$old_accesshash[$v[0]] = trim($v[1]);
			}
		}
		$old_accesshash["account_type"] = $old_accesshash["account_type"] ?: "admin";
		$accesshash = [];
		if (isset($params["user_prefix"])) {
			$accesshash[] = "user_prefix:" . $params["user_prefix"];
		} else {
			$accesshash[] = "user_prefix:" . $old_accesshash["user_prefix"];
		}
		if (!empty($params["account_type"])) {
			$accesshash[] = "account_type:" . $params["account_type"];
		} else {
			$accesshash[] = "account_type:" . $old_accesshash["account_type"];
		}
		$account_type = ["admin" => "管理员", "agent" => "代理商"];
		if ($params["user_prefix"] != $old_accesshash["user_prefix"]) {
			$dec .= " - 财务标识" . $old_accesshash["user_prefix"] . "改为" . $params["user_prefix"];
		}
		if ($params["account_type"] != $old_accesshash["account_type"]) {
			$dec .= " - 账号类型" . $account_type[$old_accesshash["account_type"]] . "改为" . $account_type[$params["account_type"]];
		}
		$update_server["accesshash"] = implode(PHP_EOL, $accesshash);
		\think\Db::startTrans();
		try {
			\think\Db::name("server_groups")->where("id", $server_info["gid"])->update(["name" => $params["name"]]);
			\think\Db::name("servers")->where("id", $id)->update($update_server);
			if (!empty($update_dcim_server)) {
				if (empty($server_info["dcim_server_id"])) {
					$update_dcim_server["serverid"] = $id;
					$update_dcim_server["bill_type"] = "month";
					\think\Db::name("dcim_servers")->insert($update_dcim_server);
				} else {
					\think\Db::name("dcim_servers")->where("serverid", $id)->update($update_dcim_server);
				}
			}
			\think\Db::commit();
			if (empty($dec)) {
				$dec .= "没有任何修改";
			}
			active_log_final(sprintf($this->lang["Dcim_admin_editServer"], $id, $dec));
			unset($dec);
			$key = "dcim_cloud_token_" . $id;
			\think\Facade\Cache::rm($key);
			$result["status"] = 200;
			$result["msg"] = lang("UPDATE SUCCESS");
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
	 * @url /admin/dcimcloud/server/:id
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
	 * @return  reinstall_times:重装次数限制
	 * @return  buy_times:超出次数是否可以购买次数
	 * @return  reinstall_price:重装次数价格
	 * @return  area:区域@
	 * @area  id:区域ID
	 * @area  area:区域代码
	 * @area  name:区域名称
	 * @return  user_prefix:财务标识
	 * @return  account_type:账号类型
	 */
	public function serverDetail($id)
	{
		$data = \think\Db::name("servers")->alias("a")->field("a.id,a.name,a.hostname,a.username,a.password,a.port,a.secure,a.disabled,a.accesshash,b.reinstall_times,b.buy_times,b.reinstall_price,b.area")->leftJoin("dcim_servers b", "a.id=b.serverid")->where("a.server_type", "dcimcloud")->where("a.id", $id)->find();
		if (empty($data)) {
			return jsonrule(["status" => "error", "msg" => lang("ID_ERROR")]);
		}
		$accesshash = $data["accesshash"];
		unset($data["accesshash"]);
		if (!empty($accesshash)) {
			$accesshash = explode(PHP_EOL, $accesshash);
			$old_accesshash = [];
			foreach ($accesshash as $v) {
				$v = explode(":", trim($v));
				if (!empty($v[0])) {
					$old_accesshash[$v[0]] = trim($v[1]);
				}
			}
			$data["user_prefix"] = $old_accesshash["user_prefix"] ?? "";
			$data["account_type"] = $old_accesshash["account_type"] ?: "admin";
		} else {
			$data["user_prefix"] = "";
			$data["account_type"] = "admin";
		}
		$data["password"] = aesPasswordDecode($data["password"]);
		$data["area"] = json_decode($data["area"], true) ?: [];
		$result["status"] = 200;
		$result["data"] = $data;
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-14
	 * @title 删除服务器
	 * @description 删除服务器
	 * @url /admin/dcimcloud/server
	 * @method  DELETE
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:服务器ID 
	 */
	public function delServer()
	{
		$id = input("post.id", 0, "intval");
		$server = \think\Db::name("host")->where("serverid", $id)->find();
		$server_group = \think\Db::name("host")->alias("a")->leftJoin("servers b", "a.serverid = b.id")->leftJoin("server_groups c", "c.id = b.gid")->where("c.system_type", "dcimcloud")->where("b.id", $id)->find();
		if (!empty($server) || !empty($server_group)) {
			return jsonrule(["status" => 400, "msg" => lang("SERVER_USING")]);
		} else {
			$info = \think\Db::name("servers")->where("server_type", "dcimcloud")->where("id", $id)->find();
			if (empty($info)) {
				return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$product = \think\Db::name("products")->where("server_group", $info["gid"])->where("api_type", "<>", "zjmf_api")->find();
			if (!empty($product)) {
				return jsonrule(["status" => 400, "msg" => lang("SERVER_USING")]);
			}
			\think\Db::startTrans();
			try {
				\think\Db::name("servers")->where("id", $id)->where("server_type", "dcimcloud")->delete();
				\think\Db::name("server_groups")->where("id", $info["gid"])->where("system_type", "dcimcloud")->delete();
				\think\Db::name("dcim_servers")->where("serverid", $id)->delete();
				\think\Db::commit();
				active_log_final(sprintf($this->lang["Dcim_admin_delServer"], $id), $server_group["uid"]);
				$result["status"] = 200;
				$result["msg"] = lang("DELETE SUCCESS");
				$key = "dcim_cloud_token_" . $id;
				\think\Facade\Cache::delete($key);
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
	 * @url /admin/dcimcloud/server
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
	 * @list  reinstall_times:重装次数限制
	 * @list  buy_times:超出次数是否可以购买次数
	 * @list  reinstall_price:重装次数价格
	 * @list  user_prefix:财务标识
	 * @list  account_type:账号类型(admin=管理员,agent=代理商)
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
		$count = \think\Db::name("servers")->alias("a")->where("a.name LIKE '%{$search}%' OR a.hostname LIKE '%{$search}%'")->where("a.server_type", "dcimcloud")->count();
		$data = \think\Db::name("servers")->alias("a")->field("a.id,a.name,a.hostname,a.username,a.password,a.port,a.secure,a.disabled,a.accesshash,count(DISTINCT b.id) server_num,c.reinstall_times,c.buy_times,c.reinstall_price,c.api_status,d.api_type,count(DISTINCT d.id) product_num")->leftJoin("host b", "b.serverid=a.id AND (b.domainstatus=\"Active\" OR b.domainstatus=\"Suspended\")")->leftJoin("dcim_servers c", "c.serverid=a.id")->leftJoin("products d", "a.gid=d.server_group AND d.api_type not in ('zjmf_api')")->where("a.name LIKE '%{$search}%' OR a.hostname LIKE '%{$search}%'")->where("a.server_type", "dcimcloud")->group("a.id")->order($orderby, $sort)->page($page)->limit($limit)->select()->toArray();
		$max_page = ceil($count / $limit);
		foreach ($data as $k => $v) {
			if ($v["server_num"] == 0 && $v["product_num"] == 0) {
				$data[$k]["removable"] = true;
			} else {
				$data[$k]["removable"] = false;
			}
			if (!empty($v["accesshash"])) {
				$v["accesshash"] = explode(PHP_EOL, $v["accesshash"]);
				$old_accesshash = [];
				foreach ($v["accesshash"] as $vv) {
					$vv = explode(":", trim($vv));
					if (!empty($vv[0])) {
						$old_accesshash[$vv[0]] = trim($vv[1]);
					}
				}
				$data[$k]["user_prefix"] = $old_accesshash["user_prefix"] ?? "";
				$data[$k]["account_type"] = $old_accesshash["account_type"] ?: "admin";
			} else {
				$data[$k]["user_prefix"] = "";
				$data[$k]["account_type"] = "admin";
			}
			$data[$k]["password"] = aesPasswordDecode($v["password"]);
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
	 * @url /admin/dcimcloud/server/:id/status
	 * @method  GET
	 * @author huanghao
	 * @version v1
	 * @param   .name:id type:int require:1 desc:服务器ID 
	 * @return  server_status:服务器状态(1连接测试成功,0失败)
	 */
	public function refreshServerStatus($id)
	{
		$server_info = \think\Db::name("servers")->field("id,hostname,username,password,secure,port")->where("id", $id)->where("server_type", "dcimcloud")->find();
		if (empty($server_info)) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$dcimcloud = new \app\common\logic\DcimCloud($id);
		$dcimcloud->is_admin = true;
		$link_status = $dcimcloud->login(true);
		if (!empty($dcimcloud->curl_error) || !empty($dcimcloud->link_error_msg)) {
			\think\Db::name("dcim_servers")->where("serverid", $id)->update(["api_status" => 0]);
			$result["status"] = 200;
			if (!empty($dcimcloud->curl_error)) {
				$result["msg"] = "连接失败curl错误：" . $dcimcloud->curl_error;
			} else {
				$result["msg"] = $dcimcloud->link_error_msg;
			}
			$result["server_status"] = 0;
		} else {
			\think\Db::name("dcim_servers")->where("serverid", $id)->update(["api_status" => 1]);
			$result["status"] = 200;
			$result["server_status"] = 1;
		}
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-14
	 * @title 刷新所有服务器状态
	 * @description 刷新所有服务器状态
	 * @url /admin/dcimcloud/server/status
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
		$server_info = \think\Db::name("servers")->field("id,hostname,username,password,secure,port")->where("server_type", "dcimcloud")->select()->toArray();
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
			$data[$v["id"]] = ["url" => $url . "/v1/login?a=a", "data" => ["username" => $v["username"], "password" => aesPasswordDecode($v["password"]) ?? ""]];
		}
		$res = batch_curl_post($data, 5);
		$result["data"] = [];
		foreach ($res as $k => $v) {
			$one["id"] = $k;
			if ($v["status"] == 500) {
				$one["status"] = 0;
				$one["msg"] = $v["msg"];
				\think\Db::name("dcim_servers")->where("serverid", $k)->update(["api_status" => 0]);
			} else {
				if (isset($v["data"]["error"])) {
					$one["status"] = 0;
					$one["msg"] = $v["data"]["error"];
					\think\Db::name("dcim_servers")->where("serverid", $k)->update(["api_status" => 0]);
				} else {
					if (!in_array($v["http_code"], [200, 201, 204])) {
						$one["status"] = 0;
						$one["msg"] = "请求失败,HTTP状态码:" . $v["http_code"];
						\think\Db::name("dcim_servers")->where("serverid", $k)->update(["api_status" => 0]);
					} else {
						$one["status"] = 1;
						$one["msg"] = "成功";
						\think\Db::name("dcim_servers")->where("serverid", $k)->update(["api_status" => 1]);
					}
				}
			}
			$result["data"][] = $one;
		}
		$result["status"] = 200;
		return jsonrule($result);
	}
}