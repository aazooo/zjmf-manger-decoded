<?php

namespace app\openapi\controller;

/**
 * @title 产品管理
 * @description 接口说明
 */
class HostController extends \cmf\controller\HomeBaseController
{
	public function initialize()
	{
		parent::initialize();
		$_action = ["on", "off", "reboot", "hard_off", "hard_reboot", "bmc", "kvm", "ikvm", "repassword", "rescue", "reinstall", "vnc"];
		$action = request()->action();
		if (in_array($action, $_action) && request()->id) {
			if ($action == "repassword") {
				$action = "crack_pass";
			}
			$client = \think\Db::name("clients")->field("phone_code,phonenumber,email,second_verify")->where("id", request()->uid)->find();
			$second_verify_action_home = explode(",", configuration("second_verify_action_home"));
			if (in_array($action, $second_verify_action_home)) {
				$second_verify_action = 1;
			} else {
				$second_verify_action = 0;
			}
			$second_verify_home = configuration("second_verify_home");
			$verification_success_1 = \think\facade\Cache::get("verification_success" . $client["phone_code"] . $client["phonenumber"]);
			$verification_success_2 = \think\facade\Cache::get("verification_success" . $client["email"]);
			if ($client["second_verify"] == 1 && !empty($second_verify_home) && !empty($second_verify_action)) {
				if (empty($verification_success_1) && empty($verification_success_2)) {
					$type = explode(",", configuration("second_verify_action_home_type"));
					$all_type = config("second_verify_action_home_type");
					$second_verify = [];
					foreach ($all_type as $v) {
						foreach ($type as $vv) {
							if ($vv == $v["name"]) {
								if ($v["name"] == "email") {
									$v["account"] = !empty($client["email"]) ? str_replace(substr($client["email"], 3, 4), "****", $client["email"]) : "未绑定邮箱";
								} elseif ($v["name"] == "phone") {
									$v["account"] = !empty($client["phonenumber"]) ? str_replace(substr($client["phonenumber"], 3, 4), "****", $client["phonenumber"]) : "未绑定手机";
								}
								$second_verify[] = $v;
							}
						}
					}
					$result["second_verify"] = $second_verify;
					echo json_encode(["status" => 400, "data" => $result, "msg" => "This operation requires secondary verification"]);
					exit;
				}
			}
			$is_certifi = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", request()->uid)->where("a.id", intval(request()->id))->value("c.is_certifi");
			$is_certifi = json_decode($is_certifi, true) ?: [];
			if (!empty($is_certifi)) {
				if ($is_certifi[$action] == 1 && !checkCertify(request()->uid)) {
					echo json_encode(["status" => 400, "msg" => lang("DCIM_CHECK_CERTIFY_ERROR")]);
					exit;
				}
			}
		}
	}
	public function getHostsCates()
	{
		$param = $this->request->param();
		$list = $this->getHostType();
		foreach ($list as $key => $value) {
			$list[$key] = ["id" => $value["id"], "name" => $value["name"]];
		}
		$data = ["cate" => $list];
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function getHosts()
	{
		$param = $this->request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$id = $param["cate_id"] ? intval($param["cate_id"]) : 0;
		$request = $this->request;
		$request->search = $param["keywords"];
		$nav_list = (new \app\common\logic\Menu())->getOneNavs("client", null);
		$nav_info = $nav_list[$id] ?? [];
		if (!getEdition()) {
			$nav_info["orderFuc"] = 0;
		}
		if (!file_exists(CMF_ROOT . "public/themes/clientarea/" . configuration("clientarea_default_themes") . "/" . $nav_info["templatePage"] . ".tpl")) {
			$nav_info["templatePage"] = "service";
		}
		$request->navRelid = array_filter(explode(",", $nav_info["relid"] ?? ""));
		$domain_status_empty = 0;
		if (!$request->param("domainstatus")) {
			$domain_status_empty = 1;
			$request->domainstatus = ["Pending", "Active", "Suspended"];
		}
		if ($nav_info["templatePage"] == "service_ssl") {
			if ($domain_status_empty) {
				$request->domainstatus = ["Pending", "Active", "Verifiy_Active", "Overdue_Active", "Issue_Active", "Cancelled", "Deleted"];
			}
		}
		$uid = $request->uid;
		$groupid = input("get.groupid", 0);
		$search = $request->search ?? "";
		$domain_status = $request->domainstatus ?? [];
		$page = $page > 0 ? $page : 1;
		$limit = $limit > 0 ? $limit : 10;
		if (!in_array($orderby, ["id", "domainstatus", "product_name", "regdate", "nextduedate", "firstpaymentamount", "dedicatedip"])) {
			$orderby = "id";
		}
		if (!in_array($sort, ["asc", "desc"])) {
			$sort = "DESC";
		}
		if ($request->navRelid) {
			$where[] = ["p.id", "in", $request->navRelid];
		}
		if ($groupid != 0 && !$request->navRelid) {
			$where[] = ["p.groupid", "=", $groupid];
		}
		$where[] = ["h.uid", "=", $uid];
		if (!empty($search)) {
			$where[] = ["h.dedicatedip|h.assignedips|h.remark|p.name|h.domain", "LIKE", "%{$search}%"];
		}
		if (!empty($domain_status)) {
			$where[] = ["h.domainstatus", "in", $domain_status];
		}
		$developer_app_product_type = array_keys(config("developer_app_product_type"));
		$where[] = ["p.type", "not in", $developer_app_product_type];
		$type = $request->type ?? input("get.type", "list");
		if ($template_page != "service_ssl") {
			if ($type == "list") {
				$where[] = ["h.domainstatus", "<>", "Cancelled"];
			} else {
				$where[] = ["h.domainstatus", "eq", "Active"];
			}
		}
		$where[] = ["h.agent_client", "=", 0];
		$count = \think\Db::name("host")->alias("h")->leftJoin("products p", "p.id=h.productid")->where($where)->count();
		$max_page = ceil($count / $page);
		$data = \think\Db::name("host")->field("h.id,h.domain,h.initiative_renew,h.domainstatus,h.regdate,h.dedicatedip,h.assignedips,h.nextduedate,h.remark,h.firstpaymentamount,h.amount,h.billingcycle,p.name as product_name,p.type,p.id as product_id")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("dcim_servers b", "h.serverid=b.serverid")->where($where)->withAttr("assignedips", function ($value) {
			if (!empty($value)) {
				return explode(",", $value);
			} else {
				return [];
			}
		})->group("h.id")->page($page)->limit($limit);
		if ($orderby === "domainstatus") {
			$data = $data->orderField("domainstatus", ["Suspended", "Active", "Pending"], $sort)->select()->toArray();
		} else {
			$data = $data->order($orderby, $sort)->select()->toArray();
		}
		foreach ($data as $key => $val) {
			$host_cancel = \think\Db::name("cancel_requests")->field("type,reason")->where("relid", $val["id"])->find();
			$data[$key]["host_cancel"] = $host_cancel ?? [];
			$data[$key]["remark"] = html_entity_decode($val["remark"]);
		}
		$result["data"]["total"] = $count;
		$result["data"]["host"] = $data;
		$domainstatus = config("public.domainstatus");
		$result["data"]["domainstatus"] = $domainstatus;
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $result["data"]]);
	}
	public function getHostDetail($id = 0)
	{
		$uid = $this->request->uid;
		if (empty($id)) {
			return json(["status" => 400, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$host_exists = \think\Db::name("host")->where("uid", $uid)->where("id", $id)->find();
		if (empty($host_exists)) {
			return json(["status" => 400, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$host = \think\Db::name("host")->field("h.id,h.uid,h.initiative_renew,h.regdate,h.domain,h.payment,p.groupid group_id,
                h.firstpaymentamount,h.amount,h.billingcycle,h.nextduedate,
                h.dedicatedip,h.assignedips,h.domainstatus,h.username,h.password,h.suspendreason,p.id as product_id,
                h.bwusage,h.bwlimit,h.os,h.remark,h.dcimid,h.port,p.type,p.name as product_name,p.config_options_upgrade")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("product_groups g", "g.id=p.gid")->leftJoin("orders o", "o.id=h.orderid")->where("h.id", $id)->find();
		$group = \think\Db::name("nav_group")->where("id", $host["group_id"])->find();
		$host["group_name"] = $group["groupname"];
		$domainstatus_config = config("domainstatus");
		$currency = getUserCurrency($uid);
		$billing_cycle = config("billing_cycle");
		$host["suspend_type"] = explode("-", $host["suspendreason"])[0] ? explode("-", $host["suspendreason"])[0] : "";
		$host["suspend_reason"] = explode("-", $host["suspendreason"])[1] ? explode("-", $host["suspendreason"])[1] : "";
		unset($host["suspendreason"]);
		$host["assignedips"] = !empty($host["assignedips"]) ? explode(",", $host["assignedips"]) : [];
		$host["password"] = cmf_decrypt($host["password"]);
		$host["ip_num"] = count($host["assignedips"]);
		$host["bwusage"] = round($host["bwusage"], 2);
		$host["remark"] = html_entity_decode($host["remark"]);
		$returndata["host"] = $host;
		$productid = $host["product_id"];
		$config_options = [];
		$config_logic = new \app\common\logic\ConfigOptions();
		$config_options = $config_logic->showInfo($productid, $id, $currency, $host["billingcycle"], false);
		$config_options = array_values($config_options);
		foreach ($config_options as $k => $v) {
			$config_options[$k]["key"] = $v["name_k"];
			$config_options[$k]["type"] = $v["option_type"];
			$config_options[$k]["value"] = $v["sub_name"];
			unset($config_options[$k]["pid"]);
			unset($config_options[$k]["name_k"]);
			unset($config_options[$k]["option_type"]);
			unset($config_options[$k]["sub_name"]);
		}
		$returndata["host"]["config_option"] = array_values($config_options);
		$custom_field_data = \think\Db::name("customfields")->field("id,fieldname name")->where("type", "product")->where("relid", $productid)->where("adminonly", 0)->select()->toArray();
		foreach ($custom_field_data as &$cv) {
			$cv["value"] = \think\Db::name("customfieldsvalues")->where("fieldid", $cv["id"])->where("relid", $id)->value("value") ?? "";
		}
		$returndata["host"]["custom_field"] = $custom_field_data ?? [];
		unset($returndata["host"]["dcimid"]);
		$os_config_option_id = \think\Db::name("product_config_links")->alias("a")->leftJoin("product_config_options b", "a.gid=b.gid")->where("a.pid", $host["product_id"])->where("b.option_type", 5)->value("b.id");
		$os_info = \think\Db::name("host_config_options")->alias("a")->field("b.option_name")->leftJoin("product_config_options_sub b", "a.optionid=b.id")->where("a.relid", $id)->where("a.configid", $os_config_option_id)->find();
		if (empty($host["username"])) {
			if (stripos($os_info["option_name"], "win") !== false) {
				$returndata["host"]["username"] = "administrator";
			} else {
				$returndata["host"]["username"] = "root";
			}
		}
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $returndata]);
	}
	public function getHostLogs($id = 0)
	{
		$uid = $this->request->uid;
		$param = $this->request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sorting = $param["sorting"] ?? "DESC";
		$fun = function (\think\db\Query $query) use($uid, $id) {
			$query->where("uid", $uid);
			$query->where("activeid", $uid);
			$query->where("usertype", "Client");
			$query->where("type", 2);
			$query->where("type_data_id", $id);
			$query->whereOr("usertype", "Sub-Account");
		};
		$logs = \think\Db::name("activity_log")->field("create_time,id,ipaddr,description,uid,user,port")->where($fun)->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["keywords"])) {
				$search_desc = $param["keywords"];
				$query->whereOr("description", "like", "%{$search_desc}%");
				$query->whereOr("ipaddr", "like", "%{$search_desc}%");
			}
		})->order("{$orderby} {$sorting}")->order("id", "DESC")->page($page)->limit($limit)->select()->toArray();
		$count = \think\Db::name("activity_log")->where($fun)->where(function (\think\db\Query $query) use($param) {
			if (!empty($param["keywords"])) {
				$search_desc = $param["keywords"];
				$query->whereOr("description", "like", "%{$search_desc}%");
				$query->whereOr("ipaddr", "like", "%{$search_desc}%");
			}
		})->count();
		$data = [];
		$data["count"] = $count;
		$data["list"] = $logs;
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function getBillLog($id = 0)
	{
		$data = [];
		$uid = request()->uid;
		$params = $this->request->only(["limit", "page", "hostid"]);
		$hostid = $id;
		if (!$hostid) {
			return json(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$gateways = gateway_list();
		$accounts = \think\Db::name("accounts")->alias("a")->field("a.trans_id,a.pay_time,a.gateway,a.refund,a.amount_out,ii.type,ii.amount as amount_in")->leftJoin("invoice_items ii", "a.invoice_id = ii.invoice_id")->withAttr("type", function ($value) {
			if ($value == "renew") {
				return "续费";
			} elseif ($value == "host") {
				return "产品";
			} elseif ($value == "upgrade") {
				return "升降级";
			} else {
				return "";
			}
		})->withAttr("gateway", function ($value) use($gateways) {
			foreach ($gateways as $v) {
				if ($v["name"] == $value) {
					return $v["title"];
				}
			}
		})->where("ii.rel_id", $hostid)->where("a.uid", $uid)->where("a.delete_time", 0)->whereIn("ii.type", ["host", "renew", "upgrade"])->select()->toArray();
		$invoices = \think\Db::name("invoice_items")->distinct(true)->field("invoice_id")->where("rel_id", $hostid)->whereIn("type", ["host", "renew"])->select()->toArray();
		$ids = array_column($invoices, "invoice_id") ?? [];
		if (!empty($ids[0])) {
			$credit_log_uses = \think\Db::name("credit")->alias("f")->leftJoin("invoice_items ii", "f.relid = ii.invoice_id")->field("f.id as trans_id,f.create_time as pay_time,ii.amount as amount_in,f.description as gateway,ii.type")->withAttr("type", function ($value) {
				if ($value == "renew") {
					return "续费";
				} elseif ($value == "host") {
					return "产品";
				} elseif ($value == "upgrade") {
					return "升降级";
				} else {
					return "";
				}
			})->whereIn("f.relid", $ids)->whereIn("ii.type", ["host", "renew", "upgrade"])->where(function (\think\db\Query $query) use($ids) {
				$query->where("f.description", "like", "%Credit Applied to Invoice #%");
				foreach ($ids as $vv) {
					$query->whereOr("f.description", "Credit Removed from Invoice #{$vv}");
					$query->whereOr("f.description", "Credit Applied to Renew Invoice #{$vv}");
				}
			})->select()->toArray();
			foreach ($credit_log_uses as &$credit_log_use) {
				if (preg_match("/Credit Applied to Invoice #/", $credit_log_use["gateway"])) {
					$credit_log_use["gateway"] = "余额支付";
				} elseif (preg_match("/Credit Removed from Invoice #/", $credit_log_use["gateway"])) {
					$credit_log_use["gateway"] = "移除余额";
				} elseif (preg_match("/Credit Applied to Renew Invoice #/", $credit_log_use["gateway"])) {
					$credit_log_use["gateway"] = "余额支付";
				}
				$credit_log_use["refund"] = 0;
				$credit_log_use["amount_out"] = 0;
				array_push($accounts, $credit_log_use);
			}
		}
		$count = count($accounts);
		$offset = ($page - 1) * $limit;
		$length = $limit;
		$accounts = array_slice($accounts, $offset, $length);
		$data["count"] = $count;
		$data["invoices"] = $accounts;
		$data["currency"] = getUserCurrency($uid);
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function getHostDownloads($id = 0)
	{
		$uid = $this->request->uid;
		$host_id = $id;
		if (empty($host_id)) {
			return json(["status" => 400, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$host_exists = \think\Db::name("host")->where("uid", $uid)->where("id", $host_id)->find();
		if (empty($host_exists)) {
			return json(["status" => 400, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$download_data = \think\Db::name("downloads")->field("d.id,d.title name,d.create_time,d.downloads amount,d.productdownload")->alias("d")->leftJoin("product_downloads p", "p.download_id=d.id")->where("p.product_id", $host_exists["productid"])->select()->toArray();
		foreach ($download_data as $key => $val) {
			if ($val["productdownload"] == 1 && !in_array($host_exists["domainstatus"], ["Active"])) {
				unset($download_data[$key]);
				continue;
			}
			unset($download_data[$key]["productdownload"]);
		}
		$data["download"] = $download_data;
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function hostDownloadFile($id = 0, $download = 0)
	{
		$uid = $this->request->uid;
		if (empty($id)) {
			return json(["status" => 400, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		$host_exists = \think\Db::name("host")->where("uid", $uid)->where("id", $id)->find();
		if (empty($host_exists)) {
			return json(["status" => 400, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		if (empty($download)) {
			return json(["status" => 400, "msg" => "Download ID error"]);
		}
		$download_data = \think\Db::name("downloads")->where("id", $download)->find();
		if (empty($download_data)) {
			return json(["status" => 400, "msg" => "Download file not found"]);
		}
		$productdownload = $download_data["productdownload"];
		if ($productdownload) {
			$product_download_data = \think\Db::name("product_downloads")->field("id,product_id")->where("download_id", $download)->select()->toArray();
			$need_product = array_column($product_download_data, "product_id");
			if (!empty($need_product)) {
				$exists_data = \think\Db::name("host")->field("id, domainstatus")->where("uid", $uid)->whereIn("productid", $need_product[0])->where("domainstatus", "Active")->select()->toArray();
				if (empty($exists_data[0])) {
					return json(["status" => 400, "msg" => "Download ID error"]);
				}
			} else {
				return json(["status" => 400, "msg" => "Download ID error"]);
			}
		}
		$filename = $download_data["location"];
		if ($download_data["filetype"] == "remote") {
			\think\Db::name("downloads")->where("id", $download)->setInc("downloads");
			\ob_clean();
			return json(["status" => 200, "data" => $this->redirect($download_data["locationname"], 302)]);
			exit;
		}
		if (file_exists(UPLOAD_PATH_DWN . "support/" . $filename)) {
			\think\Db::name("downloads")->where("id", $download)->setInc("downloads");
			\ob_clean();
			return download(UPLOAD_PATH_DWN . "support/" . $filename, $download_data["locationname"]);
			return json(["status" => 200, "data" => $this->download(UPLOAD_PATH_DWN . "support/" . $filename, explode("^", $filename)[1])]);
			exit;
			return $this->download(UPLOAD_PATH_DWN . "support/" . $filename, $filename);
		} else {
			return json(["status" => 400, "msg" => "The resource is lost"]);
		}
	}
	private function getHostType($pid = 0)
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
	public function renewPage()
	{
		$data = [];
		$param = $this->request->param();
		$hid = $param["id"];
		if (empty($hid)) {
			return json(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$host = \think\Db::name("host")->alias("a")->field("a.initiative_renew,a.productid,a.uid,a.firstpaymentamount,a.amount,a.create_time,a.nextduedate,a.billingcycle,a.productid,c.status,c.id,a.domainstatus,a.regdate,a.flag,a.promoid")->leftJoin("orders b", "b.id = a.orderid")->leftJoin("invoices c", "b.invoiceid = c.id")->where("a.id", $hid)->find();
		if (empty($host)) {
			return json(["status" => 400, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		if ($host["billingcycle"] != "onetime" && $host["billingcycle"] != "free" && $host["billingcycle"] != "hour" && $host["billingcycle"] != "day") {
			if ($host["nextduedate"] - $host["regdate"] < 3600) {
				if ($host["status"] == "Cancelled") {
					$host["status"] = "Paid";
				} else {
					$host["status"] = "Unpaid";
				}
			} else {
				$host["status"] = "Paid";
			}
		}
		$host["billingcycle_zh"] = config("billing_cycle")[$host["billingcycle"]];
		$data["host"] = $host;
		$uid = $this->request->uid;
		$currency_id = priorityCurrency($uid);
		$currency = getUserCurrency($uid);
		$data["currency"] = $currency;
		$product_model = new \app\common\model\ProductModel();
		$cycle = $product_model->getProductCycle($host["productid"], $currency_id, $hid, $host["billingcycle"], $host["amount"], "", "", "", $host["flag"]);
		$cycles = [];
		foreach ($cycle as $k => $v) {
			if (!in_array($v["billingcycle"], ["free", "ontrial"])) {
				$cycles[] = $v;
			}
		}
		$flag = getSaleProductUser($host["productid"], $host["uid"]);
		if (!$flag) {
			if ((new \app\common\logic\Renew())->unchangePrice($hid, $host["billingcycle"], $currency_id) != -1 && round((new \app\common\logic\Renew())->calculatedPrice($hid, $host["billingcycle"]), 2) != round($host["amount"], 2) && $host["promoid"] == 0) {
				$cycles = [];
			}
			if ($host["billingcycle"] != "ontrial" && !in_array($host["billingcycle"], array_column($cycles, "billingcycle"))) {
				$cycles[] = ["billingcycle" => $host["billingcycle"], "billingcycle_zh" => $host["billingcycle_zh"], "setup_fee" => 0, "price" => 0, "amount" => $host["amount"], "saleproducts" => 0];
			}
		}
		if (!empty($param["billingcycle"]) && in_array($param["billingcycle"], array_keys(config("billing_cycle")))) {
			if ($param["billingcycle"] != $host["billingcycle"]) {
				$renew = new \app\common\logic\Renew();
				if ((new \app\common\model\ProductModel())->checkProductPrice($host["productid"], $param["billingcycle"], $currency_id)) {
					$amount = $renew->calculatedPrice($hid, $param["billingcycle"], 1, $host["flag"]);
					$data["host"]["amount"] = bcsub($amount["price_cycle"], 0, 2);
					$data["host"]["saleproducts"] = bcsub($amount["price_sale_cycle"], 0, 2);
					$data["host"]["flags"] = $host["flag"];
				} else {
					$amount = $host["amount"];
					$data["host"]["amount"] = bcsub($amount, 0, 2);
					$data["host"]["saleproducts"] = bcsub(0, 0, 2);
					$data["host"]["flags"] = $host["flag"];
				}
				$data["host"]["billingcycle"] = $param["billingcycle"];
				$data["host"]["billingcycle_zh"] = config("billing_cycle")[$param["billingcycle"]];
			} else {
				$data["host"]["saleproducts"] = 0;
				$data["host"]["amount"] = $host["amount"];
				$data["host"]["flags"] = $host["flag"];
			}
		} else {
			if (!empty($cycles)) {
				if ($cycles[0]["billingcycle"] != $host["billingcycle"]) {
					$renew = new \app\common\logic\Renew();
					if ((new \app\common\model\ProductModel())->checkProductPrice($host["productid"], $cycles[0]["billingcycle"], $currency_id)) {
						$amount = $renew->calculatedPrice($hid, $cycles[0]["billingcycle"], 1, $host["flag"]);
						$data["host"]["amount"] = bcsub($amount["price_cycle"], 0, 2);
						$data["host"]["saleproducts"] = bcsub($amount["price_sale_cycle"], 0, 2);
						$data["host"]["flags"] = $host["flag"];
					} else {
						$amount = $host["amount"];
						$data["host"]["amount"] = bcsub($amount, 0, 2);
						$data["host"]["saleproducts"] = bcsub(0, 0, 2);
						$data["host"]["flags"] = $host["flag"];
					}
				} else {
					$data["host"]["saleproducts"] = 0;
					$data["host"]["amount"] = $host["amount"];
					$data["host"]["flags"] = $host["flag"];
				}
			}
		}
		$host["amount"] = sprintf("%.2f", number_format($data["host"]["amount"], 2));
		$data["cycle"] = $cycles;
		$pay_type = \think\Db::name("products")->where("id", $host["productid"])->value("pay_type");
		$pay_type = json_decode($pay_type, true);
		$data["pay_type"] = $pay_type;
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function renew()
	{
		$param = $this->request->param();
		$hid = $param["id"];
		$billingcycle = $param["billingcycle"];
		$renew = new \app\common\logic\Renew();
		$res = $renew->renew($hid, $billingcycle);
		$payment = \think\Db::name("host")->where("id", $hid)->value("payment");
		$gateway_list = gateway_list("gateways");
		$payment_name_list = array_column($gateway_list, "name");
		$payment = $payment ?: $payment_name_list[0];
		if ($res["status"] == 200 || $res["status"] == 1001) {
			$data["invoiceid"] = $res["data"]["invoice_id"];
			$data["payment"] = $payment;
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
		} else {
			return json($res);
		}
	}
	public function renewAuto()
	{
		$uid = request()->uid;
		$param = $this->request->param();
		$hid = $param["id"];
		$initiative_renew = intval($param["initiative_renew"]);
		\think\Db::name("host")->where("id", $hid)->update(["initiative_renew" => $initiative_renew]);
		$text = ["关闭", "开启"];
		active_log_final("设置产品-Host ID:{$hid} 的自动续费功能为: {$text[$initiative_renew]}", $uid, 2, $hid, 2);
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
	}
	public function renewBatchPage()
	{
		$param = $this->request->param();
		$host_ids = $param["ids"];
		$cycles_param = $param["billingcycles"] ?: [];
		if (empty($host_ids)) {
			return json(["status" => 400, "msg" => lang("Host_EMPTY")]);
		}
		if (!is_array($host_ids)) {
			return json(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$uid = request()->uid;
		$currency_id = priorityCurrency($uid);
		$currency = (new \app\common\logic\Currencies())->getCurrencies("id,prefix,suffix,code", $currency_id)[0];
		$hosts = \think\Db::name("host")->field("id")->where("uid", $uid)->whereIn("id", $host_ids)->select()->toArray();
		$host_ids = array_column($hosts, "id");
		$host_data = \think\Db::name("host")->alias("a")->field("a.productid,a.dedicatedip,a.uid,a.id,a.domainstatus,b.id as pid,b.name,b.pay_type,b.pay_method,a.nextduedate,a.billingcycle,a.amount,a.flag,b.groupid,a.promoid")->leftJoin("products b", "a.productid = b.id")->whereIn("a.id", $host_ids)->select()->toArray();
		$total = 0;
		$totalsale = 0;
		$host_data_filter = [];
		$billing_cycle = config("billing_cycle");
		foreach ($host_data as $k => $v) {
			if ($v["amount"] >= 0 && !in_array($v["billingcycle"], ["free", "onetime"]) && $v["pay_method"] == "prepayment" && in_array($v["domainstatus"], ["Active", "Suspended"])) {
				$nav_group = \think\Db::name("nav_group")->where("id", $v["groupid"])->find();
				$v["groupn"] = $nav_group;
				$renew_logic = new \app\common\logic\Renew();
				$hid = $v["id"];
				$amounts1 = $v["amount"];
				if ($cycles_param[$hid] && $cycles_param[$hid] != $v["billingcycle"]) {
					$billingcycle = $cycles_param[$hid];
					$s = $renew_logic->calculatedPrice($hid, $billingcycle, 1, $v["flag"]);
					$v["amount"] = $s["price_cycle"];
					$v["saleproducts"] = $s["price_sale_cycle"];
				} else {
					$billingcycle = $v["billingcycle"];
					$v["saleproducts"] = 0.0;
				}
				$pay_type = json_decode($v["pay_type"], true);
				$pid = $v["pid"];
				$product_model = new \app\common\model\ProductModel();
				$allow_billingcycle = $product_model->getProductCycle($pid, $currency_id, $hid, $billingcycle, $v["amount"], "", $v["billingcycle"], $amounts1, $v["flag"]);
				foreach ($allow_billingcycle as $kk => $vv) {
					if ($vv["billingcycle"] == "ontrial") {
						unset($allow_billingcycle[$kk]);
					}
					if (empty($cycles_param[$hid])) {
						if ($billingcycle == $vv["billingcycle"]) {
							$v["saleproducts"] = $vv["saleproducts"];
							$totalsale = bcadd($totalsale, $v["saleproducts"], 2);
							break;
						}
					} else {
						if ($cycles_param[$hid] == $vv["billingcycle"]) {
							$v["saleproducts"] = $vv["saleproducts"];
							$totalsale = bcadd($totalsale, $vv["saleproducts"], 2);
						}
					}
					$allow_billingcycle[$kk]["flags"] = $v["flag"];
				}
				if ($billingcycle == "ontrial") {
					$billingcycle = $allow_billingcycle[0]["billingcycle"] ?? "";
					$s = $renew_logic->calculatedPrice($hid, $billingcycle, 1, $v["flag"]);
					$v["amount"] = $s["price_cycle"];
					$v["saleproducts"] = $s["price_sale_cycle"];
				}
				$cycles = [];
				foreach ($allow_billingcycle as $kk => $vv) {
					if (!in_array($vv["billingcycle"], ["free", "ontrial"])) {
						$cycles[] = $vv;
					}
				}
				$flag = getSaleProductUser($v["pid"], $v["uid"]);
				if (!$flag) {
					if ((new \app\common\logic\Renew())->unchangePrice($hid, $billingcycle, $currency_id) != -1 && round((new \app\common\logic\Renew())->calculatedPrice($hid, $billingcycle), 2) != round($v["amount"], 2) && $v["promoid"] == 0) {
						$cycles = [];
					}
					if (!in_array($billingcycle, array_column($cycles, "billingcycle"))) {
						$cycles[] = ["billingcycle" => $billingcycle, "billingcycle_zh" => $billing_cycle[$billingcycle], "setup_fee" => 0, "price" => 0, "amount" => $v["amount"], "saleproducts" => 0];
					}
					$allow_billingcycle = $cycles;
				}
				if ($billingcycle == "onetime" || $billingcycle == "free") {
					$next_time = 0;
				} else {
					$next_time = getNextTime($billingcycle, $pay_type["pay_" . $billingcycle . "_cycle"], $v["nextduedate"], $pay_type["pay_ontrial_cycle_type"] ?: "day");
				}
				$total = bcadd($total, $v["amount"], 2);
				$v["billingcycle"] = $billingcycle;
				$v["nextduedate_renew"] = $next_time;
				$v["allow_billingcycle"] = $allow_billingcycle;
				unset($v["domainstatus"]);
				unset($v["pay_method"]);
				unset($v["pid"]);
				unset($v["pay_type"]);
				if ($v["flag"] == 1) {
					$v["flags"] = 1;
				} else {
					$v["flags"] = 0;
				}
				$host_data_filter[] = $v;
			}
		}
		$data = [];
		$data["currency"] = $currency;
		$data["hosts"] = $host_data_filter;
		$data["total"] = $total;
		$data["totalsale"] = $totalsale;
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function renewBatch()
	{
		$param = $this->request->param();
		$host_ids = $param["ids"];
		$billincycles = $param["billingcycles"];
		$renew_logci = new \app\common\logic\Renew();
		$res = $renew_logci->batchRenew($host_ids, $billincycles);
		if ($res["status"] == 200 || $res["status"] == 1001) {
			$uid = request()->uid;
			$payment = \think\Db::name("clients")->where("id", $uid)->value("defaultgateway");
			if (!$payment) {
				$gateway_list = gateway_list("gateways");
				$payment_name_list = array_column($gateway_list, "name");
				$payment = $payment_name_list[0];
			}
			$res["data"]["payment"] = $payment;
		}
		return json($res);
	}
	public function getCancelPage($id = 0)
	{
		$uid = $this->request->uid;
		$host_data = \think\Db::name("host")->field("h.productid,h.billingcycle,h.domainstatus,h.dedicatedip,p.name as productname,g.name as groupname")->alias("h")->leftJoin("products p", "p.id = h.productid")->leftJoin("product_groups g", "g.id=p.gid")->where("h.id", $id)->where("h.uid", $uid)->find();
		if (empty($host_data)) {
			return json(["status" => 400, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		if (!in_array($host_data["domainstatus"], ["Active", "Suspended"])) {
			return json(["status" => 400, "msg" => "Only activated or suspended products can apply for cancellation"]);
		}
		$info = "";
		$info .= "请求取消:" . $host_data["groupname"] . " - " . $host_data["productname"];
		if (!empty($host_data["dedicatedip"])) {
			$info .= "(" . $host_data["dedicatedip"] . ")";
		}
		$returndata = [];
		$host_cancel = \think\Db::name("cancel_requests")->field("type,reason")->where("relid", $id)->find();
		$returndata["cancel"] = $host_cancel ?? [];
		return json(["status" => 200, "data" => $returndata]);
	}
	public function postCancel($id = 0)
	{
		$param = $this->request->param();
		$uid = $this->request->uid;
		$type = $param["type"];
		$reason = $param["reason"];
		if (empty($id)) {
			return json(["status" => 400, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		if (!in_array($type, ["Immediate", "Endofbilling"])) {
			return json(["status" => 400, "msg" => "Type error"]);
		}
		if (empty($reason)) {
			return json(["status" => 400, "msg" => "You must submit the reasons for the withdrawal"]);
		}
		$host_data = \think\Db::name("host")->field("id,domainstatus,productid")->where("id", $id)->where("uid", $uid)->find();
		if (empty($host_data)) {
			return json(["status" => 400, "msg" => lang("THE_PRODUCT_WAS_NOT_FOUND")]);
		}
		if (!in_array($host_data["domainstatus"], ["Active", "Suspended"])) {
			return json(["status" => 400, "msg" => "Only activated or suspended products can apply for cancellation"]);
		}
		$product_data = \think\Db::name("products")->where("id", $host_data["productid"])->find();
		if (!$product_data["cancel_control"]) {
			return json(["status" => 400, "msg" => "The product cannot be cancelled"]);
		}
		$cancel_data = \think\Db::name("cancel_requests")->where("relid", $id)->where("delete_time", 0)->find();
		if (!empty($cancel_data)) {
			return json(["status" => 400, "msg" => "A cancellation request for this product already exists"]);
		}
		$udata = ["relid" => $id, "type" => $type ?? "Immediate", "reason" => $reason, "create_time" => time()];
		\think\facade\Hook::listen("cancellation_request", ["uid" => $uid, "relid" => $id, "reason" => $reason, "type" => $type]);
		$cancelid = \think\Db::name("cancel_requests")->insertGetId($udata);
		if (!empty($cancelid)) {
			active_log_final("产品 #Host ID:{$id}进行停用", $uid, 2, $id, 2);
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE")]);
		} else {
			return json(["status" => 400, "msg" => "Request failed, please try again or contact customer service"]);
		}
	}
	public function deleteCancel($id = 0)
	{
		$param = $this->request->param();
		\think\Db::name("cancel_requests")->where("relid", $id)->where("delete_time", 0)->delete();
		active_log_final("产品 #Host ID:{$id} 取消停用请求成功", $param["uid"], 2, $id, 2);
		return json(["status" => 200, "msg" => llang("SUCCESS MESSAGE")]);
	}
	public function upgradeConfigPage()
	{
		$re = $data = [];
		$param = $this->request->param();
		$hid = intval($param["id"]);
		if (!$hid) {
			return json(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$currency = intval($param["currencyid"]);
		$uid = request()->uid;
		if (!$uid) {
			return json(["status" => 400, "msg" => lang("ID_ERROR")]);
		}
		$currencyid = priorityCurrency($uid, $currency);
		$currency = (new \app\common\logic\Currencies())->getCurrencies("id,code,prefix,suffix", $currencyid)[0];
		$data["currency"] = $currency;
		$upgrade_logic = new \app\common\logic\Upgrade();
		try {
			$upgrade_logic->judgeUpgradeConfigError($hid);
		} catch (\Throwable $e) {
			return json(["status" => 400, "msg" => $e->getMessage()]);
		}
		$hosts = \think\Db::name("host")->alias("h")->field("pco.linkage_pid,pco.linkage_top_pid")->field("pco.option_name as option_name,pco.id as oid,pco.option_type,pcos.option_name as suboption_name,pcos.id as subid,hco.qty,h.billingcycle,h.flag,pri.*,pco.qty_stage,pco.unit")->leftJoin("host_config_options hco", "h.id = hco.relid")->leftJoin("product_config_options pco", "pco.id = hco.configid")->leftJoin("product_config_options_sub pcos", "pcos.id = hco.optionid")->leftJoin("pricing pri", "pri.relid = pcos.id")->where("h.id", $hid)->where("h.uid", $uid)->where("pri.currency", $currencyid)->where("pri.type", "configoptions")->where("pco.upgrade", 1)->select()->toArray();
		$cart = new \app\common\logic\Cart();
		$product = \think\Db::name("host")->field("productid,billingcycle")->where("id", $hid)->find();
		$cycle = $product["billingcycle"];
		$pid = $product["productid"];
		$configoptions_logic = new \app\common\logic\ConfigOptions();
		$configInfo = $configoptions_logic->getConfigInfo($pid);
		$allOption = $configoptions_logic->configShow($configInfo, $currencyid, $cycle, $uid, true);
		$hostFilters = $h = [];
		foreach ($hosts as $key => $host) {
			$option_name = explode("|", $host["option_name"]);
			if ($host["option_type"] != 5 && $host["option_type"] != 12 && $option_name[0] != "system_disk_size") {
				$h["oid"] = $host["oid"];
				$h["id"] = $host["oid"];
				$h["flag"] = $host["flag"];
				$h["option_name"] = $option_name[1] ? $option_name[1] : $host["option_name"];
				$h["option_type"] = $host["option_type"];
				$h["qty"] = $host["qty"];
				$h["suboption_name"] = explode("|", $host["suboption_name"])[1] ? explode("|", $host["suboption_name"])[1] : $host["suboption_name"];
				$h["suboption_name"] = implode(" ", explode("^", $h["suboption_name"]));
				$h["suboption_name_first"] = explode("|", $host["suboption_name"])[1] ? explode("|", $host["suboption_name"])[0] : $host["suboption_name"];
				if ($h["option_type"] == 3 && $h["qty"] == 0) {
					$h["subid"] = 0;
				} else {
					$h["subid"] = $host["subid"];
				}
				$h["fee"] = $host[$host["billingcycle"]];
				$h["setupfee"] = $host[$cart->changeCycleToupfee($host["billingcycle"])];
				$h["qty_minimum"] = 0;
				$h["qty_maximum"] = 0;
				$h["qty_stage"] = $host["qty_stage"];
				$h["unit"] = $host["unit"];
				$h["linkage_pid"] = $host["linkage_pid"];
				$h["linkage_top_pid"] = $host["linkage_top_pid"];
				$h["sub"] = [];
				foreach ($allOption as $vv) {
					if ($vv["id"] == $h["oid"]) {
						$h["qty_minimum"] = $vv["qty_minimum"];
						$h["qty_maximum"] = $vv["qty_maximum"];
						if (count($vv["sub"]) > 1 || judgeQuantity($host["option_type"]) || judgeYesNo($host["option_type"])) {
							$sub = $vv["sub"];
							if ($host["option_type"] == 13) {
								$subfilter = [];
								foreach ($sub as $v) {
									if (floatval($v["option_name_first"]) >= floatval($h["suboption_name_first"])) {
										$subfilter[] = $v;
									}
								}
							} else {
								if ($host["option_type"] == 14 || $host["option_type"] == 19) {
									$subfilter = [];
									$min = 0;
									foreach ($sub as &$v) {
										if ($h["subid"] == $v["id"]) {
											$min = $v["qty_minimum"];
											$v["qty_minimum"] = $h["qty"];
										}
									}
									foreach ($sub as $v2) {
										if ($min <= $v["qty_minimum"]) {
											$subfilter[] = $v2;
										}
									}
								} else {
									$subfilter = $sub;
								}
							}
							$h["sub"] = $subfilter;
						}
					}
				}
				if (!empty($h["sub"])) {
					$hostFilters[] = array_map(function ($v) {
						return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
					}, $h);
				}
			}
		}
		if (getEdition()) {
			$hostFilters = $this->handleLinkAgeLevel($hostFilters);
			$hostFilters = $this->handleTreeArr($hostFilters);
			$cids = \think\Db::name("product_config_options")->alias("a")->field("a.id")->leftJoin("product_config_links b", "b.gid = a.gid")->leftJoin("product_config_groups c", "a.gid = c.id")->where("b.pid", $pid)->order("a.order", "asc")->order("a.id", "asc")->column("a.id");
			$links = \think\Db::name("product_config_options_links")->whereIN("config_id", $cids)->where("type", "condition")->where("relation_id", 0)->withAttr("sub_id", function ($value) {
				return json_decode($value, true);
			})->select()->toArray();
			if (!empty($links[0])) {
				foreach ($links as &$link) {
					$result = \think\Db::name("product_config_options_links")->where("relation_id", $link["id"])->withAttr("sub_id", function ($value) {
						return json_decode($value, true);
					})->select()->toArray();
					$link["result"] = $result;
				}
			}
			if ($links) {
				$hostconfigoptions = \think\Db::name("host")->alias("h")->field("hco.qty,hco.configid,hco.optionid,pco.hidden,pco.upgrade")->leftJoin("host_config_options hco", "h.id = hco.relid")->leftJoin("product_config_options pco", "pco.id = hco.configid")->where("h.id", $hid)->where("h.uid", $uid)->select()->toArray();
				$links_config_id = array_column($links, "config_id");
				$links_config_id = array_unique($links_config_id);
				foreach ($hostconfigoptions as $k => $v) {
					if (in_array($v["configid"], $links_config_id) && ($v["hidden"] == 1 || $v["upgrade"] == 0)) {
						$host_config_options[$k]["configid"] = $v["configid"];
						$host_config_options[$k]["optionid"] = $v["optionid"];
						$host_config_options[$k]["qty"] = $v["qty"];
					}
				}
				$data["host_config_options"] = $host_config_options ? $host_config_options : [];
			}
			$data["links"] = $links ? $links : [];
		}
		$data["host"] = $hostFilters;
		$data["pid"] = $pid;
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function handleLinkAgeLevel($data)
	{
		$req = $this->request;
		if (!$data) {
			return $data;
		}
		$data = array_column($data, null, "id");
		$configOption = new \app\common\logic\ConfigOptions();
		foreach ($data as $k => $v) {
			if ($v["option_type"] != 20 || $v["linkage_pid"] != 0) {
				continue;
			}
			$req->cid = $cid = $v["id"];
			if ($v["subid"]) {
				$req->sub_id = $v["subid"];
			}
			$all_list = $configOption->webGetLinkAgeList($req);
			$linkAge = $configOption->webSetLinkAgeListDefaultVal($all_list, $req);
			$linkAge_ids = $linkAge ? array_column($linkAge, "id") : [];
			foreach ($linkAge as $val) {
				if (isset($data[$val["id"]])) {
					$data[$val["id"]]["checkSubId"] = $val["checkSubId"];
				}
			}
			$data = array_filter($data, function ($v) use($linkAge_ids, $cid) {
				if ($v["option_type"] != 20) {
					return true;
				}
				if ($v["linkage_top_pid"] != $cid) {
					return true;
				}
				if (in_array($v["id"], $linkAge_ids)) {
					return true;
				}
				return false;
			});
		}
		return $configOption->getTree($data);
	}
	public function handleTreeArr($data)
	{
		if (!$data) {
			return $data;
		}
		foreach ($data as $key => $val) {
			if (isset($val["son"]) && $val["son"]) {
				$data[$key]["son"] = changeTwoArr($val["son"]);
			}
		}
		return $data;
	}
	public function upgradeConfig()
	{
		try {
			$param = $this->request->param();
			$hid = intval($param["id"]);
			if (!$hid) {
				return json(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$upgrade_logic = new \app\common\logic\Upgrade();
			if (!$upgrade_logic->judgeUpgradeConfigError($hid)) {
				return json(["status" => 400, "msg" => "The current product cannot upgrade or downgrade configurable items"]);
			}
			$configoptions = $param["configoption"];
			if (!$upgrade_logic->checkChange($hid, $configoptions)) {
				return json(["status" => 400, "msg" => "Please select a configuration item"]);
			}
			$data["hid"] = $hid;
			$data["configoptions"] = $configoptions;
			if (!empty($configoptions) && is_array($configoptions)) {
				cache("upgrade_down_config_" . $hid, $data, 86400);
				$promo_code = $data["promo_code"] ?: "";
				$currencyid = intval($param["currencyid"]);
				$uid = request()->uid;
				$currencyid = priorityCurrency($uid, $currencyid);
				$re = $upgrade_logic->upgradeConfigCommon($hid, $configoptions, $currencyid, false, $promo_code);
				$return = $re["data"];
				return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $return]);
			} else {
				return json(["status" => 400, "msg" => "Configuration item is not an array"]);
			}
		} catch (\Throwable $e) {
			return json(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	public function upgradeConfigPromo()
	{
		try {
			$param = $this->request->param();
			$hid = intval($param["id"]);
			if (!$hid) {
				return json(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$upgrade_logic = new \app\common\logic\Upgrade();
			if (!$upgrade_logic->judgeUpgradeConfigError($hid)) {
				return json(["status" => 400, "msg" => "Invalid offer code"]);
			}
			$promo_code = $param["pormo_code"];
			$result = $upgrade_logic->checkUpgradePromo($promo_code, $hid);
			if ($result["status"] != 200) {
				$result["msg"] = "Invalid offer code";
				return json($result);
			}
			$data = cache("upgrade_down_config_" . $hid);
			if (!$data) {
				return json(["status" => 400, "msg" => "Invalid offer code"]);
			}
			$data["promo_code"] = $promo_code;
			cache("upgrade_down_config_" . $hid, $data, 86400);
			$currencyid = intval($data["currencyid"]);
			$configoptions = $data["configoptions"];
			$re = $upgrade_logic->upgradeConfigCommon($hid, $configoptions, $currencyid, false, $promo_code);
			$return = $re["data"];
			return json(["status" => 200, "msg" => "Application of promo code succeeded", "data" => $return]);
		} catch (\Throwable $e) {
			return json(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	public function upgradeConfigPromoRemove()
	{
		try {
			$param = $this->request->param();
			$hid = intval($param["id"]);
			if (!$hid) {
				return json(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$upgrade_logic = new \app\common\logic\Upgrade();
			if (!$upgrade_logic->judgeUpgradeConfigError($hid)) {
				return json(["status" => 400, "msg" => "The current product cannot upgrade or downgrade configurable items"]);
			}
			$data = cache("upgrade_down_config_" . $hid);
			if (!$data) {
				return json(["status" => 400, "msg" => "Please reselect the configuration"]);
			}
			\think\Db::name("host")->where("id", $hid)->update(["promoid" => 0]);
			$data["promo_code"] = "";
			cache("upgrade_down_config_" . $hid, $data, 86400);
			$currencyid = intval($data["currencyid"]);
			$configoptions = $data["configoptions"];
			$re = $upgrade_logic->upgradeConfigCommon($hid, $configoptions, $currencyid, false, "");
			$return = $re["data"];
			return json(["status" => 200, "msg" => "Successfully removed the promo code", "data" => $return]);
		} catch (\Throwable $e) {
			return json(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	public function upgradeConfigCheckout()
	{
		try {
			$param = $this->request->param();
			$hid = intval($param["id"]);
			if (!$hid) {
				return json(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$upgrade_logic = new \app\common\logic\Upgrade();
			if (!$upgrade_logic->judgeUpgradeConfigError($hid)) {
				return json(["status" => 400, "msg" => lang("The current product cannot upgrade or downgrade configurable items")]);
			}
			$data = cache("upgrade_down_config_" . $hid);
			if (!$data) {
				return json(["status" => 400, "msg" => "Please reselect the configuration"]);
			}
			$configoptions = $data["configoptions"];
			$promo_code = $data["promo_code"] ?: "";
			$currencyid = intval($data["currencyid"]);
			$uid = request()->uid;
			$currencyid = priorityCurrency($uid, $currencyid);
			$payment = \think\Db::name("host")->where("id", $hid)->value("payment");
			$desc = "";
			if (cache(md5(serialize($data) . "-" . $hid . "-" . get_client_ip()))) {
				return json(["status" => 400, "msg" => "Requests are too frequent"]);
			}
			cache(md5(serialize($data) . "-" . $hid . "-" . get_client_ip()), "upgrade config", 20);
			$productid = \think\Db::name("host")->where("id", $hid)->value("productid");
			$configoption_res = \think\Db::name("host_config_options")->where("relid", $hid)->select()->toArray();
			$configoption = [];
			foreach ($configoption_res as $k => $v) {
				$configoption[$v["configid"]] = $v["qty"] ?: $v["optionid"];
			}
			foreach ($configoptions as $ks => $vs) {
				$configoption[$ks] = $vs;
			}
			$senior = new \app\common\logic\SeniorConf();
			$msg = $senior->checkConf($productid, $configoption);
			if ($msg) {
				return json(["status" => 400, "msg" => $msg]);
			}
			$percent_value = $param["resource_percent_value"] ?: "";
			if (!empty($configoptions) && is_array($configoptions)) {
				$re = $upgrade_logic->upgradeConfigCommon($hid, $configoptions, $currencyid, false, $promo_code, $payment, true, $percent_value);
				return json($re);
			} else {
				return json(["status" => 400, "msg" => "Configuration item is not an array"]);
			}
		} catch (\Throwable $e) {
			return json(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	public function upgradeHostPage()
	{
		try {
			$re = $data = [];
			$re["status"] = 200;
			$re["msg"] = lang("SUCCESS MESSAGE");
			$param = $this->request->param();
			$hid = intval($param["id"]);
			if (!$hid) {
				return json(["status" => 400, "msg" => lang("ID_ERROR")]);
			}
			$upgrade_logic = new \app\common\logic\Upgrade();
			if (!$upgrade_logic->judgeUpgradeConfigError($hid, "product")) {
				return json(["status" => 400, "msg" => "The current product cannot be upgraded or downgraded"]);
			}
			$currency = intval($param["currencyid"]);
			$uid = request()->uid;
			$currency_id = priorityCurrency($uid, $currency);
			$oldhost = \think\Db::name("product_groups")->alias("pg")->field("p.name as host,h.domain,p.description,p.id as pid,h.uid,h.flag")->leftJoin("products p", "p.gid = pg.id")->leftJoin("host h", "h.productid = p.id")->where("h.id", $hid)->find();
			if ($oldhost["uid"] != $uid) {
				return json(["status" => 400, "msg" => "Illegal operation"]);
			}
			$oldhost = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $oldhost);
			$host = \think\Db::name("products")->alias("p")->field("p.id as pid,p.name as host,p.description")->leftJoin("product_groups pg", "p.gid = pg.id")->select()->toArray();
			$pids = $upgrade_logic->allowUpgradeProducts($oldhost["pid"]);
			$host_filter = [];
			foreach ($host as $k => $product) {
				if ($product["pid"] != $oldhost["pid"] && in_array($product["pid"], $pids)) {
					$product_model = new \app\common\model\ProductModel();
					$cycle = $product_model->getProductCycle($product["pid"], $currency_id, "", "", "", $uid, "", "", $host["flag"], 1);
					foreach ($cycle as &$v) {
						unset($v["billingcycle_zh"]);
						unset($v["amount"]);
					}
					$product["cycle"] = $cycle;
					$host_filter[] = $product;
				}
			}
			$currency = (new \app\common\logic\Currencies())->getCurrencies("id,code,prefix,suffix", $currency_id)[0];
			$data["currency"] = $currency;
			unset($oldhost["uid"]);
			unset($oldhost["flag"]);
			$data["old_host"] = $oldhost;
			$data["host"] = $host_filter;
			$re["data"] = $data;
			return json($re);
		} catch (\Throwable $e) {
			return json(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	public function upgradeHost()
	{
		try {
			$param = $this->request->param();
			$hid = intval($param["id"]);
			if (!$hid) {
				return json(["status" => 400, "msg" => "ID error"]);
			}
			$new_pid = intval($param["product_id"]);
			if (!$new_pid) {
				return json(["status" => 400, "msg" => "Please select an item"]);
			}
			$upgrade_logic = new \app\common\logic\Upgrade();
			if (!$upgrade_logic->judgeUpgradeConfigError($hid, "product")) {
				return json(["status" => 400, "msg" => "Current product cannot be upgraded or downgraded"]);
			}
			$currency_id = intval($param["currencyid"]);
			$billingcycle = $param["billingcycle"] ?: "";
			$uid = request()->uid;
			$host = \think\Db::name("host")->field("uid")->where("id", $hid)->find();
			if ($host["uid"] != $uid) {
				return json(["status" => 400, "msg" => "Illegal operation"]);
			}
			$data = [];
			$data["hid"] = $hid;
			$data["pid"] = $new_pid;
			$data["billingcycle"] = $billingcycle;
			$data["currencyid"] = $currency_id;
			cache("upgrade_down_product_" . $hid, $data, 86400);
			$promo_code = $param["promo_code"] ?: "";
			$re = $upgrade_logic->upgradeProductCommon($hid, $new_pid, $billingcycle, $currency_id, $promo_code);
			$return = $re["data"];
			unset($return["des"]);
			unset($return["amount"]);
			unset($return["discount"]);
			unset($return["flag"]);
			unset($return["has_renew"]);
			unset($return["old_host"]["flag"]);
			unset($return["billingcycle_zh"]);
			return json(["status" => 200, "msg" => "Success message", "data" => $return]);
		} catch (\Throwable $e) {
			return json(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	public function upgradeHostAddPromo()
	{
		try {
			$param = $this->request->param();
			$hid = intval($param["id"]);
			$data = cache("upgrade_down_product_" . $hid);
			if (!$hid) {
				return json(["status" => 400, "msg" => "ID error"]);
			}
			$uid = request()->uid;
			$host = \think\Db::name("host")->field("uid")->where("id", $hid)->find();
			if ($host["uid"] != $uid) {
				return json(["status" => 400, "msg" => "Illegal operation"]);
			}
			$upgrade_logic = new \app\common\logic\Upgrade();
			if (!$upgrade_logic->judgeUpgradeConfigError($hid, "product")) {
				return json(["status" => 400, "msg" => "Current product cannot be upgraded or downgraded"]);
			}
			$promo_code = $param["promo_code"] ?: "";
			$new_pid = $data["pid"];
			$upgrade_type = "product";
			$new_billingcycle = $data["billingcycle"];
			$result = $upgrade_logic->checkUpgradePromo($promo_code, $hid, $new_pid, $new_billingcycle, $upgrade_type);
			if ($result["status"] != 200) {
				return json($result);
			}
			$data["promo_code"] = $promo_code;
			cache("upgrade_down_product_" . $hid, $data, 86400);
			return json(["status" => 200, "msg" => "Promo code applied successfully"]);
		} catch (\Throwable $e) {
			return json(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	public function upgradeHostRemovePromo()
	{
		try {
			$param = $this->request->param();
			$hid = intval($param["id"]);
			$data = cache("upgrade_down_product_" . $hid);
			if (!$hid) {
				return json(["status" => 400, "msg" => "ID error"]);
			}
			$uid = request()->uid;
			$host = \think\Db::name("host")->field("uid")->where("id", $hid)->find();
			if ($host["uid"] != $uid) {
				return json(["status" => 400, "msg" => "Illegal operation"]);
			}
			$upgrade_logic = new \app\common\logic\Upgrade();
			if (!$upgrade_logic->judgeUpgradeConfigError($hid, "product")) {
				return json(["status" => 400, "msg" => "Current product cannot be upgraded or downgraded"]);
			}
			if (!$data) {
				return json(["status" => 400, "msg" => "Please select a new product"]);
			}
			\think\Db::name("host")->where("id", $hid)->update(["promoid" => 0]);
			$data["promo_code"] = "";
			cache("upgrade_down_product_" . $hid, $data, 86400);
			return json(["status" => 200, "msg" => "Successfully removed promo code"]);
		} catch (\Throwable $e) {
			return json(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	public function upgradeHostCheckout()
	{
		$param = $this->request->param();
		$hid = intval($param["id"]);
		if (!$hid) {
			return json(["status" => 400, "msg" => "ID error"]);
		}
		$upgrade_logic = new \app\common\logic\Upgrade();
		if (!$upgrade_logic->judgeUpgradeConfigError($hid, "product")) {
			return json(["status" => 400, "msg" => "Current product cannot be upgraded or downgraded"]);
		}
		$uid = request()->uid;
		$host = \think\Db::name("host")->field("uid")->where("id", $hid)->find();
		if ($host["uid"] != $uid) {
			return json(["status" => 400, "msg" => "Illegal operation"]);
		}
		$payment = $param["payment"] ?: "";
		$data = cache("upgrade_down_product_" . $hid);
		if (!$data) {
			return json(["status" => 400, "msg" => "Please select a new product"]);
		}
		if (cache(md5(serialize($data) . "-" . $hid . "-" . get_client_ip()))) {
			return json(["status" => 400, "msg" => "Requests are too frequent"]);
		}
		cache(md5(serialize($data) . "-" . $hid . "-" . get_client_ip()), "upgrade", 20);
		$newpid = $data["pid"];
		$billingcycle = $data["billingcycle"];
		$promocode = $data["promo_code"] ?: "";
		$result = $upgrade_logic->upgradeProductCommon($hid, $newpid, $billingcycle, "", $promocode, $payment, true);
		return json($result);
	}
	/**
	 * @title 获取主机支持的server模块接口
	 * @description 获取主机支持的server模块接口
	 * return
	 * @author xiong
	 * @url /hosts/:id/module
	 * @method GET
	 */
	public function module()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$uid = $param["uid"];
		$host_data = \think\Db::name("host")->field("p.cancel_control")->field("o.create_time as ocreate_time,o.amount as order_amount")->field("h.id,h.orderid,h.initiative_renew,h.productid,h.serverid,h.regdate,h.domain,h.payment,p.groupid,h.promoid,
                h.firstpaymentamount,h.amount,h.billingcycle,h.nextduedate,h.nextinvoicedate,
                h.dedicatedip,h.assignedips,h.domainstatus,h.username,h.password,h.suspendreason,p.id as pid,               h.auto_terminate_end_cycle,h.auto_terminate_reason,h.bwusage,h.bwlimit,h.os,h.remark,h.dcimid,h.dcim_area,h.dcim_os,h.port,p.type,p.name as productname,p.pay_method as payment_type,p.config_options_upgrade,p.api_type,p.zjmf_api_id,p.upstream_price_type,p.upstream_price_value,p.upper_reaches_id,p.config_option1,p.password password_rule,g.name as groupname,o.ordernum")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("product_groups g", "g.id=p.gid")->leftJoin("orders o", "o.id=h.orderid")->where("h.id", $host_id)->find();
		if ($host_data["type"] == "ssl") {
			return json(["status" => 400, "data" => "The host has no server module interface"]);
		}
		$returndata["module_button"] = ["control" => [], "console" => []];
		$returndata["module_client_area"] = [];
		$returndata["module_chart"] = [];
		$returndata["module_client_main_area"] = [];
		$returndata["module_power_status"] = false;
		$returndata["reinstall_random_port"] = false;
		$returndata["reinstall_format_data_disk"] = false;
		$upstream_data = [];
		if ($host_data["api_type"] == "zjmf_api") {
			$returndata["host_data"]["serverid"] = $returndata["host_data"]["zjmf_api_id"];
			$zjmf_api_params = ["host_id" => $host_data["dcimid"]];
			if ($request->nat) {
				$zjmf_api_params["nat"] = true;
			}
			$upstream_data = zjmfCurl($host_data["zjmf_api_id"], "/host/header", $zjmf_api_params, 30, "GET");
			if ($upstream_data["status"] == 200) {
				$upstream_data = $upstream_data["data"];
			} else {
				$upstream_data = [];
			}
			if (!$returndata["host_data"]["dedicatedip"] && $upstream_data["host_data"]["dedicatedip"]) {
				$sync_info = ["dedicatedip" => $upstream_data["host_data"]["dedicatedip"], "assignedips" => implode(",", $upstream_data["host_data"]["assignedips"]) ?? "", "domain" => $upstream_data["host_data"]["domain"] ?? "", "username" => $upstream_data["host_data"]["username"] ?? "", "password" => cmf_encrypt($upstream_data["host_data"]["password"]), "port" => \intval($upstream_data["host_data"]["port"]), "os" => $upstream_data["host_data"]["os"]];
				\think\Db::name("host")->where("id", $host_id)->update($sync_info);
				$sync_info["assignedips"] = !empty($sync_info["assignedips"]) ? explode(",", $sync_info["assignedips"]) : [];
				$sync_info["password"] = cmf_decrypt($sync_info["password"]);
				$sync_info["ip_num"] = count($sync_info["assignedips"]);
				$returndata["host_data"] = array_merge($returndata["host_data"], $sync_info);
			}
			$returndata["module_button"]["control"] = $upstream_data["module_button"]["control"] ?: [];
			$returndata["module_button"]["console"] = $upstream_data["module_button"]["console"] ?: [];
			$returndata["module_client_area"] = $upstream_data["module_client_area"] ?: [];
			$returndata["module_chart"] = $upstream_data["module_chart"] ?: [];
			$returndata["module_client_main_area"] = $upstream_data["module_client_main_area"] ?: [];
			$returndata["dcimcloud"]["nat_acl"] = $upstream_data["dcimcloud"]["nat_acl"] ?: "";
			$returndata["dcimcloud"]["nat_web"] = $upstream_data["dcimcloud"]["nat_web"] ?: "";
			$returndata["module_power_status"] = \boolval($upstream_data["module_power_status"]);
			$returndata["reinstall_random_port"] = \boolval($upstream_data["reinstall_random_port"]);
			$returndata["reinstall_format_data_disk"] = \boolval($upstream_data["reinstall_format_data_disk"]);
			if ($zjmf_api_params["nat"]) {
				return json(["status" => 200, "data" => $returndata]);
			}
		} elseif ($host_data["api_type"] == "manual") {
			$UpperReaches = new \app\common\logic\UpperReaches();
			$returndata["module_power_status"] = $UpperReaches->modulePowerStatus($host_id);
			$returndata["module_button"] = $UpperReaches->moduleClientButton($host_id);
			$upper_reaches = \think\Db::name("zjmf_finance_api")->where("id", $host_data["upper_reaches_id"])->find();
			$returndata["manual"] = ["id" => $host_data["upper_reaches_id"], "name" => $upper_reaches["name"]];
			$upper_reaches_res = \think\Db::name("upper_reaches_res")->where("hid", $host_id)->find();
			$returndata["host_data"]["upper_reaches_res"] = $upper_reaches_res["id"] ?? "";
			$returndata["host_data"]["upper_reaches_control_mode"] = $upper_reaches_res["control_mode"] ?? "";
		} elseif ($host_data["api_type"] == "resource") {
			if (function_exists("resourceCurl")) {
				$post_data = [];
				$post_data["host_id"] = $host_data["dcimid"];
				$upstream_data = resourceCurl($host_data["productid"], "/host/header", $post_data, 30, "GET");
				if ($upstream_data["status"] == 200) {
					$upstream_data = $upstream_data["data"];
				} else {
					$upstream_data = [];
				}
				if (!$returndata["host_data"]["dedicatedip"] && $upstream_data["host_data"]["dedicatedip"]) {
					$sync_info = ["dedicatedip" => $upstream_data["host_data"]["dedicatedip"], "assignedips" => implode(",", $upstream_data["host_data"]["assignedips"]) ?? "", "domain" => $upstream_data["host_data"]["domain"] ?? "", "username" => $upstream_data["host_data"]["username"] ?? "", "password" => cmf_encrypt($upstream_data["host_data"]["password"]), "port" => \intval($upstream_data["host_data"]["port"]), "os" => $upstream_data["host_data"]["os"]];
					\think\Db::name("host")->where("id", $host_id)->update($sync_info);
					$sync_info["assignedips"] = !empty($sync_info["assignedips"]) ? explode(",", $sync_info["assignedips"]) : [];
					$sync_info["password"] = cmf_decrypt($sync_info["password"]);
					$sync_info["ip_num"] = count($sync_info["assignedips"]);
					$returndata["host_data"] = array_merge($returndata["host_data"], $sync_info);
				}
				$returndata["module_button"]["control"] = $upstream_data["module_button"]["control"] ?: [];
				$returndata["module_button"]["console"] = $upstream_data["module_button"]["console"] ?: [];
				$returndata["module_client_area"] = $upstream_data["module_client_area"] ?: [];
				$returndata["module_chart"] = $upstream_data["module_chart"] ?: [];
				$returndata["module_client_main_area"] = $upstream_data["module_client_main_area"] ?: [];
				$returndata["dcimcloud"]["nat_acl"] = $upstream_data["dcimcloud"]["nat_acl"] ?: "";
				$returndata["dcimcloud"]["nat_web"] = $upstream_data["dcimcloud"]["nat_web"] ?: "";
				$returndata["module_power_status"] = \boolval($upstream_data["module_power_status"]);
				$returndata["reinstall_random_port"] = \boolval($upstream_data["reinstall_random_port"]);
				$returndata["reinstall_format_data_disk"] = \boolval($upstream_data["reinstall_format_data_disk"]);
			}
		} else {
			$provision_logic = new \app\common\logic\Provision();
			if ($host_data["domainstatus"] == "Active") {
				if ($host_data["type"] == "dcimcloud") {
					$dcimcloud = new \app\common\logic\DcimCloud();
					$returndata["module_button"] = $dcimcloud->moduleClientButton($host_data["dcimid"]);
					$returndata["module_client_area"] = $dcimcloud->moduleClientArea($host_id);
					$returndata["module_chart"] = $dcimcloud->chart($host_data["dcimid"], $host_id);
					$returndata["module_power_status"] = true;
					$returndata["reinstall_random_port"] = $reinstall_random_port;
					$returndata["reinstall_format_data_disk"] = $reinstall_format_data_disk;
					if ($request->nat) {
						$nat_info = $dcimcloud->getNatInfo($host_id);
						$returndata["dcimcloud"]["nat_acl"] = $nat_info["nat_acl"] ?: "";
						$returndata["dcimcloud"]["nat_web"] = $nat_info["nat_web"] ?: "";
						return json(["status" => 200, "data" => $returndata]);
					} else {
						if (!$request->tplcloud) {
							$nat_info = $dcimcloud->getNatInfo($host_id);
							$returndata["dcimcloud"]["nat_acl"] = $nat_info["nat_acl"] ?: "";
							$returndata["dcimcloud"]["nat_web"] = $nat_info["nat_web"] ?: "";
						}
					}
				} elseif ($host_data["type"] == "dcim") {
					if ($host_data["config_option1"] == "bms") {
						$dcim = new \app\common\logic\Dcim();
						$returndata["module_button"] = $dcim->moduleClientButton($host_data["dcimid"]);
						$returndata["module_client_area"] = $dcim->moduleClientArea($host_id);
						$returndata["module_power_status"] = true;
					} else {
						$returndata["module_power_status"] = true;
					}
				} else {
					$module_button = $provision_logic->clientButtonOutput($host_id);
					$module_client_area = $provision_logic->clientArea($host_id);
					$returndata["module_button"] = $module_button;
					$returndata["module_client_area"] = $module_client_area;
					$returndata["module_chart"] = $provision_logic->chart($host_id);
					$returndata["module_power_status"] = $provision_logic->checkDefineFunc($host_id, "Status");
					$returndata["module_client_main_area"] = $provision_logic->clientAreaMainOutput($host_id);
				}
			}
		}
		$returndata["dcim"]["flowpacket"] = [];
		$returndata["dcim"]["flow_packet_use_list"] = [];
		if ($host_data["api_type"] == "zjmf_api") {
			$returndata["dcim"]["flowpacket"] = $upstream_data["dcim"]["flowpacket"] ?: [];
			$returndata["host_data"]["bwlimit"] = \intval($upstream_data["host_data"]["bwlimit"]);
			$returndata["host_data"]["bwusage"] = \floatval($upstream_data["host_data"]["bwusage"]);
			if ($host_data["upstream_price_type"] == "percent") {
				foreach ($returndata["dcim"]["flowpacket"] as $k => $v) {
					$returndata["dcim"]["flowpacket"][$k]["price"] = round($v["price"] * $host_data["upstream_price_value"] / 100, 2);
				}
			}
			$returndata["dcim"]["flow_packet_use_list"] = $upstream_data["dcim"]["flow_packet_use_list"] ?: [];
			if ($host_data["type"] == "dcim" && $host_data["config_option1"] != "bms") {
				$returndata["dcim"]["auth"] = $upstream_data["dcim"]["auth"] ?? ["bmc" => "off", "crack_pass" => "off", "ikvm" => "off", "kvm" => "off", "novnc" => "off", "off" => "off", "on" => "off", "reboot" => "off", "reinstall" => "off", "rescue" => "off", "traffic" => "off"];
				$returndata["dcim"]["svg"] = $upstream_data["dcim"]["svg"] ?? "";
				$returndata["host_data"]["os_ostype"] = $upstream_data["host_data"]["os_ostype"] ?? "";
				$returndata["host_data"]["os_osname"] = $upstream_data["host_data"]["os_osname"] ?? "";
				$returndata["host_data"]["disk_num"] = $upstream_data["host_data"]["disk_num"] ?? 1;
			}
		} else {
			if ($host_data["bwlimit"] > 0) {
				$flowpacket = \think\Db::name("dcim_flow_packet")->field("id,name,capacity,price,sale_times,stock")->where("status", 1)->whereRaw("FIND_IN_SET('{$host_data["productid"]}', allow_products)")->select()->toArray();
				if (!empty($flowpacket)) {
					foreach ($flowpacket as $k => $v) {
						$flowpacket[$k]["leave"] = 1;
						if ($v["stock"] > 0 && $v["sale_times"] >= $v["stock"]) {
							$flowpacket[$k]["leave"] = 0;
						}
						unset($flowpacket[$k]["sale_times"]);
						unset($flowpacket[$k]["stock"]);
					}
					$returndata["dcim"]["flowpacket"] = $flowpacket;
				}
			}
			if ($host_data["type"] == "dcim" && $host_data["config_option1"] != "bms") {
				$server = \think\Db::name("servers")->alias("a")->field("b.*")->leftJoin("dcim_servers b", "a.id=b.serverid")->where("a.id", $host_data["serverid"])->find();
				$returndata["dcim"]["auth"] = json_decode($server["auth"], true);
				if ($host_data["bwlimit"] > 0) {
					$returndata["dcim"]["flow_packet_use_list"] = get_dcim_traffic_usage_table($host_id, $uid, $server["bill_type"], $host_data["bwusage"], $host_data["bwlimit"]);
				}
				$os = json_decode($server["os"], true);
				$returndata["dcim"]["os_group"] = $os["group"];
				$returndata["dcim"]["os"] = $os["os"];
				if (!empty($host_data["dcim_area"])) {
					$area = json_decode($server["area"], true);
					foreach ($area as $v) {
						if ($v["id"] == $host_data["dcim_area"]) {
							$returndata["dcim"]["area_code"] = $v["area"];
							$returndata["dcim"]["area_name"] = $v["name"] ?? "";
							break;
						}
					}
				} else {
					$returndata["dcim"]["area_code"] = "";
					$returndata["dcim"]["area_name"] = "";
				}
				$os_info = get_dcim_os_info($host_data["dcim_os"], $os["os"], $os["group"]);
				$returndata["host_data"]["os_ostype"] = $os_info["ostype"] ?? "";
				$returndata["host_data"]["os_osname"] = $os_info["os_name"] ?? "";
				$returndata["host_data"]["disk_num"] = 1;
				$returndata["dcim"]["svg"] = $os_info["svg"];
			} else {
				if ($host_data["bwlimit"] > 0) {
					if ($host_data["type"] == "dcimcloud") {
						$traffic_bill_type_config_options = (new \app\common\model\HostModel())->getConfigOption($host_id, "traffic_bill_type");
						$returndata["dcim"]["flow_packet_use_list"] = get_dcim_traffic_usage_table($host_id, $uid, $traffic_bill_type_config_options["sub_option_arr"][0], $host_data["bwusage"], $host_data["bwlimit"]);
					} else {
						$returndata["dcim"]["flow_packet_use_list"] = get_dcim_traffic_usage_table($host_id, $uid, "", $host_data["bwusage"], $host_data["bwlimit"]);
					}
				}
			}
		}
		$module = [];
		if ($host_data["type"] == "dcim") {
			foreach ($returndata["dcim"]["auth"] as $k => $v) {
				$custom = [];
				if ($k == "on") {
					$custom["type"] = "default";
					$custom["function"] = $k;
					$custom["name"] = "开机";
				} elseif ($k == "off") {
					$custom["type"] = "default";
					$custom["function"] = $k;
					$custom["name"] = "关机";
				} elseif ($k == "reboot") {
					$custom["type"] = "default";
					$custom["function"] = $k;
					$custom["name"] = "重启";
				} elseif ($k == "bmc") {
					$custom["type"] = "default";
					$custom["function"] = $k;
					$custom["name"] = "重置bmc";
				} elseif ($k == "kvm") {
					$custom["type"] = "default";
					$custom["function"] = $k;
					$custom["name"] = "kvm";
				} elseif ($k == "ikvm") {
					$custom["type"] = "default";
					$custom["function"] = $k;
					$custom["name"] = "ikvm";
				} elseif ($k == "novnc") {
					$custom["type"] = "default";
					$custom["function"] = "vnc";
					$custom["name"] = "VNC";
				} elseif ($k == "crack_pass") {
					$custom["type"] = "default";
					$custom["function"] = "repassword";
					$custom["name"] = "重置密码";
				} elseif ($k == "reinstall") {
					$custom["type"] = "default";
					$custom["function"] = $k;
					$custom["name"] = "重装系统";
				} elseif ($k == "rescue") {
					$custom["type"] = "rescue";
					$custom["function"] = $k;
					$custom["name"] = "救援系统";
				} elseif ($k == "traffic") {
					$custom["type"] = "default";
					$custom["function"] = "module_chart";
					$custom["name"] = "图表";
				}
				if ($custom) {
					$module[] = $custom;
				}
			}
			if ($returndata["module_power_status"]) {
				$default = [];
				$default["type"] = "default";
				$default["function"] = "status";
				$default["name"] = "服务器电源状态";
				$module[] = $default;
			}
		} else {
			$module_button = $returndata["module_button"];
			$module_client_area = $returndata["module_client_area"];
			$module_chart = $returndata["module_chart"];
			$control_console = array_merge($module_button["control"], $module_button["console"]);
			foreach ($control_console as $v) {
				if ($v["func"] == "crack_pass") {
					$v["func"] = "repassword";
				}
				$v["function"] = $v["func"];
				unset($v["func"]);
				$module[] = $v;
			}
			foreach ($module_client_area as $v) {
				$custom["type"] = "custom";
				$custom["function"] = $v["key"];
				$custom["name"] = $v["name"];
				$module[] = $custom;
			}
			if ($module_chart) {
				$default = [];
				$default["type"] = "default";
				$default["function"] = "module_chart";
				$default["name"] = "图表";
				$default["select"] = $module_chart;
				$module[] = $default;
			}
			if ($returndata["module_power_status"]) {
				$default = [];
				$default["type"] = "default";
				$default["function"] = "status";
				$default["name"] = "服务器电源状态";
				$module[] = $default;
			}
		}
		return json(["status" => 200, "data" => $module]);
	}
	/**
	 * @title 购买重装次数生成账单
	 * @description 购买重装次数生成账单
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/reinstall_buy
	 * @method POST
	 */
	public function reinstallBuy()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$uid = $this->request->uid;
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.dcimid,a.serverid,a.reinstall_info,b.type,b.api_type,b.zjmf_api_id,b.upstream_price_type,b.upstream_price_value,b.config_option1,c.reinstall_times,c.buy_times,c.reinstall_price,c.auth")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", $uid)->where("a.id", $host_id)->whereIn("b.type", "dcim,dcimcloud")->where("a.domainstatus", "Active")->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = "illegal parameter";
			return json($result);
		}
		$buy = false;
		if ($host["api_type"] == "zjmf_api") {
			$res = zjmfCurl($host["zjmf_api_id"], "/dcim/check_reinstall", ["id" => $host["dcimid"]]);
			if ($res["status"] == 400 && $res["price"] > 0) {
				$buy = true;
				if ($host["upstream_price_type"] == "percent") {
					$host["reinstall_price"] = round($res["price"] * $host["upstream_price_value"] / 100, 2);
				} else {
					$host["reinstall_price"] = $res["price"];
				}
			} else {
				$result["status"] = 400;
				$result["msg"] = "Cannot purchase times";
				return json($result);
			}
		} else {
			if ($host["buy_times"] == 0 || $host["reinstall_price"] < 0.01) {
				$result["status"] = 400;
				$result["msg"] = "Cannot purchase times";
				return json($result);
			}
			if ($host["reinstall_times"] == 0) {
				$result["status"] = 400;
				$result["msg"] = "No purchase required";
				return json($result);
			}
			$reinstall_info = json_decode($host["reinstall_info"], true);
			$num = $reinstall_info["num"] ?? 0;
			if (empty($reinstall_info) || strtotime("this week Monday") > strtotime($reinstall_info["date"])) {
				$num = 0;
			}
			if ($host["buy_times"] == 1) {
				$buy_times = get_buy_reinstall_times($uid, $host_id);
			} else {
				$buy_times = 0;
			}
			$buy = $host["reinstall_times"] > 0 && $host["reinstall_times"] + $buy_times <= $num;
		}
		if ($buy) {
			$invoice_data = ["uid" => $uid, "price" => $host["reinstall_price"], "relid" => $host_id, "description" => "购买重装次数", "type" => "zjmf_reinstall_times"];
			$r = add_custom_invoice($invoice_data);
			if ($r["status"] != 200) {
				return json($r);
			}
			$invoiceid = $r["invoiceid"];
			$data = ["uid" => $uid, "relid" => 0, "name" => "重装次数", "price" => $host["reinstall_price"], "status" => 0, "create_time" => time(), "capacity" => 1, "invoiceid" => $invoiceid, "type" => "reinstall_times", "hostid" => $host_id];
			$record = \think\Db::name("dcim_buy_record")->insertGetId($data);
			if ($record) {
				active_log_final(sprintf($this->lang["Dcim_home_buyReinstallTimes"], $host_id, $invoiceid), $uid, 2, $host_id, 2);
				$result["status"] = 200;
				$result["msg"] = "Generated and paid the bill successfully, please go to pay";
				$result["data"]["invoiceid"] = $invoiceid;
			} else {
				$result["status"] = 400;
				$result["msg"] = "The number of purchases and reloads is wrong, please contact customer service, do not pay the generated bill, the ID is：" . $invoiceid;
			}
		} else {
			$result["status"] = 400;
			$result["msg"] = "No purchase required";
		}
		return json($result);
	}
	/**
	 * @title 获取状态
	 * @description 获取状态
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/status
	 * @method PUT
	 */
	public function status()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$type = $param["type"];
		if ($type == "host") {
			$host = new \app\common\logic\Host();
			$result = $host->status($host_id);
			return json($result);
		} elseif ($type == "reinstall") {
			$host = \think\Db::name("host")->alias("a")->field("a.domainstatus")->leftJoin("products b", "a.productid=b.id")->leftJoin("dcim_servers c", "a.serverid=c.serverid")->where("a.uid", $this->request->uid)->where("a.id", $host_id)->where("b.type", "dcim")->where("domainstatus", "Active")->find();
			if (empty($host)) {
				$result["status"] = 400;
				$result["msg"] = lang("ID_ERROR");
				return json($result);
			}
			$dcim = new \app\common\logic\Dcim();
			$result = $dcim->reinstallStatus($host_id);
			if ($result["data"]["windows_finish"] === false) {
				$result_data["reinstall"] = $result["data"]["reinstall_msg"];
				$result_data["step"] = $result["data"]["step"];
				unset($result["data"]);
				unset($result["msg"]);
				$result["data"] = $result_data;
			} else {
				if ($result["data"]["last_result"]) {
					$result["msg"] = $result["data"]["last_result"]["act"] . $result["data"]["last_result"]["msg"];
					unset($result["data"]);
				}
			}
			return json($result);
		} else {
			$result["status"] = 400;
			$result["msg"] = "Parameter error";
			return json($result);
		}
	}
	/**
	 * @title 重置密码
	 * @description 重置密码
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/repassword
	 * @method PUT
	 */
	public function repassword()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$password = $param["password"];
		$host_data = \think\Db::name("host")->field("p.type")->alias("h")->leftJoin("products p", "p.id=h.productid")->where("h.id", $host_id)->where("h.uid", $this->request->uid)->find();
		if ($host_data["type"] == "dcim") {
			$check = check_dcim_auth($host_id, $this->request->uid, "crack_pass");
			if ($check["status"] != 200) {
				return json($check);
			}
			$data = ["crack_password" => $params["password"], "other_user" => intval($params["other_user"]), "user" => $params["user"] ?? "", "action" => $params["action"] ?? ""];
			$dcim = new \app\common\logic\Dcim();
			$result = $dcim->crackPass($host_id, $data);
			return json($result);
		} else {
			$host = new \app\common\logic\Host();
			$result = $host->crackPass($host_id, $password);
			return json($result);
		}
	}
	/**
	 * @title 获取重装系统
	 * @description 获取重装系统
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/reinstall
	 * @method GET
	 */
	public function getReinstall()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$host_data = \think\Db::name("host")->where("id", $host_id)->find();
		$os_config_option_id = \think\Db::name("product_config_links")->alias("a")->leftJoin("product_config_options b", "a.gid=b.gid")->where("a.pid", $host_data["productid"])->where("b.option_type", 5)->value("b.id");
		$sub = \think\Db::name("product_config_options_sub")->field("id,option_name")->where("config_id", $os_config_option_id)->where("hidden", 0)->order("sort_order ASC")->order("id asc")->select()->toArray();
		$cloud_os = [];
		$cloud_os_group = [];
		$data_config_id = array_column($sub, "id");
		foreach ($sub as $v) {
			if (!in_array($v["id"], $data_config_id)) {
				continue;
			}
			$arr = explode("|", $v["option_name"]);
			if (strpos($arr[1], "^") !== false) {
				$arr2 = explode("^", $arr[1]);
				if (empty($arr2[0]) || empty($arr2[1])) {
					continue;
				}
				if (!in_array($arr2[0], $cloud_os_group)) {
					$cloud_os_group[] = $arr2[0];
				}
				$cloud_os[] = ["os_id" => $v["id"], "name" => $arr2[1], "group_name" => $arr2[0]];
			} else {
				$cloud_os[] = ["os_id" => $v["id"], "name" => $arr[1], "group_name" => ""];
			}
		}
		if (!empty($cloud_os_group)) {
			foreach ($cloud_os_group as $k => $v) {
				$cloud_os_group[$k] = ["group_name" => $v, "img" => svgBase64EncodeImage(CMF_ROOT . "public/upload/common/system/" . getOsSvg($v) . ".svg")];
			}
			foreach ($cloud_os as $k => $v) {
				if (empty($v["group_name"])) {
					unset($cloud_os[$k]);
				}
			}
			$cloud_os = array_values($cloud_os);
		}
		$returndata["os"] = $cloud_os;
		$returndata["os_group"] = $cloud_os_group;
		return json(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 重装系统
	 * @description 重装系统
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/reinstall
	 * @method PUT
	 */
	public function reinstall()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$os_id = $param["os_id"];
		$host_data = \think\Db::name("host")->field("p.type")->alias("h")->leftJoin("products p", "p.id=h.productid")->where("h.id", $host_id)->where("h.uid", $this->request->uid)->find();
		if ($host_data["type"] == "dcim") {
			$check = check_dcim_auth($host_id, $this->request->uid, "reinstall");
			if ($check["status"] != 200) {
				return json($check);
			}
			$data = ["rootpass" => $param["dcim"]["password"], "action" => "", "mos" => $os_id, "mcon" => "", "port" => $param["dcim"]["port"], "disk" => 0, "check_disk_size" => 1, "part_type" => $param["dcim"]["part_type"] ?? 0];
			$dcim = new \app\common\logic\Dcim();
			$dcim->is_admin = false;
			$result = $dcim->reinstall($host_id, $data);
			return json($result);
		} else {
			$host = new \app\common\logic\Host();
			$port = input("post.port", 0, "intval");
			$result = $host->reinstall($host_id, $os_id, $port);
			return json($result);
		}
	}
	/**
	 * @title 救援系统
	 * @description 救援系统
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/rescue
	 * @method PUT
	 */
	public function rescue()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$rescue_id = $param["rescue_id"];
		$check = check_dcim_auth($host_id, $this->request->uid, "rescue");
		if ($check["status"] != 200) {
			return json($check);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->rescue($host_id, $rescue_id);
		return json($result);
	}
	/**
	 * @title 开机
	 * @description 开机
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/on
	 * @method PUT
	 */
	public function on()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$host_data = \think\Db::name("host")->field("p.type")->alias("h")->leftJoin("products p", "p.id=h.productid")->where("h.id", $host_id)->where("h.uid", $this->request->uid)->find();
		if ($host_data["type"] == "dcim") {
			$check = check_dcim_auth($host_id, $this->request->uid, "on");
			if ($check["status"] != 200) {
				return json($check);
			}
			$dcim = new \app\common\logic\Dcim();
			$result = $dcim->on($host_id);
			return json($result);
		} else {
			$host = new \app\common\logic\Host();
			$result = $host->on($host_id);
			return json($result);
		}
	}
	/**
	 * @title 关机
	 * @description 关机
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/off
	 * @method PUT
	 */
	public function off()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$host_data = \think\Db::name("host")->field("p.type")->alias("h")->leftJoin("products p", "p.id=h.productid")->where("h.id", $host_id)->where("h.uid", $this->request->uid)->find();
		if ($host_data["type"] == "dcim") {
			$check = check_dcim_auth($host_id, $this->request->uid, "off");
			if ($check["status"] != 200) {
				return json($check);
			}
			$dcim = new \app\common\logic\Dcim();
			$result = $dcim->off($host_id);
			return json($result);
		} else {
			$host = new \app\common\logic\Host();
			$result = $host->off($host_id);
			return json($result);
		}
	}
	/**
	 * @title 重启
	 * @description 重启
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/reboot
	 * @method PUT
	 */
	public function reboot()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$host_data = \think\Db::name("host")->field("p.type")->alias("h")->leftJoin("products p", "p.id=h.productid")->where("h.id", $host_id)->where("h.uid", $this->request->uid)->find();
		if ($host_data["type"] == "dcim") {
			$check = check_dcim_auth($host_id, $this->request->uid, "reboot");
			if ($check["status"] != 200) {
				return json($check);
			}
			$dcim = new \app\common\logic\Dcim();
			$result = $dcim->reboot($host_id);
			return json($result);
		} else {
			$host = new \app\common\logic\Host();
			$result = $host->reboot($host_id);
			return json($result);
		}
	}
	/**
	 * @title 硬关机
	 * @description 硬关机
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/hard_off
	 * @method PUT
	 */
	public function hardOff()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$host = new \app\common\logic\Host();
		$result = $host->hardOff($host_id);
		return json($result);
	}
	/**
	 * @title 硬重启
	 * @description 硬重启
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/hard_reboot
	 * @method PUT
	 */
	public function hardReboot()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$host = new \app\common\logic\Host();
		$result = $host->hardReboot($host_id);
		return json($result);
	}
	/**
	 * @title 重置bmc
	 * @description 重置bmc
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/bmc
	 * @method PUT
	 */
	public function bmc()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$check = check_dcim_auth($host_id, $this->request->uid, "bmc");
		if ($check["status"] != 200) {
			return json($check);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->bmc($host_id);
		if ($result["status"] == 400) {
			$result["msg"] = "reset failed";
		}
		return json($result);
	}
	/**
	 * @title kvm
	 * @description kvm
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/kvm
	 * @method PUT
	 */
	public function kvm()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$check = check_dcim_auth($host_id, $this->request->uid, "kvm");
		if ($check["status"] != 200) {
			return json($check);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->kvm($host_id);
		return json($result);
	}
	/**
	 * @title ikvm
	 * @description ikvm
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/ikvm
	 * @method PUT
	 */
	public function ikvm()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$check = check_dcim_auth($host_id, $this->request->uid, "ikvm");
		if ($check["status"] != 200) {
			return json($check);
		}
		$dcim = new \app\common\logic\Dcim();
		$result = $dcim->ikvm($host_id);
		return json($result);
	}
	/**
	 * @title VNC
	 * @description VNC
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/vnc
	 * @method PUT
	 */
	public function vnc()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$host_data = \think\Db::name("host")->field("p.type")->alias("h")->leftJoin("products p", "p.id=h.productid")->where("h.id", $host_id)->where("h.uid", $this->request->uid)->find();
		$host = new \app\common\logic\Host();
		$result = $host->vnc($host_id);
		return json($result);
	}
	/**
	 * @title 图表
	 * @description 图表
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/charts
	 * @method GET
	 */
	public function charts()
	{
		$param = $this->request->param();
		$host_id = $param["id"];
		$type = input("get.type");
		$select = input("get.select");
		$params = ["type" => input("get.type"), "select" => input("get.select"), "start" => input("get.start"), "end" => input("get.end")];
		if (!is_numeric($params["end"]) || empty($params["end"])) {
			$params["end"] = time() . "000";
		}
		if (!is_numeric($params["start"]) || empty($params["start"])) {
			$params["start"] = time() - 604800 . "000";
		}
		if (empty($host_id)) {
			$result["status"] = 400;
			$result["msg"] = "wrong ID";
			return json($result);
		}
		if (empty($type)) {
			$result["status"] = 400;
			$result["msg"] = "type error";
			return json($result);
		}
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.regdate,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $host_id)->find();
		if (empty($host)) {
			$result["status"] = 400;
			$result["msg"] = "wrong ID";
			return json($result);
		}
		if ($host["domainstatus"] != "Active" || request()->uid != $host["uid"]) {
			$result["status"] = 400;
			$result["msg"] = "The operation cannot be performed";
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
				$res = $dcimcloud->getChartData($host_id, $params);
			} else {
				$provision = new \app\common\logic\Provision();
				$res = $provision->getChartData($host_id, $params);
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
	 * @title 自定义函数
	 * @description 自定义函数
	 * return
	 * @author xiong
	 * @url /hosts/:id/module/custom
	 * @method GET
	 */
	public function custom()
	{
		$header = request()->header();
		$data = $this->request->param();
		$key = config("jwtkey");
		$jwt = explode(" ", $header["authorization"])[1];
		try {
			if (empty($jwt) || $jwt == "null" || count(explode(".", $jwt)) != 3) {
				return \think\Response::create("Please log in and try again")->code(200);
			}
			$tmp = \think\facade\Cache::get("client_user_login_token_" . $jwt);
			if (!$tmp) {
				return \think\Response::create("Please log in and try again")->code(200);
			}
			$jwtAuth = json_encode(\Firebase\JWT\JWT::decode($jwt, $key, ["HS256"]));
			$authInfo = json_decode($jwtAuth, true);
			$msg = [];
			if (!empty($authInfo["userinfo"])) {
				$checkJwtToken = ["status" => 1001, "msg" => "Token验证通过", "id" => $authInfo["userinfo"]["id"], "nbf" => $authInfo["nbf"], "ip" => $authInfo["ip"], "contactid" => $authInfo["userinfo"]["contactid"] ?? 0, "username" => $authInfo["userinfo"]["username"]];
			} else {
				return \think\Response::create("Token verification fails, the user does not exist")->code(200);
			}
		} catch (\Firebase\JWT\SignatureInvalidException $e) {
			return \think\Response::create("Token verification fails, the user does not exist")->code(200);
		} catch (\Firebase\JWT\ExpiredException $e) {
			return \think\Response::create("Login expired")->code(200);
		} catch (Exception $e) {
			return \think\Response::create($e->getMessage())->code(200);
		}
		$pass = \think\facade\Cache::get("client_user_update_pass_" . $tmp);
		if ($pass && $checkJwtToken["nbf"] < $pass) {
			return \think\Response::create("Password has been changed, please log in again")->code(200);
		}
		$ip_check = configuration("home_ip_check");
		if (get_client_ip() !== $checkJwtToken["ip"] && $ip_check == 1) {
			return \think\Response::create("Login failed, please log in again")->code(200);
		}
		if ($checkJwtToken["status"] == 1001 && $tmp == $checkJwtToken["id"]) {
		} else {
			return \think\Response::create("Please log in and try again")->code(200);
		}
		$id = $data["id"];
		$key = input("get.key", "");
		if (empty($id) || empty($key)) {
			return \think\Response::create("illegal parameter")->code(200);
		}
		$host = \think\Db::name("host")->alias("a")->field("a.id,a.productid,a.domainstatus,a.uid,a.dcimid,b.server_type,b.type,b.api_type,b.zjmf_api_id,c.email")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->where("a.id", $id)->find();
		if (empty($host)) {
			return \think\Response::create("host does not exist")->code(200);
		}
		if ($checkJwtToken["id"] != $host["uid"]) {
			return \think\Response::create("illegal parameter")->code(200);
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
			$html = $dcimcloud->moduleClientAreaDetail($id, $key);
		} elseif ($host["type"] == "dcim") {
			$dcim = new \app\common\logic\Dcim();
			$html = $dcim->moduleClientAreaDetail($id, $key);
		} else {
			$provision = new \app\common\logic\Provision();
			$html = $provision->clientAreaDetail($id, $key);
		}
		return \think\Response::create($html)->code(200);
	}
}