<?php

namespace app\admin\controller;

/**
 * @title 获取指定用户
 * @description 接口说明
 */
class GetUserController extends AdminBaseController
{
	public $str = [];
	public $user;
	public function initialize()
	{
		parent::initialize();
		$sessionAdminId = session("ADMIN_ID");
		$user = \think\Db::name("user")->field("id,is_sale,sale_is_use,only_mine,cat_ownerless")->where("id", $sessionAdminId)->find();
		$this->user = $user;
		if ($this->user["id"] != 1 && $this->user["is_sale"]) {
			$client = \think\Db::name("clients");
			if ($this->user["only_mine"] && $this->user["cat_ownerless"]) {
				$client->whereIn("sale_id", [$this->user["id"], 0]);
			}
			if ($this->user["only_mine"] && !$this->user["cat_ownerless"]) {
				$client->whereIn("sale_id", [$this->user["id"]]);
			}
			if (!$this->user["only_mine"] && !$this->user["cat_ownerless"]) {
				$client->whereNotIn("sale_id", [0]);
			}
			$uids = $client->column("id");
			$this->str = $uids;
		}
	}
	/**
	 * @title 获取销售员客户ids
	 * @description 接口说明:
	 * @author 刘国栋
	 */
	public function getAdminSale($uid)
	{
		$sessionAdminId = $uid;
		$str = "";
		if (empty($uid)) {
			return $str;
		}
		$sales_client_ids = \think\Db::name("user")->alias("u")->leftJoin("clients c", "c.sale_id = u.id")->field("u.id as uid,u.user_login,u.is_sale,u.sale_is_use,u.only_mine")->where("u.id", $sessionAdminId)->where("u.is_sale", 1)->where("c.sale_id", $sessionAdminId)->column("c.id");
		$str = implode(",", $sales_client_ids);
		if (empty($sales_client_ids)) {
			return $str;
		} else {
			return $str ?? "-1";
		}
	}
	/**
	 * @title 检查当前用户是否为销售员 不可以查看sale_id为空的
	 * @description 接口说明:
	 * @author 刘国栋
	 */
	public function check($uid)
	{
		$sessionAdminId = session("ADMIN_ID");
		$user = \think\Db::name("user")->field("is_sale,sale_is_use,only_mine,cat_ownerless")->where("id", $sessionAdminId)->find();
		if ($user["is_sale"] == 1 && $user["cat_ownerless"] == 1) {
			if (in_array($uid, $this->str)) {
				return true;
			}
		}
		if ($user["is_sale"] == 1 && $user["only_mine"] == 1) {
			if (in_array($uid, $this->str)) {
				return true;
			}
			return false;
		} else {
			return true;
		}
	}
	/**
	 * @title 检查当前用户是否为销售员  可以查看sale_id为空的
	 * @description 接口说明:
	 * @author 刘国栋
	 */
	public function check1($uid, $hid = 0)
	{
		if ($hid != 0) {
			$host = \think\Db::name("host")->field("uid")->where("id", $hid)->find();
			$uid = $host["uid"];
		}
		$sessionAdminId = session("ADMIN_ID");
		$cli = \think\Db::name("clients")->field("sale_id")->where("id", $uid)->find();
		if (empty($cli["sale_id"])) {
			return true;
		}
		$user = \think\Db::name("user")->field("is_sale,sale_is_use,only_mine,cat_ownerless")->where("id", $sessionAdminId)->find();
		if ($user["is_sale"] == 1 && $user["cat_ownerless"] == 1) {
			if (in_array($uid, $this->str)) {
				return true;
			}
		}
		if ($user["is_sale"] == 1 && $user["only_mine"] == 1) {
			if (in_array($uid, $this->str)) {
				return true;
			}
			return false;
		} else {
			return true;
		}
	}
	/**
	 * @title 获取当前销售员的阶级统计
	 * @description 接口说明:
	 * @author 刘国栋
	 */
	public function getLadder($uid)
	{
		$str = $this->getAdminSale($uid);
		if (!empty($str)) {
			$rows = \think\Db::name("invoices")->alias("i")->join("clients c", "i.uid=c.id")->join("currencies cu", "cu.id = c.currency")->field("i.id as invoiceid,cu.suffix")->where("i.status", "=", "Paid")->whereTime("i.paid_time", "month")->where("c.sale_id", $uid)->select()->toArray();
			$sum = \think\Db::name("invoices")->alias("i")->join("invoice_items in", "in.invoice_id=i.id")->join("host h", "h.id=in.rel_id")->join("clients c", "i.uid=c.id")->field("i.id,in.amount")->where("i.status", "=", "Paid")->whereTime("i.paid_time", "month")->where("c.sale_id", $uid)->sum("in.amount");
			$sum = round($sum, 2);
			if (is_array($rows) && count($rows) > 0) {
				$invoiceid_str = implode(",", array_column($rows, "invoiceid"));
				$accounts_sum_amount_out = \think\Db::name("accounts")->field("id,amount_in,amount_out,refund")->where("invoice_id", "IN", $invoiceid_str)->where("refund", ">", 0)->sum("amount_out");
				$sum = bcsub($sum, $accounts_sum_amount_out, 2);
			}
			$sale_ladder = \think\Db::name("sale_ladder")->field("id,turnover,bates")->where("turnover", "<=", $sum)->order("turnover", DESC)->find();
			$sale_ladder1 = \think\Db::name("sale_ladder")->field("id,turnover,bates")->where("turnover", ">", $sum)->order("turnover", ASC)->find();
			if (empty($sale_ladder)) {
				$sale_ladder = ["id" => null, "turnover" => null, "bates" => null];
			}
			if (empty($sale_ladder1)) {
				$sale_ladder1 = ["id" => null, "turnover" => null, "bates" => null];
			}
			$data = ["total" => floatval($sum), "turnover" => $sale_ladder, "last" => $sale_ladder1];
			return $data;
		}
	}
	/**
	 * @title 获取当前销售员的阶级统计(所有)
	 * @description 接口说明:
	 * @author 刘国栋
	 */
	public function getLadderforall($uid)
	{
		$rows = \think\Db::name("invoices")->alias("i")->join("clients c", "i.uid=c.id")->join("currencies cu", "cu.id = c.currency")->field("i.id as invoiceid,cu.suffix")->where("i.status", "=", "Paid")->whereTime("i.paid_time", "month")->where("c.sale_id", $uid)->select()->toArray();
		$rows1 = \think\Db::name("invoices")->alias("i")->join("invoice_items in", "in.invoice_id=i.id")->join("host h", "h.id=in.rel_id")->join("clients c", "i.uid=c.id")->field("i.id,in.amount")->where("i.status", "=", "Paid")->whereTime("i.paid_time", "month")->where("c.sale_id", $uid)->select()->toArray();
		$sum = 0;
		foreach ($rows1 as $key => $val) {
			$sum = bcadd($sum, $val["amount"], 2);
		}
		foreach ($rows as $key => $val) {
			$accounts = \think\Db::name("accounts")->field("id,amount_in,amount_out,refund")->where("invoice_id", $val["invoiceid"])->where("refund", ">", 0)->select()->toArray();
			foreach ($accounts as $key1 => $val1) {
				$sum = bcsub($sum, $val1["amount_out"], 2);
			}
		}
		$sale_ladder = \think\Db::name("sale_ladder")->field("id,turnover,bates")->where("turnover", "<=", $sum)->order("turnover", DESC)->find();
		$sale_ladder1 = \think\Db::name("sale_ladder")->field("id,turnover,bates")->where("turnover", ">", $sum)->order("turnover", ASC)->find();
		if (empty($sale_ladder)) {
			$sale_ladder = ["id" => null, "turnover" => null, "bates" => null];
		}
		if (empty($sale_ladder1)) {
			$sale_ladder1 = ["id" => null, "turnover" => null, "bates" => null];
		}
		$default_currency = \think\Db::name("currencies")->where("default", 1)->find();
		$data = ["total" => floatval($sum), "totals" => round(floatval($this->getSum($uid, $sale_ladder)), 2), "turnover" => $sale_ladder, "last" => $sale_ladder1, "suffix" => $rows[0]["suffix"] ?? $default_currency["suffix"]];
		return $data;
	}
	/**
	 * @title 当前销售员时间周期获取业绩
	 * @description 接口说明:
	 * @author 刘国栋
	 */
	public function getLaddersaleStatistics($time, $uid, $start_time = 1)
	{
		$wheres = $this->getTimes($time, "paid_time", $start_time);
		if (empty($wheres[0]) || empty($start_time)) {
			$wheres = [];
		}
		$rows = \think\Db::name("invoices")->alias("i")->join("orders o", "i.id=o.invoiceid")->join("clients c", "i.uid=c.id")->field("i.id as invoiceid")->where("i.status", "=", "Paid")->where("o.delete_time", 0)->where($wheres)->where("c.sale_id", $uid)->where("i.use_credit_limit=0 OR (use_credit_limit=1 AND i.invoice_id>0)")->select()->toArray();
		$wheres1 = $this->getTimes($time, "paid_time", $start_time);
		if (empty($wheres1[0]) || empty($start_time)) {
			$wheres1 = [];
		}
		$rowsun = \think\Db::name("invoices")->alias("i")->join("orders o", "i.id=o.invoiceid")->join("clients c", "i.uid=c.id")->field("i.id as invoiceid")->where("i.status", "=", "Unpaid")->where("o.delete_time", 0)->where($wheres1)->where("c.sale_id", $uid)->where("i.use_credit_limit=0 OR (use_credit_limit=1 AND i.invoice_id>0)")->select()->toArray();
		$rowsm = \think\Db::name("invoices")->alias("i")->join("orders o", "i.id=o.invoiceid")->join("clients c", "i.uid=c.id")->field("i.id as invoiceid")->where("o.delete_time", 0)->where($wheres1)->where("c.sale_id", $uid)->where("i.use_credit_limit=0 OR (use_credit_limit=1 AND i.invoice_id>0)")->select()->toArray();
		$wheres2 = $this->getTimes($time, "paid_time", $start_time);
		if (empty($wheres2[0]) || empty($start_time)) {
			$wheres2 = [];
		}
		$rows2 = \think\Db::name("invoices")->alias("i")->join("clients c", "i.uid=c.id")->field("i.id as invoiceid")->where("i.status", "=", "Paid")->where($wheres2)->where("i.delete_time", 0)->where("c.sale_id", $uid)->select()->toArray();
		$rows1 = \think\Db::name("invoices")->alias("i")->join("invoice_items in", "in.invoice_id=i.id")->join("host h", "h.id=in.rel_id")->join("clients c", "i.uid=c.id")->field("i.id,in.amount,in.type")->where("i.status", "=", "Paid")->where($wheres2)->where("i.delete_time", 0)->where("c.sale_id", $uid)->select()->toArray();
		$sum = 0;
		foreach ($rows1 as $key => $val) {
			$sum = bcadd($sum, $val["amount"], 2);
		}
		foreach ($rows2 as $key => $val) {
			$accounts = \think\Db::name("accounts")->field("id,amount_in,amount_out,refund")->where("invoice_id", $val["invoiceid"])->where("refund", ">", 0)->select()->toArray();
			if (!empty($accounts)) {
				foreach ($accounts as $key1 => $val1) {
					$sum = bcsub($sum, $val1["amount_out"], 2);
				}
			}
		}
		$data = ["ordercount" => count($rows), "ordercountun" => count($rowsun), "ordercountsum" => count($rowsm), "total" => floatval($sum)];
		return $data;
	}
	/**
	 * @title 当前销售员时间周期获取业绩；仅统计总金额
	 * @description 接口说明:
	 */
	public function getLaddersaleStatisticsOnlyTotalAccount($time, $uid)
	{
		$wheres2 = $this->getTimes($time, "paid_time");
		$rows1 = \think\Db::name("invoices")->alias("i")->join("invoice_items in", "in.invoice_id=i.id")->join("host h", "h.id=in.rel_id")->join("clients c", "i.uid=c.id")->field("i.id,in.amount,in.type")->where("i.status", "=", "Paid")->whereTime("i.paid_time", $time)->where($wheres2)->where("i.delete_time", 0)->where("c.sale_id", $uid)->where("i.use_credit_limit=0 OR (use_credit_limit=1 AND i.invoice_id>0)")->select()->toArray();
		$sum = 0;
		foreach ($rows1 as $key => $val) {
			$sum = bcadd($sum, $val["amount"], 2);
		}
		$rows2 = \think\Db::name("invoices")->alias("i")->join("clients c", "i.uid=c.id")->leftJoin("accounts a", "a.invoice_id=i.id")->field("i.id as invoiceid,sum(a.amount_out) as amount_outs,a.refund")->where("i.status", "=", "Paid")->whereTime("i.paid_time", $time)->where($wheres2)->where("i.delete_time", 0)->where("c.sale_id", $uid)->where("a.refund", ">", 0)->where("i.use_credit_limit=0 OR (use_credit_limit=1 AND i.invoice_id>0)")->group("i.id")->select()->toArray();
		foreach ($rows2 as $key => $val) {
			if (!empty($val["aid"])) {
				$sum = bcsub($sum, $val["amount_out"], 2);
			}
		}
		return floatval($sum);
	}
	private function getTimes($time, $str = "paid_time", $start_time = "")
	{
		if ($time == "last_month") {
			$lastmonth_end = \think\helper\Time::lastMonth();
			$wheres[] = ["i." . $str, ">=", $lastmonth_end[0]];
			$wheres[] = ["i." . $str, "<", $lastmonth_end[1]];
		} elseif ($time == "this_month") {
			$wheres[] = ["i." . $str, ">=", strtotime(date("Y-m-01", time()))];
			$wheres[] = ["i." . $str, "<", strtotime(date("Y-m", time()) . "+1 month")];
		} elseif ($time == "last_three_month") {
			$wheres[] = ["i." . $str, ">=", strtotime(date("Y-m-01", strtotime("-0 year -2 month -0 day")))];
			$wheres[] = ["i." . $str, "<", time()];
		} elseif ($time == "last_six_month") {
			$wheres[] = ["i." . $str, ">=", strtotime(date("Y-m-01", strtotime("-0 year -5 month -0 day")))];
			$wheres[] = ["i." . $str, "<", strtotime(date("Y-m-01", strtotime("-0 year -2 month -0 day")))];
		} elseif ($time == "diy_time") {
			$wheres[] = ["i." . $str, ">=", $start_time[0]];
			$wheres[] = ["i." . $str, "<", $start_time[1]];
		} elseif ($time == "last_diy_time") {
			$time = $start_time[1] - $start_time[0];
			$wheres[] = ["i." . $str, ">=", $start_time[0] - $time];
			$wheres[] = ["i." . $str, "<", $start_time[0]];
		} elseif ($time == "allmonth") {
			$wheres[] = ["i." . $str, ">=", strtotime($start_time)];
			$wheres[] = ["i." . $str, "<", strtotime($start_time . "+1 month")];
		} elseif ($time == "alltime") {
			$wheres[] = [];
		} elseif ($time == "today") {
			$wheres[] = ["i." . $str, ">=", strtotime(date("Y-m-d", time()))];
			$wheres[] = ["i." . $str, "<", strtotime(date("Y-m-d", time()) . "+1 day")];
		} elseif ($time == "week") {
			$wheres[] = ["i." . $str, ">=", strtotime(date("Y-m-d", strtotime("this week Monday", time())))];
			$wheres[] = ["i." . $str, "<", strtotime(date("Y-m-d", strtotime("this week Sunday", time())))];
		} else {
			$wheres[] = ["i." . $str, ">=", strtotime($time)];
			$wheres[] = ["i." . $str, "<", strtotime($time . "+1 day")];
		}
		return $wheres;
	}
	/**
	 * @title 获取提成总额
	 * @description 接口说明:
	 * @author 刘国栋
	 */
	public function getSum($uid, $ladder)
	{
		$where[] = ["i.paid_time", ">=", strtotime(date("Y-m", time()))];
		$where[] = ["i.paid_time", "<", strtotime(date("Y-m", time()) . "+1 month")];
		$rows1 = \think\Db::name("invoice_items")->alias("in")->join("host h", "h.id=in.rel_id")->join("products p", "p.id=h.productid")->leftJoin("sale_products sp", "p.id=sp.pid")->leftJoin("sales_product_groups spg", "spg.id=sp.gid")->join("invoices i", "i.id=in.invoice_id")->join("clients c", "i.uid=c.id")->join("currencies cu", "cu.id = c.currency")->field("i.uid,h.id as hostid,in.id,in.invoice_id,in.amount,spg.bates,p.name,c.username,c.companyname,in.type,spg.is_renew,spg.updategrade,cu.suffix,h.domain,h.dedicatedip")->where("i.status", "=", "Paid")->where("c.sale_id", $uid)->where($where)->where("i.use_credit_limit=0 OR (use_credit_limit=1 AND i.invoice_id>0)")->select()->toArray();
		$rows1 = array_values($rows1);
		$sum = 0;
		$sum1 = 0;
		$beats = 0;
		foreach ($rows1 as $key => $val) {
			if ($val["is_renew"] == 0 && $val["type"] == "renew") {
				$sum = bcadd(bcmul(0, $val["amount"], 2), $sum, 2);
				$rows1[$key]["sum"] = bcmul(0, $val["amount"], 2);
			} else {
				if ($val["updategrade"] == 0 && $val["type"] == "upgrade") {
					$sum = bcadd(bcmul(0, $val["amount"], 2), $sum, 2);
					$rows1[$key]["sum"] = bcmul(0, $val["amount"], 2);
				} else {
					$sum = bcadd(bcmul($val["bates"] / 100, $val["amount"], 2), $sum, 2);
					$rows1[$key]["sum"] = bcmul($val["bates"] / 100, $val["amount"], 2);
				}
			}
			if (!empty($ladder["turnover"])) {
				$sum1 = bcadd(bcmul($ladder["bates"] / 100, $val["amount"], 2), $sum1, 2);
				$rows1[$key]["sum1"] = bcmul($ladder["bates"] / 100, $val["amount"], 2);
			}
		}
		foreach ($rows1 as $key => $val) {
			if (!empty($arr[$val["invoice_id"]])) {
				$val["item_id"] = $val["invoice_id"] . "_" . rand(10000, 99999);
				$arr[$val["invoice_id"]]["child"][] = $val;
			} else {
				$val["item_id"] = $val["invoice_id"];
				$arr[$val["invoice_id"]] = $val;
				$arr[$val["invoice_id"]]["child"][] = $val;
			}
		}
		$arr1 = [];
		foreach ($arr as $key => $val) {
			$arr1[] = $val;
		}
		$refund = 0;
		$refund1 = 0;
		foreach ($arr1 as $key => $val) {
			$refund2 = 0;
			$refund3 = 0;
			$bates = 0;
			foreach ($val["child"] as $vals) {
				if ($vals["bates"] / 100 != 0) {
					$bates = $vals["bates"] / 100;
					continue;
				}
			}
			$accounts = \think\Db::name("accounts")->field("id,amount_out")->where("invoice_id", $val["invoice_id"])->where("refund", ">", 0)->select()->toArray();
			if (!empty($accounts)) {
				foreach ($accounts as $val2) {
					$refund2 = bcadd($refund2, bcmul($bates, $val2["amount_out"], 2), 2);
					if (!empty($ladder["turnover"])) {
						$refund3 = bcadd($refund3, bcmul($ladder["bates"] / 100, $val2["amount_out"], 2), 2);
					}
				}
			}
			$s = 0;
			$ss = 0;
			foreach ($val["child"] as $vals) {
				$s = bcadd($vals["sum"], $s, 2);
				$ss = bcadd($vals["sum1"], $ss, 2);
			}
			if ($s < $refund2) {
				$refund2 = $s;
			}
			if ($ss < $refund3) {
				$refund3 = $ss;
			}
			$refund = bcadd($refund2, $refund, 2);
			$refund1 = bcadd($refund3, $refund1, 2);
		}
		$val = bcsub(bcadd($sum, $sum1, 2), bcadd($refund, $refund1, 2), 2);
		if ($val < 0) {
			$val = 0;
		}
		return $val;
	}
}