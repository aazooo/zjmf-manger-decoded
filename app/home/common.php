<?php

function shook($name, $data = [])
{
	$gatewayInfo = getGatewayInfo($name);
	$gatewaysDIR = $gatewayInfo["gateways_dir"];
	$typename = $gatewayInfo["typename"];
	return \think\facade\Hook::exec([$gatewaysDIR, $typename], $data);
}
function invoice_user_hide($data)
{
	$res = db("invoice_user_hide")->where("uid", $data["uid"])->find();
	if ($res) {
		db("invoice_user_hide")->update($data);
	} else {
		db("invoice_user_hide")->insert($data);
	}
}
/**
 * 发起支付
 */
function start_pay($id, $payment)
{
	$uid = request()->uid ?? 3;
	$row = db("invoices")->get($id);
	$surplus = getSurplus($id);
	if ($surplus > 0) {
		$row["total"] = $surplus;
	} else {
		return ["status" => 400, "msg" => "订单已支付"];
	}
	if ($row["uid"] != $uid) {
		return ["status" => 400, "msg" => "无效参数"];
	}
	$type = $row["type"];
	$company_name = configuration("company_name");
	if ($type == "recharge") {
		$info["name"] = "服务费";
	} else {
		$info = db("invoice_items")->alias("i")->join("host h", "i.rel_id=h.id")->join("products p", "p.id=h.productid")->where("i.invoice_id", $row["id"])->where("i.rel_id", ">", 0)->field("p.name,i.rel_id")->find();
	}
	db("invoices")->where("id", $row["id"])->setInc("suffix");
	$currency = getUserCurrency($row["uid"])["code"];
	$payData = ["out_trade_no" => $row["id"], "product_name" => $company_name . $info["name"] ?: "客户系统", "total_fee" => $row["total"], "attach" => $row["type"] . "@" . $uid . "@" . $row["id"] . "@" . $row["total"] . "@" . $payment, "fee_type" => $currency];
	return shook($payment, $payData);
}
function start_certifi()
{
}