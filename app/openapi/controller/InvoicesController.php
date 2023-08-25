<?php

namespace app\openapi\controller;

/**
 * @title 支付
 * @description 接口说明
 */
class InvoicesController extends \app\home\controller\CommonController
{
	public function invoices()
	{
		$items = config("invoice_type");
		$uid = request()->uid;
		$param = $this->request->param();
		$id = intval($param["id"]);
		$tmp = \think\Db::name("invoices")->where("uid", $uid)->where("id", $id)->where("delete_time", 0)->where("is_delete", 0)->find();
		if (empty($tmp)) {
			return json(["status" => 400, "msg" => "Bill does not exist"]);
		}
		$invoice_payment_status = config("invoice_payment_status");
		$gateways = gateway_list_openapi();
		$detail = \think\Db::name("invoices")->alias("a")->field("b.username,b.companyname,a.status,a.status as status_zh,a.paid_time,a.payment,a.payment as payment_zh,a.subtotal,a.total,a.credit,a.id,a.url,b.phonenumber,a.create_time,a.use_credit_limit")->leftJoin("clients b", "a.uid = b.id")->where("a.id", $id)->where("a.uid", $uid)->withAttr("status_zh", function ($value, $data) use($invoice_payment_status) {
			return $invoice_payment_status[$data["status"]] ?: "";
		})->withAttr("payment_zh", function ($value, $data) use($gateways) {
			foreach ($gateways as $v) {
				if ($v["name"] == $data["payment"]) {
					return $v["title"];
				}
				if ($data["subtotal"] == $data["credit"] && ($data["status"] == "Paid" || $data["status"] == "Refunded")) {
					return "余额支付";
				}
				if (1 == $data["use_credit_limit"] && ($data["status"] == "Paid" || $data["status"] == "Refunded")) {
					return "信用额支付";
				}
			}
			return "";
		})->withAttr("credit", function ($value, $data) {
			return bcsub($data["subtotal"], $data["total"], 2);
		})->find();
		$invoice_items = \think\Db::name("invoice_items")->field("type,description,description2,amount,type as type_zh,rel_id")->where("invoice_id", $id)->where("uid", $uid)->withAttr("type_zh", function ($value, $data) use($items) {
			return $items[$data["type"]] ?: "";
		})->order("rel_id", "desc")->select()->toArray();
		$accounts = \think\Db::name("accounts")->alias("a")->field("a.id,a.pay_time,a.gateway,a.trans_id,a.amount_in,a.amount_out,a.fees")->withAttr("gateway", function ($value) use($gateways) {
			foreach ($gateways as $v) {
				if ($value == $v["name"]) {
					return $v["title"];
				}
			}
			return $value;
		})->where("a.invoice_id", $id)->where("a.delete_time", 0)->select()->toArray();
		$credit_log_uses = \think\Db::name("credit")->field("id,create_time as pay_time,amount as amount_in,description as gateway")->withAttr("gateway", function ($value) use($id) {
			if ($value == "Credit Applied to Invoice #{$id}") {
				return lang("余额支付");
			}
			if ($value == "Credit Removed from Invoice #{$id}") {
				return lang("已移除余额");
			}
			if ($value == "Credit Applied to Renew Invoice #{$id}") {
				return lang("余额支付");
			}
		})->where("description", "like", "%Credit Applied to Invoice #{$id}")->whereOr("description", "like", "%Credit Removed from Invoice #{$id}")->whereOr("description", "like", "%Credit Applied to Renew Invoice #{$id}")->select()->toArray();
		foreach ($credit_log_uses as $k => $v) {
			$v["id"] = "";
			$v["trans_id"] = "";
			$v["fees"] = "";
			array_push($accounts, $v);
		}
		if (1 == $detail["use_credit_limit"] && ($detail["status"] == "Paid" || $detail["status"] == "Refunded")) {
			$credit_limit["id"] = "";
			$credit_limit["trans_id"] = "";
			$credit_limit["fees"] = "";
			$credit_limit["amount_in"] = $detail["subtotal"];
			$credit_limit["gateway"] = "信用额支付";
			$credit_limit["pay_time"] = $detail["paid_time"];
			array_push($accounts, $credit_limit);
		}
		foreach ($invoice_items as &$invoice_item) {
			$host = \think\Db::name("host")->alias("a")->field("a.id,b.groupid,a.productid")->leftJoin("products b", "a.productid = b.id")->where("a.id", $invoice_item["rel_id"])->find();
			if ($invoice_item["type"] == "host") {
				$customfields = \think\Db::name("customfields")->where([["relid", "=", $host["productid"], ["type", "=", "product"]]])->select()->toArray();
				$tmp_arr = [];
				if (!empty($customfields[0])) {
					foreach ($customfields as $v) {
						if ($v["showorder"]) {
							$customfieldsvalues = \think\Db::name("customfieldsvalues")->where([["fieldid", "=", $v["id"]], ["relid", "=", $host["id"]]])->value("value");
							$tmp_arr[] = "{$v["fieldname"]}={$customfieldsvalues}";
						}
					}
				}
				if (!empty($invoice_item["description2"])) {
					$invoice_item["description"] = $invoice_item["description2"] . "\n" . implode("\n", $tmp_arr);
				} else {
					$invoice_item["description"] .= "\n" . implode("\n", $tmp_arr);
				}
			}
			unset($invoice_item["description2"]);
			unset($invoice_item["type_zh"]);
		}
		if ($invoice_items) {
			$host_ids_arr = array_filter($invoice_items, function ($v) {
				return $v["type"] == "host" ? true : false;
			});
			if ($host_ids_arr) {
				$host_ids = array_filter(array_column($host_ids_arr, "rel_id", "id"));
				$host_data = \think\Db::name("host")->whereIn("id", $host_ids)->select()->toArray();
				if ($host_data) {
					$host_data = array_column($host_data, null, "id");
					foreach ($invoice_items as $key => $m) {
						if ($m["type"] == "host" && isset($host_data[$m["rel_id"]])) {
							$new_des = explode("\n", $m["description"]);
							$new_des_0 = explode("(", $new_des[0]);
							$new_des[0] = $new_des_0[0] . "-" . $host_data[$m["rel_id"]]["domain"];
							$new_des[0] .= $host_data[$m["rel_id"]]["dedicatedip"] ? "(" . $host_data[$m["rel_id"]]["dedicatedip"] . ")" : "";
							if (!empty($new_des_0[1])) {
								$new_des[0] .= "(" . $new_des_0[1];
							}
							$new_des = implode("\n", $new_des);
							$invoice_items[$key]["description"] = $new_des;
						}
					}
				}
			}
		}
		$client_currency = getUserCurrency($uid);
		$credit = \think\Db::name("clients")->where("id", $uid)->value("credit");
		$data = ["invoices" => ["logo" => configuration("logo_url_bill"), "username" => $detail["username"], "companyname" => configuration("company_name"), "create_time" => $detail["create_time"], "status" => $detail["status"], "total" => $detail["total"], "invoice_items" => $invoice_items], "gateways" => $gateways, "currency" => $client_currency, "client_credit" => $credit];
		if ($detail["status"] == "Paid") {
			if (!empty($accounts)) {
				foreach ($accounts as &$v1) {
					unset($v1["id"]);
					unset($v1["fees"]);
				}
			}
			$data["accounts"] = $accounts ?: [];
		}
		return json(["status" => 200, "msg" => "Success message", "data" => $data]);
	}
	public function combineInvoices()
	{
		$param = $this->request->param();
		$ids = $param["ids"];
		if ($ids && !is_array($ids)) {
			$ids = [$ids];
		}
		$uid = request()->uid;
		$payment = \think\Db::name("clients")->where("id", $uid)->value("defaultgateway");
		$where = function (\think\db\Query $query) use($uid, $ids) {
			$query->where("uid", $uid)->where("delete_time", 0)->where("is_delete", 0)->whereNotIn("type", ["recharge"]);
			if (!empty($ids)) {
				$query->whereIn("id", $ids);
			}
		};
		$invoices = \think\Db::name("invoices")->field("id,subtotal,total,payment,status,type")->where($where)->select()->toArray();
		if (empty($invoices)) {
			return json(["status" => 400, "msg" => "Please select a bill"]);
		}
		$subtotal = $total = 0;
		foreach ($invoices as $v) {
			if ($v["status"] == "Paid") {
				return json(["status" => 400, "msg" => "Bill #" . $v["id"] . "has only been paid"]);
			}
			if ($v["type"] == "combine" || $v["type"] == "recharge") {
				return json(["status" => 400, "msg" => "Consolidated bills or recharge bills #" . $v["id"] . " cannot be consolidated again"]);
			}
			$total += $v["total"];
			$subtotal += $v["subtotal"];
		}
		$invoice_data = ["uid" => $uid, "invoice_num" => "", "create_time" => time(), "due_time" => time(), "paid_time" => 0, "last_capture_attempt" => 0, "subtotal" => $subtotal, "credit" => 0, "tax" => 0, "tax2" => 0, "total" => $subtotal, "taxrate" => 0, "taxrate2" => 0, "status" => "Unpaid", "payment" => $payment, "notes" => "", "type" => "combine"];
		\think\Db::startTrans();
		try {
			$ided = \think\Db::name("invoices")->where("type", "combine")->where("uid", $uid)->where("delete_time", 0)->where("status", "Unpaid")->value("id");
			\think\Db::name("invoices")->where("type", "combine")->where("uid", $uid)->where("delete_time", 0)->where("status", "Unpaid")->delete();
			\think\Db::name("invoice_items")->where("type", "combine")->where("invoice_id", $ided)->where("uid", $uid)->where("delete_time", 0)->delete();
			$invoice_id = \think\Db::name("invoices")->insertGetId($invoice_data);
			$insert_all = [];
			foreach ($invoices as $vv) {
				$insert_all[] = ["invoice_id" => $invoice_id, "uid" => $uid, "type" => "combine", "rel_id" => $vv["id"], "description" => "合并账单#Invoice ID " . $vv["id"], "amount" => $vv["subtotal"], "taxed" => 0, "due_time" => 0, "payment" => $vv["payment"], "notes" => "", "delete_time" => 0];
			}
			\think\Db::name("invoice_items")->insertAll($insert_all);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return json(["status" => 400, "msg" => "Merge failed"]);
		}
		$data = ["id" => $invoice_id];
		if ($subtotal == 0) {
			\think\Db::name("invoices")->where("id", $invoice_id)->update(["status" => "Paid", "paid_time" => time(), "update_time" => time()]);
			$invoice_logic = new \app\common\logic\Invoices();
			$invoice_logic->processPaidInvoice($invoice_id);
			return json(["status" => 1001, "msg" => "successful purchase", "data" => $data]);
		} else {
			return json(["status" => 200, "msg" => "Merged successfully", "data" => $data]);
		}
	}
	public function fundsInfo()
	{
		$uid = request()->uid;
		$params = $this->request->only(["limit", "page", "order", "sort", "keywords"]);
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "trans_id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "DESC";
		$keywords = isset($params["keywords"]) && !empty($params["keywords"]) ? $params["keywords"] : "";
		if (!in_array($order, ["trans_id", "amount_in", "pay_time", "type", "gateway"])) {
			return json(["status" => 400, "msg" => "Sort field error"]);
		}
		$data = [];
		$credit = \think\Db::name("clients")->where("id", $uid)->value("credit");
		$currency_id = priorityCurrency($uid);
		$where = "1=1";
		if (isset($keywords[0])) {
			$arr = [];
			foreach (gateway_list() as $v) {
				if (strpos($v["title"], $keywords) !== false) {
					$arr[] = "`gateway` = \"" . $v["name"] . "\"";
				}
			}
			$arr[] = "`a`.`trans_id` like \"%" . $keywords . "%\"";
			$arr[] = "`a`.`amount_in` like \"%" . $keywords . "%\"";
			$where = implode(" OR ", $arr);
		}
		$currency = \think\Db::name("currencies")->field("id,code,prefix,suffix")->where("id", $currency_id)->find();
		$data["currency"] = $currency;
		if (!!$this->checkEnabled()) {
			$data["allow_recharge"] = 1;
			$data["credit"] = $credit;
			$data["currency"] = $currency;
			$data["gateways"] = gateway_list();
		} else {
			$data["allow_recharge"] = 0;
		}
		$count = \think\Db::name("accounts")->alias("a")->field("a.trans_id,a.amount_in,a.pay_time,a.gateway")->where("a.uid", $uid)->where("a.delete_time", 0)->count();
		$accounts = \think\Db::name("accounts")->alias("a")->field("a.trans_id,a.amount_in,a.pay_time,a.gateway,a.invoice_id,a.description")->withAttr("amount_in", function ($value, $data) use($currency) {
			if ($data["amount_out"] > 0) {
				return "-" . $data["amount_out"] . $currency["suffix"];
			} else {
				return $value . $currency["suffix"];
			}
		})->withAttr("gateway", function ($value) {
			foreach (gateway_list() as $v) {
				if ($v["name"] == $value) {
					return $v["title"];
				}
			}
		})->whereRaw($where)->where("a.uid", $uid)->where("a.delete_time", 0)->limit($limit)->page($page)->order($order, $sort)->select()->toArray();
		$accounts_filter = [];
		foreach ($accounts as $key => $account) {
			if (!empty($account) && is_null($account["trans_id"])) {
				$accounts[$key]["trans_id"] = "";
			}
			$invoice_id = $account["invoice_id"];
			if (!empty($invoice_id)) {
				$type = \think\Db::name("invoices")->where("id", $invoice_id)->value("type");
				if ($account["amount_out"] > 0) {
					$type_zh = "产品退款";
				} elseif ($type == "renew") {
					$type_zh = "续费";
				} elseif ($type == "product") {
					$type_zh = "产品";
				} elseif ($type == "recharge") {
					$type_zh = "充值";
				} else {
					$type_zh = "";
				}
			} else {
				if ($account["description"] == "推介计划佣金提现") {
					$type_zh = "推介计划佣金提现";
				} else {
					$type_zh = "退款至余额入账";
				}
			}
			$accounts[$key]["type"] = $type_zh;
		}
		$data["count"] = $count;
		$data["invoices"] = $accounts;
		$currencyId = priorityCurrency($uid);
		$user_rate = \think\Db::name("currencies")->where("id", $currencyId)->value("rate");
		$default_rate = \think\Db::name("currencies")->where("default", 1)->value("rate");
		$rate = bcdiv($user_rate, $default_rate, 2);
		$data["addfunds_minimum"] = bcmul(configuration("addfunds_minimum"), $rate, 2);
		$data["addfunds_maximum"] = bcmul(configuration("addfunds_maximum"), $rate, 2);
		$data["addfunds_maximum_balance"] = bcmul(configuration("addfunds_maximum_balance"), $rate, 2);
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function funds()
	{
		if ($this->checkEnabled() != 1) {
			return json(["status" => 400, "msg" => "Recharge is not open"]);
		}
		$uid = $this->request->uid;
		$param = $this->request->param();
		$validate = new \app\home\validate\RechargeValidate();
		if (!$validate->check($param)) {
			return json(["status" => 400, "msg" => $validate->getError()]);
		}
		if (!get_gateway_status($param["payment"])) {
			return json(["status" => 400, "msg" => "Gateway does not exist"]);
		}
		$currencyId = priorityCurrency($uid);
		$user_rate1 = \think\Db::name("currencies")->where("id", $currencyId)->value("prefix");
		$user_rate = \think\Db::name("currencies")->where("id", $currencyId)->value("rate");
		$default_rate = \think\Db::name("currencies")->where("default", 1)->value("rate");
		$rate = bcdiv($user_rate, $default_rate, 2);
		$pay_rate = bcdiv($default_rate, $user_rate, 2);
		$data = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "subtotal" => $param["amount"], "total" => $param["amount"], "status" => "Unpaid", "payment" => $param["payment"], "type" => "recharge"];
		$data2 = ["uid" => $uid, "type" => "recharge", "description" => "用户充值", "amount" => $param["amount"], "due_time" => strtotime("+365 day")];
		$res = \think\Db::name("invoices")->where(["uid" => $uid, "status" => "Unpaid", "type" => "recharge", "delete_time" => 0])->find();
		$flag = true;
		$invoice_id = null;
		$credit = db("clients")->where(["id" => $uid])->value("credit");
		$userMinRecharge = configuration("addfunds_minimum") * $rate;
		if ($param["amount"] < $userMinRecharge) {
			$tmp_userMinRecharge = ceil($userMinRecharge * 100) / 100;
			return json(["msg" => "Minimum recharge amount:{$tmp_userMinRecharge}", "status" => 400]);
		}
		$userMaxRecharge = configuration("addfunds_maximum") * $rate;
		if ($userMaxRecharge < $param["amount"]) {
			return json(["msg" => "Maximum recharge amount:{$userMaxRecharge}", "status" => 400]);
		}
		$userMaxCredit = configuration("addfunds_maximum_balance") * $rate;
		if ($userMaxCredit < $credit + $param["amount"]) {
			return json(["msg" => "Exceeded the maximum allowed balance:{$userMaxCredit}", "status" => 400]);
		}
		if (!$this->checkActivate($uid)) {
			return json(["msg" => "You need an activated order to recharge", "status" => 400]);
		}
		\think\Db::startTrans();
		try {
			if (!empty($res)) {
				if ($res["credit"] > 0) {
					\think\Db::name("clients")->where("id", $uid)->setInc("credit", $res["credit"]);
				}
				$accounts = \think\Db::name("accounts")->where("invoice_id", $res["id"])->select()->toArray();
				$amount_in = $amount_out = 0;
				foreach ($accounts as $account) {
					$amount_in += $account["amount_in"];
					$amount_out += $account["amount_out"];
				}
				$credit = $amount_in - $amount_out;
				if ($credit > 0) {
					\think\Db::name("clients")->where("id", $uid)->setInc("credit", $credit);
				}
				\think\Db::name("invoices")->where("id", $res["id"])->update(["is_delete" => 1]);
			}
			$invoice_id = \think\Db::name("invoices")->insertGetId($data);
			$url = "viewbilling?id=" . $invoice_id;
			\think\Db::name("invoices")->where("id", $invoice_id)->update(["url" => $url]);
			$data2["invoice_id"] = $invoice_id;
			$ii = \think\Db::name("invoice_items")->insertGetId($data2);
			\think\Db::commit();
		} catch (\Exception $e) {
			$flag = false;
			trace($e->getMessage(), "error");
		}
		if ($flag) {
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["invoice_id" => $invoice_id]]);
		} else {
			return json(["status" => 400, "msg" => lang("ERROR MESSAGE")]);
		}
	}
	public function accountsRecord()
	{
		$uid = request()->uid;
		$params = $this->request->param();
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "a.id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "DESC";
		$where = function (\think\db\Query $query) use($uid) {
			$query->where("a.uid", $uid)->whereIn("b.type", ["renew", "product", "upgrade", "zjmf_reinstall_times", "zjmf_flow_packet", "combine", "recharge"])->where("a.refund", 0)->where("a.invoice_id", ">", 0)->where("b.delete_time", 0);
		};
		$items = config("invoice_type_all");
		$gateways = gateway_list();
		$accounts = \think\Db::name("accounts")->alias("a")->leftJoin("invoices b", "a.invoice_id = b.id")->field("a.id,a.invoice_id,a.pay_time,a.gateway as payment_zh,a.description,b.type,a.amount_in,a.trans_id")->where($where)->withAttr("payment_zh", function ($value, $data) use($gateways) {
			foreach ($gateways as $v) {
				if ($v["name"] == $value) {
					return $v["title"];
				}
			}
			return $value;
		})->order($order, $sort)->order("a.pay_time", "desc")->page($page)->limit($limit)->select()->toArray();
		foreach ($accounts as &$vv) {
			$refund = \think\Db::name("accounts")->field("id,amount_out")->where("refund", $vv["id"])->where("invoice_id", $vv["invoice_id"])->where("delete_time", 0)->where("refund", ">", 0)->select()->toArray();
			if (!empty($vv) && is_null($vv["trans_id"])) {
				$vv["trans_id"] = "";
			}
			$vv["refund"] = $refund ?: (object) [];
		}
		$total = \think\Db::name("accounts")->alias("a")->leftJoin("invoices b", "a.invoice_id = b.id")->where($where)->count();
		$client_currency = getUserCurrency($uid);
		$data = ["total" => $total, "accounts" => $accounts, "currency" => $client_currency];
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	private function checkActivate($uid)
	{
		$status = configuration("addfunds_require_order");
		if ($status == 1) {
			$res = db("orders")->where("uid", $uid)->where("delete_time", 0)->where("status", "Active")->value("id");
			if (empty($res)) {
				return false;
			}
		}
		return true;
	}
	private function checkEnabled()
	{
		return configuration("addfunds_enabled");
	}
}