<?php

namespace app\common\logic;

class Product
{
	private $list_name = "shd_all_products_list";
	private $detail_name = "shd_all_products_detail_";
	private $info_name = "shd_all_products_info";
	public $concurrent = 500;
	public $cron_max = 10;
	public function getProducts($pids = [])
	{
		$where = function (\think\db\Query $query) use($pids) {
			if (!empty($pids)) {
				$query->whereIn("id", $pids);
			}
		};
		$products = \think\Db::name("products")->where($where)->select()->toArray();
		$products_filter = [];
		foreach ($products as $product) {
			$id = $product["id"];
			$fields = \think\Db::name("customfields")->field("id,fieldname,description,fieldtype,fieldoptions,regexpr,required,showorder,showinvoice,sortorder,showdetail")->where("type", "product")->where("relid", $id)->where("adminonly", 0)->where("showorder", 1)->order("sortorder desc")->select()->toArray();
			$product_pricings = \think\Db::name("pricing")->alias("a")->field("a.*,b.code")->leftJoin("currencies b", "a.currency = b.id")->where("a.type", "product")->where("a.relid", $id)->where("b.default", 1)->select()->toArray();
			$config_groups = \think\Db::name("product_config_groups")->alias("a")->leftJoin("product_config_links b", "a.id = b.gid")->field("a.id,a.name,a.description")->where("b.pid", $id)->select()->toArray();
			$config_links_data = \think\Db::name("product_config_links")->where("pid", $id)->select()->toArray();
			$oids_all = [];
			foreach ($config_groups as $k => $v) {
				$options = \think\Db::name("product_config_options")->where("gid", $v["id"])->where("hidden", 0)->select()->toArray();
				foreach ($options as $kk => $vv) {
					$subs = \think\Db::name("product_config_options_sub")->where("config_id", $vv["id"])->where("hidden", 0)->select()->toArray();
					foreach ($subs as $kkk => $vvv) {
						$pricings = \think\Db::name("pricing")->alias("a")->field("a.*,b.code")->leftJoin("currencies b", "a.currency = b.id")->where("type", "configoptions")->where("relid", $vvv["id"])->where("b.default", 1)->select()->toArray();
						$subs[$kkk]["pricings"] = $pricings;
					}
					$options[$kk]["sub"] = $subs;
				}
				$oids_all = array_merge($oids_all, array_column($options, "id"));
				$config_groups[$k]["options"] = $options;
			}
			$advanced = \think\Db::name("product_config_options_links")->whereIn("config_id", array_unique($oids_all))->order("id", "asc")->select()->toArray();
			foreach ($advanced as &$advance) {
				$advance["sub_id"] = json_decode($advance["sub_id"], true);
			}
			$product["customfields"] = $fields;
			$product["product_pricings"] = $product_pricings;
			$product["advanced"] = $advanced;
			$product["config_groups"] = $config_groups;
			$product["config_links"] = array_column($config_links_data, "gid");
			$products_filter[$id] = $product;
		}
		return $products_filter;
	}
	public function updateDetailCache($pids = [])
	{
		if (!is_array($pids)) {
			$pids = [$pids];
		}
		foreach ($pids as $pid) {
			$tmp = $this->getProducts([$pid]);
			cache($this->detail_name . $pid, json_encode($tmp));
			unset($tmp);
		}
		return true;
	}
	public function deleteDetailCache($pids = [])
	{
		if (!is_array($pids)) {
			$pids = [$pids];
		}
		foreach ($pids as $pid) {
			cache($this->detail_name . $pid, null);
		}
		return true;
	}
	public function getDetailCache($pid)
	{
		$tmp = cache($this->detail_name . $pid);
		return json_decode($tmp, true);
	}
	public function updateInfoCache()
	{
		$infos = \think\Db::name("products")->field("id,name,location_version,stock_control,qty")->select()->toArray();
		cache($this->info_name, json_encode($infos));
		unset($infos);
		return true;
	}
	public function getInfoCache()
	{
		$tmp = cache($this->info_name);
		return json_decode($tmp, true);
	}
	public function deleteInfoCache()
	{
		cache($this->info_name, null);
		return true;
	}
	public function updateCache($pids = [])
	{
		if (!is_array($pids)) {
			$pids = [$pids];
		}
		$this->updateInfoCache();
		$this->updateDetailCache($pids);
		$this->updateListCache($pids);
		return true;
	}
	public function getCurrencyRateCache()
	{
		$currency_arr = cache("shd_cron_currency_rate");
		$currency_arr = json_decode($currency_arr, true);
		if (empty($currency_arr)) {
			$currency_arr = getRate("json");
			cache("shd_cron_currency_rate", json_encode($currency_arr), 86400);
		}
		return $currency_arr;
	}
	public function syncProduct($param)
	{
		$id = $param["pid"];
		$product = \think\Db::name("products")->where("id", $id)->find();
		$zjmf_finance_api_id = intval($param["zjmf_finance_api_id"]);
		$api = \think\Db::name("zjmf_finance_api")->where("id", $zjmf_finance_api_id)->find();
		$api_name = $api["name"];
		$upstream_pid = intval($param["upstream_pid"]);
		$timeout = isset($param["timeout"]) ? floatval($param["timeout"]) : 30;
		$log = "";
		if (isset($param["page_type"])) {
			if ($param["page_type"] == "set_config_page") {
				$log = "购物车页面";
			} elseif ($param["page_type"] == "edit_product") {
				$log = "保存商品";
			}
		}
		$res = getZjmfUpstreamProductsInfo($zjmf_finance_api_id, [$upstream_pid], $timeout);
		if ($res["status"] != 200) {
			$desc = "{$log}获取供应商'{$api_name}'商品版本信息失败,请检查供应商接口是否可用或联系供应商更新财务系统至最新版本,报错信息:{$res["msg"]}";
			active_log_final($desc, 0, 0);
			return ["status" => 400, "msg" => "【" . $api_name . "】无法链接,本地数据可能与上游数据不一致!"];
		}
		$info = $res["data"]["info"][0];
		if (empty($info)) {
			return ["status" => 400, "msg" => "同步失败,供应商【{$api_name}】商品已删除"];
		}
		$upstream_currency = $res["data"]["currency"];
		if (!isset($param["rate"])) {
			$local_currency = \think\Db::name("currencies")->where("default", 1)->value("code");
			$currency_arr = $this->getCurrencyRateCache();
			if ($local_currency == $upstream_currency) {
				$rate = 1;
			} else {
				$rate = bcdiv($currency_arr[$local_currency], $currency_arr[$upstream_currency], 20);
			}
		} else {
			$rate = floatval($param["rate"]);
		}
		if ($product["upstream_version"] != $info["location_version"] || $product["upstream_price_type"] != $param["upstream_price_type"]) {
			$res = getZjmfUpstreamProductsDetail($zjmf_finance_api_id, [$upstream_pid], $timeout);
			if ($res["status"] != 200) {
				$desc = "{$log}获取供应商'{$api_name}'商品详细信息失败,请检查供应商接口是否可用或联系供应商更新财务系统至最新版本,报错信息:{$res["msg"]}";
				active_log_final($desc, 0, 0);
				return ["status" => 400, "msg" => "【" . $api_name . "】无法链接,本地数据可能与上游数据不一致!"];
			}
			$upstream_data = $upstream_product = $res["data"]["detail"][$upstream_pid];
			if (empty($upstream_data)) {
				return ["status" => 400, "msg" => "同步失败,供应商【{$api_name}】商品已删除"];
			}
			if (empty($product["upstream_pid"])) {
				$product["first_cron"] = 1;
			}
			$product["upstream_pid"] = $upstream_pid;
			$product["zjmf_api_id"] = $zjmf_finance_api_id;
			$res = $this->baseUpdateProduct($upstream_data, $product, $rate);
			if ($res["status"] != 200) {
				$desc = "{$log}同步供应商'{$api_name}'商品'{$upstream_data["name"]}'失败,本地#PRODUCT ID:{$id},报错信息:{$res["msg"]}";
				active_log_final($desc, 0, 0);
				return $res;
			}
		} else {
			if ($info["qty"] != $product["upstream_qty"] || $info["stock_control"] != $product["upstream_stock_control"]) {
				\think\Db::name("products")->where("id", $id)->update(["upstream_qty" => $info["qty"], "upstream_stock_control" => $info["stock_control"]]);
			}
		}
		if ($product["upstream_version"] != $info["location_version"] || $info["qty"] != $product["upstream_qty"] || $info["stock_control"] != $product["upstream_stock_control"] || $product["upstream_price_type"] != $param["upstream_price_type"]) {
			if (!empty($param["upstream_price_type"])) {
				\think\Db::name("products")->where("id", $id)->update(["upstream_price_type" => $param["upstream_price_type"] ?: "percent", "upstream_price_value" => $param["upstream_price_value"] ?: 120]);
			}
			$this->updateCache([$id]);
			$desc = "{$log}同步供应商'{$api_name}'商品'{$info["name"]}'成功,本地#PRODUCT ID:{$id}";
			if (isset($param["page_type"]) && $param["page_type"] == "set_config_page") {
			} else {
				active_log_final($desc, 0, 0);
			}
		}
		return ["status" => 200, "msg" => "同步数据成功"];
	}
	public function cronSyncProduct()
	{
		$apis = \think\Db::name("zjmf_finance_api")->field("id,name")->where("type", "zjmf_api")->select()->toArray();
		$currency_arr = $this->getCurrencyRateCache();
		$local_currency = \think\Db::name("currencies")->where("default", 1)->value("code");
		foreach ($apis as $api) {
			$id = $api["id"];
			$api_name = $api["name"];
			$res = getZjmfUpstreamProductsInfo($id);
			if ($res["status"] == 200) {
				$upstream_currency = $res["data"]["currency"];
				if ($local_currency == $upstream_currency) {
					$rate = 1;
				} else {
					$rate = bcdiv($currency_arr[$local_currency], $currency_arr[$upstream_currency], 20);
				}
				$infos = $res["data"]["info"];
				$products = \think\Db::name("products")->field("id,name,description,upstream_pid,upstream_version,upstream_price_type,location_version,zjmf_api_id,gid,pay_type,
                    upstream_stock_control,upstream_qty")->where("zjmf_api_id", $id)->select()->toArray();
				$pids = $local_products = $local_pids = $exist = [];
				foreach ($infos as $info) {
					foreach ($products as $product) {
						if ($info["id"] == $product["upstream_pid"]) {
							$exist[] = $info["id"];
							if ($info["location_version"] != $product["upstream_version"]) {
								$pids[] = $info["id"];
								$local_products[$info["id"]] = $product;
								$local_pids[] = $product["id"];
							}
							if ($info["stock_control"] != $product["upstream_stock_control"] || $info["qty"] != $product["stock_control"]) {
								\think\Db::name("products")->where("id", $product["id"])->update(["upstream_qty" => $info["qty"], "upstream_stock_control" => $info["stock_control"]]);
							}
						}
					}
				}
				foreach ($products as $v) {
					if (!in_array($v["upstream_pid"], $exist)) {
						$desc = "商品'{$v["name"]}'无法同步,本地#PRODUCT ID:{$v["id"]},原因:供应商'{$api_name}'已删除该商品,请及时处理";
						active_log_final($desc, 0, 5);
					}
				}
				$concurrent = $this->concurrent;
				$count = count($pids);
				$k = ceil($count / $concurrent);
				if ($this->cron_max < $k) {
					$k = $this->cron_max;
				}
				for ($i = 0; $i < $k; $i++) {
					$tmp = array_slice($pids, $i * $concurrent, $concurrent);
					$res = getZjmfUpstreamProductsDetail($id, $tmp);
					if ($res["status"] == 200) {
						$detail = $res["data"]["detail"];
						foreach ($detail as $key => $value) {
							$local_product = $local_products[$key];
							if ($local_product["upstream_price_type"] == "percent") {
								$res = $this->baseUpdateProduct($value, $local_product, $rate, true);
							} else {
								$res = $this->customUpdateProduct($value, $local_product, $rate);
							}
							if ($res["status"] == 200) {
								$this->updateCache($local_pids);
								$desc = "定时任务同步供应商'{$api_name}'商品'{$value["name"]}'成功,本地#PRODUCT ID:" . $local_product["id"];
								active_log_final($desc, 0, 5);
							} else {
								$desc = "定时任务同步供应商'{$api_name}'商品'{$value["name"]}'失败,本地#PRODUCT ID:{$local_product["id"]},报错信息:{$res["msg"]}";
								active_log_final($desc, 0, 5);
							}
						}
					} else {
						$desc = "定时任务获取供应商'{$api_name}'商品详细信息失败,请检查供应商接口是否可用或联系供应商更新财务系统至最新版本,报错信息:{$res["msg"]}";
						active_log_final($desc, 0, 5);
					}
				}
				if (empty($pids[0])) {
					$desc = "供应商'{$api_name}'暂无商品需要同步";
					active_log_final($desc, 0, 5);
				}
			} else {
				$desc = "定时任务获取供应商'{$api_name}'商品版本信息失败,请检查供应商接口是否可用或联系供应商更新财务系统至最新版本,报错信息:{$res["msg"]}";
				active_log_final($desc, 0, 5);
			}
		}
		return true;
	}
	public function baseUpdateProduct($upstream_product, $product, $rate = 1, $is_cron = false)
	{
		$upstream_data = $upstream_product;
		$zjmf_finance_api_id = $product["zjmf_api_id"];
		$upstream_pid = $product["upstream_pid"];
		$id = $product["id"];
		$pay_type = json_decode($upstream_product["pay_type"], true);
		if ($pay_type["pay_type"] == "day" || $pay_type["pay_type"] == "hour") {
			$pay_type["pay_type"] = "recurring";
		}
		$pay_type_local = json_decode($product["pay_type"], true);
		if ($is_cron) {
			$pay_type["clientscount_rule"] = $pay_type_local["clientscount_rule"];
		}
		$basedata = ["type" => $upstream_product["type"], "password" => $upstream_product["password"], "auto_setup" => "payment", "auto_terminate_days" => $upstream_product["auto_terminate_days"], "config_options_upgrade" => $upstream_product["config_options_upgrade"], "down_configoption_refund" => $upstream_product["down_configoption_refund"], "retired" => $upstream_product["retired"], "is_featured" => $upstream_product["is_featured"], "groupid" => $upstream_product["groupid"], "api_type" => "zjmf_api", "location_version" => $product["location_version"] + 1, "upstream_version" => $upstream_product["location_version"], "zjmf_api_id" => $zjmf_finance_api_id, "server_group" => $zjmf_finance_api_id, "upstream_pid" => $upstream_pid, "hidden" => intval($upstream_product["hidden"]), "pay_method" => $upstream_product["pay_method"], "rate" => $rate, "upstream_stock_control" => $upstream_product["upstream_stock_control"], "upstream_qty" => $upstream_product["qty"], "stock_control" => 0, "qty" => 0, "upstream_auto_setup" => $upstream_product["auto_setup"], "upstream_ontrial_status" => intval($pay_type["pay_ontrial_status"]), "upstream_product_shopping_url" => $upstream_product["product_shopping_url"] ?: ""];
		$upstream_host = json_decode($upstream_product["host"], true);
		if ($upstream_host["show"] == 0) {
			$basedata["host"] = $upstream_product["host"];
		}
		$edition = getEdition();
		if (empty($pay_type["pay_ontrial_status"]) && $edition || !$edition) {
			$basedata["pay_type"] = json_encode($pay_type);
		}
		if ($pay_type["pay_ontrial_status"] && $edition) {
			$pay_type["pay_ontrial_status"] = $pay_type_local["pay_ontrial_status"];
			$basedata["pay_type"] = json_encode($pay_type);
		}
		if ($product["first_cron"]) {
			$basedata["description"] = $upstream_product["description"];
			$basedata["allow_qty"] = $upstream_product["allow_qty"];
			$basedata["is_truename"] = $upstream_product["is_truename"];
			$basedata["is_bind_phone"] = $upstream_product["is_bind_phone"];
			$basedata["cancel_control"] = $upstream_product["cancel_control"];
			$basedata["pay_type"] = json_encode($pay_type);
		}
		$price_type = config("price_type");
		\think\Db::startTrans();
		try {
			\think\Db::name("products")->where("id", $id)->update($basedata);
			$currencies = \think\Db::name("currencies")->field("id,code")->where("default", 1)->select()->toArray();
			\think\Db::name("pricing")->where("type", "product")->where("relid", $id)->delete();
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
			(new \app\common\model\ProductModel())->handleLingAge($lingAgeArr);
			\think\Db::name("info_notice")->where("relid", $id)->where("type", "product")->where("admin", 1)->update(["info" => "", "update_time" => time()]);
			\think\Db::commit();
		} catch (\Exception $e) {
			\think\Db::rollback();
			return ["status" => 400, "msg" => "同步数据失败:" . $e->getMessage()];
		}
		return ["status" => 200, "msg" => "同步数据成功"];
	}
	public function customUpdateProduct($upstream_product, $product, $rate = 1)
	{
		$pid = $product["id"];
		$group = \think\Db::name("product_groups")->where("id", $product["gid"])->find();
		$product_pricings = \think\Db::name("pricing")->alias("a")->field("a.*,b.pay_type")->leftJoin("products b", "a.relid = b.id")->where("a.type", "product")->where("b.id", $pid)->select()->toArray();
		$configoptions_pricings = \think\Db::name("pricing")->alias("a")->field("a.*,f.type")->leftJoin("product_config_options_sub b", "a.relid = b.id")->leftJoin("product_config_options c", "b.config_id = c.id")->leftJoin("product_config_links d", "c.gid = d.gid")->leftJoin("product_config_groups e", "d.gid = e.id")->leftJoin("products f", "d.pid = f.id")->where("a.type", "configoptions")->where("f.id", $pid)->select()->toArray();
		$currencies = \think\Db::name("currencies")->field("id,code")->where("default", 1)->select()->toArray();
		$price_type = config("price_type");
		$origin_price_cost = [];
		foreach ($price_type as $kkk => $vvv) {
			$currency_cost = [];
			foreach ($currencies as $currency) {
				$cost = 0;
				foreach ($product_pricings as $k => $v) {
					if ($currency["id"] == $v["currency"]) {
						$x1 = $v[$vvv[0]] < 0 ? 0 : $v[$vvv[0]];
						$y1 = $v[$vvv[1]] < 0 ? 0 : $v[$vvv[1]];
						$cost += floatval($x1) + floatval($y1);
						foreach ($configoptions_pricings as $kk => $vv) {
							if ($v["currency"] == $vv["currency"]) {
								$cost += $vv[$vvv[0]] + $vv[$vvv[1]];
							}
						}
					}
				}
				$currency_cost[$currency["code"]] = $cost;
			}
			$origin_price_cost[$kkk] = $currency_cost;
		}
		$upstream_product_pricings = $upstream_product["product_pricings"];
		$upstream_config_groups = $upstream_product["config_groups"];
		$upstream_price_cost = [];
		foreach ($price_type as $jjj => $hhh) {
			$upstream_currency_cost = [];
			foreach ($currencies as $currency) {
				$upstream_cost = 0;
				foreach ($upstream_product_pricings as $j => $h) {
					if ($currency["code"] == $h["code"]) {
						$x = $h[$hhh[0]] < 0 ? 0 : $h[$hhh[0]];
						$y = $h[$hhh[1]] < 0 ? 0 : $h[$hhh[1]];
						$upstream_cost += floatval($x) + floatval($y);
						foreach ($upstream_config_groups as $jj => $hh) {
							$options = $hh["options"];
							foreach ($options as $j4 => $h4) {
								$subs = $h4["sub"];
								foreach ($subs as $j5 => $h5) {
									$pricings = $h5["pricings"];
									foreach ($pricings as $j6 => $h6) {
										if ($h["code"] == $h6["code"]) {
											$upstream_cost += $h6[$hhh[0]] + $h6[$hhh[1]];
										}
									}
								}
							}
						}
					} else {
						$x = $h[$hhh[0]] < 0 ? 0 : $h[$hhh[0]] * $rate;
						$y = $h[$hhh[1]] < 0 ? 0 : $h[$hhh[1]] * $rate;
						$upstream_cost += floatval($x) + floatval($y);
						foreach ($upstream_config_groups as $jj => $hh) {
							$options = $hh["options"];
							foreach ($options as $j4 => $h4) {
								$subs = $h4["sub"];
								foreach ($subs as $j5 => $h5) {
									$pricings = $h5["pricings"];
									foreach ($pricings as $j6 => $h6) {
										if ($h["code"] == $h6["code"]) {
											$upstream_cost += $h6[$hhh[0]] + $h6[$hhh[1]];
										} else {
											$upstream_cost += ($h6[$hhh[0]] + $h6[$hhh[1]]) * $rate;
										}
									}
								}
							}
						}
					}
				}
				$upstream_currency_cost[$currency["code"]] = $upstream_cost;
			}
			$upstream_price_cost[$jjj] = $upstream_currency_cost;
		}
		$bilingcycle = config("billing_cycle");
		$dec = [];
		$pay_type = json_decode($product["pay_type"], true);
		$origin_price_cost_filter = [];
		if ($pay_type["pay_type"] == "onetime") {
			$origin_price_cost_filter["onetime"] = $origin_price_cost["onetime"];
		} elseif ($pay_type["pay_type"] == "recurring") {
			unset($origin_price_cost["onetime"]);
			unset($origin_price_cost["ontrial"]);
			$origin_price_cost_filter = $origin_price_cost;
		}
		if ($pay_type["pay_ontrial_status"]) {
			$origin_price_cost_filter["ontrial"] = $origin_price_cost["ontrial"];
		}
		foreach ($origin_price_cost_filter as $u => $w) {
			foreach ($currencies as $currency) {
				if ($w[$currency["code"]] < $upstream_price_cost[$u][$currency["code"]]) {
					$info = "系统检测到产品组'{$group["name"]}'中产品'{$product["name"]}'在货币为{$currency["code"]},周期为{$bilingcycle[$u]}时, 销售价低于成本价,已开启此产品库存控制,请尽快同步更新!";
					$dec[] = $info;
				}
			}
		}
		if (!empty($dec)) {
			\think\Db::name("products")->where("id", $pid)->update(["stock_control" => 1, "qty" => 0]);
			$info = implode("\n", $dec) ?? "";
			$exist = \think\Db::name("info_notice")->where("relid", $pid)->where("type", "product")->where("admin", 1)->find();
			if ($exist) {
				\think\Db::name("info_notice")->where("relid", $pid)->where("type", "product")->where("admin", 1)->update(["info" => $info, "update_time" => time()]);
			} else {
				\think\Db::name("info_notice")->insert(["relid" => $pid, "type" => "product", "info" => $info, "admin" => 1, "create_time" => time(), "update_time" => 0]);
			}
		}
		return ["status" => 200];
	}
	public function getList($pids = [])
	{
		if (!is_array($pids)) {
			$pids = [$pids];
		}
		$where = function (\think\db\Query $query) use($pids) {
			$query->where("hidden", 0)->where("retired", 0);
			if (!empty($pids)) {
				$query->whereIn("id", $pids);
			}
		};
		$currencyid = \think\Db::name("currencies")->where("default", 1)->value("id");
		$lists = \think\Db::name("products")->field("id,type,gid,name,description,pay_method,tax,order,pay_type,api_type,
            upstream_version,upstream_price_type,upstream_price_value,stock_control,qty")->where($where)->order("order", "asc")->select()->toArray();
		$filter = [];
		foreach ($lists as $v) {
			$v = array_map(function ($value) {
				return is_string($value) ? htmlspecialchars_decode($value, ENT_QUOTES) : $value;
			}, $v);
			$paytype = (array) json_decode($v["pay_type"]);
			$pricing = \think\Db::name("pricing")->where("type", "product")->where("relid", $v["id"])->where("currency", $currencyid)->find();
			if (!empty($paytype["pay_ontrial_status"])) {
				if ($pricing["ontrial"] >= 0) {
					$v["product_price"] = $pricing["ontrial"];
					$v["setup_fee"] = $pricing["ontrialfee"];
					$v["billingcycle"] = "ontrial";
					$v["billingcycle_zh"] = lang("ONTRIAL");
				} else {
					$v["product_price"] = 0;
					$v["setup_fee"] = 0;
					$v["billingcycle"] = "";
					$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
				}
				$v["ontrial"] = 1;
				$v["ontrial_cycle"] = $paytype["pay_ontrial_cycle"];
				$v["ontrial_cycle_type"] = $paytype["pay_ontrial_cycle_type"] ?: "day";
				$v["ontrial_price"] = $pricing["ontrial"];
				$v["ontrial_setup_fee"] = $pricing["ontrialfee"];
			} else {
				$v["ontrial"] = 0;
			}
			if ($paytype["pay_type"] == "free") {
				$v["product_price"] = 0;
				$v["setup_fee"] = 0;
				$v["billingcycle"] = "free";
				$v["billingcycle_zh"] = lang("FREE");
			} elseif ($paytype["pay_type"] == "onetime") {
				if ($pricing["onetime"] >= 0) {
					$v["product_price"] = $pricing["onetime"];
					$v["setup_fee"] = $pricing["osetupfee"];
					$v["billingcycle"] = "onetime";
					$v["billingcycle_zh"] = lang("ONETIME");
				} else {
					$v["product_price"] = 0;
					$v["setup_fee"] = 0;
					$v["billingcycle"] = "";
					$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
				}
			} else {
				if (!empty($pricing) && $paytype["pay_type"] == "recurring") {
					if ($pricing["hour"] >= 0) {
						$v["product_price"] = $pricing["hour"];
						$v["setup_fee"] = $pricing["hsetupfee"];
						$v["billingcycle"] = "hour";
						$v["billingcycle_zh"] = lang("HOUR");
					} elseif ($pricing["day"] >= 0) {
						$v["product_price"] = $pricing["day"];
						$v["setup_fee"] = $pricing["dsetupfee"];
						$v["billingcycle"] = "day";
						$v["billingcycle_zh"] = lang("DAY");
					} elseif ($pricing["monthly"] >= 0) {
						$v["product_price"] = $pricing["monthly"];
						$v["setup_fee"] = $pricing["msetupfee"];
						$v["billingcycle"] = "monthly";
						$v["billingcycle_zh"] = lang("MONTHLY");
					} elseif ($pricing["quarterly"] >= 0) {
						$v["product_price"] = $pricing["quarterly"];
						$v["setup_fee"] = $pricing["qsetupfee"];
						$v["billingcycle"] = "quarterly";
						$v["billingcycle_zh"] = lang("QUARTERLY");
					} elseif ($pricing["semiannually"] >= 0) {
						$v["product_price"] = $pricing["semiannually"];
						$v["setup_fee"] = $pricing["ssetupfee"];
						$v["billingcycle"] = "semiannually";
						$v["billingcycle_zh"] = lang("SEMIANNUALLY");
					} elseif ($pricing["annually"] >= 0) {
						$v["product_price"] = $pricing["annually"];
						$v["setup_fee"] = $pricing["asetupfee"];
						$v["billingcycle"] = "annually";
						$v["billingcycle_zh"] = lang("ANNUALLY");
					} elseif ($pricing["biennially"] >= 0) {
						$v["product_price"] = $pricing["biennially"];
						$v["setup_fee"] = $pricing["bsetupfee"];
						$v["billingcycle"] = "biennially";
						$v["billingcycle_zh"] = lang("BIENNIALLY");
					} elseif ($pricing["triennially"] >= 0) {
						$v["product_price"] = $pricing["triennially"];
						$v["setup_fee"] = $pricing["tsetupfee"];
						$v["billingcycle"] = "triennially";
						$v["billingcycle_zh"] = lang("TRIENNIALLY");
					} elseif ($pricing["fourly"] >= 0) {
						$v["product_price"] = $pricing["fourly"];
						$v["setup_fee"] = $pricing["foursetupfee"];
						$v["billingcycle"] = "fourly";
						$v["billingcycle_zh"] = lang("FOURLY");
					} elseif ($pricing["fively"] >= 0) {
						$v["product_price"] = $pricing["fively"];
						$v["setup_fee"] = $pricing["fivesetupfee"];
						$v["billingcycle"] = "fively";
						$v["billingcycle_zh"] = lang("FIVELY");
					} elseif ($pricing["sixly"] >= 0) {
						$v["product_price"] = $pricing["sixly"];
						$v["setup_fee"] = $pricing["sixsetupfee"];
						$v["billingcycle"] = "sixly";
						$v["billingcycle_zh"] = lang("SIXLY");
					} elseif ($pricing["sevenly"] >= 0) {
						$v["product_price"] = $pricing["sevenly"];
						$v["setup_fee"] = $pricing["sevensetupfee"];
						$v["billingcycle"] = "sevenly";
						$v["billingcycle_zh"] = lang("SEVENLY");
					} elseif ($pricing["eightly"] >= 0) {
						$v["product_price"] = $pricing["eightly"];
						$v["setup_fee"] = $pricing["eightsetupfee"];
						$v["billingcycle"] = "eightly";
						$v["billingcycle_zh"] = lang("EIGHTLY");
					} elseif ($pricing["ninely"] >= 0) {
						$v["product_price"] = $pricing["ninely"];
						$v["setup_fee"] = $pricing["ninesetupfee"];
						$v["billingcycle"] = "ninely";
						$v["billingcycle_zh"] = lang("NINELY");
					} elseif ($pricing["tenly"] >= 0) {
						$v["product_price"] = $pricing["tenly"];
						$v["setup_fee"] = $pricing["tensetupfee"];
						$v["billingcycle"] = "tenly";
						$v["billingcycle_zh"] = lang("TENLY");
					} else {
						$v["product_price"] = 0;
						$v["setup_fee"] = 0;
						$v["billingcycle"] = "";
						$v["billingcycle_zh"] = lang("PRICE_CONFIG_ERROR");
					}
				} else {
					$v["product_price"] = 0;
					$v["setup_fee"] = 0;
					$v["billingcycle"] = "";
					$v["billingcycle_zh"] = lang("PRICE_NO_CONFIG");
				}
			}
			if ($paytype["pay_type"] == "recurring" && in_array($v["type"], array_keys(config("developer_app_product_type")))) {
				if ($pricing["annually"] > 0) {
					$v["product_price"] = $pricing["annually"];
					$v["setup_fee"] = $pricing["asetupfee"];
					$v["billingcycle"] = "annually";
					$v["billingcycle_zh"] = lang("ANNUALLY");
				}
			}
			$v["product_price"] = bcadd($v["setup_fee"], $v["product_price"], 2);
			$cart_logic = new Cart();
			$rebate_total = 0;
			$config_total = $cart_logic->getProductDefaultConfigPrice($v["id"], $currencyid, $v["billingcycle"], $rebate_total);
			$rebate_total = bcadd($v["product_price"], $rebate_total, 2);
			$v["product_price"] = bcadd($v["product_price"], $config_total, 2);
			if ($v["api_type"] == "zjmf_api" && $v["upstream_version"] > 0 && $v["upstream_price_type"] == "percent") {
				$v["product_price"] = bcmul($v["product_price"], $v["upstream_price_value"] / 100, 2);
				if ($v["ontrial"] == 1) {
					$v["ontrial_price"] = bcmul($v["ontrial_price"], $v["upstream_price_value"] / 100, 2);
					$v["ontrial_setup_fee"] = bcmul($v["ontrial_setup_fee"], $v["upstream_price_value"] / 100, 2);
				}
				$rebate_total = bcmul($rebate_total, $v["upstream_price_value"] / 100, 2);
			}
			$cgs = \think\Db::name("client_groups")->alias("a")->field("a.id,b.type,b.bates")->leftJoin("user_product_bates b", "a.id=b.user")->leftJoin("user_products c", "b.products=c.gid")->where("c.pid", $v["id"])->select()->toArray();
			$cg_f = [];
			foreach ($cgs as $cg) {
				if ($cg["type"] == 1) {
					$bates = bcdiv($cg["bates"], 100, 2);
					$rebate = bcmul($rebate_total, 1 - $bates, 2) < 0 ? 0 : bcmul($rebate_total, 1 - $bates, 2);
					$cg["sale_price"] = bcsub($v["product_price"], $rebate, 2) < 0 ? 0 : bcsub($v["product_price"], $rebate, 2);
					$cg["bates"] = bcmul($v["product_price"], 1 - $bates, 2);
				} else {
					$bates = $cg["bates"];
					$rebate = $rebate_total < $bates ? $rebate_total : $bates;
					$cg["sale_price"] = bcsub($v["product_price"], $rebate, 2) < 0 ? 0 : bcsub($v["product_price"], $rebate, 2);
					$cg["bates"] = $bates;
				}
				$cg_f[$cg["id"]] = $cg;
			}
			$v["cgs"] = $cg_f;
			unset($v["pay_method"]);
			unset($v["tax"]);
			unset($v["order"]);
			unset($v["pay_type"]);
			unset($v["api_type"]);
			unset($v["upstream_version"]);
			unset($v["upstream_price_type"]);
			unset($v["upstream_price_value"]);
			$filter[$v["id"]] = $v;
		}
		return $filter;
	}
	public function updateListCache($pids = [], $force = false)
	{
		if (!is_array($pids)) {
			$pids = [$pids];
		}
		$list = $this->getListCache();
		if (empty($list) || $force) {
			$list = $this->getList();
		} else {
			$tmp = $this->getList($pids);
			$list = $tmp + $list;
		}
		if ($list) {
			cache($this->list_name, json_encode($list));
		} else {
			cache($this->list_name, null);
		}
		unset($list);
		unset($tmp);
		return true;
	}
	public function getListCache()
	{
		$list = cache($this->list_name);
		return json_decode($list, true);
	}
	public function deleteListCache()
	{
		cache($this->list_name, null);
		return true;
	}
}