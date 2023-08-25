<?php

namespace app\admin\controller;

/**
 * @title 基础统计信息
 * @description 接口说明:基础统计信息：显示去年和今年的每个月收入,画图和表，参看whmcs，使用baidu的echartjs
 */
class ReportController extends AdminBaseController
{
	private $return_array_param;
	/**
	 * @title 基础信息
	 * @description 接口说明:基础信息:每天的新注册/新订单/已付款订单/已付款账单
	 * @author wyh
	 * @url /admin/report/base_info
	 * @method GET
	 *
	 * @param .name:start_time type:int require:0 default:七天前 零点 other: desc:开始时间
	 * @param .name:end_time type:int require:0 default:今日 other: desc:结束时间
	 *
	 *
	 * @return last_version:最新版本
	 * @return install_version:当前版本
	 * @return install_version:当前版本
	 * @return latest_error_crond_time:最近一次   自动任务状态异常 上次任务结束时间
	 * @return modules:模块信息@
	 *
	 * @modules  modules_name:模块名称@
	 * @modules_name  todaytotal:今日收入
	 * @modules_name  thismonth:本月收入
	 * @modules_name  amounts:总收入
	 * @modules_name  latest_order_count:近7天收入
	 *
	 */
	public function baseInfo()
	{
		$start_time = $this->request->param("start_time");
		$end_time = $this->request->param("end_time");
		$upgrade_system_logic = new \app\common\logic\UpgradeSystem();
		$system["install_version"] = getZjmfVersion();
		$system["last_version"] = $upgrade_system_logic->getLastVersion();
		$system["cron_last_run_time_over"] = configuration("cron_last_run_time_over");
		if (configuration("cron_last_run_time_over") == configuration("cron_last_run_time") && configuration("cron_last_run_time_over_in") != configuration("cron_last_run_time")) {
			$system["diff_run_time"] = -1;
		} else {
			$system["diff_run_time"] = 1;
		}
		$this->return_array_param = 1;
		$show_modules = $this->getSystemInfoModulesList();
		if ($show_modules) {
			foreach ($show_modules as $item) {
				if ($item["name"] && $item["desc"]) {
					$call_method = "get_" . $item["name"];
					$system["modules"][$item["name"]] = $this->{$call_method}($start_time, $end_time);
				}
			}
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $system]);
	}
	/**
	 * @title 获取信息系统展示模块列表
	 * @description
	 * @author wyh
	 * @url /admin/report/get_base_module
	 * @method GET
	 */
	public function getSystemInfoModulesList()
	{
		$return_array_param = $this->return_array_param;
		$return_array = $this->request->param("return_array");
		$return_array = $return_array_param ?? intval($return_array);
		$lists = \think\Db::name("base_info")->field("id,name,desc,enable,sort")->where("delete_time", 0)->order("sort", "asc")->order("id", "asc")->select()->toArray();
		$admin_id = cmf_get_current_admin_id();
		$user = \think\Db::name("role_user")->where("user_id", $admin_id)->field("role_id")->find();
		if ($admin_id != 1 && $user["role_id"] != 1) {
			$accesses = \think\Db::name("auth_access")->alias("a")->field("a.rule_name")->leftJoin("role_user b", "a.role_id = b.role_id")->where("b.user_id", $admin_id)->select()->toArray();
			$accesses = array_column($accesses, "rule_name");
			$lists_filter = [];
			$module = $this->request->module();
			$controller = $this->request->controller();
			foreach ($lists as $k => $list) {
				$action = "get_" . $list["name"];
				$rule = "app\\" . $module . "\\controller\\" . $controller . "Controller::" . $action;
				if (in_array($rule, $accesses)) {
					$lists_filter[] = $list;
				}
			}
			$lists = $lists_filter;
		}
		if ($return_array) {
			return $lists;
		} else {
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $lists]);
		}
	}
	/**
	 * @title 修改信息系统展示模块顺序
	 * @description  参数二维数组，分别有字段：id，sort，enable
	 * @url /admin/report/update_base_module
	 * @method post
	 */
	public function updateSystemInfoModulesSort()
	{
		$input = $this->request->param("modules");
		$result = false;
		if ($input) {
			$base_info_model = new \app\admin\model\BaseInfoModel();
			$result = $base_info_model->saveAll($input);
		} else {
			$result = true;
		}
		if ($result === false) {
			return jsonrule(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		} else {
			return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => []]);
		}
	}
	/**
	 * 获取 待处理工单 数据
	 */
	private function get_waiting_ticket()
	{
		$admin_id = session("ADMIN_ID");
		$dptids = \think\Db::name("ticket_department_admin")->where("admin_id", $admin_id)->column("dptid");
		$ticket = \think\Db::name("ticket")->alias("t")->field("t.id, t.tid,t.uid,t.title,c.username,t.create_time")->leftJoin("clients c", "c.id = t.uid")->where("t.status", "in", "1,3")->where("t.merged_ticket_id", 0)->where("dptid", "in", $dptids)->limit(5)->order("id desc")->select()->toArray();
		if ($ticket) {
			foreach ($ticket as &$item) {
				$item["create_time"] = friend_date($item["create_time"]);
			}
		}
		return $ticket;
	}
	/**
	 * 获取 本月员工销售排行 数据
	 */
	private function get_staff_sales_ranking()
	{
		$sale_list = \think\Db::name("user")->field("id,user_nickname as sale_username")->where("is_sale", 1)->select()->toArray();
		$rank = [];
		$SaleController = new SaleController();
		foreach ($sale_list as &$item) {
			$item["total_amount"] = $SaleController->getLaddersaleStatisticsOnlyTotalAccount("month", $item["id"]);
		}
		$list_total = array_column($sale_list, "total_amount");
		array_multisort($list_total, SORT_DESC, $sale_list);
		foreach ($sale_list as $key => $item) {
			if ($key >= 5) {
				unset($sale_list[$key]);
			}
		}
		return $sale_list;
	}
	/**
	 * 获取 在线管理员 数据
	 */
	private function get_online_admin()
	{
		$user = \think\Db::name("user")->field("user_login as  admin_name,avatar,last_act_time")->where("user_status", 1)->where("last_act_time", ">=", time() - 3600)->order("last_act_time", "desc")->select()->toArray();
		if ($user) {
			foreach ($user as &$item) {
				$item["last_act_time"] = friend_date($item["last_act_time"]);
				$item["avatar"] = base64EncodeImage($item["avatar"]);
			}
		}
		return $user;
	}
	/**
	 * 获取 系统日志 数据
	 */
	private function get_system_log()
	{
		$log_list = \think\Db::name("activity_log")->field("create_time,id,ipaddr,description as new_desc,uid,user")->withAttr("create_time", function ($value, $data) {
			return date("Y-m-d H:i", $value);
		})->withAttr("new_desc", function ($value, $data) {
			$pattern = "/(?P<name>\\w+ ID):(?P<digit>\\d+)/";
			preg_match_all($pattern, $value, $matches);
			$name = $matches["name"];
			$digit = $matches["digit"];
			if (!empty($name)) {
				foreach ($name as $k => $v) {
					$relid = $digit[$k];
					$str = $v . ":" . $relid;
					if ($v == "Invoice ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/bill-detail?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "User ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-view/abstract?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: \">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "FlowPacket ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/dcim-traffic?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Host ID") {
						$host = \think\Db::name("host")->alias("a")->field("a.uid")->where("a.id", $relid)->find();
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-view/product-innerpage?hid=" . $relid . "&id=" . $host["uid"] . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Promo_codeID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/promo-code-add?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Order ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/order-detail?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Admin ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/admin-edit?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Contacts ID") {
					} elseif ($v == "News ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/add-news?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "TD ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/new-work-order-dept?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Ticket ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/support-ticket?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Product ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/edit-product?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "IP") {
					} elseif ($v == "PCG ID") {
						$pco = \think\Db::name("product_config_options")->where("id", $relid)->find();
						$url = "<a class=\"el-link el-link--primary is-underline\" target=\"blank\" href=\"#/edit-configurable-option1?groupId=" . $pco["gid"] . "&optionId=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Service ID") {
						$server = \think\Db::name("servers")->where("id", $relid)->find();
						if ($server["server_type"] == "dcimcloud") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/zjmfcloud\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} elseif ($server["server_type"] == "dcim") {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/dcim\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						} else {
							$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/add-server?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
							$value = str_replace($str, $url, $value);
						}
					} elseif ($v == "Create ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/balance-details?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Transaction ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/business-statement?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Role ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/permissions-edit?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Group ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/customer-group?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					} elseif ($v == "Currency ID") {
						$url = "<a class=\"el-link el-link--primary is-underline\" href=\"#/currency-settings?id=" . $relid . "\"><span class=\"el-link--inner\" style=\"display: block;\">" . $str . "</span></a>";
						$value = str_replace($str, $url, $value);
					}
				}
				return $value;
			} else {
				return $value;
			}
		})->order("id", "DESC")->limit(5)->select()->toArray();
		return $log_list;
	}
	/**
	 * 获取 本月销售排行 数据
	 */
	private function get_sales_ranking()
	{
		$data = \think\Db::name("orders")->alias("o")->join("host h", "h.orderid = o.id")->join("products p", "h.productid = p.id")->field("p.id,p.name,sum(o.amount) as total_amount")->whereTime("o.create_time", "month")->where("p.id", ">", 0)->group("p.id")->order("total_amount", "desc")->limit(5)->select()->toArray();
		return $data;
	}
	/**
	 * 获取即将过期数据
	 */
	private function get_expiring()
	{
		$product_type = config("product_type");
		$expiring = [];
		$expiring = \think\Db::name("host")->alias("h")->field("count(*) as expiring_count,p.type")->join("products p", "p.id = h.productid")->where("h.nextduedate", "between", [time(), strtotime("+7 days")])->where("h.domainstatus", "Active")->group("p.type")->select()->toArray();
		foreach ($expiring as $k => $v) {
			$expiring[$k]["key"] = $v["type"];
			$expiring[$k]["name"] = $product_type[$v["type"]];
			if (empty($product_type[$v["type"]])) {
				unset($expiring[$k]);
			}
		}
		return $expiring;
	}
	/**
	 * 获取客户统计概览数据
	 */
	private function get_client()
	{
		$today_start = strtotime(date("Y-m-d 00:00:00"));
		$today_end = strtotime(date("Y-m-d 23:59:59"));
		$today_add_client_count = \think\Db::name("clients")->whereBetweenTime("create_time", $today_start, $today_end)->count();
		$now_online_count = \think\Db::name("clients")->where("lastlogin", ">=", time() - 900)->count();
		$total_client_count = \think\Db::name("clients")->count();
		$latest_online_clinet = \think\Db::name("clients")->field("id,username,lastloginip,lastlogin")->where("lastlogin", ">=", time() - 10800)->limit(20)->order("lastlogin", "desc")->select()->toArray();
		$data["today_add_client_count"] = $today_add_client_count;
		$data["now_online_count"] = $now_online_count;
		$data["total_client_count"] = $total_client_count;
		$data["latest_online_clinet"] = $latest_online_clinet;
		return $data;
	}
	/**
	 * 获取交易统计概览数据
	 * 本月数据和上月数据所有天数
	 */
	private function get_trade($start_time = "", $end_time = "")
	{
		$firstday = date("Y-m-01", time());
		$lastday = date("Y-m-d", strtotime("{$firstday} +1 month -1 day"));
		$now_month = [];
		$pre_month = [];
		$start_time = strtotime($firstday);
		$end_time = strtotime($lastday . " 23:59:59");
		$now_month_day = date("t");
		$now_list = \think\Db::name("orders")->alias("a")->field("from_unixtime(a.create_time, \"%Y-%m-%d\") date,count(a.id) count,sum(amount) amount_total")->join("invoices b", "a.invoiceid=b.id")->whereBetweenTime("a.create_time", $start_time, $end_time)->where("b.status", "Paid")->where("b.delete_time", 0)->where("a.status", "<>", "Cancelled")->where("a.delete_time", 0)->group("date")->select()->toArray();
		$now_list_days = array_column($now_list, "date");
		for ($i = 1; $i <= $now_month_day; $i++) {
			$day = date("Y-m-d", strtotime(date("Y-m-" . $i)));
			if (!in_array($day, $now_list_days)) {
				$temp = [];
				$temp["date"] = $day;
				$temp["count"] = 0;
				$temp["amount_total"] = 0;
				$now_list[] = $temp;
			}
		}
		$now_list_date = array_column($now_list, "date");
		array_multisort($now_list_date, SORT_ASC, $now_list);
		$pre_start_time = strtotime($firstday . "-1 month");
		$pre_end_time = strtotime($lastday . " 23:59:59" . "-1 month");
		$pre_list = \think\Db::name("orders")->alias("a")->field("from_unixtime(a.create_time, \"%Y-%m-%d\") date,count(a.id) count,sum(amount) amount_total")->leftJoin("invoices b", "a.invoiceid=b.id")->whereBetweenTime("a.create_time", $pre_start_time, $pre_end_time)->where("b.status", "Paid")->where("a.status", "<>", "Cancelled")->where("b.delete_time", 0)->where("a.delete_time", 0)->group("date")->select()->toArray();
		$pre_month_day = date("t", "-1 month");
		$pre_list_days = array_column($pre_list, "date");
		for ($i = 1; $i <= $pre_month_day; $i++) {
			$day = date("Y-m-d", strtotime(date("Y-" . date("m", strtotime("-1 month")) . "-" . $i)));
			if (!in_array($day, $pre_list_days)) {
				$temp = [];
				$temp["date"] = $day;
				$temp["count"] = 0;
				$temp["amount_total"] = 0;
				$pre_list[] = $temp;
			}
		}
		$pre_list_date = array_column($pre_list, "date");
		array_multisort($pre_list_date, SORT_ASC, $pre_list);
		$data["tomonth_order_count"] = 0;
		$data["tomonth_days"] = $now_month_day;
		$data["tomonth_order_amount_count"] = 0;
		$data["tomonth"] = [];
		$data["pre_month_order_count"] = 0;
		$data["pre_month_days"] = $pre_month_day;
		$data["pre_month_order_amount_count"] = 0;
		$data["pre_month"] = [];
		if ($now_list) {
			foreach ($now_list as $item) {
				$data["tomonth_order_count"] = bcadd($item["count"], $data["tomonth_order_count"]);
				$data["tomonth_order_amount_count"] = bcadd($item["amount_total"], $data["tomonth_order_amount_count"], 2);
				$data["tomonth"][] = $item;
			}
		}
		if ($pre_list) {
			foreach ($pre_list as $item) {
				$data["pre_month_order_count"] = bcadd($item["count"], $data["pre_month_order_count"]);
				$data["pre_month_order_amount_count"] = bcadd($item["amount_total"], $data["pre_month_order_amount_count"], 2);
				$data["pre_month"][] = $item;
			}
		}
		return $data;
	}
	/**
	 * 获取交易统计概览数据
	 * 默认是近7天的数据
	 */
	private function get_trade_latest_7_days($start_time = "", $end_time = "")
	{
		$start_time = $start_time ?? strtotime("-7 days");
		$end_time = $end_time ?? time();
		$group_day = \think\Db::name("orders")->alias("a")->field("from_unixtime(a.create_time, \"%Y-%m-%d\") date,count(a.id) count,sum(amount) amount_total")->leftJoin("invoices b", "a.invoiceid=b.id")->whereBetweenTime("a.create_time", $start_time, $end_time)->where("b.status", "Paid")->where("b.delete_time", 0)->where("a.status", "<>", "Cancelled")->where("a.delete_time", 0)->group("date")->select()->toArray();
		$data["tomonth_order_count"] = 0;
		$data["tomonth_order_amount_count"] = 0;
		$data["tomonth"] = [];
		$data["pre_month_order_count"] = 0;
		$data["pre_month_order_amount_count"] = 0;
		$data["pre_month"] = [];
		$pre_start_time = strtotime("last month", $start_time);
		$pre_end_time = strtotime("-1 month", $end_time);
		$pre_month_group_day = \think\Db::name("orders")->alias("a")->field("from_unixtime(a.create_time, \"%Y-%m-%d\") date,count(a.id) count,sum(amount) amount_total")->leftJoin("invoices b", "a.invoiceid = b.id")->where("a.create_time", "between", [$pre_start_time, $pre_end_time])->where("b.status", "Paid")->where("b.delete_time", 0)->where("a.status", "<>", "Cancelled")->where("a.delete_time", 0)->group("date")->select()->toArray();
		$tomonth_num = count($group_day);
		$pre_month_num = count($pre_month_group_day);
		if ($pre_month_num < $tomonth_num) {
			foreach ($group_day as $item) {
				$pre_exit_flag = false;
				foreach ($pre_month_group_day as $pre_item) {
					if (strtotime("-1 month", $pre_item["date"]) == strtotime($item["date"])) {
						$pre_exit_flag = true;
					}
				}
				if ($pre_exit_flag === false) {
					$pre_temp["date"] = date("Y-m-d", strtotime("last month", strtotime($item["date"])));
					$pre_temp["count"] = 0;
					$pre_temp["amount_total"] = 0;
					$pre_month_group_day[] = $pre_temp;
				}
			}
		} else {
			foreach ($pre_month_group_day as $item) {
				$to_exit_flag = false;
				foreach ($group_day as $to_item) {
					if (strtotime("-1 month", $to_item["date"]) == strtotime($item["date"])) {
						$to_exit_flag = true;
					}
				}
				if ($to_exit_flag === false) {
					$to_temp["date"] = date("Y-m-d", strtotime("+1 month", strtotime($item["date"])));
					$to_temp["count"] = 0;
					$to_temp["amount_total"] = 0;
					$group_day[] = $to_temp;
				}
			}
		}
		if ($group_day) {
			foreach ($group_day as $item) {
				$data["tomonth_order_count"] += $item["count"];
				$data["tomonth_order_amount_count"] += $item["amount_total"];
				$data["tomonth"][] = $item;
			}
		}
		if ($pre_month_group_day) {
			foreach ($pre_month_group_day as $item) {
				$data["pre_month_order_count"] += $item["count"];
				$data["pre_month_order_amount_count"] += $item["amount_total"];
				$data["pre_month"][] = $item;
			}
		}
		return $data;
	}
	/**
	 * 获取待办事项概览数据
	 */
	private function get_todo()
	{
		$waiting_ticket_count = $this->getCurrentAdminOfGroupWaitingTicketNum();
		$waiting_check_order_count = \think\Db::name("orders")->where("status", "Pending")->where("delete_time", 0)->count();
		$verified_count = \think\Db::name("certifi_log")->whereIn("status", [3])->count();
		$withdraw_count = \think\Db::name("affiliates_withdraw")->where("status", 1)->count();
		return ["waiting_ticket_count" => $waiting_ticket_count, "waiting_check_order_count" => $waiting_check_order_count, "verified_count" => $verified_count, "withdraw_count" => $withdraw_count];
	}
	/**
	 * 获取当前登录的管理员所在部门未处理的工单数量
	 */
	private function getCurrentAdminOfGroupWaitingTicketNum()
	{
		$admin_id = session("ADMIN_ID");
		$dptids = \think\Db::name("ticket_department_admin")->where("admin_id", $admin_id)->column("dptid");
		return \think\Db::name("ticket")->where("status", "in", "1,3")->where("merged_ticket_id", 0)->where("dptid", "in", $dptids)->count();
	}
	/**
	 * 获取收入概览数据
	 */
	private function get_income()
	{
		$today_start = strtotime(date("Y-m-d 00:00:00"));
		$today_end = strtotime(date("Y-m-d 23:59:59"));
		$todaytotal = $this->getSpellTotal($today_start, $today_end);
		$this_month_start = strtotime(date("Y-m-01"));
		$this_month_end = strtotime("+1 month -1 seconds", $this_month_start);
		$thismonth = $this->getSpellTotal($this_month_start, $this_month_end);
		$this_year_start = strtotime(date("Y-01-01"));
		$this_year_end = time();
		$toyear = $this->getSpellTotal($this_year_start, $this_year_end);
		$amounts = $this->getSpellTotal(0, time());
		return ["todaytotal" => $todaytotal, "thismonth" => $thismonth, "toyear" => $toyear, "amounts" => $amounts];
	}
	/**
	 * 获取订单概览数据
	 */
	private function get_order()
	{
		$month_start_time = strtotime("-31 day");
		$list = \think\Db::name("orders")->alias("o")->leftJoin("invoices nv", "nv.id = o.invoiceid")->field("o.*,nv.status as nv_status,nv.id as in_id,nv.delete_time as in_delete_time")->where("o.create_time", ">=", $month_start_time)->where("o.delete_time", 0)->select();
		$data["today_paid_order_count"] = 0;
		$data["today_total_order_count"] = 0;
		$data["tomonth_paid_order_count"] = 0;
		$data["tomonth_total_order_count"] = 0;
		$data["latest_7_paid_order_count"] = 0;
		$data["latest_7_total_order_count"] = 0;
		$data["latest_7_waiting_paid_order_count"] = 0;
		if ($list) {
			$today_start = strtotime(date("Y-m-d 00:00:00"));
			$day_start_7 = strtotime(date("Y-m-d", strtotime("-6 day")));
			$day_start_30 = strtotime(date("Y-m-01"));
			foreach ($list as $item) {
				if ($today_start <= $item["create_time"] && $item["nv_status"] == "Paid" && $item["in_delete_time"] == 0) {
					$data["today_paid_order_count"] += 1;
				}
				if ($today_start <= $item["create_time"]) {
					$data["today_total_order_count"] += 1;
				}
				if ($item["nv_status"] == "Paid" && $item["in_delete_time"] == 0 && $day_start_30 <= $item["create_time"]) {
					$data["tomonth_paid_order_count"] += 1;
				}
				if ($day_start_30 <= $item["create_time"]) {
					$data["tomonth_total_order_count"] += 1;
				}
				if ($day_start_7 <= $item["create_time"] && $item["nv_status"] == "Paid" && $item["in_delete_time"] == 0) {
					$data["latest_7_paid_order_count"] += 1;
				}
				if ($day_start_7 <= $item["create_time"]) {
					$data["latest_7_total_order_count"] += 1;
				}
				if ($day_start_7 <= $item["create_time"] && $item["nv_status"] == "Unpaid") {
					$data["latest_7_waiting_paid_order_count"] += 1;
				}
			}
		}
		return $data;
	}
	/**
	 * 获取系统最近一次失败的定时任务信息，判断规则：上次执行时间小于5分钟为正常
	 * 返回上次执行时间差
	 */
	private function getLatestErrorCrondInfo()
	{
		$cron_config = config("cron_config");
		$data = getConfig(array_keys($cron_config));
		$data = array_merge($cron_config, $data);
		$data["cron_command"] = config("cron_command");
		$data["cron_last_run_time_over"] = configuration("cron_last_run_time_over");
		if (configuration("cron_last_run_time_over") == configuration("cron_last_run_time") && configuration("cron_last_run_time_over_in") != configuration("cron_last_run_time")) {
			$system["diff_run_time"] = -1;
		} else {
			$system["diff_run_time"] = 1;
		}
		return $data;
	}
	/**
	 * @auther 上官🔪
	 * @return string
	 */
	private function getIndexCount()
	{
		$ticket_info = db("ticket")->whereIn("status", "Open,CustomerReply")->order("id", "desc")->select();
		return $cancell_count;
	}
	private function getSpellTotal($start, $end)
	{
		$session_currency = session("currency");
		if ($session_currency) {
			$currency = $session_currency;
		} else {
			$currency = \think\Db::name("currencies")->field("id,code,prefix,suffix")->where("default", 1)->find();
			session("currency", $currency);
		}
		$sum = \think\Db::name("accounts")->where("delete_time", 0)->where("currency", $currency["code"])->whereBetweenTime("create_time", $start, $end)->field("sum(amount_in - amount_out) as total ")->find();
		return $sum["total"];
	}
	private function getEveryMonthTotal($year_start) : array
	{
		$year_every_month_total = [];
		for ($i = 0; $i <= 11; $i++) {
			${$i + 1 . "_start"} = strtotime("+" . $i . " month", $year_start);
			${$i + 1 . "_end"} = strtotime("+" . ($i + 1) . " month -1 seconds", $year_start);
			${$i + 1 . "_total"} = $this->getSpellTotal(${$i + 1 . "_start"}, ${$i + 1 . "_end"});
			array_push($year_every_month_total, ${$i + 1 . "_total"});
		}
		return $year_every_month_total;
	}
	private function getEveryDayTotal($month_start) : array
	{
		$days = date("t", $month_start);
		$month_every_day_total = [];
		for ($i = 0; $i <= $days - 1; $i++) {
			${$i + 1 . "_start"} = strtotime("+" . $i . " days", $month_start);
			${$i + 1 . "_end"} = strtotime("+" . ($i + 1) . " days -1 seconds", $month_start);
			${$i + 1 . "_total"} = $this->getSpellTotal(${$i + 1 . "_start"}, ${$i + 1 . "_end"});
			array_push($month_every_day_total, ${$i + 1 . "_total"});
		}
		return $month_every_day_total;
	}
}