<?php

namespace app\home\controller;

class TestController extends \think\Controller
{
	public function cartPage()
	{
		$userid = $this->request->uid;
		$shop = new \app\common\logic\Shop($userid);
		$pid = 10;
		$billingcycle = "monthly";
		$serverid = 1;
		$configoption = [226 => 354, 227 => 357];
		$customfield = [33 => "good field", 27 => "3jfdsliujfl"];
		$res = $shop->addProduct($pid, $billingcycle, $serverid, $configoption, $customfield);
		var_dump($res);
	}
	public function processPaidInvoice($invoiceid, $noemail = "", $date = "")
	{
		$invoice_data = Db::name("invoices")->where("id", $invoiceid)->find();
		$invoiceid = \intval($invoice_data["id"]);
		$uid = $invoice_data["uid"];
		$invoice_status = $invoice_data["status"];
		$invoice_num = $invoice_data["invoice_num"];
		if (!in_array($invoice_status, ["Unpaid", "Pending"]) || !empty($invoice_data["delete_time"])) {
			return false;
		}
		$time = time();
		$udata = ["status" => "Paid", "paid_time" => $time];
		if (!$invoice_num) {
			$invoice_num = date("Ymd") . $uid . rand(1000);
			$udata["invoice_num"] = $invoice_num;
		}
		Db::name("invoices")->where("id", $invoiceid)->update_time($udata);
		hook("InvoicePaidPreEmail", ["invoice_id" => $invoiceid]);
		if (!$noemail) {
			$email_logic = new \app\common\logic\Email();
			$email_logic->sendEmail("Invoice Payment Confirmation", $invoiceid);
		}
		$invoice_item_data = Db::name("invoice_items")->where("invoice_id", $invoiceid)->find();
		foreach ($invoice_item_data as $key => $val) {
			$item_id = $val["id"];
			$uid = $data["uid"];
			$type = $data["type"];
			$relid = $data["rel_id"];
			$amount = $data["amount"];
			if ($type == "host") {
				$info = Db::name("host")->field("h.id,h.billingcycle,h.domainstatus,h.nextduedate,p.pay_type,p.auto_setup")->alias("h")->leftJoin("products p", "p.id=h.productid")->where("h.id", $relid)->find();
				$auto_setup = $info["auto_setup"];
				$pay_type = json_decode($info["pay_type"], true);
				$nextduedate = getNextTime($info["billingcycle"], $pay_type["pay_" . $info["billingcycle"] . "_cycle"], $info["nextduedate"]);
				Db::name("host")->where("id", $relid)->update(["regdate" => time(), "nextduedate" => $nextduedate, "nextinvoicedate" => $nextduedate]);
				if ($auto_setup == "payment") {
					$Host = new \app\common\logic\Host();
					$result = $Host->create($relid);
					$logic_run_map = new \app\common\logic\RunMap();
					$model_host = new \app\common\model\HostModel();
					$data_i = [];
					$data_i["host_id"] = $relid;
					$data_i["active_type_param"] = [$relid, ""];
					$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
					if ($result["status"] == 200) {
						$data_i["description"] = " 支付成功后 - 开通 Host ID:{$data_i["host_id"]}的产品成功";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 400, 1, 2);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 100, 1, 2);
						}
					} else {
						$data_i["description"] = " 支付成功后 - 开通 Host ID:{$data_i["host_id"]}的产品失败。原因:{$result["msg"]}";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 400, 1, 2);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 100, 1, 2);
						}
					}
				}
			} elseif ($type == "cycle_to_mon_year") {
				$renew_cycle_data = Db::name("renew_cycle")->where("type", "cycle_to_mon_year")->where("status", "Pending")->where("relid", $item_id)->find();
				if ($renew_cycle_data) {
					$new_cycle = $renew_cycle_data["new_cycle"];
					$new_recurring_amount = $renew_cycle_data["new_recurring_amount"];
					$host_data = Db::name("host")->field("billingcycle, amount")->where("id", $relid)->find();
					Db::name("host")->where("id", $relid)->update(["billingcycle" => $new_cycle, "amount" => $amount]);
					Db::name("renew_cycle")->where("id", $renew_cycle_data["id"])->update(["status" => "Completed", "paid" => "Y"]);
					$description = "产品周期从" . config("billing_cycle")[$host_data["billingcycle"]] . "更改到" . config("billing_cycle")[$new_cycle] . ",host ID:" . $host_data["id"];
					active_log($description, $uid);
				}
			} elseif ($type == "renew") {
				$info = Db::name("host")->field("h.id,h.billingcycle,h.domainstatus,h.nextduedate,p.pay_type")->alias("h")->leftJoin("products p", "p.id=h.productid")->where("h.id", $relid)->find();
				$pay_type = json_decode($info["pay_type"], true);
				$nextduedate = getNextTime($info["billingcycle"], $pay_type["pay_" . $info["billingcycle"] . "_cycle"], $info["nextduedate"]);
				Db::name("host")->where("id", $info["hostid"])->update(["nextduedate" => $nextduedate, "nextinvoicedate" => $nextduedate]);
				$description = "产品续费成功 - Service ID:" . $info["id"] . "，到期时间修改为：" . date("Y-m-d H:i:s", $nextduedate);
				if ($info["domainstatus"] == "Suspended" && !!getConfig("cron_host_unsuspend")) {
					$host = new \app\common\logic\Host();
					$result = $host->unsuspend($info["hostid"], getConfig("cron_host_unsuspend_send"));
					$logic_run_map = new \app\common\logic\RunMap();
					$model_host = new \app\common\model\HostModel();
					$data_i = [];
					$data_i["host_id"] = $info["hostid"];
					$data_i["active_type_param"] = [$info["hostid"], getConfig("cron_host_unsuspend_send"), "", 0];
					$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
					if ($result["status"] == 200) {
						$data_i["description"] = " 成功续费产品 - 解除暂停 Host ID:{$data_i["host_id"]}的产品成功";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 400, 3);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 300, 3);
						}
					} else {
						$data_i["description"] = " 成功续费产品 - 解除暂停 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 400, 3);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 300, 3);
						}
					}
				}
				active_log($description, $uid);
			} elseif ($type == "recharge") {
				Db::name("clients")->where("id", $uid)->setInc("credit", $amount);
				credit_log(["uid" => $uid, "desc" => "Add Funds Invoice #" . $invoiceid, "amount" => $amount, "relid" => $invoiceid]);
				$description = "充值成功 - invoice ID:" . $invoiceid . " - 金额：" . $amount;
				active_log($description, $uid);
			}
		}
	}
	public function demo_ts()
	{
		$bot = new \app\common\logic\Bot();
		$a = $bot->notice_check_used(1000111400, 10857, "notice_i_expired");
		halt($a);
	}
}