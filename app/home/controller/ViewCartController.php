<?php

namespace app\home\controller;

define("VIEW_TEMPLATE_DIRECTORY", "cart");
define("VIEW_TEMPLATE_WEBSITE", true);
define("VIEW_TEMPLATE_RETURN_ARRAY", true);
define("VIEW_TEMPLATE_SUFFIX", "tpl");
class ViewCartController extends ViewBaseController
{
	public $addParam_data = [];
	public function cart(\think\Request $request)
	{
		if (trim($request->alias)) {
			$model = \think\Db::name("product_groups")->field("id")->where("alias", trim($request->alias))->whereOr("id", trim($request->alias))->find();
			if (!$model) {
				abort(404, "页面异常");
			}
			$request->gid = $model ? $model["id"] : $request->gid;
		}
		$this->setParam($request);
		$param = $request->param();
		$action = $param["action"];
		$cart = controller("cart");
		$products = "";
		$_data = [];
		$this->addParam_data = $_data = $this->addFun($request, $cart, $param);
		if ($action == "configureproduct") {
			return $this->view("configureproduct", $this->confProductFun($request, $cart, $param));
		} elseif ($action == "ordersummary") {
			echo $this->view("ordersummary", $this->orderSummaryFun($request, $cart, $param), ["autoinclude" => false]);
			exit;
		} elseif ($action == "viewcart") {
			return $this->view("viewcart", $this->viewCartFun($request, $cart, $param));
		} elseif ($action == "complete") {
			$getShopDataPage = $cart->getShopDataPage($request);
			$data["ShopData"] = $getShopDataPage["data"];
			$data["Title"] = get_title_lang("title_cart_complete");
			$this->cartGroupTpl();
			return $this->view("complete", $data);
		} elseif ($param["keywords"]) {
			$globalSearch = $cart->globalSearch();
			$products = $globalSearch["products"];
		}
		if ($param["pid"] && ($param["carttheme"] == "default" || cookie("cart_theme") == "default")) {
			$data = $this->confProductFun($request, $cart, $param);
			if (isset($_data["configoption"])) {
				foreach ($_data["configoption"] as $key => $val) {
					$data["getUrlConfig"]["config_options"][$key] = $val;
				}
			}
			$data["addParam"] = $_data;
			return $this->view("configureproduct", $data);
		}
		if ($param["pid"] && ($param["carttheme"] == "area" || cookie("cart_theme") == "area")) {
			$data = $this->productFun($request, $cart, $param, $products);
			if (isset($_data["configoption"])) {
				foreach ($_data["configoption"] as $key => $val) {
					$data["getUrlConfig"]["config_options"][$key] = $val;
				}
			}
			$data["addParam"] = $_data;
			return $this->view("product", $data);
		}
		if ($param["pid"]) {
			$gid_arr = \think\Db::name("products")->where("id", $param["pid"])->column("gid");
			if ($gid_arr) {
				$group_config = \think\Db::name("product_groups")->where("id", $gid_arr[0])->find();
				$default = configuration("order_page_style");
				if ($group_config["tpl_type"] == "custom") {
					$tplName = $group_config["order_frm_tpl"] ?: "default";
				} else {
					$tplName = $default ?: "default";
				}
				if (empty($group_config["order_frm_tpl"]) && empty($group_config["tpl_type"])) {
					$tplName = "default";
				}
			}
			if (isset($tplName) && $tplName == "default") {
				$data = $this->confProductFun($request, $cart, $param);
				if (isset($_data["configoption"])) {
					foreach ($_data["configoption"] as $key => $val) {
						$data["getUrlConfig"]["config_options"][$key] = $val;
					}
				}
				$data["addParam"] = $_data;
				return $this->view("configureproduct", $data);
			}
		}
		$data = $this->productFun($request, $cart, $param, $products);
		if (isset($_data["configoption"])) {
			foreach ($_data["configoption"] as $key => $val) {
				$data["CartConfig"]["config_options"][$key] = $val;
			}
		}
		$data["addParam"] = $_data;
		return $this->view("product", $data);
	}
	public function addFun($request, $cart, $param, $data = [])
	{
		if ($param["billingcycle"]) {
			$data["addParam"]["billingcycle"] = $param["billingcycle"];
		}
		if ($param["promocode"]) {
			$data["addParam"]["promocode"] = $param["promocode"];
		}
		if ($param["aff"]) {
			$data["addParam"]["aff"] = $param["aff"];
			$days = configuration("affiliate_cookie");
			setcookie("AffiliateID", $param["aff"], time() + 86400 * $days, "/");
		}
		if ($param["sale"]) {
			cookie("sale_param", $param["sale"]);
			$data["addParam"]["sale"] = $param["sale"];
		}
		if ($param["configoption"]) {
			$data["addParam"]["configoption"] = $param["configoption"];
		}
		return $data["addParam"];
	}
	protected function productFun($request, $cart, $param, $products)
	{
		$cartindex = $cart->index();
		$first_groups = $cartindex["first_groups"];
		$product_groups = $this->ViewModel->secondGroups("", "");
		$gidgroup = array_column($product_groups, "id");
		if (!in_array($param["gid"], $gidgroup) && !empty($param["gid"])) {
			header("location:{$this->ViewModel->domain}/cart" . $site . $url_param);
			exit;
		}
		$product_groups_checked = [];
		$_second_k = $_first_k = 0;
		if ($products) {
			$request->pid = $param["pid"] = $param["pid"] ?: $products[0]["id"] ?? null;
			$_second_k = $products[0]["gid"] ?? 0;
			$_second_arr = \think\Db::name("product_groups")->where("id", $_second_k)->find();
			$_first_k = $_second_arr["gid"] ?? 0;
		}
		if ($_second_k != 0) {
			$request->gid = $param["gid"] = $_second_k;
		}
		if ($_first_k != 0) {
			$request->fid = $param["fid"] = $_first_k;
		}
		foreach ($first_groups as $first_k => $first) {
			if ($param["fid"] == $first["id"]) {
				$_first_k = $first_k;
			}
			foreach ($product_groups as $second) {
				if ($first["id"] == $second["gid"]) {
					$first_groups[$first_k]["second"][] = $second;
				}
				if ($second["id"] == $param["gid"]) {
					$product_groups_checked["name"] = $second["name"];
					$product_groups_checked["headline"] = $second["headline"];
					$product_groups_checked["tagline"] = $second["tagline"];
					$order_frm_tpl = $second["order_frm_tpl"];
					$tpl_type = $second["tpl_type"];
					$this->cartGroupTpl($order_frm_tpl, $tpl_type);
				}
			}
		}
		$gid_is_true = \think\Db::name("product_groups")->where("id", $param["gid"])->whereNotIn("gid", [0])->find();
		if (!$gid_is_true) {
			$param["gid"] = null;
		}
		if (!$param["gid"]) {
			$order_frm_tpl = $first_groups[$_first_k]["second"][0]["order_frm_tpl"];
			$tpl_type = $first_groups[$_first_k]["second"][0]["tpl_type"];
			$this->cartGroupTpl($order_frm_tpl, $tpl_type);
			$product_groups_checked["name"] = $first_groups[$_first_k]["second"][0]["name"];
			$product_groups_checked["headline"] = $first_groups[$_first_k]["second"][0]["headline"];
			$product_groups_checked["tagline"] = $first_groups[$_first_k]["second"][0]["tagline"];
		}
		if ($request->isPost()) {
			$url_param = $this->setCustomParam($param);
			if (isset($param["i"]) && $param["i"]) {
				$editToShop = $cart->editToShop($request);
				if ($editToShop["status"] == 200) {
					header("location:{$this->ViewModel->domain}/cart?action=viewcart" . $url_param);
					exit;
				} else {
					$data["ErrorMsg"] = $editToShop["msg"];
				}
			} else {
				if ($param["promocode"]) {
					$request->promo = $param["promocode"];
					$this->addPromoToShop($request);
				}
				$addToShop = $cart->addToShop($request);
				if ($addToShop["status"] == 200) {
					header("location:{$this->ViewModel->domain}/cart?action=viewcart" . $url_param);
					exit;
				} else {
					$data["ErrorMsg"] = $addToShop["msg"];
				}
			}
		}
		if (empty($param["pid"])) {
			$param["pid"] = $cartindex["products"][0]["id"];
			$request->pid = $param["pid"];
		}
		if ($param["pid"]) {
			if (isset($param["i"]) && $param["i"]) {
				$setConfig = $cart->editToShopPage($request);
				$CartConfig["config_options"] = $setConfig["config_options"];
				$CartConfig["custom_fields_value"] = $setConfig["custom_fields_value"];
				$CartConfig["billingcyle"] = $setConfig["billingcyle"];
				$CartConfig["host"] = $setConfig["host"];
				$CartConfig["password"] = $setConfig["password"];
				$CartConfig["qty"] = $setConfig["qty"];
			} else {
				$setConfig = $cart->setConfig($request);
			}
			$CartConfig["product"] = $setConfig["product"];
			$CartConfig["option"] = $setConfig["option"];
			$CartConfig["custom_fields"] = $setConfig["custom_fields"];
			$CartConfig["hosts"] = $setConfig["hosts"];
			foreach ($setConfig["links"] as $k => $v) {
				foreach ($v["sub_id"] as $sub_k => $sub_v) {
					$sub_id[] = $sub_k;
					$sub_qty[] = $sub_v;
				}
				foreach ($v["result"] as $result_v) {
					foreach ($result_v["sub_id"] as $rsub_k => $rsub_v) {
						$rsub_id = $rsub_k;
						$rsub_qty = $rsub_v;
					}
					$condition["config_id"] = $result_v["config_id"];
					$condition["relation"] = $result_v["relation"];
					$condition["sub_id"] = $rsub_id;
					$condition["sub_qty_minimum"] = $rsub_qty["qty_minimum"];
					$condition["sub_qty_maximum"] = $rsub_qty["qty_maximum"];
				}
				$links[$k]["config_id"] = $v["config_id"];
				$links[$k]["relation"] = $v["relation"];
				$links[$k]["sub_id"] = $sub_id;
				$links[$k]["sub_qty_minimum"] = $sub_qty["qty_minimum"];
				$links[$k]["sub_qty_maximum"] = $sub_qty["qty_maximum"];
				$links[$k]["condition"] = $condition;
			}
			$CartConfig["links"] = $setConfig["links"];
			$CartConfig["dafault_currencyid"] = $setConfig["dafault_currencyid"];
			foreach ($setConfig["currency"] as $v) {
				if ($v["id"] == $setConfig["dafault_currencyid"]) {
					unset($v["default"]);
					$CartConfig["currency"] = $v;
				}
			}
			$data["CartConfig"] = $CartConfig;
			$data["order_frm_tpl"] = empty($param["carttheme"]) ? $order_frm_tpl : $param["carttheme"];
			$data["tpl_type"] = empty($param["carttheme"]) ? $tpl_type : "custom";
		}
		$data["Cart"]["product_groups"] = $first_groups;
		$data["Cart"]["products"] = $param["keywords"] ? $products : $cartindex["products"];
		$data["Cart"]["product_groups_checked"] = $product_groups_checked;
		$data["Cart"]["currency"] = $cartindex["default_currency"];
		$data["Title"] = get_title_lang("title_productFun");
		return $this->preferentialPeriod($data);
	}
	protected function viewCartFun($request, $cart, $param)
	{
		$Login = controller("Login");
		$url_param = $this->setCustomParam($param);
		if ($request->isPost() && $param["statuscart"] == "remove") {
			$removeProduct = $cart->removeProduct($request);
			header("location:{$this->ViewModel->domain}/cart?action=viewcart" . $url_param);
			exit;
		} elseif ($request->isPost() && $param["statuscart"] == "change") {
			$removeProduct = $cart->modifyProductQty($request);
			header("location:{$this->ViewModel->domain}/cart?action=viewcart" . $url_param);
			exit;
		} elseif ($request->isPost() && $param["statuscart"] == "removepromo") {
			$removePromoToShop = $cart->removePromoToShop($request);
			if ($removePromoToShop["status"] == 200) {
				$data["SuccessMsg"] = $removePromoToShop["msg"];
			} else {
				$data["ErrorMsg"] = $removePromoToShop["msg"];
			}
			if ($param["ajax"]) {
				echo json_encode($data);
				exit;
			}
		} elseif ($request->isPost() && $param["statuscart"] == "promo") {
			$addPromoToShop = $cart->addPromoToShop($request);
			if ($addPromoToShop["status"] == 200) {
				$data["SuccessMsg"] = $addPromoToShop["msg"];
			} else {
				$data["ErrorMsg"] = $addPromoToShop["msg"];
			}
			if ($param["ajax"]) {
				echo json_encode($data);
				exit;
			}
		} elseif ($request->isPost() && $param["statuscart"] == "checkout") {
			if (!$param["terms"]) {
				$data["ErrorMsg"] = "请勾选服务条款";
			} else {
				if (!request()->uid) {
					if ($param["register_or_login"] == "login") {
						if ($request->isPost()) {
							if ($param["phone_code"]) {
								$result = $Login->phonePassLogin();
							} else {
								$result = $Login->emailLogin();
							}
							if ($result["status"] == 200) {
								userSetCookie($result["jwt"]);
								$request->uid = $this->ViewModel->userinfo($result["jwt"])["uid"];
								$cart->getShopDataPage($request);
							} else {
								$data["ErrorMsg"] = $result["msg"];
							}
						}
					}
					if ($param["register_or_login"] == "register") {
						$Register = controller("Register");
						if ($request->isPost()) {
							if ($param["phone_code"]) {
								$result = $Register->registerPhone();
							} else {
								$result = $Register->registerEmail();
							}
							if ($result["status"] == 200) {
								userSetCookie($result["jwt"]);
								$request->uid = $this->ViewModel->userinfo($result["jwt"])["uid"];
								$cart->getShopDataPage($request);
							} else {
								$data["ErrorMsg"] = $result["msg"];
							}
						}
					}
				}
				if ($result["status"] == 200 || request()->uid) {
					if (request()->uid) {
						$user = controller("user");
						$request->uid = request()->uid;
						$user->setSaler();
					}
					$settle = $cart->settle($request);
					if ($settle["status"] == 200) {
						if ($param["paymt"] === "credit") {
							$request->invoiceid = $settle["data"]["invoiceid"];
							$request->use_credit = 1;
							$request->use_credit_limit = 0;
							$Pay = controller("Pay");
							$apply_credit = $Pay->applyCredit($request);
							if ($apply_credit["status"] == 1001) {
							} else {
								$data["ErrorMsg"] = $apply_credit["msg"];
							}
						} elseif ($param["paymt"] === "credit_limit") {
							$request->invoiceid = $settle["data"]["invoiceid"];
							$request->use_credit = 0;
							$request->use_credit_limit = 1;
							$Pay = controller("Pay");
							$apply_limit_credit = $Pay->applyCreditLimit($request);
							if ($apply_limit_credit["status"] == 1001) {
							} else {
								$data["ErrorMsg"] = $apply_limit_credit["msg"];
							}
						}
						header("location:{$this->ViewModel->domain}/viewbilling?id=" . $settle["data"]["invoiceid"] . "&wakeup=1");
						exit;
					} elseif ($settle["status"] == 1001) {
						header("location:{$this->ViewModel->domain}/servicedetail?id=" . $settle["data"]["hostid"][0]);
						exit;
					} elseif ($settle["status"] == 410) {
						$data["ErrorMsg"] = $settle["msg"] . "<a href=\"" . $this->ViewModel->domain . "/verified\">去认证</a>";
						if ($settle["msg_phone"]) {
							$data["ErrorMsg"] .= "<br>" . $settle["msg_phone"] . "<a href=\"" . $this->ViewModel->domain . "/security\">去绑定</a>";
						}
					} elseif ($settle["status"] == 415) {
						$data["ErrorMsg"] = $settle["msg"] . "<a href=\"" . $this->ViewModel->domain . "/security\">去绑定</a>";
					} else {
						$data["ErrorMsg"] = $settle["msg"];
					}
				}
			}
		}
		$LoginRegisterIndex = $Login->LoginRegisterIndex();
		$data["saler"] = $LoginRegisterIndex["data"]["saler"];
		$data["setsaler"] = $LoginRegisterIndex["data"]["setsaler"];
		$data["sale_setting"] = configuration("sale_setting") ? configuration("sale_setting") : 0;
		if (!request()->uid) {
			$mobileLoginVerifyPage = $Login->mobileLoginVerifyPage();
			$Oauth = controller("Oauth");
			$OauthListing = $Oauth->listing();
			$dataLogin["allow_login_email"] = $LoginRegisterIndex["data"]["allow_login_email"];
			$dataLogin["allow_login_phone"] = $LoginRegisterIndex["data"]["allow_login_phone"];
			$dataLogin["allow_id"] = $LoginRegisterIndex["data"]["allow_id"];
			$dataLogin["is_captcha"] = intval(configuration("is_captcha"));
			if (!empty($dataLogin["is_captcha"])) {
				$dataLogin["allow_login_phone_captcha"] = intval(configuration("allow_login_phone_captcha"));
				$dataLogin["allow_login_email_captcha"] = intval(configuration("allow_login_email_captcha"));
				$dataLogin["allow_login_id_captcha"] = intval(configuration("allow_login_id_captcha"));
				$dataLogin["allow_login_code_captcha"] = intval(configuration("allow_login_code_captcha"));
			}
			$dataLogin["allow_second_verify"] = $LoginRegisterIndex["data"]["allow_second_verify"];
			$dataLogin["second_verify_action_home"] = $LoginRegisterIndex["data"]["second_verify_action_home"];
			$data["Oauth"] = $OauthListing["data"];
			$data["Login"] = $dataLogin;
			$dataRegister["allow_register_email"] = $LoginRegisterIndex["data"]["allow_register_email"];
			$dataRegister["allow_register_phone"] = $LoginRegisterIndex["data"]["allow_register_phone"];
			$dataRegister["allow_email_register_code"] = $LoginRegisterIndex["data"]["allow_email_register_code"];
			$dataRegister["is_captcha"] = intval(configuration("is_captcha"));
			if (!empty($dataRegister["is_captcha"])) {
				$dataRegister["allow_register_phone_captcha"] = intval(configuration("allow_register_phone_captcha"));
				$dataRegister["allow_register_email_captcha"] = intval(configuration("allow_register_email_captcha"));
			}
			$dataRegister["fields"] = $LoginRegisterIndex["data"]["fields"];
			$dataRegister["login_register_custom_require"] = $LoginRegisterIndex["data"]["login_register_custom_require"];
			$dataRegister["login_register_custom_require_list"] = config("login_register_custom_require");
			$data["SmsCountry"] = $mobileLoginVerifyPage["data"];
			$data["Register"] = $dataRegister;
		}
		$getShopDataPage = $cart->getShopDataPage($request);
		$data["ShopData"] = $getShopDataPage["data"];
		$data["ShopData"] = $cart->setConfToShopData($getShopDataPage["data"], $param);
		$data["Title"] = get_title_lang("title_productFun");
		$data["promocode"] = $param["promocode"] ?: "";
		$data["sale"] = cookie("sale_param");
		$af_model = \think\Db::name("affiliates")->where("url_identy", $param["aff"])->find();
		$data["aff"] = $af_model ? $af_model->uid : "";
		if (request()->uid) {
			$client = \think\Db::name("clients")->field("credit,credit_limit,is_open_credit_limit,currency")->where("id", request()->uid)->find();
			$client["is_open_credit_limit"] = configuration("credit_limit") == 1 ? $client["is_open_credit_limit"] : 0;
			$client["amount_to_be_settled"] = \think\Db::name("invoices")->where("status", "Paid")->where("use_credit_limit", 1)->where("invoice_id", 0)->where("is_delete", 0)->where("uid", request()->uid)->sum("total");
			$unpaid = \think\Db::name("invoices")->where("type", "credit_limit")->where("status", "Unpaid")->where("is_delete", 0)->where("uid", request()->uid)->sum("total");
			$client["credit_limit_used"] = round($client["amount_to_be_settled"] + $unpaid, 2);
			$client["credit_limit_balance"] = round($client["credit_limit"] - $client["credit_limit_used"] > 0 ? $client["credit_limit"] - $client["credit_limit_used"] : 0, 2);
			$data["client"] = $client;
		}
		$data["is_open_shd_credit_limit"] = configuration("shd_credit_limit");
		$this->cartGroupTpl();
		return $data;
	}
	/**
	 * 添加优惠券到购物车
	 * @param Request $request
	 * @return array|\think\Response
	 */
	public function addPromoToShop(\think\Request $request)
	{
		$promo = $request->param("promo");
		if (!is_string($promo)) {
			return jsons(["status" => 406, "msg" => "优惠码格式错误"]);
		}
		$currency = $request->param("currency");
		$uid = $request->uid;
		$shop = new \app\common\logic\Shop($uid);
		$res = $shop->addPromo($promo);
		if ($res["status"] != "success") {
			return jsons(["status" => 406, "msg" => $res["msg"]]);
		}
		$param = $request->param();
		$pos = [];
		if (isset($param["pos"]) && is_array($param["pos"]) && !empty($param["pos"])) {
			$pos = $param["pos"];
		}
		$pagedata = $shop->getShopPageData($currency, $pos);
		if (!empty($pagedata["promo_error_desc"])) {
			return jsons(["status" => 406, "msg" => $pagedata["promo_error_desc"]]);
		}
		$returndata = [];
		if (isset($pagedata["promo_waring_desc"])) {
			$returndata["promo_waring_desc"] = $pagedata["promo_waring_desc"];
		}
		$returndata["promo"] = $pagedata["promo"];
		$returndata["total_price"] = $pagedata["total_price"];
		$returndata["total_desc"] = $pagedata["total_desc"];
		return jsons(["status" => 200, "msg" => "优惠码应用成功", "data" => $returndata]);
	}
	protected function orderSummaryFun($request, $cart, $param)
	{
		if (!$request->isPost()) {
			exit;
		}
		$getTotal = $cart->getTotal($request);
		$data["ConfigureTotal"] = $getTotal["products"];
		$data["ConfigureTotal"]["currency"] = $getTotal["currency"];
		$data = $this->preferentialPeriod($data);
		$this->cartGroupTpl($param["order_frm_tpl"], $param["tpl_type"]);
		return $data;
	}
	public function setCustomParam($param)
	{
		$url_param = "";
		$url_param .= $param["promocode"] ? "&promocode=" . $param["promocode"] : "";
		$url_param .= $param["aff"] ? "&aff=" . $param["aff"] : "";
		$url_param .= $param["sale"] ? "&sale=" . $param["sale"] : "";
		return $url_param;
	}
	protected function confProductFun($request, $cart, $param)
	{
		if ($request->isPost()) {
			$url_param = $this->setCustomParam($param);
			$site = !empty($param["site"]) ? "&site=" . $param["site"] : "";
			$senior = new \app\common\logic\SeniorConf();
			$msg = $senior->checkConf($param["pid"], $param["configoption"]);
			$data["ErrorMsg"] = $msg;
			if (empty($msg)) {
				if (isset($param["i"])) {
					$editToShop = $cart->editToShop($request);
					if ($editToShop["status"] == 200) {
						header("location:{$this->ViewModel->domain}/cart?action=viewcart" . $site . $url_param);
						exit;
					} else {
						$data["ErrorMsg"] = $editToShop["msg"];
					}
				} else {
					if ($param["promocode"]) {
						$request->promo = $param["promocode"];
						$this->addPromoToShop($request);
					}
					$addToShop = $cart->addToShop($request);
					if ($addToShop["status"] == 200) {
						header("location:{$this->ViewModel->domain}/cart?action=viewcart" . $site . $url_param);
						exit;
					} else {
						$data["ErrorMsg"] = $addToShop["msg"];
					}
				}
			}
		}
		if (isset($param["i"]) && $param["i"] >= 0) {
			$setConfig = $cart->editToShopPage($request);
			$CartConfig["config_options"] = $setConfig["config_options"];
			$CartConfig["custom_fields_value"] = $setConfig["custom_fields_value"];
			$CartConfig["billingcyle"] = $setConfig["billingcyle"];
			$CartConfig["host"] = $setConfig["host"];
			$CartConfig["password"] = $setConfig["password"];
			$CartConfig["qty"] = $setConfig["qty"];
		} else {
			$setConfig = $cart->setConfig($request);
		}
		if (\is_profession()) {
			$getTotalConfigoption = [];
			$range = [4, 7, 9, 11, 14, 15, 16, 17, 18, 19];
			foreach ($setConfig["option"] as $setConfigOption) {
				$configoptionKey = $setConfigOption["id"];
				$configoptionVal = "";
				if ($setConfigOption["option_type"] == 5) {
					foreach ($setConfigOption["sub"] as $setConfigOptionSub) {
						if (!empty($setConfigOptionSub["child"][0]["id"])) {
							$configoptionVal = $setConfigOptionSub["child"][0]["id"];
							break;
						}
					}
				} elseif ($setConfigOption["option_type"] == 12) {
					$configoptionVal = $setConfigOption["sub"][0]["area"][0]["id"];
				} else {
					if (in_array($setConfigOption["option_type"], $range)) {
						$configoptionVal = $setConfigOption["sub"][0]["qty_minimum"];
					} else {
						$configoptionVal = $setConfigOption["sub"][0]["id"];
					}
				}
				if ($configoptionVal >= 0) {
					$getTotalConfigoption[$configoptionKey] = $configoptionVal;
				}
			}
			$billing_cycle_unit = ["hour" => 1, "day" => 24, "monthly" => 720, "quarterly" => 2160, "semiannually" => 4320, "annually" => 8640, "biennially" => 17280, "triennially" => 25920, "fourly" => 34560, "fively" => 43200, "sixly" => 51840, "sevenly" => 60480, "eightly" => 69120, "ninely" => 77760, "tenly" => 86400];
			$billing_cycle_base = "";
			$cycle_base_pricing = 0;
			foreach ($setConfig["product"]["cycle"] as $cycleKey => $cycle) {
				if ($cycle["billingcycle"] == "ontrial" || $cycle["billingcycle"] == "free" || $cycle["billingcycle"] == "onetime") {
					continue;
				}
				$request->billingcycle = $cycle["billingcycle"];
				$request->configoption = $getTotalConfigoption;
				$request->pid = $setConfig["product"]["id"];
				$request->qty = 1;
				$getTotal2 = $cart->getTotal();
				$getTotal3[] = $getTotal2;
				$setConfig["product"]["cycle"][$cycleKey]["total"] = $getTotal2["products"]["total"];
				if (empty($billing_cycle_base)) {
					$billing_cycle_base = $cycle["billingcycle"];
					$cycle_base_pricing = $getTotal2["products"]["total"];
					if ($cycle["billingcycle"] == "hour") {
						$cycle_base_pricing = $getTotal2["products"]["total"] / $cycle["hour_cycle"];
					} elseif ($cycle["billingcycle"] == "day") {
						$cycle_base_pricing = $getTotal2["products"]["total"] / $cycle["day_cycle"];
					}
				} else {
					if (!empty($billing_cycle_base)) {
						if ($cycle["billingcycle"] == "day") {
							$cycle_base_multiple = $billing_cycle_unit["day"] * $cycle["day_cycle"] / $billing_cycle_unit[$billing_cycle_base];
						} else {
							$cycle_base_multiple = $billing_cycle_unit[$cycle["billingcycle"]] / $billing_cycle_unit[$billing_cycle_base];
						}
						$difference = $cycle_base_multiple * $cycle_base_pricing;
						$discount = sprintf("%.2f", $getTotal2["products"]["total"] / $difference) * 10;
						if ($discount < 10) {
							$setConfig["product"]["cycle"][$cycleKey]["cycle_discount"] = $discount;
						}
					}
				}
			}
		}
		$CartConfig["product"] = $setConfig["product"];
		$CartConfig["option"] = $setConfig["option"];
		$CartConfig["custom_fields"] = $setConfig["custom_fields"];
		$CartConfig["hosts"] = $setConfig["hosts"];
		foreach ($setConfig["links"] as $k => $v) {
			foreach ($v["sub_id"] as $sub_k => $sub_v) {
				$sub_id[] = $sub_k;
				$sub_qty[] = $sub_v;
			}
			foreach ($v["result"] as $result_v) {
				foreach ($result_v["sub_id"] as $rsub_k => $rsub_v) {
					$rsub_id = $rsub_k;
					$rsub_qty = $rsub_v;
				}
				$condition["config_id"] = $result_v["config_id"];
				$condition["relation"] = $result_v["relation"];
				$condition["sub_id"] = $rsub_id;
				$condition["sub_qty_minimum"] = $rsub_qty["qty_minimum"];
				$condition["sub_qty_maximum"] = $rsub_qty["qty_maximum"];
			}
			$links[$k]["config_id"] = $v["config_id"];
			$links[$k]["relation"] = $v["relation"];
			$links[$k]["sub_id"] = $sub_id;
			$links[$k]["sub_qty_minimum"] = $sub_qty["qty_minimum"];
			$links[$k]["sub_qty_maximum"] = $sub_qty["qty_maximum"];
			$links[$k]["condition"] = $condition;
		}
		$CartConfig["links"] = $setConfig["links"];
		$CartConfig["dafault_currencyid"] = $setConfig["dafault_currencyid"];
		foreach ($setConfig["currency"] as $v) {
			if ($v["id"] == $setConfig["dafault_currencyid"]) {
				unset($v["default"]);
				$CartConfig["currency"] = $v;
			}
		}
		$data["CartConfig"] = $CartConfig;
		$data["Title"] = get_title_lang("title_confProductFun");
		$this->cartGroupTpl();
		$data = $this->preferentialPeriod($data);
		$data["addParam"] = $this->addParam_data;
		if ($param["ajax"] == "true") {
			echo $this->view("configureproduct", $data, ["autoinclude" => false]);
			exit;
		}
		return $data;
	}
	public function getLinkAgeList()
	{
		try {
			return jsons(["status" => 200, "msg" => "success", "data" => controller("cart")->getLinkAgeList()]);
		} catch (\think\Exception $e) {
			return jsons(["status" => 400, "msg" => "error"]);
		}
	}
	/**
	 * discount  周期是否优惠
	 * @param $data
	 * @return data
	 */
	public function preferentialPeriod($data)
	{
		if (!isset($data["CartConfig"]["product"]["cycle"])) {
			return $data;
		}
		$list = $data["CartConfig"]["product"]["cycle"];
		$_list = array_column($list, null, "billingcycle");
		foreach ($list as $key => $val) {
			if ($val["billingcycle"] != "ontrial") {
				$list[$key]["is_checked"] = 1;
				break;
			}
		}
		$firstPrice = $firstCyle = null;
		$arr = ["monthly" => 1, "quarterly" => 3, "semiannually" => 6, "annually" => 12, "biennially" => 24, "triennially" => 36, "fourly" => 48, "fively" => 60, "sixly" => 72, "sevenly" => 84, "eightly" => 96, "ninely" => 108, "tenly" => 120];
		asort($arr);
		foreach ($arr as $key => $val) {
			if (isset($_list[$key])) {
				$firstPrice = $_list[$key]["product_price"];
				$firstCyle = $key;
				break;
			}
		}
		$monthPrice = $firstPrice / $arr[$firstCyle];
		foreach ($list as $key => $val) {
			$list[$key]["discount"] = 0;
			if (isset($arr[$list[$key]["billingcycle"]])) {
				$list[$key]["discount"] = $list[$key]["product_price"] / $arr[$list[$key]["billingcycle"]] < $monthPrice ? 1 : 0;
			}
			$list[$key]["is_checked"] = $list[$key]["is_checked"] ?? 0;
		}
		$data["CartConfig"]["product"]["cycle"] = $list;
		return $data;
	}
	public function setParam($request)
	{
		if ($request->param("fid")) {
			$request->first_gid = $request->param("fid");
		}
		if ($request->gid && !$request->fid) {
			$request->fid = \think\Db::name("product_groups")->where("id", $request->gid)->value("gid");
		}
	}
	public function cartGroupTpl($order_frm_tpl = "", $tpl_type = "")
	{
		$param = \request()->param();
		$theme = $param["carttheme"];
		if (!empty($theme)) {
			$path_arr = get_files(CMF_ROOT . "public/themes/cart");
			if ($theme && in_array($theme, $path_arr) || cookie("cart_theme") && in_array(cookie("cart_theme"), $path_arr)) {
				if ($theme) {
					cookie("cart_theme", $theme);
				}
				define("VIEW_TEMPLATE_DEFAULT", cookie("cart_theme") ?: $theme);
				return true;
			}
		}
		$default = configuration("order_page_style");
		$frm_tpl = ["default", "province", "area"];
		if (is_numeric($order_frm_tpl)) {
			$order_frm_tpl = $frm_tpl[$order_frm_tpl];
		}
		if (is_numeric($default)) {
			$default = $frm_tpl[$default];
		}
		$default = !empty($default) ? $default : "default";
		if ($tpl_type == "custom") {
			$tplName = !empty($order_frm_tpl) ? $order_frm_tpl : "default";
		} else {
			$tplName = $default;
		}
		$view_path = CMF_ROOT . "public/themes/cart/" . $tplName . "/";
		$yaml = view_tpl_yaml($view_path);
		$parent = $yaml["config-parent-theme"];
		if (count($yaml) > 0 && !empty($parent)) {
			$view_path_parent = CMF_ROOT . "public/themes/cart/" . $parent . "/";
			$action = $param["action"] . ".tpl";
			if (!file_exists($view_path . $action) && file_exists($view_path_parent . $action)) {
				$tplName = trim($parent);
			}
		}
		if ($param["action"] == "ordersummary" && empty($order_frm_tpl) && empty($tpl_type)) {
			$view_path = CMF_ROOT . "public/themes/cart/" . $tplName . "/configureproduct.tpl";
			if (!file_exists($view_path)) {
				$tplName = "default";
			}
		}
		define("VIEW_TEMPLATE_DEFAULT", $tplName);
	}
}