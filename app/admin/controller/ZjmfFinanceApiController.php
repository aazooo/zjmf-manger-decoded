<?php

namespace app\admin\controller;

/**
 * @title 魔方财务接口管理
 * @description 接口说明
 */
class ZjmfFinanceApiController extends GetUserController
{
	/**
	 * @time 2020-08-03
	 * @title 添加魔方财务API
	 * @description 添加魔方财务API
	 * @url /admin/zjmf_finance_api
	 * @method POST
	 * @author huanghao
	 * @version v1
	 * @param  .name:name type:string require:1 desc:名称
	 * @param  .name:hostname type:string require:1 desc:地址(IP或者域名)
	 * @param  .name:username type:string require:1 desc:用户名
	 * @param  .name:password type:string require:1 desc:密码
	 * @param  .name:des type:string require:1 desc:备注
	 * @param  .name:type type:string require:1 desc:接口类型：zjmf_api智简魔方，manual手动
	 * @param  .name:contact_way type:string require:1 desc:联系方式
	 * @param  .name: autoreply_isopen type:string require:0 desc:自动回复开关 [on/off]
	 * @param  .name: autoreply_account type:string require:0 desc:自动回复账号
	 * @param  .name: auto_update type:tinyint require:0 desc:前台订购实时更新库存和商品,1开启默认,0关闭
	 */
	public function createApi()
	{
		$params = input("post.");
		$type = ["zjmf_api" => "智简魔方", "manual" => "手动"];
		if ($params["type"] == "manual") {
			if (empty($params["name"])) {
				return jsonrule(["status" => 400, "msg" => "名称不能为空"]);
			}
			$count = \think\Db::name("zjmf_finance_api")->where("name", $params["name"])->count();
			if ($count >= 1) {
				return jsonrule(["status" => 400, "msg" => "名称已存在"]);
			}
			$insert = ["name" => $params["name"], "contact_way" => $params["contact_way"] ?? "", "des" => $params["des"] ?? "", "type" => $params["type"], "product_num" => 0];
			\think\Db::name("zjmf_finance_api")->insertGetId($insert);
		} elseif ($params["type"] == "zjmf_api") {
			$validate = new \app\admin\validate\ZjmfFinanceApiValidate();
			$validate_result = $validate->check($params);
			if (!$validate_result) {
				return jsonrule(["status" => 406, "msg" => $validate->getError()]);
			}
			$insert = ["name" => $params["name"], "hostname" => $params["hostname"] ?? "", "des" => $params["des"] ?? "", "username" => $params["username"] ?? "", "password" => aesPasswordEncode(html_entity_decode($params["password"], ENT_QUOTES)), "create_time" => time(), "contact_way" => $params["contact_way"] ?? "", "auto_update" => 1];
			$id = \think\Db::name("zjmf_finance_api")->insertGetId($insert);
			$res = zjmfCurl($id, "/cart/all", [], 15, "GET");
			if ($res["status"] == 200) {
				\think\Db::name("zjmf_finance_api")->where("id", $id)->update(["product_num" => $res["data"]["count"]]);
			}
			$data = ["id" => $id];
		} elseif ($params["type"] == "whmcs") {
			$validate = new \app\admin\validate\ZjmfFinanceApiValidate();
			$validate_result = $validate->check($params);
			if (!$validate_result) {
				return jsonrule(["status" => 406, "msg" => $validate->getError()]);
			}
			$insert = ["name" => $params["name"], "hostname" => $params["hostname"] ?? "", "des" => $params["des"] ?? "", "username" => $params["username"] ?? "", "password" => aesPasswordEncode(html_entity_decode($params["password"], ENT_QUOTES)), "create_time" => time(), "contact_way" => $params["contact_way"] ?? "", "auto_update" => 1, "type" => $params["type"]];
			\think\Db::name("zjmf_finance_api")->insertGetId($insert);
		}
		$description = sprintf("添加魔方财务API成功,名称:%s,接口地址:%s", $params["name"], $params["hostname"] ?: "");
		active_log($description);
		$result["status"] = 200;
		$result["msg"] = lang("ADD SUCCESS");
		$result["data"] = $data ?: [];
		return jsonrule($result);
	}
	/**
	 * @time 2020-05-13
	 * @title 修改魔方财务API
	 * @description 修改魔方财务API
	 * @url /admin/zjmf_finance_api
	 * @method PUT
	 * @author huanghao
	 * @version v1
	 * @param  .name:id type:int require:1 desc:id
	 * @param  .name:name type:string require:1 desc:名称
	 * @param  .name:hostname type:string require:1 desc:接口地址(IP或者域名)
	 * @param  .name:username type:string require:1 desc:用户名
	 * @param  .name:password type:string require:1 desc:密码
	 * @param  .name:type type:string require:1 desc:接口类型：zjmf_api智简魔方，manual手动
	 * @param  .name:contact_way type:string require:0 desc:联系方式
	 * @param  .name: autoreply_isopen type:string require:0 desc:自动回复开关 [on/off]
	 * @param  .name: autoreply_account type:string require:0 desc:自动回复账号
	 * @param  .name: auto_update type:tinyint require:0 desc:前台订购实时更新库存和商品,1开启默认,0关闭
	 */
	public function modifyApi()
	{
		$params = input("post.");
		$id = \intval($params["id"]);
		$api = \think\Db::name("zjmf_finance_api")->where("id", $id)->find();
		if (empty($api)) {
			return jsonrule(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		if ($params["type"] == "manual") {
			if (empty($params["name"])) {
				return jsonrule(["status" => 400, "msg" => "名称不能为空"]);
			}
			$count = \think\Db::name("zjmf_finance_api")->where("id", "<>", $id)->where("name", $params["name"])->count();
			if ($count >= 1) {
				return jsonrule(["status" => 400, "msg" => "名称已存在"]);
			}
			\think\Db::name("zjmf_finance_api")->where("id", $id)->update(["name" => $params["name"], "contact_way" => $params["contact_way"], "des" => $params["des"], "type" => $params["type"], "hostname" => "", "username" => "", "password" => "", "upstream_uid" => "", "product_num" => 0]);
		} else {
			$validate = new \app\admin\validate\ZjmfFinanceApiValidate();
			$validate_result = $validate->check($params);
			if (!$validate_result) {
				return jsonrule(["status" => 406, "msg" => $validate->getError()]);
			}
			$api["password"] = aesPasswordDecode($api["password"]);
			$update = [];
			$description = "修改魔方财务API成功";
			if ($params["name"] != $api["name"]) {
				$exist = \think\Db::name("zjmf_finance_api")->where("id", "<>", $id)->where("is_resource", 0)->where("name", $params["name"])->find();
				if (!empty($exist)) {
					return jsonrule(["status" => 400, "msg" => "名称已存在"]);
				}
				$description .= sprintf(" - 名称由%s修改为%s", $api["name"], $params["name"]);
				$update["name"] = $params["name"];
			}
			if ($params["hostname"] != $api["hostname"]) {
				$exist = \think\Db::name("zjmf_finance_api")->where("id", "<>", $id)->where("is_resource", 0)->where("hostname", $params["hostname"])->find();
				if (!empty($exist)) {
					return jsonrule(["status" => 400, "msg" => "接口地址已存在"]);
				}
				$description .= sprintf(" - 接口地址由%s修改为%s", $api["hostname"], $params["hostname"]);
				$update["hostname"] = $params["hostname"];
			}
			if ($params["username"] != $api["username"]) {
				$description .= sprintf(" - 用户名由%s修改为%s", $api["username"], $params["username"]);
				$update["username"] = $params["username"];
			}
			if ($params["des"] != $api["des"]) {
				$description .= sprintf(" -描述由%s修改为%s", $api["des"], $params["des"]);
				$update["des"] = $params["des"];
			}
			if ($params["password"] != $api["password"]) {
				$description .= " - 密码变更";
				$update["password"] = aesPasswordEncode(html_entity_decode($params["password"], ENT_QUOTES));
			}
			$update["auto_update"] = 1;
			$update["contact_way"] = $params["contact_way"] ?? "";
			$update["type"] = $params["type"] ?? "zjmf_api";
			if (!empty($update)) {
				\think\Db::name("zjmf_finance_api")->where("id", $id)->update($update);
				active_log($description);
				$key = "zjmf_finance_jwt_" . $id;
				\think\facade\Cache::rm($key);
				$res = zjmfCurl($id, "/cart/all", [], 15, "GET");
				if ($res["status"] == 200) {
					\think\Db::name("zjmf_finance_api")->where("id", $id)->update(["product_num" => $res["data"]["count"]]);
				}
			}
		}
		$result["status"] = 200;
		$result["msg"] = lang("UPDATE SUCCESS");
		return jsonrule($result);
	}
	/**
	 * 时间 2020-08-03
	 * @title 魔方财务API详情
	 * @desc 魔方财务API详情
	 * @url /admin/zjmf_finance_api/:id
	 * @method GET
	 * @author hh
	 * @version v1
	 * @return  name:名称
	 * @return  hostname:接口地址
	 * @return  username:用户名
	 * @return  password:密码
	 * @return  status:连接状态
	 * @return  product_num:可售商品总数
	 * @return  create_time:创建时间戳
	 */
	public function detail($id)
	{
		$data = \think\Db::name("zjmf_finance_api")->where("id", $id)->find();
		if (empty($data)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		$data["password"] = aesPasswordDecode($data["password"]);
		$result["status"] = 200;
		$result["data"] = $data;
		return jsonrule($result);
	}
	/**
	 * 时间 2020-08-03
	 * @title 删除魔方财务API
	 * @desc 删除魔方财务API
	 * @url /admin/zjmf_finance_api/:id
	 * @method DELETE
	 * @author hh
	 * @version v1
	 */
	public function deleteApi($id)
	{
		$api = \think\Db::name("zjmf_finance_api")->where("id", $id)->find();
		if (empty($api)) {
			$result["status"] = 406;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		$has_product = \think\Db::name("products")->where("zjmf_api_id", $id)->find();
		if (!empty($has_product)) {
			$result["status"] = 400;
			$result["msg"] = "有商品使用该接口,不能删除";
			return jsonrule($result);
		}
		$exist = \think\Db::name("upper_reaches_res")->where("pid", $id)->find();
		if (!empty($exist)) {
			return jsonrule(["status" => 400, "msg" => "请先删除供应商下的服务器"]);
		}
		\think\Db::name("zjmf_finance_api")->where("id", $id)->delete();
		$description = "删除魔方财务API成功,接口地址:" . $api["hostname"];
		active_log($description);
		$result["status"] = 200;
		$result["msg"] = lang("DELETE SUCCESS");
		return jsonrule($result);
	}
	/**
	 * 时间 2020-08-03
	 * @title 魔方财务API列表
	 * @desc 魔方财务API列表
	 * @url /admin/zjmf_finance_api
	 * @method GET
	 * @author hh
	 * @version v1
	 * @param   .name:page type:int require:0 desc:页数 default:1
	 * @param   .name:limit type:int require:0 desc:每页条数 default:10
	 * @param   .name:orderby type:string require:0 desc:排序(id,name,hostname,server_num,api_status) default:id
	 * @param   .name:sort type:string require:0 desc:排序方向(asc,desc) default:asc
	 * @param   .name:search type:string require:0 desc:搜索
	 * @return  list:列表数据@
	 * @list  id:ID
	 * @list  name:名称
	 * @list  hostname:接口地址
	 * @list  username:用户名
	 * @list  password:密码
	 * @list  status:连接状态(0异常1正常)
	 * @list  product_num:可售商品数量
	 * @list  set_product_num:设置商品数量
	 * @list  active_host_num:正常产品数量
	 * @list  host_num:总产品数量
	 * @list  type_zh:接口类型：zjmf_api智简魔方，manual手动
	 * @list  contact_way:联系方式
	 * @list  autoreply_isopen:自动回复开关
	 * @list  autoreply_account:自动回复账号
	 */
	public function index()
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
		$where = function (\think\db\Query $query) {
			$query->where("a.is_resource", 0);
		};
		$count = \think\Db::name("zjmf_finance_api")->alias("a")->whereLike("a.hostname|a.username", "%{$search}%")->where($where)->count();
		$data = \think\Db::name("zjmf_finance_api")->alias("a")->field("a.id,a.type,a.type as type_zh,a.contact_way,a.name,a.des,a.hostname,a.username,a.password,a.status,a.product_num,count(DISTINCT b.id) set_product_num,count(DISTINCT c.id) active_host_num,count(DISTINCT d.id) host_num")->leftJoin("products b", "a.id=b.zjmf_api_id OR a.id=b.upper_reaches_id")->leftJoin("host c", "b.id=c.productid AND domainstatus=\"Active\"")->leftJoin("host d", "b.id=d.productid")->whereLike("a.hostname|a.username", "%{$search}%")->where($where)->withAttr("type_zh", function ($value) {
			$type = ["zjmf_api" => "智简魔方", "manual" => "手动", "whmcs" => "WHMCS"];
			return $type[$value];
		})->group("a.id")->order($orderby, $sort)->page($page)->limit($limit)->select()->toArray();
		$max_page = ceil($count / $limit);
		foreach ($data as $k => $v) {
			$data[$k]["password"] = aesPasswordDecode($v["password"]) ?: "";
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
	 * 时间 2020-08-03
	 * @title 刷新魔方财务API状态
	 * @desc 刷新魔方财务API状态
	 * @url /admin/zjmf_finance_api/:id/status
	 * @method GET
	 * @author hh
	 * @version v1
	 */
	public function refreshStatus($id)
	{
		session_write_close();
		$data = \think\Db::name("zjmf_finance_api")->where("id", $id)->find();
		if (empty($data)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return jsonrule($result);
		}
		$url = rtrim($data["hostname"], "/");
		if ($data["is_resource"] == 1) {
			$url = $url . "/resource_login";
			$post_data = ["username" => $data["username"], "password" => aesPasswordDecode($data["password"]), "type" => "agent"];
		} else {
			if ($data["type"] == "whmcs") {
				$url = $url . "/modules/addons/idcsmart_api/api.php?action=/v1/check";
				$post_data = ["apiname" => $data["username"], "apikey" => aesPasswordDecode($data["password"])];
			} else {
				$url = $url . "/zjmf_api_login";
				$post_data = ["username" => $data["username"], "password" => aesPasswordDecode($data["password"])];
			}
		}
		$res = zjmfApiLogin($id, $url, $post_data, true);
		if ($res["status"] == 200) {
			\think\Db::name("zjmf_finance_api")->where("id", $id)->update(["status" => 1]);
			$result["status"] = 200;
			$result["data"]["status"] = 1;
			$result["data"]["desc"] = "连接成功";
			$res = zjmfCurl($id, "/cart/all", [], 15, "GET");
			if ($res["status"] == 200) {
				\think\Db::name("zjmf_finance_api")->where("id", $id)->update(["product_num" => $res["data"]["count"]]);
			}
		} else {
			\think\Db::name("zjmf_finance_api")->where("id", $id)->update(["status" => 0]);
			$result["status"] = 200;
			$result["data"]["status"] = 0;
			$result["data"]["desc"] = $res["msg"];
		}
		return jsonrule($result);
	}
	/**
	 * 时间 2021-05-27
	 * @title API概览
	 * @desc API概览,上游管理下游
	 * @url /admin/zjmf_finance_api/summary
	 * @param  .name:uid type:int require:1 desc:客户ID
	 * @method GET
	 * @author wyh
	 * @return  client:基础数据@
	 * @client api_password:api密钥
	 * @client api_create_time:开通时间
	 * @client agent_count:代理商品数量
	 * @client host_count:API产品数量 总量
	 * @client active_count:API产品数量 已激活
	 * @client api_count:昨日api请求次数
	 * @client ratio:日环比
	 * @client up:1上升，0下降
	 * @client lock_reason:锁定原因
	 * @client api_lock_time:锁定时间
	 * @return form_api:最近7天每天的api请求次数
	 * @return free_products:豁免产品@
	 * @free_products id:
	 * @free_products name:名称
	 * @free_products ontrial:试用数量
	 * @free_products qty:最大购买数量
	 */
	public function summary()
	{
		$uid = request()->uid;
		$client = \think\Db::name("clients")->field("api_password,api_create_time,api_open,lock_reason,api_lock_time")->where("id", $uid)->find();
		if (configuration("allow_resource_api") == 0 || $client["api_open"] == 0) {
			return jsons(["status" => 400, "msg" => "暂未开通API功能"]);
		}
		$client["api_password"] = aesPasswordDecode($client["api_password"]);
		$agent_pids = \think\Db::name("api_resource_log")->where("uid", $uid)->where("pid", "<>", 0)->field("pid")->distinct(true)->column("pid");
		$host_count = \think\Db::name("host")->whereIn("productid", $agent_pids)->where("uid", $uid)->where("stream_info", "like", "%downstream_url%")->count();
		$active_count = \think\Db::name("host")->where("domainstatus", "Active")->whereIn("productid", $agent_pids)->where("uid", $uid)->where("stream_info", "like", "%downstream_url%")->count();
		$client["agent_count"] = count($agent_pids);
		$client["host_count"] = $host_count;
		$client["active_count"] = $active_count;
		$yesterday_start = strtotime(date("Y-m-d", time()));
		$yesterday_end = $yesterday_start + 86400;
		$api_count = \think\Db::name("api_resource_log")->where("uid", $uid)->whereBetweenTime("create_time", $yesterday_start, $yesterday_end)->count();
		$client["api_count"] = $api_count;
		$before_yesterday_start = strtotime(date("Y-m-d", strtotime("-1 days")));
		$before_yesterday_end = $before_yesterday_start + 86400;
		$api_count2 = \think\Db::name("api_resource_log")->where("uid", $uid)->whereBetweenTime("create_time", $before_yesterday_start, $before_yesterday_end)->count();
		$ratio1 = bcdiv($api_count, $api_count2, 2) * 100;
		$client["ratio"] = $ratio1 . "%";
		$before_yesterday_start2 = strtotime(date("Y-m-d", strtotime("-2 days")));
		$before_yesterday_end2 = $before_yesterday_start2 + 86400;
		$api_count3 = \think\Db::name("api_resource_log")->where("uid", $uid)->whereBetweenTime("create_time", $before_yesterday_start2, $before_yesterday_end2)->count();
		$ratio2 = bcdiv($api_count3, $api_count2, 2) * 100;
		$client["up"] = 0;
		if ($ratio2 <= $ratio1) {
			$client["up"] = 1;
		}
		$form_api = $this->getEveryDayTotal(strtotime(date("Y-m-d", strtotime("-6 days"))));
		$free_products = \think\Db::name("api_user_product")->field("a.id,b.name,a.ontrial,a.qty")->alias("a")->leftJoin("products b", "a.pid = b.id")->where("uid", $uid)->select()->toArray();
		$data = ["client" => $client, "form_api" => $form_api, "free_products" => $free_products];
		$result = ["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data];
		return jsonrule($result);
	}
	/**
	 * 时间 2021-05-27
	 * @title 开启/关闭API功能
	 * @desc 开启/关闭API功能
	 * @url admin/zjmf_finance_api/open
	 * @param  .name:uid type:int require:1 desc:客户ID
	 * @param  .name:api_open type:int require:1 desc:1开启 0关闭
	 * @method POST
	 * @author wyh
	 */
	public function apiOpen()
	{
		if (!configuration("allow_resource_api")) {
			return jsons(["status" => 400, "msg" => "暂未开启API功能"]);
		}
		$param = $this->request->param();
		$uid = request()->uid;
		$up = ["api_open" => intval($param["api_open"])];
		if ($param["api_open"] == 1) {
			$up["api_create_time"] = time();
		}
		\think\Db::name("clients")->where("id", $uid)->update($up);
		if ($param["api_open"] == 1) {
			return jsonrule(["status" => 200, "msg" => "开启成功"]);
		} else {
			return jsonrule(["status" => 200, "msg" => "关闭成功"]);
		}
	}
	private function getTotal($start, $end)
	{
		$total = \think\Db::name("api_resource_log")->where("uid", request()->uid)->whereBetweenTime("create_time", $start, $end)->count();
		return intval($total);
	}
	private function getEveryDayTotal($month_start)
	{
		$days = 7;
		$month_every_day_total = [];
		for ($i = 0; $i <= $days - 1; $i++) {
			${$i + 1 . "_start"} = strtotime("+" . $i . " days", $month_start);
			${$i + 1 . "_end"} = strtotime("+" . ($i + 1) . " days -1 seconds", $month_start);
			${$i + 1 . "_total"} = $this->getTotal(${$i + 1 . "_start"}, ${$i + 1 . "_end"});
			array_push($month_every_day_total, ${$i + 1 . "_total"});
		}
		return $month_every_day_total;
	}
	/**
	 * 时间 2021-05-27
	 * @title 重置秘钥
	 * @desc 重置秘钥
	 * @url /admin/zjmf_finance_api/reset
	 * @param  .name:uid type:int require:1 desc:客户ID
	 * @method POST
	 * @author wyh
	 */
	public function resetApiPwd()
	{
		$param = $this->request->param();
		$uid = intval($param["uid"]);
		\think\Db::name("clients")->where("id", $uid)->update(["api_password" => aesPasswordEncode(randStrToPass(12, 0))]);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * 时间 2021-05-27
	 * @title api锁定
	 * @desc api锁定
	 * @url /admin/zjmf_finance_api/toggle
	 * @param  .name:uid type:int require:1 desc:客户ID
	 * @param  .name:api_open type:int require:1 desc:1开启,0关闭,2锁定
	 * @param  .name:lock_reason type:int require:1 desc:1开启,0关闭,2锁定
	 * @method POST
	 * @author wyh
	 */
	public function apiToggle()
	{
		$param = $this->request->param();
		$uid = intval($param["uid"]);
		$api_open = \think\Db::name("clients")->where("id", $uid)->value("api_open");
		if ($api_open == $param["api_open"]) {
			return jsonrule(["status" => 400, "msg" => "不可重复操作"]);
		}
		$up["api_open"] = intval($param["api_open"]);
		if ($param["api_open"] == 2) {
			$up["lock_reason"] = trim($param["lock_reason"]);
			$up["api_lock_time"] = time();
		}
		\think\Db::name("clients")->where("id", $uid)->update($up);
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * 时间 2021-05-27
	 * @title 新增,编辑豁免产品页面
	 * @desc 新增,编辑豁免产品页面
	 * @url /admin/zjmf_finance_api/freepage
	 * @param  .name:id type:int require:0 desc:豁免产品ID(编辑时需要此参数)
	 * @method GET
	 * @author wyh
	 * @return groups:产品组@
	 * @groups products:产品
	 * @return free_product:豁免产品信息@
	 * @free_product id:
	 * @free_product ontrial:试用数量
	 * @free_product qty:最大购买数量
	 */
	public function apiFreePage()
	{
		$param = $this->request->param();
		$id = intval($param["id"]);
		$freeProduct = \think\Db::name("api_user_product")->where("id", $id)->find();
		$groups = \think\Db::name("product_groups")->field("id,name")->where("hidden", 0)->select()->toArray();
		foreach ($groups as &$group) {
			$products = \think\Db::name("products")->field("id,type,name,description")->where("gid", $group["id"])->where("hidden", 0)->where("retired", 0)->select()->toArray();
			$group["products"] = $products;
		}
		$data = ["free_product" => $freeProduct ?: [], "groups" => $groups];
		$result = ["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data];
		return jsonrule($result);
	}
	/**
	 * 时间 2021-05-27
	 * @title 新增,编辑豁免产品
	 * @desc 新增,编辑豁免产品
	 * @url /admin/zjmf_finance_api/freepage
	 * @param  .name:uid type:int require:1 desc:客户ID
	 * @param  .name:id type:int require:0 desc:豁免产品ID(编辑时需要此参数)
	 * @param  .name:pids[] type:array require:0 desc:产品ID (编辑时不能更改，不传此值) 传数组(20210709改)
	 * @param  .name:ontrial type:int require:0 desc:试用数量
	 * @param  .name:qty type:int require:0 desc:最大购买数量
	 * @method POST
	 * @author wyh
	 */
	public function apiFreePost()
	{
		$param = $this->request->param();
		if (!\is_profession()) {
			return jsonrule(["status" => 400, "msg" => "免费版该功能不可用"]);
		}
		if ($param["id"]) {
			$up = ["ontrial" => intval($param["ontrial"]), "qty" => intval($param["qty"])];
			\think\Db::name("api_user_product")->where("id", $param["id"])->update($up);
		} else {
			if (!is_array($param["pids"])) {
				$pids = [$param["pids"]];
			} else {
				$pids = $param["pids"];
			}
			$insertAll = [];
			foreach ($pids as $pid) {
				$count = \think\Db::name("products")->where("id", $pid)->count();
				if ($count < 1) {
					return jsonrule(["status" => 400, "msg" => "产品不存在"]);
				}
				$insert = ["ontrial" => intval($param["ontrial"]), "qty" => intval($param["qty"]), "uid" => intval($param["uid"]), "pid" => $pid];
				$insertAll[] = $insert;
			}
			\think\Db::name("api_user_product")->insertAll($insertAll);
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * 时间 2021-05-27
	 * @title 删除豁免产品
	 * @desc 删除豁免产品
	 * @url /admin/zjmf_finance_api/freepage
	 * @param  .name:id type:int require:0 desc:豁免产品ID(编辑时需要此参数)
	 * @method DELETE
	 * @author wyh
	 */
	public function apiFreeDelete()
	{
		$param = $this->request->param();
		if (!\is_profession()) {
			return jsonrule(["status" => 400, "msg" => "免费版该功能不可用"]);
		}
		$id = intval($param["id"]);
		\think\Db::name("api_user_product")->where("id", $id)->delete();
		return jsonrule(["status" => 200, "msg" => lang("DELETE MESSAGE")]);
	}
	/**
	 * 时间 2021-05-27
	 * @title 商品列表
	 * @desc 商品列表
	 * @url /admin/zjmf_finance_api/products
	 * @param  .name:id type:int require:0 desc:API id
	 * @param  .name:keyword type:mixed require:0 desc:关键字搜索
	 * @method GET
	 * @author wyh
	 * @return products:列表@
	 * @products id:
	 * @products name：产品名称
	 * @products gname:分类名称
	 * @products qty:本地 库存
	 * @products upstream_qty:上游 库存
	 * @products host_count:数量 总
	 * @products host_active:激活
	 * @products type_zh：类型
	 * @products billingcycle_zh:周期
	 * @products product_price：价格
	 * @products product_shopping_url：链接 本地
	 * @products upstream_product_shopping_url：链接 上游
	 * @return  product_count:产品 总数
	 * @return  local_qty:本地 库存 总
	 * @return  upstream_qty:上游 库存 总
	 * @return  host_total:数量 总
	 * @return  host_active:激活 总
	 */
	public function apiProducts()
	{
		$param = $this->request->param();
		$where = function (\think\db\Query $query) use($param) {
			if (!empty($param["keyword"])) {
				$query->where("b.name|c.name", "like", "%{$param["keyword"]}%");
			}
			if (!empty($param["id"])) {
				$query->where("a.zjmf_api_id|b.zjfm_api_id|c.zjmf_api_id|c.upper_reaches_id", $param["id"]);
			} else {
				$query->where(function (\think\db\Query $query) use($param) {
					$query->where("a.is_upstream", 1)->whereOr("b.is_upstream", 1)->whereOr("c.zjmf_api_id|c.upper_reaches_id", ">", 0);
				});
			}
		};
		$products = \think\Db::name("product_first_groups")->field("d.id as zjmf_api_id,c.id,c.name,b.name as gname,b.id as gid,a.id as fgid,a.name as fgname,c.qty,c.upstream_qty,c.product_shopping_url,c.upstream_product_shopping_url,c.type,c.pay_type,c.api_type,c.upstream_version,c.upstream_price_type,c.upstream_price_value")->alias("a")->leftJoin("product_groups b", "a.id=b.gid")->leftJoin("products c", "b.id=c.gid")->leftJoin("zjmf_finance_api d", "c.zjmf_api_id=d.id or c.upper_reaches_id=d.id")->where("d.id", ">", 0)->where($where)->select()->toArray();
		$currencyid = getDefaultCurrencyId();
		$prefix = \think\Db::name("currencies")->where("id", $currencyid)->value("prefix");
		$product_count = count($products);
		$local_qty = $upstream_qty = $host_total = $host_active = 0;
		foreach ($products as &$v) {
			array_map(function ($value) {
				return is_string($value) ? htmlspecialchars_decode($value, ENT_QUOTES) : $value;
			}, $v);
			$v["type_zh"] = config("product_type")[$v["type"]];
			$paytype = (array) json_decode($v["pay_type"]);
			$pricing = \think\Db::name("pricing")->where("type", "product")->where("relid", $v["id"])->where("currency", $currencyid)->find();
			if (!empty($paytype["pay_ontrial_status"])) {
				if ($pricing["ontrial"] >= 0) {
					$v["product_price"] = $pricing["ontrial"];
					$v["setup_fee"] = $pricing["ontrialfee"];
					$v["billingcycle"] = "ontrial";
					$v["billingcycle_zh"] = lang("ONTRIAL");
				} else {
					$v["product_price"] = 0;
					$v["setup_fee"] = 0;
					$v["billingcycle"] = "";
					$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
				}
			}
			if ($paytype["pay_type"] == "free") {
				$v["product_price"] = 0;
				$v["setup_fee"] = 0;
				$v["billingcycle"] = "free";
				$v["billingcycle_zh"] = lang("FREE");
			} elseif ($paytype["pay_type"] == "onetime") {
				if ($pricing["onetime"] >= 0) {
					$v["product_price"] = $pricing["onetime"];
					$v["setup_fee"] = $pricing["osetupfee"];
					$v["billingcycle"] = "onetime";
					$v["billingcycle_zh"] = lang("ONETIME");
				} else {
					$v["product_price"] = 0;
					$v["setup_fee"] = 0;
					$v["billingcycle"] = "";
					$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
				}
			} else {
				if (!empty($pricing) && $paytype["pay_type"] == "recurring") {
					if ($pricing["hour"] >= 0) {
						$v["product_price"] = $pricing["hour"];
						$v["setup_fee"] = $pricing["hsetupfee"];
						$v["billingcycle"] = "hour";
						$v["billingcycle_zh"] = lang("HOUR");
					} elseif ($pricing["day"] >= 0) {
						$v["product_price"] = $pricing["day"];
						$v["setup_fee"] = $pricing["dsetupfee"];
						$v["billingcycle"] = "day";
						$v["billingcycle_zh"] = lang("DAY");
					} elseif ($pricing["monthly"] >= 0) {
						$v["product_price"] = $pricing["monthly"];
						$v["setup_fee"] = $pricing["msetupfee"];
						$v["billingcycle"] = "monthly";
						$v["billingcycle_zh"] = lang("MONTHLY");
					} elseif ($pricing["quarterly"] >= 0) {
						$v["product_price"] = $pricing["quarterly"];
						$v["setup_fee"] = $pricing["qsetupfee"];
						$v["billingcycle"] = "quarterly";
						$v["billingcycle_zh"] = lang("QUARTERLY");
					} elseif ($pricing["semiannually"] >= 0) {
						$v["product_price"] = $pricing["semiannually"];
						$v["setup_fee"] = $pricing["ssetupfee"];
						$v["billingcycle"] = "semiannually";
						$v["billingcycle_zh"] = lang("SEMIANNUALLY");
					} elseif ($pricing["annually"] >= 0) {
						$v["product_price"] = $pricing["annually"];
						$v["setup_fee"] = $pricing["asetupfee"];
						$v["billingcycle"] = "annually";
						$v["billingcycle_zh"] = lang("ANNUALLY");
					} elseif ($pricing["biennially"] >= 0) {
						$v["product_price"] = $pricing["biennially"];
						$v["setup_fee"] = $pricing["bsetupfee"];
						$v["billingcycle"] = "biennially";
						$v["billingcycle_zh"] = lang("BIENNIALLY");
					} elseif ($pricing["triennially"] >= 0) {
						$v["product_price"] = $pricing["triennially"];
						$v["setup_fee"] = $pricing["tsetupfee"];
						$v["billingcycle"] = "triennially";
						$v["billingcycle_zh"] = lang("TRIENNIALLY");
					} elseif ($pricing["fourly"] >= 0) {
						$v["product_price"] = $pricing["fourly"];
						$v["setup_fee"] = $pricing["foursetupfee"];
						$v["billingcycle"] = "fourly";
						$v["billingcycle_zh"] = lang("FOURLY");
					} elseif ($pricing["fively"] >= 0) {
						$v["product_price"] = $pricing["fively"];
						$v["setup_fee"] = $pricing["fivesetupfee"];
						$v["billingcycle"] = "fively";
						$v["billingcycle_zh"] = lang("FIVELY");
					} elseif ($pricing["sixly"] >= 0) {
						$v["product_price"] = $pricing["sixly"];
						$v["setup_fee"] = $pricing["sixsetupfee"];
						$v["billingcycle"] = "sixly";
						$v["billingcycle_zh"] = lang("SIXLY");
					} elseif ($pricing["sevenly"] >= 0) {
						$v["product_price"] = $pricing["sevenly"];
						$v["setup_fee"] = $pricing["sevensetupfee"];
						$v["billingcycle"] = "sevenly";
						$v["billingcycle_zh"] = lang("SEVENLY");
					} elseif ($pricing["eightly"] >= 0) {
						$v["product_price"] = $pricing["eightly"];
						$v["setup_fee"] = $pricing["eightsetupfee"];
						$v["billingcycle"] = "eightly";
						$v["billingcycle_zh"] = lang("EIGHTLY");
					} elseif ($pricing["ninely"] >= 0) {
						$v["product_price"] = $pricing["ninely"];
						$v["setup_fee"] = $pricing["ninesetupfee"];
						$v["billingcycle"] = "ninely";
						$v["billingcycle_zh"] = lang("NINELY");
					} elseif ($pricing["tenly"] >= 0) {
						$v["product_price"] = $pricing["tenly"];
						$v["setup_fee"] = $pricing["tensetupfee"];
						$v["billingcycle"] = "tenly";
						$v["billingcycle_zh"] = lang("TENLY");
					} else {
						$v["product_price"] = 0;
						$v["setup_fee"] = 0;
						$v["billingcycle"] = "";
						$v["billingcycle_zh"] = lang("PRICE_CONFIG_ERROR");
					}
				} else {
					$v["product_price"] = 0;
					$v["setup_fee"] = 0;
					$v["billingcycle"] = "";
					$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
				}
			}
			if ($paytype["pay_type"] == "recurring" && in_array($v["type"], array_keys(config("developer_app_product_type")))) {
				if ($pricing["annually"] > 0) {
					$v["product_price"] = $pricing["annually"];
					$v["setup_fee"] = $pricing["asetupfee"];
					$v["billingcycle"] = "annually";
					$v["billingcycle_zh"] = lang("ANNUALLY");
				}
			}
			$v["product_price"] = bcadd($v["setup_fee"], $v["product_price"], 2);
			$cart_logic = new \app\common\logic\Cart();
			$rebate_total = 0;
			$config_total = $cart_logic->getProductDefaultConfigPrice($v["id"], $currencyid, $v["billingcycle"], $rebate_total);
			$v["product_price"] = $v["product_count"] = bcadd($v["product_price"], $config_total, 2);
			if ($v["api_type"] == "zjmf_api" && $v["upstream_version"] > 0 && $v["upstream_price_type"] == "percent") {
				$v["product_price"] = bcmul($v["product_price"], $v["upstream_price_value"], 2) / 100;
			}
			$v["product_price"] = bcsub($v["product_price"], 0, 2);
			if ($v["api_type"] == "manual") {
				$v["profit"] = "-";
				$v["product_count"] = "-";
			} else {
				$v["profit"] = bcsub($v["product_price"], $v["product_count"], 2);
			}
			$v["host_count"] = \think\Db::name("host")->where("productid", $v["id"])->count() ?: 0;
			$v["host_active"] = \think\Db::name("host")->where("productid", $v["id"])->where("domainstatus", "Active")->count() ?: 0;
			$v["upstream_product_shopping_url"] = $v["upstream_product_shopping_url"] ?? "";
			$v["server_name"] = \think\Db::name("zjmf_finance_api")->where("id", $v["zjmf_api_id"])->value("name");
			$local_qty += $v["qty"];
			$upstream_qty += $v["upstream_qty"];
			$host_total += $v["host_count"];
			$host_active += $v["host_active"];
		}
		$products_filter = [];
		foreach ($products as $vv) {
			if (!isset($products_filter[$vv["fgname"]])) {
				$products_filter[$vv["fgname"]] = [];
			}
			$products_filter[$vv["fgname"]][] = $vv;
		}
		$filter = [];
		foreach ($products_filter as $k3 => $vvv) {
			foreach ($vvv as $v4) {
				if (!isset($filter[$k3][$v4["gname"]])) {
					$filter[$k3][$v4["gname"]] = [];
				}
				$v4 = array_map(function ($v) {
					return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
				}, $v4);
				if (empty($v4["gid"])) {
					$filter[$k3] = [];
				}
				if (!empty($v4["id"])) {
					$filter[$k3][$v4["gname"]][] = $v4;
				}
			}
		}
		$data = ["products" => $filter, "product_count" => $product_count, "local_qty" => $local_qty, "upstream_qty" => $upstream_qty, "host_count" => $host_total, "host_active" => $host_active, "prefix" => $prefix];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 订单列表
	 * @description 接口说明:
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @param .name:status type:string require:0  other: desc:状态(Pending待审核，Active已激活，Completed已完成,Suspend已暂停,Terminated被删除,Cancelled被取消,Fraud有欺诈)
	 * @param .name:ordernum type:int require:0  other: desc:订单号
	 * @param .name:start_time type:int require:0  other: desc:开始时间
	 * @param .name:end_time type:int require:0  other: desc:结束时间
	 * @param .name:amount type:int require:0  other: desc:金额
	 * @param .name:uid type:int require:0  other: desc:用户
	 * @param .name:payment type:int require:0  other: desc:支付方式
	 * @param .name:pay_status type:int require:0  other: desc:1,
	 * @param .name:sale_id type:int require:0  other: desc:1,
	 * @param .name:zjmf_api_id type:int require:0  other: desc:魔方api ID(接口里传此值)
	 * @return  list:列表@
	 * @list  id:编号
	 * @list  uid:用户id
	 * @list  ordernum：订单号
	 * @list  create_time:
	 * @list  username:
	 * @list  payment:付款方式
	 * @list  amount:总计
	 * @list  pay_status:付款状态
	 * @list  status:状态
	 * @author wyh
	 * @url admin/zjmf_finance_api/order
	 * @method get
	 */
	public function apiOrder()
	{
		session_write_close();
		$page = input("page") ?? config("page");
		$limit = input("limit") ?? config("limit");
		$order = input("order");
		$sort = input("sort") ?? "desc";
		$params = $this->request->param();
		$result = $this->validate(["page" => $page, "limit" => $limit, "order" => $order, "sort" => $sort], "app\\admin\\validate\\OrderValidate");
		if ($result !== true) {
			return jsonrule($result, 400);
		}
		$start_time = $params["start_time"] ?? 0;
		$end_time = $params["end_time"] ?? time();
		$fun = function (\think\db\Query $query) use($params) {
			$str = $this->str;
			$query->where("o.delete_time", 0);
			if (isset($params["id"])) {
				$id = $params["id"];
				$query->where("o.id", "like", "%{$id}%");
			}
			if (isset($params["uid"])) {
				$username = $params["uid"];
				$query->where("c.id", $username);
			}
			if (isset($params["amount"])) {
				$amount = $params["amount"];
				$query->where("o.amount", "like", "%{$amount}%");
			}
			if (isset($params["ordernum"])) {
				$ordernum = $params["ordernum"];
				$query->where("o.ordernum", "like", "%{$ordernum}%");
			}
			if (isset($params["status"])) {
				$status = $params["status"];
				$query->where("o.status", "like", "%{$status}%");
			}
			if (isset($params["payment"]) && !empty($params["payment"])) {
				if ($params["payment"] == "creditLimitPay") {
					$query->where("i.use_credit_limit", 1);
				} elseif ($params["payment"] == "creditPay") {
					$query->where("i.credit", ">", 0);
				} else {
					$query->where("o.payment", $params["payment"]);
				}
			}
			$sale_id = isset($params["sale_id"]) ? $params["sale_id"] : "";
			if (!empty($sale_id)) {
				$query->where("c.sale_id", "=", $sale_id);
			}
			if ($this->user["id"] != 1 && $this->user["is_sale"]) {
				$query->where("c.id", "in", $str);
			}
			if (strlen($params["pay_status"]) >= 1) {
				if ($params["pay_status"] == 0 && strlen($params["pay_status"]) == 1) {
					$query->where("o.invoiceid", "");
				} else {
					$query->where("i.status", $params["pay_status"]);
				}
			}
			$query->where("p.zjmf_api_id|p.upper_reaches_id", ">", 0);
			if (!empty($params["zjmf_api_id"])) {
				$query->where("p.zjmf_api_id|p.upper_reaches_id", $params["zjmf_api_id"]);
			}
		};
		$price_total = \think\Db::name("orders")->alias("o")->leftJoin("host h", "o.id=h.orderid")->leftJoin("products p", "h.productid=p.id")->leftjoin("clients c", "o.uid=c.id")->leftjoin("currencies cu", "cu.id = c.currency")->leftJoin("user u", "u.id = c.sale_id")->leftJoin("invoices i", "o.invoiceid = i.id")->leftJoin("zjmf_finance_api d", "p.zjmf_api_id=d.id OR p.upper_reaches_id=d.id")->where("d.is_resource", 0)->field("o.amount")->where($fun)->where("o.create_time", ">=", $start_time)->where("o.create_time", "<=", $end_time)->sum("o.amount");
		$gateways = gateway_list1("gateways", 0);
		$rows = \think\Db::name("orders")->alias("o")->leftJoin("host h", "o.id=h.orderid")->leftJoin("products p", "h.productid=p.id")->leftjoin("clients c", "o.uid=c.id")->leftjoin("currencies cu", "cu.id = c.currency")->leftJoin("user u", "u.id = c.sale_id")->leftJoin("invoices i", "o.invoiceid = i.id")->leftJoin("upgrades up", "up.order_id = o.id")->leftJoin("zjmf_finance_api d", "p.zjmf_api_id=d.id OR p.upper_reaches_id=d.id")->where("d.is_resource", 0)->field("o.notes as order_notes")->field("up.id as upid,i.type,i.subtotal,c.username,c.companyname,c.notes,o.id,o.uid,c.sale_id,o.status,o.ordernum,i.status as i_status,i.subtotal as sub,i.credit,i.use_credit_limit,o.create_time,o.invoiceid,o.amount,o.amount as am,o.payment,cu.prefix,cu.suffix,u.user_nickname,i.delete_time,i.payment as i_payment")->withAttr("payment", function ($value, $data) use($gateways) {
			$i_value = $data["i_payment"] ?: $value;
			if ($data["i_status"] == "Paid") {
				if ($data["use_credit_limit"] == 1) {
					return "信用额支付";
				} else {
					if ($data["sub"] == $data["credit"]) {
						return "余额支付";
					} else {
						if ($data["sub"] > $data["credit"] && $data["credit"] > 0) {
							foreach ($gateways as $v) {
								if ($v["name"] == $i_value) {
									return "部分余额支付+" . $v["title"];
								}
							}
						} else {
							foreach ($gateways as $v) {
								if ($v["name"] == $i_value) {
									return $v["title"];
								}
							}
						}
					}
				}
			} else {
				foreach ($gateways as $v) {
					if ($v["name"] == $i_value) {
						return $v["title"];
					}
				}
			}
		})->withAttr("amount", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->where($fun)->where("o.create_time", ">=", $start_time)->where("o.create_time", "<=", $end_time)->page($page)->limit($limit)->order($order, $sort)->group("o.id")->select()->toArray();
		$billingcycle = config("billing_cycle");
		$order_status = config("order_status");
		$invoice_payment_status = config("invoice_payment_status");
		$price_total_page = 0;
		$rows_filter = [];
		foreach ($rows as $k => $row) {
			$row["order_notes"] = $row["order_notes"] ?: "";
			if (!isset($rows_filter[$row["id"]])) {
				$price_total_page = bcadd($row["am"], $price_total_page, 2);
				$invoice_status = $row["i_status"];
				$invoice_type = $row["type"];
				if (!empty($row["upid"])) {
					$invoice_type = "upgrade";
				}
				$hosts = [];
				if ($invoice_type == "zjmf_flow_packet") {
					$hosts[] = ["hostid" => 0, "name" => "流量包", "firstpaymentamount" => $row["prefix"] . $row["subtotal"] . $row["suffix"], "billingcycle" => " -"];
				} elseif ($invoice_type == "zjmf_reinstall_times") {
					$hosts[] = ["hostid" => 0, "name" => "重装次数", "firstpaymentamount" => $row["prefix"] . $row["subtotal"] . $row["suffix"], "billingcycle" => " -"];
				} else {
					$hosts = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid = b.id")->leftJoin("upgrades u", "u.relid = a.id")->leftJoin("cancel_requests cr", "cr.relid = a.id")->field("a.uid,a.initiative_renew,a.id as hostid,b.id as invoice_type,b.name,a.domain,a.dedicatedip,a.billingcycle,a.firstpaymentamount,a.productid")->distinct(true)->where(function (\think\db\Query $query) use($row) {
						$query->where("a.orderid", $row["id"]);
						$query->whereOr("u.order_id", $row["id"]);
						$query->where("b.zjmf_api_id|b.upper_reaches_id", ">", 0);
						if (!empty($params["zjmf_api_id"])) {
							$query->where("b.zjmf_api_id|b.upper_reaches_id", $params["zjmf_api_id"]);
						}
					})->withAttr("billingcycle", function ($value) use($billingcycle) {
						return $billingcycle[$value];
					})->withAttr("firstpaymentamount", function ($value) use($row) {
						return $row["prefix"] . $value . $row["suffix"];
					})->withAttr("invoice_type", function ($value, $data) use($invoice_type) {
						if ($invoice_type == "upgrade") {
							return "upgrade";
						} else {
							return "";
						}
					})->order("a.id", "desc")->select()->toArray();
					foreach ($hosts as $k1 => $v1) {
						if (!empty($v1["type"])) {
							if ($v1["type"] == "Immediate") {
								$v1["type"] = "立即停用";
							} else {
								$v1["type"] = "到期时停用";
							}
							$hosts[$k1]["cancel_list"] = ["type" => $v1["type"], "reason" => $v1["reason"]];
						}
					}
				}
				$row["hosts"] = $hosts;
				$row["order_status"] = $order_status[$row["status"]];
				if (empty($row["invoiceid"]) || empty($invoice_status)) {
					$row["pay_status"] = ["name" => lang("NO_INVOICE"), "color" => "#808080"];
					$row["pay_status_tmp"] = "0";
				} else {
					if ($row["delete_time"] > 0) {
						$row["pay_status"] = ["name" => lang("账单已删除"), "color" => "#808080"];
						$row["pay_status_tmp"] = "0";
					} else {
						$row["pay_status"] = $invoice_payment_status[$invoice_status];
						$row["pay_status_tmp"] = $invoice_status;
					}
				}
				unset($rows[$k]["am"]);
				$rows_filter[$row["id"]] = $row;
			}
		}
		$rows = array_values($rows_filter);
		$count = \think\Db::name("orders")->alias("o")->leftJoin("host h", "o.id=h.orderid")->leftJoin("products p", "h.productid=p.id")->leftjoin("clients c", "o.uid=c.id")->leftJoin("invoices i", "o.invoiceid = i.id")->field("c.username,o.id")->where($fun)->where("o.create_time", ">=", $start_time)->where("o.create_time", "<=", $end_time)->count();
		$arr = ["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "list" => $rows, "count" => $count, "price_total" => $price_total, "price_total_page" => $price_total_page];
		return jsonrule($arr);
	}
	/**
	 * @title 订单提成
	 * @description 接口说明:
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @param .name:status type:string require:0  other: desc:状态(Pending待审核，Active已激活，Completed已完成,Suspend已暂停,Terminated被删除,Cancelled被取消,Fraud有欺诈)
	 * @param .name:ordernum type:int require:0  other: desc:订单号
	 * @param .name:start_time type:int require:0  other: desc:开始时间
	 * @param .name:end_time type:int require:0  other: desc:结束时间
	 * @param .name:amount type:int require:0  other: desc:金额
	 * @param .name:username type:int require:0  other: desc:用户
	 * @param .name:payment type:int require:0  other: desc:支付方式
	 * @param .name:pay_status type:int require:0  other: desc:1,
	 * @return  rows:列表@
	 * @list  sum:提成
	 * @author wyh
	 * @url admin/zjmf_finance_api/order_commission
	 * @method post
	 */
	public function apiOrderCom()
	{
		session_write_close();
		$page = input("page") ?? config("page");
		$limit = input("limit") ?? config("limit");
		$order = input("order");
		$sort = input("sort") ?? "desc";
		$params = $this->request->param();
		$str = $this->str;
		$result = $this->validate(["page" => $page, "limit" => $limit, "order" => $order, "sort" => $sort], "app\\admin\\validate\\OrderValidate");
		if ($result !== true) {
			return jsonrule($result, 400);
		}
		$where = ["o.delete_time" => 0];
		$id = isset($params["id"]) ? $params["id"] : "";
		$username = isset($params["username"]) ? $params["username"] : "";
		$start_time = $params["start_time"] ?? 0;
		$end_time = $params["end_time"] ?? time();
		$amount = isset($params["amount"]) ? $params["amount"] : "";
		$status = isset($params["status"]) ? $params["status"] : "";
		$ordernum = isset($params["ordernum"]) ? $params["ordernum"] : "";
		$gateways = gateway_list("gateways", 0);
		$rows = \think\Db::name("orders")->alias("o")->leftJoin("host h", "o.id=h.orderid")->leftJoin("products p", "h.productid=p.id")->leftjoin("clients c", "o.uid=c.id")->leftjoin("currencies cu", "cu.id = c.currency")->leftJoin("user u", "u.id = c.sale_id")->leftJoin("invoices i", "o.invoiceid = i.id")->leftJoin("zjmf_finance_api d", "p.zjmf_api_id=d.id OR p.upper_reaches_id=d.id")->where("d.is_resource", 0)->field("i.status,i.type,o.id,c.sale_id,o.invoiceid,cu.prefix,cu.suffix,o.amount as am,i.use_credit_limit,i.id as invoice_id")->withAttr("payment", function ($value) use($gateways) {
			foreach ($gateways as $v) {
				if ($v["name"] == $value) {
					return $v["title"];
				}
			}
		})->withAttr("amount", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->where($where)->where("o.id", "like", "%{$id}%")->where("c.username", "like", "%{$username}%")->where("o.create_time", ">=", $start_time)->where("o.create_time", "<=", $end_time)->where("o.ordernum", "like", "%{$ordernum}%")->where("o.amount", "like", "%{$amount}%")->where("o.status", "like", "%{$status}%")->where(function (\think\db\Query $query) use($params, $str) {
			if (isset($params["payment"]) && !empty($params["payment"])) {
				if ($params["payment"] == "creditLimitPay") {
					$query->where("i.use_credit_limit", 1);
				} elseif ($params["payment"] == "creditPay") {
					$query->where("i.credit", ">", 0);
				} else {
					$query->where("o.payment", $params["payment"]);
				}
			}
			if ($this->user["id"] != 1 && $this->user["is_sale"]) {
				$query->where("c.id", "in", $str);
			}
			if (strlen($params["pay_status"]) >= 1) {
				if ($params["pay_status"] == 0 && strlen($params["pay_status"]) == 1) {
					$query->where("o.invoiceid", "");
				} else {
					$query->where("i.status", $params["pay_status"]);
				}
			}
			$query->where("p.zjmf_api_id|p.upper_reaches_id", ">", 0);
			if (!empty($params["zjmf_api_id"])) {
				$query->where("p.zjmf_api_id|p.upper_reaches_id", $params["zjmf_api_id"]);
			}
		})->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
		$row1 = \think\Db::name("orders")->alias("o")->leftJoin("host h", "o.id=h.orderid")->leftJoin("products p", "h.productid=p.id")->leftjoin("clients c", "o.uid=c.id")->leftjoin("currencies cu", "cu.id = c.currency")->leftJoin("user u", "u.id = c.sale_id")->leftJoin("invoices i", "o.invoiceid = i.id")->leftJoin("zjmf_finance_api d", "p.zjmf_api_id=d.id OR p.upper_reaches_id=d.id")->where("d.is_resource", 0)->field("o.id,c.sale_id,o.invoiceid,cu.prefix,cu.suffix,o.amount,i.use_credit_limit,i.id as invoice_id")->where($where)->where("o.id", "like", "%{$id}%")->where("c.username", "like", "%{$username}%")->where("o.create_time", ">=", $start_time)->where("o.create_time", "<=", $end_time)->where("o.ordernum", "like", "%{$ordernum}%")->where("o.amount", "like", "%{$amount}%")->where("o.status", "like", "%{$status}%")->where(function (\think\db\Query $query) use($params, $str) {
			if (isset($params["payment"]) && !empty($params["payment"])) {
				if ($params["payment"] == "creditLimitPay") {
					$query->where("i.use_credit_limit", 1);
				} elseif ($params["payment"] == "creditPay") {
					$query->where("i.credit", ">", 0);
				} else {
					$query->where("o.payment", $params["payment"]);
				}
			}
			if ($this->user["id"] != 1 && $this->user["is_sale"]) {
				$query->where("c.id", "in", $str);
			}
			if (strlen($params["pay_status"]) >= 1) {
				if ($params["pay_status"] == 0 && strlen($params["pay_status"]) == 1) {
					$query->where("o.invoiceid", "");
				} else {
					$query->where("i.status", $params["pay_status"]);
				}
			}
			$query->where("p.zjmf_api_id|p.upper_reaches_id", ">", 0);
			if (!empty($params["zjmf_api_id"])) {
				$query->where("p.zjmf_api_id|p.upper_reaches_id", $params["zjmf_api_id"]);
			}
		})->select()->toArray();
		$sums1 = 0;
		foreach ($row1 as $k => $row) {
			$sums1 = bcadd($row["amount"], $sums1);
		}
		$sums = 0;
		foreach ($rows as $k => &$row) {
			$sums = bcadd($row["am"], $sums);
			$invoice_status = $row["status"];
			$invoice_type = $row["type"];
			if ($invoice_type == "upgrade") {
				$hosts = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid = b.id")->leftJoin("sale_products c", "c.pid = b.id")->leftJoin("sales_product_groups d", "d.id = c.gid")->leftJoin("upgrades u", "u.relid = a.id")->field("a.id as hostid,b.name,a.billingcycle,u.id as upid,a.firstpaymentamount,a.productid,d.bates,d.renew_bates,d.upgrade_bates,d.is_renew,d.updategrade")->distinct(true)->where(function (\think\db\Query $query) use($row) {
					$query->where("a.orderid", $row["id"]);
					$query->whereOr("u.order_id", $row["id"]);
					$query->where("b.zjmf_api_id|b.upper_reaches_id", ">", 0);
					if (!empty($params["zjmf_api_id"])) {
						$query->where("b.zjmf_api_id|b.upper_reaches_id", $params["zjmf_api_id"]);
					}
				})->withAttr("billingcycle", function ($value) {
					return config("billing_cycle")[$value];
				})->withAttr("firstpaymentamount", function ($value) use($row) {
					return $row["prefix"] . $value . $row["suffix"];
				})->withAttr("name", function ($value, $data) use($invoice_type) {
					if ($invoice_type == "upgrade") {
						return "[升降级]" . $value;
					} else {
						return $value;
					}
				})->order("a.id", "desc")->select()->toArray();
			} else {
				$hosts = \think\Db::name("invoices")->alias("i")->leftJoin("invoice_items im", "im.invoice_id = i.id")->leftJoin("host a", "a.id = im.rel_id")->leftJoin("products b", "a.productid = b.id")->leftJoin("sale_products c", "c.pid = b.id")->leftJoin("sales_product_groups d", "d.id = c.gid")->field("a.id as hostid,b.name,a.billingcycle,a.firstpaymentamount")->field("a.productid,d.bates,d.renew_bates,d.upgrade_bates,d.is_renew,d.updategrade")->distinct(true)->where("i.id", $row["invoiceid"])->where(function (\think\db\Query $query) use($params) {
					$query->where("b.zjmf_api_id|b.upper_reaches_id", ">", 0);
					if (!empty($params["zjmf_api_id"])) {
						$query->where("b.zjmf_api_id|b.upper_reaches_id", $params["zjmf_api_id"]);
					}
				})->withAttr("billingcycle", function ($value) {
					return config("billing_cycle")[$value];
				})->withAttr("firstpaymentamount", function ($value) use($row) {
					return $row["prefix"] . $value . $row["suffix"];
				})->withAttr("name", function ($value, $data) use($invoice_type) {
					if ($invoice_type == "upgrade") {
						return "[升降级]" . $value;
					} else {
						return $value;
					}
				})->order("a.id", "desc")->select()->toArray();
			}
			$sum = 0;
			$sum1 = 0;
			$refund = 0;
			$refund1 = 0;
			$wheres = [];
			$ladder = $this->getLadder($row["sale_id"]);
			$rows[$k]["ladder"] = $ladder;
			if ($invoice_status == "Paid" && ($row["use_credit_limit"] == 0 || $row["use_credit_limit"] == 1 && $row["invoice_id"] > 0)) {
				foreach ($hosts as $val) {
					$wheres[] = ["type", "neq", "renew"];
					if ($val["updategrade"] == 1 && !empty($val["upid"])) {
						$nums = \think\Db::name("invoice_items")->where("rel_id", $val["upid"])->where("type", "upgrade")->where($wheres)->sum("amount");
						$sum = bcadd(bcmul($val["upgrade_bates"] / 100, $nums, 4), $sum, 4);
					} else {
						if (!empty($val["upid"])) {
							$nums = 0.0;
						} else {
							$nums = \think\Db::name("invoice_items")->where("rel_id", $val["hostid"])->where("invoice_id", $row["invoiceid"])->where($wheres)->sum("amount");
						}
						$sum = bcadd(bcmul($val["bates"] / 100, $nums, 4), $sum, 4);
					}
					if (!empty($ladder["turnover"]["turnover"])) {
						$sum1 = bcadd(bcmul($ladder["turnover"]["bates"] / 100, $nums, 4), $sum1, 4);
					}
				}
				$amount_out = \think\Db::name("accounts")->field("id,amount_out")->where("invoice_id", $row["invoiceid"])->where("refund", ">", 0)->sum("amount_out");
				$bates = 0;
				foreach ($hosts as $vals) {
					if ($val["updategrade"] == 1 && !empty($val["upid"])) {
						if ($vals["upgrade_bates"] / 100 != 0) {
							$bates = $vals["upgrade_bates"] / 100;
							continue;
						}
					} else {
						if ($vals["bates"] / 100 != 0) {
							$bates = $vals["bates"] / 100;
							continue;
						}
					}
				}
				$refund = bcmul($bates, $amount_out, 4);
				if (!empty($ladder["turnover"]["turnover"])) {
					$refund1 = bcmul($ladder["turnover"]["bates"] / 100, $amount_out, 4);
				}
				$sum = round(bcsub($sum, $refund, 4), 2);
				if ($sum < 0) {
					$sum = 0;
				}
				$sum1 = round(bcsub($sum1, $refund1, 4), 2);
				if ($sum1 < 0) {
					$sum1 = 0;
				}
				if ($sum1 > 0) {
					$rows[$k]["sum2"] = $row["prefix"] . number_format($sum, 2, ".", "") . $row["suffix"];
					$sum = bcadd($sum, $sum1);
					$rows[$k]["flag"] = true;
					$rows[$k]["sum"] = $row["prefix"] . number_format($sum, 2, ".", "") . $row["suffix"];
					$rows[$k]["sum1"] = $row["prefix"] . number_format($sum1, 2, ".", "") . $row["suffix"];
				} else {
					$rows[$k]["sum2"] = $row["prefix"] . number_format($sum, 2, ".", "") . $row["suffix"];
					$rows[$k]["flag"] = true;
					$rows[$k]["sum"] = $row["prefix"] . number_format($sum, 2, ".", "") . $row["suffix"];
				}
			} else {
				$rows[$k]["flag"] = false;
				$rows[$k]["sum"] = $row["prefix"] . "0.00" . $row["suffix"];
			}
		}
		session_write_close();
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "list" => $rows, "price" => $sums, "totalprice" => $sums1]);
	}
	/**
	 * 时间 2021-05-27
	 * @title 产品列表
	 * @desc 产品列表
	 * @url /admin/zjmf_finance_api/host
	 * @param .name:id type:int require:0 desc:API id
	 * @param .name:page type:int require:1 default:1 other: desc:第几页
	 * @param .name:limit type:int require:1 default:10 other: desc:每页多少条
	 * @param .name:order type:string require:1 default:10 other: desc:排序字段
	 * @param .name:sort type:int require:1 default:10 other: desc:AESC,DESC
	 * @param .name:keyword type:mix require:0 default: other: desc:关键字搜索
	 * @method GET
	 * @author wyh
	 * @return products:列表@
	 * @products id:
	 * @products name：产品名称
	 * @products dedicatedip:IP
	 * @products username:用户名
	 * @products domain:主机名
	 * @products password:密码
	 * @products product_price:本地价格
	 * @products cost:成本
	 * @products profit:利润
	 * @products create_time：订购时间
	 * @products nextduedate:到期时间
	 * @products billingcycle:付款周期
	 * @products billingcycle_zh
	 * @products firstpaymentamount:首付金额
	 * @products amount:续费金额
	 * @products promo_code:优惠码
	 * @products domainstatus:状态
	 * @products domainstatus_zh:状态，包括颜色
	 * @products notes:客户备注
	 * @products remark:管理员备注
	 * @products saler:销售
	 * @products type_zh:类型
	 * @products prefix:货币单位
	 * @products initiative_renew:是否自动续费
	 * @products assignedips:其他ip
	 * @products companyname:公司名
	 */
	public function apiHost()
	{
		$params = $data = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "DESC";
		$currencyid = getDefaultCurrencyId();
		$prefix = \think\Db::name("currencies")->where("id", $currencyid)->value("prefix");
		$where = function (\think\db\Query $query) use($params) {
			$query->where("d.is_resource", 0);
			$query->where("b.zjmf_api_id|b.upper_reaches_id", ">", 0);
			if (!empty($params["keyword"])) {
				$query->where("b.name|a.dedicatedip|c.username", "like", "%{$params["keyword"]}%");
			}
			if (!empty($params["id"])) {
				$query->where("b.zjmf_api_id|b.upper_reaches_id", $params["id"]);
			}
		};
		$count = \think\Db::name("host")->alias("a")->field("a.id")->leftJoin("products b", "a.productid = b.id")->leftJoin("clients c", "a.uid = c.id")->leftJoin("zjmf_finance_api d", "b.zjmf_api_id=d.id OR b.upper_reaches_id=d.id")->where($where)->count();
		$hosts = \think\Db::name("host")->field("a.id,b.name,a.dedicatedip,c.id as uid,c.username,c.companyname,a.domain,a.password,a.create_time,a.nextduedate,a.billingcycle,a.firstpaymentamount,a.dcimid,a.port,a.initiative_renew,a.assignedips,
            a.amount,a.domainstatus,a.notes,a.remark,b.pay_type,b.api_type,b.upstream_version,b.upstream_price_type,b.upstream_price_value,b.id as pid,a.promoid,c.sale_id,b.type,b.zjmf_api_id,b.upper_reaches_id,a.upstream_cost")->alias("a")->leftJoin("products b", "a.productid = b.id")->leftJoin("clients c", "a.uid = c.id")->leftJoin("zjmf_finance_api d", "b.zjmf_api_id=d.id")->union(function (\think\db\Query $query) use($where) {
			$query->field("a.id,b.name,a.dedicatedip,c.id as uid,c.username,c.companyname,a.domain,a.password,a.create_time,a.nextduedate,a.billingcycle,a.firstpaymentamount,a.dcimid,a.port,a.initiative_renew,a.assignedips,
            a.amount,a.domainstatus,a.notes,a.remark,b.pay_type,b.api_type,b.upstream_version,b.upstream_price_type,b.upstream_price_value,b.id as pid,a.promoid,c.sale_id,b.type,b.zjmf_api_id,b.upper_reaches_id,a.upstream_cost")->name("host a")->leftJoin("products b", "a.productid = b.id")->leftJoin("clients c", "a.uid = c.id")->leftJoin("zjmf_finance_api d", "b.upper_reaches_id=d.id")->where($where);
		})->where($where)->withAttr("password", function ($value) {
			return cmf_decrypt($value);
		})->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		foreach ($hosts as &$v) {
			$pricing = \think\Db::name("pricing")->where("type", "product")->where("currency", $currencyid)->where("relid", $v["pid"])->find();
			$v["product_price"] = bcadd($pricing[$v["billingcycle"]], $pricing[config("price_type")[$v["billingcycle"]][1]]);
			$cart_logic = new \app\common\logic\Cart();
			$rebate_total = 0;
			$config_total = $cart_logic->getProductDefaultConfigPrice($v["pid"], $currencyid, $v["billingcycle"], $rebate_total);
			$v["product_price"] = bcadd($v["product_price"], $config_total, 2);
			$v["cost"] = $v["product_price"];
			if ($v["api_type"] == "zjmf_api" && $v["upstream_version"] > 0 && $v["upstream_price_type"] == "percent") {
				$v["product_price"] = bcmul($v["product_price"], $v["upstream_price_value"] / 100, 2);
			}
			if ($v["upstream_cost"] == "-") {
				$v["profit"] = "-";
			} else {
				$v["profit"] = bcsub($v["amount"], floatval($v["upstream_cost"]), 2);
			}
			$v["billingcycle_zh"] = config("billing_cycle")[$v["billingcycle"]];
			$v["promo_code"] = "";
			if (!empty($v["promoid"])) {
				$v["promo_code"] = \think\Db::name("promo_code")->where("id", $v["promoid"])->value("code") ?: "";
			}
			$v["domainstatus_zh"] = config("public.domainstatus")[$v["domainstatus"]];
			if ($v["api_type"] == "manual") {
				$v["server_name"] = \think\Db::name("zjmf_finance_api")->where("id", $v["upper_reaches_id"])->value("name");
			} else {
				$v["server_name"] = \think\Db::name("zjmf_finance_api")->where("id", $v["zjmf_api_id"])->value("name");
			}
			$v["saler"] = \think\Db::name("user")->where("id", $v["sale_id"])->value("user_nickname");
			$v["type_zh"] = config("product_type")[$v["type"]];
			$v["prefix"] = getUserCurrency($v["uid"])["prefix"];
			unset($v["pay_type"]);
			unset($v["upstream_version"]);
			unset($v["upstream_price_type"]);
			unset($v["upstream_price_value"]);
			unset($v["pid"]);
		}
		$data = ["hosts" => $hosts, "total" => $count, "prefix" => $prefix];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * 时间 2021-06-23
	 * @title 批量拉取产品信息的前台会员中心接口
	 * @desc 批量拉取产品信息的前台会员中心接口
	 * @url /admin/zjmf_finance_api/upstreamhost
	 * @param  .name:id type:int require:0 desc:接口ID
	 * @param  .name:hostid[] type:int require:1 desc:产品ID，数组
	 * @method POST
	 * @author wyh
	 * @return  hosts:基础数据@
	 * @hosts domain:主机
	 * @hosts dedicatedip:ip
	 * @hosts assignedips：附加ip
	 * @hosts create_time：购买时间
	 * @hosts nextduedate：到期时间
	 * @hosts billingcycle：周期
	 * @hosts billingcycle_zh
	 * @hosts firstpaymentamount：首付金额
	 * @hosts amount：续费金额
	 * @hosts port：端口
	 * @hosts username：用户名
	 * @hosts password：密码
	 * @hosts initiative_renew：自动续费
	 * @hosts domainstatus：状态
	 * @hosts domainstatus_zh
	 * @return currency:货币单位
	 */
	public function upstreamHost()
	{
		$param = $this->request->param();
		$id = intval($param["id"]);
		$post_data = ["hostid" => $param["hostid"]];
		$result = zjmfCurl($id, "cart/hostinfo", $post_data, 30, "GET");
		if ($result["status"] == 200) {
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $result["data"]]);
		} else {
			return jsonrule(["status" => 400, "msg" => "接口请求失败"]);
		}
	}
	/**
	 * 时间 2021-05-27
	 * @title API概览
	 * @desc API概览,下游管理上游,需要调取上游接口
	 * @url /admin/zjmf_finance_api/downstream_summary
	 * @param  .name:id type:int require:1 desc:API ID
	 * @method GET
	 * @author wyh
	 * @return  client:基础数据@
	 * @client api_password:api密钥
	 * @client api_create_time:开通时间
	 * @client agent_count:代理商品数量
	 * @client host_count:API产品数量 总量
	 * @client active_count:API产品数量 已激活
	 * @client api_count:昨日api请求次数
	 * @client ratio:日环比
	 * @client up:1上升，0下降
	 * @return form_api:最近7天每天的api请求次数
	 * @return free_products:豁免产品@
	 * @free_products id:
	 * @free_products name:名称
	 * @free_products ontrial:试用数量
	 * @free_products qty:最大购买数量
	 */
	public function downstreamSummary()
	{
		$param = $this->request->param();
		$id = intval($param["id"]);
		$api = \think\Db::name("zjmf_finance_api")->where("id", $id)->find();
		if ($api["type"] == "manual") {
			$client = [];
			$client["host_count"] = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid=b.id")->where("b.upper_reaches_id", $id)->count();
			$client["active_count"] = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid=b.id")->where("b.upper_reaches_id", $id)->where("a.domainstatus", "Active")->count();
			$client["agent_count"] = \think\Db::name("products")->where("upper_reaches_id", $id)->count();
			$data = ["client" => $client];
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
		} else {
			$result = zjmfCurl($id, "cart/summary", [], 30, "GET");
			if ($result["status"] == 200) {
				return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $result["data"]]);
			} else {
				return jsonrule(["status" => 400, "msg" => "接口请求失败"]);
			}
		}
	}
	/**
	 * 时间 2021-06-03
	 * @title API日志
	 * @desc API日志
	 * @url /admin/zjmf_finance_api/logs
	 * @method GET
	 * @author wyh
	 * @version v1
	 * @param   .name:uid type:int require:0 desc:客户ID，单个客户日志需要传 default:1
	 * @param   .name:page type:int require:0 desc:页数 default:1
	 * @param   .name:limit type:int require:0 desc:每页条数 default:10
	 * @param   .name:order type:string require:0 desc:排序(id,name,hostname,server_num,api_status) default:id
	 * @param   .name:sort type:string require:0 desc:排序方向(asc,desc) default:asc
	 * @param name:search_time type:int  require:0  default: other: desc:传入时间戳，返回当天日志
	 * @param name:search_desc type:string  require:0  default: other: desc:通过描述查询
	 * @param name:search_ip type:string  require:0  default: other: desc:ip地址查询
	 * @return  logs:日志@
	 * @list  id:ID
	 * @list  create_time:
	 * @list  description:
	 * @list  ip:
	 * @list  username:
	 */
	public function apiLog()
	{
		$param = $this->request->param();
		$page = !empty($param["page"]) ? intval($param["page"]) : config("page");
		$limit = !empty($param["limit"]) ? intval($param["limit"]) : config("limit");
		$order = !empty($param["order"]) ? trim($param["order"]) : "a.id";
		$sort = !empty($param["sort"]) ? trim($param["sort"]) : "DESC";
		$where = function (\think\db\Query $query) use($param) {
			if (!empty($param["uid"])) {
				$query->where("a.uid", $param["uid"]);
			}
			if (!empty($param["search_time"])) {
				$start_time = strtotime(date("Y-m-d", $param["search_time"]));
				$end_time = strtotime(date("Y-m-d", $param["search_time"])) + 86400;
				$query->where("a.create_time", "<=", $end_time);
				$query->where("a.create_time", ">=", $start_time);
			}
			if (!empty($param["search_desc"])) {
				$search_desc = $param["search_desc"];
				$query->where("a.description", "like", "%{$search_desc}%");
			}
			if (!empty($param["search_ip"])) {
				$search_ip = $param["search_ip"];
				$query->where("a.ip", "like", "%{$search_ip}%");
			}
		};
		$logs = \think\Db::name("api_resource_log")->alias("a")->field("a.id,a.create_time,a.description,a.ip,b.username,a.port")->leftJoin("clients b", "a.uid = b.id")->where($where)->withAttr("description", function ($value, $data) {
			$pattern = "/(?P<name>\\w+ ID):(?P<digit>\\d+)/";
			preg_match_all($pattern, $value, $matches);
			$name = $matches["name"];
			$digit = $matches["digit"];
			if (!empty($name)) {
				foreach ($name as $k => $v) {
					$relid = $digit[$k];
					$str = $v . ":" . $relid;
					if ($v == "Invoice ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/bill-detail?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "User ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-view/abstract?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "FlowPacket ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/dcim-traffic?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Host ID") {
						$host = \think\Db::name("host")->alias("a")->field("a.uid")->where("a.id", $relid)->find();
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-view/product-innerpage?hid=" . $relid . "&id=" . $host["uid"] . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Promo_codeID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/promo-code-add?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Order ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/order-detail?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Admin ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/admin-edit?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Contacts ID") {
					} elseif ($v == "Ticket ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/support-ticket?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Product ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/edit-product?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "IP") {
					} elseif ($v == "Service ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/add-server?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Create ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/balance-details?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Transaction ID") {
					} elseif ($v == "Role ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/permissions-edit?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Group ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-group?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Currency ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/currency-settings?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;height: 24px;line-height: 24px;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					}
				}
				return $value;
			} else {
				return $value;
			}
		})->withAttr("ip", function ($value, $data) {
			if (empty($data["port"])) {
				return $value;
			} else {
				return $value .= ":" . $data["port"];
			}
		})->order($order, $sort)->page($page)->limit($limit)->select()->toArray();
		$count = \think\Db::name("api_resource_log")->alias("a")->leftJoin("clients b", "a.uid = b.id")->where($where)->count();
		$user_list = \think\Db::name("api_resource_log")->alias("a")->leftJoin("clients b", "a.uid = b.id")->field("a.uid,b.username")->group("a.uid")->select()->toArray();
		$data = ["logs" => $logs, "count" => $count, "user_list" => $user_list];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 产品添加页面
	 * @description 接口说明:产品添加页面
	 * @url /admin/zjmf_finance_api/addpage
	 * @author wyh
	 * @method GET
	 * @return groupdata:产品组数据@
	 * @groupdata  id:组ID
	 * @groupdata  name:组名称
	 */
	public function addPage()
	{
		$groupdata = \think\Db::name("product_groups")->field("id,name")->order("order", "asc")->select()->toArray();
		$apis = \think\Db::name("zjmf_finance_api")->field("id,name")->select()->toArray();
		$re["status"] = 200;
		$re["msg"] = lang("SUCCESS MESSAGE");
		$re["data"]["groupdata"] = $groupdata;
		$re["data"]["apis"] = $apis;
		$re["data"]["ptype"] = $this->getProductType();
		return jsonrule($re);
	}
	private function getProductType($pid = 0)
	{
		$list = (new \app\common\logic\Menu())->getOneNavs("client", null);
		$p_list = array_filter($list, function ($v) {
			return $v["nav_type"] == 2;
		});
		if ($pid) {
			foreach ($p_list as $key => $val) {
				$p_list[$key]["is_active"] = 0;
				if (in_array($pid, explode(",", $val["relid"]))) {
					$p_list[$key]["is_active"] = 1;
				}
			}
		}
		return array_values($p_list);
	}
	/**
	 * @title 导入商品
	 * @description 接口说明:导入商品
	 * @url /admin/zjmf_finance_api/inputproduct
	 * @author wyh
	 * @method POST
	 * @param .name:gid  type:number require:1  default: other: desc:组ID
	 * @param .name:productnames[upstream_pid] type:string require:1  default: other: desc:值为产品名称,键为上游产品ID(数组,这里就是上游商品的名称)
	 * @param .name:upstream_price_value type:string require:0  default: other: desc:利润百分比
	 * @param .name:ptype type:string require:0  default: other: desc:导航类型
	 * @param .name:zjmf_finance_api_id type:int  require:1  default: other: desc:魔方财务api ID
	 * @param name:rate type:float  require:0  default: other: desc:汇率(上下游汇率不一样时,需要传此值)
	 */
	public function inputProduct(\think\Request $request)
	{
		$param = $request->param();
		$pro = new ProductController();
		$request->type = "hostingaccount";
		if (!is_array($param["productnames"])) {
			return jsonrule(["status" => 400, "msg" => "参数错误"]);
		}
		$productnames = is_array($param["productnames"]) ? $param["productnames"] : [];
		\think\Db::startTrans();
		try {
			foreach ($productnames as $k => $v) {
				$request->productname = $v;
				$res = json_decode($pro->create($request)->getContent(), true);
				if ($res["status"] != 200) {
					\think\Db::rollback();
					return jsonrule(["status" => 400, "msg" => $res["msg"]]);
				}
				$request->pid = $res["id"];
				$request->upstream_pid = $k;
				$request->api_type = "zjmf_api";
				$res1 = json_decode($pro->syncProductInfo()->getContent(), true);
				if ($res1["status"] != 200) {
					\think\Db::rollback();
					return jsonrule(["status" => 400, "msg" => $res1["msg"]]);
				}
			}
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsonrule(["status" => 400, "msg" => $e->getMessage()]);
		}
		return jsonrule(["status" => 200, "msg" => "导入成功"]);
	}
	/**
	 * @title 手动资源人工记录信息
	 * @description 接口说明:手动资源人工记录信息
	 * @url /admin/zjmf_finance_api/manualhost
	 * @author wyh
	 * @method GET
	 * @param  .name:hostid[] type:int require:1 desc:产品ID，数组
	 * @return upper_manual:基础信息@
	 * @upper_manual id:
	 * @upper_manual hid:
	 * @upper_manual regate:到期时间
	 * @upper_manual amount:金额
	 * @upper_manual billingcycle:周期
	 * @upper_manual dedicatedip:ip
	 * @upper_manual assignedips:分配ip
	 * @upper_manual create_time:开通时间
	 */
	public function getManualHost()
	{
		$param = $this->request->param();
		$hostids = is_array($param["hostid"]) ? $param["hostid"] : [];
		$upper_manual = \think\Db::name("upper_manual_info")->whereIn("hid", $hostids)->select()->toArray();
		$data = ["upper_manual" => $upper_manual];
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 手动资源人工记录信息
	 * @description 接口说明:手动资源人工记录信息(添加、编辑)
	 * @url /admin/zjmf_finance_api/manualhost
	 * @author wyh
	 * @method POST
	 * @param  .name:id type:int require:1 desc:编辑时传
	 * @return upper_manual:基础信息@
	 * @upper_manual id:
	 * @upper_manual hid:
	 * @upper_manual regate:到期时间 (以下字段都是文本，记录信息)
	 * @upper_manual amount:金额
	 * @upper_manual billingcycle:周期
	 * @upper_manual dedicatedip:ip
	 * @upper_manual assignedips:分配ip
	 * @upper_manual create_time:开通时间
	 */
	public function postManualHost()
	{
		$param = $this->request->param();
		if (empty($param["hid"])) {
			return jsonrule(["status" => 400, "msg" => "参数错误"]);
		}
		$exist = \think\Db::name("upper_manual_info")->where("hid", $param["hid"])->find();
		if (!empty($exist)) {
			\think\Db::name("upper_manual_info")->where("hid", $param["hid"])->update(["regate" => $param["regate"], "amount" => $param["amount"], "billingcycle" => $param["billingcycle"], "dedicatedip" => $param["dedicatedip"], "assignedips" => $param["assignedips"], "create_time" => $param["create_time"]]);
		} else {
			\think\Db::name("upper_manual_info")->insert(["hid" => $param["hid"], "regate" => $param["regate"], "amount" => $param["amount"], "billingcycle" => $param["billingcycle"], "dedicatedip" => $param["dedicatedip"], "assignedips" => $param["assignedips"], "create_time" => $param["create_time"]]);
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * 时间 2021-07-19
	 * @title 获取余额
	 * @desc 获取余额
	 * @url /admin/zjmf_finance_api/upstreamcredit
	 * @param  .name:id type:int require:0 desc:接口ID
	 * @method GET
	 * @author wyh
	 * @return  credit:余额
	 * @return  currency:货币属性
	 */
	public function upstreamCredit()
	{
		$param = $this->request->param();
		$id = intval($param["id"]);
		$result = zjmfCurl($id, "cart/credit", [], 30, "GET");
		if ($result["status"] == 200) {
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $result["data"]]);
		} else {
			return jsonrule(["status" => 400, "msg" => "接口请求失败"]);
		}
	}
	/**
	 * @title 续费订单列表
	 * @description 接口说明:
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @param .name:zjmf_api_id type:int require:0  other: desc:智简魔方api ID
	 * @param .name:start_time type:int require:0  other: desc:开始时间
	 * @param .name:end_time type:int require:0  other: desc:结束时间
	 * @param .name:payment type:int require:0  other: desc:支付方式
	 * @param .name:status type:int require:0  other: desc:支付状态
	 * @return  list:列表@
	 * @list  id:编号
	 * @list  uid:用户id
	 * @list  ordernum：订单号
	 * @list  create_time:
	 * @list  username:
	 * @list  payment:付款方式
	 * @list  amount:总计
	 * @list  status:状态
	 * @author wyh
	 * @url admin/zjmf_finance_api/renew
	 * @method get
	 */
	public function getRenew()
	{
		$param = $this->request->param();
		$page = $param["page"] ?: config("page");
		$limit = $param["limit"] ?: config("limit");
		$order = $param["order"];
		$sort = $param["sort"] ?: "desc";
		$where = function (\think\db\Query $query) use($param) {
			$start_time = $param["start_time"] ?: 0;
			$end_time = $param["end_time"] ?: time();
			$query->where("d.type", "renew")->where("f.zjmf_api_id|f.upper_reaches_id", ">", 0)->where("a.paid_time", ">=", $start_time)->where("a.paid_time", "<=", $end_time);
			if ($param["zjmf_api_id"]) {
				$query->where("g.id", $param["zjmf_api_id"]);
			}
			if ($param["status"]) {
				$query->where("a.status", $param["status"]);
			}
		};
		$gateways = gateway_list1("gateways", 0);
		$rows = \think\Db::name("invoices")->alias("a")->field("a.id,b.username,b.companyname,f.name,e.domain,e.dedicatedip,e.assignedips,e.nextduedate,e.amount,
            a.payment,c.prefix,c.suffix,a.use_credit_limit,a.credit,a.subtotal as sub,a.status,e.upstream_cost as cost")->leftJoin("clients b", "a.uid=b.id")->leftJoin("currencies c", "b.currency=c.id")->leftJoin("invoice_items d", "a.id=d.invoice_id")->leftJoin("host e", "d.rel_id=e.id")->leftJoin("products f", "f.id=e.productid")->leftJoin("zjmf_finance_api g", "f.zjmf_api_id=g.id OR f.upper_reaches_id=g.id")->where($where)->withAttr("payment", function ($value, $data) use($gateways) {
			if ($data["status"] == "Paid") {
				if ($data["use_credit_limit"] == 1) {
					return "信用额";
				} else {
					if ($data["sub"] == $data["credit"]) {
						return "余额支付";
					} else {
						if ($data["sub"] > $data["credit"] && $data["credit"] > 0) {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return "部分余额支付+" . $v["title"];
								}
							}
						} else {
							foreach ($gateways as $v) {
								if ($v["name"] == $value) {
									return $v["title"];
								}
							}
						}
					}
				}
			} else {
				foreach ($gateways as $v) {
					if ($v["name"] == $value) {
						return $v["title"];
					}
				}
			}
		})->page($page)->limit($limit)->order($order, $sort)->select()->toArray();
		$count = \think\Db::name("invoices")->alias("a")->leftJoin("clients b", "a.uid=b.id")->leftJoin("currencies c", "b.currency=c.id")->leftJoin("invoice_items d", "a.id=d.invoice_id")->leftJoin("host e", "d.rel_id=e.id")->leftJoin("products f", "f.id=e.productid")->leftJoin("zjmf_finance_api g", "f.zjmf_api_id=g.id OR f.upper_reaches_id=g.id")->where($where)->count();
		$arr = ["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["lists" => $rows, "count" => $count]];
		return jsonrule($arr);
	}
}