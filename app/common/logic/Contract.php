<?php

namespace app\common\logic;

class Contract
{
	public function getHostContract($hid)
	{
		$host = \think\Db::name("host")->field("productid,uid")->where("id", $hid)->whereIn("domainstatus", ["Active", "Suspended"])->find();
		$pid = $host["productid"];
		$uid = intval($host["uid"]);
		$tpls = \think\Db::name("contract")->where("status", 1)->select()->toArray();
		$tpl_filter = [];
		if (!empty($pid) && !empty($tpls)) {
			foreach ($tpls as $tpl) {
				$count = \think\Db::name("contract_pdf")->where("contract_id", $tpl["id"])->where("host_id", $hid)->whereIn("status", [1, 3, 4])->count();
				$count1 = \think\Db::name("contract_pdf")->where("contract_id", $tpl["id"])->where("uid", $uid)->whereIn("status", [1, 3, 4])->count();
				$pids = explode(",", $tpl["product_id"]);
				if (empty($count) && in_array($pid, $pids) || empty($count1) && $tpl["base"]) {
					$tpl_filter[] = $tpl;
				}
			}
		}
		if (!empty($tpl_filter[0])) {
			foreach ($tpl_filter as $v) {
				if ($v["force"]) {
					return $v;
				}
			}
			return $tpl_filter[0];
		}
		return [];
	}
	public function getUnsignedBaseContract($user_id)
	{
		$contracts = \think\Db::name("contract")->where("status", 1)->where("base", 1)->select()->toArray();
		$contracts_filter = [];
		foreach ($contracts as $contract) {
			$count = \think\Db::name("contract_pdf")->where("uid", $user_id)->where("contract_id", $contract["id"])->whereIn("status", [1, 3, 4])->count();
			if ($count == 0) {
				$contracts_filter[] = $contract;
			}
		}
		return $contracts_filter;
	}
	public function createContractNum()
	{
		$type = configuration("contract_number_custom");
		$len = intval(configuration("contract_number"));
		if ($len < 8) {
			$len = 15;
		}
		if ($len > 25) {
			$len = 25;
		}
		if ($type == 0) {
			$len = 15;
		}
		$pdf_num = randpw($len, $format = "NUMBER");
		if ($type == 1) {
			$pre = configuration("contract_number_prefix");
			$pdf_num = $pre . $pdf_num;
		}
		$count = \think\Db::name("contract_pdf")->where("pdf_num", $pdf_num)->count();
		if ($count >= 1) {
			$this->createContractNum();
		}
		return $pdf_num;
	}
	public function getTplId($hid)
	{
		$pid = \think\Db::name("host")->where("id", $hid)->value("productid");
		$contracts = \think\Db::name("contract")->where("status", 1)->select()->toArray();
		foreach ($contracts as $contract) {
			if (in_array($pid, explode(",", $contract["product_id"]))) {
				return intval($contract["id"]);
			}
		}
		return 0;
	}
	public function replaceArg($content, $hostid, $base = 0, $userid = 0)
	{
		if (empty($base)) {
			$host = \think\Db::name("host")->alias("h")->field("h.uid,p.name,h.domain,h.domainstatus,h.regdate,h.termination_date,h.nextduedate,h.billingcycle,h.firstpaymentamount,h.amount")->leftJoin("products p", "h.productid = p.id")->where("h.id", $hostid)->find();
			$uid = $host["uid"];
		} else {
			$uid = $userid;
		}
		$currency = getUserCurrency($uid);
		$client = \think\Db::name("clients")->field("username,companyname,phonenumber,email,address1")->where("id", $uid)->find();
		$search = ["{\$client_username}", "{\$client_company_name}", "{\$client_telephone}", "{\$client_email}", "{\$client_address}", "{\$client_product_name}", "{\$client_product_domain}", "{\$client_product_status}", "{\$client_product_startdate}", "{\$client_product_enddate}", "{\$client_product_billingcycle}", "{\$client_product_price}", "{\$client_product_pricewrite}", "{\$client_product_validtime}"];
		$replace = [$client["username"], $client["companyname"], $client["phonenumber"], $client["email"], $client["address1"], $host["name"], $host["domain"], config("domainstatus")[$host["domainstatus"]], date("Y-m-d H:i:s", $host["regdate"]), date("Y-m-d H:i:s", $host["termination_date"]), config("billing_cycle")[$host["billingcycle"]], $host["firstpaymentamount"], $currency["prefix"] . $host["firstpaymentamount"], $this->getMonthNum($host["nextduedate"], $host["regdate"]) . "个月"];
		$result = str_replace($search, $replace, $content);
		return $result;
	}
	public function getMonthNum($date1, $date2)
	{
		$date1_stamp = is_numeric($date1) ? $date1 : strtotime($date1);
		$date2_stamp = is_numeric($date2) ? $date2 : strtotime($date2);
		list($date_1["y"], $date_1["m"]) = explode("-", date("Y-m", $date1_stamp));
		list($date_2["y"], $date_2["m"]) = explode("-", date("Y-m", $date2_stamp));
		return abs(($date_2["y"] - $date_1["y"]) * 12 + $date_2["m"] - $date_1["m"]);
	}
	public function getContractProducts()
	{
		$contracts = \think\Db::name("contract")->where("status", 1)->column("product_id");
		$str = "";
		foreach ($contracts as $contract) {
			if (empty($str)) {
				$str = $contract;
			} else {
				$str .= "," . $contract;
			}
		}
		return explode(",", $str) ?: [];
	}
	public function createContract($tplid = null, $hid = null, $uid = null)
	{
		$uid = !is_null($uid) ? $uid : request()->uid;
		$pdf_num = $this->createContractNum();
		if (is_null($hid) && is_null($tplid)) {
			$hid = 0;
			$contract_id = 27;
		} else {
			if (empty($hid)) {
				$hid = 0;
				$contract_id = $tplid;
			} else {
				$contract_id = $this->getTplId($hid);
			}
		}
		if (empty($contract_id)) {
			return false;
		}
		$res = [];
		$res["pdf_num"] = $pdf_num;
		$res["contract_id"] = $contract_id;
		$res["uid"] = $uid;
		$res["host_id"] = $hid;
		$res["status"] = 2;
		$res["pdf_address"] = "";
		$res["information"] = $_SERVER["HTTP_USER_AGENT"];
		$res["ip"] = get_client_ip();
		$res["create_time"] = time();
		$res["remark"] = "";
		$id = \think\Db::name("contract_pdf")->insertGetId($res);
		return $id;
	}
}