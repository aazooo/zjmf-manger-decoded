<?php

namespace app\home\controller;

class OrderController extends CommonController
{
	public function orders()
	{
		$re = [];
		$re["status"] = 400;
		$re["msg"] = "请求错误";
		$clientId = $this->request->uid;
		if ($this->request->isPost()) {
			$orders = \think\Db::name("orders")->field("status,pay_time,create_time,balance,payment")->where("uid", $clientId)->order("create_time DESC")->select();
			$re["status"] = 200;
			$re["msg"] = "请求成功";
			$re["orders"] = $orders;
			return json($re);
		}
		return json($re);
	}
	/**
	 * 订单支付处理
	 * @param array $data
	 * @param string $payType
	 * @return bool
	 * @throws
	 */
	public function orderPayHandle($data = [])
	{
		$invoice_id = $data["invoice_id"] ?? "";
		$total_amount = $data["amount_in"] ?? 0;
		$trans_id = $data["trans_id"] ?? "";
		$currency = $data["currency"] ?? "CNY";
		$pay_time = strtotime($data["paid_time"]) ?? "";
		$payment = $data["payment"] ?? "AliPay";
		$IncoiceInfo = \think\Db::name("invoices")->where("id", $invoice_id)->where("delete_time", 0)->find();
		request()->uid = $IncoiceInfo["uid"];
		if (empty($IncoiceInfo)) {
			\think\facade\Log::info("错误的支付invoice_id is empty:" . json_encode($IncoiceInfo));
			return false;
		}
		if (strpos($IncoiceInfo["payment"], $payment) !== false) {
			$payment = $IncoiceInfo["payment"];
		}
		$client_currency = \think\Db::name("clients")->where("id", $IncoiceInfo["uid"])->value("currency");
		$client_currency_code = \think\Db::name("currencies")->where("id", $client_currency)->value("code");
		if ($currency != $client_currency_code) {
			$total_amount = $IncoiceInfo["total"];
			$currency = $client_currency_code;
		}
		if ($IncoiceInfo["total"] < $total_amount) {
			$credit = $total_amount - $IncoiceInfo["total"];
			if ($credit > 0) {
				\think\Db::name("clients")->where("id", $IncoiceInfo["uid"])->setInc("credit", $credit);
				credit_log(["uid" => $IncoiceInfo["uid"], "desc" => "Invoice #{$invoice_id} Overpayment", "amount" => $credit, "relid" => $invoice_id]);
			}
		}
		if ($IncoiceInfo["payment_status"] == "Paid") {
			\think\facade\Log::notice("重复支付的订单invoiceID:" . $data["out_trade_no"]);
			return true;
		}
		$account_id = check_account_id($trans_id);
		if (!empty($account_id)) {
			\think\facade\Log::info("transaction_id已存在:" . $trans_id);
			return true;
		}
		$time = time();
		$accountsData = ["uid" => $IncoiceInfo["uid"], "currency" => $currency, "gateway" => $payment, "create_time" => $time, "pay_time" => $pay_time, "amount_in" => $total_amount, "fees" => "", "amount_out" => 0, "rate" => 1, "trans_id" => $trans_id, "invoice_id" => $invoice_id, "refund" => 0, "description" => invoiceTypeToDescription($IncoiceInfo["type"], $invoice_id)];
		$upData = ["payment_status" => "Paid", "payment" => $payment];
		$tans = $data["trans_id"] ? $data["trans_id"] : $trans_id;
		$flag = false;
		$aid = \think\Db::name("accounts")->insert($accountsData);
		if ($aid) {
			\think\Db::startTrans();
			try {
				$res2 = \think\Db::name("invoices")->where("id", $IncoiceInfo["id"])->where("delete_time", 0)->update($upData);
				\think\Db::name("invoice_items")->where("invoice_id", $IncoiceInfo["id"])->where("delete_time", 0)->update(["payment" => $payment]);
				$client_credit = \think\Db::name("clients")->where("id", $IncoiceInfo["uid"])->value("credit");
				$invoice_credit = $IncoiceInfo["subtotal"] - $IncoiceInfo["total"];
				$client_credit = round($client_credit, 3);
				$invoice_credit = round($invoice_credit, 3);
				if ($invoice_credit > 0) {
					if ($invoice_credit <= $client_credit + 0.01) {
						$up_data["status"] = "Paid";
						$up_data["paid_time"] = $pay_time;
						$up_data["credit"] = $invoice_credit;
						\think\Db::name("invoices")->where("id", $IncoiceInfo["id"])->update($up_data);
						if ($invoice_credit <= $client_credit) {
							\think\Db::name("clients")->where("id", $IncoiceInfo["uid"])->setDec("credit", $invoice_credit);
						} else {
							\think\Db::name("clients")->where("id", $IncoiceInfo["uid"])->setDec("credit", $client_credit);
						}
						credit_log(["uid" => $IncoiceInfo["uid"], "desc" => "Credit Applied to Invoice #" . $IncoiceInfo["id"], "amount" => $invoice_credit, "relid" => $IncoiceInfo["id"]]);
					} else {
						active_logs(sprintf("部分余额支付失败,失败原因：余额不足(可能将余额使用至另一订单) - 账单号#Invoice ID:%d - 交易单号#Transaction ID:%s", $IncoiceInfo["id"], $tans), $IncoiceInfo["uid"]);
						active_logs(sprintf("部分余额支付失败,失败原因：余额不足(可能将余额使用至另一订单) - 账单号#Invoice ID:%d - 交易单号#Transaction ID:%s", $IncoiceInfo["id"], $tans), $IncoiceInfo["uid"], "", 2);
					}
				} else {
					if ($IncoiceInfo["total"] <= $total_amount) {
						$up_data["paid_time"] = $pay_time;
						$up_data["status"] = "Paid";
						\think\Db::name("invoices")->where("id", $IncoiceInfo["id"])->update($up_data);
					}
				}
				if ($res2) {
					$flag = true;
					\think\Db::commit();
				} else {
					exception("数据添加或修改失败", 10006);
				}
			} catch (\Exception $e) {
				\think\facade\Log::info("错误的存储，保存用户流水表失败:" . json_encode($e->getMessage()));
				\think\Db::name("accounts")->where("id", $aid)->delete();
				\think\Db::rollback();
			}
		}
		if ($flag) {
			$invoice_logic = new \app\common\logic\Invoices();
			$invoice_logic->processPaidInvoice($IncoiceInfo["id"]);
			if ($IncoiceInfo["type"] == "product") {
				active_logs(sprintf("产品支付成功 - 账单号#Invoice ID:%d - 交易单号#Transaction ID:%s", $IncoiceInfo["id"], $tans), $IncoiceInfo["uid"]);
				active_logs(sprintf("产品支付成功 - 账单号#Invoice ID:%d - 交易单号#Transaction ID:%s", $IncoiceInfo["id"], $tans), $IncoiceInfo["uid"], "", 2);
			} elseif ($IncoiceInfo["type"] == "renew") {
				active_logs("续费支付成功 - 账单号#Invoice ID:" . $IncoiceInfo["id"] . " - 交易单号#Transaction ID:" . $tans, $IncoiceInfo["uid"]);
				active_logs("续费支付成功 - 账单号#Invoice ID:" . $IncoiceInfo["id"] . " - 交易单号#Transaction ID:" . $tans, $IncoiceInfo["uid"], "", 2);
			} elseif ($IncoiceInfo["type"] == "recharge") {
				active_logs("充值支付成功 - 账单号#Invoice ID:" . $IncoiceInfo["id"] . " - 交易单号#Transaction ID:" . $tans, $IncoiceInfo["uid"]);
				active_logs("充值支付成功 - 账单号#Invoice ID:" . $IncoiceInfo["id"] . " - 交易单号#Transaction ID:" . $tans, $IncoiceInfo["uid"], "", 2);
			} elseif ($IncoiceInfo["type"] == "credit_limit") {
				active_logs("信用额账单支付成功 - 账单号#Invoice ID:" . $IncoiceInfo["id"] . " - 交易单号#Transaction ID:" . $tans, $IncoiceInfo["uid"]);
				active_logs("信用额账单支付成功 - 账单号#Invoice ID:" . $IncoiceInfo["id"] . " - 交易单号#Transaction ID:" . $tans, $IncoiceInfo["uid"], "", 2);
			}
		}
		return $flag;
	}
	public function checkOrder()
	{
		$uid = request()->uid;
		$params = $this->request->param();
		$invoice_id = isset($params["id"]) ? intval($params["id"]) : "";
		if (!$invoice_id) {
			throw new \Exception("ID_ERROR", 400);
		}
		$invoice = \think\Db::name("invoices")->where("uid", $uid)->where("id", $invoice_id)->where("delete_time", 0)->find();
		if (!empty($invoice)) {
			if ($invoice["payment_status"] == "Paid") {
				return json(["status" => 1000, "msg" => lang("支付成功"), "data" => $invoice["url"]]);
			} else {
				return json(["status" => 1001, "msg" => lang("支付失败")]);
			}
		}
		throw new \Exception("账单不存在", 400);
	}
}