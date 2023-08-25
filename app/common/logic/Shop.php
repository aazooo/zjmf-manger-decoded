<?php

namespace app\common\logic;

class Shop
{
	const MAX_LEN = 20;
	const MIN_LEN = 6;
	public $uid;
	private $cart_data;
	public function __construct($uid)
	{
		$this->uid = intval($uid);
		$this->_init();
	}
	private function _init()
	{
		$uid = $this->uid;
		$shop_cookie = cookie("shop_cookie");
		$shop_cookie_array = [];
		if (!empty($shop_cookie)) {
			$shop_cookie_array = json_decode($shop_cookie, true);
		}
		if (!empty($uid)) {
			$data = \think\Db::name("cart_session")->where("uid", $uid)->find();
			if (!empty($data)) {
				$cart_data = json_decode($data["cart_data"], true);
				if (!empty($shop_cookie_array)) {
					if (!empty($shop_cookie_array["promo"])) {
						$cart_data["promo"] = $shop_cookie_array["promo"];
					}
					if (!empty($shop_cookie_array["products"])) {
						if (!empty($cart_data["products"])) {
							$have_ids_arr = array_unique(array_column($cart_data["products"], "pid"));
							foreach ($shop_cookie_array["products"] as $key => $value) {
								array_push($cart_data["products"], $value);
							}
						} else {
							$cart_data["products"] = $shop_cookie_array["products"];
						}
					}
				}
			} else {
				$idata = [];
				$idata["uid"] = $uid;
				$cart_data = ["uid" => $uid, "products" => [], "promo" => ""];
				$idata["status"] = 1;
				$idata["create_time"] = time();
				$idata["expire_time"] = strtotime("next year");
				if (!empty($shop_cookie_array)) {
					$cart_data["products"] = $shop_cookie_array["products"];
					$cart_data["promo"] = $shop_cookie_array["promo"];
				}
				$idata["cart_data"] = json_encode($cart_data);
				\think\Db::name("cart_session")->insert($idata);
			}
			cookie("shop_cookie", null);
			$this->cart_data = $cart_data;
			$this->save();
		} else {
			if (!empty($shop_cookie_array)) {
				$this->cart_data = $shop_cookie_array;
			} else {
				$this->cart_data = [];
			}
		}
	}
	private function save($status = 1)
	{
		$cart_data = $this->cart_data;
		$cart_data_json = json_encode($cart_data);
		$uid = $this->uid;
		if (!empty($uid)) {
			$udata = ["cart_data" => $cart_data_json, "status" => $status, "expire_time" => strtotime("next year"), "update_time" => time()];
			\think\Db::name("cart_session")->where("uid", $uid)->update($udata);
		} else {
			cookie("shop_cookie", $cart_data_json, 2592000);
		}
	}
	private function checkProductToArr($pid, $billingcycle, $serverid, $configoption, $customfield, $currencyid, $productqty, $os, $host, $password, $hostid)
	{
		$addCartArr = [];
		if (empty($pid)) {
			return ["status" => "error", "msg" => "产品ID不存在"];
		}
		$product_data = \think\Db::name("products")->field("p.*")->alias("p")->leftJoin("product_groups g", "p.gid=g.id")->where("p.id", $pid)->find();
		if (empty($product_data)) {
			return ["status" => "error", "msg" => "该产品不存在"];
		}
		$product_model = new \app\common\model\ProductModel();
		if (!$product_model->checkProductPrice($pid, $billingcycle, $currencyid)) {
			return ["status" => "error", "msg" => lang("此周期未配置价格或价格错误，请重新选择周期")];
		}
		if (!empty($serverid) && !empty($product_data["server_group"])) {
			$server_group = $product_data["server_group"];
			$server_data = \think\Db::name("servers")->where("gid", $server_group)->where("id", $serverid)->find();
			if (!empty($server_data)) {
				$addCartArr["serverid"] = $serverid;
			} else {
				return ["status" => "error", "msg" => "该服务器不存在"];
			}
		}
		$allConfigArr = \think\Db::name("product_config_options")->alias("options")->field("options.*")->leftJoin("product_config_links links", "links.gid=options.gid")->where("links.pid", $pid)->select()->toArray();
		$config_keys = array_keys($configoption);
		$allConfigArrCateId = [];
		foreach ($allConfigArr as $k => $v) {
			$allConfigArrCateId[$v["id"]] = $v;
		}
		$addCartArr["pid"] = $pid;
		$addCartArr["billingcycle"] = $billingcycle;
		if (!empty($allConfigArr)) {
			foreach ($allConfigArr as $k => $v) {
				if ($v["option_type"] == 20 && !in_array($v["id"], $config_keys)) {
					continue;
				}
				$option_type = $v["option_type"];
				$config_id = $v["id"];
				if (judgeQuantity($option_type)) {
					$qty_minimum = $v["qty_minimum"];
					$qty_maximum = $v["qty_maximum"];
					if (!empty($configoption[$config_id])) {
						$qty = $configoption[$config_id] < $qty_minimum ? $qty_minimum : $configoption[$config_id];
						$qty = $qty_maximum < $qty ? $qty_maximum : $qty;
						$addCartArr["configoptions"][$config_id] = $qty;
					} else {
						$addCartArr["configoptions"][$config_id] = $qty_minimum;
					}
				} else {
					if ($option_type == 3) {
						if (!empty($configoption[$config_id])) {
							$exists_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->where("hidden", 0)->where("id", $configoption[$config_id])->order("id", "asc")->find();
							if (!empty($exists_data)) {
								$addCartArr["configoptions"][$config_id] = $configoption[$config_id];
							} else {
								$sub_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->where("hidden", 0)->order("sort_order asc")->order("id", "asc")->find();
								if (!empty($sub_data)) {
									$addCartArr["configoptions"][$config_id] = $sub_data["id"];
								}
							}
						} else {
							$addCartArr["configoptions"][$config_id] = "";
						}
					} else {
						if (!empty($configoption[$config_id])) {
							$exists_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->where("hidden", 0)->where("id", $configoption[$config_id])->order("id", "asc")->find();
							if (!empty($exists_data)) {
								$addCartArr["configoptions"][$config_id] = $configoption[$config_id];
							} else {
								$sub_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->where("hidden", 0)->order("sort_order asc")->order("id", "asc")->find();
								if (!empty($sub_data)) {
									$addCartArr["configoptions"][$config_id] = $sub_data["id"];
								}
							}
						} else {
							$sub_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->where("hidden", 0)->order("sort_order asc")->order("id", "asc")->find();
							if (!empty($sub_data)) {
								$addCartArr["configoptions"][$config_id] = $sub_data["id"];
							}
						}
					}
				}
			}
		} else {
			$addCartArr["configoptions"] = [];
		}
		$all_customfield_arr = \think\Db::name("customfields")->where("relid", $pid)->where("type", "product")->select()->toArray();
		$custom_cateid_arr = [];
		$custom_id_arr = array_column($all_customfield_arr, "id");
		foreach ($all_customfield_arr as $k => $v) {
			$custom_cateid_arr[$v["id"]] = $v;
		}
		foreach ($custom_cateid_arr as $k1 => $v1) {
			if ($v1["required"] == 1 && empty($customfield[$k1])) {
				return ["status" => "error", "msg" => $v1["fieldname"] . "不能为空"];
			}
		}
		if (!empty($customfield) && is_array($customfield)) {
			foreach ($customfield as $k => $v) {
				$v = htmlspecialchars_decode($v, ENT_QUOTES);
				if (in_array($k, $custom_id_arr)) {
					$custom_data = $custom_cateid_arr[$k];
					if ($custom_data["required"] == 1 && empty($v)) {
						return ["status" => "error", "msg" => $custom_data["fieldname"] . "不能为空"];
					}
					if ($v && $custom_data["fieldtype"] == "dropdown") {
						$fieldoptions = $custom_data["fieldoptions"];
						$fieldoptions_arr = explode(",", $fieldoptions);
						$fieldoptions_arr[] = "无";
						$fieldoptions_arr[] = "Null";
						if (!in_array($v, $fieldoptions_arr)) {
							return ["status" => "error", "msg" => $custom_data["fieldname"] . "自定义字段不符合规则"];
						}
					}
					if ($v && !empty($custom_data["regexpr"]) && in_array($custom_data["fieldtype"], array_keys(config("customfields")))) {
						$regexpr = $custom_data["regexpr"];
						$status = preg_match("/" . str_replace("/", "\\/", $regexpr) . "/", $v);
						if (!$status) {
							return ["status" => "error", "msg" => $custom_data["fieldname"] . "自定义字段不符合规则"];
						}
					}
					$addCartArr["customfield"][$k] = $v;
				} else {
					return ["status" => "error", "msg" => "自定义字段项不存在"];
				}
			}
		}
		foreach ($customfield as $k => $v) {
			$addCartArr["customfield"][$k] = $v;
		}
		$addCartArr["qty"] = $productqty;
		$addCartArr["allow_qty"] = $product_data["allow_qty"] ?? 0;
		$addCartArr["pay_type"] = $product_data["pay_type"];
		if (cartCheckOs($pid, $os)) {
			$addCartArr["os"] = $os;
		}
		$addCartArr["host"] = $host ?? "";
		$addCartArr["password"] = $password ?? "";
		$addCartArr["hostid"] = $hostid;
		return ["status" => "success", "data" => $addCartArr];
	}
	public function addProduct($pid, $billingcycle, $serverid, $configoption, $customfield, $currencyid, $qty, $os, $host, $password, $hostid, $checkuot = 0)
	{
		$res = $this->checkProductToArr($pid, $billingcycle, $serverid, $configoption, $customfield, $currencyid, $qty, $os, $host, $password, $hostid);
		if ($res["status"] == "error") {
			return $res;
		}
		$addCartArr = $res["data"];
		$cart_data = $this->cart_data;
		$cart_data["products"][] = $addCartArr;
		$this->cart_data = $cart_data;
		$this->save();
		if ($checkuot == 1) {
			$i = count($this->{$cart_data}["products"]) - 1;
		} else {
			$i = 0;
			$hook_data = ["pid" => $pid, "serverid" => $serverid, "currencyid" => $currencyid, "configoption" => $configoption, "customfield" => $customfield, "qty" => $qty, "host" => $host, "password" => $password];
			hook("shopping_cart_add_product", $hook_data);
		}
		return ["status" => "success", "i" => $i];
	}
	public function removeProduct($i)
	{
		$cart_data = $this->cart_data;
		$products_arr = $cart_data["products"];
		if (is_array($i)) {
			foreach ($i as $i_v) {
				if (array_key_exists($i_v, $products_arr)) {
					$remove_data[] = $products_arr[$i_v];
					unset($products_arr[$i_v]);
					$new_products_arr = array_values($products_arr);
				} else {
					$new_products_arr = $products_arr;
				}
			}
		} else {
			if (array_key_exists($i, $products_arr)) {
				$remove_data[] = $products_arr[$i];
				unset($products_arr[$i]);
				$new_products_arr = array_values($products_arr);
			} else {
				$new_products_arr = $products_arr;
			}
		}
		$cart_data["products"] = $new_products_arr;
		if (empty($new_products_arr[0])) {
			$this->cart_data = null;
		} else {
			$this->cart_data = $cart_data;
		}
		if (!empty($remove_data)) {
			hook("shopping_cart_remove_product", $remove_data);
		}
		$this->save();
		return ["status" => "success"];
	}
	public function getProductSession($i)
	{
		$i = intval($i);
		$cart_data = $this->cart_data;
		return $cart_data["products"][$i];
	}
	public function editProduct($i, $billingcycle, $serverid, $configoption, $customfield, $currencyid, $qty, $os, $host, $password, $hostid)
	{
		$i = intval($i);
		$cart_data = $this->cart_data;
		$pid = $cart_data["products"][$i]["pid"];
		if (empty($pid)) {
			return ["status" => "error", "msg" => "产品不存在"];
		}
		$res = $this->checkProductToArr($pid, $billingcycle, $serverid, $configoption, $customfield, $currencyid, $qty, $os, $host, $password, $hostid);
		if ($res["status"] == "error") {
			return $res;
		}
		$editCartArr = $res["data"];
		$cart_data["products"][$i] = $editCartArr;
		$this->cart_data = $cart_data;
		$this->save();
		return ["status" => "success"];
	}
	public function getShoppingCart()
	{
		return $this->cart_data;
	}
	public function checkPromoCode($promotioncode)
	{
		$uid = $this->uid;
		$data = \think\Db::name("promo_code")->where("code", $promotioncode)->find();
		$id = $data["id"];
		$maxuses = $data["max_times"];
		$uses = $data["used"];
		$startdate = $data["start_time"];
		$expiredate = $data["expiration_time"];
		$newsignups = $data["only_new_client"];
		$existingclient = $data["only_old_client"];
		$onceperclient = $data["once_per_client"];
		$one_time = $data["one_time"];
		$used = $data["used"];
		if (!$id) {
			$promoerrormessage = "您输入的优惠码不存在";
			return $promoerrormessage;
		}
		if ($startdate != "0") {
			if (time() < $startdate) {
				$promoerrormessage = "这个优惠还未开始，请重新尝试。";
				return $promoerrormessage;
			}
		}
		if ($expiredate != "0") {
			if ($expiredate < time()) {
				$promoerrormessage = "优惠码已过期";
				return $promoerrormessage;
			}
		}
		if ($maxuses > 0 && $maxuses <= $uses) {
			$promoerrormessage = "您输入的优惠码已被使用";
			return $promoerrormessage;
		}
		if ($newsignups && !empty($uid)) {
			$previousorders = \think\Db::name("orders")->where("uid", $uid)->count();
			if ($previousorders > 0) {
				$promoerrormessage = "此优惠码仅适用于新用户";
				return $promoerrormessage;
			}
		}
		if ($existingclient) {
			if ($uid) {
				$orderCount = \think\Db::name("orders")->where("uid", $uid)->where("status", "Active")->count();
				if ($orderCount == 0) {
					$promoerrormessage = "您必须至少有一个已核验通过的订单才能使用此优惠码";
					return $promoerrormessage;
				}
			} else {
				$promoerrormessage = "您必须至少有一个已核验通过的订单才能使用此优惠码";
				return $promoerrormessage;
			}
		}
		if ($onceperclient && $uid) {
			$orderCount = \think\Db::name("orders")->where("promo_code", $promotioncode)->where("uid", $uid)->whereIn("status", "Pending,Active")->count();
			if ($orderCount > 0) {
				$promoerrormessage = "此优惠码每位用户只能使用一次";
				return $promoerrormessage;
			}
		}
		return 1;
	}
	public function modifyQty($i, $qty)
	{
		if (!is_numeric($i)) {
			return ["status" => "error", "msg" => "未传入产品所在购物车位置编号或传入参数有误"];
		}
		if (empty($qty) || !is_numeric($qty) || $qty <= 0) {
			return ["status" => "error", "msg" => "未传入数量或传入数量有误"];
		}
		$i = intval($i);
		$qty = intval($qty);
		$cart_data = $this->cart_data;
		$pid = $cart_data["products"][$i]["pid"];
		if (empty($pid)) {
			return ["status" => "error", "msg" => "产品不存在"];
		}
		$product = \think\Db::name("products")->field("stock_control,qty")->where("id", $pid)->find();
		if ($product["stock_control"] == 1 && $product["qty"] < $qty) {
			return ["status" => "error", "msg" => "产品库存数量不足"];
		}
		$cart_data["products"][$i]["qty"] = $qty;
		$this->cart_data = $cart_data;
		$this->save();
		hook("shopping_cart_modify_num", ["pid" => $pid, "num" => $qty]);
		return ["status" => "success", "msg" => "修改产品数量成功"];
	}
	public function addPromo($promo = "")
	{
		if (empty($promo)) {
			return ["status" => "error", "msg" => "未传入优惠码"];
		}
		$cart_data = $this->cart_data;
		$this->cart_data["promo"] = "";
		$res = $this->checkPromoCode($promo);
		if ($res != 1) {
			return ["status" => "error", "msg" => $res];
		}
		$this->cart_data["promo"] = $promo;
		$this->save();
		return ["status" => "success", "msg" => "优惠码添加成功"];
	}
	public function removePromo()
	{
		$this->cart_data["promo"] = "";
		$this->save();
		return ["status" => "success", "msg" => "优惠码移除成功"];
	}
	private function getCurrency($currency)
	{
		$currency = intval($currency);
		if ($currency == 0) {
			$currdata = \think\Db::name("currencies")->field("id,code,prefix,suffix,rate")->where("default", 1)->find();
		} else {
			$currdata = \think\Db::name("currencies")->field("id,code,prefix,suffix,rate")->where("id", $currency)->find();
			if (empty($currdata)) {
				$currdata = \think\Db::name("currencies")->field("id,code,prefix,suffix,rate")->where("default", 1)->find();
			}
		}
		return $currdata;
	}
	public function getShopPageData($currency = 0, $pos = [])
	{
		$cart_data = $this->cart_data;
		$uid = $this->uid;
		if ($currency) {
			$currency = \think\Db::name("currencies")->where("id", $currency)->find();
		} else {
			$currency = getUserCurrency($uid);
		}
		$currencyid = $currency["id"];
		$currency_prefix = $currency["prefix"];
		$currency_suffix = $currency["suffix"];
		$pagedata = [];
		$pagedata["currency"] = $currency;
		$default_rate = \think\Db::name("currencies")->where("default", 1)->value("rate");
		$client_default = $currency["rate"];
		$rate = bcdiv($client_default, $default_rate, 2);
		$products = $cart_data["products"];
		$new_session_products = [];
		$nopos = empty($pos) ? true : false;
		if (!empty($products)) {
			$cart_products = [];
			$total_pricing = 0;
			$total_setup_pricing = 0;
			foreach ($products as $k => $v) {
				if (VIEW_TEMPLATE_WEBSITE === true || $nopos) {
					$pos[] = $k;
				}
				$pid = $v["pid"];
				$billingcycle = $v["billingcycle"];
				$serverid = $v["serverid"];
				$configoption = $v["configoptions"];
				$customfield = $v["customfield"];
				$qty = $v["qty"] ?? 1;
				$os = $v["os"] ?? [];
				$host = $v["host"] ?? "";
				$password = $v["password"] ?? "";
				$hostid = $v["hostid"] ?? 0;
				$res = $this->checkProductToArr($pid, $billingcycle, $serverid, $configoption, $customfield, $currencyid, $qty, $os, $host, $password, $hostid);
				if ($res["status"] == "error") {
					continue;
				}
				$new_session_products[] = $res["data"];
				$product_data = \think\Db::name("products")->where("id", $pid)->find();
				$is_zjmfapi = false;
				if ($product_data["api_type"] == "zjmf_api" && $product_data["upstream_version"] > 0 && $product_data["upstream_price_type"] == "percent") {
					$is_zjmfapi = true;
				}
				if ($product_data["api_type"] == "resource") {
					$user_grade = resourceUserGradePercent($uid, $pid);
					$shop = \think\Db::name("res_products")->field("b.id,b.name,b.img")->alias("a")->leftJoin("res_shop b", "a.shop_id=b.id")->where("a.productid", $pid)->find();
					$cart_products["shop_id"] = $shop["id"];
					$cart_products["shop_name"] = $shop["name"];
					$cart_products["shop_logo"] = "/upload/common/resource/" . $shop["img"];
				}
				$cart_products["productid"] = $pid;
				$cart_products["productsname"] = $product_data["name"];
				$cart_products["api_type"] = $product_data["api_type"];
				if (in_array($product_data["type"], array_keys(config("developer_app_product_type"))) && $product_data["p_uid"] > 0) {
					$nav_group = \think\Db::name("host")->alias("a")->field("c.id,c.fa_icon,c.groupname,a.id as hostid")->leftJoin("products b", "a.productid = b.id")->leftJoin("nav_group c", "c.id = b.groupid")->where("a.id", $hostid)->find();
					$cart_products["groupn"] = $nav_group;
				} else {
					$nav_group = \think\Db::name("nav_group")->where("id", $product_data["groupid"])->find();
					$nav_group["hostid"] = 0;
					$cart_products["groupn"] = $nav_group;
				}
				if (!empty($v["serverid"])) {
					$serverid = $v["serverid"];
					$server_data = \think\Db::name("servers")->field("name")->where("id", $serverid)->find();
					$cart_products["serverid"] = $serverid;
					$cart_products["servername"] = $server_data["name"];
				}
				$billingcycle = $v["billingcycle"];
				$pay_ontrial_cycle = json_decode($res["data"]["pay_type"], true);
				$cart_products["pay_ontrial_cycle"] = $pay_ontrial_cycle["pay_ontrial_cycle"];
				$cart_products["pay_ontrial_cycle_type"] = $pay_ontrial_cycle["pay_ontrial_cycle_type"] ?: "day";
				$cart_products["product_type"] = $product_data["type"];
				$rule = \think\Db::name("products")->field("host,password,is_truename")->where("id", $pid)->find();
				$cart_products["host_show"] = json_decode($rule["host"], true)["show"] ?? 0;
				$cart_products["password_show"] = json_decode($rule["password"], true)["show"] ?? 0;
				$cart_products["is_truename"] = $rule["is_truename"];
				$cart_products["host"] = $res["data"]["host"] ?? "";
				$cart_products["password"] = $res["data"]["password"] ?? "";
				$cart_products["allow_qty"] = $res["data"]["allow_qty"] ?? 0;
				$cart_products["os_name"] = array_values($v["os"])[0] ?? "";
				$cart_products["billingcycle"] = $billingcycle;
				$cart_products["qty"] = $qty;
				$cart_products["billingcycle_desc"] = config("billing_cycle_unit")[$billingcycle];
				if ($billingcycle != "free") {
					$pricing_field = config("price_type")[$billingcycle];
					$itself_field = $pricing_field[0];
					$setupfee_field = $pricing_field[1];
					$pricing_field_str = implode(",", $pricing_field);
					$prodcut_pricing_data = $this->getProductPricing($pid, $pricing_field_str ?? "", $currencyid);
					$product_pricing = $prodcut_pricing_data[$itself_field] > 0 ? $prodcut_pricing_data[$itself_field] : 0;
					$product_setup_pricing = $prodcut_pricing_data[$setupfee_field] > 0 ? $prodcut_pricing_data[$setupfee_field] : 0;
				} else {
					$product_pricing = 0;
					$product_setup_pricing = 0;
				}
				$configoptions = $res["data"]["configoptions"];
				$configoptions_showarr = [];
				$configoptions_sale_arr = [];
				$config_total_pricing = 0.0;
				$config_setup_total_pricing = 0.0;
				if (!empty($configoptions)) {
					$config_ids = array_keys($configoptions);
					$config_data = \think\Db::name("product_config_options")->whereIn("id", $config_ids)->order("order", "asc")->select()->toArray();
					foreach ($config_data as $j => $vvv) {
						$is_discount = $vvv["is_discount"];
						$config_id = $vvv["id"];
						$option_name = $vvv["option_name"];
						$option_type = $vvv["option_type"];
						$option_sub = $configoptions[$config_id];
						if (judgeQuantity($option_type)) {
							$option_sub_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->where("qty_minimum", "<=", $option_sub)->where("qty_maximum", ">=", $option_sub)->find();
						} else {
							$option_sub_data = \think\Db::name("product_config_options_sub")->where("id", $option_sub)->find();
						}
						$option_sub_id = $option_sub_data["id"];
						$sub_option_name = $option_sub_data["option_name"];
						if ($billingcycle != "free" && $vvv["hidden"] != 1) {
							$option_sub_pricing = $this->getConfigoptionsPricing($option_sub_id, $pricing_field_str, $currencyid);
							$sub_pricing = $option_sub_pricing[$itself_field] > 0 ? $option_sub_pricing[$itself_field] : 0;
							$sub_setup_pricing = $option_sub_pricing[$setupfee_field] > 0 ? $option_sub_pricing[$setupfee_field] : 0;
						} else {
							$sub_pricing = 0;
							$sub_setup_pricing = 0;
						}
						$config_str = explode("|", $option_name)[1] ? explode("|", $option_name)[1] : $option_name;
						$sub_option_name = explode("|", $sub_option_name)[1] ? explode("|", $sub_option_name)[1] : $sub_option_name;
						$sub_option_name = str_replace("^", " ", $sub_option_name);
						if (judgeQuantity($option_type)) {
							if ($sub_pricing >= 0 && $vvv["hidden"] != 1) {
								if (judgeQuantityStage($option_type)) {
									$sum = quantityStagePrice($config_id, $currencyid, $option_sub, $billingcycle);
									$sub_pricing = $sum[0];
									$sub_setup_pricing = $sum[1];
									$config_total_pricing += $sub_pricing;
									$config_setup_total_pricing += $sub_setup_pricing;
								} else {
									$config_total_pricing += $sub_pricing * $option_sub;
									if ($option_sub == 0) {
										$sub_pricing = 0;
										$sub_setup_pricing = 0;
									} else {
										$sub_pricing = $sub_pricing * $option_sub;
									}
									$config_setup_total_pricing += $sub_setup_pricing;
								}
							}
							$configoptions_showarr[$config_str]["value"] = $option_sub . configoptionsUnit($option_type);
						} else {
							if ($sub_pricing >= 0) {
								$config_total_pricing += $sub_pricing;
								$config_setup_total_pricing += $sub_setup_pricing;
							} else {
								$config_total_pricing += 0;
								$config_setup_total_pricing += 0;
							}
							$configoptions_showarr[$config_str]["value"] = $sub_option_name;
						}
						$configoptions_showarr[$config_str]["option_type"] = $option_type;
						$configoptions_showarr[$config_str]["hidden"] = $vvv["hidden"];
						$sale_arr = [];
						if ($is_zjmfapi) {
							$sub_pricing = bcmul($sub_pricing, $product_data["upstream_price_value"], 2) / 100;
							$sub_setup_pricing = bcmul($sub_setup_pricing, $product_data["upstream_price_value"], 2) / 100;
						}
						if ($product_data["api_type"] == "resource") {
							$sub_pricing = bcmul($sub_pricing, $user_grade, 2) / 100;
							$sub_setup_pricing = bcmul($sub_setup_pricing, $user_grade, 2) / 100;
						}
						$sale_arr["config_base_sale"] = bcadd($sub_pricing, $sub_setup_pricing, 2);
						$sale_arr["config_base_sale_setupfee"] = $sub_setup_pricing;
						$sale_arr["is_discount"] = $is_discount;
						$sale_arr["id"] = $config_id;
						$sale_arr["is_rebate"] = $vvv["is_rebate"];
						$sale_arr["config_sale"] = bcadd($sub_pricing, $sub_setup_pricing, 2);
						$configoptions_sale_arr[] = $sale_arr;
					}
				}
				foreach ($configoptions_showarr as $kkkk => $vvvv) {
					if ($vvvv["hidden"] == 1) {
						unset($configoptions_showarr[$kkkk]);
					}
				}
				$cart_products["configoptions"] = $configoptions_showarr;
				$cart_products["configoptions_base_sale"] = $configoptions_sale_arr;
				if ($is_zjmfapi) {
					$product_pricing = bcmul($product_pricing, $product_data["upstream_price_value"], 2) / 100;
					$config_total_pricing = bcmul($config_total_pricing, $product_data["upstream_price_value"], 2) / 100;
					$product_setup_pricing = bcmul($product_setup_pricing, $product_data["upstream_price_value"], 2) / 100;
					$config_setup_total_pricing = bcmul($config_setup_total_pricing, $product_data["upstream_price_value"], 2) / 100;
				}
				if ($product_data["api_type"] == "resource") {
					$product_pricing = bcmul($product_pricing, $user_grade, 2) / 100;
					$config_total_pricing = bcmul($config_total_pricing, $user_grade, 2) / 100;
					$product_setup_pricing = bcmul($product_setup_pricing, $user_grade, 2) / 100;
					$config_setup_total_pricing = bcmul($config_setup_total_pricing, $user_grade, 2) / 100;
				}
				$cart_products["product_base_sale"] = bcadd($product_pricing, $product_setup_pricing, 2);
				$cart_products["product_base_sale_setupfee"] = $product_setup_pricing;
				$pricing = bcadd($product_pricing, $config_total_pricing, 2);
				$setup_pricing = bcadd($product_setup_pricing, $config_setup_total_pricing, 2);
				$cart_products["product_pricing"] = bcadd($pricing, $setup_pricing, 2);
				$cart_products["total_sale"] = bcadd($pricing, $setup_pricing, 2);
				$cart_products["product_sale"] = bcadd($product_pricing, $product_setup_pricing, 2);
				$cart_products["total_sale_setupfee"] = $setup_pricing;
				$cart_products["pricing"] = floatval(sprintf("%.2f", $pricing * $rate));
				$cart_products["setup_pricing"] = floatval(sprintf("%.2f", $setup_pricing * $rate));
				if (in_array($k, $pos)) {
					$total_pricing += bcmul($pricing, $qty, 2);
					$total_setup_pricing += bcmul($setup_pricing, $qty, 2);
				}
				if ($hostid > 0) {
					$product = \think\Db::name("products")->field("id,professional_discount")->where("id", $pid)->where("p_uid", ">", 0)->find();
					$activity = \think\Db::name("app_activity_rel")->alias("a")->field("a.id,b.object,b.discount")->leftJoin("app_activity b", "b.id=a.activity_id")->where("b.start_time", "<=", time())->where("b.end_time", ">=", time())->where("a.pid", $pid)->find();
					$auth = \think\Db::name("host")->alias("a")->field("a.id,b.config_option2")->leftJoin("products b", "b.id=a.productid")->where("a.id", $hostid)->find();
					$cart_products["discount_pricing"] = $pricing;
					$cart_products["discount_amount"] = 0.0;
					if (!empty($auth) && $auth["config_option2"] == "professional") {
						if (!empty($activity)) {
							$cart_products["discount_pricing"] = in_array($activity["object"], [0, 1]) ? round($cart_products["discount_pricing"] * (100 - $activity["discount"]) / 100, 2) : $cart_products["discount_pricing"];
						}
						$cart_products["discount_pricing"] = round($cart_products["discount_pricing"] * (100 - $product["professional_discount"]) / 100, 2);
						$cart_products["discount_amount"] = bcsub($pricing, $cart_products["discount_pricing"], 2);
					} else {
						if (!empty($activity)) {
							$cart_products["discount_pricing"] = in_array($activity["object"], [0, 2]) ? round($cart_products["discount_pricing"] * (100 - $activity["discount"]) / 100, 2) : $cart_products["discount_pricing"];
							$cart_products["discount_amount"] = bcsub($pricing, $cart_products["discount_pricing"], 2);
						}
					}
				}
				$pagedata["cart_products"][] = $cart_products;
			}
			$total = $total_pricing + $total_setup_pricing;
			$total_sale = $this->getSaleData($uid, $pos, $pagedata["cart_products"]);
			$pagedata["saleproducts"] = bcsub($total, $total_sale, 2);
			$subtotal = bcsub(round($total_sale, 2), 0, 2);
			if (isset($cart_data["promo"])) {
				$promo = $cart_data["promo"];
				$use_promo_data = [];
				foreach ($pagedata["cart_products"] as $kk => $vv) {
					$use_promo_data[] = ["productid" => $vv["productid"], "billingcycle" => $vv["billingcycle"], "pricing" => $vv["pricing"], "setup_pricing" => $vv["setup_pricing"], "qty" => $vv["qty"], "product_sale" => $vv["product_sale"], "configoptions_base_sale" => $vv["configoptions_base_sale"], "total_sale" => $vv["total_sale"], "total_sale_setupfee" => $vv["total_sale_setupfee"]];
				}
				$res = $this->checkPromoCode($promo);
				if ($res == 1) {
					$promo_price_data = $this->handlePromoPrice($use_promo_data, $promo, $currency_prefix, $currency_suffix, $pos);
					if ($promo_price_data["status"] == "success") {
						$pagedata["promo"] = $promo_price_data["data"];
						$subtotal = $subtotal - $promo_price_data["data"]["promo_price"];
					} elseif ($promo_price_data["status"] == "error") {
						$pagedata["promo_error_desc"] = $promo_price_data["msg"];
					} else {
						$pagedata["promo_waring_desc"] = $promo_price_data["msg"];
					}
				} else {
					$cart_data["promo"] = "";
					$pagedata["promo_error_desc"] = $res;
				}
			}
			foreach ($pagedata["cart_products"] as &$m) {
				$m["pricing"] = bcsub(bcmul($m["pricing"], $m["qty"]), 0, 2);
				$m["setup_pricing"] = bcsub(bcmul($m["setup_pricing"], $m["qty"]), 0, 2);
				if ($m["api_type"] != "resource") {
					unset($m["configoptions"]);
					unset($m["configoptions_base_sale"]);
					unset($m["total_sale"]);
					unset($m["total_sale_setupfee"]);
				}
				if ($m["_sale_price"] < 0) {
					$m["_sale_price"] = 0;
				}
			}
			$subtotal = $subtotal < 0 ? number_format(0, 2) : bcsub($subtotal, 0, 2);
			$pagedata["total_price"] = $subtotal;
			$pagedata["total_desc"] = $currency_prefix . sprintf("%.2f", $subtotal) . $currency_suffix;
			$cart_data["products"] = $new_session_products;
			$this->cart_data = $cart_data;
			$this->save();
			return $pagedata;
		} else {
			$pagedata["cart_products"] = [];
			$pagedata["total_desc"] = $currency_prefix . "0.00" . $currency_suffix;
			return $pagedata;
		}
	}
	public function getShopPageDataOpenAPI($currency = 0, $pos = [])
	{
		$cart_data = $this->cart_data;
		$uid = $this->uid;
		if ($currency) {
			$currency = \think\Db::name("currencies")->where("id", $currency)->find();
		} else {
			$currency = getUserCurrency($uid);
		}
		$currencyid = $currency["id"];
		$currency_prefix = $currency["prefix"];
		$currency_suffix = $currency["suffix"];
		$pagedata = [];
		$pagedata["currency"] = $currency;
		$default_rate = \think\Db::name("currencies")->where("default", 1)->value("rate");
		$client_default = $currency["rate"];
		$rate = bcdiv($client_default, $default_rate, 2);
		$products = $cart_data["products"];
		$new_session_products = [];
		$nopos = empty($pos) ? true : false;
		if (!empty($products)) {
			$cart_products = [];
			$total_pricing = 0;
			$total_setup_pricing = 0;
			foreach ($products as $k => $v) {
				if (VIEW_TEMPLATE_WEBSITE === true || $nopos) {
					$pos[] = $k;
				}
				$pid = $v["pid"];
				$billingcycle = $v["billingcycle"];
				$serverid = $v["serverid"];
				$configoption = $v["configoptions"];
				$customfield = $v["customfield"];
				$qty = $v["qty"] ?? 1;
				$os = $v["os"] ?? [];
				$host = $v["host"] ?? "";
				$password = $v["password"] ?? "";
				$hostid = $v["hostid"] ?? 0;
				$res = $this->checkProductToArr($pid, $billingcycle, $serverid, $configoption, $customfield, $currencyid, $qty, $os, $host, $password, $hostid);
				if ($res["status"] == "error") {
					continue;
				}
				$new_session_products[] = $res["data"];
				$product_data = \think\Db::name("products")->where("id", $pid)->find();
				$is_zjmfapi = false;
				if ($product_data["api_type"] == "zjmf_api" && $product_data["upstream_version"] > 0 && $product_data["upstream_price_type"] == "percent") {
					$is_zjmfapi = true;
				}
				if ($product_data["api_type"] == "resource") {
					$user_grade = resourceUserGradePercent($uid, $pid);
					$shop = \think\Db::name("res_products")->field("b.id,b.name,b.img")->alias("a")->leftJoin("res_shop b", "a.shop_id=b.id")->where("a.productid", $pid)->find();
					$cart_products["shop_id"] = $shop["id"];
					$cart_products["shop_name"] = $shop["name"];
					$cart_products["shop_logo"] = "/upload/common/resource/" . $shop["img"];
				}
				$cart_products["productid"] = $pid;
				$cart_products["productsname"] = $product_data["name"];
				$cart_products["api_type"] = $product_data["api_type"];
				if (in_array($product_data["type"], array_keys(config("developer_app_product_type"))) && $product_data["p_uid"] > 0) {
					$nav_group = \think\Db::name("host")->alias("a")->field("c.id,c.fa_icon,c.groupname,a.id as hostid")->leftJoin("products b", "a.productid = b.id")->leftJoin("nav_group c", "c.id = b.groupid")->where("a.id", $hostid)->find();
					$cart_products["groupn"] = $nav_group;
				} else {
					$nav_group = \think\Db::name("nav_group")->where("id", $product_data["groupid"])->find();
					$nav_group["hostid"] = 0;
					$cart_products["groupn"] = $nav_group;
				}
				if (!empty($v["serverid"])) {
					$serverid = $v["serverid"];
					$server_data = \think\Db::name("servers")->field("name")->where("id", $serverid)->find();
					$cart_products["serverid"] = $serverid;
					$cart_products["servername"] = $server_data["name"];
				}
				$billingcycle = $v["billingcycle"];
				$pay_ontrial_cycle = json_decode($res["data"]["pay_type"], true);
				$cart_products["pay_ontrial_cycle"] = $pay_ontrial_cycle["pay_ontrial_cycle"];
				$cart_products["pay_ontrial_cycle_type"] = $pay_ontrial_cycle["pay_ontrial_cycle_type"] ?: "day";
				$cart_products["product_type"] = $product_data["type"];
				$rule = \think\Db::name("products")->field("host,password,is_truename")->where("id", $pid)->find();
				$cart_products["host_show"] = json_decode($rule["host"], true)["show"] ?? 0;
				$cart_products["password_show"] = json_decode($rule["password"], true)["show"] ?? 0;
				$cart_products["is_truename"] = $rule["is_truename"];
				$cart_products["host"] = $res["data"]["host"] ?? "";
				$cart_products["password"] = $res["data"]["password"] ?? "";
				$cart_products["allow_qty"] = $res["data"]["allow_qty"] ?? 0;
				$cart_products["os_name"] = array_values($v["os"])[0] ?? "";
				$cart_products["billingcycle"] = $billingcycle;
				$cart_products["qty"] = $qty;
				$cart_products["billingcycle_desc"] = config("billing_cycle_unit")[$billingcycle];
				if ($billingcycle != "free") {
					$pricing_field = config("price_type")[$billingcycle];
					$itself_field = $pricing_field[0];
					$setupfee_field = $pricing_field[1];
					$pricing_field_str = implode(",", $pricing_field);
					$prodcut_pricing_data = $this->getProductPricing($pid, $pricing_field_str ?? "", $currencyid);
					$product_pricing = $prodcut_pricing_data[$itself_field] > 0 ? $prodcut_pricing_data[$itself_field] : 0;
					$product_setup_pricing = $prodcut_pricing_data[$setupfee_field] > 0 ? $prodcut_pricing_data[$setupfee_field] : 0;
				} else {
					$product_pricing = 0;
					$product_setup_pricing = 0;
				}
				$configoptions = $res["data"]["configoptions"];
				$configoptions_showarr = [];
				$configoptions_sale_arr = [];
				$config_total_pricing = 0.0;
				$config_setup_total_pricing = 0.0;
				if (!empty($configoptions)) {
					$config_ids = array_keys($configoptions);
					$config_data = \think\Db::name("product_config_options")->whereIn("id", $config_ids)->order("order", "asc")->select()->toArray();
					foreach ($config_data as $j => $vvv) {
						$is_discount = $vvv["is_discount"];
						$config_id = $vvv["id"];
						$option_name = $vvv["option_name"];
						$option_type = $vvv["option_type"];
						$option_sub = $configoptions[$config_id];
						if (judgeQuantity($option_type)) {
							$option_sub_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->where("qty_minimum", "<=", $option_sub)->where("qty_maximum", ">=", $option_sub)->find();
						} else {
							$option_sub_data = \think\Db::name("product_config_options_sub")->where("id", $option_sub)->find();
						}
						$option_sub_id = $option_sub_data["id"];
						$sub_option_name = $option_sub_data["option_name"];
						if ($billingcycle != "free" && $vvv["hidden"] != 1) {
							$option_sub_pricing = $this->getConfigoptionsPricing($option_sub_id, $pricing_field_str, $currencyid);
							$sub_pricing = $option_sub_pricing[$itself_field] > 0 ? $option_sub_pricing[$itself_field] : 0;
							$sub_setup_pricing = $option_sub_pricing[$setupfee_field] > 0 ? $option_sub_pricing[$setupfee_field] : 0;
						} else {
							$sub_pricing = 0;
							$sub_setup_pricing = 0;
						}
						$config_str = explode("|", $option_name)[1] ? explode("|", $option_name)[1] : $option_name;
						$sub_option_name = explode("|", $sub_option_name)[1] ? explode("|", $sub_option_name)[1] : $sub_option_name;
						$sub_option_name = str_replace("^", " ", $sub_option_name);
						if (judgeQuantity($option_type)) {
							if ($sub_pricing >= 0 && $vvv["hidden"] != 1) {
								if (judgeQuantityStage($option_type)) {
									$sum = quantityStagePrice($config_id, $currencyid, $option_sub, $billingcycle);
									$sub_pricing = $sum[0];
									$sub_setup_pricing = $sum[1];
									$config_total_pricing += $sub_pricing;
									$config_setup_total_pricing += $sub_setup_pricing;
								} else {
									$config_total_pricing += $sub_pricing * $option_sub;
									if ($option_sub == 0) {
										$sub_pricing = 0;
										$sub_setup_pricing = 0;
									} else {
										$sub_pricing = $sub_pricing * $option_sub;
									}
									$config_setup_total_pricing += $sub_setup_pricing;
								}
							}
							$configoptions_showarr[$config_str]["value"] = $option_sub . configoptionsUnit($option_type);
						} else {
							if ($sub_pricing >= 0) {
								$config_total_pricing += $sub_pricing;
								$config_setup_total_pricing += $sub_setup_pricing;
							} else {
								$config_total_pricing += 0;
								$config_setup_total_pricing += 0;
							}
							$configoptions_showarr[$config_str]["value"] = $sub_option_name;
						}
						$configoptions_showarr[$config_str]["option_type"] = $option_type;
						$configoptions_showarr[$config_str]["hidden"] = $vvv["hidden"];
						$sale_arr = [];
						if ($is_zjmfapi) {
							$sub_pricing = bcmul($sub_pricing, $product_data["upstream_price_value"], 2) / 100;
							$sub_setup_pricing = bcmul($sub_setup_pricing, $product_data["upstream_price_value"], 2) / 100;
						}
						if ($product_data["api_type"] == "resource") {
							$sub_pricing = bcmul($sub_pricing, $user_grade, 2) / 100;
							$sub_setup_pricing = bcmul($sub_setup_pricing, $user_grade, 2) / 100;
						}
						$sale_arr["config_base_sale"] = bcadd($sub_pricing, $sub_setup_pricing, 2);
						$sale_arr["config_base_sale_setupfee"] = $sub_setup_pricing;
						$sale_arr["is_discount"] = $is_discount;
						$sale_arr["id"] = $config_id;
						$sale_arr["is_rebate"] = $vvv["is_rebate"];
						$sale_arr["config_sale"] = bcadd($sub_pricing, $sub_setup_pricing, 2);
						$configoptions_sale_arr[] = $sale_arr;
					}
				}
				foreach ($configoptions_showarr as $kkkk => $vvvv) {
					if ($vvvv["hidden"] == 1) {
						unset($configoptions_showarr[$kkkk]);
					}
				}
				$cart_products["configoptions"] = $configoptions_showarr;
				$cart_products["configoptions_base_sale"] = $configoptions_sale_arr;
				if ($is_zjmfapi) {
					$product_pricing = bcmul($product_pricing, $product_data["upstream_price_value"], 2) / 100;
					$config_total_pricing = bcmul($config_total_pricing, $product_data["upstream_price_value"], 2) / 100;
					$product_setup_pricing = bcmul($product_setup_pricing, $product_data["upstream_price_value"], 2) / 100;
					$config_setup_total_pricing = bcmul($config_setup_total_pricing, $product_data["upstream_price_value"], 2) / 100;
				}
				if ($product_data["api_type"] == "resource") {
					$product_pricing = bcmul($product_pricing, $user_grade, 2) / 100;
					$config_total_pricing = bcmul($config_total_pricing, $user_grade, 2) / 100;
					$product_setup_pricing = bcmul($product_setup_pricing, $user_grade, 2) / 100;
					$config_setup_total_pricing = bcmul($config_setup_total_pricing, $user_grade, 2) / 100;
				}
				$cart_products["product_base_sale"] = bcadd($product_pricing, $product_setup_pricing, 2);
				$cart_products["product_base_sale_setupfee"] = $product_setup_pricing;
				$pricing = bcadd($product_pricing, $config_total_pricing, 2);
				$setup_pricing = bcadd($product_setup_pricing, $config_setup_total_pricing, 2);
				$cart_products["product_pricing"] = bcadd($pricing, $setup_pricing, 2);
				$cart_products["total_sale"] = bcadd($pricing, $setup_pricing, 2);
				$cart_products["product_sale"] = bcadd($product_pricing, $product_setup_pricing, 2);
				$cart_products["total_sale_setupfee"] = $setup_pricing;
				$cart_products["pricing"] = floatval(sprintf("%.2f", $pricing * $rate));
				$cart_products["setup_pricing"] = floatval(sprintf("%.2f", $setup_pricing * $rate));
				if (in_array($k, $pos)) {
					$total_pricing += bcmul($pricing, $qty, 2);
					$total_setup_pricing += bcmul($setup_pricing, $qty, 2);
				}
				if ($hostid > 0) {
					$product = \think\Db::name("products")->field("id,professional_discount")->where("id", $pid)->where("p_uid", ">", 0)->find();
					$activity = \think\Db::name("app_activity_rel")->alias("a")->field("a.id,b.object,b.discount")->leftJoin("app_activity b", "b.id=a.activity_id")->where("b.start_time", "<=", time())->where("b.end_time", ">=", time())->where("a.pid", $pid)->find();
					$auth = \think\Db::name("host")->alias("a")->field("a.id,b.config_option2")->leftJoin("products b", "b.id=a.productid")->where("a.id", $hostid)->find();
					$cart_products["discount_pricing"] = $pricing;
					$cart_products["discount_amount"] = 0.0;
					if (!empty($auth) && $auth["config_option2"] == "professional") {
						if (!empty($activity)) {
							$cart_products["discount_pricing"] = in_array($activity["object"], [0, 1]) ? round($cart_products["discount_pricing"] * (100 - $activity["discount"]) / 100, 2) : $cart_products["discount_pricing"];
						}
						$cart_products["discount_pricing"] = round($cart_products["discount_pricing"] * (100 - $product["professional_discount"]) / 100, 2);
						$cart_products["discount_amount"] = bcsub($pricing, $cart_products["discount_pricing"], 2);
					} else {
						if (!empty($activity)) {
							$cart_products["discount_pricing"] = in_array($activity["object"], [0, 2]) ? round($cart_products["discount_pricing"] * (100 - $activity["discount"]) / 100, 2) : $cart_products["discount_pricing"];
							$cart_products["discount_amount"] = bcsub($pricing, $cart_products["discount_pricing"], 2);
						}
					}
				}
				$pagedata["cart_products"][] = $cart_products;
			}
			$total = $total_pricing + $total_setup_pricing;
			$total_sale = $this->getSaleData($uid, $pos, $pagedata["cart_products"]);
			$pagedata["saleproducts"] = bcsub($total, $total_sale, 2);
			$subtotal = bcsub(round($total_sale, 2), 0, 2);
			if (isset($cart_data["promo"])) {
				$promo = $cart_data["promo"];
				$use_promo_data = [];
				foreach ($pagedata["cart_products"] as $kk => $vv) {
					$use_promo_data[] = ["productid" => $vv["productid"], "billingcycle" => $vv["billingcycle"], "pricing" => $vv["pricing"], "setup_pricing" => $vv["setup_pricing"], "qty" => $vv["qty"], "product_sale" => $vv["product_sale"], "configoptions_base_sale" => $vv["configoptions_base_sale"], "total_sale" => $vv["total_sale"], "total_sale_setupfee" => $vv["total_sale_setupfee"]];
				}
				$res = $this->checkPromoCode($promo);
				if ($res == 1) {
					$promo_price_data = $this->handlePromoPrice($use_promo_data, $promo, $currency_prefix, $currency_suffix, $pos);
					if ($promo_price_data["status"] == "success") {
						$pagedata["promo"] = ["promo" => $promo_price_data["data"]["promo"], "promo_desc" => $promo_price_data["data"]["promo_desc_str"], "promo_price" => $promo_price_data["data"]["promo_price"]];
						$subtotal = $subtotal - $promo_price_data["data"]["promo_price"];
					}
				}
			}
			foreach ($pagedata["cart_products"] as &$m) {
				if (isset($m["type"]) && $m["type"]["type"] == 1) {
					$m["type"]["bates"] = bcmul($m["type"]["bates"], 10, 2);
				}
				$m["pricing"] = bcsub(bcmul($m["pricing"], $m["qty"]), 0, 2);
				$m["setup_pricing"] = bcsub(bcmul($m["setup_pricing"], $m["qty"]), 0, 2);
				if ($m["api_type"] != "resource") {
					unset($m["configoptions"]);
					unset($m["configoptions_base_sale"]);
					unset($m["total_sale"]);
					unset($m["total_sale_setupfee"]);
					unset($m["api_type"]);
					unset($m["groupn"]);
					unset($m["product_type"]);
					unset($m["host_show"]);
					unset($m["password_show"]);
					unset($m["is_truename"]);
					unset($m["os_name"]);
					unset($m["billingcycle_desc"]);
					unset($m["product_base_sale"]);
					unset($m["product_base_sale_setupfee"]);
					unset($m["_sale_price"]);
					unset($m["product_sale"]);
					unset($m["pricing"]);
					unset($m["product_sale_setupfee"]);
				}
			}
			$subtotal = $subtotal < 0 ? number_format(0, 2) : bcsub($subtotal, 0, 2);
			$pagedata["total_price"] = $subtotal;
			$cart_data["products"] = $new_session_products;
			$this->cart_data = $cart_data;
			$this->save();
			return $pagedata;
		} else {
			$pagedata["cart_products"] = [];
			$pagedata["total_price"] = number_format(0, 2);
			return $pagedata;
		}
	}
	private function getSaleData($uid, $pos, &$products)
	{
		$total_sale = 0;
		if (empty($products)) {
			return $total_sale;
		}
		foreach ($products as $k5 => &$v5) {
			$product_pricing = $v5["product_pricing"];
			$product_total_sale = 0;
			$product_total_sale_setupfee = 0;
			$product_total_rebate_price = 0;
			$product_total_rebate_setupfee = 0;
			$edition = getEdition();
			$pid = $v5["productid"];
			$flag = getSaleProductUser($pid, $uid);
			if ($flag["type"] == 1) {
				$bates = $flag["bates"];
				$v5["product_sale"] = bcmul($bates / 100, $v5["product_base_sale"], 20);
				$v5["product_sale_setupfee"] = bcmul($bates / 100, $v5["product_base_sale_setupfee"], 20);
				$v5["type"] = ["type" => $flag["type"], "bates" => $bates / 10];
				$pro_price = $v5["product_pricing"];
				$configoptions_base_sale_1 = $v5["configoptions_base_sale"];
				$orgin = 0;
				foreach ($configoptions_base_sale_1 as $vv) {
					if ($vv["is_rebate"] == 0) {
						$pro_price -= $vv["config_base_sale"];
						$orgin += $vv["config_base_sale"];
					}
				}
				$v5["_sale_price"] = bcadd(bcmul($bates / 100, $pro_price, 2), $orgin, 2);
			} elseif ($flag["type"] == 2) {
				$bates = $flag["bates"];
				$v5["product_sale"] = $v5["product_base_sale"] / $v5["product_pricing"] * ($v5["product_pricing"] - $bates);
				$v5["product_sale_setupfee"] = $v5["product_base_sale_setupfee"] / $v5["product_pricing"] * ($v5["product_pricing"] - $bates);
				$v5["type"] = ["type" => $flag["type"], "bates" => $bates];
				$pro_price = $v5["product_pricing"];
				$configoptions_base_sale_1 = $v5["configoptions_base_sale"];
				$orgin = 0;
				foreach ($configoptions_base_sale_1 as $vv) {
					if ($vv["is_rebate"] == 0) {
						$pro_price -= $vv["config_base_sale"];
						$orgin += $vv["config_base_sale"];
					}
					$v5["_sale_price"] = bcadd(bcsub($pro_price, $bates, 2), $orgin, 2);
				}
			} else {
				$v5["product_sale"] = $v5["product_base_sale"];
				$v5["product_sale_setupfee"] = $v5["product_base_sale_setupfee"];
			}
			$v5["product_sale"] = $v5["product_sale"] > 0 ? $v5["product_sale"] : 0;
			$v5["product_sale_setupfee"] = $v5["product_sale_setupfee"] > 0 ? $v5["product_sale_setupfee"] : 0;
			if (in_array($k5, $pos)) {
				$total_sale += $v5["product_sale"] * $v5["qty"];
			}
			$product_total_rebate_price += $v5["product_sale"] - $v5["product_sale_setupfee"];
			$product_total_rebate_setupfee += $v5["product_sale_setupfee"];
			$configoptions_base_sale = $v5["configoptions_base_sale"];
			foreach ($configoptions_base_sale as &$v6) {
				$v6["config_rebate_price"] = 0;
				$v6["config_rebate_setupfee"] = 0;
				if ($v6["is_rebate"] || !$edition) {
					if ($flag["type"] == 1) {
						$bates = $flag["bates"];
						$v6["config_sale"] = bcmul($bates / 100, $v6["config_base_sale"], 20);
						$v6["config_sale_setupfee"] = bcmul($bates / 100, $v6["config_base_sale_setupfee"], 20);
						$v6["config_rebate_price"] = bcmul($bates / 100, $v6["config_base_sale"] - $v6["config_base_sale_setupfee"], 20);
						$v6["config_rebate_setupfee"] = bcmul($bates / 100, $v6["config_base_sale_setupfee"], 20);
					} else {
						$bates = $flag["bates"];
						$v6["config_sale"] = $v6["config_base_sale"] / $v5["product_pricing"] * ($v5["product_pricing"] - $bates);
						$v6["config_sale_setupfee"] = $v6["config_base_sale_setupfee"] / $v5["product_pricing"] * ($v5["product_pricing"] - $bates);
						$v6["config_rebate_price"] = ($v6["config_base_sale"] - $v6["config_base_sale_setupfee"]) / $v5["product_pricing"] * ($v5["product_pricing"] - $bates);
						$v6["config_rebate_setupfee"] = $v6["config_base_sale_setupfee"] / $v5["product_pricing"] * ($v5["product_pricing"] - $bates);
					}
				} else {
					$v6["config_rebate_price"] = $v6["config_base_sale"] - $v6["config_base_sale_setupfee"];
					$v6["config_rebate_setupfee"] = $v6["config_base_sale_setupfee"];
				}
				$v6["config_rebate_price"] = $v6["config_rebate_price"] > 0 ? $v6["config_rebate_price"] : 0;
				$v6["config_rebate_setupfee"] = $v6["config_rebate_setupfee"] > 0 ? $v6["config_rebate_setupfee"] : 0;
				if (in_array($k5, $pos)) {
					$total_sale += bcadd($v6["config_rebate_price"], $v6["config_rebate_setupfee"], 20) * $v5["qty"];
				}
				$product_total_rebate_price += $v6["config_rebate_price"];
				$product_total_rebate_setupfee += $v6["config_rebate_setupfee"];
			}
			$v5["configoptions_base_sale"] = $configoptions_base_sale;
			$v5["total_sale"] = $product_total_rebate_price + $product_total_rebate_setupfee;
			$v5["total_sale_setupfee"] = $product_total_rebate_setupfee;
			$v5["saleproducts"] = bcmul(bcsub($v5["product_pricing"], $v5["total_sale"], 2), $v5["qty"], 2);
			$v5["pricing"] = bcsub($product_total_rebate_price, 0, 2);
			$v5["setup_pricing"] = bcsub($product_total_rebate_setupfee, 0, 2);
		}
		return $total_sale;
	}
	private function handlePromoPrice($use_promo_data, $promo, $currency_prefix, $currency_suffix, $pos = [], $flag_promo = false)
	{
		$uid = $this->uid;
		$promo_data = \think\Db::name("promo_code")->where("code", $promo)->find();
		$promo_flag = true;
		$now_pid = array_column($use_promo_data, "productid");
		if (!empty($promo_data["requires"])) {
			$need_pid = explode(",", $promo_data["requires"]);
			if (!empty($promo_data["requires_exist"]) && !empty($uid)) {
				$has_products = \think\Db::name("host")->field("productid")->where("uid", $uid)->where("domainstatus", "Active")->select()->toArray();
				$has_products = array_column($has_products, "productid") ?: [];
				$now_pid = array_merge($now_pid, $has_products);
			}
			$intersect = array_filter(array_intersect($need_pid, $now_pid));
			if (empty($intersect)) {
				$promo_error = "不满足使用该优惠码条件，需要其他产品同时购买时生效";
				$promo_flag = false;
			}
		}
		if ($promo_flag) {
			if (!empty($promo_data["appliesto"])) {
				$appliesto_pidarr = explode(",", $promo_data["appliesto"]);
				foreach ($use_promo_data as $kk => $vv) {
					if (!in_array($vv["productid"], $appliesto_pidarr)) {
						unset($use_promo_data[$kk]);
					}
				}
				if (empty($use_promo_data)) {
					$promo_error = "当前购物车产品没有可使用优惠码的产品";
					$promo_flag = false;
				}
			}
		}
		if ($promo_flag) {
			if ($promo_data["is_discount"] == 0) {
				foreach ($use_promo_data as $kk => $vv) {
					$flag = getSaleProductUser($vv["productid"], $uid);
					if ($flag) {
						unset($use_promo_data[$kk]);
					}
				}
				if (empty($use_promo_data)) {
					$promo_error = "当前购物车产品优惠码不满足使用条件(不能与折扣同时使用)";
					$promo_flag = false;
				}
			}
		}
		if ($promo_flag) {
			if (!empty($promo_data["cycles"])) {
				$promo_allow_cycle = explode(",", $promo_data["cycles"]);
				foreach ($use_promo_data as $kk => $vv) {
					if (!in_array($vv["billingcycle"], $promo_allow_cycle)) {
						unset($use_promo_data[$kk]);
					}
				}
				if (empty($use_promo_data)) {
					$promo_error = "当前购物车产品周期不满足优惠码使用条件";
					$promo_flag = false;
				}
			}
		}
		if ($promo_flag) {
			if ($promo_data["one_time"]) {
				$promo_produts_new = [];
				$promo_produts_new[0] = current($use_promo_data);
				$promo_produts_new[0]["qty"] = 1;
				$use_promo_data = $promo_produts_new;
			}
		}
		if ($promo_flag && !empty($use_promo_data)) {
			$promotype = $promo_data["type"];
			$promovalue = $promo_data["value"];
			$promorecurring = $promo_data["recurring"];
			if ($promorecurring) {
				$promo_curring_str = "终身";
			} else {
				$promo_curring_str = "一次性";
			}
			$pagedata["promo"] = $promo;
			if ($promotype == "percent") {
				$discount_pricing = 0;
				if ($promovalue > 100) {
					$promovalue = 100.0;
				}
				$promovalue = $promovalue . "%";
				$promovalue1 = round($promovalue / 10, 1) . "折";
				foreach ($use_promo_data as $kk => $vv) {
					if (in_array($kk, $pos)) {
						$discount_pricing = bcadd($discount_pricing, bcmul($vv["product_sale"], 1 - $promovalue / 100, 2) * $vv["qty"], 2);
						foreach ($vv["configoptions_base_sale"] as $kkk => $vvv) {
							if ($vvv["is_discount"] == 1) {
								$discount_pricing = bcadd($discount_pricing, bcmul($vvv["config_sale"], 1 - $promovalue / 100, 2) * $vv["qty"], 2);
							}
						}
					}
				}
				$promodescription = $promovalue1 . $promo_curring_str;
				$pagedata["promo_desc"] = "百分比 " . $promodescription;
				$pagedata["promo_desc_str"] = $promo . " 折扣: " . $promovalue1 . " " . $promo_curring_str;
				$pagedata["promo_price"] = $discount_pricing;
				$pagedata["promo_price_str"] = "-" . $currency_prefix . sprintf("%.2f", $discount_pricing) . $currency_suffix;
			} elseif ($promotype == "fixed") {
				$discount_pricing = 0;
				foreach ($use_promo_data as $kk => $vv) {
					$now_product_pricing = $vv["total_sale"];
					if (in_array($kk, $pos)) {
						$discount_pricing += ($now_product_pricing < $promovalue ? $now_product_pricing : $promovalue) * $vv["qty"];
					}
				}
				$promodescription = $currency_prefix . sprintf("%.2f", $promovalue) . $currency_suffix . $promo_curring_str;
				$pagedata["promo_desc"] = $promodescription;
				$pagedata["promo_desc_str"] = $promo . " " . $promovalue . " 减免 " . $promo_curring_str;
				$pagedata["promo_price"] = $discount_pricing;
				$pagedata["promo_price_str"] = "-" . $currency_prefix . sprintf("%.2f", $discount_pricing) . $currency_suffix;
			} elseif ($promotype == "override") {
				$discount_pricing = 0;
				foreach ($use_promo_data as $kk => $vv) {
					$now_product_pricing = $vv["total_sale"];
					if (in_array($kk, $pos)) {
						if ($now_product_pricing < $promovalue) {
							$discount_pricing += $now_product_pricing * $vv["qty"];
						} else {
							$discount_pricing += ($now_product_pricing - $promovalue) * $vv["qty"];
						}
					}
				}
				$promodescription = $currency_prefix . sprintf("%.2f", $promovalue) . $currency_suffix . $promo_curring_str;
				$pagedata["promo_desc"] = $promodescription;
				$pagedata["promo_desc_str"] = $promo . " " . $promovalue . " 覆盖价格 " . $promo_curring_str;
				$pagedata["promo_price"] = $discount_pricing;
				$pagedata["promo_price_str"] = "-" . $currency_prefix . sprintf("%.2f", $discount_pricing) . $currency_suffix;
			} elseif ($promotype == "free") {
				$discount_pricing = 0;
				foreach ($use_promo_data as $kk => $vv) {
					if (in_array($kk, $pos)) {
						$discount_pricing += $vv["total_sale_setupfee"] * $vv["qty"];
					}
				}
				$promodescription = "免设定费 " . $promo_curring_str;
				$pagedata["promo_desc"] = $promodescription;
				$pagedata["promo_desc_str"] = $promo . " 免费 " . $promo_curring_str;
				$pagedata["promo_price"] = $discount_pricing;
				$pagedata["promo_price_str"] = "-" . $currency_prefix . sprintf("%.2f", $discount_pricing) . $currency_suffix;
			}
		}
		if ($promo_flag) {
			return ["status" => "success", "data" => $pagedata];
		} else {
			return ["status" => "error", "msg" => $promo_error];
		}
	}
	private function getProductPricing($relid, $field = "*", $currency)
	{
		if ($field) {
			$pricing_data = \think\Db::name("pricing")->field("id," . $field)->where("type", "product")->where("currency", $currency)->where("relid", $relid)->find();
		} else {
			$pricing_data = \think\Db::name("pricing")->field("id")->where("type", "product")->where("currency", $currency)->where("relid", $relid)->find();
		}
		return $pricing_data;
	}
	private function getConfigoptionsPricing($relid, $field = "*", $currency)
	{
		if ($field) {
			$pricing_data = \think\Db::name("pricing")->field("id," . $field)->where("type", "configoptions")->where("currency", $currency)->where("relid", $relid)->find();
		} else {
			$pricing_data = \think\Db::name("pricing")->field("id")->where("type", "configoptions")->where("currency", $currency)->where("relid", $relid)->find();
		}
		return $pricing_data;
	}
	private function getProductDetails($pid)
	{
		$data = \think\Db::name("product_groups")->alias("g")->field("g.name as groupname,p.id,p.type,p.gid,p.name as productname,p.pay_type,p.pay_method,p.server_group")->leftJoin("product p", "p.gid=g.id")->where("g.hidden", 0)->where("p.hidden", 0)->where("p.id", $pid)->find();
		return $data ?: [];
	}
	private function getServerData($pid, $serverid)
	{
		$data = \think\Db::name("servers")->alias("s")->field("s.id,s.gid,s.name")->leftJoin("product p", "p.server_group=s.gid")->where("s.disabled", 0)->where("p.id", $pid)->where("s.id", $serverid)->find();
		return $data ?: [];
	}
	public function checkHostName($host, $pid)
	{
		if (empty($pid)) {
			return ["status" => 400, "msg" => "产品id不能为空"];
		}
		$product = \think\Db::name("products")->field("host")->where("id", $pid)->find();
		$host_basic = json_decode($product["host"], true);
		if ($host_basic["show"]) {
			$host_rule = $host_basic["rule"];
			if (strlen($host) < $host_rule["len_num"]) {
				return ["status" => 400, "msg" => "主机名长度至少{$host_rule["len_num"]}位"];
			}
		}
		return ["status" => 200];
	}
	public function checkHostPassword1($password, $pid)
	{
		if (empty($pid)) {
			return ["status" => 400, "msg" => "产品id不能为空"];
		}
		$product = \think\Db::name("products")->field("password")->where("id", $pid)->find();
		$password_basic = json_decode($product["password"], true);
		if ($password_basic["show"]) {
			$password_rule = $password_basic["rule"];
			if (strlen($password) < 6 || strlen($password) > 20) {
				return ["status" => 400, "msg" => "密码长度6-20位"];
			}
			$reg = "";
			$msg = "";
			if ($password_rule["upper"] == 1) {
				$reg .= "(?=.*[A-Z].*)";
				$msg .= "大写字母，";
			}
			if ($password_rule["lower"] == 1) {
				$reg .= "(?=.*[a-z].*)";
				$msg .= "小写字母，";
			}
			if ($password_rule["num"] == 1) {
				$reg .= "(?=.*[0-9].*)";
				$msg .= "数字，";
			}
			if ($password_rule["special"] == 1) {
				$reg .= "(?=.*[|!@#\$%&*\\[\\]\\(\\)\\{\\}\\/=?,;.:\\-_+~^\\\\].*)";
				$msg .= "特殊字符";
			}
			if (!empty($password) && !preg_match("/^" . $reg . ".{1,}\$/", $password)) {
				return ["status" => 400, "msg" => "密码由{$msg}组成"];
			}
		}
		return ["status" => 200];
	}
	public function checkHostPassword($password, $pid)
	{
		if (empty($pid)) {
			return ["status" => 400, "msg" => "产品id不能为空"];
		}
		$product = \think\Db::name("products")->field("password")->where("id", $pid)->find();
		$password_basic = json_decode($product["password"], true);
		$msg = "密码由";
		$flagNum = $flagCap = $flagLow = $flagSpec = false;
		if ($password_basic["show"]) {
			$password_rule = $password_basic["rule"];
			if (strlen($password) < self::MIN_LEN) {
				return ["status" => 400, "msg" => "密码最短" . self::MIN_LEN];
			}
			if (strlen($password) > self::MAX_LEN) {
				return ["status" => 400, "msg" => "密码最长" . self::MIN_LEN];
			}
			if ($password_rule["num"] == 1) {
				$msg .= "数字";
				if (!preg_match("/.*[0-9]{1,}.*/", $password)) {
					$flagNum = true;
				}
			} else {
				if (!preg_match("/.*[0-9]{1,}.*/", $password)) {
					$flagNum = true;
				}
			}
			if ($password_rule["lower"] == 1) {
				$msg .= "小写字母";
				if (!preg_match("/.*[a-z]{1,}.*/", $password)) {
					$flagLow = true;
				}
			} else {
				if (!preg_match("/.*[a-z]{1,}.*/", $password)) {
					$flagLow = true;
				}
			}
			if ($password_rule["upper"] == 1) {
				$msg .= "大写字母";
				if (!preg_match("/.*[A-Z]{1,}.*/", $password)) {
					$flagCap = true;
				}
			} else {
				if (!preg_match("/.*[A-Z]{1,}.*/", $password)) {
					$flagCap = true;
				}
			}
			if ($password_rule["special"] == 1) {
				$msg .= "特殊字符";
				if (!preg_match("/[`~!@#\$%^&*()_\\-+=<>?:\"{}|,.\\/;'\\[\\]·~！@#￥%……&*（）——\\-+={}|《》？：“”【】、；‘'，。、]/", $password)) {
					$flagSpec = true;
				}
			} else {
				if (!preg_match("/[`~!@#\$%^&*()_\\-+=<>?:\"{}|,.\\/;'\\[\\]·~！@#￥%……&*（）——\\-+={}|《》？：“”【】、；‘'，。、]/", $password)) {
					$flagSpec = true;
				}
			}
			$msg .= "组成";
			if ($flagSpec && $flagCap && $flagLow && $flagNum) {
				return ["status" => 400, "msg" => "密码由{$msg}组成"];
			}
		}
		return ["status" => 200];
	}
	public function configfilter($pid, $configoption)
	{
		$allConfigArr = \think\Db::name("product_config_options")->alias("options")->field("options.*")->leftJoin("product_config_links links", "links.gid=options.gid")->where("links.pid", $pid)->select()->toArray();
		$config_keys = array_keys($configoption);
		$allConfigArrCateId = [];
		foreach ($allConfigArr as $k => $v) {
			$allConfigArrCateId[$v["id"]] = $v;
		}
		if (!empty($allConfigArr)) {
			foreach ($allConfigArr as $k => $v) {
				if ($v["option_type"] == 20 && !in_array($v["id"], $config_keys)) {
					continue;
				}
				$option_type = $v["option_type"];
				$config_id = $v["id"];
				if (judgeQuantity($option_type)) {
					$qty_minimum = $v["qty_minimum"];
					$qty_maximum = $v["qty_maximum"];
					if (!empty($configoption[$config_id])) {
						$qty = $configoption[$config_id] < $qty_minimum ? $qty_minimum : $configoption[$config_id];
						$qty = $qty_maximum < $qty ? $qty_maximum : $qty;
						$addCartArr["configoptions"][$config_id] = $qty;
					} else {
						$addCartArr["configoptions"][$config_id] = $qty_minimum;
					}
				} else {
					if ($option_type == 3) {
						if (!empty($configoption[$config_id])) {
							$exists_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->where("hidden", 0)->where("id", $configoption[$config_id])->order("id", "asc")->find();
							if (!empty($exists_data)) {
								$addCartArr["configoptions"][$config_id] = $configoption[$config_id];
							} else {
								$sub_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->where("hidden", 0)->order("sort_order asc")->order("id", "asc")->find();
								if (!empty($sub_data)) {
									$addCartArr["configoptions"][$config_id] = $sub_data["id"];
								}
							}
						} else {
							$addCartArr["configoptions"][$config_id] = "";
						}
					} else {
						if (!empty($configoption[$config_id])) {
							$exists_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->where("hidden", 0)->where("id", $configoption[$config_id])->order("id", "asc")->find();
							if (!empty($exists_data)) {
								$addCartArr["configoptions"][$config_id] = $configoption[$config_id];
							} else {
								$sub_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->where("hidden", 0)->order("sort_order asc")->order("id", "asc")->find();
								if (!empty($sub_data)) {
									$addCartArr["configoptions"][$config_id] = $sub_data["id"];
								}
							}
						} else {
							$sub_data = \think\Db::name("product_config_options_sub")->where("config_id", $config_id)->where("hidden", 0)->order("sort_order asc")->order("id", "asc")->find();
							if (!empty($sub_data)) {
								$addCartArr["configoptions"][$config_id] = $sub_data["id"];
							}
						}
					}
				}
			}
		} else {
			$addCartArr["configoptions"] = [];
		}
		return $addCartArr["configoptions"] ?: [];
	}
}