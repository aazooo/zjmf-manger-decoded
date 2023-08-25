<?php

namespace app\admin\controller;

/**
 * @title 后台首页 菜单
 * @description 接口说明
 */
class IndexController extends AdminBaseController
{
	public $data2 = [];
	public $data = [];
	public $getUserCtol;
	public function initialize()
	{
		$adminSettings = cmf_get_option("admin_settings");
		if (empty($adminSettings["admin_password"]) || $this->request->path() == $adminSettings["admin_password"]) {
		}
		$app_lang = app_lang();
		$this->data2 = [["name" => "Client", "value" => $app_lang["tablelist_client"]], ["name" => "Products", "value" => $app_lang["tablelist_products"]], ["name" => "Tickets", "value" => $app_lang["tablelist_tickets"]], ["name" => "Invoice", "value" => $app_lang["tablelist_invoice"]], ["name" => "Orders", "value" => $app_lang["tablelist_orders"]]];
		$customfields = \think\Db::name("customfields")->field("id,fieldname")->where("type", "client")->select()->toArray();
		$this->data["Client"] = [["name" => "cli.id", "value" => $app_lang["tablelist_client_id"], "type" => 0, "ab_name" => "ID"], ["name" => "cli.username", "value" => $app_lang["tablelist_client_username"], "type" => 1, "ab_name" => "username"], ["name" => "cli.email", "value" => $app_lang["tablelist_client_email"], "type" => 1, "ab_name" => "email"], ["name" => "cli.qq", "value" => "qq", "type" => 1, "ab_name" => "qq"], ["name" => "cli.phonenumber", "value" => $app_lang["tablelist_client_phonenumber"], "type" => 1, "ab_name" => "phonenumber"], ["name" => "cli.companyname", "value" => $app_lang["tablelist_client_companyname"], "type" => 1, "ab_name" => "companyname"], ["name" => "cli.groupid", "value" => $app_lang["tablelist_client_group"], "type" => 0, "ab_name" => "groupid"], ["name" => "cli.sale_id", "value" => $app_lang["tablelist_client_sale"], "type" => 0, "ab_name" => "sale_id"]];
		foreach ($customfields as $v) {
			$tmp = ["name" => $v["id"], "value" => $v["fieldname"], "type" => 0, "ab_name" => $v["id"], "fixed" => "customfields"];
			array_push($this->data["Client"], $tmp);
		}
		$this->data["Products"] = [["name" => "h.id", "value" => $app_lang["tablelist_products_id"], "type" => 0, "ab_name" => "ID"], ["name" => "h.domainstatus", "value" => $app_lang["tablelist_products_domainstatus"], "type" => 0, "ab_name" => "status"], ["name" => "p.id", "value" => $app_lang["tablelist_products_name"], "type" => 0, "ab_name" => "PID"], ["name" => "h.domain", "value" => $app_lang["tablelist_products_domain"], "type" => 1, "ab_name" => "domain"], ["name" => "h.dedicatedip", "value" => "IP", "type" => 1, "ab_name" => "IP"], ["name" => "h.nextduedate", "value" => $app_lang["tablelist_products_nextduedate"], "type" => 0, "ab_name" => "nextduedate"], ["name" => "c.username", "value" => $app_lang["tablelist_client_username"], "type" => 1, "ab_name" => "username"], ["name" => "h.username", "value" => $app_lang["tablelist_products_username"], "type" => 1, "ab_name" => "husername"], ["name" => "c.sale_id", "value" => $app_lang["tablelist_client_sale"], "type" => 2, "ab_name" => "saleID"], ["name" => "h.billingcycle", "value" => $app_lang["tablelist_products_billingcycle"], "type" => 2, "ab_name" => "cycle"], ["name" => "p.type", "value" => $app_lang["tablelist_products_type"], "type" => 2, "ab_name" => "type"]];
		$this->data["Tickets"] = [["name" => "a.tid", "value" => $app_lang["tablelist_tickets_id"], "type" => 0, "ab_name" => "TID"], ["name" => "d.username", "value" => $app_lang["tablelist_client_username"], "type" => 1, "ab_name" => "username"], ["name" => "a.title", "value" => $app_lang["tablelist_tickets_title"], "type" => 1, "ab_name" => "title"], ["name" => "a.status", "value" => $app_lang["tablelist_tickets_status"], "type" => 2, "ab_name" => "status"], ["name" => "c.id", "value" => $app_lang["tablelist_tickets_cid"], "type" => 2, "ab_name" => "CID"]];
		$this->data["Invoice"] = [["name" => "i.id", "value" => $app_lang["tablelist_invoice_id"], "type" => 0, "ab_name" => "ID"], ["name" => "c.username", "value" => $app_lang["tablelist_client_username"], "type" => 1, "ab_name" => "username"], ["name" => "i.subtotal", "value" => $app_lang["tablelist_invoice_subtotal"], "type" => 0, "ab_name" => "subtotal"], ["name" => "i.paid_time", "value" => $app_lang["tablelist_invoice_paid_time"], "type" => 0, "ab_name" => "paidtime"], ["name" => "i.status", "value" => $app_lang["tablelist_invoice_status"], "type" => 2, "ab_name" => "status"], ["name" => "c.sale_id", "value" => $app_lang["tablelist_client_sale"], "type" => 2, "ab_name" => "saleID"], ["name" => "i.type", "value" => $app_lang["tablelist_invoice_type"], "type" => 2, "ab_name" => "type"]];
		$this->data["Orders"] = [["name" => "o.id", "value" => $app_lang["tablelist_orders_id"], "type" => 0, "ab_name" => "ID"], ["name" => "c.sale_id", "value" => $app_lang["tablelist_client_sale"], "type" => 2, "ab_name" => "saleID"], ["name" => "o.amount", "value" => $app_lang["tablelist_invoice_subtotal"], "type" => 0, "ab_name" => "amount"], ["name" => "c.username", "value" => $app_lang["tablelist_client_username"], "type" => 1, "ab_name" => "username"], ["name" => "o.create_time", "value" => $app_lang["tablelist_orders_create_time"], "type" => 0, "ab_name" => "createtime"]];
		$this->getUserCtol = new GetUserController();
		parent::initialize();
	}
	/**
	 * 后台首页
	 */
	public function index()
	{
		$content = hook_one("admin_index_index_view");
		if (!empty($content)) {
			return $content;
		}
		$adminMenuModel = new \app\admin\model\AdminMenuModel();
		$menus = cache("admin_menus_" . cmf_get_current_admin_id(), "", null, "admin_menus");
		if (empty($menus)) {
			$menus = $adminMenuModel->menuTree();
			cache("admin_menus_" . cmf_get_current_admin_id(), $menus, null, "admin_menus");
		}
		$this->assign("menus", $menus);
		$result = \think\Db::name("AdminMenu")->order(["app" => "ASC", "controller" => "ASC", "action" => "ASC"])->select();
		$menusTmp = [];
		foreach ($result as $item) {
			$indexTmp = $item["app"] . $item["controller"] . $item["action"];
			$indexTmp = preg_replace("/[\\/|_]/", "", $indexTmp);
			$indexTmp = strtolower($indexTmp);
			$menusTmp[$indexTmp] = $item;
		}
		$this->assign("menus_js_var", json_encode($menusTmp));
		$data = $this->request->param();
		$date = isset($data["time"]) ? $data["time"] : date("Y-m-d");
		$filename = CMF_ROOT . "data/journal/" . $date . ".log";
		$logs = [];
		if (file_exists_case($filename)) {
			fopen($filename, "r");
			$num = count(file($filename));
			$file_hwnd = fopen($filename, "r");
			$content = explode("\r\n", fread($file_hwnd, filesize($filename)));
			$content = array_slice(array_reverse($content), 0, 7);
			fclose($file_hwnd);
			foreach ($content as $k => $v) {
				if ($v) {
					$logs[$k] = json_decode($v, true);
				}
			}
		} else {
			$num = 0;
		}
		$this->assign("content", array_reverse($logs, true));
		$this->assign("time", $date);
		$this->assign("num", $num);
		return $this->fetch();
	}
	/**
	 * @title 后台 首页菜单
	 * @description 接口说明:
	 * @return menus:  菜单列表@
	 * @menus admin:客户菜单@ 110user:管理员菜单同上 1admin:插件菜单同上
	 * @admin id:菜单id name:菜单名 url:菜单url parent:父级菜单 lang:多语言key
	 * @return .content:  管理员日志
	 * @throws
	 **@author 上官磨刀
	 * @url /ad_index
	 * @method get
	 */
	public function ad_index()
	{
		$content = hook_one("admin_index_index_view");
		if (!empty($content)) {
			return $content;
		}
		$adminMenuModel = new \app\admin\model\AdminMenuModel();
		$menus = cache("admin_menus_" . cmf_get_current_admin_id(), "", null, "admin_menus");
		if (empty($menus)) {
			$menus = $adminMenuModel->menuTree();
			cache("admin_menus_" . cmf_get_current_admin_id(), $menus, null, "admin_menus");
		}
		$result = \think\Db::name("AdminMenu")->order(["app" => "ASC", "controller" => "ASC", "action" => "ASC"])->select();
		$menusTmp = [];
		foreach ($result as $item) {
			$indexTmp = $item["app"] . $item["controller"] . $item["action"];
			$indexTmp = preg_replace("/[\\/|_]/", "", $indexTmp);
			$indexTmp = strtolower($indexTmp);
			$menusTmp[$indexTmp] = $item;
		}
		$data = $this->request->param();
		$date = isset($data["time"]) ? $data["time"] : date("Y-m-d");
		$filename = CMF_ROOT . "data/journal/" . $date . ".log";
		$logs = [];
		if (file_exists_case($filename)) {
			fopen($filename, "r");
			$num = count(file($filename));
			$file_hwnd = fopen($filename, "r");
			$content = explode("\r\n", fread($file_hwnd, filesize($filename)));
			$content = array_slice(array_reverse($content), 0, 7);
			fclose($file_hwnd);
			foreach ($content as $k => $v) {
				if ($v) {
					$logs[$k] = json_decode($v, true);
				}
			}
		} else {
			$num = 0;
		}
		$data = ["menus" => $menus, "content" => array_reverse($logs, true), "time" => $date, "num" => $num];
		return jsonrule(["data" => $data, "status" => 200, "msg" => "ok"]);
	}
	/**
	 * 时间 2020/9/08 16:48
	 * @title 搜索
	 * @desc void
	 * @url searchlist
	 * @param .name:value type:number require:0 default:1 other: desc:全局搜索字段
	 * @method  get
	 * @return  array list - 搜索返回列表
	 * @author lgd
	 * @version v1
	 */
	public function search()
	{
		$datas = $this->request->param();
		$value = trim($datas["value"]);
		if (empty($value) || $value == "") {
			return jsons(["status" => 400, "data" => []]);
		}
		$data = [];
		$data[] = ["Client" => ["name" => "用户", "list" => $this->getClients($value, "client")], "Products" => ["name" => "产品", "list" => $this->getProducts($value)], "Tickets" => ["name" => "工单", "list" => $this->getTickets($value)], "Invoice" => ["name" => "账单", "list" => $this->getInvoice($value)], "Customfields" => ["name" => "自定义字段", "list" => $this->getClients($value, "custom")]];
		return jsons(["status" => 200, "data" => $data]);
	}
	public function search1()
	{
		$datas = $this->request->param();
		$value = trim($datas["value"]);
		if (empty($value) || $value == "") {
			return jsons(["status" => 200, "data" => []]);
		}
		$data[]["Ticket"] = ["name" => "工单", "list" => $this->getClients($value)];
		return json(["status" => 200, "data" => $data]);
	}
	private function getClients($value, $type = "client")
	{
		$datac = [];
		$datac = array_merge_recursive($datac, $this->searchClients($value, "", 0, $type));
		return second_array_unique_bykey($datac, "id");
	}
	private function getProducts($value)
	{
		$datac = [];
		$datac = array_merge_recursive($datac, $this->searchProducts($value, ""));
		return second_array_unique_bykey($datac, "url");
	}
	private function getOrders($value)
	{
		$datac = [];
		$datac = array_merge_recursive($datac, $this->searchOrders($value, ""));
		return second_array_unique_bykey($datac, "url");
	}
	private function getInvoice($value)
	{
		$datac = [];
		$datac = array_merge_recursive($datac, $this->searchInvoice($value, ""));
		return second_array_unique_bykey($datac, "url");
	}
	private function getTickets($value)
	{
		$datac = [];
		$datac = array_merge_recursive($datac, $this->searchTickets($value, ""));
		return second_array_unique_bykey($datac, "url");
	}
	private function searchClients($value, $key, $flag = 0, $type = "client")
	{
		$where = [];
		$param = [];
		if ($flag == 0) {
			if (!empty($key)) {
				$where[] = ["c." . $key, "eq", $value];
			} else {
				$param = ["c.id", "c.username", "c.email", "c.companyname", "c.qq", "c.phonenumber"];
			}
		} else {
			$param = $flag;
		}
		if ($type == "custom") {
			$datac = \think\Db::name("clients")->field("c.id,c.id as url,c.email as values3,c.status,cf.fieldname as values1,cfv.value as values2")->alias("c")->leftJoin("customfieldsvalues cfv", "cfv.relid = c.id")->leftJoin("customfields cf", "cf.id = cfv.fieldid")->where("cf.type", "client")->where(function (\think\db\Query $query) use($param, $value) {
				$query->where("cfv.value", "like", "%{$value}%");
			})->withAttr("url", function ($value, $data) {
				$url = "#/customer-view/abstract?id=" . $value;
				return $url;
			});
			if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
				$datac->whereIn("c.id", $this->getUserCtol->str);
			}
			$datac = $datac->where($where)->select()->toArray();
		} else {
			$datac = \think\Db::name("clients")->field("c.id,c.id as url,c.username as values1,c.companyname as values2,c.email as values3,c.status")->alias("c")->where(function (\think\db\Query $query) use($param, $value) {
				$certifi_uid = \think\Db::name("certifi_log")->where("certifi_name", "like", "%" . trim($value) . "%")->where("status", 1)->column("uid");
				if (empty($certifi_uid)) {
					$certifi_uid = [0];
				}
				foreach ($param as $v) {
					$query->whereOr($v, "like", "%{$value}%");
				}
				$query->whereOr("c.id", "in", $certifi_uid);
			})->withAttr("url", function ($value, $data) {
				$url = "#/customer-view/abstract?id=" . $value;
				return $url;
			});
			if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
				$datac->whereIn("c.id", $this->getUserCtol->str);
			}
			$datac = $datac->where($where)->select()->toArray();
		}
		foreach ($datac as $key => $value) {
			$datac[$key]["status_color"] = config("client_status1")[$value["status"]]["color"];
			$datac[$key]["status"] = config("client_status1")[$value["status"]]["name"];
		}
		return $datac;
	}
	private function searchCeriClients($value, $flag = 0)
	{
		if ($flag == 0) {
			$param = ["cl.bank", "cl.certifi_name", "cl.company_name", "cl.idcard", "cl.company_organ_code", "cl.phone"];
		} else {
			$param = $flag;
		}
		$datacl = \think\Db::name("clients")->field("c.id,c.id as url,c.email as values1,c.username as values2,c.email as values3,c.status")->alias("c")->leftJoin("certifi_log cl", "cl.uid=c.id")->where("cl.status=2")->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->whereOr($v, "like", "%{$value}%");
			}
		})->withAttr("status", function ($value, $data) {
			switch ($value) {
				case 1:
					$value = "正常";
					break;
				case 0:
					$value = "停用";
					break;
				case 2:
					$value = "关闭";
					break;
			}
			return $value;
		})->withAttr("url", function ($value, $data) {
			$url = "#/customer-view/abstract?id=" . $value;
			return $url;
		})->select()->toArray();
		return $datacl;
	}
	private function searchProducts($value, $key, $flag = 0)
	{
		$where = [];
		$param = [];
		if ($flag == 0) {
			if (!empty($key)) {
				$where[] = [$key, "eq", $value];
			} else {
				$param1 = ["h.domain", "h.dedicatedip", "h.assignedips"];
				$param = ["h.domain", "h.dedicatedip", "h.assignedips"];
			}
		} else {
			$param = $flag;
		}
		$data = \think\Db::name("host")->alias("h")->join("products p", "p.id=h.productid")->join("product_groups pg", "pg.id=p.gid")->join("clients c", "c.id=h.uid")->leftJoin("promo_code pc", "pc.id=h.promoid")->field("c.id,h.id as url,p.name as values1,h.domain as values2,h.domainstatus as status,c.username as values3")->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->whereOr($v, "like", "%{$value}%");
			}
		})->withAttr("url", function ($value, $data) {
			$host = \think\Db::name("host")->alias("a")->field("a.uid")->where("a.id", $value)->find();
			$url = "#/customer-view/product-innerpage?hid=" . $value . "&id=" . $host["uid"];
			return $url;
		});
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$data->whereIn("c.id", $this->getUserCtol->str);
		}
		$data = $data->where($where)->select()->toArray();
		foreach ($data as &$v) {
			$v["status_color"] = config("public.domainstatus")[$v["status"]]["color"];
			$v["status"] = config("public.domainstatus")[$v["status"]]["name"];
		}
		return $data;
	}
	private function searchOrders($value, $key, $flag = 0)
	{
		$where = [];
		$param = [];
		if ($flag == 0) {
			if (!empty($key)) {
				$where[] = [$key, "eq", $value];
			} else {
				$param = ["p.name", "p.description", "p.host", "p.welcome_email", "p.qty", "p.prorata_date", "pg.name", "pg.headline", "pg.tagline", "pg.order_frm_tpl", "c.username", "c.status", "c.email", "c.companyname", "c.qq", "c.country", "c.province", "c.city", "c.region", "c.address1", "c.postcode", "c.phonenumber", "c.defaultgateway", "c.credit", "c.lastloginip", "c.ip", "c.language", "h.regdate", "h.domain", "h.firstpaymentamount", "h.amount", "h.last_settle", "h.nextduedate", "h.nextinvoicedate", "h.termination_date", "h.completed_date", "h.username", "h.notes", "h.subscriptionid", "h.suspendreason", "h.overideautosuspend", "h.overidesuspenduntil", "h.dedicatedip", "h.assignedips", "h.ns1", "h.ns2", "h.bwusage", "h.bwlimit", "h.lastupdate", "h.create_time", "h.update_time", "h.suspend_time", "h.auto_terminate_reason", "h.os", "h.os_url", "h.reinstall_info", "h.port", "h.dcim_area", "h.stream_info", "o.ordernum", "o.amount"];
			}
		} else {
			$param = $flag;
		}
		$data = \think\Db::name("orders")->alias("o")->join("host h", "o.id=h.orderid")->join("products p", "p.id=h.productid")->join("product_groups pg", "pg.id=p.gid")->join("clients c", "c.id=o.uid")->field("c.id,o.id as url,p.name as values1,h.dedicatedip as values2,o.status,c.username as values3")->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->whereOr($v, "like", "%{$value}%");
			}
		})->withAttr("status", function ($value, $data) {
			return config("order_status")[$value]["name"];
		})->withAttr("url", function ($value, $data) {
			$url = "#/order-detail?id=" . $value;
			return $url;
		})->where($where)->select()->toArray();
		return $data;
	}
	private function searchInvoice($value, $key, $flag = 0)
	{
		$where = [];
		$param = [];
		if ($flag == 0) {
			if (!empty($key)) {
				$where[] = [$key, "eq", $value];
			} else {
				$param1 = ["i.invoice_num"];
				$param = ["i.id"];
			}
		} else {
			$param = $flag;
		}
		$data = \think\Db::name("Invoices")->alias("i")->join("clients c", "c.id=i.uid")->field("c.id,i.id as url,i.id as values2,c.username as values3,i.status")->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->whereOr($v, "like", "%{$value}%");
			}
		})->withAttr("url", function ($value, $data) {
			$url = "#/bill-detail?id=" . $value;
			return $url;
		});
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$data->whereIn("c.id", $this->getUserCtol->str);
		}
		$data = $data->where($where)->select()->toArray();
		foreach ($data as $key => $value) {
			$data[$key]["values1"] = "账单#";
			$data[$key]["status_color"] = config("invoice_payment_status")[$value["status"]]["color"];
			$data[$key]["status"] = config("invoice_payment_status")[$value["status"]]["name"];
		}
		return $data;
	}
	private function searchTickets($value, $key, $flag = 0)
	{
		$where = [];
		$param = [];
		if ($flag == 0) {
			if (!empty($key)) {
				$where[] = [$key, "eq", $value];
			} else {
				$param1 = ["t.name", "t.title", "t.content", "t.tid"];
				$param = ["t.title", "t.tid"];
			}
		} else {
			$param = $flag;
		}
		$data = \think\Db::name("Ticket")->alias("t")->join("ticket_department td", "td.id=t.dptid")->join("clients c", "c.id=t.uid")->field("c.id,t.id as url,t.tid as values2,t.status,t.title,c.username as values3")->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->whereOr($v, "like", "%{$value}%");
			}
		})->withAttr("url", function ($value, $data) {
			$t = \think\Db::name("Ticket")->field("tid")->where("id", $value)->find();
			$url = "#/support-ticket-detail?id=" . $value . "&tid=" . $t["tid"];
			return $url;
		});
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$data->whereIn("c.id", $this->getUserCtol->str);
		}
		$data = $data->where($where)->select()->toArray();
		foreach ($data as $key => $value) {
			$ts = \think\Db::name("Ticket_status")->field("title,color")->where("id", $value["status"])->find();
			$data[$key]["values1"] = "工单#";
			$data[$key]["values2"] = $value["values2"] . "-" . $value["title"];
			$data[$key]["status_color"] = $ts["color"];
			$data[$key]["status"] = $ts["title"];
		}
		return $data;
	}
	private function searchClients_left($value, $key, $flag = 0, $page, $limit)
	{
		$page = $page ?? config("page");
		$limit = $limit ?? config("limit");
		$where = [];
		$param = [];
		if ($flag == 0) {
			$key = $key[0];
			$where[] = [$key, "=", $value];
		} else {
			$param = $key;
			$key = $key[0];
		}
		$total = \think\Db::name("clients")->alias("cli")->field("cli.id,cli.id as url,cli.username,cli.sale_id,cg.group_colour,cli.companyname,cli.phonenumber,cli.email,cli.create_time,cli.qq,
            cc.status as company_status,cp.status as person_status,wu.id as wechat_id,cli.lastlogin,cli.status as client_status,u.user_nickname")->leftJoin("certifi_company cc", "cc.auth_user_id = cli.id")->leftJoin("certifi_person cp", "cp.auth_user_id = cli.id")->leftJoin("wechat_user wu", "cli.wechat_id = wu.id")->leftJoin("client_groups cg", "cg.id = cli.groupid")->leftJoin("user u", "cli.sale_id=u.id");
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$total->whereIn("cli.id", $this->getUserCtol->str);
		}
		$total = $total->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->where($v, "like", "%{$value}%");
			}
		})->where($where)->count();
		$list = \think\Db::name("clients")->alias("cli")->field("LENGTH(substring_index(" . $key . ",'" . $value . "',1)) as order1,LENGTH(" . $key . ") as order2,cli.id,cli.id as url,cli.username,cli.sale_id,cg.group_colour,cli.companyname,cli.phonenumber,cli.email,cli.create_time,cli.qq,
            cc.status as company_status,cp.status as person_status,wu.id as wechat_id,cli.lastlogin,cli.status as client_status,u.user_nickname")->leftJoin("certifi_company cc", "cc.auth_user_id = cli.id")->leftJoin("certifi_person cp", "cp.auth_user_id = cli.id")->leftJoin("wechat_user wu", "cli.wechat_id = wu.id")->leftJoin("client_groups cg", "cg.id = cli.groupid")->leftJoin("user u", "cli.sale_id=u.id")->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->where($v, "like", "%{$value}%");
			}
		})->withAttr("url", function ($value, $data) {
			$url = "#/customer-view/abstract?id=" . $value;
			return $url;
		})->where($where);
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$list->whereIn("cli.id", $this->getUserCtol->str);
		}
		$list = $list->order("order1,order2")->order("cli.create_time", "desc")->page($page)->limit($limit)->select()->toArray();
		$listFilter = [];
		$sale = array_column(get_sale(), "user_nickname", "id");
		foreach ($list as $key => $value) {
			$host_total = \think\Db::name("host")->where("uid", $value["id"])->count();
			$host_active = \think\Db::name("host")->where("uid", $value["id"])->where("domainstatus", "Active")->count();
			$value["host_total"] = $host_active . "(" . $host_total . ")";
			$value["status"] = $value["client_status"];
			$value["sale"] = $sale[$value["sale_id"]] ?? (object) [];
			$value["client_status"] = config("client_status")[$value["client_status"]]["status"];
			$value["company_status"] = config("client_certifi_status")[$value["company_status"]];
			$value["person_status"] = config("client_certifi_status")[$value["person_status"]];
			$value["wechat_id"] = empty($value["wechat_id"]) ? lang("USER_WECHAT_NO") : lang("USER_WECHAT_IS");
			$listFilter[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $value);
		}
		return ["total" => $total, "data" => $listFilter];
	}
	private function searchClients_left_custom($page, $limit, $custom = [])
	{
		$page = $page ?? config("page");
		$limit = $limit ?? config("limit");
		$obj = \think\Db::name("clients")->alias("cli")->field("cli.id,cli.id as url,cli.username,cli.sale_id,cg.group_colour,cli.companyname,cli.phonenumber,cli.email,cli.create_time,cli.qq,
            cc.status as company_status,cp.status as person_status,wu.id as wechat_id,cli.lastlogin,cli.status as client_status,u.user_nickname")->leftJoin("certifi_company cc", "cc.auth_user_id = cli.id")->leftJoin("certifi_person cp", "cp.auth_user_id = cli.id")->leftJoin("wechat_user wu", "cli.wechat_id = wu.id")->leftJoin("client_groups cg", "cg.id = cli.groupid")->leftJoin("user u", "cli.sale_id=u.id");
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$obj->whereIn("cli.id", $this->getUserCtol->str);
		}
		if (!empty($custom)) {
			$obj = $obj->leftJoin("customfieldsvalues cfv", "cfv.relid = cli.id")->leftJoin("customfields cf", "cf.id = cfv.fieldid")->where("cf.type", "client")->where("cf.id", array_keys($custom)[0])->where("cfv.value", "like", "%" . array_values($custom)[0] . "%");
		}
		$total = $obj->count();
		$list = $obj->withAttr("url", function ($value, $data) {
			$url = "#/customer-view/abstract?id=" . $value;
			return $url;
		})->order("cli.create_time", "desc")->page($page)->limit($limit)->select()->toArray();
		$listFilter = [];
		$sale = array_column(get_sale(), "user_nickname", "id");
		foreach ($list as $key => $value) {
			$host_total = \think\Db::name("host")->where("uid", $value["id"])->count();
			$host_active = \think\Db::name("host")->where("uid", $value["id"])->where("domainstatus", "Active")->count();
			$value["host_total"] = $host_active . "(" . $host_total . ")";
			$value["status"] = $value["client_status"];
			$value["sale"] = $sale[$value["sale_id"]] ?? (object) [];
			$value["client_status"] = config("client_status")[$value["client_status"]]["status"];
			$value["company_status"] = config("client_certifi_status")[$value["company_status"]];
			$value["person_status"] = config("client_certifi_status")[$value["person_status"]];
			$value["wechat_id"] = empty($value["wechat_id"]) ? lang("USER_WECHAT_NO") : lang("USER_WECHAT_IS");
			$listFilter[$key] = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $value);
		}
		return ["total" => $total, "data" => $listFilter];
	}
	private function searchProducts_left($value, $key, $flag = 0, $page, $limit)
	{
		$page = $page ?? config("page");
		$limit = $limit ?? config("limit");
		$where = [];
		$param = [];
		if ($flag == 0) {
			$key = $key[0];
			if ($key == "h.nextduedate") {
				$where[] = ["h.nextduedate", "egt", strtotime(date("Y-m-d", $value))];
				$where[] = ["h.nextduedate", "elt", strtotime(date("Y-m-d 23:59:59", $value))];
			} else {
				$where[] = [$key, "=", $value];
			}
		} elseif ($flag == 1) {
			$param = $key;
			$key = $key[0];
		} else {
			$key = $key[0];
			$where[] = [$key, "in", implode(",", $value)];
		}
		$host_list = \think\Db::name("host")->field("LENGTH(substring_index(" . $key . ",'" . $value . "',1)) as order1,LENGTH(" . $key . ") as order2,cr.type as crtype,cr.reason,h.id,h.initiative_renew,h.id as url,h.domain,h.uid,h.dedicatedip,h.productid,h.billingcycle,h.domain,h.payment,h.nextduedate,h.assignedips,h.regdate,h.dedicatedip,h.amount,h.firstpaymentamount,h.domainstatus,c.username,p.name as productname,c.currency,p.type,h.serverid,u.user_nickname")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("clients c", "c.id=h.uid")->leftJoin("user u", "c.sale_id=u.id")->leftJoin("cancel_requests cr", "cr.relid = h.id")->where($where)->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->where($v, "like", "%{$value}%");
			}
		})->withAttr("url", function ($value, $data) {
			$host = \think\Db::name("host")->alias("a")->field("a.uid")->where("a.id", $value)->find();
			$url = "#/customer-view/product-innerpage?hid=" . $value . "&id=" . $host["uid"];
			return $url;
		});
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$host_list->whereIn("c.id", $this->getUserCtol->str);
		}
		$host_list = $host_list->order("order1,order2")->order("h.create_time", "desc")->page($page)->limit($limit)->select()->toArray();
		$count = \think\Db::name("host")->field("LENGTH(substring_index(" . $key . ",'" . $value . "',1)) as order1,LENGTH(" . $key . ") as order2,h.id,h.id as url,h.domain,h.uid,h.dedicatedip,h.productid,h.billingcycle,h.domain,h.payment,h.nextduedate,h.assignedips,h.regdate,h.dedicatedip,h.amount,h.domainstatus,c.username,p.name as productname,c.currency,p.type,h.serverid,u.user_nickname")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("clients c", "c.id=h.uid")->leftJoin("user u", "c.sale_id=u.id")->leftJoin("cancel_requests cr", "cr.relid = h.id");
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$count->whereIn("c.id", $this->getUserCtol->str);
		}
		$count = $count->where($where)->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->where($v, "like", "%{$value}%");
			}
		})->count();
		$tmp = \think\Db::name("currencies")->select()->toArray();
		$currency = array_column($tmp, null, "id");
		$page_total = 0;
		foreach ($host_list as &$v) {
			$page_total = bcadd($page_total, $v["amount"], 2);
			$v["status_color"] = config("public.domainstatus")[$v["domainstatus"]]["color"];
			$v["assignedips"] = !empty(explode(",", $v["assignedips"])[0]) ? explode(",", $v["assignedips"]) : [];
			$v["domainstatus"] = config("public.domainstatus")[$v["domainstatus"]];
			if ($v["billingcycle"] == "onetime") {
				$v["amount"] = $currency[$v["currency"]]["prefix"] . $v["firstpaymentamount"] . $currency[$v["currency"]]["suffix"];
			} else {
				$v["amount"] = $currency[$v["currency"]]["prefix"] . $v["amount"] . $currency[$v["currency"]]["suffix"];
			}
			if (!empty($v["crtype"])) {
				if ($v["crtype"] == "Immediate") {
					$v["crtype"] = "立即停用";
				} else {
					$v["crtype"] = "到期时停用";
				}
				$v["cancel_list"] = ["crtype" => $v["crtype"], "reason" => $v["reason"]];
			}
		}
		$total = \think\Db::name("host")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("clients c", "c.id=h.uid");
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$total->whereIn("c.id", $this->getUserCtol->str);
		}
		$total = $total->where($where)->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->where($v, "like", "%{$value}%");
			}
		})->sum("h.amount");
		$default_currency = \think\Db::name("currencies")->where("default", 1)->find();
		$prefix = $default_currency["prefix"];
		$suffix = $default_currency["suffix"];
		$total = $prefix . $total . $suffix;
		$page_total = $prefix . $page_total . $suffix;
		$returndata = [];
		$returndata["list"] = $host_list;
		$pagecount = configuration("NumRecordstoDisplay");
		$returndata["pagination"]["pagecount"] = $pagecount;
		$returndata["pagination"]["page"] = $page;
		$returndata["pagination"]["total_page"] = ceil($count / $pagecount);
		$returndata["pagination"]["count"] = $count;
		$returndata["base"]["product_type"] = config("product_type");
		$returndata["base"]["billingcycle"] = config("billing_cycle");
		$returndata["base"]["server_list"] = \think\Db::name("servers")->field("id,name")->select()->toArray();
		$product_groups = \think\Db::name("product_groups")->field("id,name as groupname")->select();
		$product_list = [];
		$i = 0;
		foreach ($product_groups as $key => $val) {
			$groupid = $val["id"];
			$product_list[$i] = $val;
			$product_list[$i]["clild"] = \think\Db::name("products")->field("id,name as productname")->select()->toArray();
			$i++;
		}
		$returndata["base"]["product_list"] = $product_list;
		$returndata["base"]["gateway_list"] = gateway_list("gateways");
		$returndata["base"]["domainstatus"] = config("domainstatus");
		$returndata["list"] = $host_list;
		$returndata["total"] = $total;
		$returndata["page_total"] = $page_total;
		return ["data" => $returndata, "count" => $count];
	}
	private function searchOrders_left($value, $key, $flag = 0, $page, $limit)
	{
		$page = $page ?? config("page");
		$limit = $limit ?? config("limit");
		$where = [];
		$param = [];
		if ($flag == 0) {
			$key = $key[0];
			if ($key == "o.create_time") {
				if ($value == 1) {
					$where[] = ["o.create_time", "egt", strtotime(date("Y-m-d", time()))];
					$where[] = ["o.create_time", "elt", strtotime(date("Y-m-d 23:59:59", time()))];
				} elseif ($value == 2) {
					$where[] = ["o.create_time", "egt", strtotime(date("Y-m-d", time() - 259200))];
					$where[] = ["o.create_time", "elt", strtotime(date("Y-m-d 23:59:59", time()))];
				} elseif ($value == 3) {
					$where[] = ["o.create_time", "egt", strtotime(date("Y-m-d", time() - 604800))];
					$where[] = ["o.create_time", "elt", strtotime(date("Y-m-d 23:59:59", time()))];
				} elseif ($value == 4) {
					$where[] = ["o.create_time", "egt", strtotime(date("Y-m-d", time() - 2592000))];
					$where[] = ["o.create_time", "elt", strtotime(date("Y-m-d 23:59:59", time()))];
				}
			} else {
				$where[] = [$key, "=", $value];
			}
		} elseif ($flag == 1) {
			$param = $key;
			$key = $key[0];
		} else {
			$key = $key[0];
			$where[] = [$key, "in", implode(",", $value)];
		}
		$gateways = gateway_list();
		$rows = \think\Db::name("orders")->alias("o")->leftjoin("clients c", "o.uid=c.id")->leftjoin("currencies cu", "cu.id = c.currency")->leftJoin("user u", "u.id = c.sale_id")->leftJoin("invoices i", "o.invoiceid = i.id")->field("LENGTH(substring_index(" . $key . ",'" . $value . "',1)) as order1,LENGTH(" . $key . ") as order2,c.username,c.companyname,o.id,o.id as url,o.uid,c.sale_id,o.status,o.ordernum,i.status as i_status,i.subtotal as sub,i.credit,i.use_credit_limit,o.create_time,o.invoiceid,o.amount,o.amount as am,o.payment,cu.prefix,cu.suffix,u.user_nickname")->withAttr("payment", function ($value, $data) use($gateways) {
			if ($data["i_status"] == "Paid") {
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
		})->withAttr("amount", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->where($v, "like", "%{$value}%");
			}
		})->withAttr("url", function ($value, $data) {
			$url = "#/order-detail?id=" . $value;
			return $url;
		});
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$rows->whereIn("c.id", $this->getUserCtol->str);
		}
		$rows = $rows->where("o.delete_time", 0)->where($where)->order("order1,order2")->order("o.create_time", "desc")->page($page)->limit($limit)->select()->toArray();
		$price_total = \think\Db::name("orders")->alias("o")->leftjoin("clients c", "o.uid=c.id")->leftjoin("currencies cu", "cu.id = c.currency")->leftJoin("user u", "u.id = c.sale_id")->leftJoin("invoices i", "o.invoiceid = i.id")->field("o.id,c.sale_id,o.invoiceid,cu.prefix,cu.suffix,o.amount")->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->where($v, "like", "%{$value}%");
			}
		});
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$price_total->whereIn("c.id", $this->getUserCtol->str);
		}
		$price_total = $price_total->where($where)->where("o.delete_time", 0)->sum("o.amount");
		$billingcycle = config("billing_cycle");
		$order_status = config("order_status");
		$invoice_payment_status = config("invoice_payment_status");
		$price_total_page = 0;
		foreach ($rows as $k => $row) {
			$price_total_page = bcadd($row["am"], $price_total_page, 2);
			$invoices = \think\Db::name("invoices")->field("status,type,subtotal")->where("id", $row["invoiceid"])->where("delete_time", 0)->find();
			$invoice_status = $invoices["status"];
			$invoice_type = $invoices["type"];
			$exist_upgrade_order = \think\Db::name("upgrades")->where("order_id", $row["id"])->find();
			if (!empty($exist_upgrade_order)) {
				$invoice_type = "upgrade";
			}
			$hosts = [];
			if ($invoice_type == "zjmf_flow_packet") {
				$hosts[] = ["hostid" => 0, "name" => "流量包", "firstpaymentamount" => $row["prefix"] . $invoices["subtotal"] . $row["suffix"], "billingcycle" => " -"];
			} elseif ($invoice_type == "zjmf_reinstall_times") {
				$hosts[] = ["hostid" => 0, "name" => "重装次数", "firstpaymentamount" => $row["prefix"] . $invoices["subtotal"] . $row["suffix"], "billingcycle" => " -"];
			} else {
				$hosts = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid = b.id")->leftJoin("upgrades u", "u.relid = a.id")->leftJoin("cancel_requests cr", "cr.relid = a.id")->field("cr.type,cr.reason,a.initiative_renew,a.id as hostid,b.id as invoice_type,b.name,a.domain,a.dedicatedip,a.billingcycle,a.firstpaymentamount,a.productid")->distinct(true)->where(function (\think\db\Query $query) use($row) {
					$query->where("a.orderid", $row["id"]);
					$query->whereOr("u.order_id", $row["id"]);
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
			$rows[$k]["hosts"] = $hosts;
			$rows[$k]["order_status"] = $order_status[$row["status"]];
			if (empty($row["invoiceid"]) || empty($invoice_status)) {
				$rows[$k]["pay_status"] = ["name" => lang("NO_INVOICE"), "color" => "#2F4F4F"];
				$rows[$k]["pay_status_tmp"] = "0";
			} else {
				$rows[$k]["pay_status"] = $invoice_payment_status[$invoice_status];
				$rows[$k]["pay_status_tmp"] = $invoice_status;
			}
		}
		$count = \think\Db::name("orders")->alias("o")->leftjoin("clients c", "o.uid=c.id")->field("c.username,o.id")->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->where($v, "like", "%{$value}%");
			}
		});
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$count->whereIn("c.id", $this->getUserCtol->str);
		}
		$count = $count->where("o.delete_time", 0)->where($where)->count();
		return ["data" => $rows, "count" => $count, "price_total" => $price_total, "price_total_page" => $price_total_page];
	}
	private function searchInvoice_left($value, $key, $flag = 0, $page, $limit)
	{
		$page = $page ?? config("page");
		$limit = $limit ?? config("limit");
		$where = [];
		$param = [];
		if ($flag == 0) {
			$key = $key[0];
			if ($key == "i.paid_time") {
				$where[] = ["i.paid_time", "egt", strtotime(date("Y-m-d", $value))];
				$where[] = ["i.paid_time", "elt", strtotime(date("Y-m-d 23:59:59", $value))];
			} else {
				$where[] = [$key, "=", $value];
			}
		} elseif ($flag == 1) {
			$param = $key;
			$key = $key[0];
		} else {
			$key = $key[0];
			$where[] = [$key, "in", implode(",", $value)];
		}
		$type_arr = config("invoice_type_all");
		$gateways = gateway_list();
		$invoice_payment_status = config("invoice_payment_status");
		$sale = array_column(get_sale(), "user_nickname", "id");
		$rows = \think\Db::name("invoices")->alias("i")->field("LENGTH(substring_index(" . $key . ",'" . $value . "',1)) as order1,LENGTH(" . $key . ") as order2,c.username,i.id,i.id as url,i.uid,i.invoice_num,i.create_time,i.due_time,i.subtotal,i.subtotal as sub,i.total,cu.prefix,cu.suffix,i.status,i.payment,i.credit,i.type,i.use_credit_limit,c.sale_id,i.paid_time")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->withAttr("subtotal", function ($value, $data) {
			return $data["prefix"] . $value . $data["suffix"];
		})->withAttr("payment", function ($value, $data) use($gateways) {
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
		})->withAttr("status", function ($value) use($invoice_payment_status) {
			return $invoice_payment_status[$value];
		})->withAttr("type", function ($value) use($type_arr) {
			return $type_arr[$value];
		})->withAttr("sale_id", function ($value) use($sale) {
			return $sale[$value];
		})->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->where($v, "like", "%{$value}%");
			}
		})->withAttr("url", function ($value, $data) {
			$url = "#/bill-detail?id=" . $value;
			return $url;
		});
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$rows->whereIn("c.id", $this->getUserCtol->str);
		}
		$rows = $rows->where("i.delete_time", 0)->where($where)->order("order1,order2")->order("i.create_time", "desc")->page($page)->limit($limit)->select()->toArray();
		$count = \think\Db::name("invoices")->alias("i")->field("c.username,i.id,i.id as url,i.uid,i.invoice_num,i.create_time,i.due_time,i.subtotal,i.subtotal as sub,i.total,cu.prefix,cu.suffix,i.status,i.payment,i.credit,i.type,c.sale_id")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->where($v, "like", "%{$value}%");
			}
		});
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$count->whereIn("c.id", $this->getUserCtol->str);
		}
		$count = $count->where("i.delete_time", 0)->where($where)->count();
		$sums1 = 0;
		foreach ($rows as $key => $value) {
			$sums1 = bcadd($value["sub"], $sums1);
		}
		$rows1 = \think\Db::name("invoices")->alias("i")->field("c.username,i.id,i.uid,i.invoice_num,i.create_time,i.due_time,i.subtotal,cu.prefix,cu.suffix,i.status,i.payment,i.credit,i.type,i.paid_time")->leftjoin("clients c", "c.id=i.uid")->leftJoin("currencies cu", "cu.id = c.currency")->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->where($v, "like", "%{$value}%");
			}
		});
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$rows1->whereIn("c.id", $this->getUserCtol->str);
		}
		$rows1 = $rows1->where("i.delete_time", 0)->where($where)->select();
		$sums2 = 0;
		foreach ($rows1 as $key => $value) {
			$sums2 = bcadd($value["subtotal"], $sums2);
		}
		return ["data" => $rows, "price" => $sums1, "totalprice" => $sums2, "count" => $count];
	}
	private function searchTickets_left($value, $key, $flag = 0, $page, $limit)
	{
		$page = $page ?? config("page");
		$limit = $limit ?? config("limit");
		$where = [];
		$param = [];
		if ($flag == 0) {
			$key = $key[0];
			$where[] = [$key, "=", $value];
		} elseif ($flag == 1) {
			$param = $key;
			$key = $key[0];
		} else {
			$key = $key[0];
			$where[] = [$key, "in", implode(",", $value)];
		}
		$data = \think\Db::name("ticket")->alias("a")->field("LENGTH(substring_index(" . $key . ",'" . $value . "',1)) as order1,LENGTH(" . $key . ") as order2,a.id,a.id as url,a.tid,a.uid,a.title,a.name,a.status,a.create_time,t.color as statusColor,t.title as status_title,a.last_reply_time,b.user_login flag_admin,c.name department_name,d.username user_name")->leftJoin("user b", "a.flag=b.id")->leftJoin("ticket_department c", "a.dptid=c.id")->leftJoin("clients d", "a.uid=d.id")->leftJoin("ticket_status t", "a.status = t.id")->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->where($v, "like", "%{$value}%");
			}
		})->withAttr("url", function ($value, $data) {
			$t = \think\Db::name("Ticket")->field("tid")->where("id", $value)->find();
			$url = "#/support-ticket-detail?id=" . $value . "&tid=" . $t["tid"];
			return $url;
		});
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$data->whereIn("d.id", $this->getUserCtol->str);
		}
		$data = $data->where($where)->order("order1,order2")->order("a.create_time", "desc")->page($page)->limit($limit)->select()->toArray();
		$count = \think\Db::name("ticket")->alias("a")->field("a.id,a.id as url,a.tid,a.uid,a.title,a.name,a.status,a.create_time,t.color as statusColor,t.title as status_title,a.last_reply_time,b.user_login flag_admin,c.name department_name,d.username user_name")->leftJoin("user b", "a.flag=b.id")->leftJoin("ticket_department c", "a.dptid=c.id")->leftJoin("clients d", "a.uid=d.id")->leftJoin("ticket_status t", "a.status = t.id")->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->where($v, "like", "%{$value}%");
			}
		});
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$count->whereIn("d.id", $this->getUserCtol->str);
		}
		$count = $count->where($where)->count();
		foreach ($data as $k => $v) {
			$data[$k]["user_name"] = !empty($v["uid"]) ? $v["user_name"] : $v["name"];
			$data[$k]["format_time"] = date("Y-m-d H:i:s", $v["last_reply_time"]);
			$data[$k]["flag_admin"] = $v["flag_admin"] ?: "";
			unset($data[$k]["name"]);
		}
		$status = \think\Db::name("ticket_status")->select();
		return ["ticket_status" => $status, "data" => $data, "sum" => $count, "max_page" => ceil($count / $limit), "page" => $page];
	}
	private function searchOrderspost_left($value, $key, $flag = 0, $page, $limit)
	{
		$page = $page ?? config("page");
		$limit = $limit ?? config("limit");
		$where = [];
		$param = [];
		if ($flag == 0) {
			$key = $key[0];
			if ($key == "o.create_time") {
				if ($value == 1) {
					$where[] = ["o.create_time", "egt", strtotime(date("Y-m-d", time()))];
					$where[] = ["o.create_time", "elt", strtotime(date("Y-m-d 23:59:59", time()))];
				} elseif ($value == 2) {
					$where[] = ["o.create_time", "egt", strtotime(date("Y-m-d", time() - 259200))];
					$where[] = ["o.create_time", "elt", strtotime(date("Y-m-d 23:59:59", time()))];
				} elseif ($value == 3) {
					$where[] = ["o.create_time", "egt", strtotime(date("Y-m-d", time() - 604800))];
					$where[] = ["o.create_time", "elt", strtotime(date("Y-m-d 23:59:59", time()))];
				} elseif ($value == 4) {
					$where[] = ["o.create_time", "egt", strtotime(date("Y-m-d", time() - 2592000))];
					$where[] = ["o.create_time", "elt", strtotime(date("Y-m-d 23:59:59", time()))];
				}
			} else {
				$where[] = [$key, "=", $value];
			}
		} elseif ($flag == 1) {
			$param = $key;
			$key = $key[0];
		} else {
			$key = $key[0];
			$where[] = [$key, "in", implode(",", $value)];
		}
		$rows = \think\Db::name("orders")->alias("o")->leftjoin("clients c", "o.uid=c.id")->leftjoin("currencies cu", "cu.id = c.currency")->leftJoin("user u", "u.id = c.sale_id")->leftJoin("invoices i", "o.invoiceid = i.id")->field("LENGTH(substring_index(" . $key . ",'" . $value . "',1)) as order1,LENGTH(" . $key . ") as order2,o.id,c.sale_id,o.invoiceid,cu.prefix,cu.suffix,o.amount as am")->where($where)->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->where($v, "like", "%{$value}%");
			}
		});
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$rows->whereIn("c.id", $this->getUserCtol->str);
		}
		$rows = $rows->order("order1,order2")->order("o.create_time", "desc")->page($page)->limit($limit)->where("o.delete_time", 0)->select()->toArray();
		$row1 = \think\Db::name("orders")->alias("o")->leftjoin("clients c", "o.uid=c.id")->leftjoin("currencies cu", "cu.id = c.currency")->leftJoin("user u", "u.id = c.sale_id")->leftJoin("invoices i", "o.invoiceid = i.id")->field("LENGTH(substring_index(" . $key . ",'" . $value . "',1)) as order1,LENGTH(" . $key . ") as order2,o.id,c.sale_id,o.invoiceid,cu.prefix,cu.suffix,o.amount")->where($where)->where("o.delete_time", 0)->where(function (\think\db\Query $query) use($param, $value) {
			foreach ($param as $v) {
				$query->where($v, "like", "%{$value}%");
			}
		});
		if ($this->getUserCtol->user["id"] != 1 && $this->getUserCtol->user["is_sale"]) {
			$row1->whereIn("c.id", $this->getUserCtol->str);
		}
		$row1 = $row1->order("order1,order2")->order("o.create_time", "desc")->select()->toArray();
		$sums1 = 0;
		foreach ($row1 as $k => $row) {
			$sums1 = bcadd($row["amount"], $sums1);
		}
		$sums = 0;
		foreach ($rows as $k => &$row) {
			$sums = bcadd($row["am"], $sums);
			$invoices = \think\Db::name("invoices")->field("status,type")->where("id", $row["invoiceid"])->where("delete_time", 0)->find();
			$invoice_status = $invoices["status"];
			$invoice_type = $invoices["type"];
			$row["type"] = $invoices["type"];
			if ($invoice_type == "upgrade") {
				$hosts = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid = b.id")->leftJoin("sale_products c", "c.pid = b.id")->leftJoin("sales_product_groups d", "d.id = c.gid")->leftJoin("upgrades u", "u.relid = a.id")->field("a.id as hostid,b.name,a.billingcycle,u.id as upid,a.firstpaymentamount,a.productid,d.bates,d.renew_bates,d.upgrade_bates,d.is_renew,d.updategrade")->distinct(true)->where(function (\think\db\Query $query) use($row) {
					$query->where("a.orderid", $row["id"]);
					$query->whereOr("u.order_id", $row["id"]);
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
				$hosts = \think\Db::name("invoices")->alias("i")->leftJoin("invoice_items im", "im.invoice_id = i.id")->leftJoin("host a", "a.id = im.rel_id")->leftJoin("products b", "a.productid = b.id")->leftJoin("sale_products c", "c.pid = b.id")->leftJoin("sales_product_groups d", "d.id = c.gid")->field("a.id as hostid,b.name,a.billingcycle,a.firstpaymentamount")->field("a.productid,d.bates,d.renew_bates,d.upgrade_bates,d.is_renew,d.updategrade")->distinct(true)->where("i.id", $row["invoiceid"])->withAttr("billingcycle", function ($value) {
					return config("billing_cycle")[$value];
				})->withAttr("firstpaymentamount", function ($value) use($row) {
					return $row["prefix"] . $value . $row["suffix"];
				})->withAttr("name", function ($value, $data) use($invoice_type) {
					if ($invoice_type == "upgrade") {
						return "[升降级]" . $value;
					} else {
						return $value;
					}
				})->where("i.delete_time", 0)->order("a.id", "desc")->select()->toArray();
			}
			$sum = 0;
			$sum1 = 0;
			$refund = 0;
			$refund1 = 0;
			$wheres = [];
			$order = new OrderController();
			$ladder = $order->getLadder($row["sale_id"]);
			$rows[$k]["ladder"] = $ladder;
			if ($invoice_status == "Paid") {
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
		return ["data" => $rows, "price" => $sums, "totalprice" => $sums1];
	}
	/**
	 * 时间 2020/9/08 16:48
	 * @title 更改配置文件
	 * @desc void
	 * @param .name:type type:int require:0 default:1 other: desc:0关闭1开启
	 * @url editlog
	 * @method  get
	 * @author lgd
	 * @version v1
	 */
	public function editLog()
	{
		$datas = $this->request->param();
		$type = trim($datas["type"]);
		if ($type == 1) {
			config("log.close", true);
		} else {
			config("log.close", false);
		}
		return jsons(["status" => 200, "msg" => "success"]);
	}
	/**
	 * 时间 2020/9/08 16:48
	 * @title 条件搜索页面
	 * @desc void
	 * @url tablelist
	 * @method  get
	 * @return  array list - 搜索返回列表
	 * @author lgd
	 * @version v1
	 */
	public function tableList()
	{
		$users = (new \app\common\model\ClientModel())->clientList("id as value,username as label");
		$data1["clientlist"] = $users;
		$list = \think\Db::name("user")->field("id as value,user_nickname as label")->where("is_sale", 1)->select()->toArray();
		$data1["salelist"] = $list;
		$list = \think\Db::name("client_groups")->field("id as value,group_name as label")->select()->toArray();
		$data1["client_group"] = $list;
		$type_arr = config("billing_cycle");
		$arr = [];
		foreach ($type_arr as $k => $v) {
			$arr[] = ["value" => $k, "label" => $v];
		}
		$data1["product_billingcycle"] = $arr;
		$product_groups = \think\Db::name("product_groups")->field("id as value,name as label")->select();
		$product_list = [];
		$i = 0;
		foreach ($product_groups as $key => $val) {
			$groupid = $val["value"];
			$product_list[$i] = $val;
			$product_list[$i]["clild"] = \think\Db::name("products")->field("id as value,name as label")->where("gid", $groupid)->select()->toArray();
			$i++;
		}
		$data1["product_list"] = $product_list;
		$type_arr = config("domainstatus");
		$arr = [];
		foreach ($type_arr as $k => $v) {
			$arr[] = ["value" => $k, "label" => $v];
		}
		$data1["product_domainstatus"] = $arr;
		$type_arr = config("product_type");
		$arr = [];
		foreach ($type_arr as $k => $v) {
			$arr[] = ["value" => $k, "label" => $v];
		}
		$data1["product_type"] = $arr;
		$status = \think\Db::name("ticket_status")->field("id as value,title as label")->select()->toArray();
		$data1["Ticket_status"] = $status;
		$ids = model("TicketDepartmentAdmin")->getAllow();
		$list = \think\Db::name("ticket_department")->field("id as value,name as label")->where("id", "in", $ids)->select();
		$data1["Ticket_department"] = $list;
		$type_arr = config("invoice_type_all");
		$arr = [];
		foreach ($type_arr as $k => $v) {
			$arr[] = ["value" => $k, "label" => $v];
		}
		$data1["invoice_type"] = $arr;
		$type_arr = config("invoice_payment_status");
		$arr = [];
		foreach ($type_arr as $k => $v) {
			$arr[] = ["value" => $k, "label" => $v["name"]];
		}
		$data1["Invoice_status"] = $arr;
		$time_type = config("time_type1");
		$time_types = [];
		foreach ($time_type as $k => $v) {
			$time_types[$k] = ["value" => $v["nextduedate"], "label" => $v["name"]];
		}
		$data1["Order_time_type"] = $time_types;
		return jsons(["status" => 200, "name" => $this->data2, "data" => $this->data, "search" => $data1]);
	}
	/**
	 * 时间 2020/9/08 16:48
	 * @title 条件搜索
	 * @desc void
	 * @url searchfornamelist
	 * @param .name:table type:string require:0 default:1 other: desc:表名
	 * @param .name:key type:string require:0 default:1 other: desc:全局搜索字段
	 * @param .name:value type:string require:0 default:1 other: desc:全局搜索字段
	 * @param .name:type type:string require:0 default:1 other: desc:精确还是多选
	 * @param .name:customfields[自定义字段id(即返回的ab_name的值)] type:array require:0 default:1 other: desc:值
	 * @method  post
	 * @return  array list - 搜索返回列表
	 * @author lgd
	 * @version v1
	 */
	public function searchfornameList()
	{
		$datas = $this->request->param();
		$table = trim($datas["table"]);
		$key = trim($datas["key"]);
		$value = $datas["value"];
		$flag = $datas["flag"];
		$page = $datas["page"] ?? config("page");
		$limit = $datas["limit"] ?? config("limit");
		$name = array_column($this->data[$table], "ab_name");
		$values = array_keys($name, $key);
		$key = $this->data[$table][$values[0]]["name"];
		$type = $this->data[$table][$values[0]]["type"];
		if ($type == 2) {
			if (empty($value[0]) || $value[0] == "") {
				return jsons(["status" => 400, "data" => []]);
			}
		} else {
			if (empty($value) || $value == "") {
				return jsons(["status" => 400, "data" => []]);
			}
		}
		if (empty($key) || $key == "") {
			return jsons(["status" => 400, "data" => []]);
		}
		if (empty($table) || $table == "") {
			return jsons(["status" => 400, "data" => []]);
		}
		$data = [];
		if ($table == "Client") {
			if (isset($datas["customfields"])) {
				$custom = $datas["customfields"];
			} else {
				$custom = [];
			}
			if (!empty($custom)) {
				$data = $this->searchClients_left_custom($page, $limit, $custom);
			} else {
				$data = $this->searchClients_left($value, [$key], $type, $page, $limit);
			}
			return jsonrule(["status" => 200, "total" => $data["total"], "list" => $data["data"]]);
		} elseif ($table == "Products") {
			$data = $this->searchProducts_left($value, [$key], $type, $page, $limit);
			return jsonrule(["data" => $data["data"], "status" => 200]);
		} elseif ($table == "Invoice") {
			$data = $this->searchInvoice_left($value, [$key], $type, $page, $limit);
			return jsonrule(["data" => $data["data"], "status" => 200, "price" => $data["price"], "totalprice" => $data["totalprice"], "count" => $data["count"]]);
		} elseif ($table == "Orders") {
			if ($flag == 0) {
				$data = $this->searchOrders_left($value, [$key], $type, $page, $limit);
				return jsonrule(["list" => $data["data"], "status" => 200, "count" => $data["count"], "price_total" => $data["price_total"], "price_total_page" => $data["price_total_page"]]);
			} else {
				$data = $this->searchOrderspost_left($value, [$key], $type, $page, $limit);
				return jsonrule(["list" => $data["data"], "status" => 200, "price" => $data["price"], "totalprice" => $data["totalprice"]]);
			}
		} elseif ($table == "Tickets") {
			$data = $this->searchTickets_left($value, [$key], $type, $page, $limit);
			return jsonrule(["data" => $data["data"], "status" => 200, "ticket_status" => $data["ticket_status"], "sum" => $data["sum"], "max_page" => $data["max_page"], "page" => $data["page"]]);
		}
		return jsons(["status" => 200, "data" => $data]);
	}
	/**
	 * 时间 2020/9/08 16:48
	 * @title 条件搜索页面
	 * @desc void
	 * @url namelist
	 * @param .name:value type:string require:0 default:1 other: desc:搜索字段
	 * @method  get
	 * @return  array list - 搜索返回列表
	 * @author lgd
	 * @version v1
	 */
	public function nameList()
	{
		$email = new \app\common\logic\Email();
		$email->is_admin = true;
		$phone = 1234567;
		$ret = sendmsglimit($phone);
		if ($ret["status"] == 200) {
			var_dump($ret["msg"]);
		} else {
			var_dump($ret["msg"]);
		}
		exit;
		$datas = $this->request->param();
		$value = trim($datas["value"]);
		if (empty($value) || $value == "") {
			return jsons(["status" => 400, "data" => []]);
		}
		if ($value == "Client") {
			$data[] = ["c.id" => "ClientID", "c.username" => "姓名", "c.email" => "邮箱", "c.qq" => "qq", "c.country" => "国家", "c.province" => "省", "c.city" => "城市", "c.region" => "区域", "c.address1" => "详细地址", "c.postcode" => "邮编", "c.phonenumber" => "手机", "c.credit" => "余额", "c.ip" => "ip", "c.lastloginip" => "最近登陆ip", "cl.bank" => "银行", "cl.certifi_name" => "真实姓名", "cl.company_name" => "公司名字", "cl.idcard" => "身份证号", "cl.company_organ_code" => "营业执照"];
		} elseif ($value == "Products") {
			$data[] = ["h.id" => "HostID", "s.id" => "ServiceID", "p.id" => "ProductID", "s.name" => "ServiceName", "p.name" => "产品名", "p.description" => "产品描述", "h.domain" => "主机名", "h.firstpaymentamount" => "第一次支付价格", "h.amount" => "续费价格", "h.username" => "用户名", "h.notes" => "备注", "h.suspendreason" => "暂停原因", "h.dedicatedip" => "独立ip地址", "h.assignedips" => "分配的ip地址", "h.ns1" => "域名服务器1", "h.ns2" => "域名服务器2", "h.os" => "操作系统", "h.port" => "端口"];
		} elseif ($value == "Invoice") {
			$data[] = ["i.id" => "InvoiceID", "i.invoice_num" => "账单编号", "i.subtotal" => "总付款金额", "i.credit" => "用余额付款金额", "i.total" => "剩余付款金额"];
		} elseif ($value == "Orders") {
			$data[] = ["o.id" => "OrderID", "o.ordernum" => "订单编号", "o.amount" => "订单金额"];
		} elseif ($value == "Tickets") {
			$data[] = ["t.name" => "姓名", "t.email" => "邮箱", "t.title" => "标题", "t.content" => "内容"];
		}
		return jsons(["status" => 200, "data" => $data]);
	}
}