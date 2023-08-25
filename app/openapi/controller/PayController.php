<?php

namespace app\openapi\controller;

/**
 * @title 支付
 * @description 接口说明
 */
class PayController extends \app\home\controller\CommonController
{
	public function pay()
	{
		$param = $this->request->param();
		$uid = $this->request->uid;
		$payment = $param["payment"];
		$flag = $param["flag"] ?: false;
		$invoiceid = intval($param["invoiceid"]);
		$check_res = $this->checkInvoice($uid, $invoiceid);
		if ($check_res["status"] != 200) {
			return json($check_res);
		}
		$invoice_data = $check_res["data"];
		$returndata = [];
		$total = $invoice_data["total"];
		$payment = $payment ?: $invoice_data["payment"];
		$currency = getUserCurrency($uid);
		$returndata["gateway_list"] = gateway_list_openapi();
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
				return json(["status" => 400, "msg" => $e->getMessage(), "data" => $returndata]);
			}
			$pluginName = $payment;
			$class = cmf_get_plugin_class_shd($payment, "gateways");
			$methods = get_class_methods($class);
			if (in_array(lcfirst($pluginName) . "idcsmartauthorize", $methods) || in_array($pluginName . "idcsmartauthorize", $methods)) {
				$res = pluginIdcsmartauthorize($pluginName);
				if ($res["status"] != 200) {
					return json($res);
				}
			}
			if (!isset($pay_html["data"][0])) {
				$error = $pay_html["error"] ?: $pay_html["msg"];
				return json(["status" => 400, "msg" => "Payment interface configuration error! or " . $error, "data" => $returndata]);
			}
			$returndata["pay_html"] = $pay_html;
		}
		if ($uid) {
			$client = \think\Db::name("clients")->field("credit,credit_limit,is_open_credit_limit")->where("id", request()->uid)->find();
			$client["is_open_credit_limit"] = configuration("credit_limit") == 1 ? $client["is_open_credit_limit"] : 0;
			$client["amount_to_be_settled"] = \think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", request()->uid)->sum("total");
			$unpaid = \think\Db::name("invoices")->where("type", "credit_limit")->where("status", "Unpaid")->where("is_delete", 0)->where("uid", request()->uid)->sum("total");
			$client["credit_limit_used"] = round($client["amount_to_be_settled"] + $unpaid, 2);
			$client["credit_limit_balance"] = round($client["credit_limit"] - $client["credit_limit_used"] > 0 ? $client["credit_limit"] - $client["credit_limit_used"] : 0, 2);
			$returndata["client"] = $client;
		}
		$returndata["is_open_shd_credit_limit"] = configuration("shd_credit_limit");
		return json(["status" => 200, "data" => $returndata]);
	}
	/**
	 * 检查账单id，是否存在，未支付，并且未过期
	 */
	private function checkInvoice($uid, $invoiceid)
	{
		if (empty($invoiceid)) {
			return ["status" => 400, "msg" => "Payment item not found"];
		}
		$invoice_data = \think\Db::name("invoices")->where("id", $invoiceid)->where("uid", $uid)->find();
		if (empty($invoice_data)) {
			return ["status" => 400, "msg" => "Bill not found"];
		}
		if ($invoice_data["status"] == "Paid" || $invoice_data["total"] == 0) {
			return ["status" => 400, "msg" => "Bill paid", "data" => ["PayStatus" => "Paid"]];
		}
		if (!empty($invoice_data["delete_time"])) {
			return ["status" => 400, "msg" => "Bill has expired"];
		}
		if ($invoice_data["type"] == "upgrade") {
			$upgrade = \think\Db::name("upgrades")->alias("a")->leftJoin("orders b", "a.order_id=b.id")->leftJoin("invoices c", "c.id=b.invoiceid")->where("c.id", $invoiceid)->where("c.uid", $uid)->where("b.uid", $uid)->where("c.uid", $uid)->where("a.days_remaining", 1)->find();
			if (!empty($upgrade)) {
				return ["status" => 400, "msg" => "The bill has expired, please upgrade and order again"];
			}
		}
		return ["status" => 200, "data" => $invoice_data];
	}
	public function fund()
	{
		$uid = $this->request->uid;
		$param = $this->request->param();
		$invoiceid = intval($param["id"]);
		$use_credit = isset($param["use_credit"]) ? intval($param["use_credit"]) : 1;
		$check_res = $this->checkInvoice($uid, $invoiceid);
		if ($check_res["status"] == 200) {
			$invoice_data = $check_res["data"];
		} else {
			return json($check_res);
		}
		if (!$use_credit) {
			$invoice_data = ["credit" => 0, "total" => $invoice_data["subtotal"]];
			\think\Db::name("invoices")->where("id", $invoiceid)->update($invoice_data);
			return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => ["invoiceid" => $invoiceid]]);
		}
		$is_downstream = false;
		if ($this->request->is_api == 1) {
			$downstream_data = input("post.");
			$is_downstream = (strpos($downstream_data["downstream_url"], "https://") === 0 || strpos($downstream_data["downstream_url"], "http://") === 0) && strlen($downstream_data["downstream_token"]) == 32 && is_numeric($downstream_data["downstream_id"]);
		}
		$invoice_credit = $invoice_data["credit"];
		$user_credit = \think\Db::name("clients")->where("id", $uid)->value("credit");
		if ($user_credit <= 0) {
			return json(["status" => 400, "msg" => "Unable to use balance"]);
		}
		$invoic_subtotal = $invoice_data["subtotal"];
		if ($invoic_subtotal < $user_credit) {
			$user_credit = $invoic_subtotal;
		}
		if ($user_credit < $invoic_subtotal && $use_credit && $param["is_api"] == 1) {
			$result = ["status" => 400, "msg" => "Insufficient balance: the bill needs {$invoic_subtotal}, current balance {$user_credit}"];
			if ($is_downstream) {
				$result["msg"] .= ", After the upstream bill#" . $invoiceid . " is paid, it can be opened";
			}
			return json($result);
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
						throw new \Exception("Insufficient balance");
					}
					credit_log(["uid" => $uid, "desc" => "Credit Applied to Invoice #" . $invoiceid, "amount" => $user_credit, "relid" => $invoiceid]);
				}
				\think\Db::commit();
			} catch (\Exception $e) {
				\think\Db::rollback();
				return json(["status" => 400, "msg" => "Payment failed:" . $e->getMessage()]);
			}
			$invoice_logic = new \app\common\logic\Invoices();
			$invoice_logic->processPaidInvoice($invoiceid);
			$result["status"] = 1001;
			$result["msg"] = "Payment completed";
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
			return json($result);
		} else {
			\think\Db::name("invoices")->where("id", $invoiceid)->update(["total" => $paid_invoice_total]);
			return json(["status" => 200, "msg" => "Successful use of balance", "data" => ["invoiceid" => $invoiceid, "url" => $invoice_data["url"] ?: ""]]);
		}
	}
	public function fundDelete()
	{
		$uid = request()->uid;
		$param = $this->request->param();
		$invoiceid = intval($param["id"]);
		$check_res = $this->checkInvoice($uid, $invoiceid);
		if ($check_res["status"] == 200) {
			$invoice_data = $check_res["data"];
		} else {
			return json($check_res);
		}
		$invoice_data = ["credit" => 0, "total" => $invoice_data["subtotal"]];
		\think\Db::name("invoices")->where("id", $invoiceid)->update($invoice_data);
		$data = ["invoiceid" => $invoiceid];
		return json(["status" => 200, "msg" => "Delete balance successfully", "data" => $data]);
	}
	public function credit()
	{
		$uid = $this->request->uid;
		$param = $this->request->param();
		$invoiceid = intval($param["id"]);
		if (!configuration("shd_credit_limit")) {
			return json(["status" => 400, "msg" => "The system does not support credit payment"]);
		}
		$use_credit = isset($param["use_credit_limit"]) ? intval($param["use_credit_limit"]) : 1;
		$client = \think\Db::name("clients")->field("credit,credit_limit,is_open_credit_limit,currency")->where("id", $uid)->find();
		$client["is_open_credit_limit"] = configuration("credit_limit") == 1 ? $client["is_open_credit_limit"] : 0;
		if ($client["is_open_credit_limit"] == 0) {
			return json(["status" => 400, "msg" => "The system does not support credit payment"]);
		}
		$check_res = $this->checkInvoice($uid, $invoiceid);
		if ($check_res["status"] == 200) {
			$invoice_data = $check_res["data"];
			if ($invoice_data["credit"] > 0) {
				return json(["status" => 400, "msg" => "The current bill uses the balance and cannot be paid with credit"]);
			}
			if ($invoice_data["type"] == "credit_limit") {
				return json(["status" => 400, "msg" => "Credit bills cannot be paid with credit"]);
			}
		} else {
			return json($check_res);
		}
		$inv = \think\Db::name("invoices")->where("id", $invoiceid)->find();
		if ($inv["subtotal"] > $inv["total"]) {
			return json(["status" => 400, "msg" => "The balance of the bill has been used, and the credit can no longer be used to pay"]);
		}
		$credit_limit = \think\Db::name("clients")->where("id", $uid)->value("credit_limit");
		$amount_to_be_settled = \think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", $uid)->sum("total");
		$unpaid = \think\Db::name("invoices")->where("type", "credit_limit")->where("status", "Unpaid")->where("is_delete", 0)->where("uid", $uid)->sum("total");
		$credit_limit_used = number_format($amount_to_be_settled + $unpaid, 2, ".", "");
		$use_credit_limit = number_format($credit_limit - $credit_limit_used > 0 ? $credit_limit - $credit_limit_used : 0, 2, ".", "");
		if ($use_credit_limit < $invoice_data["total"]) {
			return json(["status" => 400, "msg" => "The current credit balance is insufficient, and the credit cannot be used to pay"]);
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
					throw new \Exception("Insufficient balance");
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
					throw new \Exception("Insufficient remaining credit");
				}
			}
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return json(["status" => 400, "msg" => "Payment failed:" . $e->getMessage()]);
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
		return json($result);
	}
	public function status()
	{
		$uid = request()->uid;
		$params = $this->request->param();
		$invoice_id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$invoice_id) {
			throw new \Exception("ID_ERROR", 400);
		}
		$invoice = \think\Db::name("invoices")->where("uid", $uid)->where("id", $invoice_id)->where("delete_time", 0)->find();
		$hids = \think\Db::name("invoice_items")->where("invoice_id", $invoice_id)->where("uid", $uid)->where("type", "host")->column("rel_id");
		if (!empty($invoice)) {
			if ($invoice["payment_status"] == "Paid") {
				return json(["status" => 1000, "msg" => "Payment successful", "data" => ["url" => $invoice["url"], "hid" => $hids]]);
			} else {
				return json(["status" => 1001, "msg" => "Payment failed"]);
			}
		}
		throw new \Exception("账单不存在", 400);
	}
}