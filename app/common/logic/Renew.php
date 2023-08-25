<?php

namespace app\common\logic;

class Renew
{
	public $lang;
	public $is_admin = false;
	public $uid;
	private $params = [];
	private $pay_type;
	public function __construct()
	{
	}
	public function initialize()
	{
		$this->lang = get_system_langs();
	}
	public function setOtherParams($amount)
	{
		$this->params = $amount;
		return $this;
	}
	public function getOtherParams()
	{
		return $this->params;
	}
	public function setPayType($pay_type)
	{
		$this->pay_type = $pay_type;
		return $this;
	}
	public function getPayType()
	{
		return $this->pay_type;
	}
	public function deleteRenewInvoice($hid)
	{
		$renew_invoices = \think\Db::name("invoices")->alias("a")->field("a.id")->leftJoin("invoice_items b", "a.id = b.invoice_id")->where("b.rel_id", $hid)->where("b.type", "renew")->where("a.status", "Unpaid")->select()->toArray();
		$invoice_ids = array_column($renew_invoices, "id");
		\think\Db::name("invoices")->whereIn("id", $invoice_ids)->delete();
	}
	public function deleteHostUnpaidUpgradeInvoice($hid)
	{
		$upgrade_invoices = \think\Db::name("invoices")->alias("a")->field("a.id")->leftJoin("invoice_items b", "a.id = b.invoice_id")->where("b.rel_id", $hid)->where("b.type", "upgrade")->where("a.status", "Unpaid")->select()->toArray();
		$invoice_ids = array_column($upgrade_invoices, "id");
		\think\Db::name("invoices")->whereIn("id", $invoice_ids)->delete();
		return true;
	}
	public function batchRenew($hids, $billingcycles)
	{
		if (empty($hids)) {
			return ["status" => 400, "msg" => lang("ID_ERROR")];
		}
		if (empty($billingcycles) || !is_array($billingcycles)) {
			return ["status" => 400, "msg" => lang("CYCLE_ERROR")];
		}
		foreach ($billingcycles as $billingcycle) {
			if (!in_array($billingcycle, array_keys(config("billing_cycle")))) {
				return ["status" => 400, "msg" => lang("CYCLE_ERROR")];
			}
		}
		if (is_string($hids) && is_string($billingcycles)) {
			$billingcycles = [$hids => $billingcycles];
			$hids = [$hids];
		}
		if (!empty($this->params) && is_array($this->params)) {
			$amount_custom = $this->params;
		}
		$uid = $this->uid ?: request()->uid;
		$payment = \think\Db::name("clients")->where("id", $uid)->value("defaultgateway");
		if (!$payment) {
			$gateway_list = gateway_list("gateways");
			$payment_name_list = array_column($gateway_list, "name");
			$payment = $payment_name_list[0];
		}
		$invoice_items_data = $renew_items_data = [];
		$total = $amount_old = 0;
		\think\Db::startTrans();
		try {
			foreach ($hids as $hid) {
				$billingcycle = $billingcycles[$hid];
				$host_data = \think\Db::name("host")->field("h.id,h.uid,h.orderid,h.productid,h.domain,h.amount,h.promoid,h.payment,h.billingcycle,h.nextduedate,h.nextinvoicedate,h.domainstatus,h.dedicatedip,p.name as productname,p.pay_method,p.pay_type,i.status,h.flag")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("orders o", "h.orderid = o.id")->leftJoin("invoices i", "o.invoiceid = i.id")->where("h.id", $hid)->where("i.delete_time", 0)->where("o.delete_time", 0)->find();
				if (!empty($host_data) && $host_data["status"] == "Unpaid") {
					return ["status" => 400, "msg" => "产品{$hid}未支付，生成续费账单失败"];
				} else {
					$host_data = \think\Db::name("host")->field("h.id,h.uid,h.orderid,h.productid,h.domain,h.amount,h.promoid,h.payment,h.billingcycle,h.nextduedate,h.nextinvoicedate,h.domainstatus,h.dedicatedip,p.name as productname,p.pay_method,p.pay_type,h.flag")->alias("h")->leftJoin("products p", "p.id=h.productid")->where("h.id", $hid)->find();
				}
				$uid = $host_data["uid"];
				$promoid = $host_data["promoid"];
				$currency_id = priorityCurrency($uid);
				$pid = $host_data["productid"];
				if ($host_data["api_type"] == "resource") {
					$billingcycle = $billingcycle ?: $host_data["billingcycle"];
					$amount1 = $this->calculatedPrice($hid, $billingcycle, 1, $host_data["flag"]);
					$amount = $amount1["price_cycle"];
					$userdiscount = 0;
				} else {
					if (!empty($billingcycle) && $billingcycle != $host_data["billingcycle"]) {
						if ($amount_custom) {
							$amount = intval($amount_custom[$hid]);
						} else {
							if ((new \app\common\model\ProductModel())->checkProductPrice($pid, $billingcycle, $currency_id)) {
								$amount1 = $this->calculatedPrice($hid, $billingcycle, 1, $host_data["flag"]);
								$amount = $amount1["price_cycle"];
								$userdiscount = $amount1["price_sale_cycle"];
								if ($userdiscount > 0) {
									$invoice_items1[] = ["uid" => $uid, "type" => "discount", "rel_id" => $hid, "description" => "客戶折扣", "amount" => "-" . $userdiscount];
								}
							} else {
								$amount = $host_data["amount"];
								$userdiscount = 0;
							}
						}
					} else {
						$billingcycle = $host_data["billingcycle"];
						if ($amount_custom) {
							$amount = intval($amount_custom[$hid]);
						} else {
							$amount = $host_data["amount"];
						}
						$userdiscount = 0;
					}
				}
				$amount = $amount < 0 ? 0 : $amount;
				$pay_method = $host_data["pay_method"];
				$domainstatus = $host_data["domainstatus"];
				$create_time = time();
				$expire_time = $host_data["nextduedate"];
				$billing_cycle = config("coupon_cycle");
				if (!in_array($domainstatus, ["Active", "Suspended"])) {
					return ["status" => 400, "msg" => lang("产品状态必须是已激活或已暂停")];
				}
				if ($amount >= 0 && !in_array($billingcycle, ["free", "ontrial"]) && $pay_method == "prepayment" && in_array($domainstatus, ["Active", "Suspended"])) {
					$total = bcadd($total, $amount, 2);
					$pay_type = json_decode($host_data["pay_type"], true);
					$start_time = date("Y/m/d H", $host_data["nextduedate"]);
					$end_time = date("Y/m/d H", getNextTime($billingcycle, $pay_type["pay_" . $billingcycle . "_cycle"], $host_data["nextduedate"], $pay_type["pay_ontrial_cycle_type"] ?: "day"));
					$invoice_items = ["uid" => $uid, "type" => "renew", "rel_id" => $hid, "description" => "续费账单 - " . $host_data["productname"] . "(" . $host_data["domain"] . "),IP({$host_data["dedicatedip"]}),购买时长：" . $billing_cycle[$billingcycle] . "(" . $start_time . "-" . $end_time . ")", "amount" => bcadd($amount, $userdiscount)];
					$invoice_items_data[] = $invoice_items;
					$renew_items = ["uid" => $uid, "type" => "normal", "new_cycle" => $billingcycle, "new_recurring_amount" => $amount, "status" => "Pending", "paid" => "N", "create_time" => $create_time, "expire_time" => $expire_time, "hid" => $hid];
					$renew_items_data[] = $renew_items;
					$has = \think\Db::name("invoice_items")->alias("a")->field("b.id,b.status,b.subtotal")->leftJoin("invoices b", "a.invoice_id=b.id")->where("a.type", "renew")->where("a.rel_id", $hid)->where("a.uid", $uid)->where("b.delete_time", 0)->where("a.delete_time", 0)->order("b.id", "desc")->find();
					if (!empty($has) && $has["status"] == "Unpaid") {
						$invoice_credit = \think\Db::name("invoices")->where("id", $has["id"])->where("delete_time", 0)->value("credit");
						$invoice_credit = $invoice_credit > 0 ? $invoice_credit : 0;
						$amount_old = bcadd($amount_old, $invoice_credit, 2);
						\think\Db::name("invoices")->where("id", $has["id"])->delete();
						\think\Db::name("invoice_items")->where("invoice_id", $has["id"])->delete();
						$accounts = \think\Db::name("accounts")->where("invoice_id", $has["id"])->select()->toArray();
						if (!empty($accounts[0])) {
							$amount_in = $amount_out = 0;
							foreach ($accounts as $account) {
								$amount_in += $account["amount_in"];
								$amount_out += $account["amount_out"];
							}
							$amount_old = bcadd($amount_old, bcsub($amount_in, $amount_out, 2), 2);
						}
					}
				} else {
					return ["status" => 400, "msg" => "产品{$hid}不满足续费条件"];
				}
				$flags = getSaleProductUser($pid, $uid);
				if ($flags) {
					$hh = \think\Db::name("host")->field("flag")->where("id", $hid)->where("flag", 1)->find();
					if (empty($hh["flag"])) {
						$res = \think\Db::name("host")->where("id", $hid)->update(["flag" => 1]);
					}
				}
			}
			$menu = new Menu();
			if (count($hids) > 1) {
				$fpid = \think\Db::name("host")->where("id", $hids[0])->value("productid");
				$url = $menu->proGetNavId(intval($fpid))["url"] ?: "";
			} else {
				if (count($hids) == 1) {
					$url = "servicedetail?id=" . $hids[0];
				}
			}
			$insert_invoices = ["uid" => $uid, "create_time" => $create_time, "due_time" => $expire_time, "paid_time" => 0, "subtotal" => $total, "credit" => 0, "total" => $total, "status" => "Unpaid", "type" => "renew", "payment" => $payment, "url" => $url];
			if ($total > 0) {
				$invoice_id = \think\Db::name("invoices")->insertGetId($insert_invoices);
				if (!empty($invoice_items1)) {
					foreach ($invoice_items1 as $k => $v) {
						$invoice_items1[$k]["invoice_id"] = $invoice_id;
						\think\Db::name("invoice_items")->insert($invoice_items1[$k]);
					}
				}
				foreach ($invoice_items_data as $k => $v) {
					$v["invoice_id"] = $invoice_id;
					$invoice_items_id = \think\Db::name("invoice_items")->insertGetId($v);
					$renew_items_data[$k]["relid"] = $invoice_items_id;
					unset($renew_items_data[$k]["hid"]);
				}
				\think\Db::name("renew_cycle")->insertAll($renew_items_data);
				\think\Db::commit();
				$res = ["status" => 200, "msg" => "生成续费账单成功", "data" => ["invoice_id" => $invoice_id]];
			} else {
				foreach ($renew_items_data as $v) {
					$this->renewHandleIn($v["new_cycle"], $v["hid"]);
				}
				\think\Db::commit();
				$res = ["status" => 1001, "msg" => "续费成功"];
			}
			foreach ($hids as $hid) {
				$this->deleteHostUnpaidUpgradeInvoice($hid);
				active_log_final(sprintf(" 生成续费账单成功 - User ID:%s - Host ID:%s - Invoice ID:%s ", $uid, $hid, $invoice_id), $uid, 2, $hid);
			}
		} catch (\Exception $e) {
			\think\Db::rollback();
			$res = ["status" => 400, "msg" => "生成续费账单失败"];
		}
		if ($res["status"] == 200) {
			if ($amount_old < $total) {
				if ($amount_old > 0) {
					\think\Db::name("clients")->where("id", $uid)->setInc("credit", $amount_old);
					credit_log(["uid" => $uid, "desc" => "已支付金额小于新账单续费金额,退款至余额", "amount" => $amount_old, "relid" => $invoice_id]);
				}
				return $res;
			} else {
				\think\Db::startTrans();
				try {
					$credit = bcsub($amount_old, $total, 2);
					if ($credit > 0) {
						\think\Db::name("clients")->where("id", $uid)->setInc("credit", $credit);
					}
					credit_log(["uid" => $uid, "desc" => "已支付金额大于新账单续费金额,退款多余金额至余额", "amount" => $credit, "relid" => $invoice_id]);
					\think\Db::name("invoices")->where("id", $invoice_id)->update(["status" => "Paid", "paid_time" => time()]);
					$invoice_logic = new Invoices();
					$invoice_logic->is_admin = $this->is_admin;
					$invoice_logic->processPaidInvoice($invoice_id);
					\think\Db::commit();
					return ["status" => 1001, "msg" => lang("续费成功"), "data" => ["invoice_id" => $invoice_id]];
				} catch (\Exception $e) {
					\think\Db::rollback();
					return ["status" => 400, "msg" => lang("续费失败")];
				}
			}
		} else {
			return $res;
		}
	}
	public function renew($hid, $billingcycle)
	{
		if (empty($hid)) {
			return ["status" => 400, "msg" => lang("ID_ERROR")];
		}
		if (empty($billingcycle) || !in_array($billingcycle, array_keys(config("billing_cycle")))) {
			return ["status" => 400, "msg" => lang("CYCLE_ERROR")];
		}
		$host_data = \think\Db::name("host")->field("h.id,h.uid,h.orderid,h.productid,h.domain,h.amount,h.promoid,h.payment,h.billingcycle,
            h.nextduedate,h.nextinvoicedate,h.domainstatus,h.dedicatedip,p.name as productname,p.pay_method,
            p.pay_type,i.status,h.flag,p.api_type,p.upstream_price_value")->alias("h")->leftJoin("products p", "p.id=h.productid")->leftJoin("orders o", "h.orderid = o.id")->leftJoin("invoices i", "o.invoiceid = i.id")->where("h.id", $hid)->where("i.delete_time", 0)->where("o.delete_time", 0)->find();
		$promoid = $host_data["promoid"];
		if (!empty($host_data)) {
			if ($host_data["status"] == "Unpaid") {
				return ["status" => 400, "msg" => lang("产品未支付，生成续费账单失败")];
			}
			if ($host_data["billingcycle"] == "ontrial" && $billingcycle == "ontrial") {
				return ["status" => 400, "msg" => lang("续费周期无效")];
			}
			$uid = $host_data["uid"];
			$currency_id = priorityCurrency($uid);
			$pid = $host_data["productid"];
		} else {
			$host_data = \think\Db::name("host")->field("h.id,h.uid,h.orderid,h.productid,h.domain,h.amount,h.promoid,h.payment,h.billingcycle,
                h.nextduedate,h.nextinvoicedate,h.domainstatus,h.dedicatedip,p.name as productname,p.pay_method,
                p.pay_type,h.flag,p.api_type,p.upstream_price_value")->alias("h")->leftJoin("products p", "p.id=h.productid")->where("h.id", $hid)->find();
			$uid = $host_data["uid"];
			$currency_id = priorityCurrency($uid);
			$pid = $host_data["productid"];
		}
		if ($host_data["api_type"] == "resource") {
			$billingcycle = $billingcycle ?: $host_data["billingcycle"];
			$amount1 = $this->calculatedPrice($hid, $billingcycle, 1, $host_data["flag"]);
			$amount = $amount1["price_cycle"];
			$userdiscount = 0;
		} else {
			if (!empty($billingcycle) && $billingcycle != $host_data["billingcycle"]) {
				if ((new \app\common\model\ProductModel())->checkProductPrice($pid, $billingcycle, $currency_id)) {
					$amount1 = $this->calculatedPrice($hid, $billingcycle, 1, $host_data["flag"]);
					$amount = $amount1["price_cycle"];
					$userdiscount = floatval($amount1["price_sale_cycle"]);
					$param = request()->param();
					if (isset($param["resource_handling"])) {
						$amount = bcmul($amount, $param["resource_handling"], 2);
						$userdiscount = bcmul($userdiscount, $param["resource_handling"], 2);
					}
				} else {
					$amount = $host_data["amount"];
					$userdiscount = 0;
				}
			} else {
				$billingcycle = $host_data["billingcycle"];
				$amount = $host_data["amount"];
				$userdiscount = 0;
			}
		}
		$invoice_items1 = [];
		if ($userdiscount > 0) {
			$invoice_items1 = ["uid" => $uid, "type" => "discount", "description" => "客戶折扣", "amount" => "-" . $userdiscount, "rel_id" => $hid];
		}
		$amount = $amount < 0 ? 0 : $amount;
		$pay_method = $host_data["pay_method"];
		$domainstatus = $host_data["domainstatus"];
		$create_time = time();
		$expire_time = $host_data["nextduedate"];
		$billing_cycle = config("coupon_cycle");
		if (!in_array($domainstatus, ["Active", "Suspended"])) {
			return ["status" => 400, "msg" => lang("产品状态必须是已激活或已暂停")];
		}
		if ($amount >= 0 && !in_array($billingcycle, ["free", "ontrial"]) && $pay_method == "prepayment" && in_array($domainstatus, ["Active", "Suspended"])) {
			$pay_type = json_decode($host_data["pay_type"], true);
			$start_time = date("Y/m/d H", $host_data["nextduedate"]);
			$end_time = date("Y/m/d H", getNextTime($billingcycle, $pay_type["pay_" . $billingcycle . "_cycle"], $host_data["nextduedate"], $pay_type["pay_ontrial_cycle_type"] ?: "day"));
			$invoice_items = ["uid" => $uid, "type" => "renew", "rel_id" => $hid, "description" => "续费账单 - " . $host_data["productname"] . "(" . $host_data["domain"] . "),IP({$host_data["dedicatedip"]}),购买时长：" . $billing_cycle[$billingcycle] . "(" . $start_time . "-" . $end_time . ")", "amount" => bcadd($amount, $userdiscount, 2)];
			$renew_items = ["uid" => $uid, "type" => "normal", "new_cycle" => $billingcycle, "new_recurring_amount" => $amount, "status" => "Pending", "paid" => "N", "create_time" => $create_time, "expire_time" => $expire_time];
			$insert_invoices = ["uid" => $uid, "create_time" => $create_time, "due_time" => $expire_time, "paid_time" => 0, "subtotal" => $amount, "credit" => 0, "total" => $amount, "status" => "Unpaid", "type" => "renew", "payment" => $host_data["payment"], "url" => "servicedetail?id=" . $hid];
			if ($host_data["api_type"] == "resource") {
				$price_type = \think\Db::name("res_products")->where("productid", $host_data["productid"])->value("price_type");
				if ($price_type == "handling") {
					$insert_invoices["handling"] = floatval(configuration("shd_resource_handling_model"));
				}
			}
			$has = \think\Db::name("invoice_items")->alias("a")->field("b.id,b.status,b.subtotal")->leftJoin("invoices b", "a.invoice_id=b.id")->where("a.type", "renew")->where("a.rel_id", $hid)->where("a.uid", $uid)->where("b.delete_time", 0)->where("a.delete_time", 0)->order("b.id", "desc")->find();
			if ($amount > 0) {
				if (empty($has) || $has["status"] === "Paid") {
					\think\Db::startTrans();
					try {
						$flags = getSaleProductUser($pid, $uid);
						if ($flags) {
							$hh = \think\Db::name("host")->field("flag")->where("id", $hid)->where("flag", 1)->find();
							if (empty($hh["flag"])) {
								$res = \think\Db::name("host")->where("id", $hid)->update(["flag" => 1]);
							}
						}
						$invoice_id = \think\Db::name("invoices")->insertGetId($insert_invoices);
						$invoice_items["invoice_id"] = $invoice_id;
						$invoice_items_id = \think\Db::name("invoice_items")->insertGetId($invoice_items);
						if (!empty($invoice_items1)) {
							$invoice_items1["invoice_id"] = $invoice_id;
							$invoice_items_id1 = \think\Db::name("invoice_items")->insertGetId($invoice_items1);
						}
						$renew_items["relid"] = $invoice_items_id;
						\think\Db::name("renew_cycle")->insert($renew_items);
						$this->deleteHostUnpaidUpgradeInvoice($hid);
						\think\Db::commit();
						active_log_final(sprintf(" 生成续费账单成功 - User ID:%s - Host ID:%s - Invoice ID:%s ", $uid, $hid, $invoice_id), $uid, 2, $hid);
						return ["status" => 200, "msg" => "生成续费账单成功", "data" => ["invoice_id" => $invoice_id]];
					} catch (\Exception $e) {
						\think\Db::rollback();
						return ["status" => 400, "msg" => "生成续费账单失败" . $e->getMessage()];
					}
				} else {
					if ($has["status"] != "Paid") {
						\think\Db::startTrans();
						try {
							$amount_old = 0;
							$invoice_credit = \think\Db::name("invoices")->where("id", $has["id"])->value("credit");
							$amount_old = bcadd($amount_old, $invoice_credit, 2);
							\think\Db::name("invoices")->where("id", $has["id"])->delete();
							\think\Db::name("invoice_items")->where("invoice_id", $has["id"])->delete();
							$flags = getSaleProductUser($pid, $uid);
							if ($flags) {
								$hh = \think\Db::name("host")->field("flag")->where("id", $hid)->where("flag", 1)->find();
								if (empty($hh["flag"])) {
									$res = \think\Db::name("host")->where("id", $hid)->update(["flag" => 1]);
								}
							}
							$invoice_id = \think\Db::name("invoices")->insertGetId($insert_invoices);
							$invoice_items["invoice_id"] = $invoice_id;
							$invoice_items1["invoice_id"] = $invoice_id;
							$invoice_items_id = \think\Db::name("invoice_items")->insertGetId($invoice_items);
							$invoice_items_id1 = \think\Db::name("invoice_items")->insertGetId($invoice_items1);
							$renew_items["relid"] = $invoice_items_id;
							$ress = \think\Db::name("renew_cycle")->insert($renew_items);
							\think\Db::commit();
							$accounts = \think\Db::name("accounts")->where("invoice_id", $has["id"])->select()->toArray();
							if (!empty($accounts[0])) {
								$amount_in = $amount_out = 0;
								foreach ($accounts as $account) {
									$amount_in += $account["amount_in"];
									$amount_out += $account["amount_out"];
								}
								$amount_old = bcadd($amount_old, bcsub($amount_in, $amount_out, 2));
							}
							\think\Db::commit();
							active_log_final(sprintf(" 生成续费账单成功 - User ID:%s - Host ID:%s - Invoice ID:%s ", $uid, $hid, $invoice_id), $uid, 2, $hid);
							$res = ["status" => 200, "msg" => "未支付账单已删除,并生成新续费账单成功", "data" => ["invoice_id" => $invoice_id]];
						} catch (\Exception $e) {
							\think\Db::rollback();
							$res = ["status" => 400, "msg" => "生成新续费账单失败" . $e->getMessage()];
						}
						if ($res["status"] == 200) {
							if ($amount_old < $amount) {
								if ($amount_old > 0) {
									\think\Db::name("clients")->where("id", $uid)->setInc("credit", $amount_old);
									credit_log(["uid" => $uid, "desc" => "已支付金额小于新账单续费金额,退款至余额", "amount" => $amount_old, "relid" => $invoice_id]);
								}
								return $res;
							} else {
								$credit = bcsub($amount_old, $amount, 2);
								if ($credit > 0) {
									\think\Db::name("clients")->where("id", $uid)->setInc("credit", $credit);
									credit_log(["uid" => $uid, "desc" => "已支付金额大于新账单续费金额,退款多余金额至余额", "amount" => $credit, "relid" => $invoice_id]);
								}
								\think\Db::name("invoices")->where("id", $invoice_id)->update(["status" => "Paid", "paid_time" => time()]);
								$invoice_logic = new Invoices();
								$invoice_logic->is_admin = $this->is_admin;
								$invoice_logic->processPaidInvoice($invoice_id);
								return ["status" => 1001, "msg" => "续费成功", "data" => ["invoice_id" => $invoice_id]];
							}
						} else {
							return $res;
						}
					} else {
						return ["status" => 400, "msg" => "不满足续费条件"];
					}
				}
			} else {
				$this->renewHandleIn($billingcycle, $hid);
				return ["status" => 200, "msg" => "续费成功"];
			}
		} else {
			return ["status" => 400, "msg" => lang("不满足续费条件")];
		}
	}
	public function calculatedPrice($hostid, $compute_cycle = "", $type = 0, $flags = 1)
	{
		$host_data = \think\Db::name("host")->field("id,uid,productid,billingcycle,promoid")->where("id", $hostid)->find();
		$uid = $host_data["uid"];
		$currency = getUserCurrency($uid);
		$currencyid = $currency["id"];
		$pid = $host_data["productid"];
		$billingcycle = $host_data["billingcycle"];
		if ($compute_cycle) {
			$billingcycle = $compute_cycle;
		} else {
			return 0;
		}
		if ($billingcycle == "free") {
			return 0.0;
		}
		$promoid = $host_data["promoid"];
		$price_type = config("price_type");
		$itself_price = $price_type[$billingcycle][0];
		$price_cycle = 0.0;
		$product_pricing = \think\Db::name("pricing")->where("type", "product")->where("relid", $pid)->where("currency", $currencyid)->find();
		if ($product_pricing[$billingcycle] < 0) {
			return $price_cycle;
		}
		if ($product_pricing[$itself_price] > 0) {
			$price_cycle += $product_pricing[$itself_price];
		}
		$price_sale_cycle = 0.0;
		$bates = 0;
		if ($flags == 0 && $type == 1) {
			$flag = false;
		} else {
			$flag = getSaleProductUser($host_data["productid"], $host_data["uid"]);
			if ($flag && $flag["type"] == 1) {
				$bates = 1 - $flag["bates"] / 100;
				$price_sale_cycle += bcmul($price_cycle, $bates, 2);
				$price_cycle = sprintf("%.2f", bcsub($price_cycle, bcmul($price_cycle, $bates, 2), 2));
			}
		}
		$configgroups = \think\Db::name("products")->alias("p")->field("pcg.id,pcg.name,pcg.description")->join("product_config_links pcl", "pcl.pid = p.id")->join("product_config_groups pcg", "pcg.id = pcl.gid")->where("p.id", $pid)->select()->toArray();
		foreach ($configgroups as $ckey => $configgroup) {
			if (!empty($configgroup)) {
				$gid = $configgroup["id"];
				$options = \think\Db::name("product_config_options")->where("gid", $gid)->where("hidden", 0)->select()->toArray();
				foreach ($options as $okey => $option) {
					$cid = $option["id"];
					$option_type = $option["option_type"];
					$user_config_data = \think\Db::name("host_config_options")->where("relid", $hostid)->where("configid", $cid)->find();
					if (empty($user_config_data)) {
						continue;
					}
					$qty = $user_config_data["qty"];
					$optionid = $user_config_data["optionid"];
					$config_pricing = \think\Db::name("pricing")->where("type", "configoptions")->where("relid", $optionid)->where("currency", $currencyid)->find();
					if ($config_pricing[$itself_price] < 0) {
						continue;
					}
					if ($flag && $flag["type"] == 1 && $option["is_discount"] == 1) {
						if (judgeQuantity($option_type)) {
							if (judgeQuantityStage($option_type)) {
								$sum = quantityStagePrice($option["id"], $currency, $qty, $billingcycle);
								$price_sale_cycle += bcmul($sum[0], $bates, 2);
								$sum[0] = bcsub($sum[0], bcmul($sum[0], $bates, 2), 2);
								$price_cycle += $sum[0];
							} else {
								$s = $config_pricing[$itself_price] * $qty;
								$price_sale_cycle += bcmul($s, $bates, 2);
								$s = bcsub($s, bcmul($s, $bates, 2), 2);
								$price_cycle += $s;
							}
						} else {
							$s = $config_pricing[$itself_price];
							$price_sale_cycle += bcmul($s, $bates, 2);
							$s = bcsub($s, bcmul($s, $bates, 2), 2);
							$price_cycle += $s;
						}
					} else {
						if (judgeQuantity($option_type)) {
							if (judgeQuantityStage($option_type)) {
								$sum = quantityStagePrice($option["id"], $currency, $qty, $billingcycle);
								$price_cycle += $sum[0];
							} else {
								$price_cycle += $config_pricing[$itself_price] * $qty;
							}
						} else {
							$price_cycle += $config_pricing[$itself_price];
						}
					}
				}
			}
		}
		$product = \think\Db::name("products")->where("id", $pid)->find();
		if ($product["api_type"] == "zjmf_api" && $product["upstream_version"] > 0 && $product["upstream_price_type"] == "percent") {
			$price_cycle = bcmul($price_cycle, $product["upstream_price_value"], 2) / 100;
		}
		if ($product["api_type"] == "resource" && function_exists("resourceUserGradePercent")) {
			$percent_value = resourceUserGradePercent($uid, $pid);
			$price_cycle = bcmul($price_cycle, $percent_value / 100, 2);
		}
		if ($flag) {
			if ($flag["type"] == 1) {
			} elseif ($flag["type"] == 2) {
				$bates = $flag["bates"];
				$price_sale_cycle = $bates;
				$price_cycle = bcsub($price_cycle, $bates, 2);
			} else {
				$bates = $flag["bates"];
				$price_sale_cycle = $bates;
				$price_cycle = bcsub($price_cycle, $bates, 2);
			}
		}
		$price_cycle = sprintf("%.2f", $price_cycle);
		$price_sale_cycle = sprintf("%.2f", $price_sale_cycle);
		if ($type == 1) {
			return ["price_cycle" => $price_cycle, "price_sale_cycle" => $price_sale_cycle];
		} else {
			return $price_cycle >= 0 ? $price_cycle : 0;
		}
	}
	protected function renewHandleIn($billingcycle, $hid)
	{
		$Host = new Host();
		$Host->is_admin = $this->is_admin;
		$res = \think\Db::name("host")->alias("a")->field("a.port")->field("a.uid,a.id as hostid,a.nextduedate,a.domainstatus,b.name,a.nextinvoicedate,a.dedicatedip")->leftJoin("products b", "a.productid = b.id")->where("a.id", $hid)->find();
		if (!empty($res)) {
			$pay_type = json_decode($res["pay_type"], true);
			if ($billingcycle == "onetime" || $billingcycle == "free") {
				$next_time = 0;
			} else {
				$next_time = getNextTime($billingcycle, $pay_type["pay_" . $billingcycle . "_cycle"], $res["nextduedate"], $pay_type["pay_ontrial_cycle_type"] ?: "day");
			}
		} else {
			$next_time = 0;
		}
		$data = ["nextduedate" => $next_time, "nextinvoicedate" => $next_time, "update_time" => time(), "amount" => 0, "billingcycle" => $billingcycle];
		$up = \think\Db::name("host")->where("id", $res["hostid"])->update($data);
		if ($res["domainstatus"] == "Suspended" && !!configuration("cron_host_unsuspend")) {
			$result = $Host->unsuspend($res["hostid"], configuration("cron_host_unsuspend_send") ?? 0);
			$logic_run_map = new RunMap();
			$model_host = new \app\common\model\HostModel();
			$data_i = [];
			$data_i["host_id"] = $res["hostid"];
			$data_i["active_type_param"] = [$res["hostid"], configuration("cron_host_unsuspend_send") ?? 0, "", 0];
			$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
			if ($result["status"] == 200) {
				$data_i["description"] = " 产品续费 - 解除暂停 Host ID:{$data_i["host_id"]}的产品成功";
				if ($is_zjmf) {
					$logic_run_map->saveMap($data_i, 1, 400, 3);
				}
				if (!$is_zjmf) {
					$logic_run_map->saveMap($data_i, 1, 300, 3);
				}
			} else {
				$data_i["description"] = " 产品续费 - 解除暂停 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
				if ($is_zjmf) {
					$logic_run_map->saveMap($data_i, 0, 400, 3);
				}
				if (!$is_zjmf) {
					$logic_run_map->saveMap($data_i, 0, 300, 3);
				}
			}
		} else {
			$email = new Email();
			$email->sendEmailBase($res["hostid"], "Service_Unsuspension_Notification", "product", true);
		}
		$message_template_type = array_column(config("message_template_type"), "id", "name");
		$sms = new Sms();
		$client = check_type_is_use($message_template_type[strtolower("Service_Unsuspension_Notification")], $res["uid"], $sms);
		if ($client) {
			$params = ["product_name" => $res["name"], "product_terminate_time" => date("Y-m-d H:i:s", $next_time), "product_mainip" => $res["dedicatedip"]];
			$params["product_mainip"] .= $res["port"] ? ":" . $res["port"] : "";
			$sms->sendSms($message_template_type[strtolower("Service_Unsuspension_Notification")], $client["phone_code"] . $client["phonenumber"], $params, false, $res["uid"]);
		}
		if ($up) {
			$description = "产品续费成功 - Host ID:" . $res["hostid"] . "，到期时间修改为：" . date("Y-m-d H:i:s", $next_time);
			trace("wyh测试使用--续费操作2:" . $description, "info_ali_notice_log");
		} else {
			$description = "产品续费失败 - Host ID:" . $res["hostid"];
		}
		active_log_final($description, $res["uid"], 2, $res["hostid"]);
		$result = $Host->renew($res["hostid"]);
		$logic_run_map = new RunMap();
		$model_host = new \app\common\model\HostModel();
		$data_i = [];
		$data_i["host_id"] = $res["hostid"];
		$data_i["active_type_param"] = [$res["hostid"]];
		$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
		if ($result["status"] == 200) {
			$data_i["description"] = " 账单支付成功 - 进行续费 Host ID:{$data_i["host_id"]}的产品成功";
			if ($is_zjmf) {
				$logic_run_map->saveMap($data_i, 1, 400, 5);
			}
			if (!$is_zjmf) {
				$logic_run_map->saveMap($data_i, 1, 300, 5);
			}
		} else {
			$data_i["description"] = " 账单支付成功 - 进行续费 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
			if ($is_zjmf) {
				$logic_run_map->saveMap($data_i, 0, 400, 5);
			}
			if (!$is_zjmf) {
				$logic_run_map->saveMap($data_i, 0, 300, 5);
			}
		}
	}
	/**
	 * 产品续费更新(外部调用) =》 加代码记得将上面内部调用一起处理
	 * @param $id 账单子项ID
	 * @throws \think\Exception
	 * @throws \think\db\exception\DataNotFoundException
	 * @throws \think\db\exception\DbException
	 * @throws \think\db\exception\ModelNotFoundException
	 * @throws \think\exception\DbException
	 * @throws \think\exception\PDOException
	 */
	public function renewHandle($id)
	{
		$Host = new Host();
		$Host->is_admin = $this->is_admin;
		$renew_cycle = \think\Db::name("renew_cycle")->where("relid", $id)->find();
		$res = \think\Db::name("invoice_items")->alias("a")->field("b.port")->field("a.uid,b.id as hostid,c.pay_type,b.nextduedate,b.domainstatus,c.name,b.nextinvoicedate,b.dedicatedip,b.domain")->leftJoin("host b", "a.rel_id = b.id")->leftJoin("products c", "b.productid = c.id")->where("a.type", "renew")->where("a.delete_time", 0)->where("a.id", $id)->find();
		$billingcycle = $renew_cycle["new_cycle"];
		if (!empty($res)) {
			$pay_type = json_decode($res["pay_type"], true);
			if ($billingcycle == "onetime" || $billingcycle == "free") {
				$next_time = 0;
			} else {
				$next_time = getNextTime($billingcycle, $pay_type["pay_" . $billingcycle . "_cycle"], $res["nextduedate"], $pay_type["pay_ontrial_cycle_type"] ?: "day");
			}
		} else {
			$next_time = 0;
		}
		$data = ["nextduedate" => $next_time, "nextinvoicedate" => $next_time, "update_time" => time(), "amount" => $renew_cycle["new_recurring_amount"], "billingcycle" => $billingcycle];
		$up = \think\Db::name("host")->where("id", $res["hostid"])->update($data);
		if ($res["domainstatus"] == "Suspended" && !!configuration("cron_host_unsuspend")) {
			$result = $Host->unsuspend($res["hostid"], configuration("cron_host_unsuspend_send") ?? 0);
			$logic_run_map = new RunMap();
			$model_host = new \app\common\model\HostModel();
			$data_i = [];
			$data_i["host_id"] = $res["hostid"];
			$data_i["active_type_param"] = [$res["hostid"], configuration("cron_host_unsuspend_send") ?? 0, "", 0];
			$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
			if ($result["status"] == 200) {
				$data_i["description"] = " 产品续费 - 解除暂停 Host ID:{$data_i["host_id"]}的产品成功";
				if ($is_zjmf) {
					$logic_run_map->saveMap($data_i, 1, 400, 3);
				}
				if (!$is_zjmf) {
					$logic_run_map->saveMap($data_i, 1, 300, 3);
				}
			} else {
				$data_i["description"] = " 产品续费 - 解除暂停 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
				if ($is_zjmf) {
					$logic_run_map->saveMap($data_i, 0, 400, 3);
				}
				if (!$is_zjmf) {
					$logic_run_map->saveMap($data_i, 0, 300, 3);
				}
			}
		} else {
			if (configuration("shd_allow_email_send")) {
				$email = new Email();
				$email->sendEmailBase($res["hostid"], "Service_Unsuspension_Notification", "product", true);
			}
		}
		$message_template_type = array_column(config("message_template_type"), "id", "name");
		$sms = new Sms();
		$client = check_type_is_use($message_template_type[strtolower("Service_Unsuspension_Notification")], $res["uid"], $sms);
		if ($client) {
			$params = ["product_name" => $res["name"], "product_end_time" => date("Y-m-d H:i:s", $next_time), "product_mainip" => $res["dedicatedip"], "hostname" => $res["domain"]];
			$params["product_mainip"] .= $res["port"] ? ":" . $res["port"] : "";
			$sms->sendSms($message_template_type[strtolower("Service_Unsuspension_Notification")], $client["phone_code"] . $client["phonenumber"], $params, false, $res["uid"]);
		}
		if ($up) {
			\think\Db::name("upgrades")->where("type", "configoptions")->where("relid", $res["hostid"])->where("uid", $res["uid"])->where("status", "Pending")->where("paid", "N")->update(["days_remaining" => 1]);
			$description = "产品续费成功 - Host ID:" . $res["hostid"] . "，到期时间修改为：" . date("Y-m-d H:i:s", $next_time);
		} else {
			$description = "产品续费失败 - Host ID:" . $res["hostid"];
		}
		active_log_final($description, $res["uid"], 2, $res["hostid"]);
		$result = $Host->renew($res["hostid"]);
		$logic_run_map = new RunMap();
		$model_host = new \app\common\model\HostModel();
		$data_i = [];
		$data_i["host_id"] = $res["hostid"];
		$data_i["active_type_param"] = [$res["hostid"]];
		$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
		if ($result["status"] == 200) {
			$data_i["description"] = " 账单支付成功 - 进行续费 Host ID:{$data_i["host_id"]}的产品成功";
			if ($is_zjmf) {
				$logic_run_map->saveMap($data_i, 1, 400, 5);
			}
			if (!$is_zjmf) {
				$logic_run_map->saveMap($data_i, 1, 300, 5);
			}
		} else {
			$data_i["description"] = " 账单支付成功 - 进行续费 Host ID:{$data_i["host_id"]}的产品失败：{$result["msg"]}";
			if ($is_zjmf) {
				$logic_run_map->saveMap($data_i, 0, 400, 5);
			}
			if (!$is_zjmf) {
				$logic_run_map->saveMap($data_i, 0, 300, 5);
			}
		}
	}
	public function unchangePrice($hid, $billingcycle, $currency_id)
	{
		$pid = \think\Db::name("host")->where("id", $hid)->value("productid");
		if ($billingcycle == "free") {
			return 0;
		}
		$price = \think\Db::name("pricing")->where("type", "product")->where("currency", $currency_id)->where("relid", $pid)->value($billingcycle);
		return intval($price);
	}
}