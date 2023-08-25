<?php

namespace app\home\controller;

/**
 * @title 支付
 * @description 接口说明
 */
class PayController extends CommonController
{
	/**
	 * @title 可用【支付】网关列表
	 * @description 接口说明
	 * @author 上官🔪
	 * @url /get_gateways/[:gateways]/
	 * @method get
	 * @param .name:gateways type:string require:1 default:gateways other: desc:模块名[gateways,addons]
	 * @return .id:id
	 * @return .name:网关名
	 * @return .title:描述
	 * @return .module:所属模块
	 */
	public function getGatewayList($module = "gateways")
	{
		$data = gateway_list($module);
		return jsons(["status" => 200, "data" => $data, "msg" => lang("SUCCESS MESSAGE")]);
	}
	/**
	 * @title
	 * 充值中心页面
	 * @description 接口说明:充值中心页面
	 * @author wyh
	 * @url /recharge_page
	 * @param .name:page type:int require:0  other: desc:页码
	 * @param .name:size type:int require:0  other: desc:长度
	 * @param .name:order type:string require:0  other: desc:排序字段(trans_id,amount_in,pay_time,type,gateway)
	 * @param .name:sort type:string require:0  other: desc:排序规则(asc/desc)
	 * @param .name:keywords type:string require:0  other: desc:关键字搜索(trans_id,amount_in)
	 * @method get
	 * @return trans_id:流水单号
	 * @return amount_in:金额
	 * @return pay_time:交易日期
	 * @return type:来源
	 * @return gateway:支付方式
	 */
	public function rechargePage()
	{
		$uid = request()->uid;
		$params = $this->request->only(["limit", "page", "order", "sort", "keywords"]);
		$page = !empty($params["page"]) ? intval($params["page"]) : config("page");
		$limit = !empty($params["limit"]) ? intval($params["limit"]) : config("limit");
		$order = !empty($params["order"]) ? trim($params["order"]) : "trans_id";
		$sort = !empty($params["sort"]) ? trim($params["sort"]) : "DESC";
		$keywords = isset($params["keywords"]) && !empty($params["keywords"]) ? $params["keywords"] : "";
		if (!in_array($order, ["trans_id", "amount_in", "pay_time", "type", "gateway"])) {
			return jsons(["status" => 400, "msg" => lang("排序字段错误")]);
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
		$currency = \think\Db::name("currencies")->field("id,prefix,suffix,code")->where("id", $currency_id)->find();
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
		$accounts = \think\Db::name("accounts")->alias("a")->field("a.trans_id,a.amount_in,a.pay_time,a.gateway,a.amount_out,a.invoice_id,a.description")->withAttr("amount_in", function ($value, $data) use($currency) {
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
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 余额充值
	 * @description 接口说明
	 * @author 上官🔪
	 * @url /recharge
	 * @method post
	 * @param .name:payment type:string require:1  other: desc:网关名(如WxPay)
	 * @param .name:amount type:decimal require:1  other: desc:金额
	 * @return
	 * @throws
	 * @route('recharge','get')
	 */
	public function recharge()
	{
		if ($this->checkEnabled() != 1) {
			return jsons(["status" => 400, "msg" => "充值未开放"]);
		}
		$uid = $this->request->uid;
		$param = $this->request->param();
		$validate = new \app\home\validate\RechargeValidate();
		if (!$validate->check($param)) {
			return jsons(["status" => 400, "msg" => $validate->getError()]);
		}
		if (!get_gateway_status($param["payment"])) {
			return jsons(["status" => 400, "msg" => " 不存在的网关！"]);
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
			return jsons(["msg" => "最小充值金额:{$tmp_userMinRecharge}", "status" => 400]);
		}
		$userMaxRecharge = configuration("addfunds_maximum") * $rate;
		if ($userMaxRecharge < $param["amount"]) {
			return jsons(["msg" => "最大充值金额:{$userMaxRecharge}", "status" => 400]);
		}
		$userMaxCredit = configuration("addfunds_maximum_balance") * $rate;
		if ($userMaxCredit < $credit + $param["amount"]) {
			return jsons(["msg" => "超出允许的余额上限:{$userMaxCredit}", "status" => 400]);
		}
		if (!$this->checkActivate($uid)) {
			return jsons(["msg" => "你需要有激活的订单后方可充值", "status" => 400]);
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
			return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["invoice_id" => $invoice_id]]);
		} else {
			return jsons(["status" => 400, "msg" => "天!!! 启动支付失败了..."]);
		}
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
	/**
	 * @title 账单页面数据
	 * @description 接口说明:账单页面数据
	 * @author 萧十一郎
	 * @url /invoice_page
	 * @method POST
	 * @param .name:invoiceid type:number require:1 default: other: desc:账单id
	 * @return invoice_subtotal:小计
	 * @return invoice_credit:账单已使用余额
	 * @return invoice_total:合计
	 * @return due_time:账单到期时间
	 * @return item_data:账单子项数据@
	 * @item_data  description:账单子项描述
	 * @item_data  type:账单子项类型
	 * @item_data  amount:账单子项金额
	 * @return gateway_list:支持的网关数据
	 * @return user_credit:用户可用余额
	 */
	public function invoicePage(\think\Request $request)
	{
		$uid = $request->uid;
		$invoiceid = input("post.invoiceid", null, "int");
		if (empty($invoiceid)) {
			return jsons(["status" => "406", "msg" => "账单id不能为空"]);
		}
		$invoice_data = \think\Db::name("invoices")->where("id", $invoiceid)->where("uid", $uid)->find();
		if ($invoice_data["status"] == "Paid" || $invoice_data["total"] == 0) {
			return jsons(["status" => "406", "msg" => "账单已支付"]);
		}
		if (empty($invoice_data) || !empty($invoice_data["delete_time"])) {
			return jsons(["status" => "406", "msg" => "账单已过期过或未找到"]);
		}
		$currency = getUserCurrency($uid);
		$prefix = $currency["prefix"];
		$suffix = $currency["suffix"];
		$returndata = [];
		$returndata["invoice_subtotal"] = $invoice_data["subtotal"];
		$returndata["invoice_credit"] = $invoice_data["credit"];
		$returndata["invoice_total"] = $invoice_data["total"];
		$returndata["due_time"] = $invoice_data["due_time"];
		$item_data = \think\Db::name("invoice_items")->field("amount,type,description")->where("invoice_id", $invoiceid)->select()->toArray();
		$returndata["item_data"] = $item_data;
		$returndata["gateway_list"] = gateway_list("gateways");
		$user_info = \think\Db::name("clients")->field("credit")->where("id", $uid)->find();
		$user_credit = $user_info["credit"];
		$returndata["user_credit"] = $user_credit;
		return jsons(["status" => 200, "data" => $returndata]);
	}
	/**
	 * @title 使用余额--页面
	 * @description 接口说明:使用余额--页面
	 * @author wyh
	 * @url /use_credit_page
	 * @method GET
	 * @param .name:invoiceid type:number require:1 default: other: desc:要支付的账单id
	 * @return invoiceid:账单ID
	 * @return invoice_credit:账单已使用余额
	 * @return total:账单总额
	 * @return credit:用户余额
	 * @return amount:剩余需支付金额
	 * @return currency:货币信息
	 * @return used:是否已使用余额,1是（打钩），0否
	 */
	public function useCreditPage()
	{
		$params = $this->request->param();
		$invoice_id = $params["invoiceid"];
		$invoice = \think\Db::name("invoices")->where("id", $invoice_id)->where("delete_time", 0)->find();
		$uid = \request()->uid;
		$curerncy_id = priorityCurrency($uid);
		$currency = (new \app\common\logic\Currencies())->getCurrencies("id,code,prefix,suffix", $curerncy_id)[0];
		$credit = \think\Db::name("clients")->where("id", $uid)->value("credit");
		$data = [];
		$data["invoiceid"] = $invoice_id;
		$data["invoice_credit"] = $invoice["credit"];
		$data["total"] = $invoice["subtotal"];
		$data["credit"] = $credit;
		$data["amount"] = bcsub($invoice["subtotal"], $credit, 2);
		$data["currency"] = $currency;
		$data["used"] = $invoice["subtotal"] > $invoice["total"] ? 1 : 0;
		$data["gateway_list"] = gateway_list();
		if ($invoice["subtotal"] <= $credit) {
			$data["deduction"] = $invoice["subtotal"];
		} else {
			$data["deduction"] = $credit;
		}
		$defaultgateway = \think\Db::name("clients")->where("id", $uid)->value("defaultgateway");
		if (!in_array($defaultgateway, array_column($data["gateway_list"], "name"))) {
			$data["payment"] = array_column($data["gateway_list"], "name")[0];
		} else {
			$data["payment"] = $defaultgateway;
		}
		return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	/**
	 * @title 向账单使用余额
	 * @description 接口说明:添加后需要重刷账单
	 * @author 萧十一郎
	 * @url /apply_credit
	 * @method POST
	 * @param .name:invoiceid type:number require:1 default: other: desc:账单id
	 * @param .name:use_credit type:float require:1 default: other: desc:1使用余额,0不使用
	 * @param .name:enough type:int require:0 default:0 other: desc:全部够才支付
	 */
	public function applyCredit(\think\Request $request)
	{
		$uid = $request->uid;
		$param = $request->param();
		$invoiceid = intval($param["invoiceid"]);
		$use_credit = $param["use_credit"];
		$check_res = $this->checkInvoice($uid, $invoiceid);
		if ($check_res["status"] == 200) {
			$invoice_data = $check_res["data"];
		} else {
			return jsons($check_res);
		}
		if (!$use_credit) {
			$invoice_data = ["credit" => 0, "total" => $invoice_data["subtotal"]];
			\think\Db::name("invoices")->where("id", $invoiceid)->update($invoice_data);
			return jsons(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["invoiceid" => $invoiceid]]);
		}
		$is_downstream = false;
		if ($request->is_api == 1) {
			$downstream_data = input("post.");
			$is_downstream = (strpos($downstream_data["downstream_url"], "https://") === 0 || strpos($downstream_data["downstream_url"], "http://") === 0) && strlen($downstream_data["downstream_token"]) == 32 && is_numeric($downstream_data["downstream_id"]);
		}
		$invoice_credit = $invoice_data["credit"];
		$user_credit = \think\Db::name("clients")->where("id", $uid)->value("credit");
		if ($user_credit <= 0) {
			return jsons(["status" => 400, "msg" => "当前余额小于等于0,不可使用余额"]);
		}
		$invoic_subtotal = $invoice_data["subtotal"];
		if ($invoic_subtotal < $user_credit) {
			$user_credit = $invoic_subtotal;
		}
		if ($user_credit < $invoic_subtotal && $use_credit && $param["is_api"] == 1) {
			$result = ["status" => 400, "msg" => "余额不足：账单需{$invoic_subtotal}，现余额{$user_credit}"];
			if ($is_downstream) {
				$result["msg"] .= ",上游账单#" . $invoiceid . "完成支付后即可开通";
			}
			return jsons($result);
		}
		$surplus = getSurplus($invoiceid);
		if ($surplus < $user_credit) {
			$user_credit = $surplus;
		}
		$paid_invoice_credit = $user_credit + $invoice_credit + $invoic_subtotal - $invoice_data["total"];
		$paid_invoice_total = bcsub($invoic_subtotal, $paid_invoice_credit, 2);
		$time = time();
		if ($paid_invoice_total == 0) {
			$update_invoice = ["paid_time" => $time, "credit" => $paid_invoice_credit, "total" => $paid_invoice_total, "status" => "Paid", "payment_status" => "Paid"];
			hook("invoice_paid", ["invoice_id" => $invoiceid]);
			\think\Db::startTrans();
			try {
				\think\Db::name("invoices")->where("id", $invoiceid)->update($update_invoice);
				$virtual_credit = $user_credit + $invoice_data["subtotal"] - $invoice_data["total"] - $invoice_credit;
				if ($virtual_credit > 0) {
					$virtual = \think\Db::name("clients")->where("id", $uid)->where("credit", ">=", $virtual_credit)->setDec("credit", $virtual_credit);
					if (empty($virtual)) {
						active_log(sprintf($this->lang["Order_admin_clients_updatecredit_fail"], $uid), $uid);
						throw new \Exception("余额不足");
					}
					credit_log(["uid" => $uid, "desc" => "Credit Applied to Invoice #" . $invoiceid, "amount" => $user_credit, "relid" => $invoiceid]);
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return jsons(["status" => 400, "msg" => "支付失败:" . $e->getMessage()]);
			}
			$invoice_logic = new \app\common\logic\Invoices();
			$invoice_logic->processPaidInvoice($invoiceid);
			$result["status"] = 1001;
			$result["msg"] = "支付完成";
			$result["data"]["hostid"] = \think\Db::name("invoice_items")->where("invoice_id", $invoiceid)->where("type", "host")->where("delete_time", 0)->column("rel_id");
			$result["data"]["url"] = $invoice_data["url"] ?: "";
			if ((strpos($param["downstream_url"], "https://") === 0 || strpos($param["downstream_url"], "http://") === 0) && strlen($param["downstream_token"]) == 32 && is_numeric($param["downstream_id"])) {
				$stream_info = \think\Db::name("host")->where("id", \intval($result["data"]["hostid"][0]))->value("stream_info");
				$stream_info = json_decode($stream_info, true) ?: [];
				$stream_info["downstream_url"] = $param["downstream_url"];
				$stream_info["downstream_token"] = $param["downstream_token"];
				$stream_info["downstream_id"] = $param["downstream_id"];
				\think\Db::name("host")->where("id", \intval($result["data"]["hostid"][0]))->update(["stream_info" => json_encode($stream_info)]);
			}
			return jsons($result);
		} else {
			if ($param["enough"] == 1) {
				return jsons(["status" => 400, "msg" => "余额不足"]);
			}
			\think\Db::name("invoices")->where("id", $invoiceid)->update(["total" => $paid_invoice_total]);
			return jsons(["status" => 200, "msg" => "使用余额成功", "data" => ["invoiceid" => $invoiceid, "url" => $invoice_data["url"] ?: ""]]);
		}
	}
	/**
	 * @title 向账单使用信用额
	 * @description 接口说明:向账单使用信用额
	 * @author xj
	 * @url /apply_credit_limit
	 * @method POST
	 * @param .name:invoiceid type:number require:1 default: other: desc:账单id
	 * @param .name:use_credit_limit type:float require:1 default: other: desc:1使用余额,0不使用
	 * @param .name:enough type:int require:0 default:0 other: desc:全部够才支付
	 */
	public function applyCreditLimit(\think\Request $request)
	{
		$uid = $request->uid;
		$param = $request->param();
		$invoiceid = intval($param["invoiceid"]);
		$use_credit = $param["use_credit_limit"];
		$client = \think\Db::name("clients")->field("credit,credit_limit,is_open_credit_limit,currency")->where("id", $uid)->find();
		$client["is_open_credit_limit"] = configuration("credit_limit") == 1 ? $client["is_open_credit_limit"] : 0;
		if ($client["is_open_credit_limit"] == 0) {
			return jsons(["status" => 400, "msg" => "系统不支持信用额支付"]);
		}
		$check_res = $this->checkInvoice($uid, $invoiceid);
		if ($check_res["status"] == 200) {
			$invoice_data = $check_res["data"];
			if ($invoice_data["credit"] > 0) {
				return jsons(["status" => 400, "msg" => "当前账单使用了余额,不可使用信用额支付"]);
			}
			if ($invoice_data["type"] == "credit_limit") {
				return jsons(["status" => 400, "msg" => "信用额账单不可使用信用额支付"]);
			}
		} else {
			return jsons($check_res);
		}
		$credit_limit = \think\Db::name("clients")->where("id", $uid)->value("credit_limit");
		$amount_to_be_settled = \think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", $uid)->sum("total");
		$unpaid = \think\Db::name("invoices")->where("type", "credit_limit")->where("status", "Unpaid")->where("is_delete", 0)->where("uid", $uid)->sum("total");
		$credit_limit_used = number_format($amount_to_be_settled + $unpaid, 2, ".", "");
		$use_credit_limit = number_format($credit_limit - $credit_limit_used > 0 ? $credit_limit - $credit_limit_used : 0, 2, ".", "");
		if ($use_credit_limit < $invoice_data["total"]) {
			return jsons(["status" => 400, "msg" => "当前信用额余额不足,不可使用信用额支付"]);
		}
		$time = time();
		$update_invoice = ["paid_time" => $time, "status" => "Paid", "use_credit_limit" => 1, "payment_status" => "Paid"];
		hook("invoice_paid", ["invoice_id" => $invoiceid]);
		\think\Db::startTrans();
		try {
			\think\Db::name("invoices")->where("id", $invoiceid)->update($update_invoice);
			$IncoiceInfo = \think\Db::name("invoices")->where("id", $invoiceid)->where("delete_time", 0)->find();
			$client_credit = \think\Db::name("clients")->where("id", $IncoiceInfo["uid"])->value("credit");
			$invoice_credit = $IncoiceInfo["subtotal"] - $IncoiceInfo["total"];
			$client_credit = round($client_credit, 3);
			$invoice_credit = round($invoice_credit, 3);
			if ($invoice_credit > 0) {
				if ($invoice_credit <= $client_credit + 0.01) {
					$up_data["status"] = "Paid";
					$up_data["paid_time"] = time();
					$up_data["credit"] = $invoice_credit;
					\think\Db::name("invoices")->where("id", $IncoiceInfo["id"])->update($up_data);
					if ($invoice_credit <= $client_credit) {
						\think\Db::name("clients")->where("id", $IncoiceInfo["uid"])->setDec("credit", $invoice_credit);
					} else {
						\think\Db::name("clients")->where("id", $IncoiceInfo["uid"])->setDec("credit", $client_credit);
					}
					credit_log(["uid" => $IncoiceInfo["uid"], "desc" => "Credit Applied to Invoice #" . $IncoiceInfo["id"], "amount" => $invoice_credit, "relid" => $IncoiceInfo["id"]]);
				} else {
					active_logs(sprintf("部分余额支付失败,失败原因：余额不足(可能将余额使用至另一订单) - 账单号#Invoice ID:%d - 交易单号#Transaction ID:%s", $IncoiceInfo["id"], ""), $IncoiceInfo["uid"]);
					active_logs(sprintf("部分余额支付失败,失败原因：余额不足(可能将余额使用至另一订单) - 账单号#Invoice ID:%d - 交易单号#Transaction ID:%s", $IncoiceInfo["id"], ""), $IncoiceInfo["uid"], "", 2);
					throw new \Exception("余额不足");
				}
			}
			if ($invoice_data["total"] > 0) {
				$credit_limit = \think\Db::name("clients")->where("id", $uid)->value("credit_limit");
				$amount_to_be_settled = \think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", $uid)->sum("total");
				$unpaid = \think\Db::name("invoices")->where("type", "credit_limit")->where("status", "Unpaid")->where("is_delete", 0)->where("uid", $uid)->sum("total");
				$credit_limit_used = number_format($amount_to_be_settled + $unpaid - $invoice_data["total"], 2, ".", "");
				$use_credit_limit = number_format($credit_limit - $credit_limit_used > 0 ? $credit_limit - $credit_limit_used : 0, 2, ".", "");
				if ($use_credit_limit < $invoice_data["total"]) {
					active_log(sprintf($this->lang["Order_admin_clients_updatecreditlimit_fail"], $uid), $uid);
					throw new \Exception("剩余信用额不足");
				}
			}
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return jsons(["status" => 400, "msg" => "支付失败:" . $e->getMessage()]);
		}
		$invoice_logic = new \app\common\logic\Invoices();
		$invoice_logic->processPaidInvoice($invoiceid);
		$result["status"] = 1001;
		$result["msg"] = "支付完成";
		$result["data"]["hostid"] = \think\Db::name("invoice_items")->where("invoice_id", $invoiceid)->where("type", "host")->where("delete_time", 0)->column("rel_id");
		$result["data"]["url"] = $invoice_data["url"] ?: "";
		if ((strpos($param["downstream_url"], "https://") === 0 || strpos($param["downstream_url"], "http://") === 0) && strlen($param["downstream_token"]) == 32 && is_numeric($param["downstream_id"])) {
			$stream_info = \think\Db::name("host")->where("id", \intval($result["data"]["hostid"][0]))->value("stream_info");
			$stream_info = json_decode($stream_info, true) ?: [];
			$stream_info["downstream_url"] = $param["downstream_url"];
			$stream_info["downstream_token"] = $param["downstream_token"];
			$stream_info["downstream_id"] = $param["downstream_id"];
			\think\Db::name("host")->where("id", \intval($result["data"]["hostid"][0]))->update(["stream_info" => json_encode($stream_info)]);
		}
		return jsons($result);
	}
	/**
	 * @title 获取网关支付页面数据
	 * @description 接口说明:返回相应的支付html，按钮/二维码链接(需要在前端执行一个3秒后执行该html提交动作的js,用于自动跳转到支付)
	 * @author 萧十一郎
	 * @url /start_pay
	 * @method POST
	 * @param .name:invoiceid type:number require:1 default: other: desc:要支付的账单id
	 * @param .name:payment type:string require:0 default: other: desc:支付方式
	 * @param .name:flag type:number require:0 default: other: desc:是否不调三方支付:1是
	 * @return payment:支付方式
	 * @return gateway_list:支付方式列表数据@
	 * @gateway_list name:支付方式
	 * @gateway_list title:名称
	 * @return total:金额
	 * @return total_desc:金额
	 * @return pay_html:支付html
	 */
	public function startPay(\think\Request $request)
	{
		$param = $request->param();
		$uid = $request->uid;
		$payment = $param["payment"];
		$flag = $param["flag"] ? $param["flag"] : false;
		$invoiceid = intval($param["invoiceid"]);
		$check_res = $this->checkInvoice($uid, $invoiceid);
		if ($check_res["status"] == 200) {
			$invoice_data = $check_res["data"];
		} else {
			return jsons($check_res);
		}
		$returndata = [];
		$total = $invoice_data["total"];
		$payment = $payment ?: $invoice_data["payment"];
		$currency = getUserCurrency($uid);
		$returndata["gateway_list"] = gateway_list();
		$payment_name_list = array_column($returndata["gateway_list"], "name");
		if (!in_array($payment, $payment_name_list)) {
			$payment = $payment_name_list[0];
		}
		$returndata["payment"] = $payment;
		$returndata["total"] = $total;
		$returndata["total_desc"] = $total . $currency["suffix"];
		$credit = \think\Db::name("clients")->where("id", $uid)->value("credit");
		$returndata["credit"] = $credit;
		$returndata["invoiceid"] = $invoiceid;
		if (!$flag) {
			try {
				$pay_html = start_pay($invoiceid, $payment);
			} catch (\Exception $e) {
				return jsons(["status" => 406, "msg" => $e->getMessage(), "data" => $returndata]);
			}
			$pluginName = $payment;
			$class = cmf_get_plugin_class_shd($payment, "gateways");
			$methods = get_class_methods($class);
			if (in_array(lcfirst($pluginName) . "idcsmartauthorize", $methods) || in_array($pluginName . "idcsmartauthorize", $methods)) {
				$res = pluginIdcsmartauthorize($pluginName);
				if ($res["status"] != 200) {
					return jsonrule($res);
				}
			}
			if (!isset($pay_html["data"][0])) {
				$error = $pay_html["error"] ?: $pay_html["msg"];
				return jsons(["status" => 406, "msg" => "支付接口配置错误!或" . $error, "data" => $returndata]);
			}
			$returndata["pay_html"] = $pay_html;
		}
		return jsons(["status" => 200, "data" => $returndata]);
	}
	/**
	 * 检查账单id，是否存在，未支付，并且未过期
	 */
	private function checkInvoice($uid, $invoiceid)
	{
		if (empty($invoiceid)) {
			return ["status" => "406", "msg" => "未找到支付项目"];
		}
		$invoice_data = \think\Db::name("invoices")->where("id", $invoiceid)->where("uid", $uid)->find();
		if (empty($invoice_data)) {
			return ["status" => "406", "msg" => "账单未找到"];
		}
		if ($invoice_data["status"] == "Paid" || $invoice_data["total"] == 0) {
			return ["status" => "406", "msg" => "账单已支付", "data" => ["PayStatus" => "Paid"]];
		}
		if (!empty($invoice_data["delete_time"])) {
			return ["status" => "406", "msg" => "账单已过期"];
		}
		if ($invoice_data["type"] == "upgrade") {
			$upgrade = \think\Db::name("upgrades")->alias("a")->leftJoin("orders b", "a.order_id=b.id")->leftJoin("invoices c", "c.id=b.invoiceid")->where("c.id", $invoiceid)->where("c.uid", $uid)->where("b.uid", $uid)->where("c.uid", $uid)->where("a.days_remaining", 1)->find();
			if (!empty($upgrade)) {
				return ["status" => 400, "msg" => "账单已失效,请重新升降级下单"];
			}
		}
		return ["status" => 200, "data" => $invoice_data];
	}
	/**
	 * @title 更新支付选择模式
	 * @param Request $request
	 * @url /change_paymt
	 * @method POST
	 */
	public function changePaymt(\think\Request $request)
	{
		$param = $request->param();
		$invoiceid = $param["invoiceid"];
		$paymt = $param["paymt"];
		\think\Db::name("invoices")->where("id", $invoiceid)->update(["use_credit_limit" => $paymt]);
		return jsons(["status" => 200, "data" => []]);
	}
	public function invoicesidCreateTmp($invoice)
	{
		$original_invoicesid = \think\Db::name("invoicesid_tmp")->where(["new_invoicesid" => $invoice["id"]])->find();
		if (!$original_invoicesid) {
			return false;
		}
		$invoicesid_tmp = \think\Db::name("invoicesid_tmp")->where(["original_invoicesid" => $original_invoicesid["original_invoicesid"]])->select()->toArray();
		$newinvoiceid_tmp = 0;
		foreach ($invoicesid_tmp as $v) {
			if ($v["total"] == $invoice["total"]) {
				$newinvoiceid_tmp = $v["new_invoicesid"];
				break;
			}
		}
		if ($newinvoiceid_tmp == 0) {
			$invoice_tmp = $invoice;
			unset($invoice_tmp["id"]);
			foreach ($invoice_tmp as $k => $v) {
				$newinvoice_tmp[$k] = $v;
				if ($v == null) {
					$newinvoice_tmp[$k] = "";
				}
			}
			$newinvoice_tmp["total"] = $newinvoice_tmp["subtotal"];
			$newinvoiceid_tmp = \think\Db::name("invoices")->insertGetId($newinvoice_tmp);
			\think\Db::name("invoices")->where("id", $newinvoiceid_tmp)->delete();
			$this->invoicesidTmp($invoice["id"], $newinvoiceid_tmp, $invoice["total"]);
		}
		return $newinvoiceid_tmp;
	}
	public function invoicesidTmp($invoiceid, $newinvoiceid, $total)
	{
		$invoices = \think\Db::name("invoicesid_tmp")->where(["new_invoicesid" => $invoiceid])->find();
		if ($invoices["new_invoicesid"] == $newinvoiceid) {
			return true;
		}
		$invoicesid_tmp = ["original_invoicesid" => $invoices["original_invoicesid"] ?: $invoiceid, "old_invoicesid" => $invoiceid, "new_invoicesid" => $newinvoiceid, "total" => $total];
		\think\Db::name("invoicesid_tmp")->insertGetId($invoicesid_tmp);
	}
}