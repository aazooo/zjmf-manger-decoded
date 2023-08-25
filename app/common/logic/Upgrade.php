<?php

namespace app\common\logic;

class Upgrade
{
	public $lang;
	public function initialize()
	{
		$this->lang = get_system_langs();
	}
	public function judgeUpgradeConfig($hid, $type = "configoptions")
	{
		$upgrade_product = \think\Db::name("product_upgrade_products")->alias("a")->leftJoin("host b", "a.product_id = b.productid")->where("b.id", $hid)->find();
		if ($type == "product" && empty($upgrade_product)) {
			return false;
		}
		$host = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid = b.id")->where("a.id", $hid)->where("a.domainstatus", "Active")->find();
		if (empty($host)) {
			return false;
		}
		if ($type == "configoptions" && !$host["config_options_upgrade"]) {
			return false;
		}
		return true;
	}
	public function judgeUpgradeConfigError($hid, $type = "configoptions")
	{
		$upgrade_product = \think\Db::name("product_upgrade_products")->alias("a")->leftJoin("host b", "a.product_id = b.productid")->where("b.id", $hid)->find();
		if ($type == "product" && empty($upgrade_product)) {
			throw new \think\Exception("当前产品无法升级或降级可配置项");
		}
		$host = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid = b.id")->where("a.id", $hid)->where("a.domainstatus", "Active")->find();
		if (empty($host)) {
			throw new \think\Exception("产品未激活");
		}
		if ($type == "configoptions" && !$host["config_options_upgrade"]) {
			throw new \think\Exception("产品未开启配置项升级");
		}
		return true;
	}
	public function renewInvoices($hid)
	{
		$ids = \think\Db::name("invoices")->alias("a")->leftJoin("invoice_items b", "a.id = b.invoice_id")->where("b.rel_id", $hid)->where("b.type", "renew")->where("a.status", "Unpaid")->where("a.delete_time", 0)->count();
		return $ids >= 1 ? true : false;
	}
	public function deleteUpgradeInvoices($hid)
	{
		$ids = \think\Db::name("invoices")->alias("a")->leftJoin("invoice_items b", "a.id = b.invoice_id")->where("b.rel_id", $hid)->where("b.type", "renew")->where("a.status", "Unpaid")->where("a.delete_time", 0)->column("a.id");
		if (!empty($ids)) {
			\think\Db::name("invoices")->whereIn("id", $ids)->delete();
			\think\Db::name("invoice_items")->whereIn("invoice_id", $ids)->where("type", "renew")->delete();
		}
		$upgrades = \think\Db::name("upgrades")->alias("a")->field("a.id,a.order_id,d.id as invoice_id,a.status,d.status as invoice_status,a.amount,a.uid")->leftJoin("host b", "a.relid = b.id")->leftJoin("orders c", "a.order_id = c.id")->leftJoin("invoices d", "c.invoiceid = d.id")->where("a.relid", $hid)->where("d.type", "upgrade")->select()->toArray();
		foreach ($upgrades as $upgrade) {
			if ($upgrade["status"] != "Completed" || $upgrade["invoice_status"] != "Paid") {
				\think\Db::name("invoices")->where("id", $upgrade["invoice_id"])->delete();
				\think\Db::name("orders")->where("id", $upgrade["order_id"])->delete();
				\think\Db::name("upgrades")->where("id", $upgrade["id"])->delete();
				\think\Db::name("invoice_items")->where("invoice_id", $upgrade["invoice_id"])->where("rel_id", $upgrade["id"])->delete();
			}
			if ($upgrade["status"] != "Completed" && $upgrade["invoice_status"] == "Paid") {
				if ($upgrade["amount"] > 0) {
					\think\Db::name("clients")->where("id", $upgrade["uid"])->setInc("credit", $upgrade["amount"]);
					credit_log(["uid" => $upgrade["uid"], "desc" => "升级失败,退款至余额", "amount" => $upgrade["amount"], "relid" => $upgrade["invoice_id"]]);
				} else {
					$amount = -$upgrade["amount"];
					\think\Db::name("clients")->where("id", $upgrade["uid"])->setDec("credit", $amount);
					credit_log(["uid" => $upgrade["uid"], "desc" => "降级失败,扣除所退余额", "amount" => $upgrade["amount"], "relid" => $upgrade["invoice_id"]]);
				}
			}
		}
	}
	public function checkChange($hid, $configoptions)
	{
		$host_config = \think\Db::name("host_config_options")->field("configid,optionid,qty")->where("relid", $hid)->select()->toArray();
		$host_config_filter = [];
		foreach ($host_config as $v) {
			$options = \think\Db::name("product_config_options")->where("id", $v["configid"])->find();
			$option_type = $options["option_type"];
			if (judgeQuantity($option_type)) {
				$host_config_filter[$v["configid"]] = $v["qty"];
			} else {
				if (judgeYesNo($option_type) && $v["qty"] == 0) {
					continue;
				} else {
					$host_config_filter[$v["configid"]] = $v["optionid"];
				}
			}
		}
		if ($configoptions == $host_config_filter) {
			return false;
		}
		return true;
	}
	public function checkChangeText($hid, $configoptions)
	{
		$host_config = \think\Db::name("host_config_options")->field("configid,optionid,qty")->where("relid", $hid)->select()->toArray();
		$host_config_filter = [];
		$text = "主机配置修改 Host ID:" . $hid . " ";
		$texts = "";
		foreach ($host_config as $v) {
			$options = \think\Db::name("product_config_options")->where("id", $v["configid"])->find();
			$option_type = $options["option_type"];
			$name = array_pop(explode("|", $options["option_name"]));
			$value = $configoptions[$v["configid"]];
			if (judgeQuantity($option_type)) {
				if ($value != $v["qty"]) {
					$texts .= $name . "由 " . $v["qty"] . " 变更为" . $value . "、";
				}
			} else {
				if ($value != $v["optionid"]) {
					$option_name_old = \think\Db::name("product_config_options_sub")->where("id", $v["optionid"])->value("option_name");
					$option_name_old = array_pop(explode("|", $option_name_old));
					$option_name_new = \think\Db::name("product_config_options_sub")->where("id", $value)->value("option_name");
					$option_name_new = array_pop(explode("|", $option_name_new));
					$texts .= $name . "由 " . $option_name_old . " 变更为" . $option_name_new . "、";
				}
			}
		}
		$text .= empty($texts) ? "未修改" : $texts;
		return ["data" => $text];
	}
	/**
	 * 过滤要删除的配置项
	 * @param $conf
	 * @param $hid
	 */
	protected function filterDelConfigOptions($conf, $filter_conf, $hid, $admin = false)
	{
		$configid = \think\Db::name("host_config_options")->where("relid", $hid)->column("configid");
		if (!$configid) {
			return $conf;
		}
		if ($admin) {
			$upgrade_config_id = \think\Db::name("product_config_options")->whereIn("id", $configid)->select()->toArray();
		} else {
			$upgrade_config_id = \think\Db::name("product_config_options")->whereIn("id", $configid)->where("hidden", 0)->where("upgrade", 1)->select()->toArray();
		}
		if (!$upgrade_config_id) {
			return $conf;
		}
		foreach ($upgrade_config_id as $k => $val) {
			if (!isset($conf[$val["id"]]) && $val["option_type"] != 12 && $val["option_type"] != 5) {
				$filter_conf[$val["id"]] = 0;
				if ($val["option_type"] == 3) {
					$filter_conf[$val["id"]] = \think\Db::name("product_config_options_sub")->where("config_id", $val["id"])->order("sort_order asc")->value("id");
				} else {
					if (judgeQuantity($val["option_type"])) {
						$filter_conf[$val["id"]] = 0;
					} else {
						$filter_conf[$val["id"]] = \think\Db::name("product_config_options_sub")->where("config_id", $val["id"])->order("sort_order asc")->value("id");
					}
				}
			}
		}
		if ($filter_conf) {
			$data = \think\Db::name("product_config_options")->field("id,linkage_top_pid as pid")->whereIn("id", array_keys($filter_conf))->select()->toArray();
			$data = changeTwoArr(toTree($data));
			$new_filter_conf = [];
			foreach ($data as $v) {
				$new_filter_conf[$v["id"]] = $filter_conf[$v["id"]] ?? "";
			}
			$filter_conf = $new_filter_conf;
		}
		return $filter_conf;
	}
	public function filterConfigOptions($pid, $configoptions, $admin = false)
	{
		$allConfigArr = \think\Db::name("product_config_options")->alias("a")->field("a.*")->leftJoin("product_config_links b", "b.gid=a.gid")->where("b.pid", $pid)->where(function (\think\db\Query $query) use($admin, $configoptions) {
			if (!$admin) {
				$query->where("a.upgrade", 1);
				$query->where("hidden", 0);
				if ($configoptions) {
					$query->whereIn("a.id", array_keys($configoptions))->whereOr("a.option_type", 3);
				}
			}
		})->order("a.order", "asc")->select()->toArray();
		$configoptions_filter = [];
		if (!empty($allConfigArr[0])) {
			foreach ($allConfigArr as $k => $v) {
				$option_type = $v["option_type"];
				$config_id = $v["id"];
				if (judgeQuantity($option_type)) {
					$qty_minimum = $v["qty_minimum"];
					$qty_maximum = $v["qty_maximum"];
					if ($configoptions[$config_id]) {
						$qty = $configoptions[$config_id] < $qty_minimum ? $qty_minimum : $configoptions[$config_id];
						$qty = $qty_maximum < $qty ? $qty_maximum : $qty;
						$configoptions_filter[$v["id"]] = $qty;
					} else {
						$configoptions_filter[$v["id"]] = $qty_minimum;
					}
				} else {
					if ($option_type == 3) {
						$exists_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->where("id", $configoptions[$config_id])->find();
						if (!empty($exists_data)) {
							$configoptions_filter[$config_id] = $configoptions[$config_id];
						} else {
							$sub_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->order("sort_order asc")->find();
							if (!empty($sub_data)) {
								$configoptions_filter[$config_id] = $sub_data["id"];
							} else {
								$configoptions_filter[$config_id] = "";
							}
						}
					} else {
						if ($option_type != 5 || $admin) {
							if ($configoptions[$config_id]) {
								$exists_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->where("id", $configoptions[$config_id])->find();
								if (!empty($exists_data)) {
									$configoptions_filter[$config_id] = $configoptions[$config_id];
								} else {
									$sub_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->order("sort_order asc")->find();
									if (!empty($sub_data)) {
										$configoptions_filter[$config_id] = $sub_data["id"];
									}
								}
							} else {
								$sub_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->order("sort_order asc")->find();
								if (!empty($sub_data)) {
									$configoptions_filter[$config_id] = $sub_data["id"];
								}
							}
						}
					}
				}
			}
		}
		return $configoptions_filter;
	}
	protected function cycleToTime($billingcycle, $number = 1, $start = 0, $ontrial = "day", $other = false)
	{
		if ($other) {
			switch ($billingcycle) {
				case "ontrial":
					if ($ontrial == "day") {
						$cycletime = 86400 * $number;
					} else {
						$cycletime = 3600 * $number;
					}
					break;
				case "hour":
					$cycletime = 3600 * $number;
					break;
				case "day":
					$cycletime = 86400 * $number;
					break;
				case "monthly":
					$cycletime = 2592000;
					break;
				case "quarterly":
					$cycletime = 7776000;
					break;
				case "semiannually":
					$cycletime = 15552000;
					break;
				case "annually":
					$cycletime = 31104000;
					break;
				case "biennially":
					$cycletime = 62208000;
					break;
				case "triennially":
					$cycletime = 93312000;
					break;
				case "fourly":
					$cycletime = 124416000;
					break;
				case "fively":
					$cycletime = 155520000;
					break;
				case "sixly":
					$cycletime = 186624000;
					break;
				case "sevenly":
					$cycletime = 217728000;
					break;
				case "eightly":
					$cycletime = 248832000;
					break;
				case "ninely":
					$cycletime = 279936000;
					break;
				case "tenly":
					$cycletime = 311040000;
					break;
				default:
					$cycletime = 0;
					break;
			}
			return $cycletime;
		}
		switch ($billingcycle) {
			case "ontrial":
				if ($ontrial == "day") {
					$cycletime = 86400 * $number;
				} else {
					$cycletime = 3600 * $number;
				}
				break;
			case "hour":
				$cycletime = 3600 * $number;
				break;
			case "day":
				$cycletime = 86400 * $number;
				break;
			case "monthly":
				$cycletime = strtotime("1 month", $start) - $start;
				break;
			case "quarterly":
				$cycletime = strtotime("3 month", $start) - $start;
				break;
			case "semiannually":
				$cycletime = strtotime("6 month", $start) - $start;
				break;
			case "annually":
				$cycletime = strtotime("1 year", $start) - $start;
				break;
			case "biennially":
				$cycletime = strtotime("2 year", $start) - $start;
				break;
			case "triennially":
				$cycletime = strtotime("3 year", $start) - $start;
				break;
			case "fourly":
				$cycletime = strtotime("4 year", $start) - $start;
				break;
			case "fively":
				$cycletime = strtotime("5 year", $start) - $start;
				break;
			case "sixly":
				$cycletime = strtotime("6 year", $start) - $start;
				break;
			case "sevenly":
				$cycletime = strtotime("7 year", $start) - $start;
				break;
			case "eightly":
				$cycletime = strtotime("8 year", $start) - $start;
				break;
			case "ninely":
				$cycletime = strtotime("9 year", $start) - $start;
				break;
			case "tenly":
				$cycletime = strtotime("10 year", $start) - $start;
				break;
			default:
				$cycletime = 0;
				break;
		}
		return $cycletime;
	}
	public function upgradeConfigAdmin($hid, $configoptions)
	{
		$host = \think\Db::name("host")->alias("h")->field("h.uid,h.regdate,h.billingcycle,p.pay_type,h.firstpaymentamount,h.amount,h.domain,h.nextduedate,h.payment,p.id as pid,p.server_group")->leftJoin("products p", "h.productid = p.id")->where("h.id", $hid)->find();
		$old_paytype = json_decode($host["pay_type"], true);
		$billingcycle = $host["billingcycle"];
		$pid = $host["pid"];
		$duetime = $host["nextduedate"];
		$surplustime = $duetime - time();
		if ($old_paytype["pay_type"] != "free" && $old_paytype["pay_type"] != "onetime") {
			if ($surplustime < 0) {
				$surplustime = 0;
			}
		}
		$params = [];
		$configoptions_old = \think\Db::name("host_config_options")->where("relid", $hid)->select()->toArray();
		foreach ($configoptions_old as $v) {
			$option = \think\Db::name("product_config_options")->field("option_type,option_name")->where("upgrade", 1)->where("id", $v["configid"])->find();
			$suboption = \think\Db::name("product_config_options_sub")->field("option_name")->where("id", $v["optionid"])->find();
			if (!judgeQuantity($option["option_type"])) {
				$params["configoptions"][explode("|", $option["option_name"])[0] ?? $option["option_name"]] = explode("|", $suboption["option_name"])[0];
			} else {
				$params["configoptions"][explode("|", $option["option_name"])[0] ?? $option["option_name"]] = $v["qty"];
			}
		}
		$host_data_all = [];
		$base_configoptions = $configoptions;
		$configoptions = $this->filterConfigOptions($pid, $configoptions, true);
		$diff_configoptions = array_keys(array_diff($configoptions, $base_configoptions));
		$diff_configoptions_yes = array_keys($base_configoptions);
		$configoptions = $this->filterDelConfigOptions($base_configoptions, $configoptions, $hid, true);
		foreach ($configoptions as $k => $v) {
			$option = \think\Db::name("product_config_options")->where("id", $k)->find();
			$option_type = $option["option_type"];
			if (judgeQuantity($option_type)) {
				$suboptions = \think\Db::name("product_config_options_sub")->where("config_id", $k)->select()->toArray();
				foreach ($suboptions as $kk => $vv) {
					if ($v > 0 && $vv["qty_minimum"] <= $v && $v <= $vv["qty_maximum"]) {
						$host_config_data = ["relid" => $hid, "configid" => $k, "optionid" => $vv["id"], "qty" => $v];
						$host_data_all[] = $host_config_data;
					}
				}
			} else {
				if ($v) {
					if (judgeYesNo($option_type) && in_array($k, $diff_configoptions)) {
					} else {
						$qty = 0;
						if (judgeYesNo($option_type) && in_array($k, $diff_configoptions_yes)) {
							$qty = 1;
						}
						$host_config_data = ["relid" => $hid, "configid" => $k, "optionid" => $v, "qty" => $qty];
						$host_data_all[] = $host_config_data;
					}
				}
			}
		}
		\think\Db::startTrans();
		try {
			\think\Db::name("host_config_options")->where("relid", $hid)->delete();
			\think\Db::name("host_config_options")->insertAll($host_data_all);
			$host_logic = new Host();
			$res = $host_logic->changePackage($hid, $params, true);
			$logic_run_map = new RunMap();
			$model_host = new \app\common\model\HostModel();
			$data_i = [];
			$data_i["host_id"] = $hid;
			$data_i["active_type_param"] = [$hid, $params, 1, 0];
			$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
			if ($res["status"] == 200) {
				$data_i["description"] = " 后台配置 - 进行升降级 Host ID:{$data_i["host_id"]}的产品成功";
				if ($is_zjmf) {
					$logic_run_map->saveMap($data_i, 1, 400, 5);
				}
				if (!$is_zjmf) {
					$logic_run_map->saveMap($data_i, 1, 300, 5);
				}
			} else {
				$data_i["description"] = " 后台配置 - 进行升降级 Host ID:{$data_i["host_id"]}的产品失败：{$res["msg"]}";
				if ($is_zjmf) {
					$logic_run_map->saveMap($data_i, 0, 400, 5);
				}
				if (!$is_zjmf) {
					$logic_run_map->saveMap($data_i, 0, 300, 5);
				}
			}
			if ($res["status"] == 200) {
				\think\Db::commit();
				return ["status" => 200, "msg" => lang("SUCCESS MESSAGE")];
			} else {
				\think\Db::rollback();
				$module = \think\Db::name("server_groups")->where("id", $host["server_group"])->value("name");
				$module = $module ?: "";
				return ["status" => 400, "msg" => "模块{$module}错误:" . $res["msg"]];
			}
		} catch (\Exception $e) {
			\think\Db::rollback();
			return ["status" => 400, "msg" => lang("FAIL MESSAGE")];
		}
	}
	public function upgradeConfigProduct($hid, $configoptions)
	{
	}
	public function upgradeConfigCommon($hid, $configoptions, $currencyid, $admin = false, $promo_code = "", $payment = "", $checkout = false, $percent_value = "")
	{
		$gateways = array_column(gateway_list(), "name");
		if (!in_array($payment, $gateways)) {
			$payment = $gateways[0];
		}
		$data = [];
		$desc = "";
		$currency = (new Currencies())->getCurrencies("id,code,prefix,suffix", $currencyid)[0];
		$host = \think\Db::name("host")->alias("h")->field("concat(pg.name, \"-\" , p.name) as name,p.name as pname,h.uid,h.regdate,h.billingcycle,h.flag,
            p.pay_type,h.firstpaymentamount,h.amount,h.domain,h.nextduedate,h.payment,p.id as pid,p.down_configoption_refund,p.api_type,p.upstream_price_type,p.upstream_price_value,p.upstream_pid")->leftJoin("products p", "h.productid = p.id")->leftJoin("product_groups pg", "pg.id = p.gid")->where("h.id", $hid)->find();
		if ($host["api_type"] == "zjmf_api" && $host["upstream_pid"] > 0 && $host["upstream_price_type"] == "percent") {
			$is_zjmf_api = true;
		} else {
			$is_zjmf_api = false;
		}
		$uid = $host["uid"];
		if ($checkout && !$admin && request()->uid != $uid) {
			return ["status" => 400, "msg" => "非法操作"];
		}
		$regdate = $host["regdate"];
		$old_paytype = json_decode($host["pay_type"], true);
		$billingcycle = $host["billingcycle"];
		$product_name = $host["pname"];
		$pid = $host["pid"];
		$duetime = $host["nextduedate"];
		$surplustime = $duetime - time();
		if ($checkout && $old_paytype["pay_type"] != "free" && $old_paytype["pay_type"] != "onetime") {
			if ($surplustime < 0) {
				$surplustime = 0;
			}
		}
		if ($host["api_type"] == "resource" && function_exists("resourceUserGradePercent")) {
			$old_grade = \think\Db::name("host")->where("id", $hid)->value("agent_grade");
			$old_grade = bcdiv($old_grade, 100, 20);
			$grade = resourceUserGradePercent($uid, $pid);
			$grade = bcdiv($grade, 100, 20);
		}
		$base_configoptions = $configoptions;
		$configoptions = $this->filterConfigOptions($pid, $configoptions);
		$diff_configoptions = array_keys(array_diff($configoptions, $base_configoptions));
		$diff_configoptions_yes = array_keys($base_configoptions);
		$configoptions = $this->filterDelConfigOptions($base_configoptions, $configoptions, $hid);
		bcscale(20);
		$alloption = [];
		$diff_pricing = 0;
		$product_pricings = \think\Db::name("pricing")->where("type", "product")->where("currency", $currencyid)->where("relid", $pid)->find();
		$product_pricing = bcadd($product_pricings[$billingcycle], $product_pricings[config("price_type")[$billingcycle][1]], 2);
		$old_option_total = $new_option_total = $product_pricing > 0 ? $product_pricing : 0;
		foreach ($configoptions as $k => $v) {
			$options1 = \think\Db::name("product_config_options")->where("id", $k)->find();
			$option_type = $options1["option_type"];
			$desc .= "-" . $options1["option_name"] . "-";
			if (judgeQuantity($option_type)) {
				$old_sub = \think\Db::name("host_config_options")->field("optionid,qty")->where("relid", $hid)->where("configid", $k)->find();
				if (!empty($old_sub)) {
					$old_subid = $old_sub["optionid"];
					$old_sub_option = \think\Db::name("product_config_options_sub")->where("id", $old_subid)->value("option_name");
					$qty = $old_sub["qty"];
					if (judgeQuantityStage($option_type)) {
						$sum = quantityStagePrice($k, $currencyid, $qty, $billingcycle);
						$old_sub_pricing = $sum[0];
						$old_pricing_base = 0;
					} else {
						$old_pricing = \think\Db::name("pricing")->where("type", "configoptions")->where("currency", $currencyid)->where("relid", $old_subid)->find();
						$old_pricing_base = floatval($old_pricing[$billingcycle]);
						$old_sub_pricing = bcmul($old_pricing_base, $qty, 20);
					}
				} else {
					$qty = 0;
					$old_sub_option = "";
					$old_sub_pricing = 0;
					$old_pricing_base = 0;
				}
				$options = \think\Db::name("product_config_options_sub")->alias("pcos")->field("pco.id as oid,pcos.id as sub_id,pcos.option_name as suboption_name,pcos.qty_minimum,pcos.qty_maximum,pco.option_type,pco.option_name as option_name,pco.qty_minimum as min,pco.qty_maximum as max,pco.is_discount,p.*,pco.is_rebate,pco.qty_stage,pco.unit")->leftJoin("product_config_options pco", "pco.id = pcos.config_id")->leftJoin("pricing p", "p.relid = pcos.id")->where("pcos.config_id", $k)->where("p.type", "configoptions")->where("currency", $currencyid)->where("pcos.hidden", 0)->where("pco.hidden", 0)->where("pco.upgrade", 1)->select()->toArray();
				$option_filter = [];
				$diff_sub = $diff_sub_surplustime = 0;
				if (!empty($options[0])) {
					foreach ($options as $option) {
						$min = $option["qty_minimum"];
						$max = $option["qty_maximum"];
						if ($v >= 0 && $option["min"] <= $v && $v <= $option["max"] && $min <= $v && $v <= $max) {
							$new_sub_pricing = $option[$billingcycle] <= 0 ? 0 : $option[$billingcycle];
							if (judgeQuantityStage($option["option_type"])) {
								$sum = quantityStagePrice($option["oid"], $currencyid, $v, $billingcycle);
								$new_sub_pricing_total = $sum[0];
							} else {
								if ($qty <= $v) {
									$new_sub_pricing_total = bcmul($new_sub_pricing, $v - $qty) + $old_sub_pricing;
								} else {
									$new_sub_pricing_total = bcmul($old_pricing_base, $v - $qty) + $old_sub_pricing;
								}
							}
							if ($is_zjmf_api) {
								$old_sub_pricing = $old_sub_pricing * $host["upstream_price_value"] / 100;
								$new_sub_pricing_total = $new_sub_pricing_total * $host["upstream_price_value"] / 100;
							}
							if ($host["api_type"] == "resource") {
								$old_sub_pricing = $old_sub_pricing * $old_grade;
								$new_sub_pricing_total = $new_sub_pricing_total * $grade;
							}
							if (!empty($percent_value)) {
								$old_sub_pricing = $old_sub_pricing * $percent_value;
								$new_sub_pricing_total = $new_sub_pricing_total * $percent_value;
							}
							$diff_sub = bcsub($new_sub_pricing_total, $old_sub_pricing, 20);
							$old_option_total += $old_sub_pricing;
							$new_option_total += $new_sub_pricing_total;
							$option_filter["old_sub_pricing"] = $old_sub_pricing;
							$option_filter["old_sub_pricing_base"] = $old_sub_pricing;
							$option_filter["new_sub_pricing"] = $new_sub_pricing_total;
							$option_filter["new_sub_pricing_base"] = $new_sub_pricing_total;
							$option_filter["diff_sub"] = $diff_sub;
							if ($billingcycle == "onetime" || $billingcycle == "free") {
								$diff_sub_surplustime = $diff_sub;
							} else {
								$diff_sub_surplustime = bcmul($diff_sub, bcdiv($surplustime, $this->cycleToTime($billingcycle, $old_paytype["pay_{$billingcycle}_cycle"], $regdate, $old_paytype["pay_ontrial_cycle_type"])));
							}
							$diff_sub_surplustime = bcsub($diff_sub_surplustime, 0, 2);
							$suboption_name = $option["suboption_name"];
							$option_name = $option["option_name"];
							$option_filter["id"] = $option["oid"];
							$option_filter["option_name"] = explode("|", $option_name)[1] ? explode("|", $option_name)[1] : $option_name;
							$option_filter["suboption_name"] = explode("|", $suboption_name)[1] ? explode("|", $suboption_name)[1] : $suboption_name;
							$option_filter["old_qty"] = $qty . $option["unit"];
							$option_filter["qty"] = $v;
							$option_filter["option_type"] = $option["option_type"];
							$option_filter["is_discount"] = $option["is_discount"];
							$option_filter["suboption_price"] = $new_sub_pricing;
							$new_suboption_id = $option["sub_id"];
							$option_filter["suboption_id"] = $new_suboption_id;
							$option_filter["diff_sub_pricing"] = $diff_sub_surplustime;
							$option_filter["old_suboption_id"] = $old_subid;
							$option_filter["is_rebate"] = $option["is_rebate"];
							$option_filter["qty_stage"] = $option["qty_stage"];
							$option_filter["unit"] = $option["unit"];
							$option_filter["oid"] = $option["oid"];
							break;
						}
					}
				}
				if (!empty($option_filter)) {
					if ($v != $qty) {
						$paid = $diff_sub_surplustime <= 0 ? "Y" : "N";
						$upgrade_item = ["original_value" => $k . "=>" . $old_subid . ":" . $qty, "new_value" => $new_suboption_id . ":" . $v, "recurring_change" => $diff_sub, "paid" => $paid, "amountdue" => $diff_sub_surplustime, "invoices_item_description" => lang("OPTIONS_UPGRADE") . ":" . $product_name . "(" . $host["domain"] . ")" . "\n" . $option_filter["option_name"] . "数量:" . $qty . " => " . $v, "upgrade_description" => $option_name . ":" . $old_sub_option . "(" . $qty . ")=>" . $suboption_name . "(" . $v . ")"];
						$option_filter["upgrade_item"] = $upgrade_item;
						$diff_pricing = bcadd($diff_pricing, $diff_sub_surplustime, 2);
						$alloption[] = $option_filter;
					} else {
						$option_filter["upgrade_item"] = [];
					}
				}
			} else {
				$old_sub = \think\Db::name("host_config_options")->field("optionid,qty")->where("relid", $hid)->where("configid", $k)->find();
				if (!empty($old_sub)) {
					$old_subid = $old_sub["optionid"];
					$old_sub_option = \think\Db::name("product_config_options_sub")->where("id", $old_subid)->value("option_name");
					$old_pricing = \think\Db::name("pricing")->where("type", "configoptions")->where("currency", $currencyid)->where("relid", $old_subid)->find();
					$old_sub_pricing = floatval($old_pricing[$billingcycle]);
					$is_yes = false;
					if (judgeYesNo($option_type) && in_array($k, $diff_configoptions_yes) && $old_sub["qty"] == 0) {
						$old_sub_pricing = 0;
						$is_yes = true;
					}
				} else {
					$old_sub_option = "";
					$old_sub_pricing = 0;
				}
				if ($v) {
					$option = \think\Db::name("product_config_options_sub")->alias("pcos")->field("pco.id as oid,pcos.option_name as suboption_name,pco.option_type,pco.option_name as option_name,pcos.id as suboption_id,pco.is_discount,p.*,pco.is_rebate,pco.qty_stage,pco.unit")->leftJoin("product_config_options pco", "pco.id = pcos.config_id")->leftJoin("pricing p", "p.relid = pcos.id")->where("pcos.id", $v)->where("pcos.config_id", $k)->where("p.type", "configoptions")->where("p.currency", $currencyid)->where("pcos.hidden", 0)->where("pco.hidden", 0)->where("pco.upgrade", 1)->find();
					if ($option) {
						$new_sub_pricing = $option[$billingcycle] <= 0 ? 0 : $option[$billingcycle];
						$is_no = false;
						if (judgeYesNo($option_type) && in_array($k, $diff_configoptions) && $old_sub["qty"] == 1) {
							$new_sub_pricing = 0;
							$is_no = true;
						}
						if ($is_zjmf_api) {
							$old_sub_pricing = $old_sub_pricing * $host["upstream_price_value"] / 100;
							$new_sub_pricing = $new_sub_pricing * $host["upstream_price_value"] / 100;
						}
						if ($host["api_type"] == "resource") {
							$old_sub_pricing = $old_sub_pricing * $old_grade;
							$new_sub_pricing = $new_sub_pricing * $grade;
						}
						if (!empty($percent_value)) {
							$old_sub_pricing = $old_sub_pricing * $percent_value;
							$new_sub_pricing = $new_sub_pricing * $percent_value;
						}
						$diff_sub = bcsub($new_sub_pricing, $old_sub_pricing, 2);
						$old_option_total += $old_sub_pricing;
						$new_option_total += $new_sub_pricing;
						$option["old_sub_pricing"] = $old_sub_pricing;
						$option["old_sub_pricing_base"] = $old_sub_pricing;
						$option["new_sub_pricing"] = $new_sub_pricing;
						$option["new_sub_pricing_base"] = $new_sub_pricing;
						$option["diff_sub"] = $diff_sub;
						if ($billingcycle == "onetime" || $billingcycle == "free") {
							$diff_sub_surplustime = $diff_sub;
						} else {
							$diff_sub_surplustime = bcmul($diff_sub, bcdiv($surplustime, $this->cycleToTime($billingcycle, $old_paytype["pay_{$billingcycle}_cycle"], $regdate, $old_paytype["pay_ontrial_cycle_type"])));
						}
						$diff_sub_surplustime = bcsub($diff_sub_surplustime, 0, 2);
						$suboption_name = $option["suboption_name"];
						$suboption_name = explode("|", $suboption_name)[1] ? explode("|", $suboption_name)[1] : $suboption_name;
						$option["suboption_name"] = $is_no ? "否" : (judgeYesNo($option_type) ? "是" : implode(" ", explode("^", $suboption_name)));
						$option_name = $option["option_name"];
						$option_name = explode("|", $option_name)[1] ? explode("|", $option_name)[1] : $option_name;
						$option["option_name"] = implode(" ", explode("^", $option_name));
						$option["diff_sub_pricing"] = $diff_sub_surplustime;
						$option["old_suboption_id"] = $old_subid;
						$option["suboption_price"] = $new_sub_pricing;
						unset($option[$billingcycle]);
						if ($v != $old_subid || $is_no || $is_yes) {
							$paid = $diff_sub_surplustime <= 0 ? "Y" : "N";
							$upgrade_item = ["original_value" => $k . "=>" . $old_subid, "new_value" => judgeYesNo($option_type) ? $v . ":" . ($is_yes ? 1 : 0) : $v, "recurring_change" => $diff_sub, "paid" => $paid, "amountdue" => $diff_sub_surplustime, "invoices_item_description" => lang("OPTIONS_UPGRADE") . ":" . $product_name . "\n" . $option["option_name"] . ":" . $old_sub_option . "(" . $old_subid . ") => " . $suboption_name . "(" . $v . ")", "upgrade_description" => $option_name . ":" . $old_sub_option . "=>" . $suboption_name];
							$option["upgrade_item"] = $upgrade_item;
							$old_sub_option = explode("|", $old_sub_option)[1] ? explode("|", $old_sub_option)[1] : $old_sub_option;
							$option["old_suboption_name"] = $is_yes ? "否" : (judgeYesNo($option_type) ? "是" : implode(" ", explode("^", $old_sub_option)));
							$diff_pricing = bcadd($diff_pricing, $diff_sub_surplustime, 2);
						} else {
							$option["upgrade_item"] = [];
						}
						$alloption[] = $option;
					}
				} else {
					$diff_sub = bcsub(0, $old_sub_pricing, 2);
					if ($billingcycle == "onetime" || $billingcycle == "free") {
						$diff_sub_surplustime = $diff_sub;
					} else {
						$diff_sub_surplustime = bcmul($diff_sub, bcdiv($surplustime, $this->cycleToTime($billingcycle, $old_paytype["pay_{$billingcycle}_cycle"], $regdate, $old_paytype["pay_ontrial_cycle_type"])));
					}
					$tmp = \think\Db::name("product_config_options")->field("option_type,option_name,is_discount,is_rebate,qty_stage,unit")->where("id", $k)->find();
					$option_name = $tmp["option_name"];
					$option_name = explode("|", $option_name)[1] ? explode("|", $option_name)[1] : $option_name;
					$option = [];
					$option["old_sub_pricing"] = $old_sub_pricing;
					$option["old_sub_pricing_base"] = $old_sub_pricing;
					$option["new_sub_pricing"] = 0;
					$option["new_sub_pricing_base"] = 0;
					$option["diff_sub"] = 0;
					$option["id"] = $k;
					$option["option_type"] = $tmp["option_type"];
					$option["option_name"] = implode(" ", explode("^", $option_name)) ?? "";
					$option["suboption_name"] = "";
					$option["suboption_id"] = "";
					$option["diff_sub_pricing"] = $diff_sub_surplustime;
					$option["old_suboption_id"] = $old_subid ?? "";
					$option["suboption_price"] = number_format(0, 2);
					$option["is_discount"] = $tmp["is_discount"];
					$option["is_rebate"] = $tmp["is_rebate"];
					$option["qty_stage"] = $tmp["qty_stage"];
					$option["unit"] = $tmp["unit"];
					$upgrade_item = ["original_value" => $k . "=>" . $old_subid, "new_value" => $v, "recurring_change" => 0, "paid" => "Y", "amountdue" => 0, "invoices_item_description" => lang("OPTIONS_UPGRADE") . ":" . $product_name . "\n" . $option["option_name"] . ":" . $old_sub_option . "(" . $old_subid . ") => " . $option["suboption_name"] . "(" . $v . ")", "upgrade_description" => $option_name . ":" . $old_sub_option . "=>" . $option["suboption_name"]];
					$option["upgrade_item"] = $upgrade_item;
					$option["old_suboption_name"] = $old_sub_option;
					$alloption[] = $option;
				}
			}
		}
		$edition = getEdition();
		$flag = getSaleProductUser($host["pid"], $uid);
		$saleproducts = 0;
		if ($flag) {
			foreach ($alloption as $km => $m) {
				if (!$m["is_rebate"] && $edition) {
					continue;
				}
				if (judgeQuantity($m["option_type"]) && intval($m["old_qty"]) != intval($m["qty"]) || !judgeQuantity($m["option_type"]) && $m["old_suboption_id"] != $m["suboption_id"]) {
					if ($flag["type"] == 1) {
						$bates = $flag["bates"] / 100;
						if ($m["is_rebate"] || !$edition) {
							$old_sub_pricing_sale = $m["old_sub_pricing"] * $bates;
							$old_sub_pricing_sale_base = $m["old_sub_pricing_base"];
							$new_sub_pricing_sale = $m["new_sub_pricing"] * $bates;
							$new_sub_pricing_sale_base = $m["new_sub_pricing_base"];
						} else {
							$old_sub_pricing_sale = $m["old_sub_pricing_base"];
							$old_sub_pricing_sale_base = $m["old_sub_pricing_base"];
							$new_sub_pricing_sale_base = $m["new_sub_pricing_base"];
							$new_sub_pricing_sale = 0;
						}
					} else {
						if ($m["is_rebate"] || !$edition) {
							$bates = $flag["bates"] > 0 ? $flag["bates"] : 0;
							if ($old_option_total < $bates && $bates < $new_option_total) {
								$old_sub_pricing_sale = 0;
								$old_sub_pricing_sale_base = 0;
								$new_sub_pricing_sale = $m["new_sub_pricing"] - $bates * $m["new_sub_pricing"] / $new_option_total;
								$new_sub_pricing_sale_base = $m["new_sub_pricing_base"] - $bates * $m["new_sub_pricing_base"] / $new_option_total;
							} else {
								if ($new_option_total < $bates) {
									$old_sub_pricing_sale = 0;
									$new_sub_pricing_sale = 0;
								} else {
									$old_sub_pricing_sale = $m["old_sub_pricing"] - $bates * $m["old_sub_pricing"] / $old_option_total;
									$old_sub_pricing_sale_base = $m["old_sub_pricing_base"];
									$new_sub_pricing_sale = $m["new_sub_pricing"] - $bates * $m["new_sub_pricing"] / $new_option_total;
									$new_sub_pricing_sale_base = $m["new_sub_pricing_base"];
								}
							}
						} else {
							$old_sub_pricing_sale = $m["old_sub_pricing_base"];
							$new_sub_pricing_sale_base = $m["new_sub_pricing_base"];
							$new_sub_pricing_sale = 0;
						}
					}
					$diff_sub_sale = bcsub($new_sub_pricing_sale, $old_sub_pricing_sale, 2);
					$diff_sub = $m["diff_sub"] ?? 0;
					$diff = $diff_sub - $diff_sub_sale;
					if ($billingcycle == "onetime" || $billingcycle == "free") {
						$diff_sub_surplustime = $diff_sub_sale;
					} else {
						$diff_sub_surplustime = bcmul($diff_sub_sale, bcdiv($surplustime, $this->cycleToTime($billingcycle, $old_paytype["pay_{$billingcycle}_cycle"], $regdate, $old_paytype["pay_ontrial_cycle_type"])));
						$diff = bcmul($diff, bcdiv($surplustime, $this->cycleToTime($billingcycle, $old_paytype["pay_{$billingcycle}_cycle"], $regdate, $old_paytype["pay_ontrial_cycle_type"])));
					}
					$saleproducts += $diff;
					$diff_sub_surplustime = bcsub($diff_sub_surplustime, 0, 2);
					$alloption[$km]["diff_sub_pricing"] = $diff_sub_surplustime;
					$alloption[$km]["old_sub_pricing"] = $old_sub_pricing_sale;
					$alloption[$km]["old_sub_pricing_base"] = $old_sub_pricing_sale_base;
					$alloption[$km]["new_sub_pricing"] = $new_sub_pricing_sale;
					$alloption[$km]["new_sub_pricing_base"] = $new_sub_pricing_sale_base;
					if (isset($m["upgrade_item"]["recurring_change"])) {
						$alloption[$km]["upgrade_item"]["recurring_change"] = $diff_sub_sale;
					}
				}
			}
		}
		$diff_pricing = bcsub($diff_pricing, $saleproducts, 2);
		$promoqualifies = $old_promoqualifies = false;
		$total = $discount_total = 0;
		$host_promo = \think\Db::name("host")->alias("a")->field("b.code")->leftJoin("promo_code b", "a.promoid = b.id")->where("a.id", $hid)->find();
		$old_promo = $host_promo["code"] ?: "";
		if ($old_promo) {
			$res1 = $this->checkUpgradePromo($old_promo, $hid, "", "", "option", true);
			if ($res1["status"] == 200) {
				$old_upgrade_value = $res1["data"]["value"];
				$old_upgrade_value_type = $res1["data"]["type"];
				$old_promo_data = $res1["data"];
				$old_promoqualifies = true;
				if (!empty($alloption)) {
					foreach ($alloption as &$v6) {
						if ($v6["is_discount"]) {
							if (judgeQuantity($v6["option_type"]) && intval($v6["old_qty"]) != intval($v6["qty"]) || !judgeQuantity($v6["option_type"]) && $v6["old_suboption_id"] != $v6["suboption_id"]) {
								if ($old_upgrade_value_type == "percent") {
									$v6["old_sub_pricing"] = $v6["old_sub_pricing"] * $old_upgrade_value / 100;
								} elseif ($old_upgrade_value_type == "fixed") {
									$v6["old_sub_pricing"] = $v6["old_sub_pricing"] - $old_upgrade_value > 0 ? $v6["old_sub_pricing"] - $old_upgrade_value : 0;
								}
							}
						}
						if ($old_promo_data) {
							if (isset($v6["upgrade_item"]["recurring_change"])) {
								$v6["upgrade_item"]["recurring_change"] = bcsub($v6["new_sub_pricing"], $v6["old_sub_pricing"], 2);
							}
						}
					}
				}
			}
		}
		if ($promo_code) {
			$result = $this->checkUpgradePromo($promo_code, $hid);
			if ($result["status"] == 200) {
				$promo = $result["data"];
				$upgrade_value = $promo["value"] >= 100 ? 100 : ($promo["value"] > 0 ? $promo["value"] : 0);
				$upgrade_value_type = $promo["type"];
				$promoqualifies = true;
				if (!empty($alloption)) {
					foreach ($alloption as &$v7) {
						if ($v7["is_discount"]) {
							if (judgeQuantity($v7["option_type"]) && intval($v7["old_qty"]) != intval($v7["qty"]) || !judgeQuantity($v7["option_type"]) && $v7["old_suboption_id"] != $v7["suboption_id"]) {
								if ($upgrade_value_type == "percent") {
									$v7["new_promo_price"] = $v7["new_sub_pricing"] * (1 - $upgrade_value / 100);
									$v7["new_sub_pricing"] = $v7["new_sub_pricing"] * $upgrade_value / 100;
								} else {
									if ($upgrade_value_type = "fixed") {
										$v7["new_promo_price"] = bcsub($v7["new_sub_pricing"], $upgrade_value) <= 0 ? $v7["new_sub_pricing"] : $upgrade_value;
										$v7["new_sub_pricing"] = bcsub($v7["new_sub_pricing"], $upgrade_value) <= 0 ? 0 : bcsub($v7["new_sub_pricing"], $upgrade_value);
									}
								}
							}
						}
						if ($promo && $promo["recurring"] == 1) {
							if (isset($v7["upgrade_item"]["recurring_change"])) {
								$v7["upgrade_item"]["recurring_change"] = bcsub($v7["new_sub_pricing"], $v7["old_sub_pricing"], 2);
							}
						}
					}
				}
			}
		}
		if ($promoqualifies || $old_promoqualifies) {
			if (!empty($alloption)) {
				foreach ($alloption as $k3 => $v3) {
					$sub_discount = $v3["new_sub_pricing"] - $v3["old_sub_pricing"];
					if ($billingcycle == "onetime" || $billingcycle == "free") {
					} else {
						$alloption[$k3]["new_promo_price"] = bcmul($v3["new_promo_price"], bcdiv($surplustime, $this->cycleToTime($billingcycle, $old_paytype["pay_{$billingcycle}_cycle"], $regdate, $old_paytype["pay_ontrial_cycle_type"])));
						$sub_discount = bcmul($sub_discount, bcdiv($surplustime, $this->cycleToTime($billingcycle, $old_paytype["pay_{$billingcycle}_cycle"], $regdate, $old_paytype["pay_ontrial_cycle_type"])));
					}
					$total = bcadd($total, $sub_discount);
					$discount_total = bcadd($discount_total, $alloption[$k3]["new_promo_price"]);
					$res_is_discount = \think\Db::name("product_config_options")->where("id", $v3["oid"])->where("is_discount", 1)->value("id");
					if ($res_is_discount) {
						if (($promoqualifies || $old_promoqualifies) && !empty($v3["upgrade_item"])) {
							$alloption[$k3]["upgrade_item"]["amountdue"] = $sub_discount;
							$alloption[$k3]["upgrade_item"]["amount"] = $v3["suboption_price"] - $alloption[$k3]["new_promo_price"];
							$alloption[$k3]["upgrade_item"]["item_discount"] = $alloption[$k3]["new_promo_price"];
							$alloption[$k3]["upgrade_item"]["discount_description"] = $v3["option_name"] . lang("OPTIONS_UPGRADE_USE_PROMO") . ":" . $promo_code;
						}
					}
				}
			}
		} else {
			$total = $diff_pricing;
		}
		$alloption_filter = [];
		$new_description = "配置项降级:";
		foreach ($alloption as $kk => $vv) {
			$vv["old_sub_pricing"] = bcsub($vv["old_sub_pricing"], 0, 2);
			$vv["new_sub_pricing"] = bcsub($vv["new_sub_pricing"], 0, 2);
			$vv["old_sub_pricing_base"] = bcsub($vv["old_sub_pricing_base"], 0, 2);
			$vv["new_sub_pricing_base"] = bcsub($vv["new_sub_pricing_base"], 0, 2);
			if (!empty($vv["upgrade_item"])) {
				$vv["qty"] = $vv["qty"] . $vv["unit"];
				$alloption_filter[] = $vv;
				$new_description .= $vv["upgrade_item"]["upgrade_description"] . "\n";
			}
		}
		$data["currency"] = $currency;
		$data["name"] = $host["name"] . "(" . $host["domain"] . ")";
		$data["payment"] = $host["payment"];
		$data["flag"] = $host["flag"];
		$data["gateway"] = gateway_list();
		$saleproducts = $saleproducts > 0 ? bcsub($saleproducts, 0, 2) : 0;
		$data["saleproducts"] = $saleproducts;
		$data["discount"] = bcsub($discount_total, 0, 2);
		$subtotal = bcadd($diff_pricing, $saleproducts, 2);
		$total = bcsub($total, 0, 2);
		$data["subtotal"] = $subtotal;
		$data["total"] = $total;
		$data["promo_code"] = $promo_code ?: "";
		$data["configoptions"] = $configoptions;
		$data["alloption"] = $alloption_filter;
		$data["billingcycle"] = $billingcycle;
		$data["billingcycle_zh"] = config("billing_cycle")[$billingcycle];
		$data["has_renew"] = $this->renewInvoices($hid);
		$desc .= "周期:" . $data["billingcycle_zh"];
		if ($checkout) {
			\think\Db::startTrans();
			try {
				$this->deleteUpgradeInvoices($hid);
				if ($promo_code && !$promoqualifies) {
					$promo_code = "";
				}
				if ($promo_code && $promoqualifies) {
					$upgradepromo = \think\Db::name("promo_code")->field("upgrade_config")->where("code", $promo_code)->find();
					$upgradeconfig = unserialize($upgradepromo["upgrade_config"]);
					$promotype = $upgradeconfig["upgrade_value_type"] ?? "";
					$promovalue = $upgradeconfig["upgrade_value"] ?? "";
					\think\Db::name("promo_code")->where("code", $promo_code)->setInc("used");
				} else {
					$promotype = "";
					$promovalue = "";
				}
				$order_data = ["uid" => $uid, "ordernum" => cmf_get_order_sn(), "status" => "Pending", "create_time" => time(), "amount" => $total <= 0 ? 0 : $total, "promo_code" => $promo_code ?? "", "promo_type" => $promotype, "promo_value" => $promovalue, "payment" => $payment];
				$invoice_data = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "subtotal" => $total <= 0 ? 0 : $total, "credit" => "", "total" => $total <= 0 ? 0 : $total, "status" => $total <= 0 ? "Paid" : "Unpaid", "payment" => $payment, "type" => "upgrade", "url" => "servicedetail?id=" . $hid];
				if ($total > 0) {
					$invoice_id = \think\Db::name("invoices")->insertGetId($invoice_data);
				} else {
					$invoice_id = 0;
				}
				$order_data["invoiceid"] = $invoice_id;
				$orderid = \think\Db::name("orders")->insertGetId($order_data);
				$upgrade_ids = [];
				foreach ($alloption_filter as $kkk => $vvv) {
					$upgrade_item_tmp = $vvv["upgrade_item"];
					if (!empty($upgrade_item_tmp)) {
						$upgrade_data = ["uid" => $uid, "order_id" => $orderid, "type" => "configoptions", "date" => time(), "relid" => $hid, "original_value" => $upgrade_item_tmp["original_value"], "new_value" => $upgrade_item_tmp["new_value"], "new_cycle" => "", "amount" => $upgrade_item_tmp["amountdue"], "credit_amount" => "", "days_remaining" => "", "total_days_in_cycle" => "", "new_recurring_amount" => "", "recurring_change" => $upgrade_item_tmp["recurring_change"], "status" => "Pending", "paid" => $upgrade_item_tmp["paid"], "description" => $host["name"] . "(" . $host["domain"] . ")" . "\n" . $upgrade_item_tmp["upgrade_description"]];
						$upgrade_id = \think\Db::name("upgrades")->insertGetId($upgrade_data);
						$upgrade_ids[] = $upgrade_id;
						$invoice_items_data = [];
						if ($upgrade_item_tmp["amountdue"] >= 0 && $invoice_id) {
							$invoice_items_data[] = ["invoice_id" => $invoice_id, "uid" => $uid, "type" => "upgrade", "rel_id" => intval($upgrade_id), "description" => $upgrade_item_tmp["invoices_item_description"], "amount" => $upgrade_item_tmp["amountdue"], "due_time" => time(), "payment" => $payment];
							if ($upgrade_item_tmp["item_discount"] && $upgrade_item_tmp["item_discount"] >= 0 && $diff_pricing >= 0) {
								$invoice_items_data[] = ["invoice_id" => $invoice_id, "uid" => $uid, "type" => "promo", "rel_id" => intval($upgrade_id), "description" => $upgrade_item_tmp["discount_description"] ?? "", "amount" => -$upgrade_item_tmp["item_discount"], "due_time" => time(), "payment" => $payment];
							}
						}
						if (!empty($invoice_items_data[0])) {
							\think\Db::name("invoice_items")->insertAll($invoice_items_data);
						}
					}
				}
				if ($flag && !empty($upgrade_ids[0])) {
					$invoice_items_data = ["invoice_id" => $invoice_id, "uid" => $uid, "type" => "discount", "rel_id" => intval($upgrade_ids[0]), "description" => "客戶折扣", "amount" => -$data["saleproducts"], "due_time" => time(), "payment" => $payment];
					if (!empty($invoice_items_data)) {
						\think\Db::name("invoice_items")->insert($invoice_items_data);
					}
				}
				$str = "";
				if ($total > 0) {
					$str .= " 可配置项升级成功";
				} else {
					$str .= " 可配置项降级成功";
				}
				active_log_final(sprintf("%s#User ID:%d - #Host ID:%d", $str, $uid, $hid), $uid, 2, $hid);
				\think\Db::commit();
				$res = ["status" => 200, "msg" => "结算成功"];
			} catch (\Exception $e) {
				\think\Db::rollback();
				$res = ["status" => 400, "msg" => lang("CHECKOUT FAIL") . $e->getMessage()];
			}
			if ($res["status"] != 200) {
				return $res;
			}
			if ($total <= 0) {
				$credit_refund = -$total;
				if (configuration("upgrade_down_product_config") && $credit_refund > 0) {
					\think\Db::startTrans();
					try {
						$invoice_refund = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "subtotal" => $credit_refund, "credit" => "", "total" => $credit_refund, "status" => "Refunded", "payment" => $payment, "type" => "upgrade", "url" => "servicedetail?id=" . $hid];
						$invoice_refund_id = \think\Db::name("invoices")->insertGetId($invoice_refund);
						$account1 = ["uid" => $uid, "currency" => $currency, "gateway" => $payment, "create_time" => time(), "pay_time" => time(), "amount_in" => $credit_refund, "fees" => "", "amount_out" => 0, "rate" => 1, "trans_id" => "", "invoice_id" => $invoice_refund_id, "refund" => 0, "description" => "产品'{$product_name}'降级退款,充值至余额"];
						$aid = \think\Db::name("accounts")->insertGetId($account1);
						$account2 = ["uid" => $uid, "currency" => $currency, "gateway" => $payment, "create_time" => time(), "pay_time" => time(), "amount_in" => 0, "fees" => "", "amount_out" => $credit_refund, "rate" => 1, "trans_id" => "", "invoice_id" => $invoice_refund_id, "refund" => $aid, "description" => "产品'{$product_name}'降级退款"];
						\think\Db::name("accounts")->insert($account2);
						$invoice_refund_item = ["invoice_id" => $invoice_refund_id, "uid" => $uid, "type" => "upgrade", "rel_id" => 0, "description" => $new_description, "amount" => $credit_refund, "due_time" => time(), "payment" => $payment];
						\think\Db::name("invoice_items")->insert($invoice_refund_item);
						\think\Db::name("clients")->where("id", $uid)->setInc("credit", $credit_refund);
						credit_log(["uid" => $uid, "desc" => $credit_refund > 0 ? "降级退款至余额" : "减少余额", "amount" => $credit_refund, "relid" => $invoice_id]);
						\think\Db::commit();
					} catch (\Exception $e) {
						\think\Db::rollback();
					}
				} else {
					$invoice_refund = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "subtotal" => 0, "credit" => "", "total" => 0, "status" => $total == 0 ? "Paid" : "Refunded", "payment" => $payment, "type" => "upgrade", "url" => "servicedetail?id=" . $hid];
					$invoice_refund_id = \think\Db::name("invoices")->insertGetId($invoice_refund);
					$invoice_refund_item = ["invoice_id" => $invoice_refund_id, "uid" => $uid, "type" => "upgrade", "rel_id" => 0, "description" => $new_description, "amount" => 0, "due_time" => time(), "payment" => $payment];
					\think\Db::name("invoice_items")->insert($invoice_refund_item);
				}
				foreach ($upgrade_ids as $upgrade_id) {
					$this->doUpgrade($upgrade_id);
				}
				return ["status" => 1001, "msg" => lang("BUY SUCCESS"), "data" => ["orderid" => $orderid]];
			} else {
				$response_data["invoiceid"] = $invoice_id;
				$response_data["orderid"] = $orderid;
				return ["status" => 200, "data" => $response_data];
			}
		}
		return ["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data];
	}
	public function checkUpgradePromo($promo_code, $hid, $new_pid = "", $new_billingcycle = "", $type = "option", $orgin = false)
	{
		if (empty($promo_code)) {
			return ["status" => 400, "msg" => lang("未检测到优惠码")];
		}
		$host = \think\Db::name("host")->alias("h")->field("h.uid,h.billingcycle,p.id as pid")->leftJoin("products p", "h.productid = p.id")->leftJoin("product_groups pg", "pg.id = p.gid")->where("h.id", $hid)->find();
		$pid = $host["pid"];
		$billingcycle = $host["billingcycle"];
		$uid = request()->uid;
		if (!$uid) {
			return ["status" => 400, "msg" => lang("PLEASE_LOGIN_AGAIN")];
		}
		$promo = \think\Db::name("promo_code")->where("code", $promo_code)->find();
		if (empty($promo)) {
			return ["status" => 400, "msg" => lang("PROMO_CODE_NO_EXIST")];
		}
		if ($promo["start_time"] && time() < $promo["start_time"]) {
			return ["status" => 400, "msg" => lang("PROMO_CODE_HAS_NOT_START")];
		}
		if ($promo["expiration_time"] != "0" && time() > $promo["expiration_time"]) {
			return ["status" => 400, "msg" => lang("PROMO_CODE_HAS_EXPIRED")];
		}
		if ($promo["max_times"] > 0 && $promo["used"] >= $promo["max_times"]) {
			return ["status" => 400, "msg" => lang("PROMO_CODE_HAS_EXCEEDING_MAX_TIMES")];
		}
		if ($promo["once_per_client"]) {
			$promocount = \think\Db::name("orders")->where("status", "Active")->where("uid", $uid)->where("promo_code", $promo_code)->count("id");
			if ($promocount > 0) {
				return ["status" => 400, "msg" => lang("PROMO_CODE_ONLY_ONCE_FOR_EVERYONE")];
			}
		}
		$appliesto = explode(",", $promo["appliesto"]);
		if (!empty($new_pid)) {
			if (count($appliesto) && $appliesto[0] && !in_array($new_pid, $appliesto)) {
				return ["status" => 400, "msg" => lang("PROMO_CODE_INAPPLICABILITY_PRODUCT")];
			}
			$requires = explode(",", $promo["requires"]);
			if (count($requires) && $requires[0] && !in_array($pid, $requires)) {
				return ["status" => 400, "msg" => lang("PROMO_CODE_REQUIRE_PRODUCT")];
			}
		} else {
			if (count($appliesto) && $appliesto[0] && !in_array($pid, $appliesto)) {
				return ["status" => 400, "msg" => lang("PROMO_CODE_INAPPLICABILITY_PRODUCT")];
			}
		}
		$cycle = explode(",", $promo["cycles"]);
		if (!empty($new_billingcycle)) {
			if (count($cycle) && $cycle[0] && !in_array($new_billingcycle, $cycle)) {
				return ["status" => 400, "msg" => lang("PROMO_CODE_CAN_NOT_APPLY_BILLINGCYLCE")];
			}
		} else {
			if (count($cycle) && $cycle[0] && !in_array($billingcycle, $cycle)) {
				return ["status" => 400, "msg" => lang("PROMO_CODE_CAN_NOT_APPLY_BILLINGCYLCE")];
			}
		}
		if ($type == "option") {
			if ($promo["lifelong"] == 0 && !$orgin) {
				return ["status" => 400, "msg" => lang("PROMO_CODE_CAN_NOT_UPGRADS_OPTIONS")];
			}
		}
		if ($type == "product") {
			if (empty($appliesto[0])) {
				return ["status" => 400, "msg" => lang("PROMO_CODE_CAN_NOT_UPGRADS_PRODUCT")];
			}
		}
		return ["status" => 200, "data" => $promo];
	}
	public function doUpgrade($upgrade_id)
	{
		$upgrade = \think\Db::name("upgrades")->where("id", $upgrade_id)->find();
		\think\Db::name("upgrades")->where("id", $upgrade_id)->update(["paid" => "Y"]);
		if (!empty($upgrade) && $upgrade["status"] != "Completed") {
			$hid = $upgrade["relid"];
			$params = [];
			$configoptions = \think\Db::name("host_config_options")->where("relid", $hid)->select()->toArray();
			foreach ($configoptions as $v) {
				$option = \think\Db::name("product_config_options")->field("option_type,option_name")->where("id", $v["configid"])->find();
				$suboption = \think\Db::name("product_config_options_sub")->field("option_name")->where("id", $v["optionid"])->find();
				if (!judgeQuantity($option["option_type"])) {
					$params["configoptions"][explode("|", $option["option_name"])[0] ?? $option["option_name"]] = explode("|", $suboption["option_name"])[0];
				} else {
					$params["configoptions"][explode("|", $option["option_name"])[0] ?? $option["option_name"]] = $v["qty"];
				}
			}
			$tmp = \think\Db::name("host")->where("id", $hid)->find();
			$old_billingcycle = $tmp["billingcycle"];
			$uid = $upgrade["uid"];
			$upgrade_amount = $upgrade["amount"];
			$host_logic = new Host();
			$is_config = false;
			$is_upstream_product = false;
			if ($upgrade["type"] == "product") {
				$new_pid = $upgrade["new_value"];
				$old_pid = $upgrade["original_value"];
				$new_billingcycle = $upgrade["new_cycle"];
				$new_amount = $upgrade["new_recurring_amount"];
				$host_data = ["productid" => $new_pid, "billingcycle" => $new_billingcycle, "amount" => $new_amount];
				$new_product = \think\Db::name("products")->where("id", $new_pid)->find();
				if ($new_product["api_type"] == "zjmf_api" && $new_product["upstream_pid"] > 0) {
					$is_upstream_product = true;
				}
				$vserverid = \think\Db::name("customfields")->alias("a")->leftJoin("customfieldsvalues b", "a.id = b.fieldid")->where("a.type", "product")->where("a.relid", $old_pid)->where("a.fieldname", "vserverid")->where("b.relid", $hid)->value("b.value");
				$params["customfields"]["vserverid"] = intval($vserverid);
				$pay_type = json_decode($new_product["pay_type"], true);
				if ($old_billingcycle == "free" || $old_billingcycle == "onetime") {
					$next_time = getNextTime($new_billingcycle, $pay_type["pay_" . $new_billingcycle . "_cycle"], 0, $pay_type["pay_ontrial_cycle_type"] ?: "day");
					$host_data["nextduedate"] = $next_time;
					$host_data["nextinvoicedate"] = $next_time;
				}
				if ($new_billingcycle == "free" || $new_billingcycle == "onetime") {
					$host_data["nextduedate"] = 0;
					$host_data["nextinvoicedate"] = 0;
				}
				$flags = getSaleProductUser($new_pid, $uid);
				if ($flags) {
					$hh = \think\Db::name("host")->field("flag")->where("id", $hid)->where("flag", 1)->find();
					if (empty($hh["flag"])) {
						$res = \think\Db::name("host")->where("id", $hid)->update(["flag" => 1]);
					}
				}
				$currency_id = priorityCurrency($uid);
				$new_option_prices = $this->getProductUpgradeConfig($hid, $new_pid, $new_billingcycle, $currency_id);
				\think\Db::name("host")->where("id", $hid)->update($host_data);
				$olds = \think\Db::name("host_config_options")->alias("a")->field("b.option_type,b.id as configid,a.optionid,a.qty,c.option_name")->leftJoin("product_config_options b", "a.configid = b.id")->leftJoin("product_config_options_sub c", "a.optionid = c.id")->where("a.relid", $hid)->select()->toArray();
				foreach ($olds as $v) {
					if (judgeOs($v["option_type"])) {
						$os_id_old = explode("|", $v["option_name"])[0];
						foreach ($new_option_prices as &$vv) {
							if (judgeOs($vv["optiontype"])) {
								$os_subs = \think\Db::name("product_config_options_sub")->where("config_id", $vv["id"])->select()->toArray();
								foreach ($os_subs as $vvv) {
									if (explode("|", $vvv["option_name"])[0] == $os_id_old) {
										$vv["selsubid"] = $vvv["id"];
									}
								}
							}
						}
					}
				}
				\think\Db::name("host_config_options")->where("relid", $hid)->delete();
				foreach ($new_option_prices as $configoption) {
					$insert_data = ["relid" => $hid, "configid" => $configoption["id"], "optionid" => $configoption["selsubid"]];
					if (judgeQuantity($configoption["optiontype"])) {
						$insert_data["qty"] = $configoption["selectedqty"];
					}
					\think\Db::name("host_config_options")->insertGetId($insert_data);
				}
				$new_product = \think\Db::name("products")->field("stock_control")->where("id", $new_pid)->find();
				if (isset($new_product["stock_control"]) && $new_product["stock_control"]) {
					\think\Db::name("products")->where("id", $new_pid)->setDec("qty");
				}
				$old_product = \think\Db::name("products")->field("stock_control")->where("id", $old_pid)->find();
				if (isset($old_product["stock_control"]) && $old_product["stock_control"]) {
					\think\Db::name("products")->where("id", $old_pid)->setInc("qty");
				}
				$old_fieldids = \think\Db::name("customfields")->field("id,fieldname")->where("type", "product")->where("relid", $old_pid)->select()->toArray();
				foreach ($old_fieldids as $ov) {
					$new_fieldid = \think\Db::name("customfields")->where("type", "product")->where("relid", $new_pid)->where("fieldname", $ov["fieldname"])->value("id");
					if ($new_fieldid) {
						\think\Db::name("customfieldsvalues")->where("fieldid", $ov["id"])->where("relid", $hid)->update(["fieldid" => $new_fieldid, "update_time" => time()]);
					}
				}
				hook("after_product_upgrade", ["upgradeId" => $upgrade_id]);
			} elseif ($upgrade["type"] == "configoptions") {
				$is_config = true;
				$original_value = $upgrade["original_value"];
				$new_value = $upgrade["new_value"];
				$config_id = explode("=>", $original_value)[0];
				$recurring_change = $upgrade["recurring_change"];
				$option_type = \think\Db::name("product_config_options")->where("id", $config_id)->value("option_type");
				$count = \think\Db::name("host_config_options")->where("relid", $hid)->where("configid", $config_id)->count();
				if (!$count) {
					if (judgeQuantity($option_type)) {
						$host_config_options_data = ["relid" => $hid, "configid" => $config_id, "optionid" => explode(":", $new_value)[0], "qty" => explode(":", $new_value)[1]];
					} else {
						if (judgeYesNo($option_type)) {
							$host_data = ["relid" => $hid, "configid" => $config_id, "optionid" => explode(":", $new_value)[0], "qty" => explode(":", $new_value)[1]];
						} else {
							$host_config_options_data = ["relid" => $hid, "configid" => $config_id, "optionid" => $new_value];
						}
					}
					\think\Db::name("host_config_options")->insert($host_config_options_data);
				} else {
					if (judgeQuantity($option_type) || judgeYesNo($option_type)) {
						\think\Db::name("host_config_options")->where("relid", $hid)->where("configid", $config_id)->update(["optionid" => explode(":", $new_value)[0], "qty" => explode(":", $new_value)[1]]);
					} else {
						if ($new_value) {
							\think\Db::name("host_config_options")->where("relid", $hid)->where("configid", $config_id)->update(["optionid" => $new_value]);
						} else {
							\think\Db::name("host_config_options")->where("relid", $hid)->where("configid", $config_id)->delete();
						}
					}
				}
				$flags = getSaleProductUser($tmp["productid"], $uid);
				if ($flags) {
					$hh = \think\Db::name("host")->field("flag")->where("id", $hid)->where("flag", 1)->find();
					if (empty($hh["flag"])) {
						$res = \think\Db::name("host")->where("id", $hid)->update(["flag" => 1]);
					}
				}
				\think\Db::name("host")->where("id", $hid)->setInc("amount", $recurring_change);
				hook("after_config_upgrade", ["upgradeId" => $upgrade_id]);
			}
			$host = \think\Db::name("host")->alias("a")->leftJoin("products b", "a.productid = b.id")->field("b.upgrade_email")->where("a.id", $hid)->find();
			$email_logic = new Email();
			$email_logic->sendEmail($host["upgrade_email"], $hid);
			$promo_code = \think\Db::name("orders")->where("id", $upgrade["order_id"])->value("promo_code");
			if ($promo_code) {
				$promo = \think\Db::name("promo_code")->field("id,type,recurring,value")->where("code", $promo_code)->find();
				\think\Db::name("host")->where("id", $hid)->update(["promoid" => $promo["id"]]);
			} else {
				\think\Db::name("host")->where("id", $hid)->update(["promoid" => 0]);
			}
		}
		$res = $host_logic->changePackage($hid, $params, $is_config, $is_upstream_product, $upgrade_id);
		if ($res["status"] == 200) {
			\think\Db::name("upgrades")->where("id", $upgrade_id)->update(["status" => "Completed"]);
		}
		$logic_run_map = new RunMap();
		$model_host = new \app\common\model\HostModel();
		$data_i = [];
		$data_i["host_id"] = $hid;
		$data_i["active_type_param"] = [$hid, $params, intval($is_config), intval($is_upstream_product)];
		$is_zjmf = $model_host->isZjmfApi($data_i["host_id"]);
		if ($res["status"] == 200) {
			$data_i["description"] = " 账单支付成功 - 进行升降级 Host ID:{$data_i["host_id"]}的产品成功";
			if ($is_zjmf) {
				$logic_run_map->saveMap($data_i, 1, 400, 6);
			}
			if (!$is_zjmf) {
				$logic_run_map->saveMap($data_i, 1, 300, 6);
			}
		} else {
			$data_i["description"] = " 账单支付成功 - 进行升降级 Host ID:{$data_i["host_id"]}的产品失败：{$res["msg"]}";
			if ($is_zjmf) {
				$logic_run_map->saveMap($data_i, 0, 400, 6);
			}
			if (!$is_zjmf) {
				$logic_run_map->saveMap($data_i, 0, 300, 6);
			}
		}
		return true;
	}
	public function allowUpgradeProducts($pid)
	{
		$pids = [];
		$allowupgradeproducts = \think\Db::name("product_upgrade_products")->field("upgrade_product_id")->where("product_id", $pid)->select()->toArray();
		if (!empty($allowupgradeproducts[0])) {
			foreach ($allowupgradeproducts as $allowupgradeproduct) {
				$pids[] = $allowupgradeproduct["upgrade_product_id"];
			}
		}
		return $pids;
	}
	public function getProductUpgradeConfig($hid, $pid, $billingcycle, $currencyid)
	{
		$cart = new Cart();
		$setupfeecycle = $cart->changeCycleToupfee($billingcycle);
		$newoptions = array_values(crossProductUpgrade($hid, $pid));
		foreach ($newoptions as $kkk => $newoption) {
			$newoption = array_map(function ($v) {
				return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
			}, $newoption);
			$newoptionid = $newoption["id"];
			$newoptionname = $newoption["option_name"];
			$newoptiontype = $newoption["option_type"];
			$newoptionhidden = $newoption["hidden"];
			$newoptionqtymin = $newoption["qty_minimum"];
			$newoptionqtymax = $newoption["qty_maximum"];
			if (strpos($newoptionname, "|")) {
				$newoptionname = trim(explode("|", $newoptionname)[1]);
			}
			$suboptionsarray = [];
			$selname = $selsuboptionname = $selsetup = $selrecurring = "";
			$selectedqty = 0;
			if (judgeQuantity($newoption["option_type"])) {
				$selectedvalue = $newoption["qty_minimum"];
			} else {
				$selectedvalue = $newoption["sub_id"];
			}
			if ($newoptiontype == "3") {
				$newsuboption = \think\Db::name("product_config_options_sub")->where("config_id", $newoptionid)->find();
				$newsuboption = array_map(function ($v) {
					return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
				}, $newsuboption);
				$newsuboptionid = $newsuboption["id"];
				$newsuboptionhidden = $newsuboption["hidden"];
				$newsuboptionname = $newsuboption["option_name"];
				if (strpos($newsuboptionname, "|")) {
					$newsuboptionname = trim(explode("|", $newsuboptionname)[1]);
				}
				$newsuboptionpricing = \think\Db::name("pricing")->where("type", "configoptions")->where("currency", $currencyid)->where("relid", $newsuboptionid)->find();
				$newsuboptionsetupfee = isset($newsuboptionpricing[$billingcycle]) ? $newsuboptionpricing[$setupfeecycle] : 0;
				$newsuboptionprice = isset($newsuboptionpricing[$billingcycle]) ? $newsuboptionpricing[$billingcycle] : 0;
				$opnameonly = $newsuboptionname;
				if ($newsuboptionprice > 0) {
					$newsuboptionname .= " " . $newsuboptionprice;
				}
				$setupvalue = $newsuboptionsetupfee > 0 ? " + " . $newsuboptionsetupfee . " " . lang("ORDERS_SETUPFEE") : "";
				$suboptionsarray[] = ["id" => $newsuboptionid, "hidden" => $newsuboptionhidden, "name" => $newsuboptionname . $setupvalue, "nameonly" => $opnameonly, "recurring" => $newsuboptionprice];
				if (!$selectedvalue) {
					$selectedvalue = 0;
				}
				$selectedqty = $selectedvalue;
				$selsubid = $newsuboptionid;
				$selname = lang("NO");
				if ($selectedqty) {
					$selname = lang("YES");
					$selsuboptionname = $newsuboptionname;
					$selsetup = $newsuboptionsetupfee;
					$selrecurring = $newsuboptionprice;
				}
			} else {
				if (judgeQuantity($newoptiontype)) {
					$newsuboptions = \think\Db::name("product_config_options_sub")->alias("pcos")->field("")->leftJoin("pricing p", "p.relid = pcos.id")->where("p.type", "configoptions")->where("p.currency", $currencyid)->where("pcos.config_id", $newoptionid)->order("pcos.sort_order ASC")->order("pcos.id asc")->select();
					$exist = false;
					foreach ($newsuboptions as $newsuboption) {
						$newsuboption = array_map(function ($v) {
							return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
						}, $newsuboption);
						$newsuboptionid = $newsuboption["relid"];
						$newsuboptionhidden = $newsuboption["hidden"];
						$newsuboptionname = $newsuboption["option_name"];
						$newsuboptionqtymin = $newsuboption["qty_minimum"];
						$newsuboptionqtymax = $newsuboption["qty_maximum"];
						$newsuboptionsetupfee = isset($newsuboption[$billingcycle]) ? $newsuboption[$setupfeecycle] : 0;
						$newsuboptionprice = isset($newsuboption[$billingcycle]) ? $newsuboption[$billingcycle] : 0;
						if (strpos($newsuboptionname, "|")) {
							$newsuboptionname = trim(explode("|", $newsuboptionname)[1]);
						}
						if (!is_numeric($selectedvalue) || $selectedvalue < 0) {
							$selectedvalue = $newoptionqtymin;
							if ($newsuboptionqtymin == $newoptionqtymin) {
								$selectedqty = $selectedvalue;
								$selsubid = $newsuboptionid;
								$selsuboptionname = $newsuboptionname;
								$selsetup = $newsuboptionsetupfee * $selectedqty;
								$selrecurring = $newsuboptionprice * $selectedqty;
								$opnameonly = $newsuboptionname;
								if ($newsuboptionprice > 0) {
									$newsuboptionname .= " " . $newsuboptionprice;
								}
								$setupvalue = $newsuboptionsetupfee > 0 ? " + " . $newsuboptionsetupfee . " " . lang("ORDERS_SETUPFEE") : "";
								$suboptionsarray[] = ["id" => $newsuboptionid, "hidden" => $newsuboptionhidden, "name" => $newsuboptionname . $setupvalue, "nameonly" => $opnameonly, "recurring" => $newsuboptionprice];
								$exist = true;
							}
						}
						if ($newoptionqtymin > 0 && $selectedvalue < $newoptionqtymin) {
							$selectedvalue = $newoptionqtymin;
							if ($newsuboptionqtymin == $newoptionqtymin) {
								$selectedqty = $selectedvalue;
								$selsubid = $newsuboptionid;
								$selsuboptionname = $newsuboptionname;
								$selsetup = $newsuboptionsetupfee * $selectedqty;
								$selrecurring = $newsuboptionprice * $selectedqty;
								$opnameonly = $newsuboptionname;
								if ($newsuboptionprice > 0) {
									$newsuboptionname .= " " . $newsuboptionprice;
								}
								$setupvalue = $newsuboptionsetupfee > 0 ? " + " . $newsuboptionsetupfee . " " . lang("ORDERS_SETUPFEE") : "";
								$suboptionsarray[] = ["id" => $newsuboptionid, "hidden" => $newsuboptionhidden, "name" => $newsuboptionname . $setupvalue, "nameonly" => $opnameonly, "recurring" => $newsuboptionprice];
								$exist = true;
							}
						}
						if ($newoptionqtymax > 0 && $newoptionqtymax < $selectedvalue) {
							$selectedvalue = $newoptionqtymax;
							if ($newsuboptionqtymax == $newoptionqtymax) {
								$selectedqty = $selectedvalue;
								$selsubid = $newsuboptionid;
								$selsuboptionname = $newsuboptionname;
								$selsetup = $newsuboptionsetupfee * $selectedqty;
								$selrecurring = $newsuboptionprice * $selectedqty;
								$opnameonly = $newsuboptionname;
								if ($newsuboptionprice > 0) {
									$newsuboptionname .= " " . $newsuboptionprice;
								}
								$setupvalue = $newsuboptionsetupfee > 0 ? " + " . $newsuboptionsetupfee . " " . lang("ORDERS_SETUPFEE") : "";
								$suboptionsarray[] = ["id" => $newsuboptionid, "hidden" => $newsuboptionhidden, "name" => $newsuboptionname . $setupvalue, "nameonly" => $opnameonly, "recurring" => $newsuboptionprice];
								$exist = true;
							}
						}
						if ($newsuboptionqtymin <= $selectedvalue && $selectedvalue <= $newsuboptionqtymax) {
							$selectedqty = $selectedvalue;
							$selsubid = $newsuboptionid;
							$selsuboptionname = $newsuboptionname;
							$selsetup = $newsuboptionsetupfee * $selectedqty;
							$selrecurring = $newsuboptionprice * $selectedqty;
							$opnameonly = $newsuboptionname;
							if ($newsuboptionprice > 0) {
								$newsuboptionname .= " " . $newsuboptionprice;
							}
							$setupvalue = $newsuboptionsetupfee > 0 ? " + " . $newsuboptionsetupfee . " " . lang("ORDERS_SETUPFEE") : "";
							$suboptionsarray[] = ["id" => $newsuboptionid, "hidden" => $newsuboptionhidden, "name" => $newsuboptionname . $setupvalue, "nameonly" => $opnameonly, "recurring" => $newsuboptionprice, "salerecurring" => $newsuboption_saleprice];
							$exist = true;
						}
					}
					if ($newsuboptions[0] && !$exist) {
						$newsuboptionsetupfee = isset($newsuboptions[0][$billingcycle]) ? $newsuboptions[0][$setupfeecycle] : 0;
						$newsuboptionprice = isset($newsuboptions[0][$billingcycle]) ? $newsuboptions[0][$billingcycle] : 0;
						$newsuboptionhidden = $newsuboptions[0]["hidden"];
						$selectedqty = $newoptionqtymin;
						$selsetup = $newsuboptionprice * $selectedqty;
						$selrecurring = $newsuboptionprice * $selectedqty;
						$selsubid = $newsuboptionid = $newsuboptions[0]["id"];
						$selsuboptionname = strpos($newsuboptions[0]["option_name"], "|") ? explode("|", $newsuboptions[0]["option_name"])[1] : $newsuboptions[0]["option_name"];
						$opnameonly = $newsuboptionname;
						if ($newsuboptionprice > 0) {
							$newsuboptionname .= " " . $newsuboptionprice;
						}
						$setupvalue = $newsuboptionsetupfee > 0 ? " + " . $newsuboptionsetupfee . " " . lang("ORDERS_SETUPFEE") : "";
						$suboptionsarray[] = ["id" => $newsuboptionid, "hidden" => $newsuboptionhidden, "name" => $newsuboptionname . $setupvalue, "nameonly" => $opnameonly, "recurring" => $newsuboptionprice];
					}
					if (judgeQuantityStage($newoptiontype)) {
						$sum = quantityStagePrice($newoptionid, $currencyid, $selectedvalue, $billingcycle);
						$selrecurring = $sum[0];
						$selsetup = $sum[1];
					}
				} else {
					$newsuboptions = \think\Db::name("product_config_options_sub")->alias("pcos")->field("pcos.hidden,pcos.option_name,p.*")->leftJoin("pricing p", "p.relid = pcos.id")->where("p.type", "configoptions")->where("p.currency", $currencyid)->where("pcos.config_id", $newoptionid)->order("pcos.sort_order ASC")->order("pcos.id asc")->select();
					foreach ($newsuboptions as $newsuboption) {
						$newsuboption = array_map(function ($v) {
							return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
						}, $newsuboption);
						$newsuboptionid = $newsuboption["relid"];
						$newsuboptionhidden = $newsuboption["hidden"];
						$newsuboptionname = $newsuboption["option_name"];
						$newsuboptionsetupfee = isset($newsuboption[$billingcycle]) ? $newsuboption[$setupfeecycle] : 0;
						$newsuboptionprice = isset($newsuboption[$billingcycle]) ? $newsuboption[$billingcycle] : 0;
						if (strpos($newsuboptionname, "|")) {
							$newsuboptionname = trim(explode("|", $newsuboptionname)[1]);
						}
						$opnameonly = $newsuboptionname;
						if ($newsuboptionprice > 0) {
							$newsuboptionname .= " " . $newsuboptionprice;
						}
						$setupvalue = $newsuboptionsetupfee > 0 ? " + " . $newsuboptionsetupfee . " " . lang("ORDERS_SETUPFEE") : "";
						if (!$newsuboptionhidden || $newsuboptionid == $selectedvalue) {
							$suboptionsarray[] = ["id" => $newsuboptionid, "hidden" => $newsuboptionhidden, "name" => $newsuboptionname . $setupvalue, "nameonly" => $opnameonly, "recurring" => $newsuboptionprice, "nameandprice" => $newsuboptionname, "setup" => $newsuboptionsetupfee, "fullprice" => $newsuboptionprice];
						}
						if ($newsuboptionid == $selectedvalue || !$selectedvalue && !$newsuboptionhidden) {
							$selname = $opnameonly;
							$selsuboptionname = $newsuboptionname;
							$selsetup = $newsuboptionsetupfee;
							$selrecurring = $newsuboptionprice;
							$selsubid = $newsuboptionid;
							$foundpreselectedvalue = true;
							break;
						}
					}
					if (!$foundpreselectedvalue && count($suboptionsarray) > 0) {
						$selname = $suboptionsarray[0]["nameonly"];
						$selsuboptionname = $suboptionsarray[0]["nameandprice"];
						$selsetup = $suboptionsarray[0]["setup"];
						$selrecurring = $suboptionsarray[0]["fullprice"];
						$selsubid = $suboptionsarray[0]["id"];
					}
				}
			}
			$newconfigoptions[] = ["id" => $newoptionid, "hidden" => $newoptionhidden, "optionname" => $newoptionname, "optiontype" => $newoptiontype, "selsubid" => $selsubid, "suboptionname" => $selsuboptionname, "selsetupfee" => $selsetup, "selrecurring" => $selrecurring, "selectedqty" => $selectedqty, "selname" => $selname, "newoptionqtymin" => $newoptionqtymin, "newoptionqtymax" => $newoptionqtymax, "suboptions" => $suboptionsarray, "is_rebate" => $newoption["is_rebate"], "is_discount" => $newoption["is_discount"]];
		}
		return $newconfigoptions;
	}
	public function upgradeProductCommon($hid, $new_pid, $new_billingcycle, $currency = "", $promo_code = "", $payment = "", $checkout = false, $percent_value = "")
	{
		$gateways = array_column(gateway_list(), "name");
		if (!in_array($payment, $gateways)) {
			$payment = $gateways[0];
		}
		$desc = "";
		bcscale(20);
		$re = $data = [];
		$cart = new Cart();
		$uid = request()->uid;
		$currency_id = priorityCurrency($uid, $currency);
		$currency = (new Currencies())->getCurrencies("id,code,prefix,suffix", $currency_id)[0];
		$old_host = \think\Db::name("product_groups")->alias("pg")->field("concat(pg.name,\"-\",p.name) as host,h.regdate,h.flag,h.uid,h.domain,p.description,p.id as pid,p.name as pname,h.firstpaymentamount,h.amount,h.billingcycle,h.nextduedate,p.pay_type")->leftJoin("products p", "p.gid = pg.id")->leftJoin("host h", "h.productid = p.id")->where("h.id", $hid)->find();
		if ($checkout && $uid != $old_host["uid"]) {
			return ["status" => 400, "msg" => "非法操作"];
		}
		$old_start = $old_host["regdate"];
		$old_host = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $old_host);
		$old_pid = $old_host["pid"];
		$old_billingcycle = $old_host["billingcycle"];
		$old_paytype = json_decode($old_host["pay_type"], true);
		$old_product_name = $old_host["pname"];
		$old_duetime = $old_host["nextduedate"];
		$diff_month = getMonthNum(date("Y-m-d", $old_duetime), date("Y-m-d", $old_start));
		$old_surplus_time = 2592000 * $diff_month + $old_start - time();
		if ($checkout && $old_paytype["pay_type"] != "free" && $old_paytype["pay_type"] != "onetime") {
			if ($old_surplus_time < 0) {
				$old_surplus_time = 0;
			}
		}
		if ($old_billingcycle == "onetime") {
			$old_amount = $old_host["firstpaymentamount"];
		} else {
			$old_amount = $old_host["amount"];
		}
		$new_product = \think\Db::name("products")->where("id", $new_pid)->find();
		$new_product = array_map(function ($v) {
			return is_string($v) ? htmlspecialchars_decode($v, ENT_QUOTES) : $v;
		}, $new_product);
		if (!$new_product) {
			return ["status" => 400, "msg" => lang("INVALID_NEW_PRODUCT_ID")];
		}
		$new_product_name = $new_product["name"];
		if ($old_host["pname"] != $new_product_name) {
			$desc = " 产品" . $old_host["pname"] . "改为" . $new_product_name;
		}
		$new_paytype = json_decode($new_product["pay_type"], true);
		$new_stock_control = $new_product["stock_control"];
		$new_stock_qty = $new_product["qty"];
		$product_model = new \app\common\model\ProductModel();
		if (!$cart->validBillingCycle($new_billingcycle) || !$product_model->checkProductPrice($new_pid, $new_billingcycle, $currency_id)) {
			return ["status" => 400, "msg" => lang("INVALID_NEW_BILLING_CYCLE")];
		}
		if ($old_host["billingcycle"] != $new_billingcycle) {
			$cycle = config("billing_cycle");
			$desc = " 周期" . $cycle[$old_host["billingcycle"]] . "改为" . $cycle[$new_billingcycle];
		}
		if (!in_array($new_pid, $this->allowUpgradeProducts($old_pid))) {
			return ["status" => 400, "msg" => lang("PRODUCT_OUT_OF_OLD_PRODUCT_UPGRADE")];
		}
		if ($new_stock_control && $new_stock_qty <= 0 && $old_pid != $new_pid) {
			return ["status" => 400, "msg" => lang("PRODUCT_OUT_OF_STOCK")];
		}
		$saleproducts = 0;
		$flag = getSaleProductUser($new_pid, $uid);
		$is_zjmf_pai = false;
		if ($new_product["api_type"] == "zjmf_api" && $new_product["upstream_pid"] > 0 && $new_product["upstream_price_type"] == "percent") {
			$is_zjmf_pai = true;
		}
		if ($new_product["api_type"] == "resource" && function_exists("resourceUserGradePercent")) {
			$grade = resourceUserGradePercent($uid, $new_pid);
			$grade = bcdiv($grade, 100, 20);
		}
		$new_option_prices = $this->getProductUpgradeConfig($hid, $new_pid, $new_billingcycle, $currency_id);
		$config_amount = 0;
		foreach ($new_option_prices as $new_option_price) {
			if ($new_option_price["hidden"] == 0) {
				if ($is_zjmf_pai) {
					$new_option_price["selrecurring"] = $new_option_price["selrecurring"] * $new_product["upstream_price_value"] / 100;
				}
				if ($new_product["api_type"] == "resource" && function_exists("resourceUserGradePercent")) {
					$new_option_price["selrecurring"] = $new_option_price["selrecurring"] * $grade;
				}
				if (!empty($percent_value)) {
					$new_option_price["selrecurring"] = $new_option_price["selrecurring"] * $percent_value;
				}
				if ($new_option_price["is_rebate"]) {
					if ($flag["type"] == 1) {
						$bates = $flag["bates"] / 100;
						$saleproducts += (1 - $bates) * $new_option_price["selrecurring"];
						$new_option_price["selrecurring"] = $bates * $new_option_price["selrecurring"];
					}
				}
				$config_amount = bcadd($config_amount, $new_option_price["selrecurring"]);
			}
		}
		if ($new_billingcycle == "free") {
			$new_price = 0;
		} else {
			$pricing = \think\Db::name("pricing")->field($new_billingcycle)->where("type", "product")->where("currency", $currency_id)->where("relid", $new_pid)->find();
			$new_price = $pricing[$new_billingcycle];
		}
		if ($new_paytype["pay_type"] == "recurring" && $new_price < 0) {
			return ["status" => 400, "msg" => lang("INVALID_NEW_BILLING_CYCLE")];
		}
		if ($is_zjmf_pai) {
			$new_price = $new_price * $new_product["upstream_price_value"] / 100;
		}
		if ($new_product["api_type"] == "resource" && function_exists("resourceUserGradePercent")) {
			$new_price = $new_price * $grade;
		}
		if (!empty($percent_value)) {
			$new_price = $new_price * $percent_value;
		}
		if ($flag["type"] == 1) {
			$bates = $flag["bates"] / 100;
			$saleproducts += (1 - $bates) * $new_price;
			$new_amount = $bates * $new_price + $config_amount;
			$data["type"] = ["type" => $flag["type"], "bates" => $flag["bates"] / 10];
		} elseif ($flag["type"] == 2) {
			$bates = $flag["bates"];
			$new_amount = $config_amount + $new_price;
			$saleproducts = bcsub($new_amount, $bates) > 0 ? $bates : $new_amount;
			$new_amount = bcsub($new_amount, $bates) > 0 ? bcsub($new_amount, $bates) : 0;
			$data["type"] = ["type" => $flag["type"], "bates" => number_format($flag["bates"], 2)];
		} else {
			$new_amount = $new_price + $config_amount;
		}
		if ($old_billingcycle == "onetime" || $old_billingcycle == "free") {
			$amountdue = bcsub($new_amount, $old_amount);
			$difference = $new_amount;
		} else {
			if ($new_billingcycle == "onetime" || $new_billingcycle == "free") {
				$old_surplus_amount = bcmul(bcdiv($old_amount, $this->cycleToTime($old_billingcycle, $old_paytype["pay_{$old_billingcycle}_cycle"], $old_start, $old_paytype["pay_ontrial_cycle_type"]), true), $old_surplus_time);
				$amountdue = bcsub($new_amount, $old_surplus_amount);
				$difference = $new_amount - $old_amount;
			} else {
				$old_surplus_amount = bcmul(bcdiv($old_amount, $this->cycleToTime($old_billingcycle, $old_paytype["pay_{$old_billingcycle}_cycle"], $old_start, $old_paytype["pay_ontrial_cycle_type"], true)), $old_surplus_time);
				$new_surplus_amount = bcmul(bcdiv($new_amount, $this->cycleToTime($new_billingcycle, $new_paytype["pay_{$new_billingcycle}_cycle"], $old_start, $new_paytype["pay_ontrial_cycle_type"], true)), $old_surplus_time);
				$amountdue = $new_surplus_amount - $old_surplus_amount;
				$difference = $new_amount - $old_amount;
			}
		}
		$saleproducts = bcsub($saleproducts, 0, 2);
		$amountdue = bcsub($amountdue, 0, 2);
		$discount = 0;
		$promoqualifies = false;
		if (!$promo_code) {
			$host_promo = \think\Db::name("host")->alias("a")->field("b.code")->leftJoin("promo_code b", "a.promoid = b.id")->where("a.id", $hid)->find();
			$promo_code = $host_promo["code"] ?: "";
		}
		if ($promo_code) {
			$promo = $this->checkUpgradePromo($promo_code, $hid, $new_pid, $new_billingcycle, "product");
			$value = $promo["data"]["value"];
			$type = $promo["data"]["type"];
			if ($amountdue > 0) {
				if ($type == "percent") {
					$discount = (100 - $value) / 100 * $amountdue;
				} elseif ($type == "fixed") {
					$discount = $amountdue < $value ? $amountdue : $value;
				} elseif ($type == "override") {
					$discount = $amountdue < $value ? $amountdue : $amountdue - $value;
				} elseif ($type == "free") {
					$discount = $amountdue;
				}
				$promoqualifies = true;
			}
		}
		if ($amountdue < 0) {
			$discount = 0;
			$amount_total = $amountdue;
		} else {
			if ($amountdue - $discount < 0) {
				$amount_total = 0;
				$discount = $amountdue;
			} else {
				$amount_total = $amountdue - $discount;
			}
		}
		if ($checkout) {
			\think\Db::startTrans();
			try {
				$this->deleteUpgradeInvoices($hid);
				if ($promo_code && !$promoqualifies) {
					$promo_code = "";
				}
				if ($promo_code) {
					$upgradepromo = \think\Db::name("promo_code")->field("upgrade_config,type,value")->where("code", $promo_code)->find();
					$upgradeconfig = unserialize($upgradepromo["upgrade_config"]);
					$promotype = $upgradeconfig["upgrade_value_type"] ?: $upgradepromo["type"];
					$promovalue = $upgradeconfig["upgrade_value"] ?: $upgradepromo["value"];
					\think\Db::name("promo_code")->where("code", $promo_code)->setInc("used");
				} else {
					$promotype = "";
					$promovalue = "";
				}
				$last_upgrade_invoice = \think\Db::name("invoices")->field("id,credit")->where("uid", $uid)->where("type", "upgrade")->where("status", "Unpaid")->order("id", "desc")->find();
				if (isset($last_upgrade_invoice["credit"]) && $last_upgrade_invoice["credit"] > 0) {
					$credit = $last_upgrade_invoice["credit"];
					$last_upgrade_invoice_id = $last_upgrade_invoice["id"];
					\think\Db::name("clients")->where("id", $uid)->setInc("credit", $credit);
					credit_log(["uid" => $uid, "desc" => "Credit Removed from Invoice #" . $last_upgrade_invoice_id, "amount" => -$credit, "relid" => $last_upgrade_invoice_id]);
				}
				$amount_with_discount = bcsub($amountdue, $discount);
				$amount_without_discount = $amountdue;
				$order_data = ["uid" => $uid, "ordernum" => cmf_get_order_sn(), "create_time" => time(), "status" => "Pending", "promo_code" => $promo_code, "promo_type" => $promotype, "promo_value" => $promovalue, "payment" => $payment, "amount" => $amount_with_discount <= 0 ? 0 : $amount_with_discount];
				$invoice_data = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "subtotal" => $amount_with_discount, "credit" => "", "total" => $amount_with_discount, "status" => "Unpaid", "payment" => $payment, "type" => "upgrade", "url" => "servicedetail?id=" . $hid];
				if ($amount_with_discount > 0) {
					$invoice_id = \think\Db::name("invoices")->insertGetId($invoice_data);
				} else {
					$invoice_id = 0;
				}
				$order_data["invoiceid"] = $invoice_id;
				$order_id = \think\Db::name("orders")->insertGetId($order_data);
				$upgrades = ["uid" => $uid, "type" => "product", "date" => time(), "relid" => $hid, "original_value" => $old_pid, "new_value" => $new_pid, "new_cycle" => $new_billingcycle, "amount" => $amount_without_discount, "recurring_change" => $difference, "order_id" => $order_id, "credit_amount" => "", "days_remaining" => "", "total_days_in_cycle" => "", "new_recurring_amount" => $new_amount, "status" => "Pending", "paid" => $amount_with_discount <= 0 ? "Y" : "N", "description" => $old_host["host"] . "=>" . $new_product_name . "\n" . $old_host["domain"]];
				$upgrade_id = \think\Db::name("upgrades")->insertGetId($upgrades);
				if ($invoice_id) {
					$invoice_items_data[] = ["invoice_id" => $invoice_id, "uid" => $uid, "type" => "upgrade", "rel_id" => $upgrade_id, "description" => lang("UPGRADE_OR_DOWNGRADE_PRODUCT") . ": " . $old_product_name . "\n" . $old_product_name . "=>" . $new_product_name . " " . "(" . date("Y-m-d") . "-" . date("Y-m-d", $old_duetime) . ")", "amount" => $amountdue, "due_time" => time(), "payment" => $payment];
					if ($flag) {
						$invoice_items_data[] = ["invoice_id" => $invoice_id, "uid" => $uid, "type" => "discount", "rel_id" => $upgrade_id, "description" => "客戶折扣", "amount" => -$saleproducts, "due_time" => time(), "payment" => $payment];
					}
					if ($discount > 0) {
						$invoice_items_data[] = ["invoice_id" => $invoice_id, "uid" => $uid, "type" => "promo", "rel_id" => $upgrade_id, "description" => lang("UPGRADE_OR_DOWNGRADE_PRODUCT_PROMO") . $promo_code . "", "amount" => $discount * -1, "due_time" => time(), "payment" => $payment];
					}
					\think\Db::name("invoice_items")->insertAll($invoice_items_data);
				}
				$has = \think\Db::name("invoice_items")->alias("a")->field("b.id,b.status,b.subtotal")->leftJoin("invoices b", "a.invoice_id=b.id")->where("a.type", "renew")->where("a.rel_id", $hid)->where("a.uid", $uid)->where("b.delete_time", 0)->where("a.delete_time", 0)->where("b.status", "Unpaid")->select()->toArray();
				if (!empty($has[0])) {
					$ids = array_column($has, "id");
					\think\Db::name("invoices")->whereIn("id", $ids)->delete();
					\think\Db::name("invoice_items")->whereIn("invoice_id", $ids)->delete();
				}
				$str = "";
				if ($amountdue > 0) {
					$str .= " 产品升降级成功";
				} else {
					$str .= " 产品升降级成功";
				}
				active_log_final(sprintf("%s#User ID:%d - #Host ID:%d", $str, $uid, $hid), $uid, 2, $hid);
				\think\Db::commit();
				$res = ["status" => 200, "msg" => "结算成功"];
			} catch (\Exception $e) {
				\think\Db::rollback();
				$res = ["status" => 400, "msg" => lang("CHECK FAIL") . $e->getMessage()];
			}
			if ($res["status"] != 200) {
				return $res;
			}
			if ($amount_with_discount <= 0) {
				$credit_refund = -$amountdue;
				if (configuration("upgrade_down_product_config") && $credit_refund > 0) {
					\think\Db::startTrans();
					try {
						$invoice_refund = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "subtotal" => $credit_refund, "credit" => "", "total" => $credit_refund, "status" => "Refunded", "payment" => $payment, "type" => "upgrade", "url" => "servicedetail?id=" . $hid];
						$invoice_refund_id = \think\Db::name("invoices")->insertGetId($invoice_refund);
						$account1 = ["uid" => $uid, "currency" => $currency, "gateway" => $payment, "create_time" => time(), "pay_time" => time(), "amount_in" => $credit_refund, "fees" => "", "amount_out" => 0, "rate" => 1, "trans_id" => "", "invoice_id" => $invoice_refund_id, "refund" => 0, "description" => "产品'{$old_product_name}'降级退款,充值至余额"];
						$aid = \think\Db::name("accounts")->insertGetId($account1);
						$account2 = ["uid" => $uid, "currency" => $currency, "gateway" => $payment, "create_time" => time(), "pay_time" => time(), "amount_in" => 0, "fees" => "", "amount_out" => $credit_refund, "rate" => 1, "trans_id" => "", "invoice_id" => $invoice_refund_id, "refund" => $aid, "description" => "产品'{$old_product_name}'降级退款"];
						\think\Db::name("accounts")->insert($account2);
						$invoice_refund_item = ["invoice_id" => $invoice_refund_id, "uid" => $uid, "type" => "upgrade", "rel_id" => 0, "description" => "", "amount" => $credit_refund, "due_time" => time(), "payment" => $payment];
						\think\Db::name("invoice_items")->insert($invoice_refund_item);
						\think\Db::name("clients")->where("id", $uid)->setInc("credit", $credit_refund);
						credit_log(["uid" => $uid, "desc" => "Upgrade/Downgrade Credit", "amount" => $credit_refund, "relid" => $upgrade_id]);
						\think\Db::commit();
					} catch (\Exception $e) {
						\think\Db::rollback();
					}
				} else {
					$invoice_refund = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "subtotal" => 0, "credit" => "", "total" => 0, "status" => "Refunded", "payment" => $payment, "type" => "upgrade", "url" => "servicedetail?id=" . $hid];
					$invoice_refund_id = \think\Db::name("invoices")->insertGetId($invoice_refund);
					$invoice_refund_item = ["invoice_id" => $invoice_refund_id, "uid" => $uid, "type" => "upgrade", "rel_id" => 0, "description" => "从{$old_product_name}降级至{$new_product_name}", "amount" => 0, "due_time" => time(), "payment" => $payment];
					\think\Db::name("invoice_items")->insert($invoice_refund_item);
				}
				$this->doUpgrade($upgrade_id);
				return ["status" => 1001, "msg" => lang("BUY SUCCESS"), "data" => ["orderid" => $order_id]];
			} else {
				$response["invoiceid"] = $invoice_id;
				$response["orderid"] = $order_id;
				return ["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $response];
			}
		}
		$data["old_host"] = ["id" => $hid, "host" => $old_host["host"], "domain" => $old_host["domain"], "flag" => $old_host["flag"]];
		$data["des"] = $new_product_name;
		$data["name"] = $new_product_name;
		$data["saleproducts"] = bcsub($saleproducts, 0, 2);
		$amount_show = bcadd($amountdue, $saleproducts, 2);
		$data["amount"] = !configuration("upgrade_down_product_config") && $amount_show < 0 ? number_format(0, 2) : $amount_show;
		$data["discount"] = bcsub($discount, 0, 2);
		$data["amount_total"] = !configuration("upgrade_down_product_config") && $amount_total < 0 ? number_format(0, 2) : bcsub($amount_total, 0, 2);
		$data["currency"] = $currency;
		$data["promo_code"] = $promo_code ?: "";
		$data["billingcycle"] = $new_billingcycle;
		$data["billingcycle_zh"] = config("billing_cycle")[$new_billingcycle];
		$data["flag"] = !empty($flag) ? 1 : 0;
		$data["has_renew"] = $this->renewInvoices($hid);
		$re["data"] = $data;
		$re["status"] = 200;
		$re["msg"] = lang("SUCCESS MESSAGE");
		return $re;
	}
}