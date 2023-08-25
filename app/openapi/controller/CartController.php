<?php

namespace app\openapi\controller;

class CartController extends \cmf\controller\HomeBaseController
{
	public function products()
	{
		$param = $this->request->param();
		$uid = request()->uid;
		$logic = new \app\common\logic\Product();
		$lists = $logic->getListCache();
		if (empty($lists)) {
			$logic->updateListCache();
			$lists = $logic->getListCache();
		}
		if (isset($param["product_id"])) {
			$pid = intval($param["product_id"]);
			$group_id = \think\Db::name("products")->where("id", $pid)->value("gid") ?: 0;
			$fgid = \think\Db::name("product_groups")->where("id", $group_id)->value("gid") ?: 0;
		} elseif (isset($param["group_id"])) {
			$group_id = intval($param["group_id"]);
			$fgid = \think\Db::name("product_groups")->where("id", $group_id)->value("gid") ?: 0;
		} elseif (isset($param["first_group_id"])) {
			$fgid = intval($param["first_group_id"]);
		}
		$gid = 0;
		if ($uid) {
			$gid = \think\Db::name("clients")->where("id", $uid)->value("groupid");
		}
		$fgs = \think\Db::name("product_first_groups")->field("id,name")->where("hidden", 0)->where(function (\think\db\Query $query) use($fgid) {
			if (isset($fgid)) {
				$query->where("id", $fgid);
			}
		})->order("order", "asc")->order("id", "asc")->select()->toArray();
		foreach ($fgs as &$fg) {
			$fg["fields"] = \think\Db::name("product_first_groups_customfields")->field("name,value")->where("relid", $fg["id"])->select()->toArray();
			$gs = \think\Db::name("product_groups")->field("id,name,headline,tagline")->where("hidden", 0)->where("order_frm_tpl", "<>", "uuid")->where(function (\think\db\Query $query) use($fg, $group_id) {
				$query->where("gid", $fg["id"]);
				if (isset($group_id)) {
					$query->where("id", $group_id);
				}
			})->order("order", "asc")->select()->toArray();
			foreach ($gs as &$g) {
				$g["fields"] = \think\Db::name("product_groups_customfields")->field("name,value")->where("relid", $g["id"])->select()->toArray();
				$tmp = array_filter($lists, function ($v) use($g) {
					if ($v["gid"] != $g["id"]) {
						return false;
					}
					return true;
				});
				$filter = [];
				foreach ($tmp as $v) {
					if ($v["ontrial"]) {
						$v["ontrial"] = ["ontrial" => $v["ontrial"], "ontrial_cycle" => $v["ontrial_cycle"], "ontrial_cycle_type" => $v["ontrial_cycle_type"], "ontrial_price" => $v["ontrial_price"], "ontrial_setup_fee" => $v["ontrial_setup_fee"]];
					} else {
						$v["ontrial"] = ["ontrial" => 0];
					}
					unset($v["ontrial_cycle"]);
					unset($v["ontrial_cycle_type"]);
					unset($v["ontrial_price"]);
					unset($v["ontrial_setup_fee"]);
					unset($v["billingcycle_zh"]);
					if ($v["gid"] = $g["id"]) {
						if ($gid && isset($v["cgs"][$gid])) {
							$v["sale_price"] = $v["cgs"][$gid]["sale_price"];
							$v["bates"] = $v["cgs"][$gid]["bates"];
						}
						unset($v["cgs"]);
						unset($v["gid"]);
						$filter[] = $v;
					}
				}
				if (isset($pid)) {
					$filter = array_filter($filter, function ($v) use($pid) {
						if ($pid != $v["id"]) {
							return false;
						}
						return true;
					});
				}
				$g["products"] = array_values($filter);
			}
			$fg["group"] = $gs;
		}
		$data = ["first_group" => $fgs, "currency" => getUserCurrency($uid)];
		return json(["status" => 200, "msg" => "Success message", "data" => $data]);
	}
	public function productsConfig()
	{
		$param = $this->request->param();
		$uid = request()->uid;
		$currencyid = priorityCurrency($uid);
		$currency = get_currency();
		if (isset($param["product_id"])) {
			$pid = intval($param["product_id"]);
			$group_id = \think\Db::name("products")->where("id", $pid)->value("gid") ?: 0;
			$fgid = \think\Db::name("product_groups")->where("id", $group_id)->value("gid") ?: 0;
		} elseif (isset($param["group_id"])) {
			$group_id = intval($param["group_id"]);
			$fgid = \think\Db::name("product_groups")->where("id", $group_id)->value("gid") ?: 0;
		} elseif (isset($param["first_group_id"])) {
			$fgid = intval($param["first_group_id"]);
		}
		if (empty($fgid) && empty($group_id) && empty($pid)) {
			return json(["status" => 400, "msg" => "param miss"]);
		}
		$fgs = \think\Db::name("product_first_groups")->field("id,name")->where("hidden", 0)->where(function (\think\db\Query $query) use($fgid) {
			if (isset($fgid)) {
				$query->where("id", $fgid);
			}
		})->order("order", "asc")->order("id", "asc")->select()->toArray();
		if (empty($fgs)) {
			$fgs[] = ["id" => 0, "name" => "", "fields" => [], "group" => []];
		}
		foreach ($fgs as &$fg) {
			$fg["fields"] = \think\Db::name("product_first_groups_customfields")->field("name,value")->where("relid", $fg["id"])->select()->toArray();
			$gs = \think\Db::name("product_groups")->field("id,name,headline,tagline")->where("hidden", 0)->where(function (\think\db\Query $query) use($fg, $group_id) {
				if ($fg["id"] == 0) {
					$query->where("order_frm_tpl", "uuid");
				} else {
					$query->where("gid", $fg["id"]);
				}
				if (isset($group_id)) {
					$query->where("id", $group_id);
				}
			})->order("order", "asc")->select()->toArray();
			foreach ($gs as &$g) {
				$g["fields"] = \think\Db::name("product_groups_customfields")->field("name,value")->where("relid", $g["id"])->select()->toArray();
				if ($pid) {
					$pids = [$pid];
				} else {
					$pids = \think\Db::name("products")->where("gid", $g["id"])->column("id");
				}
				foreach ($pids as $pid) {
					$cart = new \app\common\logic\Cart();
					$product = $cart->getProductCycle($pid, $currencyid);
					$customfields = new \app\common\logic\Customfields();
					$fields = $customfields->getCartCustomField($pid);
					$config_logic = new \app\common\logic\ConfigOptions();
					$alloption = $config_logic->openAPIConfig($pid);
					$configoptions_cycle = array_column($product["cycle"], "billingcycle");
					foreach ($alloption as $con_k => $con_v) {
						if ($con_v["sub"]) {
							foreach ($con_v["sub"] as $sub_k => $sub_v) {
								$alloption[$con_k]["sub"][$sub_k]["pricing"][0] = $this->subPricing($configoptions_cycle, $sub_v["pricing"][0]);
							}
						}
					}
					$product["configoptions"] = $alloption;
					$product["custom_fields"] = $fields;
					$g["products"][] = $product;
				}
			}
			$fg["group"] = $gs;
		}
		$data = ["currency" => $currency, "first_group" => $fgs];
		return json(["status" => 200, "msg" => "Success message", "data" => $data]);
	}
	public function subPricing($configoptions_cycle, $pricings)
	{
		$configoptions_pricing = [];
		foreach ($pricings as $key => $pricing) {
			if ($pricing["hour"] >= 0 && in_array("hour", $configoptions_cycle)) {
				$configoptions_pricing["hour"] = $pricing["hour"];
				$configoptions_pricing["hour_setup_fee"] = $pricing["hsetupfee"];
			}
			if ($pricing["day"] >= 0 && in_array("day", $configoptions_cycle)) {
				$configoptions_pricing["day"] = $pricing["day"];
				$configoptions_pricing["day_setup_fee"] = $pricing["dsetupfee"];
			}
			if ($pricing["monthly"] >= 0 && in_array("monthly", $configoptions_cycle)) {
				$configoptions_pricing["monthly"] = $pricing["monthly"];
				$configoptions_pricing["monthly_setup_fee"] = $pricing["msetupfee"];
			}
			if ($pricing["quarterly"] >= 0 && in_array("quarterly", $configoptions_cycle)) {
				$configoptions_pricing["quarterly"] = $pricing["quarterly"];
				$configoptions_pricing["quarterly_setup_fee"] = $pricing["qsetupfee"];
			}
			if ($pricing["semiannually"] >= 0 && in_array("semiannually", $configoptions_cycle)) {
				$configoptions_pricing["semiannually"] = $pricing["semiannually"];
				$configoptions_pricing["semiannually_setup_fee"] = $pricing["ssetupfee"];
			}
			if ($pricing["annually"] >= 0 && in_array("annually", $configoptions_cycle)) {
				$configoptions_pricing["annually"] = $pricing["annually"];
				$configoptions_pricing["annually_setup_fee"] = $pricing["asetupfee"];
			}
			if ($pricing["biennially"] >= 0 && in_array("biennially", $configoptions_cycle)) {
				$configoptions_pricing["biennially"] = $pricing["biennially"];
				$configoptions_pricing["biennially_setup_fee"] = $pricing["bsetupfee"];
			}
			if ($pricing["triennially"] >= 0 && in_array("triennially", $configoptions_cycle)) {
				$configoptions_pricing["triennially"] = $pricing["triennially"];
				$configoptions_pricing["triennially_setup_fee"] = $pricing["tsetupfee"];
			}
			if ($pricing["fourly"] >= 0 && in_array("fourly", $configoptions_cycle)) {
				$configoptions_pricing["fourly"] = $pricing["fourly"];
				$configoptions_pricing["fourly_setup_fee"] = $pricing["foursetupfee"];
			}
			if ($pricing["fively"] >= 0 && in_array("fively", $configoptions_cycle)) {
				$configoptions_pricing["fively"] = $pricing["fively"];
				$configoptions_pricing["fively_setup_fee"] = $pricing["fivesetupfee"];
			}
			if ($pricing["sixly"] >= 0 && in_array("sixly", $configoptions_cycle)) {
				$configoptions_pricing["sixly"] = $pricing["sixly"];
				$configoptions_pricing["sixly_setup_fee"] = $pricing["sixsetupfee"];
			}
			if ($pricing["sevenly"] >= 0 && in_array("sevenly", $configoptions_cycle)) {
				$configoptions_pricing["sevenly"] = $pricing["sevenly"];
				$configoptions_pricing["sevenly_setup_fee"] = $pricing["sevensetupfee"];
			}
			if ($pricing["eightly"] >= 0 && in_array("eightly", $configoptions_cycle)) {
				$configoptions_pricing["eightly"] = $pricing["eightly"];
				$configoptions_pricing["eightly_setup_fee"] = $pricing["eightsetupfee"];
			}
			if ($pricing["ninely"] >= 0 && in_array("ninely", $configoptions_cycle)) {
				$configoptions_pricing["ninely"] = $pricing["ninely"];
				$configoptions_pricing["ninely_setup_fee"] = $pricing["ninesetupfee"];
			}
			if ($pricing["tenly"] >= 0 && in_array("tenly", $configoptions_cycle)) {
				$configoptions_pricing["tenly"] = $pricing["tenly"];
				$configoptions_pricing["tenly_setup_fee"] = $pricing["tensetupfee"];
			}
			if ($pricing["ontrial"] >= 0 && in_array("ontrial", $configoptions_cycle)) {
				$configoptions_pricing["ontrial"] = $pricing["ontrial"];
				$configoptions_pricing["ontrial_setup_fee"] = $pricing["ontrialfee"];
			}
			if ($pricing["onetime"] >= 0 && in_array("onetime", $configoptions_cycle)) {
				$configoptions_pricing["onetime"] = $pricing["onetime"];
				$configoptions_pricing["onetime_setup_fee"] = $pricing["osetupfee"];
			}
		}
		return $configoptions_pricing;
	}
	public function productsTotal()
	{
		$param = $this->request->only(["product_id", "billingcycle", "configoption", "qty"]);
		$billingcycle = $param["billingcycle"];
		$configoption = $param["configoption"];
		$qty = isset($param["qty"]) ? intval($param["qty"]) : 1;
		$pid = intval($param["product_id"]);
		$product_filter = [];
		$total = 0;
		$cart = new \app\common\logic\Cart();
		$uid = request()->uid;
		$currency = getUserCurrency($uid);
		$currencyid = $currency["id"];
		if (!in_array($billingcycle, array_keys(config("billing_cycle")))) {
			$product_model = new \app\common\model\ProductModel();
			$billingcycle = $product_model->getProductCycle($pid, $currencyid, "", "", "", "", "", "", 1)[0]["billingcycle"] ?? "";
		}
		$product_model = new \app\common\model\ProductModel();
		if (!$product_model->checkProductPrice($pid, $billingcycle, $currencyid)) {
			return json(["status" => 400, "msg" => "Billingcycle error"]);
		}
		$setupfeecycle = $cart->changeCycleToupfee($billingcycle);
		$product = \think\Db::name("products")->alias("a")->field("a.id as productid,a.name,a.pay_type,b.*,a.api_type,a.upstream_version,a.upstream_price_type,a.upstream_price_value,a.hidden,a.stock_control,a.qty")->leftJoin("pricing b", "a.id = b.relid")->where("a.id", $pid)->where("b.type", "product")->where("b.currency", $currencyid)->find();
		if (!$product) {
			return json(["status" => 400, "msg" => "Product Dose Not Exist"]);
		}
		if ($product["api_type"] == "zjmf_api" && $product["upstream_price_type"] == "percent") {
			$is_ajmf_api = true;
		} else {
			$is_ajmf_api = false;
		}
		bcscale(2);
		$product_setup_fee = $product[$setupfeecycle] > 0 ? $product[$setupfeecycle] : 0;
		$product_price = $product[$billingcycle] > 0 ? $product[$billingcycle] : 0;
		$product_filter["product_setup_fee"] = bcsub($product_setup_fee, 0);
		$product_filter["product_price"] = bcsub($product_price, 0);
		$total += bcmul($product_setup_fee, $qty);
		$total += bcmul($product_price, $qty);
		$flag = getSaleProductUser($pid, $uid);
		$bates = 0;
		if ($flag["type"] == 1) {
			$bates = 1 - $flag["bates"] / 100;
			$saletotal = bcsub($total, bcmul($total, $bates, 2), 2);
		} else {
			$saletotal = $total;
		}
		$configoptions_logic = new \app\common\logic\ConfigOptions();
		$configoption = $configoptions_logic->filterConfigOptions($pid, $configoption);
		foreach ($configoption as $key => $value) {
			$option1 = \think\Db::name("product_config_options")->field("option_type,unit")->where("id", $key)->find();
			$option_type = $option1["option_type"];
			if ($option_type && $value) {
				if (!judgeQuantity($option_type)) {
					$option = \think\Db::name("product_config_options_sub")->alias("pcos")->field("pco.is_discount,pcos.option_name as suboption_name,pco.option_type,pco.option_name as option_name,pco.hidden,p.*,pco.is_rebate")->leftJoin("product_config_options pco", "pco.id = pcos.config_id")->leftJoin("pricing p", "p.relid = pcos.id")->where("pcos.id", $value)->where("pcos.config_id", $key)->where("p.type", "configoptions")->where("p.currency", $currencyid)->find();
					if (!$option) {
						return json(["status" => 400, "msg" => "Operate error"]);
					} else {
						$optionprice = $option[$billingcycle] > 0 ? $option[$billingcycle] : 0;
						$optionupfee = $option[$setupfeecycle] > 0 ? $option[$setupfeecycle] : 0;
						$optionsaleprice = $option[$billingcycle] > 0 ? $option[$billingcycle] : 0;
						$optionsaleupfee = $option[$setupfeecycle] > 0 ? $option[$setupfeecycle] : 0;
						if ($flag && $flag["type"] == 1) {
							$optionsaleupfee = round(bcsub($optionsaleupfee, bcmul($optionsaleupfee, $bates, 2), 2), 2);
							$optionsaleprice = round(bcsub($optionsaleprice, bcmul($optionsaleprice, $bates, 2), 2), 2);
						}
						$saletotal += bcmul($optionsaleupfee, $qty);
						$saletotal += bcmul($optionsaleprice, $qty);
						$total += bcmul($optionupfee, $qty);
						$total += bcmul($optionprice, $qty);
					}
				} else {
					$options = \think\Db::name("product_config_options_sub")->alias("pcos")->field("pcos.option_name as suboption_name,pcos.qty_minimum,pcos.qty_maximum,pco.option_type,pco.hidden,pco.option_name as option_name,pco.qty_minimum as min,pco.qty_maximum as max,pco.is_discount,p.*,pco.is_rebate")->leftJoin("product_config_options pco", "pco.id = pcos.config_id")->leftJoin("pricing p", "p.relid = pcos.id")->where("pcos.config_id", $key)->where("p.type", "configoptions")->where("currency", $currencyid)->select();
					if (!empty($options[0])) {
						foreach ($options as $option) {
							$min = $option["qty_minimum"];
							$max = $option["qty_maximum"];
							if ($value > 0 && $option["min"] <= $value && $value <= $option["max"] && $min <= $value && $value <= $max) {
								$optionprice = $option[$billingcycle] <= 0 ? 0 : $option[$billingcycle] * $value;
								$optionupfee = $option[$setupfeecycle] <= 0 ? 0 : $option[$setupfeecycle];
								$optionsaleprice = $option[$billingcycle] <= 0 ? 0 : $option[$billingcycle] * $value;
								$optionsaleupfee = $option[$setupfeecycle] <= 0 ? 0 : $option[$setupfeecycle];
								if ($flag && $option["is_discount"] == 1 && $flag["type"] == 1) {
									if (judgeQuantityStage($option_type)) {
										$sum = quantityStagePrice($key, $currencyid, $value, $billingcycle);
										$optionprice = $sum[0];
										$optionupfee = $sum[1];
										$optionsaleprice = $sum[0];
										$optionsaleupfee = $sum[1];
									}
								} else {
									if (judgeQuantityStage($option_type)) {
										$sum = quantityStagePrice($key, $currencyid, $value, $billingcycle);
										$optionprice = $sum[0];
										$optionupfee = $sum[1];
										$optionsaleprice = $sum[0];
										$optionsaleupfee = $sum[1];
									}
								}
								if ($flag && $flag["type"] == 1) {
									$optionsaleupfee = round(bcsub($optionsaleupfee, bcmul($optionsaleupfee, $bates, 2), 2), 2);
									$optionsaleprice = round(bcsub($optionsaleprice, bcmul($optionsaleprice, $bates, 2), 2), 2);
								}
								$saletotal += bcmul($optionsaleupfee, $qty);
								$saletotal += bcmul($optionsaleprice, $qty);
								$total += bcmul($optionupfee, $qty);
								$total += bcmul($optionprice, $qty);
							}
						}
					}
				}
			}
		}
		if ($is_ajmf_api) {
			$total = bcmul($total, $product["upstream_price_value"]) / 100;
			$saletotal = bcmul($saletotal, $product["upstream_price_value"]) / 100;
		}
		$return = ["total" => $total, "sale_total" => $saletotal];
		return json(["status" => 200, "msg" => "Success message", "data" => $return]);
	}
	public function addProducts()
	{
		$uid = request()->uid;
		if (!buyProductMustBindPhone($uid)) {
			return json(["status" => 400, "msg" => "Need to bind a mobile number"]);
		}
		$rule = ["product_id" => "require|number", "configoption" => "array", "customfield" => "array", "currecncyid" => "number", "qty" => "number"];
		$msg = ["product_id.require" => "Product ID is require", "product_id.number" => "Product ID is number", "configoption.array" => "Configoption is array", "customfield.array" => "Custom fields is array", "qty.number" => "Quantity is number"];
		$param = $this->request->param();
		$validate = new \think\Validate($rule, $msg);
		$result = $validate->check($param);
		if (!$result) {
			return json(["status" => 400, "msg" => $validate->getError()]);
		}
		$pid = $param["product_id"];
		$billingcycle = $param["billingcycle"];
		$configoption = $param["configoption"];
		$customfield = $param["customfield"];
		$currency = getUserCurrency($uid);
		$currencyid = $currency["id"];
		if (empty($billingcycle)) {
			$product_model = new \app\common\model\ProductModel();
			$billingcycle = $product_model->getProductCycle($pid, $currencyid, "", "", "", "", "", "", 1)[0]["billingcycle"] ?? "";
		}
		$qty = $param["qty"] ?: 1;
		if (empty($qty)) {
			return json(["status" => 400, "msg" => "Quantity is greater than 0"]);
		}
		$os = isset($param["os"]) ? $param["os"] : [];
		$shop = new \app\common\logic\Shop($uid);
		$product = \think\Db::name("products")->field("host,password,name,is_truename,stock_control,qty,zjmf_api_id,upstream_pid,api_type")->where("id", $pid)->find();
		if (!judgeOntrialNum($pid, $uid, $qty) && $billingcycle == "ontrial") {
			return json(["status" => 400, "msg" => "Exceeded number of trials"]);
		}
		if (!empty($product["stock_control"]) && $product["qty"] <= 0) {
			return json(["msg" => "Inventory shortage", "status" => 400]);
		}
		if (!empty($product["stock_control"]) && $product["qty"] < $qty) {
			return json(["msg" => "Inventory shortage", "status" => 400]);
		}
		if ($product["api_type"] == "zjmf_api" || $product["api_type"] == "resource") {
			$result = zjmfCurl($product["zjmf_api_id"], "cart/stock_control", ["pid" => $product["upstream_pid"]], 30, "GET");
			if ($result["status"] == 200) {
				$upstream_data = $result["data"];
				if (empty($upstream_data["product"])) {
					return json(["status" => 400, "msg" => "Inventory shortage"]);
				}
				if ($upstream_data["product"]["hidden"] == 1) {
					\think\Db::name("products")->where("id", $pid)->update(["hidden" => 1]);
					return json(["status" => 400, "msg" => "Product does not exist"]);
				}
				if ($upstream_data["product"]["stock_control"] && $upstream_data["product"]["qty"] <= 0) {
					return json(["status" => 400, "msg" => "Inventory shortage"]);
				}
			}
		}
		$host_data = json_decode($product["host"], true);
		if ($host_data["show"] == 1 && !isset($param["host"])) {
			return json(["status" => 400, "msg" => "Please fill in the hostname"]);
		}
		$host = $param["host"];
		if ($host_data["show"] == 1 && isset($param["host"])) {
			$check_res = verifyHostname($pid, $host);
			if ($check_res["status"] == 400) {
				return json($check_res);
			}
		}
		$password_data = json_decode($product["password"], true);
		if ($password_data["show"] == 1 && !isset($param["password"])) {
			return json(["status" => 400, "msg" => "Please fill in the password"]);
		}
		$password = $param["password"];
		if ($password_data["show"] == 1 && isset($param["password"])) {
			$check_res2 = $shop->checkHostPassword1($password, $pid);
			if ($check_res2["status"] == 400) {
				return json($check_res2);
			}
		}
		$hostid = intval($param["hostid"]);
		$res = $shop->addProduct($pid, $billingcycle, 0, $configoption, $customfield, $currencyid, $qty, $os, $host, $password, $hostid);
		if ($res["status"] == "success") {
			return json(["status" => 200, "msg" => "Added successfully"]);
		} else {
			return json(["status" => 400, "msg" => $res["msg"]]);
		}
	}
	public function handleLinkAgeLevel($data)
	{
		$req = $this->request;
		if (!$data) {
			return $data;
		}
		$data = array_column($data, null, "id");
		$configOption = new \app\common\logic\ConfigOptions();
		foreach ($data as $k => $v) {
			if ($v["option_type"] != 20 || $v["linkage_pid"] != 0) {
				continue;
			}
			$req->cid = $cid = $v["id"];
			$all_list = $configOption->webGetLinkAgeList($req);
			$linkAge = $configOption->webSetLinkAgeListDefaultVal($all_list, $req);
			$linkAge_ids = $linkAge ? array_column($linkAge, "id") : [];
			foreach ($linkAge as $val) {
				if (isset($data[$val["id"]])) {
					$data[$val["id"]]["checkSubId"] = $val["checkSubId"];
				}
			}
			$data = array_filter($data, function ($v) use($linkAge_ids, $cid) {
				if ($v["option_type"] != 20) {
					return true;
				}
				if ($v["linkage_top_pid"] != $cid) {
					return true;
				}
				if (in_array($v["id"], $linkAge_ids)) {
					return true;
				}
				return false;
			});
		}
		return $configOption->getTree($data);
	}
	public function handleTreeArr($data)
	{
		if (!$data) {
			return $data;
		}
		foreach ($data as $key => $val) {
			if (isset($val["son"]) && $val["son"]) {
				$data[$key]["son"] = changeTwoArr($val["son"]);
			}
		}
		return $data;
	}
	public function cartPage()
	{
		$param = $this->request->param();
		$pos = [];
		if (isset($param["position"]) && is_array($param["position"]) && !empty($param["position"])) {
			$pos = $param["position"];
		}
		$uid = request()->uid;
		$shop = new \app\common\logic\Shop($uid);
		$pagedata = $shop->getShopPageDataOpenAPI(0, $pos);
		$pagedata["gateway_list"] = gateway_list_openapi("gateways");
		$pagedata["default_gateway"] = getGateway($uid);
		if ($uid) {
			$client = \think\Db::name("clients")->field("username,email,address1,phonenumber,credit,credit_limit,is_open_credit_limit")->where("id", $uid)->find();
			$client["is_open_credit_limit"] = configuration("shd_credit_limit") ? configuration("credit_limit") == 1 ? $client["is_open_credit_limit"] : 0 : 0;
			$client["amount_to_be_settled"] = \think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", $uid)->sum("total");
			$unpaid = \think\Db::name("invoices")->where("type", "credit_limit")->where("status", "Unpaid")->where("is_delete", 0)->where("uid", $uid)->sum("total");
			$client["credit_limit_used"] = round($client["amount_to_be_settled"] + $unpaid, 2);
			$client["credit_limit_balance"] = round($client["credit_limit"] - $client["credit_limit_used"] > 0 ? $client["credit_limit"] - $client["credit_limit_used"] : 0, 2);
			unset($client["credit_limit"]);
			unset($client["amount_to_be_settled"]);
			unset($client["credit_limit_used"]);
			$pagedata["client"] = $client;
		}
		return json(["status" => 200, "msg" => "Success message", "data" => $pagedata]);
	}
	public function cartRemove()
	{
		$param = $this->request->param();
		$uid = request()->uid;
		$position = [intval($param["position"])];
		$shop = new \app\common\logic\Shop($uid);
		$shop->removeProduct($position);
		return json(["status" => 200, "msg" => "Success message"]);
	}
	public function cartClear()
	{
		$uid = request()->uid;
		$cart_data = \think\Db::name("cart_session")->where("uid", $uid)->value("cart_data");
		$cart_data = json_decode($cart_data, true)["products"] ?: [];
		\think\Db::name("cart_session")->where("uid", $uid)->update(["cart_data" => ""]);
		if (!empty($cart_data)) {
			$hook_data = [];
			foreach ($cart_data as $v) {
				$hook_data[] = ["pid" => $v["pid"], "billingcycle" => $v["billingcycle"], "num" => $v["num"]];
			}
			hook("shopping_cart_clear", ["data" => $hook_data]);
		}
		return json(["status" => 200, "msg" => "Success message"]);
	}
	public function cartAddPromo()
	{
		$param = $this->request->param();
		$promo = $param["promo"];
		if (empty($promo)) {
			return json(["status" => 400, "msg" => "Promo code is require"]);
		}
		$uid = $this->request->uid;
		$shop = new \app\common\logic\Shop($uid);
		$res = $shop->addPromo($promo);
		if ($res["status"] != "success") {
			return json(["status" => 400, "msg" => $res["msg"]]);
		}
		return json(["status" => 200, "msg" => "Coupon code added successfully"]);
	}
	public function cartRemovePromo()
	{
		$param = $this->request->param();
		$uid = $this->request->uid;
		$shop = new \app\common\logic\Shop($uid);
		$res = $shop->removePromo();
		if ($res["status"] != "success") {
			return json(["status" => 400, "msg" => $res["msg"]]);
		}
		return json(["status" => 200, "msg" => "Successfully removed coupon code"]);
	}
	public function cartCheckout()
	{
		$uid = $this->request->uid;
		$payment = input("post.payment", "");
		$checkout = input("post.checkout", 0);
		$default_payment = \think\Db::name("clients")->where("id", $uid)->value("defaultgateway");
		$user_info = \think\Db::name("clients")->where("id", $uid)->find();
		$cart = \think\Db::name("cart_session")->where("uid", $uid)->find();
		$cart_data = $remain_data = json_decode($cart["cart_data"], true);
		$pos_param = $this->request->param();
		$cart_products_filter = [];
		if (isset($pos_param["position"]) && is_array($pos_param["position"]) && !empty($pos_param["position"])) {
			$pos = $pos_param["position"];
			if (!empty($cart_data["products"])) {
				foreach ($pos as $n) {
					if (isset($cart_data["products"][$n])) {
						$cart_products_filter[$n] = $cart_data["products"][$n];
					}
				}
			}
		}
		$new_cart_data = "";
		if (!empty($cart_products_filter)) {
			$diff = array_diff_key($remain_data["products"], $cart_products_filter);
			$remain_data["products"] = [];
			foreach ($diff as $nn => $mm) {
				$remain_data["products"][] = $mm;
			}
			if (!empty($remain_data)) {
				$new_cart_data = json_encode($remain_data);
			}
			$cart_data["products"] = $cart_products_filter;
		}
		if (!empty($pos_param["cart_data"])) {
			\think\Db::name("cart_session")->where("uid", $uid)->update(["cart_data" => "", "update_time" => time()]);
			$cart_data = [];
			$cart_data["products"][0] = $pos_param["cart_data"];
		}
		if (empty($cart_data["products"])) {
			$result["status"] = 400;
			$result["msg"] = "Cart cannot be empty";
			return json($result);
		}
		$prod = [];
		foreach ($cart_data["products"] as $k => $value) {
			$product = \think\Db::name("products")->field("id,name,is_truename,clientscount,api_type,upstream_pid,zjmf_api_id,pay_type")->where("id", $value["pid"])->find();
			$api = \think\Db::name("api_user_product")->field("ontrial,qty")->where("uid", $uid)->where("pid", $value["pid"])->find();
			if (!empty($api)) {
				$product["clientscount"] = intval($api["qty"]);
			}
			if ($product["api_type"] == "zjmf_api") {
				$res = zjmfCurl($product["zjmf_api_id"], "cart/ontrialmax", ["pid" => $product["upstream_pid"]], 5, "GET");
				if (!empty($res["data"])) {
					$product["clientscount"] = intval($res["data"]["product"]["qty"]);
				}
			}
			if (empty($prod[$value["pid"]])) {
				$prod[$value["pid"]]["name"] = $product["name"];
				$prod[$value["pid"]]["clientscount"] = $product["clientscount"];
				$prod[$value["pid"]]["qty"] = $value["qty"];
			} else {
				$prod[$value["pid"]]["qty"] += $value["qty"];
			}
			$pay_type = json_decode($product["pay_type"], true);
			$prod[$value["pid"]]["clientscount_rule"] = !getEdition() ? 0 : $pay_type["clientscount_rule"] ?? 0;
		}
		foreach ($prod as $k => $value) {
			if ($value["clientscount"] > 0) {
				$pay_ontrial_num_rule = $value["clientscount_rule"];
				$whereMap = [];
				if ($pay_ontrial_num_rule) {
					$whereMap["domainstatus"] = "Active";
				}
				$productcounbt = \think\Db::name("host")->field("id")->where("productid", $k)->where("uid", $uid)->where($whereMap)->count();
				if ($value["clientscount"] < $productcounbt + $value["qty"]) {
					$result["status"] = 400;
					$result["msg"] = "Exceeds the amount customers can buy";
					return json($result);
				}
			}
		}
		$msg = $msg1 = $res_msg1 = $res_msg2 = [];
		$flag = checkCertify($uid);
		$flag1 = $user_info["phonenumber"];
		foreach ($cart_data["products"] as $k => $value) {
			$product = \think\Db::name("products")->field("name,is_truename,is_bind_phone")->where("id", $value["pid"])->find();
			if (!$flag && ($product["is_truename"] == 1 || configuration("certifi_isrealname") == 1)) {
				$msg[] = $product["name"];
			}
			if (!$flag1 && $product["is_bind_phone"] == 1) {
				$msg1[] = $product["name"];
			}
		}
		if (!$flag && count($msg) >= 1) {
			$msg = array_unique($msg);
			$res_msg1 = ["status" => 410, "msg" => "The product you purchased requires real-name authentication, please complete the real-name authentication before purchasing"];
		}
		if (!$flag1 && count($msg1) >= 1) {
			$res_msg2 = ["status" => 415, "msg" => "The product you purchased needs to be bound with a mobile phone number, please bind the mobile phone number before purchasing"];
		}
		if ($res_msg1 && $res_msg2) {
			$res_msg["status"] = $res_msg1["status"];
			$res_msg["msg"] = $res_msg1["msg"];
			$res_msg["msg_phone"] = $res_msg2["msg"];
			return json($res_msg);
		} else {
			if ($res_msg1) {
				return json($res_msg1);
			} else {
				if ($res_msg2) {
					return json($res_msg2);
				}
			}
		}
		$gateway = gateway_list1();
		if (!empty($payment) && !in_array($payment, array_column($gateway, "name"))) {
			$result["status"] = 400;
			$result["msg"] = "Wrong payment method";
			return json($result);
		}
		$product_items = [];
		$promo_error = "";
		$product_error = [];
		$create_invoice = false;
		if (!empty($cart_data["promo"]) && $checkout == 0) {
			$promo = \think\Db::name("promo_code")->where("code", $cart_data["promo"])->find();
			if (!empty($promo)) {
				if ($promo["start_time"] > 0 && $promo["start_time"] > time()) {
					$promo_error = "优惠码还未发放";
					$promo = [];
				}
				if ($promo["max_times"] != 0 && $promo["used"] >= $promo["max_times"] || $promo["expiration_time"] > 0 && $promo["expiration_time"] <= time()) {
					$promo_error = "优惠码已过期";
					$promo = [];
				}
				$has_active_order = \think\Db::name("orders")->where("uid", $uid)->find();
				if (!empty($promo["only_new_client"]) && !empty($has_active_order)) {
					$promo_error = "优惠码只适用于新用户";
					$promo = [];
				}
				$has_active_order = \think\Db::name("orders")->where("uid", $uid)->where("status", "Active")->find();
				if (!empty($promo["only_old_client"]) && empty($has_active_order)) {
					$promo_error = "优惠码只适用于老用户";
					$promo = [];
				}
				if (!empty($promo["once_per_client"])) {
					if (\think\Db::name("orders")->where("uid", $uid)->where("promo_code", $cart_data["promo"])->find()) {
						$promo_error = "已使用过该优惠码";
						$promo = [];
					}
				}
				if (!empty($promo["requires"])) {
					$need_pid = explode(",", $promo["requires"]);
					$now_pid = array_column($cart_data["products"], "pid");
					if (!empty($promo["requires_exist"])) {
						$has_products = \think\Db::name("host")->field("productid")->where("uid", $uid)->where("domainstatus", "Active")->select()->toArray();
						$has_products = array_column($has_products, "productid") ?: [];
						$now_pid = array_merge($now_pid, $has_products);
					}
					$intersect = array_intersect($need_pid, $now_pid);
					if (empty($intersect)) {
						$promo_error = "不满足使用该优惠码条件";
						$promo = [];
					}
				}
				if (!empty($promo)) {
					$promo["appliesto"] = explode(",", $promo["appliesto"]);
				}
			}
		}
		$currency_info = \think\Db::name("currencies")->field("id")->where("id", $user_info["currency"])->find();
		if (empty($currency_info)) {
			$currency_info = \think\Db::name("currencies")->field("id")->where("default", 1)->find();
		}
		$currency = $currency_info["id"];
		$total = 0;
		$products = [];
		$price_type = config("price_type");
		bcscale(2);
		$auth_id = 0;
		$app_id = 0;
		$productModel = new \app\common\model\ProductModel();
		foreach ($cart_data["products"] as $k => $v) {
			$qty = $v["qty"];
			$checkProcut = $productModel->checkProductPrice($v["pid"], $v["billingcycle"], $currency);
			if (!$checkProcut) {
				return json(["status" => 400, "msg" => "Cycle is error"]);
			}
			if ($v["billingcycle"] == "free") {
				$product = \think\Db::name("products")->where("id", $v["pid"])->find();
			} else {
				$product_price_type = $price_type[$v["billingcycle"]];
				if (empty($product_price_type)) {
					$result["status"] = 400;
					$result["msg"] = "Cycle is error";
					return json($result);
				}
				$product_price_field = "b." . implode(",b.", $product_price_type);
				$product = \think\Db::name("products")->alias("a")->field("a.*," . $product_price_field)->leftJoin("pricing b", "b.type=\"product\" and a.id=b.relid and currency=" . $currency)->where("a.id", $v["pid"])->find();
			}
			$products[] = $product;
			if (empty($product)) {
				$result["status"] = 400;
				$result["msg"] = "ID is Error";
				return json($result);
			}
			if (!judgeOntrialNum($v["pid"], $uid, $qty, false, true) && $v["billingcycle"] == "ontrial") {
				return json(["status" => 400, "msg" => "The product you purchased exceeds the trial quantity limit, please re-select this product cycle"]);
			}
			$pay_type = json_decode($product["pay_type"], true);
			if (!empty($pay_type["pay_ontrial_condition"]) && $v["billingcycle"] == "ontrial") {
				$one_error = [];
				foreach ($pay_type["pay_ontrial_condition"] as $vv) {
					if ($vv == "realname" && !checkCertify($uid)) {
						$one_error[] = "实名认证";
					}
					if ($vv == "email" && empty($user_info["email"])) {
						$one_error[] = "邮箱验证";
					}
					if ($vv == "phone" && empty($user_info["phonenumber"])) {
						$one_error[] = "手机验证";
					}
					if ($vv == "wechat" && empty($user_info["wechat_id"])) {
						$one_error[] = "微信验证";
					}
				}
				if (!empty($one_error)) {
					$product_error[] = "产品" . $product["name"] . ",试用需要" . implode(",", $one_error);
				}
			}
			if (!empty($product_error)) {
				continue;
			}
			if (!empty($product["retired"])) {
				$result["status"] = 400;
				$result["msg"] = lang("CART_SETTLE_PRO_RETIRED", [$product["name"]]);
				return json($result);
			}
			if (!empty($product["stock_control"]) && $product["qty"] <= 0) {
				$result["status"] = 400;
				$result["msg"] = lang("CART_SETTLE_PRO_STOCK_CONTROL", [$product["name"]]);
				return json($result);
			}
			$nextduedate = time();
			$customfields = \think\Db::name("customfields")->where("relid", $v["pid"])->where("type", "product")->order("sortorder", "asc")->select()->toArray();
			$item_desc = [];
			$_products = \think\Db::name("products")->field("server_group as gid")->where("id", $v["pid"])->find();
			$server = [];
			if ($_products) {
				$server = getServesId($_products["gid"]);
			}
			if ($pay_type["pay_type"] == "free") {
				$v["billingcycle"] = "free";
				$product_item = ["uid" => $uid, "productid" => $v["pid"], "serverid" => $server["id"] ?? 0, "regdate" => time(), "payment" => $payment, "firstpaymentamount" => 0, "amount" => 0, "billingcycle" => $v["billingcycle"], "domainstatus" => "Pending", "create_time" => time(), "auto_terminate_reason" => "", "product_config" => [], "customfields" => [], "dcim_os" => array_keys($v["os"])[0] ?? 0, "os" => array_values($v["os"])[0] ?? "", "host" => $v["host"] ?? "", "password" => $v["password"] ?? "", "qty" => $qty, "percent_value" => $product["upstream_price_value"]];
				$item_desc[] = $product["name"] . " (" . date("Y-m-d H", time()) . " - ) ";
				foreach ($customfields as $ck => $cv) {
					if (isset($v["customfield"][$cv["id"]])) {
						$product_item["customfields"][] = ["fieldid" => $cv["id"], "value" => $v["customfield"][$cv["id"]]];
					}
				}
				$developer_app = checkDeveloperApp($v["pid"]);
				if (!empty($developer_app)) {
					$host_custom = \think\Db::name("customfields")->field("id")->where("type", "product")->where("relid", $v["pid"])->where("fieldname", "hostid")->order("id", "asc")->find();
					if (!empty($host_custom)) {
						$app_custom = ["fieldid" => $host_custom["id"], "value" => $v["hostid"]];
						$product_item["customfields"][] = $app_custom;
					}
					$auth_id = $v["hostid"];
					$app_id = $v["pid"];
				}
				$config_price = $productModel->getConfigOptionsPrice($v["pid"], $currency, $product_price_type);
				if (!empty($v["configoptions"])) {
					foreach ($config_price as $kkk => $vvv) {
						if (isset($v["configoptions"][$vvv["id"]])) {
							if (judgeOs($vvv["option_type"])) {
								$configoptions_logic = new \app\common\logic\ConfigOptions();
								$os = $configoptions_logic->getOs($vvv["id"], $v["configoptions"][$vvv["id"]]);
								$product_item["os"] = $os["os"] ?? "";
								$product_item["os_url"] = $os["os_url"] ?? "";
							}
							if (judgeQuantity($vvv["option_type"])) {
								if ($v["configoptions"][$vvv["id"]] < $vvv["qty_minimum"]) {
									$v["configoptions"][$vvv["id"]] = $vvv["qty_minimum"];
								}
								if ($v["configoptions"][$vvv["id"]] > $vvv["qty_maximum"]) {
									$v["configoptions"][$vvv["id"]] = $vvv["qty_minimum"];
								}
								$sub_id = 0;
								foreach ($vvv["sub"] as $kkkk => $vvvv) {
									if ($sub_id == 0) {
										$sub_id = $kkkk;
									}
									if (strpos($vvvv["option_name"], "-") !== false) {
										$range = explode("-", $vvvv["option_name"]);
										if (is_numeric($range[0]) && is_numeric($range[1]) && $range[1] >= $v["configoptions"][$vvv["id"]] && $v["configoptions"][$vvv["id"]] >= $range[0]) {
											$sub_id = $kkkk;
											break;
										}
									}
								}
								if ($sub_id > 0) {
									$product_item["product_config"][] = ["configid" => $vvv["id"], "optionid" => $sub_id, "qty" => $v["configoptions"][$vvv["id"]]];
								}
							} else {
								if (isset($vvv["sub"][$v["configoptions"][$vvv["id"]]])) {
									$product_item["product_config"][] = ["configid" => $vvv["id"], "optionid" => $v["configoptions"][$vvv["id"]], "qty" => 0];
								}
							}
						}
					}
				}
				$product_items[] = $product_item;
			} else {
				if (is_numeric($product[$v["billingcycle"]]) && $product[$v["billingcycle"]] == -1) {
					$result["status"] = 400;
					$result["msg"] = lang("CART_SETTLE_PRO_BILL_ERROR");
					return json($result);
				}
				$create_invoice = true;
				$product_item = ["uid" => $uid, "productid" => $v["pid"], "serverid" => $server["id"] ?? 0, "regdate" => time(), "payment" => $payment, "billingcycle" => $v["billingcycle"], "nextduedate" => $nextduedate, "nextinvoicedate" => $nextduedate, "domainstatus" => "Pending", "create_time" => time(), "auto_terminate_reason" => "", "invoices_items" => [], "product_config" => [], "customfields" => [], "dcim_os" => array_keys($v["os"])[0] ?? 0, "os" => array_values($v["os"])[0] ?? "", "host" => $v["host"] ?? "", "password" => $v["password"] ?? "", "qty" => $qty, "percent_value" => $product["upstream_price_value"]];
				$next_time = getNextTime($v["billingcycle"], $pay_type["pay_" . $v["billingcycle"] . "_cycle"], 0, $pay_type["pay_ontrial_cycle_type"] ?: "day");
				$item_desc = $item_desc_home = [];
				if ($pay_type["pay_type"] == "onetime") {
					$item_desc[] = $item_desc_home[] = $product["name"];
				} else {
					$item_desc[] = $item_desc_home[] = $product["name"] . " (" . date("Y-m-d H", time()) . " - " . date("Y-m-d H", $next_time) . ") ";
				}
				foreach ($customfields as $ck => $cv) {
					if (isset($v["customfield"][$cv["id"]])) {
						$product_item["customfields"][] = ["fieldid" => $cv["id"], "value" => $v["customfield"][$cv["id"]]];
					}
				}
				$developer_app = checkDeveloperApp($v["pid"]);
				if (!empty($developer_app)) {
					$host_custom = \think\Db::name("customfields")->field("id")->where("type", "product")->where("relid", $v["pid"])->where("fieldname", "hostid")->order("id", "asc")->find();
					if (!empty($host_custom)) {
						$app_custom = ["fieldid" => $host_custom["id"], "value" => $v["hostid"]];
						$product_item["customfields"][] = $app_custom;
					}
					$auth_id = $v["hostid"];
					$app_id = $v["pid"];
				}
				$price_setup = $product[$product_price_type[1]];
				$price_cycle = $product[$product_price_type[0]];
				if ($app_id > 0 && $auth_id > 0) {
					$product = \think\Db::name("products")->field("id,professional_discount")->where("id", $app_id)->where("p_uid", ">", 0)->find();
					$activity = \think\Db::name("app_activity_rel")->alias("a")->field("a.id,b.object,b.discount")->leftJoin("app_activity b", "b.id=a.activity_id")->where("b.start_time", "<=", time())->where("b.end_time", ">=", time())->where("a.pid", $app_id)->find();
					$auth = \think\Db::name("host")->alias("a")->field("a.id,b.config_option2")->leftJoin("products b", "b.id=a.productid")->where("a.id", $auth_id)->find();
					if (!empty($auth) && $auth["config_option2"] == "professional") {
						if (!empty($activity)) {
							$price_cycle = in_array($activity["object"], [0, 1]) ? round($price_cycle * (100 - $activity["discount"]) / 100, 2) : $price_cycle;
						}
						$price_cycle = round($price_cycle * (100 - $product["professional_discount"]) / 100, 2);
					} else {
						if (!empty($activity)) {
							$price_cycle = in_array($activity["object"], [0, 2]) ? round($price_cycle * (100 - $activity["discount"]) / 100, 2) : $price_cycle;
						}
					}
				}
				$product_base_sale = $product[$product_price_type[1]] + $product[$product_price_type[0]];
				$product_base_sale_setupfee = $product[$product_price_type[1]];
				$product_base_sale_price = $product[$product_price_type[0]];
				$product_rebate_price = $product[$product_price_type[0]];
				$product_rebate_setupfee = $product[$product_price_type[1]];
				$edition = getEdition();
				$config_price = $productModel->getConfigOptionsPrice($v["pid"], $currency, $product_price_type);
				$configoptions_base_sale = [];
				if (!empty($v["configoptions"])) {
					foreach ($config_price as $kkk => $vvv) {
						if (isset($v["configoptions"][$vvv["id"]])) {
							if (judgeOs($vvv["option_type"])) {
								$configoptions_logic = app("app\\common\\logic\\ConfigOptions");
								$os = $configoptions_logic->getOs($vvv["id"], $v["configoptions"][$vvv["id"]]);
								$product_item["os"] = $os["os"] ?? "";
								$product_item["os_url"] = $os["os_url"] ?? "";
							}
							if (strpos($vvv["option_name"], "|") !== false) {
								$item_desc_name = substr($vvv["option_name"], strpos($vvv["option_name"], "|"));
							} else {
								$item_desc_name = $vvv["option_name"];
							}
							if (judgeQuantity($vvv["option_type"])) {
								if ($v["configoptions"][$vvv["id"]] < $vvv["qty_minimum"]) {
									$v["configoptions"][$vvv["id"]] = $vvv["qty_minimum"];
								}
								if ($v["configoptions"][$vvv["id"]] > $vvv["qty_maximum"]) {
									$v["configoptions"][$vvv["id"]] = $vvv["qty_minimum"];
								}
								$sub_price_setup = 0;
								$sub_price_cycle = 0;
								$config_base_sale = 0;
								$config_base_sale_setupfee = 0;
								$sub_id = 0;
								foreach ($vvv["sub"] as $kkkk => $vvvv) {
									if ($sub_price_setup === "") {
										$sub_id = $kkkk;
										$sub_price_setup = $vvvv["price_setup"];
										$sub_price_cycle = $vvvv["price_cycle"];
									}
									if ($v["configoptions"][$vvv["id"]] >= $vvvv["qty_minimum"] && $v["configoptions"][$vvv["id"]] <= $vvvv["qty_maximum"]) {
										$sub_price_setup = $vvvv["price_setup"];
										$sub_price_cycle = $vvvv["price_cycle"];
										$sub_id = $kkkk;
										break;
									}
								}
								if ($sub_id > 0) {
									$item_desc_name .= ": " . $v["configoptions"][$vvv["id"]];
									$sub_price_setup = $sub_price_setup < 0 ? 0 : $sub_price_setup;
									$sub_price_cycle = $sub_price_cycle < 0 ? 0 : $sub_price_cycle;
									if ($vvv["hidden"] != 1) {
										if (judgeQuantityStage($vvv["option_type"])) {
											$sum = quantityStagePrice($vvv["id"], $currency, $v["configoptions"][$vvv["id"]], $v["billingcycle"]);
											$price_cycle = bcadd($price_cycle, $sum[0]);
											$price_setup = bcadd($price_setup, $sum[1]);
											$config_base_sale = $sum[0] + $sum[1];
											$config_base_sale_setupfee = $sum[1];
										} else {
											if (intval($v["configoptions"][$vvv["id"]]) > 0) {
												$price_setup = bcadd($price_setup, $sub_price_setup);
											}
											$price_cycle = bcadd($price_cycle, bcmul($sub_price_cycle, $v["configoptions"][$vvv["id"]]));
											$config_base_sale = (intval($v["configoptions"][$vvv["id"]]) > 0 ? $sub_price_setup : 0) + bcmul($sub_price_cycle, $v["configoptions"][$vvv["id"]]);
											$config_base_sale_setupfee = intval($v["configoptions"][$vvv["id"]]) > 0 ? $sub_price_setup : 0;
										}
									}
									$product_item["product_config"][] = ["configid" => $vvv["id"], "optionid" => $sub_id, "qty" => $v["configoptions"][$vvv["id"]]];
								}
								$configoptions_base_sale[] = ["config_base_sale" => $config_base_sale, "config_base_sale_setupfee" => $config_base_sale_setupfee, "is_discount" => $vvv["is_discount"], "id" => $vvv["id"], "is_rebate" => $vvv["is_rebate"]];
							} else {
								$config_base_sale = 0;
								$config_base_sale_setupfee = 0;
								if (isset($vvv["sub"][$v["configoptions"][$vvv["id"]]])) {
									if ($vvv["sub"][$v["configoptions"][$vvv["id"]]]["price_setup"] > 0 && $vvv["hidden"] != 1) {
										$price_setup = bcadd($price_setup, $vvv["sub"][$v["configoptions"][$vvv["id"]]]["price_setup"]);
										$config_base_sale += $vvv["sub"][$v["configoptions"][$vvv["id"]]]["price_setup"];
										$config_base_sale_setupfee = $vvv["sub"][$v["configoptions"][$vvv["id"]]]["price_setup"];
									}
									if ($vvv["sub"][$v["configoptions"][$vvv["id"]]]["price_cycle"] > 0 && $vvv["hidden"] != 1) {
										$price_cycle = bcadd($price_cycle, $vvv["sub"][$v["configoptions"][$vvv["id"]]]["price_cycle"]);
										$config_base_sale += $vvv["sub"][$v["configoptions"][$vvv["id"]]]["price_cycle"];
									}
									if (strpos($vvv["sub"][$v["configoptions"][$vvv["id"]]]["option_name"], "|") !== false) {
										$item_desc_name .= ": " . substr($vvv["sub"][$v["configoptions"][$vvv["id"]]]["option_name"], strpos($vvv["sub"][$v["configoptions"][$vvv["id"]]]["option_name"], "|"));
									} else {
										$item_desc_name .= $vvv["sub"][$v["configoptions"][$vvv["id"]]]["option_name"];
									}
									$product_item["product_config"][] = ["configid" => $vvv["id"], "optionid" => $v["configoptions"][$vvv["id"]], "qty" => judgeYesNo($vvv["option_type"]) ? 1 : 0];
									$configoptions_base_sale[] = ["config_base_sale" => $config_base_sale, "config_base_sale_setupfee" => $config_base_sale_setupfee, "is_discount" => $vvv["is_discount"], "id" => $vvv["id"], "is_rebate" => $vvv["is_rebate"]];
								}
							}
							$item_desc_name = str_replace("|", " ", $item_desc_name);
							if (empty($vvv["hidden"])) {
								$item_desc_home[] = $item_desc_name;
							}
							$item_desc[] = $item_desc_name;
						}
					}
				}
				if ($product["api_type"] == "zjmf_api" && $product["upstream_version"] > 0 && $product["upstream_price_type"] == "percent") {
					$price_setup = bcmul($price_setup, $product["upstream_price_value"]) / 100;
					$price_cycle = bcmul($price_cycle, $product["upstream_price_value"]) / 100;
					$product_base_sale = bcmul($product_base_sale, $product["upstream_price_value"]) / 100;
					$config_base_sale_setupfee = bcmul($config_base_sale_setupfee, $product["upstream_price_value"]) / 100;
					$product_base_sale_price = $product_base_sale - $config_base_sale_setupfee;
					foreach ($configoptions_base_sale as &$m) {
						$m["config_base_sale"] = bcmul($m["config_base_sale"], $product["upstream_price_value"]) / 100;
						$m["config_base_sale_setupfee"] = bcmul($m["config_base_sale_setupfee"], $product["upstream_price_value"]) / 100;
					}
					$product_rebate_price = bcmul($product_rebate_price, $product["upstream_price_value"]) / 100;
					$product_rebate_setupfee = bcmul($product_rebate_setupfee, $product["upstream_price_value"]) / 100;
				}
				if ($product["api_type"] == "resource" && function_exists("resourceUserGradePercent")) {
					$percent = resourceUserGradePercent($uid, $product["id"]) / 100;
					$price_setup = bcmul($price_setup, $percent);
					$price_cycle = bcmul($price_cycle, $percent);
					$product_base_sale = bcmul($product_base_sale, $percent);
					$config_base_sale_setupfee = bcmul($config_base_sale_setupfee, $percent);
					$product_base_sale_price = $product_base_sale - $config_base_sale_setupfee;
					foreach ($configoptions_base_sale as &$m) {
						$m["config_base_sale"] = bcmul($m["config_base_sale"], $percent);
						$m["config_base_sale_setupfee"] = bcmul($m["config_base_sale_setupfee"], $percent);
					}
					$product_rebate_price = bcmul($product_rebate_price, $percent);
					$product_rebate_setupfee = bcmul($product_rebate_setupfee, $percent);
				}
				$param = $this->request->param();
				if (isset($param["resource_percent_value"])) {
					$resource_percent_value = $param["resource_percent_value"];
					$price_setup = bcmul($price_setup, $resource_percent_value);
					$price_cycle = bcmul($price_cycle, $resource_percent_value);
					$product_base_sale = bcmul($product_base_sale, $resource_percent_value);
					$config_base_sale_setupfee = bcmul($config_base_sale_setupfee, $resource_percent_value);
					$product_base_sale_price = $product_base_sale - $config_base_sale_setupfee;
					foreach ($configoptions_base_sale as &$m) {
						$m["config_base_sale"] = bcmul($m["config_base_sale"], $resource_percent_value);
						$m["config_base_sale_setupfee"] = bcmul($m["config_base_sale_setupfee"], $resource_percent_value);
					}
					$product_rebate_price = bcmul($product_rebate_price, $resource_percent_value);
					$product_rebate_setupfee = bcmul($product_rebate_setupfee, $resource_percent_value);
				}
				$product_total_price = bcadd($price_setup, $price_cycle);
				if ($product_total_price < 0) {
					$product_total_price = 0;
				}
				if ($price_setup > 0) {
					$product_item["invoices_items"][] = ["uid" => $uid, "type" => "setup", "description" => "初装费", "description2" => "初装费", "amount" => $price_setup, "due_time" => $nextduedate, "payment" => $payment];
				}
				$product_item["invoices_items"][] = ["uid" => $uid, "type" => "host", "description" => implode("\n", $item_desc), "description2" => implode("\n", $item_desc_home) ?? "", "amount" => $price_cycle, "due_time" => $nextduedate, "payment" => $payment];
				$flag = getSaleProductUser($v["pid"], $uid);
				if ($flag) {
					$config_total = 0;
					$config_total_setupfee = 0;
					$config_total_price = 0;
					$userdiscount = 0;
					if ($flag["type"] == 1) {
						$bates = $flag["bates"];
						$userdiscount += (1 - $bates / 100) * ($product_rebate_price + $product_rebate_setupfee);
						foreach ($configoptions_base_sale as &$mm) {
							if ($mm["is_rebate"] || !$edition) {
								$userdiscount += (1 - $bates / 100) * $mm["config_base_sale"];
								$mm["config_base_sale"] = bcmul($bates / 100, $mm["config_base_sale"]);
								$mm["config_base_sale_setupfee"] = bcmul($bates / 100, $mm["config_base_sale_setupfee"]);
							}
							$config_total += $mm["config_base_sale"];
							$config_total_setupfee += $mm["config_base_sale_setupfee"];
							$config_total_price += $mm["config_base_sale"] - $mm["config_base_sale_setupfee"];
						}
						$product_base_sale = bcmul($bates / 100, $product_base_sale);
						$product_base_sale_setupfee = bcmul($bates / 100, $product_base_sale_setupfee);
						$product_base_sale_price = $product_base_sale - $product_base_sale_setupfee;
					} elseif ($flag["type"] == 2) {
						$bates = $flag["bates"];
						$product_total_rebate_price = $product_total_price;
						$product_base_sale = $product_base_sale / $product_total_price * ($product_total_price - $bates);
						$product_base_sale_setupfee = $product_base_sale_setupfee / $product_total_price * ($product_total_price - $bates);
						$product_base_sale_price = $product_base_sale - $product_base_sale_setupfee;
						foreach ($configoptions_base_sale as &$mm) {
							if ($mm["is_rebate"] || !$edition) {
								$mm["config_base_sale"] = $mm["config_base_sale"] / $product_total_price * ($product_total_price - $bates);
								$mm["config_base_sale_setupfee"] = $mm["config_base_sale_setupfee"] / $product_total_price * ($product_total_price - $bates);
							} else {
								$product_total_rebate_price = $product_total_rebate_price - $mm["config_base_sale"];
							}
							$config_total += $mm["config_base_sale"];
							$config_total_setupfee += $mm["config_base_sale_setupfee"];
							$config_total_price += $mm["config_base_sale"] - $mm["config_base_sale_setupfee"];
						}
						$userdiscount = $bates < $product_total_rebate_price ? $bates : $product_total_rebate_price;
					}
					$userdiscount = $userdiscount > 0 ? $userdiscount : 0;
					$product_item["invoices_items"][] = ["uid" => $uid, "type" => "discount", "description" => "客戶折扣", "description2" => "客戶折扣", "amount" => "-" . $userdiscount, "due_time" => $nextduedate, "payment" => $payment];
					$product_item["flag"] = 1;
					$product_item["flag_cycle"] = $v["billingcycle"];
					$product_total_price_sale = bcadd($product_base_sale, $config_total);
					$product_total_price_sale_setupfee = bcadd($product_base_sale_setupfee, $config_total_setupfee);
					$product_total_price_sale_price = bcadd($product_base_sale_price, $config_total_price);
					$total = $total + $product_total_price_sale * $qty;
				} else {
					$product_item["flag"] = 0;
					$product_item["flag_cycle"] = $v["billingcycle"];
					$product_total_price_sale = $product_total_price;
					$product_total_price_sale_setupfee = $price_setup;
					$product_total_price_sale_price = $price_cycle;
					$total = bcadd($total, $product_total_price * $qty);
				}
				if (!empty($promo) && $product_total_price_sale > 0) {
					if ($flag && $promo["is_discount"] == 0) {
						$product_item["promoid"] = 0;
					} else {
						if ((empty($promo["appliesto"][0]) || in_array($v["pid"], $promo["appliesto"])) && (empty($promo["cycles"]) || in_array($v["billingcycle"], explode(",", $promo["cycles"])))) {
							if ($promo["type"] == "percent") {
								$promo_value = $promo["value"] > 100 ? 100 : ($promo["value"] > 0 ? $promo["value"] : 0);
								$discount_pricing = $discount_recurring = 0;
								$discount_pricing += $product_base_sale * (1 - $promo_value / 100);
								$discount_recurring += $product_base_sale_price * (1 - $promo_value / 100);
								foreach ($configoptions_base_sale as $h) {
									if ($h["is_discount"] == 1) {
										$discount_pricing += $h["config_base_sale"] * (1 - $promo_value / 100);
										$discount_recurring += ($h["config_base_sale"] - $h["config_base_sale_setupfee"]) * (1 - $promo_value / 100);
									}
								}
								if ($promo["recurring"] > 0) {
									$product_total_price_sale_price = bcsub($product_total_price_sale_price, $discount_recurring);
								}
							} elseif ($promo["type"] == "fixed") {
								$discount_pricing = $product_total_price_sale < $promo["value"] ? $product_total_price_sale : $promo["value"];
								if ($promo["recurring"] > 0) {
									$product_total_price_sale_price = $product_total_price_sale_price - $promo["value"] > 0 ? bcsub($product_total_price_sale_price, $promo["value"]) : 0;
								}
							} elseif ($promo["type"] == "override") {
								if ($product_total_price_sale < $promo["value"]) {
									$discount_pricing = $product_total_price_sale;
								} else {
									$discount_pricing = $product_total_price_sale - $promo["value"];
								}
								if ($promo["recurring"] > 0) {
									$product_total_price_sale_price = $product_total_price_sale < $promo["value"] ? $product_total_price_sale : $promo["value"];
								}
							} elseif ($promo["type"] == "free") {
								$discount_pricing = $product_total_price_sale_setupfee;
								if ($promo["recurring"] > 0) {
									$product_total_price_sale_price = 0;
								}
							} else {
								$discount_pricing = 0;
							}
							$discount_pricing = $discount_pricing > 0 ? $discount_pricing : 0;
							$product_total_price_sale = bcsub($product_total_price_sale, $discount_pricing, 2) > 0 ? bcsub($product_total_price_sale, $discount_pricing, 2) : 0;
							if ($promo["one_time"] == 1) {
								if (empty($one_time)) {
									$qty = 1;
									$total = bcsub($total, $discount_pricing * $qty);
									$product_item["invoices_items"][] = ["uid" => $uid, "type" => "promo", "description" => promoCodeDesc($promo), "description2" => promoCodeDesc($promo) ?? "", "amount" => "-" . $discount_pricing, "due_time" => $nextduedate, "payment" => $payment, "one_time" => 1];
									$one_time = true;
								}
							} else {
								$total = bcsub($total, $discount_pricing * $qty);
								$product_item["invoices_items"][] = ["uid" => $uid, "type" => "promo", "description" => promoCodeDesc($promo), "description2" => promoCodeDesc($promo) ?? "", "amount" => "-" . $discount_pricing, "due_time" => $nextduedate, "payment" => $payment];
							}
							$product_item["promoid"] = $promo["id"];
						}
					}
				} else {
					$product_item["promoid"] = 0;
				}
				$product_item["firstpaymentamount"] = $product_total_price_sale;
				$product_item["amount"] = $product_total_price_sale_price > 0 ? $product_total_price_sale_price : 0;
				$product_items[] = $product_item;
			}
		}
		if (!empty($product_error)) {
			$result["status"] = 400;
			$result["msg"] = implode("\n", $product_error);
			return json($result);
		}
		$total = $total > 0 ? $total : 0;
		$subtotal = $total;
		$invoices_data = ["uid" => $uid, "create_time" => time(), "due_time" => time(), "paid_time" => 0, "last_capture_attempt" => 0, "subtotal" => $subtotal, "credit" => 0, "tax" => 0, "tax2" => 0, "total" => $total, "taxrate" => 0, "taxrate2" => 0, "status" => "Unpaid", "payment" => $payment, "notes" => "", "type" => "product"];
		$order_data = ["uid" => $uid, "ordernum" => cmf_get_order_sn(), "status" => "Pending", "create_time" => time(), "update_time" => 0, "amount" => $total, "payment" => $payment];
		if (!empty($promo)) {
			$order_data["promo_code"] = $promo["code"];
			$order_data["promo_type"] = $promo["type"];
			$order_data["promo_value"] = $promo["value"];
		}
		$create_after_order = [];
		$create_after_pay = [];
		$all_host = [];
		if (request()->is_api == 1) {
			$downstream_data = input("post.");
			$is_downstream = (strpos($downstream_data["downstream_url"], "https://") === 0 || strpos($downstream_data["downstream_url"], "http://") === 0) && strlen($downstream_data["downstream_token"]) == 32 && is_numeric($downstream_data["downstream_id"]);
		}
		\think\Db::startTrans();
		try {
			if (!empty($create_invoice)) {
				$invoiceid = \think\Db::name("invoices")->insertGetId($invoices_data);
				if (empty($invoiceid)) {
					throw new \Exception("Bill generation failed");
				}
			}
			$invoiceid = intval($invoiceid);
			$order_data["invoiceid"] = $invoiceid;
			if ($pos_param["notes"]) {
				$order_data["notes"] = trim($pos_param["notes"]);
			}
			$orderid = \think\Db::name("orders")->insertGetId($order_data);
			$hids = [];
			foreach ($product_items as $k => $v) {
				$qtys = $v["qty"];
				if ($v["billingcycle"] == "onetime") {
					$v["amount"] = 0;
					$v["nextduedate"] = 0;
				}
				$invoices_items = $v["invoices_items"];
				$product_config = $v["product_config"];
				$customfields = $v["customfields"];
				unset($v["invoices_items"]);
				unset($v["product_config"]);
				unset($v["customfields"]);
				unset($v["qty"]);
				$v["orderid"] = $orderid;
				$pid = $v["productid"];
				$rule = \think\Db::name("products")->field("host,password")->where("id", $pid)->find();
				$host_rule = json_decode($rule["host"], true);
				$host = $v["host"];
				$password = $v["password"];
				unset($v["host"]);
				$v["password"] = empty($password) ? "" : cmf_encrypt($password);
				$r = \think\Db::name("products")->field("name,stock_control,qty,auto_setup,api_type")->where("id", $v["productid"])->find();
				if ($r["stock_control"] == 1 && $r["qty"] < $qtys) {
					throw new \Exception("Item '{$r["name"]}' is out of stock");
				}
				if (empty($v["payment"])) {
					$v["payment"] = $default_payment ?? "";
				}
				if ($r["api_type"] == "resource") {
					$v["agent_grade"] = resourceUserGradePercent($uid, $v["productid"]);
					$price_model = \think\Db::name("res_products")->where("productid", $v["productid"])->value("price_type");
					if ($price_model == "handling") {
						$v["handling"] = floatval(configuration("shd_resource_handling_model"));
					}
				}
				for ($i = 0; $i < $qtys; $i++) {
					if ($qtys > 1) {
						$v["domain"] = generateHostName($host_rule["prefix"], $host_rule["rule"], $host_rule["show"]);
					} else {
						$v["domain"] = empty($host) ? generateHostName($host_rule["prefix"], $host_rule["rule"], $host_rule["show"]) : $host;
					}
					if ($param["agent_client"]) {
						$v["agent_client"] = intval($param["agent_client"]);
					}
					$hostid = \think\Db::name("host")->insertGetId($v);
					$h = [];
					$h["hid"] = $hostid;
					$h["billingcycle"] = $v["billingcycle"];
					$hids[] = $h;
					if ($r["auto_setup"] == "order") {
						$create_after_order[] = $hostid;
					} elseif ($r["auto_setup"] == "payment") {
						$create_after_pay[] = $hostid;
					}
					$all_host[] = $hostid;
					if (!empty($invoices_items)) {
						foreach ($invoices_items as $kk => $vv) {
							$invoices_items[$kk]["invoice_id"] = $invoiceid;
							$invoices_items[$kk]["rel_id"] = $hostid;
							if ($vv["one_time"] == 1) {
								unset($vv["one_time"]);
								$vv["invoice_id"] = $invoiceid;
								$vv["rel_id"] = $hostid;
								\think\Db::name("invoice_items")->insert($vv);
								unset($invoices_items[$kk]);
							}
						}
						\think\Db::name("invoice_items")->insertAll($invoices_items);
					}
					if (!empty($product_config)) {
						foreach ($product_config as $kk => $vv) {
							$product_config[$kk]["relid"] = $hostid;
						}
						\think\Db::name("host_config_options")->insertAll($product_config);
					}
					if (!empty($customfields)) {
						foreach ($customfields as $kk => $vv) {
							$customfields[$kk]["relid"] = $hostid;
							$customfields[$kk]["create_time"] = time();
						}
						\think\Db::name("customfieldsvalues")->insertAll($customfields);
					}
				}
				\think\Db::name("products")->where("id", $v["productid"])->where("stock_control", 1)->setDec("qty", $qtys);
			}
			\think\Db::name("cart_session")->where("uid", $uid)->update(["cart_data" => $new_cart_data, "update_time" => time()]);
			if (!empty($promo)) {
				\think\Db::name("promo_code")->where("id", $promo["id"])->setInc("used");
			}
			foreach ($products as $key => $v) {
				if ($v["groupid"] != 0) {
					$ng = \think\Db::name("nav_group_user")->where("uid", $uid)->where("groupid", $v["groupid"])->find();
					if (empty($ng)) {
						$data = ["groupid" => $v["groupid"], "uid" => $uid ?? 0, "is_show" => 1];
						$ng = \think\Db::name("nav_group_user")->insert($data);
					} else {
						if ($ng["is_show"] == 0) {
							$ng = \think\Db::name("nav_group_user")->where("uid", $uid)->where("groupid", $v["groupid"])->update(["is_show" => 1]);
						}
					}
				}
			}
			if (count($all_host) == 1) {
				\think\Db::name("invoices")->where("id", $invoiceid)->update(["url" => "servicedetail?id=" . $all_host[0]]);
			} else {
				if (count($all_host) > 1) {
					$menu = new \app\common\logic\Menu();
					$fpid = \think\Db::name("host")->where("id", $all_host[0])->value("productid");
					$url = $menu->proGetNavId(intval($fpid))["url"] ?: "";
					\think\Db::name("invoices")->where("id", $invoiceid)->update(["url" => $url]);
				}
			}
			if ($is_downstream) {
				$downstream_create = \think\Db::name("host")->whereLike("stream_info", "%" . $downstream_data["downstream_token"] . "%")->find();
				if (!empty($downstream_create)) {
					$result = [];
					$result["status"] = 1001;
					$result["msg"] = "Successful purchase";
					$result["data"]["hostid"] = [$downstream_create["id"]];
					return json($result);
				} else {
					$stream_info = [];
					$stream_info["downstream_url"] = $downstream_data["downstream_url"];
					$stream_info["downstream_token"] = $downstream_data["downstream_token"];
					$stream_info["downstream_id"] = $downstream_data["downstream_id"];
					\think\Db::name("host")->where("id", \intval($all_host[0]))->update(["stream_info" => json_encode($stream_info)]);
				}
			}
			if ($invoiceid == 0) {
				active_logs(sprintf($this->lang["Cart_home_settle_success1"], $orderid), $uid);
				active_logs(sprintf($this->lang["Cart_home_settle_success1"], $orderid), $uid, "", 2);
			} else {
				active_logs(sprintf($this->lang["Cart_home_settle_success"], $invoiceid, $orderid), $uid);
				active_logs(sprintf($this->lang["Cart_home_settle_success"], $invoiceid, $orderid), $uid, "", 2);
			}
			\think\Db::commit();
			$result["status"] = 200;
			$result["msg"] = "Successful purchase";
		} catch (\Exception $e) {
			$result["status"] = 400;
			$result["msg"] = $e->getMessage();
			\think\Db::rollback();
		}
		if ($result["status"] != 200) {
			return json($result);
		}
		$curl_multi_data = [];
		if ($subtotal != 0) {
			foreach ($hids as $h) {
				if ($h["billingcycle"] != "free") {
					$arr_admin = ["relid" => $h["hid"], "name" => "【管理员】新订单通知", "type" => "invoice", "sync" => true, "admin" => true, "ip" => get_client_ip6()];
					if (configuration("shd_allow_email_send_queue")) {
						\app\queue\job\SendMail::push($arr_admin);
					} else {
						$curl_multi_data[count($curl_multi_data)] = ["url" => "async", "data" => $arr_admin];
					}
					$admin = getReceiveAdmin();
					foreach ($admin as $key => $value) {
						$arr_admin = ["relid" => $h["hid"], "name" => "【管理员】新订单通知", "type" => "invoice", "sync" => true, "admin" => true, "adminid" => $value["id"], "ip" => get_client_ip6()];
						if (configuration("shd_allow_email_send_queue")) {
							\app\queue\job\SendMail::push($arr_admin);
						} else {
							$curl_multi_data[count($curl_multi_data)] = ["url" => "async", "data" => $arr_admin];
						}
					}
					$arr_client = ["relid" => $h["hid"], "name" => "新订单通知", "type" => "invoice", "sync" => true, "admin" => false, "ip" => get_client_ip6()];
					if (configuration("shd_allow_email_send_queue")) {
						\app\queue\job\SendMail::push($arr_client);
					} else {
						$curl_multi_data[count($curl_multi_data)] = ["url" => "async", "data" => $arr_client];
					}
				}
			}
			$message_template_type = array_column(config("message_template_type"), "id", "name");
			foreach ($product_items as $k => $v) {
				$hostre = \think\Db::name("products")->field("name")->where("id", $v["productid"])->find();
				$sms = new \app\common\logic\Sms();
				$client = check_type_is_use($message_template_type[strtolower("New_Order_Notice")], $v["uid"], $sms);
				if ($client && $v["billingcycle"] != "free") {
					$b = config("billing_cycle");
					$params = ["product_name" => $hostre["name"], "product_binlly_cycle" => $b[$v["billingcycle"]], "product_price" => $v["amount"], "order_create_time" => date("Y-m-d H:i:s", $v["create_time"])];
					$arr = ["name" => $message_template_type[strtolower("New_Order_Notice")], "phone" => $client["phone_code"] . $client["phonenumber"], "params" => json_encode($params), "sync" => false, "uid" => $v["uid"], "delay_time" => 0, "is_market" => false];
					if (configuration("shd_allow_sms_send_queue")) {
						\app\queue\job\SendSms::push($arr);
					} else {
						$curl_multi_data[count($curl_multi_data)] = ["url" => "async_sms", "data" => $arr];
					}
				}
			}
		}
		foreach ($create_after_order as $v) {
			$host_arr = ["hid" => $v, "ip" => get_client_ip6()];
			if (configuration("shd_allow_auto_create_queue")) {
				\app\queue\job\AutoCreate::push($host_arr);
			} else {
				$curl_multi_data[count($curl_multi_data)] = ["url" => "async_create", "data" => $host_arr];
			}
		}
		hook("shopping_cart_settle", ["orderid" => $orderid, "total" => $total, "invoiceid" => \intval($invoiceid), "hostid" => array_column($hids, "hid")]);
		if ($total == 0) {
			if (!empty($invoiceid)) {
				\think\Db::name("invoices")->where("id", $invoiceid)->update(["status" => "Paid", "paid_time" => time()]);
				$invoice_logic = new \app\common\logic\Invoices();
				$invoice_logic->processPaidInvoice($invoiceid);
			} else {
				foreach ($create_after_pay as $vv) {
					if (configuration("shd_allow_auto_create_queue")) {
						\app\queue\job\AutoCreate::push(["hid" => $vv, "ip" => get_client_ip6()]);
					} else {
						$curl_multi_data[count($curl_multi_data)] = ["url" => "async_create", "data" => ["hid" => $vv, "ip" => get_client_ip6()]];
					}
				}
			}
			$result["status"] = 1001;
			$result["msg"] = "Successful purchase";
			$result["data"]["hostid"] = $all_host;
			$invoice_url = \think\Db::name("invoices")->where("id", $invoiceid)->find();
			$result["data"]["url"] = $invoice_url["url"] ?: "";
			asyncCurlMulti($curl_multi_data);
		} else {
			$result["status"] = 200;
			$result["data"]["invoiceid"] = $invoiceid;
			if ($is_downstream) {
				$result["data"]["hostid"] = $all_host;
			}
			asyncCurlMulti($curl_multi_data);
			return json($result);
		}
		return json($result);
	}
	public function goods()
	{
		$param = $this->request->param();
		$uid = request()->uid;
		$logic = new \app\common\logic\Product();
		$lists = $logic->getListCache();
		if (empty($lists)) {
			$logic->updateListCache();
			$lists = $logic->getListCache();
		}
		$gid = 0;
		if ($uid) {
			$gid = \think\Db::name("clients")->where("id", $uid)->value("groupid");
		}
		$fgs = \think\Db::name("product_first_groups")->field("id,name")->where("hidden", 0)->where(function (\think\db\Query $query) use($param) {
			if (isset($param["fgid"])) {
				$query->where("id", $param["fgid"]);
			}
		})->order("order", "asc")->order("id", "asc")->select()->toArray();
		foreach ($fgs as &$fg) {
			$fg["fields"] = \think\Db::name("product_first_groups_customfields")->field("name,value")->where("relid", $fg["id"])->select()->toArray();
			$gs = \think\Db::name("product_groups")->field("id,name,headline,tagline,order,gid,order_frm_tpl,tpl_type")->where("hidden", 0)->where("order_frm_tpl", "<>", "uuid")->where(function (\think\db\Query $query) use($fg, $param) {
				$query->where("gid", $fg["id"]);
				if (isset($param["gid"])) {
					$query->where("id", $param["gid"]);
				}
			})->order("order", "asc")->select()->toArray();
			foreach ($gs as &$g) {
				$g["fields"] = \think\Db::name("product_groups_customfields")->field("name,value")->where("relid", $g["id"])->select()->toArray();
				$tmp = array_filter($lists, function ($v) use($g) {
					if ($v["gid"] != $g["id"]) {
						return false;
					}
					return true;
				});
				$filter = [];
				foreach ($tmp as $v) {
					if ($v["gid"] = $g["id"]) {
						if ($gid && isset($v["cgs"][$gid])) {
							$v["sale_price"] = $v["cgs"][$gid]["sale_price"];
							$v["bates"] = $v["cgs"][$gid]["bates"];
						}
						unset($v["cgs"]);
						$filter[] = $v;
					}
				}
				if (isset($param["pid"])) {
					$filter = array_filter($filter, function ($v) use($param) {
						if ($param["pid"] != $v["id"]) {
							return false;
						}
						return true;
					});
				}
				$g["products"] = $filter;
			}
			$fg["group"] = $gs;
		}
		$data = ["fgs" => $fgs];
		return json(["status" => 200, "msg" => "请求成功", "data" => $data]);
	}
	public function goodsConfig()
	{
		$param = $this->request->param();
		$pid = intval($param["pid"]);
		$billingcycle = $param["billingcycle"] ?: "";
		$uid = request()->uid;
		$currencyid = priorityCurrency($uid);
		$currency = get_currency();
		if (empty($billingcycle)) {
			$product_model = new \app\common\model\ProductModel();
			$billingcycle = $product_model->getProductCycle($pid, $currencyid, "", "", "", "", "", "", 1)[0]["billingcycle"] ?: "";
		}
		$cart = new \app\common\logic\Cart();
		$product = $cart->getProductCycle($pid, $currencyid);
		$customfields = new \app\common\logic\Customfields();
		$fields = $customfields->getCartCustomField($pid);
		$config_logic = new \app\common\logic\ConfigOptions();
		$alloption = $config_logic->getConfigInfo($pid);
		$hook_filter = hook_one("pre_cart_product_config", ["uid" => $uid, "pid" => $pid, "options" => $alloption]);
		if ($hook_filter) {
			$alloption = $hook_filter;
		}
		$alloption = $config_logic->configShow($alloption, $currencyid, $billingcycle);
		$data = ["currency" => $currency, "product" => $product, "option" => $alloption, "custom_fields" => $fields];
		if (getEdition()) {
			$alloption = $this->handleLinkAgeLevel($alloption);
			$alloption = $this->handleTreeArr($alloption);
			$cids = \think\Db::name("product_config_options")->alias("a")->field("a.id")->leftJoin("product_config_links b", "b.gid = a.gid")->leftJoin("product_config_groups c", "a.gid = c.id")->where("b.pid", $pid)->order("a.order", "asc")->order("a.id", "asc")->column("a.id");
			$links = \think\Db::name("product_config_options_links")->whereIN("config_id", $cids)->where("type", "condition")->where("relation_id", 0)->withAttr("sub_id", function ($value) {
				return json_decode($value, true);
			})->select()->toArray();
			if (!empty($links[0])) {
				foreach ($links as &$link) {
					$result = \think\Db::name("product_config_options_links")->where("relation_id", $link["id"])->withAttr("sub_id", function ($value) {
						return json_decode($value, true);
					})->select()->toArray();
					$link["result"] = $result;
				}
			}
			$data["option"] = $alloption;
			$data["links"] = $links;
		}
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function goodsTotal()
	{
		$param = $this->request->only(["pid", "billingcycle", "configoption", "customfield", "currencyid", "qty"]);
		$billingcycle = $param["billingcycle"];
		$configoption = $param["configoption"];
		$currencyid = isset($param["currencyid"]) ? $param["currencyid"] : "";
		$qty = isset($param["qty"]) && intval($param["qty"]) > 0 ? intval($param["qty"]) : 1;
		$pid = $param["pid"];
		$res = $product_filter = $all_option = [];
		$setupfeetotal = $total = $signal_setupfee = $price_total = $signal_price = 0;
		$salesetupfeetotal = $saletotal = $salesignal_setupfee = $saleprice_total = $salesignal_price = 0;
		$cart = new \app\common\logic\Cart();
		$rebate_setupfee = $rebate_price = $rebate_signal_price = 0;
		$uid = !empty(request()->uid) ? request()->uid : "";
		$currency = getUserCurrency($uid);
		$currencyid = $currency["id"];
		if (!in_array($billingcycle, array_keys(config("billing_cycle")))) {
			$product_model = new \app\common\model\ProductModel();
			$billingcycle = $product_model->getProductCycle($pid, $currencyid, "", "", "", "", "", "", 1)[0]["billingcycle"] ?? "";
		}
		$product_model = new \app\common\model\ProductModel();
		if (!$product_model->checkProductPrice($pid, $billingcycle, $currencyid)) {
			return json(["status" => 400, "msg" => lang("CART_GETTOTAL_PRICE_ERROR")]);
		}
		$setupfeecycle = $cart->changeCycleToupfee($billingcycle);
		$product = \think\Db::name("products")->alias("a")->field("a.id as productid,a.name,a.pay_type,b.*,a.api_type,a.upstream_version,a.upstream_price_type,a.upstream_price_value,a.hidden,a.stock_control,a.qty")->leftJoin("pricing b", "a.id = b.relid")->where("a.id", $pid)->where("b.type", "product")->where("b.currency", $currencyid)->find();
		if (!$product) {
			return json(["status" => 400, "msg" => lang("CART_GETTOTAL_PRODUCT_ERROR")]);
		}
		if ($product["api_type"] == "zjmf_api" && $product["upstream_price_type"] == "percent") {
			$is_ajmf_api = true;
		} else {
			$is_ajmf_api = false;
		}
		bcscale(2);
		$pay_ontrial_cycle = json_decode($product["pay_type"], true);
		$product_setup_fee = $product[$setupfeecycle] > 0 ? $product[$setupfeecycle] : 0;
		$product_price = $product[$billingcycle] > 0 ? $product[$billingcycle] : 0;
		$product_filter["product_name"] = $product["name"];
		$product_filter["billingcycle"] = $billingcycle;
		$product_filter["billingcycle_zh"] = config("billing_cycle")[$billingcycle];
		$product_filter["product_setup_fee"] = bcsub($product_setup_fee, 0);
		$product_filter["product_price"] = bcsub($product_price, 0);
		$product_filter["pay_day_cycle"] = $pay_ontrial_cycle["pay_day_cycle"];
		$product_filter["pay_hour_cycle"] = $pay_ontrial_cycle["pay_hour_cycle"];
		$product_filter["pay_ontrial_cycle"] = $pay_ontrial_cycle["pay_ontrial_cycle"];
		$product_filter["pay_ontrial_cycle_type"] = $pay_ontrial_cycle["pay_ontrial_cycle_type"] ?: "day";
		$product_filter["stock_control"] = $product["stock_control"];
		$product_filter["qty"] = $product["qty"];
		$total += bcmul($product_setup_fee, $qty);
		$total += bcmul($product_price, $qty);
		$setupfeetotal += bcmul($product_setup_fee, $qty);
		$rebate_setupfee += bcmul($product_setup_fee, $qty);
		$rebate_price += bcmul($product_price, $qty);
		$rebate_signal_price = $product_price;
		$edition = getEdition();
		$signal_setupfee += $product_setup_fee;
		$signal_price += $product_price;
		$flag = getSaleProductUser($pid, $uid);
		$bates = 0;
		if ($flag["type"] == 1) {
			$bates = 1 - $flag["bates"] / 100;
			$saletotal = bcsub($total, bcmul($total, $bates, 2), 2);
			$salesetupfeetotal = bcsub($setupfeetotal, bcmul($setupfeetotal, $bates, 2), 2);
			$salesignal_setupfee = bcsub($product_setup_fee, bcmul($product_setup_fee, $bates, 2), 2);
			$salesignal_price = bcsub($product_price, bcmul($product_price, $bates, 2), 2);
			$product_sale_setup_fee = $salesignal_setupfee;
			$product_sale_price = $salesignal_price;
		} else {
			$saletotal = $total;
			$salesetupfeetotal = $setupfeetotal;
			$salesignal_setupfee = $product_setup_fee;
			$salesignal_price = $product_price;
			$product_sale_setup_fee = $salesignal_setupfee;
			$product_sale_price = $salesignal_price;
		}
		$configoptions_logic = new \app\common\logic\ConfigOptions();
		$configoption = $configoptions_logic->filterConfigOptions($pid, $configoption);
		foreach ($configoption as $key => $value) {
			$option1 = \think\Db::name("product_config_options")->field("option_type,unit")->where("id", $key)->find();
			$option_type = $option1["option_type"];
			$option_unit = $option1["unit"];
			if ($option_type && $value) {
				$option_filter = [];
				if (!judgeQuantity($option_type)) {
					$option = \think\Db::name("product_config_options_sub")->alias("pcos")->field("pco.is_discount,pcos.option_name as suboption_name,pco.option_type,pco.option_name as option_name,pco.hidden,p.*,pco.is_rebate")->leftJoin("product_config_options pco", "pco.id = pcos.config_id")->leftJoin("pricing p", "p.relid = pcos.id")->where("pcos.id", $value)->where("pcos.config_id", $key)->where("p.type", "configoptions")->where("p.currency", $currencyid)->find();
					if (!$option) {
						return jsons(["status" => 400, "msg" => lang("ERROR_OPERATE")]);
					} else {
						$optionname = $option["option_name"];
						$suboptionname = $option["suboption_name"];
						$optionprice = $option[$billingcycle] > 0 ? $option[$billingcycle] : 0;
						$optionupfee = $option[$setupfeecycle] > 0 ? $option[$setupfeecycle] : 0;
						$optionsaleprice = $option[$billingcycle] > 0 ? $option[$billingcycle] : 0;
						$optionsaleupfee = $option[$setupfeecycle] > 0 ? $option[$setupfeecycle] : 0;
						$option_filter["hidden"] = $option["hidden"];
						$option_filter["option_name"] = explode("|", $optionname)[1] ? explode("|", $optionname)[1] : $optionname;
						$option_filter["suboption_name"] = $suboptionname_deal = explode("|", $suboptionname)[1] ? explode("|", $suboptionname)[1] : $suboptionname;
						$option_filter["sub_name"] = $option_filter["suboption_name"];
						if (explode("^", $suboptionname_deal)[1]) {
							$option_filter_suboption_name = explode("^", $suboptionname_deal);
							$option_filter["suboption_name"] = implode(" ", $option_filter_suboption_name);
							if ($option["option_type"] == 12) {
								$option_filter["icon_flag"] = trim($option_filter_suboption_name[0]);
								$option_filter["sub_name"] = $option_filter_suboption_name[1];
							} elseif ($option["option_type"] == 5) {
								$iconos = strtolower($option_filter_suboption_name[0]);
								switch ($iconos) {
									case "windows":
										$icon_os = 1;
										break;
									case "centos":
										$icon_os = 2;
										break;
									case "ubuntu":
										$icon_os = 3;
										break;
									case "debian":
										$icon_os = 4;
										break;
									case "esxi":
										$icon_os = 5;
										break;
									case "xenserver":
										$icon_os = 6;
										break;
									case "freebsd":
										$icon_os = 7;
										break;
									case "fedora":
										$icon_os = 8;
										break;
									default:
										$icon_os = 9;
								}
								$option_filter["icon_os"] = $icon_os;
								$option_filter["sub_name"] = $option_filter_suboption_name[1];
							}
						}
						if ($flag && $flag["type"] == 1) {
							if ($is_ajmf_api) {
								$option_filter["suboption_setup_fee"] = bcsub(bcmul($optionupfee, $product["upstream_price_value"]) / 100, 0);
								$option_filter["suboption_sale_setup_fee"] = round(bcsub($option_filter["suboption_setup_fee"], bcmul($option_filter["suboption_setup_fee"], $bates, 2), 2), 2);
								$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_sale_setup_fee"] > 0 ? $option_filter["suboption_sale_setup_fee"] : 0;
								$option_filter["suboption_price"] = bcsub(bcmul($optionprice, $product["upstream_price_value"]) / 100, 0);
								$option_filter["suboption_sale_price"] = round(bcsub($option_filter["suboption_price"], bcmul($option_filter["suboption_price"], $bates, 2), 2), 2);
								$option_filter["suboption_sale_price"] = $option_filter["suboption_sale_price"] > 0 ? $option_filter["suboption_sale_price"] : 0;
								$option_filter["suboption_price_total"] = bcadd(bcmul($optionupfee, $product["upstream_price_value"]) / 100, bcmul($optionprice, $product["upstream_price_value"]) / 100);
								$option_filter["suboption_sale_price_total"] = round(bcsub($option_filter["suboption_price_total"], bcmul($option_filter["suboption_price_total"], $bates, 2), 2), 2);
								$option_filter["suboption_sale_price_total"] = $option_filter["suboption_sale_price_total"] > 0 ? $option_filter["suboption_sale_price_total"] : 0;
							} else {
								$option_filter["suboption_setup_fee"] = bcsub($optionupfee, 0);
								$option_filter["suboption_sale_setup_fee"] = round(bcsub($option_filter["suboption_setup_fee"], bcmul($option_filter["suboption_setup_fee"], $bates, 2), 2), 2);
								$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_sale_setup_fee"] > 0 ? $option_filter["suboption_sale_setup_fee"] : 0;
								$option_filter["suboption_price"] = bcsub($optionprice, 0);
								$option_filter["suboption_sale_price"] = round(bcsub($option_filter["suboption_price"], bcmul($option_filter["suboption_price"], $bates, 2), 2), 2);
								$option_filter["suboption_sale_price"] = $option_filter["suboption_sale_price"] > 0 ? $option_filter["suboption_sale_price"] : 0;
								$option_filter["suboption_price_total"] = bcadd($optionprice, $optionupfee);
								$option_filter["suboption_sale_price_total"] = round(bcsub($option_filter["suboption_price_total"], bcmul($option_filter["suboption_price_total"], $bates, 2), 2), 2);
								$option_filter["suboption_sale_price_total"] = $option_filter["suboption_sale_price_total"] > 0 ? $option_filter["suboption_sale_price_total"] : 0;
							}
							$optionsaleupfee = round(bcsub($optionsaleupfee, bcmul($optionsaleupfee, $bates, 2), 2), 2);
							$optionsaleprice = round(bcsub($optionsaleprice, bcmul($optionsaleprice, $bates, 2), 2), 2);
						} else {
							if ($is_ajmf_api) {
								$option_filter["suboption_setup_fee"] = bcsub(bcmul($optionupfee, $product["upstream_price_value"]) / 100, 0);
								$option_filter["suboption_price"] = bcsub(bcmul($optionprice, $product["upstream_price_value"]) / 100, 0);
								$option_filter["suboption_price_total"] = bcadd(bcmul($optionupfee, $product["upstream_price_value"]) / 100, bcmul($optionprice, $product["upstream_price_value"]) / 100);
								$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_setup_fee"];
								$option_filter["suboption_sale_price"] = $option_filter["suboption_price"];
								$option_filter["suboption_sale_price_total"] = $option_filter["suboption_price_total"];
							} else {
								$option_filter["suboption_setup_fee"] = bcsub($optionupfee, 0);
								$option_filter["suboption_price"] = bcsub($optionprice, 0);
								$option_filter["suboption_price_total"] = bcadd($optionprice, $optionupfee);
								$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_setup_fee"];
								$option_filter["suboption_sale_price"] = $option_filter["suboption_price"];
								$option_filter["suboption_sale_price_total"] = $option_filter["suboption_price_total"];
							}
						}
						$saletotal += bcmul($optionsaleupfee, $qty);
						$saletotal += bcmul($optionsaleprice, $qty);
						$salesetupfeetotal += bcmul($optionsaleupfee, $qty);
						$salesignal_setupfee += $optionsaleupfee;
						$salesignal_price += $optionsaleprice;
						$option_filter["option_type"] = $option_type;
						$total += bcmul($optionupfee, $qty);
						$total += bcmul($optionprice, $qty);
						$setupfeetotal += bcmul($optionupfee, $qty);
						$signal_setupfee += $optionupfee;
						$signal_price += $optionprice;
						$all_option[] = $option_filter;
						if ($option["is_rebate"] || !$edition) {
							$rebate_setupfee += bcmul($optionupfee, $qty);
							$rebate_price += bcmul($optionprice, $qty);
							$rebate_signal_price += $optionprice;
						}
					}
				} else {
					$options = \think\Db::name("product_config_options_sub")->alias("pcos")->field("pcos.option_name as suboption_name,pcos.qty_minimum,pcos.qty_maximum,pco.option_type,pco.hidden,pco.option_name as option_name,pco.qty_minimum as min,pco.qty_maximum as max,pco.is_discount,p.*,pco.is_rebate")->leftJoin("product_config_options pco", "pco.id = pcos.config_id")->leftJoin("pricing p", "p.relid = pcos.id")->where("pcos.config_id", $key)->where("p.type", "configoptions")->where("currency", $currencyid)->select();
					if (!empty($options[0])) {
						foreach ($options as $option) {
							$min = $option["qty_minimum"];
							$max = $option["qty_maximum"];
							if ($value > 0 && $option["min"] <= $value && $value <= $option["max"] && $min <= $value && $value <= $max) {
								$optionprice = $option[$billingcycle] <= 0 ? 0 : $option[$billingcycle] * $value;
								$optionupfee = $option[$setupfeecycle] <= 0 ? 0 : $option[$setupfeecycle];
								$optionsaleprice = $option[$billingcycle] <= 0 ? 0 : $option[$billingcycle] * $value;
								$optionsaleupfee = $option[$setupfeecycle] <= 0 ? 0 : $option[$setupfeecycle];
								if ($flag && $option["is_discount"] == 1 && $flag["type"] == 1) {
									if (judgeQuantityStage($option_type)) {
										$sum = quantityStagePrice($key, $currencyid, $value, $billingcycle);
										$optionprice = $sum[0];
										$optionupfee = $sum[1];
										$optionsaleprice = $sum[0];
										$optionsaleupfee = $sum[1];
									}
								} else {
									if (judgeQuantityStage($option_type)) {
										$sum = quantityStagePrice($key, $currencyid, $value, $billingcycle);
										$optionprice = $sum[0];
										$optionupfee = $sum[1];
										$optionsaleprice = $sum[0];
										$optionsaleupfee = $sum[1];
									}
								}
								$suboptionname = $option["suboption_name"];
								$optionname = $option["option_name"];
								$option_filter["hidden"] = $option["hidden"];
								$option_filter["option_name"] = explode("|", $optionname)[1] ? explode("|", $optionname)[1] : $optionname;
								$option_filter["suboption_name"] = explode("|", $suboptionname)[1] ? explode("|", $suboptionname)[1] : $suboptionname;
								if ($flag && $flag["type"] == 1) {
									if ($is_ajmf_api) {
										$option_filter["suboption_setup_fee"] = bcsub(bcmul($optionupfee, $product["upstream_price_value"]) / 100, 0);
										$option_filter["suboption_sale_setup_fee"] = round(bcsub($option_filter["suboption_setup_fee"], bcmul($option_filter["suboption_setup_fee"], $bates, 2), 2), 2);
										$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_sale_setup_fee"] > 0 ? $option_filter["suboption_sale_setup_fee"] : 0;
										$option_filter["suboption_price"] = bcsub(bcmul($optionprice, $product["upstream_price_value"]) / 100, 0);
										$option_filter["suboption_sale_price"] = round(bcsub($option_filter["suboption_price"], bcmul($option_filter["suboption_price"], $bates, 2), 2), 2);
										$option_filter["suboption_sale_price"] = $option_filter["suboption_sale_price"] > 0 ? $option_filter["suboption_sale_price"] : 0;
										$option_filter["suboption_price_total"] = bcadd(bcmul($optionupfee, $product["upstream_price_value"]) / 100, bcmul($optionprice, $product["upstream_price_value"]) / 100);
										$option_filter["suboption_sale_price_total"] = round(bcsub($option_filter["suboption_price_total"], bcmul($option_filter["suboption_price_total"], $bates, 2), 2), 2);
										$option_filter["suboption_sale_price_total"] = $option_filter["suboption_sale_price_total"] > 0 ? $option_filter["suboption_sale_price_total"] : 0;
									} else {
										$option_filter["suboption_setup_fee"] = bcsub($optionupfee, 0);
										$option_filter["suboption_sale_setup_fee"] = round(bcsub($option_filter["suboption_setup_fee"], bcmul($option_filter["suboption_setup_fee"], $bates, 2), 2), 2);
										$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_sale_setup_fee"] > 0 ? $option_filter["suboption_sale_setup_fee"] : 0;
										$option_filter["suboption_price"] = bcsub($optionprice, 0);
										$option_filter["suboption_sale_price"] = round(bcsub($option_filter["suboption_price"], bcmul($option_filter["suboption_price"], $bates, 2), 2), 2);
										$option_filter["suboption_sale_price"] = $option_filter["suboption_sale_price"] > 0 ? $option_filter["suboption_sale_price"] : 0;
										$option_filter["suboption_price_total"] = bcadd($optionprice, $optionupfee);
										$option_filter["suboption_sale_price_total"] = round(bcsub($option_filter["suboption_price_total"], bcmul($option_filter["suboption_price_total"], $bates, 2), 2), 2);
										$option_filter["suboption_sale_price_total"] = $option_filter["suboption_sale_price_total"] > 0 ? $option_filter["suboption_sale_price_total"] : 0;
									}
									$optionsaleupfee = round(bcsub($optionsaleupfee, bcmul($optionsaleupfee, $bates, 2), 2), 2);
									$optionsaleprice = round(bcsub($optionsaleprice, bcmul($optionsaleprice, $bates, 2), 2), 2);
								} else {
									if ($is_ajmf_api) {
										$option_filter["suboption_setup_fee"] = bcsub(bcmul($optionupfee, $product["upstream_price_value"]) / 100, 0);
										$option_filter["suboption_price"] = bcsub(bcmul($optionprice, $product["upstream_price_value"]) / 100, 0);
										$option_filter["suboption_price_total"] = bcadd(bcmul($optionupfee, $product["upstream_price_value"]) / 100, bcmul($optionprice, $product["upstream_price_value"]) / 100);
										$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_setup_fee"];
										$option_filter["suboption_sale_price"] = $option_filter["suboption_price"];
										$option_filter["suboption_sale_price_total"] = $option_filter["suboption_price_total"];
									} else {
										$option_filter["suboption_setup_fee"] = bcsub($optionupfee, 0);
										$option_filter["suboption_price"] = bcsub($optionprice, 0);
										$option_filter["suboption_price_total"] = bcadd($optionprice, $optionupfee);
										$option_filter["suboption_sale_setup_fee"] = $option_filter["suboption_setup_fee"];
										$option_filter["suboption_sale_price"] = $option_filter["suboption_price"];
										$option_filter["suboption_sale_price_total"] = $option_filter["suboption_price_total"];
									}
								}
								$saletotal += bcmul($optionsaleupfee, $qty);
								$saletotal += bcmul($optionsaleprice, $qty);
								$salesetupfeetotal += bcmul($optionsaleupfee, $qty);
								$salesignal_setupfee += $optionsaleupfee;
								$salesignal_price += $optionsaleprice;
								$option_filter["qty"] = $value . $option_unit;
								$option_filter["option_type"] = $option_type;
								$total += bcmul($optionupfee, $qty);
								$total += bcmul($optionprice, $qty);
								$setupfeetotal += bcmul($optionupfee, $qty);
								$signal_setupfee += $optionupfee;
								$signal_price += $optionprice;
								$all_option[] = $option_filter;
								if ($option["is_rebate"] || !$edition) {
									$rebate_setupfee += bcmul($optionupfee, $qty);
									$rebate_price += bcmul($optionprice, $qty);
									$rebate_signal_price += $optionprice;
								}
							}
						}
					}
				}
			}
		}
		foreach ($all_option as $kk => $vv) {
			if ($vv["hidden"] == 1) {
				unset($all_option[$kk]);
			}
		}
		if ($is_ajmf_api) {
			$total = bcmul($total, $product["upstream_price_value"]) / 100;
			$saletotal = bcmul($saletotal, $product["upstream_price_value"]) / 100;
			$salesignal_price = bcmul($salesignal_price, $product["upstream_price_value"]) / 100;
			$setupfeetotal = bcmul($setupfeetotal, $product["upstream_price_value"]) / 100;
			$signal_setupfee = bcmul($signal_setupfee, $product["upstream_price_value"]) / 100;
			$signal_price = bcmul($signal_price, $product["upstream_price_value"]) / 100;
			$product_filter["product_setup_fee"] = bcmul($product_filter["product_setup_fee"], $product["upstream_price_value"]) / 100;
			$product_filter["product_price"] = bcmul($product_filter["product_price"], $product["upstream_price_value"]) / 100;
			$rebate_setupfee = bcmul($rebate_setupfee, $product["upstream_price_value"]) / 100;
			$rebate_price = bcmul($rebate_price, $product["upstream_price_value"]) / 100;
			$rebate_signal_price = bcmul($rebate_signal_price, $product["upstream_price_value"]) / 100;
		}
		if ($product["api_type"] == "resource" && function_exists("resourceUserGradePercent")) {
			$percent = resourceUserGradePercent($uid, $product["productid"]);
			$total = bcmul($total, $percent) / 100;
			$saletotal = bcmul($saletotal, $percent) / 100;
			$salesignal_price = bcmul($salesignal_price, $percent) / 100;
			$setupfeetotal = bcmul($setupfeetotal, $percent) / 100;
			$signal_setupfee = bcmul($signal_setupfee, $percent) / 100;
			$signal_price = bcmul($signal_price, $percent) / 100;
			$product_filter["product_setup_fee"] = bcmul($product_filter["product_setup_fee"], $percent) / 100;
			$product_filter["product_price"] = bcmul($product_filter["product_price"], $percent) / 100;
			$rebate_setupfee = bcmul($rebate_setupfee, $percent) / 100;
			$rebate_price = bcmul($rebate_price, $percent) / 100;
			$rebate_signal_price = bcmul($rebate_signal_price, $percent) / 100;
		}
		$res["products"] = $product_filter;
		$res["products"]["child"] = $all_option;
		$res["products"]["product_sale_setup_fee"] = $product_sale_setup_fee;
		$res["products"]["product_sale_price"] = $product_sale_price;
		$res["products"]["setupfee_total"] = bcsub($setupfeetotal, 0);
		$res["products"]["total"] = bcsub($total, 0);
		$res["products"]["signal_setupfee"] = bcsub($signal_setupfee, 0);
		$res["products"]["signal_price"] = bcsub($signal_price, 0);
		if ($flag) {
			if ($flag["type"] == 1) {
				$res["products"]["sale_setupfee_total"] = bcsub($rebate_setupfee * $flag["bates"] / 100 + $setupfeetotal - $rebate_setupfee, 0);
				$res["products"]["sale_price"] = bcsub($rebate_price * $flag["bates"] / 100 + $total - $setupfeetotal - $rebate_price, 0);
				$res["products"]["sale_signal_price"] = bcsub($rebate_signal_price * $flag["bates"] / 100 + $signal_price - $rebate_signal_price, 0);
				$res["products"]["bates"] = bcsub($total, $saletotal, 2);
			} elseif ($flag["type"] == 2) {
				$bates = $flag["bates"];
				$res["products"]["bates"] = $bates;
				if ($bates <= $rebate_price) {
					$res["products"]["sale_price"] = $total - $setupfeetotal - $bates * $qty;
					$res["products"]["sale_setupfee_total"] = $setupfeetotal;
					$res["products"]["sale_signal_price"] = $signal_price - $bates;
				} else {
					$negative = $bates - $rebate_price;
					$res["products"]["sale_price"] = bcsub($total - $setupfeetotal - $rebate_price, 0);
					$res["products"]["sale_setupfee_total"] = $rebate_setupfee - $negative >= 0 ? bcsub($rebate_setupfee - $negative + $total - $setupfeetotal - $rebate_price, 0) : bcsub($total - $setupfeetotal - $rebate_price, 0);
					$res["products"]["sale_signal_price"] = bcsub($signal_price - $rebate_price / $qty, 0);
				}
			}
		} else {
			$res["products"]["sale_setupfee_total"] = 0.0;
			$res["products"]["sale_signal_price"] = 0.0;
			$res["products"]["sale_price"] = 0.0;
			$res["products"]["bates"] = 0.0;
		}
		$res["products"]["type"] = $flag;
		return json(["status" => 200, "msg" => "请求成功", "data" => $res]);
	}
	public function addGoods()
	{
		$uid = request()->uid;
		if (!buyProductMustBindPhone($uid)) {
			return json(["status" => 400, "msg" => lang("CART_ADDTOSHOP_PHONE_ERROR")]);
		}
		$rule = ["pid" => "require|number", "serverid" => "number", "configoption" => "array", "customfield" => "array", "currecncyid" => "number", "qty" => "number", "os" => "array", "hostid" => "integer", "checkout" => "number|in:0,1"];
		$msg = ["pid.require" => lang("CART_ADDTOSHOP_VERIFY_PID_REQUIRE"), "pid.number" => lang("CART_ADDTOSHOP_VERIFY_PID_NUMBER"), "serverid.number" => lang("CART_ADDTOSHOP_VERIFY_SERVERID_REQUIRE"), "configoption.array" => lang("CART_ADDTOSHOP_VERIFY_CONFIG_ARRAY"), "customfield.array" => lang("CART_ADDTOSHOP_VERIFY_CUSTOM_ARRAY"), "qty.number" => lang("CART_ADDTOSHOP_VERIFY_QTY_NUMBER"), "os.array" => lang("CART_ADDTOSHOP_VERIFY_OS_ARRAY"), "hostid.integer" => lang("CART_ADDTOSHOP_VERIFY_HOSTID_INTEGER")];
		$param = $this->request->param();
		$validate = new \think\Validate($rule, $msg);
		$result = $validate->check($param);
		if (!$result) {
			return json(["status" => 400, "msg" => $validate->getError()]);
		}
		$pid = $param["pid"];
		$billingcycle = $param["billingcycle"];
		$serverid = $param["serverid"];
		$configoption = $param["configoption"];
		$customfield = $param["customfield"];
		$currencyid = $param["currencyid"];
		$checkout = intval($param["checkout"]);
		if (empty($billingcycle)) {
			$product_model = new \app\common\model\ProductModel();
			$billingcycle = $product_model->getProductCycle($pid, $currencyid, "", "", "", "", "", "", 1)[0]["billingcycle"] ?? "";
		}
		$qty = intval($param["qty"]);
		$os = isset($param["os"]) ? $param["os"] : [];
		$shop = new \app\common\logic\Shop($uid);
		$product = \think\Db::name("products")->field("host,password,name,is_truename,stock_control,qty,zjmf_api_id,upstream_pid,api_type")->where("id", $pid)->find();
		if (!judgeOntrialNum($pid, $uid, $qty) && $billingcycle == "ontrial") {
			return json(["status" => 400, "msg" => lang("CART_ONTRIAL_NUM", [$product["name"]])]);
		}
		if (!empty($product["stock_control"]) && $product["qty"] <= 0) {
			return json(["msg" => lang("CART_SETTLE_PRO_STOCK_CONTROL", [$product["name"]]), "status" => 400]);
		}
		if ($product["api_type"] == "zjmf_api" || $product["api_type"] == "resource") {
			$result = zjmfCurl($product["zjmf_api_id"], "cart/stock_control", ["pid" => $product["upstream_pid"]], 30, "GET");
			if ($result["status"] == 200) {
				$upstream_data = $result["data"];
				if (empty($upstream_data["product"])) {
					return json(["status" => 400, "msg" => "商品缺货"]);
				}
				if ($upstream_data["product"]["hidden"] == 1) {
					\think\Db::name("products")->where("id", $pid)->update(["hidden" => 1]);
					return json(["status" => 400, "msg" => "商品不存在"]);
				}
				if ($upstream_data["product"]["stock_control"] && $upstream_data["product"]["qty"] <= 0) {
					return json(["status" => 400, "msg" => lang("CART_SETTLE_PRO_STOCK_CONTROL", [$product["name"]])]);
				}
			}
		}
		$host_data = json_decode($product["host"], true);
		$host = $param["host"];
		if ($host_data["show"] == 1 && isset($param["host"])) {
			$check_res = $shop->checkHostName($host, $pid);
			if ($check_res["status"] == 400) {
				return json($check_res);
			}
		}
		$password_data = json_decode($product["password"], true);
		$password = $param["password"];
		if ($password_data["show"] == 1 && isset($param["password"])) {
			$check_res2 = $shop->checkHostPassword($password, $pid);
			if ($check_res2["status"] == 400) {
				return json($check_res2);
			}
		}
		$hostid = intval($param["hostid"]);
		$res = $shop->addProduct($pid, $billingcycle, $serverid, $configoption, $customfield, $currencyid, $qty, $os, $host, $password, $hostid, $checkout);
		if ($res["status"] == "success") {
			if ($checkout == 1) {
				return json(["status" => 200, "msg" => lang("ADD SUCCESS"), "data" => ["i" => $res["i"]]]);
			} else {
				return json(["status" => 200, "msg" => lang("ADD SUCCESS")]);
			}
		} else {
			return json(["status" => 400, "msg" => $res["msg"]]);
		}
	}
	public function cartEditPage()
	{
		$param = $this->request->param();
		$i = intval($param["position"]);
		$uid = request()->uid;
		$shop = new \app\common\logic\Shop($uid);
		$cart_data = $shop->getProductSession($i);
		$pid = $cart_data["pid"];
		$billingcycle = $cart_data["billingcycle"];
		$currencyid = priorityCurrency(intval($uid));
		$currency = get_currency();
		$customfields = new \app\common\logic\Customfields();
		$fields = $customfields->getCartCustomField($pid);
		$cart = new \app\common\logic\Cart();
		$product = $cart->getProductCycle($pid, $currencyid);
		$config_logic = new \app\common\logic\ConfigOptions();
		$alloption = $config_logic->getConfigInfo($pid);
		$alloption = $config_logic->configShow($alloption, $currencyid, $billingcycle);
		$data = ["currency" => $currency, "product" => $product, "option" => $alloption, "custom_fields" => $fields, "config_options" => $cart_data["configoptions"], "custom_fields_value" => $cart_data["customfield"] ?: [], "billingcyle" => $billingcycle, "host" => $cart_data["host"] ?: "", "password" => $cart_data["password"] ?: "", "qty" => $cart_data["qty"], "hostid" => intval($cart_data["hostid"])];
		if (getEdition()) {
			$alloption_ids = $alloption ? array_column($alloption, "id") : [];
			$cart_data_ids = $cart_data["configoptions"] ? array_keys($cart_data["configoptions"]) : [];
			if (array_intersect($alloption_ids, $cart_data_ids)) {
				foreach ($alloption as $key => $val) {
					if (!in_array($val["id"], $cart_data_ids)) {
						unset($alloption[$key]);
					}
				}
			}
			$alloption = $this->handleLinkAgeLevel($alloption);
			$alloption = $this->handleTreeArr($alloption);
			$cids = \think\Db::name("product_config_options")->alias("a")->field("a.id")->leftJoin("product_config_links b", "b.gid = a.gid")->leftJoin("product_config_groups c", "a.gid = c.id")->where("b.pid", $pid)->order("a.order", "asc")->order("a.id", "asc")->column("a.id");
			$links = \think\Db::name("product_config_options_links")->whereIN("config_id", $cids)->where("type", "condition")->where("relation_id", 0)->withAttr("sub_id", function ($value) {
				return json_decode($value, true);
			})->select()->toArray();
			if (!empty($links[0])) {
				foreach ($links as &$link) {
					$result = \think\Db::name("product_config_options_links")->where("relation_id", $link["id"])->withAttr("sub_id", function ($value) {
						return json_decode($value, true);
					})->select()->toArray();
					$link["result"] = $result;
				}
			}
			$data["option"] = $alloption;
			$data["links"] = $links;
		}
		return json(["status" => 200, "msg" => lang("SUCCESS MESSAGE"), "data" => $data]);
	}
	public function cartEdit()
	{
		$rule = ["position" => "require|number", "billingcycle" => "require", "serverid" => "number", "configoption" => "array", "customfield" => "array", "currencyid" => "number", "qty" => "number", "os" => "array", "hostid" => "integer"];
		$msg = ["position.require" => lang("CART_EDIT_TOSHOP_VERIFY_I_REQUIRE"), "position.number" => lang("CART_EDIT_TOSHOP_VERIFY_I_NUMBER"), "billingcycle.require" => lang("CART_EDIT_TOSHOP_VERIFY_bill_REQUIRE"), "serverid.number" => lang("CART_EDIT_TOSHOP_VERIFY_SID_NUMBER"), "configoption.array" => lang("CART_EDIT_TOSHOP_VERIFY_CONF_ARRAY"), "customfield.array" => lang("CART_EDIT_TOSHOP_VERIFY_CUSTOM_ARRAY"), "qty.number" => lang("CART_ADDTOSHOP_VERIFY_QTY_NUMBER"), "os.array" => lang("CART_ADDTOSHOP_VERIFY_OS_ARRAY"), "hostid.integer" => lang("CART_ADDTOSHOP_VERIFY_HOSTID_INTEGER")];
		$param = $this->request->param();
		$validate = new \think\Validate($rule, $msg);
		$result = $validate->check($param);
		if (!$result) {
			return jsons(["status" => 400, "msg" => $validate->getError()]);
		}
		$i = intval($param["position"]);
		$billingcycle = $param["billingcycle"];
		$serverid = $param["serverid"];
		$configoption = $param["configoption"];
		$customfield = $param["customfield"];
		$currencyid = $param["currencyid"];
		$qty = isset($param["qty"]) && intval($param["qty"]) ? intval($param["qty"]) : 1;
		$os = isset($param["os"]) ? $param["os"] : [];
		$uid = $this->request->uid;
		$shop = new \app\common\logic\Shop($uid);
		$cart_data = $shop->getShoppingCart();
		$pid = $cart_data["products"][$i]["pid"];
		$product = \think\Db::name("products")->field("name")->where("id", $pid)->find();
		if (!judgeOntrialNum($pid, $uid, $qty, false, false, $i) && $billingcycle == "ontrial") {
			return jsons(["status" => 400, "msg" => lang("CART_ONTRIAL_NUM", [$product["name"]])]);
		}
		if (isset($param["host"])) {
			$host = $param["host"];
			$check_res = $shop->checkHostName($host, $pid);
			if ($check_res["status"] == 400) {
				return jsons($check_res);
			}
		} else {
			$host = "";
		}
		if (isset($param["password"])) {
			$password = $param["password"];
			$check_res2 = $shop->checkHostPassword($password, $pid);
			if ($check_res2["status"] == 400) {
				return jsons($check_res2);
			}
		} else {
			$password = "";
		}
		$hostid = intval($param["hostid"]);
		$res = $shop->editProduct($i, $billingcycle, $serverid, $configoption, $customfield, $currencyid, $qty, $os, $host, $password, $hostid);
		if ($res["status"] == "success") {
			return jsons(["status" => 200, "msg" => lang("ADD SUCCESS")]);
		} else {
			return jsons(["status" => 400, "msg" => $res["msg"]]);
		}
	}
	public function cartModifyQty()
	{
		$param = $this->request->param();
		$i = intval($param["position"]);
		$qty = intval($param["qty"]);
		$pos = [];
		if (isset($param["pos"]) && is_array($param["pos"]) && !empty($param["pos"])) {
			$pos = $param["pos"];
		}
		$uid = \request()->uid;
		$shop_logic = new \app\common\logic\Shop($uid);
		$res = $shop_logic->modifyQty($i, $qty);
		if ($res["status"] != "success") {
			return json($res);
		}
		$pagedata = $shop_logic->getShopPageData(0, $pos);
		$pagedata["gateway_list"] = gateway_list("gateways");
		$pagedata["default_gateway"] = getGateway($uid);
		if ($uid) {
			$client = \think\Db::name("clients")->field("credit,credit_limit,is_open_credit_limit")->where("id", request()->uid)->find();
			$client["is_open_credit_limit"] = configuration("credit_limit") == 1 ? $client["is_open_credit_limit"] : 0;
			$client["amount_to_be_settled"] = \think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", request()->uid)->sum("total");
			$unpaid = \think\Db::name("invoices")->where("type", "credit_limit")->where("status", "Unpaid")->where("is_delete", 0)->where("uid", request()->uid)->sum("total");
			$client["credit_limit_used"] = round($client["amount_to_be_settled"] + $unpaid, 2);
			$client["credit_limit_balance"] = round($client["credit_limit"] - $client["credit_limit_used"] > 0 ? $client["credit_limit"] - $client["credit_limit_used"] : 0, 2);
			$pagedata["client"] = $client;
		}
		$pagedata["is_open_shd_credit_limit"] = configuration("shd_credit_limit");
		return jsons(["status" => 200, "msg" => lang("CART_FETDATA_SUCCESS"), "data" => $pagedata]);
	}
}