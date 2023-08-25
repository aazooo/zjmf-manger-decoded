<?php

namespace app\common\model;

class ProductModel extends \think\Model
{
	protected $linkAge = "upstream";
	public function getConfigOptionsPrice($pid = 0, $currency = 1, $price_type = [])
	{
		$data = \think\Db::name("product_config_links")->alias("a")->field("b.*")->leftJoin("product_config_options b", "a.gid=b.gid")->where("a.pid", $pid)->group("b.id")->order("b.order", "asc")->select()->toArray();
		if (!empty($data)) {
			$config_id = array_column($data, "id");
			if (!empty($price_type)) {
				$price_type_field = ",b." . implode(",b.", $price_type);
			}
			$sub = \think\Db::name("product_config_options_sub")->alias("a")->field("a.*" . $price_type_field)->leftJoin("pricing b", "b.type=\"configoptions\" and a.id=b.relid and b.currency=" . $currency)->whereIn("a.config_id", $config_id)->order("a.sort_order", "asc")->select()->toArray();
			foreach ($data as $k => $v) {
				foreach ($sub as $kk => $vv) {
					if ($v["id"] == $vv["config_id"]) {
						$data[$k]["sub"][$vv["id"]] = ["option_name" => $vv["option_name"], "price_setup" => $vv[$price_type[1]], "price_cycle" => $vv[$price_type[0]], "qty_minimum" => $vv["qty_minimum"], "qty_maximum" => $vv["qty_maximum"], "hidden" => $v["hidden"]];
					}
				}
			}
		} else {
			$data = [];
		}
		return $data;
	}
	public function getProductCycle($pid, $currency_id, $hid = "", $billingcycle = "", $amount = "", $uid = "", $billingcycle1 = "", $amounts1 = 0, $flag = "", $upgrade = 0)
	{
		$product_info = \think\Db::name("products")->where("id", $pid)->find();
		$pricing = \think\Db::name("pricing")->where("type", "product")->where("currency", $currency_id)->where("relid", $pid)->find();
		$pay_type = json_decode($product_info["pay_type"], true);
		$cycle = config("billing_cycle");
		$price_type = config("price_type");
		$cycle_filter = [];
		$renew_logic = new \app\common\logic\Renew();
		if (empty($amounts1)) {
			$amounts1 = bcsub(0, 0, 2);
		}
		foreach (array_keys($cycle) as $v) {
			if ($v != "ontrial" && $v != "free") {
				$cycle_cmp = [];
				if ($pay_type["pay_type"] == $v && isset($pricing[$v]) && $pricing[$v] >= 0) {
					$cycle_cmp["setup_fee"] = $pricing[$price_type[$v][1]];
					$cycle_cmp["price"] = $pricing[$price_type[$v][0]];
					$cycle_cmp["billingcycle"] = $v;
					$cycle_cmp["billingcycle_zh"] = $cycle[$v];
					if ($hid) {
						if ($billingcycle != $v) {
							if (!empty($billingcycle1)) {
								if ($billingcycle1 == $v) {
									$cycle_cmp["amount"] = $amounts1;
									$cycle_cmp["saleproducts"] = 0.0;
								} else {
									$amount = $renew_logic->calculatedPrice($hid, $v, 1, $flag);
									$cycle_cmp["amount"] = $amount["price_cycle"];
									$cycle_cmp["saleproducts"] = $amount["price_sale_cycle"];
								}
							} else {
								$amount = $renew_logic->calculatedPrice($hid, $v, 1, $flag);
								$cycle_cmp["amount"] = $amount["price_cycle"];
								$cycle_cmp["saleproducts"] = $amount["price_sale_cycle"];
							}
						} else {
							if ($billingcycle != $billingcycle1) {
								$amount = $renew_logic->calculatedPrice($hid, $v, 1, $flag);
								$cycle_cmp["amount"] = $amount["price_cycle"];
								$cycle_cmp["saleproducts"] = $amount["price_sale_cycle"];
							} else {
								$cycle_cmp["amount"] = $amounts1;
								$cycle_cmp["saleproducts"] = 0.0;
							}
						}
					} else {
						$cycle_cmp["amount"] = $amounts1;
						$cycle_cmp["saleproducts"] = 0.0;
					}
					$cycle_filter[] = $cycle_cmp;
				} else {
					if ($pay_type["pay_type"] == "recurring" && isset($pricing[$v]) && $pricing[$v] >= 0 && $v != "onetime" && $v != "free") {
						$cycle_cmp["setup_fee"] = $pricing[$price_type[$v][1]];
						$cycle_cmp["price"] = $pricing[$price_type[$v][0]];
						$cycle_cmp["billingcycle"] = $v;
						$cycle_cmp["billingcycle_zh"] = $cycle[$v];
						if ($hid) {
							if ($billingcycle != $v) {
								if (!empty($billingcycle1)) {
									if ($billingcycle1 == $v) {
										$cycle_cmp["amount"] = $amounts1;
										$cycle_cmp["saleproducts"] = 0.0;
									} else {
										$amount = $renew_logic->calculatedPrice($hid, $v, 1, $flag);
										$cycle_cmp["amount"] = $amount["price_cycle"];
										$cycle_cmp["saleproducts"] = $amount["price_sale_cycle"];
									}
								} else {
									$amount = $renew_logic->calculatedPrice($hid, $v, 1, $flag);
									$cycle_cmp["amount"] = $amount["price_cycle"];
									$cycle_cmp["saleproducts"] = $amount["price_sale_cycle"];
								}
							} else {
								if ($billingcycle != $billingcycle1) {
									$amount = $renew_logic->calculatedPrice($hid, $v, 1, $flag);
									$cycle_cmp["amount"] = $amount["price_cycle"];
									$cycle_cmp["saleproducts"] = $amount["price_sale_cycle"];
								} else {
									$cycle_cmp["amount"] = $amounts1;
									$cycle_cmp["saleproducts"] = 0.0;
								}
							}
						} else {
							$cycle_cmp["amount"] = $amounts1;
							$cycle_cmp["saleproducts"] = 0.0;
						}
						$cycle_filter[] = $cycle_cmp;
					}
				}
			}
		}
		$cycle_cmp = [];
		if ($pay_type["pay_type"] == "free") {
			$cycle_cmp["setup_fee"] = number_format(0, 2);
			$cycle_cmp["price"] = number_format(0, 2);
			$cycle_cmp["billingcycle"] = "free";
			$cycle_cmp["billingcycle_zh"] = $cycle["free"];
			if ($hid) {
				$amount = $renew_logic->calculatedPrice($hid, "free", 1, $flag);
				$cycle_cmp["amount"] = $amount["price_cycle"];
				$cycle_cmp["saleproducts"] = $amount["price_sale_cycle"];
			} else {
				$cycle_cmp["amount"] = $amounts1;
				$cycle_cmp["saleproducts"] = 0.0;
			}
			$cycle_filter[] = $cycle_cmp;
		}
		if (!empty($pay_type["pay_ontrial_status"]) && isset($pricing["ontrial"]) && $pricing["ontrial"] >= 0 && !$upgrade) {
			$cycle_cmp["setup_fee"] = $pricing["ontrialfee"];
			$cycle_cmp["price"] = $pricing["ontrial"];
			$cycle_cmp["billingcycle"] = "ontrial";
			$cycle_cmp["billingcycle_zh"] = $cycle["ontrial"];
			if ($hid) {
				$amount = $renew_logic->calculatedPrice($hid, "ontrial", 1, $flag);
				$cycle_cmp["amount"] = $amount["price_cycle"];
				$cycle_cmp["saleproducts"] = $amount["price_sale_cycle"];
			} else {
				$cycle_cmp["amount"] = $amounts1;
				$cycle_cmp["saleproducts"] = 0.0;
			}
			$cycle_filter[] = $cycle_cmp;
		}
		if ($upgrade && !empty($cycle_filter)) {
			$currencyid = getDefaultCurrencyId();
			foreach ($cycle_filter as &$v) {
				$cart_logic = new \app\common\logic\Cart();
				$rebate_total = $setupfee_total = 0;
				$config_total = $cart_logic->getProductDefaultConfigPrice($pid, $currencyid, $v["billingcycle"], $rebate_total, $setupfee_total);
				$flag = getSaleProductUser($pid, $uid);
				$v["price"] = bcadd($v["price"], $v["setup_fee"], 2);
				if ($flag) {
					if ($flag["type"] == 1) {
						$bates = bcdiv($flag["bates"], 100, 2);
						$v["price"] = $v["price"] + $config_total - (1 - $bates) * ($v["price"] + $rebate_total);
					} elseif ($flag["type"] == 2) {
						$bates = $flag["bates"];
						$v["price"] = $v["price"] + $config_total - $bates > 0 ? $v["price"] + $config_total - $bates : 0;
					}
					$v["price"] = round($v["price"], 2);
				} else {
					$v["price"] = bcadd($v["price"], $config_total, 2);
				}
				$v["setup_fee"] = bcadd($v["setup_fee"], $setupfee_total, 2);
			}
		}
		return $cycle_filter;
	}
	public function checkProductPrice($pid, $billingcycle, $currency_id)
	{
		$product_info = \think\Db::name("products")->where("id", $pid)->find();
		$pricing = \think\Db::name("pricing")->where("type", "product")->where("currency", $currency_id)->where("relid", $pid)->find();
		$pay_type = json_decode($product_info["pay_type"], true);
		if ($billingcycle == "free" && $pay_type["pay_type"] == "free") {
			return true;
		}
		if ($billingcycle == "ontrial" && !empty($pay_type["pay_ontrial_status"])) {
			return true;
		}
		if ($billingcycle == "onetime" && $pay_type["pay_type"] == "onetime" && $pricing["onetime"] >= 0) {
			return true;
		}
		if ($pay_type["pay_type"] == "recurring" && isset($pricing[$billingcycle]) && $pricing[$billingcycle] >= 0) {
			return true;
		}
		return false;
	}
	public function syncProductInfo($id, $api_type, $zjmf_finance_api_id, $upstream_pid, $change_to_percent = false, $change_pid = false, $cron = false, $rate = 1, $force = false)
	{
		$product = \think\Db::name("products")->where("id", $id)->find();
		if (empty($product)) {
			return ["status" => 400, "msg" => lang("ID_ERROR")];
		}
		if (empty($api_type)) {
			return ["status" => 400, "msg" => lang("接口类型非空")];
		}
		if (!in_array($api_type, array_column(config("allow_api_type"), "name"))) {
			return ["status" => 400, "msg" => lang("接口类型错误")];
		}
		if (empty($zjmf_finance_api_id) || empty($upstream_pid)) {
			return ["status" => 400, "msg" => lang("缺少接口ID或上游产品ID")];
		}
		$api = \think\Db::name("zjmf_finance_api")->where("id", $zjmf_finance_api_id)->find();
		if (empty($api)) {
			return ["status" => 400, "msg" => lang("接口不存在")];
		}
		if ($api["is_resource"] && $api["is_using"]) {
			$res = getResourceRate($zjmf_finance_api_id, $upstream_pid);
			$rate = $res["rate"] ? floatval($res["rate"]) : 1;
		}
		if (empty($rate)) {
			$list = getZjmfUpstreamProducts($zjmf_finance_api_id);
			$currency = \think\Db::name("currencies")->where("default", 1)->value("code");
			$upstream_currency = $list["currency"];
			if ($upstream_currency) {
				$arr = getRate("json");
				if ($currency == $upstream_currency) {
					$rate = 1;
				} else {
					$rate = bcdiv($arr[$currency], $arr[$upstream_currency], 20);
				}
			} else {
				$rate = 1;
			}
		}
		$basedata = $upstream_data = $lingAgeArr = [];
		if ($api_type == "zjmf_api") {
			$upstream_res = getZjmfUpstreamProductConfig($zjmf_finance_api_id, $upstream_pid);
			if ($upstream_res["status"] == 200) {
				$upstream_data = $upstream_res["data"];
				$upstream_product = $upstream_data["products"] ?? [];
				if ($product["zjmf_api_id"] > 0 && $product["zjmf_api_id"] == $zjmf_finance_api_id && $product["upstream_pid"] > 0 && $product["upstream_pid"] != $upstream_pid) {
					$product["upstream_version"] = 0;
				}
				if ($product["zjmf_api_id"] > 0 && $product["zjmf_api_id"] != $zjmf_finance_api_id) {
					$product["upstream_version"] = 0;
				}
				if (!$change_to_percent || $change_pid || $force) {
					if (!empty($upstream_product) && $product["upstream_version"] < $upstream_product["location_version"] || $change_pid || $force) {
						$basedata["type"] = $upstream_product["type"];
						$basedata["description"] = $upstream_product["description"];
						$basedata["host"] = $upstream_product["host"];
						$basedata["password"] = $upstream_product["password"];
						$pay_type = json_decode($upstream_product["pay_type"], true);
						if ($pay_type["pay_type"] == "day" || $pay_type["pay_type"] == "hour") {
							$pay_type["pay_type"] = "recurring";
						}
						$basedata["pay_type"] = json_encode($pay_type);
						$basedata["auto_setup"] = $upstream_product["auto_setup"];
						$basedata["auto_terminate_days"] = $upstream_product["auto_terminate_days"];
						$basedata["config_options_upgrade"] = $upstream_product["config_options_upgrade"];
						$basedata["down_configoption_refund"] = $upstream_product["down_configoption_refund"];
						$basedata["retired"] = $upstream_product["retired"];
						$basedata["is_featured"] = $upstream_product["is_featured"];
						$basedata["allow_qty"] = $upstream_product["allow_qty"];
						$basedata["is_truename"] = $upstream_product["is_truename"];
						$basedata["is_bind_phone"] = $upstream_product["is_bind_phone"];
						$basedata["groupid"] = $upstream_product["groupid"];
						$basedata["upstream_qty"] = $upstream_product["qty"] ?: 0;
						$basedata["upstream_product_shopping_url"] = $upstream_product["product_shopping_url"] ?: "";
						$basedata["api_type"] = $api_type;
						$basedata["location_version"] = $product["location_version"] + 1;
						$basedata["upstream_version"] = $upstream_product["location_version"];
						$basedata["zjmf_api_id"] = $zjmf_finance_api_id;
						$basedata["server_group"] = $zjmf_finance_api_id;
						$basedata["upstream_pid"] = $upstream_pid;
						$basedata["hidden"] = 0;
						$basedata["stock_control"] = $upstream_product["stock_control"];
						$basedata["qty"] = $upstream_product["qty"];
						$basedata["pay_method"] = $upstream_product["pay_method"];
						$basedata["rate"] = $rate;
					} else {
						return ["status" => 200, "msg" => "同步数据成功"];
					}
				}
			} else {
				return $upstream_res;
			}
		}
		$price_type = config("price_type");
		\think\Db::startTrans();
		try {
			if (!empty($basedata) && (!$cron || $force)) {
				\think\Db::name("products")->where("id", $id)->update($basedata);
			}
			if (!empty($upstream_product) && $product["upstream_version"] < $upstream_product["location_version"] || $change_to_percent || $change_pid || $force) {
				$currencies = \think\Db::name("currencies")->field("id,code")->where("default", 1)->select()->toArray();
				\think\Db::name("pricing")->where("type", "product")->where("relid", $id)->delete();
				$flag = $upstream_data["flag"];
				$product_pricings = $upstream_data["product_pricings"];
				if (!empty($product_pricings[0])) {
					foreach ($currencies as $currency) {
						foreach ($product_pricings as $product_pricing) {
							if ($product_pricing["code"] == $currency["code"]) {
								unset($product_pricing["id"]);
								unset($product_pricing["code"]);
								$product_pricing["relid"] = $id;
								$product_pricing["currency"] = $currency["id"];
								if ($upstream_product["api_type"] == "zjmf_api" && $upstream_product["upstream_pid"] > 0 && $upstream_product["upstream_price_type"] == "percent") {
									foreach ($price_type as $v) {
										$product_pricing[$v[0]] = $product_pricing[$v[0]] * $upstream_product["upstream_price_value"] / 100;
										$product_pricing[$v[1]] = $product_pricing[$v[1]] * $upstream_product["upstream_price_value"] / 100;
									}
								}
								\think\Db::name("pricing")->insert($product_pricing);
							} else {
								unset($product_pricing["id"]);
								unset($product_pricing["code"]);
								$product_pricing["relid"] = $id;
								$product_pricing["currency"] = $currency["id"];
								if ($upstream_product["api_type"] == "zjmf_api" && $upstream_product["upstream_pid"] > 0 && $upstream_product["upstream_price_type"] == "percent") {
									foreach ($price_type as $v) {
										if ($product_pricing[$v[0]] >= 0) {
											$product_pricing[$v[0]] = $rate * $product_pricing[$v[0]] * $upstream_product["upstream_price_value"] / 100;
										}
										$product_pricing[$v[1]] = $rate * $product_pricing[$v[1]] * $upstream_product["upstream_price_value"] / 100;
									}
								} else {
									foreach ($price_type as $v) {
										if ($product_pricing[$v[0]] >= 0) {
											$product_pricing[$v[0]] = $product_pricing[$v[0]] * $rate;
										}
										$product_pricing[$v[1]] = $product_pricing[$v[1]] * $rate;
									}
								}
								\think\Db::name("pricing")->insert($product_pricing);
							}
						}
					}
				}
				$local_customfields = \think\Db::name("customfields")->field("id,upstream_id")->where("type", "product")->where("relid", $id)->select()->toArray();
				\think\Db::name("customfields")->where("type", "product")->where("relid", $id)->delete();
				$customfields = $upstream_data["customfields"];
				if (!empty($customfields[0])) {
					foreach ($customfields as $customfield) {
						$customfield["type"] = "product";
						$customfield["relid"] = $id;
						$customfield["adminonly"] = 0;
						$customfield["create_time"] = time();
						$customfield["update_time"] = 0;
						$customfield["upstream_id"] = $customfield["id"];
						unset($customfield["id"]);
						$new_customid = \think\Db::name("customfields")->insertGetId($customfield);
						foreach ($local_customfields as $local_customfield) {
							if ($customfield["upstream_id"] == $local_customfield["upstream_id"]) {
								\think\Db::name("customfieldsvalues")->where("fieldid", $local_customfield["id"])->update(["fieldid" => $new_customid]);
							}
						}
					}
				}
				$hostids = \think\Db::name("host")->where("productid", $id)->column("id");
				$host_links = \think\Db::name("host_config_options")->alias("a")->field("a.id,a.relid,a.configid,a.optionid,a.qty,b.upstream_id as upstream_oid,c.upstream_id as upstream_subid")->leftJoin("product_config_options b", "a.configid = b.id")->leftJoin("product_config_options_sub c", "a.optionid = c.id")->whereIn("a.relid", $hostids)->select()->toArray();
				$links = \think\Db::name("product_config_links")->where("pid", $id)->select()->toArray();
				$gids = array_column($links, "gid");
				\think\Db::name("product_config_groups")->whereIn("id", $gids)->delete();
				\think\Db::name("product_config_links")->where("pid", $id)->delete();
				$config_options = \think\Db::name("product_config_options")->whereIn("gid", $gids)->select()->toArray();
				$oids = array_column($config_options, "id");
				$sub_options = \think\Db::name("product_config_options_sub")->whereIn("config_id", $oids)->select()->toArray();
				$sub_ids = array_column($sub_options, "id");
				\think\Db::name("pricing")->where("type", "configoptions")->whereIn("relid", $sub_ids)->delete();
				\think\Db::name("product_config_options")->whereIn("id", $oids)->delete();
				\think\Db::name("product_config_options_sub")->whereIn("id", $sub_ids)->delete();
				$advanced_link_ids = \think\Db::name("product_config_options_links")->whereIn("config_id", $oids)->where("type", "condition")->where("upstream_id", ">", 0)->column("id");
				\think\Db::name("product_config_options_links")->where("type", "result")->whereIn("relation_id", $advanced_link_ids)->where("upstream_id", ">", 0)->delete();
				\think\Db::name("product_config_options_links")->whereIn("config_id", $oids)->where("type", "condition")->where("upstream_id", ">", 0)->delete();
				$config_groups = $upstream_data["config_groups"];
				if (!empty($config_groups[0])) {
					foreach ($config_groups as $config_group) {
						$options = $config_group["options"];
						$config_group["upstream_id"] = $config_group["id"];
						unset($config_group["id"]);
						unset($config_group["options"]);
						$gid = \think\Db::name("product_config_groups")->insertGetId($config_group);
						$config_link = ["gid" => $gid, "pid" => $id];
						\think\Db::name("product_config_links")->insert($config_link);
						foreach ($options as $option) {
							unset($option["advanced"]);
							$subs = $option["sub"];
							$option["upstream_id"] = $option["id"];
							unset($option["id"]);
							unset($option["gid"]);
							unset($option["sub"]);
							$option["gid"] = $gid;
							$option["auto"] = 1;
							$option["is_rebate"] = $option["is_rebate"] ?? 1;
							$option["qty_stage"] = $option["qty_stage"] ?? 0;
							$config_id = \think\Db::name("product_config_options")->insertGetId($option);
							$lingAgeArr[] = $config_id;
							foreach ($subs as $sub) {
								$pricings = $sub["pricings"];
								$sub["upstream_id"] = $sub["id"];
								unset($sub["id"]);
								unset($sub["config_id"]);
								unset($sub["pricings"]);
								$sub["config_id"] = $config_id;
								$sub_id = \think\Db::name("product_config_options_sub")->insertGetId($sub);
								foreach ($host_links as $hk => $host_link) {
									if ($host_link["upstream_oid"] == $option["upstream_id"] && $host_link["upstream_subid"] == $sub["upstream_id"]) {
										\think\Db::name("host_config_options")->where("relid", $host_link["relid"])->where("configid", $host_link["configid"])->update(["configid" => $config_id, "optionid" => $sub_id]);
										unset($host_links[$hk]);
									}
								}
								foreach ($currencies as $currency) {
									foreach ($pricings as $pricing) {
										if ($pricing["code"] == $currency["code"]) {
											unset($pricing["id"]);
											unset($pricing["currency"]);
											unset($pricing["relid"]);
											unset($pricing["code"]);
											$pricing["currency"] = $currency["id"];
											$pricing["relid"] = $sub_id;
											if ($upstream_product["api_type"] == "zjmf_api" && $upstream_product["upstream_pid"] > 0 && $upstream_product["upstream_price_type"] == "percent") {
												foreach ($price_type as $v) {
													$pricing[$v[0]] = $pricing[$v[0]] * $upstream_product["upstream_price_value"] / 100;
													$pricing[$v[1]] = $pricing[$v[1]] * $upstream_product["upstream_price_value"] / 100;
												}
											}
											\think\Db::name("pricing")->insert($pricing);
										} else {
											unset($pricing["id"]);
											unset($pricing["currency"]);
											unset($pricing["relid"]);
											unset($pricing["code"]);
											$pricing["currency"] = $currency["id"];
											$pricing["relid"] = $sub_id;
											if ($upstream_product["api_type"] == "zjmf_api" && $upstream_product["upstream_pid"] > 0 && $upstream_product["upstream_price_type"] == "percent") {
												foreach ($price_type as $v) {
													$pricing[$v[0]] = $rate * $pricing[$v[0]] * $upstream_product["upstream_price_value"] / 100;
													$pricing[$v[1]] = $rate * $pricing[$v[1]] * $upstream_product["upstream_price_value"] / 100;
												}
											} else {
												foreach ($price_type as $v) {
													$pricing[$v[0]] = $rate * $pricing[$v[0]];
													$pricing[$v[1]] = $rate * $pricing[$v[1]];
												}
											}
											\think\Db::name("pricing")->insert($pricing);
										}
									}
								}
							}
						}
					}
				}
				$advanced = $upstream_data["advanced"];
				foreach ($advanced as $m) {
					if ($m["type"] == "condition") {
						$advanced_sub_id = $m["sub_id"];
						$new_advanced_data = [];
						foreach ($advanced_sub_id as $nn => $mm) {
							$advanced_sub = \think\Db::name("product_config_options_sub")->field("config_id,id")->where("upstream_id", $nn)->order("id", "desc")->find();
							$new_advanced_sub_id = $advanced_sub["id"];
							$config_id_condition = $advanced_sub["config_id"];
							$new_advanced_data[$new_advanced_sub_id] = $mm;
						}
						$new_advanced = ["config_id" => intval($config_id_condition), "sub_id" => json_encode($new_advanced_data), "relation" => $m["relation"], "type" => $m["type"], "relation_id" => 0, "upstream_id" => $m["id"]];
						$condition_id = \think\Db::name("product_config_options_links")->insertGetId($new_advanced);
						foreach ($advanced as $m3) {
							if ($m3["type"] == "result") {
								if ($m3["relation_id"] == $m["id"]) {
									$advanced_sub_id_result = $m3["sub_id"];
									$new_advanced_data_result = [];
									foreach ($advanced_sub_id_result as $n4 => $m4) {
										$new_advanced_sub_result = \think\Db::name("product_config_options_sub")->field("config_id,id")->where("upstream_id", $n4)->order("id", "desc")->find();
										$new_advanced_sub_id_result = $new_advanced_sub_result["id"];
										$config_id_result = $new_advanced_sub_result["config_id"];
										$new_advanced_data_result[$new_advanced_sub_id_result] = $m4;
									}
									$result_advanced = ["config_id" => intval($config_id_result), "sub_id" => json_encode($new_advanced_data_result), "relation" => $m3["relation"], "type" => $m3["type"], "relation_id" => $condition_id, "upstream_id" => $m3["id"]];
									\think\Db::name("product_config_options_links")->insertGetId($result_advanced);
								}
							}
						}
					}
				}
				if (!empty($host_links[0])) {
					$link_ids = array_column($host_links, "id");
					\think\Db::name("host_config_options")->whereIn("id", $link_ids)->delete();
				}
				$this->handleLingAge($lingAgeArr);
				\think\Db::name("info_notice")->where("relid", $id)->where("type", "product")->where("admin", 1)->update(["info" => "", "update_time" => time()]);
			}
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return ["status" => 400, "msg" => lang("同步数据失败") . $e->getMessage()];
		}
		return ["status" => 200, "msg" => lang("同步数据成功")];
	}
	public function syncProductInfoToResource($id, $api_type, $zjmf_finance_api_id, $upstream_pid, $change_to_percent = false, $change_pid = false, $cron = false, $rate = 1, $force = false)
	{
		$product = \think\Db::name("products")->where("id", $id)->find();
		if (empty($product)) {
			return ["status" => 400, "msg" => lang("ID_ERROR")];
		}
		if (empty($api_type)) {
			return ["status" => 400, "msg" => lang("接口类型非空")];
		}
		if (!in_array($api_type, array_column(config("allow_api_type"), "name"))) {
			return ["status" => 400, "msg" => lang("接口类型错误")];
		}
		if (empty($zjmf_finance_api_id) || empty($upstream_pid)) {
			return ["status" => 400, "msg" => lang("缺少接口ID或上游产品ID")];
		}
		$api = \think\Db::name("zjmf_finance_api")->where("id", $zjmf_finance_api_id)->find();
		if (empty($api)) {
			return ["status" => 400, "msg" => lang("接口不存在")];
		}
		if ($api["is_resource"] && $api["is_using"]) {
			$res = getResourceRate($zjmf_finance_api_id, $upstream_pid);
			$rate = $res["rate"] ? floatval($res["rate"]) : 1;
		}
		if (empty($rate)) {
			$list = getZjmfUpstreamProducts($zjmf_finance_api_id);
			$currency = \think\Db::name("currencies")->where("default", 1)->value("code");
			$upstream_currency = $list["currency"];
			if ($upstream_currency) {
				$arr = getRate("json");
				if ($currency == $upstream_currency) {
					$rate = 1;
				} else {
					$rate = bcdiv($arr[$currency], $arr[$upstream_currency], 2);
				}
			} else {
				$rate = 1;
			}
		}
		$basedata = $upstream_data = $lingAgeArr = [];
		if ($api_type == "zjmf_api") {
			$upstream_res = getZjmfUpstreamProductConfig($zjmf_finance_api_id, $upstream_pid);
			if ($upstream_res["status"] == 200) {
				$upstream_data = $upstream_res["data"];
				$upstream_product = $upstream_data["products"] ?? [];
				if ($product["zjmf_api_id"] > 0 && $product["zjmf_api_id"] == $zjmf_finance_api_id && $product["upstream_pid"] > 0 && $product["upstream_pid"] != $upstream_pid) {
					$product["upstream_version"] = 0;
				}
				if ($product["zjmf_api_id"] > 0 && $product["zjmf_api_id"] != $zjmf_finance_api_id) {
					$product["upstream_version"] = 0;
				}
				if (!$change_to_percent || $change_pid || $force) {
					if (!empty($upstream_product) && $product["upstream_version"] < $upstream_product["location_version"] || $change_pid || $force) {
						$basedata["type"] = $upstream_product["type"];
						$basedata["description"] = $upstream_product["description"];
						$basedata["host"] = $upstream_product["host"];
						$basedata["password"] = $upstream_product["password"];
						$pay_type = json_decode($upstream_product["pay_type"], true);
						if ($pay_type["pay_type"] == "day" || $pay_type["pay_type"] == "hour") {
							$pay_type["pay_type"] = "recurring";
						}
						$basedata["pay_type"] = json_encode($pay_type);
						$basedata["auto_setup"] = $upstream_product["auto_setup"];
						$basedata["auto_terminate_days"] = $upstream_product["auto_terminate_days"];
						$basedata["config_options_upgrade"] = $upstream_product["config_options_upgrade"];
						$basedata["down_configoption_refund"] = $upstream_product["down_configoption_refund"];
						$basedata["retired"] = $upstream_product["retired"];
						$basedata["is_featured"] = $upstream_product["is_featured"];
						$basedata["allow_qty"] = $upstream_product["allow_qty"];
						$basedata["is_truename"] = $upstream_product["is_truename"];
						$basedata["is_bind_phone"] = $upstream_product["is_bind_phone"];
						$basedata["groupid"] = $upstream_product["groupid"];
						$basedata["upstream_qty"] = $upstream_product["qty"] ?: 0;
						$basedata["upstream_product_shopping_url"] = $upstream_product["product_shopping_url"] ?: "";
						$basedata["api_type"] = $api_type;
						$basedata["location_version"] = $product["location_version"] + 1;
						$basedata["upstream_version"] = $upstream_product["location_version"];
						$basedata["zjmf_api_id"] = $zjmf_finance_api_id;
						$basedata["server_group"] = $zjmf_finance_api_id;
						$basedata["upstream_pid"] = $upstream_pid;
						$basedata["hidden"] = 0;
						$basedata["stock_control"] = $upstream_product["stock_control"];
						$basedata["qty"] = $upstream_product["qty"];
						$basedata["pay_method"] = $upstream_product["pay_method"];
						$basedata["upstream_version"] = $upstream_product["location_version"];
						$basedata["rate"] = $rate;
					} else {
						return ["status" => 200, "msg" => "同步数据成功"];
					}
				}
			} else {
				return $upstream_res;
			}
		}
		$price_type = config("price_type");
		if (!empty($basedata) && (!$cron || $force)) {
			\think\Db::name("products")->where("id", $id)->update($basedata);
		}
		if (!empty($upstream_product) && $product["upstream_version"] < $upstream_product["location_version"] || $change_to_percent || $change_pid || $force) {
			$currencies = \think\Db::name("currencies")->field("id,code")->where("default", 1)->select()->toArray();
			\think\Db::name("pricing")->where("type", "product")->where("relid", $id)->delete();
			$flag = $upstream_data["flag"];
			$product_pricings = $upstream_data["product_pricings"];
			if (!empty($product_pricings[0])) {
				foreach ($currencies as $currency) {
					foreach ($product_pricings as $product_pricing) {
						if ($product_pricing["code"] == $currency["code"]) {
							unset($product_pricing["id"]);
							unset($product_pricing["code"]);
							$product_pricing["relid"] = $id;
							$product_pricing["currency"] = $currency["id"];
							if ($upstream_product["api_type"] == "zjmf_api" && $upstream_product["upstream_pid"] > 0 && $upstream_product["upstream_price_type"] == "percent") {
								foreach ($price_type as $v) {
									$product_pricing[$v[0]] = $product_pricing[$v[0]] * $upstream_product["upstream_price_value"] / 100;
									$product_pricing[$v[1]] = $product_pricing[$v[1]] * $upstream_product["upstream_price_value"] / 100;
								}
							}
							\think\Db::name("pricing")->insert($product_pricing);
						} else {
							unset($product_pricing["id"]);
							unset($product_pricing["code"]);
							$product_pricing["relid"] = $id;
							$product_pricing["currency"] = $currency["id"];
							if ($upstream_product["api_type"] == "zjmf_api" && $upstream_product["upstream_pid"] > 0 && $upstream_product["upstream_price_type"] == "percent") {
								foreach ($price_type as $v) {
									if ($product_pricing[$v[0]] >= 0) {
										$product_pricing[$v[0]] = $rate * $product_pricing[$v[0]] * $upstream_product["upstream_price_value"] / 100;
									}
									$product_pricing[$v[1]] = $rate * $product_pricing[$v[1]] * $upstream_product["upstream_price_value"] / 100;
								}
							} else {
								foreach ($price_type as $v) {
									if ($product_pricing[$v[0]] >= 0) {
										$product_pricing[$v[0]] = $product_pricing[$v[0]] * $rate;
									}
									$product_pricing[$v[1]] = $product_pricing[$v[1]] * $rate;
								}
							}
							\think\Db::name("pricing")->insert($product_pricing);
						}
					}
				}
			}
			$local_customfields = \think\Db::name("customfields")->field("id,upstream_id")->where("type", "product")->where("relid", $id)->select()->toArray();
			\think\Db::name("customfields")->where("type", "product")->where("relid", $id)->delete();
			$customfields = $upstream_data["customfields"];
			if (!empty($customfields[0])) {
				foreach ($customfields as $customfield) {
					$customfield["type"] = "product";
					$customfield["relid"] = $id;
					$customfield["adminonly"] = 0;
					$customfield["create_time"] = time();
					$customfield["update_time"] = 0;
					$customfield["upstream_id"] = $customfield["id"];
					unset($customfield["id"]);
					$new_customid = \think\Db::name("customfields")->insertGetId($customfield);
					foreach ($local_customfields as $local_customfield) {
						if ($customfield["upstream_id"] == $local_customfield["upstream_id"]) {
							\think\Db::name("customfieldsvalues")->where("fieldid", $local_customfield["id"])->update(["fieldid" => $new_customid]);
						}
					}
				}
			}
			$hostids = \think\Db::name("host")->where("productid", $id)->column("id");
			$host_links = \think\Db::name("host_config_options")->alias("a")->field("a.id,a.relid,a.configid,a.optionid,a.qty,b.upstream_id as upstream_oid,c.upstream_id as upstream_subid")->leftJoin("product_config_options b", "a.configid = b.id")->leftJoin("product_config_options_sub c", "a.optionid = c.id")->whereIn("a.relid", $hostids)->select()->toArray();
			$links = \think\Db::name("product_config_links")->where("pid", $id)->select()->toArray();
			$gids = array_column($links, "gid");
			\think\Db::name("product_config_groups")->whereIn("id", $gids)->delete();
			\think\Db::name("product_config_links")->where("pid", $id)->delete();
			$config_options = \think\Db::name("product_config_options")->whereIn("gid", $gids)->select()->toArray();
			$oids = array_column($config_options, "id");
			$sub_options = \think\Db::name("product_config_options_sub")->whereIn("config_id", $oids)->select()->toArray();
			$sub_ids = array_column($sub_options, "id");
			\think\Db::name("pricing")->where("type", "configoptions")->whereIn("relid", $sub_ids)->delete();
			\think\Db::name("product_config_options")->whereIn("id", $oids)->delete();
			\think\Db::name("product_config_options_sub")->whereIn("id", $sub_ids)->delete();
			$advanced_link_ids = \think\Db::name("product_config_options_links")->whereIn("config_id", $oids)->where("type", "condition")->where("upstream_id", ">", 0)->column("id");
			\think\Db::name("product_config_options_links")->where("type", "result")->whereIn("relation_id", $advanced_link_ids)->where("upstream_id", ">", 0)->delete();
			\think\Db::name("product_config_options_links")->whereIn("config_id", $oids)->where("type", "condition")->where("upstream_id", ">", 0)->delete();
			$config_groups = $upstream_data["config_groups"];
			if (!empty($config_groups[0])) {
				foreach ($config_groups as $config_group) {
					$options = $config_group["options"];
					$config_group["upstream_id"] = $config_group["id"];
					unset($config_group["id"]);
					unset($config_group["options"]);
					$gid = \think\Db::name("product_config_groups")->insertGetId($config_group);
					$config_link = ["gid" => $gid, "pid" => $id];
					\think\Db::name("product_config_links")->insert($config_link);
					foreach ($options as $option) {
						unset($option["advanced"]);
						$subs = $option["sub"];
						$option["upstream_id"] = $option["id"];
						unset($option["id"]);
						unset($option["gid"]);
						unset($option["sub"]);
						$option["gid"] = $gid;
						$option["auto"] = 1;
						$option["is_rebate"] = $option["is_rebate"] ?? 1;
						$option["qty_stage"] = $option["qty_stage"] ?? 0;
						$config_id = \think\Db::name("product_config_options")->insertGetId($option);
						$lingAgeArr[] = $config_id;
						foreach ($subs as $sub) {
							$pricings = $sub["pricings"];
							$sub["upstream_id"] = $sub["id"];
							unset($sub["id"]);
							unset($sub["config_id"]);
							unset($sub["pricings"]);
							$sub["config_id"] = $config_id;
							$sub_id = \think\Db::name("product_config_options_sub")->insertGetId($sub);
							foreach ($host_links as $hk => $host_link) {
								if ($host_link["upstream_oid"] == $option["upstream_id"] && $host_link["upstream_subid"] == $sub["upstream_id"]) {
									\think\Db::name("host_config_options")->where("relid", $host_link["relid"])->where("configid", $host_link["configid"])->update(["configid" => $config_id, "optionid" => $sub_id]);
									unset($host_links[$hk]);
								}
							}
							foreach ($currencies as $currency) {
								foreach ($pricings as $pricing) {
									if ($pricing["code"] == $currency["code"]) {
										unset($pricing["id"]);
										unset($pricing["currency"]);
										unset($pricing["relid"]);
										unset($pricing["code"]);
										$pricing["currency"] = $currency["id"];
										$pricing["relid"] = $sub_id;
										if ($upstream_product["api_type"] == "zjmf_api" && $upstream_product["upstream_pid"] > 0 && $upstream_product["upstream_price_type"] == "percent") {
											foreach ($price_type as $v) {
												$pricing[$v[0]] = $pricing[$v[0]] * $upstream_product["upstream_price_value"] / 100;
												$pricing[$v[1]] = $pricing[$v[1]] * $upstream_product["upstream_price_value"] / 100;
											}
										}
										\think\Db::name("pricing")->insert($pricing);
									} else {
										unset($pricing["id"]);
										unset($pricing["currency"]);
										unset($pricing["relid"]);
										unset($pricing["code"]);
										$pricing["currency"] = $currency["id"];
										$pricing["relid"] = $sub_id;
										if ($upstream_product["api_type"] == "zjmf_api" && $upstream_product["upstream_pid"] > 0 && $upstream_product["upstream_price_type"] == "percent") {
											foreach ($price_type as $v) {
												$pricing[$v[0]] = $rate * $pricing[$v[0]] * $upstream_product["upstream_price_value"] / 100;
												$pricing[$v[1]] = $rate * $pricing[$v[1]] * $upstream_product["upstream_price_value"] / 100;
											}
										} else {
											foreach ($price_type as $v) {
												$pricing[$v[0]] = $rate * $pricing[$v[0]];
												$pricing[$v[1]] = $rate * $pricing[$v[1]];
											}
										}
										\think\Db::name("pricing")->insert($pricing);
									}
								}
							}
						}
					}
				}
			}
			$advanced = $upstream_data["advanced"];
			foreach ($advanced as $m) {
				if ($m["type"] == "condition") {
					$advanced_sub_id = $m["sub_id"];
					$new_advanced_data = [];
					foreach ($advanced_sub_id as $nn => $mm) {
						$advanced_sub = \think\Db::name("product_config_options_sub")->field("config_id,id")->where("upstream_id", $nn)->order("id", "desc")->find();
						$new_advanced_sub_id = $advanced_sub["id"];
						$config_id_condition = $advanced_sub["config_id"];
						$new_advanced_data[$new_advanced_sub_id] = $mm;
					}
					$new_advanced = ["config_id" => intval($config_id_condition), "sub_id" => json_encode($new_advanced_data), "relation" => $m["relation"], "type" => $m["type"], "relation_id" => 0, "upstream_id" => $m["id"]];
					$condition_id = \think\Db::name("product_config_options_links")->insertGetId($new_advanced);
					foreach ($advanced as $m3) {
						if ($m3["type"] == "result") {
							if ($m3["relation_id"] == $m["id"]) {
								$advanced_sub_id_result = $m3["sub_id"];
								$new_advanced_data_result = [];
								foreach ($advanced_sub_id_result as $n4 => $m4) {
									$new_advanced_sub_result = \think\Db::name("product_config_options_sub")->field("config_id,id")->where("upstream_id", $n4)->order("id", "desc")->find();
									$new_advanced_sub_id_result = $new_advanced_sub_result["id"];
									$config_id_result = $new_advanced_sub_result["config_id"];
									$new_advanced_data_result[$new_advanced_sub_id_result] = $m4;
								}
								$result_advanced = ["config_id" => intval($config_id_result), "sub_id" => json_encode($new_advanced_data_result), "relation" => $m3["relation"], "type" => $m3["type"], "relation_id" => $condition_id, "upstream_id" => $m3["id"]];
								\think\Db::name("product_config_options_links")->insertGetId($result_advanced);
							}
						}
					}
				}
			}
			if (!empty($host_links[0])) {
				$link_ids = array_column($host_links, "id");
				\think\Db::name("host_config_options")->whereIn("id", $link_ids)->delete();
			}
			$this->handleLingAge($lingAgeArr);
			\think\Db::name("info_notice")->where("relid", $id)->where("type", "product")->where("admin", 1)->update(["info" => "", "update_time" => time()]);
		}
		return ["status" => 200, "msg" => lang("同步数据成功")];
	}
	public function handleLingAge($lingAgeArr)
	{
		if (!$lingAgeArr) {
			return null;
		}
		$option_arr = \think\Db::name("product_config_options")->whereIn("id", $lingAgeArr)->where("option_type", 20)->select()->toArray();
		if (!$option_arr) {
			return null;
		}
		$this->handleOptions($option_arr);
		$option_ids = array_column($option_arr, "id");
		$sub_arr = \think\Db::name("product_config_options_sub")->whereIn("config_id", $option_ids)->select()->toArray();
		if (!$sub_arr) {
			return null;
		}
		$this->handleSubs($sub_arr);
	}
	public function handleOptions($option)
	{
		$tree = $this->getTree($option);
		$this->handleOptionLevel($tree, 0, 0, "");
	}
	public function handleSubs($sub)
	{
		$tree = $this->getTree($sub);
		$this->handleSubLevel($tree, 0, 0, "");
	}
	public function handleSubLevel($tree, $linkage_pid = 0, $linkage_top_pid = 0, $linkage_level = "")
	{
		foreach ($tree as $k => $v) {
			$data = ["linkage_pid" => $v["linkage_pid"] == 0 ? 0 : $linkage_pid, "linkage_top_pid" => $linkage_pid == 0 ? 0 : $linkage_top_pid, "linkage_level" => $linkage_level ? $linkage_level . "-" . $v["id"] : "0-" . $v["id"]];
			if ($v["linkage_top_pid"] == 0) {
				$linkage_top_pid = $v["id"];
			}
			\think\Db::name("product_config_options_sub")->where("id", $v["id"])->update($data);
			if ($v["son"]) {
				$this->handleSubLevel($v["son"], $v["id"], $linkage_top_pid, $data["linkage_level"]);
			}
		}
		return null;
	}
	public function handleOptionLevel($tree, $linkage_pid = 0, $linkage_top_pid = 0, $linkage_level = "")
	{
		foreach ($tree as $k => $v) {
			$data = ["linkage_pid" => $v["linkage_pid"] == 0 ? 0 : $linkage_pid, "linkage_top_pid" => $linkage_pid == 0 ? 0 : $linkage_top_pid, "linkage_level" => $linkage_level ? $linkage_level . "-" . $v["id"] : "0-" . $v["id"]];
			if ($v["linkage_top_pid"] == 0) {
				$linkage_top_pid = $v["id"];
			}
			\think\Db::name("product_config_options")->where("id", $v["id"])->update($data);
			if ($v["son"]) {
				$this->handleOptionLevel($v["son"], $v["id"], $linkage_top_pid, $data["linkage_level"]);
			}
		}
		return null;
	}
	public function setLinkAge($value = "upstream")
	{
		$this->linkAge = $value;
		return $this;
	}
	private function getTree($data, $son = "son")
	{
		if (empty($data)) {
			return [];
		}
		if ($this->linkAge == "upstream") {
			$_data = array_column($data, null, "upstream_id");
		}
		if ($this->linkAge == "copy") {
			$_data = array_column($data, null, "copy_id");
		}
		$result = [];
		foreach ($_data as $key => $val) {
			if (isset($_data[$val["linkage_pid"]])) {
				$_data[$val["linkage_pid"]][$son][] =& $_data[$key];
			} else {
				$result[] =& $_data[$key];
			}
		}
		return $result;
	}
}