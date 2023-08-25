<?php

namespace app\common\logic;

class Invoices
{
	public $is_admin = false;
	/**
	 * 作者: huanghao
	 * 时间: 2019-12-12
	 * 是否存在未支付的续费账单
	 * @param  int  $hostid hostid
	 * @return boolean         
	 */
	public function hasRenew($hostid)
	{
		if (empty($hostid)) {
			return false;
		}
		$has = \think\Db::name("invoice_items")->alias("a")->field("b.id")->leftJoin("invoices b", "a.invoice_id=b.id")->where("a.type", "renew")->where("a.rel_id", $hostid)->where("b.status", "Unpaid")->where("b.delete_time", 0)->where("a.delete_time", 0)->find();
		return !empty($has);
	}
	/**
	 * 作者: huanghao
	 * 时间: 2019-12-12
	 * 生成续费账单
	 * @param  int    $hostid hostid
	 * @param  int    $pay    是否直接余额支付
	 * @param  int    $is_cron    是否自动任务生成的续费账单:如果是自动任务生成，且未支付，那么就不会删除已存在的续费账单重新生成
	 * @return array
	 */
	public function createRenew($hostid, $pay = 0, $is_cron = false)
	{
		$result = [];
		if (empty($hostid)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$host_data = \think\Db::name("host")->field("i.status,h.domain,h.dedicatedip,h.nextduedate,p.name as productname,i.id as invoice_id")->alias("h")->leftJoin("orders o", "h.orderid = o.id")->leftJoin("invoices i", "o.invoiceid = i.id")->leftJoin("products p", "h.productid = p.id")->where("h.id", $hostid)->where("i.type", "product")->where("o.delete_time", 0)->where("i.delete_time", 0)->find();
		if (!empty($host_data) && $host_data["status"] == "Unpaid") {
			$result["status"] = 400;
			$result["msg"] = lang("产品未支付，生成续费账单失败");
			return $result;
		} else {
			$host_data = \think\Db::name("host")->field("h.domain,h.nextduedate,h.dedicatedip,p.name as productname")->alias("h")->leftJoin("products p", "p.id=h.productid")->where("h.id", $hostid)->find();
		}
		$cron_renew_invoices = \think\Db::name("host")->field("i.status,h.domain,h.dedicatedip,h.nextduedate,p.name as productname,i.id as invoice_id")->alias("h")->leftJoin("products p", "h.productid = p.id")->leftJoin("invoice_items ii", "ii.rel_id = h.id")->leftJoin("invoices i", "ii.invoice_id = i.id")->where("h.id", $hostid)->where("i.status", "Unpaid")->where("i.type", "renew")->where("i.delete_time", 0)->where("i.is_cron", 1)->select()->toArray();
		if (!empty($cron_renew_invoices[0])) {
			return ["status" => 500, "msg" => "生成续费账单成功"];
		}
		$renew_invoices = \think\Db::name("host")->field("i.status,h.domain,h.dedicatedip,h.nextduedate,p.name as productname,i.id as invoice_id")->alias("h")->leftJoin("products p", "h.productid = p.id")->leftJoin("invoice_items ii", "ii.rel_id = h.id")->leftJoin("invoices i", "ii.invoice_id = i.id")->where("h.id", $hostid)->where("i.status", "Unpaid")->where("i.type", "renew")->where("i.delete_time", 0)->where(function (\think\db\Query $query) use($is_cron) {
			if ($is_cron) {
				$query->where("i.is_cron", "<>", 1);
			}
		})->select()->toArray();
		foreach ($renew_invoices as $v) {
			\think\Db::name("invoices")->where("id", $v["invoice_id"])->delete();
			\think\Db::name("invoice_items")->where("invoice_id", $v["invoice_id"])->delete();
		}
		$host = \think\Db::name("host")->where("id", $hostid)->find();
		$productid = $host["productid"];
		$billingcycle = $host["billingcycle"];
		if ($billingcycle == "free" || $billingcycle == "onetime" || $billingcycle == "ontrial") {
			$result["status"] = 400;
			$result["msg"] = lang("SERVICE_IS_FREE");
			return $result;
		}
		$pay_type = \think\Db::name("products")->where("id", $productid)->where("hidden", 0)->value("pay_type");
		$pay_type = json_decode($pay_type, true);
		$next_time = $host["nextduedate"];
		$invoices_data = ["uid" => $host["uid"], "create_time" => time(), "due_time" => $next_time, "paid_time" => 0, "subtotal" => $host["amount"], "credit" => 0, "total" => $host["amount"], "status" => "Unpaid", "payment" => $host["payment"], "type" => "renew", "is_cron" => $is_cron ? 1 : 0];
		$renew_items = ["uid" => $host["uid"], "type" => "normal", "new_cycle" => $billingcycle, "new_recurring_amount" => $host["amount"], "status" => "Pending", "paid" => "N", "create_time" => time(), "expire_time" => $next_time];
		$billing_cycle = config("coupon_cycle");
		\think\Db::startTrans();
		try {
			$start_time = date("Y/m/d", $host_data["nextduedate"]);
			$end_time = date("Y/m/d", getNextTime($billingcycle, $pay_type["pay_" . $billingcycle . "_cycle"], $host_data["nextduedate"], $pay_type["pay_ontrial_cycle_type"] ?: "day"));
			$dec = "续费账单 - " . $host_data["productname"] . "(" . $host_data["domain"] . "),IP({$host_data["dedicatedip"]}),购买时长：" . $billing_cycle[$billingcycle] . "(" . $start_time . "-" . $end_time . ")";
			$r1 = \think\Db::name("invoices")->insertGetId($invoices_data);
			$r2 = \think\Db::name("invoice_items")->insertGetId(["invoice_id" => $r1, "uid" => $host["uid"], "type" => "renew", "rel_id" => $hostid, "description" => $dec, "amount" => $host["amount"]]);
			$renew_items["relid"] = $r2;
			$r3 = \think\Db::name("renew_cycle")->insert($renew_items);
			\think\Db::commit();
			$result["invoice_id"] = $r1;
			$result["status"] = 200;
			$result["msg"] = lang("SUCCESS MESSAGE");
		} catch (\Exception $e) {
			\think\Db::rollback();
			$result["status"] = 400;
			$result["msg"] = "续费账单生成失败,回滚";
		}
		$description = "生成续费账单- Invoice ID:{$r1} 金额:" . $host["amount"];
		if ($result["status"] == 200) {
			$description .= "成功";
			hook("renew_invoice_create", ["invoiceid" => $r1, "hostid" => $hostid]);
			$email = new Email();
			$email->sendEmailBase($hostid, "产品到期续费提示(第一次)", "invoice", true);
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			$sms = new Sms();
			$client = check_type_is_use($message_template_type[strtolower("renew_product_reminder")], $host["uid"], $sms);
			if ($client) {
				$product = \think\Db::name("products")->field("name")->where("id", $host["productid"])->find();
				$params = ["product_name" => $product["name"], "hostname" => $host["domain"], "product_end_time" => date("Y-m-d H:i:s", $host["nextduedate"]), "product_mainip" => $host["dedicatedip"]];
				$sms->sendSms($message_template_type[strtolower("renew_product_reminder")], $client["phone_code"] . $client["phonenumber"], $params, false, $host["uid"]);
			}
			if (!empty($pay)) {
				$result1 = $this->useCreditPay($r1);
				$description .= " -产品Host ID:{$hostid} 自动续费已开启";
				if ($result1["status"] == 200) {
					$result["invoice_id"] = $r1;
					$result["status"] = 200;
					$result["msg"] = $result1["msg"];
					$description .= " -账单Invoice ID:{$r1} 支付成功";
				} else {
					$result["status"] = 400;
					$result["msg"] = $result1["msg"];
					$description .= " -账单Invoice ID:{$r1} 支付失败:" . $result["msg"];
				}
			}
		} else {
			$description .= "失败:" . $result["msg"];
		}
		active_log_final($description, $host["uid"]);
		return $result;
	}
	/**
	 * 作者: xujin
	 * 时间: 2021-01-12
	 * 生成信用额账单
	 * @param  int    $hostid hostid
	 * @param  int    $pay    是否直接余额支付
	 * @param  int    $is_cron    是否自动任务生成的续费账单:如果是自动任务生成，且未支付，那么就不会删除已存在的续费账单重新生成
	 * @return array
	 */
	public function createCreditLimit($uid, $pay = 0, $is_cron = false)
	{
		$result = [];
		if (empty($uid)) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$time = time();
		$invoices = \think\Db::name("invoices")->where("delete_time", 0)->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("uid", $uid)->select()->toArray();
		if (count($invoices) == 0) {
			return ["status" => 400, "msg" => "生成信用额账单失败"];
		}
		$clients = \think\Db::name("clients")->where("id", $uid)->find();
		$amount = 0;
		foreach ($invoices as $key => $value) {
			$amount += $value["total"];
		}
		$pay_type = \think\Db::name("products")->where("id", $productid)->where("hidden", 0)->value("pay_type");
		$pay_type = json_decode($pay_type, true);
		$next_time = $time + $clients["bill_repayment_period"] * 24 * 3600;
		$invoices_data = ["uid" => $uid, "create_time" => $time, "due_time" => $next_time, "paid_time" => 0, "subtotal" => $amount, "credit" => 0, "total" => $amount, "status" => "Unpaid", "payment" => $clients["defaultgateway"], "type" => "credit_limit", "is_cron" => 1];
		$billing_cycle = config("coupon_cycle");
		\think\Db::startTrans();
		try {
			$dec = "信用额账单";
			$r1 = \think\Db::name("invoices")->insertGetId($invoices_data);
			$r2 = \think\Db::name("invoice_items")->insertGetId(["invoice_id" => $r1, "uid" => $uid, "type" => "credit_limit", "rel_id" => 0, "description" => $dec, "amount" => $amount]);
			\think\Db::name("invoices")->where("delete_time", 0)->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("uid", $uid)->update(["invoice_id" => $r1]);
			\think\Db::commit();
			$result["invoice_id"] = $r1;
			$result["status"] = 200;
			$result["msg"] = lang("SUCCESS MESSAGE");
		} catch (\Exception $e) {
			\think\Db::rollback();
			$result["status"] = 400;
			$result["msg"] = "信用额账单生成失败,回滚";
		}
		if ($result["status"] == 200) {
			active_log_final("信用额账单生成成功", $uid);
			$email = new Email();
			$email->sendEmailBase($r1, "信用额账单已生成", "invoice", true);
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			$tmp = \think\Db::name("invoices")->field("id,total")->where("id", $r1)->find();
			$sms = new Sms();
			$client = check_type_is_use($message_template_type[strtolower("credit_limit_invoice_notice")], $uid, $sms);
			$currencies = \think\Db::name("currencies")->field("suffix")->where("id", $client["currency"])->find();
			if ($client) {
				$params = ["invoiceid" => $tmp["id"], "total" => $tmp["total"] . $currencies["suffix"]];
				$sms->sendSms($message_template_type[strtolower("credit_limit_invoice_notice")], $client["phone_code"] . $client["phonenumber"], $params, false, $uid);
			}
			if (!empty($pay)) {
				$result1 = $this->useCreditPay($r1);
				if ($result1["status"] == 200) {
					$result["invoice_id"] = $r1;
					$result["status"] = 200;
					$result["msg"] = $result1["msg"];
				} else {
					$result["status"] = 400;
					$result["msg"] = $result1["msg"];
				}
			}
		}
		return $result;
	}
	/**
	 * 作者: huanghao
	 * 时间: 2019-12-12
	 * 使用余额支付续费账单
	 * @param  int 	  $invoice_id  账单id
	 * @return [type] [description]
	 */
	public function useCreditPay($invoice_id)
	{
		$info = \think\Db::name("invoices")->alias("a")->field("a.id,a.uid,a.subtotal,a.total,b.username,b.email,b.credit,d.billingcycle,d.nextduedate,d.domainstatus,d.id hostid,e.pay_type,e.server_type")->field("d.dcimid,e.zjmf_api_id")->leftJoin("clients b", "a.uid=b.id")->leftJoin("invoice_items c", "a.id=c.invoice_id")->leftJoin("host d", "c.rel_id=d.id")->leftJoin("products e", "d.productid=e.id")->where("a.id", $invoice_id)->where("a.status", "Unpaid")->where("c.type", "renew")->where("a.type", "renew")->where("a.delete_time", 0)->find();
		if (empty($info["hostid"]) || $info["billingcycle"] == "free" || $info["nextduedate"] == 0) {
			$result["status"] = 400;
			$result["msg"] = lang("ID_ERROR");
			return $result;
		}
		$pay_type = json_decode($info["pay_type"], true);
		$nextduedate = getNextTime($info["billingcycle"], $pay_type["pay_" . $info["billingcycle"] . "_cycle"], $info["nextduedate"], $pay_type["pay_ontrial_cycle_type"] ?: "day");
		if ($info["subtotal"] == 0) {
			\think\Db::name("invoices")->where("id", $invoice_id)->where("delete_time", 0)->update(["paid_time" => time(), "status" => "Paid", "update_time" => time(), "is_delete" => 0]);
			\think\Db::name("host")->where("id", $info["hostid"])->update(["nextduedate" => $nextduedate, "nextinvoicedate" => $nextduedate]);
			$result["status"] = 200;
			$result["msg"] = lang("PAY_USE_CREDIT_SUCCESS");
		} else {
			if ($info["credit"] >= $info["subtotal"] - $info["total"]) {
				\think\Db::startTrans();
				try {
					$r1 = \think\Db::name("clients")->where("id", $info["uid"])->where("credit", ">=", $info["total"])->setDec("credit", $info["total"]);
					$r2 = \think\Db::name("invoices")->where("id", $invoice_id)->where("delete_time", 0)->update(["paid_time" => time(), "status" => "Paid", "update_time" => time(), "credit" => $info["subtotal"] - $info["total"], "is_delete" => 0]);
					\think\Db::name("host")->where("id", $info["hostid"])->update(["nextduedate" => $nextduedate, "nextinvoicedate" => $nextduedate]);
					if ($r1 && $r2) {
						\think\Db::commit();
						$result["status"] = 200;
						$result["msg"] = lang("PAY_USE_CREDIT_SUCCESS");
					} else {
						\think\Db::rollback();
						$result["status"] = 400;
						$result["msg"] = lang("PAY_USE_CREDIT_ERROR");
						if ($info["credit"] < $info["total"]) {
							$result["msg"] = lang("INSUFFICIENT_PAYMENT_BALANCE") . ",当前余额￥" . $info["credit"];
						}
					}
				} catch (\Exception $e) {
					\think\Db::rollback();
					$result["status"] = 400;
					$result["msg"] = lang("PAY_USE_CREDIT_ERROR");
				}
			} else {
				$result["status"] = 400;
				$result["msg"] = lang("INSUFFICIENT_PAYMENT_BALANCE");
			}
		}
		if ($result["status"] == 200) {
			\think\Db::name("invoices")->where("id", $invoice_id)->update(["credit" => $info["total"]]);
			\credit_log(["uid" => $info["uid"], "desc" => "Credit Applied to Renew Invoice #" . $invoice_id, "amount" => $info["total"]]);
			hook("invoice_paid", ["invoiceid" => $invoice_id]);
			$model_host = new \app\common\model\HostModel();
			$is_zjmf = $model_host->isZjmfApi($info["hostid"]);
			if ($is_zjmf) {
				$post_data["hostid"] = $info["dcimid"];
				$post_data["billingcycles"] = $info["billingcycle"];
				$module_res = zjmfCurl($info["zjmf_api_id"], "/host/renew", $post_data);
				if ($module_res["status"] == 200) {
					$post_data = [];
					$post_data["invoiceid"] = $module_res["data"]["invoiceid"];
					$post_data["use_credit"] = 1;
					zjmfCurl($info["zjmf_api_id"], "/apply_credit", $post_data);
				}
			}
			if ($info["domainstatus"] == "Suspended" && !!getConfig("cron_host_unsuspend")) {
				$host = new Host();
				$host->is_admin = $this->is_admin;
				$result1 = $host->unsuspend($info["hostid"], getConfig("cron_host_unsuspend_send"));
				$logic_run_map = new RunMap();
				$data_i = [];
				$data_i["host_id"] = $info["hostid"];
				$data_i["active_type_param"] = [$info["hostid"], getConfig("cron_host_unsuspend_send"), "", 0];
				$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
				if ($result["status"] == 200) {
					$data_i["description"] = " 余额支付续费账单 - 解除暂停 Host ID:{$data_i["host_id"]}的产品成功";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 400, 3);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 1, 300, 3);
					}
				} else {
					$data_i["description"] = " 余额支付续费账单 - 解除暂停 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
					if ($is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 400, 3);
					}
					if (!$is_zjmf) {
						$logic_run_map->saveMap($data_i, 0, 300, 3);
					}
				}
				if ($result1["status"] == 200) {
					$result["msg"] = $result1["msg"];
					return $result;
				} else {
					$result["status"] = 400;
					$result["msg"] = $result1["msg"];
				}
			}
		}
		return $result;
	}
	/**
	 * 作者: wyh
	 * 时间: 2020-05-30
	 * 账单支付成功后处理动作
	 * @param  int 	  $invoice_id  账单id
	 * @param  int 	  $email  发送账单支付邮件，默认发送
	 * @return 
	 */
	public function processPaidInvoice($invoiceid, $email = true)
	{
		session_write_close();
		if (configuration("shd_process_paid_invoice")) {
			\app\queue\job\InvoicePaid::push(["invoiceid" => $invoiceid, "email" => $email]);
			return true;
		}
		return $this->processPaidInvoiceFinal($invoiceid, $email);
	}
	public function processPaidInvoiceFinal($invoiceid, $email = true)
	{
		dcim_callback($invoiceid);
		$invoice_data = \think\Db::name("invoices")->where("id", $invoiceid)->find();
		$invoiceid = \intval($invoice_data["id"]);
		$uid = $invoice_data["uid"];
		$invoice_status = $invoice_data["status"];
		$invoice_num = $invoice_data["invoice_num"];
		if ($invoice_status != "Paid") {
			return false;
		}
		$udata = [];
		if (!$invoice_num) {
			$invoice_num = date("Ymd") . $uid . mt_rand(100000, 999999);
			$udata["invoice_num"] = $invoice_num;
		}
		$udata["is_delete"] = 0;
		\think\Db::name("invoices")->where("id", $invoiceid)->update($udata);
		hook("invoice_paid_before_email", ["invoiceid" => $invoiceid]);
		$invoice_item_data = \think\Db::name("invoice_items")->where("invoice_id", $invoiceid)->where("delete_time", 0)->select()->toArray();
		$curl_multi_data = [];
		foreach ($invoice_item_data as $key => $val) {
			$item_id = $val["id"];
			$uid = $val["uid"];
			$type = $val["type"];
			$relid = $val["rel_id"];
			$amount = $val["amount"];
			if ($type == "host") {
				$host = \think\Db::name("host")->alias("a")->field("a.id,a.uid,a.orderid,o.amount,a.productid,a.domainstatus,a.nextduedate,a.regdate,b.welcome_email,b.type,c.email,a.billingcycle,b.pay_type,b.api_type")->leftJoin("products b", "a.productid=b.id")->leftJoin("clients c", "a.uid=c.id")->join("orders o", "a.orderid=o.id")->where("a.id", $relid)->find();
				if (!empty($host)) {
					$pay_type = json_decode($host["pay_type"], true);
					if ($host["billingcycle"] == "onetime" || $host["billingcycle"] == "free") {
						\think\Db::name("host")->where("id", $host["id"])->update(["nextduedate" => 0, "nextinvoicedate" => 0, "regdate" => time()]);
					} else {
						$nextduedate = getNextTime($host["billingcycle"], $pay_type["pay_" . $host["billingcycle"] . "_cycle"], time(), $pay_type["pay_ontrial_cycle_type"] ?: "day");
						\think\Db::name("host")->where("id", $host["id"])->update(["nextduedate" => $nextduedate, "nextinvoicedate" => $nextduedate, "regdate" => time()]);
					}
				}
				$arr_admin = ["relid" => $val["rel_id"], "name" => "【管理员】订单支付完成提示", "type" => "invoice", "sync" => true, "admin" => true, "ip" => get_client_ip6()];
				if (configuration("shd_allow_email_send_queue")) {
					\app\queue\job\SendMail::push($arr_admin);
				} else {
					$curl_multi_data[count($curl_multi_data)] = ["url" => "async", "data" => $arr_admin];
				}
				$admin = getReceiveAdmin();
				foreach ($admin as $key => $value) {
					$arr_admin1 = ["relid" => $val["rel_id"], "name" => "【管理员】订单支付完成提示", "type" => "invoice", "sync" => true, "admin" => true, "adminid" => $value["id"], "ip" => get_client_ip6()];
					if (configuration("shd_allow_email_send_queue")) {
						\app\queue\job\SendMail::push($arr_admin1);
					} else {
						$curl_multi_data[count($curl_multi_data)] = ["url" => "async", "data" => $arr_admin1];
					}
				}
				$arr_client = ["relid" => $val["rel_id"], "name" => "付款成功提醒", "type" => "invoice", "sync" => true, "admin" => false, "ip" => get_client_ip6()];
				if ($email) {
					if (configuration("shd_allow_email_send_queue")) {
						\app\queue\job\SendMail::push($arr_client);
					} else {
						$curl_multi_data[count($curl_multi_data)] = ["url" => "async", "data" => $arr_client];
					}
				}
				$message_template_type = array_column(config("message_template_type"), "id", "name");
				$sms = new Sms();
				$client = check_type_is_use($message_template_type[strtolower("Invoice_Payment_Confirmation")], $host["uid"], $sms);
				if ($client) {
					$params = ["order_id" => $host["orderid"], "order_total_fee" => $host["amount"]];
					$arr_sms = ["name" => $message_template_type[strtolower("Invoice_Payment_Confirmation")], "phone" => $client["phone_code"] . $client["phonenumber"], "params" => json_encode($params), "sync" => false, "uid" => $host["uid"], "delay_time" => 0, "is_market" => false];
					if (configuration("shd_allow_sms_send_queue")) {
						\app\queue\job\SendSms::push($arr_sms);
					} else {
						$curl_multi_data[count($curl_multi_data)] = ["url" => "async_sms", "data" => $arr_sms];
					}
				}
				$productid = \think\Db::name("host")->where("id", $relid)->value("productid");
				if (get_product_condition($productid) == "payment") {
					if (configuration("shd_allow_auto_create_queue")) {
						\app\queue\job\AutoCreate::push(["hid" => $relid, "is_admin" => $this->is_admin, "ip" => get_client_ip6()]);
					} else {
						$curl_multi_data[count($curl_multi_data)] = ["url" => "async_create", "data" => ["hid" => $relid, "is_admin" => $this->is_admin, "ip" => get_client_ip6()]];
					}
				}
				if ($host["domainstatus"] == "Suspended") {
					$Host = new Host();
					$result = $Host->unsuspend($relid, 1);
					$logic_run_map = new RunMap();
					$model_host = new \app\common\model\HostModel();
					$data_i = [];
					$data_i["host_id"] = $relid;
					$data_i["active_type_param"] = [$relid, 1, "", 0];
					$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
					if ($result["status"] == 200) {
						$data_i["description"] = " 合并账单支付成功 - 解除暂停 Host ID:{$data_i["host_id"]}的产品成功";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 400, 3);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 300, 3);
						}
					} else {
						$data_i["description"] = " 合并账单支付成功 - 解除暂停 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 400, 3);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 300, 3);
						}
					}
				}
			} elseif ($type == "renew") {
				$Renew = new Renew();
				$Renew->is_admin = $this->is_admin;
				$Renew->renewHandle($item_id);
			} elseif ($type == "recharge") {
				\think\Db::name("clients")->where("id", $uid)->setInc("credit", $amount);
				credit_log(["uid" => $uid, "desc" => "Add Funds Invoice #" . $invoiceid, "amount" => $amount, "relid" => $invoiceid, "notes" => $invoice_data["notes"]]);
			} elseif ($type == "upgrade") {
				$upgrade_logic = new Upgrade();
				$upgrade_logic->doUpgrade($relid);
			} elseif ($type == "credit_limit") {
				if ($invoice_data["credit_limit_prepayment"] == 1) {
					$credit_limit_prepayment_invoices = json_decode($invoice_data["credit_limit_prepayment_invoices"], true);
					\think\Db::name("invoices")->whereIn("id", $credit_limit_prepayment_invoices)->update(["invoice_id" => $invoice_data["id"]]);
				}
				$cli = \think\Db::name("clients")->where("id", $uid)->find();
				$host = \think\Db::name("host")->alias("b")->field("b.id,b.uid,b.domain,b.nextinvoicedate,e.name")->leftJoin("orders c", "c.id = b.orderid")->leftJoin("invoices d", "d.id=c.invoiceid")->leftJoin("products e", "e.id = b.productid")->where("b.domainstatus", "Suspended")->where("d.invoice_id", $invoice_data["id"])->select()->toArray();
				foreach ($host as $hv) {
					$Host = new Host();
					$result = $Host->unsuspend($hv["id"], 1);
					$logic_run_map = new RunMap();
					$model_host = new \app\common\model\HostModel();
					$data_i = [];
					$data_i["host_id"] = $relid;
					$data_i["active_type_param"] = [$relid, 1, "", 0];
					$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
					if ($result["status"] == 200) {
						$data_i["description"] = " 账单支付成功 - 解除暂停 Host ID:{$data_i["host_id"]}的产品成功";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 400, 3);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 1, 300, 3);
						}
					} else {
						$data_i["description"] = " 账单支付成功 - 解除暂停 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
						if ($is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 400, 3);
						}
						if (!$is_zjmf) {
							$logic_run_map->saveMap($data_i, 0, 300, 3);
						}
					}
				}
			} elseif ($type == "combine") {
				\think\Db::name("invoices")->where("id", $relid)->update(["status" => "Paid", "paid_time" => time(), "update_time" => time(), "is_delete" => 0]);
				credit_log(["uid" => $uid, "desc" => "Credit Applied Invoice #" . $relid, "amount" => $amount, "relid" => $relid]);
				$this->processPaidInvoice($relid);
			} elseif ($type == "voucher") {
				\think\Db::name("voucher")->where("id", $relid)->update(["status" => "Pending", "update_time" => time()]);
			} elseif ($type == "contract") {
				\think\Db::name("contract_pdf")->where("id", $relid)->update(["status" => 3]);
			} elseif ($type == "user_grade") {
				\think\Db::name("agent")->where("uid", $uid)->update(["user_grade" => $relid]);
			}
		}
		if ($curl_multi_data) {
			asyncCurlMulti($curl_multi_data);
		}
		hook("invoice_paid", ["invoiceid" => $invoiceid]);
		return true;
	}
	public function cancelInvoices($hid)
	{
		$Ins = \think\Db::name("invoices")->alias("a")->distinct(true)->field("a.id")->leftJoin("invoice_items b", "a.id = b.invoice_id")->where("b.rel_id", $hid)->where("a.status", "Unpaid")->where("a.delete_time", 0)->where("b.delete_time", 0)->select()->toArray();
		$ids = array_column($Ins, "id");
		\think\Db::name("invoices")->whereIn("id", $ids)->update(["status" => "Cancelled", "update_time" => time()]);
		return true;
	}
	public function refundAndApply($invoiceid)
	{
		$total = 0;
		$accounts = \think\Db::name("accounts")->where("invoice_id", $invoiceid)->where("delete_time", 0)->select()->toArray();
		foreach ($accounts as $v1) {
			$total += $v1["amount_in"];
			$total -= $v1["amount_out"];
		}
		$credit = $this->creditRefund($invoiceid);
		return bcadd($total, $credit, 2);
	}
	public function creditRefund($invoiceid)
	{
		$invoice = \think\Db::name("invoices")->where("id", $invoiceid)->find();
		$apply = \think\Db::name("credit")->where("description", "like", "%Credit Applied to Invoice #%")->where("relid", $invoiceid)->sum("amount");
		if ($invoice["type"] == "renew") {
			$renew_apply = \think\Db::name("credit")->where("description", "Credit Applied to Renew Invoice #{$invoiceid}")->sum("amount");
		}
		$remove = \think\Db::name("credit")->where("description", "like", "%Credit Removed from Invoice #%")->where("relid", $invoiceid)->sum("amount");
		return bcadd($apply, bcadd($renew_apply, $remove));
	}
	public function productDivertCancelInvoices($hostid)
	{
		$renew_logic = new Renew();
		$renew_logic->deleteRenewInvoice($hostid);
		$upgrade_logic = new Upgrade();
		$upgrade_logic->deleteUpgradeInvoices($hostid);
		$hosts = \think\Db::name("host")->alias("a")->field("c.id as invoice_id")->leftJoin("orders b", "a.orderid = b.id")->leftJoin("invoices c", "b.invoiceid = c.id")->where("a.id", $hostid)->where("c.status", "Unpaid")->where("c.type", "<>", "credit_limit")->select()->toArray();
		$invoice_ids = array_column($hosts, "invoice_id");
		\think\Db::name("invoices")->whereIn("id", $invoice_ids)->useSoftDelete("delete_time", time())->delete();
		return true;
	}
}