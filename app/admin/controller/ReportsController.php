<?php

namespace app\admin\controller;

/**
 * @title 后台统计报表模块
 * @description 年度收入统计、新客户、 收入排名、产品收入
 */
class ReportsController extends AdminBaseController
{
	/**
	 * @title 年度收入统计
	 * @description 接口说明:按月统计，每一个月的收入、支出、剩余，按照时间倒序
	 * @author zhoufei
	 * @url /admin/year_reports
	 * @method GET
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:limit type:int require:0  other: desc:长度
	 * @return  month_data:月份统计数据列表@
	 * @month_data  month:月份值
	 * @month_data  income:收入
	 * @month_data  expenses:支出d
	 * @month_data  last:剩余
	 *
	 * @return  all:全年统计数据@
	 * @all  income:收入
	 * @all  expenses:支出
	 * @all  last:剩余
	 */
	public function getYearIncomeStatistics()
	{
		$params = $data = $this->request->param();
		$page = $params["page"] ?? config("page");
		$limit = $params["limit"] ?? config("limit");
		$currencies = \think\Db::name("currencies")->select();
		$data = [];
		if ($currencies) {
			foreach ($currencies as $currency) {
				$data_temp["currency"] = $currency["code"];
				$data_temp["is_default_currency"] = $currency["default"];
				$data_temp["year_count"] = \think\Db::name("accounts")->where("delete_time", 0)->where("currency", $currency["code"])->field("FROM_UNIXTIME(pay_time,'%Y年%c月') as date")->field("sum(amount_in) as income")->field("sum(amount_out) as expenses")->field("sum(amount_in - amount_out) as last")->group("date")->limit($limit)->page($page)->order("pay_time desc")->select()->toArray();
				$data_temp["year_count_num"] = \think\Db::name("accounts")->where("delete_time", 0)->where("currency", $currency["code"])->field("FROM_UNIXTIME(pay_time,'%Y年%c月') as date")->field("sum(amount_in) as income")->field("sum(amount_out) as expenses")->field("sum(amount_in - amount_out) as last")->group("date")->count();
				$data[] = $data_temp;
			}
		}
		$return_year_count = [];
		foreach ($data as $d_item) {
			if ($d_item["is_default_currency"] === 1) {
				$return_year_count["year_count"] = $d_item["year_count"];
				$return_year_count["year_count_num"] = $d_item["year_count_num"];
			}
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $return_year_count]);
	}
	/**
	 * @title 年度收入统计--图表数据
	 * @description 接口说明: 上面的图表数据+页面下面的总统计
	 * @author zhoufei
	 * @url /admin/year_reports_chart
	 * @method GET
	 * @return  month_data:月份统计数据列表@
	 * @month_data  month:月份值
	 * @month_data  income:收入
	 * @month_data  expenses:支出d
	 * @month_data  last:剩余
	 * @return  all:全年统计数据@
	 * @all  income:收入
	 * @all  expenses:支出
	 * @all  last:剩余
	 */
	public function getYearIncomeStatisticsForChart()
	{
		$currencies = \think\Db::name("currencies")->select();
		$data = [];
		if ($currencies) {
			foreach ($currencies as $currency) {
				$data_temp["currency"] = $currency["code"];
				$data_temp["is_default_currency"] = $currency["default"];
				$all = \think\Db::name("accounts")->where("delete_time", 0)->where("currency", $currency["code"])->field("sum(amount_in) as income")->field("sum(amount_out) as expenses")->field("sum(amount_in - amount_out) as last")->find();
				$all["currency_code"] = $currency["code"];
				$all["currency_prefix"] = $currency["prefix"];
				$data_temp["chart"] = $this->getStatisticYears($currency["code"], $currency["prefix"]);
				$data_temp["all"] = $all;
				$data[] = $data_temp;
			}
		}
		$return_year_count = [];
		foreach ($data as $d_item) {
			if ($d_item["is_default_currency"] === 1) {
				$return_year_count["all"] = $d_item["all"];
				$return_year_count["chart"] = $d_item["chart"];
			}
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $return_year_count]);
	}
	private function getStatisticYears($currency_code = "", $prefix = "")
	{
		$data["years"] = [];
		$data["list"] = [];
		$years = \think\Db::name("accounts")->where("delete_time", 0)->where("refund", 0)->distinct("year")->column("FROM_UNIXTIME(pay_time,'%Y') as year");
		if ($years) {
			$data["years"] = $years;
			foreach ($years as $item) {
				$list_temp["year"] = $item;
				$list_temp["data"] = [];
				$start_time = $item . "-01-01";
				$end_time = $item . "-12-31";
				$years_month_data = \think\Db::name("accounts")->where("delete_time", 0)->where("currency", $currency_code)->whereTime("pay_time", [$start_time, $end_time])->field("FROM_UNIXTIME(pay_time,'%c') as month")->field("sum(amount_in) as income")->field("sum(amount_out) as expenses")->field("sum(amount_in - amount_out) as last")->group("month")->order("month asc")->select()->toArray();
				$years_months = array_column($years_month_data, "month");
				$years_months_data = array_column($years_month_data, "last", "month");
				for ($i = 1; $i <= 12; $i++) {
					if (!in_array($i, $years_months)) {
						$list_temp["data"][] = 0;
					} else {
						$list_temp["data"][] = $years_months_data[$i];
					}
				}
				$list_temp["prefix"] = $prefix;
				$data["list"][] = $list_temp;
			}
			foreach ($data["years"] as &$item) {
				$item .= "年";
			}
		}
		return $data;
	}
	/**
	 * 获取年度订单信息  ，按月分组
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 */
	private function getYearOrderInfoGroupByMonth($year = "", $currency_code = "CNY")
	{
		$year = $year ?? date("Y");
		$orderLists = [];
		for ($i = 1; $i <= 12; $i++) {
			$month_day = date("t", strtotime($year . "-" . $i . "-01"));
			$start_time = strtotime($year . "-" . $i . "-01");
			$end_time = strtotime($year . "-" . $i . "-" . $month_day . " 23:59:59");
			$rows = \think\Db::name("orders")->alias("o")->join("invoices i", "i.id = o.invoiceid")->leftjoin("clients c", "o.uid = c.id")->leftjoin("currencies cu", "cu.id = c.currency")->leftJoin("user u", "u.id = c.sale_id")->where("i.paid_time", "between", [$start_time, $end_time])->where("i.status", "Paid")->where("o.delete_time", 0)->field("o.id,o.ordernum,c.sale_id,o.invoiceid,cu.prefix,cu.suffix,o.amount as am")->field("i.status as invoice_status,i.type as invoice_type")->select()->toArray();
			if ($rows) {
				$discount_sum = 0;
				foreach ($rows as $k => &$row) {
					$hosts = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid = b.id")->leftJoin("sale_products c", "c.pid = b.id")->leftJoin("sales_product_groups d", "d.id = c.gid")->leftJoin("upgrades u", "u.relid = a.id")->field("a.id as hostid,b.name,a.billingcycle,u.id as upid,a.firstpaymentamount,a.productid,d.bates,d.renew_bates,d.upgrade_bates,d.is_renew,d.updategrade")->distinct(true)->where(function (\think\db\Query $query) use($row) {
						$query->where("a.orderid", $row["id"]);
						$query->whereOr("u.order_id", $row["id"]);
					})->withAttr("billingcycle", function ($value) {
						return config("billing_cycle")[$value];
					})->withAttr("firstpaymentamount", function ($value) use($row) {
						return $row["prefix"] . $value . $row["suffix"];
					})->withAttr("invoice_type", function ($value, $data) use($row) {
						return $row["invoice_type"] == "upgrade" ? $row["invoice_type"] : "";
					})->order("a.id", "desc")->select()->toArray();
					$adminOrderController = new OrderController();
					$sum = 0;
					$sum1 = 0;
					$refund = 0;
					$refund1 = 0;
					$wheres = [];
					$ladder = $adminOrderController->getLadder($row["sale_id"]);
					$rows[$k]["ladder"] = $ladder;
					if ($hosts) {
						foreach ($hosts as $val) {
							$wheres[] = ["type", "neq", "renew"];
							if ($val["updategrade"] == 1 && !empty($val["upid"])) {
								$nums = \think\Db::name("invoice_items")->where("rel_id", $val["upid"])->where("type", "upgrade")->where($wheres)->sum("amount");
								$sum = bcadd(bcmul($val["upgrade_bates"] / 100, $nums, 4), $sum, 4);
							} else {
								if (!empty($val["upid"])) {
									$nums = 0.0;
								} else {
									$nums = \think\Db::name("invoice_items")->where("rel_id", $val["hostid"])->where($wheres)->sum("amount");
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
							if ($vals["bates"] / 100 != 0) {
								$bates = $vals["bates"] / 100;
								continue;
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
						$discount_sum += number_format($sum1, 2, ".", "");
					}
				}
				$temp_list["month"] = $i;
				$temp_list["discount_sum"] = $discount_sum;
				$orderLists[] = $temp_list;
			}
		}
		return $orderLists;
	}
	/**
	 * @title 新客户统计
	 * @description 接口说明:搜索条件：年/月，显示维度：天
	 * @author zhoufei
	 * @url /admin/new_client
	 * @method GET
	 * @param  .name:date type:string require:0 default:本年年月值，如2020,08 desc:年月份值
	 * @return  day_string:日期
	 * @return  new_clients_count:新客户数
	 * @return  new_order_count:新订单数
	 * @return  complete_order_count:完成订单数
	 * @return  new_ticket_count:工单数
	 * @return  reply_ticket_count:回复工单数
	 * @return  cancel_requests_count:取消申请数
	 *
	 */
	public function getNewClientStatistics()
	{
		$params = $data = $this->request->param();
		$year = isset($params["year"]) && $params["year"] ? $params["year"] : date("Y");
		$month = isset($params["month"]) && $params["month"] ? $params["month"] : date("m");
		$client_data = [];
		$new_client_years_group = $this->getNewClientYearsGroup();
		$month_day = date("t", strtotime($year . "-" . $month . "-01"));
		for ($i = 1; $i <= $month_day; $i++) {
			$start_time = strtotime($year . "-" . $month . "-" . $i);
			$end_time = strtotime($year . "-" . $month . "-" . $i . " 23:59:59");
			$client_lists = \think\Db::name("clients")->where("create_time", "between", [$start_time, $end_time])->where("status", 1)->field("id,username")->select()->toArray();
			$day_data["day_string"] = $month . "月" . $i . "日";
			$day_data["new_clients_count"] = count($client_lists);
			$day_data["new_order_count"] = $this->getClientNewOrderNum($start_time, $end_time, 0);
			$day_data["complete_order_count"] = $this->getClientNewOrderNum($start_time, $end_time, 1);
			$day_data["new_ticket_count"] = $this->getNewTicketNum($start_time, $end_time);
			$day_data["reply_ticket_count"] = $this->getReplyTicketNum($start_time, $end_time);
			$day_data["cancel_requests_count"] = $this->getCanceRequestslNum($start_time, $end_time);
			$client_data[] = $day_data;
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["client_data" => $client_data, "new_client_years_group" => $new_client_years_group]]);
	}
	private function getNewClientYearsGroup()
	{
		$year = \think\Db::name("clients")->where("status", 1)->order("create_time asc")->value("FROM_UNIXTIME(create_time,'%Y') as year");
		$years_arr = [];
		$now_year = date("Y");
		if (!$year) {
			$temp["label"] = $now_year . "年";
			$temp["value"] = $now_year;
			$years_arr[] = $temp;
		} else {
			for ($i = $year; $i <= $now_year; $i++) {
				$temp["label"] = $i . "年";
				$temp["value"] = $i;
				$years_arr[] = $temp;
			}
		}
		return $years_arr;
	}
	private function getNowYearClientMonths($year = "")
	{
		$year = $year ?? date("Y");
		$start_time = strtotime($year . "-01-01");
		$end_time = strtotime($year . "-12-31 23:59:59");
		$month_groups = \think\Db::name("clients")->where("status", 1)->where("create_time", "between", [$start_time, $end_time])->field("FROM_UNIXTIME(create_time,'%m') as month")->group("month")->order("month desc")->select()->toArray();
		$month_arr = [];
		if ($month_groups) {
			foreach ($month_groups as $item) {
				$month["name"] = $item["month"] . "月";
				$month["value"] = $item["month"];
				$month_arr[] = $month;
			}
		}
		return $month_arr;
	}
	/**
	 * 获取取消请求数
	 * @param array $client_arr 客户数据
	 * @param string $start_time 开始时间
	 * @param string $end_time 结束时间
	 */
	private function getCanceRequestslNum($start_time = "", $end_time = "")
	{
		$num = \think\Db::name("cancel_requests")->whereBetweenTime("create_time", $start_time, $end_time)->count();
		return $num;
	}
	/**
	 * 获取回复的工单数：今日新建工单且状态不等于1
	 * @param array $client_arr 客户数据
	 * @param string $start_time 开始时间
	 * @param string $end_time 结束时间
	 */
	private function getReplyTicketNum($start_time = "", $end_time = "")
	{
		$num = \think\Db::name("ticket")->whereBetweenTime("create_time", $start_time, $end_time)->where("status", "<>", 1)->distinct("id")->count();
		return $num;
	}
	/**
	 * 获取新工单数
	 * @param string $start_time 开始时间
	 * @param string $end_time 结束时间
	 */
	private function getNewTicketNum($start_time = "", $end_time = "")
	{
		$num = \think\Db::name("ticket")->whereBetweenTime("create_time", $start_time, $end_time)->count();
		return $num;
	}
	/**
	 * 获取客户新订单数
	 * @param string $start_time 开始时间
	 * @param string $end_time 结束时间
	 * @param string $paid 是否已经支付：0-未支付，1-已完成（已支付）
	 */
	private function getClientNewOrderNum($start_time = "", $end_time = "", $paid = 0)
	{
		if (!$paid) {
			$num = \think\Db::name("orders")->where("delete_time", 0)->whereBetweenTime("create_time", $start_time, $end_time)->count();
		} else {
			$num = \think\Db::name("orders")->alias("o")->join("invoices nv o.invoiceid = nv.id")->where("delete_time", 0)->where("status", "Active")->whereBetweenTime("create_time", $start_time, $end_time)->count();
		}
		return $num;
	}
	/**
	 * @title 收入排名
	 * @description 统计系统中给钱最多的客户。包含：客户名称 公司名 收入   支出  剩余
	 * @author zhoufei
	 * @url /admin/forward_client
	 * @method GET
	 * @return  data:统计数据列表@
	 * @data  id:客户id
	 * @data  client_name:客户名称
	 * @data  company_name:公司名称
	 * @data  income_sum:收入
	 * @data  expense_sum:支出
	 * @data  last:剩余
	 */
	public function rankForwardClient()
	{
		$clients = \think\Db::name("clients")->alias("c")->join("accounts a", "a.uid = c.id")->leftJoin("currencies cu", "cu.code = a.currency")->where("c.status", 1)->where("a.delete_time", 0)->field("c.id,c.username,c.companyname")->field("sum(`a`.`amount_in`) as income_sum, sum(a.amount_out) as  expense_sum,cu.prefix,cu.suffix")->order("expense_sum", "desc")->group("c.id")->select()->toArray();
		if ($clients) {
			foreach ($clients as &$client) {
				$client["income_sum"] = round($client["income_sum"], 2);
				$client["expense_sum"] = round($client["expense_sum"], 2);
				$client["last"] = round($client["income_sum"] - $client["expense_sum"], 2);
			}
			array_multisort(array_column($clients, "last"), SORT_DESC, $clients);
			$data = [];
			foreach ($clients as $key => $item) {
				if ($key >= 10) {
					break;
				}
				$data[] = $item;
			}
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 产品收入
	 * @description 旧版：统计系统中每一个产品产品组的新订购，续费的收入; <br>选择产品分组时，将返回产品组名（name）；<br>选择产品名称时，将返回具体的产品名称（name）
	 * @description 新版：按产品组所包含产品显示
	 * @author zhoufei
	 * @url /admin/product_income
	 * @method GET
	 *
	 * @param  .name:year type:number require:0 default:本年数字，如2020 desc:年份值
	 * @param  .name:month type:number require:0 default:本月数组，如8月：8 desc:月份值
	 * @param  .name:search_type type:string require:0 default:product_group desc:搜素类型：product_group(产品组)_product_name(产品名)
	 * @param       .name:limit type:int require:0 default:10 other: desc:条数
	 * @param       .name:page type:int require:0 default:1 other: desc:页数
	 *
	 * @return  data:统计数据列表@
	 * @data  id:产品组id/产品id
	 * @data  name:产品组名/产品名
	 * @data  total_amount:总金额
	 * @data  new_order_amount:新订购收入
	 * @data  new_order_num:新订购数量
	 * @data  renew_order_amount:续费收入
	 * @data  renew_order_num:续费数量
	 */
	public function productIncome()
	{
		$param = $this->request->param();
		$year = isset($param["year"]) && $param["year"] ? $param["year"] : date("Y");
		$month = isset($param["month"]) && $param["month"] ? $param["month"] : date("m");
		$limit = input("get.limit", 50, "int");
		$page = input("get.page", 1, "int");
		$start_time = strtotime($year . "-" . $month . "-01");
		$end_time = strtotime($year . "-" . $month . "-" . date("t", $start_time) . " 23:59:59");
		$default_currency = \think\Db::name("currencies")->where("default", 1)->find();
		$product_groups = \think\Db::name("product_groups")->where("hidden", 0)->field("id,name")->order("order", "ASC")->page($page)->limit($limit)->select()->toArray();
		$product_groups_counts = \think\Db::name("product_groups")->where("hidden", 0)->field("id,name")->order("order", "ASC")->count();
		if ($product_groups) {
			foreach ($product_groups as &$item) {
				$products = \think\Db::name("products")->field("id,name")->where("hidden", 0)->where("gid", $item["id"])->order("create_time", "desc")->order("order", "desc")->select()->toArray();
				if ($products) {
					foreach ($products as &$p_item) {
						$new_order_amount = 0;
						$new_order_num = 0;
						$renew_order_amount = 0;
						$renew_order_num = 0;
						$new_order = \think\Db::name("host")->alias("h")->leftJoin("invoice_items im", "im.rel_id = h.id")->leftJoin("invoices i", "im.invoice_id = i.id")->whereIn("i.status", ["Paid", "Refunded"])->where("i.type", "product")->where("h.productid", $p_item["id"])->where("i.paid_time", "between", [$start_time, $end_time])->field("sum(im.amount) as amount,count(distinct h.id) as num,group_concat(distinct i.id) as ids ")->find();
						$new_order_amount = $new_order["amount"];
						$new_order_num = $new_order["num"];
						if ($new_order["ids"]) {
							$ids = array_unique(explode(",", $new_order["ids"]));
							$new_order_refund = \think\Db::name("accounts")->where("invoice_id", "in", $ids)->where("refund", ">", 0)->sum("amount_out");
							$credit_refund = \think\Db::name("credit")->whereIn("relid", $ids)->where("description", "like", "%Credit Removed from Invoice #%")->sum("amount");
							$new_order_refund = bcsub($new_order_refund, $credit_refund);
							$new_order_amount = bcsub($new_order_amount, $new_order_refund, 2);
						}
						$renew = \think\Db::name("host")->alias("h")->leftJoin("invoice_items im", "im.rel_id = h.id")->leftJoin("invoices i", "im.invoice_id = i.id")->leftJoin("products p", "p.id = h.productid")->where("i.status", "Paid")->where("i.type", "renew")->where("im.type", "renew")->where("p.id", $p_item["id"])->whereBetweenTime("paid_time", $start_time, $end_time)->field("count( distinct im.id) as num,group_concat( distinct i.id) as ids ")->find();
						$ids = array_unique(explode(",", $renew["ids"]));
						$renew_order_amount = \think\Db::name("invoices")->where("id", "in", $ids)->sum("subtotal");
						$renew_order_num = $renew["num"];
						if ($renew["ids"]) {
							$renew_order_refund = \think\Db::name("accounts")->where("invoice_id", "in", $ids)->where("refund", ">", 0)->sum("amount_out");
							$renew_order_amount = bcsub($renew_order_amount, $renew_order_refund, 2);
						}
						$p_item["new_order_amount"] = $new_order_amount ?? 0;
						$p_item["new_order_num"] = $new_order_num ?? 0;
						$p_item["renew_order_amount"] = $renew_order_amount ?? 0;
						$p_item["renew_order_num"] = $renew_order_num ?? 0;
						$p_item["total_amount"] = bcadd($p_item["renew_order_amount"], $p_item["new_order_amount"], 2);
						$p_item["new_order_prefix"] = $new_order[0]["prefix"] ?? $default_currency["prefix"];
						$p_item["new_order_suffix"] = $new_order[0]["suffix"] ?? $default_currency["suffix"];
						$p_item["renew_order_prefix"] = $renew[0]["prefix"] ?? $default_currency["prefix"];
						$p_item["renew_order_suffix"] = $renew[0]["suffix"] ?? $default_currency["suffix"];
					}
				}
				$item["products"] = $products;
			}
		}
		$year = \think\Db::name("orders")->alias("o")->join("invoices i", "i.id = o.invoiceid")->where("i.status", "Paid")->where("i.delete_time", 0)->order("i.create_time", "asc")->group("year")->value("FROM_UNIXTIME(i.create_time,'%Y') as year");
		$years = [];
		$now_year = date("Y");
		if ($year) {
			for ($i = $year; $i <= $now_year; $i++) {
				$temp["label"] = $i . "年";
				$temp["value"] = $i;
				$years[] = $temp;
			}
		} else {
			$temp["label"] = $now_year . "年";
			$temp["value"] = $now_year;
			$years[] = $temp;
		}
		return jsonrule(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "years" => $years, "groups_count" => $product_groups_counts, "data" => $product_groups]);
	}
	/**
	 * 获取产品组收入情况
	 */
	private function getGroupProductIncome($year, $month, $page, $limit)
	{
		$product_group = \think\Db::name("product_groups")->field("id,name")->where("hidden", 0)->order("create_time", "desc")->order("order", "desc")->page($page)->limit($limit)->select()->toArray();
		$product_group_count = \think\Db::name("product_groups")->field("id,name")->where("hidden", 0)->order("create_time", "desc")->order("order", "desc")->count("id");
		$start_time = strtotime($year . "-" . $month . "-01");
		$end_time = strtotime($year . "-" . $month . "-" . date("t", $year . "-" . $month . "-01"));
		if ($product_group) {
			foreach ($product_group as &$item) {
				$item["total_amount"] = \think\Db::name("orders")->alias("o")->join("invoices i", "i.id = o.invoiceid")->join("invoice_items im", "im.invoice_id = i.id")->join("host h", "im.rel_id = h.id")->join("products p", "p.id = h.productid")->where("p.gid", $item["id"])->where("i.status", "Paid")->where(function (\think\db\Query $query) {
					$query->where("i.type", "product")->whereOr("i.type", "renew");
				})->whereBetweenTime("paid_time", $start_time, $end_time)->sum("o.amount");
				$new_order = \think\Db::name("orders")->alias("o")->join("invoices i", "i.id = o.invoiceid")->join("invoice_items im", "im.invoice_id = i.id")->join("host h", "im.rel_id = h.id")->join("products p", "p.id = h.productid")->where("p.gid", $item["id"])->where("i.status", "Paid")->whereBetweenTime("paid_time", $start_time, $end_time)->where("i.type", "product")->field("sum(o.amount) as new_order_amount")->field("count(o.id) as new_order_num")->find();
				$renew = \think\Db::name("orders")->alias("o")->join("invoices i", "i.id = o.invoiceid")->join("invoice_items im", "im.invoice_id = i.id")->join("host h", "im.rel_id = h.id")->join("products p", "p.id = h.productid")->where("p.gid", $item["id"])->where("i.status", "Paid")->whereBetweenTime("paid_time", $start_time, $end_time)->where("i.type", "renew")->field("sum(o.amount) as renew_order_amount")->field("count(o.id) as renew_order_num")->find();
				$item["new_order_amount"] = $new_order["new_order_amount"] ?? 0;
				$item["new_order_num"] = $new_order["new_order_num"] ?? 0;
				$item["renew_order_amount"] = $renew["renew_order_amount"] ?? 0;
				$item["renew_order_num"] = $renew["renew_order_num"] ?? 0;
			}
		}
		return ["list" => $product_group, "count" => $product_group_count];
	}
	/**
	 * 获取产品名收入情况
	 */
	private function getNameProductIncome($year, $month, $page, $limit)
	{
		$start_time = strtotime($year . "-" . $month . "-01");
		$end_time = strtotime($year . "-" . $month . "-" . date("t", $year . "-" . $month . "-01"));
		$products = \think\Db::name("products")->field("id,name")->where("hidden", 0)->order("create_time", "desc")->order("order", "desc")->page($page)->limit($limit)->select()->toArray();
		$products_count = \think\Db::name("products")->field("id,name")->where("hidden", 0)->order("create_time", "desc")->order("order", "desc")->count("id");
		if ($products) {
			foreach ($products as &$item) {
				$new_order = \think\Db::name("orders")->alias("o")->join("invoices i", "i.id = o.invoiceid")->join("host h", "h.orderid = o.id")->where("i.status", "Paid")->where("i.type", "product")->where("i.paid_time", "between", [$start_time, $end_time])->where("h.productid", $item["id"])->field("sum(o.amount) as new_order_amount")->field("count(o.id) as new_order_num")->find();
				$renew = \think\Db::name("orders")->alias("o")->join("invoices i", "i.id = o.invoiceid")->join("invoice_items im", "im.invoice_id = i.id")->join("host h", "im.rel_id = h.id")->join("products p", "p.id = h.productid")->where("p.gid", $item["id"])->where("i.status", "Paid")->whereBetweenTime("paid_time", $start_time, $end_time)->where("i.type", "renew")->field("sum(o.amount) as renew_order_amount")->field("count(o.id) as renew_order_num")->find();
				$item["new_order_amount"] = $new_order["new_order_amount"] ?? 0;
				$item["new_order_num"] = $new_order["new_order_num"] ?? 0;
				$item["renew_order_amount"] = $renew["renew_order_amount"] ?? 0;
				$item["renew_order_num"] = $renew["renew_order_num"] ?? 0;
				$item["total_amount"] = $renew["renew_order_amount"] + $new_order["new_order_amount"];
			}
		}
		return ["list" => $products, "count" => $products_count];
	}
}