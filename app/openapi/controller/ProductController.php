<?php

namespace app\openapi\controller;

/**
 * @title 产品管理
 * @description 接口说明
 */
class ProductController extends \cmf\controller\HomeBaseController
{
	public function getProductsCates()
	{
		$param = $this->request->param();
		$list = $this->getProductType();
		foreach ($list as $key => $value) {
			$list[$key] = ["id" => $value["id"], "name" => $value["name"]];
		}
		$data = ["cates" => $list];
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function getProducts()
	{
		$param = $this->request->param();
		$page = $param["page"] >= 1 ? intval($param["page"]) : 1;
		$limit = $param["limit"] >= 1 ? intval($param["limit"]) : 20;
		$orderby = strval($param["orderby"]) ? strval($param["orderby"]) : "id";
		$sort = $param["sort"] ?? "DESC";
		$id = $param["cate"] ? intval($param["cate"]) : 0;
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
		$request->navRelid = explode(",", $nav_info["relid"] ?? "");
		$Host = new \app\home\controller\HostController();
		$domain_status_empty = 0;
		if (!$request->param("domain_status")) {
			$domain_status_empty = 1;
			$request->domain_status = ["Pending", "Active", "Suspended"];
		}
		if ($nav_info["templatePage"] == "service_ssl") {
			if ($domain_status_empty) {
				$request->domain_status = ["Pending", "Active", "Verifiy_Active", "Overdue_Active", "Issue_Active", "Cancelled", "Deleted"];
			}
		}
		$uid = $request->uid;
		$groupid = input("get.groupid", 0);
		$search = $request->search ?? "";
		$dcim_area = input("get.dcim_area", "");
		$domain_status = $request->domain_status ?? [];
		$page = $page > 0 ? $page : 1;
		$limit = $limit > 0 ? $limit : 10;
		if (!in_array($orderby, ["id", "domainstatus", "productname", "regdate", "nextduedate", "firstpaymentamount", "dedicatedip"])) {
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
		$where_search_area = "";
		if (!empty($dcim_area)) {
			$search_server = \think\Db::name("dcim_servers")->field("serverid,area")->where("area", "<>", "")->select()->toArray();
			foreach ($search_server as $v) {
				$a = json_decode($v["area"], true);
				foreach ($a as $vv) {
					if ($vv["name"] == $dcim_area) {
						$where_search_area .= "(h.serverid=" . $v["serverid"] . " AND h.dcim_area='{$vv["id"]}') OR ";
						break;
					}
				}
			}
			$where_search_area = substr($where_search_area, 0, -4);
		}
		if (!empty($where_search_area)) {
			$count = \think\Db::name("host")->alias("h")->leftJoin("products p", "p.id=h.productid")->where($where)->where($where_search_area)->count();
			$max_page = ceil($count / $page);
			$data = \think\Db::name("host")->field("h.orderid,p.api_type,p.zjmf_api_id")->field("h.id,h.domain,h.initiative_renew,h.domainstatus,h.regdate,h.dedicatedip,h.assignedips,h.nextduedate,h.remark  notes,h.nextinvoicedate,h.firstpaymentamount,h.amount,h.billingcycle,h.os,h.os_url,h.dcimid,h.dcim_os,h.dcim_area,b.os server_os,p.name as productname,b.area,b.auth,p.type as product_type,p.id as pid,p.pay_type")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("dcim_servers b", "h.serverid=b.serverid")->where($where)->where($where_search_area)->withAttr("assignedips", function ($value) {
				if (!empty($value)) {
					return explode(",", $value);
				} else {
					return [];
				}
			})->withAttr("pay_type", function ($value) {
				return json_decode($value, true);
			})->page($page)->limit($limit);
		} else {
			$count = \think\Db::name("host")->alias("h")->leftJoin("products p", "p.id=h.productid")->where($where)->count();
			$max_page = ceil($count / $page);
			$data = \think\Db::name("host")->field("h.orderid,p.api_type,p.zjmf_api_id")->field("h.id,h.domain,h.initiative_renew,h.domainstatus,h.regdate,h.dedicatedip,h.assignedips,h.nextduedate,h.remark  notes,h.nextinvoicedate,h.firstpaymentamount,h.amount,h.billingcycle,h.os,h.os_url,h.dcimid,h.dcim_os,h.dcim_area,b.os server_os,p.name as productname,b.area,b.auth,p.type as product_type,p.id as pid,p.pay_type")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("dcim_servers b", "h.serverid=b.serverid")->where($where)->withAttr("assignedips", function ($value) {
				if (!empty($value)) {
					return explode(",", $value);
				} else {
					return [];
				}
			})->withAttr("pay_type", function ($value) {
				return json_decode($value, true);
			})->group("h.id")->page($page)->limit($limit);
		}
		if ($orderby === "domainstatus") {
			$data = $data->orderField("domainstatus", ["Suspended", "Active", "Pending"], $sort)->select()->toArray();
		} else {
			$data = $data->order($orderby, $sort)->select()->toArray();
		}
		$currency = getUserCurrency($uid);
		$billing_cycle = config("billing_cycle");
		$cert_orderinfo = [];
		foreach ($data as $key => $val) {
			$host_cancel = \think\Db::name("cancel_requests")->field("type,reason")->where("relid", $val["id"])->find();
			if (!empty($host_cancel)) {
				if ($host_cancel["type"] == "Immediate") {
					$host_cancel["type"] = "立即停用";
				} else {
					$host_cancel["type"] = "到期时停用";
				}
			}
			$data[$key]["host_cancel"] = $host_cancel ?? [];
			$data[$key]["cycle_desc"] = $billing_cycle[$val["billingcycle"]];
			if ($val["product_type"] == "ssl") {
				$cert_data = [];
				if ($val["api_type"] == "zjmf_api") {
					$zjmf_data = zjmfCurl($val["zjmf_api_id"], "/provision/sslCertFunc", ["func" => "getAllInfo", "id" => $val["dcimid"]]);
					if ($zjmf_data["status"] == 200) {
						$cert_data = $zjmf_data["data"]["orderInfo"];
					}
				} else {
					$cert_data = \think\Db::name("certssl_orderinfo")->where("hostid", $val["id"])->find();
					if ($cert_data) {
						$cert_data["domainNames_arr"] = explode(PHP_EOL, $cert_data["domainNames"]);
						$cert_data["domainNames_arr"] = array_filter($cert_data["domainNames_arr"]);
					}
				}
				$data[$key]["cycle_desc"] = "-";
				if ($cert_data && isset($cert_data["due_time"])) {
					if ($cert_data["due_time"]) {
						if ($cert_data["due_time"] > time() && $cert_data["due_time"] < time() + 5184000) {
							$val["domainstatus"] = "Overdue_Active";
						}
						if ($cert_data["due_time"] <= time()) {
							$val["domainstatus"] = "Deleted";
						}
					}
					$data[$key]["cycle_desc"] = $cert_data["due_time"] ? date("Y-m-d", $cert_data["due_time"]) : "-";
				}
				$data[$key]["used_domainNames"] = $cert_data["used_domainNames"] ?? "-";
				$data[$key]["domainNames_arr"] = $cert_data["domainNames_arr"] ?? [];
				$data[$key]["domainstatus_desc"] = config("sslDomainStatus")[$val["domainstatus"]];
			} else {
				$data[$key]["domainstatus_desc"] = config("domainstatus")[$val["domainstatus"]];
			}
			if ($val["billingcycle"] == "onetime") {
				$data[$key]["price_desc"] = $currency["prefix"] . $val["firstpaymentamount"] . $currency["suffix"];
			} else {
				$data[$key]["price_desc"] = $currency["prefix"] . $val["amount"] . $currency["suffix"];
			}
			$data[$key]["auth"] = json_decode($val["auth"], true) ?: [];
			$data[$key]["notes"] = html_entity_decode($val["notes"]);
			unset($data[$key]["dcim_os"]);
			unset($data[$key]["server_os"]);
			if (!empty($val["dcim_area"])) {
				$area = json_decode($val["area"], true);
				foreach ($area as $k => $v) {
					if ($v["id"] == $val["dcim_area"]) {
						$data[$key]["area_code"] = $v["area"];
						$data[$key]["area_name"] = $v["name"] ?? "";
						break;
					}
				}
			} else {
				$data[$key]["area_code"] = "";
				$data[$key]["area_name"] = "";
			}
			unset($data[$key]["area"]);
			unset($data[$key]["dcim_area"]);
		}
		foreach ($data as $key => $val) {
			$all_options = \think\Db::name("host_config_options")->where("relid", $val["id"])->select()->toArray();
			foreach ($all_options as $k => $v) {
				$ssl_option = \think\Db::name("product_config_options")->where("id", $v["configid"])->value("option_name");
				$ssl_option_sub = \think\Db::name("product_config_options_sub")->where("id", $v["optionid"])->value("option_name");
				$ssl_option_key = explode("|", $ssl_option)[0];
				$ssl_option_sub = explode("|", $ssl_option_sub);
				$data[$key][$ssl_option_key] = $ssl_option_sub[1] ?? $ssl_option_sub[0];
			}
			$data[$key]["invoice_id"] = \think\Db::name("orders")->where("id", $val["orderid"])->value("invoiceid");
			$hco = \think\Db::name("host_config_options")->alias("h")->field("pco.option_name,pcos.option_name as option_names")->join("product_config_options pco", "pco.id=h.configid")->join("product_config_options_sub pcos", "pcos.id=h.optionid")->where("h.relid", $val["id"])->select()->toArray();
			if ($hco[0]["option_name"] == "添加配置项") {
				$hco = [];
			}
			if (!empty($hco)) {
				foreach ($hco as $k => $v) {
					$a = explode("^", explode("|", $v["option_name"]))[1];
					$a1 = explode("|", $v["option_names"]);
					$hco[$k]["option_name"] = str_replace(" ", "", $a[0]);
					$hco[$k]["option_names"] = $a1[1];
					if ($hco[$k]["option_names"] == "" || $hco[$k]["option_name"] == "") {
						$hco = [["option_name" => "os", "option_names" => ""], ["option_name" => "Memory", "option_names" => ""], ["option_name" => "DiskSpace", "option_names" => ""], ["option_name" => "CPU", "option_names" => ""]];
						break;
					}
				}
			} else {
				$hco = [["option_name" => "os", "option_names" => ""], ["option_name" => "Memory", "option_names" => ""], ["option_name" => "DiskSpace", "option_names" => ""], ["option_name" => "CPU", "option_names" => ""]];
			}
			$data[$key]["options"] = array_column($hco, "option_names", "option_name");
		}
		$result["data"]["total"] = $count;
		$result["data"]["list"] = $data;
		if ($type == "dcim") {
			$result["data"]["area"] = get_all_dcim_area();
		} else {
			$result["data"]["area"] = [];
		}
		$domainstatus = config("public.domainstatus");
		$result["data"]["domainstatus"] = $domainstatus;
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $result["data"]]);
	}
	public function getProductDetail($id = 0)
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
		$product = \think\Db::name("host")->field("h.orderid,h.id as host_id,h.uid,h.initiative_renew,h.productid,h.serverid,h.regdate,h.domain,h.payment,p.groupid,h.promoid,
                h.firstpaymentamount,h.amount,h.billingcycle,h.nextduedate,h.nextinvoicedate,
                h.dedicatedip,h.assignedips,h.domainstatus,h.username,h.password,h.suspendreason,p.id as pid,
                h.bwusage,h.bwlimit,h.os,h.remark,h.dcimid,h.dcim_area,h.dcim_os,h.port,p.type,p.name as productname,p.pay_method as payment_type,p.config_options_upgrade,p.api_type,p.zjmf_api_id,p.upper_reaches_id,p.upstream_price_type,p.upstream_price_value,g.name as groupname,o.ordernum")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("product_groups g", "g.id=p.gid")->leftJoin("orders o", "o.id=h.orderid")->where("h.id", $host_id)->find();
		$grou = \think\Db::name("nav_group")->where("id", $product["groupid"])->find();
		$product["group"] = $grou;
		$domainstatus_config = config("domainstatus");
		$currency = getUserCurrency($uid);
		$billing_cycle = config("billing_cycle");
		$product["suspendreason_type"] = explode("-", $product["suspendreason"])[0] ? explode("-", $product["suspendreason"])[0] : "";
		$product["suspendreason"] = explode("-", $product["suspendreason"])[1] ? explode("-", $product["suspendreason"])[1] : "";
		$product["assignedips"] = !empty($product["assignedips"]) ? explode(",", $product["assignedips"]) : [];
		$product["domainstatus_desc"] = $domainstatus_config[$product["domainstatus"]];
		$product["password"] = cmf_decrypt($product["password"]);
		$product["firstpaymentamount_desc"] = $currency["prefix"] . $product["firstpaymentamount"] . $currency["suffix"];
		$product["amount_desc"] = $currency["prefix"] . $product["amount"] . $currency["suffix"];
		$product["billingcycle_desc"] = $billing_cycle[$product["billingcycle"]];
		$product["ip_num"] = count($product["assignedips"]);
		$product["bwusage"] = round($product["bwusage"], 2);
		$product["remark"] = html_entity_decode($product["remark"]);
		$returndata["product"] = $product;
		$productid = $product["productid"];
		$config_options = [];
		$config_logic = new \app\common\logic\ConfigOptions();
		$config_options = $config_logic->showInfo($productid, $host_id, $currency, $product["billingcycle"], false);
		$returndata["config_options"] = array_values($config_options);
		$custom_field_data = \think\Db::name("customfields")->field("id,fieldname")->where("type", "product")->where("relid", $productid)->where("adminonly", 0)->select()->toArray();
		foreach ($custom_field_data as &$cv) {
			$cv["value"] = \think\Db::name("customfieldsvalues")->where("fieldid", $cv["id"])->where("relid", $host_id)->value("value") ?? "";
		}
		$returndata["custom_field_data"] = $custom_field_data ?? [];
		$upstream_data = [];
		if ($product["api_type"] == "zjmf_api") {
			$returndata["product"]["serverid"] = $returndata["product"]["zjmf_api_id"];
			$upstream_data = zjmfCurl($product["zjmf_api_id"], "/host/header", ["host_id" => $product["dcimid"]], 30, "GET");
			if ($upstream_data["status"] == 200) {
				$upstream_data = $upstream_data["data"];
			} else {
				$upstream_data = [];
			}
			$returndata["dcim"]["flowpacket"] = $upstream_data["dcim"]["flowpacket"] ?: [];
			$returndata["product"]["bwlimit"] = \intval($upstream_data["product"]["bwlimit"]);
			$returndata["product"]["bwusage"] = \floatval($upstream_data["product"]["bwusage"]);
		} elseif ($product["api_type"] == "manual") {
			$upper_reaches = \think\Db::name("zjmf_finance_api")->where("id", $product["upper_reaches_id"])->find();
			$returndata["manual"] = ["id" => $product["upper_reaches_id"], "name" => $upper_reaches["name"]];
			$upper_reaches_res = \think\Db::name("upper_reaches_res")->where("hid", $host_id)->find();
			$returndata["product"]["upper_reaches_res"] = $upper_reaches_res["id"] ?? "";
			$returndata["product"]["upper_reaches_control_mode"] = $upper_reaches_res["control_mode"] ?? "";
		}
		$os_config_option_id = \think\Db::name("product_config_links")->alias("a")->leftJoin("product_config_options b", "a.gid=b.gid")->where("a.pid", $product["productid"])->where("b.option_type", 5)->value("b.id");
		$returndata["product"]["os_config_option_id"] = $os_config_option_id;
		$os_info = \think\Db::name("host_config_options")->alias("a")->field("b.option_name")->leftJoin("product_config_options_sub b", "a.optionid=b.id")->where("a.relid", $host_id)->where("a.configid", $os_config_option_id)->find();
		if (empty($product["username"])) {
			if (stripos($os_info["option_name"], "win") !== false) {
				$returndata["product"]["username"] = "administrator";
			} else {
				$returndata["product"]["username"] = "root";
			}
		}
		$userinfo = db("clients")->field("second_verify")->where("id", $uid)->find();
		$returndata["second"]["second_verify"] = $userinfo["second_verify"];
		$returndata["second"]["allow_second_verify"] = intval(configuration("second_verify_home"));
		$returndata["second"]["second_verify_action_home"] = explode(",", configuration("second_verify_action_home"));
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $returndata]);
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
	public function upgradeConfigPage1()
	{
		$data = [];
		$param = $this->request->param();
		$hid = intval($param["id"]);
		if (!$hid) {
			return json(["status" => 400, "msg" => "ID error"]);
		}
		$uid = request()->uid;
		if (!$uid) {
			return json(["status" => 400, "msg" => "ID error"]);
		}
		$currencyid = priorityCurrency($uid);
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
				$h["id"] = $host["oid"];
				$h["option_name"] = $option_name[1] ? $option_name[1] : $host["option_name"];
				$h["option_type"] = $host["option_type"];
				$h["suboption_name"] = explode("|", $host["suboption_name"])[1] ? explode("|", $host["suboption_name"])[1] : $host["suboption_name"];
				$h["suboption_name"] = implode(" ", explode("^", $h["suboption_name"]));
				$h["suboption_name_first"] = explode("|", $host["suboption_name"])[1] ? explode("|", $host["suboption_name"])[0] : $host["suboption_name"];
				if ($h["option_type"] == 3 && $h["qty"] == 0) {
					$h["subid"] = 0;
				} else {
					$h["subid"] = $host["subid"];
				}
				$h["qty_minimum"] = 0;
				$h["qty_maximum"] = 0;
				$h["unit"] = $host["unit"];
				$h["sub"] = [];
				foreach ($allOption as $vv) {
					if ($vv["id"] == $h["id"]) {
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
		$data["host"] = $hostFilters;
		$data["pid"] = $pid;
		return json(["status" => 200, "msg" => "Success message", "data" => $data]);
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
	public function upgradeConfig1()
	{
		try {
			$param = $this->request->param();
			$hid = intval($param["id"]);
			if (!$hid) {
				return json(["status" => 400, "msg" => "ID error"]);
			}
			$upgrade_logic = new \app\common\logic\Upgrade();
			if (!$upgrade_logic->judgeUpgradeConfigError($hid)) {
				return json(["status" => 400, "msg" => "Configurable items cannot be upgraded or downgraded for the current product"]);
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
				$uid = request()->uid;
				$currencyid = priorityCurrency($uid);
				$re = $upgrade_logic->upgradeConfigCommon($hid, $configoptions, $currencyid, false, $promo_code);
				$page = $re["data"];
				$alloptions = $page["alloption"];
				$filter = [];
				foreach ($alloptions as $alloption) {
					$tmp = ["oid" => $alloption["oid"], "option_name" => $alloption["option_name"], "option_type" => $alloption["option_type"], "suboption_name" => $alloption["suboption_name"], "old_suboption_name" => $alloption["old_suboption_name"]];
					if (isset($alloption["old_qty"])) {
						$tmp["old_qty"] = $alloption["old_qty"];
					}
					if (isset($alloption["qty"])) {
						$tmp["qty"] = $alloption["qty"];
					}
					$filter[] = $tmp;
				}
				$return = ["currency" => $page["currency"], "name" => $page["name"], "billingcycle" => $page["billingcycle"], "payment" => $page["payment"], "saleproducts" => $page["saleproducts"], "subtotal" => $page["subtotal"], "total" => $page["total"], "promo_code" => $page["promo_code"], "configoptions" => $page["configoptions"], "alloption" => $filter];
				return json(["status" => 200, "msg" => "Success message", "data" => $return]);
			} else {
				return json(["status" => 400, "msg" => "Parameter error"]);
			}
		} catch (\Throwable $e) {
			return json(["status" => 400, "msg" => $e->getMessage()]);
		}
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
			$uid = request()->uid;
			$currencyid = priorityCurrency($uid, $currencyid);
			$re = $upgrade_logic->upgradeConfigCommon($hid, $configoptions, $currencyid, false, $promo_code);
			$return = $re["data"];
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $return]);
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
				return json(["status" => 400, "msg" => "Please select a configuration item"]);
			}
			\think\Db::name("host")->where("id", $hid)->update(["promoid" => 0]);
			$data["promo_code"] = "";
			cache("upgrade_down_config_" . $hid, $data, 86400);
			$currencyid = intval($data["currencyid"]);
			$configoptions = $data["configoptions"];
			$uid = request()->uid;
			$currencyid = priorityCurrency($uid, $currencyid);
			$re = $upgrade_logic->upgradeConfigCommon($hid, $configoptions, $currencyid, false, "");
			$return = $re["data"];
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $return]);
		} catch (\Throwable $e) {
			return json(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	public function upgradeConfigCheckout1()
	{
		try {
			$param = $this->request->param();
			$hid = intval($param["id"]);
			if (!$hid) {
				return json(["status" => 400, "msg" => "ID error"]);
			}
			$upgrade_logic = new \app\common\logic\Upgrade();
			if (!$upgrade_logic->judgeUpgradeConfigError($hid)) {
				return json(["status" => 400, "msg" => "Configurable items cannot be upgraded or downgraded for the current product"]);
			}
			$data = cache("upgrade_down_config_" . $hid);
			if (!$data) {
				return json(["status" => 400, "msg" => "Please select a configuration item"]);
			}
			$configoptions = $data["configoptions"];
			$promo_code = $data["promo_code"] ?: "";
			$uid = request()->uid;
			$currencyid = priorityCurrency($uid);
			$payment = \think\Db::name("host")->where("id", $hid)->value("payment");
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
				return json(["status" => 400, "msg" => "Parameter error"]);
			}
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
				return json(["status" => 400, "msg" => "Configurable items cannot be upgraded or downgraded for the current product"]);
			}
			$data = cache("upgrade_down_config_" . $hid);
			if (!$data) {
				return json(["status" => 400, "msg" => "Please select a configuration item"]);
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
	public function upgradeProductPage()
	{
		try {
			$re = $data = [];
			$re["status"] = 200;
			$re["msg"] = "Success message";
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
			$currency_id = priorityCurrency($uid);
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
	public function upgradeProduct()
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
			cache("upgrade_down_product_" . $hid, $data, 86400);
			$promo_code = $param["promo_code"] ?: "";
			$re = $upgrade_logic->upgradeProductCommon($hid, $new_pid, $billingcycle, "", $promo_code);
			$return = $re["data"];
			$return["name"] = $return["des"];
			unset($return["des"]);
			unset($return["amount"]);
			unset($return["discount"]);
			unset($return["billingcycle_zh"]);
			unset($return["flag"]);
			unset($return["has_renew"]);
			return json(["status" => 200, "msg" => "Success message", "data" => $return]);
		} catch (\Throwable $e) {
			return json(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	public function upgradeProductAddPromo()
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
			$promo_code = $param["pormo_code"] ?: "";
			$new_pid = $data["pid"];
			$upgrade_type = "product";
			$new_billingcycle = $data["billingcycle"];
			$result = $upgrade_logic->checkUpgradePromo($promo_code, $hid, $new_pid, $new_billingcycle, $upgrade_type);
			if ($result["status"] != 200) {
				return json($result);
			}
			if (!$data) {
				return json(["status" => 400, "msg" => "Promo code is invalid"]);
			}
			$data["promo_code"] = $promo_code;
			cache("upgrade_down_product_" . $hid, $data, 86400);
			return json(["status" => 200, "msg" => "Promo code applied successfully"]);
		} catch (\Throwable $e) {
			return json(["status" => 400, "msg" => $e->getMessage()]);
		}
	}
	public function upgradeProductRemovePromo()
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
	public function upgradeProductCheckout()
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
}